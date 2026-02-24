=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 3.0.48
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
* Fully localisable and already fully localised in German, Swedish, French and Dutch. Also fully compatible with polylang and [qtranslate-xt](https://github.com/qtranslate/qtranslate-xt/): most of the settings allow for language tags so you can show your events in different languages to different people. The booking emails also take the choosen language into account. For other multi-lingual plugins, EME provides its own in-text language tags and takes the current chosen language into account.

For documentation on all shortcodes and placeholders, visit the [Official site](https://www.e-dynamics.be/wordpress/) .

== Installation ==

Always take a backup of your db before doing the upgrade, just in case ...  

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

== Screenshots ==

1. A map with a pin and popup
2. A view on the list of events
3. Part of the admin menu
4. Recurrence editing of an event

== Changelog ==
= 3.0.48  (2026/02/24) =
* fix edit for people with empty country but filled out state
* snapselect update
* many code edits to become compliant with WP coding style (ongoing, thanks to TommsNL)

= 3.0.47  (2026/02/19) =
* Allow attendance scans per day

= 3.0.46  (2026/02/18) =
* Allow different field html attributes for the admin backend
* Start rename Payconiq to Bancontact-Wero (not visible to users yet)
* Fix a small select-issue for single selects

= 3.0.45  (2026/02/17) =
* Make manual add of attendances work again
* More usage of snapselect for autocomplete fields, caching, members ...
* Small internal fixes

= 3.0.44  (2026/02/14) =
* fix select-caching for event-related emails 
* More intelligent caching for state/country
* Fix total record count for people in ajax search result

= 3.0.43  (2026/02/14) =
* fix snapselect paging arguments and some JS simplications

= 3.0.42  (2026/02/13) =
* fix some select dropdowns

= 3.0.41  (2026/02/13) =
* Use modified snapselect instead of tomselect
* Fix country and state names when editing a person

= 3.0.40  (2026/02/08) =
* Fix csv and printable reports if there's an older discount present

= 3.0.39  (2026/02/05) =
* Payconiq code updates
* Jodit update to 4.8.3
* Allow balloon format per location

= 3.0.38  (2026/01/23) =
* Some class fixes for adding existing person as member via admin interface
* Membership javascript fixes to hide irrelevant fields when appropriate

= 3.0.37  (2026/01/22) =
* Small update because the zip contained unwanted files

= 3.0.36  (2026/01/22) =
* Use native WP filters to update from github (and include screenshots in the plugin 'View details' popup)
* Fix discount print in booking csv and print report

= 3.0.35  (2026/01/08) =
* Better copy/paste for word when jodit is the html editor

= 3.0.34  (2026/01/03) =
* Reintroduce payconiq refunding
* Fix editing bookings with discounts

= 3.0.33  (2026/01/02) =
* Payconiq fix for paid checks

= 3.0.32  (2025/12/31) =
* Braintree API update to 6.31.0
* Fix discount min/max seats logic
* Introduce discount voucher mode

= 3.0.31  (2025/12/15) =
* Ftable update
* fix deleting categories and discounts via mass action

= 3.0.30  (2025/12/12) =
* Really Payconiq update
* Ftable update
* Fdatepicker update
* make bulk action to delete discounts work
* fix for fdatepicker onselect racecondition in start/end for events

= 3.0.29  (2025/12/11) =
* Payconiq update
* Ftable update
* Fix for unselecting date in datepicker for events
* Small newsletter improv if only 1 newsletter exists
* Small CSS fix

= 3.0.28  (2025/12/04) =
* Make sure that bookings moving from waiting list are still in PENDING, not DELETED status
* Fix bulk actions for bookings

= 3.0.27  (2025/12/03) =
* Fix bulk actions for discounts
* Small color improv for eme captcha
* Fix an access setting not being saved
* Fix first day of week for some datepickers

= 3.0.26  (2025/11/14) =
* Fix event scopes this_year--today and this_year--yesterday
* Mollie update to 3.6.0
* ftable update
* small payconiq code simplification

= 3.0.25  (2025/11/09) =
* Payconiq fix

= 3.0.24  (2025/11/04) =
* Mollie update to 3.5.0
* mercadopago update to 3.7.1
* DomPDF update to 3.1.4
* Update jodit to 4.7.4
* Braintree API update to 6.30.0
* Update Paypal API to use direct API calls, no more dependency on any SDK

= 3.0.23  (2025/10/25) =
* DomPDF update to 3.1.3
* Braintree API update to 6.29.0
* Fix tinymce using templates

= 3.0.22  (2025/10/18) =
* Make bulk actions for mailings work again
* FTable and FDatepicker updates

= 3.0.21  (2025/10/05) =
* Autocomplete in frontend now includes birthday
* fix sending mail from within bookings overview

= 3.0.20  (2025/09/29) =
* DomPDF update to 3.1.2
* Show RSVP cutoff calculated date

= 3.0.19  (2025/09/25) =
* DomPDF update to 3.1.1
* More task template choices added per event
* Make task offset work again
* Update fdatepicker
* Add notasks_template_id to shortcode eme_tasks_signupform

= 3.0.18  (2025/09/18) =
* Mercado API update to 3.7.0
* More fields added to task csv export
* Show RSVP start/end calculated date

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
* javascript and jtable updates to more reliable check for checked status of checkboxes
* also fdatepicker code updates and other javascript improvements
* Added the possibility to give a "Maximum usage count per user" for a discount (applies and requires users to be logged in).

= Older changes =
See [changelog.txt on github](https://github.com/liedekef/events-made-easy/blob/main/changelog.txt)
