<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_import_export_open_csv_stream( $filename, $delimiter = null ) {
    if ( $delimiter === null ) {
        $delimiter = get_option( 'eme_csv_delimiter' );
        if ( eme_is_empty_string( $delimiter ) ) {
            $delimiter = ';';
        }
    }
    header( 'Content-type: text/csv; charset=UTF-8' );
    header( 'Content-Encoding: UTF-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    eme_nocache_headers();
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV export
    $out = fopen( 'php://output', 'w' );
    // UTF-8 BOM, otherwise Excel doesn't show non-ASCII characters correctly
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CSV export
    fwrite( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
    return [ $out, $delimiter ];
}

function eme_import_page() {
    global $wpdb;
    $message = '';

    $data_forced_tab = '';
    if ( isset( $_POST['eme_admin_action'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        $action = eme_sanitize_request( $_POST['eme_admin_action'] );

        if ( $action == 'import_events' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_events();
            $data_forced_tab = 'data-showtab="tab-import-events"';
        } elseif ( $action == 'import_people' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            if ( current_user_can( get_option( 'eme_cap_edit_people' ) ) ) {
                $message = eme_import_csv_people();
            } else {
                $message = eme_message_error_div( __( 'You have no right to update people!', 'events-made-easy' ) );
            }
            $data_forced_tab = 'data-showtab="tab-import-people"';
        } elseif ( $action == 'import_members' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
                $message = eme_import_csv_members();
            } else {
                $message = eme_message_error_div( __( 'You have no right to manage members!', 'events-made-easy' ) );
            }
            $data_forced_tab = 'data-showtab="tab-import-members"';
        } elseif ( $action == 'import_members_dynamic_answers' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            if ( current_user_can( get_option( 'eme_cap_edit_members' ) ) ) {
                $message = eme_import_csv_member_dynamic_answers();
            } else {
                $message = eme_message_error_div( __( 'You have no right to manage members!', 'events-made-easy' ) );
            }
            $data_forced_tab = 'data-showtab="tab-import-members"';
        } elseif ( $action == 'import_locations' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_locations();
            $data_forced_tab = 'data-showtab="tab-import-locations"';
        } elseif ( $action == 'do_importdiscounts' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_discounts();
            $data_forced_tab = 'data-showtab="tab-import-discounts"';
        } elseif ( $action == 'do_importdgroups' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_discountgroups();
            $data_forced_tab = 'data-showtab="tab-import-discounts"';
        } elseif ( $action == 'import_payments' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_payments();
            $data_forced_tab = 'data-showtab="tab-import-payments"';
        } elseif ( $action == 'do_importcountries' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_countries();
            $data_forced_tab = 'data-showtab="tab-import-countries"';
        } elseif ( $action == 'do_importstates' && isset( $_FILES['eme_csv'] ) && current_user_can( get_option( 'eme_cap_cleanup' ) ) ) {
            $message = eme_import_csv_states();
            $data_forced_tab = 'data-showtab="tab-import-countries"';
        }
    }

    if ( ! empty( $message ) ) {
        $hidden_class = '';
    } else {
        $hidden_class = 'eme-hidden';
    }
?>

    <div class="wrap nosubsub">
    <div id="poststuff">
        <h1><?php esc_html_e( 'Import/Export Data', 'events-made-easy' ); ?></h1>
        <div id="import-message" class="notice notice-success is-dismissible <?php echo esc_attr( $hidden_class ); ?>">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
        <div class="eme-tabs" <?php echo $data_forced_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded data attribute string ?>>
        <div class="eme-tab" data-tab="tab-import-events"><?php esc_html_e( 'Events', 'events-made-easy' ); ?></div>
        <div class="eme-tab" data-tab="tab-import-people"><?php esc_html_e( 'People', 'events-made-easy' ); ?></div>
        <?php if ( get_option( 'eme_members_enabled' ) ) { ?>
        <div class="eme-tab" data-tab="tab-import-members"><?php esc_html_e( 'Members', 'events-made-easy' ); ?></div>
        <?php } ?>
        <div class="eme-tab" data-tab="tab-import-locations"><?php esc_html_e( 'Locations', 'events-made-easy' ); ?></div>
        <?php if ( get_option( 'eme_rsvp_enabled' ) ) { ?>
        <div class="eme-tab" data-tab="tab-import-discounts"><?php esc_html_e( 'Discounts', 'events-made-easy' ); ?></div>
        <div class="eme-tab" data-tab="tab-import-payments"><?php esc_html_e( 'Payments', 'events-made-easy' ); ?></div>
        <?php } ?>
        <div class="eme-tab" data-tab="tab-import-countries"><?php /* translators: "state" refers to a geographical region (e.g., province, canton, department) */ esc_html_e( 'Countries/States', 'events-made-easy' ); ?></div>
        </div>

        <!-- ==================== EVENTS TAB ==================== -->
        <div class="eme-tab-content" id="tab-import-events">
        <h2><?php esc_html_e( 'Import Events', 'events-made-easy' ); ?></h2>
        <form id="events-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <br>
            <label><input type="checkbox" name="auto_create_categories" value="1"> <?php esc_html_e( 'Auto-create categories from category_names that don\'t exist yet (otherwise unknown category names are skipped)', 'events-made-easy' ); ?></label>
            <input type="hidden" name="eme_admin_action" value="import_events">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import events from a CSV file. Required column: event_name. If providing categories, use the column category_names (pipe-separated, e.g. "Music||Concerts"). Optional: the columns event_taskslist and event_todoslist contain tasks/todos as json (as generated by the events export), when present they fully replace the existing tasks/todos of the event; if not present they are left untouched.', 'events-made-easy' ); ?></p>
        <h2><?php esc_html_e( 'Export Events', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_events' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all events as CSV', 'events-made-easy' ); ?>
        </a>
        </p>
        </div>

        <!-- ==================== PEOPLE TAB ==================== -->
        <div class="eme-tab-content" id="tab-import-people">
        <h2><?php esc_html_e( 'Import People', 'events-made-easy' ); ?></h2>
        <form id="people-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="import_people">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import people from a CSV file.', 'events-made-easy' ); ?></p>
        <h2><?php esc_html_e( 'Export People', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_people' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all people as CSV', 'events-made-easy' ); ?>
        </a>
        </p>
        </div>

        <!-- ==================== MEMBERS TAB ==================== -->
        <?php if ( get_option( 'eme_members_enabled' ) ) { ?>
        <div class="eme-tab-content" id="tab-import-members">
        <h2><?php esc_html_e( 'Import Members', 'events-made-easy' ); ?></h2>
        <form id="member-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="import_members">
            <input type="submit" value="<?php esc_attr_e( 'Import members', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import members from a CSV file.', 'events-made-easy' ); ?></p>

        <h2><?php esc_html_e( 'Import Dynamic Field Answers', 'events-made-easy' ); ?></h2>
        <form id="member-import-answers" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="import_members_dynamic_answers">
            <input type="submit" value="<?php esc_attr_e( 'Import dynamic field answers', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Once you finished importing members, use this to import dynamic field answers into the database.', 'events-made-easy' ); ?></p>
        </div>
        <?php } ?>

        <!-- ==================== LOCATIONS TAB ==================== -->
        <div class="eme-tab-content" id="tab-import-locations">
        <h2><?php esc_html_e( 'Import Locations', 'events-made-easy' ); ?></h2>
        <form id="location-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="import_locations">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import locations from a CSV file. Required columns: location_name, location_address1, location_city. If providing categories, use the column category_names (pipe-separated, e.g. "Music||Concerts").', 'events-made-easy' ); ?></p>
        <p><i><?php esc_html_e( 'The columns location_latitude and location_longitude are optional, but if present they need to be valid numeric values (e.g. 50.8503 or 4.3517), otherwise they are ignored.', 'events-made-easy' ); ?></i></p>
<?php
    $locations_table = EME_DB_PREFIX . EME_LOCATIONS_TBNAME;
    $pending_coords   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $locations_table WHERE location_latitude IS NULL OR location_longitude IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
?>
        <p><?php esc_html_e( 'Imported locations without valid coordinates in the CSV are saved without them; use the button below to resolve them afterwards via the public nominatim.openstreetmap.org geocoding service (throttled to 1 request/second, so this can take a while for many locations. The resolving stops if you leave this page, when you come back and want to continue press the button again.', 'events-made-easy' ); ?></p>
        <button type="button" id="resolve-coords-button" class="button-secondary"><?php esc_html_e( 'Resolve missing coordinates', 'events-made-easy' ); ?> (<span id="resolve-coords-pending"><?php echo esc_html( $pending_coords ); ?></span>)</button>
        <span id="resolve-coords-progress"></span>
        <h2><?php esc_html_e( 'Export Locations', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_locations' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all locations as CSV', 'events-made-easy' ); ?>
        </a>
        </p>
        </div>

        <!-- ==================== DISCOUNTS TAB ==================== -->
        <?php if ( get_option( 'eme_rsvp_enabled' ) ) { ?>
        <div class="eme-tab-content" id="tab-import-discounts">
        <h2><?php esc_html_e( 'Import Discounts', 'events-made-easy' ); ?></h2>
        <form id="discount-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="do_importdiscounts">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import discounts from a CSV file.', 'events-made-easy' ); ?></p>
        <h2><?php esc_html_e( 'Export Discounts', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_discounts' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all discounts as CSV', 'events-made-easy' ); ?>
        </a>
        </p>

        <hr>

        <h2><?php esc_html_e( 'Import Discount Groups', 'events-made-easy' ); ?></h2>
        <form id="discountgroups-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="do_importdgroups">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import discount groups from a CSV file.', 'events-made-easy' ); ?></p>
        <h2><?php esc_html_e( 'Export Discount Groups', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_dgroups' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all discount groups as CSV', 'events-made-easy' ); ?>
        </a>
        </p>
        </div>

        <!-- ==================== PAYMENTS TAB ==================== -->
        <div class="eme-tab-content" id="tab-import-payments">
        <h2><?php esc_html_e( 'Import Payments', 'events-made-easy' ); ?></h2>
        <form id="payment-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="import_payments">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import booking payments from a CSV file.', 'events-made-easy' ); ?></p>
        </div>
        <?php } ?>

        <!-- ==================== COUNTRIES/STATES TAB ==================== -->
        <div class="eme-tab-content" id="tab-import-countries">
        <h2><?php esc_html_e( 'Import Countries', 'events-made-easy' ); ?></h2>
        <form id="countries-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="do_importcountries">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php esc_html_e( 'Import countries from a CSV file. Required columns: alpha_2, name.', 'events-made-easy' ); ?></p>
        <h2><?php esc_html_e( 'Export Countries', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_countries' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php esc_html_e( 'Download all countries as CSV', 'events-made-easy' ); ?>
        </a>
        </p>

        <hr>

        <h2><?php /* translators: "state" refers to a geographical region (e.g., province, canton, department) */ esc_html_e( 'Import States', 'events-made-easy' ); ?></h2>
        <form id="states-import" method="post" enctype="multipart/form-data" action="#">
            <?php wp_nonce_field( 'eme_admin', 'eme_admin_nonce' ); ?>
            <input type="file" name="eme_csv" required='required'>
            <?php esc_html_e( 'Delimiter:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="delimiter" value="," required="required">
            <?php esc_html_e( 'Enclosure:', 'events-made-easy' ); ?>
            <input type="text" size="1" maxlength="1" name="enclosure" value='"' required="required">
            <input type="hidden" name="eme_admin_action" value="do_importstates">
            <input type="submit" value="<?php esc_attr_e( 'Import', 'events-made-easy' ); ?>" class="button-primary action">
        </form>
        <p><?php /* translators: "state" refers to a geographical region (e.g., province, canton, department) */ esc_html_e( 'Import states from a CSV file. Required columns: code, name, and either country_id (only valid on this same site) or country_alpha2 (portable across sites; optionally add country_lang to disambiguate).', 'events-made-easy' ); ?></p>
        <h2><?php /* translators: "state" refers to a geographical region (e.g., province, canton, department) */ esc_html_e( 'Export States', 'events-made-easy' ); ?></h2>
        <p>
        <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=eme-import&eme_admin_action=export_states' ), 'eme_admin_export', 'eme_admin_nonce' ) ); ?>">
            <?php /* translators: "state" refers to a geographical region (e.g., province, canton, department) */ esc_html_e( 'Download all states as CSV', 'events-made-easy' ); ?>
        </a>
        </p>
        <p><i><?php esc_html_e( 'The export identifies the country by alpha_2 (and language, if relevant) rather than the internal country_id, so this file can be safely imported into a different EME install.', 'events-made-easy' ); ?></i></p>
        </div>

    </div>
    </div>
<?php
}

function eme_import_csv_countries() {
    $inserted  = 0;
    $errors    = 0;
    $error_msg = '';
    $csvMimes  = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];

    if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
        return esc_html__( 'Invalid file type. Please upload a CSV file.', 'events-made-easy' );
    }
    if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
        return __( 'Problem detected while uploading the file', 'events-made-easy' );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV import
    $handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
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
    // first line is the column headers
    $headers = array_map( 'strtolower', fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') );
    // check required columns
    if ( ! in_array( 'alpha_2', $headers ) || ! in_array( 'name', $headers ) ) {
        $message = __( 'Not all required fields present.', 'events-made-easy' );
    } else {
        while ( ( $row = fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') ) !== false ) {
            $country = array_combine( $headers, $row );
            $res     = eme_db_insert_country( $country );
            if ( $res ) {
                ++$inserted;
            } else {
                ++$errors;
                // translators: %s is the CSV row data that failed to import
                $error_msg .= '<br>' . esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
            }
        }
        // translators: %1$d is the number of successful inserts, %2$d is the number of errors
        $message = sprintf( __( 'Import finished: %1$d inserts, %2$d errors', 'events-made-easy' ), $inserted, $errors );
        if ( $errors ) {
            $message .= "<br>" . $error_msg;
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV import
    fclose( $handle );

    return $message;
}

function eme_import_csv_states() {
    $inserted  = 0;
    $errors    = 0;
    $error_msg = '';
    $csvMimes  = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];

    if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
        return esc_html__( 'Invalid file type. Please upload a CSV file.', 'events-made-easy' );
    }
    if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
        return __( 'Problem detected while uploading the file', 'events-made-easy' );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV import
    $handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
    if ( ! $handle ) {
        return __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
    }
    // BOM as a string for comparison.
    $bom = "\xef\xbb\xbf";
    if ( fgets( $handle, 4 ) !== $bom ) {
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
    $headers = array_map( 'strtolower', fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') );
    if ( ! in_array( 'code', $headers ) || ! in_array( 'name', $headers ) || ( ! in_array( 'country_id', $headers ) && ! in_array( 'country_alpha2', $headers ) ) ) {
        $message = __( 'Not all required fields present.', 'events-made-easy' );
    } else {
        while ( ( $row = fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') ) !== false ) {
            $state = array_combine( $headers, $row );
            if ( empty( $state['country_id'] ) && ! empty( $state['country_alpha2'] ) ) {
                $state['country_id'] = eme_get_country_id_by_alpha2( $state['country_alpha2'], $state['country_lang'] ?? '' );
            }
            if ( empty( $state['country_id'] ) ) {
                ++$errors;
                // translators: %s is the CSV row data that failed to import
                $error_msg .= '<br>' . esc_html( sprintf( __( 'Not imported (country not found): %s', 'events-made-easy' ), implode( ',', $row ) ) );
                continue;
            }
            $res   = eme_db_insert_state( $state );
            if ( $res ) {
                ++$inserted;
            } else {
                ++$errors;
                // translators: %s is the CSV row data that failed to import
                $error_msg .= '<br>' . esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
            }
        }
        // translators: %1$d is the number of successful inserts, %2$d is the number of errors
        $message = sprintf( __( 'Import finished: %1$d inserts, %2$d errors', 'events-made-easy' ), $inserted, $errors );
        if ( $errors ) {
            $message .= "<br>" . $error_msg;
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV import
    fclose( $handle );

    return $message;
}

function eme_import_csv_discounts() {
    $inserted  = 0;
    $errors    = 0;
    $error_msg = '';
    $csvMimes  = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];

    if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
        return esc_html__( 'Invalid file type. Please upload a CSV file.', 'events-made-easy' );
    }
    if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
        return __( 'Problem detected while uploading the file', 'events-made-easy' );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV import
    $handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
    if ( ! $handle ) {
        return __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
    }
    $bom = "\xef\xbb\xbf";
    if ( fgets( $handle, 4 ) !== $bom ) {
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
    $headers = array_map( 'strtolower', fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') );
    if ( ! in_array( 'name', $headers ) || ! in_array( 'type', $headers ) || ! in_array( 'coupon', $headers ) || ! in_array( 'value', $headers ) ) {
        $message = __( 'Not all required fields present.', 'events-made-easy' );
    } else {
        $empty_props = [];
        $empty_props = eme_init_discount_props( $empty_props );
        while ( ( $row = fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') ) !== false ) {
            $line = array_combine( $headers, $row );
            // also import properties
            foreach ( $line as $key => $value ) {
                if ( preg_match( '/^prop_(.*)$/', $key, $matches ) ) {
                    $prop = $matches[1];
                    if ( ! isset( $line['properties'] ) ) {
                        $line['properties'] = [];
                    }
                    if ( array_key_exists( $prop, $empty_props ) ) {
                        $line['properties'][ $prop ] = eme_json_decode_safe( $value );
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
                // translators: %s is the CSV row data
                $error_msg .= '<br>' . esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
            }
        }
        // translators: %1$d is the number of inserts, %2$d is the number of errors
        $message = sprintf( __( 'Import finished: %1$d inserts, %2$d errors', 'events-made-easy' ), $inserted, $errors );
        if ( $errors ) {
            $message .= "<br>" . $error_msg;
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV import
    fclose( $handle );

    return $message;
}

function eme_import_csv_discountgroups() {
    $inserted  = 0;
    $errors    = 0;
    $error_msg = '';
    $csvMimes  = [ 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' ];

    if ( empty( $_FILES['eme_csv']['name'] ) || ! in_array( $_FILES['eme_csv']['type'], $csvMimes ) ) {
        return esc_html__( 'Invalid file type. Please upload a CSV file.', 'events-made-easy' );
    }
    if ( ! is_uploaded_file( $_FILES['eme_csv']['tmp_name'] ) ) {
        return __( 'Problem detected while uploading the file', 'events-made-easy' );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV import
    $handle = fopen( $_FILES['eme_csv']['tmp_name'], 'r' );
    if ( ! $handle ) {
        return __( 'Problem accessing the uploaded the file, maybe some security issue?', 'events-made-easy' );
    }
    $bom = "\xef\xbb\xbf";
    if ( fgets( $handle, 4 ) !== $bom ) {
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
    $headers = array_map( 'strtolower', fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') );
    if ( ! in_array( 'name', $headers ) ) {
        $message = __( 'Not all required fields present.', 'events-made-easy' );
    } else {
        while ( ( $row = fgetcsv( stream: $handle, separator: $delimiter, enclosure: $enclosure, escape: '') ) !== false ) {
            $discountgroup = array_combine( $headers, $row );
            $res           = eme_db_insert_dgroup( $discountgroup );
            if ( $res ) {
                ++$inserted;
            } else {
                ++$errors;
                // translators: %s is the CSV row data
                $error_msg .= '<br>' . esc_html( sprintf( __( 'Not imported: %s', 'events-made-easy' ), implode( ',', $row ) ) );
            }
        }
        // translators: %1$d is the number of inserts, %2$d is the number of errors
        $message = sprintf( __( 'Import finished: %1$d inserts, %2$d errors', 'events-made-easy' ), $inserted, $errors );
        if ( $errors ) {
            $message .= "<br>" . $error_msg;
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV import
    fclose( $handle );

    return $message;
}

// -----------------------------------------------------------------------------------------------
// Exports
// -----------------------------------------------------------------------------------------------
// Note: Members and Payments are intentionally NOT exported here yet -- financial/membership data
// deserves an explicit decision on scope before building that, rather than assuming it's wanted.

function eme_export_csv_events() {
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'events.csv' );

    $events = eme_get_events( 0, 'all', 'ASC', 0, '', '', '', '', 1, '', 0, [], 0, 0 );

    $base_columns = [ 'event_name', 'event_status', 'event_start', 'event_end', 'event_notes', 'event_rsvp', 'price', 'currency', 'event_seats', 'event_external_ref', 'registration_requires_approval', 'registration_wp_users_only', 'event_page_title_format', 'event_single_event_format', 'event_contactperson_email_body', 'event_respondent_email_body', 'event_registration_pending_email_body', 'event_registration_updated_email_body', 'event_registration_cancelled_email_body', 'event_registration_trashed_email_body', 'event_registration_paid_email_body', 'event_registration_form_format', 'event_cancel_form_format', 'event_registration_recorded_ok_html', 'event_prefix', 'event_slug', 'event_image_url', 'event_url', 'event_taskslist', 'event_todoslist', 'category_names', 'location_name', 'location_address1', 'location_city', 'location_latitude', 'location_longitude', 'location_external_ref' ];

    $att_keys    = [];
    $prop_keys   = array_keys( eme_init_event_props() );
    $answer_keys = [];
    foreach ( $events as $event ) {
        if ( ! empty( $event['event_attributes'] ) && is_array( $event['event_attributes'] ) ) {
            $att_keys = array_unique( array_merge( $att_keys, array_keys( $event['event_attributes'] ) ) );
        }
        foreach ( eme_get_event_answers( $event['event_id'] ) as $answer ) {
            $formfield = eme_get_formfield( $answer['field_id'] );
            if ( ! empty( $formfield ) ) {
                $answer_keys[ $formfield['field_name'] ] = true;
            }
        }
    }
    $answer_keys = array_keys( $answer_keys );

    $headers = $base_columns;
    foreach ( $att_keys as $key ) {
        $headers[] = 'att_' . $key;
    }
    foreach ( $prop_keys as $key ) {
        $headers[] = 'prop_' . $key;
    }
    foreach ( $answer_keys as $key ) {
        $headers[] = 'answer_' . $key;
    }
    eme_fputcsv( $out, $headers, $delimiter );

    foreach ( $events as $event ) {
        $location = ! empty( $event['location_id'] ) ? eme_get_location( $event['location_id'] ) : [];

        // tasks and todos are exported as json, so they can be imported again
        // via the 'event_taskslist'/'event_todoslist' columns
        $tasks_arr = [];
        foreach ( eme_get_event_tasks( $event['event_id'] ) as $task ) {
            $tasks_arr[] = [
                'name'        => $task['name'],
                'description' => $task['description'],
                'task_start'  => $task['task_start'],
                'task_end'    => $task['task_end'],
                'spaces'      => $task['spaces'],
                'task_seq'    => intval( $task['task_seq'] ),
                'task_nbr'    => intval( $task['task_nbr'] ),
            ];
        }

        $todos_arr = [];
        foreach ( eme_get_event_todos( $event['event_id'] ) as $todo ) {
            $todos_arr[] = [
                'name'        => $todo['name'],
                'description' => $todo['description'],
                'todo_offset' => intval( $todo['todo_offset'] ),
                'todo_seq'    => intval( $todo['todo_seq'] ),
                'todo_nbr'    => intval( $todo['todo_nbr'] ),
            ];
        }

        $row      = [
            $event['event_name'],
            $event['event_status'],
            $event['event_start'],
            $event['event_end'],
            $event['event_notes'],
            $event['event_rsvp'],
            $event['price'],
            $event['currency'],
            $event['event_seats'],
            $event['event_external_ref'],
            $event['registration_requires_approval'],
            $event['registration_wp_users_only'],
            $event['event_page_title_format'],
            $event['event_single_event_format'],
            $event['event_contactperson_email_body'],
            $event['event_respondent_email_body'],
            $event['event_registration_pending_email_body'],
            $event['event_registration_updated_email_body'],
            $event['event_registration_cancelled_email_body'],
            $event['event_registration_trashed_email_body'],
            $event['event_registration_paid_email_body'],
            $event['event_registration_form_format'],
            $event['event_cancel_form_format'],
            $event['event_registration_recorded_ok_html'],
            $event['event_prefix'],
            $event['event_slug'],
            $event['event_image_url'],
            $event['event_url'],
            $tasks_arr ? wp_json_encode( $tasks_arr ) : '',
            $todos_arr ? wp_json_encode( $todos_arr ) : '',
            implode( '||', eme_get_event_category_names( $event['event_id'] ) ),
            $location['location_name'] ?? '',
            $location['location_address1'] ?? '',
            $location['location_city'] ?? '',
            $location['location_latitude'] ?? '',
            $location['location_longitude'] ?? '',
            $location['location_external_ref'] ?? '',
        ];

        foreach ( $att_keys as $key ) {
            $att_value = $event['event_attributes'][ $key ] ?? '';
            $row[]     = is_array( $att_value ) ? wp_json_encode( $att_value ) : $att_value;
        }
        foreach ( $prop_keys as $key ) {
            $prop_value = $event['event_properties'][ $key ] ?? '';
            $row[]      = is_array( $prop_value ) ? wp_json_encode( $prop_value ) : $prop_value;
        }
        if ( $answer_keys ) {
            $answers_by_field = [];
            foreach ( eme_get_event_answers( $event['event_id'] ) as $answer ) {
                $formfield = eme_get_formfield( $answer['field_id'] );
                if ( ! empty( $formfield ) ) {
                    $answers_by_field[ $formfield['field_name'] ] = $answer['answer'];
                }
            }
            foreach ( $answer_keys as $key ) {
                $row[] = $answers_by_field[ $key ] ?? '';
            }
        }
        eme_fputcsv( $out, $row, $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_people() {
    global $wpdb;
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'people.csv' );

    $people_table = EME_DB_PREFIX . EME_PEOPLE_TBNAME;
    $people       = $wpdb->get_results( "SELECT * FROM $people_table ORDER BY person_id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    $prop_keys   = array_keys( eme_init_person_props() );
    $answer_keys = [];
    foreach ( $people as $person ) {
        foreach ( eme_get_person_answers( $person['person_id'] ) as $answer ) {
            $formfield = eme_get_formfield( $answer['field_id'] );
            if ( ! empty( $formfield ) ) {
                $answer_keys[ $formfield['field_name'] ] = true;
            }
        }
    }
    $answer_keys = array_keys( $answer_keys );

    $base_columns = [ 'lastname', 'firstname', 'email', 'phone', 'address1', 'address2', 'city', 'zip', 'state', 'country', 'status', 'birthdate', 'bd_email', 'birthplace', 'state_code', 'country_code', 'lang', 'massmail', 'newsletter', 'gdpr', 'groups' ];
    $headers      = $base_columns;
    foreach ( $prop_keys as $key ) {
        $headers[] = 'prop_' . $key;
    }
    foreach ( $answer_keys as $key ) {
        $headers[] = 'answer_' . $key;
    }
    eme_fputcsv( $out, $headers, $delimiter );

    foreach ( $people as $person ) {
        $row = [
            $person['lastname'],
            $person['firstname'],
            $person['email'],
            $person['phone'],
            $person['address1'],
            $person['address2'],
            $person['city'],
            $person['zip'],
            $person['state'],
            $person['country'],
            $person['status'],
            $person['birthdate'],
            $person['bd_email'],
            $person['birthplace'],
            $person['state_code'],
            $person['country_code'],
            $person['lang'],
            $person['massmail'],
            $person['newsletter'],
            $person['gdpr'],
            implode( '||', eme_get_persongroup_names( $person['person_id'] ) ),
        ];

        foreach ( $prop_keys as $key ) {
            $props      = eme_json_decode_safe( $person['properties'] );
            $prop_value = is_array( $props ) ? ( $props[ $key ] ?? '' ) : '';
            $row[]      = is_array( $prop_value ) ? wp_json_encode( $prop_value ) : $prop_value;
        }
        if ( $answer_keys ) {
            $answers_by_field = [];
            foreach ( eme_get_person_answers( $person['person_id'] ) as $answer ) {
                $formfield = eme_get_formfield( $answer['field_id'] );
                if ( ! empty( $formfield ) ) {
                    $answers_by_field[ $formfield['field_name'] ] = $answer['answer'];
                }
            }
            foreach ( $answer_keys as $key ) {
                $row[] = $answers_by_field[ $key ] ?? '';
            }
        }
        eme_fputcsv( $out, $row, $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_locations() {
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'locations.csv' );

    $locations = eme_get_locations( false, 'all' );
    $att_keys = [];
    $prop_keys = array_keys( eme_init_location_props() );
    $answer_keys = [];
    foreach ( $locations as $location ) {
        if ( ! empty( $location['location_attributes'] ) && is_array( $location['location_attributes'] ) ) {
            $att_keys = array_unique( array_merge( $att_keys, array_keys( $location['location_attributes'] ) ) );
        }
        foreach ( eme_get_location_answers( $location['location_id'] ) as $answer ) {
            $formfield = eme_get_formfield( $answer['field_id'] );
            if ( ! empty( $formfield ) ) {
                $answer_keys[ $formfield['field_name'] ] = true;
            }
        }
    }
    $answer_keys = array_keys( $answer_keys );

    $base_columns = [ 'location_name', 'location_address1', 'location_address2', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_latitude', 'location_longitude', 'location_description', 'location_url', 'location_external_ref', 'location_prefix', 'location_slug', 'location_image_url', 'category_names' ];
    $headers      = $base_columns;
    foreach ( $att_keys as $key ) {
        $headers[] = 'att_' . $key;
    }
    foreach ( $prop_keys as $key ) {
        $headers[] = 'prop_' . $key;
    }
    foreach ( $answer_keys as $key ) {
        $headers[] = 'answer_' . $key;
    }

    eme_fputcsv( $out, $headers, $delimiter );

    foreach ( $locations as $location ) {
        $row = [
            $location['location_name'],
            $location['location_address1'],
            $location['location_address2'],
            $location['location_city'],
            $location['location_state'],
            $location['location_zip'],
            $location['location_country'],
            $location['location_latitude'],
            $location['location_longitude'],
            $location['location_description'],
            $location['location_url'],
            $location['location_external_ref'],
            $location['location_prefix'],
            $location['location_slug'],
            $location['location_image_url'],
            implode( '||', eme_get_location_category_names( $location['location_id'] ) ),
        ];
        foreach ( $att_keys as $key ) {
            $att_value = $location['location_attributes'][ $key ] ?? '';
            $row[]     = is_array( $att_value ) ? wp_json_encode( $att_value ) : $att_value;
        }
        foreach ( $prop_keys as $key ) {
            $prop_value = $location['location_properties'][ $key ] ?? '';
            $row[]      = is_array( $prop_value ) ? wp_json_encode( $prop_value ) : $prop_value;
        }
        if ( $answer_keys ) {
            $answers_by_field = [];
            foreach ( eme_get_location_answers( $location['location_id'] ) as $answer ) {
                $formfield = eme_get_formfield( $answer['field_id'] );
                if ( ! empty( $formfield ) ) {
                    $answers_by_field[ $formfield['field_name'] ] = $answer['answer'];
                }
            }
            foreach ( $answer_keys as $key ) {
                $row[] = $answers_by_field[ $key ] ?? '';
            }
        }
        eme_fputcsv( $out, $row, $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_discounts() {
    global $wpdb;
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'discounts.csv' );

    $table     = EME_DB_PREFIX . EME_DISCOUNTS_TBNAME;
    $discounts = $wpdb->get_results( "SELECT * FROM $table ORDER BY id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $prop_keys = array_keys( eme_init_discount_props( [] ) );

    $base_columns = [ 'name', 'description', 'type', 'value', 'coupon', 'dgroup', 'valid_from', 'valid_to', 'use_per_seat', 'strcase', 'maxcount' ];
    $headers      = $base_columns;
    foreach ( $prop_keys as $key ) {
        $headers[] = 'prop_' . $key;
    }
    eme_fputcsv( $out, $headers, $delimiter );

    foreach ( $discounts as $discount ) {
        $dgroup_names = [];
        if ( ! empty( $discount['dgroup'] ) ) {
            foreach ( explode( ',', $discount['dgroup'] ) as $dgroup_id ) {
                $dgroup = eme_get_discountgroup( $dgroup_id );
                if ( ! empty( $dgroup ) ) {
                    $dgroup_names[] = $dgroup['name'];
                }
            }
        }
        $properties = eme_json_decode_safe( $discount['properties'] );
        $row        = [
            $discount['name'],
            $discount['description'],
            $discount['type'],
            $discount['value'],
            $discount['coupon'],
            implode( ',', $dgroup_names ),
            $discount['valid_from'],
            $discount['valid_to'],
            $discount['use_per_seat'],
            $discount['strcase'],
            $discount['maxcount'],
        ];
        foreach ( $prop_keys as $key ) {
            $prop_value = $properties[ $key ] ?? '';
            $row[]      = is_array( $prop_value ) ? wp_json_encode( $prop_value ) : $prop_value;
        }
        eme_fputcsv( $out, $row, $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_discountgroups() {
    global $wpdb;
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'discountgroups.csv' );
    eme_fputcsv( $out, [ 'name', 'description', 'maxdiscounts' ], $delimiter );
    $table   = EME_DB_PREFIX . EME_DISCOUNTGROUPS_TBNAME;
    $dgroups = $wpdb->get_results( "SELECT * FROM $table ORDER BY id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    foreach ( $dgroups as $dgroup ) {
        eme_fputcsv( $out, [ $dgroup['name'], $dgroup['description'], $dgroup['maxdiscounts'] ], $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_countries() {
    global $wpdb;
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'countries.csv' );
    eme_fputcsv( $out, [ 'alpha_2', 'alpha_3', 'num_3', 'name', 'lang' ], $delimiter );
    $table     = EME_DB_PREFIX . EME_COUNTRIES_TBNAME;
    $countries = $wpdb->get_results( "SELECT * FROM $table ORDER BY id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    foreach ( $countries as $country ) {
        eme_fputcsv( $out, [ $country['alpha_2'], $country['alpha_3'], $country['num_3'], $country['name'], $country['lang'] ], $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}

function eme_export_csv_states() {
    global $wpdb;
    [ $out, $delimiter ] = eme_import_export_open_csv_stream( 'states.csv' );
    eme_fputcsv( $out, [ 'code', 'name', 'country_alpha2', 'country_lang' ], $delimiter );
    $states_table    = EME_DB_PREFIX . EME_STATES_TBNAME;
    $countries_table = EME_DB_PREFIX . EME_COUNTRIES_TBNAME;
    $sql             = "SELECT s.code, s.name, c.alpha_2, c.lang FROM $states_table s LEFT JOIN $countries_table c ON s.country_id = c.id ORDER BY s.id"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are safe constants
    $states          = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    foreach ( $states as $state ) {
        eme_fputcsv( $out, [ $state['code'], $state['name'], $state['alpha_2'], $state['lang'] ], $delimiter );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV export
    fclose( $out );
}
