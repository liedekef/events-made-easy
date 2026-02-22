<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_person() {
    $person               = [
        'lastname'          => '',
        'firstname'         => '',
        'email'             => '',
        'related_person_id' => 0,
        'status'            => EME_PEOPLE_STATUS_ACTIVE,
        'phone'             => '',
        'birthdate'         => '',
        'bd_email'          => get_option( 'eme_bd_email' ),
        'birthplace'        => '',
        'address1'          => '',
        'address2'          => '',
        'city'              => '',
        'zip'               => '',
        'state'             => '',
        'country'           => '',
        'state_code'        => '',
        'country_code'      => '',
        'lang'              => eme_detect_lang(),
        'wp_id'             => null,
        'massmail'          => get_option( 'eme_people_massmail' ),
        'newsletter'        => get_option( 'eme_people_newsletter' ),
        'gdpr'              => 0,
        'properties'        => [],
    ];
    $person['properties'] = eme_init_person_props( $person['properties'] );
    return $person;
}

function eme_new_group() {
    $group = [
        'name'         => '',
        'type'         => 'static',
        'public'       => 0,
        'description'  => '',
        'email'        => '',
        'search_terms' => [],
    ];
    return $group;
}

function eme_init_person_props( $props ) {
    if ( ! isset( $props['wp_delete_user'] ) ) {
        $props['wp_delete_user'] = 0;
    }
    if ( ! isset( $props['image_id'] ) ) {
        $props['image_id'] = 0;
    }
    return $props;
}

function eme_people_page() {
    $message = '';

    $current_userid = get_current_user_id();

    if ( isset( $_POST['eme_admin_action'] ))
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
    if ( isset( $_POST['eme_admin_action'] ) && eme_sanitize_request($_POST['eme_admin_action']) == 'import_people' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
        // eme_cap_cleanup is used for cleanup, cron and imports (should more be something like 'eme_cap_actions')
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            $message = eme_message_ok_div(eme_import_csv_people());
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to update people!', 'events-made-easy' ));
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && eme_sanitize_request($_POST['eme_admin_action']) == 'do_addperson' ) {
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            [$add_update_message, $person_id] = eme_add_update_person_from_backend();
            if ( $person_id ) {
                $message = esc_html__( 'Person added', 'events-made-easy' );
                if ( ! empty( $add_update_message ) ) {
                    $message .= '<br>' . $add_update_message;
                }
                $message = eme_message_ok_div($message);
                if ( get_option( 'eme_stay_on_edit_page' ) ) {
                    eme_person_edit_layout( $person_id, $message );
                    return;
                }
            } else {
                $message = esc_html__( 'Problem detected while adding person', 'events-made-easy' );
                if ( ! empty( $add_update_message ) ) {
                    $message .= '<br>' . $add_update_message;
                }
                $message = eme_message_error_div($message);
            }
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to update people!', 'events-made-easy' ));
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && eme_sanitize_request($_POST['eme_admin_action']) == 'do_editperson' ) {
        $person_id = intval( $_POST['person_id'] );
        $wp_id     = eme_get_wpid_by_personid( $person_id );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) || ( current_user_can( get_option( 'eme_cap_author_person' ) ) && $wp_id == $current_userid ) ) {
            [$add_update_message, $person_id] = eme_add_update_person_from_backend( $person_id );
            if ( $person_id ) {
                $message = esc_html__( 'Person updated', 'events-made-easy' );
                $message .= '<br>' . $add_update_message;
                $message = eme_message_ok_div($message);
            } else {
                $message = esc_html__( 'Problem detected while updating person', 'events-made-easy' );
                $message .= '<br>' . $add_update_message;
                $message = eme_message_error_div($message);
            }
            if ( $person_id && get_option( 'eme_stay_on_edit_page' ) ) {
                eme_person_edit_layout( $person_id, $message );
                return;
            }
        } else {
            $message = esc_html__( 'You have no right to update this person!', 'events-made-easy' );
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && eme_sanitize_request($_POST['eme_admin_action']) == 'add_person' ) {
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_person_edit_layout();
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to add people!', 'events-made-easy' ));
        }
    } elseif ( isset( $_GET['eme_admin_action'] ) && eme_sanitize_request($_GET['eme_admin_action']) == 'edit_person' ) {
        $person_id = intval( $_GET['person_id'] );
        $wp_id     = eme_get_wpid_by_personid( $person_id );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) || ( current_user_can( get_option( 'eme_cap_author_person' ) ) && $wp_id == $current_userid ) ) {
            eme_person_edit_layout( $person_id );
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to update this person!', 'events-made-easy' ));
        }
    } elseif ( isset( $_GET['eme_admin_action'] ) && eme_sanitize_request($_GET['eme_admin_action']) == 'verify_people' ) {
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_person_verify_layout();
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to update people!', 'events-made-easy' ));
        }
    }
    eme_manage_people_layout( $message );
}

function eme_groups_page() {
    $message = '';
    if ( ! current_user_can( get_option( 'eme_cap_edit_people' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
        $message = esc_html__( 'You have no right to manage groups!', 'events-made-easy' );
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_addgroup' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $group_id = eme_add_update_group();
        if ( $group_id ) {
            $message = eme_message_ok_div(esc_html__( 'Group added', 'events-made-easy' ));
            if ( get_option( 'eme_stay_on_edit_page' ) ) {
                eme_group_edit_layout( $group_id, $message );
                return;
            }
        } else {
            $message = eme_message_error_div(esc_html__( 'Problem detected while adding group', 'events-made-easy' ));
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'do_editgroup' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $group_id = intval( $_POST['group_id'] );
        $res      = eme_add_update_group( $group_id );
        if ( $res ) {
            $message = eme_message_ok_div(esc_html__( 'Group updated', 'events-made-easy' ));
        } else {
            $message = eme_message_error_div(esc_html__( 'Problem detected while updating group', 'events-made-easy' ));
        }
        if ( get_option( 'eme_stay_on_edit_page' ) ) {
            eme_group_edit_layout( $group_id, $message );
            return;
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_dynamic_people_group' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_group_edit_layout(group_type: 'dynamic_people');
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to add groups!', 'events-made-easy' ));
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_dynamic_members_group' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_group_edit_layout(group_type: 'dynamic_members');
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to add groups!', 'events-made-easy' ));
        }
    } elseif ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_group' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_group_edit_layout();
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to add groups!', 'events-made-easy' ));
        }
    } elseif ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_group' ) {
        $group_id = intval( $_GET['group_id'] );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            eme_group_edit_layout( $group_id );
            return;
        } else {
            $message = eme_message_error_div(esc_html__( 'You have no right to update groups!', 'events-made-easy' ));
        }
    }
    eme_manage_groups_layout( $message );
}

function eme_person_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts(
        [
            'person_id'   => 0,
            'template_id' => 0,
        ],
        $atts
    );
    $person = [];
    // the GET param prid (person randomid) overrides person_id if present
    if ( isset( $_GET['prid'] ) && isset( $_GET['eme_frontend_nonce'] ) && wp_verify_nonce( eme_sanitize_request($_GET['eme_frontend_nonce']), 'eme_frontend' ) ) {
        $random_id = eme_sanitize_request( $_GET['prid'] );
        $person    = eme_get_person_by_randomid( $random_id );
    } elseif ( !empty($atts['person_id']) ) {
        $person = eme_get_person( intval( $atts['person_id'] ) );
    } elseif ( is_user_logged_in() ) {
        $wp_id  = get_current_user_id();
        $person = eme_get_person_by_wp_id( $wp_id );
        if (empty($person)) {
            $person = eme_fake_person_by_wp_id( $wp_id );
        }
    }
    if ( !empty($atts['template_id']) && ! empty( $person ) ) {
        $format = eme_get_template_format( intval($atts['template_id']) );
        $output = eme_replace_people_placeholders( $format, $person );
        return $output;
    } else {
        return '';
    }
}

function eme_people_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts(
        [
            'group_id'           => 0,
            'order'              => 'ASC',
            'template_id'        => 0,
            'template_id_header' => 0,
            'template_id_footer' => 0,
        ],
        $atts
    );

    if ( ! empty( $atts['group_id'] ) ) {
        $persons = eme_get_grouppersons( $atts['group_id'], $atts['order'] );
    } else {
        $persons = eme_get_persons( '', '', '', $atts['order']);
    }

    $format            = '';
    $eme_format_header = '';
    $eme_format_footer = '';
    if ( $atts['template_id'] ) {
        $format = eme_get_template_format( intval($atts['template_id']) );
    }
    if ( $atts['template_id_header'] ) {
        $eme_format_header = eme_replace_generic_placeholders( eme_get_template_format( intval($atts['template_id_header']) ) );
    }
    if ( $atts['template_id_footer'] ) {
        $eme_format_footer = eme_replace_generic_placeholders( eme_get_template_format( intval($atts['template_id_footer']) ) );
    }
    $output = '';
    if ( ! empty( $persons ) && is_array( $persons ) ) {
        foreach ( $persons as $person ) {
            $output .= eme_replace_people_placeholders( $format, $person );
        }
    }
    $output = $eme_format_header . $output . $eme_format_footer;
    return $output;
}

function eme_replace_email_event_placeholders( $format, $email, $lastname, $firstname, $event, $lang = '' ) {
    // EME language tags are already replaced
    //$format = eme_translate_string( $format );

    $needle_offset = 0;
    preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $replacement        = '';
        $found              = 1;
        if ( preg_match( '/#_INVITEURL$/', $result ) ) {
            $replacement = eme_invite_url( $event, $email, $lastname, $firstname, $lang );
        } else {
            $found = 0;
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
    return $format;
}

function eme_replace_people_placeholders( $format, $person, $target = 'html', $lang = '', $do_shortcode = 1 ) {
    $orig_target  = $target;
    if ( $target == 'htmlmail' || $target == 'html_nohtml2br' ) {
        $target = 'html';
    }

    if ( ! $person ) {
        return;
    }

    if (!empty($person['person_id'])) {
        $answers = eme_get_person_answers( $person['person_id'] );
        $files   = eme_get_uploaded_files( $person['person_id'], 'people' );
    } else {
        $answers = [];
        $files = [];
    }
    if ( empty( $lang ) ) {
        $lang = $person['lang'];
    }

    // now the generic placeholders
    $format = eme_replace_generic_placeholders( $format, $target );

    $needle_offset = 0;
    preg_match_all( '/#(ESC|URL)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $replacement                = '';
        $found                      = 1;
        $need_escape                = 0;
        $need_urlencode             = 0;

        if ( strstr( $result, '#ESC' ) ) {
            $result      = str_replace( '#ESC', '#', $result );
            $need_escape = 1;
        } elseif ( strstr( $result, '#URL' ) ) {
            $result         = str_replace( '#URL', '#', $result );
            $need_urlencode = 1;
        }

        # support for ATTEND, RESP and PERSON
        $result = preg_replace( '/#_ATTEND(_)?|#_RESP(_)?|#_PERSON(_)?/', '#_', $result );

        if ( preg_match( '/#_ID/', $result ) ) {
            if (!empty($person['person_id']))
                $replacement = intval( $person['person_id'] );
        } elseif ( preg_match( '/#_WPID/', $result ) ) {
            $replacement = intval( $person['wp_id'] );
        } elseif ( preg_match( '/#_FULLNAME/', $result ) ) {
            $replacement = eme_format_full_name( $person['firstname'], $person['lastname'] );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_(NAME|LASTNAME|FIRSTNAME|ZIP|POSTAL|CITY|ADDRESS1|ADDRESS2|PHONE|BIRTHPLACE)$/', $result ) ) {
            $field = str_replace( '#_', '', $result );
            $field = strtolower( $field );
            if ( $field == 'name' ) {
                $field = 'lastname';
            }
            if ( $field == 'postal' ) {
                $field = 'zip';
            }
            $replacement = $person[ $field ];
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_BIRTHDATE$/', $result ) ) {
            $replacement = eme_localized_date( $person['birthdate'], EME_TIMEZONE, 1 );
            if ( $target == 'html' ) {
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_EMAIL$/', $result ) ) {
            $replacement = $person['email'];
            if ( $target == 'html' ) {
                $replacement = eme_email_obfuscate( $replacement, $orig_target );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_FIRSTNAME\{(.+)\}/', $result, $matches ) ) {
            $length      = intval( $matches[1] );
            $replacement = substr( $person['firstname'], 0, $length );
            // add trailing '.'
            $replacement .= ( substr( $replacement, -1 ) == '.' ? '' : '.' );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_LASTNAME\{(.+)\}/', $result, $matches ) ) {
            $length      = intval( $matches[1] );
            $replacement = substr( $person['lastname'], 0, $length );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_INITIALS/', $result ) ) {
            $fullname    = eme_format_full_name( $person['firstname'], $person['lastname'] );
            $replacement = eme_get_initials( $fullname );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_LASTNAME_INITIALS/', $result ) ) {
            $replacement = eme_get_initials( $person['lastname'] );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_COUNTRY/', $result ) ) {
            $replacement = eme_get_country_name( $person['country_code'], $lang );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_STATE/', $result ) ) {
            $replacement = eme_get_state_name( $person['state_code'], $person['country_code'], $lang );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_GROUPS/', $result ) ) {
            if (!empty($person['person_id']))
                $replacement = join( ', ', eme_get_persongroup_names( $person['person_id'] ) );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_MEMBERSHIPS/', $result ) ) {
            if (!empty($person['person_id']))
                $replacement = eme_get_activemembership_names_by_personid( $person['person_id'] );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/^#_IS_PERSON_MEMBER_OF\{(.+?)\}$/', $result, $matches ) ) {
            $memberships = $matches[1];
            $replacement = 0;
            $active_membershipids = eme_get_active_membershipids_by_personid( $person['person_id'] );
            $memberships_arr = explode( ',', $memberships );
            foreach ( $memberships_arr as $membership_t ) {
                if (!is_numeric($membership_t)) {
                    $membership = eme_get_membership( $membership_t );
                    if ($membership) {
                        $membership_id = $membership['membership_id'];
                    } else {
                        $membership_id = 0;
                    }
                } else {
                    $membership_id = $membership_t;
                }
                if ( !empty($membership_id) && in_array($membership_id, $active_membershipids) ) {
                    $replacement = 1;
                    break;
                }
            }
        } elseif ( preg_match( '/^#_IS_PERSON_IN_GROUP\{(.+?)\}$/', $result, $matches ) ) {
            $groups = $matches[1];
            $replacement = 0;
            $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
            $groupids_arr = explode( ',', $groups );
            $person_groupids = eme_get_persongroup_ids( $person['person_id'] );
            if ( ! empty($person_groupids ) ) {
                $res_intersect = array_intersect( $person_groupids, $groupids_arr );
            } else {
                $res_intersect = 0;
            }
            if ( !empty( $res_intersect ) ) {
                $replacement = 1;
            }
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL/', $result ) ) {
            $replacement = $person['bd_email'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_MASSMAIL|#_OPT_IN|#_OPT_OUT/', $result ) ) {
            $replacement = $person['massmail'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_GDPR|#_CONSENT/', $result ) ) {
            $replacement = $person['gdpr'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_IMAGETITLE$/', $result ) ) {
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $info = eme_get_wp_image( $person['properties']['image_id'] );
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
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $info = eme_get_wp_image( $person['properties']['image_id'] );
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
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $info = eme_get_wp_image( $person['properties']['image_id'] );
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
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $info = eme_get_wp_image( $person['properties']['image_id'] );
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
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $replacement = wp_get_attachment_image( $person['properties']['image_id'], 'full', 0, [ 'class' => 'eme_person_image' ] );
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
        } elseif ( preg_match( '/#_IMAGEURL$/', $result ) ) {
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $replacement = wp_get_attachment_image_url( $person['properties']['image_id'], 'full' );
                if (empty($replacement)) {
                    $replacement = "";
                }
                if ( $target == 'html' ) {
                    $replacement = esc_url( $replacement );
                }
            }
        } elseif ( preg_match( '/#_IMAGETHUMB(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $thumb_size = substr( $matches[1], 1, -1 );
            } else {
                $thumb_size = get_option( 'eme_thumbnail_size' );
            }
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $replacement = wp_get_attachment_image( $person['properties']['image_id'], $thumb_size, 0, [ 'class' => 'eme_person_image' ] );
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
        } elseif ( preg_match( '/#_IMAGETHUMBURL(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $thumb_size = substr( $matches[1], 1, -1 );
            } else {
                $thumb_size = get_option( 'eme_thumbnail_size' );
            }
            if ( ! empty( $person['properties']['image_id'] ) ) {
                $replacement = wp_get_attachment_image_url( $person['properties']['image_id'], $thumb_size );
                if (empty($replacement)) {
                    $replacement = "";
                }
                if ( $target == 'html' ) {
                    $replacement = esc_url( $replacement );
                }
            }
        } elseif ( preg_match( '/#_INVITEURL\{(.+)\}/', $result, $matches ) ) {
            $event = eme_get_event( $matches[1] );
            if ( ! empty( $event ) ) {
                $replacement = eme_invite_url( $event, $person['email'], $person['lastname'], $person['firstname'], $lang );
                if ( $target == 'html' ) {
                    $replacement = esc_url( $replacement );
                }
            }
        } elseif ( preg_match( '/#_DBFIELD\{(.+)\}/', $result, $matches ) ) {
            $tmp_attkey = $matches[1];
            if ( isset( $person[ $tmp_attkey ] ) && ! is_array( $person[ $tmp_attkey ] ) ) {
                $replacement = $person[ $tmp_attkey ];
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
        } elseif ( preg_match( '/#_PERSONAL_FILES/', $result ) ) {
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
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
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
            if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'people' ) {
                $field_id      = $formfield['field_id'];
                $field_replace = '';
                foreach ( $answers as $answer ) {
                    if ( $answer['field_id'] == $field_id ) {
                        if ( $matches[1] == 'VALUE' ) {
                            $field_replace = eme_answer2readable( $answer['answer'], $formfield, 1, $sep, $target );
                        } else {
                            $field_replace = eme_answer2readable( $answer['answer'], $formfield, 0, $sep, $target );
                        }
                        if ( $target == 'html' ) {
                            $field_replace = apply_filters( 'eme_general', $field_replace );
                        } else {
                            $field_replace = apply_filters( 'eme_text', $field_replace );
                        }
                        break;
                    }
                }
                foreach ( $files as $file ) {
                    if ( $file['field_id'] == $field_id ) {
                        if ( $matches[1] == 'VALUE' && $formfield['field_type'] == 'file' ) {
                            // for file, we can show the url. For multifile this would not make any sense
                            if ( $target == 'html' ) {
                                $field_replace .= esc_url($file['url']) ;
                            } else {
                                $field_replace .= $file['url'] ;
                            }
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
            } else {
                // no people custom field? Then leave it alone
                $found = 0;
            }
        } elseif ( preg_match( '/#_NICKNAME$/', $result ) ) {
            if ( $person['wp_id'] > 0 ) {
                $user = get_userdata( $person['wp_id'] );
                if ( $user ) {
                    $replacement = $user->user_nicename;
                }
                if ( $target == 'html' ) {
                    $replacement = eme_esc_html( $replacement );
                    $replacement = apply_filters( 'eme_general', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            }
        } elseif ( preg_match( '/#_DISPNAME$/', $result ) ) {
            if ( $person['wp_id'] > 0 ) {
                $user = get_userdata( $person['wp_id'] );
                if ( $user ) {
                    $replacement = $user->display_name;
                }
                if ( $target == 'html' ) {
                    $replacement = eme_esc_html( $replacement );
                    $replacement = apply_filters( 'eme_general', $replacement );
                } else {
                    $replacement = apply_filters( 'eme_text', $replacement );
                }
            }
        } elseif ( preg_match( '/#_RANDOMID$/', $result ) ) {
            // if random id is empty, create one
            if ( empty( $person['random_id'] ) && !empty($person['person_id']) ) {
                $person['random_id'] = eme_random_id();
                $person_id           = eme_db_update_person( $person['person_id'], $person );
            }
            $my_nonce = wp_create_nonce( 'eme_frontend' );
            $replacement = $person['random_id']."&eme_frontend_nonce=$my_nonce";
            if ( $target == 'html' ) {
                $replacement = eme_esc_html( $replacement );
                $replacement = apply_filters( 'eme_general', $replacement );
            } else {
                $replacement = apply_filters( 'eme_text', $replacement );
            }
        } elseif ( preg_match( '/#_FAMILYCOUNT/', $result ) ) {
            if (!empty($person['person_id'])) {
                $familymember_person_ids = eme_get_family_person_ids( $person['person_id'] );
                if ( ! empty( $familymember_person_ids ) ) {
                    $replacement = count( $familymember_person_ids );
                } else {
                    $replacement = 0;
                }
            }
        } elseif ( preg_match( '/#_FAMILYMEMBERS/', $result ) ) {
            if (!empty($person['person_id'])) {
                $familymember_person_ids = eme_get_family_person_ids( $person['person_id'] );
                if ( ! empty( $familymember_person_ids ) ) {
                    $replacement = "<table style='border-collapse: collapse;border: 1px solid black;' class='eme_dyndata_table'>";
                    foreach ( $familymember_person_ids as $familymember_person_id ) {
                        $related_person = eme_get_person( $familymember_person_id );
                        if ( $related_person ) {
                            $replacement .= "<tr class='eme_dyndata_row'><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_left'>" . eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'], $related_person['email'] ) ) . "</td><td style='border: 1px solid black;padding: 5px;' class='eme_dyndata_column_right'>" . eme_esc_html( $related_person['email'] ) . '</td></tr>';
                        }
                    }
                    $replacement .= '</table>';
                }
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
            $format = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    $format = eme_translate( $format, $lang );

    // now some html
    if ( $target == 'html' ) {
        $format = eme_nl2br_save_html( $format );
    }

    if ( $do_shortcode ) {
        return do_shortcode( $format );
    } else {
        return $format;
    }
}

function eme_import_csv_people() {
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
    $updated_msg = '';
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

    // check required columns: at least email or lastname is needed
    if ( ! in_array( 'email', $headers ) && ! in_array( 'lastname', $headers ) ) {
        $result = esc_html__( 'Not all required fields present.', 'events-made-easy' );
    } else {
        $empty_props = [];
        $empty_props = eme_init_person_props( $empty_props );
        // now loop over the rest
        while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
            $line = array_combine( $headers, $row );
            // remove columns with empty values
            $line = eme_array_remove_empty_elements( $line );
            // we need at least 3 fields present, otherwise nothing will be done
            if ( ! isset( $line['email'] ) ) {
                $line['email']    = '';
                $line['massmail'] = 0;
            }
            // if email empty: at least lastname is needed
            if ( empty($line['email'] ) && !isset( $line['lastname'] ) ) {
                ++$errors;
                $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (both email and lastname are empty): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                continue;
            }
            // also allow empty firstname
            if ( !isset( $line['lastname'] ) ) {
                $line['lastname'] = '';
            }
            if ( !isset( $line['firstname'] ) ) {
                $line['firstname'] = '';
            }
            if ( ! empty( $line['email'] ) && ! eme_is_email( $line['email'] ) ) {
                ++$errors;
                $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (field %s not valid): %s', 'events-made-easy' ), 'email', implode( ',', $row ) ) );
                continue;
            }

            // also import properties
            foreach ( $line as $key => $value ) {
                if ( preg_match( '/^prop_(.*)$/', $key, $matches ) ) {
                    $prop = $matches[1];
                    if ( ! isset( $line['properties'] ) ) {
                        $line['properties'] = [];
                    }
                    if ( array_key_exists( $prop, $empty_props ) ) {
                        $line['properties'][ $prop ] = $value;
                    }
                }
            }
            // if the person already exists: update him
            $person = eme_get_person_by_name_and_email( $line['lastname'], $line['firstname'], $line['email'] );
            if ( ! $person ) {
                $person = eme_get_person_by_email_only( $line['email'] );
            }
            $person_id = 0;
            if ( $person ) {
                $person_id = eme_db_update_person( $person['person_id'], $line );
                if ( $person_id ) {
                    ++$updated;
                    $updated_msg .= '<br>' . eme_esc_html( sprintf( __( 'Updated person %d: %s', 'events-made-easy' ), $person_id, implode( ',', $row ) ) );
                } else {
                    ++$errors;
                    $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem updating the person in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                }
            } else {
                $person_id = eme_db_insert_person( $line );
                if ( $person_id ) {
                    ++$inserted;
                } else {
                    ++$errors;
                    $error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported (problem inserting the person in the db): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                }
            }
            if ( $person_id ) {
                // now handle all the extra info, in the CSV they need to be named like 'answer_XX' (with 'XX' being either the fieldid or the fieldname, e.g. answer_myfieldname)
                // if the key is called "groups", then the person will get imported into the the mentioned groups
                foreach ( $line as $key => $value ) {
                    if ( preg_match( '/^answer_(.*)$/', $key, $matches ) ) {
                        $grouping   = 0;
                        $field_name = $matches[1];
                        $formfield  = eme_get_formfield( $field_name );
                        if ( ! empty( $formfield ) ) {
                            $field_id = $formfield['field_id'];
                            $sql      = $wpdb->prepare( "DELETE FROM $answers_table WHERE related_id = %d and field_id=%d AND type='person'", $person_id, $field_id );
                            $wpdb->query( $sql );

                            $sql = $wpdb->prepare( "INSERT INTO $answers_table (related_id,field_id,answer,eme_grouping,type) VALUES (%d,%d,%s,%d,%s)", $person_id, $field_id, $value, $grouping, 'person' );
                            $wpdb->query( $sql );
                        }
                    }
                    if ( preg_match( '/^groups?$/', $key, $matches ) ) {
                        $groups = eme_convert_multi2array( $value );
                        eme_add_persongroups( $person_id, $groups );
                    }
                }
            }
        }
        $result = sprintf( esc_html__( 'Import finished: %d inserts, %d updates, %d errors', 'events-made-easy' ), $inserted, $updated, $errors );
    }
    fclose( $handle );
    if ( $updated ) {
        $result .= '<br>' . $updated_msg;
    }
    if ( $errors ) {
        $result .= '<br>' . $error_msg;
    }
    return $result;
}

function eme_csv_tasksignups_report( $event_id ) {
    $event = eme_get_event( $event_id );
    if ( empty( $event ) ) {
        return;
    }
    $current_userid = get_current_user_id();
    if ( ! ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
        ( current_user_can( get_option( 'eme_cap_list_events' ) ) && ($event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid) ) ) ) {
        echo esc_html__( 'No access', 'events-made-easy' );
        die;
    }

    $delimiter = get_option( 'eme_csv_delimiter' );
    if ( eme_is_empty_string( $delimiter ) ) {
        $delimiter = ';';
    }

    //header("Content-type: application/octet-stream");
    header( 'Content-type: text/csv; charset=UTF-8' );
    header( 'Content-Encoding: UTF-8' );
    header( 'Content-Disposition: attachment; filename="export.csv"' );
    eme_nocache_headers();

    $signups     = eme_get_event_task_signups( $event_id );
    $people_answer_fieldids = eme_get_people_export_fieldids();
    $tasksignup_answer_fieldids = eme_get_tasksignups_answers_fieldids( array_keys($signups) );

    // echo "\xEF\xBB\xBF"; // UTF-8 BOM, Excell otherwise doesn't show the characters correctly ...
    $out = fopen( 'php://output', 'w' );
    fwrite($out, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // UTF-8 BOM, Excell otherwise doesn't show the characters correctly

    if ( has_filter( 'eme_csv_header_filter' ) ) {
        $line = apply_filters( 'eme_csv_header_filter', $event );
        eme_fputcsv( $out, $line, $delimiter );
    }
    $line   = [];
    $line[] = __( 'ID', 'events-made-easy' );
    $line[] = __( 'Person ID', 'events-made-easy' );
    $line[] = __( 'Signup date', 'events-made-easy' );
    $line[] = __( 'Task name', 'events-made-easy' );
    $line[] = __( 'Task start date', 'events-made-easy' );
    $line[] = __( 'Task end date', 'events-made-easy' );
    $line[] = __( 'Last name', 'events-made-easy' );
    $line[] = __( 'First name', 'events-made-easy' );
    $line[] = get_option( 'eme_address1_string' );
    $line[] = get_option( 'eme_address2_string' );
    $line[] = __( 'City', 'events-made-easy' );
    $line[] = __( 'Postal code', 'events-made-easy' );
    $line[] = __( 'State', 'events-made-easy' );
    $line[] = __( 'Country', 'events-made-easy' );
    $line[] = __( 'Email', 'events-made-easy' );
    $line[] = __( 'Phone number', 'events-made-easy' );
    $line[] = __( 'Date of birth', 'events-made-easy' );
    $line[] = __( 'Place of birth', 'events-made-easy' );
    $line[] = __( 'MassMail', 'events-made-easy' );
    $line[] = __( 'Newsletter', 'events-made-easy' );
    $line[] = __( 'Birthday email', 'events-made-easy' );
    foreach ( $people_answer_fieldids as $field_id ) {
        $tmp_formfield = eme_get_formfield( $field_id );
        if ( ! empty( $tmp_formfield ) ) {
            $line[] = $tmp_formfield['field_name'];
        }
    }
    $line[] = __( 'Status', 'events-made-easy' );
    $line[] = __( 'Comment', 'events-made-easy' );
    foreach ( $tasksignup_answer_fieldids as $field_id ) {
        $tmp_formfield = eme_get_formfield( $field_id );
        if ( ! empty( $tmp_formfield ) ) {
            $line[] = $tmp_formfield['field_name'];
        }
    }
    $line_nbr = 1;
    if ( has_filter( 'eme_csv_column_filter' ) ) {
        $line = apply_filters( 'eme_csv_column_filter', $line, $event, $line_nbr );
    }

    eme_fputcsv( $out, $line, $delimiter );
    foreach ( $signups as $signup) {
        $localized_signup_datetime  = eme_localized_datetime( $signup['signup_date'], EME_TIMEZONE, 1 );
        $localized_taskstart_date   = eme_localized_datetime( $signup['task_start'], EME_TIMEZONE, 1 );
        $localized_taskend_date     = eme_localized_datetime( $signup['task_end'], EME_TIMEZONE, 1 );
        $person                     = eme_get_person( $signup['person_id'] );
        // if the person no longer exists, use an empty one
        if ( ! $person ) {
            $person = eme_new_person();
        }
        $person_answers = eme_get_person_answers( $signup['person_id'] );
        $line           = [];
        $status_string  = '';
        if ( $signup['signup_status'] == 0 ) {
            $status_string = __( 'Pending', 'events-made-easy' );
        } else {
            $status_string = __( 'Approved', 'events-made-easy' );
        }

        $line[] = $signup['id'];
        $line[] = $signup['person_id'];
        $line[] = $localized_signup_datetime;
        $line[] = $signup['name'];
        $line[] = $localized_taskstart_date;
        $line[] = $localized_taskend_date;
        $line[] = $person['lastname'];
        $line[] = $person['firstname'];
        $line[] = $person['address1'];
        $line[] = $person['address2'];
        $line[] = $person['city'];
        $line[] = $person['zip'];
        $line[] = eme_get_state_name( $person['state_code'], $person['country_code'] );
        $line[] = eme_get_country_name( $person['country_code'] );
        $line[] = $person['email'];
        $line[] = $person['phone'];
        $line[] = $person['birthdate'];
        $line[] = $person['birthplace'];
        $line[] = $person['massmail'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $line[] = $person['newsletter'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $line[] = $person['bd_email'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        foreach ( $people_answer_fieldids as $field_id ) {
            $found = 0;
            foreach ( $person_answers as $answer ) {
                if ( $answer['field_id'] == $field_id ) {
                    $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                    if ( ! empty( $tmp_formfield ) ) {
                        $line[] = eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', 'text', 1 );
                    }
                    $found = 1;
                    break;
                }
            }
            # to make sure the number of columns are correct, we add an empty answer if none was found
            if ( ! $found ) {
                $line[] = '';
            }
        }
        $line[] = $status_string;
        $line[] = $signup['comment'];
        $answers = eme_get_tasksignup_answers( $signup['id'] );
        foreach ( $tasksignup_answer_fieldids as $field_id ) {
            $found = 0;
            foreach ( $answers as $answer ) {
                if ( $answer['field_id'] == $field_id ) {
                    $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                    if ( ! empty( $tmp_formfield ) ) {
                        $line[] = eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', 'text', 1 );
                    }
                    $found = 1;
                    break;
                }
            }
            # to make sure the number of columns are correct, we add an empty answer if none was found
            if ( ! $found ) {
                $line[] = '';
            }
        }

        ++$line_nbr;
        if ( has_filter( 'eme_csv_column_filter' ) ) {
            $line = apply_filters( 'eme_csv_column_filter', $line, $event, $line_nbr );
        }
        eme_fputcsv( $out, $line, $delimiter );
    }

    if ( has_filter( 'eme_csv_footer_filter' ) ) {
        $line = apply_filters( 'eme_csv_footer_filter', $event );
        eme_fputcsv( $out, $line, $delimiter );
    }
    fclose( $out );
    die();
}

function eme_csv_booking_report( $event_id ) {
    $event = eme_get_event( $event_id );
    $pgs   = eme_payment_gateways();
    if ( empty( $event ) ) {
        return;
    }
    $is_multiprice = eme_is_multi( $event['price'] );
    if ( $is_multiprice ) {
        $price_count = count( eme_convert_multi2array( $event['price'] ) );
    } else {
        $price_count = 1;
    }
    $current_userid = get_current_user_id();
    if ( ! ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
        ( current_user_can( get_option( 'eme_cap_list_events' ) ) && ($event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid) ) ) ) {
        echo esc_html__( 'No access', 'events-made-easy' );
        die;
    }

    $delimiter = get_option( 'eme_csv_delimiter' );
    if ( eme_is_empty_string( $delimiter ) ) {
        $delimiter = ';';
    }

    //header("Content-type: application/octet-stream");
    header( 'Content-type: text/csv; charset=UTF-8' );
    header( 'Content-Encoding: UTF-8' );
    header( 'Content-Disposition: attachment; filename="export.csv"' );
    eme_nocache_headers();

    $bookings               = eme_get_bookings_for( $event_id );
    $people_answer_fieldids = eme_get_people_export_fieldids();
    $booking_answer_fieldids = eme_get_booking_answers_fieldids( eme_get_bookingids_for( $event_id ) );

    // echo "\xEF\xBB\xBF"; // UTF-8 BOM, Excell otherwise doesn't show the characters correctly ...
    $out = fopen( 'php://output', 'w' );
    fwrite($out, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // UTF-8 BOM, Excell otherwise doesn't show the characters correctly

    if ( has_filter( 'eme_csv_header_filter' ) ) {
        $line = apply_filters( 'eme_csv_header_filter', $event );
        eme_fputcsv( $out, $line, $delimiter );
    }
    $line   = [];
    $line[] = __( 'ID', 'events-made-easy' );
    $line[] = __( 'Person ID', 'events-made-easy' );
    $line[] = __( 'Last name', 'events-made-easy' );
    $line[] = __( 'First name', 'events-made-easy' );
    $line[] = get_option( 'eme_address1_string' );
    $line[] = get_option( 'eme_address2_string' );
    $line[] = __( 'City', 'events-made-easy' );
    $line[] = __( 'Postal code', 'events-made-easy' );
    $line[] = __( 'State', 'events-made-easy' );
    $line[] = __( 'Country', 'events-made-easy' );
    $line[] = __( 'Email', 'events-made-easy' );
    $line[] = __( 'Phone number', 'events-made-easy' );
    $line[] = __( 'Date of birth', 'events-made-easy' );
    $line[] = __( 'Place of birth', 'events-made-easy' );
    $line[] = __( 'MassMail', 'events-made-easy' );
    $line[] = __( 'Newsletter', 'events-made-easy' );
    $line[] = __( 'Birthday email', 'events-made-easy' );
    foreach ( $people_answer_fieldids as $field_id ) {
        $tmp_formfield = eme_get_formfield( $field_id );
        if ( ! empty( $tmp_formfield ) ) {
            $line[] = $tmp_formfield['field_name'];
        }
    }
    if ( $is_multiprice ) {
        #$line[]=__('Seats (Multiprice)', 'events-made-easy');
        $multprice_desc_arr = eme_convert_multi2array( $event['event_properties']['multiprice_desc'] );
        for ( $i = 0; $i < $price_count; $i++ ) {
            if ( ! empty( $multprice_desc_arr[ $i ] ) ) {
                $line[] = sprintf( __( 'Seats "%s"', 'events-made-easy' ), $multprice_desc_arr[ $i ] );
            } else {
                $line[] = sprintf( __( 'Seats category %d', 'events-made-easy' ), $i + 1 );
            }
        }
    } else {
        $line[] = __( 'Seats', 'events-made-easy' );
    }
    $line[] = __( 'Status', 'events-made-easy' );
    $line[] = __( 'Paid', 'events-made-easy' );
    $line[] = __( 'Received', 'events-made-easy' );
    $line[] = __( 'Remaining', 'events-made-easy' );
    $line[] = __( 'Booking date', 'events-made-easy' );
    $line[] = __( 'Total price', 'events-made-easy' );
    $line[] = __( 'Discount', 'events-made-easy' );
    $line[] = __( 'Discount info', 'events-made-easy' );
    $line[] = __( 'Payment Gateway', 'events-made-easy' );
    $line[] = __( 'Unique nbr', 'events-made-easy' );
    $line[] = __( 'Attendance count', 'events-made-easy' );
    $line[] = __( 'Comment', 'events-made-easy' );
    foreach ( $booking_answer_fieldids as $field_id ) {
        $tmp_formfield = eme_get_formfield( $field_id );
        if ( ! empty( $tmp_formfield ) ) {
            $line[] = $tmp_formfield['field_name'];
        }
    }
    $line_nbr = 1;
    if ( has_filter( 'eme_csv_column_filter' ) ) {
        $line = apply_filters( 'eme_csv_column_filter', $line, $event, $line_nbr );
    }

    eme_fputcsv( $out, $line, $delimiter );
    foreach ( $bookings as $booking ) {
        $localized_booking_datetime = eme_localized_datetime( $booking['creation_date'], EME_TIMEZONE, 1 );
        $person                     = eme_get_person( $booking['person_id'] );
        // if the person no longer exists, use an empty one
        if ( ! $person ) {
            $person = eme_new_person();
        }
        $person_answers = eme_get_person_answers( $booking['person_id'] );
        $line           = [];
        $status_string  = '';
        if ( $booking['waitinglist'] ) {
            $status_string = __( 'On waiting list', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_PENDING ) {
            $status_string = __( 'Pending', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
            $status_string = __( 'Awaiting user confirmation', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_APPROVED ) {
            $status_string = __( 'Approved', 'events-made-easy' );
        }

        $line[] = $booking['booking_id'];
        $line[] = $booking['person_id'];
        $line[] = $person['lastname'];
        $line[] = $person['firstname'];
        $line[] = $person['address1'];
        $line[] = $person['address2'];
        $line[] = $person['city'];
        $line[] = $person['zip'];
        $line[] = eme_get_state_name( $person['state_code'], $person['country_code'] );
        $line[] = eme_get_country_name( $person['country_code'] );
        $line[] = $person['email'];
        $line[] = $person['phone'];
        $line[] = $person['birthdate'];
        $line[] = $person['birthplace'];
        $line[] = $person['massmail'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $line[] = $person['newsletter'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $line[] = $person['bd_email'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        foreach ( $people_answer_fieldids as $field_id ) {
            $found = 0;
            foreach ( $person_answers as $answer ) {
                if ( $answer['field_id'] == $field_id ) {
                    $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                    if ( ! empty( $tmp_formfield ) ) {
                        $line[] = eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', 'text', 1 );
                    }
                    $found = 1;
                    break;
                }
            }
            # to make sure the number of columns are correct, we add an empty answer if none was found
            if ( ! $found ) {
                $line[] = '';
            }
        }
        if ( $is_multiprice ) {
            // in cases where the event switched to multiprice, but somebody already registered while it was still single price: booking_seats_mp is then empty
            if ( $booking['booking_seats_mp'] == '' ) {
                $booking['booking_seats_mp'] = $booking['booking_seats'];
            }
            $booking_seats_mp_arr = eme_convert_multi2array( $booking['booking_seats_mp'] );
            for ( $i = 0; $i < $price_count; $i++ ) {
                if ( isset( $booking_seats_mp_arr[ $i ] ) ) {
                    $line[] = $booking_seats_mp_arr[ $i ];
                } else {
                    $line[] = 0;
                }
            }
        } else {
            $line[] = $booking['booking_seats'];
        }
        $line[] = $status_string;
        $line[] = $booking['booking_paid'] ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
        $line[] = eme_convert_multi2br( eme_localized_price( $booking['received'], $event['currency'] ) );
        if ( empty( $booking['remaining'] ) && empty( $booking['received'] ) ) {
            $line[] = eme_localized_price( eme_get_total_booking_price( $booking ), $event['currency'] );
        } else {
            $line[] = eme_localized_price( $booking['remaining'], $event['currency'] );
        }

        $line[] = $localized_booking_datetime;
        $line[] = eme_localized_price( eme_get_total_booking_price( $booking ), $event['currency'], 'text' );
        $line[] = eme_localized_price( $booking['discount'], $event['currency'], 'text' );
        if ( $booking['discount'] ) {
            $discount_names = [];
            if ( $booking['dgroupid'] ) {
                $dgroup = eme_get_discountgroup( $booking['dgroupid'] );
                if ( $dgroup && isset( $dgroup['name'] ) ) {
                    $discount_names[] = sprintf( __( 'Discountgroup %s', 'events-made-easy' ), $dgroup['name'] );
                } else {
                    $discount_name[] = sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $booking['dgroupid'] );
                }
            }
            if ( ! empty( $booking['discountids'] ) ) {
                if ( eme_is_serialized( $booking['discountids'] ) ) {
                    $applied_discounts = eme_unserialize( $booking['discountids'] );
                    $applied_discountids = array_keys($applied_discounts);
                } else {
                    $applied_discountids = explode( ',', $booking['discountids'] );
                }
                foreach ( $applied_discountids as $discount_id ) {
                    $discount = eme_get_discount( $discount_id );
                    if ( $discount && isset( $discount['name'] ) ) {
                        $discount_names[] = $discount['name'];
                    } else {
                        $discount_names[] = sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id );
                    }
                }
            }
            if ( ! empty( $discount_names ) ) {
                $discount_name = ' (' . join( ',', $discount_names ) . ')';
            } else {
                $discount_name = '';
            }
            $line[]  = $discount_name;
        } else {
            $line[]  = '';
        }
        if ( ! empty( $booking['pg'] ) ) {
            if ( isset( $pgs[ $booking['pg'] ] ) ) {
                $line[] = eme_esc_html( $pgs[ $booking['pg'] ] );
            } else {
                $line[]  = '';
            }
        } else {
            $line[]  = '';
        }
        $line[]  = eme_unique_nbr_formatted( $booking['unique_nbr'] );
        $line[]  = intval( $booking['attend_count'] );
        $line[]  = $booking['booking_comment'];
        $answers = eme_get_nodyndata_booking_answers( $booking['booking_id'] );
        foreach ( $booking_answer_fieldids as $field_id ) {
            $found = 0;
            foreach ( $answers as $answer ) {
                if ( $answer['field_id'] == $field_id ) {
                    $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                    if ( ! empty( $tmp_formfield ) ) {
                        $line[] = eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', 'text', 1 );
                    }
                    $found = 1;
                    break;
                }
            }
            # to make sure the number of columns are correct, we add an empty answer if none was found
            if ( ! $found ) {
                $line[] = '';
            }
        }

        # add dynamic fields to the right
        if ( isset( $event['event_properties']['rsvp_dyndata'] ) ) {
            $answers = eme_get_dyndata_booking_answers( $booking['booking_id'] );
            foreach ( $answers as $answer ) {
                $grouping      = $answer['eme_grouping'];
                $occurence     = $answer['occurence'];
                $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                if ( ! empty( $tmp_formfield ) ) {
                    $line[] = "$grouping.$occurence " . $tmp_formfield['field_name'] . ': ' . eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '||', 'text', 1 );
                }
            }
        }

        ++$line_nbr;
        if ( has_filter( 'eme_csv_column_filter' ) ) {
            $line = apply_filters( 'eme_csv_column_filter', $line, $event, $line_nbr );
        }
        eme_fputcsv( $out, $line, $delimiter );
    }

    if ( has_filter( 'eme_csv_footer_filter' ) ) {
        $line = apply_filters( 'eme_csv_footer_filter', $event );
        eme_fputcsv( $out, $line, $delimiter );
    }
    fclose( $out );
    die();
}

function eme_printable_booking_report( $event_id ) {
    $event = eme_get_event( $event_id );
    if ( empty( $event ) ) {
        return;
    }
    $current_userid = get_current_user_id();
    if ( ! ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
        ( current_user_can( get_option( 'eme_cap_list_events' ) ) && ($event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid) ) ) ) {
        echo esc_html__( 'No access', 'events-made-easy' );
        die;
    }

    $is_multiprice   = eme_is_multi( $event['price'] );
    $is_multiseat    = eme_is_multi( $event['event_seats'] );
    $bookings        = eme_get_bookings_for( $event_id );
    $booking_answer_fieldids = eme_get_booking_answers_fieldids( eme_get_bookingids_for( $event_id ) );
    $available_seats = eme_get_available_seats( $event_id );
    $total_seats     = eme_get_total( $event['event_seats'] );
    $booked_seats    = eme_get_booked_seats( $event_id );
    $pending_seats   = eme_get_pending_seats( $event_id );

    $stylesheet = esc_url(EME_PLUGIN_URL) . 'css/eme.css';

    eme_nocache_headers();
    header( 'Content-type: text/html; charset=utf-8' );
?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html>
        <head>
    <title><?php echo esc_html__( 'Bookings for', 'events-made-easy' ) . ' ' . eme_trans_esc_html( $event['event_name'] ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($stylesheet); ?>" type="text/css" media="screen">
<?php
    $file_name = get_stylesheet_directory() . '/eme.css';
    if ( file_exists( $file_name ) ) {
        echo "<link rel='stylesheet' href='" . get_stylesheet_directory_uri() . "/eme.css' type='text/css' media='screen'>\n";
    }
    $file_name = get_stylesheet_directory() . '/eme_print.css';
    if ( file_exists( $file_name ) ) {
        echo "<link rel='stylesheet' href='" . get_stylesheet_directory_uri() . "/eme_print.css' type='text/css' media='print'>\n";
    }
?>
        </head>
        <body id="eme_printable_body">
    <div id="eme_printable_container">
    <h1><?php echo esc_html__( 'Bookings for', 'events-made-easy' ) . ' ' . eme_trans_esc_html( $event['event_name'] ); ?></h1> 
    <p><?php echo esc_html(eme_localized_datetime( $event['event_start'], EME_TIMEZONE )); ?></p>
    <p>
<?php
    if ( $event['location_id'] ) {
        $location = eme_get_location( $event['location_id'] );
        echo eme_replace_locations_placeholders( '#_LOCATIONNAME, #_ADDRESS, #_TOWN', $location );
    }
?>
    </p>
<?php
    if ( $event['price'] ) {
        print "<p>";
        esc_html_e( 'Price: ', 'events-made-easy' );
        if ($is_multiprice && !eme_is_empty_string($event['event_properties']['multiprice_desc'])) {
            $price_arr = eme_convert_multi2array( $event['price'] );
            $multprice_desc_arr = eme_convert_multi2array( $event['event_properties']['multiprice_desc'] );
            foreach ($price_arr as $key=>$price) {
                $res_arr[] = eme_localized_price( $price, $event['currency']) . " " . esc_html($multprice_desc_arr[$key]);
            }
            echo eme_convert_array2multi( $res_arr, '<br>');
        } else {
            echo eme_localized_price( $event['price'], $event['currency']);
            if (!empty($event['event_properties']['price_desc'])) {
                echo " ".esc_html($event['event_properties']['price_desc']);
            }
        }
        print "</p>";
    }
?>
    <h1><?php esc_html_e( 'Bookings data', 'events-made-easy' ); ?></h1>
    <table id="eme_printable_table">
        <tr>
            <th scope='col' class='eme_print_id'>
<?php
    esc_html_e( 'ID', 'events-made-easy' );
    $nbr_columns = 1;
?>
            </th>
            <th scope='col' class='eme_print_name eme_print_lastname'>
<?php
    esc_html_e( 'Last name', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_name eme_print_firstname'>
<?php
    esc_html_e( 'First name', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_email'>
<?php
    esc_html_e( 'Email', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_phone'>
<?php
    esc_html_e( 'Phone number', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th> 
            <th scope='col' class='eme_print_seats'>
<?php
    if ( $is_multiprice ) {
        if (!eme_is_empty_string($event['event_properties']['multiprice_desc'])) {
            esc_html_e( 'Seats', 'events-made-easy' );
            print "&nbsp; (";
            print eme_convert_array2multi(eme_convert_multi2array($event['event_properties']['multiprice_desc']),', ');
            print ")";
        } else {
            esc_html_e( 'Seats (Multiprice)', 'events-made-easy' );
        }
    } else {
        esc_html_e( 'Seats', 'events-made-easy' );
    }
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_status'>
<?php
    esc_html_e( 'Status', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_paid'>
<?php
    esc_html_e( 'Paid', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_booking_date'>
<?php
    esc_html_e( 'Booking date', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_discount'>
<?php
    esc_html_e( 'Discount', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_total_price'>
<?php
    esc_html_e( 'Total price', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th>
            <th scope='col' class='eme_print_comment'>
<?php
    esc_html_e( 'Comment', 'events-made-easy' );
    ++$nbr_columns;
?>
            </th> 
<?php
    foreach ( $booking_answer_fieldids as $field_id ) {
        $class         = 'eme_print_formfield' . $field_id;
        $tmp_formfield = eme_get_formfield( $field_id );
        if ( ! empty( $tmp_formfield ) ) {
            print "<th scope='col' class='$class'>" . $tmp_formfield['field_name'] . '</th>';
            ++$nbr_columns;
        }
    }
?>
        </tr>
<?php
    foreach ( $bookings as $booking ) {
        $localized_booking_datetime = eme_localized_datetime( $booking['creation_date'], EME_TIMEZONE );
        $person                     = eme_get_person( $booking['person_id'] );
        // if the person no longer exists, use an empty one
        if ( ! $person ) {
            $person = eme_new_person();
        }
        $status_string = '';
        if ( $booking['waitinglist'] ) {
            $status_string = __( 'On waiting list', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_PENDING ) {
            $status_string = __( 'Pending', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_USERPENDING ) {
            $status_string = __( 'Awaiting user confirmation', 'events-made-easy' );
        } elseif ( $booking['status'] == EME_RSVP_STATUS_APPROVED ) {
            $status_string = __( 'Approved', 'events-made-easy' );
        }
?>
        <tr>
            <td class='eme_print_id'><?php echo esc_html($booking['booking_id']); ?></td> 
            <td class='eme_print_name'><?php echo esc_html($person['lastname']); ?></td> 
            <td class='eme_print_name'><?php echo esc_html($person['firstname']); ?></td> 
            <td class='eme_print_email'><?php echo esc_html($person['email']); ?></td>
            <td class='eme_print_phone'><?php echo esc_html($person['phone']); ?></td>
            <td class='eme_print_seats' class='seats-number'>
<?php
        if ( $is_multiprice ) {
            // in cases where the event switched to multiprice, but somebody already registered while it was still single price: booking_seats_mp is then empty
            if ( $booking['booking_seats_mp'] == '' ) {
                $booking['booking_seats_mp'] = $booking['booking_seats'];
            }
            echo esc_html($booking['booking_seats'] . ' (' . eme_convert_array2multi( eme_convert_multi2array($booking['booking_seats_mp']), ', ') . ')');
        } else {
            echo esc_html($booking['booking_seats']);
        }
?>
            </td>
            <td class='eme_print_status' class='seats-number'><?php echo esc_html($status_string); ?></td>
            <td class='eme_print_paid'>
<?php
        if ( $booking['booking_paid'] ) {
            esc_html_e( 'Yes', 'events-made-easy' );
        } else {
            esc_html_e( 'No', 'events-made-easy' );
        }
?>
                                        </td>
            <td class='eme_print_booking_date'><?php echo esc_html($localized_booking_datetime); ?></td>
            <td class='eme_print_discount'>
<?php
        $discount_name = '';
        if ( $booking['dgroupid'] ) {
            $dgroup = eme_get_discountgroup( $booking['dgroupid'] );
            if ( $dgroup && isset( $dgroup['name'] ) ) {
                $discount_name = '<br>' . esc_html(sprintf( __( 'Discountgroup %s', 'events-made-easy' ), $dgroup['name'] ) );
            } else {
                $discount_name = '<br>' . esc_html(sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $booking['dgroupid'] ));
            }
        }
        if ( ! empty( $booking['discountids'] ) ) {
            if ( eme_is_serialized( $booking['discountids'] ) ) {
                $applied_discounts = eme_unserialize( $booking['discountids'] );
                $applied_discountids = array_keys($applied_discounts);
            } else {
                $applied_discountids = explode( ',', $booking['discountids'] );
            }
            foreach ( $applied_discountids as $discount_id ) {
                $discount = eme_get_discount( $discount_id );
                if ( $discount && isset( $discount['name'] ) ) {
                    $discount_name .= '<br>' . esc_html( $discount['name'] );
                } else {
                    $discount_name .= '<br>' . esc_html(sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id ));
                }
            }
        }
        echo eme_localized_price( $booking['discount'], $event['currency'] ) .$discount_name;
?>
            </td>
            <td class='eme_print_total_price'><?php echo eme_localized_price( eme_get_total_booking_price( $booking ), $event['currency'] ); ?></td>
            <td class='eme_print_comment'><?php echo eme_esc_html( $booking['booking_comment'] ); ?></td> 
<?php
        $answers = eme_get_nodyndata_booking_answers( $booking['booking_id'] );
        foreach ( $booking_answer_fieldids as $field_id ) {
            $found = 0;
            foreach ( $answers as $answer ) {
                if ( $answer['field_id'] == $field_id ) {
                    $class         = 'eme_print_formfield' . $answer['field_id'];
                    $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                    if ( ! empty( $tmp_formfield ) ) {
                        print "<td class='$class'>" . eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '<br>', 'html' ) . '</td>';
                    }
                    $found = 1;
                    break;
                }
            }
            # to make sure the number of columns are correct, we add an empty answer if none was found
            if ( ! $found ) {
                print "<td class='$class'>&nbsp;</td>";
            }
        }
?>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan='<?php echo intval( $nbr_columns ) - 1; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integer arithmetic ?>' style='text-align: left;' >
<?php
        if ( isset( $event['event_properties']['rsvp_dyndata'] ) ) {
            $answers = eme_get_dyndata_booking_answers( $booking['booking_id'] );
            foreach ( $answers as $answer ) {
                $grouping      = $answer['eme_grouping'];
                $occurence     = $answer['occurence'];
                $class         = 'eme_print_formfield' . $answer['field_id'];
                $tmp_formfield = eme_get_formfield( $answer['field_id'] );
                if ( ! empty( $tmp_formfield ) ) {
                    print "<span class='$class'>$grouping.$occurence " . eme_esc_html( $tmp_formfield['field_name'] ) . ': ' . eme_answer2readable( $answer['answer'], $tmp_formfield, 1, '<br>', 'html' ) . '</span><br>';
                }
            }
        }
?>
            <td>
        </tr>
            <?php } ?>
        <tr id='eme_printable_booked-seats'>
            <td colspan='<?php echo intval($nbr_columns) - 4; ?>'>&nbsp;</td>
            <td class='total-label'><?php esc_html_e( 'Booked', 'events-made-easy' ); ?>:</td>
            <td colspan='3' class='seats-number'>
<?php
        print esc_html($booked_seats);
        if ( $is_multiprice ) {
            $booked_seats_mp = eme_convert_array2multi( eme_get_booked_multiseats( $event_id ), ', ' );
            print esc_html(" ($booked_seats_mp)");
        }
?>
        </td>
        </tr>
<?php
        if ( $pending_seats > 0 ) {
?>
        <tr>
            <td colspan='<?php echo intval($nbr_columns) - 4; ?>'>&nbsp;</td>
            <td class='total-label'><?php esc_html_e( 'Approved', 'events-made-easy' ); ?>:</td>
            <td colspan='3' class='seats-number'>
<?php
            $approved_seats = eme_get_approved_seats( $event_id );
            print esc_html($approved_seats);
            if ( $is_multiprice ) {
                $approved_seats_mp = eme_convert_array2multi( eme_get_approved_multiseats( $event_id ), ', ' );
                print esc_html(" ($approved_seats_mp)");
            }
?>
            </td>
        </tr>
        </tr>
        <tr>
            <td colspan='<?php echo intval($nbr_columns) - 4; ?>'>&nbsp;</td>
            <td class='total-label'><?php esc_html_e( 'Pending', 'events-made-easy' ); ?>:</td>
            <td colspan='3' class='seats-number'>
<?php
            print esc_html($pending_seats);
            if ( $is_multiprice ) {
                $pending_seats_mp = eme_convert_array2multi( eme_get_pending_multiseats( $event_id ), ', ' );
                print esc_html(" ($pending_seats_mp)");
            }
?>
            </td>
        </tr>
<?php
        }
?>
        <?php if ( $total_seats > 0 ) { ?>
        <tr id='eme_printable_available-seats'>
            <td colspan='<?php echo intval($nbr_columns) - 4; ?>'>&nbsp;</td>
            <td class='total-label'><?php esc_html_e( 'Available', 'events-made-easy' ); ?>:</td>
            <td colspan='3' class='seats-number'>
<?php
        print esc_html($available_seats);
        if ( $is_multiseat ) {
            $available_seats_ms = eme_convert_array2multi( eme_get_available_multiseats( $event_id ), ', ' );
            print esc_html(" ($available_seats_ms)");
        }
?>
            </td>
        </tr>
        <?php } ?>

<?php
        if ( $event['event_properties']['take_attendance'] ) {
            $absent_bookings = eme_get_absent_bookings( $event['event_id'] );
            if ( $absent_bookings > 0 ) {
?>
        <tr id='eme_printable_absent-bookings'>
            <td colspan='<?php echo intval($nbr_columns) - 4; ?>'>&nbsp;</td>
            <td class='total-label'><?php esc_html_e( 'Absent', 'events-made-easy' ); ?>:</td>
            <td colspan='3' class='seats-number'><?php print esc_html($absent_bookings); ?></td>
        </tr>
<?php
            }
        }
?>
    </table>
    </div>
    </body>
    </html>
<?php
        die();
}

function eme_person_verify_layout() {
?>
    <div class="wrap nosubsub">
    <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <h1><?php esc_html_e( 'Verify link between people and WP', 'events-made-easy' ); ?></h1>
<?php
    // the next function returns a row containing multiple person ids per line (csv), lastname,firstname,email,wp_id
    $res_arr = eme_find_persons_double_wp();
    if ( count( $res_arr ) > 0 ) {
        esc_html_e( 'The table below shows the people that are linked to the same WordPress user', 'events-made-easy' );
        print '<br>';
        esc_html_e( 'Please correct these errors: a WordPress user should be linked to at most one EME person.', 'events-made-easy' );
        $wp_users = eme_get_indexed_users();

        print "<table class='eme_admin_table'>";
        print '<tr>';
        print '<th>' . esc_html__( 'ID', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Last name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'First name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Email', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Linked WP user', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Active memberships', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Future bookings made?', 'events-made-easy' ) . '</th>';
        print '</tr>';
        foreach ( $res_arr as $row ) {
            $person_ids = explode(',',$row['person_ids']);
            foreach ($person_ids as $person_id) {
                print "<tr style='border-collapse: collapse;border: 1px solid black;'>";
                print '<td>' . $person_id . '</td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['lastname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['firstname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['email'] ) . '</a></td>';
                if ( $row['wp_id'] && isset( $wp_users[ $row['wp_id'] ] ) ) {
                    print '<td>' . eme_esc_html( $wp_users[ $row['wp_id'] ] ) . '</td>';
                } else {
                    print '<td>' . esc_html__('Non-existing WP user linked!!','events-made-easy' ) . '</td>';
                }
                $membership_names = eme_get_linked_activemembership_names_by_personid( $person_id );
                print "<td>$membership_names</td>";
                $future_bookings = eme_get_bookings_by_person_id( $person_id, "future" );
                if (!empty($future_bookings)) {
                    print "<td>".esc_html__('Yes','events_made_easy')."</td>";
                } else {
                    print "<td>".esc_html__('No','events_made_easy')."</td>";
                }
                print '</tr>';
            }
        }
        print '</table>';
    } else {
        esc_html_e( 'No issues found', 'events-made-easy' );
    }

    if ( get_option( 'eme_unique_email_per_person' ) ) :
?>
    <h1><?php esc_html_e( 'Verify unique emails', 'events-made-easy' ); ?></h1>
<?php
        // the next function returns a row containing multiple person ids per line (csv), lastname,firstname,email
        $res_arr = eme_find_persons_double_email();
    if ( count( $res_arr ) > 0 ) {
        esc_html_e( 'The table below shows the people that have identical emails while you require a unique email per person', 'events-made-easy' );
        print '<br>';
        esc_html_e( 'Please correct these errors: all EME people should have a unique email.', 'events-made-easy' );
        print "<table class='eme_admin_table'>";
        print '<tr>';
        print '<th>' . esc_html__( 'ID', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Last name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'First name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Email', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Active memberships', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Future bookings made?', 'events-made-easy' ) . '</th>';
        print '</tr>';
        foreach ( $res_arr as $row ) {
            $person_ids = explode(',',$row['person_ids']);
            foreach ($person_ids as $person_id) {
                print "<tr style='border-collapse: collapse;border: 1px solid black;'>";
                print '<td>' . $person_id . '</td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['lastname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['firstname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['email'] ) . '</a></td>';
                $membership_names = eme_get_linked_activemembership_names_by_personid( $person_id );
                print "<td>$membership_names</td>";
                $future_bookings = eme_get_bookings_by_person_id( $person_id, "future" );
                if (!empty($future_bookings)) {
                    print "<td>".esc_html__('Yes','events_made_easy')."</td>";
                } else {
                    print "<td>".esc_html__('No','events_made_easy')."</td>";
                }
                print '</tr>';
            }
        }
        print '</table>';
    } else {
        esc_html_e( 'No issues found', 'events-made-easy' );
    }

    else :
?>
    <h1><?php esc_html_e( 'Verify unique name/email combinations', 'events-made-easy' ); ?></h1>
<?php
        // the next function returns a row containing multiple person ids per line (csv), lastname,firstname,email
        $res_arr = eme_find_persons_double_name_email();
    if ( count( $res_arr ) > 0 ) {
        esc_html_e( 'The table below shows the people that have an identical name and email', 'events-made-easy' );
        print '<br>';
        esc_html_e( 'Please correct these errors: all EME people should have an unique name and email combination.', 'events-made-easy' );
        print "<table class='eme_admin_table'>";
        print '<tr>';
        print '<th>' . esc_html__( 'ID', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Last name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'First name', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Email', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Active memberships', 'events-made-easy' ) . '</th>';
        print '<th>' . esc_html__( 'Future bookings made?', 'events-made-easy' ) . '</th>';
        print '</tr>';
        foreach ( $res_arr as $row ) {
            $person_ids = explode(',',$row['person_ids']);
            foreach ($person_ids as $person_id) {
                print "<tr style='border-collapse: collapse;border: 1px solid black;'>";
                print '<td>' . $person_id . '</td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['lastname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['firstname'] ) . '</a></td>';
                print "<td><a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person_id ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $row['email'] ) . '</a></td>';
                $membership_names = eme_get_linked_activemembership_names_by_personid( $person_id );
                print "<td>$membership_names</td>";
                $future_bookings = eme_get_bookings_by_person_id( $person_id, "future" );
                if (!empty($future_bookings)) {
                    print "<td>".esc_html__('Yes','events_made_easy')."</td>";
                } else {
                    print "<td>".esc_html__('No','events_made_easy')."</td>";
                }
                print '</tr>';
            }
        }
        print '</table>';
    } else {
        esc_html_e( 'No issues found', 'events-made-easy' );
    }
    endif;
?>
    </div>
    </div>
<?php
}

function eme_render_people_table_and_filters( $limit_to_group = 0) {
    $groups                  = eme_get_static_groups();
    $pdftemplates            = eme_get_templates( 'pdf', 1 );
    $htmltemplates           = eme_get_templates( 'html', 1 );
?>
    <form id="eme-admin-regsearchform" name="eme-admin-regsearchform" action="#" method="post">
<?php
    eme_render_people_searchfields( limit_to_group: $limit_to_group);
?>
        <button id="PeopleLoadRecordsButton" class="button action eme_admin_button_middle"><?php esc_html_e( 'Filter people', 'events-made-easy' ); ?></button>
<?php
    if (empty($limit_to_group)) {
?>
        <button id="StoreQueryButton" class="button action eme_admin_button_middle"><?php esc_html_e( 'Store result as dynamic group', 'events-made-easy' ); ?></button>
        <div id="StoreQueryDiv"><?php esc_html_e( 'Enter a name for this dynamic group', 'events-made-easy' ); ?> <input type="text" id="dynamicgroupname" name="dynamicgroupname" class="clearable" size=20>
    <button id="StoreQuerySubmitButton" class="button action"><?php esc_html_e( 'Store dynamic group', 'events-made-easy' ); ?></button>
        </div>
<?php
    }
?>

<?php
    $formfields_searchable = eme_get_searchable_formfields( 'people' );
    if ( empty( $limit_to_group ) && ! empty( $formfields_searchable ) ) {
?>
        <div id="hint">
        <?php esc_html_e( 'Hint: when searching for custom field values, you can optionally limit which custom fields you want to search in the "Custom fields to filter on" select-box shown.', 'events-made-easy' ); ?><br>
        </div>
<?php
    }
?>
    </form>

    <div id="bulkactions">
    <form id='people-form' action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <?php if ( isset( $_GET['trash'] ) && $_GET['trash'] == 1 ) { ?> 
    <option value="untrashPeople"><?php esc_html_e( 'Restore selected persons', 'events-made-easy' ); ?></option>
    <option value="deletePeople"><?php esc_html_e( 'Permanently delete selected persons', 'events-made-easy' ); ?></option>
    <?php } else { ?>
    <option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
        <?php if ( !$limit_to_group  ) : ?>
    <option value="addToGroup"><?php esc_html_e( 'Add to group', 'events-made-easy' ); ?></option>
    <option value="removeFromGroup"><?php esc_html_e( 'Remove from group', 'events-made-easy' ); ?></option>
        <?php endif; ?>
    <option value="gdprApprovePeople"><?php esc_html_e( 'Set GDPR approval to yes', 'events-made-easy' ); ?></option>
    <option value="gdprUnapprovePeople"><?php esc_html_e( 'Set GDPR approval to no', 'events-made-easy' ); ?></option>
    <option value="massmailPeople"><?php esc_html_e( 'Set Massmail to yes', 'events-made-easy' ); ?></option>
    <option value="noMassmailPeople"><?php esc_html_e( 'Set Massmail to no', 'events-made-easy' ); ?></option>
    <option value="bdemailPeople"><?php esc_html_e( 'Set Birthday email to yes', 'events-made-easy' ); ?></option>
    <option value="noBdemailPeople"><?php esc_html_e( 'Set Birthday email to no', 'events-made-easy' ); ?></option>
        <?php if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) : ?>
    <option value="trashPeople"><?php esc_html_e( 'Delete selected persons (move to trash)', 'events-made-easy' ); ?></option>
    <option value="gdprPeople"><?php esc_html_e( 'Remove personal data (and move to trash bin)', 'events-made-easy' ); ?></option>
        <?php endif; ?>
    <option value="changeLanguage"><?php esc_html_e( 'Change language of selected persons', 'events-made-easy' ); ?></option>
    <option value="pdf"><?php esc_html_e( 'PDF output', 'events-made-easy' ); ?></option>
    <option value="html"><?php esc_html_e( 'HTML output', 'events-made-easy' ); ?></option>
    <?php } ?>
    </select>
    <span id="span_language" class="eme-hidden">
    <?php esc_html_e( 'Change language to: ', 'events-made-easy' ); ?>
    <input type='text' id='language' name='language'>
    </span>
    <span id="span_transferto" class="eme-hidden">
    <?php esc_html_e( 'Transfer associated bookings and task signups to (leave empty for moving bookings for future events to trash too):', 'events-made-easy' ); ?>
    <select id='transferto_id' name='transferto_id'
        data-placeholder="<?php esc_attr_e( 'Select a person', 'events-made-easy' ); ?>"
        class="eme_snapselect_chooseperson">
    </select>
    </span>
    <span id="span_addtogroup" class="eme-hidden">
    <?php echo eme_ui_select_key_value( '', 'addtogroup', $groups, 'group_id', 'name', __( 'Select a group', 'events-made-easy' ), 1 ); ?>
    </span>
    <span id="span_removefromgroup" class="eme-hidden">
    <?php echo eme_ui_select_key_value( '', 'removefromgroup', $groups, 'group_id', 'name', __( 'Select a group', 'events-made-easy' ), 1 ); ?>
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
    <button id="PeopleActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
<?php
    $formfields               = eme_get_formfields( '', 'people' );
    $extrafields_arr          = [];
    $extrafieldnames_arr      = [];
    $extrafieldsearchable_arr = [];
    foreach ( $formfields as $formfield ) {
        $extrafields_arr[]          = intval($formfield['field_id']);
        $extrafieldnames_arr[]      = str_replace(',','&sbquo;',eme_translate( $formfield['field_name'] ));
        $extrafieldsearchable_arr[] = esc_html($formfield['searchable']);
    }
    $extrafields          = join( ',', $extrafields_arr );
    $extrafieldnames      = join( ',', $extrafieldnames_arr );
    $extrafieldsearchable = join( ',', $extrafieldsearchable_arr );
?>
    <div id="PeopleTableContainer" data-extrafields='<?php echo esc_attr( $extrafields ); ?>' data-extrafieldnames='<?php echo esc_attr( $extrafieldnames ); ?>' data-extrafieldsearchable='<?php echo esc_attr( $extrafieldsearchable ); ?>'></div>
<?php
}

function eme_render_people_searchfields( $limit_to_group = 0, $group_to_edit = [] ) {
    $eme_member_status_array = eme_member_status_array();
    $memberships             = eme_get_memberships();
    $groups                  = eme_get_static_groups();

    // setting the id_prefix helps in avoiding double id's if eme_render_people_searchfields is called
    // on a group page 2 times (ones to edit the group, once to show the group members+filters)
    // Double id's causes some javascripts to flip ...
    if ( ! empty( $group_to_edit ) ) {
        $edit_group   = 1;
        $id_prefix    = 'edit_';
        $search_terms = eme_unserialize( $group_to_edit['search_terms'] );
        // just to make sure ...
        $limit_to_group = 0;
    } else {
        $edit_group = 0;
        $id_prefix  = '';
        if ($limit_to_group) {
            $tmp_group = eme_get_group($limit_to_group);
            $search_terms = eme_unserialize( $tmp_group['search_terms'] );
        }
    }

    if ( $edit_group ) {
        echo '</td></tr><tr><td>' . esc_html__( 'Filter on person', 'events-made-easy' ) . '</td><td>';
    }
    if ( isset( $search_terms['search_person'] ) ) {
        $value = $search_terms['search_person'];
    } else {
        $value = '';
    }
    echo '<input type="search" value="' . esc_attr($value) . '" name="search_person" id="'.$id_prefix.'search_person" placeholder="' . esc_attr__( 'Filter on person', 'events-made-easy' ) . '" class="eme_searchfilter" size=15>';

    if ($limit_to_group) {
        echo '<input type="hidden" name="search_groups" id="'.$id_prefix.'search_groups" value="' . esc_attr($limit_to_group) . '">';
    } else {
        if ( $edit_group ) {
            echo '</td></tr><tr><td>' . esc_html__( 'Filter on group', 'events-made-easy' ) . '</td><td>';
        }
        if ( isset( $search_terms['search_groups'] ) ) {
            $value = $search_terms['search_groups'];
        } else {
            $value = '';
        }
        $extra_attributes = '" data-placeholder="' . esc_html( __( 'Any group', 'events-made-easy' )) . '"';
        echo eme_ui_multiselect_key_value( $value, 'search_groups', $groups, 'group_id', 'name', 5, '', 0, 'eme_snapselect', $extra_attributes, id_prefix: $id_prefix );
    }

    if ( $edit_group ) {
        echo '<tr><td>' . esc_html__( 'Select memberships', 'events-made-easy' ) . '</td><td>';
    }
    if ( isset( $search_terms['search_membershipids'] ) ) {
        $value = $search_terms['search_membershipids'];
    } else {
        $value = '';
    }
    $extra_attributes = '" data-placeholder="' . esc_html( __( 'Filter on membership', 'events-made-easy' )) . '"';
    echo eme_ui_multiselect_key_value( $value, 'search_membershipids', $memberships, 'membership_id', 'name', 5, '', 0, 'eme_snapselect', $extra_attributes, id_prefix: $id_prefix );

    if ( $edit_group ) {
        echo '</td></tr><tr><td>' . esc_html__( 'Select member status', 'events-made-easy' ) . '</td><td>';
    }
    if ( isset( $search_terms['search_memberstatus'] ) ) {
        $value = $search_terms['search_memberstatus'];
    } else {
        $value = '';
    }
    $extra_attributes = '" data-placeholder="' . __( 'Filter on member status', 'events-made-easy' ) . '"';
    echo eme_ui_multiselect( $value, 'search_memberstatus', $eme_member_status_array, 5, '', 0, 'eme_snapselect', $extra_attributes, id_prefix: $id_prefix );

    $formfields_searchable = eme_get_searchable_formfields( 'people' );
    if ( ! empty( $formfields_searchable ) ) {
        if ( $edit_group ) {
            echo '</td></tr><tr><td>' . esc_html__( 'Custom field value to search', 'events-made-easy' ) . '</td><td>';
        }
        if ( isset( $search_terms['search_customfields'] ) ) {
            $value = $search_terms['search_customfields'];
        } else {
            $value = '';
        }
        echo '<input type="search" value="' . esc_attr($value) . '" name="search_customfields" id="'.$id_prefix.'search_customfields" placeholder="' . esc_html__( 'Custom field value to search', 'events-made-easy' ) . '" class="eme_searchfilter" size=20>';

        if ( $edit_group ) {
            echo '</td></tr><tr><td>' . esc_html__( 'Custom field to search', 'events-made-easy' ) . '</td><td>';
        }
        if ( isset( $search_terms['search_customfieldids'] ) ) {
            $value = $search_terms['search_customfieldids'];
        } else {
            $value = '';
        }
        $label = __( 'Custom fields to filter on', 'events-made-easy' );
        $extra_attributes = 'aria-label="' . eme_esc_html( $label ) . '" data-placeholder="' . eme_esc_html( $label ) . '"';
        echo eme_ui_multiselect_key_value( $value, 'search_customfieldids', $formfields_searchable, 'field_id', 'field_name', 5, '', 0, 'eme_snapselect', $extra_attributes, 1, id_prefix: $id_prefix );
        if ( $edit_group ) {
            echo '</td></tr><tr><td>' . esc_html__( 'Exact custom field search match', 'events-made-easy' ) . '</td><td>';
        }
        if ( isset( $search_terms['search_exactmatch'] ) ) {
            $value = intval($search_terms['search_exactmatch']);
        } else {
            $value = 0;
        }
        if ( $edit_group ) {
            $label = '';
        } else {
            $label = __( 'Exact?', 'events-made-easy' );
        }
        $title = esc_attr__( 'Exact custom field search match', 'events-made-easy' );
        echo eme_nobreak_checkbox_binary( $value, 'search_exactmatch', $label, 0, '', "title='$title'");
    }
}

function eme_get_sql_people_searchfields( $search_terms, $count = 0, $ids_only = 0, $emails_only = 0, $where_arr=[] ) {
    global $wpdb;
    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $answers_table    = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $members_table    = EME_DB_PREFIX . EME_MEMBERS_TBNAME;

    // trim the search_person param too
    $search_person = isset( $search_terms['search_person'] ) ? esc_sql( $wpdb->esc_like( trim( $search_terms['search_person'] ) ) ) : '';

    // if the person is not allowed to manage all people, show only himself
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        $wp_id     = get_current_user_id();
        $person_id = eme_get_personid_by_wpid( $wp_id );
        if ( ! $person_id ) {
            $person_id = -1;
        }
        $where_arr[] = "people.person_id = $person_id";
    }

    // lets get the trash info from the url if present
    $status      = isset( $search_terms['trash'] ) && $search_terms['trash'] == 1 ? 0 : 1;
    $where_arr[] = "people.status=$status";

    if ( ! empty( $search_person ) ) {
        $where_arr[] = "(people.lastname like '%" . $search_person . "%' OR people.firstname like '%" . $search_person . "%' OR people.email like '%" . $search_person . "%')";
    }
    $usergroup_join = '';
    if ( ! empty( $search_terms['search_groups'] ) && eme_is_numeric_array( $search_terms['search_groups'] ) ) {
        $search_groups  = join( ',', $search_terms['search_groups'] );
        $where_arr[]    = "ugroups.group_id IN ($search_groups)";
        $usergroup_join = "LEFT JOIN $usergroups_table AS ugroups ON people.person_id=ugroups.person_id";
    }
    if ( ! empty( $search_terms['search_groups'] ) && is_numeric( $search_terms['search_groups'] ) ) {
        $tmp_group = eme_get_group($search_terms['search_groups'] );
        if ( $tmp_group['type'] == "dynamic_people" ) {
            $person_ids_arr = eme_get_groups_person_ids($search_terms['search_groups'] );
            if (!empty($person_ids_arr)) {
                $where_arr[]    = "people.person_id IN ( ".join(',',$person_ids_arr) .")";
            }
        } elseif ( $tmp_group['type'] == "static" ) {
            $where_arr[]    = "ugroups.group_id = ".$search_terms['search_groups'];
            $usergroup_join = "LEFT JOIN $usergroups_table AS ugroups ON people.person_id=ugroups.person_id";
        }
    }
    $member_join = '';
    if ( ! empty( $search_terms['search_membershipids'] ) && eme_is_numeric_array( $search_terms['search_membershipids'] ) ) {
        $search_membershipids = join( ',', $search_terms['search_membershipids'] );
        $where_arr[]          = "(members.membership_id IN ($search_membershipids))";
        $member_join          = "INNER JOIN $members_table AS members ON people.person_id=members.person_id";
    }
    // search_status can be 0 too, for pending
    if ( ! empty( $search_terms['search_memberstatus'] ) && eme_is_numeric_array( $search_terms['search_memberstatus'] ) ) {
        $search_memberstatus = join( ',', $search_terms['search_memberstatus'] );
        $where_arr[]         = "(members.status IN ($search_memberstatus))";
        if ( empty( $member_join ) ) {
            $member_join = "INNER JOIN $members_table AS members ON people.person_id=members.person_id";
        }
    }

    $where_arr = eme_array_remove_empty_elements($where_arr);
    if ( !empty($where_arr) ) {
        $where = 'WHERE ' . join( ' AND ', $where_arr );
    } else {
        $where = '';
    }

    $formfields_searchable = eme_get_searchable_formfields( 'people' );

    // we need this GROUP_CONCAT so we can sort on those fields too (otherwise the columns FIELD_* don't exist in the returning sql
    $group_concat_sql = '';
    $field_ids_arr    = [];
    foreach ( $formfields_searchable as $formfield ) {
        $field_id          = $formfield['field_id'];
        $field_ids_arr[]   = $field_id;
        $group_concat_sql .= "GROUP_CONCAT(CASE WHEN field_id = $field_id THEN answer END) AS 'FIELD_$field_id',";
    }

    $search_formfield_sql = '';
    if ( ! empty( $formfields_searchable ) && isset( $search_terms['search_customfields'] ) ) {
        // small optimization
        if ( $search_terms['search_customfields'] == '' ) {
            $search_customfields = '';
            $search_formfield_sql = " AND answer = '' ";
        } elseif (! empty($search_terms['search_exactmatch']))  {
            $search_customfields = esc_sql( $search_terms['search_customfields'] );
            $search_formfield_sql = " AND answer = '$search_customfields' ";
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
             WHERE related_id>0 AND type='person' $search_formfield_sql
             GROUP BY related_id
            ) ans
           ON people.person_id=ans.related_id";
    } else {
        $sql_join = "
           LEFT JOIN (SELECT $group_concat_sql related_id FROM $answers_table
             WHERE related_id>0 AND type='person'
             GROUP BY related_id
            ) ans
           ON people.person_id=ans.related_id";
    }
    if ( $count ) {
        $sql = "SELECT COUNT(distinct(people.person_id)) FROM $people_table AS people $usergroup_join $member_join $sql_join $where";
    } elseif ( $ids_only ) {
        $sql = "SELECT people.person_id FROM $people_table AS people $usergroup_join $member_join $sql_join $where GROUP BY people.person_id";
    } elseif ( $emails_only ) {
        $sql = "SELECT people.email FROM $people_table AS people $usergroup_join $member_join $sql_join $where GROUP BY people.person_id";
    } else {
        $limit   = eme_get_datatables_limit();
        $orderby = eme_get_datatables_orderby();
        $sql = "SELECT people.* FROM $people_table AS people $usergroup_join $member_join $sql_join $where GROUP BY people.person_id $orderby $limit";
    }
    return $sql;
}

function eme_manage_people_layout( $message = '' ) {
    global $plugin_page;

    if ( empty( $message ) ) {
        $hidden_class = 'eme-hidden';
    } else {
        $hidden_class = '';
    }

?>
<div class="wrap nosubsub">
<div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <div id="people-message" class="<?php echo $hidden_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded CSS class string ?>">
        <p><?php echo wp_kses_post( $message ); ?></p>
    </div>

    <?php if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) : ?>
    <h1><?php esc_html_e( 'Add a new person', 'events-made-easy' ); ?></h1>
    <div class="wrap">
    <form id="people-filter" method="post" action="<?php echo admin_url( 'admin.php?page=eme-people' ); ?>">
        <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
        <input type="hidden" name="eme_admin_action" value="add_person">
        <input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add person', 'events-made-easy' ); ?>">
    </form>
    </div>
<?php endif; ?>

    <h1><?php esc_html_e( 'Manage people', 'events-made-easy' ); ?></h1>
    <?php echo sprintf( __( "Click <a href='%s'>here</a> to verify the integrity of EME people", 'events-made-easy' ), admin_url( "admin.php?page=$plugin_page&eme_admin_action=verify_people" ) ); ?><br>

    <?php if ( isset( $_GET['trash'] ) && $_GET['trash'] == 1 ) { ?> 
        <a href="<?php echo admin_url( "admin.php?page=$plugin_page&trash=0" ); ?>"><?php esc_html_e( 'Show regular content', 'events-made-easy' ); ?></a><br>
    <?php } else { ?>
        <a href="<?php echo admin_url( "admin.php?page=$plugin_page&trash=1" ); ?>"><?php esc_html_e( 'Show trash content', 'events-made-easy' ); ?></a><br>
        <?php if ( current_user_can( get_option( 'eme_cap_cleanup' ) ) ) { ?>
        <span class="eme_import_form_img">
            <?php esc_html_e( 'Click on the icon to show the import form', 'events-made-easy' ); ?>
        <img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="eme_div_import" style="cursor: pointer; vertical-align: middle; ">
        </span>
        <div id='eme_div_import' class='eme-hidden'>
        <form id='people-import' method='post' enctype='multipart/form-data' action='#'>
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
        <input type="file" name="eme_csv">
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
        <input type="text" size=1 maxlength=1 name="delimiter" value=',' required='required'>
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
        <input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"' required='required'>
        <input type="hidden" name="eme_admin_action" value="import_people">
        <input type="submit" value="<?php esc_html_e( 'Import', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
            <?php esc_html_e( 'If you want, use this to import people info into the database', 'events-made-easy' ); ?>
        </form>
        </div>
        <?php } ?>
    <?php } ?>

<?php 
    eme_render_people_table_and_filters();
?>
</div>
</div>
<?php
}

function eme_person_edit_layout( $person_id = 0, $message = '' ) {
    global $plugin_page;

    // if only 1 country, set it as default
    $countries_alpha2 = eme_get_countries_alpha2();
    if ( count( $countries_alpha2 ) == 1 ) {
        $person['country_code'] = $countries_alpha2[0];
    }

    if ( ! $person_id ) {
        $action          = 'add';
        $persongroup_ids = [];
        $person          = eme_new_person();
    } else {
        $action          = 'edit';
        $person          = eme_get_person( $person_id );
        $persongroup_ids = eme_get_persongroup_ids( $person_id );
    }
    if ( ! empty( $person['country_code'] ) ) {
        $country_code = $person['country_code'];
        $country_arr  = [ $country_code => eme_get_country_name( $country_code ) ];
    } else {
        $country_arr = [];
    }
    if ( ! empty( $person['state_code'] ) ) {
        $country_code = $person['country_code']; // can be empty
        $state_code   = $person['state_code'];
        $state_arr    = [ $state_code => eme_get_state_name( $state_code, $country_code ) ];
    } else {
        $state_arr = [];
    }
    if ( ! empty( $person['related_person_id'] ) ) {
        $related_person = eme_get_person( $person['related_person_id'] );
    } else {
        $related_person = null;
    }
    if ( ! empty( $related_person ) ) {
        $related_person_id    = $person['related_person_id'];
    } else {
        $related_person_id    = '';
    }
    if ( $person['status'] == EME_PEOPLE_STATUS_TRASH ) {
        $readonly = 1;
    } else {
        $readonly = 0;
    }
    if ( $person['wp_id'] ) {
        $wp_readonly = "readonly='readonly'";
    } else {
        $wp_readonly = '';
    }

    $groups      = eme_get_static_groups();
?>
    <div class="wrap">
        <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <h1>
<?php
    if ( $action == 'add' ) {
        esc_html_e( 'Add person', 'events-made-easy' );
    } elseif ( $readonly ) {
        esc_html_e( 'View person in trash (read-only)', 'events-made-easy' );
    } else {
        esc_html_e( 'Edit person', 'events-made-easy' );
    }
?>
    </h1>

    <?php if ( $message != '' ) { ?>
        <div id="message">
            <?php echo wp_kses_post( $message ); ?>
        </div>
    <?php } ?>
    <div id="ajax-response"></div>
    <?php if ( ! $readonly ) { ?>
    <form name="editperson" id="editperson" method="post" autocomplete="off" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>" class="validate" enctype='multipart/form-data'>
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <?php if ( $action == 'add' ) { ?>
            <input type="hidden" name="eme_admin_action" value="do_addperson">
        <?php } else { ?>
            <input type="hidden" name="eme_admin_action" value="do_editperson">
            <input type="hidden" name="person_id" value="<?php echo intval($person['person_id']); ?>">
        <?php } ?>
    <?php } else { ?>
    <fieldset disabled="disabled">
    <?php } ?>

    <div id="div_person" class="postbox">
        <div class="inside">
        <table>
        <tr>
        <td style="vertical-align:top"><label for="firstname"><?php esc_html_e( 'First name', 'events-made-easy' ); ?></label></td>
        <td><input id="firstname" name="firstname" type="text" value="<?php echo eme_esc_html( $person['firstname'] ); ?>" size="40" <?php echo $wp_readonly; ?>><br>
<?php
    if ( ! empty( $wp_readonly ) ) {
        esc_html_e( 'Since this person is linked to a WP user, this field is read-only', 'events-made-easy' );
    }
?>
        </td>
        <td rowspan=10>
        <?php echo eme_person_replace_image_input( $person ); ?>
        </td>
        </tr>
        <tr>
        <td style="vertical-align:top"><label for="lastname"><?php esc_html_e( 'Last name', 'events-made-easy' ); ?></label></td>
        <td><input id="lastname" name="lastname" type="text" value="<?php echo eme_esc_html( $person['lastname'] ); ?>" size="40" <?php echo $wp_readonly; ?>><br>
<?php
    if ( ! empty( $wp_readonly ) ) {
        esc_html_e( 'Since this person is linked to a WP user, this field is read-only', 'events-made-easy' );
    }
?>
        </td>
        <td></td>
        </tr>
        <tr>
        <td style="vertical-align:top"><label for="email"><?php esc_html_e( 'Email', 'events-made-easy' ); ?></label></td>
        <td><input id="email" name="email" type="email" value="<?php echo eme_esc_html( $person['email'] ); ?>" size="40" <?php echo $wp_readonly; ?> autocomplete="off"><br>
<?php
    if ( ! empty( $wp_readonly ) ) {
        esc_html_e( 'Since this person is linked to a WP user, this field is read-only', 'events-made-easy' );
    }
?>
            </td>
        <td></td>
        </tr>
        <tr>
        <td style="vertical-align:top"><label for="related_person_id"><?php esc_html_e( 'Related family member', 'events-made-easy' ); ?></label></td>
        <td>
<?php
    $preselected_option = '';
    if ( ! empty( $related_person ) ) {
        $preselected_text   = eme_esc_html( eme_format_full_name( $related_person['firstname'], $related_person['lastname'], $related_person['email'] ) );
        $preselected_option = '<option value="' . intval( $person['related_person_id'] ) . '" selected>' . $preselected_text . '</option>';
    }
?>
        <select id='related_person_id' name='related_person_id'
            data-placeholder="<?php esc_html_e( 'Select a person', 'events-made-easy' ); ?>"
            data-person-id="<?php echo intval( $person['person_id'] ); ?>"
            class="eme_snapselect_chooserelatedperson">
            <?php echo $preselected_option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML option element ?>
        </select>
<?php
    if ( $person['related_person_id'] > 0 ) {
        print "<a href='" . admin_url( "admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=$related_person_id" ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . esc_html__( 'Click here to edit that person', 'events-made-easy' ) . '</a>';
    }
?>
            </td>
        <td></td>
        </tr>
        <tr>
        <td style="vertical-align:top"><?php esc_html_e( 'Family members:', 'events-made-easy' ); ?></td>
        <td>
<?php
    $familymember_person_ids = eme_get_family_person_ids( $person_id );
    if ( $action == 'edit' && ! empty( $familymember_person_ids ) ) {
        foreach ( $familymember_person_ids as $family_person_id ) {
            $family_person = eme_get_person( $family_person_id );
            if ( $family_person ) {
                print "<a href='" . admin_url( "admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=$family_person_id" ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( eme_format_full_name( $family_person['firstname'], $family_person['lastname'], $family_person['email'] ) ) . '</a><br>';
            }
        }
    }
?>
        </td>
        <td></td>
        </tr>
        <tr>
        <td><label for="phone"><?php esc_html_e( 'Phone Number', 'events-made-easy' ); ?></label></td>
        <td><input id="phone" name="phone" type="text" value="<?php echo eme_esc_html( $person['phone'] ); ?>" size="40" autocomplete="off"></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="address1"><?php echo get_option( 'eme_address1_string' ); ?></label></td>
        <td><input id="address1" name="address1" type="text" value="<?php echo eme_esc_html( $person['address1'] ); ?>" size="40"></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="address2"><?php echo get_option( 'eme_address2_string' ); ?></label></td>
        <td><input id="address2" name="address2" type="text" value="<?php echo eme_esc_html( $person['address2'] ); ?>" size="40"></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="zip"><?php esc_html_e( 'Postal code', 'events-made-easy' ); ?></label></td>
        <td><input name="zip" id="zip" type="text" value="<?php echo eme_esc_html( $person['zip'] ); ?>" size="40"></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="city"><?php esc_html_e( 'City', 'events-made-easy' ); ?></label></td>
        <td><input name="city" id="city" type="text" value="<?php echo eme_esc_html( $person['city'] ); ?>" size="40"></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="country_code"><?php esc_html_e( 'Country', 'events-made-easy' ); ?></label></td>
        <td><?php echo eme_ui_select( $person['country_code'], 'country_code', $country_arr, '', 0, 'eme_snapselect_country_class' ); ?></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="state_code"><?php esc_html_e( 'State', 'events-made-easy' ); ?></label></td>
        <td><?php echo eme_ui_select( $person['state_code'], 'state_code', $state_arr, '', 0, 'eme_snapselect_state_class' ); ?></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="dp_birthdate"><?php esc_html_e( 'Date of birth', 'events-made-easy' ); ?></label></td>
        <td><input type='hidden' name='birthdate' id='birthdate' value='<?php echo eme_esc_html( $person['birthdate'] ); ?>'>
        <input readonly='readonly' type='text' name='dp_birthdate' id='dp_birthdate' data-date='<?php echo eme_esc_html( $person['birthdate'] ); ?>' data-format='<?php echo EME_WP_DATE_FORMAT; ?>' data-alt-field='birthdate' data-view='years' class='eme_formfield_fdate'></td>
        <td></td>
        </tr>
        <tr>
        <td><label for="bd_email"><?php esc_html_e( 'Birthday email', 'events-made-easy' ); ?></label></td>
        <td colspan=2>
<?php
    echo eme_ui_select_binary( $person['bd_email'], 'bd_email' );
    esc_html_e( 'If active, the person will receive a birthday email.', 'events-made-easy' );
?>
        </td>
        </tr>
        <tr>
        <td><label for="birthplace"><?php esc_html_e( 'Place of birth', 'events-made-easy' ); ?></label></td>
        <td colspan=2><input id="birthplace" name="birthplace" type="text" value="<?php echo eme_esc_html( $person['birthplace'] ); ?>" size="40"></td>
        </tr>
        <tr>
        <td><label for="language"><?php esc_html_e( 'Language', 'events-made-easy' ); ?></label></td>
        <td colspan=2><input id="language" name="language" type="text" value="<?php echo eme_esc_html( $person['lang'] ); ?>" size="40" maxlength="7"></td>
        </tr>
        <tr>
        <tr>
        <td><label for="massmail"><?php esc_html_e( 'MassMail', 'events-made-easy' ); ?></label></td>
        <td colspan=2><?php echo eme_ui_select_binary( $person['massmail'], 'massmail' ); ?></td>
        </tr>
        <tr>
        <td><label for="newsletter"><?php esc_html_e( 'Newsletter', 'events-made-easy' ); ?></label></td>
        <td colspan=2><?php echo eme_ui_select_binary( $person['newsletter'], 'newsletter' ); ?></td>
        </tr>
        <tr>
        <td><label for="gdpr"><?php esc_html_e( 'GDPR approval', 'events-made-easy' ); ?></label></td>
        <td colspan=2><?php echo eme_ui_select_binary( $person['gdpr'], 'gdpr' ); ?></td>
        </tr>
        <tr>
        <td><label for="groups"><?php esc_html_e( 'Groups', 'events-made-easy' ); ?></label></td>
        <td colspan=2><?php 
            $extra_attributes = '" data-placeholder="' . esc_html( __( 'Select one or more groups', 'events-made-easy' )) . '"';
            echo eme_ui_multiselect_key_value( $persongroup_ids, 'groups', $groups, 'group_id', 'name', 5, '', 0, 'dyngroups eme_snapselect', $extra_attributes );
            ?><br>
        <?php esc_html_e( "Don't forget that you can define custom fields with purpose 'People' that will allow extra info based on the group the person is in.", 'events-made-easy' ); ?>
        </td>
        </tr>
<?php 
    if ($action == 'edit' ) {
        $membership_names = eme_get_linked_activemembership_names_by_personid( $person['person_id'] );
    } else {
        $membership_names = '';
    }
    if ( ! empty( $membership_names ) ) :
?>
        <tr>
        <td><?php esc_html_e( 'Active memberships', 'events-made-easy' ); ?></td>
        <td colspan=2><?php echo $membership_names; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped membership names HTML ?></td>
        </tr>
<?php
        endif;
?>
    <?php if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) : ?>
        <tr>
        <td style="vertical-align:top"><label for="wp_id"><?php esc_html_e( 'Linked WP user', 'events-made-easy' ); ?></label></td>
        <td colspan=2>
<?php
    $used_wp_ids = eme_get_used_wpids( $person['wp_id'] );
    $exclude = join( ',', $used_wp_ids );
    wp_dropdown_users(
        [
            'name'             => 'wp_id',
            'show_option_none' => '&nbsp;',
            'selected'         => $person['wp_id'],
            'exclude'          => $exclude,
        ]
    );
?>
            <br>
            <?php esc_html_e( "Linking an EME person with a WP user will not be allowed if there's another EME person matching the WP user's firstname/lastname/email.", 'events-made-easy' ); ?><br>
            <?php esc_html_e( "Linking an EME person with a WP user will change the person firstname/lastname/email to the WP user's firstname/lastname/email and those fields can then only be changed via the WP profile of that person.", 'events-made-easy' ); ?>
        </td>
        </tr>
        <tr>
        <td style="vertical-align:top"><label for="properties[wp_delete_user]"><?php esc_html_e( 'Delete linked WP user?', 'events-made-easy' ); ?></label></td>
        <td colspan=2><?php echo eme_ui_select_binary( $person['properties']['wp_delete_user'], "properties[wp_delete_user]" ); ?>
            <br>
            <?php esc_html_e( "Set this to yes if you want the linked WP user to be deleted when the EME person gets removed (moved to trash bin).", 'events-made-easy' ); ?><br>
            <?php esc_html_e( "By default, this is only set to true when a WP user is created by EME (when creating a member or doing a reservation for an event and the option to create a WP user is set). An admin will never be deleted.", 'events-made-easy' ); ?>
        </td>
        </td>
        </tr>
<?php
    endif;

    //if ($action == "edit") {
    //$files_title = esc_html__( 'Uploaded files', 'events-made-easy' );
    //print eme_get_uploaded_files_tr($person_id,"people",$files_title);
    //}
?>
        </table>
        </div>
        <div class='inside' id='eme_dynpersondata'></div>
    </div>
    <?php if ( $readonly ) { ?>
    </fieldset>
    <?php } else { ?>
    <p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php if ( $action == 'add' ) { esc_html_e( 'Add person', 'events-made-easy' ); } else { esc_html_e( 'Update person', 'events-made-easy' ); } ?>"></p>
    </form>
    <?php } ?>
    </div>
<?php
}

function eme_group_edit_layout( $group_id = 0, $message = '', $group_type = 'static'  ) {
    global $plugin_page;

    $grouppersons = [];
    $mygroups     = [];
    if ( ! $group_id ) {
        $action = 'add';
        $group  = eme_new_group();
        $group['type'] = $group_type;
    } else {
        $action  = 'edit';
        $group   = eme_get_group( $group_id );
        $persons = eme_get_grouppersons( $group['group_id'] );
        if ( ! empty( $persons ) && is_array( $persons ) ) {
            foreach ( $persons as $person ) {
                // account for possible empty values
                if ( empty( $person['lastname'] ) ) {
                    $mygroups[ $person['person_id'] ] = $person['email'];
                } else {
                    $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
                }
                $grouppersons[] = $person['person_id'];
            }
        }
    }
?>
    <div class="wrap">
        <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <h1>
<?php
    if ( $action == 'add' ) {
        if ( $group['type'] == 'dynamic_people' ) {
            esc_html_e( 'Add dynamic group of people', 'events-made-easy' );
        } elseif ( $group['type'] == 'dynamic_members' ) {
            esc_html_e( 'Add dynamic group of members', 'events-made-easy' );
        } else {
            esc_html_e( 'Add group', 'events-made-easy' );
        }
    } else {
        if ( $group['type'] == 'dynamic_people' ) {
            esc_html_e( 'Edit dynamic group of people', 'events-made-easy' );
        } elseif ( $group['type'] == 'dynamic_members' ) {
            esc_html_e( 'Edit dynamic group of members', 'events-made-easy' );
        } else {
            esc_html_e( 'Edit group', 'events-made-easy' );
        }
    }
?>
    </h1>

    <?php if ( $message != '' ) { ?>
        <div id="message">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
    <?php } ?>
    <div id="ajax-response"></div>
    <form name="editgroup" id="editgroup" method="post" autocomplete="off" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>" class="validate">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
    <input type="hidden" name="group_type" value="<?php echo esc_attr( $group['type'] ); ?>">
    <?php if ( $action == 'add' ) { ?>
    <input type="hidden" name="eme_admin_action" value="do_addgroup">
    <?php } else { ?>
    <input type="hidden" name="eme_admin_action" value="do_editgroup">
    <input type="hidden" name="group_id" value="<?php echo esc_attr( $group['group_id'] ); ?>">
    <?php } ?>

    <!-- we need titlediv and title for qtranslate as ID -->
    <div id="titlediv" class="postbox">
        <div class="inside">
        <table>
        <tr>
        <td><label for="name"><?php esc_html_e( 'Name', 'events-made-easy' ); ?></label></td>
        <td><input required='required' id="name" name="name" type="text" value="<?php echo eme_esc_html( $group['name'] ); ?>" size="40"></td>
        </tr>
        <tr>
        <td><label for="description"><?php esc_html_e( 'Description', 'events-made-easy' ); ?></label></td>
        <td><input id="description" name="description" type="text" value="<?php echo eme_esc_html( $group['description'] ); ?>" size="40"></td>
        </tr>
        <tr>
        <td><label for="email"><?php esc_html_e( 'Group email', 'events-made-easy' ); ?></label></td>
        <td><input id="email" name="email" type="email" value="<?php echo eme_esc_html( $group['email'] ); ?>" size="40"><br>
            <?php esc_html_e( 'If you want to be able to send mail to this group via your mail client (and not just via EME), you need to configure the cli_mail method (see doc) and enter a unique email address for this group. This can be left empty.', 'events-made-easy' ); ?>
            </td>
        </tr>
        <?php if ( $group['type'] == 'static' ) { ?>
        <tr>
        <td><label for="public"><?php esc_html_e( 'Public?', 'events-made-easy' ); ?></label></td>
        <td><?php echo eme_ui_select_binary( $group['public'], 'public' ); ?><br>
            <?php esc_html_e( 'If you chose for this group to be a public group, then this group will appear in the list of groups to subscribe/unsubscribe in the eme_subform and eme_unsubform shortcodes (and the form generated by #_UNSUB_URL).', 'events-made-easy' ); ?>
        </td>
        </tr>
        <tr>
        <td><label for="People"><?php esc_html_e( 'People', 'events-made-easy' ); ?></label></td>
        <td><?php echo eme_ui_multiselect( $grouppersons, 'persons', $mygroups, 5, '', 1, 'eme_snapselect_people_class' ); ?></td>
        </tr>
<?php
            } elseif ( $group['type'] == 'dynamic_people' ) {
                if ( empty( $group['search_terms'] ) && $action == 'edit' ) {
                    echo "<tr><td colspan=2><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'Warning: this group is using an older method of defining the criteria for the members in it. Upon saving this group, you will lose that info, so make sure to reenter the criteria in the fields below', 'events-made-easy' ) . '</td></tr>';
                }
                eme_render_people_searchfields( group_to_edit: $group );
            } elseif ( $group['type'] == 'dynamic_members' ) {
                if ( empty( $group['search_terms'] ) && $action == 'edit' ) {
                    echo "<tr><td colspan=2><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'Warning: this group is using an older method of defining the criteria for the members in it. Upon saving this group, you will lose that info, so make sure to reenter the criteria in the fields below', 'events-made-easy' ) . '</td></tr>';
                }
                eme_render_members_searchfields( group_to_edit: $group );
            }
?>
        </table>
        </div>
    </div>
    <p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php if ( $action == 'add' ) { esc_html_e( 'Add group', 'events-made-easy' ); } else { esc_html_e( 'Update group', 'events-made-easy' ); } ?>"></p>
    </div>
    </form>
<?php
    // let's show the existing members too for existing groups
    if ( $group['type'] == 'dynamic_members' ) {
        eme_render_member_table_and_filters( $group_id );
    } elseif ($group_id) {
        eme_render_people_table_and_filters( $group_id );
    }
?>

    </div>
<?php
}

function eme_manage_groups_layout( $message = '' ) {
    if ( empty( $message ) ) {
        $hidden_class = 'eme-hidden';
    } else {
        $hidden_class = '';
    }
?>
    <div class="wrap nosubsub">
    <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <div id="groups-message" class="<?php echo $hidden_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded CSS class string ?>">
        <p><?php echo wp_kses_post( $message ); ?></p>
    </div>

    <?php if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) : ?>
    <h1><?php esc_html_e( 'Add a new group', 'events-made-easy' ); ?></h1>
    <div class="wrap">
    <form id="add-group" method="post" action="<?php echo admin_url( 'admin.php?page=eme-groups' ); ?>">
        <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
        <input type="hidden" name="eme_admin_action" value="add_group">
        <button type="submit" class="button-primary" name="eme_admin_action" value="add_group"><?php esc_html_e( 'Add group', 'events-made-easy' ); ?></button>
        <button type="submit" class="button-primary" name="eme_admin_action" value="add_dynamic_people_group"><?php esc_html_e( 'Add dynamic group of people', 'events-made-easy' ); ?></button>
        <button type="submit" class="button-primary" name="eme_admin_action" value="add_dynamic_members_group"><?php esc_html_e( 'Add dynamic group of members', 'events-made-easy' ); ?></button>
    </form>
    </div>
<?php endif; ?>

    <h1><?php esc_html_e( 'Manage groups', 'events-made-easy' ); ?></h1>

    <div id="bulkactions">
    <form id='groups-form' action="#" method="post">
    <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <?php if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) : ?>
    <option value="deleteGroups"><?php esc_html_e( 'Delete selected groups', 'events-made-easy' ); ?></option>
<?php endif; ?>
    </select>
    <button id="GroupsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="GroupsTableContainer"></div>
    </div>
    </div>
<?php
}

function eme_person_replace_image_input_div( $person, $relative_div = 0 ) {
    wp_enqueue_media();
    if ( $person['properties']['image_id'] > 0 ) {
        $image_url = esc_url( wp_get_attachment_image_url( $person['properties']['image_id'], 'full' ) );
    } else {
        # to prevent html validation errors, use a transparent small pixel
        $image_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    }
    $no_image     = esc_html__( 'No image set', 'events-made-easy' );
    $set_image    = esc_html__( 'Choose image', 'events-made-easy' );
    $unset_image  = esc_html__( 'Remove image', 'events-made-easy' );
    $person_image = esc_html__( 'Person image', 'events-made-easy' );
    if ( $relative_div == 1 ) {
        $div_class         = 'div_person_image_relative';
        $person_image_bold = '';
    } else {
        $div_class         = 'div_person_image';
        $person_image_bold = "<b>$person_image</b>";
    }
    $output = "
<div id='{$div_class}'>
      <br>{$person_image_bold}
   <div id='eme_person_no_image' class='postarea'>
      {$no_image}
   </div>
   <div id='eme_person_current_image' class='postarea'>
   <img id='eme_person_image_example' alt='{$person_image}' title='{$person_image}' src='$image_url'>
   <input type='hidden' name='properties[image_id]' id='eme_person_image_id' value='{$person['properties']['image_id']}'>
   </div>
   <br>

   <div class='uploader'>
   <input type='button' name='image_button' id='eme_person_image_button' value='{$set_image}' class='button-secondary'>
   <input type='button' id='eme_person_remove_old_image' name='remove_old_image' value='{$unset_image}' class='button-secondary'>
   </div>
</div>
";
    return $output;
}

function eme_person_replace_image_input( $person, $relative_div = 0 ) {
    wp_enqueue_media();
    if ( $person['properties']['image_id'] > 0 ) {
        $image_url = esc_url( wp_get_attachment_image_url( $person['properties']['image_id'], 'full' ) );
    } else {
        # to prevent html validation errors, use a transparent small pixel
        $image_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    }
    $no_image     = esc_html__( 'No image set', 'events-made-easy' );
    $set_image    = esc_html__( 'Choose image', 'events-made-easy' );
    $unset_image  = esc_html__( 'Remove image', 'events-made-easy' );
    $person_image = esc_html__( 'Person image', 'events-made-easy' );
    $output = "
      <b>{$person_image}</b><br>
   <span id='eme_person_no_image' class='postarea'>
      {$no_image}
   </span>
   <span id='eme_person_current_image' class='postarea'>
   <img id='eme_person_image_example' alt='{$person_image}' title='{$person_image}' src='$image_url'>
   <input type='hidden' name='properties[image_id]' id='eme_person_image_id' value='{$person['properties']['image_id']}'>
   </span>
   <br>

   <input type='button' name='image_button' id='eme_person_image_button' value='{$set_image}' class='button-secondary'>
   <input type='button' id='eme_person_remove_old_image' name='remove_old_image' value='{$unset_image}' class='button-secondary'>
";
    return $output;
}

// API function for people wanting to check if somebody is already registered
function eme_get_person_by_post() {
    if ( isset( $_POST['lastname'] ) && isset( $_POST['email'] ) ) {
        $lastname = eme_sanitize_request( $_POST['lastname'] );
        if ( isset( $_POST['firstname'] ) ) {
            $firstname = eme_sanitize_request( $_POST['firstname'] );
        } else {
            $firstname = '';
        }
        $email = eme_sanitize_email( $_POST['email'] );
        if ( ! eme_is_email_frontend( $email ) ) {
            return false;
        }
        $person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
        return $person;
    } else {
        return false;
    }
}

function eme_count_persons_by_email( $email ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT COUNT(*) FROM $people_table WHERE email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE, $email );
    return $wpdb->get_var( $sql );
}

function eme_get_personids_by_email( $email ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT person_id FROM $people_table WHERE email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE, $email );
    return $wpdb->get_col( $sql );
}

function eme_get_person_by_email( $email ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' LIMIT 1', $email );
    $res     = $wpdb->get_row( $sql, ARRAY_A );
    if ( $res ) {
        $res['properties'] = eme_init_person_props( eme_unserialize( $res['properties'] ) );
    }
    return $res;
}

function eme_get_person_by_email_only( $email ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    // by default this function (eme_get_person_by_email_only) searches for persons with empty name and matching email
    // but if the option eme_unique_email_per_person is set, we search only for matching email
    // this option will get activated once donation has been done
    //if (get_option('eme_unique_email_per_person'))
    //  $sql = $wpdb->prepare("SELECT * FROM $people_table WHERE email = %s AND status=".EME_PEOPLE_STATUS_ACTIVE. " ORDER BY wp_id DESC LIMIT 1",$email);
    //else
    $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE lastname = '' AND firstname = '' AND email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' ORDER BY wp_id DESC LIMIT 1', $email );
    $res     = $wpdb->get_row( $sql, ARRAY_A );
    if ( $res ) {
        $res['properties'] = eme_init_person_props( eme_unserialize( $res['properties'] ) );
    }
    return $res;
}

function eme_get_person_by_name_and_email( $lastname, $firstname, $email, $skip_personid=0 ) {
    // INFO: database searches are case insensitive
    // we order by "wp_id DESC" so if someone matches with and without wp_id, the one with wp_id wins
    // we also search for lastname+firstname in the wrong order (if someone missed and switched last/firstname)
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if (!empty($skip_personid)) {
        $extra_sql = "person_id != ".intval($skip_personid). " AND";
    } else {
        $extra_sql = "";
    }
    if ( ! empty( $firstname ) ) {
        $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE $extra_sql ((lastname = %s AND firstname = %s) OR (firstname = %s AND lastname = %s)) AND email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' ORDER BY wp_id DESC', $lastname, $firstname, $lastname, $firstname, $email );
    } else {
        $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE $extra_sql lastname = %s AND email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' ORDER BY wp_id DESC', $lastname, $email );
    }
    $res = $wpdb->get_row( $sql, ARRAY_A );
    if ( ! $res && get_option( 'eme_rsvp_check_without_accents' ) ) {
        if ( ! empty( $firstname ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE $extra_sql ((lastname = %s AND firstname = %s) OR (firstname = %s AND lastname = %s)) AND email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' ORDER BY wp_id DESC', remove_accents( $lastname ), remove_accents( $firstname ), remove_accents( $lastname ), remove_accents( $firstname ), $email );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM $people_table WHERE $extra_sql lastname = %s AND email = %s AND status=" . EME_PEOPLE_STATUS_ACTIVE . ' ORDER BY wp_id DESC', remove_accents( $lastname ), $email );
        }
        $res = $wpdb->get_row( $sql, ARRAY_A );
    }
    if ( $res ) {
        $res['properties'] = eme_init_person_props( eme_unserialize( $res['properties'] ) );
    }
    return $res;
}

function eme_get_personid_by_wpid( $wp_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT person_id FROM $people_table WHERE wp_id = %d LIMIT 1", $wp_id );
    return intval( $wpdb->get_var( $sql ) );
}
function eme_get_wpid_by_personid( $person_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT wp_id FROM $people_table WHERE person_id = %d", $person_id );
    return intval( $wpdb->get_var( $sql ) );
}
function eme_get_used_wpids( $exclude_id = 0 ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( ! empty( $exclude_id ) ) {
        $sql = $wpdb->prepare( "SELECT DISTINCT wp_id FROM $people_table WHERE wp_id <> %d", $exclude_id );
    } else {
        $sql = "SELECT DISTINCT wp_id FROM $people_table";
    }
    return $wpdb->get_col( $sql );
}

function eme_find_persons_double_name_email() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare("SELECT GROUP_CONCAT(person_id) as person_ids, lastname,firstname,email FROM $people_table WHERE status=%d GROUP BY lastname,firstname,email HAVING COUNT(*)>1", EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_find_persons_double_email() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare("SELECT GROUP_CONCAT(person_id) as person_ids, lastname,firstname,email FROM $people_table WHERE status=%d GROUP BY email HAVING COUNT(*)>1", EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_find_persons_double_wp() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql = $wpdb->prepare("SELECT GROUP_CONCAT(person_id) as person_ids, wp_id,lastname,firstname,email FROM $people_table WHERE status= %d AND wp_id>0 AND wp_id IS NOT NULL GROUP BY wp_id HAVING COUNT(*)>1", EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_count_persons_with_wp_id( $wp_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT COUNT(*) FROM $people_table WHERE wp_id = %d AND status= %d ", $wp_id, EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_var( $sql );
}

function eme_get_person_by_wp_id( $wp_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $user_info    = get_userdata( $wp_id );
    if (!$user_info ) {
        return false;
    }
    $lastname     = $user_info->user_lastname;
    $firstname    = $user_info->user_firstname;
    $email        = $user_info->user_email;
    if ( empty( $lastname ) ) {
        $lastname = $user_info->display_name;
    }

    $person = wp_cache_get( "eme_person_wpid $wp_id" );
    if ( $person === false ) {
        $sql   = $wpdb->prepare( "SELECT * FROM $people_table WHERE wp_id = %d AND status=%d", $wp_id, EME_PEOPLE_STATUS_ACTIVE);
        $lines = $wpdb->get_results( $sql, ARRAY_A );
    } else {
        return $person;
    }
    // if there's more than 1 person with the same wp_id, don't return anything since we might be returning the wrong person if we would use "LIMIT 1" in the sql
    if ( count( $lines ) > 1 ) {
        return false;
    }
    if ( count( $lines ) == 1 ) {
        $person = $lines[0];
        // we use the lastname from the wp profile if that is not empty
        // if that is empty, we use the info from the person
        // if that is still empty, we use the display_name
        if ( ! empty( $lastname ) ) {
            $person['lastname'] = $lastname;
        }
        if ( ! empty( $firstname ) ) {
            $person['firstname'] = $firstname;
        }
        $person['email'] = $email;
        $person['properties'] = eme_init_person_props( eme_unserialize( $person['properties'] ) );
    } else {
        // imagine there is no user yet, but someone matching with this info (lastname, firstname, email), then we add the wp id to that existing user
        $person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
        if ( ! $person ) {
            $person = eme_get_person_by_email_only( $email );
        }
        if ( ! empty( $person ) ) {
            $res = eme_update_person_wp_id( $person['person_id'], $wp_id );
            wp_cache_delete( "eme_person_wpid $wp_id" );
            if ( $res !== false ) {
                $person['wp_id'] = $wp_id;
            }
        }
    }
    wp_cache_set( "eme_person_wpid $wp_id", $person, '', 10 );
    return $person;
}

function eme_fake_person_by_wp_id( $wp_id ) {
    global $wpdb;
    $user_info    = get_userdata( $wp_id );
    $person = eme_new_person();
    if ($user_info ) {
        $lastname     = $user_info->user_lastname;
        if ( empty( $lastname ) ) {
            $lastname = $user_info->display_name;
        }
        $person['lastname'] = $lastname;
        $person['firstname'] = $user_info->user_firstname;
        $person['email'] = $user_info->user_email;
        $person['phone'] = eme_get_user_phone( $wp_id );
        $person['wp_id'] = $wp_id;
    }
    return $person;
}

function eme_get_person_by_randomid( $random_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT * FROM $people_table WHERE random_id = %s LIMIT 1", $random_id );
    $person       = $wpdb->get_row( $sql, ARRAY_A );
    if ( $person ) {
        $person['properties'] = eme_init_person_props( eme_unserialize( $person['properties'] ) );
    }
    return $person;
}

function eme_get_person( $person_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "SELECT * FROM $people_table WHERE person_id = %d LIMIT 1", $person_id );
    $person       = $wpdb->get_row( $sql, ARRAY_A );
    if ( $person ) {
        $person['properties'] = eme_init_person_props( eme_unserialize( $person['properties'] ) );
    }
    return $person;
}

function eme_person_get_status( $person_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;

    $sql   = $wpdb->prepare( "SELECT status FROM $table WHERE person_id=%d", $person_id );
    return $wpdb->get_var( $sql );
}

function eme_trash_people( $person_ids ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( ! eme_is_list_of_int( $person_ids ) ) {
        return;
    }
    $ids_arr = explode(',',$person_ids);
    foreach ( $ids_arr as $person_id ) {
        $person = eme_get_person( $person_id );
        do_action( 'eme_trash_person_action', $person );
        // now delete the wp user if desired and not an admin
        if ($person['properties']['wp_delete_user'] && !empty($person['wp_id'])) {
            $user = get_userdata($person['wp_id']);
            if (!empty($user) && !in_array('administrator',$user->roles )) {
                wp_delete_user($person['wp_id']);
            }
        }
    }
    eme_trash_person_bookings_future_events( $person_ids );
    eme_delete_person_memberships( $person_ids );
    eme_delete_person_groups( $person_ids );
    $modif_date = current_time( 'mysql', false );
    $sql = $wpdb->prepare("UPDATE $people_table SET status=%d, modif_date=%s, wp_id=0 WHERE person_id IN ($person_ids)", EME_PEOPLE_STATUS_TRASH,$modif_date);
    $wpdb->query( $sql );
    // break the family relationship
    $sql = "UPDATE $people_table SET related_person_id=0 WHERE related_person_id IN ($person_ids)";
    $wpdb->query( $sql );
}

function eme_gdpr_trash_people( $person_ids ) {
    // we keep the bookings, so we can keep track of past events
    //eme_delete_person_bookings($ids);
    if ( ! eme_is_list_of_int( $person_ids ) ) {
        return;
    }
    $ids_arr = explode( ',', $person_ids );
    if ( has_action( 'eme_trash_person_action' ) ) {
        foreach ( $ids_arr as $person_id ) {
            $person = eme_get_person( $person_id );
            do_action( 'eme_trash_person_action', $person );
        }
    }
    eme_trash_person_bookings_future_events( $person_ids );
    eme_delete_person_answers( $person_ids );
    eme_delete_person_memberships( $person_ids );
    eme_delete_person_groups( $person_ids );
    $new_person = eme_new_person();
    foreach ( $ids_arr as $person_id ) {
        $new_person['lastname']   = "GDPR deleted $person_id";
        $new_person['firstname']  = "GDPR deleted $person_id";
        $new_person['email']      = "GDPR deleted $person_id";
        $new_person['status']     = EME_PEOPLE_STATUS_TRASH; // this moves the person to the trash too
        $new_person['massmail']   = 0;
        $new_person['newsletter'] = 0;
        $new_person['gdpr']       = 0;
        eme_db_update_person( $person_id, $new_person );
    }
}

// for CRON
function eme_people_birthday_emails() {
    global $wpdb;

    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $members_table    = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $year_month_day   = $eme_date_obj_now->format( 'Y-m-d' );
    $month_day        = $eme_date_obj_now->format( 'm-d' );
    $month            = $eme_date_obj_now->format( 'm' );
    // let's do the leap year logic outside the db
    if ( get_option( 'eme_bd_email_members_only' ) ) {
        $join          = "LEFT JOIN $members_table ON $people_table.person_id=$members_table.person_id";
        $status_active = EME_MEMBER_STATUS_ACTIVE;
        $status_grace  = EME_MEMBER_STATUS_GRACE;
        $members_only  = " AND $members_table.status IN ($status_active,$status_grace)";
    } else {
        $join         = '';
        $members_only = '';
    }

    if ( $month == '02' ) {
        // in feb: we check if someones birthday is now
        // but also the special case: if someone was born on 02-29
        // 	we check if the current day of the month is the last day of the month
        // 	so if this year the last day of feb is 02-28, we also take those with 02-29 along
        $sql = "SELECT DISTINCT $people_table.person_id FROM $people_table $join
            WHERE 
            $people_table.bd_email=1
            AND ( DATE_FORMAT(birthdate,'%m-%d') = '$month_day'
            OR (DATE_FORMAT(birthdate,'%m-%d') = '02-29' AND LAST_DAY($year_month_day) = '$year_month_day'))
            AND $people_table.status=" . EME_PEOPLE_STATUS_ACTIVE . "
            $members_only";
    } else {
        $sql = "SELECT DISTINCT $people_table.person_id FROM $people_table $join
            WHERE 
            $people_table.bd_email=1
            AND DATE_FORMAT(birthdate,'%m-%d') = '$month_day'
            AND $people_table.status=" . EME_PEOPLE_STATUS_ACTIVE . "
            $members_only";
    }
    $person_ids = $wpdb->get_col( $sql );

    $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';

    [$contact_name, $contact_email] = eme_get_default_mailer_info();

    $subject_template = get_option( 'eme_bd_email_subject' );
    $body_template    = eme_translate( get_option( 'eme_bd_email_body' ) );
    foreach ( $person_ids as $person_id ) {
        $person    = eme_get_person( $person_id );
        $subject   = eme_replace_people_placeholders( $subject_template, $person, 'text' );
        $body      = eme_replace_people_placeholders( $body_template, $person, $mail_text_html );
        $full_name = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
        eme_queue_mail( $subject, $body, $contact_email, $contact_name, $person['email'], $full_name, $contact_email, $contact_name );
    }
}

function eme_untrash_people( $person_ids ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql = $wpdb->prepare( "UPDATE $people_table SET status=%d WHERE person_id IN ($person_ids)", EME_PEOPLE_STATUS_ACTIVE);
        $wpdb->query( $sql );
    }
}

function eme_add_personid_to_newsletter( $person_id ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare( "UPDATE $people_table SET newsletter=1 WHERE person_id=%d", $person_id );
    $sql_res      = $wpdb->query( $sql );
    if ( $sql_res === false ) {
        return false;
    } else {
        return true;
    }
}
function eme_remove_email_from_newsletter( $email ) {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $where = [
	    'email' => $email,
    ];
    $fields = [
	    'newsletter' => 0,
    ];

    return $wpdb->update( $people_table, $fields, $where );
}

function eme_delete_people( $person_ids ) {
    global $wpdb;
    // we call all delete functions here, even if not needed (delete only happens after thrash and when trashing we already delte the relevant memberships and groups for that person)
    // this way this function can be called from everywhere without needing to know what to clean up
    if ( has_action( 'eme_delete_person_action' ) ) {
        foreach ( $ids_arr as $person_id ) {
            $person = eme_get_person( $person_id );
            do_action( 'eme_delete_person_action', $person );
        }
    }
    eme_delete_person_bookings( $person_ids );
    eme_delete_person_answers( $person_ids );
    eme_delete_person_memberships( $person_ids );
    eme_delete_person_groups( $person_ids );
    eme_delete_person_attendances( $person_ids );
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $wpdb->query( "DELETE FROM $people_table WHERE person_id IN ($person_ids)");
        $ids_arr   = explode( ',', $person_ids );
        foreach ( $ids_arr as $person_id ) {
            eme_delete_uploaded_files( $person_id, 'people' );
        }
    }
}

function eme_get_group( $group_id ) {
    global $wpdb;
    $groups_table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql          = $wpdb->prepare( "SELECT * FROM $groups_table WHERE group_id = %d", $group_id );
    $res          = $wpdb->get_row( $sql, ARRAY_A );
    if ( $res !== false && ! empty( $res ) && ! empty( $res['search_terms'] ) ) {
        $res['search_terms'] = eme_unserialize( $res['search_terms'] );
    }
    return $res;
}

function eme_get_group_by_name( $name ) {
    global $wpdb;
    $groups_table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql          = $wpdb->prepare( "SELECT * FROM $groups_table WHERE name = %s LIMIT 1", $name );
    $res          = $wpdb->get_row( $sql, ARRAY_A );
    if ( $res !== false && ! empty( $res ) && ! empty( $res['search_terms'] ) ) {
        $res['search_terms'] = eme_unserialize( $res['search_terms'] );
    }
    return $res;
}

function eme_get_group_by_email( $email ) {
    global $wpdb;
    $groups_table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql          = $wpdb->prepare( "SELECT * FROM $groups_table WHERE email = %s LIMIT 1", $email );
    $res          = $wpdb->get_row( $sql, ARRAY_A );
    if ( $res !== false && ! empty( $res ) && ! empty( $res['search_terms'] ) ) {
        $res['search_terms'] = eme_unserialize( $res['search_terms'] );
    }
    return $res;
}

function eme_get_group_name( $group_id ) {
    global $wpdb;
    $groups_table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql          = $wpdb->prepare( "SELECT name FROM $groups_table WHERE group_id = %d", $group_id );
    $result       = $wpdb->get_var( $sql );
    return $result;
}

function eme_get_groups() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql   = "SELECT * FROM $table ORDER BY name";
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_subscribable_groups( $group_ids = '' ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    if ( !empty( $group_ids ) && eme_is_list_of_int( $group_ids ) ) {
        $sql = "SELECT * FROM $table WHERE public=1 AND type='static' AND group_id IN ($group_ids) ORDER BY name";
    } else {
        $sql = "SELECT * FROM $table WHERE public=1 AND type='static' ORDER BY name";
    }
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_subscribable_groupids() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql   = "SELECT group_id FROM $table WHERE public=1 AND type='static' ORDER BY name";
    return $wpdb->get_col( $sql );
}

function eme_get_membergroups() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql   = "SELECT * FROM $table WHERE type='dynamic_members' ORDER BY name";
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_get_static_groups() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $sql   = "SELECT * FROM $table WHERE type = 'static' ORDER BY name";
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_groups_exists( $ids_arr ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    if ( eme_is_numeric_array( $ids_arr ) ) {
        $ids_list = implode(',', $ids_arr);
        return $wpdb->get_col( "SELECT DISTINCT group_id FROM $table WHERE group_id IN ($ids_list)" );
    } else {
        return false;
    }
}

function eme_get_persongroup_ids( $person_id, $wp_id = 0 ) {
    global $wpdb;
    $table        = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( $wp_id ) {
        $sql = $wpdb->prepare( "SELECT DISTINCT group_id FROM $table WHERE person_id IN (SELECT person_id FROM $people_table WHERE wp_id=%d)", $wp_id );
    } else {
        $sql = $wpdb->prepare( "SELECT group_id FROM $table WHERE person_id = %d", $person_id );
    }
    return $wpdb->get_col( $sql );
}

function eme_get_persongroup_names( $person_id, $wp_id = 0 ) {
    global $wpdb;
    $table        = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $groups_table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( $wp_id ) {
        $sql = $wpdb->prepare( "SELECT DISTINCT $groups_table.name FROM $table ,$groups_table WHERE $table.person_id IN (SELECT person_id FROM $people_table WHERE wp_id=%d) AND $table.group_id=$groups_table.group_id", $wp_id );
    } else {
        $sql = $wpdb->prepare( "SELECT DISTINCT $groups_table.name FROM $table,$groups_table WHERE $table.person_id = %d AND $table.group_id=$groups_table.group_id", $person_id );
    }
    return $wpdb->get_col( $sql );
}

function eme_get_grouppersons( $group_ids, $order = 'ASC' ) {
    if ( ! eme_is_list_of_int( $group_ids ) ) {
        return;
    }
    $person_ids_arr = eme_get_groups_person_ids( $group_ids );
    // eme_get_persons returns all people if all 3 first args are empty, and of course that's not what we want here
    // so we return an empty result if $person_ids_arr is empty
    if ( ! empty( $person_ids_arr ) ) {
        return eme_get_persons( $person_ids_arr, '', '', $order );
    } else {
        return;
    }
}

function eme_add_persongroups( $person_id, $group_ids, $public = 0 ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    if ( empty( $group_ids ) ) {
        return;
    }
    $current_group_ids = eme_get_persongroup_ids( $person_id );
    // make sure it is an array
    if ( ! is_array( $group_ids ) ) {
        $group_ids = [ $group_ids ];
    }

    $res = true;
    foreach ( $group_ids as $t_group ) {
        // -1 is the newsletter
        if ( is_numeric( $t_group ) && $t_group == -1 ) {
            $res = eme_add_personid_to_newsletter( $person_id );
            continue;
        }
        if ( is_numeric( $t_group ) ) {
            $group = eme_get_group( $t_group );
        } else {
            $group = eme_get_group_by_name( $t_group );
        }
        if ( ! empty( $group ) ) {
            if ( $public && empty( $group['public'] ) ) {
                continue; // the continue-statement continues the higher foreach-loop
            }
            if ( ! in_array( $group['group_id'], $current_group_ids ) ) {
                $sql     = $wpdb->prepare( "INSERT INTO $table (person_id,group_id) VALUES (%d,%d)", $person_id, $group['group_id'] );
                $sql_res = $wpdb->query( $sql );
                if ( $sql_res === false ) {
                    $res = false;
                }
            }
        } else {
            $res = false;
        }
    }
    return $res;
}

function eme_get_personid_by_email_in_groups( $email, $group_ids ) {
    global $wpdb;
    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    if ( empty( $group_ids ) ) {
        $sql = $wpdb->prepare( "SELECT p.person_id FROM $people_table p WHERE p.email=%s LIMIT 1", $email );
    } else {
        if ( eme_is_list_of_int( $group_ids ) ) {
            $sql = $wpdb->prepare( "SELECT p.person_id FROM $people_table p LEFT JOIN $usergroups_table u ON u.person_id=p.person_id WHERE p.email=%s AND u.group_id IN ($group_ids) LIMIT 1", $email );
        } else {
            return 0;
        }
    }
    $person_id = $wpdb->get_var( $sql );

    // -1 is the newsletter, a special "group"
    if ( empty( $person_id ) && in_array( '-1', explode( ',', $group_ids ) ) ) {
        $sql       = $wpdb->prepare( "SELECT p.person_id FROM $people_table p WHERE p.email=%s AND p.newsletter=1 LIMIT 1", $email );
        $person_id = $wpdb->get_var( $sql );
    }
    return $person_id;
}

function eme_delete_email_from_group( $email, $group_id ) {
    global $wpdb;
    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $sql              = $wpdb->prepare( "DELETE FROM $usergroups_table WHERE group_id=%d AND person_id IN (SELECT person_id FROM $people_table WHERE email=%s)", $group_id, $email );
    return $wpdb->query( $sql );
}

function eme_delete_person_from_group( $person_id, $group_id ) {
    global $wpdb;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $sql              = $wpdb->prepare( "DELETE FROM $usergroups_table WHERE group_id=%d AND person_id=%d", $group_id, $person_id );
    $wpdb->query( $sql );
}

function eme_update_persongroups( $person_id, $group_ids ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $sql   = $wpdb->prepare( "DELETE from $table WHERE person_id = %d", $person_id );
    $wpdb->query( $sql );
    foreach ( $group_ids as $group_id ) {
        $sql = $wpdb->prepare( "INSERT INTO $table (person_id,group_id) VALUES (%d,%d)", $person_id, $group_id );
        $wpdb->query( $sql );
    }
}

function eme_update_grouppersons( $group_id, $person_ids ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;

    // Get current person IDs in the group
    $current_person_ids = $wpdb->get_col(
        $wpdb->prepare( "SELECT person_id FROM $table WHERE group_id = %d", $group_id )
    );

    // Convert to arrays for easier comparison
    $current_ids = array_map('intval', $current_person_ids);
    $new_ids = array_map('intval', $person_ids);

    // Find IDs to add and remove
    $ids_to_add = array_diff($new_ids, $current_ids);
    $ids_to_remove = array_diff($current_ids, $new_ids);

    // Remove people no longer in the group
    if (!empty($ids_to_remove)) {
        $placeholders = implode(',', array_fill(0, count($ids_to_remove), '%d'));
        $sql = $wpdb->prepare(
            "DELETE FROM $table WHERE group_id = %d AND person_id IN ($placeholders)",
            array_merge([$group_id], $ids_to_remove)
        );
        $wpdb->query($sql);
    }

    // Add new people to the group
    if (!empty($ids_to_add)) {
        $values = [];
        $placeholders = [];
        foreach ($ids_to_add as $person_id) {
            $values[] = $person_id;
            $values[] = $group_id;
            $placeholders[] = '(%d,%d)';
        }

        $sql = $wpdb->prepare(
            "INSERT INTO $table (person_id, group_id) VALUES " . implode(',', $placeholders),
            $values
        );
        $wpdb->query($sql);
    }
}

function eme_delete_group( $group_id ) {
    global $wpdb;
    $groups_table     = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $sql              = $wpdb->prepare( "DELETE FROM $groups_table WHERE group_id = %d", $group_id );
    $wpdb->query( $sql );
    $sql = $wpdb->prepare( "DELETE FROM $usergroups_table WHERE group_id = %d", $group_id );
    $wpdb->query( $sql );
}

function eme_delete_groups( $group_ids ) {
    global $wpdb;
    $groups_table     = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    if ( eme_is_list_of_int( $group_ids ) ) {
        $wpdb->query("DELETE FROM $groups_table WHERE group_id IN ($group_ids)" );
        $wpdb->query("DELETE FROM $usergroups_table WHERE group_id IN ($group_ids)" );
    }
}

function eme_get_persons( $person_ids = '', $extra_search = '', $limit = '', $order = 'ASC' ) {
    global $wpdb;
    $people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;

    $where       = '';
    $where_arr   = [];
    $where_arr[] = 'status=' . EME_PEOPLE_STATUS_ACTIVE;
    if ( ! empty( $person_ids ) && eme_is_numeric_array( $person_ids ) ) {
        $tmp_ids     = join( ',', $person_ids );
        $where_arr[] = "person_id IN ($tmp_ids)";
    }
    if ( ! empty( $extra_search ) ) {
        $where_arr[] = $extra_search;
    }
    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    $orderby                = '';
    $order_on_custom_fields = 0;
    if ( $order == 'ASC' || $order == 'DESC' ) {
        // let's try to order as the full name dictates
        $name_format = get_option( 'eme_full_name_format' );
        if ( strpos( $name_format, '#_LASTNAME' ) > strpos( $name_format, '#_FIRSTNAME' ) ) {
            $orderby = "ORDER BY firstname $order,lastname $order,person_id $order";
        } else {
            $orderby = "ORDER BY lastname $order,firstname $order,person_id $order";
        }
    } elseif ( ! eme_is_empty_string( $order ) && preg_match( '/^[\w_\-\, ]+$/', $order ) ) {
        $order_arr = [];
        if ( preg_match( '/^[\w_\-\, ]+$/', $order ) ) {
            $order_tmp_arr = explode( ',', $order );
            foreach ( $order_tmp_arr as $order_ell ) {
                $asc_desc = 'ASC';
                if ( preg_match( '/DESC$/', $order_ell ) ) {
                    $asc_desc = 'DESC';
                }
                // if ordering on a custom field is requested, set a var indicating that
                if ( preg_match( '/FIELD_\d+/', $order_ell ) ) {
                    $order_on_custom_fields = 1;
                }
                $order_ell   = trim( preg_replace( '/ASC$|DESC$|\s/', '', $order_ell ) );
                $order_arr[] = "$order_ell $asc_desc";
            }
        }
        if ( ! empty( $order_arr ) ) {
            $orderby = 'ORDER BY ' . join( ', ', $order_arr );
        } else {
            $orderby = 'ORDER BY lastname ASC,firstname ASC,person_id ASC';
        }
    }

    // if ordering on a custom field is requested, load in that custom field too
    if ( $order_on_custom_fields == 1 ) {
        $formfields_searchable = eme_get_searchable_formfields( 'people' );
        // we need this GROUP_CONCAT so we can sort on those fields too (otherwise the columns FIELD_* don't exist in the returning sql
        $group_concat_sql = '';
        foreach ( $formfields_searchable as $formfield ) {
            $field_id          = $formfield['field_id'];
            $group_concat_sql .= "GROUP_CONCAT(CASE WHEN field_id = $field_id THEN answer END) AS 'FIELD_$field_id',";
        }

        $sql_join = "
           LEFT JOIN (SELECT $group_concat_sql related_id FROM $answers_table
             WHERE related_id>0 AND type='person'
             GROUP BY related_id
            ) ans
           ON $people_table.person_id=ans.related_id";
    } else {
        $sql_join = '';
    }

    $sql = "SELECT * FROM $people_table $sql_join $where $orderby $limit";

    $persons = $wpdb->get_results( $sql, ARRAY_A );
    foreach ( $persons as $key => $person ) {
        $person['properties'] = eme_init_person_props( eme_unserialize( $person['properties'] ) );
        $persons[ $key ]      = $person;
    }
    return $persons;
}

function eme_get_allmail_person_ids() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = "SELECT person_id FROM $people_table WHERE status=" . EME_PEOPLE_STATUS_ACTIVE . " AND email<>'' GROUP BY email";
    return $wpdb->get_col( $sql );
}

function eme_get_newsletter_person_ids() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare("SELECT person_id FROM $people_table WHERE status=%d AND massmail=1 AND newsletter=1 AND email<>'' GROUP BY email", EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_col( $sql );
}

function eme_get_massmail_person_ids() {
    global $wpdb;
    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql          = $wpdb->prepare("SELECT person_id FROM $people_table WHERE status=%d AND massmail=1 AND email<>'' GROUP BY email", EME_PEOPLE_STATUS_ACTIVE);
    return $wpdb->get_col( $sql );
}

function eme_get_groups_person_emails( $group_ids, $massmail_only=1 ) {
    global $wpdb;
    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $groups_table     = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    if ( ! eme_is_list_of_int( $group_ids ) ) {
        return;
    }
    $sql              = "SELECT group_id FROM $groups_table WHERE group_id IN ($group_ids) AND type = 'static'";
    $static_groupids  = $wpdb->get_col( $sql );

    if ($massmail_only) {
        $and_massmail_sql = "AND people.massmail=1";
        $massmail_sql = "people.massmail=1";
    } else {
        $and_massmail_sql = "";
        $massmail_sql = "";
    }

    // for static groups we look at the massmail option, for dynamic groups not
    $res = [];
    if ( ! empty( $static_groupids ) && eme_is_numeric_array( $static_groupids ) ) {
        $ids_list = implode(',', $static_groupids);
        $sql = $wpdb->prepare("SELECT people.lastname, people.firstname, people.email FROM $people_table AS people LEFT JOIN $usergroups_table AS ugroups ON people.person_id=ugroups.person_id WHERE people.status=%d $and_massmail_sql AND people.email<>'' AND ugroups.group_id IN ($ids_list) GROUP BY people.email", EME_PEOPLE_STATUS_ACTIVE);
        $res     = $wpdb->get_results( $sql, ARRAY_A );
    }
    $emails_seen = [];
    foreach ( $res as $entry ) {
        $email = $entry['email'];
        if ( ! empty( $email ) ) {
            $emails_seen[ $email ] = 1;
        }
    }

    $sql            = "SELECT * FROM $groups_table WHERE group_id IN ($group_ids) AND (type = 'dynamic_people' OR type = 'dynamic_members')";
    $dynamic_groups = $wpdb->get_results( $sql, ARRAY_A );
    foreach ( $dynamic_groups as $dynamic_group ) {
        if ( ! empty( $dynamic_group['search_terms'] ) ) {
            $search_terms = eme_unserialize( $dynamic_group['search_terms'] );
            if ( $dynamic_group['type'] == 'dynamic_members' ) {
                $sql = eme_get_sql_members_searchfields( search_terms: $search_terms, emails_only: 1, where_arr: [$massmail_sql] );
            }
            if ( $dynamic_group['type'] == 'dynamic_people' ) {
                $sql = eme_get_sql_people_searchfields( search_terms: $search_terms, emails_only: 1, where_arr: [$massmail_sql] );
            }
        } else {
            $sql = 'SELECT people.lastname, people.firstname, people.email ' . $dynamic_group['stored_sql'] . "  $and_massmail_sql";
        }
        $res2 = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $res2 as $entry ) {
            $email = $entry['email'];
            if ( ! isset( $emails_seen[ $email ] ) ) {
                $res[] = $entry;
            }
        }
    }
    return $res;
}

function eme_get_groups_person_ids( $group_ids, $extra_sql = '' ) {
    global $wpdb;
    $people_table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    $groups_table     = EME_DB_PREFIX . EME_GROUPS_TBNAME;

    // in case $extra_sql is not empty, we'll cache the info so we can reuse it
    if ( ! empty( $extra_sql ) ) {
        $answers = wp_cache_get( "eme_group_person_ids $group_ids $extra_sql" );
        if ( $answers !== false ) {
            return $answers;
        }
    }

    if ( eme_is_list_of_int( $group_ids ) ) {
        $sql = "SELECT group_id FROM $groups_table WHERE group_id IN ($group_ids) AND type = 'static'";
    } else {
        $sql = $wpdb->prepare( "SELECT group_id FROM $groups_table WHERE name = %s AND type = 'static'", $group_ids );
    }
    $static_groupids = $wpdb->get_col( $sql );

    $and_extra_sql = '';
    if ( ! empty( $extra_sql ) ) {
        $and_extra_sql = ' AND ' . $extra_sql;
    }

    if ( ! empty( $static_groupids ) && eme_is_numeric_array($static_groupids)) {
        $ids_list = implode(',', $static_groupids);
        $sql = $wpdb->prepare( "SELECT people.person_id FROM $people_table AS people LEFT JOIN $usergroups_table as ug ON people.person_id=ug.person_id WHERE people.status=%d AND ug.group_id IN ($ids_list) $and_extra_sql", EME_PEOPLE_STATUS_ACTIVE);
        $res = $wpdb->get_col( $sql );
    } else {
        $res = [];
    }

    if ( eme_is_list_of_int( $group_ids ) ) {
        $sql = "SELECT * FROM $groups_table WHERE group_id IN ($group_ids) AND (type = 'dynamic_people' OR type = 'dynamic_members')";
    } else {
        $sql = $wpdb->prepare( "SELECT * FROM $groups_table WHERE name = %s AND (type = 'dynamic_people' OR type = 'dynamic_members')", $group_ids );
    }
    $dynamic_groups = $wpdb->get_results( $sql, ARRAY_A );
    foreach ( $dynamic_groups as $dynamic_group ) {
        if ( ! empty( $dynamic_group['search_terms'] ) ) {
            $search_terms = eme_unserialize( $dynamic_group['search_terms'] );
            if ( $dynamic_group['type'] == 'dynamic_members' ) {
                $sql = eme_get_sql_members_searchfields( search_terms: $search_terms, peopleids_only: 1, where_arr: [$extra_sql] );
            }
            if ( $dynamic_group['type'] == 'dynamic_people' ) {
                $sql = eme_get_sql_people_searchfields( search_terms: $search_terms, ids_only: 1, where_arr: [$extra_sql] );
            }
        } else {
            $sql = 'SELECT people.person_id ' . $dynamic_group['stored_sql'] . $and_extra_sql;
        }
        $res2 = $wpdb->get_col( $sql );
        $res  = array_merge( $res, $res2 );
    }

    $res = array_unique( $res );
    // in case $extra_sql is not empty, we'll cache the info so we can reuse it
    if ( ! empty( $extra_sql ) ) {
        wp_cache_set( "eme_group_person_ids $group_ids $extra_sql", $res, '', 10 );
    }
    return $res;
}

function eme_get_groups_member_ids( $group_ids ) {
    global $wpdb;
    $groups_table   = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    if ( ! eme_is_list_of_int( $group_ids ) ) {
        return false;
    }
    $dynamic_groups = $wpdb->get_results( "SELECT * FROM $groups_table WHERE group_id IN ($group_ids) AND type = 'dynamic_members'", ARRAY_A);
    $res            = [];
    foreach ( $dynamic_groups as $dynamic_group ) {
        if ( ! empty( $dynamic_group['search_terms'] ) ) {
            $search_terms = eme_unserialize( $dynamic_group['search_terms'] );
            $sql          = eme_get_sql_members_searchfields( search_terms: $search_terms, memberids_only: 1 );
        } else {
            $sql = 'SELECT members.member_id ' . $dynamic_group['stored_sql'];
        }
        $res2 = $wpdb->get_col( $sql );
        $res  = array_merge( $res, $res2 );
    }
    return $res;
}

function eme_get_memberships_member_ids( $membership_ids ) {
    global $wpdb;
    $people_table  = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    if ( ! eme_is_list_of_int( $membership_ids ) ) {
        return false;
    }
    $sql = $wpdb->prepare("SELECT members.member_id FROM $people_table AS people LEFT JOIN $members_table AS members ON people.person_id=members.person_id WHERE people.status=%d.AND members.status IN (%d,%d) AND members.membership_id IN ($membership_ids) GROUP BY people.email", EME_PEOPLE_STATUS_ACTIVE,EME_MEMBER_STATUS_ACTIVE,EME_MEMBER_STATUS_GRACE);
    return $wpdb->get_col( $sql );
}

function eme_db_insert_person( $line ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;

    $person = eme_new_person();
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys     = array_intersect_key( $line, $person );
    $new_line = array_merge( $person, $keys );

    if ( has_filter( 'eme_insert_person_filter' ) ) {
        $new_line = apply_filters( 'eme_insert_person_filter', $new_line );
    }

    // some properties validation: only image_id or wp_delete_user as int is allowed
    $props                  = eme_unserialize( $new_line['properties'] );
    $new_line['properties'] = [];
    foreach ( $props as $key => $val ) {
        if ( $key == 'image_id' || $key == 'wp_delete_user' ) {
            $new_line['properties'][ $key ] = intval( $val );
        }
    }
    $new_line['properties'] = eme_serialize( $new_line['properties'] );

    if ( empty( $new_line['creation_date'] ) || ! ( eme_is_date( $new_line['creation_date'] ) || eme_is_datetime( $new_line['creation_date'] ) ) ) {
        $new_line['creation_date'] = current_time( 'mysql', false );
    }
    $new_line['modif_date'] = $new_line['creation_date'];

    // keep the wp-id seperate
    $wp_id = 0;
    if ( isset( $new_line['wp_id'] ) ) {
        $wp_id = $new_line['wp_id'];
        unset( $new_line['wp_id'] );
    }

    $new_line['random_id'] = eme_random_id();
    if ( $wpdb->insert( $table, $new_line ) === false ) {
        return false;
    } else {
        $person_id = $wpdb->insert_id;
        eme_update_person_wp_id( $person_id, $wp_id );
        return $person_id;
    }
}

function eme_db_insert_group( $line ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;

    $group = eme_new_group();
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys     = array_intersect_key( $line, $group );
    $new_line = array_merge( $group, $keys );

    if ( has_filter( 'eme_insert_group_filter' ) ) {
        $new_line = apply_filters( 'eme_insert_group_filter', $new_line );
    }

    if ( $wpdb->insert( $table, $new_line ) === false ) {
        return false;
    } else {
        return $wpdb->insert_id;
    }
}

function eme_db_update_person( $person_id, $line ) {
    global $wpdb;
    $table              = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $where              = [];
    $where['person_id'] = intval( $person_id );

    $person = eme_get_person( $person_id );
    unset( $person['person_id'] );
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys                   = array_intersect_key( $line, $person );
    $new_line               = array_merge( $person, $keys );
    $new_line['properties'] = eme_serialize( $new_line['properties'] );

    // some properties validation: only image_id or wp_delete_user as int is allowed
    $props                  = eme_unserialize( $new_line['properties'] );
    $new_line['properties'] = [];
    foreach ( $props as $key => $val ) {
        if ( $key == 'image_id' || $key == 'wp_delete_user' ) {
            $new_line['properties'][ $key ] = intval( $val );
        }
    }
    $new_line['properties'] = eme_serialize( $new_line['properties'] );

    $new_line['modif_date'] = current_time( 'mysql', false );

    // keep the wp-id seperate
    $wp_id = 0;
    if ( isset( $new_line['wp_id'] ) ) {
        $wp_id = $new_line['wp_id'];
        unset( $new_line['wp_id'] );
    }

    if ( ! empty( $new_line ) && $wpdb->update( $table, $new_line, $where ) === false ) {
        return false;
    } else {
        $res = eme_update_person_wp_id( $person_id, $wp_id );
        if ( $res !== false ) {
            return $person_id;
        } else {
            return false;
        }
    }
}

function eme_db_update_group( $group_id, $line ) {
    global $wpdb;
    $table             = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $where             = [];
    $where['group_id'] = intval( $group_id );

    $group = eme_get_group( $group_id );
    unset( $group['group_id'] );
    // we only want the columns that interest us
    // we need to do this since this function is also called for csv import
    $keys     = array_intersect_key( $line, $group );
    $new_line = array_merge( $group, $keys );

    if ( ! empty( $new_line ) && $wpdb->update( $table, $new_line, $where ) === false ) {
        return false;
    } else {
        return $group_id;
    }
}

function eme_add_update_person_from_backend( $person_id = 0 ) {
    check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
    $person = [];
    if ( isset( $_POST['lastname'] ) ) {
        $person['lastname'] = eme_sanitize_request( $_POST['lastname'] );
    }
    if ( isset( $_POST['firstname'] ) ) {
        $person['firstname'] = eme_sanitize_request( $_POST['firstname'] );
    }
    if ( isset( $_POST['email'] ) ) {
        $person['email'] = eme_sanitize_email( $_POST['email'] );
    }
    if ( isset( $_POST['birthdate'] ) && eme_is_date( $_POST['birthdate'] ) ) {
        $person['birthdate'] = eme_sanitize_request( $_POST['birthdate'] );
    }
    if ( isset( $_POST['birthplace'] ) ) {
        $person['birthplace'] = eme_sanitize_request( $_POST['birthplace'] );
    }
    if ( isset( $_POST['address1'] ) ) {
        $person['address1'] = eme_sanitize_request( $_POST['address1'] );
    }
    if ( isset( $_POST['address2'] ) ) {
        $person['address2'] = eme_sanitize_request( $_POST['address2'] );
    }
    if ( isset( $_POST['city'] ) ) {
        $person['city'] = eme_sanitize_request( $_POST['city'] );
    }
    if ( isset( $_POST['zip'] ) ) {
        $person['zip'] = eme_sanitize_request( $_POST['zip'] );
    }
    if ( isset( $_POST['state_code'] ) ) {
        $person['state_code'] = eme_sanitize_request( $_POST['state_code'] );
    }
    if ( isset( $_POST['country_code'] ) ) {
        $person['country_code'] = eme_sanitize_request( $_POST['country_code'] );
    }
    if ( isset( $_POST['phone'] ) ) {
        $person['phone'] = eme_sanitize_request( $_POST['phone'] );
    }
    // the language POST var has a different name ('language', not 'lang') to avoid a conflict with qtranslate-xt that
    //    also checks for $_POST['lang'] and redirects to that lang, which is of course not the intention when editing a person
    if ( isset( $_POST['language'] ) ) {
        $person['lang'] = eme_sanitize_request( $_POST['language'] );
    }
    if ( isset( $_POST['wp_id'] ) ) {
        $person['wp_id'] = intval( $_POST['wp_id'] );
    }
    if ( isset( $_POST['massmail'] ) ) {
        $person['massmail'] = intval( $_POST['massmail'] );
    } else {
        $person['massmail'] = 0;
    }
    if ( isset( $_POST['newsletter'] ) ) {
        $person['newsletter'] = intval( $_POST['newsletter'] );
    } else {
        $person['newsletter'] = 0;
    }
    if ( isset( $_POST['bd_email'] ) ) {
        $person['bd_email'] = intval( $_POST['bd_email'] );
    }
    if ( isset( $_POST['related_person_id'] ) ) {
        $person['related_person_id'] = intval( $_POST['related_person_id'] );
    }
    if ( isset( $_POST['gdpr'] ) ) {
        $person['gdpr'] = intval( $_POST['gdpr'] );
        if ( $person['gdpr'] ) {
            $person['gdpr_date'] = current_time( 'mysql', false );
        }
    } else {
        $person['gdpr'] = 0;
    }
    if ( isset( $_POST['groups'] ) ) {
        $groups = eme_sanitize_request( $_POST['groups'] );
    } else {
        $groups = [];
    }
    if ( isset( $_POST['properties'] ) ) {
        $person['properties'] = eme_sanitize_request( $_POST['properties'] );
    }

    // if the email is not empty, it needs to be valid
    if ( ! empty( $person['email'] ) && ! eme_is_email( $person['email'] ) ) {
        $failure   = '<p>' . esc_html__( 'Please enter a valid email address', 'events-made-easy' ) . '</p>';
        $person_id = 0;
        $res       = [
            0 => $failure,
            1 => $person_id,
        ];
        return $res;
    }
    $failure = '';
    if ( $person_id ) {
        // first check if some exists by name and email, if so: refuse
        $t_person = eme_get_person_by_name_and_email( $person['lastname'], $person['firstname'], $person['email'], $person_id );
        if ($t_person) {
            $failure   = '<p>' . esc_html__( 'A person with this name and email already exists', 'events-made-easy' ) . '</p>';
            $person_id = 0;
            $res       = [
                0 => $failure,
                1 => $person_id,
            ];
            return $res;
        }

        $updated_personid = eme_db_update_person( $person_id, $person );
        if ( $updated_personid ) {
            eme_update_persongroups( $updated_personid, $groups );
            eme_store_person_answers( $updated_personid, 0, 1 );
            $failure = eme_upload_files( $updated_personid, 'people' );
        }
        $res_id = $updated_personid;
    } else {
        // check existing
        $t_person = eme_get_person_by_name_and_email( $person['lastname'], $person['firstname'], $person['email'] );
        if ( ! $t_person ) {
            $t_person = eme_get_person_by_email_only( $person['email'] );
        }
        if ( $t_person ) {
            $person_id        = $t_person['person_id'];
            $updated_personid = eme_db_update_person( $person_id, $person );
            if ( $updated_personid ) {
                eme_update_persongroups( $updated_personid, $groups );
                eme_store_person_answers( $updated_personid, 0, 1 );
                $failure = eme_upload_files( $updated_personid, 'people' );
            }
            $res_id = $updated_personid;
        } else {
            $person_id = eme_db_insert_person( $person );
            if ( $person_id ) {
                eme_update_persongroups( $person_id, $groups );
                eme_store_person_answers( $person_id, 1, 1 );
                $failure = eme_upload_files( $person_id, 'people' );
            }
            $res_id = $person_id;
        }
    }
    $res = [
        0 => $failure,
        1 => $res_id,
    ];
    return $res;
}

function eme_add_update_group( $group_id = 0 ) {
    global $wpdb;
    check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
    $table = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $group = [];
    if ( isset( $_POST['name'] ) ) {
        $group['name'] = eme_sanitize_request( $_POST['name'] );
    }
    if ( isset( $_POST['description'] ) ) {
        $group['description'] = eme_sanitize_request( $_POST['description'] );
    }
    $group['public'] = isset( $_POST['public'] ) ? intval( $_POST['public'] ) : 0;
    $group['type'] = isset( $_POST['group_type'] ) ? eme_sanitize_request( $_POST['group_type'] ) : 'static';
    $search_terms    = [];
    $search_fields   = [ 'search_membershipids', 'search_memberstatus', 'search_person', 'search_groups', 'search_memberid', 'search_customfields', 'search_customfieldids', 'search_exactmatch' ];
    foreach ( $search_fields as $search_field ) {
        if ( isset( $_POST[ $search_field ] ) ) {
            $search_terms[ $search_field ] = esc_sql( eme_sanitize_request( $_POST[ $search_field ] ) );
        }
    }
    $group['search_terms'] = eme_serialize( $search_terms );

    // let's check if the email is unique
    if ( ! eme_is_empty_string( $_POST['email'] ) && eme_is_email( $_POST['email'] ) ) {
        $email = eme_sanitize_email( $_POST['email'] );
        if ( $group_id ) {
            $sql = $wpdb->prepare( "SELECT COUNT(group_id) from $table WHERE email=%s AND group_id<>%d", $email, $group_id );
        } else {
            $sql = $wpdb->prepare( "SELECT COUNT(group_id) from $table WHERE email=%s", $email );
        }
        $count = $wpdb->get_var( $sql );
        if ( $count > 0 ) {
            return false;
        }
        // all ok, set the email
        $group['email'] = $email;
    }

    if ( $group_id ) {
        $res = eme_db_update_group( $group_id, $group );
        if ( $res ) {
            if ( isset( $_POST['persons'] ) ) {
                $persons = eme_sanitize_request( $_POST['persons'] );
            } else {
                $persons = [];
            }
            eme_update_grouppersons( $group_id, $persons );
        }
        return $res;
    } else {
        $group_id = eme_db_insert_group( $group );
        if ( $group_id ) {
            if ( isset( $_POST['persons'] ) ) {
                $persons = eme_sanitize_request( $_POST['persons'] );
            } else {
                $persons = [];
            }
            eme_update_grouppersons( $group_id, $persons );
        }
        return $group_id;
    }
}

function eme_add_familymember_from_frontend( $main_person_id, $familymember ) {
    $person = [];

    if ( ( ! isset( $_POST['eme_admin_nonce'] ) && ! isset( $_POST['eme_frontend_nonce'] ) ) ||
        ( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), 'eme_admin' ) ) ||
        ( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
        return [
            0 => 0,
            1 => esc_html__( 'Access denied!', 'events-made-easy' ),
        ];
    }

    // lang detection
    $lang = eme_detect_lang();

    $lastname  = $familymember['lastname'];
    $firstname = $familymember['firstname'];
    if ( isset( $familymember['email'] ) ) {
        $email = $familymember['email'];
    } elseif ( ! empty( $_POST['email'] ) ) {
        $email = eme_sanitize_request( $_POST['email'] );
    } else {
        $email = '';
    }
    if ( ! empty( $email ) && ! eme_is_email_frontend( $email ) ) {
        return [
            0 => 0,
            1 => esc_html__( 'Please enter a valid email address', 'events-made-easy' ),
        ];
    }
    // most fields are taken from the main family person
    $country_code = '';
    if ( ! empty( $_POST['country_code'] ) ) {
        $country_code = eme_sanitize_request( $_POST['country_code'] );
        $country_name = eme_get_country_name( $country_code );
        if ( empty( $country_name ) ) {
            return [
                0 => 0,
                1 => esc_html__( 'Invalid country code', 'events-made-easy' ),
            ];
        }
    }
    $state_code = '';
    if ( ! empty( $_POST['state_code'] ) ) {
        $state_code = eme_sanitize_request( $_POST['state_code'] );
        $state_name = eme_get_state_name( $state_code, $country_code, $lang );
        if ( empty( $state_name ) ) {
            return [
                0 => 0,
                1 => esc_html__( 'Invalid state code', 'events-made-easy' ),
            ];
        }
    }

    if ( ! empty( $familymember['birthdate'] ) && eme_is_date( $familymember['birthdate'] ) ) {
        $person['birthdate'] = $familymember['birthdate'];
    }
    if ( ! empty( $familymember['birthplace'] ) ) {
        $person['birthplace'] = $familymember['birthplace'];
    }
    if ( ! empty( $familymember['phone'] ) ) {
        $person['phone'] = $familymember['phone'];
    } elseif ( ! empty( $_POST['phone'] ) ) {
        $person['phone'] = eme_sanitize_request( $_POST['phone'] );
    }
    if ( ! empty( $_POST['address1'] ) ) {
        $person['address1'] = eme_sanitize_request( $_POST['address1'] );
    }
    if ( ! empty( $_POST['address2'] ) ) {
        $person['address2'] = eme_sanitize_request( $_POST['address2'] );
    }
    if ( ! empty( $_POST['city'] ) ) {
        $person['city'] = eme_sanitize_request( $_POST['city'] );
    }
    if ( ! empty( $_POST['zip'] ) ) {
        $person['zip'] = eme_sanitize_request( $_POST['zip'] );
    }
    if ( isset( $familymember['newsletter'] ) ) {
        $person['newsletter'] = intval( $familymember['newsletter'] );
    } elseif ( isset( $_POST['newsletter'] ) ) {
        $person['newsletter'] = intval( $_POST['newsletter'] );
    }
    if ( isset( $familymember['massmail'] ) ) {
        $person['massmail'] = intval( $familymember['massmail'] );
    } elseif ( isset( $_POST['massmail'] ) ) {
        $person['massmail'] = intval( $_POST['massmail'] );
    }
    if ( isset( $familymember['bd_email'] ) ) {
        $person['bd_email'] = intval( $familymember['bd_email'] );
    } elseif ( isset( $_POST['bd_email'] ) ) {
        $person['bd_email'] = intval( $_POST['bd_email'] );
    }
    if ( isset( $_POST['gdpr'] ) ) {
        $person['gdpr']      = intval( $_POST['gdpr'] );
        $person['gdpr_date'] = current_time( 'mysql', false );
    }
    $person['lang']              = $lang;
    $person['state_code']        = $state_code;
    $person['country_code']      = $country_code;
    $person['related_person_id'] = $main_person_id;
    $person['lastname']          = eme_sanitize_request( $lastname );
    $person['firstname']         = eme_sanitize_request( $firstname );
    $person['email']             = $email;

    $t_person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
    if ( ! $person ) {
        $person = eme_get_person_by_email_only( $email );
    }
    // if we have a matching person, update that one. But make sure we"re not updating the main one (can happen if someone entered the main account details also as member)
    if ( $t_person && $t_person['person_id'] != $main_person_id ) {
        $person_id = $t_person['person_id'];
        $res       = eme_db_update_person( $person_id, $person );
        if ( $res ) {
            eme_store_family_answers( $person_id, $familymember );
        }
    } else {
        $person_id = eme_db_insert_person( $person );
        if ( $person_id ) {
            eme_store_family_answers( $person_id, $familymember );
        }
    }
    return $person_id;
}

function eme_add_update_person_from_form( $person_id, $lastname = '', $firstname = '', $email = '', $wp_id = 0, $create_wp_user = 0, $return_fake_person = 0 ) {
    $person = [];

    if ( ! $return_fake_person && ! empty( $email ) && ! eme_is_email_frontend( $email ) ) {
        return [
            0 => 0,
            1 => esc_html__( 'Please enter a valid email address', 'events-made-easy' ),
        ];
    }

    // lang detection
    if ( $person_id ) {
        $person_being_updated = eme_get_person( $person_id );
        if ( ! $person_being_updated ) {
            return [
                0 => 0,
                1 => esc_html__( 'Error encountered while updating person', 'events-made-easy' ),
            ];
        }
        // when a booking is done via the admin backend, take the existing language for that person
        if ( ! empty( $person_being_updated['lang'] ) && eme_is_admin_request() ) {
            $lang = $person_being_updated['lang'];
        } else {
            $lang = eme_detect_lang();
        }
    } else {
        $lang = eme_detect_lang();
    }

    if ( $create_wp_user > 0 && ! eme_is_admin_request() && ! is_user_logged_in() && email_exists( $email ) ) {
        return [
            0 => 0,
            1 => esc_html__( 'The email address belongs to an existing user. Please log in first before continuing to register with this email address.', 'events-made-easy' ),
        ];
    }


    // sanitize all in one go and rename task_ to regular fields
    $post_values = eme_sanitize_request($_POST);
    foreach ($post_values as $key => $value) {
        # If the key name contains 'task_'
        if (strpos($key, 'task_') !== false) {
            # Create a new, renamed, key. Then assign it the value from before
            $post_values[str_replace('task_', '', $key)] = $value;
            # Destroy the old key/value pair
            unset($post_values[$key]);
        }
    }

    // check for correct country value
    // This to take autocomplete field values into account, or people just submitting too fast
    $country_code = '';
    if ( ! empty( $_POST['country_code'] ) ) {
        $country_code = eme_sanitize_request( $_POST['country_code'] );
        $country_name = eme_get_country_name( $country_code );
        if ( empty( $country_name ) ) {
            return [
                0 => 0,
                1 => esc_html__( 'Invalid country code', 'events-made-easy' ),
            ];
        }
    }
    $state_code = '';
    if ( ! empty( $_POST['state_code'] ) ) {
        $state_code = eme_sanitize_request( $_POST['state_code'] );
        $state_name = eme_get_state_name( $state_code, $country_code, $lang );
        if ( empty( $state_name ) ) {
            return [
                0 => 0,
                1 => esc_html__( 'Invalid state code', 'events-made-easy' ),
            ];
        }
    }

    if ( isset( $_POST['birthdate'] ) && eme_is_date( $_POST['birthdate'] ) ) {
        $person['birthdate'] = eme_sanitize_request( $_POST['birthdate'] );
    }
    if ( isset( $_POST['birthplace'] ) ) {
        $person['birthplace'] = eme_sanitize_request( $_POST['birthplace'] );
    }
    if ( ! empty( $_POST['address1'] ) ) {
        $person['address1'] = eme_sanitize_request( $_POST['address1'] );
    }
    if ( ! empty( $_POST['address2'] ) ) {
        $person['address2'] = eme_sanitize_request( $_POST['address2'] );
    }
    if ( ! empty( $_POST['city'] ) ) {
        $person['city'] = eme_sanitize_request( $_POST['city'] );
    }
    if ( ! empty( $_POST['zip'] ) ) {
        $person['zip'] = eme_sanitize_request( $_POST['zip'] );
    }
    if ( isset( $_POST['massmail'] ) ) {
        $person['massmail'] = intval( $_POST['massmail'] );
    }
    if ( isset( $_POST['newsletter'] ) ) {
        $person['newsletter'] = intval( $_POST['newsletter'] );
    }
    if ( isset( $_POST['bd_email'] ) ) {
        $person['bd_email'] = intval( $_POST['bd_email'] );
    }
    if ( isset( $_POST['gdpr'] ) ) {
        $person['gdpr']      = intval( $_POST['gdpr'] );
        $person['gdpr_date'] = current_time( 'mysql', false );
    }
    if ( ! empty( $_POST['phone'] ) ) {
        $person['phone'] = eme_sanitize_request( $_POST['phone'] );
    }
    if ( isset( $_POST['properties'] ) ) {
        $person['properties'] = eme_sanitize_request( $_POST['properties'] );
    }
    $person['state_code']   = $state_code;
    $person['country_code'] = $country_code;
    $person['lang']         = $lang;

    if ( $return_fake_person ) {
        $person['person_id']    = -1;
        $person['wp_id']        = eme_get_wpid_by_post();
        $person['lastname'] = eme_sanitize_request( $_POST['lastname'] );
        if ( isset( $_POST['firstname'] ) ) {
            $person['firstname'] = eme_sanitize_request( $_POST['firstname'] );
        } else {
            $person['firstname'] = '';
        }
        $person['email'] = eme_sanitize_email( $_POST['email'] );
        return $person;
    } elseif ( ! $person_id ) {
        $wp_count = 0;
        if ( $wp_id > 0 ) {
            $wp_count = eme_count_persons_with_wp_id( $wp_id );
        }
        $t_person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
        if ( ! $t_person ) {
            $t_person = eme_get_person_by_email_only( $email );
            // we found a person matching with email only, meaning empty lastname/firstname, so we update it
            // this prevents people from updating their name/email with only a case-difference from the frontend
            if ( $t_person ) {
                $person['lastname']  = eme_sanitize_request( $lastname );
                $person['firstname'] = eme_sanitize_request( $firstname );
            }
        }
        if ( $t_person ) {
            $person_id = $t_person['person_id'];
            if ( $wp_id > 0 && $wp_count == 0 && $t_person['wp_id'] == 0 ) {
                $person['wp_id'] = intval( $wp_id );
            }

            $updated_personid = eme_db_update_person( $person_id, $person );
            if ( $updated_personid ) {
                eme_store_person_answers( $updated_personid );
                if ( ! empty( $_POST['subscribe_groups'] ) ) {
                    eme_add_persongroups( $updated_personid, eme_sanitize_request( $_POST['subscribe_groups'] ) );
                }
                return [
                    0 => $person_id,
                    1 => '',
                ];
            } else {
                return [
                    0 => 0,
                    1 => esc_html__( 'Error encountered while updating person', 'events-made-easy' ),
                ];
            }
        } else {
            $person['lastname']  = eme_sanitize_request( $lastname );
            $person['firstname'] = eme_sanitize_request( $firstname );
            $person['email']     = $email;
            if ( $wp_id > 0 && $wp_count == 0 ) {
                $person['wp_id'] = intval( $wp_id );
            }
            $person_id = eme_db_insert_person( $person );
            if ( $person_id ) {
                eme_store_person_answers( $person_id );
                if ( ! empty( $_POST['subscribe_groups'] ) ) {
                    eme_add_persongroups( $updated_personid, eme_sanitize_request( $_POST['subscribe_groups'] ) );
                }
                return [
                    0 => $person_id,
                    1 => '',
                ];
            } else {
                return [
                    0 => 0,
                    1 => esc_html__( 'Error encountered while adding person', 'events-made-easy' ),
                ];
            }
        }
    } else {
        if ( ! eme_is_empty_string( $_POST['lastname'] ) ) {
            $person['lastname'] = eme_sanitize_request( $_POST['lastname'] );
        } else {
            $person['lastname'] = $person_being_updated['lastname'];
        }
        if ( ! eme_is_empty_string( $_POST['firstname'] ) ) {
            $person['firstname'] = eme_sanitize_request( $_POST['firstname'] );
        } else {
            $person['firstname'] = $person_being_updated['firstname'];
        }
        if ( ! eme_is_empty_string( $_POST['email'] ) ) {
            $person['email'] = eme_sanitize_email( $_POST['email'] );
        } else {
            $person['email'] = $person_being_updated['email'];
        }
        if ( eme_is_empty_string( $person['email'] ) || ! eme_is_email_frontend( $person['email'] ) ) {
            return [
                0 => 0,
                1 => esc_html__( 'Please enter a valid email address', 'events-made-easy' ),
            ];
        }
        // check for conflicts
        $existing_personid = eme_get_person_by_name_and_email( $person['lastname'], $person['firstname'], $person['email'] );
        if ( $existing_personid && $existing_personid['person_id'] != $person_id ) {
            return [
                0 => 0,
                1 => esc_html__( 'Conflict with info from other person, please use another lastname, firstname or email', 'events-made-easy' ),
            ];
        }
        // when updating a person using the person id, we won't change the wp_id (that should happen in the admin interface for the person)
        $person['wp_id']  = $person_being_updated['wp_id'];
        $updated_personid = eme_db_update_person( $person_id, $person );
        if ( $updated_personid ) {
            eme_store_person_answers( $updated_personid );
            if ( ! empty( $_POST['subscribe_groups'] ) ) {
                eme_add_persongroups( $updated_personid, eme_sanitize_request( $_POST['subscribe_groups'] ) );
            }
            return [
                0 => $person_id,
                1 => '',
            ];
        } else {
            return [
                0 => 0,
                1 => esc_html__( 'Error encountered while updating person', 'events-made-easy' ),
            ];
        }
    }
}

function eme_user_profile( $user ) {
    // define a simple template
    $template    = '#_STARTDATE #_STARTTIME: #_EVENTNAME (#_RESPSEATS ' . esc_html__( 'seats', 'events-made-easy' ) . '). #_CANCEL_LINK<br>';
    $person_id   = eme_get_personid_by_wpid( $user->ID );
    $memberships_list = eme_get_activemembership_names_by_personid( $person_id );
?>
    <h3><?php esc_html_e( 'Events Made Easy settings', 'events-made-easy' ); ?></h3>
    <table class='form-table'>
        <tr>
        <th><label for="eme_phone"><?php esc_html_e( 'Phone number', 'events-made-easy' ); ?></label></th>
        <td><input type="text" name="eme_phone" id="eme_phone" value="<?php echo esc_attr( eme_get_user_phone( $user->ID ) ); ?>" class="regular-text"> <br>
        <?php esc_html_e( 'The phone number used by Events Made Easy when the user is indicated as the contact person for an event.', 'events-made-easy' ); ?></td>
        </tr>
        <tr>
        <th><?php esc_html_e( 'Bookings made for future events', 'events-made-easy' ); ?></th>
        <td><?php echo eme_get_bookings_list_for_wp_id( $user->ID, 'future', $template ); ?>
        </tr>
        <tr>
        <th><?php esc_html_e( 'Active memberships', 'events-made-easy' ); ?></th>
        <td><?php echo $memberships_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped memberships HTML ?></td>
        </tr>
    </table>
<?php
}

function eme_update_user_profile( $wp_id ) {
    if ( ! eme_is_empty_string( $_POST['eme_phone'] ) ) {
        eme_update_user_phone( $wp_id, $_POST['eme_phone'] );
    }
}

function eme_after_profile_update( $wp_id, $old_user_data ) {
    // make sure to do it for real wp_ids only
    if ( $wp_id == 0 ) {
        return;
    }
    $user_info = get_userdata( $wp_id );
    $lastname  = $user_info->user_lastname;
    if ( empty( $lastname ) ) {
        $lastname = $user_info->display_name;
    }
    $firstname = $user_info->user_firstname;
    $email     = $user_info->user_email;
    $phone     = eme_get_user_phone( $wp_id );
    $person    = eme_get_person_by_wp_id( $wp_id );
    if ( ! empty( $person ) ) {
        if ( ! empty( $lastname ) ) {
            $person['lastname'] = $lastname;
        }
        if ( ! empty( $firstname ) ) {
            $person['firstname'] = $firstname;
        }
        $person['email'] = $email;
        $person['phone'] = $phone;
        eme_db_update_person( $person['person_id'], $person );
    }
}

function eme_update_person_wp_id( $person_id, $wp_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    // the function wp_dropdown_users uses -1 for an "empty" wp_id, but our db only allows >=0, so lets rectify that
    if ( $wp_id < 0 ) {
        $wp_id = 0;
    }
    wp_cache_delete( "eme_person_wpid $wp_id" );
    // first we check if another person has the wp_id and if that person matches in lastname/firstname/email with the wp user, then moving is not allowed
    if ( $wp_id ) {
        $user_info = get_userdata( $wp_id );
        if ( ! empty( $user_info ) ) {
            $lastname = $user_info->user_lastname;
            if ( empty( $lastname ) ) {
                $lastname = $user_info->display_name;
            }
            $firstname = $user_info->user_firstname;
            $email     = $user_info->user_email;

            // if there is another person matching the wp user info, don't update the current wp id
            $person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
            if ( ! empty( $person ) && ( $person['person_id'] != $person_id && $person['lastname'] == $lastname && $person['firstname'] == $firstname && $person['email'] == $email ) ) {
                return false;
            }

            // now unset the existing link if present (should not be, but one never knows)
            $sql = $wpdb->prepare( "UPDATE $table SET wp_id = 0 WHERE wp_id = %d AND person_id <> %d", $wp_id, $person_id );
            $wpdb->query( $sql );

            // we'll set the wp_id and other info from wp too
            $where              = [];
            $where['person_id'] = intval( $person_id );
            $person_update      = compact( 'lastname', 'firstname', 'email', 'wp_id' );
            return $wpdb->update( $table, $person_update, $where );
        }
    } else {
        $sql = $wpdb->prepare( "UPDATE $table SET wp_id = 0 WHERE person_id = %d", $person_id );
        return $wpdb->query( $sql );
    }
}

function eme_update_email_gdpr( $email ) {
    global $wpdb;
    $table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $gdpr_date = current_time( 'mysql', false );
    $sql       = $wpdb->prepare( "UPDATE $table SET gdpr = 1, gdpr_date=%s WHERE email = %s", $gdpr_date, $email );
    $wpdb->query( $sql );
}

function eme_update_people_gdpr( $person_ids, $gdpr = 1 ) {
    global $wpdb;
    $table     = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $gdpr_date = current_time( 'mysql', false );
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql = $wpdb->prepare("UPDATE $table SET gdpr=%d, gdpr_date=%s WHERE person_id IN ($person_ids)", $gdpr, $gdpr_date);
        $wpdb->query( $sql );
    }
}

function eme_update_email_massmail( $email, $massmail ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql   = $wpdb->prepare( "UPDATE $table SET massmail = %d WHERE email = %s", $massmail, $email );
    return $wpdb->query( $sql );
}

function eme_update_people_massmail( $person_ids, $massmail = 1 ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql = $wpdb->prepare( "UPDATE $table SET massmail=%d WHERE person_id IN ($person_ids)", $massmail );
        $wpdb->query( $sql );
    }
}

function eme_update_people_bdemail( $person_ids, $bd_email = 1 ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql = $wpdb->prepare( "UPDATE $table SET bd_email=%d WHERE person_id IN ($person_ids)", $bd_email );
        $wpdb->query( $sql );
    }
}

function eme_update_people_language( $person_ids, $lang ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql   = $wpdb->prepare( "UPDATE $table SET lang=%s WHERE person_id IN ($person_ids)", $lang );
        $wpdb->query( $sql );
    }
}

function eme_get_indexed_users() {
    global $wpdb;
    $sql           = "SELECT ID, display_name FROM $wpdb->users";
    $users         = $wpdb->get_results( $sql, ARRAY_A );
    $indexed_users = [];
    foreach ( $users as $user ) {
        $indexed_users[ $user['ID'] ] = $user['display_name'];
    }
    return $indexed_users;
}

function eme_get_wp_users( $search, $offset = 0, $pagesize = 0, $wp_ids_to_exclude = [] ) {
    $meta_query = [
        'relation' => 'OR',
        [
            'key'     => 'nickname',
            'value'   => $search,
            'compare' => 'LIKE',
        ],
        [
            'key'     => 'first_name',
            'value'   => $search,
            'compare' => 'LIKE',
        ],
        [
            'key'     => 'last_name',
            'value'   => $search,
            'compare' => 'LIKE',
        ],
        [
            'key'     => 'display_name',
            'value'   => $search,
            'compare' => 'LIKE',
        ],
        [
            'key'     => 'user_email',
            'value'   => $search,
            'compare' => 'LIKE',
        ],
    ];
    $args       = [
        'meta_query'  => $meta_query,
        'orderby'     => 'ID',
        'order'       => 'ASC',
        'count_total' => true,
        // 'fields'      => [ 'ID' ], // default is all, we want all
    ];
    if (!empty($wp_ids_to_exclude)) {
        $args['exclude'] = $wp_ids_to_exclude;
    }
    if ( $pagesize > 0 ) {
        $args['offset'] = $offset;
        $args['number'] = $pagesize;
    }
    // while get_users works, it doesn't give the total for paged results, so we need WP_User_Query directly
    //$users = get_users($args);
    $user_query = new WP_User_Query( $args );
    $users      = $user_query->get_results(); // array of WP_User objects, like get_users
    $total      = $user_query->get_total(); // int, total number of users (not just the first page)
    return [ $users, $total ];
}

add_action( 'wp_ajax_eme_subscribe', 'eme_subscribe_ajax' );
add_action( 'wp_ajax_nopriv_eme_subscribe', 'eme_subscribe_ajax' );
function eme_subscribe_ajax() {
    // check for spammers as early as possible
    if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
        $message = esc_html__( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
        wp_die();
    }
    if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) {
        $message = esc_html__( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
        wp_die();
    }

    // verify captchas
    $captcha_res = eme_check_captcha();

    $eme_lastname  = isset( $_POST['lastname'] ) ? eme_sanitize_request( $_POST['lastname'] ) : '';
    $eme_firstname = isset( $_POST['firstname'] ) ? eme_sanitize_request( $_POST['firstname'] ) : '';
    $eme_email     = eme_sanitize_email( $_POST['email'] );
    if ( eme_is_email_frontend( $eme_email ) ) {
        eme_captcha_remove ( $captcha_res );
        if ( isset( $_POST['email_groups'] ) && eme_is_numeric_array( $_POST['email_groups'] ) ) {
            $eme_email_groups = join( ',', $_POST['email_groups'] );
        } elseif ( isset( $_POST['email_group'] ) && is_numeric( $_POST['email_group'] ) ) {
            $eme_email_groups = eme_sanitize_request( $_POST['email_group'] );
        } else {
            $eme_email_groups = '';
        }
        eme_sub_send_mail( $eme_lastname, $eme_firstname, $eme_email, $eme_email_groups );
        $message = esc_html__( 'A request for confirmation has been sent to the given email address.', 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'OK',
                'htmlmessage' => $message,
            ]
        );
    } else {
        $message = esc_html__( 'Please enter a valid email address', 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
    }
    wp_die();
}

function eme_subform_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts( [ 'template_id' => 0 ], $atts );
    if ( !empty($atts['template_id']) ) {
        $format = eme_get_template_format( intval($atts['template_id']) );
    } else {
        $format     = '<p>' . esc_html__( 'If you want to subscribe to future mailings, please do so by entering your email here.', 'events-made-easy' ) . '<p>#_EMAIL';
        $tmp_groups = eme_get_subscribable_groupids();
        if ( ! empty( $tmp_groups ) ) {
            $format .= '<p>' . esc_html__( 'Please select the groups you wish to subscribe to.', 'events-made-easy' ) . '</p> #_MAILGROUPS';
        }
    }

    usleep( 2 );
	$form_id   = "eme_".eme_random_id(); // JS selectors need to start with a letter, so to be sure we prefix it
    $form_html  = "<noscript><div class='eme-noscriptmsg'>" . esc_html__( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-subscribe-message-ok-$form_id' class='eme-message-success eme-subscribe-message eme-subscribe-message-success eme-hidden'></div><div id='eme-subscribe-message-error-$form_id' class='eme-message-error eme-subscribe-message eme-subscribe-message-error eme-hidden'></div><div id='div_eme-subscribe-form-$form_id' class='eme-showifjs eme-hidden'><form id='$form_id' name='eme-subscribe-form' method='post' action='#'><span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span><img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><br>";
    $form_html .= wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
    $form_html .= eme_replace_subscribeform_placeholders( $format );
    $form_html .= '</form></div>';

    return $form_html;
}

add_action( 'wp_ajax_eme_unsubscribe', 'eme_unsubscribe_ajax' );
add_action( 'wp_ajax_nopriv_eme_unsubscribe', 'eme_unsubscribe_ajax' );
function eme_unsubscribe_ajax() {
    if ( ! isset( $_POST['honeypot_check'] ) || ! empty( $_POST['honeypot_check'] ) ) {
        $message = esc_html__( "Bot detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
        wp_die();
    }
    // check for spammers as early as possible
    if ( ! isset( $_POST['eme_frontend_nonce'] ) || ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) {
        $message = esc_html__( "Form tampering detected. If you believe you've received this message in error please contact the site owner.", 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
        wp_die();
    }

    // verify captchas
    $captcha_res = eme_check_captcha();

    $eme_email = eme_sanitize_email( $_POST['email'] );
    if ( eme_is_email_frontend( $eme_email ) ) {
        eme_captcha_remove ( $captcha_res );
        if ( isset( $_POST['email_groups'] ) && eme_is_numeric_array( $_POST['email_groups'] ) ) {
            $eme_email_groups = join( ',', $_POST['email_groups'] );
        } elseif ( isset( $_POST['email_group'] ) && is_numeric( $_POST['email_group'] ) ) {
            $eme_email_groups = eme_sanitize_request( $_POST['email_group'] );
        } else {
            $eme_email_groups = '';
        }
        eme_unsub_send_mail( $eme_email, $eme_email_groups );
        $message = esc_html__( 'A request for confirmation has been sent to the given email address.', 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'OK',
                'htmlmessage' => $message,
            ]
        );
    } else {
        $message = esc_html__( 'Please enter a valid email address', 'events-made-easy' );
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $message,
            ]
        );
    }
    wp_die();
}

function eme_unsubform_shortcode( $atts = [] ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $atts = shortcode_atts( [ 'template_id' => 0 ], $atts );
    if ( !empty($atts['template_id']) ) {
        $format = eme_get_template_format( intval($atts['template_id']) );
    } else {
        $format     = '<p>' . esc_html__( 'If you want to unsubscribe from future mailings, please do so by entering your email here.', 'events-made-easy' ) . '<p>#_EMAIL';
        $tmp_groups = eme_get_subscribable_groups();
        if ( ! empty( $tmp_groups ) || wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
            $format .= '<p>' . esc_html__( 'Please select the groups you wish to unsubscribe from.', 'events-made-easy' ) . '</p> #_MAILGROUPS';
        }
    }

    usleep( 2 );
	$form_id   = "eme_".eme_random_id(); // JS selectors need to start with a letter, so to be sure we prefix it
    $form_html   = "<noscript><div class='eme-noscriptmsg'>" . esc_html__( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-unsubscribe-message-ok-$form_id' class='eme-message-success eme-unsubscribe-message eme-unsubscribe-message-success eme-hidden'></div><div id='eme-unsubscribe-message-error-$form_id' class='eme-message-error eme-unsubscribe-message eme-unsubscribe-message-error eme-hidden'></div><div id='div_eme-unsubscribe-form-$form_id' class='eme-showifjs eme-hidden'><form id='$form_id' name='eme-unsubscribe-form' method='post' action='#'><span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span><img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><br>";
    $form_html  .= wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
    $unsubscribe = 1;
    $form_html  .= eme_replace_subscribeform_placeholders( $format, $unsubscribe );
    $form_html  .= '</form></div>';

    return $form_html;
}

function eme_get_person_answers( $person_id ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $answers       = wp_cache_get( "eme_person_answers $person_id" );
    if ( $answers === false ) {
        $sql     = $wpdb->prepare( "SELECT * FROM $answers_table WHERE related_id=%d AND type='person'", $person_id );
        $answers = $wpdb->get_results( $sql, ARRAY_A );
        wp_cache_set( "eme_person_answers $person_id", $answers, '', 10 );
    }
    return $answers;
}

function eme_delete_person_groups( $person_ids ) {
    global $wpdb;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $wpdb->query( "DELETE FROM $usergroups_table WHERE person_id IN ($person_ids)" );
    }
}

function eme_delete_person_answers( $person_ids ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $wpdb->query( "DELETE FROM $answers_table WHERE related_id IN ($person_ids) AND type='person'" );
    }
}

function eme_delete_person_memberships( $person_ids ) {
    global $wpdb;
    $members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    if ( eme_is_list_of_int( $person_ids ) ) {
        $sql        = "SELECT member_id FROM $members_table WHERE person_id IN ($person_ids)";
        $member_ids = $wpdb->get_col( $sql );
        foreach ( $member_ids as $member_id ) {
            // we set the second param to 1, in which case emails will be sent for deleting members
            eme_delete_member( $member_id, 1 );
        }
    }
}

function eme_people_answers( $person_id, $new_person = 0 ) {
    return eme_store_person_answers( $person_id, $new_person );
}
function eme_store_person_answers( $person_id, $new_person = 0, $backend = 0 ) {
    $all_answers = [];
    if ( $person_id > 0 ) {
        $all_answers = eme_get_person_answers( $person_id );
        wp_cache_delete( "eme_person_answers $person_id" );
    }

    $answer_ids_seen    = [];
    $formfield_ids_seen = [];
    // for a new person the POST fields have key 0, since the person_id wasn't known yet
    if ( $new_person ) {
        $field_person_id = 0;
    } else {
        $field_person_id = $person_id;
    }
    if ( isset( $_POST['dynamic_personfields'][ $field_person_id ] ) ) {
        foreach ( $_POST['dynamic_personfields'][ $field_person_id ] as $key => $value ) {
            if ( preg_match( '/^FIELD(\d+)$/', eme_sanitize_request($key), $matches ) ) {
                $field_id  = intval( $matches[1] );
                $formfield = eme_get_formfield( $field_id );
                if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'people' ) {
                    $formfield_ids_seen[] = $field_id;
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
                    $answer_id = eme_get_answerid( $all_answers, $person_id, 'person', $field_id );
                    if ( $answer_id ) {
                        eme_update_answer( $answer_id, $value );
                    } else {
                        $answer_id = eme_insert_answer( 'person', $person_id, $field_id, $value );
                    }
                    $answer_ids_seen[] = $answer_id;
                }
            }
        }
    }

    // if via frontend-form: delete old answer_ids, but only for those fields we want updated/added. We don't touch other field answers, so we don't lose data
    // if via backend: delete old answer_ids unseen
    if ( $person_id > 0 ) {
        foreach ( $all_answers as $answer ) {
            if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && ( $backend || in_array( $answer['field_id'], $formfield_ids_seen ) ) && $answer['type'] == 'person' && $answer['related_id'] == $person_id ) {
                eme_delete_answer( $answer['answer_id'] );
            }
        }
    }
}

function eme_store_family_answers( $person_id, $familymember ) {
    $all_answers = [];
    $all_answers = eme_get_person_answers( $person_id );

    $answer_ids_seen    = [];
    $formfield_ids_seen = [];
    foreach ( $familymember as $key => $value ) {
        if ( preg_match( '/^FIELD(\d+)$/', $key, $matches ) ) {
            $field_id  = intval( $matches[1] );
            $formfield = eme_get_formfield( $field_id );
            if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'people' ) {
                $formfield_ids_seen[] = $field_id;
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
                $answer_id = eme_get_answerid( $all_answers, $person_id, 'person', $field_id );
                if ( $answer_id ) {
                    eme_update_answer( $answer_id, $value );
                } else {
                    $answer_id = eme_insert_answer( 'person', $person_id, $field_id, $value );
                }
                $answer_ids_seen[] = $answer_id;
            }
        }
    }

    // delete old answer_ids, but only for those fields we want updated/added. We don't touch other field answers, so we don't lose data
    foreach ( $all_answers as $answer ) {
        if ( ! in_array( $answer['answer_id'], $answer_ids_seen ) && in_array( $answer['field_id'], $formfield_ids_seen ) && $person_id > 0 && $answer['type'] == 'person' && $answer['related_id'] == $person_id ) {
            eme_delete_answer( $answer['answer_id'] );
        }
    }
}

function eme_ajax_people_autocomplete( $no_wp_die = 0, $wp_membership_required = 0 ) {
    global $wpdb;
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        wp_die();
    }
    if ( ( ! isset( $_POST['eme_admin_nonce'] ) && ! isset( $_POST['eme_frontend_nonce'] ) ) ||
        ( isset( $_POST['eme_admin_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_admin_nonce']), 'eme_admin' ) ) ||
        ( isset( $_POST['eme_frontend_nonce'] ) && ! wp_verify_nonce( eme_sanitize_request($_POST['eme_frontend_nonce']), 'eme_frontend' ) ) ) {
        wp_die();
    }
    $return = [];
    $lastname      = '';
    if ( isset( $_POST['lastname'] ) ) {
        $lastname = strtolower( eme_sanitize_request( $_POST['lastname'] ) );
    } elseif ( isset( $_POST['task_lastname'] ) ) {
        $lastname = strtolower( eme_sanitize_request( $_POST['task_lastname'] ) );
    }

    if ( isset( $_POST['exclude_personids'] ) ) {
        $exclude_personids = eme_sanitize_request( $_POST['exclude_personids'] );
    } else {
        $exclude_personids = '';
    }
    // verify $exclude_personids some more
    $exclude_personids_arr = explode( ',', $exclude_personids );
    if ( ! eme_is_numeric_array( $exclude_personids_arr ) ) {
        $exclude_personids = '';
    }

    header( 'Content-type: application/json; charset=utf-8' );
    if ( empty( $lastname ) ) {
        echo wp_json_encode( $return );
        if ( ! $no_wp_die ) {
            wp_die();
        }
        return;
    }

    $search_tables = get_option( 'eme_autocomplete_sources' );
    if ( isset( $_POST['eme_searchlimit'] ) && $_POST['eme_searchlimit'] == 'people' ) {
        $search_tables = 'people';
    }
    if ( $wp_membership_required ) {
        $search_tables = 'wp_users';
    }

    $wp_ids_seen = [];
    if ( $search_tables == 'people' || $search_tables == 'both' ) {
        $search = "(lastname LIKE '%" . esc_sql( $wpdb->esc_like($lastname) ) . "%' OR firstname LIKE '%" . esc_sql( $wpdb->esc_like($lastname) ) . "%' OR email LIKE '%" . esc_sql( $wpdb->esc_like($lastname) ) . "%')";
        if ( ! empty( $exclude_personids ) ) {
            $search .= " AND person_id NOT IN ($exclude_personids)";
        }
        $persons = eme_get_persons( '', $search );
        foreach ( $persons as $item ) {
            $record              = [];
            $record['lastname']  = eme_esc_html( $item['lastname'] );
            $record['firstname'] = eme_esc_html( $item['firstname'] );
            $record['address1']  = eme_esc_html( $item['address1'] );
            $record['address2']  = eme_esc_html( $item['address2'] );
            $record['city']      = eme_esc_html( $item['city'] );
            $record['zip']       = eme_esc_html( $item['zip'] );
            $record['state']     = eme_esc_html( eme_get_state_name( $item['state_code'], $item['country_code'] ) );
            $record['country']   = eme_esc_html( eme_get_country_name( $item['country_code'] ) );
            $record['email']     = eme_esc_html( $item['email'] );
            $record['phone']     = eme_esc_html( $item['phone'] );
            $record['person_id'] = intval( $item['person_id'] );
            $record['wp_id']     = intval( $item['wp_id'] );
            $record['massmail']  = intval( $item['massmail'] );
            $record['gdpr']      = intval( $item['gdpr'] );
            $record['birthdate'] = $item['birthdate'];
            $return[]            = $record;
            if (!empty($record['wp_id'] )) {
                $wp_ids_seen[]=$record['wp_id'];
            }
        }
    }
    if ( $search_tables == 'wp_users' || $search_tables == 'both' ) {
        // we don't want to include the people linked in EME, so we exclude those
        [$wp_users, $total] = eme_get_wp_users( search: $lastname, wp_ids_to_exclude: $wp_ids_seen );
        foreach ( $wp_users as $wp_user ) {
            $record             = [];
            $phone              = eme_esc_html( eme_get_user_phone( $wp_user->ID ) );
            $record['lastname'] = eme_esc_html( $wp_user->user_lastname );
            if ( empty( $record['lastname'] ) ) {
                $record['lastname'] = eme_esc_html( $wp_user->display_name );
            }
            $record['firstname'] = eme_esc_html( $wp_user->user_firstname );
            $record['email']     = eme_esc_html( $wp_user->user_email );
            $record['address1']  = '';
            $record['address2']  = '';
            $record['city']      = '';
            $record['zip']       = '';
            $record['state']     = '';
            $record['country']   = '';
            $record['phone']     = eme_esc_html( $phone );
            $record['wp_id']     = intval( $wp_user->ID );
            $record['massmail']  = 1;
            $record['gdpr']      = 1;
            $record['person_id'] = 0;
            $return[]            = $record;
        }
    }

    echo wp_json_encode( $return );
    if ( ! $no_wp_die ) {
        wp_die();
    }
}

add_action( 'wp_ajax_eme_autocomplete_people', 'eme_ajax_people_autocomplete' );
add_action( 'wp_ajax_eme_people_list', 'eme_ajax_people_list' );
add_action( 'wp_ajax_eme_groups_list', 'eme_ajax_groups_list' );
add_action( 'wp_ajax_eme_manage_people', 'eme_ajax_manage_people' );
add_action( 'wp_ajax_eme_manage_groups', 'eme_ajax_manage_groups' );
add_action( 'wp_ajax_eme_store_people_query', 'eme_ajax_store_people_query' );

function eme_ajax_people_list( ) {
    global $wpdb;
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = esc_html__( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }

    $formfields = eme_get_formfields( '', 'people' );

    $fTableResult = [];
    $limit        = eme_get_datatables_limit();
    $orderby      = eme_get_datatables_orderby();
    $search_terms = eme_unserialize(eme_sanitize_request($_POST));
    $count_sql    = eme_get_sql_people_searchfields( $search_terms, 1 );
    $sql          = eme_get_sql_people_searchfields( $search_terms );
    $recordCount  = $wpdb->get_var( $count_sql );
    $rows         = $wpdb->get_results( $sql, ARRAY_A );
    $wp_users     = eme_get_indexed_users();
    $records      = [];
    foreach ( $rows as $item ) {
        $record = [];
        if ( empty( $item['lastname'] ) ) {
            $item['lastname'] = esc_html__( 'No surname', 'events-made-easy' );
        }
        $record['people.person_id'] = $item['person_id'];
        if ( $item['related_person_id'] ) {
            $related_person              = eme_get_person( $item['related_person_id'] );
            $record['people.related_to'] = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $item['related_person_id'] ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $related_person['lastname'] . ' ' . $related_person['firstname'] ) . '</a>';
            $familytext                  = esc_html__( '(family member)', 'events-made-easy' );
        } else {
            $record['people.related_to'] = '';
            $familytext                  = '';
        }

        //$owner_user_info = get_userdata($item['wp_id']);
        //$record['people.wp_id'] = eme_esc_html($owner_user_info->display_name);
        if ( $item['wp_id'] && isset( $wp_users[ $item['wp_id'] ] ) ) {
            $record['people.wp_user'] = eme_esc_html( $wp_users[ $item['wp_id'] ] );
        } else {
            $record['people.wp_user'] = '';
        }
        $record['people.lastname']   = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $item['person_id'] ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $item['lastname'] ) . '</a> ' . $familytext;
        $record['people.firstname']  = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $item['person_id'] ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $item['firstname'] ) . '</a> ' . $familytext;
        $record['people.email']      = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $item['person_id'] ) . "' title='" . esc_attr__( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $item['email'] ) . '</a> ' . $familytext;
        $record['people.phone']      = eme_esc_html( $item['phone'] );
        $record['people.birthdate']  = eme_localized_date( $item['birthdate'], EME_TIMEZONE, 1 );
        $record['people.bd_email']   = $item['bd_email'] ? esc_html__( 'Yes', 'events-made-easy' ) : esc_html__( 'No', 'events-made-easy' );
        $record['people.birthplace'] = eme_esc_html( $item['birthplace'] );
        $record['people.address1']   = eme_esc_html( $item['address1'] );
        $record['people.address2']   = eme_esc_html( $item['address2'] );
        $record['people.city']       = eme_esc_html( $item['city'] );
        $record['people.zip']        = eme_esc_html( $item['zip'] );
        $record['people.lang']       = eme_esc_html( $item['lang'] );
        if ( $item['state_code'] ) {
            $record['people.state'] = eme_esc_html( eme_get_state_name( $item['state_code'], $item['country_code'] ) );
        } elseif ( isset( $item['state'] ) ) {
            $record['people.state'] = eme_esc_html( $item['state'] );
        }
        if ( $item['country_code'] ) {
            $record['people.country'] = eme_esc_html( eme_get_country_name( $item['country_code'] ) );
        } elseif ( isset( $item['country'] ) ) {
            $record['people.country'] = eme_esc_html( $item['country'] );
        }
        $record['people.massmail']      = $item['massmail'] ? esc_html__( 'Yes', 'events-made-easy' ) : esc_html__( 'No', 'events-made-easy' );
        $record['people.gdpr']          = $item['gdpr'] ? esc_html__( 'Yes', 'events-made-easy' ) : esc_html__( 'No', 'events-made-easy' );
        $record['people.gdpr_date']     = eme_esc_html( $item['gdpr_date'] );
        $record['people.creation_date'] = eme_localized_datetime( $item['creation_date'], EME_TIMEZONE, 1 );
        $record['people.modif_date']    = eme_localized_datetime( $item['modif_date'], EME_TIMEZONE, 1 );
        $record['people.groups']        = join( ', ', eme_esc_html( eme_get_persongroup_names( $item['person_id'] ) ) );
        $record['people.memberships']   = eme_get_activemembership_names_by_personid( $item['person_id'] );
        $answers = eme_get_person_answers( $item['person_id'] );
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
        $files = eme_get_uploaded_files( $item['person_id'], 'people' );
        foreach ( $files as $file ) {
            $key = 'FIELD_' . $file['field_id'];
            $record[$key] = ($record[$key] ?? '') . eme_get_uploaded_file_html( $file );
        }
        $records[] = $record;
    }
    $fTableResult['Result']           = 'OK';
    $fTableResult['Records']          = $records;
    $fTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $fTableResult );
    wp_die();
}

function eme_ajax_groups_list() {
    global $wpdb;
    $table            = EME_DB_PREFIX . EME_GROUPS_TBNAME;
    $usergroups_table = EME_DB_PREFIX . EME_USERGROUPS_TBNAME;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = esc_html__( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }
    $fTableResult = [];
    $sql          = "SELECT COUNT(*) FROM $table";
    $recordCount  = $wpdb->get_var( $sql );

    $sql        = "SELECT group_id,COUNT(*) AS eme_groupcount FROM $usergroups_table GROUP BY group_id";
    $res        = $wpdb->get_results( $sql, ARRAY_A );
    $groupcount = [];
    foreach ( $res as $val ) {
        $groupcount[ $val['group_id'] ] = $val['eme_groupcount'];
    }

    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();
    $sql      = "SELECT * FROM $table $orderby $limit";
    $groups   = $wpdb->get_results( $sql, ARRAY_A );
    $records  = [];
    foreach ( $groups as $group ) {
        $record = [];
        if ( empty( $group['name'] ) ) {
            $group['name'] = esc_html__( 'No name', 'events-made-easy' );
        }
        $record['group_id'] = $group['group_id'];
        $record['public']   = $group['public'] ? esc_html__( 'Yes', 'events-made-easy' ) : esc_html__( 'No', 'events-made-easy' );
        if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            $record['name'] = "<a href='" . admin_url( 'admin.php?page=eme-groups&amp;eme_admin_action=edit_group&amp;group_id=' . $group['group_id'] ) . "' title='" . esc_attr__( 'Edit group', 'events-made-easy' ) . "'>" . eme_esc_html( $group['name'] ) . '</a>';
        } else {
            $record['name'] = eme_esc_html( $group['name'] );
        }
        $record['description'] = eme_esc_html( $group['description'] );
        if ( $group['type'] == 'dynamic_people' ) {
            $record['groupcount'] = esc_html__( 'Dynamic group of people', 'events-made-easy' );
            if ( ! empty( $group['search_terms'] ) ) {
                $search_terms = eme_unserialize( $group['search_terms'] );
                $count_sql    = eme_get_sql_people_searchfields( $search_terms, 1 );
                $count        = $wpdb->get_var( $count_sql );
                if ( $count > 0 ) {
                    $record['groupcount'] .= '&nbsp;' . sprintf( _n( '(1 person)', '(%d persons)', $count, 'events-made-easy' ), $count );
                }
            }
        } elseif ( $group['type'] == 'dynamic_members' ) {
            $record['groupcount'] = esc_html__( 'Dynamic group of members', 'events-made-easy' );
            if ( ! empty( $group['search_terms'] ) ) {
                $search_terms = eme_unserialize( $group['search_terms'] );
                $count_sql    = eme_get_sql_members_searchfields( search_terms: $search_terms, count: 1 );
                $count        = $wpdb->get_var( $count_sql );
                if ( $count > 0 ) {
                    $record['groupcount'] .= '&nbsp;' . sprintf( _n( '(1 member)', '(%d members)', $count, 'events-made-easy' ), $count );
                }
            }
        } else {
            $record['groupcount'] = isset( $groupcount[ $group['group_id'] ] ) ? intval($groupcount[ $group['group_id'] ]) : 0;
        }
        $records[] = $record;
    }
    $fTableResult['Result']           = 'OK';
    $fTableResult['Records']          = $records;
    $fTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $fTableResult );
    wp_die();
}

/**
 * SnapSelect endpoint for the "choose person" field on the add-member form.
 * Returns {Records:[{id, text, firstname, lastname, email, wpId}], TotalRecordCount}
 * so that onItemAdd can populate the personal detail fields directly from the option's dataset.
 */
function eme_ajax_chooseperson_snapselect() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        wp_die();
    }

    $q        = isset( $_REQUEST['q'] ) ? strtolower( eme_sanitize_request( $_REQUEST['q'] ) ) : '';
    $pagesize = isset( $_REQUEST['pagesize'] ) ? intval( $_REQUEST['pagesize'] ) : 20;
    $page     = isset( $_REQUEST['page'] )     ? max( 1, intval( $_REQUEST['page'] ) ) : 1;
    $start    = ( $page - 1 ) * $pagesize;

    $where = ! empty( $q )
        ? "(lastname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR firstname LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%' OR email LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%') AND status=" . EME_PEOPLE_STATUS_ACTIVE
        : 'status=' . EME_PEOPLE_STATUS_ACTIVE;

    if ( ! empty( $_REQUEST['exclude_personids'] ) ) {
        $exclude_personids     = eme_sanitize_request( $_REQUEST['exclude_personids'] );
        $exclude_personids_arr = explode( ',', $exclude_personids );
        if ( eme_is_numeric_array( $exclude_personids_arr ) ) {
            $where .= " AND person_id NOT IN ($exclude_personids)";
        }
    }

    $recordCount = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
    $persons     = eme_get_persons( '', $where, "LIMIT $start,$pagesize" );

    $records = [];
    foreach ( $persons as $person ) {
        $records[] = [
            'id'        => intval( $person['person_id'] ),
            'text'      => eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] ) . ' (' . $person['email'] . ')',
            'firstname' => eme_esc_html( $person['firstname'] ),
            'lastname'  => eme_esc_html( $person['lastname'] ),
            'email'     => eme_esc_html( $person['email'] ),
            'wpid'      => intval( $person['wp_id'] ),
        ];
    }
    print wp_json_encode( [ 'Records' => $records, 'TotalRecordCount' => $recordCount ] );
    wp_die();
}
add_action( 'wp_ajax_eme_chooseperson_snapselect', 'eme_ajax_chooseperson_snapselect' );

function eme_ajax_store_people_query() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_people' ) ) ) {
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = esc_html__( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }
    if ( ! empty( $_POST['dynamicgroupname'] ) ) {
        $group         = [];
        $group['type'] = 'dynamic_people';
        $group['name'] = esc_sql(eme_sanitize_request($_POST['dynamicgroupname']) . ' ' . __( '(Dynamic)', 'events-made-easy' ));
        $search_terms  = [];
        // the same as in add_update_group
        $search_fields = [ 'search_membershipids', 'search_memberstatus', 'search_person', 'search_groups', 'search_memberid', 'search_customfields', 'search_customfieldids', 'search_exactmatch' ];
        foreach ( $search_fields as $search_field ) {
            if ( isset( $_POST[ $search_field ] ) ) {
                $search_terms[ $search_field ] = esc_sql( eme_sanitize_request( $_POST[ $search_field ] ) );
            }
        }
        $group['search_terms'] = eme_serialize( $search_terms );
        $new_group_id = eme_db_insert_group($group);
        if ($new_group_id) {
            $fTableResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . esc_html__( 'Dynamic group added', 'events-made-easy' ) . '</p></div>';
        } else {
            $fTableResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'There was a problem adding the group', 'events-made-easy' ) . '</p></div>';
        }
    } else {
        $fTableResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . esc_html__( 'Please enter a name for the group', 'events-made-easy' ) . '</p></div>';
    }
    print wp_json_encode( $fTableResult );
    wp_die();
}

function eme_ajax_manage_people() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $ajaxResult = [];
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        $ids       = eme_sanitize_request( $_POST['person_id'] );
        $ids_arr   = explode( ',', $ids );
        if ( ! eme_is_numeric_array( $ids_arr ) || ! current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            $fTableResult['Result']  = 'ERROR';
            $fTableResult['htmlmessage'] = eme_message_error_div(esc_html__( 'Access denied!', 'events-made-easy'));
            print wp_json_encode( $ajaxResult );
            wp_die();
        }

        switch ( $do_action ) {
        case 'untrashPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_untrash_people( $ids );
            break;
        case 'trashPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            if (!empty( $_POST['transferto_id'] ) ) {
                $to_person_id = intval( $_POST['transferto_id'] );
            } else {
                $to_person_id = 0;
            }
            eme_ajax_action_trash_people( $ids, $to_person_id );
            break;
        case 'gdprPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_gdpr_trash_people( $ids );
            break;
        case 'gdprApprovePeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_gdpr_approve_people( $ids );
            break;
        case 'gdprUnapprovePeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_gdpr_unapprove_people( $ids );
            break;
        case 'massmailPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_set_massmail_people( $ids );
            break;
        case 'noMassmailPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_set_nomassmail_people( $ids );
            break;
        case 'bdemailPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_set_bdemail_people( $ids );
            break;
        case 'noBdemailPeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_set_nobdemail_people( $ids );
            break;
        case 'deletePeople':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_delete_people( $ids );
            break;
        case 'addToGroup':
            header( 'Content-type: application/json; charset=utf-8' );
            $group_id = ( isset( $_POST['addtogroup'] ) ) ? intval( $_POST['addtogroup'] ) : 0;
            eme_ajax_action_add_people_to_group( $ids_arr, $group_id );
            break;
        case 'removeFromGroup':
            header( 'Content-type: application/json; charset=utf-8' );
            $group_id = ( isset( $_POST['removefromgroup'] ) ) ? intval( $_POST['removefromgroup'] ) : 0;
            eme_ajax_action_delete_people_from_group( $ids_arr, $group_id );
            break;
        case 'changeLanguage':
            header( 'Content-type: application/json; charset=utf-8' );
            eme_ajax_action_set_people_language( $ids );
            break;
        case 'pdf':
            $template_id        = ( isset( $_POST['pdf_template'] ) ) ? intval( $_POST['pdf_template'] ) : 0;
            $template_id_header = ( isset( $_POST['pdf_template_header'] ) ) ? intval( $_POST['pdf_template_header'] ) : 0;
            $template_id_footer = ( isset( $_POST['pdf_template_footer'] ) ) ? intval( $_POST['pdf_template_footer'] ) : 0;
            if ( $template_id ) {
                eme_ajax_generate_people_pdf( $ids_arr, $template_id, $template_id_header, $template_id_footer );
            }
            break;
        case 'html':
            $template_id        = ( isset( $_POST['html_template'] ) ) ? intval( $_POST['html_template'] ) : 0;
            $template_id_header = ( isset( $_POST['html_template_header'] ) ) ? intval( $_POST['html_template_header'] ) : 0;
            $template_id_footer = ( isset( $_POST['html_template_footer'] ) ) ? intval( $_POST['html_template_footer'] ) : 0;
            if ( $template_id ) {
                eme_ajax_generate_people_html( $ids_arr, $template_id, $template_id_header, $template_id_footer );
            }
            break;
        }
    }
    wp_die();
}

function eme_ajax_manage_groups() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( isset( $_REQUEST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_REQUEST['do_action'] );
        $ids       = eme_sanitize_request( $_REQUEST['group_id'] );
        $ids_arr   = explode( ',', $ids );
        if ( ! eme_is_numeric_array( $ids_arr ) || ! current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
            $ajaxResult            = [];
            $ajaxResult['Result']  = 'ERROR';
            $ajaxResult['htmlmessage'] = eme_message_error_div(esc_html__( 'Access denied!', 'events-made-easy' ));
            print wp_json_encode( $ajaxResult );
            wp_die();
        }
        switch ( $do_action ) {
        case 'deleteGroups':
            eme_ajax_action_delete_groups( $ids );
            break;
        }
    }
    wp_die();
}

function eme_ajax_action_untrash_people( $ids ) {
    $ajaxResult = [];
    eme_untrash_people( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'People recovered from trash bin.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_trash_people( $ids, $transferto_id=0 ) {
    $ajaxResult = [];
    if ( ! empty( $transferto_id ) ) {
        eme_transfer_person_bookings( $ids, $transferto_id );
        eme_transfer_person_task_signups( $ids, $transferto_id );
    }
    eme_trash_people( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'People moved to trash bin.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_gdpr_trash_people( $ids ) {
    $ajaxResult = [];
    eme_gdpr_trash_people( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Personal data removed and moved to trash bin.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_gdpr_approve_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_gdpr( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'GDPR approval set to "Yes" (make sure the selected persons are aware of this).', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_gdpr_unapprove_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_gdpr( $ids, 0 );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'GDPR approval set to "No" (make sure the selected persons are aware of this).', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_set_massmail_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_massmail( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Massmail set to "Yes" (make sure the selected persons are aware of this).', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_set_nomassmail_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_massmail( $ids, 0 );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Massmail set to "No" (make sure the selected persons are aware of this).', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_set_bdemail_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_bdemail( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Birthday email set to "Yes".', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_set_nobdemail_people( $ids ) {
    $ajaxResult = [];
    eme_update_people_bdemail( $ids, 0 );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Birthday email set to "No".', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_set_people_language( $ids ) {
    $ajaxResult = [];
    $lang       = eme_sanitize_request( $_POST['language'] );
    eme_update_people_language( $ids, $lang );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Language updated.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_delete_people( $ids ) {
    $ajaxResult = [];
    if ( ! empty( $_POST['transferto_id'] ) ) {
        $to_person_id = intval( $_POST['transferto_id'] );
        eme_transfer_person_bookings( $ids, $to_person_id );
        eme_transfer_person_task_signups( $ids, $to_person_id );
    }
    eme_delete_people( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'People deleted.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
}

function eme_ajax_action_add_people_to_group( $ids_arr, $group_id ) {
    $ajaxResult = [];
    foreach ( $ids_arr as $person_id ) {
        eme_add_persongroups( $person_id, $group_id );
    }
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'People added to group.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
    wp_die();
}

function eme_ajax_action_delete_people_from_group( $ids_arr, $group_id ) {
    $ajaxResult = [];
    foreach ( $ids_arr as $person_id ) {
        eme_delete_person_from_group( $person_id, $group_id );
    }
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'People removed from group.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
    wp_die();
}

function eme_ajax_action_delete_groups( $ids ) {
    $ajaxResult = [];
    eme_delete_groups( $ids );
    $ajaxResult['Result']      = 'OK';
    $ajaxResult['htmlmessage'] = eme_message_ok_div(esc_html__( 'Groups deleted.', 'events-made-easy' ));
    print wp_json_encode( $ajaxResult );
    wp_die();
}

function eme_ajax_generate_people_pdf( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
    $template = eme_get_template( $template_id );
    // the template format needs br-handling, so lets use a handy function
    $format = eme_get_template_format( $template_id );
    $header = eme_get_template_format( $template_id_header );
    $footer = eme_get_template_format( $template_id_footer );

    require_once 'dompdf/vendor/autoload.php';
    // instantiate and use the dompdf class
    $options = new Dompdf\Options();
    $options->set( 'isRemoteEnabled', true );
    $options->set( 'isHtml5ParserEnabled', true );
    $dompdf      = new Dompdf\Dompdf( $options );
    $margin_info = 'margin: ' . $template['properties']['pdf_margins'] . ';';
    $font_info       = 'font-family: ' . get_option( 'eme_pdf_font' );
    $orientation = $template['properties']['pdf_orientation'];
    $pagesize    = $template['properties']['pdf_size'];
    if ( $pagesize == 'custom' ) {
        $pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
    }

    $dompdf->setPaper( $pagesize, $orientation );
    $html  = "
<html>
<head>
<style>
    @page { $margin_info; }
    body { $margin_info; $font_info; }
    div.page-break {
        page-break-before: always;
    }
</style>
</head>
<body>
$header
";
    $total = count( $ids_arr );
    $i     = 1;
    foreach ( $ids_arr as $person_id ) {
        $person = eme_get_person( $person_id );
        $html  .= eme_replace_people_placeholders( $format, $person );
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

function eme_ajax_generate_people_html( $ids_arr, $template_id, $template_id_header = 0, $template_id_footer = 0 ) {
    $format = eme_get_template_format( $template_id );
    $header = eme_get_template_format( $template_id_header );
    $footer = eme_get_template_format( $template_id_footer );
    $html   = "<html><body>$header";
    foreach ( $ids_arr as $person_id ) {
        $person = eme_get_person( $person_id );
        $html  .= eme_replace_people_placeholders( $format, $person );
    }
    $html .= "$footer</body></html>";
    print $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- full print view HTML
}

function eme_get_family_person_ids( $person_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $sql   = $wpdb->prepare( "SELECT person_id FROM $table WHERE related_person_id=%d AND status<>%d", $person_id, EME_PEOPLE_STATUS_TRASH );
    return $wpdb->get_col( $sql );
}

