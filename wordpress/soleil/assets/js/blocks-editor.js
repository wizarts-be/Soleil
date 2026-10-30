/* Instituut Soleil: eigen blokken in de editor.
   De weergave en de attributen komen van de server (inc/blocks.php); hier staat enkel de editorkant. */
(function (wp) {
  "use strict";

  var el = wp.element.createElement;
  var be = wp.blockEditor;
  var c = wp.components;
  var ServerSideRender = wp.serverSideRender;
  var admin = function () { return (window.soleilEditor && window.soleilEditor.adminUrl) || "/wp-admin/"; };

  /* ---------- Blokken die de server opbouwt ---------- */
  var blocks = {
    "site-header": { title: "Header", icon: "menu", description: "Logo, menu en het paneel met behandelingen. Het menu volgt vanzelf de behandelingen." },
    "site-footer": { title: "Footer", icon: "editor-insertmore", description: "Openingsuren, behandelingen en contact. Gegevens pas je aan onder Soleil in het menu links." },
    "nieuwsbrief": { title: "Nieuwsbrief", icon: "email", description: "Kaartje rechtsonder dat na een tijdje verschijnt." },
    "openingsuren": {
      title: "Openingsuren", icon: "clock", inserter: true,
      description: "De openingsuren van het instituut. Aanpassen doe je onder Soleil > Openingsuren.",
      manage: ["Soleil > Openingsuren", "admin.php?page=soleil&tab=uren"]
    },
    "behandeling-switch": { title: "Behandeling: alle behandelingen", icon: "ellipsis", description: "De balk met alle behandelingen bovenaan." },
    "behandeling": { title: "Behandeling: pagina", icon: "heart", description: "De pagina van een behandeling, opgebouwd uit de behandelingkaart." },
    "behandeling-pager": { title: "Behandeling: vorige en volgende", icon: "leftright", description: "Links naar de vorige en volgende behandeling." },
    "behandelingen-lijst": { title: "Lijst met behandelingen", icon: "list-view", description: "Alle behandelingen per groep.", manage: ["Behandelingen", "edit.php?post_type=behandeling"] },
    "tarieven-chips": { title: "Tarieven: snelkoppelingen", icon: "tag", description: "Knopjes naar elke behandeling op de pagina.", manage: ["Behandelingen", "edit.php?post_type=behandeling"] },
    "tarieven": { title: "Tarieven", icon: "money-alt", description: "De prijzen van alle behandelingen.", manage: ["Behandelingen", "edit.php?post_type=behandeling"] },
    "in-de-kijker": { title: "Verzorging in de kijker", icon: "star-filled", description: "De verzorging van het seizoen.", manage: ["Soleil > In de kijker", "admin.php?page=soleil&tab=kijker"] },
    "reviews": { title: "Reviews", icon: "format-quote", description: "Wat klanten zeggen.", manage: ["Reviews", "edit.php?post_type=review"] },
    "voorbeeld-noot": { title: "Melding voorbeeldinhoud", icon: "info", description: "Verschijnt enkel zolang er iets als voorbeeld gemarkeerd is." },
    "merken": { title: "Merken", icon: "tag", description: "Merken waar je mee werkt.", manage: ["Merken", "edit.php?post_type=merk"] },
    "producten": { title: "Nieuwe producten", icon: "products", description: "Producten op de pagina Nieuw.", manage: ["Producten", "edit.php?post_type=product"] },
    "activiteiten": { title: "Nieuwe data", icon: "calendar-alt", description: "Activiteiten met hun komende data.", manage: ["Activiteiten", "edit.php?post_type=activiteit"] },
    "contact-kaart": { title: "Contactgegevens", icon: "location", description: "Adres, telefoon en e-mail.", manage: ["Soleil > Praktisch", "admin.php?page=soleil"] },
    "contactformulier": { title: "Contactformulier", icon: "email-alt", description: "Het formulier opent het mailprogramma van de bezoeker.", manage: ["Soleil > Praktisch", "admin.php?page=soleil"] },
    "journaal": { title: "Alle artikels", icon: "excerpt-view", description: "Alle artikels uit het journaal.", manage: ["Journaal", "edit.php"] },
    "pagina-inhoud": { title: "Inhoud van een pagina", icon: "admin-page", description: "Toont de inhoud van een vaste pagina in een template." },
    "icoon": { title: "Icoon", icon: "star-empty", description: "Een icoon van de site." }
  };

  Object.keys(blocks).forEach(function (name) {
    var def = blocks[name];
    var full = "soleil/" + name;
    wp.blocks.registerBlockType(full, {
      apiVersion: 3,
      title: def.title,
      description: def.description,
      icon: def.icon,
      category: "soleil",
      supports: { html: false, inserter: !!def.inserter, className: false, customClassName: false },
      edit: function (props) {
        var blockProps = be.useBlockProps();
        var manage = def.manage ? el("div", { className: "soleil-manage" },
          el("span", null, "Dit blok vult zich vanzelf uit ", el("strong", null, def.manage[0]), ". Aanpassen doe je daar, niet hier."),
          el("a", { className: "soleil-manage__btn", target: "_top", href: admin() + def.manage[1] }, "Openen")
        ) : null;
        return el("div", blockProps,
          props.isSelected ? manage : null,
          el(c.Disabled, null, el(ServerSideRender, { block: full, attributes: props.attributes }))
        );
      },
      save: function () { return null; }
    });
  });

  /* ---------- Kaarten op de home: 3 behandelingen + een eigen kaart ---------- */
  function HomeCardsEdit(props) {
    var blockProps = be.useBlockProps();
    var items = props.attributes.items || [];
    var custom = items.map(function (item, i) { return [item, i]; }).filter(function (x) { return !x[0].behandeling; });
    var setItem = function (i, key, value) { props.setAttributes({ items: put(items, i, merge(items[i], key, value)) }); };
    return el("div", blockProps,
      props.isSelected ? el("div", { className: "soleil-manage" },
        el("span", null, "De eerste kaarten volgen vanzelf de ", el("strong", null, "Behandelingen"), " (naam, korte omschrijving en foto). De eigen kaart pas je hieronder aan."),
        el("a", { className: "soleil-manage__btn", target: "_top", href: admin() + "edit.php?post_type=behandeling" }, "Behandelingen")
      ) : null,
      props.isSelected ? custom.map(function (x) {
        var item = x[0], i = x[1];
        return el("div", { key: i, className: "soleil-card soleil-card--inline" },
          el("div", { className: "soleil-card__section soleil-card__grid" },
            el(Photo, { id: item.foto, removable: false, onChange: function (v) { setItem(i, "foto", v); } }),
            el("div", { className: "soleil-card__fields" },
              text("Naam", item.naam, function (v) { setItem(i, "naam", v); }),
              text("Korte omschrijving", item.sub, function (v) { setItem(i, "sub", v); }),
              text("Link", item.href, function (v) { setItem(i, "href", v); }, "Bv. /behandelingen/#ontspanning.")
            )
          )
        );
      }) : null,
      el(c.Disabled, null, el(ServerSideRender, { block: "soleil/home-kaarten", attributes: props.attributes }))
    );
  }
  wp.blocks.registerBlockType("soleil/home-kaarten", {
    apiVersion: 3, title: "Behandelingen: kaarten", icon: "grid-view", category: "soleil",
    description: "Vier kaarten onder Behandelingen op de homepagina.",
    supports: { html: false, inserter: false, className: false, customClassName: false },
    edit: HomeCardsEdit, save: function () { return null; }
  });

  /* ---------- Foto in een vaste pagina: vervangen en beschrijven in het canvas ---------- */
  function PhotoEdit(props) {
    var a = props.attributes;
    var media = useSelect(function (select) { return a.id ? select("core").getEntityRecord("postType", "attachment", a.id) : null; }, [a.id]);
    var img = media ? el("img", { src: media.source_url, alt: a.alt }) : el("span", { className: "soleil-photo__empty" }, "Nog geen foto");
    var blockProps = be.useBlockProps({ className: a.tag === "img" ? "soleil-photo soleil-photo--bare" : "soleil-photo " + (a.className || "") });
    var tools = props.isSelected ? el("div", { className: "soleil-photo__tools" },
      el(be.MediaUploadCheck, null, el(be.MediaUpload, {
        onSelect: function (m) { props.setAttributes({ id: m.id }); }, allowedTypes: ["image"], value: a.id,
        render: function (o) { return el(c.Button, { variant: "primary", onClick: o.open }, a.id ? "Foto vervangen" : "Foto kiezen"); }
      })),
      el(c.TextControl, Object.assign({ label: "Beschrijving van de foto", hideLabelFromVision: false, value: a.alt, placeholder: "Leeg = decoratief", onChange: function (v) { props.setAttributes({ alt: v }); } }, ctl))
    ) : null;
    return el(a.tag === "span" ? "span" : (a.tag === "img" ? "div" : "figure"), blockProps, img, tools);
  }
  wp.blocks.registerBlockType("soleil/foto", {
    apiVersion: 3, title: "Foto", icon: "format-image", category: "soleil",
    description: "Foto op een vaste plaats in de pagina. Klik erop om ze te vervangen.",
    supports: { html: false, inserter: false, className: false, customClassName: false },
    edit: PhotoEdit, save: function () { return null; }
  });

  /* ---------- Extra HTML-attributen op tekst- en knopblokken (zie soleil_core_markup in inc/blocks.php) ---------- */
  var CORE = ["core/paragraph", "core/heading", "core/list", "core/list-item", "core/buttons", "core/button"];
  wp.hooks.addFilter("blocks.registerBlockType", "soleil/html-attrs", function (settings, name) {
    if (CORE.indexOf(name) === -1) return settings;
    return Object.assign({}, settings, { attributes: Object.assign({}, settings.attributes, { htmlAttrs: { type: "object", default: {} } }) });
  });
  // In de editor dezelfde marges als op de site (bv. style="margin-top:28px").
  wp.hooks.addFilter("editor.BlockListBlock", "soleil/html-attrs-style", wp.compose.createHigherOrderComponent(function (BlockListBlock) {
    return function (props) {
      var h = props.attributes && props.attributes.htmlAttrs;
      if (CORE.indexOf(props.name) === -1 || !h || !h.style) return el(BlockListBlock, props);
      var style = Object.assign({}, styleObject(h.style), (props.wrapperProps && props.wrapperProps.style) || {});
      return el(BlockListBlock, Object.assign({}, props, { wrapperProps: Object.assign({}, props.wrapperProps, { style: style }) }));
    };
  }, "withSoleilHtmlAttrs"));

  /* ---------- Container: zelfde tag en klassen als op de site ---------- */
  function styleObject(css) {
    var out = {};
    (css || "").split(";").forEach(function (rule) {
      var i = rule.indexOf(":");
      if (i < 1) return;
      var prop = rule.slice(0, i).trim();
      var value = rule.slice(i + 1).trim();
      out[prop.indexOf("--") === 0 ? prop : prop.replace(/-([a-z])/g, function (m, l) { return l.toUpperCase(); })] = value;
    });
    return out;
  }

  wp.blocks.registerBlockType("soleil/el", {
    apiVersion: 3,
    title: "Vak",
    description: "Onderdeel van de vaste opbouw van de pagina.",
    icon: "screenoptions",
    category: "soleil",
    supports: { html: false, inserter: false, className: false, customClassName: false, reusable: false },
    edit: function (props) {
      var a = props.attributes;
      var attrs = a.attrs || {};
      // Een link als vak wordt in de editor een gewone div: anders kan je de tekst erin niet aanklikken.
      var tag = a.tag === "a" ? "div" : (a.tag || "div");
      var blockProps = be.useBlockProps({
        className: a.className || undefined,
        id: a.anchor || undefined,
        role: attrs.role,
        style: styleObject(attrs.style)
      });
      var inner = be.useInnerBlocksProps(blockProps, { renderAppender: false, templateLock: a.templateLock });
      return el(tag === "pagina" ? "div" : tag, inner);
    },
    save: function () { return el(be.InnerBlocks.Content); }
  });

  /* ==========================================================================
     Invulkaarten: alle velden van een behandeling of artikel, in het canvas
     ========================================================================== */
  var useEntityProp = wp.coreData.useEntityProp;
  var useSelect = wp.data.useSelect;
  var Fragment = wp.element.Fragment;
  var ctl = { __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };

  function useMeta(type) {
    var meta = useEntityProp("postType", type, "meta");
    var m = meta[0] || {};
    var set = function (key, value) { var o = Object.assign({}, m); o[key] = value; meta[1](o); };
    var json = function (key, fallback) {
      try { var v = m[key] ? JSON.parse(m[key]) : null; return v == null ? fallback : v; } catch (e) { return fallback; }
    };
    var setJson = function (key, value) { set(key, value == null ? "" : JSON.stringify(value)); };
    return { m: m, set: set, json: json, setJson: setJson };
  }

  // Lijsten: vervangen, verwijderen, verschuiven zonder het origineel te wijzigen.
  function put(list, i, value) { var o = list.slice(); o[i] = value; return o; }
  function drop(list, i) { var o = list.slice(); o.splice(i, 1); return o; }
  function move(list, i, d) { var o = list.slice(); var j = i + d; if (j < 0 || j >= o.length) return o; var x = o[i]; o[i] = o[j]; o[j] = x; return o; }
  function merge(obj, key, value) { var o = Object.assign({}, obj); o[key] = value; return o; }

  function text(label, value, onChange, help, placeholder) {
    return el(c.TextControl, Object.assign({ label: label, value: value == null ? "" : String(value), help: help, placeholder: placeholder, onChange: onChange }, ctl));
  }
  function area(label, value, onChange, help, rows) {
    return el(c.TextareaControl, { label: label, value: value || "", help: help, rows: rows || 3, onChange: onChange, __nextHasNoMarginBottom: true });
  }
  // Eén item per regel.
  function lines(label, list, onChange, help) {
    return area(label, (list || []).join("\n"), function (v) { onChange(v.split("\n").map(function (x) { return x.trim(); }).filter(Boolean)); }, help, Math.max(3, (list || []).length + 1));
  }
  function section(title, help, children, extra) {
    return el("section", { className: "soleil-card__section" + (extra ? " " + extra : "") },
      el("h3", { className: "soleil-card__title" }, title),
      help ? el("p", { className: "soleil-card__help" }, help) : null,
      el.apply(null, ["div", { className: "soleil-card__fields" }].concat(children))
    );
  }
  // Tekstknoppen: dashicons laden niet in het editorcanvas.
  function tools(list, i, onChange, label) {
    return el("div", { className: "soleil-card__tools" },
      el(c.Button, { size: "small", variant: "tertiary", label: "Omhoog", showTooltip: true, disabled: i === 0, onClick: function () { onChange(move(list, i, -1)); } }, "↑"),
      el(c.Button, { size: "small", variant: "tertiary", label: "Omlaag", showTooltip: true, disabled: i === list.length - 1, onClick: function () { onChange(move(list, i, 1)); } }, "↓"),
      el(c.Button, { size: "small", variant: "tertiary", label: label || "Verwijderen", showTooltip: true, isDestructive: true, onClick: function () { onChange(drop(list, i)); } }, "✕")
    );
  }

  function Photo(props) {
    var media = useSelect(function (select) { return props.id ? select("core").getEntityRecord("postType", "attachment", props.id) : null; }, [props.id]);
    var url = media && (media.media_details && media.media_details.sizes && media.media_details.sizes.medium_large ? media.media_details.sizes.medium_large.source_url : media.source_url);
    if (!props.id) {
      return el(be.MediaPlaceholder, {
        icon: "format-image", labels: { title: props.label || "Foto", instructions: props.help || "Sleep een foto hierheen, upload er een of kies uit de mediabibliotheek." },
        accept: "image/*", allowedTypes: ["image"], onSelect: function (m) { props.onChange(m.id); }
      });
    }
    return el("div", { className: "soleil-card__photo" },
      url ? el("img", { src: url, alt: "" }) : el(c.Spinner),
      el("div", { className: "soleil-card__photo-actions" },
        el(be.MediaUpload, {
          onSelect: function (m) { props.onChange(m.id); }, allowedTypes: ["image"], value: props.id,
          render: function (o) { return el(c.Button, { variant: "secondary", onClick: o.open }, "Andere foto"); }
        }),
        props.removable === false ? null : el(c.Button, { variant: "tertiary", isDestructive: true, onClick: function () { props.onChange(0); } }, "Verwijderen")
      )
    );
  }

  function Pills(props) {
    return el("div", { className: "soleil-card__field" },
      el("span", { className: "soleil-card__label" }, props.label),
      el("div", { className: "soleil-card__pills", role: "group", "aria-label": props.label },
        props.terms ? props.terms.map(function (t) {
          var on = props.value.indexOf(t.id) !== -1;
          return el("button", { key: t.id, type: "button", className: "soleil-card__pill" + (on ? " is-on" : ""), "aria-pressed": on, onClick: function () { props.onChange([t.id]); } }, t.name);
        }) : el(c.Spinner)
      ),
      props.warn && !props.value.length ? el("p", { className: "soleil-card__warn" }, props.warn) : null
    );
  }

  /* ---------- Prijstabellen: groepen met rijen ---------- */
  function Prices(props) {
    var groups = props.value || [];
    var set = props.onChange;
    return el("div", { className: "soleil-prices" },
      groups.map(function (g, gi) {
        var rows = g.rijen || [];
        var setGroup = function (key, value) { set(put(groups, gi, merge(g, key, value))); };
        return el("div", { key: gi, className: "soleil-prices__group" },
          el("div", { className: "soleil-prices__head" },
            text("Titel boven de tabel", g.titel, function (v) { setGroup("titel", v); }, groups.length > 1 ? null : "Mag leeg blijven."),
            tools(groups, gi, set, "Tabel verwijderen")
          ),
          el("div", { className: "soleil-prices__rows" },
            el("div", { className: "soleil-prices__row soleil-prices__row--head", "aria-hidden": true },
              el("span", null, "Naam"), el("span", null, "Duur"), el("span", null, "Prijs"), el("span", null, "Toelichting (klein onder de naam)"), el("span")
            ),
            rows.map(function (r, ri) {
              var setRow = function (key) { return function (v) { setGroup("rijen", put(rows, ri, merge(r, key, v))); }; };
              return el("div", { key: ri, className: "soleil-prices__row" },
                el(c.TextControl, Object.assign({ hideLabelFromVision: true, label: "Naam", value: r.naam || "", onChange: setRow("naam") }, ctl)),
                el(c.TextControl, Object.assign({ hideLabelFromVision: true, label: "Duur", value: r.duur || "", placeholder: "60 min", onChange: setRow("duur") }, ctl)),
                el(c.TextControl, Object.assign({ hideLabelFromVision: true, label: "Prijs", value: r.prijs == null ? "" : String(r.prijs), placeholder: "70", onChange: setRow("prijs") }, ctl)),
                el(c.TextControl, Object.assign({ hideLabelFromVision: true, label: "Toelichting", value: r.toelichting || "", onChange: setRow("toelichting") }, ctl)),
                tools(rows, ri, function (v) { setGroup("rijen", v); }, "Rij verwijderen")
              );
            }),
            el(c.Button, { variant: "secondary", onClick: function () { setGroup("rijen", rows.concat([{ naam: "", duur: "", prijs: "" }])); } }, "Rij toevoegen")
          ),
          text("Noot onder de tabel", g.noot, function (v) { setGroup("noot", v); }, "Mag leeg blijven.")
        );
      }),
      el(c.Button, { variant: "secondary", onClick: function () { set(groups.concat([{ titel: "", rijen: [{ naam: "", duur: "", prijs: "" }] }])); } }, "Tabel toevoegen"),
      el("p", { className: "soleil-card__help" }, "Prijs: enkel een getal (70) krijgt een euroteken. Tekst zoals \"€25 / €35\" of \"vanaf €95\" staat er zoals je ze typt.")
    );
  }

  /* ---------- Behandelingkaart ---------- */
  var WHY_TITLES = ["Herken je dit?", "Wat ik doe", "Wat jij eraan hebt"];

  function TreatmentSetup() {
    var blockProps = be.useBlockProps({ className: "soleil-card" });
    var meta = useMeta("behandeling");
    var m = meta.m, set = meta.set, json = meta.json, setJson = meta.setJson;
    var featured = useEntityProp("postType", "behandeling", "featured_media");
    var groep = useEntityProp("postType", "behandeling", "groep");
    var data = useSelect(function (select) {
      var core = select("core");
      return {
        groepen: core.getEntityRecords("taxonomy", "groep", { per_page: -1, orderby: "id", order: "asc" }),
        activiteiten: core.getEntityRecords("postType", "activiteit", { per_page: -1, orderby: "menu_order", order: "asc", status: "publish" })
      };
    }, []);

    var makeup = json("makeup", null);
    var why = json("waarom", null);
    var extra = json("extra", null);
    var parts = json("delen", []);
    var prices = json("prijzen", []);

    var head = section("Bovenaan de pagina", "Naam van de behandeling: het veld Titel hierboven.", [
      el(Pills, { key: "g", label: "Groep in het menu", terms: data.groepen, value: groep[0] || [], onChange: groep[1], warn: "Kies een groep, anders staat de behandeling niet in het menu." }),
      el("div", { key: "p", className: "soleil-card__grid" },
        el("div", null, el(Photo, { id: featured[0], onChange: featured[1], help: "Grote foto bovenaan de pagina, ook op de kaarten en in de lijst met behandelingen." })),
        el("div", { className: "soleil-card__fields" },
          text("Beschrijving van de foto", m.foto_alt, function (v) { set("foto_alt", v); }, "Voor wie de foto niet ziet."),
          text("Korte omschrijving", m.sub, function (v) { set("sub", v); }, "Onder de naam op de kaarten, in de lijst en bij de tarieven, bv. \"ontspannen & revitaliseren\"."),
          text("Vanaf-prijs", m.vanaf || "", function (v) { set("vanaf", Number(v) || 0); }, "In de lijst met behandelingen. 0 = \"meer info\".")
        )
      ),
      makeup ? null : text("Kleine regel boven de titel", m.kicker, function (v) { set("kicker", v); }, "Leeg = de korte omschrijving."),
      makeup ? null : text("Titel op de pagina", m.kop, function (v) { set("kop", v); }, "Leeg = de naam van de behandeling."),
      makeup ? null : area("Intro", m.intro, function (v) { set("intro", v); }, "De tekst onder de titel."),
      makeup ? null : el("div", { key: "l", className: "soleil-card__grid soleil-card__grid--even" },
        text("Knop: link", m.link_url, function (v) { set("link_url", v); }, "Leeg = \"Boek hier\" naar de online agenda."),
        text("Knop: tekst", m.link_label, function (v) { set("link_label", v); }, "Enkel nodig met een eigen link.")
      )
    ]);

    var whyBlock = makeup ? null : section("Herken je dit?", "Drie blokken onder de titel: het probleem, wat jij doet en wat de klant eraan heeft.", [
      el(c.ToggleControl, { key: "t", label: "Tonen", checked: !!why, onChange: function (on) { setJson("waarom", on ? { probleem: "", oplossing: "", resultaat: "" } : null); }, __nextHasNoMarginBottom: true }),
      why ? el(Fragment, { key: "f" },
        el("div", { className: "soleil-card__grid soleil-card__grid--three" },
          [0, 1, 2].map(function (i) {
            var titles = why.titels || WHY_TITLES.slice();
            return text("Titel " + (i + 1), titles[i], function (v) { setJson("waarom", merge(why, "titels", put(titles, i, v))); }, null, WHY_TITLES[i]);
          })
        ),
        el(c.ToggleControl, { label: "Probleem als opsomming", checked: Array.isArray(why.probleem), __nextHasNoMarginBottom: true,
          onChange: function (on) { setJson("waarom", merge(why, "probleem", on ? (why.probleem ? [why.probleem] : []) : (why.probleem || []).join(" "))); } }),
        Array.isArray(why.probleem)
          ? lines("Probleem", why.probleem, function (v) { setJson("waarom", merge(why, "probleem", v)); }, "Eén punt per regel.")
          : area("Probleem", why.probleem, function (v) { setJson("waarom", merge(why, "probleem", v)); }),
        area("Wat ik doe", why.oplossing, function (v) { setJson("waarom", merge(why, "oplossing", v)); }),
        area("Wat jij eraan hebt", why.resultaat, function (v) { setJson("waarom", merge(why, "resultaat", v)); })
      ) : null
    ]);

    var priceBlock = makeup ? null : section("Prijzen", "Het prijzenvak rechts op de pagina en de kaart op Tarieven.", [
      el(Prices, { key: "p", value: prices, onChange: function (v) { setJson("prijzen", v); } }),
      area("Noot onder de prijzen", m.noot, function (v) { set("noot", v); }, "Mag leeg blijven."),
      area("Tip", m.tip, function (v) { set("tip", v); }, "Kleine tekst onderaan het prijzenvak, bv. een verwijzing naar de Skin Discovery.")
    ]);

    var partsBlock = makeup ? null : section("Onderdelen", "Behandelingen binnen deze behandeling, elk met foto, tekst en prijzen (zoals Peel, Needling en Neo-Lift bij Skin Expert). Zonder onderdelen valt dit weg.", [
      parts.map(function (p, i) {
        var setPart = function (key) { return function (v) { setJson("delen", put(parts, i, merge(p, key, v))); }; };
        return el("div", { key: i, className: "soleil-card__item" },
          el("div", { className: "soleil-card__item-head" }, el("strong", null, p.naam || "Nieuw onderdeel"), tools(parts, i, function (v) { setJson("delen", v); })),
          el("div", { className: "soleil-card__grid" },
            el(Photo, { id: p.foto, onChange: setPart("foto") }),
            el("div", { className: "soleil-card__fields" },
              text("Naam", p.naam, setPart("naam")),
              text("Anker in de link", p.id, setPart("id"), "Voor links zoals /behandelingen/skin-expert/#peel. Kleine letters, geen spaties."),
              text("Beschrijving van de foto", p.alt, setPart("alt")),
              area("Tekst", p.tekst, setPart("tekst"), null, 5)
            )
          ),
          el(Prices, { value: p.prijzen || [], onChange: setPart("prijzen") })
        );
      }),
      el(c.Button, { key: "a", variant: "secondary", onClick: function () { setJson("delen", parts.concat([{ id: "", naam: "", tekst: "", prijzen: [{ rijen: [] }] }])); } }, "Onderdeel toevoegen")
    ]);

    var activities = (data.activiteiten || []).map(function (a) { return { label: a.title && a.title.raw ? a.title.raw : (a.title && a.title.rendered) || "", value: String(a.id) }; });
    var extraBlock = makeup ? null : section("Extra vak", "Onder de prijzen, bv. de verzorging in de kijker of de data van de laserspecialist.", [
      el(c.ToggleControl, { key: "t", label: "Tonen", checked: !!extra, onChange: function (on) { setJson("extra", on ? { titel: "", tekst: "", lijst: [] } : null); if (!on) set("extra_activiteit", 0); }, __nextHasNoMarginBottom: true }),
      extra ? el(Fragment, { key: "f" },
        text("Titel", extra.titel, function (v) { setJson("extra", merge(extra, "titel", v)); }),
        area("Tekst", extra.tekst, function (v) { setJson("extra", merge(extra, "tekst", v)); }, "Mag leeg blijven."),
        el(c.SelectControl, Object.assign({
          label: "Data van een activiteit", value: String(m.extra_activiteit || ""),
          options: [{ label: "Geen: eigen lijst hieronder", value: "" }].concat(activities),
          help: "Met een activiteit staan hier de komende data van die activiteit. Voorbije data verdwijnen vanzelf.",
          onChange: function (v) { set("extra_activiteit", Number(v) || 0); }
        }, ctl)),
        m.extra_activiteit ? null : lines("Lijst", extra.lijst, function (v) { setJson("extra", merge(extra, "lijst", v)); }, "Eén regel per punt. Mag leeg blijven.")
      ) : null
    ]);

    var makeupBlock = section("Eigen paginaopbouw", "Voor make-up: vragen bovenaan, een merkblok en losse diensten in plaats van het prijzenvak.", [
      el(c.ToggleControl, { key: "t", label: "Eigen paginaopbouw gebruiken", checked: !!makeup, __nextHasNoMarginBottom: true,
        onChange: function (on) { setJson("makeup", on ? { titel: "", vragen: [], cta: "", verhaal: { label: "", titel: "", tekst: [], foto: 0, alt: "" }, diensten: [] } : null); } }),
      makeup ? (function () {
        var story = makeup.verhaal || {};
        var services = makeup.diensten || [];
        var setM = function (key) { return function (v) { setJson("makeup", merge(makeup, key, v)); }; };
        var setStory = function (key) { return function (v) { setJson("makeup", merge(makeup, "verhaal", merge(story, key, v))); }; };
        return el(Fragment, { key: "f" },
          text("Titel op de pagina", makeup.titel, setM("titel")),
          lines("Vragen", makeup.vragen, setM("vragen"), "Eén vraag per regel."),
          text("Zin onder de vragen", makeup.cta, setM("cta")),
          el("h4", { className: "soleil-card__sub" }, "Merkblok"),
          el("div", { className: "soleil-card__grid" },
            el(Photo, { id: story.foto, onChange: setStory("foto") }),
            el("div", { className: "soleil-card__fields" },
              text("Beschrijving van de foto", story.alt, setStory("alt")),
              text("Kleine regel", story.label, setStory("label")),
              area("Titel", story.titel, setStory("titel"), null, 2)
            )
          ),
          area("Tekst", (story.tekst || []).join("\n\n"), function (v) { setStory("tekst")(v.split(/\n\s*\n/).map(function (x) { return x.trim(); }).filter(Boolean)); }, "Een lege regel begint een nieuwe alinea.", 6),
          el("h4", { className: "soleil-card__sub" }, "Diensten"),
          services.map(function (s, i) {
            var setS = function (key) { return function (v) { setJson("makeup", merge(makeup, "diensten", put(services, i, merge(s, key, v)))); }; };
            return el("div", { key: i, className: "soleil-card__item" },
              el("div", { className: "soleil-card__item-head" }, el("strong", null, s.naam || "Nieuwe dienst"), tools(services, i, setM("diensten"))),
              el("div", { className: "soleil-card__grid soleil-card__grid--even" },
                text("Naam", s.naam, setS("naam")),
                text("Kleine regel", s.tag, setS("tag"), "Mag leeg blijven."),
                text("Duur", s.duur, setS("duur")),
                text("Prijs", s.prijs, setS("prijs"), "Een getal of een tekst zoals \"gratis\".")
              ),
              area("Tekst", s.tekst, setS("tekst"))
            );
          }),
          el(c.Button, { variant: "secondary", onClick: function () { setM("diensten")(services.concat([{ naam: "", tekst: "", duur: "", prijs: "" }])); } }, "Dienst toevoegen")
        );
      })() : null
    ], "soleil-card__section--quiet");

    return el("div", blockProps,
      el("div", { className: "soleil-card__head" },
        el("strong", null, "Behandeling"),
        el("span", null, "Vul de velden in en klik op Opslaan. De pagina wordt vanzelf opgebouwd, zoals de andere behandelingen.")
      ),
      head, whyBlock, priceBlock, partsBlock, extraBlock, makeupBlock
    );
  }

  wp.blocks.registerBlockType("soleil/behandelingkaart", {
    apiVersion: 3,
    title: "Behandelingkaart",
    description: "Alle gegevens van de behandeling. Enkel zichtbaar in de editor.",
    icon: "id-alt",
    category: "soleil",
    supports: { html: false, inserter: false, multiple: false, reusable: false, lock: false, className: false, customClassName: false },
    edit: TreatmentSetup,
    save: function () { return null; }
  });

  /* ---------- Berichtkaart: foto, categorie, samenvatting ---------- */
  function PostSetup() {
    var blockProps = be.useBlockProps({ className: "soleil-card soleil-card--post" });
    var featured = useEntityProp("postType", "post", "featured_media");
    var cats = useEntityProp("postType", "post", "categories");
    var excerpt = useEntityProp("postType", "post", "excerpt");
    var terms = useSelect(function (select) {
      return select("core").getEntityRecords("taxonomy", "category", { per_page: -1, orderby: "id", order: "asc", hide_empty: false });
    }, []);
    var usable = terms ? terms.filter(function (t) { return t.id !== 1; }) : null;

    return el("div", blockProps,
      el("div", { className: "soleil-card__head" },
        el("strong", null, "Artikel"),
        el("span", null, "Kies een foto en categorie, schrijf je tekst eronder en klik op Publiceren.")
      ),
      el("div", { className: "soleil-card__grid soleil-card__section" },
        el("div", null, el(Photo, { id: featured[0], onChange: featured[1], help: "Deze foto staat bovenaan het artikel en op de kaartjes in het journaal." })),
        el("div", { className: "soleil-card__fields" },
          el(Pills, { label: "Categorie", terms: usable, value: cats[0] || [], onChange: cats[1] }),
          area("Korte samenvatting", typeof excerpt[0] === "string" ? excerpt[0] : "", excerpt[1], "Staat op de kaartjes in het journaal. Leeg = het begin van je tekst.", 4)
        )
      )
    );
  }

  wp.blocks.registerBlockType("soleil/berichtkaart", {
    apiVersion: 3,
    title: "Berichtkaart",
    description: "Foto, categorie en samenvatting van het artikel. Enkel zichtbaar in de editor.",
    icon: "id-alt",
    category: "soleil",
    supports: { html: false, inserter: false, multiple: false, reusable: false, lock: false, className: false, customClassName: false },
    edit: PostSetup,
    save: function () { return null; }
  });
  // Enkel de knopstijlen van Soleil (inc/blocks.php zet ze om naar .btn en .link).
  wp.domReady(function () {
    ["fill", "outline"].forEach(function (style) { wp.blocks.unregisterBlockStyle("core/button", style); });
  });
})(window.wp);

/* Paneel "Zoekmachines" in de zijbalk van een pagina: titel in het tabblad en in Google. */
(function (wp) {
  if (!wp.editor || !wp.editor.PluginDocumentSettingPanel || !wp.plugins) return;
  var el = wp.element.createElement;
  wp.plugins.registerPlugin("soleil-seo", {
    render: function () {
      var type = wp.data.useSelect(function (s) { return s("core/editor").getCurrentPostType(); }, []);
      var meta = wp.coreData.useEntityProp("postType", "page", "meta");
      if (type !== "page") return null;
      var m = meta[0] || {};
      return el(wp.editor.PluginDocumentSettingPanel, { name: "soleil-seo", title: "Zoekmachines" },
        el(wp.components.TextControl, {
          label: "Titel in zoekmachines", value: m.seo_titel || "", __nextHasNoMarginBottom: true, __next40pxDefaultSize: true,
          help: "Leeg = de titel van de pagina, met \"| Instituut Soleil Beervelde\" erachter. De beschrijving is de samenvatting.",
          onChange: function (v) { meta[1](Object.assign({}, m, { seo_titel: v })); }
        })
      );
    }
  });
})(window.wp);
