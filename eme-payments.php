<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_payment_gateways() {
    $pgs = [
        'paypal'       => __( 'Paypal', 'events-made-easy' ),
        'legacypaypal' => __( 'Legacy Paypal', 'events-made-easy' ),
        //'2co'          => __( '2Checkout', 'events-made-easy' ),
        'webmoney'     => __( 'Webmoney', 'events-made-easy' ),
        'fdgg'         => __( 'First Data', 'events-made-easy' ),
        'mollie'       => __( 'Mollie', 'events-made-easy' ),
        'payconiq'     => __( 'Payconiq', 'events-made-easy' ),
        'worldpay'     => __( 'Worldpay', 'events-made-easy' ),
        'opayo'        => __( 'Opayo', 'events_made_easy' ),
        'sumup'        => __( 'SumUp', 'events-made-easy' ),
        'stripe'       => __( 'Stripe', 'events-made-easy' ),
        'braintree'    => __( 'Braintree', 'events-made-easy' ),
        'instamojo'    => __( 'Instamojo', 'events-made-easy' ),
        'mercadopago'  => __( 'Mercado Pago', 'events-made-easy' ),
        'fondy'        => __( 'Fondy', 'events-made-easy' ),
        'offline'      => __( 'Offline', 'events-made-easy' ),
    ];

    // allow people to change the sequence or add their own payment gateway
    if ( has_filter( 'eme_payment_gateways' ) ) {
        $pgs = apply_filters( 'eme_payment_gateways', $pgs );
    }
    return $pgs;
}

function eme_is_offline_pg( $pg ) {
    $pgs = [
        'offline',
    ];
    if ( has_filter( 'eme_offline_payment_gateways' ) ) {
        $pgs = apply_filters( 'eme_offline_payment_gateways', $pgs );
    }
    if ( in_array( $pg, $pgs ) ) {
        return 1;
    } else {
        return 0;
    }
}

function eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking ) {
    if ( $payment['target'] == 'member' ) {
        $description = sprintf( __( "Member signup for '%s'", 'events-made-easy' ), $item_name );
        $filtername  = 'eme_member_paymentform_description_filter';
    } elseif ( $payment['target'] == 'fs_event' ) {
        $description = sprintf( __( "Event submission '%s'", 'events-made-easy' ), $item_name );
        $filtername  = 'eme_fs_event_paymentform_description_filter';
    } elseif ( $multi_booking ) {
        $description = __( 'Multiple booking request', 'events-made-easy' );
        $filtername  = 'eme_rsvp_paymentform_description_filter';
    } else {
        $description = sprintf( __( "Booking for '%s'", 'events-made-easy' ), $item_name );
        $filtername  = 'eme_rsvp_paymentform_description_filter';
    }
    if ( has_filter( $filtername ) ) {
        $description = apply_filters( $filtername, $description, $payment, $gateway );
    }
    return $description;
}

function eme_payment_form( $payment_id, $resultcode = 0, $standalone = 0 ) {
    $ret_string = '';
    $payment    = eme_get_payment( $payment_id );
    if ( empty( $payment ) ) {
        return;
    }
    if ( $payment['target'] == 'member' ) {
        return eme_member_payment_form( $payment_id, $resultcode, $standalone );
    }
    if ( $payment['target'] == 'fs_event' ) {
        return eme_fs_event_payment_form( $payment_id, $resultcode, $standalone );
    }
    return eme_event_payment_form( $payment_id, $resultcode, $standalone );
}

function eme_event_payment_form( $payment_id, $resultcode = 0, $standalone = 0 ) {
    $ret_string = '';
    $payment    = eme_get_payment( $payment_id );

    $bookings = eme_get_bookings_by_paymentid( $payment_id );
    if ( empty( $bookings ) ) {
        return;
    }
    if ( count( $bookings ) == 1 ) {
        $is_multi = 0;
    } else {
        $is_multi = 1;
    }

    $total_price = eme_get_payment_price( $payment_id );

    // we take the currency of the first event in the series
    $booking = $bookings[0];
    $person  = eme_get_person( $booking['person_id'] );
    $event   = eme_get_event( $booking['event_id'] );

    $cur = $event['currency'];

    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    // if on the waiting list, it can't be paid for yet
    if ( $booking['waitinglist'] ) {
        $message = get_option( 'eme_payment_booking_on_waitinglist_format' );
        $message = eme_replace_booking_placeholders( $message, $event, $booking, $is_multi );
        return "<div class='eme-waiting'>" . $message . '</div>';
    }
    // if the booking was userpending, confirm it and set a message
    if ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
        // we arrive here, so: mark all the bookings as confirmed by the user and continue
        $booking_ids = [];
        foreach ( $bookings as $booking ) {
            $booking_ids[] = $booking['booking_id'];
        }
        eme_userconfirm_bookings( $booking_ids, $total_price, $is_multi );

        // reget the bookings, since the status changed
        $bookings = eme_get_bookings_by_paymentid( $payment_id );
        if ( $is_multi ) {
            $ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . __( 'Thank you for confirming these bookings.', 'events-made-easy' ) . '</div>';
        } else {
            $ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . __( 'Thank you for confirming this booking.', 'events-made-easy' ) . '</div>';
        }
    } elseif ( get_query_var( 'eme_rsvp_confirm' ) ) {
        // in case of confirm url and the user revisits the same page (so it is already confirmed), just show the message
        if ( $is_multi ) {
            $ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . __( 'Thank you for confirming these bookings.', 'events-made-easy' ) . '</div>';
        } else {
            $ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . __( 'Thank you for confirming this booking.', 'events-made-easy' ) . '</div>';
        }
    }
    // price 0? return possible message and call it a day
    if ( $total_price == 0 ) {
        return $ret_string;
    }

    // Since payment can happen minutes/hours later, check the free seats before accepting payment
    // this is only relevant for pending bookings that are considered as free (ignore_pending)
    foreach ( $bookings as $booking ) {
        $event = eme_get_event( $booking['event_id'] );
        if ( empty( $event ) ) {
            return "<div class='eme-message-error'>" . __( 'No such event', 'events-made-easy' ) . '</div>';
        }
        // only the case where ignore_pending is active is relevant: a pending booking is considered as free
        //    so we need to check before payment if still enough seats are available again
        if ( ! $event['event_properties']['ignore_pending'] || $booking['status'] != EME_RSVP_STATUS_PENDING ) {
            continue;
        }
        $total_seats = eme_get_total( $event['event_seats'] );
        $event_id    = $event['event_id'];
        if ( $total_seats == 0 ) {
            $seats_available = 1;
        } elseif ( eme_is_multi( $event['event_seats'] ) ) {
            $bookedSeats_mp = eme_convert_multi2array( $booking['booking_seats_mp'] );
            // we check for available seats, excluding waiting list, and excluding this pending booking
            $seats_available = eme_are_multiseats_available_for( $event_id, $bookedSeats_mp, 1, $booking['booking_id'] );
        } else {
            // we check for available seats, excluding waiting list, and excluding this pending booking
            $seats_available = eme_are_seats_available_for( $event_id, $booking['booking_seats'], 1, $booking['booking_id'] );
        }
        if ( ! $seats_available ) {
            return "<div class='eme-message-error'>" . __( 'Between the time of booking and the payment, all available seats have unfortunately all been taken already', 'events-made-easy' ) . '</div>';
        }
    }

    $eme_pg_submit_immediately = 0;
    $hidden_class              = '';
    $pg_in_use                 = '';
    // now: count the payment gateways active for this event
    // if only 1 and the option to immediately submit is set, hide the divs and forms and submit it
    $pg_count = eme_event_count_pgs( $event );
    if ( $pg_count == 1 && get_option( 'eme_pg_submit_immediately' ) ) {
        $eme_pg_submit_immediately = 1;
        $pg_in_use                 = eme_event_get_first_pg( $event );
        if ($pg_in_use != "braintree") { //braintree replaces our form, no need for submit/hidden
            $hidden_class              = 'eme-hidden';
        }
    }

    if ( $resultcode > 0 ) {
        $ret_string .= "<div class='eme-message-error eme-rsvp-message-error'>" . __( 'Payment failed for your booking for #_EVENTNAME, please try again.', 'events-made-easy' ) . '</div>';
    }

    // if not "submit immediately" or standalone: we show the header
    if ( ! $eme_pg_submit_immediately || $standalone ) {
        if ( $is_multi ) {
            $eme_payment_form_header_format = get_option( 'eme_multipayment_form_header_format' );
        } else {
            $eme_payment_form_header_format = get_option( 'eme_payment_form_header_format' );
        }
        if ( ! eme_is_empty_string( $eme_payment_form_header_format ) ) {
            $result      = eme_replace_booking_placeholders( $eme_payment_form_header_format, $event, $booking, $is_multi );
            $ret_string .= "<div id='eme-payment-formtext-header' class='eme-payment-formtext'>";
            $ret_string .= $result;
            $ret_string .= '</div>';
        } else {
            $ret_string     .= "<div id='eme-payment-handling' class='eme-payment-handling'>" . __( 'Payment handling', 'events-made-easy' ) . '</div>';
            $localized_price = eme_localized_price( $total_price, $cur );
            $ret_string     .= "<div id='eme-payment-price-info' class='eme-payment-price-info'>" . sprintf( __( 'The amount to pay is %s', 'events-made-easy' ), $localized_price ) . '</div>';
        }
    }

    // if "submit immediately": we show the button text, since the rest of the div is hidden
    if ( $eme_pg_submit_immediately ) {
        if ($pg_in_use == "braintree") {
            $above_text = "";
        } else {
            $button_above = get_option( 'eme_' . $pg_in_use . '_button_above' );
            $above_text   = eme_replace_payment_gateway_placeholders( $button_above, $pg_in_use, $total_price, $cur, $event['event_properties']['vat_pct'], 'html', $person['lang'] );
        }
        if ( ! eme_is_empty_string( $above_text ) ) {
            $ret_string .= "<div id='eme-payment-formtext-header' class='eme-message-success eme-rsvp-message-success'>";
            $ret_string .= $above_text;
            $ret_string .= '</div>';
        }
    }

    $ret_string .= "<div id='eme-payment-form' class='eme-payment-form $hidden_class'>";
    $pgs         = eme_configured_pgs_descriptions();
    foreach ( $pgs as $pg => $value ) {
        if ( isset($event['event_properties']['payment_gateways']) && in_array($pg, $event['event_properties']['payment_gateways']) ) {
            if ( eme_is_offline_pg( $pg ) ) {
                $eme_offline_format = get_option( 'eme_offline_payment' );
                $result             = eme_replace_booking_placeholders( $eme_offline_format, $event, $booking, $is_multi );
                $ret_string        .= "<div id='eme-payment-offline' class='eme-payment-offline'>";
                $ret_string        .= $result;
                $ret_string        .= '</div>';
            } else {
                $func = 'eme_payment_form_' . $pg ;
                if ( function_exists( $func ) ) {
                    $pg_form     = $func( $event['event_name'], $payment, $total_price, $cur, $is_multi );
                    $ret_string .= eme_replace_payment_gateway_placeholders( $pg_form, $pg, $total_price, $cur, $event['event_properties']['vat_pct'], 'html', $person['lang'] );
                    if ( $eme_pg_submit_immediately && $pg != "braintree" ) { //braintree replaces our form, no need for submit/hidden
                        $waitperiod  = intval( get_option( 'eme_payment_redirect_wait' ) ) * 1000;
                        $ret_string .= '<script type="text/javascript">
                            jQuery(document).ready( function($) {
                                setTimeout(function () {
                                    $( "#eme_' . $pg . '_form" ).submit();
                                }, ' . $waitperiod . ');
                            });</script>';
                    }
                }
            }
        }
    }
    $ret_string .= '</div>';

    if ( ! $eme_pg_submit_immediately || $standalone ) {
        if ( $is_multi ) {
            $eme_payment_form_footer_format = get_option( 'eme_multipayment_form_footer_format' );
        } else {
            $eme_payment_form_footer_format = get_option( 'eme_payment_form_footer_format' );
        }
        if ( ! eme_is_empty_string( $eme_payment_form_footer_format ) ) {
            $result      = eme_replace_booking_placeholders( $eme_payment_form_footer_format, $event, $booking, $is_multi );
            $ret_string .= "<div id='eme-payment-formtext-footer' class='eme-payment-formtext'>";
            $ret_string .= $result;
            $ret_string .= '</div>';
        }
    }

    return $ret_string;
}

function eme_member_payment_form( $payment_id, $resultcode = 0, $standalone = 0 ) {
    if ( $resultcode > 0 ) {
        $ret_string = "<div class='eme-message-error eme-rsvp-message-error'>" . __( 'Payment failed for your membership for #_MEMBERSHIPNAME, please try again.', 'events-made-easy' ) . '</div>';
    } else {
        $ret_string = '';
    }

    $payment     = eme_get_payment( $payment_id );
    $member      = eme_get_member_by_paymentid( $payment_id );
    if (empty($member)) {
        // member has been deleted, but the payment id is still present
        $ret_string = "<div class='eme-message-error eme-rsvp-message-error'>" . __( "No member found linked to this payment. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' ) . '</div>';
        return $ret_string;
    }
    $person      = eme_get_person( $member['person_id'] );
    $total_price = eme_get_member_payment_price( $payment_id );
    $membership  = eme_get_membership( $member['membership_id'] );

    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    } else {
        if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE || $member['status'] == EME_MEMBER_STATUS_GRACE ) {
            $end_date      = eme_localized_date( $member['end_date'], EME_TIMEZONE );
            $next_end_date = eme_get_next_end_date( $membership, $member['end_date'] );
            $next_end_date = eme_localized_date( $next_end_date, EME_TIMEZONE );
            $ret_string   .= "<div class='eme-message-success eme-rsvp-message-success'>" . sprintf( __( 'Your membership is currently active until %s. If you pay the membership fee again, your membership will be extended until %s', 'events-made-easy' ), $end_date, $next_end_date ) . '</div>';
        } elseif ( $member['status'] == EME_MEMBER_STATUS_EXPIRED ) {
            // set the third option to eme_get_start_date to 1, to force a new startdate (only has an effect for rolling-type memberships)
            $new_start_date = eme_get_start_date( $membership, $member, 1 );
            $next_end_date  = eme_get_next_end_date( $membership, $new_start_date );
            $next_end_date  = eme_localized_date( $next_end_date, EME_TIMEZONE );
            $ret_string    .= "<div class='eme-message-success eme-rsvp-message-success'>" . sprintf( __( 'Your membership has expired. If you pay the membership fee again, your membership will be reactivated until %s', 'events-made-easy' ), $next_end_date ) . '</div>';
        }
    }

    $cur = $membership['properties']['currency'];

    $eme_pg_submit_immediately = 0;
    $hidden_class              = '';
    $pg_in_use                 = '';
    // now: count the payment gateways active for this membership
    // if only 1 and the option to immediately submit is set, hide the divs and forms and submit it
    $pg_count = eme_membership_count_pgs( $membership );
    if ( $pg_count == 1 && get_option( 'eme_pg_submit_immediately' ) ) {
        $eme_pg_submit_immediately = 1;
        $pg_in_use                 = eme_membership_get_first_pg( $membership );
        if ($pg_in_use != "braintree") { //braintree replaces our form, no need for submit/hidden
            $hidden_class              = 'eme-hidden';
        }
    }

    // if not "submit immediately" or standalone: we show the header
    if ( ! $eme_pg_submit_immediately || $standalone ) {
        if ( ! eme_is_empty_string( $membership['properties']['payment_form_header_text'] ) ) {
            $eme_payment_form_header_format = $membership['properties']['payment_form_header_text'];
        } elseif ( ! empty( $membership['properties']['payment_form_header_tpl'] ) ) {
            $eme_payment_form_header_format = eme_get_template_format( $membership['properties']['payment_form_header_tpl'] );
        } else {
            $eme_payment_form_header_format = '';
        }
        if ( !eme_is_empty_string( $eme_payment_form_header_format ) ) {
            $result = eme_replace_member_placeholders( $eme_payment_form_header_format, $membership, $member );
            if ( ! eme_is_empty_string( $result ) ) {
                $ret_string .= "<div id='eme-payment-formtext' class='eme-payment-formtext'>";
                $ret_string .= $result;
                $ret_string .= '</div>';
            }
        } else {
            $ret_string     .= "<div id='eme-payment-handling' class='eme-payment-handling'>" . __( 'Payment handling', 'events-made-easy' ) . '</div>';
            $localized_price = eme_localized_price( $total_price, $cur );
            $ret_string     .= "<div id='eme-payment-price-info' class='eme-payment-price-info'>" . sprintf( __( 'The amount to pay is %s', 'events-made-easy' ), $localized_price ) . '</div>';
        }
    }

    // if "submit immediately": we show the button text, since the rest of the div is hidden
    if ( $eme_pg_submit_immediately ) {
        // braintree shows anyway, so hide the button_above
        if ($pg_in_use == "braintree") {
            $above_text = "";
        } else {
            $button_above = get_option( 'eme_' . $pg_in_use . '_button_above' );
            $above_text   = eme_replace_payment_gateway_placeholders( $button_above, $pg_in_use, $total_price, $cur, $membership['properties']['vat_pct'], 'html', $person['lang'] );
        }
        if ( ! eme_is_empty_string( $above_text ) ) {
            $ret_string .= "<div id='eme-payment-formtext-header' class='eme-message-success eme-rsvp-message-success'>";
            $ret_string .= $above_text;
            $ret_string .= '</div>';
        }
    }
    $ret_string .= "<div id='eme-payment-form' class='eme-payment-form $hidden_class'>";
    $is_multi    = 0;
    $pgs         = eme_configured_pgs_descriptions();
    foreach ( $pgs as $pg => $value ) {
        if ( isset($membership['properties']['payment_gateways']) && in_array($pg, $membership['properties']['payment_gateways']) ) {
            if ( eme_is_offline_pg( $pg ) ) {
                if ( ! eme_is_empty_string( $membership['properties']['offline_payment_text'] ) ) {
                    $eme_offline_format = $membership['properties']['offline_payment_text'];
                } else {
                    $eme_offline_format = eme_get_template_format( $membership['properties']['offline_payment_tpl'] );
                }
                $result      = eme_replace_member_placeholders( $eme_offline_format, $membership, $member );
                $ret_string .= "<div id='eme-payment-offline' class='eme-payment-offline'>";
                $ret_string .= $result;
                $ret_string .= '</div>';
            } else {
                $func = 'eme_payment_form_' . $pg ;
                if ( function_exists( $func ) ) {
                    $pg_form     = $func( $membership['name'], $payment, $total_price, $cur, $is_multi );
                    $ret_string .= eme_replace_payment_gateway_placeholders( $pg_form, $pg, $total_price, $cur, $membership['properties']['vat_pct'], 'html', $person['lang'] );
                    if ( $eme_pg_submit_immediately && $pg != "braintree" ) { //braintree replaces our form, no need for submit/hidden
                        $waitperiod  = intval( get_option( 'eme_payment_redirect_wait' ) ) * 1000;
                        $ret_string .= '<script type="text/javascript">
                            jQuery(document).ready( function($) {
                                setTimeout(function () {
                                    $( "#eme_' . $pg . '_form" ).submit();
                                }, ' . $waitperiod . ');
                            });</script>;';
                        //$ret_string .= '<script type="text/javascript">jQuery(document).ready( function($) {$( "#eme_'.$pg.'_form" ).submit();});</script>;';
                    }
                }
            }
        }
    }
    $ret_string .= '</div>';

    if ( ! $eme_pg_submit_immediately || $standalone ) {
        if ( ! eme_is_empty_string( $membership['properties']['payment_form_footer_text'] ) ) {
            $eme_payment_form_footer_format = $membership['properties']['payment_form_footer_text'];
        } elseif ( ! empty( $membership['properties']['payment_form_footer_tpl'] ) ) {
            $eme_payment_form_footer_format = eme_get_template_format( $membership['properties']['payment_form_footer_tpl'] );
        } else {
            $eme_payment_form_footer_format = '';
        }
        if ( ! eme_is_empty_string( $eme_payment_form_footer_format ) ) {
            $result      = eme_replace_member_placeholders( $eme_payment_form_footer_format, $membership, $member );
            $ret_string .= "<div id='eme-payment-formtext' class='eme-payment-formtext'>";
            $ret_string .= $result;
            $ret_string .= '</div>';
        }
    }
    return $ret_string;
}

function eme_fs_event_payment_form( $payment_id, $resultcode = 0, $standalone = 0 ) {
    if ( $resultcode > 0 ) {
        $ret_string = "<div class='eme-message-error eme-rsvp-message-error'>" . __( 'Payment failed for your membership for #_MEMBERSHIPNAME, please try again.', 'events-made-easy' ) . '</div>';
    } else {
        $ret_string = '';
    }

    $payment = eme_get_payment( $payment_id );
    if (empty($payment)) {
        return "<div class='eme-message-error eme-rsvp-message-error'>" . __( 'Nothing linked to this payment id', 'events-made-easy' ) . '</div>';
    }
    $event   = eme_get_event( $payment['related_id'] );
    if (empty($event)) {
        // event has been deleted, but the payment id is still present
        return "<div class='eme-message-error eme-rsvp-message-error'>" . __( "No event found linked to this payment. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' ) . '</div>';
    }

    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    $eme_fs_options = get_option('eme_fs');
    $total_price = $eme_fs_options['price'];
    $cur = $eme_fs_options['currency'] ? $eme_fs_options['currency'] : get_option('eme_default_currency');
    $vat_pct = get_option('eme_default_vat');
    // now: count the payment gateways active for this membership
    // if only 1 and the option to immediately submit is set, hide the divs and forms and submit it
    $pg_count = eme_fs_event_count_pgs( );
    if ( $pg_count == 1 && get_option( 'eme_pg_submit_immediately' ) ) {
        $eme_pg_submit_immediately = 1;
        $hidden_class              = 'eme-hidden';
        $pg_in_use                 = eme_fs_event_get_first_pg( );
    } else {
        $eme_pg_submit_immediately = 0;
        $hidden_class              = '';
        $pg_in_use                 = '';
    }

    // if not "submit immediately" or standalone: we show the header
    if ( ! $eme_pg_submit_immediately || $standalone ) {
        $eme_payment_form_header_format = get_option( 'eme_fs_payment_form_header_format' );
        if ( !eme_is_empty_string( $eme_payment_form_header_format ) ) {
            $result = eme_replace_event_placeholders( $eme_payment_form_header_format, $event );
            if ( ! eme_is_empty_string( $result ) ) {
                $ret_string .= "<div id='eme-payment-formtext' class='eme-payment-formtext'>";
                $ret_string .= $result;
                $ret_string .= '</div>';
            }
        } else {
            $ret_string     .= "<div id='eme-payment-handling' class='eme-payment-handling'>" . __( 'Payment handling', 'events-made-easy' ) . '</div>';
            $localized_price = eme_localized_price( $total_price, $cur );
            $ret_string     .= "<div id='eme-payment-price-info' class='eme-payment-price-info'>" . sprintf( __( 'The amount to pay is %s', 'events-made-easy' ), $localized_price ) . '</div>';
        }
    }

    // if "submit immediately": we show the button text, since the rest of the div is hidden
    if ( $eme_pg_submit_immediately ) {
        $button_above = get_option( 'eme_' . $pg_in_use . '_button_above' );
        $above_text   = eme_replace_payment_gateway_placeholders( $button_above, $pg_in_use, $total_price, $cur, $vat_pct );
        if ( !eme_is_empty_string( $above_text ) ) {
            $ret_string .= "<div id='eme-payment-formtext-header' class='eme-message-success eme-rsvp-message-success'>";
            $ret_string .= $above_text;
            $ret_string .= '</div>';
        }
    }
    $ret_string .= "<div id='eme-payment-form' class='eme-payment-form $hidden_class'>";
    $is_multi    = 0;
    $pgs         = eme_configured_pgs_descriptions();
    foreach ( $pgs as $pg => $value ) {
        if ( isset($eme_fs_options['payment_gateways']) && in_array($pg, $eme_fs_options['payment_gateways']) ) {
            if ( eme_is_offline_pg( $pg ) ) {
                $eme_offline_format = get_option( 'eme_offline_payment' );
                $result      = eme_replace_event_placeholders( $eme_offline_format, $event );
                $ret_string .= "<div id='eme-payment-offline' class='eme-payment-offline'>";
                $ret_string .= $result;
                $ret_string .= '</div>';
            } else {
                $func = 'eme_payment_form_' . $pg ;
                if ( function_exists( $func ) ) {
                    $pg_form     = $func( $event['event_name'], $payment, $total_price, $cur, $is_multi );
                    $ret_string .= eme_replace_payment_gateway_placeholders( $pg_form, $pg, $total_price, $cur, $vat_pct );
                    if ( $eme_pg_submit_immediately ) {
                        $waitperiod  = intval( get_option( 'eme_payment_redirect_wait' ) ) * 1000;
                        $ret_string .= '<script type="text/javascript">
                            jQuery(document).ready( function($) {
                                setTimeout(function () {
                                    $( "#eme_' . $pg . '_form" ).submit();
                                }, ' . $waitperiod . ');
                            });</script>;';
                        //$ret_string .= '<script type="text/javascript">jQuery(document).ready( function($) {$( "#eme_'.$pg.'_form" ).submit();});</script>;';
                    }
                }
            }
        }
    }
    $ret_string .= '</div>';

    if ( ! $eme_pg_submit_immediately || $standalone ) {
        $eme_payment_form_footer_format = get_option( 'eme_fs_payment_form_footer_format' );
        if ( !eme_is_empty_string( $eme_payment_form_footer_format ) ) {
            $result      = eme_replace_event_placeholders( $eme_payment_form_footer_format, $event );
            $ret_string .= "<div id='eme-payment-formtext' class='eme-payment-formtext'>";
            $ret_string .= $result;
            $ret_string .= '</div>';
        }
    }
    return $ret_string;
}

function eme_payment_allowed_to_pay( $payment_id ) {
    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $payment = eme_get_payment ( $payment_id );
    if ( $payment['target'] == 'member' ) {
        $member      = eme_get_member_by_paymentid( $payment_id );
        $membership  = eme_get_membership( $member['membership_id'] );
        return eme_check_member_allowed_to_pay( $member, $membership );
    } elseif ( $payment['target'] == 'fs_event' ) {
        // events are simple :-)
        $event = eme_get_event( $payment['related_id'] );
        if (empty($payment['pg_handled']) && $event['event_status'] == EME_EVENT_STATUS_FS_DRAFT ) {
            return 0;
        } else { 
            $message = get_option( 'eme_payment_booking_already_paid_format' );
            return "<div class='eme-already-paid'>" . $message . '</div>';
        }
    } else {
        $payment_paid = eme_get_payment_paid( $payment );
        if ( $payment_paid ) {
            $message = get_option( 'eme_payment_booking_already_paid_format' );
            return "<div class='eme-already-paid'>" . $message . '</div>';
        }
    }
    return 0;
}

function eme_payment_gateway_total( $price, $cur, $gateway ) {
    $price                          += eme_payment_gateway_extra_charge( $price, $gateway );
    $eme_zero_decimal_currencies_arr = eme_zero_decimal_currencies();
    if ( in_array( $cur, $eme_zero_decimal_currencies_arr ) ) {
        $price = intval( $price );
    } else {
        if ( $gateway == 'stripe' ) {
            $price *= 100;
        }
        if ( $gateway == 'payconiq' ) {
            $price *= 100;
        }
        if ( $gateway == 'fondy' ) {
            $price *= 100;
        }
    }
    if ( has_filter( 'eme_payment_gateway_change_total' ) ) {
        $price = apply_filters( 'eme_payment_gateway_change_total', $price, $gateway );
    }
    return $price;
}

function eme_payment_gateway_extra_charge( $price, $gateway ) {
    if ( empty( $gateway ) ) {
        return 0;
    }

    $extra  = get_option( 'eme_' . $gateway . '_cost' );
    $result = 0;
    if ( $extra ) {
        if ( strstr( $extra, '%' ) ) {
            $extra   = floatval(str_replace( '%', '', $extra ));
            $result += sprintf( '%01.2f', $price * $extra / 100 );
        } else {
            $result += sprintf( '%01.2f', $extra );
        }
    }
    $extra = get_option( 'eme_' . $gateway . '_cost2' );
    if ( $extra ) {
        if ( strstr( $extra, '%' ) ) {
            $extra   = floatval(str_replace( '%', '', $extra ));
            $result += sprintf( '%01.2f', $price * $extra / 100 );
        } else {
            $result += sprintf( '%01.2f', $extra );
        }
    }
    if ( has_filter( 'eme_payment_gateway_extra_cost' ) ) {
        $result += apply_filters( 'eme_payment_gateway_extra_cost', $price, $gateway );
    }
    return $result;
}

function eme_payment_form_webmoney( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway            = 'webmoney';
    $eme_webmoney_purse = get_option( 'eme_webmoney_purse' );
    if ( ! $eme_webmoney_purse ) {
        return;
    }

    $price             = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link  = eme_get_events_page();
    $payment_id        = $payment['id'];
    $success_link      = eme_payment_return_url( $payment, 0 );
    $fail_link         = eme_payment_return_url( $payment, 1 );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    // webmoney api does this itself
    // $button_label=htmlentities($button_label);
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    require_once 'payment_gateways/webmoney/webmoney.inc.php';
    $wm_request                 = new WM_Request();
    $wm_request->payment_amount = $price;
    $wm_request->payment_desc   = $description;
    $wm_request->payment_no     = $payment_id;
    $wm_request->payee_purse    = $eme_webmoney_purse;
    $wm_request->success_method = WM_POST;
    $wm_request->result_url     = $notification_link;
    $wm_request->success_url    = $success_link;
    $wm_request->fail_url       = $fail_link;
    if ( get_option( 'eme_webmoney_demo' ) ) {
        $wm_request->sim_mode = WM_ALL_SUCCESS;
    }
    $wm_request->btn_label = $button_label;
    $wm_request->form_id   = 'eme_webmoney_form';
    if ( ! empty( $button_img_url ) ) {
        $wm_request->btn_img_url = $button_img_url;
    }

    $form_html  = $button_above;
    $form_html .= $wm_request->SetForm( false );
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_2co( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway          = '2co';
    $eme_2co_business = get_option( 'eme_2co_business' );
    $eme_2co_secret = get_option( 'eme_2co_secret' );
    $eme_2co_buylinksecret = get_option( 'eme_2co_buylinksecret' );
    if ( ! $eme_2co_business || !$eme_2co_secret || !$eme_2co_buylinksecret ) {
        return;
    }

    $price             = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link  = eme_get_events_page();
    $payment_id        = $payment['id'];
    $success_link      = eme_payment_return_url( $payment, 0 );
    $fail_link         = eme_payment_return_url( $payment, 1 );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );
    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities($button_label);
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
        <input type='hidden' name='payment_id' value='$payment_id'>
        <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
        <input type='hidden' name='description' value='$description'>
        <input type='hidden' name='price' value='$price'>
        <input type='hidden' name='cur' value='$cur'>
";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_worldpay( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway             = 'worldpay';
    $worldpay_instid     = get_option( 'eme_worldpay_instid' );
    $worldpay_md5_secret = get_option( 'eme_worldpay_md5_secret' );
    if ( ! $worldpay_instid ) {
        return;
    }

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];
    // $success_link = eme_payment_return_url($payment,0);
    // $fail_link = eme_payment_return_url($payment,1);
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    if ( get_option( 'eme_worldpay_demo' ) == 1 ) {
        $url = 'https://secure-test.worldpay.com/wcc/purchase';
    } else {
        $url = 'https://secure.worldpay.com/wcc/purchase';
    }
    $quantity = 1;

    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='$url' method='post' name='eme_worldpay_form' id='eme_worldpay_form'>";
    $form_html .= "<input type='hidden' name='instId' value='$worldpay_instid'>";
    $form_html .= "<input type='hidden' name='cartId' value='$payment_id'>";
    $form_html .= "<input type='hidden' name='desc' value='$description'>";
    $form_html .= "<input type='hidden' name='amount' value='$price'>";
    $form_html .= "<input type='hidden' name='currency' value='$cur'>";
    // for worldpay notifications to work: enable dynamic payment response in your worldpay setup, using the param MC_callback
    // also: set the Payment Response password and if wanted, the MD5 secret and field combo
    $form_html .= "<input type='hidden' name='MC_callback' value='$notification_link'>";

    if ( $worldpay_md5_secret ) {
        require_once 'payment_gateways/worldpay/eme-worldpay.php';
        $params_arr = explode( ':', get_option( 'eme_worldpay_md5_parameters' ) );
        $signature  = eme_generate_worldpay_signature( $worldpay_md5_secret, $params_arr, $worldpay_instid, $payment_id, $cur, $price );
        $form_html .= "<input type='hidden' name='signature' value='$signature'>";
    }

    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' alt='$button_label' title='$button_label' src='$button_img_url' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    if ( get_option( 'eme_worldpay_demo' ) == 1 ) {
        $form_html .= "<input type='hidden' name='testMode' value='100'>";
    }
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_opayo( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway     = 'opayo';
    $vendor_name = get_option( 'eme_opayo_vendor_name' );
    if ( ! $vendor_name ) {
        return;
    }

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];
    $success_link     = eme_payment_return_url( $payment, 0 );
    $fail_link        = eme_payment_return_url( $payment, 1 );
    // opayo doesn't use a notification url, but sends the status along as part of the return url
    // so we add the notification info to it too, so we can process paid info as usual
    $success_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $success_link );
    $fail_link    = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $fail_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // the live or sandbox url
    $opayo_demo = get_option( 'eme_opayo_demo' );
    if ( $opayo_demo == 1 ) {
        $opayo_pwd = get_option( 'eme_opayo_test_pwd' );
        $url       = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
    } else {
        $opayo_pwd = get_option( 'eme_opayo_live_pwd' );
        $url       = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
    }

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $person = [];
    if ( $payment['target'] == 'member' ) {
        $member = eme_get_member_by_paymentid( $payment_id );
        if ( $member ) {
            $person = eme_get_person( $member['person_id'] );
        }
    } elseif ( $payment['target'] == 'fs_event' ) {
        $person = eme_new_person();
    } else {
        $booking_ids = eme_get_payment_booking_ids( $payment_id );
        if ( $booking_ids ) {
            $booking = eme_get_booking( $booking_ids[0] );
            $person  = eme_get_person( $booking['person_id'] );
        }
    }
    if ( empty( $person ) ) {
        $person = eme_new_person();
    }

    $query = [
        'VendorTxCode'       => $payment_id,
        'Amount'             => number_format( $price, 2, '.', '' ),
        'Currency'           => $cur,
        'Description'        => $description,
        'SuccessURL'         => $success_link,
        'FailureURL'         => $fail_link,
        'BillingSurname'     => $person['lastname'],
        'BillingFirstnames'  => $person['firstname'],
        'BillingAddress1'    => $person['address1'],
        'BillingCity'        => $person['city'],
        'BillingPostCode'    => $person['zip'],
        'BillingState'       => $person['state_code'],
        'BillingCountry'     => $person['country_code'],
        'DeliverySurname'    => $person['lastname'],
        'DeliveryFirstnames' => $person['firstname'],
        'DeliveryAddress1'   => $person['address1'],
        'DeliveryCity'       => $person['city'],
        'DeliveryPostCode'   => $person['zip'],
        'DeliveryState'      => $person['state_code'],
        'DeliveryCountry'    => $person['country_code'],
    ];

    require_once 'payment_gateways/opayo/eme-opayo-util.php';
    $crypt = SagepayUtil::encryptAes( SagepayUtil::arrayToQueryString( $query ), $opayo_pwd );

    $form_html  = $button_above;
    $form_html .= "<form action='$url' method='post' name='eme_opayo_form' id='eme_opayo_form'>";
    $form_html .= "<input type='hidden' name='VPSProtocol' value='3.00'>";
    $form_html .= "<input type='hidden' name='TxType' value='PAYMENT'>";
    $form_html .= "<input type='hidden' name='Vendor' value='$vendor_name'>";
    $form_html .= "<input type='hidden' name='Crypt' value='$crypt'>";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_braintree( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway                   = 'braintree';
    $eme_braintree_private_key = get_option( 'eme_braintree_private_key' );
    $eme_braintree_public_key  = get_option( 'eme_braintree_public_key' );
    $eme_braintree_merchant_id = get_option( 'eme_braintree_merchant_id' );
    $eme_braintree_env         = get_option( 'eme_braintree_env' );
    if ( empty($eme_braintree_public_key) || empty($eme_braintree_private_key) || empty($eme_braintree_merchant_id) ) {
        return;
    }

    require_once 'payment_gateways/braintree/vendor/autoload.php';
    $braintree_gateway = new Braintree\Gateway(
        [
            'environment' => $eme_braintree_env,
            'merchantId'  => $eme_braintree_merchant_id,
            'publicKey'   => $eme_braintree_public_key,
            'privateKey'  => $eme_braintree_private_key,
        ]
    );
    $clientToken       = $braintree_gateway->clientToken()->generate();

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];

    $quantity = 1;

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
        <div id='braintree-payment-form-div'></div>
        <input type='hidden' name='payment_id' value='$payment_id'>
        <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
        <input type='hidden' name='eme_multibooking' value='$multi_booking'>
        <input type='hidden' name='eme_{$gateway}_nonce' id='eme_{$gateway}_nonce'>
        <input type='hidden' name='price' value='$price'>
        <input type='hidden' name='cur' value='$cur'>
";
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );

    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' alt='$button_label' title='$button_label' src='$button_img_url' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='button' value='$button_label' id='braintree_submit_button' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= '</form>
        <script>
        jQuery(document).ready(function ($) {
            $.getScript("https://js.braintreegateway.com/web/dropin/1.33.0/js/dropin.min.js",function () {
                var clientToken = "' . $clientToken . '";
                braintree.dropin.create({
                    authorization: clientToken,
                    container: "#braintree-payment-form-div",
                }, function (createErr, instance) {
                    if (createErr) {
                        console.log("Create Error", createErr);
                        return;
                    }
                    $("#braintree_submit_button").on("click", function() {
                        instance.requestPaymentMethod(function (err, payload) {
                            if (err) {
                                console.log("Request Payment Method Error", err);
                                return;
                            }
                            // Add the nonce to the form and submit
                            $("#braintree_nonce").val(payload.nonce);
                            $("#eme_braintree_form").submit();
                        });
                    });
                });
            });
        });
        </script>
   ';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_sumup( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway                 = 'sumup';
    $eme_sumup_app_id        = get_option( 'eme_sumup_app_id' );
    $eme_sumup_app_secret    = get_option( 'eme_sumup_app_secret' );
    $eme_sumup_merchant_code = get_option( 'eme_sumup_merchant_code' );
    if ( empty( $eme_sumup_app_id ) ) {
        return;
    }

    $price             = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link  = eme_get_events_page();
    $payment_id        = $payment['id'];
    $return_link       = eme_payment_return_url( $payment, $gateway );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    // webmoney api does this itself
    // $button_label=htmlentities($button_label);
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    require_once 'payment_gateways/sumup/vendor/autoload.php';
    try {
        $sumup       = new \SumUp\SumUp(
            [
                'app_id'     => $eme_sumup_app_id,
                'app_secret' => $eme_sumup_app_secret,
                'grant_type' => 'client_credentials',
                'scopes'     => [ 'payments' ],
            ]
        );
        $accessToken = $sumup->getAccessToken();
        $value       = $accessToken->getValue();

        $checkoutService = $sumup->getCheckoutService();
        // first check if checkout doesn't exist already
        $checkoutResponse = $checkoutService->findByReferenceId( $payment['id'] );
        $checkoutbody     = $checkoutResponse->getBody();
        if ( ! empty( $checkoutbody ) && is_array( $checkoutbody ) && isset( $checkoutbody[0] ) ) {
            $checkoutbody = $checkoutbody[0];
        }
        if ( empty( $checkoutbody ) ) {
            $checkoutResponse = $checkoutService->create( $price, $cur, $payment['id'], $eme_sumup_merchant_code, $description, $notification_link, $return_link );
            $checkoutbody     = $checkoutResponse->getBody();
            if ( is_array( $checkoutbody ) ) {
                $checkoutbody = $checkoutbody[0];
            }
        } elseif ( $checkoutbody->status == 'PAID' ) {
                return 'already paid';
        }
        $checkoutId = $checkoutbody->id;
        //  pass the $chekoutId to the front-end to be processed
    } catch ( \SumUp\Exceptions\SumUpAuthenticationException $e ) {
        return 'SumUp Authentication error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpResponseException $e ) {
        return 'SumUp Response error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpSDKException $e ) {
        return 'SumUp SDK error: ' . $e->getMessage();
    }

    eme_update_payment_pg_pid( $payment['id'], $checkoutId );

    $form_html  = $button_above;
    $form_html .= '
    <div id="sumup-card"></div>
    <script type="text/javascript" src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js" ></script>
    <script type="text/javascript">
        SumUpCard.mount({
            checkoutId: "' . $checkoutId . '",
            onResponse: function (type, body) {
                if (type == "success" || type == "error") {
                    window.location.href = body.redirect_url;
                }
            },
        });
    </script>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_stripe( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway               = 'stripe';
    $eme_stripe_public_key = get_option( 'eme_stripe_public_key' );
    if ( ! $eme_stripe_public_key ) {
        return;
    }

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // gateway doesn't like the single quotes
    $description = str_replace( "'", '', $description );
    $description = eme_esc_html( $description );

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
   <input type='hidden' name='payment_id' value='$payment_id'>
   <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
   <input type='hidden' name='description' value='$description'>
   <input type='hidden' name='price' value='$price'>
   <input type='hidden' name='cur' value='$cur'>
   ";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_fdgg( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway       = 'fdgg';
    $store_name    = get_option( 'eme_fdgg_store_name' );
    $shared_secret = get_option( 'eme_fdgg_shared_secret' );
    if ( ! $store_name ) {
        return;
    }

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];
    $success_link     = eme_payment_return_url( $payment, 0 );
    $fail_link        = eme_payment_return_url( $payment, 1 );

    // we add the next lines to be conform with the others, but fdgg ignores the description
    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );
    $description = eme_esc_html( $description );

    // the live or sandbox url
    $mode = get_option( 'eme_fdgg_url' );
    if ( preg_match( '/sandbox|merchanttest/', $mode ) ) {
            $url = 'https://connect.merchanttest.firstdataglobalgateway.com/IPGConnect/gateway/processing';
    } else {
            $url = 'https://connect.firstdataglobalgateway.com/IPGConnect/gateway/processing';
    }

    $quantity  = 1;
    $datetime  = eme_localized_date( $payment['creation_date'], EME_TIMEZONE, 'Y:m:d-H:i:s' );
    $cur_codes = eme_currency_codes();
    $cur_code  = $cur_codes[ $cur ];

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    require_once 'payment_gateways/fdgg/fdgg-util_sha2.php';
    $hash       = fdgg_createHash( $store_name . $datetime . $price . $cur_code . $shared_secret );
    $form_html  = $button_above;
    $form_html .= "<form action='$url' method='post' name='eme_fdgg_form' id='eme_fdgg_form'>";
    $form_html .= "<input type='hidden' name='timezone' value='EME_TIMEZONE'>";
    $form_html .= "<input type='hidden' name='authenticateTransaction' value='false'>";
    $form_html .= "<input type='hidden' name='txntype' value='sale'>";
    $form_html .= "<input type='hidden' name='mode' value='payonly'>";
    $form_html .= "<input type='hidden' name='trxOrigin' value='ECI'>";
    $form_html .= "<input type='hidden' name='txndatetime' value='$datetime'>";
    $form_html .= "<input type='hidden' name='hash_algorithm' value='SHA512'>";
    $form_html .= "<input type='hidden' name='hash' value='$hash'>";
    $form_html .= "<input type='hidden' name='storename' value='$store_name'>";
    $form_html .= "<input type='hidden' name='chargetotal' value='$price'>";
    $form_html .= "<input type='hidden' name='subtotal' value='$price'>";
    $form_html .= "<input type='hidden' name='invoicenumber' value='$payment_id'>";
    $form_html .= "<input type='hidden' name='oid' value='$payment_id'>";
    $form_html .= "<input type='hidden' name='responseSuccessURL' value='$success_link'>";
    $form_html .= "<input type='hidden' name='responseFailURL' value='$fail_link'>";
    $form_html .= "<input type='hidden' name='eme_eventAction' value='{$gateway}_notification'>";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_instamojo( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway              = 'instamojo';
    $instamojo_key        = get_option( 'eme_instamojo_key' );
    $instamojo_auth_token = get_option( 'eme_instamojo_auth_token' );
    if ( ! $instamojo_key || ! $instamojo_auth_token ) {
        return;
    }

    $price             = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link  = eme_get_events_page();
    $payment_id        = $payment['id'];
    $return_link       = eme_payment_return_url( $payment, $gateway );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );
    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
   <input type='hidden' name='payment_id' value='$payment_id'>
   <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
   <input type='hidden' name='description' value='$description'>
   <input type='hidden' name='price' value='$price'>
   <input type='hidden' name='cur' value='$cur'>
   ";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= $button_below;
    $form_html .= '</form>';
    return $form_html;
}

function eme_payment_form_mollie( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway        = 'mollie';
    $mollie_api_key = get_option( 'eme_mollie_api_key' );
    if ( ! $mollie_api_key ) {
        return;
    }

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // gateway doesn't like the single quotes
    $description = str_replace( "'", '', $description );
    $description = eme_esc_html( $description );

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
   <input type='hidden' name='payment_id' value='$payment_id'>
   <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
   <input type='hidden' name='description' value='$description'>
   <input type='hidden' name='price' value='$price'>
   <input type='hidden' name='cur' value='$cur'>
   ";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_payconiq( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway = 'payconiq';
    $api_key = get_option( 'eme_payconiq_api_key' );
    if ( ! $api_key ) {
        return;
    }

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // gateway doesn't like the single quotes
    $description = str_replace( "'", '', $description );
    $description = eme_esc_html( $description );

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
   <input type='hidden' name='payment_id' value='$payment_id'>
   <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
   <input type='hidden' name='description' value='$description'>
   <input type='hidden' name='price' value='$price'>
   <input type='hidden' name='cur' value='$cur'>
   ";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_paypal( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway             = 'paypal';
    $eme_paypal_clientid = get_option( 'eme_paypal_clientid' );
    $eme_paypal_secret   = get_option( 'eme_paypal_secret' );
    if ( ! $eme_paypal_clientid || ! $eme_paypal_secret ) {
        return;
    }

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];
    // $success_link = eme_payment_return_url($payment,'paypal');
    // $fail_link = eme_payment_return_url($payment,1);
    // $notification_link = add_query_arg(array('eme_eventAction'=>'paypal_notification'),$events_page_link);

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
   <input type='hidden' name='payment_id' value='$payment_id'>
   <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
   <input type='hidden' name='description' value='$description'>
   <input type='hidden' name='price' value='$price'>
   <input type='hidden' name='cur' value='$cur'>
   ";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_legacypaypal( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway             = 'legacypaypal';
    $eme_paypal_business = get_option( 'eme_legacypaypal_business' );
    if ( ! $eme_paypal_business ) {
        return;
    }

    $quantity          = 1;
    $price             = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link  = eme_get_events_page();
    $payment_id        = $payment['id'];
    $success_link      = eme_payment_return_url( $payment, 0 );
    $cancel_link       = eme_payment_url( $payment );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    $description = eme_esc_html( $description );

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );

    require_once 'payment_gateways/paypal_legacy/Paypal.php';
    $p = new Paypal();

    // the paypal or paypal sandbox url
    $mode = get_option( 'eme_legacypaypal_url' );
    if ( preg_match( '/sandbox/', $mode ) ) {
            $p->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    } else {
            $p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
    }

    // the timeout in seconds before the button form is submitted to paypal
    // this needs the included addevent javascript function
    // 0 = no delay
    // false = disable auto submission
    $p->timeout = false;

    // the button label
    // false to disable button (if you want to rely only on the javascript auto-submission) not recommended
    $p->form_id = 'eme_legacypaypal_form';
    $p->button  = $button_label;
    if ( ! empty( $button_img_url ) ) {
        $p->button_img_url = $button_img_url;
    }

    // no encryption, use the new paypal for that
    $p->encrypt = false;

    // the actual button parameters
    // https://www.paypal.com/IntegrationCenter/ic_std-variable-reference.html
    $p->add_field( 'charset', 'utf-8' );
    $p->add_field( 'business', $eme_paypal_business );
    $p->add_field( 'return', $success_link );
    $p->add_field( 'cancel_return', $cancel_link );
    $p->add_field( 'notify_url', $notification_link );
    $p->add_field( 'item_name', $description );
    $p->add_field( 'item_number', $payment_id );
    $p->add_field( 'custom', $payment_id );
    $p->add_field( 'currency_code', $cur );
    $p->add_field( 'amount', $price );
    $p->add_field( 'quantity', $quantity );
    $p->add_field( 'no_shipping', 1 );
    if ( get_option( 'eme_legacypaypal_no_tax' ) ) {
        $p->add_field( 'tax', 0 );
    }

    $form_html  = $button_above;
    $form_html .= $p->get_button();
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_mercadopago( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway = 'mercadopago';
    if ( get_option( 'eme_mercadopago_demo' ) == 1 ) {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_sandbox_token' );
    } else {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_live_token' );
    }
    if ( ! $eme_mercadopago_access_token ) {
        return;
    }
    $payment_id        = $payment['id'];
    $events_page_link  = eme_get_events_page();
    $success_link      = eme_payment_return_url( $payment, 0 );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    require_once 'payment_gateways/mercadopago/vendor/autoload.php';
    MercadoPago\SDK::setAccessToken( $eme_mercadopago_access_token );

    $payment_id = $payment['id'];
    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // gateway doesn't like the single quotes
    $description = str_replace( "'", '', $description );
    $item_name = esc_attr( get_option( 'blog_name' ) );
    if ( empty( $item_name ) ) {
        $item_name = $description;
    }

    $description = eme_esc_html( $description );
    $item_name   = eme_esc_html( $item_name );

    $price            = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $events_page_link = eme_get_events_page();
    $payment_id       = $payment['id'];
    $quantity         = 1;

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( 'eme_' . $gateway . '_button_img_url' );
    if ( ! empty( $button_img_url ) ) {
        $data_logo = "data-logo='$button_img_url'";
    } else {
        $data_logo = '';
    }

    $locale_code             = determine_locale();
    $locale_code             = preg_replace( '/_/', '-', $locale_code );
    $button_price            = eme_localized_price( $price, $cur );
    $button_text_inside_form = eme_esc_html( sprintf( __( 'Pay %s', 'events-made-easy' ), $button_price ) );
    $form_html               = $button_above;

    // Create a preference object
    $preference = new MercadoPago\Preference();

    // Create a preference item
    $item              = new MercadoPago\Item();
    $item->title       = $item_name;
    $item->description = $description;
    $item->quantity    = 1;
    $item->currency_id = $cur;
    $item->unit_price  = $price;
    $preference->items = [ $item ];

    $preference->external_reference = $payment_id;
    $preference->notification_url   = $notification_link;
    $preference->binary_mode        = true;
    $res                            = $preference->save();

    if ( ! $res ) {
        $form_html .= '<br>' . __( 'Mercado Pago API returned an error: ', 'events-made-easy' ) . eme_esc_html( $preference->Error() );
    } else {
        $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>
           <script src='https://www.mercadopago.com.ar/integrations/v1/web-payment-checkout.js' data-preference-id='" . $preference->id . "' data-button-label='$button_label'></script>
           <input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>
           ";
        $form_html .= '</form>';
    }
    $form_html .= $button_below;
    return $form_html;
}

function eme_payment_form_fondy( $item_name, $payment, $baseprice, $cur, $multi_booking = 0 ) {
    $gateway = 'fondy';

    $merchant_id = get_option( "eme_{$gateway}_merchant_id" );
    $secret_key  = get_option( "eme_{$gateway}_secret_key" );
    if ( ! $merchant_id || ! $secret_key ) {
        return;
    }

    $payment_id = $payment['id'];
    $description = eme_get_payment_desc( $item_name, $payment, $gateway, $multi_booking );

    // gateway doesn't like the single quotes
    $description = str_replace( "'", '', $description );
    $description = eme_esc_html( $description );

    $price      = eme_payment_gateway_total( $baseprice, $cur, $gateway );
    $payment_id = $payment['id'];

    $button_above = get_option( 'eme_' . $gateway . '_button_above' );
    $button_label = get_option( 'eme_' . $gateway . '_button_label' );
    if ( empty( $button_label ) ) {
        $button_label = $gateway;
    }
    $button_label   = htmlentities( $button_label );
    $button_below   = get_option( 'eme_' . $gateway . '_button_below' );
    $button_img_url = get_option( "eme_[$gateway}_button_img_url" );
    if ( ! empty( $button_img_url ) ) {
        $data_logo = "data-logo='$button_img_url'";
    } else {
        $data_logo = '';
    }

    $form_html  = $button_above;
    $form_html .= "<form action='' method='post' name='eme_{$gateway}_form' id='eme_{$gateway}_form'>";
    $form_html .= "<input type='hidden' name='payment_id' value='$payment_id'>";
    $form_html .= "<input type='hidden' name='eme_eventAction' value='{$gateway}_charge'>";
    $form_html .= "<input type='hidden' name='description' value='$description'>";
    $form_html .= "<input type='hidden' name='price' value='$price'>";
    $form_html .= "<input type='hidden' name='cur' value='$cur'>";
    if ( ! empty( $button_img_url ) ) {
        $form_html .= "<input type='image' src='$button_img_url' alt='$button_label' title='$button_label' class='button-primary eme_submit_button'><br>";
    } else {
        $form_html .= "<input type='submit' value='$button_label' class='button-primary eme_submit_button'><br>";
    }
    $form_html .= wp_nonce_field( "$price$cur", "eme_{$gateway}_nonce", false, false );
    $form_html .= '</form>';
    $form_html .= $button_below;
    return $form_html;
}

function eme_complete_transaction_instamojo( $payment ) {
    $gateway              = 'instamojo';
    $instamojo_key        = get_option( 'eme_instamojo_key' );
    $instamojo_auth_token = get_option( 'eme_instamojo_auth_token' );
    require_once 'payment_gateways/instamojo/vendor/autoload.php';
    $mode = get_option( 'eme_instamojo_env' );
    if ( preg_match( '/sandbox/', $mode ) ) {
            $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token, 'https://test.instamojo.com/api/1.1/' );
    } else {
            $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token );
    }

    $instamojo_requestid = eme_sanitize_request( $_GET['payment_request_id'] );
    $instamojo_paymentid = eme_sanitize_request( $_GET['payment_id'] );
    // small sanity check
    $payment_requestid = $payment['pg_pid'];
    if ( $instamojo_requestid != $payment_requestid ) {
        return;
    }
    // now check the state
    try {
        $response = $api->paymentRequestPaymentStatus( $instamojo_requestid, $instamojo_paymentid );
        if ( $response['payment']['status'] == 'Credit' ) {
            eme_mark_payment_paid( $payment['id'], 1, $gateway, $instamojo_requestid );
        }
    } catch ( Exception $e ) {
        return;
    }
}

function eme_complete_transaction_sumup( $payment ) {
    $gateway                 = 'sumup';
    $eme_sumup_app_id        = get_option( 'eme_sumup_app_id' );
    $eme_sumup_app_secret    = get_option( 'eme_sumup_app_secret' );
    $eme_sumup_merchant_code = get_option( 'eme_sumup_merchant_code' );
    if ( empty( $eme_sumup_app_id ) ) {
        return;
    }

    require_once 'payment_gateways/sumup/vendor/autoload.php';
    try {
            $sumup = new \SumUp\SumUp(
                [
                    'app_id'     => $eme_sumup_app_id,
                    'app_secret' => $eme_sumup_app_secret,
                    'grant_type' => 'client_credentials',
                    'scopes'     => [ 'payments', 'transactions.history' ],
                    //'scopes'      => ['transactions.history']
                ]
            );
            $accessToken = $sumup->getAccessToken();
            $value       = $accessToken->getValue();
    } catch ( \SumUp\Exceptions\SumUpAuthenticationException $e ) {
            echo 'Authentication error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpResponseException $e ) {
            echo 'Response error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpSDKException $e ) {
            echo 'SumUp SDK error: ' . $e->getMessage();
    }

    $checkout_id      = $payment['pg_pid'];
    $checkoutService  = $sumup->getCheckoutService();
    $checkoutResponse = $checkoutService->findById( $checkout_id );
    $checkoutbody     = $checkoutResponse->getBody();
    if ( ! empty( $checkoutbody ) && is_array( $checkoutbody ) && isset( $checkoutbody[0] ) ) {
        $checkoutbody = $checkoutbody[0];
    }
    if ( $checkoutbody->status == 'PAID' ) {
        eme_mark_payment_paid( $payment['id'], 1, $gateway, $checkout_id );
    }
    //if ($transaction->status=="SUCCESSFUL") {
    //$transactionService = $sumup->getTransactionService();
    //$transaction = $transactionService->findById($transaction_id);

    //if ($transaction->status=="SUCCESSFUL") {
    //     eme_mark_payment_paid($payment["id"],1,"sumup",$transaction_id);
    //  }
}

function eme_complete_transaction_stripe( $payment ) {
    $gateway = 'stripe';
    require_once 'payment_gateways/stripe/init.php';
    $eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
    \Stripe\Stripe::setApiKey( "$eme_stripe_private_key" );

    $stripe_sessionid = $payment['pg_pid'];
    $stripe_session   = \Stripe\Checkout\Session::retrieve( $stripe_sessionid );
    // we don't trust the payment given, we use the one in the session from stripe
    $payment_id   = $stripe_session->client_reference_id;
    $stripe_pi_id = $stripe_session->payment_intent;
    if ( $payment_id == $payment['id'] ) {
        try {
            $stripe_pi = \Stripe\PaymentIntent::retrieve( $stripe_pi_id );
        } catch ( Exception $e ) {
            return;
        }
        if ( $stripe_pi->status == 'succeeded' ) {
            // we store the payment intent id, so we can refund later
            eme_mark_payment_paid( $payment_id, 1, $gateway, $stripe_pi_id );
        }
    }
}

function eme_complete_transaction_paypal( $payment ) {
    $gateway = 'paypal';
    require_once 'payment_gateways/paypal/vendor/autoload.php';
    // the paypal or paypal sandbox url
    $mode = get_option( 'eme_paypal_url' );
    if ( preg_match( '/sandbox/', $mode ) ) {
            require_once 'payment_gateways/paypal/client_sandbox.php';
            $client = PayPalClient::client();
    } else {
            require_once 'payment_gateways/paypal/client_prod.php';
            $client = PayPalClient::client();
    }

    $paypal_orderid = $payment['pg_pid'];
    $request        = new \PayPalCheckoutSdk\Orders\OrdersCaptureRequest( $paypal_orderid );
    $request->prefer( 'return=representation' );
    try {
        // Call API with your client and get a response for your call
        $response = $client->execute( $request );
        if ( $response->result->status == 'COMPLETED' ) {
            // we store the capture id to be able to refund (but set the order id as default)
            $capture_id = $paypal_orderid;
            // we do a foreach, but there's only one anyway ...
            foreach ( $response->result->purchase_units as $purchase_unit ) {
                foreach ( $purchase_unit->payments->captures as $capture ) {
                    $capture_id = $capture->id;
                }
            }
            eme_mark_payment_paid( $payment['id'], 1, $gateway, $capture_id );
            return 1;
        } else {
            return 0;
        }
    } catch ( HttpException $ex ) {
        return 0;
    } catch ( Exception $ex ) {
        return 0;
    }
}

function eme_complete_transaction_fondy( $payment ) {
    $gateway = 'fondy';
    $eme_fondy_merchant_id = get_option( 'eme_fondy_merchant_id' );
    if ( ! $eme_fondy_merchant_id ) {
        return 'No merchant ID';
    }
    $eme_fondy_secret_key = get_option( 'eme_fondy_secret_key' );
    if ( ! $eme_fondy_secret_key ) {
        return 'No secret key';
    }

    require_once 'payment_gateways/fondy/autoload.php';
    \Cloudipsp\Configuration::setMerchantId( $eme_fondy_merchant_id );
    \Cloudipsp\Configuration::setSecretKey( $eme_fondy_secret_key );

    $order_id = "ep-{$payment['id']}";
    $data     = [
        'order_id' => $order_id,
    ];

    $orderStatus = \Cloudipsp\Order::status( $data );
    $order       = $orderStatus->getData();

    if ( $order['order_status'] == 'approved' ) {
        eme_mark_payment_paid( $payment['id'], $gateway, $order_id );
        return 1;
    } else {
        return 0;
    }
}

function eme_notification_instamojo() {
    $gateway              = 'instamojo';
    $instamojo_key        = get_option( 'eme_instamojo_key' );
    $instamojo_auth_token = get_option( 'eme_instamojo_auth_token' );
    $instamojo_salt       = get_option( 'eme_instamojo_salt' );
    require_once 'payment_gateways/instamojo/vendor/autoload.php';
    $mode = get_option( 'eme_instamojo_env' );
    if ( preg_match( '/sandbox/', $mode ) ) {
            $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token, 'https://test.instamojo.com/api/1.1/' );
    } else {
            $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token );
    }

    $instamojo_requestid = eme_sanitize_request( $_POST['payment_request_id'] );
    $instamojo_paymentid = eme_sanitize_request( $_POST['payment_id'] );

    $payment = eme_get_payment_by_pg_pid( $instamojo_requestid );

    // code from https://docs.instamojo.com/docs/payments-api
    $data         = eme_sanitize_request( $_POST );
    $mac_provided = $data['mac'];  // Get the MAC from the POST data
    unset( $data['mac'] );  // Remove the MAC key from the data.
    $ver = explode( '.', phpversion() );
    ksort( $data, SORT_STRING | SORT_FLAG_CASE );
    $mac_calculated = hash_hmac( 'sha1', implode( '|', $data ), $instamojo_salt );
    if ( $mac_provided == $mac_calculated && $payment['pg_pid'] == $instamojo_requestid ) {
        if ( $data['status'] == 'Credit' ) {
            eme_mark_payment_paid( $payment_id, 1, $gateway, $instamojo_requestid );
        }
    }
}

function eme_notification_mercadopago() {
    $gateway = 'mercadopago';
    if ( get_option( 'eme_mercadopago_demo' ) == 1 ) {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_sandbox_token' );
    } else {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_live_token' );
    }
    if ( ! $eme_mercadopago_access_token ) {
        return;
    }

    require_once 'payment_gateways/mercadopago/vendor/autoload.php';
    MercadoPago\SDK::setAccessToken( $eme_mercadopago_access_token );

    // the IPN can also arrive for merchant orders, but for EME on the "payment" case will happen
    $merchant_order        = null;
    $mercadopago_paymentid = '';
    if ( isset( $_GET['topic'] ) && isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
        switch ( $_GET['topic'] ) {
            case 'payment':
                $mercadopago_paymentid = eme_sanitize_request( $_GET['id'] );
                $mercadopago_payment   = MercadoPago\Payment::find_by_id( $mercadopago_paymentid );
                // Get the payment and the corresponding merchant_order reported by the IPN.
                $merchant_order = MercadoPago\MerchantOrder::find_by_id( $mercadopago_payment->order->id );
                break;
            case 'merchant_order':
                $merchant_order = MercadoPago\MerchantOrder::find_by_id( $_GET['id'] );
                break;
        }
        if ( $merchant_order ) {
            $payment_id = $merchant_order->external_reference;
            $payment    = eme_get_payment( $payment_id );
            if ( ! $payment ) {
                http_response_code( 400 );
                return;
            }

            $paid_amount = 0;
            foreach ( $merchant_order->payments as $mercadopago_payment ) {
                if ( $mercadopago_payment->status == 'approved' ) {
                    $paid_amount += $mercadopago_payment->transaction_amount;
                }
                if ( empty( $mercadopago_paymentid ) ) {
                    $mercadopago_paymentid = $mercadopago_payment->id;
                }
            }

            // If the payment's transaction amount is equal (or bigger) than the merchant_order's amount you can release your items
            $eme_price = eme_get_payment_price( $payment_id );
            if ( $paid_amount >= $merchant_order->total_amount && $paid_amount >= $eme_price ) {
                eme_mark_payment_paid( $payment_id, 1, $gateway, $mercadopago_paymentid );
                http_response_code( 200 );
            } else {
                http_response_code( 400 );
            }
        } else {
            http_response_code( 400 );
        }
        return;
    }

    // in sandbox, this can arrive too
    if ( get_option( 'eme_mercadopago_demo' ) == 1 && isset( $_GET['type'] ) && $_GET['type'] == 'payment' && isset( $_GET['data_id'] ) && is_numeric( $_GET['data_id'] ) ) {
        $mercadopago_paymentid = eme_sanitize_request( $_GET['data_id'] );
        $mercadopago_payment   = MercadoPago\Payment::find_by_id( $mercadopago_paymentid );
        if ( $mercadopago_payment->status == 'approved' ) {
            $paid_amount = $mercadopago_payment->transaction_amount;
            $payment_id  = $mercadopago_payment->external_reference;
            $payment     = eme_get_payment( $payment_id );
            if ( ! $payment ) {
                http_response_code( 400 );
                return;
            }
            $eme_price = eme_get_payment_price( $payment_id );
            if ( $paid_amount >= $eme_price ) {
                eme_mark_payment_paid( $payment_id, 1, $gateway, $mercadopago_paymentid );
                http_response_code( 200 );
            } else {
                http_response_code( 400 );
            }
        } else {
            http_response_code( 400 );
        }
        return;
    }
}

function eme_notification_legacypaypal() {
    $gateway = 'legacypaypal';
    require_once 'payment_gateways/paypal_legacy/IPN.php';
    $ipn = new IPN();

    // the paypal url, or the sandbox url, or the ipn test url
    //$ipn->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
    $mode = get_option( 'eme_legacypaypal_url' );
    if ( preg_match( '/sandbox/', $mode ) ) {
        $ipn->paypal_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $ipn->paypal_url = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    }

    // your paypal email (the one that receives the payments)
    $ipn->paypal_email = get_option( 'eme_legacypaypal_business' );

    // log to file options
    $ipn->log_to_file  = false;           // write logs to file
    $ipn->log_filename = '/path/to/ipn.log';     // the log filename (should NOT be web accessible and should be writable)

    // log to email options
    $ipn->log_to_email = false;      // send logs by email
    $ipn->log_email    = '';        // where you want to receive the logs
    $ipn->log_subject  = 'IPN Log: '; // prefix for the email subject

    // array of currencies accepted or false to disable
    //$ipn->currencies = array('USD','EUR');
    $ipn->currencies = false;

    // date format on log headers (default: dd/mm/YYYY HH:mm:ss)
    // see https://php.net/date
    $ipn->date_format = 'd/m/Y H:i:s';

    // Prefix for file and mail logs
    $ipn->pretty_ipn = "IPN Values received:\n\n";

    // configuration ended, do the actual check

    if ( $ipn->ipn_is_valid() ) {
        /*
        A valid ipn was received and passed preliminary validations
        You can now do any custom validations you wish to ensure the payment was correct
        You can access the IPN data with $ipn->ipn['value']
        The complete() method below logs the valid IPN to the places you choose
        */
        $payment_id = intval( $ipn->ipn['custom'] );
        eme_mark_payment_paid( $payment_id, 1, $gateway, $ipn->ipn['txn_id'] );
        $ipn->complete();
    }
}

function eme_notification_2co() {
    $gateway  = '2co'; 
    $business = get_option( 'eme_2co_business' );
    $secret   = get_option( 'eme_2co_secret' );
    $secret   = get_option( 'eme_2co_secret' );

    if ( $_POST['message_type'] == 'ORDER_CREATED'
        || $_POST['message_type'] == 'INVOICE_STATUS_CHANGED' ) {
        $insMessage = [];
        foreach ( $_POST as $k => $v ) {
            $insMessage[ eme_sanitize_request($k) ] = eme_sanitize_request($v);
        }

        $hashSid = $insMessage['vendor_id'];
        if ( $hashSid != $business ) {
            die( 'Not the 2Checkout Account number it should be ...' );
        }
        $hashOrder    = $insMessage['sale_id'];
        $hashInvoice  = $insMessage['invoice_id'];
        $StringToHash = strtoupper( md5( $hashOrder . $hashSid . $hashInvoice . $secret ) );

        if ( $StringToHash != $insMessage['md5_hash'] ) {
            die( __( 'Hash Incorrect', 'events-made-easy' ) );
        }

        if ( $insMessage['invoice_status'] == 'approved' || $insMessage['invoice_status'] == 'deposited' ) {
            $payment_id = intval( $insMessage['item_id_1'] );
            eme_mark_payment_paid( $payment_id, 1, $gateway, $hashInvoice );
        }
    }
}

function eme_notification_webmoney() {
    $gateway  = 'webmoney'; 
    $webmoney_purse  = get_option( 'eme_webmoney_purse' );
    $webmoney_secret = get_option( 'eme_webmoney_secret' );

    require_once 'payment_gateways/webmoney/webmoney.inc.php';
    $wm_notif = new WM_Notification();
    if ( $wm_notif->GetForm() != WM_RES_NOPARAM ) {
        $amount = $wm_notif->payment_amount;
        if ( $webmoney_purse != $wm_notif->payee_purse ) {
            die( 'Not the webmoney purse it should be ...' );
        }
        #if ($price != $amount) {
        #   die ('Not the webmoney amount I expected ...');
        #}
        $payment_id = intval( $wm_notif->payment_no );
        if ( $wm_notif->CheckMD5( $webmoney_purse, $amount, $payment_id, $webmoney_secret ) == WM_RES_OK ) {
            eme_mark_payment_paid( $payment_id, 1, $gateway, $wm_notif->sys_invs_no );
        }
    }
}

function eme_notification_fdgg() {
    $gateway  = 'fdgg'; 
    $store_name    = get_option( 'eme_fdgg_store_name' );
    $shared_secret = get_option( 'eme_fdgg_shared_secret' );
    require_once 'payment_gateways/fdgg/fdgg-util_sha2.php';

    $payment_id      = intval( $_POST['invoicenumber'] );
    $charge_total    = eme_sanitize_request( $_POST['charge_total'] );
    $approval_code   = eme_sanitize_request( $_POST['approval_code'] );
    $response_hash   = eme_sanitize_request( $_POST['response_hash'] );
    $response_status = eme_sanitize_request( $_POST['status'] );

    // First Data only allows USD
    $payment = eme_get_payment( $payment_id );
    if ( $payment['target'] == 'member' ) {
        $member = eme_get_member_by_paymentid( $payment_id );
        if ( $member ) {
            $membership = eme_get_membership( $member['membership_id'] );
            $cur        = $membership['properties']['currency'];
        } else {
            esc_html_e( 'Incorrect payment id.', 'events-made-easy' );
            return;
        }
    } elseif ($payment['target'] == 'fs_event' ) {
        // First Data only allows USD
        $cur = 'USD';
    } else {
        $booking_ids = eme_get_payment_booking_ids( $payment_id );
        if ( $booking_ids ) {
            $booking = eme_get_booking( $booking_ids[0] );
            $event   = eme_get_event( $booking['event_id'] );
            if ( empty( $event ) ) {
                esc_html_e( 'No such event', 'events-made-easy' );
                return;
            } else {
                $cur = $event['currency'];
            }
        } else {
            esc_html_e( 'Incorrect payment id.', 'events-made-easy' );
            return;
        }
    }
    if ($cur != 'USD') {
        esc_html_e( 'Incorrect currency.', 'events-made-easy' );
        return;
    }
    $datetime  = eme_localized_date( $payment['creation_date'], EME_TIMEZONE, 'Y:m:d-H:i:s' );
    $cur_codes = eme_currency_codes();
    $cur_code  = $cur_codes[ $cur ];
    $calc_hash = fdgg_createHash( $shared_secret . $approval_code . $charge_total . $cur_code . $datetime . $store_name );

    if ( $response_hash != $calc_hash ) {
        die( __( 'Hash Incorrect', 'events-made-easy' ) );
    }

    // TODO: do some extra checks, like the price paid and such
    #$price=eme_get_total_booking_price($booking);

    if ( strtolower( $response_status ) == 'approved' ) {
        eme_mark_payment_paid( $payment_id, 1, $gateway, $payment_id );
    }
}

function eme_notification_sumup() {
    $gateway  = 'sumup'; 
    $eme_sumup_app_id        = get_option( 'eme_sumup_app_id' );
    $eme_sumup_app_secret    = get_option( 'eme_sumup_app_secret' );
    $eme_sumup_merchant_code = get_option( 'eme_sumup_merchant_code' );
    if ( empty( $eme_sumup_app_id ) ) {
        return;
    }

    require_once 'payment_gateways/sumup/vendor/autoload.php';
    try {
            $sumup       = new \SumUp\SumUp(
                [
                    'app_id'     => $eme_sumup_app_id,
                    'app_secret' => $eme_sumup_app_secret,
                    'grant_type' => 'client_credentials',
                    'scopes'     => [ 'payments', 'transactions.history' ],
                ]
            );
            $accessToken = $sumup->getAccessToken();
            $value       = $accessToken->getValue();
    } catch ( \SumUp\Exceptions\SumUpAuthenticationException $e ) {
            echo 'Authentication error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpResponseException $e ) {
            echo 'Response error: ' . $e->getMessage();
    } catch ( \SumUp\Exceptions\SumUpSDKException $e ) {
            echo 'SumUp SDK error: ' . $e->getMessage();
    }

    $checkout_id      = eme_sanitize_request( $_POST['id'] );
    $checkoutService  = $sumup->getCheckoutService();
    $checkoutResponse = $checkoutService->findById( $checkout_id );
    $checkoutbody     = $checkoutResponse->getBody();
    if ( ! empty( $checkoutbody ) && is_array( $checkoutbody ) && isset( $checkoutbody[0] ) ) {
        $checkoutbody = $checkoutbody[0];
    }
    if ( $checkoutbody->status == 'PAID' ) {
        $payment = eme_get_payment_by_pg_pid( $checkout_id );
        if ( ! empty( $payment ) ) {
            eme_mark_payment_paid( $payment['id'], 1, $gateway, $checkout->id );
        }
    }
}

function eme_notification_stripe() {
    $gateway  = 'stripe'; 
    $eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
    $webhook_secret         = get_option( 'eme_stripe_webhook_secret' );

    require_once 'payment_gateways/stripe/init.php';
    \Stripe\Stripe::setApiKey( "$eme_stripe_private_key" );

    $payload    = @file_get_contents( 'php://input' );
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event      = null;

    // verify the signature
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $webhook_secret
        );
    } catch ( \UnexpectedValueException $e ) {
        // Invalid payload
        http_response_code( 400 );
        return;
    } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
        // Invalid signature
        http_response_code( 400 );
        return;
    }

    // Handle the checkout.session.completed event
    if ( $event->type == 'checkout.session.completed' ) {
        $stripe_session = $event->data->object;
        $payment_id     = $stripe_session->client_reference_id;
        $stripe_pi_id   = $stripe_session->payment_intent;
        eme_mark_payment_paid( $payment_id, 1, $gateway, $stripe_pi_id );
    }
    http_response_code( 200 );
}

function eme_notification_fondy() {
    $gateway = 'fondy';

    $merchant_id = get_option( "eme_{$gateway}_merchant_id" );
    $secret_key  = get_option( "eme_{$gateway}_secret_key" );
    if ( ! $merchant_id || ! $secret_key ) {
        http_response_code( 500 );
        exit;
    }

    require_once 'payment_gateways/fondy/autoload.php';
    \Cloudipsp\Configuration::setMerchantId( $merchant_id );
    \Cloudipsp\Configuration::setSecretKey( $secret_key );

    try {
        $result = new \Cloudipsp\Result\Result();

        if ( $result->isApproved() ) {
            $order_id = $result->getData()['order_id'];
            $payment  = eme_get_payment_by_pg_pid( $order_id );
            if ( $payment ) {
                    eme_mark_payment_paid( $payment['id'], 1, $gateway, $order_id );
            }
        }

        http_response_code( 200 );
    } catch ( \Exception $e ) {
        // error_log("EME Fondy Notification Error: {$e->getMessage()}");
        http_response_code( 400 );
    }
}

function eme_stripe_webhook() {
    $gateway = "stripe";
    $eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
    if ( empty( $eme_stripe_private_key ) ) {
        return;
    }
    // do nothing if the events page is on localhost
    $events_page_link  = eme_get_events_page();
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );
    if ( strstr( $events_page_link, 'localhost' ) ) {
        update_option( 'eme_stripe_webhook_error', __( 'since this is a site running on localhost, no webhook will be created', 'events-made-easy' ) );
        return;
    }

    require_once 'payment_gateways/stripe/init.php';
    \Stripe\Stripe::setApiKey( "$eme_stripe_private_key" );

    // first check all webhooks, delete the one matching our url and recreate it, otherwise we can't get the secret
    $webhooks = null;
    try {
        $webhooks = \Stripe\WebhookEndpoint::all( [ 'limit' => 100 ] );
        if ( ! empty( $webhooks ) ) {
            foreach ( $webhooks->data as $webhook ) {
                $endpoint_id = $webhook->id;
                $endpoint    = \Stripe\WebhookEndpoint::retrieve( "$endpoint_id" );
                if ( $endpoint->url == $notification_link ) {
                    $endpoint->delete();
                }
            }
        }
    } catch ( \Stripe\Exception\InvalidRequestException $e ) {
        update_option( 'eme_stripe_webhook_error', $e->getMessage() );
        return;
    }

    update_option( 'eme_stripe_webhook_secret', '' );
    try {
        $endpoint = \Stripe\WebhookEndpoint::create(
            [
                'url'            => $notification_link,
                'enabled_events' => [ 'checkout.session.completed' ],
            ]
        );
    } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            update_option( 'eme_stripe_webhook_error', $e->getMessage() );
        return;
    }
    update_option( 'eme_stripe_webhook_secret', $endpoint->secret );
    update_option( 'eme_stripe_webhook_error', '' );
}

function eme_charge_paypal() {
    $gateway = "paypal";
    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );
    $success_link     = eme_payment_return_url( $payment, $gateway );
    $fail_link        = eme_payment_return_url( $payment, 1 );
    $cancel_link      = eme_payment_url( $payment );
    // $notification_link = add_query_arg(array('eme_eventAction'=>'paypal_notification'),$events_page_link);

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    require_once 'payment_gateways/paypal/vendor/autoload.php';
    // the paypal or paypal sandbox url
    $mode = get_option( 'eme_paypal_url' );
    if ( preg_match( '/sandbox/', $mode ) ) {
        require_once 'payment_gateways/paypal/client_sandbox.php';
        $client = PayPalClient::client();
    } else {
        require_once 'payment_gateways/paypal/client_prod.php';
        $client = PayPalClient::client();
    }

    // although mentioning items is not obligated, you need it or on the paypal window the amount and description won't show
    $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
    $request->prefer( 'return=representation' );
    $request->body = [
        'intent'              => 'CAPTURE',
        'purchase_units'      => [
            [
                'reference_id' => $payment_id,
                'description'  => "$description",
                'amount'       => [
                    'value'         => "$price",
                    'currency_code' => "$cur",
                    'breakdown'     => [
                        'item_total' => [
                            'value'         => "$price",
                            'currency_code' => "$cur",
                        ],
                    ],
                ],
                'items'        => [
                    [
                        'name'        => "$description",
                        'description' => "$description",
                        'unit_amount' => [
                            'value'         => "$price",
                            'currency_code' => "$cur",
                        ],
                        'quantity'    => '1',
                    ],
                ],
            ],
        ],
        'application_context' => [
            'cancel_url' => $cancel_link,
            'return_url' => $success_link,
        ],
    ];

    $url = '';
    try {
        // Call API with your client and get a response for your call
        $response = $client->execute( $request );
        // If call returns body in response, you can get the deserialized version from the result attribute of the response
        foreach ( $response->result->links as $link ) {
            if ( $link->rel == 'approve' ) {
                $url = $link->href;
            }
        }
    } catch ( \PayPalHttp\HttpException $ex ) {
        $message = json_decode( $ex->getMessage(), true );
        print 'Paypal API call failed. Error code: ' . $ex->statusCode . '<br>' . eme_prettyprint_assoc( $message );
    }
    if ( ! empty( $url ) ) {
        // we'll store the paypal payment id already, so when people arrive to the redirecturl before the webhook fired, we can check for it
        eme_update_payment_pg_pid( $payment_id, $response->result->id );
        wp_redirect($url);
        exit;
    }
}

function eme_charge_2co() {
    $gateway = "2co";
    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );
    $success_link     = eme_payment_return_url( $payment, $gateway );
    $fail_link        = eme_payment_return_url( $payment, 1 );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    $eme_2co_buylinksecret = get_option( 'eme_2co_buylinksecret' );
    $eme_2co_secret = get_option( 'eme_2co_secret' );
    $eme_2co_business  = get_option( 'eme_2co_business' );
    $item_name              = esc_attr( get_option( 'blog_name' ) );
    if ( empty( $item_name ) ) {
        $item_name = $description;
    }

    require_once 'payment_gateways/2checkout/2checkout-php-sdk/autoloader.php';
    $config    = [
            'sellerId'      => $eme_2co_business,
            'secretKey'     => $eme_2co_secret,
            'buyLinkSecretWord'    => $eme_2co_buylinksecret,
            'jwtExpireTime' => 30,
            'curlVerifySsl' => 1
    ];
    $buyLinkParameters = [
        'order-ext-ref' => $payment_id,
        'back-url' => $events_page_link,
        'currency' => $cur,
        'qty' => 1,
        'price' => $price,
        'prod' => $item_name,
        'description' => $description,
        'merchant' => $eme_2co_business,
        'return-url' => $success_link,
        'return-type' => 'Redirect',
        'tangible' => 0,
        'type' => 'PRODUCT',
        'dynamic' => 1,
    ];
    $tco = new TwocheckoutFacade($config);

    // prefill e-mail in form if possible
    if ($payment['target'] == 'booking') {
        $booking_ids = eme_get_payment_booking_ids( $payment_id );
        if ( $booking_ids ) {
            $booking = eme_get_booking( $booking_ids[0] );
            $person  = eme_get_person( $booking['person_id'] );
            if (!empty($person)) {
                $buyLinkParameters['email'] = $person['email'];
            }
        }
    }
    if (get_option('eme_2co_demo')) {
        $buyLinkParameters['test'] = 1;
    }

    $buyLinkSignature = $tco->getBuyLinkSignature($buyLinkParameters);
    $buyLinkParameters['signature'] = $buyLinkSignature;
    $redirectTo = 'https://secure.2checkout.com/checkout/buy/?' . ( http_build_query( $buyLinkParameters ) );
    wp_redirect($redirectTo);
}

function eme_charge_stripe() {
    $gateway = "stripe";
    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );
    $success_link     = eme_payment_return_url( $payment, $gateway );
    $cancel_link      = eme_payment_url( $payment );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    $eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
    $eme_stripe_public_key  = get_option( 'eme_stripe_public_key' );
    $item_name              = esc_attr( get_option( 'blog_name' ) );
    if ( empty( $item_name ) ) {
        $item_name = $description;
    }

    require_once 'payment_gateways/stripe/init.php';
    \Stripe\Stripe::setApiKey( "$eme_stripe_private_key" );
    \Stripe\Stripe::setAppInfo( 'WordPress Events Made Easy Stripe plugin' );

    $payment_methods = get_option( 'eme_stripe_payment_methods' );
    if ( empty( $payment_methods ) ) {
        $payment_methods = 'card';
    }
    // make sure it is an array with numeric keys
    if ( ! is_array( $payment_methods ) ) {
        $payment_methods_arr = explode( ',', $payment_methods );
    } else {
        $payment_methods_arr = $payment_methods;
    }

    $stripe_session_params = [
        'payment_method_types' => $payment_methods_arr,
        'payment_intent_data'  => [ 'description' => $description ],
        'line_items'           => [
            [
                'price_data' => [
                    'currency'     => strtolower( $cur ),
                    'unit_amount'  => $price,
                    'product_data' => [
                        'name'        => $item_name,
                        'description' => $description,
                    ],
                ],
                'quantity'   => 1,
            ],
        ],
        'mode'                 => 'payment',
        'client_reference_id'  => $payment_id,
        'success_url'          => $success_link,
        'cancel_url'           => $cancel_link,
    ];

    // prefill e-mail in Stripe form if possible
    $booking_ids = eme_get_payment_booking_ids( $payment_id );
    if ( $booking_ids ) {
        $booking = eme_get_booking( $booking_ids[0] );
        $person  = eme_get_person( $booking['person_id'] );
        if (!empty($person)) {
            $stripe_session_params['customer_email'] = $person['email'];
        }
    }

    $stripe_session = \Stripe\Checkout\Session::create($stripe_session_params);

    $stripe_session_id = $stripe_session->id;
    eme_update_payment_pg_pid( $payment_id, $stripe_session_id );

    print "<html><body>
        <script src='https://js.stripe.com/v3/'></script>
        <script>
            var stripe = Stripe('$eme_stripe_public_key');
            stripe.redirectToCheckout({
                sessionId: '$stripe_session_id'
            });
        </script>
        </body></html>
   ";
}

function eme_charge_braintree() {
    $gateway                   = 'braintree';
    $eme_braintree_private_key = get_option( 'eme_braintree_private_key' );
    $eme_braintree_public_key  = get_option( 'eme_braintree_public_key' );
    $eme_braintree_merchant_id = get_option( 'eme_braintree_merchant_id' );
    $eme_braintree_env         = get_option( 'eme_braintree_env' );
    if ( empty($eme_braintree_public_key) || empty($eme_braintree_private_key) || empty($eme_braintree_merchant_id) ) {
        return;
    }

    $payment_id  = intval( $_POST['payment_id'] );
    $price       = eme_sanitize_request( $_POST['price'] );
    $cur         = eme_sanitize_request( $_POST['cur'] );
    // braintree ignores the description, but let's act as usual
    $description = eme_sanitize_request( $_POST['description'] );
    $payment     = eme_get_payment( $payment_id );

    $success_link = eme_payment_return_url( $payment, 0 );
    $fail_link    = eme_payment_return_url( $payment, 1 );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    require_once 'payment_gateways/braintree/vendor/autoload.php';
    if ( ! isset( $_POST['braintree_nonce'] ) ) {
        wp_redirect($fail_link);
        exit;
    }
    $braintree_gateway = new Braintree\Gateway(
        [
            'environment' => $eme_braintree_env,
            'merchantId'  => $eme_braintree_merchant_id,
            'publicKey'   => $eme_braintree_public_key,
            'privateKey'  => $eme_braintree_private_key,
        ]
    );
    $result = $braintree_gateway->transaction()->sale(
        [
            'amount'             => $price,
            'paymentMethodNonce' => $_POST['braintree_nonce'],
            'orderId'            => $payment_id,
        ]
    );
    if ( $result->success ) {
        $transaction = $result->transaction;
        eme_mark_payment_paid( $payment_id, 1, $gateway, $transaction->id );
        wp_redirect($success_link);
        exit;
    } else {
        wp_redirect($fail_link);
        exit;
    }
}

function eme_charge_instamojo() {
    $gateway              = 'instamojo';
    $instamojo_key        = get_option( 'eme_instamojo_key' );
    $instamojo_auth_token = get_option( 'eme_instamojo_auth_token' );
    if ( ! $instamojo_key || ! $instamojo_auth_token ) {
        return;
    }

    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );

    $return_link       = eme_payment_return_url( $payment, $gateway );
    $fail_link         = eme_payment_return_url( $payment, 1 );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    require_once 'payment_gateways/instamojo/vendor/autoload.php';
    $mode = get_option( 'eme_instamojo_env' );
    if ( preg_match( '/sandbox/', $mode ) ) {
        $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token, 'https://test.instamojo.com/api/1.1/' );
    } else {
        $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token );
    }
    try {
        $instamojo_payment = $api->paymentRequestCreate(
            [
                'purpose'      => $description,
                'amount'       => "$price",
                'redirect_url' => $return_link,
                'webhook'      => $notification_link
            ]
        );
        $url = $instamojo_payment['longurl'];
    } catch ( Exception $e ) {
        $url = '';
        print 'Instamojo API call failed: ' . htmlspecialchars( $e->getMessage() );
    }

    if ( ! empty( $url ) ) {
        // we'll store the instamojo payment id already, so when people arrive to the redirecturl before the webhook fired, we can check for it
        eme_update_payment_pg_pid( $payment['id'], $instamojo_payment['id'] );
        wp_redirect($url);
        exit;
    }
}

function eme_charge_mercadopago() {
    $gateway = 'mercadopago';
    if ( get_option( 'eme_mercadopago_demo' ) == 1 ) {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_sandbox_token' );
    } else {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_live_token' );
    }
    if ( ! $eme_mercadopago_access_token ) {
        return;
    }

    // we get the external reference back as a post
    // // we also get the mercado pago payment id, merchant order and status back as post, but we won't trust it
    $payment_id   = intval( $_POST['external_reference'] );
    $payment      = eme_get_payment( $payment_id );
    $success_link = eme_payment_return_url( $payment, 0 );
    $fail_link    = eme_payment_return_url( $payment, 1 );

    require_once 'payment_gateways/mercadopago/vendor/autoload.php';
    MercadoPago\SDK::setAccessToken( $eme_mercadopago_access_token );
    $filter = [
        'external_reference' => $payment_id,
    ];
    $paid_amount = 0;
    $mercadopago_payments = MercadoPago\Payment::search( $filter );
    foreach ( $mercadopago_payments as $mercadopago_payment ) {
        if ( $mercadopago_payment->status == 'approved' ) {
            $paid_amount          += $mercadopago_payment->transaction_amount;
            $mercadopago_paymentid = $mercadopago_payment->id;
        }
    }

    $eme_price = eme_get_payment_price( $payment_id );
    if ( $paid_amount >= $eme_price ) {
        eme_mark_payment_paid( $payment_id, 1, $gateway, $mercadopago_paymentid );
        wp_redirect($success_link);
        exit;
    } else {
        wp_redirect($fail_link);
        exit;
    }
}

function eme_charge_fondy() {
    $gateway = 'fondy';

    $merchant_id = get_option( "eme_{$gateway}_merchant_id" );
    $secret_key  = get_option( "eme_{$gateway}_secret_key" );
    if ( ! $merchant_id || ! $secret_key ) {
        return;
    }

    $payment_id  = intval( $_POST['payment_id'] );
    $price       = eme_sanitize_request( $_POST['price'] );
    $cur         = eme_sanitize_request( $_POST['cur'] );
    $description = eme_sanitize_request( $_POST['description'] );

    $payment = eme_get_payment( $payment_id );

    $events_page_link  = eme_get_events_page();
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );
    $success_link      = eme_payment_return_url( $payment, 0 );
    $fail_link         = eme_payment_return_url( $payment, 1 );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    require_once 'payment_gateways/fondy/autoload.php';
    \Cloudipsp\Configuration::setMerchantId( $merchant_id );
    \Cloudipsp\Configuration::setSecretKey( $secret_key );

    $data = [
        'order_desc'          => $description,
        'amount'              => $price,
        'currency'            => $cur,
        'server_callback_url' => $notification_link,
        'response_url'        => $success_link,
        'merchant_data'       => [
            'payment_id' => $payment_id,
        ],
    ];

    try {
        $response = \Cloudipsp\Checkout::url( $data );
        $order_id = $response->getOrderID();
        eme_update_payment_pg_pid( $payment_id, $order_id );

        $response->toCheckout();
    } catch ( \Exception $e ) {
        //error_log("Fondy API error: {$e->getMessage()}");
        wp_redirect($fail_link);
        exit;
    }
}

function eme_refund_booking_paypal( $booking ) {
    require_once 'payment_gateways/paypal/vendor/autoload.php';

    // the paypal or paypal sandbox url
    $mode = get_option( 'eme_paypal_url' );
    if ( preg_match( '/sandbox/', $mode ) ) {
        require_once 'payment_gateways/paypal/client_sandbox.php';
        $client = PayPalClient::client();
    } else {
        require_once 'payment_gateways/paypal/client_prod.php';
        $client = PayPalClient::client();
    }

    $price = eme_get_total_booking_price( $booking );
    $event = eme_get_event( $booking['event_id'] );
    if ( ! empty( $event ) ) {
        $cur           = $event['currency'];
        $request       = new \PayPalCheckoutSdk\Payments\CapturesRefundRequest( $booking['pg_pid'] );
        $request->body = [
            'amount' =>
            [
                'value'         => $price,
                'currency_code' => $cur,
            ],
        ];
        try {
            $response = $client->execute( $request );
            return true;
        } catch ( Exception $ex ) {
            return false;
        }
    } else {
        return false;
    }
}

function eme_refund_booking_payconiq( $booking ) {
    $price = eme_get_total_booking_price( $booking );
    $event = eme_get_event( $booking['event_id'] );
    if ( ! empty( $event ) ) {
        $cur = $event['currency'];
    } else {
        $cur = 'EUR';
    }

    $api_key = get_option( "eme_payconiq_api_key" );
    if ( ! $api_key ) {
        return;
    }
    if ( ! class_exists( 'Payconiq\Client' ) ) {
                require_once 'payment_gateways/payconiq/src/Client.php';
        }

    $mode     = get_option( 'eme_payconiq_env' );
    $payconiq = new \Payconiq\Client( $api_key );
    if ( preg_match( '/sandbox/', $mode ) ) {
        $payconiq->setEndpointTest();
    }
    try {
        // currently no description
        $description = '';
        $payconiq_payment = $payconiq->refundPayment( $booking['pg_pid'], $price, $cur, $description );
    } catch ( Exception $e ) {
        $url = '';
        print 'Payconiq API call failed: ' . htmlspecialchars( $e->getMessage() );
    }
}

function eme_refund_booking_mercadopago( $booking ) {
    if ( get_option( 'eme_mercadopago_demo' ) == 1 ) {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_sandbox_token' );
    } else {
        $eme_mercadopago_access_token = get_option( 'eme_mercadopago_live_token' );
    }
    if ( ! $eme_mercadopago_access_token ) {
        return;
    }

    require_once 'payment_gateways/mercadopago/vendor/autoload.php';
    MercadoPago\SDK::setAccessToken( $eme_mercadopago_access_token );

    try {
        $payment = MercadoPago\Payment::find_by_id( $booking['pg_pid'] );
        $payment->refund();
        // now check it
        $payment = MercadoPago\Payment::find_by_id( $booking['pg_pid'] );
        if ( $payment->status == 'refunded' ) {
            return true;
        } else {
            return false;
        }
    } catch ( Exception $e ) {
        //print('Error: ' . $e->getMessage());
        return false;
    }
}

function eme_refund_booking_instamojo( $booking ) {
    $instamojo_key        = get_option( 'eme_instamojo_key' );
    $instamojo_auth_token = get_option( 'eme_instamojo_auth_token' );
    if ( ! $instamojo_key || ! $instamojo_auth_token ) {
        return;
    }

    require_once 'payment_gateways/instamojo/vendor/autoload.php';
    $mode = get_option( 'eme_instamojo_env' );
    if ( preg_match( '/sandbox/', $mode ) ) {
        $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token, 'https://test.instamojo.com/api/1.1/' );
    } else {
        $api = new Instamojo\Instamojo( $instamojo_key, $instamojo_auth_token );
    }
    try {
        $response = $api->refundCreate(
            [
                'payment_id' => $booking['pg_pid'],
                'type'       => 'QFL',
                'body'       => __( 'Booking refunded', 'events-made-easy' ),
            ]
        );
        return true;
    } catch ( Exception $e ) {
        //print('Error: ' . $e->getMessage());
        return false;
    }
}

function eme_refund_booking_fondy( $booking ) {
    $gateway = 'fondy';

    $merchant_id = get_option( "eme_{$gateway}_merchant_id" );
    $secret_key  = get_option( "eme_{$gateway}_secret_key" );
    if ( ! $merchant_id || ! $secret_key ) {
        return;
    }

    require_once 'payment_gateways/fondy/autoload.php';
    \Cloudipsp\Configuration::setMerchantId( $merchant_id );
    \Cloudipsp\Configuration::setSecretKey( $secret_key );

    try {
        $order_data  = [
            'order_id' => $booking['pg_pid'],
        ];
        $orderStatus = \Cloudipsp\Order::status( $order_data );
        $order       = $orderStatus->getData();

        $refund_data = [
            'order_id' => $booking['pg_pid'],
            'amount'   => $order['amount'],
            'currency' => $order['currency'],
        ];
        $response    = \Cloudipsp\Order::reverse( $refund_data );
        return $response->isReversed();
    } catch ( \Exception $e ) {
        //error_log("Fondy refund API error: {$e->getMessage()}");
        return false;
    }
}

function eme_refund_booking_stripe( $booking ) {
    $gateway = 'stripe';

    $eme_stripe_private_key = get_option( 'eme_stripe_private_key' );
    if ( ! $eme_stripe_private_key ) {
        return;
    }

    require_once 'payment_gateways/stripe/init.php';
    \Stripe\Stripe::setApiKey( "$eme_stripe_private_key" );

    $stripe_pi_id = $booking['pg_pid'];
    try {
        $stripe_pi = \Stripe\PaymentIntent::retrieve( $stripe_pi_id );
    } catch ( Exception $e ) {
        return;
    }
    $re = \Stripe\Refund::create(
        [
            'payment_intent' => $stripe_pi_id,
        ]
    );
    return true;
}

function eme_refund_booking_braintree( $booking ) {
    $gateway = 'braintree';

    $eme_braintree_private_key = get_option( 'eme_braintree_private_key' );
    $eme_braintree_public_key  = get_option( 'eme_braintree_public_key' );
    $eme_braintree_merchant_id = get_option( 'eme_braintree_merchant_id' );
    $eme_braintree_env         = get_option( 'eme_braintree_env' );
    if ( empty($eme_braintree_public_key) || empty($eme_braintree_private_key) || empty($eme_braintree_merchant_id) ) {
        return;
    }

    require_once 'payment_gateways/braintree/vendor/autoload.php';
    $braintree_gateway = new Braintree\Gateway(
        [
            'environment' => $eme_braintree_env,
            'merchantId'  => $eme_braintree_merchant_id,
            'publicKey'   => $eme_braintree_public_key,
            'privateKey'  => $eme_braintree_private_key,
        ]
    );
    $transaction_id    = $booking['pg_pid'];
    $result            = $braintree_gateway->transaction()->refund( $transaction_id );
    return true;
}

function eme_charge_mollie() {
    $gateway = 'mollie';
    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );

    $api_key = get_option( 'eme_mollie_api_key' );
    if ( ! $api_key ) {
        return;
    }

    $return_link       = eme_payment_return_url( $payment, $gateway );
    $fail_link         = eme_payment_return_url( $payment, $gateway );
    $cancel_link       = eme_payment_url( $payment );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    // Avoid loading the Mollie API if it is already loaded by another plugin
    if ( ! class_exists( 'Mollie\Api\MollieApiClient' ) ) {
        require_once 'payment_gateways/Mollie/vendor/autoload.php';
    }
    $mollie = new \Mollie\Api\MollieApiClient();

    // Mollie needs the price in EUR and 2 decimals
    try {
        $mollie->setApiKey( $api_key );
        $mollie_payment = $mollie->send(
            new \Mollie\Api\Http\Requests\CreatePaymentRequest(
                description: $description,
                amount: new \Mollie\Api\Http\Data\Money(currency: $cur, value: sprintf( '%01.2f', $price )),
                redirectUrl: $return_link,
                cancelUrl: $cancel_link,
                webhookUrl: $notification_link,
                metadata: ['payment_id' => $payment_id]
            )
        );
        $url = $mollie_payment->getCheckoutUrl();
    } catch ( \Mollie\Api\Exceptions\ApiException $e ) {
        $url = '';
        print 'Mollie API call failed: ' . htmlspecialchars( $e->getMessage() );
    }

    if ( ! empty( $url ) ) {
        // we'll store the mollie payment id already, so when people arrive to the redirecturl before the webhook fired, we can check for it
        eme_update_payment_pg_pid( $payment['id'], $mollie_payment->id );
        wp_redirect($url);
        exit;
    }
}

function eme_complete_transaction_mollie( $payment ) {
    return eme_notification_mollie( $payment['pg_pid']);
}

function eme_notification_mollie( $mollie_payment_id = 0 ) {
    $gateway = 'mollie';
    $api_key = get_option( 'eme_mollie_api_key' );
    if ( ! $api_key ) {
        return;
    }
    // Avoid loading the Mollie API if it is already loaded by another plugin
    if ( ! class_exists( 'Mollie\Api\MollieApiClient' ) ) {
        require_once 'payment_gateways/Mollie/vendor/autoload.php';
    }

    $mollie = new \Mollie\Api\MollieApiClient();
    if ( ! $mollie_payment_id ) {
        $mollie_payment_id = eme_sanitize_request( $_POST['id'] );
    }
    try {
        $mollie->setApiKey( $api_key );
        $mollie_payment = $mollie->send(
            new \Mollie\Api\Http\Requests\GetPaymentRequest(
                id: $mollie_payment_id
            )
        );
    } catch ( Exception $e ) {
        return;
    }
    $payment_id = $mollie_payment->metadata->payment_id;
    $payment    = eme_get_payment( $payment_id );
    if ( $payment['pg_pid'] != $mollie_payment_id ) {
        return;
    }
    // The payment is paid and isn't refunded or charged back
    if ( $mollie_payment->isPaid() && ! $mollie_payment->hasRefunds() && ! $mollie_payment->hasChargebacks() ) {
        eme_mark_payment_paid( $payment_id, 1, $gateway, $mollie_payment_id );
    }
}

function eme_charge_payconiq() {
    $events_page_link = eme_get_events_page();
    $payment_id       = intval( $_POST['payment_id'] );
    $price            = eme_sanitize_request( $_POST['price'] );
    $cur              = eme_sanitize_request( $_POST['cur'] );
    $description      = eme_sanitize_request( $_POST['description'] );
    $payment          = eme_get_payment( $payment_id );
    if ( $payment['target'] == 'member' ) {
        $bulkId = "members";
    } elseif ( $payment['target'] == 'fs_event' ) {
        $bulkId = "frontend event";
    } else {
        $bulkId = "bookings";
    }

    $gateway = 'payconiq';
    $api_key = get_option( "eme_{$gateway}_api_key" );
    if ( ! $api_key ) {
        return;
    }

    $return_link       = eme_payment_return_url( $payment, $gateway );
    $fail_link         = eme_payment_return_url( $payment, $gateway );
    $notification_link = add_query_arg( [ 'eme_eventAction' => "{$gateway}_notification" ], $events_page_link );

    // no cheating
    if ( empty($_POST["eme_{$gateway}_nonce"]) || ! wp_verify_nonce( $_POST["eme_{$gateway}_nonce"], "$price$cur" ) ) {
        wp_redirect($fail_link);
        exit;
    }

    // avoid that people pay again after pressing "back" and arriving on the payment form again
    $check_allowed_to_pay = eme_payment_allowed_to_pay( $payment_id );
    if ( ! empty( $check_allowed_to_pay )) {
        // not allowed: return the reason and stop
        return $check_allowed_to_pay;
    }

    if ( ! class_exists( 'Payconiq\Client' ) ) {
        require_once 'payment_gateways/payconiq/src/Client.php';
    }
    $mode     = get_option( 'eme_payconiq_env' );
    $payconiq = new \Payconiq\Client( $api_key );
    if ( preg_match( '/sandbox/', $mode ) ) {
            $payconiq->setEndpointTest();
    }
    try {
        $payconiq_payment = $payconiq->createPayment( 
            amount: $price,
            currency: $cur,
            description: $description,
            reference: $payment_id,
            //bulkId: $bulkId,
            callbackUrl: $notification_link,
            returnUrl: $return_link
        );
        $url = $payconiq_payment->_links->checkout->href;
        // fix a payconiq api bug where the href-links in sandbox point to prod too
        if ( preg_match( '/sandbox/', $mode ) ) {
            $url = str_replace( 'https://payconiq.com', 'https://ext.payconiq.com', $url );
        }
    } catch ( Exception $e ) {
        $url = '';
        print 'Payconiq API call failed: ' . htmlspecialchars( $e->getMessage() );
    }

    if ( ! empty( $url ) ) {
        // we'll store the payment id already, so when people arrive to the redirecturl before the webhook fired, we can check for it
        eme_update_payment_pg_pid( $payment['id'], $payconiq_payment->paymentId );
        wp_redirect($url);
        exit;
    }
}

function eme_complete_transaction_payconiq( $payment ) {
    return eme_notification_payconiq( $payment['pg_pid'] );
}

function eme_notification_payconiq( $payconiq_paymentid = 0 ) {
    $gateway     = 'payconiq';
    $api_key     = get_option( "eme_{$gateway}_api_key" );
    $merchant_id = get_option( "eme_{$gateway}_merchant_id" );
    if ( ! $api_key ) {
        return;
    }

    //$display_errors = ini_get( 'display_errors' );
    //@ini_set( 'display_errors', '0' );

    // if no payment id is provided, it is a real notification from payconiq, so get the payment id from the input
    if ( ! $payconiq_paymentid ) {
        $payload            = @file_get_contents( 'php://input' );
        $data               = json_decode( $payload );
        $payconiq_paymentid = $data->paymentId;
    }
    //error_log("EME saw payconiq payment id $payconiq_paymentid");
    // We won't verify the signature, but we'll get the current payment from EME and compare all that
    if ( ! class_exists( 'Payconiq\Client' ) ) {
        require_once 'payment_gateways/payconiq/src/Client.php';
    }
    $payconiq = new \Payconiq\Client( $api_key );
    $mode     = get_option( 'eme_payconiq_env' );
    if ( preg_match( '/sandbox/', $mode ) ) {
            $payconiq->setEndpointTest();
    }
    try {
        $payconiq_payment = $payconiq->retrievePayment( $payconiq_paymentid );
    } catch ( Exception $e ) {
        //error_log("EME payconiq error getting payment id $payconiq_paymentid");
        //@ini_set( 'display_errors', $display_errors );
        return;
    }

    $payconiq_merchantid = $payconiq_payment->creditor->merchantId;
    if ( $payconiq_merchantid != $merchant_id ) {
        //error_log("EME payconiq wrong merchant id $payconiq_merchantid");
        //@ini_set( 'display_errors', $display_errors );
        return;
    }

    $payment_id = $payconiq_payment->reference;
    $eme_price  = eme_get_payment_price( $payment_id );
    $payment    = eme_get_payment( $payment_id );
    if ( !$payment ) {
        // notif for payment that doesn't exist, let's quit
        return;
    }
    if ( $payment['pg_pid'] != $payconiq_paymentid ) {
        //error_log("EME payment id $payment_id does not match payconiq payment id $payconiq_paymentid");
        //@ini_set( 'display_errors', $display_errors );
        return;
    }
    // The payment is paid and to be sure we also check the paid amount
    if ( $payconiq_payment->status == 'SUCCEEDED' && $payconiq_payment->totalAmount / 100 >= $eme_price ) {
        eme_mark_payment_paid( $payment_id, 1, $gateway, $payconiq_paymentid );
    } else {
        //error_log("EME payment id $payment_id, ignored payconiq notification with payment id $payconiq_paymentid");
        //@ini_set( 'display_errors', $display_errors );
    }
}

function eme_refund_booking_mollie( $booking ) {
    $api_key = get_option( 'eme_mollie_api_key' );
    if ( ! $api_key ) {
        return;
    }
    // Avoid loading the Mollie API if it is already loaded by another plugin
    if ( ! class_exists( 'Mollie\Api\MollieApiClient' ) ) {
        require_once 'payment_gateways/Mollie/vendor/autoload.php';
    }

    $mollie = new \Mollie\Api\MollieApiClient();
    try {
        $mollie->setApiKey( $api_key );
        $mollie_payment = $mollie->send(
            new \Mollie\Api\Http\Requests\GetPaymentRequest(
                id: $booking['pg_pid']
            )
        );
    } catch ( Exception $e ) {
        return false;
    }

    // according to the refund example, mollie requires 2 decimals
    $price = eme_get_total_booking_price( $booking );
    $price = sprintf( '%01.2f', $price );
    $event = eme_get_event( $booking['event_id'] );
    if ( ! empty( $event ) ) {
        $cur = $event['currency'];
        if ( $mollie_payment->canBeRefunded() && $mollie_payment->amountRemaining->currency === $cur && $mollie_payment->amountRemaining->value >= $price ) {
            try {
                $refund = $mollie->send(
                    new \Mollie\Api\Http\Requests\CreatePaymentRefundRequest(
                        paymentId: $mollie_payment->id,
                        description: __('Booking cancelled and refunded','events-made-easy'),
                        amount: new \Mollie\Api\Http\Data\Money(currency: $cur, value: $price )
                    )
                );
                return true;
            } catch ( Exception $e ) {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function eme_notification_worldpay() {
    // for worldpay notifications to work: enable dynamic payment response in your worldpay setup, using the param MC_callback
    $worldpay_demo = get_option( 'eme_worldpay_demo' );
    if ( $worldpay_demo == 1 ) {
        $worldpay_pwd = get_option( 'eme_worldpay_test_pwd' );
    } else {
        $worldpay_pwd = get_option( 'eme_worldpay_live_pwd' );
    }

    $post_pwd        = eme_sanitize_request( $_POST['callbackPW'] );
    $trans_status    = eme_sanitize_request( $_POST['transStatus'] );
    $trans_id        = eme_sanitize_request( $_POST['transId'] );
    $test_mode       = isset( $_POST ['testMode'] ) ? eme_sanitize_request( $_POST ['testMode'] ) : 0;
    $post_instid     = eme_sanitize_request( $_POST['instId'] );
    $worldpay_instid = eme_sanitize_request( get_option( 'eme_worldpay_instid' ) );
    $payment_id      = intval( $_POST['cartId'] );
    if ( $post_pwd == $worldpay_pwd && $trans_status == 'Y' && $test_mode == 0 && $post_instid == $worldpay_instid ) {
        eme_mark_payment_paid( $payment_id, 1, 'worldpay', $trans_id );
    }
}

function eme_notification_opayo() {
    $opayo_demo = get_option( 'eme_opayo_demo' );
    if ( $opayo_demo == 1 ) {
        $opayo_pwd = get_option( 'eme_opayo_test_pwd' );
    } else {
        $opayo_pwd = get_option( 'eme_opayo_live_pwd' );
    }

    // crypt is passed as part of the request
    $crypt = eme_sanitize_request( $_GET['crypt'] );
    require_once 'payment_gateways/opayo/eme-opayo-util.php';
    $decrypt    = SagepayUtil::decryptAes( $crypt, $opayo_pwd );
    $decryptArr = SagepayUtil::queryStringToArray( $decrypt );
    if ( $decrypt && ! empty( $decryptArr ) ) {
        if ( $decryptArr['Status'] == 'OK' ) {
            $payment_id = $decryptArr['VendorTxCode'];
            eme_mark_payment_paid( $payment_id, 1, 'opayo', $decryptArr['VPSTxId'] );
        }
    }
}

function eme_get_configured_pgs() {
    // allow people to change the sequence or even add their own payment gateway
    $pgs = wp_cache_get( 'eme_configured_pgs' );

        if ( $pgs === false ) {
        $pgs = [];
        if ( ! empty( get_option( 'eme_paypal_clientid' ) ) ) {
            $pgs[] = 'paypal';
        }
        if ( ! empty( get_option( 'eme_legacypaypal_business' ) ) ) {
            $pgs[] = 'legacypaypal';
        }
        if ( ! empty( get_option( 'eme_webmoney_purse' ) ) ) {
            $pgs[] = 'webmoney';
        }
        if ( ! empty( get_option( 'eme_fdgg_store_name' ) ) ) {
            $pgs[] = 'fdgg';
        }
        if ( ! empty( get_option( 'eme_mollie_api_key' ) ) ) {
            $pgs[] = 'mollie';
        }
        if ( ! empty( get_option( 'eme_payconiq_api_key' ) ) ) {
            $pgs[] = 'payconiq';
        }
        if ( ! empty( get_option( 'eme_worldpay_instid' ) ) ) {
            $pgs[] = 'worldpay';
        }
        if ( ! empty( get_option( 'eme_opayo_vendor_name' ) ) ) {
            $pgs[] = 'opayo';
        }
        if ( ! empty( get_option( 'eme_sumup_app_id' ) ) ) {
            $pgs[] = 'sumup';
        }
        if ( ! empty( get_option( 'eme_stripe_private_key' ) ) ) {
            $pgs[] = 'stripe';
        }
        if ( ! empty( get_option( 'eme_braintree_private_key' ) ) ) {
            $pgs[] = 'braintree';
        }
        if ( ! empty( get_option( 'eme_instamojo_key' ) ) ) {
            $pgs[] = 'instamojo';
        }
        if ( ! empty( get_option( 'eme_mercadopago_sandbox_token' ) ) || ! empty( get_option( 'eme_mercadopago_live_token' ) ) ) {
            $pgs[] = 'mercadopago';
        }
        if ( ! empty( get_option( 'eme_fondy_secret_key' ) ) ) {
            $pgs[] = 'fondy';
        }
        if ( ! empty( get_option( 'eme_offline_payment' ) ) ) {
            $pgs[] = 'offline';
        }
        if ( has_filter( 'eme_configured_payment_gateways' ) ) {
            $pgs = apply_filters( 'eme_configured_payment_gateways', $pgs );
        }
        wp_cache_set( 'eme_configured_pgs', $pgs, '', 10 );
    }
    return $pgs;
}

function eme_configured_pgs_descriptions() {
    $pgs=eme_payment_gateways();
    $configured_pgs = eme_get_configured_pgs();
    foreach ($pgs as $pg=>$desc) {
        if (!in_array($pg,$configured_pgs))
            unset($pgs[$pg]);
    }
    return $pgs;
}

function eme_event_can_pay_online( $event ) {
    // count the online payment gateways active for this event
    if ( empty( $event['event_properties']['payment_gateways'] ) || !is_array( $event['event_properties']['payment_gateways'] ) )
        return 0;
    $pgs      = eme_get_configured_pgs();
    $pg_count = 0;
    foreach ( $pgs as $pg ) {
        if ( !eme_is_offline_pg($pg) && in_array($pg, $event['event_properties']['payment_gateways']) ) {
            ++$pg_count;
        }
    }
    return $pg_count;
}

function eme_event_has_pgs_configured( $event ) {
    if ( eme_event_count_pgs( $event ) > 0 ) {
        return true;
    }
    return false;
}

function eme_membership_can_pay_online( $membership ) {
    // count the payment gateways active for this event
    if ( empty( $membership['properties']['payment_gateways'] ) || !is_array( $membership['properties']['payment_gateways'] ) )
        return 0;
    $pgs      = eme_get_configured_pgs();
    $pg_count = 0;
    foreach ( $pgs as $pg ) {
        if ( !eme_is_offline_pg($pg) && in_array($pg, $membership['properties']['payment_gateways']) ) {
            ++$pg_count;
        }
    }
    return $pg_count;
}

function eme_membership_has_pgs_configured( $membership ) {
    if ( eme_membership_count_pgs( $membership ) > 0 ) {
        return true;
    }
    return false;
}

function eme_event_count_pgs( $event ) {
    // count the payment gateways active for this event
    if ( empty( $event['event_properties']['payment_gateways'] ) || !is_array( $event['event_properties']['payment_gateways'] ) )
        return 0;
    $pgs      = eme_get_configured_pgs();
    $pg_count = 0;
    foreach ( $pgs as $pg ) {
        if ( in_array($pg, $event['event_properties']['payment_gateways']) ) {
            ++$pg_count;
        }
    }
    return $pg_count;
}

function eme_membership_count_pgs( $membership ) {
    // count the payment gateways active for this event
    if ( empty( $membership['properties']['payment_gateways'] ) || !is_array( $membership['properties']['payment_gateways'] ) )
        return 0;
    $pgs      = eme_get_configured_pgs();
    $pg_count = 0;
    foreach ( $pgs as $pg ) {
        if ( in_array($pg, $membership['properties']['payment_gateways']) ) {
            ++$pg_count;
        }
    }
    return $pg_count;
}

function eme_fs_event_count_pgs( ) {
    $pgs      = eme_get_configured_pgs();
    $pg_count = 0;
    $eme_fs_options = get_option('eme_fs');
    foreach ( $pgs as $pg ) {
        if ( isset($eme_fs_options['payment_gateways']) && in_array($pg, $eme_fs_options['payment_gateways']) ) {
            ++$pg_count;
        }
    }
    return $pg_count;
}

function eme_event_get_first_pg( $event ) {
    $pgs      = eme_get_configured_pgs();
    foreach ( $pgs as $pg ) {
        if ( !empty($event['event_properties']['payment_gateways']) && in_array($pg, $event['event_properties']['payment_gateways']) ) {
            return $pg;
        }
    }
    return false;
}

function eme_membership_get_first_pg( $membership ) {
    $pgs      = eme_get_configured_pgs();
    foreach ( $pgs as $pg => $value ) {
        if ( !empty($membership['properties']['payment_gateways']) && in_array($pg, $membership['properties']['payment_gateways']) ) {
            return $pg;
        }
    }
    return false;
}

function eme_fs_event_get_first_pg( ) {
    $eme_fs_options = get_option('eme_fs');
    $pgs      = eme_get_configured_pgs();
    foreach ( $pgs as $pg => $value ) {
        if ( !empty($eme_fs_options['payment_gateways']) && in_array($pg, $eme_fs_options['payment_gateways']) ) {
            return $pg;
        }
    }
    return false;
}

function eme_create_fs_event_payment( $event_id ) {
    global $wpdb;
    $payments_table           = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $payment_id               = false;
    $payment                  = [];
    $payment['random_id']     = eme_random_id();
    $payment['related_id']    = $event_id;
    $payment['target']        = 'fs_event';
    $payment['creation_date'] = current_time( 'mysql', false );
    if ( $wpdb->insert( $payments_table, $payment ) ) {
        $payment_id = $wpdb->insert_id;
    }
    return $payment_id;
}

function eme_create_member_payment( $member_id ) {
    global $wpdb;
    $payments_table           = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $members_table            = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    $payment_id               = false;
    $payment                  = [];
    $payment['random_id']     = eme_random_id();
    $payment['related_id']    = $member_id;
    $payment['target']        = 'member';
    $payment['creation_date'] = current_time( 'mysql', false );
    if ( $wpdb->insert( $payments_table, $payment ) ) {
        $payment_id           = $wpdb->insert_id;
        $where['member_id']   = $member_id;
        $fields['unique_nbr'] = eme_unique_nbr( $payment_id );
        $fields['payment_id'] = $payment_id;
        $wpdb->update( $members_table, $fields, $where );
    }
    return $payment_id;
}

function eme_create_booking_payment( $booking_ids ) {
    global $wpdb;
    $payments_table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;

    // some safety
    if ( ! $booking_ids ) {
        return false;
    }

    $payment_id               = false;
    $payment                  = [];
    $payment['random_id']     = eme_random_id();
    $payment['target']        = 'booking';
    // we don't set the related id here, since multiple bookings can be linked to one payment
    $payment['creation_date'] = current_time( 'mysql', false );
    if ( $wpdb->insert( $payments_table, $payment ) ) {
        $payment_id      = $wpdb->insert_id;
        $booking_ids_arr = explode( ',', $booking_ids );
        foreach ( $booking_ids_arr as $booking_id ) {
            $where                = [];
            $fields               = [];
            $where['booking_id']  = $booking_id;
            $fields['unique_nbr'] = eme_unique_nbr( $payment_id );
            $fields['payment_id'] = $payment_id;
            $wpdb->update( $bookings_table, $fields, $where );
        }
    }
    return $payment_id;
}

function eme_get_payment( $payment_id=0, $payment_randomid = 0 ) {
    global $wpdb;
    $payments_table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;

    if ( $payment_id ) {
        $payment = wp_cache_get( "eme_payment $payment_id" );
    } else {
        $payment = false;
    }

        if ( $payment === false ) {
        if ( $payment_id ) {
            $sql = $wpdb->prepare( "SELECT * FROM $payments_table WHERE id=%d", $payment_id );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM $payments_table WHERE random_id=%s", $payment_randomid );
        }
        $payment = $wpdb->get_row( $sql, ARRAY_A );
        if ($payment_id) {
            wp_cache_set( "eme_payment $payment_id", $payment, '', 10 );
        }
    }
    return $payment;
}

function eme_get_payment_by_pg_pid( $pg_pid ) {
    global $wpdb;
    $payments_table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $sql            = $wpdb->prepare( "SELECT * FROM $payments_table WHERE pg_pid=%s", $pg_pid );
    return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_payment_booking_ids( $payment_id ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT booking_id FROM $table_name WHERE status IN (%d,%d,%d) AND payment_id=%d", EME_RSVP_STATUS_APPROVED, EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $payment_id );
    return $wpdb->get_col( $sql );
}

function eme_get_payment_member_ids( $payment_id ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT member_id FROM $table_name WHERE status IN (%d,%d,%d) AND payment_id=%d", EME_MEMBER_STATUS_GRACE, EME_MEMBER_STATUS_ACTIVE, EME_MEMBER_STATUS_PENDING, $payment_id );
    return $wpdb->get_col( $sql );
}

function eme_get_randompayment_booking_ids( $payment_randomid, $check_trash = 0 ) {
    global $wpdb;
    $payments_table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    if ( $check_trash ) {
        $sql = $wpdb->prepare( "SELECT $bookings_table.booking_id FROM $bookings_table LEFT JOIN $payments_table ON $bookings_table.payment_id=$payments_table.id where $bookings_table.status = %d AND $payments_table.random_id=%s", EME_RSVP_STATUS_TRASH, $payment_randomid );
    } else {
        $sql = $wpdb->prepare( "SELECT $bookings_table.booking_id FROM $bookings_table LEFT JOIN $payments_table ON $bookings_table.payment_id=$payments_table.id where $bookings_table.status IN (%d,%d,%d) AND $payments_table.random_id=%s", EME_RSVP_STATUS_APPROVED, EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $payment_randomid );
    }
    return $wpdb->get_col( $sql );
}

function eme_delete_payment( $payment_id ) {
    global $wpdb;
    $payments_table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $sql            = $wpdb->prepare( "DELETE FROM $payments_table WHERE id=%d", $payment_id );
    wp_cache_delete( "eme_payment ".$payment_id );
    return $wpdb->get_var( $sql );
}

function eme_get_payment_paid( $payment ) {
    $seats = 0;
    if ( $payment['target'] == 'member' ) {
        $member = eme_get_member_by_paymentid( $payment['id'] );
        return $member['paid'];
    } elseif ( $payment['target'] == 'fs_event' ) {
        // events are simple :-)
        return $payment['pg_handled'];
    } else {
        $unpaid_count = eme_payment_count_unpaid_bookings( $payment['id'] );
        if ( $unpaid_count > 0 ) {
            return 0;
        } else {
            return 1;
        }
    }
}
function eme_get_payment_seats( $payment ) {
    $seats = 0;
    // does only work for bookings of course
    if ( $payment['target'] != 'booking' ) {
        return 0;
    }

    $bookings = eme_get_bookings_by_paymentid( $payment['id'] );
    foreach ( $bookings as $booking ) {
        $seats += eme_get_total( $booking['booking_seats'] );
    }
    return $seats;
}

function eme_get_payment_price_novat( $payment_id ) {
    $price    = 0;
    $bookings = eme_get_bookings_by_paymentid( $payment_id );
    foreach ( $bookings as $booking ) {
        $event = eme_get_event($booking['event_id']);
        $total_booking_price = eme_get_total_booking_price( $booking );
        // take into account already received payments (in any possible way)
        if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
            $remaining = $total_booking_price;
        } else {
            $remaining = $booking['remaining'];
        }
        $price += $remaining / ( 1 + $event['event_properties']['vat_pct'] / 100 );
    }
    return $price;
}
function eme_get_payment_price_vatonly( $payment_id ) {
    $price    = 0;
    $bookings = eme_get_bookings_by_paymentid( $payment_id );
    foreach ( $bookings as $booking ) {
        $event = eme_get_event($booking['event_id']);
        $total_booking_price = eme_get_total_booking_price( $booking );
        // take into account already received payments (in any possible way)
        if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
            $remaining = $total_booking_price;
        } else {
            $remaining = $booking['remaining'];
        }
        $price += $remaining - $remaining / ( 1 + $event['event_properties']['vat_pct'] / 100 );
    }
    return $price;
}

function eme_get_payment_price( $payment_id ) {
    $price    = 0;
    $bookings = eme_get_bookings_by_paymentid( $payment_id );
    foreach ( $bookings as $booking ) {
        $total_booking_price = eme_get_total_booking_price( $booking );
        // take into account already received payments (in any possible way)
        if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
            $remaining = $total_booking_price;
        } else {
            $remaining = $booking['remaining'];
        }
        $price += $remaining;
    }
    return $price;
}

function eme_get_member_payment_price( $payment_id ) {
    $price  = 0;
    $member = eme_get_member_by_paymentid( $payment_id );
    $price  = eme_get_total_member_price( $member );
    return $price;
}

function eme_update_attendance_count( $booking_id ) {
    global $wpdb;
    if ( $booking_id ) {
        $table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
        $sql   = $wpdb->prepare( "UPDATE $table SET attend_count=attend_count+1 WHERE booking_id=%d",$booking_id);
        return $wpdb->query( $sql );
    } else {
        return false;
    }
}

function eme_get_attendance_count( $booking_id ) {
    global $wpdb;
    if ( $booking_id ) {
        $table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
        $sql   = $wpdb->prepare( "SELECT attend_count FROM $table WHERE booking_id=%d", $booking_id );
        return $wpdb->get_var( $sql );
    } else {
        return 0;
    }
}

function eme_update_payment_pg_pid( $payment_id, $pg_pid = '' ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $sql   = $wpdb->prepare( "UPDATE $table SET pg_pid=%s, pg_handled=0 WHERE id=%d", $pg_pid, $payment_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_payment ".$payment_id );
}

function eme_update_payment_pg_handled( $payment_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PAYMENTS_TBNAME;
    $sql   = $wpdb->prepare( "UPDATE $table SET pg_handled=1 WHERE id=%d", $payment_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_payment ".$payment_id );
}

function eme_mark_payment_paid( $payment_id, $is_ipn = 1, $pg = '', $pg_pid = '' ) {
    $payment = eme_get_payment( $payment_id );
    // let's now store the payment gateway id, so we can see if it has been handled already
    // This may overwrite the pg_pid set here by some gateways (like mollie, paypal) but at this point those are no longer needed anyway
    if ( $payment['pg_pid'] != $pg_pid ) {
        eme_update_payment_pg_pid( $payment_id, $pg_pid );
        $payment['pg_handled'] = 0;
    }

    // in case of payment via payment gateway, don't allow to be paid twice for the same pg_pid
    if ( $is_ipn && $payment['pg_handled'] == 1 ) {
        return;
    }

    // ok, it hasn't been paid for yet, so mark that we handled it
    // we do this as soon as possible, so other payments arriving won't trigger another payment
    if ( $is_ipn && $payment['pg_handled'] == 0 ) {
        eme_update_payment_pg_handled( $payment_id );
    }
    if ( $payment['target'] == 'member' ) {
        eme_accept_member_payment( $payment_id, $pg, $pg_pid );
        if ( $is_ipn ) {
            $member = eme_get_member_by_paymentid( $payment_id );
            eme_email_member_action( $member, 'ipnReceived' );
            if ( has_action( 'eme_ipn_member_action' ) ) {
                do_action( 'eme_ipn_member_action', $member );
            }
        }
    } elseif ( $payment['target'] == 'fs_event' ) {
        // set event status to the wanted one of FS
        $eme_fs_options = get_option('eme_fs');
        eme_change_event_status($payment['related_id'],$eme_fs_options['auto_publish']);
        if ( $is_ipn ) {
            $event = eme_get_event($payment['related_id']);
            eme_email_fs_event_action( $event, 'ipnReceived' );
            if ( has_action( 'eme_ipn_fs_event_action' ) ) {
                do_action( 'eme_ipn_fs_event_action', $event );
            }
        }
        return;
    } else {
        $booking_ids = eme_get_payment_booking_ids( $payment_id );
        $total_price = eme_get_payment_price( $payment_id );
        foreach ( $booking_ids as $booking_id ) {
            $booking = eme_get_booking( $booking_id );
            $event   = eme_get_event( $booking['event_id'] );
            if ( empty( $event ) ) {
                    continue;
            }
            $mailing_approved = get_option( 'eme_rsvp_mail_notify_approved' );
            $mailing_paid     = get_option( 'eme_rsvp_mail_notify_paid' );

            $mail_sent = 0;
            if ( $event['event_properties']['auto_approve'] && $booking['status'] == EME_RSVP_STATUS_PENDING && ! $booking['waitinglist'] ) {
                if ( $event['event_properties']['ignore_pending'] ) {
                    // we check for available seats, excluding waiting list, and excluding this pending booking
                    // reason: it is very well possible that the booking is pending, being paid for but other bookings already happened and the event is now "full"
                    $seats_available = eme_are_seats_available_for( $booking['event_id'], $booking['booking_seats'], 1, $booking['booking_id'] );
                    if ( $seats_available ) {
                        $res = eme_mark_booking_paid_approved( $booking, $pg, $pg_pid );
                        if ( $res ) {
                            //booking changed, let's get it again
                            $booking = eme_get_booking( $booking_id );
                            // if the option to send a mail after payment is received is active, we don't send a second mail for approval
                            // However: if the price to pay is 0, then no payment mail is sent ... so then we do send the approval mail
                            if ( !$mail_sent && $mailing_approved && ( ! $mailing_paid || $total_price == 0 ) ) {
                                eme_email_booking_action( $booking, 'approveBooking' );
                                $mail_sent = 1;
                            }
                        }
                    } else {
                        // a pending booking got paid via payment gateway and autoapprove is on, but
                        // no more seats available ==> we'll send out a special mail then
                        $res = eme_mark_booking_paid( $booking, $pg, $pg_pid );
                        if ( $res ) {
                            //booking changed, let's get it again
                            $booking = eme_get_booking( $booking_id );
                            eme_email_booking_action( $booking, 'pendingButPaid' );
                        }
                    }
                } else {
                    $res = eme_mark_booking_paid_approved( $booking, $pg, $pg_pid );
                    if ( $res ) {
                        //booking changed, let's get it again
                        $booking = eme_get_booking( $booking_id );
                        // if the option to send a mail after payment is received is active, we don't send a second mail for approval
                        // However: if the price to pay is 0, then no payment mail is sent ... so then we do send the approval mail
                        if ( !$mail_sent && $mailing_approved && ( ! $mailing_paid || $total_price == 0 ) ) {
                            eme_email_booking_action( $booking, 'approveBooking' );
                            $mail_sent = 1;
                        }
                    }
                }
            } else {
                $res = eme_mark_booking_paid( $booking, $pg, $pg_pid );
                if ( $res ) {
                    //booking changed, let's get it again
                    $booking = eme_get_booking( $booking_id );
                }
            }

            // Send the paid email if the event price is >0, not when the total price to pay is >0, since that can be 0 due to discount
            $booking_event_price = eme_get_booking_event_price( $booking );
            if ( !$mail_sent && $mailing_paid && ($total_price > 0 || ($total_price==0 && $booking_event_price>0 && !$mailing_approved) ) ) {
                eme_email_booking_action( $booking, 'paidBooking' );
            }

            if ( $is_ipn ) {
                eme_email_booking_action( $booking, 'ipnReceived' );
                if ( has_action( 'eme_ipn_action' ) ) {
                    do_action( 'eme_ipn_action', $booking );
                }
            }
        }
    }
}

function eme_replace_payment_gateway_placeholders( $format, $pg, $total_price, $currency, $vat_pct, $target='html', $lang='', $do_shortcode = 1 ) {
    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }
    if (empty($lang)) {
        $lang = eme_detect_lang();
    }

    $charge        = eme_payment_gateway_extra_charge( $total_price, $pg );
    $needle_offset = 0;
    preg_match_all( '/#(ESC)?_[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
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

        if ( preg_match( '/#_EXTRACHARGE$/', $result ) ) {
            $replacement = eme_localized_price( $charge, $currency, $target );
        } elseif ( preg_match( '/#_EXTRACHARGE_NO_VAT$/', $result ) ) {
            $price       = $charge / ( 1 + $vat_pct / 100 );
            $replacement = eme_localized_price( $price, $currency, $target );
        } elseif ( preg_match( '/#_EXTRACHARGE_VAT_ONLY$/', $result ) ) {
            $price       = $charge - $charge / ( 1 + $vat_pct / 100 );
            $replacement = eme_localized_price( $charge, $currency, $target );
        } elseif ( preg_match( '/#_CURRENCY$/', $result ) ) {
            $replacement = $currency;
        } elseif ( preg_match( '/#_CURRENCYSYMBOL$/', $result ) ) {
            $replacement = eme_localized_currencysymbol( $currency );
        } elseif ( preg_match( '/#_PRICE_INCLUDING_CHARGES$|#_GATEWAY_PRICE$/', $result ) ) {
            $price       = $total_price + $charge;
            $replacement = eme_localized_price( $price, $currency );
        } elseif ( preg_match( '/#_PRICE_INCLUDING_CHARGES_NO_VAT$|#_GATEWAY_PRICE_NO_VAT$/', $result ) ) {
            $price       = $total_price + $charge;
            $price       = $price / ( 1 + $vat_pct / 100 );
            $replacement = eme_localized_price( $price, $currency, $target );
        } elseif ( preg_match( '/#_PRICE_INCLUDING_CHARGES_VAT_ONLY$|#_GATEWAY_PRICE_VAT_ONLY$/', $result ) ) {
            $price       = $total_price + $charge;
            $price       = $price - $price / ( 1 + $vat_pct / 100 );
            $replacement = eme_localized_price( $price, $currency, $target );
        } elseif ( preg_match( '/#_PAYMENTGATEWAYUSED$/', $result ) ) {
            $pgs         = eme_payment_gateways();
            $replacement = $pgs[ $pg ];
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

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format, $lang );

    if ( $do_shortcode ) {
        return do_shortcode( $format );
    } else {
        return $format;
    }
}

function eme_payment_count_unpaid_bookings( $payment_id ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE status IN (%d,%d,%d) AND payment_id=%d AND booking_paid=0", EME_RSVP_STATUS_APPROVED, EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, $payment_id );
    return $wpdb->get_var( $sql );
}

function eme_refund_booking( $booking ) {
    if ( empty( $booking['pg'] ) || empty( $booking['pg_pid'] ) ) {
        return;
    }
    $pg = $booking['pg'];
    // let's use variable function names
    $refund_function = 'eme_refund_booking_' . $pg;
    if ( function_exists( $refund_function ) ) {
        $res = $refund_function( $booking );
        if ( $res ) {
            // now that the refund is done, mark it as unpaid
            return eme_mark_booking_unpaid( $booking );
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function eme_cancel_payment_form( $payment_randomid ) {
    // not used from the admin backend, but we check to be sure
    if ( eme_is_admin_request() ) {
        return;
    }

    $booking_ids = eme_get_randompayment_booking_ids( $payment_randomid );
    if ( empty( $booking_ids ) ) {
        // check trash (already cancelled)
        $trashed_booking_ids = eme_get_randompayment_booking_ids( $payment_randomid, 1 );
        if ( empty( $trashed_booking_ids ) ) {
            // nothing found in trash either: return a generic message
            return "<div id='eme-cancel-payment-message-error' class='eme-message-error eme-cancel-payment eme-cancel-payment-error'>" . __( 'Nothing found or booking already cancelled', 'events-made-easy' ) . '</div>';
        } else {
            // something found in trash: so already cancelled
            return "<div id='eme-cancel-payment-message-error' class='eme-message-error eme-cancel-payment eme-cancel-payment-error'>" . __( 'Booking already cancelled', 'events-made-easy' ) . '</div>';
        }
    }
    $format = get_option( 'eme_cancel_payment_form_format' );

    // we need to know who did the booking, so get the first bookings person_id
    $person_ids = eme_get_booking_personids( $booking_ids );
    $person     = eme_get_person( $person_ids[0] );

    $form_id = uniqid();
    $nonce   = wp_nonce_field( "cancel payment $payment_randomid", 'eme_frontend_nonce', false, false );

    $output  = "<div id='eme-cancel-payment-message-ok-$form_id' class='eme-message-success eme-cancel-payment-message eme-cancel-payment-message-success eme-hidden'></div><div id='eme-cancel-payment-message-error-$form_id' class='eme-message-error eme-cancel-payment-message eme-cancel-payment-message-error eme-hidden'></div><div id='div_eme-cancel-payment-form-$form_id'><form id='$form_id' name='eme-cancel-payment-form' method='post' action='#'>
                $nonce
                <span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
                <input type='hidden' name='eme_pmt_rndid' value='" . $payment_randomid . "'>
                ";
    $output .= eme_replace_cancel_payment_placeholders( $format, $person, $booking_ids );
    $output .= '</form>';
    return $output;
}

add_action( 'wp_ajax_eme_cancel_payment', 'eme_cancel_payment_ajax' );
add_action( 'wp_ajax_nopriv_eme_cancel_payment', 'eme_cancel_payment_ajax' );
add_action( 'wp_ajax_eme_get_payconiq_iban', 'eme_ajax_get_payconiq_iban' );

function eme_cancel_payment_ajax() {
    $payment_randomid = eme_sanitize_request( $_POST['eme_pmt_rndid'] );
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
    if ( empty( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( $_POST['eme_frontend_nonce'], "cancel payment $payment_randomid" ) ) {
        $form_html = __( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $form_html,
            ]
        );
        wp_die();
    }

    // check the captchas
    $captcha_res = eme_check_captcha();

    $format      = get_option( 'eme_cancelled_payment_format' );
    $booking_ids = eme_get_randompayment_booking_ids( $payment_randomid );
    if ( empty( $booking_ids ) ) {
        $trashed_booking_ids = eme_get_randompayment_booking_ids( $payment_randomid, 1 );
        if ( empty( $trashed_booking_ids ) ) {
            // nothing found in trash either: return a generic message
            $form_html = __( 'Nothing found or booking already cancelled', 'events-made-easy' );
        } else {
            // something found in trash: so already cancelled
            $form_html = __( 'Booking already cancelled', 'events-made-easy' );
        }
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $form_html,
            ]
        );
        wp_die();
    }
    // we need to know who did the booking, so get the first bookings person_id
    $person_ids       = eme_get_booking_personids( $booking_ids );
    $person           = eme_get_person( $person_ids[0] );
    $tmp_format       = get_option( 'eme_cancel_payment_line_format' );
    $replacement      = '';
    $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
    foreach ( $booking_ids as $booking_id ) {
        $booking = eme_get_booking( $booking_id );
        $event   = eme_get_event( $booking['event_id'] );
        if ( empty( $event ) ) {
            continue;
        }
        // first the rsvp cutoff based on event start date
        $cancel_cutofftime    = new ExpressiveDate( $event['event_start'], EME_TIMEZONE );
        $eme_cancel_rsvp_days = -1 * $event['event_properties']['cancel_rsvp_days'];
        $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
        if ( $cancel_cutofftime < $eme_date_obj_now ) {
            // cancel no longer allowed for this booking: continue the loop
            continue;
        }
        // second the rsvp cutoff based on booking age
        $cancel_cutofftime    = new ExpressiveDate( $booking['creation_date'], EME_TIMEZONE );
        $eme_cancel_rsvp_days = $event['event_properties']['cancel_rsvp_age'];
        $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
        if ( $eme_cancel_rsvp_days && $cancel_cutofftime < $eme_date_obj_now ) {
            // cancel no longer allowed for this booking: continue the loop
            continue;
        }

        if ( has_action( 'eme_frontend_cancel_booking_action' ) ) {
            do_action( 'eme_frontend_cancel_booking_action', $booking );
        }
        // delete the booking before the mail is sent, so free seats are correct
        eme_trash_booking( $booking_id );
        eme_manage_waitinglist( $event );
        eme_email_booking_action( $booking, 'cancelBooking' );
        $replacement .= eme_replace_booking_placeholders( $tmp_format, $event, $booking );
    }
    $form_html = str_replace( '#_CANCEL_PAYMENT_LINE', $replacement, $format );

    // replace leftover placeholders at the end
    $form_html = eme_replace_people_placeholders( $form_html, $person );

    // don't delete the linked payment, since the booking is in trash and can still be restored
    // eme_delete_payment($booking['payment_id']);

    eme_captcha_remove( $captcha_res );
    echo wp_json_encode(
        [
            'Result'      => 'OK',
            'htmlmessage' => $form_html,
        ]
    );
    wp_die();
}

function eme_ajax_get_payconiq_iban() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $ajaxResult              = [];

    if ( ! (
        current_user_can( get_option( 'eme_cap_registrations' ) ) ||
        current_user_can( get_option( 'eme_cap_author_registrations' ) ) ||
        current_user_can( get_option( 'eme_cap_approve' ) ) ||
        current_user_can( get_option( 'eme_cap_author_approve' ) ) ||
        current_user_can( get_option( 'eme_cap_list_members' ) )
    ) ) {
        $ajaxResult['Result']      = 'Error';
        $ajaxResult['htmlmessage'] = __( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }
    $api_key = get_option( "eme_payconiq_api_key" );
    if ( ! $api_key ) {
        wp_die();
    }
    if ( ! class_exists( 'Payconiq\Client' ) ) {
        require_once 'payment_gateways/payconiq/src/Client.php';
    }

    $pg_pid   = eme_sanitize_request( $_POST['pg_pid'] );
    $mode     = get_option( 'eme_payconiq_env' );
    $payconiq = new \Payconiq\Client( $api_key );
    if ( preg_match( '/sandbox/', $mode ) ) {
        $payconiq->setEndpointTest();
    }
    try {
        $iban = $payconiq->getRefundIban( $pg_pid );
    } catch ( Exception $e ) {
        wp_die();
    }

    $ajaxResult = [];
    $ajaxResult['iban'] = $iban;
    $payment = eme_get_payment_by_pg_pid( $pg_pid );
    $ajaxResult['payment_id'] = $payment['id']; 
    print wp_json_encode( $ajaxResult );
    wp_die();
}

function echo_configured_pg($pg) {
    $configured_pgs = eme_get_configured_pgs();
    if (in_array($pg, $configured_pgs))
        echo '&nbsp;<b style="color: green;">'.__('Configured','events-made-easy').'</b>';
}
