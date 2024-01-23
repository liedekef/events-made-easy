=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 5.4
Tested up to: 6.4.2
Stable tag: 2.4.25
Requires PHP: 8.0
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
= 2.4.25 (2024/01/23) =
* Added filters eme_payment_gateway_change_total and eme_payment_gateway_exra_cost.
  Together with the filters eme_payment_gateways, eme_offline_payment_gateways and eme_configured_payment_gateways
  these allow to create custom payment gateways
  Next to that, hooks to admin_init, setup_theme and init are needed to be called to apply possible webhooks and payment notifications as well
  This all still needs to be documented though ...
* Fix the category/notcategory option for events when using a name and not an id
* Add shortcde eme_mymemberinfo (see doc: takes template_id and membership_id as parameters and returns the rendered template)

= 2.4.24 (2024/01/15) =
* Fix recurrence creation for specific/repeated months

= 2.4.23 (2024/01/15) =
* Prefill Stripe Payment Form with E-Mail from EME Booking Form (thanks to EweLoHD)

= 2.4.22 (2023/12/30) =
* Fix #_CONTACT and #_AUTHOR event placeholders if no EME person is linked to that wp id
* Sumup and Mollie API updates
* Prevent some loops when using shortcodes/filters together with other bad-behaving plugins

= 2.4.21 (2023/12/22) =
* Fix/improve usage of WP profile for membership (and others)

= 2.4.20 (2023/12/15) =
* Avoid being able to create an identical person (same name/email) in the backend
* More verifications done when checking for people integrity
* Better person image representation in the backend
* Make frontend member editing also work for dynamic data
* Upgrade dompdf to fix a small security issue
* Mollie update to 2.63
* Convert filter dropdowns to better select

= 2.4.19 (2023/12/12) =
* Added a new recurrence period "specific months", so you can choose on which months something transpires and repeat per year
* Avoid php warnings if an image for an event/person/location/... no longer exists
* Avoid possible XSS via shortcode eme_events and the format option

= 2.4.18 (2023/12/10) =
* Fix a php warning
* Fix registering members from frontend again

= 2.4.17 (2023/12/07) =
* Documented #_YOUNGPENDINGSEATS and #_YOUNGPENDINGSEATS{xx}
* Added #_EVENTLINK (or #_EVENTURL) to just get a link to the event (needs to be made clickable by yourself)
* When updating a recurrence, existing events with bookings will not get removed even if they no longer match the recurrence
* More granular permissions for mail sending/viewing
* event scope "0000-MM" will now show events for the specified month in the future (not necessarily the current year)
* Added event scope "0000-MM--relative+Xm" to show events from a specified month in the future till X months later
* Add new shortcode eme_edit_member_form, which (for logged in user with the needed permissions) allows to edit a member from the frontend (not personal info nor membership info, but only member-entered info concerning the membership which are in fact just custom fields)
* Fix contact pending emails for user-confirmed bookings

= 2.4.16 (2023/11/23) =
* Add scope and always_header_footer param to eme_bookings and eme_attendees shortcodes
* Add #_FREESPACES and #_FREESEATS as aliases for #_AVAILABLESPACES
* When clicking on a location in the location list generated beneath a map, jump to the map
* eme_cat_filter now also supports the category name/slug as param value

= 2.4.15 (2023/11/10) =
* Fix a php warning when sending email, due to newer wp phpmailer version

= 2.4.14 (2023/11/03) =
* Fix dynamic group editing
* Allow adding of dynamic groups of type people or members, and make it more clear which type of group we're editing
* Fix eme_ipn_member_action hook (need to use $member, not $booking)

= 2.4.13 (2023/10/24) =
* Fix for case where bookings are already made and then event switches from single to multiprice

= 2.4.12 (2023/10/24) =
* Add a setting to allow people signing up for tasks to be assigned to groups too
* Take into account case where bookings are already made and then event switches from single to multiprice

= 2.4.11 (2023/10/12) =
* Fix an error when saving an event

= 2.4.10 (2023/10/11) =
* The CSV output generated by the CSV button in the admin backend (when showing tables) now uses the EME generic setting for the CSV seperator 
* Allow all people-placeholders in the task signup form (ADDRESS1, ... too) and also #_GDPR
* The waitinglist is now checked when updating an event too

= 2.4.9 (2023/09/19) =
* The CSV output generated by the shortcode eme_bookings_report_link now uses the EME generic setting for the CSV seperator 
* Fix a typo causing a php warning for membership dynamic fields and the captcha
* Show a hint that mail recipients will be recalculated at send time

= 2.4.8 (2023/09/07) =
* Fix some php warnings when the option to use the client clock for the calendar was set

= 2.4.7 (2023/08/21) =
* quick small fix for 2.4.6

= 2.4.6 (2023/08/21) =
* Fix php warning when adding/updating a member via the admin backend with empty end-date
* Remove many unused variables and cleaned up some typing errors

= 2.4.5 (2023/08/03) =
* Fix 3 php warnings
* Mollie update to 2.60
* Don't show all event custom field answers for every booking in the list of bookings

= 2.4.4 (2023/07/30) =
* Fix showing a single event from the calendar

= 2.4.3 (2023/07/28) =
* Fix placeholder #_IS_LAST_RECURRENCE
* New events scope option: format being: YYYY-MM-DD HH:MM:SS--YYYY-MM-DD HH:MM:SS, so eme_get_events function can search for events in that range (not just days, but taking hour/min/sec into account too);
* Added location capacity, which limits the number of people present at a location. This will be checked for each overlapping event/booking at a location
* Mollie update to 2.59
* Mercadopago update to 2.5.5
* Leaflet update to 1.9.4

= 2.4.2 (2023/07/15) =
* Event list widget was showing urlencoded text (due to the new php-8 function call method, urlencode was no longer needed but forgot to remove it)

= 2.4.1 (2023/07/14) =
* Widget fix that caused php error

= 2.4.0 (2023/07/14) =
* PHP 8 is now the minimal requirement
* Move to named arguments, cleaner code
* Fix creating WP user after creating member/booking (if so configured for the membership/event)

= 2.3.72 (2023/07/08) =
* Make sure no newsletter is sent even due to empty number of entries after conditional placeholders
* When setting max usage count to 0 for a membership (if it was non-zero before), check that it is 0 when doing RSVP too
  (a member could have a usage count >0 beforehand and that would cause booking failures in certain cases)

= 2.3.70 (2023/06/22) =
* A new option is added to a EME person to delete the linked WP user when the user gets trashed
  This option is on by default if EME created the WP user, but a WP admin will never be deleted.
* You can now require in the RSVP settings that a logged in user must be member of a EME group or membership in order to be able to book.
  This avoids the need of using eme_if or the #_ADDBOOKINGFORM_IF_USER_IN_GROUP and #_ADDBOOKINGFORM_IF_USER_IS_MEMBER_OF (which still work btw)
  Thanks to this, a new option can be added to members (see below: 'max usage'), so we can now use a turns-based membership
* New generic placeholder #_ALLCATEGORYIDS and allow eme_for to use this as list-value, so people can loop over categories
* New option for memberships: max usage. If set to something bigger than 0, this will indicate the maximum times a member can RSVP to an event that requires this membership.

= 2.3.69 (2023/06/03) =
* Allow to use captcha only for logged out users
* Show price description in printable booking report
* No longer show the unique payment number in printable booking reports (only usefull in Belgium and for wired transfers anyway)
* Fixed showing the discount name in the printable booking report
* Fixed some php warnings

= 2.3.68 (2023/05/27) =
* List of bookings was empty due to new function being used, fixed now

= 2.3.67 (2023/05/27) =
* Always allow style, span and meta html-tags in formats
* Remove confusing nl2br option for templates
* Reuse mailing for event mails now fills out all mail options too
* Added action hook eme_delete_member_action (executed just before the member gets deleted). 1 param: $member (array)
* Added action hook eme_status_change_action (executed after the member status changes). 2 parasm: $member_id and status
* Action hook eme_ipn_member_action fires for members after payment arrives through a payment gateway (like eme_ipn_action for bookings)
* Placeholders for events can now all start with #_EVENT or #_EVENT_
* Placeholders for locations can now all start with #_LOCATION or #_LOCATION_
* Placeholders for memberships can now all start with #_MEMBERSHIP or #_MEMBERSHIP_
* Placeholders for people can now all start with #_PERSON or #_PERSON_
* Placeholders for members can now all start with #_MEMBER or #_MEMBER_
* Placeholders for bookings can now all start with #_BOOKING or #_BOOKING_

= 2.3.66 (2023/05/10) =
* Add order option for eme_bookings shortcode. The default order is 'people.lastname ASC, people.firstname ASC, bookings.booking_id ASC'.
* Added new placeholder #_IS_INVITE_URL so you can check if an event is being booked for via an invite url
* New option for discounts, so you can chose if a certain discount is only valid for invitation-followed urls
* Invite urls can be sent for any event, not just those marked 'invite only'

= 2.3.65 (2023/04/22) =
* Added actions eme_events_daily_action, eme_members_daily_action and eme_daily_action, which all 3 run on a daily basis without arguments
  Since all 3 run on a daily basis, you could decice to put everything in eme_daily_action, but the distinction might be usefull.
* Fix member dynamic group search (typo resulted in wrong members being returned)
* improve on #_WPUSERDATA{xx} and #_WPUSERMETA{xx}: if field doesn't exist, empty string is returned, if result is an array, all values are concatenated with ", " 

= 2.3.64 (2023/04/10) =
* Add action hook eme_frontend_cancel_booking_action, so you can execute an action before the booking gets cancelled by a user (like e.g. refund)
* Add placeholder #_HAS_USER_REGISTERED{xx} (with 'xx' being one or more event ids, seperated by comma) returns 1 if the current logged in user is registered for one of the mentioned events
* Add new function eme_get_bookings_by_person_id (see the code for arguments)
* Mollie update

= 2.3.63 (2023/03/31) =
* Fix THUMB-placeholders (again)

= 2.3.62 (2023/03/30) =
* Fix some sql statements when searching for custom fields (could impact the number of results found in dynamics groups for example)
* Fix THUMB-placeholders

= 2.3.61 (2023/03/29) =
* Really fix the conditional placeholders #_IS_USER_IN_*
* Mollie update
* Fix thumb showing for people
* Add simplistic membership stats

= 2.3.60 (2023/03/26) =
* Fix the conditional placeholders #_IS_USER_IN_*

= 2.3.59 (2023/03/25) =
* Improvement: French translation updated, thanks to Frédéric M
* Add #_REMEMBERME and #_PHONE as possible placeholers to task signup form too
* Add generic placeholders #_WPUSERDATA{xx} and #_WPUSERMETA{xx}
* Reply-to also set to forced-from if the email from-sender is forced everywhere (in the EME settings)
* Fix discounts without end-date for memberships

= 2.3.58 (2023/03/15) =
* Fix searching for empty strings for custom fields

= 2.3.57 (2023/03/15) =
* Add "-a" option to cli_mail.php, to allow people to bypasspass the massmail setting for certain mailgroups
* Allow to search on empty values for custom fields in members and people overview
* Update Mollie API to 2.51.0
* Add "Remember me" functionality for forms, has a #_REMEMBERME placeholder and a setting that needs to be activated.
  #_REMEMBERME can have an optional lable (#_REMEMBERME{xx}) otherwise "Remember me?" will be used as label
* A comment can be added to task signups (generic comment, will be added to each signup). For this #_COMMENT can be used in the form and emails
* Improvement: German language updates, thanks to Joerg Knoerchen
* Allow to choose wether or not to send mails when approving/deleting task signups
* Fix: tasks for hidden events were not being rendered on public pages when specifying the event id

= 2.3.56 (2023/03/01) =
* Fix an ajax pagination bug

= 2.3.55 (2023/02/26) =
* Resolve two php notices
* Resolve a mailing php notice
* Also exclude mailings to members already subscribed for an event (if the option to do is chosen)

= 2.3.54 (2023/02/21) =
* Fix editing countries
* Fix captcha in forms

= 2.3.53 (2023/02/20) =
* Allow file upload for custom fields for events/locations and memberships too. This allows uploads per event that don't clutter the wp media anymore
* The placeholder #_FIELDVALUE will return the url of the uploaded file (only for single-file uploads), while #_FIELD will return a clickable url-list

= 2.3.52 (2023/02/16) =
* Don't escape the event name too much
* Don't include trashed events in some searches
* Correctly detect offline payment gateway to show the info

= 2.3.51 (2023/02/10) =
* Fix group mailing

= 2.3.50 (2023/02/07) =
* Remove polylang language from the special events page if set, so the language choosen is taken for that page
* Translate full name if it contains language tags
* Interpret language tags in template as early as possible, to avoid nested tags once placeholders get expanded
* Fix #_IS_USER_IN_GROUP{xx,xx,xx}
* Upgrade dompdf to 2.0.3

= 2.3.49 (2023/02/05) =
* "groups" is a reserved keyword in some mysql versions, so avoid using that as a table alias
* Fix 'from'-sender being set incorrectly to blog admin

= 2.3.48 (2023/02/02) =
* Fix an undefined var
* Translate cancel-not-allowed string for multi-language sites
* Upgrade dompdf to 2.0.2
* Due to a bug in php-cs (that 'fixed' code the wrong way), editing an event would cause all task signups to be lost

= 2.3.47 (2023/01/30) =
* Fix attendance scan and also allow people with "Manage attendees" capability to scan and insert attendance records

= 2.3.46 (2023/01/30) =
* Fix people mailing list subscribe
* Fix attendance count after qrcode scan
* Also include booking_id for attendance count, since payment id alone can count for multiple bookings
* Translate the subject for email subscriptions too, not just the mail body
* Fix editing a member's dynamic data if no mail template was defined in the membership for updating a member

= 2.3.44 (2023/01/27) =
* Show task info in event admin overview (identical to RSVP info)
* Implement simple task approval. Approval requirement can be set globally or per event (not per task, allthough in the future this might be)
* Make action eme_trash_person_action work again
* Better user confirmation logic for free events where no further approval is required

= 2.3.43 (2023/01/24) =
* Fix the new placeholders #_MEMBERPAYMENTDATE and #_MEMBERPAYMENTTIME (usage of an not-needed variable caused these not to work)
* Send the "update member" mail also when updating a single member in the backend, not just for the mass action "mark unpaid"
* Try to treat all payment methods the same way, will be easier to add others in the future in a dynamic manner
* Make sure only logged-in users with correct permissions can see the mailings (it was already nonce-protected, but better be safe than sorry)
* If payment is done via payconiq, refund might be possible (payconiq restrictions apply). But: a button will appear in the "payment gw" column, allowing you to get the IBAN number to refund to if done manually
* Fix adding categories to events via the mass-action 

= 2.3.42 (2023/01/19) =
* Added #_MULTIBOOKING_TOTALPRICE_NO_VAT and #_MULTIBOOKING_TOTALPRICE_VAT_ONLY
* Added member placeholders #_MEMBERPAYMENTDATE and #_MEMBERPAYMENTTIME that return the payment date and/or time formatted based on your wp preferences
* Added member placeholders #_MEMBERPAYMENTDATE{xx} and #_MEMBERPAYMENTTIME{xx} so you can format the layout to your liking ('xx' being the date/time format in php date notation)
* Add the "Skip payment option" to memberships (identical as for event bookings)
* Family memberships now have a setting that defines the max number of family members allowed per main member (default 10)
* Allow paymentid to be given as param to wp-admin/admin.php?page=eme-registration-approval and wp-admin/admin.php?page=eme-registration-seats
  so you can create a link to bookings just related to that payment. Example:
  wp-admin/admin.php?page=eme-registration-seats&paymentid=#_PAYMENTID
* Add "Mark paid" button in the table for approved bookings too, similar to the "Mark paid and approve" button in the table for pending bookings
* Fix categories include/exclude in #_EVENTCATEGORIES placeholders (typo in php)

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
