<?php
/**
 * Elke nacht om middernacht (Europe/Brussels) de cache van de pagina's met data leegmaken: voorbije laser- en
 * workshopdata verdwijnen bij weergave, maar een pagina-cache zou de oude versie blijven tonen.
 * Werkt enkel als er een cache-plugin actief is; zonder cache doet de taak niets.
 * WP-cron loopt bij het eerste bezoek na middernacht. Op de server liever een echte cron, zie LIVE.md.
 */

defined( 'ABSPATH' ) || exit;

const SOLEIL_CACHE_HOOK = 'soleil_clear_dated_pages';

/**
 * Pagina's die data tonen. Filter soleil_dated_urls om er toe te voegen.
 */
function soleil_dated_urls() {
	return apply_filters( 'soleil_dated_urls', array( home_url( '/nieuw/' ), home_url( '/behandelingen/laser/' ) ) );
}

/**
 * Leegt de cache van één url via de cache-plugin die actief is. Geeft de namen terug van wat er geleegd werd.
 */
function soleil_purge_url( $url ) {
	$done = array();
	if ( function_exists( 'rocket_clean_files' ) ) { // WP Rocket
		rocket_clean_files( array( $url ) );
		$done[] = 'WP Rocket';
	}
	if ( has_action( 'litespeed_purge_url' ) ) { // LiteSpeed Cache
		do_action( 'litespeed_purge_url', $url );
		$done[] = 'LiteSpeed Cache';
	}
	if ( function_exists( 'w3tc_flush_url' ) ) { // W3 Total Cache
		w3tc_flush_url( $url );
		$done[] = 'W3 Total Cache';
	}
	if ( function_exists( 'wpsc_delete_url_cache' ) ) { // WP Super Cache
		wpsc_delete_url_cache( $url );
		$done[] = 'WP Super Cache';
	}
	if ( has_action( 'cache_enabler_clear_page_cache_by_url' ) ) { // Cache Enabler
		do_action( 'cache_enabler_clear_page_cache_by_url', $url );
		$done[] = 'Cache Enabler';
	}
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) { // SiteGround Speed Optimizer
		sg_cachepress_purge_cache( $url );
		$done[] = 'SiteGround';
	}
	if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'singleDeleteCache' ) ) { // WP Fastest Cache
		$id = url_to_postid( $url );
		if ( $id ) {
			$GLOBALS['wp_fastest_cache']->singleDeleteCache( false, $id );
			$done[] = 'WP Fastest Cache';
		}
	}
	// Cache van de hosting of een andere plugin: koppel hier aan met add_action( 'soleil_purge_url', … ).
	if ( has_action( 'soleil_purge_url' ) ) {
		do_action( 'soleil_purge_url', $url );
		$done[] = 'soleil_purge_url';
	}
	return $done;
}

add_action( SOLEIL_CACHE_HOOK, function () {
	foreach ( soleil_dated_urls() as $url ) {
		soleil_purge_url( $url );
	}
	// Telkens één run op de volgende middernacht, niet "elke 24 uur": dat zou bij zomer- en wintertijd een uur verschuiven.
	wp_schedule_single_event( soleil_next_midnight(), SOLEIL_CACHE_HOOK );
} );

/**
 * Tijdstip van de volgende middernacht in Brussel.
 */
function soleil_next_midnight() {
	return ( new DateTimeImmutable( 'tomorrow', wp_timezone() ) )->getTimestamp();
}

// Inplannen zodra het thema draait (en opnieuw als de taak ontbreekt), weghalen als het thema niet meer actief is.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( SOLEIL_CACHE_HOOK ) ) {
		wp_schedule_single_event( soleil_next_midnight(), SOLEIL_CACHE_HOOK );
	}
} );
add_action( 'switch_theme', fn() => wp_clear_scheduled_hook( SOLEIL_CACHE_HOOK ) );
