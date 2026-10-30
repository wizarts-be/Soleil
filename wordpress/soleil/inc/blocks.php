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
		'el'           => array(
			'render'     => 'soleil_block_el',
			'attributes' => array(
				'tag'    => array( 'type' => 'string', 'default' => 'div' ),
				'anchor' => array( 'type' => 'string', 'default' => '' ),
				'attrs'  => array( 'type' => 'object', 'default' => array() ),
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

	foreach ( soleil_blocks() as $name => $block ) {
		$render = $block['render'];
		register_block_type( 'soleil/' . $name, array(
			'api_version'           => 3,
			'category'              => 'soleil',
			'attributes'            => ( $block['attributes'] ?? array() ) + array( 'className' => array( 'type' => 'string', 'default' => '' ) ),
			'render_callback'       => empty( $block['content'] )
				? fn( $attrs ) => call_user_func( $render, $attrs )
				: fn( $attrs, $content ) => call_user_func( $render, $attrs, $content ),
			'editor_script_handles' => array( 'soleil-blocks-editor' ),
			'supports'              => array( 'html' => false, 'className' => false, 'customClassName' => false ),
		) );
	}
} );

add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift( $categories, array( 'slug' => 'soleil', 'title' => 'Soleil' ) );
	return $categories;
} );

/* ---------- Container: exact de tag, klassen en attributen van de huidige markup ---------- */

const SOLEIL_EL_TAGS = array( 'div', 'section', 'header', 'footer', 'main', 'article', 'aside', 'nav', 'figure', 'ul', 'ol', 'li', 'a', 'span', 'blockquote' );
const SOLEIL_EL_ATTRS = array( 'role', 'aria-label', 'aria-labelledby', 'aria-current', 'style', 'href', 'target', 'rel', 'tabindex', 'hidden' );

function soleil_block_el( $a, $content ) {
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

/* ---------- Journaal ---------- */

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
	return '<time datetime="' . esc_attr( get_the_date( 'Y-m-d', $post ) ) . '">' . esc_html( get_the_date( 'j F Y', $post ) ) . '</time>';
}

/**
 * Kaartje van een artikel, zoals PostCard.astro.
 */
function soleil_post_card( $post ) {
	$post  = get_post( $post );
	$image = has_post_thumbnail( $post )
		? get_the_post_thumbnail( $post, 'large', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 560px) 100vw, (max-width: 860px) 50vw, 440px' ) )
		: '';
	return '<a class="post rv" href="' . esc_url( get_permalink( $post ) ) . '">'
		. '<figure class="ph">' . $image . '</figure>'
		. '<p class="post-meta"><span class="label">' . esc_html( soleil_post_category( $post ) ) . '</span>' . soleil_post_date( $post ) . '</p>'
		. '<h3 class="h3">' . esc_html( get_the_title( $post ) ) . '</h3>'
		. '<p>' . esc_html( get_the_excerpt( $post ) ) . '</p>'
		. '</a>';
}

/**
 * Het artikel, zoals journaal/[slug].astro: kruimelpad, categorie en datum, titel, foto, tekst en knoppen.
 */
function soleil_block_article() {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type ) {
		return '<article class="wrap article"><header class="ph-head"><h1 class="h1">Titel van het artikel</h1></header></article>';
	}
	$cat   = soleil_post_category( $post );
	$image = has_post_thumbnail( $post )
		? '<figure class="ph article-hero">' . get_the_post_thumbnail( $post, 'full', array( 'alt' => '', 'loading' => false, 'fetchpriority' => 'high', 'sizes' => '(max-width: 760px) 100vw, 760px' ) ) . '</figure>'
		: '';

	$GLOBALS['soleil_in_article'] = true;
	$content                      = apply_filters( 'the_content', $post->post_content );
	$GLOBALS['soleil_in_article'] = false;

	return '<article class="wrap article"><header class="ph-head">'
		. '<ol class="crumbs" role="list"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li><a href="' . esc_url( home_url( '/journaal/' ) ) . '">Journaal</a></li><li aria-current="page">' . esc_html( $cat ) . '</li></ol>'
		. '<p class="post-meta" style="margin-top:24px"><span class="label">' . esc_html( $cat ) . '</span>' . soleil_post_date( $post ) . '</p>'
		. '<h1 class="h1">' . esc_html( get_the_title( $post ) ) . '</h1>'
		. '</header>' . $image
		. '<div class="prose">' . $content
		. '<div class="actions" style="margin-top:56px">'
		. '<a class="btn" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>Afspraak maken' . soleil_icon( 'arrow-up-right' ) . '</a>'
		. '<a class="link" href="' . esc_url( soleil_treatment_url( 'skin-discovery' ) ) . '">Nieuw? Start met een Skin Discovery' . soleil_icon( 'arrow-right' ) . '</a>'
		. '</div></div></article>';
}

/**
 * "Meer uit het journaal": de 3 nieuwste andere artikels.
 */
function soleil_block_more_posts() {
	$post  = get_post();
	$posts = get_posts( array(
		'numberposts'  => 3,
		'post__not_in' => $post ? array( $post->ID ) : array(),
	) );
	return '<section class="section wrap" aria-labelledby="meer">'
		. '<div class="sec-head"><h2 id="meer" class="h2">Meer uit het journaal</h2><a class="link" href="' . esc_url( home_url( '/journaal/' ) ) . '">Alle artikels' . soleil_icon( 'arrow-right' ) . '</a></div>'
		. '<div class="posts">' . implode( '', array_map( 'soleil_post_card', $posts ) ) . '</div></section>';
}

/* ---------- Tekst in artikels: dezelfde markup als nu ---------- */

// Tussentitels in een artikel zijn <h2 class="h3">, lijsten <ul role="list">, alinea's zonder klasse.
// Via de filters per blok, met prioriteit 20: die lopen na de klassen die WordPress zelf toevoegt.
function soleil_article_markup( $html, $block ) {
	if ( empty( $GLOBALS['soleil_in_article'] ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag() ) {
		return $html;
	}
	$p->remove_class( 'wp-block-' . substr( $block['blockName'], 5 ) );
	if ( 'H2' === $p->get_tag() ) {
		$p->add_class( 'h3' );
	}
	if ( 'core/list' === $block['blockName'] ) {
		$p->set_attribute( 'role', 'list' );
	}
	return $p->get_updated_html();
}
foreach ( array( 'core/paragraph', 'core/heading', 'core/list' ) as $soleil_block ) {
	add_filter( 'render_block_' . $soleil_block, 'soleil_article_markup', 20, 2 );
}
