# Live gaan: checklist

Werk van boven naar onder. Vink af in de pull request of in een kopie van dit bestand.

## 1. Server

- [ ] **PHP 8.2 of nieuwer.** Getest met 8.2.29.
- [ ] **WordPress 7.0 of nieuwer.** Getest met 7.1.4. Het thema houdt rekening met de klassen die WordPress sinds 7.0 zelf op blokken zet.
- [ ] **Imagick met HEIC- en WebP-ondersteuning.** Nieuwe uploads worden verkleind tot 1600 px breed en omgezet naar WebP (`inc/uploads.php`).
  Met enkel GD gaat dat voor JPG en PNG, maar foto's van een iPhone (HEIC) blijven dan zoals ze zijn. Controle:
  ```sh
  wp eval 'var_dump(_wp_image_editor_choose(), wp_image_editor_supports(array("mime_type"=>"image/heic")), wp_image_editor_supports(array("mime_type"=>"image/webp")));'
  ```
  Verwacht: `WP_Image_Editor_Imagick`, `true`, `true`.
- [ ] **WP-CLI** beschikbaar (voor de import en de controle).
- [ ] **HTTPS** actief, en `home` en `siteurl` op `https://instituutsoleil.be`.
- [ ] In `wp-config.php`: `define( 'DISALLOW_FILE_EDIT', true );` (geen code-editor in de admin).

## 2. Back-ups

- [ ] Dagelijkse back-up van de database en `wp-content/uploads` bij de hosting, minstens 14 dagen bewaard.
- [ ] Eén back-up vlak vóór de import (stap 4) en één vlak erna. Noteer waar ze staan.
- [ ] Een back-up eens teruggezet op een testomgeving, zodat je weet dat het werkt.

## 3. Thema en instellingen

- [ ] De map `wordpress/soleil` naar `wp-content/themes/soleil`, thema **Instituut Soleil** activeren.
- [ ] Taal: `wp language core install nl_BE --activate`. Nodig voor de data ("Donderdag 29 oktober 2026", "7 september 2026").
- [ ] Tijdzone `Europe/Brussels` (de import zet ze ook): voorbije data verdwijnen op basis van deze tijdzone.
- [ ] **Permalinks** op `/journaal/%postname%/` (de import zet ze), daarna `wp rewrite flush`. Controle:
  - `/journaal/peelen-na-de-zomer/` is een artikel
  - `/behandelingen/skin-relax/` is een behandeling (niet `/journaal/behandelingen/…`)
  - `/tarieven/`, `/nieuw/`, `/over-soleil/`, `/contact/`, `/behandelingen/`, `/journaal/` werken
- [ ] **Redirects** uit `public/_redirects`. Die zitten in het thema (`inc/redirects.php`), er is niets op de server nodig. Controle, elk moet 301 geven:
  ```sh
  for u in /verwennen/ /behandelingen/gelaatsverzorging/ /behandelingen/environ-df/ /behandelingen/peelings/ \
           /behandelingen/microneedling/ /behandelingen/neo-lift/ /v2/tarieven/; do
    curl -s -o /dev/null -w "$u %{http_code} %{redirect_url}\n" https://instituutsoleil.be$u; done
  ```
- [ ] **Privacybeleid:** de link in de footer en bij de nieuwsbrief gaat naar `https://instituutsoleil.be/privacy-policy/`.
  Op de nieuwe site bestaat die pagina niet vanzelf. Maak ze aan (of zet de link goed onder Soleil > Praktisch).
- [ ] Instellingen > Lezen: **niet** "Zoekmachines ontmoedigen".

## 4. Inhoud importeren

- [ ] Vanuit de repo, met WP-CLI naar de live site:
  ```sh
  wordpress/bin/import
  ```
  (Het script gebruikt `wordpress/bin/wp`, dat naar de Local-site wijst. Op de server: `node wordpress/import/build_data.mjs`,
  `node wordpress/import/build_pages.mjs`, dan `wp eval-file wordpress/import/import.php` met de map `import/` en `public/img/` erbij.)
- [ ] Controle tegen de huidige site (Astro-build in `dist/`):
  ```sh
  WP_URL=https://instituutsoleil.be node wordpress/bin/check-html.mjs --all --chrome --build
  ```
  Verwacht: alle pagina's gelijk.
- [ ] Een paar pagina's op een gsm bekijken: home, een behandeling, tarieven, contact (formulier versturen opent het mailprogramma).
- [ ] Een pagina-cache, als die er is, moet minstens dagelijks verlopen: anders blijven voorbije laser- en workshopdata staan
  op `/nieuw/` en `/behandelingen/laser/`.

## 5. Account van Saskia

- [ ] Account met de rol **Redacteur** en haar eigen e-mailadres, het wachtwoord kiest ze zelf via de link in de mail:
  ```sh
  wp user create saskia <haar e-mailadres> --role=editor --display_name=Saskia --first_name=Saskia --send-email
  ```
- [ ] Als redacteur nagekeken: Soleil (instellingen), Journaal, Pagina's, Behandelingen, Activiteiten, Producten, Merken, Reviews en Media.
  Geen thema's, plugins, gebruikers of WordPress-instellingen.
- [ ] Het beheerdersaccount heeft een sterk wachtwoord en is niet van Saskia. Testaccounts van de ontwikkeling verwijderd.
- [ ] Saskia kort rondgeleid: instellingenpagina, een behandeling aanpassen, een artikel schrijven, een vaste pagina aanpassen.

## 6. Slot op de import (laatste stap)

Vanaf nu past Saskia de inhoud zelf aan. Een nieuwe import zou haar teksten, prijzen en instellingen overschrijven.

- [ ] Slot aanzetten:
  ```sh
  wp option update soleil_opgeleverd 1
  ```
- [ ] Controle: `wordpress/bin/import` weigert nu. Enkel met `--force` (en "ja" typen) draait hij nog.
