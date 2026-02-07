<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_task() {
    $task = [
        'event_id'    => 0,
        'task_start'  => '',
        'task_end'    => '',
        'name'        => '',
        'description' => '',
        'task_seq'    => 1,
        'task_nbr'    => 0,
        'spaces'      => 1,
    ];
    return $task;
}

function eme_handle_tasks_post_adminform( $event_id, $day_difference = 0 ) {
    $eme_tasks_arr = [];
    if ( empty( $_POST['eme_tasks'] ) ) {
        return $eme_tasks_arr;
    }
    $seq_nbr       = 1;
    $task_nbr_seen = 0;
    foreach ( $_POST['eme_tasks'] as $eme_task ) {
        if ( ! empty( $eme_task['task_nbr'] ) && intval( $eme_task['task_nbr'] ) > $task_nbr_seen ) {
            $task_nbr_seen = intval( $eme_task['task_nbr'] );
        }
    }
    $next_task_nbr = $task_nbr_seen + 1;
    foreach ( $_POST['eme_tasks'] as $eme_task ) {
        $eme_task['name']       = eme_sanitize_request( $eme_task['name'] );
        $eme_task['task_seq']   = $seq_nbr;
        $eme_task['event_id']   = $event_id;
        $eme_task['task_start'] = eme_sanitize_request( $eme_task['task_start'] );
        $eme_task['task_end']   = eme_sanitize_request( $eme_task['task_end'] );
        if ( eme_is_empty_string( $eme_task['name'] ) || eme_is_empty_datetime( $eme_task['task_start'] ) || eme_is_empty_datetime( $eme_task['task_end'] ) ) {
            continue;
        }
        if ( $day_difference != 0 ) {
            $eme_date_obj_start     = new emeExpressiveDate( $eme_task['task_start'], EME_TIMEZONE );
            $eme_date_obj_end       = new emeExpressiveDate( $eme_task['task_end'], EME_TIMEZONE );
            $eme_task['task_start'] = $eme_date_obj_start->addDays( $day_difference )->getDateTime();
            $eme_task['task_end']   = $eme_date_obj_end->addDays( $day_difference )->getDateTime();
        }
        $eme_task['description'] = eme_sanitize_request( $eme_task['description'] );
        // we check for task nbr to know if we need an update or insert
        if ( empty( $eme_task['task_nbr'] ) ) {
            $eme_task['task_nbr'] = $next_task_nbr;
            ++$next_task_nbr;
            $task_id = eme_db_insert_task( $eme_task );
        } else {
            // we update by the combo event_id and task_nbr and not by task_id
            // that way we can do task updates for recurrences too
            $task_id = eme_db_update_task_by_task_nbr( $eme_task );
        }
        $eme_tasks_arr[] = $task_id;
        ++$seq_nbr;
    }
    return $eme_tasks_arr;
}

function eme_db_insert_task( $line ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASKS_TBNAME;

    // first check for task_nbr
    if (!isset($line['task_nbr'])) {
        $sql      = $wpdb->prepare( "SELECT IFNULL(max(task_nbr),0) FROM $table WHERE event_id = %d", $line['event_id'] );
        $task_nbr = intval($wpdb->get_var( $sql ));
        $line['task_nbr'] = $task_nbr + 1;
    }
    $tmp_task = eme_new_task();
    // we only want the columns that interest us
    $keys = array_intersect_key( $line, $tmp_task );
    $task = array_merge( $tmp_task, $keys );

    if ( $wpdb->insert( $table, $task ) === false ) {
        return false;
    } else {
        $task_id = $wpdb->insert_id;
        return $task_id;
    }
}

function eme_db_update_task_by_task_nbr( $line ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASKS_TBNAME;

    // get the task id
    $sql     = $wpdb->prepare( "SELECT task_id FROM $table WHERE event_id = %d AND task_nbr = %d", $line['event_id'], $line['task_nbr'] );
    $task_id = $wpdb->get_var( $sql );
    if ( empty( $task_id ) ) {
        // this happens for recurrences where e.g. a new day is added to the recurrence
        return eme_db_insert_task( $line );
    } else {
        $line['task_id'] = $task_id;
        eme_db_update_task( $line );
        return $task_id;
    }
}

function eme_db_update_task( $line ) {
    global $wpdb;
    $table            = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $where            = [];
    $where['task_id'] = $line['task_id'];

    $tmp_task = eme_new_task();
    // we only want the columns that interest us
    $keys = array_intersect_key( $line, $tmp_task );
    $task = array_merge( $tmp_task, $keys );

    if ( $wpdb->update( $table, $task, $where ) === false ) {
        return false;
    } else {
        return true;
    }
}

function eme_db_delete_task( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $wpdb->delete( $table, [ 'task_id' => $task_id ], ['%d'] );

    $table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $wpdb->delete( $table, [ 'related_id' => $task_id, 'type' => 'tasksignup' ], ['%d', '%s'] );
}

function eme_delete_event_tasks( $event_id ) {
    global $wpdb;
    // First get all task IDs for this event
    $tasks_table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $task_ids = $wpdb->get_col( 
        $wpdb->prepare( "SELECT task_id FROM $tasks_table WHERE event_id = %d", $event_id ) 
    );
    
    // Delete answers for each task
    if (!empty($task_ids)) {
        $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
        $placeholders = implode(',', array_fill(0, count($task_ids), '%d'));
        
        $sql = $wpdb->prepare( 
            "DELETE FROM $answers_table WHERE related_id IN ($placeholders) AND type = 'tasksignup'", 
            $task_ids 
        );
        $wpdb->query( $sql );
    }
    
    // Delete the tasks
    $sql = $wpdb->prepare( "DELETE FROM $tasks_table WHERE event_id = %d", $event_id );
    $wpdb->query( $sql );
    
    // Delete the task signups
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql = $wpdb->prepare( "DELETE FROM $table WHERE event_id = %d", $event_id );
    $wpdb->query( $sql );
}

function eme_delete_event_old_tasks( $event_id, $ids_arr ) {
    global $wpdb;
    if ( empty( $ids_arr ) || ! eme_is_numeric_array( $ids_arr ) ) {
        return;
    }
    $ids_list = implode(',', $ids_arr);
    $table    = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $sql = $wpdb->prepare( "DELETE FROM $table WHERE event_id=%d AND task_id NOT IN ( $ids_list )", $event_id);
    $wpdb->query( $sql);
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql = $wpdb->prepare( "DELETE FROM $table WHERE event_id=%d AND task_id NOT IN ( $ids_list )", $event_id);
    $wpdb->query( $sql);
}

function eme_cancel_task_signup( $signup_randomid ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    if (empty($signup_randomid)) {
        return;
    }
    $sql   = $wpdb->prepare( "DELETE FROM $table WHERE random_id=%s", $signup_randomid );
    return $wpdb->query( $sql );
}

function eme_get_tasksignup_post_answers( $task_signup ) {
    $answers     = [];
    $fields_seen = [];

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
                if ( $formfield['field_type'] == 'time_js' ) {
                    $value = eme_convert_localized_time( $formfield['field_attributes'], eme_sanitize_request( $value ) );
                } else {
                    $value = eme_sanitize_request( $value );
                }
                if ($formfield['field_purpose'] == 'people') {
                    $type = 'person';
                    $related_id = isset($task_signup['person_id'])?$task_signup['person_id']:0;
                } else {
                    $type = 'tasksignup';
                    $related_id = isset($task_signup['id'])?$task_signup['id']:0;
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

function eme_store_tasksignup_answers( $task_signup, $do_update = 1 ) {
    global $wpdb;
    if ( empty( $task_signup['id'] ) ) {
        $do_update = 0;
    }

    $person_id    = $task_signup['person_id'];
    $all_answers  = [];
    if ( $do_update ) {
        $signup_id = $task_signup['id'];
        if ( $signup_id > 0 ) {
            $signup_answers = eme_get_tasksignup_answers( $signup_id );
            $person_answers  = eme_get_person_answers( $person_id );
            wp_cache_delete( "eme_person_answers $person_id" );
            $all_answers = array_merge( $signup_answers, $person_answers );
        }
    } else {
        $signup_id = 0;
    }
    $task_signup['id']=$signup_id;

    $answer_ids_seen = [];
    $found_answers   = eme_get_tasksignup_post_answers( $task_signup );
    foreach ( $found_answers as $answer ) {
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

    if ( $do_update && $signup_id > 0 ) {
        // delete old answer_ids
        foreach ( $all_answers as $answer ) {
            if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && $answer['type'] == 'tasksignup' && $answer['related_id'] == $signup_id ) {
                eme_delete_answer( $answer['answer_id'] );
            }
        }
    }
}

function eme_get_tasksignup_answers( $id ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='tasksignup'", $id );
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_db_insert_task_signup( $line ) {
    global $wpdb;
    $table               = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $line['random_id']   = eme_random_id();
    $line['signup_date'] = current_time( 'mysql', false );
    if ( $wpdb->insert( $table, $line ) === false ) {
        return false;
    } else {
        $signup_id = $wpdb->insert_id;
        $line['id'] = $signup_id;
        eme_store_tasksignup_answers($line);
        return $signup_id;
    }
}

function eme_db_update_task_signup( $line ) {
    global $wpdb;
    $table       = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $where       = [];
    $where['id'] = $line['id'];
    if ( $wpdb->update( $table, $line, $where ) === false ) {
        $res = false;
    } else {
        $res = true;
        eme_store_tasksignup_answers($line);
    }
    return $res;
}

function eme_approve_task_signup( $signup_id ) {
    global $wpdb;
    $table       = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $fields      = [];
    $fields['signup_status'] = 1;
    $where       = [];
    $where['id'] = $signup_id;
    if ( $wpdb->update( $table, $fields, $where ) === false ) {
        $res = false;
    } else {
        $res = true;
    }
    return $res;
}

function eme_transfer_person_task_signups( $person_ids, $to_person_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql = $wpdb->prepare( "UPDATE $table SET person_id = %d  WHERE person_id IN ( $person_ids )", $to_person_id);
        return $wpdb->query( $sql );
    }
}

function eme_db_delete_task_signup( $signup_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    if ( $wpdb->delete( $table, [ 'id' => $signup_id ], ['%d'] ) === false ) {
        $res = false;
    } else {
        $res = true;
    }
    return $res;
}

function eme_get_task( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE task_id=%d", $task_id );
    return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_event_tasks( $event_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE event_id=%d ORDER BY task_seq ASC", $event_id );
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_task_signup( $id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id );
    return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_count_task_signups( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE task_id=%d", $task_id );
    return $wpdb->get_var( $sql );
}

function eme_count_task_approved_signups( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE task_id=%d AND signup_status=1", $task_id );
    return $wpdb->get_var( $sql );
}

function eme_count_task_pending_signups( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE task_id=%d AND signup_status=0", $task_id );
    return $wpdb->get_var( $sql );
}

function eme_get_task_signups( $task_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE task_id=%d ", $task_id );
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_task_signups_by( $wp_id, $task_id = 0, $event_id = 0, $scope = 'future' ) {
    global $wpdb;
    $table        = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;

    $events_join = "LEFT JOIN $events_table ON $table.event_id=$events_table.event_id";
    $order_arr   = [];
    if ( empty( $event_id ) ) {
        $order_arr[] = "$events_table.event_start ASC, $events_table.event_name ASC";
    }
    if ( empty( $task_id ) ) {
        $order_arr[] = "$table.task_start ASC, $table.task_end ASC, $table.task_seq ASC";
    }
    if ( ! empty( $order_arr ) ) {
        $order_by = 'ORDER BY ' . join( ', ', $order_arr );
    } else {
        $order_by = '';
    }

    $wp_id     = intval( $wp_id );
    $where_arr = [ "$people_table.wp_id=$wp_id" ];
    if ( empty( $event_id ) ) {
        if ( $scope == 'future' ) {
            $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
            $search_end_date  = $eme_date_obj_now->getDateTime();
            $where_arr[]      = "$events_table.event_end >= '$search_end_date'";
        } elseif ( $scope == 'past' ) {
            $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
            $search_end_date  = $eme_date_obj_now->getDateTime();
            $where_arr[]      = "$events_table.event_end <= '$search_end_date'";
        }
    } else {
        $where_arr[] = "$table.event_id = " . intval( $event_id );
    }
    if ( ! empty( $task_id ) ) {
        $where_arr[] = "$table.task_id = " . intval( $task_id );
    }
    $where = 'WHERE ' . implode( ' AND ', $where_arr );

    $sql = "SELECT $table.* FROM $table LEFT JOIN $people_table ON $table.person_id=$people_table.person_id $events_join $where $order_by";
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_tasksignup_personids( $signup_ids ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    if ( eme_is_list_of_int( $signup_ids ) ) {
        return $wpdb->get_col ( "SELECT DISTINCT person_id FROM $table WHERE id IN ( $signup_ids )" );
    }
}

function eme_count_event_task_signups( $event_id ) {
    global $wpdb;
    $table      = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT task_id, COUNT(*) as signup_count FROM $table WHERE event_id=%d GROUP BY task_id", $event_id );
    $res        = $wpdb->get_results( $sql, ARRAY_A );
    $return_arr = [];
    foreach ( $res as $row ) {
        $return_arr[ $row['task_id'] ] = $row['signup_count'];
    }
    return $return_arr;
}

function eme_get_event_task_signups( $event_id ) {
    global $wpdb;
    $tasks_table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $table       = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql         = $wpdb->prepare( "SELECT * FROM $table LEFT JOIN $tasks_table ON $table.task_id=$tasks_table.task_id WHERE $table.event_id=%d", $event_id );
    $res         = $wpdb->get_results( $sql, ARRAY_A );
    $return_arr  = [];
    foreach ( $res as $row ) {
        $return_arr[ $row['id'] ] = $row;
    }
    return $return_arr;
}

function eme_count_event_task_person_signups( $event_id, $person_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE event_id=%d AND person_id=%d", $event_id, $person_id );
    return $wpdb->get_var( $sql );
}

function eme_count_person_task_signups( $task_id, $person_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE task_id=%d AND person_id=%d", $task_id, $person_id );
    return $wpdb->get_var( $sql );
}

function eme_check_task_signup_overlap( $task, $person_id ) {
    global $wpdb;
    $tasks_table = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $table       = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $sql         = $wpdb->prepare( "SELECT COUNT(*) FROM $table LEFT JOIN $tasks_table ON $table.task_id=$tasks_table.task_id WHERE $table.task_id<>%d AND person_id=%d AND task_start<%s AND task_end>%s", $task['task_id'], $person_id, $task['task_end'], $task['task_start'] );
    return $wpdb->get_var( $sql );
}

// for CRON
function eme_tasks_send_signup_reminders() {
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    // this gets us future and ongoing events with tasks enabled
    $events = eme_get_events( extra_conditions: 'event_tasks=1' );
    foreach ( $events as $event ) {
        if ( eme_is_empty_string( $event['event_properties']['task_reminder_days'] ) ) {
            continue;
        }
        $task_reminder_days = explode( ',', $event['event_properties']['task_reminder_days'] );
        if ( ! eme_is_numeric_array( $task_reminder_days ) ) {
            continue;
        }
        $tasks = eme_get_event_tasks( $event['event_id'] );
        foreach ( $tasks as $task ) {
            $eme_date_obj = new emeExpressiveDate( $task['task_start'], EME_TIMEZONE );
            $days_diff    = intval( $eme_date_obj_now->startOfDay()->getDifferenceInDays( $eme_date_obj->startOfDay() ) );
            foreach ( $task_reminder_days as $reminder_day ) {
                $reminder_day = intval( $reminder_day );
                if ( $days_diff == $reminder_day ) {
                    $signups = eme_get_task_signups( $task['task_id'] );
                    foreach ( $signups as $signup ) {
                        eme_email_tasksignup_action( $signup, 'reminder' );
                    }
                }
            }
        }
    }
}

// for GDPR CRON
function eme_tasks_remove_old_signups() {
    global $wpdb;
    $table                   = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $events_table            = EME_DB_PREFIX . EME_EVENTS_TBNAME;
    $remove_old_signups_days = get_option( 'eme_gdpr_remove_old_signups_days' );
    if ( empty( $remove_old_signups_days ) ) {
        return;
    } else {
        $remove_old_signups_days = abs( intval($remove_old_signups_days) );
    }

    $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $old_date     = $eme_date_obj->minusDays( $remove_old_signups_days )->getDateTime();

    // we don't remove old bookings, just anonymize them
    $sql = $wpdb->prepare("DELETE FROM $table WHERE event_id IN (SELECT event_id FROM $events_table WHERE $events_table.event_end < %s)", $old_date);
    $wpdb->query( $sql );
}


function eme_task_signups_page() {
    eme_task_signups_table_layout();
}

function eme_task_signups_table_layout( $message = '' ) {
    $nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    if ( empty( $message ) ) {
        $hidden_class = 'eme-hidden';
    } else {
        $hidden_class = '';
    }

    echo "
      <div class='wrap nosubsub'>
      <div id='poststuff'>
         <div id='icon-edit' class='icon32'>
         </div>
         <h1>" . __( 'Manage task signups', 'events-made-easy' ) . "</h1>\n ";

    ?>
    <div id="tasksignups-message" class="notice is-dismissible eme-message-admin <?php echo $hidden_class; ?>">
        <p><?php echo $message; ?></p>
    </div>

    <form action="#" method="post">
    <?php if (isset($_GET['event_id'])) { ?>
        <input type="hidden" name="search_eventid" id="search_eventid" value="<?php echo intval($_GET['event_id']);?>">
        <?php if (isset($_GET['status'])) { ?>
            <input type="hidden" name="search_signup_status" id="search_signup_status" value="<?php echo intval($_GET['status']);?>">
        <?php } ?>
    <?php } else { ?>
        <input type="search" name="search_name" id="search_name" placeholder="<?php esc_attr_e( 'Task name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=20>
        <input type="search" name="search_event" id="search_event" placeholder="<?php esc_attr_e( 'Event name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=20>
        <input type="search" name="search_person" id="search_person" placeholder="<?php esc_attr_e( 'Person name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=20>
        <select id="search_scope" name="search_scope">
        <?php
        $scope_names           = [];
        $scope_names['past']   = __( 'Past events', 'events-made-easy' );
        $scope_names['all']    = __( 'All events', 'events-made-easy' );
        $scope_names['future'] = __( 'Future events', 'events-made-easy' );
        foreach ( $scope_names as $key => $value ) {
            $selected = '';
            if ( $key == 'future' ) {
                $selected = "selected='selected'";
            }
            echo "<option value='".esc_attr($key)."' $selected>".esc_html($value)."</option>";
        }
        ?>
        </select>
        <?php
        $eme_signup_status_array = [
            -1 => __('All', 'events-made-easy'),
            0  => __('Pending', 'events-made-easy'),
            1 => __('Approved', 'events-made-easy')
        ];
        if (isset($_GET['status']))
            echo eme_ui_select( intval($_GET['status']), 'search_signup_status', $eme_signup_status_array );
        else
            echo eme_ui_select( -1, 'search_signup_status', $eme_signup_status_array );
        ?>

        <input id="search_start_date" type="hidden" name="search_start_date" value="">
        <input id="eme_localized_search_start_date" type="text" name="eme_localized_search_start_date" value="" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on start date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_start_date' class='eme_formfield_fdate eme_searchfilter'>
        <input id="search_end_date" type="hidden" name="search_end_date" value="">
        <input id="eme_localized_search_end_date" type="text" name="eme_localized_search_end_date" value="" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on end date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_end_date' class='eme_formfield_fdate eme_searchfilter'>
        <button id="TaskSignupsLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter task signups', 'events-made-easy' ); ?></button>
    <?php } ?>
    </form>

    <div id="bulkactions">
    <form id='task-signups-form' action="#" method="post">
    <?php echo $nonce_field; ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
    <option value="sendReminders"><?php esc_html_e( 'Send reminders for task signups', 'events-made-easy' ); ?></option>
    <option value="approveTaskSignups"><?php esc_html_e( 'Approve selected task signups', 'events-made-easy' ); ?></option>
    <option value="deleteTaskSignups"><?php esc_html_e( 'Delete selected task signups', 'events-made-easy' ); ?></option>
    </select>
        <span id="span_sendmails" class="eme-hidden">
        <?php
        esc_html_e( 'Send emails to people upon changes being made?', 'events-made-easy' );
        echo eme_ui_select_binary( 1, 'send_mail' );
        ?>
        </span>
    <button id="TaskSignupsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
<?php
    $formfields               = eme_get_formfields( '', 'generic,tasksignup' );
    $extrafields_arr          = [];
    $extrafieldnames_arr      = [];
    $extrafieldsearchable_arr = [];
    foreach ( $formfields as $formfield ) {
        $extrafields_arr[]          = intval($formfield['field_id']);
        $extrafieldnames_arr[]      = str_replace(',','&sbquo;',eme_trans_esc_html( $formfield['field_name'] ));
        $extrafieldsearchable_arr[] = esc_html($formfield['searchable']);
    }
    $extrafields          = join( ',', $extrafields_arr );
    $extrafieldnames      = join( ',', $extrafieldnames_arr );
    $extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
?>
    <div id="TaskSignupsTableContainer" data-extrafields='<?php echo $extrafields; ?>' data-extrafieldnames='<?php echo $extrafieldnames; ?>' data-extrafieldsearchable='<?php echo $extrafieldsearchable; ?>'></div>
    </div>
    </div>
    <?php
}

function eme_meta_box_div_event_task_signup_made_email( $event, $templates_array ) {
    ?>
<div>
    <b><?php esc_html_e( 'Task Signup Made Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email sent to the respondent when that person signs up for a task.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_email_subject_tpl'], 'eme_prop_task_signup_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Made Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email sent to the respondent when that person signs up for a task.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_email_body_tpl'], 'eme_prop_task_signup_email_body_tpl', $templates_array );
    ?>
</div>
<br>
<div>
    <b><?php esc_html_e( 'Contact Person Task Signup Made Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the contact person when someone signs up for a task.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_email_subject_tpl'], 'eme_prop_cp_task_signup_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Contact Person Task Signup Made Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the contact person when someone signs up for a task.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_email_body_tpl'], 'eme_prop_cp_task_signup_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_pending_email( $event, $templates_array ) {
    ?>
<div>
    <b><?php esc_html_e( 'Task Signup Pending Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email sent to the respondent when that person signs up for a task that requires approval.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_pending_email_subject_tpl'], 'eme_prop_task_signup_pending_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Pending Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email sent to the respondent when that person signs up for a task that requires approval.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_pending_email_body_tpl'], 'eme_prop_task_signup_pending_email_body_tpl', $templates_array );
    ?>
</div>
<br>
<div>
    <b><?php esc_html_e( 'Contact Person Task Signup Pending Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the contact person when someone signs up for a task that requires approval.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_pending_email_subject_tpl'], 'eme_prop_cp_task_signup_pending_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Contact Person Task Signup Pending Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the contact person when someone signs up for a task that requires approval.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_pending_email_body_tpl'], 'eme_prop_cp_task_signup_pending_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_updated_email( $event, $templates_array ) {
    ?>
<div>
    <b><?php esc_html_e( 'Task Signup Updated Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the respondent if the task signup has been updated by an admin.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_updated_email_subject_tpl'], 'eme_prop_task_signup_updated_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Updated Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the respondent if the task signup has been updated by an admin.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_updated_email_body_tpl'], 'eme_prop_task_signup_updated_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_cancelled_email( $event, $templates_array ) {
    ?>
<div>
    <b><?php esc_html_e( 'Task Signup Cancelled Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the respondent when he himself cancels a task signup.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_cancelled_email_subject_tpl'], 'eme_prop_task_signup_cancelled_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Cancelled Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the respondent when he himself cancels a task signup.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_cancelled_email_body_tpl'], 'eme_prop_task_signup_cancelled_email_body_tpl', $templates_array );
    ?>
</div>
<br>
<div>
    <b><?php esc_html_e( 'Contact Person Task Signup Cancelled Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the contact person when a respondent cancels a task signup.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_cancelled_email_subject_tpl'], 'eme_prop_cp_task_signup_cancelled_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Contact Person Task Signup Cancelled Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the contact person when a respondent cancels a task signup.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['cp_task_signup_cancelled_email_body_tpl'], 'eme_prop_cp_task_signup_cancelled_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_trashed_email( $event, $templates_array ) {
    ?>
<div id="div_event_task_signup_trashed_email">
    <b><?php esc_html_e( 'Task Signup Deleted Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the email that will be sent to the respondent if the task signup is deleted by an admin.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_trashed_email_subject_tpl'], 'eme_prop_task_signup_trashed_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Deleted Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the email that will be sent to the respondent if the task signup is deleted by an admin.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_trashed_email_body_tpl'], 'eme_prop_task_signup_trashed_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_reminder_email( $event, $templates_array ) {
    ?>
<div id="div_event_task_signup_reminder_email">
    <b><?php esc_html_e( 'Task Signup Reminder Email Subject', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The subject of the reminder email that will be sent to the respondent.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_reminder_email_subject_tpl'], 'eme_prop_task_signup_reminder_email_subject_tpl', $templates_array );
    ?>
    <br>
    <br>
    <b><?php esc_html_e( 'Task Signup Reminder Email Body', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The body of the reminder email that will be sent to the respondent.', 'events-made-easy' ); ?></p>
    <br>
    <?php
    esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' );
    echo eme_ui_select( $event['event_properties']['task_signup_reminder_email_body_tpl'], 'eme_prop_task_signup_reminder_email_body_tpl', $templates_array );
    ?>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_form_format( $event, $templates_array ) {
    ?>
<div id="div_event_task_signup_form_format">
    <b><?php esc_html_e( 'Task Signup Form (task entry section)', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The layout of the task entry section in the signup form.', 'events-made-easy' ); ?></p>
    <br>
    <?php esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' ); ?>
    <?php esc_html_e( 'Warning: this override will only be used when inside a single event, otherwise the generic setting will always be used!', 'events-made-easy' ); ?>
    <?php echo eme_ui_select( $event['event_properties']['task_form_entry_format_tpl'], 'eme_prop_task_form_entry_format_tpl', $templates_array ); ?>
    </p><p>
    <b><?php esc_html_e( 'Task Signup Form (personal info section)', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The layout of the task signup form.', 'events-made-easy' ); ?></p>
    <br>
    <?php esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' ); ?>
    <?php esc_html_e( 'Warning: this override will only be used when inside a single event, otherwise the generic setting will always be used!', 'events-made-easy' ); ?>
    <?php echo eme_ui_select( $event['event_properties']['task_signup_form_format_tpl'], 'eme_prop_task_signup_form_format_tpl', $templates_array ); ?>
    </p>
</div>
    <?php
}

function eme_meta_box_div_event_task_signup_recorded_ok_html( $event, $templates_array ) {
    ?>
<div id="div_event_task_signup_recorded_ok_html">
    <b><?php esc_html_e( 'Signup recorded message', 'events-made-easy' ); ?></b>
    <p class="eme_smaller"><?php esc_html_e( 'The text (html allowed) shown to the user when the task signup has been made successfully.', 'events-made-easy' ); ?></p>
    <br>
    <?php esc_html_e( 'Only choose a template if you want to override the default settings:', 'events-made-easy' ); ?>
    <?php echo eme_ui_select( $event['event_properties']['task_signup_recorded_ok_html_tpl'], 'eme_prop_task_signup_recorded_ok_html_tpl', $templates_array ); ?>
</div>
    <?php
}

function eme_meta_box_div_event_tasks( $event, $edit_recurrence = 0 ) {
    if ( isset( $event['is_duplicate'] ) ) {
        $tasks = eme_get_event_tasks( $event['orig_id'] );
    } elseif ( ! empty( $event['event_id'] ) ) {
        $tasks = eme_get_event_tasks( $event['event_id'] );
    } else {
        $tasks = [];
    }
    ?>
    <div id="div_tasks">
        <?php
        if ( ! empty( $tasks ) ) {
            esc_html_e( 'Change the date of the listed tasks by this many days', 'events-made-easy' );
            print "<input type='number' id=task_offset name=task_offset><button type='button' name='change_task_days' id='change_task_days'>" . __( 'Change', 'events-made-easy' ) . '</button>';
        }
        ?>
        <table class="eme_tasks">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th><strong><?php esc_html_e( 'Name', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Begin', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'End', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Spaces', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Description', 'events-made-easy' ); ?></strong></th>
                <th></th>
            </tr>
        </thead>    
        <tbody id="eme_tasks_tbody" class="eme_tasks_tbody">
            <?php
            // if there are no entries in the array, make 1 empty entry in it, so it renders at least 1 row
            if ( ! is_array( $tasks ) || count( $tasks ) == 0 ) {
                $info     = eme_new_task();
                $tasks    = [ $info ];
                $required = '';
            } else {
                $required = "required='required'";
            }
            foreach ( $tasks as $count => $task ) {
                ?>
                <tr id="eme_row_task_<?php echo $count; ?>" >
                <td>
                <?php echo "<img class='eme-sortable-handle' src='" . esc_url(EME_PLUGIN_URL) . "images/reorder.png' alt='" . esc_attr__( 'Reorder', 'events-made-easy' ) . "'>"; ?>
                </td>
                <td>
                <?php if ( ! isset( $event['is_duplicate'] ) ) : // we set the task ids only if it is not a duplicate event ?>
                    <input type='hidden' id="eme_tasks[<?php echo $count; ?>][task_id]" name="eme_tasks[<?php echo $count; ?>][task_id]" aria-label="hidden index" size="5" value="<?php if ( isset( $task['task_id'] ) ) { echo $task['task_id'];} ?>">
                    <input type='hidden' id="eme_tasks[<?php echo $count; ?>][task_nbr]" name="eme_tasks[<?php echo $count; ?>][task_nbr]" aria-label="hidden index" size="5" value="<?php if ( isset( $task['task_nbr'] ) ) { echo $task['task_nbr'];} ?>">
                <?php endif; ?>
                </td>
                <td>
                <input <?php echo $required; ?> id="eme_tasks[<?php echo $count; ?>][name]" name="eme_tasks[<?php echo $count; ?>][name]" size="15" aria-label="name" value="<?php echo $task['name']; ?>">
<?php
                if (!empty($task['task_id'])) {
                    $count_signups = eme_count_task_signups($task['task_id']);
                    if ($count_signups>0) {
                        echo "<span name='eme_tasks[$count][signup_count]' id='eme_tasks[$count][signup_count]'><br>";
                        echo "<br>";
                        echo esc_html(sprintf( _n( 'One person already signed up for this task','%d persons already signed up for this task', $count_signups, 'events-made-easy' ), $count_signups ));
                        echo "</span>";
                    }
                }
?>
                </td>
                <td>
                <input type='hidden' readonly='readonly' name='eme_tasks[<?php echo $count; ?>][task_start]' id='eme_tasks[<?php echo $count; ?>][task_start]'>
                <input <?php echo $required; ?> type='text' readonly='readonly' name='eme_tasks[<?php echo $count; ?>][dp_task_start]' id='eme_tasks[<?php echo $count; ?>][dp_task_start]' data-date='<?php if ( $task['task_start'] ) { echo eme_js_datetime( $task['task_start'] );} ?>' data-alt-field='eme_tasks[<?php echo $count; ?>][task_start]' class='eme_formfield_fdatetime'>
                </td>
                <td>
                <input type='hidden' readonly='readonly' name='eme_tasks[<?php echo $count; ?>][task_end]' id='eme_tasks[<?php echo $count; ?>][task_end]'>
                <input <?php echo $required; ?> type='text' readonly='readonly' name='eme_tasks[<?php echo $count; ?>][dp_task_end]' id='eme_tasks[<?php echo $count; ?>][dp_task_end]' data-date='<?php if ( $task['task_end'] ) { echo eme_js_datetime( $task['task_end'] );} ?>' data-alt-field='eme_tasks[<?php echo $count; ?>][task_end]' class='eme_formfield_fdatetime'>
                </td>
                <td>
                <input <?php echo $required; ?> id="eme_tasks[<?php echo $count; ?>][spaces]" name="eme_tasks[<?php echo $count; ?>][spaces]" size="12" aria-label="spaces" value="<?php echo $task['spaces']; ?>">
                </td>
                <td>
                <textarea class="eme_fullresizable" id="eme_tasks[<?php echo $count; ?>][description]" name="eme_tasks[<?php echo $count; ?>][description]" ><?php echo eme_esc_html( $task['description'] ); ?></textarea>
                </td>
                <td>
                <a href="#" class='eme_remove_task'><?php echo "<img class='eme_remove_task' src='" . esc_url(EME_PLUGIN_URL) . "images/cross.png' alt='" . esc_attr__( 'Remove', 'events-made-easy' ) . "' title='" . esc_attr__( 'Remove', 'events-made-easy' ) . "'>"; ?></a><a href="#" class="eme_add_task"><?php echo "<img class='eme_add_task' src='" . esc_url(EME_PLUGIN_URL) . "images/plus_16.png' alt='" . esc_attr__( 'Add new task', 'events-made-easy' ) . "' title='" . esc_attr__( 'Add new task', 'events-made-easy' ) . "'>"; ?></a>
                </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        </table>
        <?php esc_html_e( 'If name, start date or end date of a task is empty, it will be ignored.', 'events-made-easy' );
        esc_html_e( 'If the number of spaces for a task is 0, the task description will be treated as a section header for the next set of tasks.', 'events-made-easy' ); ?>
        <?php
        if ( $edit_recurrence ) {
            echo "<div style='background-color: lightgrey;'>";
            esc_html_e( 'For recurring events, enter the start and end date of the task as if you would do it for the first event in the series. The tasks for the other events will be adjusted accordingly.', 'events-made-easy' );
            if ( ! eme_is_empty_date( $event['event_start'] ) ) {
                echo '<br>';
                echo esc_html(sprintf( __( 'The start date of the first event in the series was initially %s', 'events-made-easy' ), eme_localized_datetime( $event['event_start'], EME_TIMEZONE ) ));
            }
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

function eme_meta_box_div_event_task_settings( $event ) {
    $eme_prop_task_registered_users_only = ( $event['event_properties']['task_registered_users_only'] ) ? "checked='checked'" : '';
    $eme_prop_task_requires_approval     = ( $event['event_properties']['task_requires_approval'] ) ? "checked='checked'" : '';
    $eme_prop_task_only_one_signup_pp    = ( $event['event_properties']['task_only_one_signup_pp'] ) ? "checked='checked'" : '';
    $eme_prop_task_allow_overlap         = ( $event['event_properties']['task_allow_overlap'] ) ? "checked='checked'" : '';
    $eme_prop_task_reminder_days         = eme_esc_html( $event['event_properties']['task_reminder_days'] );
    ?>
    <div id='div_event_task_settings'>
        <p id='p_task_registered_users_only'>
            <input id="eme_prop_task_registered_users_only" name='eme_prop_task_registered_users_only' value='1' type='checkbox' <?php echo $eme_prop_task_registered_users_only; ?>>
        <label for="eme_prop_task_registered_users_only"><?php esc_html_e( 'Require WP membership to be able to sign up for tasks?', 'events-made-easy' ); ?></label>
        </p>
        <p id='p_task_addpersontogroup'>
            <label for='eme_prop_task_addpersontogroup'><?php esc_html_e( 'Group to add people to', 'events-made-easy' ); ?></label></td>
            <td><?php echo eme_ui_multiselect_key_value( $event['event_properties']['task_addpersontogroup'], 'eme_prop_task_addpersontogroup', eme_get_static_groups(), 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class' ); ?><p class="eme_smaller"><?php esc_html_e( 'The group you want people to automatically become a member of when they subscribe.', 'events-made-easy' ); ?></p>
        <p id='p_task_requires_approval'>
            <input id="eme_prop_task_requires_approval" name='eme_prop_task_requires_approval' value='1' type='checkbox' <?php echo $eme_prop_task_requires_approval; ?>>
            <label for="eme_prop_task_requires_approval"><?php esc_html_e( 'Require approval for task signups?', 'events-made-easy' ); ?></label>
            <br><?php echo eme_ui_checkbox_binary( $event['event_properties']['ignore_pending_tasksignups'], 'eme_prop_ignore_pending_tasksignups', __( 'Consider pending (unapproved) task signups as available for new signups', 'events-made-easy' ) ); ?>
        </p>

        <p id='p_task_only_one_signup_pp'>
            <input id="eme_prop_task_only_one_signup_pp" name='eme_prop_task_only_one_signup_pp' value='1' type='checkbox' <?php echo $eme_prop_task_only_one_signup_pp; ?>>
            <label for="eme_prop_task_only_one_signup_pp"><?php esc_html_e( 'Allow only one sign up for tasks per event for a person?', 'events-made-easy' ); ?></label>
        </p>
        <p id='p_task_allow_overlap'>
            <input id="eme_prop_task_allow_overlap" name='eme_prop_task_allow_overlap' value='1' type='checkbox' <?php echo $eme_prop_task_allow_overlap; ?>>
            <label for="eme_prop_task_allow_overlap"><?php esc_html_e( 'Allow overlap for task signups?', 'events-made-easy' ); ?></label>
        </p>
        <p id='p_task_reminder_days'>
            <input id="eme_prop_task_reminder_days" name='eme_prop_task_reminder_days' type='text' value="<?php echo $eme_prop_task_reminder_days; ?>">
            <label for="eme_prop_task_reminder_days"><?php esc_html_e( 'Set the number of days before task signup reminder emails will be sent (counting from the start date of the task). If you want to send out multiple reminders, seperate the days here by commas. Leave empty for no reminder emails.', 'events-made-easy' ); ?></label>
        </p>
    </div>
    <?php
}

function eme_mytasks_signups_shortcode( $atts ) {
    eme_enqueue_frontend();
    if ( is_user_logged_in() ) {
        $wp_id = get_current_user_id();
    } else {
        return;
    }
    $person = eme_get_person_by_wp_id( $wp_id );
    if ( empty( $person ) ) {
        return;
    }
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts(
            [
                'scope'              => 'future',
                'task_id'            => 0,
                'event_id'           => 0,
                'template_id'        => 0,
                'template_id_header' => 0,
                'template_id_footer' => 0,
            ],
            $atts
    );
    $format = '';
    $header = '';
    $footer = '';
    $template_id = intval($atts['template_id']);
    $template_id_header = intval($atts['template_id_header']);
    $template_id_footer = intval($atts['template_id_footer']);
    $event_id = intval($atts['event_id']);
    $task_id = intval($atts['task_id']);
    $scope = eme_sanitize_request($atts['scope']);
    if ( ! empty( $template_id ) ) {
        $format = eme_get_template_format( $template_id );
    }
    if ( empty( $format ) ) {
        $format = eme_translate_string( get_option( 'eme_task_signup_format' ) );
    }

    if ( ! empty( $template_id_header ) ) {
        $header = eme_get_template_format( $template_id_header );
    }

    if ( ! empty( $template_id_footer ) ) {
        $footer = eme_get_template_format( $template_id_footer );
    }

    $signups = eme_get_task_signups_by( $wp_id, $task_id, $event_id, $scope );
    $result  = $header;
    foreach ( $signups as $signup ) {
        $event   = eme_get_event( $signup['event_id'] );
        $task    = eme_get_task( $signup['task_id'] );
        $result .= eme_replace_tasksignup_placeholders( $format, $signup, $person, $event, $task );
    }
    $result .= $footer;
    return $result;
}

function eme_tasks_signups_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts(
        [
            'scope'                      => 'future',
            'order'                      => 'ASC',
            'category'                   => '',
            'notcategory'                => '',
            'showperiod'                 => '',
            'author'                     => '',
            'contact_person'             => '',
            'event_id'                   => 0,
            'location_id'                => 0,
            'task_id'                    => 0,
            'show_ongoing'               => 1,
            'show_recurrent_events_once' => 0,
            'template_id'                => 0,
            'template_id_header'         => 0,
            'template_id_footer'         => 0,
            'ignore_filter'              => 0,
        ],
        $atts
    );

    $scope = eme_sanitize_request($atts['scope']);
    $order = eme_sanitize_request($atts['order']);
    $category = eme_sanitize_request($atts['category']);
    $notcategory = eme_sanitize_request($atts['notcategory']);
    $showperiod = eme_sanitize_request($atts['showperiod']);
    $author = eme_sanitize_request($atts['author']);
    $contact_person = eme_sanitize_request($atts['contact_person']);
    $event_id = eme_sanitize_request($atts['event_id']);
    $location_id = eme_sanitize_request($atts['location_id']);
    $task_id = intval($atts['task_id']);
    $show_ongoing = intval($atts['show_ongoing']);
    $show_recurrent_events_once = intval($atts['show_recurrent_events_once']);
    $template_id = intval($atts['template_id']);
    $template_id_header = intval($atts['template_id_header']);
    $template_id_footer = intval($atts['template_id_footer']);
    $ignore_filter = intval($atts['ignore_filter']);

    $event_id_arr    = [];
    $location_id_arr = [];
    $result          = '';

    $format = '';
    $header = '';
    $footer = '';
    if ( ! empty( $template_id ) ) {
        $format = eme_get_template_format( $template_id );
    }
    if ( empty( $format ) ) {
        $format = get_option( 'eme_task_signup_format' );
    }

    if ( ! empty( $template_id_header ) ) {
        $header = eme_get_template_format( $template_id_header );
    }

    if ( ! empty( $template_id_footer ) ) {
        $footer = eme_get_template_format( $template_id_footer );
    }

    if ( $task_id > 0 ) {
        $signups = eme_get_task_signups( $task_id );
        foreach ( $signups as $signup ) {
            $person  = eme_get_person( $signup['person_id'] );
            $result .= eme_replace_tasksignup_placeholders( $format, $signup, $person, $event, $task );
        }
        return $result;
    }

    if ( ! $ignore_filter && isset( $_REQUEST['eme_eventAction'] ) && eme_sanitize_request( $_REQUEST['eme_eventAction']) == 'filter' ) {
        if ( ! empty( $_REQUEST['eme_scope_filter'] ) ) {
            $scope = eme_sanitize_request( $_REQUEST['eme_scope_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_author_filter'] ) && intval( $_REQUEST['eme_author_filter'] ) > 0 ) {
            $author = intval( $_REQUEST['eme_author_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_contact_filter'] ) && intval( $_REQUEST['eme_contact_filter'] ) > 0 ) {
            $contact_person = intval( $_REQUEST['eme_contact_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_loc_filter'] ) ) {
            if ( is_array( $_REQUEST['eme_loc_filter'] ) ) {
                $arr = eme_array_remove_empty_elements( eme_sanitize_request( $_REQUEST['eme_loc_filter'] ) );
                if ( ! empty( $arr ) ) {
                    $location_id_arr = $arr;
                }
            } else {
                $location_id_arr[] = eme_sanitize_request( $_REQUEST['eme_loc_filter'] );
            }
            if ( empty( $location_id_arr ) ) {
                $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_city_filter'] ) ) {
            $cities  = eme_sanitize_request( $_REQUEST['eme_city_filter'] );
            $tmp_ids = eme_get_city_location_ids( $cities );
            if ( empty( $location_id_arr ) ) {
                $location_id_arr = $tmp_ids;
            } else {
                $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
            }
            if ( empty( $location_id_arr ) ) {
                $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_country_filter'] ) ) {
            $countries = eme_sanitize_request( $_REQUEST['eme_country_filter'] );
            $tmp_ids   = eme_get_country_location_ids( $countries );
            if ( empty( $location_id_arr ) ) {
                $location_id_arr = $tmp_ids;
            } else {
                $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
            }
            if ( empty( $location_id_arr ) ) {
                $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_cat_filter'] ) ) {
            if ( is_array( $_REQUEST['eme_cat_filter'] ) ) {
                $arr = eme_array_remove_empty_elements( eme_sanitize_request( $_REQUEST['eme_cat_filter'] ) );
                if ( ! empty( $arr ) ) {
                    $category = join( ',', $arr );
                }
            } else {
                $category = eme_sanitize_request( $_REQUEST['eme_cat_filter'] );
            }
        }
        foreach ( $_REQUEST as $key => $value ) {
            $key = eme_sanitize_request( $key );
            $value = eme_sanitize_request( $value );
            if ( preg_match( '/eme_customfield_filter(\d+)/', $key, $matches ) ) {
                $field_id  = intval( $matches[1] );
                $formfield = eme_get_formfield( $field_id );
                if ( ! empty( $formfield ) ) {
                    $is_multi = eme_is_multifield( $formfield['field_type'] );
                    if ( $formfield['field_purpose'] == 'events' ) {
                        $tmp_ids = eme_get_cf_event_ids( $value, $field_id, $is_multi );
                        if ( empty( $event_id_arr ) ) {
                            $event_id_arr = $tmp_ids;
                        } else {
                            $event_id_arr = array_intersect( $event_id_arr, $tmp_ids );
                        }
                        if ( empty( $event_id_arr ) ) {
                            $event_id = -1;
                        }
                    }
                    if ( $formfield['field_purpose'] == 'locations' ) {
                        $tmp_ids = eme_get_cf_location_ids( $value, $field_id, $is_multi );
                        if ( empty( $location_id_arr ) ) {
                            $location_id_arr = $tmp_ids;
                        } else {
                            $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
                        }
                        if ( empty( $location_id_arr ) ) {
                            $location_id = -1;
                        }
                    }
                }
            }
        }
    }
    if ( $event_id != -1 && ! empty( $event_id_arr ) ) {
        $event_id = join( ',', $event_id_arr );
    }
    if ( $location_id != -1 && ! empty( $location_id_arr ) ) {
        $location_id = join( ',', $location_id_arr );
    }

    $extra_conditions_arr = [];
    $extra_conditions     = '';
    if ( ! empty( $event_id ) ) {
        if ( strstr( ',', $event_id ) ) {
            $extra_conditions_arr[] = "event_id in (".$event_id.")";
        } else {
            $extra_conditions_arr[] = 'event_id = ' . intval( $event_id );
        }
    }

    if ( ! empty( $extra_conditions_arr ) ) {
        $extra_conditions = '(' . join( ' AND ', $extra_conditions_arr ) . ')';
    }

    $extra_conditions_arr[] = 'event_tasks = 1';
    $events                 = eme_get_events( scope: $scope, order: $order, location_id: $location_id, category: $category, author: $author, contact_person: $contact_person, show_ongoing: $show_ongoing, notcategory: $notcategory, show_recurrent_events_once: $show_recurrent_events_once, extra_conditions: $extra_conditions );

    $lang = eme_detect_lang();
    foreach ( $events as $event ) {
        $tasks = eme_get_event_tasks( $event['event_id'] );
        if ( empty( $tasks ) ) {
            continue;
        }
        $result .= "<div class='eme_event_tasks'>";
        $result .= eme_replace_event_placeholders( $header, $event );
        foreach ( $tasks as $task ) {
            if ( $task['spaces'] == 0 ) {
                $result .= '<br><span class="eme_task_section_header">'.eme_trans_esc_html( $task['name'], $lang ).'</span><br>';
            } else {
                $signups = eme_get_task_signups( $task['task_id'] );
                foreach ( $signups as $signup ) {
                    $person  = eme_get_person( $signup['person_id'] );
                    $result .= eme_replace_tasksignup_placeholders( $format, $signup, $person, $event, $task );
                }
            }
        }
        $result .= eme_replace_event_placeholders( $footer, $event );
        $result .= '</div>';
    }

    return $result;
}

function eme_tasks_signupform_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts(
            [
                'scope'                      => 'future',
                'order'                      => 'ASC',
                'category'                   => '',
                'notcategory'                => '',
                'showperiod'                 => '',
                'author'                     => '',
                'contact_person'             => '',
                'event_id'                   => 0,
                'location_id'                => 0,
                'show_ongoing'               => 1,
                'show_recurrent_events_once' => 0,
                'template_id'                => 0,
                'template_id_header'         => 0,
                'template_id_footer'         => 0,
                'signupform_template_id'     => 0,
                'notasks_template_id'        => 0,
                'skip_full'                  => 0,
                'ignore_filter'              => 0,
            ],
            $atts
    );
    $event_id_arr    = [];
    $location_id_arr = [];
    $result          = '';

    $scope = eme_sanitize_request($atts['scope']);
    $order = eme_sanitize_request($atts['order']);
    $category = eme_sanitize_request($atts['category']);
    $showperiod = eme_sanitize_request($atts['showperiod']);
    $notcategory = eme_sanitize_request($atts['notcategory']);
    $author = eme_sanitize_request($atts['author']);
    $contact_person = eme_sanitize_request($atts['contact_person']);
    $event_id = eme_sanitize_request($atts['event_id']);
    $location_id = eme_sanitize_request($atts['location_id']);

    $show_ongoing = intval($atts['show_ongoing']);
    $show_recurrent_events_once = intval($atts['show_recurrent_events_once']);
    $template_id = intval($atts['template_id']);
    $template_id_header = intval($atts['template_id_header']);
    $template_id_footer = intval($atts['template_id_footer']);
    $signupform_template_id = intval($atts['signupform_template_id']);
    $notasks_template_id = intval($atts['notasks_template_id']);
    $skip_full = intval($atts['skip_full']);
    $ignore_filter = intval($atts['ignore_filter']);

    // the filter list overrides the settings
    if ( ! $ignore_filter && isset( $_REQUEST['eme_eventAction'] ) && eme_sanitize_request( $_REQUEST['eme_eventAction']) == 'filter' ) {
        if ( ! empty( $_REQUEST['eme_scope_filter'] ) ) {
            $scope = eme_sanitize_request( $_REQUEST['eme_scope_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_author_filter'] ) && intval( $_REQUEST['eme_author_filter'] ) > 0 ) {
            $author = intval( $_REQUEST['eme_author_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_contact_filter'] ) && intval( $_REQUEST['eme_contact_filter'] ) > 0 ) {
            $contact_person = intval( $_REQUEST['eme_contact_filter'] );
        }
        if ( ! empty( $_REQUEST['eme_loc_filter'] ) ) {
            if ( is_array( $_REQUEST['eme_loc_filter'] ) ) {
                $arr = eme_array_remove_empty_elements( eme_sanitize_request( $_REQUEST['eme_loc_filter'] ) );
                if ( ! empty( $arr ) ) {
                    $location_id_arr = $arr;
                }
            } else {
                $location_id_arr[] = eme_sanitize_request( $_REQUEST['eme_loc_filter'] );
            }
            if ( empty( $location_id_arr ) ) {
                $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_city_filter'] ) ) {
            $cities  = eme_sanitize_request( $_REQUEST['eme_city_filter'] );
            $tmp_ids = eme_get_city_location_ids( $cities );
            if ( empty( $location_id_arr ) ) {
                $location_id_arr = $tmp_ids;
            } else {
                $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
            }
            if ( empty( $location_id_arr ) ) {
                    $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_country_filter'] ) ) {
            $countries = eme_sanitize_request( $_REQUEST['eme_country_filter'] );
            $tmp_ids   = eme_get_country_location_ids( $countries );
            if ( empty( $location_id_arr ) ) {
                $location_id_arr = $tmp_ids;
            } else {
                $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
            }
            if ( empty( $location_id_arr ) ) {
                    $location_id = -1;
            }
        }
        if ( ! empty( $_REQUEST['eme_cat_filter'] ) ) {
            if ( is_array( $_REQUEST['eme_cat_filter'] ) ) {
                $arr = eme_array_remove_empty_elements( eme_sanitize_request( $_REQUEST['eme_cat_filter'] ) );
                if ( ! empty( $arr ) ) {
                    $category = join( ',', $arr );
                }
            } else {
                $category = eme_sanitize_request( $_REQUEST['eme_cat_filter'] );
            }
        }
        foreach ( $_REQUEST as $key => $value ) {
            $key = eme_sanitize_request( $key );
            $value = eme_sanitize_request( $value );
            if ( preg_match( '/eme_customfield_filter(\d+)/', $key, $matches ) ) {
                $field_id  = intval( $matches[1] );
                $formfield = eme_get_formfield( $field_id );
                if ( ! empty( $formfield ) ) {
                    $is_multi = eme_is_multifield( $formfield['field_type'] );
                    if ( $formfield['field_purpose'] == 'events' ) {
                        $tmp_ids = eme_get_cf_event_ids( $value, $field_id, $is_multi );
                        if ( empty( $event_id_arr ) ) {
                            $event_id_arr = $tmp_ids;
                        } else {
                            $event_id_arr = array_intersect( $event_id_arr, $tmp_ids );
                        }
                        if ( empty( $event_id_arr ) ) {
                            $event_id = -1;
                        }
                    }
                    if ( $formfield['field_purpose'] == 'locations' ) {
                        $tmp_ids = eme_get_cf_location_ids( $value, $field_id, $is_multi );
                        if ( empty( $location_id_arr ) ) {
                            $location_id_arr = $tmp_ids;
                        } else {
                            $location_id_arr = array_intersect( $location_id_arr, $tmp_ids );
                        }
                        if ( empty( $location_id_arr ) ) {
                            $location_id = -1;
                        }
                    }
                }
            }
        }
    }
    if ( $event_id != -1 && ! empty( $event_id_arr ) ) {
        $event_id = join( ',', $event_id_arr );
    }
    if ( $location_id != -1 && ! empty( $location_id_arr ) ) {
        $location_id = join( ',', $location_id_arr );
    }

    $extra_conditions_arr = [];
    $extra_conditions     = '';
    // by default we only show tasks for public events (or private if logged in, see the function eme_get_events)
    // but if the event_id is not empty we include unlisted (hidden) events too
    $include_unlisted = 0;
    if ( ! empty( $event_id ) ) {
        if ( strstr( ',', $event_id ) ) {
            $extra_conditions_arr[] = "event_id in ($event_id)";
        } else {
            $extra_conditions_arr[] = 'event_id = ' . intval( $event_id );
        }
        $include_unlisted = 1;
    }
    $extra_conditions_arr[] = 'event_tasks = 1';

    if ( ! empty( $extra_conditions_arr ) ) {
        $extra_conditions = '(' . join( ' AND ', $extra_conditions_arr ) . ')';
    }

    $events = eme_get_events( scope: $scope, order: $order, location_id: $location_id, category: $category, author: $author, contact_person: $contact_person, show_ongoing: $show_ongoing, notcategory: $notcategory, show_recurrent_events_once: $show_recurrent_events_once, extra_conditions: $extra_conditions, include_unlisted: $include_unlisted);
    if ( empty( $events ) ) {
        if ( ! empty( $notasks_template_id ) ) {
            $notasks_text = eme_get_template_format( $notasks_template_id );
        } else {
            $notasks_text = __( 'There are no tasks to sign up for right now', 'events-made-easy' );
        }
        return "<div id='eme-tasks-message' class='eme-message-info eme-tasks-message eme-no-tasks'>" . $notasks_text . '</div>';
    }

    // per event, the header and footer are repeated, the template_id itself is repeated per task
    $format = '';
    $header = '';
    $footer = '';
    if ( ! empty( $template_id ) ) {
        $format = eme_get_template_format( $template_id );
    } elseif ($event_id) {
        $event = eme_get_event($event_id);
        if (!empty($event['event_properties']['task_form_entry_format_tpl'])) {
            $format = eme_get_template_format( $event['event_properties']['task_form_entry_format_tpl'] );
        }
    } elseif (eme_is_single_event_page()) {
        $event_id = eme_sanitize_request( get_query_var( 'event_id' ) );
        $event = eme_get_event($event_id);
        if (!empty($event['event_properties']['task_form_entry_format_tpl'])) {
            $format = eme_get_template_format( $event['event_properties']['task_form_entry_format_tpl'] );
        }
    }
    if ( empty( $format ) ) {
        $format = get_option( 'eme_task_form_taskentry_format' );
    }

    if ( ! strstr( $format, '#_TASKSIGNUPCHECKBOX' ) ) {
        $format = "#_TASKSIGNUPCHECKBOX $format";
    }

    if ( ! empty( $template_id_header ) ) {
        $header = eme_get_template_format( $template_id_header );
    }

    if ( ! empty( $template_id_footer ) ) {
        $footer = eme_get_template_format( $template_id_footer );
    }

    $form_class = "";
    if ( ! eme_is_admin_request() && ! is_user_logged_in() && get_option('eme_rememberme')) {
        wp_enqueue_script( 'eme-rememberme' );
        $form_class = "class='eme-rememberme'";
        }

    $current_userid = get_current_user_id();
    if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
        ( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) {
        $search_tables = get_option( 'eme_autocomplete_sources' );
        if ( $search_tables != 'none' ) {
            wp_enqueue_script( 'eme-autocomplete-form' );
        }
    }

    $nonce   = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
    $form_id = "eme_".eme_random_id(); // JS selectors need to start with a letter, so to be sure we prefix it
    $result .= "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-tasks-message-ok-$form_id' class='eme-message-success eme-tasks-message eme-tasks-message-success eme-hidden'></div><div id='eme-tasks-message-error-$form_id' class='eme-message-error eme-tasks-message eme-tasks-message-error eme-hidden'></div><div id='div_eme-tasks-form-$form_id' class='eme-showifjs eme-hidden'><form id='$form_id' name='eme-tasks-form' method='post' $form_class action='#'>
                $nonce
                <span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
                ";

    $open_tasks_found = 0;
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $lang = eme_detect_lang();
    foreach ( $events as $event ) {
        // we add the event ids for the autocomplete check, not used for anything else
        $result               .= "<input type='hidden' name='eme_event_ids[]' id='eme_event_ids[]' value='" . $event['event_id'] . "'>";
        $registered_users_only = $event['event_properties']['task_registered_users_only'];
        if ( $registered_users_only && ! is_user_logged_in() ) {
            continue;
        }
        $tasks = eme_get_event_tasks( $event['event_id'] );
        if ( empty( $tasks ) ) {
            continue;
        }
        $result .= "<div class='eme_event_tasks'>";
        $result .= eme_replace_event_placeholders( $header, $event );
        foreach ( $tasks as $task ) {
            $used_spaces = eme_count_task_signups( $task['task_id'] );
            $free_spaces = $task['spaces'] - $used_spaces;

            $skip = 0;
            if ( $task['spaces'] > 0 && $free_spaces == 0 && $skip_full ) {
                // skip full option, so check the free spaces for that task, if 0: set $skip=1
                $skip = 1;
            }

            $task_ended   = 0;
            $task_end_obj = emeExpressiveDate::createFromFormat( 'Y-m-d H:i:s', $task['task_end'], emeExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
            if ( $task_end_obj < $eme_date_obj_now ) {
                $task_ended = 1;
            }

            if ( $free_spaces > 0 && ! $task_ended ) {
                ++$open_tasks_found;
            }
            if ( $task['spaces'] == 0 ) {
                $result .= '<br><span class="eme_task_section_header">'.eme_trans_esc_html( $task['name'], $lang ).'</span><br>';
            } elseif ( ! $skip ) {
                $result .= eme_replace_eventtaskformfields_placeholders( $format, $task, $event );
            }
        }
        $result .= eme_replace_event_placeholders( $footer, $event );
        $result .= '</div>';
    }

    // now add the signup form
    if ( $open_tasks_found > 0 ) {
        $signupform_format = '';
        if ( ! empty( $signupform_template_id ) ) {
            $signupform_format = eme_get_template_format( $signupform_template_id );
        } elseif ($event_id) {
            $event = eme_get_event($event_id);
            if (!empty($event['event_properties']['task_signup_form_format_tpl'])) {
                $signupform_format = eme_get_template_format( $event['event_properties']['task_signup_form_format_tpl'] );
            }
        } elseif (eme_is_single_event_page()) {
            $event_id = eme_sanitize_request( get_query_var( 'event_id' ) );
            $event = eme_get_event($event_id);
            if (!empty($event['event_properties']['task_signup_form_format_tpl'])) {
                $signupform_format = eme_get_template_format( $event['event_properties']['task_signup_form_format_tpl'] );
            }
        }

        if (empty($signupform_format)) {
            $signupform_format = get_option( 'eme_task_form_format' );
        }
        $result .= eme_replace_task_signupformfields_placeholders( $form_id, $signupform_format );
    } else {
        if ( ! empty( $notasks_template_id ) ) {
            $result = "<div id='eme-tasks-message' class='eme-message-info eme-tasks-message eme-no-tasks'>" . eme_get_template_format( $notasks_template_id ) . '</div>';
        } else {
            $result = "<div id='eme-tasks-message' class='eme-message-info eme-tasks-message eme-no-tasks'>" . __( 'There are no tasks to sign up for right now', 'events-made-easy' ) . '</div>';
        }
    }

    $result .= '</form></div>';
    return $result;
}

function eme_email_tasksignup_action( $signup, $action ) {
    $person       = eme_get_person( $signup['person_id'] );
    $event        = eme_get_event( $signup['event_id'] );
    $task         = eme_get_task( $signup['task_id'] );
    $person_email = $person['email'];

    $contact        = eme_get_event_contact( $event );
    $contact_email  = $contact->user_email;
    $contact_name   = $contact->display_name;
    $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';

    // first get the initial values
    if ( $action == 'pending' ) {
        $subject = eme_get_template_format_plain( $event['event_properties']['task_signup_pending_email_subject_tpl'] ) ?: get_option( 'eme_task_signup_pending_email_subject' );
        $body = eme_get_template_format_plain( $event['event_properties']['task_signup_pending_email_body_tpl'] ) ?: get_option( 'eme_task_signup_pending_email_body' );
        $cp_subject = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_pending_email_subject_tpl'] ) ?: get_option( 'eme_cp_task_signup_pending_email_subject' );
        $cp_body = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_pending_email_body_tpl'] ) ?: get_option( 'eme_cp_task_signup_pending_email_body' );
    } elseif ( $action == 'new' ) {
        $subject = eme_get_template_format_plain( $event['event_properties']['task_signup_email_subject_tpl'] ) ?: get_option( 'eme_task_signup_email_subject' );
        $body = eme_get_template_format_plain( $event['event_properties']['task_signup_email_body_tpl'] ) ?: get_option( 'eme_task_signup_email_body' );
        $cp_subject = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_email_subject_tpl'] ) ?: get_option( 'eme_cp_task_signup_email_subject' );
        $cp_body = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_email_body_tpl'] ) ?: get_option( 'eme_cp_task_signup_email_body' );
    } elseif ( $action == 'reminder' ) {
        $subject = eme_get_template_format_plain( $event['event_properties']['task_signup_reminder_email_subject_tpl'] ) ?: get_option( 'eme_task_signup_reminder_email_subject' );
        $body = eme_get_template_format_plain( $event['event_properties']['task_signup_reminder_email_body_tpl'] ) ?: get_option( 'eme_task_signup_reminder_email_body' );
        $cp_subject = '';
        $cp_body    = '';
    } elseif ( $action == 'cancel' ) {
        $subject = eme_get_template_format_plain( $event['event_properties']['task_signup_cancelled_email_subject_tpl'] ) ?: get_option( 'eme_task_signup_cancelled_email_subject' );
        $body = eme_get_template_format_plain( $event['event_properties']['task_signup_cancelled_email_body_tpl'] ) ?: get_option( 'eme_task_signup_cancelled_email_body' );
        $cp_subject = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_cancelled_email_subject_tpl'] ) ?: get_option( 'eme_cp_task_signup_cancelled_email_subject' );
        $cp_body = eme_get_template_format_plain( $event['event_properties']['cp_task_signup_cancelled_email_body_tpl'] ) ?: get_option( 'eme_cp_task_signup_cancelled_email_body' );
    } elseif ( $action == 'delete' ) {
        $subject = eme_get_template_format_plain( $event['event_properties']['task_signup_trashed_email_subject_tpl'] ) ?: get_option( 'eme_task_signup_trashed_email_subject' );
        $body = eme_get_template_format_plain( $event['event_properties']['task_signup_trashed_email_body_tpl'] ) ?: get_option( 'eme_task_signup_trashed_email_body' );
        $cp_subject = '';
        $cp_body    = '';
    }

    if ( ! empty( $cp_subject ) && ! empty( $cp_body ) ) {
        $cp_subject = eme_replace_tasksignup_placeholders( $cp_subject, $signup, $person, $event, $task, 'text' );
        $cp_body    = eme_replace_tasksignup_placeholders( $cp_body, $signup, $person, $event, $task, $mail_text_html );
        eme_queue_mail( $cp_subject, $cp_body, $contact_email, $contact_name, $contact_email, $contact_name, $contact_email, $contact_name );
    }
    $subject     = eme_replace_tasksignup_placeholders( $subject, $signup, $person, $event, $task, 'text' );
    $body        = eme_replace_tasksignup_placeholders( $body, $signup, $person, $event, $task, $mail_text_html );
    $person_name = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
    $mail_res    = eme_queue_mail( $subject, $body, $contact_email, $contact_name, $person_email, $person_name, $contact_email, $contact_name, 0, $person['person_id'] );
    return $mail_res;
}

function eme_replace_task_placeholders( $format, $task, $event, $target = 'html', $lang = '' ) {
    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    $used_spaces = eme_count_task_signups( $task['task_id'] );
    $free_spaces = $task['spaces'] - $used_spaces;

    if ( empty( $lang ) ) {
        $lang = eme_detect_lang();
    }

    preg_match_all( '/#(ESC)?_[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $replacement        = '';
        $need_escape        = 0;
        if ( strstr( $result, '#ESC' ) ) {
            $result      = str_replace( '#ESC', '#', $result );
            $need_escape = 1;
        }

        if ( preg_match( '/#_TASKNAME$/', $result ) ) {
            $replacement = eme_translate( $task['name'], $lang );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_TASKDESCRIPTION$/', $result ) ) {
            $replacement = eme_translate( $task['description'], $lang );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_(TASKBEGIN|TASKSTARTDATE)(\{(.+?)\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $date_format = substr( $matches[2], 1, -1 );
            } else {
                $date_format = '';
            }
            $replacement = eme_localized_datetime( $task['task_start'], EME_TIMEZONE, $date_format );
        } elseif ( preg_match( '/#_(TASKEND|TASKENDDATE)(\{(.+?)\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $date_format = substr( $matches[2], 1, -1 );
            } else {
                $date_format = '';
            }
            $replacement = eme_localized_datetime( $task['task_end'], EME_TIMEZONE, $date_format );
        } elseif ( preg_match( '/#_TASKSPACES$/', $result ) ) {
            $replacement = intval( $task['spaces'] );
        } elseif ( preg_match( '/#_FREETASKSPACES$/', $result ) ) {
            $replacement = $free_spaces;
        } elseif ( preg_match( '/#_USEDTASKSPACES$/', $result ) ) {
            $replacement = $used_spaces;
        } elseif ( preg_match( '/#_TASKID$/', $result ) ) {
            $replacement = $task['task_id'];
        } elseif ( preg_match( '/#_TASKSIGNUPS$/', $result ) ) {
            $taskformat = get_option( 'eme_task_signup_format' );
            $signups    = eme_get_task_signups( $task['task_id'] );
            foreach ( $signups as $signup ) {
                $person       = eme_get_person( $signup['person_id'] );
                $replacement .= eme_replace_tasksignup_placeholders( $taskformat, $signup, $person, $event, $task );
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

    $format = eme_replace_event_placeholders( $format, $event, $orig_target, $lang );
    // now, replace any language tags found in the format itself
    $format = eme_translate( $format, $lang );

    return $format;
}

function eme_replace_tasksignup_placeholders( $format, $signup, $person, $event, $task, $target = 'html', $lang = '' ) {
    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    if ( empty( $lang ) && ! empty( $person['lang'] ) ) {
        $lang = $person['lang'];
    }
    if ( empty( $lang ) ) {
        $lang = eme_detect_lang();
    }

    preg_match_all( '/#(REQ)?_[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $replacement        = '';

        if ( preg_match( '/#_TASKSIGNUPCANCEL_URL$/', $result ) ) {
            $replacement = eme_tasksignup_cancel_url( $signup );
            if ( $target == 'html' ) {
                $replacement = esc_url( $replacement );
            }
        } elseif ( preg_match( '/#_TASKSIGNUPCANCEL_LINK$/', $result ) ) {
            $url = eme_tasksignup_cancel_url( $signup );
            if ( $target == 'html' ) {
                $url = esc_url( $url );
                $replacement = "<a href='$url'>" . __( 'Cancel task signup', 'events-made-easy' ) . '</a>';
            }
        } elseif ( preg_match( '/#_USER_IS_REGISTERED$/', $result ) ) {
            $wp_id = get_current_user_id();
            if ( $wp_id > 0 && $wp_id == $person['wp_id'] ) {
                $replacement = 1;
            } else {
                $replacement = 0;
            }
        } elseif ( preg_match( '/#_(TASK)?COMMENT/', $result ) ) {
            $replacement = $signup['comment'];
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } else {
            $found = 0;
        }

        if ( $found ) {
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // the cancel url
    // now any leftover task/people placeholders
    $format = eme_replace_people_placeholders( $format, $person, $orig_target, $lang );
    $format = eme_replace_task_placeholders( $format, $task, $event, $orig_target, $lang );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format, $lang );

    return $format;
}

add_action( 'wp_ajax_eme_tasks', 'eme_tasks_ajax' );
add_action( 'wp_ajax_nopriv_eme_tasks', 'eme_tasks_ajax' );
function eme_tasks_ajax() {
    // check for spammers as early as possible
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
    if ( ! isset( $_POST['eme_task_signups'] ) || empty( $_POST['eme_task_signups'] ) ) {
        $message = __( 'Please select at least one task.', 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
        wp_die();
    }

    $captcha_res = eme_check_captcha();

    if ( is_user_logged_in() ) {
        $booker_wp_id = get_current_user_id();
    } else {
        $booker_wp_id = 0;
    }
    $bookerLastName  = '';
    $bookerFirstName = '';
    $bookerEmail     = '';
    $bookerComment   = '';
    // comment will be added to each task signup
    if (isset($_POST['task_comment'])) {
        $bookerComment = eme_sanitize_request( $_POST['task_comment'] );
    }
    if (isset($_POST['task_lastname'])) {
        $bookerLastName = eme_sanitize_request( $_POST['task_lastname'] );
    }
    if ( isset( $_POST['task_firstname'] ) ) {
        $bookerFirstName = eme_sanitize_request( $_POST['task_firstname'] );
    }
    if (isset($_POST['task_email'])) {
        $bookerEmail = eme_sanitize_email( $_POST['task_email'] );
    }

    // start with an empty person_id, once needed it will get a real id
    $person_id = 0;
    $message   = '';
    $nok       = 0;
    $ok        = 0;
    $t_person = eme_get_person_by_name_and_email( $bookerLastName, $bookerFirstName, $bookerEmail );
    if (!empty($t_person)) {
        $person_id = $t_person['person_id'];
    }
    foreach ( $_POST['eme_task_signups'] as $event_id => $task_id_arr ) {
        $event_id              = intval( $event_id );
        $event                 = eme_get_event( $event_id );
        $allow_overlap         = $event['event_properties']['task_allow_overlap'];
        $registered_users_only = $event['event_properties']['task_registered_users_only'];
        $only_one_signup_pp    = $event['event_properties']['task_only_one_signup_pp'];
        $signup_status         = ($event['event_properties']['task_requires_approval'])? 0 : 1;
        if ( $registered_users_only && ! $booker_wp_id ) {
            $message .= get_option( 'eme_rsvp_login_required_string' );
            $nok      = 1;
            continue;
        }
        if ($only_one_signup_pp && !empty($person_id)) {
            $event_task_count_person_signups = eme_count_event_task_person_signups( $event_id, $person_id );
            if ($event_task_count_person_signups > 0) {
                $message .= __( 'Only one signup allowed', 'events-made-easy' );
                $nok      = 1;
                continue;
            }
        }
        // the next is an array with as key the task id and value the number of signups for it
        $event_task_count_signups = eme_count_event_task_signups( $event_id );
        foreach ( $task_id_arr as $task_id ) {
            $task_id = intval( $task_id );
            $task    = eme_get_task( $task_id );
            // if full, continue
            if ( isset( $event_task_count_signups[ $task_id ] ) && $event_task_count_signups[ $task_id ] >= $task['spaces'] ) {
                $message .= __( 'No more open spaces for this task', 'events-made-easy' );
                $message .= '<br>';
                $nok      = 1;
                continue;
            }
            $add_update_person_from_form_err = '';
            if ( ! $person_id && !empty($bookerLastName) && !empty($bookerEmail) ) {
                $res         = eme_add_update_person_from_form( 0, $bookerLastName, $bookerFirstName, $bookerEmail );
                $person_id   = $res[0];
                $add_update_person_from_form_err = $res[1];
            }
            if ( ! empty( $person_id ) ) {
                // no doubles
                $person_task_count_signups = eme_count_person_task_signups( $task_id, $person_id );
                if ( $person_task_count_signups > 0 ) {
                    $message .= __( 'Duplicate signup detected', 'events-made-easy' );
                    $message .= '<br>';
                    $nok      = 1;
                    continue;
                }
                // no overlaps (unless wanted)
                if ( ! $allow_overlap && eme_check_task_signup_overlap( $task, $person_id ) ) {
                    $message .= __( 'Signup overlap with another task detected', 'events-made-easy' );
                    $message .= '<br>';
                    $nok      = 1;
                    continue;
                }
                // all ok, insert signup
                $signup = [
                    'task_id'       => $task_id,
                    'person_id'     => $person_id,
                    'event_id'      => $event_id,
                    'signup_status' => $signup_status,
                    'comment'       => $bookerComment,
                ];
                $signup_id = eme_db_insert_task_signup( $signup );
                if ($signup_id) {
                    // re-get the signup, since the random id is now in it too
                    $signup = eme_get_task_signup($signup_id);
                    if ( $signup_status == 0 ) {
                        eme_email_tasksignup_action( $signup, 'pending' );
                    } else {
                        eme_email_tasksignup_action( $signup, 'new' );
                        // we'll add the person to the group of choice if the task doesn't require approval
                        eme_add_persongroups( $person_id, $event['event_properties']['task_addpersontogroup'] );
                    }
                    #$message .= __( 'Signup done', 'events-made-easy' );
                    $format = "";
                    if (!empty($event['event_properties']['task_signup_recorded_ok_html_tpl'])) {
                        $format = eme_get_template_format( $event['event_properties']['task_signup_recorded_ok_html_tpl']);
                    }
                    if (empty($format)) {
                        $format = get_option('eme_task_signup_recorded_ok_html');
                    }
                    $person = eme_get_person($person_id);
                    $message .= eme_replace_tasksignup_placeholders( $format, $signup, $person, $event, $task );
                    $message .= '<br>';
                    $ok       = 1;
                } else {
                    $message .= __( 'Signup failed', 'events-made-easy' );
                    $message .= '<br>';
                    $nok      = 1;
                    continue;
                }
            } else {
                $message .= $add_update_person_from_form_err;
                $message .= '<br>';
                $nok      = 1;
                continue;
            }
        }
    }

    // if some task signups were ok, but others not, show ok
    if ( $ok && $nok ) {
        //echo wp_json_encode(array('Result'=>'OK','keep_form'=>1,'htmlmessage'=>$message));
        echo wp_json_encode(
            [
                'Result'      => 'OK',
                'htmlmessage' => $message,
            ]
        );
    } elseif ( $nok ) {
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
    } elseif ( $ok ) {
        //echo wp_json_encode(array('Result'=>'OK','keep_form'=>1,'htmlmessage'=>$message));
        echo wp_json_encode(
            [
                'Result'      => 'OK',
                'htmlmessage' => $message,
            ]
        );
    }

    // remove the captcha if ok
    if ( $ok ) {
        eme_captcha_remove( $captcha_res );
    }
    wp_die();
}

add_action( 'wp_ajax_eme_task_signups_list', 'eme_ajax_task_signups_list' );
add_action( 'wp_ajax_eme_manage_task_signups', 'eme_ajax_manage_task_signups' );

function eme_ajax_task_signups_list() {
    global $wpdb;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_manage_task_signups' ) ) ) {
        $ajaxResult            = [];
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }

    $signups_table     = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $events_table      = EME_DB_PREFIX . EME_EVENTS_TBNAME;
    $tasks_table       = EME_DB_PREFIX . EME_TASKS_TBNAME;
    $people_table      = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $fTableResult      = [];
    if (!empty($_REQUEST['search_eventid'] )) {
        $search_eventid    = intval( $_REQUEST['search_eventid'] );
        $search_name       = "";
        $search_scope      = "";
        $search_event      = "";
        $search_person     = "";
        $search_start_date = "";
        $search_end_date   = "";
        $search_status     = isset( $_REQUEST['search_signup_status'] ) ? intval( $_REQUEST['search_signup_status'] ) : -1;
    } else {
        $search_eventid    = 0;
        $search_name       = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';
        $search_scope      = isset( $_POST['search_scope'] ) ? esc_sql( eme_sanitize_request( $_POST['search_scope'] ) ) : 'future';
        $search_event      = isset( $_POST['search_event'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_event'] ) ) ) : '';
        $search_person     = isset( $_POST['search_person'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_person'] ) ) ) : '';
        $search_start_date = isset( $_POST['search_start_date'] ) && eme_is_date( $_POST['search_start_date'] ) ? esc_sql( $_POST['search_start_date'] ) : '';
        $search_end_date   = isset( $_POST['search_end_date'] ) && eme_is_date( $_POST['search_end_date'] ) ? esc_sql( $_POST['search_end_date'] ) : '';
        $search_status     = isset( $_POST['search_signup_status'] ) ? intval( $_POST['search_signup_status'] ) : -1;
    }

    $where     = '';
    $where_arr = [];
    if ( $search_status >= 0 ) {
        $where_arr[] = "signups.signup_status = $search_status";
    }
    if ( ! empty( $search_name ) ) {
        $where_arr[] = "tasks.name like '%" . $search_name . "%'";
    }
    if ( ! empty( $search_eventid ) ) {
        $where_arr[] = "events.event_id = $search_eventid";
    } elseif ( ! empty( $search_event ) ) {
        $where_arr[] = "events.event_name like '%" . $search_event . "%'";
    }
    if ( ! empty( $search_person ) ) {
        $where_arr[] = "(people.lastname like '%" . $search_person . "%' OR people.firstname like '%" . $search_person . "%' OR people.email like '%" . $search_person . "%')";
    }

    if ( ! empty( $search_start_date ) && ! empty( $search_end_date ) ) {
        $where_arr[] = "events.event_start >= '$search_start_date'";
        $where_arr[] = "events.event_end <= '$search_end_date 23:59:59'";
    } elseif ( ! empty( $search_start_date ) ) {
        $where_arr[] = "events.event_start LIKE '$search_start_date%'";
    } elseif ( ! empty( $search_end_date ) ) {
        $where_arr[] = "events.event_end LIKE '$search_end_date%'";
    } elseif ( $search_scope == 'future' ) {
        $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
        $search_end_date  = $eme_date_obj_now->getDateTime();
        $where_arr[]      = "events.event_end >= '$search_end_date'";
    } elseif ( $search_scope == 'past' ) {
        $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
        $search_end_date  = $eme_date_obj_now->getDateTime();
        $where_arr[]      = "events.event_end <= '$search_end_date'";
    }

    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    $join  = "LEFT JOIN $events_table AS events ON signups.event_id=events.event_id ";
    $join .= "LEFT JOIN $tasks_table AS tasks ON signups.task_id=tasks.task_id ";
    $join .= "LEFT JOIN $people_table AS people ON signups.person_id=people.person_id ";

    if ( current_user_can( get_option( 'eme_cap_manage_task_signups' ) ) ) {
        $formfields  = eme_get_formfields( '', 'generic,events,tasksignup' ); 
        $sql         = "SELECT COUNT(*) FROM $signups_table AS signups $join $where";
        $recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
        $orderby     = eme_get_datatables_orderby() ?: 'ORDER BY task_start ASC, task_end ASC, task_seq ASC';
        $sql         = "SELECT signups.*, events.event_id,events.event_name, events.event_start, events.event_end, people.person_id,people.lastname, people.firstname, people.email, tasks.name AS task_name, task_start, task_end FROM $signups_table AS signups $join $where $orderby $limit";
        $rows        = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $rows as $key => $row ) {
            $answers  = eme_get_tasksignup_answers( $row['id'] );
            $localized_start_date        = eme_localized_date( $row['event_start'], EME_TIMEZONE, 1 );
            $localized_end_date          = eme_localized_date( $row['event_end'], EME_TIMEZONE, 1 );
            $localized_taskstart_date    = eme_localized_datetime( $row['task_start'], EME_TIMEZONE, 1 );
            $localized_taskend_date      = eme_localized_datetime( $row['task_end'], EME_TIMEZONE, 1 );
            $localized_signup_date       = eme_localized_datetime( $row['signup_date'], EME_TIMEZONE, 1 );
            $row['event_name']  = "<strong><a href='" . admin_url( 'admin.php?page=eme-manager&amp;eme_admin_action=edit_event&amp;event_id=' . $row['event_id'] ) . "' title='" . __( 'Edit event', 'events-made-easy' ) . "'>" . eme_trans_esc_html( $row['event_name'] ) . '</a></strong><br>' . $localized_start_date . ' - ' . $localized_end_date;
            $csv_address = admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=tasksignups_csv&amp;event_id=' . $row['event_id'] );
            $row['event_name'] .= " (<a id='tasksignups_csv_" . $row['event_id'] . "' href='$csv_address'>" . __( 'CSV export', 'events-made-easy' ) . '</a>)';
            $row['task_name']   = eme_esc_html( $row['task_name'] );
            $row['comment']     = nl2br(eme_esc_html( $row['comment'] ));
            $row['task_start']  = $localized_taskstart_date;
            $row['task_end']    = $localized_taskend_date;
            $row['signup_date'] = $localized_signup_date;
            if ( $row['signup_status'] == 1 ) {
                $row['signup_status'] = __('Approved', 'events-made-easy');
            } else {
                $row['signup_status'] = __('Pending', 'events-made-easy');
            }
            $row['person_info'] = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $row['person_id'] ) . "' title='" . __( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( eme_format_full_name( $row['firstname'], $row['lastname'], $row['email'] ) ) . '</a>' . ' (' . eme_esc_html( $row['email'] ) . ')';

            foreach ( $formfields as $formfield ) {
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $formfield['field_id'] && $answer['answer'] != '' ) {
                        $val = eme_answer2readable( $answer['answer'], $formfield, 1, ',', 'text', 1 );
                        // the 'FIELD_' value is used by the container-js
                        $answerkey = 'FIELD_' . $answer['field_id'];
                        if ( isset( $row[ $answerkey ] ) ) {
                            $row[ $answerkey ] .= "<br>$val";
                        } else {
                            $row[ $answerkey ] = $val;
                        }
                    }
                }
            }
            $rows[$key] = $row;
        }

        $fTableResult['Result']           = 'OK';
        $fTableResult['Records']          = $rows;
        $fTableResult['TotalRecordCount'] = $recordCount;
    } else {
        $fTableResult['Result']  = 'Error';
        $fTableResult['Message'] = __( 'Access denied!', 'events-made-easy' );
    }
    print wp_json_encode( $fTableResult );
    wp_die();
}

function eme_ajax_manage_task_signups() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    if ( ! current_user_can( get_option( 'eme_cap_manage_task_signups' ) ) ) {
        $ajaxResult            = [];
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }

    if ( isset( $_REQUEST['do_action'] ) ) {
        $ids_arr   = ( isset( $_REQUEST['id'] ) ) ? explode( ',', eme_sanitize_request($_POST['id']) ) : [];
        $do_action = eme_sanitize_request( $_REQUEST['do_action'] );
        $send_mail = ( isset( $_REQUEST['send_mail'] ) ) ? intval( $_REQUEST['send_mail'] ) : 1;
        switch ( $do_action ) {
        case 'sendReminders':
            eme_ajax_action_send_reminders( $ids_arr );
            break;
        case 'approveTaskSignups':
            eme_ajax_action_signup_approve( $ids_arr, $send_mail );
            break;
        case 'deleteTaskSignups':
            eme_ajax_action_signup_delete( $ids_arr, $send_mail );
            break;
        }
    }
    wp_die();
}

function eme_ajax_action_send_reminders( $ids_arr ) {
    $action_ok = 1;
    foreach ( $ids_arr as $signup_id ) {
        $signup = eme_get_task_signup( $signup_id );
        $res = eme_email_tasksignup_action( $signup, 'reminder' );
        if ( ! $res )
            $action_ok = 0;
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

function eme_ajax_action_signup_approve( $ids_arr, $send_mail=1 ) {
    $action_ok = 1;
    foreach ( $ids_arr as $signup_id ) {
        $signup = eme_get_task_signup( $signup_id );
        $res    = eme_approve_task_signup( $signup_id );
        if ( ! $res ) {
            $action_ok = 0;
        } else {
            $event = eme_get_event($signup['event_id']);
            eme_add_persongroups( $signup['person_id'], $event['event_properties']['task_addpersontogroup'] );
            if ($send_mail) {
                eme_email_tasksignup_action( $signup, 'new' );
            }
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

function eme_ajax_action_signup_delete( $ids_arr, $send_mail=1 ) {
    $action_ok = 1;
    foreach ( $ids_arr as $signup_id ) {
        $signup = eme_get_task_signup( $signup_id );
        $res    = eme_db_delete_task_signup( $signup_id );
        if ( ! $res ) {
            $action_ok = 0;
        } else {
            if ($send_mail) {
                eme_email_tasksignup_action( $signup, 'delete' );
            }
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

function eme_count_pending_tasksignups() {
    global $wpdb;
    $events_table     = EME_DB_PREFIX . EME_EVENTS_TBNAME;
    $signups_table    = EME_DB_PREFIX . EME_TASK_SIGNUPS_TBNAME;
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $now              = $eme_date_obj_now->getDateTime();
    $sql              = $wpdb->prepare( "SELECT COUNT(signups.id) FROM $signups_table AS signups LEFT JOIN $events_table AS events ON signups.event_id=events.event_id WHERE signups.signup_status=0 AND events.event_end >= %s", $now );
    return $wpdb->get_var( $sql );
}

?>
