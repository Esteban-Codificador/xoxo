import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * WCAG contrast of the design tokens in resources/css/app.css, computed from
 * their OKLCH values. Guards docs/frontend-architecture.md §5: state colors
 * must be readable (4.5:1) and progress fills visible (3:1) in both themes.
 */
const css = readFileSync(
    resolve(process.cwd(), 'resources/css/app.css'),
    'utf8',
);

function tokens(selector: ':root' | '.dark'): Record<string, string> {
    const start = css.indexOf(`\n${selector} {`);
    const block = css.slice(start, css.indexOf('\n}', start));

    return Object.fromEntries(
        [...block.matchAll(/--([\w-]+):\s*(oklch\([^)]+\));/g)].map((m) => [
            m[1],
            m[2],
        ]),
    );
}

function luminance(oklch: string): number {
    const [l, c, h = 0] = oklch
        .replace(/oklch\(|\)/g, '')
        .trim()
        .split(/\s+/)
        .map(Number);
    const a = c * Math.cos((h * Math.PI) / 180);
    const b = c * Math.sin((h * Math.PI) / 180);

    const lms = [
        (l + 0.3963377774 * a + 0.2158037573 * b) ** 3,
        (l - 0.1055613458 * a - 0.0638541728 * b) ** 3,
        (l - 0.0894841775 * a - 1.291485548 * b) ** 3,
    ];
    const clamp = (v: number) => Math.min(1, Math.max(0, v));
    const r = clamp(
        4.0767416621 * lms[0] - 3.3077115913 * lms[1] + 0.2309699292 * lms[2],
    );
    const g = clamp(
        -1.2684380046 * lms[0] + 2.6097574011 * lms[1] - 0.3413193965 * lms[2],
    );
    const bl = clamp(
        -0.0041960863 * lms[0] - 0.7034186147 * lms[1] + 1.707614701 * lms[2],
    );

    // Linear sRGB already: WCAG relative luminance applies directly.
    return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
}

function contrast(a: string, b: string): number {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

const states = ['locked', 'available', 'in-progress', 'completed', 'mastered'];

describe.each([':root', '.dark'] as const)(
    'design tokens in %s',
    (selector) => {
        const t = tokens(selector);

        it('defines every state token', () => {
            for (const state of states) {
                expect(t[`state-${state}`], `state-${state}`).toBeDefined();
                expect(
                    t[`state-${state}-soft`],
                    `state-${state}-soft`,
                ).toBeDefined();
            }
        });

        it.each(states)(
            '%s text is readable on its badge and on the page (4.5:1)',
            (state) => {
                expect(
                    contrast(t[`state-${state}`], t[`state-${state}-soft`]),
                ).toBeGreaterThanOrEqual(4.5);
                expect(
                    contrast(t[`state-${state}`], t.background),
                ).toBeGreaterThanOrEqual(4.5);
            },
        );

        it.each(states)(
            '%s progress fill is visible on the track (3:1)',
            (state) => {
                expect(
                    contrast(t[`state-${state}`], t.muted),
                ).toBeGreaterThanOrEqual(3);
            },
        );

        it('keeps body and secondary text readable', () => {
            expect(contrast(t.foreground, t.background)).toBeGreaterThanOrEqual(
                7,
            );
            expect(
                contrast(t['muted-foreground'], t.background),
            ).toBeGreaterThanOrEqual(4.5);
            expect(
                contrast(t['muted-foreground'], t.muted),
            ).toBeGreaterThanOrEqual(4.5);
        });
    },
);
