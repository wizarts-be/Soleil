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

/* ---------- Vaste pagina's: enkel tekst, links en foto's aanpassen ---------- */

const SOLEIL_LOCKED_PAGES = array( 'home', 'over-soleil', 'contact', 'nieuw', 'tarieven', 'behandelingen', 'journaal', 'pagina-niet-gevonden' );

function soleil_is_locked_page( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}
	return in_array( $post->post_name, SOLEIL_LOCKED_PAGES, true )
		|| in_array( $post->ID, array( (int) get_option( 'page_on_front' ), (int) get_option( 'page_for_posts' ) ), true );
}

// De inhoud van een vaste pagina zit in één omhulsel (soleil/el, tag "pagina") met templateLock contentOnly:
// teksten, links en foto's blijven aanpasbaar, de opbouw niet.
// Ook het hoogste niveau is contentOnly: anders ziet WordPress 7 het omhulsel als een "sectie" met de knop
// "Patroon bewerken", waarmee de vergrendeling open kan. Maar contentOnly houdt op dat niveau het invoegen niet
// tegen, dus mag er op een vaste pagina geen enkel bloktype bijkomen (allowed_block_types_all hieronder).
add_filter( 'block_editor_settings_all', function ( $settings, $context ) {
	if ( ! empty( $context->post ) && soleil_is_locked_page( $context->post ) ) {
		$settings['templateLock'] = 'contentOnly';
	}
	return $settings;
}, 10, 2 );

add_filter( 'allowed_block_types_all', function ( $allowed, $context ) {
	return ! empty( $context->post ) && soleil_is_locked_page( $context->post ) ? false : $allowed;
}, 20, 2 );

// Uitleg bovenaan de editor van een vaste pagina.
add_action( 'enqueue_block_editor_assets', function () {
	$post = get_post();
	if ( ! $post || ! soleil_is_locked_page( $post ) ) {
		return;
	}
	$notice = 'Dit is een vaste pagina: je past de teksten, links en foto\'s aan door erop te klikken. De opbouw blijft zoals ze is. Lijsten zoals behandelingen, prijzen en reviews vullen zich vanzelf.';
	$actions = array();
	if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
		$notice  = 'Deze pagina toont automatisch alle artikels uit het journaal. Hier pas je enkel de titel en de tekst bovenaan aan.';
		$actions = array( array( 'label' => 'Artikels beheren', 'url' => admin_url( 'edit.php' ) ), array( 'label' => 'Nieuw artikel', 'url' => admin_url( 'post-new.php' ) ) );
	}
	wp_add_inline_script( 'wp-edit-post', 'wp.domReady(function(){wp.data.dispatch("core/notices").createInfoNotice(' . wp_json_encode( $notice ) . ',{isDismissible:false,actions:' . wp_json_encode( $actions ) . '});});', 'after' );
} );

/* ---------- Titel in zoekmachines ---------- */

add_action( 'init', function () {
	register_post_meta( 'page', 'seo_titel', array(
		'type'          => 'string',
		'single'        => true,
		'default'       => '',
		'show_in_rest'  => true,
		'auth_callback' => fn() => current_user_can( 'edit_pages' ),
	) );
} );
