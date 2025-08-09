document.addEventListener('DOMContentLoaded', function () {
    const AttendancesTableContainer = $('#AttendancesTableContainer');
    let AttendancesTable;

    // --- Initialize Attendances Table ---
    if (AttendancesTableContainer) {
        const sortingInfo = document.createElement('div');
        sortingInfo.id = 'attendancestablesortingInfo';
        sortingInfo.style.cssText = 'margin-top: 0px; font-weight: bold;';
        AttendancesTableContainer.insertAdjacentElement('beforebegin', sortingInfo);

        AttendancesTable = new FTable('#AttendancesTableContainer', {
            title: emeattendances.translate_attendance_reports,
            paging: true,
            sorting: true,
            multiSorting: true,
            defaultSorting: 'creation_date ASC',
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_attendances&do_action=deleteAttendances&eme_admin_nonce='+emeattendances.translate_adminnonce
            },
            listQueryParams: () => ({
                action: 'eme_attendances_list',
                eme_admin_nonce: emeattendances.translate_adminnonce,
                search_type: $('#search_type')?.value || '',
                search_start_date: $('#search_start_date')?.value || '',
                search_end_date: $('#search_end_date')?.value || ''
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeattendances.translate_id,
                    visibility: 'hidden'
                },
                creation_date: {
                    title: emeattendances.translate_attendancedate
                },
                type: {
                    title: emeattendances.translate_type
                },
                person: {
                    sorting: false,
                    title: emeattendances.translate_personinfo
                },
                related_name: {
                    sorting: false,
                    title: emeattendances.translate_name
                }
            },
            sortingInfoSelector: '#attendancestablesortingInfo',
            messages: { sortingInfoNone: '' }
        });

        AttendancesTable.load();
    }

    // --- Reload Button ---
    const loadButton = $('#AttendancesLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            AttendancesTable.load();
        });
    }

    // --- Autocomplete for Person ---
    if ($('input[name="chooseperson"]')) {
        let timeout;

        document.addEventListener('click', () => {
            $$('.eme-autocomplete-suggestions').forEach(el => el.remove());
        });

        const input = $('input[name="chooseperson"]');
        input.addEventListener('input', function () {
            clearTimeout(timeout);
            $$('.eme-autocomplete-suggestions').forEach(el => el.remove());

            const value = this.value.trim();
            if (value.length < 2) return;

            timeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('lastname', value);
                formData.append('eme_admin_nonce', emeattendances.translate_adminnonce);
                formData.append('action', 'eme_autocomplete_people');
                formData.append('eme_searchlimit', 'people');

                eme_postJSON(ajaxurl, formData, (data) => {
                    const suggestions = document.createElement('div');
                    suggestions.className = 'eme-autocomplete-suggestions';

                    if (data.length) {
                        data.forEach(item => {
                            const suggestion = document.createElement('div');
                            suggestion.className = 'eme-autocomplete-suggestion';
                            suggestion.innerHTML = `
                                <strong>${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)}</strong>
                                <br><small>${eme_htmlDecode(item.email)}</small>
                            `;
                            suggestion.addEventListener('click', e => {
                                e.preventDefault();
                                if (item.person_id) {
                                    $('input[name="person_id"]').value = eme_htmlDecode(item.person_id);
                                    input.value = `${eme_htmlDecode(item.lastname)} ${eme_htmlDecode(item.firstname)} (${eme_htmlDecode(item.person_id)})  `;
                                    input.readOnly = true;
                                    input.classList.add('clearable', 'x');
                                }
                            });
                            suggestions.appendChild(suggestion);
                        });
                    } else {
                        const noMatch = document.createElement('div');
                        noMatch.className = 'eme-autocomplete-suggestion';
                        noMatch.innerHTML = `<strong>${emeattendances.translate_nomatchperson}</strong>`;
                        suggestions.appendChild(noMatch);
                    }

                    input.insertAdjacentElement('afterend', suggestions);
                });
            }, 500);
        });

        input.addEventListener('keyup', () => {
            $('input[name="person_id"]').value = '';
        });

        input.addEventListener('change', () => {
            if (input.value === '') {
                $('input[name="person_id"]').value = '';
                input.readOnly = false;
                input.classList.remove('clearable', 'x');
            }
        });
    }
});
