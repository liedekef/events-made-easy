<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// the early init runs before theme functions.php is loaded, so we only call things that don't call custom filters
function eme_actions_early_init() {
	$eme_is_admin_request = eme_is_admin_request();
	if ( isset( $_GET['eme_captcha'] ) && $_GET['eme_captcha'] == 'generate' && isset( $_GET['f'] ) ) {
		$captcha_id = eme_sanitize_filenamechars( $_GET['f'] );
		if ( ! eme_is_empty_string( $captcha_id ) ) {
			eme_captcha_generate( $captcha_id );
		}
		exit;
	}
	if ( isset( $_GET['eme_tracker_id'] ) ) {
		$tracker_id = eme_sanitize_filenamechars( $_GET['eme_tracker_id'] );
		if ( ! eme_is_empty_string( $tracker_id ) ) {
			eme_mail_track( $tracker_id );
		}
		exit;
	}

	# payment notifications don't apply filters, so we can leave these in eme_actions_early_init
	if ( isset( $_GET['eme_eventAction'] ) ) {
		// not yet implemented for new paypal ...
		#if ($_GET['eme_eventAction']=="paypal_notification") {
		#   eme_notification_paypal();
		#   exit();
		#}
		if ( $_GET['eme_eventAction'] == 'legacypaypal_notification' ) {
			eme_notification_legacypaypal();
			exit();
		}
		if ( $_GET['eme_eventAction'] == '2co_notification' ) {
			eme_notification_2co();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'webmoney_notification' ) {
			eme_notification_webmoney();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'mollie_notification' ) {
			eme_notification_mollie();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'worldpay_notification' ) {
			eme_notification_worldpay();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'instamojo_notification' ) {
			eme_notification_instamojo();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'mercadopago_notification' ) {
			eme_notification_mercadopago();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'sumup_notification' ) {
			eme_notification_sumup();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'stripe_notification' ) {
			eme_notification_stripe();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'fondy_notification' ) {
			eme_notification_fondy();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'payconiq_notification' ) {
			eme_notification_payconiq();
			exit();
		}
		if ( $_GET['eme_eventAction'] == 'opayo_notification' ) {
			eme_notification_opayo();
			// opayo doesn't use a notification url, but sends the status along as part of the return url, so we just check
			// the status and set paid or not, but then we continue regular flow of events
		}
	}
	// notification for fdgg happens via POST
	if ( isset( $_POST['eme_eventAction'] ) && $_POST['eme_eventAction'] == 'fdgg_notification' ) {
		eme_notification_fdgg();
		exit();
	}

	if ( isset( $_POST['eme_ajax_action'] ) && $_POST['eme_ajax_action'] == 'task_autocomplete_people' && isset( $_POST['task_lastname'] ) ) {
		$no_wp_die = 1;
		if ( is_user_logged_in() && isset( $_POST['eme_event_ids'] ) ) {
			if ( ! isset( $_POST['eme_frontend_nonce'] ) ||
			( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
			}
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
			// this is the case for new registrations in the backend
			$event_id = intval( $_POST['event_id'] );
			$event    = eme_get_event( $event_id );
			if ( empty( $event ) || ! isset( $_POST['eme_admin_nonce'] ) ||
			( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), "eme_admin" ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
			}
			eme_people_autocomplete_ajax( $no_wp_die, $event['registration_wp_users_only'] );
		} elseif ( isset( $_POST['booking_id'] ) && $eme_is_admin_request ) {
			// this is the case for updating a registration in the backend
			$booking_id = intval( $_POST['booking_id'] );
			$event      = eme_get_event_by_booking_id( $booking_id );
			if ( empty( $event ) || ! isset( $_POST['eme_admin_nonce'] ) ||
			( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), "eme_admin" ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
			}
			eme_people_autocomplete_ajax( $no_wp_die, $event['registration_wp_users_only'] );
		} elseif ( isset( $_POST['membership_id'] ) && is_user_logged_in() ) {
			if ( ( ! isset( $_POST['eme_admin_nonce'] ) && ! isset( $_POST['eme_frontend_nonce'] ) ) ||
			( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), 'eme_admin' ) ) ||
			( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
			}
			if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
				$membership = eme_get_membership( intval( $_POST['membership_id'] ) );
				eme_people_autocomplete_ajax( $no_wp_die, $membership['properties']['registration_wp_users_only'] );
			}
		} elseif ( is_user_logged_in() && isset( $_POST['eme_event_ids'] ) ) {
			if ( ! isset( $_POST['eme_frontend_nonce'] ) ||
			( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
				header( 'Content-type: application/json; charset=utf-8' );
				echo wp_json_encode( __( 'Access denied!', 'events-made-easy' ) );
			}
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

	if ( isset( $_POST['eme_override_eventAction'] ) ) {
		// all of these have an extra frontend nonce set (even if executed in the backend too)
		check_ajax_referer( 'eme_frontend', 'eme_frontend_nonce' );
		// the price stuff can be dependant on user functions (for discounts), so we put them also in eme_actions_init and not eme_actions_preinit
		switch ( $_POST['eme_override_eventAction'] ) {
			case 'calc_memberprice':
				eme_calc_memberprice_ajax();
				break;
			case 'dynmemberdata':
				eme_dyndata_member_ajax();
				break;
			case 'dynfamilymemberdata':
				eme_dyndata_familymember_ajax();
				break;
			case 'calc_bookingprice':
				eme_calc_bookingprice_ajax();
				break;
			case 'dynbookingdata':
				eme_dyndata_rsvp_ajax();
				break;
		}
		exit();
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

	// payment charges can apply custom filters, so we leave these in eme_actions_init
	if ( isset( $_POST['eme_eventAction'] ) ) {
		if ( $_POST['eme_eventAction'] == 'payconiq_charge' ) {
			eme_charge_payconiq();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'mollie_charge' ) {
			eme_charge_mollie();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'instamojo_charge' ) {
			eme_charge_instamojo();
			exit();
		}
		// sumup form is shown directly, no charge function
		//if ($_POST['eme_eventAction']=="sumup_charge") {
		//     eme_charge_sumup();
		//     exit();
		//  }
		if ( $_POST['eme_eventAction'] == 'stripe_charge' ) {
			eme_charge_stripe();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'paypal_charge' ) {
			eme_charge_paypal();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'mercadopago_charge' ) {
			eme_charge_mercadopago();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'fondy_charge' ) {
			eme_charge_fondy();
			exit();
		}
		if ( $_POST['eme_eventAction'] == 'braintree_charge' ) {
			eme_charge_braintree();
			// braintree uses a local charge function, so we charge the card, set paid or not and then continue regular flow of events
		}
	}
}

add_action( 'init', 'eme_actions_init', 1 );
// setup_theme fires before the theme is loaded, thus avoiding issues with themes adding empty lines at the top and thus e.g. rendering captcha invalid
// But then the custom filters in the theme functions.php are not yet loaded, causing issues with a number of hooks here
add_action( 'setup_theme', 'eme_actions_early_init', 1 );

function eme_actions_admin_init() {
	global $current_user, $eme_timezone, $plugin_page;
	$eme_date_obj = new ExpressiveDate( 'now', $eme_timezone );
	eme_options_register();

	$user_id = $current_user->ID;
	if ( isset( $_GET['eme_notice_ignore'] ) && ( $_GET['eme_notice_ignore'] == 'hello' ) ) {
		add_user_meta( $user_id, 'eme_hello_notice_ignore', $eme_date_obj->format( 'Ymd' ), true );
	}
	if ( isset( $_GET['eme_notice_ignore'] ) && ( $_GET['eme_notice_ignore'] == 'donate' ) ) {
		add_user_meta( $user_id, 'eme_donate_notice_ignore', $eme_date_obj->format( 'Ymd' ), true );
	}

	// do some actions when the settings have been updated
	if ( $plugin_page == 'eme-options' && isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
		eme_options_postsave_actions();
	}

	// add the gdpr text to the example guide
	eme_gdpr_add_suggested_privacy_content();
}
add_action( 'admin_init', 'eme_actions_admin_init' );

// for loading text domains, now happens automatically so normally the action is no longer needed
// but if you don't call this, then the site language is taken and not the user language, which gets confusing in the admin backend
//add_action( 'plugins_loaded', 'eme_load_textdomain' );

// GDPR export and erase filters
add_filter( 'wp_privacy_personal_data_exporters', 'eme_gdpr_register_exporters' );
add_filter( 'wp_privacy_personal_data_erasers', 'eme_gdpr_register_erasers' );

function eme_add_events_locations_link_search( $results, $query ) {
	global $eme_timezone;
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
			'title'     => trim( eme_esc_html( strip_tags( $event['event_name'] ) . ' (' . eme_localized_datetime( $event['event_start'], $eme_timezone ) . ')' ) ),
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
	global $eme_plugin_url;
	wp_register_script( 'eme-select2', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/js/select2.min.js', 'jquery', EME_VERSION );
	// for english, no translation code is needed)
	$language = eme_detect_lang();
	if ( $language != 'en' ) {
		$eme_plugin_dir  = eme_plugin_dir();
		$locale_file     = $eme_plugin_dir . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		$locale_file_url = $eme_plugin_url . "js/jquery-select2/select2-4.1.0-rc.0/dist/js/i18n/$language.js";
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-select2-locale', $locale_file_url, [ 'eme-select2' ], EME_VERSION );
		}
	}
	#   wp_register_script( 'eme-jquery-datatables', $eme_plugin_url."js/jquery-datatables-1.10.20/datatables.min.js",array( 'jquery' ),EME_VERSION);
	wp_register_script( 'eme-print', $eme_plugin_url . 'js/jquery.printelement.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-jquery-validate', $eme_plugin_url . 'js/jquery-validate-1.19.3/jquery.validate.min.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-jquery-jtable', $eme_plugin_url . 'js/jtable-2.5.0/jquery.jtable.js', [ 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-dialog' ], EME_VERSION );
	wp_register_script( 'eme-jtable-storage', $eme_plugin_url . 'js/jtable-2.5.0/extensions/jquery.jtable.localstorage.js', [ 'eme-jquery-jtable' ], EME_VERSION );
	wp_register_script( 'eme-jtable-search', $eme_plugin_url . 'js/jtable-2.5.0/extensions/jquery.jtable.toolbarsearch.js', [ 'eme-jquery-jtable', 'eme-jtable-storage' ], EME_VERSION );
	if ( wp_script_is( 'eme-select2-locale', 'registered' ) ) {
		wp_register_script( 'eme-basic', $eme_plugin_url . 'js/eme.js', [ 'jquery', 'eme-select2', 'eme-select2-locale' ], EME_VERSION );
	} else {
		wp_register_script( 'eme-basic', $eme_plugin_url . 'js/eme.js', [ 'jquery', 'eme-select2' ], EME_VERSION );
	}
	wp_register_script( 'eme-admin', $eme_plugin_url . 'js/eme_admin.js', [ 'jquery', 'eme-jquery-jtable', 'eme-jtable-storage', 'jquery-ui-accordion', 'jquery-ui-autocomplete', 'jquery-ui-tabs', 'jquery-ui-sortable', 'eme-jquery-validate', 'eme-print' ], EME_VERSION );

	wp_register_style( 'eme-leaflet-css', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.css', EME_VERSION );
	wp_register_script( 'eme-leaflet-maps', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.js', [ 'jquery' ], EME_VERSION, true );
	wp_register_script( 'eme-admin-maps', $eme_plugin_url . 'js/eme_admin_maps.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_script( 'eme-autocomplete-form', $eme_plugin_url . 'js/eme_autocomplete_form.js', [ 'jquery-ui-autocomplete' ], EME_VERSION );
	wp_register_script( 'eme-options', $eme_plugin_url . 'js/eme_admin_options.js', [ 'jquery' ], EME_VERSION );
	wp_register_script( 'eme-formfields', $eme_plugin_url . 'js/eme_admin_fields.js', [ 'jquery' ], EME_VERSION );

	$locale_code     = determine_locale();
	$locale_code     = preg_replace( '/_/', '-', $locale_code );
	$locale_file     = $eme_plugin_url . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
	$locale_file_url = $eme_plugin_url . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
	// for english, no translation code is needed)
	if ( $locale_code != 'en-US' ) {
		if ( ! file_exists( $locale_file ) ) {
			$locale_code     = substr( $locale_code, 0, 2 );
			$locale_file     = $eme_plugin_url . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
			$locale_file_url = $eme_plugin_url . "js/jtable-2.5.0/localization/jquery.jtable.$locale_code.js";
		}
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-jtable-locale', $locale_file_url, '', EME_VERSION );
		}
	}

	wp_register_script( 'eme-rsvp', $eme_plugin_url . 'js/eme_admin_rsvp.js', [ 'eme-autocomplete-form' ], EME_VERSION );
	wp_register_script( 'eme-sendmails', $eme_plugin_url . 'js/eme_admin_sendmails.js', '', EME_VERSION );
	wp_register_script( 'eme-discounts', $eme_plugin_url . 'js/eme_admin_discounts.js', [ 'eme-jtable-search' ], EME_VERSION );
	wp_register_script( 'eme-countries', $eme_plugin_url . 'js/eme_admin_countries.js', '', EME_VERSION );
	wp_register_script( 'eme-people', $eme_plugin_url . 'js/eme_admin_people.js', '', EME_VERSION );
	wp_register_script( 'eme-templates', $eme_plugin_url . 'js/eme_admin_templates.js', '', EME_VERSION );
	wp_register_script( 'eme-tasksignups', $eme_plugin_url . 'js/eme_admin_tasksignups.js', '', EME_VERSION );
	wp_register_script( 'eme-members', $eme_plugin_url . 'js/eme_admin_members.js', '', EME_VERSION );
	wp_register_script( 'eme-events', $eme_plugin_url . 'js/eme_admin_events.js', '', EME_VERSION );
	wp_register_script( 'eme-locations', $eme_plugin_url . 'js/eme_admin_locations.js', '', EME_VERSION );
	wp_register_script( 'eme-attendances', $eme_plugin_url . 'js/eme_admin_attendances.js', '', EME_VERSION );
	wp_register_style( 'eme_stylesheet', $eme_plugin_url . 'css/eme.css' );
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		$eme_css_url = get_stylesheet_directory_uri() . '/eme.css';
		wp_register_style( 'eme_stylesheet_extra', get_stylesheet_directory_uri() . '/eme.css', 'eme_stylesheet' );
	}
	wp_register_style( 'eme-jquery-ui-autocomplete', $eme_plugin_url . 'css/jquery.autocomplete.css' );
	wp_register_style( 'eme-jquery-ui-css', $eme_plugin_url . 'css/jquery-ui-theme-smoothness-1.11.3/jquery-ui.min.css' );
	wp_register_style( 'eme-jquery-jtable-css', $eme_plugin_url . 'js/jtable-2.5.0/themes/jqueryui/jtable_jqueryui.css' );
	wp_register_style( 'eme-jquery-select2-css', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/css/select2.min.css' );
	wp_register_style( 'eme-jtables-css', $eme_plugin_url . 'css/jquery.jtables.css' );
	#   wp_register_style('eme-jquery-datatables', $eme_plugin_url."js/jquery-datatables-1.10.20/datatables.min.css");
	eme_admin_enqueue_js();
}
add_action( 'admin_enqueue_scripts', 'eme_admin_register_scripts' );

function eme_register_scripts_orig() {
	global $eme_wp_date_format, $eme_wp_time_format, $eme_plugin_url;
	// the frontend also needs the datepicker (the month filter) and also for custom fields

	if ( get_option( 'eme_load_js_in_header' ) ) {
		$load_js_in_footer = false;
	} else {
		$load_js_in_footer = true;
	}

	eme_enqueue_datetimepicker();
	wp_register_script( 'eme-select2', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	// we enqueue select2 directly and not as a dependance in eme-basic, so people can dequeue it if wanted
	wp_enqueue_script( 'eme-select2' );
	// for english, no translation code is needed)
	$language = eme_detect_lang();
	if ( $language != 'en' ) {
			$eme_plugin_dir  = eme_plugin_dir();
			$locale_file     = $eme_plugin_dir . "js/jquery-select2/select2-4.1.0-rc.0/dist//js/i18n/$language.js";
			$locale_file_url = $eme_plugin_url . "js/jquery-select2/select2-4.1.0-rc.0/dist//js/i18n/$language.js";
		if ( file_exists( $locale_file ) ) {
				wp_enqueue_script( 'eme-select2-locale', $locale_file_url, [ 'eme-select2' ], EME_VERSION, $load_js_in_footer );
		}
	}
	wp_register_script( 'eme-basic', $eme_plugin_url . 'js/eme.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	$translation_array = [
		'translate_plugin_url'         => $eme_plugin_url,
		'translate_ajax_url'           => admin_url( 'admin-ajax.php' ),
		'translate_selectstate'        => __( 'State', 'events-made-easy' ),
		'translate_selectcountry'      => __( 'Country', 'events-made-easy' ),
		'translate_frontendnonce'      => wp_create_nonce( 'eme_frontend' ),
		'translate_error'              => __( 'An error has occurred', 'events-made-easy' ),
		'translate_clear'              => __( 'Clear', 'events-made-easy' ),
		'translate_mailingpreferences' => __( 'Mailing preferences', 'events-made-easy' ),
		'translate_yessure'            => __( "Yes, I'm sure", 'events-made-easy' ),
		'translate_iwantmails'         => __( 'I want to receive mails', 'events-made-easy' ),
		'translate_firstDayOfWeek'     => get_option( 'start_of_week' ),
		'translate_flanguage'          => $language,
		'translate_fdateformat'        => $eme_wp_date_format,
		'translate_ftimeformat'        => $eme_wp_time_format,
	];
	wp_localize_script( 'eme-basic', 'emebasic', $translation_array );
	wp_enqueue_script( 'eme-basic' );

	if ( get_option( 'eme_use_client_clock' ) && ! isset( $_COOKIE['eme_client_time'] ) ) {
		// client clock should be executed asap, so load it in the header, and no defer
			$translation_array = [
				'translate_ajax_url' => admin_url( 'admin-ajax.php' ),
			];
			wp_register_script( 'eme-client_clock_submit', $eme_plugin_url . 'js/client-clock.js', [ 'jquery' ], EME_VERSION );
			wp_localize_script( 'eme-client_clock_submit', 'emeclock', $translation_array );
			wp_enqueue_script( 'eme-client_clock_submit' );
	}

	// the frontend also needs the autocomplete (rsvp form)
	$search_tables = get_option( 'eme_autocomplete_sources' );
	if ( $search_tables != 'none' && is_user_logged_in() ) {
		wp_register_script( 'eme-autocomplete-form', $eme_plugin_url . 'js/eme_autocomplete_form.js', [ 'jquery-ui-autocomplete' ], EME_VERSION, $load_js_in_footer );
	}

	if ( get_option( 'eme_massmail_popup' ) ) {
			wp_enqueue_script( 'jquery-ui-dialog' );
	}
	wp_enqueue_style( 'eme-jquery-ui-css', $eme_plugin_url . 'css/jquery-ui-theme-smoothness-1.11.3/jquery-ui.min.css', [], EME_VERSION );
	wp_enqueue_style( 'eme-jquery-ui-autocomplete', $eme_plugin_url . 'css/jquery.autocomplete.css', [], EME_VERSION );
	wp_enqueue_style( 'eme-jquery-select2-css', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/css/select2.min.css', [], EME_VERSION );

	wp_enqueue_style( 'eme_textsec', $eme_plugin_url . 'css/text-security/text-security-disc.css', [], EME_VERSION );
	wp_enqueue_style( 'eme_stylesheet', $eme_plugin_url . 'css/eme.css', [], EME_VERSION );
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		wp_enqueue_style( 'eme_stylesheet_extra', get_stylesheet_directory_uri() . '/eme.css', 'eme_stylesheet', [], EME_VERSION );
	}

	if ( get_option( 'eme_map_is_active' ) ) {
		wp_enqueue_style( 'eme-leaflet-css', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.css', [], EME_VERSION );
	}
	wp_register_script( 'eme-leaflet-maps', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.js', [ 'jquery' ], EME_VERSION, true );
	wp_register_script( 'eme-leaflet-gestures', $eme_plugin_url . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_script( 'eme-leaflet-markercluster', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/leaflet.markercluster.js', [ 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_style( 'eme-markercluster-css1', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/MarkerCluster.css', EME_VERSION, false );
	wp_register_style( 'eme-markercluster-css2', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/MarkerCluster.Default.css', EME_VERSION, false );
	wp_register_style( 'eme-gestures-css', $eme_plugin_url . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.css', EME_VERSION, false );
	wp_register_script( 'eme-location-map', $eme_plugin_url . 'js/eme_location_map.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );

	if ( get_option( 'eme_recaptcha_for_forms' ) ) {
			// using explicit rendering of the captcha would allow to capture the widget id and reset it if needed, but we won't use that ...
			//wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=eme_CaptchaCallback&render=explicit', array('eme-basic'), '',true);
			wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js', [ 'eme-basic' ], '', true );
	}
	if ( get_option( 'eme_hcaptcha_for_forms' ) ) {
			wp_register_script( 'eme-hcaptcha', 'https://js.hcaptcha.com/1/api.js', [ 'eme-basic' ], '', true );
	}
}

function eme_register_scripts() {
	global $eme_wp_date_format, $eme_wp_time_format, $eme_plugin_url;
	// the frontend also needs the datepicker (the month filter) and also for custom fields

	if ( get_option( 'eme_load_js_in_header' ) ) {
		$load_js_in_footer = false;
	} else {
		$load_js_in_footer = true;
	}
	$language = eme_detect_lang();

	wp_register_script( 'eme-select2', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	if ( $language != 'en' ) {
		$eme_plugin_dir  = eme_plugin_dir();
		$locale_file     = $eme_plugin_dir . "js/jquery-select2/select2-4.1.0-rc.0/dist//js/i18n/$language.js";
		$locale_file_url = $eme_plugin_url . "js/jquery-select2/select2-4.1.0-rc.0/dist//js/i18n/$language.js";
		if ( file_exists( $locale_file ) ) {
			wp_register_script( 'eme-select2-locale', $locale_file_url, [ 'eme-select2' ], EME_VERSION, $load_js_in_footer );
		}
	}
	wp_register_script( 'eme-basic', $eme_plugin_url . 'js/eme.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	$translation_array = [
		'translate_plugin_url'         => $eme_plugin_url,
		'translate_ajax_url'           => admin_url( 'admin-ajax.php' ),
		'translate_selectstate'        => __( 'State', 'events-made-easy' ),
		'translate_selectcountry'      => __( 'Country', 'events-made-easy' ),
		'translate_frontendnonce'      => wp_create_nonce( 'eme_frontend' ),
		'translate_error'              => __( 'An error has occurred', 'events-made-easy' ),
		'translate_clear'              => __( 'Clear', 'events-made-easy' ),
		'translate_mailingpreferences' => __( 'Mailing preferences', 'events-made-easy' ),
		'translate_yessure'            => __( "Yes, I'm sure", 'events-made-easy' ),
		'translate_iwantmails'         => __( 'I want to receive mails', 'events-made-easy' ),
		'translate_firstDayOfWeek'     => get_option( 'start_of_week' ),
		'translate_flanguage'          => $language,
		'translate_fdateformat'        => $eme_wp_date_format,
		'translate_ftimeformat'        => $eme_wp_time_format,
	];
	wp_localize_script( 'eme-basic', 'emebasic', $translation_array );

	if ( get_option( 'eme_use_client_clock' ) && ! isset( $_COOKIE['eme_client_time'] ) ) {
		// client clock should be executed asap, so load it in the header, and no defer
		$translation_array = [
			'translate_ajax_url' => admin_url( 'admin-ajax.php' ),
		];
		wp_register_script( 'eme-client_clock_submit', $eme_plugin_url . 'js/client-clock.js', [ 'jquery' ], EME_VERSION );
		wp_localize_script( 'eme-client_clock_submit', 'emeclock', $translation_array );
		wp_enqueue_script( 'eme-client_clock_submit' );
	}

	// the frontend also needs the autocomplete (rsvp form)
	$search_tables = get_option( 'eme_autocomplete_sources' );
	if ( $search_tables != 'none' && is_user_logged_in() ) {
		wp_register_script( 'eme-autocomplete-form', $eme_plugin_url . 'js/eme_autocomplete_form.js', [ 'jquery-ui-autocomplete' ], EME_VERSION, $load_js_in_footer );
	}

	wp_register_script( 'eme-leaflet-maps', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.js', [ 'jquery' ], EME_VERSION, true );
	wp_register_script( 'eme-leaflet-gestures', $eme_plugin_url . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_script( 'eme-leaflet-markercluster', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/leaflet.markercluster.js', [ 'eme-leaflet-maps' ], EME_VERSION, true );
	wp_register_style( 'eme-leaflet-css', $eme_plugin_url . 'js/leaflet-1.8.0/leaflet.css', EME_VERSION, false );
	wp_register_style( 'eme-markercluster-css1', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/MarkerCluster.css', EME_VERSION, false );
	wp_register_style( 'eme-markercluster-css2', $eme_plugin_url . 'js/leaflet-markercluster-1.4.1/MarkerCluster.Default.css', EME_VERSION, false );
	wp_register_style( 'eme-gestures-css', $eme_plugin_url . 'js/leaflet-gesturehandling-1.2.1/leaflet-gesture-handling.min.css', EME_VERSION, false );
	wp_register_script( 'eme-location-map', $eme_plugin_url . 'js/eme_location_map.js', [ 'jquery', 'eme-leaflet-maps' ], EME_VERSION, true );

	if ( get_option( 'eme_recaptcha_for_forms' ) ) {
		// using explicit rendering of the captcha would allow to capture the widget id and reset it if needed, but we won't use that ...
		//wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js?onload=eme_CaptchaCallback&render=explicit', array('eme-basic'), '',true);
		wp_register_script( 'eme-recaptcha', 'https://www.google.com/recaptcha/api.js', [ 'eme-basic' ], '', true );
	}
	if ( get_option( 'eme_hcaptcha_for_forms' ) ) {
		wp_register_script( 'eme-hcaptcha', 'https://js.hcaptcha.com/1/api.js', [ 'eme-basic' ], '', true );
	}
}
add_action( 'wp_enqueue_scripts', 'eme_register_scripts' );

function eme_enqueue_frontend_orig() {
}

function eme_enqueue_frontend() {
	global $eme_plugin_url;
	if ( ! wp_script_is( 'eme-basic', 'enqueued' ) ) {
		eme_enqueue_datetimepicker();
		wp_enqueue_script( 'eme-select2' );
		if ( wp_script_is( 'eme-select2-locale', 'registered' ) ) {
			wp_enqueue_script( 'eme-select2-locale' );
		}
		// for english, no translation code is needed)
		wp_enqueue_script( 'eme-basic' );
		if ( get_option( 'eme_massmail_popup' ) ) {
			wp_enqueue_script( 'jquery-ui-dialog' );
		}
		wp_enqueue_style( 'eme-jquery-ui-css', $eme_plugin_url . 'css/jquery-ui-theme-smoothness-1.11.3/jquery-ui.min.css', [], EME_VERSION );
		wp_enqueue_style( 'eme-jquery-ui-autocomplete', $eme_plugin_url . 'css/jquery.autocomplete.css', [], EME_VERSION );
		wp_enqueue_style( 'eme-jquery-select2-css', $eme_plugin_url . 'js/jquery-select2/select2-4.1.0-rc.0/dist/css/select2.min.css', [], EME_VERSION );

		wp_enqueue_style( 'eme_textsec', $eme_plugin_url . 'css/text-security/text-security-disc.css', [], EME_VERSION );
		wp_enqueue_style( 'eme_stylesheet', $eme_plugin_url . 'css/eme.css', [], EME_VERSION );
		$eme_css_name = get_stylesheet_directory() . '/eme.css';
		if ( file_exists( $eme_css_name ) ) {
			wp_enqueue_style( 'eme_stylesheet_extra', get_stylesheet_directory_uri() . '/eme.css', 'eme_stylesheet', [], EME_VERSION );
		}
	}
}

function eme_add_defer_attribute( $tag, $handle ) {
	if ( 'eme-basic' === $handle ) {
			return str_replace( ' src', ' defer="defer" src', $tag );
	}

	if ( 'eme-recaptcha' === $handle || 'eme-hcaptcha' === $handle ) {
		return str_replace( ' src', ' defer="defer" async src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'eme_add_defer_attribute', 10, 2 );

add_action( 'template_redirect', 'eme_template_redir' );
add_action( 'admin_notices', 'eme_admin_notices' );

function eme_admin_notices() {
	global $pagenow, $plugin_page, $eme_timezone;
	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;
	$eme_date_obj = new ExpressiveDate( 'now', $eme_timezone );

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
		// let's show the donate notice again after 3 months
		if ( $eme_donate_notice_ignore && ( intval( $eme_date_obj->format( 'Ymd' ) ) - intval( $eme_donate_notice_ignore ) > 90 ) ) {
			delete_user_meta( $user_id, 'eme_donate_notice_ignore' );
			$eme_donate_notice_ignore = 0;
		}
		if ( ! $eme_hello_notice_ignore && preg_match( '/^eme-/', $plugin_page ) ) { ?>
		<div class="updated notice"><?php echo sprintf( __( "<p>Hey, <strong>%s</strong>, welcome to <strong>Events Made Easy</strong>! We hope you like it around here.</p><p>Now it's time to insert events lists through <a href='%s' title='Widgets page'>widgets</a>, <a href='%s' title='Template tags documentation'>template tags</a> or <a href='%s' title='Shortcodes documentation'>shortcodes</a>.</p><p>By the way, have you taken a look at the <a href='%s' title='Change settings'>Settings page</a>? That's where you customize the way events and locations are displayed.</p><p>What? Tired of seeing this advice? I hear you, <a href=\"%6\$s\" title=\"Don't show this advice again\">click here</a> and you won't see this again!</p>", 'events-made-easy' ), $current_user->display_name, admin_url( 'widgets.php' ), '//www.e-dynamics.be/wordpress/#template-tags', '//www.e-dynamics.be/wordpress/#shortcodes', admin_url( 'admin.php?page=eme-options' ), add_query_arg( [ 'eme_notice_ignore' => 'hello' ], remove_query_arg( 'eme_notice_ignore' ) ) ); ?></div>
			<?php
		}

		if ( ! $eme_donate_notice_ignore && preg_match( '/^eme-/', $plugin_page ) ) {
			?>
<div class="updated notice" style="padding: 10px 10px 10px 10px; border: 1px solid #ddd; background-color:#FFFFE0;">
	<div>
	<h3><?php esc_html_e( 'Donate to the development of Events Made Easy', 'events-made-easy' ); ?></h3>
			<?php
			_e( 'If you find <strong>Events Made Easy</strong> useful to you, please consider making a small donation to help contribute to my time invested and to further development. Thanks for your kind support!', 'events-made-easy' );
			?>
	<br><br>
PayPal: <a href="https://www.paypal.com/donate/?business=SMGDS4GLCYWNG&no_recurring=0&currency_code=EUR"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!"></a>
	<br><br>
Liberapay: <a href="https://liberapay.com/frankyvl/donate"><img alt="Donate using Liberapay" src="https://liberapay.com/assets/widgets/donate.svg"></a>
	<br><br>
			<?php
			echo sprintf( __( '<a href="%s" title="I already donated">I already donated.</a>', 'events-made-easy' ), add_query_arg( [ 'eme_notice_ignore' => 'donate' ], remove_query_arg( 'eme_notice_ignore' ) ) );
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
	global $eme_plugin_url;
	if ( get_option( 'eme_load_js_in_header' ) ) {
		$load_js_in_footer = false;
	} else {
		$load_js_in_footer = true;
	}
	$eme_plugin_dir = eme_plugin_dir();

	wp_enqueue_script( 'eme-jquery-timepicker', $eme_plugin_url . 'js/jquery-timepicker/jquery.timepicker.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	wp_enqueue_style( 'eme-jquery-timepicker', $eme_plugin_url . 'js/jquery-timepicker/jquery.timepicker.min.css', [], EME_VERSION );
	wp_enqueue_script( 'eme-jquery-fdatepicker', $eme_plugin_url . 'js/fdatepicker/js/fdatepicker.min.js', [ 'jquery' ], EME_VERSION, $load_js_in_footer );
	wp_enqueue_style( 'eme-jquery-fdatepicker', $eme_plugin_url . 'js/fdatepicker/css/fdatepicker.min.css', [], EME_VERSION );
	// fdatepicker only needs the language (for now)
	$language = eme_detect_lang();
	// for english, no translation code is needed)
	if ( $language != 'en' ) {
		$locale_file     = $eme_plugin_dir . "js/fdatepicker/js/i18n/fdatepicker.$language.js";
		$locale_file_url = $eme_plugin_url . "js/fdatepicker/js/i18n/fdatepicker.$language.js";
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

?>
