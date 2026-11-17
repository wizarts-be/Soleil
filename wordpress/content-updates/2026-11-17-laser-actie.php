<?php
/**
 * Laser: op de staging stond "Eigen paginaopbouw" (make-up) aan voor een actie, waardoor de gewone pagina
 * (titel, intro, Herken je dit?, laserdata) verdween. Dit zet de pagina terug op de gewone opbouw, zonder iets
 * van Saskia's inhoud te verliezen:
 *   - het merkblok (promo oksels, voorwaarden, foto) wordt het blok "Actie" (H2 "Actie van de maand");
 *   - de diensten worden prijsrijen in het prijzenvak, zoals bij de andere behandelingen (vanaf 4 prijzen blokken);
 *   - de make-up-opbouw gaat uit. Een kopie van de oude waarde blijft in de meta makeup_backup_2026_11_17.
 * Doet niets als de make-up-opbouw al uit staat.
 *
 *   wp eval-file content-updates/2026-11-17-laser-actie.php
 */

$post = get_page_by_path( 'laser', OBJECT, 'behandeling' );
if ( ! $post ) {
	WP_CLI::error( 'Behandeling laser niet gevonden.' );
}
$raw = (string) get_post_meta( $post->ID, 'makeup', true );
$old = json_decode( $raw, true );
if ( ! $old ) {
	WP_CLI::success( 'De make-up-opbouw staat al uit op laser. Niets gedaan.' );
	return;
}
$save = fn( $key, $value ) => update_post_meta( $post->ID, $key, wp_slash( is_string( $value ) ? $value : wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );

$save( 'makeup_backup_2026_11_17', $raw );

// Actie uit het merkblok.
$story = (array) ( $old['verhaal'] ?? array() );
$text  = implode( "\n\n", array_filter( array_map( 'trim', (array) ( $story['tekst'] ?? array() ) ), 'strlen' ) );
if ( '' !== $text || ! empty( $story['foto'] ) ) {
	$save( 'actie', array(
		'label' => (string) ( $story['label'] ?? '' ),
		'titel' => '' !== trim( (string) ( $story['titel'] ?? '' ) ) ? $story['titel'] : 'Actie van de maand',
		'tekst' => $text,
		'foto'  => (int) ( $story['foto'] ?? 0 ),
		'alt'   => (string) ( $story['alt'] ?? '' ),
	) );
}

// Diensten als prijsrijen in het prijzenvak (weergave automatisch: vanaf precies 4 prijzen blokken).
$rows = array();
foreach ( (array) ( $old['diensten'] ?? array() ) as $d ) {
	if ( '' === trim( (string) ( $d['naam'] ?? '' ) ) ) {
		continue;
	}
	$rows[] = array(
		'naam'  => (string) $d['naam'],
		'duur'  => (string) ( $d['duur'] ?? '' ),
		'prijs' => (string) ( $d['prijs'] ?? '' ),
		'kaart' => (string) ( $d['tekst'] ?? '' ),
	);
}
if ( $rows ) {
	$prices = json_decode( (string) get_post_meta( $post->ID, 'prijzen', true ), true ) ?: array();
	$prices[] = array( 'titel' => '', 'rijen' => $rows );
	$save( 'prijzen', $prices );
}

update_post_meta( $post->ID, 'makeup', '' );
WP_CLI::success( 'Laser terug op de gewone opbouw: actie ' . ( '' !== $text ? 'overgezet' : 'leeg' ) . ', ' . count( $rows ) . ' prijs/prijzen.' );
