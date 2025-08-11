<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
Plugin Name: Events Made Easy
Version: 3.0.0
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

// INCLUDES
require_once 'eme-install.php';
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
require_once 'eme-todos.php';
require_once 'eme-translate.php';
require_once 'eme-fs.php';
if ( ! class_exists( 'ExpressiveDate' ) ) {
    require_once 'class-expressivedate.php';
}

// Setting constants, no calls to "__" here!!!
define( 'EME_VERSION', '2.6.11' );
define( 'EME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
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
define( 'DEFAULT_CAP_SEND_GENERIC_MAILS', 'edit_posts' );
define( 'DEFAULT_CAP_VIEW_MAILS', 'edit_posts' );
define( 'DEFAULT_CAP_MANAGE_MAILS', 'edit_others_posts' );
define( 'DEFAULT_CAP_LIST_ATTENDANCES', 'edit_posts' );
define( 'DEFAULT_CAP_MANAGE_ATTENDANCES', 'edit_others_posts' );
define( 'DEFAULT_EVENT_LIST_HEADER_FORMAT', "<ul class='eme_events_list'>" );
define( 'DEFAULT_EVENT_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_CAT_EVENT_LIST_HEADER_FORMAT', "<ul class='eme_events_list'>" );
define( 'DEFAULT_CAT_EVENT_LIST_FOOTER_FORMAT', '</ul>' );
define( 'DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT', '<li>#_LINKEDNAME<ul><li>#_STARTDATE</li><li>#_TOWN</li></ul></li>' );
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
define( 'EME_EVENT_STATUS_FS_DRAFT', 6 );
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
define( 'EME_MAIL_STATUS_PLANNED', 0);
define( 'EME_MAIL_STATUS_SENT', 1);
define( 'EME_MAIL_STATUS_FAILED', 2);
define( 'EME_MAIL_STATUS_CANCELLED', 3);
define( 'EME_MAIL_STATUS_IGNORED', 4);
define( 'EME_MAIL_STATUS_DELAYED', 5);
define( 'EME_MAIL_STATUS_RESENT', 6);
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
define( 'EME_INCLUDE_DIR', $upload_info['basedir'] . '/events-made-easy/includes' );
define( 'EME_PLUGIN_URL',  eme_plugin_url() );
define( 'EME_DB_PREFIX',  eme_get_db_prefix() );
define( 'EME_WP_DATE_FORMAT', get_option( 'date_format' ) );
define( 'EME_WP_TIME_FORMAT', get_option( 'time_format' ) );
define( 'EME_TIMEZONE', wp_timezone_string() );

// 2 globals, being used in filters to prevent shortcode/filter combo recursion
$eme_page_title_count = 0;
$eme_html_title_count = 0;

function eme_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'events-manager.php' ) !== false ) {
		$new_links = [
			'donate Paypal'  => '<a href="https://www.paypal.com/donate/?business=SMGDS4GLCYWNG&no_recurring=0&currency_code=EUR">Donate (Paypal)</a>',
			'Github Sponsoring'  => '<a href="https://github.com/sponsors/liedekef">Github sponsoring</a>',
			'Support' => '<a href="https://github.com/liedekef/events-made-easy">Support</a>',
		];
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'eme_plugin_row_meta', 10, 2 );

function eme_add_action_links( $links ) {
	$mylinks = [ '<a href="admin.php?page=eme-options">Settings</a>' ];
	return array_merge( $links, $mylinks );
}
add_filter( 'plugin_action_links_' . EME_PLUGIN_BASENAME, 'eme_add_action_links' );

// To enable activation through the activate function
register_activation_hook( __FILE__, 'eme_install' );
// when deactivation is needed
register_deactivation_hook( __FILE__, 'eme_uninstall' );
// when a new blog is added for network installation and the plugin is network activated
add_action( 'wp_initialize_site', 'eme_new_blog', 900, 2 );

// filters for general events field
add_filter( 'eme_general', 'wptexturize' );
//add_filter( 'eme_general', 'convert_smilies' );
add_filter( 'eme_general', 'convert_chars' );
add_filter( 'eme_general', 'trim' );

// TEXT content filter
add_filter( 'eme_text', 'wp_strip_all_tags' );
add_filter( 'eme_text', 'html_entity_decode' );

// Adding a new rule
function eme_insertMyRewriteRules( $rules ) {
	// using pagename as param to index.php causes rewrite troubles if the page is a subpage of another
	// luckily for us we have the page id, and this works ok
	$events_page_id  = eme_get_events_page_id();
	$newrules        = [];
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
	array_push( $vars, 'event_id' , 'location_id' , 'membership_id' , 'calendar_day' , 'eme_city' , 'eme_country' , 'eme_event_cat' , 'eme_pmt_rndid' , 'eme_pmt_result' , 'eme_rsvp_confirm' , 'eme_check_rsvp' , 'eme_check_member' );
	return $vars;
}
add_filter( 'query_vars', 'eme_insertMyRewriteQueryVars' );

// include our custom update checker code
require_once 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/liedekef/events-made-easy/',
	__FILE__,
	'events-made-easy'
);
// we'll use a release asset
$myUpdateChecker->getVcsApi()->enableReleaseAssets('/events-made-easy\.zip/');

// Create the Manage Events and the Options submenus
add_action( 'admin_menu', 'eme_create_events_submenu' );
function eme_create_events_submenu() {
	# just in case: make sure the Settings page can be reached if something is not correct with the security settings
	$cap_settings = get_option( 'eme_cap_settings' );
    if (empty($cap_settings)) {
		$cap_settings = DEFAULT_CAP_SETTINGS;
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
		$pending_bookings_title = esc_attr( sprintf(_n( '%d pending booking', '%d pending bookings', $pending_bookings_count, 'events-made-easy' ), number_format_i18n($pending_bookings_count ) ));
		$pending_members_title  = esc_attr( sprintf(_n( '%d pending member', '%d pending members', $pending_members_count, 'events-made-easy' ), number_format_i18n($pending_members_count ) ));
		// we can't use the global var $plugin_page yet, so we check using _GET
		if ( !empty($_GET['page']) && preg_match( '/^eme-/', eme_sanitize_request($_GET['page']) ) ) {
			$main_menu_label = '';
		} else {
            if ( $pending_count ) {
                // show the count on the main menu if we're not in the EME menu
                $main_menu_label = " <span class='update-plugins'>" . number_format_i18n( $pending_count ) . '</span>';
            } else {
                $main_menu_label = '';
            }
		}
		if ( $pending_bookings_count ) {
			$pending_bookings_menu_label = " <span class='update-plugins' title='$pending_bookings_title'>" . number_format_i18n( $pending_bookings_count ) . '</span>';
		} else {
			$pending_bookings_menu_label = '';
		}
		if ( $pending_members_count ) {
			$members_menu_label = " <span class='update-plugins' title='$pending_members_title'>" . number_format_i18n( $pending_members_count ) . '</span>';
		} else {
			$members_menu_label = '';
		}

		// location 40: Above the appearance menu
		add_menu_page( __( 'Events Made Easy', 'events-made-easy' ), __( 'Events Made Easy', 'events-made-easy' ) . $main_menu_label, get_option( 'eme_cap_list_events' ), 'eme-manager', 'eme_events_page', EME_PLUGIN_URL . 'images/calendar-16.png', 40 );
		// Add a submenu to the custom top-level menu:
		// edit event also needs just "add" as capability, otherwise you will not be able to edit own created events
		add_submenu_page( 'eme-manager', __( 'All events', 'events-made-easy' ), __( 'Events', 'events-made-easy' ), get_option( 'eme_cap_list_events' ), 'eme-manager', 'eme_events_page' );
		add_submenu_page( 'eme-manager', __( 'Locations', 'events-made-easy' ), __( 'Locations', 'events-made-easy' ), get_option( 'eme_cap_list_locations' ), 'eme-locations', 'eme_locations_page' );
		if ( get_option( 'eme_categories_enabled' ) ) {
			add_submenu_page( 'eme-manager', __( 'Categories', 'events-made-easy' ), __( 'Categories', 'events-made-easy' ), get_option( 'eme_cap_categories' ), 'eme-categories', 'eme_categories_page' );
		}
		add_submenu_page( 'eme-manager', __( 'Holidays', 'events-made-easy' ), __( 'Holidays', 'events-made-easy' ), get_option( 'eme_cap_holidays' ), 'eme-holidays', 'eme_holidays_page' );
		add_submenu_page( 'eme-manager', __( 'Custom Fields', 'events-made-easy' ), __( 'Custom Fields', 'events-made-easy' ), get_option( 'eme_cap_forms' ), 'eme-formfields', 'eme_formfields_page' );
		add_submenu_page( 'eme-manager', __( 'Templates', 'events-made-easy' ), __( 'Templates', 'events-made-easy' ), get_option( 'eme_cap_templates' ), 'eme-templates', 'eme_templates_page' );
		if ( get_option( 'eme_rsvp_enabled' ) ) {
			add_submenu_page( 'eme-manager', __( 'Discounts', 'events-made-easy' ), __( 'Discounts', 'events-made-easy' ), get_option( 'eme_cap_discounts' ), 'eme-discounts', 'eme_discounts_page' );
			add_submenu_page( 'eme-manager', __( 'Pending Bookings', 'events-made-easy' ), __( 'Pending Bookings', 'events-made-easy' ) . $pending_bookings_menu_label, get_option( 'eme_cap_list_approve' ), 'eme-registration-approval', 'eme_registration_approval_page' );
			add_submenu_page( 'eme-manager', __( 'Approved Bookings', 'events-made-easy' ), __( 'Approved Bookings', 'events-made-easy' ), get_option( 'eme_cap_list_registrations' ), 'eme-registration-seats', 'eme_registration_seats_page' );
		}
		if ( get_option( 'eme_tasks_enabled' ) ) {
			add_submenu_page( 'eme-manager', __( 'Task signups', 'events-made-easy' ), __( 'Task signups', 'events-made-easy' ), get_option( 'eme_cap_manage_task_signups' ), 'eme-task-signups', 'eme_task_signups_page' );
		}
		add_submenu_page( 'eme-manager', __( 'People', 'events-made-easy' ), __( 'People', 'events-made-easy' ), get_option( 'eme_cap_access_people' ), 'eme-people', 'eme_people_page' );
		add_submenu_page( 'eme-manager', __( 'Groups', 'events-made-easy' ), __( 'Groups', 'events-made-easy' ), get_option( 'eme_cap_access_people' ), 'eme-groups', 'eme_groups_page' );
		if ( get_option( 'eme_members_enabled' ) ) {
            add_submenu_page( 'eme-manager', __( 'Members', 'events-made-easy' ), __( 'Members', 'events-made-easy' ) . $members_menu_label, get_option( 'eme_cap_access_members' ), 'eme-members', 'eme_members_page' );
            add_submenu_page( 'eme-manager', __( 'Memberships', 'events-made-easy' ), __( 'Memberships', 'events-made-easy' ), get_option( 'eme_cap_access_members' ), 'eme-memberships', 'eme_memberships_page' );
        }
		add_submenu_page( 'eme-manager', __( 'Countries/states', 'events-made-easy' ), __( 'Countries/states', 'events-made-easy' ), $cap_settings, 'eme-countries', 'eme_countries_page' );
		add_submenu_page( 'eme-manager', __( 'Email management', 'events-made-easy' ), __( 'Email management', 'events-made-easy' ), get_option( 'eme_cap_send_mails' ), 'eme-emails', 'eme_emails_page' );
		add_submenu_page( 'eme-manager', __( 'Attendance Reports', 'events-made-easy' ), __( 'Attendance Reports', 'events-made-easy' ), get_option( 'eme_cap_list_events' ), 'eme-attendance-reports', 'eme_attendances_page' );
		add_submenu_page( 'eme-manager', __( 'Scheduled actions', 'events-made-easy' ), __( 'Scheduled actions', 'events-made-easy' ), $cap_settings, 'eme-cron', 'eme_cron_page' );
		add_submenu_page( 'eme-manager', __( 'Cleanup actions', 'events-made-easy' ), __( 'Cleanup actions', 'events-made-easy' ), get_option( 'eme_cap_cleanup' ), 'eme-cleanup', 'eme_cleanup_page' );
		add_submenu_page( 'eme-manager', __( 'Events Made Easy Settings', 'events-made-easy' ), __( 'Settings', 'events-made-easy' ), $cap_settings, 'eme-options', 'eme_options_page' );
	}
}

function eme_explain_events_page_missing() {
	$advice = sprintf( __( "Error: the special events page is not set or no longer exist, please set the option '%s' to an existing page or EME will not work correctly!", 'events-made-easy' ), __( 'Events page', 'events-made-easy' ) );
	?>
	<div id="message" class="error"><p> <?php echo eme_esc_html( $advice ); ?> </p></div>
	<?php
}

if (file_exists(EME_INCLUDE_DIR) && is_dir(EME_INCLUDE_DIR)) {
	foreach ( glob( EME_INCLUDE_DIR . '/eme_*.php' ) as $file ) {
		require_once($file);
	}
}
?>
