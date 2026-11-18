<?php
/**
 * Behandelingen: de gegevens uit de invulkaart en de blokken die er een pagina van maken,
 * zoals behandelingen/[slug].astro. Ook de lijst, de tarieven en de kaarten op de home lezen hieruit.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alle gegevens van een behandeling, met dezelfde betekenis als het type Treatment in site.ts.
 */
function soleil_treatment( $post ) {
	$post   = get_post( $post );
	$terms  = get_the_terms( $post, 'groep' );
	$group  = $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
	$m      = fn( $key ) => (string) get_post_meta( $post->ID, $key, true );
	$makeup = soleil_json( $post->ID, 'makeup' );
	$extra  = soleil_json( $post->ID, 'extra' );

	// Het extra vak kan de komende data van een activiteit tonen (de laserpagina).
	$activity = (int) get_post_meta( $post->ID, 'extra_activiteit', true );
	if ( $extra && $activity ) {
		$extra['data'] = soleil_upcoming_times( (array) get_post_meta( $activity, 'data', true ) );
	}

	$prices = soleil_clean_prices( soleil_json( $post->ID, 'prijzen' ) );
	// Make-up: de tarieven komen uit de diensten, zoals in site.ts.
	if ( $makeup ) {
		$prices = array( array( 'rijen' => array_map( fn( $s ) => array( 'naam' => $s['naam'] ?? '', 'duur' => $s['duur'] ?? '', 'prijs' => $s['prijs'] ?? '' ), $makeup['diensten'] ?? array() ) ) );
	}

	return array(
		'id'     => $post->ID,
		'slug'   => $post->post_name,
		'name'   => get_the_title( $post ),
		'group'  => $group,
		'url'    => get_permalink( $post ),
		'img'    => (int) get_post_thumbnail_id( $post ),
		'alt'    => $m( 'foto_alt' ),
		'sub'    => $m( 'sub' ),
		'kicker' => $m( 'kicker' ),
		'title'  => $m( 'kop' ),
		'intro'  => $m( 'intro' ),
		'from'   => (float) get_post_meta( $post->ID, 'vanaf', true ),
		'tip'    => $m( 'tip' ),
		'note'   => $m( 'noot' ),
		'link'   => '' !== $m( 'link_url' ) ? array( 'href' => $m( 'link_url' ), 'label' => $m( 'link_label' ) ) : null,
		'why'    => soleil_json( $post->ID, 'waarom' ),
		'extra'  => $extra,
		'parts'  => soleil_json( $post->ID, 'delen' ),
		'prices' => $prices,
		'view'   => $m( 'prijzen_weergave' ),
		'cards_title' => $m( 'kaartjes_titel' ),
		'page'   => $makeup,
		'action' => soleil_json( $post->ID, 'actie' ),
	);
}

/**
 * Alle behandelingen in menuvolgorde (eerst per groep, dan menu_order), zoals treatments in site.ts.
 */
function soleil_treatments() {
	$all = array();
	foreach ( soleil_treatment_groups() as $g ) {
		foreach ( $g['items'] as $post ) {
			$all[] = $post;
		}
	}
	return $all;
}

function soleil_group_anchor( $t ) {
	return $t['group'] ? home_url( '/behandelingen/#' . $t['group']->slug ) : home_url( '/behandelingen/' );
}

function soleil_crumbs( $t ) {
	return '<ol class="crumbs" role="list"><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>'
		. '<li><a href="' . esc_url( soleil_group_anchor( $t ) ) . '">' . esc_html( $t['group'] ? $t['group']->name : '' ) . '</a></li>'
		. '<li aria-current="page">' . esc_html( $t['name'] ) . '</li></ol>';
}

function soleil_current_treatment() {
	$post = get_post();
	return $post && 'behandeling' === $post->post_type ? soleil_treatment( $post ) : null;
}

/* ---------- Behandelingen naast elkaar bovenaan ---------- */

function soleil_block_treatment_switch() {
	$current = get_post();
	$links   = '';
	foreach ( soleil_treatment_groups() as $i => $g ) {
		$links .= $i > 0 ? '<span class="sep" aria-hidden="true"></span>' : '';
		foreach ( $g['items'] as $post ) {
			$links .= '<a href="' . esc_url( get_permalink( $post ) ) . '"' . ( $current && $current->ID === $post->ID ? ' aria-current="page"' : '' ) . '>' . esc_html( get_the_title( $post ) ) . '</a>';
		}
	}
	return '<div class="wrap" style="padding-top:clamp(24px,3vw,40px)"><nav class="tswitch" aria-label="Behandelingen">' . $links . '</nav></div>';
}

/* ---------- De pagina zelf ---------- */

function soleil_block_treatment() {
	$t = soleil_current_treatment();
	if ( ! $t ) {
		return '<header class="wrap thead"><div><h1 class="display">Behandeling</h1></div></header>';
	}
	return $t['page'] ? soleil_treatment_makeup( $t ) : soleil_treatment_default( $t );
}

function soleil_book_button( $class = 'btn', $label = 'Afspraak maken' ) {
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( soleil_setting( 'booking' ) ) . '"' . soleil_blank() . '>' . esc_html( $label ) . soleil_icon( 'arrow-up-right' ) . '</a>';
}

function soleil_treatment_default( $t ) {
	$link = $t['link']
		? '<a class="btn" href="' . esc_url( soleil_link( $t['link']['href'] ) ) . '"' . soleil_blank() . '>' . esc_html( $t['link']['label'] ) . soleil_icon( 'arrow-up-right' ) . '</a>'
		: '';

	// In de hero enkel de eerste alinea van de intro, zoals bij de andere behandelingen. Een langere intro
	// (bv. laser: de uitleg over Cathy) staat verder links onder de hero als gewone tekst.
	$intro = array_values( array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', str_replace( "\r", '', (string) $t['intro'] ) ) ), 'strlen' ) ) ?: array( '' );

	$html = '<header class="wrap thead">'
		. '<figure class="framed rv-img">' . soleil_img( $t['img'], array( 'alt' => $t['alt'], 'fetchpriority' => 'high' ) ) . '</figure>'
		. '<div>' . soleil_crumbs( $t )
		. '<p class="label rv">' . esc_html( '' !== $t['kicker'] ? $t['kicker'] : $t['sub'] ) . '</p>'
		. '<h1 class="display rv">' . esc_html( '' !== $t['title'] ? $t['title'] : $t['name'] ) . '</h1>'
		. '<div class="lede-wrap rv" style="--d:1">' . str_replace( '<p>', '<p class="lede">', soleil_paragraphs( $intro[0] ) ) . '</div>'
		. '<div class="actions rv" style="--d:2">' . ( $link ? $link : soleil_book_button( 'btn', 'Boek hier' ) ) . '</div>'
		. '</div></header>';

	// De kaartjes (als die er zijn) staan op elke behandeling meteen onder de hero, zoals bij make-up.
	$cards = soleil_price_cards( $t );
	$html .= $cards . '<section class="wrap tbody"><div>';
	if ( count( $intro ) > 1 ) {
		$html .= '<div class="tbody-text rv">' . soleil_paragraphs( implode( "\n\n", array_slice( $intro, 1 ) ) ) . '</div>';
	}
	if ( $t['why'] ) {
		$w      = $t['why'];
		$titles = array_replace( array( 'Herken je dit?', 'Wat ik doe', 'Wat jij eraan hebt' ), array_filter( (array) ( $w['titels'] ?? array() ), 'strlen' ) );
		$prob   = is_array( $w['probleem'] ?? '' )
			? '<ul role="list">' . implode( '', array_map( fn( $x ) => '<li>' . esc_html( $x ) . '</li>', $w['probleem'] ) ) . '</ul>'
			: '<p>' . esc_html( $w['probleem'] ?? '' ) . '</p>';
		$html  .= '<div class="why">'
			. '<div class="rv">' . soleil_icon( 'question' ) . '<h3>' . esc_html( $titles[0] ) . '</h3>' . $prob . '</div>'
			. '<div class="rv" style="--d:1">' . soleil_icon( 'hand-heart' ) . '<h3>' . esc_html( $titles[1] ) . '</h3><p>' . esc_html( $w['oplossing'] ?? '' ) . '</p></div>'
			. '<div class="rv" style="--d:2">' . soleil_icon( 'sparkle' ) . '<h3>' . esc_html( $titles[2] ) . '</h3><p>' . esc_html( $w['resultaat'] ?? '' ) . '</p></div>'
			. '</div>';
	}
	if ( $t['parts'] ) {
		$html .= '<div class="parts" style="margin-top:clamp(48px,6vw,80px)">';
		foreach ( $t['parts'] as $p ) {
			$html .= '<article class="part rv"' . ( ! empty( $p['id'] ) ? ' id="' . esc_attr( $p['id'] ) . '"' : '' ) . '>'
				. '<figure class="ph">' . soleil_img( $p['foto'] ?? 0, array( 'alt' => $p['alt'] ?? '', 'loading' => 'lazy' ) ) . '</figure>'
				. '<div><h2 class="h3">' . esc_html( $p['naam'] ?? '' ) . '</h2><p>' . esc_html( $p['tekst'] ?? '' ) . '</p>'
				. soleil_price_groups( soleil_clean_prices( $p['prijzen'] ?? array() ) )
				. '</div></article>';
		}
		$html .= '</div>';
	}
	$extra        = $t['extra'] && '' !== trim( (string) ( $t['extra']['titel'] ?? '' ) ) ? $t['extra'] : null;
	$panel_prices = ! $cards && soleil_has_prices( $t['prices'] );
	if ( $extra && $panel_prices ) {
		// Prijzen in het vak rechts: het extra vak komt links onder de tekst, als eigen blok met eventueel een foto
		// (bv. "Pedicure" bij Handen & voeten), zodat er links geen lege ruimte is en het vak rechts kort blijft.
		$html .= soleil_extra_block( $extra );
		$extra = null;
	}
	$html .= '</div>';

	$panel = '<aside class="panel rv" aria-labelledby="prijzen">';
	if ( $panel_prices ) {
		$panel .= '<h2 id="prijzen">Prijzen</h2>' . soleil_price_groups( $t['prices'] )
			. ( '' !== $t['note'] ? '<p class="price-note">' . esc_html( $t['note'] ) . '</p>' : '' );
	} elseif ( ! $extra ) {
		// Geen prijzen in het vak (blokken of onderdelen) en geen extra vak: enkel de naam boven de knop.
		$panel .= '<h2 id="prijzen">' . esc_html( $t['name'] ) . '</h2>';
	}
	if ( $extra ) {
		// Staan de prijzen niet in het vak (blokken of onderdelen, bv. Skin Expert), dan begint het vak meteen
		// met het extra vak ("In de kijker deze winter") in plaats van "De prijzen staan bij elke behandeling hiernaast".
		$first  = ! str_contains( $panel, 'id="prijzen"' );
		$panel .= '<div class="panel-extra' . ( $first ? ' panel-extra--first' : '' ) . '">'
			. ( $first ? '<h2 id="prijzen" class="label label--sunset">' : '<p class="label label--sunset">' ) . esc_html( $extra['titel'] ) . ( $first ? '</h2>' : '</p>' )
			. soleil_paragraphs( $extra['tekst'] ?? '' )
			. soleil_extra_list( $extra )
			. '</div>';
	}
	if ( '' !== $t['tip'] ) {
		$panel .= '<p class="price-note">' . esc_html( $t['tip'] ) . '</p>';
	}
	$panel .= ( $link ? $link : soleil_book_button() ) . '</aside>';

	return $html . $panel . '</section>' . soleil_action_block( $t['action'] );
}

/**
 * Optioneel blok onderaan een behandeling: kleine regel, H2, tekst en foto (bv. "Actie van de maand" bij laser).
 * Beige vak zoals het extra vak. De foto blijft heel (een actiebeeld heeft vaak tekst in de foto).
 * De eerste alinea is de actie, de alinea's daarna staan kleiner (bv. de voorwaarden).
 */
function soleil_action_block( $a ) {
	if ( ! $a || ( '' === trim( (string) ( $a['titel'] ?? '' ) ) && '' === trim( (string) ( $a['tekst'] ?? '' ) ) ) ) {
		return '';
	}
	$foto  = (int) ( $a['foto'] ?? 0 );
	$parts = preg_split( '/\n\s*\n/', trim( str_replace( "\r", '', (string) ( $a['tekst'] ?? '' ) ) ), 2 );
	return '<section class="wrap action-wrap" aria-labelledby="actie"><div class="action-block rv' . ( $foto ? ' has-photo' : '' ) . '">'
		. ( $foto ? '<figure>' . soleil_img( $foto, array( 'alt' => $a['alt'] ?? '', 'loading' => 'lazy' ) ) . '</figure>' : '' )
		. '<div>' . ( '' !== trim( (string) ( $a['label'] ?? '' ) ) ? '<p class="label">' . esc_html( $a['label'] ) . '</p>' : '' )
		. '<h2 id="actie" class="h2">' . esc_html( '' !== trim( (string) ( $a['titel'] ?? '' ) ) ? $a['titel'] : 'Actie van de maand' ) . '</h2>'
		. soleil_paragraphs( $parts[0] )
		. ( isset( $parts[1] ) ? '<div class="action-small">' . soleil_paragraphs( $parts[1] ) . '</div>' : '' )
		. '</div></div></section>';
}

/**
 * Tekst met lege regels als alinea's; een gewone regeleinde blijft een nieuwe regel.
 */
function soleil_paragraphs( $text ) {
	return implode( '', array_map( fn( $x ) => '<p>' . nl2br( esc_html( $x ), false ) . '</p>', array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', str_replace( "\r", '', (string) $text ) ) ), 'strlen' ) ) );
}

/**
 * Het extra vak als eigen blok in de linkerkolom: beige, met de foto links als die er is.
 */
function soleil_extra_block( $e ) {
	$foto = (int) ( $e['foto'] ?? 0 );
	return '<aside class="extra-block rv' . ( $foto ? ' has-photo' : '' ) . '" aria-labelledby="extra">'
		. ( $foto ? '<figure class="ph">' . soleil_img( $foto, array( 'alt' => $e['alt'] ?? '', 'loading' => 'lazy' ) ) . '</figure>' : '' )
		. '<div><h2 id="extra" class="label label--sunset">' . esc_html( $e['titel'] ?? '' ) . '</h2>'
		. soleil_paragraphs( $e['tekst'] ?? '' )
		. soleil_extra_list( $e )
		. '</div></aside>';
}

/**
 * De lijst in het extra vak: de data van een activiteit per maand, anders Saskia's eigen regels.
 */
function soleil_extra_list( $e ) {
	if ( isset( $e['data'] ) ) {
		return soleil_date_months( $e['data'] );
	}
	$list = array_filter( (array) ( $e['lijst'] ?? array() ), 'strlen' );
	return $list ? '<ul role="list">' . implode( '', array_map( fn( $x ) => '<li>' . esc_html( $x ) . '</li>', $list ) ) . '</ul>' : '';
}

/**
 * Komende data per maand. Zonder komende data een korte zin, zodat het vak nooit leeg staat.
 */
function soleil_date_months( $times ) {
	if ( ! $times ) {
		return '<p>Nieuwe data volgen binnenkort.</p>';
	}
	$months = array();
	foreach ( $times as $time ) {
		$months[ $time->format( 'Y-m' ) ][] = $time;
	}
	$html = '';
	foreach ( $months as $days ) {
		$html .= '<li><span class="date-month">' . esc_html( soleil_month_nl( $days[0] ) . ' ' . $days[0]->format( 'Y' ) ) . '</span><ul role="list">';
		foreach ( $days as $time ) {
			$html .= '<li' . ( $time === $times[0] ? ' class="date-next"' : '' ) . '><time datetime="' . esc_attr( $time->format( 'Y-m-d' ) ) . '">'
				. esc_html( soleil_weekday_nl( $time ) . ' ' . $time->format( 'j' ) ) . '</time></li>';
		}
		$html .= '</ul></li>';
	}
	return '<ul class="date-months" role="list">' . $html . '</ul>';
}

/**
 * Lege prijsrijen (geen naam en geen prijs) en lege tabellen weglaten. In de editor blijft er soms een lege rij
 * of tabel staan; die mag geen gat in het prijzenvak geven en niet meetellen voor de kaartjes.
 */
function soleil_clean_prices( $groups ) {
	$out = array();
	foreach ( (array) $groups as $g ) {
		$g['rijen'] = array_values( array_filter( (array) ( $g['rijen'] ?? array() ), fn( $r ) => '' !== trim( (string) ( $r['naam'] ?? '' ) ) || '' !== trim( (string) ( $r['prijs'] ?? '' ) ) ) );
		if ( $g['rijen'] || '' !== trim( (string) ( $g['noot'] ?? '' ) ) ) {
			$out[] = $g;
		}
	}
	return $out;
}

/**
 * Alle prijsrijen van een behandeling, over de tabellen heen.
 */
function soleil_price_rows( $groups ) {
	$rows = array();
	foreach ( (array) $groups as $g ) {
		foreach ( (array) ( $g['rijen'] ?? array() ) as $r ) {
			$rows[] = $r;
		}
	}
	return $rows;
}

/** De titel boven de blokken: zelf in te vullen per behandeling, standaard "Behandelingen". */
function soleil_cards_title( $t ) {
	return '' !== $t['cards_title'] ? $t['cards_title'] : 'Behandelingen';
}

/**
 * De prijzen als kaartjes, zoals de diensten bij make-up. Automatisch bij precies 4 prijzen; per behandeling
 * kan Saskia kiezen voor altijd kaartjes of altijd het prijzenvak. Geeft '' als het prijzenvak blijft.
 */
function soleil_price_cards( $t ) {
	$rows = soleil_price_rows( $t['prices'] );
	$on   = 'kaartjes' === $t['view'] ? (bool) $rows : ( 'vak' !== $t['view'] && 4 === count( $rows ) );
	if ( ! $on ) {
		return '';
	}
	$items = '';
	foreach ( $rows as $i => $r ) {
		$text   = trim( (string) ( $r['kaart'] ?? '' ) );
		$text   = '' !== $text ? $text : trim( (string) ( $r['toelichting'] ?? '' ) );
		$items .= '<li class="rv" style="--d:' . $i . '"><h3 class="h3">' . esc_html( $r['naam'] ?? '' ) . '</h3>'
			. ( '' !== $text ? '<p>' . esc_html( $text ) . '</p>' : '<p></p>' )
			. '<div class="services-foot"><span>' . esc_html( $r['duur'] ?? '' ) . '</span><strong>' . esc_html( soleil_euro( $r['prijs'] ?? '' ) ) . '</strong></div>'
			. soleil_book_button( 'link', 'Boek nu' ) . '</li>';
	}
	return '<section class="wrap price-cards" aria-labelledby="aanbod"><h2 id="aanbod" class="h2 rv" style="margin-bottom:clamp(28px,4vw,48px)">' . esc_html( soleil_cards_title( $t ) ) . '</h2>'
		. '<ul class="services" role="list">' . $items . '</ul>'
		. ( '' !== $t['note'] ? '<p class="price-note">' . esc_html( $t['note'] ) . '</p>' : '' )
		. '</section>';
}

function soleil_has_prices( $groups ) {
	foreach ( (array) $groups as $g ) {
		if ( ! empty( $g['rijen'] ) ) {
			return true;
		}
	}
	return false;
}

function soleil_treatment_makeup( $t ) {
	$p     = $t['page'];
	$story = $p['verhaal'] ?? array();
	// Gewone tekst onder de titel (vroeger een opsomming van vragen; die lopen nu als tekst achter elkaar).
	$intro = trim( (string) ( $p['tekst'] ?? '' ) );
	if ( '' === $intro ) {
		$intro = implode( ' ', array_filter( array_map( 'trim', (array) ( $p['vragen'] ?? array() ) ), 'strlen' ) );
	}
	$intro = str_replace( '<p>', '<p class="lede">', soleil_paragraphs( $intro ) );

	$html = '<header class="wrap thero"><div>' . soleil_crumbs( $t )
		. '<h1 class="h1 rv">' . esc_html( $p['titel'] ?? '' ) . '</h1>'
		. ( '' !== $intro ? '<div class="intro rv" style="--d:1">' . $intro . '</div>' : '' )
		. '<p class="rv" style="--d:2;margin-top:24px;font-weight:500">' . esc_html( $p['cta'] ?? '' ) . '</p>'
		. '<div class="actions rv" style="--d:2;margin-top:24px">' . soleil_book_button() . '</div>'
		. '</div><figure class="ph rv-img">' . soleil_img( $t['img'], array( 'alt' => $t['alt'], 'fetchpriority' => 'high' ) ) . '</figure></header>';

	$services = '';
	foreach ( (array) ( $p['diensten'] ?? array() ) as $i => $s ) {
		$services .= '<li class="rv" style="--d:' . $i . '"><h3 class="h3">' . esc_html( $s['naam'] ?? '' ) . '</h3>'
			. ( ! empty( $s['tag'] ) ? '<p class="label">' . esc_html( $s['tag'] ) . '</p>' : '' )
			. '<p>' . esc_html( $s['tekst'] ?? '' ) . '</p>'
			. '<div class="services-foot"><span>' . esc_html( $s['duur'] ?? '' ) . '</span><strong>' . esc_html( soleil_euro( $s['prijs'] ?? '' ) ) . '</strong></div>'
			. soleil_book_button( 'link', 'Boek nu' ) . '</li>';
	}
	// De blokjes komen meteen onder de hero, het merkblok daarna.
	$html .= '<section class="wrap" style="padding-top:clamp(40px,5vw,72px)" aria-labelledby="aanbod"><h2 id="aanbod" class="h2 rv" style="margin-bottom:clamp(28px,4vw,48px)">' . esc_html( soleil_cards_title( $t ) ) . '</h2>'
		. '<ul class="services" role="list">' . $services . '</ul></section>';
	$html .= '<section class="wrap section brand-block" aria-labelledby="merk">'
		. '<figure class="ph rv-img">' . soleil_img( $story['foto'] ?? 0, array( 'alt' => $story['alt'] ?? '', 'loading' => 'lazy' ) ) . '</figure>'
		. '<div class="rv"><p class="label">' . esc_html( $story['label'] ?? '' ) . '</p>'
		. '<h2 id="merk" class="h2">' . esc_html( $story['titel'] ?? '' ) . '</h2>'
		. implode( '', array_map( fn( $x ) => '<p>' . esc_html( $x ) . '</p>', (array) ( $story['tekst'] ?? array() ) ) )
		. '</div></section>';

	return $html;
}

/* ---------- Vorige en volgende ---------- */

function soleil_block_treatment_pager() {
	$all     = soleil_treatments();
	$current = get_post();
	$ids     = wp_list_pluck( $all, 'ID' );
	$i       = $current ? array_search( $current->ID, $ids, true ) : false;
	if ( false === $i || count( $all ) < 2 ) {
		return '';
	}
	$prev = $all[ ( $i - 1 + count( $all ) ) % count( $all ) ];
	$next = $all[ ( $i + 1 ) % count( $all ) ];
	return '<nav class="wrap section" style="padding-bottom:0" aria-label="Vorige en volgende behandeling"><div class="pager">'
		. '<a href="' . esc_url( get_permalink( $prev ) ) . '"><small>' . soleil_icon( 'arrow-left' ) . 'Vorige behandeling</small><strong>' . esc_html( get_the_title( $prev ) ) . '</strong></a>'
		. '<a href="' . esc_url( get_permalink( $next ) ) . '"><small>Volgende behandeling' . soleil_icon( 'arrow-right' ) . '</small><strong>' . esc_html( get_the_title( $next ) ) . '</strong></a>'
		. '</div></nav>';
}

/* ---------- Overzicht /behandelingen/ ---------- */

function soleil_block_treatment_list() {
	$html = '';
	foreach ( soleil_treatment_groups() as $g ) {
		$items = '';
		foreach ( $g['items'] as $i => $post ) {
			$t      = soleil_treatment( $post );
			$items .= '<li class="rv" style="--d:' . $i . '"><a href="' . esc_url( $t['url'] ) . '">'
				. '<span class="ph">' . soleil_img( $t['img'], array( 'alt' => '', 'loading' => 'lazy' ) ) . '</span>'
				. '<h2>' . esc_html( $t['name'] ) . '</h2>'
				. '<span class="d">' . esc_html( $t['sub'] ) . '</span>'
				. '<span class="p">' . esc_html( $t['from'] > 0 ? soleil_euro( $t['from'] ) : 'meer info' ) . soleil_icon( 'arrow-right' ) . '</span>'
				. '</a></li>';
		}
		$html .= '<div class="tlist-group" id="' . esc_attr( $g['slug'] ) . '" style="scroll-margin-top:calc(var(--header-h) + 40px)">'
			. '<p class="label">' . esc_html( $g['title'] ) . '</p><ul class="tlist" role="list">' . $items . '</ul></div>';
	}
	return $html;
}

/* ---------- Tarieven ---------- */

/**
 * Behandelingen op de tarievenpagina: alle, behalve wie in "zonder" staat (Skin Discovery staat al bij Huidanalyse en Skin Relax).
 */
function soleil_rate_treatments( $a ) {
	$skip = array_map( 'trim', explode( ',', (string) $a['zonder'] ) );
	return array_values( array_filter( soleil_treatments(), fn( $p ) => ! in_array( $p->post_name, $skip, true ) ) );
}

function soleil_block_rate_chips( $a ) {
	$links = '';
	foreach ( soleil_rate_treatments( $a ) as $post ) {
		$links .= '<a href="#' . esc_attr( $post->post_name ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
	}
	return '<nav class="chips" aria-label="Categorieën">' . $links . '</nav>';
}

function soleil_block_rates( $a ) {
	$html = '';
	foreach ( soleil_rate_treatments( $a ) as $post ) {
		$t     = soleil_treatment( $post );
		$html .= '<section class="rate" id="' . esc_attr( $t['slug'] ) . '" aria-labelledby="r-' . esc_attr( $t['slug'] ) . '">'
			. '<div class="rate-head"><h2 id="r-' . esc_attr( $t['slug'] ) . '">' . esc_html( $t['name'] ) . '</h2><a href="' . esc_url( $t['url'] ) . '">Meer info</a></div>'
			. '<p>' . esc_html( $t['sub'] ) . '</p>'
			. soleil_price_groups( $t['prices'] );
		foreach ( $t['parts'] as $p ) {
			$rows = array();
			foreach ( soleil_clean_prices( $p['prijzen'] ?? array() ) as $g ) {
				$rows = array_merge( $rows, (array) ( $g['rijen'] ?? array() ) );
			}
			$html .= soleil_prices( $rows, $p['naam'] ?? '' );
		}
		$html .= ( '' !== $t['note'] ? '<p class="price-note">' . esc_html( $t['note'] ) . '</p>' : '' )
			. ( $t['link'] ? '<p style="padding-block:12px 8px"><a class="link" href="' . esc_url( soleil_link( $t['link']['href'] ) ) . '"' . soleil_blank() . '>' . esc_html( $t['link']['label'] ) . soleil_icon( 'arrow-up-right' ) . '</a></p>' : '' )
			. '</section>';
	}
	return '<div class="rates">' . $html . '</div>';
}

/* ---------- Kaarten op de home ---------- */

/**
 * items: array( array( 'behandeling' => 'skin-relax' ), array( 'naam' => …, 'sub' => …, 'foto' => id, 'href' => … ) ).
 */
function soleil_block_home_cards( $a ) {
	$html = '';
	foreach ( (array) $a['items'] as $i => $item ) {
		$post = ! empty( $item['behandeling'] ) ? get_page_by_path( $item['behandeling'], OBJECT, 'behandeling' ) : null;
		$t    = $post ? soleil_treatment( $post ) : null;
		$card = array(
			'href' => $t ? $t['url'] : soleil_link( $item['href'] ?? '' ),
			'img'  => (int) ( $item['foto'] ?? 0 ) ?: ( $t ? $t['img'] : 0 ),
			'name' => '' !== ( $item['naam'] ?? '' ) ? $item['naam'] : ( $t ? $t['name'] : '' ),
			'sub'  => '' !== ( $item['sub'] ?? '' ) ? $item['sub'] : ( $t ? $t['sub'] : '' ),
		);
		$href  = str_starts_with( $card['href'], '/' ) ? home_url( $card['href'] ) : $card['href'];
		$html .= '<li class="rv" style="--d:' . $i . '"><a href="' . esc_url( $href ) . '">'
			. '<figure class="ph">' . soleil_img( $card['img'], array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'
			. '<h3>' . esc_html( $card['name'] ) . soleil_icon( 'arrow-up-right' ) . '</h3>'
			. '<p>' . esc_html( $card['sub'] ) . '</p></a></li>';
	}
	return '<ul class="cards" role="list">' . $html . '</ul>';
}
