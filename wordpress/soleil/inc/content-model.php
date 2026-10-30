<?php
/**
 * Inhoudstypes: behandelingen (met groep), activiteiten, producten, merken en reviews.
 * Het journaal gebruikt de gewone berichten. Alle velden zijn gewone post meta.
 */

defined( 'ABSPATH' ) || exit;

function soleil_labels( $singular, $plural, $new = 'Nieuwe' ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'menu_name'          => $plural,
		'add_new'            => $new . ' toevoegen',
		'add_new_item'       => $singular . ' toevoegen',
		'edit_item'          => $singular . ' bewerken',
		'new_item'           => $new . ' ' . strtolower( $singular ),
		'view_item'          => $singular . ' bekijken',
		'search_items'       => $plural . ' zoeken',
		'not_found'          => 'Niets gevonden',
		'not_found_in_trash' => 'Niets gevonden in de prullenbak',
		'all_items'          => 'Alle ' . strtolower( $plural ),
	);
}

add_action( 'init', function () {
	register_post_type( 'behandeling', array(
		'labels'        => soleil_labels( 'Behandeling', 'Behandelingen' ),
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false,
		// with_front: false, anders komen behandelingen onder /journaal/ (de permalinkstructuur van het journaal).
		'rewrite'       => array( 'slug' => 'behandelingen', 'with_front' => false ),
		'menu_icon'     => 'dashicons-heart',
		'menu_position' => 20,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
		'template'      => array( array( 'soleil/behandelingkaart' ) ),
		'template_lock' => 'all',
	) );

	register_taxonomy( 'groep', 'behandeling', array(
		'labels'            => soleil_labels( 'Groep', 'Groepen' ),
		'public'            => false,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'rewrite'           => false,
	) );
	register_term_meta( 'groep', 'volgorde', array( 'type' => 'integer', 'single' => true, 'default' => 0, 'show_in_rest' => true ) );

	$simple = array(
		'public'             => false,
		'show_ui'            => true,
		'show_in_rest'       => true,
		'publicly_queryable' => false,
		'has_archive'        => false,
		'rewrite'            => false,
	);

	register_post_type( 'activiteit', $simple + array(
		'labels'        => soleil_labels( 'Activiteit', 'Activiteiten' ),
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 21,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'product', $simple + array(
		'labels'        => soleil_labels( 'Product', 'Producten', 'Nieuw' ),
		'menu_icon'     => 'dashicons-products',
		'menu_position' => 22,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'merk', $simple + array(
		'labels'        => soleil_labels( 'Merk', 'Merken', 'Nieuw' ),
		'menu_icon'     => 'dashicons-tag',
		'menu_position' => 23,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'review', $simple + array(
		'labels'        => soleil_labels( 'Review', 'Reviews' ),
		'menu_icon'     => 'dashicons-format-quote',
		'menu_position' => 24,
		'supports'      => array( 'title', 'page-attributes' ),
	) );

	// Pagina's krijgen een samenvatting: die tekst is de beschrijving in zoekmachines.
	add_post_type_support( 'page', 'excerpt' );
} );

/* ---------- Velden ---------- */

/**
 * Velden per type: sleutel => array( type, label, uitleg ).
 * Types: string, text (meerdere regels), integer, number, boolean, json, url, dates.
 */
function soleil_fields() {
	return array(
		'behandeling' => array(
			'sub'              => array( 'string' ),
			'kicker'           => array( 'string' ),
			'kop'              => array( 'string' ),
			'intro'            => array( 'text' ),
			'vanaf'            => array( 'number' ),
			'foto_alt'         => array( 'string' ),
			'tip'              => array( 'text' ),
			'noot'             => array( 'text' ),
			'link_url'         => array( 'string' ),
			'link_label'       => array( 'string' ),
			'extra_activiteit' => array( 'integer' ),
			'prijzen'          => array( 'json' ),
			'waarom'           => array( 'json' ),
			'extra'            => array( 'json' ),
			'delen'            => array( 'json' ),
			'makeup'           => array( 'json' ),
		),
		'activiteit'  => array(
			'tekst'      => array( 'text', 'Tekst' ),
			'data'       => array( 'dates', 'Data', 'Voorbije data verdwijnen vanzelf van de site. Er staan er hoogstens 3 op de pagina Nieuw.' ),
			'geen_data'  => array( 'string', 'Tekst zonder data', 'Staat er als er geen komende data zijn, bv. "Datum volgt".' ),
			'link_url'   => array( 'url', 'Link', 'Bv. /behandelingen/laser/.' ),
			'link_label' => array( 'string', 'Tekst van de link', 'Bv. Meer over laserontharing.' ),
			'voorbeeld'  => array( 'boolean', 'Voorbeeldinhoud', 'Aan = op de pagina Nieuw staat dat de inhoud nog aangevuld wordt.' ),
		),
		'product'     => array(
			'merk'      => array( 'string', 'Merk', 'Staat klein boven de naam.' ),
			'tekst'     => array( 'text', 'Tekst' ),
			'link_url'  => array( 'url', 'Link', 'Leeg = de webshop.' ),
			'voorbeeld' => array( 'boolean', 'Voorbeeldinhoud', 'Aan = op de pagina Nieuw staat dat de inhoud nog aangevuld wordt.' ),
		),
		'merk'        => array(
			'regel' => array( 'text', 'Korte regel', 'Staat onder de naam. De foto kies je rechts; zonder foto staat de naam in de tegel.' ),
		),
		'review'      => array(
			'tekst'     => array( 'text', 'Review', 'Letterlijk zoals de klant ze schreef.' ),
			'plaats'    => array( 'string', 'Behandeling', 'Kleine regel onder de naam, bv. Huidverbetering.' ),
			'voorbeeld' => array( 'boolean', 'Voorbeeldreview', 'Aan = op de homepagina staat dat dit voorbeeldreviews zijn.' ),
		),
	);
}

add_action( 'init', function () {
	$auth = fn() => current_user_can( 'edit_posts' );
	foreach ( soleil_fields() as $type => $fields ) {
		foreach ( $fields as $key => $field ) {
			$kind = $field[0];
			$args = array(
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => $auth,
			);
			switch ( $kind ) {
				case 'integer':
					$args += array( 'type' => 'integer', 'default' => 0 );
					break;
				case 'number':
					$args += array( 'type' => 'number', 'default' => 0 );
					break;
				case 'boolean':
					$args += array( 'type' => 'boolean', 'default' => false );
					break;
				case 'json':
					$args += array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'soleil_sanitize_json' );
					break;
				case 'dates':
					$args = array_merge( $args, array( 'type' => 'array', 'default' => array(), 'sanitize_callback' => 'soleil_sanitize_dates', 'show_in_rest' => array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ) ) ) );
					break;
				default:
					$args += array( 'type' => 'string', 'default' => '' );
			}
			register_post_meta( $type, $key, $args );
		}
	}
} );

function soleil_sanitize_json( $value ) {
	$value = (string) $value;
	if ( '' === $value ) {
		return '';
	}
	$data = json_decode( $value, true );
	return null === $data ? '' : wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

function soleil_sanitize_dates( $value ) {
	$out = array();
	foreach ( (array) $value as $date ) {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
			$out[] = $date;
		}
	}
	sort( $out );
	return array_values( array_unique( $out ) );
}

/**
 * JSON-veld van een behandeling als array.
 */
function soleil_json( $post_id, $key ) {
	$data = json_decode( (string) get_post_meta( $post_id, $key, true ), true );
	return is_array( $data ) ? $data : array();
}

/* ---------- Eenvoudig formulier voor activiteiten, producten, merken en reviews ---------- */

const SOLEIL_SIMPLE_TYPES = array( 'activiteit', 'product', 'merk', 'review' );

add_filter( 'use_block_editor_for_post_type', fn( $use, $type ) => in_array( $type, SOLEIL_SIMPLE_TYPES, true ) ? false : $use, 10, 2 );

add_filter( 'enter_title_here', function ( $text, $post ) {
	$hints = array(
		'activiteit' => 'Naam van de activiteit, bv. Make-up workshop',
		'product'    => 'Naam van het product',
		'merk'       => 'Naam van het merk',
		'review'     => 'Naam van de klant, bv. Ann G.',
	);
	return $hints[ $post->post_type ] ?? $text;
}, 10, 2 );

add_action( 'add_meta_boxes', function ( $type ) {
	if ( ! in_array( $type, SOLEIL_SIMPLE_TYPES, true ) ) {
		return;
	}
	add_meta_box( 'soleil-fields', 'Gegevens', 'soleil_meta_box', $type, 'normal', 'high' );
} );

function soleil_meta_box( $post ) {
	wp_nonce_field( 'soleil_fields', 'soleil_fields_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( soleil_fields()[ $post->post_type ] as $key => $field ) {
		list( $kind, $label ) = $field;
		$help  = $field[2] ?? '';
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'soleil-' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		switch ( $kind ) {
			case 'text':
				echo '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="soleil[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
				break;
			case 'boolean':
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="soleil[' . esc_attr( $key ) . ']" value="1"' . checked( (bool) $value, true, false ) . '> Aan</label>';
				break;
			case 'dates':
				soleil_dates_field( $id, $key, (array) $value );
				break;
			default:
				echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="soleil[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
		}
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

function soleil_dates_field( $id, $key, $dates ) {
	$row = fn( $date ) => '<li><input type="date" name="soleil[' . esc_attr( $key ) . '][]" value="' . esc_attr( $date ) . '"> <button type="button" class="button-link button-link-delete" data-remove>Verwijderen</button></li>';
	echo '<ul class="soleil-dates" id="' . esc_attr( $id ) . '" style="margin:0">';
	foreach ( $dates as $date ) {
		echo $row( $date ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul><button type="button" class="button" data-add-date>Datum toevoegen</button>';
	echo '<template id="' . esc_attr( $id ) . '-row">' . $row( '' ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<script>
	(function () {
		var list = document.getElementById(<?php echo wp_json_encode( $id ); ?>), tpl = document.getElementById(<?php echo wp_json_encode( $id . '-row' ); ?>);
		list.nextElementSibling.addEventListener('click', function () { list.appendChild(tpl.content.cloneNode(true)); list.lastElementChild.querySelector('input').focus(); });
		list.addEventListener('click', function (e) { if (e.target.matches('[data-remove]')) e.target.closest('li').remove(); });
	})();
	</script>
	<?php
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! in_array( $post->post_type, SOLEIL_SIMPLE_TYPES, true ) || ! isset( $_POST['soleil_fields_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['soleil_fields_nonce'] ), 'soleil_fields' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$input = isset( $_POST['soleil'] ) ? wp_unslash( (array) $_POST['soleil'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( soleil_fields()[ $post->post_type ] as $key => list( $kind ) ) {
		$value = $input[ $key ] ?? '';
		switch ( $kind ) {
			case 'boolean':
				update_post_meta( $post_id, $key, ! empty( $value ) );
				break;
			case 'dates':
				update_post_meta( $post_id, $key, soleil_sanitize_dates( (array) $value ) );
				break;
			case 'text':
				update_post_meta( $post_id, $key, sanitize_textarea_field( $value ) );
				break;
			case 'url':
				update_post_meta( $post_id, $key, '' === trim( $value ) ? '' : ( str_starts_with( trim( $value ), '/' ) ? sanitize_text_field( $value ) : esc_url_raw( $value ) ) );
				break;
			default:
				update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
		}
	}
}, 10, 2 );

// Foto's: de uitgelichte afbeelding heet hier gewoon "Foto".
add_filter( 'admin_post_thumbnail_html', function ( $html, $post_id ) {
	if ( in_array( get_post_type( $post_id ), array( 'activiteit', 'product', 'merk' ), true ) ) {
		$html = str_replace( array( 'Uitgelichte afbeelding instellen', 'Set featured image' ), 'Foto kiezen', $html );
	}
	return $html;
}, 10, 2 );

// Lijsten in de admin volgen dezelfde volgorde als op de site.
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'orderby' ) ) {
		return;
	}
	if ( in_array( $query->get( 'post_type' ), array_merge( array( 'behandeling' ), SOLEIL_SIMPLE_TYPES ), true ) ) {
		$query->set( 'orderby', 'menu_order title' );
		$query->set( 'order', 'ASC' );
	}
} );

// Extra kolommen.
add_filter( 'manage_behandeling_posts_columns', fn( $cols ) => array_slice( $cols, 0, 2 ) + array( 'vanaf' => 'Vanaf' ) + $cols );
add_action( 'manage_behandeling_posts_custom_column', function ( $col, $id ) {
	if ( 'vanaf' === $col ) {
		$from = (float) get_post_meta( $id, 'vanaf', true );
		echo esc_html( $from > 0 ? soleil_euro( $from ) : 'meer info' );
	}
}, 10, 2 );

add_filter( 'manage_activiteit_posts_columns', fn( $cols ) => array_slice( $cols, 0, 2 ) + array( 'data' => 'Komende data' ) + $cols );
add_action( 'manage_activiteit_posts_custom_column', function ( $col, $id ) {
	if ( 'data' === $col ) {
		$dates = soleil_upcoming( (array) get_post_meta( $id, 'data', true ), 0 );
		echo esc_html( $dates ? implode( ', ', $dates ) : (string) get_post_meta( $id, 'geen_data', true ) );
	}
}, 10, 2 );

/**
 * Komende data als "Donderdag 29 oktober 2026", zoals in site.ts. Een datum telt mee tot het einde van die dag.
 * $limit 0 = alle.
 */
function soleil_upcoming( $dates, $limit = 3 ) {
	$today = wp_date( 'Y-m-d' );
	$out   = array();
	foreach ( soleil_sanitize_dates( $dates ) as $date ) {
		if ( $date < $today ) {
			continue;
		}
		$time  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' 12:00', wp_timezone() );
		$out[] = ucfirst( wp_date( 'l j F Y', $time->getTimestamp() ) );
	}
	return $limit ? array_slice( $out, 0, $limit ) : $out;
}
