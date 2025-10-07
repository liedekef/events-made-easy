<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_recurrence() {
	$recurrence = [
		'recurrence_start_date'    => '',
		'recurrence_end_date'      => '',
		'recurrence_interval'      => 1,
		'recurrence_freq'          => '',
		'recurrence_byday'         => '',
		'recurrence_byweekno'      => '',
		'specific_months'          => '',
		'specific_days'            => '',
		'event_duration'           => 1,
		'holidays_id'              => 0,
		'exclude_days'             => '',
	];
	return $recurrence;
}

function eme_get_recurrence( $recurrence_id ) {
	global $wpdb;
	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;
	$sql              = $wpdb->prepare( "SELECT * FROM $recurrence_table WHERE recurrence_id = %d", $recurrence_id );
	$recurrence       = $wpdb->get_row( $sql, ARRAY_A );
	return $recurrence;
}

function eme_get_perpetual_recurrences() {
	global $wpdb;
	$res = [];
	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;
	$sql              = "SELECT * FROM $recurrence_table WHERE recurrence_freq != 'specific'";
	$recurrences      = $wpdb->get_results( $sql, ARRAY_A );
	foreach ($recurrences as $recurrence) {
		if (eme_is_empty_date($recurrence['recurrence_end_date'])) {
			$res[] = $recurrence;
		}
	}
	return $res;
}

function eme_get_recurrence_days( $recurrence ) {
	$matching_days = [];

	if ( $recurrence['recurrence_freq'] == 'specific' ) {
		$matching_days = explode( ',', $recurrence['specific_days'] );
		sort( $matching_days );
		return $matching_days;
	}

	$start_date_obj     = new emeExpressiveDate( $recurrence['recurrence_start_date'], EME_TIMEZONE );
	// we'll need to compare to the beginning of today
	$eme_date_obj_today = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$eme_date_obj_today->startOfDay();

	if (eme_is_empty_date($recurrence['recurrence_end_date'])) {
		// end date empty, so take the start date, add eleven years to it to make sure
		// and indicate we just want the next 10 occurences
		$end_date_obj     = $start_date_obj->copy()->addYears(11);
		$only_the_next_10 = 1;
	} else {
		$end_date_obj     = new emeExpressiveDate( $recurrence['recurrence_end_date'], EME_TIMEZONE );
		$only_the_next_10 = 0;
	}

	$excluded_days = [];
	if ( isset( $recurrence['holidays_id'] ) && $recurrence['holidays_id'] > 0 ) {
		$excluded_days = eme_get_holiday_listinfo( $recurrence['holidays_id'] );
	}
	if (!empty($recurrence['exclude_days'])) {
		$extra_excluded_days = explode( ',', $recurrence['exclude_days'] );
		foreach ($extra_excluded_days as $excluded_day) {
			$excluded_days[$excluded_day] = 1;
		}	
	}	

	$last_week_start  = [ 25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25 ];
	if (empty($recurrence['recurrence_byday'])) {
		$choosen_weekdays = [];
	} else {
		$choosen_weekdays = explode( ',', $recurrence['recurrence_byday'] );
	}
	if (empty($recurrence['specific_months'])) {
		$choosen_months = [];
	} else {
		$choosen_months = explode( ',', $recurrence['specific_months'] );
	}

	$daycounter       = 0;
	$weekcounter      = 0;
	$monthcounter     = 0;
	$start_monthday   = $start_date_obj->format( 'j' );
	$cycle_date_obj   = $start_date_obj->copy();
	$occurence_counter = 0;

	while ( $cycle_date_obj <= $end_date_obj && $occurence_counter<10 ) {
		$ymd = $cycle_date_obj->getDate();

		// skip excluded_days
		if ( ! empty( $excluded_days ) && isset( $excluded_days[ $ymd ] ) ) {
			$cycle_date_obj->addOneDay();
			++$daycounter;
			if ( $daycounter % 7 == 0 ) {
				++$weekcounter;
			}
			if ( $cycle_date_obj->format( 'j' ) == 1 ) {
				++$monthcounter;
			}
			continue;
		}

		if ( empty( $recurrence['recurrence_interval'] ) ) {
			$recurrence['recurrence_interval'] = 1;
		}

		if ( $recurrence['recurrence_freq'] == 'daily' ) {
			if ( $daycounter % $recurrence['recurrence_interval'] == 0 ) {
				if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
					$matching_days[0] = $ymd;
				} else {
					$matching_days[] = $ymd;
					if ( $only_the_next_10 == 1 ) $occurence_counter++;
				}
			}
		}

		if ( $recurrence['recurrence_freq'] == 'weekly' ) {
			if ( $weekcounter % $recurrence['recurrence_interval'] == 0 ) {
				if ( ! $recurrence['recurrence_byday'] && eme_N_weekday( $cycle_date_obj ) == eme_N_weekday( $start_date_obj ) ) {
					if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
						$matching_days[0] = $ymd;
					} else {
						$matching_days[] = $ymd;
						if ( $only_the_next_10 == 1 ) $occurence_counter++;
					}
				} elseif ( in_array( eme_N_weekday( $cycle_date_obj ), $choosen_weekdays ) ) {
					// specific days, so we only check for those days
					if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
						$matching_days[0] = $ymd;
					} else {
						$matching_days[] = $ymd;
						if ( $only_the_next_10 == 1 ) $occurence_counter++;
					}
				}
			}
		}

		if ( $recurrence['recurrence_freq'] == 'monthly' ) {
			$monthday = $cycle_date_obj->format( 'j' );
			$month    = $cycle_date_obj->format( 'n' );
			if ( $monthcounter % $recurrence['recurrence_interval'] == 0 ) {
				// if recurrence_byweekno=0 ==> means to use the startday as repeating day
				if ( $recurrence['recurrence_byweekno'] == 0 ) {
					if ( $monthday == $start_monthday ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					}
				} elseif ( in_array( eme_N_weekday( $cycle_date_obj ), $choosen_weekdays ) ) {
					$monthweek = floor( ( ( $cycle_date_obj->format( 'd' ) - 1 ) / 7 ) ) + 1;
					if ( ( $recurrence['recurrence_byweekno'] == -1 ) && ( $monthday >= $last_week_start[ $month - 1 ] ) ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					} elseif ( $recurrence['recurrence_byweekno'] == $monthweek ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					}
				}
			}
		}
		if ( $recurrence['recurrence_freq'] == 'specific_months' ) {
			$monthday = $cycle_date_obj->format( 'j' );
			$month    = $cycle_date_obj->format( 'n' );
			if ( in_array( $month, $choosen_months ) ) {
				// if recurrence_byweekno=0 ==> means to use the startday as repeating day
				if ( $recurrence['recurrence_byweekno'] == 0 ) {
					if ( $monthday == $start_monthday ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					}
				} elseif ( in_array( eme_N_weekday( $cycle_date_obj ), $choosen_weekdays ) ) {
					$monthweek = floor( ( ( $cycle_date_obj->format( 'd' ) - 1 ) / 7 ) ) + 1;
					if ( ( $recurrence['recurrence_byweekno'] == -1 ) && ( $monthday >= $last_week_start[ $month - 1 ] ) ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					} elseif ( $recurrence['recurrence_byweekno'] == $monthweek ) {
						if ( $only_the_next_10 == 1 && $cycle_date_obj < $eme_date_obj_today ) {
							$matching_days[0] = $ymd;
						} else {
							$matching_days[] = $ymd;
							if ( $only_the_next_10 == 1 ) $occurence_counter++;
						}
					}
				}
			}
		}
		$cycle_date_obj->addOneDay();
		++$daycounter;
		if ( $daycounter % 7 == 0 ) {
			++$weekcounter;
		}
		if ( $cycle_date_obj->format( 'j' ) == 1 ) {
			++$monthcounter;
		}
	}

	sort( $matching_days );
	return $matching_days;
}

// backwards compatible: eme_insert_recurrent_event renamed to eme_db_insert_recurrence
function eme_insert_recurrent_event( $event, $recurrence ) {
	return eme_db_insert_recurrence( $recurrence, $event );
}

function eme_db_insert_recurrence( $recurrence, $event ) {
	global $wpdb;
	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;

	// never try to update a autoincrement value ...
	if ( isset( $recurrence['recurrence_id'] ) ) {
		unset( $recurrence['recurrence_id'] );
	}
    if (eme_is_empty_date($recurrence['recurrence_end_date'])) {
        $recurrence['recurrence_end_date'] = null;
    }

	// some sanity checks
	$recurrence['recurrence_interval'] = intval( $recurrence['recurrence_interval'] );
	// if the end date is set, it should be a sensible end date
	if ( $recurrence['recurrence_freq'] != 'specific' ) {
        if ( eme_is_empty_date($recurrence['recurrence_end_date']) ) {
			// if the end date is empty, we set the start date to the first occurence
			// (which is 1 event in the past), this allows for cleanup
			$matching_days = eme_get_recurrence_days( $recurrence );
			$recurrence['recurrence_start_date'] = $matching_days[0];
		} else {
			$eme_date_obj1 = new emeExpressiveDate( $recurrence['recurrence_start_date'], EME_TIMEZONE );
			$eme_date_obj2 = new emeExpressiveDate( $recurrence['recurrence_end_date'], EME_TIMEZONE );
			if ( $eme_date_obj2 < $eme_date_obj1 ) {
				$recurrence['recurrence_end_date'] = $recurrence['recurrence_start_date'];
			}
		}
	} else {
		if (empty($recurrence['specific_days'])) {
			return 0;
		}
		// get the recurrence start days
		// this also ordens the days by date, so we'll update the recurrence with that too
		$matching_days = eme_get_recurrence_days( $recurrence );
		$recurrence['specific_days'] = join( ',', $matching_days );
		$recurrence['recurrence_start_date'] = $matching_days[0];
		// find the last matching day too
		$last_day     = $matching_days[0];
		foreach ( $matching_days as $day ) {
			if ( $day > $last_day ) {
				$last_day = $day;
			}
		}
		$recurrence['recurrence_end_date'] = $last_day;
	}
	$recurrence['event_duration'] = intval( $recurrence['event_duration'] );

	$wpdb->insert( $recurrence_table, $recurrence );
	$recurrence_id = $wpdb->insert_id;

	$recurrence['recurrence_id'] = $recurrence_id;
	$event['recurrence_id']      = $recurrence['recurrence_id'];
	$count                       = eme_insert_events_for_recurrence( $recurrence, $event );
	if ( $count ) {
		if ( has_action( 'eme_insert_recurrence_action' ) ) {
			do_action( 'eme_insert_recurrence_action', $event, $recurrence );
		}
		return $recurrence_id;
	} else {
		eme_db_delete_recurrence( $recurrence_id );
		return 0;
	}
}

function eme_insert_events_for_recurrence( $recurrence, $event ) {
	$matching_days = eme_get_recurrence_days( $recurrence );
	$count = 0;
	// in order to take tasks into account for recurring events, we need to know the difference in days between the events
	$eme_date_obj_now  = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$eme_date_obj_orig = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
	$event_start_time  = eme_get_time_from_dt( $event['event_start'] );
	$event_end_time    = eme_get_time_from_dt( $event['event_end'] );
	foreach ( $matching_days as $day ) {
		$event['event_start'] = "$day $event_start_time";
		$eme_date_obj         = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
		// no past events will be created, unless for specific days
		if ( $recurrence['recurrence_freq'] != 'specific' && $eme_date_obj < $eme_date_obj_now ) {
                        continue;
		}
		$day_difference       = $eme_date_obj_orig->getDifferenceInDays( $eme_date_obj );
		$eme_date_obj->addDays( $recurrence['event_duration'] - 1 );
		$event_end_date     = $eme_date_obj->getDate();
		$event['event_end'] = "$event_end_date $event_end_time";
		$event_id           = eme_db_insert_event( $event, 1, $day_difference );
		if ( $event_id ) {
			eme_event_store_answers( $event_id );
		}
		++$count;
	}
	return $count;
}

// backwards compatible: eme_update_recurrence renamed to eme_db_update_recurrence
function eme_update_recurrence( $event, $recurrence ) {
	return eme_db_update_recurrence( $recurrence, $event );
}

function eme_db_update_recurrence( $recurrence, $event, $only_change_recdates = 0 ) {
	global $wpdb;
	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;

    if (eme_is_empty_date($recurrence['recurrence_end_date'])) {
        $recurrence['recurrence_end_date'] = null;
    }
	// some sanity checks
	// if the end date is set, it should be a sensible end date
	if ( $recurrence['recurrence_freq'] != 'specific' ) {
	       	if ( eme_is_empty_date( $recurrence['recurrence_end_date'] ) ) {
			// if the end date is empty, we set the start date to the first occurence
			// (which is 1 event in the past), this allows for cleanup
			$matching_days = eme_get_recurrence_days( $recurrence );
			$recurrence['recurrence_start_date'] = $matching_days[0];
		} else {
			$eme_date_obj1 = new emeExpressiveDate( $recurrence['recurrence_start_date'], EME_TIMEZONE );
			$eme_date_obj2 = new emeExpressiveDate( $recurrence['recurrence_end_date'], EME_TIMEZONE );
			if ( $eme_date_obj2 < $eme_date_obj1 ) {
				$recurrence['recurrence_end_date'] = $recurrence['recurrence_start_date'];
			}
		}
	} else {
		if ( empty( $recurrence['specific_days'] ) ) {
			return 0;
		}
		// get the recurrence start days
		// this also ordens the days by date, so we'll update the recurrence with that too
		$matching_days = eme_get_recurrence_days( $recurrence );
		$recurrence['specific_days'] = join( ',', $matching_days );
		$recurrence['recurrence_start_date'] = $matching_days[0];
		// find the last matching day too
		$last_day     = $matching_days[0];
		foreach ( $matching_days as $day ) {
			if ( $day > $last_day ) {
				$last_day = $day;
			}
		}
		$recurrence['recurrence_end_date'] = $last_day;
	}

	$where = [ 'recurrence_id' => $recurrence['recurrence_id'] ];
	$wpdb->update( $recurrence_table, $recurrence, $where );
	$event['recurrence_id'] = $recurrence['recurrence_id'];
	$count                  = eme_update_events_for_recurrence( $recurrence, $event, $only_change_recdates );
	if ( $count ) {
		if ( has_action( 'eme_update_recurrence_action' ) ) {
			do_action( 'eme_update_recurrence_action', $event, $recurrence );
		}
		return $count;
	} else {
		eme_db_delete_recurrence( $recurrence['recurrence_id'] );
		return 0;
	}
}

function eme_update_events_for_recurrence( $recurrence, $event, $only_change_recdates = 0 ) {
	global $wpdb;
	$events_table  = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$matching_days = eme_get_recurrence_days( $recurrence );

	// 2 steps for updating events for a recurrence:
	// First step: check the existing events and
	//       if they still match the recurrence days or they have existing bookings (future events only), update them
	// 	otherwise delete the old event
	// Reason for doing this: we want to keep possible booking data for a recurrent event as well
	// and just deleting all current events for a recurrence and inserting new ones would break the link
	// between booking id and event id
	// Second step: check all days of the recurrence and if no event exists yet, insert it
	$sql    = $wpdb->prepare( "SELECT event_id,event_start FROM $events_table WHERE recurrence_id = %d AND event_status <> %d", $recurrence['recurrence_id'], EME_EVENT_STATUS_TRASH );
	$events = $wpdb->get_results( $sql, ARRAY_A );

	// in order to take tasks into account for recurring events, we need to know the difference in days between the events
	$eme_date_obj_now  = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$eme_date_obj_orig = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
	$event_start_time  = eme_get_time_from_dt( $event['event_start'] );
	$event_end_time    = eme_get_time_from_dt( $event['event_end'] );
	// we'll return the number of events in the recurrence at the end
	$count = 0;
	// Doing step 1
	foreach ( $events as $existing_event ) {
		$day       = eme_get_date_from_dt( $existing_event['event_start'] );
		$array_key = array_search( $day, $matching_days );
		$existing_event_start_obj = new emeExpressiveDate( $existing_event['event_start'], EME_TIMEZONE );
		// if future events in the recurrence have bookings, we won't delete those but keep them in the recurrence series
		if ( $existing_event_start_obj >= $eme_date_obj_now ) {
			$bookings_count = eme_count_bookings_for( $existing_event['event_id'] );
		} else {
			$bookings_count = 0;
		}
		if ( $array_key !== false || $bookings_count > 0 ) {
			if ( ! $only_change_recdates ) {
				$event['event_start'] = "$day $event_start_time";
				$eme_date_obj         = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
				$day_difference       = $eme_date_obj_orig->getDifferenceInDays( $eme_date_obj );
				$eme_date_obj->addDays( $recurrence['event_duration'] - 1 );
				$event_end_date     = $eme_date_obj->getDate();
				$event['event_end'] = "$event_end_date $event_end_time";
				$res                = eme_db_update_event( $event, $existing_event['event_id'], 1, $day_difference );
				if ( $res ) {
					eme_event_store_answers( $existing_event['event_id'] );
				}
			}
			// we handled a specific day, so remove it from the array
			// in step 2 we count on the fact that $matching_days only contains days not existing
			unset( $matching_days[ $array_key ] );
			++$count;
		} else {
			eme_db_delete_event( $existing_event['event_id'], 1 );
		}
	}
	// Doing step 2
	foreach ( $matching_days as $day ) {
		$event['event_start'] = "$day $event_start_time";
		$eme_date_obj         = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
		// no past events will be created
		if ($eme_date_obj < $eme_date_obj_now) {
			continue;
		}
		$day_difference       = $eme_date_obj_orig->getDifferenceInDays( $eme_date_obj );
		$eme_date_obj->addDays( $recurrence['event_duration'] - 1 );
		$event_end_date     = $eme_date_obj->getDate();
		$event['event_end'] = "$event_end_date $event_end_time";
		++$count;
		$event_id = eme_db_insert_event( $event, 1, $day_difference );
		if ( $event_id ) {
			eme_event_store_answers( $event_id );
		}
	}
	return $count;
}

function eme_db_delete_recurrence( $recurrence_id ) {
	global $wpdb;
	if ( has_action( 'eme_delete_recurrence_action' ) ) {
		$recurrence = eme_get_recurrence( $recurrence_id );
		$event      = eme_get_event( eme_get_recurrence_first_eventid( $recurrence_id ) );
		if ( ! empty( $event ) ) {
			do_action( 'eme_delete_recurrence_action', $event, $recurrence );
		}
	}

	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;
	$sql              = $wpdb->prepare( "DELETE FROM $recurrence_table WHERE recurrence_id = %d", $recurrence_id );
	$wpdb->query( $sql );
	eme_trash_events_for_recurrence_id( $recurrence_id );
	return true;
}

function eme_trash_events_for_recurrence_id( $recurrence_id ) {
	$ids_arr = eme_get_recurrence_eventids( $recurrence_id );
	if ( ! empty( $ids_arr ) ) {
		$ids = join( ',', $ids_arr );
		eme_trash_events( $ids );
	}
}

function eme_get_recurrence_first_eventid( $recurrence_id ) {
	global $wpdb;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$sql          = $wpdb->prepare( "SELECT event_id FROM $events_table WHERE recurrence_id = %d AND event_status !=%d ORDER BY event_start ASC LIMIT 1", $recurrence_id, EME_EVENT_STATUS_TRASH );
	return $wpdb->get_var( $sql );
}

function eme_get_recurrence_eventids( $recurrence_id, $future_only = 0 ) {
	global $wpdb;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	if ( $future_only ) {
		$eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$today        = $eme_date_obj->format( 'Y-m-d' );
		$sql          = $wpdb->prepare( "SELECT event_id FROM $events_table WHERE recurrence_id = %d AND event_start > %s ORDER BY event_start ASC", $recurrence_id, $today );
	} else {
		$sql = $wpdb->prepare( "SELECT event_id FROM $events_table WHERE recurrence_id = %d ORDER BY event_start ASC", $recurrence_id );
	}
	return $wpdb->get_col( $sql );
}

function eme_get_recurrence_desc( $recurrence_id ) {
	global $wp_locale;
	$recurrence = eme_get_recurrence( $recurrence_id );
	if ( empty( $recurrence ) ) {
		return;
	}

	$weekdays_name  = [ __( 'Monday' ), __( 'Tuesday' ), __( 'Wednesday' ), __( 'Thursday' ), __( 'Friday' ), __( 'Saturday' ), __( 'Sunday' ) ];
	$monthweek_name = [
		'1'  => __( 'the first %s of the month', 'events-made-easy' ),
		'2'  => __( 'the second %s of the month', 'events-made-easy' ),
		'3'  => __( 'the third %s of the month', 'events-made-easy' ),
		'4'  => __( 'the fourth %s of the month', 'events-made-easy' ),
		'5'  => __( 'the fifth %s of the month', 'events-made-easy' ),
		'-1' => __( 'the last %s of the month', 'events-made-easy' ),
	];
	if (eme_is_empty_date($recurrence['recurrence_end_date'])) {
		$output         = sprintf( __( 'From %s onwards (automatically extended)', 'events-made-easy' ), eme_localized_date( $recurrence['recurrence_start_date'], EME_TIMEZONE ) ) . ', ';
	} else {
		$output         = sprintf( __( 'From %s to %s', 'events-made-easy' ), eme_localized_date( $recurrence['recurrence_start_date'], EME_TIMEZONE ), eme_localized_date( $recurrence['recurrence_end_date'], EME_TIMEZONE ) ) . ', ';
	}
	if ( $recurrence['recurrence_freq'] == 'daily' ) {
		$freq_desc = __( 'everyday', 'events-made-easy' );
		if ( $recurrence['recurrence_interval'] > 1 ) {
			$freq_desc = sprintf( __( 'every %s days', 'events-made-easy' ), $recurrence['recurrence_interval'] );
		}
	} elseif ( $recurrence['recurrence_freq'] == 'weekly' ) {
		if ( ! $recurrence['recurrence_byday'] ) {
			# no weekdays given for the recurrence, so we use the
			# day of the week of the startdate as reference
			$recurrence['recurrence_byday'] = eme_localized_date( $recurrence['recurrence_start_date'], EME_TIMEZONE, 'w' );
			# Sunday is 7, not 0
			if ( $recurrence['recurrence_byday'] == 0 ) {
				$recurrence['recurrence_byday'] = 7;
			}
		}
		$weekday_array = explode( ',', $recurrence['recurrence_byday'] );
		$natural_days  = [];
		foreach ( $weekday_array as $day ) {
			$natural_days[] = $weekdays_name[ $day - 1 ];
		}
		$and_string = __( ' and ', 'events-made-easy' );
		$output    .= implode( $and_string, $natural_days );
		$freq_desc  = ', ' . __( 'every week', 'events-made-easy' );
		if ( $recurrence['recurrence_interval'] > 1 ) {
			$freq_desc = ', ' . sprintf( __( 'every %s weeks', 'events-made-easy' ), $recurrence['recurrence_interval'] );
		}
	} elseif ( $recurrence['recurrence_freq'] == 'monthly' ) {
		if ( ! $recurrence['recurrence_byday'] ) {
			# no monthday given for the recurrence, so we use the
			# day of the month of the startdate as reference
			$recurrence['recurrence_byday'] = eme_localized_date( $recurrence['recurrence_start_date'], EME_TIMEZONE, 'e' );
		}
		$weekday_array = explode( ',', $recurrence['recurrence_byday'] );
		$natural_days  = [];
		foreach ( $weekday_array as $day ) {
			$natural_days[] = $weekdays_name[ $day - 1 ];
		}
		$and_string = __( ' and ', 'events-made-easy' );
		$freq_desc  = sprintf( ( $monthweek_name[ $recurrence['recurrence_byweekno'] ] ), implode( $and_string, $natural_days ) );
		if ( $recurrence['recurrence_interval'] > 1 ) {
			$freq_desc .= ', ' . sprintf( __( 'every %s months', 'events-made-easy' ), $recurrence['recurrence_interval'] );
		} else {
			$freq_desc .= ', ' . __( 'every month', 'events-made-easy' );
		}
	} elseif ( $recurrence['recurrence_freq'] == 'specific_months' ) {
		if ( ! $recurrence['recurrence_byday'] ) {
			# no monthday given for the recurrence, so we use the
			# day of the month of the startdate as reference
			$recurrence['recurrence_byday'] = eme_localized_date( $recurrence['recurrence_start_date'], EME_TIMEZONE, 'e' );
		}
		$weekday_array = explode( ',', $recurrence['recurrence_byday'] );
		$natural_days  = [];
		foreach ( $weekday_array as $day ) {
			$natural_days[] = $weekdays_name[ $day - 1 ];
		}
		$and_string = __( ' and ', 'events-made-easy' );
		$freq_desc  = sprintf( ( $monthweek_name[ $recurrence['recurrence_byweekno'] ] ), implode( $and_string, $natural_days ) );
		$choosen_months = explode( ',', $recurrence['specific_months'] );
		foreach ($choosen_months as $month_no) {
			$freq_desc .= ', ' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $month_no ) );
		}
	} elseif ( $recurrence['recurrence_freq'] == 'specific' ) {
		$specific_days    = eme_get_recurrence_days( $recurrence );
		$natural_days     = [];
		$eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
		foreach ( $specific_days as $day ) {
			$date_obj = new emeExpressiveDate( $day, EME_TIMEZONE );
			if ( $date_obj < $eme_date_obj_now ) {
				$natural_days[] = '<s>' . eme_localized_date( $day, EME_TIMEZONE ) . '</s>';
			} else {
				$natural_days[] = eme_localized_date( $day, EME_TIMEZONE );
			}
		}
		if ( eme_is_admin_request() ) {
			//return __("Specific days",'events-made-easy');
			return implode( '<br>', $natural_days );
		} else {
			return implode( ', ', $natural_days );
		}
	} else {
		$freq_desc = '';
	}
	$output .= $freq_desc;
	return $output;
}

function eme_recurrence_count( $recurrence_id ) {
	# return the number of events for an recurrence
	global $wpdb;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$sql          = $wpdb->prepare( "SELECT COUNT(*) FROM $events_table WHERE recurrence_id = %d", $recurrence_id );
	return $wpdb->get_var( $sql );
}

add_action( 'wp_ajax_eme_recurrences_list', 'eme_ajax_recurrences_list' );
add_action( 'wp_ajax_eme_manage_recurrences', 'eme_ajax_manage_recurrences' );

function eme_ajax_recurrences_list() {
	global $wpdb;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
	if ( ! current_user_can( get_option( 'eme_cap_list_events' ) ) ) {
		$ajaxResult                = [];
			$ajaxResult['Result']  = 'Error';
			$ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $ajaxResult );
			wp_die();
	}

	$eme_date_obj       = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$today              = $eme_date_obj->getDate();
	$event_status_array = eme_status_array();

    $limit             = eme_get_datatables_limit();
	$orderby           = eme_get_datatables_orderby();
	$scope             = ( isset( $_POST['scope'] ) ) ? esc_sql( eme_sanitize_request( $_POST['scope'] ) ) : 'ongoing';
	$search_name       = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';
	$search_start_date = isset( $_POST['search_start_date'] ) && eme_is_date( $_POST['search_start_date'] ) ? esc_sql( eme_sanitize_request($_POST['search_start_date']) ) : '';
	$search_end_date   = isset( $_POST['search_end_date'] ) && eme_is_date( $_POST['search_end_date'] ) ? esc_sql( eme_sanitize_request($_POST['search_end_date'])) : '';

	$where     = '';
	$where_arr = [];
	if ( ! empty( $search_name ) ) {
		$where_arr[] = "event_name like '%" . $search_name . "%'";
	}
	if ( ! empty( $search_start_date ) && ! empty( $search_end_date ) ) {
		$where_arr[] = "recurrence_start_date >= '$search_start_date'";
		$where_arr[] = "(recurrence_end_date <= '$search_end_date' OR recurrence_end_date IS NULL)";
	} elseif ( ! empty( $search_start_date ) ) {
		$where_arr[] = "recurrence_start_date = '$search_start_date'";
	} elseif ( ! empty( $search_end_date ) ) {
		$where_arr[] = "recurrence_end_date = '$search_end_date'";
	} elseif ( ! empty( $scope ) ) {
		if ( $scope == 'ongoing' ) {
			$where_arr[] = "(recurrence_end_date >= '$today' OR recurrence_end_date IS NULL)";
		} elseif ( $scope == 'past' ) {
			$where_arr[] = "recurrence_end_date < '$today'";
		}
	}
	if ( $where_arr ) {
		$where = 'WHERE ' . implode( ' AND ', $where_arr );
	}

	$recurrence_table = EME_DB_PREFIX . EME_RECURRENCE_TBNAME;
	if ( ! empty( $search_name ) ) {
		$events_table      = EME_DB_PREFIX . EME_EVENTS_TBNAME;
		$count_sql         = "SELECT COUNT(recurrence_id) FROM $recurrence_table NATURAL JOIN ( SELECT * FROM $events_table WHERE recurrence_id >0 GROUP BY recurrence_id ) as event $where";
		$recurrences_count = $wpdb->get_var( $count_sql );
		$sql               = "SELECT * FROM $recurrence_table NATURAL JOIN ( SELECT * FROM $events_table WHERE recurrence_id >0 GROUP BY recurrence_id ) as event $where $orderby $limit";
	} else {
		$count_sql         = "SELECT COUNT(recurrence_id) FROM $recurrence_table $where";
		$recurrences_count = $wpdb->get_var( $count_sql );
		$sql               = "SELECT * FROM $recurrence_table $where $orderby $limit";
	}
	$recurrences = $wpdb->get_results( $sql, ARRAY_A );

	$rows = [];
	foreach ( $recurrences as $recurrence ) {
		// due to our select with natural join, $recurrence contains everything for an event too (except eme_unserialized properties
		// for ease of code, we'll set $event=$recurrence and use $event where logical
		if ( empty( $search_name ) ) {
			$event = eme_get_event( eme_get_recurrence_first_eventid( $recurrence['recurrence_id'] ) );
		} else {
			$event = eme_get_extra_event_data( $recurrence );
		}
		// if no event info, continue
		if ( empty( $event ) ) {
			continue;
		}
		if ( ! $recurrence['event_duration'] ) {
			// older recurrences did not have event_duration
			$event_start_obj = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
			$event_end_obj   = new emeExpressiveDate( $event['event_end'], EME_TIMEZONE );
			$duration_days   = abs( $event_end_obj->getDifferenceInDays( $event_start_obj ) ) + 1;
		} else {
			$duration_days = $recurrence['event_duration'];
		}
		if ( $duration_days > 1 ) {
			$day_string = __( 'days', 'events-made-easy' );
		} else {
			$day_string = __( 'day', 'events-made-easy' );
		}
		if ( $event['event_properties']['all_day'] ) {
			$duration_string = sprintf( '%d %s', $duration_days, $day_string );
		} else {
			$duration_string = sprintf( '%d %s, %s-%s', $duration_days, $day_string, eme_localized_time( $event['event_start'], EME_TIMEZONE ), eme_localized_time( $event['event_end'], EME_TIMEZONE ) );
		}

		$record                  = [];
		$record['recurrence_id'] = $recurrence['recurrence_id'];
		$record['event_name']    = "<strong><a href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-manager&amp;eme_admin_action=edit_recurrence&amp;recurrence_id=' . $recurrence['recurrence_id'] ), 'eme_admin', 'eme_admin_nonce' ) . "' title='" . __( 'Edit recurrence', 'events-made-easy' ) . "'>" . eme_trans_esc_html( $event['event_name'] ) . '</a></strong>';
        $copy_link='window.location.href="'.admin_url( 'admin.php?page=eme-manager&amp;eme_admin_action=duplicate_recurrence&amp;recurrence_id=' . $recurrence['recurrence_id'] ).'";';
        $record[ 'copy'] = "<button onclick='$copy_link' title='" . __( 'Duplicate this recurrence', 'events-made-easy' ) . "' class='ftable-command-button eme-copy-button'><span>copy</span></a>";
		if ( $event['event_rsvp'] ) {
			$total_seats = eme_get_total( $event['event_seats'] );
			if ( eme_is_multi( $event['event_seats'] ) ) {
				$total_seats_string = $total_seats . ' (' . $event['event_seats'] . ')';
			} else {
				$total_seats_string = $total_seats;
			}
			$record['event_name'] .= '<br>' . __( 'Max:', 'events-made-easy' ) . ' '. $total_seats_string;
			if ( empty( $event['price'] ) ) {
					$record['eventprice'] = __( 'Free', 'events-made-easy' );
			} else {
				$record['eventprice'] = eme_convert_multi2br( eme_localized_price( $event['price'], $event['currency'] ) );
			}
		} else {
			$record['eventprice'] = '';
		}

		if ( ! empty( $event['event_category_ids'] ) ) {
			$categories            = explode( ',', $event['event_category_ids'] );
			$record['event_name'] .= "<br><span class='eme_small' title='" . __( 'Category', 'events-made-easy' ) . "'>";
			$cat_names             = [];
			foreach ( $categories as $cat ) {
				$category = eme_get_category( $cat );
				if ( $category ) {
					$cat_names[] = eme_trans_esc_html( $category['category_name'] );
				}
			}
			$record['event_name'] .= implode( ', ', $cat_names );
			$record['event_name'] .= '</span>';
		}
		$location = eme_get_location( $event['location_id'] );
		if ( empty( $location ) ) {
			$location = eme_new_location();
		}
		if ( empty( $location['location_name'] ) ) {
				$record['location_name'] = '';
		} else {
				$record['location_name'] = "<a href='" . admin_url( 'admin.php?page=eme-locations&amp;eme_admin_action=edit_location&amp;location_id=' . $location['location_id'] ) . "' title='" . __( 'Edit location', 'events-made-easy' ) . "'>" . eme_trans_esc_html( $location['location_name'] ) . '</a>';
			if ( ! $location['location_latitude'] && ! $location['location_longitude'] && get_option( 'eme_map_is_active' ) && ! $event['location_properties']['online_only'] ) {
					$record['location_name'] .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . __( 'Location map coordinates are empty! Please edit the location to correct this, otherwise it will not show correctly on your website.', 'events-made-easy' ) . "'>";
			}
		}

		if ( ! empty( $location['location_address1'] ) || ! empty( $location['location_address2'] ) ) {
			$record['location_name'] .= '<br>' . eme_trans_esc_html( $location['location_address1'] ) . ' ' . eme_trans_esc_html( $location['location_address2'] );
		}
		if ( ! empty( $location['location_city'] ) || ! empty( $location['location_state'] ) || ! empty( $location['location_zip'] ) || ! empty( $location['location_country'] ) ) {
			$record['location_name'] .= '<br>' . eme_trans_esc_html( $location['location_city'] ) . ' ' . eme_trans_esc_html( $location['location_state'] ) . ' ' . eme_trans_esc_html( $location['location_zip'] ) . ' ' . eme_trans_esc_html( $location['location_country'] );
		}
		if ( ! $location['location_properties']['online_only'] && ! empty( $location['location_url'] ) ) {
			$record['location_name'] .= '<br>' . eme_trans_esc_html( $location['location_url'] );
		}

		if ( isset( $event_status_array[ $event['event_status'] ] ) ) {
			$record['event_status'] = $event_status_array[ $event['event_status'] ];
		}
		$record['recinfo']       = eme_get_recurrence_desc( $event['recurrence_id'] );
		$record['rec_singledur'] = $duration_string;
		$rows[]                  = $record;
	}

	$ajaxResult                     = [];
	$ajaxResult['Result']           = 'OK';
	$ajaxResult['Records']          = $rows;
	$ajaxResult['TotalRecordCount'] = $recurrences_count;
	print wp_json_encode( $ajaxResult );
	wp_die();
}

function eme_ajax_manage_recurrences() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	$ajaxResult = [];
	if ( isset( $_POST['do_action'] ) ) {
		$do_action          = eme_sanitize_request( $_POST['do_action'] );
		$rec_new_start_date = eme_sanitize_request( $_POST['rec_new_start_date'] );
		$rec_new_end_date   = eme_sanitize_request( $_POST['rec_new_end_date'] );
		$ids                = $_POST['recurrence_id'];
		$ids_arr            = explode( ',', $ids );
		if ( ! eme_is_numeric_array( $ids_arr ) || ! current_user_can( get_option( 'eme_cap_edit_events' ) ) ) {
			$ajaxResult['Result']  = 'Error';
			$ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $ajaxResult );
			wp_die();
		}

		switch ( $do_action ) {
			case 'deleteRecurrences':
				eme_ajax_action_recurrences_delete( $ids_arr );
				break;
			case 'publicRecurrences':
				eme_ajax_action_recurrences_status( $ids_arr, EME_EVENT_STATUS_PUBLIC );
				break;
			case 'privateRecurrences':
				eme_ajax_action_recurrences_status( $ids_arr, EME_EVENT_STATUS_PRIVATE );
				break;
			case 'draftRecurrences':
				eme_ajax_action_recurrences_status( $ids_arr, EME_EVENT_STATUS_DRAFT );
				break;
			case 'extendRecurrences':
				eme_ajax_action_recurrences_extend( $ids_arr, $rec_new_start_date, $rec_new_end_date );
				break;
		}
	} else {
		$ajaxResult['Result']  = 'Error';
		$ajaxResult['Message'] = __( 'No action defined!', 'events-made-easy' );
		print wp_json_encode( $ajaxResult );
	}
	wp_die();
}

function eme_ajax_action_recurrences_delete( $ids_arr ) {
	foreach ( $ids_arr as $recurrence_id ) {
		eme_db_delete_recurrence( $recurrence_id );
	}
	$ajaxResult            = [];
	$ajaxResult['Result']  = 'OK';
	$ajaxResult['Message'] = __( 'Recurrences deleted and events moved to trash', 'events-made-easy' );
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_recurrences_status( $ids_arr, $status ) {
	foreach ( $ids_arr as $recurrence_id ) {
		$events_ids = eme_get_recurrence_eventids( $recurrence_id );
		eme_change_event_status( $events_ids, $status );
	}
	$ajaxResult            = [];
	$ajaxResult['Result']  = 'OK';
	$ajaxResult['Message'] = __( 'Recurrences status updated', 'events-made-easy' );
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_recurrences_extend( $ids_arr, $rec_new_start_date, $rec_new_end_date ) {
	foreach ( $ids_arr as $recurrence_id ) {
		$recurrence = eme_get_recurrence( $recurrence_id );
		$event      = eme_get_event( eme_get_recurrence_first_eventid( $recurrence_id ) );
		if ( ! empty( $event ) ) {
			if ( ! eme_is_empty_date( $rec_new_start_date ) && ! eme_is_empty_date( $rec_new_end_date ) ) {
				$recurrence['recurrence_start_date'] = $rec_new_start_date;
				// we don't change end dates for perpetual recurrences here
				if ( ! eme_is_empty_date($recurrence['recurrence_end_date'] ) ) {
					$recurrence['recurrence_end_date']   = $rec_new_end_date;
				}
				// we add the 1 as param so eme_db_update_recurrence knows it is for changing dates only and can skip some things
				eme_db_update_recurrence( $recurrence, $event, 1 );
			} elseif ( ! eme_is_empty_date( $rec_new_start_date ) && eme_is_empty_date( $rec_new_end_date ) ) {
				$recurrence['recurrence_start_date'] = $rec_new_start_date;
				// we add the 1 as param so eme_db_update_recurrence knows it is for changing dates only and can skip some things
				eme_db_update_recurrence( $recurrence, $event, 1 );
			} elseif ( eme_is_empty_date( $rec_new_start_date ) && ! eme_is_empty_date( $rec_new_end_date ) && eme_is_empty_date($recurrence['recurrence_end_date'] ) ) {
				$recurrence['recurrence_end_date'] = $rec_new_end_date;
				// we add the 1 as param so eme_db_update_recurrence knows it is for changing dates only and can skip some things
				eme_db_update_recurrence( $recurrence, $event, 1 );
			}
			wp_cache_delete( 'eme_event ' . $event['event_id'] );
			unset( $recurrence );
			unset( $event );
		}
	}
	$ajaxResult            = [];
	$ajaxResult['Result']  = 'OK';
	$ajaxResult['Message'] = __( 'Start and end date adjusted for the selected recurrences', 'events-made-easy' );
	print wp_json_encode( $ajaxResult );
}

