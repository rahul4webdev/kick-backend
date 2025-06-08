@extends('admin_layouts/main')
@section('pageSpecificCss')
    <link href="{{ asset('assets/bundles/datatables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet">
    <link href="{{ asset('assets/bundles/summernote/summernote-bs4.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/izitoast/css/iziToast.min.css') }}" rel="stylesheet">
1
@stop
@section('content')
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Verification Request List (<span
                                    class="total_verification_request">{{ $total_verification_request }}</span>)</h4>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="verification-request-listing">
                                    <thead>
                                        <tr>
                                            <th>PhotoId Image</th>
                                            <th>Photo With Id Image</th>
                                            <th>User Name</th>
                                            <th>Id Number</th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Created Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@section('pageSpecificJs')

    <script src="{{ asset('assets/bundles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/page/datatables.js') }}"></script>
    <script src="{{ asset('assets/bundles/izitoast/js/iziToast.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            var dataTable = $('#verification-request-listing').dataTable({
                'processing': true,
                'serverSide': true,
                'serverMethod': 'post',
                "order": [
                    [0, "desc"]
                ],
                'columnDefs': [{
                    'targets': [7, 8],
                    /* column index */
                    'orderable': false,
                    /* true or false */
                }],
                'ajax': {
                    'url': '{{ route('showVerificationRequestList') }}',
                    'data': function(data) {}
                }
            });

            $(document).on('click', '.VerifyRequest', function(e) {
                e.preventDefault();
                var verification_request_id = $(this).attr('data-id');
                var text = 'Your verification request confirm!';
                var confirmButtonText = 'Yes, Confirm it!';
                var btn = 'btn-success';
                swal({
                        title: "Are you sure?",
                        text: text,
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: btn,
                        confirmButtonText: confirmButtonText,
                        cancelButtonText: "No, cancel please!",
                        closeOnConfirm: false,
                        closeOnCancel: false
                    },
                    function(isConfirm) {
                        if (isConfirm) {
                            $('.loader').show();
                            $.ajax({
                                url: '{{ route('verifyRequest') }}',
                                type: 'POST',
                                data: {
                                    "verification_request_id": verification_request_id
                                },
                                dataType: "json",
                                cache: false,
                                success: function(data) {
                                    $('.loader').hide();
                                    $('#verification-request-listing').DataTable().ajax
                                        .reload(null, false);
                                    $('.total_verification_request').text(data
                                        .total_verification_request);
                                    if (data.success == 1) {
                                        swal("Confirm!",
                                            "Your verification request has been confirm!",
                                            "success");
                                    } else {
                                        swal("Confirm!",
                                            "Verification Request has not been confirm!",
                                            "error");
                                    }
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    alert(errorThrown);
                                }
                            });
                        } else {
                            swal("Cancelled", "Your imaginary file is safe :)", "error");
                        }
                    });
            });


            $(document).on('click', '.DeleteVerificationRequest', function(e) {
                e.preventDefault();
                var verification_request_id = $(this).attr('data-id');

                swal({
                    title: "Are you sure?",
                    text: "You want set your reject message:",
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    confirmButtonClass: 'btn-danger',
                    confirmButtonText: 'Yes, reject it!',
                    animation: "slide-from-top",
                    inputPlaceholder: "Enter message"
                }, function(message_text) {
                    if (message_text === false) return false;
                    if (message_text === "") {
                        swal.showInputError("Enter message here");
                        return false
                    } else {
                        $.ajax({
                            url: '{{ route('deleteVerificationRequest') }}',
                            data: {
                                verification_request_id: verification_request_id,
                                message_text: message_text
                            },
                            type: 'POST',
                            dataType: "json",
                            cache: false,
                            success: function(data) {
                                $('.loader').hide();
                                $('#verification-request-listing').DataTable().ajax
                                    .reload(null, false);
                                $('.total_verification_request').text(data
                                    .total_verification_request);
                                if (data.success == 1) {
                                    swal("Confirm!",
                                        "Verification Request has been deleted!",
                                        "success");
                                } else {
                                    swal("Confirm!",
                                        "Verification Request has not been deleted!",
                                        "error");
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alert(errorThrown);
                            }
                        });
                    }
                });
            });

        });
    </script>

@endsection
