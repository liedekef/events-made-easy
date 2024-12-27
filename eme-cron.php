<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_cron_schedules( $schedules ) {
    if ( ! isset( $schedules['eme_1min'] ) ) {
        $schedules['eme_1min'] = [
            'interval' => 60,
            'display'  => __( 'Once every minute (EME schedule)', 'events-made-easy' ),
        ];
    }
    if ( ! isset( $schedules['eme_5min'] ) ) {
        $schedules['eme_5min'] = [
            'interval' => 5 * 60,
            'display'  => __( 'Once every 5 minutes (EME schedule)', 'events-made-easy' ),
        ];
    }
    if ( ! isset( $schedules['eme_15min'] ) ) {
        $schedules['eme_15min'] = [
            'interval' => 15 * 60,
            'display'  => __( 'Once every 15 minutes (EME schedule)', 'events-made-easy' ),
        ];
    }
    if ( ! isset( $schedules['eme_30min'] ) ) {
        $schedules['eme_30min'] = [
            'interval' => 30 * 60,
            'display'  => __( 'Once every 30 minutes (EME schedule)', 'events-made-easy' ),
        ];
    }
    if ( ! isset( $schedules['eme_4weeks'] ) ) {
        $schedules['eme_4weeks'] = [
            'interval' => 60 * 60 * 24 * 28,
            'display'  => __( 'Once every 4 weeks (EME schedule)', 'events-made-easy' ),
        ];
    }
    return $schedules;
}
add_filter( 'cron_schedules', 'eme_cron_schedules' );

function eme_plan_queue_mails() {
    if ( get_option( 'eme_queue_mails' ) ) {
        $schedules = wp_get_schedules();
        // we stored the choosen schedule in the option with the same name eme_cron_send_queued
        // and take hourly as sensible default
        $schedule = get_option( 'eme_cron_send_queued' );
        if ( empty( $schedule ) ) {
            wp_unschedule_hook( 'eme_cron_send_queued' );
        } else {
            if ( ! isset( $schedules[ $schedule ] ) ) {
                $schedule = 'hourly';
                update_option( 'eme_cron_send_queued', $schedule );
            }
            if ( ! wp_next_scheduled( 'eme_cron_send_queued' ) ) {
                wp_schedule_event( time(), $schedule, 'eme_cron_send_queued' );
            } else {
                $current_schedule = wp_get_schedule( 'eme_cron_send_queued' );
                if ( $current_schedule != $schedule ) {
                    wp_unschedule_hook( 'eme_cron_send_queued' );
                    wp_schedule_event( time(), $schedule, 'eme_cron_send_queued' );
                }
            }
        }
    } elseif ( wp_next_scheduled( 'eme_cron_send_queued' ) ) {
        wp_unschedule_hook( 'eme_cron_send_queued' );
    }
}

add_action( 'eme_cron_send_new_events', 'eme_cron_send_new_events_function' );
function eme_cron_send_new_events_function() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    // no queuing active? Then no newsletter either
    if ( ! get_option( 'eme_queue_mails' ) ) {
        return;
    }

    $days = intval( get_option( 'eme_cron_new_events_days' ) );
    $scope = '+' . $days . 'd';

    // make sure no mail is sent if no events are planned
    $check_for_events = eme_are_events_available( $scope );
    if ( ! $check_for_events ) {
        return;
    }

    $mail_subject = eme_get_template_format_plain( get_option( 'eme_cron_new_events_subject' ) );
    $header       = eme_get_template_format_plain( get_option( 'eme_cron_new_events_header' ) );
    $entry        = eme_get_template_format_plain( get_option( 'eme_cron_new_events_entry' ) );
    $footer       = eme_get_template_format_plain( get_option( 'eme_cron_new_events_footer' ) );

    $limit = 0;
    $no_events_message = '';
    $mail_message = eme_get_events_list( limit: $limit, scope: $scope, format: $entry, format_header: $header, format_footer: $footer, no_events_message: $no_events_message);
    // thanks to no_events_message being empty, in case no events are found the result is empty and then we don't send a mail
    // the call eme_are_events_available checks for just scope, but inside the entry-format there can be conditional placeholders too resulting in an empty mail
    if ( empty($mail_message) ) {
        return;
    }

    $person_ids           = eme_get_newsletter_person_ids();
    $mail_text_html       = get_option( 'eme_mail_send_html' ) ? 'htmlmail' : 'text';
    $contact              = eme_get_event_contact();
    $contact_email        = $contact->user_email;
    $contact_name         = $contact->display_name;

    // we'll create a mailing for the newsletter, so we can delete/cancel if easily while ongoing too
    $eme_date_obj     = new ExpressiveDate( 'now', EME_TIMEZONE );
    $mailing_datetime = $eme_date_obj->getDateTime();
    $mailing_name     = "newsletter $mailing_datetime";
    $mailing_id       = eme_db_insert_ongoing_mailing( $mailing_name, $mail_subject, $mail_message, $contact_email, $contact_name, $contact_email, $contact_name, $mail_text_html );
    // even if we fail to create a mailing, we'll continue
    if ( ! $mailing_id ) {
        $mailing_id = 0;
    }

    foreach ( $person_ids as $person_id ) {
        $person      = eme_get_person( $person_id );
        $tmp_message = eme_replace_people_placeholders( $mail_message, $person, $mail_text_html );
        $person_name = eme_format_full_name( $person['firstname'], $person['lastname'] );
        eme_queue_mail( $mail_subject, $tmp_message, $contact_email, $contact_name, $person['email'], $person_name, $contact_email, $contact_name, $mailing_id, $person_id );
    }
}

// add an action for the cronjob to map to the cleanup function,
add_action( 'eme_cron_cleanup_actions', 'eme_cron_cleanup_function' );
function eme_cron_cleanup_function() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    $eme_number = intval( get_option( 'eme_cron_cleanup_unpaid_minutes' ) );
    if ( $eme_number > 0 ) {
        eme_cleanup_unpaid( $eme_number );
    }

    $eme_number = intval( get_option( 'eme_cron_cleanup_unconfirmed_minutes' ) );
    if ( $eme_number > 0 ) {
        eme_cleanup_unconfirmed( $eme_number );
    }

    if ( get_option( 'eme_captcha_for_forms' ) ) {
        $tmp_dir = get_temp_dir();
        foreach ( glob( $tmp_dir . 'eme_captcha_*' ) as $file ) {
            // delete captcha files older than 30 minutes
            if ( time() - filemtime( $file ) > 1800 ) {
                wp_delete_file( $file );
            }
        }
    }
}

add_action( 'eme_cron_send_queued', 'eme_cron_send_queued' );
function eme_cron_send_queued() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    if ( get_option( 'eme_queue_mails' ) ) {
        eme_send_queued();
    }
}

add_action( 'eme_cron_member_daily_actions', 'eme_cron_member_daily_actions' );
function eme_cron_member_daily_actions() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    eme_member_recalculate_status();
    eme_member_send_expiration_reminders();
    eme_member_remove_pending();
    if ( has_action( 'eme_members_daily_action' ) ) {
        do_action( 'eme_members_daily_action' );
    }
}

add_action( 'eme_cron_events_daily_actions', 'eme_cron_events_daily_actions' );
function eme_cron_events_daily_actions() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    eme_tasks_send_signup_reminders();
    eme_todos_send_reminders();
    eme_rsvp_send_pending_reminders();
    eme_rsvp_send_approved_reminders();
    $recurrences = eme_get_perpetual_recurrences();
    foreach ($recurrences as $recurrence) {
        $event = eme_get_event( eme_get_recurrence_first_eventid( $recurrence['recurrence_id'] ) );
        // we add the 1 as param so eme_db_update_recurrence knows it is for changing dates only and can skip some things
        eme_db_update_recurrence( $recurrence, $event, 1 );

    }
    if ( has_action( 'eme_events_daily_action' ) ) {
        do_action( 'eme_events_daily_action' );
    }
}

add_action( 'eme_cron_gdpr_daily_actions', 'eme_cron_gdpr_daily_actions' );
function eme_cron_gdpr_daily_actions() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    eme_member_anonymize_expired();
    eme_member_remove_expired();
    eme_rsvp_anonymize_old_bookings();
    eme_delete_old_events();
    eme_tasks_remove_old_signups();
    eme_archive_old_mailings();
    eme_delete_old_attendances();
}

add_action( 'eme_cron_daily_actions', 'eme_cron_daily_actions' );
function eme_cron_daily_actions() {
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        return;
    }
    eme_people_birthday_emails();
    if ( has_action( 'eme_daily_action' ) ) {
        do_action( 'eme_daily_action' );
    }
}

function eme_cron_page() {
    $message = '';
    if ( current_user_can( get_option( 'eme_cap_settings' ) ) ) {
        // do the actions if required
        if ( isset( $_POST['eme_admin_action'] ) ) {
            check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
            if ( $_POST['eme_admin_action'] == 'eme_cron_cleanup_unpaid' ) {
                $eme_cron_cleanup_unpaid_minutes = intval( $_POST['eme_cron_cleanup_unpaid_minutes'] );
                if ( $eme_cron_cleanup_unpaid_minutes >= 5 ) {
                    update_option( 'eme_cron_cleanup_unpaid_minutes', $eme_cron_cleanup_unpaid_minutes );
                    $message = sprintf( __( 'Scheduled the cleanup of unpaid pending bookings older than %d minutes', 'events-made-easy' ), $eme_cron_cleanup_unpaid_minutes );
                } else {
                    update_option( 'eme_cron_cleanup_unpaid_minutes', 0 );
                    $message = __( 'No automatic cleanup of unpaid pending bookings will be done.', 'events-made-easy' );
                }
            } elseif ( $_POST['eme_admin_action'] == 'eme_cron_cleanup_unconfirmed' ) {
                $eme_cron_cleanup_unconfirmed_minutes = intval( $_POST['eme_cron_cleanup_unconfirmed_minutes'] );
                if ( $eme_cron_cleanup_unconfirmed_minutes >= 5 ) {
                    update_option( 'eme_cron_cleanup_unconfirmed_minutes', $eme_cron_cleanup_unconfirmed_minutes );
                    $message = sprintf( __( 'Scheduled the cleanup of unconfirmed bookings older than %d minutes', 'events-made-easy' ), $eme_cron_cleanup_unconfirmed_minutes );
                } else {
                    update_option( 'eme_cron_cleanup_unconfirmed_minutes', 0 );
                    $message = __( 'No automatic cleanup of unconfirmed bookings will be done.', 'events-made-easy' );
                }
            } elseif ( $_POST['eme_admin_action'] == 'eme_cron_send_new_events' ) {
                $eme_cron_new_events_schedule = eme_sanitize_request($_POST['eme_cron_new_events_schedule']);
                $eme_cron_new_events_days     = intval( $_POST['eme_cron_new_events_days'] );
                $eme_cron_new_events_subject  = intval( $_POST['eme_cron_new_events_subject'] );
                $eme_cron_new_events_header   = intval( $_POST['eme_cron_new_events_header'] );
                $eme_cron_new_events_entry    = intval( $_POST['eme_cron_new_events_entry'] );
                $eme_cron_new_events_footer   = intval( $_POST['eme_cron_new_events_footer'] );
                if ( wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
                    $schedule = wp_get_schedule( 'eme_cron_send_new_events' );
                    if ( $schedule != $eme_cron_new_events_schedule ) {
                        wp_unschedule_hook( 'eme_cron_send_new_events' );
                        delete_option( 'eme_cron_send_new_events' );
                    }
                }
                if ( $eme_cron_new_events_days > 0 ) {
                    if ( $eme_cron_new_events_schedule ) {
                        $schedules = wp_get_schedules();
                        if ( isset( $schedules[ $eme_cron_new_events_schedule ] ) ) {
                            $new_events_schedule = $schedules[ $eme_cron_new_events_schedule ];
                            if ( ! wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
                                wp_schedule_event( time(), $eme_cron_new_events_schedule, 'eme_cron_send_new_events' );
                            }
                            update_option( 'eme_cron_send_new_events', $eme_cron_new_events_schedule );
                            update_option( 'eme_cron_new_events_days', $eme_cron_new_events_days );
                            update_option( 'eme_cron_new_events_subject', $eme_cron_new_events_subject );
                            update_option( 'eme_cron_new_events_header', $eme_cron_new_events_header );
                            update_option( 'eme_cron_new_events_entry', $eme_cron_new_events_entry );
                            update_option( 'eme_cron_new_events_footer', $eme_cron_new_events_footer );
                        }
                    } else {
                        $message = __( 'New events will not be mailed to EME registered people.', 'events-made-easy' );
                    }
                } else {
                    $message = __( 'New events will not be mailed to EME registered people.', 'events-made-easy' );
                }
            }
        }
    }

    eme_cron_form( $message );
}

function eme_cron_form( $message = '' ) {
    $schedules = wp_get_schedules();
?>
<div class="wrap">
<div id="icon-events" class="icon32"></div>
<h1><?php esc_html_e( 'Scheduled actions', 'events-made-easy' ); ?></h1>

    <?php if ( $message != '' ) { ?>
    <div id='message' class='updated eme-message-admin'>
    <p><?php echo $message; ?></p>
    </div>
<?php
    } else {
        if ( ! defined( 'DISABLE_WP_CRON' ) || ( defined( 'DISABLE_WP_CRON' ) && ! DISABLE_WP_CRON ) ) {
            echo "<div id='message' class='updated eme-message-admin'><p>";
            esc_html_e( 'Cron tip for more accurate scheduled actions:', 'events-made-easy' );
            echo '<br>';
            esc_html_e( 'Put something like this in the crontab of your server:', 'events-made-easy' );
            echo '<br>';
            echo '<code>*/5 * * * * wget -q -O - ' . site_url( '/wp-cron.php' ) . ' >/dev/null 2>&1 </code><br>';
            esc_html_e( 'And add the following to your wp-config.php:', 'events-made-easy' );
            echo '<br>';
            echo "<code>define('DISABLE_WP_CRON', true);</code>";
            echo '</p></div>';
        }

        if (get_option( 'eme_queue_mails' ) && ! wp_next_scheduled( 'eme_cron_send_queued' )) {
            echo "<div id='message' class='updated eme-message-admin'><p>";
            esc_html_e("Mail queueing is active but the mail queue is not scheduled to be processed. Make sure to either configure a schedule or run the registered REST API call from system cron with the appropriate options in order to process the queue.", 'events-made-easy' );
            echo '<br>';
            esc_html_e( 'Put something like this in the crontab of your server:', 'events-made-easy' );
            echo '<br>';
            echo '<code>*/5 * * * * curl --user "username:password" ' . site_url( '/wp-json/events-made-easy/v1/processqueue/60' ) . ' >/dev/null 2>&1 </code><br>';
            esc_html_e( 'Change the "username" by your user and the "password" by an application password generated in your WP user settings.', 'events-made-easy' );
            echo '<br>';
            esc_html_e( '"60" means the script can run at most for 55 seconds (=60-5, 5 being a safety measure). Never set this higher than your cron recurrence of course', 'events-made-easy' );
            echo '</p></div>';
        }
    }
?>
<?php
    // if not data master, then don't do this
    if ( ! eme_is_datamaster() ) {
        echo "<div id='message' class='updated eme-message-admin'><p>";
        esc_html_e( 'EME multisite data sharing is active, this instance will not do any planned actions. All planned actions will be executed from the main site.', 'events-made-easy' );
        echo '</p></div>';
        return;
    }
?>
    <h2><?php esc_html_e( 'Planned cleanup actions', 'events-made-easy' ); ?></h2>
    <form action="" method="post">
    <label for="eme_cron_cleanup_unpaid_minutes">
<?php
    echo wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    $minutes = intval( get_option( 'eme_cron_cleanup_unpaid_minutes' ) );
    esc_html_e( 'Schedule the automatic removal of unpaid pending bookings older than', 'events-made-easy' );
?>
    </label>
    <input type="number" id="eme_cron_cleanup_unpaid_minutes" name="eme_cron_cleanup_unpaid_minutes" size="6" maxlength="6" min="0" max="999999" step="5" value="<?php echo $minutes; ?>">
    <?php esc_html_e( '(value is in minutes, leave empty or 0 to disable the scheduled cleanup)', 'events-made-easy' ); ?>
    <input type='hidden' name='eme_admin_action' value='eme_cron_cleanup_unpaid'>
    <input type="submit" value="<?php esc_html_e( 'Apply', 'events-made-easy' ); ?>" name="doaction" id="eme_doaction" class="button-primary action">
    </form>
    <form action="" method="post">
    <label for="eme_cron_cleanup_unconfirmed_minutes">
<?php
    echo wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    $minutes = intval( get_option( 'eme_cron_cleanup_unconfirmed_minutes' ) );
    esc_html_e( 'Schedule the automatic removal of unconfirmed bookings older than', 'events-made-easy' );
?>
    </label>
    <input type="number" id="eme_cron_cleanup_unconfirmed_minutes" name="eme_cron_cleanup_unconfirmed_minutes" size="6" maxlength="6" min="0" max="999999" step="5" value="<?php echo $minutes; ?>">
    <?php esc_html_e( '(value is in minutes, leave empty or 0 to disable the scheduled cleanup)', 'events-made-easy' ); ?>
    <input type='hidden' name='eme_admin_action' value='eme_cron_cleanup_unconfirmed'>
    <input type="submit" value="<?php esc_html_e( 'Apply', 'events-made-easy' ); ?>" name="doaction" id="eme_doaction" class="button-primary action">
    </form>
<br>
<hr>
    <h2><?php esc_html_e( 'Email queue info', 'events-made-easy' ); ?></h2>
<?php
    $eme_queued_count = eme_get_queued_count();
    if ( $eme_queued_count > 1 ) {
        echo sprintf( __( 'There are %d messages in the mail queue.', 'events-made-easy' ), $eme_queued_count );
    } elseif ( $eme_queued_count ) {
        esc_html_e( 'There is 1 message in the mail queue.', 'events-made-easy' );
    } else {
        esc_html_e( 'There are no messages in the mail queue.', 'events-made-easy' );
    }

    if ( $eme_queued_count && ( ! get_option( 'eme_queue_mails' ) || ! wp_next_scheduled( 'eme_cron_send_queued' ) ) ) {
        echo '<br>';
        if ( ! get_option( 'eme_queue_mails' ) ) {
            esc_html_e( 'WARNING: messages found in the queue but the mail queue is not activated, so they will not be sent out. Make sure to run the registered REST API call from system cron with the appropriate options in order to process the queue.', 'events-made-easy' );
        } else {
            esc_html_e( 'WARNING: messages found in the queue but the mail queue is not scheduled to be processed. Make sure to run the registered REST API call from system cron with the appropriate options in order to process the queue.', 'events-made-easy' );
        }
    } else {
        $eme_cron_send_queued_schedule = wp_get_schedule( 'eme_cron_send_queued' );
        if ( isset( $schedules[ $eme_cron_send_queued_schedule ] ) ) {
            $eme_cron_queue_count = intval(get_option( 'eme_cron_queue_count' ) );
            $schedule = $schedules[ $eme_cron_send_queued_schedule ];
            echo '<br>';
            if ($eme_cron_queue_count > 0 ) {
                echo sprintf( esc_html__( 'Queued mails will be send out in batches of %d %s', 'events-made-easy' ), get_option( 'eme_cron_queue_count' ), $schedule['display'] );
            } else {
                echo sprintf( esc_html__( 'All queued mails will be send out without limit %s.', 'events-made-easy' ), $schedule['display'] );
            }
        }
    }

?>

<br><br>

<?php
    if ( get_option( 'eme_queue_mails' ) ) {
?>
<hr>
    <h2><?php esc_html_e( 'Newsletter', 'events-made-easy' ); ?></h2>
    <form action="" method="post">
<?php
        echo wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
        $days                = intval( get_option( 'eme_cron_new_events_days' ) );
        $subject             = intval( get_option( 'eme_cron_new_events_subject' ) );
        $header              = intval( get_option( 'eme_cron_new_events_header' ) );
        $entry               = intval( get_option( 'eme_cron_new_events_entry' ) );
        $footer              = intval( get_option( 'eme_cron_new_events_footer' ) );
        esc_html_e( 'Send a mail to all EME registered people for upcoming events that will happen in the next', 'events-made-easy' );
?>
    <input type="number" id="eme_cron_new_events_days" name="eme_cron_new_events_days" size="6" maxlength="6" min="0" max="999999" step="1" value="<?php echo $days; ?>"><?php esc_html_e( 'days', 'events-made-easy' ); ?><br>
        <?php $templates_array = eme_get_templates_array_by_id( 'rsvpmail' ); ?>
<?php
        esc_html_e( 'Email subject template', 'events-made-easy' );
        echo eme_ui_select( $subject, 'eme_cron_new_events_subject', $templates_array );
?>
        <br>
<?php
        esc_html_e( 'Email body header', 'events-made-easy' );
        echo eme_ui_select( $header, 'eme_cron_new_events_header', $templates_array );
?>
        <br>
<?php
        esc_html_e( 'Email body single event entry', 'events-made-easy' );
        echo eme_ui_select( $entry, 'eme_cron_new_events_entry', $templates_array );
?>
        <br>
<?php
        esc_html_e( 'Email body footer', 'events-made-easy' );
        echo eme_ui_select( $footer, 'eme_cron_new_events_footer', $templates_array );
?>
        <br>
    <input type='hidden' name='eme_admin_action' value='eme_cron_send_new_events'>
    <br>
    <select name="eme_cron_new_events_schedule">
    <option value=""><?php esc_html_e( 'Not scheduled', 'events-made-easy' ); ?></option>
<?php
        $scheduled = wp_get_schedule( 'eme_cron_send_new_events' );
	$new_events_schedule = $schedules[ $scheduled ];
        foreach ( $schedules as $key => $schedule ) {
            $selected = ( $key == $scheduled ) ? 'selected="selected"' : '';
            print "<option $selected value='$key'>" . $schedule['display'] . '</option>';
        }
?>
    </select>
    <input type="submit" value="<?php esc_html_e( 'Apply', 'events-made-easy' ); ?>" name="doaction" id="eme_doaction" class="button-primary action">
    </form>
    <br>
<?php
        $eme_cron_queue_count     = intval(get_option( 'eme_cron_queue_count' ));
        $eme_cron_queued_schedule = wp_get_schedule( 'eme_cron_send_queued' );
        if (!empty($eme_cron_queued_schedule)) {
            $mail_schedule = $schedules[ $eme_cron_queued_schedule ];
            if ( $eme_cron_queue_count> 0 ) {
                echo sprintf( __( '%s there will be a check if new events should be mailed to EME registered people (those will then be queued and send out in batches of %d %s)', 'events-made-easy' ), $new_events_schedule['display'], $eme_cron_queue_count, $mail_schedule['display'] );
            } else {
                echo sprintf( __( '%s there will be a check if new events should be mailed to EME registered people (those will then be queued and send out all at once %s)', 'events-made-easy' ), $new_events_schedule['display'], $mail_schedule['display'] );
            }
        } else {
            if ( $eme_cron_queue_count> 0 ) {
                echo sprintf( __( '%s there will be a check if new events should be mailed to EME registered people (those will then be queued and send out in batches of %d every time the queue is processed via the REST API call)', 'events-made-easy' ), $new_events_schedule['display'], $eme_cron_queue_count );
            } else {
                echo sprintf( __( '%s there will be a check if new events should be mailed to EME registered people (those will then be queued and send out all at once every time the queue is processed via the REST API call)', 'events-made-easy' ), $new_events_schedule['display'], $mail_schedule['display'] );
            }
        }
    } else {
        echo '<br>';
        esc_html_e( 'Email queueing is not activated.', 'events-made-easy' );
        echo '<br>';
        esc_html_e( 'Because mail queueing is not activated, the newsletter functionality is not available.', 'events-made-easy' );
    }
?>


</div>
<?php
}

?>
