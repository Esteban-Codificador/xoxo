<?php

use App\Domain\Assessment\QuestionTypes\Matching;
use App\Domain\Assessment\QuestionTypes\MultipleChoice;
use App\Domain\Assessment\QuestionTypes\OptionId;
use App\Domain\Assessment\QuestionTypes\Ordering;
use App\Domain\Assessment\QuestionTypes\SingleChoice;
use App\Domain\Assessment\QuestionTypes\Texts;
use App\Domain\Assessment\QuestionTypes\TrueFalse;
use Random\Engine\Mt19937;
use Random\Randomizer;

/*
| The question-type engine (ADR-022): what an author may write, what the
| learner sees (never the answer), how an answer is read and graded.
*/

function seededRandom(int $seed = 7): Randomizer
{
    return new Randomizer(new Mt19937($seed));
}

it('identifies options by a hash of their text, not by position', function () {
    expect(OptionId::of('git rebase'))->toBe(OptionId::of('git rebase'))
        ->and(OptionId::of('git rebase'))->not->toBe(OptionId::of('git merge'))
        ->and(OptionId::of('git rebase'))->toMatch('/^[0-9a-f]{10}$/');
});

it('grades a single choice question and hides which option is right', function () {
    $type = new SingleChoice;
    $payload = ['options' => [['text' => 'merge', 'correct' => false], ['text' => 'rebase', 'correct' => true]]];

    $shown = $type->present($payload, seededRandom());

    expect($shown)->toBe(['options' => [
        ['id' => OptionId::of('merge'), 'text' => 'merge'],
        ['id' => OptionId::of('rebase'), 'text' => 'rebase'],
    ]])
        ->and(json_encode($shown))->not->toContain('correct')
        ->and($type->answer($payload, ['choice' => OptionId::of('rebase')]))->toBe(['choice' => OptionId::of('rebase')])
        ->and($type->isCorrect($payload, ['choice' => OptionId::of('rebase')]))->toBeTrue()
        ->and($type->isCorrect($payload, ['choice' => OptionId::of('merge')]))->toBeFalse()
        ->and($type->answer($payload, ['choice' => 'otra']))->toBeNull()
        ->and($type->answer($payload, 'rebase'))->toBeNull()
        ->and($type->solution($payload))->toBe(['choice' => OptionId::of('rebase')]);
});

it('checks what an author writes for a choice question', function () {
    $single = new SingleChoice;
    $multiple = new MultipleChoice;

    expect($single->problems(['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => true]]]))->toHaveKey('options')
        ->and($single->problems(['options' => [['text' => 'a', 'correct' => false], ['text' => 'b', 'correct' => false]]]))->toHaveKey('options')
        ->and($multiple->problems(['options' => [['text' => 'a', 'correct' => false], ['text' => 'b', 'correct' => false]]]))->toHaveKey('options')
        ->and($multiple->problems(['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => true]]]))->toBe([])
        ->and($single->problems(['options' => [['text' => 'a', 'correct' => true]]]))->toHaveKey('options')
        ->and($single->problems(['options' => [['text' => 'a', 'correct' => true], ['text' => ' a ', 'correct' => false]]]))->toHaveKey('options.1.text')
        ->and($single->problems(['options' => [['text' => '', 'correct' => true], ['text' => 'b', 'correct' => 'yes']]]))->toHaveKeys(['options.0.text', 'options.1.correct'])
        ->and($single->problems(['options' => [['text' => str_repeat('x', 301), 'correct' => true], ['text' => 'b', 'correct' => false]]]))->toHaveKey('options.0.text')
        ->and($single->problems(['options' => array_fill(0, 9, ['text' => 'x', 'correct' => false])]))->toHaveKey('options')
        ->and($single->problems('nada'))->toHaveKey('options')
        ->and($single->normalize(['options' => [['text' => ' a ', 'correct' => true, 'extra' => 1], ['text' => 'b', 'correct' => false]]]))
        ->toBe(['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => false]]]);
});

it('grades a multiple choice question only when exactly the right options are marked', function () {
    $type = new MultipleChoice;
    $payload = ['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => false], ['text' => 'c', 'correct' => true]]];
    [$a, $b, $c] = [OptionId::of('a'), OptionId::of('b'), OptionId::of('c')];

    expect($type->answer($payload, ['choices' => [$c, $a, 'ajena']]))->toBe(['choices' => [$a, $c]])
        ->and($type->isCorrect($payload, ['choices' => [$a, $c]]))->toBeTrue()
        ->and($type->isCorrect($payload, ['choices' => [$a]]))->toBeFalse()
        ->and($type->isCorrect($payload, ['choices' => [$a, $b, $c]]))->toBeFalse()
        ->and($type->answer($payload, ['choices' => []]))->toBeNull()
        ->and($type->solution($payload))->toBe(['choices' => [$a, $c]]);
});

it('grades a true or false statement', function () {
    $type = new TrueFalse;

    expect($type->problems(['answer' => false]))->toBe([])
        ->and($type->problems(['answer' => 'false']))->toHaveKey('answer')
        ->and($type->present(['answer' => false], seededRandom()))->toBe([])
        ->and($type->answer(['answer' => false], ['value' => false]))->toBe(['value' => false])
        ->and($type->answer(['answer' => false], ['value' => 'false']))->toBeNull()
        ->and($type->isCorrect(['answer' => false], ['value' => false]))->toBeTrue()
        ->and($type->isCorrect(['answer' => false], ['value' => true]))->toBeFalse();
});

it('shuffles the items of an ordering question and grades the full order', function () {
    $type = new Ordering;
    $payload = ['items' => ['add', 'commit', 'push']];
    $ids = array_map(OptionId::of(...), $payload['items']);

    foreach (range(1, 30) as $seed) {
        $shown = array_column($type->present($payload, seededRandom($seed))['items'], 'id');

        expect($shown)->not->toBe($ids)->and(collect($shown)->sort()->values()->all())->toBe(collect($ids)->sort()->values()->all());
    }

    expect($type->present($payload, seededRandom(3)))->toBe($type->present($payload, seededRandom(3)))
        ->and($type->isCorrect($payload, ['order' => $ids]))->toBeTrue()
        ->and($type->isCorrect($payload, ['order' => array_reverse($ids)]))->toBeFalse()
        ->and($type->answer($payload, ['order' => [$ids[0], $ids[0], $ids[1]]]))->toBeNull()
        ->and($type->answer($payload, ['order' => [$ids[0], $ids[1]]]))->toBeNull()
        ->and($type->problems(['items' => ['uno']]))->toHaveKey('items')
        ->and($type->problems(['items' => ['uno', 'uno']]))->toHaveKey('items.1')
        ->and($type->solution($payload))->toBe(['order' => $ids]);
});

it('shows the right column of a matching question shuffled and grades every pair', function () {
    $type = new Matching;
    $payload = ['pairs' => [['left' => 'add', 'right' => 'prepara'], ['left' => 'commit', 'right' => 'guarda'], ['left' => 'push', 'right' => 'publica']]];
    $solution = $type->solution($payload)['matches'];

    foreach (range(1, 30) as $seed) {
        $shown = $type->present($payload, seededRandom($seed));

        expect(array_column($shown['left'], 'text'))->toBe(['add', 'commit', 'push'])
            ->and(array_column($shown['right'], 'text'))->not->toBe(['prepara', 'guarda', 'publica']);
    }

    $wrong = $solution;
    [$first, $second] = array_keys($wrong);
    [$wrong[$first], $wrong[$second]] = [$wrong[$second], $wrong[$first]];

    expect($type->isCorrect($payload, ['matches' => $solution]))->toBeTrue()
        ->and($type->isCorrect($payload, ['matches' => $wrong]))->toBeFalse()
        ->and($type->isCorrect($payload, ['matches' => array_slice($solution, 0, 2, true)]))->toBeFalse()
        ->and($type->answer($payload, ['matches' => [OptionId::of('add') => 'ajeno']]))->toBeNull()
        ->and($type->problems(['pairs' => [['left' => 'a', 'right' => 'x'], ['left' => 'b', 'right' => 'x']]]))->toHaveKey('pairs.1.right')
        ->and($type->problems(['pairs' => [['left' => 'a', 'right' => 'x'], ['left' => 'b']]]))->toHaveKey('pairs.1.right');
});

it('never returns the given order when shuffling two or more', function () {
    expect(Texts::shuffleApart([1, 2], seededRandom(1)))->toBe([2, 1])
        ->and(Texts::shuffleApart([1], seededRandom(1)))->toBe([1]);
});
