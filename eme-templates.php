<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_template() {
    $template               = [
        'name'        => '',
        'description' => '',
        'format'      => '',
        'type'        => '',
        'properties'  => [],
    ];
    $template['properties'] = eme_init_template_props( $template['properties'] );
    return $template;
}

function eme_init_template_props( $props ) {
    if ( ! isset( $props['pdf_width'] ) || empty( $props['pdf_width'] ) ) {
        $props['pdf_width'] = 0;
    }
    if ( ! isset( $props['pdf_height'] ) || empty( $props['pdf_height'] ) ) {
        $props['pdf_height'] = 0;
    }
    if ( ! isset( $props['pdf_size'] ) ) {
        $props['pdf_size'] = 'a4';
    }
    if ( ! isset( $props['pdf_orientation'] ) ) {
        $props['pdf_orientation'] = 'portrait';
    }
    if ( ! isset( $props['pdf_margins'] ) || empty( $props['pdf_margins'] ) ) {
        $props['pdf_margins'] = '0';
    }
    if ( ! isset( $props['pdf_attach_format'] ) || empty( $props['pdf_attach_format'] ) ) {
        $props['pdf_attach_format'] = '';
    }
    return $props;
}

function eme_template_types() {
    $arr = [
        ''               => __( 'All', 'events-made-easy' ),
        'event'          => __( 'Event', 'events-made-easy' ),
        'rsvpform'       => __( 'RSVP form', 'events-made-easy' ),
        'rsvpmail'       => __( 'RSVP related mail', 'events-made-easy' ),
        'taskform'       => __( 'Task form', 'events-made-easy' ),
        'taskmail'       => __( 'Task related mail', 'events-made-easy' ),
        'membershipform' => __( 'Membership form', 'events-made-easy' ),
        'membershipmail' => __( 'Membership related mail', 'events-made-easy' ),
        'mail'           => __( 'Generic mail', 'events-made-easy' ),
        'shortcodes'     => __( 'Only used in shortcodes', 'events-made-easy' ),
        'pdf'            => __( 'PDF output template', 'events-made-easy' ),
        'html'           => __( 'HTML output template', 'events-made-easy' ),
    ];
    return $arr;
}

function eme_templates_page() {
    global $wpdb;

    if ( ! current_user_can( get_option( 'eme_cap_templates' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
        $message = __( 'You have no right to update templates!', 'events-made-easy' );
        eme_templates_table_layout( $message );
        return;
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_template' ) {
        // edit template
        $template_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        eme_templates_edit_layout( $template_id );
        return;
    }

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'copy_template' ) {
        $template_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $template    = eme_get_template( $template_id );
        if ( empty( $template ) ) {
            $template = eme_new_template();
        }
        // make it look like a new template
        if ( isset( $template['id'] ) ) {
            unset( $template['id'] );
        }
        $template['name'] .= __( ' (Copy)', 'events-made-easy' );

        eme_templates_edit_layout( 0, '', $template );
        return;
    }

    if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_template' ) {
        // add template
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_templates_edit_layout();
        return;
    }

    // Insert/Update/Delete Record
    $templates_table   = EME_DB_PREFIX . EME_TEMPLATES_TBNAME;
    $validation_result = true;
    $message           = '';
    if ( isset( $_POST['eme_admin_action'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( $_POST['eme_admin_action'] == 'do_edittemplate' && isset( $_POST['description'] ) && isset( $_POST['template_format'] ) ) {
            // template update required
            $template                = [];
            $properties              = [];
            $template['name']        = eme_sanitize_request( $_POST['name'] );
            $template['description'] = eme_sanitize_request( $_POST['description'] );
            $template['type']        = eme_sanitize_request( $_POST['type'] );
            $template['format']      = eme_kses_maybe_unfiltered( $_POST['template_format'] );
            $template['modif_date']  = current_time( 'mysql', false );
            if ( isset( $_POST['properties'] ) ) {
                $properties = eme_sanitize_request( $_POST['properties'] );
            }
            $template['properties'] = eme_serialize( eme_init_template_props( $properties ) );

            $template_id = 0;
            if ( $properties['pdf_size'] == 'custom' && ( empty( $properties['pdf_width'] ) || empty( $properties['pdf_height'] ) ) ) {
                $validation_result = false;
                $message           = __( "When choosing 'custom' as PDF size, please specify width and height.", 'events-made-easy' );
            } elseif ( isset( $_POST['template_id'] ) && intval( $_POST['template_id'] ) > 0 ) {
                $template_id       = intval( $_POST['template_id'] );
                $validation_result = $wpdb->update( $templates_table, $template, [ 'id' => $template_id ] );
                if ( $validation_result !== false ) {
                    $message = __( 'Successfully edited the template.', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem editing your template, please try again.', 'events-made-easy' );
                }
            } else {
                $validation_result = $wpdb->insert( $templates_table, $template );
                if ( $validation_result !== false ) {
                    $template_id = $wpdb->insert_id;
                    $message     = __( 'Successfully added the template.', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem adding your template, please try again.', 'events-made-easy' );
                }
            }

            wp_cache_delete( "eme_template $template_id" );
            if ( get_option( 'eme_stay_on_edit_page' ) || $validation_result === false ) {
                eme_templates_edit_layout( $template_id, $message, $template );
                return;
            }
        }
    }
    eme_templates_table_layout( $message );
}

function eme_templates_table_layout( $message = '' ) {
    global $plugin_page;

    $template_types = eme_template_types();
    $destination    = admin_url( "admin.php?page=$plugin_page" );
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
         <h1>" . __( 'Manage templates', 'events-made-easy' ) . "</h1>\n ";

    ?>
    <div id="templates-message" class="notice is-dismissible eme-message-admin <?php echo esc_attr( $hidden_class ); ?>">
        <p><?php echo wp_kses_post( $message ); ?></p>
    </div>

    <div class="wrap">
    <form id="templates-new" method="post" action="<?php echo esc_url( $destination ); ?>">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
            <input type="hidden" name="eme_admin_action" value="add_template">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add template', 'events-made-easy' ); ?>">
        </form>
    </div>
    <br><br>
    <form action="#" method="post">
    <?php echo eme_ui_select( '', 'search_type', $template_types ); ?>
    <input type="search" name="search_name" id="search_name" placeholder="<?php esc_attr_e( 'Template name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=20>
    <button id="TemplatesLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter templates', 'events-made-easy' ); ?></button>
    </form>

    <div id="bulkactions">
    <form id='templates-form' action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteTemplates"><?php esc_html_e( 'Delete selected templates', 'events-made-easy' ); ?></option>
    </select>
    <button id="TemplatesActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="TemplatesTableContainer"></div>
    </div>
    </div>
    <?php
}

function eme_templates_edit_layout( $template_id = 0, $message = '', $template = '' ) {
    global $plugin_page;

    if ( ! empty( $template ) ) {
        if ( eme_is_serialized( $template['properties'] ) ) {
            $template['properties'] = eme_init_template_props( eme_unserialize( $template['properties'] ) );
        }
    }
    if ( $template_id ) {
        if ( empty( $template ) ) {
            $template = eme_get_template( $template_id );
        }
        $h1_string     = __( 'Edit template', 'events-made-easy' );
        $action_string = __( 'Update template', 'events-made-easy' );
    } else {
        if ( empty( $template ) ) {
            $template = eme_new_template();
        }
        $h1_string     = __( 'Create template', 'events-made-easy' );
        $action_string = __( 'Add template', 'events-made-easy' );
    }
    $template_types      = eme_template_types();
    $orientation_array   = [
        'portrait'  => __( 'Portrait', 'events-made-easy' ),
        'landscape' => __( 'Landscape', 'events-made-easy' ),
    ];

    $size_array = [ 'custom' => __( 'Custom', 'events-made-easy' ) ];
    require_once 'dompdf/vendor/autoload.php';
    foreach ( Dompdf\Adapter\CPDF::$PAPER_SIZES as $key => $val ) {
        $size_array[ $key ] = strtoupper( $key );
    }

    echo "
   <div class='wrap'>
      <div id='poststuff'>
      <div id='icon-edit' class='icon32'>
      </div>

      <h1>" . $h1_string . '</h1>';

    if ( $message != '' ) {
        echo "
      <div id='message' class='updated notice notice-success is-dismissible'>
         <p>$message</p>
      </div>";
    }
?>

        <div id='ajax-response'></div>
        <form name='edit_template' id='edit_template' method='post' action='<?php echo esc_url( admin_url( "admin.php?page=$plugin_page" ) ); ?>' class='validate'>
        <input type='hidden' name='eme_admin_action' value='do_edittemplate'>
        <input type='hidden' name='template_id' value='<?php echo esc_attr( $template_id ); ?>'>
        <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false ); ?>
        <table>
            <tr>
            <td><?php esc_html_e( 'Name', 'events-made-easy' ); ?></label></td>
            <td><input required='required' id='name' name='name' type='text' value='<?php echo eme_esc_html( $template['name'] ); ?>' size='40'></td>
            </tr>
            <tr>
            <td><?php esc_html_e( 'Description', 'events-made-easy' ); ?></label></td>
            <td><input id='description' name='description' type='text' value='<?php echo eme_esc_html( $template['description'] ); ?>' size='40'></td>
            </tr>
            <tr>
            <td><?php esc_html_e( 'Format', 'events-made-easy' ); ?></label></td>
            <td><?php eme_wysiwyg_textarea( 'template_format', $template['format'], 1, 1 ); ?>
<?php
    if ( current_user_can( 'unfiltered_html' ) ) {
        echo "<div class='eme_notice_unfiltered_html'>";
        esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
        echo '</div>';
    }
    esc_html_e( 'Newlines will get translated to HTML br-tags when/where/if appropriate but for templates that will get used in HTML output: be sure to not include newlines (certainly not empty lines) unless wanted.', 'events-made-easy' );
?>
            </td>
            </tr>
            <tr>
            <td style='vertical-align:top'><?php esc_html_e( 'Type', 'events-made-easy' ); ?></label></td>
            <td><?php echo eme_ui_select( $template['type'], 'type', $template_types ); ?>
            <br><?php esc_html_e( 'The type allows you to indicate where you want to use this template. This helps to limit the dropdown list of templates to chose from in other parts of EME.', 'events-made-easy' ); ?>
            <br><?php esc_html_e( "The type 'All' means it can be selected anywhere where template selections are possible.", 'events-made-easy' ); ?>
            <br><?php esc_html_e( "The type 'PDF' is used for PDF templating and allows more settings concerning page size, orientation, ...", 'events-made-easy' ); ?>
            <br><?php esc_html_e( "If you know the template is only used in/for shortcodes, use the type 'Shortcode'.", 'events-made-easy' ); ?>
            </td>
            </tr>
        </table>

        <table class='form-table' id='pdf_properties'>
            <tr class='form-field'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF size', 'events-made-easy' ); ?></th>
            <td><?php echo eme_ui_select( $template['properties']['pdf_size'], 'properties[pdf_size]', $size_array ); ?><br>
            <?php esc_html_e( "If you select 'Custom', you can enter your own widht/height below.", 'events-made-easy' ); ?></td>
            </tr>
            <tr class='form-field'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF orientation', 'events-made-easy' ); ?></th>
            <td><?php echo eme_ui_select( $template['properties']['pdf_orientation'], 'properties[pdf_orientation]', $orientation_array ); ?></td>
            </tr>
            <tr class='form-field'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF margins', 'events-made-easy' ); ?></th>
            <td><input type='text' name='properties[pdf_margins]' id='properties[pdf_margins]' value='<?php echo eme_esc_html( $template['properties']['pdf_margins'] ); ?>' size='40'><br>
            <?php esc_html_e( "See <a href='https://www.w3schools.com/cssref/pr_margin.asp'>this page</a> for info on what you can enter here.", 'events-made-easy' ); ?></td>
            </tr>
            <tr class='form-field template-pdf-custom'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF width', 'events-made-easy' ); ?></th>
            <td><input type='text' name='properties[pdf_width]' id='properties[pdf_width]' value='<?php echo eme_esc_html( $template['properties']['pdf_width'] ); ?>' size='40'><br>
            <?php esc_html_e( 'The width of the PDF document (in pt)', 'events-made-easy' ); ?></td>
            </tr>
            <tr class='form-field template-pdf-custom'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF height', 'events-made-easy' ); ?></th>
            <td><input type='text' name='properties[pdf_height]' id='properties[pdf_height]' value='<?php echo eme_esc_html( $template['properties']['pdf_height'] ); ?>' size='40'><br>
            <?php esc_html_e( 'The heigth of the PDF document (in pt)', 'events-made-easy' ); ?></td>
            </tr>
            <tr class='form-field'>
            <th scope='row' style='vertical-align:top'><?php esc_html_e( 'PDF mail attach format', 'events-made-easy' ); ?></th>
            <td><input type='text' name='properties[pdf_attach_format]' id='properties[pdf_attach_format]' value='<?php echo eme_esc_html( $template['properties']['pdf_attach_format'] ); ?>' size='40'><br>
            <?php esc_html_e( "When the template is being used as an attacment in a mail, the attachment has a default name. If you don't like the name given to the attachment in the mail, you can change it here. Relevant placeholders are allowed in their context (event/membership/booking/member/...). The '.pdf' extension will get added automatically, so no need to mention it.", 'events-made-easy' ); ?></td>
            </tr>
        </table>
        <p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo esc_attr( $action_string ); ?>'></p>
        </form>
    </div>
    </div>
<?php
}

function eme_get_templates( $type = '', $strict = 0 ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TEMPLATES_TBNAME;
    if ( ! empty( $type ) ) {
        if ( $strict ) {
            $sql = $wpdb->prepare( "SELECT * FROM $table WHERE type=%s ORDER BY type,name", $type );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM $table WHERE type='' OR type=%s ORDER BY type,name", $type );
        }
        return $wpdb->get_results( $sql, ARRAY_A );
    } else {
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY type,name", ARRAY_A );
    }
}

function eme_get_templates_name_id( $type = '', $strict = 0 ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_TEMPLATES_TBNAME;
    if ( ! empty( $type ) ) {
        if ( $strict ) {
            $sql = $wpdb->prepare( "SELECT name,id FROM $table WHERE type=%s ORDER BY type,name", $type );
        } else {
            $sql = $wpdb->prepare( "SELECT name,id FROM $table WHERE type='' OR type=%s ORDER BY type,name", $type );
        }
        return $wpdb->get_results( $sql, ARRAY_A );
    } else {
        return $wpdb->get_results( "SELECT name,id FROM $table ORDER BY type,name", ARRAY_A );
    }
}

function eme_get_templates_array_by_id( $type = '', $strict = 0 ) {
    $templates       = eme_get_templates_name_id( $type, $strict );
    $templates_by_id = [];
    if ( is_array( $templates ) && count( $templates ) > 0 ) {
        $templates_by_id[0] = '&nbsp;';
    } else {
        $templates_by_id[0] = __( 'No templates defined yet!', 'events-made-easy' );
    }
    foreach ( $templates as $template ) {
        $templates_by_id[ $template['id'] ] = $template['name'];
    }
    return $templates_by_id;
}

function eme_get_template( $template_id ) {
    global $wpdb;
    // let's do this correct
    $template_id = intval( $template_id );
    $template    = wp_cache_get( "eme_template $template_id" );
    if ( $template === false ) {
        $templates_table = EME_DB_PREFIX . EME_TEMPLATES_TBNAME;
        $sql             = $wpdb->prepare( "SELECT * FROM $templates_table WHERE id = %d", $template_id );
        $template        = $wpdb->get_row( $sql, ARRAY_A );
        if ( $template !== false ) {
            if ( empty( $template['properties'] ) ) {
                $template['properties'] = [];
            }
            $template['properties'] = eme_init_template_props( eme_unserialize( $template['properties'] ) );
            wp_cache_set( "eme_template $template_id", $template, '', 10 );
        }
    }
    return $template;
}

function eme_get_template_format( $template_id, $nl2br_wanted = 1 ) {
    if ( ! $template_id ) {
        return;
    }
    $template = eme_get_template( $template_id );
    // in the case the template wasn't found ...
    if ( ! $template || ! isset( $template['format'] ) ) {
        return '';
    }

    // translate possible language tags
    // $format=eme_translate($template['format']);
    $format = $template['format'];

    preg_match_all( '/#_INCLUDE_TEMPLATE(\{\d+\})/', $format, $placeholders );
    foreach ( $placeholders[0] as $result ) {
        if ( preg_match( '/#_INCLUDE_TEMPLATE\{(\d+)\}/', $result, $matches ) ) {
            $tmp_template_id = $matches[1];
            if ( $tmp_template_id != $template_id ) {
                $replacement = eme_get_template_format( $tmp_template_id, $nl2br_wanted );
                $format      = str_replace( $result, $replacement, $format );
            }
        }
    }
    // interpret EME language tags already, so if the format contains placeholders (that - once expanded - might contain other language tags), don't get confused (nested tags not supported yet).
    $format = eme_translate_string( $format );
    // if we don't want nl2br, return the format as is
    if ( $nl2br_wanted == 0 || empty( $format ) ) {
        return $format;
    }

    if ( preg_match( '/mail/', $template['type'] ) ) {
        if ( get_option( 'eme_mail_send_html' ) ) {
            return eme_nl2br_save_html( $format );
        } else {
            return $format;
        }
    } else {
        return eme_nl2br_save_html( $format );
    }
}

// use the next call to get a template format for mail sending
function eme_get_template_format_plain( $template_id ) {
    return eme_get_template_format( $template_id, 0 );
}

add_action( 'wp_ajax_eme_templates_list', 'eme_ajax_templates_list' );
add_action( 'wp_ajax_eme_manage_templates', 'eme_ajax_manage_templates' );
add_action( 'wp_ajax_eme_get_template', 'eme_ajax_get_template' );
//add_action( 'wp_ajax_eme_get_template_plain', 'eme_ajax_get_template_plain' );

function eme_ajax_templates_list() {
    global $wpdb;
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    $table          = EME_DB_PREFIX . EME_TEMPLATES_TBNAME;
    $template_types = eme_template_types();
    $fTableResult   = [];
    $search_type    = isset( $_POST['search_type'] ) ? esc_sql( eme_sanitize_request( $_POST['search_type'] ) ) : '';
    $search_name    = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';

    $where     = '';
    $where_arr = [];
    if ( ! empty( $search_name ) ) {
        $where_arr[] = "name like '%" . $search_name . "%'";
    }
    if ( ! empty( $search_type ) ) {
        $where_arr[] = "(type = '$search_type')";
    }
    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    if ( current_user_can( get_option( 'eme_cap_templates' ) ) ) {
        $sql         = "SELECT COUNT(*) FROM $table $where";
        $recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
        $orderby     = eme_get_datatables_orderby();
        $sql         = "SELECT * FROM $table $where $orderby $limit";
        $res         = $wpdb->get_results( $sql, ARRAY_A );
        $rows        = [];
        foreach ( $res  as $key => $val ) {
            if ( empty( $val['name'] ) ) {
                $val['name'] = __( 'No name', 'events-made-easy' );
            }

            $row = [];
            $row['id'] = $val['id'];
            $row['description'] = $val['description'];
            $row[ 'type'] = $template_types[ $val['type'] ];
            $row[ 'name'] = "<a href='" . admin_url( 'admin.php?page=eme-templates&amp;eme_admin_action=edit_template&amp;id=' . $val['id'] ) . "'>" . $val['name'] . '</a>';
            $copy_link='window.location.href="'.admin_url( 'admin.php?page=eme-templates&amp;eme_admin_action=copy_template&amp;id=' . $val['id'] ).'";';
            $row[ 'copy'] = "<button onclick='$copy_link' title='" . __( 'Duplicate this template', 'events-made-easy' ) . "' class='ftable-command-button eme-copy-button'><span>copy</span></a>";
            $rows[] = $row;
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

function eme_ajax_manage_templates() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    if ( !current_user_can( get_option( 'eme_cap_templates' ) ) ) {
        wp_die();
    }
    if ( isset( $_REQUEST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_REQUEST['do_action'] );
        switch ( $do_action ) {
        case 'deleteTemplates':
            eme_ajax_record_delete( EME_TEMPLATES_TBNAME, 'eme_cap_templates', 'id' );
            break;
        }
    }
    wp_die();
}

//function eme_ajax_get_template_plain() {
//   $ajaxResult=array();
//   if (isset($_REQUEST['template_id']) && intval($_REQUEST['template_id'])>0) {
//     $ajaxResult['htmlmessage']=eme_get_template_format_plain($_REQUEST['template_id']);
//   } else {
//     $ajaxResult['htmlmessage']='';
//   }
//   echo wp_json_encode($ajaxResult);
//   wp_die();
//}

function eme_ajax_get_template() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    // only used when sending mail, so limit to that
    if ( ! (
        current_user_can( get_option( 'eme_cap_send_mails' ) ) || 
        current_user_can( get_option( 'eme_cap_send_other_mails' ) ) || 
        current_user_can( get_option( 'eme_cap_send_generic_mails' ) ) || 
        current_user_can( get_option( 'eme_cap_manage_mails' ) )
    ) ) {
    wp_die();
    }
    $ajaxResult = [];
    if ( isset( $_POST['template_id'] ) && intval( $_POST['template_id'] ) > 0 ) {
        $ajaxResult['htmlmessage'] = eme_get_template_format( $_POST['template_id'] );
    } else {
        $ajaxResult['htmlmessage'] = '';
    }
    echo wp_json_encode( $ajaxResult );
    wp_die();
}

?>
