<?php

use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Publishing\LessonDraft;
use App\Domain\Curriculum\Publishing\LessonReadiness;
use App\Domain\Curriculum\Publishing\LessonTemplate;

/*
| The publishing contract (content-architecture §3), and the practice rule
| in particular: new lessons start with an empty "## Práctica" from the
| template, so only what is written under it counts.
*/

/**
 * @param  list<array<string, mixed>>  $content
 */
function doc(array $content): RichContent
{
    return RichContent::fromDocument(['type' => 'doc', 'content' => $content]);
}

/**
 * @return array<string, mixed>
 */
function h2(string $text, int $level = 2): array
{
    return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [['type' => 'text', 'text' => $text]]];
}

/**
 * @return array<string, mixed>
 */
function para(string $text): array
{
    return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
}

function draftWith(RichContent $body, int $practiceActivities = 0): LessonDraft
{
    return new LessonDraft(
        summary: str_repeat('Resumen claro. ', 8),
        whyItMatters: str_repeat('Importa a diario. ', 8),
        objectives: ['Explicar un commit', 'Crear una rama'],
        body: $body,
        skillCount: 1,
        practiceActivityCount: $practiceActivities,
        parentsPublished: true,
    );
}

/**
 * @return list<string>
 */
function issueCodes(LessonDraft $draft): array
{
    return array_map(fn ($issue) => $issue->code, (new LessonReadiness)->check($draft));
}

it('finds the text under a heading up to the next one of its level', function () {
    $body = doc([
        h2('Concepto'), para('Un commit es una instantánea.'),
        h2('Práctica'), para('Crea un repositorio.'), h2('Paso 2', 3), para('Haz tres commits.'),
        h2('Cierre'), para('Fin.'),
    ]);

    expect($body->sectionText('práctica'))->toBe("Crea un repositorio.\n\nPaso 2\n\nHaz tres commits.")
        ->and($body->sectionText('Concepto'))->toBe('Un commit es una instantánea.')
        ->and($body->sectionText('Errores comunes'))->toBeNull()
        ->and(doc([h2('Práctica')])->sectionText('Práctica'))->toBe('');
});

it('does not take the empty practice heading of the template as practice', function () {
    $filled = str_repeat('Git guarda la historia como instantáneas encadenadas. ', 40);
    $template = LessonTemplate::body();
    $withoutPractice = doc([h2('Concepto'), para($filled), h2('Práctica')]);
    $withPractice = doc([h2('Concepto'), para($filled), h2('Práctica'), para('Crea un repositorio y haz tres commits.')]);

    expect(issueCodes(draftWith($template)))->toContain('missing_practice')
        ->and(issueCodes(draftWith($withoutPractice)))->toBe(['missing_practice'])
        ->and(issueCodes(draftWith($withPractice)))->toBe([])
        // An exercise or a lab (phase 6) is practice too.
        ->and(issueCodes(draftWith($withoutPractice, practiceActivities: 1)))->toBe([]);
});
