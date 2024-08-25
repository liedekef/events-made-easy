<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_booking() {
	$booking = [
		'event_id'         => 0,
		'person_id'        => 0,
		'payment_id'       => 0,
		'booking_seats'    => 0,
		'booking_seats_mp' => '',
		'status'           => EME_RSVP_STATUS_PENDING,
		'booking_comment'  => '',
		'event_price'      => '',
		'booking_paid'     => 0,
		'received'         => '',
		'remaining'        => 0,
		'pg'               => '',
		'pg_pid'           => '',
		'discount'         => '',
		'discountids'      => '',
		'dcodes_entered'   => [],
		'dcodes_used'      => [],
		'dgroupid'         => 0,
		'waitinglist'      => 0,
	];

	return $booking;
}

function eme_add_booking_form( $event_id, $only_if_not_registered = 0 ) {
	$event           = eme_get_event( $event_id );
	$event_ids       = [ 0 => $event ];
	$is_multibooking = 0;
	// we don't worry about the eme_register_empty_seats param, for attendance-like events it is checked later on
	return eme_add_multibooking_form( $event_ids, 0, 0, 0, 0, 0, $is_multibooking, $only_if_not_registered );
}

function eme_add_multibooking_form( $events, $template_id_header = 0, $template_id_entry = 0, $multiprice_template_id_entry = 0, $template_id_footer = 0, $eme_register_empty_seats = 0, $is_multibooking = 1, $only_if_not_registered = 0, $only_one_event = 0, $only_one_seat = 0, $simple = 0 ) {
	
	// we need template ids
	$format_entry            = '';
	$multiprice_format_entry = '';
	$format_header           = '';
	$format_footer           = '';
	if ( $is_multibooking ) {
		if ( $template_id_header ) {
			$format_header = eme_get_template_format( $template_id_header );
		}
		if ( $multiprice_template_id_entry ) {
			$multiprice_format_entry = eme_get_template_format( $multiprice_template_id_entry );
		}
		if ( $template_id_entry ) {
			$format_entry = eme_get_template_format( $template_id_entry );
		} elseif ( $simple ) {
			$format_entry = '#_EVENTNAME #_STARTDATE #_STARTTIME';
		} elseif ( $only_one_event ) {
			$format_entry = '#_EVENTNAME #_STARTDATE #_STARTTIME (#_AVAILABLESEATS/#_TOTALSEATS)';
		} else {
			$format_entry = '#_EVENTNAME #_STARTDATE #_STARTTIME: #_SEATS<br>';
		}
		if ( $only_one_event ) {
			// for only_one_event, make sure no #_SEATS is in there
			$format_entry = str_replace( '#_SEATS', '', $format_entry );
		}
		if ( $template_id_footer ) {
			$format_footer = eme_get_template_format( $template_id_footer );
		} else {
			$format_footer = __( 'Last name', 'events-made-easy' ) . ': #_LASTNAME<br>' . __( 'First name', 'events-made-easy' ) . ': #_FIRSTNAME<br>' . __( 'Email', 'events-made-easy' ) . ': #_EMAIL<br>#_SUBMIT';
		}
	}
	if ( has_filter( 'eme_add_booking_form_prefilter' ) ) {
		$format_entry = apply_filters( 'eme_add_booking_form_prefilter', $format_entry );
		if ( ! empty( $multiprice_format_entry ) ) {
			$multiprice_format_entry = apply_filters( 'eme_add_booking_form_prefilter', $multiprice_format_entry );
		}
	}

	// make sure ...
	if ( $eme_register_empty_seats != 1 ) {
		$eme_register_empty_seats = 0;
	}

	if ( empty( $events ) || ! is_array( $events ) ) {
		return;
	}

	// rsvp not active or no rsvp for this event, then return
	// let's check the first event
	$event = $events[0];
	if ( ! eme_is_event_rsvp( $event ) ) {
		return;
	}

	$current_userid = get_current_user_id();
	$registration_wp_users_only = $event['registration_wp_users_only'];
	$form_class = '';
	// if we require a user to be WP registered to be able to book
	// in the backend we should not check this condition
	if ( ! eme_is_admin_request() ) {
		if ( ( $registration_wp_users_only || ! empty( $event['event_properties']['rsvp_required_group_ids']) || ! empty( $event['event_properties']['rsvp_required_membership_ids']) || $event['event_status'] == EME_EVENT_STATUS_PRIVATE || $event['event_status'] == EME_EVENT_STATUS_DRAFT || $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT ) && ! is_user_logged_in() ) {
			$form_html  = "<div class='eme-message-error eme-rsvp-message eme-rsvp-message-error'>";
			$format     = get_option( 'eme_rsvp_login_required_string' );
			$form_html .= eme_replace_event_placeholders( $format, $event );
			$form_html .= '</div>';
			return $form_html;
		}

		// check group memberships
		if ( ! empty( $event['event_properties']['rsvp_required_group_ids'] ) ) {
			$person = eme_get_person_by_wp_id( $current_userid );
			if ( empty( $person ) ) {
				return '';
			}
			$person_groupids = eme_get_persongroup_ids( $person['person_id'] );
			if ( ! empty( $person_groupids ) ) {
				$res_intersect   = array_intersect( $person_groupids, $event['event_properties']['rsvp_required_group_ids'] );
			} else {
				$res_intersect = 0;
			}
			if ( empty( $res_intersect ) ) {
				return '';
			}
		}

		// check memberships
		if ( ! empty( $event['event_properties']['rsvp_required_membership_ids'] ) ) {
			$membershipids = eme_get_active_membershipids_by_wpid( $current_userid );
			if ( ! empty( $membershipids ) ) {
				$res_intersect = array_intersect( $membershipids, $event['event_properties']['rsvp_required_membership_ids'] );
			} else {
				$res_intersect = 0;
			}
			if ( empty( $res_intersect ) ) {
				return '';
			}
		}

		if ( $event['event_properties']['invite_only'] ) {
			if ( ! eme_check_invite_url( $event['event_id'] ) ) {
				$form_html  = "<div class='eme-message-error eme-rsvp-message eme-rsvp-message-error'>";
				$format     = get_option( 'eme_rsvp_invitation_required_string' );
				$form_html .= eme_replace_event_placeholders( $format, $event );
				$form_html .= '</div>';
				return $form_html;
			}
		}
		if (! is_user_logged_in() && get_option('eme_rememberme')) {
			wp_enqueue_script( 'eme-rememberme' );
			$form_class = "class='eme-rememberme'";
		}
	}

	if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
		( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) {
		$search_tables = get_option( 'eme_autocomplete_sources' );
		if ( $search_tables != 'none' ) {
			wp_enqueue_script( 'eme-autocomplete-form' );
		}
	}

	usleep( 2 );
	$form_id   = uniqid();
	$form_html = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
<div id='eme-rsvp-addmessage-ok-$form_id' class='eme-message-success eme-rsvp-message eme-rsvp-message-success eme-hidden'></div><div id='eme-rsvp-addmessage-error-$form_id' class='eme-message-error eme-rsvp-message eme-rsvp-message-error eme-hidden'></div><div id='div_eme-payment-form-$form_id' class='eme-payment-form eme-hidden'></div><div id='div_eme-rsvp-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-rsvp-form' method='post' $form_class action='#' >";
	// add a nonce for extra security
	$form_html .= wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
	// also add a honeypot field: if it gets completed with data,
	// it's a bot, since a humand can't see this (using CSS to render it invisible)
	$form_html .= "<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>";
	if ( $is_multibooking ) {
		$form_html .= "<input type='hidden' name='eme_is_multibooking' value='$is_multibooking'>";
		if ( $simple ) {
			$form_html .= "<input type='hidden' name='simple_multibooking' value='1'>";
		} else {
			$form_html .= "<input type='hidden' name='eme_multibooking_tpl_id' value='$template_id_entry'>";
		}
	}
	if ( $eme_register_empty_seats ) {
		$form_html .= "<input type='hidden' name='eme_register_empty_seats' value='$eme_register_empty_seats'>";
	}
	if ( $only_if_not_registered ) {
		$form_html .= "<input type='hidden' name='only_if_not_registered' value='$only_if_not_registered'>";
	}
	if ( ! eme_is_admin_request() && ( $registration_wp_users_only || $event['event_status'] == EME_EVENT_STATUS_PRIVATE || $event['event_status'] == EME_EVENT_STATUS_DRAFT || $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT ) ) {
		// if in the frontend and wp membership is required
		// and we're logged in (otherwise we don't get here)
		$form_html .= "<input type='hidden' name='wp_id' value='$current_userid'>";
	}
	$form_html .= "<input type='hidden' name='person_id' value=''>";

	if ( $is_multibooking ) {
		$form_html .= eme_replace_extra_multibooking_formfields_placeholders( $format_header, $event );
	}

	if ( $is_multibooking && $only_one_event && ! $simple ) {
		$form_html .= "<select name='eme_event_ids[]'>";
	}
	foreach ( $events as $tmp_event ) {
		$event_id = $tmp_event['event_id'];
		if ( eme_is_multi( $tmp_event['price'] ) && ! empty( $multiprice_format_entry ) ) {
			$event_booking_format_entry = $multiprice_format_entry;
		} else {
			$event_booking_format_entry = $format_entry;
		}
		if ( $only_if_not_registered && $current_userid && eme_get_booking_ids_by_wp_event_id( $current_userid, $event_id ) ) {
			continue;
		}

		// if only one 1 allowed, set the min/max to 1 and hide it
		if ( $only_one_seat ) {
			$tmp_event['event_properties']['min_allowed'] = 1;
			$tmp_event['event_properties']['max_allowed'] = 1;
		}

		$eme_event_rsvp_status = eme_event_rsvp_status( $tmp_event );
		// only when rsvp is started. If not multibooking we show an error if not started
		if ( $eme_event_rsvp_status != 1 ) {
			if ( ! $is_multibooking ) {
				if ( $eme_event_rsvp_status == 0 ) {
					$mess = get_option( 'eme_rsvp_not_yet_allowed_string' );
				} else {
					$mess = get_option( 'eme_rsvp_no_longer_allowed_string' );
				}
				$mess = eme_translate( $mess );
				return "<div class='eme-message-error eme-rsvp-message eme-rsvp-message-error'>" . $mess . '</div>';
			} else {
				continue;
			}
		}

		$seats_available = eme_are_seats_available( $tmp_event );
		if ( ! $seats_available ) {
			if ( ! $is_multibooking ) {
				$eme_rsvp_full_text = get_option( 'eme_rsvp_full_string' );
				$form_html         .= "<div class='eme-message-error eme-rsvp-message-error'>$eme_rsvp_full_text</div>";
			}
		} else {
			$new_booking = eme_new_booking();
			if ( $is_multibooking && $only_one_event ) {
				if ( $simple ) {
					$value      = eme_replace_event_placeholders( $event_booking_format_entry, $tmp_event );
					$form_html .= "<input type='radio' id='eme_event_{$event_id}' name='eme_event_ids[]' value='$event_id'> <label for='eme_event_{$event_id}'>" . eme_esc_html( $value ) . '</label>';
				} else {
					$form_html .= "<option value='$event_id'>" . eme_replace_event_placeholders( $event_booking_format_entry, $tmp_event ) . '</option>';
				}
			} elseif ( $is_multibooking && $simple ) {
				$value      = eme_replace_event_placeholders( $event_booking_format_entry, $tmp_event );
				$form_html .= "<input type='checkbox' name='eme_event_ids[]' id='eme_event_ids_{$event_id}' value='$event_id'> <label for='eme_event_ids_{$event_id}'>" . eme_esc_html( $value ) . '</label>';
			} else {
				$form_html .= "<input type='hidden' name='eme_event_ids[]' value='$event_id'>";
			}
			// for autocomplete js and single events, we need the event id (so autocomplete only works for authorized users)
			// regular formfield replacement here, but indicate that it is for multibooking
			if ( $is_multibooking ) {
				if ( $simple ) {
					$form_html .= eme_replace_rsvp_formfields_placeholders( $tmp_event, $new_booking, '#_SEATS <br>', $is_multibooking );
				} else {
					$form_html .= eme_replace_rsvp_formfields_placeholders( $tmp_event, $new_booking, $event_booking_format_entry, $is_multibooking );
				}
			} else {
				$form_html .= eme_replace_rsvp_formfields_placeholders( $tmp_event, $new_booking );
			}
		}
	}
	if ( $is_multibooking ) {
		if ( $only_one_event && ! $simple ) {
			$form_html .= '</select>';
			foreach ( $events as $tmp_event ) {
				$event_id    = $tmp_event['event_id'];
				$var_prefix  = "bookings[$event_id][";
				$var_postfix = ']';
				$fieldname   = "{$var_prefix}bookedSeats{$var_postfix}";
				$form_html  .= "<input type='hidden' name='$fieldname' value='1'>";
			}
		}
		$form_html .= eme_replace_extra_multibooking_formfields_placeholders( $format_footer, $event );
	}
	$form_html .= '</form></div>';
	if ( has_filter( 'eme_add_booking_form_filter' ) ) {
		$form_html = apply_filters( 'eme_add_booking_form_filter', $form_html );
	}

	return $form_html;
}

function eme_add_booking_form_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract( shortcode_atts( [ 'id' => 0 ], $atts ) );
	if ( $id ) {
		return eme_add_booking_form( $id );
	}
}

function eme_add_simple_multibooking_form_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'id'                     => 0,
				'recurrence_id'          => 0,
				'category_id'            => 0,
				'template_id_header'     => 0,
				'template_id'            => 0,
				'template_id_footer'     => 0,
				'register_empty_seats'   => 0,
				'only_if_not_registered' => 0,
				'only_one_event'         => 0,
				'only_one_seat'          => 0,
				'scope'                  => '',
				'order'                  => 'ASC',
			],
		    $atts
		)
	);
	$register_empty_seats   = filter_var( $register_empty_seats, FILTER_VALIDATE_BOOLEAN );
	$only_if_not_registered = filter_var( $only_if_not_registered, FILTER_VALIDATE_BOOLEAN );
	$only_one_event         = filter_var( $only_one_event, FILTER_VALIDATE_BOOLEAN );
	$only_one_seat          = filter_var( $only_one_seat, FILTER_VALIDATE_BOOLEAN );
	$ids                    = explode( ',', $id );
	if ( ! empty( $recurrence_id ) ) {
		// we only want future events, so set the second arg to 1
		$ids    = eme_get_recurrence_eventids( $recurrence_id, 1 );
		$events = eme_get_rsvp_event_arr( $ids );
	} elseif ( ! empty( $category_id ) || ! empty( $scope ) ) {
		$events = eme_get_events( scope: $scope, order: $order, category: $category_id );
	} else {
		$events = eme_get_rsvp_event_arr( $ids );
	}
	//if ($ids && $template_id_header && $template_id && $template_id_footer)
	if ( ! empty( $events ) ) {
		return eme_add_multibooking_form( $events, $template_id_header, $template_id, 0, $template_id_footer, $register_empty_seats, 1, $only_if_not_registered, $only_one_event, $only_one_seat, 1 );
	}
}

function eme_add_multibooking_form_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'id'                     => '',
				'recurrence_id'          => 0,
				'category_id'            => 0,
				'template_id_header'     => 0,
				'template_id'            => 0,
				'multiprice_template_id' => 0,
				'template_id_footer'     => 0,
				'register_empty_seats'   => 0,
				'only_if_not_registered' => 0,
				'only_one_event'         => 0,
				'only_one_seat'          => 0,
				'scope'                  => '',
				'order'                  => 'ASC',
				'simple'                 => 0,
			],
		    $atts
		)
	);
	$register_empty_seats   = filter_var( $register_empty_seats, FILTER_VALIDATE_BOOLEAN );
	$only_if_not_registered = filter_var( $only_if_not_registered, FILTER_VALIDATE_BOOLEAN );
	$only_one_event         = filter_var( $only_one_event, FILTER_VALIDATE_BOOLEAN );
	$only_one_seat          = filter_var( $only_one_seat, FILTER_VALIDATE_BOOLEAN );
	$simple                 = filter_var( $simple, FILTER_VALIDATE_BOOLEAN );
	$ids                    = explode( ',', $id );
	if ( ! empty( $recurrence_id ) ) {
		// we only want future events, so set the second arg to 1
		$ids    = eme_get_recurrence_eventids( $recurrence_id, 1 );
		$events = eme_get_rsvp_event_arr( $ids );
	} elseif ( ! empty( $category_id ) || ! empty( $scope ) ) {
		$events = eme_get_events( scope: $scope, order: $order, category: $category_id, extra_conditions: 'event_rsvp=1' );
	} else {
		$events = eme_get_rsvp_event_arr( $ids );
	}
	//if ($ids && $template_id_header && $template_id && $template_id_footer)
	if ( ! empty( $events ) ) {
		return eme_add_multibooking_form( $events, $template_id_header, $template_id, $multiprice_template_id, $template_id_footer, $register_empty_seats, 1, $only_if_not_registered, $only_one_event, $only_one_seat, $simple );
	}
}

function eme_booking_list_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'id'                 => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'template_id_footer' => 0,
				'rsvp_status'        => 0,
				'approval_status'    => 0,
				'paid_status'        => 0,
				'order'              => '',
				'scope'              => 'future',
				'always_header_footer' => 0,
			],
		    $atts
		)
	);
	$always_header_footer = intval( $always_header_footer );
	$approval_status = intval( $approval_status );
	$rsvp_status     = intval( $rsvp_status );
	if ( $approval_status == 1 ) {
		$rsvp_status = EME_RSVP_STATUS_PENDING;
	}
	if ( $approval_status == 2 ) {
		$rsvp_status = EME_RSVP_STATUS_APPROVED;
	}
	$paid_status = intval( $paid_status );
	if ( empty( $id ) && eme_is_single_event_page() ) {
		$id = eme_sanitize_request( get_query_var( 'event_id' ) );
	}
	if ( !empty( $id ) ) {
		$event = eme_get_event( $id );
		if ( ! empty( $event ) ) {
			return eme_get_bookings_list_for_event( $event, $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status, 0, $order, $always_header_footer );
		}
	} elseif (!empty($scope)) {
		$events = eme_get_events( scope: $scope, extra_conditions: 'event_rsvp=1' );
		$res = '';
		foreach ($events as $event) {
			$res .= eme_get_bookings_list_for_event( $event, $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status, 0, $order, $always_header_footer );
		}
		return $res;
	} else {
		return '';
	}
}

function eme_mybooking_list_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'id'                 => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'template_id_footer' => 0,
				'future'             => null,
				'scope'              => 'future',
				'rsvp_status'        => 0,
				'approval_status'    => 0,
				'paid_status'        => 0,
			],
		    $atts
		)
	);
	$approval_status = intval( $approval_status );
	$rsvp_status     = intval( $rsvp_status );
	// future (old param) overrides scope if set
	if ( ! is_null( $future ) ) {
		$scope = intval( $future );
	}
	if ( $approval_status == 1 ) {
		$rsvp_status = EME_RSVP_STATUS_PENDING;
	}
	if ( $approval_status == 2 ) {
		$rsvp_status = EME_RSVP_STATUS_APPROVED;
	}
	$paid_status = intval( $paid_status );
	if ( is_user_logged_in() ) {
		$wp_id = get_current_user_id();
		if ( $id && $wp_id ) {
			$event = eme_get_event( $id );
			if ( ! empty( $event ) ) {
					return eme_get_bookings_list_for_event( $event, $template_id, $template_id_header, $template_id_footer, 0, 0, $wp_id, $order );
			} else {
				return '';
			}
		} elseif ( $wp_id ) {
			return eme_get_bookings_list_for_wp_id( $wp_id, $scope, '', $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status );
		} else {
			return '';
		}
	}
}

function eme_attendee_list_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'id'                 => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'template_id_footer' => 0,
				'rsvp_status'        => 0,
				'approval_status'    => 0,
				'paid_status'        => 0,
				'order'              => '',
				'scope'              => 'future',
				'always_header_footer' => 0,
			],
		    $atts
		)
	);
	$always_header_footer = intval( $always_header_footer );
	$approval_status = intval( $approval_status );
	$rsvp_status     = intval( $rsvp_status );
	if ( $approval_status == 1 ) {
		$rsvp_status = EME_RSVP_STATUS_PENDING;
	}
	if ( $approval_status == 2 ) {
		$rsvp_status = EME_RSVP_STATUS_APPROVED;
	}
	$paid_status = intval( $paid_status );
	if ( empty( $id ) && eme_is_single_event_page() ) {
		$id = eme_sanitize_request( get_query_var( 'event_id' ) );
	}
	if ( !empty( $id ) ) {
		$event = eme_get_event( $id );
		if ( ! empty( $event ) ) {
			return eme_get_attendees_list( $event, $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status, $order, $always_header_footer );
		}
	} elseif (!empty($scope)) {
		$events = eme_get_events( scope: $scope, extra_conditions: 'event_rsvp=1' );
		$res = '';
		foreach ($events as $event) {
			$res .= eme_get_attendees_list( $event, $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status, $order, $always_header_footer );
		}
		return $res;
	} else {
		return '';
	}
}

function eme_attendees_report_link_shortcode( $atts ) {
	global $post;
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'title'              => __( 'Attendees CSV', 'events-made-easy' ),
				'scope'              => 'this_month',
				'event_template_id'  => 0,
				'attend_template_id' => 0,
				'category'           => '',
				'notcategory'        => '',
				'public_access'      => 0,
			],
		    $atts
		)
	);
	$public_access = filter_var( $public_access, FILTER_VALIDATE_BOOLEAN );
	if ( ( ! is_user_logged_in() || ! current_user_can( get_option( 'eme_cap_list_registrations' ) ) ) && ! $public_access ) {
		return;
	}
	// public access? Then page needs to be password protected
	if ( $public_access && empty( $post->post_password ) ) {
		return;
	}

	$args                  = compact( 'scope', 'event_template_id', 'attend_template_id', 'category', 'notcategory', 'public_access' );
	$args['eme_attendees'] = 'report';
	$url                   = eme_current_page_url( $args );
	// add nonce, so public access can't be faked
	if ( $public_access ) {
		$url = wp_nonce_url( $url, "eme_attendees $public_access", 'eme_attendees_nonce' );
	}
	return "<a href='$url' title='" . esc_attr( $title ) . "'>" . esc_html( $title ) . '</a>';
}

function eme_bookings_report_link_shortcode( $atts ) {
	global $post;
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
				'title'              => __( 'Bookings CSV', 'events-made-easy' ),
				'event_id'           => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'public_access'      => 0,
			],
		    $atts
		)
	);
	$public_access = filter_var( $public_access, FILTER_VALIDATE_BOOLEAN );
	if ( ( ! is_user_logged_in() || ! current_user_can( get_option( 'eme_cap_list_registrations' ) ) ) && ! $public_access ) {
		return;
	}
	// public access? Then page needs to be password protected
	if ( $public_access && empty( $post->post_password ) ) {
		return;
	}
	if ( empty( $event_id ) || empty( $template_id ) ) {
		return;
	}

	$args                 = compact( 'event_id', 'template_id', 'template_id_header', 'public_access' );
	$args['eme_bookings'] = 'report';
	$url                  = eme_current_page_url( $args );
	// add nonce, so public access can't be faked
	if ( $public_access ) {
		$url = wp_nonce_url( $url, "eme_bookings $public_access", 'eme_bookings_nonce' );
	}
	return "<a href='$url' title='" . esc_attr( $title ) . "'>" . esc_html( $title ) . '</a>';
}

function eme_bookings_frontend_csv_report( $event_id, $template_id, $template_id_header ) {
	if ( ! empty( $event_id ) ) {
		$bookings = eme_get_bookings_for( $event_id );
	} else {
		return '';
	}

	if ( empty( $template_id ) ) {
		return '';
	}

	$separator = get_option( 'eme_csv_separator' );
        if ( eme_is_empty_string( $separator ) ) {
                $separator = ',';
        }

	$format            = '';
	$eme_format_header = '';
	// no nl2br for csv output
	$format = eme_get_template_format( $template_id, 0 );

	header( 'Content-type: text/csv; charset=UTF-8' );
        header( 'Content-Encoding: UTF-8' );
	header( 'Content-Disposition: attachment; filename=report-' . date( 'Ymd-His' ) . '.csv' );
        eme_nocache_headers();
        echo "\xEF\xBB\xBF"; // UTF-8 BOM, Excell otherwise doesn't show the characters correctly ...

	$fp = fopen( 'php://output', 'w' );

	if ( $template_id_header ) {
		// no nl2br for csv output
		$eme_format_header = eme_get_template_format( $template_id_header, 0 );
		$headers           = explode( ',', $eme_format_header );
		eme_fputcsv( $fp, $headers, $separator );
	}

	$event = eme_get_event( $event_id );
	if ( ! empty( $event ) ) {
		foreach ( $bookings as $booking ) {
			$line       = [];
			$format_arr = explode( ',', $format );
			$line_count = 1;
			foreach ( $format_arr as $single_format ) {
				#$line[]=eme_replace_member_placeholders($single_format, $membership, $member, "text");
				$el       = eme_replace_booking_placeholders( $single_format, $event, $booking, 0, 'text' );
				$el_arr   = preg_split( '/\R/', $el );
				$el_count = count( $el_arr );
				if ( $el_count > $line_count ) {
					$line_count = $el_count;
				}
				$line[] = $el_arr;
			}
			// now we have a line that contains arrays on every position, with $line_count indicating the max size of an arr in the line
			// so let's use that to output multiple lines
			for ( $i = 0;$i < $line_count;$i++ ) {
				$output = [];
				foreach ( $line as $el_arr ) {
					if ( isset( $el_arr[ $i ] ) ) {
						$output[] = $el_arr[ $i ];
					} else {
						$output[] = '';
					}
				}
				eme_fputcsv( $fp, $output, $separator );
			}
		}
	}
	fclose( $fp );
	exit;
}

function eme_attendees_frontend_csv_report( $scope, $category, $notcategory, $event_template_id, $attend_template_id ) {
	$events = eme_get_events( scope: $scope, category: $category, notcategory: $notcategory );
	// we really don't want nl2br to happen for csv output
	$attend_format = eme_get_template_format( $attend_template_id, 0 );
	$event_format  = eme_get_template_format( $event_template_id, 0 );
	if ( ! $attend_format ) {
		$attend_format = '#_ATTENDFIRSTNAME #_ATTENDLASTNAME';
	}
	if ( ! $event_format ) {
		$event_format = '#_EVENTNAME #_STARTTIME';
	}

	eme_nocache_headers();
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=report-' . date( 'Ymd-His' ) . '.csv' );
	$fp      = fopen( 'php://output', 'w' );
	$headers = [ __( 'Title\Date', 'events-made-easy' ) ];

	$all_attendees     = [];
	$all_attendees_rec = [];
	$all_dates         = [];
	foreach ( $events as $event ) {
		$event_id         = $event['event_id'];
		$recurrence_id    = $event['recurrence_id'];
		$event_start_date = eme_get_date_from_dt( $event['event_start'] );
		if ( $recurrence_id ) {
			$all_attendees_rec[ $recurrence_id ][ $event_start_date ] = eme_get_attendees( $event_id );
		} else {
			$all_attendees[ $event_id ][ $event_start_date ] = eme_get_attendees( $event_id );
		}
		$all_dates[ $event_start_date ] = 1;
	}
	ksort( $all_dates );
	foreach ( $all_dates as $event_start_date => $val ) {
		$headers[] = $event_start_date;
	}
	eme_fputcsv( $fp, $headers );
	$handled_recurrence_ids = [];
	foreach ( $events as $event ) {
		$line          = [];
		$event_id      = $event['event_id'];
		$recurrence_id = $event['recurrence_id'];
		if ( isset( $handled_recurrence_ids[ $recurrence_id ] ) ) {
			continue;
		}
		$line[] = eme_replace_event_placeholders( $event_format, $event );
		foreach ( $all_dates as $event_start_date => $val ) {
			if ( isset( $all_attendees_rec[ $recurrence_id ][ $event_start_date ] ) ) {
				$list = '';
				foreach ( $all_attendees_rec[ $recurrence_id ][ $event_start_date ] as $attendee ) {
					$list .= eme_replace_attendees_placeholders( $attend_format, $event, $attendee ) . "\r\n";
				}
				$line[] = $list;
			} elseif ( isset( $all_attendees[ $event_id ][ $event_start_date ] ) ) {
				$list = '';
				foreach ( $all_attendees[ $event_id ][ $event_start_date ] as $attendee ) {
					$list .= eme_replace_attendees_placeholders( $attend_format, $event, $attendee ) . "\r\n";
				}
				$line[] = $list;
			} else {
				$line[] = '';
			}
		}
		if ( $recurrence_id ) {
			$handled_recurrence_ids[ $recurrence_id ] = 1;
		}
		eme_fputcsv( $fp, $line );
	}
	fclose( $fp );
	exit;
}

function eme_cancel_bookings_form( $event_id ) {
	$form_html           = '';
	$event               = eme_get_event( $event_id );
	// rsvp not active or no rsvp for this event, then return
	if ( empty( $event ) || ! eme_is_event_rsvp( $event ) ) {
		return;
	}
	$registration_wp_users_only = $event['registration_wp_users_only'];
	if ( ( $registration_wp_users_only || $event['event_status'] == EME_EVENT_STATUS_PRIVATE || $event['event_status'] == EME_EVENT_STATUS_DRAFT || $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT  ) && ! is_user_logged_in() ) {
		// we require a user to be WP registered to be able to delete a booking
		$form_html  = "<div class='eme-message-error eme-rsvp-message eme-rsvp-message-error'>";
		$format     = get_option( 'eme_rsvp_login_required_string' );
		$form_html .= eme_replace_event_placeholders( $format, $event );
		$form_html .= '</div>';
		return $form_html;
	}

	if ( eme_is_event_rsvp_ended( $event ) || eme_is_event_cancelrsvp_ended( $event ) ) {
		$no_longer_allowed = get_option( 'eme_rsvp_cancel_no_longer_allowed_string' );
		$form_html         = "<div class='eme-message-error eme-cancel-bookings-message-error'>" . $no_longer_allowed . '</div>';
	} else {
		usleep( 2 );
		$form_id = uniqid();
		$nonce   = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );

		$form_html  = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
	   <div id='eme-cancel-bookings-message-ok-$form_id' class='eme-message-success eme-cancel-bookings-message eme-cancel-bookings-message-success eme-hidden'></div><div id='eme-cancel-bookings-message-error-$form_id' class='eme-message-error eme-cancel-bookings-message eme-cancel-bookings-message-error eme-hidden'></div><div id='div_eme-cancel-bookings-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-cancel-bookings-form' method='post' action='#'>";
		$form_html .= $nonce;
		$form_html .= "<input type='hidden' name='event_id' value='$event_id'>";
		$form_html .= "<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>";
		$form_html .= eme_replace_cancelformfields_placeholders( $event );
		$form_html .= '</form></div>';
		if ( has_filter( 'eme_cancel_booking_form_filter' ) ) {
			$form_html = apply_filters( 'eme_cancel_booking_form_filter', $form_html );
		}
		// backwards compatible
		if ( has_filter( 'eme_delete_booking_form_filter' ) ) {
			$form_html = apply_filters( 'eme_delete_booking_form_filter', $form_html );
		}
	}
	return $form_html;
}

function eme_cancel_bookings_form_shortcode( $atts ) {
	extract( shortcode_atts( [ 'id' => 0 ], $atts ) );
	return eme_cancel_bookings_form( $id );
}

add_action( 'wp_ajax_eme_add_bookings', 'eme_add_bookings_ajax' );
add_action( 'wp_ajax_nopriv_eme_add_bookings', 'eme_add_bookings_ajax' );
function eme_add_bookings_ajax() {
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
	if ( empty( $_POST['eme_event_ids'] ) ) {
		$form_html = __( 'Please select at least one event.', 'events-made-easy' );
		echo wp_json_encode(
		    [
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	$events = [];
	if ( !empty( $_POST['eme_event_ids'] ) && eme_is_numeric_array( $_POST['eme_event_ids'] ) ) {
		$events = eme_get_rsvp_event_arr( $_POST['eme_event_ids'] );
	}
	if ( empty( $events ) ) {
		$form_html = __( 'Please select at least one event.', 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	// check membership requirement
	$memberships_failed = 0;
	$usage_count_update_needed_for = [];
	foreach ($events as $event) {
		if ( ! empty( $event['event_properties']['rsvp_required_membership_ids'] ) ) {
			// membership required? Then the user is also required to be logged in
			if (! is_user_logged_in() ) {
				$memberships_failed = 1;
				continue;
			}
			$current_userid = get_current_user_id();
                        $membership_ids = eme_get_active_membershipids_by_wpid( $current_userid );
                        if ( ! empty( $membership_ids ) ) {
                                $res_intersect = array_intersect( $membership_ids, $event['event_properties']['rsvp_required_membership_ids'] );
                        } else {
                                $res_intersect = 0;
                        }
                        if ( empty( $res_intersect ) ) {
				$memberships_failed = 1;
				continue;
                        }
			// now check the usage_count: if we find 1 membership still "ok", we use that
			$usage_count_failed = 1;
			foreach ( $res_intersect as $membership_id ) {
				$membership = eme_get_membership( $membership_id );
				$member = eme_get_member_by_wpid_membershipid( $current_userid, $membership['membership_id'] );
				# max_usage_count = 0 is unlimited, so check that (the case where a member had a usage count before and then the membership max was later set to 0)
				if ($membership['properties']['max_usage_count'] == 0 || 
					($membership['properties']['max_usage_count']>0 && $member['properties']['usage_count']<$membership['properties']['max_usage_count'])) {
					// if a member needs a turn update, we only do it once for the whole multibooking
					$usage_count_update_needed_for[$member['member_id']] = $member;
					$usage_count_failed = 0;
					continue;
				}
			}
			if ($usage_count_failed>0) {
				$memberships_failed = 1;
				continue;
			}
		}
	}

	if ( $memberships_failed == 1 ) {
		$form_html = __( 'No valid membership found.', 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	$event       = $events[0];
	$captcha_res = eme_check_captcha( $event['event_properties'] );

	if ( isset( $_POST['eme_is_multibooking'] ) && intval( $_POST['eme_is_multibooking'] ) > 0 ) {
		$is_multibooking = 1;
	} else {
		$is_multibooking = 0;
	}
	$payment_id = 0;
	if ( ! $is_multibooking ) {
		if ( has_filter( 'eme_eval_booking_form_post_filter' ) ) {
			$eval_filter_return = apply_filters( 'eme_eval_booking_form_post_filter', $event );
		} else {
			$eval_filter_return = [
				0 => 1,
				1 => '',
			];
		}
	} elseif ( has_filter( 'eme_eval_multibooking_form_post_filter' ) ) {
			$eval_filter_return = apply_filters( 'eme_eval_multibooking_form_post_filter', $events );
	} else {
		$eval_filter_return = [
			0 => 1,
			1 => '',
		];
	}
	if ( is_array( $eval_filter_return ) && ! $eval_filter_return[0] ) {
		// the result of own eval rules failed, so let's use that as a result
		$form_result_message = $eval_filter_return[1];
	} else {
		$send_mail = get_option( 'eme_rsvp_mail_notify_is_active' );
		if ( $is_multibooking && isset( $_POST['simple_multibooking'] ) ) {
			$simple      = 1;
			$booking_res = eme_multibook_seats( $events, $send_mail, '', $is_multibooking, $simple );
		} elseif ( $is_multibooking ) {
			if ( isset( $_POST['eme_multibooking_tpl_id'] ) ) {
				$tpl_id = intval($_POST['eme_multibooking_tpl_id']);
			} else {
				$tpl_id = 0;
			}
			if ( $tpl_id ) {
				$format_entry = eme_get_template_format( $tpl_id );
			} else {
				$format_entry = '#_{d/m/Y} #_24HSTARTTIME: #_SEATS<br>';
			}
			$booking_res = eme_multibook_seats( $events, $send_mail, $format_entry );
		} else {
			$booking_res = eme_book_seats( $event, $send_mail );
		}
		$form_result_message = $booking_res[0];
		$payment_id          = $booking_res[1];
		// booking done, now update the usage_count for the members needed (if booking succeeded, meaning payment id is not empty)
		if ($payment_id && !empty($usage_count_update_needed_for)) {
			foreach ($usage_count_update_needed_for as $member) {
				eme_update_member_usage_count( $member );
			}
		}
	}

	// let's decide for the first event wether or not payment is needed
	if ( $payment_id && eme_event_has_pgs_configured( $event ) && ! $event['event_properties']['skippaymentoptions'] && ! $event['event_properties']['require_user_confirmation'] ) {
		if ( $event['event_properties']['selected_captcha'] == "captcha" ) {
			eme_captcha_remove( $captcha_res );
		}
		$total_price = eme_get_payment_price( $payment_id );
		if ( $total_price > 0 ) {
			$booking_ids = eme_get_payment_booking_ids( $payment_id );
			if ( count( $booking_ids ) == 1 ) {
				$is_multi = 0;
			} else {
				$is_multi = 1;
			}

			// count the payment gateways active for this event
			$pg_count = eme_event_count_pgs( $event );

			$booking = eme_get_booking( $booking_ids[0] );
			if ( $booking['waitinglist'] ) {
				$message              = get_option( 'eme_payment_booking_on_waitinglist_format' );
				$message              = eme_replace_booking_placeholders( $message, $event, $booking, $is_multi );
				$form_result_message .= '<br>' . $message;
				echo wp_json_encode(
				    [
						'Result'      => 'OK',
						'keep_form'   => 0,
						'htmlmessage' => $form_result_message,
					]
				);
			} elseif ( $pg_count == 1 && get_option( 'eme_pg_submit_immediately' ) ) {
				$payment_form = eme_payment_form( $payment_id );
				echo wp_json_encode(
				    [
						'Result'      => 'OK',
						'keep_form'   => 0,
						'htmlmessage' => $form_result_message,
						'paymentform' => $payment_form,
					]
				);
			} elseif ( get_option( 'eme_payment_redirect' ) ) {
				$payment      = eme_get_payment( $payment_id );
				$payment_url  = eme_payment_url( $payment );
				$waitperiod   = intval( get_option( 'eme_payment_redirect_wait' ) ) * 1000;
				$redirect_msg = get_option( 'eme_payment_redirect_msg' );
				if ( ! eme_is_empty_string( $redirect_msg ) ) {
					$redirect_msg         = str_replace( '#_PAYMENT_URL', $payment_url, $redirect_msg );
					$form_result_message .= '<br>' . $redirect_msg;
				}
				echo wp_json_encode(
				    [
						'Result'          => 'OK',
						'keep_form'       => 0,
						'htmlmessage'     => $form_result_message,
						'waitperiod'      => $waitperiod,
						'paymentredirect' => $payment_url,
					]
				);
			} else {
				$payment_form = eme_payment_form( $payment_id );
				echo wp_json_encode(
				    [
						'Result'      => 'OK',
						'keep_form'   => 0,
						'htmlmessage' => $form_result_message,
						'paymentform' => $payment_form,
					]
				);
			}
		} else {
			// price=0, so set it to paid, autoapprove etc ...
			// second param is set to 0 to indicate it is not an IPN based-payment
			eme_mark_payment_paid( $payment_id, 0 );
			if ( isset( $_POST['only_if_not_registered'] ) ) {
				$only_if_not_registered = intval( $_POST['only_if_not_registered'] );
			} else {
				$only_if_not_registered = 0;
			}
			if ( ! $only_if_not_registered && get_option( 'eme_rsvp_show_form_after_booking' ) && ! get_option( 'eme_rememberme' ) ) {
				echo wp_json_encode(
				    [
						'Result'      => 'OK',
						'keep_form'   => 1,
						'htmlmessage' => $form_result_message,
					]
				);
			} else {
				echo wp_json_encode(
				    [
						'Result'      => 'OK',
						'keep_form'   => 0,
						'htmlmessage' => $form_result_message,
					]
				);
			}
		}
	} elseif ( $payment_id ) {
		if ( $event['event_properties']['selected_captcha'] == "captcha" ) {
			eme_captcha_remove( $captcha_res );
		}
		// the booking is done, so if wanted let's indicate we want to show the form again
		// but of course not if the option "only_if_not_registered" was set ...
		if ( isset( $_POST['only_if_not_registered'] ) ) {
			$only_if_not_registered = intval( $_POST['only_if_not_registered'] );
		} else {
			$only_if_not_registered = 0;
		}
		if ( ! $only_if_not_registered && get_option( 'eme_rsvp_show_form_after_booking' ) && ! get_option( 'eme_rememberme' ) ) {
			echo wp_json_encode(
			    [
					'Result'      => 'OK',
					'keep_form'   => 1,
					'htmlmessage' => $form_result_message,
				]
			);
		} else {
			echo wp_json_encode(
			    [
					'Result'      => 'OK',
					'keep_form'   => 0,
					'htmlmessage' => $form_result_message,
				]
			);
		}
	} else {
		// booking failed
		echo wp_json_encode(
		    [
				'Result'      => 'NOK',
				'htmlmessage' => $form_result_message,
			]
		);
	}
	wp_die();
}

add_action( 'wp_ajax_eme_cancel_bookings', 'eme_cancel_bookings_ajax' );
add_action( 'wp_ajax_nopriv_eme_cancel_bookings', 'eme_cancel_bookings_ajax' );
function eme_cancel_bookings_ajax() {
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

	$event_id = intval( $_POST['event_id'] );
	if ( ! $event_id ) {
		$form_html = __( 'No event id detected', 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	$event = eme_get_event( $event_id );
	if ( empty( $event ) ) {
		$form_html = __( 'No event id detected', 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	}

	// now the captcha
	$captcha_res = eme_check_captcha( $event['event_properties'] );

	$registration_wp_users_only = $event['registration_wp_users_only'];

	$booking_ids = [];
	if ( $registration_wp_users_only || $event['event_status'] == EME_EVENT_STATUS_PRIVATE || $event['event_status'] == EME_EVENT_STATUS_DRAFT || $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT ) {
		// we require a user to be WP registered to be able to book
		if ( is_user_logged_in() ) {
			$wp_id       = get_current_user_id();
			$booking_ids = eme_get_booking_ids_by_wp_event_id( $wp_id, $event_id );
		}
	} elseif ( isset( $_POST['lastname'] ) && isset( $_POST['email'] ) ) {
		$bookerLastName = eme_sanitize_request( $_POST['lastname'] );
		if ( isset( $_POST['firstname'] ) ) {
			$bookerFirstName = eme_sanitize_request( $_POST['firstname'] );
		} else {
			$bookerFirstName = '';
		}
		$bookerEmail = eme_sanitize_email( $_POST['email'] );
		$booker      = eme_get_person_by_name_and_email( $bookerLastName, $bookerFirstName, $bookerEmail );
		if ( ! $booker ) {
			$booker = eme_get_person_by_email_only( $bookerEmail );
		}
		if ( $booker ) {
			$person_id   = $booker['person_id'];
			$booking_ids = eme_get_booking_ids_by_person_event_id( $person_id, $event_id );
		}
	}
	if ( ! empty( $booking_ids ) ) {
		$mail_res = 1;
		foreach ( $booking_ids as $booking_id ) {
			// first get the booking details, then delete it and then send the mail
			// the mail needs to be sent after the deletion, otherwise the count of free seats is wrong
			$booking = eme_get_booking( $booking_id );
			if ( has_action( 'eme_frontend_cancel_booking_action' ) ) {
				do_action( 'eme_frontend_cancel_booking_action', $booking );
			}
			eme_trash_booking( $booking_id );
			eme_manage_waitinglist( $event );
			$res = eme_email_booking_action( $booking, 'cancelBooking' );
			if ( ! $res ) {
				$mail_res = 0;
			}
		}
		$form_html = __( 'Booking deleted', 'events-made-easy' );
		if ( ! $mail_res ) {
			$form_html .= '<br>' . __( 'There were some problems while sending mail.', 'events-made-easy' );
		}
	} else {
		$form_html = __( 'There are no bookings associated to this name and email', 'events-made-easy' );
	}
	if ( $event['event_properties']['selected_captcha'] == "captcha" ) {
		eme_captcha_remove ( $captcha_res );
	}
	echo wp_json_encode(
	    [
			'Result'      => 'OK',
			'htmlmessage' => $form_html,
		]
	);
	wp_die();
}

function eme_multibook_seats( $events, $send_mail, $format, $is_multibooking = 1, $simple = 0 ) {
	$eme_is_admin_request = eme_is_admin_request();
	$booking_ids          = [];
	$form_html            = '';
	if ( $eme_is_admin_request && get_option( 'eme_rsvp_admin_allow_overbooking' ) ) {
		$allow_overbooking = 1;
	} else {
		$allow_overbooking = 0;
	}

	if ( ( ! isset( $_POST['eme_admin_nonce'] ) && ! isset( $_POST['eme_frontend_nonce'] ) ) ||
                ( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), 'eme_admin' ) ) ||
                ( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
                        $form_html = __( 'Access denied!', 'events-made-easy' );
                        return [
                                0 => $form_html,
                                1 => $booking_ids,
                        ];
        }

	$event = $events[0];
	if ( ! eme_is_empty_string( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request ) {
		if ( ! isset( $_POST['rsvp_password'] ) ) {
			$form_html = __( 'Password is missing', 'events-made-easy' );
			return [
				0 => $form_html,
				1 => $booking_ids,
			];
		} elseif ( eme_sanitize_request($_POST['rsvp_password']) != $event['event_properties']['rsvp_password'] ) {
			$form_html = __( 'Incorrect password given', 'events-made-easy' );
			return [
				0 => $form_html,
				1 => $booking_ids,
			];
		}
	}

	$booking_info_to_be_made = [];
	$bookedSeats_for_capacity_check = []; 
	// now do regular checks
	foreach ( $events as $event ) {
		$event_id = $event['event_id'];
		// an event without matching booking? Skip it
		// can happen for multibookings where some events are already in the past (because for those the booking form no longer shows)
		if ( $is_multibooking && ! $event['event_properties']['take_attendance'] && ! isset( $_POST['bookings'][ $event_id ] ) ) {
			continue;
		}

		$tmp_booking    = eme_booking_from_form( $event );
		$bookedSeats    = $tmp_booking['booking_seats'];
		$bookedSeats_mp = eme_convert_multi2array( $tmp_booking['booking_seats_mp'] );

		$min_allowed     = $event['event_properties']['min_allowed'];
		$max_allowed     = $event['event_properties']['max_allowed'];
		$take_attendance = 0;
		if ( $event['event_properties']['take_attendance'] ) {
			$take_attendance = 1;
		}
		if ( $take_attendance && ! eme_is_multi( $event['price'] ) ) {
			// we set min=0,max=1 for regular events, to protect people from stupid mistakes
			// we don't do this for multiprice events since you can say e.g. min=1,max=1 to force exactly 1 seat then ...
			$min_allowed = 0;
			$max_allowed = 1;
		}

		// only register empty seats if wanted, this can also be used to turn attendance in a yes/no-type event
		// but of course yes/no and not taking attendance is only usefull in multibooking events, since the eme_register_empty_seats
		// can only be set for multibooking events ...
		// the continue-statement continues the higher foreach-loop
		if ( $bookedSeats == 0 && ! $take_attendance && empty( $_POST['eme_register_empty_seats'] ) ) {
			// only add the message if not multibooking and the min allowed number of seats is >0
			if ( ! $is_multibooking && ! eme_is_multi( $min_allowed ) && $min_allowed > 0 ) {
				$form_html .= __( 'Please select at least one seat.', 'events-made-easy' );
				continue;
			} elseif ( $is_multibooking ) {
				continue;
			}
		}
		if ( $bookedSeats == 0 && $is_multibooking && $take_attendance && empty( $_POST['eme_register_empty_seats'] ) ) {
			continue;
		}

		$bookerLastName  = '';
		$bookerFirstName = '';
		$bookerEmail     = '';
		$booker_wp_id    = 0;
		if ( is_user_logged_in() ) {
			// if the user has the correct rights, we take the booker id from the form, otherwise we ignore it
			$current_userid = get_current_user_id();
			if ( $eme_is_admin_request ) {
				// a booking from the backend? Then we take the wp id from the post, not the current logged in user
				$booker_wp_id = eme_get_wpid_by_post();
			} elseif ( ! $eme_is_admin_request && ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
				( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) ) {
				$booker_wp_id = eme_get_wpid_by_post();
			} else {
				$booker_wp_id = $current_userid;
			}
		}
		if ( ( $event['event_status'] == EME_EVENT_STATUS_PRIVATE || $event['event_status'] == EME_EVENT_STATUS_DRAFT || $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT  ) && ! is_user_logged_in() ) {
			$form_html .= __( 'WP membership required to continue', 'events-made-easy' );
			continue;
		}

		$registration_wp_users_only = $event['registration_wp_users_only'];
		if ( $registration_wp_users_only ) {
			// we should never get here, but be safe anyway
			if ( ! is_user_logged_in() ) {
				$form_html .= __( 'WP membership required to continue', 'events-made-easy' );
				continue;
			} elseif ( ! $booker_wp_id ) {
				// we require a user to be WP registered to be able to book
				$form_html .= __( 'Please select a WP member from the lastname autocomplete selection', 'events-made-easy' );
				continue;
			}
		}
		if ( isset( $_POST['lastname'] ) && isset( $_POST['email'] ) ) {
			$bookerLastName = eme_sanitize_request( $_POST['lastname'] );
			if ( isset( $_POST['firstname'] ) ) {
				$bookerFirstName = eme_sanitize_request( $_POST['firstname'] );
			}
			$bookerEmail = eme_sanitize_email( $_POST['email'] );
		}

		if ( eme_is_empty_string( $bookerLastName ) ) {
			// if any required field is empty: return an error
			$form_html .= __( 'Please fill out your last name', 'events-made-easy' );
			// to be backwards compatible, don't require bookerFirstName here: it can be empty for forms that just use #_NAME
			continue;
		}

		// things we check in the frontend only
		if ( ! $eme_is_admin_request ) {
			// we require an email, but only in the frontend
			// this allows the rare case of persons without email to be booked for an event
			if ( ! eme_is_email( $bookerEmail, 1 ) ) {
				$form_html .= __( 'Please enter a valid email address', 'events-made-easy' );
				continue;
			}

			if ( $event['event_properties']['person_only_once'] ) {
				$booker = false;
				if ( isset( $_POST['person_id'] ) ) {
					$booker = eme_get_person( intval( $_POST['person_id'] ) );
				}
				if ( empty( $booker ) ) {
					$booker = eme_get_person_by_name_and_email( $bookerLastName, $bookerFirstName, $bookerEmail );
					if ( ! $booker ) {
						$booker = eme_get_person_by_email_only( $bookerEmail );
					}
				}
				if ( ! empty( $booker ) ) {
					$tmp_booking_ids = eme_get_booking_ids_by_person_event_id( $booker['person_id'], $event_id );
					if ( count( $tmp_booking_ids ) > 0 ) {
						$tmp_format = get_option( 'eme_rsvp_person_already_registered_string' );
						$tmp_format = eme_replace_event_placeholders( $tmp_format, $event );
						$form_html .= $tmp_format . '<br>';
						continue;
					}
				}
			}
			if ( $event['event_properties']['email_only_once'] && ! empty( $bookerEmail ) ) {
				$tmp_booking_ids = eme_get_booking_ids_by_email_event_id( $bookerEmail, $event_id );
				if ( count( $tmp_booking_ids ) > 0 ) {
					$tmp_format = get_option( 'eme_rsvp_email_already_registered_string' );
					$tmp_format = eme_replace_event_placeholders( $tmp_format, $event );
					$form_html .= $tmp_format . '<br>';
					continue;
				}
			}

			// check all required fields
			if ( get_option( 'eme_rsvp_check_required_fields' ) ) {
				$all_required_fields     = eme_find_required_formfields( $format );
				$missing_required_fields = [];
				$eme_address1_string     = get_option( 'eme_address1_string' );
				$eme_address2_string     = get_option( 'eme_address2_string' );
				foreach ( $all_required_fields as $required_field ) {
					if ( preg_match( '/LASTNAME|EMAIL|SEATS|SPACES|DISCOUNT|PASSWORD/', $required_field ) ) {
						// we already check these separately, and EMAIL regex also catches _HTML5_EMAIL
						// discount can return multiple times, so we rely on the form logic
						continue;
					} elseif ( preg_match( '/PHONE/', $required_field ) ) {
						// PHONE regex also catches HTML5_PHONE
						if ( eme_is_empty_string( $_POST['phone'] ) ) {
							$missing_required_fields[] = __( 'Phone number', 'events-made-easy' );
						}
					} elseif ( preg_match( '/FIRSTNAME/', $required_field ) ) {
						// if wp membership is required, this is a disabled field (not submitted via POST) and info got from WP
						if ( $registration_wp_users_only ) {
							continue;
						} elseif ( eme_is_empty_string( $_POST['firstname'] ) ) {
							$missing_required_fields[] = __( 'First name', 'events-made-easy' );
						}
					} elseif ( preg_match( '/(ADDRESS1|ADDRESS2|CITY|ZIP|POSTAL|BIRTHDATE|BIRTHPLACE)/', $required_field, $matches ) ) {
						$fieldname = strtolower( $matches[1] );
						if ( $fieldname == 'postal' ) {
							$fieldname = 'zip';
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'address1' ) {
							$missing_required_fields[] = $eme_address1_string;
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'address2' ) {
							$missing_required_fields[] = $eme_address2_string;
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'city' ) {
							$missing_required_fields[] = __( 'City', 'events-made-easy' );
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'zip' ) {
							$missing_required_fields[] = __( 'Postal code', 'events-made-easy' );
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'birthdate' ) {
							$missing_required_fields[] = __( 'Date of birth', 'events-made-easy' );
						}
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) && $fieldname == 'birthplace' ) {
							$missing_required_fields[] = __( 'Place of birth', 'events-made-easy' );
						}
					} elseif ( preg_match( '/STATE/', $required_field, $matches ) ) {
						$fieldname = 'state_code';
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) ) {
							$missing_required_fields[] = __( 'State', 'events-made-easy' );
						}
					} elseif ( preg_match( '/COUNTRY/', $required_field, $matches ) ) {
						$fieldname = 'country_code';
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) ) {
							$missing_required_fields[] = __( 'Country', 'events-made-easy' );
						}
					} elseif ( preg_match( '/COMMENT/', $required_field ) ) {
						if ( eme_is_empty_string( $tmp_booking['booking_comment'] ) ) {
							$missing_required_fields[] = __( 'Comment', 'events-made-easy' );
						}
					} elseif ( preg_match( '/MASSMAIL|OPT_OUT|OPT_IN/', $required_field, $matches ) ) {
						$fieldname = 'massmail';
						if ( eme_is_empty_string( $_POST[ $fieldname ] ) ) {
							$missing_required_fields[] = __( 'Massmail', 'events-made-easy' );
						}
					} elseif ( preg_match( '/FIELD(\d+)/', $required_field, $matches ) ) {
						// can be 0 as value for custom fields, so we error out if it is not set, or if it is empty and not 0
						$single_found = 0;
						$multi_found  = 0;
						if ( isset( $_POST['bookings'][ $event_id ] ) ) {
							if ( isset( $_POST['bookings'][ $event_id ][ $required_field ] ) && ( ! eme_is_empty_string( $_POST['bookings'][ $event_id ][ $required_field ] ) ||
								( is_numeric( $_POST['bookings'][ $event_id ][ $required_field ] ) && $_POST['bookings'][ $event_id ][ $required_field ] == 0 ) ) ) {
								$multi_found = 1;
							}
						}
						if ( ! empty( $_FILES[ $required_field ] ) || ( isset( $_POST[ $required_field ] ) && ( ! eme_is_empty_string( $_POST[ $required_field ] ) ||
							( is_numeric( $_POST[ $required_field ] ) && $_POST[ $required_field ] == 0 ) ) ) ) {
							$single_found = 1;
						}
						if ( ! ( $single_found || $multi_found ) ) {
							$field_key = $matches[1];
							$formfield = eme_get_formfield( $field_key );
							if ( ! empty( $formfield ) ) {
								$missing_required_fields[] = $formfield['field_name'];
							}
						}
					} elseif ( eme_is_empty_string( $_POST[ $required_field ] ) ) {
						$missing_required_fields[] = $required_field;
					}
				}
				if ( count( $missing_required_fields ) > 0 ) {
					// if any required field is empty: return an error
					$missing_required_fields_string = join( ', ', $missing_required_fields );
					$form_html                     .= sprintf( __( 'Please make sure all of the following required fields are filled out: %s', 'events-made-easy' ), $missing_required_fields_string );
					continue;
				}
			}

			// check for wrong discount codes
			$dcodes_entered = $tmp_booking['dcodes_entered'];
			$dcodes_used    = $tmp_booking['dcodes_used'];
			if ( ! empty( $dcodes_entered ) ) {
				if ( ! $tmp_booking['discount'] || empty( $dcodes_used ) || count( $dcodes_used ) != count( $dcodes_entered ) ) {
					$form_html .= __( 'You did not enter a valid discount code', 'events-made-easy' );
					continue;
				}
			}
		}

		if ( has_filter( 'eme_eval_booking_filter' ) ) {
			$eval_filter_return = apply_filters( 'eme_eval_booking_filter', $event );
		} else {
			$eval_filter_return = [
				0 => 1,
				1 => '',
			];
		}

		if ( ! eme_is_multi( $min_allowed ) && $bookedSeats < $min_allowed ) {
			$form_html .= __( 'Please enter a correct number of seats to reserve', 'events-made-easy' );
		} elseif ( eme_is_multi( $min_allowed ) && eme_is_multi( $event['event_seats'] ) && $bookedSeats_mp < eme_convert_multi2array( $min_allowed ) ) {
			$form_html .= __( 'Please enter a correct number of seats to reserve', 'events-made-easy' );
		} elseif ( ! eme_is_multi( $max_allowed ) && $max_allowed > 0 && $bookedSeats > $max_allowed ) {
			// we check the max, but only is max_allowed>0, max_allowed=0 means no limit
			$form_html .= __( 'Please enter a correct number of seats to reserve', 'events-made-easy' );
		} elseif ( eme_is_multi( $max_allowed ) && eme_is_multi( $event['event_seats'] ) && eme_get_total( $max_allowed ) > 0 && $bookedSeats_mp > eme_convert_multi2array( $max_allowed ) ) {
			// we check the max, but only is the total max_allowed>0, max_allowed=0 means no limit
			// currently we don't support 0 as being no limit per array element
			$form_html .= __( 'Please enter a correct number of seats to reserve', 'events-made-easy' );
		} elseif ( is_array( $eval_filter_return ) && ! $eval_filter_return[0] ) {
			// the result of own eval rules
			$form_html .= $eval_filter_return[1];
		} else {
			$total_seats = eme_get_total( $event['event_seats'] );
			if ( $total_seats == 0 ) {
				$seats_available = 1;
			} elseif ( eme_is_multi( $event['event_seats'] ) ) {
				$seats_available = eme_are_multiseats_available_for( $event_id, $bookedSeats_mp );
			} else {
				$seats_available = eme_are_seats_available_for( $event_id, $bookedSeats );
			}

			if ( ! ( $seats_available || $allow_overbooking )) {
				$form_html .= __( 'Booking cannot be made: not enough seats available!', 'events-made-easy' );
			} else {
				$booking_info_to_be_made[] = [
					'bookerLastName' =>$bookerLastName,
					'bookerFirstName' => $bookerFirstName,
					'bookerEmail' => $bookerEmail,
					'booker_wp_id' => $booker_wp_id,
					'event' => $event,
					'tmp_booking' => $tmp_booking
				];
				$bookedSeats_for_capacity_check[] = [
					'bookedSeats' => $bookedSeats,
					'start' => $event['event_start'],
					'end' => $event['event_end'],
					'location_id' => $event['location_id']
				];
			}
		}
	} // end foreach ($events as $event)

	// now check location capacity, per event overlapping on the same location
	foreach ($booking_info_to_be_made as $t_info) {
		// the extract call will give us $event, $tmp_booking etc ... in the current symbol space
		extract($t_info);
		if (!empty($event['location_id'])) {
			$location = eme_get_location($event['location_id']);
		} else {
			$location = [];
		}
		if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
			$used_capacity = eme_get_event_location_used_capacity( $event );
			$booked_cap = 0;
			foreach ($bookedSeats_for_capacity_check as $t2_info) {
				if ($event['location_id'] != $t2_info['location_id']) {
					continue;
				}
				if (($event['event_start']>=$t2_info['start'] && $event['event_start']<=$t2_info['end']) ||
					($event['event_end']>=$t2_info['start'] && $event['event_end']<=$t2_info['end'])
				) {
					$booked_cap += $t2_info['bookedSeats'];
				}
			}
			if ($used_capacity + $booked_cap > $location['location_properties']['max_capacity']) {
				$form_html .= __( 'The location does not allow this many people to be present at the same time.', 'events-made-easy' );
				continue;
			}
		}
	}

	// if form_html is not empty, return here already and don't do anything
	if ( ! empty( $form_html ) ) {
		return [
			0 => $form_html,
			1 => $booking_ids,
		];
	}

	// now we have all booking info ready to be made without errors
	foreach ($booking_info_to_be_made as $t_info) {
		// the extract call will give us $event, $tmp_booking etc ... in the current symbol space
		extract($t_info);
		$res       = eme_add_update_person_from_form( 0, $bookerLastName, $bookerFirstName, $bookerEmail, $booker_wp_id, $event['event_properties']['create_wp_user'] );
		$person_id = $res[0];
		// ok, just to be safe: check the person_id of the booker
		if ( $person_id ) {
			$booker = eme_get_person( $person_id );
			// if the user is logged in and the phone field is not empty, update that in the user profile
			if ( ! empty( $booker['wp_id'] ) && ! eme_is_empty_string( $booker['phone'] ) ) {
				eme_update_user_phone( $booker['wp_id'], $booker['phone'] );
			}
			$booking_id = eme_db_insert_booking( $event, $booker, $tmp_booking );
			if ( $booking_id ) {
				// now upload the wanted files. If uploading fails, show that and delete the booking
				$booking         = eme_get_booking( $booking_id );
				$upload_failures = eme_upload_files( $booking_id, 'bookings' );
				if ( $upload_failures ) {
					// uploading failed, so show why, try to refund if paid online and delete the booking
					$form_html .= $upload_failures;
					eme_refund_booking( $booking );
					eme_delete_booking( $booking_id );
				} else {
					$booking_ids[] = $booking_id;
					// make sure to update the discount count if applied
					if ( ! $eme_is_admin_request && ! empty( $booking['discountids'] ) ) {
						$discount_ids = explode( ',', $booking['discountids'] );
						foreach ( $discount_ids as $discount_id ) {
							eme_increase_discount_booking_count( $discount_id, $booking );
						}
					}

					// everything ok? So then we add the user in WP if desired
					// this will only do it if the booker is not logged in and his email doesn't exist in wp yet
					if ( $event['event_properties']['create_wp_user'] > 0 && ! $booker_wp_id && ! email_exists( $booker['email'] ) ) {
						//$wp_userid=eme_create_wp_user($booker);
						eme_create_wp_user( $booker );
					}
					// now everything is done, so execute the hook if present
					if ( has_action( 'eme_insert_rsvp_action' ) ) {
						do_action( 'eme_insert_rsvp_action', $booking );
					}
				}
			}
		} else {
			$form_html .= $res[1];
		}
	}

	if ( ! empty( $booking_ids ) ) {
		// the payment needs to be created before the mail is sent or placeholders replaced, otherwise you can't send a link to the payment ...
		$booking_ids_done = join( ',', $booking_ids );
		$payment_id       = eme_create_booking_payment( $booking_ids_done );

		if ( $simple ) {
			$ok_format = eme_nl2br_save_html( get_option( 'eme_registration_recorded_ok_html' ) );
			$lang      = eme_detect_lang();
			// in simple form: we send all mails (even user confirmation required) for each booking
			foreach ( $booking_ids as $booking_id ) {
				$email_success = true;
				$booking       = eme_get_booking( $booking_id );
				if ( $send_mail && ! eme_is_empty_string( $bookerEmail ) ) {
					// leave the action empty, then regular approval flow is followed (even in admin)
					$action        = '';
					$email_success = eme_email_booking_action( $booking, $action );
				}
				$event      = eme_get_event( $booking['event_id'] );
				$form_html .= eme_replace_booking_placeholders( $ok_format, $event, $booking, 0, 'html', $lang );
				$form_html .= '<br>';
				if ( ! $email_success ) {
					$form_html .= __( 'Warning: there was a problem sending you the booking mail, please contact the site administrator to sort this out.', 'events-made-easy' );
				}
			}
		} else {
			// send the mail and show the result based on the first booking done in the series
			// we first send the mail as the function eme_replace_booking_placeholders can use #_BOOKINGPDF_URL which can be a PDF generated by eme_email_booking_action if the template is identical for #_BOOKINGPDF_URL and the "ticket pdf template" in the event rsvp settings
			$booking       = eme_get_booking( $booking_ids[0] );
			$email_success = true;
			if ( $send_mail && ! eme_is_empty_string( $bookerEmail ) ) {
				// leave the action empty, then regular approval flow is followed (even in admin)
				$action        = '';
				$email_success = eme_email_booking_action( $booking, $action, $is_multibooking );
			}
			$event = eme_get_event( $booking['event_id'] );

			if ( ! eme_is_empty_string( $event['event_registration_recorded_ok_html'] ) ) {
				$ok_format = eme_nl2br_save_html( $event['event_registration_recorded_ok_html'] );
			} elseif ( $event['event_properties']['event_registration_recorded_ok_html_tpl'] > 0 ) {
				$ok_format = eme_get_template_format( $event['event_properties']['event_registration_recorded_ok_html_tpl'] );
			} else {
				$ok_format = eme_nl2br_save_html( get_option( 'eme_registration_recorded_ok_html' ) );
			}

			// we'll use the current active language to display the form thank-you
			$lang       = eme_detect_lang();
			$form_html .= eme_replace_booking_placeholders( $ok_format, $event, $booking, $is_multibooking, 'html', $lang );
			if ( ! $email_success ) {
				$form_html .= '<br>' . __( 'Warning: there was a problem sending you the booking mail, please contact the site administrator to sort this out.', 'events-made-easy' );
			}
		}
	} else {
		$payment_id = 0;
	}

	$res = [
		0 => $form_html,
		1 => $payment_id,
	];
	return $res;
}

// the eme_book_seats can also be called from the admin backend, that's why for certain things, we check using is_admin where we are
function eme_book_seats( $event, $send_mail ) {
	if ( ! eme_is_empty_string( $event['event_registration_form_format'] ) ) {
		$format = eme_nl2br_save_html( $event['event_registration_form_format'] );
	} elseif ( $event['event_properties']['event_registration_form_format_tpl'] > 0 ) {
		$format = eme_get_template_format( $event['event_properties']['event_registration_form_format_tpl'] );
	} else {
		$format = eme_nl2br_save_html( get_option( 'eme_registration_form_format' ) );
	}
	$events          = [ 0 => $event ];
	$is_multibooking = 0;
	return eme_multibook_seats( $events, $send_mail, $format, $is_multibooking );
}

function eme_get_booking( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT * FROM $bookings_table WHERE booking_id = %d", $booking_id );
	$booking        = $wpdb->get_row( $sql, ARRAY_A );
	if ( $booking !== false ) {
		if ( eme_is_serialized( $booking['dcodes_used'] ) ) {
			$booking['dcodes_used'] = eme_unserialize( $booking['dcodes_used'] );
		} else {
			$booking['dcodes_used'] = [];
		}
		if ( eme_is_serialized( $booking['dcodes_entered'] ) ) {
			$booking['dcodes_entered'] = eme_unserialize( $booking['dcodes_entered'] );
		} else {
			$booking['dcodes_entered'] = [];
		}
	}
	return $booking;
}

function eme_get_event_price( $event_id ) {
	global $wpdb;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$sql          = $wpdb->prepare( "SELECT price FROM $events_table WHERE event_id = %d", $event_id );
	$result       = $wpdb->get_var( $sql );
	return $result;
}

function eme_get_bookings_by_wp_id( $wp_id, $scope, $rsvp_status = 0, $paid_status = 0 ) {
	global $wpdb;
	$events_table    = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$bookings_table  = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$extra_condition = '';
	if ( $rsvp_status ) {
		$extra_condition .= " bookings.status=".intval($rsvp_status);
	} else {
		$extra_condition .= ' bookings.status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$extra_condition .= ' AND booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$extra_condition .= ' AND booking_paid=1';
	}

	if ( ! empty( $extra_condition ) ) {
		$extra_condition = "$extra_condition AND ";
	}

	if ( $scope == 1 || $scope == 'future' ) {
		$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
		$now          = $eme_date_obj->getDateTime();
		$sql          = $wpdb->prepare( "SELECT bookings.* FROM $bookings_table AS bookings,$events_table AS events,$people_table AS people WHERE $extra_condition bookings.person_id=people.person_id AND people.wp_id = %d AND bookings.event_id=events.event_id AND events.event_start>%s ORDER BY events.event_start ASC", $wp_id, $now );
	} elseif ( $scope == 'past' ) {
		$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
		$now          = $eme_date_obj->getDateTime();
		$sql          = $wpdb->prepare( "SELECT bookings.* FROM $bookings_table AS bookings,$events_table AS events,$people_table AS people WHERE $extra_condition bookings.person_id=people.person_id AND people.wp_id = %d AND bookings.event_id=events.event_id AND events.event_start<=%s ORDER BY events.event_start ASC", $wp_id, $now );
	} elseif ( $scope == 0 || $scope == 'all' ) {
		$sql = $wpdb->prepare( "SELECT * FROM $bookings_table AS bookings,$events_table AS events,$people_table AS people WHERE $extra_condition bookings.person_id=people.person_id AND people.wp_id = %d AND bookings.event_id=events.event_id ORDER BY events.event_start ASC", $wp_id );
	}
	$bookings = $wpdb->get_results( $sql, ARRAY_A );
	if ( ! empty( $bookings ) ) {
		foreach ( $bookings as $key => $booking ) {
			if ( eme_is_serialized( $booking['dcodes_used'] ) ) {
				$booking['dcodes_used'] = eme_unserialize( $booking['dcodes_used'] );
			} else {
				$booking['dcodes_used'] = [];
			}
			if ( eme_is_serialized( $booking['dcodes_entered'] ) ) {
				$booking['dcodes_entered'] = eme_unserialize( $booking['dcodes_entered'] );
			} else {
				$booking['dcodes_entered'] = [];
			}
			$bookings[ $key ] = $booking;
		}
	}
	return $bookings;
}

function eme_get_bookings_by_person_id( $person_id, $scope, $rsvp_status = 0, $paid_status = 0 ) {
	global $wpdb;
	$events_table    = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$bookings_table  = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$extra_condition = '';
	if ( $rsvp_status ) {
		$extra_condition .= " bookings.status=".intval($rsvp_status);
	} else {
		$extra_condition .= ' bookings.status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$extra_condition .= ' AND booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$extra_condition .= ' AND booking_paid=1';
	}

	if ( ! empty( $extra_condition ) ) {
		$extra_condition = "$extra_condition AND ";
	}

	if ( $scope == 1 || $scope == 'future' ) {
		$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
		$now          = $eme_date_obj->getDateTime();
		$sql          = $wpdb->prepare( "SELECT bookings.* FROM $bookings_table AS bookings,$events_table AS events WHERE $extra_condition bookings.person_id= %d AND bookings.event_id=events.event_id AND events.event_start>%s ORDER BY events.event_start ASC", $person_id, $now );
	} elseif ( $scope == 'past' ) {
		$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
		$now          = $eme_date_obj->getDateTime();
		$sql          = $wpdb->prepare( "SELECT bookings.* FROM $bookings_table AS bookings,$events_table AS events WHERE $extra_condition bookings.person_id= %d AND bookings.event_id=events.event_id AND events.event_start<=%s ORDER BY events.event_start ASC", $person_id, $now );
	} elseif ( $scope == 0 || $scope == 'all' ) {
		$sql = $wpdb->prepare( "SELECT * FROM $bookings_table AS bookings,$events_table AS events WHERE $extra_condition bookings.person_id= %d AND bookings.event_id=events.event_id ORDER BY events.event_start ASC", $person_id );
	}
	$bookings = $wpdb->get_results( $sql, ARRAY_A );
	if ( ! empty( $bookings ) ) {
		foreach ( $bookings as $key => $booking ) {
			if ( eme_is_serialized( $booking['dcodes_used'] ) ) {
				$booking['dcodes_used'] = eme_unserialize( $booking['dcodes_used'] );
			} else {
				$booking['dcodes_used'] = [];
			}
			if ( eme_is_serialized( $booking['dcodes_entered'] ) ) {
				$booking['dcodes_entered'] = eme_unserialize( $booking['dcodes_entered'] );
			} else {
				$booking['dcodes_entered'] = [];
			}
			$bookings[ $key ] = $booking;
		}
	}
	return $bookings;
}

function eme_get_booking_by_person_event_id( $person_id, $event_id ) {
	return eme_get_booking_ids_by_person_event_id( $person_id, $event_id );
}
function eme_get_booking_ids_by_person_event_id( $person_id, $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT booking_id FROM $bookings_table WHERE status IN (%d,%d,%d) AND person_id = %d AND event_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $person_id, $event_id );
	return $wpdb->get_col( $sql );
}

function eme_get_booking_ids_by_email_event_id( $email, $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql            = $wpdb->prepare( "SELECT bookings.booking_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND bookings.event_id = %d AND people.email = %s", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id, $email );
	return $wpdb->get_col( $sql );
}

function eme_get_booking_ids_by_wp_event_id( $wp_id, $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql            = $wpdb->prepare( "SELECT bookings.booking_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND bookings.event_id = %d AND people.wp_id=%d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id, $wp_id );
	return $wpdb->get_col( $sql );
}

function eme_get_pending_booking_ids_by_bookingids( $booking_ids ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($booking_ids) ) {
		return $wpdb->get_col( $wpdb->prepare( "SELECT booking_id FROM $bookings_table WHERE booking_id IN ($booking_ids) AND status IN (%d,%d)", EME_RSVP_STATUS_PENDING,EME_RSVP_STATUS_USERPENDING ) );
	} else {
		return 0;
	}	
}
function eme_get_unpaid_booking_ids_by_bookingids( $booking_ids ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($booking_ids) ) {
		return $wpdb->get_col( "SELECT booking_id FROM $bookings_table WHERE booking_id IN ( $booking_ids ) AND booking_paid=0" );
	} else {
		return 0;
	}	
}

// API function: get all bookings for a certain email
function eme_get_booking_ids_by_email( $email ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql            = $wpdb->prepare( "SELECT bookings.booking_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND people.email = %s", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $email );
	return $wpdb->get_col( $sql );
}

function eme_get_booked_seats_by_wp_event_id( $wp_id, $event_id ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_booked_multiseats_by_wp_event_id( $wp_id, $event_id ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql            = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) AS booked_seats FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND people.wp_id = %d AND bookings.event_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $wp_id, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_booked_multiseats_by_wp_event_id( $wp_id, $event_id ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table    = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql              = $wpdb->prepare( "SELECT booking_seats_mp FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND people.wp_id = %d AND bookings.event_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $wp_id, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_get_booked_seats_by_person_event_id( $person_id, $event_id ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_booked_multiseats_by_person_event_id( $person_id, $event_id ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) AS booked_seats FROM $bookings_table WHERE status IN (%d,%d,%d) AND person_id = %d AND event_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $person_id, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_booked_multiseats_by_person_event_id( $person_id, $event_id ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql              = $wpdb->prepare( "SELECT booking_seats_mp FROM $bookings_table WHERE status IN (%d,%d,%d) AND person_id = %d AND event_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $person_id, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_get_event_id_by_booking_id( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT DISTINCT event_id FROM $bookings_table WHERE booking_id = %d", $booking_id );
	$event_id       = $wpdb->get_var( $sql );
	return $event_id;
}

function eme_get_event_by_booking_id( $booking_id ) {
	$event_id = eme_get_event_id_by_booking_id( $booking_id );
	$event    = [];
	if ( $event_id ) {
		$event = eme_get_event( $event_id );
	}
	return $event;
}

function eme_get_event_ids_by_booker_id( $person_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT DISTINCT event_id FROM $bookings_table WHERE status IN (%d,%d,%d) AND person_id = %d", EME_RSVP_STATUS_APPROVED, EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $person_id );
	return $wpdb->get_col( $sql );
}

function eme_record_booking( $event, $booker, $booking ) {
	return eme_db_insert_booking( $event, $booker, $booking );
}
function eme_db_insert_booking( $event, $booker, $booking ) {
	global $wpdb, $plugin_page;
	$eme_is_admin_request = eme_is_admin_request();
	$bookings_table       = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$booking['person_id'] = intval( $booker['person_id'] );

	if ( ! $eme_is_admin_request && $booking['waitinglist'] ) {
		// if we're adding a booking via the frontend and the waitinglist is on, approval needed
		$booking['status'] = EME_RSVP_STATUS_PENDING;
	} elseif ( ! $eme_is_admin_request && $event['event_properties']['require_user_confirmation'] ) {
		$booking['status'] = EME_RSVP_STATUS_USERPENDING;
	} elseif ( ! $eme_is_admin_request && $event['registration_requires_approval'] ) {
		// if we're adding a booking via the frontend, check for approval needed
		$price = eme_get_total_booking_price( $booking );
		if ( $price == 0 && $event['event_properties']['auto_approve'] ) {
			// if approval is needed and auto-approve after payment is on and the price is 0 (due to discount or so), then mark approved
			$booking['status'] = EME_RSVP_STATUS_APPROVED;
		} else {
			$booking['status'] = EME_RSVP_STATUS_PENDING;
		}
	} elseif ( $eme_is_admin_request && $event['registration_requires_approval'] && $plugin_page == 'eme-registration-approval' ) {
		// if we're adding a booking via the backend, check the page we came from to check for approval too
		$booking['status'] = EME_RSVP_STATUS_PENDING;
	} else {
		$booking['status'] = EME_RSVP_STATUS_APPROVED;
	}

	// eme_serialize if needed
	$booking['dcodes_entered'] = eme_serialize( $booking['dcodes_entered'] );
	$booking['dcodes_used']    = eme_serialize( $booking['dcodes_used'] );

	$booking['creation_date'] = current_time( 'mysql', false );
	$booking['modif_date'] = $booking['creation_date'];

	if ( $wpdb->insert( $bookings_table, $booking ) ) {
		$booking_id            = $wpdb->insert_id;
		$booking['booking_id'] = $booking_id;
		eme_add_persongroups( $booking['person_id'], $event['event_properties']['rsvp_addpersontogroup'] );
		eme_store_booking_answers( $booking );
		return $booking_id;
	} else {
		return false;
	}
}

// API function so people easily get the booking answers from a post, primarily used for discounts
// we use $booking as a var because in discount functions this is also the main var
function eme_get_booking_post_answers( $booking, $include_dynamicdata = 1 ) {
	$answers     = [];
	$fields_seen = [];
	$event_id    = $booking['event_id'];

	// first do the booking answers per event if any
	if ( isset( $_POST['bookings'][ $event_id ] ) ) {
		foreach ( $_POST['bookings'][ $event_id ] as $key => $value ) {
			if ( preg_match( '/^FIELD(\d+)$/', $key, $matches ) ) {
				$field_id      = intval( $matches[1] );
				$fields_seen[] = $field_id;
				$formfield     = eme_get_formfield( $field_id );
				if ( ! empty( $formfield ) ) {
					// for multivalue fields like checkbox, the value is in fact an array
					// to store it, we make it a simple "multi" string using eme_convert_array2multi, so later on when we need to parse the values
					// (when editing a booking), we can re-convert it to an array with eme_convert_multi2array (see eme_formfields.php)
					if ( is_array( $value ) ) {
						$value = eme_convert_array2multi( $value );
					}
					if ( $formfield['field_type'] == 'textarea' ) {
						$value = eme_sanitize_textarea( $value );
					} elseif ( $formfield['field_type'] == 'time_js' ) {
						$value = eme_convert_localized_time( $formfield['field_attributes'], eme_sanitize_request( $value ) );
					} else {
						$value = eme_sanitize_request( $value );
					}
					if ($formfield['field_purpose'] == 'people') {
						$type = 'person';
						$related_id = isset($booking['person_id'])?$booking['person_id']:0;
					} else {
						$type = 'booking';
						$related_id = isset($booking['booking_id'])?$booking['booking_id']:0;
					}
					// some extra fields are added, so people can use these to check things: field_name, field_purpose, extra_charge (also used in code), grouping_id and occurence_id
					$answer    = [
						'field_name'    => $formfield['field_name'],
						'field_id'      => $field_id,
						'field_purpose' => $formfield['field_purpose'],
						'extra_charge'  => $formfield['extra_charge'],
						'answer'        => $value,
						'grouping_id '  => 0,
						'occurence_id'  => 0,
						'eme_grouping'  => 0,
						'occurence'     => 0,
						'type'          => $type,
						'related_id'    => $related_id,
					];
					$answers[] = $answer;
				}
			}
		}
	}

	// do the dynamic answers if any
	// this is a little tricky: dynamic answers are in fact grouped by a seat condition when filled out, and there can be more than 1 of the same group
	// so we need a little more looping here ...
	if ( $include_dynamicdata && isset( $_POST['dynamic_bookings'][ $event_id ] ) ) {
		foreach ( $_POST['dynamic_bookings'][ $event_id ] as $group_id => $group_value ) {
			foreach ( $group_value as $occurence_id => $occurence_value ) {
				foreach ( $occurence_value as $key => $value ) {
					if ( preg_match( '/^FIELD(\d+)$/', $key, $matches ) ) {
						$field_id = intval( $matches[1] );
						// we don't store the field ids seen here, reason: you can and are allowed to use the same fields in the main form and in dynamic fields too
						// $fields_seen[]=$field_id;
						$formfield = eme_get_formfield( $field_id );
						if ( ! empty( $formfield ) ) {
							// for multivalue fields like checkbox, the value is in fact an array
							// to store it, we make it a simple "multi" string using eme_convert_array2multi, so later on when we need to parse the values
							// (when editing a booking), we can re-convert it to an array with eme_convert_multi2array (see eme_formfields.php)
							if ( is_array( $value ) ) {
								$value = eme_convert_array2multi( $value );
							}
							if ( $formfield['field_type'] == 'textarea' ) {
								$value = eme_sanitize_textarea( $value );
							} elseif ( $formfield['field_type'] == 'time_js' ) {
								$value = eme_convert_localized_time( $formfield['field_attributes'], eme_sanitize_request( $value ) );
							} else {
								$value = eme_sanitize_request( $value );
							}
							if ($formfield['field_purpose'] == 'people') {
								$type = 'person';
								$related_id = isset($booking['person_id'])?$booking['person_id']:0;
							} else {
								$type = 'booking';
								$related_id = isset($booking['booking_id'])?$booking['booking_id']:0;
							}
							// some extra fields are added, so people can use these to check things: field_name, field_purpose, extra_charge (also used in code), grouping_id and occurence_id
							$answer    = [
								'field_name'    => $formfield['field_name'],
								'field_id'      => $field_id,
								'field_purpose' => $formfield['field_purpose'],
								'extra_charge'  => $formfield['extra_charge'],
								'answer'        => $value,
								'grouping_id'   => intval($group_id),
								'occurence_id'  => intval($occurence_id),
								'eme_grouping'  => intval($group_id),
								'occurence'     => intval($occurence_id),
								'type'          => $type,
								'related_id'    => $related_id,
							];
							$answers[] = $answer;
						}
					}
				}
			}
		}
	}

	foreach ( $_POST as $key => $value ) {
		if ( preg_match( '/^FIELD(\d+)$/', $key, $matches ) ) {
			$field_id = intval( $matches[1] );
			// the value was already stored for a multibooking, so don't do it again
			if ( in_array( $field_id, $fields_seen ) ) {
				continue;
			}
			$formfield = eme_get_formfield( $field_id );
			if ( ! empty( $formfield ) ) {
				// for multivalue fields like checkbox, the value is in fact an array
				// to store it, we make it a simple "multi" string using eme_convert_array2multi, so later on when we need to parse the values
				// (when editing a booking), we can re-convert it to an array with eme_convert_multi2array (see eme_formfields.php)
				if ( is_array( $value ) ) {
					$value = eme_convert_array2multi( $value );
				}
				if ( $formfield['field_type'] == 'textarea' ) {
					$value = eme_sanitize_textarea( $value );
				} elseif ( $formfield['field_type'] == 'time_js' ) {
					$value = eme_convert_localized_time( $formfield['field_attributes'], eme_sanitize_request( $value ) );
				} else {
					$value = eme_sanitize_request( $value );
				}
				if ($formfield['field_purpose'] == 'people') {
					$type = 'person';
					$related_id = isset($booking['person_id'])?$booking['person_id']:0;
				} else {
					$type = 'booking';
					$related_id = isset($booking['booking_id'])?$booking['booking_id']:0;
				}
				// some extra fields are added, so people can use these to check things: field_name, field_purpose, extra_charge (also used in code), grouping_id and occurence_id
				$answer    = [
					'field_name'    => $formfield['field_name'],
					'field_id'      => $field_id,
					'field_purpose' => $formfield['field_purpose'],
					'extra_charge'  => $formfield['extra_charge'],
					'answer'        => $value,
					'grouping_id '  => 0,
					'occurence_id'  => 0,
					'eme_grouping'  => 0,
					'occurence'     => 0,
					'type'          => $type,
					'related_id'    => $related_id,
				];
				$answers[] = $answer;
			}
		}
	}
	return $answers;
}

function eme_booking_answers( $booking, $do_update = 1 ) {
	return eme_store_booking_answers( $booking, $do_update );
}
function eme_store_booking_answers( $booking, $do_update = 1 ) {
	global $wpdb;
	if ( empty( $booking['booking_id'] ) ) {
		$do_update = 0;
	}

	$extra_charge = 0;
	$person_id    = $booking['person_id'];
	$all_answers  = [];
	if ( $do_update ) {
		$booking_id = $booking['booking_id'];
		if ( $booking_id > 0 ) {
			$booking_answers = eme_get_booking_answers( $booking_id );
			$person_answers  = eme_get_person_answers( $person_id );
			wp_cache_delete( "eme_person_answers $person_id" );
			$all_answers = array_merge( $booking_answers, $person_answers );

		}
	} else {
		$booking_id = 0;
	}
	$booking['booking_id']=$booking_id;

	$answer_ids_seen = [];
	$found_answers   = eme_get_booking_post_answers( $booking );
	foreach ( $found_answers as $answer ) {
		if ( $answer['extra_charge'] && is_numeric( $answer['answer'] ) ) {
			$extra_charge += $answer['answer'];
		}
		if ( $do_update ) {
			$answer_id = eme_get_answerid( $all_answers, $answer['related_id'], $answer['type'], $answer['field_id'], $answer['eme_grouping'], $answer['occurence'] );
			if ( $answer_id ) {
				eme_update_answer( $answer_id, $answer['answer'] );
				$answer_ids_seen[] = $answer_id;
			} else {
				$answer_id = eme_insert_answer( $answer['type'], $answer['related_id'], $answer['field_id'], $answer['answer'], $answer['eme_grouping'], $answer['occurence'] );
			}
		}
	}

	if ( $do_update && $booking_id > 0 ) {
		// put the extra charge found in the booking made
		$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
		$sql            = $wpdb->prepare( "UPDATE $bookings_table SET extra_charge = %s WHERE booking_id = %d", $extra_charge, $booking_id );
		$wpdb->query( $sql );

		// delete old answer_ids
		foreach ( $all_answers as $answer ) {
			if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && $answer['type'] == 'booking' && $answer['related_id'] == $booking_id ) {
				eme_delete_answer( $answer['answer_id'] );
			}
		}
	}
	return $extra_charge;
}

function eme_get_booking_answers( $booking_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='booking'", $booking_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_nodyndata_booking_answers( $booking_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND eme_grouping=0 AND type='booking'", $booking_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_dyndata_booking_answers( $booking_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND eme_grouping>0 AND type='booking' ORDER BY eme_grouping,occurence,field_id", $booking_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}
function eme_get_dyndata_booking_answer( $booking_id, $grouping = 0, $occurence = 0 ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='booking' AND eme_grouping=%d AND occurence=%d", $booking_id, $grouping, $occurence );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_delete_booking_answers( $booking_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id=%d AND type='booking'", $booking_id );
	$wpdb->query( $sql );
}

function eme_delete_all_bookings_for_event_id( $event_id ) {
	global $wpdb;
	$answers_table  = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "DELETE FROM $answers_table WHERE type='booking' AND related_id IN (SELECT booking_id from $bookings_table WHERE event_id = %d)", $event_id );
	$wpdb->query( $sql );
	$sql = $wpdb->prepare( "DELETE FROM $bookings_table WHERE event_id = %d", $event_id );
	$wpdb->query( $sql );
	return 1;
}

function eme_trash_person_bookings_future_events( $person_ids ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$events_table     = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today        = $eme_date_obj_now->getDateTime();
	if (eme_is_list_of_int($person_ids) ) {
		$sql = $wpdb->prepare( "UPDATE $bookings_table SET status = %d WHERE person_id IN ($person_ids) AND event_id IN (SELECT event_id from $events_table WHERE event_end >= %s)", EME_RSVP_STATUS_TRASH, $today );
		$wpdb->query( $sql );
	}
}

function eme_delete_person_bookings( $person_ids ) {
	global $wpdb;
	$answers_table  = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($person_ids) ) {
		$wpdb->query( "DELETE FROM $answers_table WHERE type='booking' AND related_id IN (SELECT booking_id from $bookings_table WHERE person_id IN ($person_ids))");
		$wpdb->query( "DELETE FROM $bookings_table WHERE person_id IN ($person_ids)");
	}
}

function eme_transfer_person_bookings( $person_ids, $to_person_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($person_ids) ) {
		$sql = $wpdb->prepare( "UPDATE $bookings_table SET person_id = %d WHERE person_id IN ($person_ids)", $to_person_id );
		return $wpdb->query( $sql );
	} else {
		return false;
	}
}

function eme_trash_bookings_for_event_ids( $ids ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($ids) ) {
		$sql = $wpdb->prepare("UPDATE $bookings_table SET status = %d WHERE event_id IN ($ids)", EME_RSVP_STATUS_TRASH);
		$wpdb->query( $sql );
	}
}

function eme_trash_booking( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if ( has_action( 'eme_trash_rsvp_action' ) ) {
		$booking = eme_get_booking( $booking_id );
		do_action( 'eme_trash_rsvp_action', $booking );
	}
	$where               = [];
	$fields              = [];
	$where['booking_id'] = $booking_id;
	$fields['status']    = EME_RSVP_STATUS_TRASH;
	$res                 = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_delete_booking( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$booking        = eme_get_booking( $booking_id );
	if ( empty( $booking ) ) {
		return false;
	}
	if ( has_action( 'eme_delete_rsvp_action' ) ) {
		do_action( 'eme_delete_rsvp_action', $booking );
	}
	$sql = $wpdb->prepare( "DELETE FROM $bookings_table WHERE booking_id = %d", $booking_id );
	$res = $wpdb->query( $sql );
	// delete optional attachments
	eme_delete_uploaded_files( $booking_id, 'bookings' );
	eme_delete_booking_answers( $booking_id );

	// now check the payment linked to that booking
	// and if no other bookings are linked to the orignal payment: delete it too
	$booking_ids = eme_get_payment_booking_ids( $booking['payment_id'] );
	if ( empty( $booking_ids ) ) {
		eme_delete_payment( $booking['payment_id'] );
	}
	return $res;
}

function eme_partial_payment_booking( $booking, $amount ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where               = [];
	$fields              = [];
	$where['booking_id'] = $booking['booking_id'];
	$price               = eme_get_total_booking_price( $booking );
	if ( empty( $booking['received'] ) ) {
		$fields['received'] = $amount;
	} else {
		$received           = eme_convert_multi2array( $booking['received'] );
		$received[]         = $amount;
		$fields['received'] = eme_convert_array2multi( $received );
	}
	$total_received = eme_get_total( $fields['received'] );
	if ( $total_received >= $price ) {
		$fields['booking_paid'] = 1;
		$fields['remaining']    = 0;
	} else {
		$fields['remaining'] = $price - $total_received;
	}
	$fields['payment_date'] = current_time( 'mysql', false );
	$res                    = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_booking_paid( $booking, $pg = '', $pg_pid = '' ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                  = [];
	$fields                 = [];
	$where['booking_id']    = $booking['booking_id'];
	$fields['booking_paid'] = 1;
	$fields['pg']           = $pg;
	$fields['pg_pid']       = $pg_pid;
	$fields['payment_date'] = current_time( 'mysql', false );
	if ( empty( $booking['received'] ) ) {
		$price              = eme_get_total_booking_price( $booking );
		$fields['received'] = $price;
	}
	$fields['remaining'] = 0;
	$res                 = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_booking_unpaid( $booking ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                  = [];
	$fields                 = [];
	$where['booking_id']    = $booking['booking_id'];
	$fields['booking_paid'] = 0;
	$price                  = eme_get_total_booking_price( $booking );
	$fields['received']     = '';
	$fields['remaining']    = $price;
	$fields['pg']           = '';
	$fields['pg_pid']       = '';
	$fields['payment_date'] = '0000-00-00 00:00:00';
	$res                    = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_booking_paid_approved( $booking, $pg = '', $pg_pid = '' ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                  = [];
	$fields                 = [];
	$where['booking_id']    = $booking['booking_id'];
	$where['waitinglist']   = 0;
	$fields['booking_paid'] = 1;
	$fields['pg']           = $pg;
	$fields['pg_pid']       = $pg_pid;
	$fields['status']       = EME_RSVP_STATUS_APPROVED;
	$fields['payment_date'] = current_time( 'mysql', false );
	if ( empty( $booking['received'] ) ) {
		$price              = eme_get_total_booking_price( $booking );
		$fields['received'] = $price;
	}
	$fields['remaining'] = 0;
	$res                 = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_booking_pending( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                 = [];
	$where['booking_id']   = $booking_id;
	$fields['status']      = EME_RSVP_STATUS_PENDING;
	$fields['waitinglist'] = 0;
	$res                   = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_set_booking_reminder( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where               = [];
	$where['booking_id'] = $booking_id;
	$fields['reminder']  = current_time( 'timestamp' );
	$res                 = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_remove_from_waitinglist( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                 = [];
	$fields                = [];
	$where['booking_id']   = $booking_id;
	$fields['waitinglist'] = 0;
	$res                   = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_move_on_waitinglist( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                 = [];
	$fields                = [];
	$where['booking_id']   = $booking_id;
	$fields['waitinglist'] = 1;
	$fields['status']      = EME_RSVP_STATUS_PENDING;
	$res                   = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_booking_userconfirm( $booking_ids ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if (eme_is_list_of_int($booking_ids) ) {
		$sql = $wpdb->prepare( "UPDATE $bookings_table SET status=%d WHERE booking_id IN ($booking_ids)", EME_RSVP_STATUS_USERPENDING );
		$wpdb->query( $sql );
	}
}

function eme_userconfirm_bookings( $booking_ids_arr, $price, $is_multibooking = 0 ) {
	$action = '';
	foreach ( $booking_ids_arr as $booking_id ) {
		$event = eme_get_event_by_booking_id( $booking_id );
		if ( $price == 0 && (!$event['registration_requires_approval'] || $event['event_properties']['auto_approve'] ) ) {
			$booking = eme_get_booking( $booking_id );
			$res = eme_mark_booking_paid_approved( $booking );
			if ( $res ) {
				$action = 'approveBooking';
			}
			eme_manage_waitinglist( $event );
		} elseif ( ! $event['registration_requires_approval'] ) {
			$res = eme_approve_booking( $booking_id );
			if ( $res ) {
				$action = 'approveBooking';
			}
			eme_manage_waitinglist( $event );
		} else {
			$res = eme_mark_booking_pending( $booking_id );
			if ( $res ) {
			        // action should just be some value (but not "pendingBooking" since that is only called from the backend and then no contact person email will get sent)
                                $action = 'userconfirmedBooking';
			}
		}
	}

	// now send the mail for the first booking only if appropriate
	if ( ! empty( $action ) ) {
		$booking = eme_get_booking( $booking_ids_arr[0] );
		eme_email_booking_action( $booking, $action, $is_multibooking );
	}
}

function eme_approve_booking( $booking_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$where                 = [];
	$fields                = [];
	$where['booking_id']   = $booking_id;
	$fields['waitinglist'] = 0;
	$fields['status']      = EME_RSVP_STATUS_APPROVED;
	$res                   = $wpdb->update( $bookings_table, $fields, $where );
	if ( $res === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_db_update_booking( $line ) {
	global $wpdb;
	$bookings_table      = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$where               = [];
	$where['booking_id'] = $line['booking_id'];
	$line['modif_date']  = current_time( 'mysql', false );
	// make sure we don't disturb the discount fields for now, that calc is done later
	unset( $line['dcodes_used'] );
	unset( $line['dcodes_entered'] );

	if ( $wpdb->update( $bookings_table, $line, $where ) === false ) {
		$res = false;
	} else {
		$res = true;
	}

	if ( $res ) {
		$booking = eme_get_booking( $line['booking_id'] );
		//eme_delete_booking_answers($booking_id);
		eme_store_booking_answers( $booking );
		eme_update_booking_discount( $booking );
		// now that everything is (or should be) correctly entered in the db, execute possible actions for the booking
		if ( has_action( 'eme_update_rsvp_action' ) ) {
			do_action( 'eme_update_rsvp_action', $booking );
		}
	}
	return $res;
}

function eme_get_available_seats( $event_id, $exclude_waiting_list = 0, $exclude_pending_booking_id = 0 ) {
	$event = eme_get_event( $event_id );
	if ( empty( $event ) ) {
		return 0;
	}
	if (!empty($event['location_id'])) {
		$location = eme_get_location($event['location_id']);
	} else {
		$location = [];
	}
	if ( eme_is_multi( $event['event_seats'] ) ) {
		$available_seats = array_sum( eme_get_available_multiseats( $event_id, $exclude_waiting_list, $exclude_pending_booking_id ) );
	} else {
		if ( $event['event_properties']['ignore_pending'] == 1 ) {
			$available_seats = $event['event_seats'] - eme_get_approved_seats( $event_id );
			if ( eme_event_has_pgs_configured( $event ) ) {
				$available_seats -= eme_get_young_pending_seats( $event_id, $exclude_pending_booking_id );
			}
		} else {
			$available_seats = $event['event_seats'] - eme_get_booked_seats( $event_id, $exclude_waiting_list );
		}
		if ( $exclude_waiting_list ) {
			$available_seats -= $event['event_properties']['waitinglist_seats'];
		}
	}

	if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
		$used_capacity = eme_get_event_location_used_capacity( $event );
		$free_location_capacity = $location['location_properties']['max_capacity'] - $used_capacity;
		if ($free_location_capacity < 0) {
			$free_location_capacity=0;
		}
		if ($available_seats > $free_location_capacity) {
			$available_seats = $free_location_capacity;
		}
	}

	// the number of seats left can be <0 if more than one booking happened at the same time and people fill in things slowly
	if ( $available_seats < 0 ) {
		$available_seats = 0;
	}
	return $available_seats;
}

function eme_get_available_multiseats( $event_id, $exclude_waiting_list = 0, $exclude_pending_booking_id = 0 ) {
	$event = eme_get_event( $event_id );
	if ( empty( $event ) ) {
		return 0;
	}
	if (!empty($event['location_id'])) {
		$location = eme_get_location($event['location_id']);
	} else {
		$location = [];
	}
	$multiseats      = eme_convert_multi2array( $event['event_seats'] );
	$available_seats = [];
	if ( $event['event_properties']['ignore_pending'] == 1 ) {
		$used_multiseats = eme_get_approved_multiseats( $event_id );
		if ( eme_event_has_pgs_configured( $event ) ) {
			$young_pending_multiseats = eme_get_young_pending_multiseats( $event_id, $exclude_pending_booking_id );
		} else {
			$young_pending_multiseats = [];
		}
	} else {
		$used_multiseats          = eme_get_booked_multiseats( $event_id, $exclude_waiting_list );
		$young_pending_multiseats = [];
	}
	foreach ( $multiseats as $key => $value ) {
		if ( isset( $used_multiseats[ $key ] ) ) {
			$available_seats[ $key ] = $value - $used_multiseats[ $key ];
		} else {
			$available_seats[ $key ] = $value;
		}
		if ( isset( $young_pending_multiseats[ $key ] ) ) {
			$available_seats[ $key ] -= $young_pending_multiseats[ $key ];
		}
		// the next is not in use yet: waitinglist_seats is currently not allowed to be multi
		if ( $exclude_waiting_list && eme_is_multi( $event['event_properties']['waitinglist_seats'] ) ) {
			$waitinglist_multiseats   = eme_convert_multi2array( $event['event_properties']['waitinglist_seats'] );
			$available_seats[ $key ] -= intval($waitinglist_multiseats[ $key ]);
		}

		if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
			$used_capacity = eme_get_event_location_used_capacity( $event );
			$free_location_capacity = $location['location_properties']['max_capacity'] - $used_capacity;
			if ($free_location_capacity < 0) {
				$free_location_capacity=0;
			}
			if ($available_seats[ $key ] > $free_location_capacity) {
				$available_seats[ $key ] = $free_location_capacity;
			}
		}
		// the number of seats left can be <0 if more than one booking happened at the same time and people fill in things slowly
		if ( $available_seats[ $key ] < 0 ) {
			$available_seats[ $key ] = 0;
		}
	}
	return $available_seats;
}

function eme_get_booked_waitinglistseats( $event_id ) {
	return eme_get_booked_seats( $event_id, 0, 1 );
}

function eme_get_booked_seats( $event_id, $exclude_waiting_list = 0, $only_waiting_list = 0 ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_booked_multiseats( $event_id, $exclude_waiting_list ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$exclude        = ( $exclude_waiting_list == 1 ) ? 'AND waitinglist=0' : '';
	$waiting        = ( $only_waiting_list == 1 ) ? 'AND waitinglist=1' : '';

	$sql = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id = %d $exclude $waiting", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_absent_bookings( $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT COUNT(*) FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id = %d AND booking_seats=0", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_booked_multiseats( $event_id, $exclude_waiting_list = 0, $only_waiting_list = 0 ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$exclude          = ( $exclude_waiting_list == 1 ) ? 'AND waitinglist=0' : '';
	$waiting          = ( $only_waiting_list == 1 ) ? 'AND waitinglist=1' : '';
	$sql              = $wpdb->prepare( "SELECT COALESCE(NULLIF(booking_seats_mp, ''), booking_seats) FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id = %d $exclude $waiting", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		if ( empty( $booked_seats ) ) {
			continue;
		}
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			// handle the case where $value is not set, can happen if someone changes an event from single to multi and bookings were already made when it was single
			if ( ! $value ) {
				$value = 0;
			}
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_get_booked_multiwaitinglistseats( $event_id ) {
	return eme_get_booked_multiseats( $event_id, 0, 1 );
}

function eme_get_paid_seats( $event_id ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_approved_multiseats( $event_id ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) AS booked_seats FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id = %d and booking_paid=1", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_paid_multiseats( $event_id ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql              = $wpdb->prepare( "SELECT COALESCE(NULLIF(booking_seats_mp, ''), booking_seats) FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id = %d and booking_paid=1", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		if ( empty( $booked_seats ) ) {
			continue;
		}
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_get_approved_seats( $event_id ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_approved_multiseats( $event_id ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) AS booked_seats FROM $bookings_table WHERE status=%d AND event_id = %d", EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_approved_multiseats( $event_id ) {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql              = $wpdb->prepare( "SELECT COALESCE(NULLIF(booking_seats_mp, ''), booking_seats) FROM $bookings_table WHERE status=%d AND event_id = %d", EME_RSVP_STATUS_APPROVED, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		if ( empty( $booked_seats ) ) {
			continue;
		}
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_get_young_pending_seats( $event_id, $exclude_booking_id = 0 ) {
	
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$old_date         = $eme_date_obj_now->minusMinutes( 5 )->getDateTime();
	return eme_get_pending_seats( $event_id, $old_date, $exclude_booking_id );
}

function eme_get_pending_seats( $event_id, $old_date = '', $exclude_booking_id = 0 ) {
	global $wpdb;
	if ( eme_is_event_multiseats( $event_id ) ) {
		return array_sum( eme_get_pending_multiseats( $event_id ) );
	}
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if ( empty( $old_date ) ) {
		$younger_than = '';
	} else {
		$younger_than = "AND creation_date > '$old_date'";
	}
	if ( empty( $exclude_booking_id ) ) {
		$exclude_booking = '';
	} else {
		$exclude_booking = "AND booking_id != $exclude_booking_id";
	}
	$sql = $wpdb->prepare( "SELECT COALESCE(SUM(booking_seats),0) AS booked_seats FROM $bookings_table WHERE status IN (%d,%d) AND event_id = %d $younger_than $exclude_booking", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $event_id );
	return $wpdb->get_var( $sql );
}

function eme_get_total_seats( $event_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT event_seats FROM $table WHERE event_id = %d", $event_id );
	$res   = $wpdb->get_var( $sql );
	if ( $res ) {
		return eme_get_total( $res );
	} else {
		return 0;
	}
}

function eme_get_young_pending_multiseats( $event_id, $exclude_booking_id = 0 ) {
	
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$old_date         = $eme_date_obj_now->minusMinutes( 5 )->getDateTime();
	return eme_get_pending_multiseats( $event_id, $old_date, $exclude_booking_id );
}
function eme_get_pending_multiseats( $event_id, $old_date = '', $exclude_booking_id = 0 ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if ( empty( $old_date ) ) {
		$younger_than = '';
	} else {
		$younger_than = "AND creation_date > '$old_date'";
	}
	if ( empty( $exclude_booking_id ) ) {
				$exclude_booking = '';
	} else {
		$exclude_booking = "AND booking_id != $exclude_booking_id";
	}
	$sql              = $wpdb->prepare( "SELECT COALESCE(NULLIF(booking_seats_mp, ''), booking_seats) FROM $bookings_table WHERE status IN (%d,%d) AND event_id = %d $younger_than $exclude_booking", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $event_id );
	$booking_seats_mp = $wpdb->get_col( $sql );
	$result           = [];
	foreach ( $booking_seats_mp as $booked_seats ) {
		$multiseats = eme_convert_multi2array( $booked_seats );
		foreach ( $multiseats as $key => $value ) {
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = $value;
			} else {
				$result[ $key ] += $value;
			}
		}
	}
	return $result;
}

function eme_are_seats_available( $event ) {
	// you can book the available number of seats, with a max of x per time
	$min_allowed = $event['event_properties']['min_allowed'];
	$event_id    = $event['event_id'];
	// no seats anymore? No booking form then ... but only if it is required that the min number of
	// bookings should be >0 (it can be=0 for attendance bookings)
	$take_attendance = 0;
	if ( $event['event_properties']['take_attendance'] ) {
		$take_attendance = 1;
	}
	$seats_available = 1;
	$total_seats     = eme_get_total( $event['event_seats'] );
	if ( $total_seats == 0 ) {
		$seats_available = 1;
	} elseif ( eme_is_multi( $min_allowed ) ) {
		$min_allowed_arr = eme_convert_multi2array( $min_allowed );
		// min_allowed can be multi, but the total seats doesn't need to be ...
		if ( ! eme_is_multi( $event['event_seats'] ) ) {
			$seats_available = eme_get_available_seats( $event_id );
		} else {
			$avail_seats = eme_get_available_multiseats( $event_id );
			foreach ( $avail_seats as $key => $value ) {
				if ( $value == 0 && ! ( $min_allowed_arr[ $key ] == 0 && $take_attendance ) ) {
					$seats_available = 0;
				}
			}
		}
	} else {
		$avail_seats = eme_get_available_seats( $event_id );
		if ( $avail_seats == 0 && ! ( $min_allowed == 0 && $take_attendance ) ) {
			$seats_available = 0;
		}
	}
	return $seats_available;
}

function eme_are_seats_available_for( $event_id, $seats, $exclude_waiting_list = 0, $exclude_pending_booking_id = 0 ) {
	$total_seats = eme_get_total_seats( $event_id );
	if ( $total_seats > 0 ) {
		$available_seats = eme_get_available_seats( $event_id, $exclude_waiting_list, $exclude_pending_booking_id );
		$remaining_seats = $available_seats - $seats;
		return ( $remaining_seats >= 0 );
	} else {
		// in case the total number of seats is 0, we always have available seats
		return 1;
	}
}

function eme_are_multiseats_available_for( $event_id, $multiseats, $exclude_waiting_list = 0, $exclude_pending_booking_id = 0 ) {
	$available_seats = eme_get_available_multiseats( $event_id, $exclude_waiting_list, $exclude_pending_booking_id );
	foreach ( $available_seats as $key => $value ) {
		$remaining_seats = $value - $multiseats[ $key ];
		if ( $remaining_seats < 0 ) {
			return 0;
		}
	}
	return 1;
}

function eme_get_bookingids_for( $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql            = $wpdb->prepare( "SELECT booking_id FROM $bookings_table WHERE status IN (%d,%d,%d) AND event_id=%d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_col( $sql );
}

function eme_get_basic_bookings_on_waitinglist( $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$bookings       = wp_cache_get( "eme_basic_bookings_onwaitinglist $event_id" );
	if ( $bookings === false ) {
		$sql      = $wpdb->prepare( "SELECT booking_id, booking_seats, booking_seats_mp, remaining FROM $bookings_table WHERE event_id=%d AND waitinglist=1 ORDER BY creation_date ASC", $event_id );
		$bookings = $wpdb->get_results( $sql, ARRAY_A );
		wp_cache_set( "eme_basic_bookings_onwaitinglist $event_id", $bookings, '', 5 );
	}
	return $bookings;
}

function eme_count_bookings_for( $event_ids, $rsvp_status = 0, $paid_status = 0 ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

	$bookings = [];
	if ( ! $event_ids ) {
		return $bookings;
	}

	$where = [];
	if ( is_array( $event_ids ) && eme_is_numeric_array( $event_ids ) ) {
		$where[] = 'bookings.event_id IN (' . join( ',', $event_ids ) . ')';
	} elseif ( is_numeric( $event_ids ) ) {
		$where[] = "bookings.event_id = $event_ids";
	} else {
		$where[] = 'bookings.event_id = 0';
	}
	if ( $rsvp_status ) {
		$where[] = "bookings.status=$rsvp_status";
	} else {
		$where[] = 'bookings.status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$where[] = 'bookings.booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$where[] = 'bookings.booking_paid=1';
	}
	$where = 'WHERE ' . implode( ' AND ', $where );
	#$sql = "SELECT * FROM $bookings_table $where ORDER BY booking_id";
	$sql = "SELECT COUNT(*) FROM $bookings_table AS bookings $where";
	return $wpdb->get_var( $sql );
}

function eme_get_bookings_for( $event_ids, $rsvp_status = 0, $paid_status = 0, $order = '' ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;

	$bookings = [];
	if ( ! $event_ids ) {
		return $bookings;
	}

	$where = [];
	if ( is_array( $event_ids ) && eme_is_numeric_array( $event_ids ) ) {
		$where[] = 'bookings.event_id IN (' . join( ',', $event_ids ) . ')';
	} elseif ( is_numeric( $event_ids ) ) {
		$where[] = "bookings.event_id = $event_ids";
	} else {
		$where[] = 'bookings.event_id = 0';
	}
	if ( $rsvp_status ) {
		$where[] = "bookings.status=$rsvp_status";
	} else {
		$where[] = 'bookings.status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$where[] = 'bookings.booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$where[] = 'bookings.booking_paid=1';
	}
	$where = 'WHERE ' . implode( ' AND ', $where );
	#$sql = "SELECT * FROM $bookings_table $where ORDER BY booking_id";
	$sql = "SELECT bookings.* FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id $where";
	if ( empty( $order ) ) {
		$sql .= ' ORDER BY people.lastname ASC, people.firstname ASC, bookings.booking_id ASC';
	} elseif ( ! empty( $order ) && preg_match( '/^[\w_\-\, ]+$/', $order ) ) {
		$sql .= " ORDER BY $order";
	}
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_bookings_for_event_wp_id( $event_id, $wp_id, $order = '' ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;

	$bookings = [];
	if ( ! $event_id || ! $wp_id ) {
		return $bookings;
	}

	$sql = $wpdb->prepare( "SELECT bookings.* FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND bookings.event_id = %d AND people.wp_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id, $wp_id );

	if ( empty( $order ) ) {
		$sql .= ' ORDER BY people.lastname ASC, people.firstname ASC, bookings.booking_id ASC';
	} elseif ( ! empty( $order ) && preg_match( '/^[\w_\-\, ]+$/', $order ) ) {
		$sql .= " ORDER BY $order";
	}

	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_booking_personids( $booking_ids ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if ( is_array( $booking_ids ) ) {
		$booking_ids = join( ',', $booking_ids );
	}
	if (eme_is_list_of_int($booking_ids) ) {
		return $wpdb->get_col("SELECT DISTINCT person_id FROM $bookings_table WHERE booking_id IN ($booking_ids)");
	} else {
		return false;
	}
}

function eme_get_bookings_by_paymentid( $payment_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$bookings       = [];
	if ( ! $payment_id ) {
		return $bookings;
	}
	$sql      = $wpdb->prepare( "SELECT * FROM $bookings_table WHERE status IN (%d,%d,%d) AND payment_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $payment_id );
	$bookings = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $bookings as $key => $booking ) {
		if ( eme_is_serialized( $booking['dcodes_used'] ) ) {
				$booking['dcodes_used'] = eme_unserialize( $booking['dcodes_used'] );
		} else {
			$booking['dcodes_used'] = [];
		}
		if ( eme_is_serialized( $booking['dcodes_entered'] ) ) {
				$booking['dcodes_entered'] = eme_unserialize( $booking['dcodes_entered'] );
		} else {
			$booking['dcodes_entered'] = [];
		}
		$bookings[ $key ] = $booking;
	}
	return $bookings;
}

function eme_get_wp_ids_for( $event_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql            = $wpdb->prepare( "SELECT DISTINCT people.wp_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND bookings.event_id = %d AND people.wp_id != 0", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $event_id );
	return $wpdb->get_col( $sql );
}

function eme_get_event_ids_for( $wp_id ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	if ( $wp_id ) {
		$sql = $wpdb->prepare( "SELECT DISTINCT bookings.event_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.status IN (%d,%d,%d) AND people.wp_id = %d", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED, $wp_id );
		return $wpdb->get_col( $sql );
	} else {
		return false;
	}
}

// for backwards compat
function eme_get_attendee_ids_for( $event_id, $rsvp_status = 0, $paid_status = 0, $order = '' ) {
	return eme_get_attendee_ids( $event_id, $rsvp_status, $paid_status, $order );
}

function eme_get_attendee_ids( $event_id, $rsvp_status = 0, $paid_status = 0, $order = '' ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	if ( is_array( $event_id ) && eme_is_numeric_array( $event_id ) ) {
		$ids_list = implode(',', $event_id);
		$sql = "SELECT DISTINCT people.person_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.event_id IN ($ids_list) AND bookings.person_id>0";
	} else {
		$sql = $wpdb->prepare( "SELECT DISTINCT people.person_id FROM $bookings_table AS bookings LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id WHERE bookings.event_id = %d AND bookings.person_id>0", $event_id );
	}
	if ( $rsvp_status ) {
		$sql .= " AND bookings.status=$rsvp_status";
	} else {
		$sql .= ' AND bookings.status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$sql .= ' AND bookings.booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$sql .= ' AND bookings.booking_paid=1';
	}
	if ( empty( $order ) ) {
		$sql .= ' ORDER BY people.lastname ASC, people.firstname ASC';
	} elseif ( ! empty( $order ) && preg_match( '/^[\w_\-\, ]+$/', $order ) ) {
		$sql .= " ORDER BY $order";
	}

	return $wpdb->get_col( $sql );
}

// for backwards compat
function eme_get_attendees_for( $event_id, $rsvp_status = 0, $paid_status = 0 ) {
	return eme_get_attendees( $event_id, $rsvp_status, $paid_status );
}

function eme_get_attendees( $event_id, $rsvp_status = 0, $paid_status = 0 ) {
	global $wpdb;
	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	if ( is_array( $event_id ) && eme_is_numeric_array( $event_id ) ) {
		$ids_list = implode(',', $event_id);
		$sql = "SELECT DISTINCT person_id FROM $bookings_table WHERE event_id IN ($ids_list)";
	} else {
		$sql = $wpdb->prepare( "SELECT DISTINCT person_id FROM $bookings_table WHERE event_id = %d", $event_id );
	}
	if ( $rsvp_status ) {
		$sql .= " AND status=$rsvp_status";
	} else {
		$sql .= ' AND status!=' . EME_RSVP_STATUS_TRASH;
	}
	if ( $paid_status == 1 ) {
		$sql .= ' AND booking_paid=0';
	} elseif ( $paid_status == 2 ) {
		$sql .= ' AND booking_paid=1';
	}

	$person_ids = $wpdb->get_col( $sql );
	if ( $person_ids ) {
		$attendees = eme_get_persons( $person_ids );
	} else {
		$attendees = [];
	}
	return $attendees;
}

// for backwards compat
function eme_get_attendees_list_for( $event, $template_id = 0, $template_id_header = 0, $template_id_footer = 0, $rsvp_status = 0, $paid_status = 0, $order = '' ) {
	return eme_get_attendees_list( $event, $template_id, $template_id_header, $template_id_footer, $rsvp_status, $paid_status, $order );
}

function eme_get_attendees_list( $event, $template_id = 0, $template_id_header = 0, $template_id_footer = 0, $rsvp_status = 0, $paid_status = 0, $order = '', $always_header_footer=0 ) {
	if ( get_option( 'eme_attendees_list_ignore_pending' ) ) {
		$rsvp_status = EME_RSVP_STATUS_APPROVED;
	}
	$attendee_ids  = eme_get_attendee_ids( $event['event_id'], $rsvp_status, $paid_status, $order );
	$format        = get_option( 'eme_attendees_list_format' );
	$format_header = DEFAULT_BOOKINGS_LIST_HEADER_FORMAT;
	$format_footer = DEFAULT_BOOKINGS_LIST_FOOTER_FORMAT;

	// rsvp not active or no rsvp for this event, then return
	if ( ! eme_is_event_rsvp( $event ) ) {
		return;
	}

	if ( $template_id ) {
		$format = eme_get_template_format( $template_id );
	}

	// header and footer can't contain per booking info, so we don't replace booking placeholders there
	if ( $template_id_header ) {
		$format_header = eme_get_template_format( $template_id_header );
	}
	if ( $template_id_footer ) {
		$format_footer = eme_get_template_format( $template_id_footer );
	}
	$eme_format_header = eme_replace_event_placeholders( $format_header, $event );
	$eme_format_footer = eme_replace_event_placeholders( $format_footer, $event );

	if ( $attendee_ids ) {
		$lang = eme_detect_lang();
		$res  = $eme_format_header;
		foreach ( $attendee_ids as $attendee_id ) {
			$attendee = eme_get_person( $attendee_id );
			$res     .= eme_replace_attendees_placeholders( $format, $event, $attendee, 'html', $lang );
		}
		$res .= $eme_format_footer;
	} else {
		$res = "";
		if ($always_header_footer)
			$res  .= $eme_format_header;
		$res .= "<p class='eme_no_bookings'>" . __( 'No responses yet!', 'events-made-easy' ) . '</p>';
		if ($always_header_footer)
			$res  .= $eme_format_footer;
	}
	return $res;
}

function eme_get_bookings_list_for_event( $event, $template_id = 0, $template_id_header = 0, $template_id_footer = 0, $rsvp_status = 0, $paid_status = 0, $wp_id = 0, $order = '', $always_header_footer=0 ) {
	if ( get_option( 'eme_attendees_list_ignore_pending' ) ) {
		$rsvp_status = EME_RSVP_STATUS_APPROVED;
	}
	if ( $wp_id ) {
		$bookings = eme_get_bookings_for_event_wp_id( $event['event_id'], $wp_id, $order );
	} else {
		$bookings = eme_get_bookings_for( $event['event_id'], $rsvp_status, $paid_status, $order );
	}
	$format_header = get_option( 'eme_bookings_list_header_format' );
	$format_footer = get_option( 'eme_bookings_list_footer_format' );

	// rsvp not active or no rsvp for this event, then return
	if ( ! eme_is_event_rsvp( $event ) ) {
		return;
	}

	if ( $template_id ) {
		$format = eme_get_template_format( $template_id );
		if (empty($format)) {
			$format = get_option( 'eme_bookings_list_format' );
		}
	} else {
		$format = get_option( 'eme_bookings_list_format' );
	}

	// header and footer can't contain per booking info, so we don't replace booking placeholders there
	if ( $template_id_header ) {
		$format_header = eme_get_template_format( $template_id_header );
	}
	if ( $template_id_footer ) {
		$format_footer = eme_get_template_format( $template_id_footer );
	}
	$eme_format_header = eme_replace_event_placeholders( $format_header, $event );
	$eme_format_footer = eme_replace_event_placeholders( $format_footer, $event );

	if ( $bookings ) {
		$lang = eme_detect_lang();
		$res  = $eme_format_header;
		foreach ( $bookings as $booking ) {
			$res .= eme_replace_booking_placeholders( $format, $event, $booking, 0, 'html', $lang );
		}
		$res .= $eme_format_footer;
	} else {
		$res = "";
		if ($always_header_footer)
			$res  .= $eme_format_header;
		$res .= "<p class='eme_no_bookings'>" . __( 'No responses yet!', 'events-made-easy' ) . '</p>';
		if ($always_header_footer)
			$res  .= $eme_format_footer;
	}
	return $res;
}

function eme_get_bookings_list_for_wp_id( $wp_id, $scope, $template = '', $template_id = 0, $template_id_header = 0, $template_id_footer = 0, $rsvp_status = 0, $paid_status = 0 ) {
	$bookings = eme_get_bookings_by_wp_id( $wp_id, $scope, $rsvp_status, $paid_status );

	if ( $template ) {
		$format        = $template;
		$format_header = '';
		$format_footer = '';
	} else {
		$format        = get_option( 'eme_bookings_list_format' );
		$format_header = get_option( 'eme_bookings_list_header_format' );
		$format_footer = get_option( 'eme_bookings_list_footer_format' );
	}

	if ( $template_id ) {
		$format = eme_get_template_format( $template_id );
		if (empty($format)) {
			$format = get_option( 'eme_bookings_list_format' );
		}
	}

	// header and footer can't contain per booking info, so we don't replace booking placeholders there
	// but for a person, no event info in header/footer either, so no replacement at all
	if ( $template_id_header ) {
		$format_header = eme_replace_generic_placeholders(eme_get_template_format( $template_id_header ));
	}
	if ( $template_id_footer ) {
		$format_footer = eme_replace_generic_placeholders(eme_get_template_format( $template_id_footer ));
	}

	if ( $bookings ) {
		$lang = eme_detect_lang();
		$res  = $format_header;
		foreach ( $bookings as $booking ) {
			$event = eme_get_event( $booking['event_id'] );
			if ( ! empty( $event ) ) {
				$res .= eme_replace_booking_placeholders( $format, $event, $booking, 0, 'html', $lang );
			}
		}
		$res .= $format_footer;
	} else {
		$res = "<p class='eme_no_bookings'>" . __( 'No bookings found.', 'events-made-easy' ) . '</p>';
	}
	return $res;
}

function eme_replace_booking_placeholders( $format, $event, $booking, $is_multibooking = 0, $target = 'html', $lang = '', $take_answers_from_post = 0 ) {
	// replace EME language tags as early as possible
        $format = eme_translate_string_nowptrans( $format );

	$orig_target  = $target;
	if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
		$target = 'html';
	}

	if ( $booking['person_id'] == -1 ) {
		// -1 ? then this is from a fake booking
		$person          = eme_add_update_person_from_form( 0, '', '', '', 0, 0, 1 );
		$person_answers  = [];
		$booking_answers = eme_get_booking_post_answers( $booking, 0 ); // add the 0-option to exclude dynamic answers
		$dyn_answers     = [];
		$files           = [];
	} else {
		$person = eme_get_person( $booking['person_id'] );
		if ( $take_answers_from_post ) {
			$booking_answers = eme_get_booking_post_answers( $booking, 0 ); // add the 0-option to exclude dynamic answers
		} else {
			$booking_answers = eme_get_nodyndata_booking_answers( $booking['booking_id'] );
		}
		$person_answers = eme_get_person_answers( $booking['person_id'] );
		$dyn_answers    = ( isset( $event['event_properties']['rsvp_dyndata'] ) ) ? eme_get_dyndata_booking_answers( $booking['booking_id'] ) : [];
		$files          = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
	}
	if ( ! $person ) {
		return;
	}
	$answers = array_merge( $booking_answers, $person_answers );
	if ( empty( $lang ) && ! empty( $person['lang'] ) ) {
		$lang = $person['lang'];
	}
	if ( empty( $lang ) ) {
		$lang = eme_detect_lang();
	}
	$total_booking_price = eme_get_total_booking_price( $booking );
	// First: replace all event placeholders, but
	// don't let eme_replace_event_placeholders replace other shortcodes yet, let eme_replace_booking_placeholders finish and that will do it
	// These also replace the generic placeholders, so no need to call eme_replace_generic_placeholders again
	$format = eme_replace_event_placeholders( $format, $event, $target, $lang, 0 );
	// if not a fake booking, replace person placeholders
	if ( $booking['person_id'] != -1 ) {
		$format = eme_replace_people_placeholders( $format, $person, $orig_target, $lang, 0 );
	}
	$format = eme_replace_email_event_placeholders( $format, $person['email'], $person['lastname'], $person['firstname'], $event, $lang );

	$current_userid = get_current_user_id();

	$payment_id = $booking['payment_id'];
	$payment    = eme_get_payment( $payment_id );

	$needle_offset = 0;
	preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$replacement        = '';
		$found              = 1;
		$need_escape        = 0;
		$need_urlencode     = 0;
		if ( strstr( $result, '#ESC' ) ) {
			$result      = str_replace( '#ESC', '#', $result );
			$need_escape = 1;
		} elseif ( strstr( $result, '#URL' ) ) {
			$result         = str_replace( '#URL', '#', $result );
			$need_urlencode = 1;
		}

		// support for #_BOOKING and #_BOOKING_
		$result = preg_replace( '/#_BOOKING(_)?/', '#_', $result );
		
		if ( preg_match( '/#_(RESP)?COMMENT/', $result ) ) {
			$replacement = $booking['booking_comment'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_(RESP)?CANCELCOMMENT/', $result ) ) {
			if ( isset( $_POST['eme_cancelcomment'] ) ) {
				$replacement = eme_sanitize_textarea( $_POST['eme_cancelcomment'] );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/(#_RESPSPACES|#_SPACES|#_RESPSEATS|#_SEATS)\{(\d+)\}/', $result, $matches ) ) {
			$field_id = intval( $matches[2] ) - 1;
			if ( eme_is_multi( $booking['event_price'] ) ) {
				$seats = eme_convert_multi2array( $booking['booking_seats_mp'] );
				if ( array_key_exists( $field_id, $seats ) ) {
					$replacement = $seats[ $field_id ];
				}
			}
		} elseif ( preg_match( '/#_(RESP)?DYNAMICFIELD\{(.*?)\}$/', $result, $matches ) ) {
			$field_key = $matches[2];
			$formfield = eme_get_formfield( $field_key );
			if ( ! empty( $dyn_answers ) ) {
				foreach ( $dyn_answers as $answer ) {
					if ( $answer['field_id'] != $formfield['field_id'] ) {
						continue;
					}
					$tmp_formfield = eme_get_formfield( $answer['field_id'] );
					if ( ! empty( $tmp_formfield ) ) {
						if ( $target == 'html' ) {
							$replacement .= eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '<br>', $target );
						} else {
							$replacement .= eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', $target ) . "\n";
						}
					}
				}
				$replacement = eme_translate( $replacement, $lang );
				if ( $target == 'html' ) {
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			}
		} elseif ( preg_match( '/#_(RESP)?DYNAMICDATA/', $result ) ) {
			# this should return something without br-tags, so html-mails don't get confused and
			# the function eme_nl2br_save_html can still do it's stuff based on the rest of the mail content/templates
			if ( ! empty( $dyn_answers ) ) {
				if ( $target == 'html' ) {
					$replacement = "<table style='border-collapse: collapse;border: 1px solid black;' class='eme_dyndata_table'>";
				}
				$old_grouping  = 1;
				$old_occurence = 0;
				foreach ( $dyn_answers as $answer ) {
					$grouping      = $answer['eme_grouping'];
					$occurence     = $answer['occurence'];
					//$class         = 'eme_print_formfield' . $answer['field_id'];
					$tmp_formfield = eme_get_formfield( $answer['field_id'] );
					if ( ! empty( $tmp_formfield ) ) {
						if ( $target == 'html' ) {
							if ( $old_grouping != $grouping || $old_occurence != $occurence ) {
								$replacement  .= "</table><br><table style='border-collapse: collapse;border: 1px solid black;' class='eme_dyndata_table'>";
								$old_grouping  = $grouping;
								$old_occurence = $occurence;
							}
							$replacement .= "<tr class='eme_dyndata_row'><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_left'>" . eme_esc_html( $tmp_formfield['field_name'] ) . ":</td><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_right'> " . eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '<br>', $target ) . '</td></tr>';
						} else {
							$replacement .= $tmp_formfield['field_name'] . ': ' . eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', $target ) . "\n";
						}
					}
				}
				if ( $target == 'html' ) {
					$replacement .= '</table>';
				}
				if ( $target == 'html' ) {
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			}
		} elseif ( preg_match( '/#_TOTALPRICE$/', $result ) ) {
			if ( $need_escape ) {
				$replacement = $total_booking_price;
			} else {
				$replacement = eme_localized_price( $total_booking_price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_TOTALPRICE_NO_VAT$/', $result ) ) {
			$price = $total_booking_price / ( 1 + $event['event_properties']['vat_pct'] / 100 );
			if ( $need_escape ) {
				$replacement = $price;
			} else {
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_TOTALPRICE_VAT_ONLY$/', $result ) ) {
			$price = $total_booking_price - $total_booking_price / ( 1 + $event['event_properties']['vat_pct'] / 100 );
			if ( $need_escape ) {
				$replacement = $price;
			} else {
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_AMOUNTRECEIVED$/', $result ) ) {
			$replacement = eme_localized_price( $booking['received'], $event['currency'], $target );
		} elseif ( preg_match( '/#_AMOUNTREMAINING$/', $result ) ) {
			if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
				$remaining = $total_booking_price;
			} else {
				$remaining = $booking['remaining'];
			}
			if ( $need_escape ) {
				$replacement = $remaining;
			} else {
				$replacement = eme_localized_price( $remaining, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_TOTALDISCOUNT$/', $result ) ) {
			if ( $need_escape ) {
				$replacement = $booking['discount'];
			} else {
				$replacement = eme_localized_price( $booking['discount'], $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_APPLIEDDISCOUNTNAMES$/', $result ) ) {
			if ( ! empty( $booking['discountids'] ) ) {
				$discount_ids   = explode( ',', $booking['discountids'] );
				$discount_names = [];
				foreach ( $discount_ids as $discount_id ) {
					$discount = eme_get_discount( $discount_id );
					if ( $discount && isset( $discount['name'] ) ) {
						$discount_names[] = eme_esc_html( $discount['name'] );
					} else {
						$discount_names[] = sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id );
					}
				}
				$replacement = join( ', ', $discount_names );
				if ( $target == 'html' ) {
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			}
		} elseif ( preg_match( '/#_DISCOUNTCODES_ENTERED$/', $result ) ) {
			$dcodes_entered = $booking['dcodes_entered'];
			$replacement    = join( ', ', $dcodes_entered );
		} elseif ( preg_match( '/#_DISCOUNTCODES_VALID|#_DISCOUNTCODES_USED$/', $result ) ) {
			$dcodes_used = $booking['dcodes_used'];
			$replacement = join( ', ', $dcodes_used );
		} elseif ( preg_match( '/#_PRICEPERSEAT$/', $result ) ) {
			$price = eme_get_seat_booking_price( $booking );
			if ( $need_escape ) {
				$replacement = $price;
			} else {
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_PRICEPERSEAT_NO_VAT$/', $result ) ) {
			$price = eme_get_seat_booking_price( $booking ) / ( 1 + $event['event_properties']['vat_pct'] / 100 );
			if ( $need_escape ) {
				$replacement = $price;
			} else {
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_PRICEPERSEAT_VAT_ONLY$/', $result ) ) {
			$price = eme_get_seat_booking_price( $booking );
			$price = $price - $price / ( 1 + $event['event_properties']['vat_pct'] / 100 );
			if ( $need_escape ) {
				$replacement = $price;
			} else {
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_PRICEPERSEAT\{(\d+)\}/', $result, $matches ) ) {
			// total price to pay per price if multiprice
			$total_prices = eme_get_seat_booking_multiprice_arr( $booking );
			$field_id     = intval( $matches[1] ) - 1;
			if ( array_key_exists( $field_id, $total_prices ) ) {
				$price = $total_prices[ $field_id ];
				if ( $need_escape ) {
					$replacement = $price;
				} else {
					$replacement = eme_localized_price( $price, $event['currency'], $target );
				}
			}
		} elseif ( preg_match( '/#_PRICEPERSEAT_NO_VAT\{(\d+)\}/', $result, $matches ) ) {
			// total price to pay per price if multiprice
			$total_prices = eme_get_seat_booking_multiprice_arr( $booking );
			$field_id     = intval( $matches[1] ) - 1;
			if ( array_key_exists( $field_id, $total_prices ) ) {
				$price = $total_prices[ $field_id ] / ( 1 + $event['event_properties']['vat_pct'] / 100 );
				if ( $need_escape ) {
					$replacement = $price;
				} else {
					$replacement = eme_localized_price( $price, $event['currency'], $target );
				}
			}
		} elseif ( preg_match( '/#_PRICEPERSEAT_VAT_ONLY\{(\d+)\}/', $result, $matches ) ) {
			// total price to pay per price if multiprice
			$total_prices = eme_get_seat_booking_multiprice_arr( $booking );
			$field_id     = intval( $matches[1] ) - 1;
			if ( array_key_exists( $field_id, $total_prices ) ) {
				$price = $total_prices[ $field_id ];
				$price = $price - $price / ( 1 + $event['event_properties']['vat_pct'] / 100 );
				if ( $need_escape ) {
					$replacement = $price;
				} else {
					$replacement = eme_localized_price( $price, $event['currency'], $target );
				}
			}
		} elseif ( preg_match( '/#_PDF_URL\{(\d+)\}/', $result, $matches ) ) {
			$template_id = intval( $matches[1] );
			$pdf_path = eme_generate_booking_pdf( $booking, $event, $template_id );
			if ( ! empty( $pdf_path ) ) {
				$replacement = EME_UPLOAD_URL . '/bookings/' . $booking['booking_id'] . '/' . basename( $pdf_path );
			}
		} elseif ( preg_match( '/#_TOTALPRICE\{(\d+)\}/', $result, $matches ) ) {
			// total price to pay per price if multiprice
			$total_prices = eme_get_total_booking_multiprice_arr( $booking );
			$field_id     = intval( $matches[1] ) - 1;
			if ( array_key_exists( $field_id, $total_prices ) ) {
				$price = $total_prices[ $field_id ];
				if ( $need_escape ) {
					$replacement = $price;
				} else {
					$replacement = eme_localized_price( $price, $event['currency'], $target );
				}
			}
		} elseif ( preg_match( '/#_CHARGE\{(.+)\}$/', $result, $matches ) ) {
			if ( $need_escape ) {
				$replacement = eme_payment_gateway_extra_charge( $total_booking_price, $matches[1] );
			} else {
				$replacement = eme_localized_price( eme_payment_gateway_extra_charge( $total_booking_price, $matches[1] ), $event['currency'], $target );
			}
		} elseif ( preg_match( '/#_RESPSPACES$|#_SPACES$|#_RESPSEATS$|#_SEATS$/', $result ) ) {
			$replacement = eme_get_total( $booking['booking_seats'] );
		} elseif ( preg_match( '/#_CREATIONDATE\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_date( $booking['creation_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( preg_match( '/#_CREATIONDATE/', $result ) ) {
			$replacement = eme_localized_date( $booking['creation_date'], EME_TIMEZONE );
		} elseif ( preg_match( '/#_CREATIONTIME\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_time( $booking['creation_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( preg_match( '/#_CREATIONTIME/', $result ) ) {
			$replacement = eme_localized_time( $booking['creation_date'], EME_TIMEZONE );
		} elseif ( $payment && preg_match( '/#_PAYMENTDATE\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_date( $booking['payment_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( $payment && preg_match( '/#_PAYMENTDATE/', $result ) ) {
			$replacement = eme_localized_date( $booking['payment_date'], EME_TIMEZONE );
		} elseif ( $payment && preg_match( '/#_PAYMENTTIME\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_time( $booking['payment_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( $payment && preg_match( '/#_PAYMENTTIME/', $result ) ) {
			$replacement = eme_localized_time( $booking['payment_date'], EME_TIMEZONE );
		} elseif ( preg_match( '/#_ID/', $result ) ) {
			$replacement = $booking['booking_id'];
		} elseif ( preg_match( '/#_TRANSFER_NBR_BE97|UNIQUE_NBR/', $result ) ) {
			$replacement = eme_unique_nbr_formatted( $booking['unique_nbr'] );
		} elseif ( preg_match( '/#_DBFIELD\{(.+)\}/', $result, $matches ) ) {
			$tmp_attkey = $matches[1];
			if ( isset( $booking[ $tmp_attkey ] ) && ! is_array( $booking[ $tmp_attkey ] ) ) {
				$replacement = $booking[ $tmp_attkey ];
				if ( $target == 'html' ) {
					$replacement = eme_trans_esc_html( $replacement, $lang );
					$replacement = apply_filters( 'eme_general', $replacement );
				} elseif ( $target == 'rss' ) {
					$replacement = eme_translate( $replacement, $lang );
					$replacement = apply_filters( 'the_content_rss', $replacement );
				} else {
					$replacement = eme_translate( $replacement, $lang );
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			}
		} elseif ( preg_match( '/#_PAYMENTID/', $result ) ) {
			$replacement = $booking['payment_id'];
		} elseif ( preg_match( '/#_PAYMENT_URL/', $result ) ) {
			// the payment url is also used for user confirmation of a booking
			if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
				$replacement = eme_booking_confirm_url( $payment );
				if ( $target == 'html' ) {
					$replacement = esc_url( $replacement );
				}
			} elseif ( ! $booking['waitinglist'] && $payment && eme_event_has_pgs_configured( $event ) ) {
				$replacement = eme_payment_url( $payment );
				if ( $target == 'html' ) {
					$replacement = esc_url( $replacement );
				}
			}
		} elseif ( preg_match( '/#_CONFIRM_URL/', $result ) ) {
			if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
				$replacement = eme_booking_confirm_url( $payment );
				if ( $target == 'html' ) {
					$replacement = esc_url( $replacement );
				}
			}
		} elseif ( $payment && preg_match( '/#_(ATTENDANCE_)?QRCODE({.*?\})?$/', $result, $matches ) ) {
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$size = substr( $matches[2], 1, -1 );
			} else {
				$size = 'medium';
			}
			$targetBasePath                = EME_UPLOAD_DIR . '/bookings/' . $booking['booking_id'];
			$targetBaseUrl                 = EME_UPLOAD_URL . '/bookings/' . $booking['booking_id'];
			$url_to_encode                 = eme_check_rsvp_url( $payment, $booking['booking_id'] );
			[$target_file, $target_url] = eme_generate_qrcode( $url_to_encode, $targetBasePath, $targetBaseUrl, $size );
			if ( is_file( $target_file ) ) {
				[$width, $height, $type, $attr] = getimagesize( $target_file );
				$replacement                    = "<img width='$width' height='$height' src='$target_url'>";
			}
		} elseif ( $payment && preg_match( '/#_ATTENDANCE_URL$/', $result ) ) {
			$replacement = eme_check_rsvp_url( $payment, $booking['booking_id'] );
			if ( $target == 'html' ) {
				$replacement = esc_url( $replacement );
			}
		} elseif ( $payment && preg_match( '/#_CANCEL_URL$/', $result ) ) {
			$replacement = eme_cancel_url( $payment );
			if ( $target == 'html' ) {
				$replacement = esc_url( $replacement );
			}
		} elseif ( $payment && preg_match( '/#_CANCEL_LINK$/', $result ) ) {
			$url = eme_cancel_url( $payment );
			if ( $target == 'html' ) {
				$url = esc_url( $url );
			}
			$replacement = "<a href='$url'>" . __( 'Cancel booking', 'events-made-easy' ) . '</a>';
		} elseif ( $payment && preg_match( '/#_CANCEL_OWN_URL$/', $result ) ) {
			if ( $person['wp_id'] == $current_userid || $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) {
				$replacement = eme_cancel_url( $payment );
				if ( $target == 'html' ) {
					$replacement = esc_url( $replacement );
				}
			}
		} elseif ( $payment && preg_match( '/#_CANCEL_OWN_LINK$/', $result ) ) {
			if ( $person['wp_id'] == $current_userid || $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) {
				$url = eme_cancel_url( $payment );
				if ( $target == 'html' ) {
					$url = esc_url( $url );
				}
				$replacement = "<a href='$url'>" . __( 'Cancel booking', 'events-made-easy' ) . '</a>';
			}
		} elseif ( $payment && preg_match( '/#_CANCEL_CODE$/', $result ) ) {
			$replacement = $payment['random_id'];
		} elseif ( preg_match( '/#_FILES/', $result ) ) {
			$res_files = [];
			foreach ( $files as $file ) {
				if ( $target == 'html' ) {
					$res_files[] = eme_get_uploaded_file_html( $file );
				} else {
					$res_files[] = $file['name'] . ' [' . $file['url'] . ']';
				}
			}
			if ( $target == 'html' ) {
				$replacement = join( '<br>', $res_files );
			} else {
				$replacement = join( "\n", $res_files );
			}
		} elseif ( preg_match( '/#_FIELDS/', $result ) ) {
			$field_replace = '';
			if ( $target == 'html' ) {
				$sep     = '<br>';
				$eol_sep = '<br>';
			} else {
				$sep     = '||';
				$eol_sep = "\n";
			}
			foreach ( $answers as $answer ) {
				$tmp_formfield = eme_get_formfield( $answer['field_id'] );
				if ( ! empty( $tmp_formfield ) ) {
					$tmp_answer     = eme_answer2readable( $answer['answer'], $tmp_formfield, 1, $sep, $target );
					$field_replace .= $tmp_formfield['field_name'] . ": $tmp_answer" . $eol_sep;
				}
			}
			$replacement = eme_translate( $field_replace, $lang );
			if ( $target == 'html' ) {
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PAID|#_PAYED/', $result ) ) {
			$replacement = ( $booking['booking_paid'] ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
		} elseif ( preg_match( '/#_IS_PAID|#_IS_PAYED/', $result ) ) {
			$replacement = ( $booking['booking_paid'] ) ? 1 : 0;
		} elseif ( preg_match( '/#_IS_PENDING/', $result ) ) {
			$replacement = ( $booking['status'] == EME_RSVP_STATUS_PENDING ) ? 1 : 0;
		} elseif ( preg_match( '/#_IS_USERPENDING/', $result ) ) {
			$replacement = ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) ? 1 : 0;
		} elseif ( preg_match( '/#_IS_APPROVED/', $result ) ) {
			$replacement = ( $booking['status'] == EME_RSVP_STATUS_APPROVED ) ? 1 : 0;
		} elseif ( preg_match( '/#_ON_WAITINGLIST/', $result ) ) {
			$replacement = ( $booking['waitinglist'] ) ? 1 : 0;
		} elseif ( preg_match( '/#_WAITINGLIST_POSITION/', $result ) ) {
			$basic_bookings = eme_get_basic_bookings_on_waitinglist( $booking['event_id'] );
			$position       = 1;
			foreach ( $basic_bookings as $basic_booking ) {
				if ( $basic_booking['booking_id'] == $booking['booking_id'] ) {
					$replacement = $position;
					break;
				}
				++$position;
			}
		} elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
			$field_key = $matches[1];
			$formfield = eme_get_formfield( $field_key );
			if ( ! empty( $formfield ) ) {
				if ( $target == 'html' ) {
					$replacement = eme_trans_esc_html( $formfield['field_name'], $lang );
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = eme_translate( $formfield['field_name'], $lang );
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			} else {
				$found = 0;
			}
		} elseif ( preg_match( '/#_FIELD(VALUE)?\{(.+?)\}(\{.+?\})?/', $result, $matches ) ) {
			$field_key = $matches[2];
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$sep = substr( $matches[3], 1, -1 );
			} else {
				$sep = '||';
			}
			$formfield = eme_get_formfield( $field_key );
			if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp' ] ) ) {
				$field_id      = $formfield['field_id'];
				$field_replace = '';
				foreach ( $answers as $answer ) {
					if ( $answer['field_id'] == $field_id ) {
						if ( $matches[1] == 'VALUE' || $take_answers_from_post ) {
							$field_replace = eme_answer2readable( $answer['answer'], $formfield, 0, $sep, $target );
						} else {
							$field_replace = eme_answer2readable( $answer['answer'], $formfield, 1, $sep, $target );
						}
						continue;
					}
				}
				foreach ( $files as $file ) {
					if ( $file['field_id'] == $field_id ) {
						if ( $target == 'html' ) {
							$field_replace .= eme_get_uploaded_file_html( $file ) . '<br>';
						} else {
							$field_replace .= $file['name'] . ' [' . $file['url'] . ']' . "\n";
						}
					}
				}

				$replacement = eme_translate( $field_replace, $lang );
				if ( $target == 'html' ) {
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			} else {
				$found = 0;
			}
		} elseif ( $payment && preg_match( '/#_MULTIBOOKING_SEATS$/', $result ) ) {
			if ( $is_multibooking ) {
				// returns the total of all seats for all bookings in the payment id related to this booking
				$replacement = eme_get_payment_seats( $payment );
			}
		} elseif ( $payment_id && preg_match( '/#_MULTIBOOKING_TOTALPRICE$/', $result ) ) {
			if ( $is_multibooking ) {
				// returns the price for all bookings in the payment id related to this booking
				$price       = eme_get_payment_price( $payment_id );
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( $payment_id && preg_match( '/#_MULTIBOOKING_TOTALPRICE_NO_VAT$/', $result ) ) {
			if ( $is_multibooking ) {
				// returns the price for all bookings in the payment id related to this booking
				$price       = eme_get_payment_price_novat( $payment_id );
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( $payment_id && preg_match( '/#_MULTIBOOKING_TOTALPRICE_VAT_ONLY$/', $result ) ) {
			if ( $is_multibooking ) {
				// returns the price for all bookings in the payment id related to this booking
				$price       = eme_get_payment_price_vatonly( $payment_id );
				$replacement = eme_localized_price( $price, $event['currency'], $target );
			}
		} elseif ( $payment_id && preg_match( '/#_MULTIBOOKING_DETAILS_TEMPLATE\{(\d+)\}$/', $result, $matches ) ) {
			$template_id = intval( $matches[1] );
			$template    = eme_get_template_format( $template_id );
			$res         = '';
			if ( $template && $is_multibooking ) {
				$bookings = eme_get_bookings_by_paymentid( $payment_id );
				foreach ( $bookings as $tmp_booking ) {
					$tmp_event = eme_get_event( $tmp_booking['event_id'] );
					if ( ! empty( $event ) ) {
						$res .= eme_replace_booking_placeholders( $template, $tmp_event, $tmp_booking, $is_multibooking, 'text', $lang ) . "\n";
					}
				}
			}
			$replacement = $res;
		} elseif ( preg_match( '/#_IS_MULTIBOOKING/', $result ) ) {
			$replacement = $is_multibooking;
		} else {
			$found = 0;
		}

		if ( $found ) {
			if ( $need_escape ) {
				$replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
			}
			$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	$format = eme_replace_payment_gateway_placeholders( $format, $booking['pg'], $total_booking_price, $event['currency'], $event['event_properties']['vat_pct'], $orig_target, $lang, 0 );
	// now replace any language tags found in the format itself
	$format = eme_translate( $format, $lang );

	// now some html
	if ( $target == 'html' ) {
		$format = eme_nl2br_save_html( $format );
	}

	return do_shortcode( $format );
}

function eme_replace_attendees_placeholders( $format, $event, $person, $target = 'html', $lang = '' ) {
	// replace EME language tags as early as possible
        $format = eme_translate_string_nowptrans( $format );

	$orig_target  = $target;
	if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
		$target = 'html';
	}

	if ( empty( $lang ) ) {
		$lang = $person['lang'];
	}
	// First: replace all event placeholders, but
	// don't let eme_replace_event_placeholders replace other shortcodes yet, let eme_replace_attendees_placeholders finish and that will do it
	$format = eme_replace_event_placeholders( $format, $event, $orig_target, $lang, 0 );
	$format = eme_replace_people_placeholders( $format, $person, $orig_target, $lang, 0 );
	$format = eme_replace_email_event_placeholders( $format, $person['email'], $person['lastname'], $person['firstname'], $event, $lang );

	$needle_offset = 0;
	preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$replacement        = '';
		$found              = 1;
		$need_escape        = 0;
		if ( strstr( $result, '#ESC' ) ) {
			$result      = str_replace( '#ESC', '#', $result );
			$need_escape = 1;
		}
		if ( preg_match( '/#_ATTENDSPACES$|#_ATTENDSEATS$/', $result ) ) {
			$replacement = eme_get_booked_seats_by_person_event_id( $person['person_id'], $event['event_id'] );
		} elseif ( preg_match( '/(#_ATTENDSPACES|#_ATTENDSEATS)\{(\d+)\}$/', $result, $matches ) ) {
			$field_id    = intval( $matches[2] ) - 1;
			$replacement = 0;
			if ( eme_is_multi( $event['event_seats'] ) ) {
				$seats = eme_get_booked_multiseats_by_person_event_id( $person['person_id'], $event['event_id'] );
				if ( array_key_exists( $field_id, $seats ) ) {
					$replacement = $seats[ $field_id ];
				}
			}
		} else {
			$found = 0;
		}
		if ( $found ) {
			if ( $need_escape ) {
				$replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
			}
			$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	// now replace any language tags found in the format itself
	$format = eme_translate( $format, $lang );

	// now some html
	if ( $target == 'html' ) {
		$format = eme_nl2br_save_html( $format );
	}

	return do_shortcode( $format );
}

// backwards compatibility
function eme_email_rsvp_booking( $booking, $action, $is_multibooking = 0, $queue = 0 ) {
	return eme_email_booking_action( $booking, $action, $is_multibooking );
}

function eme_email_booking_action( $booking, $action, $is_multibooking = 0 ) {
	// first check if a mail should be send at all
	$mailing_is_active = get_option( 'eme_rsvp_mail_notify_is_active' );
	if ( ! $mailing_is_active ) {
		return true;
	}

	$mailing_pending  = get_option( 'eme_rsvp_mail_notify_pending' );
	$mailing_approved = get_option( 'eme_rsvp_mail_notify_approved' );
	$mailing_paid     = get_option( 'eme_rsvp_mail_notify_paid' );

	$person = eme_get_person( $booking['person_id'] );
	if ( ! $person ) {
		return;
	}
	$event = eme_get_event( $booking['event_id'] );
	if ( empty( $event ) ) {
		return;
	}
	$contact        = eme_get_event_contact( $event );
	$contact_email  = $contact->user_email;
	$contact_name   = $contact->display_name;
	$mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';

	// and now send the wanted mails
	$person_name            = eme_format_full_name( $person['firstname'], $person['lastname'] );
	$person_subject         = '';
	$person_subject_filter  = '';
	$person_body            = '';
	$person_body_filter     = '';
	$contact_subject        = '';
	$contact_subject_filter = '';
	$contact_body           = '';
	$contact_body_filter    = '';
	$attachment_ids         = '';
	$attachment_tmpl_ids_arr = [];
	$ticket_attachment      = '';
	switch ( $action ) {
		case 'resendApprovedBooking':
			// can only be called from within the backend interface
			// so we don't send the mail to the event contact
			if ( $mailing_approved ) {
				$template_id = $event['event_properties']['ticket_template_id'];
				if ( $template_id && ( $event['event_properties']['ticket_mail'] == 'approval' || $event['event_properties']['ticket_mail'] == 'always' ) ) {
					$ticket_attachment = eme_generate_booking_pdf( $booking, $event, $template_id );
				}
				$attachment_ids = $event['event_properties']['booking_attach_ids'];
				if ( empty( $attachment_ids ) ) {
					$attachment_ids = get_option( 'eme_booking_attach_ids' );
				}
				$attachment_tmpl_ids_arr = $event['event_properties']['booking_attach_tmpl_ids'];
				if ( empty( $attachment_tmpl_ids_arr ) ) {
					$attachment_tmpl_ids_arr = get_option( 'eme_booking_attach_tmpl_ids' );
				}
				if ( ! empty( $event['event_properties']['event_respondent_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_respondent_email_subject'];
				} elseif ( $event['event_properties']['event_respondent_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_respondent_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_respondent_email_subject' );
				}

				if ( ! empty( $event['event_respondent_email_body'] ) ) {
					$person_body = $event['event_respondent_email_body'];
				} elseif ( $event['event_properties']['event_respondent_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_respondent_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_respondent_email_body' );
				}
			} else {
				return true;
			}
			$person_subject_filter = 'confirmed_subject';
			$person_body_filter    = 'confirmed_body';
			break;
		case 'pendingBooking':
			// can only be called from within the backend interface
			// so we don't send the mail to the event contact
			if ( $mailing_pending ) {
				$attachment_ids = $event['event_properties']['pending_attach_ids'];
				if ( empty( $attachment_ids ) ) {
					$attachment_ids = get_option( 'eme_pending_attach_ids' );
				}
				$attachment_tmpl_ids_arr = $event['event_properties']['pending_attach_tmpl_ids'];
				if ( empty( $attachment_tmpl_ids_arr ) ) {
					$attachment_tmpl_ids_arr = get_option( 'eme_pending_attach_tmpl_ids' );
				}
				if ( ! empty( $event['event_properties']['event_registration_pending_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_registration_pending_email_subject'];
				} elseif ( $event['event_properties']['event_registration_pending_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_registration_pending_email_subject' );
				}

				if ( ! empty( $event['event_registration_pending_email_body'] ) ) {
					$person_body = $event['event_registration_pending_email_body'];
				} elseif ( $event['event_properties']['event_registration_pending_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_registration_pending_email_body' );
				}
			} else {
				return true;
			}
			$person_subject_filter = 'pending_subject';
			$person_body_filter    = 'pending_body';
			break;
		case 'reminderPendingBooking':
			// we don't send the mail to the event contact
			if ( $mailing_pending ) {
				if ( ! empty( $event['event_properties']['event_registration_pending_reminder_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_registration_pending_reminder_email_subject'];
				} elseif ( $event['event_properties']['event_registration_pending_reminder_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_reminder_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_registration_pending_reminder_email_subject' );
				}

				if ( ! empty( $event['event_registration_pending_reminder_email_body'] ) ) {
					$person_body = $event['event_registration_pending_reminder_email_body'];
				} elseif ( $event['event_properties']['event_registration_pending_reminder_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_reminder_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_registration_pending_reminder_email_body' );
				}
			} else {
				return true;
			}
			$person_subject_filter = 'pending_reminder_subject';
			$person_body_filter    = 'pending_reminder_body';
			break;
		case 'reminderBooking':
			// we don't send the mail to the event contact
			if ( $mailing_approved ) {
				if ( ! empty( $event['event_properties']['event_registration_reminder_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_registration_reminder_email_subject'];
				} elseif ( $event['event_properties']['event_registration_reminder_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_reminder_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_registration_reminder_email_subject' );
				}

				if ( ! empty( $event['event_registration_reminder_email_body'] ) ) {
					$person_body = $event['event_registration_reminder_email_body'];
				} elseif ( $event['event_properties']['event_registration_reminder_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_reminder_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_registration_reminder_email_body' );
				}
			} else {
				return true;
			}
			$person_subject_filter = 'reminder_subject';
			$person_body_filter    = 'reminder_body';
			break;
		case 'trashBooking':
			if ( ! empty( $event['event_properties']['event_registration_trashed_email_subject'] ) ) {
				$person_subject = $event['event_properties']['event_registration_trashed_email_subject'];
			} elseif ( $event['event_properties']['event_registration_trashed_email_subject_tpl'] > 0 ) {
				$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_trashed_email_subject_tpl'] );
			} else {
				$person_subject = get_option( 'eme_registration_trashed_email_subject' );
			}

			if ( ! empty( $event['event_registration_trashed_email_body'] ) ) {
				$person_body = $event['event_registration_trashed_email_body'];
			} elseif ( $event['event_properties']['event_registration_trashed_email_body_tpl'] > 0 ) {
				$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_trashed_email_body_tpl'] );
			} else {
				$person_body = get_option( 'eme_registration_trashed_email_body' );
			}
			$person_subject_filter = 'trash_subject';
			$person_body_filter    = 'trash_body';
			break;
		case 'updateBooking':
			if ( ! empty( $event['event_properties']['event_registration_updated_email_subject'] ) ) {
				$person_subject = $event['event_properties']['event_registration_updated_email_subject'];
			} elseif ( $event['event_properties']['event_registration_updated_email_subject_tpl'] > 0 ) {
				$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_updated_email_subject_tpl'] );
			} else {
				$person_subject = get_option( 'eme_registration_updated_email_subject' );
			}

			if ( ! empty( $event['event_registration_updated_email_body'] ) ) {
				$person_body = $event['event_registration_updated_email_body'];
			} elseif ( $event['event_properties']['event_registration_updated_email_body_tpl'] > 0 ) {
				$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_updated_email_body_tpl'] );
			} else {
				$person_body = get_option( 'eme_registration_updated_email_body' );
			}
			$person_subject_filter = 'updated_subject';
			$person_body_filter    = 'updated_body';
			break;
		case 'cancelBooking':
			if ( ! empty( $event['event_properties']['event_registration_cancelled_email_subject'] ) ) {
				$person_subject = $event['event_properties']['event_registration_cancelled_email_subject'];
			} elseif ( $event['event_properties']['event_registration_cancelled_email_subject_tpl'] > 0 ) {
				$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_cancelled_email_subject_tpl'] );
			} else {
				$person_subject = get_option( 'eme_registration_cancelled_email_subject' );
			}

			if ( ! empty( $event['event_registration_cancelled_email_body'] ) ) {
				$person_body = $event['event_registration_cancelled_email_body'];
			} elseif ( $event['event_properties']['event_registration_cancelled_email_body_tpl'] > 0 ) {
				$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_cancelled_email_body_tpl'] );
			} else {
				$person_body = get_option( 'eme_registration_cancelled_email_body' );
			}

			if ( ! empty( $event['event_properties']['contactperson_registration_cancelled_email_subject'] ) ) {
				$contact_subject = $event['event_properties']['contactperson_registration_cancelled_email_subject'];
			} elseif ( $event['event_properties']['contactperson_registration_cancelled_email_subject_tpl'] > 0 ) {
				$contact_subject = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_cancelled_email_subject_tpl'] );
			} else {
				$contact_subject = get_option( 'eme_contactperson_cancelled_email_subject' );
			}

			if ( ! empty( $event['event_properties']['contactperson_registration_cancelled_email_body'] ) ) {
				$contact_body = $event['event_properties']['contactperson_registration_cancelled_email_body'];
			} elseif ( $event['event_properties']['contactperson_registration_cancelled_email_body_tpl'] > 0 ) {
				$contact_body = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_cancelled_email_body_tpl'] );
			} else {
				$contact_body = get_option( 'eme_contactperson_cancelled_email_body' );
			}

			$person_subject_filter  = 'cancelled_subject';
			$person_body_filter     = 'cancelled_body';
			$contact_subject_filter = 'contact_cancelled_body';
			$contact_body_filter    = 'contact_cancelled_subject';
			break;
		case 'paidBooking':
			if ( $mailing_paid ) {
				$template_id = $event['event_properties']['ticket_template_id'];
				if ( $template_id && ( $event['event_properties']['ticket_mail'] == 'payment' || $event['event_properties']['ticket_mail'] == 'always' ) ) {
					$ticket_attachment = eme_generate_booking_pdf( $booking, $event, $template_id );
				}
				$attachment_ids = $event['event_properties']['paid_attach_ids'];
				if ( empty( $attachment_ids ) ) {
					$attachment_ids = get_option( 'eme_paid_attach_ids' );
				}
				$attachment_tmpl_ids_arr = $event['event_properties']['paid_attach_tmpl_ids'];
				if ( empty( $attachment_tmpl_ids_arr ) ) {
					$attachment_tmpl_ids_arr = get_option( 'eme_paid_attach_tmpl_ids' );
				}
				if ( ! empty( $event['event_properties']['event_registration_paid_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_registration_paid_email_subject'];
				} elseif ( $event['event_properties']['event_registration_paid_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_paid_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_registration_paid_email_subject' );
				}

				if ( ! empty( $event['event_registration_paid_email_body'] ) ) {
					$person_body = $event['event_registration_paid_email_body'];
				} elseif ( $event['event_properties']['event_registration_paid_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_paid_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_registration_paid_email_body' );
				}
				if ( ! empty( $event['event_properties']['contactperson_registration_paid_email_subject'] ) ) {
					$contact_subject = $event['event_properties']['contactperson_registration_paid_email_subject'];
				} elseif ( $event['event_properties']['contactperson_registration_paid_email_subject_tpl'] > 0 ) {
					$contact_subject = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_paid_email_subject_tpl'] );
				} else {
					$contact_subject = get_option( 'eme_contactperson_paid_email_subject' );
				}

				if ( ! empty( $event['event_properties']['contactperson_registration_paid_email_body'] ) ) {
					$contact_body = $event['event_properties']['contactperson_registration_paid_email_body'];
				} elseif ( $event['event_properties']['contactperson_registration_paid_email_body_tpl'] > 0 ) {
					$contact_body = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_paid_email_body_tpl'] );
				} else {
					$contact_body = get_option( 'eme_contactperson_paid_email_body' );
				}
			} else {
				return true;
			}
			$person_subject_filter  = 'paid_subject';
			$person_body_filter     = 'paid_body';
			$contact_subject_filter = 'contact_paid_subject';
			$contact_body_filter    = 'contact_paid_body';
			break;
		case 'pendingButPaid':
			$contact_subject = 'Failed to auto-approve pending paid booking';
			$contact_body = 'A payment was received via a payment gateway for a pending booking <br>with booking id #_BOOKINGID, but since the event (#_EVENTNAME) is fully booked,<br> the booking did not get automatically approved.<br><br>Yours faithfully,<br>#_CONTACTPERSON';
			$person_subject_filter  = 'pendingbutpaid_subject';
			$person_body_filter     = 'pendingbutpaid_body';
			$contact_subject_filter = 'contact_pendingbutpaid_subject';
			$contact_body_filter    = 'contact_pendingbutpaid_body';
			break;
		case 'ipnReceived':
			if ( ! empty( $event['event_properties']['contactperson_registration_ipn_email_subject'] ) ) {
				$contact_subject = $event['event_properties']['contactperson_registration_ipn_email_subject'];
			} elseif ( $event['event_properties']['contactperson_registration_ipn_email_subject_tpl'] > 0 ) {
				$contact_subject = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_ipn_email_subject_tpl'] );
			} else {
				$contact_subject = get_option( 'eme_contactperson_ipn_email_subject' );
			}

			if ( ! empty( $event['event_properties']['contactperson_registration_ipn_email_body'] ) ) {
				$contact_body = $event['event_properties']['contactperson_registration_ipn_email_body'];
			} elseif ( $event['event_properties']['contactperson_registration_ipn_email_body_tpl'] > 0 ) {
				$contact_body = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_ipn_email_body_tpl'] );
			} else {
				$contact_body = get_option( 'eme_contactperson_ipn_email_body' );
			}
			$contact_subject_filter = 'contact_ipn_subject';
			$contact_body_filter    = 'contact_ipn_body';
			break;
		// approveBooking falls in the default too
		// case 'approveBooking':
		default:
			// this is the case when booking from frontend happened, there we decide pending or approved based on event and booking properties
			// send different mails depending on approval or not
			if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
				if ( ! empty( $event['event_properties']['event_registration_userpending_email_subject'] ) ) {
					$person_subject = $event['event_properties']['event_registration_userpending_email_subject'];
				} elseif ( $event['event_properties']['event_registration_userpending_email_subject_tpl'] > 0 ) {
					$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_userpending_email_subject_tpl'] );
				} else {
					$person_subject = get_option( 'eme_registration_userpending_email_subject' );
				}

				if ( ! empty( $event['event_properties']['event_registration_userpending_email_body'] ) ) {
					$person_body = $event['event_properties']['event_registration_userpending_email_body'];
				} elseif ( $event['event_properties']['event_registration_userpending_email_body_tpl'] > 0 ) {
					$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_userpending_email_body_tpl'] );
				} else {
					$person_body = get_option( 'eme_registration_userpending_email_body' );
				}
				$person_subject_filter = 'userconfirmation_pending_subject';
				$person_body_filter    = 'userconfirmation_pending_body';
			} elseif ( $booking['status'] == EME_RSVP_STATUS_PENDING && ( $event['registration_requires_approval'] || $booking['waitinglist'] ) ) {
				if ( $mailing_pending ) {
					$template_id = $event['event_properties']['ticket_template_id'];
					if ( $template_id && ( $event['event_properties']['ticket_mail'] == 'booking' || $event['event_properties']['ticket_mail'] == 'always' ) ) {
						$ticket_attachment = eme_generate_booking_pdf( $booking, $event, $template_id );
					}
					$attachment_ids = $event['event_properties']['pending_attach_ids'];
					if ( empty( $attachment_ids ) ) {
						$attachment_ids = get_option( 'eme_pending_attach_ids' );
					}
					$attachment_tmpl_ids_arr = $event['event_properties']['pending_attach_tmpl_ids'];
					if ( empty( $attachment_tmpl_ids_arr ) ) {
						$attachment_tmpl_ids_arr = get_option( 'eme_pending_attach_tmpl_ids' );
					}
					if ( ! empty( $event['event_properties']['event_registration_pending_email_subject'] ) ) {
						$person_subject = $event['event_properties']['event_registration_pending_email_subject'];
					} elseif ( $event['event_properties']['event_registration_pending_email_subject_tpl'] > 0 ) {
						$person_subject = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_email_subject_tpl'] );
					} else {
						$person_subject = get_option( 'eme_registration_pending_email_subject' );
					}

					if ( ! empty( $event['event_registration_pending_email_body'] ) ) {
						$person_body = $event['event_registration_pending_email_body'];
					} elseif ( $event['event_properties']['event_registration_pending_email_body_tpl'] > 0 ) {
						$person_body = eme_get_template_format_plain( $event['event_properties']['event_registration_pending_email_body_tpl'] );
					} else {
						$person_body = get_option( 'eme_registration_pending_email_body' );
					}
					if ( ! empty( $event['event_properties']['contactperson_registration_pending_email_subject'] ) ) {
						$contact_subject = $event['event_properties']['contactperson_registration_pending_email_subject'];
					} elseif ( $event['event_properties']['contactperson_registration_pending_email_subject_tpl'] > 0 ) {
						$contact_subject = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_pending_email_subject_tpl'] );
					} else {
						$contact_subject = get_option( 'eme_contactperson_pending_email_subject' );
					}

					if ( ! empty( $event['event_properties']['contactperson_registration_pending_email_body'] ) ) {
						$contact_body = $event['event_properties']['contactperson_registration_pending_email_body'];
					} elseif ( $event['event_properties']['contactperson_registration_pending_email_body_tpl'] > 0 ) {
						$contact_body = eme_get_template_format_plain( $event['event_properties']['contactperson_registration_pending_email_body_tpl'] );
					} else {
						$contact_body = get_option( 'eme_contactperson_pending_email_body' );
					}
				} else {
					return true;
				}
				$person_subject_filter  = 'pending_subject';
				$person_body_filter     = 'pending_body';
				$contact_subject_filter = 'contact_pending_subject';
				$contact_body_filter    = 'contact_pending_body';
			} else {
				// if we don't require approval and the total price is 0, we add the optional ticket template here
				if ( $mailing_approved ) {
					$template_id = $event['event_properties']['ticket_template_id'];
					if ( $template_id && ( $event['event_properties']['ticket_mail'] == 'booking' || $event['event_properties']['ticket_mail'] == 'approval' || $event['event_properties']['ticket_mail'] == 'always' ) ) {
						$ticket_attachment = eme_generate_booking_pdf( $booking, $event, $template_id );
					}
					$attachment_ids = $event['event_properties']['booking_attach_ids'];
					if ( empty( $attachment_ids ) ) {
						$attachment_ids = get_option( 'eme_booking_attach_ids' );
					}
					$attachment_tmpl_ids_arr = $event['event_properties']['booking_attach_tmpl_ids'];
					if ( empty( $attachment_tmpl_ids_arr ) ) {
						$attachment_tmpl_ids_arr = get_option( 'eme_booking_attach_tmpl_ids' );
					}
					if ( ! empty( $event['event_properties']['event_respondent_email_subject'] ) ) {
						$person_subject = $event['event_properties']['event_respondent_email_subject'];
					} elseif ( $event['event_properties']['event_respondent_email_subject_tpl'] > 0 ) {
						$person_subject = eme_get_template_format_plain( $event['event_properties']['event_respondent_email_subject_tpl'] );
					} else {
						$person_subject = get_option( 'eme_respondent_email_subject' );
					}

					if ( ! empty( $event['event_respondent_email_body'] ) ) {
						$person_body = $event['event_respondent_email_body'];
					} elseif ( $event['event_properties']['event_respondent_email_body_tpl'] > 0 ) {
						$person_body = eme_get_template_format_plain( $event['event_properties']['event_respondent_email_body_tpl'] );
					} else {
						$person_body = get_option( 'eme_respondent_email_body' );
					}
					if ( ! empty( $event['event_properties']['event_contactperson_email_subject'] ) ) {
						$contact_subject = $event['event_properties']['event_contactperson_email_subject'];
					} elseif ( $event['event_properties']['event_contactperson_email_subject_tpl'] > 0 ) {
						$contact_subject = eme_get_template_format_plain( $event['event_properties']['event_contactperson_email_subject_tpl'] );
					} else {
						$contact_subject = get_option( 'eme_contactperson_email_subject' );
					}

					if ( ! empty( $event['event_contactperson_email_body'] ) ) {
						$contact_body = $event['event_contactperson_email_body'];
					} elseif ( $event['event_properties']['event_contactperson_email_body_tpl'] > 0 ) {
						$contact_body = eme_get_template_format_plain( $event['event_properties']['event_contactperson_email_body_tpl'] );
					} else {
						$contact_body = get_option( 'eme_contactperson_email_body' );
					}
				} else {
					return true;
				}
				$person_subject_filter  = 'confirmed_subject';
				$person_body_filter     = 'confirmed_body';
				$contact_subject_filter = 'contact_subject';
				$contact_body_filter    = 'contact_body';
			}
	}

	// replace needed placeholders
	if ( ! empty( $person_subject ) ) {
		$person_subject = eme_replace_booking_placeholders( $person_subject, $event, $booking, $is_multibooking, 'text', $person['lang'] );
		if ( ! empty( $person_subject_filter ) ) {
			$filtername = 'eme_rsvp_email_' . $mail_text_html . '_' . $person_subject_filter . '_filter';
			if ( has_filter( $filtername ) ) {
				$person_subject = apply_filters( $filtername, $person_subject );
			}
		}
	}

	if ( ! empty( $person_body ) ) {
		$person_body = eme_replace_booking_placeholders( $person_body, $event, $booking, $is_multibooking, $mail_text_html, $person['lang'] );
		if ( ! empty( $person_body_filter ) ) {
			$filtername = 'eme_rsvp_email_' . $mail_text_html . '_' . $person_body_filter . '_filter';
			if ( has_filter( $filtername ) ) {
				$person_body = apply_filters( $filtername, $person_body );
			}
		}
	}

	$lang = eme_detect_lang();
	if ( ! empty( $contact_subject ) ) {
		$contact_subject = eme_replace_booking_placeholders( $contact_subject, $event, $booking, $is_multibooking, 'text', $lang );
		if ( ! empty( $contact_subject_filter ) ) {
			$filtername = 'eme_rsvp_email_' . $mail_text_html . '_' . $contact_subject_filter . '_filter';
			if ( has_filter( $filtername ) ) {
				$contact_subject = apply_filters( $filtername, $contact_subject );
			}
		}
	}

	if ( ! empty( $contact_body ) ) {
		$contact_body = eme_replace_booking_placeholders( $contact_body, $event, $booking, $is_multibooking, $mail_text_html, $lang );
		if ( ! empty( $contact_body_filter ) ) {
			$filtername = 'eme_rsvp_email_' . $mail_text_html . '_' . $contact_body_filter . '_filter';
			if ( has_filter( $filtername ) ) {
				$contact_body = apply_filters( $filtername, $contact_body );
			}
		}
	}

	// possible mail body filter: eme_rsvp_email_body_text_filter or eme_rsvp_email_body_html_filter
	// here "html" is enough
	if ( $mail_text_html == 'htmlmail' ) {
		$mail_text_html = 'html';
	}
	$filtername = 'eme_rsvp_email_body_' . $mail_text_html . '_filter';
	if ( has_filter( $filtername ) ) {
		if ( ! empty( $person_body ) ) {
			$person_body = apply_filters( $filtername, $person_body );
		}
		if ( ! empty( $contact_body ) ) {
			$contact_body = apply_filters( $filtername, $contact_body );
		}
	}

	// now an action, so you can hook into everything
	if ( has_action( 'eme_rsvp_email_action' ) ) {
		do_action( 'eme_rsvp_email_action', $booking, $action, $person_subject, $person_body );
	}

	// now send the mails
	$mail_res = true; // make sure we return true if no mail is sent due to empty subject or body
	if ( ! empty( $contact_subject ) && ! empty( $contact_body ) ) {
		// from, to and replyto are all 3 identical, so the next function call seems a bit weird :-)
		$mail_res = eme_queue_mail( $contact_subject, $contact_body, $contact_email, $contact_name, $contact_email, $contact_name, $contact_email, $contact_name );
	}
	if ( ! empty( $person_subject ) && ! empty( $person_body ) ) {
		// this possibily overrides mail_res, but that's ok since errors for mail to people are more important than to the contact person
		// to make sure the attachment_ids is an array ...
		if ( empty( $attachment_ids ) ) {
			$attachment_ids_arr = [];
		} else {
			$attachment_ids_arr = array_unique(explode( ',', $attachment_ids ));
		}
		// add the optional ticket template too, but since it is not a wp attachment, we directly add the ticket file path
		if ( ! empty( $ticket_attachment ) ) {
			$attachment_ids_arr[] = $ticket_attachment;
		}
		// create and add the needed pdf attachments too
		if ( !empty( $attachment_tmpl_ids_arr ) ) {
			foreach ($attachment_tmpl_ids_arr as $attachment_tmpl_id) {
				$attachment_ids_arr[] = eme_generate_booking_pdf( $booking, $event, $attachment_tmpl_id );
			}
		}

		if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
			$mail_res = eme_queue_fastmail( $person_subject, $person_body, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, 0, $booking['person_id'], 0, $attachment_ids_arr );
		} else {
			$mail_res = eme_queue_mail( $person_subject, $person_body, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, 0, $booking['person_id'], 0, $attachment_ids_arr );
		}
	}
	return $mail_res;
}

function eme_registration_approval_page() {
	eme_registration_seats_page( 1 );
}

function eme_registration_seats_page( $pending = 0 ) {
	global $plugin_page;

	// do the actions if required
	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'newBooking' && isset( $_GET['event_id'] ) ) {
		$event_id = intval( $_GET['event_id'] );
		check_admin_referer( "eme_admin", 'eme_admin_nonce' );
		$event = eme_get_event( $event_id );
		if ( empty( $event ) ) {
			print "<div id='message' class='error'><p>" . __( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			return;
		}
		$current_userid = get_current_user_id();
		if ( ! ( current_user_can( get_option( 'eme_cap_registrations' ) ) ||
			( current_user_can( get_option( 'eme_cap_author_registrations' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) ) {

			print "<div id='message' class='error'><p>" . __( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			return;
		}
		// we need to set the action url, otherwise the GET parameters stay and we will fall in this if-statement all over again
		$action_url  = admin_url( "admin.php?page=$plugin_page" );
		$nonce_field = wp_nonce_field( "eme_admin", 'eme_admin_nonce', false, false );
		$ret_string  = '<h1>' . __( 'Add booking', 'events-made-easy' ) . '</h1>';
		if ( get_option( 'eme_rsvp_admin_allow_overbooking' ) ) {
			$ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . __( 'Be aware: the overbooking option is set.', 'events-made-easy' ) . '</div>';
		}
		$ret_string .= "<form id='eme-rsvp-adminform' name='eme-rsvp-adminform' method='post' action='$action_url' enctype='multipart/form-data' >";
		$ret_string .= $nonce_field;
		$ret_string .= __( 'Send mails for new booking?', 'events-made-easy' ) . eme_ui_select_binary( 1, 'send_mail', 0, 'nodynamicupdates' );
		$ret_string .= '<br>';
		$new_booking = eme_new_booking();
		$ret_string .= eme_replace_rsvp_formfields_placeholders( $event, $new_booking );
		$ret_string .= "
	    <input type='hidden' name='eme_admin_action' value='addBooking'>
	    <input type='hidden' name='event_id' value='$event_id'>
	    <input type='hidden' name='person_id' value='' >
	    <input type='hidden' name='wp_id' value=''>
	    </form>";
		print $ret_string;
		return;
	} elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'editBooking' && isset( $_GET['booking_id'] ) ) {
		$booking_id = intval( $_GET['booking_id'] );
		check_admin_referer( "eme_admin", 'eme_admin_nonce' );
		$booking  = eme_get_booking( $booking_id );
		$event_id = $booking['event_id'];
		$event    = eme_get_event( $event_id );
		if ( empty( $event ) ) {
			print "<div id='message' class='error'><p>" . __( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			return;
		}
		$current_userid = get_current_user_id();
		if ( ! ( current_user_can( get_option( 'eme_cap_registrations' ) ) ||
			( current_user_can( get_option( 'eme_cap_author_registrations' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) ) {

			print "<div id='message' class='error'><p>" . __( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			return;
		}

		// we need to set the action url, otherwise the GET parameters stay and we will fall in this if-statement all over again
		$action_url  = admin_url( "admin.php?page=$plugin_page" );
		$nonce_field = wp_nonce_field( "eme_admin", 'eme_admin_nonce', false, false );
		$ret_string  = '<h1>' . __( 'Edit booking', 'events-made-easy' ) . '</h1>';
		$ret_string .= "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $booking['person_id'] ) . "' title='" . __( 'Click on this link to edit the corresponding person info', 'events-made-easy' ) . "'>" . __( 'Click on this link to edit the corresponding person info', 'events-made-easy' ) . '</a><br><br>';

		// the event id can be empty if we are editng a booking where the event has been removed
		if ( ! empty( $event['event_id'] ) ) {
			$ret_string .= "<form id='eme-rsvp-adminform' name='eme-rsvp-adminform' method='post' action='$action_url' enctype='multipart/form-data' >";
		}
		$ret_string .= $nonce_field;
		$ret_string .= '<table>';
		$ret_string .= '<tr><td>' . __( 'Send mails for changed booking?', 'events-made-easy' ) . '</td><td>' . eme_ui_select_binary( 1, 'send_mail', 0, 'nodynamicupdates' ) . '</td></tr>';
		$ret_string .= '<tr><td>' . __( 'Move booking to event', 'events-made-easy' ) . '</td><td>';
		$ret_string .= "<input type='hidden' id='transferto_id' name='transferto_id'>";
		$ret_string .= "<input type='hidden' id='person_id' name='person_id' value='" . $booking['person_id'] . "'>";
		$ret_string .= "<input type='text' id='chooseevent' name='chooseevent' class='nodynamicupdates' placeholder='" . __( 'Start typing an event name', 'events-made-easy' ) . "'>";
		$ret_string .= "&nbsp;<input id='eventsearch_all' name='eventsearch_all' value='1' type='checkbox'>" . __( 'Check this box to search through all events and not just future ones.', 'events-made-easy' ) . '</td></tr>';
		$ret_string .= '<tr><td>' . __( 'Has the booking been paid?', 'events-made-easy' ) . '</td><td>' . eme_ui_select_binary( $booking['booking_paid'], 'booking_paid', 0, 'nodynamicupdates' ) . '</td></tr>';
		$ret_string .= '</table>';
		if ( empty( $event['event_id'] ) ) {
			$ret_string .= "<br><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . __( 'Warning: the event related to this booking has been removed, editing is no longer allowed!', 'events-made-easy' ) . '<br>';
		} elseif ( $booking['event_price'] != $event['price'] ) {
			$ret_string .= "<br><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . __( 'Warning: the price of the event has changed compared to this booking, the new price will be taken into account for changes!', 'events-made-easy' ) . '<br>';
		}
		$ret_string .= eme_replace_rsvp_formfields_placeholders( $event, $booking );
		if ( ! empty( $event['event_id'] ) ) {
			$ret_string .= "
	 <input type='hidden' name='eme_admin_action' value='updateBooking'>
	 <input type='hidden' id='booking_id' name='booking_id' value='$booking_id'>
	 </form>";
		}
		//$files_title=__('Uploaded files related to this booking', 'events-made-easy');
		//$ret_string.= eme_get_uploaded_files_br($booking['booking_id'],"bookings",$files_title).'</table>';
		print $ret_string;
		return;
	} else {
		$action    = isset( $_POST ['eme_admin_action'] ) ? eme_sanitize_request($_POST ['eme_admin_action']) : '';
		$send_mail = isset( $_POST ['send_mail'] ) ? intval( $_POST ['send_mail'] ) : 1;
		if ( $action == 'addBooking' ) {
			$event_id = intval( $_POST['event_id'] );
			check_admin_referer( "eme_admin", 'eme_admin_nonce' );
			$event = eme_get_event( $event_id );
			if ( empty( $event ) ) {
				print "<div id='message' class='error'><p>" . __( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			} else {
				$booking_res = eme_book_seats( $event, $send_mail );
				$result      = $booking_res[0];
				$payment_id  = $booking_res[1];
				if ( ! $payment_id ) {
					print "<div id='message' class='error'><p>$result</p></div>";
				} else {
					print "<div id='message' class='updated notice is-dismissible'><p>$result</p></div>";
				}
			}
		} elseif ( $action == 'updateBooking' ) {
			$booking_id = intval( $_POST['booking_id'] );
			check_admin_referer( "eme_admin", 'eme_admin_nonce' );
			$transferto_id = isset( $_POST ['transferto_id'] ) ? intval( $_POST ['transferto_id'] ) : 0;
			$person_id     = isset( $_POST ['person_id'] ) ? intval( $_POST ['person_id'] ) : 0;
			$booking       = eme_get_booking( $booking_id );
			// transferto_id is only given for moving a booking to another event
			if ( $transferto_id && $booking['event_id'] != $transferto_id ) {
				$orig_event_id       = $booking['event_id'];
				$booking['event_id'] = $transferto_id;
				// move all data from the original event in $_POST to the new event id too, so other booking code (like eme_get_booking_post_answer) can work as expected with the new event id
				foreach ( $_POST as $key => $val ) {
					if ( is_array( $_POST[ $key ] ) && isset( $_POST[ $key ][ $orig_event_id ] ) ) {
						$_POST[ $key ][ $transferto_id ] = eme_sanitize_request( $_POST[ $key ][ $orig_event_id ] );
					}
				}
			} else {
				$orig_event_id = 0;
			}
			if ( ! $person_id ) {
				// this is the case where we clear the existing booker info and enter totally new booking info
				$lastname = eme_sanitize_request( $_POST['lastname'] );
				if ( isset( $_POST['firstname'] ) ) {
					$firstname = eme_sanitize_request( $_POST['firstname'] );
				} else {
					$firstname = '';
				}
				$email  = eme_sanitize_email( $_POST['email'] );
				$person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
				if ( ! $person ) {
					$person = eme_get_person_by_email_only( $email );
				}
				if ( ! $person ) {
					$person              = [];
					$person['lastname']  = $lastname;
					$person['firstname'] = $firstname;
					$person['email']     = $email;
					$person_id           = eme_db_insert_person( $person );
				} else {
					$person_id = $person['person_id'];
				}
			}
			if ( $person_id && $booking['person_id'] != $person_id ) {
				$booking['person_id'] = $person_id;
			}
			// now that the possible move is done, set the event id variable
			$event_id = $booking['event_id'];
			$event    = eme_get_event( $event_id );
			// take into account that pricing might have changed, so we use the new price
			$booking['booking_paid'] = intval( $_POST['booking_paid'] );
			if ( $booking['event_price'] != $event['price'] ) {
				// first get the current price of the booking
				$current_price = eme_get_total_booking_price( $booking );
				// now set the new event price and calculate the new booking price
				$booking['event_price'] = $event['price'];
				$new_price              = eme_get_total_booking_price( $booking );
				$price_diff             = $new_price - $current_price;
				$booking['remaining']  += $price_diff;
				if ( $booking['remaining'] <= 0 ) {
					$booking['remaining']    = 0;
					$booking['booking_paid'] = 1;
				}
			}

			if ( isset( $_POST['eme_rsvpcomment'] ) ) {
				$booking['booking_comment'] = eme_sanitize_textarea( $_POST['eme_rsvpcomment'] );
			}

			$update_message  = '';
			$enough_seats    = 1;
			$total_seats     = eme_get_total_seats( $event_id );
			// we need to check the available number of seats
			if ( eme_is_multi( $booking['event_price'] ) ) {
				// it is a multiprice event, but the max number of seats can be a single number too, we need to account for that
				if ( eme_is_multi( $event['event_seats'] ) ) {
					$available_multiseats = eme_get_available_multiseats( $event_id );
				} else {
					$available_seats = eme_get_available_seats( $event_id );
				}
				$already_booked_seats_mp = eme_convert_multi2array( $booking['booking_seats_mp'] );
				$already_booked_seats    = eme_get_total( $booking['booking_seats_mp'] );

				$booking_prices_mp = eme_convert_multi2array( $booking['event_price'] );
				$bookedSeats_mp    = [];
				// start with the existing mp bookedseats, in case a #_SEATSxx is missing (due to a eme_if condition maybe), it is then not touched
				foreach ( $booking_prices_mp as $key => $value ) {
					$bookedSeats_mp[ $key ] = intval( $already_booked_seats_mp[ $key ] );
				}
				foreach ( $_POST['bookings'][ $event_id ] as $key => $value ) {
					if ( preg_match( '/bookedSeats(\d+)/', $key, $matches ) ) {
						$field_id                    = intval( $matches[1] ) - 1;
						$bookedSeats_mp[ $field_id ] = intval( $value );
						if ( eme_is_multi( $event['event_seats'] ) && $bookedSeats_mp[ $field_id ] > $available_multiseats[ $field_id ] + intval( $already_booked_seats_mp[ $field_id ] ) ) {
							$enough_seats = 0;
						}
					}
				}

				$bookedSeats = 0;
				foreach ( $bookedSeats_mp as $val ) {
					$bookedSeats += intval( $val );
				}
				if ( ! eme_is_multi( $event['event_seats'] ) && $bookedSeats > $available_seats + $already_booked_seats ) {
					$enough_seats = 0;
				}
				// just in case the total amount of seats=0: always enough room
				if ( $total_seats == 0 ) {
					$enough_seats = 1;
				}

				$booking['booking_seats']    = $bookedSeats;
				$booking['booking_seats_mp'] = eme_convert_array2multi( $bookedSeats_mp );
			} else {
				if ( isset( $_POST['bookings'][ $event_id ]['bookedSeats'] ) ) {
					$bookedSeats = intval( $_POST['bookings'][ $event_id ]['bookedSeats'] );
				} else {
					$bookedSeats = 0;
				}
				$booking['booking_seats'] = $bookedSeats;

				if ( $total_seats == 0 ) {
					$enough_seats = 1;
				} else {
					$available_seats      = eme_get_available_seats( $event_id );
					$already_booked_seats = $booking['booking_seats'];

					// if we want to increase the number of booked seats for this booking, check the available total
					if ( $bookedSeats > $available_seats + $already_booked_seats ) {
						$enough_seats = 0;
					}
				}
			}
			// now do the update
			if ( $enough_seats || get_option( 'eme_rsvp_admin_allow_overbooking' ) ) {
				eme_db_update_booking( $booking );
				eme_manage_waitinglist( $event, $send_mail );
				// for transferred bookings, check the original event also for waitinglist updates
				if ( $orig_event_id > 0 ) {
					$orig_event = eme_get_event( $orig_event_id );
					if ( ! empty( $orig_event ) ) {
						eme_manage_waitinglist( $orig_event, $send_mail );
					}
				}
				$update_message  = __( 'Booking updated', 'events-made-easy' );
				$upload_failures = eme_upload_files( $booking_id, 'bookings' );
				if ( $upload_failures ) {
					$update_message .= $upload_failures;
				}
				$res = eme_add_update_person_from_form( $booking['person_id'] );
				if ( ! $res[0] ) {
					$update_message .= $res[1];
				}
				// now get the changed booking and send mail if wanted
				$booking = eme_get_booking( $booking_id );
				print "<div id='message' class='updated notice is-dismissible'><p>" . $update_message . '</p></div>';
				if ( $send_mail ) {
					$mail_res = eme_email_booking_action( $booking, $action );
					if ( ! $mail_res ) {
						print "<div id='mailmessage' class='error notice is-dismissible'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
					}
				}
			} else {
				$update_message = __( 'During the time of your change, some free seats were taken leaving not enough free seats available anymore', 'events-made-easy' );
				print "<div id='message' class='error notice is-dismissible'><p>" . $update_message . '</p></div>';
			}
		} elseif ( $action == 'import_payments' && isset( $_FILES['eme_csv'] ) ) {
			// eme_cap_cleanup is used for cleanup, cron and imports (should more be something like 'eme_cap_actions')
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$message = eme_import_csv_payments();
			print "<div id='message' class='updated notice is-dismissible'><p>" . $message . '</p></div>';
		}
	}

	// now show the menu
	if ( isset( $_GET['trash'] ) && $_GET['trash'] == 1 ) {
		$trash = 1;
	} else {
		$trash = 0;
	}
	eme_registration_seats_form_table( $pending, $trash );
}

function eme_import_csv_payments() {
	global $wpdb;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
		$today        = $eme_date_obj_now->getDate();

	//validate whether uploaded file is a csv file
	$csvMimes = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];
	if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
		return sprintf( __( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
	}
	if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
		return __( 'Problem detected while uploading the file', 'events-made-easy' );
		return $result;
	}
	$updated     = 0;
	$ignored     = 0;
	$errors      = 0;
	$error_msg   = '';
	$ignored_msg = '';
	$handle      = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
	if ( ! $handle ) {
		return __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
	}
	// BOM as a string for comparison.
		$bom = "\xef\xbb\xbf";
		// Progress file pointer and get first 3 characters to compare to the BOM string.
	if ( fgets( $handle, 4 ) !== $bom ) {
		// BOM not found - rewind pointer to start of file.
		rewind( $handle );
	}

	if ( ! eme_is_empty_string( $_POST['enclosure'] ) ) {
		$enclosure = eme_sanitize_request( $_POST['enclosure'] );
		$enclosure = substr( $enclosure, 0, 1 );
	} else {
		$enclosure = '"';
	}
	if ( ! eme_is_empty_string( $_POST['delimiter'] ) ) {
		$delimiter = eme_sanitize_request( $_POST['delimiter'] );
	} else {
		$delimiter = ',';
	}

	// get the first row as keys and lowercase them
	$headers = array_map( 'strtolower', fgetcsv( $handle, 0, $delimiter, $enclosure ) );

	// check required columns
	if ( ! in_array( 'payment_date', $headers ) || ! ( in_array( 'unique_nbr', $headers ) || in_array( 'payment_id', $headers ) || in_array( 'payment_randomid', $headers ) ) || ! in_array( 'amount', $headers ) ) {
		$result = __( 'Not all required fields present.', 'events-made-easy' );
	} else {
		$empty_props = eme_init_event_props( );
		// now loop over the rest
		while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
			$line = array_combine( $headers, $row );
			// remove columns with empty values
			$line                = eme_array_remove_empty_elements( $line );
			$amount              = eme_int_price( $line['amount'] );
			$payment_date_parsed = date_parse( $line['payment_date'] );
			if ( ! empty( $payment_date_parsed['year'] ) && ! empty( $payment_date_parsed['month'] ) && ! empty( $payment_date_parsed['day'] ) ) {
				$payment_date = $payment_date_parsed['year'] . '-' . sprintf( '%02d', $payment_date_parsed['month'] ) . '-' . sprintf( '%02d', $payment_date_parsed['day'] );
			} else {
				$payment_date = $today;
			}

			$payment_id = 0;
			if ( ! empty( $line['payment_id'] ) ) {
				$payment_id = intval( $line['payment_id'] );
				$payment    = eme_get_payment( $payment_id );
				if ( empty( $payment ) ) {
					$payment_id = 0;
				}
			} elseif ( ! empty( $line['payment_randomid'] ) ) {
				$payment_randomid = intval( $line['payment_randomid'] );
				$payment          = eme_get_payment( payment_randomid: $payment_randomid );
				if ( ! empty( $payment ) ) {
					$payment_id = $payment['id'];
				}
			} elseif ( ! empty( $line['unique_nbr'] ) ) {
				$unique_nbr = sprintf( '%012d', eme_str_numbers_only( $line['unique_nbr'] ) );
				if ( eme_unique_nbr_check( $unique_nbr ) ) {
					$sql        = $wpdb->prepare( "SELECT payment_id FROM $bookings_table WHERE unique_nbr=%s LIMIT 1", $unique_nbr );
					$payment_id = $wpdb->get_var( $sql );
				}
			}

			if ( empty( $payment_id ) ) {
				++$ignored;
				$ignored_msg .= '<br>' . eme_esc_html( sprintf( __( 'No linked payment found: %s', 'events-made-easy' ), implode( $delimiter, $row ) ) );
			} elseif ( ! eme_is_date( $payment_date ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Field %s not valid: %s', 'events-made-easy' ), 'payment_date', implode( $delimiter, $row ) ) );
			} elseif ( empty( $amount ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Field %s not valid: %s', 'events-made-easy' ), 'amount', implode( $delimiter, $row ) ) );
			} else {
				$to_pay = eme_get_payment_price( $payment_id );
				if ( $to_pay == 0 ) {
					++$ignored;
					$ignored_msg .= '<br>' . eme_esc_html( sprintf( __( 'Already paid in full: %s', 'events-made-easy' ), implode( $delimiter, $row ) ) );
				} elseif ( $to_pay == $amount ) {
					++$updated;
					eme_mark_payment_paid( $payment_id, 0 );
				} else {
					++$errors;
					$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Amount paid (%s) is not equal to the expected amount (%s): %s', 'events-made-easy' ), $amount, $to_pay, implode( $delimiter, $row ) ) );
				}
			}
		}
		$result = sprintf( __( 'Import finished: %d entries marked as paid, %d ignored, %d errors', 'events-made-easy' ), $updated, $ignored, $errors );
		if ( $ignored ) {
			$result .= '<br><br>' . __( 'Ignored entries', 'events-made-easy' ) . '<br>' . $ignored_msg;
		}
		if ( $errors ) {
			$result .= '<br><br>' . __( 'Erronous entries', 'events-made-easy' ) . '<br>' . $error_msg;
		}
	}
	fclose( $handle );
	return $result;
}

function eme_registration_seats_form_table( $pending = 0, $trash = 0 ) {
	global $plugin_page;

	$scope_names           = [];
	$scope_names['past']   = __( 'Past events', 'events-made-easy' );
	$scope_names['all']    = __( 'All events', 'events-made-easy' );
	$scope_names['future'] = __( 'Future events', 'events-made-easy' );

	$categories = eme_get_categories();

	$pdftemplates     = eme_get_templates( 'pdf', 1 );
	$htmltemplates    = eme_get_templates( 'html', 1 );
	$rsvptemplates    = eme_get_templates( 'rsvpmail' );
	$mailing_pending  = get_option( 'eme_rsvp_mail_notify_pending' );
	$mailing_approved = get_option( 'eme_rsvp_mail_notify_approved' );

	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	?>
<div class="wrap">
<div id="icon-events" class="icon32">
</div>
<h1>
	<?php
	if ( isset( $_GET['person_id'] ) ) {
		$person_id = intval( $_GET['person_id'] );
	} else {
		$person_id = 0;
	}
	if ( isset( $_GET['event_id'] ) ) {
		$event_id = intval( $_GET['event_id'] );
		$event    = eme_get_event( $event_id );
		if ( empty( $event ) ) {
			esc_html_e( 'No such event', 'events-made-easy' );
			return;
		} else {
			$event_q_string = '&event_id=' . intval( $_GET['event_id'] );
			if ( $pending ) {
				printf( __( 'Manage pending bookings for %s', 'events-made-easy' ), eme_translate( $event['event_name'] ) );
			} else {
				printf( __( 'Manage approved bookings for %s', 'events-made-easy' ), eme_translate( $event['event_name'] ) );
			}
		}
	} else {
		$event_q_string = '';
		$event_id       = 0;
		if ( $trash ) {
			esc_html_e( 'Manage trashed bookings', 'events-made-easy' );
		} elseif ( $pending ) {
			esc_html_e( 'Manage pending bookings', 'events-made-easy' );
		} else {
			esc_html_e( 'Manage approved bookings', 'events-made-easy' );
		}
	}
	?>
</h1>
	<?php if ( $trash ) { ?>
		<a href="<?php echo admin_url( "admin.php?page=$plugin_page&trash=0$event_q_string" ); ?>"><?php esc_html_e( 'Show regular content', 'events-made-easy' ); ?></a><br>
	<?php } else { ?>
		<a href="<?php echo admin_url( "admin.php?page=$plugin_page&trash=1$event_q_string" ); ?>"><?php esc_html_e( 'Show trash content', 'events-made-easy' ); ?></a><br>
		<div id="bookings-message" style="display: none;"></div>
		<span class="eme_import_form_img">
		<?php esc_html_e( 'Click on the icon to show the import form to import payments', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_import" style="cursor: pointer; vertical-align: middle; ">
		</span>
		<div id='div_import' style='display:none;'>
		<form id='payment-import' method='post' enctype='multipart/form-data' action='#'>
		<?php echo $nonce_field; ?>
		<input type="file" name="eme_csv">
		<?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?> 
		<input type="text" size=1 maxlength=1 name="delimiter" value=',' required='required'>
		<?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
		<input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"' required='required'>
		<input type="hidden" name="eme_admin_action" value="import_payments">
		<input type="submit" value="<?php esc_html_e( 'Import', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
		<?php esc_html_e( 'If you want, use this to import booking payments', 'events-made-easy' ); ?>
		</form>
		</div>
	<?php } ?>

	<div>
	<form id="eme-admin-regsearchform" name="eme-admin-regsearchform" action="#" method="post">

	<?php
	// this is used in the javascript
	if ( $pending ) {
		echo '<input type="hidden" id="booking_status" name="booking_status" value="PENDING">';
	} else {
		echo '<input type="hidden" id="booking_status" name="booking_status" value="APPROVED">';
	}

	echo "<input type='hidden' name='event_id' id='event_id' value='$event_id'>";
	if ( ! $event_id && ! $person_id ) {
		// if eitherr event id or person id is passed via GET, we ignore the scope, so let's hide the selection too
		?>
		<select id='scope' name='scope'>
		<?php
		foreach ( $scope_names as $key => $value ) {
			$selected = '';
			if ( $key == 'future' ) {
				$selected = "selected='selected'";
			}
			echo "<option value='".esc_attr($key)."' $selected>".esc_html($value)."</option>  ";
		}
		?>
		</select>
		<select id="category" name="category">
		<option value='0'><?php esc_html_e( 'All categories', 'events-made-easy' ); ?></option>
		<option value='none'><?php esc_html_e( 'Events without category', 'events-made-easy' ); ?></option>
		<?php
		foreach ( $categories as $category ) {
			echo "<option value='" . esc_attr($category['category_id']) . "'>" . esc_html($category['category_name']) . '</option>';
		}
		?>
		</select>

		<input type="text" name="search_event" id="search_event" placeholder="<?php esc_attr_e( 'Filter on event', 'events-made-easy' ); ?>" size=15>
		<input id="eme_localized_search_start_date" type="text" name="eme_localized_search_start_date" value="" style="background: #FCFFAA;" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on start date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_start_date' class='eme_formfield_fdate'>
		<input id="search_start_date" type="hidden" name="search_start_date" value="">
		<input id="eme_localized_search_end_date" type="text" name="eme_localized_search_end_date" value="" style="background: #FCFFAA;" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on end date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_end_date' class='eme_formfield_fdate'>
		<input id="search_end_date" type="hidden" name="search_end_date" value="">
		<?php
	}
	?>
	<a onclick='return false;' href='#'  class="showhidebutton" alt="show/hide" data-showhide="extra_searchfields"><?php esc_html_e( 'Show/hide extra filters', 'events-made-easy' ); ?></a>
	<div id="extra_searchfields" style="display:none;">
	<?php if ( ! $person_id ) : ?>
	<input type="text" name="search_person" id="search_person" placeholder="<?php esc_attr_e( 'Filter on person', 'events-made-easy' ); ?>" size=15>
<?php endif; ?>
	<input type="text" name="search_customfields" id="search_customfields" placeholder="<?php esc_attr_e( 'Filter on custom field answer', 'events-made-easy' ); ?>" size=20>
	<input type="text" name="search_unique" id="search_unique" placeholder="<?php esc_attr_e( 'Filter on unique nbr', 'events-made-easy' ); ?>" size=15>
	<input type="text" name="search_paymentid" id="search_paymentid" placeholder="<?php esc_attr_e( 'Filter on payment id', 'events-made-easy' ); ?>" <?php if (isset($_GET['paymentid'])) esc_attr_e(intval($_GET['paymentid'])); else echo ''; ?> size=15>
	<input type="text" name="search_pg_pid" id="search_pg_pid" placeholder="<?php esc_attr_e( 'Filter on payment GW id', 'events-made-easy' ); ?>" size=15>
	</div>
	<button id="BookingsLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter bookings', 'events-made-easy' ); ?></button>
	</form>
	</div>
	<div>
	<form id="eme-admin-regform" name="eme-admin-regform" action="#" method="post">
	<select name="eme_admin_action" id="eme_admin_action">
	<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
	<?php if ( $trash ) { ?>
	<option value="deleteBooking"><?php esc_html_e( 'Permanently delete booking', 'events-made-easy' ); ?></option>
	<option value="userConfirmBooking"><?php esc_html_e( 'Restore booking and mark pending and awaiting user confirmation', 'events-made-easy' ); ?></option>
	<option value="pendingBooking"><?php esc_html_e( 'Restore booking and mark pending', 'events-made-easy' ); ?></option>
	<option value="approveBooking"><?php esc_html_e( 'Restore booking and mark approved', 'events-made-easy' ); ?></option>
<?php } elseif ( $pending ) { ?>
	<option value="approveBooking"><?php esc_html_e( 'Approve booking', 'events-made-easy' ); ?></option>
	<option value="trashBooking"><?php esc_html_e( 'Delete booking (move to trash)', 'events-made-easy' ); ?></option>
		<?php if ( $mailing_pending ) { ?>
		<option value="resendPendingBooking"><?php esc_html_e( 'Resend the mail for pending booking', 'events-made-easy' ); ?></option>
	<?php } ?>
	<option value="markPaid"><?php esc_html_e( 'Mark paid', 'events-made-easy' ); ?></option>
	<option value="markUnpaid"><?php esc_html_e( 'Mark unpaid', 'events-made-easy' ); ?></option>
	<option value="partialPayment"><?php esc_html_e( 'Partial payment', 'events-made-easy' ); ?></option>
	<option value="unsetwaitinglistBooking"><?php esc_html_e( 'Move booking off the waitinglist', 'events-made-easy' ); ?></option>
	<option value="setwaitinglistBooking"><?php esc_html_e( 'Put booking on the waitinglist', 'events-made-easy' ); ?></option>
	<option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
	<option value="rsvpMails"><?php esc_html_e( 'Send booking related email to selected bookings', 'events-made-easy' ); ?></option>
	<option value="pdf"><?php esc_html_e( 'PDF output', 'events-made-easy' ); ?></option>
	<option value="html"><?php esc_html_e( 'HTML output', 'events-made-easy' ); ?></option>
<?php } else { ?>
	<option value="pendingBooking"><?php esc_html_e( 'Make booking pending', 'events-made-easy' ); ?></option>
	<option value="trashBooking"><?php esc_html_e( 'Delete booking (move to trash)', 'events-made-easy' ); ?></option>
	<?php if ( $mailing_approved ) { ?>
		<option value="resendApprovedBooking"><?php esc_html_e( 'Resend the mail for approved booking', 'events-made-easy' ); ?></option>
	<?php } ?>
	<option value="markPaid"><?php esc_html_e( 'Mark paid', 'events-made-easy' ); ?></option>
	<option value="markUnpaid"><?php esc_html_e( 'Mark unpaid', 'events-made-easy' ); ?></option>
	<option value="partialPayment"><?php esc_html_e( 'Partial payment', 'events-made-easy' ); ?></option>
	<option value="setwaitinglistBooking"><?php esc_html_e( 'Put booking on the waitinglist', 'events-made-easy' ); ?></option>
	<option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
	<option value="rsvpMails"><?php esc_html_e( 'Send booking related email to selected bookings', 'events-made-easy' ); ?></option>
	<option value="pdf"><?php esc_html_e( 'PDF output', 'events-made-easy' ); ?></option>
	<option value="html"><?php esc_html_e( 'HTML output', 'events-made-easy' ); ?></option>
<?php } ?>
	</select>
	<span id="span_sendtocontact" class="eme-hidden">
	<?php
	esc_html_e( 'Send mails to contact person too?', 'events-made-easy' );
	echo eme_ui_select_binary( 0, 'send_to_contact_too' );
	?>
	</span>
	<span id="span_sendmails" class="eme-hidden">
	<?php
	esc_html_e( 'Send mails to attendees upon changes being made?', 'events-made-easy' );
	echo eme_ui_select_binary( 1, 'send_mail' );
	?>
	</span>
	<?php if ( get_option( 'eme_payment_refund_ok' ) ) : ?>
	<span id="span_refund" class="eme-hidden">
		<?php
		esc_html_e( 'Refund if possible?', 'events-made-easy' );
		echo eme_ui_select_binary( 0, 'refund' );
		?>
	</span>
	<?php endif; ?>
	<span id="span_partialpayment" class="eme-hidden">
	<?php
	esc_html_e( 'Partial payment amount', 'events-made-easy' );
	$label = eme_esc_html('Partial payment amount', 'events-made-easy' );
	echo eme_ui_number( 0, 'partial_amount', 0, '', 'aria-label="' . $label . '"' );
	?>
	</span>
	<span id="span_rsvpmailtemplate" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'rsvpmail_template_subject', $rsvptemplates, 'id', 'name', __( 'Select a subject template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'rsvpmail_template', $rsvptemplates, 'id', 'name', __( 'Please select a body template', 'events-made-easy' ), 1 ); ?>
	</span>
	<span id="span_pdftemplate" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'pdf_template_header', $pdftemplates, 'id', 'name', __( 'Select an optional header template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'pdf_template', $pdftemplates, 'id', 'name', __( 'Please select a template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'pdf_template_footer', $pdftemplates, 'id', 'name', __( 'Select an optional footer template', 'events-made-easy' ), 1 ); ?>
	</span>
	<span id="span_htmltemplate" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'html_template_header', $htmltemplates, 'id', 'name', __( 'Select an optional header template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'html_template', $htmltemplates, 'id', 'name', __( 'Please select a template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'html_template_footer', $htmltemplates, 'id', 'name', __( 'Select an optional footer template', 'events-made-easy' ), 1 ); ?>
	</span>
	<button id="BookingsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
	<span class="rightclickhint">
		<?php esc_html_e( 'Hint: rightclick on the column headers to show/hide columns', 'events-made-easy' ); ?>
	</span>
	</form>
	</div>
	<?php
	$formfields               = eme_get_formfields( '', 'rsvp,generic' );
	$extrafields_arr          = [];
	$extrafieldnames_arr      = [];
	$extrafieldsearchable_arr = [];
	foreach ( $formfields as $formfield ) {
		$extrafields_arr[]          = $formfield['field_id'];
		$extrafieldnames_arr[]      = eme_trans_esc_html( $formfield['field_name'] );
		$extrafieldsearchable_arr[] = $formfield['searchable'];
	}
	// add the formfields of events as last
	// first a separator (will be used in the js)
	$formfields               = eme_get_formfields( '', 'events' );
	if (!empty($formfields)) {
		$extrafields_arr[]          = 'SEPARATOR';
		$extrafieldnames_arr[]      = '<b>'.__('Event fields','events-made-easy').'</b>';
		$extrafieldsearchable_arr[] = 0;
		foreach ( $formfields as $formfield ) {
			$extrafields_arr[]          = $formfield['field_id'];
			$extrafieldnames_arr[]      = eme_trans_esc_html( $formfield['field_name'] );
			$extrafieldsearchable_arr[] = $formfield['searchable'];
		}
	}
	// these 2 values are used as data-fields to the container-div, and are used by the js to create extra columns
	$extrafields          = join( ',', $extrafields_arr );
	$extrafieldnames      = join( ',', $extrafieldnames_arr );
	$extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
	?>
	<div id="BookingsTableContainer" data-extrafields='<?php echo $extrafields; ?>' data-extrafieldnames='<?php echo $extrafieldnames; ?>' data-extrafieldsearchable='<?php echo $extrafieldsearchable; ?>'></div>

</div>
	<?php
}

// template function
function eme_is_event_rsvpable() {
	$rsvp_is_active = get_option( 'eme_rsvp_enabled' );
	if ( eme_is_single_event_page() && isset( $_REQUEST['event_id'] ) ) {
		$event = eme_get_event( $_REQUEST['event_id'] );
		if ( ! empty( $event ) && $rsvp_is_active ) {
			return $event['event_rsvp'];
		}
	}
	return 0;
}

function eme_event_needs_approval( $event_id ) {
	global $wpdb;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$sql          = $wpdb->prepare( "SELECT registration_requires_approval from $events_table where event_id=%d", $event_id );
	return $wpdb->get_var( $sql );
}

// the next function returns the price for 1 booking, not taking into account the number of seats or anything
function eme_get_booking_event_price( $booking ) {
	return $booking['event_price'];
}

// the next function returns the price for a specific booking, multiplied by the number of seats booked and multiprice taken into account
function eme_get_total_booking_price( $booking, $ignore_extras = 0 ) {
	$basic_price = eme_get_booking_event_price( $booking );

	if ( eme_is_multi( $basic_price ) ) {
		$price = array_sum( eme_get_total_booking_multiprice_arr( $booking ) );
	} else {
		$price = $basic_price * $booking['booking_seats'];
	}
	if ( ! $ignore_extras ) {
		if ( ! empty( $booking['extra_charge'] ) ) {
			$price += $booking['extra_charge'];
		}
		if ( ! empty( $booking['discount'] ) ) {
			$price -= $booking['discount'];
		}
	}
	if ( $price < 0 ) {
		$price = 0;
	}
	return $price;
}

function eme_bookings_total_booking_seats( $bookings ) {
	$seats = 0;
	foreach ( $bookings as $booking ) {
		$seats += $booking['booking_seats'];
	}
	return $seats;
}

function eme_get_seat_booking_price( $booking ) {
	return eme_get_total_booking_price( $booking ) / $booking['booking_seats'];
}

function eme_get_total_booking_multiprice_arr( $booking ) {
	$price       = [];
	$basic_price = eme_get_booking_event_price( $booking );

	if ( eme_is_multi( $basic_price ) ) {
		$prices = eme_convert_multi2array( $basic_price );
		$seats  = eme_convert_multi2array( $booking['booking_seats_mp'] );
		foreach ( $prices as $key => $val ) {
			$price[] = floatval( $val ) * floatval( $seats[ $key ] );
		}
	}
	return $price;
}

function eme_get_seat_booking_multiprice_arr( $booking ) {
	$price       = [];
	$basic_price = eme_get_booking_event_price( $booking );

	if ( eme_is_multi( $basic_price ) ) {
		$price = eme_convert_multi2array( $basic_price );
	}
	return $price;
}

function eme_is_event_rsvp( $event ) {
	$rsvp_is_active = get_option( 'eme_rsvp_enabled' );
	if ( $rsvp_is_active && $event['event_id'] && $event['event_rsvp'] ) {
		return 1;
	} else {
		return 0;
	}
}

// unused function ...
function eme_is_event_multiprice( $event_id ) {
	$event = eme_get_event( $event_id );
	if ( ! empty( $event ) ) {
		return eme_is_multi( $event['price'] );
	} else {
		return false;
	}
}

function eme_is_event_multiseats( $event_id ) {
	$event = eme_get_event( $event_id );
	if ( ! empty( $event ) ) {
		return eme_is_multi( $event['event_seats'] );
	} else {
		return false;
	}
}

function eme_event_rsvp_status( $event ) {
	// this functions returns 0 if rsvp is not started yet, 1 if rsvp is allowed and ongoing, 2 if rsvp is ended
	if ( ! eme_is_event_rsvp( $event ) ) {
		return 0;
	}

	$event_rsvp_startdatetime = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
	$event_rsvp_enddatetime   = new ExpressiveDate( $event['event_end'], EME_TIMEZONE );
	if ( $event['event_properties']['rsvp_start_target'] == 'end' ) {
		$event_rsvp_start = $event_rsvp_enddatetime->copy();
	} else {
		$event_rsvp_start = $event_rsvp_startdatetime->copy();
	}
	if ( $event['event_properties']['rsvp_end_target'] == 'start' ) {
		$event_rsvp_end = $event_rsvp_startdatetime->copy();
	} else {
		$event_rsvp_end = $event_rsvp_enddatetime->copy();
	}

	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	// allow rsvp from rsvp_start_number_days:rsvp_start_number_hours before the event starts/ends (rsvp_start_target)
	if ( $event['event_properties']['rsvp_start_number_days'] > 0 || $event['event_properties']['rsvp_start_number_hours'] > 0 ) {
		if ( $event_rsvp_start->minusDays( $event['event_properties']['rsvp_start_number_days'] )->minusHours( $event['event_properties']['rsvp_start_number_hours'] ) > $eme_date_obj_now ) {
			return 0;
		}
	}
	// allow rsvp until rsvp_end_number_days:rsvp_end_number_hours before the event starts/ends (rsvp_end_target)
	if ( $event_rsvp_end->minusDays( $event['event_properties']['rsvp_end_number_days'] )->minusHours( $event['event_properties']['rsvp_end_number_hours'] ) < $eme_date_obj_now ) {
		return 2;
	}
	// in all other cases: return ok
	return 1;
}

function eme_is_event_rsvp_started( $event ) {
	$eme_event_rsvp_status = eme_event_rsvp_status( $event );
	if ( $eme_event_rsvp_status == 1 ) {
		return 1;
	} else {
		return 0;
	}
}

function eme_is_event_rsvp_ended( $event ) {
	$eme_event_rsvp_status = eme_event_rsvp_status( $event );
	if ( $eme_event_rsvp_status == 2 ) {
		return 1;
	} else {
		return 0;
	}
}

function eme_is_event_cancelrsvp_ended( $event ) {
	$eme_date_obj_now     = new ExpressiveDate( 'now', EME_TIMEZONE );
	$cancel_cutofftime    = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
	$cancel_cutofftime->minusDays( $event['event_properties']['cancel_rsvp_days'] );
	if ( eme_is_event_rsvp_ended( $event ) || $cancel_cutofftime < $eme_date_obj_now ) {
			return 1;
	} else {
		return 0;
	}
}

add_action( 'wp_ajax_eme_bookings_list', 'eme_ajax_bookings_list' );
add_action( 'wp_ajax_eme_manage_bookings', 'eme_ajax_manage_bookings' );

function eme_ajax_bookings_list() {
	global $wpdb;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! (
	    current_user_can( get_option( 'eme_cap_registrations' ) ) ||
		current_user_can( get_option( 'eme_cap_author_registrations' ) ) ||
		current_user_can( get_option( 'eme_cap_approve' ) ) ||
		current_user_can( get_option( 'eme_cap_author_approve' ) )
	) ) {
		wp_die();
	}

	$bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$events_table   = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$people_table   = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$answers_table  = EME_DB_PREFIX . EME_ANSWERS_TBNAME;

	$jtStartIndex   = ( isset( $_REQUEST['jtStartIndex'] ) ) ? intval( $_REQUEST['jtStartIndex'] ) : 0;
	$jtPageSize     = ( isset( $_REQUEST['jtPageSize'] ) ) ? intval( $_REQUEST['jtPageSize'] ) : 10;
	$jtSorting      = ( ! empty( $_REQUEST['jtSorting'] ) && ! empty( eme_sanitize_sql_orderby( $_REQUEST['jtSorting'] ) ) ) ? esc_sql(eme_sanitize_sql_orderby( $_REQUEST['jtSorting']) ) : 'creation_date ASC';
	$booking_status = ( isset( $_REQUEST['booking_status'] ) ) ? eme_sanitize_request( $_REQUEST['booking_status'] ) : 'APPROVED';
	$search_event   = isset( $_REQUEST['search_event'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request($_REQUEST['search_event']) ) ) : '';
	$search_person  = isset( $_REQUEST['search_person'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request($_REQUEST['search_person']) ) ) : '';
	// the unique number can contain text (+, /, ...), but we only need the numbers, so lets do that
	$search_unique     = isset( $_REQUEST['search_unique'] ) ? esc_sql(eme_str_numbers_only( $_REQUEST['search_unique']) ) : '';
	$search_paymentid  = isset( $_REQUEST['search_paymentid'] ) ? intval( $_REQUEST['search_paymentid'] ) : 0;
	$search_pg_pid     = isset( $_REQUEST['search_pg_pid'] ) ? esc_sql( eme_sanitize_request($_REQUEST['search_pg_pid']) ) : '';
	$search_start_date = isset( $_REQUEST['search_start_date'] ) && eme_is_date( $_REQUEST['search_start_date'] ) ? esc_sql( eme_sanitize_request($_REQUEST['search_start_date']) ) : '';
	$search_end_date   = isset( $_REQUEST['search_end_date'] ) && eme_is_date( $_REQUEST['search_end_date'] ) ? esc_sql( eme_sanitize_request($_REQUEST['search_end_date']) ) : '';
	$scope             = ( isset( $_REQUEST['scope'] ) ) ? esc_sql( eme_sanitize_request( $_REQUEST['scope'] ) ) : 'future';
	$category          = isset( $_REQUEST['category'] ) ? esc_sql( eme_sanitize_request( $_REQUEST['category'] ) ) : '';
	$person_id         = isset( $_REQUEST['person_id'] ) ? intval( $_REQUEST['person_id'] ) : 0;
	$event_id          = isset( $_REQUEST['event_id'] ) ? intval( $_REQUEST['event_id'] ) : 0;
	if ( isset( $_REQUEST['trash'] ) && $_REQUEST['trash'] == 1 ) {
				$trash = 1;
	} else {
			$trash = 0;
	}

	// The toolbar search input
	$q         = isset( $_REQUEST['q'] ) ? eme_sanitize_request($_REQUEST['q']) : '';
	$opt       = isset( $_REQUEST['opt'] ) ? eme_sanitize_request($_REQUEST['opt']) : '';
	$where     = '';
	$where_arr = [];

	if ( $booking_status == 'PENDING' ) {
		if ( ! ( current_user_can( get_option( 'eme_cap_approve' ) ) ||
			current_user_can( get_option( 'eme_cap_author_approve' ) ) ) ) {
			$jTableResult['Result']  = 'Error';
			$jTableResult['Message'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $jTableResult );
			wp_die();
		}
		// in case the person only has author rights
		if ( ! ( current_user_can( get_option( 'eme_cap_approve' ) ) ) ) {
			$current_userid = get_current_user_id();
			$event_ids_arr  = eme_get_eventids_by_author( $current_userid, $scope, $event_id );
			if ( empty( $event_ids_arr ) ) {
				$jTableResult['Result']           = 'OK';
				$jTableResult['TotalRecordCount'] = 0;
				$jTableResult['Records']          = [];
				print wp_json_encode( $jTableResult );
				wp_die();
			} else {
				$where_arr[] = '(bookings.event_id IN (' . join( ',', $event_ids_arr ) . '))';
			}
		}
	} elseif ( $booking_status == 'APPROVED' ) {
		if ( ! ( current_user_can( get_option( 'eme_cap_registrations' ) ) ||
			current_user_can( get_option( 'eme_cap_author_registrations' ) ) ) ) {
			$jTableResult['Result']  = 'Error';
			$jTableResult['Message'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $jTableResult );
			wp_die();
		}
		// in case the person only has author rights
		if ( ! ( current_user_can( get_option( 'eme_cap_registrations' ) ) ) ) {
			$current_userid = get_current_user_id();
			$event_ids_arr  = eme_get_eventids_by_author( $current_userid, $scope, $event_id );
			if ( empty( $event_ids_arr ) ) {
				$jTableResult['Result']           = 'OK';
				$jTableResult['TotalRecordCount'] = 0;
				$jTableResult['Records']          = [];
				print wp_json_encode( $jTableResult );
				wp_die();
			} else {
				$where_arr[] = '(bookings.event_id IN (' . join( ',', $event_ids_arr ) . '))';
			}
		}
	}

	// we need this GROUP_CONCAT so we can sort on those fields too (otherwise the columns FIELD_* don't exist in the returning sql
	$formfields_searchable = eme_get_searchable_formfields( 'rsvp', 1 );
	$group_concat_sql      = '';
	$field_ids_arr         = [];
	foreach ( $formfields_searchable as $formfield ) {
		$field_id          = $formfield['field_id'];
		$field_ids_arr[]   = $field_id;
		$group_concat_sql .= "GROUP_CONCAT(CASE WHEN field_id = $field_id THEN answer END) AS 'FIELD_$field_id',";
	}
	if ( ! empty( $group_concat_sql ) ) {
		$sql_join = "
		   LEFT JOIN (SELECT $group_concat_sql related_id FROM $answers_table
			 WHERE related_id>0 AND type='booking'
			 GROUP BY related_id
			) ans
		   ON bookings.booking_id=ans.related_id";
	} else {
		$sql_join = '';
	}

	if ( $trash ) {
		$where_arr[] = 'bookings.status=' . EME_RSVP_STATUS_TRASH;
	} elseif ( $booking_status == 'APPROVED' ) {
		$where_arr[] = 'bookings.status=' . EME_RSVP_STATUS_APPROVED;
	} elseif ( $booking_status == 'PENDING' ) {
		$where_arr[] = '(bookings.status=' . EME_RSVP_STATUS_PENDING . ' OR bookings.status=' . EME_RSVP_STATUS_USERPENDING . ')';
	}

	if ( $q ) {
		for ( $i = 0; $i < count( $opt ); $i++ ) {
			$fld = esc_sql( $opt[ $i ] );
			if ( $fld == 'booker' ) {
				$where_arr[] = "(lastname like '%" . esc_sql( $wpdb->esc_like( $q[ $i ] ) ) . "%' OR firstname '%" . esc_sql( $wpdb->esc_like( $q[ $i ] ) ) . "%')";
			} else {
				$where_arr[] = "`$fld` like '%" . esc_sql( $wpdb->esc_like( $q[ $i ] ) ) . "%'";
			}
		}
	}
	if ( ! empty( $_REQUEST['search_customfields'] ) ) {
		$search_customfields = $wpdb->esc_like( eme_sanitize_request($_REQUEST['search_customfields']) );
		$sql                 = $wpdb->prepare("SELECT related_id FROM $answers_table WHERE answer LIKE %s AND type='booking' GROUP BY related_id", "%$search_customfields%");
		$booking_ids         = $wpdb->get_col( $sql );
		if ( ! empty( $booking_ids ) ) {
			$where_arr[] = '(bookings.booking_id IN (' . join( ',', $booking_ids ) . '))';
		}
	}

	// match some non-existing column names in the ajax for sorting to good ones
	$jtSorting = str_replace( 'booker ASC', 'lastname ASC, firstname ASC', $jtSorting );
	$jtSorting = str_replace( 'booker DESC', 'lastname DESC, firstname DESC', $jtSorting );
	$jtSorting = str_replace( 'datetime ASC', 'event_start ASC', $jtSorting );
	$jtSorting = str_replace( 'datetime DESC', 'event_start DESC', $jtSorting );

	$eme_date_obj_reminder = new ExpressiveDate( 'now', EME_TIMEZONE );
	$eme_date_obj_now      = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today                 = $eme_date_obj_now->getDateTime();
	if ( ! empty( $search_person ) ) {
		$where_arr[] = "(lastname like '%$search_person%' OR firstname like '%$search_person%' OR email like '%$search_person%')";
	}
	if ( ! empty( $search_unique ) ) {
		$where_arr[] = "unique_nbr like '%$search_unique%'";
		// for this search, don't limit the scope
		$scope = 'all';
	}
	if ( ! empty( $search_paymentid ) ) {
		$where_arr[] = "payment_id=$search_paymentid";
		// for this search, don't limit the scope
		$scope = 'all';
	}
	if ( ! empty( $search_pg_pid ) ) {
		$where_arr[] = "pg_pid like '%$search_pg_pid%'";
		// for this search, don't limit the scope
		$scope = 'all';
	}
	if ( ! empty( $person_id ) ) {
		$where_arr[] = "bookings.person_id=$person_id";
		// for this search, don't limit the scope
		$scope = 'all';
	}

	// the event_id overrides the search for event and the start/end dates
	if ( ! empty( $event_id ) ) {
		$where_arr[] = "bookings.event_id=$event_id";
	} else {
		if ( ! empty( $search_start_date ) && ! empty( $search_end_date ) ) {
			$where_arr[] = "events.event_start >= '$search_start_date 00:00:00'";
			$where_arr[] = "events.event_end <= '$search_end_date 23:59:59'";
			$scope       = 'all';
		} elseif ( ! empty( $search_start_date ) ) {
			$where_arr[] = "events.event_start LIKE '$search_start_date%'";
			$scope       = 'all';
		} elseif ( ! empty( $search_end_date ) ) {
			$where_arr[] = "events.event_end LIKE '$search_end_date%'";
			$scope       = 'all';
		} elseif ( $scope == 'past' ) {
				$where_arr[] = "events.event_end < '$today'";
		} elseif ( $scope == 'future' ) {
			$where_arr[] = "events.event_end >= '$today'";
		}

		if ( ! empty( $search_event ) ) {
			$where_arr[] = "events.event_name LIKE '%$search_event%'";
		}
	}

	if ( is_numeric( $category ) ) {
		if ( $category > 0 ) {
			$where_arr[] = "FIND_IN_SET($category,events.event_category_ids)";
		}
	} elseif ( $category == 'none' ) {
		$where_arr[] = "events.event_category_ids = ''";
	}

	if ( ! empty( $where_arr ) ) {
		$where = ' WHERE ' . implode( ' AND ', $where_arr );
	}

	$sql1        = "SELECT count(bookings.booking_id) FROM $bookings_table AS bookings LEFT JOIN $events_table AS events ON bookings.event_id=events.event_id LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id $sql_join $where";
	$sql2        = "SELECT bookings.* FROM $bookings_table AS bookings LEFT JOIN $events_table AS events ON bookings.event_id=events.event_id LEFT JOIN $people_table AS people ON bookings.person_id=people.person_id $sql_join $where ORDER BY $jtSorting LIMIT $jtStartIndex,$jtPageSize";
	$recordCount = $wpdb->get_var( $sql1 );
	$bookings    = $wpdb->get_results( $sql2, ARRAY_A );
	$wp_users    = eme_get_indexed_users();
	$pgs         = eme_payment_gateways();

	$formfields = eme_get_formfields( '', 'rsvp,generic,events' );
	$rows = [];
	// the array $event_name_info will be used to store the event info for bookings, so we don't need to recalculate that for each booking
	$event_name_info = [];
	foreach ( $bookings as $booking ) {
		$line     = [];
		$event_id = $booking['event_id'];
		$event    = eme_get_event( $event_id );
		$answers  = eme_get_event_answers( $event_id );
		$answers  = array_merge($answers,eme_get_booking_answers( $booking['booking_id'] ));
		if ( ! empty( $booking['person_id'] ) ) {
			$person = eme_get_person( $booking['person_id'] );
			// if a booking person_id gets removed for some reason, this is a non-existing person, so let's take a new one to avoid php warnings
			if ( empty( $person ) ) {
				$person = eme_new_person();
			}
			$person_info_shown  = eme_esc_html( eme_format_full_name( $person['firstname'], $person['lastname'] ) );
			$person_info_shown .= ' (' . eme_esc_html( $person['email'] ) . ')';
			if ( ! empty( $person['wp_id'] ) && isset( $wp_users[ $person['wp_id'] ] ) ) {
				$line['wp_user'] = eme_esc_html( $wp_users[ $person['wp_id'] ] );
			} else {
				$line['wp_user'] = '';
			}
			$line['booker'] = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person['person_id'] ) . "' title='" . __( 'Click the name of the booker in order to see and/or edit the details of the booker.', 'events-made-easy' ) . "'>" . eme_esc_html( $person_info_shown ) . '</a>';
		} else {
			$line['booker']  = __( 'Anonymous', 'events-made-easy' );
			$line['wp_user'] = '';
		}

		$date_obj             = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
		$localized_start_date = eme_localized_date( $event['event_start'], EME_TIMEZONE, 1 );
		$localized_start_time = eme_localized_time( $event['event_start'], EME_TIMEZONE, 1 );
		$localized_end_date   = eme_localized_date( $event['event_end'], EME_TIMEZONE, 1 );
		$localized_end_time   = eme_localized_time( $event['event_end'], EME_TIMEZONE, 1 );
		$localized_booking_datetime = eme_localized_datetime( $booking['creation_date'], EME_TIMEZONE, 1 );
		$localized_payment_datetime = eme_localized_datetime( $booking['payment_date'], EME_TIMEZONE, 1 );
		if ( $booking['reminder'] > 0 ) {
			$eme_date_obj_reminder->setTimestamp( $booking['reminder'] );
			$booking_reminder            = $eme_date_obj_reminder->getDateTime();
			$localized_reminder_datetime = eme_localized_datetime( $booking_reminder, EME_TIMEZONE, 1 );
		} else {
			$localized_reminder_datetime = '';
		}

		$line['booking_id'] = $booking['booking_id'];
		if ( $booking_status == 'PENDING' ) {
			$page = 'eme-registration-approval';
		} else {
			$page = 'eme-registration-seats';
		}
		if ( $trash ) {
			$line['edit_link'] = '';
		} else {
			$line['edit_link'] = "<a href='" . wp_nonce_url( admin_url( "admin.php?page=$page&amp;eme_admin_action=editBooking&amp;booking_id=" . $booking ['booking_id'] ), 'eme_admin', 'eme_admin_nonce' ) . "' title='" . esc_attr__( 'Click here to see and/or edit the details of the booking.', 'events-made-easy' ) . "'>" . "<img src='" . esc_url(EME_PLUGIN_URL) . "images/edit.png' alt='" . __( 'Edit', 'events-made-easy' ) . "'> " . '</a>';
		}
		if ( ! isset( $event_name_info[ $event_id ] ) ) {
			$event_name_info[ $event_id ] = '';
			$add_event_info               = 1;
		} else {
			$add_event_info = 0;
		}
		if ( $add_event_info ) {
			$event_name_info[ $event_id ] .= "<strong><a href='" . admin_url( 'admin.php?page=eme-manager&amp;eme_admin_action=edit_event&amp;event_id=' . $event['event_id'] ) . "' title='" . __( 'Edit event', 'events-made-easy' ) . "'>" . eme_trans_esc_html( $event['event_name'] ) . '</a></strong>';
		}
		if ( $event['event_rsvp'] ) {
			if ( $add_event_info ) {
				$event_name_info[ $event_id ] .= '<br>' . esc_html__( 'RSVP Info: ', 'events-made-easy' );
				$booked_seats  = eme_get_approved_seats( $event['event_id'] );
				$pending_seats = eme_get_pending_seats( $event['event_id'] );
				$booked_string = esc_html__( 'Approved:', 'events-made-easy' );
				$total_seats   = eme_get_total( $event['event_seats'] );
				if ( eme_is_multi( $event['event_seats'] ) ) {
					if ( $pending_seats > 0 ) {
						$pending_seats_string = $pending_seats . ' (' . eme_convert_array2multi( eme_get_pending_multiseats( $event['event_id'] ) ) . ')';
					} else {
						$pending_seats_string = $pending_seats;
					}
					$total_seats_string = $total_seats . ' (' . $event['event_seats'] . ')';
					if ( $booked_seats > 0 ) {
						$booked_seats_string = $booked_seats . ' (' . eme_convert_array2multi( eme_get_approved_multiseats( $event['event_id'] ) ) . ')';
					} else {
						$booked_seats_string = $booked_seats;
					}
				} else {
					$pending_seats_string = $pending_seats;
					$total_seats_string   = $total_seats;
					$booked_seats_string  = $booked_seats;
				}
				if ( $total_seats > 0 ) {
					$available_seats = eme_get_available_seats( $event['event_id'] );
					if ( eme_is_multi( $event['event_seats'] ) ) {
						$available_seats_string = $available_seats . ' (' . eme_convert_array2multi( eme_get_available_multiseats( $event['event_id'] ) ) . ')';
					} else {
						$available_seats_string = $available_seats;
					}
					$event_name_info[ $event_id ] .= esc_html__( 'Free: ', 'events-made-easy' ) . $available_seats_string;
					$event_name_info[ $event_id ] .= ', ' . "<a href='" . admin_url( 'admin.php?page=eme-registration-seats&amp;event_id=' . $event['event_id'] ) . "'>$booked_string $booked_seats_string</a>";
				} else {
					$total_seats_string                    = '&infin;';
					$event_name_info[ $event_id ] .= "<a href='" . admin_url( 'admin.php?page=eme-registration-seats&amp;event_id=' . $event['event_id'] ) . "'>$booked_string $booked_seats_string</a>";
				}

				if ( $pending_seats > 0 ) {
					$event_name_info[ $event_id ] .= ', ' . "<a href='" . admin_url( 'admin.php?page=eme-registration-approval&amp;event_id=' . $event['event_id'] ) . "'>" . __( 'Pending: ', 'events-made-easy' ) . "$pending_seats_string</a>";
				}
				if ( $event['event_properties']['take_attendance'] ) {
					$absent_bookings = eme_get_absent_bookings( $event['event_id'] );
					if ( $absent_bookings > 0 ) {
						$event_name_info[ $event_id ] .= ', ' . "<a href='" . admin_url( 'admin.php?page=eme-registration-seats&amp;event_id=' . $event['event_id'] ) . "'>" . __( 'Absent:', 'events-made-easy' ) . " $absent_bookings</a>";
					}
				}
				$event_name_info[ $event_id ] .= ', ' . __( 'Max: ', 'events-made-easy' ) . $total_seats_string;
				$waitinglist_seats            = $event['event_properties']['waitinglist_seats'];
				if ( $waitinglist_seats > 0 ) {
					$event_name_info[ $event_id ] .= ' ' . sprintf( __( '(%d waiting list seats included)', 'events-made-easy' ), $waitinglist_seats );
				}

				if ( $booked_seats > 0 || $pending_seats > 0 ) {
					$printable_address            = admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=booking_printable&amp;event_id=' . $event['event_id'] );
					$csv_address                  = admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=booking_csv&amp;event_id=' . $event['event_id'] );
					$event_name_info[ $event_id ] .= " <br>(<a id='booking_printable_" . $event['event_id'] . "' href='$printable_address'>" . __( 'Printable view', 'events-made-easy' ) . '</a>)';
					$event_name_info[ $event_id ] .= " (<a id='booking_csv_" . $event['event_id'] . "' href='$csv_address'>" . __( 'CSV export', 'events-made-easy' ) . '</a>)';
				}
			}

			if ( $event['registration_requires_approval'] ) {
				$page = 'eme-registration-approval';
			} else {
				$page = 'eme-registration-seats';
			}

			$line['rsvp'] = "<a href='" . wp_nonce_url( admin_url( "admin.php?page=$page&amp;eme_admin_action=newBooking&amp;event_id=" . $event['event_id'] ), 'eme_admin', 'eme_admin_nonce' ) . "' title='" . esc_attr__( 'Add booking for this event', 'events-made-easy' ) . "'>" . esc_html__( 'RSVP', 'events-made-easy' ) . '</a>';
			if ( ! empty( $event['event_properties']['rsvp_password'] ) ) {
				$line['rsvp'] .= '<br>(' . esc_html__( 'Password protected', 'events-made-easy' ) . ')';
			}
		} else {
			$line['rsvp'] = '';
		}
		if ( $event['event_tasks'] && $add_event_info ) {
			$tasks = eme_get_event_tasks( $event['event_id'] );
			$task_count = count($tasks);
			if ( $add_event_info && $task_count>0 ) {
				$pending_spaces = 0;
                                $used_spaces = 0;
                                //$total_spaces = 0;
                                foreach ( $tasks as $task ) {
					if ( $event['event_properties']['task_requires_approval'] ) {
						$pending_spaces += eme_count_task_pending_signups( $task['task_id'] );
					}
                                        $used_spaces += eme_count_task_approved_signups( $task['task_id'] );
                                        //$total_spaces += $task['spaces'];
                                }
                                #$free_spaces = $total_spaces - $used_spaces;
                                #$event_name_info[ $event_id ] .= '<br>' . esc_html__( sprintf( 'Task Info: %d tasks, %d/%d/%d free/used/total slots', 'events-made-easy' ), $task_count, $free_spaces, $used_spaces, $total_spaces );
				 $event_name_info[ $event_id ] .= '<br>' . sprintf( __('Task Info: %d tasks', 'events-made-easy' ), $task_count );
                                if ( $pending_spaces >0 ) {
                                        $event_name_info[ $event_id ] .= ', ' . "<a href='" . admin_url( 'admin.php?page=eme-task-signups&amp;status=0&amp;event_id=' . $event['event_id'] ) . "'>" . __( 'Pending:', 'events-made-easy' ) . " $pending_spaces</a>";
                                }
                                $event_name_info[ $event_id ] .= ', ' . "<a href='" . admin_url( 'admin.php?page=eme-task-signups&amp;status=1&amp;event_id=' . $event['event_id'] ) . "'>" . __( 'Approved:', 'events-made-easy' ) . " $used_spaces</a>";
			}
		}

		$line['event_name'] = $event_name_info[ $event_id ];
		$line['event_id']   = $event_id;
		$line['event_cats'] = join( '<br>', eme_get_event_category_names( $event_id ) );

		$line['datetime'] = $localized_start_date;
		if ( $localized_end_date != '' && $localized_end_date != $localized_start_date ) {
			$line['datetime'] .= ' - ' . $localized_end_date;
		}
		$line['datetime'] .= '<br>';
		if ( $event['event_properties']['all_day'] == 1 ) {
			$line['datetime'] .= esc_html__( 'All day', 'events-made-easy' );
		} else {
			$line['datetime'] .= esc_html("$localized_start_time - $localized_end_time");
		}
		if ( $date_obj < $eme_date_obj_now ) {
			$line['datetime'] = "<span style='text-decoration: line-through;'>" . $line['datetime'] . '</span>';
		}

		$line['creation_date'] = esc_html($localized_booking_datetime);
		$line['payment_id']    = eme_esc_html( $booking['payment_id'] );
		if ( $booking['payment_date'] != '0000-00-00 00:00:00' ) {
			$line['payment_date'] = esc_html($localized_payment_datetime);
		} else {
			$line['payment_date'] = '';
		}
		if ( eme_is_multi( eme_get_booking_event_price( $booking ) ) ) {
			$line['seats'] = eme_convert_multi2br( $booking['booking_seats_mp'] );
		} else {
			$line['seats'] = esc_html($booking['booking_seats']);
		}
		if ( $booking['waitinglist'] ) {
			$line['seats'] .= '<br>' . esc_html__( '(On waitinglist)', 'events-made-easy' );
		}
		if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
			$line['seats'] .= '<br>' . esc_html__( '(Awaiting user confirmation)', 'events-made-easy' );
		}
		if ( $booking['reminder'] > 0 ) {
			$line['lastreminder'] = $localized_reminder_datetime;
		} else {
			$line['lastreminder'] = esc_html__( 'Never', 'events-made-easy' );
		}
		$line['eventprice'] = eme_convert_multi2br( eme_localized_price( eme_get_booking_event_price( $booking ), $event['currency'] ) );
		$line['totalprice'] = eme_localized_price( eme_get_total_booking_price( $booking ), $event['currency'] );
		$line['discount']   = eme_localized_price( $booking['discount'], $event['currency'] );
		// dcodes_used is still eme_serialized here
		$line['dcodes_used']  = eme_esc_html( eme_unserialize( $booking['dcodes_used'] ) );
		$line['unique_nbr']   = "<span title='" . sprintf( __( 'This is based on the payment ID of the booking: %d', 'events-made-easy' ), $booking ['payment_id'] ) . "'>" . eme_esc_html( eme_unique_nbr_formatted( $booking['unique_nbr'] ) ) . '</span>';
		$line['booking_paid'] = $booking['booking_paid'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
		if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
			$line['remaining'] = $line['totalprice'];
		} else {
			$line['remaining'] = eme_localized_price( $booking['remaining'], $event['currency'] );
		}
		$line['received'] = eme_convert_multi2br( eme_localized_price( $booking['received'], $event['currency'] ) );
		if ( !empty( $booking['pg'] ) ) {
			if ( isset( $pgs[ $booking['pg'] ] ) ) {
                                $line['pg'] = eme_esc_html( $pgs[ $booking['pg'] ] );
                        } else {
                                $line['pg'] = 'UNKNOWN';
                        }
			if ($booking['pg'] == 'payconiq' && !empty($booking['pg_pid'])) {
				$line['pg'] .= "<br><button id='button_".$booking['payment_id']."' class='button action eme_iban_button' data-pg_pid='".$booking['pg_pid']."'>".esc_html__('Get IBAN')."</button><span id='payconiq_".$booking['payment_id']."'></span>";
			}
                } else {
                        $line['pg'] = '';
                }

		$line['pg_pid']          = eme_esc_html( $booking['pg_pid'] );
		$line['attend_count']    = intval( $booking['attend_count'] );
		$line['booking_comment'] = eme_esc_html( $booking['booking_comment'] );
		foreach ( $formfields as $formfield ) {
			foreach ( $answers as $answer ) {
				if ( $answer['field_id'] == $formfield['field_id'] && $answer['answer'] != '' ) {
					$val = eme_answer2readable( $answer['answer'], $formfield, 1, ',', 'text', 1 );
					// the 'FIELD_' value is used by the container-js
					$key = 'FIELD_' . $answer['field_id'];
					if ( isset( $line[ $key ] ) ) {
						$line[ $key ] .= "<br>$val";
					} else {
						$line[ $key ] = $val;
					}
				}
			}
		}
		$files1 = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
		$files2 = eme_get_uploaded_files( $booking['person_id'], 'people' );
		$files  = array_merge( $files1, $files2 );
		foreach ( $files as $file ) {
			$key = 'FIELD_' . $file['field_id'];
			if ( isset( $line[ $key ] ) ) {
				$line[ $key ] .= eme_get_uploaded_file_html( $file );
			} else {
				$line[ $key ] = eme_get_uploaded_file_html( $file );
			}
		}
		$rows[] = $line;
	}
	$jTableResult['Result']           = 'OK';
	$jTableResult['TotalRecordCount'] = $recordCount;
	$jTableResult['Records']          = $rows;
	print wp_json_encode( $jTableResult );
	wp_die();
}

function eme_ajax_manage_bookings() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! (
	    current_user_can( get_option( 'eme_cap_registrations' ) ) ||
		current_user_can( get_option( 'eme_cap_author_registrations' ) ) ||
		current_user_can( get_option( 'eme_cap_approve' ) ) ||
		current_user_can( get_option( 'eme_cap_author_approve' ) )
	) ) {
		wp_die();
	}

	if ( isset( $_POST['do_action'] ) ) {
		$booking_ids = eme_sanitize_request( $_POST['booking_ids'] );
		$ids_arr     = explode( ',', $booking_ids );
		$do_action   = eme_sanitize_request( $_POST['do_action'] );
		$send_mail   = ( isset( $_POST['send_mail'] ) ) ? intval( $_POST['send_mail'] ) : 1;
		$refund      = ( isset( $_POST['refund'] ) ) ? intval( $_POST['refund'] ) : 0;
		// to be sure
		if ( ! get_option( 'eme_payment_refund_ok' ) ) {
			$refund = 0;
		}

		if ( eme_is_numeric_array( $ids_arr ) ) {
			switch ( $do_action ) {
				case 'markpaidandapprove':
					// shortcut button to do 2 things at once, mail will always be sent
					eme_ajax_action_rsvp_markpaidandapprove( $ids_arr );
					break;
				case 'approveBooking':
					eme_ajax_action_rsvp_aprove( $ids_arr, $do_action, $send_mail );
					break;
				case 'deleteBooking':
					eme_ajax_action_rsvp_delete( $ids_arr );
					break;
				case 'trashBooking':
					eme_ajax_action_rsvp_trash( $ids_arr, $do_action, $send_mail, $refund );
					break;
				case 'partialPayment':
					$amount = ( isset( $_POST['partial_amount'] ) ) ? eme_sanitize_request($_POST['partial_amount']) : 0;
					if ( count( $ids_arr ) == 1 && is_numeric( $amount ) ) {
						$booking_id = $ids_arr[0];
						eme_ajax_action_booking_partial_payment( $booking_id, $amount, $send_mail );
					}
					break;
				case 'markPaid':
					eme_ajax_action_mark_booking_paid( $ids_arr, 'paidBooking', $send_mail );
					break;
				case 'markUnpaid':
					eme_ajax_action_mark_booking_unpaid( $ids_arr, 'updateBooking', $send_mail, $refund );
					break;
				case 'resendApprovedBooking':
					$send_to_contact_too = ( isset( $_POST['send_to_contact_too'] ) ) ? intval( $_POST['send_to_contact_too'] ) : 0;
					if ($send_to_contact_too) {
						eme_ajax_action_resend_booking_mail( $ids_arr, 'approvedBooking' );
					} else {
						eme_ajax_action_resend_booking_mail( $ids_arr, $do_action );
					}
					break;
				case 'resendPendingBooking':
					eme_ajax_action_resend_booking_mail( $ids_arr, 'pendingBooking' );
					break;
				case 'userConfirmBooking':
					eme_ajax_action_mark_userconfirm( $booking_ids, $do_action );
					break;
				case 'pendingBooking':
					eme_ajax_action_mark_pending( $ids_arr, $do_action, $send_mail, $refund );
					break;
				case 'unsetwaitinglistBooking':
					eme_ajax_action_remove_waitinglist( $ids_arr, $do_action, $send_mail );
					break;
				case 'setwaitinglistBooking':
					eme_ajax_action_move_waitinglist( $ids_arr, $do_action, $send_mail, $refund );
					break;
				case 'rsvpMails':
					$template_id_subject = ( isset( $_POST['rsvpmail_template_subject'] ) ) ? intval( $_POST['rsvpmail_template_subject'] ) : 0;
					$template_id         = ( isset( $_POST['rsvpmail_template'] ) ) ? intval( $_POST['rsvpmail_template'] ) : 0;
					if ( $template_id_subject && $template_id ) {
						eme_ajax_action_send_booking_mails( $ids_arr, $template_id_subject, $template_id );
					}
					break;
				case 'pdf':
					$template_id        = ( isset( $_POST['pdf_template'] ) ) ? intval( $_POST['pdf_template'] ) : 0;
					$template_id_header = ( isset( $_POST['pdf_template_header'] ) ) ? intval( $_POST['pdf_template_header'] ) : 0;
					$template_id_footer = ( isset( $_POST['pdf_template_footer'] ) ) ? intval( $_POST['pdf_template_footer'] ) : 0;
					if ( $template_id ) {
						eme_ajax_generate_booking_pdf( $ids_arr, $template_id, $template_id_header, $template_id_footer );
					}
					break;
				case 'html':
					$template_id        = ( isset( $_POST['html_template'] ) ) ? intval( $_POST['html_template'] ) : 0;
					$template_id_header = ( isset( $_POST['html_template_header'] ) ) ? intval( $_POST['html_template_header'] ) : 0;
					$template_id_footer = ( isset( $_POST['html_template_footer'] ) ) ? intval( $_POST['html_template_footer'] ) : 0;
					if ( $template_id ) {
						eme_ajax_generate_booking_html( $ids_arr, $template_id, $template_id_header, $template_id_footer );
					}
					break;
			}
		}
	}
	wp_die();
}

function eme_ajax_action_rsvp_markpaidandapprove( $ids_arr ) {
	$action_ok        = 1;
	$mail_ok          = 1;
	$mailing_approved = get_option( 'eme_rsvp_mail_notify_approved' );
	$mailing_paid     = get_option( 'eme_rsvp_mail_notify_paid' );

	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		if ( $booking['booking_paid'] && $booking['status'] == EME_RSVP_STATUS_APPROVED ) {
			continue;
		}
		if ( $booking['booking_paid'] ) {
			$res = eme_approve_booking( $booking_id );
		} else {
			$res = eme_mark_booking_paid_approved( $booking );
		}
		if ( $res ) {
			$booking = eme_get_booking( $booking_id );
			if ( has_action( 'eme_approve_rsvp_action' ) ) {
				do_action( 'eme_approve_rsvp_action', $booking );
			}
			// if we need to send out approval mails, we don't send out the paid mails too
			if ( $mailing_approved ) {
				$res2 = eme_email_booking_action( $booking, 'approveBooking' );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			} elseif ( $mailing_paid ) {
				$res2 = eme_email_booking_action( $booking, 'paidBooking' );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $mail_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p><p>' . __( 'Hint: for a booking on the waiting list, the only allowed action is to move it off the waiting list.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_remove_waitinglist( $ids_arr, $action, $send_mail ) {
	$action_ok = 1;
	$mail_ok   = 1;
	foreach ( $ids_arr as $booking_id ) {
		// waiting list can only be removed for pending bookings
		// so set the action to "pending"
		$action = 'pendingBooking';
		$res    = eme_remove_from_waitinglist( $booking_id );
		if ( $res ) {
			$booking = eme_get_booking( $booking_id );
			if ( $send_mail ) {
				$res2 = eme_email_booking_action( $booking, $action );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $mail_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_move_waitinglist( $ids_arr, $action, $send_mail, $refund ) {
	$action_ok = 1;
	$mail_ok   = 1;
	$refund_ok = 1;
	foreach ( $ids_arr as $booking_id ) {
		// waiting list can only be removed for pending bookings
		// so set the action to "pending"
		$action = 'pendingBooking';
		$res    = eme_move_on_waitinglist( $booking_id );
		if ( $res ) {
			$booking = eme_get_booking( $booking_id );
			if ( $refund ) {
				$res3 = eme_refund_booking( $booking );
				if ( ! $res3 ) {
					$refund_ok = 0;
				}
			}
			if ( $send_mail ) {
				$res2 = eme_email_booking_action( $booking, $action );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( ! $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( ! $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems refunding the payment.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( ! $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems sending mail and refunding the payment.', 'events-made-easy' ) . '</p></div>';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_rsvp_aprove( $ids_arr, $action, $send_mail ) {
	$action_ok = 1;
	$mail_ok   = 1;
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		// let's make sure to do this only for pending bookings (or trashed, for restoring to pending/approved)
		if ( $booking['status'] == EME_RSVP_STATUS_PENDING || $booking['status'] == EME_RSVP_STATUS_USERPENDING || $booking['status'] == EME_RSVP_STATUS_TRASH ) {
			$res = eme_approve_booking( $booking_id );
			if ( $res ) {
				$booking = eme_get_booking( $booking_id );
				if ( has_action( 'eme_approve_rsvp_action' ) ) {
					do_action( 'eme_approve_rsvp_action', $booking );
				}
				if ( $send_mail ) {
					$res2 = eme_email_booking_action( $booking, $action );
					if ( ! $res2 ) {
						$mail_ok = 0;
					}
				}
			} else {
				$action_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mail_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p><p>' . __( 'Hint: for a booking on the waiting list, the only allowed action is to move it off the waiting list.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_rsvp_delete( $ids_arr ) {
	$action_ok = 1;
	foreach ( $ids_arr as $booking_id ) {
		$res = eme_delete_booking( $booking_id );
		if ( ! $res ) {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_rsvp_trash( $ids_arr, $action, $send_mail, $refund ) {
	$action_ok = 1;
	$mail_ok   = 1;
	$refund_ok = 1;
	foreach ( $ids_arr as $booking_id ) {
		$res = eme_trash_booking( $booking_id );
		if ( $res ) {
			$booking = eme_get_booking( $booking_id );
			$event   = eme_get_event( $booking['event_id'] );
			if ( ! empty( $event ) ) {
				eme_manage_waitinglist( $event, $send_mail );
				if ( $refund ) {
					$res3 = eme_refund_booking( $booking );
					if ( ! $res3 ) {
						$refund_ok = 0;
					}
				}
				if ( $send_mail ) {
					$res2 = eme_email_booking_action( $booking, $action );
					if ( ! $res2 ) {
						$mail_ok = 0;
					}
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( ! $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( ! $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems refunding the payment.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( ! $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems sending mail and refunding the payment.', 'events-made-easy' ) . '</p></div>';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_booking_partial_payment( $booking_id, $amount, $send_mail ) {
	$action_ok = 1;
	$mail_ok   = 1;

	$mailing_approved = get_option( 'eme_rsvp_mail_notify_approved' );
	$mailing_paid     = get_option( 'eme_rsvp_mail_notify_paid' );

	$booking = eme_get_booking( $booking_id );
	// already paid? Then don't do anything
	if ( $booking['booking_paid'] ) {
		return;
	}
	$res = eme_partial_payment_booking( $booking, $amount );
	if ( $res ) {
		// the booking has changed, so get it again
		$booking = eme_get_booking( $booking_id );
		$event   = eme_get_event( $booking['event_id'] );
		// if the booking is now paid for, approve if needed and send out the expected mails
		if ( ! empty( $event ) && $booking['booking_paid'] ) {
			if ( $event['event_properties']['auto_approve'] && ( $booking['status'] == EME_RSVP_STATUS_PENDING || $booking['status'] == EME_RSVP_STATUS_USERPENDING ) ) {
				eme_approve_booking( $booking_id );
				if ( $send_mail && $mailing_approved ) {
					$booking = eme_get_booking( $booking_id );
					if ( $mailing_approved ) {
						$res2 = eme_email_booking_action( $booking, 'approveBooking' );
						if ( ! $res2 ) {
							$mail_ok = 0;
						}
					} elseif ( $mailing_paid ) {
						$res2 = eme_email_booking_action( $booking, 'paidBooking' );
						if ( ! $res2 ) {
							$mail_ok = 0;
						}
					}
				}
			} elseif ( $send_mail ) {
					$res2 = eme_email_booking_action( $booking, 'paidBooking' );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		}
	} else {
		$action_ok = 0;
	}
	$ajaxResult = [];
	if ( $mail_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_mark_booking_paid( $ids_arr, $action, $send_mail ) {
	$action_ok = 1;
	$mail_ok   = 1;
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		if ( $booking['booking_paid'] ) {
			continue;
		}
		$event = eme_get_event( $booking['event_id'] );
		if ( ! empty( $event ) ) {
			if ( $event['event_properties']['auto_approve'] && ( $booking['status'] == EME_RSVP_STATUS_PENDING || $booking['status'] == EME_RSVP_STATUS_USERPENDING ) && ! $booking['waitinglist'] ) {
				$res = eme_mark_booking_paid_approved( $booking );
			} else {
				$res = eme_mark_booking_paid( $booking );
			}
			if ( $res ) {
				if ( $send_mail ) {
					$booking = eme_get_booking( $booking_id );
					$res2    = eme_email_booking_action( $booking, $action );
					if ( ! $res2 ) {
						$mail_ok = 0;
					}
				}
			} else {
				$action_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mail_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_mark_booking_unpaid( $ids_arr, $action, $send_mail, $refund ) {
	$action_ok = 1;
	$mail_ok   = 1;
	foreach ( $ids_arr as $booking_id ) {
		// refund also marks it as unpaid if successful
		if ( $refund ) {
			$booking = eme_get_booking( $booking_id );
			$res     = eme_refund_booking( $booking );
		} else {
			$booking = eme_get_booking( $booking_id );
			$res     = eme_mark_booking_unpaid( $booking );
		}
		if ( $res ) {
			// the booking has changed
			$booking = eme_get_booking( $booking_id );
			if ( $send_mail ) {
				$res2 = eme_email_booking_action( $booking, $action );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( ! $action_ok ) {
		if ( $refund ) {
			$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem refunding the payment, please check your logs.', 'events-made-easy' ) . '</p></div>';
		} else {
			$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		}
		$ajaxResult['Result'] = 'ERROR';
	} elseif ( ! $mail_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_send_booking_mails( $ids_arr, $subject_template_id, $body_template_id ) {
	$mail_ok        = 1;
	$mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
	$subject        = eme_get_template_format_plain( $subject_template_id );
	$body           = eme_get_template_format_plain( $body_template_id );
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		$person  = eme_get_person( $booking['person_id'] );
		$event   = eme_get_event( $booking['event_id'] );
		if ( ! empty( $event ) && $person && is_array( $person ) ) {
			$contact           = eme_get_event_contact( $event );
				$contact_email = $contact->user_email;
				$contact_name  = $contact->display_name;
			$tmp_subject       = eme_replace_booking_placeholders( $subject, $event, $booking, 0, 'text' );
			$tmp_message       = eme_replace_booking_placeholders( $body, $event, $booking, 0, $mail_text_html );
			$person_name       = eme_format_full_name( $person['firstname'], $person['lastname'] );
			$mail_res          = eme_queue_mail( $tmp_subject, $tmp_message, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, 0, $person['person_id'] );
			if ( ! $mail_res ) {
				$mail_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mail_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_resend_booking_mail( $ids_arr, $action ) {
	$mail_ok = 1;
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		$res     = eme_email_booking_action( $booking, $action );
		if ( ! $res ) {
			$mail_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $mail_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_mark_userconfirm( $ids, $action ) {
	eme_mark_booking_userconfirm( $ids );
	$ajaxResult                = [];
	$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
	$ajaxResult['Result']      = 'OK';
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_mark_pending( $ids_arr, $action, $send_mail, $refund ) {
	$action_ok = 1;
	$mail_ok   = 1;
	$refund_ok = 1;
	foreach ( $ids_arr as $booking_id ) {
		$res = eme_mark_booking_pending( $booking_id );
		if ( $res ) {
			if ( $refund ) {
				$booking = eme_get_booking( $booking_id );
				$res3    = eme_refund_booking( $booking );
				if ( ! $res3 ) {
					$refund_ok = 0;
				}
			}
			// booking might have changed after refund, so get it again
			$booking = eme_get_booking( $booking_id );
			if ( $send_mail ) {
				$res2 = eme_email_booking_action( $booking, $action );
				if ( ! $res2 ) {
					$mail_ok = 0;
				}
			}
		} else {
			$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( ! $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( ! $mail_ok && $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems refunding the payment.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} elseif ( ! $mail_ok && ! $refund_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'The action has been executed successfully but there were some problems sending mail and refunding the payment.', 'events-made-easy' ) . '</p></div>';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_generate_booking_pdf( $booking, $event, $template_id ) {
	$template = eme_get_template( $template_id );

	// if the template is not meant for pdf, return
	if ( $template['type'] != "pdf" ) {
		return;
	}

	$targetPath  = EME_UPLOAD_DIR . '/bookings/' . $booking['booking_id'];
	$pdf_path    = '';
	if ( is_dir( $targetPath ) ) {
		foreach ( glob( "$targetPath/booking-$template_id-*.pdf" ) as $filename ) {
			$pdf_path = $filename;
		}
		// support the older "ticket-" name convention too
		if ( empty( $pdf_path ) ) {
			foreach ( glob( "$targetPath/ticket-$template_id-*.pdf" ) as $filename ) {
				$pdf_path = $filename;
			}
		}
	}
	// we found a generated pdf, let's check the pdf creation time against the modif time of the event/booking/template
	if ( !empty( $pdf_path ) ) {
		$pdf_mtime      = filemtime( $pdf_path );
		$pdf_mtime_obj      = new ExpressiveDate( 'now', EME_TIMEZONE );
		$pdf_mtime_obj->setTimestamp($pdf_mtime);
		$booking_mtime_obj  = new ExpressiveDate( $booking['modif_date'], EME_TIMEZONE );
		$event_mtime_obj    = new ExpressiveDate( $event['modif_date'], EME_TIMEZONE );
		$template_mtime_obj = new ExpressiveDate( $template['modif_date'], EME_TIMEZONE );
		if ($booking_mtime_obj<$pdf_mtime_obj && $event_mtime_obj<$pdf_mtime_obj && $template_mtime_obj<$pdf_mtime_obj) {
			return $pdf_path;
		}
	}

	// the template format needs br-handling, so lets use a handy function
	$format = eme_get_template_format( $template_id );

	require_once 'dompdf/vendor/autoload.php';
	// instantiate and use the dompdf class
	$options = new Dompdf\Options();
	$options->set( 'isRemoteEnabled', true );
	$options->set( 'isHtml5ParserEnabled', true );
	$dompdf      = new Dompdf\Dompdf( $options );
	$margin_info = 'margin: ' . $template['properties']['pdf_margins'];
	$font_info       = 'font-family: ' . get_option( 'eme_pdf_font' );
	$orientation = $template['properties']['pdf_orientation'];
	$pagesize    = $template['properties']['pdf_size'];
	if ( $pagesize == 'custom' ) {
		$pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
	}

	$dompdf->setPaper( $pagesize, $orientation );
	$css = "\n<link rel='stylesheet' id='eme-css'  href='" . esc_url(EME_PLUGIN_URL) . "css/eme.css' type='text/css' media='all'>";
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		$css        .= "\n<link rel='stylesheet' id='eme-css-extra'  href='" . get_stylesheet_directory_uri() . "/eme.css' type='text/css' media='all'>";
	}
	$extra_html_header = get_option( 'eme_html_header' );
        $extra_html_header = trim( preg_replace( '/\r\n/', "\n", $extra_html_header ) );

	$html = "<html>
<head>
<style>
    @page { $margin_info; }
    body { $margin_info; $font_info; }
    div.page-break {
        page-break-before: always;
    }
</style>$css
$extra_html_header
</head>
<body>
";
	// avoid a loop between eme_replace_booking_placeholders and eme_generate_booking_pdf
	$format = str_replace( '#_BOOKINGPDF_URL', '', $format );

	$html .= eme_replace_booking_placeholders( $format, $event, $booking );
	$html .= "</body></html>";
	$dompdf->loadHtml( $html, get_bloginfo( 'charset' ) );
	$dompdf->render();
	// now we know where to store it, so create the dir
	if ( ! is_dir( $targetPath ) ) {
		wp_mkdir_p( $targetPath );
	}
	if ( ! is_file( $targetPath . '/index.html' ) ) {
		touch( $targetPath . '/index.html' );
	}
	// unlink old pdf
	array_map( 'wp_delete_file', glob( "$targetPath/booking-$template_id-*.pdf" ) );
	// now put new one
	$rand_id     = eme_random_id();
	$target_file = $targetPath . "/booking-$template_id-$rand_id.pdf";
	file_put_contents( $target_file, $dompdf->output() );
	return $target_file;
}

function eme_ajax_generate_booking_pdf( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
	$template   = eme_get_template( $template_id );
	$header = eme_get_template_format( $template_id_header );
	$footer = eme_get_template_format( $template_id_footer );
	// the template format needs br-handling, so lets use a handy function
	$format = eme_get_template_format( $template_id );

	require_once 'dompdf/vendor/autoload.php';
	// instantiate and use the dompdf class
	$options = new Dompdf\Options();
	$options->set( 'isRemoteEnabled', true );
	$options->set( 'isHtml5ParserEnabled', true );
	$dompdf      = new Dompdf\Dompdf( $options );
	$margin_info = 'margin: ' . $template['properties']['pdf_margins'];
	$font_info       = 'font-family: ' . get_option( 'eme_pdf_font' );
	$orientation = $template['properties']['pdf_orientation'];
	$pagesize    = $template['properties']['pdf_size'];
	if ( $pagesize == 'custom' ) {
		$pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
	}

	$dompdf->setPaper( $pagesize, $orientation );
	$css          = "\n<link rel='stylesheet' id='eme-css'  href='" . esc_url(EME_PLUGIN_URL) . "css/eme.css' type='text/css' media='all'>";
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		$css        .= "\n<link rel='stylesheet' id='eme-css-extra'  href='" . get_stylesheet_directory_uri() . "/eme.css' type='text/css' media='all'>";
	}

	$extra_html_header = get_option( 'eme_html_header' );
        $extra_html_header = trim( preg_replace( '/\r\n/', "\n", $extra_html_header ) );
	$html  = "<html>
<head>
<style>
    @page { $margin_info; }
    body { $margin_info; $font_info; }
    div.page-break {
        page-break-before: always;
    }
</style>$css
$extra_html_header
</head>
<body>
$header
";
	$total = count( $ids_arr );
	$i     = 1;
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		$event       = eme_get_event( $booking['event_id'] );
		if ( ! empty( $event ) ) {
			$html .= eme_replace_booking_placeholders( $format, $event, $booking );
		}
		if ( $i < $total ) {
			// dompdf uses a style to detect forced page breaks
			$html .= '<div class="page-break"></div>';
			++$i;
		}
	}
	$html .= "$footer</body></html>";

	$dompdf->loadHtml( $html, get_bloginfo( 'charset' ) );
	$dompdf->render();
	$dompdf->stream();
}

function eme_ajax_generate_booking_html( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
	$format     = eme_get_template_format( $template_id );
	$header = eme_get_template_format( $template_id_header );
	$footer = eme_get_template_format( $template_id_footer );
	$extra_html_header = get_option( 'eme_html_header' );
        $extra_html_header = trim( preg_replace( '/\r\n/', "\n", $extra_html_header ) );
	$html   = "<html><head>$extra_html_header</head><body>$header";
	$total  = count( $ids_arr );
	$i      = 1;
	foreach ( $ids_arr as $booking_id ) {
		$booking = eme_get_booking( $booking_id );
		$event       = eme_get_event( $booking['event_id'] );
		if ( ! empty( $event ) ) {
			$html .= eme_replace_booking_placeholders( $format, $event, $booking );
		}
		if ( $i < $total ) {
			// dompdf uses a style to detect forced page breaks
			$html .= '<div class="page-break"></div>';
			++$i;
		}
	}
	$html .= "$footer</body></html>";
	print $html;
}

// for CRON
function eme_rsvp_send_pending_reminders() {
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	// this gets us future and ongoing events with tasks enabled
	$events = eme_get_events( extra_conditions: 'event_rsvp=1' );
	foreach ( $events as $event ) {
		if ( eme_is_empty_string( $event['event_properties']['rsvp_pending_reminder_days'] ) ) {
			continue;
		}
		$reminder_days = explode( ',', $event['event_properties']['rsvp_pending_reminder_days'] );
		if ( ! eme_is_numeric_array( $reminder_days ) ) {
			continue;
		}
		$bookings     = eme_get_bookings_for( $event['event_id'], EME_RSVP_STATUS_PENDING );
		$eme_date_obj = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
		$days_diff    = intval( $eme_date_obj_now->startOfDay()->getDifferenceInDays( $eme_date_obj->startOfDay() ) );
		foreach ( $bookings as $booking ) {
			foreach ( $reminder_days as $reminder_day ) {
				$reminder_day = intval( $reminder_day );
				if ( $days_diff == $reminder_day ) {
					eme_email_booking_action( $booking, 'reminderPendingBooking' );
					eme_set_booking_reminder( $booking['booking_id'] );
				}
			}
		}
	}
}

function eme_rsvp_send_approved_reminders() {
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	// this gets us future and ongoing events with tasks enabled
	$events = eme_get_events( extra_conditions: 'event_rsvp=1' );
	foreach ( $events as $event ) {
		if ( eme_is_empty_string( $event['event_properties']['rsvp_approved_reminder_days'] ) ) {
			continue;
		}
		$reminder_days = explode( ',', $event['event_properties']['rsvp_approved_reminder_days'] );
		if ( ! eme_is_numeric_array( $reminder_days ) ) {
			continue;
		}
		$bookings     = eme_get_bookings_for( $event['event_id'], EME_RSVP_STATUS_APPROVED );
		$eme_date_obj = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
		$days_diff    = intval( $eme_date_obj_now->startOfDay()->getDifferenceInDays( $eme_date_obj->startOfDay() ) );
		foreach ( $bookings as $booking ) {
			foreach ( $reminder_days as $reminder_day ) {
				$reminder_day = intval( $reminder_day );
				if ( $days_diff == $reminder_day ) {
					eme_email_booking_action( $booking, 'reminderBooking' );
					eme_set_booking_reminder( $booking['booking_id'] );
				}
			}
		}
	}
}

// for GDPR CRON
function eme_rsvp_anonymize_old_bookings() {
	global $wpdb;
	$events_table                = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$bookings_table              = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$anonymize_old_bookings_days = get_option( 'eme_gdpr_anonymize_old_bookings_days' );
	if ( empty( $anonymize_old_bookings_days ) ) {
		return;
	} else {
		$anonymize_old_bookings_days = abs( $anonymize_old_bookings_days );
	}

	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
	$now          = $eme_date_obj->getDateTime();
	$old_date     = $eme_date_obj->minusDays( $anonymize_old_bookings_days )->getDateTime();

	// we don't remove old bookings, just anonymize them
	$sql = $wpdb->prepare("UPDATE $bookings_table SET person_id=0 WHERE creation_date < %s AND event_id IN (SELECT event_id FROM $events_table WHERE $events_table.event_end < %s)", $old_date, $now);
	$wpdb->query( $sql );
}

function eme_count_pending_bookings() {
	global $wpdb;
	$events_table     = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$bookings_table   = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$now              = $eme_date_obj_now->getDateTime();
	$sql              = $wpdb->prepare( "SELECT count(bookings.booking_id) FROM $bookings_table AS bookings LEFT JOIN $events_table AS events ON bookings.event_id=events.event_id WHERE bookings.status IN (%d,%d) AND events.event_end >= %s", EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $now );
	return $wpdb->get_var( $sql );
}

function eme_manage_waitinglist( $event, $send_mail = 1 ) {
	// if the correct event property is set:
	// get the bookings on the waiting list
	// and compare with number of free seats (take multiseat into account)
	// If a booking is found and the price is 0 and autoapprove: move off the waiting list and approve and send mail
	// otherwise if a booking is found: move off the waiting list as pending and send mail
	if ( $event['event_properties']['check_free_waiting'] ) {
		$total_seats    = eme_get_total( $event['event_seats'] );
		$event_id       = $event['event_id'];
		$basic_bookings = eme_get_basic_bookings_on_waitinglist( $event_id );
		foreach ( $basic_bookings as $basic_booking ) {
			$seats_available = 0;
			if ( $total_seats == 0 ) {
				$seats_available = 1;
			} elseif ( eme_is_multi( $event['event_seats'] ) ) {
				$bookedSeats_mp = eme_convert_multi2array( $basic_booking['booking_seats_mp'] );
				// we check for available seats, excluding waiting lists
				$seats_available = eme_are_multiseats_available_for( $event_id, $bookedSeats_mp, 1 );
			} else {
				// we check for available seats, excluding waiting lists
				$seats_available = eme_are_seats_available_for( $event_id, $basic_booking['booking_seats'], 1 );
			}
			if ( $seats_available ) {
				// booking remaining price 0? then move this of the waiting list and if autoapprove is on (or no pending is configured), approve and send mail
				if ( ! $event['registration_requires_approval'] || ( $event['event_properties']['auto_approve'] && $basic_booking['remaining'] == 0 ) ) {
					$res = eme_approve_booking( $basic_booking['booking_id'] );
					if ( $res && $send_mail ) {
						// get the booking for the mail
						$booking = eme_get_booking( $basic_booking['booking_id'] );
						eme_email_booking_action( $booking, 'approveBooking' );
					}
				} else {
					$res = eme_mark_booking_pending( $basic_booking['booking_id'] );
					if ( $res && $send_mail ) {
						// get the booking for the mail
						$booking = eme_get_booking( $basic_booking['booking_id'] );
						eme_email_booking_action( $booking, 'pendingBooking' );
					}
				}
			}
		}
	}
}

?>
