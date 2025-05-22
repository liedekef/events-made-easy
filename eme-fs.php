<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_email_fs_event_action( $event, $action ) {
    $eme_fs_options = get_option('eme_fs');
    if (empty($eme_fs_options['contact_person']) || $eme_fs_options['contact_person']<=0)
        return;
    $contact        = eme_get_contact($eme_fs_options['contact_person']);
    $contact_email  = $contact->user_email;
    $contact_name   = $contact->display_name;
    $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';

    $author         = eme_get_author($event);
    $person         = eme_get_person_by_wp_id( $author->ID );
    if ( empty( $person ) ) {
        $person = eme_fake_person_by_wp_id( $author->ID );
    }
    $person_name    = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );

    // first get the initial values
    $person_subject  = '';
    $person_body     = '';
    $contact_subject = '';
    $contact_body    = '';
    $attachment_ids         = '';
    $attachment_tmpl_ids_arr = [];

    if ( $action == 'ipnReceived' ) {
        $attachment_ids = get_option( 'eme_fs_ipn_attach_ids' );
        $attachment_tmpl_ids_arr = get_option( 'eme_fs_ipn_attach_tmpl_ids' );
        $contact_subject = get_option('eme_fs_contactperson_ipn_email_subject');
        $contact_body = get_option('eme_fs_contactperson_ipn_email_body');
        $person_subject = get_option('eme_fs_author_ipn_email_subject');
        $person_body = get_option('eme_fs_author_ipn_email_body');
    } elseif ( $action == 'newevent' ) {
        $contact_subject = get_option('eme_fs_contactperson_newevent_email_subject');
        $contact_body = get_option('eme_fs_contactperson_newevent_email_body');
    }

    if ( ! empty( $person_subject ) ) {
        $person_subject = eme_replace_event_placeholders( $person_subject, $event, 'text' );
    }
    if ( ! empty( $person_body ) ) {
        $person_body = eme_replace_event_placeholders( $person_body, $event, $mail_text_html );
    }
    if ( ! empty( $contact_subject ) ) {
        $contact_subject = eme_replace_event_placeholders( $contact_subject, $event, 'text' );
    }
    if ( ! empty( $contact_body ) ) {
        $contact_body = eme_replace_event_placeholders( $contact_body, $event, $mail_text_html );
    }

    $mail_res = true; // make sure we return true if no mail is sent due to empty subject or body
    if ( ! empty( $contact_subject ) && ! empty( $contact_body ) ) {
        $mail_res = eme_queue_mail( $contact_subject, $contact_body, $contact_email, $contact_name, $contact_email, $contact_name, $contact_email, $contact_name );
    }
    if ( ! empty( $person_subject ) && ! empty( $person_body ) ) {
        // this possibily overrides mail_res, but that's ok since errors for mail to people are more important than to the contact person
        // to make sure the attachment_ids is an array ...
        if ( empty( $attachment_ids ) ) {
            $attachment_ids_arr = [];
        } else {
            $attachment_ids_arr = array_unique(explode( ',', $attachment_ids ));
        }
        // create and add the needed pdf attachments too
        if ( !empty( $attachment_tmpl_ids_arr ) ) {
            foreach ($attachment_tmpl_ids_arr as $attachment_tmpl_id) {
                $attachment_ids_arr[] = eme_generate_fs_event_pdf( $person, $event, $attachment_tmpl_id );
            }
        }

        $mail_res = eme_queue_mail( $person_subject, $person_body, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, 0, $person['person_id'], 0, $attachment_ids_arr );
    }

    return $mail_res;
}

function eme_generate_fs_event_pdf( $person, $event, $template_id ) {
    $template = eme_get_template( $template_id );

    // if the template is not meant for pdf, return
    if ( $template['type'] != "pdf" ) {
        return;
    }

    $targetPath  = EME_UPLOAD_DIR . '/fsevents/' . $person['wp_id'];
    $pdf_path    = '';
    if ( is_dir( $targetPath ) ) {
        foreach ( glob( "$targetPath/fsevent-$template_id-*.pdf" ) as $filename ) {
            $pdf_path = $filename;
        }
    }
    // we found a generated pdf, let's check the pdf creation time against the modif time of the event/booking/template
    if ( !empty( $pdf_path ) ) {
        $pdf_mtime      = filemtime( $pdf_path );
        $pdf_mtime_obj      = new ExpressiveDate( 'now', EME_TIMEZONE );
        $pdf_mtime_obj->setTimestamp($pdf_mtime);
        $booking_mtime_obj  = new ExpressiveDate( $booking['modif_date'], EME_TIMEZONE );
        $event_mtime_obj    = new ExpressiveDate( $event['modif_date'], EME_TIMEZONE );
        $template_mtime_obj = new ExpressiveDate( $template['modif_date'], EME_TIMEZONE );
        if ($booking_mtime_obj<$pdf_mtime_obj && $event_mtime_obj<$pdf_mtime_obj && $template_mtime_obj<$pdf_mtime_obj) {
            return $pdf_path;
        }
    }

    // the template format needs br-handling, so lets use a handy function
    $format = eme_get_template_format( $template_id );

    require_once 'dompdf/vendor/autoload.php';
    // instantiate and use the dompdf class
    $options = new Dompdf\Options();
    $options->set( 'isRemoteEnabled', true );
    $options->set( 'isHtml5ParserEnabled', true );
    $dompdf      = new Dompdf\Dompdf( $options );
    $margin_info = 'margin: ' . $template['properties']['pdf_margins'];
    $font_info       = 'font-family: ' . get_option( 'eme_pdf_font' );
    $orientation = $template['properties']['pdf_orientation'];
    $pagesize    = $template['properties']['pdf_size'];
    if ( $pagesize == 'custom' ) {
        $pagesize = [ 0, 0, $template['properties']['pdf_width'], $template['properties']['pdf_height'] ];
    }

    $dompdf->setPaper( $pagesize, $orientation );
    $css = "\n<link rel='stylesheet' id='eme-css'  href='" . esc_url(EME_PLUGIN_URL) . "css/eme.css' type='text/css' media='all'>";
    $eme_css_name = get_stylesheet_directory() . '/eme.css';
    if ( file_exists( $eme_css_name ) ) {
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
    // avoid a loop between eme_replace_booking_placeholders and eme_generate_booking_pdf
    $format = str_replace( '#_BOOKINGPDF_URL', '', $format );

    $format = eme_replace_people_placeholders( $format, $person );
    $html .= eme_replace_event_placeholders( $format, $event );
    $html .= "</body></html>";
    $dompdf->loadHtml( $html, get_bloginfo( 'charset' ) );
    $dompdf->render();
    // now we know where to store it, so create the dir
    eme_mkdir_with_index( $targetPath );
    // unlink old pdf
    array_map( 'wp_delete_file', glob( "$targetPath/fsevent-$template_id-*.pdf" ) );
    // now put new one
    $rand_id     = eme_random_id();
    $target_file = $targetPath . "/fsevent-$template_id-$rand_id.pdf";
    file_put_contents( $target_file, $dompdf->output() );
    return $target_file;
}


function eme_add_event_form_shortcode( $atts ) {
    eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $eme_fs_options = get_option('eme_fs');
    $is_user_logged_in=is_user_logged_in();

    if ((!$is_user_logged_in && !$eme_fs_options['guest_submit']) || ($is_user_logged_in && !current_user_can($eme_fs_options['cap_add_event'])) ) {
        if ($eme_fs_options['redirect_to_login']) {
            //auth_redirect();
            global $wp;
            $current_url = home_url( add_query_arg( array(), $wp->request ) );
            if (is_user_logged_in())
                $login_url = wp_login_url($current_url,true);
            else
                $login_url = wp_login_url($current_url);
            return eme_js_redirect($login_url);
        } else {
            if (empty($eme_fs_options['guest_not_allowed_text']))
                $eme_fs_options['guest_not_allowed_text'] = __("Sorry, but you're not allowed to submit new events.","events-made-easy");
            return "<div class='eme_fs_not_allowed'>". $eme_fs_options['guest_not_allowed_text'] . "</div>";
        }
        return false;
    }

    wp_enqueue_style( 'eme-leaflet-css' );
    wp_enqueue_script( 'eme-fs-location' );
    wp_enqueue_script( 'eme-edit-maps' );
    if (get_option( 'eme_htmleditor' ) == 'jodit') {
        // WordPress media library
        wp_enqueue_media();
        $translation_array = [
            'translate_adminnonce'      => wp_create_nonce( 'eme_admin' ),
            'translate_flanguage'       => eme_detect_lang(),
            'translate_insertfrommedia' => __('Insert from Media Library', 'events-made-easy' ),
            'translate_preview'         => __('Preview', 'events-made-easy' ),
            'translate_insertnbsp'      => __('Insert non-breaking space', 'events-made-easy' ),
        ];
        wp_localize_script( 'eme-jodit', 'emejodit', $translation_array );
        wp_enqueue_script('eme-jodit');
        wp_enqueue_style('jodit-css');
    }

    if (get_option( 'eme_htmleditor' ) == 'summernote') {
        wp_enqueue_media();
        $translation_array = [
            'translate_adminnonce'      => wp_create_nonce( 'eme_admin' ),
            'translate_flanguage'       => eme_detect_lang(),
            'translate_insertfrommedia' => __('Insert from Media Library', 'events-made-easy' ),
            'translate_preview'         => __('Preview', 'events-made-easy' ),
            'translate_insertnbsp'      => __('Insert non-breaking space', 'events-made-easy' ),
        ];
        wp_localize_script( 'eme-summernote', 'emesummernote', $translation_array );
        wp_enqueue_script('eme-summernote');
        wp_enqueue_style('summernote-css');
        wp_enqueue_script('summernote-table-js');
        wp_enqueue_style('summernote-table-css');
    }

    $atts = shortcode_atts( [ 'id' => 0, 'startdatetime' => '' ], $atts );
    $atts['id'] = intval($atts['id']);
    if ( $atts['startdatetime'] != 'now' )
        $atts['startdatetime'] = '';

    $form_id = uniqid();
    $nonce = wp_nonce_field( 'eme_frontend', 'eme_frontend_nonce', false, false );
    $form_html   = "<noscript><div class='eme-noscriptmsg'>" . __( 'Javascript is required for this form to work properly', 'events-made-easy' ) . "</div></noscript>
        <div id='eme-fs-message-ok-$form_id' class='eme-message-success eme-fs-message eme-fs-message-success eme-hidden'></div><div id='eme-fs-message-error-$form_id' class='eme-message-error eme-fs-message eme-fs-message-error eme-hidden'></div><div id='div_eme-fs-form-$form_id' style='display: none' class='eme-showifjs'><form id='$form_id' name='eme-fs-form' method='post' action='#'>
        $nonce
        <span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span>
";
    $format = eme_get_template_format( $atts['id'] );
    $form_html .= eme_event_fs_form( $atts['id'], $atts['startdatetime'] );
    $form_html  .= '</form></div>';
    return $form_html;
}

function eme_event_fs_form( $format, $startdatetime = '' ) {
    $eme_fs_options = get_option('eme_fs');
    if (empty($format)) {
        $format = $eme_fs_options['form_format'];
    }

    // replace EME language tags as early as possible
    $format = eme_translate_string( $format );

    // the generic placeholders
    $format = eme_replace_generic_placeholders( $format );

    // if the start datetime should be set to now
    $data_date = '';
    $data_time = '';
    if ($startdatetime == 'now') {
        $data_date = "data-date='" . eme_js_datetime('now')."'";
        $eme_date_obj_now = new ExpressiveDate( 'now' );
        $data_time = "value='".$eme_date_obj_now->format( EME_WP_TIME_FORMAT )."'";
    }

    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $format = eme_add_captcha_submit( $format );
    } else {
        $format = eme_add_captcha_submit( $format, eme_get_selected_captcha($eme_fs_options) );
    }

    $latitude_added = 0;
    $longitude_added = 0;
    $location_id_added = 0;
    $needle_offset = 0;
    preg_match_all( '/#(REQ)?_[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $empty_event = eme_new_event();
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $replacement        = '';
        $more               = '';
        $type               = 'text';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
        }               

        #_FIELD{} or #_FIELD{}{}{}
        #_ATT{} of #_ATT{}{}{} 
        #_PROP{} of #_PROP{}{}{}
        #_CUSTOMFIELD{}
        if ( preg_match( '/#_FIELD\{(.+?)\}(\{.+?\})?(\{.+?\})?$/', $result, $matches ) ) {
            $field = eme_sanitize_request($matches[1]);
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $type = substr( $matches[2], 1, -1 );
            }
            if ( isset( $matches[3] ) ) {
                // remove { and } (first and last char of second match)
                $more = substr( $matches[3], 1, -1 );
            }
            // for now date/time
            if ( $field == 'event_start_date' ) {
                $more .= " $data_date";
            }
            if ( $field == 'event_start_time' ) {
                $more .= " $data_time";
            }

            // for backwards compatibility
            if ($field == "location_address") $field="location_address1";
            if ($field == "location_town") $field="location_city";
            if ($field == "location_id") $location_id_added=1;

            // ignore manual adding lat and long (they are added autom)
            if ($field!="location_latitude" && $field!="location_longitude") {
                // try to be intelligent: if a property exists, we use the property
                if (!isset($empty_event[$field]) && isset($empty_event['event_properties'][$field])) {
                    $replacement = eme_get_fs_field_html('event-properties', 'prop-'.$type , $more , $required, $prop);
                } else {
                    $replacement = eme_get_fs_field_html($field, $type , $more , $required);
                }
            }

            // location also needs id, latitude and longitude (these are hidden anyway)
            if ( strstr( $field, 'location_' ) ) {
                if (!$location_id_added) {
                    $replacement .= eme_get_fs_field_html("location_id");
                    $location_id_added = 1;
                }
                if (!$latitude_added) {
                    $replacement .= eme_get_fs_field_html("location_latitude");
                    $latitude_added = 1;
                }
                if (!$longitude_added) {
                    $replacement .= eme_get_fs_field_html("location_longitude");
                    $longitude_added = 1;
                }
            }
        } elseif ( preg_match( '/#_ATT\{(.+?)\}(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
            $att = eme_sanitize_request($matches[1]);
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $type = substr( $matches[2], 1, -1 );
            }
            if ( isset( $matches[3] ) ) {
                // remove { and } (first and last char of second match)
                $more = substr( $matches[3], 1, -1 );
            }
            $replacement = eme_get_fs_field_html('event-attributes', 'att-'.$type , $more , $required, $att);
        } elseif ( preg_match( '/#_PROP\{(.+?)\}(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
            $prop = eme_sanitize_request($matches[1]);
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $type = substr( $matches[2], 1, -1 );
            }
            if ( isset( $matches[3] ) ) {
                // remove { and } (first and last char of second match)
                $more = substr( $matches[3], 1, -1 );
            }
            $replacement = eme_get_fs_field_html('event-properties', 'prop-'.$type , $more , $required, $prop);
        } elseif ( preg_match( '/#_CUSTOMFIELD\{(.+?)\}$/', $result, $matches ) ) {
            $formfield = eme_get_formfield($matches[1]);
            if ($formfield && ($formfield['field_purpose']=='events' || $formfield['field_purpose']=='locations')) {
                $postfield_name="FIELD".$formfield['field_id'];
                if ($formfield['field_required'])
                    $required=1;
                $replacement = eme_get_formfield_html($formfield,$postfield_name,'',$required);
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) {
            if (is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' )) {
                $replacement = '';
            } elseif ( !empty($eme_fs_options['selected_captcha']) && ! $captcha_set ) {
                $configured_captchas = eme_get_configured_captchas();
                if (!array_key_exists($eme_fs_options['selected_captcha'], $configured_captchas))
                    $eme_fs_options['selected_captcha'] = array_key_first($configured_captchas);
                $replacement = eme_generate_captchas_html($eme_fs_options['selected_captcha']);
                if (!empty($replacement))
                    $captcha_set = 1;
            }
        } elseif ( preg_match( '/#_MAP$/', $result ) ) {
            $replacement = "<div id='eme-edit-location-map' class='eme-frontendedit-location-map'></div>";
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __( 'Create event', 'events-made-easy' );
            }
            $replacement = "<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' style='display:none;'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = get_option( 'eme_form_required_field_string' );
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
    $format = eme_translate( $format );
    return do_shortcode( $format );
}

function eme_get_fs_field_html( $field = false, $type = 'text', $more = '', $required=0, $field_id = false) {
    if (!$field)
        return false;
    $localized_field_id='';
    $eme_fs_options = get_option('eme_fs');
    $selected_captcha = eme_get_selected_captcha($eme_fs_options);
    // if the type is not hidden, set it to the sensible value
    if ($type != 'hidden') {
        switch($field) {
        case 'event_notes':
        case 'location_description':
            if ($eme_fs_options['use_wysiwyg']) {
                $type = 'wysiwyg_textarea';
            } else {
                $type = 'textarea';
            }
            break;
        case 'event_category_ids':
            $type = ($type != 'radio')?'category_select':'category_radio';
            break;
        case 'event_status':
            $type = 'status_select';
            break;
        case 'event_rsvp':
            $type = 'binary';
            break;
        case 'location_id':
        case 'location_latitude':
        case 'location_longitude':
            $type = 'hidden';
            break;
        case 'event_start_time':
            //$localized_field_id='localized-start-time';
            //$more .= "required='required' readonly='readonly' class='eme_formfield_ftime' data-alt-field='event_start_time'";
            //$type = 'localized_datetime';
            $field = 'event[localized_start_time]';
            $more .= " size=8 class='eme_formfield_timepicker'";
            $type = 'localized_time';
            break;
        case 'event_end_time':
            //$localized_field_id='localized-end-time';
            //$more .= "readonly='readonly' class='eme_formfield_ftime' data-alt-field='event_end_time'";
            //$type = 'localized_datetime';
            $field = 'event[localized_end_time]';
            $more .= " size=8 class='eme_formfield_timepicker'";
            $type = 'localized_time';
            break;
        case 'event_start_date':
            $localized_field_id='localized-start-date';
            $more .= " readonly='readonly' class='eme_formfield_fdate' data-alt-field='event_start_date'";
            $type = 'localized_datetime';
            $required = 1;
            break;
        case 'event_end_date':
            $localized_field_id='localized-end-date';
            $more .= " readonly='readonly' class='eme_formfield_fdate' data-alt-field='event_end_date'";
            $type = 'localized_datetime';
            break;
        case 'location_name':
            $required = 1;
            $type = 'text';
            $more .= " class='clearable'";
            break;
        case 'event_name':
            $required = 1;
            $type = 'text';
            break;
        case 'event_attributes':
        case 'event_properties':
            break;
        case 'recaptcha':
        case 'hcaptcha':
        case 'cfcaptcha':
        case 'captcha':
            $type = $selected_captcha;
            break;
        case 'event_image_url':
        case 'event_url':
            $type = ($type != 'url')?'text':'url';
        }
    }
    if ($required) {
        $more .= " required='required'";
    }
    $html_by_type = array(
        'number' => '<input type="number" id="%s" name="event[%s]" min="0" step="any" %s/>',
        'text' => '<input type="text" id="%s" name="event[%s]" %s/>',
        'url' => '<input type="url" id="%s" name="event[%s]" %s/>',
        'localized_time' => '<input type="text" id="%s" name="%s" %s/>',
        'localized_datetime' => '<input type="text" id="%s" name="%s" %s/>',
        'textarea' => '<textarea id="%s" name="event[%s]" %s></textarea>',
        'hidden' => '<input type="hidden" id="%s" name="event[%s]" %s />',
        'attr-textarea' => '<textarea id="%s" name="event_attributes[%s]" %s></textarea>',
        'attr-text' => '<input type="text" id="%s" name="event_attributes[%s]" %s />',
        'attr-tel' => '<input type="tel" id="%s" name="event_attributes[%s]" %s />',
        'attr-email' => '<input type="email" id="%s" name="event_attributes[%s]" %s />',
        'attr-hidden' => '<input type="hidden" id="%s" name="event_attributes[%s]" %s />',
        'prop-text' => '<input type="text" id="%s" name="event_properties[%s]" %s />',
        'prop-hidden' => '<input type="hidden" id="%s" name="event_properties[%s]" %s />',
        'prop-textarea' => '<textarea id="%s" name="event_properties[%s]" %s></textarea>',
    );

    $field_id = ($field_id)?$field_id:$field;

    $res = '';
    switch($type) {
    case 'wysiwyg_textarea':
        if ($eme_fs_options['allow_upload'] && is_user_logged_in()) {
            $editor_settings=['media_buttons'=>true,'textarea_name'=>"event[$field]"];
	    $allow_upload="yes";
	} else {
            $editor_settings=['media_buttons'=>false,'textarea_name'=>"event[$field]"];
	    $allow_upload="no";
	}
        $editor_settings['editor_class'] = "eme_fs_wysiwig_editor_width";
	if (get_option('eme_htmleditor') == 'tinymce') {
            ob_start(); // Start output buffer
            wp_editor('',$field_id,$editor_settings);
            // Store the printed data in $editor variable
            $res = ob_get_clean();
	}
	if (get_option('eme_htmleditor') == 'jodit') {
            $res = "<textarea class='eme-fs-editor' name='$field_id' id='$field_id' rows='6' data-allowupload='$allow_upload'></textarea>";
	}
        break;
    case 'localized_datetime':
        //echo sprintf($html_by_type['hidden'], $field_id, $field, '', $more);
        $res = sprintf($html_by_type['hidden'], $field_id, $field, $more);
        $res .= sprintf($html_by_type[$type], $localized_field_id, "event[$localized_field_id]", $more);
        break;
    case 'status_select':
        $res = eme_fs_getstatusselect($more);
        break;
    case 'category_select':
        $res = eme_fs_getcategoriesselect($more);
        break;
    case 'category_radio':
        $res = eme_fs_getcategoriesradio($more);
        break;
    case 'recaptcha':
    case 'hcaptcha':
    case 'cfcaptcha':
    case 'captcha':
        $res = eme_generate_captchas_html();
        break;
    case 'binary':
        $res = eme_fs_getbinaryselect("event[".$field."]",$field_id,0);
        break;
    case 'prop-binary':
        $res = eme_fs_getbinaryselect("event_properties[".$field_id."]",$field_id,0);
        break;
    default:
        $res = sprintf($html_by_type[$type], $field_id, $field_id, $more);
        break;
    }
    return $res;
}

function eme_fs_getcategories() {
    $categories = eme_get_categories();
    if (has_filter('eme_fs_categories_filter')) $categories=apply_filters('eme_fs_categories_filter',$categories);
    return($categories);
}

function eme_fs_getcategoriesradio( $more ) {
    $categories = eme_fs_getcategories();
    $category_radios = array();
    if ( $categories ) {
        // the first value should be empty, so if it is required, the browser can require it ...
        $category_radios[] = '<input type="hidden" name="event[event_category_ids]" value="0" '.$more.' />';
        foreach ($categories as $category){
            $category_radios[] = sprintf('<input type="radio" id="event_category_ids_%s" value="%s" name="event[event_category_ids]" %s />', $category['category_id'], $category['category_id'], $checked);
            $category_radios[] = sprintf('<label for="event_category_ids_%s">%s</label><br/>', $category['category_id'], $category['category_name']);
        }
    }
    return implode("\n", $category_radios);
}

function eme_fs_getcategoriesselect( $more ) {
    $category_select = array();
    $category_select[] = '<select id="event_category_ids" name="event[event_category_ids]" '.$more.' >';
    $categories = eme_fs_getcategories();
    if ( $categories ) {
        // the first value should be empty, so if it is required, the browser can require it ...
        $category_select[] = '<option value="">&nbsp;</option>';
        foreach ($categories as $category){
            $category_select[] = sprintf('<option value="%s">%s</option>', $category['category_id'], $category['category_name']);
        }
    }
    $category_select[] = '</select>';
    return implode("\n", $category_select);
}

function eme_fs_getstatusselect( $more ) {
    $event_status_array = eme_status_array ();
    $status_select = array();
    $status_select[] = '<select id="event_status" name="event[event_status]" '.$more.' >';
    // the first value should be empty, so if it is required, the browser can require it ...
    $category_select[] = '<option value="">'.__('Event Status','events-made-easy').'</option>';
    foreach ($event_status_array as $event_status_key=>$event_status_value) {
        $status_select[] = "<option value='$event_status_key'> $event_status_value</option>";
    }
    $status_select[] = '</select>';
    return implode("\n", $status_select);
}

function eme_fs_getbinaryselect( $name, $field_id, $default ) {
    $val = "<select name='$name' id='$field_id'>";
    $selected_YES="";
    $selected_NO="";
    if ($default==1)
        $selected_YES = "selected='selected'";
    else
        $selected_NO = "selected='selected'";
    $val.= "<option value='0' $selected_NO>".__('No', 'events-made-easy')."</option>";
    $val.= "<option value='1' $selected_YES>".__('Yes', 'events-made-easy')."</option>";
    $val.=" </select>";
    return $val;
}

function eme_fs_process_newevent() {
    $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
    $eme_fs_options = get_option('eme_fs');
    $captcha_res = eme_check_captcha( $eme_fs_options );
    if (empty($eme_fs_options['success_message']))
        $eme_fs_options['success_message'] = __('New event succesfully created.','events-made-easy');
    if (empty($_POST['event'])) {
        $event_data = [];
    } else {
        $event_data = eme_kses($_POST['event']);
    }
    // add in the event_attributes and properties
    if (isset($_POST['event_attributes']) && !empty($_POST['event_attributes'])) {
        $event_data['event_attributes'] = eme_kses($_POST['event_attributes']);
    }
    if (isset($_POST['event_properties']) && !empty($_POST['event_properties'])) {
        $event_data['event_properties'] = eme_kses($_POST['event_properties']);
    }
    if (isset($event_data['event_properties']['all_day']) && $event_data['event_properties']['all_day']==1)
        $all_day=1;
    else
        $all_day=0;

    $eme_fs_event_errors = [];
    if ( !isset($event_data['event_name']) || empty($event_data['event_name']) ) {
        $eme_fs_event_errors[] = __('Please enter a name for the event', 'events-made-easy');
    }

    if ( isset($event_data['event_notes']) && empty($event_data['event_notes']) ) {
        $eme_fs_event_errors[] = __('Please enter an event description', 'events-made-easy');
    }
    if ( isset($event_data['location_description']) && empty($event_data['location_description']) ) {
        $eme_fs_event_errors[] = __('Please enter a location description', 'events-made-easy');
    }
    if ( !isset($event_data['event_start_date']) || empty($event_data['event_start_date']) ) {
        $eme_fs_event_errors[] = __('Enter the event\'s start date', 'events-made-easy');
    }
    if ( !isset($event_data['event_end_date']) || empty($event_data['event_end_date']) ) {
        $event_data['event_end_date'] = $event_data['event_start_date'];
    }

    if ( isset( $event_data['event_category_ids'] ) && eme_is_numeric_array( eme_sanitize_request( $event_data['event_category_ids'] ) ) ) {
        $event_data['event_category_ids'] = join( ',', eme_sanitize_request( $event_data['event_category_ids'] ) );
    }

    if ($all_day) {
        $event_start_time = '00:00';
        $event_end_time = "23:59";
    } else {
        if ( ! empty( $event_data['localized_start_time'] ) ) {
            $start_date_obj   = ExpressiveDate::createFromFormat( EME_WP_TIME_FORMAT, eme_sanitize_request( $event_data['localized_start_time'] ), ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
            $event_start_time = $start_date_obj->format( 'H:i:00' );
        } else {
            $event_start_time = '00:00:00';
        }
        if ( ! empty( $event_data['localized_end_time'] ) ) {
            $end_date_obj   = ExpressiveDate::createFromFormat( EME_WP_TIME_FORMAT, eme_sanitize_request( $event_data['localized_end_time'] ), ExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
            $event_end_time = $end_date_obj->format( 'H:i:00' );
        } else {
            $event_end_time = '23:59:59';
        }
    }

    $event_data['event_start']=$event_data['event_start_date'].' '.$event_start_time;
    $event_data['event_end']=$event_data['event_end_date'].' '.$event_end_time;
    if ($event_data['event_start'] > $event_data['event_end']) {
        $eme_fs_event_errors[] =  __('The end date/time must occur <strong>after</strong> the start date/time', 'events-made-easy');
    }

    $res_html = '';
    $res_code = 'OK';
    if ( empty($eme_fs_event_errors) ) {
        $force=0;
        if ($eme_fs_options['force_location_creation'])
            $force=1;
        if (empty($event_data['location_id']))
            $event_data['location_id'] = eme_fs_processlocation($event_data, $force);

        if (empty($event_data['event_status']))
            $event_data['event_status']=$eme_fs_options['auto_publish'];
        if (!isset($event_data['event_category_ids']) && !empty($eme_fs_options['default_cat']))
            $event_data['event_category_ids']=$eme_fs_options['default_cat'];

        // make sure all event properties are set as expected
        if (!empty($event_data['event_properties'])) {
            // the not-set properties need to be treated as if a new event, so set new_event param to 1
            $event_data['event_properties'] = eme_init_event_props($event_data['event_properties'], 1);
        }

        $new_event = eme_new_event();
        $event_data = array_merge($new_event,$event_data);

        $pg_count = eme_fs_event_count_pgs( );
        if ($eme_fs_options['price']>0 && $pg_count>0) {
            $event_data['event_status'] = EME_EVENT_STATUS_FS_DRAFT;
        }
        $event_data = eme_sanitize_event($event_data);
	$validation_result = '';
	if (has_filter('eme_fs_validate_event_filter')) {
		$validation_result = apply_filters( 'eme_fs_validate_event_filter', $event_data );
	}
        if (empty($validation_result)) {
            $validation_result = eme_validate_event ( $event_data );
        }

        if (empty($validation_result)) { // meaning all is ok
            if (has_filter('eme_fs_event_insert_filter')) $event_data=apply_filters('eme_fs_event_insert_filter',$event_data);
            $event_id = eme_db_insert_event($event_data);
            if ($event_id) {
                eme_captcha_remove( $captcha_res );
                eme_event_store_answers($event_id);
                eme_upload_files( $event_id, 'events' );
                $event = eme_get_event($event_id);
                eme_email_fs_event_action( $event, 'newevent' );
                if (has_action('eme_fs_submit_event_action')) {
                    do_action('eme_fs_submit_event_action',$event);
                }
                if ($eme_fs_options['price']>0 && $pg_count>0) {
                    $payment_id  = eme_create_fs_event_payment($event_id);
                    $payment     = eme_get_payment( $payment_id );
                    $res_html = eme_js_redirect(eme_payment_url($payment), $eme_fs_options['redirect_timeout']);
                    if ($eme_fs_options['redirect_timeout'] == 0) {
                        $res_code = 'REDIRECT_IMM';
                    } else {
                        $res_html .= eme_replace_event_placeholders($eme_fs_options['success_message'], $event);
                    }
                } elseif ($eme_fs_options['always_success_message']) {
                    $res_html = eme_replace_event_placeholders($eme_fs_options['success_message'], $event);
                } elseif ((is_user_logged_in() && $event['event_status'] != EME_EVENT_STATUS_DRAFT) || 
                    $event['event_status'] == EME_EVENT_STATUS_PUBLIC ) {
                    $res_html = eme_js_redirect(eme_event_url($event), $eme_fs_options['redirect_timeout']);
                    if ($eme_fs_options['redirect_timeout'] == 0) {
                        $res_code = 'REDIRECT_IMM';
                    } else {
                        $res_html .= eme_replace_event_placeholders($eme_fs_options['success_message'], $event);
                    }
                } else {
                    $res_html = eme_replace_event_placeholders($eme_fs_options['success_message'], $event);
                }
            } else {
                $eme_fs_event_errors[] = __('Database insert failed!','events-made-easy');
            }
        } else {
            $eme_fs_event_errors[] = $validation_result;
        }
    }

    if (empty($eme_fs_event_errors)) {
        return [
            'Result'      => $res_code,
            'htmlmessage' => $res_html
        ];

    } else {
        return [
            'Result'      => 'NOK',
            'htmlmessage' => join('<br>',$eme_fs_event_errors)
        ];
    }
}

function eme_fs_processlocation( $event_data, $force=0 ) {
    $location = eme_new_location();
    // for backwards compatibility
    if (isset($event_data['location_address'])) {
        $location['location_address1'] = $event_data['location_address1'];
    }
    if (isset($event_data['location_town'])) {
        $location['location_city'] = $event_data['location_town'];
    }
    $location['location_name'] = isset($event_data['location_name']) ? $event_data['location_name'] : '';
    $location['location_description'] = isset($event_data['location_description']) ? $event_data['location_description'] : '';
    $location['location_address1'] = isset($event_data['location_address1']) ? $event_data['location_address1'] : '';
    $location['location_address2'] = isset($event_data['location_address2']) ? $event_data['location_address2'] : '';
    $location['location_city'] = isset($event_data['location_city']) ? $event_data['location_city'] : '';
    $location['location_state'] = isset($event_data['location_state']) ? $event_data['location_state'] : '';
    $location['location_zip'] = isset($event_data['location_zip']) ? $event_data['location_zip'] : '';
    $location['location_country'] = isset($event_data['location_country']) ? $event_data['location_country'] : '';
    $location['location_latitude'] = isset($event_data['location_latitude']) ? $event_data['location_latitude'] : '';
    $location['location_longitude'] = isset($event_data['location_longitude']) ? $event_data['location_longitude'] : '';
    if (empty($location['location_name']) && empty($location['location_address1']) && empty($location['location_latitude']) && empty($location['location_longitude'])) {
        return 0;
    }
    $location = eme_sanitize_location($location);
    $location_id=eme_get_identical_location_id($location);
    if (!$location_id ) {
        $validation_result = eme_validate_location ( $location );
        if ($validation_result == "OK") {
            $location_id = eme_insert_location($location, $force);
            eme_location_store_answers($location_id);
        }
    }
    return $location_id;
}

add_action( 'wp_ajax_eme_frontend_submit', 'eme_frontend_submit_ajax' );
add_action( 'wp_ajax_nopriv_eme_frontend_submit', 'eme_frontend_submit_ajax' );
function eme_frontend_submit_ajax() {
    // check for spammers as early as possible
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

    $eme_fs_options = get_option('eme_fs');
    $is_user_logged_in=is_user_logged_in();
    if ((!$is_user_logged_in && !$eme_fs_options['guest_submit']) || ($is_user_logged_in && !current_user_can($eme_fs_options['cap_add_event'])) ) {
        $form_html = __("Sorry, but you're not allowed to submit new events.","events-made-easy");
        echo wp_json_encode(
            [
                'Result'      => 'NOK',
                'htmlmessage' => $form_html,
            ]
        );
        wp_die();
    }

    echo wp_json_encode(eme_fs_process_newevent());
    wp_die();
}
