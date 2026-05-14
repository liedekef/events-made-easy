document.addEventListener('DOMContentLoaded', function () {
    const AttendancesTableContainer = EME.$('#AttendancesTableContainer');
    let AttendancesTable;

    // --- Initialize Attendances Table ---
    if (AttendancesTableContainer) {
        AttendancesTable = new FTable('#AttendancesTableContainer', {
            title: emeadmin.translate_attendance_reports,
            paging: true,
            sorting: true,
            sortingResetButton: true,
            multiSorting: true,
            defaultSorting: 'creation_date ASC',
            csvExport: true,
            printTable: true,
            actions: {
                listAction: ajaxurl,
                deleteAction: ajaxurl+'?action=eme_manage_attendances&do_action=deleteAttendances&eme_admin_nonce='+emeadmin.translate_adminnonce
            },
            listQueryParams: () => ({
                action: 'eme_attendances_list',
                eme_admin_nonce: emeadmin.translate_adminnonce,
                search_type: EME.$('#search_type')?.value || '',
                search_start_date: EME.$('[name=search_start_date]')?.value || '',
                search_end_date: EME.$('[name=search_end_date]')?.value || ''
            }),
            fields: {
                id: {
                    key: true,
                    width: '1%',
                    columnResizable: false,
                    title: emeadmin.translate_id,
                    visibility: 'hidden'
                },
                creation_date: {
                    title: emeadmin.translate_attendancedate
                },
                type: {
                    title: emeadmin.translate_type
                },
                person: {
                    sorting: false,
                    title: emeadmin.translate_personinfo
                },
                related_name: {
                    sorting: false,
                    title: emeadmin.translate_name
                }
            }
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
