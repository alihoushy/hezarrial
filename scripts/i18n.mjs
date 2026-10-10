#!/usr/bin/env node
/**
 * Checks the translation files against the code.
 *
 *   node scripts/i18n.mjs          report missing / unused / mismatched translations (exit 1 on any)
 *   node scripts/i18n.mjs --list   print every translatable key found in the code, one JSON line each
 *
 * Translatable text is any Persian string literal in resources/js (t('…'), tr('…'))
 * or passed to __('…') / @lang('…') in app/ and resources/views/. Translations live in resources/lang/{code}.json,
 * keyed by that Persian text. To add a language, copy en.json to {code}.json,
 * translate the values, and add the language to config('app.supported_locales').
 */
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const PERSIAN_LETTER = /[\u0621-\u063A\u0641-\u064A\u0679-\u06D3]/;
// Persian symbols that are shown as-is in every language, and files whose Persian is data, not UI text.
const IGNORED_KEYS = new Set(['؟']);
const IGNORED_FILES = new Set(['resources/js/lib/jalali.ts', 'resources/js/lib/search.ts']);

function walk(dir, extensions) {
    return readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);

        if (statSync(path).isDirectory()) {
            // Composer's vendor/ is skipped; resources/views/vendor holds our own published templates.
            return name === 'node_modules' || (name === 'vendor' && !path.includes('resources/views')) ? [] : walk(path, extensions);
        }

        return extensions.some((extension) => name.endsWith(extension)) ? [path] : [];
    });
}

const unescape = (text) => text.replace(/\\(['"\\])/g, '$1');

function usedKeys() {
    const keys = new Map(); // key -> first "file:line"

    const add = (key, file, source, index) => {
        if (IGNORED_KEYS.has(key) || !PERSIAN_LETTER.test(key) || keys.has(key)) {
            return;
        }

        keys.set(key, `${file}:${source.slice(0, index).split('\n').length}`);
    };

    for (const path of walk(join(root, 'resources/js'), ['.ts', '.tsx'])) {
        const file = relative(root, path);

        if (IGNORED_FILES.has(file) || path.endsWith('.d.ts')) {
            continue;
        }

        const source = readFileSync(path, 'utf8');

        for (const match of source.matchAll(/(['"])((?:(?!\1)[^\\\n]|\\.)*)\1/g)) {
            add(unescape(match[2]), file, source, match.index);
        }
    }

    // PHP (__('…')) and Blade views (__('…'), @lang('…')).
    const phpFiles = [...walk(join(root, 'app'), ['.php']), ...walk(join(root, 'resources/views'), ['.blade.php'])];

    for (const path of phpFiles) {
        const file = relative(root, path);
        const source = readFileSync(path, 'utf8');

        for (const match of source.matchAll(/(?:__|@lang)\(\s*'((?:[^'\\\n]|\\.)*)'/g)) {
            add(unescape(match[1]), file, source, match.index);
        }
    }

    return keys;
}

const placeholders = (text) => [...text.matchAll(/:(\w+)/g)].map((match) => match[1]).sort().join(',');

const keys = usedKeys();

if (process.argv.includes('--list')) {
    for (const [key, where] of keys) {
        console.log(JSON.stringify({ key, where }));
    }

    process.exit(0);
}

const langDir = join(root, 'resources/lang');
// fa.json is not a translation of the Persian text: it holds Persian for the English messages of
// third-party packages (Fortify, ...), so it is not checked against the keys found in the code.
const locales = readdirSync(langDir).filter((name) => name.endsWith('.json') && name !== 'fa.json').map((name) => name.replace(/\.json$/, ''));
let problems = 0;

for (const code of locales) {
    const dictionary = JSON.parse(readFileSync(join(langDir, `${code}.json`), 'utf8'));
    const missing = [...keys].filter(([key]) => !dictionary[key]);
    const unused = Object.keys(dictionary).filter((key) => !keys.has(key));
    const mismatched = Object.entries(dictionary).filter(([key, value]) => keys.has(key) && placeholders(key) !== placeholders(value));

    console.log(`${code}.json: ${keys.size - missing.length}/${keys.size} translated`);

    for (const [key, where] of missing) console.log(`  missing    ${where}  ${key}`);
    for (const key of unused) console.log(`  unused     ${key}`);
    for (const [key, value] of mismatched) console.log(`  placeholders differ  ${key}  =>  ${value}`);

    problems += missing.length + unused.length + mismatched.length;
}

if (!existsSync(langDir) || locales.length === 0) {
    console.log('No translation files found.');
}

process.exit(problems > 0 ? 1 : 0);
