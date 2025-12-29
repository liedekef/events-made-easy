<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_new_discount() {
	$discount = [
		'name'         => '',
		'description'  => '',
		'type'         => EME_DISCOUNT_TYPE_FIXED,
		'value'        => 0,
		'coupon'       => '',
		'dgroup'       => '',
		'valid_from'   => '',
		'valid_to'     => '',
		'properties'   => [],
		'use_per_seat' => 0,
		'strcase'      => 1,
		'count'        => 0,
		'maxcount'     => 0,
	];

	$discount['properties'] = eme_init_discount_props( $discount['properties'] );
	return $discount;
}

function eme_new_discountgroup() {
	$discountgroup = [
		'name'         => '',
		'description'  => '',
		'maxdiscounts' => 0,
	];

	return $discountgroup;
}

function eme_init_discount_props( $props ) {
	if ( ! isset( $props['invite_only'] ) ) {
		$props['invite_only'] = 0;
	}
	if ( ! isset( $props['wp_users_only'] ) ) {
		$props['wp_users_only'] = 0;
	}
	if ( ! isset( $props['voucher'] ) ) {
		$props['voucher'] = 0;
	}
	if ( ! isset( $props['maxcount_pp'] ) ) {
		$props['maxcount_pp'] = 0;
	}
	if ( ! isset( $props['wp_role'] ) ) {
		$props['wp_role'] = '';
	}
	if ( ! isset( $props['group_ids'] ) ) {
		$props['group_ids'] = [];
	}
	if ( ! isset( $props['membership_ids'] ) ) {
		$props['membership_ids'] = [];
	}
	if ( ! isset( $props['min_seats'] ) ) {
		$props['min_seats'] = ''; // empty string here, since 0 is being checked later on
	}
	if ( ! isset( $props['max_seats'] ) ) {
		$props['max_seats'] = ''; // empty string here, since 0 is being checked later on
	}
	return $props;
}

function eme_discounts_page() {
	if ( ! current_user_can( get_option( 'eme_cap_discounts' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
		$message = __( 'You have no right to manage discounts!', 'events-made-easy' );
		eme_discounts_main_layout( $message );
		return;
	}

	$message  = '';
	$csvMimes = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];

	// handle possible ations
	if ( isset( $_POST['eme_admin_action'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		if ( $_POST['eme_admin_action'] == 'do_importdiscounts' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
			$inserted  = 0;
			$errors    = 0;
			$error_msg = '';
			//validate whether uploaded file is a csv file
			if ( ! empty( $_FILES['eme_csv']['name'] ) && in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
				if ( is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
						$handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
					if ( ! $handle ) {
							$message = __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
					} else {
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

						// first line is the column headers
						$headers = array_map( 'strtolower', fgetcsv( $handle, 0, $delimiter, $enclosure ) );
						// check required columns
						if ( ! in_array( 'name', $headers ) || ! in_array( 'type', $headers ) || ! in_array( 'coupon', $headers ) || ! in_array( 'value', $headers ) ) {
							$message = __( 'Not all required fields present.', 'events-made-easy' );
						} else {
							$empty_props = [];
							$empty_props = eme_init_discount_props( $empty_props );
							while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
								$line = array_combine( $headers, $row );
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

								// convert dgroup names to id's
								if ( ! empty( $line['dgroup'] ) ) {
									$dgroups    = $line['dgroup'];
									$dgroup_arr = explode( ',', $dgroups );
                                    $selected_dgroup_arr = [];
                                    foreach ( $dgroup_arr as $dgroup_name ) {
                                        $dgroup = eme_get_discountgroup( $dgroup_name );
                                        // take the case where the group no longer exists into account
                                        if ( ! empty( $dgroup ) ) {
                                            $selected_dgroup_arr[] = $dgroup['id'];
                                        }
                                    }
                                    if ( ! empty( $selected_dgroup_arr ) ) {
                                        $line['dgroup'] = join( ',', $selected_dgroup_arr );
                                    } else {
                                        $line['dgroup'] = '';
                                    }
								}

								$res = eme_db_insert_discount( $line );
								if ( $res ) {
									++$inserted;
								} else {
									++$errors;
									$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
								}
							}
							$message = sprintf( __( 'Import finished: %d inserts, %d errors', 'events-made-easy' ), $inserted, $errors );
							if ( $errors ) {
								$message .= '<br>' . $error_msg;
							}
						}
						fclose( $handle );
					}
				} else {
					$message = __( 'Problem detected while uploading the file', 'events-made-easy' );
				}
			} else {
				$message = sprintf( esc_html__( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
			}
		} elseif ( $_POST['eme_admin_action'] == 'do_importdgroups' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
			$inserted  = 0;
			$errors    = 0;
			$error_msg = '';
			//validate whether uploaded file is a csv file
			if ( ! empty( $_FILES['eme_csv']['name'] ) && in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
				if ( is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
						$handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
					if ( ! $handle ) {
							$message = __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
					} else {
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

						// first line is the column headers
						$headers = array_map( 'strtolower', fgetcsv( $handle, 0, $delimiter, $enclosure ) );
						// check required columns
						if ( ! in_array( 'name', $headers ) ) {
							$message = __( 'Not all required fields present.', 'events-made-easy' );
						} else {
							while ( ( $row = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
								$discountgroup = array_combine( $headers, $row );
								$res           = eme_db_insert_dgroup( $discountgroup );
								if ( $res ) {
									++$inserted;
								} else {
									++$errors;
									$error_msg .= '<br>' . eme_esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
								}
							}
							$message = sprintf( __( 'Import finished: %d inserts, %d errors', 'events-made-easy' ), $inserted, $errors );
							if ( $errors ) {
								$message .= '<br>' . $error_msg;
							}
						}
						fclose( $handle );
					}
				} else {
					$message = __( 'Problem detected while uploading the file', 'events-made-easy' );
				}
			} else {
				$message = sprintf( esc_html__( 'No CSV file detected: %s', 'events-made-easy' ), $_FILES['eme_csv']['type'] );
			}
		} elseif ( $_POST['eme_admin_action'] == 'do_editdiscount' ) {
			if ( ! empty( $_POST['id'] ) ) {
				$discount_id = intval( $_POST['id'] );
				$discount    = eme_get_discount( $discount_id );
			} else {
				$discount_id = 0;
				$discount    = eme_new_discount();
			}
			foreach ( $discount as $key => $val ) {
				if ( isset( $_POST[ $key ] ) ) {
					$discount[ $key ] = eme_sanitize_request( $_POST[ $key ] );
				}
			}
			// dgroup is an array, let's convert it
			if ( is_array( $discount['dgroup'] ) ) {
				$discount['dgroup'] = join( ',', $discount['dgroup'] );
			}
			// unchecked checkboxes don't get sent in forms
			if ( ! isset( $_POST['strcase'] ) ) {
				$discount['strcase'] = 0;
			}
			if ( ! isset( $_POST['use_per_seat'] ) ) {
				$discount['use_per_seat'] = 0;
			}
			if ( ! is_numeric( $discount['type'] ) || $discount['type'] < 0 || $discount['type'] > 4 ) {
				$discount['type'] = EME_DISCOUNT_TYPE_FIXED;
			}

			if ( $discount['type'] == EME_DISCOUNT_TYPE_PCT ) { // for percentage type
				if ( $discount['value'] < 0 ) {
					$discount['value'] = 0;
				}
				if ( $discount['value'] > 100 ) {
					$discount['value'] = 100;
				}
			}
			if ( ! is_numeric( $discount['strcase'] ) || $discount['strcase'] < 0 || $discount['strcase'] > 1 ) {
				$discount['strcase'] = 1;
			}
			if ( eme_is_empty_datetime( $discount['valid_from'] ) ) {
				$discount['valid_from'] = null;
			}
			if ( eme_is_empty_datetime( $discount['valid_to'] ) ) {
				$discount['valid_to'] = null;
			}
			if ( $discount_id ) {
				$validation_result = eme_db_update_discount( $discount_id, $discount );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully edited the discount', 'events-made-easy' );
					if ( get_option( 'eme_stay_on_edit_page' ) ) {
						eme_discounts_edit_layout( $discount_id, $message );
						return;
					}
				} else {
					$message = __( 'There was a problem editing the discount, please try again.', 'events-made-easy' );
				}
			} else {
				$new_id = eme_db_insert_discount( $discount );
				if ( $new_id ) {
					$message = __( 'Successfully added the discount', 'events-made-easy' );
					if ( get_option( 'eme_stay_on_edit_page' ) ) {
						eme_discounts_edit_layout( $new_id, $message );
						return;
					}
				} else {
					$message = __( 'There was a problem adding the discount, please try again.', 'events-made-easy' );
				}
			}
			eme_manage_discounts_layout( $message );
			return;
		} elseif ( $_POST['eme_admin_action'] == 'do_editdgroup' ) {
			if ( ! empty( $_POST['id'] ) ) {
				$dgroup_id = intval( $_POST['id'] );
				$dgroup    = eme_get_discountgroup( $dgroup_id );
			} else {
				$dgroup_id = 0;
				$dgroup    = eme_new_discountgroup();
			}
			foreach ( $dgroup as $key => $val ) {
				if ( isset( $_POST[ $key ] ) ) {
					$dgroup[ $key ] = eme_sanitize_request( $_POST[ $key ] );
				}
			}
			if ( $dgroup_id ) {
				$validation_result = eme_db_update_dgroup( $dgroup_id, $dgroup );
				if ( $validation_result !== false ) {
					$message = __( 'Successfully edited the discount group', 'events-made-easy' );
					if ( get_option( 'eme_stay_on_edit_page' ) ) {
						eme_dgroups_edit_layout( $dgroup_id, $message );
						return;
					}
				} else {
					$message = __( 'There was a problem editing the discount group, please try again.', 'events-made-easy' );
				}
			} else {
				$new_id = eme_db_insert_dgroup( $dgroup );
				if ( $new_id ) {
					$message = __( 'Successfully added the discount group', 'events-made-easy' );
					if ( get_option( 'eme_stay_on_edit_page' ) ) {
						eme_dgroups_edit_layout( $new_id, $message );
						return;
					}
				} else {
					$message = __( 'There was a problem adding the discount group, please try again.', 'events-made-easy' );
				}
			}
			eme_manage_dgroups_layout( $message );
			return;
		}
	}

	// now that we handled possible ations, let's show the wanted screen
	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_discount' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_discounts_edit_layout();
		return;
	}
	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_dgroup' ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_dgroups_edit_layout();
		return;
	}
	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_discount' && ! empty( $_GET['id'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_discounts_edit_layout( intval( $_GET['id'] ) );
		return;
	}
	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_dgroup' && ! empty( $_GET['id'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		eme_dgroups_edit_layout( intval( $_GET['id'] ) );
		return;
	}

	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'discounts' ) {
		eme_manage_discounts_layout( $message );
		return;
	}
	if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'dgroups' ) {
		eme_manage_dgroups_layout( $message );
		return;
	}
	eme_discounts_main_layout();
}

function eme_discounts_main_layout( $message = '' ) {
	$discounts_destination = admin_url( 'admin.php?page=eme-discounts&amp;eme_admin_action=discounts' );
	$dgroups_destination   = admin_url( 'admin.php?page=eme-discounts&amp;eme_admin_action=dgroups' );
	$html                  = "
      <div class='wrap nosubsub'>\n
         <div id='icon-edit' class='icon32'>
         </div>
         <h1>" . __( 'Discount management', 'events-made-easy' ) . '</h1>
   ';
	if ( ! empty( $message ) ) {
		$html .= '<div id="discounts-message" class="eme-message-admin"><p>' . $message . '</p></div>';
	}

	$html .= '<h2>' . __( 'Manage discounts', 'events-made-easy' ) . '</h2>';
	$html .= "<a href='$discounts_destination'>" . __( 'Manage discounts', 'events-made-easy' ) . '</a><br>';
	$html .= '<h2>' . __( 'Manage discountgroups', 'events-made-easy' ) . '</h2>';
	$html .= "<a href='$dgroups_destination'>" . __( 'Manage discountgroups', 'events-made-easy' ) . '</a><br>';
	echo $html;
}

function eme_manage_discounts_layout( $message = '' ) {
	global $plugin_page;

	$dgroups     = eme_get_dgroups();
	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
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
		 
		<div id="discounts-message" class="notice is-dismissible eme-message-admin <?php echo $hidden_class; ?>">
			<p><?php echo $message; ?></p>
		</div>

		<h1><?php esc_html_e( 'Add a new discount definition', 'events-made-easy' ); ?></h1>
		<div class="wrap">
		<form method="post" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>">
			<?php echo $nonce_field; ?>
			<input type="hidden" name="eme_admin_action" value="add_discount">
			<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add discount', 'events-made-easy' ); ?>">
		</form>
		</div>

		<h1><?php esc_html_e( 'Manage discounts', 'events-made-easy' ); ?></h1>

	<?php if ( current_user_can( get_option( 'eme_cap_cleanup' ) ) ) { ?>
	<span class="eme_import_form_img">
		<?php esc_html_e( 'Click on the icon to show the import form', 'events-made-easy' ); ?>
	<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="eme_div_import" style="cursor: pointer; vertical-align: middle; ">
	</span>
	<div id='eme_div_import' class='eme-hidden'>
	<form id='discount-import' method='post' enctype='multipart/form-data' action='#'>
		<?php echo $nonce_field; ?>
	<input type="file" name="eme_csv">
		<?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
	<input type="text" size=1 maxlength=1 name="delimiter" value=','>
		<?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
	<input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"'>
	<input type="hidden" name="eme_admin_action" value="do_importdiscounts">
	<input type="submit" value="<?php esc_html_e( 'Import', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
	<?php esc_html_e( 'If you want, use this to import discounts into the database', 'events-made-easy' ); ?>
	</form>
	</div>
	<?php } ?>
    <div id="bulkactions">
	<form id='discounts-form' action="#" method="post">
	<?php echo $nonce_field; ?>
	<select id="eme_admin_action" name="eme_admin_action">
	<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
	<option value="deleteDiscounts"><?php esc_html_e( 'Delete selected discounts', 'events-made-easy' ); ?></option>
	<option value="addToGroup"><?php esc_html_e( 'Add to group', 'events-made-easy' ); ?></option>
	<option value="removeFromGroup"><?php esc_html_e( 'Remove from group', 'events-made-easy' ); ?></option>
	<option value="changeValidFrom"><?php esc_html_e( 'Change "valid from" date', 'events-made-easy' ); ?></option>
	<option value="changeValidTo"><?php esc_html_e( 'Change "valid until" date', 'events-made-easy' ); ?></option>
	</select>
	<span id="span_addtogroup" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'addtogroup', $dgroups, 'id', 'name', __( 'Select a group', 'events-made-easy' ), 1 ); ?>
	</span>
	<span id="span_removefromgroup" class="eme-hidden">
	<?php echo eme_ui_select_key_value( '', 'removefromgroup', $dgroups, 'id', 'name', __( 'Select a group', 'events-made-easy' ), 1 ); ?>
	</span>
	<span id="span_newvalidfrom" class="eme-hidden">
	<input id="new_validfrom" type="hidden" name="new_validfrom" value="">
	<input id="eme_localized_new_validfrom" type="text" name="eme_localized_new_validfrom" value="" readonly="readonly" placeholder="<?php esc_html_e( 'Select new "valid from" date/time', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='new_validfrom' class='eme_formfield_fdatetime'>
	</span>
	<span id="span_newvalidto" class="eme-hidden">
	<input id="new_validto" type="hidden" name="new_validto" value="">
	<input id="eme_localized_new_validto" type="text" name="eme_localized_new_validto" value="" readonly="readonly" placeholder="<?php esc_html_e( 'Select new "valid until" date/time', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='new_validto' class='eme_formfield_fdatetime'>
	</span>
	<button id="DiscountsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
	</form>
    </div>
	<div id="DiscountsTableContainer"></div>
	</div> 
	</div>
	<?php
}

function eme_manage_dgroups_layout( $message = '' ) {
	global $plugin_page;
	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
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
		 
	<div id="discountgroups-message" class="notice is-dismissible eme-message-admin <?php echo $hidden_class; ?>">
		<p><?php echo $message; ?></p>
	</div>

		<h1><?php esc_html_e( 'Add a new discount group', 'events-made-easy' ); ?></h1>
		<div class="wrap">
		<form method="post" action="<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>">
			<?php echo $nonce_field; ?>
			<input type="hidden" name="eme_admin_action" value="add_dgroup">
			<input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add discount group', 'events-made-easy' ); ?>">
		</form>
		</div>

		<h1><?php esc_html_e( 'Manage discount groups', 'events-made-easy' ); ?></h1>

	<?php if ( current_user_can( get_option( 'eme_cap_cleanup' ) ) ) { ?>
	<span class="eme_import_form_img">
		<?php esc_html_e( 'Click on the icon to show the import form', 'events-made-easy' ); ?>
	<img src="<?php echo esc_url(EME_PLUGIN_URL); ?>images/showhide.png" class="showhidebutton" alt="show/hide" data-showhide="eme_div_import" style="cursor: pointer; vertical-align: middle; ">
	</span>
	<div id='eme_div_import' class='eme-hidden'>
	<form id='discountgroups-import' method='post' enctype='multipart/form-data' action='#'>
		<?php echo $nonce_field; ?>
	<input type="file" name="eme_csv">
		<?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
	<input type="text" size=1 maxlength=1 name="delimiter" value=','>
		<?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
	<input required="required" type="text" size=1 maxlength=1 name="enclosure" value='"'>
	<input type="hidden" name="eme_admin_action" value="do_importdgroups">
	<input type="submit" value="<?php esc_html_e( 'Import', 'events-made-easy' ); ?>" name="doaction" id="doaction" class="button-primary action">
		<?php esc_html_e( 'If you want, use this to import discountgroups into the database', 'events-made-easy' ); ?>
	</form>
	</div>
	<?php } ?>
    <div id="bulkactions">
	<form id='discountgroups-form' action="#" method="post">
	<?php echo $nonce_field; ?>
	<select id="eme_admin_action" name="eme_admin_action">
	<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
	<option value="deleteDiscountGroups"><?php esc_html_e( 'Delete selected discountgroups', 'events-made-easy' ); ?></option>
	</select>
	<button id="DiscountGroupsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
	</form>
    </div>
	<div id="DiscountGroupsTableContainer"></div>
	</div> 
	</div>
	<?php
}

function eme_booking_discount( $event, $booking ) {
    // this function calculates discounts but doesn't change anything to the discounts themselves
	$discountgroup_id = $booking['dgroupid'];
	$total_discount   = 0;
    $legacy_format = false;
    $applied_discounts = [];
    $applied_discountids = [];
	if ( ! empty( $booking['discountids'] ) ) {
        if ( eme_is_serialized( $booking['discountids'] ) ) {
            $applied_discounts = eme_unserialize( $booking['discountids'] );
            $applied_discountids = array_keys($applied_discounts);
        } else {
            $applied_discountids = explode( ',', $booking['discountids'] );
            $legacy_format = true;
        }
	}

	if ( eme_is_admin_request() ) {
		if ( isset( $_POST['DISCOUNT'] ) ) {
			$post_discount = sprintf( '%01.2f', $_POST['DISCOUNT'] );
			$booking_discount = sprintf( '%01.2f', $booking['discount'] );
			// if there's an amount entered and it is different than what was calculated before, we clear the discount id references
			if ( $post_discount != $booking_discount ) {
                $legacy_format          = false;
				$applied_discounts      = [];
				$applied_discountids    = [];
				$discountgroup_id       = 0;
				$booking['dcodes_used'] = [];
			}
		}
	} elseif ( ! empty( $event['event_properties']['rsvp_discountgroup'] ) ) {
        $discount_group = eme_get_discountgroup( $event['event_properties']['rsvp_discountgroup'] );
		$discountgroup_id = $discount_group['id'];
		if ( ! $discountgroup_id ) {
			return false;
		}

		$discount_ids  = eme_get_discountids_by_group( $discount_group );
		$group_count   = 0;
		$max_discounts = $discount_group['maxdiscounts'];

		$booking['dcodes_used'] = [];
		foreach ( $discount_ids as $id ) {
			// a discount can only be applied once
			if ( in_array( $id, $applied_discountids ) ) {
				continue;
			}
			if ( $max_discounts == 0 || $group_count < $max_discounts ) {
				$discount    = eme_get_discount( $id );
				$calc_result = eme_calc_booking_discount( $discount, $booking );
				if ( $calc_result === false || empty( $calc_result[0] ) ) {
					$amount = 0;
				} else {
					$amount = $calc_result[0];
				}
				if ( $amount ) {
					++$group_count;
					$total_discount       += $amount;
                    $applied_discounts[$id] = $amount;
                    $applied_discountids[] = $id;
					// we assign the dcodes_used back to $booking, so the next run of this foreach loop has this knowlede in eme_calc_booking_discount()
					if ( ! empty( $calc_result[1] ) ) {
						$booking['dcodes_used'] = $calc_result[1];
					}
				}
			}
		}
	} elseif ( ! empty( $event['event_properties']['rsvp_discount'] ) ) {
        $discount = eme_get_discount( $event['event_properties']['rsvp_discount'] );
		$calc_result = eme_calc_booking_discount( $discount, $booking );
		if ( $calc_result === false || empty( $calc_result[0] ) ) {
			$amount = 0;
		} else {
			$amount = $calc_result[0];
		}
		if ( $amount ) {
			$total_discount        = $amount;
            $applied_discounts[$discount['id']] = $amount;
            $applied_discountids[] = $discount['id'];
			if ( ! empty( $calc_result[1] ) ) {
				$booking['dcodes_used'] = $calc_result[1];
			} else {
				$booking['dcodes_used'] = [];
			}
		}
	}

    if ($legacy_format) {
        $discountids = join( ',', array_unique( $applied_discountids ) );
    } else {
        $discountids = eme_serialize( $applied_discounts );
    }
	return [
		'discount'    => $total_discount,
		'discountids' => $discountids,
		'dgroupid'    => $discountgroup_id,
		'dcodes_used' => $booking['dcodes_used'],
	];
}

function eme_member_discount( $membership, $member ) {
	$discountgroup_id = $member['dgroupid'];
	$total_discount   = 0;
    $legacy_format = false;
    $applied_discountids = [];
    $applied_discounts = [];
    if ( ! empty( $member['discountids'] ) ) {
        if ( eme_is_serialized( $member['discountids'] ) ) {
            $applied_discounts = eme_unserialize( $member['discountids'] );
            $applied_discountids = array_keys($applied_discounts);
        } else {
            $applied_discountids = explode( ',', $member['discountids'] );
            $legacy_format = true;
        }
    }

	if ( eme_is_admin_request() ) {
		if ( isset( $_POST['DISCOUNT'] ) ) {
			$post_discount = sprintf( '%01.2f', $_POST['DISCOUNT'] );
			$member_discount = sprintf( '%01.2f', $member['discount'] );
			// if there's an amount entered and it is different than what was calculated before, we clear the discount id references
			if ( $post_discount != $member_discount ) {
				$applied_discounts     = [];
				$applied_discountids   = [];
				$discountgroup_id      = 0;
				$member['dcodes_used'] = [];
			}
		}
	} elseif ( ! empty( $membership['properties']['discountgroup'] ) ) {
        $discount_group = eme_get_discountgroup( $membership['properties']['discountgroup'] );
		$discountgroup_id = $discount_group['id'];
		if ( ! $discountgroup_id ) {
			return false;
		}

		$discount_ids  = eme_get_discountids_by_group( $discount_group );
		$group_count   = 0;
		$max_discounts = $discount_group['maxdiscounts'];

		$member['dcodes_used'] = [];
		foreach ( $discount_ids as $id ) {
			// a discount can only be applied once
			if ( in_array( $id, $applied_discountids ) ) {
				continue;
			}
			if ( $max_discounts == 0 || $group_count < $max_discounts ) {
				$discount    = eme_get_discount( $id );
				$calc_result = eme_calc_member_discount( $discount, $member );
				if ( $calc_result === false || empty( $calc_result[0] ) ) {
					$amount = 0;
				} else {
					$amount = $calc_result[0];
				}
				if ( $amount ) {
					++$group_count;
					$total_discount += $amount;
                    $applied_discounts[$id] = $amount;
					$applied_discountids[] = $id;
					// we assign the dcodes_used back to $member, so the next run of this foreach loop has this knowlede in eme_calc_member_discount()
					if ( ! empty( $calc_result[1] ) ) {
						$member['dcodes_used'] = $calc_result[1];
					}
				}
			}
		}
	} elseif ( ! empty( $membership['properties']['discount'] ) ) {
        $discount = eme_get_discount( $membership['properties']['discount'] );
		$calc_result = eme_calc_member_discount( $discount, $member );
		if ( $calc_result === false || empty( $calc_result[0] ) ) {
			$amount = 0;
		} else {
			$amount = $calc_result[0];
		}
		if ( $amount ) {
			$total_discount        = $amount;
            $applied_discounts[$discount['id']] = $amount;
            $applied_discountids[] = $discount['id'];
			if ( ! empty( $calc_result[1] ) ) {
                $member['dcodes_used'] = $calc_result[1];
			} else {
				$member['dcodes_used'] = [];
			}
		}
	}

    if ($legacy_format) {
        $discountids = join( ',', array_unique( $applied_discountids ) );
    } else {
        $discountids = eme_serialize( $applied_discounts );
    }
	return [
		'discount'    => $total_discount,
		'discountids' => $discountids,
		'dgroupid'    => $discountgroup_id,
		'dcodes_used' => $member['dcodes_used'],
	];
}

function eme_check_booking_discount( $booking ) {
	global $wpdb;

	$event = eme_get_event( $booking['event_id'] );
	if ( empty( $event ) ) {
		return;
	}
    // the next calc calculates discounts but doesn't change anything to the discounts themselves
	$calc_discount = eme_booking_discount( $event, $booking );

	$bookings_table        = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
	$where                 = [];
	$fields                = [];
	$where['booking_id']   = $booking['booking_id'];
	$fields['discount']    = $calc_discount['discount'];
	$fields['discountids'] = $calc_discount['discountids'];
	$fields['dcodes_used'] = eme_serialize( $calc_discount['dcodes_used'] );
	$fields['dgroupid']    = $calc_discount['dgroupid'];
	if ( $calc_discount['discount'] != $booking['discount'] ) {
		// if the discount is not equal to the original, the $calc_discount['discountids'] value will be empty
		// so we also decrease the usage count of the discount ids
        if ( eme_is_serialized( $booking['discountids'] ) ) {
            $applied_discounts = eme_unserialize( $booking['discountids'] );
            $applied_discountids = array_keys($applied_discounts);
        } else {
            $applied_discountids = explode( ',', $booking['discountids'] );
        }
		foreach ( $applied_discountids as $discount_id ) {
			eme_decrease_discount_booking_count( $discount_id, $booking );
		}
	}
	$wpdb->update( $bookings_table, $fields, $where );
}

function eme_check_member_discount( $member ) {
	global $wpdb;

	$membership    = eme_get_membership( $member['membership_id'] );
	$calc_discount = eme_member_discount( $membership, $member );

	$members_table         = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
	$where                 = [];
	$fields                = [];
	$where['member_id']    = $member['member_id'];
	$fields['discount']    = $calc_discount['discount'];
	$fields['discountids'] = $calc_discount['discountids'];
	$fields['dcodes_used'] = eme_serialize( $calc_discount['dcodes_used'] );
	$fields['dgroupid']    = $calc_discount['dgroupid'];
	if ( $calc_discount != $member['discount'] ) {
		// if the discount is not equal to the original, the $calc_discount['discountids'] value will be empty
        // so we also decrease the usage count of the discount ids
        if ( eme_is_serialized( $member['discountids'] ) ) {
            $applied_discounts = eme_unserialize( $member['discountids'] );
            $applied_discountids = array_keys($applied_discounts);
        } else {
            $applied_discountids = explode( ',', $member['discountids'] );
        }
		foreach ( $applied_discountids as $discount_id ) {
			eme_decrease_discount_member_count( $discount_id, $member );
		}
	}
	$wpdb->update( $members_table, $fields, $where );
}

function eme_discounts_edit_layout( $discount_id = 0, $message = '' ) {
	global $plugin_page;

	$selected_dgroup_arr = [];
	if ( $discount_id ) {
		$discount        = eme_get_discount( $discount_id );
		$selected_dgroup = $discount['dgroup'];
		if ( ! empty( $selected_dgroup ) ) {
			// let's see if the dgroup is an array of integers, if not: we take it as a name and convert it to 1 id
			$selected_dgroup_arr = explode( ',', $selected_dgroup );
			if ( ! eme_is_numeric_array( $selected_dgroup_arr ) ) {
				$dgroup = eme_get_discountgroup_by_name( $selected_dgroup );
				// take the case where the group no longer exists into account
				if ( ! empty( $dgroup ) ) {
					$selected_dgroup_arr = [ $dgroup['id'] ];
				} else {
					$selected_dgroup_arr = [];
				}
			}
		}
		$h1_string     = __( 'Edit discount', 'events-made-easy' );
		$action_string = __( 'Update discount', 'events-made-easy' );
	} else {
		$discount      = eme_new_discount();
		$h1_string     = __( 'Create discount', 'events-made-easy' );
		$action_string = __( 'Add discount', 'events-made-easy' );
	}

	$nonce_field    = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	$discount_types = eme_get_discounttypes();
	$dgroups        = eme_get_dgroups();
	$groups         = eme_get_static_groups();
	$memberships    = eme_get_memberships();

	?>
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
		</div>
		 
		<h1><?php echo $h1_string; ?></h1>
	  
		<?php if ( $message != '' ) { ?>
		<div id='message' class='updated notice notice-success is-dismissible'>
		<p><?php echo $message; ?></p>
		</div>
		<?php } ?>
		<div id='ajax-response'></div>

		<form name='edit_discounts' id='edit_discounts' method='post' action='<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>'>
		<input type='hidden' name='eme_admin_action' value='do_editdiscount'>
		<input type='hidden' name='id' value='<?php echo $discount_id; ?>'>
		<?php echo $nonce_field; ?>
		<table class='form-table'>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='name'><?php esc_html_e( 'Discount name', 'events-made-easy' ); ?></label></th>
			<td><input name='name' id='name' required='required' type='text' value='<?php echo eme_esc_html( $discount['name'] ); ?>' size='40'><br>
			<?php esc_html_e( 'The name of the discount', 'events-made-easy' ); ?></td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='description'><?php esc_html_e( 'Description', 'events-made-easy' ); ?></label></th>
			<td><textarea name='description' id='description' rows='5' ><?php echo eme_esc_html( $discount['description'] ); ?></textarea></td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='type'><?php esc_html_e( 'Type', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select( $discount['type'], 'type', $discount_types ); ?>
			<br><?php esc_html_e( 'For type "Code" you have to create your own discount filters, please read the documention for this', 'events-made-easy' ); ?>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='value'><?php esc_html_e( 'Discount value', 'events-made-easy' ); ?></label></th>
			<td><input name='value' id='value' type='text' value='<?php echo eme_esc_html( $discount['value'] ); ?>' size='40'>
			<br><?php esc_html_e( 'The applied discount value if the correct coupon code is entered (this does not apply to discounts of type "Code")', 'events-made-easy' ); ?>
			<br><?php esc_html_e( 'For type "Percentage" the value should be >=0 and <=100', 'events-made-easy' ); ?>
				</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='coupon'><?php esc_html_e( 'Coupon code', 'events-made-easy' ); ?></label></th>
			<td><input name='coupon' id='coupon' type='text' value='<?php echo eme_esc_html( $discount['coupon'] ); ?>' size='40'>
			<br><?php esc_html_e( 'The coupon code to enter for the discount to apply (this does not apply to discounts of type "Code")', 'events-made-easy' ); ?>
			<br><?php esc_html_e( 'If you leave the coupon code empty but set a discount expiration date, you can use this as an "early bird" discount', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[voucher]'><?php esc_html_e( 'Voucher mode', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select_binary( $discount['properties']['voucher'], 'properties[voucher]' ); ?>
			<br><p class='eme_smaller'><?php esc_html_e( 'If voucher mode is active (for type "fixed" only), the discount value will be decreased by the calculated value until it reaches 0.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='value'><?php esc_html_e( 'Discount groups', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_multiselect_key_value( $selected_dgroup_arr, 'dgroup', $dgroups, 'id', 'name', 5, '', 0, 'eme_select2_width50_class' ); ?>
			<br><?php esc_html_e( 'If wanted, you can make this discount a part of one or more discount groups, and then apply a discount group to your event', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='dp_valid_from'><?php esc_html_e( 'Valid from', 'events-made-easy' ); ?></label></th>
			<td><input type='hidden' readonly='readonly' name='valid_from' id='valid_from'>
			<input type='text' readonly='readonly' name='dp_valid_from' id='dp_valid_from' data-date='<?php if ( $discount['valid_from'] ) { echo eme_js_datetime( $discount['valid_from'] );} ?>' data-alt-field='valid_from' class='eme_formfield_fdatetime'>
			<br><?php esc_html_e( 'An optional coupon start date and time, if entered the coupon is not valid before this date and time.', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='dp_valid_to'><?php esc_html_e( 'Valid until', 'events-made-easy' ); ?></label></th>
			<td><input type='hidden' readonly='readonly' name='valid_to' id='valid_to'>
			<input type='text' readonly='readonly' name='dp_valid_to' id='dp_valid_to' data-date='<?php if ( $discount['valid_to'] ) { echo eme_js_datetime( $discount['valid_to'] );} ?>' data-alt-field='valid_to' class='eme_formfield_fdatetime'>
			<br><?php esc_html_e( 'An optional coupon expiration date and time, if entered the coupon is not valid after this date and time.', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[invite_only]'><?php esc_html_e( 'Invited people only', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select_binary( $discount['properties']['invite_only'], 'properties[invite_only]' ); ?>
			<br><p class='eme_smaller'><?php esc_html_e( 'People need to be invited and click on the invitation URL for this discount to apply.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[wp_users_only]'><?php esc_html_e( 'Logged-in users only', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select_binary( $discount['properties']['wp_users_only'], 'properties[wp_users_only]' ); ?>
			<br><p class='eme_smaller'><?php esc_html_e( 'Require users to be logged-in for this discount to apply.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[group_ids]'><?php esc_html_e( 'Require EME groups', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_multiselect_key_value( $discount['properties']['group_ids'], 'properties[group_ids]', $groups, 'group_id', 'name', 5, '', 0, 'eme_select2_width50_class' ); ?>
			<br><p class='eme_smaller'><?php esc_html_e( 'Require logged-in user to be in of one of the selected EME groups for this discount to apply.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[membership_ids]'><?php esc_html_e( 'Require EME membership', 'events-made-easy' ); ?></label></th>
			<td>
			<?php
			if ( ! empty( $memberships ) ) {
				echo eme_ui_multiselect_key_value( $discount['properties']['membership_ids'], 'properties[membership_ids]', $memberships, 'membership_id', 'name', 5, '', 0, 'eme_select2_memberships_class' );
			} else {
				esc_html_e( 'No memberships defined yet!', 'events-made-easy' );
			}
			?>
			<br><p class='eme_smaller'><?php esc_html_e( 'Require logged-in user to be a member of one of the selected EME memberships for this discount to apply.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[wp_role]'><?php esc_html_e( 'WP role required', 'events-made-easy' ); ?></label></th>
			<td><select id='wp_role' name='properties[wp_role]'><option value=''>&nbsp;</option><?php wp_dropdown_roles( $discount['properties']['wp_role'] ); ?> </select>
			<br><p class='eme_smaller'><?php esc_html_e( 'Require users to have the selected WP role for the discount to apply.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='strcase'><?php esc_html_e( 'Case sensitive?', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select_binary( $discount['strcase'], 'strcase' ); ?></td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='maxcount'><?php esc_html_e( 'Maximum usage count', 'events-made-easy' ); ?></label></th>
			<td><input name='maxcount' id='maxcount' type='text' value='<?php echo eme_esc_html( $discount['maxcount'] ); ?>' size='40'>
			<br><?php esc_html_e( 'The maximum number of times this discount can be applied', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='maxcount'><?php esc_html_e( 'Maximum usage count per user', 'events-made-easy' ); ?></label></th>
			<td><input name='properties[maxcount_pp]' id='properties[maxcount_pp]' type='text' value='<?php echo eme_esc_html( $discount['properties']['maxcount_pp'] ); ?>' size='40'>
			<br><?php esc_html_e( 'The maximum number of times this discount can be applied per user (this implies the person needs to be logged in). This checks all bookings and members, so it can be heavy on the database (and removed bookings of course impact the count).', 'events-made-easy' ); ?>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[min_seats]'><?php esc_html_e( 'Min #seats booked', 'events-made-easy' ); ?></label></th>
			<td><input name='properties[min_seats]' id='properties[min_seats]' type='text' value='<?php echo eme_esc_html( $discount['properties']['min_seats'] ); ?>' size='40'>
			<br><p class='eme_smaller'><?php esc_html_e( 'The minimum number of seats that need to be booked for the discount to apply. Leave empty (not 0) for no limit.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='properties[max_seats]'><?php esc_html_e( 'Max #seats booked', 'events-made-easy' ); ?></label></th>
			<td><input name='properties[max_seats]' id='properties[max_seats]' type='text' value='<?php echo eme_esc_html( $discount['properties']['max_seats'] ); ?>' size='40'>
			<br><p class='eme_smaller'><?php esc_html_e( 'The maximum number of seats that need to be booked for the discount to apply. Leave empty (not 0) for no limit.', 'events-made-easy' ); ?></p>
			</td>
		</tr>
	<?php if ( $discount_id ) { ?>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='count'><?php esc_html_e( 'Current usage count', 'events-made-easy' ); ?></label></th>
			<td><input name='count' id='count' type='text' value='<?php echo eme_esc_html( $discount['count'] ); ?>' size='40'>
			<br><?php esc_html_e( 'The current number of times this discount has been applied', 'events-made-easy' ); ?>
			</td>
		</tr>
	<?php } ?>
		<tr class='form-field'>
			<th scope='row' style='vertical-align:top'><label for='use_per_seat'><?php esc_html_e( 'Count usage per seat?', 'events-made-easy' ); ?></label></th>
			<td><?php echo eme_ui_select_binary( $discount['use_per_seat'], 'use_per_seat' ); ?>
			<br><?php esc_html_e( 'By default the coupon usage count is counted per form submit. If you want, you can augment the usage count by the number of seats booked instead.', 'events-made-easy' ); ?>
			</td>
		</tr>
		</table>
	<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo $action_string; ?>'></p>
		</form>
	</div>
	<?php
}

function eme_dgroups_edit_layout( $dgroup_id = 0, $message = '' ) {
	global $plugin_page;

	if ( $dgroup_id ) {
		$dgroup            = eme_get_discountgroup( $dgroup_id );
			$h1_string     = __( 'Edit discount group', 'events-made-easy' );
			$action_string = __( 'Update discount group', 'events-made-easy' );
	} else {
			$dgroup        = eme_new_discountgroup();
			$h1_string     = __( 'Create discount group', 'events-made-easy' );
			$action_string = __( 'Add discount group', 'events-made-easy' );
	}

	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );

	?>
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
		</div>
		 
		<h1><?php echo $h1_string; ?></h1>
	  
		<?php if ( $message != '' ) { ?>
		<div id='message' class='updated notice notice-success is-dismissible'>
		<p><?php echo $message; ?></p>
		</div>
		<?php } ?>
		<div id='ajax-response'></div>

		<form name='edit_dgroups' id='edit_dgroups' method='post' action='<?php echo admin_url( "admin.php?page=$plugin_page" ); ?>'>
		<input type='hidden' name='eme_admin_action' value='do_editdgroup'>
		<input type='hidden' name='id' value='<?php echo $dgroup_id; ?>'>
		<?php echo $nonce_field; ?>
		<table class='form-table'>
			<tr class='form-field'>
		<th scope='row' style='vertical-align:top'><label for='name'><?php esc_html_e( 'Discountgroup name', 'events-made-easy' ); ?></label></th>
		<td><input name='name' id='name' required='required' type='text' value='<?php echo eme_esc_html( $dgroup['name'] ); ?>' size='40'><br>
		<?php esc_html_e( 'The name of the discount group', 'events-made-easy' ); ?></td>
			</tr>
			<tr class='form-field'>
		<th scope='row' style='vertical-align:top'><label for='description'><?php esc_html_e( 'Description', 'events-made-easy' ); ?></label></th>
			<td><textarea name='description' id='description' rows='5' ><?php echo eme_esc_html( $dgroup['description'] ); ?></textarea></td>
			</tr>
			<tr class='form-field'>
		<th scope='row' style='vertical-align:top'><label for='maxdiscounts'><?php esc_html_e( 'Max amount of discounts applied', 'events-made-easy' ); ?></label></th>
			<td><input name='maxdiscounts' id='maxdiscounts' type='text' value='<?php echo eme_esc_html( $dgroup['maxdiscounts'] ); ?>' size='40'>
			<br><?php esc_html_e( 'A discount group exists of several discounts, and you can ask for several discount codes by using #_DISCOUNT repeatedly. This parameter then puts a limit of the number of valid discounts that can be applied. So even if you e.g. enter 5 valid coupon codes, you can limit the usage to only 3 or so.', 'events-made-easy' ); ?>
				</td>
			</tr>
		</table>
	<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo $action_string; ?>'></p>
		</form>
	</div>
	<?php
}

function eme_get_discounts( $extra_search = '' ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql   = "SELECT * FROM $table";
	if ( ! empty( $extra_search ) ) {
		$sql .= " WHERE $extra_search";
	}
	$rows = $wpdb->get_results( $sql, ARRAY_A );
	foreach ( $rows as $key => $discount ) {
			$discount['properties'] = eme_init_discount_props( eme_unserialize( $discount['properties'] ) );
			$rows[ $key ]           = $discount;
	}
	return $rows;
}

function eme_get_dgroups( $extra_search = '' ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$sql   = "SELECT * FROM $table";
	if ( ! empty( $extra_search ) ) {
		$sql .= " WHERE $extra_search";
	}
	return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_db_insert_discount( $line ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;

	$discount = eme_new_discount();
	// we only want the columns that interest us
	$keys     = array_intersect_key( $line, $discount );
	$new_line = array_merge( $discount, $keys );
	if ( has_filter( 'eme_insert_discount_filter' ) ) {
		$new_line = apply_filters( 'eme_insert_discount_filter', $new_line );
	}

	if ( ! eme_is_serialized( $new_line['properties'] ) ) {
		$new_line['properties'] = eme_serialize( $new_line['properties'] );
	}

	if ( $wpdb->insert( $table, $new_line ) === false ) {
		return false;
	} else {
		return $wpdb->insert_id;
	}
}

function eme_db_update_discount( $id, $line ) {
    global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;

    $discount = eme_get_discount( $id );
    // we only want the columns that interest us
    $keys     = array_intersect_key( $line, $discount );
    $new_line = array_merge( $discount, $keys );
	if ( ! eme_is_serialized( $new_line['properties'] ) ) {
        $new_line['properties'] = eme_serialize( $new_line['properties'] );
	}
    $where = [ 'id' => $id ];
	if ( isset( $new_line['id'] ) ) {
		unset( $new_line['id'] );
	}
	if ( $wpdb->update( $table, $new_line, $where ) === false ) {
        return false;
	} else {
        return $id;
	}
}

function eme_db_insert_dgroup( $line ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;

	$discountgroup = eme_new_discountgroup();
	// we only want the columns that interest us
	$keys     = array_intersect_key( $line, $discountgroup );
	$new_line = array_merge( $discountgroup, $keys );
	if ( $wpdb->insert( $table, $new_line ) === false ) {
			return false;
	} else {
			return $wpdb->insert_id;
	}
}

function eme_db_update_dgroup( $id, $line ) {
		global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;

		$discountgroup = eme_get_discountgroup( $id );
		// we only want the columns that interest us
		$keys     = array_intersect_key( $line, $discountgroup );
		$new_line = array_merge( $discountgroup, $keys );
		$where    = [ 'id' => $id ];
	if ( isset( $new_line['id'] ) ) {
		unset( $new_line['id'] );
	}
	if ( $wpdb->update( $table, $new_line, $where ) === false ) {
			return false;
	} else {
			return $id;
	}
}

function eme_add_discount_to_group( $discount_id, $group_id ) {
		$discount = eme_get_discount( $discount_id );
	$changed      = 0;
	if ( empty( $discount['dgroup'] ) ) {
		$discount['dgroup'] = $group_id;
		$changed            = 1;
	} else {
		$dgroups_arr = explode( ',', $discount['dgroup'] );
		if ( ! in_array( $group_id, $dgroups_arr ) ) {
			$dgroups_arr[] = $group_id;
			$changed       = 1;
		}
		$discount['dgroup'] = join( ',', $dgroups_arr );
	}
	if ( $changed ) {
		eme_db_update_discount( $discount_id, $discount );
	}
}

function eme_remove_discount_from_group( $discount_id, $group_id ) {
	$discount = eme_get_discount( $discount_id );
	$changed  = 0;
	if ( empty( $discount['dgroup'] ) ) {
		return;
	} else {
		$dgroups_arr = explode( ',', $discount['dgroup'] );
		$key = array_search( $group_id, $dgroups_arr );
		if ( $key !== false ) {
			unset( $dgroups_arr[ $key ] );
			$changed = 1;
		}
		$discount['dgroup'] = join( ',', $dgroups_arr );
	}
	if ( $changed ) {
		eme_db_update_discount( $discount_id, $discount );
	}
}

function eme_change_discount_validfrom( $discount_id, $date ) {
		global $wpdb;
		$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;

	if ( eme_is_datetime( $date ) ) {
		$sql = $wpdb->prepare( "UPDATE $table SET valid_from = %s WHERE id = %d", $date, $discount_id );
		$wpdb->query( $sql );
	}
}
function eme_change_discount_validto( $discount_id, $date ) {
		global $wpdb;
		$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;

	if ( eme_is_datetime( $date ) ) {
		$sql = $wpdb->prepare( "UPDATE $table SET valid_to = %s WHERE id = %d", $date, $discount_id );
		$wpdb->query( $sql );
	}
}

function eme_get_discountgroup( $id ) {
	global $wpdb;
    if ( !is_numeric( $id ) ) {
        return eme_get_discountgroup_by_name( $id );
    }
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
	return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_discountgroup_by_name( $name ) {
	global $wpdb;
    if ( is_numeric( $name ) ) {
        return eme_get_discountgroup( $name );
    }
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT * FROM $table WHERE name = %s", $name );
	return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_discountids_by_group( $dgroup ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT id FROM $table WHERE FIND_IN_SET(%d,dgroup) OR dgroup = %s", $dgroup['id'], $dgroup['name'] );
	return $wpdb->get_col( $sql );
}

function eme_increase_discount_count( $id, $usage ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql   = $wpdb->prepare( "UPDATE $table SET count=count+%d WHERE id = %d", $usage, $id );
	return $wpdb->query( $sql );
}
function eme_decrease_discount_count( $id, $usage ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql   = $wpdb->prepare( "UPDATE $table SET count=count-%d WHERE id = %d", $usage, $id );
	return $wpdb->query( $sql );
}

// next function is only called after new booking
function eme_update_booking_discounts( $booking ) {
    if (empty($booking['discountids'])) {
        return;
    }
    if ( eme_is_serialized( $booking['discountids'] ) ) {
        $applied_discounts = eme_unserialize( $booking['discountids'] );
        $applied_discountids = array_keys($applied_discounts);
    } else {
        $applied_discountids = explode( ',', $booking['discountids'] );
    }
    foreach ($applied_discountids as $discountid) {
        $discount = eme_get_discount( $discountid );
        if ( $discount ) {
            if ( $discount['use_per_seat'] > 0 ) {
                if ( $discount['maxcount'] > 0 && $discount['count'] + $booking['booking_seats'] > $discount['maxcount'] ) {
                    $discount_count_to_use = $discount['maxcount'] - $discount['count'];
                } else {
                    $discount_count_to_use = $booking['booking_seats'];
                }
            } else {
                $discount_count_to_use = 1;
            }
            eme_increase_discount_count( $discount['id'], $discount_count_to_use );

            if ( ! empty( $discount['properties']['voucher'] ) && $discount['type'] == EME_DISCOUNT_TYPE_FIXED && !empty($applied_discounts[ $discount['id'])) {
                $discount['value'] -= $applied_discounts[ $discount['id']];
                if ($discount['value']<0) $discount['value']=0;
                eme_db_update_discount( $discount['id'], $discount);
            }
        }
    }
}

// next function is only called after member insert
function eme_update_member_discounts( $member ) {
    if (empty($member['discountids'])) {
        return;
    }
    if ( eme_is_serialized( $member['discountids'] ) ) {
        $applied_discounts = eme_unserialize( $member['discountids'] );
        $applied_discountids = array_keys($applied_discounts);
    } else {
        $applied_discountids = explode( ',', $member['discountids'] );
    }
    foreach ($applied_discountids as $discountid) {
        $discount = eme_get_discount( $discountid );
        if ( $discount ) {
            $discount_count_to_use = 1;
            eme_increase_discount_count( $discount['id'], $discount_count_to_use );
            if ( ! empty( $discount['properties']['voucher'] ) && $discount['type'] == EME_DISCOUNT_TYPE_FIXED && !empty($applied_discounts[ $discount['id'])) {
                $discount['value'] -= $applied_discounts[ $discount['id']];
                eme_db_update_discount( $discount['id'], $discount);
            }
        }
    }
}

function eme_decrease_discount_booking_count( $id, $booking ) {
	$discount = eme_get_discount( $id );
	if ( $discount ) {
		$count = $discount['count'];
		if ( $discount['use_per_seat'] > 0 ) {
			if ( $count < $booking['booking_seats'] ) {
				$decrease = $count;
			} else {
				$decrease = $booking['booking_seats'];
			}
		} elseif ( $count > 0 ) {
				$decrease = 1;
		} else {
				$decrease = 0;
		}
		eme_decrease_discount_count( $discount['id'], $decrease );
	}
}

function eme_decrease_discount_member_count( $id, $member ) {
	$discount = eme_get_discount( $id );
	if ( $discount ) {
		$count = $discount['count'];
		if ( $count > 0 ) {
			$decrease = 1;
		} else {
			$decrease = 0;
		}
		eme_decrease_discount_count( $discount['id'], $decrease );
	}
}

function eme_get_discount( $id ) {
	global $wpdb;
    if ( !is_numeric( $id ) ) {
        return eme_get_discount_by_name( $id );
    }
	$table    = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql      = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
	$discount = $wpdb->get_row( $sql, ARRAY_A );
	if ( $discount ) {
			$discount['properties'] = eme_init_discount_props( eme_unserialize( $discount['properties'] ) );
			return $discount;
	} else {
			return false;
	}
}
function eme_get_discount_name( $id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT name FROM $table WHERE id = %d", $id );
	return $wpdb->get_var( $sql );
}
function eme_get_dgroup_name( $id ) {
	global $wpdb;
	$table = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$sql   = $wpdb->prepare( "SELECT name FROM $table WHERE id = %d", $id );
	return $wpdb->get_var( $sql );
}

function eme_get_discount_by_name( $name ) {
	global $wpdb;
    if ( is_numeric( $name ) ) {
        return eme_get_discount( $name );
    }
	$table    = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$sql      = $wpdb->prepare( "SELECT * FROM $table WHERE name = %s", $name );
	$discount = $wpdb->get_row( $sql, ARRAY_A );
	if ( $discount ) {
			$discount['properties'] = eme_init_discount_props( eme_unserialize( $discount['properties'] ) );
			return $discount;
	} else {
			return false;
	}
}

function eme_get_person_used_discount_count( $person_id, $discount_id) {
	global $wpdb;
	$table    = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    // Use LIKE as a pre-filter to reduce rows
    $like_pattern = '%' . $wpdb->esc_like( $discount_id ) . '%';

    $bookings_table = EME_DB_PREFIX . EME_BOOKINGS_TBNAME;
    $members_table = EME_DB_PREFIX . EME_MEMBERS_TBNAME;
    
    // Use UNION to combine both queries into one database call
    $sql = $wpdb->prepare( 
        "SELECT discountids FROM $bookings_table 
         WHERE person_id = %d 
         AND discountids LIKE %s
         UNION ALL
         SELECT discountids FROM $members_table 
         WHERE person_id = %d 
         AND discountids LIKE %s",
        $person_id, $like_pattern,
        $person_id, $like_pattern
    );
    
    $discountids_results = $wpdb->get_col( $sql );
    $count = 0;
    foreach ( $discountids_results as $discountids ) {
        if ( eme_is_serialized( $discountids ) ) {
            // Serialized format: unserialize and check keys
            $applied_discounts = eme_unserialize( $discountids );
            if ( is_array( $applied_discounts ) && array_key_exists( $discount_id, $applied_discounts ) ) {
                $count++;
            }
        } else {
            // CSV format: split and check
            $discount_ids = explode( ',', $discountids );
            if ( in_array( $discount_id, $discount_ids ) ) {
                $count++;
            }
        }
    }
    return $count;
}

function eme_calc_booking_discount( $discount, $booking ) {
	// check valid from/to
	if ( ! eme_is_empty_datetime( $discount['valid_from'] ) ) {
		$eme_date_obj_now   = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$eme_valid_from_obj = new emeExpressiveDate( $discount['valid_from'], EME_TIMEZONE );
		if ( $eme_valid_from_obj > $eme_date_obj_now ) {
			return false;
		}
	}
	if ( ! eme_is_empty_datetime( $discount['valid_to'] ) ) {
		$eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$eme_valid_to_obj = new emeExpressiveDate( $discount['valid_to'], EME_TIMEZONE );
		if ( $eme_valid_to_obj < $eme_date_obj_now ) {
			return false;
		}
	}
	$is_user_logged_in = is_user_logged_in();

	// check if not max usage count reached
	if ( $discount['maxcount'] > 0 && $discount['count'] >= $discount['maxcount'] ) {
		return false;
	}

	// check if invitation is required
	if ( ! empty( $discount['properties']['invite_only'] ) && ! eme_check_invite_url( $booking['event_id'] ) ) {
		return false;
	}

	// check if logged in needed
	if ( ! empty( $discount['properties']['wp_users_only'] ) && ! $is_user_logged_in ) {
		return false;
	}
 
	// check max usage count per person
	if ( ! empty( $discount['properties']['maxcount_pp'] )) {
        if ( ! $is_user_logged_in ) {
            return false;
        }
        $person_id = eme_get_personid_by_wpid(get_current_user_id());
        // if person not found: can be they never booked before so it's ok
        if (!empty($person_id)) {
            $person_used_discount_count = eme_get_person_used_discount_count($person_id, $discount['id']);
            if ($person_used_discount_count >= $discount['properties']['maxcount_pp']) {
                return false;
            }
        }
	}
 
	// check min seats
	if ( $discount['properties']['min_seats'] !== '' && $booking['booking_seats']<intval($discount['properties']['min_seats']) ) {
		return false;
	}
	// check max seats
	if ( $discount['properties']['max_seats'] !== '' && $booking['booking_seats']>intval($discount['properties']['max_seats']) ) {
		return false;
	}

	// check wp role
	if ( ! empty( $discount['properties']['wp_role'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$wp_user = wp_get_current_user();
		if ( ! in_array( $discount['properties']['wp_role'], (array) $wp_user->roles ) ) {
			return false;
		}
	}

	// check group memberships
	if ( ! empty( $discount['properties']['group_ids'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$current_userid = get_current_user_id();
		$person         = eme_get_person_by_wp_id( $current_userid );
		if ( empty( $person ) ) {
			return false;
		}
		$person_groupids = eme_get_persongroup_ids( $person['person_id'] );
		if ( ! empty($person_groupids ) ) {
			$res_intersect = array_intersect( $person_groupids, $discount['properties']['group_ids'] );
		} else {
			$res_intersect = 0;
		}
		if ( empty( $res_intersect ) ) {
			return false;
		}
	}

	// check memberships
	if ( ! empty( $discount['properties']['membership_ids'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$current_userid = get_current_user_id();
		$membershipids  = eme_get_active_membershipids_by_wpid( $current_userid );
		if ( ! empty($membershipids ) ) {
			$res_intersect = array_intersect( $membershipids, $discount['properties']['membership_ids'] );
		} else {
			$res_intersect = 0;
		}
		if ( empty( $res_intersect ) ) {
			return false;
		}
	}

	$discount_calculated = 0;
	$dcodes_used         = $booking['dcodes_used'];
	if ( $discount['type'] == EME_DISCOUNT_TYPE_CODE && has_filter( 'eme_discount_' . $discount['name'] ) ) {
		// discount type=code: via own filters, based on the discount name
		$tmp_res             = apply_filters( 'eme_discount_' . $discount['name'], $booking );
		$discount_calculated = sprintf( '%01.2f', $tmp_res );
	} elseif ( ! empty( $discount['valid_to'] ) && $discount['coupon'] == '' ) {
		// if an expiration date is set but not a coupon value, we consider this a discount for "early birds"
		// no #_DISCOUNT is needed then
		$discount_calculated = eme_calc_booking_single_discount( $discount, $booking );
	} else {
		$dcodes_entered = $booking['dcodes_entered'];
		foreach ( $dcodes_entered as $dcode_entered ) {
			if ( in_array( $dcode_entered, $dcodes_used ) ) {
				continue;
			}
			$discount_calculated = eme_calc_booking_single_discount( $discount, $booking, $dcode_entered );
			if ( $discount_calculated ) {
				$dcodes_used[] = $dcode_entered;
				break;
			}
		}
	}
	return [ $discount_calculated, $dcodes_used ];
}

function eme_calc_booking_single_discount( $discount, $booking, $coupon = '' ) {
	$res = 0;
	if ( ( ! empty( $discount['strcase'] ) && strcmp( $discount['coupon'], $coupon ) === 0 ) ||
		( empty( $discount['strcase'] ) && strcasecmp( $discount['coupon'], $coupon ) === 0 ) ) {
		if ( $discount['type'] == EME_DISCOUNT_TYPE_FIXED ) {
			$res = $discount['value'];
		} elseif ( $discount['type'] == EME_DISCOUNT_TYPE_PCT ) {
			// eme_get_total_booking_price by default takes the discount and extra charge into account
			// not that it matters for discount, as for now the discount is 0
			// and for the discount calc, we don't want the extra charge taken into account either
			$ignore_extras = 1;
			$price         = eme_get_total_booking_price( $booking, $ignore_extras );
			$res           = sprintf( '%01.2f', $price * $discount['value'] / 100 );
		} elseif ( $discount['type'] == EME_DISCOUNT_TYPE_FIXED_PER_SEAT ) {
			// discount per seat, but if we also want discount usage per seat, we can only
			// take into account the "free" discount codes
			if ( ! empty( $discount['use_per_seat'] ) ) {
				if ( $discount['maxcount'] > 0 && $discount['count'] + $booking['booking_seats'] > $discount['maxcount'] ) {
					$discount_count_to_use = $discount['maxcount'] - $discount['count'];
					$res                   = $discount['value'] * $discount_count_to_use;
				} else {
					$res = $discount['value'] * $booking['booking_seats'];
				}
			} else {
				$res = $discount['value'] * $booking['booking_seats'];
			}
		}
	}
	return $res;
}

function eme_calc_member_discount( $discount, $member ) {
	// check valid from/to
	if ( ! eme_is_empty_datetime( $discount['valid_from'] ) ) {
		$eme_date_obj_now   = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$eme_valid_from_obj = new emeExpressiveDate( $discount['valid_from'], EME_TIMEZONE );
		if ( $eme_valid_from_obj > $eme_date_obj_now ) {
			return false;
		}
	}
	if ( ! eme_is_empty_datetime( $discount['valid_to'] ) ) {
		$eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
		$eme_valid_to_obj = new emeExpressiveDate( $discount['valid_to'], EME_TIMEZONE );
		if ( $eme_valid_to_obj < $eme_date_obj_now ) {
			return false;
		}
	}
	$is_user_logged_in = is_user_logged_in();

	// check if not max usage count reached
	if ( $discount['maxcount'] > 0 && $discount['count'] >= $discount['maxcount'] ) {
		return false;
	}

	// check if logged in needed
	if ( ! empty( $discount['properties']['wp_users_only'] ) && ! $is_user_logged_in ) {
		return false;
	}

	// check wp role
	if ( ! empty( $discount['properties']['wp_role'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$wp_user = wp_get_current_user();
		if ( ! in_array( $discount['properties']['wp_role'], (array) $wp_user->roles ) ) {
			return false;
		}
	}

	// check group memberships
	if ( ! empty( $discount['properties']['group_ids'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$current_userid = get_current_user_id();
		$person         = eme_get_person_by_wp_id( $current_userid );
		if ( empty( $person ) ) {
			return false;
		}
		$person_groupids = eme_get_persongroup_ids( $person['person_id'] );
		$res_intersect   = array_intersect( $person_groupids, $discount['properties']['group_ids'] );
		if ( empty( $person_groupids ) || empty( $res_intersect ) ) {
			return false;
		}
	}

	// check memberships
	if ( ! empty( $discount['properties']['membership_ids'] ) ) {
		if ( ! $is_user_logged_in ) {
			return false;
		}
		$current_userid = get_current_user_id();
		$membershipids  = eme_get_active_membershipids_by_wpid( $current_userid );
		$res_intersect  = array_intersect( $membershipids, $discount['properties']['membership_ids'] );
		if ( empty( $membershipids ) || empty( $res_intersect ) ) {
			return false;
		}
	}

	$discount_calculated = 0;
	$dcodes_used         = $member['dcodes_used'];
	if ( $discount['type'] == EME_DISCOUNT_TYPE_CODE && has_filter( 'eme_discount_' . $discount['name'] ) ) {
		// discount type=code: via own filters, based on the discount name
		$tmp_res             = apply_filters( 'eme_discount_' . $discount['name'], $member );
		$discount_calculated = sprintf( '%01.2f', $tmp_res );
	} elseif ( ! empty( $discount['valid_to'] ) && $discount['coupon'] == '' ) {
		// if an expiration date is set but not a coupon value, we consider this a discount for "early birds"
		// no #_DISCOUNT is needed then
		$discount_calculated = eme_calc_member_single_discount( $discount, $member );
	} else {
		$dcodes_entered = $member['dcodes_entered'];
		foreach ( $dcodes_entered as $dcode_entered ) {
			if ( in_array( $dcode_entered, $dcodes_used ) ) {
				continue;
			}
			$discount_calculated = eme_calc_member_single_discount( $discount, $member, $dcode_entered );
			if ( $discount_calculated ) {
				$dcodes_used[] = $dcode_entered;
				break;
			}
		}
	}
	return [ $discount_calculated, $dcodes_used ];
}

function eme_calc_member_single_discount( $discount, $member, $coupon = '' ) {
	$res = 0;
	if ( ( ! empty( $discount['strcase'] ) && strcmp( $discount['coupon'], $coupon ) === 0 ) ||
		( empty( $discount['strcase'] ) && strcasecmp( $discount['coupon'], $coupon ) === 0 ) ) {
		if ( $discount['type'] == EME_DISCOUNT_TYPE_FIXED ) {
			$res = $discount['value'];
		} elseif ( $discount['type'] == EME_DISCOUNT_TYPE_PCT ) {
			// eme_get_total_member_price by default takes the discount and extra charge into account
			// not that it matters for discount, as for now the discount is 0
			// and for the discount calc, we don't want the extra charge taken into account either
			$ignore_extras = 1;
			$price         = eme_get_total_member_price( $member, $ignore_extras );
			$res           = sprintf( '%01.2f', $price * $discount['value'] / 100 );
		}
	}
	return $res;
}

function eme_get_discounttypes() {
	$types = [
		EME_DISCOUNT_TYPE_FIXED          => __( 'Fixed', 'events-made-easy' ),
		EME_DISCOUNT_TYPE_PCT            => __( 'Percentage', 'events-made-easy' ),
		EME_DISCOUNT_TYPE_CODE           => __( 'Code', 'events-made-easy' ),
		EME_DISCOUNT_TYPE_FIXED_PER_SEAT => __( 'Fixed per seat', 'events-made-easy' ),
	];
	return $types;
}

function eme_get_discounttype( $type ) {
	$types = eme_get_discounttypes();
	return $types[ $type ];
}

add_action( 'wp_ajax_eme_discounts_list', 'eme_ajax_discounts_list' );
add_action( 'wp_ajax_eme_manage_discounts', 'eme_ajax_manage_discounts' );
add_action( 'wp_ajax_eme_discount_edit', 'eme_ajax_discount_edit' );
add_action( 'wp_ajax_eme_discountgroups_list', 'eme_ajax_discountgroups_list' );
add_action( 'wp_ajax_eme_manage_discountgroups', 'eme_ajax_manage_discountgroups' );
add_action( 'wp_ajax_eme_discountgroups_edit', 'eme_ajax_discountgroups_edit' );
add_action( 'wp_ajax_eme_discounts_select2', 'eme_ajax_discounts_select2' );
add_action( 'wp_ajax_eme_dgroups_select2', 'eme_ajax_dgroups_select2' );

function eme_ajax_discounts_list() {
	global $wpdb;
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
	$table        = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$fTableResult = [];
	// The toolbar search input
	$q           = isset( $_POST['q'] ) ? eme_sanitize_request($_POST['q']) : '';
	$opt         = isset( $_POST['opt'] ) ? eme_sanitize_request($_POST['opt']) : '';
	$where       = '';
	$where_array = [];
	if ( $q ) {
		for ( $i = 0; $i < count( $opt ); $i++ ) {
				$fld           = esc_sql( $opt[ $i ] );
				$where_array[] = "`$fld` LIKE '%" . esc_sql( $wpdb->esc_like( $q[ $i ] ) ) . "%'";
		}
		$where = ' WHERE ' . implode( ' AND ', $where_array );
	}
	if ( current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		$sql         = "SELECT COUNT(*) FROM $table $where";
		$recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
		$orderby     = eme_get_datatables_orderby();

		$sql  = "SELECT * FROM $table $where $orderby $limit";
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $rows as $key => $row ) {
			$selected_dgroup = $row['dgroup'];
			// let's see if the dgroup is an csv of integers, if so: convert to names
			$selected_dgroup_arr = explode( ',', $selected_dgroup );
			$res_arr             = [];
            foreach ( $selected_dgroup_arr as $dgroup_name ) { // array can be mix of names and ids
                $dgroup = eme_get_discountgroup( $dgroup_name );
                if ( ! empty( $dgroup ) ) {
                    $res_arr[] = $dgroup['name'];
                }
            }
            $selected_dgroup = join( ',', $res_arr );
			$rows[ $key ]['dgroup'] = $selected_dgroup;
			if ( ! empty( $row['valid_from'] ) ) {
				$rows[ $key ]['valid_from'] = eme_localized_datetime( $row['valid_from'], EME_TIMEZONE, 1 );
			} else {
				$rows[ $key ]['valid_from'] = '';
			}
			if ( ! empty( $row['valid_to'] ) ) {
				$rows[ $key ]['valid_to'] = eme_localized_datetime( $row['valid_to'], EME_TIMEZONE, 1 );
			} else {
				$rows[ $key ]['valid_to'] = '';
			}
			$rows[ $key ]['type']             = eme_get_discounttype( $row['type'] );
				$rows[ $key ]['strcase']      = ( $row['strcase'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
				$rows[ $key ]['use_per_seat'] = ( $row['use_per_seat'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
				$rows[ $key ]['name']         = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-discounts&amp;eme_admin_action=edit_discount&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . $row['name'] . '</a>';
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

function eme_ajax_discountgroups_list() {
	global $wpdb;
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
	$table        = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$fTableResult = [];
	// The toolbar search input
	$q           = isset( $_POST['q'] ) ? eme_sanitize_request($_POST['q']) : '';
	$opt         = isset( $_POST['opt'] ) ? eme_sanitize_request($_POST['opt']) : '';
	$where       = '';
	$where_array = [];
	if ( $q ) {
		for ( $i = 0; $i < count( $opt ); $i++ ) {
				$fld           = esc_sql( $opt[ $i ] );
				$where_array[] = "`$fld` LIKE '%" . esc_sql( $wpdb->esc_like( $q[ $i ] ) ) . "%'";
		}
		$where = ' WHERE ' . implode( ' AND ', $where_array );
	}
	if ( current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		$sql         = "SELECT COUNT(*) FROM $table $where";
		$recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
		$orderby     = eme_get_datatables_orderby();

		$sql  = "SELECT * FROM $table $where $orderby $limit";
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $rows as $key => $row ) {
				$rows[ $key ]['name'] = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-discounts&amp;eme_admin_action=edit_dgroup&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . $row['name'] . '</a>';
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

function eme_ajax_discounts_select2() {
	global $wpdb;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if (! current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		wp_die();
	}
	$table        = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
	$fTableResult = [];
	$q            = isset( $_REQUEST['q'] ) ? strtolower( eme_sanitize_request( $_REQUEST['q'] ) ) : '';
	if ( ! empty( $q ) ) {
			$where = "(name LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%')";
	} else {
			$where = '(1=1)';
	}

	$pagesize    = intval( $_REQUEST['pagesize'] );
	$start       = isset( $_REQUEST['page'] ) ? (intval( $_REQUEST['page'] ) -1) * $pagesize : 0;
	$count_sql   = "SELECT COUNT(*) FROM $table WHERE $where";
	$recordCount = $wpdb->get_var( $count_sql );
	$search      = "$where ORDER BY name LIMIT $start,$pagesize";

	$records   = [];
	$discounts = eme_get_discounts( $search );
	foreach ( $discounts as $discount ) {
		$record       = [];
		$record['id'] = $discount['id'];
		// no eme_esc_html here, select2 does it own escaping upon arrival
		$record['text'] = $discount['name'];
		$records[]      = $record;
	}
	$fTableResult['TotalRecordCount'] = $recordCount;
	$fTableResult['Records']          = $records;
	print wp_json_encode( $fTableResult );
	wp_die();
}

function eme_ajax_dgroups_select2() {
	global $wpdb;

	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if (! current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		wp_die();
	}
	$table        = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
	$fTableResult = [];
	$q            = isset( $_REQUEST['q'] ) ? strtolower( eme_sanitize_request( $_REQUEST['q'] ) ) : '';
	if ( ! empty( $q ) ) {
			$where = "(name LIKE '%" . esc_sql( $wpdb->esc_like( $q ) ) . "%')";
	} else {
			$where = '(1=1)';
	}

	$pagesize    = intval( $_REQUEST['pagesize'] );
	$start       = isset( $_REQUEST['page'] ) ? (intval( $_REQUEST['page'] ) -1) * $pagesize : 0;
	$count_sql   = "SELECT COUNT(*) FROM $table WHERE $where";
	$recordCount = $wpdb->get_var( $count_sql );
	$search      = "$where ORDER BY name LIMIT $start,$pagesize";

	$records = [];
	$dgroups = eme_get_dgroups( $search );
	foreach ( $dgroups as $dgroup ) {
		$record       = [];
		$record['id'] = $dgroup['id'];
		// no eme_esc_html here, select2 does it own escaping upon arrival
		$record['text'] = $dgroup['name'];
		$records[]      = $record;
	}
	$fTableResult['TotalRecordCount'] = $recordCount;
	$fTableResult['Records']          = $records;
	print wp_json_encode( $fTableResult );
	wp_die();
}

function eme_ajax_manage_discounts() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if (! current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		wp_die();
	}
	$ajaxResult           = [];
	$ajaxResult['Result'] = 'OK';
	if ( isset( $_REQUEST['do_action'] ) ) {
		$do_action = eme_sanitize_request( $_REQUEST['do_action'] );
		switch ( $do_action ) {
			case 'deleteDiscounts':
				eme_ajax_record_delete( EME_DISCOUNTS_TBNAME, 'eme_cap_discounts', 'id' );
				$ajaxResult['Message'] = __( 'Discounts deleted', 'events-made-easy' );
				break;
			case 'changeValidFrom':
				$date    = ( isset( $_REQUEST['new_validfrom'] ) ) ? eme_sanitize_request( $_REQUEST['new_validfrom'] ) : '';
				$ids_arr = explode( ',', eme_sanitize_request($_REQUEST['id']) );
				if ( eme_is_datetime( $date ) && eme_is_numeric_array( $ids_arr ) ) {
					foreach ( $ids_arr as $discount_id ) {
							eme_change_discount_validfrom( $discount_id, $date );
					}
				}
				$ajaxResult['Message'] = __( 'Date changed.', 'events-made-easy' );
				break;
			case 'changeValidTo':
				$date    = ( isset( $_REQUEST['new_validto'] ) ) ? eme_sanitize_request( $_REQUEST['new_validto'] ) : '';
				$ids_arr = explode( ',', eme_sanitize_request($_REQUEST['id']) );
				if ( eme_is_datetime( $date ) && eme_is_numeric_array( $ids_arr ) ) {
					foreach ( $ids_arr as $discount_id ) {
						eme_change_discount_validto( $discount_id, $date );
					}
				}
				$ajaxResult['Message'] = __( 'Date changed.', 'events-made-easy' );
				break;
			case 'addToGroup':
				$group_id = ( isset( $_REQUEST['addtogroup'] ) ) ? intval( $_REQUEST['addtogroup'] ) : 0;
				$ids_arr  = explode( ',', eme_sanitize_request($_REQUEST['id']) );
				if ( eme_is_numeric_array( $ids_arr ) ) {
					foreach ( $ids_arr as $discount_id ) {
						eme_add_discount_to_group( $discount_id, $group_id );
					}
				}
				$ajaxResult['Message'] = __( 'Discounts added to group.', 'events-made-easy' );
				break;
			case 'removeFromGroup':
				$group_id = ( isset( $_REQUEST['removefromgroup'] ) ) ? intval( $_REQUEST['removefromgroup'] ) : 0;
				$ids_arr  = explode( ',', eme_sanitize_request($_REQUEST['id']) );
				if ( eme_is_numeric_array( $ids_arr ) ) {
					foreach ( $ids_arr as $discount_id ) {
						eme_remove_discount_from_group( $discount_id, $group_id );
					}
				}
				$ajaxResult['Message'] = __( 'Discounts removed from group.', 'events-made-easy' );
				break;

		}
	}
	print wp_json_encode( $ajaxResult );
	wp_die();
}

function eme_ajax_manage_discountgroups() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if (! current_user_can( get_option( 'eme_cap_discounts' ) ) ) {
		wp_die();
	}
	$ajaxResult           = [];
	$ajaxResult['Result'] = 'OK';
	if ( isset( $_REQUEST['do_action'] ) ) {
		$do_action = eme_sanitize_request( $_REQUEST['do_action'] );
		switch ( $do_action ) {
			case 'deleteDiscountGroups':
				eme_ajax_record_delete( EME_DISCOUNTGROUPS_TBNAME, 'eme_cap_discounts', 'id' );
				$ajaxResult['Message'] = __( 'Discount groups deleted.', 'events-made-easy' );
				break;
		}
	}
	print wp_json_encode( $ajaxResult );
	wp_die();
}

