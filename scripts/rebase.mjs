// Makes a built site work under a sub-path (GitHub Pages preview), without touching the source.
// Usage: node scripts/rebase.mjs dist /Soleil
// Prefixes root-relative links in HTML, CSS and JS and marks every page noindex,
// so the preview never competes with the real site in search results.
import { readdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const [dir, raw] = process.argv.slice(2);
const base = '/' + raw.replace(/^\/|\/$/g, '');

async function* files(d) {
  for (const e of await readdir(d, { withFileTypes: true })) {
    const p = path.join(d, e.name);
    if (e.isDirectory()) yield* files(p);
    else yield p;
  }
}

let n = 0;
for await (const f of files(dir)) {
  const ext = path.extname(f);
  if (!['.html', '.css', '.js'].includes(ext)) continue;
  let s = await readFile(f, 'utf8');
  const before = s;
  // href="/x", src="/x", action="/x" (not protocol-relative "//")
  s = s.replace(/(\s(?:href|src|action|poster)=["'])\/(?!\/)/g, `$1${base}/`);
  // url(/x) in CSS and inline styles
  s = s.replace(/url\((["']?)\/(?!\/)/g, `url($1${base}/`);
  // bundled asset paths inside scripts
  if (ext === '.js') s = s.replace(/(["'`])\/_astro\//g, `$1${base}/_astro/`);
  if (ext === '.html') s = s.replace('<head>', '<head><meta name="robots" content="noindex">');
  if (s !== before) { await writeFile(f, s); n++; }
}
console.log(`rebased ${n} files to ${base}/`);
