<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_membership() {
	
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today            = $eme_date_obj_now->getDate();

	$membership               = [
		'name'            => '',
		'description'     => '',
		'type'            => '', // fixed/rolling
		'start_date'      => $today, // only for fixed
		'duration_count'  => 1,
		'duration_period' => 'years',
	];
	$membership['properties'] = eme_init_membership_props();

	return $membership;
}

function eme_new_member() {
	$member = [
		'membership_id'     => 0,
		'person_id'         => 0,
		'related_member_id' => 0,
		'status'            => 0,
		'paid'              => 0,
		'pg'                => '',
		'pg_pid'            => '',
		'extra_charge'      => 0,
		'status_automatic'  => 1,
		'start_date'        => '0000-00-00',
		'end_date'          => '0000-00-00',
		'payment_date'      => '0000-00-00 00:00',
		'discount'          => '',
		'discountids'       => '',
		'dcodes_entered'    => [],
		'dcodes_used'       => [],
		'dgroupid'          => 0,
	];
	return $member;
}

function eme_init_membership_props( $props = [] ) {
	if ( ! isset( $props['reminder_days'] ) ) {
		$props['reminder_days'] = '';
	} else {
		$test_arr = explode( ',', $props['reminder_days'] );
		if ( ! eme_is_numeric_array( $test_arr ) ) {
			$props['reminder_days'] = '';
		}
	}
	if ( ! isset( $props['remove_pending_days'] ) ) {
		$props['remove_pending_days'] = 0;
	}
	if ( ! isset( $props['registration_wp_users_only'] ) ) {
		$props['registration_wp_users_only'] = 0;
	}
	if ( ! isset( $props['grace_period'] ) ) {
		$props['grace_period'] = 0;
	}
	if ( ! isset( $props['one_free_period'] ) ) {
		$props['one_free_period'] = 0;
	}
	if ( ! isset( $props['price'] ) ) {
		$props['price'] = 0;
	}
	if ( ! isset( $props['extra_charge'] ) ) {
		$props['extra_charge'] = 0;
	}
	if ( ! isset( $props['contact_id'] ) ) {
		$props['contact_id'] = 0;
	}
	if ( ! isset( $props['member_template_id'] ) ) {
		$props['member_template_id'] = 0;
	}
	if ( ! isset( $props['family_membership'] ) ) {
		$props['family_membership'] = 0;
	}
	if ( ! isset( $props['family_maxmembers'] ) ) {
		$props['family_maxmembers'] = 10;
	}
	if ( ! isset( $props['addpersontogroup'] ) ) {
		$props['addpersontogroup'] = [];
	}
	if ( ! isset( $props['dyndata'] ) ) {
		$props['dyndata'] = [];
	}
	if ( ! isset( $props['dyndata_all_fields'] ) ) {
		$props['dyndata_all_fields'] = 0;
	}
	if ( ! isset( $props['currency'] ) ) {
		$props['currency'] = get_option( 'eme_default_currency' );
	}
	if ( ! isset( $props['vat_pct'] ) ) {
		$props['vat_pct'] = get_option( 'eme_default_vat' );
	}
	if ( ! isset( $props['use_cfcaptcha'] ) ) {
		$props['use_cfcaptcha'] = get_option( 'eme_cfcaptcha_for_forms' ) ? 1 : 0;
	}
	if ( ! isset( $props['use_hcaptcha'] ) ) {
		$props['use_hcaptcha'] = get_option( 'eme_hcaptcha_for_forms' ) ? 1 : 0;
	}
	if ( ! isset( $props['use_recaptcha'] ) ) {
		$props['use_recaptcha'] = get_option( 'eme_recaptcha_for_forms' ) ? 1 : 0;
	}
	if ( ! isset( $props['use_captcha'] ) ) {
		$props['use_captcha'] = get_option( 'eme_captcha_for_forms' ) ? 1 : 0;
	}
	if ( ! isset( $props['captcha_only_logged_out'] ) ) {
                $props['captcha_only_logged_out'] = get_option( 'eme_captcha_only_logged_out' ) ? 1 : 0;
        }
	if ( ! isset( $props['create_wp_user'] ) ) {
		$props['create_wp_user'] = 0;
	}
	if ( ! isset( $props['newmember_attach_ids'] ) ) {
		$props['newmember_attach_ids'] = '';
	}
	if ( ! isset( $props['renewal_cutoff_days'] ) ) {
		$props['renewal_cutoff_days'] = 0;
	}
	if ( ! isset( $props['allow_renewal'] ) ) {
		$props['allow_renewal'] = 1;
	}
	if ( ! isset( $props['attendancerecord'] ) ) {
		$props['attendancerecord'] = 0;
	}
	if ( ! isset( $props['discount'] ) ) {
		$props['discount'] = '';
	}
	if ( ! isset( $props['discountgroup'] ) ) {
		$props['discountgroup'] = '';
	}
	if ( ! isset( $props['skippaymentoptions'] ) ) {
		$props['skippaymentoptions'] = 0;
	}

	$payment_gateways = eme_payment_gateways();
	foreach ( $payment_gateways as $pg => $desc ) {
		// the properties for payment gateways alsways have "use_" in front of them, so add it
		if ( ! isset( $props[ 'use_' . $pg ] ) ) {
				$props[ 'use_' . $pg ] = 0;
		}
	}

	if ( ! isset( $props['new_subject_format'] ) ) {
		$props['new_subject_format'] = __( 'Welcome #_FIRSTNAME', 'events-made-easy' );
	}
	if ( ! isset( $props['extended_subject_format'] ) ) {
		$props['extended_subject_format'] = __( 'Membership extended', 'events-made-easy' );
	}
	if ( ! isset( $props['updated_subject_format'] ) ) {
		$props['updated_subject_format'] = __( 'Membership updated', 'events-made-easy' );
	}
	if ( ! isset( $props['paid_subject_format'] ) ) {
		$props['paid_subject_format'] = __( 'Payment received', 'events-made-easy' );
	}
	if ( ! isset( $props['reminder_subject_format'] ) ) {
		$props['reminder_subject_format'] = __( 'Expiration reminder for #_MEMBERSHIPNAME', 'events-made-easy' );
	}
	if ( ! isset( $props['stop_subject_format'] ) ) {
		$props['stop_subject_format'] = __( 'Goodbye #_FIRSTNAME', 'events-made-easy' );
	}
	if ( ! isset( $props['contact_new_subject_format'] ) ) {
		$props['contact_new_subject_format'] = __( 'New member signed up', 'events-made-easy' );
	}
	if ( ! isset( $props['contact_stop_subject_format'] ) ) {
		$props['contact_stop_subject_format'] = __( 'Member stopped', 'events-made-easy' );
	}
	if ( ! isset( $props['contact_ipn_subject_format'] ) ) {
		$props['contact_ipn_subject_format'] = __( 'Member IPN received', 'events-made-easy' );
	}
	if ( ! isset( $props['contact_paid_subject_format'] ) ) {
		$props['contact_paid_subject_format'] = __( 'Member payment received', 'events-made-easy' );
	}

	$templates = [ 'member_form_tpl', 'familymember_form_tpl', 'payment_form_header_tpl', 'payment_form_footer_tpl', 'new_body_format_tpl', 'updated_body_format_tpl', 'extended_body_format_tpl', 'paid_body_format_tpl', 'reminder_body_format_tpl', 'stop_body_format_tpl', 'contact_new_body_format_tpl', 'contact_stop_body_format_tpl', 'contact_ipn_body_format_tpl', 'contact_paid_body_format_tpl', 'offline_payment_tpl', 'member_added_tpl', 'payment_success_tpl' ];
	foreach ( $templates as $template ) {
		if ( ! isset( $props[ $template ] ) ) {
			$props[ $template ] = 0;
		}
	}
	$template_texts = [ 'member_form_text', 'familymember_form_text', 'payment_form_header_text', 'payment_form_footer_text', 'payment_success_text', 'offline_payment_text', 'new_body_text', 'contact_new_body_text', 'updated_body_text', 'extended_body_text', 'paid_body_text', 'contact_paid_body_text', 'reminder_body_text', 'stop_body_text', 'contact_stop_body_text', 'contact_ipn_body_text', 'member_added_text' ];
	foreach ( $template_texts as $template_text ) {
		if ( ! isset( $props[ $template_text ] ) ) {
			$props[ $template_text ] = '';
		}
	}
	return $props;
}

function eme_db_insert_membership( $membership ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$wpdb->show_errors( true );
	if ( ! eme_is_serialized( $membership['properties'] ) ) {
		$membership['properties'] = eme_serialize( $membership['properties'] );
	}
	if ( ! $wpdb->insert( $table, $membership ) ) {
		$wpdb->print_error();
		return false;
	} else {
		return $wpdb->insert_id;
	}
}

function eme_db_insert_member( $line, $membership, $member_id = 0 ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$wpdb->show_errors( true );

	if ( $membership['properties']['create_wp_user'] ) {
		$person = eme_get_person( $line['person_id'] );
		if ( ! empty( $person ) && empty( $person['wp_id'] ) && ! email_exists( $person['email'] ) ) {
			eme_create_wp_user( $person );
		}
	}

	$tmp_member = eme_new_member();
	// we only want the columns that interest us
	// we need to do this since this function is also called for csv import
	$keys   = array_intersect_key( $line, $tmp_member );
	$member = array_merge( $tmp_member, $keys );
	if ( $membership['duration_period'] == 'forever' ) {
		$member['end_date'] = '0000-00-00';
	}

	if ( has_filter( 'eme_insert_member_filter' ) ) {
		$member = apply_filters( 'eme_insert_member_filter', $member );
	}

	if ( empty( $member['creation_date'] ) || ! ( eme_is_date( $member['creation_date'] ) || eme_is_datetime( $member['creation_date'] ) ) ) {
		$member['creation_date'] = current_time( 'mysql', false );
	}
	$member['modif_date']    = $member['creation_date'];
	$member['membership_id'] = $membership['membership_id'];

	// eme_serialize if needed
	$member['dcodes_entered'] = eme_serialize( $member['dcodes_entered'] );
	$member['dcodes_used']    = eme_serialize( $member['dcodes_used'] );

	// add the memberid if wanted
	if ( ! empty( $member_id ) ) {
		$member['member_id'] = intval( $member_id );
	}

	if ( $wpdb->insert( $table, $member ) === false ) {
		return false;
	} else {
		if ( empty( $member_id ) ) {
			$member_id           = $wpdb->insert_id;
			$member['member_id'] = $member_id;
		}
		eme_store_member_answers( $member );
		return $member_id;
	}
}

function eme_db_update_member( $member_id, $line, $membership, $update_answers = 1 ) {
	global $wpdb;
	$table              = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$where              = [];
	$where['member_id'] = intval( $member_id );

	$tmp_member = eme_get_member( $member_id );
	// we only want the columns that interest us
	// we need to do this since this function is also called for csv import
	$keys   = array_intersect_key( $line, $tmp_member );
	$member = array_merge( $tmp_member, $keys );

	// make sure to have a good end_date for forever-memberships
	if ( $membership['duration_period'] == 'forever' ) {
		$member['end_date'] = '0000-00-00';
	}

	// if not a family membership, make sure this member is not related
	if ( empty( $membership['properties']['family_membership'] ) ) {
		$member['related_member_id'] = 0;
	}

	$member['modif_date'] = current_time( 'mysql', false );
	// make sure we always have the correct membership id
	$member['membership_id'] = $membership['membership_id'];

	// eme_serialize if needed
	$member['dcodes_entered'] = eme_serialize( $member['dcodes_entered'] );
	$member['dcodes_used']    = eme_serialize( $member['dcodes_used'] );

	if ( ! empty( $member ) && $wpdb->update( $table, $member, $where ) === false ) {
		return false;
	} else {
		eme_member_recalculate_status( $member_id );
		// now get the updated member before doing other actions
		$member = eme_get_member( $member_id );
		// in case we edit a head of family, $update_answers is 0 for the family members (in the function eme_add_update_member), otherwise those will get the same answers as the head, which is not the intention
		if ( $update_answers ) {
			eme_store_member_answers( $member );
		}
		// only for accounts that are not family members, we calc discount
		if ( $member['related_member_id'] == 0 ) {
			eme_update_member_discount( $member );
		}
		return $member_id;
	}
}

function eme_db_update_membership( $membership_id, $line ) {
	global $wpdb;
	$table                  = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$where                  = [];
	$where['membership_id'] = intval( $membership_id );

	$membership = eme_get_membership( $membership_id );
	wp_cache_delete( "eme_membership $membership_id" );
	unset( $membership['membership_id'] );
	// we only want the columns that interest us
	// we need to do this since this function is also called for csv import
	$keys     = array_intersect_key( $line, $membership );
	$new_line = array_merge( $membership, $keys );

	if ( ! eme_is_serialized( $new_line['properties'] ) ) {
		$new_line['properties'] = eme_serialize( $new_line['properties'] );
	}

	if ( ! empty( $new_line ) && $wpdb->update( $table, $new_line, $where ) === false ) {
		return false;
	} else {
		return $membership_id;
	}
}

function eme_update_member_lastseen( $member_id ) {
	global $wpdb;
	$table              = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$where              = [];
	$where['member_id'] = intval( $member_id );

	$fields              = [];
	$fields['last_seen'] = current_time( 'mysql', false );
	$wpdb->update( $table, $fields, $where );
}

function eme_get_members( $member_ids, $extra_search = '' ) {
	global $wpdb;
	$people_table      = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$members_table     = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$lines             = [];
	if ( ! empty( $member_ids ) && eme_is_numeric_array( $member_ids ) ) {
		$ids_list = implode(',', $member_ids);
		$sql     = "SELECT members.*, people.lastname, people.firstname, people.email, memberships.name AS membership_name
              FROM $members_table AS members
              LEFT JOIN $memberships_table AS memberships ON members.membership_id=memberships.membership_id
              LEFT JOIN $people_table AS people ON members.person_id=people.person_id
              WHERE members.member_id IN ($ids_list)";
		if ( ! empty( $extra_search ) ) {
			$sql .= " AND $extra_search";
		}
	} else {
		$sql = "SELECT members.*, people.lastname, people.firstname, people.email, memberships.name AS membership_name
              FROM $members_table AS members
              LEFT JOIN $memberships_table AS memberships ON members.membership_id=memberships.membership_id
              LEFT JOIN $people_table AS people ON members.person_id=people.person_id
              ";
		if ( ! empty( $extra_search ) ) {
			$sql .= " WHERE $extra_search";
		}
	}
	$lines = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $lines;
}

function eme_get_memberships( $exclude_id = 0 ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$sql   = "SELECT * FROM $table";
	if ( ! empty( $exclude_id ) ) {
		$sql .= ' WHERE membership_id <> ' . intval( $exclude_id );
	}
	$memberships = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $memberships as $key => $membership ) {
		$membership['properties'] = eme_init_membership_props( eme_unserialize( $membership['properties'] ) );
		$memberships[ $key ]      = $membership;
	}
	return $memberships;
}

function eme_get_membership( $id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	if ( empty( $id ) ) {
		return false;
	}
	if ( ! is_numeric( $id ) ) {
		$sql = $wpdb->prepare( "SELECT * FROM $table WHERE name=%s", $id );
	} else {
		$sql = $wpdb->prepare( "SELECT * FROM $table WHERE membership_id=%d", $id );
	}
	$membership = wp_cache_get( "eme_membership $id" );
	if ( $membership === false ) {
		//$wpdb->show_errors(true);
		$membership = $wpdb->get_row( $sql, ARRAY_A );
		if ( $membership ) {
			$membership['properties'] = eme_init_membership_props( eme_unserialize( $membership['properties'] ) );
			wp_cache_set( "eme_membership $id", $membership, '', 15 );
		}
	}

	if ( $membership ) {
		return $membership;
	} else {
		return false;
	}
}

function eme_get_membership_stats( $ids ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	if ( ! eme_is_list_of_int( $ids ) ) {
		return false;
	}

	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
	$remove_expired_days = get_option( 'eme_gdpr_remove_expired_member_days' );
	if (!empty( $remove_expired_days ) ) {
		$eme_date_obj->minusDays( $remove_expired_days )->startOfMonth()->addOneMonth();
		$difference = $eme_date_obj->getDifferenceInMonths($eme_date_obj_now);
	} else {
		$eme_date_obj->startOfMonth()->modifyMonths(-12);
		$difference = 12;
	}

	$res = '<table>';
	$counter = 0;
	while ( $counter <= $difference ) {
		$limit_start   = $eme_date_obj->format( 'Y-m-d' );
		$days_in_month = $eme_date_obj->getDaysInMonth();
		$limit_end     = $eme_date_obj->format( "Y-m-$days_in_month" );
		if ($counter==$difference) {
			$sql = "SELECT count(*) FROM $table WHERE status=1 AND membership_id IN ($ids)";
			$member_nbr = $wpdb->get_var( $sql );
		} else {
			$sql1 = $wpdb->prepare( "SELECT count(*) FROM $table WHERE start_date<=%s AND (end_date >= %s OR end_date = '0000-00-00') AND membership_id IN ($ids)", $limit_end, $limit_start );
			$member_nbr_1 = $wpdb->get_var( $sql1 );
			$sql2 = $wpdb->prepare( "SELECT count(*) FROM $table WHERE end_date>=%s AND end_date <= %s AND status=100 AND membership_id IN ($ids)", $limit_start, $limit_end );
			$member_nbr_2 = $wpdb->get_var( $sql2 );
			$member_nbr = $member_nbr_1 - $member_nbr_2;

		}
		$res .= "<tr><td>".$eme_date_obj->format( 'Y-m' )."</td><td>$member_nbr</td></tr>";
		$eme_date_obj->startOfMonth()->modifyMonths(+1);
		$counter++;
	}
	// now the cur month
	$res .= '</table>';
	return $res;
}

function eme_membership_types() {
	$type_array = [
		'fixed'   => __( 'Fixed startdate', 'events-made-easy' ),
		'rolling' => __( 'Rolling period', 'events-made-easy' ),
	];
	return $type_array;
}

function eme_membership_durations() {
	$duration_array = [
		''        => '',
		'days'    => __( 'Days', 'events-made-easy' ),
		'weeks'   => __( 'Weeks', 'events-made-easy' ),
		'months'  => __( 'Months', 'events-made-easy' ),
		'years'   => __( 'Years', 'events-made-easy' ),
		'forever' => __( 'Forever', 'events-made-easy' ),
	];
	return $duration_array;
}

function eme_get_member( $id ) {
	global $wpdb;
	$table  = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql    = $wpdb->prepare( "SELECT * FROM $table WHERE member_id=%d", $id );
	$member = $wpdb->get_row( $sql, ARRAY_A );
	if ( ! empty( $member ) ) {
		if ( eme_is_serialized( $member['dcodes_used'] ) ) {
				$member['dcodes_used'] = eme_unserialize( $member['dcodes_used'] );
		} else {
			$member['dcodes_used'] = [];
		}
		if ( eme_is_serialized( $member['dcodes_entered'] ) ) {
				$member['dcodes_entered'] = eme_unserialize( $member['dcodes_entered'] );
		} else {
			$member['dcodes_entered'] = [];
		}
	}
	return $member;
}

function eme_get_active_member_by_personid_membershipid( $person_id, $membership_id ) {
	global $wpdb;
	$table         = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$status_active = EME_MEMBER_STATUS_ACTIVE;
	$status_grace  = EME_MEMBER_STATUS_GRACE;
	$sql           = $wpdb->prepare( "SELECT * FROM $table WHERE person_id=%d AND membership_id=%d AND status IN ($status_active,$status_grace) LIMIT 1", $person_id, $membership_id );
	$member        = $wpdb->get_row( $sql, ARRAY_A );
	if ( ! empty( $member ) ) {
		if ( eme_is_serialized( $member['dcodes_used'] ) ) {
				$member['dcodes_used'] = eme_unserialize( $member['dcodes_used'] );
		} else {
			$member['dcodes_used'] = [];
		}
		if ( eme_is_serialized( $member['dcodes_entered'] ) ) {
				$member['dcodes_entered'] = eme_unserialize( $member['dcodes_entered'] );
		} else {
			$member['dcodes_entered'] = [];
		}
	}
	return $member;
}

function eme_get_member_by_wpid_membershipid( $wp_id, $membership_id, $status = '' ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$persons_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	if ( ! empty( $status ) ) {
		if ( strstr( $status, ',' ) ) {
			$cond_status = "AND members.status IN ($status)";
		} else {
			$cond_status = "AND members.status = $status";
		}
	} else {
		$cond_status = '';
	}
	$sql    = $wpdb->prepare( "SELECT members.* FROM $members_table AS members, $persons_table AS persons WHERE members.membership_id=%d $cond_status AND members.person_id=persons.person_id AND persons.wp_id=%d LIMIT 1", $membership_id, $wp_id );
	$member = $wpdb->get_row( $sql, ARRAY_A );
	if ( ! empty( $member ) ) {
		if ( eme_is_serialized( $member['dcodes_used'] ) ) {
				$member['dcodes_used'] = eme_unserialize( $member['dcodes_used'] );
		} else {
			$member['dcodes_used'] = [];
		}
		if ( eme_is_serialized( $member['dcodes_entered'] ) ) {
				$member['dcodes_entered'] = eme_unserialize( $member['dcodes_entered'] );
		} else {
			$member['dcodes_entered'] = [];
		}
	}

	return $member;
}

function eme_get_activemembership_names_by_personid( $person_id ) {
	global $wpdb;
	$status_active     = EME_MEMBER_STATUS_ACTIVE;
	$status_grace      = EME_MEMBER_STATUS_GRACE;
	$members_table     = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$people_table      = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql               = $wpdb->prepare( "SELECT DISTINCT memberships.name FROM $memberships_table AS memberships,$members_table AS members WHERE memberships.membership_id=members.membership_id AND members.person_id = %d AND members.status IN ($status_active,$status_grace)", $person_id );
	return $wpdb->get_col( $sql );
}

function eme_get_active_membershipids_by_wpid( $wp_id ) {
	global $wpdb;
	$status_active = EME_MEMBER_STATUS_ACTIVE;
	$status_grace  = EME_MEMBER_STATUS_GRACE;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql           = $wpdb->prepare( "SELECT DISTINCT members.membership_id FROM $members_table AS members, $people_table AS persons WHERE members.status IN ($status_active,$status_grace) AND members.person_id=persons.person_id AND persons.wp_id=%d", $wp_id );
	return $wpdb->get_col( $sql );
}

function eme_get_memberids_by_wpid( $wp_id ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$persons_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql           = $wpdb->prepare( "SELECT DISTINCT members.member_id FROM $members_table AS members, $persons_table AS persons WHERE members.person_id=persons.person_id AND persons.wp_id=%d", $wp_id );
	return $wpdb->get_col( $sql );
}

function eme_get_members_by_wpid_membershipid( $wp_id, $membership_id ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$persons_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$sql           = $wpdb->prepare( "SELECT members.* FROM $members_table AS members, $persons_table AS persons WHERE members.membership_id=%d AND members.person_id=persons.person_id AND persons.wp_id=%d", $membership_id, $wp_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_membership_memberids( $membership_id ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT member_id from $members_table WHERE membership_id = %d", $membership_id );
	return $wpdb->get_col( $sql );
}

function eme_get_member_by_paymentid( $id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE payment_id=%d AND related_member_id=0", $id );
	return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_delete_member( $member_id ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	if ( ! empty( $member_id ) ) {
		if ( has_action( 'eme_delete_member_action' ) ) {
			$member = eme_get_member($member_id);
			do_action( 'eme_delete_member_action', $member );
		}
		// do the related member ids before deletion the head of the family
		$related_member_ids = eme_get_family_member_ids( $member_id );
		if ( ! empty( $related_member_ids ) ) {
			foreach ( $related_member_ids as $related_member_id ) {
				eme_delete_member( $related_member_id );
			}
		}
		eme_delete_member_answers( $member_id );
		$sql = $wpdb->prepare( "DELETE FROM $members_table WHERE member_id = %d", $member_id );
		$wpdb->query( $sql );
		eme_delete_uploaded_files( $member_id, 'members' );
	}
}

function eme_delete_membership( $membership_id ) {
	global $wpdb;
	$members_table     = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$answers_table     = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	// first get all existing memberids, so we can delete the uploaded files
	$member_ids = eme_get_membership_memberids( $membership_id );
	foreach ( $member_ids as $member_id ) {
		eme_delete_uploaded_files( $member_id, 'members' );
	}
	$sql = $wpdb->prepare( "DELETE FROM $answers_table WHERE type='member' AND related_id IN (SELECT member_id from $members_table WHERE membership_id = %d)", $membership_id );
	$wpdb->query( $sql );
	$sql = $wpdb->prepare( "DELETE FROM $members_table WHERE membership_id = %d", $membership_id );
	$wpdb->query( $sql );
	$sql = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id = %d AND type='membership'", $membership_id );
	$wpdb->query( $sql );
	$sql = $wpdb->prepare( "DELETE FROM $memberships_table WHERE membership_id = %d", $membership_id );
	$wpdb->query( $sql );

	eme_delete_membership_attendances( $membership_id );
	eme_delete_membership_answers( $membership_id );
	eme_delete_uploaded_files( $membership_id, 'memberships' );
	wp_cache_delete( "eme_membership $membership_id" );
}

// the next function returns the price for a specific member
function eme_get_total_member_price( $member, $ignore_extras = 0 ) {
	$membership = eme_get_membership( $member['membership_id'] );
	$price      = $membership['properties']['price'];

	if ( ! $ignore_extras ) {
		if ( ! empty( $member['extra_charge'] ) ) {
			$price += $member['extra_charge'];
		}
		if ( ! empty( $member['discount'] ) ) {
			$price -= $member['discount'];
		}
	}
		// we add the extra cost for new members
	if ( $member['status'] == EME_MEMBER_STATUS_PENDING && ! empty( $membership['properties']['extra_charge'] ) ) {
		$price += $membership['properties']['extra_charge'];
	}

	if ( $price < 0 ) {
		$price = 0;
	}
	return $price;
}

function eme_members_page() {
	$message        = '';
	$current_userid = get_current_user_id();

	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'import' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$message = eme_import_csv_members();
		} else {
			$message = __( 'You have no right to manage members!', 'events-made-easy' );
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'import_dynamic_answers' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$message = eme_import_csv_member_dynamic_answers();
		} else {
			$message = __( 'You have no right to manage members!', 'events-made-easy' );
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_addmember' ) {
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$res     = eme_add_update_member();
			$message = $res[0];
		} else {
			$message = __( 'You have no right to manage members!', 'events-made-easy' );
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_editmember' ) {
		$member_id = intval( $_POST['member_id'] );
		$send_mail = ( isset( $_POST['send_mail'] ) ) ? intval( $_POST['send_mail'] ) : 0;
		$member    = eme_get_member( $member_id );
		$wp_id     = eme_get_wpid_by_personid( $member['person_id'] );
		if ( $member && ( current_user_can( get_option( 'eme_cap_edit_members' ) ) || ( current_user_can( get_option( 'eme_cap_author_members' ) ) && $wp_id == $current_userid ) ) ) {
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$res     = eme_add_update_member( $member_id, $send_mail );
			$message = $res[0];
		} else {
			$message = __( 'You have no right to edit this member!', 'events-made-easy' );
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_member' ) {
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
			$member = eme_new_member();
			eme_member_edit_layout( $member );
			return;
		} else {
			$message = __( 'You have no right to manage members!', 'events-made-easy' );
		}
	} elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_member' && isset( $_GET['member_id'] ) ) {
		$member_id = intval( $_GET['member_id'] );
		$member    = eme_get_member( $member_id );
		$wp_id     = eme_get_wpid_by_personid( $member['person_id'] );
		if ( $member && ( current_user_can( get_option( 'eme_cap_edit_members' ) ) || ( current_user_can( get_option( 'eme_cap_author_members' ) ) && $wp_id == $current_userid ) ) ) {
			if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
				eme_member_edit_layout( $member );
			} else {
				eme_member_edit_layout( $member, 1 );
			}
			return;
		} else {
			$message = __( 'You have no right to manage members!', 'events-made-easy' );
		}
	}
	eme_manage_members_layout( $message );
}

function eme_memberships_page() {
	$message = '';
	if ( ! current_user_can( get_option( 'eme_cap_edit_members' ) ) && ( isset( $_POST['eme_admin_action'] ) || isset( $_GET['eme_admin_action'] ) ) ) {
		$message = __( 'You have no right to manage memberships!', 'events-made-easy' );
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_addmembership' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		[$membership_id, $res_text] = eme_add_update_membership();
		if ( $membership_id ) {
			$message = __( 'Membership added', 'events-made-easy' );
			if ( get_option( 'eme_stay_on_edit_page' ) || ! empty( $res_text ) ) {
				$membership = eme_get_membership( $membership_id );
				if ( ! empty( $res_text ) ) {
					$message .= "<br>$res_text";
				}
				eme_membership_edit_layout( $membership, $message );
				return;
			}
		} else {
			$message = __( 'Problem detected while adding membership', 'events-made-easy' );
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_editmembership' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		$membership_id       = intval( $_POST['membership_id'] );
		[$res, $res_text] = eme_add_update_membership( $membership_id );
		if ( $res ) {
			$message = __( 'Membership updated', 'events-made-easy' );
			if ( get_option( 'eme_stay_on_edit_page' ) || ! empty( $res_text ) ) {
				$membership = eme_get_membership( $membership_id );
				if ( ! empty( $res_text ) ) {
					$message .= "<br>$res_text";
				}
				eme_membership_edit_layout( $membership, $message );
				return;
			}
		} else {
			$message    = __( 'Problem detected while updating membership', 'events-made-easy' );
			$membership = eme_get_membership( $membership_id );
			eme_membership_edit_layout( $membership, $message );
			return;
		}
	} elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_membership' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		$membership = eme_new_membership();
		eme_membership_edit_layout( $membership );
		return;
	} elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_membership' ) {
		$membership_id = intval( $_GET['membership_id'] );
		$membership    = eme_get_membership( $membership_id );
		if ( $membership ) {
			eme_membership_edit_layout( $membership );
			return;
		}
	}
	eme_manage_memberships_layout( $message );
}

function eme_add_update_member( $member_id = 0, $send_mail = 1 ) {
	$member        = [];
	$payment_id    = 0;
	$membership_id = 0;
	$transfer      = 0;
	if ( ! empty( $_POST['membership_id'] ) ) {
		$membership_id = intval( $_POST['membership_id'] );
	} else {
		return __( 'No valid membership selected', 'events-made-easy' );
	}
	if ( ! empty( $_POST['transferto_membershipid'] ) ) {
		$orig_membership_id = $membership_id;
		$membership_id      = intval( $_POST['transferto_membershipid'] );
		// move all data from the original membership in $_POST to the new membership id too, so other membership code (like eme_store_member_answers) can work as expected with the new membership id
		foreach ( $_POST as $key => $val ) {
			$key = eme_sanitize_request( $key );
			if ( is_array( $_POST[ $key ] ) && isset( $_POST[ $key ][ $orig_membership_id ] ) ) {
				$_POST[ $key ][ $membership_id ] = eme_sanitize_request( $_POST[ $key ][ $orig_membership_id ] );
			}
		}
		$transfer = 1;
	}

	$eme_is_admin_request = eme_is_admin_request();
	if ( $eme_is_admin_request ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		if ( isset( $_POST['status'] ) ) {
			$member['status'] = intval( $_POST['status'] );
		}
		if ( ! empty( $_POST['transferto_personid'] ) ) {
			$member['person_id'] = intval( $_POST['transferto_personid'] );
		}
		if ( isset( $_POST['status_automatic'] ) ) {
			$member['status_automatic'] = intval( $_POST['status_automatic'] );
		}
		if ( ! eme_is_empty_date( $_POST['start_date'] ) && eme_is_date( $_POST['start_date'] ) ) {
			$member['start_date'] = eme_sanitize_request( $_POST['start_date'] );
		}
		if ( $transfer && ! eme_is_empty_date( $member['start_date'] ) && eme_is_empty_date( $_POST['end_date'] ) ) {
			$membership         = eme_get_membership( $membership_id );
			$member['end_date'] = eme_get_next_end_date( $membership, $member['start_date'] );
		} elseif ( eme_is_date( $_POST['end_date'] ) ) {
				$member['end_date'] = eme_sanitize_request( $_POST['end_date'] );
		} else {
			$member['end_date'] = eme_get_next_end_date( $membership, $member['start_date'] );
		}
		if ( isset( $_POST['paid'] ) ) {
			$member['paid'] = intval( $_POST['paid'] );
		}
		if ( eme_is_datetime( $_POST['payment_date'] ) ) {
			$member['payment_date'] = eme_sanitize_request( $_POST['payment_date'] );
		}
		if ( empty( $membership['properties']['family_membership'] ) ) {
			$member['related_member_id'] = 0;
		} elseif ( isset( $_POST['related_member_id'] ) ) {
			// for a family member, we take the info from the head of the family (except the payment id, that needs to be uniquely linked to 1 member)
			$member['related_member_id'] = intval( $_POST['related_member_id'] );
			if ( $member['related_member_id'] > 0 ) {
				// make sure we don't link to ourselves
				if ( $member_id && $member['related_member_id'] == $member_id ) {
					return __( 'Linking to yourself is not allowed.', 'events-made-easy' );
				}
				$related_member = eme_get_member( $member['related_member_id'] );
				// if the related member is also already related to someone else: stop here
				if ( ! empty( $related_member['related_member_id'] ) ) {
					return __( "The related family member you're trying to set already belongs to another family account, so this is not allowed.", 'events-made-easy' );
				}
				$membership_id        = $related_member['membership_id'];
				$member['start_date'] = $related_member['start_date'];
				$member['end_date']   = $related_member['end_date'];
				if ( eme_is_empty_date( $member['payment_date'] ) ) {
					$member['payment_date'] = $related_member['payment_date'];
				}
				$member['paid']             = $related_member['paid'];
				$member['status']           = $related_member['status'];
				$member['status_automatic'] = $related_member['status_automatic'];
			}
		}
	}
	// even though we got the membership in the previous if-structure, the membership id could've been changed if the person was designated as being a family member
	// so we get the membership here again, to be sure we have the correct one
	$membership = eme_get_membership( $membership_id );
	if ( $member_id ) {
		// existing member, all person info remains unchanged
		$res = eme_db_update_member( $member_id, $member, $membership );
		if ( $res ) {
			$member          = eme_get_member( $member_id );
			$upload_failures = eme_upload_files( $member_id, 'members' );
			// upload failures: in the backend, just show them, in the frontend: only show them and delete the member again
			if ( ! empty( $upload_failures ) ) {
				$result  = __( 'Member updated, but there were some problems uploading files', 'events-made-easy' );
				$result .= $upload_failures;
			} else {
				$result = __( 'Member updated', 'events-made-easy' );
				if ( $send_mail ) {
					eme_email_member_action( $member, 'updateMember' );
				}
			}
			$payment_id = $member['payment_id'];
			// if we're updaing the head of the family, change the family members
			$related_member_ids = eme_get_family_member_ids( $member_id );
			if ( ! empty( $related_member_ids ) ) {
				$fields_to_copy = [ 'start_date', 'end_date', 'paid', 'payment_date', 'status', 'status_automatic', 'reminder', 'reminder_date', 'pg', 'pg_pid' ];
				foreach ( $related_member_ids as $related_member_id ) {
					$related_member = [];
					foreach ( $fields_to_copy as $field_to_copy ) {
						$related_member[ $field_to_copy ] = $member[ $field_to_copy ];
					}
					// just to be sure, the next 2 fields only need to be present for the master account, not family members
					$related_member['payment_id'] = 0;
					$related_member['unique_nbr'] = '';
					// for the update of an existing master account, we will not update the answers for family members of course
					$update_answers = 0;
					eme_db_update_member( $related_member_id, $related_member, $membership, $update_answers );
					if ( $send_mail ) {
						eme_email_member_action( $related_member, 'updateMember' );
					}
				}
			}
		} else {
			$result = __( 'Problem detected while updating member', 'events-made-easy' );
		}
	} elseif ( ! empty( $_POST['person_id'] ) || ( isset( $_POST['lastname'] ) && isset( $_POST['firstname'] ) && isset( $_POST['email'] ) ) ) {
		if ( isset( $_POST['send_mail'] ) && $_POST['send_mail'] == 1 ) {
			$send_mail = 1;
		} else {
			$send_mail = 0;
		}
			$err       = '';
			$person_id = 0;
		if ( ! empty( $_POST['person_id'] ) ) {
			$person_id = intval( $_POST['person_id'] );
			$person    = eme_get_person( $person_id );
			if ( ! $person ) {
				$err       = __( 'No such person found', 'events-made-easy' );
				$person_id = 0;
			}
		} elseif ( eme_is_empty_string( $_POST['lastname'] ) ) {
			// we need at least lastname
			$err = __( 'Please enter at least the last name for a new member', 'events-made-easy' );
		} elseif ( ! $eme_is_admin_request && ! eme_is_email( $_POST['email'], 1 ) ) {
			// we need an email
			$err = __( 'Please enter a valid email address', 'events-made-easy' );
		} elseif ( $membership['properties']['create_wp_user'] && ! eme_is_email( $_POST['email'], 1 ) ) {
			// we need an email
			$err = __( 'Please enter a valid email address', 'events-made-easy' );
		} else {
			$wp_id          = eme_get_wpid_by_post();
			$bookerLastName = eme_sanitize_request( $_POST['lastname'] );
			if ( isset( $_POST['firstname'] ) ) {
				$bookerFirstName = eme_sanitize_request( $_POST['firstname'] );
			} else {
				$bookerFirstName = '';
			}
			$bookerEmail = eme_sanitize_email( $_POST['email'] );

			$res       = eme_add_update_person_from_form( 0, $bookerLastName, $bookerFirstName, $bookerEmail, $wp_id, $membership['properties']['create_wp_user'] );
			$person_id = $res[0];
			$err       = $res[1];
			// now add the family members
			$familymember_person_ids = [];
			if ( $person_id ) {
				if ( isset( $_POST['familymember'] ) ) {
					foreach ( $_POST['familymember'] as $familymember ) {
						// sanitizing happens in eme_add_familymember_from_frontend
						$familymember_person_ids[] = eme_add_familymember_from_frontend( $person_id, $familymember );
					}
				}
			}
		}
		if ( ! $person_id ) {
			$result = $err;
		} else {
			// if it is an existing member (pending, active, grace or expired)
			// we include expired so we can also redirect those to the payment info page
			$member_id = eme_is_member( $person_id, $membership_id, 1 );
			if ( $member_id ) {
				$existing_member = eme_get_member( $member_id );
				if ( eme_is_active_member( $existing_member ) ) {
					// if it is an active member (active, grace)
					$result = __( 'This person is already a member', 'events-made-easy' );
				} else {
					// else it is pending
					$payment_id = $existing_member['payment_id'];
					// no payment id yet? Then let's create a payment (can be old members, older imports, ...)
					if ( empty( $payment_id ) ) {
						$payment_id = eme_create_member_payment( $existing_memberid );
					}
					$payment = eme_get_payment( $payment_id );
					if ( ! empty( $payment ) ) {
						$payment_url = esc_url( eme_payment_url( $payment ) );
					} else {
						$payment_url = '';
					}
					if ( empty( $payment_url ) ) {
						$result = __( 'Payment not possible for this member.', 'events-made-easy' );
					} elseif ( eme_is_expired_member( $existing_member ) ) {
						$result = sprintf( __( 'This person is already a member, but the membership has expired. No updates have been done. Please click <a href="%s">here</a> to complete the payment process and reactivate the membership.', 'events-made-easy' ), $payment_url );
					} else {
						$result = sprintf( __( 'This person is already a member, but has not paid yet. Please click <a href="%s">here</a> to complete the payment process.', 'events-made-easy' ), $payment_url );
					}
				}
			} else {
				$new_member              = eme_member_from_form( $membership );
				$new_member['person_id'] = $person_id;
				// now merge the new member info (as it is taken like if from frontend) with the extra info added in the backend (if the member was added via the backend)
				if ( empty( $member ) ) {
					$member = $new_member;
				} else {
					$member = array_merge( $new_member, $member );
				}
				$member_id = eme_db_insert_member( $member, $membership );
				if ( $member_id ) {
					$upload_failures = eme_upload_files( $member_id, 'members' );
					// upload failures: in the backend, just show them, in the frontend: only show them and delete the member again
					if ( ! empty( $upload_failures ) ) {
						if ( $eme_is_admin_request ) {
							$result  = __( 'Member added, but there were some problems uploading files', 'events-made-easy' );
							$result .= $upload_failures;
						} else {
							$result  = __( 'There were some problems uploading files, please try again', 'events-made-easy' );
							$result .= $upload_failures;
							eme_delete_member( $member_id );
						}
					} else {
						// make sure to update the discount count if applied
						if ( ! $eme_is_admin_request && ! empty( $member['discountids'] ) ) {
							$discount_ids = explode( ',', $member['discountids'] );
							foreach ( $discount_ids as $discount_id ) {
									eme_increase_discount_member_count( $discount_id, $member );
							}
						}

						// now for the familymembers, we need to do this before we send out the newMember mail, otherwise the info
						// concerning related family members (#_FAMILYCOUNT and #_FAMILYMEMBERS) is not correctly replaced
						$familymember_person_ids = eme_get_family_person_ids( $person_id );
						$fields_to_copy          = [ 'start_date', 'end_date', 'paid', 'payment_date', 'status', 'status_automatic', 'reminder', 'reminder_date', 'pg', 'pg_pid' ];
						foreach ( $familymember_person_ids as $familymember_person_id ) {
							$familymember_id = eme_is_member( $familymember_person_id, $membership_id );
							if ( $familymember_id ) {
								$familymember                      = eme_get_member( $familymember_id );
								$familymember['related_member_id'] = $member_id;
								foreach ( $fields_to_copy as $field_to_copy ) {
									$familymember[ $field_to_copy ] = $member[ $field_to_copy ];
								}
								// just to be sure, the next 2 fields only need to be present for the master account, not family members
								$familymember['payment_id'] = 0;
								$familymember['unique_nbr'] = '';
								eme_db_update_member( $familymember_id, $familymember, $membership );
							} else {
								$familymember                      = [];
								$familymember['person_id']         = $familymember_person_id;
								$familymember['related_member_id'] = $member_id;
								foreach ( $fields_to_copy as $field_to_copy ) {
									$familymember[ $field_to_copy ] = $member[ $field_to_copy ];
								}
								eme_db_insert_member( $familymember, $membership );
							}
						}

						// now handle the rest
						$payment_id = eme_create_member_payment( $member_id );
						$member     = eme_get_member( $member_id );
						// send mail if either from the frontend or from backend if send_mail=1, but not if the member is a familymember
						if ( ( ! $eme_is_admin_request || $send_mail == 1 ) && ! $member['related_member_id'] ) {
							$res2 = eme_email_member_action( $member, 'newMember' );
							if ( $member['paid'] ) {
								$res2 = eme_email_member_action( $member, 'markPaid' );
							}
						} else {
								$res2 = 1;
						}
						if ( ! eme_is_empty_string( $membership['properties']['member_added_text'] ) ) {
							$added_format = $membership['properties']['member_added_text'];
						} else {
							$added_format = eme_get_template_format( $membership['properties']['member_added_tpl'] );
						}
						if ( isset( $added_format ) ) {
							$result = eme_replace_member_placeholders( $added_format, $membership, $member );
							if ( ! $res2 ) {
								$result .= __( 'Member added, but there were some problems sending out the mail', 'events-made-easy' );
							}
						} elseif ( $res2 ) {
								$result = __( 'Member added', 'events-made-easy' );
						} else {
							$result = __( 'Member added, but there were some problems sending out the mail', 'events-made-easy' );
						}
						// now that everything is (or should be) correctly entered in the db, execute possible actions for the new member
						if ( has_action( 'eme_insert_member_action' ) ) {
							do_action( 'eme_insert_member_action', $member );
						}
					}
				}
			}
		}
	} else {
		$result = __( 'Problem detected while adding personal info: at least last name, first name and email need to be present', 'events-made-easy' );
	}
	$res = [
		0 => $result,
		1 => $payment_id,
	];
	return $res;
}

function eme_is_active_member( $member ) {
	if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE || $member['status'] == EME_MEMBER_STATUS_GRACE ) {
		return 1;
	} else {
		return 0;
	}
}

function eme_is_expired_member( $member ) {
	if ( $member['status'] == EME_MEMBER_STATUS_EXPIRED ) {
		return 1;
	} else {
		return 0;
	}
}

function eme_is_active_memberid( $member_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE member_id=%d AND status IN (%d,%d)", $member_id, EME_MEMBER_STATUS_ACTIVE, EME_MEMBER_STATUS_GRACE );
	$res   = $wpdb->get_var( $sql );
	if ( $res > 0 ) {
		return 1;
	} else {
		return 0;
	}
}

function eme_is_member( $person_id, $membership_id, $include_expired = 0 ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	if ( $include_expired ) {
		$sql = $wpdb->prepare( "SELECT member_id FROM $table WHERE membership_id=%d AND person_id=%d", $membership_id, $person_id );
	} else {
		$sql = $wpdb->prepare( "SELECT member_id FROM $table WHERE membership_id=%d AND person_id=%d AND status!=%d", $membership_id, $person_id, EME_MEMBER_STATUS_EXPIRED );
	}
	return $wpdb->get_var( $sql );
}

function eme_check_member_allowed_to_pay( $member, $membership ) {
        if ( $membership['properties']['allow_renewal'] && $member['status'] != EME_MEMBER_STATUS_PENDING ) {
                if ( empty( $member['end_date'] ) || $member['end_date'] == '0000-00-00' ) {
                        return "<div class='eme-message-success eme-already-paid'>" . __( 'This has already been paid for', 'events-made-easy' ) . '</div>';
                } else {
                        $too_soon_to_pay = 0;
                        if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE && ! empty( $membership['properties']['renewal_cutoff_days'] ) ) {
                                $end_date_obj     = ExpressiveDate::createFromFormat( 'Y-m-d', $member['end_date'], ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
                                $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
                                $diff             = $eme_date_obj_now->getDifferenceInDays( $end_date_obj );
                                if ( $diff > intval( $membership['properties']['renewal_cutoff_days'] ) ) {
                                        $too_soon_to_pay = 1;
                                }
                        }

                        if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE && $too_soon_to_pay ) {
                                $end_date    = eme_localized_date( $member['end_date'], EME_TIMEZONE );
                                $ret_string .= "<div class='eme-message-success eme-rsvp-message-success'>" . sprintf( __( 'Your membership is currently active until %s. It is not allowed to extend the membership yet (too soon).', 'events-made-easy' ), $end_date ) . '</div>';
                                return $ret_string;
			}
		}
	}
	if ( ! $membership['properties']['allow_renewal'] && $member['status'] == EME_MEMBER_STATUS_EXPIRED ) {
                $contact         = eme_get_contact( $membership['properties']['contact_id'] );
                $contact_name    = $contact->display_name;
		$ret_string .= "<div class='eme-message-error eme-rsvp-message-error'>" . sprintf( __( 'Your membership has expired but renewal is not allowed, please contact %s.', 'events-made-easy' ), $contact_name ) . '</div>';
                return $ret_string;
        }

	return '';
}

function eme_membership_exists( $id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE membership_id=%d", $id );
	return $wpdb->get_var( $sql );
}

function eme_memberships_exists( $ids_arr ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	if ( ! empty( $ids_arr ) && eme_is_numeric_array( $ids_arr ) ) {
		$ids_list = join( ',', $ids_arr );
		return $wpdb->get_col( "SELECT DISTINCT membership_id FROM $table WHERE membership_id IN ($ids_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	} else {
		return false;
	}
}

function eme_add_update_membership( $membership_id = 0 ) {
	$membership = [];
	$properties = [];
	$message    = '';

	$membership['name']            = isset( $_POST['name'] ) ? eme_sanitize_request( $_POST['name'] ) : '';
	$membership['description']     = isset( $_POST['description'] ) ? eme_sanitize_request( $_POST['description'] ) : '';
	$membership['duration_count']  = isset( $_POST['duration_count'] ) ? intval( $_POST['duration_count'] ) : 0;
	$membership['duration_period'] = isset( $_POST['duration_period'] ) ? eme_sanitize_request( $_POST['duration_period'] ) : '';
	$membership['type']            = isset( $_POST['type'] ) ? eme_sanitize_request( $_POST['type'] ) : '';
	if ( isset( $_POST['properties'] ) ) {
		$membership['properties'] = eme_kses( $_POST['properties'] );
	}
	// now for the select boxes, we need to set to 0 if not in the _POST
	$select_post_vars = [ 'use_captcha', 'use_recaptcha', 'use_hcaptcha', 'use_cfcaptcha', 'captcha_only_logged_out', 'create_wp_user' ];
	foreach ( $select_post_vars as $post_var ) {
		if ( ! isset( $_POST['properties'][ $post_var ] ) ) {
			$membership['properties'][ $post_var ] = 0;
		}
	}
	if ( $membership['properties']['use_captcha'] && ! function_exists( 'imagecreatetruecolor' ) ) {
		$membership['properties']['use_captcha'] = 0;
	}

	if ( isset( $_POST['start_date'] ) && eme_is_date( $_POST['start_date'] ) ) {
		$membership['start_date'] = eme_sanitize_request( $_POST['start_date'] );
	} else {
		$membership['start_date'] = '';
	}

	$eme_dyndata = eme_handle_dyndata_post_adminform();
	if ( ! empty( $eme_dyndata ) ) {
		$membership['properties']['dyndata'] = $eme_dyndata;
	}

	// some common sense logic
	if ( empty( $membership['duration_period'] ) ) {
		$membership['duration_count'] = 0;
	}
	if ( $membership['duration_period'] == 'forever' ) {
		$membership['properties']['reminder_days'] = '';
	}
	$membership['properties']['remove_pending_days'] = intval( $membership['properties']['remove_pending_days'] );

	if ( empty( get_option( 'eme_hcaptcha_for_forms' ) ) || empty( get_option( 'eme_hcaptcha_site_key' ) ) ) {
		$membership['properties']['use_hcaptcha'] = 0;
	}
	if ( empty( get_option( 'eme_cfcaptcha_for_forms' ) ) || empty( get_option( 'eme_cfcaptcha_site_key' ) ) ) {
		$membership['properties']['use_cfcaptcha'] = 0;
	}
	if ( empty( get_option( 'eme_recaptcha_for_forms' ) ) || empty( get_option( 'eme_recaptcha_site_key' ) ) ) {
		$membership['properties']['use_recaptcha'] = 0;
	}

	if ( isset( $membership['properties']['price'] ) ) {
		if ( ! is_numeric( $membership['properties']['price'] ) ) {
			$membership['properties']['price'] = 0;
			$message                          .= __( 'The membership price is not a valid price, resetted to 0', 'events-made-easy' ) . '<br>';
		}
	} else {
		$membership['properties']['price'] = 0;
	}
	if ( isset( $membership['properties']['extra_charge'] ) ) {
		if ( ! is_numeric( $membership['properties']['extra_charge'] ) ) {
			$membership['properties']['extra_charge'] = 0;
			$message                                 .= __( 'The extra charge for the membership is not a valid price, resetted to 0', 'events-made-easy' ) . '<br>';
		}
	} else {
		$membership['properties']['extra_charge'] = 0;
	}

	if ( $membership_id ) {
		$membership_id = eme_db_update_membership( $membership_id, $membership );
	} else {
		$membership_id = eme_db_insert_membership( $membership );
	}
	if ( $membership_id ) {
		eme_membership_store_answers( $membership_id );
		eme_upload_files( $membership_id, 'memberships' );
	}
	return [ $membership_id, $message ];
}

function eme_member_edit_layout( $member, $limited = 0 ) {
	global $plugin_page;

	if ( ! isset( $member['member_id'] ) ) {
		$action = 'add';
	} else {
		$action = 'edit';
	}
	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	?>
	<div class="wrap">
		<div id="poststuff">
		<div id="icon-edit" class="icon32">
		</div>

		<?php
		if ( $action == 'add' && ! empty( $_POST['membership_id'] ) ) {
			$membership_id = intval( $_POST['membership_id'] );
			eme_admin_edit_memberform( $member, $membership_id );
		}
		if ( $action == 'edit' ) {
			$membership_id = $member['membership_id'];
			eme_admin_edit_memberform( $member, $membership_id, $limited );
		}
		?>
		</div>
	</div>
	<?php
}

function eme_admin_edit_memberform( $member, $membership_id, $limited = 0 ) {
	global $plugin_page;
	$nonce_field             = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	$eme_member_status_array = eme_member_status_array();
	usleep( 2 );
	$form_id = uniqid();

	$membership = eme_get_membership( $membership_id );
	if ( ! empty( $membership['properties']['family_membership'] ) && ! empty( $member['related_member_id'] ) ) {
		$limited = 1;
	}
	// if limited, then a number of fields are read-only
	if ( $limited ) {
		$disabled = "disabled='true'";
	} else {
		$disabled = '';
	}

	if ( ! isset( $member['member_id'] ) ) {
		$action               = 'add';
		$h1_message           = __( 'Add member', 'events-made-easy' );
		$member['start_date'] = eme_get_start_date( $membership, $member );
		$member['end_date']   = eme_get_next_end_date( $membership, $member['start_date'] );
		$related_person_name  = '';
		$related_person_class = '';
	} else {
		$action     = 'edit';
		$h1_message = __( 'Edit member', 'events-made-easy' );
		if ( eme_is_empty_date( $member['start_date'] ) ) {
			$member['start_date'] = eme_get_start_date( $membership, $member );
			$member['end_date']   = eme_get_next_end_date( $membership, $member['start_date'] );
		}
		if ( ! empty( $member['related_member_id'] ) ) {
			$related_member = eme_get_member( $member['related_member_id'] );
			if ( $related_member ) {
				$related_person = eme_get_person( $related_member['person_id'] );
			} else {
				$related_person = null;
			}
		} else {
			$related_person = null;
		}
		if ( ! empty( $related_person ) ) {
			$related_person_name  = eme_format_full_name( $related_person['firstname'], $related_person['lastname'] );
			$related_person_class = "readonly='readonly' class='clearable x'";
		} else {
			$related_person_name  = '';
			$related_person_class = '';
		}
	}
	echo "<h1>$h1_message </h1>";
	if ( $action == 'edit' ) {
		echo "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $member['person_id'] ) . "' title='" . esc_html__( 'Click on this link to edit the corresponding person info', 'events-made-easy' ) . "'>" . esc_html__( 'Click on this link to edit the corresponding person info', 'events-made-easy' ) . '</a><br><br>';
	}
	?>
	<form name="eme-member-adminform" id="eme-member-adminform" method="post" autocomplete="off" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>" enctype='multipart/form-data'>
	<?php
	echo $nonce_field;
	if ( $action == 'add' ) {
	?>
		<input type='hidden' name='eme_admin_action' value='do_addmember'>
		<input type='hidden' name='person_id' id='person_id' value=''>
	<?php } else { ?>
		<input type='hidden' name='eme_admin_action' value='do_editmember'>
		<input type='hidden' name='person_id' id='person_id' value='<?php echo $member['person_id']; ?>'>
		<input type='hidden' name='member_id' id='member_id' value='<?php echo $member['member_id']; ?>'>
	<?php } ?>
	<table>
	<?php
	if ( $action == 'add' ) {
		?>
		<?php
		// we'll present the option to send a mail only if the membership is configured to do so
		$member_subject = $membership['properties']['new_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['new_body_text'] ) ) {
			$member_body = $membership['properties']['new_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['new_body_format_tpl'] );
		}
		if ( ! empty ( $member_subject ) && ! empty ( $member_body ) ) {
		?>
		<tr><td><?php esc_html_e( 'Send mail to new member?', 'events-made-easy' ); ?>
		</td><td>
		<?php echo eme_ui_select_binary( 1, 'send_mail', 0, 'nodynamicupdates' ); ?>
		</td></tr>
		<?php } ?>
		<tr><td>
		<?php esc_html_e( 'If you want, select an existing person to become a member', 'events-made-easy' ); ?>
		</td><td>
			<input type='text' id='chooseperson' name='chooseperson' placeholder="<?php esc_attr_e( 'Start typing a name', 'events-made-easy' ); ?>" class="nodynamicupdates">
		</td></tr>
	<?php } else { ?>
		<?php
		// we'll present the option to send a mail only if the membership is configured to do so
		$member_subject = $membership['properties']['updated_subject_format'];
                if ( ! eme_is_empty_string( $membership['properties']['updated_body_text'] ) ) {
                        $member_body = $membership['properties']['updated_body_text'];
                } else {
                        $member_body = eme_get_template_format_plain( $membership['properties']['updated_body_format_tpl'] );
                }
		if ( ! empty ( $member_subject ) && ! empty ( $member_body ) ) {
		?>
		<tr><td><?php esc_html_e( 'Send mail for changed member?', 'events-made-easy' ); ?>
		</td><td>
		<?php echo eme_ui_select_binary( 1, 'send_mail', 0, 'nodynamicupdates' ); ?>
		</td></tr>
		<?php } ?>
		<?php if ( empty( $membership['properties']['family_membership'] ) || ( ! empty( $membership['properties']['family_membership'] ) && empty( $member['related_member_id'] ) ) ) { ?>
		<tr><td>
			<?php esc_html_e( 'If you want, select an existing person to transfer this member to', 'events-made-easy' ); ?><br>
		</td><td>
			<input type='text' id='transferperson' name='transferperson' placeholder="<?php esc_attr_e( 'Start typing a name', 'events-made-easy' ); ?>" class="nodynamicupdates">
			<input type='hidden' name='transferto_personid' id='transferto_personid' value=''>
		</td></tr>
		<tr><td>
			<?php esc_html_e( 'If you want, select a different membership to transfer this member to: ', 'events-made-easy' ); ?>
		</td><td>
		<?php
			$memberships = eme_get_memberships( $membership['membership_id'] );
			echo eme_ui_select_key_value( '', 'transferto_membershipid', $memberships, 'membership_id', 'name', '&nbsp;', 0, 'nodynamicupdates' );
		?>
		<br>
		<?php
			echo "<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>";
			esc_html_e( 'Warning: if you transfer a member from one membership to another, make sure both memberships have the same index values for dynamic fields, otherwise the answers to those fields will either get mixed up or become empty.', 'events-made-easy' );
		?>
		</td></tr>
		<?php } ?>
	<?php } ?>
		<tr><td><?php esc_html_e( 'Membership', 'events-made-easy' ); ?></td>
		<td>
		<?php
			echo eme_esc_html( $membership['name'] );
		?>
	</td></tr>
	<?php if ( ! empty( $membership['properties']['family_membership'] ) ) { ?>
	<tr>
		<td style="vertical-align:top"><label for="chooserelatedmember"><?php esc_html_e( "'Head of the family' account", 'events-made-easy' ); ?></label></td>
		<td> <input type="hidden" name="related_member_id" id="related_member_id" value="<?php echo intval( $member['related_member_id'] ); ?>">
		<?php
		$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
		if ( $action == 'edit' && ! empty( $related_member_ids ) ) {
			esc_html_e( 'This member is head of the family for other members.', 'events-made-easy' );
			print '<br>' . esc_html__( 'Family members:', 'events-made-easy' );
			foreach ( $related_member_ids as $related_member_id ) {
				$related_member = eme_get_member( $related_member_id );
				if ( $related_member ) {
					$related_person = eme_get_person( $related_member['person_id'] );
					if ( $related_person ) {
						print "<br><a href='" . admin_url( "admin.php?page=eme-members&amp;eme_admin_action=edit_member&amp;member_id=$related_member_id" ) . "' title='" . esc_html__( 'Edit member', 'events-made-easy' ) . "'>" . eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'] ) ) . '</a>';
					}
				}
			}
		} else {
			?>
		<input type='text' id='chooserelatedmember' name='chooserelatedmember' placeholder="<?php esc_attr_e( 'Start typing a name', 'events-made-easy' ); ?>" value="<?php echo $related_person_name; ?>" <?php echo $related_person_class; ?>>
		<br><?php esc_html_e( "You can link this member to a 'head of the family' account, after which this member's start/end date and status are linked to the values of the head of the family and the below values for those fields are then ignored. This person will then no longer be charged for his membership too.", 'events-made-easy' ); ?>
			<?php
			if ( ! empty( $member['related_member_id'] ) ) {
				echo "<br><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>";
				esc_html_e( "Since this member relates to another family member, you can't edit the basic membership info. Please first remove the relationship (by emptying this field) and press save if you want to do that.", 'events-made-easy' );
			}
		}
		?>
		</td>
	</tr>
	<?php } ?>
		<tr><td><?php esc_html_e( 'Start date', 'events-made-easy' ); ?></td>
		<td>
			<input type='hidden' name='start_date' id='start_date' value='<?php echo $member['start_date']; ?>'>
		<?php
		if ( $limited ) {
			echo eme_localized_date( $member['start_date'], EME_TIMEZONE, 1 );
		} else {
			?>
			<input type='text' readonly='readonly' name='dp_start_date' id='dp_start_date' data-date='<?php echo eme_js_datetime( $member['start_date'] ); ?>' data-alt-field='start_date' class='eme_formfield_fdate'>
		<?php } ?>
	</td></tr>
	<tr><td><?php esc_html_e( 'End date', 'events-made-easy' ); ?></td>
		<td>
			<input type='hidden' name='end_date' id='end_date' value='<?php echo $member['end_date']; ?>'>
		<?php
		if ( $limited ) {
			echo eme_localized_date( $member['end_date'], EME_TIMEZONE, 1 );
		} else {
			?>
			<input type='text' readonly='readonly' name='dp_end_date' id='dp_end_date' data-date='<?php echo eme_js_datetime( $member['end_date'] ); ?>' data-alt-field='end_date' class='eme_formfield_fdate'>
		<?php } ?>
	</td></tr>
	<tr><td><?php esc_html_e( 'Member status calculated automatically', 'events-made-easy' ); ?></td>
	<td><?php echo eme_ui_select_binary( $member['status_automatic'], 'status_automatic', 0, 'nodynamicupdates', $disabled ); ?>
		<?php
		if ( $member['status_automatic'] && ! $member['paid'] && $action == 'edit' && empty( $member['related_member_id'] ) ) {
			echo "<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'Warning: membership is not paid for, so automatic status calculation will not happen!', 'events-made-easy' );
		}
		?>
		<?php echo "<p class='eme_smaller'>" . esc_html__( 'If set to automatic and the membership is paid for, the status will be recalculated on a daily basis.', 'events-made-easy' ) . '</p>'; ?>
		</td></tr>
		<tr><td><?php esc_html_e( 'Member status', 'events-made-easy' ); ?></td>
	<td><?php echo eme_ui_select( $member['status'], 'status', $eme_member_status_array, '', 0, 'nodynamicupdates', $disabled ); ?>
	</td></tr>
		<tr><td><?php esc_html_e( 'Has the member paid?', 'events-made-easy' ); ?></td>
	<td><?php echo eme_ui_select_binary( $member['paid'], 'paid', 0, 'nodynamicupdates', $disabled ); ?>
	</td></tr>
	<tr><td><?php esc_html_e( 'Last payment received date', 'events-made-easy' ); ?></td>
		<td>
			<input type='hidden' name='payment_date' id='payment_date' value='<?php echo $member['payment_date']; ?>'>
		<?php
		if ( $limited ) {
				echo eme_localized_datetime( $member['payment_date'], EME_TIMEZONE, 1 );
		} else {
			?>
			<input type='text' readonly='readonly' name='dp_payment_date' id='dp_payment_date' data-date='<?php echo eme_js_datetime( $member['payment_date'] ); ?>' data-alt-field='payment_date' class='eme_formfield_fdatetime'>
		<?php } ?>
		<br>
		<?php echo "<p class='eme_smaller'>" . esc_html__( 'This indicates the last date a payment was received. Changing this will only change that date, no new payment will actually be processed and the membership state is not influenced by this.', 'events-made-easy' ) . '</p>'; ?>
	</td></tr>
			<?php
				//if ($action == "edit") {
				//  $member_id=$member['member_id'];
			// $files_title=__('Uploaded files related to the person', 'events-made-easy');
			// print eme_get_uploaded_files_tr($member['person_id'],"people",$files_title,1);
			// $files_title=__('Uploaded files related to the membership', 'events-made-easy');
			// print eme_get_uploaded_files_tr($member_id,"members",$files_title);
				//}
			?>

		</table><br><br>
		<?php
		esc_html_e( 'Member info', 'events-made-easy' );
		echo '<br>';
		echo eme_member_form( $member, $membership_id, 1, $form_id );
		?>
		</form>
	<?php
}

function eme_member_form( $member, $membership_id, $from_backend = 0, $form_id = 0 ) {
	$form_html  = '';
	$membership = eme_get_membership( $membership_id );
	if ( empty( $membership ) ) {
		$form_html  = "<div id='eme-member-addmessage-error-$form_id' class='eme-message-error eme-member-message-error'>";
		$form_html .= sprintf( __( 'No membership with ID %d found', 'events-made-easy' ), $membership_id );
		$form_html .= '</div>';
		return $form_html;
	}
	if ( $membership['properties']['registration_wp_users_only'] && ! is_user_logged_in() ) {
		$form_html  = "<div id='eme-member-addmessage-error-$form_id' class='eme-message-error eme-member-message-error'>";
		$format     = get_option( 'eme_membership_login_required_string' );
		$form_html .= eme_replace_membership_placeholders( $format, $membership );
		$form_html .= '</div>';
		return $form_html;
	}
	$wp_id      = 0;
	$form_class = '';
	if ( $member['person_id'] ) {
		$person = eme_get_person( $member['person_id'] );
		$wp_id  = $person['wp_id'];
	} elseif ( ! $from_backend ) {
		$wp_id = get_current_user_id();
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			$search_tables = get_option( 'eme_autocomplete_sources' );
			if ( $search_tables != 'none' ) {
				wp_enqueue_script( 'eme-autocomplete-form' );
			}
		}
		if ( ! is_user_logged_in() && get_option('eme_rememberme')) {
			wp_enqueue_script( 'eme-rememberme' );
			$form_class = "class='eme-rememberme'";
		}
	}
	// to make sure wp_id has a valid value for non-logged users
	if ( ! $wp_id ) {
		$wp_id = 0;
	}
	if ( ! $from_backend ) {
		// we sleep for 2 microseconds, to be sure that uniqid gives another value
		usleep( 2 );
		$form_id    = uniqid();
		$form_html  = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
		<div id='eme-member-addmessage-ok-$form_id' class='eme-message-success eme-member-message eme-member-message-success eme-hidden'></div><div id='eme-member-addmessage-error-$form_id' class='eme-message-error eme-member-message eme-member-message-error eme-hidden'></div><div id='div_eme-payment-form-$form_id' class='eme-payment-form'></div><div id='div_eme-member-form-$form_id' style='display: none' class='eme-showifjs'><form name='eme-member-form' id='$form_id' method='post' $form_class action='#'>";
		$form_html .= wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
		$form_html .= "<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>";
	}
	$form_html .= "<input type='hidden' id='membership_id' name='membership_id' value='$membership_id'>";
	$form_html .= "<input type='hidden' name='wp_id' value='$wp_id' class='dynamicupdates'>";

	$format = null;
	if ( ! eme_is_empty_string( $membership['properties']['member_form_text'] ) ) {
		$format = $membership['properties']['member_form_text'];
	} elseif ( ! empty( $membership['properties']['member_form_tpl'] ) ) {
		$format = eme_get_template_format( $membership['properties']['member_form_tpl'] );
	}
	if ( eme_is_empty_string( $format ) ) {
		$format = "<table class='eme-rsvp-form'>
            <tr><th scope='row'>" . esc_html__( 'Last name', 'events-made-easy' ) . "*:</th><td>#_LASTNAME</td></tr>
            <tr><th scope='row'>" . esc_html__( 'First name', 'events-made-easy' ) . "*:</th><td>#REQ_FIRSTNAME</td></tr>
            <tr><th scope='row'>" . esc_html__( 'Email', 'events-made-easy' ) . "*:</th><td>#_EMAIL</td></tr>
            </table>
            #_SUBMIT
            ";
	}

	$form_html .= eme_replace_membership_formfields_placeholders( $membership, $member, $format );

	if ( ! $from_backend ) {
		$form_html .= '</form></div>';
	}
	return $form_html;
}

function eme_membership_edit_layout( $membership, $message = '' ) {
	global $plugin_page;

	if ( ! isset( $membership['membership_id'] ) ) {
		$is_new_membership = 1;
	} else {
		$is_new_membership = 0;
	}
	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	?>
	<div class="wrap">
		<div id="icon-edit" class="icon32">
		</div>

		<h1>
		<?php
		if ( $is_new_membership == 1 ) {
			esc_html_e( 'Add a membership definition', 'events-made-easy' );
		} else {
			echo sprintf( __( "Edit membership '%s'", 'events-made-easy' ), eme_esc_html( $membership['name'] ) );
		}
		?>
			</h1>

		<?php if ( $message != '' ) { ?>
			<div id="message" class="updated notice notice-success is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>


		<div id="ajax-response"></div>
		<form name="membershipForm" id="membershipForm" method="post" autocomplete="off" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>"  enctype="multipart/form-data" class="validate">
		<?php echo $nonce_field; ?>
		<?php if ( $is_new_membership == 1 ) { ?>
		<input type="hidden" name="eme_admin_action" value="do_addmembership">
		<?php } else { ?>
		<input type="hidden" name="eme_admin_action" value="do_editmembership">
		<input type="hidden" name="membership_id" value="<?php echo $membership['membership_id']; ?>">
		<?php } ?>

		<!-- we need titlediv and title for qtranslate as ID -->
		<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div id="membership-tabs" style="display: none;">
					<ul>
					<li><a href="#tab-membershipdetails"><?php esc_html_e( 'Membership details', 'events-made-easy' ); ?></a></li>
					<li><a href="#tab-mailformats"><?php esc_html_e( 'Mail format settings', 'events-made-easy' ); ?></a></li>
					<li><a href="#tab-customfields"><?php esc_html_e( 'Custom fields', 'events-made-easy' ); ?></a></li>
					</ul>
					<div id="tab-membershipdetails">
					<?php eme_meta_box_div_membershipdetails( $membership, $is_new_membership ); ?>
					</div>
					<div id="tab-mailformats">
					<?php eme_meta_box_div_membershipmailformats( $membership ); ?>
					</div>
					<div id="tab-customfields">
					<?php eme_meta_box_div_membershipcustomfields( $membership ); ?>
					</div>
				</div> <!-- end membership-tabs -->
				<p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php if ( $is_new_membership == 1 ) { esc_html_e( 'Add membership', 'events-made-easy' ); } else { esc_html_e( 'Update membership', 'events-made-easy' ); } ?>"></p>
			</div>
		</div>
		</div>
		</form>
	</div>
	<?php
}

function eme_meta_box_div_membershipdetails( $membership, $is_new_membership ) {
	$templates_array            = eme_get_templates_array_by_id( 'membershipform' );
	$templates_array2           = eme_get_templates( 'membershipform' );
	$currency_array             = eme_currency_array();
	$type_array                 = eme_membership_types();
	$duration_array             = eme_membership_durations();
	$registration_wp_users_only = ( $membership['properties']['registration_wp_users_only'] ) ? "checked='checked'" : '';
	$captcha_only_logged_out    = ( $membership['properties']['captcha_only_logged_out'] ) ? "checked='checked'" : '';
	$use_captcha                = ( $membership['properties']['use_captcha'] ) ? "checked='checked'" : '';
	$use_recaptcha              = ( $membership['properties']['use_recaptcha'] ) ? "checked='checked'" : '';
	$use_hcaptcha               = ( $membership['properties']['use_hcaptcha'] ) ? "checked='checked'" : '';
	$use_cfcaptcha              = ( $membership['properties']['use_cfcaptcha'] ) ? "checked='checked'" : '';
	$attendancerecord           = ( $membership['properties']['attendancerecord'] ) ? "checked='checked'" : '';
	$allow_renewal              = ( $membership['properties']['allow_renewal'] ) ? "checked='checked'" : '';
	$family_membership          = ( $membership['properties']['family_membership'] ) ? "checked='checked'" : '';
	$create_wp_user             = ( $membership['properties']['create_wp_user'] ) ? "checked='checked'" : '';
	$membership_discount        = ( $membership['properties']['discount'] ) ? $membership['properties']['discount'] : '';
	$membership_discountgroup   = ( $membership['properties']['discountgroup'] ) ? $membership['properties']['discountgroup'] : '';
	$discount_arr               = [];
	$dgroup_arr                 = [];
	if ( ! empty( $membership_discount ) ) {
		$discount_arr = [ $membership_discount => eme_get_discount_name( $membership_discount ) ];
	}
	if ( ! empty( $membership_discountgroup ) ) {
		$dgroup_arr = [ $membership_discountgroup => eme_get_dgroup_name( $membership_discountgroup ) ];
	}

	?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Name', 'events-made-easy' ); ?></label></td>
	<td><input required='required' id="name" name="name" type="text" value="<?php echo eme_esc_html( $membership['name'] ); ?>" size="40"></td>
	</tr>
	<tr>
	<td><label for="description"><?php esc_html_e( 'Description', 'events-made-easy' ); ?></label></td>
	<td><input id="description" name="description" type="text" value="<?php echo eme_esc_html( $membership['description'] ); ?>" size="40"></td>
	</tr>
	<tr>
	<td><label for="type"><?php esc_html_e( 'Type', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['type'], 'type', $type_array ); ?></td>
	</tr>
	<tr id='startdate'>
	<td><label for="start_date"><?php esc_html_e( 'Start date', 'events-made-easy' ); ?></label></td>
	<td><input type='hidden' name='start_date' id='start_date' value='<?php echo $membership['start_date']; ?>'>
		<input type='text' readonly='readonly' name='dp_start_date' id='dp_start_date' data-date='<?php echo eme_js_datetime( $membership['start_date'] ); ?>' data-alt-field='start_date' class='eme_formfield_fdate'>
	</td>
	</tr>
	<tr>
	<td><label for="duration_count"><?php esc_html_e( 'Duration period', 'events-made-easy' ); ?></label></td>
	<td><input type="integer" id="duration_count" name="duration_count" value="<?php echo $membership['duration_count']; ?>" size="4"><?php echo eme_ui_select( $membership['duration_period'], 'duration_period', $duration_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'Once this duration period has passed, the membership start date for new members will be increased by the passed period.', 'events-made-easy' ); ?>
	</td>
	</tr>
	<tr id='freeperiod'>
	<td><label for="properties[one_free_period]"><?php esc_html_e( 'One extra free period', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select_binary( $membership['properties']['one_free_period'], 'properties[one_free_period]', 0 ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'When someone becomes a member, the end date is the end date of the current period calculated for this membership.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If you want new members to get one extra membership period for free, set this option.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='graceperiod'>
	<td><label for="properties[grace_period]"><?php esc_html_e( 'Grace period', 'events-made-easy' ); ?></label></td>
	<td><input type="text" id="properties[grace_period]" name="properties[grace_period]" value="<?php echo $membership['properties']['grace_period']; ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'After a membership has expired, people can still be considered as a member until this many days have passed.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'After the mentioned number of days have passed, the membership will be set to expired.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='reminder'>
	<td><label for="properties[reminder_days]"><?php esc_html_e( 'Reminder', 'events-made-easy' ); ?></label></td>
	<td><input type="text" id="properties[reminder_days]" name="properties[reminder_days]" value="<?php echo $membership['properties']['reminder_days']; ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'Set the number of days before membership expiration a reminder will be sent out.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If you want to send out multiple reminders, seperate the days here by commas. This can contain negative numbers too, if you want to send out a reminder past the membership end date, e.g. during the grace period.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='remove_pending'>
	<td><label for="properties[remove_pending_days]"><?php esc_html_e( 'Automatically remove pending members', 'events-made-easy' ); ?></label></td>
	<td><input type="text" id="properties[remove_pending_days]" name="properties[remove_pending_days]" value="<?php echo $membership['properties']['remove_pending_days']; ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'Set the number of days after which pending members are automatically removed. Leave empty or 0 for no automatic removal.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr>
	<td><label for="allow_renewal"><?php esc_html_e( 'Allow membership renewal', 'events-made-easy' ); ?></label></td>
	<td><input id="allow_renewal" name="properties[allow_renewal]" type="checkbox" <?php echo $allow_renewal; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want members to be able to renew/extend the membership after payment via the unique payment url generated by the member placeholder #_PAYMENT_URL or the generic placeholder #_MEMBERSHIP_PAYMENT_URL{xx} (with xx being the membership id, see the doc).', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='tr_renewal_cutoff_days'>
	<td><label for="properties[renewal_cutoff_days]"><?php esc_html_e( 'Renewal for active members based on end date', 'events-made-easy' ); ?></label></td>
	<td><input type="text" id="properties[renewal_cutoff_days]" name="properties[renewal_cutoff_days]" value="<?php echo $membership['properties']['renewal_cutoff_days']; ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'Allow active members to pay for a renewal only if the membership end date is less than the mentioned number of days away. This prevents people from payment many times in a row. Enter 0 or nothing to disable this (the default).', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<?php if ( ! empty( get_option( 'eme_recaptcha_for_forms' ) ) && ! empty( get_option( 'eme_recaptcha_site_key' ) ) ) : ?>
	<tr>
	<td><label for="properties[use_recaptcha]"><?php esc_html_e( 'Google reCAPTCHA', 'events-made-easy' ); ?></label></td>
	<td><input id="use_recaptcha" name="properties[use_recaptcha]" type="checkbox" <?php echo $use_recaptcha; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want to use the Google reCAPTCHA on the membership signup form.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If this option is checked, make sure to use #_RECAPTCHA in your membership signup form. If not present, it will be added just above the submit button.', 'events-made-easy' ); ?></span></p>
	</td>
	</tr>
<?php endif; ?>
	<?php if ( ! empty( get_option( 'eme_hcaptcha_for_forms' ) ) && ! empty( get_option( 'eme_hcaptcha_site_key' ) ) ) : ?>
	<tr>
	<td><label for="use_hcaptcha"><?php esc_html_e( 'hCaptcha', 'events-made-easy' ); ?></label></td>
	<td><input id="use_hcaptcha" name="properties[use_hcaptcha]" type="checkbox" <?php echo $use_hcaptcha; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want to use the hCaptcha on the membership signup form.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If this option is checked, make sure to use #_HCAPTCHA in your membership signup form. If not present, it will be added just above the submit button.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
<?php endif; ?>
	<?php if ( ! empty( get_option( 'eme_cfcaptcha_for_forms' ) ) && ! empty( get_option( 'eme_cfcaptcha_site_key' ) ) ) : ?>
	<tr>
	<td><label for="use_cfcaptcha"><?php esc_html_e( 'Cloudflare Turnstile', 'events-made-easy' ); ?></label></td>
	<td><input id="use_cfcaptcha" name="properties[use_cfcaptcha]" type="checkbox" <?php echo $use_cfcaptcha; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want to use Cloudflare Turnstile on the membership signup form.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If this option is checked, make sure to use #_CFCAPTCHA in your membership signup form. If not present, it will be added just above the submit button.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
<?php endif; ?>
	<tr>
	<td><label for="use_captcha"><?php esc_html_e( 'Captcha', 'events-made-easy' ); ?></label></td>
	<td><input id="use_captcha" name="properties[use_captcha]" type="checkbox" <?php echo $use_captcha; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want to use the captcha on the membership signup form.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If this option is checked, make sure to use #_CAPTCHA in your membership signup form. If not present, it will be added just above the submit button.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr>
	<td><label for="captcha_only_logged_out"><?php esc_html_e( 'Only use captcha for logged out users?', 'events-made-easy' ); ?></label></td>
	<td><input id="captcha_only_logged_out" name="properties[captcha_only_logged_out]" type="checkbox" <?php echo $captcha_only_logged_out; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'If this option is checked, the captcha will only be used for logged out users.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr>
	<td><label for="create_wp_user"><?php esc_html_e( 'Create WP user after signup', 'events-made-easy' ); ?></label></td>
	<td><input id="create_wp_user" name="properties[create_wp_user]" type="checkbox" <?php echo $create_wp_user; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'This will create a WP user after the membership signup is completed, as if the person registered in WP itself. This will only create a user if the person signing up was not logged in and the email is not yet taken by another WP user.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr>
	<td><label for="price"><?php esc_html_e( 'Price', 'events-made-easy' ); ?></label></td>
	<td><input id="price" name="properties[price]" type="text" value="<?php echo eme_esc_html( $membership['properties']['price'] ); ?>" size="4">
	<span class="eme_smaller"><br><?php esc_html_e( 'Use the point as decimal separator', 'events-made-easy' ); ?></span>
	</td>
	</tr>
	<tr>
	<td><label for="extra_charge"><?php esc_html_e( 'Extra charge for new members', 'events-made-easy' ); ?></label></td>
	<td><input id="extra_charge" name="properties[extra_charge]" type="text" value="<?php echo eme_esc_html( $membership['properties']['extra_charge'] ); ?>" size="4">
	<span class="eme_smaller"><br><?php esc_html_e( 'Use the point as decimal separator', 'events-made-easy' ); ?></span>
	</td>
	</tr>
	<tr>
	<td><label for="properties[currency]"><?php esc_html_e( 'Currency', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['currency'], 'properties[currency]', $currency_array ); ?></td>
	</tr>
	<tr>
	<td><label for="vat_pct"><?php esc_html_e( 'VAT percentage', 'events-made-easy' ); ?></label></td>
	<td><input id="vat_pct" name="properties[vat_pct]" type="text" value="<?php echo eme_esc_html( $membership['properties']['vat_pct'] ); ?>" size="4">%
		<br><p class='eme_smaller'><?php esc_html_e( 'The price you indicate for memberships is VAT included, special placeholders are foreseen to indicate the price without VAT.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='row_discount'>
	<td><label for='properties[discount]'><?php esc_html_e( 'Discount to apply', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['discount'], 'properties[discount]', $discount_arr, '', 0, 'eme_select2_discounts_class' ); ?><p class="eme_smaller"><?php esc_html_e( 'The discount name you want to apply (is overridden by discount group if used).', 'events-made-easy' ); ?></p></td>
	</tr>
	<tr id='row_discountgroup'>
	<td><label for='properties[discountgroup]'><?php esc_html_e( 'Discount group to apply', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['discountgroup'], 'properties[discountgroup]', $dgroup_arr, '', 0, 'eme_select2_dgroups_class' ); ?><p class="eme_smaller"><?php esc_html_e( 'The discount group name you want applied (overrides the discount).', 'events-made-easy' ); ?></p></td>
	</tr>
	<tr>
	<td><label for="properties[contact_id]"><?php esc_html_e( 'Contact person', 'events-made-easy' ); ?></label></td>
	<td>
	<?php
	wp_dropdown_users(
	    [
			'name'             => 'properties[contact_id]',
			'show_option_none' => __( 'WP Admin', 'events-made-easy' ),
			'selected'         => $membership['properties']['contact_id'],
		]
	);
	?>
		</td>
	</tr>
	<tr>
	<td><label for="registration_wp_users_only"><?php esc_html_e( 'Logged-in users only', 'events-made-easy' ); ?></label></td>
	<td><input id="registration_wp_users_only" name='properties[registration_wp_users_only]' value='1' type='checkbox' <?php echo $registration_wp_users_only; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Require users to be logged-in before being able to sign up for this membership.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id='attendancerecord'>
	<td><label for="attendancerecord"><?php esc_html_e( 'Keep attendance records?', 'events-made-easy' ); ?></label></td>
	<td><input id="attendancerecord" name="properties[attendancerecord]" type="checkbox" <?php echo $attendancerecord; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want an attendance record to be kept everytime the member QRCODE is scanned by an EME admin.', 'events-made-easy' ); ?>
	</td>
	</tr>
	<tr id="member_form_tpl">
	<td><label for="properties[member_form_tpl]"><?php esc_html_e( 'Member Form:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select_key_value( $membership['properties']['member_form_tpl'], 'properties[member_form_tpl]', $templates_array2, 'id', 'name', __( 'Please select a template', 'events-made-easy' ), 1 ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'This is the form that will be shown when a new member wants to sign up for this membership.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'The template should at least contain the placeholders #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT. If not, the form will not be shown. If empty, a simple default will be used.', 'events-made-easy' ); ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_member_form_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['member_form_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_member_form_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[member_form_text]', $membership['properties']['member_form_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for="family_membership"><?php esc_html_e( 'Ask for family member info when someone signs up', 'events-made-easy' ); ?></label></td>
	<td><input id="family_membership" name="properties[family_membership]" type="checkbox" <?php echo $family_membership; ?>>
		<br><p class='eme_smaller'><?php esc_html_e( 'Select this option if you want to ask for extra info for each family member of the person that signs up. These will also become a member but payment will only be handled by the initial member that signs up. The membership member form must include the placeholder "#_FAMILYCOUNT" to ask for the number of extra family members and "#_FAMILYMEMBERS" to ask for the extra family members info.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id="tr_family_maxmembers">
	<td><label for="family_maxmembers"><?php esc_html_e( 'Maximum number of family members', 'events-made-easy' ); ?></label></td>
	<td><input id="family_maxmembers" name="properties[family_maxmembers]" type="number" value="<?php echo intval( $membership['properties']['family_maxmembers'] ); ?>" size="4">
		<br><p class='eme_smaller'><?php esc_html_e( 'The maximum number of family members allowed to sign up.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id="tr_familymember_form_tpl">
	<td><label for="properties[familymember_form_tpl]"><?php esc_html_e( 'Family Member Form:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select_key_value( $membership['properties']['familymember_form_tpl'], 'properties[familymember_form_tpl]', $templates_array2, 'id', 'name', __( 'Please select a template', 'events-made-easy' ), 1 ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'This is the form that will be shown/repeated for the family members when a new member wants to sign up for this membership.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'The template should at least contain the placeholders #_LASTNAME, #_FIRSTNAME, #_EMAIL. If not, the form will not be shown. If empty, a simple default will be used.', 'events-made-easy' ); ?></p>
		<br><?php esc_html_e( 'The template may contain the person placeholders #_LASTNAME, #_FIRSTNAME, #_EMAIL, #_OPT_IN (or #_OPT_OUT), #_BIRTHDATE, #_BIRTHPLACE, #_PHONE and placeholders referring to custom person fields, nothing else. #_LASTNAME, #_FIRSTNAME are required. If #_EMAIL, #_PHONE, #_OPT_IN (or #_OPT_OUT) is not set, it is copied over from the person signing up. The address info is always copied over from the person signing up.', 'events-made-easy' ); ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_familymember_form_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['familymember_form_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_familymember_form_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[familymember_form_text]', $membership['properties']['familymember_form_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for="properties[member_added_tpl]"><?php esc_html_e( 'Member Added Message:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['member_added_tpl'], 'properties[member_added_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php echo esc_html__( 'The format of the text shown after someone subscribed. If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . esc_html__( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a>'; ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_member_added_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['member_added_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_member_added_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[member_added_text]', $membership['properties']['member_added_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for="properties[payment_form_header_tpl]"><?php esc_html_e( 'Payment Form Header:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['payment_form_header_tpl'], 'properties[payment_form_header_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php echo esc_html__( 'The format of the text shown above the payment buttons. If left empty, a default message will be shown.', 'events-made-easy' ) . '<br>' . esc_html__( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a>'; ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_payment_form_header_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['payment_form_header_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_payment_form_header_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[payment_form_header_text]', $membership['properties']['payment_form_header_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for="properties[payment_form_footer_tpl]"><?php esc_html_e( 'Payment Form Footer:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['payment_form_footer_tpl'], 'properties[payment_form_footer_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php echo esc_html__( 'The format of the text shown below the payment buttons. Default: empty.', 'events-made-easy' ) . '<br>' . esc_html__( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a>'; ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_payment_form_footer_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['payment_form_footer_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_payment_form_footer_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[payment_form_footer_text]', $membership['properties']['payment_form_footer_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for="properties[payment_success_tpl]"><?php esc_html_e( 'Payment Success Message:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['payment_success_tpl'], 'properties[payment_success_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php echo esc_html__( 'The message shown when the payment is succesfull for membership signup. Default: see global EME settings for payments, subsection "General options".', 'events-made-easy' ) . '<br>' . esc_html__( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a>'; ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_payment_success_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['payment_success_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_payment_success_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[payment_success_text]', $membership['properties']['payment_success_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr>
	<td><label for='properties[addpersontogroup]'><?php esc_html_e( 'Group to add people to', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_multiselect_key_value( $membership['properties']['addpersontogroup'], 'properties[addpersontogroup]', eme_get_static_groups(), 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class' ); ?><br><p class='eme_smaller'><?php esc_html_e( 'The group you want people to automatically become a member of when they subscribe.', 'events-made-easy' ); ?></p></td>
	</tr>
	<tr>
	<td><label for='properties[member_template_id]'><?php esc_html_e( 'Membership card PDF template', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select_key_value( $membership['properties']['member_template_id'], 'properties[member_template_id]', eme_get_templates( 'pdf', 1 ), 'id', 'name', '&nbsp;' ); ?><br>
		<p class='eme_smaller'><?php esc_html_e( 'This optional template is used to send a PDF attachment in the mail when the membership is paid for.', 'events-made-easy' ); ?><br>
	</td>
	</tr>
	<tr>
	<td><?php esc_html_e( 'Payment methods', 'events-made-easy' ); ?></label></td>
	<td>
	<?php
	esc_html_e( 'If no payment method is selected, the "Member Added Message" will be shown. Otherwise the "Member Added Message" will be shown and after some seconds the user gets redirected to the payment page (see the generic EME settings on the redirection timeout and more payment settings).', 'events-made-easy' );
	echo '<br>';
	$found_methods        = eme_get_configured_pgs();
	$count_configured_pgs = count( $found_methods );
	$pgs                  = eme_payment_gateways();
	// for memberships, the offline payment is configured per membership (not globally), so remove that from the found methods
	if ( isset( $found_methods['offline'] ) ) {
		unset( $found_methods['offline'] );
	}
	foreach ( $pgs as $pg => $pg_desc ) {
		// offline is handled per membership (not globally), so continue
		if ( $pg == 'offline' ) {
			continue;
		}
			// if it is a new membership and there's only one pg configured, select it by default
		if ( $is_new_membership && $count_configured_pgs == 1 && $found_methods[0] == $pg ) {
				$membership['properties'][ 'use_' . $pg ] = 1;
		}
		if ( ! in_array( $pg, $found_methods ) ) {
				continue;
		}
		echo eme_ui_checkbox_binary( $membership['properties'][ 'use_' . $pg ], 'properties[use_' . $pg . ']', $pg_desc );
			echo '<br>';
	}

	echo eme_ui_checkbox_binary( $membership['properties']['use_offline'], 'properties[use_offline]', __( 'Offline', 'events-made-easy' ) );
	echo '<br>';
	if ( empty( $found_methods ) ) {
			esc_html_e( 'No payment methods configured yet. Go in the EME payment settings and configure some.', 'events-made-easy' );
	}

	?>
		<p class='eme_smaller'><?php esc_html_e( 'If one or more payment methods are selected, the person signing up will be redirected to a payment page. In that case, make sure to check the different payment templates defined for this membership.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr id="tr_offline">
	<td><label for="properties[offline_payment_tpl]"><?php esc_html_e( 'Offline Payment Format:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['offline_payment_tpl'], 'properties[offline_payment_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php echo esc_html__( 'The format of the text shown for the offline payment method. Default: empty.', 'events-made-easy' ) . '<br>' . esc_html__( 'For all possible placeholders, see ', 'events-made-easy' ) . "<a target='_blank' href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a>'; ?></p>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_offline_payment_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['offline_payment_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_offline_payment_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[offline_payment_text]', $membership['properties']['offline_payment_text'], 1, 0 ); ?>
		</div>
	</td>
	</tr>
	<tr id="tr_skippaymentoptions">
	<td><label for="properties[skippaymentoptions]"><?php esc_html_e( 'Skip payment methods after registration:', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_checkbox_binary( $membership['properties']['skippaymentoptions'], 'properties[skippaymentoptions]', __( 'Skip payment methods', 'events-made-easy' ) );
                  esc_html_e( 'If you want to skip the possibility to pay immediately after registration, select this option. This might be useful if you for example want to approve unpaid members internally and only then send them the payment link using #_PAYMENT_URL (for example via the Reminder template).', 'events-made-easy' ); ?>
	</td>
	</tr>
	</table>
	<br>
	<br>
	<?php

	$templates_array = eme_get_templates_array_by_id( 'membershipform' );
	if ( isset( $membership['properties']['dyndata'] ) ) {
		$eme_data = $membership['properties']['dyndata'];
	} else {
		$eme_data = [];
	}
	// for new memberships there's no membership id
	if ( isset( $membership['membership_id'] ) ) {
		$used_groupingids = eme_get_membership_cf_answers_groupingids( $membership['membership_id'] );
	} else {
		$used_groupingids = [];
	}
	eme_dyndata_adminform( $eme_data, $templates_array, $used_groupingids );
	$eme_membership_dyndata_all_fields = ( $membership['properties']['dyndata_all_fields'] ) ? "checked='checked'" : '';
	?>
	<div>
		<br>
		<b><?php esc_html_e( 'Dynamic data check on every field', 'events-made-easy' ); ?></b>
		<input id="properties[dyndata_all_fields]" name='properties[dyndata_all_fields]' value='1' type='checkbox' <?php echo $eme_membership_dyndata_all_fields; ?>>
		<span class="eme_smaller"><br><?php esc_html_e( 'By default the dynamic data check only happens for the fields mentioned in your dynamic data condition if those are present in your membership form definition. Using this option, you can use all membership placeholders, even if not defined in your membership form. The small disadvantage is that more requests will be made to the backend, so use only when absolutely needed.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If your membership uses a discount of type code and you want the dynamic price (using #_DYNAMICPRICE) to be updated taking that discount into account too, then also check this option.', 'events-made-easy' ); ?>
		</span>
	</div>
	<?php
}

function eme_meta_box_div_membershipmailformats( $membership ) {
	
	$templates_array = eme_get_templates_array_by_id( 'membershipmail' );
	?>
<div id="tab-mailformats">
<div id="mailformats-accordion">

	<h3><?php esc_html_e( 'New member email', 'events-made-easy' ); ?></h3>
		<div>
	<img style='vertical-align: middle;' src='<?php echo esc_url(EME_PLUGIN_URL); ?>images/warning.png' alt='warning'><?php esc_html_e( 'Warning: when the membership is configured to ask for family member info, this mail is NOT sent to each of the family members, just the member that is signing up.', 'events-made-easy' ); ?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'New member email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[new_subject_format]" name="properties[new_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['new_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the person signing up as a member.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'New member email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['new_body_format_tpl'], 'properties[new_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the person signing up as a member.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_new_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['new_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_new_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[new_body_text]', $membership['properties']['new_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
	<tr><td><?php esc_html_e( 'New member mail attachments', 'events-made-easy' ); ?></td>
	<td>
<span id="newmember_attach_links">
	<?php
	$attachment_ids = $membership['properties']['newmember_attach_ids'];
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
	<input type="hidden" name="properties[newmember_attach_ids]" id="eme_newmember_attach_ids" value="<?php echo $attachment_ids; ?>">
	<input type="button" name="newmember_attach_button" id="newmember_attach_button" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>" class="button-secondary action">
	<input type="button" name="newmember_remove_attach_button" id="newmember_remove_attach_button" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>" class="button-secondary action">
	<br><?php esc_html_e( 'Optionally add attachments to the mail when a new member signs up.', 'events-made-easy' ); ?>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson new member email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[contact_new_subject_format]" name="properties[contact_new_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['contact_new_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the contactperson when someone signes up as a member.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson new member email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['contact_new_body_format_tpl'], 'properties[contact_new_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the contactperson when someone signes up as a member.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_contact_new_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['contact_new_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_contact_new_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[contact_new_body_text]', $membership['properties']['contact_new_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

	<h3><?php esc_html_e( 'Member updated Email', 'events-made-easy' ); ?></h3>
		<div>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Updated member email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[updated_subject_format]" name="properties[updated_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['updated_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the member upon changes.', 'events-made-easy' ); ?></p>
		<br><p class='eme_smaller'><?php esc_html_e( 'Currently only used when a member is manually marked as unpaid.', 'events-made-easy' ); ?></p>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Updated member email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['updated_body_format_tpl'], 'properties[updated_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the member upon changes.', 'events-made-easy' ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'Currently only used when a member is manually marked as unpaid.', 'events-made-easy' ); ?></p><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_updated_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['updated_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_updated_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[updated_body_text]', $membership['properties']['updated_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

	<h3><?php esc_html_e( 'Membership extended email', 'events-made-easy' ); ?></h3>
		<div>
	<img style='vertical-align: middle;' src='<?php echo esc_url(EME_PLUGIN_URL); ?>images/warning.png' alt='warning'><?php esc_html_e( 'Warning: when the membership is configured to ask for family member info, this mail is ALSO sent to each of the family members.', 'events-made-easy' ); ?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership extended email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[extended_subject_format]" name="properties[extended_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['extended_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the member when the membership is extended.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership extended email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['extended_body_format_tpl'], 'properties[extended_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the member when the membership is extended.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_extended_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['extended_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_extended_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[extended_body_text]', $membership['properties']['extended_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
	</table>
	</div>

	<h3><?php esc_html_e( 'Membership paid email', 'events-made-easy' ); ?></h3>
		<div>
	<img style='vertical-align: middle;' src='<?php echo esc_url(EME_PLUGIN_URL); ?>images/warning.png' alt='warning'><?php esc_html_e( 'Warning: when the membership is configured to ask for family member info, this mail is ALSO sent to each of the family members.', 'events-made-easy' ); ?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership paid email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[paid_subject_format]" name="properties[paid_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['paid_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the member when marked as paid.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership paid email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['paid_body_format_tpl'], 'properties[paid_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the member when marked as paid.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_paid_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['paid_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_paid_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[paid_body_text]', $membership['properties']['paid_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson membership paid email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[contact_paid_subject_format]" name="properties[contact_paid_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['contact_paid_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the contactperson after a member is marked as paid.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson membership paid email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['contact_paid_body_format_tpl'], 'properties[contact_paid_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the contactperson after a member is marked as paid.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_contact_paid_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['contact_paid_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_contact_paid_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[contact_paid_body_text]', $membership['properties']['contact_paid_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

	<h3><?php esc_html_e( 'Membership reminder email', 'events-made-easy' ); ?></h3>
		<div>
	<img style='vertical-align: middle;' src='<?php echo esc_url(EME_PLUGIN_URL); ?>images/warning.png' alt='warning'><?php esc_html_e( 'Warning: when the membership is configured to ask for family member info, this mail is NOT sent to each of the family members, just the head of the family.', 'events-made-easy' ); ?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership reminder email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[reminder_subject_format]" name="properties[reminder_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['reminder_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the member when membership is about to expire. These reminders will be sent once a day, based on the reminder settings of the defined membership.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'This reminder email does NOT take into account an optional grace period.', 'events-made-easy' ); ?>
		</p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership reminder email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['reminder_body_format_tpl'], 'properties[reminder_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the member when membership is about to expire. These reminders will be sent once a day, based on the reminder settings of the defined membership.', 'events-made-easy' ); ?><br>
				<br><?php esc_html_e( 'This reminder email does NOT take into account an optional grace period.', 'events-made-easy' ); ?>
				<br><?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_reminder_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['reminder_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_reminder_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[reminder_body_text]', $membership['properties']['reminder_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

	<h3><?php esc_html_e( 'Membership stopped email', 'events-made-easy' ); ?></h3>
		<div>
	<img style='vertical-align: middle;' src='<?php echo esc_url(EME_PLUGIN_URL); ?>images/warning.png' alt='warning'><?php esc_html_e( 'Warning: when the membership is configured to ask for family member info, this mail is ALSO sent to each of the family members.', 'events-made-easy' ); ?>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership stopped email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[stop_subject_format]" name="properties[stop_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['stop_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the member when a membership has expired or is marked as stopped.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If a grace period is defined for the membership, the expiry email is only sent at the end of the grace period.', 'events-made-easy' ); ?>
		</p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Membership stopped email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['stop_body_format_tpl'], 'properties[stop_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the member when a membership has expired or is marked as stopped.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'If a grace period is defined for the membership, the expiry email is only sent at the end of the grace period.', 'events-made-easy' ); ?>
		<br><?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_stop_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['stop_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_stop_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[stop_body_text]', $membership['properties']['stop_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson membership stopped email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[contact_stop_subject_format]" name="properties[contact_stop_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['contact_stop_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the mail sent to the contactperson when a membership has expired or is stopped.', 'events-made-easy' ); ?>
					<br><?php esc_html_e( 'If a grace period is defined for the membership, the expiry email is only sent at the end of the grace period.', 'events-made-easy' ); ?>
			</p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson membership stopped email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['contact_stop_body_format_tpl'], 'properties[contact_stop_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the mail sent to the contactperson when a membership has expired or is stopped.', 'events-made-easy' ); ?>
				<br><?php esc_html_e( 'If a grace period is defined for the membership, the expiry email is only sent at the end of the grace period.', 'events-made-easy' ); ?>
				<br><?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_contact_stop_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['contact_stop_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_contact_stop_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[contact_stop_body_text]', $membership['properties']['contact_stop_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

	<h3><?php esc_html_e( 'Contactperson payment notification email', 'events-made-easy' ); ?></h3>
		<div>
	<table class="eme_membership_admin_table">
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson payment notification email subject', 'events-made-easy' ); ?></label></td>
	<td><input id="properties[contact_ipn_subject_format]" name="properties[contact_ipn_subject_format]" type="text" value="<?php echo eme_esc_html( $membership['properties']['contact_ipn_subject_format'] ); ?>" size="40">
		<br><p class='eme_smaller'><?php esc_html_e( 'The subject of the email which will be sent to the contact person when a payment notification is received via a payment gateway.', 'events-made-easy' ); ?></p>
		<br>
	</td>
	</tr>
	<tr>
	<td><label for="name"><?php esc_html_e( 'Contactperson payment notification email body', 'events-made-easy' ); ?></label></td>
	<td><?php echo eme_ui_select( $membership['properties']['contact_ipn_body_format_tpl'], 'properties[contact_ipn_body_format_tpl]', $templates_array ); ?>
		<br><p class='eme_smaller'><?php esc_html_e( 'The body of the email which will be sent to the contact person when a payment notification is received via a payment gateway.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'No template shown in the list? Then go in the section Templates and create a template of type "Membership related mail".', 'events-made-easy' ); ?>
		<br>
		<?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>
		<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_membership_properties_contact_ipn_body_text" style="cursor: pointer; vertical-align: middle; ">
		<?php
		if ( eme_is_empty_string( $membership['properties']['contact_ipn_body_text'] ) ) {
				$showhide_style = 'style="display:none; width:100%;"';
		} else {
			$showhide_style = 'style="width:100%;"';
		}
		?>
		<div id="div_membership_properties_contact_ipn_body_text" <?php echo $showhide_style; ?>>
		<?php eme_wysiwyg_textarea( 'properties[contact_ipn_body_text]', $membership['properties']['contact_ipn_body_text'], 1, 0 ); ?>
		</div>
		</p>
	</td>
	</tr>
		</table>
		</div>

</div>
</div>
	<?php
}

function eme_meta_box_div_membershipcustomfields( $membership ) {
?>
<div id="div_membership_customfields">
	<br><b>
	<?php
	echo esc_html__( 'Custom fields', 'events-made-easy' );
	?>
	</b>
	<p><?php echo esc_html__( "Here custom fields of type 'Membership' are shown.", 'events-made-easy' ); ?></p>
	<?php
		if ( current_user_can( 'unfiltered_html' ) ) {
			echo "<div class='eme_notice_unfiltered_html'>";
			esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
			echo '</div>';
		}
	?>
	<table style='width: 100%;'>
	<?php
	$formfields = eme_get_formfields( '', 'memberships' );
	$formfields     = apply_filters( 'eme_membership_formfields', $formfields );
	if ( ! empty( $membership['membership_id'] ) ) {
		$answers = eme_get_membership_answers( $membership['membership_id'] );
		$files = eme_get_uploaded_files( $membership['membership_id'], 'memberships' );
	} else {
		$answers = [];
	}
	foreach ( $formfields as $formfield ) {
		$field_name     = eme_trans_esc_html( $formfield['field_name'] );
		$field_id       = $formfield['field_id'];
		$postfield_name = 'FIELD' . $field_id;
		$entered_val    = '';
		foreach ( $answers as $answer ) {
			if ( $answer['field_id'] == $field_id ) {
				$entered_val = $answer['answer'];
			}
		}
		if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
			$entered_files = [];
			foreach ( $files as $file ) {
				if ( $file['field_id'] == $field_id ) {
					$entered_files[] = $file;
				}
			}
			$entered_val = $entered_files;
		}

		if ( $formfield['field_required'] ) {
			$required = 1;
		} else {
			$required = 0;
		}
		if ( $formfield['field_type'] == 'hidden' ) {
			$field_html = esc_html__( "Custom fields of type 'hidden' are useless here and of course won't be shown.", 'events-made-easy' );
		} else {
			$field_html = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required );
		}
		echo "<tr><td>$field_name</td><td style='width: 100%;'>$field_html</td></tr>";
	}
	?>
	</table>
</div>
<?php
}

function eme_render_members_searchfields( $group = [] ) {
	$eme_member_status_array = eme_member_status_array();
	$memberships             = eme_get_memberships();
	$value                   = '';
	if ( ! empty( $group ) ) {
		$edit_group   = 1;
		$search_terms = eme_unserialize( $group['search_terms'] );
	} else {
		$edit_group = 0;
	}
	if ( $edit_group ) {
		echo '<tr><td>' . esc_html__( 'Select memberships', 'events-made-easy' ) . '</td><td>';
		if ( isset( $search_terms['search_membershipids'] ) ) {
			$value = $search_terms['search_membershipids'];
		}
	}
	echo eme_ui_multiselect_key_value( $value, 'search_membershipids', $memberships, 'membership_id', 'name', 5, '', 0, 'eme_select2_memberships_class' );
	if ( $edit_group ) {
		echo '</td></tr><tr><td>' . esc_html__( 'Select member status', 'events-made-easy' ) . '</td><td>';
		if ( isset( $search_terms['search_memberstatus'] ) ) {
			$value = $search_terms['search_memberstatus'];
		}
	}
	echo eme_ui_multiselect( $value, 'search_memberstatus', $eme_member_status_array, 5, '', 0, 'eme_select2_memberstatus_class' );
	if ( $edit_group ) {
		echo '</td></tr><tr><td>' . esc_html__( 'Filter on person', 'events-made-easy' ) . '</td><td>';
		if ( isset( $search_terms['search_person'] ) ) {
			$value = $search_terms['search_person'];
		}
	}
	echo '<input type="text" value="' . esc_html($value) . '" class="clearable" name="search_person" id="search_person" placeholder="' . esc_html__( 'Filter on person', 'events-made-easy' ) . '" size=15>';
	if ( $edit_group ) {
		echo '</td></tr><tr><td>' . esc_html__( 'Filter on member ID', 'events-made-easy' ) . '</td><td>';
		if ( isset( $search_terms['search_memberid'] ) ) {
			$value = $search_terms['search_memberid'];
		}
	}
	echo '<input type="text" value="' . esc_html($value) . '" class="clearable" name="search_memberid" id="search_memberid" placeholder="' . esc_html__( 'Filter on member ID', 'events-made-easy' ) . '" size=15>';
	echo '<input type="text" name="search_paymentid" id="search_paymentid" placeholder="' . esc_html__( 'Filter on payment id', 'events-made-easy' ) . '" size=15>';
		echo '<input type="text" name="search_pg_pid" id="search_pg_pid" placeholder="' . esc_html__( 'Filter on payment GW id', 'events-made-easy' ) . '" size=15>';

	$formfields_searchable = eme_get_searchable_formfields( 'members', 1 );
	if ( ! empty( $formfields_searchable ) ) {
		if ( $edit_group ) {
			echo '</td></tr><tr><td>' . esc_html__( 'Custom field value to search', 'events-made-easy' ) . '</td><td>';
			if ( isset( $search_terms['search_customfields'] ) ) {
				$value = $search_terms['search_customfields'];
			}
		}
		echo '<input type="text" value="' . esc_html($value) . '" class="clearable" name="search_customfields" id="search_customfields" placeholder="' . esc_html__( 'Custom field value to search', 'events-made-easy' ) . '" size=20>';
		if ( $edit_group ) {
			echo '</td></tr><tr><td>' . esc_html__( 'Custom field to search', 'events-made-easy' ) . '</td><td>';
			if ( isset( $search_terms['search_customfieldids'] ) ) {
				$value = $search_terms['search_customfieldids'];
			}
		}
		echo eme_ui_multiselect_key_value( $value, 'search_customfieldids', $formfields_searchable, 'field_id', 'field_name', 5, '', 0, 'eme_select2_customfieldids_class' );
	}
}

function eme_get_sql_members_searchfields( $search_terms, $start = 0, $pagesize = 0, $sorting = '', $count = 0, $memberids_only = 0, $peopleids_only = 0, $emails_only = 0 ) {
	global $wpdb;
	$members_table           = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table       = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$people_table            = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$answers_table           = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$eme_member_status_array = eme_member_status_array();

	$answer_member_ids = [];
	$where_arr         = [];

	$people_join = "LEFT JOIN $people_table AS people ON members.person_id=people.person_id";
	// trim the search_person too
	if ( ! empty( $search_terms['search_person'] ) ) {
		$search_person = esc_sql( $wpdb->esc_like( trim( $search_terms['search_person'] ) ) );
		$where_arr[]   = "(people.lastname LIKE '%$search_person%' OR people.firstname LIKE '%$search_person%' OR people.email LIKE '%$search_person%')";
	}

	// if the person is not allowed to manage all people, show only himself
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
		$wp_id          = get_current_user_id();
		$member_ids_arr = eme_get_memberids_by_wpid( $wp_id );
		if ( empty( $member_ids_arr ) ) {
			$where_arr[] = '(members.member_id = -1)';
		} else {
			$member_ids  = join( ',', $member_ids_arr );
			$where_arr[] = "(members.member_id IN ($member_ids))";
		}
	} elseif ( ! empty( $search_terms['search_memberid'] ) ) {
		$search_memberid = intval( $search_terms['search_memberid'] );
		$where_arr[]     = "(members.member_id = $search_memberid)";
	}
	if ( ! empty( $search_terms['search_membershipids'] ) && eme_is_numeric_array( $search_terms['search_membershipids'] ) ) {
		$search_membershipids = join( ',', $search_terms['search_membershipids'] );
		$where_arr[]          = "(members.membership_id IN ($search_membershipids))";
	}
	// search_status can be 0 too, for pending
	if ( ! empty( $search_terms['search_memberstatus'] ) ) {
		$search_memberstatus = join( ',', $search_terms['search_memberstatus'] );
		$where_arr[]         = "(members.status IN ($search_memberstatus))";
	}
	if ( ! empty( $search_terms['search_paymentid'] ) ) {
		$search_paymentid = intval( $search_terms['search_paymentid'] );
		$where_arr[]      = "(payment_id=$search_paymentid)";
	}
	if ( ! empty( $search_terms['search_pg_pid'] ) ) {
		$search_pg_pid = esc_sql( $wpdb->esc_like( $search_terms['search_pg_pid'] ) );
		$where_arr[]   = "(pg_pid like '%$search_pg_pid%')";
	}
	if ( ! empty( $where_arr ) ) {
		$where = 'WHERE ' . join( ' AND ', $where_arr );
	} else {
		$where = '';
	}

	$formfields_searchable = eme_get_searchable_formfields( 'members', 1 );

	// we need this GROUP_CONCAT so we can sort on those fields too (otherwise the columns FIELD_* don't exist in the returning sql
	// but we'll do the GROUP_CONCAT only when needed of course
	$group_concat_sql = '';
	$field_ids_arr    = [];
	foreach ( $formfields_searchable as $formfield ) {
		$field_id        = $formfield['field_id'];
		$field_ids_arr[] = $field_id;
		if ( ! ( $memberids_only || $peopleids_only || $emails_only ) && ! strstr( $where, 'FIELD' ) ) {
			$group_concat_sql .= "GROUP_CONCAT(CASE WHEN field_id = $field_id THEN answer END) AS 'FIELD_$field_id',";
		}
	}

        $search_formfield_sql = '';
        if ( ! empty( $formfields_searchable ) && isset( $search_terms['search_customfields'] ) ) {
                // small optimization
                if ( $search_terms['search_customfields'] == '' ) {
                        $search_customfields = '';
                        $search_formfield_sql = " AND answer = '' ";
                } else  {
                        $search_customfields = esc_sql( $wpdb->esc_like($search_terms['search_customfields']) );
                        $search_formfield_sql = " AND answer LIKE '%$search_customfields%' ";
                }
                if ( ! empty( $search_terms['search_customfieldids'] ) && eme_is_numeric_array( $search_terms['search_customfieldids'] ) ) {
                        $field_ids = join( ',', $search_terms['search_customfieldids'] );
                        $search_formfield_sql .= " AND field_id IN ($field_ids) ";
                } else {
                        // we don't search for a specific field, so search in all, but then the search value is not allowed to be empty
			// so if it is empty, set this var to empty
                        $field_ids = join( ',', $field_ids_arr );
                        if ($search_terms['search_customfields'] == '' ) {
                                $search_formfield_sql = "";
                        } else {
                                $search_formfield_sql .= " AND field_id IN ($field_ids) ";
                        }
                }
        }
	if (!empty($search_formfield_sql)) {
		$sql_join = "
		   INNER JOIN (SELECT $group_concat_sql related_id FROM $answers_table
			 WHERE related_id>0 AND type='member' $search_formfield_sql
			 GROUP BY related_id
			) ans
		   ON members.member_id=ans.related_id";
	} else {
		$sql_join = "
		   LEFT JOIN (SELECT $group_concat_sql related_id FROM $answers_table
			 WHERE related_id>0 AND type='member'
			 GROUP BY related_id
			) ans
		   ON members.member_id=ans.related_id";
	}
	if ( $count ) {
		$sql = "SELECT COUNT(*) FROM $members_table AS members $people_join $sql_join $where";
	} elseif ( $memberids_only ) {
		$sql = "SELECT members.member_id FROM $members_table AS members $people_join $sql_join $where $sorting";
	} elseif ( $peopleids_only ) {
		$sql = "SELECT people.person_id FROM $members_table AS members $people_join $sql_join $where $sorting";
	} elseif ( $emails_only ) {
		$sql = "SELECT people.email FROM $members_table AS members $people_join $sql_join $where $sorting";
	} else {
		$sql = "SELECT members.*, people.lastname, people.firstname, people.email, people.birthdate, people.birthplace, people.address1, people.address2, people.zip, people.city, people.state_code, people.country_code, people.wp_id
	   FROM $members_table AS members $people_join $sql_join $where $sorting";
		if ( ! empty( $pagesize ) ) {
			$sql .= " LIMIT $start,$pagesize";
		}
	}
	return $sql;
}

function eme_manage_members_layout( $message ) {
	global $plugin_page;

	$memberships     = eme_get_memberships();
	$pdftemplates    = eme_get_templates( 'pdf', 1 );
	$htmltemplates   = eme_get_templates( 'html', 1 );
	$membertemplates = eme_get_templates( 'membershipmail', 1 );
	$nonce_field     = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );

	if ( empty( $message ) ) {
			$style_class = "style='display:none;'";
	} else {
		$style_class = "class='notice is-dismissible eme-message-admin'";
	}
	?>
	<div class="wrap nosubsub">
	<div id="poststuff">
	<div id="icon-edit" class="icon32">
	</div>

	<div id="members-message" <?php echo $style_class; ?>>
		<p><?php echo $message; ?></p>
	</div>

	<?php if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) : ?>
		<h1><?php esc_html_e( 'Add a new member', 'events-made-easy' ); ?></h1>
		<div class="wrap">
		<?php if ( ! empty( $memberships ) ) { ?>
		<form id="members-filter" method="post" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>">
			<input type="hidden" name="eme_admin_action" value="add_member">
				<?php
				echo $nonce_field;
				echo eme_ui_select_key_value( '', 'membership_id', $memberships, 'membership_id', 'name' );
				?>
			<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add member', 'events-made-easy' ); ?>">
		</form>
		</div>
				<?php
		} else {
			esc_html_e( 'No memberships defined yet!', 'events-made-easy' );
		}
		?>
	<?php endif; ?>
		 
		<h1><?php esc_html_e( 'Manage members', 'events-made-easy' ); ?></h1>

	<?php if ( current_user_can( get_option( 'eme_cap_cleanup' ) ) ) { ?>
	<span class="eme_import_form_img">
	<?php esc_html_e( 'Click on the icon to show the import form', 'events-made-easy' ); ?>
	<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_import" style="cursor: pointer; vertical-align: middle; ">
	</span>
	<div id='div_import' style='display:none;'>
	<form id='member-import' method='post' enctype='multipart/form-data' action='#'>
	<?php echo $nonce_field; ?>
	<input type="file" name="eme_csv">
	<?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
	<input type="text" size=1 maxlength=1 name="delimiter" value=',' required='required'>
	<?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
	<input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"' required='required'>
	<input type="hidden" name="eme_admin_action" value="import">
	<?php
	esc_html_e( 'Allow empty email?', 'events-made-easy' );
	echo eme_ui_select_binary( '', 'allow_empty_email' );
	?>
	<input type="submit" value="<?php esc_html_e( 'Import members', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
	<?php esc_html_e( 'If you want, use this to import members info into the database', 'events-made-easy' ); ?>
	</form>
	<form id='member-import-answers' method='post' enctype='multipart/form-data' action='#'>
	<?php echo $nonce_field; ?>
	<input type="file" name="eme_csv">
	<?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
	<input type="text" size=1 maxlength=1 name="delimiter" value=',' required='required'>
	<?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
	<input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"' required='required'>
	<input type="hidden" name="eme_admin_action" value="import_dynamic_answers">
	<?php
	esc_html_e( 'Allow empty email?', 'events-made-easy' );
	echo eme_ui_select_binary( '', 'allow_empty_email' );
	?>
	<input type="submit" value="<?php esc_html_e( 'Import dynamic field answers', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
	<?php esc_html_e( 'Once you finished importing members, use this to import dynamic field answers into the database', 'events-made-easy' ); ?>
	</form>
	</div>
	<br>
	<?php } ?>
	<br>

	<form id="eme-admin-regsearchform" name="eme-admin-regsearchform" action="#" method="post">
	<?php
	eme_render_members_searchfields();
	?>
	<button id="MembersLoadRecordsButton" class="button action eme_admin_button_middle"><?php esc_html_e( 'Filter members', 'events-made-easy' ); ?></button>
	<button id="StoreQueryButton" class="button action eme_admin_button_middle"><?php esc_html_e( 'Store result as dynamic group', 'events-made-easy' ); ?></button>
	<div id="StoreQueryDiv"><?php esc_html_e( 'Enter a name for this dynamic group', 'events-made-easy' ); ?> <input type="text" id="dynamicgroupname" name="dynamicgroupname" class="clearable" size=20>
		<button id="StoreQuerySubmitButton" class="button action"><?php esc_html_e( 'Store dynamic group', 'events-made-easy' ); ?></button>
	</div>
	<?php
	$formfields_searchable = eme_get_searchable_formfields( 'members', 1 );
	if ( ! empty( $formfields_searchable ) ) {
		?>
	<div id="hint">
		<?php esc_html_e( 'Hint: when searching for custom field values, you can optionally limit which custom fields you want to search in the "Custom fields to filter on" select-box shown.', 'events-made-easy' ); ?><br>
		<?php esc_html_e( 'If you can\'t see your custom field in the "Custom fields to filter on" select-box, make sure you marked it as "searchable" in the field definition.', 'events-made-easy' ); ?>
	</div>
		<?php
	}
	?>
	</form>

	<form id='members-form' action="#" method="post">
	<?php echo $nonce_field; ?>
	<select id="eme_admin_action" name="eme_admin_action">
	<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
	<?php if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) : ?>
	<option value="acceptPayment"><?php esc_html_e( 'Accept membership payment', 'events-made-easy' ); ?></option>
	<option value="markUnpaid"><?php esc_html_e( 'Set membership unpaid', 'events-made-easy' ); ?></option>
	<option value="stopMembership"><?php esc_html_e( 'Stop membership', 'events-made-easy' ); ?></option>
	<option value="deleteMembers"><?php esc_html_e( 'Delete selected members', 'events-made-easy' ); ?></option>
	<option value="resendPendingMember"><?php esc_html_e( 'Resend the mail for pending members', 'events-made-easy' ); ?></option>
	<option value="resendPaidMember"><?php esc_html_e( 'Resend the mail for paid members', 'events-made-easy' ); ?></option>
	<option value="resendExpirationReminders"><?php esc_html_e( 'Resend the expiration reminder mail', 'events-made-easy' ); ?></option>
	<option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
	<option value="memberMails"><?php esc_html_e( 'Send membership related email to selected members', 'events-made-easy' ); ?></option>
	<?php endif; ?>
	<option value="pdf"><?php esc_html_e( 'PDF output', 'events-made-easy' ); ?></option>
	<option value="html"><?php esc_html_e( 'HTML output', 'events-made-easy' ); ?></option>
	</select>
	<span id="span_sendmails" class="eme-hidden">
	<?php
	esc_html_e( 'Send mails to members upon changes being made?', 'events-made-easy' );
	echo eme_ui_select_binary( 1, 'send_mail' );
	?>
	</span>
	<span id="span_trashperson" class="eme-hidden">
	<?php
	esc_html_e( 'Move corresponding persons to the trash bin?', 'events-made-easy' );
	echo eme_ui_select_binary( 0, 'trash_person' );
	?>
	</span>
	<span id="span_membermailtemplate" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'membermail_template_subject', $membertemplates, 'id', 'name', __( 'Select a subject template', 'events-made-easy' ), 1 ); ?>
	<?php echo eme_ui_select_key_value( '', 'membermail_template', $membertemplates, 'id', 'name', __( 'Please select a body template', 'events-made-easy' ), 1 ); ?>
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
	<button id="MembersActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
	<span class="rightclickhint">
		<?php esc_html_e( 'Hint: rightclick on the column headers to show/hide columns', 'events-made-easy' ); ?>
	</span>
	<?php
	$extrafields_arr          = [];
	$extrafieldnames_arr      = [];
	$extrafieldsearchable_arr = [];
	if ( get_option( 'eme_members_show_people_info' ) ) {
		$formfields = eme_get_formfields( '', 'members,people,generic' );
	} else {
		$formfields = eme_get_formfields( '', 'members,generic' );
	}
	foreach ( $formfields as $formfield ) {
		$extrafields_arr[]      = $formfield['field_id'];
		$extrafieldnames_arr[]  = eme_trans_esc_html( $formfield['field_name'] );
		$extrafieldsearchable_arr[] = $formfield['searchable'];
	}
	// these 2 values are used as data-fields to the container-div, and are used by the js to create extra columns
	$extrafields          = join( ',', $extrafields_arr );
	$extrafieldnames      = join( ',', $extrafieldnames_arr );
	$extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
	?>
	</form>
	<div id="MembersTableContainer" data-extrafields='<?php echo $extrafields; ?>' data-extrafieldnames='<?php echo $extrafieldnames; ?>' data-extrafieldsearchable='<?php echo $extrafieldsearchable; ?>'></div>
	</div>
	</div>
	<?php
}

function eme_manage_memberships_layout( $message ) {
	global $plugin_page;

	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	if ( empty( $message ) ) {
		$hidden_style = 'display:none;';
	} else {
		$hidden_style = '';
	}
	?>
	<div class="wrap nosubsub">
	<div id="poststuff">
	<div id="icon-edit" class="icon32">
	</div>

	<div id="memberships-message" class="notice is-dismissible eme-message-admin" style="<?php echo $hidden_style; ?>">
		<p><?php echo $message; ?></p>
	</div>

	<?php if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) : ?>
		<h1><?php esc_html_e( 'Add a new membership definition', 'events-made-easy' ); ?></h1>
		<div class="wrap">
		<form id="memberships-filter" method="post" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>">
			<?php echo $nonce_field; ?>
			<input type="hidden" name="eme_admin_action" value="add_membership">
			<input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add membership', 'events-made-easy' ); ?>">
		</form>
		</div>
	<?php endif; ?>

	<h1><?php esc_html_e( 'Manage memberships', 'events-made-easy' ); ?></h1>

	<form id='memberships-form' action="#" method="post">
	<?php echo $nonce_field; ?>
	<select id="eme_admin_action" name="eme_admin_action">
	<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
	<?php if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) : ?>
	<option value="deleteMemberships"><?php esc_html_e( 'Delete selected memberships', 'events-made-easy' ); ?></option>
	<option value="showMembershipStats"><?php esc_html_e( 'Show membership statistics', 'events-made-easy' ); ?></option>
	<?php endif; ?>
	</select>
	<button id="MembershipsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
	<span class="rightclickhint">
	<?php esc_html_e( 'Hint: rightclick on the column headers to show/hide columns', 'events-made-easy' ); ?>
	</span><br>
	<?php
	$formfields               = eme_get_formfields( '', 'memberships' );
	$extrafields_arr          = [];
	$extrafieldnames_arr      = [];
	$extrafieldsearchable_arr = [];
	foreach ( $formfields as $formfield ) {
		$extrafields_arr[]          = $formfield['field_id'];
		$extrafieldnames_arr[]      = eme_trans_esc_html( $formfield['field_name'] );
		$extrafieldsearchable_arr[] = $formfield['searchable'];
	}
	// these 2 values are used as data-fields to the container-div, and are used by the js to create extra columns
	$extrafields          = join( ',', $extrafields_arr );
	$extrafieldnames      = join( ',', $extrafieldnames_arr );
	$extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
	?>
	</form>
	<div id="MembershipsTableContainer" data-extrafields='<?php echo $extrafields; ?>' data-extrafieldnames='<?php echo $extrafieldnames; ?>' data-extrafieldsearchable='<?php echo $extrafieldsearchable; ?>'></div>
	</div>
	</div>
	<?php
}

function eme_fake_member( $membership ) {
	$member = eme_member_from_form( $membership );
	// indicate a negative person ID, so we can check on that later on if needed
	$member['person_id'] = -1;
	return $member;
}

function eme_member_from_form( $membership ) {
	$member                  = eme_new_member();
	$member['member_id']     = 0;
	$membership_id           = $membership['membership_id'];
	$member['membership_id'] = $membership_id;

	$member['extra_charge'] = eme_store_member_answers( $member, 0 );

	$dcodes_entered = [];
	if ( isset( $_POST['members'] ) ) {
		foreach ( $_POST['members'][ $membership_id ] as $key => $value ) {
			if ( preg_match( '/^DISCOUNT/', $key, $matches ) ) {
				$discount_value = eme_sanitize_request( $value );
				if ( ! empty( $value ) ) {
					$dcodes_entered[] = $discount_value;
				}
			}
		}
	}

	$member['dcodes_entered'] = $dcodes_entered;
	$calc_discount            = eme_member_discount( $membership, $member );
	$member['discount']       = $calc_discount['discount'];
	$member['dcodes_used']    = $calc_discount['dcodes_used'];
	$member['discountids']    = $calc_discount['discountids'];
	$member['dgroupid']       = $calc_discount['dgroupid'];

	return $member;
}

function eme_calc_price_fake_member( $membership ) {
	$member = eme_fake_member( $membership );
	return eme_get_total_member_price( $member );
}

function eme_calc_memberprice_ajax() {
	header( 'Content-type: application/json; charset=utf-8' );
	if ( isset( $_POST['membership_id'] ) ) {
		$membership_id = intval( $_POST['membership_id'] );
	}
	if ( isset( $_POST['member_id'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		$member        = eme_get_member( intval( $_POST['member_id'] ) );
		$membership_id = $member['membership_id'];
	}
	if ( ! $membership_id ) {
		echo wp_json_encode( [ 'total' => '' ] );
		return;
	}
	$membership = eme_get_membership( $membership_id );
	if ( ! $membership ) {
		echo wp_json_encode( [ 'total' => '' ] );
		return;
	}
	$total  = eme_calc_price_fake_member( $membership );
	$cur    = $membership['properties']['currency'];
	$result = eme_localized_price( $total, $cur );
	echo wp_json_encode( [ 'total' => $result ] );
}

function eme_dyndata_familymember_ajax() {
	header( 'Content-type: application/json; charset=utf-8' );
	if ( isset( $_POST['membership_id'] ) ) {
		$membership_id = intval( $_POST['membership_id'] );
	} else {
		return;
	}
	$count      = intval( $_POST['familycount'] );
	$form_html  = '';
	$membership = eme_get_membership( $membership_id );
	if ( ! eme_is_empty_string( $membership['properties']['familymember_form_text'] ) ) {
		$format = $membership['properties']['familymember_form_text'];
	} else {
		$format = eme_get_template_format( $membership['properties']['familymember_form_tpl'] );
	}
	if ( eme_is_empty_string( $format ) ) {
		$format = "<table class='eme-rsvp-form'>
            <tr><th scope='row'>" . esc_html__( 'Last name', 'events-made-easy' ) . "*:</th><td>#_LASTNAME</td></tr>
            <tr><th scope='row'>" . esc_html__( 'First name', 'events-made-easy' ) . "*:</th><td>#REQ_FIRSTNAME</td></tr>
            <tr><th scope='row'>" . esc_html__( 'Email', 'events-made-easy' ) . "*:</th><td>#_EMAIL</td></tr>
            </table>
            ";
	}
	for ( $i = 1;$i <= $count;$i++ ) {
		$form_html .= eme_replace_membership_familyformfields_placeholders( $format, $i );
	}
	echo wp_json_encode( [ 'Result' => do_shortcode( $form_html ) ] );
}

function eme_dyndata_member_ajax() {
	header( 'Content-type: application/json; charset=utf-8' );
	$membership_id = 0;
	if ( ! empty( $_POST['membership_id'] ) ) {
		$membership_id = intval( $_POST['membership_id'] );
	}

	if ( ! empty( $_POST['member_id'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		$member        = eme_get_member( intval( $_POST['member_id'] ) );
		$membership_id = $member['membership_id'];
	} else {
		$member = [];
	}

	$total     = 0;
	$cur       = '';
	$form_html = '';
	if ( $membership_id ) {
		$membership = eme_get_membership( $membership_id );
		if ( empty( $member ) ) {
			$member = eme_fake_member( $membership );
		}
		if ( isset( $membership['properties']['dyndata'] ) ) {
			$conditions = $membership['properties']['dyndata'];
			foreach ( $conditions as $count => $condition ) {
				// the next check is mostly to eliminate older conditions that didn't have the field-param
				if ( empty( $condition['field'] ) ) {
					continue;
				}
				// sensible values ...
				if ( empty( $condition['grouping'] ) ) {
					$grouping = $count;
				} else {
					$grouping = intval( $condition['grouping'] );
				}
				if ( $condition['field'] == '#_GROUPS' ) {
								$wp_id = eme_get_wpid_by_post();
					$entered_val       = join( ',', eme_esc_html( eme_get_persongroup_names( 0, $wp_id ) ) );
				} else {
					// indicate "1" to make sure the answers are taken from the POST, and not from the existing member
						$entered_val = eme_replace_member_placeholders( $condition['field'], $membership, $member, 'html', '', 1 );
				}

				if ( $condition['condition'] == 'eq' && $entered_val == $condition['condval'] ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'ne' && $entered_val != $condition['condval'] ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'contains' && strpos( $entered_val, $condition['condval'] ) !== false ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'notcontains' && strpos( $entered_val, $condition['condval'] ) === false ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'incsv' && ( in_array( $condition['condval'], explode( ',', $entered_val ) ) || in_array( $condition['condval'], explode( ', ', $entered_val ) ) ) ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'notincsv' && ! ( in_array( $condition['condval'], explode( ',', $entered_val ) ) || in_array( $condition['condval'], explode( ', ', $entered_val ) ) ) ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'lt' && $entered_val < $condition['condval'] ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					if ( $condition['repeat'] ) {
						$entered_val          = intval( $entered_val );
						$condition['condval'] = intval( $condition['condval'] );
						for ( $i = $entered_val;$i < $condition['condval'];$i++ ) {
							$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping, $i - $entered_val );
						}
					} else {
						$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					}
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'gt' && $entered_val > $condition['condval'] ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					if ( $condition['repeat'] ) {
						$entered_val          = intval( $entered_val );
						$condition['condval'] = intval( $condition['condval'] );
						for ( $i = $condition['condval'];$i < $entered_val;$i++ ) {
							$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping, $i - $condition['condval'] );
						}
					} else {
						$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					}
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
				if ( $condition['condition'] == 'ge' && $entered_val >= $condition['condval'] ) {
					$template   = eme_get_template_format( $condition['template_id'] );
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_header'] ) ) );
					if ( $condition['repeat'] ) {
						$entered_val          = intval( $entered_val );
						$condition['condval'] = intval( $condition['condval'] );
						for ( $i = $condition['condval'];$i <= $entered_val;$i++ ) {
							$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping, $i - $condition['condval'] );
						}
					} else {
						$form_html .= eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $template, $grouping );
					}
					$form_html .= eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $condition['template_id_footer'] ) ) );
				}
			}
		}
	}
	echo wp_json_encode( [ 'Result' => do_shortcode( $form_html ) ] );
}

function eme_get_member_post_answers( $member, $include_dynamicdata = 1 ) {
	$answers = [];
	//$fields_seen=array();
	$membership_id = $member['membership_id'];

	// do the dynamic answers if any
	// this is a little tricky: dynamic answers are in fact grouped by a seat condition when filled out, and there can be more than 1 of the same group
	// so we need a little more looping here ...
	if ( $include_dynamicdata && isset( $_POST['dynamic_member'][ $membership_id ] ) ) {
		foreach ( $_POST['dynamic_member'][ $membership_id ] as $group_id => $group_value ) {
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
							// (when editing a member), we can re-convert it to an array with eme_convert_multi2array (see eme_formfields.php)
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
							$answer    = [
								'field_name'    => $formfield['field_name'],
								'field_id'      => $field_id,
								'field_purpose' => $formfield['field_purpose'],
								'extra_charge'  => $formfield['extra_charge'],
								'answer'        => $value,
								'grouping_id'   => $group_id,
								'occurence_id'  => $occurence_id,
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
			// Code was taken from bookings, but for members this and the following 2 lines
			// don't do anything, since no multibooking exists for memberships of course, so $fields_seen is always empty
			//if (in_array($field_id,$fields_seen))
			//  continue;
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
				$answer    = [
					'field_name'    => $formfield['field_name'],
					'field_id'      => $field_id,
					'field_purpose' => $formfield['field_purpose'],
					'extra_charge'  => $formfield['extra_charge'],
					'answer'        => $value,
					'grouping_id'   => 0,
					'occurence_id'  => 0,
				];
				$answers[] = $answer;
			}
		}
	}
	return $answers;
}

function eme_member_answers( $member, $membership, $do_update = 1 ) {
	return eme_store_member_answers( $member, $do_update );
}
function eme_store_member_answers( $member, $do_update = 1 ) {
	global $wpdb;
	$fields_seen = [];

	$extra_charge   = 0;
	$membership_id  = $member['membership_id'];
	$member_answers = [];
	$person_answers = [];
	$all_answers    = [];
	if ( $do_update ) {
		$member_id = $member['member_id'];
		if ( $member_id > 0 ) {
			$member_answers = eme_get_member_answers( $member_id );
			$person_answers = eme_get_person_answers( $member['person_id'] );
			wp_cache_delete( 'eme_person_answers ' . $member['person_id'] );
			$all_answers = array_merge( $member_answers, $person_answers );
		}
	} else {
		$member_id = 0;
	}
	$person_id = $member['person_id'];

	$answer_ids_seen = [];
	$found_answers   = eme_get_member_post_answers( $member );
	foreach ( $found_answers as $answer ) {
		if ( $answer['extra_charge'] && is_numeric( $answer['answer'] ) ) {
			$extra_charge += $answer['answer'];
		}
		if ( $do_update ) {
			if ( $answer['field_purpose'] == 'people' ) {
				$answer_id = eme_get_answerid( $all_answers, $person_id, 'person', $answer['field_id'], $answer['grouping_id'], $answer['occurence_id'] );
			} else {
				$answer_id = eme_get_answerid( $all_answers, $member_id, 'member', $answer['field_id'], $answer['grouping_id'], $answer['occurence_id'] );
			}
			if ( $answer_id ) {
				eme_update_answer( $answer_id, $answer['answer'] );
				$answer_ids_seen[] = $answer_id;
			} elseif ( $answer['field_purpose'] == 'people' ) {
					$answer_id = eme_insert_answer( 'person', $person_id, $answer['field_id'], $answer['answer'], $answer['grouping_id'], $answer['occurence_id'] );
			} else {
				$answer_id = eme_insert_answer( 'member', $member_id, $answer['field_id'], $answer['answer'], $answer['grouping_id'], $answer['occurence_id'] );
			}
		}
	}

	if ( $do_update && $member_id > 0 ) {
		// put the extra charge found in the member
		$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
		$sql           = $wpdb->prepare( "UPDATE $members_table SET extra_charge = %s WHERE member_id = %d", $extra_charge, $member_id );
		$wpdb->query( $sql );

		// delete old answer_ids
		foreach ( $all_answers as $answer ) {
			if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && $answer['type'] == 'member' && $answer['related_id'] == $member_id ) {
				eme_delete_answer( $answer['answer_id'] );
			}
		}
	}
	return $extra_charge;
}

function eme_get_person_ids_from_member_ids( $member_ids ) {
	global $wpdb;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql           = "SELECT person_id from $members_table WHERE member_id IN ($member_ids)";
	return $wpdb->get_col( $sql );
}

function eme_get_member_answers( $member_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='member'", $member_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_nodyndata_member_answers( $member_id ) {
	global $wpdb;
	$answers_table        = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$formfield_table_name = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
	$sql                  = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND eme_grouping=0 AND type='member'", $member_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_dyndata_member_answers( $member_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND eme_grouping>0 AND type='member' ORDER BY eme_grouping,occurence,field_id", $member_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}
function eme_get_dyndata_member_answer( $member_id, $grouping = 0, $occurence = 0 ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND eme_grouping=%d AND occurence=%d AND type='member'", $member_id, $grouping, $occurence );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_delete_member_answers( $member_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id=%d AND type='member'", $member_id );
	$wpdb->query( $sql );
}
function eme_delete_membership_answers( $membership_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$sql           = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id=%d AND type='membership'", $member_id );
	$wpdb->query( $sql );
}

// for backwards compatibility
function eme_get_next_start_date( $membership, $member, $renew_expired = 0 ) {
	return eme_get_start_date( $membership, $member, $renew_expired );
}

// function is only called for new or renew_expired
function eme_get_start_date( $membership, $member, $renew_expired = 0 ) {
	if ( ! $membership ) {
		return;
	}

	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	if ( $membership['type'] == 'rolling' ) {
		if ( $renew_expired ) {
			// expired members that want a renewal? Base it on today
			return $eme_date_obj_now->getDate();
			//} elseif ((empty($member['start_date']) || $member['start_date']=="0000-00-00") && !empty($member['creation_date'])) {
		} else {
			// new members have an empty start date, so base it on the creation date if set (start date is never set for new members)
			if ( empty( $member['creation_date'] ) ) {
				return $eme_date_obj_now->getDate();
			} else {
				$eme_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d H:i:s', $member['creation_date'], ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
				// check the return code to make sure we can return something sensible
				if ( $eme_date_obj !== false ) {
					return $eme_date_obj->getDate();
				} else {
					return $eme_date_obj_now->getDate();
				}
			}
		}
	} else {
		$base_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d', $membership['start_date'], ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
		if ( $membership['duration_period'] == 'forever' ) {
			$start_date_obj = $eme_date_obj_now->copy();
		} else {
			$interval       = DateInterval::createFromDateString( $membership['duration_count'] . ' ' . $membership['duration_period'] );
			$start_date_obj = $base_date_obj->copy();
			while ( $start_date_obj < $eme_date_obj_now ) {
				$start_date_obj->add( $interval );
			}
			// Now the next start date will be in the future, but for new or renewing expired members this should be the current period
			// So we take 1 interval of again
			$start_date_obj->sub( $interval );
		}
		// now make sure we don't allow something to start before the actual defined membership start date
		if ( $start_date_obj < $base_date_obj ) {
			return $base_date_obj->getDate();
		} else {
			return $start_date_obj->getDate();
		}
	}
}

function eme_get_next_end_date( $membership, $start_date, $new_member = 0 ) {
	if ( ! $membership ) {
		return;
	}
	if ( $membership['duration_period'] == 'forever' ) {
		return '';
	}

	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	// set at midnight from today
	$eme_date_obj_now->today();
	$base_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d', $start_date, ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
	$interval      = DateInterval::createFromDateString( $membership['duration_count'] . ' ' . $membership['duration_period'] );
	$base_date_obj->add( $interval );
	while ( $base_date_obj < $eme_date_obj_now ) {
		$base_date_obj->add( $interval );
	}
	// for new members, if they get 1 free period, then add another interval
	if ( $new_member && $membership['properties']['one_free_period'] ) {
		$base_date_obj->add( $interval );
	}
	return $base_date_obj->getDate();
}

// for CRON
function eme_member_recalculate_status( $member_id = 0 ) {
	global $wpdb;
	$members_table     = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	// we only recalculate member status if status_automatic=1 and the member has paid
	if ( $member_id ) {
		$sql = "SELECT a.member_id, a.status, a.start_date, a.end_date, b.duration_period, b.properties FROM $members_table a LEFT JOIN $memberships_table b ON a.membership_id=b.membership_id WHERE a.member_id=$member_id AND a.status_automatic=1 AND a.paid=1 AND related_member_id=0";
	} else {
		$sql = "SELECT a.member_id, a.status, a.start_date, a.end_date, b.duration_period, b.properties FROM $members_table a LEFT JOIN $memberships_table b ON a.membership_id=b.membership_id WHERE a.status_automatic=1 AND a.paid=1 AND related_member_id=0";
	}
	$rows = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $rows as $item ) {
		$properties        = eme_init_membership_props( eme_unserialize( $item['properties'] ) );
		$grace_period      = intval( $properties['grace_period'] );
		$status_calculated = eme_member_calc_status( $item['start_date'], $item['end_date'], $item['duration_period'], $grace_period );
		if ( $item['status'] != $status_calculated ) {
			$related_member_ids = eme_get_family_member_ids( $item['member_id'] );
			if ( $status_calculated == EME_MEMBER_STATUS_EXPIRED ) {
				// stop member also stops familiy members
				$res = eme_stop_member( $item['member_id'] );
				if ($res) {
					$member = eme_get_member( $item['member_id'] );
					eme_email_member_action( $member, 'stopMember' );
					if ( ! empty( $related_member_ids ) ) {
						// the family members are also stopped when calling eme_stop_member, but we still need to send the mail
						foreach ( $related_member_ids as $related_member_id ) {
							$member = eme_get_member( $related_member_id );
							eme_email_member_action( $member, 'stopMember' );
						}
					}
				}
			} else {
				eme_member_set_status( $item['member_id'], $status_calculated );
				if ( ! empty( $related_member_ids ) ) {
					foreach ( $related_member_ids as $related_member_id ) {
						eme_member_set_status( $related_member_id, $status_calculated );
					}
				}
			}
		}
	}
}

// for CRON
function eme_member_remove_pending() {
	global $wpdb;
	$table            = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql              = $wpdb->prepare( "SELECT member_id,membership_id,creation_date from $table WHERE status=%d", EME_MEMBER_STATUS_PENDING );
	$members          = $wpdb->get_results( $sql, ARRAY_A );
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	foreach ( $members as $member ) {
		$membership = eme_get_membership( $member['membership_id'] );
		if ( ! empty( $membership['properties']['remove_pending_days'] ) ) {
			$datetime = new ExpressiveDate( $member['creation_date'], EME_TIMEZONE );
			$diff     = $datetime->getDifferenceInDays( $eme_date_obj_now );
			if ( $diff > $membership['properties']['remove_pending_days'] ) {
				eme_delete_member( $member['member_id'] );
			}
		}
	}
}

// for GDPR CRON
function eme_member_remove_old_expired() {
	global $wpdb;
	$table               = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$remove_expired_days = get_option( 'eme_gdpr_remove_expired_member_days' );
	if ( empty( $remove_expired_days ) ) {
		return;
	}

	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today            = $eme_date_obj_now->getDate();

	$sql        = $wpdb->prepare( "SELECT member_id from $table WHERE status=%d AND DATEDIFF(%s,end_date)>%d", EME_MEMBER_STATUS_EXPIRED, $today, $remove_expired_days );
	$member_ids = $wpdb->get_col( $sql );
	foreach ( $member_ids as $member_id ) {
		eme_delete_member( $member_id );
	}
}

// for CRON
function eme_member_send_expiration_reminders() {
	global $wpdb;
	$table            = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships      = eme_get_memberships();
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today            = $eme_date_obj_now->getDate();
	foreach ( $memberships as $membership ) {
		$membership_id = $membership['membership_id'];
		if ( $membership['duration_period'] == 'forever' ) {
			continue;
		}
		if ( ! eme_is_empty_string( $membership['properties']['reminder_days'] ) ) {
			$reminder_days = explode( ',', $membership['properties']['reminder_days'] );
			foreach ( $reminder_days as $reminder_day ) {
				$day = intval( $reminder_day );
				// only send a reminder if really needed, and reminder can be negative (meaning the membership is in 'grace' state)
				$sql        = $wpdb->prepare( "SELECT member_id from $table WHERE membership_id=$membership_id AND related_member_id=0 AND status IN (%d,%d) AND DATEDIFF(end_date,%s)=%d", EME_MEMBER_STATUS_ACTIVE, EME_MEMBER_STATUS_GRACE, $today, $day );
				$member_ids = $wpdb->get_col( $sql );
				foreach ( $member_ids as $member_id ) {
					eme_member_send_expiration_reminder( $member_id );
				}
			}
		}
	}
}

function eme_count_pending_members() {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT COUNT(*) from $table WHERE status=%d", EME_MEMBER_STATUS_PENDING );
	return $wpdb->get_var( $sql );
}

function eme_member_send_expiration_reminder( $member_id ) {
	global $wpdb;
	$table            = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	$today            = $eme_date_obj_now->getDate();
	$member           = eme_get_member( $member_id );
	eme_email_member_action( $member, 'expiration_reminder' );
	$sql = $wpdb->prepare( "UPDATE $table SET reminder=reminder+1,reminder_date=%s WHERE member_id=%d", $today, $member_id );
	$wpdb->query( $sql );
}

function eme_member_calc_status( $start_date, $end_date, $duration = '', $grace_period = 0 ) {
	$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
	// set at midnight from today
	$eme_date_obj_now->today();
	$start_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d', $start_date, ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
	if ( $start_date_obj === false ) {
		// a default return
		return EME_MEMBER_STATUS_PENDING;
	}
	$start_date_obj->setTime( 0, 0, 0 );

	if ( ! empty( $start_date ) && $start_date_obj <= $eme_date_obj_now && $duration == 'forever' ) {
		return EME_MEMBER_STATUS_ACTIVE;
	}

	if ( ! empty( $end_date ) ) {
		$end_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d', $end_date, ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
		if ( $end_date_obj === false ) {
			// a default return
			return EME_MEMBER_STATUS_PENDING;
		}
		$end_date_obj->setTime( 0, 0, 0 );
		if ( ! empty( $grace_period ) ) {
			$grace_date_obj = $end_date_obj->copy()->addDays( $grace_period );
			if ( $grace_date_obj < $eme_date_obj_now ) {
				return EME_MEMBER_STATUS_EXPIRED;
			}
			if ( $end_date_obj < $eme_date_obj_now && $eme_date_obj_now <= $grace_date_obj ) {
				return EME_MEMBER_STATUS_GRACE;
			}
		} elseif ( $end_date_obj < $eme_date_obj_now ) {
			return EME_MEMBER_STATUS_EXPIRED;
		}
	}
	if ( ! empty( $start_date ) && $start_date_obj <= $eme_date_obj_now ) {
		return EME_MEMBER_STATUS_ACTIVE;
	}
	if ( ! empty( $start_date ) && $eme_date_obj_now < $start_date_obj ) {
		return EME_MEMBER_STATUS_PENDING;
	}

	// a default return
	return EME_MEMBER_STATUS_PENDING;
}

function eme_member_set_paid( $member, $pg = '', $pg_pid = '' ) {
	global $wpdb;
	$table      = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$membership = eme_get_membership( $member['membership_id'] );

	if ( ! empty( $member['related_member_id'] ) ) {
		return 0;
	}

	$where                   = [];
	$fields                  = [];
	$where['member_id']      = $member['member_id'];
	$fields['paid']          = 1;
	$fields['reminder']      = 0;
	$fields['reminder_date'] = '0000-00-00 00:00:00';
	$fields['pg']            = $pg;
	$fields['pg_pid']        = $pg_pid;
	$fields['payment_date']  = current_time( 'mysql', false );

	// if a membership is paid, set the start and end date if need be
	// and recalc the status in case of automatic
	if ( eme_is_empty_date( $member['start_date'] ) ) {
		$fields['start_date'] = eme_get_start_date( $membership, $member );
		$new_member           = 1;
		$fields['end_date']   = eme_get_next_end_date( $membership, $fields['start_date'], $new_member );
		if ( $member['status_automatic'] ) {
			$fields['status'] = eme_member_calc_status( $fields['start_date'], $fields['end_date'], $membership['duration_period'], $membership['properties']['grace_period'] );
		}
	} elseif ( eme_is_empty_date( $member['end_date'] ) ) {
		$new_member         = 1;
		$fields['end_date'] = eme_get_next_end_date( $membership, $member['start_date'], $new_member );
		if ( $member['status_automatic'] ) {
			$fields['status'] = eme_member_calc_status( $member['start_date'], $fields['end_date'], $membership['duration_period'], $membership['properties']['grace_period'] );
		}
	} elseif ( $member['status_automatic'] ) {
			$fields['status'] = eme_member_calc_status( $member['start_date'], $member['end_date'], $membership['duration_period'], $membership['properties']['grace_period'] );
	}

	$res                = $wpdb->update( $table, $fields, $where );
	$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
	if ( ! empty( $related_member_ids ) ) {
		foreach ( $related_member_ids as $related_member_id ) {
			$where['member_id'] = $related_member_id;
			$wpdb->update( $table, $fields, $where );
		}
	}
	return $res;
}

function eme_member_set_unpaid( $member ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	// do nothing if not paid or if a family member
	if ( ! $member['paid'] || ! empty( $member['related_member_id'] ) ) {
		return true;
	}

	$where                  = [];
	$fields                 = [];
	$where['member_id']     = $member['member_id'];
	$fields['paid']         = 0;
	$fields['payment_date'] = '';
	// reset the status to pending if calc was automatic (otherwise you could have active paid => active unpaid which would be weird)
	if ( $member['status_automatic'] ) {
		$fields['status'] = EME_MEMBER_STATUS_PENDING;
	}

	$res                = $wpdb->update( $table, $fields, $where );
	$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
	if ( ! empty( $related_member_ids ) ) {
		foreach ( $related_member_ids as $related_member_id ) {
			$where['member_id'] = $related_member_id;
			$wpdb->update( $table, $fields, $where );
		}
	}
	return $res;
}

function eme_extend_member( $member, $pg = '', $pg_pid = '' ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

	if ( empty( $member['end_date'] ) ) {
		return eme_member_set_paid( $member, $pg, $pg_pid );
	}
	if ( ! empty( $member['related_member_id'] ) ) {
		return 0;
	}
	$membership              = eme_get_membership( $member['membership_id'] );
	$where                   = [];
	$fields                  = [];
	$where['member_id']      = $member['member_id'];
	$fields['paid']          = 1;
	$fields['pg']            = $pg;
	$fields['pg_pid']        = $pg_pid;
	$fields['reminder']      = 0;
	$fields['reminder_date'] = '0000-00-00 00:00:00';
	$fields['renewal_count'] = $member['renewal_count'] + 1;
	// the function is called for active or grace status, so let's set to active if needed
	if ( $member['status'] == EME_MEMBER_STATUS_GRACE ) {
		$fields['status'] = EME_MEMBER_STATUS_ACTIVE;
	}
	$fields['payment_date'] = current_time( 'mysql', false );
	$fields['end_date']     = eme_get_next_end_date( $membership, $member['end_date'] );
	$res                    = $wpdb->update( $table, $fields, $where );
	$related_member_ids     = eme_get_family_member_ids( $member['member_id'] );
	if ( ! empty( $related_member_ids ) ) {
		foreach ( $related_member_ids as $related_member_id ) {
			$where['member_id'] = $related_member_id;
			$wpdb->update( $table, $fields, $where );
		}
	}
	return $res;
}

function eme_renew_expired_member( $member, $pg = '', $pg_pid = '' ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

	if ( ! empty( $member['related_member_id'] ) ) {
		return 0;
	}
	$membership             = eme_get_membership( $member['membership_id'] );
	$where                  = [];
	$fields                 = [];
	$where['member_id']     = $member['member_id'];
	$fields['paid']         = 1;
	$fields['pg']           = $pg;
	$fields['pg_pid']       = $pg_pid;
	$fields['payment_date'] = current_time( 'mysql', false );
	// set the third option to eme_get_start_date to 1, to force a new startdate (only has an effect for rolling-type memberships)
	$fields['start_date']       = eme_get_start_date( $membership, $member, 1 );
	$fields['end_date']         = eme_get_next_end_date( $membership, $fields['start_date'] );
	$fields['renewal_count']    = 0;
	$fields['status_automatic'] = 1;
	$fields['status']           = eme_member_calc_status( $fields['start_date'], $fields['end_date'], $membership['duration_period'], $membership['properties']['grace_period'] );

	$res                = $wpdb->update( $table, $fields, $where );
	$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
	if ( ! empty( $related_member_ids ) ) {
		foreach ( $related_member_ids as $related_member_id ) {
			$where['member_id'] = $related_member_id;
			$wpdb->update( $table, $fields, $where );
		}
	}
	return $res;
}

function eme_get_family_member_ids( $member_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	if ( empty( $member_id ) ) {
		return false;
	}
	$sql = $wpdb->prepare( "select member_id from $table where related_member_id=%d", $member_id );
	return $wpdb->get_col( $sql );
}

function eme_member_set_status( $member_id, $status ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

	$where              = [];
	$fields             = [];
	$where['member_id'] = $member_id;

	$fields['status'] = $status;
	$res = $wpdb->update( $table, $fields, $where );
	if ( has_action( 'eme_member_status_change_action' ) ) {
                do_action( 'eme_member_status_change_action', $member_id, $status );
        }
	return $res;
}

function eme_stop_member( $member_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

	$where              = [];
	$fields             = [];
	$where['member_id'] = $member_id;
	// first we work on the main account
	$where['related_member_id'] = 0;

	$member = eme_get_member( $member_id );

	// only for member with active or grace status
	if ( $member['status'] != EME_MEMBER_STATUS_ACTIVE && $member['status'] != EME_MEMBER_STATUS_GRACE ) {
		return false;
	}

	// when we stop the member, make sure the end date reflects this too
	// this is more relevant when manually marking a member as stopped
	if ( ! eme_is_empty_date( $member['end_date'] ) ) {
		$eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
		// set at midnight from today
		$eme_date_obj_now->today();
		$end_date_obj = ExpressiveDate::createFromFormat( 'Y-m-d', $member['end_date'], ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
		if ( $end_date_obj > $eme_date_obj_now ) {
			$fields['end_date'] = $eme_date_obj_now->getDate();
		}
	}

	$fields['status_automatic'] = 0;
	$fields['status']           = EME_MEMBER_STATUS_EXPIRED;
	// we set the paid status to 0 so a call to eme_member_payment_url will show the payment form again for an expired member too
	$fields['paid'] = 0;
	$res = $wpdb->update( $table, $fields, $where );
	if ( has_action( 'eme_member_status_change_action' ) ) {
                do_action( 'eme_member_status_change_action', $member_id, EME_MEMBER_STATUS_EXPIRED );
        }

	// now that this is done, work on the family members
	if ( $res === false ) {
		return false;
	} else {
		$related_member_ids = eme_get_family_member_ids( $member_id );
		if ( ! empty( $related_member_ids ) ) {
			$where2 = [];
			foreach ( $related_member_ids as $related_member_id ) {
				$where2['member_id'] = $related_member_id;
				$wpdb->update( $table, $fields, $where2 );
				if ( has_action( 'eme_member_status_change_action' ) ) {
					do_action( 'eme_member_status_change_action', $related_member_id, EME_MEMBER_STATUS_EXPIRED );
				}
			}
		}
		return true;
	}
}

function eme_email_member_action( $member, $action ) {
	$person       = eme_get_person( $member['person_id'] );
	$person_email = $person['email'];
	$person_name  = eme_format_full_name( $person['firstname'], $person['lastname'] );

	$membership     = eme_get_membership( $member['membership_id'] );
	$contact        = eme_get_contact( $membership['properties']['contact_id'] );
	$contact_email  = $contact->user_email;
	$contact_name   = $contact->display_name;
	$mail_text_html = get_option( 'eme_rsvp_send_html' ) ? 'htmlmail' : 'text';

	// first get the initial values
	$member_subject  = '';
	$member_body     = '';
	$contact_subject = '';
	$contact_body    = '';
	$atts            = [];
	if ( $action == 'expiration_reminder' ) {
		$member_subject = $membership['properties']['reminder_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['reminder_body_text'] ) ) {
			$member_body = $membership['properties']['reminder_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['reminder_body_format_tpl'] );
		}
	} elseif ( $action == 'markPaid' ) {
		$member_subject = $membership['properties']['paid_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['paid_body_text'] ) ) {
			$member_body = $membership['properties']['paid_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['paid_body_format_tpl'] );
		}
		$contact_subject = $membership['properties']['contact_paid_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['contact_paid_body_text'] ) ) {
			$contact_body = $membership['properties']['contact_paid_body_text'];
		} else {
			$contact_body = eme_get_template_format_plain( $membership['properties']['contact_paid_body_format_tpl'] );
		}
		$template_id = $membership['properties']['member_template_id'];
		if ( $template_id ) {
			$atts[] = eme_generate_member_pdf( $member, $membership, $template_id );
		}
	} elseif ( $action == 'resendPaidMember' ) {
		$member_subject = $membership['properties']['paid_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['paid_body_text'] ) ) {
			$member_body = $membership['properties']['paid_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['paid_body_format_tpl'] );
		}
		$template_id = $membership['properties']['member_template_id'];
		if ( $template_id ) {
			$atts[] = eme_generate_member_pdf( $member, $membership, $template_id );
		}
	} elseif ( $action == 'extendMember' ) {
		$member_subject = $membership['properties']['extended_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['extended_body_text'] ) ) {
			$member_body = $membership['properties']['extended_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['extended_body_format_tpl'] );
		}
		$template_id = $membership['properties']['member_template_id'];
		if ( $template_id ) {
			$atts[] = eme_generate_member_pdf( $member, $membership, $template_id );
		}
	} elseif ( $action == 'updateMember' ) {
		$member_subject = $membership['properties']['updated_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['updated_body_text'] ) ) {
			$member_body = $membership['properties']['updated_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['updated_body_format_tpl'] );
		}
	} elseif ( $action == 'stopMember' ) {
		$member_subject = $membership['properties']['stop_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['stop_body_text'] ) ) {
			$member_body = $membership['properties']['stop_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['stop_body_format_tpl'] );
		}
		$contact_subject = $membership['properties']['contact_stop_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['contact_stop_body_text'] ) ) {
			$contact_body = $membership['properties']['contact_stop_body_text'];
		} else {
			$contact_body = eme_get_template_format_plain( $membership['properties']['contact_stop_body_format_tpl'] );
		}
	} elseif ( $action == 'ipnReceived' ) {
		$contact_subject = $membership['properties']['contact_ipn_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['contact_ipn_body_text'] ) ) {
			$contact_body = $membership['properties']['contact_ipn_body_text'];
		} else {
			$contact_body = eme_get_template_format_plain( $membership['properties']['contact_ipn_body_format_tpl'] );
		}
	} elseif ( $action == 'newMember' || $action == 'resendPendingMember' || empty( $action ) ) {
		$member_subject = $membership['properties']['new_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['new_body_text'] ) ) {
			$member_body = $membership['properties']['new_body_text'];
		} else {
			$member_body = eme_get_template_format_plain( $membership['properties']['new_body_format_tpl'] );
		}
		$contact_subject = $membership['properties']['contact_new_subject_format'];
		if ( ! eme_is_empty_string( $membership['properties']['contact_new_body_text'] ) ) {
			$contact_body = $membership['properties']['contact_new_body_text'];
		} else {
			$contact_body = eme_get_template_format_plain( $membership['properties']['contact_new_body_format_tpl'] );
		}
		$attachment_ids = $membership['properties']['newmember_attach_ids'];
		if ( ! empty( $attachment_ids ) ) {
			$atts = explode( ',', $attachment_ids );
		}
	}

	// replace needed placeholders for the member and the contact
	if ( ! empty( $member_subject ) ) {
		$member_subject = eme_replace_member_placeholders( $member_subject, $membership, $member, 'text' );
	}
	if ( ! empty( $member_body ) ) {
		$member_body = eme_replace_member_placeholders( $member_body, $membership, $member, $mail_text_html );
	}
	if ( ! empty( $contact_subject ) ) {
		$contact_subject = eme_replace_member_placeholders( $contact_subject, $membership, $member, 'text' );
	}
	if ( ! empty( $contact_body ) ) {
		$contact_body = eme_replace_member_placeholders( $contact_body, $membership, $member, $mail_text_html );
	}

	// now an action, so you can hook into everything
	if ( has_action( 'eme_member_email_action' ) ) {
		do_action( 'eme_member_email_action', $member, $action, $member_subject, $member_body );
	}

	$mail_res = true; // make sure we return true if no mail is sent due to empty subject or body
	// first send the mail to the contact person, including all attachments for the member
	if ( ! empty( $contact_subject ) && ! empty( $contact_body ) ) {
		$mail_res = eme_queue_mail( $contact_subject, $contact_body, $contact_email, $contact_name, $contact_email, $contact_name, $contact_email, $contact_name, 0, 0, 0, $atts );
	}

	// we overwrite mail_res if a member mail is sent too, because then that result is what interests us
	if ( ! empty( $member_subject ) && ! empty( $member_body ) ) {
		$mail_res = eme_queue_mail( $member_subject, $member_body, $contact_email, $contact_name, $person_email, $person_name, $contact_email, $contact_name, 0, 0, $member['member_id'], $atts );
	}

	return $mail_res;
}

function eme_add_member_form_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
			    'id'   => 0,
			    'name' => '',
		    ],
		    $atts
	    )
	);
	if ( ! empty( $name ) ) {
		$membership = eme_get_membership( $name );
		$id         = $membership['membership_id'];
	}
	$member = eme_new_member();
	if ( $id ) {
		return eme_member_form( $member, $id );
	}
}

function eme_mymemberships_list_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
	    shortcode_atts(
		    [
			    'template_id'        => 0,
			    'template_id_header' => 0,
			    'template_id_footer' => 0,
		    ],
		    $atts
	    )
	);
	if ( is_user_logged_in() ) {
		$wp_id = get_current_user_id();
		if ( $wp_id ) {
			$format = eme_get_template_format( $template_id );
			if ( ! empty( $template_id_header ) ) {
				$header = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_header ) ) );
			} else {
				$header = "";
			}
			if ( ! empty( $template_id_footer ) ) {
				$footer = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_footer ) ) );
			} else {
				$footer = "";
			}
			$ids_arr = eme_get_memberids_by_wpid( $wp_id );

			$result = $header;
			$lang   = eme_detect_lang();
			foreach ( $ids_arr as $member_id ) {
				$member     = eme_get_member( $member_id );
				$membership = eme_get_membership( $member['membership_id'] );
				$result    .= eme_replace_member_placeholders( $format, $membership, $member, 'html', $lang );
			}
			$result .= $footer;
			return $result;
		} else {
			return '';
		}
	}
}

function eme_members_report_link_shortcode( $atts ) {
	global $post;
	eme_enqueue_frontend();
	extract(
		shortcode_atts(
			[
				'group_id'           => 0,
				'membership_id'      => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'link_text'          => __( 'Members CSV', 'events-made-easy' ),
				'public_access'      => 0,
			],
			$atts
		)
	);
	$public_access = filter_var( $public_access, FILTER_VALIDATE_BOOLEAN );

	if ( ( ! is_user_logged_in() || ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) && ! $public_access ) {
		return;
	}
	// public access? Then page needs to be password protected
	if ( $public_access && empty( $post->post_password ) ) {
		return;
	}

	if ( empty( $template_id ) ) {
		return '';
	}
	$args                = compact( 'group_id', 'membership_id', 'template_id', 'template_id_header', 'public_access' );
	$args['eme_members'] = 'report';
	$url                 = eme_current_page_url( $args );
	// add nonce, so public access can't be faked
	if ( $public_access ) {
		$url = wp_nonce_url( $url, "eme_members $public_access", 'eme_members_nonce' );
	}
	return "<a href='$url' title='" . esc_attr( $link_text ) . "'>" . esc_html( $link_text ) . '</a>';
}

function eme_members_shortcode( $atts ) {
	eme_enqueue_frontend();
	extract(
		shortcode_atts(
			[
				'group_id'           => 0,
				'membership_id'      => 0,
				'template_id'        => 0,
				'template_id_header' => 0,
				'template_id_footer' => 0,
			],
			$atts
		)
	);

	if ( ! empty( $group_id ) ) {
		$member_ids = eme_get_groups_member_ids( $group_id );
	} elseif ( ! empty( $membership_id ) ) {
		$member_ids = eme_get_memberships_member_ids( $membership_id );
	} else {
		return '';
	}

	if ( empty( $template_id ) ) {
		return '';
	}

	$format            = '';
	$eme_format_header = '';
	$eme_format_footer = '';
	$format                = eme_get_template_format( $template_id );
	if ( $template_id_header ) {
		$eme_format_header = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_header ) ) );
	}
	if ( $template_id_footer ) {
		$eme_format_footer = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_footer ) ) );
	}
	$output = '';
	$lang       = eme_detect_lang();
	foreach ( $member_ids as $member_id ) {
		$member     = eme_get_member( $member_id );
		$membership = eme_get_membership( $member['membership_id'] );
		$output    .= eme_replace_member_placeholders( $format, $membership, $member, 'html', $lang );
	}
	$output = $eme_format_header . $output . $eme_format_footer;
	return $output;
}

function eme_members_frontend_csv_report( $group_id, $membership_id, $template_id, $template_id_header ) {
	if ( ! empty( $group_id ) ) {
		$member_ids = eme_get_groups_member_ids( $group_id );
	} elseif ( ! empty( $membership_id ) ) {
		$member_ids = eme_get_memberships_member_ids( $membership_id );
	} else {
		return '';
	}

	if ( empty( $template_id ) ) {
		return '';
	}

	$format            = '';
	$eme_format_header = '';
	// no nl2br for csv output
	$format = eme_get_template_format( $template_id, 0 );

	eme_nocache_headers();
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=report-' . date( 'Ymd-His' ) . '.csv' );
	$fp = fopen( 'php://output', 'w' );

	if ( $template_id_header ) {
		// no nl2br for csv output
		$eme_format_header = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_header, 0 ) ) );
		$headers               = explode( ',', $eme_format_header );
		eme_fputcsv( $fp, $headers );
	}

	$lang = eme_detect_lang();
	foreach ( $member_ids as $member_id ) {
		$member     = eme_get_member( $member_id );
		$membership = eme_get_membership( $member['membership_id'] );
		$line       = [];
		$format_arr = explode( ',', $format );
		$line_count = 1;
		foreach ( $format_arr as $single_format ) {
			#$line[]=eme_replace_member_placeholders($single_format, $membership, $member, "text");
			$el       = eme_replace_member_placeholders( $single_format, $membership, $member, 'text', $lang );
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
			eme_fputcsv( $fp, $output );
		}
	}
	fclose( $fp );
	exit;
}

add_action( 'add_meta_boxes', 'eme_access_meta_box' );
function eme_access_meta_box() {
	// side, normal or advanced (normal and advanced put it below the editor
	add_meta_box( 'eme_access-meta-box', 'EME Access check', 'eme_access_meta_box_cb', 'page', 'normal' );
	add_meta_box( 'eme_access-meta-box', 'EME Access check', 'eme_access_meta_box_cb', 'post', 'normal' );
}
function eme_access_meta_box_cb( $post ) {
	$custom_values          = get_post_custom( $post->ID );
	$selected_membershipids = isset( $custom_values['eme_membershipids'] ) ? $custom_values['eme_membershipids'][0] : '';
	$selected_groupids      = isset( $custom_values['eme_groupids'] ) ? $custom_values['eme_groupids'][0] : '';
	$access_denied_tpl      = ! empty( $custom_values['eme_access_denied'] ) ? intval( $custom_values['eme_access_denied'][0] ) : 0;
	$drip_counter           = ! empty( $custom_values['eme_drip_counter'] ) ? intval( $custom_values['eme_drip_counter'][0] ) : 0;
	if ( eme_is_serialized( $selected_membershipids ) ) {
		$selected_membershipids_arr = eme_unserialize( $selected_membershipids );
	} else {
		$selected_membershipids_arr = [ $selected_membershipids ];
	}
	if ( eme_is_serialized( $selected_groupids ) ) {
		$selected_groupids_arr = eme_unserialize( $selected_groupids );
	} else {
		$selected_groupids_arr = [ $selected_groupids ];
	}
	$all_memberships = eme_get_memberships();
	$all_groups      = eme_get_static_groups();
	echo "<label for='eme_membershipids'>" . esc_html__( 'Limit access to EME members of', 'events-made-easy' ) . '</label><br>';
	$memberships_arr = [];
	foreach ( $all_memberships as $membership ) {
		$memberships_arr[ $membership['membership_id'] ] = $membership['name'];
	}
	echo eme_ui_checkbox( $selected_membershipids_arr, 'eme_membershipids', $memberships_arr, false, 0 );
	echo "<br><label for='eme_drip_counter'>" . esc_html__( 'Allow access after the membership has been active for this many days (drip content):', 'events-made-easy' ) . '</label>&nbsp;';
	echo "<input type='number' id='eme_drip_counter' name='eme_drip_counter' value='$drip_counter'>";
	echo '<br><br>';
	echo "<label for='eme_membershipids'>" . esc_html__( 'Limit access to EME people that are members of the following groups', 'events-made-easy' ) . '</label><br>';
	$groups_arr = [];
	foreach ( $all_groups as $group ) {
		$groups_arr[ $group['group_id'] ] = $group['name'];
	}
	echo eme_ui_checkbox( $selected_groupids_arr, 'eme_groupids', $groups_arr, false, 0 );

	echo "<br><label for='eme_access_denied'>" . esc_html__( 'Access denied message template', 'events-made-easy' ) . '</label>&nbsp;';
	#echo "<input type='text' name='eme_access_denied' id='eme_access_denied' value='$text'>";
	$templates_array = eme_get_templates_array_by_id();
	echo eme_ui_select( $access_denied_tpl, 'eme_access_denied', $templates_array );
		echo "<br><p class='eme_smaller'>" . esc_html__( 'The format of the text shown if access to the page is denied. If left empty, a default message will be shown.', 'events-made-easy' ) . '</p>';

	#$eme_editor_settings = eme_get_editor_settings();
	#wp_editor($text,'eme_access_denied',$eme_editor_settings);
	wp_nonce_field( 'eme_meta_box', 'eme_meta_box_nonce' );
}
add_action( 'save_post', 'eme_access_meta_box_save' );
function eme_access_meta_box_save( $post_id ) {
	// Bail if we're doing an auto save
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// if our nonce isn't there, or we can't verify it, bail
	if ( ! isset( $_POST['eme_meta_box_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_meta_box_nonce']), 'eme_meta_box' ) ) {
		return;
	}

	// if our current user can't edit this post, bail
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['eme_membershipids'] ) && eme_is_numeric_array( $_POST['eme_membershipids'] ) ) {
		update_post_meta( $post_id, 'eme_membershipids', $_POST['eme_membershipids'] );
	} else {
		delete_post_meta( $post_id, 'eme_membershipids' );
	}
	if ( isset( $_POST['eme_groupids'] ) && eme_is_numeric_array( $_POST['eme_groupids'] ) ) {
		update_post_meta( $post_id, 'eme_groupids', $_POST['eme_groupids'] );
	} else {
		delete_post_meta( $post_id, 'eme_groupids' );
	}

	if ( isset( $_POST['eme_access_denied'] ) ) {
		#$allowed=array(
		#   'a' => array( // on allow a tags
		#       'href' => array() // and those anchors can only have href attribute
		#   )
		#);
		update_post_meta( $post_id, 'eme_access_denied', wp_kses_post( $_POST['eme_access_denied'] ) );
	}
	if ( isset( $_POST['eme_drip_counter'] ) ) {
		update_post_meta( $post_id, 'eme_drip_counter', intval( $_POST['eme_drip_counter'] ) );
	}
}

add_action( 'wp_ajax_eme_add_member', 'eme_add_member_ajax' );
add_action( 'wp_ajax_nopriv_eme_add_member', 'eme_add_member_ajax' );
function eme_add_member_ajax() {
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

	if ( ! isset( $_POST['membership_id'] ) ) {
		$form_html = __( 'No membership selected', 'events-made-easy' );
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_html,
			]
		);
		wp_die();
	} else {
		$membership = eme_get_membership( intval( $_POST['membership_id'] ) );
	}

        $captcha_res = eme_check_captchas( $membership['properties'] );

	// check for wrong discount codes
	$tmp_member     = eme_member_from_form( $membership );
	$dcodes_entered = $tmp_member['dcodes_entered'];
	$dcodes_used    = $tmp_member['dcodes_used'];
	if ( ! empty( $dcodes_entered ) ) {
		if ( ! $tmp_member['discount'] || empty( $dcodes_used ) || count( $dcodes_used ) != count( $dcodes_entered ) ) {
			$result = __( 'You did not enter a valid discount code', 'events-made-easy' );
			echo wp_json_encode(
				[
					'Result'      => 'NOK',
					'htmlmessage' => $result,
				]
			);
			wp_die();
		}
	}

	if ( has_filter( 'eme_eval_member_form_post_filter' ) ) {
		$eval_filter_return = apply_filters( 'eme_eval_member_form_post_filter', $membership );
	} else {
		$eval_filter_return = [
			0 => 1,
			1 => '',
		];
	}

	if ( is_array( $eval_filter_return ) && ! $eval_filter_return[0] ) {
		// the result of own eval rules failed, so let's use that as a result
		$form_result_message = $eval_filter_return[1];
		$payment_id          = 0;
	} else {
		$member_res          = eme_add_update_member();
		$form_result_message = $member_res[0];
		$payment_id          = $member_res[1];
	}

	// let's decide for the first event wether or not payment is needed
	if ( $payment_id && eme_membership_has_pgs_configured( $membership ) && !$membership['properties']['skippaymentoptions']) {
		eme_captcha_remove( $captcha_res );
		$total_price = eme_get_member_payment_price( $payment_id );

		// count the payment gateways active for this membership
		$pg_count = eme_membership_count_pgs( $membership );

		if ( $total_price > 0 ) {
			if ( $pg_count == 1 && get_option( 'eme_pg_submit_immediately' ) ) {
				$payment_form = eme_payment_member_form( $payment_id );
				echo wp_json_encode(
					[
						'Result'      => 'OK',
						'htmlmessage' => $form_result_message,
						'paymentform' => $payment_form,
					]
				);
			} elseif ( get_option( 'eme_payment_redirect' ) ) {
				$payment     = eme_get_payment( $payment_id );
				$payment_url = eme_payment_url( $payment );
				$waitperiod      = intval( get_option( 'eme_payment_redirect_wait' ) ) * 1000;
				$redirect_msg    = get_option( 'eme_payment_redirect_msg' );
				if ( ! empty( $redirect_msg ) ) {
					$redirect_msg         = str_replace( '#_PAYMENT_URL', $payment_url, $redirect_msg );
					$form_result_message .= '<br>' . $redirect_msg;
				}
				echo wp_json_encode(
					[
						'Result'          => 'OK',
						'htmlmessage'     => $form_result_message,
						'waitperiod'      => $waitperiod,
						'paymentredirect' => $payment_url,
					]
				);
			} else {
				$payment_form = eme_payment_member_form( $payment_id );
				echo wp_json_encode(
					[
						'Result'      => 'OK',
						'htmlmessage' => $form_result_message,
						'paymentform' => $payment_form,
					]
				);
			}
		} else {
			// price=0
			echo wp_json_encode(
				[
					'Result'      => 'OK',
					'htmlmessage' => $form_result_message,
				]
			);
		}
	} elseif ( $payment_id ) {
		eme_captcha_remove( $captcha_res );
		echo wp_json_encode(
			[
				'Result'      => 'OK',
				'htmlmessage' => $form_result_message,
			]
		);
	} else {
		// failed
		echo wp_json_encode(
			[
				'Result'      => 'NOK',
				'htmlmessage' => $form_result_message,
			]
		);
	}
	wp_die();
}

function eme_replace_member_placeholders( $format, $membership, $member, $target = 'html', $lang = '', $take_answers_from_post = 0 ) {
	$orig_target  = $target;
	if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
		$target = 'html';
	}

	// replace EME language tags as early as possible
	$format = eme_translate_string_nowptrans( $format );

	if ( $member['person_id'] == -1 ) {
		// -1 ? then this is from a fake member
		$person         = eme_add_update_person_from_form( 0, '', '', '', 0, 0, 1 );
		$person_answers = [];
		$member_answers = eme_get_member_post_answers( $member, 0 ); // add the 0-option to exclude dynamic answers
		$dyn_answers    = [];
		$files          = [];
	} else {
		$person         = eme_get_person( $member['person_id'] );
		$person_answers = eme_get_person_answers( $member['person_id'] );
		if ( $take_answers_from_post ) {
			$member_answers = eme_get_member_post_answers( $member, 0 ); // add the 0-option to exclude dynamic answers
		} else {
			$member_answers = eme_get_nodyndata_member_answers( $member['member_id'] );
		}
		$dyn_answers = ( isset( $membership['properties']['dyndata'] ) ) ? eme_get_dyndata_member_answers( $member['member_id'] ) : [];
		$files       = eme_get_uploaded_files( $member['member_id'], 'members' );
	}
	$answers = array_merge( $member_answers, $person_answers );
	if ( empty( $lang ) && ! empty( $person['lang'] ) ) {
		$lang = $person['lang'];
	}
	if ( empty( $lang ) ) {
		$lang = eme_detect_lang();
	}

	$total_member_price = eme_get_total_member_price( $member );

	// no payment id yet? let's create one (can be old members, older imports, ...)
	if ( empty( $member['payment_id'] ) ) {
		$member['payment_id'] = eme_create_member_payment( $member['member_id'] );
	}

	// replace the generic placeholders
	$format = eme_replace_generic_placeholders( $format, $orig_target );

	$needle_offset = 0;
	preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$replacement        = '';
		$found              = 1;
		$need_escape        = 0;

		# support for #_MEMBERxxx and #_MEMBER_xxx
                $result = preg_replace( '/#_MEMBER(_)?/', '#_', $result );

		if ( strstr( $result, '#ESC' ) ) {
			$result      = str_replace( '#ESC', '#', $result );
			$need_escape = 1;
		}
		if ( preg_match( '/#_ID/', $result ) ) {
			$replacement = $member['member_id'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_TOTALDISCOUNT$/', $result ) ) {
			if ( $need_escape ) {
				$replacement = $member['discount'];
			} else {
				$replacement = eme_localized_price( $member['discount'], $membership['properties']['currency'], $target );
			}
		} elseif ( preg_match( '/#_APPLIEDDISCOUNTNAMES$/', $result ) ) {
			if ( ! empty( $member['discountids'] ) ) {
				$discount_ids   = explode( ',', $member['discountids'] );
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
			$dcodes_entered = $member['dcodes_entered'];
			$replacement    = join( ', ', $dcodes_entered );
		} elseif ( preg_match( '/#_DISCOUNTCODES_VALID|#_DISCOUNTCODES_USED$/', $result ) ) {
			$dcodes_used = $member['dcodes_used'];
			$replacement = join( ', ', $dcodes_used );
		} elseif ( preg_match( '/#_PRICE$/', $result ) ) {
			$replacement = eme_localized_price( $total_member_price, $membership['properties']['currency'], $target );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE_NO_VAT/', $result ) ) {
			$price       = $total_member_price / ( 1 + $membership['properties']['vat_pct'] / 100 );
			$replacement = eme_localized_price( $price, $membership['properties']['currency'], $target );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE_VAT_ONLY/', $result ) ) {
			$price       = $total_member_price - $total_member_price / ( 1 + $membership['properties']['vat_pct'] / 100 );
			$replacement = eme_localized_price( $price, $membership['properties']['currency'], $target );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_CURRENCY$/', $result ) ) {
			$replacement = $membership['properties']['currency'];
			if ( $target == 'html' ) {
				$replacement = apply_filters( 'eme_general', $replacement );
			} elseif ( $target == 'rss' ) {
				$replacement = apply_filters( 'the_content_rss', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_CURRENCYSYMBOL$/', $result ) ) {
			$replacement = eme_localized_currencysymbol( $membership['properties']['currency'] );
			if ( $target == 'html' ) {
				$replacement = apply_filters( 'eme_general', $replacement );
			} elseif ( $target == 'rss' ) {
				$replacement = apply_filters( 'the_content_rss', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_TRANSFER_NBR_BE97|UNIQUE_NBR/', $result ) ) {
			$replacement = eme_unique_nbr_formatted( $member['unique_nbr'] );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_LASTSEEN/', $result ) ) {
			if ( ! eme_is_empty_datetime( $member['last_seen'] ) ) {
				$replacement = eme_localized_datetime( $member['last_seen'], EME_TIMEZONE );
			} else {
				$replacement = __( 'Never', 'events-made-easy' );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_CREATIONDATE\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_date( $member['creation_date'], EME_TIMEZONE, $matches[1] );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_STARTDATE\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_date( $member['start_date'], EME_TIMEZONE, $matches[1] );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_ENDDATE\{(.+?)\}/', $result, $matches ) ) {
			if ( eme_is_expired_member( $member ) && $need_escape ) {
				$replacement = 'expired';
			} elseif ( eme_is_active_member( $member ) && ( $membership['duration_period'] == 'forever' ) ) {
				if ( $need_escape ) {
					$replacement = 'forever';
				} else {
					$replacement = __( 'no end date', 'events-made-easy' );
				}
			} elseif ( ! eme_is_empty_date( $member['end_date'] ) ) {
				$replacement = eme_localized_date( $member['end_date'], EME_TIMEZONE, $matches[1] );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_NEXTENDDATE\{(.+?)\}/', $result, $matches ) ) {
			if ( eme_is_expired_member( $member ) && $need_escape ) {
				$replacement = 'expired';
			} elseif ( eme_is_active_member( $member ) && ( $membership['duration_period'] == 'forever' ) ) {
				if ( $need_escape ) {
					$replacement = 'forever';
				} else {
					$replacement = __( 'no end date', 'events-made-easy' );
				}
			} elseif ( ! eme_is_empty_date( $member['end_date'] ) ) {
				$next_end_date = eme_get_next_end_date( $membership, $member['end_date'] );
				$replacement   = eme_localized_date( $next_end_date, EME_TIMEZONE, $matches[1] );
			} elseif ( eme_is_empty_date( $member['end_date'] ) ) {
				$start_date    = eme_get_start_date( $membership, $member );
				$next_end_date = eme_get_next_end_date( $membership, $start_date );
				$replacement       = eme_localized_date( $next_end_date, EME_TIMEZONE, $matches[1] );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_CREATIONDATE$/', $result ) ) {
			$replacement = eme_localized_date( $member['creation_date'], EME_TIMEZONE );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_STARTDATE$/', $result ) ) {
			$replacement = eme_localized_date( $member['start_date'], EME_TIMEZONE );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_ENDDATE$/', $result ) ) {
			if ( eme_is_expired_member( $member ) && $need_escape ) {
				$replacement = 'expired';
			} elseif ( eme_is_active_member( $member ) && ( $membership['duration_period'] == 'forever' ) ) {
				if ( $need_escape ) {
					$replacement = 'forever';
				} else {
					$replacement = __( 'no end date', 'events-made-easy' );
				}
			} elseif ( ! eme_is_empty_date( $member['end_date'] ) ) {
				$replacement = eme_localized_date( $member['end_date'], EME_TIMEZONE );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_NEXTENDDATE$/', $result ) ) {
			if ( eme_is_expired_member( $member ) ) {
				if ( $need_escape ) {
					$replacement = 'expired';
				} else {
					$replacement = __( 'expired', 'events-made-easy' );
				}
			} elseif ( eme_is_active_member( $member ) && ( $membership['duration_period'] == 'forever' ) ) {
				if ( $need_escape ) {
					$replacement = 'forever';
				} else {
					$replacement = __( 'no end date', 'events-made-easy' );
				}
			} elseif ( ! eme_is_empty_date( $member['end_date'] ) ) {
				$next_end_date = eme_get_next_end_date( $membership, $member['end_date'] );
				$replacement   = eme_localized_date( $next_end_date, EME_TIMEZONE );
			} elseif ( eme_is_empty_date( $member['end_date'] ) ) {
				$start_date    = eme_get_start_date( $membership, $member );
				$next_end_date = eme_get_next_end_date( $membership, $start_date );
				$replacement       = eme_localized_date( $next_end_date, EME_TIMEZONE );
			}
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PAYMENTDATE\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_date( $member['payment_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( preg_match( '/#_PAYMENTDATE/', $result ) ) {
			$replacement = eme_localized_date( $member['payment_date'], EME_TIMEZONE );
		} elseif ( preg_match( '/#_PAYMENTTIME\{(.+?)\}/', $result, $matches ) ) {
			$replacement = eme_localized_time( $member['payment_date'], EME_TIMEZONE, $matches[1] );
		} elseif ( preg_match( '/#_PAYMENTTIME/', $result ) ) {
			$replacement = eme_localized_time( $member['payment_date'], EME_TIMEZONE );
		} elseif ( preg_match( '/#_STATUS$/', $result ) ) {
			$eme_member_status_array = eme_member_status_array();
			$replacement             = $eme_member_status_array[ $member['status'] ];
		} elseif ( preg_match( '/#_IS_MEMBER_PENDING$/', $result ) ) {
			if ( $member['status'] == EME_MEMBER_STATUS_PENDING ) {
				$replacement = 1;
			} else {
				$replacement = O;
			}
		} elseif ( preg_match( '/#_IS_MEMBER_ACTIVE$/', $result ) ) {
			if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE ) {
				$replacement = 1;
			} else {
				$replacement = O;
			}
		} elseif ( preg_match( '/#_IS_MEMBER_GRACE$/', $result ) ) {
			if ( $member['status'] == EME_MEMBER_STATUS_GRACE ) {
				$replacement = 1;
			} else {
				$replacement = O;
			}
		} elseif ( preg_match( '/#_IS_MEMBER_EXPIRED$/', $result ) ) {
			if ( $member['status'] == EME_MEMBER_STATUS_EXPIRED ) {
				$replacement = 1;
			} else {
				$replacement = O;
			}
		} elseif ( preg_match( '/#_FAMILYCOUNT/', $result ) ) {
			$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
			if ( ! empty( $related_member_ids ) ) {
				$replacement = count( $related_member_ids );
			} else {
				$replacement = 0;
			}
		} elseif ( preg_match( '/#_FAMILYMEMBERS/', $result ) ) {
			$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
			if ( ! empty( $related_member_ids ) ) {
				$replacement = "<table style='border-collapse: collapse;border: 1px solid black;' class='eme_dyndata_table'>";
				foreach ( $related_member_ids as $related_member_id ) {
					$related_member = eme_get_member( $related_member_id );
					if ( $related_member ) {
						$related_person = eme_get_person( $related_member['person_id'] );
						if ( $related_person ) {
							$replacement .= "<tr class='eme_dyndata_row'><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_left'>" . eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'] ) ) . "</td><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_right'>" . eme_esc_html( $related_person['email'] ) . '</td></tr>';
						}
					}
				}
				$replacement .= '</table>';
			}
		} elseif ( preg_match( '/#_PDF_URL\{(.+?)\}$/', $result, $matches ) ) {
			$template_id = intval( $matches[1] );
			$targetPath  = EME_UPLOAD_DIR . '/members/' . $member['member_id'];
			$pdf_path    = '';
			if ( is_dir( $targetPath ) ) {
				foreach ( glob( "$targetPath/member-$template_id-*.pdf" ) as $filename ) {
					$pdf_path = $filename;
				}
			}
			if ( empty( $pdf_path ) ) {
				$pdf_path = eme_generate_member_pdf( $member, $membership, $template_id );
			}
			if ( ! empty( $pdf_path ) ) {
				$replacement = EME_UPLOAD_URL . '/members/' . $member['member_id'] . '/' . basename( $pdf_path );
			}
		} elseif ( preg_match( '/#_PAYMENTID/', $result ) ) {
			$replacement = $member['payment_id'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PAYMENT_URL/', $result ) ) {
			$payment = eme_get_payment( $member['payment_id'] );
			if ( $payment ) {
				$replacement = eme_payment_url( $payment );
				if ( $target == 'html' ) {
					$replacement = esc_url( $replacement );
				}
			}
		} elseif ( preg_match( '/#_QRCODE(\{.+?\})?$/', $result, $matches ) ) {
			if ( isset( $matches[1] ) ) {
				// remove { and } (first and last char of second match)
				$size = substr( $matches[1], 1, -1 );
			} else {
				$size = 'medium';
			}
			$targetBasePath             = EME_UPLOAD_DIR . '/members/' . $member['member_id'];
			$targetBaseUrl              = EME_UPLOAD_URL . '/members/' . $member['member_id'];
			$url_to_encode              = eme_member_url( $member );
			[$target_file, $target_url] = eme_generate_qrcode( $url_to_encode, $targetBasePath, $targetBaseUrl, $size );
			if ( is_file( $target_file ) ) {
				[$width, $height, $type, $attr] = getimagesize( $target_file );
				$replacement = "<img width='$width' height='$height' src='$target_url'>";
			}
		} elseif ( preg_match( '/#_DYNAMICFIELD\{(.+?)\}$/', $result, $matches ) ) {
			$field_key = $matches[1];
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
		} elseif ( preg_match( '/#_DYNAMICDATA$/', $result ) ) {
			if ( ! empty( $dyn_answers ) ) {
				if ( $target == 'html' ) {
					$replacement = "<table style='border-collapse: collapse;border: 1px solid black;' class='eme_dyndata_table'>";
				}
				$old_grouping = 1;
				$old_occurence    = 0;
				foreach ( $dyn_answers as $answer ) {
					$grouping      = $answer['eme_grouping'];
					$occurence     = $answer['occurence'];
					$class         = 'eme_print_formfield' . $answer['field_id'];
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
				// to close the last table
				if ( $target == 'html' ) {
					$replacement .= '</table>';
				}
				$replacement = eme_translate( $replacement, $lang );
				if ( $target == 'html' ) {
					$replacement = apply_filters( 'eme_general', $replacement );
				} else {
					$replacement = apply_filters( 'eme_text', $replacement );
				}
			}
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
		} elseif ( preg_match( '/#_PERSONAL_FILES/', $result ) ) {
			$res_files    = [];
			$person_files = eme_get_uploaded_files( $member['person_id'], 'people' );
			foreach ( $person_files as $file ) {
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
		} elseif ( preg_match( '/#_FIELDNAME\{(.+?)\}/', $result, $matches ) ) {
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
			if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'members' ] ) ) {
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
						if ( $matches[1] == 'VALUE' && $formfield['field_type'] == 'file' ) {
							// for file, we can show the url. For multifile this would not make any sense
							$field_replace = $file['url'] ;
						} else {
							if ( $target == 'html' ) {
								$field_replace .= eme_get_uploaded_file_html( $file ) . '<br>';
							} else {
								$field_replace .= $file['name'] . ' [' . $file['url'] . ']' . "\n";
							}
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
				// no members custom field? Then leave it alone
				$found = 0;
			}
		} else {
			$found = 0;
		}
		if ( $found ) {
			$format     = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	$do_shortcode = 0;
	$format       = eme_replace_payment_gateway_placeholders( $format, $member['pg'], $total_member_price, $membership['properties']['currency'], $membership['properties']['vat_pct'], $orig_target, $lang, $do_shortcode );

	// now some html
	if ( $target == 'html' ) {
		$format = eme_nl2br_save_html( $format );
	}

	$format = eme_replace_membership_placeholders( $format, $membership, $orig_target, $lang, $do_shortcode );
	if ( $member['person_id'] != -1 ) {
		$format = eme_replace_people_placeholders( $format, $person, $orig_target, $lang, $do_shortcode );
	}
	return do_shortcode( $format );
}

function eme_replace_membership_placeholders( $format, $membership, $target = 'html', $lang = '', $do_shortcode = 1, $recursion_level = 0 ) {
	// replace EME language tags as early as possible
	$format = eme_translate_string_nowptrans( $format );

	$orig_target  = $target;
	if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
		$target = 'html';
	}

	if ( ! empty( $membership ) && isset( $membership['membership_id'] ) ) {
		$answers = eme_get_membership_answers( $membership['membership_id'] );
		$files   = eme_get_uploaded_files( $membership['membership_id'], 'memberships' );
	} else {
		$answers = [];
		$files   = [];
	}

	// replace what we can inside curly brackets
	if ( $recursion_level <= 1 ) {
		$needle_offset = 0;
		preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})(\{(?>[^{}]+|(?2))*\})?/', $format, $placeholders, PREG_OFFSET_CAPTURE );
		$curlies = array_merge( $placeholders[2], $placeholders[3] );
		foreach ( $curlies as $orig_result ) {
			if ( empty( $orig_result[0] ) ) {
				continue; // don't even handle empty results, avoid php warning
			}
			$orig_result_length = strlen( $orig_result[0] );
			if ( $orig_result_length <= 2 ) {
				continue; // strlen 2 is the empty {}, so no need to handle
			}
			$orig_result_needle = $orig_result[1] - $needle_offset;
			$replacement        = '';
			$found              = 1;
			if ( strstr( $orig_result[0], '#' ) ) {
				$replacement = eme_replace_membership_placeholders( $orig_result[0], $membership, $target, $lang, $do_shortcode, $recursion_level + 1 );
			} else {
				$found = 0;
			}
			if ( $found ) {
				$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
				$needle_offset += $orig_result_length - strlen( $replacement );
			}
		}
	}

	if ( $recursion_level == 0 ) {
		// replace the generic placeholders
		$format = eme_replace_generic_placeholders( $format, $orig_target );
	}

	$needle_offset = 0;
	preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$replacement        = '';
		$found              = 1;

		# support for #_MEMBERSHIPxxx and #_MEMBERSHIP_xxx
                $result = preg_replace( '/#_MEMBERSHIP(_)?/', '#_', $result );

		if ( preg_match( '/#_NAME/', $result ) ) {
			$replacement = $membership['name'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_DESCRIPTION/', $result ) ) {
			$replacement = $membership['description'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE$/', $result ) ) {
			$price       = $membership['properties']['price'];
			$currency    = $membership['properties']['currency'];
			$replacement = eme_localized_price( $price, $currency );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE_NO_VAT/', $result ) ) {
			$price       = $membership['properties']['price'] / ( 1 + $membership['properties']['vat_pct'] / 100 );
			$currency    = $membership['properties']['currency'];
			$replacement = eme_localized_price( $price, $currency );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE_VAT_ONLY/', $result ) ) {
			$price       = $membership['properties']['price'];
			$price       = $price - $price / ( 1 + $membership['properties']['vat_pct'] / 100 );
			$currency    = $membership['properties']['currency'];
			$replacement = eme_localized_price( $price, $currency );
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_PRICE_VAT_PCT/', $result ) ) {
			$replacement = $membership['properties']['vat_pct'];
			if ( $target == 'html' ) {
				$replacement = eme_esc_html( $replacement );
				$replacement = apply_filters( 'eme_general', $replacement );
			} else {
				$replacement = apply_filters( 'eme_text', $replacement );
			}
		} elseif ( preg_match( '/#_CONTACT/', $result ) ) {
			$contact = eme_get_contact( $membership['properties']['contact_id'] );
			if ( $contact ) {
				if ( $result == '#_CONTACTPERSON' ) {
					$t_format = '#_NAME';
				} elseif ( $result == '#_CONTACTEMAIL' ) {
					$t_format = '#_EMAIL';
				} else {
					$t_format = $result;
				}
				$t_format = str_replace( '#_CONTACT', '#_', $t_format );
				$t_format = str_replace( '#_AUTHOR', '#_', $t_format );
				$person   = eme_get_person_by_wp_id( $contact->ID );
				if ( $person ) {
					// to be consistent: #_CONTACTNAME returns the full name if not linked to an EME user, so we do that here too
					if ( $t_format == '#_NAME' ) {
						$t_format = '#_FULLNAME';
					}
					$replacement = eme_replace_people_placeholders( $t_format, $person, $target, $lang, 0 );
				} else {
					if ( preg_match( '/#_NAME/', $t_format ) ) {
						$replacement = $contact->display_name;
						if ( $target == 'html' ) {
							$replacement = eme_trans_esc_html( $replacement, $lang );
						}
					} elseif ( preg_match( '/#_LASTNAME/', $t_format ) ) {
						$replacement = $contact->user_lastname;
						if ( $target == 'html' ) {
							$replacement = eme_trans_esc_html( $replacement, $lang );
						}
					} elseif ( preg_match( '/#_FIRSTNAME/', $t_format ) ) {
						$replacement = $contact->user_firstname;
						if ( $target == 'html' ) {
							$replacement = eme_trans_esc_html( $replacement, $lang );
						}
					} elseif ( preg_match( '/#_EMAIL/', $t_format ) ) {
						$replacement = $contact->user_email;
						// ascii encode for primitive harvesting protection ...
						$replacement = eme_email_obfuscate( $replacement, $orig_target );
					} elseif ( preg_match( '/#_PHONE/', $t_format ) ) {
						$replacement = eme_get_user_phone( $contact->ID );
						if ( $target == 'html' ) {
							$replacement = eme_trans_esc_html( $replacement, $lang );
						}
					}

					if ( $target == 'html' ) {
						$replacement = apply_filters( 'eme_general', $replacement );
					} elseif ( $target == 'rss' ) {
						$replacement = apply_filters( 'the_content_rss', $replacement );
					} else {
						$replacement = apply_filters( 'eme_text', $replacement );
					}
				}
			}
		} elseif ( preg_match( '/#_FIELDNAME\{(.+?)\}/', $result, $matches ) ) {
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
			if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'memberships' ) {
				$field_id      = $formfield['field_id'];
				$field_replace = '';
				foreach ( $answers as $answer ) {
					if ( $answer['field_id'] == $field_id ) {
						if ( $matches[1] == 'VALUE' ) {
							$field_replace = eme_answer2readable( $answer['answer'], $formfield, 0, $sep, $target );
						} else {
							$field_replace = eme_answer2readable( $answer['answer'], $formfield, 1, $sep, $target );
						}
					}
				}
				foreach ( $files as $file ) {
					if ( $file['field_id'] == $field_id ) {
						if ( $matches[1] == 'VALUE' && $formfield['field_type'] == 'file' ) {
							// for file, we can show the url. For multifile this would not make any sense
							$field_replace = $file['url'] ;
						} else {
							if ( $target == 'html' ) {
								$field_replace .= eme_get_uploaded_file_html( $file ) . '<br>';
							} else {
								$field_replace .= $file['name'] . ' [' . $file['url'] . ']' . "\n";
							}
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
				// no memberships custom field? Then leave it alone
				$found = 0;
			}
		} else {
			$found = 0;
		}
		if ( $found ) {
			$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	if ( $recursion_level == 0 ) {
		// replace leftover generic placeholders
		$format = eme_replace_generic_placeholders( $format, $orig_target );

		// now translate the format itself
		$format = eme_translate( $format, $lang );

		// now some html
		if ( $target == 'html' ) {
			$format = eme_nl2br_save_html( $format );
		}

		if ( $do_shortcode ) {
			$format = do_shortcode( $format );
		}
	}
	return $format;
}

function eme_import_csv_members() {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;

	if ( ! current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
		return __( 'Access denied', 'events-made-easy' );
	}
	//validate whether uploaded file is a csv file
	$csvMimes = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];
	if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
		return sprintf( __( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
	}
	if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
		return __( 'Problem detected while uploading the file', 'events-made-easy' );
	}
	$updated   = 0;
	$inserted  = 0;
	$errors    = 0;
	$error_msg = '';
	$handle    = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
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
		$enclosure         = substr( $enclosure, 0, 1 );
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
	if ( ! in_array( 'lastname', $headers ) || ! in_array( 'firstname', $headers ) || ! in_array( 'email', $headers ) || ! in_array( 'membership', $headers ) || ! in_array( 'start_date', $headers ) ) {
		return __( 'Not all required fields present.', 'events-made-easy' );
	} else {
		// now loop over the rest
		while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
			$line = array_combine( $headers, $row );
			// remove columns with empty values
			$line = eme_array_remove_empty_elements( $line );
			if ( isset( $_POST['allow_empty_email'] ) && $_POST['allow_empty_email'] == 1 && ! isset( $line['email'] ) ) {
								$line['email']    = '';
								$line['massmail'] = 0;
			}
			// also allow empty firstname
			if ( ! isset( $line['firstname'] ) ) {
								$line['firstname'] = '';
			}
			if ( ! empty( $line['email'] ) && ! eme_is_email( $line['email'] ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (field %s not valid): %s', 'events-made-easy' ), 'email', implode( ',', $row ) ) );
			} elseif ( ! empty( $line['start_date'] ) && ! eme_is_date( $line['start_date'] ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (field %s not valid): %s', 'events-made-easy' ), 'start_date', implode( ',', $row ) ) );
			} elseif ( ! empty( $line['end_date'] ) && ! eme_is_date( $line['end_date'] ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (field %s not valid): %s', 'events-made-easy' ), 'end_date', implode( ',', $row ) ) );
			} elseif ( ! empty( $line['creation_date'] ) && ! ( eme_is_date( $line['creation_date'] ) || eme_is_datetime( $line['creation_date'] ) ) ) {
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (field %s not valid): %s', 'events-made-easy' ), 'creation_date', implode( ',', $row ) ) );
			} elseif ( isset( $line['lastname'] ) && isset( $line['firstname'] ) && isset( $line['email'] ) && isset( $line['membership'] ) && isset( $line['start_date'] ) ) {
				// we need at least 4 fields present, otherwise nothing will be done
				$person_id  = 0;
				$membership = eme_get_membership( $line['membership'] );
				if ( $membership ) {
					// if the person already exists: update him
					$person = eme_get_person_by_name_and_email( $line['lastname'], $line['firstname'], $line['email'] );
					if ( ! $person ) {
						$person = eme_get_person_by_email_only( $line['email'] );
					}
					if ( $person ) {
						$person_id = $person['person_id'];
					} else {
						$person = $line;
						// status should be active, but it can also be provided for members, so we override it in case it was present
						if ( isset( $person['status'] ) ) {
							$person['status'] = EME_PEOPLE_STATUS_ACTIVE;
						}
						$person_id = eme_db_insert_person( $person );
						if ( ! $person_id ) {
							++$errors;
							$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem updating the person in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
						}
					}
				} else {
					// if membership doesn't exist
					++$errors;
					$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (membership does not exist): %s', 'events-made-easy' ), implode( ',', $row ) ) );
				}
				if ( $membership && $person_id ) {
					if ( isset( $line['start_date'] ) ) {
						if ( ! isset( $line['end_date'] ) ) {
							$line['end_date'] = eme_get_next_end_date( $membership, $line['start_date'] );
						}
					} else {
						$line['start_date'] = '';
						$line['end_date']   = '';
					}
					$line['status']           = isset( $line['status'] ) ? intval( $line['status'] ) : EME_MEMBER_STATUS_PENDING;
					$line['status_automatic'] = isset( $line['status_automatic'] ) ? intval( $line['status_automatic'] ) : 1;
					$line['paid']             = isset( $line['paid'] ) ? intval( $line['paid'] ) : 1;
					$member_id                = eme_is_member( $person_id, $membership['membership_id'] );
					if ( $member_id ) {
						eme_db_update_member( $member_id, $line, $membership );
						++$updated;
					} else {
						$line['person_id'] = $person_id;
						// if the memberid is present as value to import, do that too if the memberid doesn't exist yet
						if ( ! empty( $line['member_id'] ) && empty( eme_get_member( $line['member_id'] ) ) ) {
							$member_id = eme_db_insert_member( $line, $membership, $line['member_id'] );
						} else {
							$member_id = eme_db_insert_member( $line, $membership );
						}
						if ( $member_id ) {
							// create/update the payment id, so an imported pending member can pay too
							$payment_id = eme_create_member_payment( $member_id );
							++$inserted;
						}
					}
					if ( $member_id ) {
						// now handle all the extra info, in the CSV they need to be named like 'answer_XX_fieldname' (with XX being a number starting from 0, e.g. answer_0_myfieldname)
						foreach ( $line as $key => $value ) {
							if ( preg_match( '/^answer_(.*)$/', $key, $matches ) ) {
								$grouping   = 0;
								$field_name = $matches[1];
								$formfield  = eme_get_formfield( $field_name );
								if ( ! empty( $formfield ) ) {
									$field_id = $formfield['field_id'];
									$sql      = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id=%d and field_id=%d and type='member'", $member_id, $field_id );
									$wpdb->query( $sql );
									$sql = $wpdb->prepare( "INSERT INTO $answers_table (related_id,field_id,answer,eme_grouping,type) VALUES (%d,%d,%s,%d,%s)", $member_id, $field_id, $value, $grouping, 'member' );
									$wpdb->query( $sql );
								}
							}
						}
					} else {
						++$errors;
						$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem inserting the member in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
					}
				}
			} else {
				// if lastname, firstname or email is empty
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (not all required fields are present): %s', 'events-made-easy' ), print_r( $line, true ) ) );
			}
		}
	}
	fclose( $handle );
	$result = sprintf( __( 'Import finished: %d inserts, %d updates, %d errors', 'events-made-easy' ), $inserted, $updated, $errors );
	if ( $errors ) {
		$result .= '<br>' . $error_msg;
	}
	eme_member_recalculate_status();
	return $result;
}

function eme_import_csv_member_dynamic_answers() {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;

	if ( ! current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
			return __( 'Access denied', 'events-made-easy' );
	}

	//validate whether uploaded file is a csv file
	$csvMimes = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];
	if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
		return sprintf( __( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
	}
	if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
		return __( 'Problem detected while uploading the file', 'events-made-easy' );
	}
	$inserted  = 0;
	$errors    = 0;
	$error_msg = '';
	$handle    = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
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
		$enclosure         = substr( $enclosure, 0, 1 );
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
	if ( ! in_array( 'lastname', $headers ) || ! in_array( 'firstname', $headers ) || ! in_array( 'email', $headers ) || ! in_array( 'membership', $headers ) ) {
		return __( 'Not all required fields present.', 'events-made-easy' );
	} else {
		// now loop over the rest
		// a simple array to be able to increase occurence counter based on memberid and grouping
		$occurences = [];
		while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
			$line = array_combine( $headers, $row );
			// remove columns with empty values
			$line = eme_array_remove_empty_elements( $line );
			if ( isset( $_POST['allow_empty_email'] ) && $_POST['allow_empty_email'] == 1 && ! isset( $line['email'] ) ) {
								$line['email']    = '';
								$line['massmail'] = 0;
			}

			// we need at least 4 fields present, otherwise nothing will be done
			if ( isset( $line['lastname'] ) && isset( $line['firstname'] ) && isset( $line['email'] ) && isset( $line['membership'] ) ) {
				// if the person already exists: update him
				$person_id  = 0;
				$membership = eme_get_membership( $line['membership'] );
				if ( $membership ) {
					$person = eme_get_person_by_name_and_email( $line['lastname'], $line['firstname'], $line['email'] );
					if ( $person ) {
						$person_id = $person['person_id'];
					} else {
						++$errors;
						$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (person does not exist): %s', 'events-made-easy' ), implode( ',', $row ) ) );
					}
				}
				if ( $membership && $person_id ) {
					$member_id = eme_is_member( $person_id, $membership['membership_id'] );
					if ( $member_id ) {
						if ( ! isset( $occurences[ $member_id ] ) ) {
							$occurences[ $member_id ] = [];
						}
						// make sure grouping contains a sensible value (we call it "index now, but keep "grouping" for backwards compat)
						if ( isset( $line['index'] ) ) {
							$grouping = intval( $line['index'] );
							if ( $grouping < 1 ) {
								$grouping = 1;
							}
						} elseif ( isset( $line['grouping'] ) ) {
							$grouping = intval( $line['grouping'] );
							if ( $grouping < 1 ) {
								$grouping = 1;
							}
						} else {
							$grouping = 1;
						}
						if ( ! isset( $occurences[ $member_id ][ $grouping ] ) ) {
							$occurence = 0;
						} else {
							$occurence = $occurences[ $member_id ][ $grouping ];
							++$occurence;
						}
						$occurences[ $member_id ][ $grouping ] = $occurence;
						// handle all the extra info, in the CSV they need to be named like 'answer_XX_fieldname' (with XX being a number starting from 0, e.g. answer_0_myfieldname)
						foreach ( $line as $key => $value ) {
							if ( preg_match( '/^answer_(.*)$/', $key, $matches ) ) {
								$field_name = $matches[1];
								$formfield  = eme_get_formfield( $field_name );
								if ( ! empty( $formfield ) ) {
									$field_id = $formfield['field_id'];
									$sql      = $wpdb->prepare( "INSERT INTO $answers_table (related_id,field_id,answer,eme_grouping,occurence,type) VALUES (%d,%d,%s,%d,%d,%s)", $member_id, $field_id, $value, $grouping, $occurence, 'member' );
									$wpdb->query( $sql );
									++$inserted;
								}
							}
						}
					} else {
						++$errors;
						$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (member does not exist): %s', 'events-made-easy' ), implode( ',', $row ) ) );
					}
				} else {
					// if membership doesn't exist
					++$errors;
					$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (membership does not exist): %s', 'events-made-easy' ), implode( ',', $row ) ) );
				}
			} else {
				// if lastname, firstname or email is empty
				++$errors;
				$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (not all required fields are present): %s', 'events-made-easy' ), implode( ',', $row ) ) );
			}
		}
	}
	fclose( $handle );
	$result = sprintf( __( 'Import finished: %d inserts, %d errors', 'events-made-easy' ), $inserted, $errors );
	if ( $errors ) {
		$result .= '<br>' . $error_msg;
	}
	return $result;
}


function eme_member_person_autocomplete_ajax( $no_wp_die = 0 ) {
	global $wpdb;
	$people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
		wp_die();
	}
	$return = [];
	$q      = '';
	if ( isset( $_REQUEST['lastname'] ) ) {
		$q = strtolower( eme_sanitize_request( $_REQUEST['lastname'] ) );
	} elseif ( isset( $_REQUEST['q'] ) ) {
		$q = strtolower( eme_sanitize_request( $_REQUEST['q'] ) );
	}

	if ( isset( $_REQUEST['membership_id'] ) ) {
		$membership_id = intval( $_REQUEST['membership_id'] );
	} else {
		$membership_id = 0;
	}
	if ( isset( $_REQUEST['exclude_personid'] ) ) {
		$exclude_personid = intval( $_REQUEST['exclude_personid'] );
	} else {
		$exclude_personid = 0;
	}
	if ( isset( $_REQUEST['related_member_id'] ) ) {
		$related_member_id = intval( $_REQUEST['related_member_id'] );
	} else {
		$related_member_id = 0;
	}

	header( 'Content-type: application/json; charset=utf-8' );
	if ( empty( $q ) ) {
		echo wp_json_encode( $return );
		if ( ! $no_wp_die ) {
			wp_die();
		}
		return;
	}

	$search = "(people.lastname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR people.firstname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR people.email LIKE '%" . esc_sql( $wpdb->esc_like($q) ) . "%')";
	if ( $exclude_personid > 0 ) {
		$search .= " AND members.person_id<>$exclude_personid";
	}
	if ( $related_member_id > 0 ) {
		$search .= " AND members.member_id<>$related_member_id";
	}
	// we need people not in the membership, so ...
	if ( $membership_id > 0 ) {
		$search .= " AND (members.membership_id<>$membership_id OR (members.status = " . EME_MEMBER_STATUS_EXPIRED . '))';
	}

	$sql = "SELECT people.person_id,people.lastname,people.firstname,people.email
	   FROM $members_table AS members
	   LEFT JOIN $people_table AS people ON members.person_id=people.person_id
           WHERE $search";

	$persons = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $persons as $item ) {
		$record              = [];
		$record['lastname']  = eme_esc_html( $item['lastname'] );
		$record['firstname'] = eme_esc_html( $item['firstname'] );
		$record['email']     = eme_esc_html( $item['email'] );
		$record['person_id'] = intval( $item['person_id'] );
		$return[]            = $record;
	}

	echo wp_json_encode( $return );
	if ( ! $no_wp_die ) {
		wp_die();
	}
}

function eme_member_main_account_autocomplete_ajax() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
		wp_die();
	}
	$return        = [];
	$q             = '';
	$membership_id = 0;
	$member_id     = 0;
	if ( ! empty( $_REQUEST['q'] ) ) {
		$q = strtolower( eme_sanitize_request( $_REQUEST['q'] ) );
	}
	if ( ! empty( $_REQUEST['member_id'] ) ) {
		$member_id = intval( $_REQUEST['member_id'] );
	}
	if ( ! empty( $_REQUEST['membership_id'] ) ) {
		$membership_id = intval( $_REQUEST['membership_id'] );
	}

	header( 'Content-type: application/json; charset=utf-8' );
	if ( empty( $q ) ) {
		echo wp_json_encode( $return );
		wp_die();
	}

	$search  = "(lastname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR firstname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR email LIKE '%" . esc_sql( $wpdb->esc_like($q) ) . "%') AND members.member_id <> $member_id AND members.related_member_id=0 AND members.membership_id=$membership_id";
	$persons = eme_get_members( '', $search );
	foreach ( $persons as $item ) {
		$record              = [];
		$record['lastname']  = eme_esc_html( $item['lastname'] );
		$record['firstname'] = eme_esc_html( $item['firstname'] );
		$record['email']     = eme_esc_html( $item['email'] );
		$record['member_id'] = intval( $item['member_id'] );
		$return[]            = $record;
	}

	echo wp_json_encode( $return );
	wp_die();
}

add_action( 'wp_ajax_eme_autocomplete_memberperson', 'eme_member_person_autocomplete_ajax' );
add_action( 'wp_ajax_eme_autocomplete_membermainaccount', 'eme_member_main_account_autocomplete_ajax' );
add_action( 'wp_ajax_eme_members_list', 'eme_ajax_members_list' );
add_action( 'wp_ajax_eme_members_select2', 'eme_ajax_members_select2' );
add_action( 'wp_ajax_eme_memberships_list', 'eme_ajax_memberships_list' );
add_action( 'wp_ajax_eme_manage_members', 'eme_ajax_manage_members' );
add_action( 'wp_ajax_eme_manage_memberships', 'eme_ajax_manage_memberships' );
add_action( 'wp_ajax_eme_store_members_query', 'eme_ajax_store_members_query' );

function eme_ajax_memberships_list() {
	global $wpdb;
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
			$ajaxResult['Result']      = 'Error';
			$ajaxResult['htmlmessage'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $ajaxResult );
			wp_die();
	}
	$status_active = EME_MEMBER_STATUS_ACTIVE;
	$status_grace  = EME_MEMBER_STATUS_GRACE;
	$table         = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$ajaxResult    = [];

	$formfields = eme_get_formfields( '', 'memberships' );

	$sql         = "SELECT COUNT(*) FROM $table";
	$recordCount = $wpdb->get_var( $sql );
	$start       = ( isset( $_REQUEST['jtStartIndex'] ) ) ? intval( $_REQUEST['jtStartIndex'] ) : 0;
	$pagesize    = ( isset( $_REQUEST['jtPageSize'] ) ) ? intval( $_REQUEST['jtPageSize'] ) : 10;
	$sorting     = ( ! empty( $_REQUEST['jtSorting'] ) && ! empty( eme_sanitize_sql_orderby( $_REQUEST['jtSorting'] ) ) ) ? 'ORDER BY ' . esc_sql( eme_sanitize_sql_orderby($_REQUEST['jtSorting']) ) : '';

	$sql         = $wpdb->prepare("SELECT membership_id,COUNT(*) AS familymembercount FROM $members_table WHERE status IN (%d,%d) AND related_member_id>0 GROUP BY membership_id", $status_active, $status_grace);
	$res         = $wpdb->get_results( $sql, ARRAY_A );
	$familymembercount = [];
	foreach ( $res as $val ) {
			$familymembercount[ $val['membership_id'] ] = $val['familymembercount'];
	}
	$sql         = $wpdb->prepare("SELECT membership_id,COUNT(*) AS mainmembercount FROM $members_table WHERE status IN (%d,%d) AND related_member_id=0 GROUP BY membership_id", $status_active, $status_grace);
	$res         = $wpdb->get_results( $sql, ARRAY_A );
	$mainmembercount = [];
	foreach ( $res as $val ) {
			$mainmembercount[ $val['membership_id'] ] = $val['mainmembercount'];
	}

	$sql     = "SELECT * FROM $table $sorting LIMIT $start,$pagesize";
	$rows    = $wpdb->get_results( $sql, ARRAY_A );
	$records = [];
	foreach ( $rows as $item ) {
		if ( empty( $item['name'] ) ) {
			$item['name'] = __( 'No name', 'events-made-easy' );
		}
		$item['properties'] = eme_init_membership_props( eme_unserialize( $item['properties'] ) );
		$contact            = eme_get_contact( $item['properties']['contact_id'] );
		$contact_email      = $contact->user_email;
		$contact_name       = $contact->display_name;

		$record                  = [];
		$record['membership_id'] = $item['membership_id'];
		if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			$record['name'] = "<a href='" . admin_url( 'admin.php?page=eme-memberships&amp;eme_admin_action=edit_membership&amp;membership_id=' . $item['membership_id'] ) . "' title='" . esc_html__( 'Edit membership', 'events-made-easy' ) . "'>" . eme_esc_html( $item['name'] ) . '</a>';
		} else {
			$record['name'] = eme_esc_html( $item['name'] );
		}

		if ( eme_is_empty_string( $item['properties']['member_form_text'] ) && empty( $item['properties']['member_form_tpl'] ) ) {
			$record['name'] .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . __( 'No membership form has been defined for this membership, a simple default will be used.', 'events-made-easy' ) . "'>";
		}

		$record['description'] = eme_esc_html( $item['description'] );
		if ( ! isset( $familymembercount[ $item['membership_id'] ] ) ) {
			$familymembercount[ $item['membership_id'] ] = 0;
		}
		if ( ! isset( $mainmembercount[ $item['membership_id'] ] ) ) {
			$mainmembercount[ $item['membership_id'] ] = 0;
		}
		$total = $mainmembercount[ $item['membership_id'] ] + $familymembercount[ $item['membership_id'] ];
		if ( ! empty( $item['properties']['family_membership'] ) ) {
			$record['membercount'] = $total . ' (' . sprintf( esc_html__( '%d head of the family accounts + %d family members', 'events-made-easy' ), $mainmembercount[ $item['membership_id'] ], $familymembercount[ $item['membership_id'] ] ) . ')';
		} else {
			$record['membercount'] = $total;
		}
		$record['contact'] = eme_esc_html( "$contact_name ($contact_email)" );
		$answers           = eme_get_membership_answers( $item['membership_id'] );
		foreach ( $formfields as $formfield ) {
			foreach ( $answers as $val ) {
				if ( $val['field_id'] == $formfield['field_id'] && $val['answer'] != '' ) {
					$tmp_answer = eme_answer2readable( $val['answer'], $formfield, 1, ',', 'text', 1 );
					// the 'FIELD_' value is used by the container-js
					$key = 'FIELD_' . $val['field_id'];
					if ( isset( $record[ $key ] ) ) {
						$record[ $key ] .= "<br>$tmp_answer";
					} else {
						$record[ $key ] = $tmp_answer;
					}
				}
			}
		}
		$files = eme_get_uploaded_files( $item['membership_id'], 'memberships' );
		foreach ( $files as $file ) {
			$key = 'FIELD_' . $file['field_id'];
			if ( isset( $record[ $key ] ) ) {
				$record[ $key ] .= eme_get_uploaded_file_html( $file );
			} else {
				$record[ $key ] = eme_get_uploaded_file_html( $file );
			}
		}

		$records[] = $record;
	}
	$ajaxResult['Result']           = 'OK';
	$ajaxResult['TotalRecordCount'] = $recordCount;
	$ajaxResult['Records']          = $records;
	print wp_json_encode( $ajaxResult );
	wp_die();
}

function eme_ajax_members_list( $dynamic_groupname = '' ) {
	global $wpdb;
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	$eme_member_status_array = eme_member_status_array();
	$pgs                     = eme_payment_gateways();
	$ajaxResult              = [];

	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
			$ajaxResult['Result']      = 'Error';
			$ajaxResult['htmlmessage'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $ajaxResult );
			wp_die();
	}

	if ( ! empty( $dynamic_groupname ) ) {
			$table         = EME_DB_PREFIX . EME_GROUPS_TBNAME;
			$group['type'] = 'dynamic_members';
		$group['name']     = $dynamic_groupname . ' ' . __( '(Dynamic)', 'events-made-easy' );
		$search_terms      = [];
		// the same as in add_update_group
		$search_fields = [ 'search_membershipids', 'search_memberstatus', 'search_person', 'search_groups', 'search_memberid', 'search_customfields', 'search_customfieldids' ];
		foreach ( $search_fields as $search_field ) {
			if ( isset( $_POST[ $search_field ] ) ) {
				$search_terms[ $search_field ] = esc_sql( eme_sanitize_request( $_POST[ $search_field ] ) );
			}
		}
		$group['search_terms'] = eme_serialize( $search_terms );
			return $wpdb->insert( $table, $group );
	}

	$start     = ( isset( $_REQUEST['jtStartIndex'] ) ) ? intval( $_REQUEST['jtStartIndex'] ) : 0;
	$pagesize  = ( isset( $_REQUEST['jtPageSize'] ) ) ? intval( $_REQUEST['jtPageSize'] ) : 10;
	$sorting   = ( ! empty( $_REQUEST['jtSorting'] ) && ! empty( eme_sanitize_sql_orderby( $_REQUEST['jtSorting'] ) ) ) ? 'ORDER BY ' . esc_sql( $_REQUEST['jtSorting'] ) : '';
	$count_sql = eme_get_sql_members_searchfields( $_POST, $start, $pagesize, $sorting, 1 );
	$sql       = eme_get_sql_members_searchfields( $_POST, $start, $pagesize, $sorting );

	$recordCount = $wpdb->get_var( $count_sql );
	$rows        = $wpdb->get_results( $sql, ARRAY_A );
	$wp_users    = eme_get_indexed_users();
	if ( get_option( 'eme_members_show_people_info' ) ) {
		$formfields = eme_get_formfields( '', 'members,people,generic' );
	} else {
		$formfields = eme_get_formfields( '', 'members,generic' );
	}
	$records = [];
	foreach ( $rows as $item ) {
		$record     = [];
		$membership = eme_get_membership( $item['membership_id'] );
		// we can sort on member_id, but in our constructed sql , we have members.member_id and ans.member_id
		// some mysql databases don't like it if you then just sort on member_id, so we'll change it to members.member_id
		$record['members.member_id'] = $item['member_id'];
		$record['related_member_id'] = '';
		if ( $item['related_member_id'] ) {
			$familytext     = eme_esc_html( __( '(family member)', 'events-made-easy' ) );
			$related_member = eme_get_member( $item['related_member_id'] );
			if ( $related_member ) {
				$related_person              = eme_get_person( $related_member['person_id'] );
				$record['related_member_id'] = "<a href='" . admin_url( 'admin.php?page=eme-members&amp;eme_admin_action=edit_member&amp;member_id=' . $item['related_member_id'] ) . "' title='" . esc_html__( 'Edit member', 'events-made-easy' ) . "'>" . eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'] ) ) . '</a>';
				$familytext                 .= '<br>' . esc_html__( 'Head of the family: ', 'events-made-easy' ) . $record['related_member_id'];
			}
		} elseif ( ! empty( $membership['properties']['family_membership'] ) ) {
				$familytext = '<br>' . esc_html__( '(head of the family)', 'events-made-easy' );
		} else {
			$familytext = '';
		}

		$record['person_id']  = $item['person_id'];
		$record['lastname']   = "<a href='" . admin_url( 'admin.php?page=eme-members&amp;eme_admin_action=edit_member&amp;member_id=' . $item['member_id'] ) . "' title='" . esc_html__( 'Edit member', 'events-made-easy' ) . "'>" . eme_esc_html( $item['lastname'] ) . '</a> ' . $familytext;
		$record['firstname']  = "<a href='" . admin_url( 'admin.php?page=eme-members&amp;eme_admin_action=edit_member&amp;member_id=' . $item['member_id'] ) . "' title='" . esc_html__( 'Edit member', 'events-made-easy' ) . "'>" . eme_esc_html( $item['firstname'] ) . '</a> ' . $familytext;
		$record['email']      = "<a href='" . admin_url( 'admin.php?page=eme-members&amp;eme_admin_action=edit_member&amp;member_id=' . $item['member_id'] ) . "' title='" . esc_html__( 'Edit member', 'events-made-easy' ) . "'>" . eme_esc_html( $item['email'] ) . '</a> ' . $familytext;
		$record['birthdate']  = eme_localized_date( $item['birthdate'], EME_TIMEZONE, 1 );
		$record['birthplace'] = eme_esc_html( $item['birthplace'] );
		$record['address1']   = eme_esc_html( $item['address1'] );
		$record['address2']   = eme_esc_html( $item['address2'] );
		$record['city']       = eme_esc_html( $item['city'] );
		$record['zip']        = eme_esc_html( $item['zip'] );
		if ( ! empty( $item['state_code'] ) ) {
				$record['state'] = eme_esc_html( eme_get_state_name( $item['state_code'], $item['country_code'] ) );
		} else {
			$record['state'] = '';
		}
		if ( ! empty( $item['country_code'] ) ) {
				$record['country'] = eme_esc_html( eme_get_country_name( $item['country_code'] ) );
		} else {
			$record['country'] = '';
		}
		if ( $membership ) {
			$record['membership_name'] = eme_esc_html( $membership['name'] );
		} else {
			$record['membership_name'] = '';
		}
		$record['start_date']      = eme_localized_date( $item['start_date'], EME_TIMEZONE, 1 );
		$record['end_date']        = eme_localized_date( $item['end_date'], EME_TIMEZONE, 1 );
		$record['creation_date']   = eme_localized_datetime( $item['creation_date'], EME_TIMEZONE, 1 );
		$record['last_seen']       = eme_localized_datetime( $item['last_seen'], EME_TIMEZONE, 1 );
		$record['payment_date']    = eme_localized_datetime( $item['payment_date'], EME_TIMEZONE, 1 );
		$record['reminder']        = intval( $item['reminder'] );
		$record['reminder_date']   = eme_localized_datetime( $item['reminder_date'], EME_TIMEZONE, 1 );
		$record['membershipprice'] = eme_localized_price( $membership['properties']['price'], $membership['properties']['currency'] );
		$record['totalprice']      = eme_localized_price( eme_get_total_member_price( $item ), $membership['properties']['currency'] );
		$record['discount']        = eme_localized_price( $item['discount'], $membership['properties']['currency'] );
		// dcodes_used is still eme_serialized here
		$record['dcodes_used']   = eme_esc_html( eme_unserialize( $item['dcodes_used'] ) );
		$record['renewal_count'] = intval( $item['renewal_count'] );
		$record['paid']          = ( $item['paid'] == 1 ) ? esc_html__( 'Yes', 'events-made-easy' ) : esc_html__( 'No', 'events-made-easy' );
		$record['payment_id']    = eme_esc_html( $item['payment_id'] );
		$record['unique_nbr']    = "<span title='" . sprintf( __( 'This is based on the payment ID of the member: %d', 'events-made-easy' ), $item['payment_id'] ) . "'>" . eme_esc_html( eme_unique_nbr_formatted( $item['unique_nbr'] ) ) . '</span>';
		$record['status']        = $eme_member_status_array[ $item['status'] ];
		$record['wp_id']         = eme_esc_html( $item['wp_id'] );
		$record['wp_nickname']   = '';
		$record['wp_dispname']   = '';
		if ( $item['wp_id'] && isset( $wp_users[ $record['wp_id'] ] ) ) {
			$record['wp_user'] = eme_esc_html( $wp_users[ $record['wp_id'] ] );
		} else {
			$record['wp_user'] = '';
		}

		if ( !empty( $item['pg'] ) ) {
			if ( isset( $pgs[ $item['pg'] ] ) ) {
				$record['pg'] = eme_esc_html( $pgs[ $item['pg'] ] );
			} else {
				$record['pg'] = 'UNKNOWN';
			}
			if ($item['pg'] == 'payconiq' && !empty($item['pg_pid'])) {
				$record['pg'] .= "<br><button id='button_".$item['payment_id']."' class='button action eme_iban_button' data-pg_pid='".$item['pg_pid']."'>".esc_html__('Get IBAN')."</button><span id='payconiq_".$item['payment_id']."'></span>";
			}
		} else {
			$record['pg'] = '';
		}
		$record['pg_pid'] = eme_esc_html( $item['pg_pid'] );
		$answers          = eme_get_member_answers( $item['member_id'] );
		foreach ( $formfields as $formfield ) {
			foreach ( $answers as $val ) {
				if ( $val['field_id'] == $formfield['field_id'] && $val['answer'] != '' ) {
					$tmp_answer = eme_answer2readable( $val['answer'], $formfield, 1, ',', 'text', 1 );
					// the 'FIELD_' value is used by the container-js
					$key = 'FIELD_' . $val['field_id'];
					if ( isset( $record[ $key ] ) ) {
						$record[ $key ] .= "<br>$tmp_answer";
					} else {
						$record[ $key ] = $tmp_answer;
					}
				}
			}
		}
		$files1 = eme_get_uploaded_files( $item['member_id'], 'members' );
		$files2 = eme_get_uploaded_files( $item['person_id'], 'people' );
		$files  = array_merge( $files1, $files2 );
		foreach ( $files as $file ) {
			$key = 'FIELD_' . $file['field_id'];
			if ( isset( $record[ $key ] ) ) {
				$record[ $key ] .= eme_get_uploaded_file_html( $file );
			} else {
				$record[ $key ] = eme_get_uploaded_file_html( $file );
			}
		}
		$records[] = $record;
	}
	$ajaxResult['Result']           = 'OK';
	$ajaxResult['TotalRecordCount'] = $recordCount;
	$ajaxResult['Records']          = $records;
	print wp_json_encode( $ajaxResult );
	wp_die();
}

function eme_ajax_members_select2() {
	global $wpdb;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
			$ajaxResult 		   = [];
			$ajaxResult['Result']      = 'Error';
			$ajaxResult['htmlmessage'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $ajaxResult );
			wp_die();
	}

	$table             = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$people_table      = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
	$members_table     = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$memberships_table = EME_DB_PREFIX . EME_MEMBERSHIPS_TBNAME;
	$jTableResult      = [];
	$q                 = isset( $_REQUEST['q'] ) ? strtolower( eme_sanitize_request( $_REQUEST['q'] ) ) : '';
	if ( ! empty( $q ) ) {
			$where = "(people.lastname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR people.firstname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR people.email LIKE '%" . esc_sql( $wpdb->esc_like($q) ) . "%')";
	} else {
			$where = '(1=1)';
	}
	$pagesize = intval( $_REQUEST['pagesize'] );
	//$start= isset($_REQUEST["page"]) ? intval($_REQUEST["page"])*$pagesize : 0;
	$start     = ( isset( $_REQUEST['page'] ) && intval( $_REQUEST['page'] ) > 0 ) ? ( intval( $_REQUEST['page'] ) - 1 ) * $pagesize : 0;
	$sql       = "SELECT members.member_id, people.lastname, people.firstname, people.wp_id, memberships.name AS membership_name
           FROM $members_table AS members
           LEFT JOIN $memberships_table AS memberships ON members.membership_id=memberships.membership_id
           LEFT JOIN $people_table as people ON members.person_id=people.person_id
           WHERE $where ORDER BY people.lastname, people.firstname LIMIT $start,$pagesize";
	$count_sql = "SELECT count(*)
           FROM $members_table AS members
           LEFT JOIN $memberships_table AS memberships ON members.membership_id=memberships.membership_id
           LEFT JOIN $people_table as people ON members.person_id=people.person_id
           WHERE $where";

	$records     = [];
	$recordCount = $wpdb->get_var( $count_sql );
	$members     = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $members as $member ) {
		$record       = [];
		$record['id'] = $member['member_id'];
		// no eme_esc_html here, select2 does it own escaping upon arrival
		$record['text'] = eme_format_full_name( $member['firstname'], $member['lastname'] ) . ' (' . $member['membership_name'] . ')';
		$records[]      = $record;
	}
	$jTableResult['TotalRecordCount'] = $recordCount;
	$jTableResult['Records']          = $records;
	print wp_json_encode( $jTableResult );
	wp_die();
}

function eme_ajax_store_members_query() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_members' ) ) ) {
		wp_die();
	}
	if ( ! empty( $_POST['dynamicgroupname'] ) ) {
		eme_ajax_members_list( $_POST['dynamicgroupname'] );
		$jTableResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'Dynamic group added', 'events-made-easy' ) . '</p></div>';
	} else {
		$jTableResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please enter a name for the group', 'events-made-easy' ) . '</p></div>';
	}
	print wp_json_encode( $jTableResult );
	wp_die();
}

function eme_ajax_manage_members() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( isset( $_REQUEST['do_action'] ) ) {
		$do_action    = eme_sanitize_request( $_REQUEST['do_action'] );
		$send_mail    = ( isset( $_POST['send_mail'] ) ) ? intval( $_POST['send_mail'] ) : 1;
		$trash_person = ( isset( $_POST['trash_person'] ) ) ? intval( $_POST['trash_person'] ) : 0;

		$ids     = eme_sanitize_request($_POST['member_id']);
		$ids_arr = explode( ',', $ids );
		if ( ! eme_is_numeric_array( $ids_arr ) || ! current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			$jTableResult['Result']      = 'Error';
			$jTableResult['htmlmessage'] = __( 'Access denied!', 'events-made-easy' );
			print wp_json_encode( $jTableResult );
			wp_die();
		}

		switch ( $do_action ) {
			case 'deleteMembers':
				eme_ajax_action_delete_members( $ids_arr, $trash_person );
				break;
			case 'acceptPayment':
				eme_ajax_action_payment_membership( $ids_arr, $send_mail );
				break;
			case 'markUnpaid':
				eme_ajax_action_set_member_unpaid( $ids_arr, 'updateMember', $send_mail );
				break;
			case 'stopMembership':
				eme_ajax_action_stop_membership( $ids_arr, 'stopMember', $send_mail );
				break;
			case 'resendPendingMember':
				eme_ajax_action_resend_pending_member( $ids_arr, $do_action );
				break;
			case 'resendPaidMember':
				eme_ajax_action_resend_paid_member( $ids_arr, $do_action );
				break;
			case 'resendExpirationReminders':
				eme_ajax_action_resend_member_reminders( $ids_arr );
				break;
			case 'memberMails':
				$template_id_subject = ( isset( $_POST['membermail_template_subject'] ) ) ? intval( $_POST['membermail_template_subject'] ) : 0;
				$template_id         = ( isset( $_POST['membermail_template'] ) ) ? intval( $_POST['membermail_template'] ) : 0;
				if ( $template_id_subject && $template_id ) {
					eme_ajax_action_send_member_mails( $ids_arr, $template_id_subject, $template_id );
				} else {
					$ajaxResult                = [];
					$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'Content of subject or body was empty, no mail has been sent.', 'events-made-easy' ) . '</p></div>';
					$ajaxResult['Result']      = 'ERROR';
					print wp_json_encode( $ajaxResult );
					wp_die();
				}
				break;
			case 'pdf':
				$template_id        = ( isset( $_POST['pdf_template'] ) ) ? intval( $_POST['pdf_template'] ) : 0;
				$template_id_header = ( isset( $_POST['pdf_template_header'] ) ) ? intval( $_POST['pdf_template_header'] ) : 0;
				$template_id_footer = ( isset( $_POST['pdf_template_footer'] ) ) ? intval( $_POST['pdf_template_footer'] ) : 0;
				if ( $template_id ) {
					eme_ajax_generate_member_pdf( $ids_arr, $template_id, $template_id_header, $template_id_footer );
				}
				break;
			case 'html':
				$template_id        = ( isset( $_POST['html_template'] ) ) ? intval( $_POST['html_template'] ) : 0;
				$template_id_header = ( isset( $_POST['html_template_header'] ) ) ? intval( $_POST['html_template_header'] ) : 0;
				$template_id_footer = ( isset( $_POST['html_template_footer'] ) ) ? intval( $_POST['html_template_footer'] ) : 0;
				if ( $template_id ) {
						eme_ajax_generate_member_html( $ids_arr, $template_id, $template_id_header, $template_id_footer );
				}
				break;
		}
	}
	wp_die();
}

function eme_ajax_manage_memberships() {
	$ajaxResult = [];
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( isset( $_REQUEST['do_action'] ) ) {
		$do_action = eme_sanitize_request( $_REQUEST['do_action'] );

		$ids     = eme_sanitize_request($_POST['membership_id']);
		$ids_arr = explode( ',', $ids );
		if ( ! eme_is_numeric_array( $ids_arr ) || ! current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
			$ajaxResult['Result']      = 'Error';
			$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'Access denied!', 'events-made-easy' ) . '</p></div>';
			print wp_json_encode( $ajaxResult );
			wp_die();
		}
		switch ( $do_action ) {
			case 'showMembershipStats':
				$membershipstats = eme_get_membership_stats( $ids );
				$ajaxResult['Result']      = 'OK';
				$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . $membershipstats . '</p></div>';
				print wp_json_encode( $ajaxResult );
				break;
			case 'deleteMemberships':
				foreach ( $ids_arr as $membership_id ) {
						eme_delete_membership( $membership_id );
				}
				$ajaxResult['Result']      = 'OK';
				$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'Memberships deleted.', 'events-made-easy' ) . '</p></div>';
				print wp_json_encode( $ajaxResult );
				break;
		}
	}
	wp_die();
}

function eme_ajax_action_send_member_mails( $ids_arr, $subject_template_id, $body_template_id ) {
	$mail_ok        = 1;
	$mail_text_html = get_option( 'eme_rsvp_send_html' ) ? 'html' : 'text';
	$subject        = eme_get_template_format_plain( $subject_template_id );
	$body           = eme_get_template_format_plain( $body_template_id );
	$ajaxResult     = [];
	if ( eme_is_empty_string( $subject ) || eme_is_empty_string( $body ) ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'Content of subject or body was empty, no mail has been sent.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
		print wp_json_encode( $ajaxResult );
		return;
	}
	foreach ( $ids_arr as $member_id ) {
		$member     = eme_get_member( $member_id );
		$person     = eme_get_person( $member['person_id'] );
		$membership = eme_get_membership( $member['membership_id'] );
		if ( $person && is_array( $person ) ) {
			$contact           = eme_get_contact( $membership['properties']['contact_id'] );
				$contact_email = $contact->user_email;
				$contact_name  = $contact->display_name;
				$tmp_subject   = eme_replace_member_placeholders( $subject, $membership, $member, $mail_text_html );
				$tmp_message   = eme_replace_member_placeholders( $body, $membership, $member, $mail_text_html );
				$person_name   = eme_format_full_name( $person['firstname'], $person['lastname'] );
				$mail_res      = eme_queue_mail( $tmp_subject, $tmp_message, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, 0, 0, $member['member_id'] );
			if ( ! $mail_res ) {
				$mail_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mail_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_delete_members( $ids_arr, $trash_person = 0 ) {
	global $wpdb;
	if ( $trash_person ) {
		$ids        = join( ',', $ids_arr );
		$person_ids = eme_get_person_ids_from_member_ids( $ids );
		if ( ! empty( $person_ids ) ) {
			eme_ajax_action_trash_people( join( ',', $person_ids ) );
		} else {
			$ajaxResult['Result']      = 'ERROR';
			$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'No corresponding persons found.', 'events-made-easy' ) . '</p></div>';
			print wp_json_encode( $ajaxResult );
		}
	} else {
		$ajaxResult = [];
		foreach ( $ids_arr as $member_id ) {
			eme_delete_member( $member_id );
		}
		$ajaxResult['Result']      = 'OK';
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'Members deleted.', 'events-made-easy' ) . '</p></div>';
		print wp_json_encode( $ajaxResult );
	}
}

function eme_ajax_action_set_member_unpaid( $ids_arr, $action, $send_mail ) {
	$action_ok = 1;
	$mails_ok  = 1;

	foreach ( $ids_arr as $member_id ) {
		$member = eme_get_member( $member_id );
		$res    = eme_member_set_unpaid( $member );
		if ( $res ) {
			if ( $send_mail ) {
					$member = eme_get_member( $member_id );
					$res2   = eme_email_member_action( $member, $action );
				if ( ! $res2 ) {
					$mails_ok = 0;
				}
			}
		} else {
				$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $mails_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_resend_paid_member( $ids_arr, $action ) {
	$mails_ok = 1;
	foreach ( $ids_arr as $member_id ) {
		$member = eme_get_member( $member_id );
		// we resend only for paid members
		if ( $member['status'] == EME_MEMBER_STATUS_ACTIVE && $member['paid'] ) {
			$res2 = eme_email_member_action( $member, $action );
			if ( ! $res2 ) {
				$mails_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mails_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_accept_member_payment( $payment_id, $pg = '', $pg_pid = '' ) {
	$member = eme_get_member_by_paymentid( $payment_id );
	// we don't accept payments for family members, only the head of the family
	if ( ! empty( $member['related_member_id'] ) ) {
		return;
	}
		$res = 0;
	switch ( $member['status'] ) {
		case EME_MEMBER_STATUS_EXPIRED:
			$res    = eme_renew_expired_member( $member, $pg, $pg_pid );
			$action = 'extendMember';
			break;
		case EME_MEMBER_STATUS_PENDING:
			$res    = eme_member_set_paid( $member, $pg, $pg_pid );
			$action = 'markPaid';
			break;
		case EME_MEMBER_STATUS_ACTIVE:
		case EME_MEMBER_STATUS_GRACE:
			$res    = eme_extend_member( $member, $pg, $pg_pid );
			$action = 'extendMember';
			break;
	}
	if ( $res ) {
		$membership = eme_get_membership( $member['membership_id'] );
		if ( ! empty( $membership['properties']['addpersontogroup'] ) ) {
			eme_add_persongroups( $member['person_id'], $membership['properties']['addpersontogroup'] );
		}
		$member = eme_get_member( $member['member_id'] );
		eme_email_member_action( $member, $action );
		$related_member_ids = eme_get_family_member_ids( $member['member_id'] );
		if ( ! empty( $related_member_ids ) ) {
			foreach ( $related_member_ids as $related_member_id ) {
				$related_member = eme_get_member( $related_member_id );
				if ( ! empty( $membership['properties']['addpersontogroup'] ) ) {
					eme_add_persongroups( $related_member['person_id'], $membership['properties']['addpersontogroup'] );
				}
				eme_email_member_action( $related_member, $action );
			}
		}
	}
		return $res;
}

function eme_ajax_action_payment_membership( $ids_arr, $send_mail ) {
	$action_ok = 1;
	foreach ( $ids_arr as $member_id ) {
		$member = eme_get_member( $member_id );
		// no payment id yet? let's create one (can be old members, older imports, ...)
		if ( empty( $member['payment_id'] ) ) {
			$member['payment_id'] = eme_create_member_payment( $member_id );
		}
		eme_mark_payment_paid( $member['payment_id'], 0 );
	}

	$ajaxResult                = [];
	$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
	$ajaxResult['Result']      = 'OK';
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_stop_membership( $ids_arr, $action, $send_mail ) {
	$action_ok = 1;
	$mails_ok  = 1;
	foreach ( $ids_arr as $member_id ) {
		$res = eme_stop_member( $member_id );
		if ( $res ) {
			if ( $send_mail ) {
					$member = eme_get_member( $member_id );
					$res2   = eme_email_member_action( $member, $action );
				if ( ! $res2 ) {
					$mails_ok = 0;
				}
			}
			$related_member_ids = eme_get_family_member_ids( $member_id );
			if ( ! empty( $related_member_ids ) ) {
				foreach ( $related_member_ids as $related_member_id ) {
					$member = eme_get_member( $related_member_id );
					eme_email_member_action( $member, $action );
				}
			}
		} else {
				$action_ok = 0;
		}
	}
	$ajaxResult = [];
	if ( $mails_ok && $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} elseif ( $action_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully but there were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There was a problem executing the desired action, please check your logs.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_resend_pending_member( $ids_arr, $action ) {
	$mails_ok = 1;
	foreach ( $ids_arr as $member_id ) {
		$member = eme_get_member( $member_id );
		if ( $member['status'] == EME_MEMBER_STATUS_PENDING && ! $member['related_member_id'] ) {
				$res2 = eme_email_member_action( $member, $action );
			if ( ! $res2 ) {
				$mails_ok = 0;
			}
		}
	}
	$ajaxResult = [];
	if ( $mails_ok ) {
		$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'OK';
	} else {
		$ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
		$ajaxResult['Result']      = 'ERROR';
	}
	print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_resend_member_reminders( $ids_arr ) {
	$mails_ok = 1;
	foreach ( $ids_arr as $member_id ) {
		$member = eme_get_member( $member_id );
		if ( ( $member['status'] == EME_MEMBER_STATUS_ACTIVE || $member['status'] == EME_MEMBER_STATUS_GRACE ) && ! $member['related_member_id'] ) {
				eme_member_send_expiration_reminder( $member_id );
		}
	}
	$ajaxResult                = [];
	$ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'The action has been executed successfully.', 'events-made-easy' ) . '</p></div>';
	$ajaxResult['Result']      = 'OK';
	print wp_json_encode( $ajaxResult );
}

function eme_generate_member_pdf( $member, $membership, $template_id ) {
	$template = eme_get_template( $template_id );
	// the template format needs br-handling, so lets use a handy function
		$format = eme_get_template_format( $template_id );

	require_once 'dompdf/2.0.3/vendor/autoload.php';
	// instantiate and use the dompdf class
	$options = new Dompdf\Options();
	$options->set( 'isRemoteEnabled', true );
	$options->set( 'isHtml5ParserEnabled', true );
	$dompdf      = new Dompdf\Dompdf( $options );
	$margin_info = 'margin: ' . $template['properties']['pdf_margins'];
	$font_info   = 'font-family: ' . get_option( 'eme_pdf_font' );
	$orientation = $template['properties']['pdf_orientation'];
	$pagesize    = $template['properties']['pdf_size'];
	if ( $pagesize == 'custom' ) {
		$pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
	}

	$dompdf->setPaper( $pagesize, $orientation );
	$css          = "\n<link rel='stylesheet' id='eme-css'  href='" . esc_url(EME_PLUGIN_URL) . "css/eme.css' type='text/css' media='all'>";
	$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
		$eme_css_url = get_stylesheet_directory_uri() . '/eme.css';
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
	// avoid a loop between eme_replace_member_placeholders and eme_generate_member_pdf
	$format = str_replace( '#_MEMBERPDF_URL', '', $format );

	$html .= eme_replace_member_placeholders( $format, $membership, $member );
	$html .= '</body></html>';
	$dompdf->loadHtml( $html, get_bloginfo( 'charset' ) );
	$dompdf->render();
	// now we know where to store it, so create the dir
		$targetPath = EME_UPLOAD_DIR . '/members/' . $member['member_id'];
	if ( ! is_dir( $targetPath ) ) {
		wp_mkdir_p( $targetPath );
	}
	if ( ! is_file( $targetPath . '/index.html' ) ) {
		touch( $targetPath . '/index.html' );
	}
	// unlink old pdf
	array_map( 'wp_delete_file', glob( "$targetPath/member-$template_id-*.pdf" ) );
	// now put new one
	$rand_id         = eme_random_id();
	$target_file = $targetPath . "/member-$template_id-$rand_id.pdf";
	file_put_contents( $target_file, $dompdf->output() );
	return $target_file;
}

function eme_ajax_generate_member_pdf( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
	$template = eme_get_template( $template_id );
	// the template format needs br-handling, so lets use a handy function
		$format = eme_get_template_format( $template_id );
	$header     = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_header ) ) );
		$footer = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_footer ) ) );

	require_once 'dompdf/2.0.3/vendor/autoload.php';
	// instantiate and use the dompdf class
	$options = new Dompdf\Options();
	$options->set( 'isRemoteEnabled', true );
	$options->set( 'isHtml5ParserEnabled', true );
	$dompdf      = new Dompdf\Dompdf( $options );
	$margin_info = 'margin: ' . $template['properties']['pdf_margins'];
	$font_info   = 'font-family: ' . get_option( 'eme_pdf_font' );
	$orientation = $template['properties']['pdf_orientation'];
	$pagesize    = $template['properties']['pdf_size'];
	if ( $pagesize == 'custom' ) {
		$pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
	}

	$dompdf->setPaper( $pagesize, $orientation );
		$css          = "\n<link rel='stylesheet' id='eme-css'  href='" . esc_url(EME_PLUGIN_URL) . "css/eme.css' type='text/css' media='all'>";
		$eme_css_name = get_stylesheet_directory() . '/eme.css';
	if ( file_exists( $eme_css_name ) ) {
			$eme_css_url = get_stylesheet_directory_uri() . '/eme.css';
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
	foreach ( $ids_arr as $member_id ) {
		$member     = eme_get_member( $member_id );
		$membership = eme_get_membership( $member['membership_id'] );
		$html      .= eme_replace_member_placeholders( $format, $membership, $member );
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

function eme_ajax_generate_member_html( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
	// the template format needs br-handling, so lets use a handy function
	$format = eme_get_template_format( $template_id );
	$header = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_header ) ) );
	$footer = eme_translate( eme_replace_generic_placeholders( eme_get_template_format( $template_id_footer ) ) );
	$extra_html_header = get_option( 'eme_html_header' );
        $extra_html_header = trim( preg_replace( '/\r\n/', "\n", $extra_html_header ) );
        $html   = "<html><head>$extra_html_header</head><body>$header";
	$total = count( $ids_arr );
	$i     = 1;
	$lang  = eme_detect_lang();
	foreach ( $ids_arr as $member_id ) {
		$member     = eme_get_member( $member_id );
		$membership = eme_get_membership( $member['membership_id'] );
		$html      .= eme_replace_member_placeholders( $format, $membership, $member, 'html', $lang );
	}
	$html .= "$footer</body></html>";
	print $html;
}

function eme_get_membership_post_answers() {
	$answers = [];
	foreach ( $_POST as $key => $value ) {
		if ( preg_match( '/^FIELD(\d+)$/', $key, $matches ) ) {
			$field_id  = intval( $matches[1] );
			$formfield = eme_get_formfield( $field_id );
			if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'memberships' ) {
				$value = eme_kses_maybe_unfiltered( $value );
				// for multivalue fields like checkbox, the value is in fact an array
				// to store it, we make it a simple "multi" string using eme_convert_array2multi, so later on when we need to parse the values
				// (when editing), we can re-convert it to an array with eme_convert_multi2array (see eme_formfields.php)
				if ( is_array( $value ) ) {
					$value = eme_convert_array2multi( $value );
				}
				$answer    = [
					'field_name'   => $formfield['field_name'],
					'field_id'     => $field_id,
					'extra_charge' => $formfield['extra_charge'],
					'answer'       => $value,
				];
				$answers[] = $answer;
			}
		}
	}
	return $answers;
}

function eme_get_membership_answers( $membership_id ) {
	global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$cf            = wp_cache_get( "eme_membership_cf $membership_id" );
	if ( $cf === false ) {
		$sql = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='membership'", $membership_id );
		$cf  = $wpdb->get_results( $sql, ARRAY_A );
		wp_cache_set( "eme_membership_cf $membership_id", $cf, '', 60 );
	}
	return $cf;
}

function eme_membership_store_answers( $membership_id ) {
	$answer_ids_seen = [];

	$all_answers   = eme_get_membership_answers( $membership_id );
	$found_answers = eme_get_membership_post_answers();
	foreach ( $found_answers as $answer ) {
		$formfield = eme_get_formfield( $answer['field_id'] );
		if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'memberships' ) {
			$answer_id = eme_get_answerid( $all_answers, $membership_id, 'membership', $answer['field_id'] );
			if ( $answer_id ) {
				eme_update_answer( $answer_id, $answer['answer'] );
			} else {
				$answer_id = eme_insert_answer( 'membership', $membership_id, $answer['field_id'], $answer['answer'] );
			}
			$answer_ids_seen[] = $answer_id;
		}
	}

	// delete old answer_ids
	foreach ( $all_answers as $answer ) {
		if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && $answer['type'] == 'membership' && $answer['related_id'] == $membership_id ) {
			eme_delete_answer( $answer_id );
		}
	}
	wp_cache_delete( "eme_membership_cf $membership_id" );
}

function eme_get_cf_membership_ids( $val, $field_id, $is_multi = 0 ) {
	global $wpdb;
	$table      = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$conditions = [];
	$val        = eme_kses( $val );

	if ( is_array( $val ) ) {
		foreach ( $val as $tmpval ) {
			$tmpval = esc_sql( $tmpval );
			if ( $is_multi ) {
				$conditions[] = "answer REGEXP '^" . $tmpval . '|\\\\|' . $tmpval . '\\\\||\\\\|' . $tmpval . '$' . "'";
			} else {
				$conditions[] = "answer LIKE '%$tmpval%'";
			}
		}
	} else {
		$val = esc_sql( $val );
		if ( $is_multi ) {
			$conditions[] = "answer REGEXP '^" . $val . '|\\\\|' . $val . '\\\\||\\\\|' . $val . '$' . "'";
		} else {
			$conditions[] = "answer LIKE '%$val%'";
		}
	}
	$condition = '';
	if ( ! empty( $conditions ) ) {
		$condition = 'AND (' . join( ' OR ', $conditions ) . ')';
	}
	$sql = "SELECT DISTINCT related_id FROM $table WHERE field_id=$field_id AND type='membership' $condition";
	return $wpdb->get_col( $sql );
}

function eme_get_membership_cf_answers_groupingids( $membership_id ) {
		global $wpdb;
	$answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
	$members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
		$sql       = $wpdb->prepare( "select distinct a.eme_grouping from $answers_table a left join $members_table m on m.member_id=a.related_id where m.membership_id=%d AND a.type='member'", $membership_id );
		return $wpdb->get_col( $sql );
}

?>
