<?php
/**
 * Kleine hulpfuncties: iconen, logo, prijzen, openingsuren.
 * Elke functie geeft dezelfde HTML als de Astro-component met dezelfde naam.
 */

defined( 'ABSPATH' ) || exit;

function soleil_asset( $path ) {
	return get_theme_file_uri( 'assets/' . $path );
}

/**
 * Phosphor-icoon (light), zoals Icon.astro: class, aria-hidden en focusable vooraan op de svg.
 */
function soleil_icon( $name, $class = '' ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$file           = get_theme_file_path( 'assets/icons/' . sanitize_file_name( $name ) . '.svg' );
		$cache[ $name ] = file_exists( $file ) ? trim( file_get_contents( $file ) ) : '';
	}
	if ( '' === $cache[ $name ] ) {
		return '';
	}
	return preg_replace( '/^<svg/', '<svg class="' . esc_attr( $class ) . '" aria-hidden="true" focusable="false"', $cache[ $name ], 1 );
}

/**
 * Het Soleil-beeldmerk, zoals Logo.astro.
 */
function soleil_logo( $class = '' ) {
	static $logo = null;
	if ( null === $logo ) {
		$logo = json_decode( (string) file_get_contents( get_theme_file_path( 'assets/logo.json' ) ), true );
	}
	$paths = '';
	foreach ( $logo['paths'] as $d ) {
		$paths .= '<path d="' . esc_attr( $d ) . '"></path>';
	}
	return '<svg class="' . esc_attr( trim( 'logo-mark ' . $class ) ) . '" viewBox="' . esc_attr( $logo['viewBox'] ) . '" fill="currentColor" aria-hidden="true">' . $paths . '</svg>';
}

/**
 * Prijs zoals euro() in site.ts: een getal krijgt een euroteken, tekst ("€25 / €35", "vanaf €95", "gratis") blijft.
 */
function soleil_euro( $value ) {
	$value = trim( (string) $value );
	return preg_match( '/^\d+([.,]\d+)?$/', $value ) ? '€' . $value : $value;
}

/**
 * "09:00" → "9u", "18:30" → "18u30", zoals time() in site.ts.
 */
function soleil_time( $value ) {
	$value = preg_replace( '/:00/', 'u', (string) $value, 1 );
	$value = preg_replace( '/:/', 'u', $value, 1 );
	return preg_replace( '/^0/', '', $value, 1 );
}

/**
 * Openingsuren, zoals Week.astro.
 */
function soleil_week() {
	$html = '<div class="week" data-week>';
	foreach ( soleil_hours() as $h ) {
		$open  = '' !== $h['open'] && '' !== $h['close'];
		$text  = $open ? soleil_time( $h['open'] ) . ' - ' . soleil_time( $h['close'] ) : 'gesloten';
		$text  = esc_html( $text ) . ( '' !== $h['note'] ? ', ' . esc_html( $h['note'] ) : '' );
		$html .= '<div' . ( $open ? '' : ' class="closed"' ) . ' data-day="' . (int) $h['d'] . '"><b>' . esc_html( $h['day'] ) . '</b><span>' . $text . '</span></div>';
	}
	return $html . '<p class="week-note">' . esc_html( soleil_setting( 'hours_note' ) ) . '</p></div>';
}

/**
 * Pad van de huidige pagina, bv. "/tarieven/", om aria-current te zetten zoals in de Astro-header.
 */
function soleil_current_path() {
	global $wp;
	$request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	return '' === $request ? '/' : '/' . $request . '/';
}

function soleil_aria_current( $href ) {
	return soleil_current_path() === $href ? ' aria-current="page"' : '';
}

/**
 * Externe link: target en rel zoals overal op de site.
 */
function soleil_blank() {
	return ' target="_blank" rel="noopener"';
}
