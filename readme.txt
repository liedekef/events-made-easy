=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 5.4
Tested up to: 6.1
Stable tag: 2.3.41
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage and display events, memberships, recurring events, locations and maps, volunteers, widgets, RSVP, ICAL and RSS feeds, payment gateways support. SEO compatible.
             
== Description ==

Events Made Easy is a full-featured event and membership management solution for Wordpress. Events Made Easy supports public, private, draft and recurring events, membership and locations management, RSVP (+ optional approval), several payment gateways (Paypal, 2Checkout, FirstData, Mollie and others) and OpenStreetMap integration. With Events Made Easy you can plan and publish your event, let people book spaces for your weekly meetings or manage volunteers and memberships. You can add events list, calendars and description to your blog using multiple sidebar widgets or shortcodes; if you are a web designer you can simply employ the placeholders provided by Events Made Easy. 

Main features:
* Public, private, draft and recurring events with custom and dynamic fields in the RSVP form
* Membership management with custom and dynamic fields
* Volunteer management for events (using event tasks)
* Attendance reporting for events and memberships if the rsvp or member qrcode is scanned by someone with enough rights
* Page and post content protection through memberships or via shortcodes
* Drip content via memberships
* People and groups with custom fields per person
* PDF creation for membership, bookings and people info
* Membership card or booking ticket can be sent as PDF via mail automatically, with optional QR code to scan for attendance/payment info
* RSS and ICAL feeds
* Calendar management, with holidays integration
* Several widgets for event listings and calendar
* Location management, with optional OpenStreetMap integration
* RSVP bookings with custom fields and dynamic fields, payment tracking, optional approval, discounts
* Protection of forms with internal captcha, Google reCaptcha or hCaptcha
* Templating for mails, event lists, single events, feeds, RSVP forms, ... with specific placeholders for each
* Lots of shortcodes and options
* Payment gateways: Paypal, FirstData, 2CheckOut, Mollie, Payconiq, Worldpay, Stripe, Braintree, Instamojo, Mercado Pago, Fondy, SumUp, Opayo
* Send mails to registered people, automatically send reminders for payments
* Automatically send reminders for memberships that are about to expire or have expired
* Mail queueing and newsletter functionality
* Mailings can be planned in the future, cancelled ... and can include extra attacments
* Multi-site compatible
* Several GDPR assistance features (request, view and edit personal info via link; delete old records for mailings, attendances, bookings)
* Fully localisable and already fully localised in German, Swedish, French and Dutch. Also fully compatible with qtranslate-xt (https://github.com/qtranslate/qtranslate-xt/): most of the settings allow for language tags so you can show your events in different languages to different people. The booking mails also take the choosen language into account. For other multi-lingual plugins, EME provides its own in-text language tags and takes the current chosen language into account.

For documentation on all shortcodes and placeholders, visit the [Official site](https://www.e-dynamics.be/wordpress/) .

== Installation ==

Always take a backup of your db before doing the upgrade, just in case ...  

For existing wordpress users that have version 2.3.18 or older:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded, this will upload the zip and replace the existing installation without losing data
   If the file is too big for uploading, try again with "events-made-easy-minimal.zip" (which is a minimum version of the previous release, after which a regular update will present itself).
   If still too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. After that, updating the plugin will be as usual in the backend

For new users:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded
   If the file is too big for uploading, try again with "events-made-easy-minimal.zip" (which is a minimum version of the previous release, after which a regular update will present itself).
   If still too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. Activate the plugin through the 'Plugins' menu in WordPress (make sure your configured database user has the right to create/modify tables and columns) 
5. Add events list or calendars following the instructions in the Usage section.  

= Usage =

After the installation, Events Made Easy add a top level "Events" menu to your Wordpress Administration.

*  The *Events* page lets you manage your events. The *Add new* page lets you insert a new event.
   Generic EME settings concerning RSVP mails and templates can be overriden per event.
*  The *Locations* page lets you add, delete and edit locations directly. Locations are automatically added with events if not present, but this interface lets you customise your locations data and add a picture.
*  The *Categories* page lets you add, delete and edit categories (if Categories are activated in the Settings page).
*  The *Holidays* page is used to define and manage holiday lists used in a calendar
*  The *Custom fields* page lets you manage custom fields that can be used for events, locations, people, members, memberships and RSVP definitions
*  The *Template* page lets you manage templates for events, memberships, mails, pdf creation, ...
*  The *Discounts* page lets you manage discounts and discount groups used in RSVP or membership definitions
*  The *People* page serves as a gathering point for the information about the people who booked a space for one of your events or for members personal info.
   It can also be used to add custom info for a person based on the group he's in, so as to reflect the structure of an organization or just store extra info
*  The *Groups* page
*  The *Pending bookings* page is used to manage bookings for events that require approval.
*  The *Change bookings* page is used to change bookings for events.
*  The *Members* page is used to manage all your members (e.g. membership status, custom member info).
*  The *Memberships* page is used to define and manage your memberships. 
*  The *Countries/states* page can be used to define countries and states (in different languages) for personal info in membership and RSVP forms
*  The *Send mails* page allows the planning, creation and management of mailings for events or generic info (many options possible)
*  The *Scheduled actions* page is used to plan automated EME tasks (like sending reminders, cancel unpaid bookings, newsletter)).
*  The *Cleanup actions* page
*  The *Settings* page is used to set generic EME defaults for events, payment gateways, mailserver info, mail templates, ...
*  Fine-grainded configurable access control (ACL) for managing events, locations, bookings, members, ...

Events list and calendars can be added to your blogs through widgets, shortcodes and placeholders. See the full documentation at the [Official site](https://www.e-dynamics.be/wordpress/).
 
== Frequently Asked Questions ==

See the FAQ section at the [Official site](https://www.e-dynamics.be/wordpress/).

== Changelog ==
= 2.3.42 (2023//) =
* Added #_MULTIBOOKING_TOTALPRICE_NO_VAT and #_MULTIBOOKING_TOTALPRICE_VAT_ONLY
* Allow paymentid to be given as param to wp-admin/admin.php?page=eme-registration-approval and wp-admin/admin.php?page=eme-registration-seats
  so you can create a link to bookings just related to that payment. Example:
  wp-admin/admin.php?page=eme-registration-seats&paymentid=#_PAYMENTID
* Add "Mark paid" button in the table for approved bookings too, similar to the "Mark paid and approve" button in the table for pending bookings

= 2.3.41 (2023/01/15) =
* Add an action to (retry to) resend all failed mails in a mailing
* Fix wrongfull captcha check if captcha was disabled for an event
* Decrease use of globals as much as possible, "define" is more than enough (and available during plugin install, while globals are not)
 
= 2.3.38 (2023/01/14) =
* Captcha also for gdpr-related forms (request/change personal info)
* Fix a warning during plugin activation
* Fix showing event title and booking comment in booking printable report

= 2.3.37 (2023/01/11) =
* Fix hCaptcha and Cloudflare Turnstile captcha verification
* Fix memberships going past grace period (upon which they daily received the stopped-member mail but the status was not set to stopped)

= 2.3.36 (2023/01/11) =
* Payment gateway updates for Mollie, Mercadopago and Stripe
* Fix updating a person personal info (due to double nonce-checking people received "access denied")
* Fix hCaptcha and Cloudflare Turnstile captcha verification

= 2.3.35 (2023/01/07) =
* Fix options title on some options
* Fix GDPR settings (typo prevented the page from rendering)
* Add a default member form format if none is provided (and alert in the list of memberships if done)

= 2.3.34 (2023/01/06) =
* Fix Google reCaptcha

= 2.3.33 (2023/01/05) =
* Fix multiprice/multiseat events

= 2.3.32 (2023/01/05) =
* Fix deleting custom fields
* Add Cloudflare Turnstile as captcha method for forms

= 2.3.31 (2023/01/01) =
* No changes, but release to make sure new updates come in fine

= 2.3.30 (2023/01/01) =
* Fix plugin update checker to actually use the released zip (of course that will only really work from this release onwards :-) )
* Use short php array notation, and updated lang files

= 2.3.29 (2022/12/30) =
* Update cron schedules to prefix them with "eme_".
    After update, verify your EME Email setting "Send out queued mails in batches of" (if mail queuing is activated) and the Newsletter setting in the menu "Scheduled actions"
    (the Newsletter setting will probably need to be set again)
* Fix autocomplete people

= 2.3.28 (2022/12/28) =
* Fix several sql statements (prepare statement only works with arrays as argument if that is the only one ...)

= 2.3.27 (2022/12/28) =
* Fix sending mail

= 2.3.26 (2022/12/27) =
* SQL code rewriting (not vulnerable, but more in line with what is expected by wp, not 100% done)
