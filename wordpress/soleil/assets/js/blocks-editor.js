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
    }
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
      var inner = be.useInnerBlocksProps(blockProps, { renderAppender: false });
      return el(tag, inner);
    },
    save: function () { return el(be.InnerBlocks.Content); }
  });
})(window.wp);
