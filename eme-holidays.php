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
				$validation_result = $wpdb->update( $holidays_table, $holidays, [ 'id' => intval( $_POST['id'] ) ] );
				if ( $validation_result !== false ) {
						$message = __( 'Successfully edited the list of holidays', 'events-made-easy' );
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
    <div id="icon-edit" class="icon32">
    </div>

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
         <p><?php echo nl2br( esc_html( $message ) ); ?></p>
    </div>
    <?php } ?>

    <div id="holidays-message" class="eme-hidden" ></div>
    <div id="bulkactions">
    <form action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
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
	$layout      = "
   <div class='wrap'>
      <div id='icon-edit' class='icon32'>
      </div>
         
      <h1>" . $h1_string . '</h1>';

    $layout .= "
      <div id='ajax-response'></div>

      <form name='edit_holidays' id='edit_holidays' method='post' action='" . admin_url( "admin.php?page=$plugin_page" ) . "'>
      <input type='hidden' name='eme_admin_action' value='do_editholidays'>
      <input type='hidden' name='id' value='" . $holidays_id . "'>
      $nonce_field
      <table class='form-table'>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='name'>" . __( 'Holidays listname', 'events-made-easy' ) . "</label></th>
               <td><input name='name' id='name' required='required' type='text' value='" . eme_esc_html( $holidays['name'] ) . "' size='40'><br>
                 " . __( 'The name of the holidays list', 'events-made-easy' ) . "</td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='description'>" . __( 'Holidays list', 'events-made-easy' ) . "</label></th>
               <td><textarea name='list' id='description' rows='5' >" . eme_esc_html( $holidays['list'] ) . '</textarea><br>
                 ' . __( 'Basic format: YYYY-MM-DD, one per line', 'events-made-easy' ) . '<br>' . __( 'For more information about holidays, see ', 'events-made-easy' ) . " <a target='_blank' href='https://www.e-dynamics.be/wordpress/?cat=6086'>" . __( 'the documentation', 'events-made-easy' ) . "</a></td>
            </tr>
         </table>
      <p class='submit'><input type='submit' class='button-primary' name='submit' value='" . $action_string . "'></p>
      </form>
   </div>
   ";
	echo $layout; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	$sql            = $wpdb->prepare( "SELECT * FROM $holidays_table WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_holiday_listinfo( $id ) {
	$holiday_list = eme_get_holiday_list( $id );
	$res_days     = [];
	$days         = explode( "\n", str_replace( "\r", "\n", $holiday_list['list'] ) );
	foreach ( $days as $day_info ) {
		//$info=explode(',',$day_info);
		[$date_info, $name, $class, $link] = array_pad( explode( ',', $day_info ), 4, '' );
		if ( preg_match( '/^([0-9]{4}-[0-9]{2}-[0-9]{2})--([0-9]{4}-[0-9]{2}-[0-9]{2})$/', $date_info, $matches ) ) {
			$start   = $matches[1];
			$end     = $matches[2];
			$current = strtotime( $start );
			$end     = strtotime( $end );
			while ( $current <= $end ) {
				$day_in_range                       = date( 'Y-m-d', $current );
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
		if (!empty($class)) {
			print '<span class="'.$class.'" id="eme_holidays_date">' . eme_localized_date( $day, EME_TIMEZONE ) . '</span>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			print '<span id="eme_holidays_date">' . eme_localized_date( $day, EME_TIMEZONE ) . '</span>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		print '&nbsp; <span id="eme_holidays_name">' . eme_trans_esc_html( $name ) . '</span><br>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
        $fTableResult['Result']  = 'Error';
        $fTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $fTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_HOLIDAYS_TBNAME;
    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();

    $count_sql  = "SELECT COUNT(*) FROM $table";
    $sql  = "SELECT * FROM $table $orderby $limit";
    $recordCount = $wpdb->get_var( $count_sql );
    $rows = $wpdb->get_results( $sql, ARRAY_A );

    $records = [];
    foreach ( $rows as $row ) {
        $record  = [];
        if ( empty( $row['name'] ) ) {
            $row['name'] = __( 'No name', 'events-made-easy' );
        }
        $record['id'] = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-holidays&amp;eme_admin_action=edit_holidays&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . $row['id'] . '</a>';
        $record['name'] = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-holidays&amp;eme_admin_action=edit_holidays&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . eme_trans_esc_html( $row['name'] ) . '</a>';
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
        $fTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
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
                $wpdb->query( "DELETE FROM $table WHERE id IN ( $ids_list )");
            }
            $fTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Holiday lists deleted','events-made-easy')."</div>";
            $fTableResult['Result'] = 'OK';
            break;
        }
    }
    print wp_json_encode( $fTableResult );
    wp_die();
}
