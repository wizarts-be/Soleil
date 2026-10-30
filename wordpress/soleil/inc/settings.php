<?php
/**
 * Instellingenpagina "Soleil": praktische gegevens, openingsuren, footer en de verzorging in de kijker.
 * Alles staat in één optie, soleil_settings. Het importscript vult ze de eerste keer.
 */

defined( 'ABSPATH' ) || exit;

const SOLEIL_DAYS = array(
	1 => 'Maandag',
	2 => 'Dinsdag',
	3 => 'Woensdag',
	4 => 'Donderdag',
	5 => 'Vrijdag',
	6 => 'Zaterdag',
);

/**
 * Velden per tab: sleutel => array( label, type, uitleg ).
 */
function soleil_settings_fields() {
	$hours = array();
	foreach ( SOLEIL_DAYS as $d => $day ) {
		$hours[ 'hours_' . $d ] = array( $day, 'hours', '' );
	}

	return array(
		'praktisch' => array(
			'title'  => 'Praktisch',
			'fields' => array(
				'name'       => array( 'Naam', 'text', '' ),
				'owner'      => array( 'Zaakvoerder', 'text', '' ),
				'street'     => array( 'Straat en nummer', 'text', '' ),
				'city'       => array( 'Postcode en gemeente', 'text', '' ),
				'phone'      => array( 'Telefoon', 'text', 'Zoals het op de site staat, bv. 0470 22 92 02.' ),
				'phone_href' => array( 'Telefoon als link', 'text', 'Internationaal formaat, bv. tel:+32470229202.' ),
				'email'      => array( 'E-mail', 'email', '' ),
				'booking'    => array( 'Online afspraak maken', 'url', 'Alle knoppen "Afspraak maken" en "Boek hier" gaan naar deze link.' ),
				'webshop'    => array( 'Webshop', 'url', '' ),
				'route'      => array( 'Route plannen', 'url', 'Link naar Google Maps.' ),
				'instagram'  => array( 'Instagram', 'url', '' ),
				'facebook'   => array( 'Facebook', 'url', '' ),
				'vat'        => array( 'Btw-nummer', 'text', '' ),
				'privacy'    => array( 'Privacybeleid', 'url', 'Link in de footer en bij de nieuwsbrief.' ),
			),
		),
		'uren'      => array(
			'title'  => 'Openingsuren',
			'fields' => $hours + array(
				'hours_note' => array( 'Notitie onder de uren', 'text', 'Bv. Zaterdagnamiddag na telefonische afspraak.' ),
			),
		),
		'footer'    => array(
			'title'  => 'Footer',
			'fields' => array(
				'footer_slogan' => array( 'Slogan', 'text', 'De grote zin links in de footer.' ),
				'footer_note'   => array( 'Noot over de telefoon', 'textarea', 'Kleine tekst onder de knop.' ),
			),
		),
		'kijker'    => array(
			'title'  => 'In de kijker',
			'intro'  => 'De verzorging van het seizoen, op de homepagina en op de pagina Nieuw.',
			'fields' => array(
				'spot_season' => array( 'Seizoen', 'text', 'Staat klein boven de naam, bv. herfst.' ),
				'spot_name'   => array( 'Naam van de verzorging', 'text', '' ),
				'spot_text'   => array( 'Tekst', 'textarea', '' ),
				'spot_image'  => array( 'Foto', 'image', '' ),
				'spot_alt'    => array( 'Beschrijving van de foto', 'text', 'Voor wie de foto niet ziet, bv. "Een peeling wordt met een penseel aangebracht".' ),
				'spot_dur'    => array( 'Duur', 'text', 'Bv. 60 min.' ),
				'spot_price'  => array( 'Prijs', 'text', 'Enkel een getal (125) of een tekst (vanaf €95).' ),
				'spot_href'   => array( 'Link "Meer info"', 'text', 'Bv. /behandelingen/skin-expert/#peel.' ),
			),
		),
	);
}

function soleil_settings() {
	static $settings = null;
	if ( null === $settings ) {
		$settings = (array) get_option( 'soleil_settings', array() );
	}
	return $settings;
}

function soleil_setting( $key ) {
	$value = soleil_settings()[ $key ] ?? '';
	return is_array( $value ) ? $value : (string) $value;
}

/**
 * Openingsuren als lijst, zoals hours in site.ts.
 */
function soleil_hours() {
	$out = array();
	foreach ( SOLEIL_DAYS as $d => $day ) {
		$h     = (array) soleil_setting( 'hours_' . $d );
		$out[] = array(
			'd'     => $d,
			'day'   => $day,
			'open'  => (string) ( $h['open'] ?? '' ),
			'close' => (string) ( $h['close'] ?? '' ),
			'note'  => (string) ( $h['note'] ?? '' ),
		);
	}
	return $out;
}

/* ---------- Admin ---------- */

add_action( 'admin_menu', function () {
	add_menu_page( 'Soleil', 'Soleil', 'edit_pages', 'soleil', 'soleil_settings_page', 'dashicons-admin-home', 3 );
} );

add_filter( 'option_page_capability_soleil', fn() => 'edit_pages' );

add_action( 'admin_init', function () {
	register_setting( 'soleil', 'soleil_settings', array(
		'type'              => 'object',
		'sanitize_callback' => 'soleil_sanitize_settings',
		'default'           => array(),
	) );
} );

function soleil_sanitize_settings( $input ) {
	$out = soleil_settings();
	foreach ( soleil_settings_fields() as $tab ) {
		foreach ( $tab['fields'] as $key => list( , $type ) ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$value = $input[ $key ];
			switch ( $type ) {
				case 'hours':
					$out[ $key ] = array(
						'open'  => soleil_sanitize_time( $value['open'] ?? '' ),
						'close' => soleil_sanitize_time( $value['close'] ?? '' ),
						'note'  => sanitize_text_field( $value['note'] ?? '' ),
					);
					break;
				case 'url':
					$out[ $key ] = esc_url_raw( trim( $value ) );
					break;
				case 'email':
					$out[ $key ] = sanitize_email( $value );
					break;
				case 'image':
					$out[ $key ] = absint( $value );
					break;
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $value );
					break;
				default:
					$out[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $out;
}

function soleil_sanitize_time( $value ) {
	return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value ) ? $value : '';
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_soleil' === $hook ) {
		wp_enqueue_media();
	}
} );

function soleil_settings_page() {
	$tabs    = soleil_settings_fields();
	$current = isset( $_GET['tab'], $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'praktisch'; // phpcs:ignore WordPress.Security.NonceVerification
	$tab     = $tabs[ $current ];
	?>
	<div class="wrap soleil-settings">
		<h1>Soleil</h1>
		<p>Gegevens die op meerdere plaatsen op de site staan. Pas ze hier aan, dan veranderen ze overal.</p>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $t ) : ?>
				<a class="nav-tab<?php echo $slug === $current ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=soleil&tab=' . $slug ) ); ?>"><?php echo esc_html( $t['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if ( ! empty( $tab['intro'] ) ) : ?>
			<p><?php echo esc_html( $tab['intro'] ); ?></p>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'soleil' ); ?>
			<table class="form-table" role="presentation">
				<?php if ( 'uren' === $current ) : ?>
					<tr><td colspan="2" style="padding-left:0">Laat open en sluit leeg voor een dag waarop je gesloten bent.</td></tr>
				<?php endif; ?>
				<?php foreach ( $tab['fields'] as $key => list( $label, $type, $help ) ) : ?>
					<tr>
						<th scope="row"><label for="soleil-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><?php soleil_settings_field( $key, $type ); ?>
							<?php if ( $help ) : ?>
								<p class="description"><?php echo esc_html( $help ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Opslaan' ); ?>
		</form>
	</div>
	<?php
}

function soleil_settings_field( $key, $type ) {
	$name  = 'soleil_settings[' . $key . ']';
	$id    = 'soleil-' . $key;
	$value = soleil_setting( $key );

	if ( 'hours' === $type ) {
		$h = (array) $value;
		printf(
			'<span class="soleil-hours"><label>Open <input type="time" id="%1$s" name="%2$s[open]" value="%3$s"></label> <label>Sluit <input type="time" name="%2$s[close]" value="%4$s"></label> <label>Noot <input type="text" class="regular-text" name="%2$s[note]" value="%5$s"></label></span>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $h['open'] ?? '' ),
			esc_attr( $h['close'] ?? '' ),
			esc_attr( $h['note'] ?? '' )
		);
		return;
	}

	if ( 'textarea' === $type ) {
		printf( '<textarea class="large-text" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
		return;
	}

	if ( 'image' === $type ) {
		$id_value = (int) $value;
		$preview  = $id_value ? wp_get_attachment_image( $id_value, 'medium', false, array( 'style' => 'display:block;max-width:240px;height:auto;margin-bottom:8px' ) ) : '';
		printf(
			'<div class="soleil-image" data-soleil-image><div data-preview>%1$s</div><input type="hidden" id="%2$s" name="%3$s" value="%4$s"><button type="button" class="button" data-pick>%5$s</button> <button type="button" class="button-link button-link-delete" data-remove%6$s>Verwijderen</button></div>',
			$preview, // phpcs:ignore WordPress.Security.EscapeOutput
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $id_value ?: '' ),
			$id_value ? 'Andere foto' : 'Foto kiezen',
			$id_value ? '' : ' hidden'
		);
		add_action( 'admin_footer', 'soleil_settings_image_script' );
		return;
	}

	$input = array( 'url' => 'url', 'email' => 'email' )[ $type ] ?? 'text';
	printf( '<input type="%s" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $input ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
}

function soleil_settings_image_script() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
	<script>
	document.querySelectorAll('[data-soleil-image]').forEach(function (box) {
		var input = box.querySelector('input'), preview = box.querySelector('[data-preview]'), pick = box.querySelector('[data-pick]'), remove = box.querySelector('[data-remove]');
		pick.addEventListener('click', function () {
			var frame = wp.media({ title: 'Foto kiezen', library: { type: 'image' }, button: { text: 'Gebruiken' }, multiple: false });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				var src = (a.sizes && a.sizes.medium ? a.sizes.medium : a).url;
				input.value = a.id;
				preview.innerHTML = '<img src="' + src + '" alt="" style="display:block;max-width:240px;height:auto;margin-bottom:8px">';
				pick.textContent = 'Andere foto';
				remove.hidden = false;
			});
			frame.open();
		});
		remove.addEventListener('click', function () { input.value = ''; preview.innerHTML = ''; pick.textContent = 'Foto kiezen'; remove.hidden = true; });
	});
	</script>
	<?php
}
