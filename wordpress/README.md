# Instituut Soleil – WordPress

- `soleil/` – block theme. Lokaal gekoppeld via symlink:
  `~/Local Sites/soleil/app/public/wp-content/themes/soleil`
- `import/` – zet de inhoud van de statische site in WordPress (fase 5).
- `PLAN.md` – opbouw, blokken, inhoudsmodel.

## Opstarten
1. Local openen → site **soleil** starten (http://soleil.local)
2. WP-CLI: `wordpress/bin/wp <commando>` (bv. `wordpress/bin/wp post list`)

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
- **Weergave > Menu's**: de lijst behandelingen in de footer
