# Instituut Soleil – WordPress

Deze code staat enkel op de branch `wordpress`. `main` is de Astro-site die de klant als preview ziet en verandert niet.

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

Het script zet eerst het thema actief en WordPress in het Nederlands (`nl_BE`, nodig voor de data), bouwt dan
`import/content/data.json` en `import/content/pages/*.html` en draait `import/import.php`. Op een lege WordPress-installatie
is niets anders nodig: geen thema activeren, geen taal, geen permalinks, geen pagina's of menu's met de hand.

`wordpress/bin/import` werkt op de Local-site. Op de server gelden de stappen in `LIVE.md` (thema en taal in stap 3).

Een andere installatie op dezelfde Local-database (bv. een schone test):
`SOLEIL_WP_PATH=/pad/naar/wordpress wordpress/bin/import`.

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

- **Soleil** (menu links): praktische gegevens (ook de nieuwsbrief aan/uit), openingsuren, footer (ook de lijst behandelingen), verzorging in de kijker
- **Privacybeleid** (pagina): titel en opbouw vast, de tekst vrij (alinea's, tussentitels, lijsten)
- **Behandelingen**: één invulkaart per behandeling (foto, teksten, prijzen, onderdelen, extra vak); de pagina bouwt zichzelf op
- **Journaal**: artikels met een berichtkaart (foto, categorie, samenvatting); in de tekst ook het blok Openingsuren
- **Activiteiten, Producten, Merken, Reviews**: een eenvoudig formulier; voorbije data van activiteiten verdwijnen vanzelf
- **Pagina's**: home, behandelingen, tarieven, nieuw, over Soleil, contact, journaal en de 404-pagina (privé) zijn
  vergrendeld: teksten, links en foto's aanpassen kan, blokken toevoegen, verschuiven of verwijderen niet
- **Foto's uploaden**: nieuwe foto's worden bij het uploaden verkleind tot hoogstens 1600 px breed en bewaard als WebP
  (het origineel wordt niet bewaard). De geïmporteerde foto's blijven het originele bestand.
