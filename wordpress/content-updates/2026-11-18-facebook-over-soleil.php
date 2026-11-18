<?php
/**
 * Over Soleil: naast "Volg Soleil op Instagram" ook een link naar Facebook.
 * Werkt op de inhoud die er staat (de staging houdt Saskia's eigen teksten) en doet niets als de link er al is.
 * De link gaat naar het Facebook-adres bij Soleil > Praktisch.
 *
 *   wp eval-file content-updates/2026-11-18-facebook-over-soleil.php           (lokaal: wordpress/bin/wp eval-file …)
 */

$post = get_page_by_path( 'over-soleil' );
if ( ! $post ) {
	WP_CLI::error( 'Pagina Over Soleil niet gevonden.' );
}
if ( str_contains( $post->post_content, 'href="/facebook/"' ) ) {
	WP_CLI::success( 'De link naar Facebook staat al op Over Soleil. Niets gedaan.' );
	return;
}

$pattern = '#(<!-- wp:buttons \{"htmlAttrs":\{"tag":"p","style":"margin-top:28px)("\}\} -->\s*<div class="wp-block-buttons"><!-- wp:button \{"className":"is-style-link"\} -->\s*<div class="wp-block-button is-style-link"><a [^>]*href="/instagram/"[^>]*>.*?</a></div>\s*<!-- /wp:button -->)#s';

$button = "\n\n" . '<!-- wp:button {"className":"is-style-link"} -->' . "\n"
	. '<div class="wp-block-button is-style-link"><a class="wp-block-button__link wp-element-button" href="/facebook/" target="_blank" rel="noreferrer noopener">Volg Soleil op Facebook</a></div>' . "\n"
	. '<!-- /wp:button -->';

$content = preg_replace_callback(
	$pattern,
	fn( $m ) => $m[1] . ';display:flex;flex-wrap:wrap;gap:12px 32px' . $m[2] . $button,
	$post->post_content,
	1,
	$count
);
if ( 1 !== $count ) {
	WP_CLI::error( 'De link naar Instagram staat niet zoals verwacht op Over Soleil. Zet de link met de hand.' );
}

$result = wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( 'Link naar Facebook toegevoegd aan Over Soleil.' );
