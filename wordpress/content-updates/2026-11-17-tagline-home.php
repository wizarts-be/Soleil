<?php
/**
 * Home: de tagline "Stralen begint bij een huid in balans" als eigen sectie, na Merken en voor de laatste band.
 * Werkt op de inhoud die er staat (de staging houdt Saskia's eigen teksten), en doet niets als de sectie er al is.
 *
 *   wp eval-file content-updates/2026-11-17-tagline-home.php           (lokaal: wordpress/bin/wp eval-file …)
 */

$home = (int) get_option( 'page_on_front' );
$post = get_post( $home );
if ( ! $post ) {
	WP_CLI::error( 'Geen homepagina gevonden.' );
}
if ( str_contains( $post->post_content, '"className":"tagline"' ) ) {
	WP_CLI::success( 'De tagline staat al op de home. Niets gedaan.' );
	return;
}

$img = get_posts( array( 'post_type' => 'attachment', 'name' => 'tagline-soleil-2026-color', 'fields' => 'ids', 'numberposts' => 1 ) );
if ( ! $img ) {
	WP_CLI::error( 'TAGLINE-SOLEIL-2026-COLOR niet gevonden in de media.' );
}

$section = '<!-- wp:soleil/el {"tag":"section","attrs":{"aria-label":"Stralen begint bij een huid in balans"},"className":"tagline"} -->' . "\n"
	. '<!-- wp:soleil/el {"className":"wrap"} -->' . "\n"
	. '<!-- wp:soleil/foto {"id":' . (int) $img[0] . ',"alt":"Stralen begint bij een huid in balans","className":"tagline-img rv"} /-->' . "\n"
	. '<!-- /wp:soleil/el -->' . "\n"
	. '<!-- /wp:soleil/el -->' . "\n\n";

// Voor de laatste band ("Even helemaal tijd voor jezelf"), dus direct na de sectie Merken.
$anchor = '<!-- wp:soleil/el {"tag":"section","attrs":{"style":"padding-bottom:0","aria-labelledby":"cta"}';
if ( 1 !== substr_count( $post->post_content, $anchor ) ) {
	WP_CLI::error( 'De laatste band staat niet (één keer) op de home. Zet de sectie met de hand.' );
}

$content = str_replace( $anchor, $section . $anchor, $post->post_content );
$result  = wp_update_post( array( 'ID' => $home, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( 'Tagline toegevoegd aan de home (foto ' . (int) $img[0] . ').' );
