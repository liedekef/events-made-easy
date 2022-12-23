<?php

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require 'events-manager.php';

// For Single site
if ( ! is_multisite() ) {
	_eme_uninstall( 1 );
} else {
	// For Multisite
	// For regular options.
	global $wpdb,$eme_db_prefix;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	//$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $my_blog_id ) {
		switch_to_blog( $my_blog_id );
		_eme_uninstall( 1 );
		restore_current_blog();
	}
	//switch_to_blog( $original_blog_id );
}
?>
