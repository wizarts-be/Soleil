<?php
/**
 * Home: de band "Even helemaal tijd voor jezelf" onderaan gaat weg. De vorige versie blijft bewaard bij de revisies.
 * Doet niets als de band al weg is. Als laatste draaien: de scripts voor de tagline en de seizoenstips plaatsen hun
 * sectie net boven deze band.
 *
 *   wp eval-file content-updates/2026-11-18-band-weg-home.php           (lokaal: wordpress/bin/wp eval-file …)
 */

/**
 * Knipt de sectie weg die begint bij $anchor, tot en met de bijhorende sluitende blokcomment.
 * Geeft null als de sectie niet (volledig) gevonden wordt.
 */
function soleil_cut_section( $content, $anchor ) {
	$start = strpos( $content, $anchor );
	if ( false === $start ) {
		return null;
	}
	$depth = 0;
	preg_match_all( '#<!-- (/?)wp:soleil/el\b.*?(/?)-->#s', $content, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $start );
	foreach ( $tags as $tag ) {
		if ( '/' === $tag[2][0] ) {
			continue; // Blok zonder inhoud: opent en sluit zichzelf.
		}
		$depth += '/' === $tag[1][0] ? -1 : 1;
		if ( 0 === $depth ) {
			$end = $tag[0][1] + strlen( $tag[0][0] );
			return rtrim( substr( $content, 0, $start ) ) . "\n" . ltrim( substr( $content, $end ) );
		}
	}
	return null;
}

$anchor = '<!-- wp:soleil/el {"tag":"section","attrs":{"style":"padding-bottom:0","aria-labelledby":"cta"}';
$home   = get_post( (int) get_option( 'page_on_front' ) );
if ( ! $home ) {
	WP_CLI::error( 'Geen homepagina gevonden.' );
}
if ( ! str_contains( $home->post_content, $anchor ) ) {
	WP_CLI::success( 'De band staat niet meer op de home. Niets gedaan.' );
	return;
}

$content = soleil_cut_section( $home->post_content, $anchor );
if ( null === $content ) {
	WP_CLI::error( 'De band kon niet netjes afgebakend worden. Haal hem met de hand weg.' );
}
$result = wp_update_post( array( 'ID' => $home->ID, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( 'De band "Even helemaal tijd voor jezelf" is weg van de home.' );
