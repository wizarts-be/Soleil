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
 * Het artikel, zoals journaal/[slug].astro: kruimelpad, categorie en datum, titel, foto, tekst en knoppen.
 */
function soleil_block_article() {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type ) {
		return '<article class="wrap article"><header class="ph-head"><h1 class="h1">Titel van het artikel</h1></header></article>';
	}
	$cat   = soleil_post_category( $post );
	$image = has_post_thumbnail( $post )
		? '<figure class="ph article-hero">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'fetchpriority' => 'high' ) ) . '</figure>'
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

/* ---------- Verzorging in de kijker (Spotlight.astro) ---------- */

function soleil_block_spotlight( $a ) {
	$href = soleil_setting( 'spot_href' );
	return '<div class="spot">'
		. '<figure class="ph rv-img">' . soleil_img( soleil_setting( 'spot_image' ), array( 'alt' => soleil_setting( 'spot_alt' ), 'loading' => 'lazy' ) ) . '</figure>'
		. '<div class="spot-copy rv">'
		. '<p class="label label--sunset">Verzorging in de kijker · ' . esc_html( soleil_setting( 'spot_season' ) ) . '</p>'
		. '<h2 id="' . esc_attr( $a['kopId'] ) . '" class="h1">' . esc_html( soleil_setting( 'spot_name' ) ) . '</h2>'
		. '<p class="muted">' . esc_html( soleil_setting( 'spot_text' ) ) . '</p>'
		. '<p class="spot-price"><span>' . esc_html( soleil_setting( 'spot_dur' ) ) . '</span><strong>' . esc_html( soleil_euro( soleil_setting( 'spot_price' ) ) ) . '</strong></p>'
		. '<div class="actions">' . soleil_book_button( 'btn', 'Boek deze verzorging' )
		. '<a class="link" href="' . esc_url( str_starts_with( $href, '/' ) ? home_url( $href ) : $href ) . '">Meer info' . soleil_icon( 'arrow-right' ) . '</a></div>'
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
 * Melding dat een lijst nog voorbeeldinhoud bevat. Verdwijnt vanzelf als niets nog als voorbeeld gemarkeerd is.
 */
function soleil_block_example_note( $a ) {
	if ( 'nieuw' === $a['soort'] ) {
		return soleil_has_examples( array( 'product', 'activiteit' ) ) ? '<p class="muted" style="margin-top:12px">Voorbeeldinhoud: de producten en workshopdata vullen we nog aan.</p>' : '';
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
		$html .= '<li class="rv" style="--d:' . $i . '"><figure class="ph">' . $photo . '</figure><h3>' . esc_html( $name ) . '</h3><p>' . esc_html( get_post_meta( $post->ID, 'regel', true ) ) . '</p></li>';
	}
	return '<ul class="btiles" role="list">' . $html . '</ul>';
}

function soleil_block_products() {
	$html = '';
	foreach ( soleil_items( 'product' ) as $i => $post ) {
		$href  = (string) get_post_meta( $post->ID, 'link_url', true );
		$href  = '' !== $href ? soleil_link( $href ) : soleil_setting( 'webshop' );
		$html .= '<li class="rv" style="--d:' . $i . '"><a href="' . esc_url( $href ) . '"' . soleil_blank() . '>'
			. '<figure class="ph">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'
			. '<p class="label">' . esc_html( get_post_meta( $post->ID, 'merk', true ) ) . '</p>'
			. '<h3>' . esc_html( get_the_title( $post ) ) . soleil_icon( 'arrow-up-right' ) . '</h3>'
			. '<p>' . esc_html( get_post_meta( $post->ID, 'tekst', true ) ) . '</p></a></li>';
	}
	return '<ul class="btiles news" role="list">' . $html . '</ul>';
}

function soleil_block_activities() {
	$html = '';
	foreach ( soleil_items( 'activiteit' ) as $i => $post ) {
		$dates = soleil_upcoming( (array) get_post_meta( $post->ID, 'data', true ) );
		if ( ! $dates && '' !== (string) get_post_meta( $post->ID, 'geen_data', true ) ) {
			$dates = array( (string) get_post_meta( $post->ID, 'geen_data', true ) );
		}
		$href  = (string) get_post_meta( $post->ID, 'link_url', true );
		$html .= '<li class="rv" style="--d:' . $i . '">'
			. '<figure class="ph">' . soleil_img( get_post_thumbnail_id( $post ), array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'
			. '<div><h3>' . esc_html( get_the_title( $post ) ) . '</h3>'
			. '<p class="muted">' . esc_html( get_post_meta( $post->ID, 'tekst', true ) ) . '</p>'
			. '<ul class="dates-list" role="list">' . implode( '', array_map( fn( $d ) => '<li>' . esc_html( $d ) . '</li>', $dates ) ) . '</ul>'
			. ( '' !== $href ? '<a class="link" href="' . esc_url( str_starts_with( $href, '/' ) ? home_url( $href ) : $href ) . '">' . esc_html( get_post_meta( $post->ID, 'link_label', true ) ) . soleil_icon( 'arrow-right' ) . '</a>' : '' )
			. '</div></li>';
	}
	return '<ul class="dates" role="list">' . $html . '</ul>';
}

/* ---------- Contact ---------- */

function soleil_block_contact_card() {
	return '<div class="card rv">'
		. '<p class="big-line">' . esc_html( soleil_setting( 'street' ) ) . '<br>' . esc_html( soleil_setting( 'city' ) ) . '</p>'
		. '<p style="margin-top:20px"><a class="link" href="' . esc_attr( soleil_setting( 'phone_href' ) ) . '">' . esc_html( soleil_setting( 'phone' ) ) . '</a></p>'
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
const SOLEIL_CORE_BLOCKS = array( 'core/paragraph', 'core/heading', 'core/list', 'core/buttons', 'core/button' );

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
		// <div class="wp-block-buttons …"> wordt <div class="actions …"> (of de tag uit htmlAttrs).
		$tag   = in_array( $attrs['tag'] ?? 'div', array( 'div', 'p' ), true ) ? ( $attrs['tag'] ?? 'div' ) : 'div';
		$class = trim( (string) ( $block['attrs']['className'] ?? '' ) );
		$inner = preg_replace( '#^\s*<div[^>]*>(.*)</div>\s*$#s', '$1', $html );
		$open  = '<' . $tag . ' class="' . esc_attr( '' !== $class ? $class : 'actions' ) . '"' . soleil_html_attrs( $attrs ) . '>';
		return $open . trim( $inner ) . '</' . $tag . '>';
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
	foreach ( array( 'role', 'style', 'id' ) as $key ) {
		if ( isset( $attrs[ $key ] ) && '' !== $attrs[ $key ] ) {
			$p->set_attribute( $key, $attrs[ $key ] );
		}
	}
	// Links naar /afspraak/, /webshop/, … wijzen naar de link uit de instellingen.
	while ( $p->next_tag( 'a' ) ) {
		$href = (string) $p->get_attribute( 'href' );
		if ( '' !== $href ) {
			$p->set_attribute( 'href', soleil_link( $href ) );
		}
	}
	return $p->get_updated_html();
}
foreach ( array( 'core/paragraph', 'core/heading', 'core/list', 'core/buttons', 'core/button' ) as $soleil_block ) {
	add_filter( 'render_block_' . $soleil_block, 'soleil_core_markup', 20, 2 );
}

function soleil_html_attrs( $attrs ) {
	$out = '';
	foreach ( array( 'role', 'style', 'id' ) as $key ) {
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
