# Events Made Easy

## Description

Events Made Easy is a full-featured event and membership management solution for Wordpress and ClassicPress. Events Made Easy supports public, private, draft and recurring events, membership and locations management, RSVP (+ optional approval), several payment gateways (Paypal, 2Checkout, FirstData, Mollie and others) and OpenStreetMap integration. With Events Made Easy you can plan and publish your event, let people book spaces for your weekly meetings or manage volunteers and memberships. You can add events list, calendars and description to your blog using multiple sidebar widgets or shortcodes; if you are a web designer you can simply employ the placeholders provided by Events Made Easy. 

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
* Templating for emails, event lists, single events, feeds, RSVP forms, ... with specific placeholders for each
* Lots of shortcodes and options
* Payment gateways: Paypal, FirstData, 2CheckOut, Mollie, Payconiq, Worldpay, Stripe, Braintree, Instamojo, Mercado Pago, Fondy, SumUp, Opayo
* Send emails to registered people, automatically send reminders for payments
* Automatically send reminders for memberships that are about to expire or have expired
* Mail queueing and newsletter functionality
* Mailings can be planned in the future, cancelled ... and can include extra attacments
* Multi-site compatible
* Several GDPR assistance features (request, view and edit personal info via link; delete old records for mailings, attendances, bookings)
* Fully localisable and already fully localised in German, Swedish, French and Dutch. Also fully compatible with qtranslate-xt (https://github.com/qtranslate/qtranslate-xt/): most of the settings allow for language tags so you can show your events in different languages to different people. The booking emails also take the choosen language into account. For other multi-lingual plugins, EME provides its own in-text language tags and takes the current chosen language into account.

For documentation on all shortcodes and placeholders, visit the [Official site](https://www.e-dynamics.be/wordpress/) .

## Installation

Always take a backup of your db before doing the upgrade, just in case ...  

#### For new users:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded  
   If the file is too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. Activate the plugin through the 'Plugins' menu in WordPress (make sure your configured database user has the right to create/modify tables and columns) 
5. Add events list or calendars following the instructions in the Usage section.  

#### For existing wordpress users that have version 2.3.18 or older:

1. Download the zip "events-made-easy.zip" from the [latest release on github](https://github.com/liedekef/events-made-easy/releases)
2. Go in the Wordpress 'Plugins' menu, and click on "Add new"
3. Select the zip you downloaded, this will upload the zip and replace the existing installation without losing data  
   If the file is too big, or you need to use FTP/SSH: use your favorite upload tool to upload the contents of the zip file to the `/wp-content/plugins/events-made-easy` directory (remove the old files first)
4. After that, updating the plugin will be as usual in the backend

## Usage

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
 
## Translations and how to translate

You can find the latest versions of language files [here](https://github.com/liedekef/events-made-easy/tree/main/langs)

Here is how to translate any string in a language of your choice:

First, download and install poedit (or any language editor that can handle ".po" files). Then open the .po language file of choice (it is already merged with the “template file” events-made-easy.pot). Then you’ll see all strings not correctly translated/fuzzy/... . It’s very easy (and exactly like the web interface on the wordpress translation site). Once you press save (after you finished editing), poedit created (compiles) the .mo file for you, so you can then send me the .po and .mo file. Or create a pull request on github with the new files.

I sometimes forget to update the language files, so drop me a hint me when you think something is out of date 🙂

## Frequently Asked Questions

See the FAQ section at the [Official site](https://www.e-dynamics.be/wordpress/).
