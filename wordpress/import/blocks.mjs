// Kleine helpers die geldige blokmarkup schrijven voor de vaste pagina's.
// Foto's staan als placeholder {{ID:public/img/x.webp}}; import.php vult het bijlage-ID in.
// Wat elke helper op de site wordt, staat in wordpress/soleil/inc/blocks.php (soleil_block_el, soleil_core_markup, …).

const json = (attrs) => {
  const clean = Object.fromEntries(Object.entries(attrs).filter(([, v]) => v !== undefined && v !== '' && !(typeof v === 'object' && !Array.isArray(v) && !Object.keys(v).length)));
  return Object.keys(clean).length ? ' ' + JSON.stringify(clean) : '';
};
const cls = (...names) => names.filter(Boolean).join(' ');
const join = (children) => children.flat().filter(Boolean).join('\n\n');
const attr = (s) => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;');

/** Container met exact deze tag, klassen en attributen (soleil/el). */
export function el(tag, className, children, { id, ...attrs } = {}) {
  return `<!-- wp:soleil/el${json({ tag: tag === 'div' ? undefined : tag, className, anchor: id, attrs })} -->\n${join(children)}\n<!-- /wp:soleil/el -->`;
}

/** Alinea. html mag inline links bevatten. */
export function p(html, className = '', htmlAttrs = {}) {
  return `<!-- wp:paragraph${json({ className, htmlAttrs })} -->\n<p${className ? ` class="${className}"` : ''}>${html}</p>\n<!-- /wp:paragraph -->`;
}

/** Titel. id via anchor, zodat aria-labelledby blijft werken. */
export function h(level, html, className = '', { id, ...htmlAttrs } = {}) {
  return `<!-- wp:heading${json({ level: level === 2 ? undefined : level, className, anchor: id, htmlAttrs })} -->\n`
    + `<h${level} class="${cls('wp-block-heading', className)}"${id ? ` id="${id}"` : ''}>${html}</h${level}>\n<!-- /wp:heading -->`;
}

/** Lijst. items: tekst of [tekst, htmlAttrs van het item]. */
export function list(items, className = '', htmlAttrs = {}, ordered = false) {
  const tag = ordered ? 'ol' : 'ul';
  const lis = items.map((item) => {
    const [html, a = {}] = Array.isArray(item) ? item : [item];
    return `<!-- wp:list-item${json({ htmlAttrs: a })} -->\n<li>${html}</li>\n<!-- /wp:list-item -->`;
  });
  return `<!-- wp:list${json({ ordered: ordered || undefined, className, htmlAttrs })} -->\n<${tag} class="${cls('wp-block-list', className)}">${lis.join('\n\n')}</${tag}>\n<!-- /wp:list -->`;
}

/** Kruimelpad zoals op elke pagina. */
export function crumbs(...trail) {
  const items = trail.map(([label, href]) => (href ? `<a href="${href}">${label}</a>` : [label, { 'aria-current': 'page' }]));
  return list(items, 'crumbs', { role: 'list' }, true);
}

/**
 * Knoppen. tag: div (standaard, klasse "actions"), p, of none (enkel de knop, zonder omhulsel).
 */
export function buttons(items, className = '', htmlAttrs = {}) {
  const tagClass = htmlAttrs.tag && htmlAttrs.tag !== 'div' ? className : className || 'actions';
  return `<!-- wp:buttons${json({ className: tagClass, htmlAttrs })} -->\n<div class="${cls('wp-block-buttons', tagClass)}">${items.join('\n\n')}</div>\n<!-- /wp:buttons -->`;
}

/**
 * Eén knop. style: '' (btn), light, ghost, sunset, link. icoon: '' (automatisch), map-pin, geen.
 * Externe links (target _blank) krijgen op de site de schuine pijl.
 */
export function button(text, href, { style = '', external = false, icoon = '', className = '' } = {}) {
  const c = cls(style ? `is-style-${style}` : '', className);
  const target = external ? ' target="_blank" rel="noreferrer noopener"' : '';
  return `<!-- wp:button${json({ className: c, htmlAttrs: icoon ? { icoon } : {} })} -->\n`
    + `<div class="${cls('wp-block-button', c)}"><a class="wp-block-button__link wp-element-button" href="${attr(href)}"${target}>${text}</a></div>\n<!-- /wp:button -->`;
}

/** Placeholder voor een bijlage-ID; import.php vervangt "{{ID:pad}}" (met aanhalingstekens) door het getal. */
export const media = (path) => `{{ID:${path}}}`;

/** Foto (soleil/foto). loading: lazy, eager of high. maten: width/height van het origineel tonen. */
export function foto(path, alt, className = '', { tag, loading = 'lazy', maten } = {}) {
  return `<!-- wp:soleil/foto${json({ id: media(path), alt, className, tag, loading: loading === 'lazy' ? undefined : loading, maten })} /-->`;
}

export function icoon(naam) {
  return `<!-- wp:soleil/icoon${json({ naam })} /-->`;
}

/** Dynamisch blok zonder inhoud, bv. dyn('reviews'). */
export function dyn(name, attrs = {}) {
  return `<!-- wp:soleil/${name}${json(attrs)} /-->`;
}

/** Een vaste pagina: alles in één omhulsel met templateLock contentOnly (zie soleil_block_el). */
export const page = (...children) =>
  `<!-- wp:soleil/el {"tag":"pagina","templateLock":"contentOnly","lock":{"move":true,"remove":true}} -->\n${join(children)}\n<!-- /wp:soleil/el -->\n`;
