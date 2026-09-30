<?php

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\ImportOutcome;
use App\Domain\Content\Package\ImportReport;
use App\Domain\Content\Package\InvalidContentPackage;
use App\Domain\Content\Package\PackageImporter;
use App\Domain\Content\Package\PackageReader;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Models\AuditLog;
use App\Models\ContentImportRecord;
use App\Models\Lesson;
use App\Models\MediaAsset;
use App\Models\Skill;
use App\Models\Track;
use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ContentPackageFixture;

function importFixture(ContentPackageFixture $fixture, bool $force = false, bool $dryRun = false): ImportReport
{
    return app(PackageImporter::class)->import(app(PackageReader::class)->read($fixture->path), $force, $dryRun);
}

beforeEach(function () {
    $this->fixture = ContentPackageFixture::valid();
});

afterEach(function () {
    $this->fixture->cleanup();
});

it('imports the whole package and publishes lessons', function () {
    $report = importFixture($this->fixture);

    $segunda = Lesson::query()->firstWhere('slug', 'segunda');

    expect($report->count(EntityType::Lesson, ImportOutcome::Created))->toBe(2)
        ->and($segunda->status)->toBe(ContentStatus::Published)
        ->and($segunda->publishedVersion->version)->toBe(1)
        ->and($segunda->prerequisites->pluck('slug')->all())->toBe(['primera'])
        ->and($segunda->skills->first()->getRelationValue('pivot')->getAttribute('weight'))->toBe(2)
        ->and($segunda->resources->pluck('url')->all())->toBe(['https://docs.example.test/base'])
        ->and(Track::query()->firstWhere('slug', 'avanzado')->prerequisites->first()->dependency->min_progress)->toBe(80)
        ->and(Skill::query()->firstWhere('slug', 'avanzada')->prerequisites->pluck('slug')->all())->toBe(['fundamentos'])
        ->and(Lesson::query()->visibleToLearners()->count())->toBe(2);
});

it('is idempotent', function () {
    importFixture($this->fixture);
    $auditRows = AuditLog::count();

    $report = importFixture($this->fixture);

    expect($report->count(EntityType::Lesson, ImportOutcome::Unchanged))->toBe(2)
        ->and($report->count(EntityType::Track, ImportOutcome::Unchanged))->toBe(2)
        ->and(Lesson::count())->toBe(2)
        ->and(AuditLog::count())->toBe($auditRows);
});

it('updates changed entities and publishes a new lesson version', function () {
    importFixture($this->fixture);

    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'title' => 'Título corregido']);
    $report = importFixture($this->fixture);

    $lesson = Lesson::query()->firstWhere('slug', 'primera');

    expect($report->count(EntityType::Lesson, ImportOutcome::Updated))->toBe(1)
        ->and($report->count(EntityType::Lesson, ImportOutcome::Unchanged))->toBe(1)
        ->and($lesson->publishedVersion->version)->toBe(2)
        ->and($lesson->publishedVersion->title)->toBe('Título corregido');
});

it('never overwrites what an editor changed in the CMS', function () {
    importFixture($this->fixture);
    Lesson::query()->firstWhere('slug', 'primera')->update(['title' => 'Editado en el CMS']);

    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'title' => 'Cambio en el paquete']);
    $report = importFixture($this->fixture);

    expect($report->count(EntityType::Lesson, ImportOutcome::SkippedModified))->toBe(1)
        ->and($report->warnings()[0])->toContain('modificado en el CMS')
        ->and(Lesson::query()->firstWhere('slug', 'primera')->title)->toBe('Editado en el CMS');
});

it('overwrites CMS edits only when forced', function () {
    importFixture($this->fixture);
    Lesson::query()->firstWhere('slug', 'primera')->update(['title' => 'Editado en el CMS']);
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'title' => 'Cambio en el paquete']);

    importFixture($this->fixture, force: true);

    expect(Lesson::query()->firstWhere('slug', 'primera')->title)->toBe('Cambio en el paquete');
});

it('does not bring back entities deleted in the CMS', function () {
    importFixture($this->fixture);
    Lesson::query()->firstWhere('slug', 'segunda')->delete();

    $report = importFixture($this->fixture);

    expect($report->count(EntityType::Lesson, ImportOutcome::SkippedDeleted))->toBe(1)
        ->and(Lesson::query()->where('slug', 'segunda')->exists())->toBeFalse();

    importFixture($this->fixture, force: true);

    expect(Lesson::query()->where('slug', 'segunda')->exists())->toBeTrue();
});

it('changes nothing on a dry run but reports exactly what it would do', function () {
    $report = importFixture($this->fixture, dryRun: true);

    expect($report->count(EntityType::Lesson, ImportOutcome::Created))->toBe(2)
        ->and(Lesson::count())->toBe(0)
        ->and(ContentImportRecord::count())->toBe(0)
        ->and(AuditLog::count())->toBe(0);
});

it('imports nothing from an invalid package', function () {
    $this->fixture->lesson('03-rota', ['key' => 'base.rota', 'slug' => 'rota', 'skills' => [['key' => 'fantasma', 'weight' => 1]]]);

    expect(fn () => importFixture($this->fixture))->toThrow(InvalidContentPackage::class)
        ->and(Lesson::count())->toBe(0);
});

it('audits the import as IMPORTED and the publications as PUBLISHED', function () {
    importFixture($this->fixture);

    expect(AuditLog::query()->where('action', AuditAction::Imported)->where('auditable_type', 'lesson')->count())->toBe(2)
        ->and(AuditLog::query()->where('action', AuditAction::Published)->count())->toBe(2)
        ->and(AuditLog::query()->where('action', AuditAction::Created)->count())->toBe(0);
});

it('imports the images of the package as they are, once each', function () {
    $bytes = imageBytes(200, 100);
    $this->fixture->write('media/ciclo.png', $bytes);
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera'], ContentPackageFixture::lessonBody('![Ciclo de vida](media/ciclo.png)'));
    $this->fixture->lesson('02-segunda', [
        'key' => 'base.segunda', 'slug' => 'segunda',
        'depends_on' => [['lesson' => 'base.primera', 'kind' => 'REQUIRED']],
    ], ContentPackageFixture::lessonBody('![El mismo ciclo, otra vez](media/ciclo.png)'));

    // A dry run leaves no row and no file behind.
    importFixture($this->fixture, dryRun: true);
    expect(MediaAsset::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    importFixture($this->fixture);
    $asset = MediaAsset::query()->sole();
    $primera = Lesson::firstWhere('slug', 'primera');

    // Not re-encoded: the checksum that names the file in the package stays the same.
    expect($asset->checksum)->toBe(hash('sha256', $bytes))
        ->and(Storage::disk('local')->get($asset->path))->toBe($bytes)
        ->and([$asset->width, $asset->height, $asset->uploaded_by])->toBe([200, 100, null])
        ->and($primera->publishedVersion->body->mediaIds())->toBe([$asset->id])
        ->and(collect($primera->publishedVersion->body->doc['content'])->firstWhere('type', 'image')['attrs']['alt'])->toBe('Ciclo de vida')
        ->and(Lesson::firstWhere('slug', 'segunda')->body->mediaIds())->toBe([$asset->id]);

    $report = importFixture($this->fixture);

    expect($report->count(EntityType::Lesson, ImportOutcome::Unchanged))->toBe(2)
        ->and(MediaAsset::count())->toBe(1);
});

it('imports the videos of the package and the lessons that show them, in order', function () {
    Http::preventStrayRequests();
    Http::fake(['www.youtube.com/oembed*' => Http::response(['title' => 'Un video', 'thumbnail_url' => 'https://i.ytimg.com/vi/x/hqdefault.jpg'])]);
    $this->fixture->yaml('videos/videos.yaml', [
        ['key' => 'intuicion', 'url' => 'https://www.youtube.com/watch?v=fake-abcdef', 'title' => 'La intuición', 'instructor' => '3Blue1Brown', 'duration' => '18:40', 'language' => 'en', 'difficulty' => 'BEGINNER', 'description' => 'Antes de las fórmulas.'],
        ['key' => 'detalle', 'url' => 'fake-ghijkl', 'title' => 'Backpropagation', 'language' => 'en', 'status' => 'DRAFT'],
    ]);
    $this->fixture->lesson('01-primera', ['key' => 'base.primera', 'slug' => 'primera', 'videos' => ['detalle', 'intuicion']]);

    // A dry run asks YouTube nothing.
    importFixture($this->fixture, dryRun: true);
    Http::assertNothingSent();
    expect(Video::count())->toBe(0);

    $report = importFixture($this->fixture);
    $intuicion = Video::query()->firstWhere('external_id', 'fake-abcdef');

    expect($report->count(EntityType::Video, ImportOutcome::Created))->toBe(2)
        ->and([$intuicion->title, $intuicion->instructor, $intuicion->duration_seconds, $intuicion->status])
        ->toBe(['La intuición', '3Blue1Brown', 1120, ContentStatus::Published])
        // Checked with oEmbed once imported.
        ->and($intuicion->link_status)->toBe(LinkStatus::Ok)
        ->and(Video::query()->firstWhere('external_id', 'fake-ghijkl')->status)->toBe(ContentStatus::Draft)
        ->and(Lesson::firstWhere('slug', 'primera')->videos()->pluck('external_id')->all())->toBe(['fake-ghijkl', 'fake-abcdef']);
    Http::assertSentCount(2);

    // Importing again changes nothing and asks nothing.
    $again = importFixture($this->fixture);
    expect($again->count(EntityType::Video, ImportOutcome::Unchanged))->toBe(2)
        ->and($again->count(EntityType::Lesson, ImportOutcome::Unchanged))->toBe(2);
    Http::assertSentCount(2);
});
