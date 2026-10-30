// Vergelijkt de HTML van de WordPress-site met de Astro-build (dist/), pagina per pagina.
// Draai dit na elke WordPress-update: core voegt soms nieuwe klassen of attributen toe aan blokken
// (zoals "wp-block-paragraph" sinds 7.0), en dan wijkt de markup af van de huidige site.
//
// Gebruik (vanuit de repo-root, Local-site gestart):
//   node wordpress/bin/check-html.mjs                 artikel + behandelingpagina
//   node wordpress/bin/check-html.mjs --all           alle pagina's van de site
//   node wordpress/bin/check-html.mjs /tarieven/ …    eigen lijst
//   --build      eerst de Astro-site opnieuw bouwen (npm run build)
//   --chrome     ook header en footer vergelijken, niet enkel <main>
//   WP_URL=http://soleil.local (standaard)
//
// Wat telt als gelijk: dezelfde tags, klassen, attributen en tekst. Genegeerd: het domein, het pad en de
// grootte-suffix van foto's (/img/x.webp ↔ /wp-content/uploads/…/x.webp), de volgorde van attributen,
// HTML-entiteiten (&amp; ↔ &), witruimte tussen tags en de inhoud van iconen (<svg>).
// Ook genegeerd: width/height op foto's. WordPress schrijft de echte maat van het bestand, Astro had ze met de hand
// getypt (de homefoto is 1600×1111, Astro zegt 1135). De CSS bepaalt de grootte, dus visueel verandert er niets.
// Bewust anders: lijstitems met een voorbije datum ("Donderdag 29 oktober 2026"). WordPress laat die weg,
// de Astro-build toont ze tot de volgende build (bv. op de laserpagina). Ze tellen aan beide kanten niet mee.
import { readFileSync, existsSync, writeFileSync, mkdtempSync } from 'node:fs';
import { execSync, spawnSync } from 'node:child_process';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const WP = (process.env.WP_URL || 'http://soleil.local').replace(/\/$/, '');
const args = process.argv.slice(2);
const flag = (f) => args.includes(f);
const DEFAULT = ['/journaal/peelen-na-de-zomer/', '/behandelingen/skin-expert/'];
const ALL = [
  '/', '/behandelingen/', '/tarieven/', '/nieuw/', '/over-soleil/', '/contact/', '/journaal/',
  '/behandelingen/skin-discovery/', '/behandelingen/huidanalyse/', '/behandelingen/skin-relax/', '/behandelingen/skin-expert/',
  '/behandelingen/arrangementen/', '/behandelingen/lichaam/', '/behandelingen/handen-voeten/', '/behandelingen/epilaties/',
  '/behandelingen/make-up/', '/behandelingen/laser/',
  '/journaal/nieuwe-openingsuren/', '/journaal/peelen-na-de-zomer/', '/journaal/huid-verzorgen-na-de-zomer/', '/journaal/huidanalyse-toestel/',
  '/bestaat-niet/',
];
// Een pagina die niet bestaat, vergelijken we met de 404-pagina van de build.
const distFile = (path) => (path === '/bestaat-niet/' ? 'dist/404.html' : `dist${path}index.html`);
const pages = flag('--all') ? ALL : args.filter((a) => a.startsWith('/')).length ? args.filter((a) => a.startsWith('/')) : DEFAULT;

if (flag('--build') || !existsSync('dist/index.html')) {
  console.log('Astro-site bouwen…');
  execSync('npm run build', { stdio: 'ignore' });
}

const entities = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“', ndash: '–', mdash: '—', hellip: '…', euro: '€' };
const decode = (s) => s.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (m, e) =>
  e[0] === '#' ? String.fromCodePoint(e[1] === 'x' || e[1] === 'X' ? parseInt(e.slice(2), 16) : +e.slice(1)) : entities[e.toLowerCase()] ?? m);

const photo = (src) => {
  const file = src.split('/').pop().replace(/\?.*$/, '');
  return '[foto ' + file.replace(/-\d+x\d+(?=\.\w+$)/, '').replace(/-\d+(?=\.\w+$)/, '') + ']';
};

function normalise(html, chrome) {
  const body = html.slice(html.indexOf('<body'));
  let part = body;
  if (!chrome) {
    const start = body.search(/<main\b/);
    const end = body.indexOf('</main>');
    part = start >= 0 && end > start ? body.slice(start, end + 7) : '';
  } else {
    // WordPress zet altijd klassen op <body> en alles in <div class="wp-site-blocks">: geen invloed op de opmaak.
    part = body.replace(/^<body[^>]*>/, '<body>').replace(/<script[\s\S]*?<\/script>/g, '');
    if (part.includes('<div class="wp-site-blocks">')) {
      part = part.replace('<div class="wp-site-blocks">', '');
      const end = part.lastIndexOf('</div>', part.indexOf('</body>'));
      part = part.slice(0, end) + part.slice(end + 6);
    }
  }
  part = part
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<(svg)\b([^>]*)>[\s\S]*?<\/svg>/g, (m, t, a) => `<svg${a}></svg>`)
    .replace(/<(script|style)\b[\s\S]*?<\/\1>/g, '');

  const out = [];
  const re = /<\/?([a-zA-Z][\w-]*)([^>]*?)\/?>|([^<]+)/g;
  let m;
  while ((m = re.exec(part))) {
    if (m[3] !== undefined) {
      const text = decode(m[3]).replace(/\s+/g, ' ').trim();
      if (text) out.push(text);
      continue;
    }
    const tag = m[1].toLowerCase();
    if (m[0].startsWith('</')) { out.push(`</${tag}>`); continue; }
    const attrs = [];
    const ar = /([^\s=]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g;
    let a;
    while ((a = ar.exec(m[2]))) {
      const name = a[1].toLowerCase();
      if (name === '/' || name.startsWith('data-astro-cid')) continue;
      let value = decode(a[2] ?? a[3] ?? a[4] ?? '');
      if (name === 'href' || name === 'src' || name === 'action') value = value.replace(WP, '').replace('http://localhost:4321', '');
      if (tag === 'img' && name === 'src') value = photo(value);
      if (tag === 'img' && (name === 'width' || name === 'height')) continue;
      if (name === 'class') value = value.split(/\s+/).filter(Boolean).join(' ');
      attrs.push(value === '' && !['alt', 'class'].includes(name) ? name : `${name}="${value}"`);
    }
    attrs.sort();
    out.push(`<${tag}${attrs.length ? ' ' + attrs.join(' ') : ''}>`);
  }
  return dropPastDates(out).join('\n') + '\n';
}

const MONTHS = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
const today = new Date(new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Brussels' }).format(new Date()));
function isPast(text) {
  const m = text.match(/^\p{L}+dag (\d{1,2}) (\p{L}+) (\d{4})$/u);
  if (!m || MONTHS.indexOf(m[2].toLowerCase()) < 0) return false;
  return new Date(Date.UTC(+m[3], MONTHS.indexOf(m[2].toLowerCase()), +m[1])) < today;
}
function dropPastDates(tokens) {
  const out = [];
  for (let i = 0; i < tokens.length; i++) {
    if (tokens[i] === '<li>' && tokens[i + 2] === '</li>' && isPast(tokens[i + 1])) { i += 2; continue; }
    out.push(tokens[i]);
  }
  return out;
}

const dir = mkdtempSync(join(tmpdir(), 'soleil-check-'));
let failed = 0;
for (const path of pages) {
  const file = distFile(path);
  if (!existsSync(file)) { console.log(`?  ${path}: niet in de Astro-build`); failed++; continue; }
  let wp;
  try {
    const res = await fetch(WP + path);
    if (!res.ok && !(path === '/bestaat-niet/' && res.status === 404)) throw new Error(`HTTP ${res.status}`);
    wp = await res.text();
  } catch (e) {
    console.log(`✗  ${path}: WordPress niet bereikbaar (${e.message}). Draait de Local-site?`);
    failed++;
    continue;
  }
  const a = normalise(readFileSync(file, 'utf8'), flag('--chrome'));
  const w = normalise(wp, flag('--chrome'));
  if (a === w) { console.log(`✓  ${path}`); continue; }
  failed++;
  const fa = join(dir, 'astro.txt'), fw = join(dir, 'wordpress.txt');
  writeFileSync(fa, a); writeFileSync(fw, w);
  const diff = spawnSync('diff', ['-U2', '--label', 'astro', '--label', 'wordpress', fa, fw], { encoding: 'utf8' }).stdout;
  const lines = diff.split('\n');
  console.log(`✗  ${path}: ${lines.filter((l) => /^[-+][^-+]/.test(l)).length} regels verschillen`);
  console.log(lines.slice(2, 60).map((l) => '   ' + l).join('\n') + (lines.length > 60 ? '\n   …' : ''));
}
console.log(failed ? `\n${failed} van ${pages.length} pagina's wijken af.` : `\nAlle ${pages.length} pagina's gelijk.`);
process.exit(failed ? 1 : 0);
