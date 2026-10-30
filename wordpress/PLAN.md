# Instituut Soleil: plan WordPress-thema

Doel: de huidige Astro-site als WordPress block theme. Zelfde uitzicht, zelfde gedrag, zelfde teksten.
Enige verschil: Saskia past zelf teksten, foto's, prijzen, data en openingsuren aan.

Opbouw zoals SK Lochristi (`Sklochristi-V1/wordpress`): block theme met theme.json, eigen dynamische blokken
die hun HTML in PHP bouwen, een invulkaart in het canvas met `useEntityProp`, meta met `show_in_rest`,
een herhaalbaar importscript via WP-CLI.

## Uitgangspunten

- **Markup = huidige markup.** Elke sectie geeft dezelfde tags, klassen, `id`'s, `aria-*` en inline `style`
  (bv. `style="--d:1"`) als de Astro-pagina. Zo werken `style.css` (= `global.css`) en `main.js` (= `main.ts`) ongewijzigd.
- **style.css = global.css**, op twee punten na:
  1. de kleurvariabelen in `:root` verwijzen naar theme.json (`--bone: var(--wp--preset--color--bone)`), zodat kleuren op één plek staan;
  2. onderaan een kort blok "WordPress gelijktrekken" (admin bar, `wp-block-*`-klassen die toch opduiken).
  De `<style>` uit `Newsletter.astro` komt er letterlijk bij. De `@import`s van fontsource vallen weg: fonts komen uit theme.json.
- **Fonts zelf gehost**: Raleway Variable (normaal + cursief) en EB Garamond Variable, latin + latin-ext,
  gekopieerd uit `node_modules/@fontsource-variable/*`. Zelfde familienamen (`'Raleway Variable'`, `'EB Garamond Variable'`),
  zodat `--sans` en `--serif` niet veranderen. Hanken Grotesk staat in package.json maar wordt niet gebruikt: niet meenemen.
- **Iconen**: de 13 Phosphor-iconen (light) die de site gebruikt, als svg in `assets/icons/`. `soleil_icon()` zet er
  `aria-hidden="true" focusable="false"` op, net als `Icon.astro`.
- **Logo**: `src/assets/logo-data.json` naar `assets/logo.json`, `soleil_logo()` bouwt dezelfde svg als `Logo.astro`. Favicon = `public/favicon.svg`.
- **URL's**: `/behandelingen/<slug>/` (CPT, `with_front: false`), `/journaal/<slug>/` (permalinkstructuur `/journaal/%postname%/`),
  pagina's `/tarieven/`, `/nieuw/`, `/over-soleil/`, `/contact/`, `/behandelingen/`, `/journaal/`. De redirects uit `public/_redirects` als `wp_redirect` in het thema.
- **Head** zoals `Base.astro`: titel (`<titel> | Instituut Soleil Beervelde`, behalve als "Soleil" er al in staat), description,
  canonical, `theme-color`, og-tags, JSON-LD `BeautySalon` (openingsuren uit de instellingen), `document.documentElement.classList.add('js')`.
  Titel en description per pagina: twee velden in de zijbalk van de pagina (paneel "Zoekmachines"); de description is de samenvatting (excerpt).
- **Taal**: interface en labels in het Nederlands, sentence case. WordPress in `nl_BE`, zodat `wp_date()` "donderdag 29 oktober 2026" en "7 september 2026" geeft.

## Hoe pagina-inhoud exact dezelfde HTML geeft

De vaste pagina's bestaan uit drie soorten blokken:

1. **`soleil/el`, container.** Rendert `<tag class id …attrs>…inner blocks…</tag>` zonder extra klassen.
   Attributen: `tag` (section, div, header, ul, li, figure, a, …), `className`, `anchor`, `attrs` (role, aria-labelledby, aria-label, style, href, target, rel).
   Vervangt `core/group`, dat `wp-block-group` en layoutklassen toevoegt en geen `ul`/`li`/`a` kent.
   In de editor dezelfde tag en klassen, dus dezelfde layout.
2. **Kernblokken voor tekst**: `core/paragraph`, `core/heading`, `core/list`. Ze krijgen een extra attribuut `htmlAttrs`
   (role, style, id) dat een `render_block`-filter met `WP_HTML_Tag_Processor` op de eerste tag zet. Zo blijft
   `<p class="rv" style="--d:1">` en `<ul class="asks rv" style="--d:1" role="list">` exact.
3. **Knoppen**: `core/buttons` + `core/button`, door een `render_block`-filter herschreven naar de huidige markup:
   `core/buttons` → `<div class="actions" …>` (of `<p>`, voor een losse link zoals "Lees mijn verhaal"),
   `core/button` → `<a class="btn btn--light" href target rel>Tekst<svg/></a>`.
   Stijlen in de editor: Knop, Licht, Omlijnd, Sunset, Link (→ `class="link"`). Icoon per knop
   (pijl rechts, pijl schuin, kaartspeld vooraan, geen), standaard volgens `target`.
4. **`soleil/foto`**: `<figure class="…"><img …></figure>` of een losse `<img>` (band, menu-kaart), met eigen alt-tekst
   per gebruik (dezelfde foto heeft op verschillende plaatsen een andere alt), `loading`/`fetchpriority`, en srcset uit WordPress.
5. **`soleil/icoon`**: één Phosphor-icoon (usp's, why-blokken).

Vaste links naar het boekingssysteem, de webshop, de route en Instagram staan in de inhoud als `/afspraak/`, `/webshop/`,
`/route/`, `/instagram/`. Een filter zet ze bij weergave om naar de url uit de instellingen (en die paden redirecten ook),
zodat een nieuwe boekingslink op één plek verandert.

## Blokken

Naamruimte `soleil/`, categorie "Soleil". Dynamisch = HTML in PHP (`inc/blocks.php`), editor = `ServerSideRender`
met een gele balk "Deze lijst vult zich vanzelf uit … Aanpassen doe je daar" + knoppen, zoals SK Lochristi.

| Blok | Toont | Bron | Waar |
|---|---|---|---|
| `site-header` | skip-link, header met logo, nav, mega-paneel Behandelingen, mobiel menu, dock-knop | behandelingen per groep (CPT), instellingen (boeking, telefoon, route, adres), foto van Skin Discovery in de menu-kaart | `parts/header.html` |
| `site-footer` | slogan, boekknop, openingsuren, behandelingenlijst, contact, onderbalk | instellingen, menu "Footer: behandelingen" (Weergave > Menu's, labels van de klant: "Skin Relax Facials", …) | `parts/footer.html` |
| `nieuwsbrief` | popup rechtsonder | vaste tekst, privacy-url uit instellingen | `parts/footer.html` |
| `openingsuren` | `.week` met dagen, uren, notitie; vandaag gemarkeerd door main.js | instellingen (hours, hoursNote) | footer, contact, in artikels (met `margin-block:1.6em`-wrapper) |
| `behandeling-switch` | `.tswitch` met alle behandelingen, groepen gescheiden | CPT behandeling | `single-behandeling.html` |
| `behandeling` | kop (`.thead` of make-up `.thero`), why, delen, prijzenpaneel, extra, tip; make-up: vragen, merkblok, services | meta van de behandeling | `single-behandeling.html` |
| `behandeling-pager` | vorige/volgende (rondlopend) | CPT, menuvolgorde | `single-behandeling.html` |
| `behandelingkaart` | invulkaart in de editor, niets op de site | meta (useEntityProp) | editor behandeling |
| `berichtkaart` | invulkaart: foto, categorie, samenvatting | featured image, categorie, excerpt | editor bericht |
| `home-kaarten` | 4 kaarten onder Behandelingen | 3 behandelingen (naam, sub, foto) + kaart "Ontspanning" (eigen tekst/foto, link `/behandelingen/#ontspanning`) | home |
| `in-de-kijker` | `.spot` | instellingen "In de kijker" | home, nieuw |
| `reviews` | `.quotes` | CPT review | home |
| `voorbeeld-noot` | "Voorbeeldreviews: …" / "Voorbeeldinhoud: …", enkel als een item als voorbeeld gemarkeerd is | CPT review / product + activiteit | home, nieuw |
| `merken` | `.btiles`, tegel met woord als er geen foto is (ISOV) | CPT merk | home, over-soleil |
| `producten` | `.btiles.news` | CPT product | nieuw |
| `activiteiten` | `.dates`, voorbije data vallen weg, max. 3 per activiteit | CPT activiteit | nieuw |
| `behandelingen-lijst` | `.tlist` per groep, "vanaf"-prijs of "meer info" | CPT + groep | behandelingen |
| `tarieven-chips` / `tarieven` | ankerchips en `.rates`-kaarten (zonder Skin Discovery, delen als aparte tabel, make-up uit de services) | CPT | tarieven |
| `contact-kaart` | adres, telefoon, e-mail, boekknop | instellingen | contact |
| `contactformulier` | formulier + bedankt-melding, `data-mailto` | instellingen (e-mail) | contact |
| `journaal` | `.posts` met alle berichten (PostCard) | berichten | journaal |
| `artikel` | crumbs, meta, titel, hoofdfoto, inhoud in `.prose`, knoppen onderaan | bericht | `single.html` |
| `journaal-meer` | "Meer uit het journaal", 3 andere berichten | berichten | `single.html` |
| `pagina-inhoud` | inhoud van een vaste pagina | pagina "Journaal" / private pagina "Pagina niet gevonden" | `home.html`, `404.html` |
| `el`, `foto`, `icoon` | zie hierboven | attributen | vaste pagina's |

Prijzen worden overal met dezelfde helper getoond (`soleil_prices()` = `Prices.astro`, `soleil_euro()` = `euro()`:
een getal krijgt `€`, tekst zoals "€25 / €35" of "vanaf €95" blijft).

## Inhoudsmodel

Alles in standaard WordPress-velden (titel, featured image, excerpt, menu_order, taxonomie, post meta). Geen ACF.

**Behandeling** (CPT `behandeling`, publiek, `/behandelingen/<slug>/`), taxonomie **groep** (Huidverbetering, Ontspanning; volgorde via term meta).
Volgorde = menu_order (zelfde volgorde als `treatments` in site.ts). Velden volgens het type `Treatment`:

| site.ts | WordPress |
|---|---|
| name, slug, group | titel, slug, groep |
| img | featured image; alt-tekst in meta `foto_alt` |
| sub, kicker, title, intro, from, tip, note | meta (string/number) |
| link {href, label} | meta `link_url`, `link_label` |
| prices: PriceGroup[] | meta `prijzen` (JSON: groepen met titel, noot, rijen naam/duur/prijs/toelichting) |
| why {problem, solution, benefit, titles} | meta `waarom` (JSON; probleem als tekst of lijst) |
| extra {title, text, list} | meta `extra` (JSON) + optioneel `extra_activiteit`: de data komen dan uit die activiteit (laser) |
| parts[] | meta `delen` (JSON, foto als bijlage-ID) |
| page (make-up) | meta `makeup` (JSON: titel, vragen, cta, merkblok, services) |

Skin Discovery staat als prijsrij bij drie behandelingen (zoals nu). Make-up: de tarieven komen uit de services, zoals in site.ts.

**Invulkaart** (`soleil/behandelingkaart`): het enige blok in de editor van een behandeling (template met `templateLock: all`).
Secties in het canvas: kop (kicker, intro, foto, knop), waarom, prijzen (groepen en rijen toevoegen/verwijderen/verschuiven),
delen, extra blok, tip/noot, make-up-pagina. De pagina zelf bouwt de template.

**Journaal** = berichten (labels "Journaal", "Artikel"). Categorieën Nieuws, Huidtips, Nieuw. `soleil/berichtkaart`
bovenaan zoals SK Lochristi: foto, categorie (pills), samenvatting. In de tekst: alinea's, tussentitels (krijgen `class="h3"`),
lijsten en het blok **Openingsuren**.

**Activiteit, product, merk, review**: eenvoudig formulier (klassieke editor uit, eigen metaboxen), volgorde met menu_order.

- activiteit: titel, tekst, data (datumkiezer per rij), tekst zonder data ("Datum volgt"), link + label, foto, voorbeeld-vinkje
- product: naam, merk, tekst, foto, link (standaard webshop), voorbeeld-vinkje
- merk: naam, regel, foto (optioneel)
- review: tekst, naam, behandeling, voorbeeld-vinkje

**Instellingenpagina "Soleil"** (menu-item in de admin):
- Praktisch: naam, zaakvoerder, straat, gemeente, telefoon (+ tel-link), e-mail, boekingslink, webshop, route, Instagram, Facebook, btw, privacybeleid
- Openingsuren: maandag t.e.m. zaterdag (open, sluit, noot; leeg = gesloten) + notitie onder de uren
- Footer: slogan en telefoonnoot
- In de kijker: seizoen, naam, tekst, foto + alt, duur, prijs, link

**Verlopen data**: activiteitdata worden bij weergave gefilterd (datum ≥ vandaag, Europe/Brussels), zowel op /nieuw/ (max. 3) als in het extra blok van de laserpagina.

## Vergrendelde pagina's

home (voorpagina), over-soleil, contact, nieuw, tarieven, behandelingen, journaal (berichtenpagina), pagina-niet-gevonden (privé, getoond door `404.html`).
Via `block_editor_settings_all` krijgen ze `templateLock: "contentOnly"`: Saskia past teksten, links en foto's aan,
maar voegt geen blokken toe en verschuift of verwijdert niets. Dynamische blokken tonen in de editor waar de inhoud vandaan komt.

## Bestandsstructuur

```
wordpress/
  PLAN.md
  README.md                  opstarten, import, wat Saskia zelf beheert
  bin/wp                     WP-CLI voor de Local-site "Soleil" (zoals SK Lochristi)
  soleil/                    het thema
    style.css                global.css + nieuwsbrief + gelijktrekken
    theme.json               kleuren, fonts, template parts; geen layout/spacing/presets van WordPress
    functions.php
    inc/
      helpers.php            iconen, logo, euro, uren, prijstabel, data, links
      settings.php           instellingenpagina "Soleil" + soleil_info()/soleil_hours()/soleil_spotlight()
      content-model.php      CPT's, taxonomie, meta, metaboxen
      site-chrome.php        header, footer, nieuwsbrief, menu-locatie, head-tags
      blocks.php             dynamische blokken + render_block-filters (el, knoppen, htmlAttrs, links)
      admin.php              labels, vergrendeling, beheer-links, adminbalk
    assets/
      css/editor.css         editor: invulkaarten, knoppen, beheerbalk
      js/main.js             main.ts + nieuwsbrief, getranspileerd met esbuild
      js/blocks-editor.js    editorkant van de blokken en kaarten
      fonts/*.woff2
      icons/*.svg
      logo.json, favicon.svg
    parts/header.html, footer.html
    templates/front-page.html, page.html, home.html, single.html, single-behandeling.html, 404.html, index.html
  import/
    build_data.mjs           site.ts + journal.json → content/data.json (Node, TypeScript-strip)
    build_pages.mjs          vaste pagina's → content/pages/*.html (blokmarkup, teksten letterlijk uit src/pages)
    blocks.mjs               helpers die geldige blokmarkup schrijven
    import.php               wp eval-file: instellingen, media, CPT's, berichten, pagina's, menu, opties
```

## Verschillen met SK Lochristi

- **Geen ACF.** Velden in eigen code: `register_post_meta` (show_in_rest) + kleine metaboxen voor de eenvoudige types.
- **Instellingenpagina** in plaats van `inc/config.php`: praktische gegevens staan niet in code.
- **Kleuren één keer**, in theme.json; style.css gebruikt de presets.
- **Editor-CSS in `assets/css/editor.css`** in plaats van een heredoc in admin.php.
- **Vaste pagina's met `templateLock: contentOnly`.**
- **`soleil/el` in plaats van `core/group`**, plus `htmlAttrs` op kernblokken en herschreven knoppen: de HTML moet exact de bestaande zijn.
- **Header zonder WordPress-menu**: de opbouw ligt vast (behandelingen uit de CPT, vaste pagina's). Enkel de footerlijst is een menu.
- **Importbouwers in Node** in plaats van Python, omdat site.ts TypeScript is (Node 25 leest het rechtstreeks).
- **Complexe velden als JSON-meta** (prijzen, waarom, delen, make-up), bewerkt in de invulkaart.

## Fases

1. Dit plan.
2. Basis: theme.json, style.css, editor.css, fonts, header, footer, instellingenpagina.
3. Inhoudstypes, meta, behandelingkaart en berichtkaart.
4. Dynamische blokken en templates.
5. Vergrendelde pagina's + importscript.
6. Controle: elke pagina naast `npm run dev`, desktop (1440) en mobiel (390), met `scripts/shots.mjs`; verschillen oplossen.

Nodig vóór fase 2 getest kan worden: een Local-site **"Soleil"** (PHP 8.2+, WordPress 6.8+), met het thema als symlink
in `wp-content/themes/soleil` en de taal `nl_BE`.
