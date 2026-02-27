$(document).ready(function () {
    var statusFilters = { '': 'allOrdersTable', '0': 'pendingOrdersTable', '1': 'confirmedOrdersTable', '3': 'deliveredOrdersTable', '4': 'cancelledOrdersTable' };

    Object.entries(statusFilters).forEach(function ([status, tableId]) {
        $('#' + tableId).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listProductOrders_Admin',
                type: 'POST',
                data: function (d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.status_filter = status;
                }
            },
            columns: [
                { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
                { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }
            ],
            order: [],
        });
    });
});
