<?php
/**
 * Admin op maat van Soleil.
 */

defined( 'ABSPATH' ) || exit;

// Reacties staan uit op deze site: menu-item weg.
add_action( 'admin_menu', fn() => remove_menu_page( 'edit-comments.php' ) );
add_action( 'admin_bar_menu', fn( $bar ) => $bar->remove_node( 'comments' ), 999 );

// Berichten heten hier "Journaal".
add_action( 'init', function () {
	$labels = get_post_type_object( 'post' )->labels;

	$labels->name               = 'Journaal';
	$labels->menu_name          = 'Journaal';
	$labels->name_admin_bar     = 'Artikel';
	$labels->singular_name      = 'Artikel';
	$labels->all_items          = 'Alle artikels';
	$labels->add_new            = 'Nieuw artikel';
	$labels->add_new_item       = 'Nieuw artikel';
	$labels->edit_item          = 'Artikel bewerken';
	$labels->new_item           = 'Nieuw artikel';
	$labels->view_item          = 'Artikel bekijken';
	$labels->search_items       = 'Artikels zoeken';
	$labels->not_found          = 'Geen artikels gevonden';
	$labels->not_found_in_trash = 'Geen artikels in de prullenbak';
} );

// Startopbouw van een nieuw artikel: de berichtkaart bovenaan, dan de tekst.
add_action( 'init', function () {
	get_post_type_object( 'post' )->template = array(
		array( 'soleil/berichtkaart', array( 'lock' => array( 'move' => true, 'remove' => true ) ) ),
		array( 'core/paragraph', array( 'placeholder' => 'Schrijf hier het artikel. Druk op Enter voor een nieuwe alinea, of op + voor een tussentitel, lijst, foto of de openingsuren.' ) ),
	);
}, 20 );

// Bestaande artikels en behandelingen krijgen hun kaart ook zodra ze in de editor openen.
foreach ( array( 'post' => '{"lock":{"move":true,"remove":true}}', 'behandeling' => '' ) as $soleil_type => $soleil_attrs ) {
	add_filter( 'rest_prepare_' . $soleil_type, function ( $response, $post, $request ) use ( $soleil_type, $soleil_attrs ) {
		$block = 'post' === $soleil_type ? 'soleil/berichtkaart' : 'soleil/behandelingkaart';
		if ( 'edit' !== $request['context'] || has_block( $block, $post ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( isset( $data['content']['raw'] ) ) {
			$card                   = '<!-- wp:' . $block . ( $soleil_attrs ? ' ' . $soleil_attrs : '' ) . ' /-->';
			$data['content']['raw'] = 'behandeling' === $soleil_type ? $card : $card . "\n\n" . $data['content']['raw'];
			$response->set_data( $data );
		}
		return $response;
	}, 10, 3 );
}

// Blokken die in een artikel mogen: tekst, tussentitels, lijsten, foto's en de openingsuren.
add_filter( 'allowed_block_types_all', function ( $allowed, $context ) {
	if ( empty( $context->post ) || 'post' !== $context->post->post_type ) {
		return $allowed;
	}
	return array( 'soleil/berichtkaart', 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/image', 'soleil/openingsuren' );
}, 10, 2 );
