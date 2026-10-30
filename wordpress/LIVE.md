# Live gaan: checklist

Werk van boven naar onder. Vink af in de pull request of in een kopie van dit bestand.

## 0. Beslissingen die nog nodig zijn

- [ ] **Nieuwsbrief.** De popup werkt zoals op de huidige site, maar de inschrijvingen gaan nergens naartoe: er is geen
  koppeling met een mailprogramma. Te kiezen:
  - koppelen aan een dienst (Mailchimp, Brevo, MailerLite, …): de keuze ligt bij Saskia, de koppeling is extra werk; of
  - de popup uitzetten tot dat zo is: Soleil > Praktisch > Nieuwsbrief, vinkje "Tonen" uit. Geen code nodig.
  Beslissing: ______________________
- [ ] **Privacybeleid.** De huidige pagina op instituutsoleil.be/privacy-policy/ is de Engelse standaardtekst van WordPress
  (over reacties, Gravatar en accounts, met lege rubrieken), geen beleid van Soleil. Die is daarom niet overgenomen.
  De pagina Privacybeleid (`/privacy-policy/`) staat klaar met een placeholder ("Voorbeeldinhoud: …").
  Nodig: een echte tekst in het Nederlands (wie, welke gegevens: contactformulier via e-mail, nieuwsbrief als die blijft,
  de online agenda van Optios, de webshop), en die in de pagina zetten. Titel en opbouw liggen vast, de tekst is vrij.
  Beslissing / wie levert de tekst: ______________________
- [ ] **Cache.** Gebruikt de hosting een pagina-cache? Het thema leegt elke nacht om middernacht de cache van `/nieuw/` en
  `/behandelingen/laser/` (`inc/cache.php`), voor WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache,
  Cache Enabler, SiteGround Speed Optimizer en WP Fastest Cache. Een andere cache (bv. van de hosting zelf) koppel je
  met `add_action( 'soleil_purge_url', … )`.
  Cache bij de hosting: ______________________

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
- [ ] **Privacybeleid:** de pagina `/privacy-policy/` bevat de echte tekst, niet meer de placeholder (zie 0). De link in de
  footer en bij de nieuwsbrief wijst ernaar (Soleil > Praktisch > Privacybeleid: `/privacy-policy/`).
- [ ] **WP-cron als echte cron.** WordPress voert geplande taken pas uit bij het eerste bezoek. Voor het legen van de cache
  om middernacht beter een cron op de server: in `wp-config.php` `define( 'DISABLE_WP_CRON', true );` en in de crontab
  `*/15 * * * * wp --path=<pad naar WordPress> cron event run --due-now >/dev/null 2>&1`.
  Controle: `wp cron event list --hook=soleil_clear_dated_pages` toont de volgende run om 00:00.
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
- [ ] Is er een pagina-cache: na middernacht `/nieuw/` bekijken, de voorbije data van gisteren zijn weg.

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
