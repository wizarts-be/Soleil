# Instituut Soleil: hifi V3

> (c) 2026 Wizarts BV. Alle rechten voorbehouden, zie [LICENSE](LICENSE).

A completely new site with the new logo. Built with Astro (static output) and self-hosted fonts. V4 (15 Oct 2026) reworks all client feedback: see `feedback/FEEDBACK-TRACKER.md` and `feedback/NOTION-CHANGELOG.md`.

```bash
npm install
npm run dev      # http://localhost:4321
npm run build    # static site in dist/
```

## Pages
Home, Behandelingen (overview + Huidanalyse & advies, Skin Relax, Skin Expert), Ontspanning (`/verwennen/`), Tarieven, Over Soleil, Journaal, Contact, 404. Old URLs (including the `/v2/` review links) redirect via `public/_redirects`.
All prices, treatments and hours live in `src/data/site.ts`. Articles are in `src/data/journal.json`.

## Sources
- Prices: the 2026 price list (PDF on instituutsoleil.be). Several prices from V2 were out of date.
- Opening hours: the news post "Nieuwe openingsuren" (7 Sept 2026): Thursday until 20:00, Friday from 08:00.
- Articles: copied verbatim from the live site.
- Photos: the same set as V2 (`public/img`).

## Brand
- **Look:** modern editorial, photo-led. Light theme only, no rounded corners (client request). Styles in `src/styles/global.css`.
- **Colours:** page `#fbf9f6`, beige `#f5efe6`, deeper beige `#ece4d6` (footer, panels), hazelnut `#a58a68` (buttons), logo tan `#c4a283`, ink `#4b3a2f`.
- **Type:** Hanken Grotesk (everything) and EB Garamond (wordmark only).
- **Owner:** Saskia (Ferket). 27 years confirmed by her own copy.

## Still to confirm with the client
See the last section of `feedback/NOTION-CHANGELOG.md`.
