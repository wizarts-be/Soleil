// Bouwt wordpress/soleil/assets/js/main.js uit dezelfde bronnen als de Astro-site:
// src/scripts/main.ts en het <script> in src/components/Newsletter.astro.
// Gebruik (vanuit de repo-root): node wordpress/bin/build-js.mjs
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';

const newsletter = readFileSync('src/components/Newsletter.astro', 'utf8').match(/<script>\n([\s\S]*?)<\/script>/)[1];

await build({
  stdin: {
    contents: `import './src/scripts/main.ts';\n{\n${newsletter}\n}\n`,
    resolveDir: process.cwd(),
    sourcefile: 'main-and-newsletter.ts',
    loader: 'ts',
  },
  bundle: true,
  format: 'iife',
  target: 'es2020',
  banner: { js: '/* Instituut Soleil. Gebouwd met wordpress/bin/build-js.mjs uit src/scripts/main.ts en src/components/Newsletter.astro. Niet met de hand aanpassen. */' },
  outfile: 'wordpress/soleil/assets/js/main.js',
});
console.log('main.js gebouwd');
