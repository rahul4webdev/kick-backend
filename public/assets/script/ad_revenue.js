$(document).ready(function () {
    // Load stats
    $.post('fetchAdRevenueStats', {_token: $('meta[name="csrf-token"]').attr('content')}, function (data) {
        $('#statImpressions').text(Number(data.total_impressions).toLocaleString());
        $('#statRevenue').text('$' + Number(data.total_revenue).toFixed(2));
        $('#statEnrolled').text(data.total_enrolled);
        $('#statPending').text(data.total_pending);
        $('#statPaidOut').text('$' + Number(data.total_paid_out).toFixed(2));
        $('#statPlatformRev').text('$' + Number(data.platform_revenue).toFixed(2));
    });

    // Enrollments DataTable
    var enrollmentsTable = $('#enrollmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'listAdRevenueEnrollments',
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            {data: 0}, {data: 1}, {data: 2}, {data: 3}, {data: 4},
            {data: 5}, {data: 6}, {data: 7}, {data: 8}
        ],
        order: [[7, 'desc']],
        language: {emptyTable: 'No enrollments found'}
    });

    // Payouts DataTable
    var payoutsTable;
    $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        if (e.target.id === 'v-pills-payouts-tab' && !payoutsTable) {
            payoutsTable = $('#payoutsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'listAdRevenuePayouts',
                    type: 'POST',
                    data: function (d) {
                        d._token = $('meta[name="csrf-token"]').attr('content');
                    }
                },
                columns: [
                    {data: 0}, {data: 1}, {data: 2}, {data: 3},
                    {data: 4}, {data: 5}, {data: 6}, {data: 7}
                ],
                order: [[1, 'desc']],
                language: {emptyTable: 'No payouts found'}
            });
        }
    });
});

function updateEnrollment(id, status) {
    var action = status === 1 ? 'approve' : 'reject';
    if (!confirm('Are you sure you want to ' + action + ' this enrollment?')) return;

    $.post('updateAdRevenueEnrollment', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        enrollment_id: id,
        status: status
    }, function (data) {
        if (data.status) {
            $('#enrollmentsTable').DataTable().ajax.reload();
            toastr.success(data.message);
        } else {
            toastr.error(data.message);
        }
    });
}

function processMonthly() {
    if (!confirm('Process monthly ad revenue payouts for last month? This will credit coins to enrolled creators.')) return;

    $.post('processMonthlyAdRevenue', {
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (data) {
        if (data.status) {
            toastr.success(data.message + ' (' + data.data.creators_processed + ' creators)');
            location.reload();
        } else {
            toastr.error(data.message);
        }
    });
}
