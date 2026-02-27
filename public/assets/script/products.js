$(document).ready(function () {
    var statusFilters = { '': 'allProductsTable', '1': 'pendingProductsTable', '2': 'approvedProductsTable', '3': 'rejectedProductsTable' };

    Object.entries(statusFilters).forEach(function ([status, tableId]) {
        $('#' + tableId).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listProducts_Admin',
                type: 'POST',
                data: function (d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.status_filter = status;
                }
            },
            columns: [
                { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
                { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }, { data: 10 }
            ],
            order: [],
        });
    });

    // Update status (approve/reject)
    $(document).on('click', '.update-status', function (e) {
        e.preventDefault();
        var id = $(this).attr('rel');
        var status = $(this).data('status');
        var action = status == 2 ? 'approve' : 'reject';

        if (!confirm('Are you sure you want to ' + action + ' this product?')) return;

        $.ajax({
            url: 'updateProductStatus',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), id: id, status: status },
            success: function (res) {
                if (res.status) {
                    Object.values(statusFilters).forEach(function (tid) {
                        $('#' + tid).DataTable().ajax.reload(null, false);
                    });
                }
                alert(res.message);
            }
        });
    });

    // Delete
    $(document).on('click', '.delete', function (e) {
        e.preventDefault();
        var id = $(this).attr('rel');
        if (!confirm('Are you sure you want to delete this product?')) return;

        $.ajax({
            url: 'deleteProduct_Admin',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), id: id },
            success: function (res) {
                if (res.status) {
                    Object.values(statusFilters).forEach(function (tid) {
                        $('#' + tid).DataTable().ajax.reload(null, false);
                    });
                }
                alert(res.message);
            }
        });
    });
});
