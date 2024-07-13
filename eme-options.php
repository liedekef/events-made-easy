<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_add_options( $reset = 0 ) {
	$contact_person_email_subject_localizable                = __( "New booking for '#_EVENTNAME'", 'events-made-easy' );
	$contact_person_email_body_localizable                   = __( '#_PERSONFULLNAME (#_PERSONEMAIL) will attend #_EVENTNAME on #_STARTDATE. They want to book #_RESPSEATS seat(s).<br>Now there are #_RESERVEDSEATS seat(s) booked, #_AVAILABLESEATS are still available.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$contactperson_cancelled_email_subject_localizable       = __( "A booking has been cancelled for '#_EVENTNAME'", 'events-made-easy' );
	$contactperson_cancelled_email_body_localizable          = __( '#_PERSONFULLNAME (#_PERSONEMAIL) has cancelled for #_EVENTNAME on #_STARTDATE. <br>Now there are #_RESERVEDSEATS seat(s) booked, #_AVAILABLESEATS are still available.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$contact_person_pending_email_subject_localizable        = __( "Approval required for new booking for '#_EVENTNAME'", 'events-made-easy' );
	$contact_person_pending_email_body_localizable           = __( '#_PERSONFULLNAME (#_PERSONEMAIL) would like to attend #_EVENTNAME on #_STARTDATE. They want to book #_RESPSEATS seat(s).<br>Now there are #_RESERVEDSEATS seat(s) booked, #_AVAILABLESEATS are still available.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$respondent_email_subject_localizable                    = __( "Booking for '#_EVENTNAME' confirmed", 'events-made-easy' );
	$respondent_email_body_localizable                       = __( 'Dear #_PERSONFULLNAME,<br><br>You have successfully booked #_RESPSEATS seat(s) for #_EVENTNAME.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_pending_email_subject_localizable          = __( "Booking for '#_EVENTNAME' is pending", 'events-made-easy' );
	$registration_pending_email_body_localizable             = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME is pending.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_userpending_email_subject_localizable      = __( "Booking for '#_EVENTNAME' requires your confirmation", 'events-made-easy' );
	$registration_userpending_email_body_localizable         = __( "Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME requires your confirmation.<br>Please click on this link to confirm #_BOOKING_CONFIRM_URL<br>If you did not make this booking, you don't need to do anything, it will then be removed automatically.<br><br>Yours faithfully,<br>#_CONTACTPERSON", 'events-made-easy' );
	$registration_cancelled_email_subject_localizable        = __( "Booking for '#_EVENTNAME' cancelled", 'events-made-easy' );
	$registration_cancelled_email_body_localizable           = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME has been cancelled.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_trashed_email_subject_localizable          = __( "Booking for '#_EVENTNAME' deleted", 'events-made-easy' );
	$registration_trashed_email_body_localizable             = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME has been deleted.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_updated_email_subject_localizable          = __( "Booking for '#_EVENTNAME' updated", 'events-made-easy' );
	$registration_updated_email_body_localizable             = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME has been updated.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_paid_email_subject_localizable             = __( "Booking for '#_EVENTNAME' paid", 'events-made-easy' );
	$registration_paid_email_body_localizable                = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to book #_RESPSEATS seat(s) for #_EVENTNAME has been paid for.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_pending_reminder_email_subject_localizable = __( "Reminder: pending booking for event '#_EVENTNAME'", 'events-made-easy' );
	$registration_pending_reminder_email_body_localizable    = __( 'Dear #_PERSONFULLNAME,<br><br>This is a reminder that your request to book #_RESPSEATS seat(s) for #_EVENTNAME is pending.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_reminder_email_subject_localizable         = __( "Reminder: Booking for event '#_EVENTNAME'", 'events-made-easy' );
	$registration_reminder_email_body_localizable            = __( 'Dear #_PERSONFULLNAME,<br><br>This is a reminder that you booked #_RESPSEATS seat(s) for #_EVENTNAME.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$registration_recorded_ok_html_localizable               = __( 'Your booking has been recorded', 'events-made-easy' );
	$registration_form_format_localizable                    = "<table class='eme-rsvp-form'>
            <tr><th scope='row'>" . __( 'Last name', 'events-made-easy' ) . "*:</th><td>#_LASTNAME</td></tr>
            <tr><th scope='row'>" . __( 'First name', 'events-made-easy' ) . "*:</th><td>#REQ_FIRSTNAME</td></tr>
            <tr><th scope='row'>" . __( 'Email', 'events-made-easy' ) . "*:</th><td>#_EMAIL</td></tr>
            <tr><th scope='row'>" . __( 'Phone number', 'events-made-easy' ) . ":</th><td>#_PHONE</td></tr>
            <tr><th scope='row'>" . __( 'Seats', 'events-made-easy' ) . "*:</th><td>#_SEATS</td></tr>
            <tr><th scope='row'>" . __( 'Comment', 'events-made-easy' ) . ':</th><td>#_COMMENT</td></tr>
            </table>
            #_SUBMIT
            ';
	$cancel_form_format_localizable                          = "<table class='eme-rsvp-form'>
            <tr><th scope='row'>" . __( 'Last name', 'events-made-easy' ) . "*:</th><td>#_LASTNAME</td></tr>
            <tr><th scope='row'>" . __( 'First name', 'events-made-easy' ) . "*:</th><td>#REQ_FIRSTNAME</td></tr>
            <tr><th scope='row'>" . __( 'Email', 'events-made-easy' ) . '*:</th><td>#_EMAIL</td></tr>
            </table>
            #_SUBMIT
            ';
	$cancel_payment_form_format_localizable                  = __( "You're about to cancel the following bookings:", 'events-made-easy' ) . '<br>#_CANCEL_PAYMENT_LINE #_SUBMIT';
	$cancel_payment_line_format_localizable                  = '#_STARTDATE #_STARTTIME: #_EVENTNAME (#_RESPSEATS ' . __( 'seats', 'events-made-easy' ) . ')<br>';
	$cancelled_payment_format_localizable                    = __( 'The following bookings have been cancelled:', 'events-made-easy' ) . '<br>#_CANCEL_PAYMENT_LINE';
	$eme_cpi_subject_localizable                             = __( 'Change personal info request', 'events-made-easy' );
	$eme_cpi_body_localizable                                = __( 'Hi,<br><br>Please find below the info needed to change the personal info for each matching person<br>#_CHANGE_PERSON_INFO<br><br>Yours faithfully', 'events-made-easy' );
	$eme_cpi_form_localizable                                = esc_html__( 'Last name: ', 'events-made-easy' ) . '#_LASTNAME <br>' .
				esc_html__( 'First name: ', 'events-made-easy' ) . '#_FIRSTNAME <br>' .
				esc_html__( 'Email: ', 'events-made-easy' ) . '#_EMAIL <br>';

	$eme_gdpr_page_title_localizable           = __( 'Personal info', 'events-made-easy' );
	$eme_gdpr_subject_localizable              = __( 'Personal info request', 'events-made-easy' );
	$eme_gdpr_body_localizable                 = __( 'Hi, please copy/paste this link in your browser to be able to see all your personal info: #_GDPR_URL', 'events-made-easy' );
	$eme_gdpr_approve_page_title_localizable   = __( 'GDPR approval', 'events-made-easy' );
	$eme_gdpr_approve_page_content_localizable = __( 'Thank you for allowing us to store your personal info.', 'events-made-easy' );
	$eme_gdpr_approve_subject_localizable      = __( 'Personal info approval request', 'events-made-easy' );
	$eme_gdpr_approve_body_localizable         = __( 'Hi, please copy/paste this link in your browser to allow us to store your personal info: #_GDPR_APPROVE_URL', 'events-made-easy' );
	$eme_sub_subject_localizable               = __( 'Subscription request', 'events-made-easy' );
	$eme_sub_body_localizable                  = __( 'Hi, please copy/paste this link in your browser to subscribe: #_SUB_CONFIRM_URL . This link will expire within one day.', 'events-made-easy' );
	$eme_unsub_subject_localizable             = __( 'Unsubscription request', 'events-made-easy' );
	$eme_unsub_body_localizable                = __( 'Hi, please copy/paste this link in your browser to unsubscribe: #_UNSUB_CONFIRM_URL . This link will expire within one day.', 'events-made-easy' );
	$eme_payment_button_label_localizable      = __( 'Pay via %s', 'events-made-easy' );
	$eme_payment_button_above_localizable      = '<br>' . __( 'You can pay via %s. If you wish to do so, click the button below.', 'events-made-easy' );

	$eme_rsvp_not_yet_allowed_localizable              = __( 'Bookings not yet allowed on this date.', 'events-made-easy' );
	$eme_rsvp_no_longer_allowed_localizable            = __( 'Bookings no longer allowed on this date.', 'events-made-easy' );
	$eme_rsvp_cancel_no_longer_allowed_localizable     = __( 'Cancellations no longer allowed on this date.', 'events-made-easy' );
	$eme_rsvp_full_localizable                         = __( 'Bookings no longer possible: no seats available anymore.', 'events-made-easy' );
	$eme_rsvp_on_waiting_list_localizable              = __( 'This booking will be put on the waiting list.', 'events-made-easy' );
	$eme_form_required_field_string_localizable        = __( 'Required field', 'events-made-easy' );
	$eme_address1_localizable                          = __( 'Address line 1', 'events-made-easy' );
	$eme_address2_localizable                          = __( 'Address line 2', 'events-made-easy' );
	$eme_payment_redirect_msg_localizable              = __( 'You will be redirected to the payment page in a few seconds, or click <a href="#_PAYMENT_URL">here</a> to go there immediately', 'events-made-easy' );
	$eme_membership_unauth_attendance_msg_localizeable = __( 'OK, member #_MEMBERID (#_PERSONFULLNAME) is active.', 'events-made-easy' );
	$eme_membership_attendance_msg_localizeable        = __( 'OK, member #_MEMBERID (#_PERSONFULLNAME) is active.', 'events-made-easy' );
	$task_signup_recorded_ok_html_localizable          = __( 'You have successfully signed up for this task', 'events-made-easy' );
	$task_signup_cancelled_ok_html_localizable         = __( 'You have successfully cancelled your signup for this task', 'events-made-easy' );
	$task_form_format_localizable                      = "<table class='eme-rsvp-form'>
            <tr><th scope='row'>" . __( 'Last name', 'events-made-easy' ) . "*:</th><td>#_LASTNAME</td></tr>
            <tr><th scope='row'>" . __( 'First name', 'events-made-easy' ) . "*:</th><td>#REQ_FIRSTNAME</td></tr>
            <tr><th scope='row'>" . __( 'Email', 'events-made-easy' ) . '*:</th><td>#_EMAIL</td></tr>
            </table>
            #_SUBMIT
            ';
	$task_cp_pending_email_subject_localizable         = __( "Approval required for signup for task '#_TASKNAME' for '#_EVENTNAME'", 'events-made-easy' );
	$task_cp_pending_email_body_localizable            = __( 'Approval required: #_PERSONFULLNAME (#_PERSONEMAIL) signed up for #_TASKNAME (#_TASKSTARTDATE) for #_EVENTNAME on #_STARTDATE.<br>Now there are #_FREETASKSPACES free spaces for this task.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$task_cp_email_subject_localizable                 = __( "New signup for task '#_TASKNAME' for '#_EVENTNAME'", 'events-made-easy' );
	$task_cp_email_body_localizable                    = __( '#_PERSONFULLNAME (#_PERSONEMAIL) signed up for #_TASKNAME (#_TASKSTARTDATE) for #_EVENTNAME on #_STARTDATE.<br>Now there are #_FREETASKSPACES free spaces for this task.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$task_cp_cancelled_email_subject_localizable       = __( "A task signup has been cancelled for '#_EVENTNAME'", 'events-made-easy' );
	$task_cp_cancelled_email_body_localizable          = __( '#_PERSONFULLNAME (#_PERSONEMAIL) has cancelled his signup for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME on #_STARTDATE.<br>Now there are #_FREETASKSPACES free spaces for this task.<br><br>Yours faithfully,<br>Events Manager', 'events-made-easy' );
	$task_signup_pending_email_subject_localizable     = __( "Signup for task '#_TASKNAME' for '#_EVENTNAME' is pending approval", 'events-made-easy' );
	$task_signup_pending_email_body_localizable        = __( 'Dear #_PERSONFULLNAME,<br><br>Your signup for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME is registered and pending approval.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$task_signup_email_subject_localizable             = __( "Signup for task '#_TASKNAME' for '#_EVENTNAME'", 'events-made-easy' );
	$task_signup_email_body_localizable                = __( 'Dear #_PERSONFULLNAME,<br><br>You have successfully signed up for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$task_signup_cancelled_email_subject_localizable   = __( "Signup for task '#_TASKNAME' for '#_EVENTNAME' cancelled", 'events-made-easy' );
	$task_signup_cancelled_email_body_localizable      = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to sign up for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME has been cancelled.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$task_signup_trashed_email_subject_localizable     = __( "Signup for task '#_TASKNAME' for '#_EVENTNAME' deleted", 'events-made-easy' );
	$task_signup_trashed_email_body_localizable        = __( 'Dear #_PERSONFULLNAME,<br><br>Your request to sign up for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME has been deleted.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	#$task_signup_updated_email_subject_localizable = __("Signup for task '#_TASKNAME' for '#_EVENTNAME' updated",'events-made-easy');
	#$task_signup_updated_email_body_localizable = __("Dear #_PERSONFULLNAME,<br><br>Your request to sign up for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME has been updated.<br><br>Yours faithfully,<br>#_CONTACTPERSON",'events-made-easy');
	$task_signup_reminder_email_subject_localizable = __( "Reminder: Signup for task '#_TASKNAME' for '#_EVENTNAME'", 'events-made-easy' );
	$task_signup_reminder_email_body_localizable    = __( 'Dear #_PERSONFULLNAME,<br><br>This is a reminder that you signed up for #_TASKNAME (#_TASKBEGIN) for #_EVENTNAME.<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
	$eme_bd_email_subject_localizable               = __( 'Happy birthday #_PERSONFIRSTNAME', 'events-made-easy' );
	$eme_bd_email_body_localizable                  = __( 'Hi #_PERSONFIRSTNAME,<br><br>Congratulations on your birthday!!!<br><br>From EME', 'events-made-easy' );

	$eme_options = [
		'eme_event_list_item_format'                      => '<li>#_STARTDATE - #_STARTTIME<br> #_LINKEDNAME<br>#_TOWN </li>',
		'eme_event_list_item_format_header'               => DEFAULT_EVENT_LIST_HEADER_FORMAT,
		'eme_cat_event_list_item_format_header'           => DEFAULT_CAT_EVENT_LIST_HEADER_FORMAT,
		'eme_event_list_item_format_footer'               => DEFAULT_EVENT_LIST_FOOTER_FORMAT,
		'eme_cat_event_list_item_format_footer'           => DEFAULT_CAT_EVENT_LIST_FOOTER_FORMAT,
		'eme_display_calendar_in_events_page'             => 0,
		'eme_display_events_in_events_page'               => 0,
		'eme_single_event_format'                         => '#_STARTDATE - #_STARTTIME<br>#_TOWN<br>#_NOTES<br>#_ADDBOOKINGFORM<br>#_MAP',
		'eme_event_page_title_format'                     => '#_EVENTNAME',
		'eme_event_html_title_format'                     => DEFAULT_EVENT_HTML_TITLE_FORMAT,
		'eme_show_period_monthly_dateformat'              => DEFAULT_SHOW_PERIOD_MONTHLY_DATEFORMAT,
		'eme_show_period_yearly_dateformat'               => DEFAULT_SHOW_PERIOD_YEARLY_DATEFORMAT,
		'eme_filter_form_format'                          => DEFAULT_FILTER_FORM_FORMAT,
		'eme_events_page_title'                           => DEFAULT_EVENTS_PAGE_TITLE,
		'eme_no_events_message'                           => __( 'No events', 'events-made-easy' ),
		'eme_form_required_field_string'                  => $eme_form_required_field_string_localizable,
		'eme_location_page_title_format'                  => '#_LOCATIONNAME',
		'eme_location_html_title_format'                  => DEFAULT_LOCATION_HTML_TITLE_FORMAT,
		'eme_location_baloon_format'                      => "<strong>#_LOCATIONNAME</strong><br>#_ADDRESS - #_TOWN<br><a href='#_LOCATIONPAGEURL'>Details</a>",
		'eme_location_map_icon'                           => '',
		'eme_location_event_list_item_format'             => DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT,
		'eme_location_no_events_message'                  => __( 'No events at this location', 'events-made-easy' ),
		'eme_single_location_format'                      => '#_ADDRESS<br>#_TOWN<br>#_DESCRIPTION #_MAP',
		'eme_page_access_denied'                          => __( 'Access denied!', 'events-made-easy' ),
		'eme_membership_login_required_string'            => __( 'You need to be logged in in order to be able to register for this membership.', 'events-made-easy' ),
		'eme_membership_unauth_attendance_msg'            => $eme_membership_unauth_attendance_msg_localizeable,
		'eme_membership_attendance_msg'                   => $eme_membership_attendance_msg_localizeable,
		'eme_members_show_people_info'                    => 0,
		'eme_ical_title_format'                           => '#_EVENTNAME',
		'eme_ical_description_format'                     => '#_NOTES',
		'eme_ical_location_format'                        => '#_LOCATIONNAME, #_ADDRESS, #_TOWN',
		'eme_rss_main_title'                              => get_bloginfo( 'title' ) . ' - ' . __( 'Events', 'events-made-easy' ),
		'eme_rss_main_description'                        => get_bloginfo( 'description' ) . ' - ' . __( 'Events', 'events-made-easy' ),
		'eme_rss_description_format'                      => '#_STARTDATE - #_STARTTIME <br> #_NOTES <br>#_LOCATIONNAME <br>#_ADDRESS <br>#_TOWN',
		'eme_rss_title_format'                            => '#_EVENTNAME',
		'eme_rss_show_pubdate'                            => 1,
		'eme_rss_pubdate_startdate'                       => 0,
		'eme_map_is_active'                               => true,
		'eme_map_zooming'                                 => true,
		'eme_indiv_zoom_factor'                           => 14,
		'eme_map_gesture_handling'                        => 0,
		'eme_seo_permalink'                               => true,
		'eme_permalink_events_prefix'                     => 'events',
		'eme_permalink_locations_prefix'                  => 'locations',
		'eme_permalink_categories_prefix'                 => '',
		'eme_permalink_calendar_prefix'                   => '',
		'eme_permalink_payments_prefix'                   => '',
		'eme_default_contact_person'                      => -1,
		'eme_honeypot_for_forms'                          => 1,
		'eme_captcha_only_logged_out'                     => 0,
		'eme_hcaptcha_for_forms'                          => 0,
		'eme_hcaptcha_site_key'                           => '',
		'eme_hcaptcha_secret_key'                         => '',
		'eme_recaptcha_for_forms'                         => 0,
		'eme_recaptcha_site_key'                          => '',
		'eme_recaptcha_secret_key'                        => '',
		'eme_cfcaptcha_for_forms'                         => 0,
		'eme_cfcaptcha_site_key'                          => '',
		'eme_cfcaptcha_secret_key'                        => '',
		'eme_captcha_for_forms'                           => 0,
		'eme_stay_on_edit_page'                           => 0,
		'eme_rsvp_mail_notify_is_active'                  => 1,
		'eme_rsvp_mail_notify_pending'                    => 1,
		'eme_rsvp_mail_notify_paid'                       => 0,
		'eme_rsvp_mail_notify_approved'                   => 1,
		'eme_rsvp_check_required_fields'                  => 1,
		'eme_contactperson_email_subject'                 => $contact_person_email_subject_localizable,
		'eme_contactperson_email_body'                    => eme_br2nl( $contact_person_email_body_localizable ),
		'eme_contactperson_cancelled_email_subject'       => $contactperson_cancelled_email_subject_localizable,
		'eme_contactperson_cancelled_email_body'          => eme_br2nl( $contactperson_cancelled_email_body_localizable ),
		'eme_contactperson_pending_email_subject'         => $contact_person_pending_email_subject_localizable,
		'eme_contactperson_pending_email_body'            => eme_br2nl( $contact_person_pending_email_body_localizable ),
		'eme_contactperson_ipn_email_subject'             => '',
		'eme_contactperson_ipn_email_body'                => '',
		'eme_contactperson_paid_email_subject'            => '',
		'eme_contactperson_paid_email_body'               => '',
		'eme_respondent_email_subject'                    => $respondent_email_subject_localizable,
		'eme_respondent_email_body'                       => eme_br2nl( $respondent_email_body_localizable ),
		'eme_registration_pending_email_subject'          => $registration_pending_email_subject_localizable,
		'eme_registration_pending_email_body'             => eme_br2nl( $registration_pending_email_body_localizable ),
		'eme_registration_userpending_email_subject'      => $registration_userpending_email_subject_localizable,
		'eme_registration_userpending_email_body'         => eme_br2nl( $registration_userpending_email_body_localizable ),
		'eme_registration_cancelled_email_subject'        => $registration_cancelled_email_subject_localizable,
		'eme_registration_cancelled_email_body'           => eme_br2nl( $registration_cancelled_email_body_localizable ),
		'eme_registration_trashed_email_subject'          => $registration_trashed_email_subject_localizable,
		'eme_registration_trashed_email_body'             => eme_br2nl( $registration_trashed_email_body_localizable ),
		'eme_registration_updated_email_subject'          => $registration_updated_email_subject_localizable,
		'eme_registration_updated_email_body'             => eme_br2nl( $registration_updated_email_body_localizable ),
		'eme_registration_paid_email_subject'             => $registration_paid_email_subject_localizable,
		'eme_registration_paid_email_body'                => eme_br2nl( $registration_paid_email_body_localizable ),
		'eme_registration_pending_reminder_email_subject' => $registration_pending_reminder_email_subject_localizable,
		'eme_registration_pending_reminder_email_body'    => eme_br2nl( $registration_pending_reminder_email_body_localizable ),
		'eme_registration_reminder_email_subject'         => $registration_reminder_email_subject_localizable,
		'eme_registration_reminder_email_body'            => eme_br2nl( $registration_reminder_email_body_localizable ),
		'eme_registration_recorded_ok_html'               => $registration_recorded_ok_html_localizable,
		'eme_registration_form_format'                    => $registration_form_format_localizable,
		'eme_cancel_form_format'                          => $cancel_form_format_localizable,
		'eme_cancel_payment_form_format'                  => $cancel_payment_form_format_localizable,
		'eme_cancel_payment_line_format'                  => $cancel_payment_line_format_localizable,
		'eme_cancelled_payment_format'                    => $cancelled_payment_format_localizable,
		'eme_tasks_enabled'                               => false,
		'eme_task_reminder_days'                          => '',
		'eme_rsvp_pending_reminder_days'                  => '',
		'eme_rsvp_approved_reminder_days'                 => '',
		'eme_task_registered_users_only'                  => 0,
		'eme_task_requires_approval'                      => 0,
		'eme_task_allow_overlap'                          => 0,
		'eme_task_form_taskentry_format'                  => '#_TASKSIGNUPCHECKBOX #_TASKNAME (#_TASKBEGIN - #_TASKEND) (#_FREETASKSPACES/#_TASKSPACES) <br>',
		'eme_task_form_format'                            => $task_form_format_localizable,
		'eme_task_signup_format'                          => '#_FULLNAME <br>',
		'eme_task_signup_recorded_ok_html'                => $task_signup_recorded_ok_html_localizable,
		'eme_cp_task_signup_pending_email_subject'        => $task_cp_pending_email_subject_localizable,
		'eme_cp_task_signup_pending_email_body'           => $task_cp_pending_email_body_localizable,
		'eme_cp_task_signup_email_subject'                => $task_cp_email_subject_localizable,
		'eme_cp_task_signup_email_body'                   => $task_cp_email_body_localizable,
		'eme_cp_task_signup_cancelled_email_subject'      => $task_cp_cancelled_email_subject_localizable,
		'eme_cp_task_signup_cancelled_email_body'         => $task_cp_cancelled_email_body_localizable,
		'eme_task_signup_pending_email_subject'           => $task_signup_pending_email_subject_localizable,
		'eme_task_signup_pending_email_body'              => $task_signup_pending_email_body_localizable,
		'eme_task_signup_email_subject'                   => $task_signup_email_subject_localizable,
		'eme_task_signup_email_body'                      => $task_signup_email_body_localizable,
		'eme_task_signup_cancelled_email_subject'         => $task_signup_cancelled_email_subject_localizable,
		'eme_task_signup_cancelled_email_body'            => $task_signup_cancelled_email_body_localizable,
		'eme_task_signup_trashed_email_subject'           => $task_signup_trashed_email_subject_localizable,
		'eme_task_signup_trashed_email_body'              => $task_signup_trashed_email_body_localizable,
		'eme_task_signup_reminder_email_subject'          => $task_signup_reminder_email_subject_localizable,
		'eme_task_signup_reminder_email_body'             => $task_signup_reminder_email_body_localizable,
		'eme_bd_email_subject'                            => $eme_bd_email_subject_localizable,
		'eme_bd_email_body'                               => $eme_bd_email_body_localizable,
		'eme_gdpr_remove_old_attendances_days'            => 0,
		'eme_gdpr_anonymize_expired_member_days'          => 0,
		'eme_gdpr_remove_expired_member_days'             => 0,
		'eme_gdpr_anonymize_old_bookings_days'            => 0,
		'eme_gdpr_remove_old_events_days'                 => 0,
		'eme_gdpr_archive_old_mailings_days'              => 0,
		'eme_gdpr_remove_old_signups_days'                => 0,
		'eme_gdpr_page_header'                            => '',
		'eme_gdpr_page_footer'                            => '',
		'eme_gdpr_page_title'                             => $eme_gdpr_page_title_localizable,
		'eme_gdpr_subject'                                => $eme_gdpr_subject_localizable,
		'eme_gdpr_body'                                   => $eme_gdpr_body_localizable,
		'eme_gdpr_approve_page_title'                     => $eme_gdpr_approve_page_title_localizable,
		'eme_gdpr_approve_page_content'                   => $eme_gdpr_approve_page_content_localizable,
		'eme_gdpr_approve_subject'                        => $eme_gdpr_approve_subject_localizable,
		'eme_gdpr_approve_body'                           => $eme_gdpr_approve_body_localizable,
		'eme_cpi_subject'                                 => $eme_cpi_subject_localizable,
		'eme_cpi_body'                                    => $eme_cpi_body_localizable,
		'eme_cpi_form'                                    => $eme_cpi_form_localizable,
		'eme_sub_subject'                                 => $eme_sub_subject_localizable,
		'eme_sub_body'                                    => $eme_sub_body_localizable,
		'eme_unsub_subject'                               => $eme_unsub_subject_localizable,
		'eme_unsub_body'                                  => $eme_unsub_body_localizable,
		'eme_cancel_rsvp_days'                            => 0,
		'eme_cancel_rsvp_age'                             => 0,
		'eme_rsvp_check_without_accents'                  => 0,
		'eme_smtp_host'                                   => 'localhost',
		'eme_smtp_port'                                   => 25,
		'eme_smtp_encryption'                             => '',
		'eme_smtp_verify_cert'                            => 1,
		'eme_smtp_auth'                                   => 0,
		'eme_smtp_username'                               => '',
		'eme_smtp_password'                               => '',
		'eme_mail_sender_name'                            => '',
		'eme_mail_sender_address'                         => '',
		'eme_mail_force_from'                             => 0,
		'eme_mail_bcc_address'                            => '',
		'eme_mail_send_method'                            => 'wp_mail',
		'eme_mail_send_html'                              => 1,
		'eme_rsvp_registered_users_only'                  => 0,
		'eme_rsvp_reg_for_new_events'                     => 0,
		'eme_rsvp_default_number_spaces'                  => 10,
		'eme_rsvp_require_approval'                       => 0,
		'eme_rsvp_require_user_confirmation'              => 0,
		'eme_rsvp_show_form_after_booking'                => 0,
		'eme_rsvp_hide_full_events'                       => 0,
		'eme_rsvp_hide_rsvp_ended_events'                 => 0,
		'eme_rsvp_admin_allow_overbooking'                => 0,
		'eme_attendees_list_format'                       => '<li>#_PERSONFULLNAME (#_ATTENDSEATS)</li>',
		'eme_attendees_list_ignore_pending'               => 0,
		'eme_bookings_list_format'                        => '<li>#_PERSONFULLNAME (#_RESPSEATS)</li>',
		'eme_bookings_list_ignore_pending'                => 0,
		'eme_bookings_list_header_format'                 => DEFAULT_BOOKINGS_LIST_HEADER_FORMAT,
		'eme_bookings_list_footer_format'                 => DEFAULT_BOOKINGS_LIST_FOOTER_FORMAT,
		'eme_full_calendar_event_format'                  => '<li>#_LINKEDNAME</li>',
		'eme_small_calendar_event_title_format'           => '#_EVENTNAME',
		'eme_small_calendar_event_title_separator'        => ', ',
		'eme_cal_hide_past_events'                        => 0,
		'eme_cal_show_single'                             => 1,
		'eme_smtp_debug'                                  => 0,
		'eme_shortcodes_in_widgets'                       => 0,
		'eme_load_js_in_header'                           => 0,
		'eme_use_client_clock'                            => 0,
		'eme_event_list_number_items'                     => 10,
		'eme_use_select_for_locations'                    => false,
		'eme_attributes_enabled'                          => false,
		'eme_rsvp_enabled'                                => true,
		'eme_rsvp_addbooking_submit_string'               => __( 'Send your booking', 'events-made-easy' ),
		'eme_rsvp_addbooking_min_spaces'                  => 1,
		'eme_rsvp_addbooking_max_spaces'                  => 10,
		'eme_rsvp_delbooking_submit_string'               => __( 'Cancel your booking', 'events-made-easy' ),
		'eme_address1_string'                             => $eme_address1_localizable,
		'eme_address2_string'                             => $eme_address2_localizable,
		'eme_rsvp_not_yet_allowed_string'                 => $eme_rsvp_not_yet_allowed_localizable,
		'eme_rsvp_no_longer_allowed_string'               => $eme_rsvp_no_longer_allowed_localizable,
		'eme_rsvp_cancel_no_longer_allowed_string'        => $eme_rsvp_cancel_no_longer_allowed_localizable,
		'eme_rsvp_full_string'                            => $eme_rsvp_full_localizable,
		'eme_rsvp_on_waiting_list_string'                 => $eme_rsvp_on_waiting_list_localizable,
		'eme_rsvp_login_required_string'                  => __( 'You need to be logged in in order to be able to register for this event.', 'events-made-easy' ),
		'eme_rsvp_invitation_required_string'             => __( 'You need to be invited for this event in order to be able to register.', 'events-made-easy' ),
		'eme_rsvp_email_already_registered_string'        => __( 'This email has already registered.', 'events-made-easy' ),
		'eme_rsvp_person_already_registered_string'       => __( 'This person has already registered.', 'events-made-easy' ),
		'eme_categories_enabled'                          => true,
		'eme_cap_list_events'                             => DEFAULT_CAP_LIST_EVENTS,
		'eme_cap_add_event'                               => DEFAULT_CAP_ADD_EVENT,
		'eme_cap_author_event'                            => DEFAULT_CAP_AUTHOR_EVENT,
		'eme_cap_publish_event'                           => DEFAULT_CAP_PUBLISH_EVENT,
		'eme_cap_edit_events'                             => DEFAULT_CAP_EDIT_EVENTS,
		'eme_cap_manage_task_signups'                     => DEFAULT_CAP_MANAGE_TASK_SIGNUPS,
		'eme_cap_list_locations'                          => DEFAULT_CAP_LIST_LOCATIONS,
		'eme_cap_add_locations'                           => DEFAULT_CAP_ADD_LOCATION,
		'eme_cap_author_locations'                        => DEFAULT_CAP_AUTHOR_LOCATION,
		'eme_cap_edit_locations'                          => DEFAULT_CAP_EDIT_LOCATIONS,
		'eme_cap_categories'                              => DEFAULT_CAP_CATEGORIES,
		'eme_cap_holidays'                                => DEFAULT_CAP_HOLIDAYS,
		'eme_cap_templates'                               => DEFAULT_CAP_TEMPLATES,
		'eme_cap_access_people'                           => DEFAULT_CAP_ACCESS_PEOPLE,
		'eme_cap_list_people'                             => DEFAULT_CAP_EDIT_PEOPLE,
		'eme_cap_edit_people'                             => DEFAULT_CAP_EDIT_PEOPLE,
		'eme_cap_author_person'                           => DEFAULT_CAP_AUTHOR_PERSON,
		'eme_cap_access_members'                          => DEFAULT_CAP_ACCESS_MEMBERS,
		'eme_cap_list_members'                            => DEFAULT_CAP_EDIT_MEMBERS,
		'eme_cap_edit_members'                            => DEFAULT_CAP_EDIT_MEMBERS,
		'eme_cap_author_member'                           => DEFAULT_CAP_AUTHOR_MEMBER,
		'eme_cap_discounts'                               => DEFAULT_CAP_DISCOUNTS,
		'eme_cap_list_approve'                            => DEFAULT_CAP_LIST_APPROVE,
		'eme_cap_author_approve'                          => DEFAULT_CAP_AUTHOR_APPROVE,
		'eme_cap_approve'                                 => DEFAULT_CAP_APPROVE,
		'eme_cap_list_registrations'                      => DEFAULT_CAP_LIST_REGISTRATIONS,
		'eme_cap_author_registrations'                    => DEFAULT_CAP_AUTHOR_REGISTRATIONS,
		'eme_cap_registrations'                           => DEFAULT_CAP_REGISTRATIONS,
		'eme_cap_attendancecheck'                         => DEFAULT_CAP_ATTENDANCECHECK,
		'eme_cap_membercheck'                             => DEFAULT_CAP_MEMBERCHECK,
		'eme_cap_forms'                                   => DEFAULT_CAP_FORMS,
		'eme_cap_cleanup'                                 => DEFAULT_CAP_CLEANUP,
		'eme_cap_settings'                                => DEFAULT_CAP_SETTINGS,
		'eme_cap_send_mails'                              => DEFAULT_CAP_SEND_MAILS,
		'eme_cap_send_other_mails'                        => DEFAULT_CAP_SEND_OTHER_MAILS,
		'eme_cap_send_generic_mails'                      => DEFAULT_CAP_SEND_GENERIC_MAILS,
		'eme_cap_view_mails'                              => DEFAULT_CAP_VIEW_MAILS,
		'eme_cap_manage_mails'                            => DEFAULT_CAP_MANAGE_MAILS,
		'eme_cap_list_attendances'                        => DEFAULT_CAP_LIST_ATTENDANCES,
		'eme_cap_manage_attendances'                      => DEFAULT_CAP_MANAGE_ATTENDANCES,
		'eme_limit_admin_event_listing'			  => 0,
		'eme_html_header'                                 => '',
		'eme_html_footer'                                 => '',
		'eme_event_html_headers_format'                   => '',
		'eme_location_html_headers_format'                => '',
		'eme_offline_payment'                             => '',
		'eme_legacypaypal_url'                            => 'live',
		'eme_legacypaypal_business'                       => '',
		'eme_legacypaypal_no_tax'                         => 0,
		'eme_legacypaypal_cost'                           => 0,
		'eme_legacypaypal_cost2'                          => 0,
		'eme_legacypaypal_button_label'                   => sprintf( $eme_payment_button_label_localizable, 'Paypal' ),
		'eme_legacypaypal_button_img_url'                 => '',
		'eme_legacypaypal_button_above'                   => sprintf( $eme_payment_button_above_localizable, 'Paypal' ),
		'eme_legacypaypal_button_below'                   => '',
		'eme_paypal_url'                                  => 'live',
		'eme_paypal_clientid'                             => '',
		'eme_paypal_secret'                               => '',
		'eme_paypal_cost'                                 => 0,
		'eme_paypal_cost2'                                => 0,
		'eme_paypal_button_label'                         => sprintf( $eme_payment_button_label_localizable, 'Paypal' ),
		'eme_paypal_button_img_url'                       => '',
		'eme_paypal_button_above'                         => sprintf( $eme_payment_button_above_localizable, 'Paypal' ),
		'eme_paypal_button_below'                         => '',
		'eme_webmoney_demo'                               => 0,
		'eme_webmoney_purse'                              => '',
		'eme_webmoney_secret'                             => '',
		'eme_webmoney_cost'                               => 0,
		'eme_webmoney_cost2'                              => 0,
		'eme_webmoney_button_label'                       => sprintf( $eme_payment_button_label_localizable, 'Webmoney' ),
		'eme_webmoney_button_img_url'                     => '',
		'eme_webmoney_button_above'                       => sprintf( $eme_payment_button_above_localizable, 'Webmoney' ),
		'eme_webmoney_button_below'                       => '',
		'eme_worldpay_demo'                               => 1,
		'eme_worldpay_instid'                             => '',
		'eme_worldpay_md5_secret'                         => '',
		'eme_worldpay_md5_parameters'                     => 'instId:cartId:currency:amount',
		'eme_worldpay_test_pwd'                           => '',
		'eme_worldpay_live_pwd'                           => '',
		'eme_worldpay_cost'                               => 0,
		'eme_worldpay_cost2'                              => 0,
		'eme_worldpay_button_label'                       => sprintf( $eme_payment_button_label_localizable, 'Worldpay' ),
		'eme_worldpay_button_img_url'                     => '',
		'eme_worldpay_button_above'                       => sprintf( $eme_payment_button_above_localizable, 'Worldpay' ),
		'eme_worldpay_button_below'                       => '',
		'eme_braintree_private_key'                       => '',
		'eme_braintree_public_key'                        => '',
		'eme_braintree_merchant_id'                       => '',
		'eme_braintree_env'                               => 'production',
		'eme_braintree_cost'                              => 0,
		'eme_braintree_cost2'                             => 0,
		'eme_braintree_button_label'                      => sprintf( $eme_payment_button_label_localizable, 'Braintree' ),
		'eme_braintree_button_img_url'                    => '',
		'eme_braintree_button_above'                      => sprintf( $eme_payment_button_above_localizable, 'Braintree' ),
		'eme_braintree_button_below'                      => '',
		'eme_instamojo_env'                               => 'sandbox',
		'eme_instamojo_key'                               => '',
		'eme_instamojo_auth_token'                        => '',
		'eme_instamojo_salt'                              => '',
		'eme_instamojo_cost'                              => 0,
		'eme_Instamojo_cost2'                             => 0,
		'eme_instamojo_button_label'                      => sprintf( $eme_payment_button_label_localizable, 'Instamojo' ),
		'eme_instamojo_button_img_url'                    => '',
		'eme_instamojo_button_above'                      => sprintf( $eme_payment_button_above_localizable, 'Instamojo' ),
		'eme_instamojo_button_below'                      => '',
		'eme_sumup_merchant_code'                         => '',
		'eme_sumup_app_id'                                => '',
		'eme_sumup_app_secret'                            => '',
		'eme_sumup_cost'                                  => 0,
		'eme_sumup_cost2'                                 => 0,
		'eme_sumup_button_label'                          => sprintf( $eme_payment_button_label_localizable, 'SumUp' ),
		'eme_sumup_button_img_url'                        => '',
		'eme_sumup_button_above'                          => sprintf( $eme_payment_button_above_localizable, 'SumUp' ),
		'eme_sumup_button_below'                          => '',
		'eme_stripe_private_key'                          => '',
		'eme_stripe_public_key'                           => '',
		'eme_stripe_cost'                                 => 0,
		'eme_stripe_cost2'                                => 0,
		'eme_stripe_button_label'                         => sprintf( $eme_payment_button_label_localizable, 'Stripe' ),
		'eme_stripe_button_img_url'                       => '',
		'eme_stripe_button_above'                         => sprintf( $eme_payment_button_above_localizable, 'Stripe' ),
		'eme_stripe_button_below'                         => '',
		'eme_stripe_payment_methods'                      => 'card',
		'eme_fdgg_url'                                    => 'live',
		'eme_fdgg_store_name'                             => '',
		'eme_fdgg_shared_secret'                          => '',
		'eme_fdgg_cost'                                   => 0,
		'eme_fdgg_cost2'                                  => 0,
		'eme_fdgg_button_label'                           => sprintf( $eme_payment_button_label_localizable, 'First Data' ),
		'eme_fdgg_button_img_url'                         => '',
		'eme_fdgg_button_above'                           => sprintf( $eme_payment_button_above_localizable, 'First Data' ),
		'eme_fdgg_button_below'                           => '',
		'eme_opayo_demo'                                  => 1,
		'eme_opayo_vendor_name'                           => '',
		'eme_opayo_test_pwd'                              => '',
		'eme_opayo_live_pwd'                              => '',
		'eme_opayo_cost'                                  => 0,
		'eme_opayo_cost2'                                 => 0,
		'eme_opayo_button_label'                          => sprintf( $eme_payment_button_label_localizable, 'Opayo' ),
		'eme_opayo_button_img_url'                        => '',
		'eme_opayo_button_above'                          => sprintf( $eme_payment_button_above_localizable, 'Opayo' ),
		'eme_opayo_button_below'                          => '',
		'eme_mollie_api_key'                              => '',
		'eme_mollie_cost'                                 => 0,
		'eme_mollie_cost2'                                => 0,
		'eme_mollie_button_label'                         => sprintf( $eme_payment_button_label_localizable, 'Mollie' ),
		'eme_mollie_button_img_url'                       => '',
		'eme_mollie_button_above'                         => sprintf( $eme_payment_button_above_localizable, 'Mollie' ),
		'eme_mollie_button_below'                         => __( 'Using Mollie, you can pay using one of the following methods:', 'events-made-easy' ) . '<br>',
		'eme_payconiq_api_key'                            => '',
		'eme_payconiq_env'                                => '',
		'eme_payconiq_merchant_id'                        => '',
		'eme_payconiq_cost'                               => 0,
		'eme_payconiq_cost2'                              => 0,
		'eme_payconiq_button_label'                       => sprintf( $eme_payment_button_label_localizable, 'Payconiq' ),
		'eme_payconiq_button_img_url'                     => esc_url(EME_PLUGIN_URL) . 'images/payment_gateways/payconiq/logo.png',
		'eme_payconiq_button_above'                       => sprintf( $eme_payment_button_above_localizable, 'Payconiq' ),
		'eme_payconiq_button_below'                       => '',
		'eme_mercadopago_demo'                            => 1,
		'eme_mercadopago_sandbox_token'                   => '',
		'eme_mercadopago_live_token'                      => '',
		'eme_mercadopago_cost'                            => 0,
		'eme_mercadopago_cost2'                           => 0,
		'eme_mercadopago_button_label'                    => sprintf( $eme_payment_button_label_localizable, 'Mercado Pago' ),
		'eme_mercadopago_button_img_url'                  => '',
		'eme_mercadopago_button_above'                    => sprintf( $eme_payment_button_above_localizable, 'Mercado Pago' ),
		'eme_mercadopago_button_below'                    => '',
		'eme_fondy_merchant_id'                           => '',
		'eme_fondy_secret_key'                            => '',
		'eme_fondy_cost'                                  => 0,
		'eme_fondy_cost2'                                 => 0,
		'eme_fondy_button_label'                          => sprintf( $eme_payment_button_label_localizable, 'Fondy' ),
		'eme_fondy_button_img_url'                        => '',
		'eme_fondy_button_above'                          => sprintf( $eme_payment_button_above_localizable, 'Fondy' ),
		'eme_fondy_button_below'                          => '',
		'eme_event_initial_state'                         => EME_EVENT_STATUS_DRAFT,
		'eme_use_external_url'                            => 1,
		'eme_bd_email'                                    => 0,
		'eme_bd_email_members_only'                       => 0,
		'eme_default_currency'                            => 'EUR',
		'eme_default_vat'                                 => '0',
		'eme_default_price'                               => '0',
		'eme_payment_refund_ok'                           => 1,
		'eme_pg_submit_immediately'                       => 0,
		'eme_payment_redirect'                            => 1,
		'eme_payment_redirect_wait'                       => 5,
		'eme_payment_redirect_msg'                        => $eme_payment_redirect_msg_localizable,
		'eme_rsvp_start_target'                           => 'start',
		'eme_rsvp_start_number_days'                      => 0,
		'eme_rsvp_start_number_hours'                     => 0,
		'eme_rsvp_end_target'                             => 'start',
		'eme_rsvp_end_number_days'                        => 0,
		'eme_rsvp_end_number_hours'                       => 0,
		'eme_thumbnail_size'                              => 'thumbnail',
		'eme_payment_form_header_format'                  => '',
		'eme_payment_form_footer_format'                  => '',
		'eme_multipayment_form_header_format'             => '',
		'eme_multipayment_form_footer_format'             => '',
		'eme_payment_succes_format'                       => __( 'Payment success for your booking for #_EVENTNAME', 'events-made-easy' ),
		'eme_payment_fail_format'                         => __( 'Payment failed for your booking for #_EVENTNAME', 'events-made-easy' ),
		'eme_payment_member_succes_format'                => __( 'Payment success for your membership signup for #_MEMBERSHIPNAME', 'events-made-easy' ),
		'eme_payment_member_fail_format'                  => __( 'Payment failed for your membership signup for #_MEMBERSHIPNAME', 'events-made-easy' ),
		'eme_payment_booking_already_paid_format'         => __( 'This has already been paid for', 'events-made-easy' ),
		'eme_payment_booking_on_waitinglist_format'       => __( 'This booking is on a waitinglist, payment is not possible.', 'events-made-easy' ),
		'eme_enable_notes_placeholders'                   => 0,
		'eme_uninstall_drop_data'                         => 0,
		'eme_uninstall_drop_settings'                     => 0,
		'eme_csv_separator'                               => ';',
		'eme_decimals'                                    => 2,
		'eme_timepicker_minutesstep'                      => 5,
		'eme_localize_price'                              => 1,
		'eme_autocomplete_sources'                        => 'none',
		'eme_cron_cleanup_unpaid_minutes'                 => 0,
		'eme_cron_cleanup_unconfirmed_minutes'            => 0,
		'eme_cron_queue_count'                            => 50,
		'eme_mail_sleep'                                  => 0,
		'eme_queue_mails'                                 => 1,
		'eme_people_newsletter'                           => 1,
		'eme_people_massmail'                             => 0,
		'eme_massmail_popup'                              => 1,
		'eme_massmail_popup_text'                         => __( 'You selected to not receive future mails. Are you sure about this?', 'events-made-easy' ),
		'eme_add_events_locs_link_search'                 => 0,
		'eme_booking_attach_ids'                          => '',
		'eme_pending_attach_ids'                          => '',
		'eme_paid_attach_ids'                             => '',
		'eme_booking_attach_tmpl_ids'                     => '',
		'eme_pending_attach_tmpl_ids'                     => '',
		'eme_paid_attach_tmpl_ids'                        => '',
		'eme_subscribe_attach_ids'                        => '',
		'eme_allowed_html'                                => '',
		'eme_allowed_style_attr'                          => '',
		'eme_redir_priv_event_url'                        => '',
		'eme_redir_protected_pages_url'                   => '',
		'eme_full_name_format'                            => '#_FIRSTNAME #_LASTNAME',
		'eme_pdf_font'                                    => 'dejavu sans',
		'eme_frontend_nocache'                            => 0,
		'eme_use_is_page_for_title'                       => 0,
		'eme_mail_tracking'                               => 0,
		'eme_mail_blacklist'                              => '',
		'eme_backend_dateformat'                          => '',
		'eme_backend_timeformat'                          => '',
		'eme_check_free_waiting'                          => 0,
		'eme_multisite_active'                            => 0,
		'eme_rememberme'                                  => 0,
	];

	foreach ( $eme_options as $key => $value ) {
		eme_add_option( $key, $value, $reset );
	}
}

function eme_update_options( $db_version ) {
	if ( $db_version ) {
		if ( $db_version > 0 && $db_version < 49 ) {
			delete_option( 'eme_events_admin_limit' );
		}
		if ( $db_version > 0 && $db_version < 55 ) {
			$smtp_port = get_option( 'eme_rsvp_mail_port' );
			delete_option( 'eme_rsvp_mail_port' );
			update_option( 'eme_smtp_port', $smtp_port );
		}
		if ( $db_version < 70 ) {
			delete_option( 'eme_google_checkout_type' );
			delete_option( 'eme_google_merchant_id' );
			delete_option( 'eme_google_merchant_key' );
			delete_option( 'eme_google_cost' );
		}
		if ( $db_version < 105 ) {
			delete_option( 'eme_phpold' );
			delete_option( 'eme_conversion_needed' );
		}
		if ( $db_version < 119 ) {
			// remove some deprecated options
			$options = [ 'eme_image_max_width', 'eme_image_max_height', 'eme_image_max_size', 'eme_legacy', 'eme_deprecated', 'eme_legacy_warning', 'eme_list_events_page', 'eme_deny_mail_event_edit', 'eme_fb_app_id' ];
			foreach ( $options as $opt ) {
				delete_option( $opt );
			}
			// rename some options
			$rename_options = [
				'eme_cron_reminder_unpayed_minutes' => 'eme_cron_reminder_unpaid_minutes',
				'eme_cron_cleanup_unpayed_minutes'  => 'eme_cron_cleanup_unpaid_minutes',
				'eme_cron_reminder_unpayed_subject' => 'eme_cron_reminder_unpaid_subject',
				'eme_cron_reminder_unpayed_body'    => 'eme_cron_reminder_unpaid_body',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
			// remove old schedule
			if ( wp_next_scheduled( 'eme_cron_cleanup_unpayed' ) ) {
				wp_clear_scheduled_hook( 'eme_cron_cleanup_unpayed' );
			}
		}
		if ( $db_version < 127 ) {
			delete_option( 'eme_calc_price_dynamically' );
		}
		if ( $db_version < 157 ) {
			delete_option( 'eme_rsvp_force_nl2br' );
		}
		if ( $db_version < 164 ) {
			delete_option( 'eme_payment_add_bookingid_to_return' );
		}
		if ( $db_version < 184 ) {
			delete_option( 'eme_payment_show_custom_return_page' );
		}
		if ( $db_version < 197 ) {
			// rename some options
			$rename_options = [
				'eme_rsvp_required_field_string' => 'eme_form_required_field_string',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 206 ) {
			delete_option( 'eme_gmap_api_key' );
			$rename_options = [
				'eme_gmap_active'  => 'eme_map_active',
				'eme_gmap_zooming' => 'eme_map_zooming',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 210 ) {
			delete_option( 'eme_clean_session_data' );
		}
		if ( $db_version < 211 ) {
			delete_option( 'eme_captcha_no_case' );
		}
		if ( $db_version < 218 ) {
			delete_option( 'eme_global_zoom_factor' );
		}
		if ( $db_version < 229 ) {
			delete_option( 'eme_paypal_s_encrypt' );
			delete_option( 'eme_paypal_s_paypalcert' );
			delete_option( 'eme_paypal_s_pubcert' );
			delete_option( 'eme_paypal_s_privkey' );
			delete_option( 'eme_paypal_s_certid' );
			delete_option( 'eme_paypal_business' );
			delete_option( 'eme_paypal_no_tax' );
		}
		if ( $db_version < 234 ) {
			delete_option( 'eme_cancel_payment_header_format' );
			delete_option( 'eme_cancel_payment_footer_format' );
			delete_option( 'eme_cancelled_payment_header_format' );
			delete_option( 'eme_cancelled_payment_line_format' );
			delete_option( 'eme_cancelled_payment_footer_format' );
			$cancel_payment_form_format_localizable = __( "You're about to cancel the following bookings:", 'events-made-easy' ) . '<br>#_CANCEL_PAYMENT_LINE #_SUBMIT';
			update_option( 'eme_cancel_payment_form_format', $cancel_payment_form_format_localizable );
		}
		if ( $db_version < 241 ) {
			delete_option( 'eme_stripe_data_img_url' );
		}
		if ( $db_version < 242 ) {
			delete_option( 'eme_loop_protection' );
		}
		if ( $db_version < 247 ) {
			// rename some options
			$rename_options = [
				'eme_registration_denied_email_subject' => 'eme_registration_trashed_email_subject',
				'eme_registration_denied_email_body'    => 'eme_registration_trashed_email_body',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 250 ) {
			// rename some options
			$rename_options = [
				'eme_cap_people'  => 'eme_cap_edit_people',
				'eme_cap_members' => 'eme_cap_edit_members',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 278 ) {
			delete_option( 'eme_ical_quote_tzid' );
		}
		if ( $db_version < 289 ) {
			delete_option( 'eme_always_queue_mails' );
		}
		if ( $db_version < 298 ) {
			// rename some options
			$rename_options = [
				'eme_captcha_for_booking' => 'eme_captcha_for_forms',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
			delete_option( 'eme_donation_done' );
			delete_option( 'eme_hello_to_user' );
		}
		if ( $db_version < 298 ) {
			delete_option( 'eme_categories_separator' );
			delete_option( 'eme_categorydescriptions_separator' );
			delete_option( 'eme_linkedcategories_separator' );
			delete_option( 'eme_global_maptype' );
			delete_option( 'eme_indiv_maptype' );

		}
		if ( $db_version < 304 ) {
			delete_option( 'eme_recurrence_enabled' );
		}
		if ( $db_version < 306 ) {
			// rename some options
			$rename_options = [
				'eme_mail_recipient_format' => 'eme_full_name_format',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 309 ) {
			update_option( 'eme_full_name_format', '#_FIRSTNAME #_LASTNAME' );
			$eme_cpi_body_localizable = __( 'Hi,<br><br>Please find below the info needed to change the personal info for each matching person<br>#_CHANGE_PERSON_INFO<br><br>Yours faithfully,<br>#_CONTACTPERSON', 'events-made-easy' );
			update_option( 'eme_cpi_body', $eme_cpi_body_localizable );
		}
		if ( $db_version < 315 ) {
			// rename some options
			$rename_options = [
				'eme_gdpr_remove_old_bookings_days' => 'eme_gdpr_anonymize_old_bookings_days',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 327 ) {
			delete_option( 'eme_disable_wpautop' );
		}
		if ( $db_version < 338 ) {
			if ( wp_next_scheduled( 'eme_cron_reminder_unpaid' ) ) {
				wp_unschedule_hook( 'eme_cron_reminder_unpaid' );
			}
			delete_option( 'eme_cron_reminder_unpaid_minutes' );
			delete_option( 'eme_cron_reminder_unpaid_subject' );
			delete_option( 'eme_cron_reminder_unpaid_body' );
		}
		if ( $db_version < 340 ) {
			update_option( 'eme_cap_access_people', get_option( 'eme_cap_list_people' ) );
			update_option( 'eme_cap_access_members', get_option( 'eme_cap_list_members' ) );
		}
		if ( $db_version < 347 ) {
			delete_option( 'eme_paymill_demo' );
			delete_option( 'eme_paymill_private_key' );
			delete_option( 'eme_paymill_public_key' );
			delete_option( 'eme_paymill_private_test_key' );
			delete_option( 'eme_paymill_public_test_key' );
			delete_option( 'eme_paymill_cost' );
			delete_option( 'eme_paymill_cost2' );
			delete_option( 'eme_paymill_button_label' );
			delete_option( 'eme_paymill_button_img_url' );
			delete_option( 'eme_paymill_button_above' );
			delete_option( 'eme_paymill_button_below' );
			delete_option( 'eme_sagepay_demo' );
			delete_option( 'eme_sagepay_vendor_name' );
			delete_option( 'eme_sagepay_test_pwd' );
			delete_option( 'eme_sagepay_live_pwd' );
			delete_option( 'eme_sagepay_cost' );
			delete_option( 'eme_sagepay_cost2' );
			delete_option( 'eme_sagepay_button_label' );
			delete_option( 'eme_sagepay_button_img_url' );
			delete_option( 'eme_sagepay_button_above' );
			delete_option( 'eme_sagepay_button_below' );
		}
		if ( $db_version < 361 ) {
			if ( ! empty( get_option( 'eme_mail_sender_address' ) ) ) {
				update_option( 'eme_mail_force_from', 1 );
			}
		}
		if ( $db_version < 366 ) {
			if ( get_option( 'eme_payconiq_button_img_url' ) == 'images/payment_gateways/payconiq/logo.png') {
				update_option( 'eme_payconiq_button_img_url', esc_url(EME_PLUGIN_URL) . 'images/payment_gateways/payconiq/logo.png' );
			}
		}
		if ( $db_version < 379 ) {
			$to_delete_options = [
				'eme_2co_demo',
				'eme_2co_secret',
				'eme_2co_business',
				'eme_2co_cost',
				'eme_2co_cost',
				'eme_2co_button_label',
				'eme_2co_button_img_url',
				'eme_2co_button_above',
				'eme_2co_button_below'
			];
			foreach ( $to_delete_options as $to_delete_option ) {
				delete_option( $to_delete_option );
			}
			// rename some options
			$rename_options = [
				'eme_rsvp_number_days' => 'eme_rsvp_end_number_days',
				'eme_rsvp_number_hours' => 'eme_rsvp_end_number_hours',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
		}
		if ( $db_version < 388 ) {
			// rename some options
			$rename_options = [
				'eme_rsvp_mail_SMTPAuth' => 'eme_smtp_auth',
				'eme_rsvp_mail_send_method' => 'eme_mail_send_method',
				'eme_rsvp_send_html' => 'eme_mail_send_html',
			];
			foreach ( $rename_options as $old_option => $new_option ) {
				if ( get_option( $old_option ) ) {
					update_option( $new_option, get_option( $old_option ) );
					delete_option( $old_option );
				}
			}
			delete_option('eme_unique_email_per_person');
		}
	}
	// make sure the captcha doesn't cause problems
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		update_option( 'eme_captcha_for_forms', 0 );
	}

	// always reset the drop data option
	update_option( 'eme_uninstall_drop_data', 0 );
	update_option( 'eme_uninstall_drop_settings', 0 );
}

function eme_add_option( $key, $value, $reset ) {
	$option_val = get_option( $key, 'non_existing' );
	if ( $option_val == 'non_existing' || $reset ) {
		update_option( $key, $value );
	}
}

////////////////////////////////////
// WP options registration/deletion
////////////////////////////////////
function eme_options_delete() {
	$all_options = wp_load_alloptions();
	foreach ( array_keys($all_options) as $name ) {
		if ( preg_match( '/^eme_/', $name ) ) {
			delete_option( $name );
		}
	}
}

function eme_options_postsave_actions() {
	$tab = isset( $_GET['tab'] ) ? eme_sanitize_request( $_GET['tab'] ) : 'general';
	// if we saved settings on the payments tab, certain webhooks need to be created
	if ( $tab == 'payments' ) {
		eme_stripe_webhook();
	}

	// make sure the permalink settings are ok
	$eme_flush_rewrite_rules = 0;
	$events_prefixes         = explode( ',', get_option( 'eme_permalink_events_prefix', 'events' ) );
	$e_converted             = array_map( 'eme_permalink_convert_noslash', $events_prefixes );
	if ( $e_converted != $events_prefixes ) {
		update_option( 'eme_permalink_events_prefix', join( ',', $e_converted ) );
		$eme_flush_rewrite_rules = 1;
	}

	$locations_prefixes = explode( ',', get_option( 'eme_permalink_locations_prefix', 'locations' ) );
	$l_converted        = array_map( 'eme_permalink_convert_noslash', $locations_prefixes );
	if ( $l_converted != $locations_prefixes ) {
		update_option( 'eme_permalink_locations_prefix', join( ',', $l_converted ) );
		$eme_flush_rewrite_rules = 1;
	}

	$categories_prefixes = explode( ',', get_option( 'eme_permalink_categories_prefix', '' ) );
	$c_converted         = array_map( 'eme_permalink_convert_noslash', $categories_prefixes );
	if ( $c_converted != $categories_prefixes ) {
		update_option( 'eme_permalink_categories_prefix', join( ',', $c_converted ) );
		$eme_flush_rewrite_rules = 1;
	}

	$cal_prefix  = get_option( 'eme_permalink_calendar_prefix', '' );
	$c_converted = eme_permalink_convert_noslash( $cal_prefix );
	if ( $c_converted != $cal_prefix ) {
		update_option( 'eme_permalink_calendar_prefix', $c_converted );
		$eme_flush_rewrite_rules = 1;
	}
	$payments_prefix = get_option( 'eme_permalink_payments_prefix', '' );
	$c_converted     = eme_permalink_convert_noslash( $payments_prefix );
	if ( $c_converted != $payments_prefix ) {
		update_option( 'eme_permalink_payments_prefix', $c_converted );
		$eme_flush_rewrite_rules = 1;
	}
	// flush the rewrite rules if needed
	if ( $tab == 'seo' || $eme_flush_rewrite_rules ) {
		flush_rewrite_rules();
	}

	if ( get_option( 'eme_mail_force_from' ) && ! eme_is_email( get_option( 'eme_mail_sender_address' ) ) ) {
		update_option( 'eme_mail_force_from', 0 );
		update_option( 'eme_mail_sender_address', '' );
	}

	// make sure queue sending is configured properly
	eme_plan_queue_mails();

	// some extra checks
	if ( $tab == 'gdpr' ) {
		$remove_expired_days = get_option( 'eme_gdpr_remove_expired_member_days' );
		$anonymize_expired_days = get_option( 'eme_gdpr_anonymize_expired_member_days' );
		if (!empty( $remove_expired_days ) && !empty( $anonymize_expired_days )) {
			if ($remove_expired_days<=$anonymize_expired_days) {
				$remove_expired_days = $anonymize_expired_days+1;
				update_option( 'eme_gdpr_remove_expired_member_days', $remove_expired_days );
			}
		}
	}

	// allow custom actions
	if (has_action('eme_options_postsave_action')) {
		do_action('eme_options_postsave_action');
	}
}

function eme_options_register() {
	// only the options you want changed in the Settings page, not eg. eme_hello_to_user, eme_donation_done
	// and only those for the tab shown, otherwise the others get reset to empty values
	// The tab value is set in the form in the function eme_options_page. It needs to be set there as a hidden value when calling options.php, otherwise
	//    it won't be known here and all values will be lost.
	if ( ! isset( $_POST['option_page'] ) || ( $_POST['option_page'] != 'eme-options' ) ) {
		return;
	}
	$options = [];
	$tab     = isset( $_POST['tab'] ) ? eme_sanitize_request( $_POST['tab'] ) : 'general';
	switch ( $tab ) {
		case 'general':
			$options = [ 'eme_use_select_for_locations', 'eme_add_events_locs_link_search', 'eme_rsvp_enabled', 'eme_tasks_enabled', 'eme_categories_enabled', 'eme_attributes_enabled', 'eme_map_is_active', 'eme_load_js_in_header', 'eme_use_client_clock', 'eme_uninstall_drop_data', 'eme_uninstall_drop_settings', 'eme_shortcodes_in_widgets', 'eme_enable_notes_placeholders', 'eme_autocomplete_sources', 'eme_captcha_for_forms', 'eme_recaptcha_for_forms', 'eme_recaptcha_site_key', 'eme_recaptcha_secret_key', 'eme_hcaptcha_for_forms', 'eme_hcaptcha_site_key', 'eme_hcaptcha_secret_key', 'eme_cfcaptcha_for_forms', 'eme_cfcaptcha_site_key', 'eme_cfcaptcha_secret_key', 'eme_honeypot_for_forms', 'eme_captcha_only_logged_out', 'eme_frontend_nocache', 'eme_use_is_page_for_title', 'eme_rememberme' ];
			break;
		case 'seo':
			$options = [ 'eme_seo_permalink', 'eme_permalink_events_prefix', 'eme_permalink_locations_prefix', 'eme_permalink_categories_prefix', 'eme_permalink_calendar_prefix', 'eme_permalink_payments_prefix' ];
			break;
		case 'access':
			$options = [ 'eme_cap_add_event', 'eme_cap_author_event', 'eme_cap_publish_event', 'eme_cap_list_events', 'eme_cap_edit_events', 'eme_cap_manage_task_signups', 'eme_cap_list_locations', 'eme_cap_add_locations', 'eme_cap_author_locations', 'eme_cap_edit_locations', 'eme_cap_categories', 'eme_cap_holidays', 'eme_cap_templates', 'eme_cap_access_people', 'eme_cap_list_people', 'eme_cap_edit_people', 'eme_cap_author_person', 'eme_cap_access_members', 'eme_cap_list_members', 'eme_cap_edit_members', 'eme_cap_author_member', 'eme_cap_discounts', 'eme_cap_list_approve', 'eme_cap_author_approve', 'eme_cap_approve', 'eme_cap_list_registrations', 'eme_cap_author_registrations', 'eme_cap_registrations', 'eme_cap_attendancecheck', 'eme_cap_membercheck', 'eme_cap_forms', 'eme_cap_cleanup', 'eme_cap_settings', 'eme_cap_send_mails', 'eme_cap_send_other_mails', 'eme_cap_list_attendances', 'eme_cap_manage_attendances', 'eme_limit_admin_event_listing' ];
			break;
		case 'events':
			$options = [ 'eme_events_page', 'eme_display_events_in_events_page', 'eme_display_calendar_in_events_page', 'eme_event_list_number_items', 'eme_event_initial_state', 'eme_event_list_item_format_header', 'eme_cat_event_list_item_format_header', 'eme_event_list_item_format', 'eme_event_list_item_format_footer', 'eme_cat_event_list_item_format_footer', 'eme_event_page_title_format', 'eme_event_html_title_format', 'eme_single_event_format', 'eme_show_period_monthly_dateformat', 'eme_show_period_yearly_dateformat', 'eme_events_page_title', 'eme_no_events_message', 'eme_filter_form_format', 'eme_redir_priv_event_url' ];
			break;
		case 'calendar':
			$options = [ 'eme_small_calendar_event_title_format', 'eme_small_calendar_event_title_separator', 'eme_full_calendar_event_format', 'eme_cal_hide_past_events', 'eme_cal_show_single' ];
			break;
		case 'locations':
			$options = [ 'eme_location_list_format_header', 'eme_location_list_format_item', 'eme_location_list_format_footer', 'eme_location_page_title_format', 'eme_location_html_title_format', 'eme_single_location_format', 'eme_location_event_list_item_format', 'eme_location_no_events_message' ];
			break;
		case 'members':
			$options = [ 'eme_page_access_denied', 'eme_membership_login_required_string', 'eme_redir_protected_pages_url', 'eme_membership_attendance_msg', 'eme_membership_unauth_attendance_msg', 'eme_members_show_people_info' ];
			break;
		case 'rss':
			$options = [ 'eme_rss_main_title', 'eme_rss_main_description', 'eme_rss_title_format', 'eme_rss_description_format', 'eme_rss_show_pubdate', 'eme_rss_pubdate_startdate', 'eme_ical_description_format', 'eme_ical_location_format', 'eme_ical_title_format', 'eme_ical_quote_tzid' ];
			break;
		case 'rsvp':
			$options = [ 'eme_default_contact_person', 'eme_rsvp_registered_users_only', 'eme_rsvp_reg_for_new_events', 'eme_rsvp_require_approval', 'eme_rsvp_require_user_confirmation', 'eme_rsvp_default_number_spaces', 'eme_rsvp_addbooking_min_spaces', 'eme_rsvp_addbooking_max_spaces', 'eme_rsvp_hide_full_events', 'eme_rsvp_hide_rsvp_ended_events', 'eme_rsvp_show_form_after_booking', 'eme_rsvp_addbooking_submit_string', 'eme_rsvp_delbooking_submit_string', 'eme_rsvp_not_yet_allowed_string', 'eme_rsvp_no_longer_allowed_string', 'eme_rsvp_full_string', 'eme_rsvp_on_waiting_list_string', 'eme_rsvp_cancel_no_longer_allowed_string', 'eme_attendees_list_format', 'eme_attendees_list_ignore_pending', 'eme_bookings_list_ignore_pending', 'eme_bookings_list_header_format', 'eme_bookings_list_format', 'eme_bookings_list_footer_format', 'eme_registration_recorded_ok_html', 'eme_registration_form_format', 'eme_cancel_form_format', 'eme_cancel_payment_form_format', 'eme_cancel_payment_line_format', 'eme_cancelled_payment_format', 'eme_rsvp_start_number_days', 'eme_rsvp_start_number_hours', 'eme_rsvp_start_target', 'eme_rsvp_end_number_days', 'eme_rsvp_end_number_hours', 'eme_rsvp_end_target', 'eme_rsvp_check_required_fields', 'eme_cancel_rsvp_days', 'eme_cancel_rsvp_age', 'eme_rsvp_check_without_accents', 'eme_rsvp_admin_allow_overbooking', 'eme_rsvp_login_required_string', 'eme_rsvp_invitation_required_string', 'eme_rsvp_email_already_registered_string', 'eme_rsvp_person_already_registered_string', 'eme_check_free_waiting', 'eme_rsvp_pending_reminder_days', 'eme_rsvp_approved_reminder_days' ];
			break;
		case 'tasks':
			$options = [ 'eme_task_registered_users_only', 'eme_task_requires_approval', 'eme_task_allow_overlap', 'eme_task_form_taskentry_format', 'eme_task_form_format', 'eme_task_signup_format', 'eme_task_signup_recorded_ok_html', 'eme_task_reminder_days' ];
			break;
		case 'mail':
			$options = [ 'eme_rsvp_mail_notify_is_active', 'eme_rsvp_mail_notify_pending', 'eme_rsvp_mail_notify_paid', 'eme_rsvp_mail_notify_approved', 'eme_mail_sender_name', 'eme_mail_sender_address', 'eme_mail_force_from', 'eme_mail_send_method', 'eme_smtp_host', 'eme_smtp_port', 'eme_smtp_encryption', 'eme_smtp_auth', 'eme_smtp_username', 'eme_smtp_password', 'eme_smtp_debug', 'eme_mail_send_html', 'eme_mail_bcc_address', 'eme_smtp_verify_cert', 'eme_queue_mails', 'eme_cron_send_queued', 'eme_cron_queue_count', 'eme_people_newsletter', 'eme_people_massmail', 'eme_massmail_popup_text', 'eme_massmail_popup', 'eme_mail_tracking', 'eme_mail_sleep', 'eme_mail_blacklist' ];
			break;
		case 'mailtemplates':
			$options = [ 'eme_contactperson_email_subject', 'eme_contactperson_cancelled_email_subject', 'eme_contactperson_pending_email_subject', 'eme_contactperson_email_body', 'eme_contactperson_cancelled_email_body', 'eme_contactperson_pending_email_body', 'eme_contactperson_ipn_email_subject', 'eme_contactperson_ipn_email_body', 'eme_contactperson_paid_email_subject', 'eme_contactperson_paid_email_body', 'eme_respondent_email_subject', 'eme_respondent_email_body', 'eme_registration_pending_email_subject', 'eme_registration_pending_email_body', 'eme_registration_userpending_email_subject', 'eme_registration_userpending_email_body', 'eme_registration_cancelled_email_subject', 'eme_registration_cancelled_email_body', 'eme_registration_trashed_email_subject', 'eme_registration_trashed_email_body', 'eme_registration_updated_email_subject', 'eme_registration_updated_email_body', 'eme_registration_paid_email_subject', 'eme_registration_paid_email_body', 'eme_registration_pending_reminder_email_subject', 'eme_registration_pending_reminder_email_body', 'eme_registration_reminder_email_subject', 'eme_registration_reminder_email_body', 'eme_sub_subject', 'eme_sub_body', 'eme_unsub_subject', 'eme_unsub_body', 'eme_booking_attach_ids', 'eme_pending_attach_ids', 'eme_paid_attach_ids', 'eme_booking_attach_tmpl_ids', 'eme_pending_attach_tmpl_ids', 'eme_paid_attach_tmpl_ids', 'eme_subscribe_attach_ids', 'eme_full_name_format', 'eme_cp_task_signup_pending_email_subject', 'eme_cp_task_signup_pending_email_body', 'eme_cp_task_signup_email_subject', 'eme_cp_task_signup_email_body', 'eme_cp_task_signup_cancelled_email_subject', 'eme_cp_task_signup_cancelled_email_body', 'eme_task_signup_pending_email_subject', 'eme_task_signup_pending_email_body', 'eme_task_signup_email_subject', 'eme_task_signup_email_body', 'eme_task_signup_cancelled_email_subject', 'eme_task_signup_cancelled_email_body', 'eme_task_signup_trashed_email_subject', 'eme_task_signup_trashed_email_body', 'eme_task_signup_reminder_email_subject', 'eme_task_signup_reminder_email_body', 'eme_bd_email_subject', 'eme_bd_email_body' ];
			break;
		case 'gdpr':
			$options = [ 'eme_cpi_subject', 'eme_cpi_body', 'eme_cpi_form', 'eme_gdpr_subject', 'eme_gdpr_body', 'eme_gdpr_approve_subject', 'eme_gdpr_approve_body', 'eme_gdpr_page_title', 'eme_gdpr_page_header', 'eme_gdpr_page_footer', 'eme_gdpr_approve_page_title', 'eme_gdpr_approve_page_content', 'eme_gdpr_remove_expired_member_days', 'eme_gdpr_anonymize_expired_member_days', 'eme_gdpr_anonymize_old_bookings_days', 'eme_gdpr_remove_old_events_days', 'eme_gdpr_archive_old_mailings_days', 'eme_gdpr_remove_old_attendances_days', 'eme_gdpr_remove_old_signups_days' ];
			break;
		case 'payments':
			$options = [ 'eme_default_vat', 'eme_payment_form_header_format', 'eme_payment_form_footer_format', 'eme_multipayment_form_header_format', 'eme_multipayment_form_footer_format', 'eme_payment_succes_format', 'eme_payment_fail_format', 'eme_payment_member_succes_format', 'eme_payment_member_fail_format', 'eme_payment_booking_already_paid_format', 'eme_payment_booking_on_waitinglist_format', 'eme_default_currency', 'eme_default_price', 'eme_payment_refund_ok', 'eme_pg_submit_immediately', 'eme_payment_redirect', 'eme_payment_redirect_wait', 'eme_payment_redirect_msg', 'eme_paypal_url', 'eme_paypal_clientid', 'eme_paypal_secret', 'eme_webmoney_purse', 'eme_webmoney_secret', 'eme_webmoney_demo', 'eme_fdgg_url', 'eme_fdgg_store_name', 'eme_fdgg_shared_secret', 'eme_paypal_cost', 'eme_fdgg_cost', 'eme_webmoney_cost', 'eme_paypal_cost2', 'eme_fdgg_cost2', 'eme_webmoney_cost2', 'eme_mollie_api_key', 'eme_mollie_cost', 'eme_mollie_cost2', 'eme_paypal_button_label', 'eme_paypal_button_above', 'eme_paypal_button_below', 'eme_fdgg_button_label', 'eme_fdgg_button_above', 'eme_fdgg_button_below', 'eme_webmoney_button_label', 'eme_webmoney_button_above', 'eme_webmoney_button_below', 'eme_mollie_button_label', 'eme_mollie_button_above', 'eme_mollie_button_below', 'eme_paypal_button_img_url', 'eme_fdgg_button_img_url', 'eme_webmoney_button_img_url', 'eme_mollie_button_img_url', 'eme_worldpay_demo', 'eme_worldpay_instid', 'eme_worldpay_md5_secret', 'eme_worldpay_md5_parameters', 'eme_worldpay_test_pwd', 'eme_worldpay_live_pwd', 'eme_worldpay_cost', 'eme_worldpay_cost2', 'eme_worldpay_button_label', 'eme_worldpay_button_img_url', 'eme_worldpay_button_above', 'eme_worldpay_button_below', 'eme_braintree_private_key', 'eme_braintree_public_key', 'eme_braintree_merchant_id', 'eme_braintree_env', 'eme_braintree_cost', 'eme_braintree_cost2', 'eme_braintree_button_label', 'eme_braintree_button_img_url', 'eme_braintree_button_above', 'eme_braintree_button_below', 'eme_stripe_private_key', 'eme_stripe_public_key', 'eme_stripe_cost', 'eme_stripe_cost2', 'eme_stripe_button_label', 'eme_stripe_button_img_url', 'eme_stripe_button_above', 'eme_stripe_button_below', 'eme_stripe_payment_methods', 'eme_offline_payment', 'eme_legacypaypal_url', 'eme_legacypaypal_business', 'eme_legacypaypal_no_tax', 'eme_legacypaypal_cost', 'eme_legacypaypal_cost2', 'eme_legacypaypal_button_label', 'eme_legacypaypal_button_img_url', 'eme_legacypaypal_button_above', 'eme_legacypaypal_button_below', 'eme_instamojo_env', 'eme_instamojo_key', 'eme_instamojo_auth_token', 'eme_instamojo_salt', 'eme_instamojo_cost', 'eme_instamojo_cost2', 'eme_instamojo_button_label', 'eme_instamojo_button_img_url', 'eme_instamojo_button_above', 'eme_instamojo_button_below', 'eme_mercadopago_demo', 'eme_mercadopago_sandbox_token', 'eme_mercadopago_live_token', 'eme_mercadopago_cost', 'eme_mercadopago_cost2', 'eme_mercadopago_button_label', 'eme_mercadopago_button_img_url', 'eme_mercadopago_button_above', 'eme_mercadopago_button_below', 'eme_fondy_merchant_id', 'eme_fondy_secret_key', 'eme_fondy_cost', 'eme_fondy_cost2', 'eme_fondy_button_label', 'eme_fondy_button_img_url', 'eme_fondy_button_above', 'eme_fondy_button_below', 'eme_payconiq_api_key', 'eme_payconiq_env', 'eme_payconiq_merchant_id', 'eme_payconiq_cost', 'eme_payconiq_cost2', 'eme_payconiq_button_label', 'eme_payconiq_button_img_url', 'eme_payconiq_button_above', 'eme_payconiq_button_below', 'eme_sumup_merchant_code', 'eme_sumup_app_id', 'eme_sumup_app_secret', 'eme_sumup_cost', 'eme_sumup_cost2', 'eme_sumup_button_label', 'eme_sumup_button_img_url', 'eme_sumup_button_above', 'eme_sumup_button_below', 'eme_opayo_demo', 'eme_opayo_vendor_name', 'eme_opayo_test_pwd', 'eme_opayo_live_pwd', 'eme_opayo_cost', 'eme_opayo_cost2', 'eme_opayo_button_label', 'eme_opayo_button_img_url', 'eme_opayo_button_above', 'eme_opayo_button_below' ];
			break;
		case 'maps':
			$options = [ 'eme_indiv_zoom_factor', 'eme_map_zooming', 'eme_location_baloon_format', 'eme_location_map_icon', 'eme_map_gesture_handling' ];
			break;
		case 'other':
			// put eme_allowed_style_attr and eme_allowed_html first, so it has a immediate impact on the other options
			$options = [ 'eme_allowed_style_attr', 'eme_allowed_html', 'eme_thumbnail_size', 'eme_image_max_width', 'eme_image_max_height', 'eme_image_max_size', 'eme_html_header', 'eme_html_footer', 'eme_event_html_headers_format', 'eme_location_html_headers_format', 'eme_csv_separator', 'eme_use_external_url', 'eme_bd_email', 'eme_bd_email_members_only', 'eme_time_remove_leading_zeros', 'eme_stay_on_edit_page', 'eme_localize_price', 'eme_decimals', 'eme_timepicker_minutesstep', 'eme_form_required_field_string', 'eme_version', 'eme_pdf_font', 'eme_backend_dateformat', 'eme_backend_timeformat', 'eme_address1_string', 'eme_address2_string', 'eme_multisite_active' ];
			break;
	}

	foreach ( $options as $opt ) {
		register_setting ( 'eme-options', $opt );
		add_filter ( 'sanitize_option_' . $opt, 'eme_sanitize_option', 10, 2 );
	}
}

function eme_sanitize_option( $option_value, $option_name ) {
	// allow js only in very specific header settings
	//$allow_js_arr=array('eme_html_header','eme_html_footer','eme_event_html_headers_format','eme_location_html_headers_format','eme_payment_form_header_format','eme_payment_form_footer_format','eme_multipayment_form_header_format','eme_multipayment_form_footer_format','eme_payment_succes_format','eme_payment_fail_format','eme_payment_member_succes_format','eme_payment_member_fail_format','eme_registration_recorded_ok_html');
	$no_kses = ['eme_smtp_password'];
	$numeric_options = [
		'eme_rsvp_start_number_days' => 0,
		'eme_rsvp_start_number_hours' => 0,
		'eme_rsvp_end_number_days' => 0,
		'eme_rsvp_end_number_hours' => 0,
		'eme_cancel_rsvp_days' => 0,
		'eme_cancel_rsvp_age' => 0,
		'eme_event_list_number_items' => 10,
		'eme_smtp_port' => 25,
                'eme_mail_sleep' => 0,
	];

	if ( is_array( $option_value ) ) {
		return array_map( 'eme_sanitize_option', $option_value );
	} else {
		if (in_array($option_name,$no_kses)) {
			$output = $option_value;
		} else {
			$output = eme_kses( $option_value );
		}
		if (array_key_exists($option_name,$numeric_options) && ! is_numeric( $output ) ) {
			$output = $numeric_options[$option_name];
		}
		if ( $option_name == 'eme_sub_body' && ! strstr( $output, '#_SUB_CONFIRM_URL' ) )
			$output .= "\n#_SUB_CONFIRM_URL";
		if ( $option_name == 'eme_unsub_body' && ! strstr( $output, '#_UNSUB_CONFIRM_URL' ) )
			$output .= "\n#_UNSUB_CONFIRM_URL";
		if ( $option_name == 'eme_full_name_format' ) {
			if ( ! strstr( $output, '#_LASTNAME' ) )
				$output .= ' #_LASTNAME';
			if ( ! strstr( $output, '#_FIRSTNAME' ) )
				$output .= ' #_FIRSTNAME';
			$output = trim( $output );
		}
		if ( $option_name == 'eme_cpi_body' && ! strstr( $output, '#_CHANGE_PERSON_INFO' ) )
                        $output .= "\n#_CHANGE_PERSON_INFO";
		if ( $option_name == 'eme_gdpr_approve_body' && ! strstr( $output, '#_GDPR_APPROVE_URL' ) )
                        $output .= "\n#_GDPR_APPROVE_URL";
		if ( $option_name == 'eme_gdpr_body' && ! strstr( $output, '#_GDPR_URL' ) )
                        $output .= "\n#_GDPR_URL";
		if ( $option_name == 'eme_default_vat' && (! is_numeric( $output ) || $output < 0 || $output > 100 ))
			$output = 0;
		if ( $option_name == 'eme_captcha_for_forms' && ! function_exists( 'imagecreatetruecolor' ) )
			$output = 0;
	}
	return $output;
}

function eme_admin_tabs( $current = 'homepage' ) {
	$tabs = [
		'general'       => __( 'General', 'events-made-easy' ),
		'access'        => __( 'Access', 'events-made-easy' ),
		'seo'           => __( 'SEO', 'events-made-easy' ),
		'events'        => __( 'Events', 'events-made-easy' ),
		'locations'     => __( 'Locations', 'events-made-easy' ),
		'members'       => __( 'Members', 'events-made-easy' ),
		'calendar'      => __( 'Calendar', 'events-made-easy' ),
		'rss'           => __( 'RSS & ICAL', 'events-made-easy' ),
		'rsvp'          => __( 'RSVP', 'events-made-easy' ),
		'tasks'         => __( 'Event tasks', 'events-made-easy' ),
		'mail'          => __( 'Email', 'events-made-easy' ),
		'mailtemplates' => __( 'Email templates', 'events-made-easy' ),
		'gdpr'          => __( 'Data protection', 'events-made-easy' ),
		'payments'      => __( 'Payments', 'events-made-easy' ),
		'maps'          => __( 'Maps', 'events-made-easy' ),
		'other'         => __( 'Other', 'events-made-easy' ),
	];
	if ( ! get_option( 'eme_rsvp_enabled' ) ) {
		unset( $tabs['rsvp'] );
	}
	if ( ! get_option( 'eme_map_is_active' ) ) {
		unset( $tabs['maps'] );
	}
	if ( ! get_option( 'eme_tasks_enabled' ) ) {
		unset( $tabs['tasks'] );
	}
	echo '<div id="icon-themes" class="icon32"></div>';
	echo '<h1 class="nav-tab-wrapper">';
	$eme_options_url = admin_url( 'admin.php?page=eme-options' );
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='$eme_options_url&tab=$tab'>$name</a>";
	}
	echo '</h1>';
}

function eme_check_conflicting_slug() {
	global $wpdb;
	$events_prefixes     = explode( ',', get_option( 'eme_permalink_events_prefix', 'events' ) );
	$locations_prefixes  = explode( ',', get_option( 'eme_permalink_locations_prefix', 'locations' ) );
	$categories_prefixes = explode( ',', get_option( 'eme_permalink_categories_prefix', '' ) );
	$calendar_prefix     = get_option( 'eme_permalink_calendar_prefix', '' );
	$payments_prefix     = get_option( 'eme_permalink_payments_prefix', '' );

	if ( ! empty( array_intersect( $events_prefixes, $locations_prefixes ) ) ) {
		return true;
	}
	if ( ! empty( array_intersect( $events_prefixes, $categories_prefixes ) ) ) {
		return true;
	}
	if ( ! empty( array_intersect( $categories_prefixes, $locations_prefixes ) ) ) {
		return true;
	}
	if ( ! eme_is_empty_string( $calendar_prefix ) ) {
		if ( in_array( $calendar_prefix, $events_prefixes ) || in_array( $calendar_prefix, $locations_prefixes ) || in_array( $calendar_prefix, $categories_prefixes ) ) {
			return true;
		}
	}
	if ( ! eme_is_empty_string( $payments_prefix ) ) {
		if ( in_array( $payments_prefix, $events_prefixes ) || in_array( $payments_prefix, $locations_prefixes ) || in_array( $payments_prefix, $categories_prefixes ) ) {
			return true;
		}
	}
	if ( ! eme_is_empty_string( $calendar_prefix ) && ! eme_is_empty_string( $payments_prefix ) && $payments_prefix == $calendar_prefix ) {
		return true;
	}

	$events_pageid = eme_get_events_page_id();

	$check_sql = "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND ID != %d AND post_status != 'trash' LIMIT 1";
	foreach ( $events_prefixes as $events_prefix ) {
		if ( eme_is_empty_string( $events_prefix ) ) {
					continue;
		}
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $events_prefix, $events_pageid ) );
		if ( ! empty( $post_name_check ) ) {
			return $post_name_check;
		}
	}
	foreach ( $locations_prefixes as $locations_prefix ) {
		if ( eme_is_empty_string( $locations_prefix ) ) {
					continue;
		}
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $locations_prefix, $events_pageid ) );
		if ( ! empty( $post_name_check ) ) {
			return $post_name_check;
		}
	}
	foreach ( $categories_prefixes as $categories_prefix ) {
		if ( eme_is_empty_string( $categories_prefix ) ) {
					continue;
		}
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $categories_prefix, $events_pageid ) );
		if ( ! empty( $post_name_check ) ) {
			return $post_name_check;
		}
	}
	if ( ! empty( $calendar_prefix ) ) {
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $calendar_prefix, $events_pageid ) );
		if ( ! empty( $post_name_check ) ) {
			return $post_name_check;
		}
	}
	if ( ! empty( $payments_prefix ) ) {
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $payments_prefix, $events_pageid ) );
		if ( ! empty( $post_name_check ) ) {
			return $post_name_check;
		}
	}
	return false;
}

function eme_explain_slug_conflict( $conflict_found ) {
	// $conflict_found is either true or a page id
	?>
		<div class="notice notice-warning"><p>
		<?php
		if ( $conflict_found === true ) {
			esc_html_e( 'The EME SEO permalink settings are conflicting with itself (the permalink prefix setting for either events, locations or categories contains a value that is not unique). Please resolve the conflict by changing your EME SEO permalink settings.', 'events-made-easy' );
		} else {
			esc_html_e( 'The EME SEO permalink settings are conflicting with an existing page (the permalink setting for either events, locations or categories is identical with the permalink of another WordPress page). This might cause problems rendering either events or that page. Please resolve the conflict by either changing your EME SEO permalink settings or the permalink of the conflicting page.', 'events-made-easy' );
			echo '<br>';
			echo sprintf( __( 'The conflicting page can be edited <a href="%s" target="_blank">here</a>.', 'events-made-easy' ), admin_url( "post.php?post=$conflict_found&action=edit" ) );
		}
		?>
		</p></div>
	<?php
}

// Function composing the options page
function eme_options_page() {
	

	$tab = isset( $_GET['tab'] ) ? eme_sanitize_request( $_GET['tab'] ) : 'general';
	eme_admin_tabs( $tab );
	$conflict_found = eme_check_conflicting_slug();
	if ( ! empty( $conflict_found ) ) {
		eme_explain_slug_conflict( $conflict_found );
	}
	?>
<div class="wrap">
<div id='icon-options-general' class='icon32'>
</div>
<h1><?php esc_html_e( 'Event Manager Options', 'events-made-easy' ); ?></h1>
<p> 
	<?php printf( __( "Please also check <a href='%s'>your profile</a> for some per-user EME settings.", 'events-made-easy' ), admin_url( 'profile.php' ) ); ?>
</p>
<form id="eme_options_form" method="post" action="options.php" autocomplete="off">
<input type='hidden' name='tab' value='<?php echo eme_esc_html( $tab ); ?>'>
	<?php
	settings_fields( 'eme-options' );
	switch ( $tab ) {
		case 'general':
			?>

<h3><?php esc_html_e( 'General options', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_radio_binary( __( 'Use dropdown for locations?', 'events-made-easy' ), 'eme_use_select_for_locations', __( 'Select yes to select the location from a drop-down menu; location selection will be faster, but you will lose the ability to insert locations with events.', 'events-made-easy' ) . '<br>' . __( 'When the qtranslate plugin is installed and activated, this setting will be ignored and always considered \'Yes\'.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Add events and locations to WP link search function?', 'events-made-easy' ), 'eme_add_events_locs_link_search', __( 'If selected, events and locations will be shown in the link search when creating links in the WordPress editor.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use RSVP?', 'events-made-easy' ), 'eme_rsvp_enabled', __( 'Select yes to enable the RSVP feature so people can register for an event and book seats.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use tasks?', 'events-made-easy' ), 'eme_tasks_enabled', __( 'Select yes to enable the Tasks feature so people can sign up for event tasks (volunteer management).', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use categories?', 'events-made-easy' ), 'eme_categories_enabled', __( 'Select yes to enable the category features.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use attributes?', 'events-made-easy' ), 'eme_attributes_enabled', __( 'Select yes to enable the attributes feature.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Enable Maps?', 'events-made-easy' ), 'eme_map_is_active', __( 'Check this option to be able to show a map of the event or location using #_MAP or the available shortcodes.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Always include JS in header?', 'events-made-easy' ), 'eme_load_js_in_header', __( 'Some themes are badly designed and can have issues showing the map or advancing in the calendar. If so, try activating this option which will cause the javascript to always be included in the header of every page (off by default).', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use the client computer clock for the calendar', 'events-made-easy' ), 'eme_use_client_clock', __( 'Check this option if you want to use the clock of the client as base to calculate current day for the calendar.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Add anti-spam honeypot field for forms?', 'events-made-easy' ), 'eme_honeypot_for_forms', __( 'Check this option if you want to add an invisible field to your forms. Bots will fill out this field and thus get trapped.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use EME captcha for forms?', 'events-made-easy' ), 'eme_captcha_for_forms', __( 'Check this option if you want to use a simple image captcha on the booking/cancel/membership forms, to thwart spammers a bit. You can then either add #_CAPTCHA to your form layout yourself or it will automatically be added just above the submit button if not present.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use Google reCAPTCHA for forms?', 'events-made-easy' ), 'eme_recaptcha_for_forms', __( 'Check this option if you want to use Google reCAPTCHA on the booking/cancel/membership forms, to thwart spammers a bit. Currently only supports reCAPTCHA v2 Tickbox. You can then either add #_RECAPTCHA to your form layout yourself or it will automatically added just above the submit button if not present.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Google reCAPTCHA site key', 'events-made-easy' ), 'eme_recaptcha_site_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_input_text( __( 'Google reCAPTCHA secret key', 'events-made-easy' ), 'eme_recaptcha_secret_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use hCaptcha for forms?', 'events-made-easy' ), 'eme_hcaptcha_for_forms', __( 'Check this option if you want to use hCaptcha on the booking/cancel/membership forms, to thwart spammers a bit. You can then either add #_HCAPTCHA to your form layout yourself or it will automatically added just above the submit button if not present.', 'events-made-easy' ) );
				eme_options_input_text( __( 'hCaptcha site key', 'events-made-easy' ), 'eme_hcaptcha_site_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_input_text( __( 'hCaptcha secret key', 'events-made-easy' ), 'eme_hcaptcha_secret_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use Cloudflare Turnstile for forms?', 'events-made-easy' ), 'eme_cfcaptcha_for_forms', __( 'Check this option if you want to use Cloudflare Turnstile on the booking/cancel/membership forms, to thwart spammers a bit. You can then either add #_CFCAPTCHA to your form layout yourself or it will automatically added just above the submit button if not present.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Cloudflare Turnstile site key', 'events-made-easy' ), 'eme_cfcaptcha_site_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_input_text( __( 'Cloudflare Turnstile secret key', 'events-made-easy' ), 'eme_cfcaptcha_secret_key', __( 'This field is required', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Only use captcha for logged out users?', 'events-made-easy' ), 'eme_captcha_only_logged_out', __( 'If this option is checked, the captcha will only be used for logged out users.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Enable Remember-me functionality?', 'events-made-easy' ), 'eme_rememberme', __( 'Check this option to show a checkbox that allows people to choose if they want their lastname/firstname/email stored locallly, to have it prefilled next time. This also requires the use of a #_REMEMBERME placeholder in your form, and only works for not logged-in users in the frontend. If checked, the option "Show the RSVP form again after booking" will be ignored.', 'events-made-easy' ) );
				eme_options_select(
				    __( 'Autocomplete sources', 'events-made-easy' ),
				    'eme_autocomplete_sources',
				    [
						'none'     => __( 'None', 'events-made-easy' ),
						'people'   => __( 'EME people', 'events-made-easy' ),
						'wp_users' => __( 'Wordpress users', 'events-made-easy' ),
						'both'     => __(
						    'Both EME people and WP users',
						    'events-made-easy'
						),
					],
				    __( 'Decide if autocompletion is used in RSVP or membership forms and select if you want to search EME people, WP users or both. The autocompletion only works on the lastname field and only if you have sufficient rights (event creator or event author).', 'events-made-easy' )
				);
				eme_options_radio_binary( __( 'Delete all stored EME data when upgrading or deactivating?', 'events-made-easy' ), 'eme_uninstall_drop_data', __( 'Check this option if you want to delete all EME data concerning events, bookings, ... when upgrading or deactivating the plugin.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Delete all EME settings when upgrading or deactivating?', 'events-made-easy' ), 'eme_uninstall_drop_settings', __( 'Check this option if you want to delete all EME settings when upgrading or deactivating the plugin.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Enable shortcodes in widgets', 'events-made-easy' ), 'eme_shortcodes_in_widgets', __( 'Check this option if you want to enable the use of shortcodes in widgets (affects shortcodes of any plugin used in widgets, so use with care).', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Enable placeholders in event or location notes', 'events-made-easy' ), 'eme_enable_notes_placeholders', __( 'Check this option if you want to enable the use of placeholders in the event or location notes. By default placeholders in notes are not being touched at all so as not to interfere with possible format settings for other shortcodes you can/want to use, so use with care.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Add nocache headers?', 'events-made-easy' ), 'eme_frontend_nocache', __( 'In the frontend WordPress allows browsers to cache content, but this can cause issues if you have shortcodes that change over time (like events). Checking this option will cause nocache headers to be sent so browsers no longer cache the content (this does not impact browser caching of CSS and JS files).', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use alternative method to set events page title?', 'events-made-easy' ), 'eme_use_is_page_for_title', __( "A great number of themes doesn't correctly use WordPress standards to set the page title, so this provides an alternative method if the page title is not set to the event title when viewing a single event.", 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'seo':
			?>

<h3><?php esc_html_e( 'Permalink options', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_radio_binary( __( 'Enable event permalinks if possible?', 'events-made-easy' ), 'eme_seo_permalink', __( 'If Yes, EME will render SEO permalinks if permalinks are activated.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Events permalink prefix', 'events-made-easy' ), 'eme_permalink_events_prefix', __( 'The permalink prefix used for events, categories, payments and the calendar. You can mention multiple prefixes separated by "," (just make sure they do not conflict with something else) in which case the first one will be used for payments and the calendar and for each event you can then chose which prefix you like best. ', 'events-made-easy' ) );
				eme_options_input_text( __( 'Locations permalink prefix', 'events-made-easy' ), 'eme_permalink_locations_prefix', __( 'The permalink prefix used for locations. You can mention multiple prefixes separated by "," (just make sure they do not conflict with something else).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Categories permalink prefix', 'events-made-easy' ), 'eme_permalink_categories_prefix', __( 'The permalink prefix used for categories. If empty, the event prefixes are used. You can mention multiple prefixes separated by "," (just make sure they do not conflict with something else).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Calendar permalink prefix', 'events-made-easy' ), 'eme_permalink_calendar_prefix', __( 'The permalink prefix used for the calendar. If empty, the event prefixes are used.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Payments permalink prefix', 'events-made-easy' ), 'eme_permalink_payments_prefix', __( 'The permalink prefix used for payments. If empty, the event prefixes are used.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'access':
			?>

<h3><?php esc_html_e( 'Access rights', 'events-made-easy' ); ?></h3>
<p><?php esc_html_e( 'Tip: Use a plugin like "User Role Editor" to add/edit capabilities and roles.', 'events-made-easy' ); ?></p>

<div id="eme-payments-accordion">
<h3><?php esc_html_e( 'Events', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
				eme_options_select( __( 'List events', 'events-made-easy' ), 'eme_cap_list_events', eme_get_all_caps(), sprintf( __( 'Permission needed to list all events, useful for CSV exports for bookings and such. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_EVENTS ) ) . '<br><b>' . __( 'All your Events Made Easy admins need this as well, otherwise the main menu will not show.', 'events-made-easy' ) . '</b>' );
				eme_options_radio_binary( __( 'Limit event listing?', 'events-made-easy' ), 'eme_limit_admin_event_listing', __( 'If Yes, the admin listing of events will be limited to those the current user can author or is contact person for.', 'events-made-easy' ) );
				eme_options_select( __( 'Add event', 'events-made-easy' ), 'eme_cap_add_event', eme_get_all_caps(), sprintf( __( 'Permission needed to add a new event. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_ADD_EVENT ) ) );
				eme_options_select( __( 'Author event', 'events-made-easy' ), 'eme_cap_author_event', eme_get_all_caps(), sprintf( __( 'Permission needed to edit own events (events for which you are the author). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_EVENT ) ) );
				eme_options_select( __( 'Publish event', 'events-made-easy' ), 'eme_cap_publish_event', eme_get_all_caps(), sprintf( __( 'Permission needed to make an event public. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_PUBLISH_EVENT ) ) );
				eme_options_select( __( 'Edit events', 'events-made-easy' ), 'eme_cap_edit_events', eme_get_all_caps(), sprintf( __( 'Permission needed to edit all events. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_EDIT_EVENTS ) ) );
				eme_options_select( __( 'Manage task signups', 'events-made-easy' ), 'eme_cap_manage_task_signups', eme_get_all_caps(), sprintf( __( 'Permission needed to manage all task signups. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_MANAGE_TASK_SIGNUPS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Locations', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'List locations', 'events-made-easy' ), 'eme_cap_list_locations', eme_get_all_caps(), sprintf( __( 'Permission needed to list all locations. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_LOCATIONS ) ) . '<br><b>' . __( 'All your location admins need this as well, otherwise the locations menu will not show.', 'events-made-easy' ) . '</b>' );
			eme_options_select( __( 'Add location', 'events-made-easy' ), 'eme_cap_add_locations', eme_get_all_caps(), sprintf( __( 'Permission needed to add locations. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_ADD_LOCATION ) ) );
			eme_options_select( __( 'Author location', 'events-made-easy' ), 'eme_cap_author_locations', eme_get_all_caps(), sprintf( __( 'Permission needed to edit own locations (locations for which you are the author). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_LOCATION ) ) );
			eme_options_select( __( 'Edit locations', 'events-made-easy' ), 'eme_cap_edit_locations', eme_get_all_caps(), sprintf( __( 'Permission needed to edit all locations. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_EDIT_LOCATIONS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Categories', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Edit categories', 'events-made-easy' ), 'eme_cap_categories', eme_get_all_caps(), sprintf( __( 'Permission needed to edit all categories. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_CATEGORIES ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Holidays', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Holidays', 'events-made-easy' ), 'eme_cap_holidays', eme_get_all_caps(), sprintf( __( 'Permission needed to manage holidays. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_HOLIDAYS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Templates', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Edit templates', 'events-made-easy' ), 'eme_cap_templates', eme_get_all_caps(), sprintf( __( 'Permission needed to edit all templates. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_TEMPLATES ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Discounts', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Manage disounts', 'events-made-easy' ), 'eme_cap_discounts', eme_get_all_caps(), sprintf( __( 'Permission needed to manage discounts. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_DISCOUNTS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'People and groups', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Access to people and groups', 'events-made-easy' ), 'eme_cap_access_people', eme_get_all_caps(), sprintf( __( 'Permission needed to access the people or groups menu. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_ACCESS_PEOPLE ) ) . '<br><b>' . __( 'All your people admins need this, otherwise the people and groups menus will not show.', 'events-made-easy' ) . '</b>' );
			eme_options_select( __( 'List people and groups', 'events-made-easy' ), 'eme_cap_list_people', eme_get_all_caps(), sprintf( __( 'Permission needed to see the list of people or groups. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_PEOPLE ) ) );
			eme_options_select( __( 'Author person', 'events-made-easy' ), 'eme_cap_author_person', eme_get_all_caps(), sprintf( __( 'Permission needed to manage own personal info (the WordPress user logged needs to be linked to an EME user for this to work). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_PERSON ) ) );
			eme_options_select( __( 'Edit people and groups', 'events-made-easy' ), 'eme_cap_edit_people', eme_get_all_caps(), sprintf( __( 'Permission needed to manage registered people. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_EDIT_PEOPLE ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Memberships', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Access to members and memberships', 'events-made-easy' ), 'eme_cap_access_members', eme_get_all_caps(), sprintf( __( 'Permission needed to access the members or memberships menu. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_ACCESS_MEMBERS ) ) . '<br><b>' . __( 'All your member admins need this, otherwise the members and memberships menus will not show.', 'events-made-easy' ) . '</b>' );
			eme_options_select( __( 'List members and memberships', 'events-made-easy' ), 'eme_cap_list_members', eme_get_all_caps(), sprintf( __( 'Permission needed to see the list of members or memberships. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_MEMBERS ) ) );
			eme_options_select( __( 'Author member', 'events-made-easy' ), 'eme_cap_author_member', eme_get_all_caps(), sprintf( __( 'Permission needed to manage own member info (the WordPress user logged needs to be linked to an EME user for this to work). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_MEMBER ) ) );
			eme_options_select( __( 'Manage members and memberships', 'events-made-easy' ), 'eme_cap_edit_members', eme_get_all_caps(), sprintf( __( 'Permission needed to manage members and memberships. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_EDIT_MEMBERS ) ) );
			eme_options_select( __( 'Member check', 'events-made-easy' ), 'eme_cap_membercheck', eme_get_all_caps(), sprintf( __( 'Permission needed to check if a member is active (link generated by #_QRCODE in a member context). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_MEMBERCHECK ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Bookings', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'List pending bookings', 'events-made-easy' ), 'eme_cap_list_approve', eme_get_all_caps(), sprintf( __( 'Permission needed to list pending bookings. If someone does not have this role, the menu concerning pending bookings will not appear. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_APPROVE ) ) );
			eme_options_select( __( 'Author approve bookings', 'events-made-easy' ), 'eme_cap_author_approve', eme_get_all_caps(), sprintf( __( 'Permission needed to approve pending bookings by the author of an event. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_APPROVE ) ) );
			eme_options_select( __( 'Approve all bookings', 'events-made-easy' ), 'eme_cap_approve', eme_get_all_caps(), sprintf( __( 'Permission needed to approve all pending bookings. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_APPROVE ) ) );
			eme_options_select( __( 'List approved bookings', 'events-made-easy' ), 'eme_cap_list_registrations', eme_get_all_caps(), sprintf( __( 'Permission needed to list approved bookings. If someone does not have this role, the menu concerning approved bookings will not appear. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_REGISTRATIONS ) ) );
			eme_options_select( __( 'Author edit bookings', 'events-made-easy' ), 'eme_cap_author_registrations', eme_get_all_caps(), sprintf( __( 'Permission needed to edit approved bookings by the author of an event. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_AUTHOR_REGISTRATIONS ) ) );
			eme_options_select( __( 'Edit all bookings', 'events-made-easy' ), 'eme_cap_registrations', eme_get_all_caps(), sprintf( __( 'Permission needed to edit all approved bookings. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_REGISTRATIONS ) ) );
			eme_options_select( __( 'Attendance check', 'events-made-easy' ), 'eme_cap_attendancecheck', eme_get_all_caps(), sprintf( __( 'Permission needed to check attendance for events (link generated by #_QRCODE in a RSVP context). Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_ATTENDANCECHECK ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Emails', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Send emails for own events', 'events-made-easy' ), 'eme_cap_send_mails', eme_get_all_caps(), sprintf( __( 'Permission needed to send mails for own events and be able to access the mailing submenu. Default: %s.', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_SEND_MAILS ) ) );
			eme_options_select( __( 'Send emails for any event', 'events-made-easy' ), 'eme_cap_send_other_mails', eme_get_all_caps(), sprintf( __( 'Permission needed to send mails for any event. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_SEND_OTHER_MAILS ) ) );
			eme_options_select( __( 'Send generic emails', 'events-made-easy' ), 'eme_cap_send_generic_mails', eme_get_all_caps(), sprintf( __( 'Permission needed to send generic mails. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_SEND_GENERIC_MAILS ) ) );
			eme_options_select( __( 'View mailings and mail queue', 'events-made-easy' ), 'eme_cap_send_other_mails', eme_get_all_caps(), sprintf( __( 'Permission needed to view planned mailings and the mail queue. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_VIEW_MAILS ) ) );
			eme_options_select( __( 'Manage mailings and mail queue', 'events-made-easy' ), 'eme_cap_send_other_mails', eme_get_all_caps(), sprintf( __( 'Permission needed to manage planned mailings and the mail queue. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_MANAGE_MAILS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Custom fields', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Edit form fields', 'events-made-easy' ), 'eme_cap_forms', eme_get_all_caps(), sprintf( __( 'Permission needed to edit form fields. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_FORMS ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Attendances', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'List attendances', 'events-made-easy' ), 'eme_cap_list_attendances', eme_get_all_caps(), sprintf( __( 'Permission needed to list attendances. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_LIST_ATTENDANCES ) ) );
			eme_options_select( __( 'Manage attendances', 'events-made-easy' ), 'eme_cap_manage_attendances', eme_get_all_caps(), sprintf( __( 'Permission needed to manage (add/delete) attendances. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_MANAGE_ATTENDANCES ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Actions', 'events-made-easy' ); ?></h3>
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Actions', 'events-made-easy' ), 'eme_cap_cleanup', eme_get_all_caps(), sprintf( __( 'Permission needed to execute cleanup actions, manage cron settings and import actions. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_CLEANUP ) ) );
			?>
</table>
</div>
<h3><?php esc_html_e( 'Settings', 'events-made-easy' ); ?></h3> 
<div>
<table class="form-table">
			<?php
			eme_options_select( __( 'Edit settings', 'events-made-easy' ), 'eme_cap_settings', eme_get_all_caps(), sprintf( __( 'Permission needed to edit settings. Default: %s', 'events-made-easy' ), eme_capNamesCB( DEFAULT_CAP_SETTINGS ) ) );
			?>
</table>
</div>
</div>

			<?php
			break;
		case 'events':
			?>

<h3><?php esc_html_e( 'Events page', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_select( __( 'Events page', 'events-made-easy' ), 'eme_events_page', eme_get_all_pages(), __( 'This option allows you to select which page to use as an events page.', 'events-made-easy' ) . '<br><strong>' . __( 'The content of this page (including shortcodes of any kind) will be ignored completely and dynamically replaced by events data.', 'events-made-easy' ) . '</strong>' );
				eme_options_radio_binary( __( 'Display events in events page?', 'events-made-easy' ), 'eme_display_events_in_events_page', __( 'This option allows to display a default list of events in the events page. It is not recommended to use this option, but use shortcodes on regular pages instead.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Display calendar in events page?', 'events-made-easy' ), 'eme_display_calendar_in_events_page', __( 'This option allows to display an events calendar in the events page. It is not recommended to use this option, but use shortcodes on regular pages instead.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Number of events to show in lists', 'events-made-easy' ), 'eme_event_list_number_items', __( 'The number of events to show in a list if no specific limit is specified (used in the shortcode eme_events, RSS feed, the placeholders #_NEXTEVENTS and #_PASTEVENTS, ...). Use 0 for no limit.', 'events-made-easy' ) );
				eme_options_select( __( 'Initial status for a new event', 'events-made-easy' ), 'eme_event_initial_state', eme_status_array(), __( 'Initial status for a new event', 'events-made-easy' ) );
				eme_options_input_text( __( 'URL to redirect private events to', 'events-made-easy' ), 'eme_redir_priv_event_url', __( 'When a person that is not logged-in wants to look at a private event, that person is redirected to the WordPress login page. If you want to redirect to another url, enter it here. The permalink of the current visited page will be added to the mentioned url query string with a parameter called "redirect".', 'events-made-easy' ) );
			?>
</table>
<h3><?php esc_html_e( 'Events format', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
			eme_options_textarea( __( 'Default event list format header', 'events-made-easy' ), 'eme_event_list_item_format_header', sprintf( __( 'This content will appear just above your code for the default event list format. If you leave this empty, the value <code>%s</code> will be used.', 'events-made-easy' ), eme_esc_html( DEFAULT_EVENT_LIST_HEADER_FORMAT ) ) );
			eme_options_textarea( __( 'Default categories event list format header', 'events-made-easy' ), 'eme_cat_event_list_item_format_header', sprintf( __( 'This content will appear just above your code for the event list format when showing events for a specific category. If you leave this empty, the value <code>%s</code> will be used.', 'events-made-easy' ), eme_esc_html( DEFAULT_CAT_EVENT_LIST_HEADER_FORMAT ) ) );
			eme_options_textarea( __( 'Default event list format', 'events-made-easy' ), 'eme_event_list_item_format', __( 'The format of any events in a list.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_textarea( __( 'Default event list format footer', 'events-made-easy' ), 'eme_event_list_item_format_footer', sprintf( __( 'This content will appear just below your code for the default event list format. If you leave this empty, the value <code>%s</code> will be used.', 'events-made-easy' ), eme_esc_html( DEFAULT_EVENT_LIST_FOOTER_FORMAT ) ) );
			eme_options_textarea( __( 'Default categories event list format footer', 'events-made-easy' ), 'eme_cat_event_list_item_format_footer', sprintf( __( 'This content will appear just below your code for the default event list format when showing events for a specific category. If you leave this empty, the value <code>%s</code> will be used.', 'events-made-easy' ), eme_esc_html( DEFAULT_CAT_EVENT_LIST_FOOTER_FORMAT ) ) );
			eme_options_input_text( __( 'Single event page title format', 'events-made-easy' ), 'eme_event_page_title_format', __( 'The format of a single event page title. Follow the previous formatting instructions.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Single event html title format', 'events-made-easy' ), 'eme_event_html_title_format', __( 'The format of a single event html page title. Follow the previous formatting instructions.', 'events-made-easy' ) . __( ' The default is: ', 'events-made-easy' ) . eme_esc_html( DEFAULT_EVENT_HTML_TITLE_FORMAT ) );
			eme_options_textarea( __( 'Default single event format', 'events-made-easy' ), 'eme_single_event_format', __( 'The format of a single event page.<br>Follow the previous formatting instructions. <br>Use <code>#_MAP</code> to insert a map.<br>Use <code>#_CONTACTNAME</code>, <code>#_CONTACTEMAIL</code>, <code>#_CONTACTPHONE</code> to insert respectively the name, email address and phone number of the designated contact person. <br>Use <code>#_ADDBOOKINGFORM</code> to insert a form to allow the user to respond to your events booking one or more seats (RSVP).<br> Use <code>#_REMOVEBOOKINGFORM</code> to insert a form where users, inserting their name and email address, can remove their bookings.', 'events-made-easy' ) . __( '<br>Use <code>#_ADDBOOKINGFORM_IF_NOT_REGISTERED</code> to insert the booking form only if the user has not registered yet. Similar use <code>#_REMOVEBOOKINGFORM_IF_REGISTERED</code> to insert the booking removal form only if the user has already registered before. These two codes only work for WP users.', 'events-made-easy' ) . __( '<br> Use <code>#_DIRECTIONS</code> to insert a form so people can ask directions to the event.', 'events-made-easy' ) . __( '<br> Use <code>#_CATEGORIES</code> to insert a comma-separated list of categories an event is in.', 'events-made-easy' ) . __( '<br> Use <code>#_ATTENDEES</code> to get a list of the names attending the event.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Monthly period date format', 'events-made-easy' ), 'eme_show_period_monthly_dateformat', __( 'The format of the date-string used when you use showperiod=monthly as an option to &#91;the eme_events] shortcode, also used for monthly pagination. Use php date() compatible settings.', 'events-made-easy' ) . __( ' The default is: ', 'events-made-easy' ) . DEFAULT_SHOW_PERIOD_MONTHLY_DATEFORMAT );
			eme_options_input_text( __( 'Yearly period date format', 'events-made-easy' ), 'eme_show_period_yearly_dateformat', __( 'The format of the date-string used when you use showperiod=yearly as an option to &#91;the eme_events] shortcode, also used for yearly pagination. Use php date() compatible settings.', 'events-made-easy' ) . __( ' The default is: ', 'events-made-easy' ) . DEFAULT_SHOW_PERIOD_YEARLY_DATEFORMAT );
			eme_options_input_text( __( 'Events page title', 'events-made-easy' ), 'eme_events_page_title', __( 'The title on the multiple events page.', 'events-made-easy' ) );
			eme_options_input_text( __( 'No events message', 'events-made-easy' ), 'eme_no_events_message', __( 'The message displayed when no events are available.', 'events-made-easy' ) );
			?>
</table>
<h3><?php esc_html_e( 'Events filtering format', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
			eme_options_textarea( __( 'Default event list filtering format', 'events-made-easy' ), 'eme_filter_form_format', __( 'This defines the layout of the event list filtering form when using the shortcode <code>[eme_filterform]</code>. Use <code>#_FILTER_CATS</code>, <code>#_FILTER_LOCS</code>, <code>#_FILTER_TOWNS</code>, <code>#_FILTER_WEEKS</code>, <code>#_FILTER_MONTHS</code>.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=28'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			?>
</table>

			<?php
			break;
		case 'calendar':
			?>

<h3><?php esc_html_e( 'Calendar options', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_radio_binary( __( 'Hide past events?', 'events-made-easy' ), 'eme_cal_hide_past_events', __( 'Check this option if you want to hide past events in the calendar.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Show single event?', 'events-made-easy' ), 'eme_cal_show_single', __( 'Check this option if you want to immediately show the single event and not a list of events if there is only one event on a specific day.', 'events-made-easy' ) );
			?>
</table>
<h3><?php esc_html_e( 'Calendar format', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
			eme_options_input_text( __( 'Small calendar title', 'events-made-easy' ), 'eme_small_calendar_event_title_format', __( 'The format of the title, corresponding to the text that appears when hovering on an eventful calendar day.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Small calendar title separator', 'events-made-easy' ), 'eme_small_calendar_event_title_separator', __( 'The separator appearing on the above title when more than one event is taking place on the same day.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Full calendar events format', 'events-made-easy' ), 'eme_full_calendar_event_format', __( 'The format of each event when displayed in the full calendar. Remember to include <code>li</code> tags before and after the event.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'locations':
			?>

<h3><?php esc_html_e( 'Locations format', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_textarea( __( 'Default location list format header', 'events-made-easy' ), 'eme_location_list_format_header', sprintf( __( 'This content will appear just above your code for the default location list format. If you leave this empty, the value <code>%s</code> will be used.<br>Used by the shortcode <code>[eme_locations]</code>', 'events-made-easy' ), eme_esc_html( DEFAULT_LOCATION_LIST_HEADER_FORMAT ) ) );
				eme_options_textarea( __( 'Default location list item format', 'events-made-easy' ), 'eme_location_list_format_item', sprintf( __( 'The format of a location in a location list. If you leave this empty, the value <code>%s</code> will be used.<br>See the documentation for a list of available placeholders for locations.<br>Used by the shortcode <code>[eme_locations]</code>', 'events-made-easy' ), eme_esc_html( DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT ) ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=26'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Default location list format footer', 'events-made-easy' ), 'eme_location_list_format_footer', sprintf( __( 'This content will appear just below your code for the default location list format. If you leave this empty, the value <code>%s</code> will be used.<br>Used by the shortcode <code>[eme_locations]</code>', 'events-made-easy' ), eme_esc_html( DEFAULT_LOCATION_LIST_FOOTER_FORMAT ) ) );

				eme_options_input_text( __( 'Single location page title format', 'events-made-easy' ), 'eme_location_page_title_format', __( 'The format of a single location page title.<br>Follow the previous formatting instructions.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Single location html title format', 'events-made-easy' ), 'eme_location_html_title_format', __( 'The format of a single location html page title.<br>Follow the previous formatting instructions.', 'events-made-easy' ) . __( ' The default is: ', 'events-made-easy' ) . DEFAULT_LOCATION_HTML_TITLE_FORMAT );
				eme_options_textarea( __( 'Default single location page format', 'events-made-easy' ), 'eme_single_location_format', __( 'The format of a single location page.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=26'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Default location event list format', 'events-made-easy' ), 'eme_location_event_list_item_format', __( 'The format of the events list inserted in the location page through the <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> element. <br> Follow the events formatting instructions', 'events-made-easy' ) );
				eme_options_textarea( __( 'Default no events message', 'events-made-easy' ), 'eme_location_no_events_message', __( 'The message to be displayed in the list generated by <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> when no events are available.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'members':
			?>

<h3><?php esc_html_e( 'Membership options', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_input_text( __( 'Membership form login required text', 'events-made-easy' ), 'eme_membership_login_required_string', __( 'The text shown instead of the membership form if a person needs to be logged in in order to register. You can use membership placeholders here.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Page Access Denied message', 'events-made-easy' ), 'eme_page_access_denied', __( 'The message shown if people are not allowed to view certain WP pages because of EME membership restrictions. You can use conditional tags and generic placeholders here.', 'events-made-easy' ), 1, 1 );
				eme_options_input_text( __( 'URL to redirect membership protected pages to', 'events-made-easy' ), 'eme_redir_protected_pages_url', __( 'When a person that is not logged-in wants to look at a page that requires some EME membership, that person also gets to see the page access denied message above. If you want to redirect to some url instead, enter it here. The permalink of the current visited page will be added to the mentioned url query string with a parameter called "redirect".', 'events-made-easy' ) );
				eme_options_textarea( __( 'Default format for membership attendance check when not logged in', 'events-made-easy' ), 'eme_membership_unauth_attendance_msg', __( 'The format of the text shown when the QR code of an active member is scanned by a person not logged in. All member and membership placeholders can be used.', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'Default format for membership attendance check when logged in', 'events-made-easy' ), 'eme_membership_attendance_msg', __( 'The format of the text shown when the QR code of an active member is scanned by a person with sufficient rights. All member and membership placeholders can be used.', 'events-made-easy' ), 1, 1 );
				eme_options_radio_binary( __( 'Show custom people fields in members overview', 'events-made-easy' ), 'eme_members_show_people_info', __( 'To limit the number of fields shown/hidden in the overview of members, the custom fields of type "people" are not added by default. Check this option if you want those to be available in the members overview too.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'rss':
			?>

<h3><?php esc_html_e( 'RSS and ICAL feed format', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_input_text( __( 'RSS main title', 'events-made-easy' ), 'eme_rss_main_title', __( 'The main title of your RSS events feed.', 'events-made-easy' ) );
				eme_options_input_text( __( 'RSS main description', 'events-made-easy' ), 'eme_rss_main_description', __( 'The main description of your RSS events feed.', 'events-made-easy' ) );
				eme_options_input_text( __( 'RSS title format', 'events-made-easy' ), 'eme_rss_title_format', __( 'The format of the title of each item in the events RSS feed.', 'events-made-easy' ) );
				eme_options_textarea( __( 'RSS description format', 'events-made-easy' ), 'eme_rss_description_format', __( 'The format of the description of each item in the events RSS feed. Follow the previous formatting instructions.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'RSS Pubdate usage', 'events-made-easy' ), 'eme_rss_show_pubdate', __( 'Show the event creation/modification date as PubDate info in the in the events RSS feed.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'RSS Pubdate is start date', 'events-made-easy' ), 'eme_rss_pubdate_startdate', __( 'If you select this, the pubDate field in RSS will be the event start date, not the modification date.', 'events-made-easy' ) );
				eme_options_input_text( __( 'ICAL title format', 'events-made-easy' ), 'eme_ical_title_format', __( 'The format of the title of each item in the events ICAL feed.', 'events-made-easy' ) );
				eme_options_input_text( __( 'ICAL description format', 'events-made-easy' ), 'eme_ical_description_format', __( 'The format of the description of each item in the events ICAL feed. Follow the previous formatting instructions.', 'events-made-easy' ) );
				eme_options_input_text( __( 'ICAL location format', 'events-made-easy' ), 'eme_ical_location_format', __( 'The format of the location of each item in the events ICAL feed (if a location is defined for that event). Use any regular location placeholders.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'rsvp':
			?>

<h3><?php esc_html_e( 'RSVP: registrations and bookings', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
				$indexed_users[-1] = __( 'Event author', 'events-made-easy' );
				$indexed_users    += eme_get_indexed_users();
				eme_options_select( __( 'Default contact person', 'events-made-easy' ), 'eme_default_contact_person', $indexed_users, __( 'Select the default contact person. This user will be employed whenever a contact person is not explicitly specified for an event', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default enable registrations for new events?', 'events-made-easy' ), 'eme_rsvp_reg_for_new_events', __( 'Check this option if you want to enable registrations by default for new events.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default require approval for registrations?', 'events-made-easy' ), 'eme_rsvp_require_approval', __( 'Check this option if you want by default that new registrations require approval.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default require user confirmation for registrations?', 'events-made-easy' ), 'eme_rsvp_require_user_confirmation', __( 'Check this option if you want by default that new registrations require confirmation of the person doing the booking.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Send out pending booking reminders', 'events-made-easy' ), 'eme_rsvp_pending_reminder_days', __( 'Set the number of days before reminder emails will be sent for pending bookings (counting from the start date of the event). If you want to send out multiple reminders, seperate the days here by commas. Leave empty for no reminder emails.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Send out approved booking reminders', 'events-made-easy' ), 'eme_rsvp_approved_reminder_days', __( 'Set the number of days before reminder emails will be sent for approved bookings (counting from the start date of the event). If you want to send out multiple reminders, seperate the days here by commas. Leave empty for no reminder emails.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default require WP membership to be able to register?', 'events-made-easy' ), 'eme_rsvp_registered_users_only', __( 'Check this option if you want by default that only logged-in users can book for an event.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Check required fields upon submit?', 'events-made-easy' ), 'eme_rsvp_check_required_fields', __( 'Check this option if you want to check on the server-side if all required fields have been completed upon RSVP form submit. You might want to disable this if your form uses eme_if to show/hide certain form fields, otherwise your form might not get submitted if the hidden fields are marked as required.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Default number of seats', 'events-made-easy' ), 'eme_rsvp_default_number_spaces', __( 'The default number of seats an event has.', 'events-made-easy' ) . ' ' . __( 'Enter 0 for no limit', 'events-made-easy' ) );
				eme_options_input_text( __( 'Min number of seats to book', 'events-made-easy' ), 'eme_rsvp_addbooking_min_spaces', __( 'The minimum number of seats a person can book in one go (it can be 0, for e.g. just an attendee list).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Max number of seats to book', 'events-made-easy' ), 'eme_rsvp_addbooking_max_spaces', __( 'The maximum number of seats a person can book in one go.', 'events-made-easy' ) );
				$eme_rsvp_start_number_days  = get_option( 'eme_rsvp_start_number_days' );
				$eme_rsvp_start_number_hours = get_option( 'eme_rsvp_start_number_hours' );
				$eme_rsvp_start_target   = get_option( 'eme_rsvp_start_target' );
				$eme_rsvp_end_number_days  = get_option( 'eme_rsvp_end_number_days' );
				$eme_rsvp_end_number_hours = get_option( 'eme_rsvp_end_number_hours' );
				$eme_rsvp_end_target   = get_option( 'eme_rsvp_end_target' );
			?>
	<tr style='vertical-align:top' id='eme_rsvp_start_number_row'>
		<th scope="row"><?php esc_html_e( 'By default allow RSVP from', 'events-made-easy' ); ?></th>
		<td>
		<input name="eme_rsvp_start_number_days" type="text" id="eme_rsvp_start_number_days" value="<?php echo eme_esc_html( $eme_rsvp_start_number_days ); ?>" size="4"> <?php esc_html_e( 'days', 'events-made-easy' ); ?>
		<input name="eme_rsvp_start_number_hours" type="text" id="eme_rsvp_start_number_hours" value="<?php echo eme_esc_html( $eme_rsvp_start_number_hours ); ?>" size="4"> <?php esc_html_e( 'hours', 'events-made-easy' ); ?>
			<?php
				$eme_rsvp_start_target_list = [
					'start' => __( 'starts', 'events-made-easy' ),
					'end'   => __( 'ends', 'events-made-easy' ),
				];
				esc_html_e( 'before the event ', 'events-made-easy' );
				echo eme_ui_select( $eme_rsvp_start_target, 'eme_rsvp_start_target', $eme_rsvp_start_target_list );
				?>
		<br><?php esc_html_e( '(0 for both days and hours indicates no limit)', 'events-made-easy' ); ?>
		</td>
	</tr>
	<tr style='vertical-align:top' id='eme_rsvp_end_number_row'>
		<th scope="row"><?php esc_html_e( 'By default allow RSVP until this many', 'events-made-easy' ); ?></th>
		<td>
		<input name="eme_rsvp_end_number_days" type="text" id="eme_rsvp_end_number_days" value="<?php echo eme_esc_html( $eme_rsvp_end_number_days ); ?>" size="4"> <?php esc_html_e( 'days', 'events-made-easy' ); ?>
		<input name="eme_rsvp_end_number_hours" type="text" id="eme_rsvp_end_number_hours" value="<?php echo eme_esc_html( $eme_rsvp_end_number_hours ); ?>" size="4"> <?php esc_html_e( 'hours', 'events-made-easy' ); ?>
			<?php
				$eme_rsvp_end_target_list = [
					'start' => __( 'starts', 'events-made-easy' ),
					'end'   => __( 'ends', 'events-made-easy' ),
				];
				esc_html_e( 'before the event ', 'events-made-easy' );
				echo eme_ui_select( $eme_rsvp_end_target, 'eme_rsvp_end_target', $eme_rsvp_end_target_list );
				?>
		<br><?php esc_html_e( '(0 for both days and hours indicates no limit)', 'events-made-easy' ); ?>
		</td>
	</tr>
			<?php
			eme_options_input_text( __( 'RSVP cancel cutoff before event starts', 'events-made-easy' ), 'eme_cancel_rsvp_days', __( 'Allow RSVP cancellation until this many days before the event starts.', 'events-made-easy' ) );
			eme_options_input_text( __( 'RSVP cancel cutoff booking age', 'events-made-easy' ), 'eme_cancel_rsvp_age', __( 'Allow RSVP cancellation until this many days after the booking has been made.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'RSVP check no accents (diacritics)?', 'events-made-easy' ), 'eme_rsvp_check_without_accents', __( 'Check this option if you want to have the RSVP forms to also check for first name and last name without accents (diacritics) when required (when you require unique registrations or the cancel form), to accomodate languages where people use both (e.g. "Será" and "Sera").', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Hide fully booked events?', 'events-made-easy' ), 'eme_rsvp_hide_full_events', __( 'Check this option if you want to hide events that are fully booked from the calendar and events listing in the front.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Hide RSVP ended events?', 'events-made-easy' ), 'eme_rsvp_hide_rsvp_ended_events', __( 'Check this option if you want to hide events that no longer allow bookings.', 'events-made-easy' ) . '<br>' . __( 'WARNING: This might throw of the limit parameter for the eme_events shortcode if that parameter is used: in case the option to hide RSVP ended events is selected, the number of events shown might be less than indicated with the limit option.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Add booking form submit text', 'events-made-easy' ), 'eme_rsvp_addbooking_submit_string', __( 'The string of the submit button on the add booking form', 'events-made-easy' ) );
			eme_options_input_text( __( 'Cancel booking form submit text', 'events-made-easy' ), 'eme_rsvp_delbooking_submit_string', __( 'The string of the submit button on the cancel booking form', 'events-made-easy' ) );
			eme_options_input_text( __( 'Event fully booked text', 'events-made-easy' ), 'eme_rsvp_full_string', __( 'The text shown on the booking form if no seats are available anymore.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Booking on waiting list text', 'events-made-easy' ), 'eme_rsvp_on_waiting_list_string', __( 'The text shown on the booking form if this booking will be put on the waiting list.', 'events-made-easy' ) );
			eme_options_input_text( __( 'RSVP not yet allowed text', 'events-made-easy' ), 'eme_rsvp_not_yet_allowed_string', __( 'The text shown on the booking form if bookings are not yet allowed on that date and time.', 'events-made-easy' ) );
			eme_options_input_text( __( 'RSVP no longer allowed text', 'events-made-easy' ), 'eme_rsvp_no_longer_allowed_string', __( 'The text shown on the booking form if an event no longer allows bookings.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Cancel no longer allowed text', 'events-made-easy' ), 'eme_rsvp_cancel_no_longer_allowed_string', __( 'The text shown on the cancel booking form if an event no longer allows bookings.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Booking form login required text', 'events-made-easy' ), 'eme_rsvp_login_required_string', __( 'The text shown instead of the booking form if a person needs to be logged in in order to register.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Booking form invitation required text', 'events-made-easy' ), 'eme_rsvp_invitation_required_string', __( 'The text shown instead of the booking form if an invitation is required and the person is not using a correct invitation link.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Email already registered text', 'events-made-easy' ), 'eme_rsvp_email_already_registered_string', __( 'The text shown if the email used to register is already used by another booking and the email is only allowed to register once.', 'events-made-easy' ) . '<br>' . sprintf( __( "For all placeholders you can use here, see <a target='_blank' href='%s'>the documentation</a>", 'events-made-easy' ), '//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-2-events/' ) );
			eme_options_input_text( __( 'Person already registered text', 'events-made-easy' ), 'eme_rsvp_person_already_registered_string', __( 'The text shown if the person (combo of last name/first name/email) used to register is already used by another booking and the person is allowed to register once.', 'events-made-easy' ) . '<br>' . sprintf( __( "For all placeholders you can use here, see <a target='_blank' href='%s'>the documentation</a>", 'events-made-easy' ), '//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-2-events/' ) );
			eme_options_input_text( __( 'Attendees list format', 'events-made-easy' ), 'eme_attendees_list_format', __( 'The format for the attendees list when using the <code>#_ATTENDEES</code> placeholder.', 'events-made-easy' ) . '<br>' . __( 'For all placeholders you can use here, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=48'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_radio_binary( __( 'Attendees list ignore pending', 'events-made-easy' ), 'eme_attendees_list_ignore_pending', __( 'Whether or not to ignore pending bookings when using the <code>#_ATTENDEES</code> placeholder.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Bookings list header format', 'events-made-easy' ), 'eme_bookings_list_header_format', __( 'The header format for the bookings list when using the <code>#_BOOKINGS</code> placeholder.', 'events-made-easy' ) . sprintf( __( " The default is '%s'", 'events-made-easy' ), eme_esc_html( DEFAULT_BOOKINGS_LIST_HEADER_FORMAT ) ) );
			eme_options_input_text( __( 'Bookings list format', 'events-made-easy' ), 'eme_bookings_list_format', __( 'The format for the bookings list when using the <code>#_BOOKINGS</code> placeholder.', 'events-made-easy' ) . '<br>' . __( 'For all placeholders you can use here, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=45'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' . '<br>' . __( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=44'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Bookings list footer format', 'events-made-easy' ), 'eme_bookings_list_footer_format', __( 'The footer format for the bookings list when using the <code>#_BOOKINGS</code> placeholder.', 'events-made-easy' ) . sprintf( __( " The default is '%s'", 'events-made-easy' ), eme_esc_html( DEFAULT_BOOKINGS_LIST_FOOTER_FORMAT ) ) );
			eme_options_radio_binary( __( 'Ignore pending bookings in the bookings list', 'events-made-easy' ), 'eme_bookings_list_ignore_pending', __( 'Whether or not to ignore pending bookings when using the <code>#_BOOKINGS</code> placeholder.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Check waitinglist when seats become available', 'events-made-easy' ), 'eme_check_free_waiting', __( 'Automatically take a booking from the waiting list when seats become available again', 'events-made-easy' ) );

			eme_options_textarea( __( 'Booking recorded message', 'events-made-easy' ), 'eme_registration_recorded_ok_html', __( 'The text (html allowed) shown to the user when the booking has been made successfully.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1 );
			eme_options_radio_binary( __( 'Show RSVP form again after booking?', 'events-made-easy' ), 'eme_rsvp_show_form_after_booking', __( "Uncheck this option if you don't want to show the RSVP booking form again after a successful booking.", 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Allow overbooking?', 'events-made-easy' ), 'eme_rsvp_admin_allow_overbooking', __( 'Check this option if you want to allow overbookings when adding/editing an booking in the admin interface.', 'events-made-easy' ) );
			?>
</table>

<h3><?php esc_html_e( 'RSVP forms format', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
			eme_options_textarea( __( 'Booking form format', 'events-made-easy' ), 'eme_registration_form_format', __( 'The layout of the form for bookings. #_NAME, #_EMAIL and #_SEATS are obligated fields, if not present then the form will not be shown.', 'events-made-easy' ) . '<br>' . __( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=44'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', 1 );
			eme_options_textarea( __( 'Cancel all bookings form format', 'events-made-easy' ), 'eme_cancel_form_format', __( 'The layout of the cancel form generated by [eme_cancel_all_bookings_form], used to cancel all bookings for one event. #_NAME and #_EMAIL are obligated fields, if not present then the form will not be shown.', 'events-made-easy' ) . '<br>' . __( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=5950'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', 1 );
			eme_options_textarea( __( 'Cancel one payment form format', 'events-made-easy' ), 'eme_cancel_payment_form_format', __( 'The layout of the cancel form generated when going to the #_CANCEL_URL (and related) placeholder, used to cancel all bookings related to 1 payment. #_SUBMIT, and #_CANCEL_PAYMENT_LINE (see the value of the next options below ) are the obligated fields, if not present then the form will not be shown.', 'events-made-easy' ) . '<br>' . __( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=5950'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', 1 );
			eme_options_textarea( __( 'Cancel one payment form: booking line format', 'events-made-easy' ), 'eme_cancel_payment_line_format', __( "When canceling a payment, the payment can consist of multiple bookings. This options defines the format of one booking line in the form (and will get repeated for each booking), and can only be used in the 'Cancel one payment form format' mentioned above and in the 'Cancelled payment format' mentioned below, by using the placeholder #_CANCEL_PAYMENT_LINE", 'events-made-easy' ), 1 );
			eme_options_textarea( __( 'Cancelled payment format', 'events-made-easy' ), 'eme_cancelled_payment_format', __( 'This options defines the format of the message shown when a payment has been cancelled. Can contain all people placeholders and #_CANCEL_PAYMENT_LINE (repeated for each booking)', 'events-made-easy' ), 1 );
			?>
</table>

			<?php
			break;
		case 'tasks':
			?>
<h3><?php esc_html_e( 'Event tasks options', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
				eme_options_radio_binary( __( 'By default require WP membership to be able to sign up for tasks?', 'events-made-easy' ), 'eme_task_registered_users_only', __( 'Check this option if you want by default that only logged-in users can sign up for tasks.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default require sign up approval for tasks?', 'events-made-easy' ), 'eme_task_requires_approval', __( 'Check this option if you want by default that signups for tasks need to be approved.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Allow overlap for task signups?', 'events-made-easy' ), 'eme_task_allow_overlap', __( 'Check this option if you want to allow a person to sign up for tasks that overlap in time.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Task signup form format (task entry part)', 'events-made-easy' ), 'eme_task_form_taskentry_format', __( 'The layout of one task people can select to signup for (repeated for each task).', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the shortcode eme_tasks_signupform if the option "template_id" is not used for that shortcode.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-17-tasks-signup-form/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Task signup form format (personal info part)', 'events-made-easy' ), 'eme_task_form_format', __( 'The layout of the section of the task signup form where the info concerning lastname/firstname and email is collected.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the shortcode eme_tasks_signupform if the option "signupform_template_id" is not used for that shortcode.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-17-tasks-signup-form/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Task participants format', 'events-made-easy' ), 'eme_task_signup_format', __( 'The layout of one line in the list of signups for tasks (generated by #_TASKSIGNUPS or the eme_tasks_signups shortcode).', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the shortcode eme_tasks_signups if the option "template_id" is not used for that shortcode.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Signup recorded message', 'events-made-easy' ), 'eme_task_signup_recorded_ok_html', __( 'The text (html allowed) shown to the user when the task signup has been made successfully.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', 1 );
				#eme_options_textarea( __( 'Signup cancelled message', 'events-made-easy' ), 'eme_task_signup_cancelled_ok_html', __( 'The text (html allowed) shown to the user when the task signup has been successfully cancelled by the user.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', 1 );
				eme_options_input_text( __( 'Send out reminder emails', 'events-made-easy' ), 'eme_task_reminder_days', __( 'Set the number of days before task signup reminder emails will be sent (counting from the start date of the task). If you want to send out multiple reminders, seperate the days here by commas. Leave empty for no reminder emails.', 'events-made-easy' ) );

			?>
</table>

			<?php
			break;
		case 'mail':
			?>
<h3><?php esc_html_e( 'Email options', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
				eme_options_radio_binary( __( 'Enable the RSVP email notifications?', 'events-made-easy' ), 'eme_rsvp_mail_notify_is_active', __( 'Check this option if you want to receive an email when someone books seats for your events.', 'events-made-easy' ) );
			?>
</table>
<table id="rsvp_mail_notify-data" class='form-table'>
			<?php
			eme_options_radio_binary( __( 'Enable pending RSVP emails?', 'events-made-easy' ), 'eme_rsvp_mail_notify_pending', __( 'Check this option if you want to send mails for pending bookings.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Enable approved RSVP emails?', 'events-made-easy' ), 'eme_rsvp_mail_notify_approved', __( 'Check this option if you want to send mails for approved bookings.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Enable paid RSVP emails?', 'events-made-easy' ), 'eme_rsvp_mail_notify_paid', __( 'Check this option if you want to send mails when a payment arrives via a payment gateway or when a booking is marked as paid.', 'events-made-easy' ) );
			?>
</table>
<table class='form-table'>
			<?php
			eme_options_radio_binary( __( 'Send HTML mails', 'events-made-easy' ), 'eme_mail_send_html', __( 'Check this option if you want to use html in the mails being sent.', 'events-made-easy' ) );
			if ( eme_is_datamaster() ) {
				eme_options_radio_binary( __( 'Use mail queuing?', 'events-made-easy' ), 'eme_queue_mails', __( 'If activated, you can plan mails for sending at a later date and time.', 'events-made-easy' ) . '<br><b>' . __( 'It is recommended to activate this option.', 'events-made-easy' ) . '</b>' );
				?>
	<tr style='vertical-align:top' id='eme_queued_mails_options_row'>
		<th scope="row"><?php esc_html_e( 'Email queue settings', 'events-made-easy' ); ?></th>
		<td>
		<label for="eme_cron_send_queued">
				<?php
					$eme_cron_queue_count = intval( get_option( 'eme_cron_queue_count' ) );
					esc_html_e( 'Send out queued mails in batches of ', 'events-made-easy' );
				?>
		</label>
		<input type="number" id="eme_cron_queue_count" name="eme_cron_queue_count" size="6" maxlength="6" min="1" max="999999" step="1" value="<?php echo $eme_cron_queue_count; ?>">&nbsp;
		<select name="eme_cron_send_queued">
		<option value=""><?php esc_html_e( 'Not scheduled', 'events-made-easy' ); ?></option>
				<?php
				$schedules = wp_get_schedules();
				$scheduled = wp_get_schedule( 'eme_cron_send_queued' );
				foreach ( $schedules as $key => $schedule ) {
					$selected = ( $key == $scheduled ) ? 'selected="selected"' : '';
					print "<option $selected value='$key'>" . $schedule['display'] . '</option>';
				}
				?>
		</select>
		</td>
	</tr>
				<?php
				eme_options_input_int( __( 'Pause between mails', 'events-made-easy' ), 'eme_mail_sleep', __( 'Indicate how much time (in microseconds, one microsecond being one millionth of a second) to wait between queued mails being sent. By default this is 0, meaning EME sends mails in bursts based on your mail queue settings. This option can be used to send mails more slowly, but be aware to not cause PHP timeouts.', 'events-made-easy' ) );
			} else {
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Email queuing', 'events-made-easy' ) ));
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Pause between mails', 'events-made-easy' ) ));
			}
			eme_options_radio_binary( __( 'Read tracking', 'events-made-easy' ), 'eme_mail_tracking', __( 'Add an image (1x1 transparant pixel) to html mails so you can track if people opened the mail or not (be aware that people can easily bypass this by disabling images in their mail client). As this might be a privacy issue, it is deactivated by default.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'MassMail', 'events-made-easy' ), 'eme_people_massmail', __( "Should new persons in the database be considered for massmailing or not? This setting is used if you don't ask for opt-in/out info in e.g. the RSVP form. Warning: setting this to 'yes' is not GDPR compliant if you don't ask for a person's mail preferences.", 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Massmail popup', 'events-made-easy' ), 'eme_massmail_popup', __( 'If a person chooses to not receive mail via #_OPT_IN or #_OPT_OUT, you can optionally show a popup asking if they are sure about this.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Massmail popup text', 'events-made-easy' ), 'eme_massmail_popup_text', __( 'The text shown in the Massmail popup window.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Newsletter', 'events-made-easy' ), 'eme_people_newsletter', __( "Should new persons receive the automatic EME newsletter concerning new events if it is scheduled to go out? Warning: setting this to 'yes' is not GDPR compliant, you should ask people to subscribe to the relevant groups and/or newsletter.", 'events-made-easy' ) );
			eme_options_input_text( __( 'Default email sender name', 'events-made-easy' ), 'eme_mail_sender_name', __( 'The default name on emails when EME sends mails concerning GDPR, mailing subscribe/unsubscribe or birthdays. If left empty, the name of the default contact person for RSVP mails will be used (or the blog admin if empty).', 'events-made-easy' ) );
			eme_options_input_text( __( 'Default email sender address', 'events-made-easy' ), 'eme_mail_sender_address', __( 'The default email address with which EME mails concerning GDPR, mailing subscribe/unsubscribe or birthdays will be sent. If left empty, the address of the default contact person for RSVP mails will be used (or the blog admin if empty). If you use Gmail to send mails, this must correspond with your Gmail account.', 'events-made-easy' ), 'email' );
			eme_options_radio_binary( __( 'Force sender address everywhere', 'events-made-easy' ), 'eme_mail_force_from', __( 'Force the configured sender address to be used for all outgoing emails. If not activated, the name and email address of the default contact person for RSVP mails will be used for generic mails, while for event or membership related mails the configured contact person will be used (or the blog admin if empty).', 'events-made-easy' ) . '<br>' . __( 'Remark: if - for certain events or memberships - the sender address of the contact person is identical to the one configured here, but only the sender name differs, the name from the contact person of the event or membership will be used.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Default email BCC', 'events-made-easy' ), 'eme_mail_bcc_address', __( 'Insert an email address that will be added in Bcc to all outgoing mails (multiple addresses are to be separated by comma or semicolon). Can be left empty.', 'events-made-easy' ) );
			eme_options_select(
			    __( 'Email sending method', 'events-made-easy' ),
			    'eme_mail_send_method',
			    [
					'smtp'     => 'SMTP',
					'mail'     => __( 'PHP email function', 'events-made-easy' ),
					'sendmail' => 'Sendmail',
					'qmail'    => 'Qmail',
					'wp_mail'  => 'Wordpress Email (default)',
				],
			    __( 'Select how you want to send out emails.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'SMTP host', 'events-made-easy' ), 'eme_smtp_host', __( "The SMTP host. Usually it corresponds to 'localhost'.", 'events-made-easy' ) );
			eme_options_input_text( __( 'SMTP port', 'events-made-easy' ), 'eme_smtp_port', __( "The port through which you email notifications will be sent. Make sure the firewall doesn't block this port", 'events-made-easy' ) );
			eme_options_select(
			    __( 'SMTP encryption method', 'events-made-easy' ),
			    'eme_smtp_encryption',
			    [
					'none' => __( 'None', 'events-made-easy' ),
					'tls'  => __( 'TLS', 'events-made-easy' ),
					'ssl'  => __(
					    'SSL',
					    'events-made-easy'
					),
				],
			    __( 'Select the SMTP encryption method.', 'events-made-easy' )
			);
			eme_options_radio_binary( __( 'Use SMTP authentication?', 'events-made-easy' ), 'eme_smtp_auth', __( 'SMTP authentication is often needed. If you use Gmail, make sure to set this parameter to Yes', 'events-made-easy' ) );
			eme_options_input_text( __( 'SMTP username', 'events-made-easy' ), 'eme_smtp_username', __( 'Insert the username to be used to access your SMTP server.', 'events-made-easy' ) );
			eme_options_input_password( __( 'SMTP password', 'events-made-easy' ), 'eme_smtp_password', __( 'Insert the password to be used to access your SMTP server', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Verify SMTP certificates?', 'events-made-easy' ), 'eme_smtp_verify_cert', __( 'Uncheck this option if you have issues sending mail via secure SMTP due to mismatching certificates. Since this in fact defeats the purpose of having certificates, it is not recommended to use it, but sometimes it is needed at specific hosting providers. This has only an effect for private ip ranges (like e.g. 127.0.0.1, localhost, ...), for public mailservers this is not allowed.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Debug SMTP?', 'events-made-easy' ), 'eme_smtp_debug', __( 'Check this option if you have issues sending mail via SMTP. Only do this for debugging purposes and deactivate it afterwards!', 'events-made-easy' ) );
			$test_url = admin_url( 'admin.php?page=eme-emails#tab-testmail' );
			eme_options_textarea( __( 'Email blacklist', 'events-made-easy' ), 'eme_mail_blacklist', __( 'A list of emails (one per line) that will not be accepted in EME. Examples can be ".com" (to not accept anything from ".com"), "anything.com" (to not accept addresses ending in "anything.com"), or even specific email addresses.', 'events-made-easy' ) );
			echo "<tr><th colspan='2'>" . sprintf( __( "Hint: after you changed your mail settings, go to the <a href='%s'>Emails management</a> submenu to send a test mail.", 'events-made-easy' ), esc_url($test_url) ) . '</td></tr>';
			?>
</table>
			<?php
			break;
		case 'mailtemplates':
			if ( get_option( 'eme_mail_send_html' ) == '1' ) {
				$use_html_editor = 1;
				$use_full        = 1;
			} else {
				$use_html_editor = 0;
				$use_full        = 0;
			}
			?>

<div id="eme-mailtemplates-accordion">
<h3><?php esc_html_e( 'Full name format', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
				eme_options_input_text( __( 'Full name format', 'events-made-easy' ), 'eme_full_name_format', __( 'The format of the full name of a person, used when sending mails to a person or displaying the full name. Only 2 placeholders can and need to be used: #_FIRSTNAME and #_LASTNAME. The default is "#_LASTNAME #_FIRSTNAME".', 'events-made-easy' ) );
			?>
			 
</table>
</div>
<h3><?php esc_html_e( 'Booking Made or Approved Email', 'events-made-easy' ); ?></h3>
<div>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			} elseif ( ! get_option( 'eme_rsvp_mail_notify_approved' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated for bookings made or approved, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			esc_html_e( 'When an event is configured to auto-approve bookings after payment and you have selected to send out payment mails and the total amount to pay is not 0, this mail is not sent but the mail concerning a booking being paid is sent when a pending booking is marked as paid.', 'events-made-easy' );
			?>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Booking Made Email Subject', 'events-made-easy' ), 'eme_respondent_email_subject', __( 'The subject of the email sent to the respondent when a booking is made (not pending) or approved.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Made Email Body', 'events-made-easy' ), 'eme_respondent_email_body', __( 'The body of the email sent to the respondent when a booking is made (not pending) or approved.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Booking Made Email Subject', 'events-made-easy' ), 'eme_contactperson_email_subject', __( 'The subject of the email which will be sent to the contact person when a booking is made.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Booking Made Email Body', 'events-made-easy' ), 'eme_contactperson_email_body', __( 'The body of the email which will be sent to the contact person when a booking is made.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
			 
	<tr><th scope='row'><?php esc_html_e( 'Booking made mail attachments', 'events-made-easy' ); ?></th>
<td>
<span id="booking_attach_links">
			<?php
			$attachment_ids = get_option( 'eme_booking_attach_ids' );
			if ( ! empty( $attachment_ids ) ) {
				$attachment_id_arr = array_unique( explode( ',', $attachment_ids ));
				foreach ( $attachment_id_arr as $attachment_id ) {
					$attach_link = eme_get_attachment_link( $attachment_id );
					if ( ! empty( $attach_link ) ) {
						echo $attach_link;
						echo '<br \>';
					}
				}
			} else {
				$attachment_ids = '';
			}
			?>
			</span>
	<input type="hidden" name="eme_booking_attach_ids" id="eme_booking_attach_ids" value="<?php echo $attachment_ids; ?>">
<input type="button" name="booking_attach_button" id="booking_attach_button" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<input type="button" name="booking_remove_attach_button" id="booking_remove_attach_button" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>" class="button-secondary action">
	<br><?php esc_html_e( 'Optionally add attachments to the mail when a new booking is made.', 'events-made-easy' ); ?>
</td></tr>
<?php
        $pdftemplates = eme_get_templates_array_by_id( 'pdf', 1 );
        if (!empty($pdftemplates)) {
		$title = __( 'PDF templates as attachments', 'events-made-easy' );
		$name  = 'eme_booking_attach_tmpl_ids';
		$description = __( 'Optionally add PDF templates as attachments to the mail.', 'events-made-easy' );
		eme_options_multiselect( $title, $name, $pdftemplates, $description, false, 'eme_select2_width50_class' );
        } else {
                esc_html_e( 'No PDF templates defined yet.', 'events-made-easy' );
        }
?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Pending Email', 'events-made-easy' ); ?></h3>
<div>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			} elseif ( ! get_option( 'eme_rsvp_mail_notify_pending' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated for pending bookings, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			?>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Booking Awaiting User Confirmation Email Subject', 'events-made-easy' ), 'eme_registration_userpending_email_subject', __( 'The subject of the email which will be sent to the respondent if the booking requires user confirmation.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Awaiting User Confirmation Email Body', 'events-made-easy' ), 'eme_registration_userpending_email_body', __( 'The body of the email which will be sent to the respondent if the booking requires user confirmation.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Booking Pending Email Subject', 'events-made-easy' ), 'eme_registration_pending_email_subject', __( 'The subject of the email which will be sent to the respondent if the booking requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Pending Email Body', 'events-made-easy' ), 'eme_registration_pending_email_body', __( 'The body of the email which will be sent to the respondent if the booking requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Pending Booking Email Subject', 'events-made-easy' ), 'eme_contactperson_pending_email_subject', __( 'The subject of the email which will be sent to the contact person if a booking requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Pending Booking Email Body', 'events-made-easy' ), 'eme_contactperson_pending_email_body', __( 'The body of the email which will be sent to the contact person if a booking requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
			 
	<tr><th scope='row'><?php esc_html_e( 'Pending mail attachments', 'events-made-easy' ); ?></th>
<td>
<span id="pending_attach_links">
			<?php
			$attachment_ids = get_option( 'eme_pending_attach_ids' );
			if ( ! empty( $attachment_ids ) ) {
					$attachment_id_arr = explode( ',', $attachment_ids );
				foreach ( $attachment_id_arr as $attachment_id ) {
					$attach_link = eme_get_attachment_link( $attachment_id );
					if ( ! empty( $attach_link ) ) {
						echo $attach_link;
						echo '<br \>';
					}
				}
			} else {
				$attachment_ids = '';
			}
			?>
			</span>
<input type="hidden" name="eme_pending_attach_ids" id="eme_pending_attach_ids" value="<?php echo $attachment_ids; ?>">
<input type="button" name="pending_attach_button" id="pending_attach_button" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<input type="button" name="pending_remove_attach_button" id="pending_remove_attach_button" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<br><?php esc_html_e( 'Optionally add attachments to the mail when a booking is pending.', 'events-made-easy' ); ?>
</td></tr>
<?php
        $pdftemplates = eme_get_templates_array_by_id( 'pdf', 1 );
        if (!empty($pdftemplates)) {
		$title = __( 'PDF templates as attachments', 'events-made-easy' );
		$name  = 'eme_pending_attach_tmpl_ids';
		$description = __( 'Optionally add PDF templates as attachments to the mail.', 'events-made-easy' );
		eme_options_multiselect( $title, $name, $pdftemplates, $description, false, 'eme_select2_width50_class' );
        } else {
                esc_html_e( 'No PDF templates defined yet.', 'events-made-easy' );
        }
?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Updated Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			eme_options_input_text( __( 'Booking Updated Email Subject', 'events-made-easy' ), 'eme_registration_updated_email_subject', __( 'The subject of the email which will be sent to the respondent if the booking has been updated by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Updated Email Body', 'events-made-easy' ), 'eme_registration_updated_email_body', __( 'The body of the email which will be sent to the respondent if the booking has been updated by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Reminder Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			eme_options_input_text( __( 'Pending Booking Reminder Email Subject', 'events-made-easy' ), 'eme_registration_pending_reminder_email_subject', __( 'The subject of the email which will be sent to the respondent as a reminder of a pending booking.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Pending Booking Reminder Email Body', 'events-made-easy' ), 'eme_registration_pending_reminder_email_body', __( 'The body of the email which will be sent to the respondent as a reminder of a pending booking.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Accepted Booking Reminder Email Subject', 'events-made-easy' ), 'eme_registration_reminder_email_subject', __( 'The subject of the email which will be sent to the respondent as a reminder of an approved booking.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Accepted Booking Reminder Email Body', 'events-made-easy' ), 'eme_registration_reminder_email_body', __( 'The body of the email which will be sent to the respondent as a reminder of an approved booking.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Cancelled Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			eme_options_input_text( __( 'Booking Cancelled Email Subject', 'events-made-easy' ), 'eme_registration_cancelled_email_subject', __( 'The subject of the email which will be sent to the respondent when he cancels all his bookings for an event.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Cancelled Email Body', 'events-made-easy' ), 'eme_registration_cancelled_email_body', __( 'The body of the email which will be sent to the respondent when he cancels all his bookings for an event.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Cancelled Booking Email Subject', 'events-made-easy' ), 'eme_contactperson_cancelled_email_subject', __( 'The subject of the email which will be sent to the contact person when a respondent cancels all his bookings for an event.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Cancelled Booking Email Body', 'events-made-easy' ), 'eme_contactperson_cancelled_email_body', __( 'The body of the email which will be sent to the contact person when a respondent cancels all his bookings for an event.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Deleted Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			eme_options_input_text( __( 'Booking Deleted Email Subject', 'events-made-easy' ), 'eme_registration_trashed_email_subject', __( 'The subject of the email which will be sent to the respondent if the booking is deleted by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_textarea( __( 'Booking Deleted Email Body', 'events-made-easy' ), 'eme_registration_trashed_email_body', __( 'The body of the email which will be sent to the respondent if the booking is deleted by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Paid Email', 'events-made-easy' ); ?></h3>
<div>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			} elseif ( ! get_option( 'eme_rsvp_mail_notify_paid' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated for paid bookings, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			?>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Booking Paid Email Subject', 'events-made-easy' ), 'eme_registration_paid_email_subject', __( 'The subject of the email which will be sent to the respondent when a booking is marked as paid.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Booking Paid Email Body', 'events-made-easy' ), 'eme_registration_paid_email_body', __( 'The body of the email which will be sent to the respondent when a booking is marked as paid.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a>.<br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Paid Email Subject', 'events-made-easy' ), 'eme_contactperson_paid_email_subject', __( 'The subject of the email which will be sent to the contact person when a booking is marked as paid (not via a payment gateway).', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Paid Email Body', 'events-made-easy' ), 'eme_contactperson_paid_email_body', __( 'The body of the email which will be sent to the contact person when a booking is marked as paid (not via a payment gateway).', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
		<tr><th scope='row'><?php esc_html_e( 'Booking paid mail attachments', 'events-made-easy' ); ?></th>
<td>
<span id="paid_attach_links">
			<?php
			$attachment_ids = get_option( 'eme_paid_attach_ids' );
			if ( ! empty( $attachment_ids ) ) {
					$attachment_id_arr = explode( ',', $attachment_ids );
				foreach ( $attachment_id_arr as $attachment_id ) {
					$attach_link = eme_get_attachment_link( $attachment_id );
					if ( ! empty( $attach_link ) ) {
						echo $attach_link;
						echo '<br \>';
					}
				}
			} else {
				$attachment_ids = '';
			}
			?>
			</span>
<input type="hidden" name="eme_paid_attach_ids" id="eme_paid_attach_ids" value="<?php echo $attachment_ids; ?>">
<input type="button" name="paid_attach_button" id="paid_attach_button" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<input type="button" name="paid_remove_attach_button" id="paid_remove_attach_button" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<br><?php esc_html_e( 'Optionally add attachments to the mail when a booking is paid.', 'events-made-easy' ); ?>
</td></tr>
<?php
        $pdftemplates = eme_get_templates_array_by_id( 'pdf', 1 );
        if (!empty($pdftemplates)) {
		$title = __( 'PDF templates as attachments', 'events-made-easy' );
		$name  = 'eme_paid_attach_tmpl_ids';
		$description = __( 'Optionally add PDF templates as attachments to the mail.', 'events-made-easy' );
		eme_options_multiselect( $title, $name, $pdftemplates, $description, false, 'eme_select2_width50_class' );
        } else {
                esc_html_e( 'No PDF templates defined yet.', 'events-made-easy' );
        }
?>
</table>
</div>

<h3><?php esc_html_e( 'Booking Payment Gateway Notification Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			if ( ! get_option( 'eme_rsvp_mail_notify_is_active' ) ) {
				print "<div class='info eme-message-admin'><p>" . __( 'RSVP notifications are not activated, so these mails will not be sent. Go in the Email settings to activate this if wanted.', 'events-made-easy' ) . '</p></div>';
			}
			eme_options_input_text( __( 'Contact Person Payment Notification Email Subject', 'events-made-easy' ), 'eme_contactperson_ipn_email_subject', __( 'The subject of the email which will be sent to the contact person when a payment notification is received via a payment gateway.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Payment Notification Email Body', 'events-made-easy' ), 'eme_contactperson_ipn_email_body', __( 'The body of the email which will be sent to the contact person when a payment notification is received via a payment gateway.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Mailing group subscription Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Subscribe email subject', 'events-made-easy' ), 'eme_sub_subject', __( 'The subject of the email which will be sent to the person asking to subscribe to a mailing group.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Subscribe email body', 'events-made-easy' ), 'eme_sub_body', __( 'The body of the email which will be sent to the person asking to subscribe to a mailing group.', 'events-made-easy' ) . '<br>' . __( 'Should contain at least the placeholder #_SUB_CONFIRM_URL (if this placeholder is not present, people will not be able to confirm their subscription). Next to that, #_LASTNAME and #_FIRSTNAME can also be used. You should also advise the person that this subscription confirmation link is only valid for a specific amount of time (typically one day).', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
	<tr><th scope='row'><?php esc_html_e( 'Subscribe mail attachments', 'events-made-easy' ); ?></th>
<td>
<span id="subscribe_attach_links">
			<?php
			$attachment_ids = get_option( 'eme_subscribe_attach_ids' );
			if ( ! empty( $attachment_ids ) ) {
					$attachment_id_arr = explode( ',', $attachment_ids );
				foreach ( $attachment_id_arr as $attachment_id ) {
					$attach_link = eme_get_attachment_link( $attachment_id );
					if ( ! empty( $attach_link ) ) {
						echo $attach_link;
						echo '<br \>';
					}
				}
			} else {
					$attachment_ids = '';
			}
			?>
			</span>
<input type="hidden" name="eme_subscribe_attach_ids" id="eme_subscribe_attach_ids" value="<?php echo $attachment_ids; ?>">
<input type="button" name="subscribe_attach_button" id="subscribe_attach_button" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<input type="button" name="subscribe_remove_attach_button" id="subscribe_remove_attach_button" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>" class="button-secondary action">
<br><?php esc_html_e( 'Optionally add attachments to the mail when someone subscribes to a mailing group.', 'events-made-easy' ); ?>
</td></tr>
			<?php
			eme_options_input_text( __( 'Unsubscribe email subject', 'events-made-easy' ), 'eme_unsub_subject', __( 'The subject of the email which will be sent to the person asking to unsubscribe.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Unsubscribe email body', 'events-made-easy' ), 'eme_unsub_body', __( 'The body of the email which will be sent to the person asking to unsubscribe.', 'events-made-easy' ) . '<br>' . __( 'Can contain all people placeholders and one additional required placeholder, namely #_UNSUB_CONFIRM_URL (which will be replaced with the unsubscribe confirmation url).', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Task Signup Pending Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Task Signup Pending Email Subject', 'events-made-easy' ), 'eme_task_signup_pending_email_subject', __( 'The subject of the email sent to the respondent when that person signs up for a task that requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Task Signup Pending Email Body', 'events-made-easy' ), 'eme_task_signup_pending_email_body', __( 'The body of the email sent to the respondent when that person signs up for a task that requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Task Signup Pending Email Subject', 'events-made-easy' ), 'eme_cp_task_signup_pending_email_subject', __( 'The subject of the email which will be sent to the contact person when someone signs up for a task that requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Task Signup Pending Email Body', 'events-made-easy' ), 'eme_cp_task_signup_pending_email_body', __( 'The body of the email which will be sent to the contact person when someone signs up for a task that requires approval.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
			 
</table>
</div>

<h3><?php esc_html_e( 'Task Signup Made Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Task Signup Made Email Subject', 'events-made-easy' ), 'eme_task_signup_email_subject', __( 'The subject of the email sent to the respondent when that person signs up for a task.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Task Signup Made Email Body', 'events-made-easy' ), 'eme_task_signup_email_body', __( 'The body of the email sent to the respondent when that person signs up for a task.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Task Signup Made Email Subject', 'events-made-easy' ), 'eme_cp_task_signup_email_subject', __( 'The subject of the email which will be sent to the contact person when someone signs up for a task.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Task Signup Made Email Body', 'events-made-easy' ), 'eme_cp_task_signup_email_body', __( 'The body of the email which will be sent to the contact person when someone signs up for a task.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
			 
</table>
</div>

<h3><?php esc_html_e( 'Task Signup Reminder Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Task Signup Reminder Email Subject', 'events-made-easy' ), 'eme_task_signup_reminder_email_subject', __( 'The subject of the reminder email which will be sent to the respondent.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Task Signup Reminder Email Body', 'events-made-easy' ), 'eme_task_signup_reminder_email_body', __( 'The body of the reminder email which will be sent to the respondent.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Task Signup Cancelled Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Task Signup Cancelled Email Subject', 'events-made-easy' ), 'eme_task_signup_cancelled_email_subject', __( 'The subject of the email which will be sent to the respondent when he himself cancels a task signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Task Signup Cancelled Email Body', 'events-made-easy' ), 'eme_task_signup_cancelled_email_body', __( 'The body of the email which will be sent to the respondent when he himself cancels a task signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			eme_options_input_text( __( 'Contact Person Task Signup Cancelled Email Subject', 'events-made-easy' ), 'eme_cp_task_signup_cancelled_email_subject', __( 'The subject of the email which will be sent to the contact person when a respondent cancels a task signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ) );
			eme_options_textarea( __( 'Contact Person Task Signup Cancelled Email Body', 'events-made-easy' ), 'eme_cp_task_signup_cancelled_email_body', __( 'The body of the email which will be sent to the contact person when a respondent cancels a task signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If you leave this empty, this mail will not be sent.', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Task Signup Deleted Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Task Signup Deleted Email Subject', 'events-made-easy' ), 'eme_task_signup_trashed_email_subject', __( 'The subject of the email which will be sent to the respondent if the task signup is deleted by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_textarea( __( 'Task Signup Deleted Email Body', 'events-made-easy' ), 'eme_task_signup_trashed_email_body', __( 'The body of the email which will be sent to the respondent if the task signup is deleted by an admin.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', $use_html_editor, $use_full );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Birthday Email', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Birthday Email Subject', 'events-made-easy' ), 'eme_bd_email_subject', __( 'The subject of the email which will be sent to people on their birthday (if active for them).', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_textarea( __( 'Birthday Email Body', 'events-made-easy' ), 'eme_bd_email_body', __( 'The body of the email which will be sent to  to people on their birthday (if active for them).', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-16-taskssignups/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>', $use_html_editor, $use_full );
			?>
</table>
</div>
</div>

			<?php
			break;
		case 'gdpr':
			?>
<h3><?php esc_html_e( 'GDPR: General Data Protection Regulation options', 'events-made-easy' ); ?></h3>
			<?php print esc_html__( 'For more info concerning GDPR, see', 'events-made-easy' ) . " <a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/6-shortcodes/eme_gdpr_approve/'>" . esc_html(sprintf( __( 'the documentation about the shortcode %s', 'events-made-easy' ), 'eme_gdpr_approve' )) . "</a>, <a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/6-shortcodes/eme_request_personal_info/'>" . esc_html(sprintf( __( 'the documentation about the shortcode %s', 'events-made-easy' ), 'eme_request_personal_info' )) . '</a> ' . esc_html__( 'and', 'events-made-easy' ) . " <a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/6-shortcodes/eme_change_personal_info/'>" .esc_html( sprintf( __( 'the documentation about the shortcode %s', 'events-made-easy' ), 'eme_change_personal_info' )) . '</a>'; ?>
<table class='form-table'>
			<?php
			if ( get_option( 'eme_mail_send_html' ) == '1' ) {
				$use_html_editor = 1;
				$use_full        = 1;
			} else {
				$use_html_editor = 0;
				$use_full        = 0;
			}
			if ( eme_is_datamaster() ) {
				eme_options_input_text( __( 'Automatically anonymize expired members', 'events-made-easy' ), 'eme_gdpr_anonymize_expired_member_days', __( 'Set the number of days after which expired members are automatically anonymized. Leave empty or 0 if not wanted.', 'events-made-easy' ) . '<br>' . __( 'Setting this to something greater than 0 helps you in achieving GDPR compliance. Recommended values are 180 (half a year) or 365 (one year).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically remove expired members', 'events-made-easy' ), 'eme_gdpr_remove_expired_member_days', __( 'Set the number of days after which expired members are automatically removed. Leave empty or 0 for no automatic removal.', 'events-made-easy' ) . '<br>' . __('If set, the value must be greater than the number of days after which you want expired members to be anonymized.','events-made-easy'). '<br>' . __( 'Setting this to something greater than 0 helps you in achieving GDPR compliance. Recommended values are 180 (half a year) or 365 (one year).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically anonimyze old bookings', 'events-made-easy' ), 'eme_gdpr_anonymize_old_bookings_days', __( 'Set the number of days after which old bookings for finished events are automatically anonimized. Bookings are not deleted to keep statistics and data on the old events. Leave empty or 0 if not wanted.', 'events-made-easy' ) . '<br>' . __( 'Setting this to something greater than 0 helps you in achieving GDPR compliance. Recommended values are 180 (half a year) or 365 (one year).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically remove old events', 'events-made-easy' ), 'eme_gdpr_remove_old_events_days', __( 'Set the number of days after which old events are automatically removed, including all their bookings. Leave empty or 0 for no automatic removal.', 'events-made-easy' ) . '<br>' . __( 'This value should be bigger than the number of days after which old bookings are anonimyzed.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically remove task signups for old events', 'events-made-easy' ), 'eme_gdpr_remove_old_signups_days', __( 'Automatically remove signups for tasks that have ended the specified number of days ago. Leave empty or 0 for no automatic removal.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically archive old mailings and remove old mails', 'events-made-easy' ), 'eme_gdpr_archive_old_mailings_days', __( 'Set the number of days after which mailings are automatically archived and old mails are removed. Leave empty or 0 for no automatic archiving or removal.', 'events-made-easy' ) . '<br>' . __( 'Setting this to something greater than 0 helps you in achieving GDPR compliance. Recommended values are 180 (half a year) or 365 (one year).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Automatically delete old attendance records', 'events-made-easy' ), 'eme_gdpr_remove_old_attendances_days', __( 'Set the number of days after which attendance records are automatically removed. Leave empty or 0 for no automatic removal.', 'events-made-easy' ) . '<br>' . __( 'Setting this to something greater than 0 helps you in achieving GDPR compliance. Recommended values are 180 (half a year) or 365 (one year).', 'events-made-easy' ) );
			} else {
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically remove expired members', 'events-made-easy' ) ));
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically anonimyze old bookings', 'events-made-easy' )) );
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically remove old events', 'events-made-easy' ) ));
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically remove task signups for old events', 'events-made-easy' )) );
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically archive old mailings and remove old mails', 'events-made-easy' ) ));
				echo '<br>';
				echo esc_html(sprintf( __( 'Multisite data sharing is activated for EME, the option "%s" will use the settings from the main site', 'events-made-easy' ), __( 'Automatically delete old attendance records', 'events-made-easy' ) ));
				echo '<br>';
			}
				eme_options_input_text( __( 'Personal info approval email subject', 'events-made-easy' ), 'eme_gdpr_approve_subject', __( 'The subject of the email which will be sent to the person asking for personal info storage approval.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_gdpr_approve].', 'events-made-easy' ) );
				eme_options_textarea( __( 'Personal info approval email body', 'events-made-easy' ), 'eme_gdpr_approve_body', __( 'The body of the email which will be sent to the person asking for personal info storage approval.', 'events-made-easy' ) . '<br>' . __( 'Can only contain 1 placeholder, namely #_GDPR_APPROVE_URL.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_gdpr_approve].', 'events-made-easy' ), $use_html_editor, $use_full );
				eme_options_input_text( __( 'Personal info approval page title', 'events-made-easy' ), 'eme_gdpr_approve_page_title', __( 'The title of the page after approval for personal info storage has been given.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used after giving approval by clicking on the link generated by #_GDPR_APPROVE_URL.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Personal info approval page content', 'events-made-easy' ), 'eme_gdpr_approve_page_content', __( 'Content of the page after approval for personal info storage has been given.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used after giving approval by clicking on the link generated by #_GDPR_APPROVE_URL.', 'events-made-easy' ), 1, 1 );
				echo '<hr>';
				eme_options_input_text( __( 'Personal info email subject', 'events-made-easy' ), 'eme_gdpr_subject', __( 'The subject of the email which will be sent to the person asking for his personal info.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_gdpr].', 'events-made-easy' ) );
				eme_options_textarea( __( 'Personal info email body', 'events-made-easy' ), 'eme_gdpr_body', __( 'The body of the email which will be sent to the person asking for his personal info.', 'events-made-easy' ) . '<br>' . __( 'Can only contain 1 placeholder, namely #_GDPR_URL.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_gdpr].', 'events-made-easy' ), $use_html_editor, $use_full );
				eme_options_input_text( __( 'Personal info page title', 'events-made-easy' ), 'eme_gdpr_page_title', __( 'The title of the page when personal info is rendered.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used when rendering personal info after clicking on #_GDPR_URL.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Personal info page header', 'events-made-easy' ), 'eme_gdpr_page_header', __( 'Text to be shown above the personal info being rendered.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used when rendering personal info after clicking on #_GDPR_URL.', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'Personal info page footer', 'events-made-easy' ), 'eme_gdpr_page_footer', __( 'Text to be shown below the personal info being rendered.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used when rendering personal info after clicking on #_GDPR_URL.', 'events-made-easy' ), 1, 1 );

				eme_options_input_text( __( 'Change personal info email subject', 'events-made-easy' ), 'eme_cpi_subject', __( 'The subject of the email which will be sent to the person asking to change his personal info.', 'events-made-easy' ) . '<br>' . __( 'No placeholders can be used.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_change_personal_info].', 'events-made-easy' ) );
				eme_options_textarea( __( 'Change personal info email body', 'events-made-easy' ), 'eme_cpi_body', __( 'The body of the email which will be sent to the person asking to change his personal info.', 'events-made-easy' ) . '<br>' . __( 'Can only contain 1 placeholder, namely #_CHANGE_PERSON_INFO.', 'events-made-easy' ) . '<br>' . __( 'This setting is used in the mail sent as a result of submitting the form created by the shortcode [eme_change_personal_info].', 'events-made-easy' ), $use_html_editor, $use_full );
				eme_options_textarea( __( 'Change personal info Form template', 'events-made-easy' ), 'eme_cpi_form', __( 'The template of the form which will be presented to the person to actually change his personal info.', 'events-made-easy' ) . '<br>' . __( 'The placeholders you can use in this setting are : #_LASTNAME, #_EMAIL, #_PHONE, #_FIRSTNAME, #_BIRTHDATE, #_BIRTHPLACE, #_ADDRESS1, #_ADDRESS2, #_CITY, #_STATE, #_ZIP, #_COUNTRY, #_OPT_IN, #_OPT_OUT, #_GDPR and all #_FIELD{xx} placeholders for custom fields of type "person".', 'events-made-easy' ), $use_html_editor, $use_full );
			?>
</table>

			<?php
			break;
		case 'payments':
			$events_page_link = eme_get_events_page();
			?>

<div id="eme-payments-accordion">
<h3><?php esc_html_e( 'General options', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
				eme_options_select( __( 'Default currency', 'events-made-easy' ), 'eme_default_currency', eme_currency_array(), __( 'Select the default currency for payments.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Default price', 'events-made-easy' ), 'eme_default_price', __( 'The default price for an event.', 'events-made-easy' ) . '<br>' . __( 'Use the point as decimal separator', 'events-made-easy' ) );
				eme_options_input_text( __( 'Default VAT percentage', 'events-made-easy' ), 'eme_default_vat', __( 'The default VAT percentage applied to all prices. The price you indicate for events or memberships is VAT included, special placeholders are foreseen to indicate the price without VAT.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Redirect towards payment page?', 'events-made-easy' ), 'eme_payment_redirect', __( 'Select yes to redirect to the payment page after a successfull booking or membership signup, select no to show the payment page inline. It is recommended to leave this at yes, so people can use their back-button in the browser to come back to the payment page.', 'events-made-easy' ) );
				eme_options_input_int( __( 'Redirect wait period', 'events-made-easy' ), 'eme_payment_redirect_wait', __( 'Indicate in seconds how many seconds to wait before redirecting to the payment page.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Redirect message', 'events-made-easy' ), 'eme_payment_redirect_msg', __( 'The message shown before redirecting to the payment page. Only one placeholder allowed (#_PAYMENT_URL).', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Redirect immediately to payment gateway?', 'events-made-easy' ), 'eme_pg_submit_immediately', __( 'By default, people get a list of payment gateways to choose from before being redirected to the payment gateway of choice. Select yes to immediately redirect to the payment gateway if there is only one payment gateway to chose from. This redirect takes also the setting  "Redirect wait period" into account. The text above the payment button of the relevant payment gateway will then also be shown.', 'events-made-easy' ) );
				eme_options_textarea( __( 'RSVP Payment form header format', 'events-made-easy' ), 'eme_payment_form_header_format', __( 'The format of the text shown above the payment buttons. If left empty, a default message will be shown.', 'events-made-easy' ) . ' ' . __( 'This option is only valid for event bookings, for memberships this can be defined for each membership individually.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'Javascript allowed', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'RSVP Payment form footer format', 'events-made-easy' ), 'eme_payment_form_footer_format', __( 'The format of the text shown below the payment buttons. Default: empty.', 'events-made-easy' ) . ' ' . __( 'This option is only valid for event bookings, for memberships this can be defined for each membership individually.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'Javascript allowed', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'Multibooking payment form header format', 'events-made-easy' ), 'eme_multipayment_form_header_format', __( 'The format of the text shown above the payment buttons in the multibooking form. If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . __( 'Although the same placeholders as for the regular payment form header format can be used, it is advised to only use multibooking related placeholders.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'Multibooking payment form footer format', 'events-made-easy' ), 'eme_multipayment_form_footer_format', __( 'The format of the text shown below the payment buttons in the multibooking form. Default: empty.', 'events-made-easy' ) . '<br>' . __( 'Although the same placeholders as for the regular payment form header format can be used, it is advised to only use multibooking related placeholders.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1, 1 );
				eme_options_textarea( __( 'Payment already done format', 'events-made-easy' ), 'eme_payment_booking_already_paid_format', __( 'The message shown instead of the payment form if payment has already been done.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Booking on waiting list format', 'events-made-easy' ), 'eme_payment_booking_on_waitinglist_format', __( 'The message shown instead of the payment form if you try to pay for a booking that is on the waiting list.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=27'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
				eme_options_textarea( __( 'Payment RSVP success return page format', 'events-made-easy' ), 'eme_payment_succes_format', __( 'The format of the return page when the payment is succesfull for RSVP bookings.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1 );
				eme_options_textarea( __( 'Payment RSVP failure return page format', 'events-made-easy' ), 'eme_payment_fail_format', __( 'The format of the return page when the payment failed or has been canceled for RSVP bookings.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1 );
				eme_options_textarea( __( 'Payment membership success return page format', 'events-made-easy' ), 'eme_payment_member_succes_format', __( 'The format of the return page when the payment is succesfull for membership signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1 );
				eme_options_textarea( __( 'Payment membership failure return page format', 'events-made-easy' ), 'eme_payment_member_fail_format', __( 'The format of the return page when the payment failed or has been canceled for membership signup.', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'the documentation', 'events-made-easy' ) . '</a><br>' . __( 'If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . __( 'Javascript allowed', 'events-made-easy' ), 1 );
				eme_options_radio_binary( __( 'Allow refunds?', 'events-made-easy' ), 'eme_payment_refund_ok', __( 'In case of a cancelled booking, the option to refund is presented (if the payment gateway supports it). If you want to disable this, select No.', 'events-made-easy' ) );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Offline payment info', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_textarea( __( 'Offline payment info', 'events-made-easy' ), 'eme_offline_payment', __( 'The text containing all info for offline payment. Can contain HTML and placeholders like the payment header/footer settings.', 'events-made-easy' ) . ' ' . __( 'This option is only valid for event bookings, for memberships this can be defined for each membership individually.', 'events-made-easy' ), 1 );
			?>
</table>
</div>

<h3><?php esc_html_e( 'Paypal', 'events-made-easy' ); ?> <b>Deprecated, use Braintree</b></h3>
<div>
<table class='form-table'>
			<?php
			echo "<tr><td colspan='2' class='notice notice-warning'>" . esc_html__( 'Remark: due to the incomplete PHP implementation by Paypal, it is not recommended to use this method. It works fine, but has some shortcomings: no webhook functionality (meaning: if someone closes the browser immediately after payment, the payment will not get marked as paid in EME) and refunding is not possible.', 'events-made-easy' ) . '</td></tr>';
			eme_options_select(
			    __( 'PayPal live or test', 'events-made-easy' ),
			    'eme_paypal_url',
			    [
					'sandbox' => __( 'Paypal Sandbox (for testing)', 'events-made-easy' ),
					'live'    => __(
					    'Paypal Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test paypal in a paypal sandbox or go live and really use paypal.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'PayPal client ID', 'events-made-easy' ), 'eme_paypal_clientid', __( 'Paypal client ID.', 'events-made-easy' ) . '<br>' . sprintf( __( 'For more info on Paypal apps and credentials, see <a href="%s">this page</a>', 'events-made-easy' ), 'https://developer.paypal.com/docs/integration/admin/manage-apps/#create-an-app-for-testing' ) );
			eme_options_input_text( __( 'PayPal secret', 'events-made-easy' ), 'eme_paypal_secret', __( 'Paypal secret.', 'events-made-easy' ) . '<br>' . sprintf( __( 'For more info on Paypal apps and credentials, see <a href="%s">this page</a>', 'events-made-easy' ), 'https://developer.paypal.com/docs/integration/admin/manage-apps/#create-an-app-for-testing' ) );
			$gateway = 'paypal';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Legacy Paypal', 'events-made-easy' ); ?> <b>Deprecated, use Braintree</b></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'legacypaypal_notification' ], $events_page_link );
			eme_options_select(
			    __( 'PayPal live or test', 'events-made-easy' ),
			    'eme_legacypaypal_url',
			    [
					'sandbox' => __( 'Paypal Sandbox (for testing)', 'events-made-easy' ),
					'live'    => __(
					    'Paypal Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test paypal in a paypal sandbox or go live and really use paypal.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'PayPal business info', 'events-made-easy' ), 'eme_legacypaypal_business', __( 'Paypal business ID or email.', 'events-made-easy' ) );
			eme_options_radio_binary( __( 'Ignore Paypal tax setting?', 'events-made-easy' ), 'eme_legacypaypal_no_tax', __( 'Select yes to ignore the tax setting in your Paypal profile.', 'events-made-easy' ) );
			$gateway = 'legacypaypal';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Webmoney', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'webmoney_notification' ], $events_page_link );

			eme_options_select(
			    __( 'Webmoney live or test', 'events-made-easy' ),
			    'eme_webmoney_demo',
			    [
					1 => __( 'Webmoney Sandbox (for testing)', 'events-made-easy' ),
					0 => __(
					    'Webmoney Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Webmoney in a sandbox or go live and really use Webmoney.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Webmoney Purse', 'events-made-easy' ), 'eme_webmoney_purse', __( 'Webmoney Purse.', 'events-made-easy' ) );
			eme_options_input_password( __( 'Webmoney Secret', 'events-made-easy' ), 'eme_webmoney_secret', __( 'Webmoney secret.', 'events-made-easy' ) );
			$gateway = 'webmoney';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'First Data', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'fdgg_notification' ], $events_page_link );

			eme_options_select(
			    __( 'First Data live or test', 'events-made-easy' ),
			    'eme_fdgg_url',
			    [
					'sandbox' => __( 'First Data Sandbox (for testing)', 'events-made-easy' ),
					'live'    => __(
					    'First Data Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test First Data in a sandbox or go live and really use First Data.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'First Data Store Name', 'events-made-easy' ), 'eme_fdgg_store_name', __( 'First Data Store Name.', 'events-made-easy' ) );
			eme_options_input_password( __( 'First Data Shared Secret', 'events-made-easy' ), 'eme_fdgg_shared_secret', __( 'First Data Shared Secret.', 'events-made-easy' ) );
			$gateway = 'fdgg';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Mollie', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'mollie_notification' ], $events_page_link );

			eme_options_input_text( __( 'Mollie API key', 'events-made-easy' ), 'eme_mollie_api_key', __( 'Mollie API key', 'events-made-easy' ) );
			$gateway = 'mollie';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Payconiq', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'payconiq_notification' ], $events_page_link );
			eme_options_select(
			    __( 'Payconiq live or test', 'events-made-easy' ),
			    'eme_payconiq_env',
			    [
					'sandbox'    => __( 'Payconiq Sandbox (for testing)', 'events-made-easy' ),
					'production' => __(
					    'Payconiq Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Payconiq in a sandbox or go live and really use Payconiq.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Payconiq API key', 'events-made-easy' ), 'eme_payconiq_api_key', __( 'Payconiq API key', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payconiq Merchant ID', 'events-made-easy' ), 'eme_payconiq_merchant_id', __( 'Payconiq Merchant ID', 'events-made-easy' ) );
			$gateway = 'payconiq';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is implemented but the way payconiq works it might still require manual transfer of the funds to the relevant bank accounts anyway.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Worldpay', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'worldpay_notification' ], $events_page_link );
			eme_options_select(
			    __( 'Worldpay live or test', 'events-made-easy' ),
			    'eme_worldpay_demo',
			    [
					1 => __( 'Worldpay Sandbox (for testing)', 'events-made-easy' ),
					0 => __(
					    'Worldpay Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Worldpay in a sandbox or go live and really use Worldpay.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Worldpay installation ID', 'events-made-easy' ), 'eme_worldpay_instid', __( 'Worldpay installation ID', 'events-made-easy' ) );
			eme_options_input_text( __( 'Worldpay MD5 secret', 'events-made-easy' ), 'eme_worldpay_md5_secret', __( 'Worldpay MD5 secret used when submitting payments', 'events-made-easy' ) );
			eme_options_input_text( __( 'Worldpay MD5 parameters', 'events-made-easy' ), 'eme_worldpay_md5_parameters', __( "Worldpay parameters used to generate the MD5 signature, separated by ':'. Only use these 4 in the order of your choice: instId,cartId,currency and/or amount", 'events-made-easy' ) );
			eme_options_input_password( __( 'Worldpay Test Password', 'events-made-easy' ), 'eme_worldpay_test_pwd', __( 'Worldpay password for payment notifications when testing', 'events-made-easy' ) );
			eme_options_input_password( __( 'Worldpay Live Password', 'events-made-easy' ), 'eme_worldpay_live_pwd', __( 'Worldpay password for payment notifications when using Worldpay for real', 'events-made-easy' ) );
			$gateway = 'worldpay';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Opayo', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_select(
			    __( 'Opayo live or test', 'events-made-easy' ),
			    'eme_opayo_demo',
			    [
					1 => __( 'Opayo Sandbox (for testing)', 'events-made-easy' ),
					0 => __(
					    'Opayo Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Opayo in a sandbox or go live and really use Opayo.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Opayo Vendor Name', 'events-made-easy' ), 'eme_opayo_vendor_name', __( 'Opayo Vendor Name', 'events-made-easy' ) );
			eme_options_input_password( __( 'Opayo Test Password', 'events-made-easy' ), 'eme_opayo_test_pwd', __( 'Opayo password for testing purposes', 'events-made-easy' ) );
			eme_options_input_password( __( 'Opayo Live Password', 'events-made-easy' ), 'eme_opayo_live_pwd', __( 'Opayo password when using Opayo for real', 'events-made-easy' ) );
			$gateway = 'opayo';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: for Opayo to work, your PHP installation must have the mcrypt module installed and activated. Search the internet for which extra PHP package to install and/or which line in php.ini to change.', 'events-made-easy' ) . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'SumUp', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'sumup_notification' ], $events_page_link );
			eme_options_input_text( __( 'SumUp Merchant Code', 'events-made-easy' ), 'eme_sumup_merchant_code', __( 'SumUp Merchant Code', 'events-made-easy' ) );
			eme_options_input_text( __( 'SumUp App ID', 'events-made-easy' ), 'eme_sumup_app_id', __( 'SumUp App ID', 'events-made-easy' ) );
			eme_options_input_text( __( 'SumUp App Secret', 'events-made-easy' ), 'eme_sumup_app_secret', __( 'SumUp App Secret', 'events-made-easy' ) );
			$gateway = 'sumup';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding not implemented yet.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Stripe', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			$notification_link = add_query_arg( [ 'eme_eventAction' => 'stripe_notification' ], $events_page_link );
			eme_options_input_text( __( 'Stripe Secret Key', 'events-made-easy' ), 'eme_stripe_private_key', __( 'Stripe Secret Key', 'events-made-easy' ) );
			eme_options_input_text( __( 'Stripe Public Key', 'events-made-easy' ), 'eme_stripe_public_key', __( 'Stripe Public Key', 'events-made-easy' ) );
			$gateway = 'stripe';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			$stripe_pms = [
				'alipay'            => 'alipay',
				'card'              => 'card',
				'ideal'             => 'ideal',
				'fpx'               => 'fpx',
				'bacs_debit'        => 'bacs_debit',
				'bancontact'        => 'bancontact',
				'giropay'           => 'giropay',
				'p24'               => 'p24',
				'eps'               => 'eps',
				'sofort'            => 'sofort',
				'sepa_debit'        => 'sepa_debit',
				'grabpay'           => 'grabpay',
				'afterpay_clearpay' => 'afterpay_clearpay',
				'acss_debit'        => 'acss_debit',
				'wechat_pay'        => 'wechat_pay',
				'boleto'            => 'boleto',
				'oxxo'              => 'oxxo',
			];
			eme_options_multiselect( __( 'Stripe payment methods', 'events-made-easy' ), 'eme_stripe_payment_methods', $stripe_pms, __( "The different Stripe payment methods you want to handle/provide. Defaults to 'card'. See the <a href='https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-payment_method_types'>Stripe doc</a> for more info.", 'events-made-easy' ), false, 'eme_select2_width50_class' );

			echo "<tr><td colspan='2'>" . esc_html__( 'Info: the url for payment notifications is: ', 'events-made-easy' ) . $notification_link . '</td></tr>';
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			$eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
			if ( ! empty( $eme_stripe_private_key ) ) {
				$stripe_webhookid = get_option( 'eme_stripe_webhook_secret' );
				if ( empty( $stripe_webhookid ) ) {
					$err_txt = get_option( 'eme_stripe_webhook_error' );
					if ( ! empty( $err_txt ) ) {
						echo "<tr><td colspan='2' class='notice notice-warning'>" . sprintf( esc_html__( 'WARNING: webhook has not been created. Reason: %s', 'events-made-easy' ), $err_txt ) . '</td></tr>';
					} else {
						echo "<tr><td colspan='2' class='notice notice-warning'>" . esc_html__( 'WARNING: no webhook has been created. Press save to attempt to create one.', 'events-made-easy' ) . '</td></tr>';
					}
				} else {
					echo "<tr><td colspan='2' class='notice notice-success'>" . esc_html__( 'Info: a webhook to the mentioned link has been successfully created.', 'events-made-easy' ) . '</td></tr>';
				}
			}
			?>
</table>
</div>

<h3><?php esc_html_e( 'Braintree', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_select(
			    __( 'Braintree live or test', 'events-made-easy' ),
			    'eme_braintree_env',
			    [
					'sandbox'    => __( 'Braintree Sandbox (for testing)', 'events-made-easy' ),
					'production' => __(
					    'Braintree Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Braintree in a sandbox or go live and really use Braintree.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Braintree Merchant ID', 'events-made-easy' ), 'eme_braintree_merchant_id', __( 'Braintree Merchant ID', 'events-made-easy' ) );
			eme_options_input_text( __( 'Braintree Public Key', 'events-made-easy' ), 'eme_braintree_public_key', __( 'Braintree Public Key', 'events-made-easy' ) );
			eme_options_input_text( __( 'Braintree Private Key', 'events-made-easy' ), 'eme_braintree_private_key', __( 'Braintree Private Key', 'events-made-easy' ) );
			$gateway = 'braintree';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Instamojo', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_select(
			    __( 'Instamojo live or test', 'events-made-easy' ),
			    'eme_instamojo_env',
			    [
					'sandbox'    => __( 'Instamojo Sandbox (for testing)', 'events-made-easy' ),
					'production' => __(
					    'Instamojo Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Instamojo in a sandbox or go live and really use Instamojo.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Instamojo Private Key', 'events-made-easy' ), 'eme_instamojo_key', __( 'Instamojo Private Key', 'events-made-easy' ) );
			eme_options_input_text( __( 'Instamojo Private Auth Token', 'events-made-easy' ), 'eme_instamojo_auth_token', __( 'Instamojo Private Auth Token', 'events-made-easy' ) );
			eme_options_input_text( __( 'Instamojo Private Salt', 'events-made-easy' ), 'eme_instamojo_salt', __( 'Instamojo Private Salt', 'events-made-easy' ) );
			$gateway = 'instamojo';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Mercado Pago', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_select(
			    __( 'Mercado Pago live or test', 'events-made-easy' ),
			    'eme_mercadopago_demo',
			    [
					1 => __( 'Mercado Pago Sandbox (for testing)', 'events-made-easy' ),
					0 => __(
					    'Mercado Pago Live',
					    'events-made-easy'
					),
				],
			    __( 'Choose wether you want to test Mercado Pago in a sandbox or go live and really use Mercado Pago.', 'events-made-easy' )
			);
			eme_options_input_text( __( 'Mercado Pago Sandbox Access Token', 'events-made-easy' ), 'eme_mercadopago_sandbox_token', __( 'Mercado Pago Sandbox Access Token', 'events-made-easy' ) );
			eme_options_input_text( __( 'Mercado Pago Live Access Token', 'events-made-easy' ), 'eme_mercadopago_live_token', __( 'Mercado Pago Live Access Token', 'events-made-easy' ) );
			$gateway = 'mercadopago';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>

<h3><?php esc_html_e( 'Fondy', 'events-made-easy' ); ?></h3>
<div>
<table class='form-table'>
			<?php
			eme_options_input_text( __( 'Fondy Merchant ID', 'events-made-easy' ), 'eme_fondy_merchant_id', __( 'Fondy Merchant ID', 'events-made-easy' ) );
			eme_options_input_text( __( 'Fondy Secret Key', 'events-made-easy' ), 'eme_fondy_secret_key', __( 'Fondy Secret Key', 'events-made-easy' ) );
			$gateway = 'fondy';
			eme_options_input_text( __( 'Extra charge', 'events-made-easy' ), 'eme_' . $gateway . '_cost', __( 'Extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Extra charge 2', 'events-made-easy' ), 'eme_' . $gateway . '_cost2', __( 'Second extra charge added to the price. Can either be an absolute number or a percentage. E.g. 2 or 5%', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button label', 'events-made-easy' ), 'eme_' . $gateway . '_button_label', __( 'The text shown inside the payment button', 'events-made-easy' ) );
			eme_options_input_text( __( 'Payment button image', 'events-made-easy' ), 'eme_' . $gateway . '_button_img_url', __( 'The url to an image for the payment button that replaces the standard submit button with the label mentioned above.', 'events-made-easy' ) );
			eme_options_input_text( __( 'Text above payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_above', __( 'The text shown just above the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			eme_options_input_text( __( 'Text below payment button', 'events-made-easy' ), 'eme_' . $gateway . '_button_below', __( 'The text shown just below the payment button', 'events-made-easy' ) . '<br>' . __( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/payment-gateways/'>" . __( 'the documentation', 'events-made-easy' ) . '</a>' );
			echo "<tr><td colspan='2'>" . esc_html__( 'Info: refunding is possible.', 'events-made-easy' ) . '</td></tr>';
			?>
</table>
</div>
</div>

			<?php
			break;
		case 'maps':
			?>
<h3><?php esc_html_e( 'Map options', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
				eme_options_radio_binary( __( 'Enable map scroll-wheel zooming?', 'events-made-easy' ), 'eme_map_zooming', __( 'Yes, enables map scroll-wheel zooming. No, enables scroll-wheel page scrolling over maps. (It will be necessary to refresh your web browser on a map page to see the effect of this change.)', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Enable map gestures?', 'events-made-easy' ), 'eme_map_gesture_handling', __( 'If you choose to use map gestures, then on a desktop zooming must be done using ctrl+zoom, and on a mobile device the user needs to use two fingers to pan the map. This to prevent getting "trapped" in the map when a big map is shown.', 'events-made-easy' ) );
				eme_options_input_text( __( 'Individual map zoom factor', 'events-made-easy' ), 'eme_indiv_zoom_factor', __( 'The zoom factor used when showing a single map (max: 14).', 'events-made-easy' ) );
				eme_options_input_text( __( 'Default location map icon', 'events-made-easy' ), 'eme_location_map_icon', __( "By default a regular pin is shown on the map where the location is. If you don't like the default, you can set another map icon here.", 'events-made-easy' ) . '<br>' . __( 'Size should be 32x32, bottom center will be pointing to the location on the map.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Default location balloon format', 'events-made-easy' ), 'eme_location_baloon_format', __( 'The format of the text appearing in the balloon describing the location in the map.', 'events-made-easy' ) );
			?>
</table>

			<?php
			break;
		case 'other':
			?>

<h3><?php esc_html_e( 'Other settings', 'events-made-easy' ); ?></h3>
<table class='form-table'>
			<?php
				eme_options_radio_binary( __( 'Stay on edit page after save?', 'events-made-easy' ), 'eme_stay_on_edit_page', __( 'This allows you to stay on the edit page after saving events, locations, templates, formfields, people, groups or memberships.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Remove leading zeros from minutes?', 'events-made-easy' ), 'eme_time_remove_leading_zeros', __( 'PHP date/time functions have no notation to show minutes without leading zeros. Checking this option will return e.g. 9 for 09 and empty for 00. This setting affects custom time placeholders and also the generic *DATE and *TIME placeholders', 'events-made-easy' ) );
				eme_options_input_text( __( 'CSV separator', 'events-made-easy' ), 'eme_csv_separator', __( 'Set the separator used in CSV exports.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Localize price', 'events-made-easy' ), 'eme_localize_price', __( "If selected, all prices will be shown in the current locale with the price symbol. If you don't want this, deselect this option to regain the old behavior of localized prices where you need to take care of the decimal accuracy, the currency symbol and it's location yourself. This option only works if the php class NumberFormatter is present, which is always the case in newer php versions but just don't forget to load the INTL extension in php.", 'events-made-easy' ) );
				eme_options_input_int( __( 'Decimals accuracy', 'events-made-easy' ), 'eme_decimals', __( 'EME tries to show the prices in the frontend in the current locale, with the decimals accuracy set here. Defaults to 2.', 'events-made-easy' ) . '<br>' . __( 'This option is not used if the localize price option above is active and the php class NumberFormatter is present.', 'events-made-easy' ) );
				eme_options_input_int( __( 'Timepicker step interval', 'events-made-easy' ), 'eme_timepicker_minutesstep', __( 'The timepicker step interval. Defaults to 5-minutes interval (meaning steps of 5 minutes will be taken)', 'events-made-easy' ) );
				eme_options_input_text( __( 'Required field text', 'events-made-easy' ), 'eme_form_required_field_string', __( 'The text shown next to a form field when it is a required field', 'events-made-easy' ) );
				eme_options_input_text( __( 'Address line 1 text', 'events-made-easy' ), 'eme_address1_string', __( "By default, the text shown for the first address line is 'Address line 1', but this might not be very informative to people, so feel free to put something else here, like 'street name'", 'events-made-easy' ) );
				eme_options_input_text( __( 'Address line 2 text', 'events-made-easy' ), 'eme_address2_string', __( "By default, the text shown for the second address line is 'Address line 2', but this might not be very informative to people, so feel free to put something else here, like 'house number'", 'events-made-easy' ) );
				eme_options_select( __( 'Thumbnail size', 'events-made-easy' ), 'eme_thumbnail_size', eme_thumbnail_sizes(), __( 'Choose the default thumbnail size to be shown when using placeholders involving thumbnails like e.g. #_EVENTIMAGETHUMB, #_LOCATIONIMAGETHUMB, ...', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Use external url for single events or locations?', 'events-made-easy' ), 'eme_use_external_url', __( 'If selected, clicking on the single event or location url for details will go to the defined external url for that event or location if present.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'By default send out birthday email for new persons?', 'events-made-easy' ), 'eme_bd_email', __( 'If selected, new persons registered with a non-empty birthday will get a birthday email. Go in the Email Templates settings to change the look and feel of that email.', 'events-made-easy' ) );
				eme_options_radio_binary( __( 'Limit birthday emails to active members?', 'events-made-easy' ), 'eme_bd_email_members_only', __( 'If selected and birthday emails are to be send, only persons with an active membership will get a birthday email.', 'events-made-easy' ) );
				eme_options_input_text( __( 'EME backend date format', 'events-made-easy' ), 'eme_backend_dateformat', __( 'The date format used in EME tables. Leave this empty to use the WordPress settings.', 'events-made-easy' ) . "<p class='date-time-doc'>" . __( '<a href="https://wordpress.org/support/article/formatting-date-and-time/">Documentation on date and time formatting</a>.' ) . '</p>' );
				eme_options_input_text( __( 'EME backend time format', 'events-made-easy' ), 'eme_backend_timeformat', __( 'The time format used in EME tables. Leave this empty to use the WordPress settings.', 'events-made-easy' ) . "<p class='date-time-doc'>" . __( '<a href="https://wordpress.org/support/article/formatting-date-and-time/">Documentation on date and time formatting</a>.' ) . '</p>' );
				require_once 'dompdf/vendor/autoload.php';
				$dompdf                = new Dompdf\Dompdf();
				$dompdf_fontfamilies   = array_keys( $dompdf->getFontMetrics()->getFontFamilies() );
				$pdf_font_families_arr = [];
				foreach ( $dompdf_fontfamilies as $font ) {
					$pdf_font_families_arr[ $font ] = ucwords( $font );
				}
				eme_options_select( __( 'PDF font', 'events-made-easy' ), 'eme_pdf_font', $pdf_font_families_arr, __( 'Set the font to be used in generated PDF files. Sometimes you need to use a different font because not all characters might be defined in the current selected font.', 'events-made-easy' ) );
				eme_options_input_int( __( 'EME DB version', 'events-made-easy' ), 'eme_version', __( "This is the current EME database version, you can use this to put the version back to an older version; upon saving EME will then redo the missed database upgrades from that version onwards if they didn't happen correctly and reset the value to the latest version. In any normal situation, you never need to change this value.", 'events-made-easy' ) );
			?>
</table>

<h3><?php esc_html_e( 'Extra html tags', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
			eme_options_textarea( __( 'Extra html tags', 'events-made-easy' ), 'eme_allowed_html', __( 'By default WordPress is strict in what html tags and attributes are allowed. You can here add a list of tags+attributes you want to allow in EME settings that allow html (in addition to the defaults, nothing gets removed). The list should be one tag per line, followed by its optional attributes, all seperated by ",". As an example, to allow the iframe tag and some attributes, you would add: "iframe,src,height,width,frameborder" ', 'events-made-easy' ) );
			eme_options_textarea( __( 'Extra style attributes', 'events-made-easy' ), 'eme_allowed_style_attr', __( 'While the setting above allows you to add extra html tags and attributes, WordPress is even more strict concerning attributes allowed in the style tag. You can here add a list of extra style attributes you want to allow in EME settings that allow html (in addition to the defaults, nothing gets removed). The list should be one attribute per line. As an example, to allow the visibility attribute for the style tag, you would add: "visbility" ', 'events-made-easy' ) );
			?>
</table>
<h3><?php esc_html_e( 'Extra html headers', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
				eme_options_textarea( __( 'Extra html header', 'events-made-easy' ), 'eme_html_header', __( 'Here you can define extra html headers, no placeholders can be used.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Extra html footer', 'events-made-easy' ), 'eme_html_footer', __( 'Here you can define extra html footer, no placeholders can be used.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Extra event html headers', 'events-made-easy' ), 'eme_event_html_headers_format', __( 'Here you can define extra html headers when viewing a single event, typically used to add meta tags for facebook or SEO. All event placeholders can be used, but will be stripped from resulting html.', 'events-made-easy' ) );
				eme_options_textarea( __( 'Extra location html headers', 'events-made-easy' ), 'eme_location_html_headers_format', __( 'Here you can define extra html headers when viewing a single location, typically used to add meta tags for facebook or SEO. All location placeholders can be used, but will be stripped from resulting html.', 'events-made-easy' ) );
			?>
</table>
<h3><?php esc_html_e( 'Multisite options', 'events-made-easy' ); ?></h3>
<table class="form-table">
			<?php
			eme_options_radio_binary( __( 'Activate multisite data sharing?', 'events-made-easy' ), 'eme_multisite_active', __( 'If selected and WordPress multisite is active, this EME instance will use the database tables of the main multisite instance. This will cause all events, locations, bookings, memberships, templates etc ... to be shared with the main site. The only thing that remains local are all the EME options, allowing you to make language subsites or other things. Also be aware of planned actions: those will only be executed based on the options set in the main site, not per subsite. If you do not want to share data amongst subsites, this option is not needed.', 'events-made-easy' ) );
			?>
</table>


			<?php
			break;
	} // end of switch-statement
	?>


<p class="submit"><input type="submit" class="button-primary" id="eme_options_submit" name="Submit" value="<?php esc_html_e( 'Save Changes', 'events-made-easy' ); ?>"></p>
</form>
</div>
	<?php
}
?>
