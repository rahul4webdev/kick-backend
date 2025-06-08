@extends('admin_layouts/main')
@section('pageSpecificCss')
    <link href="{{ asset('assets/bundles/datatables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet">
    <link href="{{ asset('assets/bundles/summernote/summernote-bs4.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/izitoast/css/iziToast.min.css') }}" rel="stylesheet">

@stop
@section('content')
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Sound Category List (<span class="total_sound_category">{{ $total_sound_category }}</span>)
                            </h4>
                        </div>

                        <div class="card-body">
                            <div class="pull-right">
                                <div class="buttons">
                                    <button class="btn btn-primary text-light" data-toggle="modal"
                                        data-target="#soundCategoryModal" data-whatever="@mdo"
                                        @if (Session::get('admin_id') == 2) {{ 'disabled' }} @endif>Add Sound
                                        Category</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="sound-category-listing">
                                    <thead>
                                        <tr>
                                            <th>Sound Category Image</th>
                                            <th>Sound Category Title</th>
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


    <div tabindex="-1" role="dialog" class="modal fade slide-right" id="soundCategoryModal" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content" uib-modal-transclude="">
                <div class="modal-header">
                    <div class="position-relative title-content w-100">
                        <h4 class="modal-title sound_categorytitle">Add SoundCategory</h4>
                        <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body clearfix">

                    <form class="edit-detail-content" id='addUpdateSoundCategory' method="post"
                        enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <input type="hidden" name="sound_category_id" id="sound_category_id" value="">

                        <div class="form-group">
                            <label for="categoryname">Sound Category Name</label>
                            <input type="text" class="form-control" id="sound_category_name" name="sound_category_name"
                                placeholder="Enter Category Name">
                        </div>

                        <div class="form-group">
                            <label for="categoryprofile">Sound Category Profile</label>
                            <input type="file" class="form-control-file file-upload" id="sound_category_profile"
                                name="sound_category_profile">
                            <label id="sound_category_profile-error" class="error image_error"
                                for="sound_category_profile"></label>
                        </div>
                        <div class="form-group preview_soundcatimg">

                        </div>
                        <div class="form-group form-action text-right m-t-10">
                            <a style="color: #fff" class="btn btn-danger btn-md bg-danger sound_category_cancel">Cancel</a>
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
            var dataTable = $('#sound-category-listing').dataTable({
                'processing': true,
                'serverSide': true,
                'serverMethod': 'post',
                "order": [
                    [0, "desc"]
                ],
                'columnDefs': [{
                    'targets': [2],
                    /* column index */
                    'orderable': false,
                    /* true or false */
                }],
                'ajax': {
                    'url': '{{ route('showSoundCategoryList') }}',
                    'data': function(data) {}
                }
            });

            $(document).on('change', '#sound_category_profile', function() {
                CheckFileExtention(this, 'preview_soundcatimg');
            });

            var CheckFileExtention = function(input, cl, flag) {

                if (input.files) {
                    var allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
                    var msg = '.jpeg/.jpg/.png only';

                    if (!allowedExtensions.exec(input.value)) {
                        iziToast.error({
                            title: 'Error!',
                            message: 'Please upload file having extensions' + msg,
                            position: 'topRight'
                        });
                        input.value = '';
                        return false;
                    } else {
                        if (cl.length > 0) {
                            var reader = new FileReader();

                            reader.onload = function(e) {
                                $('.' + cl).html('<div class=""><img src="' + e.target.result +
                                    '" width="150" height="150"/> </div>');
                            }

                            reader.readAsDataURL(input.files[0]);
                        }
                    }
                }
            };

            $('#soundCategoryModal').on('hidden.bs.modal', function(e) {
                $("#addUpdateSoundCategory")[0].reset();
                $('.modal-title').text('Add Sound Category');
                $('#sound_category_id').val("");
                $('.preview_soundcatimg').html('');
                var validator = $("#addUpdateSoundCategory").validate();
                validator.resetForm();
            });

            $("#sound-category-listing").on("click", ".UpdateSoundCategory", function() {
                $('.loader').show();
                $('.modal-title').text('Edit SoundCategory');
                $('#sound_category_id').val($(this).attr('data-id'));
                $('#sound_category_name').val($(this).attr('data-name'));
                var src = $(this).attr('data-src');
                if (src) {
                    $('.preview_soundcatimg').html('<div class=""><img src="' + src +
                        '" width="150" height="150"/> </div>');
                }
                $('.loader').hide();
            });

            $("#addUpdateSoundCategory").validate({
                rules: {
                    sound_category_name: {
                        required: true
                    },
                    sound_category_profile: {
                        required: {
                            depends: function(element) {
                                return ($('#sound_category_id').val() == 0)
                            }
                        }
                    }
                },
                messages: {
                    sound_category_name: {
                        required: "Please enter sound category name"
                    },
                    sound_category_profile: {
                        required: "Please upload image"
                    }
                }
            });

            $(document).on('submit', '#addUpdateSoundCategory', function(e) {
                e.preventDefault();

                var formdata = new FormData($("#addUpdateSoundCategory")[0]);
                $('.loader').show();
                $.ajax({
                    url: '{{ route('addUpdateSoundCategory') }}',
                    type: 'POST',
                    data: formdata,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(data) {
                        $('.loader').hide();
                        $('#soundCategoryModal').modal('hide');
                        if (data.success == 1) {

                            $('#sound-category-listing').DataTable().ajax.reload(null, false);
                            $('.total_sound_category').text(data.total_sound_category);
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

            $(document).on('click', '.DeleteSoundCategory', function(e) {
                e.preventDefault();
                var sound_category_id = $(this).attr('data-id');
                var text = 'You will not be able to recover Sound Category data!';
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
                        closeOnConfirm: false,
                        closeOnCancel: false
                    },
                    function(isConfirm) {
                        if (isConfirm) {
                            $('.loader').show();
                            $.ajax({
                                url: '{{ route('deleteSoundCategory') }}',
                                type: 'POST',
                                data: {
                                    "sound_category_id": sound_category_id
                                },
                                dataType: "json",
                                cache: false,
                                success: function(data) {
                                    $('.loader').hide();
                                    $('#sound-category-listing').DataTable().ajax.reload(
                                        null, false);
                                    $('.total_sound_category').text(data
                                        .total_sound_category);
                                    if (data.success == 1) {
                                        swal("Confirm!", "Sound Category has been deleted!",
                                            "success");
                                    } else {
                                        swal("Confirm!",
                                            "Sound Category has not been deleted!",
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

        });
    </script>

@endsection
