// Copies the bank logos from @persianlabs/icons (MIT) into public/images/banks, optimised.
//
//   npm install --no-save @persianlabs/icons svgo
//   node scripts/bank-logos.mjs
//
// The logos are served as plain <img> files instead of bundled React components, so a
// user only downloads the logo of their own bank. Output is committed (the shared-hosting
// zip is built from git), so this only needs re-running when the library changes.
import { mkdirSync, readdirSync, readFileSync, writeFileSync, copyFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { optimize } from 'svgo';

const source = 'node_modules/@persianlabs/icons';
const target = 'public/images/banks';

rmSync(target, { recursive: true, force: true });
mkdirSync(target, { recursive: true });

let before = 0;
let after = 0;
const large = [];

for (const file of readdirSync(join(source, 'assets/banks/color')).sort()) {
    const svg = readFileSync(join(source, 'assets/banks/color', file), 'utf8');
    const { data } = optimize(svg, {
        multipass: true,
        floatPrecision: 2,
        plugins: ['preset-default'],
    });

    writeFileSync(join(target, file), data);
    before += Buffer.byteLength(svg);
    after += Buffer.byteLength(data);

    if (Buffer.byteLength(data) > 30_000) large.push(`${file} ${(Buffer.byteLength(data) / 1000).toFixed(0)}KB`);
}

copyFileSync(join(source, 'LICENSE'), join(target, 'LICENSE'));
writeFileSync(
    join(target, 'README.md'),
    'Bank logos from [@persianlabs/icons](https://github.com/persianlabs/icons) (MIT), optimised with svgo by `scripts/bank-logos.mjs`.\n' +
        'Source credit: [zegond/logos-per-banks](https://github.com/zegond/logos-per-banks). Design credit: the "400 Persian Brands" Figma community file.\n' +
        'The logos remain the trademarks of their banks.\n',
);

console.log(`${readdirSync(target).filter((f) => f.endsWith('.svg')).length} logos, ${(before / 1000).toFixed(0)}KB -> ${(after / 1000).toFixed(0)}KB`);
if (large.length) console.log('Still large:', large.join(', '));
