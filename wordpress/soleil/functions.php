<?php
/**
 * Instituut Soleil.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/site-chrome.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/admin.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'style.css', 'assets/css/editor.css' ) );
	remove_theme_support( 'core-block-patterns' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'soleil', get_stylesheet_uri(), array(), soleil_version( 'style.css' ) );
	wp_enqueue_script( 'soleil', get_theme_file_uri( 'assets/js/main.js' ), array(), soleil_version( 'assets/js/main.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );

}, 20 );

// De site steunt niet op de standaardstijlen van WordPress-blokken: alle opmaak staat in style.css.
add_action( 'wp_print_styles', function () {
	foreach ( wp_styles()->queue as $handle ) {
		if ( str_starts_with( $handle, 'wp-block-' ) || 'classic-theme-styles' === $handle ) {
			wp_dequeue_style( $handle );
		}
	}
}, 100 );

// De header heeft zijn eigen skip-link ("Naar de inhoud").
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
remove_action( 'wp_footer', 'the_block_template_skip_link' );

// Teksten blijven letterlijk zoals ingevoerd: geen automatische kastlijnen of gekrulde quotes.
add_filter( 'run_wptexturize', '__return_false' );

// Geen emoji-script, geen oEmbed-ontdekking: de statische site had ze ook niet.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

function soleil_version( $file ) {
	$path = get_theme_file_path( $file );
	return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
}
