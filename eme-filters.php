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
				'old_select'  => 0,
				'scope_count' => 12,
				'submit'      => 'Submit',
				'category'    => '',
				'notcategory' => '',
				'template_id' => 0,
			],
		    $atts
	);
	$multiple = filter_var( $atts['multiple'], FILTER_VALIDATE_BOOLEAN );
	$old_select = filter_var( $atts['old_select'], FILTER_VALIDATE_BOOLEAN );
	$multisize = intval($atts['multisize']);
	$scope_count = intval($atts['scope_count']);
	$template_id = intval($atts['template_id']);
	$category = eme_sanitize_request($atts['category']);
	$notcategory = eme_sanitize_request($atts['notcategory']);
	$submit = eme_trans_esc_html($atts['submit']);

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

	$content = eme_replace_filter_form_placeholders( $filter_form_format, $multiple, $multisize, $scope_count, $category, $notcategory, $old_select );
	# using the current page as action, so we can leave action empty in the html form definition
	# this helps to keep the language and any other parameters, and works with permalinks as well
	$form_id = uniqid();
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
	$eme_date_obj  = new ExpressiveDate( 'now', EME_TIMEZONE );
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
	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
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

	$eme_date_obj = new ExpressiveDate( 'now', EME_TIMEZONE );
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

function eme_replace_filter_form_placeholders( $format, $multiple, $multisize, $scope_count, $category, $notcategory, $old_select ) {
	// if one of these changes, also the eme_events.php needs changing for the "Next page" part
	$author_post_name          = 'eme_author_filter';
	$contact_post_name         = 'eme_contact_filter';
	$loc_post_name             = 'eme_loc_filter';
	$cat_post_name             = 'eme_cat_filter';
	$city_post_name            = 'eme_city_filter';
	$country_post_name         = 'eme_country_filter';
	$scope_post_name           = 'eme_scope_filter';
	$customfield_post_name     = 'eme_customfield_filter';
	$localized_scope_post_name = 'eme_localized_scope_filter';

	$selected_scope    = isset( $_REQUEST[ $scope_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $scope_post_name ] ) : '';
	$selected_location = isset( $_REQUEST[ $loc_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $loc_post_name ] ) : 0;
	$selected_city     = isset( $_REQUEST[ $city_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $city_post_name ] ) : 0;
	$selected_country  = isset( $_REQUEST[ $country_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $country_post_name ] ) : 0;
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
	$selected_author   = isset( $_REQUEST[ $author_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $author_post_name ] ) : 0;
	$selected_contact  = isset( $_REQUEST[ $contact_post_name ] ) ? eme_sanitize_request( $_REQUEST[ $contact_post_name ] ) : 0;

	$extra_conditions_arr = [];
	if ( $category != '' ) {
		$extra_conditions_arr[] = "(category_id IN ($category))";
	}
	if ( $notcategory != '' ) {
		$extra_conditions_arr[] = "(category_id NOT IN ($notcategory))";
	}
	$extra_conditions = implode( ' AND ', $extra_conditions_arr );

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
			$aria_label = 'aria-label="' . eme_esc_html( $label ) . '"';

			$categories = eme_get_categories( $eventful, 'future', $extra_conditions );
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
						if ( $old_select ) {
							$replacement = eme_ui_multiselect( $selected_category, $cat_post_name, $cat_list, $multisize, $label, 0, '', $aria_label );
						} else {
							$replacement = eme_ui_multiselect( $selected_category, $cat_post_name, $cat_list, $multisize, $label, 0, 'eme_select2_filter', $aria_label . "data-placeholder='$label'", 1 );
						}
					} else {
						$replacement = eme_ui_select( $selected_category, $cat_post_name, $cat_list, $label, 0, 'eme_select2_filter', $aria_label );
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
			$aria_label = 'aria-label="' . eme_esc_html( $label ) . '"';
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
						if ( $old_select > 1 ) {
							$replacement = eme_ui_multiselect( $selected_location, $loc_post_name, $loc_list, $multisize, $label, 0, '', $aria_label );
						} else {
							$replacement = eme_ui_multiselect( $selected_location, $loc_post_name, $loc_list, $multisize, $label, 0, 'eme_select2_filter', $aria_label . "data-placeholder='$label'", 1 );
						}
					} else {
						$replacement = eme_ui_select( $selected_location, $loc_post_name, $loc_list, $label, 0, 'eme_select2_filter', $aria_label );
					}
				}
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_TOWNS(\{.+?\})?|#_(EVENTFUL_)?FILTER_CITIES(\{.+?\})?/', $result ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[2], 1, -1 );
			} elseif ( $multiple ) {
				$label = __( 'Select one or more cities', 'events-made-easy' );
			} else {
				$label = __( 'Select a city', 'events-made-easy' );
			}
			$aria_label = 'aria-label="' . eme_esc_html( $label ) . '"';
			$cities     = eme_get_locations( eventful: $eventful, scope: 'future', ignore_filter: true );
			if ( ! empty( $cities ) ) {
				$city_list = [];
				foreach ( $cities as $this_city ) {
					$id               = eme_translate( $this_city['location_city'] );
					$city_list[ $id ] = $id;
				}
				$city_list = eme_array_remove_empty_elements( $city_list );
				if ( ! empty( $city_list ) ) {
					asort( $city_list );
					if ( $multiple ) {
						if ( $old_select > 1 ) {
							$replacement = eme_ui_multiselect( $selected_city, $city_post_name, $city_list, $multisize, $label, 0, '', $aria_label );
						} else {
							$replacement = eme_ui_multiselect( $selected_city, $city_post_name, $city_list, $multisize, $label, 0, 'eme_select2_filter', $aria_label . "data-placeholder='$label'", 1 );
						}
					} else {
						$replacement = eme_ui_select( $selected_city, $city_post_name, $city_list, $label, 0, 'eme_select2_filter', $aria_label );
					}
				}
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_COUNTRIES(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[2], 1, -1 );
			} elseif ( $multiple ) {
				$label = __( 'Select one or more countries', 'events-made-easy' );
			} else {
				$label = __( 'Select a country', 'events-made-easy' );
			}
			$aria_label = 'aria-label="' . eme_esc_html( $label ) . '"';
			$countries  = eme_get_locations( eventful: $eventful, scope: 'future', ignore_filter: true );
			if ( ! empty( $countries ) ) {
				$country_list = [];
				foreach ( $countries as $this_country ) {
					$id                  = eme_translate( $this_country['location_country'] );
					$country_list[ $id ] = $id;
				}
				$country_list = eme_array_remove_empty_elements( $country_list );
				if ( ! empty( $country_list ) ) {
					asort( $country_list );
					if ( $multiple ) {
						if ( $old_select > 1 ) {
							$replacement = eme_ui_multiselect( $selected_country, $country_post_name, $country_list, $multisize, $label, 0, '', $aria_label );
						} else {
							$replacement = eme_ui_multiselect( $selected_country, $country_post_name, $country_list, $multisize, $label, 0, 'eme_select2_filter', $aria_label . "data-placeholder='$label'", 1 );
						}
					} else {
						$replacement = eme_ui_select( $selected_country, $country_post_name, $country_list, $label, 0, 'eme_select2_filter', $aria_label );
					}
				}
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_WEEKS(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$past_count = intval(substr( $matches[2], 1, -1 ));
			} else {
				$past_count = 0;
			}
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$future_count = intval(substr( $matches[3], 1, -1 ));
			} else {
				$future_count = $scope_count;
			}
			if ( $scope_fieldcount == 0 ) {
				$label       = __( 'Select Week', 'events-made-easy' );
				$aria_label  = 'aria-label="' . eme_esc_html( $label ) . '"';
				$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_week_scope( $past_count, $future_count, $eventful ), $label, 0, '', $aria_label );
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_MONTHS(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$past_count = intval(substr( $matches[2], 1, -1 ));
			} else {
				$past_count = 0;
			}
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$future_count = intval(substr( $matches[3], 1, -1 ));
			} else {
				$future_count = $scope_count;
			}
			if ( $scope_fieldcount == 0 ) {
				$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_month_scope( $past_count, $future_count, $eventful ) );
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_FILTER_MONTHRANGE/', $result ) ) {
			if ( $scope_fieldcount == 0 ) {
				$select_scope = __( 'Select a daterange', 'events-made-easy' );
				$replacement  = "<input type='hidden' id='$scope_post_name' name='$scope_post_name'>";
				$replacement .= "<input type='text' id='$localized_scope_post_name' name='$localized_scope_post_name' placeholder='$select_scope' readonly='readonly' data-alt-field='$scope_post_name' data-autoclose='false' data-range='true' data-multiple-dates-separator=' -- ' data-alt-field-multiple-dates-separator='--' data-date='' style='width: 30ch;' class='eme_formfield_fdate' >";
				eme_enqueue_datetimepicker();
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_(EVENTFUL_)?FILTER_YEARS(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) && $matches[1] == 'EVENTFUL_' ) {
				$eventful = 1;
			}
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$past_count = intval(substr( $matches[2], 1, -1 ));
			} else {
				$past_count = 0;
			}
			if ( isset( $matches[3] ) ) {
				// remove { and } (first and last char of second match)
				$future_count = intval(substr( $matches[3], 1, -1 ));
			} else {
				$future_count = $scope_count;
			}
			if ( $scope_fieldcount == 0 ) {
				$replacement = eme_ui_select( $selected_scope, $scope_post_name, eme_create_year_scope( $past_count, $future_count, $eventful ) );
				++$scope_fieldcount;
			}
		} elseif ( preg_match( '/#_FILTER_CONTACT(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[1], 1, -1 );
			} else {
				$label = __( 'Event contact', 'events-made-easy' );
			}
			$args = [
				'echo'             => 0,
				'name'             => $contact_post_name,
				'show_option_none' => eme_esc_html( $label ),
				'selected'         => $selected_contact,
				'class'            => 'eme_select2_filter',
			];
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$exclude = substr( $matches[2], 1, -1 );
				// check if all integers
				$exclude_arr = explode( ',', $exclude );
				if ( eme_is_numeric_array( $exclude_arr ) ) {
					$args['exclude'] = $exclude_arr;
				}
			}
			// other arguments can be changed via the filter
			if ( has_filter( 'eme_filter_searchfilter_contact' ) ) {
				$args = apply_filters( 'eme_filter_searchfilter_contact', $args );
			}
			$replacement = wp_dropdown_users( $args );
		} elseif ( preg_match( '/#_FILTER_AUTHOR(\{.+?\})?(\{.+?\})?/', $result, $matches ) ) {
			if ( isset( $matches[1] ) ) {
				// remove { and } (first and last char of second match)
				$label = substr( $matches[1], 1, -1 );
			} else {
				$label = __( 'Event author', 'events-made-easy' );
			}
			$args = [
				'echo'             => 0,
				'name'             => $author_post_name,
				'show_option_none' => eme_esc_html( $label ),
				'selected'         => $selected_author,
				'class'            => 'eme_select2_filter',
			];
			if ( isset( $matches[2] ) ) {
				// remove { and } (first and last char of second match)
				$exclude = substr( $matches[2], 1, -1 );
				// check if all integers
				$exclude_arr = explode( ',', $exclude );
				if ( eme_is_numeric_array( $exclude_arr ) ) {
					$args['exclude'] = $exclude_arr;
				}
			}
			// other arguments can be changed via the filter
			if ( has_filter( 'eme_filter_searchfilter_author' ) ) {
				$args = apply_filters( 'eme_filter_searchfilter_author', $args );
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
			$replacement = "<input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
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


