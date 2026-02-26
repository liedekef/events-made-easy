<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_category() {
	$category = [
		'category_name'   => '',
		'category_slug'   => '',
		'category_prefix' => '',
		'description'     => '',
	];
	return $category;
}

function eme_categories_page() {
	global $wpdb;

	if ( ! current_user_can( get_option( 'eme_cap_categories' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
		$message = __( 'You have no right to update categories!', 'events-made-easy' );
		eme_categories_table_layout( $message );
		return;
	}

	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_category' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		// edit category
		eme_categories_edit_layout();
		return;
	}

	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_category' ) {
		// add category
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_categories_edit_layout();
		return;
	}

	// Insert/Update/Delete Record
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	$message          = '';
	if ( isset( $_POST['eme_admin_action'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		if ( $_POST['eme_admin_action'] == 'do_editcategory' ) {
			// category update required
			$category                  = [];
			$category['category_name'] = eme_sanitize_request( $_POST['category_name'] );
			$category['description']   = eme_kses( $_POST['description'] );
			if ( ! empty( $_POST['category_prefix'] ) ) {
					$category['category_prefix'] = eme_sanitize_request( $_POST['category_prefix'] );
			}
			if ( ! empty( $_POST['category_slug'] ) ) {
					$category['category_slug'] = eme_permalink_convert_noslash( eme_sanitize_request( $_POST['category_slug'] ) );
			} else {
				$category['category_slug'] = eme_permalink_convert_noslash( $category['category_name'] );
			}
			if ( isset( $_POST['category_id'] ) && intval( $_POST['category_id'] ) > 0 ) {
				$validation_result = $wpdb->update( $categories_table, $category, [ 'category_id' => intval( $_POST['category_id'] ) ] );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully edited the category', 'events-made-easy' );
				} else {
					$message = __( 'There was a problem editing your category, please try again.', 'events-made-easy' );
				}
			} else {
				$validation_result = $wpdb->insert( $categories_table, $category );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully added the category', 'events-made-easy' );
				} else {
					$message = __( 'There was a problem adding your category, please try again.', 'events-made-easy' );
				}
			}
		}
	}
	eme_categories_table_layout( $message );
}

function eme_categories_table_layout( $message = '' ) {

?>
    <div class="wrap nosubsub">
    <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <?php if ( current_user_can( get_option( 'eme_cap_categories' ) ) ) : ?>
        <h1><?php esc_html_e( 'Add a new category', 'events-made-easy' ); ?></h1>
        <div class="wrap">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=eme-categories' ) ); ?>">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
            <input type="hidden" name="eme_admin_action" value="add_category">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add category', 'events-made-easy' ); ?>">
        </form>
        </div>
    <?php endif; ?>

    <h1><?php esc_html_e( 'Manage categories', 'events-made-easy' ); ?></h1>
    <?php if ( $message != '' ) { ?>
    <div id="message" class="updated notice notice-success is-dismissible">
         <p><?php echo wp_kses_post( $message ); ?></p>
    </div>
    <?php } ?>

    <div id="categories-message" class="eme-hidden" ></div>
    <div id="bulkactions">
    <form action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteCategories"><?php esc_html_e( 'Delete selected categories', 'events-made-easy' ); ?></option>
    </select>
    <button id="CategoriesActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="CategoriesTableContainer"></div>
    </div>
    </div>
<?php
}

function eme_categories_edit_layout() {
	global $plugin_page;

	if ( ! empty( $_GET['category_id'] ) ) {
		$category_id   = intval( $_GET['category_id'] );
		$category      = eme_get_category( $category_id );
		$h1_string     = __( 'Edit category', 'events-made-easy' );
		$action_string = __( 'Update category', 'events-made-easy' );
		$permalink_string  = __( 'Permalink: ', 'events-made-easy' );
		$action            = 'edit';
	} else {
		$category_id   = 0;
		$category      = eme_new_category();
		$h1_string     = __( 'Create category', 'events-made-easy' );
		$action_string = __( 'Add category', 'events-made-easy' );
		$permalink_string  = __( 'Permalink prefix: ', 'events-made-easy' );
		$action            = 'add';
	}

	?>
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
		</div>
		 
		<h1><?php echo esc_html( $h1_string ); ?></h1>   
	  
		<div id='ajax-response'></div>
		<form name='edit_category' id='edit_category' method='post' action='<?php echo esc_url( admin_url( "admin.php?page=$plugin_page" ) ); ?>'>
		<input type='hidden' name='eme_admin_action' value='do_editcategory'>
		<input type='hidden' name='category_id' value='<?php echo esc_attr( $category_id ); ?>'>
		<?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
		<table class='form-table'>
			<tr class='form-field'>
		        <th scope='row' style='vertical-align:top'><label for='category_name'><?php esc_html_e( 'Category name', 'events-made-easy' ); ?></label></th>
		        <td><input name='category_name' id='category_name' type='text' required='required' value='<?php echo esc_html( $category['category_name'] ); ?>' size='40'><br>
		<?php esc_html_e( 'The name of the category', 'events-made-easy' ); ?></td>
			</tr>
			<tr>
			    <th scope='row' style='vertical-align:top'><label for='slug'><?php echo esc_html( $permalink_string ); ?></label></th>
				<td>
				<?php
				echo trailingslashit( home_url() );
				$categories_prefixes = get_option( 'eme_permalink_categories_prefix', '' );
				if ( empty( $categories_prefixes ) ) {
					$extra_prefix        = 'cat/';
					$categories_prefixes = get_option( 'eme_permalink_events_prefix', 'events' );
				} else {
					$extra_prefix = '';
				}
				if ( preg_match( '/,/', $categories_prefixes ) ) {
					$categories_prefixes     = explode( ',', $categories_prefixes );
					$categories_prefixes_arr = [];
					foreach ( $categories_prefixes as $categories_prefix ) {
						$categories_prefixes_arr[ $categories_prefix ] = eme_permalink_convert( $categories_prefix );
					}
					$prefix = $category['category_prefix'] ? $category['category_prefix'] : '';
					echo eme_ui_select( $prefix, 'category_prefix', $categories_prefixes_arr ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select()
				} else {
					echo eme_permalink_convert( $categories_prefixes );
				}
				echo esc_html( $extra_prefix );
				if ( $action == 'edit' ) {
					$slug = $category['category_slug'] ? $category['category_slug'] : $category['category_name'];
					$slug = eme_permalink_convert_noslash( $slug );
					?>
					<input type="text" id="slug" name="category_slug" value="<?php echo esc_attr( $slug ); ?>"><?php echo user_trailingslashit( '' ); ?>
						<?php
				}
				?>
				</td>
			</tr>
			<tr class='form-field'>
                <th scope='row' style='vertical-align:top'><label for='description'><?php esc_html_e( 'Category description', 'events-made-easy' ); ?></label></th>
			    <td><div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<!-- we need description for qtranslate as ID -->
				<?php
				eme_wysiwyg_textarea( 'description', $category['description'], 1, 1 );
				?>
				<br><?php esc_html_e( 'The description of the category', 'events-made-easy' ); ?>
				</div>
			    </td>
			</tr>
		</table>
		<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo esc_attr( $action_string ); ?>'></p>
		</form>
	</div>
	<?php
}

function eme_get_cached_categories() {
	$cats = wp_cache_get( 'eme_all_cats' );
	if ( $cats === false ) {
		$cats = eme_get_categories();
		wp_cache_set( 'eme_all_cats', $cats, '', 60 );
	}
	return $cats;
}

function eme_get_categories( $eventful = false, $scope = 'future', $extra_conditions = '' ) {
	global $wpdb;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	$categories       = [];
	$order_by         = ' ORDER BY category_name ASC';
	if ( $eventful ) {
		$events = eme_get_events( scope: $scope, order: 'ASC' );
		if ( $events ) {
			foreach ( $events as $event ) {
				if ( ! empty( $event['event_category_ids'] ) ) {
					$event_cats = explode( ',', $event['event_category_ids'] );
					if ( ! empty( $event_cats ) ) {
						foreach ( $event_cats as $category_id ) {
							$categories[ $category_id ] = $category_id;
						}
					}
				}
			}
		}
		if ( ! empty( $categories ) && eme_is_numeric_array( $categories ) ) {
			$event_cats = join( ',', $categories );
			if ( $extra_conditions != '' ) {
				$extra_conditions = " AND ($extra_conditions)";
			}
			$result = $wpdb->get_results( "SELECT * FROM $categories_table WHERE category_id IN ( $event_cats ) $extra_conditions $order_by", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	} else {
		if ( $extra_conditions != '' ) {
			$extra_conditions = " WHERE ($extra_conditions)";
		}
		$result = $wpdb->get_results( "SELECT * FROM $categories_table $extra_conditions $order_by", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	if ( has_filter( 'eme_categories_filter' ) ) {
		$result = apply_filters( 'eme_categories_filter', $result );
	}
	return $result;
}

function eme_get_categories_filtered( $category_ids, $categories ) {
	$cat_id_arr = explode( ',', $category_ids );
	$new_arr    = [];
	foreach ( $categories as $cat ) {
		if ( in_array( $cat['category_id'], $cat_id_arr ) ) {
			$new_arr[] = $cat;
		}
	}
	return $new_arr;
}

function eme_get_category( $category_id ) {
	global $wpdb;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	$prepared_sql              = $wpdb->prepare( "SELECT * FROM $categories_table WHERE category_id = %d", $category_id );
	return $wpdb->get_row( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_event_category_names( $event_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$event_table      = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT category_name FROM $categories_table, $event_table where event_id = %d AND FIND_IN_SET(category_id,event_category_ids) $extra_conditions $order_by", $event_id );
	return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

function eme_get_event_category_descriptions( $event_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$event_table      = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT description FROM $categories_table, $event_table where event_id = %d AND FIND_IN_SET(category_id,event_category_ids) $extra_conditions $order_by", $event_id );
	return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

function eme_get_event_categories( $event_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$event_table      = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT $categories_table.* FROM $categories_table, $event_table where event_id = %d AND FIND_IN_SET(category_id,event_category_ids) $extra_conditions $order_by", $event_id );
	return $wpdb->get_results( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

function eme_get_category_eventids( $category_id, $future_only = 1 ) {
	
	// similar to eme_get_recurrence_eventids
	global $wpdb;
	$events_table    = EME_DB_PREFIX . EME_EVENTS_TBNAME;
	$extra_condition = '';
	if ( $future_only ) {
		$eme_date_obj    = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$today           = $eme_date_obj->getDateTime();
		$extra_condition = "AND event_start > '$today'";
	}
	$cat_ids   = explode( ',', $category_id );
	$event_ids = [];
	foreach ( $cat_ids as $cat_id ) {
		$prepared_sql = $wpdb->prepare( "SELECT event_id FROM $events_table WHERE FIND_IN_SET(%d,event_category_ids) $extra_condition ORDER BY event_start ASC, event_name ASC", $cat_id );
		if ( empty( $event_ids ) ) {
			$event_ids = $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$event_ids = array_unique( array_merge( $event_ids, $wpdb->get_col( $prepared_sql ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
	return $event_ids;
}

function eme_get_location_categories( $location_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$locations_table  = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT $categories_table.* FROM $categories_table, $locations_table where location_id = %d AND FIND_IN_SET(category_id,location_category_ids) $extra_conditions $order_by", $location_id );
	return $wpdb->get_results( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_location_category_names( $location_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$locations_table  = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT $categories_table.category_name FROM $categories_table, $locations_table WHERE location_id = %d AND FIND_IN_SET(category_id,location_category_ids) $extra_conditions $order_by", $location_id );
	return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_location_category_descriptions( $location_id, $extra_conditions = '', $order_by = '' ) {
	global $wpdb;
	$locations_table  = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	if ( $extra_conditions != '' ) {
		$extra_conditions = " AND ($extra_conditions)";
	}
	if ( $order_by != '' ) {
		$order_by = " ORDER BY $order_by";
	}
	$prepared_sql = $wpdb->prepare( "SELECT $categories_table.description FROM $categories_table, $locations_table WHERE location_id = %d AND FIND_IN_SET(category_id,location_category_ids) $extra_conditions $order_by", $location_id );
	return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_category_ids( $cat_slug = '' ) {
	global $wpdb;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	$cat_ids          = [];
	if ( ! empty( $cat_slug ) ) {
		$prepared_sql = $wpdb->prepare( "SELECT DISTINCT category_id FROM $categories_table WHERE category_slug = %s", $cat_slug );
	} else {
		$prepared_sql = "SELECT category_id FROM $categories_table ORDER BY category_id";
	}
	$cat_ids = $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $cat_ids;
}

function eme_get_category_id_by_name_slug ($cat_name ) {
	global $wpdb;
	$categories_table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
	$cat_name = eme_sanitize_request($cat_name);
	$prepared_sql = $wpdb->prepare( "SELECT category_id FROM $categories_table WHERE category_name = %s OR category_slug = %s LIMIT 1", $cat_name, $cat_name );
	return $wpdb->get_var( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_get_categories_shortcode( $atts ) {
	eme_enqueue_frontend();

    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

	$atts = shortcode_atts(
		[
			'event_id'           => 0,
			'eventful'           => false,
			'scope'              => 'all',
			'template_id'        => 0,
			'template_id_header' => 0,
			'template_id_footer' => 0,
		],
		$atts
	);

	$event_id = $atts['event_id'];
	$eventful = filter_var( $atts['eventful'], FILTER_VALIDATE_BOOLEAN );
	$scope = $atts['scope'];
	$template_id = $atts['template_id'];
	$template_id_header = $atts['template_id_header'];
	$template_id_footer = $atts['template_id_footer'];

	if ( $event_id ) {
		$categories = eme_get_event_categories( $event_id );
	} else {
		$categories = eme_get_categories( $eventful, $scope );
	}

	// Initialize format and templates for header/footer
	$format = '';
	$eme_format_header = '';
	$eme_format_footer = '';

	if ( $template_id ) {
		$format = eme_get_template_format( $template_id );
	}
	if ( $template_id_header ) {
		$format_header = eme_get_template_format( $template_id_header );
		$eme_format_header = eme_replace_categories_placeholders( $format_header );
	}
	if ( $template_id_footer ) {
		$format_footer = eme_get_template_format( $template_id_footer );
		$eme_format_footer = eme_replace_categories_placeholders( $format_footer );
	}

	// Set default format if not defined
	if (eme_is_empty_string( $format )) {
		$format = '<li class="cat-#_CATEGORYFIELD{category_id}">#_CATEGORYFIELD{category_name}</li>';
	}
	if (eme_is_empty_string( $eme_format_header )) {
		$eme_format_header = '<ul>';
	}
	if (eme_is_empty_string( $eme_format_footer )) {
		$eme_format_footer = '</ul>';
	}

	// Build output using categories
	$output = '';
	foreach ($categories as $cat) {
		$output .= eme_replace_categories_placeholders( $format, $cat );
	}
	$output = $eme_format_header . $output . $eme_format_footer;

	return $output;
}

function eme_replace_categories_placeholders( $format, $cat = '', $target = 'html', $do_shortcode = 1, $lang = '' ) {
	if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
		$target = 'html';
	}

	$needle_offset = 0;
	preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$need_escape        = 0;
		$need_urlencode     = 0;
		$found              = 1;
		if ( strstr( $result, '#ESC' ) ) {
			$result      = str_replace( '#ESC', '#', $result );
			$need_escape = 1;
		} elseif ( strstr( $result, '#URL' ) ) {
			$result         = str_replace( '#URL', '#', $result );
			$need_urlencode = 1;
		}
		$replacement = '';

		if ( preg_match( '/#_CATEGORYFIELD\{(.+)\}/', $result, $matches ) ) {
			$tmp_attkey = $matches[1];
			if ( isset( $cat[ $tmp_attkey ] ) && ! is_array( $cat[ $tmp_attkey ] ) ) {
				$replacement = $cat[ $tmp_attkey ];
			}
		} elseif ( preg_match( '/#_CATEGORYURL/', $result ) ) {
			$replacement = eme_category_url( $cat );
		} else {
			$found = 0;
		}

		if ( $found ) {
			if ( $target == 'html' ) {
				$replacement = esc_html( eme_translate( $replacement, $lang ) );
				$replacement = apply_filters( 'eme_general', $replacement );
			} elseif ( $target == 'rss' ) {
				$replacement = eme_translate( $replacement, $lang );
				$replacement = apply_filters( 'the_content_rss', $replacement );
			} else {
				$replacement = eme_translate( $replacement, $lang );
				$replacement = apply_filters( 'eme_text', $replacement );
			}
			if ( $need_escape ) {
				$replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
			}
			if ( $need_urlencode ) {
				$replacement = rawurlencode( $replacement );
			}
			$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	// now, replace any language tags found
	$format = eme_translate( $format, $lang );

	// and now replace any shortcodes, if wanted
	if ( $do_shortcode ) {
		return do_shortcode( $format );
	} else {
		return $format;
	}
}

add_action( 'wp_ajax_eme_categories_list', 'eme_ajax_action_categories_list' );
add_action( 'wp_ajax_eme_manage_categories', 'eme_ajax_action_manage_categories' );

function eme_ajax_action_categories_list() {
    global $wpdb;

    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $fTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_categories' ) )) {
        $fTableResult['Result']  = 'Error';
        $fTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $fTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();

    $count_sql  = "SELECT COUNT(*) FROM $table";
    $sql  = "SELECT * FROM $table $orderby $limit";
    $recordCount = $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is a safe variable
    $rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name and conditions are safe variables

    $records = [];
    foreach ( $rows as $row ) {
        $record  = [];
        if ( empty( $row['category_name'] ) ) {
            $row['category_name'] = __( 'No name', 'events-made-easy' );
        }
        $record['category_id'] = "<a href='" . esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-categories&eme_admin_action=edit_category&category_id=' . $row['category_id'] ), 'eme_admin', 'eme_admin_nonce' ) ) . "'>" . $row['category_id'] . '</a>';
        $record['category_name'] = "<a href='" . esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-categories&eme_admin_action=edit_category&category_id=' . $row['category_id'] ), 'eme_admin', 'eme_admin_nonce' ) ) . "'>" . esc_html( eme_translate( $row['category_name'] ) ) . '</a>';
        $records[] = $record;
    }
    $fTableResult['Result']           = 'OK';
    $fTableResult['Records']          = $records;
    $fTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $fTableResult );
    wp_die();
}

function eme_ajax_action_manage_categories() {
    global $wpdb;
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $fTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_categories' ) )) {
        $fTableResult['Result']  = 'Error';
        $fTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $fTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_CATEGORIES_TBNAME;
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
        case 'deleteCategories':
            $category_ids_list = eme_sanitize_request($_POST['category_ids']);
            if (eme_is_list_of_int($category_ids_list)) {
                $ids_arr = array_map('intval', explode(',', $category_ids_list));
                $placeholders = implode(',', array_fill(0, count($ids_arr), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE category_id IN ($placeholders)", ...$ids_arr)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }
            $fTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Categories deleted','events-made-easy')."</div>";
            $fTableResult['Result'] = 'OK';
            break;
        }
    }
    print wp_json_encode( $fTableResult );
    wp_die();
}
