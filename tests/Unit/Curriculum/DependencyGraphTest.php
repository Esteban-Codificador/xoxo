<?php

use App\Domain\Curriculum\Graph\CycleDetected;
use App\Domain\Curriculum\Graph\DependencyGraph;

// Edges are [node, prerequisite]: "ml depends on python".
function curriculumGraph(): DependencyGraph
{
    return DependencyGraph::fromEdges([
        ['numpy', 'python'],
        ['math', 'python'],
        ['ml', 'numpy'],
        ['ml', 'math'],
        ['dl', 'ml'],
    ], ['orientation']);
}

it('orders prerequisites before their dependents', function () {
    $order = curriculumGraph()->topologicalOrder();

    expect($order)->toHaveCount(6)
        ->and(array_search('python', $order))->toBeLessThan(array_search('numpy', $order))
        ->and(array_search('numpy', $order))->toBeLessThan(array_search('ml', $order))
        ->and(array_search('math', $order))->toBeLessThan(array_search('ml', $order))
        ->and(array_search('ml', $order))->toBeLessThan(array_search('dl', $order));
});

it('keeps isolated nodes in the ordering', function () {
    expect(curriculumGraph()->topologicalOrder())->toContain('orientation');
});

it('ranks nodes by their longest path from a root', function () {
    expect(curriculumGraph()->ranks())->toBe([
        'orientation' => 0,
        'python' => 0,
        'numpy' => 1,
        'math' => 1,
        'ml' => 2,
        'dl' => 3,
    ]);
});

it('finds no cycle in an acyclic graph', function () {
    expect(curriculumGraph()->findCycle())->toBeNull();
});

it('reports the nodes of an existing cycle', function () {
    $graph = DependencyGraph::fromEdges([['a', 'b'], ['b', 'c'], ['c', 'a']]);

    expect($graph->findCycle())->toBe(['a', 'b', 'c', 'a']);
});

it('refuses to order a cyclic graph', function () {
    DependencyGraph::fromEdges([['a', 'b'], ['b', 'a']])->topologicalOrder();
})->throws(CycleDetected::class, 'a → b → a');

it('detects that a new edge would close a cycle', function () {
    // ml already depends on python (through numpy and math): making python depend on ml closes the loop.
    $graph = curriculumGraph();
    $cycle = $graph->wouldCreateCycle('python', 'ml');

    expect($cycle)->toHaveCount(4)
        ->and($cycle[0])->toBe('python')
        ->and($cycle[1])->toBe('ml')
        ->and($cycle[3])->toBe('python')
        ->and($graph->prerequisitesOf('ml'))->toContain($cycle[2])
        ->and($graph->prerequisitesOf($cycle[2]))->toContain('python');
});

it('accepts a new edge that keeps the graph acyclic', function () {
    expect(curriculumGraph()->wouldCreateCycle('dl', 'python'))->toBeNull();
});

it('rejects self dependencies', function () {
    expect(curriculumGraph()->wouldCreateCycle('ml', 'ml'))->toBe(['ml', 'ml']);
});

it('lists prerequisites and dependents of a node', function () {
    $graph = curriculumGraph();

    expect($graph->prerequisitesOf('ml'))->toBe(['numpy', 'math'])
        ->and($graph->dependentsOf('python'))->toBe(['numpy', 'math']);
});

it('works with integer ids', function () {
    $graph = DependencyGraph::fromEdges([[3, 1], [2, 1], [3, 2]]);

    expect($graph->topologicalOrder())->toBe([1, 2, 3]);
});
