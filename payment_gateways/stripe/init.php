<?php
// Backwards compatibility shim.
// Older extra payment gateway plugins (see eme_extra_pg.php) do a require_once of this file to load the Stripe library.
// The library is now loaded via composer's autoloader, so just forward to that.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
require_once __DIR__ . '/vendor/autoload.php';
 
