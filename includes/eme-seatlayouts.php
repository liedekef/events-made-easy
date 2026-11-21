<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_seatlayout_get_layout( $event_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE event_id = %d", $event_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a safe variable
	return $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
}

function eme_seatlayout_get_seats( $layout_id, $only_active = 0 ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_SEATS_TBNAME;
	$where = $wpdb->prepare( 'layout_id = %d', $layout_id );
	if ( $only_active ) {
		$where .= ' AND disabled = 0';
	}
	$sql = "SELECT * FROM $table WHERE $where ORDER BY seat_row ASC, seat_col ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a safe variable
	return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
}

function eme_seatlayout_get_seats_by_ids( $event_id, $seat_ids ) {
	global $wpdb;
	if ( empty( $seat_ids ) ) {
		return [];
	}
	$seat_ids    = array_values( array_unique( array_map( 'intval', $seat_ids ) ) );
	$placeholders = implode( ',', array_fill( 0, count( $seat_ids ), '%d' ) );
	$seats_table  = EME_DB_PREFIX . EME_SEATS_TBNAME;
	$layouts_table = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
	$sql = $wpdb->prepare( "SELECT seats.* FROM $seats_table AS seats LEFT JOIN $layouts_table AS layouts ON seats.layout_id = layouts.layout_id WHERE layouts.event_id = %d AND seats.seat_id IN ($placeholders)", ...array_merge( [ $event_id ], $seat_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names and placeholders are safe
	return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
}

function eme_seatlayout_get_booked_seat_ids( $event_id ) {
	global $wpdb;
	$booking_seats_table = EME_DB_PREFIX . EME_BOOKING_SEATS_TBNAME;
	$bookings_table      = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$sql                 = $wpdb->prepare( "SELECT booking_seats.seat_id FROM $booking_seats_table AS booking_seats LEFT JOIN $bookings_table AS bookings ON booking_seats.booking_id = bookings.booking_id WHERE booking_seats.event_id = %d AND bookings.status IN (%d,%d,%d)", $event_id, EME_RSVP_STATUS_PENDING, EME_RSVP_STATUS_USERPENDING, EME_RSVP_STATUS_APPROVED ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are safe variables
	$seat_ids            = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
	return array_map( 'intval', $seat_ids );
}

function eme_seatlayout_get_booking_labels( $booking_id ) {
	global $wpdb;
	$booking_seats_table = EME_DB_PREFIX . EME_BOOKING_SEATS_TBNAME;
	$seats_table         = EME_DB_PREFIX . EME_SEATS_TBNAME;
	$sql                 = $wpdb->prepare( "SELECT seats.seat_label FROM $booking_seats_table AS booking_seats LEFT JOIN $seats_table AS seats ON booking_seats.seat_id = seats.seat_id WHERE booking_seats.booking_id = %d ORDER BY seats.seat_row ASC, seats.seat_col ASC", $booking_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are safe variables
	return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
}

function eme_seatlayout_are_available( $event_id, $seat_ids ) {
	$seat_ids = array_values( array_unique( array_map( 'intval', $seat_ids ) ) );
	if ( empty( $seat_ids ) ) {
		return true;
	}
	$seats = eme_seatlayout_get_seats_by_ids( $event_id, $seat_ids );
	if ( count( $seats ) != count( $seat_ids ) ) {
		return false;
	}
	foreach ( $seats as $seat ) {
		if ( $seat['disabled'] ) {
			return false;
		}
	}
	$booked_ids = eme_seatlayout_get_booked_seat_ids( $event_id );
	return empty( array_intersect( $seat_ids, $booked_ids ) );
}

function eme_seatlayout_book_seats( $booking_id, $event_id, $seat_ids ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_BOOKING_SEATS_TBNAME;
	foreach ( array_values( array_unique( array_map( 'intval', $seat_ids ) ) ) as $seat_id ) {
		$res = $wpdb->insert( $table, [ 'booking_id' => intval( $booking_id ), 'event_id' => intval( $event_id ), 'seat_id' => $seat_id ], [ '%d', '%d', '%d' ] );
		if ( ! $res ) {
			eme_seatlayout_release_booking_seats( $booking_id );
			return false;
		}
	}
	return true;
}

function eme_seatlayout_release_booking_seats( $booking_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_BOOKING_SEATS_TBNAME;
	return $wpdb->delete( $table, [ 'booking_id' => intval( $booking_id ) ], [ '%d' ] );
}

function eme_seatlayout_delete_event( $event_id ) {
	global $wpdb;
	$booking_seats_table = EME_DB_PREFIX . EME_BOOKING_SEATS_TBNAME;
	$seats_table         = EME_DB_PREFIX . EME_SEATS_TBNAME;
	$layouts_table       = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
	$layout              = eme_seatlayout_get_layout( $event_id );
	if ( empty( $layout ) ) {
		return;
	}
	$wpdb->delete( $booking_seats_table, [ 'event_id' => intval( $event_id ) ], [ '%d' ] );
	$wpdb->delete( $seats_table, [ 'layout_id' => intval( $layout['layout_id'] ) ], [ '%d' ] );
	$wpdb->delete( $layouts_table, [ 'layout_id' => intval( $layout['layout_id'] ) ], [ '%d' ] );
}

function eme_seatlayout_seat_letter( $row ) {
	$letter = '';
	$row    = intval( $row ) + 1;
	while ( $row > 0 ) {
		--$row;
		$letter = chr( 65 + ( $row % 26 ) ) . $letter;
		$row    = intval( $row / 26 );
	}
	return $letter;
}

function eme_seatlayout_price_categories( $event ) {
	$prices = eme_convert_multi2array( $event['price'] );
	$descriptions = eme_convert_multi2array( $event['event_properties']['multiprice_desc'] ?? '' );
	$categories   = [];
	foreach ( $prices as $key => $price ) {
		$label = isset( $descriptions[ $key ] ) ? trim( $descriptions[ $key ] ) : '';
		if ( $label === '' ) {
			// translators: %s is the price of a category
			$label = sprintf( __( 'Price %s', 'events-made-easy' ), eme_localized_price( $price, $event['currency'] ) );
		}
		$categories[ $key ] = $label;
	}
	if ( empty( $categories ) ) {
		$categories[0] = __( 'Standard', 'events-made-easy' );
	}
	return $categories;
}

function eme_seatlayout_form_uses_seatmap( $event ) {
	if ( ! empty( $event['event_registration_form_format'] ) ) {
		$format = $event['event_registration_form_format'];
	} elseif ( ! empty( $event['event_properties']['event_registration_form_format_tpl'] ) ) {
		$format = eme_get_template_format( $event['event_properties']['event_registration_form_format_tpl'] );
	} else {
		$format = get_option( 'eme_registration_form_format' );
	}
	return strstr( (string) $format, '#_SEATMAP' ) !== false;
}

function eme_seatlayout_get_submitted_seat_ids( $event_id ) {
	if ( ! isset( $_POST['bookings'][ $event_id ]['seats'] ) ) {
		return [];
	}
	$value = $_POST['bookings'][ $event_id ]['seats']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below
	if ( ! is_array( $value ) ) {
		$value = [ $value ];
	}
	$seat_ids = [];
	foreach ( $value as $seat_id ) {
		$seat_id = eme_sanitize_request( $seat_id );
		if ( is_array( $seat_id ) ) {
			continue;
		}
		$seat_id = intval( $seat_id );
		if ( $seat_id > 0 ) {
			$seat_ids[] = $seat_id;
		}
	}
	return array_values( array_unique( $seat_ids ) );
}

// When seats are selected, they define both the number of seats and the price category counts.
function eme_seatlayout_apply_to_booking( $event, &$booking ) {
	$layout = eme_seatlayout_get_layout( $event['event_id'] );
	if ( empty( $layout ) ) {
		return false;
	}
	$seat_ids = eme_seatlayout_get_submitted_seat_ids( $event['event_id'] );
	if ( empty( $seat_ids ) ) {
		return false;
	}
	if ( ! eme_seatlayout_are_available( $event['event_id'], $seat_ids ) ) {
		$booking['booking_seats']    = 0;
		$booking['booking_seats_mp'] = '';
		$booking['eme_seat_ids']     = [];
		return false;
	}
	$seats        = eme_seatlayout_get_seats_by_ids( $event['event_id'], $seat_ids );
	$price_count  = count( eme_seatlayout_price_categories( $event ) );
	$seat_counts  = array_fill( 0, $price_count, 0 );
	foreach ( $seats as $seat ) {
		$index = min( max( intval( $seat['price_index'] ), 0 ), $price_count - 1 );
		++$seat_counts[ $index ];
	}
	$booking['booking_seats']    = count( $seats );
	$booking['booking_seats_mp'] = $price_count > 1 ? eme_convert_array2multi( $seat_counts ) : '';
	$booking['eme_seat_ids']     = $seat_ids;
	return true;
}

function eme_seatlayout_normalize_posted_cell( $cell, $row, $col, $price_count ) {
	if ( ! is_array( $cell ) ) {
		return [];
	}
	$enabled = $cell['enabled'] ?? $cell['e'] ?? 0;
	$label   = $cell['label'] ?? $cell['l'] ?? '';
	if ( is_array( $label ) ) {
		$label = '';
	}
	$label = trim( (string) $label );
	if ( $label === '' ) {
		$label = eme_seatlayout_seat_letter( $row - 1 ) . $col;
	}
	$label       = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 20 ) : substr( $label, 0, 20 );
	$price_index = $cell['price_index'] ?? $cell['p'] ?? 0;
	return [
		'enabled'     => ! empty( $enabled ) ? 1 : 0,
		'label'       => $label,
		'price_index' => min( max( intval( $price_index ), 0 ), $price_count - 1 ),
	];
}

function eme_seatlayout_decode_posted_data( $event ) {
	$price_count = count( eme_seatlayout_price_categories( $event ) );
	$result      = [ 'name' => '', 'rows' => 0, 'cols' => 0, 'seats' => [] ];

	if ( isset( $_POST['eme_seatlayout_data'] ) ) {
		$raw     = eme_sanitize_request( $_POST['eme_seatlayout_data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized here
		$decoded = eme_json_decode_safe( $raw );
		if ( is_array( $decoded ) ) {
			$result['name'] = isset( $decoded['name'] ) && ! is_array( $decoded['name'] ) ? trim( (string) $decoded['name'] ) : '';
			$result['rows'] = min( 60, max( 0, intval( $decoded['rows'] ?? 0 ) ) );
			$result['cols'] = min( 60, max( 0, intval( $decoded['cols'] ?? 0 ) ) );
			$seats          = $decoded['seats'] ?? [];
			if ( is_array( $seats ) ) {
				foreach ( $seats as $key => $cell ) {
					if ( ! preg_match( '/^(\d+):(\d+)$/', (string) $key, $matches ) ) {
						continue;
					}
					$row = intval( $matches[1] );
					$col = intval( $matches[2] );
					if ( $row < 1 || $row > $result['rows'] || $col < 1 || $col > $result['cols'] ) {
						continue;
					}
					$normalized = eme_seatlayout_normalize_posted_cell( $cell, $row, $col, $price_count );
					if ( ! empty( $normalized ) ) {
						$result['seats'][ $row ][ $col ] = $normalized;
					}
				}
			}
			return $result;
		}
	}

	if ( isset( $_POST['eme_seatlayout'] ) && is_array( $_POST['eme_seatlayout'] ) ) {
		$posted         = eme_sanitize_request( $_POST['eme_seatlayout'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized here
		$result['name'] = isset( $posted['name'] ) && ! is_array( $posted['name'] ) ? trim( (string) $posted['name'] ) : '';
		$result['rows'] = min( 60, max( 0, intval( $posted['rows'] ?? 0 ) ) );
		$result['cols'] = min( 60, max( 0, intval( $posted['cols'] ?? 0 ) ) );
		$seats          = $posted['seats'] ?? [];
		if ( is_array( $seats ) ) {
			foreach ( $seats as $row => $row_cells ) {
				if ( ! is_array( $row_cells ) || ! ctype_digit( (string) $row ) ) {
					continue;
				}
				$row = intval( $row );
				if ( $row < 1 || $row > $result['rows'] ) {
					continue;
				}
				foreach ( $row_cells as $col => $cell ) {
					if ( ! ctype_digit( (string) $col ) ) {
						continue;
					}
					$col = intval( $col );
					if ( $col < 1 || $col > $result['cols'] ) {
						continue;
					}
					$normalized = eme_seatlayout_normalize_posted_cell( $cell, $row, $col, $price_count );
					if ( ! empty( $normalized ) ) {
						$result['seats'][ $row ][ $col ] = $normalized;
					}
				}
			}
		}
		return $result;
	}

	return [];
}

function eme_seatlayout_save_from_post( $event_id ) {
	$event = eme_get_event( $event_id );
	if ( empty( $event ) ) {
		return;
	}
	$data = eme_seatlayout_decode_posted_data( $event );
	if ( empty( $data ) ) {
		return;
	}
	global $wpdb;
	$layout_table = EME_DB_PREFIX . EME_SEATLAYOUTS_TBNAME;
	$seats_table  = EME_DB_PREFIX . EME_SEATS_TBNAME;
	$layout       = eme_seatlayout_get_layout( $event_id );
	$rows         = $data['rows'];
	$cols         = $data['cols'];

	if ( ! $rows || ! $cols ) {
		if ( $layout ) {
			eme_seatlayout_delete_event( $event_id );
		}
		return;
	}

	$price_count  = count( eme_seatlayout_price_categories( $event ) );
	$layout_name  = $data['name'];
	$layout_id    = 0;
	if ( $layout ) {
		$layout_id = intval( $layout['layout_id'] );
		$wpdb->update( $layout_table, [ 'layout_name' => $layout_name, 'layout_rows' => $rows, 'layout_cols' => $cols ], [ 'layout_id' => $layout_id ], [ '%s', '%d', '%d' ], [ '%d' ] );
	} else {
		$wpdb->insert( $layout_table, [ 'event_id' => intval( $event_id ), 'layout_name' => $layout_name, 'layout_rows' => $rows, 'layout_cols' => $cols ], [ '%d', '%s', '%d', '%d' ] );
		$layout_id = $wpdb->insert_id;
	}
	if ( ! $layout_id ) {
		return;
	}

	$booked_ids = eme_seatlayout_get_booked_seat_ids( $event_id );
	$keep_ids   = [];
	for ( $row = 1; $row <= $rows; ++$row ) {
		for ( $col = 1; $col <= $cols; ++$col ) {
			$cell         = $data['seats'][ $row ][ $col ] ?? [];
			$enabled      = empty( $cell ) ? 1 : ( ! empty( $cell['enabled'] ) ? 1 : 0 );
			$price_index  = min( max( intval( $cell['price_index'] ?? 0 ), 0 ), $price_count - 1 );
			$label        = isset( $cell['label'] ) && ! is_array( $cell['label'] ) ? trim( (string) $cell['label'] ) : '';
			if ( $label === '' ) {
				$label = eme_seatlayout_seat_letter( $row - 1 ) . $col;
			}
			$label        = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 20 ) : substr( $label, 0, 20 );
			$sql          = $wpdb->prepare( "SELECT seat_id FROM $seats_table WHERE layout_id = %d AND seat_row = %d AND seat_col = %d", $layout_id, $row, $col ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a safe variable
			$seat_id    = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
			if ( $seat_id ) {
				$seat_id = intval( $seat_id );
				$wpdb->update( $seats_table, [ 'seat_label' => $label, 'price_index' => $price_index, 'disabled' => 1 - $enabled ], [ 'seat_id' => $seat_id ], [ '%s', '%d', '%d' ], [ '%d' ] );
				if ( $enabled ) {
					$keep_ids[] = $seat_id;
				}
			} elseif ( $enabled ) {
				$wpdb->insert( $seats_table, [ 'layout_id' => $layout_id, 'seat_row' => $row, 'seat_col' => $col, 'seat_label' => $label, 'price_index' => $price_index, 'disabled' => 0 ], [ '%d', '%d', '%d', '%s', '%d', '%d' ] );
				$keep_ids[] = $wpdb->insert_id;
			}
		}
	}

	// Remove seats that are no longer part of the layout, but never touch booked seats.
	$sql        = $wpdb->prepare( "SELECT seat_id FROM $seats_table WHERE layout_id = %d", $layout_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a safe variable
	$all_ids    = array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
	$remove_ids = array_diff( $all_ids, $keep_ids, $booked_ids );
	if ( ! empty( $remove_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $remove_ids ), '%d' ) );
		$sql          = $wpdb->prepare( "DELETE FROM $seats_table WHERE seat_id IN ($placeholders)", ...$remove_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and placeholders are safe
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
	}
	// Booked seats outside the new grid remain, but can no longer be selected.
	$disabled_ids = array_diff( $booked_ids, $keep_ids );
	if ( ! empty( $disabled_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $disabled_ids ), '%d' ) );
		$sql          = $wpdb->prepare( "UPDATE $seats_table SET disabled = 1 WHERE seat_id IN ($placeholders)", ...$disabled_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and placeholders are safe
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared
	}
}

function eme_seatlayout_render_admin( $event ) {
    if (empty($event['event_id'])) {
        $layout = [];
    } else {
        $layout       = eme_seatlayout_get_layout( $event['event_id'] );
    }
	$categories   = eme_seatlayout_price_categories( $event );
	$rows         = $layout ? intval( $layout['layout_rows'] ) : 0;
	$cols         = $layout ? intval( $layout['layout_cols'] ) : 0;
	$seats        = $layout ? eme_seatlayout_get_seats( $layout['layout_id'] ) : [];
	$seats_by_pos = [];
	foreach ( $seats as $seat ) {
		$seats_by_pos[ $seat['seat_row'] . ':' . $seat['seat_col'] ] = $seat;
	}
	$category_options = [];
	foreach ( $categories as $index => $label ) {
		$category_options[] = [ 'value' => $index, 'label' => $label ];
	}
	$data = [
		'rows'    => $rows,
		'cols'    => $cols,
		'layout'  => [ 'name' => $layout['layout_name'] ?? '' ],
		'seats'   => [],
	];
	foreach ( $seats as $seat ) {
		$data['seats'][ $seat['seat_row'] ][ $seat['seat_col'] ] = [ 'enabled' => ! $seat['disabled'], 'price_index' => intval( $seat['price_index'] ), 'label' => $seat['seat_label'] ];
	}
	$payload_seats = [];
	foreach ( $data['seats'] as $row => $row_seats ) {
		foreach ( $row_seats as $col => $cell ) {
			$payload_seats[ $row . ':' . $col ] = [ 'e' => $cell['enabled'] ? 1 : 0, 'l' => $cell['label'], 'p' => $cell['price_index'] ];
		}
	}
	$payload = [ 'name' => $data['layout']['name'], 'rows' => $rows, 'cols' => $cols, 'seats' => $payload_seats ];
?>
	<div class='eme-seatlayout-admin'>
		<p><?php esc_html_e( 'A seat layout uses the price categories defined in the RSVP settings. If you change the prices or price categories, update the layout as well.', 'events-made-easy' ); ?></p>
		<table class='form-table'>
			<tr>
				<th scope='row'><label for='eme_seatlayout_name'><?php esc_html_e( 'Layout name', 'events-made-easy' ); ?></label></th>
				<td><input type='text' id='eme_seatlayout_name' value='<?php echo esc_attr( $data['layout']['name'] ); ?>'></td>
			</tr>
			<tr>
				<th scope='row'><label for='eme_seatlayout_rows'><?php esc_html_e( 'Rows', 'events-made-easy' ); ?></label></th>
				<td><input type='number' min='0' max='60' id='eme_seatlayout_rows' value='<?php echo esc_attr( $rows ); ?>'></td>
			</tr>
			<tr>
				<th scope='row'><label for='eme_seatlayout_cols'><?php esc_html_e( 'Seats per row', 'events-made-easy' ); ?></label></th>
				<td><input type='number' min='0' max='60' id='eme_seatlayout_cols' value='<?php echo esc_attr( $cols ); ?>'>
				<button type='button' class='button' id='eme_seatlayout_generate'><?php esc_html_e( 'Generate grid', 'events-made-easy' ); ?></button></td>
			</tr>
		</table>
		<input type='hidden' id='eme_seatlayout_data' name='eme_seatlayout_data' value='<?php echo esc_attr( eme_json_encode_safe( $payload ) ); ?>'>
		<div id='eme-seatlayout-grid' class='eme-seatlayout-grid-scroll' data-categories='<?php echo esc_attr( wp_json_encode( $category_options ) ); ?>'>
<?php
	if ( $rows && $cols ) {
		echo "<table class='eme-seatlayout-grid-table'><colgroup><col class='eme-seatlayout-rowlabel-col'>";
		for ( $col = 1; $col <= $cols; ++$col ) {
			echo "<col class='eme-seatlayout-seat-col'>";
		}
		echo '</colgroup><thead><tr><th></th>';
		for ( $col = 1; $col <= $cols; ++$col ) {
			echo '<th scope="col">' . esc_html( $col ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		for ( $row = 1; $row <= $rows; ++$row ) {
			echo "<tr><th scope='row' class='eme-seatlayout-rowlabel'>" . esc_html( eme_seatlayout_seat_letter( $row - 1 ) ) . '</th>';
			for ( $col = 1; $col <= $cols; ++$col ) {
				$seat  = $seats_by_pos[ $row . ':' . $col ] ?? [];
				$label = $seat['seat_label'] ?? eme_seatlayout_seat_letter( $row - 1 ) . $col;
				echo "<td class='eme-seatlayout-cell'>";
				echo '<label><input type="checkbox" class="eme-seatlayout-enabled" value="1" ' . checked( empty( $seat ) ? 1 : ! $seat['disabled'], 1, false ) . '>' . esc_html( $label ) . '</label>';
				echo '<input type="text" class="eme-seatlayout-label" value="' . esc_attr( $label ) . '" maxlength="20">';
				echo '<select class="eme-seatlayout-price">';
				foreach ( $categories as $index => $category_label ) {
					echo "<option value='" . esc_attr( $index ) . "' " . selected( ( $seat['price_index'] ?? 0 ), $index, false ) . '>' . esc_html( $category_label ) . '</option>';
				}
				echo '</select></td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>' . esc_html__( 'Enter the number of rows and seats per row, then click "Generate grid".', 'events-made-easy' ) . '</p>';
	}
?>
		</div>
		<p class='eme_smaller'><?php esc_html_e( 'Uncheck seats you do not want to sell (e.g. aisles). Booked seats cannot be removed; they are only disabled for new bookings.', 'events-made-easy' ); ?></p>
	</div>
<?php
}

function eme_seatlayout_render_frontend( $event, $is_multibooking = 0 ) {
	$layout = eme_seatlayout_get_layout( $event['event_id'] );
	if ( empty( $layout ) || $is_multibooking ) {
		return '';
	}
	$seats      = eme_seatlayout_get_seats( $layout['layout_id'] );
	$booked_ids = eme_seatlayout_get_booked_seat_ids( $event['event_id'] );
	$categories = eme_seatlayout_price_categories( $event );
	$max        = $event['event_properties']['max_allowed'] ?? '';
	$max        = eme_is_multi( $max ) ? array_sum( array_map( 'intval', eme_convert_multi2array( $max ) ) ) : intval( $max );
	$rows       = intval( $layout['layout_rows'] );
	$cols       = intval( $layout['layout_cols'] );

	$seats_by_pos = [];
	foreach ( $seats as $seat ) {
		$seats_by_pos[ intval( $seat['seat_row'] ) . ':' . intval( $seat['seat_col'] ) ] = $seat;
	}
	$html = "<div class='eme-seatmap' data-max-seats='" . esc_attr( $max ) . "'>";
	$html .= "<div class='eme-seatmap-legend'>";
	foreach ( $categories as $index => $label ) {
		$html .= "<span class='eme-seatmap-legend-item eme-seatmap-price-" . esc_attr( $index ) . "'>" . esc_html( $label ) . '</span>';
	}
	$html .= "<span class='eme-seatmap-legend-item eme-seatmap-legend-free'>" . esc_html__( 'Free', 'events-made-easy' ) . '</span>';
	$html .= "<span class='eme-seatmap-legend-item eme-seatmap-legend-taken'>" . esc_html__( 'Taken', 'events-made-easy' ) . '</span>';
	$html .= "<span class='eme-seatmap-legend-item eme-seatmap-legend-aisle'>" . esc_html__( 'Unavailable', 'events-made-easy' ) . '</span>';
	$html .= '</div><table class="eme-seatmap-table"><tbody>';
	for ( $row = 1; $row <= $rows; ++$row ) {
		$html .= "<tr><th class='eme-seatmap-rowlabel'>" . esc_html( eme_seatlayout_seat_letter( $row - 1 ) ) . '</th>';
		for ( $col = 1; $col <= $cols; ++$col ) {
			$seat    = $seats_by_pos[ $row . ':' . $col ] ?? null;
			$taken   = $seat && in_array( intval( $seat['seat_id'] ), $booked_ids );
			$bookable = $seat && ! $seat['disabled'] && ! $taken;
			$label   = $seat ? $seat['seat_label'] : eme_seatlayout_seat_letter( $row - 1 ) . $col;
			$classes = $bookable || $taken ? 'eme-seatmap-seat eme-seatmap-price-' . intval( $seat['price_index'] ) : 'eme-seatmap-seat eme-seatmap-seat-aisle';
			if ( $taken ) {
				$classes .= ' eme-seatmap-seat-taken';
			}
			$html .= "<td class='" . esc_attr( $classes ) . "'>";
			if ( $bookable || $taken ) {
				$html .= "<label><input type='checkbox' class='dynamicprice eme-seatmap-input' name='bookings[" . esc_attr( $event['event_id'] ) . "][seats][]' value='" . esc_attr( $seat['seat_id'] ) . "' data-label='" . esc_attr( $label ) . "' data-taken='" . ( $taken ? 1 : 0 ) . "' " . disabled( $taken, true, false ) . '>' . esc_html( $label ) . '</label>';
			} else {
				$html .= '<span class="eme-seatmap-seat-label">' . esc_html( $label ) . '</span>';
			}
			$html .= '</td>';
		}
		$html .= '</tr>';
	}
	$html .= '</tbody></table><div class="eme-seatmap-selection"></div></div>';
	return $html;
}
