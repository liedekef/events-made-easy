<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_gdpr_approve_url( $email ) {
	$language = eme_detect_lang();

	$the_link = eme_get_events_page();
	// some plugins add the lang info to the home_url, remove it so we don't get into trouble or add it twice
	$the_link = remove_query_arg( 'lang', $the_link );
	$nonce    = wp_create_nonce( "gdpr $email" );
	$the_link = add_query_arg(
	    [
			'eme_gdpr_approve' => $email,
			'eme_gdpr_nonce'   => $nonce,
		],
	    $the_link
	);
	if ( ! empty( $language ) ) {
			$the_link = add_query_arg( [ 'lang' => $language ], $the_link );
	}
	return $the_link;
}

function eme_gdpr_url( $email ) {
	$language = eme_detect_lang();

	$the_link = eme_get_events_page();
	// some plugins add the lang info to the home_url, remove it so we don't get into trouble or add it twice
	$the_link = remove_query_arg( 'lang', $the_link );
	$nonce    = wp_create_nonce( "gdpr $email" );
	$the_link = add_query_arg(
	    [
			'eme_gdpr'       => $email,
			'eme_gdpr_nonce' => $nonce,
		],
	    $the_link
	);
	if ( ! empty( $language ) ) {
			$the_link = add_query_arg( [ 'lang' => $language ], $the_link );
	}
	return $the_link;
}

add_action( 'wp_ajax_eme_rpi', 'eme_rpi_ajax' );
add_action( 'wp_ajax_nopriv_eme_rpi', 'eme_rpi_ajax' );
function eme_rpi_ajax() {
	// check for spammers as early as possible
	if ( get_option( 'eme_honeypot_for_forms' ) ) {
		if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
				$form_html = __( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
				echo wp_json_encode(
				    [
						'Result'      => 'NOK',
						'htmlmessage' => $form_html,
					]
				);
				wp_die();
		}
	}
	if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) {
			$form_html = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
			echo wp_json_encode(
			    [
					'Result'      => 'NOK',
					'htmlmessage' => $form_html,
				]
			);
			wp_die();
	}

	$remove_captcha_if_ok = 1;
	eme_check_captcha( null, $remove_captcha_if_ok );

	$mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
	// send email to client if it exists, otherwise do nothing, but always return the same message
	$email = eme_sanitize_email( $_POST['eme_email'] );
	// check if email is found, if so: send the gdpr url
	if ( eme_count_persons_by_email( $email ) > 0 ) {
		$contact_email = get_option( 'eme_mail_sender_address' );
		$contact_name  = get_option( 'eme_mail_sender_name' );
		if ( empty( $contact_email ) ) {
			$contact       = eme_get_contact();
			$contact_email = $contact->user_email;
			$contact_name  = $contact->display_name;
		}
		$gdpr_link    = eme_gdpr_url( $email );
		$gdpr_subject = eme_translate( get_option( 'eme_gdpr_subject' ) );
		$gdpr_body    = eme_translate( get_option( 'eme_gdpr_body' ) );
		$gdpr_body    = str_replace( '#_GDPR_URL', $gdpr_link, $gdpr_body );
		$gdpr_body    = eme_replace_generic_placeholders( $gdpr_body, $mail_text_html );
		eme_queue_fastmail( $gdpr_subject, $gdpr_body, $contact_email, $contact_name, $email, '', $contact_email, $contact_name );
	}
	$form_html = __( 'Thank you for your request, an email will be sent with further info.', 'events-made-easy' );
	echo wp_json_encode(
	    [
			'Result'      => 'OK',
			'htmlmessage' => $form_html,
		]
	);
	wp_die();
}

function eme_rpi_shortcode( $atts ) {
	eme_enqueue_frontend();
	if ( isset( $_GET['eme_email'] ) ) {
		$email = eme_esc_html( eme_sanitize_email( $_GET['eme_email'] ) );
	} else {
		$email = '';
	}

	$atts = shortcode_atts( [ 'show_info_if_logged_in' => 0 ], $atts );
	$show_info_if_logged_in = filter_var( $atts['show_info_if_logged_in'], FILTER_VALIDATE_BOOLEAN );

	// for logged in users that are linked to an EME user, immediately show the info
	if ( $show_info_if_logged_in && is_user_logged_in() ) {
		$current_userid = get_current_user_id();
		$person         = eme_get_person_by_wp_id( $current_userid );
		if ( ! empty( $person ) ) {
			return eme_show_personal_info( $person['email'] );
		}
	}

	$captcha_html = eme_generate_captchas_html();
	$nonce = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
	usleep( 2 );
	$form_id   = uniqid();
	$form_html = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
	<div id='eme-rpi-message-ok-$form_id' class='eme-message-success eme-rpi-message eme-rpi-message-success eme-hidden'></div><div id='eme-rpi-message-error-$form_id' class='eme-message-error eme-rpi-message eme-rpi-message-error eme-hidden'></div><div id='div_eme-rpi-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-rpi-form' method='post' action='#'>
		$nonce
		<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
		<input type='email' name='eme_email' required='required' value='" . $email . "' placeholder='" . __( 'Email', 'events-made-easy' ) . "'>
		<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' style='display:none;'><br>
		$captcha_html
		<input type='submit' value='" . __( 'Request person data', 'events-made-easy' ) . "' name='doaction' id='doaction' class='button-primary action'>
		</form></div>";
	return $form_html;
}

add_action( 'wp_ajax_eme_gdpr_approve', 'eme_gdpr_approve_ajax' );
add_action( 'wp_ajax_nopriv_eme_gdpr_approve', 'eme_gdpr_approve_ajax' );
function eme_gdpr_approve_ajax() {
	// check for spammers as early as possible
	if ( get_option( 'eme_honeypot_for_forms' ) ) {
		if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
			$form_html = __( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
			echo wp_json_encode(
				[
					'Result'      => 'NOK',
					'htmlmessage' => $form_html,
				]
			);
			wp_die();
		}
	}
	if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) {
		$form_html = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	$remove_captcha_if_ok = 1;
	eme_check_captcha( null, $remove_captcha_if_ok );

	$mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
	// send email to client if it exists, otherwise do nothing, but always return the same message
	$email = eme_sanitize_email( $_POST['eme_email'] );
	// check if email is found, if so: send the gdpr url
	if ( eme_count_persons_by_email( $email ) > 0 ) {
		$contact_email = get_option( 'eme_mail_sender_address' );
		$contact_name  = get_option( 'eme_mail_sender_name' );
		if ( empty( $contact_email ) ) {
			$contact       = eme_get_contact();
			$contact_email = $contact->user_email;
			$contact_name  = $contact->display_name;
		}
		$gdpr_link    = eme_gdpr_approve_url( $email );
		$gdpr_subject = eme_translate( get_option( 'eme_gdpr_approve_subject' ) );
		$gdpr_body    = eme_translate( get_option( 'eme_gdpr_approve_body' ) );
		$gdpr_body    = str_replace( '#_GDPR_APPROVE_URL', $gdpr_link, $gdpr_body );
		$gdpr_body    = eme_replace_generic_placeholders( $gdpr_body, $mail_text_html );
		eme_queue_fastmail( $gdpr_subject, $gdpr_body, $contact_email, $contact_name, $email, '', $contact_email, $contact_name );
	}
	$form_html = __( 'Thank you for your request, an email will be sent with further info.', 'events-made-easy' );
	echo wp_json_encode(
		[
			'Result'      => 'OK',
			'htmlmessage' => $form_html,
		]
	);
	wp_die();
}

function eme_gdpr_approve_shortcode() {
	eme_enqueue_frontend();
	if ( isset( $_GET['eme_email'] ) ) {
		$email = eme_esc_html( eme_sanitize_email( $_GET['eme_email'] ) );
	} else {
		$email = '';
	}

	$captcha_html = eme_generate_captchas_html();
	$nonce = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
	usleep( 2 );
	$form_id   = uniqid();
	$form_html = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
	<div id='eme-gdpr-approve-message-ok-$form_id' class='eme-message-success eme-gdpr-approve-message eme-gdpr-approve-message-success eme-hidden'></div><div id='eme-gdpr-approve-message-error-$form_id' class='eme-message-error eme-gdpr-approve-message eme-gdpr-approve-message-error eme-hidden'></div><div id='div_eme-gdpr-approve-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-gdpr-approve-form' method='post' action='#'>
		$nonce
		<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
   		<input type='email' name='eme_email' required='required' value='" . $email . "' placeholder='" . __( 'Email', 'events-made-easy' ) . "'>
		<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' style='display:none;'><br>
		$captcha_html
   		<input type='submit' value='" . __( 'Initiate GDPR approval', 'events-made-easy' ) . "' name='doaction' id='doaction' class='button-primary action'>
		</form>";
	return $form_html;
}

add_action( 'wp_ajax_eme_cpi_request', 'eme_cpi_request_ajax' );
add_action( 'wp_ajax_nopriv_eme_cpi_request', 'eme_cpi_request_ajax' );
function eme_cpi_request_ajax() {
	// check for spammers as early as possible
	if ( get_option( 'eme_honeypot_for_forms' ) ) {
		if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
			$message = __( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
			echo wp_json_encode(
				[
					'Result'      => 'NOK',
					'htmlmessage' => $message,
				]
			);
			wp_die();
		}
	}
	if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) {
		$message = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $message,
			]
		);
		wp_die();
	}

	$remove_captcha_if_ok = 1;
	eme_check_captcha( null, $remove_captcha_if_ok );

	// send email to client if it exists, otherwise do nothing, but always return the same message
	$email = eme_sanitize_email( $_POST['eme_email'] );
	// check if email is found, if so: send the url
	$mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
	$person_ids     = eme_get_personids_by_email( $email );
	if ( ! empty( $person_ids ) ) {
		$contact_email = get_option( 'eme_mail_sender_address' );
		$contact_name  = get_option( 'eme_mail_sender_name' );
		if ( empty( $contact_email ) ) {
			$contact       = eme_get_contact();
			$contact_email = $contact->user_email;
			$contact_name  = $contact->display_name;
		}
		$change_subject = eme_translate( get_option( 'eme_cpi_subject' ) );
		$change_body    = eme_translate( get_option( 'eme_cpi_body' ) );
		if ( $mail_text_html == 'htmlmail' ) {
			$change_info = "<table style='border-collapse: collapse;border: 1px solid black;'><tr><th style='border: 1px solid black;padding: 5px;'>" . __( 'First name', 'events-made-easy' ) . "</th><th style='border: 1px solid black;padding: 5px;'>" . __( 'Last name', 'events-made-easy' ) . "</th><th style='border: 1px solid black;padding: 5px;'></th></tr>";
		} else {
			$change_info = '';
		}
		$first_person_name = '';
		foreach ( $person_ids as $person_id ) {
			$person      = eme_get_person( $person_id );
			$change_link = eme_cpi_url( $person_id, $email );
			$person_name = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
			if ( empty( $first_person_name ) ) {
				$first_person_name = $person_name;
			}
			if ( $mail_text_html == 'htmlmail' ) {
				$change_info .= "<tr><td style='border: 1px solid black;padding: 5px;'>" . eme_esc_html( $person['firstname'] ) . "</td><td style='border: 1px solid black;padding: 5px;'>" . eme_esc_html( $person['lastname'] ) . "</td><td style='border: 1px solid black;padding: 5px;'><a href='$change_link'>" . __( 'Click here to change the info for this person', 'events-made-easy' ) . '</a></td></tr>';
			} else {
				$change_info .= "$person_name: $change_link " . __( '(copy/paste this link in your browser to change the info for this person)', 'events-made-easy' );
			}
		}
		if ( $mail_text_html == 'htmlmail' ) {
			$change_info .= '</table>';
		}
		if ( strstr( $change_body, '#_CHANGE_PERSON_URL' ) ) {
			$change_body = str_replace( '#_CHANGE_PERSON_URL', $change_info, $change_body );
		}
		$change_body = str_replace( '#_CHANGE_PERSON_INFO', $change_info, $change_body );
		$change_body = eme_replace_generic_placeholders( $change_body, $mail_text_html );
		eme_queue_fastmail( $change_subject, $change_body, $contact_email, $contact_name, $email, $first_person_name, $contact_email, $contact_name );
	}
	$message = __( 'Thank you for your request, an email will be sent with further info.', 'events-made-easy' );
	echo wp_json_encode(
		[
			'Result'      => 'OK',
			'htmlmessage' => $message,
		]
	);
	wp_die();
}

function eme_cpi_shortcode( $atts ) {
	eme_enqueue_frontend();
	if ( isset( $_GET['eme_email'] ) ) {
		$email = eme_esc_html( eme_sanitize_email( $_GET['eme_email'] ) );
	} else {
		$email = '';
	}

	$atts = shortcode_atts( [ 'show_form_if_logged_in' => 0 ], $atts );
	$show_form_if_logged_in = filter_var( $atts['show_form_if_logged_in'], FILTER_VALIDATE_BOOLEAN );

	// for logged in users that are linked to an EME user, immediately show the form
	if ( $show_form_if_logged_in && is_user_logged_in() ) {
		$current_userid = get_current_user_id();
		$person_id      = eme_get_personid_by_wpid( $current_userid );
		if ( ! empty( $person_id ) ) {
			return eme_cpi_form( $person_id );
		}
	}

	$nonce = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );

	$captcha_html = eme_generate_captchas_html();

	usleep( 2 );
	$form_id = uniqid();
	$form_html   = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-cpi-request-message-ok-$form_id' class='eme-message-success eme-cpi-request-message eme-cpi-request-message-success eme-hidden'></div><div id='eme-cpi-request-message-error-$form_id' class='eme-message-error eme-cpi-request-message eme-cpi-request-message-error eme-hidden'></div><div id='div_eme-cpi-request-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-cpi-request-form' method='post' action='#'>
		$nonce
		<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
		<input type='email' name='eme_email' value='" . $email . "' placeholder='" . __( 'Email', 'events-made-easy' ) . "'>
		<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' style='display:none;'><br>
		$captcha_html
		<input type='submit' value='" . __( 'Request to change personal info', 'events-made-easy' ) . "' name='doaction' id='doaction' class='button-primary action'>
		</form></div>";
	return $form_html;
}

add_action( 'wp_ajax_eme_cpi', 'eme_cpi_ajax' );
add_action( 'wp_ajax_nopriv_eme_cpi', 'eme_cpi_ajax' );
function eme_cpi_ajax() {
	// check for spammers as early as possible
	if ( get_option( 'eme_honeypot_for_forms' ) ) {
		if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
			$message = __( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
			echo wp_json_encode(
				[
					'Result'      => 'NOK',
					'htmlmessage' => $message,
				]
			);
			wp_die();
		}
	}
	if ( empty( $_POST['person_id'] ) ) {
		$message = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $message,
			]
		);
		wp_die();
	}
	$person_id = intval( $_POST['person_id'] );
	if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), "eme_frontend" ) ) {
		$message = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $message,
			]
		);
		wp_die();
	}

	$captcha_res = eme_check_captcha();

	[$person_id, $add_update_message] = eme_add_update_person_from_form( $person_id );
	if ( $person_id ) {
		$message = __( 'Person updated', 'events-made-easy' );
		eme_captcha_remove( $captcha_res );
	} else {
		$message  = __( 'Problem detected while updating person', 'events-made-easy' );
		$message .= '<br>' . $add_update_message;
	}
	echo wp_json_encode(
		[
			'Result'      => 'OK',
			'htmlmessage' => $message,
		]
	);
	wp_die();
}

function eme_cpi_form( $person_id ) {
	$person = eme_get_person( $person_id );
	if ( empty( $person ) ) {
		return "<div class='eme-message-error eme-cpi-message-error'>" . __( 'This link is no longer valid, please request a new link.', 'events-made-easy' ) . '</div>';
	}

	$nonce          = wp_nonce_field( "eme_frontend", 'eme_frontend_nonce', false, false );
	$format_default = esc_html__( 'Last name: ', 'events-made-easy' ) . '#_LASTNAME <br>' .
		esc_html__( 'First name: ', 'events-made-easy' ) . '#_FIRSTNAME <br>' .
		esc_html__( 'Email: ', 'events-made-easy' ) . '#_EMAIL <br>';
	$format         = eme_nl2br_save_html( get_option( 'eme_cpi_form', $format_default ) );

	usleep( 2 );
	$form_id = uniqid();
	$form_html   = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-cpi-message-ok-$form_id' class='eme-message-success eme-cpi-message eme-cpi-message-success eme-hidden'></div><div id='eme-cpi-message-error-$form_id' class='eme-message-error eme-cpi-message eme-cpi-message-error eme-hidden'></div><div id='div_eme-cpi-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-cpi-form' method='post' action='#'>
		$nonce
		<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
		<input type='hidden' name='person_id' value='" . $person_id . "'>
   		";
	$form_html  .= eme_replace_cpiform_placeholders( $format, $person );
	$form_html  .= '</form></div>';
	return $form_html;
}

function eme_gdpr_approve_show() {
	print eme_translate( get_option( 'eme_gdpr_approve_page_content' ) );
}

function eme_show_personal_info( $email ) {
	$output = eme_translate( get_option( 'eme_gdpr_page_header' ) );
	if ( eme_count_persons_by_email( $email ) > 0 ) {
		$person_ids          = eme_get_personids_by_email( $email );
		$counted             = count( $person_ids );
		$eme_address1_string = get_option( 'eme_address1_string' );
		$eme_address2_string = get_option( 'eme_address2_string' );
		foreach ( $person_ids as $person_id ) {
			$person   = eme_get_person( $person_id );
			$answers  = eme_get_person_answers( $person['person_id'] );
			$members  = eme_get_members( '', 'people.person_id=' . $person['person_id'] );
			$groups   = join( ',', eme_get_persongroup_names( $person['person_id'] ) );
			$massmail = $person['massmail'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
			$gdpr     = $person['gdpr'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
			$output  .= '<table>';
			$output  .= '<tr><td>' . __( 'ID', 'events-made-easy' ) . '</td><td>' . intval( $person['person_id'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'Last name', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['lastname'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'First name', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['firstname'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'Email', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['email'] ) . '</td></tr>';
			$output  .= '<tr><td>' . $eme_address1_string . '</td><td>' . eme_esc_html( $person['address1'] ) . '</td></tr>';
			$output  .= '<tr><td>' . $eme_address2_string . '</td><td>' . eme_esc_html( $person['address2'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'City', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['city'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'Postal code', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['zip'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'State', 'events-made-easy' ) . '</td><td>' . eme_esc_html( eme_get_state_name( $person['state_code'], $person['country_code'], $person['lang'] ) ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'Country', 'events-made-easy' ) . '</td><td>' . eme_esc_html( eme_get_country_name( $person['country_code'], $person['lang'] ) ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'Phone number', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $person['phone'] ) . '</td></tr>';
			$output  .= '<tr><td>' . __( 'MassMail', 'events-made-easy' ) . '</td><td>' . $massmail . '</td></tr>';
			$output  .= '<tr><td>' . __( 'GDPR approval', 'events-made-easy' ) . '</td><td>' . $gdpr . '</td></tr>';
			if ( ! empty( $person['properties']['image_id'] ) ) {
				$img = wp_get_attachment_image( $person['properties']['image_id'], 'full', 0, [ 'class' => 'eme_person_image' ] );
				$output             .= '<tr><td>' . __( 'Image', 'events-made-easy' ) . '</td><td>' . $img . '</td></tr>';
			}
			$output .= '<tr><td>' . __( 'Member of group(s)', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $groups ) . '</td></tr>';
			foreach ( $answers as $answer ) {
				$formfield = eme_get_formfield( $answer['field_id'] );
				if ( ! empty( $formfield ) ) {
					$name       = eme_trans_esc_html( $formfield['field_name'] );
					$tmp_answer = eme_answer2readable( $answer['answer'], $formfield, 1, '<br>', 'html' );
					$output    .= "<tr><td>$name</td><td>$tmp_answer</td></tr>";
				}
			}
			// Now the media
			$files = eme_get_uploaded_files( $person_id, 'people' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					$output .= '<tr><td>' . eme_trans_esc_html( $file['field_name'] ) . '</td><td>' . "<a href='" . $file['url'] . "'>" . eme_esc_html( $file['name'] ) . '</a></td></tr>';
				}
			}
			$output .= '</table>';
			if ( count( $members ) > 0 ) {
				$output .= '<br>' . __( 'Memberships', 'events-made-easy' ) . '<br>';
				foreach ( $members as $member ) {
					$start_date         = eme_localized_date( $member['start_date'] );
					$end_date           = eme_localized_date( $member['end_date'] );
					$output            .= '<table>';
					$output            .= '<tr><td>' . __( 'ID', 'events-made-easy' ) . '</td><td>' . intval( $member['member_id'] ) . '</td></tr>';
					$output            .= '<tr><td>' . __( 'Membership', 'events-made-easy' ) . '</td><td>' . eme_esc_html( $member['membership_name'] ) . '</td></tr>';
					$output            .= '<tr><td>' . __( 'Start', 'events-made-easy' ) . '</td><td>' . $start_date . '</td></tr>';
					$output            .= '<tr><td>' . __( 'End', 'events-made-easy' ) . '</td><td>' . $end_date . '</td></tr>';
					$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
					if ( ! empty( $related_member_ids ) ) {
						foreach ( $related_member_ids as $related_member_id ) {
							$related_member = eme_get_member( $related_member_id );
							if ( $related_member ) {
								$related_person = eme_get_person( $related_member['person_id'] );
								if ( $related_person ) {
									$output .= '<tr><td>' . __( 'Main family account for', 'events-made-easy' ) . '</td><td>' . eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'], $related_person['email'] ) ) . ' (' . eme_esc_html( $related_person['email'] ) . ')</td></tr>';
								}
							}
						}
					}
					$answers = eme_get_member_answers( $member['member_id'] );
					foreach ( $answers as $answer ) {
						$formfield = eme_get_formfield( $answer['field_id'] );
						if ( ! empty( $formfield ) ) {
							$name       = eme_trans_esc_html( $formfield['field_name'] );
							$tmp_answer = eme_answer2readable( $answer['answer'], $formfield, 1, '<br>', 'html' );
							$output    .= "<tr><td>$name</td><td>$tmp_answer</td></tr>";
						}
					}
					// Now the media
					$files = eme_get_uploaded_files( $member['member_id'], 'members' );
					if ( ! empty( $files ) ) {
						foreach ( $files as $file ) {
							$output .= '<tr><td>' . eme_trans_esc_html( $file['field_name'] ) . '</td><td>' . "<a href='" . $file['url'] . "'>" . eme_esc_html( $file['name'] ) . '</a></td></tr>';
						}
					}
					$output .= '</table>';
				}
			}
			if ( $counted > 1 ) {
				$output .= '<hr>';
			}
		}
	}
	$output .= eme_translate( get_option( 'eme_gdpr_page_footer' ) );
	return $output;
}

/**
 * Add the suggested privacy policy text to the policy postbox.
 */
function eme_gdpr_add_suggested_privacy_content() {
		$content =
		'<h3>' . __( 'What personal data we collect and why we collect it', 'events-made-easy' ) . '</h3>' .
		'<p>' . __( 'EME collects data based on the RSVP and member forms and optionally extra info in the backend for people. These are configured using by the person operating the site and thus should be explained in a global manner.', 'events-made-easy' ) . '</p>' .
		'<p>' . __( 'The data export will not show the info stored by RSVP forms if that info was unique to that event, however upon deletion all that data will be removed too.', 'events-made-easy' ) . '</p>' .
		'<p>' . __( 'EME optionally uses a cookie to store the client date and time in order to adjust for client/server time differences when showing event dates and times.', 'events-made-easy' ) . '</p>';

	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		wp_add_privacy_policy_content( __( 'Events Made Easy', 'events-made-easy' ), $content );
	}
}

function eme_gdpr_register_exporters( $exporters ) {
	$exporters[] = [
		'exporter_friendly_name' => __( 'Events Made Easy' ),
		'callback'               => 'eme_gdpr_user_data_exporter',
	];
	return $exporters;
}
function eme_gdpr_user_data_exporter( $email, $page = 1 ) {
	$export_items = [];
	if ( eme_count_persons_by_email( $email ) > 0 ) {
		$person_ids          = eme_get_personids_by_email( $email );
		$eme_address1_string = get_option( 'eme_address1_string' );
		$eme_address2_string = get_option( 'eme_address2_string' );
		foreach ( $person_ids as $person_id ) {
			$person   = eme_get_person( $person_id );
			$answers  = eme_get_person_answers( $person['person_id'] );
			$members  = eme_get_members( '', 'people.person_id=' . $person['person_id'] );
			$groups   = join( ',', eme_get_persongroup_names( $person['person_id'] ) );
			$massmail = $person['massmail'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
			$gdpr     = $person['gdpr'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
			$data     = [];
			$data[]   = [
				'name'  => __( 'Person ID', 'events-made-easy' ),
				'value' => $person['person_id'],
			];
			$data[]   = [
				'name'  => __( 'Last name', 'events-made-easy' ),
				'value' => $person['lastname'],
			];
			$data[]   = [
				'name'  => __( 'First name', 'events-made-easy' ),
				'value' => $person['firstname'],
			];
			$data[]   = [
				'name'  => __( 'Email', 'events-made-easy' ),
				'value' => $person['email'],
			];
			$data[]   = [
				'name'  => $eme_address1_string,
				'value' => $person['address1'],
			];
			$data[]   = [
				'name'  => $eme_address2_string,
				'value' => $person['address2'],
			];
			$data[]   = [
				'name'  => __( 'City', 'events-made-easy' ),
				'value' => $person['city'],
			];
			$data[]   = [
				'name'  => __( 'Postal code', 'events-made-easy' ),
				'value' => $person['zip'],
			];
			$data[]   = [
				'name'  => __( 'State', 'events-made-easy' ),
				'value' => eme_get_state_name( $person['state_code'], $person['country_code'], $person['lang'] ),
			];
			$data[]   = [
				'name'  => __( 'Country', 'events-made-easy' ),
				'value' => eme_get_country_name( $person['country_code'], $person['lang'] ),
			];
			$data[]   = [
				'name'  => __( 'Phone number', 'events-made-easy' ),
				'value' => $person['phone'],
			];
			$data[]   = [
				'name'  => __( 'MassMail', 'events-made-easy' ),
				'value' => $massmail,
			];
			$data[]   = [
				'name'  => __( 'GDPR approval', 'events-made-easy' ),
				'value' => $gdpr,
			];
			$data[]   = [
				'name'  => __( 'Member of group(s)', 'events-made-easy' ),
				'value' => $groups,
			];
			foreach ( $answers as $answer ) {
				$formfield = eme_get_formfield( $answer['field_id'] );
				if ( ! empty( $formfield ) ) {
					$data[] = [
						'name'  => eme_translate( $formfield['field_name'] ),
						'value' => eme_answer2readable( $answer['answer'], $formfield, 1, ',', 'text' ),
					];
				}
			}
			// Add this group of items to the exporters data array.
			$group_id       = 'eme-personal-data';
			$group_label    = __( 'Events Made Easy Personal Data', 'event-made-easy' );
			$export_items[] = [
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $person_id,
				'data'        => $data,
			];

			// Now the media
			$files = eme_get_uploaded_files( $person_id, 'people' );
			if ( ! empty( $files ) ) {
				$group_id    = 'eme-personal-data-media';
				$group_label = __( 'Events Made Easy Uploaded files linked to the person', 'event-made-easy' );
				$data        = [];
				foreach ( $files as $file ) {
					$data[] = [
						'name'  => eme_translate( $file['field_name'] ),
						'value' => "<a href='" . $file['url'] . "'>" . $file['name'] . '</a>',
					];
				}
				$export_items[] = [
					'group_id'    => $group_id,
					'group_label' => $group_label,
					'item_id'     => $person_id,
					'data'        => $data,
				];
			}

			if ( count( $members ) > 0 ) {
				foreach ( $members as $member ) {
					$start_date         = eme_localized_date( $member['start_date'] );
					$end_date           = eme_localized_date( $member['end_date'] );
					$data               = [];
					$data[]             = [
						'name'  => __( 'Member ID', 'events-made-easy' ),
						'value' => $member['member_id'],
					];
					$data[]             = [
						'name'  => __( 'Membership', 'events-made-easy' ),
						'value' => $member['membership_name'],
					];
					$data[]             = [
						'name'  => __( 'Start', 'events-made-easy' ),
						'value' => $start_date,
					];
					$data[]             = [
						'name'  => __( 'End', 'events-made-easy' ),
						'value' => $end_date,
					];
					$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
					if ( ! empty( $related_member_ids ) ) {
						foreach ( $related_member_ids as $related_member_id ) {
							$related_member = eme_get_member( $related_member_id );
							if ( $related_member ) {
								$related_person = eme_get_person( $related_member['person_id'] );
								if ( $related_person ) {
									$data[] = [
										'name'  => __( 'Main family account for', 'events-made-easy' ),
										'value' => eme_format_full_name( $related_person['firstname'], $related_person['lastname'] ) . ' (' . $related_person['email'] . ')',
									];
								}
							}
						}
					}
					$answers = eme_get_member_answers( $member['member_id'] );
					foreach ( $answers as $answer ) {
						$formfield = eme_get_formfield( $answer['field_id'] );
						if ( ! empty( $formfield ) ) {
							$data[] = [
								'name'  => eme_translate( $formfield['field_name'] ),
								'value' => eme_answer2readable( $answer['answer'], $formfield, 1, ',', 'text' ),
							];
						}
					}
					$group_id       = 'eme-member-data';
					$group_label    = __( 'Events Made Easy Member Data', 'event-made-easy' );
					$export_items[] = [
						'group_id'    => $group_id,
						'group_label' => $group_label,
						'item_id'     => $member['member_id'],
						'data'        => $data,
					];
					// Now the media
					$files = eme_get_uploaded_files( $member['member_id'], 'members' );
					if ( ! empty( $files ) ) {
						$group_id    = 'eme-member-data-media';
						$group_label = __( 'Events Made Easy Uploaded files linked to the member', 'event-made-easy' );
						$data        = [];
						foreach ( $files as $file ) {
							$data[] = [
								'name'  => eme_translate( $file['field_name'] ),
								'value' => "<a href='" . $file['url'] . "'>" . $file['name'] . '</a>',
							];
						}
						$export_items[] = [
							'group_id'    => $group_id,
							'group_label' => $group_label,
							'item_id'     => $person_id,
							'data'        => $data,
						];
					}
				}
			}
		}
	}
	// Returns an array of exported items for this pass, but also a boolean whether this exporter is finished.
	//If not it will be called again with $page increased by 1.
	return [
		'data' => $export_items,
		'done' => true,
	];
}

function eme_gdpr_register_erasers( $erasers = [] ) {
	$erasers[] = [
		'eraser_friendly_name' => __( 'Events Made Easy' ),
		'callback'             => 'eme_gdpr_user_data_eraser',
	];
	return $erasers;
}
function eme_gdpr_user_data_eraser( $email, $page = 1 ) {
	if ( empty( $email ) ) {
		return [
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => [],
			'done'           => true,
		];
	}
	$person_ids = eme_get_personids_by_email( $email );
	if ( ! empty( $person_ids ) ) {
		$ids = join( ',', $person_ids );
		eme_gdpr_trash_people( $ids );
	}
	$items_removed  = true;
	$items_retained = false;
	$messages       = [];
	$messages[]     = __( "All data from the plugin Events Made Easy related to this email has been removed, but don't forget this also cancelled the corresponding memberships!", 'events-made-easy' );
	return [
		'items_removed'  => $items_removed,
		'items_retained' => $items_retained,
		'messages'       => $messages,
		'done'           => true,
	];
}


