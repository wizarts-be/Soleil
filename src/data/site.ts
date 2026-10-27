/*
  Instituut Soleil: single source for practical info, treatments and prices.
  Source: prijslijst 2026 + feedback Saskia (post-its, Word document, mail), 9 to 15 Oct 2026.
  See feedback/FEEDBACK-TRACKER.md for every change.
*/

export const info = {
  name: 'Instituut Soleil',
  owner: 'Saskia',
  tagline: 'Stralen begint met een huid in balans',
  years: 27,
  street: 'Rivierstraat 70',
  city: '9080 Beervelde',
  phone: '0470 22 92 02',
  phoneHref: 'tel:+32470229202',
  email: 'info@instituutsoleil.be',
  booking: 'https://booking.optios.net/18875',
  webshop: 'https://www.webshopsoleil.be/',
  route: 'https://www.google.com/maps/dir/?api=1&destination=Rivierstraat+70+9080+Beervelde',
  instagram: 'https://www.instagram.com/instituutsoleil/',
  facebook: 'https://www.facebook.com/Instituut-Soleil-1582526178629782/',
  vat: 'BE 0837.999.371',
};

/* d: 0 = zondag … 6 = zaterdag */
export type Day = { day: string; short: string; d: number; open?: string; close?: string; note?: string };
export const hours: Day[] = [
  { day: 'Maandag', short: 'Ma', d: 1, open: '09:00', close: '18:00' },
  { day: 'Dinsdag', short: 'Di', d: 2, open: '08:00', close: '18:30' },
  { day: 'Woensdag', short: 'Wo', d: 3 },
  { day: 'Donderdag', short: 'Do', d: 4, open: '08:00', close: '20:00' },
  { day: 'Vrijdag', short: 'Vr', d: 5, open: '08:00', close: '18:00' },
  { day: 'Zaterdag', short: 'Za', d: 6, open: '09:00', close: '13:30' },
];
/* Shown under the opening hours (post-it Saskia, 15 Oct). */
export const hoursNote = 'Zaterdagnamiddag na telefonische afspraak.';
export const time = (s?: string) => s?.replace(':00', 'u').replace(':', 'u').replace(/^0/, '');

/* [naam, duur, prijs, toelichting?]. prijs: number in euro, string for ranges or notes */
export type Row = [string, string, number | string, string?];
export type PriceGroup = { title?: string; line?: string; rows: Row[]; note?: string };

export const euro = (v: number | string) => (typeof v === 'number' ? `€${v}` : v);

/* ---------- Huidverbetering: one page each ---------- */
export type Group = 'Huidverbetering' | 'Ontspanning';
export type Treatment = {
  slug: string;
  group?: Group;
  name: string;
  sub: string;
  img: string;
  alt: string;
  from: number;
  intro: string;
  prices: PriceGroup[];
  tip?: string;
  note?: string;
  link?: { href: string; label: string };
  /* Problem the visitor recognises, what Soleil does about it, what she gains. Kept to one or two lines each.
     titles overrides the three block headings; a problem given as a list shows as bullets. */
  why?: { problem: string | string[]; solution: string; benefit: string; titles?: [string, string, string] };
  /* Page heading and small line above it, when they differ from the menu name and sub. */
  title?: string;
  kicker?: string;
  /* Extra block in the side panel, under the prices (e.g. the laser dates). */
  extra?: { title: string; text?: string; list: string[] };
  parts?: { id: string; name: string; img: string; alt: string; text: string; prices: PriceGroup[] }[];
  /* Own page layout (make-up): intro questions, a brand block and simple service blocks instead of the price panel. */
  page?: {
    title: string;
    questions: string[];
    cta: string;
    story: { label: string; title: string; text: string[]; img: string; alt: string };
    services: { name: string; tag?: string; text: string; dur: string; price: number | string }[];
  };
};

export const discovery: Row = ['Skin Discovery', '90 min', 105, 'analyse + verzorging + advies, 1e kennismakingsbehandeling'];

export const skin: Treatment[] = [
  {
    slug: 'skin-discovery',
    why: { problem: 'Je wilt iets aan je huid doen, maar je weet niet waar te beginnen of welke producten bij je passen.', solution: 'In één afspraak analyseer ik je huid, geef ik een verzorging op maat en stel ik samen een plan voor thuis op.', benefit: 'Je weet meteen wat je huid nodig heeft en geeft geen geld meer uit aan producten die niet werken.' },
    name: 'Skin Discovery',
    sub: 'nieuw? start hier',
    img: 'zaakvoerder-toestel',
    alt: 'Saskia naast een behandeltoestel in het instituut',
    from: 105,
    intro:
      'Leer jouw huid beter kennen. We combineren een huidanalyse met een aangepaste gelaatsverzorging en persoonlijk advies voor thuis. Ideaal als eerste behandeling.',
    prices: [{ rows: [discovery] }],
  },
  {
    slug: 'huidanalyse',
    why: { problem: 'Je smeert al jaren crèmes, maar ziet weinig verschil. Wat er onder het oppervlak gebeurt, zie je niet.', solution: 'Met de huidscanner bekijk ik pigment, rimpels, talg, vocht en zonschade tot in de diepere lagen.', benefit: 'Advies dat gebaseerd is op wat je huid echt nodig heeft, niet op trends of giswerk.' },
    name: 'Huidanalyse & advies',
    sub: 'je huid beter leren kennen',
    img: 'huidanalyse-scan',
    alt: 'Een gezicht in beeld op de huidscanner',
    from: 60,
    intro:
      'Met de huidscanner bekijk ik je huid tot in de diepere lagen. Daarna bespreken we samen wat ze nodig heeft, in het instituut en thuis.',
    tip: 'Nieuw bij Soleil? Kies dan de Skin Discovery: analyse, verzorging en advies in één afspraak.',
    prices: [
      {
        rows: [
          ['Advies moment', '30 min', 25, 'intakegesprek zonder analyse, gratis bij aankoop van 3 producten'],
          ['Skin analyse + advies', '60 min', 60],
          discovery,
        ],
      },
    ],
  },
  {
    slug: 'skin-relax',
    why: { titles: ['Voelt je huid wat minder stralend?', 'Wat ik doe', 'Wat jij eraan hebt'], problem: 'Je huid voelt droog of ziet er wat dof uit. En misschien merk je dat je zelf ook toe bent aan een rustmoment.', solution: 'Bij Instituut Soleil neem ik graag de tijd voor jou, met een verzorging die je huid laat stralen en jou even op adem laat komen.', benefit: 'Een frisse, verzorgde huid en een uur waarin je helemaal tot rust komt.' },
    name: 'Skin Relax',
    sub: 'ontspannen & revitaliseren',
    img: 'gelaatsverzorging',
    alt: 'Gezicht wordt gereinigd tijdens een gelaatsverzorging',
    from: 70,
    intro:
      'Een verzorgende gelaatsbehandeling waarbij ontspanning centraal staat. Een rustmoment voor jezelf, met aandacht voor een frisse, verzorgde huid.',
    tip: 'Weet je niet welke verzorging bij jou past? Kies dan eerst de Skin Discovery, dan overloop ik wat het best bij jou past.',
    prices: [
      {
        title: 'Skin Relax',
        rows: [
          ['Skin Relax 1u', '60 min', 70],
          ['Skin Relax 1u30', '90 min', 90],
          discovery,
        ],
      },
      {
        title: "Extra's",
        rows: [
          ['Extra oogcontour', '20 min', 50],
          ['Extra epilatie gelaat, per zone', '10 min', 10],
          ['Extra milia verwijderen', '10 min', 10],
        ],
      },
    ],
  },
  {
    slug: 'skin-expert',
    why: { problem: 'Fijne lijntjes, pigmentvlekken, littekens of acne die niet weggaan met gewone verzorging.', solution: 'Doelgerichte behandelingen met peeling, needling of Neo-Lift, in kuurvorm en met de juiste verzorging thuis.', benefit: 'Zichtbare huidverbetering stap voor stap, met technieken waarvan de werking bewezen is.' },
    name: 'Skin Expert',
    sub: 'herstellen & vernieuwen',
    img: 'led-lichttherapie',
    alt: 'Behandeltafel onder de LED-lamp in het instituut',
    from: 120,
    intro:
      'Een doelgerichte gelaatsbehandeling met intensieve producten en specifieke technieken of toestellen. Afgestemd op jouw huid, met aandacht voor herstel en huidverbetering.',
    tip: 'Werk je voor het eerst aan huidverbetering? Begin dan met een Skin Discovery.',
    /* Seasonal highlight in the side panel. Saskia changes this per season. */
    extra: { title: 'In de kijker deze herfst', text: 'Skin Expert Peel: je huid voelt weer zacht en glad aan. De ideale start van een kuur.', list: [] },
    prices: [],
    parts: [
      {
        id: 'peel',
        name: 'Peel',
        img: 'peeling',
        alt: 'Een peeling wordt met een penseel aangebracht',
        text: 'Een peeling maakt de bovenste laag dode huidcellen los, zodat je huid weer zacht en glad aanvoelt en haar frisheid terugkrijgt. Altijd aangepast aan je huidtype. In kuurvorm verfijnt ze de huidstructuur en vermindert ze lijntjes, pigmentvlekken, littekens en acne. Een ideaal begin voor anti-aging en resultaatgerichte behandelingen.',
        prices: [
          {
            rows: [
              ['Skin Expert Peel', '60 min', 125, 'masker + LED'],
              ['Skin Expert Peel Deluxe', '90 min', 150, 'met relaxmassage'],
            ],
          },
        ],
      },
      {
        id: 'needling',
        name: 'Needling',
        img: 'microneedling',
        alt: 'Microneedling-pen tijdens een behandeling',
        text: 'Met fijne naaldjes stimuleer ik het natuurlijke huidherstel en de aanmaak van collageen. Dit helpt fijne lijntjes en acnelittekens te verzachten en de huidstructuur te verfijnen.',
        prices: [
          {
            rows: [
              ['Skin Expert Needling', '75 min', 125, 'needling + masker + LED-lichttherapie'],
              ['Skin Expert Needling + peel', '90 min', 185, 'needling + peel + LED-masker'],
            ],
          },
        ],
      },
      {
        id: 'neo-lift',
        name: 'Neo-Lift',
        img: 'neo-lift',
        alt: 'Neo-Lift behandeling van het gelaat',
        text: 'Radiofrequentie en elektrostimulatie samen: voor meer collageen en elastine en stevigere contouren, zonder naalden. Voor een mooi resultaat is een kuur aan te raden.',
        prices: [
          {
            rows: [
              ['Neo-Lift gelaat', '45 min', 120],
              ['Neo-Lift gelaat + hals', '90 min', 180],
              ['Neo-Lift + peel', '90 min', 185],
            ],
          },
        ],
      },
    ],
  },
];

/* ---------- Ontspanning: one page each, same template ---------- */
export type Arrangement = { name: string; duration: string; solo: number; duo?: number; items: string[] };
export const arrangements: Arrangement[] = [
  { name: 'Mini-arrangement', duration: '1u15', solo: 100, items: ['Skin Relax basisverzorging', 'Epilatie wenkbrauwen', 'Korte rugmassage'] },
  { name: 'Feeling', duration: '1u30', solo: 115, duo: 220, items: ['Relaxerende rugmassage', 'Warme rugpakking', 'Mini gelaatsverzorging', 'Epilatie wenkbrauwen'] },
  { name: 'Soleil', duration: '2u', solo: 165, items: ['Relaxerende rugmassage', 'Skin Relax standaardverzorging', 'Epilatie wenkbrauwen', 'Manicure met hydratatie'] },
  { name: 'Relax', duration: '2u tot 2u30', solo: 150, duo: 290, items: ['Lichaamspeeling', 'Warme lichaamspakking + douche', 'Mini gelaatsverzorging'] },
  { name: 'Anti-stress', duration: '2u30', solo: 200, items: ['Skin Relax standaardverzorging', 'Epilatie wenkbrauwen', 'Relaxerende rugmassage', 'Warme rugpakking', 'Manicure met hydratatie', 'Korte voetmassage'] },
];
const arrangementRows: Row[] = arrangements.flatMap((a) => {
  const rows: Row[] = [[a.name, a.duration, a.solo, a.items.join(' · ')]];
  if (a.duo) rows.push([`${a.name} duo`, a.duration, a.duo, 'met 2 zalig genieten']);
  return rows;
});

export const laser = {
  name: 'Laser ontharing',
  text: 'Elke ochtend scheren, ingegroeide haartjes en na twee dagen alweer stoppels? Tijd voor een gladde huid die ook glad blijft. Met de LightSheer® diodelaser, een medische diodelaser met 4 golflengtes, pak je ongewenst haar aan, snel en vrijwel pijnloos.',
  /* Days the specialist (Cathy, Haarlaser Team) is in the institute. */
  dates: ['Donderdag 29 oktober 2026', 'Dinsdag 10 november 2026', 'Donderdag 10 december 2026', 'Dinsdag 22 december 2026', 'Donderdag 21 januari 2027', 'Dinsdag 2 februari 2027', 'Donderdag 4 maart 2027', 'Dinsdag 16 maart 2027', 'Donderdag 15 april 2027', 'Dinsdag 27 april 2027', 'Donderdag 27 mei 2027', 'Dinsdag 8 juni 2027', 'Donderdag 8 juli 2027', 'Dinsdag 20 juli 2027'],
  url: 'https://www.haarlaser.team',
  label: 'Prijzen op haarlaser.team',
};

export const relax: Treatment[] = [
  {
    slug: 'arrangementen',
    why: { problem: 'Je hebt het druk en vindt zelden tijd voor jezelf.', solution: 'Van kop tot teen verzorgd worden, alleen of met twee in de duo-cabine, en daarna nagenieten in de relaxruimte.', benefit: 'Even helemaal weg, zonder iets te moeten. Ook een mooi cadeau.' },
    name: 'Arrangementen',
    sub: 'solo of met twee',
    img: 'duo-cabine',
    alt: 'Twee personen krijgen tegelijk een gezichtsmassage in de duo-cabine',
    from: 100,
    kicker: "Self-care is not a luxury, it's a power move",
    intro: 'Even helemaal niets moeten. Laat je onderdompelen in pure verwennerij, met zachte aanrakingen, heerlijke geuren en luxe producten. Laat de drukte los en kom opnieuw tot rust.',
    prices: [{ rows: arrangementRows }],
  },
  {
    slug: 'lichaam',
    why: { problem: 'Spanning in je rug en schouders, of een huid die ruw en droog aanvoelt.', solution: 'Massage, lichaamspeeling of een warme pakking, rustig en met aandacht.', benefit: 'Een ontspannen lichaam en een zachte, verzorgde huid.' },
    name: 'Lichaamsbehandelingen',
    sub: 'massage, peeling & pakking',
    img: 'massage-lichaam',
    alt: 'Rugmassage',
    from: 35,
    intro: 'Laat de spanning uit je lichaam wegvloeien met een massage, een peeling of een warme pakking.',
    prices: [
      {
        rows: [
          ['Massage', '25 min', 35],
          ['Lichaamspeeling met douche', '30 min', 40],
          ['Lichaamspakking + douche', '40 min', 40],
          ['Lichaamspeeling + pakking', '60 min', 80],
        ],
      },
    ],
  },
  {
    slug: 'handen-voeten',
    why: { problem: 'Je handen en voeten krijgen in de drukte het minst aandacht.', solution: 'Manicure en pedicure met gellak, die tijdens een combo-verzorging ook een beetje extra aandacht krijgen.', benefit: 'Verzorgde handen en voeten, en alles in één afspraak.' },
    name: 'Handen & voeten',
    sub: 'manicure, gellak & pedicure',
    img: 'handen-voeten',
    alt: 'Verzorging van de handen',
    from: 25,
    intro: 'Een extra moment van zachtheid voor je handen en voeten. Deze verzorgingen zijn vooral te boeken in combinatie met een Skin Relax-verzorging en maken je ontspanningsmoment compleet. Geniet van een verzorgd gevoel en kom helemaal tot rust.',
    note: 'Pedicure bied ik uitsluitend aan als onderdeel van een combobehandeling, samen met de Skin Relax-verzorging. Zo wordt het één samenhangend verwenmoment, met rust en volle aandacht.',
    prices: [
      {
        title: 'Handen & voeten',
        rows: [
          ['Manicure / lakken', '30 min', '€25 / €35'],
          ['Gellak verwijderen + verzorging', '20 min', 25],
          ['Gellak en pedicure', '60 min', 65],
          ['Kleine pedicure', '', 30, '* enkel te boeken in combinatie met een Skin Relax'],
          ['Grote pedicure', '', 45, '* enkel te boeken in combinatie met een Skin Relax'],
        ],
      },
      {
        title: 'Combo met Skin Relax',
        rows: [
          ['Skin Relax basis + manicure', '', 'vanaf €95'],
          ['Skin Relax basis + pedicure', '', 'vanaf €100'],
          ['Skin Relax standaard + manicure', '', 'vanaf €120'],
          ['Skin Relax standaard + pedicure', '', 'vanaf €125'],
          ['Skin Relax standaard + manicure + pedicure', '', 150],
        ],
      },
    ],
  },
  {
    slug: 'epilaties',
    why: { problem: 'Ongewenste haartjes die je liever niet zelf weghaalt.', solution: 'Ontharing met hars, nauwkeurig en hygiënisch, voor gelaat en lichaam.', benefit: 'Een gladde huid voor langere tijd, zonder gedoe thuis.' },
    name: 'Epilaties',
    sub: 'ontharing met hars',
    img: 'ontharing',
    alt: 'Ontharing met hars',
    from: 15,
    intro: 'Ontharing met hars, voor gelaat en lichaam. Definitief ontharen kan met de laser.',
    prices: [
      {
        rows: [
          ['Onderbenen', '30 min', 25],
          ['Onderbenen + achterzijde bil', '30 min', 38],
          ['Volledige benen zonder bikini', '60 min', 48],
          ['Bikini sliprand', '15 min', 15],
          ['Bikini brazilian', '30 min', 40],
          ['Oksels', '10 min', 15],
          ['Wenkbrauwen', '15 min', 15],
          ['Bovenlip, kin of wang, per zone', '10 min', 15],
          ['Volledig gelaat', '25 min', 35],
        ],
      },
    ],
  },
  {
    slug: 'make-up',
    name: 'Make-up',
    sub: 'minerale make-up',
    img: 'make-up',
    alt: 'Make-up wordt aangebracht met een kwast',
    from: 45,
    intro: 'Minerale make-up van Jane Iredale.',
    prices: [],
    page: {
      title: 'Skincare make-up',
      questions: [
        'Wil jij een mooie natuurlijke uitstraling?',
        'Geen gedoe meer met poriën verstoppende producten?',
        'En daarbovenop make-up die effectief huidverbeterend werkt?',
      ],
      cta: 'Boek dan jouw persoonlijke minerale make-up consultatie!',
      story: {
        label: 'Jane iredale minerale make up.',
        title: 'wist je dat...jane iredale veel meer is dan een klassiek mineraal of clean beauty make-up merk?',
        text: [
          'Natuurlijk zijn de producten vrij van schadelijke stoffen, dat is de basis, maar bij jane iredale gaat het een flinke stap verder. De formules zitten boordevol actieve ingrediënten zoals antioxidanten en botanische extracten die je huid écht verbeteren. Ze hydrateren, beschermen tegen UV- en blauw licht en ondersteunen de gezondheid van de huid.',
          'Geen plamuurlaagje dus, maar verzorgende make-up die je huid laat ademen, kalmeert én versterkt.',
        ],
        img: 'producten-jane-iredale',
        alt: 'Make-up producten van Jane Iredale',
      },
      services: [
        { name: 'Foundation test', text: 'Ben je op zoek naar de perfecte foundation van jane iredale en heb je deze nog niet eerder gebruikt? Maar je wilt geen volledige make-up look? Boek en vind jouw ideale match.', dur: '25 min', price: 'gratis' },
        { name: 'Kort make-up advies', tag: 'SNEL & SPECIFIEK', text: 'Heb je een kleine vraag of wil je 1 specifiek item uittesten (bv. Foundationkleur afstemmen, blush, lipstick, oogschaduw)? Dan is dit mini-moment ideaal.', dur: '20 min', price: 'gratis' },
        { name: 'Make-up', tag: 'make-up voor jouw mooiste momenten', text: 'Een frisse, verzorgde look waarin jij straalt, zonder dat het te veel wordt. Zo zie je er fantastisch uit en kan je vol zelfvertrouwen schitteren', dur: '30 min', price: 45 },
        { name: 'Make up cursus', text: 'Je leert stap voor stap alles zelf aanbrengen, met volop ruimte voor jouw vragen. Ideaal om je routine te verfijnen, iets bij te leren of gewoon te genieten van een moment voor jezelf.', dur: '70 min', price: 70 },
      ],
    },
  },
  {
    slug: 'laser',
    why: { titles: ['Herken je dit?', 'Werkwijze', 'Wat jij eraan hebt'], problem: ['Ongewenste haargroei', 'Ingegroeide haren', 'Irritatie na het scheren'], solution: 'Definitieve ontharing met de diodelaser, door een erkende laserspecialist van Haarlaser Team, in het instituut.', benefit: 'Blijvend resultaat, met een gratis test- en uitlegsessie vooraf.' },
    name: laser.name,
    title: 'Medische laserontharing',
    extra: { title: 'Cathy van Haarlaser Team is aanwezig op', list: laser.dates },
    sub: 'definitief ontharen',
    img: 'ontharing',
    alt: 'Ontharing',
    from: 0,
    intro: laser.text,
    prices: [],
    link: { href: laser.url, label: laser.label },
  },
];

relax.forEach((t) => { if (t.page) t.prices = [{ rows: t.page.services.map((x): Row => [x.name, x.dur, x.price]) }]; });
skin.forEach((t) => (t.group = 'Huidverbetering'));
relax.forEach((t) => (t.group = 'Ontspanning'));
/* Every treatment, in menu order. One page each at /behandelingen/<slug>/. */
export const treatments: Treatment[] = [...skin, ...relax];
export const href = (t: Treatment) => `/behandelingen/${t.slug}/`;
export const groups = [
  { title: 'Huidverbetering' as Group, items: skin },
  { title: 'Ontspanning' as Group, items: relax },
];

export const brands = [
  { name: 'Environ', img: 'producten-environ', line: 'Huidverzorging op basis van vitamine A, met jarenlang onderzoek achter elk product.' },
  { name: 'ISOV', img: '', line: 'Koreaanse huidverzorging die wetenschap en actieve ingrediënten combineert voor doelgerichte huidverbetering.' },
  { name: 'Exuviance', img: 'producten-exuviance', line: 'Zachte, effectieve zuren voor een gladdere, egalere huid.' },
  { name: 'Jane Iredale', img: 'producten-jane-iredale', line: 'Minerale make-up die je huid verzorgt terwijl je ze draagt.' },
];

/* Why Soleil: the homepage USPs */
export const usps = [
  { icon: 'medal', title: '27 jaar ervaring', text: 'Al 27 jaar huidexpert in Beervelde, met elk jaar bijscholing.' },
  { icon: 'scan', title: 'Eerst analyse', text: 'Met de huidscanner zie ik wat je huid echt nodig heeft, voor we beginnen.' },
  { icon: 'chat-circle', title: 'Eerlijk advies', text: 'Geen snelle trends of loze beloftes, wel een aanpak die past bij jouw huid.' },
  { icon: 'house-line', title: 'Begeleiding voor thuis', text: '75% van het resultaat komt van je verzorging thuis. Die stem ik mee af, en ik blijf je begeleiden bij je thuisroutine.' },
];

/* Reviews: real client reviews (Google and the booking system), chosen from the documents
   the client sent. Quoted verbatim, names shortened to first name + initial.
   A review with placeholder: true shows the "voorbeeldreviews" label again. */
export const reviews: { text: string; name: string; place: string; placeholder?: boolean }[] = [
  { text: 'Professioneel, vriendelijk en een echte expert in huidverzorging/verbetering. Saskia kent overduidelijk haar vak en heeft een heel aangename aanpak. Een madam met passie en al stiekem aan het aftellen naar de volgende behandeling.', name: 'Aurélie V.', place: 'Huidverbetering' },
  { text: 'Heel lieve dame, hartelijke ontvangst. Heel proper en stijlvol salon. Gekregen waar we voor gekomen waren, een fantastische gezichtsverzorging en massage voor ons twee. Zeker voor herhaling vatbaar.', name: 'Ann G.', place: 'Verwennen met twee' },
  { text: 'Gelaatsverzorging en maquillage: een rustig moment op een fijne plek: gerealiseerd door een lieve en vakbekwame dame!', name: 'Hilde V.', place: 'Gelaatsverzorging & make-up' },
];
export const reviewsArePlaceholders = reviews.some((r) => r.placeholder);

/* Footer list, in the client's own wording and order */
export const footerTreatments = [
  { href: '/behandelingen/skin-relax/', label: 'Skin Relax Facials' },
  { href: '/behandelingen/skin-expert/', label: 'Skin Expert Facials' },
  { href: '/behandelingen/epilaties/', label: 'Epilaties' },
  { href: '/behandelingen/lichaam/', label: 'Lichaamsbehandelingen' },
  { href: '/behandelingen/arrangementen/', label: 'Arrangementen' },
  { href: '/behandelingen/laser/', label: 'Definitieve ontharing met laser' },
];
