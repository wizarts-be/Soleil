<?php
/**
 * Zet de inhoud van de statische site in WordPress.
 *
 * Gebruik (vanuit de repo-root): wordpress/bin/import          (bouwt data.json en de pagina's, draait dan dit script)
 *
 * Mag opnieuw gedraaid worden: bestaande items (zelfde slug) worden bijgewerkt,
 * foto's worden maar één keer geüpload (bijgehouden in _soleil_source).
 *
 * Slot: na de oplevering staat de optie soleil_opgeleverd aan. Dan weigert de import, want ze zou de teksten,
 * prijzen en instellingen van Saskia overschrijven met die van de statische site. Toch draaien: wordpress/bin/import --force
 */

defined( 'ABSPATH' ) || exit;

if ( get_option( 'soleil_opgeleverd' ) && ! in_array( 'force', $args ?? array(), true ) ) {
	WP_CLI::error( "De site is opgeleverd (optie soleil_opgeleverd): de import zou de aanpassingen van Saskia overschrijven.\nWil je dat echt, draai dan: wordpress/bin/import --force" );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

define( 'SOLEIL_IMPORT_ROOT', dirname( __DIR__, 2 ) );
// Geïmporteerde foto's blijven het originele bestand (zie inc/uploads.php).
define( 'SOLEIL_IMPORT', true );
$data = json_decode( file_get_contents( __DIR__ . '/content/data.json' ), true );
if ( ! $data ) {
	WP_CLI::error( 'content/data.json ontbreekt. Draai eerst: node wordpress/import/build_data.mjs' );
}

function soleil_log( $msg ) {
	WP_CLI::log( $msg );
}

/* ---------------------------------------------------------------- Media */

/**
 * Bijlage voor een bestand in de repo (bv. public/img/peeling.webp). Eén keer uploaden, daarna hergebruiken.
 */
function soleil_import_media( $source ) {
	if ( ! $source ) {
		return 0;
	}
	$existing = soleil_media_by_source( $source );
	if ( $existing ) {
		return $existing;
	}
	$path = SOLEIL_IMPORT_ROOT . '/' . $source;
	if ( ! file_exists( $path ) ) {
		soleil_log( "  ! bestand ontbreekt: $source" );
		return 0;
	}
	$tmp = wp_tempnam( basename( $path ) );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $path ), 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		soleil_log( '  ! upload mislukt: ' . $source . ' (' . $id->get_error_message() . ')' );
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return 0;
	}
	update_post_meta( $id, '_soleil_source', $source );
	soleil_log( '  foto: ' . basename( $path ) );
	return (int) $id;
}

/* ---------------------------------------------------------------- Posts */

function soleil_upsert( $type, $slug, $fields, $meta = array() ) {
	$existing = get_posts( array(
		'post_type'   => $type,
		'name'        => $slug,
		'post_status' => 'any',
		'numberposts' => 1,
	) );
	$postarr = array_merge( array(
		'post_type'      => $type,
		'post_name'      => $slug,
		'post_status'    => 'publish',
		'post_author'    => 1,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	), $fields );
	if ( $existing ) {
		$postarr['ID'] = $existing[0]->ID;
	}
	$id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $id ) ) {
		soleil_log( "  ! $type $slug: " . $id->get_error_message() );
		return 0;
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, is_array( $value ) || is_bool( $value ) ? $value : wp_slash( $value ) );
	}
	return $id;
}

function soleil_json_meta( $value ) {
	return null === $value ? '' : wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/* ---------------------------------------------------------------- Instellingen */

soleil_log( 'Instellingen' );
update_option( 'blogname', 'Instituut Soleil' );
update_option( 'blogdescription', 'Huidverbetering in Beervelde' );
update_option( 'timezone_string', 'Europe/Brussels' );
update_option( 'date_format', 'j F Y' );
update_option( 'time_format', 'H:i' );
update_option( 'start_of_week', 1 );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );
update_option( 'default_pingback_flag', 0 );
update_option( 'permalink_structure', '/journaal/%postname%/' );

$settings = $data['settings'];
foreach ( $settings['hours'] as $d => $h ) {
	$settings[ 'hours_' . $d ] = $h;
}
unset( $settings['hours'] );
$settings['spot_image'] = soleil_import_media( $settings['spot_image'] );
update_option( 'soleil_settings', $settings );

/* ---------------------------------------------------------------- Activiteiten (vóór de behandelingen: de laserpagina verwijst ernaar) */

soleil_log( 'Activiteiten' );
$activities = array();
foreach ( $data['activities'] as $a ) {
	$id = soleil_upsert( 'activiteit', $a['slug'], array( 'post_title' => $a['name'], 'menu_order' => $a['order'] ), $a['meta'] );
	if ( $id ) {
		set_post_thumbnail( $id, soleil_import_media( $a['img'] ) );
		$activities[ $a['slug'] ] = $id;
	}
}

/* ---------------------------------------------------------------- Behandelingen */

soleil_log( 'Behandelingen' );
$groups = array();
foreach ( $data['groups'] as $i => $name ) {
	$term = term_exists( $name, 'groep' ) ?: wp_insert_term( $name, 'groep' );
	$groups[ $name ] = (int) $term['term_id'];
	update_term_meta( $groups[ $name ], 'volgorde', $i );
}

foreach ( $data['treatments'] as $t ) {
	$meta = $t['meta'];
	foreach ( array( 'prijzen', 'waarom', 'extra' ) as $key ) {
		$meta[ $key ] = soleil_json_meta( $meta[ $key ] );
	}
	if ( $meta['delen'] ) {
		foreach ( $meta['delen'] as &$part ) {
			$part['foto'] = soleil_import_media( $part['foto'] );
		}
		unset( $part );
	}
	if ( $meta['makeup'] ) {
		$meta['makeup']['verhaal']['foto'] = soleil_import_media( $meta['makeup']['verhaal']['foto'] );
	}
	$meta['delen']            = soleil_json_meta( $meta['delen'] );
	$meta['makeup']           = soleil_json_meta( $meta['makeup'] );
	$meta['extra_activiteit'] = $t['extra_activiteit'] ? ( $activities[ $t['extra_activiteit'] ] ?? 0 ) : 0;

	$id = soleil_upsert( 'behandeling', $t['slug'], array(
		'post_title'   => $t['name'],
		'menu_order'   => $t['order'],
		'post_content' => '<!-- wp:soleil/behandelingkaart /-->',
	), $meta );
	if ( $id ) {
		wp_set_object_terms( $id, array( $groups[ $t['group'] ] ), 'groep' );
		set_post_thumbnail( $id, soleil_import_media( $t['img'] ) );
		soleil_log( '  ' . $t['name'] );
	}
}

/* ---------------------------------------------------------------- Producten, merken, reviews */

soleil_log( 'Producten, merken en reviews' );
foreach ( array( 'products' => 'product', 'brands' => 'merk', 'reviews' => 'review' ) as $key => $type ) {
	foreach ( $data[ $key ] as $item ) {
		$id = soleil_upsert( $type, $item['slug'], array( 'post_title' => $item['name'], 'menu_order' => $item['order'] ), $item['meta'] );
		if ( $id && ! empty( $item['img'] ) ) {
			set_post_thumbnail( $id, soleil_import_media( $item['img'] ) );
		} elseif ( $id ) {
			delete_post_thumbnail( $id );
		}
	}
}

/* ---------------------------------------------------------------- Journaal */

soleil_log( 'Journaal' );
$cats = array();
foreach ( $data['journal'] as $i => $p ) {
	if ( ! isset( $cats[ $p['cat'] ] ) ) {
		$term              = term_exists( $p['cat'], 'category' ) ?: wp_insert_term( $p['cat'], 'category' );
		$cats[ $p['cat'] ] = (int) $term['term_id'];
	}

	// Blokken uit journal.json → kernblokken. Opeenvolgende lijstitems worden één lijst, zoals in journaal/[slug].astro.
	$blocks = array( '<!-- wp:soleil/berichtkaart {"lock":{"move":true,"remove":true}} /-->' );
	$list   = array();
	$flush  = function () use ( &$list, &$blocks ) {
		if ( $list ) {
			$items    = implode( "\n\n", array_map( fn( $x ) => "<!-- wp:list-item -->\n<li>" . esc_html( $x ) . "</li>\n<!-- /wp:list-item -->", $list ) );
			$blocks[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $items . "</ul>\n<!-- /wp:list -->";
			$list     = array();
		}
	};
	foreach ( $p['blocks'] as $b ) {
		if ( 'li' === $b['type'] ) {
			$list[] = $b['text'];
			continue;
		}
		$flush();
		if ( 'p' === $b['type'] ) {
			$blocks[] = "<!-- wp:paragraph -->\n<p>" . esc_html( $b['text'] ) . "</p>\n<!-- /wp:paragraph -->";
		} elseif ( 'h2' === $b['type'] ) {
			$blocks[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $b['text'] ) . "</h2>\n<!-- /wp:heading -->";
		} elseif ( 'hours' === $b['type'] ) {
			$blocks[] = '<!-- wp:soleil/openingsuren /-->';
		}
	}
	$flush();

	// Zelfde datum: een minuut verschil houdt de volgorde van journal.json aan.
	$id = soleil_upsert( 'post', $p['slug'], array(
		'post_title'    => $p['title'],
		'post_excerpt'  => $p['excerpt'],
		'post_date'     => $p['date'] . ' 12:' . sprintf( '%02d', 59 - $i ) . ':00',
		'post_category' => array( $cats[ $p['cat'] ] ),
		'post_content'  => implode( "\n\n", $blocks ),
	) );
	if ( $id ) {
		set_post_thumbnail( $id, soleil_import_media( $p['img'] ) );
		soleil_log( '  ' . $p['title'] );
	}
}

// Voorbeeldinhoud van een verse installatie.
foreach ( array( 'hello-world', 'hallo-wereld' ) as $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

/* ---------------------------------------------------------------- Vaste pagina's */

soleil_log( "Pagina's" );
// slug => titel in de admin, titel in zoekmachines (leeg = de titel), beschrijving (= samenvatting). Uit de Base-props in src/pages.
$pages = array(
	'home'                 => array( 'Home', 'Instituut Soleil | Huidverbetering in Beervelde', 'Instituut Soleil in Beervelde: huidanalyse, Skin Relax en Skin Expert, ontspanning en eerlijk persoonlijk advies.' ),
	'behandelingen'        => array( 'Behandelingen', '', 'Skin Discovery, huidanalyse, Skin Relax, Skin Expert en ontspanning bij Instituut Soleil in Beervelde.' ),
	'tarieven'             => array( 'Tarieven', 'Alle behandelingen & prijzen', 'Alle behandelingen en prijzen van Instituut Soleil in Beervelde.' ),
	'nieuw'                => array( 'Nieuw', 'Nieuw bij Soleil', 'De verzorging van het seizoen, nieuwe producten en de volgende data voor workshops en laserontharing bij Instituut Soleil.' ),
	'over-soleil'          => array( 'Over Soleil', '', 'Ik ben ' . $data['settings']['owner'] . ', het gezicht achter Instituut Soleil.' ),
	'contact'              => array( 'Contact', '', 'Instituut Soleil, Rivierstraat 70, 9080 Beervelde. Boek online, bel of stuur een bericht.' ),
	'journaal'             => array( 'Journaal', '', 'Huidtips per seizoen en nieuws uit Instituut Soleil in Beervelde.' ),
	'pagina-niet-gevonden' => array( 'Pagina niet gevonden', '', 'Deze pagina bestaat niet (meer).' ),
);
$page_ids = array();
foreach ( $pages as $slug => list( $title, $seo, $excerpt ) ) {
	$markup = (string) file_get_contents( __DIR__ . '/content/pages/' . $slug . '.html' );
	// "{{ID:public/img/x.webp}}" (met aanhalingstekens, in de blokattributen) → bijlage-ID.
	$markup = preg_replace_callback( '/"\{\{ID:([^}]+)\}\}"/', fn( $m ) => (string) soleil_import_media( $m[1] ), $markup );

	$page_ids[ $slug ] = soleil_upsert( 'page', $slug, array(
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'post_content' => $markup,
		'menu_order'   => count( $page_ids ),
		// De 404-pagina is enkel bedoeld voor de 404-template: privé, dus niet zelf bereikbaar.
		'post_status'  => 'pagina-niet-gevonden' === $slug ? 'private' : 'publish',
	), array( 'seo_titel' => $seo ) );
	soleil_log( "  $title" );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['home'] );
update_option( 'page_for_posts', $page_ids['journaal'] );

// Testinhoud van de ontwikkeling en voorbeeldinhoud van een verse installatie.
foreach ( array( 'test', 'sample-page', 'voorbeeld-pagina' ) as $slug ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

/* ---------------------------------------------------------------- Footer: lijst behandelingen (Soleil > Footer) */

$settings                 = get_option( 'soleil_settings', array() );
$settings['footer_items'] = array_map( fn( $f ) => array( 'label' => $f['label'], 'behandeling' => $f['slug'] ), $data['footer'] );
update_option( 'soleil_settings', $settings );

// Vroegere versies gebruikten hiervoor een WordPress-menu.
$old_menu = wp_get_nav_menu_object( 'Footer: behandelingen' );
if ( $old_menu ) {
	wp_delete_nav_menu( $old_menu->term_id );
}

flush_rewrite_rules();
soleil_log( 'Klaar.' );
