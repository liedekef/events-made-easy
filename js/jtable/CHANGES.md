Short changes:

* Rewritten to use plain jQuery, not jQuery-UI, with HTML5 modal dialogs
* Removed options: dialogShowEffect, dialogHideEffect
* Added option "roomForSortableIcon" (true/false) for sorting, so the sortable icon has room to appear next to the text
  True by default
* Added option "formDialogWidth", which takes a css-width as value, to change the auto-width of the create/edit dialog to something else
* Made Date-field a HTML5 date-field. The old datepicker can be restored using DateJS as type
* added listQueryParams to jtable-call, to indicate parameters to be loaded on
every load-call, can be a function
  Examples:
```
            listQueryParams: {
                    'action': "eme_people_list",
                    'eme_admin_nonce': emepeople.translate_adminnonce,
			}
```
  Or, if you want data evaluated live:
```
            listQueryParams: function () {
                let params = {
                    'action': "eme_people_list",
                    'eme_admin_nonce': emepeople.translate_adminnonce,
                    'trash': $_GET['trash'],
                    'search_person': $('#search_person').val(),
                    'search_groups': $('#search_groups').val(),
                    'search_memberstatus': $('#search_memberstatus').val(),
                    'search_membershipids': $('#search_membershipids').val(),
                    'search_customfields': $('#search_customfields').val(),
                    'search_customfieldids': $('#search_customfieldids').val(),
                    'search_exactmatch': exactmatch
                }
                return params;
            },
```
  The extra param to the load-call itself will add/override params defined in
  listQueryParams. Example:
```
  $('#PeopleTableContainer').jtable('load', {'test':"eee"});
```
* the queryparams for paging and sorting are now also added to the GET/POST as
regular params, no more forced to the url as GET params
