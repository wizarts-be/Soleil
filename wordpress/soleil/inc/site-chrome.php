<?php
/**
 * Header, footer, nieuwsbrief en de head-tags, zoals Header.astro, Footer.astro, Newsletter.astro en Base.astro.
 * De behandelingen komen uit het inhoudstype Behandeling, de rest uit de instellingen (Soleil in het menu),
 * ook de lijst behandelingen in de footer: een WordPress-menu zou het recht edit_theme_options vragen,
 * en daarmee ziet een redacteur ook thema's en de site-editor.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Behandelingen per groep, in menuvolgorde: array( array( 'title' => 'Huidverbetering', 'slug' => …, 'items' => WP_Post[] ) ).
 */
function soleil_treatment_groups() {
	if ( ! taxonomy_exists( 'groep' ) ) {
		return array();
	}
	$terms = get_terms( array(
		'taxonomy'   => 'groep',
		'hide_empty' => true,
		'meta_key'   => 'volgorde',
		'orderby'    => 'meta_value_num',
		'order'      => 'ASC',
	) );
	$groups = array();
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		$groups[] = array(
			'title' => $term->name,
			'slug'  => $term->slug,
			'items' => get_posts( array(
				'post_type'   => 'behandeling',
				'numberposts' => -1,
				'orderby'     => 'menu_order title',
				'order'       => 'ASC',
				'tax_query'   => array( array( 'taxonomy' => 'groep', 'terms' => $term->term_id ) ),
			) ),
		);
	}
	return $groups;
}

function soleil_treatment_url( $slug ) {
	return home_url( '/behandelingen/' . $slug . '/' );
}

/* ---------- Header ---------- */

function soleil_render_site_header() {
	$booking = soleil_setting( 'booking' );
	$groups  = soleil_treatment_groups();
	$path    = soleil_current_path();

	$lists = '';
	foreach ( $groups as $g ) {
		$items = '';
		foreach ( $g['items'] as $t ) {
			$href   = '/behandelingen/' . $t->post_name . '/';
			$items .= '<li><a href="' . esc_url( home_url( $href ) ) . '"' . soleil_aria_current( $href ) . '><span>' . esc_html( get_the_title( $t ) ) . '</span></a></li>';
		}
		$lists .= '<div><p class="label">' . esc_html( $g['title'] ) . '</p><ul role="list">' . $items . '</ul></div>';
	}

	$discovery = get_page_by_path( 'skin-discovery', OBJECT, 'behandeling' );
	$card_img  = $discovery && has_post_thumbnail( $discovery )
		? soleil_img( get_post_thumbnail_id( $discovery ), array( 'alt' => '', 'loading' => 'lazy' ) )
		: '';
	$card = '<a class="menu-card" href="' . esc_url( soleil_treatment_url( 'skin-discovery' ) ) . '">' . $card_img
		. '<span><small>Nieuw bij Soleil?</small>Start met een Skin Discovery</span></a>';

	$nav = '';
	foreach ( array( '/tarieven/' => 'Tarieven', '/nieuw/' => 'Nieuw', '/over-soleil/' => 'Over Soleil', '/contact/' => 'Contact' ) as $href => $label ) {
		$nav .= '<a class="nv-item" href="' . esc_url( home_url( $href ) ) . '"' . soleil_aria_current( $href ) . '>' . esc_html( $label ) . '</a>';
	}

	$pages = '';
	foreach ( array( array( '/', 'Home' ), array( '/tarieven/', 'Alle behandelingen & prijzen' ), array( '/tarieven/', 'Tarieven' ), array( '/nieuw/', 'Nieuw' ), array( '/over-soleil/', 'Over Soleil' ), array( '/contact/', 'Contact' ) ) as list( $href, $label ) ) {
		$pages .= '<li><a href="' . esc_url( home_url( $href ) ) . '"' . soleil_aria_current( $href ) . '>' . esc_html( $label ) . '</a></li>';
	}

	return '<a class="skip" href="#main">Naar de inhoud</a>'
		. '<header class="hd" data-header><div class="wrap hd-main">'
		. '<a class="brand" href="' . esc_url( home_url( '/' ) ) . '" aria-label="Instituut Soleil, naar de homepagina">' . soleil_logo()
		. '<span class="brand-word" aria-hidden="true"><small>instituut</small><span>SOLEIL</span></span></a>'
		. '<nav class="nv" aria-label="Hoofdmenu">'
		. '<button class="nv-item" type="button" aria-expanded="false" aria-controls="mega" data-dd-btn' . ( str_starts_with( $path, '/behandelingen/' ) ? ' data-current="true"' : '' ) . '>Behandelingen' . soleil_icon( 'caret-down' ) . '</button>'
		. $nav
		. '<a class="btn hd-book" href="' . esc_url( $booking ) . '"' . soleil_blank() . '>Afspraak maken</a>'
		. '</nav>'
		. '<button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu" data-menu-btn><span data-menu-label>Menu</span><i aria-hidden="true"></i></button>'
		. '</div>'
		. '<div class="mega" id="mega" data-mega><div class="wrap mega-in">' . $lists
		. '<a class="mega-all" href="' . esc_url( home_url( '/tarieven/' ) ) . '"' . soleil_aria_current( '/tarieven/' ) . '>Alle behandelingen &amp; prijzen' . soleil_icon( 'arrow-right' ) . '</a>'
		. $card . '</div></div></header>'
		. '<div class="menu" id="menu" data-sheet><div class="wrap menu-in">'
		. '<nav class="menu-treat" aria-label="Behandelingen">' . $lists . '</nav>'
		. '<div class="menu-side"><nav aria-label="Pagina\'s"><ul role="list">' . $pages . '</ul></nav>' . $card
		. '<p class="menu-contact"><a href="' . esc_attr( soleil_setting( 'phone_href' ) ) . '">' . soleil_icon( 'phone' ) . esc_html( soleil_setting( 'phone' ) ) . '</a>'
		. '<a href="' . esc_url( soleil_setting( 'route' ) ) . '"' . soleil_blank() . '>' . soleil_icon( 'map-pin' ) . esc_html( soleil_setting( 'street' ) ) . ', Beervelde</a></p>'
		. '</div></div></div>'
		. '<a class="dock btn" href="' . esc_url( $booking ) . '"' . soleil_blank() . ' data-dock>Afspraak maken</a>';
}

/* ---------- Footer ---------- */

function soleil_render_site_footer() {
	// De lijst staat op de instellingenpagina (Soleil > Footer), in de woorden van Saskia.
	$treatments = '';
	foreach ( (array) soleil_setting( 'footer_items' ) as $item ) {
		$treatments .= '<li><a href="' . esc_url( soleil_treatment_url( $item['behandeling'] ) ) . '">' . esc_html( $item['label'] ) . '</a></li>';
	}

	$tel  = '<a href="' . esc_attr( soleil_setting( 'phone_href' ) ) . '">' . esc_html( soleil_setting( 'phone' ) ) . '</a>';
	$mail = '<a href="mailto:' . esc_attr( soleil_setting( 'email' ) ) . '">' . esc_html( soleil_setting( 'email' ) ) . '</a>';

	return '<footer class="ft"><div class="wrap ft-top">'
		. '<div><p class="h2">' . esc_html( soleil_setting( 'footer_slogan' ) ) . '</p>'
		. '<div class="actions"><a class="btn btn--sunset" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>Afspraak maken' . soleil_icon( 'arrow-up-right' ) . '</a></div>'
		. '<p style="margin-top:28px;max-width:44ch">' . esc_html( soleil_setting( 'footer_note' ) ) . '</p></div>'
		. '<div><h4>Openingsuren</h4>' . soleil_week() . '</div>'
		. '<div><h4>Behandelingen</h4><ul role="list">' . $treatments . '</ul></div>'
		. '<div><h4>Contact</h4>'
		. '<p>' . esc_html( soleil_setting( 'street' ) ) . '<br>' . esc_html( soleil_setting( 'city' ) ) . '</p>'
		. '<p style="margin-top:12px">' . $tel . '<br>' . $mail . '</p>'
		. '<p style="margin-top:12px"><a href="' . esc_url( soleil_setting( 'webshop' ) ) . '"' . soleil_blank() . '>Webshop: producten en cadeaubonnen</a><br>'
		. '<a href="' . esc_url( soleil_setting( 'instagram' ) ) . '"' . soleil_blank() . '>Instagram</a><br>'
		. '<a href="' . esc_url( soleil_setting( 'facebook' ) ) . '"' . soleil_blank() . '>Facebook</a></p>'
		. '</div></div>'
		. '<div class="wrap ft-base"><span>© ' . esc_html( wp_date( 'Y' ) ) . ' Instituut Soleil, btw ' . esc_html( soleil_setting( 'vat' ) ) . '</span>'
		. '<span><a href="' . esc_url( home_url( '/nieuw/' ) ) . '">Nieuw</a>, <a href="' . esc_url( home_url( '/journaal/' ) ) . '">Journaal</a>, <a href="' . esc_url( soleil_setting( 'privacy' ) ) . '">Privacybeleid</a></span>'
		. '</div></footer>';
}

/* ---------- Nieuwsbrief ---------- */

function soleil_render_newsletter() {
	return '<aside class="nl" id="nieuwsbrief" aria-labelledby="nl-title" hidden data-nl>'
		. '<button class="nl-x" type="button" aria-label="Sluiten" data-nl-close><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg></button>'
		. '<form data-nl-form>'
		. '<p class="nl-title" id="nl-title">Schrijf je in voor de nieuwsbrief</p>'
		. '<p class="nl-text">Af en toe huidtips, nieuwe behandelingen en acties in je mailbox.</p>'
		. '<div class="nl-row"><label class="nl-sr" for="nl-email">E-mailadres</label>'
		. '<input id="nl-email" type="email" name="email" required autocomplete="email" placeholder="Je e-mailadres" />'
		. '<button type="submit">Inschrijven</button></div>'
		. '<p class="nl-fine">Uitschrijven kan altijd. <a href="' . esc_url( soleil_setting( 'privacy' ) ) . '">Privacybeleid</a></p>'
		. '<p class="nl-msg" role="status" data-nl-msg></p>'
		. '</form></aside>';
}

/* ---------- Head, zoals Base.astro ---------- */

/**
 * Titel van de pagina zonder sitenaam: het veld "Titel in zoekmachines" of de gewone titel.
 */
function soleil_page_title() {
	if ( is_404() ) {
		return 'Pagina niet gevonden';
	}
	$id = is_home() ? (int) get_option( 'page_for_posts' ) : get_queried_object_id();
	if ( ! $id ) {
		return get_bloginfo( 'name' );
	}
	$seo = trim( (string) get_post_meta( $id, 'seo_titel', true ) );
	return '' !== $seo ? $seo : get_the_title( $id );
}

function soleil_page_description() {
	if ( is_404() ) {
		return 'Deze pagina bestaat niet (meer).';
	}
	$id   = is_home() ? (int) get_option( 'page_for_posts' ) : get_queried_object_id();
	$post = $id ? get_post( $id ) : null;
	if ( ! $post ) {
		return get_bloginfo( 'description' );
	}
	if ( 'behandeling' === $post->post_type ) {
		return get_the_title( $post ) . ' bij Instituut Soleil in Beervelde: ' . get_post_meta( $post->ID, 'sub', true ) . '.';
	}
	if ( 'post' === $post->post_type ) {
		return mb_substr( get_the_excerpt( $post ), 0, 155 );
	}
	return $post->post_excerpt;
}

add_filter( 'pre_get_document_title', function () {
	$title = soleil_page_title();
	return str_contains( $title, 'Soleil' ) ? $title : $title . ' | Instituut Soleil Beervelde';
} );

add_action( 'wp_head', function () {
	echo "<script>document.documentElement.classList.add('js');</script>\n";
}, 1 );

add_action( 'wp_head', function () {
	$title       = wp_get_document_title();
	$description = soleil_page_description();
	$image       = is_singular( 'post' ) && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'full' ) : soleil_default_image_url();
	$hours       = array();
	$codes       = array( 1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa' );
	foreach ( soleil_hours() as $h ) {
		if ( '' !== $h['open'] && '' !== $h['close'] ) {
			$hours[] = $codes[ $h['d'] ] . ' ' . $h['open'] . '-' . $h['close'];
		}
	}
	preg_match( '/^(\d{4})\s+(.*)$/', soleil_setting( 'city' ), $city );
	$ld = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'BeautySalon',
		'name'         => soleil_setting( 'name' ),
		'url'          => home_url( '/' ),
		'telephone'    => preg_replace( '/^tel:/', '', soleil_setting( 'phone_href' ) ),
		'email'        => soleil_setting( 'email' ),
		'image'        => soleil_default_image_url(),
		'address'      => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => soleil_setting( 'street' ),
			'postalCode'      => $city[1] ?? '',
			'addressLocality' => $city[2] ?? '',
			'addressCountry'  => 'BE',
		),
		'openingHours' => $hours,
		'sameAs'       => array( soleil_setting( 'instagram' ), soleil_setting( 'facebook' ) ),
	);

	// Homepagina: de grote foto bovenaan vroeg laden, zoals index.astro.
	if ( is_front_page() && soleil_default_image_url() ) {
		echo '<link rel="preload" as="image" href="' . esc_url( soleil_default_image_url() ) . '" fetchpriority="high">' . "\n";
	}
	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	if ( is_home() ) {
		echo '<link rel="canonical" href="' . esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ) . '">' . "\n";
	}
	echo '<meta name="theme-color" content="#fbf9f6">' . "\n";
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( soleil_asset( 'favicon.svg' ) ) . '" type="image/svg+xml">' . "\n";
	}
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta property="og:locale" content="nl_BE">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 2 );

/**
 * Standaardfoto voor delen en JSON-LD: de behandelfoto van de homepagina (bijlage met bron zaakvoerder-behandeling).
 */
function soleil_default_image_url() {
	$id = soleil_media_by_source( 'public/img/zaakvoerder-behandeling.webp' );
	return $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';
}

/**
 * Bijlage die het importscript uit een bestand in de repo heeft gemaakt (meta _soleil_source).
 */
function soleil_media_by_source( $source ) {
	static $cache = array();
	if ( empty( $cache[ $source ] ) ) {
		$ids              = get_posts( array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_soleil_source',
			'meta_value'  => $source,
		) );
		$cache[ $source ] = $ids ? (int) $ids[0] : 0;
	}
	return $cache[ $source ];
}
