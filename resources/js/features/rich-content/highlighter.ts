import type { HighlighterCore, ThemedToken } from 'shiki/core';

/**
 * Shiki with the JavaScript regex engine (no WASM), two themes and each
 * grammar loaded on first use: a lesson only downloads the languages it
 * shows. Unknown languages render as plain text.
 */
const grammars = {
    bash: () => import('shiki/langs/shellscript.mjs'),
    c: () => import('shiki/langs/c.mjs'),
    cpp: () => import('shiki/langs/cpp.mjs'),
    css: () => import('shiki/langs/css.mjs'),
    diff: () => import('shiki/langs/diff.mjs'),
    dockerfile: () => import('shiki/langs/dockerfile.mjs'),
    go: () => import('shiki/langs/go.mjs'),
    html: () => import('shiki/langs/html.mjs'),
    ini: () => import('shiki/langs/ini.mjs'),
    java: () => import('shiki/langs/java.mjs'),
    javascript: () => import('shiki/langs/javascript.mjs'),
    json: () => import('shiki/langs/json.mjs'),
    jsx: () => import('shiki/langs/jsx.mjs'),
    markdown: () => import('shiki/langs/markdown.mjs'),
    php: () => import('shiki/langs/php.mjs'),
    python: () => import('shiki/langs/python.mjs'),
    rust: () => import('shiki/langs/rust.mjs'),
    sql: () => import('shiki/langs/sql.mjs'),
    toml: () => import('shiki/langs/toml.mjs'),
    tsx: () => import('shiki/langs/tsx.mjs'),
    typescript: () => import('shiki/langs/typescript.mjs'),
    xml: () => import('shiki/langs/xml.mjs'),
    yaml: () => import('shiki/langs/yaml.mjs'),
} as const;

export type SupportedLanguage = keyof typeof grammars;

const aliases: Record<string, SupportedLanguage> = {
    'c++': 'cpp',
    console: 'bash',
    docker: 'dockerfile',
    js: 'javascript',
    md: 'markdown',
    py: 'python',
    rs: 'rust',
    sh: 'bash',
    shell: 'bash',
    shellscript: 'bash',
    ts: 'typescript',
    yml: 'yaml',
    zsh: 'bash',
};

export function resolveLanguage(language: unknown): SupportedLanguage | null {
    if (typeof language !== 'string') {
        return null;
    }

    const key = language.toLowerCase();

    if (key in grammars) {
        return key as SupportedLanguage;
    }

    return aliases[key] ?? null;
}

let highlighter: Promise<HighlighterCore> | null = null;

function getHighlighter(): Promise<HighlighterCore> {
    highlighter ??= Promise.all([
        import('shiki/core'),
        import('shiki/engine/javascript'),
    ]).then(([{ createHighlighterCore }, { createJavaScriptRegexEngine }]) =>
        createHighlighterCore({
            themes: [
                import('shiki/themes/github-light.mjs'),
                import('shiki/themes/github-dark.mjs'),
            ],
            langs: [],
            engine: createJavaScriptRegexEngine(),
        }),
    );

    return highlighter;
}

/**
 * Tokens with both themes: `color` is the light one and `--shiki-dark` the
 * dark one (see `.shiki-tokens` in app.css).
 */
export async function highlight(
    code: string,
    language: SupportedLanguage,
): Promise<ThemedToken[][]> {
    const instance = await getHighlighter();
    const shikiName = language === 'bash' ? 'shellscript' : language;

    if (!instance.getLoadedLanguages().includes(shikiName)) {
        await instance.loadLanguage(grammars[language]());
    }

    return instance.codeToTokens(code, {
        lang: shikiName,
        themes: { light: 'github-light', dark: 'github-dark' },
    }).tokens;
}
