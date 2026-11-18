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
 * Het officiële logo: zon met "instituut", SOLEIL eronder. De link eromheen draagt de naam (aria-label).
 */
function soleil_logo() {
	return '<img class="logo" src="' . esc_url( soleil_asset( 'logo-soleil.webp' ) ) . '" width="640" height="322" alt="">';
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

/**
 * Foto zoals op de statische site: het originele bestand, zonder srcset.
 * WordPress zou anders een herschaalde versie tonen, en dan verschillen de pixels van de huidige site.
 * $attrs in de volgorde van de Astro-markup, bv. array( 'alt' => '', 'loading' => 'lazy' ).
 */
function soleil_img( $id, $attrs = array() ) {
	$id  = (int) $id;
	$src = $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
	if ( ! $src ) {
		return '';
	}
	$html = '<img src="' . esc_url( $src ) . '"';
	$attrs += array( 'alt' => '' );
	foreach ( $attrs as $name => $value ) {
		if ( 'size' === $name ) {
			// width/height van het origineel, enkel waar de statische site ze ook had.
			$meta  = wp_get_attachment_metadata( $id );
			$html .= ! empty( $meta['width'] ) ? ' width="' . (int) $meta['width'] . '" height="' . (int) $meta['height'] . '"' : '';
			continue;
		}
		if ( null !== $value && false !== $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
	}
	return $html . '>';
}

/**
 * Prijstabel, zoals Prices.astro. $rows: array( array( 'naam', 'duur', 'prijs', 'toelichting' ) ).
 */
function soleil_prices( $rows, $title = '', $note = '' ) {
	$html = '<table class="prices">' . ( '' !== (string) $title ? '<caption>' . esc_html( $title ) . '</caption>' : '' ) . '<tbody>';
	foreach ( $rows as $r ) {
		$sub   = trim( (string) ( $r['toelichting'] ?? '' ) );
		$html .= '<tr><th scope="row">' . esc_html( $r['naam'] ?? '' ) . ( '' !== $sub ? '<small>' . esc_html( $sub ) . '</small>' : '' ) . '</th>'
			. '<td class="dur' . soleil_cell_class( $r['duur'] ?? '' ) . '">' . esc_html( $r['duur'] ?? '' ) . '</td>'
			. '<td class="eur' . soleil_cell_class( soleil_euro( $r['prijs'] ?? '' ) ) . '">' . esc_html( soleil_euro( $r['prijs'] ?? '' ) ) . '</td></tr>';
	}
	$html .= '</tbody></table>';
	return $html . ( '' !== (string) $note ? '<p class="price-note">' . esc_html( $note ) . '</p>' : '' );
}

/**
 * Een groep prijstabellen (meta "prijzen" van een behandeling of een onderdeel).
 */
function soleil_price_groups( $groups ) {
	$html = '';
	foreach ( (array) $groups as $g ) {
		$html .= soleil_prices( $g['rijen'] ?? array(), $g['titel'] ?? '', $g['noot'] ?? '' );
	}
	return $html;
}

/**
 * Duur en prijs blijven op één regel, behalve een lange tekst: die mag afbreken en duwt de prijstabel niet breder.
 */
function soleil_cell_class( $value ) {
	return mb_strlen( (string) $value ) > 16 ? ' is-long' : '';
}
