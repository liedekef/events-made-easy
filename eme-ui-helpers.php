<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_option_items( $arr, $saved_value ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $arr ) ) {
        return;
    }

    $output = '';
    foreach ( $arr as $key => $item ) {
        $selected = '';
        if ( is_array( $saved_value ) ) {
            in_array( $key, $saved_value ) ? $selected = "selected='selected' " : $selected = '';
        } else {
            "$key" == $saved_value ? $selected = "selected='selected' " : $selected = '';
        }
        $output .= "<option value='" . eme_esc_html( $key ) . "' $selected >" . eme_esc_html( $item ) . "</option>\n";
    }
    echo $output;
}

function eme_checkbox_items( $name, $arr, $saved_values, $horizontal = true ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $arr ) ) {
        return;
    }

    $output = '';
    $name   = wp_strip_all_tags( $name );
    foreach ( $arr as $key => $item ) {
        $checked = '';
        if ( in_array( $key, $saved_values ) ) {
            $checked = "checked='checked'";
        }
        $id = esc_attr( eme_get_field_id( $name, $key ));
        $output .= "<input type='checkbox' name='$name' id='$id' value='" . eme_esc_html( $key ) . "' $checked>&nbsp;<label for='$id'>" . eme_esc_html_keep_br( $item ) . '</label>';
        if ( $horizontal ) {
            $output .= "&nbsp;";
        } else {
            $output .= "<br>\n";
        }
    }
    echo $output;
}

function eme_options_input_type( $title, $name, $description, $type = 'text', $option_value = false ) {
    $name = wp_strip_all_tags( $name );
    if ( $option_value === false ) {
        $option_value = eme_nl2br( get_option( $name ) );
    }
    $autocomplete = '';
    if ( $type == "password" ) {
        $autocomplete = 'autocomplete="new-password"';
    }
?>
    <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
        <th scope="row"><label for='<?php echo $name; ?>'><?php echo eme_esc_html_keep_br( $title ); ?></label></th>
        <td>
<?php echo "<input $autocomplete name='$name' type='$type' id='$name' style='width: 95%;' value='" . eme_esc_html( $option_value ) . "' size='45'>";
if ( ! empty( $description ) ) {
    echo '<br>' . $description;
}
?>
        </td>
    </tr>
<?php
}

function eme_options_input_text( $title, $name, $description, $option_value = false ) {
    eme_options_input_type( $title, $name, $description, "text", $option_value);
}

function eme_options_input_int( $title, $name, $description, $option_value = false ) {
    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = intval( get_option( $name ) );
    }
    eme_options_input_type( $title, $name, $description, "number", $option_value);
}

function eme_options_input_password( $title, $name, $description ) {
    $name = wp_strip_all_tags( $name );
    $option_value = get_option( $name );
    eme_options_input_type( $title, $name, $description, "password", $option_value);
}

function eme_options_textarea( $title, $name, $description, $show_wp_editor = 0, $show_full = 0, $option_value = false ) {
    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = get_option( $name );
    }
    $option_value = eme_nl2br_save_html( $option_value );
?>
    <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
    <th scope="row"><label for='<?php echo $name; ?>'><?php echo eme_esc_html_keep_br( $title ); ?></label></th>
    <td>
<?php
    eme_wysiwyg_textarea( $name, $option_value, $show_wp_editor, $show_full);
    if ( ! empty( $description ) ) {
        echo '<br>' . $description;
    }
?>
    </td>
    </tr>
<?php
}

function eme_options_radio_binary( $title, $name, $description, $option_value = false ) {
    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = get_option( $name );
    }
?>
        <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
            <th scope="row"><?php echo esc_html( $title ); ?></th>
            <td>
            <input id="<?php echo $name; ?>_yes" name="<?php echo $name; ?>" type="radio" value="1" <?php if ( $option_value ) { echo "checked='checked'";} ?> ><label for='<?php echo $name; ?>_yes'><?php esc_html_e( 'Yes', 'events-made-easy' ); ?> <br>
            <input  id="<?php echo $name; ?>_no" name="<?php echo $name; ?>" type="radio" value="0" <?php if ( ! $option_value ) { echo "checked='checked'";} ?> ><label for='<?php echo $name; ?>_no'><?php esc_html_e( 'No', 'events-made-easy' ); ?>
<?php
    if ( ! empty( $description ) ) {
        echo '<br>' . $description;
    }
?>
        </td>
        </tr>
<?php
}

function eme_options_input_list( $title, $name, $list, $description, $option_value = false ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = get_option( $name );
    }
?>
    <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
    <th scope="row"><label for='<?php echo $name; ?>'><?php echo eme_esc_html_keep_br( $title ); ?></label></th>
    <td>
<?php
    echo eme_ui_list( $option_value, $name, $list );
    if ( ! empty( $description ) ) {
        echo '<br>' . $description;
    }
?>
    </td>
    </tr>
<?php
}

function eme_options_select( $title, $name, $list, $description, $option_value = false, $add_empty_first = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = get_option( $name );
    }
?>
    <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
    <th scope="row"><label for='<?php echo $name; ?>'><?php echo eme_esc_html_keep_br( $title ); ?></label></th>
    <td>
<?php
    echo eme_ui_select( $option_value, $name, $list, $add_empty_first );
    if ( ! empty( $description ) ) {
        echo '<br>' . $description;
    }
?>
    </td>
    </tr>
<?php
}

function eme_options_multiselect( $title, $name, $list, $description, $option_value = false, $class = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    $name = wp_strip_all_tags( $name );
    if ( ! $option_value ) {
        $option_value = get_option( $name );
    }
    if ( ! empty( $option_value ) && ! is_array( $option_value ) && strstr( $option_value, ',' ) ) {
        $tmp_arr          = explode( ',', $option_value );
        $option_value_arr = [];
        foreach ( $tmp_arr as $val ) {
            $option_value_arr[ $val ] = $val;
        }
    } else {
        $option_value_arr = $option_value;
    }
?>
    <tr style='vertical-align:top' id='<?php echo $name; ?>_row'>
    <th scope="row"><label for='<?php echo $name; ?>'><?php echo eme_esc_html_keep_br( $title ); ?></label></th>
    <td>
<?php
    echo eme_ui_multiselect( $option_value_arr, $name, $list, 5, '', 0, $class );
    if ( ! empty( $description ) ) {
        echo '<br>' . $description;
    }
?>
    </td>
    </tr>
<?php
}

function eme_ui_select_binary( $option_value, $name, $required = 0, $class = '', $extra_attributes = '' ) {
    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $name         = wp_strip_all_tags( $name );
    $val          = "<select $class_att $required_att name='$name' id='$name' $extra_attributes >";
    $selected_YES = '';
    $selected_NO  = '';
    if ( $option_value ) {
        $selected_YES = "selected='selected'";
    } else {
        $selected_NO = "selected='selected'";
    }
    $val .= "<option value='0' $selected_NO>" . __( 'No', 'events-made-easy' ) . '</option>';
    $val .= "<option value='1' $selected_YES>" . __( 'Yes', 'events-made-easy' ) . '</option>';
    $val .= ' </select>';
    return $val;
}

function eme_form_select( $option_value, $name, $id, $list, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $class_att $required_att id='$id' name='$name' $extra_attributes >";
    if ( $add_empty_first != '' ) {
        $val .= "<option value=''>$add_empty_first</option>";
    }
    foreach ( $list as $key => $value ) {
        if ( is_array( $value ) ) {
            $t_key   = $value[0];
            $t_value = eme_esc_html( $value[1] );
        } else {
            $t_key   = $key;
            $t_value = eme_esc_html( $value );
        }
        if ( empty( $t_value ) && $t_value !== '0' ) {
            $t_value = '&nbsp;';
        }
        "$t_key" === "$option_value" ? $selected = "selected='selected' " : $selected = '';
        $val                                    .= "<option value='" . eme_esc_html( $t_key ) . "' $selected>$t_value</option>";
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_list( $option_value, $name, $list, $required = 0, $class = '', $extra_attributes = '') {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $random_id = eme_random_id();
    $datalist_id = $name."_".$random_id;
    $val = "<input list='$datalist_id' $class_att $required_att id='$name' name='$name' value='$option_value' $extra_attributes >";
    $val .= "<datalist id='$datalist_id'>";
    foreach ( $list as $key => $value ) {
        $val .= "<option value='".eme_esc_html( $value )."'>";
    }
    $val .= "</datalist>";
    return $val;
}

function eme_ui_select( $option_value, $name, $list, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $class_att $required_att id='$name' name='$name' $extra_attributes >";
    if ( $add_empty_first != '' ) {
        $val .= "<option value=''>$add_empty_first</option>";
    }
    foreach ( $list as $key => $value ) {
        if ( is_array( $value ) ) {
            $t_key   = $value[0];
            $t_value = eme_esc_html( $value[1] );
        } else {
            $t_key   = $key;
            $t_value = eme_esc_html( $value );
        }
        if ( empty( $t_value ) && $t_value !== '0' ) {
            $t_value = '&nbsp;';
        }
        if ( $t_key == 'BEGINOPTGROUP' ) {
            $val .= "<optgroup label='" . eme_esc_html( $t_value ) . "'>";
        } elseif ( $t_key == 'ENDOPTGROUP' ) {
            $val .= "</optgroup>";
        } else {
            "$t_key" === "$option_value" ? $selected = "selected='selected' " : $selected = '';
            $val .= "<option value='" . eme_esc_html( $t_key ) . "' $selected>$t_value</option>";
        }
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_select_inverted( $option_value, $name, $list, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $class_att $required_att id='$name' name='$name' $extra_attributes >";
    if ( ! empty( $add_empty_first ) ) {
        $val .= "<option value=''>$add_empty_first</option>";
    }
    foreach ( $list as $value => $key ) {
        $t_value = eme_esc_html( $value );
        if ( empty( $t_value ) ) {
            $t_value = '&nbsp;';
        }
        "$key" === "$option_value" ? $selected = "selected='selected' " : $selected = '';
        $val                                  .= "<option value='" . eme_esc_html( $key ) . "' $selected>$t_value</option>";
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_select_key_value( $option_value, $name, $list, $key, $value, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $class_att $required_att id='$name' name='$name' $extra_attributes >";
    if ( $add_empty_first != '' ) {
        $val .= "<option value=''>" . eme_esc_html( $add_empty_first ) . '</option>';
    }
    foreach ( $list as $line ) {
        $t_key   = $line[ $key ];
        $t_value = eme_esc_html( $line[ $value ] );
        if ( empty( $t_value ) && $t_value !== '0' ) {
            $t_value = '&nbsp;';
        }
        "$t_key" == $option_value ? $selected = "selected='selected' " : $selected = '';
        $val .= "<option value='" . eme_esc_html( $t_key ) . "' $selected>$t_value</option>";
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_multiselect( $option_value, $name, $list, $size = 5, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '', $disable_first_option = 0, $id_prefix = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $required_att $class_att $extra_attributes multiple='multiple' name='{$name}[]' id='{$id_prefix}{$name}' size='$size'>";
    if ( $add_empty_first != '' ) {
        if ($disable_first_option) {
            $val .= "<option disabled='disabled' value=''>" . eme_esc_html( $add_empty_first ) . '</option>';
        } else {
            $val .= "<option value=''>" . eme_esc_html( $add_empty_first ) . '</option>';
        }
    }
    foreach ( $list as $key => $value ) {
        $selected = '';
        if ( is_array( $value ) ) {
            $t_key   = $value[0];
            $t_value = eme_esc_html( $value[1] );
        } else {
            $t_key   = $key;
            $t_value = eme_esc_html( $value );
        }
        if ( ! empty( $t_key ) ) {
            if ( is_array( $option_value ) ) {
                if (in_array( $t_key, $option_value )) $selected = "selected='selected' ";
            } else {
                if ("$t_key" == $option_value) $selected = "selected='selected' ";
            }
        }
        if ( $t_key == 'BEGINOPTGROUP' ) {
            $val .= "<optgroup label='" . eme_esc_html( $t_value ) . "'>";
        } elseif ( $t_key == 'ENDOPTGROUP' ) {
            $val .= "</optgroup>";
        } else {
            $val .= "<option value='" . eme_esc_html( $t_key ) . "' $selected>$t_value</option>";
        }
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_multiselect_key_value( $option_value, $name, $list, $key, $value, $size = 3, $add_empty_first = '', $required = 0, $class = '', $extra_attributes = '', $disable_first_option = 0, $id_prefix = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $name = wp_strip_all_tags( $name );
    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $val = "<select $required_att $class_att $extra_attributes multiple='multiple' name='{$name}[]' id='{$id_prefix}{$name}' size='$size'>";
    if ( ! empty( $add_empty_first ) ) {
        if ($disable_first_option) {
            $val .= "<option disabled='disabled' value=''>" . eme_esc_html( $add_empty_first ) . '</option>';
        } else {
            $val .= "<option value=''>" . eme_esc_html( $add_empty_first ) . '</option>';
        }
    }
    foreach ( $list as $line ) {
        $selected = '';
        $t_key   = $line[ $key ];
        $t_value = eme_esc_html( $line[ $value ] );
        if ( ! empty( $t_key ) ) {
            if ( is_array( $option_value ) ) {
                if (in_array( $t_key, $option_value )) $selected = "selected='selected' ";
            } else {
                if ("$t_key" == $option_value) $selected = "selected='selected' ";
            }
        }
        if ( empty( $t_value ) ) {
            $t_value = '&nbsp;';
        }
        $val .= "<option value='" . eme_esc_html( $t_key ) . "' $selected>$t_value</option>";
    }
    $val .= ' </select>';
    return $val;
}

function eme_ui_radio( $option_value, $name, $list, $horizontal = true, $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $val     = '';
    $counter = 0;
    $name    = wp_strip_all_tags( $name );
    foreach ( $list as $key => $value ) {
        if ( is_array( $value ) ) {
            $t_key   = $value[0];
            $t_value = $value[1];
        } else {
            $t_key   = $key;
            $t_value = $value;
        }
        "$t_key" == $option_value ? $selected = "checked='checked' " : $selected = '';
        $val                                 .= "<input $required_att type='radio' id='{$name}_{$counter}' name='$name' $class_att value='" . eme_esc_html( $t_key ) . "' $selected $extra_attributes>&nbsp;<label for='{$name}_{$counter}'>" . eme_esc_html( $t_value ) . '</label>';
        if (  $horizontal ) {
            $val .= "&nbsp;";
        } else {
            $val .= "<br>\n";
        }
        ++$counter;
    }
    return $val;
}

function eme_ui_checkbox_binary( $option_value, $name, $label = '', $required = 0, $class = '', $extra_attributes = '' ) {
    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $option_value ? $selected = "checked='checked' " : $selected = '';

    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $name = wp_strip_all_tags( $name );
    $val  = "<input $required_att type='checkbox' name='{$name}' id='{$name}' $class_att value='1' $selected $extra_attributes>";
    if ( ! empty( $label ) ) {
        $val .= "&nbsp;<label for='{$name}'>" . eme_esc_html_keep_br( $label ) . '</label>';
    }
    return $val;
}

function eme_nobreak_checkbox_binary( $option_value, $name, $label = '', $required = 0, $class = '', $extra_attributes = '' ) {
    $val  = "<div class='eme-item'>";
    $val .= eme_ui_checkbox_binary( $option_value, $name, $label, $required, $class, $extra_attributes);
    $val .= "</div>";
    return $val;
}

function eme_ui_checkbox( $option_value, $name, $list, $horizontal = true, $required = 0, $class = '', $extra_attributes = '' ) {
    // make sure it is an array, otherwise just go back
    if ( ! is_array( $list ) ) {
        return;
    }

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    $val     = '';
    $counter = 0;
    $name    = wp_strip_all_tags( $name );
    foreach ( $list as $key => $value ) {
        if ( is_array( $option_value ) ) {
            in_array( $key, $option_value ) ? $selected = "checked='checked' " : $selected = '';
        } else {
            "$key" == $option_value ? $selected = "checked='checked' " : $selected = '';
        }
        $val .= "<input $required_att type='checkbox' name='{$name}[]' id='{$name}_{$counter}' $class_att value='" . eme_esc_html( $key ) . "' $selected $extra_attributes> <label for='{$name}_{$counter}'>" . eme_esc_html_keep_br( $value ) . '</label>';
        if ( $horizontal ) {
            $val .= "&nbsp;";
        } else {
            $val .= "<br>\n";
        }
        ++$counter;
    }
    return $val;
}

function eme_ui_number( $option_value, $name, $required = 0, $class = '', $extra_attributes = '' ) {
    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }
    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }

    if ( ! strstr( $extra_attributes, 'aria-label' ) ) {
        $extra_attributes .= ' aria-label="' . $name . '"';
    }

    $name = wp_strip_all_tags( $name );
    return "<input type='number' $required_att $class_att $extra_attributes name='{$name}' id='{$name}' value='$option_value'>";
}

function eme_get_field_id ( $field_name, $number = 1) {
    $field_name = str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name );
    $field_name = trim( $field_name, '-' );
    return 'emefield-' . $number . '-' . $field_name;
}
