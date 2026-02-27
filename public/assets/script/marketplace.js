$(document).ready(function () {
    var statusFilters = { '': 'allCampaignsTable', '2': 'activeCampaignsTable', '4': 'completedCampaignsTable', '5': 'cancelledCampaignsTable' };

    Object.entries(statusFilters).forEach(function ([status, tableId]) {
        $('#' + tableId).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'listCampaigns_Admin',
                type: 'POST',
                data: function (d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.status_filter = status;
                }
            },
            columns: [
                { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
                { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }
            ],
            order: [],
        });
    });

    // Delete
    $(document).on('click', '.delete', function (e) {
        e.preventDefault();
        var id = $(this).attr('rel');
        if (!confirm('Are you sure you want to delete this campaign?')) return;

        $.ajax({
            url: 'deleteCampaign_Admin',
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
