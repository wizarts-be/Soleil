<?php
/**
 * Seizoenstips: één lege voorbeeldtip die Saskia zelf invult. De bestaande berichten gaan naar de prullenbak (daar blijven
 * ze 30 dagen terug te zetten; neem vooraf een back-up van de database). Doet niets als de voorbeeldtip al bestaat, dus
 * latere tips blijven altijd staan. Na 2026-11-18-seizoenstips.php draaien.
 *
 *   wp eval-file content-updates/2026-11-18-eerste-seizoenstip.php           (lokaal: wordpress/bin/wp eval-file …)
 */

const SOLEIL_EERSTE_TIP = 'eerste-seizoenstip';

$found = get_posts( array( 'post_type' => 'post', 'name' => SOLEIL_EERSTE_TIP, 'post_status' => 'any', 'numberposts' => 1 ) );
if ( $found ) {
	WP_CLI::success( 'De voorbeeldtip bestaat al. Niets gedaan.' );
	return;
}

// De foto van de bestaande tip over peelen, zodat de pagina meteen volledig oogt. Saskia kiest zelf een andere.
$source = get_posts( array( 'post_type' => 'post', 'name' => 'peelen-na-de-zomer', 'post_status' => 'any', 'numberposts' => 1 ) );
$photo  = $source ? (int) get_post_thumbnail_id( $source[0] ) : 0;

$others = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'fields' => 'ids' ) );

$author   = get_user_by( 'login', 'saskia.ferket' );
$category = get_category_by_slug( 'huidtips' );
$content  = '<!-- wp:soleil/berichtkaart {"lock":{"move":true,"remove":true}} /-->' . "\n\n"
	. '<!-- wp:paragraph -->' . "\n"
	. '<p>Hier komt de tekst.</p>' . "\n"
	. '<!-- /wp:paragraph -->';

$id = wp_insert_post( array(
	'post_type'     => 'post',
	'post_status'   => 'publish',
	'post_title'    => 'Titel van je tip',
	'post_name'     => SOLEIL_EERSTE_TIP,
	'post_content'  => wp_slash( $content ),
	'post_author'   => $author ? $author->ID : get_current_user_id(),
	'post_category' => $category ? array( $category->term_id ) : array(),
), true );
if ( is_wp_error( $id ) ) {
	WP_CLI::error( $id->get_error_message() );
}
if ( $photo ) {
	set_post_thumbnail( $id, $photo );
}

foreach ( $others as $other ) {
	wp_trash_post( $other );
}
WP_CLI::success( 'Voorbeeldtip geplaatst (' . $id . '). Naar de prullenbak: ' . count( $others ) . ' bericht(en).' );
