<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_filter_form_shortcode( $atts ) {
	eme_enqueue_frontend();
    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

	$atts = shortcode_atts(
		    [
				'multiple'    => 0,
				'multisize'   => 5,
				'scope_count' => 12,
				'submit'      => 'Submit',
				'category'    => '',
				'notcategory' => '',
				'template_id' => 0,
			],
		    $atts
	);
	$multiple = filter_var( $atts['multiple'], FILTER_VALIDATE_BOOLEAN );
	$multisize = intval($atts['multisize']);
	$scope_count = intval($atts['scope_count']);
	$template_id = intval($atts['template_id']);
	$category = eme_sanitize_request($atts['category']);
	$notcategory = eme_sanitize_request($atts['notcategory']);
	$submit = esc_attr( eme_translate( $atts['submit'] ) );

	if ( $template_id ) {
		// when using a template, don't bother with fields, the template should contain the things needed
		$filter_form_format = eme_get_template_format( $template_id );
	} else {
		$filter_form_format = get_option( 'eme_filter_form_format' );
	}

	if ( strstr( $filter_form_format, '#_SUBMIT' ) ) {
		$submit_to_added = 0;
	} else {
		$submit_to_added = 1;
	}

	$content = eme_replace_filter_form_placeholders( $filter_form_format, $multiple, $multisize, $scope_count, $category, $notcategory );
	# using the current page as action, so we can leave action empty in the html form definition
	# this helps to keep the language and any other parameters, and works with permalinks as well
    $form_id = "eme_".eme_random_id(); // JS selectors need to start with a letter, so to be sure we prefix it
	$form  = "<form id='eme_filter_form-$form_id' name='eme_filter_form' action='' method='POST'>";
	$form .= "<input type='hidden' name='eme_eventAction' value='filter'>";
	$form .= $content;
	if ( $submit_to_added ) {
		$form .= "<input name='eme_submit_button' class='eme_submit_button' type='submit' value='$submit'>";
	}
	$form .= '</form>';
	return $form;
}

function eme_create_week_scope( $past_count, $future_count, $eventful = 0 ) {
	$start_of_week = get_option( 'start_of_week' );
	$eme_date_obj  = new emeExpressiveDate( 'now', EME_TIMEZONE );
	if ($past_count) {
		$eme_date_obj->minusWeeks($past_count);
	}
	$count = $past_count + $future_count;
	$eme_date_obj->setWeekStartDay( $start_of_week );
	$scope = [];
	for ( $i = 0; $i < $count; $i++ ) {
		$limit_start = $eme_date_obj->copy()->startOfWeek()->format( 'Y-m-d' );
		$limit_end   = $eme_date_obj->copy()->endOfWeek()->format( 'Y-m-d' );
		$this_scope  = $limit_start . '--' . $limit_end;
		if ( $eventful ) {
			$check_for_events = eme_are_events_available( $this_scope );
			if ( ! $check_for_events ) {
				continue;
			}
		}
		$scope_text           = eme_localized_date( $limit_start, EME_TIMEZONE ) . ' -- ' . eme_localized_date( $limit_end, EME_TIMEZONE );
		$scope[ $this_scope ] = $scope_text;
		$eme_date_obj->addOneWeek();
	}
	if ( has_filter( 'eme_week_scope_filter' ) ) {
		$scope = apply_filters( 'eme_week_scope_filter', $scope );
	}
	return $scope;
}

function eme_create_month_scope( $past_count, $future_count, $eventful = 0 ) {
	$scope        = [];
	$scope[0]     = __( 'Select Month', 'events-made-easy' );
	$eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
	if ($past_count) {
		$eme_date_obj->minusMonths($past_count);
	}
	$count = $past_count + $future_count;
	for ( $i = 0; $i < $count; $i++ ) {
		$limit_start   = $eme_date_obj->startOfMonth()->format( 'Y-m-d' );
		$days_in_month = $eme_date_obj->getDaysInMonth();
		$limit_end     = $eme_date_obj->format( "Y-m-$days_in_month" );
		$this_scope    = "$limit_start--$limit_end";
		if ( $eventful ) {
			$check_for_events = eme_are_events_available( $this_scope );
			if ( ! $check_for_events ) {
				continue;
			}
		}
		$scope_text           = eme_localized_date( $limit_start, EME_TIMEZONE, get_option( 'eme_show_period_monthly_dateformat' ) );
		$scope[ $this_scope ] = $scope_text;
		$eme_date_obj->addOneMonth();
	}
	if ( has_filter( 'eme_month_scope_filter' ) ) {
		$scope = apply_filters( 'eme_month_scope_filter', $scope );
	}
	return $scope;
}

function eme_create_year_scope( $past_count, $future_count, $eventful = 0 ) {
	$scope    = [];
	$scope[0] = __( 'Select Year', 'events-made-easy' );

	$eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
	if ($past_count) {
		$eme_date_obj->minusYears($past_count);
	}
	$count = $past_count + $future_count;
	for ( $i = 0; $i < $count; $i++ ) {
		$year        = $eme_date_obj->getYear();
		$limit_start = "$year-01-01";
		$limit_end   = "$year-12-31";
		$this_scope  = "$limit_start--$limit_end";
		if ( $eventful ) {
			$check_for_events = eme_are_events_available( $this_scope );
			if ( ! $check_for_events ) {
				continue;
			}
		}
		$scope_text           = eme_localized_date( $limit_start, EME_TIMEZONE, get_option( 'eme_show_period_yearly_dateformat' ) );
		$scope[ $this_scope ] = $scope_text;
		$eme_date_obj->addOneYear();
	}
	if ( has_filter( 'eme_year_scope_filter' ) ) {
		$scope = apply_filters( 'eme_year_scope_filter', $scope );
	}
	return $scope;
}

function eme_replace_filter_form_placeholders( $format, $multiple, $multisize, $scope_count, $category, $notcategory ) {
	// if one of these changes, also the eme_events.php needs changing for the "Next page" part
	$author_post_name          = 'eme_author_filter';
	$contact_post_name         = 'eme_contact_filter';
	$loc_post_name             = 'eme_loc_filter';
	$cat_post_name             = 'eme_cat_filter';
	$city_post_name            = 'eme_city_filter';
	$state_post_name           = 'eme_state_filter';
	$country_post_name         = 'eme_country_filter';
	$scope_post_name           = 'eme_scope_filter';
	$customfield_post_name     = 'eme_customfield_filter';

	$selected_scope    = eme_sanitize_request( $_REQUEST[ $scope_post_name ] ?? '' );
	$selected_location = eme_sanitize_request( $_REQUEST[ $loc_post_name ] ?? 0 );
	$selected_city     = eme_sanitize_request( $_REQUEST[ $city_post_name ] ?? 0 );
	$selected_state    = eme_sanitize_request( $_REQUEST[ $state_post_name ] ?? 0 );
	$selected_country  = eme_sanitize_request( $_REQUEST[ $country_post_name ] ?? 0 );
	$selected_category = 0;
	if (isset( $_REQUEST[ $cat_post_name ] )) {
		$val = eme_sanitize_request( $_REQUEST[ $cat_post_name ] );
		if (is_numeric($val)) {
			$selected_category = $val;
		} else {
			$cat_id = eme_get_category_id_by_name_slug($val);
			if (!empty($cat_id)) {
				$selected_category = $cat_id;
			}
		}
	}
	$selected_author   = eme_sanitize_request( $_REQUEST[ $author_post_name ] ?? 0 );
	$selected_contact  = eme_sanitize_request( $_REQUEST[ $contact_post_name ] ?? 0 );

    $extra_conditions_arr = [];
    if ( $category != '' ) {
        // Convert comma-separated string to array
        $extra_conditions_arr['category'] = explode( ',', $category );
    }
    if ( $notcategory != '' ) {
        // Convert comma-separated string to array
        $extra_conditions_arr['notcategory'] = explode( ',', $notcategory );
    }

	$scope_fieldcount = 0;
	$needle_offset    = 0;
	preg_match_all( '/#(ESC|URL|SINGLE|MULTIPLE)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
	foreach ( $placeholders[0] as $orig_result ) {
		$result             = $orig_result[0];
		$orig_result_needle = $orig_result[1] - $needle_offset;
		$orig_result_length = strlen( $orig_result[0] );
		$replacement        = '';
		$eventful           = 0;
		$found              = 1;

		$force_single = 0;
		if ( strstr( $result, '#SINGLE' ) ) {
			$result       = str_replace( '#SINGLE', '#', $result );
			$multiple     = 0;
			$force_single = 1;
		}
		if ( strstr( $result, '#MULTIPLE' ) ) {
			$result   = str_replace( '#MULTIPLE', '#', $result );
			$multiple = 1;
		}

		if ( preg_match( '/#_(EVENTFUL_)?FILTER_CATS(\{.+?\})?/', $result, $matches ) && get_option( 'eme_categories_enabled' ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[2], 1, -1 );
			} elseif ( $multiple ) {
				$label = __( 'Select one or more categories', 'events-made-easy' );
			} else {
				$label = __( 'Select a category', 'events-made-easy' );
			}
			$aria_label = 'aria-label="' . esc_html( $label ) . '"';

			$categories = eme_get_categories( $eventful, 'future', $extra_conditions_arr );
			if ( $categories ) {
				$cat_list = [];
				foreach ( $categories as $this_category ) {
					$id              = $this_category['category_id'];
					$cat_list[ $id ] = eme_translate( $this_category['category_name'] );
				}
				$cat_list = eme_array_remove_empty_elements( $cat_list );
				if ( ! empty( $cat_list ) ) {
					asort( $cat_list );
					if ( $multiple ) {
                        $replacement = eme_ui_multiselect( $selected_category, $cat_post_name, $cat_list, $multisize, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'", 1 );
					} else {
						$replacement = eme_ui_select( $selected_category, $cat_post_name, $cat_list, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'" );
					}
				}
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_LOCS(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[2], 1, -1 );
			} elseif ( $multiple ) {
				$label = __( 'Select one or more locations', 'events-made-easy' );
			} else {
				$label = __( 'Select a location', 'events-made-easy' );
			}
			$aria_label = 'aria-label="' . esc_html( $label ) . '"';
			$locations  = eme_get_locations( eventful: $eventful, scope: 'future', ignore_filter: true );
			if ( ! empty( $locations ) ) {
				$loc_list = [];
				foreach ( $locations as $this_location ) {
					$id              = $this_location['location_id'];
					$loc_list[ $id ] = eme_translate( $this_location['location_name'] );
				}
				$loc_list = eme_array_remove_empty_elements( $loc_list );
				if ( ! empty( $loc_list ) ) {
					asort( $loc_list );
					if ( $multiple ) {
                        $replacement = eme_ui_multiselect( $selected_location, $loc_post_name, $loc_list, $multisize, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'", 1 );
					} else {
						$replacement = eme_ui_select( $selected_location, $loc_post_name, $loc_list, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'" );
					}
				}
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_(TOWNS|CITIES|STATES|COUNTRIES)(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			// per-type config: which location field feeds the list, its post name/selected value, and default labels
			// translators: "state" refers to a geographical region (e.g., province, canton, department)
			$field_config = [
				'TOWNS'     => [ 'field' => 'location_city', 'post_name' => $city_post_name, 'selected' => $selected_city, 'label_multi' => __( 'Select one or more cities', 'events-made-easy' ), 'label_single' => __( 'Select a city', 'events-made-easy' ) ],
				'CITIES'    => [ 'field' => 'location_city', 'post_name' => $city_post_name, 'selected' => $selected_city, 'label_multi' => __( 'Select one or more cities', 'events-made-easy' ), 'label_single' => __( 'Select a city', 'events-made-easy' ) ],
				'STATES'    => [ 'field' => 'location_state', 'post_name' => $state_post_name, 'selected' => $selected_state, 'label_multi' => __( 'Select one or more states', 'events-made-easy' ), 'label_single' => __( 'Select a state', 'events-made-easy' ) ],
				'COUNTRIES' => [ 'field' => 'location_country', 'post_name' => $country_post_name, 'selected' => $selected_country, 'label_multi' => __( 'Select one or more countries', 'events-made-easy' ), 'label_single' => __( 'Select a country', 'events-made-easy' ) ],
			];
			$cfg = $field_config[ $matches[2] ];
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[3], 1, -1 );
			} elseif ( $multiple ) {
				$label = $cfg['label_multi'];
			} else {
				$label = $cfg['label_single'];
			}
			$aria_label  = 'aria-label="' . esc_html( $label ) . '"';
			$locations   = eme_get_locations( eventful: $eventful, scope: 'future', ignore_filter: true );
            $list = [];
            if (!empty($locations)) {
                foreach ( $locations as $loc ) {
                    $id           = eme_translate( $loc[ $cfg['field'] ] );
                    $list[ $id ]  = $id;
                }
                $list = eme_array_remove_empty_elements( $list );
                if ( ! empty( $list ) ) {
                    asort( $list );
                }
            }
            if (!empty($list)) {
                if ( $multiple ) {
                    $replacement = eme_ui_multiselect( $cfg['selected'], $cfg['post_name'], $list, $multisize, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'", 1 );
                } else {
                    $replacement = eme_ui_select( $cfg['selected'], $cfg['post_name'], $list, '', 0, 'eme_snapselect_allow_empty', $aria_label . " data-placeholder='$label'" );
                }
            }
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_(WEEKS|MONTHS|YEARS)(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$past_count = intval(substr( $matches[3], 1, -1 ));
			} else {
				$past_count = 0;
			}
			if ( isset( $matches[4] ) ) {
				// remove { and } (first and last char of second match)
				$future_count = intval(substr( $matches[4], 1, -1 ));
			} else {
				$future_count = $scope_count;
			}
			if ( $scope_fieldcount == 0 ) {
				switch ( $matches[2] ) {
					case 'WEEKS':
						$label       = __( 'Select Week', 'events-made-easy' );
						$aria_label  = 'aria-label="' . esc_html( $label ) . '"';
						$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_week_scope( $past_count, $future_count, $eventful ), $label, 0, '', $aria_label );
						break;
					case 'MONTHS':
						$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_month_scope( $past_count, $future_count, $eventful ) );
						break;
					case 'YEARS':
						$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_year_scope( $past_count, $future_count, $eventful ) );
						break;
				}
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_FILTER_MONTHRANGE/', $result ) ) {
			if ( $scope_fieldcount == 0 ) {
				$select_scope = __( 'Select a daterange', 'events-made-easy' );
				$replacement .= "<input type='text' id='$scope_post_name' name='$scope_post_name' placeholder='$select_scope' readonly='readonly' data-autoclose='false' data-range='true' data-multiple-separator=' -- ' data-alt-field-multiple-separator='--' data-date='' style='width: 30ch;' class='eme_formfield_fdate' >";
				eme_enqueue_datetimepicker();
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_FILTER_(CONTACT|AUTHOR)(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			// per-type config: post name, selected value, default label and filter hook
			$user_config = [
				'CONTACT' => [ 'post_name' => $contact_post_name, 'selected' => $selected_contact, 'default_label' => __( 'Event contact', 'events-made-easy' ), 'hook' => 'eme_filter_searchfilter_contact' ],
				'AUTHOR'  => [ 'post_name' => $author_post_name, 'selected' => $selected_author, 'default_label' => __( 'Event author', 'events-made-easy' ), 'hook' => 'eme_filter_searchfilter_author' ],
			];
			$cfg = $user_config[ $matches[1] ];
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[2], 1, -1 );
			} else {
				$label = $cfg['default_label'];
			}
			$args = [
				'echo'              => 0,
				'name'              => $cfg['post_name'],
				'show_option_none'  => esc_html( $label ),
				'option_none_value' => '',
				'selected'          => $cfg['selected'],
				'class'             => 'eme_snapselect_allow_empty',
			];
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$exclude = substr( $matches[3], 1, -1 );
				// check if all integers
				$exclude_arr = explode( ',', $exclude );
				if ( eme_is_integer_array( $exclude_arr ) ) {
					$args['exclude'] = $exclude_arr;
				}
			}
			// other arguments can be changed via the filter
			if ( has_filter( $cfg['hook'] ) ) {
				$args = apply_filters( $cfg['hook'], $args );
			}
			$replacement = wp_dropdown_users( $args );
		} elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
			$field_key = $matches[1];
			$formfield = eme_get_formfield( $field_key );
			if ( ! empty( $formfield ) ) {
				$postfield_name = $customfield_post_name . $formfield['field_id'];
				$entered_val    = '';
				if ( isset( $_REQUEST[ $postfield_name ] ) ) {
					$entered_val = eme_sanitize_request( $_REQUEST[ $postfield_name ] );
				}
				if ( $formfield['field_required'] ) {
					$required = 1;
				} else {
					$required = 0;
				}
				if ( $formfield['field_purpose'] == 'events' || $formfield['field_purpose'] == 'locations' ) {
					$replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, '', 0, $force_single );
				}
			}
		} elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[1], 1, -1 );
			} else {
				$label = __( 'Submit', 'events-made-easy' );
			}
			$replacement = "<input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . esc_attr( eme_translate( $label ) ) . "'>";
		} else {
			$found = 0;
		}

		if ( $found ) {
			$replacement    = apply_filters( 'eme_general', $replacement );
			$format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
			$needle_offset += $orig_result_length - strlen( $replacement );
		}
	}

	return do_shortcode( $format );
}


