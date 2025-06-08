@extends('admin_layouts/main')
@section('pageSpecificCss')
    <link href="{{ asset('assets/bundles/datatables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet">
@stop
@section('content')
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Gifts (<span class="total_gifts">{{ $total_gifts }}</span>)</h4>
                        </div>
                        <div class="card-body">
                            <div class="pull-right">
                                <div class="buttons">
                                    <button class="btn btn-primary text-light" data-toggle="modal" data-target="#giftsModal"
                                        data-whatever="@mdo"
                                        @if (Session::get('admin_id') == 2) {{ 'disabled' }} @endif>Add Gift</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="gifts-listing">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Price</th>
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
    </section>

    <div tabindex="-1" role="dialog" class="modal fade slide-right" id="giftsModal" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content" uib-modal-transclude="">
                <div class="modal-header">
                    <div class="position-relative title-content w-100">
                        <h4 class="modal-title soundtitle">Add Gift</h4>
                        <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body clearfix">

                    <form class="edit-detail-content" id='addUpdateGift' method="post" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <input type="hidden" name="gift_id" id="gift_id" value="">

                        <div class="form-group">
                            <label>Gift Image</label>
                            <input accept="image/png, image/gif, image/jpeg" type="file" class="form-control"
                                id="gift_image" name="gift_image">
                        </div>

                        <div class="form-group">
                            <label for="coin_price">Gift Price (Coin)</label>
                            <input type="text" class="form-control" id="coin_price" name="coin_price"
                                placeholder="Coin Price">
                        </div>

                        <div class="form-group form-action text-right m-t-10">
                            <a style="color: #fff" class="btn btn-danger btn-md bg-danger sound_cancel">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-md m-l-10"
                                @if (Session::get('admin_id') == 2) {{ 'disabled' }} @endif>Submit</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('pageSpecificJs')

    <script src="{{ asset('assets/bundles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/page/datatables.js') }}"></script>
    <script src="{{ asset('assets/bundles/izitoast/js/iziToast.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            var dataTable = $('#gifts-listing').dataTable({
                'processing': true,
                'serverSide': true,
                'serverMethod': 'post',
                "order": [
                    [0, "desc"]
                ],
                'columnDefs': [{
                    'targets': [0, 1, 2],
                    /* column index */
                    'orderable': false,
                    /* true or false */
                }],
                'ajax': {
                    'url': '{{ route('showGiftsList') }}',
                    'data': function(data) {}
                }
            });


            $('#giftsModal').on('hidden.bs.modal', function(e) {
                $("#addUpdateGift")[0].reset();
                $('.modal-title').text('Add Coin Plan');
                $('#gift_id').val("");
                var validator = $("#addUpdateGift").validate();
                validator.resetForm();
            });

            $("#gifts-listing").on("click", ".updateGift", function() {
                $("#addUpdateGift")[0].reset();
                $('.modal-title').text('Edit Gift');
                $('#gift_id').val($(this).attr('data-id'));
                $('#coin_price').val($(this).attr('data-price'));
                var validator = $("#addUpdateGift").validate();
                validator.resetForm();
            });

            $("#addUpdateGift").validate({
                rules: {
                    gift_image: {
                        required: {
                            depends: function(element) {
                                return ($('#gift_id').val() == 0)
                            }
                        }
                    },
                    coin_price: {
                        required: true
                    },
                },
                messages: {
                    gift_image: {
                        required: "Please select Gift Image."
                    },
                    coin_price: {
                        required: "Please add gift's coin price"
                    },
                }
            });

            $(document).on('submit', '#addUpdateGift', function(e) {
                e.preventDefault();

                var formdata = new FormData($("#addUpdateGift")[0]);
                $('.loader').show();
                $.ajax({
                    url: '{{ route('addUpdateGift') }}',
                    type: 'POST',
                    data: formdata,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(data) {
                        $('.loader').hide();
                        $('#giftsModal').modal('hide');
                        if (data.success == 1) {
                            $('#gifts-listing').DataTable().ajax.reload(null, false);
                            $('.total_gifts').text(data.total_gifts);
                            iziToast.success({
                                title: 'Success!',
                                message: data.message,
                                position: 'topRight'
                            });
                        } else {
                            iziToast.error({
                                title: 'Error!',
                                message: data.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            });


            $(document).on('click', '#deleteGift', function(e) {
                e.preventDefault();
                var item_id = $(this).attr('data-id');
                var text = 'You will not be able to recover this data!';
                var confirmButtonText = 'Yes, Delete it!';
                var btn = 'btn-danger';
                swal({
                        title: "Are you sure?",
                        text: text,
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: btn,
                        confirmButtonText: confirmButtonText,
                        cancelButtonText: "No, cancel please!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function(isConfirm) {
                        if (isConfirm) {
                            $('.loader').show();
                            $.ajax({
                                url: '{{ route('deleteGift') }}',
                                type: 'POST',
                                data: {
                                    "item_id": item_id
                                },
                                dataType: "json",
                                cache: false,
                                success: function(data) {
                                    console.log(data);
                                    $('.loader').hide();
                                    $('#gifts-listing').DataTable().ajax.reload(null,
                                        false);
                                    $('.total_gifts').text(data.total_gifts);
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
