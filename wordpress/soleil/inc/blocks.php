<?php
/**
 * Eigen blokken. Ze bouwen hun HTML op de server, met dezelfde markup als de Astro-site,
 * zodat style.css en main.js ongewijzigd werken. De editorkant staat in assets/js/blocks-editor.js.
 */

defined( 'ABSPATH' ) || exit;

function soleil_blocks() {
	return array(
		'site-header'  => array( 'render' => 'soleil_render_site_header' ),
		'site-footer'  => array( 'render' => 'soleil_render_site_footer' ),
		'nieuwsbrief'  => array( 'render' => 'soleil_render_newsletter' ),
		'openingsuren' => array(
			'render'     => 'soleil_block_hours',
			'attributes' => array( 'ruimte' => array( 'type' => 'boolean', 'default' => true ) ),
		),
		'behandelingkaart' => array( 'render' => '__return_empty_string' ),
		'berichtkaart' => array( 'render' => '__return_empty_string' ),
		'artikel'      => array( 'render' => 'soleil_block_article' ),
		'journaal-meer' => array( 'render' => 'soleil_block_more_posts' ),
		'laatste-tip'  => array( 'render' => 'soleil_block_latest_tip' ),
		'tekstpagina'  => array( 'render' => 'soleil_block_text_page' ),
		'behandeling-switch' => array( 'render' => 'soleil_block_treatment_switch' ),
		'behandeling'  => array( 'render' => 'soleil_block_treatment' ),
		'behandeling-pager' => array( 'render' => 'soleil_block_treatment_pager' ),
		'behandelingen-lijst' => array( 'render' => 'soleil_block_treatment_list' ),
		'tarieven-chips' => array(
			'render'     => 'soleil_block_rate_chips',
			'attributes' => array( 'zonder' => array( 'type' => 'string', 'default' => 'skin-discovery' ) ),
		),
		'tarieven'     => array(
			'render'     => 'soleil_block_rates',
			'attributes' => array( 'zonder' => array( 'type' => 'string', 'default' => 'skin-discovery' ) ),
		),
		'home-kaarten' => array(
			'render'     => 'soleil_block_home_cards',
			'attributes' => array( 'items' => array( 'type' => 'array', 'default' => array(), 'role' => 'content' ) ),
		),
		'in-de-kijker' => array(
			'render'     => 'soleil_block_spotlight',
			'attributes' => array( 'kopId' => array( 'type' => 'string', 'default' => 'kijker' ) ),
		),
		'reviews'      => array( 'render' => 'soleil_block_reviews' ),
		'voorbeeld-noot' => array(
			'render'     => 'soleil_block_example_note',
			'attributes' => array( 'soort' => array( 'type' => 'string', 'default' => 'reviews' ) ),
		),
		'merken'       => array( 'render' => 'soleil_block_brands' ),
		'producten'    => array( 'render' => 'soleil_block_products' ),
		'product'      => array( 'render' => 'soleil_block_product' ),
		'activiteiten' => array( 'render' => 'soleil_block_activities' ),
		'contact-kaart' => array( 'render' => 'soleil_block_contact_card' ),
		'contactformulier' => array( 'render' => 'soleil_block_contact_form' ),
		'journaal'     => array( 'render' => 'soleil_block_journal' ),
		'pagina-inhoud' => array(
			'render'     => 'soleil_block_page_content',
			'attributes' => array( 'pagina' => array( 'type' => 'string', 'default' => '' ) ),
		),
		'foto'         => array(
			'render'     => 'soleil_block_photo',
			'attributes' => array(
				'id'            => array( 'type' => 'number', 'default' => 0, 'role' => 'content' ),
				'alt'           => array( 'type' => 'string', 'default' => '', 'role' => 'content' ),
				'tag'           => array( 'type' => 'string', 'default' => 'figure' ),
				'loading'       => array( 'type' => 'string', 'default' => 'lazy' ),
				'maten'         => array( 'type' => 'boolean', 'default' => false ),
			),
		),
		'icoon'        => array(
			'render'     => 'soleil_block_icon',
			'attributes' => array( 'naam' => array( 'type' => 'string', 'default' => 'sparkle' ) ),
		),
		'el'           => array(
			'render'     => 'soleil_block_el',
			'attributes' => array(
				'tag'    => array( 'type' => 'string', 'default' => 'div' ),
				'anchor' => array( 'type' => 'string', 'default' => '' ),
				'attrs'  => array( 'type' => 'object', 'default' => array() ),
				'templateLock' => array( 'type' => array( 'string', 'boolean' ), 'enum' => array( 'all', 'insert', 'contentOnly', false ) ),
			),
			'content'    => true,
		),
	);
}

add_action( 'init', function () {
	wp_register_script(
		'soleil-blocks-editor',
		get_theme_file_uri( 'assets/js/blocks-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-core-data', 'wp-dom-ready', 'wp-hooks', 'wp-compose' ),
		soleil_version( 'assets/js/blocks-editor.js' ),
		true
	);
	wp_add_inline_script( 'soleil-blocks-editor', 'window.soleilEditor = ' . wp_json_encode( array( 'adminUrl' => admin_url() ) ) . ';', 'before' );

	// Blokken die zich vullen uit een lijst of de instellingen. In een vergrendelde pagina kan je een blok enkel aanklikken
	// als het een inhoudsattribuut heeft; zonder dat ziet Saskia nooit de melding "Aanpassen doe je bij …" met de knop Openen.
	$managed = array( 'openingsuren', 'behandelingen-lijst', 'tarieven-chips', 'tarieven', 'in-de-kijker', 'reviews', 'merken', 'producten', 'activiteiten', 'contact-kaart', 'contactformulier', 'journaal', 'laatste-tip' );

	foreach ( soleil_blocks() as $name => $block ) {
		$render = $block['render'];
		$attrs  = ( $block['attributes'] ?? array() ) + array( 'className' => array( 'type' => 'string', 'default' => '' ) );
		if ( in_array( $name, $managed, true ) ) {
			$attrs['beheer'] = array( 'type' => 'string', 'default' => '', 'role' => 'content' );
		}
		register_block_type( 'soleil/' . $name, array(
			'api_version'           => 3,
			'category'              => 'soleil',
			'attributes'            => $attrs,
			'render_callback'       => empty( $block['content'] )
				? fn( $attrs ) => call_user_func( $render, $attrs )
				: fn( $attrs, $content ) => call_user_func( $render, $attrs, $content ),
			'editor_script_handles' => array( 'soleil-blocks-editor' ),
			'supports'              => array( 'html' => false, 'className' => false, 'customClassName' => false ),
		) );
	}
} );

// Knopstijlen in de editor. soleil_button_markup() maakt er de klassen van de site van.
add_action( 'init', function () {
	register_block_style( 'core/button', array( 'name' => 'btn', 'label' => 'Knop', 'is_default' => true ) );
	register_block_style( 'core/button', array( 'name' => 'light', 'label' => 'Licht' ) );
	register_block_style( 'core/button', array( 'name' => 'ghost', 'label' => 'Omlijnd' ) );
	register_block_style( 'core/button', array( 'name' => 'sunset', 'label' => 'Sunset' ) );
	register_block_style( 'core/button', array( 'name' => 'link', 'label' => 'Link met pijl' ) );
} );

add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift( $categories, array( 'slug' => 'soleil', 'title' => 'Soleil' ) );
	return $categories;
} );

/* ---------- Container: exact de tag, klassen en attributen van de huidige markup ---------- */

const SOLEIL_EL_TAGS = array( 'div', 'section', 'header', 'footer', 'main', 'article', 'aside', 'nav', 'figure', 'ul', 'ol', 'li', 'a', 'span', 'blockquote' );
const SOLEIL_EL_ATTRS = array( 'role', 'aria-label', 'aria-labelledby', 'aria-current', 'style', 'href', 'target', 'rel', 'tabindex', 'hidden' );

function soleil_block_el( $a, $content ) {
	// "pagina": het omhulsel van een vaste pagina. Het vergrendelt de inhoud in de editor en geeft zelf geen HTML.
	if ( 'pagina' === $a['tag'] ) {
		return $content;
	}
	$tag  = in_array( $a['tag'], SOLEIL_EL_TAGS, true ) ? $a['tag'] : 'div';
	$html = '<' . $tag;
	if ( '' !== $a['className'] ) {
		$html .= ' class="' . esc_attr( $a['className'] ) . '"';
	}
	if ( '' !== $a['anchor'] ) {
		$html .= ' id="' . esc_attr( $a['anchor'] ) . '"';
	}
	foreach ( (array) $a['attrs'] as $name => $value ) {
		if ( ! in_array( $name, SOLEIL_EL_ATTRS, true ) ) {
			continue;
		}
		if ( 'href' === $name ) {
			$value = soleil_link( $value );
		}
		$html .= true === $value ? ' ' . $name : ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	return $html . '>' . $content . '</' . $tag . '>';
}

/* ---------- Foto en icoon ---------- */

/**
 * Foto in een vaste pagina: <figure class="…"><img></figure>, of een losse <img> (band, menukaart).
 * loading: lazy, eager (geen attribuut) of high (fetchpriority="high").
 */
function soleil_block_photo( $a ) {
	$attrs = array( 'alt' => $a['alt'] );
	if ( $a['maten'] ) {
		$attrs['size'] = true;
	}
	if ( 'high' === $a['loading'] ) {
		$attrs['fetchpriority'] = 'high';
	} elseif ( 'lazy' === $a['loading'] ) {
		$attrs['loading'] = 'lazy';
	}
	$img = soleil_img( $a['id'], $attrs );
	if ( 'img' === $a['tag'] ) {
		return $img;
	}
	$tag = 'span' === $a['tag'] ? 'span' : 'figure';
	return '<' . $tag . ( '' !== $a['className'] ? ' class="' . esc_attr( $a['className'] ) . '"' : '' ) . '>' . $img . '</' . $tag . '>';
}

function soleil_block_icon( $a ) {
	return soleil_icon( $a['naam'] );
}

/* ---------- Openingsuren ---------- */

function soleil_block_hours( $a ) {
	// In een artikel staat het blok met wat ruimte rond, zoals in journaal/[slug].astro.
	return $a['ruimte'] ? '<div style="margin-block:1.6em">' . soleil_week() . '</div>' : soleil_week();
}

/* ---------- Vaste links in de inhoud ---------- */

/**
 * /afspraak/, /webshop/, /route/ en /instagram/ in de inhoud wijzen naar de link uit de instellingen.
 * Zo verandert een nieuwe boekingslink op één plek.
 */
function soleil_link_tokens() {
	return array(
		'/afspraak/'  => 'booking',
		'/webshop/'   => 'webshop',
		'/route/'     => 'route',
		'/instagram/' => 'instagram',
		'/facebook/'  => 'facebook',
	);
}

function soleil_link( $href ) {
	$href = (string) $href;
	$path = preg_replace( '#^' . preg_quote( untrailingslashit( home_url() ), '#' ) . '#', '', $href );
	$map  = soleil_link_tokens();
	return isset( $map[ $path ] ) && soleil_setting( $map[ $path ] ) ? soleil_setting( $map[ $path ] ) : $href;
}

// Wie toch op zo'n pad belandt (bv. vanuit de editor), gaat door naar de echte link.
add_action( 'template_redirect', function () {
	$path = soleil_current_path();
	$map  = soleil_link_tokens();
	if ( is_404() && isset( $map[ $path ] ) && soleil_setting( $map[ $path ] ) ) {
		wp_redirect( soleil_setting( $map[ $path ] ), 302 );
		exit;
	}
} );

/* ---------- Template parts zonder extra wrapper ---------- */

// Header en footer staan rechtstreeks in <body>, zoals nu: de sticky header heeft de hele pagina als ruimte.
add_filter( 'render_block_core/template-part', function ( $html ) {
	return preg_replace( '#^\s*<(header|footer|div)[^>]*>(.*)</\1>\s*$#s', '$2', $html );
} );

/* ---------- Inhoud van een pagina zonder extra wrapper ---------- */

// De secties staan rechtstreeks in <main>, zoals nu.
add_filter( 'render_block_core/post-content', function ( $html ) {
	return preg_replace( '#^\s*<div[^>]*>(.*)</div>\s*$#s', '$1', $html );
} );

/* ---------- Seizoenstips (de gewone berichten) ---------- */

function soleil_tips_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/seizoenstips/' );
}

function soleil_tips_title() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_the_title( $id ) : 'Seizoenstips';
}

// De oude adressen onder /journaal/ blijven werken.
add_action( 'template_redirect', function () {
	if ( is_404() && preg_match( '#^/journaal(/.*)?$#', soleil_current_path(), $m ) ) {
		wp_safe_redirect( home_url( '/seizoenstips' . ( $m[1] ?? '/' ) ), 301 );
		exit;
	}
}, 1 );

// Er is geen apart overzicht: /seizoenstips/ opent meteen de nieuwste tip, de andere tips staan onderaan die pagina.
add_action( 'template_redirect', function () {
	$tip = is_home() ? soleil_latest_tip() : null;
	if ( $tip ) {
		wp_safe_redirect( get_permalink( $tip ), 302 );
		exit;
	}
}, 2 );

// Nog geen enkele tip gepubliceerd: de hele sectie op de home blijft weg.
add_filter( 'render_block_soleil/el', function ( $html, $block ) {
	return str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'tips-home' ) && ! get_posts( array( 'numberposts' => 1, 'fields' => 'ids' ) ) ? '' : $html;
}, 10, 2 );

function soleil_post_category( $post ) {
	foreach ( get_the_category( $post->ID ) as $term ) {
		if ( (int) get_option( 'default_category' ) !== $term->term_id ) {
			return $term->name;
		}
	}
	return '';
}

/**
 * Datum zoals Intl.DateTimeFormat('nl-BE', { day: 'numeric', month: 'long', year: 'numeric' }): "28 augustus 2026".
 */
function soleil_post_date( $post ) {
	$time = get_post_datetime( $post );
	return '<time datetime="' . esc_attr( $time->format( 'Y-m-d' ) ) . '">' . esc_html( $time->format( 'j' ) . ' ' . strtolower( soleil_month_nl( $time ) ) . ' ' . $time->format( 'Y' ) ) . '</time>';
}

/**
 * Kaartje van een artikel, zoals PostCard.astro.
 */
function soleil_post_card( $post ) {
	$post  = get_post( $post );
	$image = has_post_thumbnail( $post )
		? soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) )
		: '';
	return '<a class="post rv" href="' . esc_url( get_permalink( $post ) ) . '">'
		. '<figure class="ph">' . $image . '</figure>'
		. '<p class="post-meta"><span class="label">' . esc_html( soleil_post_category( $post ) ) . '</span>' . soleil_post_date( $post ) . '</p>'
		. '<h3 class="h3">' . esc_html( get_the_title( $post ) ) . '</h3>'
		. '<p>' . esc_html( get_the_excerpt( $post ) ) . '</p>'
		. '</a>';
}

/**
 * De nieuwste echte tip: berichten in de categorie Nieuws of Nieuw (bv. nieuwe openingsuren) komen niet op de home,
 * tenzij er niets anders is.
 */
function soleil_latest_tip() {
	$news  = array_filter( array_map( fn( $slug ) => (int) ( get_category_by_slug( $slug )->term_id ?? 0 ), array( 'nieuws', 'nieuw' ) ) );
	$posts = get_posts( array( 'numberposts' => 1, 'category__not_in' => $news ) ) ?: get_posts( array( 'numberposts' => 1 ) );
	return $posts ? $posts[0] : null;
}

/**
 * De nieuwste tip op de home: foto, categorie en datum, titel, korte tekst. Het hele blok is klikbaar.
 */
function soleil_block_latest_tip() {
	$post = soleil_latest_tip();
	if ( ! $post ) {
		return '';
	}
	$photo = has_post_thumbnail( $post );
	return '<a class="spot tip-card rv' . ( $photo ? '' : ' tip-card--text' ) . '" href="' . esc_url( get_permalink( $post ) ) . '">'
		. ( $photo ? '<figure class="ph">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>' : '' )
		. '<div class="spot-copy"><p class="post-meta"><span class="label">' . esc_html( soleil_post_category( $post ) ) . '</span>' . soleil_post_date( $post ) . '</p>'
		. '<h3 class="h1">' . esc_html( get_the_title( $post ) ) . '</h3>'
		. '<p class="muted">' . esc_html( get_the_excerpt( $post ) ) . '</p>'
		. '<span class="link">' . esc_html( soleil_text( 'tip_read', 'Lees deze tip' ) ) . soleil_icon( 'arrow-right' ) . '</span>'
		. '</div></a>';
}

/**
 * Geeft elke tussentitel in de tekst een anker en geeft de lijst van tussentitels terug voor "In deze tip".
 */
function soleil_tip_sections( $content ) {
	$toc     = array();
	$content = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#s', function ( $m ) use ( &$toc ) {
		$text = trim( wp_strip_all_tags( $m[2] ) );
		if ( '' === $text ) {
			return $m[0];
		}
		$id    = 'tip-' . ( count( $toc ) + 1 );
		$toc[] = array( 'id' => $id, 'text' => $text );
		return '<h2 id="' . $id . '"' . preg_replace( '#\sid="[^"]*"#', '', $m[1] ) . '>' . $m[2] . '</h2>';
	}, $content );
	return array( $content, $toc );
}

/**
 * De pagina van een tip: kop zoals een behandeling (foto, titel), de tekst in een leeskolom met rechts de inhoud
 * en een knop, en onderaan een afsluitend blok. Korte tips zonder tussentitels krijgen enkel de leeskolom.
 */
function soleil_block_article() {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type ) {
		return '<article class="wrap article"><header class="ph-head"><h1 class="h1">Titel van de tip</h1></header></article>';
	}
	$cat   = soleil_post_category( $post );
	$photo = has_post_thumbnail( $post );
	$book  = soleil_text( 'tip_cta_btn', 'Afspraak maken' );

	$GLOBALS['soleil_in_article'] = true;
	$content                      = apply_filters( 'the_content', $post->post_content );
	$GLOBALS['soleil_in_article'] = false;

	list( $content, $toc ) = soleil_tip_sections( $content );
	$minutes               = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( $content ) ) / 200 ) );
	$side                  = '';
	if ( count( $toc ) >= 3 ) {
		$links = implode( '', array_map( fn( $t ) => '<li><a href="#' . esc_attr( $t['id'] ) . '">' . esc_html( $t['text'] ) . '</a></li>', $toc ) );
		$side  = '<aside class="tip-side" aria-labelledby="tip-inhoud"><p class="label" id="tip-inhoud">' . esc_html( soleil_text( 'tip_toc', 'In deze tip' ) ) . '</p>'
			. '<ol class="tip-toc" role="list">' . $links . '</ol>'
			. '<a class="btn" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>' . esc_html( $book ) . soleil_icon( 'arrow-up-right' ) . '</a></aside>';
	}

	return '<article>'
		. '<header class="wrap thead tip-head' . ( $photo ? '' : ' thead--solo' ) . '">'
		. ( $photo ? '<figure class="framed rv-img">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'fetchpriority' => 'high' ) ) . '</figure>' : '' )
		. '<div><ol class="crumbs" role="list"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li aria-current="page">' . esc_html( soleil_tips_title() ) . '</li></ol>'
		. '<p class="post-meta">' . ( '' !== $cat ? '<span class="label">' . esc_html( $cat ) . '</span>' : '' ) . soleil_post_date( $post ) . '<span>' . $minutes . ' min lezen</span></p>'
		. '<h1 class="display rv">' . esc_html( get_the_title( $post ) ) . '</h1></div></header>'
		. '<div class="wrap tip-layout' . ( $side ? '' : ' tip-layout--plain' ) . '"><div class="prose tip-body">' . $content . '</div>' . $side . '</div>'
		. '<section class="wrap" aria-labelledby="tip-cta"><div class="tip-cta rv"><div><h2 id="tip-cta" class="h2">' . esc_html( soleil_text( 'tip_cta_title', 'Benieuwd wat jouw huid nodig heeft?' ) ) . '</h2>'
		. '<p>' . esc_html( soleil_text( 'tip_cta_text', 'Ik bekijk je huid en geef je eerlijk advies op maat.' ) ) . '</p></div>'
		. '<div class="actions"><a class="btn" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>' . esc_html( $book ) . soleil_icon( 'arrow-up-right' ) . '</a>'
		. '<a class="link" href="' . esc_url( soleil_treatment_url( 'skin-discovery' ) ) . '">' . esc_html( soleil_text( 'tip_cta_link', 'Nieuw? Start met een Skin Discovery' ) ) . soleil_icon( 'arrow-right' ) . '</a></div></div></section>'
		. '</article>';
}

/**
 * Tekstpagina (privacybeleid): kruimelpad en titel vast, de tekst in de leeskolom van een artikel.
 * Geen ontwerp van de statische site: dezelfde klassen als een journaalartikel, zonder datum, foto en knoppen.
 */
function soleil_block_text_page() {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$GLOBALS['soleil_in_article'] = true;
	$content                      = apply_filters( 'the_content', $post->post_content );
	$GLOBALS['soleil_in_article'] = false;

	return '<article class="wrap article"><header class="ph-head">'
		. '<ol class="crumbs" role="list"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li aria-current="page">' . esc_html( get_the_title( $post ) ) . '</li></ol>'
		. '<h1 class="h1">' . esc_html( get_the_title( $post ) ) . '</h1>'
		. '</header><div class="prose">' . $content . '</div></article>';
}

/**
 * "Meer seizoenstips": alle andere tips onder de tip, de nieuwste eerst. Is er maar één tip, dan valt dit weg.
 */
function soleil_block_more_posts() {
	$post  = get_post();
	$posts = get_posts( array(
		'numberposts'  => -1,
		'post__not_in' => $post ? array( $post->ID ) : array(),
	) );
	if ( ! $posts ) {
		return '';
	}
	return '<section class="section wrap" aria-labelledby="meer">'
		. '<div class="sec-head"><h2 id="meer" class="h2">' . esc_html( soleil_text( 'tip_more', 'Meer seizoenstips' ) ) . '</h2></div>'
		. '<div class="posts">' . implode( '', array_map( 'soleil_post_card', $posts ) ) . '</div></section>';
}

/* ---------- Verzorging in de kijker (Spotlight.astro) ---------- */

function soleil_block_spotlight( $a ) {
	$href   = trim( soleil_setting( 'spot_href' ) );
	$season = trim( soleil_setting( 'spot_season' ) );
	return '<div class="spot">'
		. '<figure class="ph rv-img">' . soleil_img( soleil_setting( 'spot_image' ), array( 'alt' => soleil_setting( 'spot_alt' ), 'loading' => 'lazy' ) ) . '</figure>'
		. '<div class="spot-copy rv">'
		. '<p class="label label--sunset">' . esc_html( soleil_text( 'spot_label', 'Verzorging in de kijker' ) . ( '' !== $season ? ' · ' . $season : '' ) ) . '</p>'
		. '<h2 id="' . esc_attr( $a['kopId'] ) . '" class="h1">' . esc_html( soleil_setting( 'spot_name' ) ) . '</h2>'
		. str_replace( '<p>', '<p class="muted">', soleil_paragraphs( soleil_setting( 'spot_text' ) ) )
		. '<p class="spot-price"><span>' . esc_html( soleil_setting( 'spot_dur' ) ) . '</span><strong>' . esc_html( soleil_euro( soleil_setting( 'spot_price' ) ) ) . '</strong></p>'
		. '<div class="actions">' . soleil_book_button( 'btn', soleil_text( 'spot_book', 'Boek deze verzorging' ) )
		. ( '' !== $href ? '<a class="link" href="' . esc_url( str_starts_with( $href, '/' ) ? home_url( $href ) : $href ) . '">' . esc_html( soleil_text( 'spot_more', 'Meer info' ) ) . soleil_icon( 'arrow-right' ) . '</a>' : '' )
		. '</div>'
		. '</div></div>';
}

/* ---------- Lijsten uit de eenvoudige inhoudstypes ---------- */

function soleil_items( $type ) {
	return get_posts( array( 'post_type' => $type, 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
}

function soleil_has_examples( $types ) {
	foreach ( (array) $types as $type ) {
		foreach ( soleil_items( $type ) as $post ) {
			if ( get_post_meta( $post->ID, 'voorbeeld', true ) ) {
				return true;
			}
		}
	}
	return false;
}

function soleil_block_reviews() {
	$html = '';
	foreach ( soleil_items( 'review' ) as $i => $post ) {
		$html .= '<li class="rv" style="--d:' . $i . '"><span class="qmark" aria-hidden="true">“</span>'
			. '<blockquote>' . esc_html( get_post_meta( $post->ID, 'tekst', true ) ) . '</blockquote>'
			. '<p><strong>' . esc_html( get_the_title( $post ) ) . '</strong><span>' . esc_html( get_post_meta( $post->ID, 'plaats', true ) ) . '</span></p></li>';
	}
	return '<ul class="quotes" role="list">' . $html . '</ul>';
}

/**
 * Melding dat de reviews nog voorbeelden zijn. Verdwijnt vanzelf als niets nog als voorbeeld gemarkeerd is.
 * Op de pagina Nieuw staat geen melding meer: daar staat Saskia's eigen inhoud.
 */
function soleil_block_example_note( $a ) {
	if ( 'nieuw' === $a['soort'] ) {
		return '';
	}
	return soleil_has_examples( 'review' ) ? '<p class="muted rv">Voorbeeldreviews: deze vervangen we door echte reviews van klanten.</p>' : '';
}

function soleil_block_brands() {
	$html = '';
	foreach ( soleil_items( 'merk' ) as $i => $post ) {
		$name  = get_the_title( $post );
		$photo = has_post_thumbnail( $post )
			? soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => 'Producten van ' . $name, 'loading' => 'lazy' ) )
			: '<span class="word" aria-hidden="true">' . esc_html( $name ) . '</span>';
		$text  = '<p>' . esc_html( get_post_meta( $post->ID, 'regel', true ) ) . '</p>';
		$href  = soleil_link( trim( (string) get_post_meta( $post->ID, 'link_url', true ) ) );
		if ( '' === $href ) {
			$html .= '<li class="rv" style="--d:' . $i . '"><figure class="ph">' . $photo . '</figure><h3>' . esc_html( $name ) . '</h3>' . $text . '</li>';
			continue;
		}
		// Met een link is het hele blokje klikbaar. Een link naar een andere site opent in een nieuw tabblad.
		$url   = str_starts_with( $href, '/' ) ? home_url( $href ) : $href;
		$local = str_starts_with( $url, home_url() );
		$html .= '<li class="rv" style="--d:' . $i . '"><a class="brand-link" href="' . esc_url( $url ) . '"' . ( $local ? '' : soleil_blank() ) . '>'
			. '<figure class="ph">' . $photo . '</figure><h3>' . esc_html( $name ) . soleil_icon( $local ? 'arrow-right' : 'arrow-up-right' ) . '</h3>' . $text . '</a></li>';
	}
	return '<ul class="btiles" role="list">' . $html . '</ul>';
}

function soleil_product_shop_url( $post ) {
	$href = (string) get_post_meta( $post->ID, 'link_url', true );
	return '' !== $href ? soleil_link( $href ) : soleil_setting( 'webshop' );
}

function soleil_product_has_detail( $post ) {
	return '' !== trim( wp_strip_all_tags( (string) get_post_meta( $post->ID, 'detail', true ) ) );
}

/**
 * Kaartjes op de pagina Nieuw. Met info over het product gaat het kaartje naar de eigen pagina,
 * zonder info meteen naar de webshop (zoals vroeger).
 */
function soleil_product_card( $post, $i ) {
	$detail = soleil_product_has_detail( $post );
	$href   = $detail ? get_permalink( $post ) : soleil_product_shop_url( $post );
	return '<li class="rv" style="--d:' . $i . '"><a href="' . esc_url( $href ) . '"' . ( $detail ? '' : soleil_blank() ) . '>'
		. '<figure class="ph">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'
		. '<p class="label">' . esc_html( get_post_meta( $post->ID, 'merk', true ) ) . '</p>'
		. '<h3>' . esc_html( get_the_title( $post ) ) . soleil_icon( $detail ? 'arrow-right' : 'arrow-up-right' ) . '</h3>'
		. '<p>' . esc_html( get_post_meta( $post->ID, 'tekst', true ) ) . '</p></a></li>';
}

function soleil_block_products() {
	$html = '';
	foreach ( soleil_items( 'product' ) as $i => $post ) {
		$html .= soleil_product_card( $post, $i );
	}
	return '<ul class="btiles news" role="list">' . $html . '</ul>';
}

/**
 * De eigen pagina van een product: foto, merk, naam, korte tekst, knop naar de webshop en de info.
 */
function soleil_block_product() {
	$post = get_post();
	if ( ! $post || 'product' !== $post->post_type ) {
		return '<header class="wrap thead"><div><h1 class="display">Product</h1></div></header>';
	}
	$name   = get_the_title( $post );
	$merk   = (string) get_post_meta( $post->ID, 'merk', true );
	$tekst  = (string) get_post_meta( $post->ID, 'tekst', true );
	$detail = (string) get_post_meta( $post->ID, 'detail', true );
	$photo  = has_post_thumbnail( $post )
		? '<figure class="framed rv-img">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => $name, 'fetchpriority' => 'high' ) ) . '</figure>'
		: '';
	$nieuw  = home_url( '/nieuw/' );
	$shop   = '<a class="btn" href="' . esc_url( soleil_product_shop_url( $post ) ) . '"' . soleil_blank() . '>' . esc_html( soleil_text( 'prod_shop', 'Bekijk in de webshop' ) ) . soleil_icon( 'arrow-up-right' ) . '</a>';

	$html = '<header class="wrap thead' . ( $photo ? '' : ' thead--solo' ) . '">' . $photo . '<div>'
		. '<ol class="crumbs" role="list"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li><a href="' . esc_url( $nieuw ) . '">Nieuw</a></li><li aria-current="page">' . esc_html( $name ) . '</li></ol>'
		. ( '' !== $merk ? '<p class="label rv">' . esc_html( $merk ) . '</p>' : '' )
		. '<h1 class="display rv">' . esc_html( $name ) . '</h1>'
		. ( '' !== $tekst ? '<p class="lede rv" style="--d:1">' . esc_html( $tekst ) . '</p>' : '' )
		. '<div class="actions rv" style="--d:2">' . $shop . '</div></div></header>';

	// De info naast een kleine titel, op hetzelfde raster als de header (foto links, tekst rechts).
	if ( soleil_product_has_detail( $post ) ) {
		$html .= '<section class="wrap pinfo" aria-labelledby="info">'
			. '<div class="pinfo-head"><p class="label">' . esc_html( soleil_text( 'prod_label', 'Meer weten' ) ) . '</p><h2 id="info" class="h2">' . esc_html( soleil_text( 'prod_title', 'Over dit product' ) ) . '</h2></div>'
			. '<div class="prose">' . wpautop( wp_kses_post( $detail ) )
			. '<div class="actions">' . $shop
			. '<a class="link" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>' . esc_html( soleil_text( 'prod_advice', 'Liever eerst advies? Maak een afspraak' ) ) . soleil_icon( 'arrow-up-right' ) . '</a>'
			. '</div></div></section>';
	}

	// Ook nieuw: de andere producten, zelfde kaartjes als op de pagina Nieuw.
	$others = array_values( array_filter( soleil_items( 'product' ), fn( $p ) => $p->ID !== $post->ID ) );
	if ( $others ) {
		$cards = '';
		foreach ( array_slice( $others, 0, 3 ) as $i => $p ) {
			$cards .= soleil_product_card( $p, $i );
		}
		$html .= '<section class="wrap section pmore" aria-labelledby="ook-nieuw">'
			. '<div class="sec-head"><h2 id="ook-nieuw" class="h1 rv">' . esc_html( soleil_text( 'prod_more', 'Ook nieuw' ) ) . '</h2>'
			. '<a class="link rv" href="' . esc_url( $nieuw ) . '">' . esc_html( soleil_text( 'prod_all', 'Alles wat nieuw is' ) ) . soleil_icon( 'arrow-right' ) . '</a></div>'
			. '<ul class="btiles news" role="list">' . $cards . '</ul></section>';
	}
	return $html;
}

function soleil_block_activities() {
	$html = '';
	foreach ( soleil_items( 'activiteit' ) as $i => $post ) {
		$dates = soleil_upcoming( (array) get_post_meta( $post->ID, 'data', true ) );
		if ( ! $dates && '' !== (string) get_post_meta( $post->ID, 'geen_data', true ) ) {
			$dates = array( (string) get_post_meta( $post->ID, 'geen_data', true ) );
		}
		$href  = (string) get_post_meta( $post->ID, 'link_url', true );
		$label = trim( (string) get_post_meta( $post->ID, 'link_label', true ) );
		$html .= '<li class="rv" style="--d:' . $i . '">'
			. '<figure class="ph">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'
			. '<div><h3' . ( get_post_meta( $post->ID, 'titel_vet', true ) ? ' class="is-bold"' : '' ) . '>' . esc_html( wp_strip_all_tags( get_the_title( $post ) ) ) . '</h3>'
			. str_replace( '<p>', '<p class="muted">', soleil_paragraphs( get_post_meta( $post->ID, 'tekst', true ) ) ) // lege regel = nieuwe alinea
			. ( $dates ? '<ul class="dates-list" role="list">' . implode( '', array_map( fn( $d ) => '<li>' . esc_html( $d ) . '</li>', $dates ) ) . '</ul>' : '' )
			. ( '' !== $href && '' !== $label ? '<a class="link" href="' . esc_url( str_starts_with( $href, '/' ) ? home_url( $href ) : $href ) . '">' . esc_html( $label ) . soleil_icon( 'arrow-right' ) . '</a>' : '' )
			. '</div></li>';
	}
	return '<ul class="dates" role="list">' . $html . '</ul>';
}

/* ---------- Contact ---------- */

function soleil_block_contact_card() {
	return '<div class="card rv">'
		. '<p class="big-line">' . esc_html( soleil_setting( 'street' ) ) . '<br>' . esc_html( soleil_setting( 'city' ) ) . '</p>'
		. '<p style="margin-top:20px"><a class="link" href="' . esc_url( soleil_setting( 'phone_href' ) ) . '">' . esc_html( soleil_setting( 'phone' ) ) . '</a></p>'
		. '<p style="margin-top:8px"><a class="link" href="mailto:' . esc_attr( soleil_setting( 'email' ) ) . '">' . esc_html( soleil_setting( 'email' ) ) . '</a></p>'
		. '<div class="actions" style="margin-top:28px">' . soleil_book_button() . '</div>'
		. '</div>';
}

/**
 * Het formulier opent het mailprogramma van de bezoeker (main.js), zoals nu. Er wordt niets op de server bewaard.
 */
function soleil_block_contact_form() {
	$email = soleil_setting( 'email' );
	return '<form class="form" data-form data-mailto="' . esc_attr( $email ) . '" novalidate>'
		. '<div class="form-row">'
		. '<div class="field"><label for="n">Naam</label><input id="n" name="naam" autocomplete="name" required aria-describedby="n-err" /><span class="err" id="n-err">Vul je naam in.</span></div>'
		. '<div class="field"><label for="t">Telefoon <small>(niet verplicht)</small></label><input id="t" name="telefoon" type="tel" autocomplete="tel" /></div>'
		. '</div>'
		. '<div class="field"><label for="e">E-mail</label><input id="e" name="email" type="email" autocomplete="email" required aria-describedby="e-err" /><span class="err" id="e-err">Vul een geldig e-mailadres in.</span></div>'
		. '<div class="field"><label for="m">Je vraag</label><textarea id="m" name="vraag" required aria-describedby="m-err"></textarea><span class="err" id="m-err">Schrijf kort je vraag.</span></div>'
		. '<div class="actions"><button class="btn" type="submit">Verstuur via e-mail</button></div>'
		. '</form>'
		. '<div class="done" tabindex="-1" hidden><p class="h3">Je mailprogramma is geopend</p>'
		. '<p class="muted" style="margin-top:8px">Verstuur het bericht daar. Opende er niets? Mail naar <a class="link" href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p></div>';
}

/* ---------- Journaal (journaal/index.astro) ---------- */

function soleil_block_journal() {
	$posts = get_posts( array( 'numberposts' => -1 ) );
	return '<section class="wrap posts" style="padding-bottom:var(--section)">' . implode( '', array_map( 'soleil_post_card', $posts ) ) . '</section>';
}

/* ---------- Inhoud van een vaste pagina in een template (journaal, 404) ---------- */

/**
 * pagina: "berichten" = de pagina die het journaal toont, anders een slug (bv. pagina-niet-gevonden, ook als die privé is).
 */
function soleil_block_page_content( $a ) {
	static $depth = 0;
	if ( 'berichten' === $a['pagina'] ) {
		$page = get_post( (int) get_option( 'page_for_posts' ) );
	} else {
		$found = get_posts( array( 'post_type' => 'page', 'name' => $a['pagina'], 'post_status' => array( 'publish', 'private' ), 'numberposts' => 1 ) );
		$page  = $found ? $found[0] : null;
	}
	if ( ! $page || $depth > 0 ) {
		return '';
	}
	++$depth;
	$html = do_blocks( $page->post_content );
	--$depth;
	return $html;
}

/* ==========================================================================
   Kernblokken in de vaste pagina's: dezelfde markup als de statische site
   ========================================================================== */

/**
 * Extra attribuut htmlAttrs op tekst- en knopblokken: role, style, id en (voor knoppen) tag en icoon.
 * Zo krijgt een alinea bv. exact <p class="rv" style="--d:1">, wat WordPress zelf niet kan opslaan.
 */
const SOLEIL_CORE_BLOCKS = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/buttons', 'core/button' );
const SOLEIL_HTML_ATTRS  = array( 'role', 'style', 'id', 'aria-current', 'aria-label' );

add_filter( 'register_block_type_args', function ( $args, $name ) {
	if ( in_array( $name, SOLEIL_CORE_BLOCKS, true ) ) {
		$args['attributes']['htmlAttrs'] = array( 'type' => 'object', 'default' => array() );
	}
	return $args;
}, 10, 2 );

/**
 * Waarom prioriteit 20, en via render_block_{blok} in plaats van render_block:
 * sinds WordPress 7.0 voegt core zelf klassen toe aan opgeslagen blokken (bv. "wp-block-paragraph" via
 * block_core_paragraph_add_class op render_block_core/paragraph, prioriteit 10). De filters per blok lopen ná het
 * algemene render_block, dus wie de markup wil gelijktrekken moet daar en later inhaken.
 * Controle na elke WordPress-update: node wordpress/bin/check-html.mjs (vergelijkt met de Astro-build).
 */
function soleil_core_markup( $html, $block ) {
	if ( '' === trim( $html ) ) {
		return $html;
	}
	$name  = $block['blockName'];
	$attrs = (array) ( $block['attrs']['htmlAttrs'] ?? array() );

	if ( 'core/button' === $name ) {
		return soleil_button_markup( $html, $block['attrs'] ?? array(), $attrs );
	}
	if ( 'core/buttons' === $name ) {
		// <div class="wp-block-buttons …"> wordt <div class="actions …">, een <p> rond een losse link,
		// of niets (tag "none": de knop staat rechtstreeks in de sectie, zoals naast een titel).
		$tag   = in_array( $attrs['tag'] ?? 'div', array( 'div', 'p', 'none' ), true ) ? ( $attrs['tag'] ?? 'div' ) : 'div';
		$class = trim( (string) ( $block['attrs']['className'] ?? '' ) );
		$inner = trim( preg_replace( '#^\s*<div[^>]*>(.*)</div>\s*$#s', '$1', $html ) );
		if ( 'none' === $tag ) {
			return $inner;
		}
		$class = '' === $class && 'div' === $tag ? 'actions' : $class;
		return '<' . $tag . ( '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . soleil_html_attrs( $attrs ) . '>' . $inner . '</' . $tag . '>';
	}

	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag() ) {
		return $html;
	}
	$p->remove_class( 'wp-block-' . substr( $name, 5 ) );
	if ( ! empty( $GLOBALS['soleil_in_article'] ) ) {
		// In een artikel: tussentitels <h2 class="h3">, lijsten <ul role="list">, zoals journaal/[slug].astro.
		if ( 'H2' === $p->get_tag() ) {
			$p->add_class( 'h3' );
		}
		if ( 'core/list' === $name ) {
			$p->set_attribute( 'role', 'list' );
		}
	}
	foreach ( SOLEIL_HTML_ATTRS as $key ) {
		if ( isset( $attrs[ $key ] ) && '' !== $attrs[ $key ] ) {
			$p->set_attribute( $key, $attrs[ $key ] );
		}
	}
	if ( null === $p->get_attribute( 'class' ) || '' === trim( (string) $p->get_attribute( 'class' ) ) ) {
		$p->remove_attribute( 'class' );
	}
	// Links naar /afspraak/, /webshop/, … wijzen naar de link uit de instellingen.
	while ( $p->next_tag( 'a' ) ) {
		$href = (string) $p->get_attribute( 'href' );
		if ( '' !== $href ) {
			$p->set_attribute( 'href', soleil_link( $href ) );
		}
	}
	$html = $p->get_updated_html();

	// Alinea als <span> met icoon, bv. de knop in de Skin Discovery-banner (<span class="btn btn--light">… <svg>).
	if ( 'core/paragraph' === $name && 'span' === ( $attrs['tag'] ?? '' ) ) {
		$html = preg_replace( '#^(\s*)<p\b(.*)</p>(\s*)$#s', '$1<span$2' . ( ! empty( $attrs['icoon'] ) ? soleil_icon( $attrs['icoon'] ) : '' ) . '</span>$3', $html );
	}
	return $html;
}
foreach ( SOLEIL_CORE_BLOCKS as $soleil_block ) {
	add_filter( 'render_block_' . $soleil_block, 'soleil_core_markup', 20, 2 );
}

function soleil_html_attrs( $attrs ) {
	$out = '';
	foreach ( SOLEIL_HTML_ATTRS as $key ) {
		if ( isset( $attrs[ $key ] ) && '' !== $attrs[ $key ] ) {
			$out .= ' ' . $key . '="' . esc_attr( $attrs[ $key ] ) . '"';
		}
	}
	return $out;
}

/**
 * core/button wordt <a class="btn …"> of <a class="link"> met het icoon van de site.
 * Stijl in de editor → klasse: Knop = btn, Licht = btn btn--light, Omlijnd = btn btn--ghost, Sunset = btn btn--sunset, Link = link.
 * Icoon (htmlAttrs.icoon): leeg = pijl (schuin bij een externe link), "map-pin" vooraan, "geen".
 */
function soleil_button_markup( $html, $block_attrs, $attrs ) {
	if ( ! preg_match( '#<a\b([^>]*)>(.*?)</a>#s', $html, $m ) ) {
		return $html;
	}
	$a     = new WP_HTML_Tag_Processor( '<a' . $m[1] . '>' );
	$a->next_tag();
	$href   = soleil_link( (string) $a->get_attribute( 'href' ) );
	$blank  = '_blank' === $a->get_attribute( 'target' );
	$styles = array( 'light' => 'btn btn--light', 'ghost' => 'btn btn--ghost', 'sunset' => 'btn btn--sunset', 'link' => 'link' );
	$style  = preg_match( '/is-style-([a-z]+)/', (string) ( $block_attrs['className'] ?? '' ), $s ) ? $s[1] : '';
	$extra  = trim( preg_replace( '/is-style-[a-z]+/', '', (string) ( $block_attrs['className'] ?? '' ) ) );
	$class  = trim( ( $styles[ $style ] ?? 'btn' ) . ' ' . $extra );
	$icon   = (string) ( $attrs['icoon'] ?? '' );
	$label  = trim( $m[2] );

	if ( 'map-pin' === $icon ) {
		$label = soleil_icon( 'map-pin' ) . $label;
	} elseif ( 'geen' !== $icon ) {
		$label .= soleil_icon( '' !== $icon ? $icon : ( $blank ? 'arrow-up-right' : 'arrow-right' ) );
	}
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $href ) . '"' . ( $blank ? soleil_blank() : '' ) . '>' . $label . '</a>';
}
