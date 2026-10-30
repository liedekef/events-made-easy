<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_holidays() {
	$hol = [
		'name' => '',
		'list' => '',
	];
	return $hol;
}

function eme_holidays_page() {
	global $wpdb;

	if ( ! current_user_can( get_option( 'eme_cap_holidays' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
		$message = __( 'You have no right to update holidays!', 'events-made-easy' );
		eme_holidays_table_layout( $message );
		return;
	}

	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_holidays' ) {
		// edit holidays
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_holidays_edit_layout();
		return;
	}

	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_holidays' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_holidays_edit_layout();
		return;
	}

	// Insert/Update/Delete Record
	$holidays_table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
	$message        = '';
	if ( isset( $_POST['eme_admin_action'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		if ( $_POST['eme_admin_action'] == 'do_editholidays' ) {
			// holidays update required
			$holidays         = [];
			$holidays['name'] = eme_sanitize_request( $_POST['name'] );
			$holidays['list'] = eme_sanitize_request( $_POST['list'] );
			if ( ! empty( $_POST['id'] ) ) {
				$holidays_id = intval( $_POST['id'] );
				// events with bookings/task signups that would end up on a holiday: ask what to do with the bookings first
				$booked = eme_get_holiday_booked_events( $holidays_id, eme_parse_holiday_list( $holidays['list'] ) );
				if ( ! empty( $booked ) && ! isset( $_POST['send_trashmails'] ) ) {
					eme_holidays_confirm_layout( $holidays, $holidays_id, $booked );
					return;
				}
				$validation_result = $wpdb->update( $holidays_table, $holidays, [ 'id' => $holidays_id ] );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully edited the list of holidays', 'events-made-easy' );
					$changes = eme_holidays_update_recurrences( $holidays_id, intval( $_POST['send_trashmails'] ?? 0 ) );
					if ( $changes['removed'] ) {
						$message .= '<br>' . sprintf( __( '%d event(s) removed from recurrences', 'events-made-easy' ), $changes['removed'] );
					}
					if ( $changes['added'] ) {
						$message .= '<br>' . sprintf( __( '%d event(s) added to recurrences', 'events-made-easy' ), $changes['added'] );
					}
				} else {
					$message = __( 'There was a problem editing the list of holidays, please try again.', 'events-made-easy' );
				}
			} else {
                $validation_result = $wpdb->insert( $holidays_table, $holidays );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully added the list of holidays', 'events-made-easy' );
				} else {
					$message = __( 'There was a problem adding the list of holidays, please try again.', 'events-made-easy' );
				}
			}
		}
	}
	eme_holidays_table_layout( $message );
}

function eme_holidays_table_layout( $message = '' ) {
?>
    <div class="wrap nosubsub">
    <div id="poststuff">

    <?php if ( current_user_can( get_option( 'eme_cap_holidays' ) ) ) : ?>
        <h1><?php esc_html_e( 'Add a new list of holidays', 'events-made-easy' ); ?></h1>
        <div class="wrap">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=eme-holidays' ) ); ?>">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
            <input type="hidden" name="eme_admin_action" value="add_holidays">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add holidays list', 'events-made-easy' ); ?>">
        </form>
        </div>
    <?php endif; ?>

    <h1><?php esc_html_e( 'Manage list of holidays', 'events-made-easy' ); ?></h1>
    <?php if ( $message != '' ) { ?>
    <div id="message" class="updated notice notice-success is-dismissible">
         <p><?php echo wp_kses_post( $message ); ?></p>
    </div>
    <?php } ?>

    <div class="bulkactions">
    <form action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
    <select id="eme_admin_action_holidays" name="eme_admin_action_holidays">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteHolidays"><?php esc_html_e( 'Delete selected lists of holidays', 'events-made-easy' ); ?></option>
    </select>
    <button id="HolidaysActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="HolidaysTableContainer"></div>
    </div>
    </div>
<?php
}

function eme_holidays_edit_layout() {
	global $plugin_page;

	if ( isset( $_GET['id'] ) ) {
			$holidays_id   = intval( $_GET['id'] );
			$holidays      = eme_get_holiday_list( $holidays_id );
			$h1_string     = esc_html__( 'Edit holidays list', 'events-made-easy' );
			$action_string = esc_attr__( 'Update list of holidays', 'events-made-easy' );
	} else {
			$holidays_id   = 0;
			$holidays      = eme_new_holidays();
			$h1_string     = esc_html__( 'Create holidays list', 'events-made-easy' );
			$action_string = esc_attr__( 'Add list of holidays', 'events-made-easy' );
	}

	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    // we show the list ordered by date, array_filter already removes the empty lines
    $list_lines = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "\n", $holidays['list'] ) ) ) );
    sort( $list_lines, SORT_STRING );
    $holidays['list'] = implode( "\n", $list_lines );

	$layout      = "
   <div class='wrap'>
      <h1>" . $h1_string . '</h1>';

    $layout .= "
      <form name='edit_holidays' id='edit_holidays' method='post' action='" . esc_url( admin_url( "admin.php?page=$plugin_page" ) ) . "'>
      <input type='hidden' name='eme_admin_action' value='do_editholidays'>
      <input type='hidden' name='id' value='" . $holidays_id . "'>
      $nonce_field
      <table class='form-table'>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='name'>" . __( 'Holidays listname', 'events-made-easy' ) . "</label></th>
               <td><input name='name' id='name' required='required' type='text' value='" . esc_html( $holidays['name'] ) . "' size='40'><br>
                 " . __( 'The name of the holidays list', 'events-made-easy' ) . "</td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='description'>" . __( 'Holidays list', 'events-made-easy' ) . "</label></th>
               <td><textarea name='list' id='description' rows='5' >" . esc_html( $holidays['list'] ) . '</textarea><br>
                 ' . __( 'Basic format: YYYY-MM-DD, one per line', 'events-made-easy' ) . '<br>' . esc_html__( 'For more information about holidays, see ', 'events-made-easy' ) . " <a target='_blank' rel='noopener noreferrer' href='https://www.e-dynamics.be/wordpress/eme-docs/holidays/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . "</a></td>
            </tr>
         </table>
      <p class='submit'><input type='submit' class='button-primary' name='submit' value='" . $action_string . "'></p>
      </form>
      " . eme_holidays_recurrences_html( $holidays_id ) . "
   </div>
   ";
	echo $layout; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// list of recurrences using this holidays list, shown in the edit screen
function eme_holidays_recurrences_html( $holidays_id ) {
	if ( empty( $holidays_id ) ) {
		return '';
	}
	$html = '';
	foreach ( eme_get_recurrences_for_holidays_id( $holidays_id ) as $recurrence ) {
		$event = eme_get_event( eme_get_recurrence_first_eventid( $recurrence['recurrence_id'] ) );
		if ( empty( $event ) ) {
			continue;
		}
		$url   = wp_nonce_url( admin_url( 'admin.php?page=eme-manager&eme_admin_action=edit_recurrence&recurrence_id=' . $recurrence['recurrence_id'] ), 'eme_admin', 'eme_admin_nonce' );
		$html .= "<li><a href='" . esc_url( $url ) . "'>" . esc_html( eme_translate( $event['event_name'] ) ) . '</a> (' . eme_get_recurrence_desc_from_array( $recurrence ) . ')</li>';
	}
	$title = '<h2>' . esc_html__( 'Recurrences using this list of holidays', 'events-made-easy' ) . '</h2>';
	if ( empty( $html ) ) {
		return $title . '<p>' . esc_html__( 'None', 'events-made-easy' ) . '</p>';
    } else {
		$title .= '<p>' . esc_html__( 'These recurrences might get changed: events happening on a day matching the holidays will get cancelled together with their bookings (with an optional cancel mail to the booker).', 'events-made-easy' ) . '</p>';
    }
	return $title . '<ul>' . $html . '</ul>';
}

// future events (of recurrences using this list) that fall on one of $holiday_days and have bookings or task signups
// those are the ones eme_db_update_recurrence would otherwise keep
function eme_get_holiday_booked_events( $holidays_id, $holiday_days ) {
	global $wpdb;
	$booked      = [];
	$recurrences = eme_get_recurrences_for_holidays_id( $holidays_id );
	if ( empty( $recurrences ) || empty( $holiday_days ) ) {
		return $booked;
	}
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$rec_ids      = array_map( 'intval', array_column( $recurrences, 'recurrence_id' ) );
	$placeholders = implode( ',', array_fill( 0, count( $rec_ids ), '%d' ) );
	$now          = ( new emeExpressiveDate( 'now', EME_TIMEZONE ) )->format( 'Y-m-d H:i:s' );
	$sql          = $wpdb->prepare( "SELECT event_id,event_start,event_rsvp,event_tasks FROM $events_table WHERE recurrence_id IN ($placeholders) AND event_status <> %d AND event_start >= %s ORDER BY event_start", array_merge( $rec_ids, [ EME_EVENT_STATUS_TRASH, $now ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$events       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $events as $event ) {
		if ( ! isset( $holiday_days[ eme_get_date_from_dt( $event['event_start'] ) ] ) ) {
			continue;
		}
		$bookings = $event['event_rsvp'] ? eme_count_bookings_for( $event['event_id'] ) : 0;
		$signups  = $event['event_tasks'] ? eme_count_tasksignups_for( $event['event_id'] ) : 0;
		if ( $bookings > 0 || $signups > 0 ) {
			$booked[] = [
				'event_id'    => $event['event_id'],
				'event_start' => $event['event_start'],
				'bookings'    => $bookings,
				'signups'     => $signups,
			];
		}
	}
	return $booked;
}

// the question: what to do with the bookings of events that are about to be removed
function eme_holidays_confirm_layout( $holidays, $holidays_id, $booked ) {
?>
	<div class="wrap">
	<h1><?php esc_html_e( 'Confirm update of holidays list', 'events-made-easy' ); ?></h1>
	<p><?php esc_html_e( 'The events below are part of a recurrence using this list, fall on a holiday of the new list and have bookings or task signups. If you continue they will be moved to the trash bin, together with their bookings.', 'events-made-easy' ); ?></p>
	<ul>
	<?php
	foreach ( $booked as $b ) {
		$event = eme_get_event( $b['event_id'] );
		echo '<li>' . esc_html( eme_localized_date( $b['event_start'], EME_TIMEZONE ) . ' - ' . eme_translate( $event['event_name'] ) ) . ' (' . intval( $b['bookings'] ) . ' ' . esc_html__( 'bookings', 'events-made-easy' ) . ', ' . intval( $b['signups'] ) . ' ' . esc_html__( 'task signups', 'events-made-easy' ) . ')</li>';
	}
	?>
	</ul>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=eme-holidays' ) ); ?>">
		<?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
		<input type="hidden" name="eme_admin_action" value="do_editholidays">
		<input type="hidden" name="id" value="<?php echo intval( $holidays_id ); ?>">
		<input type="hidden" name="name" value="<?php echo esc_attr( $holidays['name'] ); ?>">
		<textarea name="list" style="display:none"><?php echo esc_textarea( $holidays['list'] ); ?></textarea>
		<p>
		<?php
		esc_html_e( 'Send emails for cancelled bookings too?', 'events-made-easy' );
		echo eme_ui_select_binary( 0, 'send_trashmails' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select()
		?>
		</p>
		<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Update list of holidays', 'events-made-easy' ); ?>">
		<a class="button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=eme-holidays' ) ); ?>"><?php esc_html_e( 'Cancel', 'events-made-easy' ); ?></a>
	</form>
	</div>
<?php
}

// called after the list has been saved: trash the booked events on holidays (same as the trashEvents bulk action),
// then let the normal recurrence update remove/add the remaining events (only_change_recdates=1, like extendRecurrences)
function eme_holidays_update_recurrences( $holidays_id, $send_trashmails ) {
	$changes     = [ 'removed' => 0, 'added' => 0 ];
	$recurrences = eme_get_recurrences_for_holidays_id( $holidays_id );
	if ( empty( $recurrences ) ) {
		return $changes;
	}
	// fetch the template events first, the first event might get trashed below
	// and remember the current events, so we can report what really changed
	$templates  = [];
	$before_ids = [];
	foreach ( $recurrences as $recurrence ) {
		$templates[ $recurrence['recurrence_id'] ] = eme_get_event( eme_get_recurrence_first_eventid( $recurrence['recurrence_id'] ) );
		$before_ids = array_merge( $before_ids, eme_get_recurrence_eventids( $recurrence['recurrence_id'] ) );
	}
	$booked = eme_get_holiday_booked_events( $holidays_id, eme_get_holiday_listinfo( $holidays_id ) );
	if ( ! empty( $booked ) ) {
		eme_trash_events( implode( ',', array_column( $booked, 'event_id' ) ), $send_trashmails );
	}
	$after_ids = [];
	foreach ( $recurrences as $recurrence ) {
		$event = $templates[ $recurrence['recurrence_id'] ];
		if ( ! empty( $event ) ) {
			eme_db_update_recurrence( $recurrence, $event, 1 );
			wp_cache_delete( 'eme_event ' . $event['event_id'] );
		}
		$after_ids = array_merge( $after_ids, eme_get_recurrence_eventids( $recurrence['recurrence_id'] ) );
	}
	$changes['removed'] = count( array_diff( $before_ids, $after_ids ) );
	$changes['added']   = count( array_diff( $after_ids, $before_ids ) );
	return $changes;
}

function eme_get_holiday_lists() {
	global $wpdb;
	$holidays_table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
	$sql            = "SELECT id,name FROM $holidays_table";
	return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
function eme_get_holiday_list( $id ) {
	global $wpdb;
	$id = intval($id);
	$holidays_table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
	$prepared_sql   = $wpdb->prepare( "SELECT * FROM $holidays_table WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_row( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_holiday_listinfo( $id ) {
	$holiday_list = eme_get_holiday_list( $id );
	return eme_parse_holiday_list( $holiday_list['list'] );
}

function eme_parse_holiday_list( $list ) {
	$res_days     = [];
    $list_lines = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "\n", $list ) ) ) );
	foreach ( $list_lines as $day_info ) {
		//$info=explode(',',$day_info);
		[$date_info, $name, $class, $link] = array_pad( array_map( 'trim', explode( ',', $day_info ) ), 4, '' );
		if ( preg_match( '/^([0-9]{4}-[0-9]{2}-[0-9]{2})--([0-9]{4}-[0-9]{2}-[0-9]{2})$/', $date_info, $matches ) ) {
			$start   = $matches[1];
			$end     = $matches[2];
			$current = strtotime( $start );
			$end     = strtotime( $end );
			while ( $current <= $end ) {
                $day_in_range                       = date( 'Y-m-d', $current ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- local date iteration from strtotime()
				$res_days[ $day_in_range ]['name']  = $name;
				$res_days[ $day_in_range ]['class'] = $class;
				$res_days[ $day_in_range ]['link']  = $link;
				$current                            = strtotime( '+1 days', $current );
			}
		} elseif ( preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_info ) ) {
			$res_days[ $date_info ]['name']  = $name;
			$res_days[ $date_info ]['class'] = $class;
			$res_days[ $date_info ]['link']  = $link;
		}
	}
	return $res_days;
}

function eme_get_holidays_array_by_id() {
	$holidays       = eme_get_holiday_lists();
	$holidays_by_id = [];
	if ( ! empty( $holidays ) ) {
		$holidays_by_id[] = '';
		foreach ( $holidays as $holiday_list ) {
			$holidays_by_id[ $holiday_list['id'] ] = $holiday_list['name'];
		}
	}
	return $holidays_by_id;
}

# return number of days until next event or until the specified event
function eme_holidays_shortcode( $atts ) {
	eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

	$atts = shortcode_atts(
		    [
				'id'    => 0,
				'scope' => 'all',
			],
		    $atts
	);

	$id = intval($atts['id']);
	$scope = eme_sanitize_request($atts['scope']);
	if ( empty( $id ) ) {
		return;
	}

	$eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
	print '<div id="eme_holidays_list">';
    $days = eme_get_holiday_listinfo($id);
	foreach ( $days as $day=>$rest ) {
		if ( empty( $day ) ) {
			continue;
		}
		$eme_date_obj = new emeExpressiveDate( $day, EME_TIMEZONE );
		if ( $scope === 'future' && $eme_date_obj < $eme_date_obj_now ) {
			continue;
		}
		if ( $scope === 'past' && $eme_date_obj > $eme_date_obj_now ) {
			continue;
		}
		if ( ! empty( $rest['class'] ) ) {
			print '<span class="' . esc_attr( $rest['class'] ) . '" id="eme_holidays_date">' . eme_localized_date( $day, EME_TIMEZONE ) . '</span>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			print '<span id="eme_holidays_date">' . eme_localized_date( $day, EME_TIMEZONE ) . '</span>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		print '&nbsp; <span id="eme_holidays_name">' . esc_html( eme_translate( $rest['name'] ?? '' ) ) . '</span><br>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	print '</div>';
}

add_action( 'wp_ajax_eme_holidays_list', 'eme_ajax_action_holidays_list' );
add_action( 'wp_ajax_eme_manage_holidays', 'eme_ajax_action_manage_holidays' );

function eme_ajax_action_holidays_list() {
    global $wpdb;

    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $fTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_holidays' ) )) {
        $fTableResult['Result']  = 'ERROR';
        $fTableResult['htmlmessage'] = eme_message_error_div( __( 'Access denied!', 'events-made-easy' ) );
        print wp_json_encode( $fTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
    $limit    = eme_get_ftable_limit();
    $orderby  = eme_get_ftable_orderby();

    $count_sql  = "SELECT COUNT(*) FROM $table";
    $sql  = "SELECT * FROM $table $orderby $limit";
    $recordCount = $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is a safe variable
    $rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is a safe variable

    $records = [];
    foreach ( $rows as $row ) {
        $record  = [];
        if ( empty( $row['name'] ) ) {
            $row['name'] = __( 'No name', 'events-made-easy' );
        }
        $record['id'] = "<a href='" . esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-holidays&eme_admin_action=edit_holidays&id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) ) . "'>" . $row['id'] . '</a>';
        $record['name'] = "<a href='" . esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-holidays&eme_admin_action=edit_holidays&id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) ) . "'>" . esc_html( eme_translate( $row['name'] ) ) . '</a>';
        $records[] = $record;
    }
    $fTableResult['Result']           = 'OK';
    $fTableResult['Records']          = $records;
    $fTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $fTableResult );
    wp_die();
}

function eme_ajax_action_manage_holidays() {
    global $wpdb;
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $fTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_holidays' ) )) {
        $fTableResult['Result']  = 'ERROR';
        $fTableResult['htmlmessage'] = eme_message_error_div( __( 'Access denied!', 'events-made-easy' ) );
        print wp_json_encode( $fTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
        case 'deleteHolidays':
            $ids_list = eme_sanitize_request($_POST['holidays_ids']);
            if (eme_is_list_of_int($ids_list)) {
                $ids_arr = array_map('intval', explode(',', $ids_list));
                $placeholders = implode(',', array_fill(0, count($ids_arr), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id IN ($placeholders)", ...$ids_arr)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }
            $fTableResult['htmlmessage'] = eme_message_ok_div( __( 'Holiday lists deleted', 'events-made-easy' ) );
            $fTableResult['Result'] = 'OK';
            break;
        }
    }
    print wp_json_encode( $fTableResult );
    wp_die();
}
