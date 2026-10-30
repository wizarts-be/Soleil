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
