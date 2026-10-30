<?php
/**
 * Admin op maat van Soleil.
 */

defined( 'ABSPATH' ) || exit;

// Reacties staan uit op deze site: menu-item weg.
add_action( 'admin_menu', fn() => remove_menu_page( 'edit-comments.php' ) );
add_action( 'admin_bar_menu', fn( $bar ) => $bar->remove_node( 'comments' ), 999 );
