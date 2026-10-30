# Instituut Soleil – WordPress

- `soleil/` – block theme. Lokaal gekoppeld via symlink:
  `~/Local Sites/soleil/app/public/wp-content/themes/soleil`
- `import/` – zet de inhoud van de statische site in WordPress.
- `PLAN.md` – opbouw, blokken, inhoudsmodel.

## Opstarten
1. Local openen → site **soleil** starten (http://soleil.local)
2. WP-CLI: `wordpress/bin/wp <commando>` (bv. `wordpress/bin/wp post list`)

## Import

```sh
node wordpress/import/build_data.mjs                      # site.ts + journal.json -> import/content/data.json
node wordpress/import/build_pages.mjs                     # vaste pagina's -> import/content/pages/*.html
wordpress/bin/wp eval-file wordpress/import/import.php    # in WordPress zetten (opnieuw draaien mag)
```

Opnieuw importeren overschrijft de vaste pagina's, behandelingen en instellingen met de inhoud van de statische
site. Draai het dus niet meer zodra Saskia zelf teksten aanpast.

## Controle na een WordPress-update
WordPress voegt soms zelf klassen of attributen toe aan blokken (bv. `wp-block-paragraph` sinds 7.0).
Dit script vergelijkt de HTML met de Astro-build en toont elk verschil:

```sh
node wordpress/bin/check-html.mjs            # artikel + behandelingpagina
node wordpress/bin/check-html.mjs --all      # alle pagina's
node wordpress/bin/check-html.mjs --chrome   # ook header en footer
```

Voorbije data (bv. laserdata) laat WordPress bewust weg; die tellen niet mee.

## Scripts
`main.js` wordt gebouwd uit dezelfde bronnen als de statische site (`src/scripts/main.ts` en het script in
`src/components/Newsletter.astro`). Na een wijziging daarin, vanuit de repo-root:

```sh
node wordpress/bin/build-js.mjs
```

## Wat Saskia zelf beheert
- **Soleil** (menu links): praktische gegevens, openingsuren, footer, verzorging in de kijker
- **Behandelingen**: één invulkaart per behandeling (foto, teksten, prijzen, onderdelen, extra vak); de pagina bouwt zichzelf op
- **Journaal**: artikels met een berichtkaart (foto, categorie, samenvatting); in de tekst ook het blok Openingsuren
- **Activiteiten, Producten, Merken, Reviews**: een eenvoudig formulier; voorbije data van activiteiten verdwijnen vanzelf
- **Pagina's**: home, behandelingen, tarieven, nieuw, over Soleil, contact, journaal en de 404-pagina (privé) zijn
  vergrendeld: teksten, links en foto's aanpassen kan, blokken toevoegen, verschuiven of verwijderen niet
- **Weergave > Menu's**: de lijst behandelingen in de footer
- **Foto's uploaden**: nieuwe foto's worden bij het uploaden verkleind tot hoogstens 1600 px breed en bewaard als WebP
  (het origineel wordt niet bewaard). De geïmporteerde foto's blijven het originele bestand.
