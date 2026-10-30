<?php
/**
 * Oude adressen, zoals public/_redirects van de statische site (301).
 */

defined( 'ABSPATH' ) || exit;

function soleil_redirects() {
	return array(
		'/verwennen/'                       => '/behandelingen/#ontspanning',
		'/behandelingen/gelaatsverzorging/' => '/behandelingen/skin-relax/',
		'/behandelingen/environ-df/'        => '/behandelingen/skin-expert/',
		'/behandelingen/peelings/'          => '/behandelingen/skin-expert/#peel',
		'/behandelingen/microneedling/'     => '/behandelingen/skin-expert/#needling',
		'/behandelingen/neo-lift/'          => '/behandelingen/skin-expert/#neo-lift',
	);
}

// Vóór WordPress een 404 of een "bedoelde je…"-redirect kiest.
add_action( 'template_redirect', function () {
	$path = '/' . ltrim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = trailingslashit( $path );
	$map  = soleil_redirects();
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}
	// /v2/* → /*: de vorige preview stond onder /v2/.
	if ( str_starts_with( $path, '/v2/' ) ) {
		wp_safe_redirect( home_url( substr( $path, 3 ) ), 301 );
		exit;
	}
}, 1 );
