<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_location() {
    $location = [
        'location_name'         => '',
        'location_address1'     => '',
        'location_address2'     => '',
        'location_city'         => '',
        'location_state'        => '',
        'location_zip'          => '',
        'location_country'      => '',
        'location_latitude'     => '',
        'location_longitude'    => '',
        'location_description'  => '',
        'location_category_ids' => '',
        'location_url'          => '',
        'location_prefix'       => '',
        'location_slug'         => '',
        'location_image_url'    => '',
        'location_external_ref' => '',
        'location_image_id'     => 0,
        'location_author'       => 0,
        'location_attributes'   => [],
    ];

    $location['location_properties'] = eme_init_location_props();
    return $location;
}

function eme_init_location_props( $props = [] ) {
    if ( ! isset( $props['map_icon'] ) ) {
        $props['map_icon'] = '';
    }
    if ( ! isset( $props['online_only'] ) ) {
        $props['online_only'] = 0;
    }
    if ( ! isset( $props['max_capacity'] ) ) {
        $props['max_capacity'] = 0;
    }
    if ( ! isset( $props['override_loc'] ) ) {
        $props['override_loc'] = 0;
    }
    // for sure integers
    $numbers = [ 'online_only', 'max_capacity', 'override_loc' ];
    foreach ( $numbers as $opt ) {
        $props[$opt]=intval($props[$opt]);
    }
    return $props;
}

function eme_locations_page() {
    $current_userid = get_current_user_id();
    if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'import_locations' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
        // eme_cap_cleanup is used for cleanup, cron and imports (should more be something like 'eme_cap_actions')
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $message = eme_import_csv_locations();
        eme_locations_table( $message );

    } elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_location' ) {
        $location_id = eme_sanitize_request( $_GET['location_id'] );
        $location    = eme_get_location( $location_id );
        if ( ! empty( $location ) && ( current_user_can( get_option( 'eme_cap_edit_locations' ) ) ||
            ( current_user_can( get_option( 'eme_cap_author_locations' ) ) && ( $location['location_author'] == $current_userid ) ) ) ) {
            // edit location
            eme_locations_edit_layout( $location );
        } else {
            $message = __( 'You have no right to edit this location!', 'events-made-easy' );
            eme_locations_table( $message );
        }
    } elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'copy_location' ) {
        $location_id = eme_sanitize_request( $_GET['location_id'] );
        $location    = eme_get_location( $location_id );
        if ( empty( $location ) ) {
            $location = eme_new_location();
        }
        // we add the custom field answers to the location copy
        $location['cf_answers'] = eme_get_location_answers( $location_id );
        // make it look like a new location
        if ( isset( $location['location_id'] ) ) {
            unset( $location['location_id'] );
        }
        $location['location_name'] .= __( ' (Copy)', 'events-made-easy' );

        if ( current_user_can( get_option( 'eme_cap_add_locations' ) ) ) {
            eme_locations_edit_layout( $location );
        } else {
            $message = __( 'You have no right to copy this location!', 'events-made-easy' );
            eme_locations_table( $message );
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_location' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( current_user_can( get_option( 'eme_cap_add_locations' ) ) ) {
            $location = eme_new_location();
            eme_locations_edit_layout( $location );
        } else {
            $message = __( 'You have no right to add a location!', 'events-made-easy' );
            eme_locations_table( $message );
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && ( $_POST['eme_admin_action'] == 'do_editlocation' || $_POST['eme_admin_action'] == 'do_addlocation' ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $action = eme_sanitize_request( $_POST['eme_admin_action'] );
        if ( $action == 'do_editlocation' ) {
            $location_id = intval( $_POST['location_id'] );
            $location    = eme_get_location( $location_id );
            if ( empty( $location ) ) {
                $location_id = 0;
                $location    = eme_new_location();
            }
        } else {
            $location_id = 0;
            $location    = eme_new_location();
        }

        if ( $action == 'do_addlocation' && ! current_user_can( get_option( 'eme_cap_add_locations' ) ) ) {
            $message = __( 'You have no right to add a location!', 'events-made-easy' );
            eme_locations_table( $message );
        } elseif ( $action == 'do_editlocation' && ! ( current_user_can( get_option( 'eme_cap_edit_locations' ) ) ||
            ( current_user_can( get_option( 'eme_cap_author_locations' ) ) && ( $location['location_author'] == $current_userid ) ) ) ) {
            $message = __( 'You have no right to edit this location!', 'events-made-easy' );
            eme_locations_table( $message );
        } else {
            $post_vars = [ 'location_name', 'location_address1', 'location_address2', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_url', 'location_image_url', 'location_image_id', 'location_prefix', 'location_slug', 'location_latitude', 'location_longitude', 'location_author' ];
            foreach ( $post_vars as $post_var ) {
                if ( isset( $_POST[ $post_var ] ) ) {
                    $location[ $post_var ] = eme_sanitize_request( $_POST[ $post_var ] );
                }
            }
            //switched to WP TinyMCE field
            $location['location_description'] = eme_kses_maybe_unfiltered( $_POST['location_description'] );

            if ( isset( $_POST['location_category_ids'] ) && eme_is_numeric_array( $_POST['location_category_ids'] ) ) {
                $location ['location_category_ids'] = join( ',', $_POST['location_category_ids'] );
            } else {
                $location ['location_category_ids'] = '';
            }

            $location_attributes = [];
            $i=1;
            while (isset( $_POST[ "eme_attr_{$i}_ref" ] ) && !eme_is_empty_string( $_POST[ "eme_attr_{$i}_ref" ] ) ) {
                if ( !eme_is_empty_string( $_POST[ "eme_attr_{$i}_name" ] ) ) {
                    $location_attributes[ $_POST[ "eme_attr_{$i}_ref" ] ] = eme_kses( $_POST[ "eme_attr_{$i}_name" ] );
                }
                $i++;
            }
            $location['location_attributes'] = $location_attributes;

            $location_properties = [];
            foreach ( $_POST as $key => $value ) {
                if ( preg_match( '/eme_loc_prop_(.+)/', eme_sanitize_request( $key ), $matches ) ) {
                    $found_key = $matches[1];
                    if ( preg_match( '/password/', $found_key ) ) {
                        $location_properties[ $found_key ] = $value;
                    } else {
                        $location_properties[ $found_key ] = eme_kses( $value );
                    }
                }
            }
            $location['location_properties'] = eme_init_location_props( $location_properties );

            $location          = eme_sanitize_location( $location );
            $validation_result = eme_validate_location( $location );
            if ( $validation_result == 'OK' ) {
                if ( $action == 'do_addlocation' ) {
                    $new_location_id = eme_insert_location( $location );
                    if ( $new_location_id ) {
                        eme_location_store_answers( $new_location_id );
                        eme_upload_files( $new_location_id, 'locations' );
                        $new_location = eme_get_location( $new_location_id );
                        $message      = __( 'The location has been added.', 'events-made-easy' );
                        if ( get_option( 'eme_stay_on_edit_page' ) ) {
                            eme_locations_edit_layout( $new_location, $message );
                            return;
                        }
                    } else {
                        $message = __( 'There has been a problem adding the location.', 'events-made-easy' );
                    }
                } elseif ( $action == 'do_editlocation' ) {
                    if ( eme_update_location( $location, $location_id ) ) {
                        eme_location_store_answers( $location_id );
                        eme_upload_files( $location_id, 'locations' );
                        $message = __( 'The location has been updated.', 'events-made-easy' );
                        if ( get_option( 'eme_stay_on_edit_page' ) ) {
                            // for edit, we need a location id
                            $location = eme_get_location( $location_id );
                            eme_locations_edit_layout( $location, $message );
                            return;
                        }
                    } else {
                        $message = __( 'The location update failed.', 'events-made-easy' );
                    }
                }
                eme_locations_table( $message );
            } else {
                // validation failed, show why and return to the edit
                $message                         = $validation_result;
                $location['location_attributes'] = eme_unserialize( $location_attributes );
                $location['location_properties'] = eme_unserialize( $location_properties );
                eme_locations_edit_layout( $location, $message );
            }
        }
    } else {
        // no action, just a locations list
        eme_locations_table();
    }
}

function eme_import_csv_locations() {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;

    //validate whether uploaded file is a csv file
    $csvMimes = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];
    if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
        return sprintf( esc_html__( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
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
        $enclosure = substr( $enclosure, 0, 1 );
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
    if ( ! in_array( 'location_name', $headers ) || ! in_array( 'location_address1', $headers ) || ! in_array( 'location_city', $headers ) ) {
        $result = __( 'Not all required fields present.', 'events-made-easy' );
    } else {
        $empty_props = [];
        $empty_props = eme_init_location_props( $empty_props );
        // now loop over the rest
        while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
            $line = array_combine( $headers, $row );
            // remove columns with empty values
            $line        = eme_array_remove_empty_elements( $line );
            $location_id = 0;
            if ( isset( $line['location_name'] ) && isset( $line['location_address1'] ) && isset( $line['location_city'] ) ) {
                // also import attributes
                foreach ( $line as $key => $value ) {
                    if ( preg_match( '/^att_(.*)$/', $key, $matches ) ) {
                        $att = $matches[1];
                        if ( ! isset( $line['location_attributes'] ) ) {
                            $line['location_attributes'] = [];
                        }
                        $line['location_attributes'][ $att ] = $value;
                    }
                }

                // also import properties
                foreach ( $line as $key => $value ) {
                    if ( preg_match( '/^prop_(.*)$/', $key, $matches ) ) {
                        $prop = $matches[1];
                        if ( ! isset( $line['location_properties'] ) ) {
                            $line['location_properties'] = [];
                        }
                        if ( array_key_exists( $prop, $empty_props ) ) {
                            $line['location_properties'][ $prop ] = $value;
                        }
                    }
                }

                // if the location already exists: update it
                if ( isset( $line['external_ref'] ) ) {
                    $location_id = eme_check_location_external_ref( $line['external_ref'] );
                }
                #if (!$location_id && isset($line['location_latitude']) && isset($line['location_longitude']))
                #   $location_id=eme_check_location_coord($line['location_latitude'],$line['location_longitude']);
                #if (!$location_id)
                #   $location_id=eme_check_location_name_address($line);

                if ( $location_id ) {
                    // location_id is returned if update is ok, and we use the location id later on
                    $location_id = eme_update_location( $line, $location_id );
                    if ( $location_id ) {
                        ++$updated;
                    } else {
                        ++$errors;
                        $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem updating the location in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                    }
                } else {
                    $location_id = eme_insert_location( $line );
                    if ( $location_id ) {
                        ++$inserted;
                    } else {
                        ++$errors;
                        $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem inserting the location in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                    }
                }
                if ( $location_id ) {
                    // now handle all the extra info, in the CSV they need to be named like 'answer_XX' (with 'XX' being either the fieldid or the fieldname, e.g. answer_myfieldname)
                    foreach ( $line as $key => $value ) {
                        if ( preg_match( '/^answer_(.*)$/', $key, $matches ) ) {
                            $field_name = $matches[1];
                            $formfield  = eme_get_formfield( $field_name );
                            if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'locations' ) {
                                $field_id = $formfield['field_id'];
                                $sql      = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id = %d and field_id=%d AND type='location'", $location_id, $field_id );
                                $wpdb->query( $sql );

                                $sql = $wpdb->prepare( "INSERT INTO $answers_table (related_id,field_id,answer,type) VALUES (%d,%d,%s,%s)", $location_id, $field_id, $value, 'location' );
                                $wpdb->query( $sql );
                            }
                        }
                    }
                }
            } else {
                ++$errors;
                $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (not all required fields are present): %s', 'events-made-easy' ), implode( ',', $row ) ) );
            }
        }
        $result = sprintf( __( 'Import finished: %d inserts, %d updates, %d errors', 'events-made-easy' ), $inserted, $updated, $errors );
        if ( $errors ) {
            $result .= '<br>' . $error_msg;
        }
    }
    fclose( $handle );
    return $result;
}

function eme_locations_edit_layout( $location, $message = '' ) {
    global $plugin_page;

    if ( ! isset( $location['location_id'] ) ) {
        $action = 'add';
    } else {
        $action = 'edit';
    }
    $nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
?>
    <div class="wrap">
    <?php if ( $message != '' ) { ?>
        <div id="message" class="notice is-dismissible eme-message-admin">
        <p><?php echo $message; ?></p>
        </div>
    <?php } ?>
        <div id="eme-location-changed" class='notice is-dismissible eme-message-admin' style="display:none;">
        <p><?php esc_html_e( 'The location details have changed. Please verify the coordinates and press Save when done', 'events-made-easy' ); ?></p>
        </div>
    <form enctype="multipart/form-data" name="locationForm" id="locationForm" autocomplete="off" method="post" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>" class="validate">
    <?php echo $nonce_field; ?>
    <?php if ( $action == 'add' ) { ?>
    <input type="hidden" name="eme_admin_action" value="do_addlocation">
    <?php } else { ?>
    <input type="hidden" name="eme_admin_action" value="do_editlocation">
    <?php } ?>
        <div id="icon-locations" class="icon32"></div>
        <h1>
<?php
    if ( $action == 'add' ) {
        esc_html_e( 'Insert New Location', 'events-made-easy' );
    } else {
        echo sprintf( __( "Edit Location '%s'", 'events-made-easy' ), eme_translate( $location['location_name'] ) );
    }
?>
        </h1>
        <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <!-- MAIN -->
            <div id="post-body-content">
    <div class="eme-tabs">
        <div class="eme-tab" data-tab="tab-locationdetails"><?php esc_html_e( 'Location', 'events-made-easy' ); ?></div>
    <?php if ( get_option( 'eme_attributes_enabled' ) ) : ?>
        <div class="eme-tab" data-tab="tab-locationattributes"><?php esc_html_e( 'Attributes', 'events-made-easy' ); ?></div>
    <?php endif; ?>
        <div class="eme-tab" data-tab="tab-locationcustomfields"><?php esc_html_e( 'Custom fields', 'events-made-easy' ); ?></div>
    </div>
    <div class="eme-tab-content" id="tab-locationdetails">
<?php
    eme_meta_box_div_location_name( $location );
    eme_meta_box_div_location_details( $location );
    eme_meta_box_div_location_notes( $location );
    eme_meta_box_div_location_image( $location );
    eme_meta_box_div_location_url( $location );
?>
    </div>
    <?php if ( get_option( 'eme_attributes_enabled' ) ) : ?>
    <div class="eme-tab-content" id="tab-locationattributes">
<?php
    eme_meta_box_div_location_attributes( $location );
?>
    </div>
    <?php endif; ?>
    <div class="eme-tab-content" id="tab-locationcustomfields">
<?php
    eme_meta_box_div_location_customfields( $location );
?>
    </div>

<p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php if ( $action == 'add' ) { esc_html_e( 'Add location', 'events-made-easy' ); } else { esc_html_e( 'Update location', 'events-made-easy' ); } ?>"></p>
</div>
<!-- END OF MAIN -->
<!-- SIDEBAR -->
    <div id="postbox-container-1" class="postbox-container">
        <div id='side-sortables' class="meta-box-sortables ui-sortable">
            <!-- author postbox -->
<?php
    if ( $action == 'edit' ) {
        $location_author = $location['location_author'];
    } else {
        $location_author = get_current_user_id();
    }
?>
            <div class="postbox" id="eme_authordiv">
            <h2 class='hndle'><span><?php esc_html_e( 'Author', 'events-made-easy' ); ?></span></h2>
                <div class="inside">
                <p><?php esc_html_e( 'Author of this location: ', 'events-made-easy' ); ?>
<?php
    wp_dropdown_users(
        [
            'name'     => 'location_author',
            'selected' => $location_author,
        ]
    );
?>
                </p>
                </div>
            </div>
            <!-- categories postbox -->
            <div class="postbox" id="eme_categoriesdiv">
            <h2 class='hndle'><span><?php esc_html_e( 'Category', 'events-made-easy' ); ?></span></h2>
                <div class="inside">
<?php
    $categories = eme_get_categories();
    if ( empty( $categories ) ) {
?>
                        <span><?php esc_html_e( 'No categories defined.', 'events-made-easy' ); ?></span>
<?php
    } else {
        foreach ( $categories as $category ) {
            if ( $location['location_category_ids'] && in_array( $category['category_id'], explode( ',', $location['location_category_ids'] ) ) ) {
                $selected = "checked='checked'";
            } else {
                $selected = '';
            }
?>
                            <input type="checkbox" name="location_category_ids[]" value="<?php echo $category['category_id']; ?>" <?php echo $selected; ?>><?php echo eme_trans_esc_html( $category['category_name'] ); ?><br>
<?php
        } // end foreach
    } // end if
?>
                </div>
            </div>
        </div>
    </div>
    <!-- END OF SIDEBAR -->
</div>
</div>
</form>
</div>
<?php
}

function eme_meta_box_div_location_name( $location ) {
    if ( empty( $location['location_id'] ) ) {
        $action                  = 'add';
        $location['location_id'] = 0;
    } else {
        $action = 'edit';
    }
    echo "<input type='hidden' id='location_id' name='location_id' value='" . intval( $location['location_id'] ) . "'>";
?>
<div id="titlediv">
    <?php if ( $action == 'edit' ) { ?>
        <b> <?php esc_html_e( 'Location name', 'events-made-easy' ); ?> </b>
    <?php } ?>
    <input name="location_name" id="location_name" type="text" required="required" placeholder="<?php esc_attr_e( 'Location name', 'events-made-easy' ); ?>" value="<?php echo esc_html( $location['location_name'] ); ?>" size="40">
    <br>
    <br>
<?php
    if ( $action == 'edit' ) {
        echo '<b>' . esc_html__( 'Permalink: ', 'events-made-easy' ) . '</b>';
    } else {
        echo '<b>' . esc_html__( 'Permalink prefix: ', 'events-made-easy' ) . '</b>';
    }
    echo trailingslashit( home_url() );
    $locations_prefixes = get_option( 'eme_permalink_locations_prefix', 'locations' );
    if ( preg_match( '/,/', $locations_prefixes ) ) {
        $locations_prefixes     = explode( ',', $locations_prefixes );
        $locations_prefixes_arr = [];
        foreach ( $locations_prefixes as $locations_prefix ) {
            $locations_prefixes_arr[ $locations_prefix ] = eme_permalink_convert( $locations_prefix );
        }
        $prefix = $location['location_prefix'] ? $location['location_prefix'] : '';
        echo eme_ui_select( $prefix, 'location_prefix', $locations_prefixes_arr );
    } else {
        echo eme_permalink_convert( $locations_prefixes );
    }
    if ( $action == 'edit' ) {
        $slug = $location['location_slug'] ? $location['location_slug'] : $location['location_name'];
        $slug = eme_permalink_convert_noslash( $slug );
?>
        <input type="text" id="slug" name="location_slug" value="<?php echo $slug; ?>"><?php echo user_trailingslashit( '' ); ?>
<?php
    }
?>
</div>
<?php
}

function eme_meta_box_div_location_name_for_event( $location ) {
    if ( empty( $location['location_id'] ) ) {
        $edit_link = "<img id='img_edit_location' name='img_edit_location' src='" . esc_url(EME_PLUGIN_URL) . "images/edit.png' alt='" . esc_attr__( 'Add location', 'events-made-easy' ) . "' title='" . __( 'Add location', 'events-made-easy' ) . "' style='cursor: pointer;'>";
        $location['location_id'] = 0;
    } else {
        $edit_link = "<img id='img_edit_location' name='img_edit_location' src='" . esc_url(EME_PLUGIN_URL) . "images/edit.png' alt='" . esc_attr__( 'Edit location', 'events-made-easy' ) . "' title='" . __( 'Edit location', 'events-made-easy' ) . "' style='cursor: pointer;'>";
    }
    echo "<input type='hidden' id='location_id' name='location_id' value='" . intval( $location['location_id'] ) . "'>";
?>
        <div id="loc_name" class="postbox">
            <h3>
                <?php esc_html_e( 'Location name', 'events-made-easy' ); ?>
            </h3>
            <div class="inside">
            <input name="location_name" id="location_name" type="text" placeholder="<?php esc_attr_e( 'Location name', 'events-made-easy' ); ?>" value="<?php echo esc_html( $location['location_name'] ); ?>" size="40"> <?php echo $edit_link; ?>
            <br><span class="eme_smaller"><?php esc_html_e( 'This is an autocomplete field. If a name of an existing location matches, it will be suggested.', 'events-made-easy' ); ?></span>
            </div>
        </div>
<?php
}

function eme_meta_box_div_location_details( $location ) {
    $map_is_active = get_option( 'eme_map_is_active' );
    $eme_loc_prop_override_loc_checked = ( $location['location_properties']['override_loc'] ) ? "checked='checked'" : '';
?>
        <div id="loc_address" class="postbox" style="overflow: hidden;">
            <h3>
                <?php esc_html_e( 'Location address', 'events-made-easy' ); ?>
            </h3>
            <div class="inside" style="float:left; width:50%">
            <table><tr>
            <td><label for="location_address1"><?php echo eme_translate( get_option( 'eme_address1_string' ) ); ?></label></td>
            <td><input id="location_address1" name="location_address1" type="text" value="<?php echo eme_esc_html( $location['location_address1'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_address2"><?php echo eme_translate( get_option( 'eme_address2_string' ) ); ?></label></td>
            <td><input id="location_address2" name="location_address2" type="text" value="<?php echo eme_esc_html( $location['location_address2'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_city"><?php esc_html_e( 'City', 'events-made-easy' ); ?></label></td>
            <td><input name="location_city" id="location_city" type="text" value="<?php echo eme_esc_html( $location['location_city'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_state"><?php esc_html_e( 'State', 'events-made-easy' ); ?></label></td>
            <td><input name="location_state" id="location_state" type="text" value="<?php echo eme_esc_html( $location['location_state'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_zip"><?php esc_html_e( 'Postal code', 'events-made-easy' ); ?></label></td>
            <td><input name="location_zip" id="location_zip" type="text" value="<?php echo eme_esc_html( $location['location_zip'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_country"><?php esc_html_e( 'Country', 'events-made-easy' ); ?></label></td>
            <td><input name="location_country" id="location_country" type="text" value="<?php echo eme_esc_html( $location['location_country'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td colspan='2'>
            <?php esc_html_e( "If you're are really serious about the correct location, specify the latitude and longitude coordinates.", 'events-made-easy' ); ?>
            </td>
            </tr>
            <tr>
                <td><label for="eme_loc_prop_override_loc"><?php esc_html_e( 'Override location coordinates', 'events-made-easy' ); ?></label></td>
                <td><input id="eme_loc_prop_override_loc" name='eme_loc_prop_override_loc' value='1' type='checkbox' <?php echo $eme_loc_prop_override_loc_checked; ?>>
            </tr>
            <tr>
            <td><label for="location_latitude"><?php esc_html_e( 'Latitude', 'events-made-easy' ); ?></label></td>
            <td><input id="location_latitude" name="location_latitude" type="text" value="<?php echo eme_esc_html( $location['location_latitude'] ); ?>" size="40"></td>
            </tr>
            <tr>
            <td><label for="location_longitude"><?php esc_html_e( 'Longitude', 'events-made-easy' ); ?></label></td>
            <td><input id="location_longitude" name="location_longitude" type="text" value="<?php echo eme_esc_html( $location['location_longitude'] ); ?>" size="40"></td>
            </tr></table>
            </div>
            <div class="inside" style="float:left;">
<?php
    if ( $map_is_active ) {
?>
        <div id='eme-edit-location-map' class='eme-adminedit-location-map'></div></td>
<?php
    }
?>
            </div>
        </div>

<?php
    if ( $map_is_active ) :
?>

        <div id="loc_map_icon" class="postbox">
            <h3>
            <?php esc_html_e( 'Location map icon url', 'events-made-easy' ); ?>
            </h3>
            <div class="inside">
            <table><tr>
            <td><label for="eme_loc_prop_map_icon"><?php esc_html_e( 'Map icon url', 'events-made-easy' ); ?></label></td>
        <td><input id="eme_loc_prop_map_icon" name="eme_loc_prop_map_icon" type="text" value="<?php echo eme_esc_html( $location['location_properties']['map_icon'] ); ?>" size="40">
        <br><?php esc_html_e( "By default a regular pin is shown on the map where the location is. If you don't like the default, you can set another map icon here.", 'events-made-easy' ); ?>
        <br><?php esc_html_e( 'Size should be 32x32, bottom center will be pointing to the location on the map.', 'events-made-easy' ); ?>
            </td>
            </tr></table>
            </div>
        </div>

<?php
        if ( function_exists( 'qtrans_getLanguage' ) || function_exists( 'ppqtrans_getLanguage' ) || defined( 'ICL_LANGUAGE_CODE' ) ) :
?>
        <div id="loc_qtrans_warning" class="postbox"><?php esc_html_e( "Because qtranslate or a derivate is active, the title of the location might not update automatically in the balloon, so don't panic there.", 'events-made-easy' ); ?> </div>
        <?php endif; ?>
        <?php endif; ?>

        <div id="loc_max_capacity" class="postbox">
            <h3>
            <?php esc_html_e( 'Location maximum capacity', 'events-made-easy' ); ?>
            </h3>
            <div class="inside">
            <table><tr>
            <td><label for="eme_loc_prop_max_capacity"><?php esc_html_e( 'Max capacity', 'events-made-easy' ); ?></label></td>
        <td><input id="eme_loc_prop_max_capacity" name="eme_loc_prop_max_capacity" type="text" value="<?php echo $location['location_properties']['max_capacity']; ?>" size="40">
        <br><?php esc_html_e( "If setting the max capacity to something else than 0, then - for all events that are happening at the same time at this location - a check will be done to see if the location still allows extra people inside.", 'events-made-easy' ); ?>
            </td>
            </tr></table>
            </div>
        </div>
<?php
}

function eme_meta_box_div_location_notes( $location ) {
?>
        <div class="postbox" id="loc_description">
            <h3>
                <?php esc_html_e( 'Location description', 'events-made-easy' ); ?>
            </h3>
            <div class="inside">
                <div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
<?php
    eme_wysiwyg_textarea( 'location_description', $location['location_description'], 1, 1 );
    if ( current_user_can( 'unfiltered_html' ) ) {
        echo "<div class='eme_notice_unfiltered_html'>";
        esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
        echo '</div>';
    }
?>
                </div>
                <?php esc_html_e( 'A description of the location. You may include any kind of info here.', 'events-made-easy' ); ?>
                <br>
            </div>
        </div>
<?php
}

function eme_meta_box_div_location_image( $location ) {
    if ( ! empty( $location['location_image_id'] ) ) {
        $location['location_image_url'] = esc_url( wp_get_attachment_image_url( $location['location_image_id'], 'full' ) );
    }
?>
<div id="div_location_image" class="postbox">
    <h3>
        <?php esc_html_e( 'Location image', 'events-made-easy' ); ?>
    </h3>
    <div id="location_current_image_inside" class="inside">
<?php
    if ( ! empty( $location['location_image_url'] ) ) {
        echo "<img id='eme_location_image_example' alt='" . esc_attr__( 'Location image', 'events-made-easy' ) . "' src='" . $location['location_image_url'] . "' width='200'>";
        echo "<input type='hidden' name='location_image_url' id='location_image_url' value='" . $location['location_image_url'] . "'>";
    } else {
        # to prevent html validation errors, use a transparent small pixel
        echo "<img id='eme_location_image_example' alt='" . esc_attr__( 'Location image', 'events-made-easy' ) . "' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=' width='200'>";
        echo "<input type='hidden' name='location_image_url' id='location_image_url'>";
    }
    if ( ! empty( $location['location_image_id'] ) ) {
        echo "<input type='hidden' name='location_image_id' id='location_image_id' value='" . $location['location_image_id'] . "'>";
    } else {
        echo "<input type='hidden' name='location_image_id' id='location_image_id'>";
    }
?>
    <div class="uploader">
    <input type="button" name="location_image_button" id="location_image_button" value="<?php esc_attr_e( 'Set a featured image', 'events-made-easy' ); ?>" class="button-secondary action">
    <input type="button" id="location_remove_image_button" name="location_remove_image_button" value=" <?php esc_attr_e( 'Unset featured image', 'events-made-easy' ); ?>" class="button-secondary action">
    </div>
    </div>
</div>
<?php
}

function eme_meta_box_div_location_url( $location ) {
    $eme_loc_prop_online_only_checked = ( $location['location_properties']['online_only'] ) ? "checked='checked'" : '';

?>
<div id="div_location_url" class="postbox">
    <h3>
        <?php esc_html_e( 'External info', 'events-made-easy' ); ?>
    </h3>
    <div id="div_location_url_inside" class="inside">
    <table>
    <tr>
    <td><label for="location_url"><?php esc_html_e( 'External URL', 'events-made-easy' ); ?></label></td>
    <td><input id="location_url" name="location_url" type="text" value="<?php echo eme_esc_html( $location['location_url'] ); ?>" size="40">
    </td>
    </tr>
    <tr>
    <td><label for="eme_loc_prop_online_only"><?php esc_html_e( 'Only online location', 'events-made-easy' ); ?></label></td>
    <td><input id="eme_loc_prop_online_only" name='eme_loc_prop_online_only' value='1' type='checkbox' <?php echo $eme_loc_prop_online_only_checked; ?>>
    <br><span class="eme_smaller"><?php esc_html_e( 'Check this is the location is purely virtual (like a meeting url or so).', 'events-made-easy' ); ?></span>
    </td>
    </tr>
    </table>
    </div>
</div>
<?php
}

function eme_meta_box_div_location_attributes( $location ) {
?>
<div id="div_location_attributes">
    <br>
<?php
    echo '<b>' . esc_html__( 'Attributes', 'events-made-easy' ) . '</b>';
?>
<?php
    eme_attributes_form( $location );
?>
</div>
<?php
}

function eme_meta_box_div_location_customfields( $location ) {
    $formfields = eme_get_formfields( '', 'locations' );
    $formfields = apply_filters( 'eme_location_formfields', $formfields );
?>
<div id="div_location_customfields">
        <br><b> <?php esc_html_e( 'Custom fields', 'events-made-easy' ); ?> </b>
        <p><?php esc_html_e( "Here custom fields of type 'locations' are shown.", 'events-made-easy' ); ?>
        <br><?php esc_html_e( 'The difference with location attributes is that attributes need to be defined in your format first and can only be text, here you can first create custom fields of any kind which allows more freedom.', 'events-made-easy' ); ?>
        </p>
<?php
    if ( current_user_can( 'unfiltered_html' ) && !empty($formfields) ) {
        echo "<div class='eme_notice_unfiltered_html'>";
        esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
        echo '</div>';
    }
?>
    <table style="width: 100%;">
<?php
    // only in case of location duplicate, the cf_answers is set
    if ( isset( $location['cf_answers'] ) ) {
        $answers = $location['cf_answers'];
        $files   = [];
    } elseif ( ! empty( $location['location_id'] ) ) {
        $answers = eme_get_location_answers( $location['location_id'] );
        $files = eme_get_uploaded_files( $location['location_id'], 'locations' );
    } else {
        $answers = [];
        $files   = [];
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
            $field_html = __( "Custom fields of type 'hidden' are useless here and of course won't be shown.", 'events-made-easy' );
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


function eme_locations_table( $message = '' ) {
    $nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );

?>
    <div class="wrap nosubsub">
    <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <?php if ( current_user_can( get_option( 'eme_cap_add_locations' ) ) ) : ?>
        <h1><?php esc_html_e( 'Add a new location', 'events-made-easy' ); ?></h1>
        <div class="wrap">
        <form id="locations-filter" method="post" action="<?php echo admin_url( 'admin.php?page=eme-locations' ); ?>">
            <?php echo $nonce_field; ?>
            <input type="hidden" name="eme_admin_action" value="add_location">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add location', 'events-made-easy' ); ?>">
        </form>
        </div>
    <?php endif; ?>

        <h1><?php esc_html_e( 'Manage locations', 'events-made-easy' ); ?></h1>
        <?php if ( $message != '' ) { ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
        <?php } ?>

        <?php if ( current_user_can( get_option( 'eme_cap_cleanup' ) ) ) { ?>
        <span class="eme_import_form_img">
            <?php esc_html_e( 'Click on the icon to show the import form', 'events-made-easy' ); ?>
        <img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="div_import" style="cursor: pointer; vertical-align: middle; ">
        </span>
        <div id='div_import' style='display:none;'>
        <form id='location-import' method='post' enctype='multipart/form-data' action='#'>
            <?php echo $nonce_field; ?>
        <input type="file" name="eme_csv">
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
        <input type="text" size=1 maxlength=1 name="delimiter" value=',' required='required'>
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
        <input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"' required='required'>
        <input type="hidden" name="eme_admin_action" value="import_locations">
        <input type="submit" value="<?php esc_html_e( 'Import', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
            <?php esc_html_e( 'If you want, use this to import locations into the database', 'events-made-easy' ); ?>
        </form>
        </div>
        <?php } ?>

    <form action="#" method="post">
    <input type="search" name="search_name" id="search_name" placeholder="<?php esc_attr_e( 'Location name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=10>
<?php
    $formfields_searchable = eme_get_searchable_formfields( 'locations' );
    if ( ! empty( $formfields_searchable ) ) {
        echo '<input type="search" name="search_customfields" id="search_customfields" placeholder="' . esc_attr__( 'Custom field value to search', 'events-made-easy' ) . '" size=20>';
        $label = __( 'Custom fields to filter on', 'events-made-easy' );
        $extra_attributes = 'aria-label="' . eme_esc_html( $label ) . '" data-placeholder="' . eme_esc_html( $label ) . '"';
        echo eme_ui_multiselect_key_value( '', 'search_customfieldids', $formfields_searchable, 'field_id', 'field_name', 5, $label, 0, 'eme_select2_fitcontent', $extra_attributes, 1 );
    }
?>
    <button id="LocationsLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter location', 'events-made-easy' ); ?></button>
<?php
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

    <div id="bulkactions">
    <form action="#" method="post">
    <?php echo $nonce_field; ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteLocations"><?php esc_html_e( 'Delete selected locations', 'events-made-easy' ); ?></option>
    </select>
    <span id="span_transferto" class="eme-hidden">
    <?php esc_html_e( 'Transfer associated events to (leave empty to delete the location info for those events):', 'events-made-easy' ); ?>
    <input type='hidden' id='transferto_id' name='transferto_id'>
    <input type='text' id='chooselocation' name='chooselocation' placeholder="<?php esc_attr_e( 'Start typing a name', 'events-made-easy' ); ?>">
    </span>
    <button id="LocationsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <span class="rightclickhint" id="colvis">
        <?php esc_html_e( 'Hint: rightclick on the column headers to show/hide columns', 'events-made-easy' ); ?>
    </span>
    </form>
    </div>
<?php
    $formfields               = eme_get_formfields( '', 'locations' );
    $extrafields_arr          = [];
    $extrafieldnames_arr      = [];
    $extrafieldsearchable_arr = [];
    foreach ( $formfields as $formfield ) {
        $extrafields_arr[]          = $formfield['field_id'];
        $extrafieldnames_arr[]      = str_replace(',','&sbquo;',eme_trans_esc_html( $formfield['field_name'] ));
        $extrafieldsearchable_arr[] = $formfield['searchable'];
    }
    // these 2 values are used as data-fields to the container-div, and are used by the js to create extra columns
    $extrafields          = join( ',', $extrafields_arr );
    $extrafieldnames      = join( ',', $extrafieldnames_arr );
    $extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
?>
    <div id="LocationsTableContainer" data-extrafields='<?php echo $extrafields; ?>' data-extrafieldnames='<?php echo $extrafieldnames; ?>' data-extrafieldsearchable='<?php echo $extrafieldsearchable; ?>' ></div>
    </div>
    </div>

<?php
}

function eme_search_locations( $name ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $query = "SELECT * FROM $table WHERE (location_name LIKE %s) OR
        (location_description LIKE %s) ORDER BY location_name";
    $sql   = $wpdb->prepare( $query, '%'.$wpdb->esc_like($name).'%', '%'.$wpdb->esc_like($name).'%' );
    return $wpdb->get_results( $sql, ARRAY_A );
}

// this returns all locations, can be useful in dropdown for location selects
function eme_get_all_locations() {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $sql             = "SELECT * FROM $locations_table WHERE location_name != '' ORDER BY location_name";
    $locations       = $wpdb->get_results( $sql, ARRAY_A );
    foreach ( $locations as $key => $location ) {
        $locations[ $key ] = eme_get_extra_location_data( $location );
    }
    if ( has_filter( 'eme_location_list_filter' ) ) {
        $locations = apply_filters( 'eme_location_list_filter', $locations );
    }
    return $locations;
}

function eme_get_locations_by_distance( $longitude, $latitude, $distance, $location_ids_only ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    if ( $location_ids_only ) {
        $sql       = $wpdb->prepare( "SELECT location_id FROM $locations_table WHERE ST_Distance_Sphere(point(%f,%f),point(location_longitude,location_latitude)) < %d", $longitude, $latitude, $distance );
        $locations = $wpdb->get_col( $sql );
    } else {
        $sql       = $wpdb->prepare( "SELECT *, ST_Distance_Sphere(point(%f,%f),point(location_longitude,location_latitude)) AS distance_meters FROM $locations_table HAVING distance_meters < %d ORDER BY distance_meters,location_name", $longitude, $latitude, $distance );
        $locations = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $locations as $key => $location ) {
            $locations[ $key ] = eme_get_extra_location_data( $location );
        }
        if ( has_filter( 'eme_location_list_filter' ) ) {
            $locations = apply_filters( 'eme_location_list_filter', $locations );
        }
    }
    return $locations;
}

function eme_get_locations( $eventful = false, $scope = 'all', $category = '', $notcategory = '', $limit = 0, $ignore_filter = false, $random_order = false, $author = '', $contact_person = '' ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $locations       = [];

    $location_id_arr = [];
    $location_id     = '';
    // the filter list overrides the settings
    if ( ! $ignore_filter && isset( $_REQUEST['eme_eventAction'] ) && eme_sanitize_request( $_REQUEST['eme_eventAction']) == 'filter' ) {
        if ( ! empty( $_REQUEST['eme_scope_filter'] ) ) {
            $scope = eme_sanitize_request( $_REQUEST['eme_scope_filter'] );
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
                if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'locations' ) {
                    $is_multi    = eme_is_multifield( $formfield['field_type'] );
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
    if ( $location_id != -1 && ! empty( $location_id_arr ) ) {
        $location_id = join( ',', $location_id_arr );
    }

    // for the query: we don't do "SELECT *" because the data returned from this function is also used in the function eme_global_map_json()
    // and some fields from the events table contain carriage returns, which can't be passed along
    // The function eme_global_map_json tries to remove these, but the data is not needed and better be safe than sorry
    $eventful = filter_var( $eventful, FILTER_VALIDATE_BOOLEAN );
    if ( $eventful ) {
        $events = eme_get_events( scope: $scope, location_id: $location_id, category: $category, author: $author, contact_person: $contact_person, show_ongoing: 1, notcategory: $notcategory );
        if ( $events ) {
            foreach ( $events as $event ) {
                $event_location_id = $event['location_id'];
                if ( $event_location_id ) {
                    $this_location = eme_get_location( $event_location_id );
                    // the key is based on the location name first and the location id (if different locations have the same name)
                    // using this method we can then sort on the name
                    if ( ! empty( $this_location ) && $this_location['location_name'] != '' ) {
                        $locations[ $this_location['location_name'] . $event_location_id ] = $this_location;
                    }
                }
            }
            // sort on the key (name/id pair)
            ksort( $locations );
        }
    } else {
        $conditions = [];
        if ( get_option( 'eme_categories_enabled' ) ) {
            if ( is_numeric( $category ) ) {
                if ( $category > 0 ) {
                    $conditions [] = " FIND_IN_SET($category,location_category_ids)";
                }
            } elseif ( $category == 'none' ) {
                $conditions [] = "location_category_ids=''";
            } elseif ( preg_match( '/,/', $category ) ) {
                $category_arr        = explode( ',', $category );
                $category_conditions = [];
                foreach ( $category_arr as $cat ) {
                    if ( is_numeric( $cat ) && $cat > 0 ) {
                        $category_conditions[] = " FIND_IN_SET($cat,location_category_ids)";
                    } elseif ( $cat == 'none' ) {
                        $category_conditions[] = " location_category_ids=''";
                    }
                }
                $conditions [] = '(' . implode( ' OR', $category_conditions ) . ')';
            } elseif ( preg_match( '/\+/', $category ) ) {
                $category_arr        = explode( '+', $category );
                $category_conditions = [];
                foreach ( $category_arr as $cat ) {
                    if ( is_numeric( $cat ) && $cat > 0 ) {
                        $category_conditions[] = " FIND_IN_SET($cat,location_category_ids)";
                    }
                }
                $conditions [] = '(' . implode( ' AND ', $category_conditions ) . ')';
            }

            if ( is_numeric( $notcategory ) ) {
                if ( $notcategory > 0 ) {
                    $conditions[] = "(NOT FIND_IN_SET($notcategory,location_category_ids) OR location_category_ids IS NULL)";
                }
            } elseif ( $notcategory == 'none' ) {
                $conditions[] = "location_category_ids != ''";
            } elseif ( preg_match( '/,/', $notcategory ) ) {
                $notcategory_arr     = explode( ',', $notcategory );
                $category_conditions = [];
                foreach ( $notcategory_arr as $cat ) {
                    if ( is_numeric( $cat ) && $cat > 0 ) {
                        $category_conditions[] = "(NOT FIND_IN_SET($cat,location_category_ids) OR location_category_ids IS NULL)";
                    } elseif ( $cat == 'none' ) {
                        $category_conditions[] = "location_category_ids != ''";
                    }
                }
                $conditions[] = '(' . implode( ' OR ', $category_conditions ) . ')';
            } elseif ( preg_match( '/\+| /', $notcategory ) ) {
                // url decoding of '+' is ' '
                $notcategory_arr     = preg_split( '/\+| /', $notcategory, 0, PREG_SPLIT_NO_EMPTY );
                $category_conditions = [];
                foreach ( $notcategory_arr as $cat ) {
                    if ( is_numeric( $cat ) && $cat > 0 ) {
                        $category_conditions[] = "(NOT FIND_IN_SET($cat,location_category_ids) OR location_category_ids IS NULL)";
                    }
                }
                $conditions[] = '(' . implode( ' AND ', $category_conditions ) . ')';
            }
        }

        if ( ! empty( $location_id ) ) {
            $location_ids = explode( ',', $location_id );
            if ( eme_is_numeric_array( $location_ids ) ) {
                $conditions [] = "(location_id IN ($location_id))";
            }
        }

        if ( ! empty( $author ) ) {
            if ( is_numeric( $author ) ) {
                $conditions[] = 'location_author = ' . $author;
            } elseif ( $author == '#_MYSELF' ) {
                $current_userid = get_current_user_id();
                $conditions[]   = 'location_author = ' . $current_userid;
            }
        }
        // extra conditions for authors: if we're in the admin itf, return only the locations for which you have the right to change anything
        $current_userid = get_current_user_id();
        if ( eme_is_admin_request() && ! current_user_can( get_option( 'eme_cap_edit_locations' ) ) && current_user_can( get_option( 'eme_cap_author_locations' ) ) ) {
            $conditions [] = "(location_author = $current_userid)";
        }

        $where = implode( ' AND ', $conditions );
        if ( $where != '' ) {
            $where = ' AND ' . $where;
        }

        if ( $limit > 0 ) {
            $limit = " LIMIT $limit";
        } else {
            $limit = '';
        }

        if ( $random_order ) {
            $sql = "SELECT * FROM $locations_table WHERE location_name != '' $where ORDER BY RAND() $limit";
        } else {
            $sql = "SELECT * FROM $locations_table WHERE location_name != '' $where ORDER BY location_name $limit";
        }
        $locations = $wpdb->get_results( $sql, ARRAY_A );
        // don't forget the images (for the older locations that didn't use the wp gallery)
        if ( $locations ) {
            foreach ( $locations as $key => $location ) {
                $locations[ $key ] = eme_get_extra_location_data( $location );
            }
        }
    }
    if ( has_filter( 'eme_location_list_filter' ) ) {
        $locations = apply_filters( 'eme_location_list_filter', $locations );
    }
    return $locations;
}

function eme_get_location( $location_id ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;

    if ( is_string( $location_id ) && $location_id == '#_SINGLE_EVENTPAGE_LOCATIONID' && eme_is_single_event_page() ) {
        $eventid_or_slug = eme_sanitize_request( get_query_var( 'event_id' ) );
        $event               = eme_get_event( $eventid_or_slug );
        if ( ! empty( $event ) ) {
            $location_id = $event['location_id'];
        }
    }
    if ( is_string( $location_id ) && $location_id == '#_SINGLE_LOCATIONPAGE_LOCATIONID' && eme_is_single_location_page() ) {
        $location_id = eme_sanitize_request( get_query_var( 'location_id' ) );
    }

    if ( empty( $location_id ) ) {
        return false;
    } else {
        $location = wp_cache_get( "eme_location $location_id" );
        if ( $location === false ) {
            if ( is_numeric( $location_id ) ) {
                $sql = $wpdb->prepare( "SELECT * from $locations_table WHERE location_id = %d", $location_id );
            } else {
                $sql = $wpdb->prepare( "SELECT * from $locations_table WHERE location_slug = %s", $location_id );
            }
            $location = $wpdb->get_row( $sql, ARRAY_A );
            if ( $location ) {
                $location = eme_get_extra_location_data( $location );
                wp_cache_set( "eme_location $location_id", $location, '', 60 );
            }
        }
        return $location;
    }
}

function eme_get_extra_location_data( $location ) {
    foreach ( $location as $key => $val ) {
        if ( is_null( $val ) ) {
            $location[ $key ] = '';
        }
    }
    $location['location_attributes'] = eme_unserialize( $location['location_attributes'] );
    $location['location_attributes'] = ( ! is_array( $location['location_attributes'] ) ) ? [] : $location['location_attributes'];

    $location['location_properties'] = eme_unserialize( $location['location_properties'] );
    $location['location_properties'] = ( ! is_array( $location['location_properties'] ) ) ? [] : $location['location_properties'];
    $location['location_properties'] = eme_init_location_props( $location['location_properties'] );

    if ( has_filter( 'eme_location_filter' ) ) {
        $location = apply_filters( 'eme_location_filter', $location );
    }

    return $location;
}

function eme_get_city_location_ids( $cities ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $location_ids    = [];
    $conditions      = '';
    if ( is_array( $cities ) ) {
        $city_conditions = [];
        foreach ( $cities as $city ) {
            $city_conditions[] = " location_city = '" . esc_sql( $city ) . "'";
        }
        $conditions = '(' . implode( ' OR', $city_conditions ) . ')';
    } elseif ( ! empty( $cities ) ) {
        $conditions = " location_city = '" . esc_sql( $cities ) . "'";
    }
    if ( ! empty( $conditions ) ) {
        $sql          = "SELECT DISTINCT location_id FROM $locations_table WHERE " . $conditions;
        $location_ids = $wpdb->get_col( $sql );
    }
    return $location_ids;
}

function eme_get_country_location_ids( $countries ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $location_ids    = [];
    $conditions      = '';
    if ( is_array( $countries ) ) {
        $country_conditions = [];
        foreach ( $countries as $country ) {
            $country_conditions[] = " location_country = '" . esc_sql( $country ) . "'";
        }
        $conditions = '(' . implode( ' OR', $country_conditions ) . ')';
    } elseif ( ! empty( $countries ) ) {
        $conditions = " location_country = '" . esc_sql( $countries ) . "'";
    }
    if ( ! empty( $conditions ) ) {
        $sql          = "SELECT DISTINCT location_id FROM $locations_table WHERE " . $conditions;
        $location_ids = $wpdb->get_col( $sql );
    }
    return $location_ids;
}

function eme_get_identical_location_id( $location ) {
    global $wpdb;
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $prepared_sql    = $wpdb->prepare( "SELECT location_id FROM $locations_table WHERE location_name = %s AND location_address1 = %s AND location_address2 = %s AND location_city = %s AND location_state = %s AND location_zip = %s AND location_country = %s AND location_latitude = %s AND location_longitude = %s LIMIT 1", stripcslashes( $location['location_name'] ), stripcslashes( $location['location_address1'] ), stripcslashes( $location['location_address2'] ), stripcslashes( $location['location_city'] ), stripcslashes( $location['location_state'] ), stripcslashes( $location['location_zip'] ), stripcslashes( $location['location_country'] ), stripcslashes( $location['location_latitude'] ), stripcslashes( $location['location_longitude'] ) );
    return $wpdb->get_var( $prepared_sql );
}

function eme_sanitize_location( $location ) {
    // remove possible unwanted fields
    if ( isset( $location['location_id'] ) ) {
        unset( $location['location_id'] );
    }

    // check all variables that need to be urls
    $url_vars = [ 'location_url', 'location_image_url' ];
    foreach ( $url_vars as $url_var ) {
        if ( ! empty( $location[ $url_var ] ) ) {
            //make sure url's have a correct prefix
            $parsed = parse_url( $location[ $url_var ] );
            if ( empty( $parsed['scheme'] ) ) {
                $scheme               = is_ssl() ? 'https://' : 'http://';
                $location[ $url_var ] = $scheme . ltrim( $location[ $url_var ], '/' );
            }
            //make sure url's are correctly escaped
            $location[ $url_var ] = esc_url_raw( $location[ $url_var ] );
        }
    }

    if ( empty( $location['location_longitude'] ) ) {
        $location['location_longitude'] = '';
    }
    if ( empty( $location['location_latitude'] ) ) {
        $location['location_latitude'] = '';
    }

    if ( ! empty( $location['location_slug'] ) ) {
        $location['location_slug'] = eme_permalink_convert_noslash( $location['location_slug'] );
    } else {
        $location['location_slug'] = eme_permalink_convert_noslash( $location['location_name'] );
    }

    // some things just need to be integers, let's brute-force them
    $int_vars = [ 'location_image_id', 'location_author' ];
    foreach ( $int_vars as $int_var ) {
        if ( isset( $location[ $int_var ] ) ) {
            $location[ $int_var ] = intval( $location[ $int_var ] );
        }
    }

    return eme_kses( $location );
}

function eme_validate_location( $location ) {
    $location_required_fields = [
        'location_name'     => __( 'The location name', 'events-made-easy' ),
        'location_address1' => __( 'The location address', 'events-made-easy' ),
        'location_city'     => __( 'The location city', 'events-made-easy' ),
    ];
    $troubles                 = '';
    if ( empty( $location['location_name'] ) ) {
        $troubles .= '<li>' . sprintf( __( '%s is missing!', 'events-made-easy' ), $location_required_fields['location_name'] ) . '</li>';
    }
    if ( empty( $location['location_longitude'] ) && empty( $location['location_longitude'] ) && ! $location['location_properties']['online_only'] ) {
        if ( empty( $location['location_address1'] ) ) {
            $troubles .= '<li>' . sprintf( __( '%s is missing!', 'events-made-easy' ), $location_required_fields['location_address1'] ) . '</li>';
        }
        if ( empty( $location['location_city'] ) ) {
            $troubles .= '<li>' . sprintf( __( '%s is missing!', 'events-made-easy' ), $location_required_fields['location_city'] ) . '</li>';
        }
    }

    if ( empty( $troubles ) ) {
        return 'OK';
    } else {
        $message = __( 'Ach, some problems here:', 'events-made-easy' ) . "<ul>\n$troubles</ul>";
        return $message;
    }
}

function eme_update_location( $line, $location_id ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;

    if ( empty( $line['location_author'] ) ) {
        $line['location_author'] = get_current_user_id();
    }
    if ( empty( $line['location_slug'] ) ) {
        $line['location_slug'] = eme_permalink_convert_noslash( $line['location_name'] );
    }
    $line['location_slug'] = eme_unique_slug( $line['location_slug'], EME_LOCATIONS_TBNAME, 'location_slug', 'location_id', $location_id );

    $location = eme_new_location();
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys                            = array_intersect_key( $line, $location );
    $new_line                        = array_merge( $location, $keys );
    $new_line['location_attributes'] = eme_serialize( $new_line['location_attributes'] );
    $new_line['location_properties'] = eme_serialize( $new_line['location_properties'] );

    $where = [ 'location_id' => $location_id ];
    if ( $wpdb->update( $table_name, $new_line, $where ) === false ) {
        return false;
    } else {
        wp_cache_delete( "eme_location $location_id" );
        return $location_id;
    }
}

function eme_insert_location( $line, $force = 0 ) {
    // the force parameter can be used to ignore capabilities for a user when inserting a location
    // the frontend submit plugin can use this
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;

    if ( empty( $line['location_author'] ) ) {
        $line['location_author'] = get_current_user_id();
    }
    if ( empty( $line['location_slug'] ) ) {
        $line['location_slug'] = eme_permalink_convert_noslash( $line['location_name'] );
    }
    $line['location_slug'] = eme_unique_slug( $line['location_slug'], EME_LOCATIONS_TBNAME, 'location_slug', 'location_id' );

    $location = eme_new_location();
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys     = array_intersect_key( $line, $location );
    $new_line = array_merge( $location, $keys );
    if ( has_filter( 'eme_insert_location_filter' ) ) {
        $new_line = apply_filters( 'eme_insert_location_filter', $new_line );
    }
    $new_line['location_attributes'] = eme_serialize( $new_line['location_attributes'] );
    $new_line['location_properties'] = eme_serialize( $new_line['location_properties'] );

    if ( current_user_can( get_option( 'eme_cap_add_locations' ) ) || $force ) {
        if ( ! $wpdb->insert( $table_name, $new_line ) ) {
            return false;
        } else {
            $location_id = $wpdb->insert_id;
            return $location_id;
        }
    } else {
        return false;
    }
}

function eme_delete_location( $location_id, $transfer_id = 0 ) {
    global $wpdb;

    // don't delete the location transferring to
    if ( $location_id == $transfer_id ) {
        return;
    }

    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $sql        = $wpdb->prepare( "DELETE FROM $table_name where location_id=%d", $location_id );
    $wpdb->query( $sql );

    $events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
    $sql          = $wpdb->prepare( "UPDATE $events_table SET location_id=%d WHERE location_id = %d", $transfer_id, $location_id );
    $wpdb->query( $sql );

    eme_delete_location_answers( $location_id );
    eme_delete_uploaded_files( $location_id, 'locations' );
}

function eme_delete_location_answers( $location_id ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $sql           = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id=%d AND type='location'", $location_id );
    $wpdb->query( $sql );
}


function eme_check_location_external_ref( $id ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT location_id FROM $table_name WHERE location_external_ref = %s", $id );
    return $wpdb->get_var( $sql );
}
function eme_check_location_coord( $lat, $long ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $sql        = $wpdb->prepare( "SELECT location_id FROM $table_name WHERE location_latitude = %s AND location_longitude = %s", $lat, $long );
    return $wpdb->get_var( $sql );
}

function eme_check_location_name_address( $location ) {
    global $wpdb;
    $table_name = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    if ( ! isset( $location['location_address2'] ) ) {
        $location['location_address2'] = '';
    }
    if ( ! isset( $location['location_city'] ) ) {
        $location['location_city'] = '';
    }
    if ( ! isset( $location['location_state'] ) ) {
        $location['location_state'] = '';
    }
    if ( ! isset( $location['location_zip'] ) ) {
        $location['location_zip'] = '';
    }
    if ( ! isset( $location['location_country'] ) ) {
        $location['location_country'] = '';
    }
    $sql = $wpdb->prepare( "SELECT location_id FROM $table_name WHERE location_name = %s AND location_address1 = %s AND location_address2 = %s AND location_city = %s AND location_state = %s AND location_zip = %s AND location_country = %s LIMIT 1", stripcslashes( $location['location_name'] ), stripcslashes( $location['location_address1'] ), stripcslashes( $location['location_address2'] ), stripcslashes( $location['location_city'] ), stripcslashes( $location['location_state'] ), stripcslashes( $location['location_zip'] ), stripcslashes( $location['location_country'] ) );
    return $wpdb->get_var( $sql );
}

function eme_location_has_events( $location_id ) {
    global $wpdb;
    $events_table = EME_DB_PREFIX . EME_EVENTS_TBNAME;
    if ( ! eme_is_admin_request() ) {
        if ( is_user_logged_in() ) {
            $condition = 'AND event_status IN (' . EME_EVENT_STATUS_PUBLIC . ',' . EME_EVENT_STATUS_PRIVATE . ')';
        } else {
            $condition = 'AND event_status=' . EME_EVENT_STATUS_PUBLIC;
        }
    }

    $sql             = "SELECT COUNT(event_id) FROM $events_table WHERE location_id = $location_id $condition";
    $affected_events = $wpdb->get_results( $sql );
    return ( $affected_events > 0 );
}

function eme_global_map_shortcode( $atts ) {
    global $post;
    eme_enqueue_frontend();

    if ( get_option( 'eme_map_is_active' ) ) {
        $defaults = [
            'show_locations'    => true,
            'letter_icons'      => true,
            'show_events'       => false,
            'eventful'          => false,
            'marker_clustering' => false,
            'ignore_filter'     => false,
            'scope'             => 'all',
            'paging'            => 0,
            'category'          => '',
            'notcategory'       => '',
            'author'            => '',
            'contact_person'    => '',
            'width'             => 450,
            'height'            => 300,
            'list_location'     => 'after',
        ];
        $atts = shortcode_atts( $defaults, $atts );

        $eventful          = filter_var( $atts['eventful'], FILTER_VALIDATE_BOOLEAN );
        $show_events       = filter_var( $atts['show_events'], FILTER_VALIDATE_BOOLEAN );
        $show_locations    = filter_var( $atts['show_locations'], FILTER_VALIDATE_BOOLEAN );
        $marker_clustering = filter_var( $atts['marker_clustering'], FILTER_VALIDATE_BOOLEAN );
        $ignore_filter     = filter_var( $atts['ignore_filter'], FILTER_VALIDATE_BOOLEAN );
        $letter_icons      = filter_var( $atts['letter_icons'], FILTER_VALIDATE_BOOLEAN );
        $scope             = eme_sanitize_request( $atts['scope'] );
        $width             = eme_sanitize_request( $atts['width'] );
        $height            = eme_sanitize_request( $atts['height'] );

        wp_enqueue_style( 'eme-leaflet-css' );
        if ( $marker_clustering ) {
            wp_enqueue_script( 'eme-leaflet-markercluster' );
            wp_enqueue_style( 'eme-markercluster-css1' );
            wp_enqueue_style( 'eme-markercluster-css2' );
        }
        if ( get_option( 'eme_map_gesture_handling' ) ) {
            wp_enqueue_script( 'eme-leaflet-gestures' );
            wp_enqueue_style( 'eme-gestures-css' );
        }
        wp_enqueue_script( 'eme-show-maps' );

        $result           = '';
        $prev_text        = '';
        $next_text        = '';
        $scope_offset     = 0;

        if ( $eventful && $atts['paging'] == 1 ) {
            $eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );

            if ( isset( $_GET['eme_offset'] ) ) {
                $scope_offset = intval( $_GET['eme_offset'] );
            }
            $prev_offset = $scope_offset - 1;
            $next_offset = $scope_offset + 1;

            if ( $scope == 'this_week' ) {
                $start_of_week = get_option( 'start_of_week' );
                $eme_date_obj->setWeekStartDay( $start_of_week );
                $eme_date_obj->modifyWeeks( $scope_offset );
                $limit_start = $eme_date_obj->startOfWeek()->format( 'Y-m-d' );
                $limit_end   = $eme_date_obj->endOfWeek()->format( 'Y-m-d' );
                $scope       = "$limit_start--$limit_end";
                $scope_text  = eme_localized_date( $limit_start, EME_TIMEZONE ) . ' -- ' . eme_localized_date( $limit_end, EME_TIMEZONE );
                $prev_text   = __( 'Previous week', 'events-made-easy' );
                $next_text   = __( 'Next week', 'events-made-easy' );

            } elseif ( $scope == 'this_year' ) {
                $eme_date_obj->modifyYears( $scope_offset );
                $year        = $eme_date_obj->getYear();
                $limit_start = "$year-01-01";
                $limit_end   = "$year-12-31";
                $scope       = "$limit_start--$limit_end";
                $scope_text  = eme_localized_date( $limit_start, EME_TIMEZONE, get_option( 'eme_show_period_yearly_dateformat' ) );
                $prev_text   = __( 'Previous year', 'events-made-easy' );
                $next_text   = __( 'Next year', 'events-made-easy' );

            } elseif ( $scope == 'today' ) {
                $scope       = $eme_date_obj->modifyDays( $scope_offset )->format( 'Y-m-d' );
                $limit_start = $scope;
                $limit_end   = $scope;
                $scope_text  = eme_localized_date( $limit_start, EME_TIMEZONE );
                $prev_text   = __( 'Previous day', 'events-made-easy' );
                $next_text   = __( 'Next day', 'events-made-easy' );

            } elseif ( $scope == 'tomorrow' ) {
                ++$scope_offset;
                $scope       = $eme_date_obj->modifyDays( $scope_offset )->format( 'Y-m-d' );
                $limit_start = $scope;
                $limit_end   = $scope;
                $scope_text  = eme_localized_date( $limit_start, EME_TIMEZONE );
                $prev_text   = __( 'Previous day', 'events-made-easy' );
                $next_text   = __( 'Next day', 'events-made-easy' );

            } else {
                $eme_date_obj->modifyMonths( $scope_offset );
                $limit_start = $eme_date_obj->startOfMonth()->format( 'Y-m-d' );
                $limit_end   = $eme_date_obj->endOfMonth()->format( 'Y-m-d' );
                $scope       = "$limit_start--$limit_end";
                $scope_text  = eme_localized_date( $limit_start, EME_TIMEZONE, get_option( 'eme_show_period_monthly_dateformat' ) );
                $prev_text   = __( 'Previous month', 'events-made-easy' );
                $next_text   = __( 'Next month', 'events-made-easy' );

            }

            $older_events = eme_get_events( limit: 1, scope: '--' . $limit_start, category: $atts['category'], show_ongoing: 1, notcategory: $atts['notcategory'] );
            $newer_events = eme_get_events( limit: 1, scope: '++' . $limit_end, category: $atts['category'], show_ongoing: 1, notcategory: $atts['notcategory'] );
            if ( count( $older_events ) == 0 ) {
                $prev_text = '';
            }
            if ( count( $newer_events ) == 0 ) {
                $next_text = '';
            }
        }

        $limit         = 0;
        $ignore_filter = false;
        $random_order  = false;
        $locations     = eme_get_locations( eventful: $eventful, scope: $scope, category: $atts['category'], notcategory: $atts['notcategory'], limit: $limit, ignore_filter: $ignore_filter, random_order: $random_order, author: $atts['author'], contact_person: $atts['contact_person'] );
        $id_base       = preg_replace( '/\D/', '_', microtime( 1 ) );
        $id_base       = rand() . '_' . $id_base;
        if ( ! empty( $width ) && ! empty( $height ) ) {
            if ( ! preg_match( '/\%$|px$|fr$|em$/', $width ) ) {
                $width = $width . 'px';
            }
            if ( ! preg_match( '/\%$|px$|fr$|em$/', $height ) ) {
                $height = $height . 'px';
            }
            $style = "style='width: $width; height: $height'";
        } else {
            $style = '';
        }
        if ( ! empty( $locations ) ) {
            $result           = "<div id='eme_global_map_$id_base' class='eme_global_map' $style>map</div>";
            $locations_string = 'global_map_info_' . $id_base;
            $locations_val    = eme_global_map_json( $locations, $marker_clustering, $letter_icons );
            $result          .= "<script type='text/javascript'>
                $locations_string = $locations_val;
         </script>";
        }

        if ( $atts['paging'] == 1 ) {
            $pagination_top = "<div id='div_locations-pagination-top_$id_base' class='locations-pagination-top'> ";
            $this_page_url = get_permalink($post->ID);
            if ( $prev_text != '' ) {
                $pagination_top .= "<a class='eme_nav_left' href='" . add_query_arg( [ 'eme_offset' => $prev_offset ], $this_page_url ) . "'>&lt;&lt; $prev_text</a>";
            }
            if ( $next_text != '' ) {
                $pagination_top .= "<a class='eme_nav_right' href='" . add_query_arg( [ 'eme_offset' => $next_offset ], $this_page_url ) . "'>$next_text &gt;&gt;</a>";
            }
            $pagination_top   .= "<span class='eme_nav_center'>$scope_text</span>";
            $pagination_top   .= '</div>';
            $pagination_bottom = str_replace( 'locations-pagination-top', 'locations-pagination-bottom', $pagination_top );
            $result            = $pagination_top . $result . $pagination_bottom;
        }

        $loc_list = "<div id='eme_div_locations_list_$id_base' class='eme_div_locations_list'><ol id='eme_locations_list_$id_base' class='eme_locations_list'>";
        if ( $letter_icons ) {
            $letter_style = "style='list-style-type: upper-alpha'";
            $firstletter  = 'A';
        } else {
            $letter_style = '';
            $firstletter  = '';
        }
        foreach ( $locations as $location ) {
            if ( $show_locations ) {
                $loc_list .= "<li id='location-" . $location['location_id'] . "_$id_base" .
                "' $letter_style><a>" .
                eme_trans_esc_html( $location['location_name'] ) . '</a></li>';
            }
            if ( $show_events ) {
                $events    = eme_get_events( scope: $scope, offset: $scope_offset, location_id: $location['location_id'], category: $atts['category'] );
                $loc_list .= "<ol id='eme_events_list'>";
                foreach ( $events as $event ) {
                    if ( $show_locations ) {
                        $loc_list .= "<li id='location-" . $location['location_id'] . "_$id_base" .
                            "' style='list-style-type: none'>- <a>" .
                            eme_trans_esc_html( $event['event_name'] ) . '</a></li>';
                    } else {
                        $loc_list .= "<li id='location-" . $location['location_id'] . "_$id_base" .
                            "' style='list-style-type: none'>$firstletter. <a>" .
                            eme_trans_esc_html( $event['event_name'] ) . '</a></li>';
                    }
                }
                $loc_list .= '</ol>';
            }
            if ( $letter_icons ) {
                    ++$firstletter;
            }
        }
        $loc_list .= '</ol></div>';
        if ( $atts['list_location'] == 'before' ) {
            $result = $loc_list . $result;
        } elseif ( $atts['list_location'] == 'after' ) {
            $result .= $loc_list;
        }
    } else {
        $result = '';
    }
    return $result;
}

function eme_single_location_map_shortcode( $atts ) {
    eme_enqueue_frontend();
    $atts = shortcode_atts(
            [
                'id'     => '',
                'width'  => 0,
                'height' => 0,
                'zoom'   => get_option( 'eme_indiv_zoom_factor' ),
            ],
            $atts
    );
    $id = eme_sanitize_request($atts['id']);
    $width = eme_sanitize_request($atts['width']);
    $height = eme_sanitize_request($atts['height']);
    $zoom = eme_sanitize_request($atts['zoom']);

    $location = eme_get_location( $id );
    if ( ! empty( $location ) ) {
        $map_div = eme_single_location_map( $location, $width, $height, $zoom );
        return $map_div;
    }
}

function eme_display_single_location( $location_id, $template_id = 0, $ignore_url = 0 ) {
    $location = eme_get_location( $location_id );
    // also take into account the generic option for using the external url
    if ( empty( $location ) ) {
        return '';
    }
    if ( ! $ignore_url ) {
        $ignore_url = get_option( 'eme_use_external_url' );
    }
    if ( eme_is_empty_string( $location['location_url'] ) && ! $ignore_url && eme_is_url( $location['location_url'] ) ) {
        // url not empty, so we redirect to it
        $page_body = eme_js_redirect( $location['location_url'] );
        return $page_body;
    } elseif ( $template_id ) {
        $single_location_format = eme_get_template_format( $template_id );
    } else {
        $single_location_format = get_option( 'eme_single_location_format' );
    }
    $page_body = eme_replace_locations_placeholders( $single_location_format, $location );
    return $page_body;
}

function eme_get_location_shortcode( $atts ) {
    eme_enqueue_frontend();
    $atts = shortcode_atts(
            [
                'id'          => '',
                'template_id' => 0,
                'ignore_url'  => 0,
            ],
            $atts
    );
    $ignore_url = filter_var( $atts['ignore_url'], FILTER_VALIDATE_BOOLEAN );
    $id = eme_sanitize_request($atts['id']);
    $template_id = intval($atts['template_id']);
    return eme_display_single_location( $id, $template_id, $ignore_url );
}

function eme_get_locations_shortcode( $atts ) {
    eme_enqueue_frontend();
    $atts = shortcode_atts(
        [
            'eventful'           => false,
            'ignore_filter'      => false,
            'random_order'       => false,
            'category'           => '',
            'notcategory'        => '',
            'author'             => '',
            'contact_person'     => '',
            'scope'              => 'all',
            'limit'              => 0,
            'template_id'        => 0,
            'template_id_header' => 0,
            'template_id_footer' => 0,
        ],
        $atts
    );

    $eventful      = filter_var( $atts['eventful'], FILTER_VALIDATE_BOOLEAN );
    $ignore_filter = filter_var( $atts['ignore_filter'], FILTER_VALIDATE_BOOLEAN );
    $random_order  = filter_var( $atts['random_order'], FILTER_VALIDATE_BOOLEAN );

    $locations = eme_get_locations( eventful: $eventful, scope: $atts['scope'], category: $atts['category'], notcategory: $atts['notcategory'], limit: $atts['limit'], ignore_filter: $ignore_filter, random_order: $random_order, author: $atts['author'], contact_person: $atts['contact_person'] );

    $format            = '';
    $eme_format_header = '';
    $eme_format_footer = '';

    if ( $atts['template_id'] ) {
        $format = eme_get_template_format( $atts['template_id'] );
    }
    if ( $atts['template_id_header'] ) {
        $format_header     = eme_get_template_format( $atts['template_id_header'] );
        $eme_format_header = eme_replace_locations_placeholders( $format_header );
    }
    if ( $atts['template_id_footer'] ) {
        $format_footer     = eme_get_template_format( $atts['template_id_footer'] );
        $eme_format_footer = eme_replace_locations_placeholders( $format_footer );
    }
    if ( empty( $format ) ) {
        $format = get_option( 'eme_location_list_format_item' );
        $format = ( $format != '' ) ? $format : '<li class="location-#_LOCATIONID">#_LOCATIONNAME</li>';
        if ( empty( $eme_format_header ) ) {
            $eme_format_header = eme_replace_locations_placeholders( get_option( 'eme_location_list_format_header' ) );
            $eme_format_header = ( $eme_format_header != '' ) ? $eme_format_header : DEFAULT_LOCATION_LIST_HEADER_FORMAT;
        }
        if ( empty( $eme_format_footer ) ) {
            $eme_format_footer = eme_replace_locations_placeholders( get_option( 'eme_location_list_format_footer' ) );
            $eme_format_footer = ( $eme_format_footer != '' ) ? $eme_format_footer : DEFAULT_LOCATION_LIST_FOOTER_FORMAT;
        }
    }

    $output = '';
    foreach ( $locations as $location ) {
        $output .= eme_replace_locations_placeholders( $format, $location );
    }
    $output = $eme_format_header . $output . $eme_format_footer;
    return $output;
}


function eme_replace_event_location_placeholders( $format, $event, $target = 'html', $do_shortcode = 1, $lang = '' ) {
    // replace EME language tags as early as possible
        $format = eme_translate_string( $format );

    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    // in the case of the function eme_get_events, the $event contains also the location info
    // so let's just check if the location name is there, if not: get the location
    if ( $event['location_id'] > 0 ) {
        $location = eme_get_location( $event['location_id'] );
        if ( empty( $location ) ) {
            return $format;
        }
    } else {
        // to make sure all location placeholders are replaced (even by empty stuff)
        // we create a new location, otherwise we could just return $format but that would leave
        // some location placeholders unreplaced (which is not the behavior before 1.6.6)
        $location = eme_new_location();
    }

    // we don't want eme_replace_locations_placeholders to replace generic placeholders. Reason: eme_replace_event_location_placeholders
    //    is called from eme_replace_event_placeholders, where generic placeholders are already being replaced
    $avoid_double_code = 1;
    return eme_replace_locations_placeholders( $format, $location, $orig_target, $do_shortcode, $lang, $avoid_double_code );
}

function eme_replace_locations_placeholders( $format, $location = '', $target = 'html', $do_shortcode = 1, $lang = '', $avoid_double_code = 0 ) {
    // replace EME language tags as early as possible
        $format = eme_translate_string( $format );

    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    // some variables we'll use further down more than once
    $current_userid                = get_current_user_id();
    $eme_enable_notes_placeholders = get_option( 'eme_enable_notes_placeholders' );

    // replace the generic placeholders
    if ( ! $avoid_double_code ) {
        $format = eme_replace_generic_placeholders( $format, $orig_target );
    }

    // replace the notes sections, since these can contain other placeholders
    if ( $eme_enable_notes_placeholders ) {
        $format = eme_replace_locationnotes_placeholders( $format, $location, $orig_target );
    }

    // then we do the custom attributes, since these can contain other placeholders
    if ($location && preg_match_all( '/#(ESC|URL)?_ATT\{.+?\}(\{.+?\})?/', $format, $results )) {
        foreach ( $results[0] as $resultKey => $result ) {
            $need_escape    = 0;
            $need_urlencode = 0;
            $orig_result    = $result;
            if ( strstr( $result, '#ESC' ) ) {
                $result      = str_replace( '#ESC', '#', $result );
                $need_escape = 1;
            } elseif ( strstr( $result, '#URL' ) ) {
                $result         = str_replace( '#URL', '#', $result );
                $need_urlencode = 1;
            }
            $replacement = '';
            //Strip string of placeholder and just leave the reference
            $attRef = substr( substr( $result, 0, strpos( $result, '}' ) ), 6 );
            if ( isset( $location['location_attributes'][ $attRef ] ) ) {
                $replacement = $location['location_attributes'][ $attRef ];
            }
            if ( trim( $replacement ) == ''
                && isset( $results[2][ $resultKey ] )
                && $results[2][ $resultKey ] != '' ) {
                //Check to see if we have a second set of braces;
                $replacement = substr( $results[2][ $resultKey ], 1, strlen( trim( $results[2][ $resultKey ] ) ) - 2 );
            }

            if ( $need_escape ) {
                $replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
            }
            if ( $need_urlencode ) {
                $replacement = rawurlencode( $replacement );
            }
            $format = str_replace( $orig_result, $replacement, $format );
        }
    }

    if (!empty( $location['location_id'] )) {
        $answers = eme_get_location_answers( $location['location_id'] );
        $files   = eme_get_uploaded_files( $location['location_id'], 'locations' );
    } else {
        $answers = [];
        $files = [];
    }
    $all_categories      = eme_get_cached_categories();
    $location_categories = null;
    // and now all the other placeholders
    if ($location && preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE )) {
        $needle_offset = 0;
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

            # support for #_LOCATION and #_LOCATION_
            $result = preg_replace( '/#_LOCATION(_)?/', '#_', $result );
            if ($result == '#_') $result = '#_NAME';

            $replacement = '';

            // echo "RESULT: $result <br>";
            // matches alla fields placeholder
            if ( preg_match( '/#_MAP/', $result ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    if ( $target == 'html' && get_option( 'eme_map_is_active' ) ) {
                        $replacement = eme_single_location_map( $location );
                    }
                }
            } elseif ( preg_match( '/#_PASTEVENTS(\{.+?\})?$/', $result, $matches ) ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $order = substr( $matches[1], 1, -1 );
                } else {
                    $order = '';
                }
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $replacement = eme_events_in_location_list( $location, 'past', $order );
                }
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_NEXTEVENTS(\{.+?\})?$/', $result, $matches ) ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $order = substr( $matches[1], 1, -1 );
                } else {
                    $order = '';
                }
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $replacement = eme_events_in_location_list( $location, 'future', $order );
                }
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_ALLEVENTS(\{.+?\})?$/', $result, $matches ) ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $order = substr( $matches[1], 1, -1 );
                } else {
                    $order = '';
                }
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $replacement = eme_events_in_location_list( $location, 'all', $order );
                }
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_(ADDRESS|TOWN|CITY|STATE|ZIP|COUNTRY|LATITUDE|LONGITUDE|POSTAL)/', $result ) ) {
                $field = 'location_' . ltrim( strtolower( $result ), '#_' );
                if ( $field == 'location_address' ) {
                    $field = 'location_address1';
                }
                if ( $field == 'location_town' ) {
                    $field = 'location_city';
                }
                if ( $field == 'location_postal' ) {
                    $field = 'location_zip';
                }
                if ( isset( $location[ $field ] ) ) {
                    $replacement = $location[ $field ];
                }
                $replacement = eme_trans_esc_html( $replacement, $lang );
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_NAME$/', $result ) ) {
                $field = 'location_name';
                if ( isset( $location[ $field ] ) ) {
                    $replacement = $location[ $field ];
                }
                $replacement = eme_trans_esc_html( $replacement, $lang );
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_ID/', $result ) ) {
                $field = 'location_id';
                if ( isset( $location[ $field ] ) ) {
                    $replacement = $location[ $field ];
                }
                $replacement = eme_trans_esc_html( $replacement, $lang );
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_IMAGETITLE$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $info = eme_get_wp_image( $location['location_image_id'] );
                    if (!empty($info)) {
                        $replacement = $info['title'];
                        if ( $target == 'html' ) {
                            $replacement = apply_filters( 'eme_general', $replacement );
                        } elseif ( $target == 'rss' ) {
                            $replacement = apply_filters( 'the_content_rss', $replacement );
                        } else {
                            $replacement = apply_filters( 'eme_text', $replacement );
                        }
                    }
                }
            } elseif ( preg_match( '/#_IMAGEALT$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $info = eme_get_wp_image( $location['location_image_id'] );
                    if (!empty($info)) {
                        $replacement = $info['alt'];
                        if ( $target == 'html' ) {
                            $replacement = apply_filters( 'eme_general', $replacement );
                        } elseif ( $target == 'rss' ) {
                            $replacement = apply_filters( 'the_content_rss', $replacement );
                        } else {
                            $replacement = apply_filters( 'eme_text', $replacement );
                        }
                    }
                }
            } elseif ( preg_match( '/#_IMAGECAPTION$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $info = eme_get_wp_image( $location['location_image_id'] );
                    if (!empty($info)) {
                        $replacement = $info['caption'];
                        if ( $target == 'html' ) {
                            $replacement = apply_filters( 'eme_general', $replacement );
                        } elseif ( $target == 'rss' ) {
                            $replacement = apply_filters( 'the_content_rss', $replacement );
                        } else {
                            $replacement = apply_filters( 'eme_text', $replacement );
                        }
                    }
                }
            } elseif ( preg_match( '/#_IMAGEDESCRIPTION$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $info = eme_get_wp_image( $location['location_image_id'] );
                    if (!empty($info)) {
                        $replacement = $info['description'];
                        if ( $target == 'html' ) {
                            $replacement = apply_filters( 'eme_general', $replacement );
                        } elseif ( $target == 'rss' ) {
                            $replacement = apply_filters( 'the_content_rss', $replacement );
                        } else {
                            $replacement = apply_filters( 'eme_text', $replacement );
                        }
                    }
                }
            } elseif ( preg_match( '/#_IMAGE$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $replacement = wp_get_attachment_image( $location['location_image_id'], 'full', 0, [ 'class' => 'eme_location_image' ] );
                    if (empty($replacement)) {
                        $replacement = "";
                    }
                } elseif ( ! empty( $location['location_image_url'] ) ) {
                    $url = $location['location_image_url'];
                    if ( $target == 'html' ) {
                        $url = esc_url( $url );
                    }
                    $replacement = "<img src='$url' alt='" . eme_trans_esc_html( $location['location_name'], $lang ) . "'>";
                }
                if ( ! empty( $replacement ) ) {
                    if ( $target == 'html' ) {
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_IMAGEURL$/', $result ) ) {
                if ( ! empty( $location['location_image_id'] ) ) {
                    $replacement = wp_get_attachment_image_url( $location['location_image_id'], 'full' );
                    if (empty($replacement)) {
                        $replacement = "";
                    }
                } elseif ( ! empty( $location['location_image_url'] ) ) {
                    $replacement = $location['location_image_url'];
                }
                if ( $target == 'html' ) {
                    $replacement = esc_url( $replacement );
                }
            } elseif ( preg_match( '/#_IMAGETHUMB(\{.+?\})?$/', $result, $matches ) ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $thumb_size = substr( $matches[1], 1, -1 );
                } else {
                    $thumb_size = get_option( 'eme_thumbnail_size' );
                }
                if ( ! empty( $location['location_image_id'] ) ) {
                    $replacement = wp_get_attachment_image( $location['location_image_id'], $thumb_size, 0, [ 'class' => 'eme_location_image' ] );
                    if (empty($replacement)) {
                        $replacement = "";
                    }
                    if ( $target == 'html' ) {
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_IMAGETHUMBURL(\{.+?\})?$/', $result, $matches ) ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $thumb_size = substr( $matches[1], 1, -1 );
                } else {
                    $thumb_size = get_option( 'eme_thumbnail_size' );
                }
                if ( ! empty( $location['location_image_id'] ) ) {
                    $replacement = wp_get_attachment_image_url( $location['location_image_id'], $thumb_size );
                    if (empty($replacement)) {
                        $replacement = "";
                    }
                    if ( $target == 'html' ) {
                        $replacement = esc_url( $replacement );
                    }
                }
            } elseif ( preg_match( '/#_PAGEURL/', $result ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $replacement = eme_location_url( $location, $lang );
                }
                if ( $target == 'html' ) {
                    if ( $target == 'html' ) {
                        $replacement = esc_url( $replacement );
                    }
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_DIRECTIONS/', $result ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 && $target == 'html' ) {
                    $replacement = eme_add_directions_form( $location );
                    $replacement = apply_filters( 'eme_general', $replacement );
                }
                # until I find something easy not-google related, this is returning the google form

            } elseif ( preg_match( '/#_DBFIELD\{(.+?)\}/', $result, $matches ) ) {
                $tmp_attkey = $matches[1];
                if ( isset( $location[ $tmp_attkey ] ) && ! is_array( $location[ $tmp_attkey ] ) ) {
                    $replacement = $location[ $tmp_attkey ];
                    if ( $target == 'html' ) {
                        $replacement = eme_trans_esc_html( $replacement, $lang );
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = eme_translate( $replacement, $lang );
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = eme_translate( $replacement, $lang );
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_MYLOCATIONATT\{(.+?)\}/', $result, $matches ) ) {
                $tmp_attkey = $matches[1];
                if ( isset( $location['location_attributes'][ $tmp_attkey ] ) ) {
                    $replacement = $location['location_attributes'][ $tmp_attkey ];
                    if ( $target == 'html' ) {
                        $replacement = eme_trans_esc_html( $replacement, $lang );
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = eme_translate( $replacement, $lang );
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = eme_translate( $replacement, $lang );
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_CATEGORIES$/', $result ) && get_option( 'eme_categories_enabled' ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $sep = ', ';
                    if ( has_filter( 'eme_categories_sep_filter' ) ) {
                        $sep = apply_filters( 'eme_categories_sep_filter', $sep );
                    }
                    if ( is_null( $location_categories ) ) {
                        $location_categories = eme_get_categories_filtered( $location['location_category_ids'], $all_categories );
                    }
                    $cat_names = array_column( $location_categories, 'category_name' );
                    if ( $target == 'html' ) {
                        $replacement = eme_trans_esc_html( join( $sep, $cat_names ), $lang );
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = eme_translate( join( $sep, $cat_names ), $lang );
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = eme_translate( join( $sep, $cat_names ), $lang );
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_CATEGORIES_CSS/', $result ) && get_option( 'eme_categories_enabled' ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    if ( is_null( $location_categories ) ) {
                        $location_categories = eme_get_categories_filtered( $location['location_category_ids'], $all_categories );
                    }
                    $cat_names = array_column( $location_categories, 'category_name' );
                    if ( $target == 'html' ) {
                        $replacement = eme_trans_esc_html( join( ' ', $cat_names ), $lang );
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = eme_translate( join( ' ', $cat_names ), $lang );
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = eme_translate( join( ' ', $cat_names ), $lang );
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/#_CATEGORYDESCRIPTIONS/', $result ) && get_option( 'eme_categories_enabled' ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    $sep = ', ';
                    if ( has_filter( 'eme_categorydescriptions_sep_filter' ) ) {
                        $sep = apply_filters( 'eme_categorydescriptions_sep_filter', $sep );
                    }
                    if ( is_null( $location_categories ) ) {
                        $location_categories = eme_get_categories_filtered( $location['location_category_ids'], $all_categories );
                    }
                    $cat_descs = array_column( $location_categories, 'description' );
                    if ( $target == 'html' ) {
                        $replacement = eme_trans_esc_html( join( $sep, $cat_descs ), $lang );
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = eme_translate( join( $sep, $cat_descs ), $lang );
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = eme_translate( join( $sep, $cat_descs ), $lang );
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( preg_match( '/^#_CATEGORIES\{(.*?)\}\{(.*?)\}/', $result, $matches ) && get_option( 'eme_categories_enabled' ) ) {
                $include_cats         = $matches[1];
                $exclude_cats         = $matches[2];
                $extra_conditions_arr = [];
                $order_by             = '';
                if ( ! empty( $include_cats ) && eme_is_list_of_int( $include_cats ) ) {
                    $extra_conditions_arr[] = "category_id IN ($include_cats)";
                    $order_by = "FIELD(category_id,$include_cats)";
                }
                if ( ! empty( $exclude_cats ) && eme_is_list_of_int( $exclude_cats )) {
                    $extra_conditions_arr[] = "category_id NOT IN ($exclude_cats)";
                }
                $extra_conditions = join( ' AND ', $extra_conditions_arr );
                $categories       = eme_get_location_category_names( $location['location_id'], $extra_conditions, $order_by );
                $cat_names        = [];
                foreach ( $categories as $cat_name ) {
                    if ( $target == 'html' ) {
                        $cat_names[] = eme_trans_esc_html( $cat_name, $lang );
                    } else {
                        $cat_names[] = eme_translate( $cat_name, $lang );
                    }
                }
                $sep = ', ';
                if ( has_filter( 'eme_categories_sep_filter' ) ) {
                    $sep = apply_filters( 'eme_categories_sep_filter', $sep );
                }
                $replacement = join( $sep, $cat_names );
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/^#_CATEGORIES_CSS\{(.*?)\}\{(.*?)\}/', $result, $matches ) && get_option( 'eme_categories_enabled' ) ) {
                $include_cats         = $matches[1];
                $exclude_cats         = $matches[2];
                $extra_conditions_arr = [];
                $order_by             = '';
                if ( ! empty( $exclude_cats ) && eme_is_list_of_int( $include_cats )) {
                    $extra_conditions_arr[] = "category_id IN ($include_cats)";
                    $order_by = "FIELD(category_id,$include_cats)";
                }
                if ( ! empty( $exclude_cats ) && eme_is_list_of_int( $exclude_cats )) {
                    $extra_conditions_arr[] = "category_id NOT IN ($exclude_cats)";
                }
                $extra_conditions = join( ' AND ', $extra_conditions_arr );
                $categories       = eme_get_location_category_names( $location['location_id'], $extra_conditions, $order_by );
                if ( $target == 'html' ) {
                    $replacement = eme_trans_esc_html( join( ' ', $categories ), $lang );
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = eme_translate( join( ' ', $categories ), $lang );
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = eme_translate( join( ' ', $categories ), $lang );
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_CATEGORYDESCRIPTIONS\{(.*?)\}\{(.*?)\}/', $result, $matches ) && get_option( 'eme_categories_enabled' ) ) {
                $include_cats         = $matches[1];
                $exclude_cats         = $matches[2];
                $extra_conditions_arr = [];
                $order_by             = '';
                if ( ! empty( $exclude_cats ) && eme_is_list_of_int( $include_cats )) {
                    $extra_conditions_arr[] = "category_id IN ($include_cats)";
                    $order_by = "FIELD(category_id,$include_cats)";
                }
                if ( ! empty( $exclude_cats ) && eme_is_list_of_int( $exclude_cats )) {
                    $extra_conditions_arr[] = "category_id NOT IN ($exclude_cats)";
                }
                $extra_conditions = join( ' AND ', $extra_conditions_arr );
                $categories       = eme_get_location_category_descriptions( $location['location_id'], $extra_conditions, $order_by );
                $sep              = ', ';
                if ( has_filter( 'eme_categorydescriptions_sep_filter' ) ) {
                    $sep = apply_filters( 'eme_categorydescriptions_sep_filter', $sep );
                }
                $replacement = eme_translate( join( $sep, $categories ), $lang );
                if ( $target == 'html' ) {
                    $replacement = apply_filters( 'eme_general', $replacement );
                } elseif ( $target == 'rss' ) {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            } elseif ( preg_match( '/#_EDITLOCATIONLINK/', $result ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    if ( current_user_can( get_option( 'eme_cap_edit_locations' ) ) ||
                        ( current_user_can( get_option( 'eme_cap_author_locations' ) ) && ( $location['location_author'] == $current_userid ) ) ) {
                        $url = admin_url( 'admin.php?page=eme-locations&amp;eme_admin_action=edit_location&amp;location_id=' . $location['location_id'] );
                        if ( $target == 'html' ) {
                            $url = esc_url( $url );
                        }
                        $replacement = "<a href='$url'>" . __( 'Edit', 'events-made-easy' ) . '</a>';
                    }
                }
            } elseif ( preg_match( '/#_EDITLOCATIONURL/', $result ) ) {
                if ( isset( $location['location_id'] ) && $location['location_id'] > 0 ) {
                    if ( current_user_can( get_option( 'eme_cap_edit_locations' ) ) ||
                        ( current_user_can( get_option( 'eme_cap_author_locations' ) ) && ( $location['location_author'] == $current_userid ) ) ) {
                        $replacement = admin_url( 'admin.php?page=eme-locations&amp;eme_admin_action=edit_location&amp;location_id=' . $location['location_id'] );
                        if ( $target == 'html' ) {
                            $replacement = esc_url( $replacement );
                        }
                    }
                }
            } elseif ( $location && preg_match( '/#_EXTERNAL_URL/', $result ) ) {
                if ( ! empty( $location['location_url'] ) ) {
                    $replacement = $location['location_url'];
                    if ( $target == 'html' ) {
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = apply_filters( 'eme_text', $replacement );
                    }
                }
            } elseif ( $location && preg_match( '/#_EXTERNAL_REF/', $result ) ) {
                if ( ! empty( $location['location_external_ref'] ) ) {
                    // remove the 'fb_' prefix
                    $replacement = preg_replace( '/fb_/', '', $location['location_external_ref'] );
                    if ( $target == 'html' ) {
                        $replacement = apply_filters( 'eme_general', $replacement );
                    } elseif ( $target == 'rss' ) {
                        $replacement = apply_filters( 'the_content_rss', $replacement );
                    } else {
                        $replacement = apply_filters( 'eme_text', $replacement );
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
                if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'locations' ) {
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
                    // no location custom field? Then leave it alone
                    $found = 0;
                }
            } else {
                $found = 0;
            }

            if ( $found ) {
                // to be sure
                if (is_null($replacement)) {
                    $replacement = "";
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
    }

    # we handle NOTES the last, this used to be the default behavior
    # so no placeholder replacement happened accidentaly in possible shortcodes inside #_NOTES
    # but since we have templates to aid in all that ...
    if ( ! $eme_enable_notes_placeholders ) {
        $format = eme_replace_locationnotes_placeholders( $format, $location, $orig_target );
    }

    if ( ! $avoid_double_code ) {
        // replace leftover generic placeholders
        $format = eme_replace_generic_placeholders( $format, $orig_target );

        // now, replace any language tags found
        $format = eme_translate( $format, $lang );

        // now some html
        if ( $target == 'html' ) {
            $format = eme_nl2br_save_html( $format );
        }

        // and now replace any shortcodes, if wanted
        if ( $do_shortcode ) {
            $format = do_shortcode( $format );
        }
    }

    return $format;
}

function eme_replace_locationnotes_placeholders( $format, $location, $target = 'html' ) {
    // replace EME language tags as early as possible
        $format = eme_translate_string( $format );

    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    if ( $location && preg_match_all( '/#(ESC)?_(DESCRIPTION|LOCATIONDETAILS|LOCATIONEXCERPT|LOCATIONNOEXCERPT)/', $format, $placeholders, PREG_OFFSET_CAPTURE ) ) {
        $needle_offset = 0;
        foreach ( $placeholders[0] as $orig_result ) {
            $result             = $orig_result[0];
            $orig_result_needle = $orig_result[1] - $needle_offset;
            $orig_result_length = strlen( $orig_result[0] );
            $need_escape        = 0;
            $found              = 1;
            if ( strstr( $result, '#ESC' ) ) {
                $result      = str_replace( '#ESC', '#', $result );
                $need_escape = 1;
            }
            $field = ltrim( strtolower( $result ), '#_' );
            // to catch every alternative (we just need to know if it is an excerpt or not)
            $show_excerpt = 0;
            $show_rest    = 0;
            if ( $field == 'excerpt' ) {
                $show_excerpt = 1;
            }
            if ( $field == 'noexcerpt' ) {
                $show_rest = 1;
            }

            $replacement = '';
            if ( ! eme_is_empty_string( $location['location_description'] ) ) {
                // first translate, since for "noexcerpt" the language indication is not there (it is only at the beginning of the notes, not after the separator)
                $notes = eme_translate( $location['location_description'] );

                // make sure no windows line endings are in
                $notes = preg_replace( '/\r\n|\n\r/', "\n", $notes );
                if ( $show_excerpt ) {
                    // If excerpt, use the part before the more delimiter, removing a possible line ending
                    if ( preg_match( '/<\!--more-->/', $notes ) ) {
                        $matches     = preg_split( '/\n?<\!--more-->/', $notes );
                        $replacement = eme_excerpt( $matches[0], 'eme_location_excerpt ' . $location['location_id'] );
                    } else {
                        $replacement    = eme_excerpt( $notes, 'eme_location_excerpt ' . $location['location_id'] );
                        $excerpt_length = apply_filters( 'eme_excerpt_length', 55 );
                        $replacement    = wp_trim_words( $replacement, $excerpt_length );
                    }
                } elseif ( $show_rest ) {
                    // If the rest is wanted, use the part after the more delimiter, removing a possible line ending
                    $matches = preg_split( '/<\!--more-->\n?/', $notes );
                    if ( isset( $matches[1] ) ) {
                        $replacement = $matches[1];
                    } else {
                        $replacement = $notes;
                    }
                } elseif ( preg_match( '/<\!--more-->/', $notes ) ) {
                        // remove the more-delimiter, but if it was on a line by itself, replace by a linefeed
                        $replacement = preg_replace( '/\n<\!--more-->\n/', "\n", $notes );
                        $replacement = preg_replace( '/<\!--more-->/', '', $replacement );
                } else {
                    $replacement = $notes;
                }
            }
            if ( $target == 'html' ) {
                if ( $show_excerpt ) {
                    $replacement = apply_filters( 'the_excerpt', $replacement );
                } else {
                    // apply the_content filter, but don't replace shortcodes here already
                    remove_filter( 'the_content', 'do_shortcode', 11 );
                    $replacement = apply_filters( 'the_content', $replacement );
                    add_filter( 'the_content', 'do_shortcode', 11 );
                }
            } elseif ( $target == 'rss' ) {
                if ( $show_excerpt ) {
                    $replacement = apply_filters( 'the_excerpt_rss', $replacement );
                } else {
                    $replacement = apply_filters( 'the_content_rss', $replacement );
                }
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
            if ( $found ) {
                // to be sure
                if (is_null($replacement)) {
                    $replacement = "";
                }
                if ( $need_escape ) {
                    $replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
                }
                $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
                $needle_offset += $orig_result_length - strlen( $replacement );
            }
        }
    }
    return $format;
}

function eme_add_directions_form( $location ) {
    $locale_code = substr( get_locale(), 0, 2 );
    $res         = '';
    if ( isset( $location['location_address1'] ) && isset( $location['location_city'] ) ) {
        $res .= '<form action="//maps.google.com/maps" method="get" target="_blank" style="text-align:left;">';
        $res .= '<div id="eme_direction_form"><label for="saddr">' . __( 'Your Street Address', 'events-made-easy' ) . '</label><br>';
        $res .= '<input type="text" name="saddr" id="saddr" value="">';
        $res .= '<input type="hidden" name="daddr" value="' . $location['location_address1'] . ', ' . $location['location_city'] . '">';
        $res .= '<input type="hidden" name="hl" value="' . $locale_code . '"></div>';
        $res .= '<input type="submit" value="' . __( 'Get Directions', 'events-made-easy' ) . '">';
        $res .= '</form>';
    }

    # some people might want to change the form to their liking
    if ( has_filter( 'eme_directions_form_filter' ) ) {
        $res = apply_filters( 'eme_directions_form_filter', $res );
    }

    return $res;
}

function eme_global_map_json( $locations, $marker_clustering, $letter_icons ) {
    $json_locations = [];
    foreach ( $locations as $location ) {
        $json_location = [];

        // we need lat and long, otherwise it fails
        if ( empty( $location['location_latitude'] ) || empty( $location['location_longitude'] ) ) {
            continue;
        }

        # first we set the balloon info
        $tmp_loc = eme_replace_locations_placeholders( get_option( 'eme_location_baloon_format' ), $location );
        // newlines are already replaced by eme_replace_locations_placeholders
                //    no newlines allowed, otherwise no map is shown
                //    $tmp_loc = eme_nl2br( $tmp_loc );
        # no other white chars but spaces allowed (wp_json_encode allows them, but the JS-json parses fails)
        $tmp_loc                           = preg_replace( '/\s+/', ' ', $tmp_loc );
        $json_location['location_balloon'] = eme_trans_esc_html( $tmp_loc );

        # second, we fill in the rest of the info
        foreach ( $location as $key => $value ) {
            # we skip some keys, since json is limited in size we only return what's needed in the javascript
            if ( preg_match( '/location_balloon|location_id|location_latitude|location_longitude/', $key ) ) {
                # no newlines allowed, otherwise no map is shown
                $value                 = eme_nl2br( $value );
                $json_location[ $key ] = eme_trans_esc_html( $value );
            }
            $json_location['map_icon'] = eme_esc_html( $location['location_properties']['map_icon'] );
        }
        $json_locations[] = $json_location;
    }

    $marker_clustering_val = ( $marker_clustering ) ? 'true' : 'false';

    $json = [
        'locations'         => $json_locations,
        'enable_zooming'    => get_option( 'eme_map_zooming' ) ? 'true' : 'false',
        'default_map_icon'  => get_option( 'eme_location_map_icon' ),
        'letter_icons'      => $letter_icons ? 'true' : 'false',
        'marker_clustering' => $marker_clustering_val,
        'gestures'          => get_option( 'eme_map_gesture_handling' ) ? 'true' : 'false',
    ];
    return wp_json_encode( $json );
}

function eme_single_location_map( $location, $width = 0, $height = 0, $zoom_factor = 0 ) {
    $map_is_active = get_option( 'eme_map_is_active' );
    if ( $zoom_factor == 0 ) {
        $zoom_factor = get_option( 'eme_indiv_zoom_factor' );
    }

    $map_text = eme_replace_locations_placeholders( get_option( 'eme_location_baloon_format' ), $location );
    // newlines are already replaced by eme_replace_locations_placeholders
    //    no newlines allowed, otherwise no map is shown
    //    $map_text = eme_nl2br_save_html( $map_text );
    // no other white chars but spaces allowed (wp_json_encode allows them, but the JS-json parses fails)
    $map_text = preg_replace( '/\s+/', ' ', $map_text );
    // if map is not active: we don't show the map
    // if the location name is empty: we don't show the map. But that can never happen since it's checked when creating the location
    if ( $map_is_active && isset( $location['location_id'] ) && $location['location_id'] > 0 && ! empty( $location['location_latitude'] ) && ! empty( $location['location_longitude'] ) ) {
        wp_enqueue_style( 'eme-leaflet-css' );
        if ( get_option( 'eme_map_gesture_handling' ) ) {
            wp_enqueue_script( 'eme-leaflet-gestures' );
            wp_enqueue_style( 'eme-gestures-css' );
        }
        wp_enqueue_script( 'eme-show-maps' );
        //$id_base = $location['location_id'];
        // we can't create a unique <div>-id based on location id alone, because you can have multiple maps on the sampe page for
        // different events but they can go to the same location...
        // So we also use the event_id (if present) and the microtime for this, and replace all non digits by underscore (otherwise the generated javascript will error)
        $id_base = preg_replace( '/\D/', '_', microtime( 1 ) );
        // the next is only possible when called from within an event
        if ( isset( $location['event_id'] ) ) {
            $id_base = $location['event_id'] . '_' . $id_base;
        } else {
            $id_base = rand() . '_' . $id_base;
        }
        $id             = 'eme-location-map_' . $id_base;
        $enable_zooming = get_option( 'eme_map_zooming' ) ? 'true' : 'false';
        $gestures       = get_option( 'eme_map_gesture_handling' ) ? 'true' : 'false';
        $map_icon       = get_option( 'eme_location_map_icon' );
        if ( $zoom_factor > 14 ) {
            $zoom_factor = 14;
        }
        //$map_div = "<div id='$id' style=' background: green; width: 400px; height: 300px'></div>" ;
        if ( ! empty( $width ) && ! empty( $height ) ) {
            if ( ! preg_match( '/\%$|px$|fr$|em$/', $width ) ) {
                $width = $width . 'px';
            }
            if ( ! preg_match( '/\%$|px$|fr$|em$/', $height ) ) {
                $height = $height . 'px';
            }
            $style = "style='width: $width; height: $height'";
        } else {
            $style = '';
        }
        $data    = "data-lat='" . $location['location_latitude'] . "'";
        $data   .= " data-lon='" . $location['location_longitude'] . "'";
        $data   .= " data-map_icon='" . $location['location_properties']['map_icon'] . "'";
        $data   .= " data-map_text='" . eme_esc_html( $map_text ) . "'";
        $data   .= " data-enable_zooming='$enable_zooming'";
        $data   .= " data-gestures='$gestures'";
        $data   .= " data-default_map_icon='$map_icon'";
        $data   .= " data-zoom_factor='$zoom_factor'";
        $map_div = "<div id='$id' class='eme-location-map' $style $data></div>";
    } else {
        $map_div = '';
    }
    return $map_div;
}

function eme_events_in_location_list( $location, $scope = 'future', $order = 'ASC' ) {
    $eme_event_list_number_events = intval(get_option( 'eme_event_list_number_items' ));
    $events                       = eme_get_events( limit: $eme_event_list_number_events, scope: $scope, order: $order, location_id: $location['location_id'] );
    $list                         = '';
    if ( count( $events ) > 0 ) {
        foreach ( $events as $event ) {
            $list .= eme_replace_event_placeholders( get_option( 'eme_location_event_list_item_format' ), $event );
        }
    } else {
        $list = get_option( 'eme_location_no_events_message' );
    }
    return $list;
}

// API function, leave it as is
function eme_locations_search_ajax() {
    header( 'Content-type: application/json; charset=utf-8' );
    if ( !empty( $_GET['id'] ) ) {
        $item   = eme_get_location( intval( $_GET['id'] ) );
        if ( empty( $item ) ) {
            echo wp_json_encode( [] );
        } else {
            $record = [];
            $record['id']           = $item['location_id'];
            $record['name']         = eme_trans_esc_html( $item['location_name'] );
            $record['address1']     = eme_trans_esc_html( $item['location_address1'] );
            $record['address2']     = eme_trans_esc_html( $item['location_address2'] );
            $record['city']         = eme_trans_esc_html( $item['location_city'] );
            $record['state']        = eme_trans_esc_html( $item['location_state'] );
            $record['zip']          = eme_trans_esc_html( $item['location_zip'] );
            $record['country']      = eme_trans_esc_html( $item['location_country'] );
            $record['latitude']     = eme_trans_esc_html( $item['location_latitude'] );
            $record['longitude']    = eme_trans_esc_html( $item['location_longitude'] );
            $record['map_icon']     = eme_trans_esc_html( $item['location_properties']['map_icon'] );
            $record['online_only']  = eme_trans_esc_html( $item['location_properties']['online_only'] );
            $record['location_url'] = eme_trans_esc_html( $item['location_url'] );
            echo wp_json_encode( $record );
        }
    } else {
        eme_locations_autocomplete_ajax( 1 );
    }
}

function eme_locations_autocomplete_ajax( $no_wp_die = 0 ) {
    if ( $no_wp_die == 0 ) {
        if ( ( ! isset( $_REQUEST['eme_admin_nonce'] ) && ! isset( $_REQUEST['eme_frontend_nonce'] ) ) ||
            ( isset( $_REQUEST['eme_admin_nonce'] ) && ! wp_verify_nonce( $_REQUEST['eme_admin_nonce'], 'eme_admin' ) ) ||
            ( isset( $_REQUEST['eme_frontend_nonce'] ) && ! wp_verify_nonce( $_REQUEST['eme_frontend_nonce'], 'eme_frontend' ) ) ) {
            wp_die();
        }
    }
    $res = [];
    if ( ! isset( $_REQUEST['name'] ) ) {
        echo wp_json_encode( $res );
        return;
    }

    $locations = eme_search_locations( eme_sanitize_request($_REQUEST['name']) );
    // change null to empty
    $locations = array_map(
        function( $v ) {
            return ( is_null( $v ) ) ? '' : $v;
        },
        $locations
    );

    foreach ( $locations as $item ) {
        $record                = [];
        $record['location_id'] = $item['location_id'];
        $record['name']        = eme_trans_esc_html( $item['location_name'] );
        $record['address1']    = eme_trans_esc_html( $item['location_address1'] );
        $record['address2']    = eme_trans_esc_html( $item['location_address2'] );
        $record['city']        = eme_trans_esc_html( $item['location_city'] );
        $record['state']       = eme_trans_esc_html( $item['location_state'] );
        $record['zip']         = eme_trans_esc_html( $item['location_zip'] );
        $record['country']     = eme_trans_esc_html( $item['location_country'] );
        $record['latitude']    = eme_trans_esc_html( $item['location_latitude'] );
        $record['longitude']   = eme_trans_esc_html( $item['location_longitude'] );
        $res[]                 = $record;
    }
    echo wp_json_encode( $res );
    if ( ! $no_wp_die ) {
        wp_die();
    }
}

add_action( 'wp_ajax_eme_autocomplete_locations', 'eme_locations_autocomplete_ajax' );
add_action( 'wp_ajax_nopriv_eme_autocomplete_locations', 'eme_locations_autocomplete_ajax' );
add_action( 'wp_ajax_eme_locations_list', 'eme_ajax_locations_list' );
add_action( 'wp_ajax_eme_manage_locations', 'eme_ajax_manage_locations' );
function eme_ajax_locations_list() {
    global $wpdb;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );

    if ( ! current_user_can( get_option( 'eme_cap_list_locations' ) ) ){
            $ajaxResult['Result']  = 'Error';
            $ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
            print wp_json_encode( $ajaxResult );
            wp_die();
    }

    $table         = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $search_name   = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';
    $where         = '';
    $where_arr     = [];
    if ( ! empty( $search_name ) ) {
        $where_arr[] = "(locations.location_name like '%" . $search_name . "%')";
    }

    // if the person is not allowed to manage all locations, show only events he can edit
    if ( ! current_user_can( get_option( 'eme_cap_edit_locations' ) ) ) {
        $wp_id = get_current_user_id();
        if ( ! $wp_id ) {
            $ajaxResult['Result']           = 'OK';
            $ajaxResult['Records']          = [];
            $ajaxResult['TotalRecordCount'] = 0;
            print wp_json_encode( $ajaxResult );
            wp_die();
        }
        $where_arr[] = "(location_author=$wp_id)";
    }

    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    $formfields_searchable = eme_get_searchable_formfields( 'locations' );
    $formfields            = eme_get_formfields( '', 'locations' );

    $jTableResult = [];
    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();

    if ( empty( $formfields_searchable ) ) {
        $count_sql = "SELECT COUNT(*) FROM $table AS locations $where";
        $sql       = "SELECT locations.* FROM $table AS locations $where $orderby $limit";
    } else {
        $field_ids_arr = [];
        foreach ( $formfields_searchable as $formfield ) {
            $field_ids_arr[] = $formfield['field_id'];
        }
        if ( ! empty( $_POST['search_customfieldids'] ) && eme_is_numeric_array( $_POST['search_customfieldids'] ) ) {
            $field_ids = join( ',', $_POST['search_customfieldids'] );
        } else {
            $field_ids = join( ',', $field_ids_arr );
        }
        if ( isset( $_POST['search_customfields'] ) && $_POST['search_customfields'] != '' ) {
            $search_customfields = esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_customfields'] ) ) );
            $sql_join        = "
                   INNER JOIN (SELECT related_id FROM $answers_table
                         WHERE answer LIKE '%$search_customfields%' AND field_id IN ($field_ids) AND type='location'
                         GROUP BY related_id
                        ) ans
                   ON locations.location_id=ans.related_id";
        } else {
            $sql_join = "
                   LEFT JOIN (SELECT related_id FROM $answers_table WHERE type='location'
                         GROUP BY related_id
                        ) ans
                   ON locations.location_id=ans.related_id";
        }
        $count_sql = "SELECT COUNT(*) FROM $table AS locations $sql_join $where";
        $sql       = "SELECT locations.* FROM $table AS locations $sql_join $where $orderby $limit";
    }

    $recordCount = $wpdb->get_var( $count_sql );
    $rows        = $wpdb->get_results( $sql, ARRAY_A );
    $records     = [];
    foreach ( $rows as $location ) {
        $location = eme_get_extra_location_data( $location );
        if ( empty( $location['location_name'] ) ) {
            $location['location_name'] = __( 'No name', 'events-made-easy' );
        }
        $record                  = [];
        $record['location_id']   = $location['location_id'];
        $record['location_name'] = "<a href='" . admin_url( 'admin.php?page=eme-locations&amp;eme_admin_action=edit_location&amp;location_id=' . $location['location_id'] ) . "' title='" . __( 'Edit location', 'events-made-easy' ) . "'>" . eme_trans_esc_html( $location['location_name'] ) . '</a>';
        if ( ! $location['location_latitude'] && ! $location['location_longitude'] && get_option( 'eme_map_is_active' ) && ! $location['location_properties']['online_only'] ) {
            $record['location_name'] .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_attr__( 'Location map coordinates are empty! Please edit the location to correct this, otherwise it will not show correctly on your website.', 'events-made-easy' ) . "'>";
        }
        if ( ! empty( $location['location_category_ids'] ) ) {
                        $categories            = explode( ',', $location['location_category_ids'] );
                        $record['location_name'] .= "<br><span class='eme_small' title='" . __( 'Category', 'events-made-easy' ) . "'>";
                        $cat_names             = [];
                        foreach ( $categories as $cat ) {
                                $category = eme_get_category( $cat );
                                if ( $category ) {
                                        $cat_names[] = eme_trans_esc_html( $category['category_name'] );
                                }
                        }
                        $record['location_name'] .= implode( ', ', $cat_names );
                        $record['location_name'] .= '</span>';
                }
        if ( ! empty( $location['location_properties']['max_capacity'] ) ) {
                        $record['location_name'] .= "<br><span class='eme_small' title='" . __( 'Max capacity', 'events-made-easy' ) . "'>";
            $record['location_name'] .= __( 'Max capacity', 'events-made-easy' ) . " ".$location['location_properties']['max_capacity'];
                        $record['location_name'] .= '</span>';
        }

        $record['location_address1']  = eme_trans_esc_html( $location['location_address1'] );
        $record['location_address2']  = eme_trans_esc_html( $location['location_address2'] );
        $record['location_city']      = eme_trans_esc_html( $location['location_city'] );
        $record['location_state']     = eme_trans_esc_html( $location['location_state'] );
        $record['location_zip']       = eme_trans_esc_html( $location['location_zip'] );
        $record['location_country']   = eme_trans_esc_html( $location['location_country'] );
        $record['location_latitude']  = eme_trans_esc_html( $location['location_latitude'] );
        $record['location_longitude'] = eme_trans_esc_html( $location['location_longitude'] );
        $record['external_url']       = eme_trans_esc_html( $location['location_url'] );
        $record['online_only']        = $location['location_properties']['online_only'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $location_url                 = eme_location_url( $location );
        $record['view']               = "<a href='$location_url'>" . __( 'View location', 'events-made-easy' ) . '</a>';
        $copy_link='window.location.href="'.admin_url( 'admin.php?page=eme-locations&amp;eme_admin_action=copy_location&amp;location_id=' . $location['location_id'] ).'";';
        $record[ 'copy'] = "<button onclick='$copy_link' title='" . __( 'Duplicate this location', 'events-made-easy' ) . "' class='jtable-command-button eme-copy-button'><span>copy</span></a>";
        $location_cf_values           = eme_get_location_answers( $location['location_id'] );
        foreach ( $formfields as $formfield ) {
            foreach ( $location_cf_values as $val ) {
                if ( $val['field_id'] == $formfield['field_id'] && ! empty( $val['answer'] ) ) {
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
        $files = eme_get_uploaded_files( $location['location_id'], 'locations' );
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
    $jTableResult['Result']           = 'OK';
    $jTableResult['Records']          = $records;
    $jTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_manage_locations() {
    $current_userid = get_current_user_id();
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $jTableResult = [];
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
            case 'deleteLocations':
                $ids_arr = explode( ',', eme_sanitize_request($_POST['location_id']) );
                $to_id   = intval( $_POST['transferto_id'] );
                foreach ( $ids_arr as $location_id ) {
                    $location = eme_get_location( intval( $location_id ) );
                    if ( ! empty( $location ) && ( current_user_can( get_option( 'eme_cap_edit_locations' ) ) ||
                        ( current_user_can( get_option( 'eme_cap_author_locations' ) ) && ( $location['location_author'] == $current_userid ) ) ) ) {
                        eme_delete_location( intval( $location_id ), $to_id );
                    }
                }
                $jTableResult['Result'] = 'OK';
                break;
        }
    }
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_get_location_post_answers() {
    $answers = [];
    foreach ( $_POST as $key => $value ) {
        if ( preg_match( '/^FIELD(\d+)$/', eme_sanitize_request( $key ), $matches ) ) {
            $field_id  = intval( $matches[1] );
            $formfield = eme_get_formfield( $field_id );
            if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'locations' ) {
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

function eme_get_location_cf_answers( $location_id ) {
    return eme_get_location_answers( $location_id );
}

function eme_get_location_answers( $location_id ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $cf            = wp_cache_get( "eme_location_cf $location_id" );
    if ( $cf === false ) {
        $sql = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='location'", $location_id );
        $cf  = $wpdb->get_results( $sql, ARRAY_A );
        wp_cache_set( "eme_location_cf $location_id", $cf, '', 60 );
    }
    return $cf;
}

// for backwards compat
function eme_location_store_cf_answers( $location_id ) {
    return eme_location_store_answers( $location_id );
}

function eme_location_store_answers( $location_id ) {
    $answer_ids_seen = [];

    $all_answers   = eme_get_location_answers( $location_id );
    $found_answers = eme_get_location_post_answers();
    foreach ( $found_answers as $answer ) {
        $formfield = eme_get_formfield( $answer['field_id'] );
        if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'locations' ) {
            $answer_id = eme_get_answerid( $all_answers, $location_id, 'location', $answer['field_id'] );
            if ( $answer_id ) {
                eme_update_answer( $answer_id, $answer['answer'] );
            } else {
                $answer_id = eme_insert_answer( 'location', $location_id, $answer['field_id'], $answer['answer'] );
            }
            $answer_ids_seen[] = $answer_id;
        }
    }

    // delete old answer_ids
    foreach ( $all_answers as $answer ) {
        if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && $location_id > 0 && $answer['type'] == 'location' && $answer['related_id'] == $location_id ) {
            eme_delete_answer( $answer['answer_id'] );
        }
    }
    wp_cache_delete( "eme_location_cf $location_id" );
}

function eme_get_cf_location_ids( $val, $field_id, $is_multi = 0 ) {
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
    $sql = "SELECT DISTINCT related_id FROM $table WHERE field_id=$field_id AND type='location' $condition";
    return $wpdb->get_col( $sql );
}

?>
