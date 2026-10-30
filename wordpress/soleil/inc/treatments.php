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
		$extra['lijst'] = soleil_upcoming( (array) get_post_meta( $activity, 'data', true ), 0 );
	}

	$prices = soleil_json( $post->ID, 'prijzen' );
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
		'page'   => $makeup,
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

	$html = '<header class="wrap thead">'
		. '<figure class="framed rv-img">' . soleil_img( $t['img'], array( 'alt' => $t['alt'], 'fetchpriority' => 'high' ) ) . '</figure>'
		. '<div>' . soleil_crumbs( $t )
		. '<p class="label rv">' . esc_html( '' !== $t['kicker'] ? $t['kicker'] : $t['sub'] ) . '</p>'
		. '<h1 class="display rv">' . esc_html( '' !== $t['title'] ? $t['title'] : $t['name'] ) . '</h1>'
		. '<p class="lede rv" style="--d:1">' . esc_html( $t['intro'] ) . '</p>'
		. '<div class="actions rv" style="--d:2">' . ( $link ? $link : soleil_book_button( 'btn', 'Boek hier' ) ) . '</div>'
		. '</div></header>';

	$html .= '<section class="wrap tbody"><div>';
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
				. soleil_price_groups( $p['prijzen'] ?? array() )
				. '</div></article>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';

	$panel = '<aside class="panel rv" aria-labelledby="prijzen">';
	if ( soleil_has_prices( $t['prices'] ) ) {
		$panel .= '<h2 id="prijzen">Prijzen</h2>' . soleil_price_groups( $t['prices'] )
			. ( '' !== $t['note'] ? '<p class="price-note">' . esc_html( $t['note'] ) . '</p>' : '' );
	} elseif ( $t['parts'] ) {
		$panel .= '<h2 id="prijzen">' . esc_html( $t['name'] ) . '</h2><p class="muted">De prijzen staan bij elke behandeling hiernaast.</p>';
	} else {
		$panel .= '<h2 id="prijzen">' . esc_html( $t['name'] ) . '</h2>';
	}
	if ( $t['extra'] ) {
		$e      = $t['extra'];
		$list   = array_filter( (array) ( $e['lijst'] ?? array() ), 'strlen' );
		$panel .= '<div class="panel-extra"><p class="label label--sunset">' . esc_html( $e['titel'] ?? '' ) . '</p>'
			. ( ! empty( $e['tekst'] ) ? '<p>' . esc_html( $e['tekst'] ) . '</p>' : '' )
			. ( $list ? '<ul role="list">' . implode( '', array_map( fn( $x ) => '<li>' . esc_html( $x ) . '</li>', $list ) ) . '</ul>' : '' )
			. '</div>';
	}
	if ( '' !== $t['tip'] ) {
		$panel .= '<p class="price-note">' . esc_html( $t['tip'] ) . '</p>';
	}
	$panel .= ( $link ? $link : soleil_book_button() ) . '</aside>';

	return $html . $panel . '</section>';
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
	$asks  = implode( '', array_map( fn( $q ) => '<li>' . esc_html( $q ) . '</li>', (array) ( $p['vragen'] ?? array() ) ) );

	$html = '<header class="wrap thero"><div>' . soleil_crumbs( $t )
		. '<h1 class="h1 rv">' . esc_html( $p['titel'] ?? '' ) . '</h1>'
		. '<ul class="asks rv" style="--d:1" role="list">' . $asks . '</ul>'
		. '<p class="rv" style="--d:2;margin-top:24px;font-weight:500">' . esc_html( $p['cta'] ?? '' ) . '</p>'
		. '<div class="actions rv" style="--d:2;margin-top:24px">' . soleil_book_button() . '</div>'
		. '</div><figure class="ph rv-img">' . soleil_img( $t['img'], array( 'alt' => $t['alt'], 'fetchpriority' => 'high' ) ) . '</figure></header>';

	$html .= '<section class="wrap section brand-block" aria-labelledby="merk">'
		. '<figure class="ph rv-img">' . soleil_img( $story['foto'] ?? 0, array( 'alt' => $story['alt'] ?? '', 'loading' => 'lazy' ) ) . '</figure>'
		. '<div class="rv"><p class="label">' . esc_html( $story['label'] ?? '' ) . '</p>'
		. '<h2 id="merk" class="h2">' . esc_html( $story['titel'] ?? '' ) . '</h2>'
		. implode( '', array_map( fn( $x ) => '<p>' . esc_html( $x ) . '</p>', (array) ( $story['tekst'] ?? array() ) ) )
		. '</div></section>';

	$services = '';
	foreach ( (array) ( $p['diensten'] ?? array() ) as $i => $s ) {
		$services .= '<li class="rv" style="--d:' . $i . '"><h3 class="h3">' . esc_html( $s['naam'] ?? '' ) . '</h3>'
			. ( ! empty( $s['tag'] ) ? '<p class="label">' . esc_html( $s['tag'] ) . '</p>' : '' )
			. '<p>' . esc_html( $s['tekst'] ?? '' ) . '</p>'
			. '<div class="services-foot"><span>' . esc_html( $s['duur'] ?? '' ) . '</span><strong>' . esc_html( soleil_euro( $s['prijs'] ?? '' ) ) . '</strong></div>'
			. soleil_book_button( 'link', 'Boek nu' ) . '</li>';
	}
	return $html . '<section class="wrap" aria-labelledby="aanbod"><h2 id="aanbod" class="h2 rv" style="margin-bottom:clamp(28px,4vw,48px)">Make-up behandelingen</h2>'
		. '<ul class="services" role="list">' . $services . '</ul></section>';
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
			foreach ( (array) ( $p['prijzen'] ?? array() ) as $g ) {
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
