import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';

$(function() {
    "use strict";

    $('.datatable').DataTable({
        language: { url: "https://cdn.datatables.net/plug-ins/2.1.0/i18n/ca.json" },
        paging: false,
        order: []
    });
});
