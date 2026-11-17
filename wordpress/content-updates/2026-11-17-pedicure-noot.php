<?php
/**
 * Handen & voeten: de pedicure-info stond twee keer in het prijzenvak (de noot onder de prijzen en het extra vak
 * "Pedicure"). Saskia's eigen tekst in het extra vak blijft; de noot gaat weg. Enkel als de noot nog de oude tekst is.
 *
 *   wp eval-file content-updates/2026-11-17-pedicure-noot.php
 */

$post = get_page_by_path( 'handen-voeten', OBJECT, 'behandeling' );
if ( ! $post ) {
	WP_CLI::error( 'Behandeling handen-voeten niet gevonden.' );
}
$noot = trim( (string) get_post_meta( $post->ID, 'noot', true ) );
if ( '' === $noot ) {
	WP_CLI::success( 'De noot is al leeg. Niets gedaan.' );
	return;
}
if ( ! str_starts_with( $noot, 'Pedicure bied ik uitsluitend aan' ) ) {
	WP_CLI::error( 'De noot is intussen aangepast, ik laat ze staan: ' . $noot );
}
update_post_meta( $post->ID, 'noot', '' );
WP_CLI::success( 'Noot onder de prijzen van Handen & voeten leeggemaakt.' );
