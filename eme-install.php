<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// we define all db-constants here, this also means the uninstall can include this file and use it
// and doesn't need to include the main file
define( 'EME_DB_VERSION', 371 ); // increase this if the db schema changes or the options change
define( 'EME_EVENTS_TBNAME', 'eme_events' );
define( 'EME_EVENTS_CF_TBNAME', 'eme_events_cf' );
define( 'EME_RECURRENCE_TBNAME', 'eme_recurrence' );
define( 'EME_LOCATIONS_TBNAME', 'eme_locations' );
define( 'EME_LOCATIONS_CF_TBNAME', 'eme_locations_cf' );
define( 'EME_BOOKINGS_TBNAME', 'eme_bookings' );
define( 'EME_PEOPLE_TBNAME', 'eme_people' );
define( 'EME_GROUPS_TBNAME', 'eme_groups' );
define( 'EME_USERGROUPS_TBNAME', 'eme_usergroups' );
define( 'EME_CATEGORIES_TBNAME', 'eme_categories' );
define( 'EME_HOLIDAYS_TBNAME', 'eme_holidays' );
define( 'EME_TEMPLATES_TBNAME', 'eme_templates' );
define( 'EME_FORMFIELDS_TBNAME', 'eme_formfields' );
define( 'EME_FIELDTYPES_TBNAME', 'eme_fieldtypes' );
define( 'EME_ANSWERS_TBNAME', 'eme_answers' );
define( 'EME_PAYMENTS_TBNAME', 'eme_payments' );
define( 'EME_DISCOUNTS_TBNAME', 'eme_discounts' );
define( 'DISCOUNTEME_GROUPS_TBNAME', 'eme_dgroups' );
define( 'EME_MQUEUE_TBNAME', 'eme_mqueue' );
define( 'EME_MAILINGS_TBNAME', 'eme_mailings' );
define( 'EME_MEMBERS_TBNAME', 'eme_members' );
define( 'EME_MEMBERSHIPS_TBNAME', 'eme_memberships' );
define( 'EME_MEMBERSHIPS_CF_TBNAME', 'eme_memberships_cf' );
define( 'EME_COUNTRIES_TBNAME', 'eme_countries' );
define( 'EME_STATES_TBNAME', 'eme_states' );
define( 'EME_ATTENDANCES_TBNAME', 'eme_attendances' );
define( 'EME_TASKS_TBNAME', 'eme_tasks' );
define( 'EME_TASK_SIGNUPS_TBNAME', 'eme_task_signups' );

function eme_install( $networkwide ) {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ( $networkwide ) {
			// Loop through sites.
			$blog_ids = get_sites( [ 'fields' => 'ids' ] );
			foreach ( $blog_ids as $site_id ) {
				switch_to_blog( $site_id );
				_eme_install();
				restore_current_blog();
			}
			return;
		}
	}
	// executed if no network activation
	_eme_install();
}

// the private function; for activation
function _eme_install() {
	eme_add_options();
	$db_version = intval( get_option( 'eme_version' ) );
	if ( $db_version > EME_DB_VERSION ) {
		$db_version = EME_DB_VERSION;
	}
	if ( $db_version != EME_DB_VERSION ) {
		eme_update_options( $db_version );
	}

	// some dir
	if ( ! is_dir( EME_UPLOAD_DIR ) ) {
		wp_mkdir_p( EME_UPLOAD_DIR );
	}
	if ( ! is_dir( EME_UPLOAD_DIR . '/bookings' ) ) {
		wp_mkdir_p( EME_UPLOAD_DIR . '/bookings' );
	}
	if ( ! is_file( EME_UPLOAD_DIR . '/bookings/index.html' ) ) {
		touch( EME_UPLOAD_DIR . '/bookings/index.html' );
	}
	if ( ! is_dir( EME_UPLOAD_DIR . '/people' ) ) {
		wp_mkdir_p( EME_UPLOAD_DIR . '/people' );
	}
	if ( ! is_file( EME_UPLOAD_DIR . '/people/index.html' ) ) {
		touch( EME_UPLOAD_DIR . '/people/index.html' );
	}
	if ( ! is_dir( EME_UPLOAD_DIR . '/members' ) ) {
		wp_mkdir_p( EME_UPLOAD_DIR . '/members' );
	}
	if ( ! is_file( EME_UPLOAD_DIR . '/members/index.html' ) ) {
		touch( EME_UPLOAD_DIR . '/members/index.html' );
	}

	// Create events page if necessary
	$events_page_id = eme_get_events_page_id();
	if ( $events_page_id ) {
		$events_page = get_page( $events_page_id );
		if ( ! $events_page || $events_page->post_status != 'publish' ) {
			eme_create_events_page();
		}
	} else {
		eme_create_events_page();
	}
	// SEO rewrite rules
	flush_rewrite_rules();

	// create/update the db tables needed
	if ( $db_version != EME_DB_VERSION ) {
		eme_create_tables( $db_version );
	}

	// make sure no unintended cleanup happens
	$cleanup_unpaid_minutes = intval( get_option( 'eme_cron_cleanup_unpaid_minutes' ) );
	if ( ! wp_next_scheduled( 'eme_cron_cleanup_unpaid' ) && $cleanup_unpaid_minutes > 0 ) {
		update_option( 'eme_cron_cleanup_unpaid_minutes', 0 );
	}
	$cleanup_unconfirmed_minutes = intval( get_option( 'eme_cron_cleanup_unconfirmed_minutes' ) );
	if ( ! wp_next_scheduled( 'eme_cron_cleanup_unconfirmed' ) && $cleanup_unconfirmed_minutes > 0 ) {
		update_option( 'eme_cron_cleanup_unconfirmed_minutes', 0 );
	}

	// some cron we want
	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
	// midnight
	$timestamp = $eme_date_obj->addOneDay()->setTime( 0, 0, 0 )->getTimestamp();
	// to make sure summer/winter starts at the same day, we add one hour (WP "daily" uses 24 hours/day fixed)
	$timestamp += 3600;
	if ( $db_version < 349 ) {
		wp_unschedule_hook( 'eme_cron_member_daily_actions' );
		wp_unschedule_hook( 'eme_cron_gdpr_daily_actions' );
		wp_unschedule_hook( 'eme_cron_events_daily_actions' );
	}
	if ( ! wp_next_scheduled( 'eme_cron_member_daily_actions' ) ) {
			wp_schedule_event( $timestamp, 'daily', 'eme_cron_member_daily_actions' );
	}
	if ( ! wp_next_scheduled( 'eme_cron_gdpr_daily_actions' ) ) {
			wp_schedule_event( $timestamp + 300, 'daily', 'eme_cron_gdpr_daily_actions' );
	}
	if ( ! wp_next_scheduled( 'eme_cron_events_daily_actions' ) ) {
			wp_schedule_event( $timestamp, 'daily', 'eme_cron_events_daily_actions' );
	}
	if ( ! wp_next_scheduled( 'eme_cron_daily_actions' ) ) {
		$res = wp_schedule_event( $timestamp + 600, 'daily', 'eme_cron_daily_actions' );
	}
	if ( ! wp_next_scheduled( 'eme_cron_cleanup_actions' ) ) {
		$res = wp_schedule_event( time(), 'eme_5min', 'eme_cron_cleanup_actions' );
	}

	// now cleanup old crons
	$crons_to_remove = [ 'eme_cron_cleanup_unpaid', 'eme_cron_cleanup_unconfirmed', 'eme_cron_cleanup_captcha' ];
	foreach ( $crons_to_remove as $tmp_cron ) {
		if ( wp_next_scheduled( $tmp_cron ) ) {
			wp_unschedule_hook( $tmp_cron );
		}
	}

	// we'll restore some planned actions too, if previously deactivated
	$cron_actions = [ 'eme_cron_send_new_events', 'eme_cron_send_queued' ];
	foreach ( $cron_actions as $cron_action ) {
		$schedule = get_option( $cron_action );
		// old schedule names are renamed to eme_*
		if (preg_match( '/^(1min|5min|15min|30min|4weeks)$/', $schedule, $matches ) ) {
			$res = $matches[0];
			$schedule = "eme_".$res;
			update_option($cron_action, $schedule);
			wp_unschedule_hook( $cron_action );
		}

		// if the action is planned, keep the planning in an option (if we're not clearing all data) and then clear the planning
		if ( ! empty( $schedule ) && ! wp_next_scheduled( $cron_action ) ) {
			wp_schedule_event( time(), $schedule, $cron_action );
		}
	}

	// make sure queue sending is configured properly
	if ( wp_next_scheduled( 'eme_cron_send_queued' ) ) {
		$schedule = wp_get_schedule( 'eme_cron_send_queued' );
		update_option( 'eme_cron_send_queued', $schedule );
	}
	eme_plan_queue_mails();

	// remove possible translations in WP (but leave frontend submit)
	array_map( 'wp_delete_file', preg_grep('/.*frontend.*/', glob( WP_CONTENT_DIR."/languages/plugins/events-made-easy*" ), PREG_GREP_INVERT) );

	// now set the version correct
	update_option( 'eme_version', EME_DB_VERSION );
}

function eme_uninstall( $networkwide ) {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ( $networkwide ) {
			// Get all blog ids
			$blog_ids = get_sites( [ 'fields' => 'ids' ] );
                        foreach ( $blog_ids as $site_id ) {
                                switch_to_blog( $site_id );
                                _eme_uninstall();
				restore_current_blog();
                        }
			return;
		}
	}
	// executed if no network activation
	_eme_uninstall();
}

function _eme_uninstall( $force_drop = 0 ) {
	global $wpdb;
	$drop_data     = get_option( 'eme_uninstall_drop_data' );
	$drop_settings = get_option( 'eme_uninstall_drop_settings' );

	// these crons get planned with a fixed schedule at activation time, so we don't need to store their planned setting when deactivating
	$cron_actions1 = [ 'eme_cron_daily_actions', 'eme_cron_member_daily_actions', 'eme_cron_gdpr_daily_actions', 'eme_cron_events_daily_actions', 'eme_cron_cleanup_actions' ];
	foreach ( $cron_actions1 as $cron_action ) {
		if ( wp_next_scheduled( $cron_action ) ) {
			wp_unschedule_hook( $cron_action );
		}
	}
	$cron_actions2 = [ 'eme_cron_cleanup_unpaid', 'eme_cron_send_new_events', 'eme_cron_send_queued' ];
	foreach ( $cron_actions2 as $cron_action ) {
		// if the action is planned, keep the planning in an option (if we're not clearing all data) and then clear the planning
		if ( wp_next_scheduled( $cron_action ) ) {
			if ( ! ( $drop_settings || $force_drop ) ) {
				$scheduled = wp_get_schedule( $cron_action );
				update_option( $cron_action, $scheduled );
			}
			wp_unschedule_hook( $cron_action );
		}
	}

	if ( $drop_data || $force_drop ) {
		// during uninstall, we only take the prefix per blog (not based on the settings "is_multisite() && get_option( 'eme_multisite_active' )" in the function  eme_get_db_prefix)
		$db_prefix = $wpdb->prefix;
		eme_drop_table( $db_prefix . EME_EVENTS_TBNAME );
		eme_drop_table( $db_prefix . EME_RECURRENCE_TBNAME );
		eme_drop_table( $db_prefix . EME_LOCATIONS_TBNAME );
		eme_drop_table( $db_prefix . EME_BOOKINGS_TBNAME );
		eme_drop_table( $db_prefix . EME_PEOPLE_TBNAME );
		eme_drop_table( $db_prefix . EME_GROUPS_TBNAME );
		eme_drop_table( $db_prefix . EME_USERGROUPS_TBNAME );
		eme_drop_table( $db_prefix . EME_CATEGORIES_TBNAME );
		eme_drop_table( $db_prefix . EME_HOLIDAYS_TBNAME );
		eme_drop_table( $db_prefix . EME_TEMPLATES_TBNAME );
		eme_drop_table( $db_prefix . EME_FORMFIELDS_TBNAME );
		eme_drop_table( $db_prefix . EME_FIELDTYPES_TBNAME );
		eme_drop_table( $db_prefix . EME_ANSWERS_TBNAME );
		eme_drop_table( $db_prefix . EME_PAYMENTS_TBNAME );
		eme_drop_table( $db_prefix . EME_DISCOUNTS_TBNAME );
		eme_drop_table( $db_prefix . DISCOUNTEME_GROUPS_TBNAME );
		eme_drop_table( $db_prefix . EME_MQUEUE_TBNAME );
		eme_drop_table( $db_prefix . EME_MAILINGS_TBNAME );
		eme_drop_table( $db_prefix . EME_MEMBERS_TBNAME );
		eme_drop_table( $db_prefix . EME_MEMBERSHIPS_TBNAME );
		eme_drop_table( $db_prefix . EME_COUNTRIES_TBNAME );
		eme_drop_table( $db_prefix . EME_STATES_TBNAME );
		eme_drop_table( $db_prefix . EME_ATTENDANCES_TBNAME );
		eme_drop_table( $db_prefix . EME_TASKS_TBNAME );
		eme_drop_table( $db_prefix . EME_TASK_SIGNUPS_TBNAME );
	}
	if ( $drop_settings || $force_drop ) {
		eme_delete_events_page();
		eme_options_delete();
		eme_metabox_options_delete();
	}
	if ( $drop_data && ! $drop_settings ) {
		// make sure eme_version is deleted if drop_data is selected
		// this is not needed if drop_settings is set, since that already removes all eme related options
		delete_option( 'eme_version' );
	}

	// unschedule the update checker when uninstalling only
	if ($force_drop) {
		$cron_action = 'puc_cron_check_updates-events-made-easy';
		if ( wp_next_scheduled( $cron_action ) ) {
			wp_unschedule_hook( $cron_action );
		}
	}

	// SEO rewrite rules
	flush_rewrite_rules();
}

function eme_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;

	if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		$old_blog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		_eme_install();
		switch_to_blog( $old_blog );
	}
}

function eme_create_tables( $db_version ) {
	global $wpdb;
	// Creates the events table if necessary
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// during install, the prefix changes per blog, so get it here
	$db_prefix = eme_get_db_prefix();
	$charset       = '';
	$collate       = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) ) {
			$charset = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$collate = "COLLATE $wpdb->collate";
		}
	}
	// db_version returns incorrect info
	// $mysql_version  = $wpdb->db_version();
	//$mysql_version  = $wpdb->get_var("SELECT VERSION();");
	//$mysql_version = preg_replace( '/[^0-9.].*/', '', $mysql_version);
	//$mysql_old = version_compare( $mysql_version, "5.6", '<' );

	eme_create_events_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_recurrence_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_locations_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_bookings_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_people_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_members_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_categories_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_holidays_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_templates_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_formfields_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_answers_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_payments_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_discounts_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_discountgroups_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_mqueue_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_countries_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_states_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_attendances_table( $charset, $collate, $db_version, $db_prefix );
	eme_create_task_tables( $charset, $collate, $db_version, $db_prefix );
}

function eme_create_events_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;

	$table_name = $db_prefix . EME_EVENTS_TBNAME;

	$default_current_ts = 'DEFAULT CURRENT_TIMESTAMP';
	$update_current_ts  = 'DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
	if ( ! eme_table_exists( $table_name ) ) {
		// Creating the events table
		$sql = 'CREATE TABLE ' . $table_name . " (
			event_id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_status mediumint(9) DEFAULT 1,
			event_author mediumint(9) DEFAULT 0,
			event_name text NOT NULL,
			event_prefix text,
			event_slug text,
			event_url text,
			event_start datetime,
			event_end datetime,
			creation_date datetime,
			modif_date datetime,
			event_notes longtext,
			event_rsvp bool DEFAULT 0,
			event_tasks bool DEFAULT 0,
			price text,
			currency text,
			rsvp_number_days mediumint(5) DEFAULT 0,
			rsvp_number_hours mediumint(5) DEFAULT 0,
			event_seats text,
			event_contactperson_id mediumint(9) DEFAULT 0,
			location_id mediumint(9) DEFAULT 0,
			recurrence_id mediumint(9) DEFAULT 0,
			event_category_ids text,
			event_attributes text, 
			event_properties text, 
			event_page_title_format text, 
			event_single_event_format text, 
			event_contactperson_email_body text, 
			event_respondent_email_body text, 
			event_registration_recorded_ok_html text, 
			event_registration_pending_email_body text, 
			event_registration_updated_email_body text, 
			event_registration_cancelled_email_body text, 
			event_registration_paid_email_body text, 
			event_registration_trashed_email_body text, 
			event_registration_form_format text, 
			event_cancel_form_format text, 
			registration_requires_approval bool DEFAULT 0,
			registration_wp_users_only bool DEFAULT 0,
			event_image_url text,
			event_image_id mediumint(9) DEFAULT 0,
			event_external_ref text, 
			UNIQUE KEY (event_id),
			KEY (event_start),
			KEY (event_end)
		) $charset $collate;";

		maybe_create_table( $table_name, $sql );
		// insert a few events in the new table
		// get the current timestamp into an array
		$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
		$eme_date_obj->addDays( 7 );
		$in_one_week = $eme_date_obj->getDate();
		$eme_date_obj->minusDays( 7 );
		$eme_date_obj->addWeeks( 4 );
		$in_four_weeks = $eme_date_obj->getDate();
		$eme_date_obj->minusWeeks( 4 );
		$eme_date_obj->addOneYear();
		$in_one_year = $eme_date_obj->getDate();

		// some events
		$event                = eme_new_event();
		$event['event_name']  = 'Orality in James Joyce Conference';
		$event['event_start'] = "$in_one_week 16:00:00";
		$event['event_end']   = "$in_one_week 18:00:00";
		$event['location_id'] = 1;
		$event                = eme_sanitize_event( $event );
		// the fourth param is 1 to indicate we're in plugin install mode
		// this will cause eme_db_insert_event to check the prefix itself
		// since during install plugin globals are not available ...
		eme_db_insert_event( $event, 0, 0, 1 );

		$event                = eme_new_event();
		$event['event_name']  = 'Traditional music session';
		$event['event_start'] = "$in_four_weeks 20:00:00";
		$event['event_end']   = "$in_four_weeks 22:00:00";
		$event['location_id'] = 2;
		$event                = eme_sanitize_event( $event );
		// the fourth param is 1 to indicate we're in plugin install mode
		// this will cause eme_db_insert_event to check the prefix itself
		// since during install plugin globals are not available ...
		eme_db_insert_event( $event, 0, 0, 1 );

		$event                = eme_new_event();
		$event['event_name']  = '6 Nations, Italy VS Ireland';
		$event['event_start'] = "$in_one_year 22:00:00";
		$event['event_end']   = "$in_one_year 23:59:59";
		$event['location_id'] = 3;
		$event                = eme_sanitize_event( $event );
		// the fourth param is 1 to indicate we're in plugin install mode
		// this will cause eme_db_insert_event to check the prefix itself
		// since during install plugin globals are not available ...
		eme_db_insert_event( $event, 0, 0, 1 );

	} else {
		// eventual maybe_add_column() for later versions
		maybe_add_column( $table_name, 'event_status', "ALTER TABLE $table_name ADD event_status mediumint(9) DEFAULT 1;" );
		maybe_add_column( $table_name, 'event_start', "ALTER TABLE $table_name ADD event_start datetime;" );
		maybe_add_column( $table_name, 'event_end', "ALTER TABLE $table_name ADD event_end datetime;" );
		maybe_add_column( $table_name, 'event_rsvp', "ALTER TABLE $table_name ADD event_rsvp bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'event_tasks', "ALTER TABLE $table_name ADD event_tasks bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'rsvp_number_days', "ALTER TABLE $table_name ADD rsvp_number_days mediumint(5) DEFAULT 0;" );
		maybe_add_column( $table_name, 'rsvp_number_hours', "ALTER TABLE $table_name ADD rsvp_number_hours mediumint(5) DEFAULT 0;" );
		maybe_add_column( $table_name, 'price', "ALTER TABLE $table_name ADD price text;" );
		maybe_add_column( $table_name, 'currency', "ALTER TABLE $table_name ADD currency text;" );
		maybe_add_column( $table_name, 'event_seats', "ALTER TABLE $table_name ADD event_seats text;" );
		maybe_add_column( $table_name, 'location_id', "ALTER TABLE $table_name ADD location_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'recurrence_id', "ALTER TABLE $table_name ADD recurrence_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'event_contactperson_id', "ALTER TABLE $table_name ADD event_contactperson_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'event_attributes', "ALTER TABLE $table_name ADD event_attributes text;" );
		maybe_add_column( $table_name, 'event_properties', "ALTER TABLE $table_name ADD event_properties text;" );
		maybe_add_column( $table_name, 'event_url', "ALTER TABLE $table_name ADD event_url text;" );
		maybe_add_column( $table_name, 'event_prefix', "ALTER TABLE $table_name ADD event_prefix text;" );
		maybe_add_column( $table_name, 'event_slug', "ALTER TABLE $table_name ADD event_slug text;" );
		maybe_add_column( $table_name, 'event_category_ids', "ALTER TABLE $table_name ADD event_category_ids text;" );
		maybe_add_column( $table_name, 'event_page_title_format', "ALTER TABLE $table_name ADD event_page_title_format text;" );
		maybe_add_column( $table_name, 'event_single_event_format', "ALTER TABLE $table_name ADD event_single_event_format text;" );
		maybe_add_column( $table_name, 'event_contactperson_email_body', "ALTER TABLE $table_name ADD event_contactperson_email_body text;" );
		maybe_add_column( $table_name, 'event_respondent_email_body', "ALTER TABLE $table_name ADD event_respondent_email_body text;" );
		maybe_add_column( $table_name, 'event_registration_pending_email_body', "ALTER TABLE $table_name ADD event_registration_pending_email_body text;" );
		maybe_add_column( $table_name, 'event_registration_updated_email_body', "ALTER TABLE $table_name ADD event_registration_updated_email_body text;" );
		maybe_add_column( $table_name, 'event_registration_cancelled_email_body', "ALTER TABLE $table_name ADD event_registration_cancelled_email_body text;" );
		maybe_add_column( $table_name, 'event_registration_paid_email_body', "ALTER TABLE $table_name ADD event_registration_paid_email_body text;" );
		maybe_add_column( $table_name, 'event_registration_recorded_ok_html', "ALTER TABLE $table_name ADD event_registration_recorded_ok_html text;" );
		maybe_add_column( $table_name, 'registration_requires_approval', "ALTER TABLE $table_name ADD registration_requires_approval bool DEFAULT 0;" );
		$registration_wp_users_only = get_option( 'eme_rsvp_registered_users_only' );
		maybe_add_column( $table_name, 'registration_wp_users_only', "ALTER TABLE $table_name ADD registration_wp_users_only bool DEFAULT $registration_wp_users_only;" );
		maybe_add_column( $table_name, 'event_author', "ALTER TABLE $table_name ADD event_author mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'creation_date', "ALTER TABLE $table_name ADD creation_date datetime;" );
		maybe_add_column( $table_name, 'modif_date', "ALTER TABLE $table_name ADD modif_date datetime" );
		eme_maybe_drop_column( $table_name, 'creation_date_gmt' );
		eme_maybe_drop_column( $table_name, 'modif_date_gmt' );
		maybe_add_column( $table_name, 'event_registration_form_format', "ALTER TABLE $table_name ADD event_registration_form_format text;" );
		maybe_add_column( $table_name, 'event_cancel_form_format', "ALTER TABLE $table_name ADD event_cancel_form_format text;" );
		maybe_add_column( $table_name, 'event_image_url', "ALTER TABLE $table_name ADD event_image_url text;" );
		maybe_add_column( $table_name, 'event_image_id', "ALTER TABLE $table_name ADD event_image_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'event_external_ref', "ALTER TABLE $table_name ADD event_external_ref text;" );
		if ( $db_version < 3 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_name text;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_notes longtext;" );
		}
		if ( $db_version < 4 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE event_category_id event_category_ids text;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_author mediumint(9) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_contactperson_id mediumint(9) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_seats mediumint(9) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY location_id mediumint(9) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY recurrence_id mediumint(9) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_rsvp bool DEFAULT 0;" );
		}
		if ( $db_version < 5 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_rsvp bool DEFAULT 0;" );
		}
		if ( $db_version < 11 ) {
			if ( eme_column_exists( $table_name, 'event_creator_id' ) ) {
					eme_maybe_drop_column( $table_name, 'event_author' );
					$wpdb->query( "ALTER TABLE $table_name CHANGE event_creator_id event_author mediumint(9) DEFAULT 0;" );
			}
			// in case event_creator_id didn't exist ...
			maybe_add_column( $table_name, 'event_author', "ALTER TABLE $table_name ADD event_author mediumint(9) DEFAULT 0;" );
		}
		if ( $db_version < 29 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY price text;" );
		}
		if ( $db_version < 33 ) {
			$post_table_name = $db_prefix . 'posts';
			$wpdb->query( "UPDATE $table_name SET event_image_id = (select ID from $post_table_name where post_type = 'attachment' AND guid = $table_name.event_image_url);" );
		}
		if ( $db_version < 38 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_seats text;" );
		}
		if ( $db_version < 70 ) {
			eme_maybe_drop_column( $table_name, 'use_google' );
		}
		if ( $db_version < 247 ) {
			if ( eme_column_exists( $table_name, 'event_registration_denied_email_body' ) && ! eme_column_exists( $table_name, 'event_registration_trashed_email_body' ) ) {
					$wpdb->query( "ALTER TABLE $table_name CHANGE event_registration_denied_email_body event_registration_trashed_email_body text;" );
			}
			maybe_add_column( $table_name, 'event_registration_trashed_email_body', "ALTER TABLE $table_name ADD event_registration_trashed_email_body text;" );
		}
		if ( $db_version < 281 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY rsvp_number_days mediumint(5) DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY rsvp_number_hours mediumint(5) DEFAULT 0;" );
		}
		if ( $db_version < 294 ) {
			eme_migrate_event_payment_options();
			eme_maybe_drop_column( $table_name, 'use_paypal' );
			eme_maybe_drop_column( $table_name, 'use_2co' );
			eme_maybe_drop_column( $table_name, 'use_webmoney' );
			eme_maybe_drop_column( $table_name, 'use_fdgg' );
			eme_maybe_drop_column( $table_name, 'use_mollie' );
			eme_maybe_drop_column( $table_name, 'use_sagepay' );
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY modif_date datetime ;" );
		}
		if ( $db_version < 302 ) {
			$wpdb->query( "UPDATE $table_name SET event_start = CONCAT(event_start_date,' ',event_start_time), event_end = CONCAT(event_end_date,' ',event_end_time) WHERE event_start_date IS NOT NULL and event_start_date <> '0000-00-00';" );
		}
		if ( $db_version < 303 ) {
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `event_start` )" );
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `event_end` )" );
		}
		if ( $db_version < 311 ) {
			eme_maybe_drop_column( $table_name, 'event_start_date' );
			eme_maybe_drop_column( $table_name, 'event_end_date' );
			eme_maybe_drop_column( $table_name, 'event_start_time' );
			eme_maybe_drop_column( $table_name, 'event_end_time' );
		}
	}
}

function eme_create_recurrence_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_RECURRENCE_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
			recurrence_id mediumint(9) NOT NULL AUTO_INCREMENT,
			recurrence_start_date date NOT NULL,
			recurrence_end_date date NOT NULL,
			recurrence_interval tinyint NOT NULL, 
			recurrence_freq tinytext NOT NULL,
			recurrence_byday tinytext NOT NULL,
			recurrence_byweekno tinyint NOT NULL,
			event_duration mediumint(9) DEFAULT 0,
			recurrence_specific_days text,
			holidays_id mediumint(9) DEFAULT 0,
			UNIQUE KEY (recurrence_id)
	 	) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'event_duration', "ALTER TABLE $table_name ADD event_duration mediumint(9) DEFAULT 0;" );
		eme_maybe_drop_column( $table_name, 'creation_date_gmt' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		eme_maybe_drop_column( $table_name, 'creation_date' );
		maybe_add_column( $table_name, 'recurrence_specific_days', "ALTER TABLE $table_name ADD recurrence_specific_days text;" );
		maybe_add_column( $table_name, 'holidays_id', "ALTER TABLE $table_name ADD holidays_id mediumint(9) DEFAULT 0;" );
		if ( $db_version < 3 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY recurrence_byday tinytext NOT NULL ;" );
		}
		if ( $db_version < 4 ) {
			eme_maybe_drop_column( $table_name, 'recurrence_name' );
			eme_maybe_drop_column( $table_name, 'recurrence_start_time' );
			eme_maybe_drop_column( $table_name, 'recurrence_end_time' );
			eme_maybe_drop_column( $table_name, 'recurrence_notes' );
			eme_maybe_drop_column( $table_name, 'location_id' );
			eme_maybe_drop_column( $table_name, 'event_contactperson_id' );
			eme_maybe_drop_column( $table_name, 'event_category_id' );
			eme_maybe_drop_column( $table_name, 'event_page_title_format' );
			eme_maybe_drop_column( $table_name, 'event_single_event_format' );
			eme_maybe_drop_column( $table_name, 'event_contactperson_email_body' );
			eme_maybe_drop_column( $table_name, 'event_respondent_email_body' );
			eme_maybe_drop_column( $table_name, 'registration_requires_approval' );
		}
	}
}

function eme_create_locations_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_LOCATIONS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         location_id mediumint(9) NOT NULL AUTO_INCREMENT,
         location_name text NOT NULL,
         location_prefix text,
         location_slug text,
         location_url text,
         location_address1 tinytext, 
         location_address2 tinytext, 
         location_city tinytext, 
         location_state tinytext, 
         location_zip tinytext, 
         location_country tinytext, 
         location_latitude tinytext,
         location_longitude tinytext,
         location_description text,
         location_author mediumint(9) DEFAULT 0,
         location_category_ids text,
         location_image_url text,
         location_image_id mediumint(9) DEFAULT 0,
         location_attributes text, 
         location_properties text, 
         location_external_ref text, 
         UNIQUE KEY (location_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );

		$wpdb->query(
		    'INSERT INTO ' . $table_name . " (location_name, location_address1, location_city, location_latitude, location_longitude)
               VALUES ('Arts Millenium Building', 'Newcastle Road','Galway', '53.275', '-9.06532')"
		);
		$wpdb->query(
		    'INSERT INTO ' . $table_name . " (location_name, location_address1, location_city, location_latitude, location_longitude)
               VALUES ('The Crane Bar', '2, Sea Road','Galway', '53.2683224', '-9.0626223')"
		);
		$wpdb->query(
		    'INSERT INTO ' . $table_name . " (location_name, location_address1, location_city, location_latitude, location_longitude)
               VALUES ('Taaffes Bar', '19 Shop Street','Galway', '53.2725', '-9.05321')"
		);
	} else {
		maybe_add_column( $table_name, 'location_author', "ALTER TABLE $table_name ADD location_author mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'location_category_ids', "ALTER TABLE $table_name ADD location_category_ids text;" );
		eme_maybe_drop_column( $table_name, 'location_creation_date_gmt' );
		eme_maybe_drop_column( $table_name, 'location_creation_date' );
		eme_maybe_drop_column( $table_name, 'location_modif_date' );
		maybe_add_column( $table_name, 'location_url', "ALTER TABLE $table_name ADD location_url text;" );
		maybe_add_column( $table_name, 'location_prefix', "ALTER TABLE $table_name ADD location_prefix text;" );
		maybe_add_column( $table_name, 'location_slug', "ALTER TABLE $table_name ADD location_slug text;" );
		maybe_add_column( $table_name, 'location_image_url', "ALTER TABLE $table_name ADD location_image_url text;" );
		maybe_add_column( $table_name, 'location_image_id', "ALTER TABLE $table_name ADD location_image_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'location_attributes', "ALTER TABLE $table_name ADD location_attributes text;" );
		maybe_add_column( $table_name, 'location_properties', "ALTER TABLE $table_name ADD location_properties text;" );
		maybe_add_column( $table_name, 'location_external_ref', "ALTER TABLE $table_name ADD location_external_ref text;" );
		if ( $db_version < 3 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY location_name text NOT NULL ;" );
		}
		if ( $db_version < 33 ) {
			$post_table_name = $db_prefix . 'posts';
			$wpdb->query( "UPDATE $table_name SET location_image_id = (select ID from $post_table_name where post_type = 'attachment' AND guid = $table_name.location_image_url);" );
		}
		if ( $db_version < 110 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE location_address location_address1 tinytext;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE location_town location_city tinytext;" );
			maybe_add_column( $table_name, 'location_address2', "ALTER TABLE $table_name ADD location_address2 tinytext;" );
			maybe_add_column( $table_name, 'location_state', "ALTER TABLE $table_name ADD location_state tinytext;" );
			maybe_add_column( $table_name, 'location_zip', "ALTER TABLE $table_name ADD location_zip tinytext;" );
			maybe_add_column( $table_name, 'location_country', "ALTER TABLE $table_name ADD location_country tinytext;" );
		}
		if ( $db_version < 183 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE location_latitude location_latitude tinytext;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE location_longitude location_longitude tinytext;" );
		}
	}
}

function eme_create_bookings_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_BOOKINGS_TBNAME;

	// column discount: effective calculated discount value
	// columns discountid , dgroupid: pointer to discount/discout group applied
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         booking_id mediumint(9) NOT NULL AUTO_INCREMENT,
         event_id mediumint(9) NOT NULL,
         person_id mediumint(9) NOT NULL, 
         payment_id mediumint(9) DEFAULT NULL, 
         status tinyint DEFAULT 1,
         booking_seats mediumint(9) NOT NULL,
         booking_seats_mp varchar(250),
         waitinglist bool DEFAULT 0,
         booking_comment text,
         event_price text,
         extra_charge tinytext,
         creation_date datetime,
         modif_date datetime,
         payment_date datetime DEFAULT '0000-00-00 00:00:00',
         booking_paid bool DEFAULT 0,
         received tinytext,
         remaining tinytext,
         pg tinytext,
         pg_pid tinytext,
         reminder INT(11) DEFAULT 0,
         unique_nbr varchar(20),
         discount tinytext,
         discountids tinytext,
         dcodes_entered tinytext,
         dcodes_used tinytext,
         dgroupid INT(11) DEFAULT 0,
         attend_count INT(11) DEFAULT 0,
         UNIQUE KEY  (booking_id),
         KEY (status)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'attend_count', "ALTER TABLE $table_name ADD attend_count INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'status', "ALTER TABLE $table_name ADD status tinyint DEFAULT 1;" );
		maybe_add_column( $table_name, 'booking_comment', "ALTER TABLE $table_name ADD booking_comment text;" );
		maybe_add_column( $table_name, 'waitinglist', "ALTER TABLE $table_name ADD waitinglist bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'payment_date', "ALTER TABLE $table_name ADD payment_date datetime DEFAULT '0000-00-00 00:00:00';" );
		maybe_add_column( $table_name, 'creation_date', "ALTER TABLE $table_name ADD creation_date datetime;" );
		maybe_add_column( $table_name, 'modif_date', "ALTER TABLE $table_name ADD modif_date datetime;" );
		eme_maybe_drop_column( $table_name, 'creation_date_gmt' );
		eme_maybe_drop_column( $table_name, 'modif_date_gmt' );
		maybe_add_column( $table_name, 'booking_seats_mp', "ALTER TABLE $table_name ADD booking_seats_mp varchar(250);" );
		eme_maybe_drop_column( $table_name, 'ip' );
		eme_maybe_drop_column( $table_name, 'wp_id' );
		eme_maybe_drop_column( $table_name, 'lang' );
		maybe_add_column( $table_name, 'extra_charge', "ALTER TABLE $table_name ADD extra_charge tinytext;" );
		maybe_add_column( $table_name, 'discount', "ALTER TABLE $table_name ADD discount tinytext;" );
		maybe_add_column( $table_name, 'dcodes_entered', "ALTER TABLE $table_name ADD dcodes_entered tinytext ;" );
		maybe_add_column( $table_name, 'dcodes_used', "ALTER TABLE $table_name ADD dcodes_used tinytext ;" );
		maybe_add_column( $table_name, 'dgroupid', "ALTER TABLE $table_name ADD dgroupid INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'reminder', "ALTER TABLE $table_name ADD reminder INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'received', "ALTER TABLE $table_name ADD received tinytext;" );
		maybe_add_column( $table_name, 'remaining', "ALTER TABLE $table_name ADD remaining tinytext;" );
		if ( eme_column_exists( $table_name, 'discountid' ) ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE discountid discountids tinytext;" );
		} else {
			maybe_add_column( $table_name, 'discountids', "ALTER TABLE $table_name ADD discountids tinytext;" );
		}
		if ( eme_column_exists( $table_name, 'transfer_nbr_be97' ) ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE transfer_nbr_be97 unique_nbr varchar(20);" );
		} else {
			maybe_add_column( $table_name, 'unique_nbr', "ALTER TABLE $table_name ADD unique_nbr varchar(20);" );
		}
		maybe_add_column( $table_name, 'pg', "ALTER TABLE $table_name ADD pg tinytext;" );
		maybe_add_column( $table_name, 'pg_pid', "ALTER TABLE $table_name ADD pg_pid tinytext;" );

		if ( $db_version < 3 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY event_id mediumint(9) NOT NULL;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY person_id mediumint(9) NOT NULL;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY booking_seats mediumint(9) NOT NULL;" );
		}
		if ( $db_version < 47 ) {
			$people_table_name = $db_prefix . EME_PEOPLE_TBNAME;
			$wpdb->query( "update $table_name a JOIN $people_table_name b on (a.person_id = b.person_id)  set a.wp_id=b.wp_id;" );
		}
		if ( $db_version < 92 ) {
			maybe_add_column( $table_name, 'payment_id', "ALTER TABLE $table_name ADD payment_id mediumint(9) DEFAULT NULL;" );
			$payment_table_name = $db_prefix . EME_PAYMENTS_TBNAME;
			$sql                = "SELECT id,booking_ids from $payment_table_name";

			$rows = $wpdb->get_results( $sql, ARRAY_A );
			if ( $rows !== false && ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$booking_ids = explode( ',', $row['booking_ids'] );
					if ( is_array( $booking_ids ) && count( $booking_ids ) > 0 ) {
						foreach ( $booking_ids as $booking_id ) {
							$sql = $wpdb->prepare( "UPDATE $table_name SET payment_id=%d WHERE booking_id=%d", $row['id'], $booking_id );
							$wpdb->query( $sql );
						}
					}
				}
				eme_maybe_drop_column( $payment_table_name, 'booking_ids' );
			}
		}
		if ( $db_version < 107 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE booking_payed booking_paid bool DEFAULT 0;" );
		}
		if ( $db_version < 208 ) {
			if ( eme_column_exists( $table_name, 'booking_price' ) ) {
				$wpdb->query( "ALTER TABLE $table_name CHANGE booking_price event_price text;" );
			} else {
				maybe_add_column( $table_name, 'event_price', "ALTER TABLE $table_name ADD event_price text;" );
			}
		}

		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY modif_date datetime;" );
		}
		if ( $db_version < 302 ) {
			// we forgot to add the index in the past when adding the table, so: drop the index and set it again
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `status` )" );
		}
		if ( $db_version < 345 ) {
			// old records that were marked as active and not approved, now get PENDING as status
			$sql = $wpdb->prepare( "UPDATE $table_name SET status=%d WHERE booking_approved=0 AND status=1", EME_RSVP_STATUS_PENDING );
			$wpdb->query( $sql );
			eme_maybe_drop_column( $table_name, 'booking_approved' );
		}
	}
}

function eme_create_people_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name          = $db_prefix . EME_PEOPLE_TBNAME;
	$grouptable_name     = $db_prefix . EME_GROUPS_TBNAME;
	$usergrouptable_name = $db_prefix . EME_USERGROUPS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         person_id mediumint(9) NOT NULL AUTO_INCREMENT,
         related_person_id mediumint(9) DEFAULT 0,
         lastname tinytext, 
         firstname tinytext, 
         email tinytext NOT NULL,
         status tinyint DEFAULT 1,
         phone tinytext,
         wp_id bigint(20) unsigned DEFAULT NULL,
         address1 tinytext, 
         address2 tinytext, 
         city tinytext, 
         zip tinytext, 
         state tinytext, 
         country tinytext, 
         state_code tinytext,
         country_code tinytext,
         birthdate date,
         bd_email bool DEFAULT 0,
         birthplace varchar(50) DEFAULT '',
         lang varchar(10) DEFAULT '',
         massmail bool DEFAULT 0,
         newsletter bool DEFAULT 0,
         gdpr bool DEFAULT 0,
         properties text,
         creation_date datetime,
         modif_date datetime,
         gdpr_date date,
         random_id varchar(50),
         UNIQUE KEY (person_id),
         KEY (status)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'random_id', "ALTER TABLE $table_name ADD random_id varchar(50);" );
		maybe_add_column( $table_name, 'gdpr', "ALTER TABLE $table_name ADD gdpr bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'gdpr_date', "ALTER TABLE $table_name ADD gdpr_date date;" );
		maybe_add_column( $table_name, 'birthdate', "ALTER TABLE $table_name ADD birthdate date;" );
		maybe_add_column( $table_name, 'birthplace', "ALTER TABLE $table_name ADD birthplace varchar(50) DEFAULT '';" );
		maybe_add_column( $table_name, 'status', "ALTER TABLE $table_name ADD status tinyint DEFAULT 1;" );
		maybe_add_column( $table_name, 'state_code', "ALTER TABLE $table_name ADD state_code tinytext;" );
		maybe_add_column( $table_name, 'country_code', "ALTER TABLE $table_name ADD country_code tinytext;" );
		maybe_add_column( $table_name, 'wp_id', "ALTER TABLE $table_name ADD wp_id bigint(20) unsigned DEFAULT NULL;" );
		maybe_add_column( $table_name, 'firstname', "ALTER TABLE $table_name ADD firstname tinytext;" );
		maybe_add_column( $table_name, 'address1', "ALTER TABLE $table_name ADD address1 tinytext;" );
		maybe_add_column( $table_name, 'address2', "ALTER TABLE $table_name ADD address2 tinytext;" );
		maybe_add_column( $table_name, 'city', "ALTER TABLE $table_name ADD city tinytext;" );
		maybe_add_column( $table_name, 'state', "ALTER TABLE $table_name ADD state tinytext;" );
		maybe_add_column( $table_name, 'zip', "ALTER TABLE $table_name ADD zip tinytext;" );
		maybe_add_column( $table_name, 'country', "ALTER TABLE $table_name ADD country tinytext;" );
		maybe_add_column( $table_name, 'lang', "ALTER TABLE $table_name ADD lang varchar(10) DEFAULT '';" );
		// for existing installations, we set massmail and newsletter=1 if the col was missing, to reflect old behaviour
		maybe_add_column( $table_name, 'massmail', "ALTER TABLE $table_name ADD massmail bool DEFAULT 1;" );
		maybe_add_column( $table_name, 'newsletter', "ALTER TABLE $table_name ADD newsletter bool DEFAULT 1;" );
		maybe_add_column( $table_name, 'properties', "ALTER TABLE $table_name ADD properties text;" );
		maybe_add_column( $table_name, 'creation_date', "ALTER TABLE $table_name ADD creation_date datetime;" );
		maybe_add_column( $table_name, 'modif_date', "ALTER TABLE $table_name ADD modif_date datetime;" );
		maybe_add_column( $table_name, 'related_person_id', "ALTER TABLE $table_name ADD related_person_id mediumint(9) DEFAULT 0;" );
		maybe_add_column( $table_name, 'bd_email', "ALTER TABLE $table_name ADD bd_email bool DEFAULT 0;" );
		if ( $db_version < 10 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY person_phone tinytext;" );
		}
		if ( $db_version < 78 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE person_phone phone tinytext;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE person_name lastname tinytext NOT NULL;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE person_email email tinytext NOT NULL;" );
		}
		if ( $db_version < 163 ) {
			eme_maybe_drop_column( $table_name, 'image_id' );
		}
		if ( $db_version < 204 ) {
			add_clean_index( $table_name, 'status' );
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY modif_date datetime ;" );
		}
		if ( $db_version < 306 ) {
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `related_person_id` )" );
		}
	}

	// now the groups table
	if ( ! eme_table_exists( $grouptable_name ) ) {
		$sql = 'CREATE TABLE ' . $grouptable_name . " (
         group_id int(11) NOT NULL auto_increment,
         name varchar(50) DEFAULT NULL,
         email tinytext,
         description tinytext,
         type tinytext,
         public bool DEFAULT 0,
         stored_sql text,
         search_terms text,
         UNIQUE KEY  (group_id)
         ) $charset $collate;";
		maybe_create_table( $grouptable_name, $sql );
	} else {
		maybe_add_column( $grouptable_name, 'type', "ALTER TABLE $grouptable_name ADD type tinytext;" );
		maybe_add_column( $grouptable_name, 'email', "ALTER TABLE $grouptable_name ADD email tinytext;" );
		maybe_add_column( $grouptable_name, 'stored_sql', "ALTER TABLE $grouptable_name ADD stored_sql text;" );
		maybe_add_column( $grouptable_name, 'search_terms', "ALTER TABLE $grouptable_name ADD search_terms text;" );
		if ( $db_version < 175 ) {
			$wpdb->query( "UPDATE $grouptable_name SET type = 'static';" );
		}
		if ( $db_version < 344 ) {
			$wpdb->query( "ALTER TABLE $grouptable_name CHANGE mail_only public bool DEFAULT 0;" );
		} else {
			maybe_add_column( $grouptable_name, 'public', "ALTER TABLE $grouptable_name ADD public bool DEFAULT 0;" );
		}
	}

	// now the table defining group members
	if ( ! eme_table_exists( $usergrouptable_name ) ) {
		$sql = 'CREATE TABLE ' . $usergrouptable_name . " (
         person_id int(11),
         group_id int(11)
         ) $charset $collate;";
		maybe_create_table( $usergrouptable_name, $sql );
	}
}

function eme_create_categories_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_CATEGORIES_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         category_id int(11) NOT NULL auto_increment,
         category_name tinytext NOT NULL,
         description text,
         category_prefix text,
         category_slug text,
         UNIQUE KEY  (category_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'category_prefix', "ALTER TABLE $table_name ADD category_prefix text;" );
		maybe_add_column( $table_name, 'category_slug', "ALTER TABLE $table_name ADD category_slug text;" );
		maybe_add_column( $table_name, 'description', "ALTER TABLE $table_name ADD description text;" );
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		if ( $db_version < 66 ) {
			$categories = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
			foreach ( $categories as $this_category ) {
				$where                   = [];
				$fields                  = [];
				$where['category_id']    = $this_category['category_id'];
				$fields['category_slug'] = eme_permalink_convert_noslash( $this_category['category_name'] );
				$wpdb->update( $table_name, $fields, $where );
			}
		}
	}
}

function eme_create_holidays_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_HOLIDAYS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         name tinytext NOT NULL,
         list text NOT NULL,
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
	}
}

function eme_create_templates_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_TEMPLATES_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         name tinytext,
         description tinytext,
         format text NOT NULL,
         type tinytext,
         properties text,
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'type', "ALTER TABLE $table_name ADD type tinytext;" );
		maybe_add_column( $table_name, 'properties', "ALTER TABLE $table_name ADD properties text;" );
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		if ( $db_version < 41 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY format text NOT NULL;" );
		}
		if ( $db_version < 144 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY type tinytext;" );
			$wpdb->query( "UPDATE $table_name SET type='' WHERE type IS NULL;" );
		}
		if ( $db_version < 151 ) {
			if ( ! eme_column_exists( $table_name, 'name' ) ) {
					maybe_add_column( $table_name, 'name', "ALTER TABLE $table_name ADD name tinytext;" );
					$wpdb->query( "UPDATE $table_name SET name = description;" );
			}
		}
	}
}

function eme_create_formfields_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_FORMFIELDS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         field_id int(11) NOT NULL auto_increment,
         field_type tinytext NOT NULL,
         field_name tinytext NOT NULL,
         field_values text NOT NULL,
         admin_values text,
         field_tags text,
         admin_tags text,
         field_attributes tinytext,
         field_purpose tinytext,
         field_condition tinytext,
         field_required bool DEFAULT 0,
         export bool DEFAULT 0,
         extra_charge bool DEFAULT 0,
         searchable bool DEFAULT 0,
         UNIQUE KEY  (field_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		maybe_add_column( $table_name, 'field_tags', "ALTER TABLE $table_name ADD field_tags text;" );
		maybe_add_column( $table_name, 'admin_values', "ALTER TABLE $table_name ADD admin_values text;" );
		maybe_add_column( $table_name, 'admin_tags', "ALTER TABLE $table_name ADD admin_tags text;" );
		maybe_add_column( $table_name, 'field_attributes', "ALTER TABLE $table_name ADD field_attributes tinytext;" );
		maybe_add_column( $table_name, 'extra_charge', "ALTER TABLE $table_name ADD extra_charge bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'field_condition', "ALTER TABLE $table_name ADD field_condition tinytext;" );
		maybe_add_column( $table_name, 'field_required', "ALTER TABLE $table_name ADD field_required bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'export', "ALTER TABLE $table_name ADD export bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'searchable', "ALTER TABLE $table_name ADD searchable bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'field_purpose', "ALTER TABLE $table_name ADD field_purpose tinytext;" );
		if ( $db_version < 104 ) {
			eme_maybe_drop_column( $table_name, 'field_pattern' );
		}
		if ( $db_version < 154 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE field_info field_values text NOT NULL;" );
		}
		if ( $db_version < 54 ) {
			$wpdb->query( 'UPDATE ' . $table_name . ' SET field_tags=field_values' );
		}
		if ( $db_version < 166 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE field_type old_type mediumint(9);" );
			maybe_add_column( $table_name, 'field_type', "ALTER TABLE $table_name ADD field_type tinytext NOT NULL;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='text' WHERE old_type=1;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='dropdown' WHERE old_type=2;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='textarea' WHERE old_type=3;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='radiobox' WHERE old_type=4;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='radiobox_vertical' WHERE old_type=5;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='checkbox' WHERE old_type=6;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='checkbox_vertical' WHERE old_type=7;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='date' WHERE old_type=8;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='date_js' WHERE old_type=9;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='dropdown_multi' WHERE old_type=10;" );
			eme_drop_table( $db_prefix . EME_FIELDTYPES_TBNAME );
		}
		// the next one is to fix older issues
		if ( $db_version < 193 ) {
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='radiobox_vertical' WHERE old_type=5;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='checkbox_vertical' WHERE old_type=7;" );
			eme_drop_table( $db_prefix . EME_FIELDTYPES_TBNAME );
		}
		if ( $db_version < 214 ) {
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_purpose='generic' WHERE field_purpose IS NULL;" );
		}
		if ( $db_version < 215 ) {
			eme_maybe_drop_column( $table_name, 'old_type' );
		}
	}
}

function eme_create_answers_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_ANSWERS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         answer_id int(11) NOT NULL auto_increment,
         type varchar(20) DEFAULT NULL,
         related_id mediumint(9) DEFAULT 0,
         booking_id mediumint(9) DEFAULT 0,
         person_id mediumint(9) DEFAULT 0,
         member_id mediumint(9) DEFAULT 0,
         field_id int(11) DEFAULT 0,
         answer text NOT NULL,
         eme_grouping int(11) DEFAULT 0,
         occurence int(11) DEFAULT 0,
         UNIQUE KEY  (answer_id),
         KEY  (type),
         KEY  (related_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		if ( $db_version == 23 ) {
			$wpdb->query( 'ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY' );
		}
		if ( $db_version < 112 ) {
			maybe_add_column( $table_name, 'field_id', "ALTER TABLE $table_name ADD field_id INT(11) DEFAULT 0;" );
			$formfield_table_name = $db_prefix . EME_FORMFIELDS_TBNAME;
			$res                  = $wpdb->query( "UPDATE $table_name SET field_id = (select field_id from $formfield_table_name where field_name = $table_name.field_name LIMIT 1);" );
			if ( $res !== false ) {
				eme_maybe_drop_column( $table_name, 'field_name' );
			}
		}
		if ( $db_version < 125 ) {
			maybe_add_column( $table_name, 'occurence', "ALTER TABLE $table_name ADD occurence INT(11) DEFAULT 0;" );
		}
		if ( $db_version < 138 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY booking_id mediumint(9) DEFAULT 0;" );
			maybe_add_column( $table_name, 'person_id', "ALTER TABLE $table_name ADD person_id MEDIUMINT(9) DEFAULT 0;" );
		}
		if ( $db_version < 143 ) {
			maybe_add_column( $table_name, 'member_id', "ALTER TABLE $table_name ADD member_id MEDIUMINT(9) DEFAULT 0;" );
		}
		if ( $db_version < 156 ) {
			$wpdb->query( 'ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY' );
			$wpdb->query( 'ALTER TABLE ' . $table_name . ' ADD answer_id INT(11) PRIMARY KEY AUTO_INCREMENT;' );
		}
		if ( $db_version < 161 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE answer_id answer_id INT(11) PRIMARY KEY AUTO_INCREMENT;" );
		}
		if ( $db_version < 279 ) {
			if ( eme_column_exists( $table_name, 'grouping' ) ) {
					$wpdb->query( "ALTER TABLE $table_name CHANGE grouping eme_grouping INT(11) DEFAULT 0;" );
			}
			maybe_add_column( $table_name, 'eme_grouping', "ALTER TABLE $table_name ADD eme_grouping INT(11) DEFAULT 0;" );
		}
		if ( $db_version < 304 ) {
			maybe_add_column( $table_name, 'related_id', "ALTER TABLE $table_name ADD related_id MEDIUMINT(9) DEFAULT 0;" );
			maybe_add_column( $table_name, 'type', "ALTER TABLE $table_name ADD type VARCHAR(20) DEFAULT NULL;" );
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `related_id` )" );
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `type` )" );
			$wpdb->query( "UPDATE $table_name SET related_id=person_id,type='person' WHERE person_id>0" );
			eme_maybe_drop_column( $table_name, 'person_id' );
			$wpdb->query( "UPDATE $table_name SET related_id=member_id,type='member' WHERE member_id>0" );
			eme_maybe_drop_column( $table_name, 'member_id' );
			$wpdb->query( "UPDATE $table_name SET related_id=booking_id,type='booking' WHERE booking_id>0" );
			eme_maybe_drop_column( $table_name, 'booking_id' );
			$cf_table_name = $db_prefix . EME_MEMBERSHIPS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
					$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`membership_id`,`answer`,'membership' FROM $cf_table_name" );
					eme_drop_table( $cf_table_name );
			}
			$cf_table_name = $db_prefix . EME_EVENTS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
				$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`event_id`,`answer`,'event' FROM $cf_table_name" );
				eme_drop_table( $cf_table_name );
			}
			$cf_table_name = $db_prefix . EME_LOCATIONS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
					$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`location_id`,`answer`,'location' FROM $cf_table_name" );
					eme_drop_table( $cf_table_name );
			}
		}
	}
}

function eme_create_payments_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_PAYMENTS_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         creation_date datetime,
         random_id varchar(50),
         target varchar(50),
         pg_pid varchar(256),
         pg_handled BOOL DEFAULT 0,
         UNIQUE KEY  (id),
         KEY  (random_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'modif_date' );
		maybe_add_column( $table_name, 'pg_handled', "ALTER TABLE $table_name ADD pg_handled BOOL DEFAULT 0;" );
		maybe_add_column( $table_name, 'target', "ALTER TABLE $table_name ADD target varchar(50);" );
		maybe_add_column( $table_name, 'random_id', "ALTER TABLE $table_name ADD random_id varchar(50);" );
		maybe_add_column( $table_name, 'creation_date', "ALTER TABLE $table_name ADD creation_date datetime;" );
		eme_maybe_drop_column( $table_name, 'creation_date_gmt' );
		eme_maybe_drop_column( $table_name, 'attend_count' );
		if ( $db_version < 80 ) {
			$payment_ids = $wpdb->get_col( "SELECT id FROM $table_name" );
			foreach ( $payment_ids as $payment_id ) {
				$random_id = eme_random_id();
				$sql       = $wpdb->prepare( "UPDATE $table_name SET random_id = %s WHERE id = %d", $random_id, $payment_id );
				$wpdb->query( $sql );
			}
		}
		if ( $db_version < 104 ) {
			eme_maybe_drop_column( $table_name, 'booking_ids' );
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
		}
		if ( $db_version < 322 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE random_id random_id varchar(50);" );
			add_clean_index( $table_name, 'random_id' );
		}
		if ( $db_version < 325 ) {
			// else if pg_pids exists: rename to pg_pid and change type to varchar, otherwise create it
			if ( eme_column_exists( $table_name, 'pg_pids' ) ) {
					$wpdb->query( "ALTER TABLE $table_name CHANGE pg_pids pg_pid varchar(256);" );
			} else {
				maybe_add_column( $table_name, 'pg_pid', "ALTER TABLE $table_name ADD pg_pid varchar(256);" );
			}
		}
		if ( $db_version < 358 ) {
			$wpdb->query( "UPDATE $table_name set target='member' where member_id>0;" );
			eme_maybe_drop_column( $table_name, 'member_id' );
		}
	}
}

function eme_create_discounts_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_DISCOUNTS_TBNAME;

	// coupon types: 1=fixed,2=percentage,3=code (filter),4=fixed_per_seat
	// column coupon: text to be entered by booker
	// column value: the applied discount (converted in php to floating point)
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         name varchar(50) DEFAULT NULL,
         description tinytext,
         type tinyint UNSIGNED DEFAULT 0,
         coupon tinytext,
         dgroup tinytext,
         value tinytext,
         maxcount tinyint UNSIGNED DEFAULT 0,
         count tinyint UNSIGNED DEFAULT 0,
         strcase bool DEFAULT 1,
         use_per_seat bool DEFAULT 0,
         valid_from datetime DEFAULT NULL, 
         valid_to datetime DEFAULT NULL, 
         properties text,
         UNIQUE KEY  (id),
         UNIQUE KEY  (name)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'valid_from', "ALTER TABLE $table_name ADD valid_from datetime DEFAULT NULL;" );
		maybe_add_column( $table_name, 'valid_to', "ALTER TABLE $table_name ADD valid_to datetime DEFAULT NULL;" );
		maybe_add_column( $table_name, 'strcase', "ALTER TABLE $table_name ADD strcase bool DEFAULT 1;" );
		maybe_add_column( $table_name, 'use_per_seat', "ALTER TABLE $table_name ADD use_per_seat bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'properties', "ALTER TABLE $table_name ADD properties text;" );
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		if ( $db_version < 254 ) {
			if ( eme_column_exists( $table_name, 'expire' ) ) {
					$wpdb->query( "UPDATE $table_name SET valid_to=CONCAT(expire,' 23:59:00') WHERE expire IS NOT NULL;" );
					eme_maybe_drop_column( $table_name, 'expire' );
			}
		}
	}
}

function eme_create_discountgroups_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . DISCOUNTEME_GROUPS_TBNAME;

	// column maxdiscounts: max number of discounts in a group that can
	// be used, 0 for no max (this to avoid hackers from adding discount fields
	// to a form)
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         description tinytext,
         name varchar(50) DEFAULT NULL,
         maxdiscounts tinyint UNSIGNED DEFAULT 0,
         UNIQUE KEY  (id),
         UNIQUE KEY  (name)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		maybe_add_column( $table_name, 'description', "ALTER TABLE $table_name ADD description tinytext;" );
	}
}

function eme_create_mqueue_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_MQUEUE_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         mailing_id int(11) DEFAULT 0,
         person_id int(11) DEFAULT 0,
         member_id int(11) DEFAULT 0,
         status tinyint DEFAULT 0,
         creation_date datetime,
         sent_datetime datetime NOT NULL DEFAULT '0000-00-00 00:00:00', 
         first_read_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00', 
         last_read_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00', 
         read_count int DEFAULT 0,
         fromemail tinytext,
         fromname tinytext,
         receiveremail tinytext,
         receivername tinytext,
         replytoemail tinytext,
         replytoname tinytext,
         subject tinytext,
         body text,
         random_id varchar(50),
         error_msg tinytext,
         attachments text,
         UNIQUE KEY  (id),
         KEY  (status),
         KEY  (random_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'mailing_id', "ALTER TABLE $table_name ADD mailing_id int(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'person_id', "ALTER TABLE $table_name ADD person_id int(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'member_id', "ALTER TABLE $table_name ADD member_id int(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'random_id', "ALTER TABLE $table_name ADD random_id varchar(50);" );
		maybe_add_column( $table_name, 'read_count', "ALTER TABLE $table_name ADD read_count int DEFAULT 0;" );
		maybe_add_column( $table_name, 'error_msg', "ALTER TABLE $table_name ADD error_msg tinytext;" );
		maybe_add_column( $table_name, 'attachments', "ALTER TABLE $table_name ADD attachments text;" );
		maybe_add_column( $table_name, 'fromname', "ALTER TABLE $table_name ADD fromname tinytext;" );
		maybe_add_column( $table_name, 'fromemail', "ALTER TABLE $table_name ADD fromemail tinytext;" );
		if ( $db_version < 140 ) {
			$wpdb->query( "ALTER TABLE $table_name DROP KEY is_sent;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE is_sent state tinyint DEFAULT 0;" );
		}
		if ( $db_version < 153 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE state status tinyint DEFAULT 0;" );
			add_clean_index( $table_name, 'status' );
		}
		if ( $db_version < 292 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE random_id random_id varchar(50);" );
			add_clean_index( $table_name, 'random_id' );
		}
		if ( $db_version < 293 ) {
			if ( eme_column_exists( $table_name, 'read_datetime' ) ) {
					$wpdb->query( "ALTER TABLE $table_name CHANGE read_datetime first_read_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00';" );
			} else {
				maybe_add_column( $table_name, 'first_read_on', "ALTER TABLE $table_name ADD first_read_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00';" );
			}
			maybe_add_column( $table_name, 'last_read_on', "ALTER TABLE $table_name ADD last_read_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00';" );
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
		}
	}

	$table_name = $db_prefix . EME_MAILINGS_TBNAME;
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         name varchar(255) DEFAULT NULL,
	 planned_on datetime DEFAULT '0000-00-00 00:00:00', 
         creation_date datetime,
         read_count int DEFAULT 0,
         total_read_count int DEFAULT 0,
         subject tinytext,
         body text,
         fromemail tinytext,
         fromname tinytext,
         replytoemail tinytext,
         replytoname tinytext,
         mail_text_html tinytext,
         status tinytext,
         stats varchar(255) DEFAULT '',
         conditions text,
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'status', "ALTER TABLE $table_name ADD status tinytext;" );
		maybe_add_column( $table_name, 'stats', "ALTER TABLE $table_name ADD stats varchar(255) DEFAULT '';" );
		maybe_add_column( $table_name, 'replytoname', "ALTER TABLE $table_name ADD replytoname tinytext;" );
		maybe_add_column( $table_name, 'replytoemail', "ALTER TABLE $table_name ADD replytoemail tinytext;" );
		maybe_add_column( $table_name, 'fromname', "ALTER TABLE $table_name ADD fromname tinytext;" );
		maybe_add_column( $table_name, 'fromemail', "ALTER TABLE $table_name ADD fromemail tinytext;" );
		maybe_add_column( $table_name, 'mail_text_html', "ALTER TABLE $table_name ADD mail_text_html tinytext;" );
		maybe_add_column( $table_name, 'subject', "ALTER TABLE $table_name ADD subject tinytext;" );
		maybe_add_column( $table_name, 'body', "ALTER TABLE $table_name ADD body text;" );
		maybe_add_column( $table_name, 'conditions', "ALTER TABLE $table_name ADD conditions text ;" );
		maybe_add_column( $table_name, 'read_count', "ALTER TABLE $table_name ADD read_count int DEFAULT 0;" );
		maybe_add_column( $table_name, 'total_read_count', "ALTER TABLE $table_name ADD total_read_count int DEFAULT 0;" );
		if ( $db_version < 176 ) {
			$wpdb->query( "UPDATE $table_name set status='cancelled' where cancelled=1;" );
			eme_maybe_drop_column( $table_name, 'cancelled' );
		}
		if ( $db_version < 177 ) {
			$wpdb->query( "UPDATE $table_name set total_read_count=read_count" );
			eme_maybe_drop_column( $table_name, 'mail_count' );
			$mailings = eme_get_mailings();
			foreach ( $mailings as $mailing ) {
				$id    = $mailing['id'];
				$stats = eme_get_mailing_stats( $id );
				if ( $mailing['status'] == 'cancelled' ) {
					eme_cancel_mailing( $id );
				} elseif ( $stats['planned'] > 0 && $stats['sent'] == 0 ) {
					eme_mark_mailing_planned( $id );
				} elseif ( $stats['planned'] > 0 && $stats['sent'] > 0 ) {
					eme_mark_mailing_ongoing( $id );
				} else {
					eme_mark_mailing_completed( $id );
				}
			}
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
		}
		if ( $db_version < 316 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY name varchar(255) DEFAULT NULL;" );
		}
	}
}
function eme_create_members_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_MEMBERS_TBNAME;

	// state contains the defined/calculated state
	// autostate indicates if the state needs to be calculated automatically
	//    or if it is set manually
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         member_id int(11) NOT NULL auto_increment,
         related_member_id int(11) DEFAULT 0,
         membership_id int(11) DEFAULT 0,
         person_id int(11) DEFAULT 0,
         status tinyint DEFAULT 0,
         status_automatic BOOL DEFAULT 1,
         creation_date datetime,
         modif_date datetime,
         last_seen datetime DEFAULT '0000-00-00 00:00:00',
         start_date date NOT NULL DEFAULT '0000-00-00', 
         end_date date NOT NULL DEFAULT '0000-00-00', 
         reminder INT(11) DEFAULT 0,
         reminder_date datetime DEFAULT '0000-00-00 00:00:00',
         renewal_count INT(11) DEFAULT 0,
         unique_nbr varchar(20),
         payment_id mediumint(9) DEFAULT NULL, 
         payment_date datetime DEFAULT '0000-00-00 00:00:00',
         paid bool DEFAULT 0,
         pg tinytext,
         pg_pid tinytext,
         extra_charge tinytext,
         discount tinytext,
         discountids tinytext,
         dcodes_entered tinytext,
         dcodes_used tinytext,
         dgroupid INT(11) DEFAULT 0,
         properties text,
         UNIQUE KEY  (member_id),
         KEY  (related_member_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'reminder', "ALTER TABLE $table_name ADD reminder INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'related_member_id', "ALTER TABLE $table_name ADD related_member_id INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'renewal_count', "ALTER TABLE $table_name ADD renewal_count INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'payment_id', "ALTER TABLE $table_name ADD payment_id INT(9) DEFAULT NULL;" );
		maybe_add_column( $table_name, 'payment_date', "ALTER TABLE $table_name ADD payment_date datetime DEFAULT '0000-00-00 00:00:00';" );
		maybe_add_column( $table_name, 'reminder_date', "ALTER TABLE $table_name ADD reminder_date datetime DEFAULT '0000-00-00 00:00:00';" );
		maybe_add_column( $table_name, 'last_seen', "ALTER TABLE $table_name ADD last_seen datetime DEFAULT '0000-00-00 00:00:00';" );
		maybe_add_column( $table_name, 'paid', "ALTER TABLE $table_name ADD paid bool DEFAULT 0;" );
		maybe_add_column( $table_name, 'creation_date', "ALTER TABLE $table_name ADD creation_date datetime;" );
		maybe_add_column( $table_name, 'modif_date', "ALTER TABLE $table_name ADD modif_date datetime;" );
		maybe_add_column( $table_name, 'pg', "ALTER TABLE $table_name ADD pg tinytext;" );
		maybe_add_column( $table_name, 'pg_pid', "ALTER TABLE $table_name ADD pg_pid tinytext;" );
		maybe_add_column( $table_name, 'discount', "ALTER TABLE $table_name ADD discount tinytext;" );
		eme_maybe_drop_column( $table_name, 'discountid' );
		maybe_add_column( $table_name, 'discountids', "ALTER TABLE $table_name ADD discountids tinytext;" );
		maybe_add_column( $table_name, 'dgroupid', "ALTER TABLE $table_name ADD dgroupid INT(11) DEFAULT 0;" );
		maybe_add_column( $table_name, 'dcodes_entered', "ALTER TABLE $table_name ADD dcodes_entered tinytext ;" );
		maybe_add_column( $table_name, 'dcodes_used', "ALTER TABLE $table_name ADD dcodes_used tinytext ;" );
		maybe_add_column( $table_name, 'properties', "ALTER TABLE $table_name ADD properties text;" );
		if ( eme_column_exists( $table_name, 'transfer_nbr_be97' ) ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE transfer_nbr_be97 unique_nbr varchar(20);" );
		} else {
			maybe_add_column( $table_name, 'unique_nbr', "ALTER TABLE $table_name ADD unique_nbr varchar(20);" );
		}

		if ( $db_version < 153 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE state status tinyint DEFAULT 0;" );
			$wpdb->query( "ALTER TABLE $table_name CHANGE state_automatic status_automatic BOOL DEFAULT 1;" );
		}
		if ( $db_version < 296 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
			$wpdb->query( "ALTER TABLE $table_name MODIFY modif_date datetime ;" );
		}
		if ( $db_version < 262 ) {
			$wpdb->query( "ALTER TABLE $table_name MODIFY extra_charge tinytext;" );
		}
		if ( $db_version < 306 ) {
			$wpdb->query( "ALTER TABLE `$table_name` ADD INDEX ( `related_member_id` )" );
		}
	}

	// properties: templ.id's for form, and subject/body for payment, confirm, reminder, stop templates
	//             and info concerning payment gateways
	//             and info concerning reminder days before sending reminder
	// type: fixed/rolling
	// duration_count+duration_period form a logical date format: 1 days, 2 days, 3 weeks, etc ...
	// start date: only used for type fixed
	$table_name = $db_prefix . EME_MEMBERSHIPS_TBNAME;
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         membership_id int(11) NOT NULL auto_increment,
         name varchar(50) DEFAULT NULL,
         description tinytext,
         type varchar(50) DEFAULT NULL,
         start_date date DEFAULT '0000-00-00', 
         duration_count tinyint DEFAULT 0,
         duration_period varchar(50) DEFAULT '',
         properties text,
         UNIQUE KEY  (membership_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'modif_date' );
		eme_maybe_drop_column( $table_name, 'creation_date' );
	}
}

function eme_create_countries_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_COUNTRIES_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         alpha_2 char(2) DEFAULT NULL,
         alpha_3 char(3) DEFAULT NULL,
         num_3 char(3) DEFAULT NULL,
         name varchar(100) DEFAULT NULL,
         lang varchar(10) DEFAULT '',
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
		if ( $db_version < 246 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE num_3 num_3 char(3) DEFAULT NULL;" );
		}
		if ( $db_version < 319 ) {
			$wpdb->query( "ALTER TABLE $table_name CHANGE locale lang varchar(10) DEFAULT '';" );
		}
	}
}

function eme_create_states_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_STATES_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         code tinytext,
         name varchar(100) DEFAULT NULL,
         country_id int(11) DEFAULT NULL,
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		eme_maybe_drop_column( $table_name, 'creation_date' );
		eme_maybe_drop_column( $table_name, 'modif_date' );
	}
}

function eme_create_task_tables( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_TASKS_TBNAME;

	// the sequence of the tasks is decided per event and stored as task_seq
	// when updating a task, we will use the combo event_id and task_nbr, so we can use that for
	// recurrences too
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         task_id mediumint(9) NOT NULL AUTO_INCREMENT,
         event_id mediumint(9) NOT NULL,
         task_start datetime,
         task_end datetime,
         task_seq smallint DEFAULT 1,
         task_nbr smallint DEFAULT 1,
         name varchar(50) DEFAULT NULL,
         spaces smallint DEFAULT 1,
	 description text,
         UNIQUE KEY  (task_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'event_id', "ALTER TABLE $table_name ADD event_id mediumint(9) NOT NULL;" );
		maybe_add_column( $table_name, 'task_seq', "ALTER TABLE $table_name ADD task_seq smallint DEFAULT 1;" );
		maybe_add_column( $table_name, 'task_nbr', "ALTER TABLE $table_name ADD task_nbr smallint DEFAULT 1;" );
	}

	$table_name = $db_prefix . EME_TASK_SIGNUPS_TBNAME;
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
	 id int(11) NOT NULL auto_increment,
         task_id mediumint(9) NOT NULL,
         person_id mediumint(9) NOT NULL,
         event_id mediumint(9) NOT NULL,
         signup_status BOOL DEFAULT 1;
         comment text,
         random_id varchar(50),
         UNIQUE KEY  (id),
         KEY  (event_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	} else {
		maybe_add_column( $table_name, 'comment', "ALTER TABLE $table_name ADD comment text;" );
		if ( $db_version < 367 ) {
			$wpdb->query( "ALTER TABLE $table_name ADD signup_status BOOL DEFAULT 1;" );
		}
	}
}

function eme_create_attendances_table( $charset, $collate, $db_version, $db_prefix ) {
	global $wpdb;
	$table_name = $db_prefix . EME_ATTENDANCES_TBNAME;

	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         type varchar(20) DEFAULT NULL,
         person_id int(11) DEFAULT NULL,
         related_id int(11) DEFAULT NULL,
         creation_date datetime,
         UNIQUE KEY  (id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	}
	if ( $db_version < 296 ) {
		$wpdb->query( "ALTER TABLE $table_name MODIFY creation_date datetime ;" );
	}
}

function eme_create_events_page() {
	$postarr = [
		'post_title'     => wp_strip_all_tags( __( 'Events', 'events-made-easy' ) ),
		'post_content'   => __( "This page is used by Events Made Easy. Don't change it, don't use it in your menu's, don't delete it. Just make sure the EME setting called 'Events page' points to this page. EME uses this page to render any and all events, locations, bookings, maps, ... anything. If you do want to delete this page, create a new one EME can use and update the EME setting 'Events page' accordingly.", 'events-made-easy' ),
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	];
	$int_post_id = wp_insert_post( $postarr );
	if ( $int_post_id ) {
		update_option( 'eme_events_page', $int_post_id );
	}
}

function eme_delete_events_page() {
	$events_page_id = eme_get_events_page_id();
	if ( $events_page_id ) {
		wp_delete_post( $events_page_id );
	}
}

