<script>
window.SweepDatagrid = window.SweepDatagrid || {
    defaults: function (overrides) {
        return $.extend(true, {
            processing: true,
            responsive: false,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: '<"sweep-dt-toolbar"<"sweep-dt-length"l><"sweep-dt-filter"f>>'
                + 'rt<"sweep-dt-footer"<"sweep-dt-info"i><"sweep-dt-paging"p>>',
            language: {
                search: '<div class="input-group-text d-inline-flex width-3 align-items-center justify-content-center border-bottom-right-radius-0 border-top-right-radius-0 border-right-0"><i class="fal fa-search"></i></div>',
                searchPlaceholder: 'Search…',
            },
            initComplete: function () {
                if (typeof initApp !== 'undefined' && typeof initApp.appForms === 'function') {
                    initApp.appForms('.dataTables_filter', 'has-length', 'has-disabled');
                }
            },
        }, overrides || {});
    },
    initClientGrid: function (selector, overrides) {
        var $table = $(selector);
        if (!$table.length || $.fn.dataTable.isDataTable($table)) {
            return $table.length ? $table.DataTable() : null;
        }

        return $table.DataTable(this.defaults($.extend({
            serverSide: false,
            searching: true,
            paging: true,
            ordering: true,
        }, overrides || {})));
    },
};
</script>
