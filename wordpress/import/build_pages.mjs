// Schrijft de blokinhoud van de vaste pagina's naar content/pages/*.html.
// Opbouw en teksten letterlijk uit src/pages/*.astro; gegevens (naam, usp's, …) uit src/data/site.ts.
// Links naar de online agenda, webshop, route en Instagram staan als /afspraak/, /webshop/, /route/, /instagram/:
// de site zet ze om naar de link uit de instellingen.
// Gebruik (vanuit de repo-root): node wordpress/import/build_pages.mjs
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { info, usps } from '../../src/data/site.ts';
import { el, p, h, crumbs, buttons, button, foto, icoon, dyn, media, page } from './blocks.mjs';

const out = `${dirname(fileURLToPath(import.meta.url))}/content/pages`;
mkdirSync(out, { recursive: true });
const img = (name) => `public/img/${name}.webp`;
const esc = (s) => s.replace(/&/g, '&amp;');
const BOOK = '/afspraak/', SHOP = '/webshop/', ROUTE = '/route/', INSTA = '/instagram/';

const pages = {};

/* ---------------------------------------------------------------- Home (index.astro) */
pages.home = page(
  el('section', 'hero', [
    el('div', 'wrap hero-in', [
      el('div', 'hero-copy', [
        p('Instituut Soleil · Beervelde', 'label rv'),
        h(1, 'Stralen begint met een huid in balans.', 'display rv', { id: 'h1' }),
        p('Bij Instituut Soleil geef ik jou en je huid de aandacht die jullie verdienen. In een warme, rustgevende sfeer combineer ik huidverbetering met ontspanning en een moment helemaal voor jezelf.', 'rv', { style: '--d:1' }),
        buttons([
          button('Afspraak maken', BOOK, { external: true }),
          button('Nieuw? Begin met een Skin Discovery', '/behandelingen/skin-discovery/', { style: 'link' }),
        ], 'actions rv', { style: '--d:2' }),
      ]),
      foto(img('zaakvoerder-behandeling'), 'Saskia geeft een behandeling in haar instituut', 'hero-media rv-img', { loading: 'high', maten: true }),
    ]),
  ], { 'aria-labelledby': 'h1' }),

  el('section', '', [
    el('div', 'wrap', [
      el('ul', 'usp', usps.map((u, i) => el('li', 'rv', [icoon(u.icon), h(3, esc(u.title)), p(esc(u.text))], { style: `--d:${i}` })), { role: 'list' }),
    ]),
  ], { 'aria-label': 'Waarom Soleil' }),

  el('section', 'section', [
    el('div', 'wrap', [
      el('div', 'sec-head', [
        h(2, 'Behandelingen', 'h1 rv', { id: 'beh' }),
        buttons([button('Alle behandelingen &amp; prijzen', '/tarieven/', { style: 'link', className: 'rv' })], '', { tag: 'none' }),
      ]),
      el('a', 'disc rv', [
        el('div', '', [
          p('Nieuw bij Soleil?', 'label label--sunset'),
          h(3, 'Begin met een Skin Discovery'),
          p('Een huidanalyse, een verzorging op maat en persoonlijk advies voor thuis. Ideaal als eerste behandeling.', 't'),
        ]),
        el('div', 'disc-side', [p('Ontdek de Skin Discovery', 'btn btn--light', { tag: 'span', icoon: 'arrow-right' })]),
      ], { href: '/behandelingen/skin-discovery/' }),
      dyn('home-kaarten', {
        items: [
          { behandeling: 'huidanalyse' },
          { behandeling: 'skin-relax' },
          { behandeling: 'skin-expert' },
          { naam: 'Ontspanning', sub: 'arrangementen, lichaam, handen & voeten', foto: media(img('massage-lichaam')), href: '/behandelingen/#ontspanning' },
        ],
      }),
    ]),
  ], { 'aria-labelledby': 'beh' }),

  el('section', 'section', [
    el('div', 'wrap', [
      dyn('in-de-kijker'),
      buttons([button('Alles wat nieuw is bij Soleil', '/nieuw/', { style: 'link' })], 'spot-more rv', { tag: 'p' }),
    ]),
  ], { style: 'padding-top:0', 'aria-labelledby': 'kijker' }),

  el('section', 'section', [
    el('div', 'wrap welcome', [
      foto(img('zaakvoerder-portret'), `${info.owner}, zaakvoerder van Instituut Soleil`, 'framed framed--left rv-img', { maten: true }),
      el('div', 'rv', [
        p('Welkom bij Soleil', 'label'),
        h(2, `Ik ben ${info.owner}`, 'h1', { id: 'welkom' }),
        p('Met persoonlijke behandelingen, kwalitatieve producten en eerlijk advies stem ik elke verzorging af op wat jouw huid nodig heeft. Samen werken we stap voor stap aan een gezonde, stralende huid, met een verzorgingsroutine die ook thuis bij je past.', 'muted'),
        p('Ik verwelkom je graag voor een moment van rust, persoonlijke aandacht en een natuurlijke glow.', 'muted'),
        buttons([button('Lees mijn verhaal', '/over-soleil/', { style: 'link' })], '', { tag: 'p' }),
      ]),
    ]),
  ], { style: 'padding-bottom:0', 'aria-labelledby': 'welkom' }),

  el('section', 'section', [
    el('div', 'wrap', [
      el('div', 'sec-head', [
        el('div', '', [h(2, 'Wat klanten zeggen', 'h1 rv', { id: 'reviews' }), dyn('voorbeeld-noot', { soort: 'reviews' })]),
      ]),
      dyn('reviews'),
    ]),
  ], { 'aria-labelledby': 'reviews' }),

  el('section', '', [
    el('div', 'wrap', [
      el('div', 'sec-head', [
        el('div', '', [
          h(2, 'Merken waar ik op vertrouw', 'h1 rv', { id: 'merken' }),
          p('Merken met veel actieve bestanddelen, jarenlang onderzoek en klinische testen.', 'muted rv'),
        ]),
        buttons([button('Naar de webshop', SHOP, { style: 'link', external: true, className: 'rv' })], '', { tag: 'none' }),
      ]),
      dyn('merken'),
    ]),
  ], { 'aria-labelledby': 'merken' }),

  el('section', 'section', [
    el('div', 'wrap', [
      el('div', 'band rv-img', [
        foto(img('instituut-massage'), '', '', { tag: 'img' }),
        el('div', '', [
          h(2, 'Even helemaal tijd voor jezelf.', 'h1', { id: 'cta' }),
          buttons([button('Bekijk de arrangementen', '/behandelingen/arrangementen/', { style: 'light' })]),
        ]),
      ]),
    ]),
  ], { style: 'padding-bottom:0', 'aria-labelledby': 'cta' }),
);

/* ---------------------------------------------------------------- Behandelingen (behandelingen/index.astro) */
pages.behandelingen = page(
  el('header', 'wrap ph-head', [
    crumbs(['Home', '/'], ['Behandelingen']),
    h(1, 'Behandelingen', 'h1'),
    p('Nieuw bij Soleil? Begin met een <a class="link" href="/behandelingen/skin-discovery/">Skin Discovery</a>.', 'lede'),
  ]),
  el('section', 'wrap', [dyn('behandelingen-lijst')]),
);

/* ---------------------------------------------------------------- Tarieven (tarieven.astro) */
pages.tarieven = page(
  el('header', 'wrap ph-head', [
    crumbs(['Home', '/'], ['Tarieven']),
    h(1, 'Alle behandelingen &amp; prijzen', 'display'),
    p('Weet je niet goed wat kiezen en wil je aan huidverbetering werken? Boek dan best een <a class="link" href="/behandelingen/skin-discovery/">Skin Discovery</a>.', 'lede'),
    dyn('tarieven-chips'),
  ]),
  el('section', 'wrap', [dyn('tarieven')]),
);

/* ---------------------------------------------------------------- Nieuw (nieuw.astro) */
pages.nieuw = page(
  el('header', 'wrap ph-head', [
    crumbs(['Home', '/'], ['Nieuw']),
    h(1, 'Nieuw bij Soleil', 'display'),
    p('De verzorging van het seizoen, nieuwe producten en de volgende data, allemaal op één plek.', 'lede'),
    dyn('voorbeeld-noot', { soort: 'nieuw' }),
  ]),
  el('section', 'wrap', [dyn('in-de-kijker')], { 'aria-labelledby': 'kijker' }),
  el('section', 'section', [
    el('div', 'wrap', [
      el('div', 'sec-head', [
        el('div', '', [
          h(2, 'Nieuwe producten', 'h1 rv', { id: 'producten' }),
          p('Net binnen in het instituut en in de webshop.', 'muted rv'),
        ]),
        buttons([button('Naar de webshop', SHOP, { style: 'link', external: true, className: 'rv' })], '', { tag: 'none' }),
      ]),
      dyn('producten'),
    ]),
  ], { 'aria-labelledby': 'producten' }),
  el('section', 'section', [
    el('div', 'wrap', [
      el('div', 'sec-head', [h(2, 'Nieuwe data', 'h1 rv', { id: 'data' })]),
      dyn('activiteiten'),
    ]),
  ], { style: 'padding-top:0', 'aria-labelledby': 'data' }),
);

/* ---------------------------------------------------------------- Over Soleil (over-soleil.astro) */
const gallery = [
  ['instituut-massage', 'De behandelruimte met warm licht'],
  ['duo-cabine', 'Twee personen in de duo-cabine'],
  ['led-lichttherapie', 'Behandeltafel onder de LED-lamp'],
  ['zaakvoerder-toestel', 'Saskia naast een behandeltoestel'],
  ['gelaatsverzorging', 'Een gelaatsverzorging'],
  ['zaakvoerder-behandeling', 'Saskia geeft een behandeling'],
];
pages['over-soleil'] = page(
  el('header', 'wrap', [crumbs(['Home', '/'], ['Over Soleil'])], { style: 'padding-top:clamp(24px,3vw,40px)' }),
  el('section', 'wrap story section', [
    foto(img('zaakvoerder-portret'), `${info.owner}, zaakvoerder van Instituut Soleil`, 'framed rv-img', { loading: 'high', maten: true }),
    el('div', '', [
      p('Over Soleil', 'label'),
      h(1, 'Mijn persoonlijk verhaal', 'display story-title'),
      el('div', 'prose', [
        p(`Ik ben ${info.owner}, het gezicht achter Instituut Soleil. Al ${info.years} jaar begeleid ik met veel passie klanten naar een mooiere, sterkere en meer gebalanceerde huid.`, 'rv'),
        p('Mijn interesse in duurzame huidverbetering is ook heel persoonlijk gegroeid. Ik heb zelf jarenlang geworsteld met acne en pigmentvlekken en weet hoe frustrerend een probleemhuid kan zijn. Door te werken met huidverbeterende merken zoals Environ en Jane Iredale kreeg ik mijn eigen huid beter onder controle. Die ervaring motiveerde mij om mij steeds verder te verdiepen en bij te scholen.', 'rv'),
        p('Ik kies bewust voor actieve producten, innovatieve technieken en behandelingen die ondersteund worden door wetenschappelijk onderzoek en kennis. Geen snelle TikTok-trends of loze beloftes, maar eerlijk advies en een aanpak die past bij jouw huid.', 'rv'),
        p('In een stijlvol en rustgevend kader neem ik de tijd om naar jou en je huid te luisteren. Samen werken we stap voor stap aan een huid die gezonder, egaler, sterker en meer in balans aanvoelt.', 'rv'),
      ]),
    ]),
  ]),
  el('section', '', [
    el('div', 'wrap values', [
      el('div', 'rv', [h(2, 'Waar ik voor sta', 'h2', { id: 'sta' }), p('Drie principes die elke behandeling bepalen.')]),
      el('div', 'rv', [h(3, 'Geen quick fix', 'h3'), p('Elke huid is anders. Ik maak graag extra tijd vrij om je persoonlijk te adviseren en te begeleiden.')], { style: '--d:1' }),
      el('div', 'rv', [h(3, 'Advies voor thuiscare', 'h3'), p('Wonderen met enkel professionele behandelingen bestaan niet. 75% van het resultaat komt van je verzorging thuis.')], { style: '--d:2' }),
      el('div', 'rv', [h(3, 'Bewezen werking', 'h3'), p('Ik werk uitsluitend met producten en technieken die wetenschappelijk onderbouwd zijn.')], { style: '--d:3;grid-column:span 2' }),
    ]),
  ], { style: 'padding-bottom:var(--section)', 'aria-labelledby': 'sta' }),
  el('section', '', [
    el('div', 'wrap', [
      h(2, 'Merken waar ik achter sta', 'h1 rv', { id: 'merken', style: 'margin-bottom:clamp(20px,3vw,32px)' }),
      dyn('merken'),
      buttons([button('Naar de webshop', SHOP, { style: 'link', external: true })], '', { tag: 'p', style: 'margin-top:28px' }),
    ]),
  ], { 'aria-labelledby': 'merken' }),
  el('section', 'section', [
    el('div', 'wrap', [
      h(2, 'Het instituut', 'h1 rv', { id: 'inst' }),
      p('Een stijlvol en warm kader waar verzorging en ontspanning samenkomen.', 'lede rv', { style: 'margin-block:16px clamp(28px,4vw,48px)' }),
      el('div', 'gallery', gallery.map(([name, alt]) => foto(img(name), alt, 'ph rv-img'))),
      buttons([button('Volg Soleil op Instagram', INSTA, { style: 'link', external: true })], '', { tag: 'p', style: 'margin-top:28px' }),
    ]),
  ], { style: 'padding-bottom:0', 'aria-labelledby': 'inst' }),
);

/* ---------------------------------------------------------------- Contact (contact.astro) */
pages.contact = page(
  el('header', 'wrap ph-head', [
    crumbs(['Home', '/'], ['Contact']),
    h(1, 'Welkom bij Instituut Soleil', 'display'),
    p('Jouw moment van rust begint hier. Boek eenvoudig online je afspraak of neem gerust contact op bij vragen. Ik help je graag verder.', 'lede'),
  ]),
  el('section', 'wrap contact', [
    dyn('contact-kaart'),
    el('div', 'card card--forest rv', [
      h(2, 'Openingsuren'),
      dyn('openingsuren', { ruimte: false }),
      p('Tijdens behandelingen neem ik de telefoon niet op. Laat je naam en nummer achter, ik bel je zo vlug mogelijk terug.', 'muted', { style: 'margin-top:20px' }),
    ], { style: '--d:1' }),
    el('div', 'card route rv', [
      foto(img('instituut-massage'), 'De behandelruimte van Instituut Soleil', 'ph'),
      el('div', '', [
        h(2, 'Zo bereik je Instituut Soleil'),
        p('Instituut Soleil ligt vlak bij de afrit van de E17 in Beervelde. Je bereikt ons vlot en parkeert comfortabel op de ruime parking voor de deur.', 'muted'),
        p(`${info.street}, ${info.city}`, 'muted', { style: 'margin-top:8px' }),
        buttons([button('Route plannen', ROUTE, { style: 'ghost', external: true, icoon: 'map-pin' })], 'actions', { style: 'margin-top:24px' }),
      ]),
    ]),
    el('div', 'card route rv', [
      el('div', '', [
        h(2, 'Stuur een bericht', '', { id: 'bericht' }),
        p('Een vraag over een behandeling of een cadeaubon? Ik antwoord zo snel mogelijk.', 'muted'),
      ], { style: 'padding:clamp(24px,3vw,40px);align-self:start' }),
      el('div', '', [dyn('contactformulier')], { style: 'padding:clamp(24px,3vw,40px)' }),
    ], { style: 'grid-template-columns:minmax(0,0.8fr) minmax(0,1.2fr)' }),
  ]),
  el('section', 'wrap section', [
    el('div', 'gift rv', [
      el('div', '', [
        p('Cadeaubon', 'label label--sunset'),
        h(2, 'Schenk een moment van rust', 'h2', { id: 'cadeau' }),
        p('Een cadeaubon van Instituut Soleil koop je eenvoudig in de webshop.', 'muted'),
      ]),
      buttons([button('Naar de webshop', SHOP, { external: true })], '', { tag: 'none' }),
    ]),
  ], { style: 'padding-bottom:0', 'aria-labelledby': 'cadeau' }),
);

/* ---------------------------------------------------------------- Journaal (journaal/index.astro) */
pages.journaal = page(
  el('header', 'wrap ph-head', [
    crumbs(['Home', '/'], ['Journaal']),
    h(1, 'Journaal', 'h1'),
    p('Huidtips per seizoen en nieuws uit het instituut.', 'lede'),
  ]),
  dyn('journaal'),
);

/* ---------------------------------------------------------------- 404 (404.astro) */
pages['pagina-niet-gevonden'] = page(
  el('header', 'wrap ph-head', [
    h(1, 'Deze pagina bestaat niet (meer).', 'h1'),
    p('Misschien is ze verhuisd. Bekijk de behandelingen of ga terug naar de homepagina.', 'lede'),
    buttons([
      button('Naar de behandelingen', '/behandelingen/'),
      button('Naar de homepagina', '/', { style: 'link', icoon: 'geen' }),
    ], 'actions', { style: 'margin-top:32px' }),
  ], { style: 'min-height:50dvh' }),
);

for (const [slug, html] of Object.entries(pages)) writeFileSync(`${out}/${slug}.html`, html);
console.log(`pagina's: ${Object.keys(pages).join(', ')}`);
