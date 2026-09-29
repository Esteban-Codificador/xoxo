/**
 * A slug suggestion from a title: "Git: commits y staging" →
 * "git-commits-y-staging". Only a starting point the author can change;
 * the server validates the final slug.
 */
export function slugify(text: string, maxLength = 120): string {
    return text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .slice(0, maxLength)
        .replace(/^-+|-+$/g, '');
}
