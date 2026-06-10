<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_formfield() {
    $formfield = [
        'field_type'       => 'text',
        'field_name'       => '',
        'field_values'     => '',
        'field_tags'       => '',
        'admin_values'     => '',
        'admin_tags'       => '',
        'field_attributes' => '',
        'admin_attributes' => '',
        'field_purpose'    => '',
        'field_condition'  => '',
        'field_required'   => 0,
        'export'           => 0,
        'extra_charge'     => 0,
        'searchable'       => 0,
    ];
    return $formfield;
}

function eme_formfields_page() {
    global $wpdb;

    if ( ! current_user_can( get_option( 'eme_cap_forms' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
        $message = __( 'You have no right to update form fields!', 'events-made-easy' );
        eme_formfields_table_layout( $message );
        return;
    }

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_formfield' ) {
        // edit formfield
        $field_id = intval( $_GET['field_id'] );
        eme_formfields_edit_layout( $field_id );
        return;
    }

    if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_formfield' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_formfields_edit_layout();
        return;
    }

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'copy_formfield' ) {
        $field_id = intval( $_GET['field_id'] );
        $formfield = eme_get_formfield( $field_id );
        if ( empty( $formfield ) ) {
            eme_formfields_edit_layout();
            return;
        }
        unset( $formfield['field_id'] );
        $formfield['field_name'] .= __( ' (Copy)', 'events-made-easy' );
        eme_formfields_edit_layout( 0, '', $formfield );
        return;
    }

    // Insert/Update/Delete Record
    $formfields_table  = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $validation_result = '';
    $message           = '';
    if ( isset( $_POST['eme_admin_action'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( $_POST['eme_admin_action'] == 'do_editformfield' ) {
            $formfield                   = [];
            $field_id                    = intval( $_POST['field_id'] );
            $formfield['field_name']     = trim( eme_sanitize_request( $_POST['field_name'] ) );
            $formfield['field_type']     = trim( esc_html( eme_sanitize_request( $_POST['field_type'] ) ) );
            $formfield['extra_charge']   = intval( $_POST['extra_charge'] );
            $formfield['searchable']     = intval( $_POST['searchable'] );
            $formfield['field_required'] = intval( $_POST['field_required'] );
            if ( eme_is_multifield( $formfield['field_type'] ) ) {
                if (eme_is_empty_string($_POST['field_values'] )) {
                    $field_values_arr = [];
                } else {
                    $field_values_arr = eme_sanitize_request( eme_convert_multi2array(eme_convert_array2multi(eme_text_split_newlines( $_POST['field_values'] ) )));
                }
                if (eme_is_empty_string($_POST['field_tags'] )) {
                    $field_tags_arr = [];
                } else {
                    $field_tags_arr = eme_kses( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['field_tags'] ) )));
                }
                if (eme_is_empty_string($_POST['admin_values'] )) {
                    $admin_values_arr = [];
                } else {
                    $admin_values_arr = eme_sanitize_request( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['admin_values'] ) )));
                }
                if (eme_is_empty_string($_POST['admin_tags'] )) {
                    $admin_tags_arr = [];
                } else {
                    $admin_tags_arr = eme_kses( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['admin_tags'] ) )));
                }

                // some sanity checks
                if (empty($field_values_arr)) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: the field value can not be empty for this type of field.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (eme_array_has_dupes($field_values_arr) || eme_array_has_dupes($admin_values_arr)) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: the field values need to be unique for this type of field.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (! empty( $field_tags_arr ) && count( $field_values_arr ) != count( $field_tags_arr ) ) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: if you specify field tags, there need to be exact the same amount of tags as values.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (! empty( $admin_tags_arr ) && count( $admin_values_arr ) != count( $admin_tags_arr ) ) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: if you specify field tags, there need to be exact the same amount of tags as values.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }

                $formfield['field_values'] = eme_convert_array2multi( $field_values_arr );
                $formfield['field_tags'] = eme_convert_array2multi( $field_tags_arr );
                $formfield['admin_values'] = eme_convert_array2multi( $admin_values_arr );
                $formfield['admin_tags'] = eme_convert_array2multi( $admin_tags_arr );
            } else {
                $formfield['field_values'] = trim( eme_sanitize_request( $_POST['field_values'] ) );
                $formfield['field_tags']   = trim( eme_sanitize_request( $_POST['field_tags'] ) );
                $formfield['admin_values'] = trim( eme_sanitize_request( $_POST['admin_values'] ) );
                $formfield['admin_tags']   = trim( eme_sanitize_request( $_POST['admin_tags'] ) );
            }
            $formfield['field_attributes'] = trim( eme_sanitize_request( $_POST['field_attributes'] ) );
            $formfield['admin_attributes'] = trim( eme_sanitize_request( $_POST['admin_attributes'] ) );
            // for updates the field_purpose can be empty, so check for this
            if ( ! empty( $_POST['field_purpose'] ) ) {
                $formfield['field_purpose'] = trim( eme_sanitize_request( $_POST['field_purpose'] ) );
            }
            // condition can be null if there was a group assigned and the group got deleted, so let's check for that too
            // we also remove group:0 from the array in case other groups are choosen too
            if ( ! empty( $_POST['field_condition'] ) && is_array( $_POST['field_condition'] ) ) {
                $condition_arr = eme_sanitize_request( $_POST['field_condition'] );
                //Remove element by value using unset()
                $key = array_search( 'group:0', $condition_arr );
                if ( $key !== false ) {
                    unset( $condition_arr[ $key ] );
                }
                $formfield['field_condition'] = join( ',', eme_sanitize_request( $condition_arr ) );
            }
            if ( empty( $formfield['field_condition'] ) ) {
                $formfield['field_condition'] = 'group:0';
            }
            if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' || $formfield['field_purpose'] != 'people' ) {
                $formfield['export'] = 0;
            } elseif ( isset( $_POST['export'] ) ) {
                $formfield['export'] = intval( $_POST['export'] );
            } else {
                $formfield['export'] = 0;
            }
            if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                // files are not stored in the db, so we can't search on them
                $formfield['searchable'] = 0;
                // for type file, we only accept integers here
                // since we use that as max size
                if ( ! empty( $formfield['admin_values'] ) ) {
                    $formfield['admin_values'] = intval( $formfield['admin_values'] );
                }
                if ( ! empty( $formfield['field_values'] ) ) {
                    $formfield['field_values'] = intval( $formfield['field_values'] );
                }
            }
            if ( $field_id > 0 ) {
                $validation_result = $wpdb->update( $formfields_table, $formfield, [ 'field_id' => $field_id ] );
                if ( $validation_result !== false ) {
                    $message = __( 'Successfully edited the field', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem editing the field', 'events-made-easy' );
                }
                if ( get_option( 'eme_stay_on_edit_page' ) || $validation_result === false ) {
                    eme_formfields_edit_layout( $field_id, $message );
                    return;
                }
            } else {
                $validation_result = $wpdb->insert( $formfields_table, $formfield );
                if ( $validation_result !== false ) {
                    $new_field_id = $wpdb->insert_id;
                    $message      = __( 'Successfully added the field', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem adding the field', 'events-made-easy' );
                }
                if ( get_option( 'eme_stay_on_edit_page' ) || $validation_result === false ) {
                    eme_formfields_edit_layout( $new_field_id, $message );
                    return;
                }
            }
        }
    }

    eme_formfields_table_layout( $message );
}

function eme_formfields_table_layout( $message = '' ) {
    global $plugin_page;
    $field_types    = eme_get_fieldtypes();
    $field_purposes = eme_get_fieldpurpose();
    $destination    = esc_url( admin_url( "admin.php?page=$plugin_page" ) );
    if ( empty( $message ) ) {
        $hidden_class = 'eme-hidden';
    } else {
        $hidden_class = '';
    }
?>
    <div class="wrap nosubsub">
    <div id="poststuff">

    <div id="formfields-message" class="notice is-dismissible eme-message-admin <?php echo esc_attr( $hidden_class ); ?>">
        <p><?php echo wp_kses_post( $message ); ?></p>
    </div>

    <h1><?php esc_html_e( 'Add custom field', 'events-made-easy' ); ?></h1>

    <div class="wrap">
        <form id="formfields-new" method="post" action="<?php echo esc_url( $destination ); ?>">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="hidden" name="eme_admin_action" value="add_formfield">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add custom field', 'events-made-easy' ); ?>">
        </form>
    </div>
    <h1><?php esc_html_e( 'Manage custom fields', 'events-made-easy' ); ?></h1>
    <form action="#" method="post">
    <?php echo eme_ui_select( '', 'search_type', $field_types, __( 'Any', 'events-made-easy' ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?>
    <?php echo eme_ui_select( '', 'search_purpose', $field_purposes, __( 'Any', 'events-made-easy' ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?>
    <input type="search" name="search_name" id="search_name" placeholder="<?php esc_attr_e( 'Field name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=10>
    <button id="FormfieldsLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter fields', 'events-made-easy' ); ?></button>
    </form>

    <div class="bulkactions">
    <form id='formfields-form' action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteFormfields"><?php esc_html_e( 'Delete selected fields', 'events-made-easy' ); ?></option>
    </select>
    <button id="FormfieldsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="FormfieldsTableContainer"></div>
    </div>
    </div>
<?php
}

function eme_formfields_edit_layout( $field_id = 0, $message = '', $t_formfield = [] ) {
    global $plugin_page;

    $field_types                      = eme_get_fieldtypes();
    $field_purposes                   = eme_get_fieldpurpose();
    $groups                           = eme_get_static_groups();
    $peoplefieldconditions            = [];
 
    //$peoplefieldconditions['group:0'] = __( 'Show for all people', 'events-made-easy' );
    foreach ( $groups as $group ) {
        $peoplefieldconditions[ 'group:' . $group['group_id'] ] = $group['name'];
    }

    $nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    if ( $field_id > 0 ) {
        $used          = eme_check_used_formfield( $field_id );
        $formfield     = eme_get_formfield( $field_id );
        $h1_string     = __( 'Edit field', 'events-made-easy' );
        $action_string = __( 'Update field', 'events-made-easy' );
    } else {
        $used          = 0;
        $formfield     = eme_new_formfield();
        $h1_string     = __( 'Create field', 'events-made-easy' );
        $action_string = __( 'Add field', 'events-made-easy' );
    }
    if ( ! empty( $t_formfield ) ) {
        $formfield = array_merge( $formfield, $t_formfield );
    }
    $layout = "
   <div class='wrap'>
      <h1>" . $h1_string . '</h1>';

    if ( $message != '' ) {
        $layout .= "
      <div id='message' class='updated notice notice-success is-dismissible'>
         <p>$message</p>
      </div>";
    }

    if ( $used ) {
        $layout .= "
      <div id='eme_formfield_warning' class='notice below-h1 eme-message-admin'>
         <p>" . __( 'Warning: this field is already used in RSVP replies, member signups, event or location definitions. Changing the field type or values might result in unwanted side effects.', 'events-made-easy' ) . '</p>
      </div>';
    }

    $layout .= "
      <div id='ajax-response'></div>

      <form name='edit_formfield' id='edit_formfield' method='post' action='" . esc_url( admin_url( "admin.php?page=$plugin_page" ) ) . "' class='validate'>
      <input type='hidden' name='eme_admin_action' value='do_editformfield'>
      $nonce_field
      <input type='hidden' name='field_id' value='" . $field_id . "'>

      <table class='form-table'>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_name'>" . __( 'Field name', 'events-made-easy' ) . "</label></th>
               <td><input name='field_name' id='field_name' type='text' value='" . esc_html( $formfield['field_name'] ) . "' size='40' required='required'></td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_type'>" . __( 'Field type', 'events-made-easy' ) . '</label></th>
               <td>' . eme_ui_select( $formfield['field_type'], 'field_type', $field_types ) . '
                    <br>' . __( "For the types 'Date (JS)','Datetime (JS)' and 'Time (JS)' you can optionally enter a custom date format in 'HTML Field attributes' to be used when the field is shown.", 'events-made-easy' ) . '
                    <br>' . __( "For the types 'Dropdown' and 'Dropdown (multiple)' you can optionally enter a placeholder in 'HTML Field attributes' to be used when the field is shown. Be sure to add an empty first line (value & tag) then, otherwise the placeholder might not show.", 'events-made-easy' ) . '
                    <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB in 'Field values'.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_purpose'>" . __( 'Field purpose', 'events-made-easy' ) . '</label></th>
               ';
    if ( ! $used || in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'members' ] ) ) {
        if ( $used && in_array( $formfield['field_purpose'], [ 'rsvp', 'members' ] ) ) {
            // for members or rsvp field: allow to change between those and generic
            unset( $field_purposes['events'] );
            unset( $field_purposes['locations'] );
            unset( $field_purposes['memberships'] );
            unset( $field_purposes['people'] );
        }
        $layout .= '
           <td>' . eme_ui_select( $formfield['field_purpose'], 'field_purpose', $field_purposes ) . '
                    <br>' . __( "If you select 'RSVP field', 'People field' or 'Members field', this field will show up as an extra column in the overview table for bookings, people or members. Selecting 'Generic' will cause it to show up in the overview table for bookings or members.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'People field' you can add a condition to this field, meaning that if the person is in the group you selected in the condition, this is an extra field that will then be available to fill out for that person. This allows you to put people in e.g. a Volunteer group and then ask for more volunteer info.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'People field' and use this field in a RSVP or membership form, the info will be stored to the person, so you can ask for extra personal info when someone signs up. When editing the person, those fields will then be visible.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'Events field', 'Locations field' or 'Memberships field', this field will be used in the definition of the event, location or membership. Warning: this is unrelated to the use of custom fields in RSVP forms, so if you don't intend to use this field in the definition of events, locations or memberships, don't select this.", 'events-made-easy' ) . '
               </td>';
    } else {
        $layout .= '<td>' . eme_get_fieldpurpose( $formfield['field_purpose'] );
        $layout .= "<input type='hidden' name='field_purpose' id='field_purpose' value='" . $formfield['field_purpose'] . "'></td>";
    }

    $field_condition_arr = explode( ',', $formfield['field_condition'] );
    $layout             .= "
            </tr>
            <tr id='tr_export' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='export'>" . __( 'Include in CSV export', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['export'], 'export' ) . '
                   <br>' . __( 'Include this field in the CSV export for bookings.', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_condition' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_condition'>" . __( 'Field condition', 'events-made-easy' ) . '</label></th>
               <td>' . eme_ui_multiselect( $field_condition_arr, 'field_condition', $peoplefieldconditions, 5, '', 0, ' eme_snapselect' ) . '
                   <br>' . __( 'Only show this field if the person is member of the selected group. Leave empty to add this field to all people.', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_required'>" . __( 'Required field', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['field_required'], 'field_required' ) . '
                  <br>' . __( 'Use this if the field is required to be filled out.', 'events-made-easy' ) . '
                  <br>' . __( 'This overrides the use of "#REQ" when defining a field in a form.', 'events-made-easy' ) . "
            </tr>
            <tr id='tr_searchable' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='searchable'>" . __( 'Searchable or sortable', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['searchable'], 'searchable' ) . '
                  <br>' . __( 'When defining a custom field, it is also used in the administration interface for events, locations, people, members (depending on its purpose).', 'events-made-easy' ) . '
                  <br>' . __( 'However, being able to search or sort on such a field is more heavy on the database, that is why by default this parameter is set to "No".', 'events-made-easy' ) . '
                  <br>' . __( 'If you want to search or sort on such a field, set this parameter to "Yes".', 'events-made-easy' ) . "
            </tr>
            <tr id='tr_extra_charge' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='extra_charge'>" . __( 'Extra charge', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['extra_charge'], 'extra_charge' ) . '
                  <br>' . __( 'Use this if the field indicates an extra charge to the total price (can be negative to indicate a discount), in which case you should also set the field value to the charge.', 'events-made-easy' ) . '
                  <br>' . __( 'For multivalue fields (like e.g. dropdown) the field values should indicate the price for that selection (and the price needs to be unique).', 'events-made-easy' ) . '
                  <br>' . __( "This is ignored for fields with purpose 'Events field', 'Locations field' or 'Memberships field'", 'events-made-easy' ) . "
            </tr>
            <tr id='tr_field_values' class='form-field'>
           <th scope='row' style='vertical-align:top'><label for='field_values'>" . __( 'Field values', 'events-made-easy' ) . '</label></th>';

    $layout .= "<td><div id='field_values_container'><input name='field_values' id='field_values' type='text' value='" . esc_html( $formfield['field_values'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'Enter here the default value a field should have, or enter the list of values for fields that support multiple values.', 'events-made-easy' ) . '
                  <br>' . __( 'For fields that support multiple values (like Dropdown or Checkbox), enter one value per line. To include an empty first option (e.g., for a blank default in a dropdown), start with an empty line at the top.', 'events-made-easy' ) . '
                  <br>' . __( "For the types 'Date (Javascript)', 'Datetime (Javascript)' and 'Time (Javascript)' you can optionally enter the word 'NOW' to automatically use the current date and/or time when the field is displayed.", 'events-made-easy' ) . '
                  <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_tags' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_tags'>" . __( 'Field tags', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='field_tags_container'><input name='field_tags' id='field_tags' type='text' value='" . esc_html( $formfield['field_tags'] ) . "' size='40'></div>";
    $layout .= '
          <br>' . __( 'This option determines the "visible" value people will see for the field.', 'events-made-easy' ) . '
          <br>' . __( 'For multivalue fields, you can here enter the "visible" tag people will see per value (so, if "Field values" contain e.g. "a1||a2||a3", you can use here e.g. "Text a1||Text a2||Text a3").', 'events-made-easy' ) . '
                  <br>' . __( 'If left empty, the field values will be used (so the visible tag equals the value).', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_admin_values' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='admin_values'>" . __( 'Admin Field values', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='admin_values_container'><input name='admin_values' id='admin_values' type='text' value='" . esc_html( $formfield['admin_values'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'If you want a bigger number of choices for e.g. dropdown fields in the admin interface, enter the possible values here', 'events-made-easy' ) . '
                  <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_admin_tags' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='admin_tags'>" . __( 'Admin Field tags', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='admin_tags_container'><input name='admin_tags' id='admin_tags' type='text' value='" . esc_html( $formfield['admin_tags'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'If you want a bigger number of choices for e.g. dropdown fields in the admin interface, enter the possible tags here', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_attributes' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_attributes'>" . __( 'HTML field attributes', 'events-made-easy' ) . "</label></th>
               <td><input name='field_attributes' id='field_attributes' type='text' value='" . esc_html( $formfield['field_attributes'] ) . "' size='40'>
                   <br>" . __( 'Here you can specify extra html attributes for your field (like size, maxlength, pattern, ...).', 'events-made-easy' ) . '
                   <br>' . __( "For the types 'Date (Javascript)', 'Datetime (Javascript)' and 'Time (Javascript)' enter a valid PHP-format of the date you like to see when entering/showing the value (unrecognized characters in the format will cause the result to be empty). If left empty, the WordPress settings for date format will be used.", 'events-made-easy' ) . "
                    <br>" . __( "For the types 'Dropdown' and 'Dropdown (multiple)' you can optionally enter a placeholder by using data-placeholder: data-placeholder='my placeholder value'. Be sure to add an empty first line (value & tag) then, otherwise the placeholder might not show.", 'events-made-easy' ) . __('EME uses a custom snapselect version for dropdowns, see https://github.com/liedekef/snapselect for all data-related possibilities.','events-made-easy') . "
               </td>
            </tr>
            <tr id='tr_admin_attributes' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='admin_attributes'>" . __( 'Admin HTML field attributes', 'events-made-easy' ) . "</label></th>
               <td><input name='admin_attributes' id='admin_attributes' type='text' value='" . esc_html( $formfield['admin_attributes'] ) . "' size='40'>
                   <br>" . __( 'If you want different HTML attributes in the admin interface, enter these here.', 'events-made-easy' ) . "
               </td>
            </tr>
      </table>
      <p class='submit'><input type='submit' class='button-primary' name='submit' value='" . $action_string . "'></p>
      </form>

   </div>
   <p>" . esc_html__( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' rel='noopener noreferrer' href='https://www.e-dynamics.be/wordpress/eme-docs/custom-attributes/'>" . esc_html__( 'the documentation', 'events-made-easy' ) . '</a></p>
   ';
    echo $layout; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from hardcoded strings and translations
}

function eme_get_dyndata_conditions() {
        $data = [
            'eq'          => __( 'equal to', 'events-made-easy' ),
            'ne'          => __( 'not equal to', 'events-made-easy' ),
            'lt'          => __( 'lower than', 'events-made-easy' ),
            'gt'          => __( 'greater than', 'events-made-easy' ),
            'ge'          => __( 'greater than or equal to', 'events-made-easy' ),
            'contains'    => __( 'contains', 'events-made-easy' ),
            'notcontains' => __( 'does not contain', 'events-made-easy' ),
            'incsv'       => __( 'CSV list contains', 'events-made-easy' ),
            'notincsv'    => __( 'CSV list does not contain', 'events-made-easy' ),
        ];

        return $data;
}

function eme_get_used_formfield_ids() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    return $wpdb->get_col( "SELECT DISTINCT field_id FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a safe variable
}

function eme_check_used_formfield( $field_id ) {
    global $wpdb;
    $table  = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $prepared_query  = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE field_id=%d", $field_id );
    $count  = $wpdb->get_var( $prepared_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return $count;
}

function eme_get_formfields( $ids = '', $purpose = '' ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $where            = '';
    $where_arr        = [];
    if ( ! empty( $ids ) && eme_is_list_of_int( $ids ) ) {
        $ids_arr      = array_map( 'intval', explode( ',', $ids ) );
        $placeholders = implode( ',', array_fill( 0, count( $ids_arr ), '%d' ) );
        $where_arr[]  = $wpdb->prepare( "field_id IN ($placeholders)", ...$ids_arr ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
    if ( ! empty( $purpose ) ) {
        $purposes     = explode( ',', $purpose );
        $purposes_arr = [];
        foreach ( $purposes as $tmp_p ) {
            $purposes_arr[] = $wpdb->prepare("field_purpose = %s", $tmp_p );
        }
        $where_arr[] = '(' . join( ' OR ', $purposes_arr ) . ')';
    }
    if ( ! empty( $where_arr ) ) {
        $where = 'WHERE ' . join( ' AND ', $where_arr );
    }
    return $wpdb->get_results( "SELECT * FROM $formfields_table $where", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and conditions are safe variables
}

function eme_get_searchable_formfields( $purpose = '', $include_generic = 0 ) {
    $cache_key = 'eme_searchable_formfields ' . $purpose . '_' . intval( $include_generic );
    $cached    = wp_cache_get( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $where            = '';
    $where_arr        = [];
    $where_arr[]      = 'searchable=1';
    $where_arr[]      = "field_type <> 'file' AND field_type <> 'multifile'";
    if ( ! empty( $purpose ) ) {
        if ( $include_generic ) {
            $where_arr[] = $wpdb->prepare( "(field_purpose = %s OR field_purpose='generic')", $purpose );
        } else {
            $where_arr[] = $wpdb->prepare( "field_purpose = %s", $purpose );
        }
    }
    if ( ! empty( $where_arr ) ) {
        $where = 'WHERE ' . join( ' AND ', $where_arr );
    }
    $result = $wpdb->get_results( "SELECT * FROM $formfields_table $where", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and conditions are safe variables
    wp_cache_set( $cache_key, $result, '', 60 );
    return $result;
}

function eme_get_formfield( $field_info ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    if ( is_numeric( $field_info ) || $field_info == 'performer' ) {
        $formfield = wp_cache_get( "eme_formfield $field_info" );
    } else {
        $formfield = false;
    }
    if ( $formfield === false ) {
        if ( is_numeric( $field_info ) ) {
            $prepared_sql = $wpdb->prepare( "SELECT * FROM $formfields_table WHERE field_id=%d", $field_info ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            $prepared_sql = $wpdb->prepare( "SELECT * FROM $formfields_table WHERE field_name=%s LIMIT 1", $field_info ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        $formfield = $wpdb->get_row( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ( is_numeric( $field_info ) || $field_info == 'performer' ) {
            wp_cache_set( "eme_formfield $field_info", $formfield, '', 60 );
        }
    }
    return $formfield;
}

function eme_delete_formfields( $ids_arr ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    if ( ! empty( $ids_arr ) && eme_is_numeric_array( $ids_arr ) ) {
        $ids_arr_int  = array_map( 'intval', $ids_arr );
        $placeholders = implode( ',', array_fill( 0, count( $ids_arr_int ), '%d' ) );
        $validation_result = $wpdb->query( $wpdb->prepare( "DELETE FROM $formfields_table WHERE field_id IN ($placeholders)", ...$ids_arr_int ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $validation_result !== false ) {
            $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
            $wpdb->query( $wpdb->prepare( "DELETE FROM $answers_table WHERE field_id IN ($placeholders)", ...$ids_arr_int ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function eme_get_fieldpurpose( $purpose = '' ) {
    $uses = [
        'generic'     => __( 'Generic', 'events-made-easy' ),
        'events'      => __( 'Events field', 'events-made-easy' ),
        'locations'   => __( 'Locations field', 'events-made-easy' ),
        'rsvp'        => __( 'RSVP field', 'events-made-easy' ),
        'people'      => __( 'People field', 'events-made-easy' ),
        'members'     => __( 'Members field', 'events-made-easy' ),
        'memberships' => __( 'Memberships field', 'events-made-easy' ),
    ];
    if ( $purpose ) {
        if ( isset( $uses[ $purpose ] ) ) {
            return $uses[ $purpose ];
        } else {
            return $uses['generic'];
        }
    } else {
        return $uses;
    }
}

function eme_get_fieldtypes() {
    $types = [
        'text'              => __( 'Text', 'events-made-easy' ),
        'textarea'          => __( 'Textarea', 'events-made-easy' ),
        'dropdown'          => __( 'Dropdown', 'events-made-easy' ),
        'dropdown_multi'    => __( 'Dropdown (multiple)', 'events-made-easy' ),
        'radiobox'          => __( 'Radiobox', 'events-made-easy' ),
        'radiobox_vertical' => __( 'Radiobox (vertical)', 'events-made-easy' ),
        'checkbox'          => __( 'Checkbox', 'events-made-easy' ),
        'checkbox_vertical' => __( 'Checkbox (vertical)', 'events-made-easy' ),
        'password'          => __( 'Password', 'events-made-easy' ),
        'hidden'            => __( 'Hidden', 'events-made-easy' ),
        'readonly'          => __( 'Readonly', 'events-made-easy' ),
        'file'              => __( 'File upload', 'events-made-easy' ),
        'multifile'         => __( 'Multiple files upload', 'events-made-easy' ),
        'date'              => __( 'Date', 'events-made-easy' ),
        'date_js'           => __( 'Date (Javascript)', 'events-made-easy' ),
        'datetime_js'       => __( 'Datetime (Javascript)', 'events-made-easy' ),
        'datetime-local'    => __( 'Datetime-local', 'events-made-easy' ),
        'month'             => __( 'Month', 'events-made-easy' ),
        'week'              => __( 'Week', 'events-made-easy' ),
        'time'              => __( 'Time', 'events-made-easy' ),
        'time_js'           => __( 'Time (Javascript)', 'events-made-easy' ),
        'color'             => __( 'Color', 'events-made-easy' ),
        'email'             => __( 'Email', 'events-made-easy' ),
        'number'            => __( 'Number', 'events-made-easy' ),
        'range'             => __( 'Range', 'events-made-easy' ),
        'tel'               => __( 'Tel', 'events-made-easy' ),
        'url'               => __( 'Url', 'events-made-easy' ),
        'datalist'          => __( 'Datalist', 'events-made-easy' ),
    ];
    return $types;
}

function eme_get_fieldtype( $type ) {
    $fieldtypes = eme_get_fieldtypes();
    return $fieldtypes[ $type ];
}

function eme_is_multifield( $type ) {
    return in_array( $type, [ 'dropdown', 'dropdown_multi', 'radiobox', 'radiobox_vertical', 'checkbox', 'checkbox_vertical', 'datalist' ] );
}

function eme_get_formfield_html( $formfield, $field_name, $entered_val, $required, $class = '', $ro = 0, $force_single = 0, $force_edit = 0 ) {
    if ( empty( $formfield ) ) {
        return;
    }

    $simple_fieldname = 'FIELD' . $formfield['field_id'];
    if ( empty( $field_name ) ) {
        $field_name = $simple_fieldname;
    }

    $field_name = wp_strip_all_tags( $field_name );
    if ( eme_is_admin_request() && has_filter( 'eme_admin_field_value_filter' ) ) {
        $entered_val = apply_filters( 'eme_admin_field_value_filter', $formfield, $field_name, $entered_val );
    } elseif ( ! eme_is_admin_request() && has_filter( 'eme_field_value_filter' ) ) {
        $entered_val = apply_filters( 'eme_field_value_filter', $formfield, $field_name, $entered_val );
    }

    if ( ! is_array( $entered_val ) && eme_is_multi( $entered_val ) ) {
        $entered_val = eme_convert_multi2array( $entered_val );
    }

    if ( $ro ) {
        $readonly = "readonly='readonly'";
        $disabled = "disabled='disabled'";
    } else {
        $readonly = '';
        $disabled = '';
    }

    if ( $class ) {
        $class_att = "class='$class eme_formfield'";
    } else {
        $class_att = "class='eme_formfield'";
    }
    $field_attributes = eme_merge_classes_into_attrs("$class eme_formfield", $formfield['field_attributes']);
    if (!empty($formfield['admin_attributes'])) {
        $admin_attributes = eme_merge_classes_into_attrs("$class eme_formfield", $formfield['admin_attributes']);
    } else {
        $admin_attributes = $field_attributes;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    $field_values = '';
    $field_tags = '';
    if ( (eme_is_admin_request() && isset( $_REQUEST['eme_admin_action'] )) || $force_edit ) {
        // remove some attributes for backend edit (like checked)
        $field_attributes = eme_remove_attrs('checked', $admin_attributes);

        // fields can have a different value for front/backend for multi-fields
        if ( ! empty( $formfield['admin_values'] ) ) {
            $field_values = $formfield['admin_values'];
            if ( ! empty( $formfield['admin_tags'] ) ) {
                $field_tags = $formfield['admin_tags'];
            } else {
                $field_tags = $formfield['admin_values'];
            }
        } else {
            $field_values = $formfield['field_values'];
            $field_tags = $formfield['field_tags'];
        }
    } else {
        $field_values = $formfield['field_values'];
        $field_tags   = $formfield['field_tags'];
    }
    if ( empty( $field_tags ) ) {
        $field_tags = $field_values;
    }

    $html = '';
    switch ( $formfield['field_type'] ) {
        case 'text':
        case 'date':
        case 'datetime-local':
        case 'month':
        case 'week':
        case 'time':
        case 'color':
        case 'email':
        case 'number':
        case 'range':
        case 'tel':
        case 'url':
            # for text fields
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = esc_html( $value );
            $html  = "<input $readonly $required_att type='" . $formfield['field_type'] . "' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            break;
        case 'hidden':
            $value = eme_translate( $field_tags );
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = esc_html( $value );
            if ( eme_is_admin_request() ) {
                    $html  = "<input $readonly $required_att type='text' name='$field_name' id='$field_name' value='$value' $field_attributes><br>";
                    $html .= __( 'This is a hidden field, but in the backend it is shown as text so an admin can see its value and optionally change it', 'events-made-easy' );
            } else {
                $html = "<input $readonly $required_att type='hidden' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            }
            break;
        case 'password':
            $value = esc_html( $entered_val );
            $new_attrs = eme_merge_classes_into_attrs('eme_passwordfield', $field_attributes);
            $html = "<input $readonly $required_att type='text' autocomplete='off' name='$field_name' id='$field_name' value='$value' $new_attrs>";
            break;
        case 'readonly':
            $value = esc_html( $entered_val );
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = esc_html( $value );
            $html  = "<input readonly='readonly' $required_att type='text' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            break;
        case 'dropdown':
            # dropdown
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            // since the values for a dropdown field need not be unique, we give them as an array to be built with eme_ui_select
            foreach ( $values as $key => $val ) {
                $tag      = eme_translate( $tags[ $key ] );
                $new_el   = [
                    0 => $val,
                    1 => $tag,
                ];
                $my_arr[] = $new_el;
            }
            $new_attrs = eme_merge_classes_into_attrs('eme_snapselect', $field_attributes) . ' ' . $disabled;
            $html = eme_ui_select( $entered_val, $field_name, $my_arr, '', $required, '', $new_attrs );
            break;
        case 'dropdown_multi':
            # dropdown, multiselect
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            // since the values for a dropdown field need not be unique, we give them as an array to be built with eme_ui_select
            foreach ( $values as $key => $val ) {
                $tag      = eme_translate( $tags[ $key ] );
                $new_el   = [
                    0 => $val,
                    1 => $tag,
                ];
                $my_arr[] = $new_el;
            }
            // force_single can be 1 (only possible case is in the filterform for now)
            if ( $force_single == 1 ) {
                $html = eme_ui_select( $entered_val, $field_name, $my_arr, '', $required, '', $field_attributes . ' ' . $disabled );
            } else {
                $new_attrs = eme_merge_classes_into_attrs('eme_snapselect', $field_attributes) . ' ' . $disabled;
                $html = eme_ui_multiselect( $entered_val, $field_name, $my_arr, 5, '', $required, '', $new_attrs );
            }
            break;
        case 'textarea':
            # textarea
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = esc_html( $value );
            $html  = "<textarea $required_att name='$field_name' id='$field_name' $field_attributes $readonly>$value</textarea>";
            break;
        case 'radiobox':
            # radiobox
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            $html = eme_ui_radio( $entered_val, $field_name, $my_arr, true, $required, '', $field_attributes . ' ' . $disabled );
            break;
        case 'radiobox_vertical':
            # radiobox, vertical
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            $html = eme_ui_radio( $entered_val, $field_name, $my_arr, false, $required, '', $field_attributes . ' ' . $disabled );
            break;
        case 'checkbox':
            # checkbox
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            // checkboxes can't be made required in the frontend, since that would require all checkboxes to be checked
            // so we use a div+js to accomplish this
            $html = '';
            if ( $required ) {
                $html = '<div class="eme-checkbox-group-required">';
            }
            $html .= eme_ui_checkbox( $entered_val, $field_name, $my_arr, true, 0, '', $field_attributes . ' ' . $disabled );
            if ( $required ) {
                $html .= '</div>';
            }
            break;
        case 'checkbox_vertical':
            # checkbox, vertical
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            // checkboxes can't be made required in the frontend, since that would require all checkboxes to be checked
            // so we use a div+js to accomplish this
            $html = '';
            if ( $required ) {
                $html = '<div class="eme-checkbox-group-required">';
            }
            $html .= eme_ui_checkbox( $entered_val, $field_name, $my_arr, false, 0, '', $field_attributes . ' ' . $disabled );
            if ( $required ) {
                $html .= '</div>';
            }
            break;
        case 'file':
            // file upload
            // in the admin interface, no upload is required (otherwise edit will never work as well ...)
            if ( eme_is_admin_request() || $force_edit ) {
                $required     = 0;
                $required_att = '';
            }
            // only simple field names accepted, that way the upload code can stay simple and we don't need to worry about arrays and such
            if ( $field_name != $simple_fieldname ) {
                // the field_name can be something like an array name, so we remove redundant info (like the field id in it) and keep integers
                $clean      = preg_replace( "/$simple_fieldname/", '', $field_name );
                $indexes    = preg_replace( '/[^\d]/i', '', $clean );
                $field_name = $simple_fieldname . '_' . $indexes;
            }
            // if the entered_val is not empty it means the file is already uploaded, so we don't show the form
            $html = '<span>';
            if ( ! empty( $entered_val ) ) {
                $showhide_style = "class='eme-hidden'";
            } else {
                $showhide_style = '';
            }
            $html .= "<input type='file' $disabled $required_att name='$field_name' id='$field_name' $showhide_style $field_attributes>";
            if ( ! empty( $entered_val ) ) {
                foreach ( $entered_val as $file ) {
                    $html .= eme_get_uploaded_file_linkdelete( $file );
                }
            }
            if ( empty( $entered_val ) ) {
                $html .= '<br>';
            }
            $html .= '</span>';
            break;
        case 'multifile':
            // file upload
            // in the admin interface, no upload is required (otherwise edit will never work as well ...)
            if ( eme_is_admin_request() || $force_edit ) {
                    $required     = 0;
                    $required_att = '';
            }
            // only simple field names accepted, that way the upload code can stay simple and we don't need to worry about arrays and such
            if ( $field_name != $simple_fieldname ) {
                // the field_name can be something like an array name, so we remove redundant info (like the field id in it) and keep integers
                $clean      = preg_replace( "/$simple_fieldname/", '', $field_name );
                $indexes    = preg_replace( '/[^\d]/i', '', $clean );
                $field_name = $simple_fieldname . '_' . $indexes;
            }
            // if the entered_val is not empty it means the file is already uploaded, so we don't show the form
            $html = '<span>';
            if ( ! empty( $entered_val ) ) {
                $showhide_style = "class='eme-hidden'";
            } else {
                $showhide_style = '';
            }
            $html .= "<input type='file' $disabled $required_att name='{$field_name}[]' id='$field_name' multiple $showhide_style $field_attributes>";
            if ( ! empty( $entered_val ) ) {
                foreach ( $entered_val as $file ) {
                    $html .= eme_get_uploaded_file_linkdelete( $file );
                }
            }
            if ( empty( $entered_val ) ) {
                $html .= '<br>';
            }
            $html .= '</span>';
            break;
        case 'date_js':
            # for date JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getDate();
            }

            $value = esc_html( $value );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_DATE_FORMAT;
            }
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='{$field_name}' id='{$field_name}' data-date='$value' data-format='$dateformat' class='eme_formfield eme_formfield_fdate $class'>";
            break;
        case 'datetime_js':
            # for datetime JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getDateTime();
            }
            $value    = esc_html( $value );
            $js_value = eme_js_datetime( $value, EME_TIMEZONE );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_DATE_FORMAT .' '. EME_WP_TIME_FORMAT;
            }
            $dateformat = $field_attributes;
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='{$field_name}' id='{$field_name}' data-date='$js_value' data-format='$dateformat' class='eme_formfield eme_formfield_fdatetime $class'>";
            break;
        case 'time_js':
            # for time JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getTime();
            }
            $value    = esc_html( $value );
            $js_value = eme_js_datetime( $value, EME_TIMEZONE );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_TIME_FORMAT;
            }
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='{$field_name}' id='{$field_name}' data-date='$js_value' data-format='$dateformat' class='eme_formfield_ftime $class'>";
            break;
        case 'datalist':
            # for text fields
            $value = $entered_val;
            $value = esc_html( $entered_val );
            $html  = "<input $readonly $required_att type='text' list='list_$field_name' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            // now the datalist
            $html  .= "<datalist id='list_$field_name'>";
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            foreach ( $values as $key => $val ) {
                $val  = esc_html($val);
                $tag  = esc_html($tags[ $key ]);
                $html .= "<option value='$val'>$tag</option>";
            }
            $html  .= "</datalist>";
            break;
    }
    return $html;
}

function eme_replace_eventtaskformfields_placeholders( $format, $task, $event ) {
    //$used_spaces = eme_count_task_approved_signups( $task['task_id'] );
    if ($event['event_properties']['ignore_pending_tasksignups']) {
        $used_spaces = eme_count_task_approved_signups( $task['task_id'] );
    } else {
        $used_spaces = eme_count_task_signups( $task['task_id'] );
    }
    $free_spaces = $task['spaces'] - $used_spaces;

    $task_ended       = 0;
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $task_end_obj     = emeExpressiveDate::createFromFormat( 'Y-m-d H:i:s', $task['task_end'], emeExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
    if ( $task_end_obj < $eme_date_obj_now ) {
        $task_ended = 1;
    }
    $use_radiobox = 0;
    if ( $event['event_properties']['task_only_one_signup_pp'] ) {
        $use_radiobox = 1;
    }

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        if ( preg_match( '/#_TASKSIGNUPCHECKBOX$/', $result ) ) {
            $disabled = '';
            if ( $free_spaces == 0 || $task_ended ) {
                $disabled = 'disabled="disabled"';
            }
            $select_value = $task['task_id'];
            $select_name  = 'eme_task_signups[' . $event['event_id'] . '][]';
            $select_id    = 'eme_task_signups_' . $event['event_id'] . '_' . $select_value;
            if ($use_radiobox) {
                $replacement  = "<input type='radio' name='{$select_name}' id='{$select_id}' value='$select_value' $disabled>";
            } else {
                $replacement  = "<input type='checkbox' name='{$select_name}' id='{$select_id}' value='$select_value' $disabled>";
            }
        } elseif ( preg_match( '/#_TASKHTMLID$/', $result ) ) {
            $replacement    = 'eme_task_signups_' . $event['event_id'] . '_' . $select_value;
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover task placeholders
    $format = eme_replace_task_placeholders( $format, $task, $event );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );

    return $format;
}

function eme_replace_cancelformfields_placeholders( $event ) {
    $eme_is_admin_request = eme_is_admin_request();
    if ( $eme_is_admin_request ) {
        return '';
    }

    $registration_wp_users_only = $event['registration_wp_users_only'];
    if ( $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }
    $readonly = $registration_wp_users_only ? "readonly='readonly'" : '';

    if ( ! eme_is_empty_string( $event['event_cancel_form_format'] ) ) {
        $format = $event['event_cancel_form_format'];
    } elseif ( $event['event_properties']['event_cancel_form_format_tpl'] > 0 ) {
        $format = eme_get_template_format( $event['event_properties']['event_cancel_form_format_tpl'] );
    } else {
        $format = get_option( 'eme_cancel_form_format' );
    }

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && $event['event_properties']['captcha_only_logged_out'] );
    $format = eme_add_missing_placeholders( $format, $add_captcha );
    if ($add_captcha && ! $eme_is_admin_request ) {
        $selected_captcha = $event['event_properties']['selected_captcha'];
    }

    $person = eme_new_person();
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $fetched      = eme_get_person_by_wp_id( $current_user->ID );
        $person       = $fetched ?: eme_fake_person_by_wp_id( $current_user->ID );
    }
    $person = eme_esc_person_for_form( $person );

    $ctx      = [ 'required' => false, 'required_att' => '' ];

    // Pre-validate required placeholders before running the dispatch
    $has_lastname = preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_email    = preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    if ( ! $has_lastname || ! $has_email ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_EMAIL placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $handlers = [
        '/#_(NAME|LASTNAME)(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $person, $readonly ) {
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Last name', 'events-made-easy' );
            return [ 'html' => "<input required='required' type='text' name='lastname' id='lastname' value='{$person['lastname']}' $readonly placeholder='$placeholder_text'>", 'set_required' => true ];
        },
        '/#_FIRSTNAME(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $person, $readonly ) {
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'First name', 'events-made-easy' );
            return "<input {$ctx['required_att']} type='text' name='firstname' id='firstname' value='{$person['firstname']}' $readonly placeholder='$placeholder_text'>";
        },
        '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $person, $readonly ) {
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Email', 'events-made-easy' );
            return [ 'html' => "<input required='required' type='email' name='email' id='email' value='{$person['email']}' $readonly placeholder='$placeholder_text'>", 'set_required' => true ];
        },
        '/#_CANCELCOMMENT(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Cancel reason', 'events-made-easy' );
            return "<textarea {$ctx['required_att']} name='eme_cancelcomment' placeholder='$placeholder_text'></textarea>";
        },
        '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/' => function( $result, $matches, $ctx ) use ( $selected_captcha ) {
            if ( ! empty( $selected_captcha ) ) {
                return eme_generate_captchas_html( $selected_captcha );
            }
            return '';
        },
        '/#_SUBMIT(\{.+?\})?/' => function( $result, $matches, $ctx ) {
            $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : get_option( 'eme_rsvp_delbooking_submit_string' );
            return "<img id='rsvp_cancel_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
        },
    ];

    $format = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    $format = eme_replace_event_placeholders( $format, $event );
    $format = eme_translate( $format );

    return $format;
}

function eme_replace_cancel_payment_placeholders( $format, $person, $booking_ids ) {
    $eme_is_admin_request = eme_is_admin_request();

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ));
    $format = eme_add_missing_placeholders( $format, $add_captcha );
    $configured_captchas = eme_get_configured_captchas();
    if ($add_captcha && ! empty( $configured_captchas ) && ! $eme_is_admin_request ) {
        $selected_captcha = array_key_first( $configured_captchas );
    }

    $ctx      = [ 'required' => false, 'required_att' => '' ];

    if ( ! str_contains( $format, '#_CANCEL_PAYMENT_LINE' ) ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_CANCEL_PAYMENT_LINE and #_SUBMIT (or similar) placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $handlers = [
        '/#_CANCEL_PAYMENT_LINE$/' => function( $result, $matches, $ctx ) use ( $booking_ids ) {
            $tmp_format       = get_option( 'eme_cancel_payment_line_format' );
            $replacement      = '';
            $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
            foreach ( $booking_ids as $booking_id ) {
                $booking = eme_get_booking( $booking_id );
                $event   = eme_get_event( $booking['event_id'] );
                if ( empty( $event ) ) { continue; }
                $cancel_cutofftime    = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
                $eme_cancel_rsvp_days = -1 * $event['event_properties']['cancel_rsvp_days'];
                $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
                if ( $cancel_cutofftime < $eme_date_obj_now ) {
                    $no_longer_allowed = eme_translate( get_option( 'eme_rsvp_cancel_no_longer_allowed_string' ) );
                    // signal early return via a special key
                    return [ 'html' => '', 'early_return' => "<div class='eme-message-error eme-rsvp-message-error'>$no_longer_allowed</div>" ];
                }
                $cancel_cutofftime    = new emeExpressiveDate( $booking['creation_date'], EME_TIMEZONE );
                $eme_cancel_rsvp_days = $event['event_properties']['cancel_rsvp_age'];
                $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
                if ( $eme_cancel_rsvp_days && $cancel_cutofftime < $eme_date_obj_now ) {
                    $no_longer_allowed = eme_translate( get_option( 'eme_rsvp_cancel_no_longer_allowed_string' ) );
                    return [ 'html' => '', 'early_return' => "<div class='eme-message-error eme-rsvp-message-error'>$no_longer_allowed</div>" ];
                }
                $replacement .= eme_replace_booking_placeholders( $tmp_format, $event, $booking );
            }
            return [ 'html' => $replacement ];
        },
        '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/' => function( $result, $matches, $ctx ) use ( $selected_captcha ) {
            if ( ! empty( $selected_captcha ) ) {
                return eme_generate_captchas_html( $selected_captcha );
            }
            return '';
        },
        '/#_SUBMIT(\{.+?\})?/' => function( $result, $matches, $ctx ) {
            $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : get_option( 'eme_rsvp_delbooking_submit_string' );
            return "<img id='cancel_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
        },
    ];

    // eme_replace_cancel_payment_placeholders needs early-return support; run inline
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = false;
        $replacement        = '';

        foreach ( $handlers as $pattern => $handler ) {
            if ( preg_match( $pattern, $result, $matches ) ) {
                $found = true;
                $ret   = $handler( $result, $matches, $ctx );
                if ( is_array( $ret ) ) {
                    if ( ! empty( $ret['early_return'] ) )   { return $ret['early_return']; }
                    $replacement = $ret['html'] ?? '';
                    if ( ! empty( $ret['not_found'] ) )      { $found = false; }
                    if ( ! empty( $ret['set_required'] ) ) { $required = true; }
                } else {
                    $replacement = (string) $ret;
                }
                break;
            }
        }
        if ( $found ) {
            if ( is_null( $replacement ) ) { $replacement = ''; }
            $format        = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    $format = eme_replace_people_placeholders( $format, $person );
    $format = eme_translate( $format );

    return $format;
}

/**
 * Build a person array pre-escaped for use as form field values.
 * Wraps eme_new_person() defaults with esc_html() on all string fields,
 * and applies the single-country-default for country_code.
 * Pass a real $person array to fill from existing data.
 */
function eme_esc_person_for_form( $person = [] ) {
    if ( empty( $person ) ) {
        $person = eme_new_person();
    }
    // apply single-country default when nothing is set
    if ( empty( $person['country_code'] ) ) {
        $countries_alpha2 = eme_get_countries_alpha2();
        if ( count( $countries_alpha2 ) === 1 ) {
            $person['country_code'] = $countries_alpha2[0];
        }
    }
    // escape all string fields used as HTML attribute values
    foreach ( [ 'lastname', 'firstname', 'birthplace', 'address1', 'address2',
                'city', 'zip', 'state_code', 'country_code', 'email', 'phone' ] as $key ) {
        $person[ $key ] = isset( $person[ $key ] ) ? esc_html( $person[ $key ] ) : '';
    }
    if ( isset( $person['birthdate'] ) && ! eme_is_date( $person['birthdate'] ) ) {
        $person['birthdate'] = '';
    } else {
        $person['birthdate'] = isset( $person['birthdate'] ) ? esc_html( $person['birthdate'] ) : '';
    }
    $person['massmail'] = intval( $person['massmail'] ?? get_option('eme_people_massmail') );
    $person['bd_email'] = isset( $person['bd_email'] ) ? intval( $person['bd_email'] ) : 0;
    $person['gdpr']     = isset( $person['gdpr'] )     ? intval( $person['gdpr'] )     : 0;
    return $person;
}

function eme_get_dyndata_people_fields( $condition ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $prepared_sql              = $wpdb->prepare( "SELECT * FROM $formfields_table where field_purpose='people' AND FIND_IN_SET(%s,field_condition)", $condition );
    return $wpdb->get_results( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Shared handler definitions for person/contact form fields.
 *
 * $ctx keys used here:
 *   person           array   esc_html'd person (from eme_esc_person_for_form)
 *   fn_prefix        string  field-name prefix: '' or 'task_'
 *   form_id          mixed   used for building unique element ids (state/country)
 *   eme_is_admin     bool
 *   readonly         string  global readonly attr for this form context
 *   disabled         string  global disabled attr
 *   dfc_basic        string  bare dynamic-field class name(s), e.g. 'dynamicupdates'
 *   extra_css        string  additional CSS classes to append ('' for now)
 *   allow_clear      bool    show data-clearable on readonly name/email fields
 *   invite_readonly  string  readonly attr when invite URL is followed (rsvp only)
 *   selected_captcha string
 *   required         bool    set by dispatch loop each iteration
 *   required_att     string  set by dispatch loop each iteration
 *
 * Handlers return either:
 *   - a plain string (the HTML replacement)
 *   - an array with keys: 'html', and optionally:
 *       'not_found'     => true   (treat as unmatched placeholder)
 *       'set_required'  => true   (mark $required = true after handler)
 *       'early_return'  => string (return immediately with this value)
 */
function eme_get_person_formfield_handler_definitions() {
    static $handlers = [];
    if ( ! empty( $handlers ) ) {
        return $handlers;
    }

    $handlers = [
        '/#_(NAME|LASTNAME)(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];

            $fieldname = $ctx['fn_prefix'] . 'lastname';
            if ( is_user_logged_in() && ! $ctx['eme_is_admin'] ) {
                $this_readonly = "readonly='readonly'";
                if ( $ctx['allow_clear'] ) {
                    $this_readonly .= " data-clearable='true'";
                }
            } elseif ( ! empty( $ctx['invite_readonly'] ) && ! empty( $p['lastname'] ) ) {
                $this_readonly = $ctx['invite_readonly'];
            } else {
                $this_readonly = $ctx['readonly'];
            }
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Last name', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            $replacement = "<input required='required' type='text' name='$fieldname' id='$fieldname' value='{$p['lastname']}' $this_readonly class='$class' placeholder='$placeholder_text'>";
            if ( wp_script_is( 'eme-autocomplete-form', 'enqueued' ) && get_option( 'eme_autocomplete_sources' ) !== 'none' ) {
                $replacement .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url( EME_PLUGIN_URL ) . "images/warning.png' alt='warning' title='" . esc_attr__( "Notice: since you're logged in as a person with the right to edit or author this event, the 'Last name' field is also an autocomplete field so you can select existing people if desired. Or just clear the field and start typing.", 'events-made-easy' ) . "'>";
            }
            if ( ! empty( $ctx['wp_profile_warning'] ) ) {
                $replacement .= sprintf( $ctx['wp_profile_warning'], esc_html__( 'You can change your last name in your WP profile.', 'events-made-easy' ) );
            }
            return [ 'html' => $replacement, 'set_required' => true ];
        },
        '/#_FIRSTNAME(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];

            $fieldname = $ctx['fn_prefix'] . 'firstname';
            if ( is_user_logged_in() && ! $ctx['eme_is_admin'] ) {
                $this_readonly = "readonly='readonly'";
            } elseif ( ! empty( $ctx['invite_readonly'] ) && ! empty( $p['firstname'] ) ) {
                $this_readonly = $ctx['invite_readonly'];
            } else {
                $this_readonly = $ctx['readonly'];
            }
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'First name', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            $replacement = "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['firstname']}' $this_readonly class='$class' placeholder='$placeholder_text'>";
            if ( ! empty( $ctx['wp_profile_warning'] ) ) {
                $replacement .= sprintf( $ctx['wp_profile_warning'], esc_html__( 'You can change your first name in your WP profile.', 'events-made-easy' ) );
            }
            return [ 'html' => $replacement ];
        },
        '/#_BIRTHDATE(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'birthdate';
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Date of birth', 'events-made-easy' );
            $class = trim( "eme_formfield eme_formfield_fdate {$ctx['extra_css']}" );
            return "<input {$ctx['required_att']} readonly='readonly' {$ctx['disabled']} type='text' name='$fieldname' id='$fieldname' data-date='{$p['birthdate']}' data-format='" . EME_WP_DATE_FORMAT . "' data-view='years' class='$class' placeholder='$placeholder_text'>";
        },
        '/#_BIRTHPLACE(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'birthplace';
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Place of birth', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['birthplace']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_ADDRESS1(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'address1';
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_attr( eme_translate( get_option( 'eme_address1_string' ) ) );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['address1']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_ADDRESS2(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'address2';
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_attr( eme_translate( get_option( 'eme_address2_string' ) ) );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['address2']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_CITY(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'city';
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'City', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['city']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_(ZIP|POSTAL)(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'zip';
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Postal code', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='text' name='$fieldname' id='$fieldname' value='{$p['zip']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_STATE$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'state_code';
            $fieldid   = ! empty( $ctx['form_id'] ) ? $ctx['form_id'] . '-' . $fieldname : $fieldname;
            $state_arr = ! empty( $p['state_code'] ) ? [ $p['state_code'] => eme_get_state_name( $p['state_code'], $p['country_code'] ) ] : [];
            $class     = trim( "eme_snapselect_state_class {$ctx['dfc_basic']} {$ctx['extra_css']}" );
            return eme_form_select( $p['state_code'], $fieldname, $fieldid, $state_arr, '', $ctx['required'], $class, $ctx['disabled'] );
        },
        '/#_COUNTRY$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'country_code';
            $fieldid   = ! empty( $ctx['form_id'] ) ? $ctx['form_id'] . '-' . $fieldname : $fieldname;
            $country_arr = ! empty( $p['country_code'] ) ? [ $p['country_code'] => eme_get_country_name( $p['country_code'] ) ] : [];
            $class     = trim( "eme_snapselect_country_class {$ctx['dfc_basic']} {$ctx['extra_css']}" );
            return eme_form_select( $p['country_code'], $fieldname, $fieldid, $country_arr, '', $ctx['required'], $class, $ctx['disabled'] );
        },
        '/#_COUNTRY\{(.+)\}$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'country_code';
            $fieldid   = ! empty( $ctx['form_id'] ) ? $ctx['form_id'] . '-' . $fieldname : $fieldname;
            $class     = trim( "eme_snapselect_country_class {$ctx['dfc_basic']} {$ctx['extra_css']}" );
            if ( ! empty( $p['country_code'] ) ) {
                $country_arr = [ $p['country_code'] => eme_get_country_name( $p['country_code'] ) ];
                return eme_form_select( $p['country_code'], $fieldname, $fieldid, $country_arr, '', $ctx['required'], $class, $ctx['disabled'] );
            }
            $country_code = $matches[1];
            $country_name = eme_get_country_name( $country_code );
            if ( ! empty( $country_name ) ) {
                $country_arr = [ $country_code => $country_name ];
                return eme_form_select( $country_code, $fieldname, $fieldid, $country_arr, '', $ctx['required'], $class, $ctx['disabled'] );
            }
            return '';
        },
        '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'email';

            if ( is_user_logged_in() && ! $ctx['eme_is_admin'] ) {
                $this_readonly = "readonly='readonly'";
            } elseif ( ! empty( $ctx['invite_readonly'] ) && ! empty( $p['email'] ) ) {
                $this_readonly = $ctx['invite_readonly'];
            } else {
                $this_readonly = $ctx['readonly'];
            }
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Email', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            $req_att = $ctx['eme_is_admin'] ? '' : "required='required'";
            $replacement = "<input $req_att type='email' name='$fieldname' id='$fieldname' value='{$p['email']}' $this_readonly class='$class' placeholder='$placeholder_text'>";
            if ( ! empty( $ctx['wp_profile_warning'] ) ) {
                $replacement .= sprintf( $ctx['wp_profile_warning'], esc_html__( 'You can change your email in your WP profile.', 'events-made-easy' ) );
            }
            return [ 'html' => $replacement, 'set_required' => ! empty( $req_att ) ];
        },
        '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/' => function( $result, $matches, $ctx ) {
            $p = $ctx['person'];
            $fieldname = $ctx['fn_prefix'] . 'phone';
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Phone number', 'events-made-easy' );
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] );
            return "<input {$ctx['required_att']} type='tel' name='$fieldname' id='$fieldname' value='{$p['phone']}' {$ctx['readonly']} class='$class' placeholder='$placeholder_text'>";
        },
        '/#_BIRTHDAY_EMAIL$/' => function( $result, $matches, $ctx ) {
            return eme_ui_select_binary( $ctx['person']['bd_email'], $ctx['fn_prefix'] . 'bd_email' );
        },
        '/#_OPT_IN$/' => function( $result, $matches, $ctx ) {
            $selected = $ctx['person']['massmail'] ?? 0;
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] . ' eme_snapselect' );
            $replacement = eme_ui_select_binary( $selected, $ctx['fn_prefix'] . 'massmail', 0, $class, $ctx['disabled'] );
            if ( ! $ctx['eme_is_admin'] && get_option( 'eme_massmail_popup' ) ) {
                $popup = esc_html( get_option( 'eme_massmail_popup_text' ) );
                if ( ! eme_is_empty_string( $popup ) ) {
                    $confirm = esc_html__( 'Yes', 'events-made-easy' );
                    $cancel  = esc_html__( 'No', 'events-made-easy' );
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
                }
            }
            return $replacement;
        },
        '/#_OPT_OUT|#_MASSMAIL/' => function( $result, $matches, $ctx ) {
            $selected = $ctx['person']['massmail'] ?? 1;
            $class = trim( $ctx['dfc_basic'] . ' ' . $ctx['extra_css'] . ' eme_snapselect' );
            $replacement = eme_ui_select_binary( $selected, $ctx['fn_prefix'] . 'massmail', 0, $class, $ctx['disabled'] );
            if ( ! $ctx['eme_is_admin'] && get_option( 'eme_massmail_popup' ) ) {
                $popup = esc_html( get_option( 'eme_massmail_popup_text' ) );
                if ( ! eme_is_empty_string( $popup ) ) {
                    $confirm = esc_html__( 'Yes', 'events-made-easy' );
                    $cancel  = esc_html__( 'No', 'events-made-easy' );
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
                }
            }
            return $replacement;
        },
        '/#_GDPR(\{.+?\})?/' => function( $result, $matches, $ctx ) {
            if ( $ctx['eme_is_admin'] ) {
                return '';
            }
            $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : '';
            $class = trim( "eme-gdpr-field nodynamicupdates {$ctx['extra_css']}" );
            return eme_ui_checkbox_binary( $ctx['person']['gdpr'], $ctx['fn_prefix'] . 'gdpr', $label, 1, $class, $ctx['disabled'] );
        },
        '/#_REMEMBERME(\{.+?\})?/' => function( $result, $matches, $ctx ) {
            if ( $ctx['eme_is_admin'] || is_user_logged_in() ) {
                return '';
            }
            $label = isset( $matches[1] )
                ? substr( $matches[1], 1, -1 )
                : __( 'Remember me?', 'events-made-easy' );
            return eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
        },
        '/#_SUBSCRIBE_TO_GROUP\{(.+?)\}(\{.+?\})?/' => function( $result, $matches, $ctx ) {
            $group = is_numeric( $matches[1] )
                ? eme_get_group( $matches[1] )
                : eme_get_group_by_name( eme_sanitize_request( $matches[1] ) );
            if ( empty( $group ) ) {
                return __( 'Group does not exist', 'events-made-easy' );
            }
            if ( ! $group['public'] ) {
                return __( 'Group is not public', 'events-made-easy' );
            }
            $group_id = $group['group_id'];
            $label = isset( $matches[2] ) ? substr( $matches[2], 1, -1 ) : $group['name'];
            $class = trim( "nodynamicupdates {$ctx['extra_css']}" );
            $replacement = "<input id='subscribe_groups_$group_id' name='subscribe_groups[]' value='$group_id' type='checkbox' class='$class'>";
            if ( ! empty( $label ) ) {
                $replacement .= "<label for='subscribe_groups_$group_id'>" . esc_html( $label ) . '</label>';
            }
            return $replacement;
        },
        '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/' => function( $result, $matches, $ctx ) {
            if ( ! empty( $ctx['selected_captcha'] ) ) {
                $captcha_only_logged_out = $ctx['captcha_only_logged_out'] ?? null;
                return eme_generate_captchas_html( $ctx['selected_captcha'], $captcha_only_logged_out );
            }
            return '';
        },
        '/#_FIELDNAME\{(.+)\}/' => function( $result, $matches, $ctx ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) ) {
                return esc_html( eme_translate( $formfield['field_name'] ) );
            }
            return [ 'html' => '', 'not_found' => true ];
        },
    ];
    return $handlers;
}

/**
 * Generic dispatch loop shared by all form-rendering functions.
 *
 * @param string $format      The format string being processed.
 * @param array  $handlers    Merged handler table (shared + function-specific).
 * @param array  $ctx         Context array; 'required' and 'required_att' are
 *                            updated per iteration by this function.
 * @return string             The processed format string.
 */
function eme_run_formfield_dispatch( $format, $handlers, $ctx ) {
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;

    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = false;
        $required           = false;
        $required_att       = '';
        $replacement        = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = true;
            $required_att = "required='required'";
        }
        // support #_RESP* aliases
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }
        // support #_CONSENT as alias for #_GDPR
        if ( strstr( $result, '#_CONSENT' ) ) {
            $result = str_replace( '#_CONSENT', '#_GDPR', $result );
        }

        $ctx['required']     = $required;
        $ctx['required_att'] = $required_att;

        foreach ( $handlers as $pattern => $handler ) {
            if ( preg_match( $pattern, $result, $matches ) ) {
                $found = true;
                $ret   = $handler( $result, $matches, $ctx );
                if ( is_array( $ret ) ) {
                    if ( ! empty( $ret['early_return'] ) )   { return $ret['early_return']; }
                    $replacement = $ret['html'] ?? '';
                    if ( ! empty( $ret['not_found'] ) )      { $found = false; }
                    if ( ! empty( $ret['set_required'] ) ) { $required = true; }
                } else {
                    $replacement = (string) $ret;
                }
                break;
            }
        }

        if ( $found ) {
            if ( $required ) {
                $req_str = eme_translate( get_option( 'eme_form_required_field_string' ) );
                if ( ! empty( $req_str ) ) {
                    $replacement .= "<div class='eme-required-field'>$req_str</div>";
                }
            }
            if ( is_null( $replacement ) ) {
                $replacement = '';
            }
            $format        = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    return $format;
}

function eme_replace_dynamic_rsvp_formfields_placeholders( $event, $booking, $format, $grouping, $i = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();
    $event_id             = $event['event_id'];
    $dynamic_price_class  = 'dynamicprice';
    // dynamic_field_class: dynamically added field, no extra ajax actions except price
    $dynamic_field_class = 'nodynamicupdates dynamicfield';

    if ( $eme_is_admin_request && ! empty( $booking['booking_id'] ) ) {
        $editing_booking_from_backend = true;
        $dyn_answers                  = eme_get_dyndata_booking_answer( $booking['booking_id'], $grouping, $i );
        $files1                       = eme_get_uploaded_files( $booking['person_id'], 'people' );
        $files2                       = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
        $files                        = array_merge( $files1, $files2 );
    } else {
        $editing_booking_from_backend = false;
        $dyn_answers                  = [];
        $files                        = [];
    }

    $handlers = [
        '/#_FIELDNAME\{(.+)\}/' => function( $result, $matches, $ctx ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) ) {
                return esc_html( eme_translate( $formfield['field_name'] ) );
            }
            return [ 'html' => '', 'not_found' => true ];
        },
        '/#_FIELD\{(.+)\}/' => function( $result, $matches, $ctx ) use ( $event_id, $grouping, $i, $dynamic_price_class, $dynamic_field_class, $editing_booking_from_backend, $dyn_answers, $files ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $var_prefix     = "dynamic_bookings[$event_id][$grouping][$i][";
                $postfield_name = "{$var_prefix}FIELD{$field_id}]";
                $postvar_arr    = [ 'dynamic_bookings', $event_id, $grouping, $i, 'FIELD' . $field_id ];
                $entered_val    = eme_getValueFromPath( $_POST, $postvar_arr );
                if ( $editing_booking_from_backend && $entered_val === false ) {
                    foreach ( $dyn_answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                        }
                    }
                }
                if ( $editing_booking_from_backend && ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id && $file['extra_id'] == "$event_id$grouping$i" ) {
                            $entered_files[] = $file;
                        }
                    }
                    $entered_val = $entered_files;
                }
                $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
                $class    = $formfield['extra_charge'] ? "$dynamic_price_class $dynamic_field_class" : $dynamic_field_class;
                return [ 'html' => eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $class ), 'set_required' => $required ];
            }
            return [ 'html' => '', 'not_found' => true ];
        },
        '/#_FIELDCOUNTER$/' => function( $result, $matches, $ctx ) use ( $i ) {
            return intval( $i ) + 1;
        },
        '/#_FIELDGROUPINDEX$/' => function( $result, $matches, $ctx ) use ( $grouping ) {
            return intval( $grouping ) + 1;
        },
    ];

    $ctx         = [ 'required' => false, 'required_att' => '' ];
    $format      = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    $format      = eme_replace_event_placeholders( $format, $event );
    return $format;
}

function eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $format, $grouping, $i = 0 ) {
    $membership_id       = $membership['membership_id'];
    $dynamic_price_class = 'dynamicprice';
    $dynamic_field_class = 'nodynamicupdates dynamicfield';

    if ( ! empty( $member['member_id'] ) && current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
        $dyn_answers = eme_get_dyndata_member_answer( $member['member_id'], $grouping, $i );
        $files1      = eme_get_uploaded_files( $member['person_id'], 'people' );
        $files2      = eme_get_uploaded_files( $member['member_id'], 'members' );
        $files       = array_merge( $files1, $files2 );
        $member_edit = true;
    } else {
        $dyn_answers = [];
        $files       = [];
        $member_edit = false;
    }

    $handlers = [
        '/#_FIELDNAME\{(.+)\}/' => function( $result, $matches, $ctx ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) ) {
                return esc_html( eme_translate( $formfield['field_name'] ) );
            }
            return [ 'html' => '', 'not_found' => true ];
        },
        '/#_FIELD\{(.+)\}/' => function( $result, $matches, $ctx ) use ( $membership_id, $grouping, $i, $dynamic_price_class, $dynamic_field_class, $dyn_answers, $files, $member_edit ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'members', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $var_prefix     = "dynamic_member[$membership_id][$grouping][$i][";
                $postfield_name = "{$var_prefix}FIELD{$field_id}]";
                $postvar_arr    = [ 'dynamic_member', $membership_id, $grouping, $i, 'FIELD' . $field_id ];
                $entered_val    = eme_getValueFromPath( $_POST, $postvar_arr );
                if ( $entered_val === false ) {
                    foreach ( $dyn_answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                        }
                    }
                }
                if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id && $file['extra_id'] == "$membership_id$grouping$i" ) {
                            $entered_files[] = $file;
                        }
                    }
                    $entered_val = $entered_files;
                }
                $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
                $class    = $formfield['extra_charge'] ? "$dynamic_price_class $dynamic_field_class" : $dynamic_field_class;
                return [ 'html' => eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $class, 0, 0, $member_edit ), 'set_required' => $required ];
            }
            return [ 'html' => '', 'not_found' => true ];
        },
        '/#_FIELDCOUNTER$/' => function( $result, $matches, $ctx ) use ( $i ) {
            return intval( $i ) + 1;
        },
        '/#_FIELDGROUPINDEX$/' => function( $result, $matches, $ctx ) use ( $grouping ) {
            return intval( $grouping ) + 1;
        },
    ];

    $ctx         = [ 'required' => false, 'required_att' => '' ];
    $format      = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    $format      = eme_replace_membership_placeholders( $format, $membership );

    // In the admin member-edit form, wrap each occurrence in a labelled deletable container
    if ( ! empty( $member['member_id'] ) && current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
        $del_label = esc_attr( __( 'Delete this group', 'events-made-easy' ) );
        $del_text  = esc_html( __( 'Delete this group', 'events-made-easy' ) );
        $format    =
            "<fieldset class='eme_dyndata_occurence_block'"
            . " data-member-id='" . intval( $member['member_id'] ) . "'"
            . " data-grouping='"  . intval( $grouping ) . "'"
            . " data-occurence='" . intval( $i ) . "'>"
            . "<legend class='eme_dyndata_occurence_label'>"
            .   " <button type='button' class='button button-small eme_delete_dyndata_occurence'"
            .     " title='$del_label'>&#x2715; $del_text</button>"
            . "</legend>"
            . $format
            . "</fieldset>";
    }
    return $format;
}

function eme_replace_rsvp_formfields_placeholders( $form_id, $event, $booking, $format = '', $is_multibooking = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();

    if ( isset( $event['event_id'] ) ) {
        $event_id = $event['event_id'];
    } else {
        $event_id = 0;
    }
    if ( ! empty( $event['location_id'] ) ) {
        $location = eme_get_location( $event['location_id'] );
    } else {
        $location = [];
    }

    $registration_wp_users_only = $event['registration_wp_users_only'];
    if ( $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }

    $allow_clear = false;
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
            ( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_user->ID || $event['event_contactperson_id'] == $current_user->ID ) ) ) {
            $allow_clear = true;
        } elseif ( ! $registration_wp_users_only ) {
            $allow_clear = true;
        }
    } else {
        $current_user = 0;
    }

    $new_booking_in_frontend      = ! $eme_is_admin_request && empty( $booking['booking_id'] );
    $editing_booking_from_backend = $eme_is_admin_request && ! empty( $booking['booking_id'] );
    $allow_overbooking            = $eme_is_admin_request && get_option( 'eme_rsvp_admin_allow_overbooking' );

    // Build person data
    $person = eme_new_person();
    if ( is_user_logged_in() && ! $eme_is_admin_request ) {
        $fetched = eme_get_person_by_wp_id( $current_user->ID );
        $person  = $fetched ?: eme_fake_person_by_wp_id( $current_user->ID );
    }
    if ( $editing_booking_from_backend && ! empty( $booking['person_id'] ) ) {
        $person = eme_get_person( $booking['person_id'] );
    }
    $person        = eme_esc_person_for_form( $person );
    $bookerComment = $editing_booking_from_backend ? esc_html( $booking['booking_comment'] ) : '';
    $bookedSeats   = $editing_booking_from_backend ? esc_html( $booking['booking_seats'] ) : 0;
    $booking_seats_mp = [];
    if ( $editing_booking_from_backend && $booking['booking_seats_mp'] ) {
        $booking_seats_mp = eme_convert_multi2array( $booking['booking_seats_mp'] );
    }

    // invite URL overrides
    $invite_readonly = '';
    if ( eme_check_invite_url( $event['event_id'] ) && ! $eme_is_admin_request ) {
        if ( ! empty( $_GET['eme_email'] ) ) {
            $person['email'] = eme_sanitize_email( $_GET['eme_email'] );
        }
        if ( ! empty( $_GET['eme_ln'] ) ) {
            $person['lastname'] = eme_sanitize_request( $_GET['eme_ln'] );
        }
        if ( ! empty( $_GET['eme_fn'] ) ) {
            $person['firstname'] = eme_sanitize_request( $_GET['eme_fn'] );
        }
        $invite_readonly = "readonly='readonly'";
    }

    if ( $editing_booking_from_backend ) {
        $readonly = "readonly='readonly'";
        $disabled = "disabled='disabled'";
    } else {
        $readonly = '';
        $disabled = '';
    }

    if ( eme_is_empty_string( $format ) ) {
        if ( ! eme_is_empty_string( $event['event_registration_form_format'] ) ) {
            $format = $event['event_registration_form_format'];
        } elseif ( $event['event_properties']['event_registration_form_format_tpl'] > 0 ) {
            $format = eme_get_template_format( $event['event_properties']['event_registration_form_format_tpl'] );
        } else {
            $format = get_option( 'eme_registration_form_format' );
        }
    }

    // Dynamic data fields
    $eme_dyndatafields = [];
    if ( isset( $event['event_properties']['rsvp_dyndata'] ) ) {
        foreach ( $event['event_properties']['rsvp_dyndata'] as $dynfield ) {
            $eme_dyndatafields[] = $dynfield['field'];
        }
    }
    $add_dyndata = ! empty( $eme_dyndatafields );

    $selected_captcha = '';
    if ( $is_multibooking ) { // done in eme_replace_extra_multibooking_formfields_placeholders
        $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
    }
    if ( ! $is_multibooking ) {
        $add_captcha = !( is_user_logged_in() && $event['event_properties']['captcha_only_logged_out'] );
        $format = eme_add_missing_placeholders( $format, $add_captcha, $add_dyndata );
        if ($add_captcha && ! $eme_is_admin_request ) {
            $selected_captcha = $event['event_properties']['selected_captcha'];
        }
    }

    // Custom event attribute placeholders
    preg_match_all( '/#(ESC|URL)?_ATT\{.+?\}(\{.+?\})?/', $format, $results );
    foreach ( $results[0] as $resultKey => $result ) {
        $need_escape    = false;
        $need_urlencode = false;
        $orig_result    = $result;
        if ( strstr( $result, '#ESC' ) ) {
            $result      = str_replace( '#ESC', '#', $result );
            $need_escape = true;
        } elseif ( strstr( $result, '#URL' ) ) {
            $result         = str_replace( '#URL', '#', $result );
            $need_urlencode = true;
        }
        $replacement = '';
        $attRef      = substr( substr( $result, 0, strpos( $result, '}' ) ), 6 );
        if ( isset( $event['event_attributes'][ $attRef ] ) ) {
            $replacement = $event['event_attributes'][ $attRef ];
        }
        if ( trim( $replacement ) == '' && isset( $results[2][ $resultKey ] ) && $results[2][ $resultKey ] != '' ) {
            $replacement = substr( $results[2][ $resultKey ], 1, strlen( trim( $results[2][ $resultKey ] ) ) - 2 );
        }
        if ( $need_escape ) {
            $replacement = esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
        }
        if ( $need_urlencode ) {
            $replacement = rawurlencode( $replacement );
        }
        $format = str_replace( $orig_result, $replacement, $format );
    }

    $dynamic_price_class_basic = 'dynamicprice'; // js checks for this span regardless
    $dynamic_data_wanted       = ( strstr( $format, '#_DYNAMICDATA' ) && ! empty( $eme_dyndatafields ) ) || $event['event_properties']['dyndata_all_fields'];
    $dynamic_data_rendered     = false;

    // Seat options
    $min_allowed       = $event['event_properties']['min_allowed'];
    $max_allowed       = $event['event_properties']['max_allowed'];
    $waitinglist       = false;
    $waitinglist_seats = $event['event_properties']['waitinglist_seats'];
    $event_seats       = eme_get_total( $event['event_seats'] );

    if ( $allow_overbooking ) {
        $avail_seats = $event_seats;
    } else {
        $avail_seats = eme_get_available_seats( $event_id, 1 );
        if ( $waitinglist_seats > 0 && $avail_seats <= 0 && ! eme_is_multi( $event['event_seats'] ) ) {
            $waitinglist = true;
            $avail_seats = eme_get_available_seats( $event_id );
        }
    }

    $booked_seats_options = [];
    $max_allowed_is_multi = eme_is_multi( $max_allowed );
    $min_allowed_is_multi = eme_is_multi( $min_allowed );
    $multi_min_allowed = [];
    $multi_max_allowed = [];
    if ( $max_allowed_is_multi ) {
        $multi_max_allowed = eme_convert_multi2array( $max_allowed );
    }
    if ( $min_allowed_is_multi ) {
        $multi_min_allowed = eme_convert_multi2array( $min_allowed );
    }

    if ( eme_is_multi( $event['event_seats'] ) ) {
        $event_multiseats = eme_convert_multi2array( $event['event_seats'] );
        $multi_avail      = $allow_overbooking ? $event_multiseats : eme_get_available_multiseats( $event_id );
        foreach ( $multi_avail as $key => $avail_seats ) {
            $booked_seats_options[ $key ] = [];
            $real_max_allowed             = $max_allowed_is_multi ? (int) $multi_max_allowed[ $key ] : (int) $max_allowed;
            if ( $event_multiseats[ $key ] > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
                $real_max_allowed = $avail_seats;
            }
            if ( ! empty( $location ) && ! empty( $location['location_properties']['max_capacity'] ) ) {
                $used_capacity          = eme_get_event_location_used_capacity( $event );
                $free_location_capacity = max( 0, $location['location_properties']['max_capacity'] - $used_capacity );
                if ( $real_max_allowed > $free_location_capacity ) {
                    $real_max_allowed = $free_location_capacity;
                }
            }
            if ( $event_multiseats[ $key ] == 0 && $real_max_allowed == 0 ) {
                $real_max_allowed = 10;
            }
            if ( $editing_booking_from_backend && isset( $booking_seats_mp[ $key ] ) ) {
                $real_max_allowed += intval( $booking_seats_mp[ $key ] );
                if ( $max_allowed_is_multi && $real_max_allowed > intval( $multi_max_allowed[ $key ] ) && intval( $multi_max_allowed[ $key ] ) > 0 ) {
                    $real_max_allowed = intval( $multi_max_allowed[ $key ] );
                } elseif ( $real_max_allowed > $max_allowed && $max_allowed > 0 ) {
                    $real_max_allowed = $max_allowed;
                }
            }
            $real_min_allowed = $min_allowed_is_multi ? $multi_min_allowed[ $key ] : 0;
            for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
                $booked_seats_options[ $key ][ $i ] = $i;
            }
        }
    } elseif ( eme_is_multi( $event['price'] ) ) {
        foreach ( eme_convert_multi2array( $event['price'] ) as $key => $value ) {
            $booked_seats_options[ $key ] = [];
            $real_max_allowed             = $max_allowed_is_multi ? (int) $multi_max_allowed[ $key ] : (int) $max_allowed;
            if ( $event_seats > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
                $real_max_allowed = $avail_seats;
            }
            if ( ! empty( $location ) && ! empty( $location['location_properties']['max_capacity'] ) ) {
                $used_capacity          = eme_get_event_location_used_capacity( $event );
                $free_location_capacity = max( 0, $location['location_properties']['max_capacity'] - $used_capacity );
                if ( $real_max_allowed > $free_location_capacity ) {
                    $real_max_allowed = $free_location_capacity;
                }
            }
            if ( $event_seats == 0 && $real_max_allowed == 0 ) {
                $real_max_allowed = 10;
            }
            if ( $editing_booking_from_backend && isset( $booking_seats_mp[ $key ] ) ) {
                $real_max_allowed += $booking_seats_mp[ $key ];
                if ( $max_allowed_is_multi && $real_max_allowed > intval( $multi_max_allowed[ $key ] ) && intval( $multi_max_allowed[ $key ] ) > 0 ) {
                    $real_max_allowed = intval( $multi_max_allowed[ $key ] );
                } elseif ( $real_max_allowed > $max_allowed && $max_allowed > 0 ) {
                    $real_max_allowed = $max_allowed;
                }
            }
            $real_min_allowed = $min_allowed_is_multi ? intval( $multi_min_allowed[ $key ] ) : 0;
            for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
                $booked_seats_options[ $key ][ $i ] = $i;
            }
        }
    } else {
        $real_max_allowed = $max_allowed_is_multi ? $multi_max_allowed[0] : $max_allowed;
        if ( $event_seats > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
            $real_max_allowed = $avail_seats;
        }
        if ( ! empty( $location ) && ! empty( $location['location_properties']['max_capacity'] ) ) {
            $used_capacity          = eme_get_event_location_used_capacity( $event );
            $free_location_capacity = max( 0, $location['location_properties']['max_capacity'] - $used_capacity );
            if ( $real_max_allowed > $free_location_capacity ) {
                $real_max_allowed = $free_location_capacity;
            }
        }
        if ( $event_seats == 0 && $real_max_allowed == 0 ) {
            $real_max_allowed = 10;
        }
        if ( $editing_booking_from_backend && $real_max_allowed < $bookedSeats ) {
            $real_max_allowed += $bookedSeats;
            if ( $max_allowed_is_multi && $real_max_allowed > $multi_max_allowed[0] ) {
                $real_max_allowed = $multi_max_allowed[0];
            } elseif ( $real_max_allowed > $max_allowed ) {
                $real_max_allowed = $max_allowed;
            }
        }
        $real_min_allowed = $min_allowed_is_multi ? $multi_min_allowed[0] : $min_allowed;
        for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
            $booked_seats_options[ $i ] = $i;
        }
    }

    $discount_fields_count = 0;

    // Build $ctx
    $ctx = [
        'person'          => $person,
        'fn_prefix'       => '',
        'form_id'         => $form_id,
        'eme_is_admin'    => $eme_is_admin_request,
        'readonly'        => $readonly,
        'disabled'        => $disabled,
        'dfc_basic'       => 'nodynamicupdates', // updated per-iteration below via closure
        'extra_css'       => '',
        'allow_clear'     => $allow_clear,
        'invite_readonly' => $invite_readonly,
        'selected_captcha'=> $selected_captcha,
        'captcha_only_logged_out'=> $event['event_properties']['captcha_only_logged_out'],
        'required'        => false,
        'required_att'    => '',
    ];

    $handlers = eme_get_person_formfield_handler_definitions();

    // ── rsvp-specific handlers ───────────────────────────────────────────────

    $handlers['/#_COMMENT(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $is_multibooking, $bookerComment ) {
        if ( $is_multibooking ) {
            return '';
        }
        $placeholder_text = isset( $matches[1] )
            ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
            : esc_html__( 'Comment', 'events-made-easy' );
        $dfc = "class='{$ctx['dfc_basic']}'";
        return "<textarea {$ctx['required_att']} name='eme_rsvpcomment' $dfc placeholder='$placeholder_text' >$bookerComment</textarea>";
    };

    $handlers['/#_SEATS$|#_SPACES$/'] = function( $result, $matches, $ctx ) use ( $event_id, $is_multibooking, $editing_booking_from_backend, $bookedSeats, $booked_seats_options, $waitinglist, $new_booking_in_frontend, $min_allowed_is_multi, $min_allowed, $max_allowed, $dynamic_price_class_basic ) {
        $var_prefix  = "bookings[$event_id][";
        $var_postfix = ']';
        $fieldname   = "{$var_prefix}bookedSeats{$var_postfix}";
        $entered_val = ( $editing_booking_from_backend && isset( $bookedSeats ) ) ? $bookedSeats : 0;
        $dfc_basic   = $ctx['dfc_basic'];
        if ( $event['event_properties']['take_attendance'] ?? false ) {
            if ( ! $min_allowed_is_multi && $min_allowed > 0 ) {
                $replacement = "<input type='hidden' name='$fieldname' value='1'>";
            } else {
                if ( $new_booking_in_frontend ) {
                    $entered_val = 1;
                }
                $replacement = eme_ui_checkbox_binary( $entered_val, $fieldname, '', 0, '', "class='eme-attendance-field $dynamic_price_class_basic $dfc_basic eme_snapselect'" );
            }
        } else {
            if ( ! $min_allowed_is_multi && $min_allowed > 0 && $min_allowed == $max_allowed ) {
                $replacement = "<input type='hidden' name='$fieldname' value='$min_allowed'>";
            } else {
                $replacement = eme_ui_select( $entered_val, $fieldname, $booked_seats_options, '', $ctx['required'], "$dynamic_price_class_basic $dfc_basic eme_snapselect" );
            }
            if ( $waitinglist && ! $editing_booking_from_backend ) {
                $replacement .= "<span id='eme_waitinglist'><br>" . eme_translate( get_option( 'eme_rsvp_on_waiting_list_string' ) ) . '</span>';
            }
        }
        return [ 'html' => $replacement ];
    };

    $handlers['/#_(SEATS|SPACES)\{(\d+)\}/'] = function( $result, $matches, $ctx ) use ( $event_id, $event, $editing_booking_from_backend, $booking_seats_mp, $booked_seats_options, $min_allowed_is_multi, $multi_min_allowed, $multi_max_allowed, $dynamic_price_class_basic ) {
        $field_id    = intval( $matches[2] );
        $fieldname   = "bookings[$event_id][bookedSeats{$field_id}]";
        $entered_val = ( $editing_booking_from_backend && $field_id > 0 && isset( $booking_seats_mp[ $field_id - 1 ] ) )
            ? intval( $booking_seats_mp[ $field_id - 1 ] ) : 0;
        $dfc_basic   = $ctx['dfc_basic'];
        if ( ! eme_is_multi( $event['price'] ) ) {
            $error_msg = __( 'By using #_SEATS{xx}, you are using multiple seat categories in your RSVP template, but you have not defined a price for each category in your event RSVP settings. Please correct the event RSVP settings.', 'events-made-easy' );
            return [ 'html' => '', 'early_return' => "<div class='eme-message-error eme-rsvp-message-error'>$error_msg</div>" ];
        } elseif ( $event['event_properties']['take_attendance'] ) {
            if ( $min_allowed_is_multi && $multi_min_allowed[ $field_id - 1 ] > 0 ) {
                $replacement = "<input type='hidden' name='$fieldname' value='1'>";
            } else {
                $replacement = eme_ui_select_binary( $entered_val, $fieldname, 0, "$dynamic_price_class_basic $dfc_basic eme_snapselect" );
            }
        } else {
            if ( $min_allowed_is_multi && $multi_min_allowed[ $field_id - 1 ] > 0 && $multi_min_allowed[ $field_id - 1 ] == $multi_max_allowed[ $field_id - 1 ] ) {
                $replacement = "<input type='hidden' name='$fieldname' value='{$multi_min_allowed[$field_id-1]}'>";
            } else {
                $replacement = eme_ui_select( $entered_val, $fieldname, $booked_seats_options[ $field_id - 1 ], '', $ctx['required'], "$dynamic_price_class_basic $dfc_basic eme_snapselect" );
            }
        }
        return [ 'html' => $replacement ];
    };

    $handlers['/#_PASSWORD(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $event, $eme_is_admin_request, $is_multibooking ) {
        if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request && ! $is_multibooking ) {
            $placeholder_text = esc_html__( 'Password', 'events-made-easy' );
            $dfc = "class='{$ctx['dfc_basic']}'";
            $replacement = "<input required='required' type='text' class='eme_passwordfield' autocomplete='off' name='rsvp_password' value='' $dfc placeholder='$placeholder_text'>";
            return [ 'html' => $replacement, 'set_required' => true ];
        }
        return '';
    };

    $handlers['/#_DYNAMICPRICE$/'] = function( $result, $matches, $ctx ) use ( $is_multibooking ) {
        return $is_multibooking ? '' : "<span id='eme_calc_bookingprice'></span>";
    };

    $handlers['/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/'] = function( $result, $matches, $ctx ) use ( $is_multibooking ) {
        return $is_multibooking ? '' : "<span id='eme_calc_bookingprice_detail'></span>";
    };

    $handlers['/#_DYNAMICDATA$/'] = function( $result, $matches, $ctx ) use ( $event, &$dynamic_data_rendered ) {
        if ( ! $dynamic_data_rendered && ! empty( $event['event_properties']['rsvp_dyndata'] ) ) {
            $dynamic_data_rendered = true;
            return "<div id='eme_dyndata'></div>";
        }
        return '';
    };

    $handlers['/#_DISCOUNT(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $event, $event_id, $is_multibooking, $eme_is_admin_request, $booking, $dynamic_price_class_basic, &$discount_fields_count ) {
        if ( ! $event['event_properties']['rsvp_discount'] && ! $event['event_properties']['rsvp_discountgroup'] ) {
            return [ 'html' => '', 'not_found' => true ];
        }
        ++$discount_fields_count;
        if ( ! $eme_is_admin_request ) {
            $var_prefix     = "bookings[$event_id][";
            $postfield_name = "{$var_prefix}DISCOUNT{$discount_fields_count}]";
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Discount code', 'events-made-easy' );
            return "<input class='$dynamic_price_class_basic' type='text' name='$postfield_name' value='' {$ctx['required_att']} placeholder='$placeholder_text'>";
        } elseif ( $discount_fields_count == 1 ) {
            if ( $booking['discount'] ) {
                $replacement = "<input class='$dynamic_price_class_basic' type='text' name='DISCOUNT' value='{$booking['discount']}'><br>"
                    . sprintf( __( 'Enter a new fixed discount value if wanted, or leave as is to keep the calculated value %s based on the following applied discounts:', 'events-made-easy' ), eme_localized_price( $booking['discount'], $event['currency'] ) );
                $replacement .= '<ul>';
            } else {
                $replacement = "<input class='$dynamic_price_class_basic' type='text' name='DISCOUNT' value=''><br>"
                    . __( 'Enter a fixed discount value if wanted', 'events-made-easy' );
            }
            if ( $booking['dgroupid'] ) {
                $dgroup       = eme_get_discountgroup( $booking['dgroupid'] );
                $replacement .= $dgroup && isset( $dgroup['name'] )
                    ? '<li>' . sprintf( __( 'Discountgroup %s', 'events-made-easy' ), esc_html( $dgroup['name'] ) ) . '</li>'
                    : '<li>' . sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $booking['dgroupid'] ) . '</li>';
            }
            if ( ! empty( $booking['discountids'] ) ) {
                $applied_discountids = eme_is_serialized( $booking['discountids'] )
                    ? array_keys( eme_json_decode_safe( $booking['discountids'] ) )
                    : explode( ',', $booking['discountids'] );
                foreach ( $applied_discountids as $discount_id ) {
                    $discount     = eme_get_discount( $discount_id );
                    $replacement .= $discount && isset( $discount['name'] )
                        ? '<li>' . esc_html( $discount['name'] ) . '</li>'
                        : '<li>' . sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id ) . '</li>';
                }
            }
            if ( $booking['discount'] ) {
                $replacement .= '</ul>';
            }
            $replacement .= '<br>' . __( 'Only one discount field can be used in the admin backend, the others are not rendered', 'events-made-easy' ) . '<br>';
            return $replacement;
        }
        return '';
    };

    $handlers['/#_FIELD\{(.+)\}/'] = function( $result, $matches, $ctx ) use ( $event_id, $is_multibooking, $editing_booking_from_backend, $booking, $person, $dynamic_price_class_basic ) {
        $dfc_basic = $ctx['dfc_basic'];
        $formfield = eme_get_formfield( $matches[1] );
        if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
            if ( ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) && $is_multibooking ) {
                return '';
            }
            $field_id    = $formfield['field_id'];
            $var_prefix  = $is_multibooking ? "bookings[$event_id][" : '';
            $var_postfix = $is_multibooking ? ']' : '';
            $fieldname   = "{$var_prefix}FIELD{$field_id}{$var_postfix}";
            $entered_val = '';
            $field_readonly = false;
            if ( $editing_booking_from_backend ) {
                if ( $formfield['field_purpose'] == 'people' ) {
                    $answers        = eme_get_person_answers( $booking['person_id'] );
                    $field_readonly = true;
                } else {
                    $answers = eme_get_nodyndata_booking_answers( $booking['booking_id'] );
                }
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) { $entered_val = $answer['answer']; break; }
                }
                $files1 = eme_get_uploaded_files( $booking['person_id'], 'people' );
                $files2 = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
                $files  = array_merge( $files1, $files2 );
                if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id ) { $entered_files[] = $file; }
                    }
                    $entered_val = $entered_files;
                }
            } elseif ( $formfield['field_purpose'] == 'people' && is_user_logged_in() && ! empty( $person['person_id'] ) ) {
                $answers = eme_get_person_answers( $person['person_id'] );
                $files   = eme_get_uploaded_files( $booking['person_id'] ?? 0, 'people' );
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) { $entered_val = $answer['answer']; break; }
                }
                if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id ) { $entered_files[] = $file; }
                    }
                    $entered_val = $entered_files;
                }
            }
            $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
            $class    = $formfield['extra_charge'] ? "$dynamic_price_class_basic $dfc_basic" : $dfc_basic;
            return [ 'html' => eme_get_formfield_html( $formfield, $fieldname, $entered_val, $required, $class, $field_readonly ), 'set_required' => $required ];
        }
        return [ 'html' => '', 'not_found' => true ];
    };

    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) use ( $is_multibooking, $editing_booking_from_backend ) {
        if ( $is_multibooking ) {
            return '';
        }
        if ( $editing_booking_from_backend ) {
            $label = __( 'Update booking', 'events-made-easy' );
        } elseif ( isset( $matches[1] ) ) {
            $label = substr( $matches[1], 1, -1 );
        } else {
            $label = get_option( 'eme_rsvp_addbooking_submit_string' );
        }
        return "<img id='rsvp_add_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    // ── Run dispatch, updating dfc_basic per-iteration via a wrapper ─────────
    // Because dfc_basic changes per iteration (based on dyndata), we run a
    // custom loop here instead of eme_run_formfield_dispatch.
 
    // pre-validate required placeholders
    $has_lastname = $is_multibooking || preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_email    = $is_multibooking || preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    $has_password = $eme_is_admin_request || $is_multibooking || empty( $event['event_properties']['rsvp_password'] ) || preg_match( '/#(REQ|ESC)?_PASSWORD/', $format );

    if ( eme_is_multi( $event['price'] ) ) {
        $has_seats = preg_match( '/#(REQ|ESC)?_(SEATS|SPACES)\{(\d+)\}/', $format );
    } else {
        $has_seats = preg_match( '/#(REQ|ESC)?_(SEATS|SPACES)/', $format );
    }

    if ( ! $has_lastname || ! $has_email || ! $has_password || ! $has_seats ) {
        $res = '';
        if ( ! $has_lastname || ! $has_email || ! $has_seats ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_EMAIL, #_SEATS and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        }
        if ( eme_is_multi( $event['price'] ) ) {
            $res .= __( "Since this is a multiprice event, make sure you changed the setting 'Booking Form' for the event to include #_SEATS{xx} placeholders for each price.", 'events-made-easy' ) . '<br>';
        }
        if ( ! $has_password && ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request ) {
            $res .= __( 'Check that the placeholder #_PASSWORD is present in the form.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;

    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = false;
        $required           = false;
        $required_att       = '';
        $replacement        = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = true;
            $required_att = "required='required'";
        }
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }

        // per-iteration dynamic field class
        if ( $dynamic_data_wanted && ! $is_multibooking && ( in_array( $result, $eme_dyndatafields ) || $event['event_properties']['dyndata_all_fields'] ) ) {
            $dfc_basic = 'dynamicupdates';
        } else {
            $dfc_basic = 'nodynamicupdates';
        }

        // multibooking field prefix
        $var_prefix  = $is_multibooking ? "bookings[$event_id][" : '';
        $var_postfix = $is_multibooking ? ']' : '';

        $ctx['dfc_basic']    = $dfc_basic;
        $ctx['required']     = $required;
        $ctx['required_att'] = $required_att;

        foreach ( $handlers as $pattern => $handler ) {
            if ( preg_match( $pattern, $result, $matches ) ) {
                $found = true;
                $ret   = $handler( $result, $matches, $ctx );
                if ( is_array( $ret ) ) {
                    if ( ! empty( $ret['early_return'] ) ) { return $ret['early_return']; }
                    $replacement = $ret['html'] ?? '';
                    if ( ! empty( $ret['not_found'] ) )    { $found = false; }
                    if ( ! empty( $ret['set_required'] ) ) { $required = true; } // to indicate the required flag in the found-section
                } else {
                    $replacement = (string) $ret;
                }
                break;
            }
        }

        if ( $found ) {
            if ( $required ) {
                $req_str = eme_translate( get_option( 'eme_form_required_field_string' ) );
                if ( ! empty( $req_str ) ) {
                    $replacement .= "<div class='eme-required-field'>$req_str</div>";
                }
            }
            if ( is_null( $replacement ) ) { $replacement = ''; }
            $format        = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    $format = eme_replace_event_placeholders( $format, $event );

    return $format;
}

function eme_replace_membership_formfields_placeholders( $form_id, $membership, $member, $format ) {
    $eme_is_admin_request = eme_is_admin_request();
    $membership_id        = $membership['membership_id'];

    $registration_wp_users_only = $membership['properties']['registration_wp_users_only'];
    if ( ! $eme_is_admin_request && $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }
    if ( eme_is_empty_string( $format ) ) {
        return;
    }

    $has_lastname  = preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_firstname = preg_match( '/#(REQ|ESC)?_FIRSTNAME/', $format );
    $has_email     = preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    if ( ! $has_lastname || ! $has_firstname || ! $has_email ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $allow_clear    = false;
    $editing_member = false;
    $current_userid = get_current_user_id();
    if ( ! empty( $current_userid ) ) {
        if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) && ! empty( $member['member_id'] ) && ! empty( $member['person_id'] ) ) {
            $editing_member = true;
        }
        if ( ! $editing_member && ( ! $registration_wp_users_only || current_user_can( get_option( 'eme_cap_edit_members' ) ) ) ) {
            $allow_clear = true;
        }
    }

    $person = eme_new_person();
    if ( $editing_member ) {
        $person = eme_get_person( $member['person_id'] );
    } elseif ( ! empty( $current_userid ) && ! $eme_is_admin_request ) {
        $fetched = eme_get_person_by_wp_id( $current_userid );
        $person  = $fetched ?: eme_fake_person_by_wp_id( $current_userid );
    }
    $person = eme_esc_person_for_form( $person );

    $readonly = '';
    $disabled = '';
    if ( $editing_member ) {
        $readonly = "readonly='readonly' style='width: 100%;'";
        $disabled = "disabled='disabled'";
    }

    $eme_dyndatafields = [];
    if ( isset( $membership['properties']['dyndata'] ) ) {
        foreach ( $membership['properties']['dyndata'] as $dynfield ) {
            $eme_dyndatafields[] = $dynfield['field'];
        }
    }
    $add_dyndata = ! empty( $eme_dyndatafields );

    if ( $membership['properties']['family_membership'] && ! preg_match( '/#_FAMILYCOUNT/', $format ) ) {
        $text   = '#_FAMILYCOUNT';
        $format = preg_match( '/#_SUBMIT/', $format )
            ? preg_replace( '/#_SUBMIT/', "$text<br>#_SUBMIT", $format )
            : $format . $text;
    }
    if ( $membership['properties']['family_membership'] && ! preg_match( '/#_FAMILYMEMBERS/', $format ) ) {
        $text   = '#_FAMILYMEMBERS';
        $format = preg_match( '/#_SUBMIT/', $format )
            ? preg_replace( '/#_SUBMIT/', "$text<br>#_SUBMIT", $format )
            : $format . $text;
    }

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && $membership['properties']['captcha_only_logged_out'] ) ;
    $format = eme_add_missing_placeholders( $format, $add_captcha, $add_dyndata );
    if ($add_captcha && ! $eme_is_admin_request ) {
        $selected_captcha = $membership['properties']['selected_captcha'];
    }

    $dynamic_price_class_basic = str_contains( $format, '#_DYNAMICPRICE' ) ? 'dynamicprice' : '';
    $dynamic_data_wanted       = ( strstr( $format, '#_DYNAMICDATA' ) && ! empty( $eme_dyndatafields ) ) || $membership['properties']['dyndata_all_fields'];
    $dynamic_data_rendered     = false;
    $personal_info_class       = 'personal_info';
    $discount_fields_count     = 0;

    $ctx = [
        'person'           => $person,
        'fn_prefix'        => '',
        'form_id'          => $form_id,
        'eme_is_admin'     => $eme_is_admin_request,
        'readonly'         => $readonly,
        'disabled'         => $disabled,
        'dfc_basic'        => 'nodynamicupdates',
        'extra_css'        => $personal_info_class,
        'allow_clear'      => $allow_clear,
        'invite_readonly'  => '',
        'selected_captcha' => $selected_captcha,
        'captcha_only_logged_out' => $membership['properties']['captcha_only_logged_out'],
        'required'         => false,
        'required_att'     => '',
    ];

    // Membership REQ handling: #REQ only applies outside admin
    // Override #_NAME and #_EMAIL handlers to enforce required only outside admin
    $handlers = eme_get_person_formfield_handler_definitions();

    $handlers['/#_DYNAMICPRICE$/'] = function( $result, $matches, $ctx ) {
        return "<span id='eme_calc_memberprice'></span>";
    };
    $handlers['/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/'] = function( $result, $matches, $ctx ) {
        return "<span id='eme_calc_memberprice_detail'></span>";
    };
    $handlers['/#_DYNAMICDATA$/'] = function( $result, $matches, $ctx ) use ( $membership, &$dynamic_data_rendered ) {
        if ( ! $dynamic_data_rendered && ! empty( $membership['properties']['dyndata'] ) ) {
            $dynamic_data_rendered = true;
            return "<div id='eme_dyndata'></div>";
        }
        return '';
    };
    $handlers['/#_FAMILYCOUNT/'] = function( $result, $matches, $ctx ) use ( $membership, $eme_is_admin_request, &$familycount_found ) {
        if ( ! $eme_is_admin_request ) {
            if ( empty( $familycount_found ) ) {
                $familycount_found = true;
                $range_arr = [];
                for ( $i = 0; $i <= $membership['properties']['family_maxmembers']; $i++ ) {
                    $range_arr[ $i ] = $i;
                }
                return eme_ui_select( 1, 'familycount', $range_arr );
            }
            return '';
        }
        return __( "In the backend you can't add or edit family member info, use the frontend form for that.", 'events-made-easy' );
    };
    $handlers['/#_FAMILYMEMBERS/'] = function( $result, $matches, $ctx ) use ( $eme_is_admin_request, &$familymembers_found ) {
        if ( ! $eme_is_admin_request && empty( $familymembers_found ) ) {
            $familymembers_found = true;
            return "<div id='eme_dyndata_family'></div>";
        }
        return '';
    };
    $handlers['/#_DISCOUNT(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $membership, $membership_id, $member, $eme_is_admin_request, $dynamic_price_class_basic, &$discount_fields_count ) {
        if ( ! $membership['properties']['discount'] && ! $membership['properties']['discountgroup'] ) {
            return [ 'html' => '', 'not_found' => true ];
        }
        ++$discount_fields_count;
        if ( ! $eme_is_admin_request ) {
            $postfield_name   = "members[$membership_id][DISCOUNT{$discount_fields_count}]";
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Discount code', 'events-made-easy' );
            return "<input class='$dynamic_price_class_basic' type='text' name='$postfield_name' value='' {$ctx['required_att']} placeholder='$placeholder_text'>";
        } elseif ( $discount_fields_count == 1 ) {
            if ( $member['discount'] ) {
                $replacement = "<input class='$dynamic_price_class_basic' type='text' name='DISCOUNT' value='{$member['discount']}'><br>"
                    . sprintf( __( 'Enter a new fixed discount value if wanted, or leave as is to keep the calculated value %s based on the following applied discounts:', 'events-made-easy' ), eme_localized_price( $member['discount'], $membership['properties']['currency'] ) );
                $replacement .= '<ul>';
            } else {
                $replacement = "<input class='$dynamic_price_class_basic' type='text' name='DISCOUNT' value=''><br>"
                    . __( 'Enter a fixed discount value if wanted', 'events-made-easy' );
            }
            if ( $member['dgroupid'] ) {
                $dgroup       = eme_get_discountgroup( $member['dgroupid'] );
                $replacement .= $dgroup && isset( $dgroup['name'] )
                    ? '<li>' . sprintf( __( 'Discountgroup %s', 'events-made-easy' ), esc_html( $dgroup['name'] ) ) . '</li>'
                    : '<li>' . sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $member['dgroupid'] ) . '</li>';
            }
            if ( ! empty( $member['discountids'] ) ) {
                $applied_discountids = eme_is_serialized( $member['discountids'] )
                    ? array_keys( eme_json_decode_safe( $member['discountids'] ) )
                    : explode( ',', $member['discountids'] );
                foreach ( $applied_discountids as $discount_id ) {
                    $discount     = eme_get_discount( $discount_id );
                    $replacement .= $discount && isset( $discount['name'] )
                        ? '<li>' . esc_html( $discount['name'] ) . '</li>'
                        : '<li>' . sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id ) . '</li>';
                }
            }
            if ( $member['discount'] ) {
                $replacement .= '</ul>';
            }
            $replacement .= '<br>' . __( 'Only one discount field can be used in the admin backend, the others are not rendered', 'events-made-easy' ) . '<br>';
            return $replacement;
        }
        return '';
    };
    $handlers['/#_FIELD\{(.+)\}/'] = function( $result, $matches, $ctx ) use ( $member, $person, $editing_member, $eme_is_admin_request, $dynamic_price_class_basic, $personal_info_class ) {
        $dfc_basic = $ctx['dfc_basic'];
        $formfield = eme_get_formfield( $matches[1] );
        if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'members', 'people' ] ) ) {
            $field_id    = $formfield['field_id'];
            $fieldname   = 'FIELD' . $field_id;
            $entered_val = '';
            $field_readonly = false;
            if ( $editing_member ) {
                if ( $formfield['field_purpose'] == 'people' ) {
                    $answers        = eme_get_person_answers( $member['person_id'] );
                    $field_readonly = true;
                } else {
                    $answers = eme_get_nodyndata_member_answers( $member['member_id'] );
                }
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) { $entered_val = $answer['answer']; break; }
                }
            } elseif ( $formfield['field_purpose'] == 'people' && is_user_logged_in() && ! empty( $person['person_id'] ) ) {
                $answers = eme_get_person_answers( $person['person_id'] );
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) { $entered_val = $answer['answer']; break; }
                }
            }
            $required = ($formfield['field_required'] || $ctx['required']) && ( ! $eme_is_admin_request || $formfield['field_purpose'] != 'people' );
            $class    = $dfc_basic;
            if ( $formfield['field_purpose'] == 'people' ) { $class .= " $personal_info_class"; }
            if ( $formfield['extra_charge'] )               { $class .= " $dynamic_price_class_basic"; }
            return [ 'html' => eme_get_formfield_html( $formfield, $fieldname, $entered_val, $required, $class, $field_readonly, 0, $editing_member ), 'set_required' => $required ];
        }
        return [ 'html' => '', 'not_found' => true ];
    };
    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) use ( $editing_member ) {
        if ( $editing_member ) {
            $label = __( 'Update member', 'events-made-easy' );
        } elseif ( isset( $matches[1] ) ) {
            $label = substr( $matches[1], 1, -1 );
        } else {
            $label = __( 'Become member', 'events-made-easy' );
        }
        return "<img id='member_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    // per-iteration dfc_basic update (same pattern as rsvp)
    $familycount_found   = false;
    $familymembers_found = false;

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = false;
        $required           = false;
        $required_att       = '';
        $replacement        = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result = str_replace( '#REQ', '#', $result );
            if ( ! $eme_is_admin_request ) {
                $required     = true;
                $required_att = "required='required'";
            }
        }

        $dfc_basic = ( $dynamic_data_wanted && ( in_array( $result, $eme_dyndatafields ) || $membership['properties']['dyndata_all_fields'] ) )
            ? 'dynamicupdates' : 'nodynamicupdates';

        $ctx['dfc_basic']    = $dfc_basic;
        $ctx['required']     = $required;
        $ctx['required_att'] = $required_att;

        foreach ( $handlers as $pattern => $handler ) {
            if ( preg_match( $pattern, $result, $matches ) ) {
                $found = true;
                $ret   = $handler( $result, $matches, $ctx );
                if ( is_array( $ret ) ) {
                    if ( ! empty( $ret['early_return'] ) )   { return $ret['early_return']; }
                    $replacement = $ret['html'] ?? '';
                    if ( ! empty( $ret['not_found'] ) )      { $found = false; }
                    if ( ! empty( $ret['set_required'] ) ) { $required = true; }
                } else {
                    $replacement = (string) $ret;
                }
                break;
            }
        }

        if ( $found ) {
            if ( $required ) {
                $req_str = eme_translate( get_option( 'eme_form_required_field_string' ) );
                if ( ! empty( $req_str ) ) { $replacement .= "<div class='eme-required-field'>$req_str</div>"; }
            }
            if ( is_null( $replacement ) ) { $replacement = ''; }
            $format        = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    $format = eme_replace_membership_placeholders( $format, $membership );

    return $format;
}

function eme_replace_task_signupformfields_placeholders( $form_id, $format ) {
    $eme_is_admin_request = eme_is_admin_request();
    $readonly             = is_user_logged_in() ? "readonly='readonly'" : '';

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ));
    $format = eme_add_missing_placeholders( $format, $add_captcha );
    $configured_captchas = eme_get_configured_captchas();
    if ($add_captcha && ! empty( $configured_captchas ) && ! $eme_is_admin_request ) {
        $selected_captcha = array_key_first( $configured_captchas );
    }

    $person = eme_new_person();
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $fetched      = eme_get_person_by_wp_id( $current_user->ID );
        $person       = $fetched ?: eme_fake_person_by_wp_id( $current_user->ID );
    }
    $person = eme_esc_person_for_form( $person );

    $has_lastname = preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_email    = preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    if ( ! $has_lastname || ! $has_email ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_EMAIL placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $ctx = [
        'person'           => $person,
        'fn_prefix'        => 'task_',
        'form_id'          => $form_id,
        'eme_is_admin'     => $eme_is_admin_request,
        'readonly'         => $readonly,
        'disabled'         => '',
        'dfc_basic'        => '',
        'extra_css'        => '',
        'allow_clear'      => false,
        'invite_readonly'  => '',
        'selected_captcha' => $selected_captcha,
        'required'         => false,
        'required_att'     => '',
    ];

    $handlers = eme_get_person_formfield_handler_definitions();

    $handlers['/#_COMMENT(\{.+?\})?$/'] = function( $result, $matches, $ctx ) {
        $placeholder_text = isset( $matches[1] )
            ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
            : esc_html__( 'Comment', 'events-made-easy' );
        return "<textarea name='task_comment' id='task_comment' placeholder='$placeholder_text' ></textarea>";
    };
    $handlers['/#_FIELD\{(.+)\}/'] = function( $result, $matches, $ctx ) {
        $formfield = eme_get_formfield( $matches[1] );
        if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'tasksignup', 'people' ] ) ) {
            $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
            $fieldname = 'FIELD' . $formfield['field_id'];
            return [ 'html' => eme_get_formfield_html( $formfield, $fieldname, '', $required ), 'set_required' => $required ];
        }
        return [ 'html' => '', 'not_found' => true ];
    };
    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) {
        $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : __( 'Subscribe', 'events-made-easy' );
        return "<img id='task_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    $format = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    $format = eme_translate( $format );

    return $format;
}

function eme_replace_extra_multibooking_formfields_placeholders( $form_id, $format, $event ) {
    $eme_is_admin_request      = eme_is_admin_request();
    $dynamic_price_class_basic = 'dynamicprice';

    $allow_clear = false;
    $person      = eme_new_person();
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $fetched      = eme_get_person_by_wp_id( $current_user->ID );
        $person       = $fetched ?: eme_fake_person_by_wp_id( $current_user->ID );
        if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ) {
            $allow_clear = true;
        }
    }
    $person = eme_esc_person_for_form( $person );

    $selected_captcha = '';
    if ( ! ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) ) {
        $configured_captchas = eme_get_configured_captchas();
        if ( ! empty( $configured_captchas ) && ! $eme_is_admin_request ) {
            $selected_captcha = array_key_first( $configured_captchas );
        }
    }

    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        $format = ! empty( $selected_captcha )
            ? preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format )
            : preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
    }

    $ctx = [
        'person'           => $person,
        'fn_prefix'        => '',
        'form_id'          => $form_id,
        'eme_is_admin'     => $eme_is_admin_request,
        'readonly'         => '',
        'disabled'         => '',
        'dfc_basic'        => '',
        'extra_css'        => '',
        'allow_clear'      => $allow_clear,
        'invite_readonly'  => '',
        'selected_captcha' => $selected_captcha,
        'required'         => false,
        'required_att'     => '',
    ];

    $handlers = eme_get_person_formfield_handler_definitions();

    $handlers['/#_COMMENT(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $person ) {
        $placeholder_text = isset( $matches[1] )
            ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
            : esc_html__( 'Comment', 'events-made-easy' );
        return "<textarea {$ctx['required_att']} name='eme_rsvpcomment' placeholder='$placeholder_text' ></textarea>";
    };
    $handlers['/#_PASSWORD(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $event, $eme_is_admin_request ) {
        if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request ) {
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Password', 'events-made-easy' );
            return [ 'html' => "<input required='required' type='text' name='rsvp_password' value='' class='eme_passwordfield' autocomplete='off' placeholder='$placeholder_text'>", 'set_required' => true ];
        }
        return '';
    };
    $handlers['/#_DYNAMICPRICE$/'] = function( $result, $matches, $ctx ) {
        return "<span id='eme_calc_bookingprice'></span>";
    };
    $handlers['/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/'] = function( $result, $matches, $ctx ) {
        return "<span id='eme_calc_bookingprice_detail'></span>";
    };
    $handlers['/#_FIELD\{(.+)\}/'] = function( $result, $matches, $ctx ) use ( $dynamic_price_class_basic ) {
        $formfield = eme_get_formfield( $matches[1] );
        if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
            $field_id  = $formfield['field_id'];
            $fieldname = 'FIELD' . $field_id;
            $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
            $class     = $formfield['extra_charge'] ? "$dynamic_price_class_basic" : '';
            return [ 'html' => eme_get_formfield_html( $formfield, $fieldname, '', $required, $class ), 'set_required' => $required ];
        }
        return [ 'html' => '', 'not_found' => true ];
    };
    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) {
        $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : get_option( 'eme_rsvp_addbooking_submit_string' );
        return "<img id='rsvp_add_loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    $format = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    $format = eme_replace_event_placeholders( $format, $event );
    $format = eme_translate( $format );
    return $format;
}

function eme_replace_subscribeform_placeholders( $format, $unsubscribe = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ));
    $format = eme_add_missing_placeholders( $format, $add_captcha );
    $configured_captchas = eme_get_configured_captchas();
    if ($add_captcha && ! empty( $configured_captchas ) && ! $eme_is_admin_request ) {
        $selected_captcha = array_key_first( $configured_captchas );
    }

    $person   = eme_new_person();
    $readonly = '';
    if ( is_user_logged_in() ) {
        $readonly     = "readonly='readonly'";
        $current_user = wp_get_current_user();
        $fetched      = eme_get_person_by_wp_id( $current_user->ID );
        $person       = $fetched ?: eme_fake_person_by_wp_id( $current_user->ID );
    } elseif ( isset( $_GET['eme_email'] ) ) {
        $person['email'] = esc_html( eme_sanitize_email( $_GET['eme_email'] ) );
    }
    $person = eme_esc_person_for_form( $person );

    // wp-profile warning appended to readonly fields
    $wp_profile_warning = ! empty( $readonly )
        ? "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url( EME_PLUGIN_URL ) . "images/warning.png' alt='warning'>%s</div>"
        : '';

    $has_email     = preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    if ( ! $has_email ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $ctx = [
        'person'           => $person,
        'fn_prefix'        => '',
        'form_id'          => '',
        'eme_is_admin'     => $eme_is_admin_request,
        'readonly'         => $readonly,
        'disabled'         => '',
        'dfc_basic'        => '',
        'extra_css'        => '',
        'allow_clear'      => false,
        'invite_readonly'  => '',
        'selected_captcha' => $selected_captcha,
        'required'         => false,
        'required_att'     => '',
    ];

    // subscribeform only uses NAME, FIRSTNAME, EMAIL, MAILGROUPS, GDPR, REMEMBERME, CAPTCHA, SUBMIT
    // Override NAME/FIRSTNAME/EMAIL to add the wp-profile warning and handle $unsubscribe
    $handlers = [];

    $handlers['/#_(NAME|LASTNAME)(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $person, $readonly, $unsubscribe, $wp_profile_warning ) {
        if ( $unsubscribe ) {
            return '';
        }
        $placeholder_text = isset( $matches[2] )
            ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
            : esc_html__( 'Last name', 'events-made-easy' );
        $replacement = "<input {$ctx['required_att']} type='text' name='lastname' id='lastname' value='{$person['lastname']}' $readonly placeholder='$placeholder_text'>";
        if ( $wp_profile_warning ) {
            $replacement .= sprintf( $wp_profile_warning, esc_html__( 'You can change your last name in your WP profile.', 'events-made-easy' ) );
        }
        return $replacement;
    };
    $handlers['/#_FIRSTNAME(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $person, $readonly, $unsubscribe, $wp_profile_warning ) {
        if ( $unsubscribe ) {
            return '';
        }
        $placeholder_text = isset( $matches[1] )
            ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
            : esc_html__( 'First name', 'events-made-easy' );
        $replacement = "<input {$ctx['required_att']} type='text' name='firstname' id='firstname' value='{$person['firstname']}' $readonly placeholder='$placeholder_text'>";
        if ( $wp_profile_warning ) {
            $replacement .= sprintf( $wp_profile_warning, esc_html__( 'You can change your first name in your WP profile.', 'events-made-easy' ) );
        }
        return $replacement;
    };
    $handlers['/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/'] = function( $result, $matches, $ctx ) use ( $person, $readonly, $wp_profile_warning ) {
        $placeholder_text = isset( $matches[2] )
            ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
            : esc_html__( 'Email', 'events-made-easy' );
        $replacement = "<input required='required' type='email' name='email' value='{$person['email']}' $readonly placeholder='$placeholder_text'>";
        if ( $wp_profile_warning ) {
            $replacement .= sprintf( $wp_profile_warning, esc_html__( 'You can change your email in your WP profile.', 'events-made-easy' ) );
        }
        return [ 'html' => $replacement, 'set_required' => true ]; // unconditional true, since the form only exists in the frontend
    };
    $handlers['/#_MAILGROUPS(\{.+?\})?/'] = function( $result, $matches, $ctx ) {
        if ( isset( $matches[1] ) ) {
            $group_ids = substr( $matches[1], 1, -1 );
            if ( eme_is_list_of_int( $group_ids ) ) {
                $groups  = eme_get_subscribable_groups( $group_ids );
                $ids_arr = explode( ',', $group_ids );
                if ( in_array( '-1', $ids_arr ) && wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
                    $groups[] = [ 'group_id' => -1, 'name' => __( 'Newsletter concerning new events', 'events-made-easy' ) ];
                }
                return count( $ids_arr ) == 1
                    ? eme_ui_select_key_value( $ids_arr[0], 'email_group', $groups, 'group_id', 'name', '', 1 )
                    : eme_ui_multiselect_key_value( '', 'email_groups', $groups, 'group_id', 'name', 5, '', 1 );
            }
            return '';
        }
        $tmp_groups               = eme_get_subscribable_groups();
        $eme_cron_send_new_events = wp_next_scheduled( 'eme_cron_send_new_events' );
        $subscribable_groups      = ( ! empty( $tmp_groups ) && ( count( $tmp_groups ) > 1 || $eme_cron_send_new_events ) )
            ? [ '' => esc_html__( 'All', 'events-made-easy' ) ] : [];
        if ( $eme_cron_send_new_events ) {
            $subscribable_groups['-1'] = esc_html__( 'Newsletter concerning new events', 'events-made-easy' );
        }
        foreach ( $tmp_groups as $group ) {
            $subscribable_groups[ $group['group_id'] ] = esc_html( $group['name'] );
        }
        if ( ! empty( $subscribable_groups ) ) {
            return count( $subscribable_groups ) == 1
                ? eme_ui_select( '', 'email_group', $subscribable_groups, '', 1 )
                : eme_ui_multiselect( '', 'email_groups', $subscribable_groups, 5, '', 1 );
        }
        return '';
    };
    $handlers['/#_GDPR(\{.+?\})?/'] = function( $result, $matches, $ctx ) use ( $person ) {
        $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : '';
        return eme_ui_checkbox_binary( $person['gdpr'], 'gdpr', $label, 1, 'eme-gdpr-field' );
    };
    $handlers['/#_REMEMBERME(\{.+?\})?/'] = function( $result, $matches, $ctx ) {
        $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : __( 'Remember me?', 'events-made-easy' );
        if ( ! is_user_logged_in() ) {
            return eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
        }
        return '';
    };
    $handlers['/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/'] = function( $result, $matches, $ctx ) use ( $selected_captcha ) {
        if ( ! empty( $selected_captcha ) ) {
            return eme_generate_captchas_html( $selected_captcha );
        }
        return '';
    };
    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) use ( $unsubscribe ) {
        if ( isset( $matches[1] ) ) {
            $label = substr( $matches[1], 1, -1 );
        } elseif ( $unsubscribe ) {
            $label = __( 'Unsubscribe', 'events-made-easy' );
        } else {
            $label = __( 'Subscribe', 'events-made-easy' );
        }
        return "<img id='loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    $format = eme_run_formfield_dispatch( $format, $handlers, $ctx );

    return $format;
}

function eme_replace_cpiform_placeholders( $format, $person ) {
    $eme_is_admin_request = eme_is_admin_request();

    $selected_captcha = '';
    $add_captcha = !(is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ));
    $format = eme_add_missing_placeholders( $format, $add_captcha );
    $configured_captchas = eme_get_configured_captchas();
    if ($add_captcha && ! empty( $configured_captchas ) && ! $eme_is_admin_request ) {
        $selected_captcha = array_key_first( $configured_captchas );
    }

    $current_userid = get_current_user_id();
    $readonly       = ( is_user_logged_in() && $person['wp_id'] == $current_userid ) ? "readonly='readonly'" : '';
    $person         = eme_esc_person_for_form( $person );

    // wp-profile warning for readonly fields
    $wp_profile_warning = ! empty( $readonly )
        ? "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url( EME_PLUGIN_URL ) . "images/warning.png' alt='warning'>%s</div>"
        : '';

    $has_lastname  = preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_firstname = preg_match( '/#(REQ|ESC)?_FIRSTNAME/', $format );
    $has_email     = preg_match( '/#(REQ|ESC)?_(EMAIL|HTML5_EMAIL)/', $format );
    if ( ! $has_lastname || ! $has_firstname || ! $has_email ) {
        return "<div id='message' class='eme-message-error eme-cpi-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $ctx = [
        'person'           => $person,
        'fn_prefix'        => '',
        'form_id'          => '',
        'eme_is_admin'     => $eme_is_admin_request,
        'readonly'         => $readonly,
        'disabled'         => '',
        'dfc_basic'        => '',
        'extra_css'        => '',
        'allow_clear'      => false,
        'invite_readonly'  => '',
        'selected_captcha' => $selected_captcha,
        'required'         => false,
        'required_att'     => "",
        'wp_profile_warning' => $wp_profile_warning,
    ];

    $handlers = eme_get_person_formfield_handler_definitions();
    $handlers['/#_IMAGE/'] = function( $result, $matches, $ctx ) use ( $person ) {
        return eme_person_replace_image_input_div( $person, 1 );
    };
    $handlers['/#_FIELD\{(.+)\}/'] = function( $result, $matches, $ctx ) use ( $person ) {
        $formfield = eme_get_formfield( $matches[1] );
        if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'people' ) {
            $field_id       = $formfield['field_id'];
            $person_id      = $person['person_id'];
            $postfield_name = "dynamic_personfields[$person_id][FIELD{$field_id}]";
            $postvar_arr    = [ 'dynamic_personfields', $person_id, 'FIELD' . $field_id ];
            $entered_val    = ! empty( $_POST ) ? eme_getValueFromPath( $_POST, $postvar_arr ) : false;
            if ( $entered_val === false ) {
                $answers = eme_get_person_answers( $person_id );
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) { $entered_val = $answer['answer']; break; }
                }
            }
            $files = eme_get_uploaded_files( $person_id, 'people' );
            if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                $entered_files = [];
                foreach ( $files as $file ) {
                    if ( $file['field_id'] == $field_id ) { $entered_files[] = $file; }
                }
                $entered_val = $entered_files;
            }
            $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
            return [ 'html' => eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required ), 'set_required' => $required ];
        }
        return [ 'html' => '', 'not_found' => true ];
    };
    $handlers['/#_SUBMIT(\{.+?\})?/'] = function( $result, $matches, $ctx ) {
        $label = isset( $matches[1] ) ? substr( $matches[1], 1, -1 ) : __( 'Save personal info', 'events-made-easy' );
        return "<img id='loading_gif' alt='loading' src='" . esc_url( EME_PLUGIN_URL ) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
    };

    $format = eme_run_formfield_dispatch( $format, $handlers, $ctx );
    return $format;
}

function eme_replace_membership_familyformfields_placeholders( $format, $counter ) {
    $dynamic_field_class_basic = 'nodynamicupdates dynamicfield';

    $has_lastname  = preg_match( '/#(REQ|ESC)?_(LASTNAME|NAME)/', $format );
    $has_firstname = preg_match( '/#(REQ|ESC)?_FIRSTNAME/', $format );
    if ( ! $has_lastname || ! $has_firstname ) {
        return "<div id='message' class='eme-message-error eme-family-message-error'>"
            . __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_FIRSTNAME placeholders.', 'events-made-easy' )
            . '</div>';
    }

    $handlers = [
        '/#_(NAME|LASTNAME)(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr = [ 'familymember', $counter, 'lastname' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Last name', 'events-made-easy' );
            $html = "<input required='required' type='text' name='familymember[$counter][lastname]' id='familymember[$counter][lastname]' value='$entered_val' class='$dynamic_field_class_basic' placeholder='$placeholder_text'>";
            return [ 'html' => $html, 'set_required' => true ];
        },
        '/#_FIRSTNAME(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr = [ 'familymember', $counter, 'firstname' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'First name', 'events-made-easy' );
            $html = "<input required='required' type='text' name='familymember[$counter][firstname]' id='familymember[$counter][firstname]' value='$entered_val' class='$dynamic_field_class_basic' placeholder='$placeholder_text'>";
            return [ 'html' => $html, 'set_required' => true ];
        },
        '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr = [ 'familymember', $counter, 'phone' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Phone number', 'events-made-easy' );
            return [ 'html' => "<input {$ctx['required_att']} type='tel' name='familymember[$counter][phone]' id='familymember[$counter][phone]' value='$entered_val' class='$dynamic_field_class_basic' placeholder='$placeholder_text'>" ];
        },
        '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr = [ 'familymember', $counter, 'email' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[2] )
                ? esc_attr( eme_translate( substr( $matches[2], 1, -1 ) ) )
                : esc_html__( 'Email', 'events-made-easy' );
            return [ 'html' => "<input required='required' type='email' name='familymember[$counter][email]' id='familymember[$counter][email]' value='$entered_val' class='$dynamic_field_class_basic' placeholder='$placeholder_text'>", 'set_required' => true ];
        },
        '/#_BIRTHDAY_EMAIL/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr     = [ 'familymember', $counter, 'bd_email' ];
            $entered_val     = eme_getValueFromPath( $_POST, $postvar_arr );
            $selected_bd_email = ( $entered_val === false ) ? 1 : $entered_val;
            return eme_ui_select_binary( $selected_bd_email, "familymember[$counter][bd_email]", 0, $dynamic_field_class_basic );
        },
        '/#_OPT_OUT/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr      = [ 'familymember', $counter, 'massmail' ];
            $entered_val      = eme_getValueFromPath( $_POST, $postvar_arr );
            $selected_massmail = ( $entered_val === false ) ? 1 : $entered_val;
            return eme_ui_select_binary( $selected_massmail, "familymember[$counter][massmail]", 0, $dynamic_field_class_basic . ' eme_snapselect' );
        },
        '/#_OPT_IN/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr      = [ 'familymember', $counter, 'massmail' ];
            $entered_val      = eme_getValueFromPath( $_POST, $postvar_arr );
            $selected_massmail = ( $entered_val === false ) ? 0 : $entered_val;
            return eme_ui_select_binary( $selected_massmail, "familymember[$counter][massmail]", 0, $dynamic_field_class_basic . ' eme_snapselect' );
        },
        '/#_BIRTHPLACE(\{.+?\})?/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $postvar_arr = [ 'familymember', $counter, 'birthplace' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Place of birth', 'events-made-easy' );
            return [ 'html' => "<input required='required' type='text' name='familymember[$counter][birthplace]' id='familymember[$counter][birthplace]' value='$entered_val' class='$dynamic_field_class_basic' placeholder='$placeholder_text'>", 'set_required' => true ];
        },
        '/#_BIRTHDATE(\{.+?\})?/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $fieldname   = "familymember[$counter][birthdate]";
            $postvar_arr = [ 'familymember', $counter, 'birthdate' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) { $entered_val = ''; }
            $placeholder_text = isset( $matches[1] )
                ? esc_attr( eme_translate( substr( $matches[1], 1, -1 ) ) )
                : esc_html__( 'Date of birth', 'events-made-easy' );
            return [ 'html' => "<input required='required' readonly='readonly' type='text' name='$fieldname' id='$fieldname' data-date='$entered_val' data-format='" . EME_WP_DATE_FORMAT . "' data-view='years' class='eme_formfield eme_formfield_fdate $dynamic_field_class_basic' placeholder='$placeholder_text'>", 'set_required' => true ];
        },
        '/#_FIELDNAME\{(.+)\}/' => function( $result, $matches, $ctx ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) ) {
                return esc_html( eme_translate( $formfield['field_name'] ) );
            }
            return [ 'html' => '', 'not_found' => true ];
        },
        '/#_FIELD\{(.+)\}/' => function( $result, $matches, $ctx ) use ( $counter, $dynamic_field_class_basic ) {
            $formfield = eme_get_formfield( $matches[1] );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'members', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $postfield_name = "familymember[$counter][FIELD{$field_id}]";
                $postvar_arr    = [ 'familymember', $counter, 'FIELD' . $field_id ];
                $entered_val    = eme_getValueFromPath( $_POST, $postvar_arr );
                $required = (bool) $formfield['field_required'] || (bool) $ctx['required'];
                return [ 'html' => eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_field_class_basic ), 'set_required' => $required ];
            }
            return [ 'html' => '', 'not_found' => true ];
        },
    ];

    $ctx         = [ 'required' => false, 'required_att' => '' ];
    $format      = eme_run_formfield_dispatch( $format, $handlers, $ctx );

    return $format;
}

function eme_find_required_formfields( $format ) {
    preg_match_all( '/#REQ_?[A-Za-z0-9_]+(\{.*?\})?/', $format, $placeholders );
    usort( $placeholders[0], 'eme_sort_stringlenth' );
    $result = [];
    foreach ( $placeholders[0] as $placeholder ) {
        // #_NAME and #REQ_NAME should be using _LASTNAME
        $res = preg_replace( '/_NAME/', '_LASTNAME', $placeholder );
        if ( preg_match( '/#REQ_FIELD/', $res ) ) {
            // We just want the fieldnames: FIELD1, FIELD2, ... like they are POST'd via the form
            // But there are 3 possible notations in the format: FIELD1, FIELD{1}, FIELD{fieldname}
            $res       = preg_replace( '/#REQ_FIELD|\{|\}/', '', $res );
            $formfield = eme_get_formfield( $res );
            if ( ! empty( $formfield ) ) {
                $res = 'FIELD' . $formfield['field_id'];
            } else {
                $res = '';
            }
        } else {
            $res = preg_replace( '/#REQ_|\{|\}/', '', $res );
        }
        if ( ! empty( $res ) ) {
            $result[] = $res;
        }
    }

    // formfields can be required in their definition too, so lets check those too
    preg_match_all( '/#_[A-Za-z0-9_]+(\{.*?\})?/', $format, $placeholders );
    usort( $placeholders[0], 'eme_sort_stringlenth' );
    foreach ( $placeholders[0] as $placeholder ) {
        if ( preg_match( '/#_FIELD/', $placeholder ) ) {
            // We just want the fieldnames: FIELD1, FIELD2, ... like they are POST'd via the form
            // But there are 3 possible notations in the format: FIELD1, FIELD{1}, FIELD{fieldname}
            $res       = preg_replace( '/#_FIELD|\{|\}/', '', $placeholder );
            $formfield = eme_get_formfield( $res );
            if ( ! empty( $formfield ) && $formfield['field_required'] ) {
                $res      = 'FIELD' . $formfield['field_id'];
                $result[] = $res;
            }
        }
    }
    return $result;
}

function eme_answer2readable( $answer, $formfield, $convert_val = 1, $sep = '||', $target = 'html', $from_backend = 0 ) {
    $field_values = $formfield['field_values'];
    $field_tags   = $formfield['field_tags'];

    if ( eme_is_multifield( $formfield['field_type'] ) ) {
        if ( $convert_val ) {
            $answers = eme_convert_multi2array( $answer );
            if (empty($answers))
                $answers = ['']; // this to catch the possibility of an empty string as answer but we still want to show the tag

            $values  = eme_convert_multi2array( $field_values );
            if ( empty( $field_tags ) ) {
                return eme_convert_array2multi( $answers, $sep );
            }
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $answers as $ans ) {
                foreach ( $values as $key => $val ) {
                    if ( $val === $ans ) {
                        if ( $target == 'html' ) {
                            $my_arr[] = esc_html( $tags[ $key ] );
                        } else {
                            $my_arr[] = $tags[ $key ];
                        }
                    }
                }
            }
            return eme_convert_array2multi( $my_arr, $sep );
        } else {
            $answers = eme_convert_multi2array( $answer );
            if ( $target == 'html' ) {
                $answers = array_map( 'esc_html', $answers );
            }
            return eme_convert_array2multi( $answers, $sep );
        }
    } else {
        if ( ! isset( $formfield['field_attributes'] ) ) {
            $formfield['field_attributes'] = '';
        }
        if ( $formfield['field_type'] == 'date' ) { // for type DATE
            return eme_localized_date( $answer, EME_TIMEZONE, $from_backend );
        } elseif ( $formfield['field_type'] == 'date_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_date( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_date( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['field_type'] == 'datetime_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_datetime( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_datetime( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['field_type'] == 'time_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_time( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_time( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['extra_charge'] && $target == 'html' ) {
            //return eme_convert_answer_price($answer);
            return $answer;
        } else {
            return $answer;
        }
    }
}

function eme_convert_answer_price( $answer ) {
    if ( $answer['type'] == 'booking' ) { // for fields with answers that are an extra charge
        $event = eme_get_event_by_booking_id( $answer['related_id'] );
        return eme_localized_price( $answer, $event['currency'] );
    } elseif ( $answer['type'] == 'member' ) { // for fields with answers that are an extra charge
        $member     = eme_get_member( $answer['related_id'] );
        $membership = eme_get_membership( $member['membership_id'] );
        return eme_localized_price( $answer, $membership['properties']['currency'] );
    } else {
        return $answer;
    }
}

function eme_get_answer_fieldids( $ids_arr ) {
    return eme_get_booking_answers_fieldids( $ids_arr );
}

function eme_get_booking_answers_fieldids( $ids_arr ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each event/booking)
    if (!empty($ids_arr) &&  eme_is_numeric_array( $ids_arr ) ) {
        $ids_arr_int  = array_map( 'intval', $ids_arr );
        $placeholders = implode( ',', array_fill( 0, count( $ids_arr_int ), '%d' ) );
        $prepared_sql = $wpdb->prepare( "SELECT DISTINCT field_id FROM $answers_table WHERE type='booking' AND eme_grouping=0 AND related_id IN ($placeholders) ORDER BY field_id", ...$ids_arr_int ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } else {
        return [];
    }
}

function eme_get_tasksignups_answers_fieldids( $ids_arr ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each event/booking)
    if (!empty($ids_arr) &&  eme_is_numeric_array( $ids_arr ) ) {
        $ids_arr_int  = array_map( 'intval', $ids_arr );
        $placeholders = implode( ',', array_fill( 0, count( $ids_arr_int ), '%d' ) );
        $prepared_sql = $wpdb->prepare( "SELECT DISTINCT field_id FROM $answers_table WHERE type='tasksignup' AND eme_grouping=0 AND related_id IN ($placeholders) ORDER BY field_id", ...$ids_arr_int ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } else {
        return [];
    }
}

function eme_get_people_export_fieldids() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each run)
    $sql = "SELECT field_id FROM $table WHERE export=1 AND field_purpose='people' ORDER BY field_id";
    return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_dyndata_adminform( $eme_data, $templates_array, $used_groupingids ) {
    $eme_dyndata_conditions = eme_get_dyndata_conditions();
    ?>
    <div id="div_dyndata">
        <b><?php esc_html_e( 'Dynamically show fields based on a number of conditions', 'events-made-easy' ); ?></b>
        <table class="eme_dyndata">
        <thead>
            <tr>
                <th></th>
                <th><strong><?php esc_html_e( 'Index', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Field condition', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Templates', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Repeat', 'events-made-easy' ); ?></strong></th>
                <th></th>
            </tr>
        </thead>    
        <tbody id="eme_dyndata_tbody" class="eme_dyndata_tbody">
            <?php
            // if there are no entries in the eme_data array, make 1 empty entry in it, so it renders at least 1 row
            if ( ! is_array( $eme_data ) || count( $eme_data ) == 0 ) {
                $info     = [
                    'field'              => '',
                    'condition'          => '',
                    'condval'            => '',
                    'template_id_header' => 0,
                    'template_id'        => 0,
                    'template_id_footer' => 0,
                    'repeat'             => 0,
                    'grouping'           => 1,
                ];
                $eme_data = [ $info ];
                $required = '';
                $dyn_count_total = 0;
            } else {
                $required = "required='required'";
                $dyn_count_total = count( $eme_data);
            }
            foreach ( $eme_data as $count => $info ) {
                $grouping_used = in_array( $info['grouping'], $used_groupingids ) ? 1 : 0;
                ?>
                    <tr id="eme_dyndata_<?php echo esc_attr( $count ); ?>">
                    <td>
                <?php echo "<img class='eme-sortable-handle' src='" . esc_url(EME_PLUGIN_URL) . "images/reorder.png' alt='" . esc_attr__( 'Reorder', 'events-made-easy' ) . "'>"; ?>
                    </td>
                    <td>
            <!-- the grouping index parameter should be a unique index per condition. This is used to set/retrieve all the entered info based on this condition in the database (so once set, always keep it to the same value for that condition) -->
            <!-- Since it is too complicated to explain that, but we still need it: keep it a hidden field if possible, the value for new rows is set via php anyway -->
                        <?php if ($dyn_count_total>0 && $grouping_used==0) : ?>
                        <input type='text' id="eme_dyndata[<?php echo esc_attr( $count ); ?>][grouping]" name="eme_dyndata[<?php echo esc_attr( $count ); ?>][grouping]" aria-label="hidden grouping index" size="5" maxlength="5" value="<?php echo esc_attr( $info['grouping'] ); ?>">
                        <?php else : ?>
                        <?php if ($dyn_count_total>0) echo esc_html( $info['grouping'] ); ?>
                        <input type='hidden' id="eme_dyndata[<?php echo esc_attr( $count ); ?>][grouping]" name="eme_dyndata[<?php echo esc_attr( $count ); ?>][grouping]" aria-label="hidden grouping index" value="<?php echo esc_attr( $info['grouping'] ); ?>">
                        <?php endif; ?>
                    </td>
                    <td><table style="">
                        <tr><td><?php esc_html_e( 'Field', 'events-made-easy' ); ?></td><td><input <?php echo $required; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded attribute ?> id="eme_dyndata[<?php echo esc_attr( $count ); ?>][field]" name="eme_dyndata[<?php echo esc_attr( $count ); ?>][field]" size="12" aria-label="field" value="<?php echo esc_attr( $info['field'] ); ?>"></td></tr>
                        <tr><td><?php esc_html_e( 'Condition', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['condition'], 'eme_dyndata[' . $count . '][condition]', $eme_dyndata_conditions, '', 0, '', "aria-label='condition'" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?></td></tr>
                        <tr><td><?php esc_html_e( 'Condition value', 'events-made-easy' ); ?></td><td><input <?php echo $required; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded attribute ?> id="eme_dyndata[<?php echo esc_attr( $count ); ?>][condval]" name="eme_dyndata[<?php echo esc_attr( $count ); ?>][condval]" aria-label="condition value" size="12" value="<?php echo esc_attr( $info['condval'] ); ?>"></td></tr>
                    </table>
                    </td>
                    <td><table style="">
                        <tr><td><?php esc_html_e( 'Header template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id_header'], 'eme_dyndata[' . $count . '][template_id_header]', $templates_array, '', 0, '', "aria-label='template_id_header'" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?></td></tr>
                        <tr><td><?php esc_html_e( 'Template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id'], 'eme_dyndata[' . $count . '][template_id]', $templates_array, '', 0, '', "aria-label='template_id'" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?></td></tr>
                        <tr><td><?php esc_html_e( 'Footer template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id_footer'], 'eme_dyndata[' . $count . '][template_id_footer]', $templates_array, '', 0, '', "aria-label='template_id_footer'" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?></td></tr>
                    </table>
                    </td>
                    <td>
                <?php echo eme_ui_select_binary( $info['repeat'], 'eme_dyndata[' . $count . '][repeat]', 0, '', "aria-label='repeat'" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select() ?>
                    </td>
                    <td>
                        <a href="#" class='eme_remove_dyndatacondition'><?php echo "<img class='eme_remove_dyndatacondition' src='" . esc_url(EME_PLUGIN_URL) . "images/cross.png' alt='" . esc_attr__( 'Remove', 'events-made-easy' ) . "' title='" . esc_attr__( 'Remove', 'events-made-easy' ) . "'>"; ?></a><a href="#" class="eme_dyndata_add_tag"><?php echo "<img class='eme_dyndata_add_tag' src='" . esc_url(EME_PLUGIN_URL) . "images/plus_16.png' alt='" . esc_attr__( 'Add new condition', 'events-made-easy' ) . "' title='" . esc_attr__( 'Add new condition', 'events-made-easy' ) . "'>"; ?></a>
                <?php
                if ( $grouping_used ) {
                    echo "<br><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_attr__( 'Warning: there are already answers entered based on this condition, changing or removing this condition might lead to unwanted side effects.', 'events-made-easy' ) . "'>";
                }
                ?>
                    </td>
                    </tr>
                <?php
            }
            ?>
        </tbody>
        </table>
        <p class='eme_smaller'>
        <?php esc_html_e( 'This will additionally show the selected template in the form if the condition is met.', 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( "The 'Field' parameter is to be filled out with any valid placeholder allowed in the form.", 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( "The selected template will be shown several times if the repeat option is used (based on the number of times the field is different from the condition value. This is not used for the 'equal to' condition selector.", 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( 'The selected template can contain html and also have placeholders for custom form fields (no other placeholders allowed).', 'events-made-easy' ); ?>
        <?php esc_html_e( 'Use the placeholder #_DYNAMICDATA to show the dynamic forms in your form.', 'events-made-easy' ); ?>
        </p>
    </div>
    <?php
}

function eme_handle_dyndata_post_adminform() {
    $eme_dyndata           = [];
    $biggest_grouping_seen = 0;
    $groupings_seen        = [];
    $eme_dyndata_arr       = [];
    if ( empty( $_POST['eme_dyndata'] ) ) {
        return $eme_dyndata_arr;
    }
    foreach ( wp_unslash( $_POST['eme_dyndata'] ) as $eme_dyndata ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( $eme_dyndata['template_id'] > 0 && isset( $eme_dyndata['grouping'] ) ) {
            $grouping = intval( $eme_dyndata['grouping'] );
            if ( $biggest_grouping_seen < $grouping ) {
                $biggest_grouping_seen = $grouping;
            }
        }
    }
    foreach ( wp_unslash( $_POST['eme_dyndata'] ) as $eme_dyndata ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( $eme_dyndata['template_id'] > 0 ) {
            $eme_dyndata['template_id'] = intval( $eme_dyndata['template_id'] );
            if ( isset( $eme_dyndata['repeat'] ) && $eme_dyndata['repeat'] == 1 ) {
                $eme_dyndata['repeat']  = intval( $eme_dyndata['repeat'] );
                $eme_dyndata['condval'] = intval( $eme_dyndata['condval'] );
            } else {
                $eme_dyndata['repeat']  = 0;
                $eme_dyndata['condval'] = $eme_dyndata['condval'];
            }
            if ( isset( $eme_dyndata['template_id_header'] ) ) {
                $eme_dyndata['template_id_header'] = intval( $eme_dyndata['template_id_header'] );
            } else {
                $eme_dyndata['template_id_header'] = 0;
            }
            if ( isset( $eme_dyndata['template_id_footer'] ) ) {
                $eme_dyndata['template_id_footer'] = intval( $eme_dyndata['template_id_footer'] );
            } else {
                $eme_dyndata['template_id_footer'] = 0;
            }
            if ( isset( $eme_dyndata['grouping'] ) ) {
                // to make sure people don't use 2 times the same id
                $grouping = intval( $eme_dyndata['grouping'] );
                if ( in_array( $grouping, $groupings_seen ) ) {
                    $eme_dyndata['grouping'] = $biggest_grouping_seen + 1;
                    ++$biggest_grouping_seen;
                    $groupings_seen[] = $biggest_grouping_seen;
                } else {
                    $eme_dyndata['grouping'] = $grouping;
                    $groupings_seen[]        = $grouping;
                }
            } else {
                        $eme_dyndata['grouping'] = $biggest_grouping_seen + 1;
                ++$biggest_grouping_seen;
                $groupings_seen[] = $biggest_grouping_seen;
            }
            $eme_dyndata_arr[] = $eme_dyndata;
        }
    }
    return $eme_dyndata_arr;
}

add_action( 'wp_ajax_eme_formfields_list', 'eme_ajax_formfields_list' );
add_action( 'wp_ajax_eme_manage_formfields', 'eme_ajax_manage_formfields' );

function eme_ajax_formfields_list() {
    global $wpdb;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_events' ) ) ) {
        $ajaxResult            = [];
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }

    $table              = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $used_formfield_ids = eme_get_used_formfield_ids();
    $fTableResult       = [];
    $search_type        = isset( $_POST['search_type'] ) ? eme_sanitize_request( $_POST['search_type'] ) : '';
    $search_purpose     = isset( $_POST['search_purpose'] ) ? eme_sanitize_request( $_POST['search_purpose'] ) : '';
    $search_name        = isset( $_POST['search_name'] ) ? eme_sanitize_request( $_POST['search_name'] ) : '';
    $where              = '';
    $where_arr          = [];
    if ( ! empty( $search_name ) ) {
        $where_arr[] = $wpdb->prepare( 'field_name LIKE %s', '%' . $wpdb->esc_like( $search_name ) . '%' );
    }
    if ( ! empty( $search_type ) ) {
        $where_arr[] = $wpdb->prepare( "field_type = %s", $search_type);
    }
    if ( ! empty( $search_purpose ) ) {
        $where_arr[] = $wpdb->prepare( "field_purpose = %s", $search_purpose);
    }
    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    if ( current_user_can( get_option( 'eme_cap_forms' ) ) ) {
        $sql         = "SELECT COUNT(*) FROM $table $where";
        $recordCount = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name and conditions are safe variables
        $limit       = eme_get_ftable_limit();
        $orderby     = eme_get_ftable_orderby();
        $sql         = "SELECT * FROM $table $where $orderby $limit";
        $rows        = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name and conditions are safe variables
        $res         = [];
        foreach ( $rows as $key => $formfield ) {
            if ( empty( $formfield['field_name'] ) ) {
                $row['field_name'] = __( 'No name', 'events-made-easy' );
            }
            $rows[ $key ]['field_type']     = eme_get_fieldtype( $formfield['field_type'] );
            $rows[ $key ]['field_required'] = ( $formfield['field_required'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['field_purpose']  = eme_get_fieldpurpose( $formfield['field_purpose'] );
            $rows[ $key ]['extra_charge']   = ( $formfield['extra_charge'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['searchable']     = ( $formfield['searchable'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['used']           = in_array( $formfield['field_id'], $used_formfield_ids ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['field_name']     = "<a href='" . esc_url( admin_url( 'admin.php?page=eme-formfields&eme_admin_action=edit_formfield&field_id=' . $formfield['field_id'] ) ) . "'>" . esc_html( $formfield['field_name'] ) . '</a>';

            $copy_link='window.location.href="'.esc_url( admin_url( 'admin.php?page=eme-formfields&eme_admin_action=copy_formfield&field_id=' . $formfield['field_id'] ) ).'";';
            $rows[ $key ][ 'copy'] = "<button onclick='$copy_link' title='" . esc_attr__( 'Copy', 'events-made-easy' ) . "' class='ftable-command-button eme-copy-button'><span>copy</span></a>";

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

function eme_ajax_manage_formfields() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $fTableResult=[];
    if (! current_user_can( get_option( 'eme_cap_forms' ) ) || !isset( $_REQUEST['field_id'] ) ) {
        $fTableResult['Result']      = 'Error';
        $fTableResult['Message']     = __( 'Access denied!', 'events-made-easy' );
    }
    if ( isset( $_REQUEST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_REQUEST['do_action'] );
        switch ( $do_action ) {
            case 'deleteFormfield':
                // validation happens in the eme_delete_formfields function
                eme_delete_formfields( [ intval($_REQUEST['field_id']) ] );
                $fTableResult['Result']      = 'OK';
                $fTableResult['Message'] = __( 'Records deleted!', 'events-made-easy' );
                print wp_json_encode( $fTableResult );
                wp_die();
                break;
            case 'deleteFormfields':
                $field_ids = explode( ',', eme_sanitize_request($_REQUEST['field_id']) );
                if (eme_is_numeric_array( $field_ids)) {
                    // validation happens in the eme_delete_formfields function
                    eme_delete_formfields( $field_ids );
                    $fTableResult['Result']      = 'OK';
                    $fTableResult['Message'] = __( 'Records deleted!', 'events-made-easy' );
                } else {
                    $fTableResult['Result']      = 'Error';
                    $fTableResult['Message']     = __( 'Access denied!', 'events-made-easy' );
                }
                print wp_json_encode( $fTableResult );
                wp_die();
                break;
        }
    }
    wp_die();
}
