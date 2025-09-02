<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_todo() {
	$todo = [
		'event_id'    => 0,
		'todo_offset' => 0,
		'name'        => '',
		'description' => '',
		'todo_seq'    => 1,
		'todo_nbr'    => 0,
	];
	return $todo;
}

function eme_handle_todos_post_adminform( $event_id ) {
	$eme_todos_arr = [];
	if ( empty( $_POST['eme_todos'] ) ) {
		return $eme_todos_arr;
	}
	$seq_nbr       = 1;
	$todo_nbr_seen = 0;
	foreach ( $_POST['eme_todos'] as $eme_todo ) {
		if ( ! empty( $eme_todo['todo_nbr'] ) && intval( $eme_todo['todo_nbr'] ) > $todo_nbr_seen ) {
			$todo_nbr_seen = intval( $eme_todo['todo_nbr'] );
		}
	}
	$next_todo_nbr = $todo_nbr_seen + 1;
	foreach ( $_POST['eme_todos'] as $eme_todo ) {
		$eme_todo['name']       = eme_sanitize_request( $eme_todo['name'] );
		$eme_todo['todo_seq']   = $seq_nbr;
		$eme_todo['event_id']   = $event_id;
		$eme_todo['todo_offset'] = intval( $eme_todo['todo_offset'] );
		if ( eme_is_empty_string( $eme_todo['name'] ) ) {
			continue;
		}
		$eme_todo['description'] = eme_sanitize_request( $eme_todo['description'] );
		// we check for todo nbr to know if we need an update or insert
		if ( empty( $eme_todo['todo_nbr'] ) ) {
			$eme_todo['todo_nbr'] = $next_todo_nbr;
			++$next_todo_nbr;
			$todo_id = eme_db_insert_todo( $eme_todo );
		} else {
			// we update by the combo event_id and todo_nbr and not by todo_id
			// that way we can do todo updates for recurrences too
			$todo_id = eme_db_update_todo_by_todo_nbr( $eme_todo );
		}
		$eme_todos_arr[] = $todo_id;
		++$seq_nbr;
	}
	return $eme_todos_arr;
}

function eme_db_insert_todo( $line ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;

	// first check for todo_nbr
	if (!isset($line['todo_nbr'])) {
		$sql      = $wpdb->prepare( "SELECT IFNULL(max(todo_nbr),0) FROM $table WHERE event_id = %d", $line['event_id'] );
		$todo_nbr = intval($wpdb->get_var( $sql ));
		$line['todo_nbr'] = $todo_nbr + 1;
	}
	$tmp_todo = eme_new_todo();
	// we only want the columns that interest us
	$keys = array_intersect_key( $line, $tmp_todo );
	$todo = array_merge( $tmp_todo, $keys );

	if ( $wpdb->insert( $table, $todo ) === false ) {
		return false;
	} else {
		$todo_id = $wpdb->insert_id;
		return $todo_id;
	}
}

function eme_db_update_todo_by_todo_nbr( $line ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;

	// get the todo id
	$sql     = $wpdb->prepare( "SELECT todo_id FROM $table WHERE event_id = %d AND todo_nbr = %d", $line['event_id'], $line['todo_nbr'] );
	$todo_id = $wpdb->get_var( $sql );
	if ( empty( $todo_id ) ) {
		// this happens for recurrences where e.g. a new day is added to the recurrence
		return eme_db_insert_todo( $line );
	} else {
		$line['todo_id'] = $todo_id;
		eme_db_update_todo( $line );
		return $todo_id;
	}
}

function eme_db_update_todo( $line ) {
	global $wpdb;
	$table            = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$where            = [];
	$where['todo_id'] = $line['todo_id'];

	$tmp_todo = eme_new_todo();
	// we only want the columns that interest us
	$keys = array_intersect_key( $line, $tmp_todo );
	$todo = array_merge( $tmp_todo, $keys );

	if ( $wpdb->update( $table, $todo, $where ) === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_mark_todo_sent( $todo_id ) {
	global $wpdb;
	$table            = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$where            = [];
	$where['todo_id'] = $todo_id;

	$todo_sent = [ 'reminder_sent' => 1];

	if ( $wpdb->update( $table, $todo_sent, $where ) === false ) {
		return false;
	} else {
		return true;
	}
}

function eme_db_delete_todo( $todo_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$wpdb->delete( $table, [ 'todo_id' => $todo_id ], ['%d'] );
}

function eme_delete_event_todos( $event_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$sql   = $wpdb->prepare( "DELETE FROM $table WHERE event_id=%d", $event_id );
	$wpdb->query( $sql );
}

function eme_delete_event_old_todos( $event_id, $ids_arr ) {
	global $wpdb;
	if ( empty( $ids_arr ) || ! eme_is_numeric_array( $ids_arr ) ) {
		return;
	}
	$ids_list = implode(',', $ids_arr);
	$table    = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$sql = $wpdb->prepare( "DELETE FROM $table WHERE event_id=%d AND todo_id NOT IN ( $ids_list )", $event_id);
	$wpdb->query( $sql);
}

function eme_get_todo( $todo_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE todo_id=%d", $todo_id );
	return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_event_todos( $event_id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE event_id=%d ORDER BY todo_seq ASC", $event_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_meta_box_div_event_todos( $event ) {
	if ( isset( $event['is_duplicate'] ) ) {
		$todos = eme_get_event_todos( $event['orig_id'] );
	} elseif ( ! empty( $event['event_id'] ) ) {
		$todos = eme_get_event_todos( $event['event_id'] );
	} else {
		$todos = [];
	}
	?>
	<div id="div_todos">
		<table class="eme_todos">
		<thead>
			<tr>
				<th></th>
				<th></th>
				<th><strong><?php esc_html_e( 'Name', 'events-made-easy' ); ?></strong></th>
				<th><strong><?php esc_html_e( 'Event start offset (days)', 'events-made-easy' ); ?></strong></th>
				<th><strong><?php esc_html_e( 'Description', 'events-made-easy' ); ?></strong></th>
				<th></th>
			</tr>
		</thead>    
		<tbody id="eme_todos_tbody" class="eme_todos_tbody">
			<?php
			// if there are no entries in the array, make 1 empty entry in it, so it renders at least 1 row
			if ( ! is_array( $todos ) || count( $todos ) == 0 ) {
				$info     = eme_new_todo();
				$todos    = [ $info ];
				$required = '';
			} else {
				$required = "required='required'";
			}
			foreach ( $todos as $count => $todo ) {
				?>
				<tr id="eme_row_todo_<?php echo $count; ?>" >
				<td>
				<?php echo "<img class='eme-sortable-handle' src='" . esc_url(EME_PLUGIN_URL) . "images/reorder.png' alt='" . esc_attr__( 'Reorder', 'events-made-easy' ) . "'>"; ?>
				</td>
				<td>
				<?php if ( ! isset( $event['is_duplicate'] ) ) : // we set the todo ids only if it is not a duplicate event ?>
					<input type='hidden' id="eme_todos[<?php echo $count; ?>][todo_id]" name="eme_todos[<?php echo $count; ?>][todo_id]" aria-label="hidden index" size="5" value="<?php if ( isset( $todo['todo_id'] ) ) { echo $todo['todo_id'];} ?>">
					<input type='hidden' id="eme_todos[<?php echo $count; ?>][todo_nbr]" name="eme_todos[<?php echo $count; ?>][todo_nbr]" aria-label="hidden index" size="5" value="<?php if ( isset( $todo['todo_nbr'] ) ) { echo $todo['todo_nbr'];} ?>">
				<?php endif; ?>
				</td>
				<td>
				<input <?php echo $required; ?> id="eme_todos[<?php echo $count; ?>][name]" name="eme_todos[<?php echo $count; ?>][name]" size="12" aria-label="name" value="<?php echo $todo['name']; ?>">
				</td>
				<td>
				<input name='eme_todos[<?php echo $count; ?>][todo_offset]' id='eme_todos[<?php echo $count; ?>][todo_offset]' size="5" aria-label="event offset in days" value="<?php echo $todo['todo_offset']; ?>">
				</td>
				<td style="width: 60%;">
				<textarea class="eme_fullresizable" id="eme_todos[<?php echo $count; ?>][description]" name="eme_todos[<?php echo $count; ?>][description]" ><?php echo eme_esc_html( $todo['description'] ); ?></textarea>
				</td>
				<td>
				<a href="#" class='eme_remove_todo'><?php echo "<img class='eme_remove_todo' src='" . esc_url(EME_PLUGIN_URL) . "images/cross.png' alt='" . esc_attr__( 'Remove', 'events-made-easy' ) . "' title='" . esc_attr__( 'Remove', 'events-made-easy' ) . "'>"; ?></a><a href="#" class="eme_add_todo"><?php echo "<img class='eme_add_todo' src='" . esc_url(EME_PLUGIN_URL) . "images/plus_16.png' alt='" . esc_attr__( 'Add new todo', 'events-made-easy' ) . "' title='" . esc_attr__( 'Add new todo', 'events-made-easy' ) . "'>"; ?></a>
				</td>
				</tr>
				<?php
			}
			?>
		</tbody>
		</table>
	</div>
	<?php
}

function eme_get_past_unsent_todos() {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_TODOS_TBNAME;
	$events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$search_date  = $eme_date_obj_now->getDate();
	$sql   = $wpdb->prepare("SELECT $table.* FROM $table LEFT JOIN $events_table ON $table.event_id=$events_table.event_id WHERE reminder_sent=0 AND DATE_SUB($events_table.event_start,INTERVAL $table.todo_offset DAY) < %s", $search_date . ' 23:59:00');
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_email_todo($todo) {
	$event = eme_get_event( $todo['event_id'] );
	$contact = eme_get_event_contact( $event );
	[$from_name, $from_email] = eme_get_default_mailer_info();
	$contact_email  = $contact->user_email;
        $contact_name   = $contact->display_name;

	$contact_subject = __('Todo reminder for event #_EVENTNAME: ','events_made_easy').$todo['name'];
	$contact_body = $todo['description'];
	$contact_subject = eme_replace_event_placeholders($contact_subject, $event, 'text');
	$contact_body = eme_replace_event_placeholders($contact_body, $event);

	return eme_queue_mail( $contact_subject, $contact_body, $from_email, $from_name, $contact_email, $contact_name );
}

// for CRON
function eme_todos_send_reminders() {
	$todos = eme_get_past_unsent_todos();
	foreach ( $todos as $todo ) {
		$res = eme_email_todo( $todo );
		if ( $res )
			eme_mark_todo_sent( $todo['todo_id'] );
	}
}
