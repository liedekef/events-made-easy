=== Events Made Easy ===  
Contributors: liedekef
Donate link: https://www.e-dynamics.be/wordpress
Tags: events, memberships, locations, bookings, calendars, maps, payment gateways, drip content
Requires at least: 5.4
Tested up to: 6.1
Stable tag: 2.3.19
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage and display events, memberships, recurring events, locations and maps, volunteers, widgets, RSVP, ICAL and RSS feeds, payment gateways support. SEO compatible.
             
== Description ==

**Try it out on your free dummy site: Click here => [https://tastewp.org/plugins/events-made-easy/](https://tastewp.org/plugins/events-made-easy/)**

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

For more information, documentation and support forum visit the [Official site](https://www.e-dynamics.be/wordpress/) .

== Installation ==

Always take a backup of your db before doing the upgrade, just in case ...  
=== For existing wordpress users that have version 2.3.14 or older ===
1. Download first an intermediate version (2.3.20) here: [zip file](https://www.e-dynamics.be/events-made-easy.zip)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded, this will upload the zip and replace the existing installation without losing data
   If the file is too big for uploading, use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory
4. After that, updating the plugin will be as usual in the backend

=== For new users ===
1. Download the latest release from github
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded
   If the file is too big for uploading, use your favorite upload tool to upload the `events-made-easy` folder (inside the zip file) to the `/wp-content/plugins/` directory  
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

Events list and calendars can be added to your blogs through widgets, shortcodes and placeholders. See the full documentation at the [Events Made Easy Support Page](https://www.e-dynamics.be/wordpress/).
 
== Frequently Asked Questions ==

See the FAQ section at [the documentation site](https://www.e-dynamics.be/wordpress).

== Changelog ==
= 2.3.20 (2022/12/23) =
* Second github-based release

= 2.3.19 (2022/12/23) =
* First github-based release

= 2.3.17 (2022/12/22) =
* First PhpSniffer pass fixes

= 2.3.16 (2022/12/20) =
* More code sanitizing to improve security
* JS definition via plugin input is no longer allowed, so options in EME will no longer allow js under any circumstances either

= 2.3.15 (2022/12/19) =
* jquery-timepicker update to 1.14.0
* GDPR mail for change personal info was not send in html (if wanted to do so)
* Added extra security checks on certain ajax calls to make sure no SQL injection of any kind can take place

= 2.3.14 (2022/12/14) =
* Fix a discount list php error in some cases

= 2.3.13 (2022/12/07) =
* Fix a typo introduced in 2.3.12 to check for the sender email address

= 2.3.12 (2022/12/06) =
* Added distance option to eme_events shortcode, if that option and the location_id option are present, you can show "nearby events" with distance in meters
  If using this inside a single event layout format, you can use #_LOCATIONID to get the location id of the current event
* Fix the replyto-sender for emails

= 2.3.11 (2022/11/30) =
* When people paid via a payment gateway, they didn't receive a payment confirmation mail

= 2.3.10 (2022/11/26) =
* Mail blacklisting implemented (see EME mail options)

= 2.3.9 (2022/11/18) =
* Fix booking done from the backend (the current logged in user was used, not the user info set in the rsvp form)
* Fix setting person initial phone when the option to create a wp user was set (due to a wp hook race condition, the EME phone field was emptied)
* Fix contact person email for events

= 2.3.8 (2022/11/18) =
* Holidays now can be specified using a range too, and an optional link, so these are now possible:
  2022-12-25,Christmas
  2022-12-20--2023-01-02,Christmas holidays
  2022-12-25,Christmas,my_extra_css_class
  2022-12-25,Christmas,my_extra_css_class,link_to_the_holiday_info
* Fix deleting a booking (the related payment was not deleted due to a typo)
* Change the email functions (eme_send_email, eme_queue_mail, ...) to also have from-name and email as an extra optional argument (if not present, the reply_to will be taken as usual)
* Add an extra option in the email configuration to use the default sender as "forced" sender/email (by default true if anything is entered for existing setups)
* If "forced" sender is not active, you can now change the "from" for generic emails

= 2.3.7 (2022/11/11) =
* Fix a bug introduced in 2.3.6 for events that had a multi-price setting without multi-seat (which is possible, e.g. to just have children pay less)

= 2.3.6 (2022/11/11) =
* Update dompdf to 2.0.1
* Allow CSV payment imports for bookings, three fields need to be present: payment_date, amount and one of the following 3: payment_id, payment_randomid or unique_nbr
* Allow #_TRANSFER_NBR_BE97 (deprecated) or #_UNIQUE_NBR for both booking and member placeholders
* Allow to show the unique nbr for members in the admin interface
* Make sure the correct discount value is taken into account upon update of a booking
* When editing a booking where a seat field is hidden (using eme_if), that seat was set to 0. This is now fixed by taking the current seat value for missing seats
* payment div id changed to avoid double ids: the id eme-payment-formtext is now eme-payment-formtext-header (or eme-payment-formtext-footer where relevant)
* payment database table change to have less double info (member id was stored in the payment table, the payment id was stored in the members table which is double info)

= 2.3.5 (2022/10/25) =
* Enqueue for forgotten shortcodes

= 2.3.4 (2022/10/23) =
* Keep payment info, even for trashed (cancelled bookings), this allows to return an "already cancelled" message when someone tries to cancel two times
* Fix a membership page check if dripped content option was used
* Only enqueue scripts and styles if needed

= 2.3.3 (2022/10/18) =
* Don't show signup checkbox for tasks past their end date
* Allow the start/end date of all tasks to be changed at once (usefull if you have a lot of tasks for an event and you copy the event to a new date)
* Fix mass action setting new recurrence start date if end date is empty
* For file uploads with dynamic data and multiple upload fields: only show the uploads relevant for each field (and not all)
* Dynamic data improvement when editing a booking in the backend (changing the seats or comment was not taken into account)

= 2.3.2 (2022/10/15) =
* Fix people update php warning
* New field type (multiple files upload) to allow to upload multiple files in one go
* Show uploaded files instead of the upload formfield when appropriate (and not a list of uploaded files at the top)

= 2.3.1 (2022/10/08) =
* For generic mails, the option to send to all people is no longer selected by default. This causes less mistakes
* Fix Stripe checkout for newer api keys
* Fix discount import when discount groups are used as well
* Update Stripe API to 9.6.0
* Update Mollie API to 2.46.0

= 2.3.0 (2022/10/02) =
* Improve the people cleanup function (take task signups into account as well)
* When editing a person in the backend, check the mail address validity too
* Improve the initials returned by #_INITIALS
* Fix dynamic data based on dropdown fields where the field tags are different than the field values
* For eme_attendees and eme_bookings: if the id-option is not given and inside a single event view, then the id of that event is taken

= 2.2.99 (2022/09/25) =
* Also allow #_FAMILYCOUNT and #_FAMILYMEMBERS for people placeholders
* Add multiprice_template_id to shortcode eme_add_multibooking_form (used as template in the list of events in a multibooking form if the event is multiprice and the value is not 0, the default is 0 in which caste template_id is used as usual). This allows mixing multiprice and regular price events in one multibooking form.
* Fix sending event mails to groups 

= 2.2.98 (2022/09/16) =
* Small fix for custom fields of type person and RSVP form
* Add "eventStatus" to list of fields for google event headers
* Make sure event description is not empty in list of fields for google event headers
* Performer google event header is added if a custom field with name "performer" and purpose "event" exists and has a non-empty value for the event

= 2.2.97 (2022/09/08) =
* Add rsvp placeholder #_IS_USERPENDING
* Allow members, member groups and membership selection for event related emails too
* Take multibooking into account for bookings needing user confirmation
* Add option "Pause between mails"
* Fix placeholders #_TASKSTARTDATE and #_TASKENDDATE

= 2.2.96 (2022/08/17) =
* Forgot to update the dompdf path for templates, this fixes it

= 2.2.95 (2022/08/16) =
* Fix creating tables (the previous version used a new method to detect the table prefix more dynamically but that caused troubles for new installations)
* Correctly detect that #_EMAIL and #_LASTNAME are present in the RSVP form (also handle the usecase #_EMAIL{xx} and #_LASTNAME{xx})
* New event status "Unlisted", causing an event to be public but not to appear in the list of events (so less restrictive than private events)
* Make #_IS_USER_IN_GROUP{xx} also work for dynamic groups
* Typo fix for ignore_pending events and free space check upon payment
* Update dompdf to 2.0.0
* Update Mollie API to 2.45.0
* Update Braintree API to 6.9.0
* Update Mercadopago API to 2.4.9
* Update Stripe API to 9.1.0
* Update Instamojo API to 1.0
* Making a template copy is now possible
* Added filter eme_offline_payment_gateways (returns a list of gateways that can only be paid offline) and eme_configured_payment_gateways (returns a list of actual configured gateways, not all available). Both filters are usefull for creating custom payment gateways in the future
* Added placeholder #_MEMBERSHIPS, returning a comma-separated list of memberships a person is in
* Upgrade Leaflet to 1.8.0 . Support Ukraine!!!
  
= 2.2.94 (2022/08/07) =
* Fix order for #_ALLEVENTS, #_PASTEVENTS and #_NEXTEVENTS
* The order param for eme_people now can order on custom fields too, you'll need to know the field id and then as example to order on field with id 4 use FIELD_4:
  [eme_people order="FIELD_4 ASC, LASTNAME, FIRSTNAME"]
* Multisite data sharing support, allowing subsites to use the data from the main site (not the options). This is not needed if no data sharing is required (and is off by default).
  Planned actions will only be executed in the main site.

= 2.2.93 (2022/08/01) =
* Fix a regular expression for payment placeholder replacements, causing possible php warnings to pop up

= 2.2.92 (2022/07/30) =
* Make sure the task-form setting is saved when being modified in the options
* #_TASKBEGIN and #_TASKEND now also can have an optional datetime argument (php style), if not the generic style for date+time is used
  Example: #_TASKBEGIN{j M H:i}

= 2.2.91 (2022/07/21) =
* Fix missing closing div, causing several payment methods to be grouped under the added Opaya one.

= 2.2.90 (2022/07/19) =
* Fix mass action to delete discount from group (it in fact added it, wrong function call ...)
* Added Opayo payment gateway (formerly known as Sage Pay)
* Add rsvp email action eme_rsvp_email_action (4 params: the booking, the action, the calculated person email subject and body)
  This way you can hook into the mail action and based on the action itself, execute custom code (like sending a mail). See the doc for an example
* Added member email action eme_member_email_action (also 4 params: the member, the action, the calculated member email subject and body)
  See the doc for an example
* If creating a new event/membership and there's only one payment gateway configured, select it by default
* The filterform can now have #MULTIPLE_ and #SINGLE_ prefixes, forcing a certain search field to be multiple while others not. For custom fields you can force a dropdown field to be single too using this, even if the definition of the dropdown is a multi-dropdown select (not the other way around)
* The formfields #_PHONE, #_CITY, #_ZIP or #_POSTAL, #_ADDRESS1, #_ADDRESS2, #_NAME, #_FIRSTNAME, #_BIRTHPLACE, #_BORTHDATE #_EMAIL, #_COMMENT and #_CANCELCOMMENT now have an extra argument that you can use to set the html placeholder on the field, if not used a default placeholder will be shown. Example: #_ADDRESS2{House number} will show "House number" as placeholder, while otherwise "Address line 2" will be shown as placeholder
* #_POSTAL is added as an alternative for #_ZIP (and the default translation is now "Postal code" since that's more international than "Zip")
* Convert discount group names to ids when importing discounts

= 2.2.89 (2022/07/08) =
* Added author and contact_person options to the eme_locations_map shortcode too
* Wordpress page access can now also be limited to EME groups, not just EME memberships
* Small payconiq correction
* Fix #_CURDATE and #_CURTIME
* Update Stripe API to 8.9.0
* Update Mollie API to 2.44.1
* Correct members state calculation for "forever" memberships when payment arrives

= 2.2.88 (2022/07/01) =
* Added author and contact_person options to the eme_locations shortcode, but only used if eventful=1 and then it gets locations with events (where events then match the author and/or contact person). If eventful=0 (default), the contact_person is ignored but location author is taken into account
* Document #_BIRTHDAY_EMAIL, #_BIRTHDATE and #_BIRTHPLACE
* Added generic placeholder #_DATE{xx}{yy} with "xx" being a php date expression (like e.g. "+7 days") and "yy" being the wanted format (can be left out, then the generic format for dates is used). This allows to create templates to send a mail to persons saying things like "this is valid until 7 days from now".
* Fix some searching functionality in admin backends (for discounts, countries, rsvp)

= 2.2.87 (2022/06/24) =
* To have less conflicts, #_EVENTFIELD{xx}, #_LOCATIONFIELD{xx}, #_BOOKINGFIELD{xx}, #_ATTENDFIELD{xx}, #_RESPFIELD{xx} and #_PERSONFIELD{xx} have been renamed to #_EVENTDBFIELD{xx}, #_LOCATIONDBFIELD{xx}, #_BOOKINGDBFIELD{xx}, #_ATTENDDBFIELD{xx}, #_RESPDBFIELD{xx} and #_PERSONDBFIELD{xx}
* The placeholders #_IMAGE and #_IMAGEURL now only works for people, no longer for locations. One should use #_LOCATIONIMAGE as placeholder for locations
* Fix showing "pending" string in the CSV export for multiseat events 
* Show booking status (approved, pending, waiting list, awaiting user confirmation) as separate column in csv/printable booking report
* Fix debug statement causing discounts to not work as expected

= 2.2.86 (2022/06/17) =
* Fix closing form-tag for cancel form
* Fix discount import without start/end dates

= 2.2.85 (2022/06/14) =
* Allow slug to be changed when editing recurring events as well
* Added SumUp as payment gateway
* Fix locations shortcode (notcategory param was not taken correctly into account)
* Fix valid_from/to checks for imported discounts

= 2.2.84 (2022/06/06) =
* Fix #_LINKEDNAME (due to a typo, it was no longer showing the link at all)

= 2.2.83 (2022/06/05) =
* Add offset option to eme_events shortcode
* The birthday calendar picker will start with years first
* #_LINKEDNAME will create a url that opens in an new tab for events if the url is external

= 2.2.82 (2022/05/29) =
* Add notcategory as an option for eme_locations and eme_locations_map shortcodes
* Make sorting work again in members and membership overview (small bug introduced in 2.2.81)
* Fix list of people (reason was the use of wordpress function sanitize_sql_orderby which is not perfect ...)

= 2.2.81 (2022/05/27) =
* More potential sql fixes (not proven, but better safe than sorry), thanks to Erwan via https://wpscan.com

= 2.2.80 (2022/05/27) =
* Security update: fix SQL injection with unescaped lang variable (reported by Dave via https://wpscan.com)
  Users are advised to update to the latest version immediately!

= 2.2.79 (2022/05/21) =
* Fix import of custom field answers for locations
* People birthday emails were being sent 2 times
* Added booking mailfilters userconfirmation_pending_subject/body

= 2.2.78 (2022/05/16) =
* Fix leftover php issue in ExpressiveDate.php so it works with php 8.1 and older

= 2.2.77 (2022/05/15) =
* Fix some php issues (trying to be ok with php 8.1 seems more daunting than expected)

= 2.2.76 (2022/05/15) =
* Add filter eme_wp_userdata_filter, which allows you to set extra info for the WP user being created after a booking (if that option is set)
  The current EME person is given as argument (array), the result should be an array that is accepted by wp_update_user
* Fix waitinglist management in case a booking is not paid for but booking approval is not required
* Check free seats just before the payment form is shown, in case pending bookings are considered as free we need to make sure at the moment of payment seats are actually available
* If the option is set to consider pending bookings as free seats, pending bookings younger than 5 minutes are considered as occupied seats as well as to avoid possible clashes with slow payments (only if online payment for that event is possible)
* Added the possibility to filter on category in bookings overview
* Added #_YOUNGPENDINGSEATS: gives the number of pending seats younger than 5 minutes for an event (those are counted as occupied too, even if pending seats are considered as free)
* Added #_YOUNGPENDINGSEATS{xx} gives the number of pending seats younger than 5 minutes for the xx-th seat category for a multi-seat event
* Include all member attachments in mails to the contact person

= 2.2.75 (2022/05/06) =
* Fix BCC mail sending
* Add option for newsletter sub/unsub per person too (the automatic mail for new events)
* Add extra SEO permalink prefixes for calendar and payments
* Added placeholder #_BOOKING_CONFIRM_URL (which gives a nicer link for a booker to confirm his booking if permalinks are active, not just the payment url)

= 2.2.74 (2022/04/25) =
* Allow filter on email settings, so you can e.g. change server/port/... based on the 'to'
  The filter is called eme_filter_mail_options and takes 1 array as argument:
     $mail_options=array(
           'fromMail',
           'fromName',
           'toMail',
           'toName',
           'replytoMail',
           'replytoName',
           'bcc_addresses',
           'mail_send_method', // smtp, mail, sendmail, qmail, wp_mail
           'send_html',        // true or false
           'smtp_host',        
           'smtp_encryption',  // none, tls or ssl
           'smtp_verify_cert', // true or false
           'smtp_port',        
           'smtp_auth',        // 0 or 1, false or true
           'smtp_username',
           'smtp_password', 
           'smtp_debug',      // true or false
   );
  The return should be the filtered (changed) array
* Optionally show event categories in bookings
* Make "#_SINGLE_EVENTPAGE_EVENTID" placeholder work as expected
* Add generic placeholder #_SINGLE_EVENTPAGE_LOCATIONID, returning the location id of the event currently being shown (so you can e.g. show a location map of the current event in a widget)
* Add generic placeholder #_SINGLE_LOCATIONPAGE_LOCATIONID, returning the location id of the location currently being shown (so you can e.g. show a location map of the current location in a widget)
* #_PAYMENT_URL placeholder (also used to have the user confirm a booking) was not showing the link if online payment for the event was not possible

= 2.2.73 (2022/04/15) =
* Leaflet JS will now also switch to dark mode if your browser is set that way
* Allow direct template input for memberships too, without needing to define separate templates
* Make add-tasks work again (javascript error sneaked in)

= 2.2.72 (2022/04/11) =
* Delete old answers for custom fields for people if appropriate (when the custom formfield is no longer present) if updating the person in the backend
* Allow 0/empty as value for planned actions "Schedule the automatic removal of unpaid pending bookings older than" and "Schedule the automatic removal of unconfirmed bookings older than"
* Correct Donate button

= 2.2.71 (2022/04/09) =
* Better plan cron (WP only takes 24 hours in a day, not taking summer/winter timezones into account)
* Birthday mails needs to be sent for people indicated to do so, despite the default setting for new people

= 2.2.70 (2022/04/08) =
* Update Stripe API to 7.121.0
* Update Braintree API to 6.8.0
* Update Fondy API to 1.0.9.1
* Update Mercadopago API to 2.4.5
* Update Mollie API to 2.41.0
* Remove Paymill and Sagepay payment gateways
* Implement Braintree refunding
* Show Worldpay callback url in the payment options page
* Add RSVP placeholder #_WAITINGLIST_POSITION, returning the position of a booking on the waiting list
* Implement birthday functionality with new people placeholder #_BIRTHDAY_EMAIL, can be used to show a form field (yes/no) to allow people to indicate they want an email (or not) or as text info (Yes/No text) 

Older changes can be found in changelog.txt
