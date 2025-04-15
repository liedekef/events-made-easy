<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_set_wpmail_html_content_type() {
    return 'text/html';
}

// for backwards compat, the fromname and email are after the replyto and can be empty
function eme_send_mail( $subject, $body, $receiveremail, $receivername = '', $replytoemail = '', $replytoname = '', $fromemail = '', $fromname = '', $atts_arr = [], $custom_headers = [] ) {
    $subject  = preg_replace( '/(^\s+|\s+$)/m', '', $subject );
    $res      = true;
    $message  = '';
    $debugtxt = '';

    // nothing to send? Then act as if all is ok
    if ( empty( $body ) || empty( $subject ) || empty( $receiveremail ) ) {
        return [ $res, $message ];
    }

    if ( empty( $fromemail ) ) {
        $fromemail = $replytoemail;
        $fromname  = $replytoname;
    }
    // if forced or fromemail is still empty
    if ( get_option( 'eme_mail_force_from' ) || empty( $fromemail ) ) {
        [$fromname, $fromemail] = eme_get_default_mailer_info();
    }
    // now the from should never be empty, so just check reply to again
    if ( empty( $replytoemail ) ) {
        $replytoemail = $fromemail;
    }
    if ( empty( $replytoname ) ) {
        $replytoname = $fromname;
    }

    // get all mail options, put them in an array and apply filter
    // if you change this array, don't forget to update the doc
    $mailoptions = [
        'fromMail'         => $fromemail,
        'fromName'         => $fromname,
        'toMail'           => $receiveremail,
        'toName'           => $receivername,
        'replytoMail'      => $replytoemail,
        'replytoName'      => $replytoname,
        'bcc_addresses'    => get_option( 'eme_mail_bcc_address', '' ),
        'mail_send_method' => get_option( 'eme_mail_send_method' ), // smtp, mail, sendmail, qmail, wp_mail
        'send_html'        => get_option( 'eme_mail_send_html' ), // true or false
        'smtp_host'        => get_option( 'eme_smtp_host', 'localhost' ),
        'smtp_encryption'  => get_option( 'eme_smtp_encryption' ), // none, tls or ssl
        'smtp_verify_cert' => get_option( 'eme_smtp_verify_cert' ),  // true or false
        'smtp_port'        => intval(get_option( 'eme_smtp_port', 25 )),
        'smtp_auth'        => get_option( 'eme_smtp_auth' ), // 0 or 1, false or true
        'smtp_username'    => get_option( 'eme_smtp_username', '' ),
        'smtp_password'    => get_option( 'eme_smtp_password', '' ),
        'smtp_debug'       => get_option( 'eme_smtp_debug' ),  // true or false
    ];
    $mailoptions = apply_filters( 'eme_filter_mail_options', $mailoptions );

    if ( empty( $mailoptions['smtp_host'] ) ) {
        $mailoptions['smtp_host'] = 'localhost';
    }
    if ( empty( $mailoptions['smtp_port'] ) ) {
        $mailoptions['smtp_port'] = 25;
    }

    $bcc_addresses = preg_split( '/,|;/', $mailoptions['bcc_addresses'] );

    // allow either an array of file paths or of attachment ids
    $attachment_paths_arr = [];
    if ( ! is_array( $atts_arr ) ) {
        $atts_arr = [];
    }
    foreach ( $atts_arr as $attachment ) {
        if ( ! empty( $attachment ) ) {
            if ( is_numeric( $attachment ) ) {
                $file_path = get_attached_file( $attachment );
                if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
                    $attach_name = eme_sanitize_attach_filename(basename($file_path));
                    if (!empty($attach_name))
                        $attachment_paths_arr[$attach_name] = $file_path;
                }
            } elseif ( is_array( $attachment ) ) {
                // an array: the first element is the desired attach name, the second the real path of the file to attach
                if ( file_exists( $attachment[1] ) ) {
                    if (eme_is_empty_string($attachment[0])) {
                        // if no desired name, we base ourselves on the real path but remove some ugly parts
                        $filename = pathinfo($attachment[1], PATHINFO_FILENAME);
                        $extension = pathinfo($attachment[1], PATHINFO_EXTENSION);
                        if (empty($extension))
                            $extension = "none";
                        // now remove parts of the file
                        $filename = preg_replace( '/(member-\d+|booking-\d+)-.*/', '$1', $filename );
                        $filename = preg_replace( '/.*-(qrcode.*)/', '$1', $filename );
                        $attach_name = eme_sanitize_attach_filename($filename.'.'.$extension);
                        $attachment_paths_arr[$attach_name] = $attachment[1];
                    } else {
                        $filename = eme_sanitize_attach_filename($attachment[0]);
                        $attachment_paths_arr[$filename] = $attachment[1];
                    }
                }
            } else {
                // if it is not a numeric id, it is a file path (like for pdf tickets)
                if ( file_exists( $attachment ) ) {
                    $filename = pathinfo($attachment, PATHINFO_FILENAME);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    if (empty($extension))
                        $extension = "none";
                    // now remove parts of the file
                    $filename = preg_replace( '/(member-\d+|booking-\d+)-.*/', '$1', $filename );
                    $filename = preg_replace( '/.*-(qrcode.*)/', '$1', $filename );
                    $attach_name = eme_sanitize_attach_filename($filename.'.'.$extension);
                    $attachment_paths_arr[$attach_name] = $attachment;
                }
            }
        }
    }

    if ( ! in_array( $mailoptions['mail_send_method'], [ 'smtp', 'mail', 'sendmail', 'qmail', 'wp_mail' ] ) ) {
        $mailoptions['mail_send_method'] = 'wp_mail';
    }

    if ( $mailoptions['mail_send_method'] == 'wp_mail' ) {
        // Set the correct mail headers (the first 2 are to try to avoid auto-repliers)
        $headers[] = 'Auto-Submitted: auto-generated';
        $headers[] = 'X-Auto-Response-Suppress: all';
        $headers[] = 'From: '.$mailoptions['fromName'].' <'.$mailoptions['fromMail'].'>';
        if ( !empty($mailoptions['replytoMail']) && eme_is_email($mailoptions['replytoMail'])) {
            $headers[] = 'Reply-To: '.$mailoptions['replytoName'].' <'.$mailoptions['replytoMail'].'>';
        }
        if ( ! empty( $mailoptions['bcc_addresses'] ) ) {
            foreach ( $bcc_addresses as $bcc_address ) {
                if (eme_is_email($bcc_address)) {
                    $headers[] = 'Bcc: ' . trim( $bcc_address );
                }
            }
        }
        if ( ! empty( $custom_headers ) && is_array( $custom_headers ) ) {
            foreach ( $custom_headers as $custom_header ) {
                $headers[] = $custom_header;
            }
        }

        // set the correct content type
        if ( $mailoptions['send_html'] ) {
            $body = eme_nl2br_save_html( $body );
            // set the content-type header, wp-mail knows about this one
            // it is cleaner than add_filter/remove_filter of wp_mail_content_type ...
            $headers[] = 'Content-type: text/html';
            //add_filter( 'wp_mail_content_type', 'eme_set_wpmail_html_content_type' );
        }

        // now send it
        if ( ! empty( $mailoptions['toMail'] ) ) {
            $res = wp_mail( $mailoptions['toMail'], $subject, $body, $headers, $attachment_paths_arr );
            if ( ! $res ) {
                $message = __( 'There were some problems while sending mail.', 'events-made-easy' );
            }
        } else {
            $res     = false;
            $message = __( 'Empty email', 'events-made-easy' );
        }

        // Reset content-type to avoid conflicts
        //if ( $mailoptions['send_html'] ) {
        //	remove_filter( 'wp_mail_content_type', 'eme_set_wpmail_html_content_type' );
        //}
    } else {
        // we prefer the new location first
        if ( file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
        } else {
            // for older wp instances (pre 5.5)
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            $mail = new PHPMailer();
        }

        $mail->ClearAllRecipients();
        $mail->ClearAddresses();
        $mail->ClearAttachments();
        $mail->clearCustomHeaders();
        $mail->clearReplyTos();
        $mail->CharSet = 'utf-8';
        // avoid the x-mailer header
        $mail->XMailer = ' ';
        // Set the correct mail headers (the first 2 are to try to avoid auto-repliers)
        $mail->addCustomHeader('Auto-Submitted: auto-generated');
        $mail->addCustomHeader('X-Auto-Response-Suppress: all');
        // add custom headers
        if ( ! empty( $custom_headers ) && is_array( $custom_headers ) ) {
            foreach ( $custom_headers as $custom_header ) {
                $mail->addCustomHeader( $custom_header );
            }
        }
        //$mail->SetLanguage( 'en', __DIR__ . '/' );

        if ( $mailoptions['mail_send_method'] == 'qmail' ) {
            $mail->IsQmail();
        } else {
            $mail->Mailer = $mailoptions['mail_send_method'];
        }

        if ( $mailoptions['mail_send_method'] == 'smtp' ) {
            // let us keep a normal smtp timeout ...
            $mail->Timeout = 10;
            $mail->Host    = $mailoptions['smtp_host'];

            // we set optional encryption and port settings
            // but if the Host contains ssl://, tls:// or port info, it will take precedence over these anyway
            // so it is not bad at all :-)
            if ( $mailoptions['smtp_encryption'] == 'tls' || $mailoptions['smtp_encryption'] == 'ssl' ) {
                $mail->SMTPSecure = $mailoptions['smtp_encryption'];
            } else {
                // if we don't want encryption, let's disable autotls too, since that might be a problem
                $mail->SMTPAutoTLS = false;
            }

            if ( ! $mailoptions['smtp_verify_cert'] ) {
                // let's disable certificate verification, but only for reserved ranges
                // weirdly the private range filter doesn't contain 127.0.0.0/8, so we use reserved
                //    range which is still internal
                $tmp_ip = $mail->Host;
                // remove the possible ssl:// or tls://
                $tmp_ip = preg_replace( '/.*?:\/\//', '', $tmp_ip );
                // if the host setting is not an ip, resolve it and get the ip
                if ( ! filter_var( $tmp_ip, FILTER_VALIDATE_IP ) ) {
                    $lookup = dns_get_record( $tmp_ip );
                    if ( $lookup ) {
                        foreach ( $lookup as $res ) {
                            if ( isset( $res['ip'] ) ) {
                                $tmp_ip = $res['ip'];

                            } elseif ( isset( $res['ipv6'] ) ) {
                                $tmp_ip = $res['ipv6'];

                            }
                            // we're only interested in 1 result
                            break;
                        }
                    }
                }

                $in_reserved_range = 0;
                if ( filter_var( $tmp_ip, FILTER_VALIDATE_IP )
                    && ! filter_var( $tmp_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE ) ) {
                    // ip in the reserved range? then we still set it only if the ip is valid
                    $in_reserved_range = 1;
                }

                // so now we disable cert verification as requested and allow self signed
                // but only for ip's in the reserved range
                if ( $in_reserved_range ) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer'       => false,
                            'verify_peer_name'  => false,
                            'allow_self_signed' => true,
                        ],
                    ];
                }
            }

            $mail->Port = intval( $mailoptions['smtp_port'] );

            if ( $mailoptions['smtp_auth'] ) {
                $mail->SMTPAuth = true;
                $mail->Username = $mailoptions['smtp_username'];
                $mail->Password = $mailoptions['smtp_password'];
            }
            if ( $mailoptions['smtp_debug'] ) {
                $mail->SMTPDebug           = 2;
                $mail->Debugoutput         = function( $str, $level ) use (&$debugtxt) {
                    $debugtxt .= "$level: $str\n";
                };
            }
        }
        $mail->setFrom( $mailoptions['fromMail'], $mailoptions['fromName'] );
        $altbody = eme_replacelinks( $body );
        if ( $mailoptions['send_html'] ) {
            $mail->isHTML( true );
            // Convert all message body line breaks to CRLF, makes quoted-printable encoding work much better
            $mail->AltBody = $mail->normalizeBreaks( $mail->html2text( $altbody ) );
            $mail->Body    = $mail->normalizeBreaks( eme_nl2br_save_html( $body ) );
        } else {
            $mail->Body = $mail->normalizeBreaks( $altbody );
        }
        $mail->Subject = $subject;
        if ( ! empty( $mailoptions['replytoMail'] ) && eme_is_email($mailoptions['replytoMail'] ) ) {
            $mail->addReplyTo( $mailoptions['replytoMail'], $mailoptions['replytoName'] );
        }
        if ( ! empty( $mailoptions['bcc_addresses'] ) ) {
            foreach ( $bcc_addresses as $bcc_address ) {
                if (eme_is_email($bcc_address)) {
                    $mail->addBCC( trim( $bcc_address ) );
                }
            }
        }

        if ( ! empty( $attachment_paths_arr ) ) {
            foreach ( $attachment_paths_arr as $filename => $att ) {
                $filename = is_string( $filename ) ? $filename : '';
                $mail->addAttachment( $att, $filename );
            }
        }

        if ( ! empty( $mailoptions['toMail'] ) ) {
            $mail->addAddress( $mailoptions['toMail'], $mailoptions['toName'] );
            if ( ! $mail->send() ) {
                $res     = false;
                $message = $mail->ErrorInfo;
            } else {
                $res = true;
            }
        } else {
            $res     = false;
            $message = __( 'Empty email', 'events-made-easy' );
        }
    }
    // remove the phpmailer url added for some errors
    $message = str_replace( 'https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting', '', $message );
    return [ $res, $message, $debugtxt ];
}

function eme_db_insert_ongoing_mailing( $mailing_name, $subject, $body, $fromemail, $fromname, $replytoemail, $replytoname, $mail_text_html, $conditions = [] ) {
    $now           = current_time( 'mysql', false );
    return eme_db_insert_mailing( $mailing_name, $now, $subject, $body, $fromemail, $fromname, $replytoemail, $replytoname, $mail_text_html, $conditions, "ongoing" );
}

function eme_db_insert_mailing( $mailing_name, $planned_on, $subject, $body, $fromemail, $fromname, $replytoemail, $replytoname, $mail_text_html, $conditions, $status = "initial" ) {
    global $wpdb;
    $mailing_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;

    if ( empty( $fromemail ) ) {
        $fromemail = $replytoemail;
        $fromname  = $replytoname;
    }
    // if forced or fromemail is still empty
    if ( get_option( 'eme_mail_force_from' ) || empty( $fromemail ) ) {
        [$fromname, $fromemail] = eme_get_default_mailer_info();
    }
    // now the from should never be empty, so just check reply to again
    if ( empty( $replytoemail ) ) {
        $replytoemail = $fromemail;
    }
    if ( empty( $replytoname ) ) {
        $replytoname = $fromname;
    }

    $now           = current_time( 'mysql', false );
    $mailing       = [
        'name'           => mb_substr( $mailing_name, 0, 255 ),
        'planned_on'     => $planned_on,
        'status'         => $status,
        'subject'        => mb_substr( $subject, 0, 255 ),
        'body'           => $body,
        'fromemail'      => $fromemail,
        'fromname'       => mb_substr( $fromname, 0, 255 ),
        'replytoemail'   => $replytoemail,
        'replytoname'    => mb_substr( $replytoname, 0, 255 ),
        'mail_text_html' => $mail_text_html,
        'creation_date'  => $now,
        'conditions'     => eme_serialize( $conditions ),
    ];

    // add userid if possible
    $current_userid = get_current_user_id();
    if (!empty($current_userid)) {
        $mailing['created_by'] = $current_userid;
    }

    if ( $wpdb->insert( $mailing_table, $mailing ) === false ) {
        return false;
    } else {
        return $wpdb->insert_id;
    }
}

function eme_queue_fastmail( $subject, $body, $fromemail, $fromname, $receiveremail, $receivername, $replytoemail, $replytoname, $mailing_id = 0, $person_id = 0, $member_id = 0, $atts_arr = [] ) {
    return eme_queue_mail( $subject, $body, $fromemail, $fromname, $receiveremail, $receivername, $replytoemail, $replytoname, $mailing_id, $person_id, $member_id, $atts_arr, 1 );
}

function eme_queue_mail( $subject, $body, $fromemail, $fromname, $receiveremail, $receivername, $replytoemail = '', $replytoname = '', $mailing_id = 0, $person_id = 0, $member_id = 0, $atts_arr = [], $send_immediately = 0 ) {
    global $wpdb;
    $mqueue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;

    if ( eme_is_empty_string( $subject ) || eme_is_empty_string( $body ) ) {
        // no mail to be sent: fake it
        return true;
    }
    // no valid email? return false
    if ( ! eme_is_email( $receiveremail ) ) {
        return false;
    }

    $random_id      = eme_random_id();

    if ( ! get_option( 'eme_queue_mails' ) ) {
        $send_immediately = 1;
    }

    // the next checks are normally not needed if part of a mailing: then this is already done
    // but when calling eme_queue_mail directly (the case for eme_db_insert_ongoing_mailing) it needs to be done
    // so we do it again here (identical as for eme_db_insert_mailing)
    if ( empty( $fromemail ) ) {
        $fromemail = $replytoemail;
        $fromname  = $replytoname;
    }
    // if forced or fromemail is still empty
    if ( get_option( 'eme_mail_force_from' ) || empty( $fromemail ) ) {
        [$fromname, $fromemail] = eme_get_default_mailer_info();
    }
    // now the from should never be empty, so just check reply to again
    if ( empty( $replytoemail ) ) {
        $replytoemail = $fromemail;
    }
    if ( empty( $replytoname ) ) {
        $replytoname = $fromname;
    }

    $add_listhdrs = 0;
    if ( ! empty( $mailing_id ) && eme_add_listhdrs( $mailing_id ) ) {
        $add_listhdrs = 1;
    }

    $now  = current_time( 'mysql', false );
    $mail = [
        'subject'       => mb_substr( $subject, 0, 255 ),
        'body'          => $body,
        'fromemail'     => $fromemail,
        'fromname'      => mb_substr( $fromname, 0, 255 ),
        'receiveremail' => $receiveremail,
        'receivername'  => mb_substr( $receivername, 0, 255 ),
        'replytoemail'  => $replytoemail,
        'replytoname'   => mb_substr( $replytoname, 0, 255 ),
        'mailing_id'    => $mailing_id,
        'person_id'     => $person_id,
        'member_id'     => $member_id,
        'attachments'   => eme_serialize( $atts_arr ),
        'add_listhdrs'  => $add_listhdrs,
        'creation_date' => $now,
        'random_id'     => $random_id,
    ];

    // add userid if possible
    if (!empty($current_userid)) {
        $mail['created_by'] = $current_userid;
    }

    if ( $send_immediately ) {
        // we add the mail to the queue as sent and send it immediately
        $mail['status']        = 1;
        $mail['sent_datetime'] = $now;
        if ( $wpdb->insert( $mqueue_table, $mail ) === false ) {
            return false;
        } else {
            return eme_process_single_mail( $mail );
        }
    } elseif ( $wpdb->insert( $mqueue_table, $mail ) === false ) {
        return false;
    } else {
        return true;
    }
}

function eme_mark_mail_ignored( $id, $random_id ) {
    global $wpdb;
    $mqueue_table            = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $where                   = [];
    $fields                  = [];
    if ( empty( $id ) ) {
        $where['random_id'] = $random_id;
    } else {
        $where['id'] = intval( $id );
    }
    $fields['status']        = 4;
    $fields['sent_datetime'] = current_time( 'mysql', false );
    if ( $wpdb->update( $mqueue_table, $fields, $where ) === false ) {
        return false;
    } else {
        return true;
    }
}

function eme_mark_mail_sent( $id, $random_id ) {
    global $wpdb;
    $mqueue_table            = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $where                   = [];
    $fields                  = [];
    if ( empty( $id ) ) {
        $where['random_id'] = $random_id;
    } else {
        $where['id'] = intval( $id );
    }
    $where['id']             = intval( $id );
    $fields['status']        = 1;
    $fields['sent_datetime'] = current_time( 'mysql', false );
    if ( $wpdb->update( $mqueue_table, $fields, $where ) === false ) {
        return false;
    } else {
        return true;
    }
}

function eme_mark_mail_fail( $id, $random_id, $error_msg = '' ) {
    global $wpdb;
    $mqueue_table            = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $where                   = [];
    $fields                  = [];
    if ( empty( $id ) ) {
        $where['random_id'] = $random_id;
    } else {
        $where['id'] = intval( $id );
    }
    $fields['status']        = 2;
    $fields['error_msg']     = esc_sql( $error_msg );
    $fields['sent_datetime'] = current_time( 'mysql', false );
    if ( $wpdb->update( $mqueue_table, $fields, $where ) === false ) {
        return false;
    } else {
        return true;
    }
}

function eme_cancel_all_queued() {
    global $wpdb;

    // cancel all ongoing and planned mailings
    $ongoing_mailings = eme_get_mailings(status: 'ongoing');
    foreach ( $ongoing_mailings as $mailing) {
        eme_cancel_mailing( $mailing['id'] );
    }
    $planned_mailings = eme_get_mailings(status: 'planned');
    foreach ( $planned_mailings as $mailing) {
        eme_cancel_mailing( $mailing['id'] );
    }

    // cancel individual mails
    $mqueue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql          = "UPDATE $mqueue_table WHERE status=3 WHERE status=0";
    $wpdb->query( $sql );
}

function eme_get_queued_count() {
    global $wpdb;
    $mqueue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    // the queued count is to know how much mails are left unsent in the queue
    $sql = "SELECT COUNT(*) FROM $mqueue_table WHERE status=0";
    $count = $wpdb->get_var( $sql );

    // now also include planned mailings
    $planned_mailings = eme_get_mailings(status: 'planned');
    foreach ($planned_mailings as $mailing) {
        // older mailings inserted the mails directly and not update the stats
        // newer mailings only set the planned stats and update the receivers the moment the mailing starts
        if (empty($mailing['stats'])) {
            $stats = eme_get_mailing_stats( $mailing['id'] );
        } else {
            $stats = eme_unserialize( $mailing['stats'] );
        }
        $count += $stats['planned'];
    }
    return $count;
}

function eme_get_queued( $now ) {
    global $wpdb;
    $mqueue_table   = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    // we take only the queued mails with status=0 where either the planning date for the mailing has passed (so we know those can be send) or that are not part of a mailing
    $sql                  = "SELECT $mqueue_table.* FROM $mqueue_table LEFT JOIN $mailings_table ON $mqueue_table.mailing_id=$mailings_table.id WHERE $mqueue_table.status=0 AND ($mqueue_table.mailing_id=0 OR ($mqueue_table.mailing_id>0 and $mailings_table.planned_on<'$now'))";
    $eme_cron_queue_count = intval( get_option( 'eme_cron_queue_count' ) );
    if ( $eme_cron_queue_count > 0 ) {
        $sql .= " LIMIT $eme_cron_queue_count";
    }
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_rest_send_queued( WP_REST_Request $request ) {
    $force_interval = $request['interval'];
    //if (defined('REST_REQUEST')) {
    //	return new WP_REST_Response( $force_interval, 200 );
    //}
    if (is_numeric($force_interval))
        eme_send_queued($force_interval);
}

function eme_process_single_mail( $mail ) {
    // check if tracking is required and possible (meaning: only for html mails)
    if ( get_option( 'eme_mail_send_html' ) && get_option( 'eme_mail_tracking' ) ) {
        $add_tracking = true;
    } else {
        $add_tracking = false;
    }

    if ( empty( $mail['id'] ) ) {
        // in the case of send_immediately (call to eme_queue_fastmail), the mail id is not set
        $mail['id'] = 0;
    }
    if ( empty( $mail['receiveremail'] ) ) {
        eme_mark_mail_ignored( $mail['id'], $mail['random_id'] );
        return true;
    }
    $body = $mail['body'];
    if (eme_is_serialized( $mail['attachments'] )) {
        $atts_arr = eme_unserialize( $mail['attachments'] );
    } else {
        $atts_arr = [];
    }
    if ( $add_tracking && ! empty( $mail['random_id'] ) ) {
        $track_url  = eme_tracker_url( $mail['random_id'] );
        $track_html = "<img src='$track_url' alt=''>";
        // if a closing body-tag is present, add it before that
        // otherwise add it to the end
        if ( strstr( $body, '</body>' ) ) {
            $body = str_replace( '</body>', $track_html . '</body>', $body );
        } else {
            $body .= $track_html;
        }
    }
    $custom_headers = [ 'X-EME-mailid:' . $mail['random_id'] ];
    if ( $mail['add_listhdrs'] ) {
        $custom_headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
        $custom_headers[] = sprintf( "List-Unsubscribe: <%s>", eme_unsub_rid_url( $mail['random_id'] ) );
    }
    $mail_res_arr   = eme_send_mail( $mail['subject'], $body, $mail['receiveremail'], $mail['receivername'], $mail['replytoemail'], $mail['replytoname'], $mail['fromemail'], $mail['fromname'], $atts_arr, $custom_headers );
    if ( $mail_res_arr[0] ) {
        eme_mark_mail_sent( $mail['id'], $mail['random_id'] );
        return true;
    } else {
        eme_mark_mail_fail( $mail['id'], $mail['random_id'], $mail_res_arr[1] );
        return false;
    }
}

function eme_send_queued($force_interval=0) {
    // we'll build in a safety precaution to make sure to never surpass the schedule duration
    $start_time   = time();
    if (!$force_interval || !is_numeric($force_interval)) {
        $scheduled    = wp_get_schedule( 'eme_cron_send_queued' );
        if (!$scheduled) { // issue with wp cron? Then take 1 hour, to make sure this still runs ok
            $scheduled = 'hourly';
        }
        $wp_schedules = wp_get_schedules();
        $interval     = $wp_schedules[ $scheduled ]['interval'];
        // let's keep 5 seconds to ourselves (see at the end of this function)
        $interval -= 5;
    } else {
        $interval = $force_interval-5;
    }

    $eme_mail_sleep     = intval( get_option( 'eme_mail_sleep' ) );
    if ( $eme_mail_sleep >= 1000000 ) {
        $eme_mail_sleep_seconds  = intval( $eme_mail_sleep / 1000000 );
        $eme_mail_sleep_useconds = $eme_mail_sleep % 1000000;
    } elseif ( $eme_mail_sleep > 0 ) {
        $eme_mail_sleep_seconds  = 0;
        $eme_mail_sleep_useconds = $eme_mail_sleep;
    } else {
        $eme_mail_sleep_seconds  = 0;
        $eme_mail_sleep_useconds = 0;
    }

    // we use $now as an argument for eme_get_passed_planned_mailingids and eme_get_queued
    // Reason: since eme_check_mailing_receivers can take some time, we want to make sure that
    // both eme_get_passed_planned_mailingids and eme_get_queued are talking about the same 'past'
    $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
    $now              = $eme_date_obj_now->getDateTime();
    // for all planned mailings in the passed: re-evaluate the receivers
    // since we mark the mailing as ongoing afterwards, this re-evalution only happens once
    // and as such doesn't get in the way of eme_get_queued doing it's work
    $passed_planned_mailings = eme_get_passed_planned_mailingids( $now );
    foreach ( $passed_planned_mailings as $mailing_id ) {
        // the next function call can take a while
        eme_check_mailing_receivers( $mailing_id );
        eme_mark_mailing_ongoing( $mailing_id );
    }

    // now handle any queued mails
    $mails = eme_get_queued( $now );
    foreach ( $mails as $mail ) {
        eme_process_single_mail( $mail  );

        // we'll build in a safety precaution to make sure to never surpass the schedule duration
        // this is only usefull if the sleep is configured, since otherwise mails are send immediately, but just to be sure ...
        $cur_time = time();
        if ( $cur_time - $start_time > $interval ) {
            break;
        }

        // now sleep if wanted and the send the next mail
        if ( $eme_mail_sleep_seconds > 0 ) {
            sleep( $eme_mail_sleep_seconds );
        }
        if ( $eme_mail_sleep_useconds > 0 ) {
            usleep( $eme_mail_sleep_useconds );
        }
    }

    // and for the mailings that were marked ongoing, mark them as finished if appropriate
    // thanks to the fact we substraced 5 seconds from the $interval, we always have time to finish this
    $ongoing_mailings = eme_get_mailings(status: 'ongoing');
    foreach ( $ongoing_mailings as $mailing) {
        if ( eme_count_mails_to_send( $mailing['id'] ) == 0 ) {
            eme_mark_mailing_completed( $mailing['id'] );
        }
    }
}

function eme_get_passed_planned_mailingids( $now ) {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = "SELECT id FROM $mailings_table WHERE status='planned' AND planned_on<'$now'";
    return $wpdb->get_col( $sql );
}

function eme_mark_mailing_planned( $mailing_id, $planned_count ) {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $stats          = [
        'planned'          => $planned_count,
        'sent'             => 0,
        'failed'           => 0,
        'cancelled'        => 0,
        'ignored'          => 0,
        'total_read_count' => 0,
    ];
    $sql = $wpdb->prepare( "UPDATE $mailings_table SET status='planned', stats=%s WHERE id=%d", eme_serialize( $stats ), $mailing_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_mailing $mailing_id" );
}

function eme_mark_mailing_ongoing( $mailing_id ) {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = $wpdb->prepare( "UPDATE $mailings_table SET status='ongoing' WHERE id=%d", $mailing_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_mailing $mailing_id" );
}

function eme_mark_mailing_completed( $mailing_id ) {
    global $wpdb;
    $stats          = eme_get_mailing_stats( $mailing_id );
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = $wpdb->prepare( "UPDATE $mailings_table SET status='completed', stats=%s WHERE id=%d", eme_serialize( $stats ), $mailing_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_mailing $mailing_id" );
    if ( $stats['failed'] > 0 ) {
        $mailing        = eme_get_mailing( $mailing_id );
        $failed_subject = __( 'Mailing completed with errors', 'events-made-easy' );
        $failed_body    = sprintf( __( 'Mailing "%s" completed with %d errors, please check the mailing report', 'events-made-easy' ), $mailing['name'], $stats['failed'] );
        eme_send_mail( $failed_subject, $failed_body, $mailing['replytoemail'], $mailing['replytoname'], $mailing['replytoemail'], $mailing['replytoname'] );
    }
}

function eme_archive_mailing( $mailing_id ) {
    global $wpdb;
    $mailing = eme_get_mailing( $mailing_id );
    if ( $mailing['status'] == 'planned' || $mailing['status'] == 'ongoing' ) {
        return;
    }
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    if ( $mailing['status'] == 'completed' ) {
        // for completed mailings, the stats no longer change
        $sql = $wpdb->prepare( "UPDATE $mailings_table SET status='archived' WHERE id=%d", $mailing_id );
    } else {
        $stats = eme_serialize( eme_get_mailing_stats( $mailing_id ) );
        $sql   = $wpdb->prepare( "UPDATE $mailings_table SET status='archived', stats=%s WHERE id=%d", $stats, $mailing_id );
    }
    $wpdb->query( $sql );
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = $wpdb->prepare( "DELETE FROM $queue_table WHERE mailing_id=%d", $mailing_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_mailing $mailing_id" );
}

function eme_mailing_retry_failed( $id ) {
    global $wpdb;
    $mqueue_table            = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $where                   = [];
    $fields                  = [];
    $where['mailing_id']     = intval( $id );
    $where['status']         = 2;
    $fields['status']        = 0;
    if ( $wpdb->update( $mqueue_table, $fields, $where ) === false ) {
        return false;
    } else {
        return true;
    }
}

// for GDPR CRON
function eme_archive_old_mailings() {
    global $wpdb;
    $archive_old_mailings_days = get_option( 'eme_gdpr_archive_old_mailings_days' );
    if ( empty( $archive_old_mailings_days ) ) {
        return;
    } else {
        $archive_old_mailings_days = abs( $archive_old_mailings_days );
    }
    $eme_date_obj   = new ExpressiveDate( 'now', EME_TIMEZONE );
    $old_date       = $eme_date_obj->minusDays( $archive_old_mailings_days )->getDateTime();
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = "SELECT id FROM $mailings_table WHERE creation_date < '$old_date' AND (status='completed' OR status='cancelled')";
    $mailing_ids    = $wpdb->get_col( $sql );
    foreach ( $mailing_ids as $mailing_id ) {
        eme_archive_mailing( $mailing_id );
    }

    // now remove old mails not belonging to a specific mailing
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = "DELETE FROM $queue_table WHERE mailing_id=0 AND creation_date < '$old_date'";
    $wpdb->query( $sql );
}

function eme_cancel_mail( $mail_id ) {
    global $wpdb;
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = $wpdb->prepare( "UPDATE $queue_table SET status=3 WHERE status=0 AND id=%d", $mail_id );
    $wpdb->query( $sql );
}

function eme_cancel_mailing( $mailing_id ) {
    global $wpdb;
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = $wpdb->prepare( "UPDATE $queue_table SET status=3 WHERE status=0 AND mailing_id=%d", $mailing_id );
    $wpdb->query( $sql );
    $stats          = eme_serialize( eme_get_mailing_stats( $mailing_id ) );
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = $wpdb->prepare( "UPDATE $mailings_table SET status='cancelled', stats=%s WHERE id=%d", $stats, $mailing_id );
    $wpdb->query( $sql );
    wp_cache_delete( "eme_mailing $mailing_id" );
}

function eme_delete_mail( $id ) {
    global $wpdb;
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = $wpdb->prepare( "DELETE FROM $queue_table WHERE id=%d", $id );
    $wpdb->query( $sql );
}

function eme_delete_mailing_mails( $id ) {
    global $wpdb;
    $queue_table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql         = $wpdb->prepare( "DELETE FROM $queue_table WHERE mailing_id=%d", $id );
    $wpdb->query( $sql );
}

function eme_delete_mailing( $id ) {
    global $wpdb;
    eme_delete_mailing_mails( $id );
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $sql            = $wpdb->prepare( "DELETE FROM $mailings_table WHERE id=%d", $id );
    $wpdb->query( $sql );
}

function eme_get_mail( $id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id );
    return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_mail_by_rid( $random_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql   = $wpdb->prepare( "SELECT * FROM $table WHERE random_id=%s", $random_id );
    return $wpdb->get_row( $sql, ARRAY_A );
}

function eme_get_mailing( $id ) {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $mailing = wp_cache_get( "eme_mailing $id" );
    if ( $mailing === false ) {
        $sql     = $wpdb->prepare( "SELECT * FROM $mailings_table WHERE id=%d", $id );
        $mailing = $wpdb->get_row( $sql, ARRAY_A );
        wp_cache_set( "eme_mailing $id", $mailing, '', 10 );
    }
    return $mailing;
}

function eme_get_mailings( $status = '', $search_text = '' ) {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;
    $mailing_states = eme_mailing_localizedstates();
    if ( !empty( $status ) && array_key_exists( $status, $mailing_states ) ) {
        $where = " WHERE status='$status' ";
    } else {
        $where = " WHERE status<>'archived' ";
    }
    if ( !empty($search_text)) {
        $search_text = "%" . $wpdb->esc_like( $search_text ) . "%";
        $sql = $wpdb->prepare("SELECT * FROM $mailings_table $where AND ( name LIKE %s OR subject LIKE %s ) ORDER BY planned_on,name", $search_text, $search_text);
    } else {
        $sql = "SELECT * FROM $mailings_table $where ORDER BY planned_on,name";
    }
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_mail_states() {
    $states = [
        0 => 'planned',
        1 => 'sent',
        2 => 'failed',
        3 => 'cancelled',
        4 => 'ignored',
    ];
    return $states;
}

function eme_mail_localizedstates() {
    $states = [
        0 => __( 'Planned', 'events-made-easy' ),
        1 => __( 'Sent', 'events-made-easy' ),
        2 => __( 'Failed', 'events-made-easy' ),
        3 => __( 'Cancelled', 'events-made-easy' ),
        4 => __( 'Ignored', 'events-made-easy' )
    ];
    return $states;
}

function eme_mailing_localizedstates() {
    $states = [
        'archived'  => __( 'Archived', 'events-made-easy' ),
        'planned'   => __( 'Planned', 'events-made-easy' ),
        'ongoing'   => __( 'Ongoing', 'events-made-easy' ),
        'completed' => __( 'Completed', 'events-made-easy' ),
        'cancelled' => __( 'Cancelled', 'events-made-easy' ),
        'initial'   => __( 'Initializing ...', 'events-made-easy' )
    ];
    return $states;
}

function eme_count_mails_to_send( $mailing_id ) {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status=0 AND mailing_id=%d", $mailing_id );
    return $wpdb->get_var( $sql );
}

function eme_get_mailing_stats( $mailing_id = 0 ) {
    global $wpdb;
    $table     = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $sql       = "SELECT COUNT(*) AS count,status FROM $table WHERE mailing_id=$mailing_id GROUP BY mailing_id,status";
    $lines = $wpdb->get_results( $sql, ARRAY_A );
    $res       = [
        'planned'          => 0,
        'sent'             => 0,
        'failed'           => 0,
        'cancelled'        => 0,
        'ignored'          => 0,
        'total_read_count' => 0,
    ];
    $states    = eme_mail_states();
    foreach ( $lines as $line ) {
        $status         = $states[ $line['status'] ];
        $res[ $status ] = $line['count'];
    }
    return $res;
}

function eme_mail_track( $random_id ) {
    global $wpdb;
    $table          = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;

    if ( ! empty( $random_id ) && get_option( 'eme_mail_tracking' ) ) {
        // we'll randomly sleep between 0 and 20 times 0.1 seconds (100000 microseconds)
        // so if 2 requests for the same id arrive at the same time, it will hopefully not do the select at the same time
        // Without the random sleep, 2 request for the same id would cause the read_count in the mailings_table to be updated too much (nothing to worry about, but it wouldn't reflect reality)
        // /usleep(rand(0, 20)*100000); // we do it in microseconds with random, is better than simple sleep(rand(0,2)) which could return the same result for rand too often
        $queued_mail = eme_get_mail_by_rid( $random_id );
        if ( $queued_mail ) {
            // update the queue table when the mail was read for the first time
            $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );
            // ignore if the same track arrives within the firt 2 minutes
            $ignore = 0;
            if ( ! eme_is_empty_datetime( $queued_mail['last_read_on'] ) ) {
                $eme_date_obj_lastread = new ExpressiveDate( $queued_mail['last_read_on'], EME_TIMEZONE );
                if ( $eme_date_obj_lastread->getDifferenceInMinutes( $eme_date_obj_now ) < 2 ) {
                    $ignore = 1;
                }
            }
            if ( ! $ignore ) {
                $now = $eme_date_obj_now->getDateTime();
                // we add the read_count=0 to the SQL statement so we know that 2 identical queries arriving almost at the same time will not cause the same update
                $sql = $wpdb->prepare( "UPDATE $table SET first_read_on=%s, last_read_on=%s, read_count=1 WHERE id = %d AND read_count=0", $now, $now, $queued_mail['id'] );
                $res = $wpdb->query( $sql );
                // update the mailing table with the count of times the mail was read
                // read_count in the mailings_table is the unique read count for this mailing
                if ( $res !== false ) {
                    if ( $res > 0 ) {
                        // res is >0, meaning a row was changed, so it was read for the first time
                        if ( $queued_mail['mailing_id'] > 0 ) {
                            $sql = $wpdb->prepare( "UPDATE $mailings_table SET read_count=read_count+1, total_read_count=total_read_count+1 WHERE id = %d", $queued_mail['mailing_id'] );
                            $wpdb->query( $sql );
                            wp_cache_delete( "eme_mailing ".$queued_mail['mailing_id'] );
                        }
                    } else {
                        // no row changed, meaning the mail was already read once, so do it without read_count=0 check
                        $sql = $wpdb->prepare( "UPDATE $table SET last_read_on=%s, read_count=read_count+1 WHERE id = %d", $now, $queued_mail['id'] );
                        $res = $wpdb->query( $sql );
                        if ( ! empty( $res ) && $queued_mail['mailing_id'] > 0 ) { // not false and >0
                            $sql = $wpdb->prepare( "UPDATE $mailings_table SET total_read_count=total_read_count+1 WHERE id = %d", $queued_mail['mailing_id'] );
                            $wpdb->query( $sql );
                            wp_cache_delete( "eme_mailing ".$queued_mail['mailing_id'] );
                        }
                    }
                }
            }
        }

        // always return a transparant image of 1x1
        $eme_plugin_dir = eme_plugin_dir();
        header( 'Content-Type: image/gif' );
        //$image = file_get_contents($eme_plugin_dir.'images/1x1.gif');
        //echo $image;
        readfile( $eme_plugin_dir . 'images/1x1.gif' );
    }
}

function eme_check_mailing_receivers( $mailing_id ) {
    if ( ! $mailing_id ) {
        return;
    }
    $mailing = eme_get_mailing( $mailing_id );
    if ( ! $mailing || empty( $mailing['conditions'] ) ) {
        return;
    }
    $conditions = eme_unserialize( $mailing['conditions'] );
    // we delete all planned mails for the mailing and enter the mails anew, this allows us to have all mails with the latest content and receivers
    // for newer versions of EME this delete no longer does anything since the individual mails are only inserted here when calling eme_update_mailing_receivers
    eme_delete_mailing_mails( $mailing_id );
    eme_update_mailing_receivers( $mailing['subject'], $mailing['body'], $mailing['fromemail'], $mailing['fromname'], $mailing['replytoemail'], $mailing['replytoname'], $mailing['mail_text_html'], $conditions, $mailing_id );
}

function eme_count_planned_mailing_receivers( $conditions, $mailing_id = 0) {
    return eme_update_mailing_receivers( conditions: $conditions, mailing_id: $mailing_id, count_only: 1);
}

function eme_update_mailing_receivers( $mail_subject = '', $mail_message = '', $from_email = '', $from_name = '', $replyto_email = '', $replyto_name = '', $mail_text_html = 'html', $conditions = [], $mailing_id = 0, $count_only = 0 ) {
    $res = [
        'mail_problems' => 0,
        'total'		=> 0,
        'not_sent'      => ''
    ];
    if (!$count_only) {
        $mail_subject = eme_replace_generic_placeholders( $mail_subject );
        $mail_message = eme_replace_generic_placeholders( $mail_message );
    }
    $not_sent             = [];
    $emails_handled       = [];
    if ( isset( $conditions['ignore_massmail_setting'] ) && $conditions['ignore_massmail_setting'] == 1 ) {
        $ignore_massmail_setting = 1;
    } else {
        $ignore_massmail_setting = 0;
    }

    $attachment_ids      = '';
    $person_ids          = [];
    $member_ids          = [];
    $cond_person_ids_arr = [];
    $cond_member_ids_arr = [];
    $atts_arr          = [];

    if ( $conditions['action'] == 'genericmail' ) {
        if ( isset( $conditions['eme_generic_attach_ids'] ) && eme_is_list_of_int( $conditions['eme_generic_attach_ids'] ) ) {
            $attachment_ids = $conditions['eme_generic_attach_ids'];
            if ( ! empty( $attachment_ids ) ) {
                $atts_arr = explode( ',', $attachment_ids );
            }
        }

        if ( isset( $conditions['eme_send_all_people'] ) ) {
            // although we check later on the massmail preference per person too, we optimize the sql load a bit
            if ( $ignore_massmail_setting ) {
                $person_ids = eme_get_allmail_person_ids();
            } else {
                $person_ids = eme_get_massmail_person_ids();
            }
        } else {
            if ( ! empty( $conditions['eme_genericmail_send_persons'] ) ) {
                $cond_person_ids_arr = explode( ',', $conditions['eme_genericmail_send_persons'] );
                $person_ids          = $cond_person_ids_arr;
            }
            if ( ! empty( $conditions['eme_send_members'] ) ) {
                $cond_member_ids_arr = explode( ',', $conditions['eme_send_members'] );
                $member_ids          = $cond_member_ids_arr;
            }
            if ( ! empty( $conditions['eme_genericmail_send_peoplegroups'] ) ) {
                $person_ids = array_unique( array_merge( $person_ids, eme_get_groups_person_ids( $conditions['eme_genericmail_send_peoplegroups'] ) ) );
            }
            if ( ! empty( $conditions['eme_genericmail_send_membergroups'] ) ) {
                $member_ids = array_unique( array_merge( $member_ids, eme_get_groups_member_ids( $conditions['eme_genericmail_send_membergroups'] ) ) );
            }
            if ( ! empty( $conditions['eme_send_memberships'] ) ) {
                $member_ids = array_unique( array_merge( $member_ids, eme_get_memberships_member_ids( $conditions['eme_send_memberships'] ) ) );
            }
        }
        foreach ( $member_ids as $member_id ) {
            $member = eme_get_member( $member_id );
            $person = eme_get_person( $member['person_id'] );
            // if corresponding person has no massmail preference, then skip him unless the name was speficially defined as standalone member to mail to
            if ( ! $ignore_massmail_setting && ! $person['massmail'] && ! in_array( $member_id, $cond_member_ids_arr ) ) {
                continue;
            }
            $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );
            // we will NOT ignore double emails for member-related mails
            // we could postpone the placeholder replacement until the moment of actual sending (for large number of mails)
            // but that complicates the queue-code and is in fact ugly (I did it, but removed it on 2017-12-04)
            // Once I hit execution timeouts I'll rethink it again
            if ($count_only) {
                $mail_res    = eme_is_email($person['email']);
            } else {
                $membership  = eme_get_membership( $member['membership_id'] );
                $tmp_subject = eme_replace_member_placeholders( $mail_subject, $membership, $member, 'text' );
                $tmp_message = eme_replace_member_placeholders( $mail_message, $membership, $member, $mail_text_html );
                $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $person['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, 0, $member_id, $atts_arr );
            }
            if ( ! $mail_res ) {
                $res['mail_problems'] = 1;
                $not_sent[]           = $person_name;
            } else {
                $emails_handled[] = $person['email'];
            }
        }
        foreach ( $person_ids as $person_id ) {
            $person = eme_get_person( $person_id );
            // if person has no massmail preference, then skip him unless the name was speficially defined as standalone person to mail to
            if ( ! $ignore_massmail_setting && ! $person['massmail'] && ! in_array( $person_id, $cond_person_ids_arr ) ) {
                continue;
            }
            $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );
            // we will ignore double emails
            if ( ! in_array( $person['email'], $emails_handled ) ) {
                if ($count_only) {
                    $mail_res    = eme_is_email($person['email']);
                } else {
                    $tmp_subject = eme_replace_people_placeholders( $mail_subject, $person, 'text' );
                    $tmp_message = eme_replace_people_placeholders( $mail_message, $person, $mail_text_html );
                    $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $person['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, $person_id, 0, $atts_arr );
                }
                if ( ! $mail_res ) {
                    $res['mail_problems'] = 1;
                    $not_sent[]           = $person_name;
                } else {
                    $emails_handled[] = $person['email'];
                }
            }
        }
    } elseif ( $conditions['action'] == 'eventmail' ) {
        if ( ! isset( $conditions['rsvp_status'] ) ) {
            $conditions['rsvp_status'] = 0;
        }
        if ( ! empty( $conditions['pending_approved'] ) ) {
            if ( $conditions['pending_approved'] == 1 ) {
                $conditions['rsvp_status'] == EME_RSVP_STATUS_PENDING;
            }
            if ( $conditions['pending_approved'] == 2 ) {
                $conditions['rsvp_status'] == EME_RSVP_STATUS_APPROVED;
            }
        }
        if ( ! isset( $conditions['only_unpaid'] ) ) {
            $conditions['only_unpaid'] = 0;
        }
        if ( ! isset( $conditions['exclude_registered'] ) ) {
            $conditions['exclude_registered'] = 0;
        }
        if ( isset( $conditions['eme_eventmail_attach_ids'] ) && eme_is_list_of_int( $conditions['eme_eventmail_attach_ids'] ) ) {
            $attachment_ids = $conditions['eme_eventmail_attach_ids'];
            if ( ! empty( $attachment_ids ) ) {
                $atts_arr = explode( ',', $attachment_ids );
            }
        }

        // conditions event_id can be multiple ids
        $event_ids = explode( ',', $conditions['event_id'] );
        foreach ($event_ids as $event_id) {
            $event = eme_get_event( $event_id );
            if ( empty( $event ) ) {
                $res['mail_problems'] = 1;
            } elseif ( $conditions['eme_mail_type'] == 'attendees' ) {
                $attendee_ids = eme_get_attendee_ids( $event_id, $conditions['rsvp_status'], $conditions['only_unpaid'] );
                foreach ( $attendee_ids as $attendee_id ) {
                    $attendee    = eme_get_person( $attendee_id );
                    if ($count_only) {
                        $mail_res    = eme_is_email($attendee['email']);
                    } else {
                        $tmp_subject = eme_replace_attendees_placeholders( $mail_subject, $event, $attendee, 'text' );
                        $tmp_message = eme_replace_attendees_placeholders( $mail_message, $event, $attendee, $mail_text_html );
                        $person_name = eme_format_full_name( $attendee['firstname'], $attendee['lastname'] );
                        $person_id   = $attendee['person_id'];
                        $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $attendee['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, $person_id, 0, $atts_arr );
                    }
                    if ( ! $mail_res ) {
                        $res['mail_problems'] = 1;
                        $not_sent[]           = $person_name;
                    } else {
                        $emails_handled[] = $attendee['email'];
                    }
                }
            } elseif ( $conditions['eme_mail_type'] == 'bookings' ) {
                $bookings = eme_get_bookings_for( $event_id, $conditions['rsvp_status'], $conditions['only_unpaid'] );
                foreach ( $bookings as $booking ) {
                    // we use the language done in the booking for the mails, not the attendee lang in this case
                    $attendee = eme_get_person( $booking['person_id'] );
                    if ( $attendee && is_array( $attendee ) ) {
                        if ($count_only) {
                            $mail_res    = eme_is_email($attendee['email']);
                        } else {
                            $tmp_subject = eme_replace_booking_placeholders( $mail_subject, $event, $booking, 0, 'text' );
                            $tmp_message = eme_replace_booking_placeholders( $mail_message, $event, $booking, 0, $mail_text_html );
                            $person_name = eme_format_full_name( $attendee['firstname'], $attendee['lastname'] );
                            $person_id   = $attendee['person_id'];
                            $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $attendee['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, $person_id, 0, $atts_arr );
                        }
                        if ( ! $mail_res ) {
                            $res['mail_problems'] = 1;
                            $not_sent[]           = $person_name;
                        } else {
                            $emails_handled[] = $attendee['email'];
                        }
                    }
                }
            } elseif ( $conditions['eme_mail_type'] == 'all_people' || $conditions['eme_mail_type'] == 'people_and_groups' || $conditions['eme_mail_type'] == 'all_people_not_registered' ) {
                if ( $conditions['eme_mail_type'] == 'all_people' || $conditions['eme_mail_type'] == 'all_people_not_registered' ) {
                    // although we check later on the massmail preference per person too, we optimize the sql load a bit
                    if ( $ignore_massmail_setting ) {
                        $person_ids = eme_get_allmail_person_ids();
                    } else {
                        $person_ids = eme_get_massmail_person_ids();
                    }
                } elseif ( $conditions['eme_mail_type'] == 'people_and_groups' ) {
                    if ( ! empty( $conditions['eme_eventmail_send_persons'] ) ) {
                        $person_ids = explode( ',', $conditions['eme_eventmail_send_persons'] );
                    }
                    if ( ! empty( $conditions['eme_eventmail_send_groups'] ) ) {
                        $person_ids = array_unique( array_merge( $person_ids, eme_get_groups_person_ids( $conditions['eme_eventmail_send_groups'] ) ) );
                    }
                    if ( ! empty( $conditions['eme_eventmail_send_members'] ) ) {
                        $cond_member_ids_arr = explode( ',', $conditions['eme_eventmail_send_members'] );
                        $member_ids          = $cond_member_ids_arr;
                    }
                    if ( ! empty( $conditions['eme_eventmail_send_membergroups'] ) ) {
                        $member_ids = array_unique( array_merge( $member_ids, eme_get_groups_member_ids( $conditions['eme_eventmail_send_membergroups'] ) ) );
                    }
                    if ( ! empty( $conditions['eme_eventmail_send_memberships'] ) ) {
                        $member_ids = array_unique( array_merge( $member_ids, eme_get_memberships_member_ids( $conditions['eme_eventmail_send_memberships'] ) ) );
                    }
                }
                if ( ! empty( $conditions['exclude_registered'] ) || $conditions['eme_mail_type'] == 'all_people_not_registered' ) {
                    $registered_ids = eme_get_attendee_ids( $event_id );
                } else {
                    $registered_ids = [];
                }
                foreach ( $member_ids as $member_id ) {
                    $member = eme_get_member( $member_id );
                    if ( in_array( $member['person_id'], $registered_ids ) ) {
                        continue;
                    }
                    $person = eme_get_person( $member['person_id'] );
                    // if corresponding person has no massmail preference, then skip him unless the name was speficially defined as standalone member to mail to
                    if ( ! $ignore_massmail_setting && ! $person['massmail'] && ! in_array( $member_id, $cond_member_ids_arr ) ) {
                        continue;
                    }
                    $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );

                    // we will NOT ignore double emails for member-related mails
                    // we could postpone the placeholder replacement until the moment of actual sending (for large number of mails)
                    // but that complicates the queue-code and is in fact ugly (I did it, but removed it on 2017-12-04)
                    // Once I hit execution timeouts I'll rethink it again
                    if ($count_only) {
                        $mail_res    = eme_is_email($person['email']);
                    } else {
                        $tmp_subject = eme_replace_event_placeholders( $mail_subject, $event, 'text', $person['lang'], 0 );
                        $tmp_message = eme_replace_event_placeholders( $mail_message, $event, $mail_text_html, $person['lang'], 0 );
                        $tmp_message = eme_replace_email_event_placeholders( $tmp_message, $person['email'], $person['lastname'], $person['firstname'], $event );
                        $membership  = eme_get_membership( $member['membership_id'] );
                        $tmp_subject = eme_replace_member_placeholders( $tmp_subject, $membership, $member, 'text' );
                        $tmp_message = eme_replace_member_placeholders( $tmp_message, $membership, $member, $mail_text_html );
                        $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $person['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, 0, $member_id, $atts_arr );
                    }

                    if ( ! $mail_res ) {
                        $res['mail_problems'] = 1;
                        $not_sent[]           = $person_name;
                    } else {
                        $emails_handled[] = $person['email'];
                    }
                }
                foreach ( $person_ids as $person_id ) {
                    if ( in_array( $person_id, $registered_ids ) ) {
                        continue;
                    }
                    $person = eme_get_person( $person_id );
                    // we will ignore double emails
                    if ( ! in_array( $person['email'], $emails_handled ) ) {
                        $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );
                        if ( ! $ignore_massmail_setting && ! $person['massmail'] ) {
                            continue;
                        }
                        if ($count_only) {
                            $mail_res    = eme_is_email($person['email']);
                        } else {
                            $tmp_subject = eme_replace_event_placeholders( $mail_subject, $event, 'text', $person['lang'], 0 );
                            $tmp_message = eme_replace_event_placeholders( $mail_message, $event, $mail_text_html, $person['lang'], 0 );
                            $tmp_message = eme_replace_email_event_placeholders( $tmp_message, $person['email'], $person['lastname'], $person['firstname'], $event );
                            $tmp_subject = eme_replace_people_placeholders( $tmp_subject, $person, 'text' );
                            $tmp_message = eme_replace_people_placeholders( $tmp_message, $person, $mail_text_html );
                            $person_id   = $person['person_id'];
                            $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $person['email'], $person_name, $replyto_email, $replyto_name, $mailing_id, $person_id, 0, $atts_arr );
                        }
                        if ( ! $mail_res ) {
                            $res['mail_problems'] = 1;
                            $not_sent[]           = $person_name;
                        } else {
                            $emails_handled[] = $person['email'];
                        }
                    }
                }
            } elseif ( $conditions['eme_mail_type'] == 'all_wp' || $conditions['eme_mail_type'] == 'all_wp_not_registered' ) {
                $wp_users = get_users();
                if ( $conditions['eme_mail_type'] == 'all_wp_not_registered' || $conditions['exclude_registered'] ) {
                    $attendee_wp_ids = eme_get_wp_ids_for( $event_id );
                } else {
                    $attendee_wp_ids = [];
                }
                $lang = eme_detect_lang();
                foreach ( $wp_users as $wp_user ) {
                    if ( in_array( $wp_user->user_email, $emails_handled ) ) {
                        continue;
                    }
                    if ( in_array( $wp_user->ID, $attendee_wp_ids ) ) {
                        continue;
                    }
                    if ($count_only) {
                        // wp email is always considered valid, but let's keep the code identical
                        $mail_res    = eme_is_email($wp_user->user_email);
                    } else {
                        $tmp_subject = eme_replace_event_placeholders( $mail_subject, $event, 'text', $lang, 0 );
                        $tmp_message = eme_replace_event_placeholders( $mail_message, $event, $mail_text_html, $lang, 0 );
                        $tmp_message = eme_replace_email_event_placeholders( $tmp_message, $wp_user->user_firstname, $wp_user->display_name, $wp_user->display_name, $event );
                        $mail_res    = eme_queue_mail( $tmp_subject, $tmp_message, $from_email, $from_name, $wp_user->user_email, $wp_user->display_name, $replyto_email, $replyto_name, $mailing_id, 0, 0, $atts_arr );
                    }
                    if ( ! $mail_res ) {
                        $res['mail_problems'] = 1;
                        $not_sent[]           = $wp_user->display_name;
                    } else {
                        $emails_handled[] = $wp_user->user_email;
                    }
                }
            }
        }
    }
    $res['not_sent'] = join( ', ', $not_sent );
    $res['total'] = count($emails_handled);
    return $res;
}

add_action( 'wp_ajax_eme_mailingreport_list', 'eme_mailingreport_list' );
function eme_mailingreport_list() {
    global $wpdb;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    $jTableResult = [];
    if ( ! current_user_can( get_option( 'eme_cap_manage_mails' ) ) ) {
        $jTableResult            = [];
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    if ( ! isset( $_POST['mailing_id'] ) ) {
        return;
    }
    $mailing_id  = intval( $_POST['mailing_id'] );
    $search_name = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';
    $where       = '';
    $where_arr   = [];
    $where_arr[] = '(mailing_id=' . $mailing_id . ')';
    if ( ! empty( $search_name ) ) {
        $where_arr[] = "(receivername like '%$search_name%' OR receiveremail like '%$search_name%')";
    }

    if ( ! empty( $where_arr ) ) {
        $where = ' WHERE ' . implode( ' AND ', $where_arr );
    }

    $sql          = "SELECT COUNT(*) FROM $table $where";
    $recordCount  = $wpdb->get_var( $sql );
    $limit        = eme_get_datatables_limit();
    $orderby      = eme_get_datatables_orderby();
    $sql          = "SELECT * FROM $table $where $orderby $limit";
    $rows         = $wpdb->get_results( $sql, ARRAY_A );
    $records      = [];
    $states       = eme_mail_localizedstates();
    foreach ( $rows as $item ) {
        $record                  = [];
        $id                      = $item['id'];
        $record['receiveremail'] = $item['receiveremail'];
        $record['receivername']  = $item['receivername'];
        $record['status']        = $states[ $item['status'] ];
        if ($item['status'] == 0) // for planned mails, the read count is empty (and not 0)
            $record['read_count']    = '';
        else
            $record['read_count']    = $item['read_count'];
        $record['error_msg']     = eme_esc_html( $item['error_msg'] );
        if ( $item['status'] > 0 ) {
            $localized_datetime      = eme_localized_datetime( $item['sent_datetime'] );
            $record['sent_datetime'] = $localized_datetime;
            if ( ! eme_is_empty_datetime( $item['first_read_on'] ) ) {
                $record['first_read_on'] = eme_localized_datetime( $item['first_read_on'] );
                // to account for older setups that didn't have the last_read_on column
                if ( eme_is_empty_datetime( $item['last_read_on'] ) ) {
                    $item['last_read_on'] = $item['first_read_on'];
                }
                $record['last_read_on'] = eme_localized_datetime( $item['last_read_on'] );
                // to account for older mailings
                if ( $record['read_count'] == 0 ) {
                    $record['read_count'] = 1;
                }
            } else {
                $record['first_read_on'] = '';
                $record['last_read_on']  = '';
            }
            $record['action'] = " <a title='".__( 'Reuse this mail', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=reuse_mail&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Reuse', 'events-made-easy' ) . '</a>';
        } else {
            $record['sent_datetime'] = '';
            $record['first_read_on'] = '';
            $record['last_read_on']  = '';
            $record['action']        = '';
        }
        $records[] = $record;
    }
    $jTableResult['Result']           = 'OK';
    $jTableResult['Records']          = $records;
    $jTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $jTableResult );
    wp_die();
}

add_action( 'wp_ajax_eme_previeweventmail', 'eme_send_mails_ajax_action_previeweventmail' );
add_action( 'wp_ajax_eme_previewmail', 'eme_send_mails_ajax_action_previewmail' );
add_action( 'wp_ajax_eme_eventmail', 'eme_send_mails_ajax_action_eventmail' );
add_action( 'wp_ajax_eme_genericmail', 'eme_send_mails_ajax_action_genericmail' );
add_action( 'wp_ajax_eme_testmail', 'eme_send_mails_ajax_action_testmail' );

add_action( 'wp_ajax_eme_mails_list', 'eme_ajax_mails_list' );
add_action( 'wp_ajax_eme_mailings_list', 'eme_ajax_mailings_list' );
add_action( 'wp_ajax_eme_archivedmailings_list', 'eme_ajax_archivedmailings_list' );
add_action( 'wp_ajax_eme_manage_mails', 'eme_ajax_manage_mails' );
add_action( 'wp_ajax_eme_manage_mailings', 'eme_ajax_manage_mailings' );
add_action( 'wp_ajax_eme_manage_archivedmailings', 'eme_ajax_manage_archivedmailings' );

function eme_ajax_mailings_list() {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;

    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $jTableResult = [];
    if ( ! current_user_can( get_option( 'eme_cap_manage_mails' ) ) ){
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }

    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();
    $where = " WHERE status<>'archived' ";
    if ( !isset($_POST['search_text'] ) || eme_is_empty_string( $_POST['search_text'] ) ) {
        $count_sql = "SELECT COUNT(*) FROM $mailings_table $where";
        $sql = "SELECT * FROM $mailings_table $where $orderby $limit";
    } else {
        $search_text = "%" . $wpdb->esc_like( eme_sanitize_request( $_POST['search_text'] ) ) . "%";
        $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM $mailings_table $where AND ( name LIKE %s OR subject LIKE %s )", $search_text, $search_text);
        $sql = $wpdb->prepare("SELECT * FROM $mailings_table $where AND ( name LIKE %s OR subject LIKE %s ) $orderby $limit", $search_text, $search_text);
    }
    $recordCount = $wpdb->get_var( $count_sql );
    $mailings = $wpdb->get_results( $sql, ARRAY_A );
    $mailing_states = eme_mailing_localizedstates();
    $areyousure = esc_html__( 'Are you sure you want to do this?', 'events-made-easy' );
    $records = [];
    foreach ( $mailings as $mailing ) {
        $id = $mailing['id'];
        if ( $mailing['status'] == '') { // old empty status = completed
            $mailing['status'] = 'completed';
        }
        $status = 'UNKNOWN';
        if ( array_key_exists( $mailing['status'], $mailing_states ) ) {
            $status = $mailing_states[ $mailing['status'] ];
        }
        if ( $mailing['status'] == 'cancelled' ) {
            $stats  = eme_unserialize( $mailing['stats'] );
            $extra  = sprintf( __( '%d mails sent, %d mails failed, %d mails cancelled', 'events-made-easy' ), $stats['sent'], $stats['failed'], $stats['cancelled'] );
            $action = "<a onclick='return areyousure(\"$areyousure\");' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=delete_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Delete', 'events-made-easy' ) . "</a><br><a onclick='return areyousure(\"$areyousure\");' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=archive_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Archive', 'events-made-easy' ) . '</a>';
        } elseif ( $mailing['status'] == 'initial' ) {
            $stats  = '';
            $extra  = '';
            $action = "<a onclick='return areyousure(\"$areyousure\");' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=cancel_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Cancel', 'events-made-easy' ) . '</a>';
        } elseif ( $mailing['status'] == 'planned' ) {
            // older mailings inserted the mails directly and not update the stats
            // newer mailings only set the planned stats and update the receivers the moment the mailing starts
            if (empty($mailing['stats'])) {
                $stats  = eme_get_mailing_stats( $id );
            } else {
                $stats  = eme_unserialize( $mailing['stats'] );
            }
            $extra  = sprintf( __( '%d mails left', 'events-made-easy' ), $stats['planned'] );
            $action = "<a onclick='return areyousure(\"$areyousure\");' title='".__( 'Delete this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=delete_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Delete', 'events-made-easy' ) . "</a><br><a onclick='return areyousure(\"$areyousure\");' title='".__( 'Cancel the sending of this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=cancel_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Cancel', 'events-made-easy' ) . '</a>';
        } elseif ( $mailing['status'] == 'ongoing' ) {
            $stats  = eme_get_mailing_stats( $id );
            $extra  = sprintf( __( '%d mails sent, %d mails failed, %d mails left', 'events-made-easy' ), $stats['sent'], $stats['failed'], $stats['planned'] );
            $action = "<a onclick='return areyousure(\"$areyousure\");' title='".__( 'Cancel the sending of this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=cancel_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Cancel', 'events-made-easy' ) . '</a>';
        } elseif ( $mailing['status'] == 'completed' || $mailing['status'] == '' ) {
            $stats  = eme_unserialize( $mailing['stats'] );
            $extra  = sprintf( __( '%d mails sent, %d mails failed', 'events-made-easy' ), $stats['sent'], $stats['failed'] );
            $action = "<a onclick='return areyousure(\"$areyousure\");' title='".__( 'Delete this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=delete_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Delete', 'events-made-easy' ) . "</a><br><a onclick='return areyousure(\"$areyousure\");' title='".__( 'Archive this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=archive_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Archive', 'events-made-easy' ) . '</a>';
        }
        if ( ! empty( $mailing['subject'] ) && ! empty( $mailing['body'] ) ) {
            $action .= "<br><a title='".__( 'Reuse this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=reuse_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Reuse', 'events-made-easy' ) . '</a>';
        }
        if ( is_array( $stats ) && !empty( $stats['failed'] ) ) {
            $action .= "<br><a onclick='return areyousure(\"$areyousure\");' title='".__( 'Retry failed messages from this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=retry_failed_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Retry failed mails', 'events-made-easy' ) . '</a>';
        }

        $record = [];
        $record['id'] = $id;
        $record['name'] = eme_esc_html( $mailing['name'] );
        $record['subject'] = eme_esc_html( $mailing['subject'] );
        $record['planned_on'] = eme_localized_datetime( $mailing['planned_on'] );
        $record['creation_date'] = eme_localized_datetime( $mailing['creation_date'] );
        $record['status'] = eme_esc_html( $status );
        $record['read_count'] = intval( $mailing['read_count'] );
        $record['total_read_count'] = intval( $mailing['total_read_count'] );
        if ( $mailing['status'] == 'planned' ) {
            $planned_estimation_title = eme_esc_html( sprintf(__('The number of emails to be sent was estimated the moment the mailing was created (%s). This will be re-evaluated at send time.','events-made-easy'), $record['creation_date']) ) ;
            $record['extra_info'] = eme_esc_html( $extra ) . "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='$planned_estimation_title'>";
        } else {
            $record['extra_info'] = eme_esc_html( $extra );
        }
        if ( $mailing['status'] == 'planned' ) {
            $record['report'] = '';
        } else {
            $record['report'] = "<a title='".__( 'Show mailing report', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=report_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Report', 'events-made-easy' ) . '</a>';
        }
        $record['action'] = $action;
        $records[] = $record;
    }

    $jTableResult['Result']           = 'OK';
    $jTableResult['Records']          = $records;
    $jTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_manage_mailings() {
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $jTableResult = [];
    if ( ! current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
            case 'archiveMailings':
                $mailing_ids = explode( ',', eme_sanitize_request($_POST['mailing_ids']) );
                if (eme_is_integer_array($mailing_ids)) {
                    foreach ( $mailing_ids as $mailing_id ) {
                        eme_archive_mailing( $mailing_id );
                    }
                }
                $jTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Mailings archived','events-made-easy')."</div>";
                $jTableResult['Result'] = 'OK';
                break;
            case 'deleteMailings':
                $mailing_ids = explode( ',', eme_sanitize_request($_POST['mailing_ids']) );
                if (eme_is_integer_array($mailing_ids)) {
                    foreach ( $mailing_ids as $mailing_id ) {
                        eme_delete_mailing( $mailing_id );
                    }
                }
                $jTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Mailings deleted','events-made-easy')."</div>";
                $jTableResult['Result'] = 'OK';
                break;
        }
    }
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_archivedmailings_list() {
    global $wpdb;
    $mailings_table = EME_DB_PREFIX . EME_MAILINGS_TBNAME;

    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $jTableResult = [];
    if ( ! current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }

    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();
    $where = " WHERE status='archived' ";
    if ( !isset($_POST['search_text'] ) || eme_is_empty_string( $_POST['search_text'] ) ) {
        $count_sql = "SELECT COUNT(*) FROM $mailings_table $where";
        $sql = "SELECT * FROM $mailings_table $where $orderby $limit";
    } else {
        $search_text = "%" . $wpdb->esc_like( eme_sanitize_request( $_POST['search_text'] ) ) . "%";
        $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM $mailings_table $where AND ( name LIKE %s OR subject LIKE %s )", $search_text, $search_text);
        $sql = $wpdb->prepare("SELECT * FROM $mailings_table $where AND ( name LIKE %s OR subject LIKE %s ) $orderby $limit", $search_text, $search_text);
    }
    $recordCount = $wpdb->get_var( $count_sql );
    $mailings = $wpdb->get_results( $sql, ARRAY_A );
    $mailing_states = eme_mailing_localizedstates();
    $areyousure = esc_html__( 'Are you sure you want to do this?', 'events-made-easy' );
    $records = [];
    foreach ( $mailings as $mailing ) {
        $id = $mailing['id'];

        $stats  = eme_unserialize( $mailing['stats'] );
        $extra  = sprintf( __( '%d mails sent, %d mails failed, %d mails cancelled', 'events-made-easy' ), $stats['sent'], $stats['failed'], $stats['cancelled'] );
        $action = "<a onclick='return areyousure(\"$areyousure\");' title='".__( 'Delete this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=delete_archivedmailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Delete', 'events-made-easy' ) . '</a>';
        if ( ! empty( $mailing['subject'] ) && ! empty( $mailing['body'] ) ) {
            $action .= "<br><a title='".__( 'Reuse this mailing', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=reuse_mailing&amp;id=' . $id ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Reuse', 'events-made-easy' ) . '</a>';
        }

        $record = [];
        $record['id'] = $id;
        $record['name'] = eme_esc_html( $mailing['name'] );
        $record['subject'] = eme_esc_html( $mailing['subject'] );
        $record['planned_on'] = eme_localized_datetime( $mailing['planned_on'] );
        $record['read_count'] = intval( $mailing['read_count'] );
        $record['total_read_count'] = intval( $mailing['total_read_count'] );
        $record['extra_info'] = eme_esc_html( $extra );
        $record['action'] = $action;
        $records[] = $record;
    }

    $jTableResult['Result']           = 'OK';
    $jTableResult['Records']          = $records;
    $jTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_manage_archivedmailings() {
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $jTableResult = [];
    if ( ! current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
            case 'deleteArchivedMailings':
                $mailing_ids = explode( ',', eme_sanitize_request($_POST['mailing_ids']) );
                if (eme_is_integer_array($mailing_ids)) {
                    foreach ( $mailing_ids as $mailing_id ) {
                        eme_delete_mailing( $mailing_id );
                    }
                }
                $jTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Mailings deleted','events-made-easy')."</div>";
                $jTableResult['Result'] = 'OK';
                break;
        }
    }
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_mails_list() {
    global $wpdb;

    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );

    $jTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }

    $table = EME_DB_PREFIX . EME_MQUEUE_TBNAME;
    $where = '';

    $limit    = eme_get_datatables_limit();
    $orderby  = eme_get_datatables_orderby();

    if ( !isset($_POST['search_text'] ) || eme_is_empty_string( $_POST['search_text'] ) ) {
        if ( ! empty( $_POST['search_failed'] ) ) {
            $where = 'WHERE status=2';
        }
        $count_sql = "SELECT COUNT(*) FROM $table";
        if (empty($orderby)) {
            // subselect to first get the last 100, and then the outer select to reverse sort them (newer last)
            $sql  = "SELECT * FROM (SELECT * FROM $table $where ORDER BY id DESC $limit) as q ORDER BY q.id";
        } else {
            $sql  = "SELECT * FROM $table $where $orderby $limit";
        }
    } else {
        $search_text = "%" . $wpdb->esc_like( eme_sanitize_request( $_POST['search_text'] ) ) . "%";
        if ( ! empty( $_POST['search_failed'] ) ) {
            $where = 'AND status=2';
        }
        $count_sql  = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE (receivername LIKE %s OR receiveremail LIKE %s OR subject LIKE %s) $where", $search_text, $search_text, $search_text );
        if (empty($orderby)) {
            // "order by status=0, id" will show the planned mails (status=0) last
            $orderby = "ORDER BY status=0,id";
        }
        $sql  = $wpdb->prepare( "SELECT * FROM $table WHERE (receivername LIKE %s OR receiveremail LIKE %s OR subject LIKE %s) $where $orderby $limit", $search_text, $search_text, $search_text );
    }
    $recordCount = $wpdb->get_var( $count_sql );
    $rows = $wpdb->get_results( $sql, ARRAY_A );

    $states = eme_mail_localizedstates();
    $records = [];
    foreach ( $rows as $row ) {
        $record  = [];
        $record['id'] = $row['id'];
        $record['person_id'] = $row['person_id'];
        $record['fromname'] = eme_esc_html( $row['fromname'] );
        $record['fromemail'] = eme_esc_html( $row['fromemail'] );
        $record['receivername'] = eme_esc_html( $row['receivername'] );
        $record['receiveremail'] = eme_esc_html( $row['receiveremail'] );
        $record['subject'] = eme_esc_html( $row['subject'] );
        $record['status'] = $states[ $row['status'] ];
        $record['creation_date'] = eme_localized_datetime( $row['creation_date'] );
        // if status >0, then the mail is already treated
        if ( $row['status'] > 0 ) {
            if ( ! eme_is_empty_datetime( $row['sent_datetime'] ) ) {
                $record['sent_datetime'] = eme_localized_datetime( $row['sent_datetime'] );
            }
            if ( ! eme_is_empty_datetime( $row['first_read_on'] ) ) {
                $record['first_read_on'] = eme_localized_datetime( $row['first_read_on'] );
                // to account for older setups that didn't have the last_read_on column
                if ( eme_is_empty_datetime( $row['last_read_on'] ) ) {
                    $row['last_read_on'] = $row['first_read_on'];
                }
                $record['last_read_on'] = eme_localized_datetime( $row['last_read_on'] );
                $record['read_count'] = $row['read_count'];
            }
            $record['error_msg'] = eme_esc_html( $row['error_msg'] );
            $record['action'] = "<a title='".__( 'Reuse this mail', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=reuse_mail&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Reuse', 'events-made-easy' ) . '</a>';
        } else {
            //$record['action'] = "";
            //if ( $row['mailing_id'] > 0 ) {
            //    $record['action'] = __('This mail is part of a mailing','events-made-easy') . "<br>";
            //}
            $record['action'] = "<a title='".__( 'Cancel the sending of this mail', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=cancel_mail&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Cancel', 'events-made-easy' ) . "</a><br><a title='".__( 'Reuse this mail', 'events-made-easy' )."' href='" . wp_nonce_url( admin_url( 'admin.php?page=eme-emails&amp;eme_admin_action=reuse_mail&amp;id=' . $row['id'] ), 'eme_admin', 'eme_admin_nonce' ) . "'>" . __( 'Reuse', 'events-made-easy' ) . '</a>';
        }
        $records[] = $record;
    }
    $jTableResult['Result']           = 'OK';
    $jTableResult['Records']          = $records;
    $jTableResult['TotalRecordCount'] = $recordCount;
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_ajax_manage_mails() {
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $jTableResult = [];
    if ( !current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $jTableResult['Result']  = 'Error';
        $jTableResult['htmlmessage'] = "<div class='error eme-message-admin'>".__( 'Access denied!', 'events-made-easy' )."</div>";
        print wp_json_encode( $jTableResult );
        wp_die();
    }
    if ( isset( $_POST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_POST['do_action'] );
        switch ( $do_action ) {
            case 'deleteMails':
                $mail_ids = explode( ',', eme_sanitize_request($_POST['mail_ids']) );
                if (eme_is_integer_array($mail_ids)) {
                    foreach ( $mail_ids as $mail_id ) {
                        eme_delete_mail( $mail_id );
                    }
                }
                $jTableResult['htmlmessage'] = "<div class='updated eme-message-admin'>".__('Mails deleted','events-made-easy')."</div>";
                $jTableResult['Result'] = 'OK';
                break;
        }
    }
    print wp_json_encode( $jTableResult );
    wp_die();
}

function eme_send_mails_ajax_action_testmail() {
    if ( !current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        wp_die();
    }
    eme_send_mails_ajax_actions( 'testmail' );
}
function eme_send_mails_ajax_action_eventmail() {
    if ( current_user_can( get_option( 'eme_cap_send_other_mails' ) ) ||
        current_user_can( get_option( 'eme_cap_send_mails' ) ) ) {
        eme_send_mails_ajax_actions( 'eventmail' );
    } else {
        wp_die();
    }
}
function eme_send_mails_ajax_action_genericmail() {
    if ( !current_user_can( get_option( 'eme_cap_send_generic_mails' ) )) {
        wp_die();
    }
    eme_send_mails_ajax_actions( 'genericmail' );
}
function eme_send_mails_ajax_action_previewmail() {
    if ( !current_user_can( get_option( 'eme_cap_send_generic_mails' ) )) {
        wp_die();
    }
    eme_send_mails_ajax_actions( 'previewmail' );
}
function eme_send_mails_ajax_action_previeweventmail() {
    if ( current_user_can( get_option( 'eme_cap_send_other_mails' ) ) ||
        current_user_can( get_option( 'eme_cap_send_mails' ) ) ) {
        eme_send_mails_ajax_actions( 'previeweventmail' );
    } else {
        wp_die();
    }
}

function eme_send_mails_ajax_actions( $action ) {
    global $wpdb;
    header( 'Content-type: application/json; charset=utf-8' );
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    if ( ! (current_user_can( get_option( 'eme_cap_manage_mails' ) ) || current_user_can( get_option( 'eme_cap_view_mails' ) ) ) ) {
        print "<div class='error eme-message-admin'>";
        esc_html_e( 'Access denied!', 'events-made-easy' );
        print "</div>";
        wp_die();
    }
    if (current_user_can( get_option( 'eme_cap_manage_mails' ) )) {
        $actions_allowed = 1;
    } else {
        $actions_allowed = 0;
    }
    $ajaxResult       = [];
    $conditions       = [];
    $eme_date_obj_now = new ExpressiveDate( 'now', EME_TIMEZONE );

    if ( $action == 'testmail' ) {
        $testmail_to = eme_sanitize_email( $_POST['testmail_to'] );
        if ( ! eme_is_email( $testmail_to ) ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please enter a valid email address', 'events-made-easy' ) . '</p></div>';
            $ajaxResult['Result']      = 'ERROR';
            echo wp_json_encode( $ajaxResult );
            wp_die();
        }

        $contact       = eme_get_contact();
        $contact_email = $contact->user_email;
        $contact_name  = $contact->display_name;
        $person_name   = 'EME test recipient';
        $tmp_subject   = 'EME test subject';
        $tmp_message   = 'This is a test message from Events Made Easy.';
        $mail_res_arr  = eme_send_mail( $tmp_subject, $tmp_message, $testmail_to, $person_name, $contact_email, $contact_name );
        $mail_res      = $mail_res_arr[0];
        $extra_html    = eme_esc_html( $mail_res_arr[1] );
        if ( ! empty( $mail_res_arr[2] ) ) {
            // this contains debug messages
            $extra_html .= nl2br( eme_esc_html( $mail_res_arr[2] ) );
        }
        if ( $mail_res ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . "</p><p>$extra_html</p></div>";
            $ajaxResult['Result']      = 'OK';
        } else {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . "</p><p>$extra_html</p></div>";
            $ajaxResult['Result']      = 'ERROR';
        }
        echo wp_json_encode( $ajaxResult );
        wp_die();
    }

    $queue = intval( get_option( 'eme_queue_mails' ) );
    $fast_queue = 0;
    $conditions['action'] = $action;
    if ( $action == 'genericmail' || $action == 'previewmail' ) {
        if ( ! empty( $_POST['genericmail_ignore_massmail_setting'] ) ) {
            $conditions['ignore_massmail_setting'] = 1;
        }
        if ( ! empty( $_POST['eme_generic_attach_ids'] ) && eme_is_list_of_int( $_POST['eme_generic_attach_ids'] ) ) {
            $conditions['eme_generic_attach_ids'] = eme_sanitize_request( $_POST['eme_generic_attach_ids'] );
        }
        if ( ! empty( $_POST['generic_mail_subject'] ) ) {
            $mail_subject = eme_sanitize_request( $_POST['generic_mail_subject'] );
        } elseif ( isset( $_POST['generic_subject_template'] ) && intval( $_POST['generic_subject_template'] ) > 0 ) {
            $mail_subject = eme_get_template_format_plain( intval( $_POST['generic_subject_template'] ) );
        } else {
            $mail_subject = '';
        }

        if ( ! empty( $_POST['generic_mail_message'] ) ) {
            $mail_message = eme_kses_maybe_unfiltered( $_POST['generic_mail_message'] );
        } elseif ( isset( $_POST['generic_message_template'] ) && intval( $_POST['generic_message_template'] ) > 0 ) {
            $mail_message = eme_get_template_format_plain( intval( $_POST['generic_message_template'] ) );
        } else {
            $mail_message = '';
        }

        // mail filters
        $mail_subject = apply_filters( 'eme_generic_email_subject_filter', $mail_subject );
        $mail_message = apply_filters( 'eme_generic_email_body_filter', $mail_message );

        if ( empty( $mail_subject ) || empty( $mail_message ) ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please enter both subject and message for the mail to be sent.', 'events-made-easy' ) . '</p></div>';
            $ajaxResult['Result']      = 'ERROR';
            echo wp_json_encode( $ajaxResult );
            wp_die();
        }

        $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
        if ( ! empty( $_POST['generic_mail_from_name'] ) && ! empty( $_POST['generic_mail_from_email'] ) && eme_is_email( $_POST['generic_mail_from_email'] ) ) {
            $contact_name  = eme_sanitize_request( $_POST['generic_mail_from_name'] );
            $contact_email = eme_sanitize_request( $_POST['generic_mail_from_email'] );
        } else {
            [$contact_name, $contact_email] = eme_get_default_mailer_info();
        }

        if ( empty( $contact_email ) ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'No default sender defined and no event contact email found, bailing out', 'events-made-easy' ) . '</p></div>';
            $ajaxResult['Result']      = 'ERROR';
            echo wp_json_encode( $ajaxResult );
            wp_die();
        }
        $mailing_id = 0;
        if ( $action == 'previewmail' ) {
            // let's add attachments too
            $attachment_ids_arr = [];
            if ( isset( $conditions['eme_generic_attach_ids'] ) && eme_is_list_of_int( $conditions['eme_generic_attach_ids'] ) ) {
                $attachment_ids = $conditions['eme_generic_attach_ids'];
                if ( ! empty( $attachment_ids ) ) {
                    $attachment_ids_arr = explode( ',', $attachment_ids );
                }
            }
            $preview_mail_to = intval( $_POST['send_previewmailto_id'] );
            if ( $preview_mail_to == 0 ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please select a person to send the preview mail to.', 'events-made-easy' ) . '</p></div>';
                $ajaxResult['Result']      = 'ERROR';
            } else {
                $person       = eme_get_person( $preview_mail_to );
                $person_name  = eme_format_full_name( $person['firstname'], $person['lastname'] );
                $mail_subject = eme_replace_generic_placeholders( $mail_subject, 'text' );
                $mail_message = eme_replace_generic_placeholders( $mail_message, $mail_text_html );
                $mail_subject = eme_replace_people_placeholders( $mail_subject, $person, 'text' );
                $mail_message = eme_replace_people_placeholders( $mail_message, $person, $mail_text_html );
                // no queueing for preview email
                $res = eme_send_mail( $mail_subject, $mail_message, $person['email'], $person_name, $contact_email, $contact_name, $contact_email, $contact_name, $attachment_ids_arr );
                if ( $res ) {
                    $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
                    $ajaxResult['Result']      = 'OK';
                } else {
                    $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
                    $ajaxResult['Result']      = 'ERROR';
                }
            }
            echo wp_json_encode( $ajaxResult );
            wp_die();
        } else {
            if ( ! empty( $_POST['genericmail_mailing_name'] ) ) {
                $mailing_name = eme_sanitize_request( $_POST['genericmail_mailing_name'] );
            } else {
                $mailing_name = 'mailing ' . $eme_date_obj_now->getDateTime();
            }
            if ( ! empty( $_POST['genericmail_actualstartdate'] ) ) {
                $mailing_datetime = eme_sanitize_request( $_POST['genericmail_actualstartdate'] );
            } else {
                $mailing_datetime = $eme_date_obj_now->getDateTime();
                $fast_queue=1;
            }
            if ( isset( $_POST['eme_send_all_people'] ) ) {
                $conditions['eme_send_all_people'] = 1;
            } else {
                if ( ! empty( $_POST['eme_genericmail_send_persons'] ) && eme_is_numeric_array( $_POST['eme_genericmail_send_persons'] ) ) {
                    $conditions['eme_genericmail_send_persons'] = join( ',', $_POST['eme_genericmail_send_persons'] );
                }
                if ( ! empty( $_POST['eme_send_members'] ) && eme_is_numeric_array( $_POST['eme_send_members'] ) ) {
                    $conditions['eme_send_members'] = join( ',', $_POST['eme_send_members'] );
                }
                if ( ! empty( $_POST['eme_genericmail_send_peoplegroups'] ) && eme_is_numeric_array( $_POST['eme_genericmail_send_peoplegroups'] ) ) {
                    $conditions['eme_genericmail_send_peoplegroups'] = join( ',', $_POST['eme_genericmail_send_peoplegroups'] );
                }
                if ( ! empty( $_POST['eme_genericmail_send_membergroups'] ) && eme_is_numeric_array( $_POST['eme_genericmail_send_membergroups'] ) ) {
                    $conditions['eme_genericmail_send_membergroups'] = join( ',', $_POST['eme_genericmail_send_membergroups'] );
                }
                if ( ! empty( $_POST['eme_send_memberships'] ) && eme_is_numeric_array( $_POST['eme_send_memberships'] ) ) {
                    $conditions['eme_send_memberships'] = join( ',', $_POST['eme_send_memberships'] );
                }
            }
            if ( $queue && $fast_queue ) {
                $mailing_id = eme_db_insert_ongoing_mailing( $mailing_name, $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
                $res = eme_update_mailing_receivers( $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions, $mailing_id );
            } elseif ( $queue ) {
                // in case we want a mailing to be done at multiple times, the times are separated by ","
                $dates = explode( ',', $mailing_datetime );
                foreach ( $dates as $datetime ) {
                    $mailing_id = eme_db_insert_mailing( $mailing_name, $datetime, $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
                    // we just need the count of receivers here, the actual insert of individual mails happens when the mailing starts
                    $res        = eme_count_planned_mailing_receivers( $conditions, $mailing_id );
                    eme_mark_mailing_planned( $mailing_id, $res['total'] );
                }
            } else {
                $res = eme_update_mailing_receivers( $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
            }
        }

        //now, we use the res output from the last call of eme_update_mailing_receivers (in case of multiple planned mailings, possible problems are the same for all anyway)
        if ( ! $res['mail_problems'] ) {
            if ( $queue ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mailing has been planned.', 'events-made-easy' ) . '</p></div>';
            } else {
                $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
            }
            $ajaxResult['Result'] = 'OK';
        } else {
            if ( $queue ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mailing has been put on the queue, but not all persons will receive it.', 'events-made-easy' ) . '</p></div>';
                if ( ! empty( $res['not_sent'] ) ) {
                    $ajaxResult['htmlmessage'] .= "<div id='message' class='error eme-message-admin'><p>" . __( 'The following persons will not receive the mail:', 'events-made-easy' ) . ' ' . eme_esc_html( $res['not_sent'] ) . '</p></div>';
                }
            } else {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
                if ( ! empty( $res['not_sent'] ) ) {
                    $ajaxResult['htmlmessage'] .= "<div id='message' class='error eme-message-admin'><p>" . __( 'Email to the following persons has not been sent:', 'events-made-easy' ) . ' ' . eme_esc_html( $res['not_sent'] ) . '</p></div>';
                }
            }
            $ajaxResult['Result'] = 'ERROR';
        }
        echo wp_json_encode( $ajaxResult );
        wp_die();
    }

    if ( $action == 'eventmail' || $action == 'previeweventmail' ) {
        if ( ! empty( $_POST['eventmail_ignore_massmail_setting'] ) ) {
            $conditions['ignore_massmail_setting'] = 1;
        }
        if ( ! empty( $_POST['eme_eventmail_attach_ids'] ) && eme_is_list_of_int( $_POST['eme_eventmail_attach_ids'] ) ) {
            $conditions['eme_eventmail_attach_ids'] = eme_sanitize_request( $_POST['eme_eventmail_attach_ids'] );
        }
        if ( ! empty( $_POST ['event_mail_subject'] ) ) {
            $mail_subject = eme_sanitize_request( $_POST ['event_mail_subject'] );
        } elseif ( isset( $_POST ['event_subject_template'] ) && intval( $_POST ['event_subject_template'] ) > 0 ) {
            $mail_subject = eme_get_template_format_plain( intval( $_POST ['event_subject_template'] ) );
        } else {
            $mail_subject = '';
        }

        if ( ! empty( $_POST ['event_mail_message'] ) ) {
            $mail_message = eme_kses_maybe_unfiltered( $_POST ['event_mail_message'] );
        } elseif ( isset( $_POST ['event_message_template'] ) && intval( $_POST ['event_message_template'] ) > 0 ) {
            $mail_message = eme_get_template_format_plain( intval( $_POST ['event_message_template'] ) );
        } else {
            $mail_message = '';
        }

        // mail filters
        $mail_subject = apply_filters( 'eme_event_email_subject_filter', $mail_subject );
        $mail_message = apply_filters( 'eme_event_email_body_filter', $mail_message );

        if ( empty( $mail_subject ) || empty( $mail_message ) ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please enter both subject and message for the mail to be sent.', 'events-made-easy' ) . '</p></div>';
            $ajaxResult['Result']      = 'ERROR';
            echo wp_json_encode( $ajaxResult );
            wp_die();
        }

        $event_ids = isset( $_POST['event_ids'] ) ? wp_parse_id_list($_POST['event_ids']) : 0;
        if ( ! eme_is_numeric_array( $event_ids ) ) {
            $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please select at least one event.', 'events-made-easy' ) . '</p></div>';
            $ajaxResult['Result']      = 'ERROR';
            echo wp_json_encode( $ajaxResult );
            wp_die();
        }

        if ( ! empty( $_POST['eventmail_mailing_name'] ) ) {
            $mailing_name = eme_sanitize_request( $_POST['eventmail_mailing_name'] );
        } else {
            $mailing_name = 'event mailing ' . $eme_date_obj_now->getDateTime();
        }
        if ( ! empty( $_POST['eventmail_actualstartdate'] ) ) {
            $mailing_datetime = eme_sanitize_request( $_POST['eventmail_actualstartdate'] );
        } else {
            $mailing_datetime = $eme_date_obj_now->getDateTime();
            $fast_queue=1;
        }
        if ( $action == 'previeweventmail' ) {
            // let's add attachments too
            $attachment_ids_arr = [];
            if ( isset( $conditions['eme_generic_attach_ids'] ) ) {
                $attachment_ids = $conditions['eme_generic_attach_ids'];
                if ( ! empty( $attachment_ids ) ) {
                    $attachment_ids_arr = explode( ',', $attachment_ids );
                }
            }
            $preview_mail_to = intval( $_POST['send_previeweventmailto_id'] );
            if ( $preview_mail_to == 0 ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please select a person to send the preview mail to.', 'events-made-easy' ) . '</p></div>';
                $ajaxResult['Result']      = 'ERROR';
            } else {
                $person      = eme_get_person( $preview_mail_to );
                $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );
                $event       = eme_get_event( $event_ids[0] );
                if ( ! empty( $event ) ) {
                    $contact       = eme_get_event_contact( $event );
                    $contact_email = $contact->user_email;
                    $contact_name  = $contact->display_name;
                    $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
                    $mail_subject  = eme_replace_event_placeholders( $mail_subject, $event, 'text', $person['lang'], 0 );
                    $mail_message  = eme_replace_event_placeholders( $mail_message, $event, $mail_text_html, $person['lang'], 0 );
                    $mail_message  = eme_replace_email_event_placeholders( $mail_message, $person['email'], $person['lastname'], $person['firstname'], $event, $person['lang'] );
                    $mail_subject  = eme_replace_people_placeholders( $mail_subject, $person, 'text' );
                    $mail_message  = eme_replace_people_placeholders( $mail_message, $person, $mail_text_html );
                    // no queueing for preview email
                    $res = eme_send_mail( $mail_subject, $mail_message, $person['email'], $person_name, $contact_email, $contact_name, $contact_email, $contact_name, $attachment_ids_arr );
                    if ( $res ) {
                        $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
                        $ajaxResult['Result']      = 'OK';
                    } else {
                        $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
                        $ajaxResult['Result']      = 'ERROR';
                    }
                } else {
                    $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'No such event', 'events-made-easy' ) . '</p></div>';
                    $ajaxResult['Result']      = 'ERROR';
                }
            }
            echo wp_json_encode( $ajaxResult );
            wp_die();
        } else {
            if ( ! empty( $_POST['eme_eventmail_send_persons'] ) && eme_is_numeric_array( $_POST['eme_eventmail_send_persons'] ) ) {
                $conditions['eme_eventmail_send_persons'] = join( ',', $_POST['eme_eventmail_send_persons'] );
            }
            if ( ! empty( $_POST['eme_eventmail_send_groups'] ) && eme_is_numeric_array( $_POST['eme_eventmail_send_groups'] ) ) {
                $conditions['eme_eventmail_send_groups'] = join( ',', $_POST['eme_eventmail_send_groups'] );
            }
            if ( ! empty( $_POST['eme_eventmail_send_members'] ) && eme_is_numeric_array( $_POST['eme_eventmail_send_members'] ) ) {
                $conditions['eme_eventmail_send_members'] = join( ',', $_POST['eme_eventmail_send_members'] );
            }
            if ( ! empty( $_POST['eme_eventmail_send_membergroups'] ) && eme_is_numeric_array( $_POST['eme_eventmail_send_membergroups'] ) ) {
                $conditions['eme_eventmail_send_membergroups'] = join( ',', $_POST['eme_eventmail_send_membergroups'] );
            }
            if ( ! empty( $_POST['eme_eventmail_send_memberships'] ) && eme_is_numeric_array( $_POST['eme_eventmail_send_memberships'] ) ) {
                $conditions['eme_eventmail_send_memberships'] = join( ',', $_POST['eme_eventmail_send_memberships'] );
            }
            $eme_mail_type = isset( $_POST ['eme_mail_type'] ) ? eme_sanitize_request($_POST ['eme_mail_type']) : 'attendees';
            if ( empty( $eme_mail_type ) ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Please select the type of mail to be sent.', 'events-made-easy' ) . '</p></div>';
                $ajaxResult['Result']      = 'ERROR';
                echo wp_json_encode( $ajaxResult );
                wp_die();
            }
            $conditions['eme_mail_type'] = $eme_mail_type;
        }

        $mailing_id                       = 0;
        $rsvp_status                      = isset( $_POST ['rsvp_status'] ) ? intval( $_POST ['rsvp_status'] ) : 0;
        $only_unpaid                      = isset( $_POST ['only_unpaid'] ) ? intval( $_POST ['only_unpaid'] ) : 0;
        $exclude_registered               = isset( $_POST ['exclude_registered'] ) ? intval( $_POST ['exclude_registered'] ) : 0;
        $conditions['rsvp_status']        = $rsvp_status;
        $conditions['only_unpaid']        = $only_unpaid;
        $conditions['exclude_registered'] = $exclude_registered;
        $current_userid                   = get_current_user_id();
        $mail_problems                    = 0;
        $mail_access_problems             = 0;
        $not_sent                         = [];
        $count_event_ids                  = count( $event_ids );
        foreach ( $event_ids as $event_id ) {
            $conditions['event_id'] = $event_id;
            $event                  = eme_get_event( $event_id );
            if ( empty( $event ) ) {
                continue;
            }
            $mailing_id = 0;
            if ( $count_event_ids > 1 ) {
                $mailing_name .= " ($event_id)";
            }
            if ( current_user_can( get_option( 'eme_cap_send_other_mails' ) ) ||
                ( current_user_can( get_option( 'eme_cap_send_mails' ) ) && ( $event['event_author'] == $current_userid || $event['event_contactperson_id'] == $current_userid ) ) ) {
                $contact        = eme_get_event_contact( $event );
                $contact_email  = $contact->user_email;
                $contact_name   = $contact->display_name;
                $mail_text_html = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';

                if ( $queue && $fast_queue ) {
                    $mailing_id = eme_db_insert_ongoing_mailing( $mailing_name, $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
                    $res = eme_update_mailing_receivers( $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions, $mailing_id );
                } elseif ( $queue ) {
                    // in case we want a mailing to be done at multiple times, the times are separated by ","
                    $dates = explode( ',', $mailing_datetime );
                    foreach ( $dates as $datetime ) {
                        $mailing_id = eme_db_insert_mailing( $mailing_name, $datetime, $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
                        // we just need the count of receivers here, the actual insert of individual mails happens when the mailing starts
                        $res        = eme_count_planned_mailing_receivers( $conditions, $mailing_id );
                        eme_mark_mailing_planned( $mailing_id, $res['total'] );
                    }
                } else {
                    $res = eme_update_mailing_receivers( $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html, $conditions );
                }
                $mail_problems += $res['mail_problems'];
                $not_sent[]     = $res['not_sent'];
            } else {
                $mail_access_problems = 1;
            }
        }

        if ( ! $mail_problems ) {
            if ( $queue ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mailing has been planned.', 'events-made-easy' ) . '</p></div>';
            } else {
                $ajaxResult['htmlmessage'] = "<div id='message' class='updated eme-message-admin'><p>" . __( 'The mail has been sent.', 'events-made-easy' ) . '</p></div>';
            }
            $ajaxResult['Result'] = 'OK';
        } else {
            if ( $mail_access_problems ) {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'Only mails for events you have the right to send mails for have been sent.', 'events-made-easy' ) . '</p></div>';
            } else {
                $ajaxResult['htmlmessage'] = "<div id='message' class='error eme-message-admin'><p>" . __( 'There were some problems while sending mail.', 'events-made-easy' ) . '</p></div>';
                if ( ! empty( $not_sent ) ) {
                    $ajaxResult['htmlmessage'] .= "<div class='error eme-message-admin'><p>" . __( 'Email to the following persons has not been sent:', 'events-made-easy' ) . ' ' . join( ', ', eme_esc_html( $not_sent ) ) . '</p></div>';
                }
            }
            $ajaxResult['Result'] = 'ERROR';
        }
        echo wp_json_encode( $ajaxResult );
        wp_die();
    }
    wp_die();
}

function eme_emails_page() {
    $eme_queue_mails = get_option( 'eme_queue_mails' );
    if ( ! wp_next_scheduled( 'eme_cron_send_queued' ) ) {
        $eme_queue_mails_configured = 0;
    } else {
        $eme_queue_mails_configured = 1;
    }

    $mygroups        = [];
    $mymembergroups  = [];
    $myevents        = [];
    $person_ids      = [];
    $event_ids       = [];
    $membership_ids  = [];
    $persongroup_ids = [];
    $membergroup_ids = [];
    $member_ids      = [];
    // if we get a request for mailings, set the active tab to the 'tab-genericmails' tab (which is index 1)
    if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'new_mailing' ) {
        $data_forced_tab = 'data-showtab="tab-genericmails"';
        if ( isset( $_POST['tasksignup_ids'] ) ) {
            // when editing, select2 needs a populated list of selected items
            $tasksignup_ids = eme_sanitize_request($_POST['tasksignup_ids']);
            $person_ids     = eme_get_tasksignup_personids( $tasksignup_ids );
            $persons        = eme_get_persons( $person_ids );
            foreach ( $persons as $person ) {
                $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'] );
            }
        }
        if ( isset( $_POST['booking_ids'] ) ) {
            // when editing, select2 needs a populated list of selected items
            $booking_ids = eme_sanitize_request($_POST['booking_ids']);
            $person_ids  = eme_get_booking_personids( $booking_ids );
            $persons     = eme_get_persons( $person_ids );
            foreach ( $persons as $person ) {
                $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'] );
            }
        }
        if ( isset( $_POST['person_ids'] ) ) {
            // when editing, select2 needs a populated list of selected items
            $person_ids = explode( ',', eme_sanitize_request($_POST['person_ids'] ));
            $persons    = eme_get_persons( $person_ids );
            foreach ( $persons as $person ) {
                $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'] );
            }
        }
        if ( isset( $_POST['member_ids'] ) ) {
            // when editing, select2 needs a populated list of selected items
            $member_ids = explode( ',', eme_sanitize_request($_POST['member_ids'] ));
            $members    = eme_get_members( $member_ids );
            foreach ( $members as $member ) {
                $mymembergroups[ $member['member_id'] ] = eme_format_full_name( $member['firstname'], $member['lastname'] );
            }
        }
    } else {
        $data_forced_tab = '';
    }
    $exclude_registered_checked     = '';
    $only_unpaid_checked            = '';
    $eme_mail_type                  = '';
    $send_to_all_people_checked     = '';
    $event_mail_subject             = '';
    $event_mail_message             = '';
    $event_mail_attachment_ids      = '';
    $event_mail_attach_url_string   = '';
    $generic_mail_subject           = '';
    $generic_mail_message           = '';
    $generic_mail_attachment_ids    = '';
    $generic_mail_attach_url_string = '';
    $generic_mail_ignore_massmail_setting = '';
    $event_mail_ignore_massmail_setting   = '';
    #$ignore_massmail_setting        = '';
    #$attachment_ids    = '';
    #$attach_url_string = '';

    [$generic_mail_from_name, $generic_mail_from_email] = eme_get_default_mailer_info();

    $peoplegroups = eme_get_groups();
    $membergroups = eme_get_membergroups();

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'reuse_mail' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $id   = intval( $_GET['id'] );
        $mail = eme_get_mail( $id );
        if ( $mail ) {
            $generic_mail_subject    = $mail['subject'];
            $generic_mail_message    = $mail['body'];
            $generic_mail_from_name  = $mail['fromname'];
            $generic_mail_from_email = $mail['fromemail'];
            if ( $mail['person_id'] > 0 ) {
                $person_ids[] = $mail['person_id'];
                $person       = eme_get_person( $mail['person_id'] );
                if ( !empty( $person ) ) {
                    $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
                }
            } elseif ( $mail['member_id'] > 0 ) {
                $member_ids[] = $mail['member_id'];
                $member       = eme_get_member( $mail['member_id'] );
                if (! empty( $member ) ) {
                    $person = eme_get_person( $member['person_id'] );
                    if ( !empty( $person ) ) {
                        $mymembergroups[ $member['member_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'], $person['firstname'] );
                    }
                }
            }
            // reuse the attachments too
            if ( ! empty( $mail['attachments'] ) ) {
                $attachment_ids_arr = eme_unserialize( $mail['attachments'] );
                // now also build the attach_url_string variable
                foreach ( $attachment_ids_arr as $attachment_id ) {
                    if (is_int( $attachment_id )) 
                        $generic_mail_attachment_ids[] = $attachment_id;
                    $attach_link = eme_get_attachment_link( $attachment_id );
                    if ( ! empty( $attach_link ) ) {
                        $generic_mail_attach_url_string .= $attach_link;
                        $generic_mail_attach_url_string .= '<br \>';
                    }
                }
            }
            $data_forced_tab = 'data-showtab="tab-genericmails"';
        }
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'retry_failed_mailing' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $id      = intval( $_GET['id'] );
        $mailing = eme_get_mailing( $id );
        if ( $mailing ) {
            eme_mailing_retry_failed( $id );
            eme_mark_mailing_ongoing( $id );
        }
        $data_forced_tab = 'data-showtab="tab-mailings"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'reuse_mailing' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $id      = intval( $_GET['id'] );
        $mailing = eme_get_mailing( $id );
        if ( $mailing ) {
            $conditions = eme_unserialize( $mailing['conditions'] );
            if ( $conditions['action'] == 'genericmail' ) {
                if ( ! empty( $conditions['ignore_massmail_setting'] ) ) {
                    $generic_mail_ignore_massmail_setting = "checked='checked'";
                }
                $generic_mail_subject    = $mailing['subject'];
                $generic_mail_message    = $mailing['body'];
                $generic_mail_from_name  = $mailing['fromname'];
                $generic_mail_from_email = $mailing['fromemail'];
                $data_forced_tab         = 'data-showtab="tab-genericmails"';
                if ( ! empty( $conditions['eme_send_all_people'] ) ) {
                    $send_to_all_people_checked = "checked='checked'";
                } else {
                    if ( ! empty( $conditions['eme_genericmail_send_persons'] ) ) {
                        $person_ids = explode( ',', $conditions['eme_genericmail_send_persons'] );
                        $persons    = eme_get_persons( $person_ids );
                        foreach ( $persons as $person ) {
                            $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
                        }
                    }
                    if ( ! empty( $conditions['eme_send_members'] ) ) {
                        $member_ids = explode( ',', $conditions['eme_send_members'] );
                        $members    = eme_get_members( $member_ids );
                        foreach ( $members as $member ) {
                            $mymembergroups[ $member['member_id'] ] = eme_format_full_name( $member['firstname'], $member['lastname'], $person['email'] );
                        }
                    }
                    if ( ! empty( $conditions['eme_genericmail_send_peoplegroups'] ) ) {
                        $persongroup_ids = explode( ',', $conditions['eme_genericmail_send_peoplegroups'] );
                    }
                    if ( ! empty( $conditions['eme_genericmail_send_membergroups'] ) ) {
                        $membergroup_ids = explode( ',', $conditions['eme_genericmail_send_membergroups'] );
                    }
                    if ( ! empty( $conditions['eme_send_memberships'] ) ) {
                        $membership_ids = explode( ',', $conditions['eme_send_memberships'] );
                    }
                }
                // reuse the attachments too
                if ( ! empty( $conditions['eme_generic_attach_ids'] ) && eme_is_list_of_int( $conditions['eme_generic_attach_ids'] ) ) {
                    $generic_mail_attachment_ids     = $conditions['eme_generic_attach_ids'];
                    // now also build the attach_url_string variable
                    $attachment_ids_arr = explode( ',', $generic_mail_attachment_ids );
                    foreach ( $attachment_ids_arr as $attachment_id ) {
                        $attach_link = eme_get_attachment_link( $attachment_id );
                        if ( ! empty( $attach_link ) ) {
                            $generic_mail_attach_url_string .= $attach_link;
                            $generic_mail_attach_url_string .= '<br \>';
                        }
                    }
                }
            } elseif ( $conditions['action'] == 'eventmail' ) {
                if ( ! empty( $conditions['ignore_massmail_setting'] ) ) {
                    $event_mail_ignore_massmail_setting = "checked='checked'";
                }
                $event_mail_subject = $mailing['subject'];
                $event_mail_message = $mailing['body'];
                if ( ! empty( $conditions['eme_mail_type'] ) ) {
                    $eme_mail_type = $conditions['eme_mail_type'];
                }
                if ( ! empty( $conditions['exclude_registered'] ) ) {
                    $exclude_registered_checked = "checked='checked'";
                }
                if ( ! empty( $conditions['only_unpaid'] ) ) {
                    $only_unpaid_checked = "checked='checked'";
                }
                $data_forced_tab    = 'data-showtab="tab-eventmails"';
                if ( ! empty( $conditions['event_id'] ) ) {
                    $event_ids = explode( ',', $conditions['event_id'] );
                    $events    = eme_get_events(  extra_conditions: 'event_id IN ('.$conditions['event_id'].')' );
                    foreach ( $events as $event ) {
                        $myevents[ $event['event_id'] ] = $event['event_name']. ' (' . eme_localized_date( $event['event_start'], EME_TIMEZONE, 1 ) . ')';
                    }
                }
                if ( ! empty( $conditions['eme_eventmail_send_persons'] ) ) {
                    $person_ids = explode( ',', $conditions['eme_eventmail_send_persons'] );
                    $persons    = eme_get_persons( $person_ids );
                    foreach ( $persons as $person ) {
                        $mygroups[ $person['person_id'] ] = eme_format_full_name( $person['firstname'], $person['lastname'], $person['email'] );
                    }
                }
                if ( ! empty( $conditions['eme_eventmail_send_members'] ) ) {
                    $member_ids = explode( ',', $conditions['eme_eventmail_send_members'] );
                    $members    = eme_get_members( $member_ids );
                    foreach ( $members as $member ) {
                        $mymembergroups[ $member['member_id'] ] = eme_format_full_name( $member['firstname'], $member['lastname'], $member['email'] );
                    }
                }
                if ( ! empty( $conditions['eme_eventmail_send_groups'] ) ) {
                    $persongroup_ids = explode( ',', $conditions['eme_eventmail_send_groups'] );
                }
                if ( ! empty( $conditions['eme_eventmail_send_membergroups'] ) ) {
                    $membergroup_ids = explode( ',', $conditions['eme_eventmail_send_membergroups'] );
                }
                if ( ! empty( $conditions['eme_eventmail_send_memberships'] ) ) {
                    $membership_ids = explode( ',', $conditions['eme_eventmail_send_memberships'] );
                }
                // reuse the attachments too
                if ( ! empty( $conditions['eme_eventmail_attach_ids'] ) && eme_is_list_of_int( $conditions['eme_eventmail_attach_ids'] ) ) {
                    $event_mail_attachment_ids     = $conditions['eme_eventmail_attach_ids'];
                    // now also build the attach_url_string variable
                    $attachment_ids_arr = explode( ',', $event_mail_attachment_ids );
                    foreach ( $attachment_ids_arr as $attachment_id ) {
                        $attach_link = eme_get_attachment_link( $attachment_id );
                        if ( ! empty( $attach_link ) ) {
                            $event_mail_attach_url_string .= $attach_link;
                            $event_mail_attach_url_string .= '<br \>';
                        }
                    }
                }
            }
        }
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'archive_mailing' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_archive_mailing( $id );
        $data_forced_tab    = 'data-showtab="tab-mailings"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'delete_mailing' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_delete_mailing( $id );
        $data_forced_tab    = 'data-showtab="tab-mailings"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'delete_archivedmailing' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_delete_mailing( $id );
        $data_forced_tab    = 'data-showtab="tab-mailingsarchive"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'cancel_mailing' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_cancel_mailing( $id );
        $data_forced_tab    = 'data-showtab="tab-mailings"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'cancel_mail' && isset( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_cancel_mail( $id );
        $data_forced_tab    = 'data-showtab="tab-sentmail"';
    }
    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'report_mailing' && isset( $_GET['id'] ) ) {
        // the id param will be captured by js to fill out the report table via jtable
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
?>
        <div class="wrap nosubsub">
        <div id="poststuff">
        <div id="icon-edit" class="icon32">
        </div>
        <h1><?php esc_html_e( 'Mailing report', 'events-made-easy' ); ?></h1>
    <form action="#" method="post">
    <input type="search" class="eme_searchfilter" name="search_name" id="search_name" placeholder="<?php esc_attr_e( 'Person name', 'events-made-easy' ); ?>" size=10>
    <button id="ReportLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter', 'events-made-easy' ); ?></button>
    </form>
    <!--
    <p><?php esc_html_e( 'Remark: the list of recipients below is just an indication based on the moment the mailing was created. Just before the mailing will actually start, this list will be refreshed based on the conditions the mailing was created with.', 'events-made-easy' ); ?></p>
    -->
    <div id="MailingReportTableContainer"></div>
        </div>
        </div>
<?php
        return;
    }

    $templates_array = eme_get_templates_array_by_id( 'rsvpmail' );
    $memberships     = eme_get_memberships();
    // now show the form
?>
<div class="wrap">
<div id="icon-events" class="icon32">
</div>
<div class="eme-tabs" <?php echo $data_forced_tab; ?>>
    <div class="eme-tab" data-tab="tab-eventmails"><?php esc_html_e( 'Event related email', 'events-made-easy' ); ?></div>
    <div class="eme-tab" data-tab="tab-genericmails"><?php esc_html_e( 'Generic email', 'events-made-easy' ); ?></div>
    <div class="eme-tab" data-tab="tab-mailings"><?php esc_html_e( 'Mailings', 'events-made-easy' ); ?></div>
    <div class="eme-tab" data-tab="tab-mailingsarchive"><?php esc_html_e( 'Mailings archive', 'events-made-easy' ); ?></div>
    <div class="eme-tab" data-tab="tab-sentmail"><?php esc_html_e( 'Sent emails', 'events-made-easy' ); ?></div>
    <div class="eme-tab" data-tab="tab-testmail"><?php esc_html_e( 'Test email', 'events-made-easy' ); ?></div>
</div>
<div class="eme-tab-content" id="tab-mailings">
        <?php eme_mailings_div(); ?>
</div>
<div class="eme-tab-content" id="tab-mailingsarchive">
        <?php eme_mailings_archive_div(); ?>
</div>
<div class="eme-tab-content" id="tab-sentmail">
        <?php eme_mails_div(); ?>
</div>
<div class="eme-tab-content" id="tab-eventmails">
    <h1><?php esc_html_e( 'Send event related emails', 'events-made-easy' ); ?></h1>
    <form id='send_mail' name='send_mail' action="#" method="post" onsubmit="return false;">
    <div id='send_event_mail_div'>
        <table>
        <tr>
        <td><?php
    $label      = esc_html__( 'Select the event(s)', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect( $event_ids, 'event_ids', $myevents, 5, '', 0, 'eme_select2_events_class', $aria_label ); ?>
        <br><label><input id="eventsearch_all" name='eventsearch_all' value='1' type='checkbox'> <?php esc_html_e( 'Check this box to search through all events and not just future ones.', 'events-made-easy' ); ?> </label>
            <p class='eme_smaller'><?php esc_html_e( 'Remark: if you select multiple events, a mailing will be created for each selected event', 'events-made-easy' ); ?></p>
        </td>
        </tr>
        <tr>
        <td><?php esc_html_e( 'Select the type of mail', 'events-made-easy' ); ?></td>
        <td>
<?php
    $eme_mail_type_arr = [
        'attendees' => __( 'Attendee mails', 'events-made-easy' ),
        'bookings' => __('Booking mails', 'events-made-easy'),
        'all_people' => __('Email to all people registered in EME', 'events-made-easy'),
        'people_and_groups' => __('Email to people and/or groups registered in EME', 'events-made-easy'),
        'all_wp' => __('Email to all WP users', 'events-made-easy'),
    ];
    echo eme_ui_select( $eme_mail_type, 'eme_mail_type', $eme_mail_type_arr, '&nbsp;', 1);
?>
        </td>
        </tr>
        <tr id="eme_rsvp_status_row">
        <td><?php esc_html_e( 'Select your target audience', 'events-made-easy' ); ?></td>
        <td>
            <select name="rsvp_status">
            <option value=0><?php esc_html_e( 'All registered persons', 'events-made-easy' ); ?></option>
            <option value=<?php echo EME_RSVP_STATUS_APPROVED; ?>><?php esc_html_e( 'Only approved bookings', 'events-made-easy' ); ?></option>
            <option value=<?php echo EME_RSVP_STATUS_PENDING; ?>><?php esc_html_e( 'Only pending bookings', 'events-made-easy' ); ?></option>
            </select>
        </td>
        </tr>
        <tr id="eme_exclude_registered_row">
        <td><?php esc_html_e( 'Exclude people already registered for the selected event(s)', 'events-made-easy' ); ?>&nbsp;</td>
        <td>
        <input type="checkbox" name="exclude_registered" value="1" <?php echo $exclude_registered_checked; ?>>
        </td>
        </tr>
        <tr id="eme_only_unpaid_row">
        <td><span id="span_unpaid_attendees"><?php esc_html_e( 'Only send mails to attendees who did not pay yet', 'events-made-easy' ); ?></span>
        <span id="span_unpaid_bookings"><?php esc_html_e( 'Only take unpaid bookings into account', 'events-made-easy' ); ?></span>
        &nbsp;
        </td>
        <td>
            <input type="checkbox" name="only_unpaid" value="1" <?php echo $only_unpaid_checked; ?>>
        </td>
        </tr>
        <tr id="eme_people_row">
        <td>
<?php
    $label      = eme_esc_html( 'Send to a number of people', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect( $person_ids, 'eme_eventmail_send_persons', $mygroups, 5, '', 0, 'eme_select2_people_class', $aria_label ); ?></td>
        </tr>
        <tr id="eme_groups_row">
        <td width='20%' class="eme-wsnobreak">
<?php
    $label      = eme_esc_html( 'Send to a number of groups', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect_key_value( $persongroup_ids, 'eme_eventmail_send_groups', $peoplegroups, 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class', $aria_label ); ?></td>
        </tr>
    <tr id="eme_members_row1"><td width='20%' class="eme-wsnobreak">
<?php
    $label      = eme_esc_html( 'Send to a number of members', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
    </td>
    <td><?php echo eme_ui_multiselect( $member_ids, 'eme_eventmail_send_members', $mymembergroups, 5, '', 0, 'eme_select2_members_class', $aria_label ); ?></td></tr>
    <tr id="eme_members_row2"><td width='20%' class="eme-wsnobreak">
<?php
    $label      = eme_esc_html( 'Send to a number of member groups', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
    </td>
    <td><?php echo eme_ui_multiselect_key_value( $membergroup_ids, 'eme_eventmail_send_membergroups', $membergroups, 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class', $aria_label ); ?></td></tr>
    <tr id="eme_members_row3"><td width='20%' class="eme-wsnobreak">
<?php
    $label      = eme_esc_html( 'Send to active members belonging to', 'events-made-easy' );
    $aria_label = 'aria-label="' . $label . '"';
    echo $label;
?>
    </td>
    <td><?php echo eme_ui_multiselect_key_value( $membership_ids, 'eme_eventmail_send_memberships', $memberships, 'membership_id', 'name', 5, '', 0, 'eme_select2_memberships_class', $aria_label ); ?></td></tr>
        </table>
        <div class="form-field"><p>
        <b><?php esc_html_e( 'Subject', 'events-made-easy' ); ?></b><br>
<?php
    esc_html_e( 'Either choose from a template: ', 'events-made-easy' );
    echo eme_ui_select( 0, 'event_subject_template', $templates_array );
?>
        <br>
        <?php esc_html_e( 'Or enter your own: ', 'events-made-easy' ); ?>
        <input type="text" name="event_mail_subject" id="event_mail_subject" value="<?php echo eme_esc_html( $event_mail_subject ); ?>">
        </p></div>
        <div class="form-field"><p>
        <b><?php esc_html_e( 'Message', 'events-made-easy' ); ?></b><br>
<?php
    esc_html_e( 'Either choose from a template: ', 'events-made-easy' );
    echo eme_ui_select( 0, 'event_message_template', $templates_array );
?>
        <br>
<?php
    esc_html_e( 'Or enter your own: ', 'events-made-easy' );
?>
        </p>
<?php
    if ( get_option( 'eme_mail_send_html' ) ) {
        // for mails, let enable the full html editor
        eme_wysiwyg_textarea( 'event_mail_message', $event_mail_message, 1, 1 );
        if ( current_user_can( 'unfiltered_html' ) ) {
            echo "<div class='eme_notice_unfiltered_html'>";
            esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
            echo '</div>';
        }
    } else {
        echo "<textarea name='event_mail_message' id='event_mail_message' rows='10' required='required'>" . eme_esc_html( $event_mail_message ) . '</textarea>';
    }
?>
        </div>
        <div><p>
<?php
    esc_html_e( 'You can use any placeholders mentioned here:', 'events-made-easy' );
    print "<br><a href='//www.e-dynamics.be/wordpress/?cat=25'>" . __( 'Event placeholders', 'events-made-easy' ) . '</a>';
    print "<br><a href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-12-people/'>" . __( 'People placeholders', 'events-made-easy' ) . '</a>';
    print "<br><a href='//www.e-dynamics.be/wordpress/?cat=48'>" . __( 'Attendees placeholders', 'events-made-easy' ) . '</a> (' . __( 'for ', 'events-made-easy' ) . __( 'Attendee mails', 'events-made-easy' ) . ')';
    print "<br><a href='//www.e-dynamics.be/wordpress/?cat=45'>" . __( 'Booking placeholders', 'events-made-easy' ) . '</a> (' . __( 'for ', 'events-made-easy' ) . __( 'Booking mails', 'events-made-easy' ) . ')';
    print "<br><a href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . __( 'Member placeholders', 'events-made-easy' ) . '</a> (' . __( 'if you selected members, memberships or member groups', 'events-made-easy' ) . ')';
    print '<br>' . __( 'You can also use any shortcode you want.', 'events-made-easy' );
?>
        </p></div>
            <hr>
        <div id='div_event_mailing_attach'>
        <p>
        <b><?php esc_html_e( 'Optionally add attachments to your mailing', 'events-made-easy' ); ?></b><br>
        <span id="eventmail_attach_links"><?php echo $event_mail_attach_url_string; ?></span>
        <input type="hidden" name="eme_eventmail_attach_ids" id="eme_eventmail_attach_ids" value="<?php echo $event_mail_attachment_ids; ?>">
        <input type="button" name="eventmail_attach_button" id="eventmail_attach_button" class="button-secondary action" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>">
        <input type="button" name="eventmail_remove_attach_button" id="eventmail_remove_attach_button" class="button-secondary action" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>">
        </p>
        </div>
<?php
    if ( $eme_queue_mails ) {
?>
        <hr>
        <div id='div_event_mailing_definition'>
        <p>
        <b><?php esc_html_e( 'Set mailing name and start date and time', 'events-made-easy' ); ?></b><br>
                <label for='eventmail_mailing_name'><?php esc_html_e( 'Mailing name: ', 'events-made-easy' ); ?></label> <input type='text' name='eventmail_mailing_name' id='eventmail_mailing_name' value='' required='required'><br>
                <?php esc_html_e( 'Start date and time: ', 'events-made-easy' ); ?>
        <input type='hidden' name='eventmail_actualstartdate' id='eventmail_actualstartdate' value=''>
                <input type='text' readonly='readonly' name='eventmail_startdate' id='eventmail_startdate' data-date='' data-alt-field='eventmail_actualstartdate' data-multiple-dates="true" style="background: #FCFFAA;"><?php esc_html_e( 'Leave empty to send the mail immediately', 'events-made-easy' ); ?><br>
        <span id='eventmail-specificdates' class="eme_smaller"></span>
        <span id='eventmail-multidates-expl' class="eme_smaller"><?php esc_html_e( '(multiple dates can be selected, in which case the mailing will be planned on each selected date and time)', 'events-made-easy' ); ?></span>
        </p>
        </div>
        <?php } ?>
        <hr>
        <div id='div_event_ignore_massmail_setting'>
        <p>
        <b><label for='eventmail_ignore_massmail_setting'><?php esc_html_e( 'Ignore massmail setting:', 'events-made-easy' ); ?></label></b>
                <input id="eventmail_ignore_massmail_setting" name='eventmail_ignore_massmail_setting' value='1' type='checkbox' <?php echo $event_mail_ignore_massmail_setting; ?>><br>
                <?php esc_html_e( 'When sending a mail to all EME people or certain groups, it is by default only sent to the people who have indicated they want to receive mass mailings. If you need to send the mail to all the persons regardless their massmail setting, check this option.', 'events-made-easy' ); ?>
        </p>
        </div>
        <hr>
        <?php esc_html_e( 'Enter a test recipient', 'events-made-easy' ); ?>
        <input type="hidden" name="send_previeweventmailto_id" id="send_previeweventmailto_id" value="">
        <input type='search' id='eventmail_chooseperson' name='eventmail_chooseperson' placeholder="<?php esc_html_e( 'Start typing a name', 'events-made-easy' ); ?>">
        <button id='previeweventmailButton' class="button-primary action"> <?php esc_html_e( 'Send Preview Email', 'events-made-easy' ); ?></button>
        <div id="previeweventmail-message" class="eme-hidden" ></div>
        <hr>
        <button id='eventmailButton' class="button-primary action"> <?php esc_html_e( 'Send email', 'events-made-easy' ); ?></button>
<?php
        if ( ! $eme_queue_mails ) {
?>
            <div class='eme-message-admin'><p>
<?php
            esc_html_e( 'Warning: using this functionality to send mails to attendees can result in a php timeout, so not everybody will receive the mail then. This depends on the number of attendees, the load on the server, ... . If this happens, activate and configure mail queueing.', 'events-made-easy' );
?>
                </p></div>
<?php
        } elseif ( $eme_queue_mails && ! $eme_queue_mails_configured ) {
?>
            <div class='eme-message-admin'><p>
<?php
            printf( __( 'Email queueing has been activated but not scheduled. Go in the <a href="%s">Email settings</a> and select a schedule or make sure to run the registered REST API call from system cron with the appropriate options to process the queue.', 'events-made-easy' ), admin_url( 'admin.php?page=eme-options&tab=mail' ) );
?>
                </p></div>
<?php
        }
?>
    </div>
    </form>
    <div id="eventmail-message" class="eme-hidden" ></div>
</div>

<div class="eme-tab-content" id="tab-genericmails">
    <h1><?php esc_html_e( 'Send generic emails', 'events-made-easy' ); ?></h1>
    <?php esc_html_e( "Use the below form to send a generic mail. Don't forget to use the #_UNSUB_URL for unsubscribe possibility.", 'events-made-easy' ); ?>
    <form id='send_generic_mail' name='send_generic_mail' action="#" method="post" onsubmit="return false;">
        <div class="form-field">
        <b><?php esc_html_e( 'Target audience:', 'events-made-easy' ); ?></b><br>
        <label for='eme_send_all_people'><?php esc_html_e( 'Send to all EME people', 'events-made-easy' ); ?></label>
        <input id="eme_send_all_people" name='eme_send_all_people' value='1' type='checkbox' <?php echo $send_to_all_people_checked; ?>><br>
        <div id='div_eme_send_all_people'>
<?php
        esc_html_e( 'Deselect this to select specific groups and/or memberships for your mailing', 'events-made-easy' );
        $memberships = eme_get_memberships();
?>
        </div>
        <div id='div_eme_send_groups'><table class='widefat'>
        <tr><td width='20%' class="eme-wsnobreak">
<?php
        $label      = eme_esc_html( 'Send to a number of people', 'events-made-easy' );
        $aria_label = 'aria-label="' . $label . '"';
        echo $label;
?>
        </td>
                <td><?php echo eme_ui_multiselect( $person_ids, 'eme_genericmail_send_persons', $mygroups, 5, '', 0, 'eme_select2_people_class', $aria_label ); ?></td></tr>
        <tr><td width='20%' class="eme-wsnobreak">
<?php
        $label      = eme_esc_html( 'Send to a number of groups', 'events-made-easy' );
        $aria_label = 'aria-label="' . $label . '"';
        echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect_key_value( $persongroup_ids, 'eme_genericmail_send_peoplegroups', $peoplegroups, 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class', $aria_label ); ?></td></tr>
        <tr><td width='20%' class="eme-wsnobreak">
<?php
        $label      = eme_esc_html( 'Send to a number of members', 'events-made-easy' );
        $aria_label = 'aria-label="' . $label . '"';
        echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect( $member_ids, 'eme_send_members', $mymembergroups, 5, '', 0, 'eme_select2_members_class', $aria_label ); ?></td></tr>
        <tr><td width='20%' class="eme-wsnobreak">
<?php
        $label      = eme_esc_html( 'Send to a number of member groups', 'events-made-easy' );
        $aria_label = 'aria-label="' . $label . '"';
        echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect_key_value( $membergroup_ids, 'eme_genericmail_send_membergroups', $membergroups, 'group_id', 'name', 5, '', 0, 'eme_select2_groups_class', $aria_label ); ?></td></tr>
        <tr><td width='20%' class="eme-wsnobreak">
<?php
        $label      = eme_esc_html( 'Send to active members belonging to', 'events-made-easy' );
        $aria_label = 'aria-label="' . $label . '"';
        echo $label;
?>
        </td>
        <td><?php echo eme_ui_multiselect_key_value( $membership_ids, 'eme_send_memberships', $memberships, 'membership_id', 'name', 5, '', 0, 'eme_select2_memberships_class', $aria_label ); ?></td></tr>
        </table>
        </div>
        </div>
        <div class="form-field">
<?php
        if ( ! get_option( 'eme_mail_force_from' ) ) {
?>
        <p>
        <b><?php esc_html_e( 'Sender name', 'events-made-easy' ); ?></b><br>
        <input type="text" name="generic_mail_from_name" id="generic_mail_from_name" value="<?php echo eme_esc_html( $generic_mail_from_name ); ?>" required='required' size='40'>
        </p>
        <p>
        <b><?php esc_html_e( 'Sender email', 'events-made-easy' ); ?></b><br>
        <input type="text" name="generic_mail_from_email" id="generic_mail_from_email" value="<?php echo eme_esc_html( $generic_mail_from_email ); ?>" required='required' size='40'>
        </p>
<?php
        }
?>
        <p>
        <b><?php esc_html_e( 'Subject', 'events-made-easy' ); ?></b><br>
        <input type="text" name="generic_mail_subject" id="generic_mail_subject" value="<?php echo eme_esc_html( $generic_mail_subject ); ?>" required='required' size='40'>
        </p>
        </div>
        <div class="form-field">
        <p>
        <b><?php esc_html_e( 'Message', 'events-made-easy' ); ?></b><br>
        <?php $templates_array = eme_get_templates_array_by_id( 'mail' ); ?>
<?php
        esc_html_e( 'Either choose from a template: ', 'events-made-easy' );
        echo eme_ui_select( 0, 'generic_message_template', $templates_array );
?>
        <br>
<?php
        esc_html_e( 'Or enter your own: ', 'events-made-easy' );
?>
        </p>
<?php
        if ( get_option( 'eme_mail_send_html' ) ) {
            // for mails, let enable the full html editor
            eme_wysiwyg_textarea( 'generic_mail_message', $generic_mail_message, 1, 1 );
            if ( current_user_can( 'unfiltered_html' ) ) {
                echo "<div class='eme_notice_unfiltered_html'>";
                esc_html_e( 'Your account has the ability to post unrestricted HTML content here, except javascript.', 'events-made-easy' );
                echo '</div>';
            }
        } else {
            echo "<textarea name='generic_mail_message' id='generic_mail_message' rows='10' required='required'>" . eme_esc_html( $generic_mail_message ) . '</textarea>';
        }
?>
        </div>
        <div>
<?php
        esc_html_e( 'You can use any placeholders mentioned here:', 'events-made-easy' );
        print "<br><a href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-12-people/'>" . __( 'People placeholders', 'events-made-easy' ) . '</a> (' . __( 'for ', 'events-made-easy' ) . __( 'People or groups', 'events-made-easy' ) . ')';
        print "<br><a href='//www.e-dynamics.be/wordpress/category/documentation/7-placeholders/7-14-members/'>" . __( 'Member placeholders', 'events-made-easy' ) . '</a> (' . __( 'for ', 'events-made-easy' ) . __( 'members', 'events-made-easy' ) . ')';
        print '<br>' . __( 'You can also use any shortcode you want.', 'events-made-easy' );
?>
        </div>
            <hr>
        <div id='div_generic_mailing_attach'>
        <p>
        <b><?php esc_html_e( 'Optionally add attachments to your mailing', 'events-made-easy' ); ?></b><br>
            <span id="generic_attach_links"><?php echo $generic_mail_attach_url_string; ?></span>
            <input type="hidden" name="eme_generic_attach_ids" id="eme_generic_attach_ids" value="<?php echo $generic_mail_attachment_ids; ?>">
            <input type="button" name="generic_attach_button" id="generic_attach_button" class="button-secondary action" value="<?php esc_html_e( 'Add attachments', 'events-made-easy' ); ?>">
            <input type="button" name="generic_remove_attach_button" id="generic_remove_attach_button" class="button-secondary action" value="<?php esc_html_e( 'Remove attachments', 'events-made-easy' ); ?>">
            </p>
        </div>
<?php
        if ( $eme_queue_mails ) {
?>
        <hr>
        <div id='div_generic_mailing_definition'>
        <p>
        <b><?php esc_html_e( 'Set mailing name and start date and time', 'events-made-easy' ); ?></b><br>
                <label for='genericmail_mailing_name'><?php esc_html_e( 'Mailing name: ', 'events-made-easy' ); ?></label> <input type='text' name='genericmail_mailing_name' id='genericmail_mailing_name' value='' required='required'><br>
                <?php esc_html_e( 'Start date and time: ', 'events-made-easy' ); ?>
        <input type='hidden' name='genericmail_actualstartdate' id='genericmail_actualstartdate' value=''>
                <input type='text' readonly='readonly' name='genericmail_startdate' id='genericmail_startdate' data-date='' data-alt-field='genericmail_actualstartdate' data-multiple-dates="true" style="background: #FCFFAA;"><?php esc_html_e( 'Leave empty to send the mail immediately', 'events-made-easy' ); ?><br>
        <span id='genericmail-specificdates' class="eme_smaller"></span>
        <span id='genericmail-multidates-expl' class="eme_smaller"><?php esc_html_e( '(multiple dates can be selected, in which case the mailing will be planned on each selected date and time)', 'events-made-easy' ); ?></span>
        </p>
        </div>
        <?php } ?>
        <hr>
        <div id='div_generic_ignore_massmail_setting'>
        <p>
        <b><label for='genericmail_ignore_massmail_setting'><?php esc_html_e( 'Ignore massmail setting:', 'events-made-easy' ); ?></label></b>
                <input id="genericmail_ignore_massmail_setting" name='genericmail_ignore_massmail_setting' value='1' type='checkbox' <?php echo $generic_mail_ignore_massmail_setting; ?>><br>
                <?php esc_html_e( 'When sending a mail to all EME people or certain groups, it is by default only sent to the people who have indicated they want to receive mass mailings. If you need to send the mail to all the persons regardless their massmail setting, check this option.', 'events-made-easy' ); ?>
        </p>
        </div>
        <hr>
        <?php esc_html_e( 'Enter a test recipient', 'events-made-easy' ); ?>
        <input type="hidden" name="send_previewmailto_id" id="send_previewmailto_id" value="">
        <input type='search' id='chooseperson' name='chooseperson' placeholder="<?php esc_html_e( 'Start typing a name', 'events-made-easy' ); ?>">
        <button id='previewmailButton' class="button-primary action"> <?php esc_html_e( 'Send Preview Email', 'events-made-easy' ); ?></button>
        <div id="previewmail-message" class="eme-hidden" ></div>
        <hr>
        <button id='genericmailButton' class="button-primary action"> <?php esc_html_e( 'Send email', 'events-made-easy' ); ?></button>
<?php
            if ( ! $eme_queue_mails ) {
?>
            <div class='eme-message-admin'><p>
<?php
                esc_html_e( 'Warning: using this functionality to send mails to attendees can result in a php timeout, so not everybody will receive the mail then. This depends on the number of attendees, the load on the server, ... . If this happens, activate and configure mail queueing.', 'events-made-easy' );
?>
                </p></div>
<?php
            } elseif ( $eme_queue_mails && ! $eme_queue_mails_configured ) {
?>
            <div class='eme-message-admin'><p>
<?php
                printf( __( 'Email queueing has been activated but not scheduled. Go in the <a href="%s">Email settings</a> and select a schedule or make sure to run the registered REST API call from system cron with the appropriate options to process the queue.', 'events-made-easy' ), admin_url( 'admin.php?page=eme-options&tab=mail' ) );
?>
                </p></div>
<?php
            }
?>
    </form>
    <div id="genericmail-message" class="eme-hidden" ></div>
</div>

<div class="eme-tab-content" id="tab-testmail">
    <h1><?php esc_html_e( 'Test mail settings', 'events-made-easy' ); ?></h1>
    <div id="testmail-message" class="eme-hidden" ></div>
    <?php esc_html_e( 'Use the below form to send a test mail', 'events-made-easy' ); ?>
    <form id='send_testmail' name='send_testmail' action="#" method="post" onsubmit="return false;">
    <label for='testmail_to'><?php esc_html_e( 'Enter the recipient', 'events-made-easy' ); ?></label>
    <input type="email" name="testmail_to" id="testmail_to" value="" placeholder="<?php esc_html_e( 'Enter any valid mail address', 'events-made-easy' ); ?>">
    <button id='testmailButton' class="button-primary action"> <?php esc_html_e( 'Send Email', 'events-made-easy' ); ?></button>
    </form>
</div>

</div> <!-- wrap -->
<?php
}

function eme_mails_div() {
    if ( ! (current_user_can( get_option( 'eme_cap_manage_mails' ) ) || current_user_can( get_option( 'eme_cap_view_mails' ) ) ) ) {
        print "<div class='eme-message-admin'>";
        esc_html_e( 'Access denied!', 'events-made-easy' );
        print "</div>";
        wp_die();
    }

?>
    <h1><?php esc_html_e( 'Sent emails', 'events-made-easy' ); ?></h1>
    <div class='eme-message-admin'><p>
<?php
            $archive_old_mailings_days = get_option( 'eme_gdpr_archive_old_mailings_days' );
            if ( empty( $archive_old_mailings_days ) ) {
                esc_html_e( 'If you want to archive old mailings and clean up old mails automatically, check the option "Automatically archive old mailings and remove old mails" in the GDPR Settings of EME', 'events-made-easy' );
            } else {
                sprintf(esc_html__( 'Every %d days, old mailings will be archived and old mails will be cleaned up (see the option "Automatically archive old mailings and remove old mails" in the GDPR Settings of EME)', 'events-made-easy' ),$archive_old_mailings_days);
            }
?>
    </p></div>
    <form id='search_mail' name='search_mail' action="#" method="post" onsubmit="return false;">
    <label for='search_text'><?php esc_html_e( 'Enter an optional search text', 'events-made-easy' ); ?></label>
    <input type="search" name="search_text" id="search_text" value="">
    <input id="search_failed" name='search_failed' value='1' type='checkbox' ><label for='search_failed'><?php esc_html_e( 'Only show failed emails', 'events-made-easy' ); ?></label>
    <button id='MailsLoadRecordsButton' class="button-primary action"> <?php esc_html_e( 'Filter mails', 'events-made-easy' ); ?></button>
    </form>
    <br>
    <div id="mails-message" class="eme-hidden" ></div>
    <div>
    <form action="#" method="post">
    <select id="eme_admin_action_mails" name="eme_admin_action_mails">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="sendMails"><?php esc_html_e( 'Send generic email to selected persons', 'events-made-easy' ); ?></option>
    <option value="deleteMails"><?php esc_html_e( 'Delete selected mails', 'events-made-easy' ); ?></option>
    </select>
    <button id="MailsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </span>
    </form>
    </div>
	<div id="MailsTableContainer"></div>
<?php
}

function eme_mailings_div() {
    if ( ! (current_user_can( get_option( 'eme_cap_manage_mails' ) ) || current_user_can( get_option( 'eme_cap_view_mails' ) ) ) ) {
        print "<div class='eme-message-admin'>";
        esc_html_e( 'Access denied!', 'events-made-easy' );
        print "</div>";
        wp_die();
    }

?>
    <h1><?php esc_html_e( 'Mailings overview', 'events-made-easy' ); ?></h1>
    <div><p>
<?php
    esc_html_e( 'Here you can find an overview of all planned, ongoing or completed mailings. For an overview of all emails, check the "Sent emails" tab.', 'events-made-easy' );
?>
    </p></div>
    <div class='eme-message-admin'><p>
<?php
    $archive_old_mailings_days = get_option( 'eme_gdpr_archive_old_mailings_days' );
    if ( empty( $archive_old_mailings_days ) ) {
        esc_html_e( 'If you want to archive old mailings and clean up old mails automatically, check the option "Automatically archive old mailings and remove old mails" in the GDPR Settings of EME', 'events-made-easy' );
    } else {
        sprintf(esc_html__( 'Every %d days, old mailings will be archived and old mails will be cleaned up (see the option "Automatically archive old mailings and remove old mails" in the GDPR Settings of EME)', 'events-made-easy' ),$archive_old_mailings_days);
    }
?>
    </p></div>
<?php
    if ( ! get_option( 'eme_queue_mails' ) ) {
        print "<div class='eme-message-admin'><p>";
        esc_html_e( 'Email queueing is not activated, so sent mails will only be visible in the "Sent emails" tab', 'events-made-easy' );
        print '</p></div>';
    }
?>

    <form id='search_mailings' name='search_mailings' action="#" method="post" onsubmit="return false;">
    <label for='search_mailingstext'><?php esc_html_e( 'Enter the search text (leave empty to show all)', 'events-made-easy' ); ?></label>
    <input type="search" name="search_mailingstext" id="search_mailingstext" value="">
    <button id='MailingsLoadRecordsButton' class="button-primary action"> <?php esc_html_e( 'Filter', 'events-made-easy' ); ?></button>
    </form>
    <br>
    <div id="mailings-message" class="eme-hidden" ></div>
    <div>
    <form action="#" method="post">
    <select id="eme_admin_action_mailings" name="eme_admin_action_mailings">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="archiveMailings"><?php esc_html_e( 'Archive selected mailings', 'events-made-easy' ); ?></option>
    <option value="deleteMailings"><?php esc_html_e( 'Delete selected mailings', 'events-made-easy' ); ?></option>
    </select>
    <button id="MailingsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
	<div id="MailingsTableContainer"></div>
<?php 
}

function eme_mailings_archive_div() {
    if ( ! (current_user_can( get_option( 'eme_cap_manage_mails' ) ) || current_user_can( get_option( 'eme_cap_view_mails' ) ) ) ) {
        print "<div class='eme-message-admin'>";
        esc_html_e( 'Access denied!', 'events-made-easy' );
        print "</div>";
        wp_die();
    }
?>
    <h1><?php esc_html_e( 'Archived mailings', 'events-made-easy' ); ?></h1>
<?php
    esc_html_e( 'Here you can find an overview of all archived mailings', 'events-made-easy' );
?>

    <form id='search_mailingsarchive' name='search_mailingsarchive' action="#" method="post" onsubmit="return false;">
    <label for='search_archivedmailingstext'><?php esc_html_e( 'Enter the search text (leave empty to show all)', 'events-made-easy' ); ?></label>
    <input type="search" name="search_archivedmailingstext" id="search_archivedmailingstext" value="">
    <button id='ArchivedMailingsLoadRecordsButton' class="button-primary action"> <?php esc_html_e( 'Filter', 'events-made-easy' ); ?></button>
    </form>
    <div id="archivedmailings-message" class="eme-hidden" ></div>
    <div>
    <form action="#" method="post">
    <select id="eme_admin_action_archivedmailings" name="eme_admin_action_archivedmailings">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteArchivedMailings"><?php esc_html_e( 'Delete selected mailings', 'events-made-easy' ); ?></option>
    </select>
    <button id="ArchivedMailingsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
	<div id="ArchivedMailingsTableContainer"></div>
<?php
}

function eme_get_default_mailer_info() {
    $fromemail = '';
    $fromname = '';
    $default_sender_address = get_option( 'eme_mail_sender_address' );
    if ( eme_is_email( $default_sender_address ) ) {
        $fromemail = $default_sender_address;
        $fromname  = get_option( 'eme_mail_sender_name' );
    } else {
        $contact   = eme_get_contact();
        $fromemail = $contact->user_email;
        $fromname  = $contact->display_name;
    }
    // Still empty from, then we go further up
    if ( empty( $fromemail ) ) {
        $fromemail = get_option( 'admin_email' );
    }
    if ( empty( $fromname ) ) {
        $fromname = get_option( 'blogname' );
    }
    return [$fromname,$fromemail];
}

function eme_add_listhdrs( $mailing_id ) {
    $add_listhdrs = false;
    if (empty($mailing_id)) {
        return false;
    }
    $mailing = eme_get_mailing($mailing_id);
    if (empty($mailing)) {
        return false;
    }
    $conditions = eme_unserialize($mailing['conditions']);
    if ($conditions['action'] == 'newsletter' ||
        !empty($conditions['eme_send_all_people']) || // generic mail to all people
        (!empty($conditions['eme_mail_type']) && ( $conditions['eme_mail_type'] == 'all_people' || $conditions['eme_mail_type'] == 'all_people_not_registered' )) || // eventmail to all people
        !empty($conditions['eme_genericmail_send_peoplegroups']) || // event mail to certain groups
        !empty($conditions['eme_eventmail_send_groups']) // event mail to certain groups
    ) {
        $add_listhdrs = true;
    }
    return $add_listhdrs;
}

function eme_sub_send_mail( $lastname, $firstname, $email, $groups ) {
    [$contact_name, $contact_email] = eme_get_default_mailer_info();
    $sub_link    = eme_sub_confirm_url( $lastname, $firstname, $email, $groups );
    $sub_subject = eme_translate( get_option( 'eme_sub_subject' ) );
    $sub_body    = eme_translate( get_option( 'eme_sub_body' ) );
    $sub_body    = str_replace( '#_SUB_CONFIRM_URL', $sub_link, $sub_body );
    $sub_body    = str_replace( '#_LASTNAME', $lastname, $sub_body );
    $sub_body    = str_replace( '#_FIRSTNAME', $firstname, $sub_body );
    $sub_body    = str_replace( '#_EMAIL', $email, $sub_body );
    $full_name   = eme_format_full_name( $firstname, $lastname );
    eme_queue_fastmail( $sub_subject, $sub_body, $contact_email, $contact_name, $email, $full_name, $contact_email, $contact_name );
}

function eme_unsub_send_mail( $email, $groupids ) {
    // find persons with matching email in the mentioned groups
    $person_id = eme_get_person_by_email_in_groups( $email, $groupids );
    if ( ! empty( $person_id ) ) {
        [$contact_name, $contact_email] = eme_get_default_mailer_info();
        $unsub_link    = eme_unsub_confirm_url( $email, $groupids );
        $unsub_subject = get_option( 'eme_unsub_subject' );
        $unsub_body    = eme_translate( get_option( 'eme_unsub_body' ) );
        $unsub_body    = str_replace( '#_UNSUB_CONFIRM_URL', $unsub_link, $unsub_body );
        $person        = eme_get_person( $person_id );
        $unsub_body    = eme_replace_people_placeholders( $unsub_body, $person );
        $name          = '';
        if ( ! empty( $person['lastname'] ) ) {
            $name = $person['lastname'];
        }
        if ( ! empty( $person['firstname'] ) ) {
            $name .= ' ' . $person['firstname'];
        }
        eme_queue_fastmail( $unsub_subject, $unsub_body, $contact_email, $contact_name, $email, $name, $contact_email, $contact_name );
    }
}

function eme_unsub_send_confirmation_mail( $email ) {
    // find persons with matching email in the mentioned groups
    $person_id = eme_get_person_by_email_only( $email );
    if ( ! empty( $person_id ) ) {
        [$contact_name, $contact_email] = eme_get_default_mailer_info();
        $unsub_confirm_subject = get_option( 'eme_unsub_confirm_subject' );
        $unsub_confirm_body    = eme_translate( get_option( 'eme_unsub_confirm_body' ) );
        $person        = eme_get_person( $person_id );
        $unsub_confirm_body    = eme_replace_people_placeholders( $unsub_confirm_body, $person );
        $name          = '';
        if ( ! empty( $person['lastname'] ) ) {
            $name = $person['lastname'];
        }
        if ( ! empty( $person['firstname'] ) ) {
            $name .= ' ' . $person['firstname'];
        }
        eme_queue_fastmail( $unsub_confirm_subject, $unsub_confirm_body, $contact_email, $contact_name, $email, $name, $contact_email, $contact_name );
    }
}

function eme_sub_do( $lastname, $firstname, $email, $group_ids ) {
    $person = eme_get_person_by_name_and_email( $lastname, $firstname, $email );
    $res    = false;
    if ( ! $person ) {
        $person = eme_get_person_by_email_only( $email );
    }
    if ( empty( $group_ids ) ) {
        $group_ids = eme_get_public_groupids();
    }
    if ( ! empty( $person ) ) {
        $res = eme_add_persongroups( $person['person_id'], $group_ids, 1 );
    } else {
        $wp_id = 0;
        // if the user is logged in, we overwrite the lastname/firstname with that info
        if ( is_user_logged_in() ) {
            $wp_id     = get_current_user_id();
            $user_info = get_userdata( $wp_id );
            $lastname  = $user_info->user_lastname;
            if ( empty( $lastname ) ) {
                $lastname = $user_info->display_name;
            }
            $firstname = $user_info->user_firstname;
        }
        $res2      = eme_add_update_person_from_form( 0, $lastname, $firstname, $email, $wp_id );
        $person_id = $res2[0];
        if ( $person_id ) {
            $res = eme_add_persongroups( $person_id, $group_ids, 1 );
        }
    }
    if ( $res ) {
        eme_update_email_massmail( $email, 1 );
    }
    return $res;
}

function eme_unsub_do( $email, $group_ids ) {
    $count = 0;
    $public_groupids = eme_get_public_groupids(); // all public static groups
    if ( eme_count_persons_by_email( $email ) > 0 ) {
        if ( empty( $group_ids ) ) {
            $group_ids = $public_groupids;
            if ( wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
                $group_ids[] = -1;
            }
            eme_update_email_massmail( $email, 0 );
            $count++;
        } else {
            $group_ids = array_intersect( $group_ids, $public_groupids );
        }
        if ( ! empty( $group_ids ) ) {
            foreach ( $group_ids as $group_id ) {
                // -1 is the newsletter
                if ( $group_id == -1 ) {
                    eme_remove_email_from_newsletter( $email );
                    $count++;
                } else {
                    $group = eme_get_group( $group_id );
                    if ( ! empty( $group['public'] ) && $group['type']='static' ) {
                        eme_delete_emailfromgroup( $email, $group_id );
                        $count++;
                    }
                }
            }
        }
    }
    if ( $count ) {
        eme_unsub_send_confirmation_mail( $email );
    }
    return $count;
}

