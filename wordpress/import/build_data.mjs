// Zet de inhoud van de statische site om naar content/data.json voor import.php.
// Teksten worden letterlijk overgenomen uit src/data/site.ts en src/data/journal.json.
// Gebruik (vanuit de repo-root): node wordpress/import/build_data.mjs
import { writeFileSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as site from '../../src/data/site.ts';

const here = dirname(fileURLToPath(import.meta.url));
const journal = JSON.parse(readFileSync(new URL('../../src/data/journal.json', import.meta.url), 'utf8'));
const img = (name) => (name ? `public/img/${name}.webp` : '');

/* "Donderdag 29 oktober 2026" → "2026-10-29" */
const months = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
const isoDate = (s) => {
  const [, d, m, y] = s.toLowerCase().split(' ');
  const mi = months.indexOf(m);
  return mi < 0 ? null : `${y}-${String(mi + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
};

const rows = (list) => list.map(([naam, duur, prijs, toelichting]) => ({ naam, duur, prijs: String(prijs), ...(toelichting ? { toelichting } : {}) }));
const groups = (list = []) => list.map((g) => ({ titel: g.title ?? '', rijen: rows(g.rows), ...(g.note ? { noot: g.note } : {}) }));

const LASER_ACTIVITY = 'laser-ontharing';

const treatments = site.treatments.map((t, i) => ({
  slug: t.slug,
  name: t.name,
  group: t.group,
  order: i,
  img: img(t.img),
  meta: {
    foto_alt: t.alt,
    sub: t.sub,
    kicker: t.kicker ?? '',
    kop: t.title ?? '',
    intro: t.intro,
    vanaf: t.from,
    tip: t.tip ?? '',
    noot: t.note ?? '',
    link_url: t.link?.href ?? '',
    link_label: t.link?.label ?? '',
    // Make-up: de prijzen komen in WordPress uit de diensten (zoals in site.ts), dus geen aparte tabel.
    prijzen: t.page ? null : groups(t.prices),
    waarom: t.why ? { ...(t.why.titles ? { titels: t.why.titles } : {}), probleem: t.why.problem, oplossing: t.why.solution, resultaat: t.why.benefit } : null,
    // De laserdata staan één keer, bij de activiteit; het extra vak op de laserpagina leest ze daar.
    extra: t.extra ? { titel: t.extra.title, tekst: t.extra.text ?? '', lijst: t.extra.list === site.laser.dates ? [] : t.extra.list } : null,
    delen: t.parts ? t.parts.map((p) => ({ id: p.id, naam: p.name, foto: img(p.img), alt: p.alt, tekst: p.text, prijzen: groups(p.prices) })) : null,
    makeup: t.page
      ? {
          titel: t.page.title,
          vragen: t.page.questions,
          cta: t.page.cta,
          verhaal: { label: t.page.story.label, titel: t.page.story.title, tekst: t.page.story.text, foto: img(t.page.story.img), alt: t.page.story.alt },
          diensten: t.page.services.map((s) => ({ naam: s.name, ...(s.tag ? { tag: s.tag } : {}), tekst: s.text, duur: s.dur, prijs: String(s.price) })),
        }
      : null,
  },
  extra_activiteit: t.extra && t.extra.list === site.laser.dates ? LASER_ACTIVITY : '',
}));

// Activiteiten: de laser krijgt álle data (voorbije vallen bij weergave weg), "Datum volgt" wordt de tekst zonder data.
const activities = site.events.map((e, i) => {
  const all = e.name === site.laser.name ? site.laser.dates : e.dates;
  const data = all.map(isoDate).filter(Boolean);
  return {
    slug: e.name === site.laser.name ? LASER_ACTIVITY : e.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
    name: e.name,
    order: i,
    img: img(e.img),
    meta: { tekst: e.text, data, geen_data: all.filter((d) => !isoDate(d)).join(', '), link_url: e.href, link_label: e.label, voorbeeld: !!e.placeholder },
  };
});

const data = {
  settings: {
    name: site.info.name,
    owner: site.info.owner,
    street: site.info.street,
    city: site.info.city,
    phone: site.info.phone,
    phone_href: site.info.phoneHref,
    email: site.info.email,
    booking: site.info.booking,
    webshop: site.info.webshop,
    route: site.info.route,
    instagram: site.info.instagram,
    facebook: site.info.facebook,
    vat: site.info.vat,
    // Uit Footer.astro en Newsletter.astro.
    privacy: 'https://instituutsoleil.be/privacy-policy/',
    footer_slogan: 'Gun je huid de aandacht die ze verdient.',
    footer_note: 'Tijdens behandelingen wordt de telefoon niet beantwoord, ik contacteer je zo vlug mogelijk.',
    hours_note: site.hoursNote,
    hours: Object.fromEntries(site.hours.map((h) => [h.d, { open: h.open ?? '', close: h.close ?? '', note: h.note ?? '' }])),
    spot_season: site.spotlight.season,
    spot_name: site.spotlight.name,
    spot_text: site.spotlight.text,
    spot_image: img(site.spotlight.img),
    spot_alt: site.spotlight.alt,
    spot_dur: site.spotlight.dur,
    spot_price: String(site.spotlight.price),
    spot_href: site.spotlight.href,
  },
  groups: site.groups.map((g) => g.title),
  treatments,
  activities,
  products: site.newProducts.map((p, i) => ({
    slug: p.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
    name: p.name,
    order: i,
    img: img(p.img),
    meta: { merk: p.brand, tekst: p.text, link_url: p.href === site.info.webshop ? '' : p.href, voorbeeld: !!p.placeholder },
  })),
  brands: site.brands.map((b, i) => ({ slug: b.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'), name: b.name, order: i, img: img(b.img), meta: { regel: b.line } })),
  reviews: site.reviews.map((r, i) => ({ slug: `review-${i + 1}`, name: r.name, order: i, meta: { tekst: r.text, plaats: r.place, voorbeeld: !!r.placeholder } })),
  footer: site.footerTreatments.map((f) => ({ label: f.label, slug: f.href.split('/').filter(Boolean).pop() })),
  journal: journal.map((p) => ({ slug: p.slug, title: p.title, cat: p.cat, date: p.date, img: img(p.img), excerpt: p.excerpt, blocks: p.blocks })),
};

mkdirSync(`${here}/content`, { recursive: true });
writeFileSync(`${here}/content/data.json`, JSON.stringify(data, null, 1) + '\n');
console.log(`data.json: ${treatments.length} behandelingen, ${activities.length} activiteiten, ${data.products.length} producten, ${data.brands.length} merken, ${data.reviews.length} reviews, ${journal.length} artikels`);
