# Instituut Soleil – WordPress

- `soleil/` – block theme. Lokaal gekoppeld via symlink:
  `~/Local Sites/soleil/app/public/wp-content/themes/soleil`
- `import/` – zet de inhoud van de statische site in WordPress.
- `PLAN.md` – opbouw, blokken, inhoudsmodel.
- `LIVE.md` – checklist om live te gaan.

## Opstarten
1. Local openen → site **soleil** starten (http://soleil.local)
2. WP-CLI: `wordpress/bin/wp <commando>` (bv. `wordpress/bin/wp post list`)

## Import

```sh
wordpress/bin/import            # site.ts, journal.json en de vaste pagina's -> WordPress (opnieuw draaien mag)
```

Het script bouwt `import/content/data.json` en `import/content/pages/*.html` en draait dan `import/import.php`.

**Slot na de oplevering.** Staat de optie `soleil_opgeleverd` aan, dan weigert de import: hij zou de teksten, prijzen
en instellingen van Saskia overschrijven. Toch nodig? `wordpress/bin/import --force` (vraagt om "ja" te typen).
Het slot gaat aan als laatste stap van de oplevering, zie `LIVE.md`.

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
Saskia heeft een account met de rol **Redacteur**: ze past alle inhoud en de instellingenpagina aan, maar ziet geen
thema's, plugins, gebruikers of WordPress-instellingen.

- **Soleil** (menu links): praktische gegevens, openingsuren, footer (ook de lijst behandelingen), verzorging in de kijker
- **Behandelingen**: één invulkaart per behandeling (foto, teksten, prijzen, onderdelen, extra vak); de pagina bouwt zichzelf op
- **Journaal**: artikels met een berichtkaart (foto, categorie, samenvatting); in de tekst ook het blok Openingsuren
- **Activiteiten, Producten, Merken, Reviews**: een eenvoudig formulier; voorbije data van activiteiten verdwijnen vanzelf
- **Pagina's**: home, behandelingen, tarieven, nieuw, over Soleil, contact, journaal en de 404-pagina (privé) zijn
  vergrendeld: teksten, links en foto's aanpassen kan, blokken toevoegen, verschuiven of verwijderen niet
- **Foto's uploaden**: nieuwe foto's worden bij het uploaden verkleind tot hoogstens 1600 px breed en bewaard als WebP
  (het origineel wordt niet bewaard). De geïmporteerde foto's blijven het originele bestand.
