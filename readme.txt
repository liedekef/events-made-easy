=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 2.5.13
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage and display events, memberships, recurring events, locations and maps, volunteers, widgets, RSVP, ICAL and RSS feeds, payment gateways support. SEO compatible.
             
== Description ==

Events Made Easy is a full-featured event and membership management solution for Wordpress. Events Made Easy supports public, private, draft and recurring events, membership and locations management, RSVP (+ optional approval), several payment gateways (Paypal, 2Checkout, FirstData, Mollie and others) and OpenStreetMap integration. With Events Made Easy you can plan and publish your event, let people book spaces for your weekly meetings or manage volunteers and memberships. You can add events list, calendars and description to your blog using multiple sidebar widgets or shortcodes; if you are a web designer you can simply employ the placeholders provided by Events Made Easy. 

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
* Templating for mails, event lists, single events, feeds, RSVP forms, ... with specific placeholders for each
* Lots of shortcodes and options
* Payment gateways: Paypal, FirstData, 2CheckOut, Mollie, Payconiq, Worldpay, Stripe, Braintree, Instamojo, Mercado Pago, Fondy, SumUp, Opayo
* Send mails to registered people, automatically send reminders for payments
* Automatically send reminders for memberships that are about to expire or have expired
* Mail queueing and newsletter functionality
* Mailings can be planned in the future, cancelled ... and can include extra attacments
* Multi-site compatible
* Several GDPR assistance features (request, view and edit personal info via link; delete old records for mailings, attendances, bookings)
* Fully localisable and already fully localised in German, Swedish, French and Dutch. Also fully compatible with polylang and qtranslate-xt (https://github.com/qtranslate/qtranslate-xt/): most of the settings allow for language tags so you can show your events in different languages to different people. The booking mails also take the choosen language into account. For other multi-lingual plugins, EME provides its own in-text language tags and takes the current chosen language into account.

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
= 2.5.14 (2024/10/) =
* Bulk action to task signups added to send reminders

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

= 2.4.53 (2024/07/19) =
* A renamed option was not taken into account in all pages

= 2.4.52 (2024/07/17) =
* Fix an option code typo

= 2.4.51 (2024/07/17) =
* Add an EME dashboard to the main WP dashboard page
* Make automatic redirect to payment gateway work in all cases again, this time for memberships too

= 2.4.50 (2024/07/15) =
* Make automatic redirect to payment gateway work in all cases again

= 2.4.49 (2024/07/11) =
* Mollie API update
* Add email settings in case a member is deleted in the admin interface
* Also support #_CONSENT as placeholder (alias for #_GDPR)
* Show notice after upgrading EME (will be used to show/point to the changelog too)

= 2.4.48 (2024/07/01) =
* Mollie API update
* Avoid creating past events for recurrences
* Make sure captcha settings can be deactivated per event/membership
* Planned mailings will now no longer insert the individual mails but do that just before being sent
* Recurrences no longer need an end date, in which case the next 10 events will be planned (checked daily),
  while older events will be removed except the most recent one.
* The number of spaces for a task can now be 0 too. If that's the case, the description of that task
  will be used as a section header for the next set of tasks.
* Added action hook eme_ical_header_action, allows to add custom headers by just echo-ing them

= 2.4.47 (2024/06/08) =
* Make sort on membership name work in members overview
* Small fix in membership statistics (some counters included pending members)

= 2.4.46 (2024/05/26) =
* #_IS_USER_IN_GROUP fix for lists of group names (not ids)
* Get the first user with admin rights as default contact person if no other is found
* Only email is required for person import
* When trashing bookings for a trashed event, also execute the hook eme_trash_rsvp_action if present

= 2.4.45 (2024/05/21) =
* Update dompdf to 3.0.0
* Fix #_IS_USER_IN_GROUP for static groups
* Fix ical php warning

= 2.4.44 (2024/05/17) =
* Make #_IS_USER_IN_GROUP also work for dynamic groups
* Account for empty/forced from-email when inserting a mailing (the individual mails were ok, so it just caused a php warning)

= 2.4.43 (2024/05/09) =
* Correct direct adding of dynamic groups of people or members
* Added an option for exact search match for custom field searches (for dynamic groups too)

= 2.4.42 (2024/05/03) =
* Added an option to anonymize old members (and not just remove them), this allows for membership stats to be more correct and kept longer
* The date/time format set for custom fields was not respected in the form itself in firefox, this has been corrected
* When updating from a version older than 2.4.34, the per-event rsvp end cutoff day was set to the start date (if set at all)

= 2.4.41 (2024/04/24) =
* Typo caused #_IS_USER_MEMBER_OF not to work

= 2.4.40 (2024/04/24) =
* Better people-cleanup function
* Update dompdf to 2.0.7
* Make #_IS_USER_MEMBER_PENDING work
* Fix membership edit

= 2.4.39 (2024/04/18) =
* Show RSVP and task info overview in the event edit window too (in the sidebar), like in the events overview table
* PDF templates can now be added to the paid/pending/booking made/approved mails (default and per event)
* PDF templates can now be added to the paid/extended/new member mails (set per membership)
* Update dompdf to 2.0.5

= 2.4.38 (2024/04/01) =
* Avoid html-encoding of some options (like the smtp password)
* Make #_IS_REGISTERED_PENDING and #_IS_REGISTERED_APPROVED work as expected (there was a typo in the executed sql statement)
* Small tasklist improv: disable checkboxes if appropriate (not remove them), makes the list more uniform
* Upgraded braintree API to 6.18.0
* Fix auto-approve payments if pending seats are not considered as free

= 2.4.37 (2024/03/24) =
* Fix setting the "Allow renewal" property for memberships
* Check a borderline case for full events when a pending booking is being paid for
* Added a mail for the case when a payment arrives for a pending booking for an event with auto-approve active but the
  event is fully booked so the booking can't get approved automatically
  By default this mail will only be sent to the contact person (and is empty for the booker).
  The mail content can only be changed via a mail filter (see doc on eme_rsvp_email_text_xxx_filter and eme_rsvp_email_html_xxx_filter
  with 'xxx' being 'pendingButPaid')

= 2.4.36 (2024/03/18) =
* Fix typo for event payment gateways registration/activation

= 2.4.35 (2024/03/17) =
* Added #_IS_PERSON_MEMBER_OF{xx} so you can check if a person is an active member of certain memberships
* Added #_IS_PERSON_IN_GROUP{xx} so you can check if a person is a member of certain groups
* Added shortcode eme_person_memberinfo, that works like eme_mymemberinfo but on the EME person being treated (like during mail)
  Takes 3 required arguments: person_id, template_id and membership_id. person_id can be #_PERSONID if used inside another shortcode (that then first replaces #_PERSONID with the required value and then the shortcode is interpreted)

= 2.4.34 (2024/03/10) =
* Fix a newly introduced bug if rsvp cutoff times were floating point numbers

= 2.4.33 (2024/03/08) =
* Remove abandonned 2Checkout payment gateway
* Event DB columns rsvp_number_days and rsvp_number_hours are gone, both are now event properties (in line with some other settings) and called rsvp_end_number_days and rsvp_end_number_hours
* Slovak language contribution by Jozef Gaal
* Make sure no countries with double alpha-2 code and the same language can exist
* Correct the use of the setting "Limit event listing?" It was not working due to a wrong column name in the database query
* Show the people/members of a group when editing that group (and allow filtering/actions as if in the people/member section)
* Added support for event placeholders #_RSVPSTART{xx} and #_RSVPEND{xx}, next to just #_RSVPSTART and #_RSVPEND so you can format the layout to your liking ('xx' being the date/time format in php date notation)

= 2.4.32 (2024/02/23) =
* Fix yet another named parameter usage (typo) causing event listing not to show in some shortcodes

= 2.4.31 (2024/02/23) =
* Fix a new named parameter usage (typo) causing rsvp not to work

= 2.4.30 (2024/02/22) =
* Fix extra charge for payment gateways if eme_payment_gateway_extra_cost was defined
* The dompdf included with EME uses a php-svg-lib version that had a security vulnerability
  The php-svg-lib version has been upgraded to address this (probability to misuse this was low to non-existing in EME)

= 2.4.29 (2024/02/17) =
* Allow to have a folder wp-content/uploads/events-made-easy/includes. Files present in that folder will be included by EME
  This allows for EME extensions and are not overwritten by theme/plugin updates

= 2.4.28 (2024/02/05) =
* Fix payment button for memberships
* Fix weekly recurrence edit

= 2.4.27 (2024/02/01) =
* Correction release: for payments the failure message was shown even after successfull payment (apart from the message, all is correctly handled)

= 2.4.26 (2024/02/01) =
* Added action hook eme_options_postsave_action, to allow own code to be called upon saving options
  This is needed for e.g. custom payment gateways that need a webhook created
* Added option "old_select" for filters, so people wanting the old style of select back, they can get it
  This option defaults to 0, other value: 1
* For multiselects and filter form, the label was not shown anymore: fixed
* Fix task cancel and the use of custom task signup forms
* Braintree API update to 6.16

= 2.4.25 (2024/01/23) =
* Added filters eme_payment_gateway_change_total and eme_payment_gateway_exra_cost.
  Together with the filters eme_payment_gateways, eme_offline_payment_gateways and eme_configured_payment_gateways
  these allow to create custom payment gateways
  This all still needs to be documented though ...
* Fix the category/notcategory option for events when using a name and not an id
* Add shortcde eme_mymemberinfo (see doc: takes template_id and membership_id as parameters and returns the rendered template)

= 2.4.24 (2024/01/15) =
* Fix recurrence creation for specific/repeated months

= 2.4.23 (2024/01/15) =
* Prefill Stripe Payment Form with E-Mail from EME Booking Form (thanks to EweLoHD)

= Older changes: see changelog.txt
