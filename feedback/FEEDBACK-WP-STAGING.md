# Feedbackronde WordPress-staging (november 2026)

Feedback van Saskia na haar eerste weken op https://soleil.staging.wizarts.be/. Gestart op 16 november 2026.
Eén rij per punt. Status: ✅ klaar en gepusht · 🚀 ook op de staging · ⏳ bezig · ⬜ nog te doen · ❓ eerst vragen.

## Werkwijze (lees dit eerst)

**Regel nummer één: code gaat naar de staging, de inhoud blijft daar.** Saskia werkt zelf op de staging. Nooit de database
van Local naar de staging zetten: dan zijn haar teksten, prijzen en foto's weg.

- **Repo:** `~/WIZARTS/Soleil/Soleil-Hifi_V3`, branch `wordpress`, remote `origin` (= `wizarts-be/Soleil`). Remote `old` niet gebruiken.
- **Local:** site `soleil` in Local starten → http://soleil.local. Het thema is een symlink naar `wordpress/soleil` in de repo.
  WP-CLI: `wordpress/bin/wp <commando>`.
- **Login lokaal:** dezelfde als op de staging (beheerder `wzbeheer-6dae`). Testen als Saskia: Gebruikers → `saskia.ferket` →
  Switch To (plugin User Switching).
- **Lokale database:** kopie van de staging van 16/11 12:18. Saskia werkt intussen verder op de staging, dus lokaal loopt achter.
  Opnieuw binnenhalen: zie "Staging naar Local" onderaan.
- **Slot op de import** (`soleil_opgeleverd = 1`) staat aan, op de staging en lokaal. `wordpress/bin/import` nooit met `--force` draaien.
- **SSH staging:** `ssh stagingwizartsbe@ssh020.webhosting.be` (de naam `ssh.staging.wizarts.be` werkt nog niet). Login met SSH-sleutel.
  Site: `~/subsites/soleil.staging.wizarts.be`, WP-CLI is er (`wp`).
- **Back-ups:** staging-database van 16/11 in `~/private/backups/` op de server. Lokale bestanden in `~/WIZARTS/Soleil/staging-pull/`
  (dump van de staging, de oude lokale database en de oude lokale uploads).
- **Testdata lokaal:** bij het product "Zachte peeling voor thuis" (ID 31) staat lokaal testtekst in "Info over dit product".
  Niet op de staging.

### Thema naar de staging zetten

Altijd eerst een back-up en een droge run (`-n`), dan pas echt:

```sh
ssh stagingwizartsbe@ssh020.webhosting.be 'cd ~/subsites/soleil.staging.wizarts.be && wp db export ~/private/backups/soleil-$(date +%F-%H%M).sql'
cd ~/WIZARTS/Soleil/Soleil-Hifi_V3/wordpress
rsync -rcn --delete --itemize-changes soleil/ stagingwizartsbe@ssh020.webhosting.be:subsites/soleil.staging.wizarts.be/wp-content/themes/soleil/
# klopt de lijst? dan zonder -n
```

Daarna op de staging nakijken: home, /nieuw/, een behandeling, en de editor als Saskia.

### Inhoud op de staging aanpassen

Wijzigingen aan de inhoud (bv. een nieuwe sectie op de home) staan als script in `wordpress/content-updates/`.
Na het uitrollen van het thema: script naar de server kopiëren en draaien. Elk script doet niets als het al gedraaid heeft.

```sh
scp wordpress/content-updates/<script>.php stagingwizartsbe@ssh020.webhosting.be:private/
ssh stagingwizartsbe@ssh020.webhosting.be 'cd ~/subsites/soleil.staging.wizarts.be && wp eval-file ~/private/<script>.php'
```

### Staging naar Local

```sh
ssh stagingwizartsbe@ssh020.webhosting.be 'cd ~/subsites/soleil.staging.wizarts.be && wp db export ~/private/backups/soleil-$(date +%F-%H%M).sql'
# dump ophalen met scp, lokaal inladen met de mysql van Local (socket in ~/Library/Application Support/Local/run/<site-id>/mysql/),
# dan: wordpress/bin/wp search-replace 'https://soleil.staging.wizarts.be' 'http://soleil.local' --all-tables --precise
# uploads: rsync van wp-content/uploads/ naar ~/Local Sites/soleil/app/public/wp-content/uploads/
```

## Stand van zaken

**Gepusht maar nog niet op de staging:** commits `f385c74`, `ddaa217`, `b6d36e2`, `8a8015b`, `000b25f`, `74ee9f4`, `98db93c`, `3e0cbea` en `3d0da8b`. Bij de volgende keer uitrollen gaan ze mee.

## Punten

### Algemeen en typografie

| # | Feedback | Actie | Status |
|---|---|---|---|
| 1 | "Nieuw bij Soleil" → eyebrow iets groter | Alle eyebrows boven een titel 13px en semi-bold (600). Menukopjes en tags op kaarten blijven 11px | ✅ `f385c74` |
| 2 | "Het logo in de navigatie vervangen door het logo van Instituut in de Zon" + als favicon | Nav: officieel logo LOGO-SOLEIL-2026-COLOR (media ID 306) als `assets/logo-soleil.webp`, krimpt mee bij scrollen; oude svg-zon + woordmerk weg. Favicon: `assets/favicon.png`, zon + "instituut" zonder SOLEIL op crème vierkant, ook apple-touch-icon. Site-pictogram in WP staat op 0 (anders overschrijft dat het favicon) | ✅ `b6d36e2` |
| 3 | Activiteit bewerken → titel moet bold staan | | ⬜ |
| 4 | Blok ook kapot na tekst aan te passen (bij activiteit?) | Eerst nabootsen als Saskia | ⬜ |
| 5 | Saskia heeft op sommige pagina's "geen rechten" | Oorzaak: vaste pagina's zijn vergrendeld en lijsten vullen zich uit Producten, Reviews, … De gele balk met uitleg en de knop "Bewerk …" staat nu altijd zichtbaar in de editor. Per pagina nakijken wat ze nog echt mist | ⏳ deels `ddaa217` |

### Home

| # | Feedback | Actie | Status |
|---|---|---|---|
| 6 | "Stralen begint met een huid in balans?" | Geen actie in de feedback. In de media staat TAGLINE-SOLEIL-2026-COLOR (ID 308) met die zin, misschien gaat het daarover | ❓ |
| 7 | Goudachtige quote: TAGLINE-SOLEIL-2026-COLOR ("Stralen begint bij een huid in balans") als aparte sectie | Op de home na Merken, boven de laatste band. Beige vak op de breedte van de kolom, zoals Skin Discovery. Foto aanpasbaar in de editor. **Inhoud:** op de staging nog draaien: `content-updates/2026-11-17-tagline-home.php` (zie "Inhoud op de staging aanpassen") | ✅ `000b25f` (script nog draaien op de staging) |
| 8 | Journaal op home: 1 blogpost + knop naar het overzicht ("seizoenstips"); de sectie "Ik ben Saskia" weg, daar het journaal | Inhoud op de staging mee aanpassen (geen database-import) | ⬜ |
| 9 | Blok boven de footer "Even helemaal tijd voor jezelf" weg | | ⬜ |

### Nieuw

| # | Feedback | Actie | Status |
|---|---|---|---|
| 10 | Product in de kijker → detailpagina over het nieuwe product | Eigen pagina per product op `/nieuw/<product>/`, nieuw veld "Info over dit product", knop naar de webshop, "Ook nieuw" onderaan. Info leeg = kaartje gaat naar de webshop | ✅ `ddaa217` |
| 11 | Nieuw bij Soleil: geen toegang om tekst en dingen aan te passen | Zie 5: de gele balk wijst nu naar Producten / Activiteiten / In de kijker. Nakijken of er nog vaste tekst is die ze niet kan aanpassen | ⏳ |

### Behandelingen

| # | Feedback | Actie | Status |
|---|---|---|---|
| 12 | Arrangementen (home): foto niet aanpasbaar, blok mag kleiner (geen rechten) | Het gaat om de band "Even helemaal tijd voor jezelf" (knop naar de arrangementen). In de editor was de achtergrondfoto 0x0 en lag de overloop erover: niets aan te klikken, leek vergrendeld. Opgelost voor elke achtergrondfoto (`soleil/foto` met tag img): foto zichtbaar, knop "Foto vervangen" altijd zichtbaar rechtsboven. Titel en knop blijven aanpasbaar. Ook: eigen kaart "Ontspanning" in Behandelingen nu altijd open en compact. "Blok kleiner" en punt 9 (blok weg) nog te bespreken | ⏳ ontgrendelen ✅, "kleiner" nog te doen |
| 13 | Prijzen: bij precies 4 prijzen kaartjes zoals make-up, anders het prijzenvak; Saskia kan zelf kaartjes zetten/aanpassen/weghalen op elke behandeling | Kaartjes komen uit de prijsrijen (één bron). Per behandeling bij Prijzen: "Blokken of één prijskaart?": Automatisch (4 = blokken) / Blokken / Eén prijskaart, titel boven de blokken (leeg = Behandelingen, zie 19) en per prijs "Tekst in het blok" (leeg = toelichting). Kaartje weg = prijs weg. Het prijzenvak houdt dan enkel de naam, extra vak, tip en de knop. Lege prijsrijen en lege tabellen tellen niet mee en geven geen gat meer (Huidanalyse had een lege 2e tabel). Nu automatisch kaartjes: Huidanalyse en Lichaam. Tarieven blijft de tabel | ✅ `74ee9f4` |
| 14 | "Skin Expert"-prijsblokjes naast de behandelingen weg, op die plaats gewoon "In de kijker deze winter" | Gelezen als: het vak rechts op Skin Expert begon met "Skin Expert / De prijzen staan bij elke behandeling hiernaast". Staan de prijzen niet in het vak (onderdelen of blokken) en is er een extra vak, dan begint het vak nu meteen met dat extra vak ("In de kijker deze winter"), dan de tip en de knop. Behandelingen met een prijzenvak (bv. Handen & voeten) blijven zoals ze zijn. Leeg extra vak = geen lege ruimte meer | ✅ `98db93c` |
| 15 | Handen & voeten: extra blokje met meer info (geen pedicure zonder gelaatsverzorging); stond 2 keer onder de prijzen | Stond er 2x: noot onder de prijzen + extra vak "Pedicure". Saskia's tekst (extra vak) blijft, als eigen beige blok links onder de tekst (staan de prijzen rechts in het vak, dan komt het extra vak links), met optionele foto (nieuw veld bij Extra vak); lege regel = nieuwe alinea. Lokaal staat er als voorbeeld foto 188 (HANDEN EN VOETEN (1)); op de staging kiest Saskia zelf. Noot leeggemaakt. **Inhoud:** op de staging nog `content-updates/2026-11-17-pedicure-noot.php` draaien (doet niets als de noot intussen veranderd is) | ✅ `3e0cbea` (script nog draaien op de staging) |
| 16 | Skincare make-up: geen opsomming, gewoon een tekstblokje; blokjes meteen onder de hero | Vragen-opsomming wordt gewone tekst (lopend; lege regel = alinea). Editor: veld "Tekst onder de titel" in plaats van "Vragen" (bestaande vragen staan er al in). Volgorde: hero, dan de blokjes (minder witruimte ertussen), dan het merkblok Jane Iredale. Geldt ook voor Laser (zelfde opbouw). Geen inhoud te wijzigen op de staging | ✅ `3d0da8b` |
| 17 | Laserontharing: tekst is weg; 4 blokjes kunnen toevoegen | Oorzaak: "Eigen paginaopbouw" (make-up) stond aan op laser voor een actie, dat verbergt de hele gewone pagina. Nu: laser terug op de gewone opbouw (titel, intro, Herken je dit?, laserdata), zelfde opbouw als de andere behandelingen: in de hero enkel de eerste alinea van de intro, de rest links onder de hero (geldt voor elke behandeling met een lange intro); "Intake gesprek" staat als prijs in het prijzenvak (vanaf 4 prijzen blokken, of zelf "Blokken" kiezen); laserdata links als eigen blok. Schakelaar "Eigen paginaopbouw" enkel nog zichtbaar waar hij aan staat (make-up). Ook: data altijd in het Nederlands (WordPress staat op en_US, data stonden in het Engels, ook op Nieuw), intro met alinea's. **Inhoud:** op de staging nog `content-updates/2026-11-17-laser-actie.php` draaien (oude waarde blijft bewaard in makeup_backup_2026_11_17) | ⏳ lokaal klaar |
| 18 | Laserontharing: H2-titel boven de tekst ("Actie van de maand") | Nieuw blok "Actie" op elke behandeling: kleine regel, H2 (standaard "Actie van de maand"), tekst en foto, onderaan de pagina. Saskia's promo oksels (4+1 gratis, voorwaarden, foto) staat erin via het script van 17 | ⏳ lokaal klaar |
| 19 | Make-up: standaard "Behandelingen" boven de blokken, maar zelf aan te passen | | ❓ alleen make-up of alle pagina's met kaarten? |

### Over Soleil en merken

| # | Feedback | Actie | Status |
|---|---|---|---|
| 20 | Merken waar ik achter sta → blokjes klikbaar, Saskia kan per merk een link toevoegen | Veld "Link" bij Merken | ⬜ |
| 21 | Over Soleil: vermelding van Facebook en Instagram | Links staan al in Soleil > Praktisch | ⬜ |
| 22 | Over Soleil: popup "nieuwsbrief ontvangen", net zoals op home? | | ❓ |

## Open vragen voor Saskia / PM

- Punt 6: wat moet er gebeuren met "Stralen begint met een huid in balans"?
- Punt 8: is "Seizoenstips" de titel van de sectie op home of een categorie in het journaal?
- Punt 19: "Behandelingen" boven de blokken: alleen voor make-up of voor alle pagina's met kaarten?
- Punt 22: nieuwsbriefpopup op Over Soleil ook, of alleen op home?
- Product zonder info: kaartje naar de webshop (nu) of altijd naar een eigen pagina?
