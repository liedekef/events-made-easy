<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Seat maps: an alternative way to fill in the seat fields of the RSVP form.
// The seatmap only decides WHICH seats a booking holds; the number of seats per price category is always
// derived from the selected seats on the server (eme_booking_from_form), so all existing checks and
// the price calculation keep working. Availability is derived from the booking status: a seat is taken
// when a pending/userpending/approved booking holds it. Trashed bookings keep their seat rows.

function eme_seatlayout_active_statuses() {
    return implode( ',', [ intval( EME_RSVP_STATUS_PENDING ), intval( EME_RSVP_STATUS_USERPENDING ), intval( EME_RSVP_STATUS_APPROVED ) ] );
}

function eme_seatlayout_row_letter( $row ) {
    $letter = '';
    $row    = intval( $row );
    while ( $row > 0 ) {
        --$row;
        $letter = chr( 65 + ( $row % 26 ) ) . $letter;
        $row    = intval( $row / 26 );
    }
    return $letter;
}

function eme_seatlayout_default_label( $row, $col ) {
    return eme_seatlayout_row_letter( $row ) . intval( $col );
}

function eme_seatlayout_get_layout( $event_id, $refresh = false ) {
    global $wpdb;
    static $cache = [];
    $event_id = intval( $event_id );
    if ( $refresh || ! array_key_exists( $event_id, $cache ) ) {
        $table = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
        $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE event_id = %d", $event_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row   = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $cache[ $event_id ] = $row ? $row : [];
    }
    return $cache[ $event_id ];
}

function eme_seatlayout_get_seats( $layout_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_SEATLAYOUT_SEATS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE layout_id = %d ORDER BY seat_row ASC, seat_col ASC", $layout_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// only returns seats that belong to the layout of this event
function eme_seatlayout_get_seats_by_ids( $event_id, $seat_ids ) {
    global $wpdb;
    $seat_ids = array_values( array_unique( array_map( 'intval', (array) $seat_ids ) ) );
    if ( empty( $seat_ids ) ) {
        return [];
    }
    $seats_table   = EME_DB_PREFIX . EME_SEATLAYOUT_SEATS_TBNAME;
    $layouts_table = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
    $placeholders  = implode( ',', array_fill( 0, count( $seat_ids ), '%d' ) );
    $sql           = $wpdb->prepare( "SELECT seats.* FROM $seats_table AS seats JOIN $layouts_table AS layouts ON seats.layout_id = layouts.layout_id WHERE layouts.event_id = %d AND seats.seat_id IN ($placeholders) ORDER BY seats.seat_row, seats.seat_col", $event_id, ...$seat_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// the format of the booking form that is used for this event
function eme_seatlayout_get_form_format( $event ) {
    if ( ! eme_is_empty_string( $event['event_registration_form_format'] ?? '' ) ) {
        return $event['event_registration_form_format'];
    }
    if ( intval( $event['event_properties']['event_registration_form_format_tpl'] ?? 0 ) > 0 ) {
        return eme_get_template_format( $event['event_properties']['event_registration_form_format_tpl'] );
    }
    return get_option( 'eme_registration_form_format' );
}

// Seatmap mode is decided server-side: the event has a layout and its booking form contains #_SEATMAP.
// Never decide this based on posted data.
function eme_seatlayout_event_uses_seatmap( $event, $format = null ) {
    if ( empty( $event['event_id'] ) ) {
        return false;
    }
    $layout = eme_seatlayout_get_layout( $event['event_id'] );
    if ( empty( $layout ) ) {
        return false;
    }
    if ( is_null( $format ) || eme_is_empty_string( $format ) ) {
        $format = eme_seatlayout_get_form_format( $event );
    }
    return strstr( (string) $format, '#_SEATMAP' ) !== false;
}

function eme_seatlayout_get_posted_seat_ids( $event_id ) {
    if ( empty( $_POST['bookings'][ $event_id ]['seat_ids'] ) || ! is_array( $_POST['bookings'][ $event_id ]['seat_ids'] ) ) {
        return [];
    }
    $seat_ids = [];
    foreach ( wp_unslash( $_POST['bookings'][ $event_id ]['seat_ids'] ) as $seat_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( is_array( $seat_id ) ) {
            continue;
        }
        $seat_id = intval( $seat_id );
        if ( $seat_id > 0 ) {
            $seat_ids[] = $seat_id;
        }
    }
    return array_slice( array_values( array_unique( $seat_ids ) ), 0, 1000 );
}

function eme_seatlayout_price_categories( $event ) {
    $prices       = eme_convert_multi2array( $event['price'] ?? '' );
    $descriptions = eme_convert_multi2array( $event['event_properties']['multiprice_desc'] ?? '' );
    $categories   = [];
    foreach ( $prices as $key => $price ) {
        $label = isset( $descriptions[ $key ] ) ? trim( $descriptions[ $key ] ) : '';
        if ( $label === '' ) {
            // translators: %s is the price of a category
            $label = sprintf( __( 'Price %s', 'events-made-easy' ), eme_localized_price( $price, $event['currency'] ?? '' ) );
        }
        $categories[ $key ] = $label;
    }
    if ( empty( $categories ) ) {
        $categories[0] = __( 'Standard', 'events-made-easy' );
    }
    return $categories;
}

// number of seats (total and per price category) for a list of seat ids; unknown ids are ignored
function eme_seatlayout_counts_for_seat_ids( $event, $seat_ids ) {
    $count = count( eme_seatlayout_price_categories( $event ) );
    $mp    = array_fill( 0, $count, 0 );
    $total = 0;
    foreach ( eme_seatlayout_get_seats_by_ids( $event['event_id'], $seat_ids ) as $seat ) {
        $index = min( max( intval( $seat['price_index'] ), 0 ), $count - 1 );
        ++$mp[ $index ];
        ++$total;
    }
    return [
        'total' => $total,
        'mp'    => $mp,
    ];
}

function eme_seatlayout_get_taken_seat_ids( $event_id, $exclude_booking_id = 0 ) {
    global $wpdb;
    $seat_bookings  = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $statuses       = eme_seatlayout_active_statuses();
    $sql            = $wpdb->prepare( "SELECT sb.seat_id FROM $seat_bookings AS sb JOIN $bookings_table AS b ON b.booking_id = sb.booking_id WHERE sb.event_id = %d AND sb.booking_id != %d AND b.status IN ($statuses)", $event_id, $exclude_booking_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// seats referenced by any booking, whatever its status (trashed bookings keep their seats)
function eme_seatlayout_get_reserved_seat_ids( $event_id ) {
    global $wpdb;
    $seat_bookings = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $sql           = $wpdb->prepare( "SELECT DISTINCT seat_id FROM $seat_bookings WHERE event_id = %d", $event_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_seatlayout_get_seat_ids_for_booking( $booking_id ) {
    global $wpdb;
    $seat_bookings = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $sql           = $wpdb->prepare( "SELECT seat_id FROM $seat_bookings WHERE booking_id = %d", $booking_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_seatlayout_get_labels_for_booking( $booking_id ) {
    global $wpdb;
    $seat_bookings = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $seats_table   = EME_DB_PREFIX . EME_SEATLAYOUT_SEATS_TBNAME;
    $sql           = $wpdb->prepare( "SELECT seats.seat_row, seats.seat_col, seats.seat_label FROM $seat_bookings AS sb JOIN $seats_table AS seats ON seats.seat_id = sb.seat_id WHERE sb.booking_id = %d ORDER BY seats.seat_row, seats.seat_col", $booking_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $labels        = [];
    foreach ( $wpdb->get_results( $sql, ARRAY_A ) as $seat ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $labels[] = $seat['seat_label'] !== '' ? $seat['seat_label'] : eme_seatlayout_default_label( $seat['seat_row'], $seat['seat_col'] );
    }
    return $labels;
}

// checks that the seats exist for this event, can be booked and are free (optionally ignoring the seats of one booking)
function eme_seatlayout_validate_seat_ids( $event_id, $seat_ids, $exclude_booking_id = 0 ) {
    $valid = [
        'valid'   => true,
        'message' => '',
    ];
    $seat_ids = array_values( array_unique( array_map( 'intval', (array) $seat_ids ) ) );
    if ( empty( $seat_ids ) ) {
        return $valid;
    }
    $invalid = [
        'valid'   => false,
        'message' => __( 'One or more of the selected seats are no longer available. Please choose other seats.', 'events-made-easy' ),
    ];
    $seats = eme_seatlayout_get_seats_by_ids( $event_id, $seat_ids );
    if ( count( $seats ) != count( $seat_ids ) ) {
        return $invalid;
    }
    // a disabled seat can only be kept by the booking that already holds it
    $own_seat_ids = $exclude_booking_id ? eme_seatlayout_get_seat_ids_for_booking( $exclude_booking_id ) : [];
    foreach ( $seats as $seat ) {
        if ( $seat['disabled'] && ! in_array( intval( $seat['seat_id'] ), $own_seat_ids, true ) ) {
            return $invalid;
        }
    }
    if ( array_intersect( $seat_ids, eme_seatlayout_get_taken_seat_ids( $event_id, $exclude_booking_id ) ) ) {
        return $invalid;
    }
    return $valid;
}

// One named lock per event: seat check + booking insert + seat rows insert must be atomic.
// The lock is released on every path. Calls inside a lock already held by this request just run.
function eme_seatlayout_with_lock( $event_id, $callback, $run_unlocked_on_failure = false ) {
    global $wpdb;
    static $held = [];
    $event_id = intval( $event_id );
    if ( ! empty( $held[ $event_id ] ) ) {
        return $callback();
    }
    $name = 'eme_seats_' . substr( md5( $wpdb->dbname . EME_DB_PREFIX ), 0, 12 ) . '_' . $event_id;
    $got  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $name ) );
    if ( 1 !== intval( $got ) ) {
        error_log( "EME: could not get the seat lock for event $event_id" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        return $run_unlocked_on_failure ? $callback() : false;
    }
    $held[ $event_id ] = true;
    try {
        return $callback();
    } finally {
        unset( $held[ $event_id ] );
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
    }
}

function eme_seatlayout_insert_booking_seats( $booking_id, $event_id, $seat_ids ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    foreach ( array_values( array_unique( array_map( 'intval', (array) $seat_ids ) ) ) as $seat_id ) {
        $res = $wpdb->insert(
            $table,
            [
                'booking_id' => intval( $booking_id ),
                'event_id'   => intval( $event_id ),
                'seat_id'    => $seat_id,
            ],
            [ '%d', '%d', '%d' ]
        );
        if ( ! $res ) {
            eme_seatlayout_delete_booking_seats( $booking_id );
            return false;
        }
    }
    return true;
}

function eme_seatlayout_delete_booking_seats( $booking_id ) {
    global $wpdb;
    $wpdb->delete( EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME, [ 'booking_id' => intval( $booking_id ) ], [ '%d' ] );
}

function eme_seatlayout_delete_seat_bookings_for_event( $event_id ) {
    global $wpdb;
    $wpdb->delete( EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME, [ 'event_id' => intval( $event_id ) ], [ '%d' ] );
}

// must be called BEFORE the bookings of these persons are deleted
function eme_seatlayout_delete_seat_bookings_for_persons( $person_ids_arr ) {
    global $wpdb;
    if ( empty( $person_ids_arr ) ) {
        return;
    }
    $seat_bookings  = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $placeholders   = implode( ',', array_fill( 0, count( $person_ids_arr ), '%d' ) );
    $sql            = $wpdb->prepare( "DELETE FROM $seat_bookings WHERE booking_id IN (SELECT booking_id FROM $bookings_table WHERE person_id IN ($placeholders))", ...$person_ids_arr ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// deletes the layout, its seats and all seat reservations of an event (used when the event itself is deleted)
function eme_seatlayout_delete_event_seats( $event_id ) {
    global $wpdb;
    $layout = eme_seatlayout_get_layout( $event_id, true );
    eme_seatlayout_delete_seat_bookings_for_event( $event_id );
    if ( ! empty( $layout ) ) {
        $wpdb->delete( EME_DB_PREFIX . EME_SEATLAYOUT_SEATS_TBNAME, [ 'layout_id' => intval( $layout['layout_id'] ) ], [ '%d' ] );
        $wpdb->delete( EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME, [ 'layout_id' => intval( $layout['layout_id'] ) ], [ '%d' ] );
        eme_seatlayout_get_layout( $event_id, true );
    }
}

// Called after a booking got an active status (approved, pending, userpending): its seats may have been
// sold to somebody else while the booking was trashed. A seat that is disabled is NOT a conflict,
// disabled only blocks new selections. Returns false (and trashes the booking again) on a conflict.
function eme_seatlayout_restore_booking( $booking_id ) {
    global $wpdb;
    $booking_id    = intval( $booking_id );
    $seat_bookings = EME_DB_PREFIX . EME_SEAT_BOOKINGS_TBNAME;
    $bookings      = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $sql           = $wpdb->prepare( "SELECT event_id, seat_id FROM $seat_bookings WHERE booking_id = %d", $booking_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rows          = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    if ( empty( $rows ) ) {
        return true;
    }
    $event_id = intval( $rows[0]['event_id'] );
    $seat_ids = array_map( 'intval', array_column( $rows, 'seat_id' ) );
    return eme_seatlayout_with_lock(
        $event_id,
        function () use ( $wpdb, $booking_id, $seat_bookings, $bookings, $seat_ids ) {
            $placeholders = implode( ',', array_fill( 0, count( $seat_ids ), '%d' ) );
            $statuses     = eme_seatlayout_active_statuses();
            $sql          = $wpdb->prepare( "SELECT sb.booking_id FROM $seat_bookings AS sb JOIN $bookings AS b ON b.booking_id = sb.booking_id WHERE sb.seat_id IN ($placeholders) AND sb.booking_id != %d AND b.status IN ($statuses) LIMIT 1", ...array_merge( $seat_ids, [ $booking_id ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ( ! $wpdb->get_var( $sql ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                return true;
            }
            error_log( "EME: booking $booking_id could not get its seats back (sold to somebody else), it has been trashed again" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            eme_trash_booking( $booking_id );
            return false;
        },
        true
    );
}

/* ------------------------------------------------------------------ */
/* saving the layout from the event edit form                          */
/* ------------------------------------------------------------------ */

// returns a notice for the admin (or an empty string)
function eme_seatlayout_save_from_post( $event_id, $event ) {
    global $wpdb;
    if ( ! isset( $_POST['eme_seatlayout_json'] ) ) {
        return '';
    }
    $data = json_decode( wp_unslash( $_POST['eme_seatlayout_json'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every value is validated below
    if ( ! is_array( $data ) ) {
        return '';
    }
    $event_id     = intval( $event_id );
    $layouts      = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
    $seats_table  = EME_DB_PREFIX . EME_SEATLAYOUT_SEATS_TBNAME;
    $price_count  = count( eme_seatlayout_price_categories( $event ) );
    $rows         = min( 60, max( 0, intval( $data['rows'] ?? 0 ) ) );
    $cols         = min( 60, max( 0, intval( $data['cols'] ?? 0 ) ) );
    $layout       = eme_seatlayout_get_layout( $event_id, true );
    $existing     = [];
    $reserved     = [];
    $notice       = '';
    if ( $layout ) {
        $reserved = array_flip( eme_seatlayout_get_reserved_seat_ids( $event_id ) );
        foreach ( eme_seatlayout_get_seats( $layout['layout_id'] ) as $seat ) {
            $existing[ intval( $seat['seat_row'] ) . ':' . intval( $seat['seat_col'] ) ] = $seat;
            // the grid never shrinks below a seat that is part of a booking
            if ( isset( $reserved[ intval( $seat['seat_id'] ) ] ) ) {
                if ( $seat['seat_row'] > $rows || $seat['seat_col'] > $cols ) {
                    $rows   = max( $rows, intval( $seat['seat_row'] ) );
                    $cols   = max( $cols, intval( $seat['seat_col'] ) );
                    $notice = __( 'The seat layout was not reduced completely: seats that are part of a booking cannot be removed.', 'events-made-easy' );
                }
            }
        }
    }

    // the wanted seats, indexed by position; identity is always the position, never a posted seat id
    $wanted = [];
    foreach ( (array) ( $data['seats'] ?? [] ) as $cell ) {
        if ( ! is_array( $cell ) ) {
            continue;
        }
        $row = intval( $cell['r'] ?? 0 );
        $col = intval( $cell['c'] ?? 0 );
        if ( $row < 1 || $row > $rows || $col < 1 || $col > $cols ) {
            continue;
        }
        $label = ( isset( $cell['l'] ) && is_string( $cell['l'] ) ) ? trim( sanitize_text_field( $cell['l'] ) ) : '';
        if ( $label === '' ) {
            $label = eme_seatlayout_default_label( $row, $col );
        }
        $wanted[ $row . ':' . $col ] = [
            'r' => $row,
            'c' => $col,
            'l' => mb_substr( $label, 0, 20 ),
            'p' => min( max( intval( $cell['p'] ?? 0 ), 0 ), $price_count - 1 ),
            'd' => empty( $cell['d'] ) ? 0 : 1,
        ];
    }

    if ( empty( $wanted ) ) {
        if ( $layout ) {
            if ( empty( $reserved ) ) {
                eme_seatlayout_delete_event_seats( $event_id );
            } else {
                // bookings still use seats: keep the layout, but nothing can be selected anymore
                $wpdb->query( $wpdb->prepare( "UPDATE $seats_table SET disabled = 1 WHERE layout_id = %d", $layout['layout_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $notice = __( 'The seat layout cannot be removed because bookings use its seats; all seats have been disabled instead.', 'events-made-easy' );
            }
        }
        return $notice;
    }

    if ( $layout ) {
        $layout_id = intval( $layout['layout_id'] );
        if ( $layout['layout_rows'] != $rows || $layout['layout_cols'] != $cols ) {
            $wpdb->update( $layouts, [ 'layout_rows' => $rows, 'layout_cols' => $cols ], [ 'layout_id' => $layout_id ], [ '%d', '%d' ], [ '%d' ] );
        }
    } else {
        $wpdb->insert( $layouts, [ 'event_id' => $event_id, 'layout_rows' => $rows, 'layout_cols' => $cols ], [ '%d', '%d', '%d' ] );
        $layout_id = intval( $wpdb->insert_id );
        if ( ! $layout_id ) {
            return $notice;
        }
    }

    $inserts      = [];
    $delete_ids   = [];
    $disable_ids  = [];
    foreach ( $existing as $key => $seat ) {
        $seat_id     = intval( $seat['seat_id'] );
        $is_reserved = isset( $reserved[ $seat_id ] );
        if ( isset( $wanted[ $key ] ) ) {
            $want  = $wanted[ $key ];
            $price = $is_reserved ? intval( $seat['price_index'] ) : $want['p']; // the price category of a booked seat does not change
            if ( $seat['seat_label'] !== $want['l'] || intval( $seat['price_index'] ) !== $price || intval( $seat['disabled'] ) !== $want['d'] ) {
                $wpdb->update( $seats_table, [ 'seat_label' => $want['l'], 'price_index' => $price, 'disabled' => $want['d'] ], [ 'seat_id' => $seat_id ], [ '%s', '%d', '%d' ], [ '%d' ] );
            }
            unset( $wanted[ $key ] );
        } elseif ( $is_reserved ) {
            if ( ! $seat['disabled'] ) {
                $disable_ids[] = $seat_id;
            }
        } else {
            $delete_ids[] = $seat_id;
        }
    }
    $inserts = array_values( $wanted ); // what is left is new

    foreach ( [ 'delete' => $delete_ids, 'disable' => $disable_ids ] as $what => $ids ) {
        if ( empty( $ids ) ) {
            continue;
        }
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        if ( 'delete' === $what ) {
            $sql = $wpdb->prepare( "DELETE FROM $seats_table WHERE seat_id IN ($placeholders)", ...$ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            $sql = $wpdb->prepare( "UPDATE $seats_table SET disabled = 1 WHERE seat_id IN ($placeholders)", ...$ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
    foreach ( array_chunk( $inserts, 200 ) as $chunk ) {
        $values = [];
        $args   = [];
        foreach ( $chunk as $seat ) {
            $values[] = '(%d,%d,%d,%s,%d,%d)';
            array_push( $args, $layout_id, $seat['r'], $seat['c'], $seat['l'], $seat['p'], $seat['d'] );
        }
        $sql = $wpdb->prepare( "INSERT INTO $seats_table (layout_id, seat_row, seat_col, seat_label, price_index, disabled) VALUES " . implode( ',', $values ), ...$args ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
    eme_seatlayout_get_layout( $event_id, true );
    return $notice;
}

/* ------------------------------------------------------------------ */
/* admin: the layout editor in the event form                          */
/* ------------------------------------------------------------------ */

function eme_seatlayout_render_admin( $event ) {
    $is_duplicate = ! empty( $event['orig_id'] );
    $source_id    = $is_duplicate ? intval( $event['orig_id'] ) : intval( $event['event_id'] ?? 0 );
    $layout       = $source_id ? eme_seatlayout_get_layout( $source_id ) : [];
    $categories   = eme_seatlayout_price_categories( $event );
    $payload      = [
        'rows'  => 0,
        'cols'  => 0,
        'seats' => [],
    ];
    if ( $layout ) {
        // a copy of an event does not copy its reservations
        $reserved        = $is_duplicate ? [] : array_flip( eme_seatlayout_get_reserved_seat_ids( $source_id ) );
        $payload['rows'] = intval( $layout['layout_rows'] );
        $payload['cols'] = intval( $layout['layout_cols'] );
        foreach ( eme_seatlayout_get_seats( $layout['layout_id'] ) as $seat ) {
            $payload['seats'][] = [
                'r' => intval( $seat['seat_row'] ),
                'c' => intval( $seat['seat_col'] ),
                'l' => $seat['seat_label'],
                'p' => intval( $seat['price_index'] ),
                'd' => intval( $seat['disabled'] ),
                'b' => isset( $reserved[ intval( $seat['seat_id'] ) ] ) ? 1 : 0,
            ];
        }
    }
    $capacity = eme_get_total( $event['event_seats'] ?? 0 );
?>
<div class="inside eme-seatlayout-editor"
    data-categories="<?php echo esc_attr( wp_json_encode( array_values( $categories ) ) ); ?>"
    data-capacity="<?php echo esc_attr( intval( $capacity ) ); ?>"
    data-prompt="<?php echo esc_attr__( 'Seat label', 'events-made-easy' ); ?>"
    data-save="<?php echo esc_attr__( 'Save', 'events-made-easy' ); ?>"
    data-cancel="<?php echo esc_attr__( 'Cancel', 'events-made-easy' ); ?>"
    data-msg-reserved="<?php echo esc_attr__( 'This seat is part of a booking and cannot be removed or get another price category.', 'events-made-easy' ); ?>">
    <input type="hidden" name="eme_seatlayout_json" id="eme_seatlayout_json" value="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
    <p><strong><?php esc_html_e( 'Seat layout', 'events-made-easy' ); ?></strong><?php echo eme_help_button( 'eme-help-seatlayout-intro', __( 'Seat layout', 'events-made-easy' ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_help_button() ?></p>
<?php
    eme_help(
        'eme-help-seatlayout-intro',
        __( 'Seat layout', 'events-made-easy' ),
        [
            __( 'Define a seat layout for this event and add #_SEATMAP to the booking form (it replaces #_SEATS).', 'events-made-easy' ),
            __( 'Seats that are part of a booking can never be removed.', 'events-made-easy' ),
        ],
        false
    );
?>
    <p>
        <label><?php esc_html_e( 'Rows', 'events-made-easy' ); ?> <input type="number" class="eme-seatlayout-rows" min="0" max="60" value="<?php echo esc_attr( $payload['rows'] ); ?>"></label>
        <label><?php esc_html_e( 'Columns', 'events-made-easy' ); ?> <input type="number" class="eme-seatlayout-cols" min="0" max="60" value="<?php echo esc_attr( $payload['cols'] ); ?>"></label>
    </p>
    <p class="eme-seatlayout-tools">
<?php foreach ( $categories as $index => $category_label ) : ?>
        <button type="button" class="button eme-seatlayout-tool" data-tool="price:<?php echo esc_attr( intval( $index ) ); ?>"><span class="eme-seat-swatch eme-seat-price-<?php echo esc_attr( intval( $index ) % 10 ); ?>"></span> <?php echo esc_html( $category_label ); ?></button>
<?php endforeach; ?>
        <button type="button" class="button eme-seatlayout-tool" data-tool="disable"><?php esc_html_e( 'Toggle disabled', 'events-made-easy' ); ?></button>
        <button type="button" class="button eme-seatlayout-tool" data-tool="rename"><?php esc_html_e( 'Rename', 'events-made-easy' ); ?></button>
        <button type="button" class="button eme-seatlayout-tool" data-tool="remove"><?php esc_html_e( 'Remove', 'events-made-easy' ); ?></button>
    </p>
    <p class="eme_smaller"><?php esc_html_e( 'Pick a tool, then click or drag over the grid. A seat that is disabled cannot be booked.', 'events-made-easy' ); ?></p>
    <div class="eme-seatlayout-grid"></div>
    <p class="eme_smaller"><?php esc_html_e( 'Number of seats that can be booked:', 'events-made-easy' ); ?> <strong class="eme-seatlayout-count">0</strong></p>
    <p class="eme-seatlayout-warning eme-message-error" style="display:none"><?php esc_html_e( 'Warning: the number of seats in the layout differs from the number of seats set for this event in the RSVP settings.', 'events-made-easy' ); ?></p>
</div>
<?php
}

/* ------------------------------------------------------------------ */
/* frontend/backend booking form: the seatmap itself                   */
/* ------------------------------------------------------------------ */

function eme_seatlayout_render_frontend( $event, $booking, $editing_booking_from_backend, $input_class ) {
    $event_id = intval( $event['event_id'] );
    $layout   = eme_seatlayout_get_layout( $event_id );
    if ( empty( $layout ) ) {
        return '';
    }
    $exclude_booking_id = ( $editing_booking_from_backend && ! empty( $booking['booking_id'] ) ) ? intval( $booking['booking_id'] ) : 0;
    $selected           = eme_seatlayout_get_posted_seat_ids( $event_id );
    if ( empty( $selected ) && $exclude_booking_id ) {
        $selected = eme_seatlayout_get_seat_ids_for_booking( $exclude_booking_id );
    }
    $selected   = array_flip( $selected );
    $taken      = array_flip( eme_seatlayout_get_taken_seat_ids( $event_id, $exclude_booking_id ) );
    $categories = eme_seatlayout_price_categories( $event );
    $by_pos     = [];
    foreach ( eme_seatlayout_get_seats( $layout['layout_id'] ) as $seat ) {
        $by_pos[ intval( $seat['seat_row'] ) . ':' . intval( $seat['seat_col'] ) ] = $seat;
    }
    $rows = max( 1, intval( $layout['layout_rows'] ) );
    $cols = max( 1, intval( $layout['layout_cols'] ) );
    $max  = $event['event_properties']['max_allowed'] ?? 0;
    $max  = eme_is_multi( $max ) ? eme_get_total( $max ) : intval( $max );

    $grid = '';
    $found_taken = false;
    $found_unavail = false;
    for ( $row = 1; $row <= $rows; $row++ ) {
        for ( $col = 1; $col <= $cols; $col++ ) {
            $seat = $by_pos[ $row . ':' . $col ] ?? null;
            if ( empty( $seat ) ) {
                $grid .= "<span class='eme-seat eme-seat-empty'></span>";
                continue;
            }
            $seat_id = intval( $seat['seat_id'] );
            $label   = $seat['seat_label'] !== '' ? $seat['seat_label'] : eme_seatlayout_default_label( $row, $col );
            $price   = 'eme-seat-price-' . ( intval( $seat['price_index'] ) % 10 );
            if ( $seat['disabled'] && ! isset( $selected[ $seat_id ] ) ) {
                $grid .= "<span class='eme-seat eme-seat-unavailable' title='" . esc_attr__( 'Unavailable', 'events-made-easy' ) . "'>" . esc_html( $label ) . '</span>';
                $found_unavail = true;
            } elseif ( isset( $taken[ $seat_id ] ) ) { // only in the frontend
                $grid .= "<span class='eme-seat eme-seat-taken $price' title='" . esc_attr__( 'Taken', 'events-made-easy' ) . "'>" . esc_html( $label ) . '</span>';
                $found_taken = true;
            } else {
                $checked = isset( $selected[ $seat_id ] ) ? " checked='checked'" : '';
                $grid   .= "<label class='eme-seat eme-seat-free $price'>";
                $grid   .= "<input type='checkbox' class='eme-seat-checkbox $input_class' name='bookings[$event_id][seat_ids][]' value='$seat_id'$checked>";
                $grid   .= '<span>' . esc_html( $label ) . '</span></label>';
            }
        }
    }

    $legend = "<div class='eme-seatmap-legend'>";
    if ( count( $categories ) > 1 ) {
        foreach ( $categories as $index => $category_label ) {
            $legend .= "<span><span class='eme-seat eme-seat-swatch eme-seat-price-" . ( intval( $index ) % 10 ) . "'></span>" . esc_html( $category_label ) . '</span>';
        }
    }
    if ($found_taken) {
        $legend .= "<span><span class='eme-seat eme-seat-swatch eme-seat-taken'></span>" . esc_html__( 'Taken', 'events-made-easy' ) . '</span>';
    }
    if ($found_unavail) {
        $legend .= "<span><span class='eme-seat eme-seat-swatch eme-seat-unavailable'></span>" . esc_html__( 'Unavailable', 'events-made-easy' ) . '</span>';
    }
    $legend .= '</div>';

    return "<div class='eme-seatmap' data-max-seats='" . esc_attr( $max ) . "'><div class='eme-seatmap-grid' style='grid-template-columns:repeat($cols,minmax(2.6em,1fr))'>" . $grid . '</div>' . $legend . '</div>';
}
