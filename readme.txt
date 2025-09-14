=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 3.0.17
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl.html

Manage and display events, memberships, recurring events, locations and maps, volunteers, widgets, RSVP, ICAL and RSS feeds, payment gateways support. SEO compatible.
             
== Description ==

Events Made Easy is a full-featured event and membership management solution for Wordpress and ClassicPress. Events Made Easy supports public, private, draft and recurring events, membership and locations management, RSVP (+ optional approval), several payment gateways (Paypal, 2Checkout, FirstData, Mollie and others) and OpenStreetMap integration. With Events Made Easy you can plan and publish your event, let people book spaces for your weekly meetings or manage volunteers and memberships. You can add events list, calendars and description to your blog using multiple sidebar widgets or shortcodes; if you are a web designer you can simply employ the placeholders provided by Events Made Easy. 

Main features:
* Public, private, draft and recurring events with custom and dynamic fields in the RSVP form
* Allow people to create events from the frontend using a specific shortcode
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
* Templating for emails, event lists, single events, feeds, RSVP forms, ... with specific placeholders for each
* Lots of shortcodes and options
* Payment gateways: Paypal, FirstData, 2CheckOut, Mollie, Payconiq, Worldpay, Stripe, Braintree, Instamojo, Mercado Pago, Fondy, SumUp, Opayo
* Send emails to registered people, automatically send reminders for payments
* Automatically send reminders for memberships that are about to expire or have expired
* Mail queueing and newsletter functionality
* Mailings can be planned in the future, cancelled ... and can include extra attacments
* Multi-site compatible
* Several GDPR assistance features (request, view and edit personal info via link; delete old records for mailings, attendances, bookings)
* Fully localisable and already fully localised in German, Swedish, French and Dutch. Also fully compatible with polylang and qtranslate-xt (https://github.com/qtranslate/qtranslate-xt/): most of the settings allow for language tags so you can show your events in different languages to different people. The booking emails also take the choosen language into account. For other multi-lingual plugins, EME provides its own in-text language tags and takes the current chosen language into account.

For documentation on all shortcodes and placeholders, visit the [Official site](https://www.e-dynamics.be/wordpress/) .

== Installation ==

Always take a backup of your db before doing the upgrade, just in case ...  

For existing wordpress users that have version 2.3.18 or older:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded, this will upload the zip and replace the existing installation without losing data
   If the file is too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. After that, updating the plugin will be as usual in the backend

For new users:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded
   If the file is too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. Activate the plugin through the 'Plugins' menu in WordPress (make sure your configured database user has the right to create/modify tables and columns) 
5. Add events list or calendars following the instructions in the Usage section.  

= Usage =

After the installation, Events Made Easy add a top level "Events" menu to your Wordpress Administration.

*  The *Events* page lets you manage your events. The *Add new* page lets you insert a new event.
   Generic EME settings concerning RSVP emails and templates can be overriden per event.
*  The *Locations* page lets you add, delete and edit locations directly. Locations are automatically added with events if not present, but this interface lets you customise your locations data and add a picture.
*  The *Categories* page lets you add, delete and edit categories (if Categories are activated in the Settings page).
*  The *Holidays* page is used to define and manage holiday lists used in a calendar
*  The *Custom fields* page lets you manage custom fields that can be used for events, locations, people, members, memberships and RSVP definitions
*  The *Template* page lets you manage templates for events, memberships, emails, pdf creation, ...
*  The *Discounts* page lets you manage discounts and discount groups used in RSVP or membership definitions
*  The *People* page serves as a gathering point for the information about the people who booked a space for one of your events or for members personal info.
   It can also be used to add custom info for a person based on the group he's in, so as to reflect the structure of an organization or just store extra info
*  The *Groups* page
*  The *Pending bookings* page is used to manage bookings for events that require approval.
*  The *Change bookings* page is used to change bookings for events.
*  The *Members* page is used to manage all your members (e.g. membership status, custom member info).
*  The *Memberships* page is used to define and manage your memberships. 
*  The *Countries/states* page can be used to define countries and states (in different languages) for personal info in membership and RSVP forms
*  The *Send emails* page allows the planning, creation and management of mailings for events or generic info (many options possible)
*  The *Scheduled actions* page is used to plan automated EME tasks (like sending reminders, cancel unpaid bookings, newsletter)).
*  The *Cleanup actions* page
*  The *Settings* page is used to set generic EME defaults for events, payment gateways, emailserver info, mail templates, ...
*  Fine-grainded configurable access control (ACL) for managing events, locations, bookings, members, ...

Events list and calendars can be added to your blogs through widgets, shortcodes and placeholders. See the full documentation at the [Official site](https://www.e-dynamics.be/wordpress/).
 
== Frequently Asked Questions ==

See the FAQ section at the [Official site](https://www.e-dynamics.be/wordpress/).

== Changelog ==
= 3.0.17  (2025/09/14) =
* Add tom-select maps for js and css
* Fix waiting list visual warning when editing booking in the backend
* Added eme_move_from_waitinglist_rsvp_action action hook, executed after a booking has been moved from the waiting list to pending or approved.
  One argument: the updated booking
* Allow custom fields in the task signup form

= 3.0.16  (2025/09/03) =
* payconiq API switch is rescheduled to Oct 19
* ftable update

= 3.0.15  (2025/08/31) =
* typo fix for first day of the week when using the JS datepicker
* code correction for title of page in admin backend when editing event
* remove unused images
* ftable update to increase pageSizes
* fdatepicker update to correct weekend coloring

= 3.0.14  (2025/08/24) =
* fix frontend submit start/end time

= 3.0.13  (2025/08/24) =
* update datepicker to account for escaped characters

= 3.0.12  (2025/08/23) =
* Make frontend submit work again (due to empty time and a wrong option the datepicker bailed out)

= 3.0.11  (2025/08/22) =
* code fix to redirect immediately to payment gateway (for memberships too and for braintree also)

= 3.0.10  (2025/08/22) =
* code fix to redirect immediately to payment gateway
* fix for time-select in events

= 3.0.9  (2025/08/22) =
* ftable and fdatepicker updates
* fix JS selectors for form ids too

= 3.0.8  (2025/08/21) =
* better admin feedback for missing input
* fix JS selectors and a possible case with captcha id

= 3.0.7  (2025/08/19) =
* Braintree API update
* Mollie API update
* Fix when editing a booking (javascript error blocked certain actions)

= 3.0.6  (2025/08/17) =
* CSV button fix (it exported too much columns)
* Make sure datepicker works for newly added tasks too

= 3.0.5  (2025/08/16) =
* Avoid clashing of $ with other scripts, using own namespace now
* Fix a small issue with captcha image selector

= 3.0.4  (2025/08/15) =
= 3.0.3  (2025/08/15) =
* the custom fields overview in tables was not correct, fixed (due to jquery migration)

= 3.0.2  (2025/08/14) =
* time_js custom field fix
* datetime format was set to default, not user preferences
* datepicker update to prevent paste/drop but allow people to tab in/out for keyboard users

= 3.0.1  (2025/08/14) =
* Small JS typo fix when wanting to see the bookings via the event list
* Improvement for the select-boxes (if not multiple: lose focus after selection)

= 3.0.0  (2025/08/13) =
* Huge JS code rewrite to remove jquery dependency from EME, fdatepicker and ftable
* Added the possibility to give a "Maximum usage count per user" for a discount (applies and requires users to be logged in).
* Mollie update
* Updater-code update to account for php 8.4

= 2.6.12 (2025//) =
* javascript and jtable updates to more reliable check for checked status of checkboxes
* also fdatepicker code updates and other javascript improvements
* Added the possibility to give a "Maximum usage count per user" for a discount (applies and requires users to be logged in).

= 2.6.11 (2025/07/06) =
* Allow event authors to also do some event bulk actions
* Fix discount imports
* a br was shown for some options where it shouldn't be

= 2.6.10 (2025/06/26) =
* Small typo fix release

= 2.6.9 (2025/06/26) =
* Smarter br-handling if the editor is jodit 
* Better word paste if the editor is jodit
* The content of the reminder emails could not be entered directly per event

= 2.6.8 (2025/06/24) =
* New custom field type: datalist
* Allow some html in custom field tags for fields that have labels rendered
* Add bulk action to make events hidden
* Bulk action to add people to groups has been added to the RSVP admin page too
* Discounts can now check the number of booked seats too

= 2.6.7 (2025/06/16) =
* Fix payconiq date checking
* Jtable update to account for firefox and minimal column width

= 2.6.6 (2025/06/15) =
* Fix some select2 styling
* Small select2 and jtable updates
* CSV export now includes better discount info

= 2.6.5 (2025/06/12) =
* Calendar fixes due to refactor issues

= 2.6.4 (2025/06/11) =
* fix some select2 widths and sending via tinymce

= 2.6.3 (2025/06/10) =
* JS code refactoring, including minor fixes
* Mercadopago API update to 3.5.0
* Mail template selection was not working for tinymce in html modus, fixed
* Admin notices now get dismissed via ajax
* Long unseen bug in custom field dropdowns if the admin fields were different than the frontend ones but the admin tags were empty

= 2.6.2 (2025/06/06) =
* allow BEGINOPTGROUP and ENDOPTGROUP for dropdown fields
* Update Mollie API to 3.1.0
* Be able to work with other Mollie libs from plugins that haven't updated yet

= 2.6.1 (2025/06/02) =
* Fix frontend submit
* Update Braintree API to 6.26.0

= 2.6.0 (2025/06/01) =
* Integrate jodit as editor
* Small sumup and braintree fixes
* Use newer mollie api code implementation
* Fix mollie refund (description is now a required parameter there)
* Eventmail preview didn't include the attachment (the actual mailing does)
* #_DISCOUNT_VALID_TILL{discountid}{interval format} #_DISCOUNT_VALID_FROM{discountid}{interval format} have been added to the placeholders for events

= 2.5.62 (2025/05/05) =
* Typo for payments in membership payments

= 2.5.61 (2025/05/05) =
* Fix the REST API call to take the new status for pending mails into account as well

= 2.5.60 (2025/05/04) =
* Fixed yet another constant typo causing bookings to fail now ...

= 2.5.59 (2025/05/04) =
* Fixed constant typo for ignoring pending booking mail

= 2.5.57 (2025/05/04) =
* If wanted, send out paid emails immediately (when a payment arrives via a payment gateway)

= 2.5.56 (2025/04/28) =
* The pending booking mail now gets removed after one run from the mail queue if the payment arrives (and only if online payments are possible). This is a more consistent removal period and action

= 2.5.55 (2025/04/26) =
* Added API function eme_send_mail_to_groups to easily send emails to an EME group and obey list headers etc...
  Function def:
     eme_send_mail_to_groups( $group_ids, $subject, $body, $fromemail, $fromname, $replytoemail='', $replytoname='')
     ==> $group_ids needs to be a CSV list of group ids
     ==> $replytoemail and $replytoname are optional (then the from-info is taken)
* Added API function eme_send_eventmail_to_groups to easily send emails to an EME group concerning events and obey list headers etc...
  Function def:
     eme_send_eventmail_to_groups( $event_ids, $group_ids, $subject, $body, $fromemail, $fromname, $replytoemail='', $replytoname='')
     ==> $event_ids needs to be a CSV list of event ids
     ==> $group_ids needs to be a CSV list of group ids
     ==> $replytoemail and $replytoname are optional (then the from-info is taken)
* Unsent mail for a pending booking is removed if the payment arrives in time, to avoid the booker to receive 2 emails at the same time

= 2.5.54 (2025/04/23) =
* Fix show/hide columns, offset was wrong by 1 due to code reorg in jtable

= 2.5.52 (2025/04/23) =
* Trashed events should not be shown in eme_events shortcode nor in the dashboard overview
* Jtable update

= 2.5.51 (2025/04/17) =
* Unsub from the newsletter was not working: fixed
* If nothing is done in unsub, now a message will be returned reflecting that
* Start using more wpdb->update functions

= 2.5.49 (2025/04/16) =
* Unsub confirmation email was not being sent, fixed
* Remove a borderline case causing a PHP notice from wordpress to pop up "Notice: Function _load_textdomain_just_in_time was called incorrectly."

= 2.5.48 (2025/04/15) =
* Make sure unsub actually does something
* Add unsub confirm emails so people know they unsubscribed

= 2.5.47 (2025/04/15) =
* Renamed #_DYNAMICPRICE_PER_PG to #_DYNAMICPRICE_DETAILED
* #_DYNAMICPRICE_DETAILED now will return 0 (localized) if the price is 0, not the per payment method info in that case
* Allow #_DYNAMICPRICE and #_DYNAMICPRICE_DETAILED to be used together (although not recommended)
* Added better info per payment method option in the backend
* Ctrl-click is the default again for sorting on multiple columns (hint added for that part too)
  For mobile Ctrl-key is not available, there the single-click stays functional
* Add List-unsub one-click headers if/where possible

= 2.5.45 (2025/04/10) =
* Fix copying event answers for missing events when updating an event
* Make sure every mail has the desired sender for newsletters too
* Extended RSVP placeholder #_TOTALPRICE{xx}: xx can now be the name of a payment method (and not only a number for multiseat event), this returns the total price including the extra costs for that particular payment method
* Added member placeholders #_PRICE{xx} (see above) and #_CHARGE{xx} (see the doc)
* Added member and rsvp placeholders #_DYNAMICPRICE_PER_PG , which will show the total price to pay per relevant payment method

= 2.5.44 (2025/04/04) =
* Fix discount filtering in events
* Allow/fix sorting on custom fields for events and locations
* Slightly smaller delay to show dynamic price or fields when a user stops typing (from 1 second to 0.5 seconds)
* Added customfield_ids and customfield_value options to the eme_events shortcode:
  customfield_ids should be a comma-separated list of custom field ids you want to search through
  customfield_value is the value you want to search for in the list of custom field ids
  This allows the events to be filtered on custom field values as well.

= 2.5.43 (2025/03/28) =
* Fix the use of #_FIELDVALUE for uploaded files

= 2.5.42 (2025/03/09) =
* jtable translation updates included
* Braintree API update to version 6.24.0
* Small JS fixes
* More visual feedback when creating or listing events to indicate possible location max cap limit

= 2.5.41 (2025/03/02) =
* Fix html-output in mass-actions dropdown
* Show sorting info for some tables, to indicate exactly how the data is sorted

= 2.5.40 (2025/02/24) =
* Task option typo fix
* Added captcha support for Friendly Captcha
* Jtable sorting fix
* Payconiq lib update

= 2.5.39 (2025/02/19) =
* Jtable updates
* Mollie API update
* Make sure multisorting is active everywhere where table sorting is possible

= 2.5.38 (2025/02/13) =
* Some old multisite fixes (whnn adding a blog and EME is network-activated)
* Allow translation tags in EME FS format

= 2.5.37 (2025/02/08) =
* Small jtable update to fix certain small bugs
* Add eme_trash_event_action, works like eme_delete_event_action but fires just before the status of the event is set to trash

= 2.5.36 (2025/02/05) =
* Use jtable everywhere, more uniform coding (better in mailing-overview)

= 2.5.35 (2025/01/29) =
* Fix Frontend submit if no own validation filter is defined

= 2.5.34 (2025/01/23) =
* Add filter eme_fs_validate_event_filter, which should return empty if all FS event data validation is ok
  Parameter: $event_data (array containing all data entered via the frontend submit form)
* Fix multiple occurences of state and country input fields on one page

= 2.5.33 (2025/01/19) =
* Small bugfix due to the eme_mybookings and eme_bookings change

= 2.5.32 (2025/01/16) =
* Remove use of old deprecated function in todos, it caused a php error if todos were defined for an event

= 2.5.31 (2025/01/14) =
* Both the shortcodes eme_mybookings and eme_bookings now accept multiple id's in the id-param, to indicate multiple events:
  [eme_bookings id="1,3,5" template_id=3 template_id_header=7 template_id_footer=9]
* Per recurrence, you can now specify excluded days (next to the holidays list), on those days no events will be created

= 2.5.30 (2025/01/05) =
* Avoid a php error triggered when planning a mailing to be sent immediately and then going in the "Scheduled actions" submenu before the first cron-period had passed
* The selecting of events was accidentally removed from code for sending event-related emails

= 2.5.29 (2025/01/03) =
* Fix the jtable action buttons

= 2.5.28 (2024/12/30) =
* Fix sorting multiple columns in the events overview if you selected 'date and time' as a column too

= 2.5.27 (2024/12/29) =
* More jtable styling fixes
* The action to clear the complete queue now cancels all ongoing and planned mailings and all individual emails too

= 2.5.26 (2024/12/25) =
* Paging fix due to typo

= 2.5.25 (2024/12/25) =
* The configured payment gateways were not showing as expected
* Make "Show all bookings" in people overview work again as expected
* Added a REST API call, in case you don't trust WP cron to process the queue. As an example, you can call:
  curl --insecure --user "username:password" https://localhost/wordpress/wp-json/events-made-easy/v1/processqueue/60
  ==> don't user --insecure for public sites, and change "https://localhost/wordpress" by your wordpress url
  ==> change the "username" by your user and the "password" by an application password generated in your WP user settings
  ==> "60" means the script can run at most for 55 seconds (=60-5, 5 being a safety measure). Never set this higher than your cron recurrence of course
  ==> set the timing option for queue processing to "not scheduled" in EME, so the two don't interfere :-) But in fact: it doesn't really matter: EME is resilient enough to cope with both at the same time (but better be safe than sorry).

= 2.5.24 (2024/12/22) =
* Some more table fixes (more logical resizing)
* Last release of 2024 :-)

= 2.5.23 (2024/12/18) =
* Rewritten the jtable jquery plugin so it no longer requires jquery-ui. This allows for less jquery-stuff to be loaded and thus faster

= 2.5.22 (2024/12/12) =
* Fix a JS error in templates admin
* Keep current tab after actions in mailer menu

= 2.5.21 (2024/12/12) =
* Extra bulk action when managing bookings, to indicate attendance
* Allow dynamic groups also as condition in the setting 'Require logged-in user to be in of one of the selected EME groups in order to be able to book for this event.'
* Added #_ATTENDANCEPROOF_URL placeholder, which generates a link that - when clicked - will generate a pdf if the booking attendance count was > 0. The pdf template can be set in the event attendance settings.
* The url to check for attendance is now nonce-protected, but old url's are still supported

= 2.5.20 (2024/11/24) =
* Some HTML fixes
* Make the option 'Attendees list ignore pending' work as advertised (only for the placeholders, not the shortcodes)
* Uploaded files were not always shown in the list of files per event/booking (because of extra "-" in the filenames)

= 2.5.19 (2024/11/20) =
* Family members form template can now also contain custom fields of type "member" (next to those of type "people")
* Fix membership update due to invisible required field

= 2.5.18 (2024/11/14) =
* eme_locations_map shortcode was parsing width and heigth wrong due to new code

= 2.5.17 (2024/11/14) =
* New introduced WP bug causes the translation strings to no longer load as expected
  So the logic to loading translation strings has been rewritten to no longer use load_plugin_textdomain but load_textdomain
* Delimiter option was renamed but the old option name was still being used in some places
* Fix the console error "Not allowed to navigate top frame to data URL 'data:'..." in some browsers when clicking the CSV export button

= 2.5.16 (2024/11/13) =
* Added some details/summary (accordion) open/close animations and open only 1 details at the same time by adding the name attribute
* For family-type memberships: enfore the presence of #_FAMILYCOUNT and only 1-time presence of both #_FAMILYCOUNT -a #_FAMILYMEMBERS
* Frontend submit js and php warning fixes
  
= 2.5.15 (2024/11/10) =
* Replaced the use of jquery-ui-tabs and jquery-ui-accordion by regular html5, css and a little bit of JS. This should speed up things
* Removed the use of jquery-ui-dialog in the frontend. Once jtable no longer needs it, it will be removed in the backend too
* Removed the use of jquery-ui-autocomplete
* Added option all_events to eme_simple_multibooking_form, so all events are selected to attend.
* Allow more characters in pdf attach names (for templates, when used as mail attachments only)

= 2.5.14 (2024/10/26) =
* Bulk action to task signups added to send reminders
* PDF templates have a setting to change the mail attachment name if the template is used in member or booking related emails

= 2.5.13 (2024/10/05) =
* Add generic placeholders #_USER_GROUPS and #_USER_MEMBERSHIPS
* Add list of todos for an event. Each passed todo will send a mail to the contact person so you can use this to plan your event and not forget things. Difference with tasks: volunteers can subscribe to tasks, not todos. The todos are just a list of things you don't want to forget.

= 2.5.12 (2024/09/17) =
* Fix a small check causing membership stats to not work as expected

= 2.5.11 (2024/09/16) =
* Added task option to limit task signups to one per event per person. The task signup checkbox then becomes a radiobox too
* Added task formfield placeholder #_TASKHTMLID. This can be used in the tast formfield to e.g. enclose extra task info in a html-label in the task signup form format:
  #_TASKSIGNUPCHECKBOX <label for="#_TASKHTMLID">#_TASKNAME (#_TASKBEGIN - #_TASKEND) (#_FREETASKSPACES/#_TASKSPACES)</label> <br />
* reminder-options are lists of numbers but were converted to a single value when creating new events

= 2.5.10 (2024/09/05) =
* CLI tool can allow mail from everywhere: allowed_senders=ALL
* Allow 'value=' for some fs fields
* Fix deleting holiday lists
* 4 extra RSVP mail filters, so you can filter (=change) the pending booking text yourself when moving a booking from//to the waiting list:
    eme_rsvp_email_from_pending_to_waitinglist_subject
    eme_rsvp_email_from_pending_to_waitinglist_body
    eme_rsvp_email_from_waitinglist_to_pending_subject
    eme_rsvp_email_from_waitinglist_to_pending_body
  By default they have the same value as the pending mail/subject

= 2.5.9 (2024/08/26) =
* Fix some PHP warnings
* Fix payment method setting via FS submit if only payment method is defined

= 2.5.8 (2024/08/21) =
* New feature: pay for event submissions
* Event submissions can now send email to the person submitting (if logged in) and a defined contact person
* It's no longer needed to use #_CFCAPTCHA, #_HCAPTCHA, #RECAPTCHA. Just #_CAPTCHA is sufficient, since only one captcha is allowed and EME knows which one to use. If more than one captcha is configured, certain forms will use the first configured one.
* Action eme_ipn_fs_event_action added, executed after the submitted event is paid for (1 param: $event)
* Action eme_fs_submit_event_action added, executed after the event is submitted into EME (1 param: $event)

= 2.5.7 (2024/08/13) =
* The filterform placeholders #_FILTERWEEKS, #_FILTERMONTHS, #_FILTERYEARS now take 2 extra optional placeholders that indicate the number of week/months/years in the past and the future you want the scope to be. Example:
#_FILTERYEARS{5}{3} to create a year scope from 5 years in the past till 2 years in the future
#_FILTERYEARS{5} to create a year scope from 5 years in the past till the default scope count (taken from the eme_filterform shortcode) in the future
#_FILTERYEARS to create a year scope from now till the default scope count (taken from the eme_filterform shortcode) in the future
* The new FS form did not correctly noted down event start/end times

= 2.5.6 (2024/08/10) =
* Fix guest frontend event submit
* Fix redirection to event: if the submitted event is not public and a guest submitted it, we show the success message and don't redirect

= 2.5.5 (2024/08/07) =
* Frontend submit now supports generic EME placeholders and interprets shortcodes too
* New parameter for frontend submit: startdatetime=now. If used, the start date and time are by default set to
  the current date/time (as the old plugin did)
  Example: [eme_add_event_form startdatetime=now]
* Add a status to memberships, so you can put memberships as inactive

= 2.5.4 (2024/07/30) =
* syntax error fix

= 2.5.3 (2024/07/30) =
* Fix a typo causing custom field not to be rendered in the frontend add event form
* Make frontend submit also work when wysiwyg not activated
* Treat #_FIELD as #_PROP if a property with that name exists and no basic event setting (this in fact renders #_PROP a bit redundant, but since you need to look in the code to know all properties I'm going to leave it as is)

= 2.5.2 (2024/07/28) =
* Remove duplicate code from frontend submit
* Make sure the location id gets passed from frontend submit form (if selecting existing location), and latitude and longitude get added automatically

= 2.5.1 (2024/07/26) =
* Localising done earlier for the newly added javascript for frontend submit, so it works in all circumstances now (or it should)

= 2.5.0 (2024/07/26) =
* Integrated the frontend submit form, using [eme_add_event_form] shortcode and templating support
