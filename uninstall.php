<?php

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// require the minimal files needed to uninstall the plugin
// we could include the main file, but that might trigger other issues
// and does "too much"
if ( ! defined( 'EME_PLUGIN_DIR' ) ) {
    define( 'EME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

require_once EME_PLUGIN_DIR . 'includes/eme-install.php';
require_once EME_PLUGIN_DIR . 'includes/eme-options.php';
require_once EME_PLUGIN_DIR . 'includes/eme-functions.php';

// For Single site
if ( ! is_multisite() ) {
	_eme_uninstall( 1 );
} else {
	// For Multisite
	// For regular options.
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	//$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $my_blog_id ) {
		switch_to_blog( $my_blog_id );
		_eme_uninstall( 1 );
		restore_current_blog();
	}
	//switch_to_blog( $original_blog_id );
}
?>
