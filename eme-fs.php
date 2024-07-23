<?php

if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly.
}


function eme_add_event_form_shortcode( $atts ) {
	eme_enqueue_frontend();
	$eme_fs_options = get_option('eme_fs');
	if (!$eme_fs_options['success_page']) {
?>
	    <div class="emefs_error">
	    <h2><?php _e('Basic Configuration is Missing', 'events-made-easy-frontend-submit'); ?></h2>
	    <p><?php _e('You have to configure the page where successful submissions will be redirected to.', 'events-made-easy-frontend-submit'); ?></p>
	    </div>
<?php
		return false;
	}
	$is_user_logged_in=is_user_logged_in();
	if ((!$is_user_logged_in && !$eme_fs_optionsoptions['guest_submit']) || ($is_user_logged_in && !current_user_can($eme_fs_options['cap_add_event'])) ) {
		if ($eme_fs_options['redirect_to_login']) {
			//auth_redirect();
			global $wp;
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
			if (is_user_logged_in())
				$login_url = wp_login_url($current_url,true);
			else
				$login_url = wp_login_url($current_url);
			echo eme_js_redirect($login_url);
		} else {
			if (empty($eme_fs_options['guest_not_allowed_text']))
				$eme_fs_options['guest_not_allowed_text']=__("Sorry, but you're not allowed to submit new events.","events-made-easy-frontend-submit");
?>
		 <div class="emefs_not_allowed">
<?php
			echo $eme_fs_options['guest_not_allowed_text'];
?>
		 </div>
<?php
		}
		return false;
	}

	$map_enabled = intval($eme_fs_options['map_enabled']);
	wp_enqueue_style( 'eme-leaflet-css' );
	wp_enqueue_style( 'emefs_stylesheet', EME_PLUGIN_URL . 'css/emefs.css', [], EME_VERSION );
	$translation_array = [ 'ajax_url' => admin_url( 'admin-ajax.php' ), 'map_enabled' => $map_enabled, 'frontendnonce' => wp_create_nonce( 'eme_frontend' ) ];
	wp_localize_script( 'eme-fs-map', 'emefs', $translation_array );
	wp_enqueue_script( 'eme-fs-map' );
        extract( shortcode_atts( [ 'id' => 0 ], $atts ) );

	$form_html = '<div id="new_event_form">
        <form id="new_event" name="new_event" method="post" enctype="multipart/form-data" action="'. get_permalink() .'">';
	$form_html .= eme_event_fs_form( $id );
	// always add the honeypot field
	$form_html .= "<span id='honeypot_check'><input type='text' name='honeypot_check' value='' autocomplete='off'></span></form>";
	return $form_html;
}

function eme_event_fs_form( $template_id ) {
	$eme_fs_options = get_option('eme_fs');
        if ( $template_id ) {
                $format = eme_get_template_format( $template_id );
	}
	if (empty($format)) {
		$format = $eme_fs_options['form_format'];
	}

        $captcha_set = 0;
        if ( $eme_fs_options['use_recaptcha'] ) {
                $format = eme_add_captcha_submit( $format, 'recaptcha' );
        } elseif ( $eme_fs_options['use_hcaptcha'] ) {
                $format = eme_add_captcha_submit( $format, 'hcaptcha' );
        } elseif ( $eme_fs_options['use_cfcaptcha'] ) {
                $format = eme_add_captcha_submit( $format, 'cfcaptcha' );
        } elseif ( $eme_fs_options['use_captcha'] ) {
                $format = eme_add_captcha_submit( $format, 'captcha' );
        } else {
                $format = eme_add_captcha_submit( $format );
        }

        $needle_offset = 0;
        preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
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

		#_FIELD{} 
		#_ATTR{} of #_ATTR{}{} ==> zie #_SUBSCRIBE_TO_GROUP
		#_PROP{} of #_PROP{}{}
		if ( preg_match( '/#_FIELD\{(.+)\}(\{.+?\})?$/', $result, $matches ) ) {
			$field = $matches[1];
			if ( isset( $matches[2] ) ) {
				$type = $matches[2];
			}
			$replacement = eme_get_fs_field_html(field: $field, required: $required, type: $type);

                } elseif ( preg_match( '/#_ATT\{(.+?)\}(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			$att = $matches[1];
			if ( isset( $matches[2] ) ) {
				$type = $matches[2];
			}
			if ( isset( $matches[3] ) ) {
				$more = $matches[3];
			}
			$replacement = eme_get_fs_field_html('event-attributes', 'att-'.$type , $more , $required, $att);

                } elseif ( preg_match( '/#_PROP\{(.+?)\}(\{.+?\})?/', $result, $matches ) ) {
			$prop = $matches[1];
			if ( isset( $matches[2] ) ) {
				$more = $matches[2];
			}
			// $type will get overwritten in the function call, but here it is easier to just 
			// give it, otherwise I would need to specify all other args individually
			$replacement = eme_get_fs_field_html('event-properties', 'prop-'.$type , $more , $required, $prop);

                } elseif ( preg_match( '/#_CUSTOMFIELD\{(.+?)\}$/', $result ) ) {
			$formfield = eme_sanitize_request($matches[1]);
			if ($formfield && ($formfield['field_purpose']=='events' || $formfield['field_purpose']=='locations')) {
				$postfield_name="FIELD".$formfield['field_id'];
				if ($formfield['field_required'])
					$required=1;
				$replacement = eme_get_formfield_html($formfield,$postfield_name,'',$required);
			}
                } elseif ( preg_match( '/#_CFCAPTCHA$/', $result ) ) {
                        if ( $eme_fs_options['use_cfcaptcha'] && ! $captcha_set ) {
                                $replacement = eme_load_cfcaptcha_html();
                                $captcha_set = 1;
                        }
                } elseif ( preg_match( '/#_HCAPTCHA$/', $result ) ) {
                        if ( $eme_fs_options['use_hcaptcha'] && ! $captcha_set ) {
                                $replacement = eme_load_hcaptcha_html();
                                $captcha_set = 1;
                        }
                } elseif ( preg_match( '/#_RECAPTCHA$/', $result ) ) {
                        if ( $eme_fs_options['use_recaptcha'] && ! $captcha_set ) {
                                $replacement = eme_load_recaptcha_html();
                                $captcha_set = 1;
                        } 
                } elseif ( preg_match( '/#_CAPTCHA$/', $result ) ) {
                        if ( $eme_fs_options['use_captcha'] && ! $captcha_set ) {
                                $replacement = eme_load_captcha_html();
                                $captcha_set = 1;
                                if ( ! $eme_is_admin_request ) {
					$required = 1;
                                }
                        }
                } elseif ( preg_match( '/#_MAP$/', $result ) ) {
			$replacement = "<div id='event-map'></div>";
                } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
                        if ( isset( $matches[1] ) ) {
                                // remove { and } (first and last char of second match)
                                $label = substr( $matches[1], 1, -1 );
                        } else {
                                $label = __( 'Create event', 'events-made-easy' );
                        }
                        $replacement = "<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' style='display:none;'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_nowptrans_esc_html( $label ) . "'>";
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

	return $format;

}

function eme_get_fs_field_html($field = false, $type = 'text', $more = '', $required=0, $field_id = false) {
      if (!$field)
         return false;
      $localized_field_id='';
      $eme_fs_options = get_option('eme_fs');
      // if the type is not hidden, set it to the sensible value
      if ($type != 'hidden') {
              switch($field) {
              case 'event_notes':
              case 'location_description':
                      if ($eme_fs_options['use_wysiwyg'])
                              $type = 'wysiwyg_textarea';
                      else
                              $type = 'textarea';
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
              case 'location_latitude':
              case 'location_longitude':
                      $type = 'hidden';
                      break;
              case 'event_start_time':
                      $localized_field_id='localized-start-time';
                      $more .= "required='required' readonly='readonly' class='eme_formfield_ftime' data-alt-field='event_start_time'";
                      $type = 'localized_text';
                      break;
              case 'event_end_time':
                      $localized_field_id='localized-end-time';
                      $more .= "readonly='readonly' class='eme_formfield_ftime' data-alt-field='event_end_time'";
                      $type = 'localized_text';
                      break;
              case 'event_start_date':
                      $localized_field_id='localized-start-date';
                      $more .= "required='required' readonly='readonly' class='eme_formfield_fdate' data-alt-field='event_start_date'";
                      $type = 'localized_text';
                      break;
              case 'event_end_date':
                      $localized_field_id='localized-end-date';
                      $more .= "readonly='readonly' class='eme_formfield_fdate' data-alt-field='event_end_date'";
                      $type = 'localized_text';
                      break;
              case 'event_name':
                      $more .= "required='required'";
                      $type = 'text';
                      break;
              case 'event_attributes':
              case 'event_properties':
                      break;
              case 'recaptcha':
                      if ($eme_fs_options['use_recaptcha'])
                              $type = 'recaptcha';
                      else
                              $type = '';
                      break;
              case 'hcaptcha':
                      if ($eme_fs_options['use_hcaptcha'])
                              $type = 'hcaptcha';
                      else
                              $type = '';
                      break;
              case 'cfcaptcha':
                      if ($eme_fs_options['use_cfcaptcha'])
                              $type = 'cfcaptcha';
                      else
                              $type = '';
                      break;
              case 'captcha':
                      if ($eme_fs_options['use_captcha'])
                              $type = 'captcha';
                      else
                              $type = '';
                      break;
              case 'event_image_url':
              case 'event_url':
                      $type = ($type != 'url')?'text':'url';
              }
      }
      if ($required) {
	      $more .= "required='required'";
      }
      $html_by_type = array(
            'number' => '<input type="number" id="%s" name="event[%s]" min="0" step="any" value="" %s/>',
            'text' => '<input type="text" id="%s" name="event[%s]" value="" %s/>',
            'url' => '<input type="url" id="%s" name="event[%s]" value="" %s/>',
            'localized_text' => '<input type="text" id="%s" name="%s" value="" %s/>',
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

      switch($type) {
         case 'wysiwyg_textarea':
            if ($eme_fs_options['allow_upload'])
                    $editor_settings=array('media_buttons'=>true,'textarea_name'=>"event[$field]");
            else
                    $editor_settings=array('media_buttons'=>false,'textarea_name'=>"event[$field]");
	    ob_start(); // Start output buffer
            wp_editor('',$field_id,$editor_settings);
	    // Store the printed data in $editor variable
	    return ob_get_clean();
            break;
         case 'localized_text':
            //echo sprintf($html_by_type['hidden'], $field_id, $field, '', $more);
            $res = sprintf($html_by_type['hidden'], $field_id, $field, $more);
            $res .= sprintf($html_by_type[$type], $localized_field_id, "event[$localized_field_id]", $more);
	    return $res;
            break;
         case 'status_select':
            return eme_fs_getstatusselect($more);
            break;
         case 'category_select':
            return eme_fs_getcategoriesselect($more);
            break;
         case 'category_radio':
            return eme_fs_getcategoriesradio($more);
            break;
         case 'recaptcha':
            return eme_load_recaptcha_html();
            break;
         case 'hcaptcha':
            return eme_load_hcaptcha_html();
            break;
         case 'cfcaptcha':
            return eme_load_cfcaptcha_html();
            break;
         case 'captcha':
            return eme_load_captcha_html();
            break;
         case 'binary':
            return eme_fs_getbinaryselect("event[".$field."]",$field_id,0);
            break;
         case 'prop-binary':
            return eme_fs_getbinaryselect("event_properties[".$field_id."]",$field_id,0);
            break;
         case 'attr-textarea':
         case 'prop-hidden':
         case 'prop-text':
         case 'attr-hidden':
         case 'attr-text':
         case 'attr-tel':
         case 'attr-email':
         case 'attr-number':
         case 'textarea':
         case 'hidden':
         case 'number':
         case 'text':
         case 'url':
            // for backwards compatibility
            if ($field == "location_address") $field="location_address1";
            if ($field == "location_town") $field="location_city";
            return sprintf($html_by_type[$type], $field_id, $field_id, $more);
            break;
      }
}

function eme_fs_getcategories() {
	$categories = eme_get_categories();
	if (has_filter('eme_fs_categories_filter')) $categories=apply_filters('eme_fs_categories_filter',$categories);
	return($categories);
}

function eme_fs_getcategoriesradio($more) {
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

function eme_fs_getcategoriesselect($more) {
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

function eme_fs_getstatusselect($more) {

      $event_status_array = eme_status_array ();
      $status_select = array();
      $status_select[] = '<select id="event_status" name="event[event_status]" '.$more.' >';
         // the first value should be empty, so if it is required, the browser can require it ...
         $category_select[] = '<option value="0">'.__('Event Status','events-made-easy').'</option>';
         foreach ($event_status_array as $event_status_key=>$event_status_value) {
            $status_select[] = "<option value='$event_status_key'> $event_status_value</option>";
         }
      $status_select[] = '</select>';
      return implode("\n", $status_select);
   }

function eme_fs_getbinaryselect($name,$field_id,$default) {
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

add_action( 'wp_ajax_eme_fs_locations_list', 'eme_fs_ajax_locations_list' );
add_action( 'wp_ajax_nopriv_eme_fs_locations_list', 'eme_fs_ajax_locations_list' );
function eme_fs_ajax_locations_list() {
        $res = array();
        if (!isset($_POST["q"])) {
                echo json_encode($res);
                return;
        }
        check_ajax_referer( 'eme_frontend', 'frontend_nonce' );
        $locations = eme_search_locations(eme_sanitize_request($_POST["q"]));
        foreach($locations as $item) {
                $record = array();
                $record['id']       = $item['location_id'];
                $record['name']     = eme_trans_sanitize_html($item['location_name']);
                $record['address1'] = eme_trans_sanitize_html($item['location_address1']);
                $record['address2'] = eme_trans_sanitize_html($item['location_address2']);
                $record['city']     = eme_trans_sanitize_html($item['location_city']);
                $record['state']    = eme_trans_sanitize_html($item['location_state']);
                $record['zip']      = eme_trans_sanitize_html($item['location_zip']);
                $record['country']  = eme_trans_sanitize_html($item['location_country']);
                $record['latitude'] = eme_trans_sanitize_html($item['location_latitude']);
                $record['longitude']= eme_trans_sanitize_html($item['location_longitude']);
                $res[]  = $record;
        }

        print json_encode($res);
        wp_die();
}



