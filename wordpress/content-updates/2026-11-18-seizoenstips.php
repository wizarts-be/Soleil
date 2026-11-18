<?php
/**
 * Het journaal heet voortaan Seizoenstips:
 *   - de pagina krijgt de naam en het adres /seizoenstips/ en opent de nieuwste tip (oude adressen sturen vanzelf door, zie het thema);
 *   - de artikels komen onder /seizoenstips/<artikel>/;
 *   - op de home komt de sectie Seizoenstips met de nieuwste tip, boven de laatste band.
 * Werkt op de inhoud die er staat (de staging houdt Saskia's eigen teksten). Elke stap doet niets als ze al gebeurd is.
 * Eerst het thema uitrollen, en op de staging eerst 2026-11-17-tagline-home.php draaien.
 *
 *   wp eval-file content-updates/2026-11-18-seizoenstips.php           (lokaal: wordpress/bin/wp eval-file …)
 */

// Overzichtspagina.
$page = get_post( (int) get_option( 'page_for_posts' ) );
if ( ! $page ) {
	WP_CLI::error( 'Geen overzichtspagina van de artikels gevonden.' );
}
$content = str_replace(
	array( '<li>Journaal</li>', '<h1 class="wp-block-heading h1">Journaal</h1>' ),
	array( '<li>Seizoenstips</li>', '<h1 class="wp-block-heading h1">Seizoenstips</h1>' ),
	$page->post_content
);
if ( 'seizoenstips' !== $page->post_name || $content !== $page->post_content ) {
	$result = wp_update_post( array(
		'ID'           => $page->ID,
		'post_title'   => 'Seizoenstips',
		'post_name'    => 'seizoenstips',
		'post_content' => wp_slash( $content ),
	), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	WP_CLI::log( 'Overzichtspagina hernoemd naar Seizoenstips.' );
}

// Adres van de artikels.
global $wp_rewrite;
if ( '/seizoenstips/%postname%/' !== get_option( 'permalink_structure' ) ) {
	$wp_rewrite->set_permalink_structure( '/seizoenstips/%postname%/' );
	WP_CLI::log( 'Artikels staan nu onder /seizoenstips/.' );
}
flush_rewrite_rules( false );

// Sectie op de home.
$home = get_post( (int) get_option( 'page_on_front' ) );
if ( ! $home ) {
	WP_CLI::error( 'Geen homepagina gevonden.' );
}
if ( str_contains( $home->post_content, 'tips-home' ) ) {
	WP_CLI::success( 'De sectie Seizoenstips staat al op de home.' );
	return;
}

$section = <<<'HTML'
<!-- wp:soleil/el {"tag":"section","attrs":{"aria-labelledby":"tips"},"className":"section tips-home"} -->
<!-- wp:soleil/el {"className":"wrap"} -->
<!-- wp:soleil/el {"className":"sec-head"} -->
<!-- wp:soleil/el -->
<!-- wp:heading {"className":"h1 rv","anchor":"tips"} -->
<h2 id="tips" class="wp-block-heading h1 rv">Seizoenstips</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"muted rv"} -->
<p class="muted rv">Af en toe deel ik een tip voor je huid, afgestemd op het seizoen.</p>
<!-- /wp:paragraph -->
<!-- /wp:soleil/el -->
<!-- /wp:soleil/el -->

<!-- wp:soleil/laatste-tip /-->
<!-- /wp:soleil/el -->
<!-- /wp:soleil/el -->


HTML;

$anchor = '<!-- wp:soleil/el {"tag":"section","attrs":{"style":"padding-bottom:0","aria-labelledby":"cta"}';
if ( 1 !== substr_count( $home->post_content, $anchor ) ) {
	WP_CLI::error( 'De laatste band staat niet (één keer) op de home. Zet de sectie met de hand.' );
}
$result = wp_update_post( array( 'ID' => $home->ID, 'post_content' => wp_slash( str_replace( $anchor, $section . $anchor, $home->post_content ) ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( 'Sectie Seizoenstips toegevoegd aan de home.' );
