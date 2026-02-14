document.addEventListener('DOMContentLoaded', function () {
    const AttendancesTableContainer = EME.$('#AttendancesTableContainer');
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
                search_type: EME.$('#search_type')?.value || '',
                search_start_date: EME.$('#search_start_date')?.value || '',
                search_end_date: EME.$('#search_end_date')?.value || ''
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
    const loadButton = EME.$('#AttendancesLoadRecordsButton');
    if (loadButton) {
        loadButton.addEventListener('click', e => {
            e.preventDefault();
            AttendancesTable.load();
        });
    }
});
