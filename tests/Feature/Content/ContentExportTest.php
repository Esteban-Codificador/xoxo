<?php

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\Export\ExportFormat;
use App\Domain\Content\Package\Export\ExportPlan;
use App\Domain\Content\Package\Export\ExportRefused;
use App\Domain\Content\Package\Export\FileChangeKind;
use App\Domain\Content\Package\Export\PackageExporter;
use App\Domain\Content\Package\ImportOutcome;
use App\Domain\Content\Package\PackageImporter;
use App\Domain\Content\Package\PackageReader;
use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Actions\CreateLesson;
use App\Domain\Curriculum\Actions\CreateModule;
use App\Domain\Curriculum\Actions\CreateTrack;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Domain\Curriculum\Actions\UpdateRoadmap;
use App\Domain\Curriculum\Publishing\LessonTemplate;
use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\UnlockPolicy;
use App\Models\ContentImportRecord;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\MediaAsset;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
| content:export on a copy of the real package (hand-written formatting
| included): what did not change keeps its bytes, what changed in the CMS
| is written so that importing it reproduces the database, and nothing
| edited by hand is ever overwritten silently.
*/

const LESSON_FILE = 'tracks/01-fundamentos-computacion/04-git-y-colaboracion/01-commits-arbol-de-trabajo-y-staging.md';

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/content-export-test-'.Str::random(10);
    $this->path = "{$this->root}/ai-engineer";
    File::copyDirectory(base_path('content/ai-engineer'), $this->path);
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

function exportPlan(string $path, bool $copy = false, bool $force = false): ExportPlan
{
    return app(PackageExporter::class)->plan($path, $copy, $force);
}

function exportTo(string $path, bool $copy = false, bool $force = false): ExportPlan
{
    $plan = exportPlan($path, $copy, $force);
    expect($plan->conflicts)->toBe([]);
    app(PackageExporter::class)->apply($plan);

    return $plan;
}

/**
 * @return array<string, string> Relative path => contents.
 */
function packageFiles(string $path): array
{
    $files = [];

    foreach (File::allFiles($path) as $file) {
        $files[$file->getRelativePathname()] = $file->getContents();
    }

    ksort($files);

    return $files;
}

/**
 * @return list<string>
 */
function changedPaths(ExportPlan $plan): array
{
    return array_map(fn ($change) => "{$change->kind->value} {$change->path}", $plan->pending());
}

/**
 * Starts from an empty database, as `migrate:fresh` would.
 */
function wipeContent(): void
{
    DB::statement('TRUNCATE roadmaps, skills, resources, content_import_records RESTART IDENTITY CASCADE');
}

it('leaves the package byte for byte identical right after importing it', function () {
    $before = packageFiles($this->path);

    $plan = exportTo($this->path);

    expect($plan->pending())->toBe([])
        ->and($plan->count(FileChangeKind::Unchanged))->toBe(16)
        ->and(packageFiles($this->path))->toBe($before);
});

it('renders a package that means exactly what the database holds', function () {
    $copy = "{$this->root}/copia";
    exportTo($copy, copy: true);

    $reader = app(PackageReader::class);
    $original = $reader->read($this->path);
    $exported = $reader->read($copy);
    $format = app(ExportFormat::class);

    foreach (EntityType::cases() as $type) {
        expect(array_keys($exported->all($type)))->toEqualCanonicalizing(array_keys($original->all($type)));

        foreach ($original->all($type) as $key => $entity) {
            expect($format->comparable($exported->find($type, $key)))->toEqual($format->comparable($entity), "{$type->value} {$key}");
        }
    }

    $this->artisan('content:validate', ['path' => $copy])->assertSuccessful();
});

it('rewrites only what changed in the CMS, and a fresh import reproduces it', function () {
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $lesson->title = 'Git: commits, árbol de trabajo y área de staging';
    $lesson->body = RichContent::fromDocument([
        'type' => 'doc',
        'content' => [...$lesson->body->doc['content'], ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Párrafo añadido en el CMS.']]]],
    ]);
    $lesson->save();
    app(PublishLesson::class)->handle($lesson, 'Añade un párrafo');

    Skill::firstWhere('slug', 'git')->update(['slug' => 'git-basico', 'difficulty' => Difficulty::Intermediate]);
    ExternalResource::firstWhere('url', 'https://git-scm.com/docs/git-rebase')->update(['status' => ContentStatus::Archived]);

    $plan = exportTo($this->path);

    expect(changedPaths($plan))->toBe([
        'updated resources/fundamentos.yaml',
        'updated skills/git.md',
        'updated '.LESSON_FILE,
    ]);

    $written = (string) file_get_contents("{$this->path}/".LESSON_FILE);
    expect($written)->toContain("title: 'Git: commits, árbol de trabajo y área de staging'")
        ->toContain('Párrafo añadido en el CMS.')
        ->and((string) file_get_contents("{$this->path}/skills/git.md"))->toContain('slug: git-basico');

    // A new database gets the same content from the package alone.
    wipeContent();
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();

    $reimported = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    expect($reimported->publishedVersion->title)->toBe('Git: commits, árbol de trabajo y área de staging')
        ->and($reimported->publishedVersion->body->plainText())->toContain('Párrafo añadido en el CMS.')
        ->and(Skill::firstWhere('slug', 'git-basico')?->difficulty)->toBe(Difficulty::Intermediate)
        ->and($reimported->skills->pluck('slug')->all())->toBe(['git-basico'])
        ->and(ExternalResource::firstWhere('url', 'https://git-scm.com/docs/git-rebase')->status)->toBe(ContentStatus::Archived);
});

it('marks the database as in sync, so importing right after changes nothing', function () {
    Lesson::firstWhere('slug', 'ramas-merge-y-rebase')->update(['last_reviewed_at' => '2026-09-29']);
    exportTo($this->path);

    $report = app(PackageImporter::class)->import(app(PackageReader::class)->read($this->path), dryRun: true);

    foreach (EntityType::cases() as $type) {
        expect($report->count($type, ImportOutcome::SkippedModified))->toBe(0)
            ->and($report->count($type, ImportOutcome::Updated))->toBe(0);
    }
});

it('adopts skills and resources created in the CMS', function () {
    $skill = Skill::factory()->create(['slug' => 'python', 'name' => 'Python', 'description' => 'Programación en Python.']);
    $resource = ExternalResource::factory()->create(['title' => 'Guía de staging', 'url' => 'https://docs.example.test/staging']);
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $lesson->resources()->attach($resource, ['position' => 5]);

    $plan = exportTo($this->path);

    expect(changedPaths($plan))->toBe([
        'updated resources/fundamentos.yaml',
        'created skills/python.md',
        'updated '.LESSON_FILE,
    ])->and(ContentImportRecord::query()->where('key', 'skill:python')->value('importable_id'))->toBe($skill->id)
        ->and(ContentImportRecord::query()->where('key', 'resource:guia-de-staging')->value('importable_id'))->toBe($resource->id);

    // Same database: nothing new to create.
    $report = app(PackageImporter::class)->import(app(PackageReader::class)->read($this->path), dryRun: true);
    expect($report->count(EntityType::Skill, ImportOutcome::Created))->toBe(0)
        ->and($report->count(EntityType::Resource, ImportOutcome::Created))->toBe(0);

    // New database: they come with the package, attached where they were.
    wipeContent();
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();
    expect(Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging')->resources->pluck('url'))
        ->toContain('https://docs.example.test/staging')
        ->and(Skill::firstWhere('slug', 'python')?->description)->toBe('Programación en Python.');
});

it('exports the published version and reports unpublished changes', function () {
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $lesson->update(['title' => 'Borrador sin publicar']);

    $plan = exportTo($this->path);

    expect($plan->pending())->toBe([])
        ->and($plan->warnings)->toHaveCount(1)
        ->and($plan->warnings[0])->toContain('Borrador sin publicar')->toContain('versión 1');

    // The draft stays in the database, untouched by an import.
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();
    expect($lesson->refresh()->title)->toBe('Borrador sin publicar');
});

it('refuses to overwrite a file edited by hand that was never imported', function () {
    $file = "{$this->path}/".LESSON_FILE;
    File::put($file, str_replace('estimated_minutes: 40', 'estimated_minutes: 45', (string) file_get_contents($file)));
    $edited = (string) file_get_contents($file);

    expect(exportPlan($this->path)->conflicts)->toHaveCount(1)
        ->and(exportPlan($this->path)->conflicts[0])->toContain('cambió en el archivo y no se importó');

    $this->artisan('content:export', ['path' => $this->path])->assertFailed();
    expect(file_get_contents($file))->toBe($edited);

    // Importing first resolves it: the export then has nothing to change.
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();
    expect(exportPlan($this->path)->conflicts)->toBe([])
        ->and(exportPlan($this->path)->pending())->toBe([]);
});

it('reports a conflict when the file and the database both changed, and --force keeps the database', function () {
    $file = "{$this->path}/".LESSON_FILE;
    File::put($file, str_replace('estimated_minutes: 40', 'estimated_minutes: 45', (string) file_get_contents($file)));
    Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging')->update(['last_reviewed_at' => '2026-09-29']);

    expect(exportPlan($this->path)->conflicts[0])->toContain('en el archivo y en la base de datos');

    exportTo($this->path, force: true);
    expect(file_get_contents($file))->toContain('estimated_minutes: 40')->toContain("last_reviewed: '2026-09-29'");
});

it('does not delete a file that was never imported', function () {
    File::put("{$this->path}/skills/nueva.md", "---\nkey: nueva\nname: Nueva\ndifficulty: BEGINNER\nstatus: PUBLISHED\n---\nUna skill escrita a mano.\n");

    expect(exportPlan($this->path)->conflicts[0])->toContain('skills/nueva.md: nunca se importó');
});

it('refuses database states the package cannot represent and writes nothing', function () {
    Module::firstWhere('slug', 'git-y-colaboracion')->update(['status' => ContentStatus::Draft]);
    $before = packageFiles($this->path);

    expect(fn () => exportPlan($this->path))->toThrow(ExportRefused::class);

    try {
        exportPlan($this->path);
    } catch (ExportRefused $exception) {
        expect(implode("\n", $exception->reasons))->toContain('contrato de publicación');
    }

    $this->artisan('content:export', ['path' => $this->path])->assertFailed();
    expect(packageFiles($this->path))->toBe($before);
});

it('writes a copy anywhere without moving the sync point', function () {
    Skill::firstWhere('slug', 'git')->update(['name' => 'Git y GitHub']);
    $records = ContentImportRecord::query()->orderBy('id')->get(['key', 'source_hash', 'entity_hash'])->toArray();

    $this->artisan('content:export', ['path' => "{$this->root}/respaldo", '--copy' => true])->assertSuccessful();

    expect(file_get_contents("{$this->root}/respaldo/skills/git.md"))->toContain("name: 'Git y GitHub'")
        ->and(ContentImportRecord::query()->orderBy('id')->get(['key', 'source_hash', 'entity_hash'])->toArray())->toBe($records);
});

it('only syncs to the package directory itself', function () {
    expect(fn () => exportPlan("{$this->root}/otro-nombre"))->toThrow(ExportRefused::class, 'exporta a un directorio con ese nombre');

    // A fresh directory never becomes the reference once a package is in sync.
    expect(fn () => exportPlan("{$this->root}/vacio/ai-engineer"))->toThrow(ExportRefused::class, 'ya está sincronizada');

    File::put("{$this->root}/cualquiera.txt", 'x');
    expect(fn () => exportPlan($this->root, copy: true))->toThrow(ExportRefused::class, 'no es un paquete de contenido');
});

it('shows what would change on a dry run without writing', function () {
    Skill::firstWhere('slug', 'git')->update(['name' => 'Git y GitHub']);
    $before = packageFiles($this->path);

    $this->artisan('content:export', ['path' => $this->path, '--dry-run' => true])
        ->expectsOutputToContain('modificado  skills/git.md')
        ->expectsOutputToContain('Simulación: no se escribió nada.')
        ->assertSuccessful();

    expect(packageFiles($this->path))->toBe($before);
});

it('keeps the hand-written text of every field and block that did not change', function () {
    $file = "{$this->path}/".LESSON_FILE;
    $before = (string) file_get_contents($file);
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $lesson->summary .= ' Con ejemplos de git add -p.';
    $doc = $lesson->body->doc;
    $doc['content'][] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Cierre añadido en el CMS.']]];
    $lesson->body = RichContent::fromDocument($doc);
    $lesson->save();
    app(PublishLesson::class)->handle($lesson);

    exportTo($this->path);
    $after = (string) file_get_contents($file);

    // Removed: only the four lines of the folded summary. Added: the new summary and the new paragraph.
    $removed = array_values(array_diff(explode("\n", $before), explode("\n", $after)));
    $added = array_values(array_diff(explode("\n", $after), explode("\n", $before)));

    expect($removed)->toHaveCount(4)
        ->and($removed[0])->toBe('summary: >-')
        ->and($added)->toHaveCount(2)
        ->and($added[0])->toStartWith('summary: ')->toContain('Con ejemplos de git add -p.')
        ->and($added[1])->toBe('Cierre añadido en el CMS.');
});

it('rewrites only the resource that changed inside a shared file', function () {
    $before = (string) file_get_contents("{$this->path}/resources/fundamentos.yaml");
    ExternalResource::firstWhere('url', 'https://git-scm.com/docs')->update(['description' => 'Referencia oficial de todos los comandos.']);

    exportTo($this->path);
    $after = (string) file_get_contents("{$this->path}/resources/fundamentos.yaml");

    expect(array_values(array_diff(explode("\n", $after), explode("\n", $before))))
        ->toBe(["  description: 'Referencia oficial de todos los comandos.'"])
        ->and(array_values(array_diff(explode("\n", $before), explode("\n", $after))))
        ->toBe(['  description: Referencia oficial de todos los comandos de Git.']);
});

it('accepts what the editor saves and refuses what Markdown cannot carry', function () {
    $document = json_decode((string) file_get_contents(base_path('resources/js/features/rich-content/every-node.fixture.json')), true);
    $track = Track::firstWhere('slug', 'orientacion');
    $image = storedImage();
    $document['doc']['content'][12]['attrs']['mediaId'] = $image->id;

    // Null link attributes, default cell spans: the editor's output exports as is, with its image.
    $track->update(['description' => RichContent::fromArray($document)]);
    expect(changedPaths(exportPlan($this->path)))->toBe(['created '.$image->packagePath(), 'updated tracks/00-orientacion/track.md']);

    // A merged cell has no GFM form.
    $document['doc']['content'][13]['content'][0]['content'][0]['attrs']['colspan'] = 2;
    $track->update(['description' => RichContent::fromArray($document)]);

    expect(fn () => exportPlan($this->path))->toThrow(ExportRefused::class, 'no puede representar fielmente');
});

it('exports tracks, modules and lessons created in the CMS, and imports them back', function () {
    $roadmap = Roadmap::firstWhere('slug', 'ai-engineer');
    $track = app(CreateTrack::class)->handle($roadmap, [
        'title' => 'Python para IA', 'slug' => 'python-para-ia', 'summary' => 'Python como lenguaje de trabajo.',
        'why_it_matters' => 'El ecosistema de IA se escribe en Python.', 'difficulty' => 'BEGINNER', 'estimated_hours' => 20,
    ]);
    $module = app(CreateModule::class)->handle($track, [
        'title' => 'Sintaxis', 'slug' => 'sintaxis', 'summary' => 'Lo básico del lenguaje.',
    ]);
    app(CreateLesson::class)->handle($module, [
        'title' => 'Variables y tipos', 'slug' => 'variables-y-tipos', 'summary' => 'Qué guarda una variable.',
        'why_it_matters' => 'Todo programa empieza por sus datos.', 'content_type' => 'CONCEPT', 'difficulty' => 'BEGINNER', 'estimated_minutes' => 20,
    ]);

    $plan = exportTo($this->path);

    expect(changedPaths($plan))->toBe([
        'created tracks/02-python-para-ia/01-sintaxis/01-variables-y-tipos.md',
        'created tracks/02-python-para-ia/01-sintaxis/module.md',
        'created tracks/02-python-para-ia/track.md',
    ])->and((string) file_get_contents("{$this->path}/tracks/02-python-para-ia/01-sintaxis/01-variables-y-tipos.md"))
        ->toContain('status: DRAFT')->toContain('## Práctica');

    wipeContent();
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();

    $lesson = Lesson::firstWhere('slug', 'variables-y-tipos');
    expect($lesson->status)->toBe(ContentStatus::Draft)
        ->and($lesson->module->track->slug)->toBe('python-para-ia')
        ->and($lesson->body->headings(2))->toBe(LessonTemplate::SECTIONS);
});

it('exports the roadmap edited in the CMS field by field, and imports it back', function () {
    $roadmap = Roadmap::firstWhere('slug', 'ai-engineer');
    $before = (string) file_get_contents("{$this->path}/roadmap.yaml");
    $description = $roadmap->description?->toArray();
    $description['doc']['content'][] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Párrafo añadido en el CMS.']]];

    app(UpdateRoadmap::class)->handle($roadmap, [
        'title' => 'Ingeniería de IA',
        'slug' => 'ai-engineer',
        'summary' => $roadmap->summary,
        'description' => $description,
        'unlock_policy' => 'STRICT',
    ]);

    $plan = exportTo($this->path);
    $written = (string) file_get_contents("{$this->path}/roadmap.yaml");

    expect(changedPaths($plan))->toBe(['updated roadmap.yaml'])
        ->and($written)->toContain('unlock_policy: STRICT')
        ->toContain('Párrafo añadido en el CMS.');

    // Only what was edited changed: two fields and one new paragraph. The
    // rest, the wrapped description paragraphs included, keeps its text.
    $removed = array_values(array_diff(explode("\n", $before), explode("\n", $written)));
    $added = array_values(array_diff(explode("\n", $written), explode("\n", $before)));
    expect($removed)->toBe(['title: AI Engineer', 'unlock_policy: ADVISORY'])
        ->and($added)->toBe(["title: 'Ingeniería de IA'", 'unlock_policy: STRICT', '  Párrafo añadido en el CMS.']);

    wipeContent();
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();

    $reimported = Roadmap::firstWhere('slug', 'ai-engineer');
    expect($reimported->title)->toBe('Ingeniería de IA')
        ->and($reimported->unlock_policy)->toBe(UnlockPolicy::Strict)
        ->and($reimported->description?->plainText())->toContain('Párrafo añadido en el CMS.');
});

/**
 * Adds an image after the first paragraph of the lesson and publishes it.
 */
function publishWithImage(Lesson $lesson, MediaAsset $image, string $alt): void
{
    $content = $lesson->body->doc['content'];
    array_splice($content, 1, 0, [['type' => 'image', 'attrs' => ['mediaId' => $image->id, 'alt' => $alt]]]);
    $lesson->update(['body' => RichContent::fromDocument(['type' => 'doc', 'content' => $content])]);
    app(PublishLesson::class)->handle($lesson->refresh(), 'Añade un diagrama');
}

it('exports the images the content shows, and a fresh import brings them back', function () {
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    $file = 'tracks/01-fundamentos-computacion/04-git-y-colaboracion/01-commits-arbol-de-trabajo-y-staging.md';
    $image = storedImage(320, 200);
    $bytes = (string) Storage::disk('local')->get($image->path);
    publishWithImage($lesson, $image, 'Las tres áreas de Git: árbol de trabajo, staging y repositorio');

    $plan = exportTo($this->path);

    expect(changedPaths($plan))->toBe(['created '.$image->packagePath(), "updated {$file}"])
        ->and(file_get_contents("{$this->path}/{$image->packagePath()}"))->toBe($bytes)
        ->and((string) file_get_contents("{$this->path}/{$file}"))
        ->toContain("\n\n![Las tres áreas de Git: árbol de trabajo, staging y repositorio]({$image->packagePath()})\n\n")
        ->and(exportPlan($this->path)->pending())->toBe([]);

    // A new environment: no rows, no files.
    wipeContent();
    DB::table('media_assets')->delete();
    Storage::disk('local')->deleteDirectory('media');
    $this->artisan('content:import', ['path' => $this->path])->assertSuccessful();

    $restored = MediaAsset::query()->sole();
    $body = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging')->publishedVersion->body;

    expect($restored->checksum)->toBe($image->checksum)
        ->and(Storage::disk('local')->get($restored->path))->toBe($bytes)
        ->and($body->mediaIds())->toBe([$restored->id])
        ->and(exportPlan($this->path)->pending())->toBe([]);
});

it('removes from the package an image the content no longer shows', function () {
    $lesson = Lesson::firstWhere('slug', 'ramas-merge-y-rebase');
    $old = storedImage(rgb: [10, 10, 10]);
    publishWithImage($lesson, $old, 'Una rama que sale de main');
    exportTo($this->path);

    $new = storedImage(rgb: [250, 250, 250]);
    $content = array_map(
        fn (array $node) => $node['type'] === 'image' ? ['type' => 'image', 'attrs' => ['mediaId' => $new->id, 'alt' => 'Dos ramas unidas por un merge']] : $node,
        $lesson->refresh()->body->doc['content'],
    );
    $lesson->update(['body' => RichContent::fromDocument(['type' => 'doc', 'content' => $content])]);
    app(PublishLesson::class)->handle($lesson->refresh());

    // Sorted by path, and file names come from checksums: compare as a set.
    expect(changedPaths(exportTo($this->path)))->toEqualCanonicalizing([
        'deleted '.$old->packagePath(),
        'created '.$new->packagePath(),
        'updated tracks/01-fundamentos-computacion/04-git-y-colaboracion/02-ramas-merge-y-rebase.md',
    ])->and(is_file("{$this->path}/{$old->packagePath()}"))->toBeFalse();
});

it('refuses to export an image whose file is gone', function () {
    $image = storedImage();
    publishWithImage(Lesson::firstWhere('slug', 'ramas-merge-y-rebase'), $image, 'Diagrama de ramas');
    Storage::disk('local')->delete($image->path);

    expect(fn () => exportPlan($this->path))->toThrow(ExportRefused::class, "Falta el archivo de la imagen {$image->id}");
});
