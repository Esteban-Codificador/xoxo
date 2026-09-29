import { describe, expect, it } from 'vitest';
import { computeLevels, layoutGraph, NODE_HEIGHT } from './layout';

describe('layoutGraph', () => {
    it('places prerequisites above the tracks they unlock', () => {
        const positions = layoutGraph(
            ['python', 'math', 'ml'],
            [
                { from: 'python', to: 'ml' },
                { from: 'math', to: 'ml' },
            ],
        );

        expect(positions.ml.y).toBeGreaterThanOrEqual(
            positions.python.y + NODE_HEIGHT,
        );
        expect(positions.ml.y).toBeGreaterThanOrEqual(
            positions.math.y + NODE_HEIGHT,
        );
        expect(positions.python.x).not.toBe(positions.math.x);
    });

    it('ignores edges to unknown nodes', () => {
        const positions = layoutGraph(['a'], [{ from: 'ghost', to: 'a' }]);

        expect(Object.keys(positions)).toEqual(['a']);
    });
});

describe('computeLevels', () => {
    it('groups by the longest chain of prerequisites', () => {
        expect(
            computeLevels(
                ['orientacion', 'python', 'math', 'ml', 'extra'],
                [
                    { from: 'orientacion', to: 'python' },
                    { from: 'python', to: 'ml' },
                    { from: 'math', to: 'ml' },
                    { from: 'orientacion', to: 'ml' },
                ],
            ),
        ).toEqual([['orientacion', 'math', 'extra'], ['python'], ['ml']]);
    });

    it('survives a cycle instead of looping', () => {
        expect(
            computeLevels(
                ['a', 'b'],
                [
                    { from: 'a', to: 'b' },
                    { from: 'b', to: 'a' },
                ],
            ).flat(),
        ).toEqual(expect.arrayContaining(['a', 'b']));
    });
});
