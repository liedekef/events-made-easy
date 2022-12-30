<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
Plugin Name: Events Made Easy
Version: 2.3.29
Plugin URI: https://www.e-dynamics.be/wordpress
Update URI: https://github.com/liedekef/events-made-easy/
Description: Manage and display events and memberships. Also includes recurring events; locations; widgets; maps; RSVP; ICAL and RSS feeds; Paypal, 2Checkout and others.
Author: Franky Van Liedekerke
Author URI: https://www.e-dynamics.be/
Text Domain: events-made-easy
Domain Path: /langs
*/

/*
Copyright (c) 2010-2021, Franky Van Liedekerke.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Setting constants
define( 'EME_VERSION', '2.3.29' );
define( 'EME_DB_VERSION', 363 );
define( 'EVENTS_TBNAME', 'eme_events' );
define( 'EVENTS_CF_TBNAME', 'eme_events_cf' );
define( 'RECURRENCE_TBNAME', 'eme_recurrence' );
define( 'LOCATIONS_TBNAME', 'eme_locations' );
define( 'LOCATIONS_CF_TBNAME', 'eme_locations_cf' );
define( 'BOOKINGS_TBNAME', 'eme_bookings' );
define( 'PEOPLE_TBNAME', 'eme_people' );
define( 'GROUPS_TBNAME', 'eme_groups' );
define( 'USERGROUPS_TBNAME', 'eme_usergroups' );
define( 'CATEGORIES_TBNAME', 'eme_categories' );
define( 'HOLIDAYS_TBNAME', 'eme_holidays' );
define( 'TEMPLATES_TBNAME', 'eme_templates' );
define( 'FORMFIELDS_TBNAME', 'eme_formfields' );
define( 'FIELDTYPES_TBNAME', 'eme_fieldtypes' );
define( 'ANSWERS_TBNAME', 'eme_answers' );
define( 'PAYMENTS_TBNAME', 'eme_payments' );
define( 'DISCOUNTS_TBNAME', 'eme_discounts' );
define( 'DISCOUNTGROUPS_TBNAME', 'eme_dgroups' );
define( 'MQUEUE_TBNAME', 'eme_mqueue' );
define( 'MAILINGS_TBNAME', 'eme_mailings' );
define( 'MEMBERS_TBNAME', 'eme_members' );
define( 'MEMBERSHIPS_TBNAME', 'eme_memberships' );
define( 'MEMBERSHIPS_CF_TBNAME', 'eme_memberships_cf' );
define( 'COUNTRIES_TBNAME', 'eme_countries' );
define( 'STATES_TBNAME', 'eme_states' );
define( 'ATTENDANCES_TBNAME', 'eme_attendances' );
define( 'TASKS_TBNAME', 'eme_tasks' );
define( 'TASK_SIGNUPS_TBNAME', 'eme_task_signups' );
define( 'DEFAULT_CAP_ADD_EVENT', 'edit_posts' );
define( 'DEFAULT_CAP_AUTHOR_EVENT', 'publish_posts' );
define( 'DEFAULT_CAP_PUBLISH_EVENT', 'publish_posts' );
define( 'DEFAULT_CAP_LIST_EVENTS', 'edit_posts' );
define( 'DEFAULT_CAP_EDIT_EVENTS', 'edit_others_posts' );
define( 'DEFAULT_CAP_LIST_LOCATIONS', 'edit_others_posts' );
define( 'DEFAULT_CAP_ADD_LOCATION', 'edit_others_posts' );
define( 'DEFAULT_CAP_AUTHOR_LOCATION', 'edit_others_posts' );
define( 'DEFAULT_CAP_EDIT_LOCATIONS', 'edit_others_posts' );
define( 'DEFAULT_CAP_CATEGORIES', 'activate_plugins' );
define( 'DEFAULT_CAP_HOLIDAYS', 'activate_plugins' );
define( 'DEFAULT_CAP_TEMPLATES', 'activate_plugins' );
define( 'DEFAULT_CAP_MANAGE_TASK_SIGNUPS', 'edit_posts' );
define( 'DEFAULT_CAP_ACCESS_PEOPLE', 'edit_posts' );
define( 'DEFAULT_CAP_LIST_PEOPLE', 'edit_posts' );
define( 'DEFAULT_CAP_EDIT_PEOPLE', 'edit_others_posts' );
define( 'DEFAULT_CAP_AUTHOR_PERSON', 'edit_posts' );
define( 'DEFAULT_CAP_DISCOUNTS', 'edit_posts' );
define( 'DEFAULT_CAP_ACCESS_MEMBERS', 'edit_posts' );
define( 'DEFAULT_CAP_LIST_MEMBERS', 'edit_posts' );
define( 'DEFAULT_CAP_EDIT_MEMBERS', 'edit_others_posts' );
define( 'DEFAULT_CAP_AUTHOR_MEMBER', 'edit_posts' );
define( 'DEFAULT_CAP_LIST_REGISTRATIONS', 'edit_posts' );
define( 'DEFAULT_CAP_LIST_APPROVE', 'edit_posts' );
define( 'DEFAULT_CAP_AUTHOR_APPROVE', 'edit_posts' );
define( 'DEFAULT_CAP_APPROVE', 'edit_others_posts' );
define( 'DEFAULT_CAP_AUTHOR_REGISTRATIONS', 'edit_posts' );
define( 'DEFAULT_CAP_REGISTRATIONS', 'edit_others_posts' );
define( 'DEFAULT_CAP_ATTENDANCECHECK', 'edit_posts' );
define( 'DEFAULT_CAP_MEMBERCHECK', 'edit_posts' );
define( 'DEFAULT_CAP_FORMS', 'edit_others_posts' );
define( 'DEFAULT_CAP_CLEANUP', 'activate_plugins' );
define( 'DEFAULT_CAP_SETTINGS', 'activate_plugins' );
define( 'DEFAULT_CAP_SEND_MAILS', 'edit_posts' );
define( 'DEFAULT_CAP_SEND_OTHER_MAILS', 'edit_others_posts' );
define( 'DEFAULT_CAP_LIST_ATTENDANCES', 'edit_posts' );
define( 'DEFAULT_CAP_MANAGE_ATTENDANCES', 'edit_others_posts' );
define( 'DEFAULT_EVENT_LIST_HEADER_FORMAT', "<ul class='eme_events_list'>" );
define( 'DEFAULT_EVENT_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_CAT_EVENT_LIST_HEADER_FORMAT', "<ul class='eme_events_list'>" );
define( 'DEFAULT_CAT_EVENT_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_EVENTS_PAGE_TITLE', __( 'Events', 'events-made-easy' ) );
define( 'DEFAULT_EVENT_HTML_TITLE_FORMAT', '#_EVENTNAME' );
define( 'DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT', '<li>#_LINKEDNAME<ul><li>#_STARTDATE</li><li>#_TOWN</li></ul></li>' );
define( 'DEFAULT_NO_EVENTS_MESSAGE', __( 'No events', 'events-made-easy' ) );
define( 'DEFAULT_LOCATION_HTML_TITLE_FORMAT', '#_LOCATIONNAME' );
define( 'DEFAULT_LOCATION_LIST_HEADER_FORMAT', "<ul class='eme_locations_list'>" );
define( 'DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT', '<li>#_EVENTNAME - #_STARTDATE - #_STARTTIME</li>' );
define( 'DEFAULT_LOCATION_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_BOOKINGS_LIST_HEADER_FORMAT', "<ul class='eme_bookings_list_ul'>" );
define( 'DEFAULT_BOOKINGS_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_SHOW_PERIOD_MONTHLY_DATEFORMAT', 'F, Y' );
define( 'DEFAULT_SHOW_PERIOD_YEARLY_DATEFORMAT', 'Y' );
define( 'DEFAULT_FILTER_FORM_FORMAT', '#_FILTER_CATS #_FILTER_LOCS' );
define( 'EME_EVENT_STATUS_TRASH', 0 );
define( 'EME_EVENT_STATUS_PUBLIC', 1 );
define( 'EME_EVENT_STATUS_PRIVATE', 2 );
define( 'EME_EVENT_STATUS_UNLISTED', 3 );
define( 'EME_EVENT_STATUS_DRAFT', 5 );
define( 'EME_RSVP_STATUS_USERPENDING', 3 );
define( 'EME_RSVP_STATUS_PENDING', 2 );
define( 'EME_RSVP_STATUS_APPROVED', 1 );
define( 'EME_RSVP_STATUS_TRASH', 0 );
define( 'EME_PEOPLE_STATUS_ACTIVE', 1 );
define( 'EME_PEOPLE_STATUS_TRASH', 0 );
define( 'EME_MEMBER_STATUS_PENDING', 0 );
define( 'EME_MEMBER_STATUS_ACTIVE', 1 );
define( 'EME_MEMBER_STATUS_GRACE', 99 );
define( 'EME_MEMBER_STATUS_EXPIRED', 100 );
define( 'EME_DISCOUNT_TYPE_FIXED', 1 );
define( 'EME_DISCOUNT_TYPE_PCT', 2 );
define( 'EME_DISCOUNT_TYPE_CODE', 3 );
define( 'EME_DISCOUNT_TYPE_FIXED_PER_SEAT', 4 );
#define('EME_LANGUAGE_REGEX','[a-z]{2,3}([_\-][a-z]{2,3})?');
#define('EME_LANGUAGE_REGEX','[a-z]{2}(_[a-z]{2})?');
define( 'EME_LANGUAGE_REGEX', '[a-z]{2,3}' );
$upload_info = wp_upload_dir();
define( 'EME_UPLOAD_DIR', $upload_info['basedir'] . '/events-made-easy' );
define( 'EME_UPLOAD_URL', $upload_info['baseurl'] . '/events-made-easy' );
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

add_filter( 'plugin_row_meta', 'eme_plugin_row_meta', 10, 2 );
function eme_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, 'events-manager.php' ) !== false ) {
		$new_links = array(
			'donate Paypal'  => '<a href="https://www.paypal.com/donate/?business=SMGDS4GLCYWNG&no_recurring=0&currency_code=EUR">Donate (Paypal)</a>',
			'donate Liberapay'  => '<a href="https://liberapay.com/frankyvl/donate">Donate (Liberapay)</a>',
			'Support' => '<a href="https://github.com/liedekef/events-made-easy">Support</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'eme_add_action_links' );
function eme_add_action_links( $links ) {
	$mylinks = array( '<a href="admin.php?page=eme-options">Settings</a>' );
	return array_merge( $links, $mylinks );
}

// To enable activation through the activate function
register_activation_hook( __FILE__, 'eme_install' );
// when deactivation is needed
register_deactivation_hook( __FILE__, 'eme_uninstall' );
// when a new blog is added for network installation and the plugin is network activated
add_action( 'wpmu_new_blog', 'eme_new_blog', 10, 6 );

// filters for general events field (corresponding to those of "the_title")
add_filter( 'eme_general', 'wptexturize' );
add_filter( 'eme_general', 'convert_chars' );
add_filter( 'eme_general', 'trim' );

// TEXT content filter
add_filter( 'eme_text', 'wp_strip_all_tags' );
add_filter( 'eme_text', 'html_entity_decode' );

// set some vars
if ( function_exists( 'wp_timezone_string' ) ) {
	$eme_timezone = wp_timezone_string();
} else {
	$eme_timezone = get_option( 'timezone_string' );
	if ( ! $eme_timezone ) {
		$offset = get_option( 'gmt_offset' );
		if ( $offset > 0 ) {
			$eme_timezone = "+$offset";
		} elseif ( $offset < 0 ) {
			$eme_timezone = "$offset";
		} else {
			$eme_timezone = '+0';
		}
	}
}
$eme_wp_date_format = get_option( 'date_format' );
$eme_wp_time_format = get_option( 'time_format' );

// Adding a new rule
function eme_insertMyRewriteRules( $rules ) {
	// using pagename as param to index.php causes rewrite troubles if the page is a subpage of another
	// luckily for us we have the page id, and this works ok
	$events_page_id  = eme_get_events_page_id();
	$newrules        = array();
	$events_prefixes = explode( ',', get_option( 'eme_permalink_events_prefix', 'events' ) );
	foreach ( $events_prefixes as $events_prefix ) {
		if ( eme_is_empty_string( $events_prefix ) ) {
			continue;
		}
		$events_prefix = eme_permalink_convert( $events_prefix );
		$newrules[ '(.*/)?' . $events_prefix . '(\d{4})-(\d{2})-(\d{2})/c(.*)' ] = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]' . '&eme_event_cat=$matches[5]';
		$newrules[ '(.*/)?' . $events_prefix . '(\d{4})-(\d{2})-(\d{2})/?$' ]    = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]';
		$newrules[ '(.*/)?' . $events_prefix . '(\d{4})-(\d{2})-(\d{2})/(.+)$' ] = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]' . '&$matches[5]';
		$newrules[ '(.*/)?' . $events_prefix . '(\d+)/.*' ]                      = 'index.php?page_id=' . $events_page_id . '&event_id=$matches[2]';
		$newrules[ '(.*/)?' . $events_prefix . 'p/(.*)' ]                        = 'index.php?page_id=' . $events_page_id . '&eme_pmt_rndid=$matches[2]';
		// the user booking confirm goes to the payment page, but we use a specific permalink here
		$newrules[ '(.*/)?' . $events_prefix . 'confirm/(.*)' ] = 'index.php?page_id=' . $events_page_id . '&eme_pmt_rndid=$matches[2]&eme_rsvp_confirm=1';
		$newrules[ '(.*/)?' . $events_prefix . 'town/(.*)' ]    = 'index.php?page_id=' . $events_page_id . '&eme_city=$matches[2]';
		$newrules[ '(.*/)?' . $events_prefix . 'city/(.*)' ]    = 'index.php?page_id=' . $events_page_id . '&eme_city=$matches[2]';
		$newrules[ '(.*/)?' . $events_prefix . 'country/(.*)' ] = 'index.php?page_id=' . $events_page_id . '&eme_country=$matches[2]';
		$newrules[ '(.*/)?' . $events_prefix . 'cat/(.*)' ]     = 'index.php?page_id=' . $events_page_id . '&eme_event_cat=$matches[2]';
		$newrules[ '(.*/)?' . $events_prefix . '(.*)' ]         = 'index.php?page_id=' . $events_page_id . '&event_id=$matches[2]';
	}

	$locations_prefixes = explode( ',', get_option( 'eme_permalink_locations_prefix', 'locations' ) );
	foreach ( $locations_prefixes as $locations_prefix ) {
		if ( eme_is_empty_string( $locations_prefix ) ) {
			continue;
		}
		$locations_prefix                                      = eme_permalink_convert( $locations_prefix );
		$newrules[ '(.*/)?' . $locations_prefix . '(\d+)/.*' ] = 'index.php?page_id=' . $events_page_id . '&location_id=$matches[2]';
		$newrules[ '(.*/)?' . $locations_prefix . '(.*)' ]     = 'index.php?page_id=' . $events_page_id . '&location_id=$matches[2]';
	}

	$categories_prefixes = explode( ',', get_option( 'eme_permalink_categories_prefix', '' ) );
	foreach ( $categories_prefixes as $categories_prefix ) {
		if ( eme_is_empty_string( $categories_prefix ) ) {
			continue;
		}
		$categories_prefix                                  = eme_permalink_convert( $categories_prefix );
		$newrules[ '(.*/)?' . $categories_prefix . '(.*)' ] = 'index.php?page_id=' . $events_page_id . '&eme_event_cat=$matches[2]';
	}

	$cal_prefix = get_option( 'eme_permalink_calendar_prefix', '' );
	if ( ! empty( $cal_prefix ) ) {
		$cal_prefix = eme_permalink_convert( $cal_prefix );
		$newrules[ '(.*/)?' . $cal_prefix . '(\d{4})-(\d{2})-(\d{2})/c(.*)' ] = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]' . '&eme_event_cat=$matches[5]';
		$newrules[ '(.*/)?' . $cal_prefix . '(\d{4})-(\d{2})-(\d{2})/?$' ]    = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]';
		$newrules[ '(.*/)?' . $cal_prefix . '(\d{4})-(\d{2})-(\d{2})/(.+)$' ] = 'index.php?page_id=' . $events_page_id . '&calendar_day=$matches[2]-$matches[3]-$matches[4]' . '&$matches[5]';
	}

	$payments_prefix = get_option( 'eme_permalink_payments_prefix', '' );
	if ( ! empty( $payments_prefix ) ) {
		$payments_prefix                                  = eme_permalink_convert( $payments_prefix );
		$newrules[ '(.*/)?' . $payments_prefix . '(.*)' ] = 'index.php?page_id=' . $events_page_id . '&eme_pmt_rndid=$matches[2]';
	}

	return $newrules + $rules;
}
add_filter( 'rewrite_rules_array', 'eme_insertMyRewriteRules' );

// Adding the id var so that WP recognizes it
// any variable added in the rewrite rules that is not defined here will get removed, so this is important.
function eme_insertMyRewriteQueryVars( $vars ) {
	array_push( $vars, 'event_id' );
	array_push( $vars, 'location_id' );
	array_push( $vars, 'membership_id' );
	array_push( $vars, 'calendar_day' );
	array_push( $vars, 'eme_city' );
	array_push( $vars, 'eme_country' );
	array_push( $vars, 'eme_event_cat' );
	// a bit cryptic for the booking id
	array_push( $vars, 'eme_pmt_rndid' );
	// for the payment result
	array_push( $vars, 'eme_pmt_result' );
	array_push( $vars, 'eme_rsvp_confirm' );
	// for attendance
	array_push( $vars, 'eme_check_rsvp' );
	// for members
	array_push( $vars, 'eme_check_member' );
	return $vars;
}
add_filter( 'query_vars', 'eme_insertMyRewriteQueryVars' );

// INCLUDES
// We let the includes happen at the end, so all init-code is done
// (like eg. the load_textdomain). Some includes do stuff based on _GET
// so they need the correct info before doing stuff
require_once 'eme-options.php';
require_once 'eme-functions.php';
require_once 'eme-filters.php';
require_once 'eme-events.php';
require_once 'eme-calendar.php';
require_once 'eme-widgets.php';
require_once 'eme-rsvp.php';
require_once 'eme-locations.php';
require_once 'eme-people.php';
require_once 'eme-recurrence.php';
require_once 'eme-ui-helpers.php';
require_once 'eme-categories.php';
require_once 'eme-holidays.php';
require_once 'eme-templates.php';
require_once 'eme-attributes.php';
require_once 'eme-attendances.php';
require_once 'eme-ical.php';
require_once 'eme-cleanup.php';
require_once 'eme-cron.php';
require_once 'eme-formfields.php';
require_once 'eme-shortcodes.php';
require_once 'eme-actions.php';
require_once 'eme-payments.php';
require_once 'eme-discounts.php';
require_once 'eme-members.php';
require_once 'eme-mailer.php';
require_once 'eme-countries.php';
require_once 'eme-gdpr.php';
require_once 'eme-tasks.php';
require_once 'eme-translate.php';

require_once 'class-expressivedate.php';

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/liedekef/events-made-easy/',
	__FILE__,
	'events-made-easy'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');
$myUpdateChecker->getVcsApi()->enableReleaseAssets('/events-made-easy\.zip/');

// now some extra global vars
$eme_plugin_url = eme_plugin_url();
$eme_db_prefix  = eme_get_db_prefix();

function eme_install( $networkwide ) {
	global $wpdb,$eme_db_prefix;
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ( $networkwide ) {
			//$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				_eme_install();
				restore_current_blog();
			}
			// make sure we get the right prefix again at the end, during install it can change in the eme_create_tables call
			$eme_db_prefix = eme_get_db_prefix();
			//switch_to_blog($old_blog);
			return;
		}
	}
	// executed if no network activation
	_eme_install();
}

// the private function; for activation
function _eme_install() {
	global $eme_timezone;

	eme_add_options();
	$db_version = intval( get_option( 'eme_version' ) );
	if ( $db_version > EME_DB_VERSION ) {
		$db_version = EME_DB_VERSION;
	}
	if ( $db_version != EME_DB_VERSION ) {
		eme_update_options( $db_version );
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
	$eme_date_obj = new ExpressiveDate( 'now', $eme_timezone );
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
	$crons_to_remove = array( 'eme_cron_cleanup_unpaid', 'eme_cron_cleanup_unconfirmed', 'eme_cron_cleanup_captcha' );
	foreach ( $crons_to_remove as $tmp_cron ) {
		if ( wp_next_scheduled( $tmp_cron ) ) {
			wp_unschedule_hook( $tmp_cron );
		}
	}

	// we'll restore some planned actions too, if previously deactivated
	$cron_actions = array( 'eme_cron_send_new_events', 'eme_cron_send_queued' );
	foreach ( $cron_actions as $cron_action ) {
		$schedule = get_option( $cron_action );
		// old schedule names are renamed to eme_*
		if (preg_match( '/^(1min|5min|15min|30min|4weeks)$/', $schedule, $matches ) ) {
			$res = $matches[0];
			$schedule = "eme_".$res;
			update_option($cron_action,$schedule);
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

	// now set the version correct
	update_option( 'eme_version', EME_DB_VERSION );
}

function eme_uninstall( $networkwide ) {
	global $wpdb;

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ( $networkwide ) {
			$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				_eme_uninstall();
			}
			switch_to_blog( $old_blog );
			return;
		}
	}
	// executed if no network activation
	_eme_uninstall();
}

function _eme_uninstall( $force_drop = 0 ) {
	$drop_data     = get_option( 'eme_uninstall_drop_data' );
	$drop_settings = get_option( 'eme_uninstall_drop_settings' );

	// these crons get planned with a fixed schedule at activation time, so we don't need to store their planned setting when deactivating
	$cron_actions1 = array( 'eme_cron_daily_actions', 'eme_cron_cleanup_captcha' );
	foreach ( $cron_actions1 as $cron_action ) {
		if ( wp_next_scheduled( $cron_action ) ) {
			wp_unschedule_hook( $cron_action );
		}
	}
	$cron_actions2 = array( 'eme_cron_cleanup_unpaid', 'eme_cron_send_new_events', 'eme_cron_send_queued' );
	foreach ( $cron_actions2 as $cron_action ) {
		// if the action is planned, keep the planning in an option (if we're not clearing all data) and then clear the planning
		if ( wp_next_scheduled( $cron_action ) ) {
			if ( ! ( $drop_settings || $force_drop ) ) {
				$scheduled = wp_get_schedule( $cron_action );
				update_option( $cron_action, $scheduled );
			} else {
				wp_unschedule_hook( $cron_action );
			}
		} else {
			delete_option( $cron_action );
		}
	}

	if ( $drop_data || $force_drop ) {
		// during uninstall, the prefix changes per blog, so get it here
		$db_prefix = eme_get_db_prefix();
		eme_drop_table( $db_prefix . EVENTS_TBNAME );
		eme_drop_table( $db_prefix . RECURRENCE_TBNAME );
		eme_drop_table( $db_prefix . LOCATIONS_TBNAME );
		eme_drop_table( $db_prefix . BOOKINGS_TBNAME );
		eme_drop_table( $db_prefix . PEOPLE_TBNAME );
		eme_drop_table( $db_prefix . GROUPS_TBNAME );
		eme_drop_table( $db_prefix . USERGROUPS_TBNAME );
		eme_drop_table( $db_prefix . CATEGORIES_TBNAME );
		eme_drop_table( $db_prefix . HOLIDAYS_TBNAME );
		eme_drop_table( $db_prefix . TEMPLATES_TBNAME );
		eme_drop_table( $db_prefix . FORMFIELDS_TBNAME );
		eme_drop_table( $db_prefix . FIELDTYPES_TBNAME );
		eme_drop_table( $db_prefix . ANSWERS_TBNAME );
		eme_drop_table( $db_prefix . PAYMENTS_TBNAME );
		eme_drop_table( $db_prefix . DISCOUNTS_TBNAME );
		eme_drop_table( $db_prefix . DISCOUNTGROUPS_TBNAME );
		eme_drop_table( $db_prefix . MQUEUE_TBNAME );
		eme_drop_table( $db_prefix . MAILINGS_TBNAME );
		eme_drop_table( $db_prefix . MEMBERS_TBNAME );
		eme_drop_table( $db_prefix . MEMBERSHIPS_TBNAME );
		eme_drop_table( $db_prefix . COUNTRIES_TBNAME );
		eme_drop_table( $db_prefix . STATES_TBNAME );
		eme_drop_table( $db_prefix . ATTENDANCES_TBNAME );
		eme_drop_table( $db_prefix . TASKS_TBNAME );
		eme_drop_table( $db_prefix . TASK_SIGNUPS_TBNAME );
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

	// SEO rewrite rules
	flush_rewrite_rules();
}

function eme_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb,$eme_db_prefix;

	if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		$old_blog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		_eme_install();
		switch_to_blog( $old_blog );
	}
}

function eme_create_tables( $db_version ) {
	global $wpdb,$eme_db_prefix;
	// Creates the events table if necessary
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// during install, the prefix changes per blog, so get it here
	$eme_db_prefix = eme_get_db_prefix();
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

	eme_create_events_table( $charset, $collate, $db_version );
	eme_create_recurrence_table( $charset, $collate, $db_version );
	eme_create_locations_table( $charset, $collate, $db_version );
	eme_create_bookings_table( $charset, $collate, $db_version );
	eme_create_people_table( $charset, $collate, $db_version );
	eme_create_members_table( $charset, $collate, $db_version );
	eme_create_categories_table( $charset, $collate, $db_version );
	eme_create_holidays_table( $charset, $collate, $db_version );
	eme_create_templates_table( $charset, $collate, $db_version );
	eme_create_formfields_table( $charset, $collate, $db_version );
	eme_create_answers_table( $charset, $collate, $db_version );
	eme_create_payments_table( $charset, $collate, $db_version );
	eme_create_discounts_table( $charset, $collate, $db_version );
	eme_create_discountgroups_table( $charset, $collate, $db_version );
	eme_create_mqueue_table( $charset, $collate, $db_version );
	eme_create_countries_table( $charset, $collate, $db_version );
	eme_create_states_table( $charset, $collate, $db_version );
	eme_create_attendances_table( $charset, $collate, $db_version );
	eme_create_task_tables( $charset, $collate, $db_version );
}

function eme_create_events_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix, $eme_timezone;

	$table_name = $eme_db_prefix . EVENTS_TBNAME;

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
		$eme_date_obj = new ExpressiveDate( 'now', $eme_timezone );
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
		eme_db_insert_event( $event );

		$event                = eme_new_event();
		$event['event_name']  = 'Traditional music session';
		$event['event_start'] = "$in_four_weeks 20:00:00";
		$event['event_end']   = "$in_four_weeks 22:00:00";
		$event['location_id'] = 2;
		$event                = eme_sanitize_event( $event );
		eme_db_insert_event( $event );

		$event                = eme_new_event();
		$event['event_name']  = '6 Nations, Italy VS Ireland';
		$event['event_start'] = "$in_one_year 22:00:00";
		$event['event_end']   = "$in_one_year 23:59:59";
		$event['location_id'] = 3;
		$event                = eme_sanitize_event( $event );
		eme_db_insert_event( $event );

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
			$post_table_name = $eme_db_prefix . 'posts';
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

function eme_create_recurrence_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . RECURRENCE_TBNAME;

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

function eme_create_locations_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . LOCATIONS_TBNAME;

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
			$post_table_name = $eme_db_prefix . 'posts';
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

function eme_create_bookings_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . BOOKINGS_TBNAME;

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
			$people_table_name = $eme_db_prefix . PEOPLE_TBNAME;
			$wpdb->query( "update $table_name a JOIN $people_table_name b on (a.person_id = b.person_id)  set a.wp_id=b.wp_id;" );
		}
		if ( $db_version < 92 ) {
			maybe_add_column( $table_name, 'payment_id', "ALTER TABLE $table_name ADD payment_id mediumint(9) DEFAULT NULL;" );
			$payment_table_name = $eme_db_prefix . PAYMENTS_TBNAME;
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

function eme_create_people_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name          = $eme_db_prefix . PEOPLE_TBNAME;
	$grouptable_name     = $eme_db_prefix . GROUPS_TBNAME;
	$usergrouptable_name = $eme_db_prefix . USERGROUPS_TBNAME;

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

function eme_create_categories_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . CATEGORIES_TBNAME;

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
				$where                   = array();
				$fields                  = array();
				$where['category_id']    = $this_category['category_id'];
				$fields['category_slug'] = eme_permalink_convert_noslash( $this_category['category_name'] );
				$wpdb->update( $table_name, $fields, $where );
			}
		}
	}
}

function eme_create_holidays_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . HOLIDAYS_TBNAME;

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

function eme_create_templates_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . TEMPLATES_TBNAME;

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

function eme_create_formfields_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . FORMFIELDS_TBNAME;

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
			eme_drop_table( $eme_db_prefix . FIELDTYPES_TBNAME );
		}
		// the next one is to fix older issues
		if ( $db_version < 193 ) {
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='radiobox_vertical' WHERE old_type=5;" );
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_type='checkbox_vertical' WHERE old_type=7;" );
			eme_drop_table( $eme_db_prefix . FIELDTYPES_TBNAME );
		}
		if ( $db_version < 214 ) {
			$wpdb->query( 'UPDATE ' . $table_name . " SET field_purpose='generic' WHERE field_purpose IS NULL;" );
		}
		if ( $db_version < 215 ) {
			eme_maybe_drop_column( $table_name, 'old_type' );
		}
	}
}

function eme_create_answers_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . ANSWERS_TBNAME;

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
			$formfield_table_name = $eme_db_prefix . FORMFIELDS_TBNAME;
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
			$cf_table_name = $eme_db_prefix . MEMBERSHIPS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
					$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`membership_id`,`answer`,'membership' FROM $cf_table_name" );
					eme_drop_table( $cf_table_name );
			}
			$cf_table_name = $eme_db_prefix . EVENTS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
				$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`event_id`,`answer`,'event' FROM $cf_table_name" );
				eme_drop_table( $cf_table_name );
			}
			$cf_table_name = $eme_db_prefix . LOCATIONS_CF_TBNAME;
			if ( eme_table_exists( $cf_table_name ) ) {
					$wpdb->query( "INSERT INTO $table_name(`field_id`,`related_id`,`answer`,`type`) SELECT `field_id`,`location_id`,`answer`,'location' FROM $cf_table_name" );
					eme_drop_table( $cf_table_name );
			}
		}
	}
}

function eme_create_payments_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . PAYMENTS_TBNAME;

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

function eme_create_discounts_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . DISCOUNTS_TBNAME;

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

function eme_create_discountgroups_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . DISCOUNTGROUPS_TBNAME;

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

function eme_create_mqueue_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . MQUEUE_TBNAME;

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

	$table_name = $eme_db_prefix . MAILINGS_TBNAME;
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
function eme_create_members_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . MEMBERS_TBNAME;

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
	$table_name = $eme_db_prefix . MEMBERSHIPS_TBNAME;
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

function eme_create_countries_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . COUNTRIES_TBNAME;

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

function eme_create_states_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . STATES_TBNAME;

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

function eme_create_task_tables( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . TASKS_TBNAME;

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

	$table_name = $eme_db_prefix . TASK_SIGNUPS_TBNAME;
	if ( ! eme_table_exists( $table_name ) ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
         id int(11) NOT NULL auto_increment,
         task_id mediumint(9) NOT NULL,
         person_id mediumint(9) NOT NULL,
         event_id mediumint(9) NOT NULL,
         random_id varchar(50),
         UNIQUE KEY  (id),
         KEY  (event_id)
         ) $charset $collate;";
		maybe_create_table( $table_name, $sql );
	}
}

function eme_create_attendances_table( $charset, $collate, $db_version ) {
	global $wpdb,$eme_db_prefix;
	$table_name = $eme_db_prefix . ATTENDANCES_TBNAME;

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
	global $wpdb,$eme_db_prefix;
	$postarr = array(
		'post_title'     => wp_strip_all_tags( __( 'Events', 'events-made-easy' ) ),
		'post_content'   => __( "This page is used by Events Made Easy. Don't change it, don't use it in your menu's, don't delete it. Just make sure the EME setting called 'Events page' points to this page. EME uses this page to render any and all events, locations, bookings, maps, ... anything. If you do want to delete this page, create a new one EME can use and update the EME setting 'Events page' accordingly.", 'events-made-easy' ),
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);
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

// Create the Manage Events and the Options submenus
add_action( 'admin_menu', 'eme_create_events_submenu' );
function eme_create_events_submenu() {
	global $eme_plugin_url;
	# just in case: make sure the Settings page can be reached if something is not correct with the security settings
	if ( get_option( 'eme_cap_settings' ) == '' ) {
		$cap_settings = DEFAULT_CAP_SETTINGS;
	} else {
		$cap_settings = get_option( 'eme_cap_settings' );
	}

	$events_page_id = eme_get_events_page_id();
	if ( ! $events_page_id ) {
		add_action( 'admin_notices', 'eme_explain_events_page_missing' );
	} else {
		$events_page = get_page( $events_page_id );
		if ( ! $events_page || $events_page->post_status != 'publish' ) {
			add_action( 'admin_notices', 'eme_explain_events_page_missing' );
		}
	}

	if ( function_exists( 'add_submenu_page' ) ) {
		// check if the db version is ok, otherwise the call to eme_count_pending_bookings and eme_count_pending_members cause errors during plugin install
		$db_version = intval( get_option( 'eme_version' ) );
		if ( $db_version == EME_DB_VERSION ) {
			$pending_bookings_count = eme_count_pending_bookings();
			$pending_members_count  = eme_count_pending_members();
		} else {
			$pending_bookings_count = 0;
			$pending_members_count  = 0;
		}
		$pending_count          = $pending_bookings_count + $pending_members_count;
		$pending_bookings_title = esc_attr( sprintf( __( '%d pending bookings', 'events-made-easy' ), $pending_bookings_count ) );
		$pending_members_title  = esc_attr( sprintf( __( '%d pending members', 'events-made-easy' ), $pending_members_count ) );
		// we can't use the global var $plugin_page yet, so we check using _GET
		if ( !empty($_GET['page']) && preg_match( '/^eme-/', eme_sanitize_request($_GET['page']) ) ) {
			$main_menu_label = '';
		} else {
			// show the count on the main menu if we're not in the EME menu
			$main_menu_label = " <span class='update-plugins count-$pending_count'<span class='update-count'>" . number_format_i18n( $pending_count ) . '</span></span>';
		}
		if ( $pending_bookings_count ) {
			$pending_bookings_menu_label = " <span class='update-plugins count-$pending_bookings_count' title='$pending_bookings_title'><span class='update-count'>" . number_format_i18n( $pending_bookings_count ) . '</span></span>';
		} else {
			$pending_bookings_menu_label = '';
		}
		if ( $pending_members_count ) {
			$members_menu_label = " <span class='update-plugins count-$pending_members_count' title='$pending_members_title'><span class='update-count'>" . number_format_i18n( $pending_members_count ) . '</span></span>';
		} else {
			$members_menu_label = '';
		}

		// location 40: Above the appearance menu
		add_menu_page( __( 'Events Made Easy', 'events-made-easy' ), __( 'Events Made Easy', 'events-made-easy' ) . $main_menu_label, get_option( 'eme_cap_list_events' ), 'eme-manager', 'eme_events_page', $eme_plugin_url . 'images/calendar-16.png', 40 );
		// Add a submenu to the custom top-level menu:
		// edit event also needs just "add" as capability, otherwise you will not be able to edit own created events
		$plugin_page = add_submenu_page( 'eme-manager', __( 'All events', 'events-made-easy' ), __( 'Events', 'events-made-easy' ), get_option( 'eme_cap_list_events' ), 'eme-manager', 'eme_events_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Locations', 'events-made-easy' ), __( 'Locations', 'events-made-easy' ), get_option( 'eme_cap_list_locations' ), 'eme-locations', 'eme_locations_page' );
		if ( get_option( 'eme_categories_enabled' ) ) {
			$plugin_page = add_submenu_page( 'eme-manager', __( 'Categories', 'events-made-easy' ), __( 'Categories', 'events-made-easy' ), get_option( 'eme_cap_categories' ), 'eme-categories', 'eme_categories_page' );
		}
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Holidays', 'events-made-easy' ), __( 'Holidays', 'events-made-easy' ), get_option( 'eme_cap_holidays' ), 'eme-holidays', 'eme_holidays_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Custom Fields', 'events-made-easy' ), __( 'Custom Fields', 'events-made-easy' ), get_option( 'eme_cap_forms' ), 'eme-formfields', 'eme_formfields_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Templates', 'events-made-easy' ), __( 'Templates', 'events-made-easy' ), get_option( 'eme_cap_templates' ), 'eme-templates', 'eme_templates_page' );
		if ( get_option( 'eme_rsvp_enabled' ) ) {
			$plugin_page = add_submenu_page( 'eme-manager', __( 'Discounts', 'events-made-easy' ), __( 'Discounts', 'events-made-easy' ), get_option( 'eme_cap_discounts' ), 'eme-discounts', 'eme_discounts_page' );
			$plugin_page = add_submenu_page( 'eme-manager', __( 'Pending Bookings', 'events-made-easy' ), __( 'Pending Bookings', 'events-made-easy' ) . $pending_bookings_menu_label, get_option( 'eme_cap_list_approve' ), 'eme-registration-approval', 'eme_registration_approval_page' );
			$plugin_page = add_submenu_page( 'eme-manager', __( 'Approved Bookings', 'events-made-easy' ), __( 'Approved Bookings', 'events-made-easy' ), get_option( 'eme_cap_list_registrations' ), 'eme-registration-seats', 'eme_registration_seats_page' );
		}
		if ( get_option( 'eme_tasks_enabled' ) ) {
			$plugin_page = add_submenu_page( 'eme-manager', __( 'Task signups', 'events-made-easy' ), __( 'Task signups', 'events-made-easy' ), get_option( 'eme_cap_manage_task_signups' ), 'eme-task-signups', 'eme_task_signups_page' );
		}
		$plugin_page = add_submenu_page( 'eme-manager', __( 'People', 'events-made-easy' ), __( 'People', 'events-made-easy' ), get_option( 'eme_cap_access_people' ), 'eme-people', 'eme_people_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Groups', 'events-made-easy' ), __( 'Groups', 'events-made-easy' ), get_option( 'eme_cap_access_people' ), 'eme-groups', 'eme_groups_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Members', 'events-made-easy' ), __( 'Members', 'events-made-easy' ) . $members_menu_label, get_option( 'eme_cap_access_members' ), 'eme-members', 'eme_members_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Memberships', 'events-made-easy' ), __( 'Memberships', 'events-made-easy' ), get_option( 'eme_cap_access_members' ), 'eme-memberships', 'eme_memberships_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Countries/states', 'events-made-easy' ), __( 'Countries/states', 'events-made-easy' ), $cap_settings, 'eme-countries', 'eme_countries_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Email management', 'events-made-easy' ), __( 'Email management', 'events-made-easy' ), get_option( 'eme_cap_send_mails' ), 'eme-emails', 'eme_emails_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Attendance Reports', 'events-made-easy' ), __( 'Attendance Reports', 'events-made-easy' ), get_option( 'eme_cap_list_events' ), 'eme-attendance-reports', 'eme_attendances_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Scheduled actions', 'events-made-easy' ), __( 'Scheduled actions', 'events-made-easy' ), $cap_settings, 'eme-cron', 'eme_cron_page' );
		$plugin_page = add_submenu_page( 'eme-manager', __( 'Cleanup actions', 'events-made-easy' ), __( 'Cleanup actions', 'events-made-easy' ), get_option( 'eme_cap_cleanup' ), 'eme-cleanup', 'eme_cleanup_page' );

		$plugin_page = add_submenu_page( 'eme-manager', __( 'Events Made Easy Settings', 'events-made-easy' ), __( 'Settings', 'events-made-easy' ), $cap_settings, 'eme-options', 'eme_options_page' );
		//add_action( 'admin_head-'. $plugin_page, 'eme_admin_options_script' );
		// do some option checking after the options have been updated
		// add_action( 'load-'. $plugin_page, 'eme_admin_options_save');
	}
}

function eme_explain_events_page_missing() {
	$advice = sprintf( __( "Error: the special events page is not set or no longer exist, please set the option '%s' to an existing page or EME will not work correctly!", 'events-made-easy' ), __( 'Events page', 'events-made-easy' ) );
	?>
	<div id="message" class="error"><p> <?php echo eme_esc_html( $advice ); ?> </p></div>
	<?php
}

add_filter( 'admin_footer_text', 'eme_admin_footer_text' );
function eme_admin_footer_text( $text ) {
	global $plugin_page;
	if ( empty( $plugin_page ) ) {
			return $text;
	}

	if ( preg_match( '/^eme-/', $plugin_page ) ) {
		$text = sprintf(
				/* translators: %s: review url */
			__( 'If you like Events Made Easy, please leave a <a href="%s" target="_blank" style="text-decoration:none">★★★★★</a> rating. A huge thanks in advance!', 'events-made-easy' ),
			'https://wordpress.org/support/plugin/events-made-easy/reviews/?filter=5'
		);
	}
	return $text;
}

?>
