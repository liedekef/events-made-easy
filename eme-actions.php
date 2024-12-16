<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// the early init runs before theme functions.php is loaded, so we only call things that don't call custom filters
function eme_actions_early_init() {
	$eme_is_admin_request = eme_is_admin_request();
	if ( !empty( $_GET['eme_captcha'] ) && $_GET['eme_captcha'] == 'generate' && !empty( $_GET['f'] ) ) {
		$captcha_id = eme_sanitize_filenamechars( $_GET['f'] );
		if ( $_GET['f']==$captcha_id && ! eme_is_empty_string( $captcha_id ) && get_option( 'eme_captcha_for_forms' ) ) {
			eme_captcha_generate( $captcha_id );
		}
		exit;
	}
	if ( !empty( $_GET['eme_tracker_id'] ) ) {
		$tracker_id = eme_sanitize_filenamechars( $_GET['eme_tracker_id'] );
		if ( ! eme_is_empty_string( $tracker_id ) ) {
			eme_mail_track( $tracker_id );
		}
		exit;
	}

    if ( get_query_var( 'eme_rsvp_proof' ) && !empty( $_GET['bid'] ) && !empty( $_GET['eme_hash'] ) ) {
        $booking_id = intval( $_GET['bid'] );
        $get_rsvp_proof_hash = eme_sanitize_request( $_GET['eme_hash'] );
        $calc_rsvp_proof_hash = wp_hash( $booking_id . '|' . 'rsvp_proof' , 'nonce' );
        if ( $get_rsvp_proof_hash == $calc_rsvp_proof_hash ) {
            $booking = eme_get_booking( $booking_id );
            if (!empty($booking) && $booking['attend_count'] > 0 ) {
                $payment = eme_get_payment ($booking['payment_id']);
                $event   = eme_get_event( $booking['event_id'] );
                if ( $event && $payment && $payment['target'] == 'booking' ) {
                    // the 1 param at the end causes direct streaming to the browser
                    eme_generate_booking_pdf($booking, $event, $event['event_properties']['attendance_rsvp_proof_tpl'], 1);
                }
            }
        }
    }

	if ( isset( $_POST['eme_ajax_action'] ) && $_POST['eme_ajax_action'] == 'task_autocomplete_people' && isset( $_POST['task_lastname'] ) ) {
		check_ajax_referer( 'eme_frontend', 'eme_frontend_nonce' );
		$no_wp_die = 1;
		if ( is_user_logged_in() && isset( $_POST['eme_event_ids'] ) ) {
			$event          = eme_get_event( intval( $_POST['eme_event_ids'][0] ) );
			$current_userid = get_current_user_id();
			if ( ! empty( $event ) && ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
			( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) ) {
				eme_people_autocomplete_ajax( $no_wp_die );
			}
		}
		exit;
	}

	if ( isset( $_POST['eme_ajax_action'] ) && $_POST['eme_ajax_action'] == 'rsvp_autocomplete_people' && isset( $_POST['lastname'] ) ) {
		$no_wp_die = 1;

		if ( isset( $_POST['event_id'] ) && $eme_is_admin_request ) {
			check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
			// this is the case for new registrations in the backend
			$event_id = intval( $_POST['event_id'] );
			$event    = eme_get_event( $event_id );
			if ( !empty( $event )) 
				eme_people_autocomplete_ajax( $no_wp_die, $event['registration_wp_users_only'] );
		} elseif ( isset( $_POST['booking_id'] ) && $eme_is_admin_request ) {
			check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
			// this is the case for updating a registration in the backend
			$booking_id = intval( $_POST['booking_id'] );
			$event      = eme_get_event_by_booking_id( $booking_id );
			if ( !empty( $event )) 
				eme_people_autocomplete_ajax( $no_wp_die, $event['registration_wp_users_only'] );
		} elseif ( isset( $_POST['membership_id'] ) && is_user_logged_in() ) {
			if ( ( ! isset( $_POST['eme_admin_nonce'] ) && ! isset( $_POST['eme_frontend_nonce'] ) ) ||
			( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( $_POST['eme_admin_nonce'], 'eme_admin' ) ) ||
			( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( $_POST['eme_frontend_nonce'], 'eme_frontend' ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
				if ( wp_doing_ajax() ) {
					wp_die( -1, 403 );
				} else {
					die( '-1' );
				}
			}
			if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
				$membership = eme_get_membership( intval( $_POST['membership_id'] ) );
				eme_people_autocomplete_ajax( $no_wp_die, $membership['properties']['registration_wp_users_only'] );
			}
		} elseif ( is_user_logged_in() && isset( $_POST['eme_event_ids'] ) ) {
			check_ajax_referer( 'eme_frontend', 'eme_frontend_nonce' );
			$event          = eme_get_event( intval( $_POST['eme_event_ids'][0] ) );
			$current_userid = get_current_user_id();
			if ( ! empty( $event ) && ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
			( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) ) {
				eme_people_autocomplete_ajax( $no_wp_die, $event['registration_wp_users_only'] );
			}
		} else {
			header( 'Content-type: application/json; charset=utf-8' );
			echo wp_json_encode( [] );
		}
		exit;
	}
}

function eme_actions_init() {
	// first the no cache headers
	//eme_nocache_headers();
	eme_load_textdomain();

	$eme_is_admin_request = eme_is_admin_request();

	// now, first update if needed
	$db_version = intval( get_option( 'eme_version' ) );
	if ( $db_version && $db_version != EME_DB_VERSION ) {
		_eme_install();
	}

	// now first all ajax ops: exit needed
	if ( isset( $_GET ['eme_ical'] ) && $_GET ['eme_ical'] == 'public_single' && isset( $_GET ['event_id'] ) ) {
		eme_ical_single();
		exit;
	}
	if ( isset( $_GET ['eme_ical'] ) && $_GET ['eme_ical'] == 'public' ) {
		eme_ical();
		exit;
	}
	if ( isset( $_GET ['eme_sitemap'] ) && $_GET ['eme_sitemap'] == 'public' ) {
		eme_sitemap();
		exit;
	}
	if ( isset( $_GET['eme_rss'] ) && $_GET['eme_rss'] == 'main' ) {
		eme_rss();
		exit;
	}

	if ( isset( $_GET['eme_admin_action'] ) && $eme_is_admin_request ) {
		if ( $_GET['eme_admin_action'] == 'autocomplete_locations' ) {
			$no_wp_die = 1;
			eme_locations_search_ajax( $no_wp_die );
			exit;
		}
		if ( $_GET['eme_admin_action'] == 'booking_printable' && isset( $_GET['event_id'] ) ) {
			eme_printable_booking_report( intval( $_GET['event_id'] ) );
			exit();
		}
		if ( $_GET['eme_admin_action'] == 'booking_csv' && isset( $_GET['event_id'] ) ) {
			eme_csv_booking_report( intval( $_GET['event_id'] ) );
			exit();
		}
	}

	# payment notifications can apply filters in eme_get_configured_pgs(), so these need to be in eme_actions_init, not in eme_actions_early_init
	// payment charges and eme_get_configured_pgs can apply custom filters, so we leave these in eme_actions_init too
	if ( isset( $_REQUEST['eme_eventAction'] ) ) {
		$configured_pgs = eme_get_configured_pgs();
		foreach ($configured_pgs as $pg) {
			// don't care if it is GET or POST for notifications (most use GET, fdgg uses POST)
			$notification_function = 'eme_notification_'.$pg;
			if ( $_REQUEST['eme_eventAction'] == $pg.'_notification' && function_exists($notification_function)) {
				$notification_function();
				if ($pg != "opayo") {
					// opayo doesn't use a notification url, but sends the status along as part of the return url, so we just check
					// the status and set paid or not, but then we continue regular flow of events
					exit();
				}
			}

			// charge calls normally come in via POST, but let's be generic and use REQUEST too
			$charge_function = 'eme_charge_'.$pg;
			if ( $_REQUEST['eme_eventAction'] == $pg.'_charge' && function_exists($charge_function)) {
				$charge_function();
			}
		}
	}
}

add_action( 'init', 'eme_actions_init', 1 );
// setup_theme fires before the theme is loaded, thus avoiding issues with themes adding empty lines at the top and thus e.g. rendering captcha invalid
// But then the custom filters in the theme functions.php are not yet loaded, causing issues with a number of hooks here
add_action( 'setup_theme', 'eme_actions_early_init', 1 );

function eme_actions_admin_init() {
	global $current_user, $plugin_page;
	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
	eme_options_register();

	$user_id = $current_user->ID;
	if ( isset( $_GET['eme_notice_ignore'] ) && ( $_GET['eme_notice_ignore'] == 'hello' ) ) {
		update_user_meta( $user_id, 'eme_hello_notice_ignore', $eme_date_obj->format( 'Ymd' ) );
	}
	if ( isset( $_GET['eme_notice_ignore'] ) && ( $_GET['eme_notice_ignore'] == 'donate' ) ) {
		update_user_meta( $user_id, 'eme_donate_notice_ignore', EME_VERSION . $eme_date_obj->format( 'Ymd' ) );
	}

	// do some actions when the settings have been updated
	if ( $plugin_page == 'eme-options' && isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
		eme_options_postsave_actions();
	}

	// add the gdpr text to the example guide
	eme_gdpr_add_suggested_privacy_content();
}
add_action( 'admin_init', 'eme_actions_admin_init' );

// GDPR export and erase filters
add_filter( 'wp_privacy_personal_data_exporters', 'eme_gdpr_register_exporters' );
add_filter( 'wp_privacy_personal_data_erasers', 'eme_gdpr_register_erasers' );

function eme_add_events_locations_link_search( $results, $query ) {
	if ( ! isset( $query['s'] ) ) {
			return $results;
	}
	// Add only on the first result page
	if ( $query['offset'] > 0 ) {
			return $results;
	}
	$events = eme_search_events( $query['s'] );
	foreach ( $events as $event ) {
		$results[] = [
			'ID'        => $event['event_id'],
			'title'     => trim( eme_esc_html( strip_tags( $event['event_name'] ) . ' (' . eme_localized_datetime( $event['event_start'], EME_TIMEZONE ) . ')' ) ),
			'permalink' => eme_event_url( $event ),
			'info'      => __( 'Event', 'events-made-easy' ),
		];
	}
	$locations = eme_search_locations( $query['s'] );
	foreach ( $locations as $location ) {
		$results[] = [
			'ID'        => $location['location_id'],
			'title'     => trim( eme_esc_html( strip_tags( $location['location_name'] ) ) ),
			'permalink' => eme_location_url( $location ),
			'info'      => __( 'Location', 'events-made-easy' ),
		];
	}
	return $results;
}
if ( get_option( 'eme_add_events_locs_link_search' ) ) {
	add_filter( 'wp_link_query', 'eme_add_events_locations_link_search', 10, 2 );
}

function eme_actions_widgets_init() {
	register_widget( 'WP_Widget_eme_list' );
	register_widget( 'WP_Widget_eme_calendar' );
}
add_action( 'widgets_init', 'eme_actions_widgets_init' );

add_action( 'wp_head', 'eme_general_head' );
add_action( 'wp_footer', 'eme_general_footer' );
//if (get_option('eme_load_js_in_header')) {
//   add_action('wp_head', 'eme_ajaxize_calendar');
//} else {
//   add_action('wp_footer', 'eme_ajaxize_calendar');
//}

function eme_admin_register_scripts() {
	wp_register_script( 'eme-select2', EME_PLUGIN_URL . 'js/jquery-select2/select2-4.1.0-rc.0/dist/js/select2.min.js', 'jquery', EME_VERSION );
	// for english, no translation code is needed)
	$language = eme_detect_lang();
	if ( $language != 'en' ) {
		$eme_plugin_dir  = eme_plugin_dir();
		$locale_file     = $eme_plugin_dir . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		$locale_file_url = EME_PLUGIN_URL . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-select2-locale', $locale_file_url, [ 'eme-select2' ], EME_VERSION );
		}
	}
	wp_register_script( 'eme-print', EME_PLUGIN_URL . 'js/jquery.printelement.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-sortable', EME_PLUGIN_URL . 'js/sortable/sortable.min.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-jquery-validate', EME_PLUGIN_URL . 'js/jquery-validate/jquery.validate.min.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-jquery-jtable', EME_PLUGIN_URL . 'js/jtable-2.5.0/jquery.jtable.js', [ ], EME_VERSION );
	wp_register_script( 'eme-jtable-storage', EME_PLUGIN_URL . 'js/jtable-2.5.0/extensions/jquery.jtable.localstorage.js', [ 'eme-jquery-jtable' ], EME_VERSION );
	wp_register_script( 'eme-jtable-search', EME_PLUGIN_URL . 'js/jtable-2.5.0/extensions/jquery.jtable.toolbarsearch.js', [ 'eme-jquery-jtable', 'eme-jtable-storage' ], EME_VERSION );
	if ( wp_script_is( 'eme-select2-locale', 'registered' ) ) {
		wp_register_script( 'eme-basic', EME_PLUGIN_URL . 'js/eme.js', [ 'jquery', 'eme-select2', 'eme-select2-locale' ], EME_VERSION );
	} else {
		wp_register_script( 'eme-basic', EME_PLUGIN_URL . 'js/eme.js', [ 'jquery', 'eme-select2' ], EME_VERSION );
	}
	wp_register_script( 'eme-admin', EME_PLUGIN_URL . 'js/eme_admin.js', [ 'jquery', 'eme-jquery-jtable', 'eme-jtable-storage', 'eme-jquery-validate', 'eme-sortable', 'eme-print' ], EME_VERSION );

	wp_register_style( 'eme-leaflet-css', EME_PLUGIN_URL . 'js/leaflet-1.9.4/leaflet.css', [], EME_VERSION );
	wp_register_script( 'eme-leaflet-maps', EME_PLUGIN_URL . 'js/leaflet-1.9.4/leaflet.js', [ 'jquery' ], EME_VERSION, true );
	wp_register_script( 'eme-edit-maps', EME_PLUGIN_URL . 'js/eme_edit_maps.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_script( 'eme-autocomplete-form', EME_PLUGIN_URL . 'js/eme_autocomplete_form.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-rememberme', EME_PLUGIN_URL . 'js/eme_localstorage.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-options', EME_PLUGIN_URL . 'js/eme_admin_options.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-formfields', EME_PLUGIN_URL . 'js/eme_admin_fields.js', [ 'jquery' ], EME_VERSION );

	$locale_code     = determine_locale();
	$locale_code     = preg_replace( '/_/', '-', $locale_code );
	$locale_file     = EME_PLUGIN_URL . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
	$locale_file_url = EME_PLUGIN_URL . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
	// for english, no translation code is needed)
	if ( $locale_code != 'en-US' ) {
		if ( ! file_exists( $locale_file ) ) {
			$locale_code     = substr( $locale_code, 0, 2 );
			$locale_file     = EME_PLUGIN_URL . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
			$locale_file_url = EME_PLUGIN_URL . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
		}
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-jtable-locale', $locale_file_url, '', EME_VERSION );
		}
	}

	wp_register_script( 'eme-rsvp', EME_PLUGIN_URL . 'js/eme_admin_rsvp.js', [ 'eme-autocomplete-form' ], EME_VERSION );
	wp_register_script( 'eme-sendmails', EME_PLUGIN_URL . 'js/eme_admin_sendmails.js', [], EME_VERSION );
	wp_register_script( 'eme-discounts', EME_PLUGIN_URL . 'js/eme_admin_discounts.js', [ 'eme-jtable-search' ], EME_VERSION );
	wp_register_script( 'eme-countries', EME_PLUGIN_URL . 'js/eme_admin_countries.js', [], EME_VERSION );
	wp_register_script( 'eme-people', EME_PLUGIN_URL . 'js/eme_admin_people.js', [], EME_VERSION );
	wp_register_script( 'eme-templates', EME_PLUGIN_URL . 'js/eme_admin_templates.js', [], EME_VERSION );
	wp_register_script( 'eme-tasksignups', EME_PLUGIN_URL . 'js/eme_admin_tasksignups.js', [], EME_VERSION );
	wp_register_script( 'eme-members', EME_PLUGIN_URL . 'js/eme_admin_members.js', [], EME_VERSION );
	wp_register_script( 'eme-events', EME_PLUGIN_URL . 'js/eme_admin_events.js', [], EME_VERSION );
	wp_register_script( 'eme-locations', EME_PLUGIN_URL . 'js/eme_admin_locations.js', [], EME_VERSION );
	wp_register_script( 'eme-attendances', EME_PLUGIN_URL . 'js/eme_admin_attendances.js', [], EME_VERSION );
	wp_register_style( 'eme_textsec', EME_PLUGIN_URL . 'css/text-security/text-security-disc.css', [], EME_VERSION );
	wp_register_style( 'eme_stylesheet', EME_PLUGIN_URL . 'css/eme.css', [], EME_VERSION );
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		wp_register_style( 'eme_stylesheet_extra', get_stylesheet_directory_uri() . '/eme.css', [ 'eme_stylesheet' ], EME_VERSION );
	}
        wp_register_style( 'eme-jquery-jtable-css', EME_PLUGIN_URL . 'js/jtable-2.5.0/themes/lightcolor/gray/jtable.min.css' );
	//wp_register_style( 'eme-jquery-jtable-css', EME_PLUGIN_URL . 'js/jtable-2.5.0/themes/jqueryui/jtable_jqueryui.css' );
	wp_register_style( 'eme-jquery-select2-css', EME_PLUGIN_URL . 'js/jquery-select2/select2-4.1.0-rc.0/dist/css/select2.min.css' );
	wp_register_style( 'eme-jtables-css', EME_PLUGIN_URL . 'css/jquery.jtables.css' );
    //wp_register_style( 'eme-tabulator-css', EME_PLUGIN_URL . 'js/tabulator/css/tabulator.min.css' );
    //wp_register_style( 'eme-datatables-css', EME_PLUGIN_URL . 'js/datatables/css/datatables.min.css' );
	eme_admin_enqueue_js();
}
add_action( 'admin_enqueue_scripts', 'eme_admin_register_scripts' );

function eme_register_scripts() {
	// the frontend also needs the datepicker (the month filter) and also for custom fields
	if ( get_option( 'eme_load_js_in_header' ) ) {
		$load_js_in_footer = false;
	} else {
		$load_js_in_footer = true;
	}
	$language = eme_detect_lang();

	// if on the payment page and we need to submit immediately, we need jquery loaded in the header
	// so our jquery code to submit the payment form works
	if (get_option( 'eme_pg_submit_immediately' ) && get_query_var( 'eme_pmt_rndid' ) ) {
		wp_enqueue_script( 'jquery' );
	}
	wp_register_script( 'eme-select2', EME_PLUGIN_URL . 'js/jquery-select2/select2-4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	if ( $language != 'en' ) {
		$eme_plugin_dir  = eme_plugin_dir();
		$locale_file     = $eme_plugin_dir . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		$locale_file_url = EME_PLUGIN_URL . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-select2-locale', $locale_file_url, [ 'eme-select2' ], EME_VERSION, $load_js_in_footer );
		}
	}
	wp_register_script( 'eme-basic', EME_PLUGIN_URL . 'js/eme.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	$eme_fs_options = get_option('eme_fs');
	$translation_array = [
		'translate_plugin_url'         => EME_PLUGIN_URL,
		'translate_ajax_url'           => admin_url( 'admin-ajax.php' ),
		'translate_selectstate'        => __( 'State', 'events-made-easy' ),
		'translate_selectcountry'      => __( 'Country', 'events-made-easy' ),
		'translate_frontendnonce'      => wp_create_nonce( 'eme_frontend' ),
		'translate_error'              => __( 'An error has occurred', 'events-made-easy' ),
		'translate_clear'              => __( 'Clear', 'events-made-easy' ),
		'translate_mailingpreferences' => __( 'Mailing preferences', 'events-made-easy' ),
		'translate_yessure'            => __( "Yes, I'm sure", 'events-made-easy' ),
		'translate_firstDayOfWeek'     => get_option( 'start_of_week' ),
		'translate_fs_wysiwyg'         => $eme_fs_options['use_wysiwyg']? 'true': 'false',
		'translate_flanguage'          => $language,
		'translate_fdateformat'        => EME_WP_DATE_FORMAT,
		'translate_ftimeformat'        => EME_WP_TIME_FORMAT,
	];
	wp_localize_script( 'eme-basic', 'emebasic', $translation_array );

	if ( get_option( 'eme_use_client_clock' ) && ! isset( $_COOKIE['eme_client_time'] ) ) {
		// client clock should be executed asap, so load it in the header, and no defer
		$translation_array = [
			'translate_ajax_url' => admin_url( 'admin-ajax.php' ),
		];
		wp_register_script( 'eme-client_clock_submit', EME_PLUGIN_URL . 'js/client-clock.js', [ 'jquery' ], EME_VERSION );
		wp_localize_script( 'eme-client_clock_submit', 'emeclock', $translation_array );
		wp_enqueue_script( 'eme-client_clock_submit' );
	}

	// the frontend also needs the autocomplete (rsvp form)
	$search_tables = get_option( 'eme_autocomplete_sources' );
	if ( $search_tables != 'none' && is_user_logged_in() ) {
		wp_register_script( 'eme-autocomplete-form', EME_PLUGIN_URL . 'js/eme_autocomplete_form.js', [ ], EME_VERSION, $load_js_in_footer );
	}
	wp_register_script( 'eme-rememberme', EME_PLUGIN_URL . 'js/eme_localstorage.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );

	$eme_map_is_active = get_option( 'eme_map_is_active' );
	if ( $eme_map_is_active) {
		wp_register_script( 'eme-leaflet-maps', EME_PLUGIN_URL . 'js/leaflet-1.9.4/leaflet.js', [ 'jquery' ], EME_VERSION, true );
		wp_register_script( 'eme-leaflet-gestures', EME_PLUGIN_URL . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
		wp_register_script( 'eme-leaflet-markercluster', EME_PLUGIN_URL . 'js/leaflet-markercluster-1.4.1/leaflet.markercluster.js', [ 'eme-leaflet-maps' ], EME_VERSION, true );
		wp_register_style( 'eme-leaflet-css', EME_PLUGIN_URL . 'js/leaflet-1.9.4/leaflet.css', [], EME_VERSION );
		wp_register_style( 'eme-markercluster-css1', EME_PLUGIN_URL . 'js/leaflet-markercluster-1.4.1/MarkerCluster.css', [], EME_VERSION );
		wp_register_style( 'eme-markercluster-css2', EME_PLUGIN_URL . 'js/leaflet-markercluster-1.4.1/MarkerCluster.Default.css', [], EME_VERSION );
		wp_register_style( 'eme-gestures-css', EME_PLUGIN_URL . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.css', [], EME_VERSION );
		wp_register_script( 'eme-show-maps', EME_PLUGIN_URL . 'js/eme_show_maps.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
		wp_register_script( 'eme-edit-maps', EME_PLUGIN_URL . 'js/eme_edit_maps.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
		$translation_array = [
			'translate_map_zooming'   => get_option( 'eme_map_zooming' ) ? 'true' : 'false',
			'translate_default_map_icon'  => get_option( 'eme_location_map_icon' ),
		];
		wp_localize_script( 'eme-edit-maps', 'emeeditmaps', $translation_array );
		wp_register_script( 'eme-fs-location', EME_PLUGIN_URL . 'js/eme_fs.js', [ 'jquery', 'eme-leaflet-maps', 'eme-edit-maps' ], EME_VERSION, true );
	} else {
		wp_register_script( 'eme-fs-location', EME_PLUGIN_URL . 'js/eme_fs.js', [ 'jquery' ], EME_VERSION, true );
	}
	$map_enabled = $eme_map_is_active ? 'true' : 'false';
    $translation_array = [
        'translate_ajax_url' => admin_url( 'admin-ajax.php' ),
        'translate_map_is_active' => $map_enabled,
        'translate_nomatchlocation' => __( 'No matching location found', 'events-made-easy' ),
        'translate_frontendnonce' => wp_create_nonce( 'eme_frontend' )
        ];
    wp_localize_script( 'eme-fs-location', 'emefs', $translation_array );

	if ( get_option( 'eme_recaptcha_for_forms' ) ) {
		// using explicit rendering of the captcha would allow to capture the widget id and reset it if needed, but we won't use that ...
		//wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=eme_CaptchaCallback&render=explicit', array('eme-basic'), '',true);
		wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js', [ 'eme-basic' ], '', true );
	}
	if ( get_option( 'eme_hcaptcha_for_forms' ) ) {
		wp_register_script( 'eme-hcaptcha', 'https://js.hcaptcha.com/1/api.js', [ 'eme-basic' ], '', true );
	}
	if ( get_option( 'eme_cfcaptcha_for_forms' ) ) {
		wp_register_script( 'eme-cfcaptcha', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [ 'eme-basic' ], '', true );
	}
}
add_action( 'wp_enqueue_scripts', 'eme_register_scripts' );

function eme_enqueue_frontend() {
	if ( ! wp_script_is( 'eme-basic', 'enqueued' ) ) {
		eme_enqueue_datetimepicker();
		wp_enqueue_script( 'eme-select2' );
		if ( wp_script_is( 'eme-select2-locale', 'registered' ) ) {
			wp_enqueue_script( 'eme-select2-locale' );
		}
		// for english, no translation code is needed)
		wp_enqueue_script( 'eme-basic' );
		wp_enqueue_style( 'eme-jquery-select2-css', EME_PLUGIN_URL . 'js/jquery-select2/select2-4.1.0-rc.0/dist/css/select2.min.css', [], EME_VERSION );

		wp_enqueue_style( 'eme_textsec', EME_PLUGIN_URL . 'css/text-security/text-security-disc.css', [], EME_VERSION );
		wp_enqueue_style( 'eme_stylesheet', EME_PLUGIN_URL . 'css/eme.css', [], EME_VERSION );
		$eme_css_name = get_stylesheet_directory() . '/eme.css';
		if ( file_exists( $eme_css_name ) ) {
			wp_enqueue_style( 'eme_stylesheet_extra', get_stylesheet_directory_uri() . '/eme.css', [ 'eme_stylesheet' ], EME_VERSION );
		}
	}
}

function eme_add_defer_attribute( $tag, $handle ) {
	if ( 'eme-basic' === $handle ) {
		return str_replace( ' src', ' defer="defer" src', $tag );
	}

	if ( 'eme-recaptcha' === $handle || 'eme-hcaptcha' === $handle || 'eme-cfcaptcha' === $handle ) {
		return str_replace( ' src', ' defer="defer" async src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'eme_add_defer_attribute', 10, 2 );

add_action( 'template_redirect', 'eme_template_redir' );
add_action( 'admin_notices', 'eme_admin_notices' );

function eme_admin_notices() {
	global $pagenow, $plugin_page;
	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;
	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );

	$events_page_id = eme_get_events_page_id();
	if ( $pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['post'] ) && $_GET['post'] == "$events_page_id" ) {
		$message = sprintf( __( "This page corresponds to <strong>Events Made Easy</strong> events page. Its content will be overriden by <strong>Events Made Easy</strong>. If you want to display your content, you can can assign another page to <strong>Events Made Easy</strong> in the <a href='%s'>Settings</a>. ", 'events-made-easy' ), 'admin.php?page=eme-options' );
		$notice  = "<div class='error'><p>$message</p></div>";
		echo $notice;
	}

	// only show the notices to admin users
	$allowed_roles = [ 'administrator' ];
	if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
		$single                   = true;
		$eme_hello_notice_ignore  = get_user_meta( $user_id, 'eme_hello_notice_ignore', $single );
		$eme_donate_notice_ignore = get_user_meta( $user_id, 'eme_donate_notice_ignore', $single );
		if ($eme_donate_notice_ignore ) {
			// if the donate notice doesn't contain the current version, show the notice
			if ( ! preg_match( '/^'.EME_VERSION.'/', $eme_donate_notice_ignore ) ) {
				$eme_donate_notice_ignore = '';
			} else {
				$eme_donate_notice_ignore = preg_replace( '/^'.EME_VERSION.'/', '', $eme_donate_notice_ignore );
				// let's show the donate notice again after 3 months
				if ( intval( $eme_date_obj->format( 'Ymd' ) ) - intval( $eme_donate_notice_ignore ) > 90 ) {
					$eme_donate_notice_ignore = '';
				}
			}
		}
		if ( empty($eme_hello_notice_ignore) && !empty($plugin_page) && preg_match( '/^eme-/', $plugin_page ) ) { ?>
		<div class="notice-updated" style="padding: 10px 10px 10px 10px; border: 1px solid #ddd; background-color:#FFFFE0;"><?php echo sprintf( __( "<p>Hey, <strong>%s</strong>, welcome to <strong>Events Made Easy</strong>! We hope you like it around here.</p><p>Now it's time to insert events lists through <a href='%s' title='Widgets page'>widgets</a>, <a href='%s' title='Template tags documentation'>template tags</a> or <a href='%s' title='Shortcodes documentation'>shortcodes</a>.</p><p>By the way, have you taken a look at the <a href='%s' title='Change settings'>Settings page</a>? That's where you customize the way events and locations are displayed.</p><p>What? Tired of seeing this advice? I hear you, <a href=\"%6\$s\" title=\"Don't show this advice again\">click here</a> and you won't see this again!</p>", 'events-made-easy' ), $current_user->display_name, admin_url( 'widgets.php' ), '//www.e-dynamics.be/wordpress/#template-tags', '//www.e-dynamics.be/wordpress/#shortcodes', admin_url( 'admin.php?page=eme-options' ), add_query_arg( [ 'eme_notice_ignore' => 'hello' ], remove_query_arg( 'eme_notice_ignore' ) ) ); ?></div>
			<?php
		}

		if ( empty($eme_donate_notice_ignore) && !empty($plugin_page) && preg_match( '/^eme-/', $plugin_page ) ) {
			?>
<div class="notice-updated" style="padding: 10px 10px 10px 10px; border: 1px solid #ddd; background-color:#FFFFE0;">
	<div>
	<h3><?php esc_html_e( 'Events Made Easy has been installed or upgraded', 'events-made-easy' ); ?></h3>
	<h3><?php esc_html_e( 'Please donate to the development of Events Made Easy', 'events-made-easy' ); ?></h3>
			<?php
			_e( 'If you find <strong>Events Made Easy</strong> useful to you, please consider making a small donation to help contribute to my time invested and to further development. Thanks for your kind support!', 'events-made-easy' );
			?>
	<br><br>
PayPal: <a href="https://www.paypal.com/donate/?business=SMGDS4GLCYWNG&no_recurring=0&currency_code=EUR"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!"></a>
	<br><br>
Github: <a href="https://github.com/sponsors/liedekef">Github sponsoring</a>
	<br><br>
			<?php
			echo sprintf( '<a href="%s" title="%s">%s</a>', add_query_arg( [ 'eme_notice_ignore' => 'donate' ], remove_query_arg( 'eme_notice_ignore' ) ), __("Dismiss",'events-made-easy'), __("Dismiss",'events-made-easy') );
			?>
	</div>
</div>
			<?php
		}
	}
}

// when editing other profiles then your own
add_action( 'edit_user_profile', 'eme_user_profile' );
add_action( 'edit_user_profile_update', 'eme_update_user_profile' );
// when editing your own profile
add_action( 'show_user_profile', 'eme_user_profile' );
add_action( 'personal_options_update', 'eme_update_user_profile' );
// hook after user profile is updated
add_action( 'profile_update', 'eme_after_profile_update', 10, 2 );

add_action( 'wp_ajax_eme_dismiss_admin_notice', 'eme_dismiss_admin_notice' );
function eme_dismiss_admin_notice() {
	$option_name        = eme_sanitize_request( $_POST['option_name'] );
	$dismissible_length = eme_sanitize_request( $_POST['dismissible_length'] );

	if ( 'forever' != $dismissible_length ) {
		$dismissible_length = strtotime( absint( $dismissible_length ) . ' days' );
	}

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( current_user_can( get_option( 'eme_cap_list_events' ) ) ) {
		update_option( $option_name, $dismissible_length );
	}
	wp_die();
}

add_action( 'wp_ajax_eme_del_upload', 'eme_del_upload_ajax' );
function eme_del_upload_ajax() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_edit_people' ) ) || current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
		wp_die();
	}

	if ( isset( $_POST['id'] ) && isset( $_POST['name'] ) && isset( $_POST['type'] ) && isset( $_POST['random_id'] ) && isset( $_POST['field_id'] ) && isset( $_POST['extra_id'] ) ) {
			$id        = intval( $_POST['id'] );
			$type      = eme_sanitize_request( $_POST['type'] );
			$random_id = eme_sanitize_request( $_POST['random_id'] );
		// in case of empty string: don't do an intval (it would result in 0, which is not what we want)
		if ( empty( $_POST['extra_id'] ) ) {
			$extra_id = '';
		} else {
			$extra_id = intval( $_POST['extra_id'] );
		}
			$field_id = intval( $_POST['field_id'] );
			$fName    = trim( eme_sanitize_request( $_POST['name'] ) );
		$indexOFF     = strrpos( $fName, '.' );
		if ( $indexOFF ) {
			$nameFile  = substr( $fName, 0, $indexOFF );
			$extension = substr( $fName, $indexOFF + 1 );
		} else {
			$nameFile  = $fName;
			$extension = 'none';
		}
		$clean     = eme_sanitize_filenamechars( $nameFile );
		$clean_ext = eme_sanitize_filenamechars( $extension );
		$random_id = eme_sanitize_filenamechars( $random_id );
		if ( empty( $clean ) || empty( $clean_ext ) || empty( $random_id ) ) {
			return;
		}
		$fname = "$random_id-$field_id-$extra_id-$clean.$clean_ext";

		if ( in_array( $type, [ 'bookings', 'people', 'members' ] ) ) {
			eme_delete_uploaded_file( $fname, $id, $type );
		}
	}
	wp_die();
}

add_action( 'send_headers', 'eme_frontend_nocache_headers' );
function eme_frontend_nocache_headers() {
	if ( get_option( 'eme_frontend_nocache' ) ) {
		eme_nocache_headers();
	}
}

function eme_enqueue_fdatepicker() {
	return eme_enqueue_datetimepicker();
}

function eme_enqueue_datetimepicker() {
	if ( get_option( 'eme_load_js_in_header' ) ) {
		$load_js_in_footer = false;
	} else {
		$load_js_in_footer = true;
	}
	$eme_plugin_dir = eme_plugin_dir();

	wp_enqueue_script( 'eme-jquery-timepicker', EME_PLUGIN_URL . 'js/jquery-timepicker/jquery.timepicker.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	wp_enqueue_style( 'eme-jquery-timepicker', EME_PLUGIN_URL . 'js/jquery-timepicker/jquery.timepicker.min.css', [], EME_VERSION );
	wp_enqueue_script( 'eme-jquery-fdatepicker', EME_PLUGIN_URL . 'js/fdatepicker/js/fdatepicker.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	wp_enqueue_style( 'eme-jquery-fdatepicker', EME_PLUGIN_URL . 'js/fdatepicker/css/fdatepicker.min.css', [], EME_VERSION );
	// fdatepicker only needs the language (for now)
	$language = eme_detect_lang();
	// for english, no translation code is needed)
	if ( $language != 'en' ) {
		$locale_file     = $eme_plugin_dir . "js/fdatepicker/js/i18n/fdatepicker.$language.js";
		$locale_file_url = EME_PLUGIN_URL . "js/fdatepicker/js/i18n/fdatepicker.$language.js";
		if ( file_exists( $locale_file ) ) {
			wp_enqueue_script( 'eme-jquery-fdatepick-locale', $locale_file_url, [ 'eme-jquery-fdatepicker' ], EME_VERSION, $load_js_in_footer );
		}
	}
}

function eme_add_my_quicktags() {
	global $plugin_page;
	if ( preg_match( '/^eme-/', $plugin_page ) && wp_script_is( 'quicktags' ) ) {
		?>
<script type="text/javascript">
if (typeof QTags != 'undefined') {
	QTags.addButton( 'br', 'br', '<br>' );
	QTags.addButton( 'p', 'p', '<p>', '</p>' );
}
</script>
		<?php
	}
}
// the eme_add_my_quicktags action will be added when needed, see function eme_get_editor_settings
// add_action('admin_print_footer_scripts', 'eme_add_my_quicktags');

// action executed after plugin update
// currently not needed, so we disable the action hook
function eme_update_completed( $upgrader_object, $options ) {
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
		foreach( $options['plugins'] as $plugin ) {
			// Check to ensure it's my plugin
			if( $plugin == plugin_basename( __FILE__ ) ) {
				$current_userid = get_current_user_id();
				delete_user_meta( $current_userid, 'eme_donate_notice_ignore' );
			}
		}
	}
}
//add_action( 'upgrader_process_complete', 'eme_update_completed', 10, 2 );


add_action('wp_dashboard_setup', 'eme_custom_dashboard_widget');
function eme_custom_dashboard_widget() {
	wp_add_dashboard_widget('eme_custom_events_widget', __('Events Made Easy','events-made-easy'), 'eme_custom_dashboard_next_events');
}

function eme_custom_dashboard_next_events() {
	$format = "<li>#_STARTDATE #_STARTTIME <a href='#_EDITEVENTURL'>#_EVENTNAME</a> </li>";
	#$format = '';
	$format_header = '<h3>'.__('List of next 10 events','events-made-easy').'</h3><ul>';
	$format_footer = '</ul>';
	echo eme_get_events_list(limit: 10, format: $format, format_header: $format_header, format_footer: $format_footer);
}
?>
