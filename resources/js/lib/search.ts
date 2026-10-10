/**
 * Lower-cases and unifies Arabic letters, half-spaces and the word "بانک", so a search for
 * "بانك ملي" finds "بانک ملی". (Listed in scripts/i18n.mjs: its Persian is data, not UI text.)
 */
export function normalizeSearch(text: string): string {
    return text
        .toLowerCase()
        .replace(/ك/g, 'ک')
        .replace(/[يى]/g, 'ی')
        .replace(/‌/g, ' ')
        .replace(/(^|\s)بانک(\s|$)/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}
