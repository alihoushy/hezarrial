import { usePage } from '@inertiajs/react';
import { useEffect, useSyncExternalStore } from 'react';

type Replacements = Record<string, string | number>;
type Dictionary = Record<string, string>;

/**
 * Translations are resources/lang/{code}.json, shared with Laravel and keyed by
 * the Persian source text. Persian is the source language, so it has no file.
 */
const files = import.meta.glob<Dictionary>('../../lang/*.json', { eager: true, import: 'default' });

const dictionaries: Record<string, Dictionary> = {};

for (const [path, dictionary] of Object.entries(files)) {
    const code = path.match(/([^/]+)\.json$/)?.[1];

    if (code) {
        dictionaries[code] = dictionary;
    }
}

let current = 'fa';

export function getLocale(): string {
    return current;
}

/** Translate a Persian source string. `:name` placeholders are filled from `replacements`. */
export function t(key: string, replacements?: Replacements): string {
    const text = dictionaries[current]?.[key] || key;

    if (!replacements) {
        return text;
    }

    return text.replace(/:(\w+)/g, (match, name: string) => (name in replacements ? String(replacements[name]) : match));
}

/**
 * Marks a string for translation without translating it yet (like gettext's
 * noop): use it for module-level constants, then call t() where it is shown.
 */
export const tr = <T extends string>(key: T): T => key;

/**
 * Applies the page's locale. `t()` reads a module-level value, so it is set
 * while the layout renders, before the page below it renders, and the
 * document's lang and dir follow it.
 */
export function useLocale() {
    const locale = usePage().props.locale ?? { code: 'fa', dir: 'rtl' as const };

    current = locale.code;

    useEffect(() => {
        document.documentElement.lang = locale.code;
        document.documentElement.dir = locale.dir;
    }, [locale.code, locale.dir]);

    return locale;
}

/** The document's text direction; usable outside the Inertia tree (toaster, Radix). */
export function useDocumentDirection(): 'rtl' | 'ltr' {
    return useSyncExternalStore(
        (onChange) => {
            const observer = new MutationObserver(onChange);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['dir'] });

            return () => observer.disconnect();
        },
        () => (document.documentElement.dir === 'ltr' ? 'ltr' : 'rtl'),
        () => 'rtl',
    );
}
