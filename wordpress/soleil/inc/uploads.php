<?php
/**
 * Nieuwe uploads worden even licht als de foto's van nu: hoogstens 1600 px breed, als WebP.
 * Het origineel wordt niet bewaard. De foto's van de import blijven ongewijzigd (SOLEIL_IMPORT).
 */

defined( 'ABSPATH' ) || exit;

const SOLEIL_MAX_WIDTH = 1600;

function soleil_shrink_upload( $upload ) {
	if ( defined( 'SOLEIL_IMPORT' ) || ! empty( $upload['error'] ) ) {
		return $upload;
	}
	if ( ! in_array( $upload['type'], array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'image/avif' ), true ) ) {
		return $upload;
	}
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $upload;
	}
	$editor = wp_get_image_editor( $upload['file'] );
	if ( is_wp_error( $editor ) ) {
		return $upload;
	}
	$size    = $editor->get_size();
	$resize  = $size['width'] > SOLEIL_MAX_WIDTH;
	$convert = 'image/webp' !== $upload['type'];
	if ( ! $resize && ! $convert ) {
		return $upload;
	}
	if ( $resize ) {
		$editor->resize( SOLEIL_MAX_WIDTH, null, false );
	}
	$editor->set_quality( 82 );

	$info   = pathinfo( $upload['file'] );
	$target = $convert
		? $info['dirname'] . '/' . wp_unique_filename( $info['dirname'], $info['filename'] . '.webp' )
		: $upload['file'];
	$saved  = $editor->save( $target, 'image/webp' );
	if ( is_wp_error( $saved ) ) {
		return $upload;
	}
	if ( $saved['path'] !== $upload['file'] ) {
		wp_delete_file( $upload['file'] );
	}
	return array(
		'file' => $saved['path'],
		'url'  => trailingslashit( dirname( $upload['url'] ) ) . wp_basename( $saved['path'] ),
		'type' => 'image/webp',
	);
}
// Upload via de mediabibliotheek of de editor (formulier) en via de REST-API of "van url" (sideload).
add_filter( 'wp_handle_upload', 'soleil_shrink_upload' );
add_filter( 'wp_handle_sideload', 'soleil_shrink_upload' );

// De verkleinde foto is de foto: WordPress maakt er geen "-scaled"-versie meer van.
add_filter( 'big_image_size_threshold', fn() => defined( 'SOLEIL_IMPORT' ) ? 2560 : false );
