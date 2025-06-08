@extends('admin_layouts/main')
@section('pageSpecificCss')
<link href="{{asset('assets/bundles/datatables/datatables.min.css')}}" rel="stylesheet">
<link href="{{asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<link href="{{asset('assets/bundles/summernote/summernote-bs4.css')}}" rel="stylesheet">
<link href="{{asset('assets/bundles/izitoast/css/iziToast.min.css')}}" rel="stylesheet">

@stop
@section('content')
<section class="section">
  <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Profile Category List (<span class="total_profile_category">{{$total_profile_category}}</span>)</h4>
                    </div>
                   
                    <div class="card-body">	
                        <div class="pull-right">
                            <div class="buttons"> 
                                <button class="btn btn-primary text-light" data-toggle="modal" data-target="#profileCategoryModal" data-whatever="@mdo" @if(Session::get('admin_id') == 2){{"disabled"}}@endif>Add Profile Category</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped" id="profile-category-listing">
                                <thead>
                                <tr>
                                  <th>Profile Category Image</th>
                                  <th>Profile Category Title</th>
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


<div tabindex="-1" role="dialog" class="modal fade slide-right" id="profileCategoryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" uib-modal-transclude="">
            <div class="modal-header">
                <div class="position-relative title-content w-100">
                    <h4 class="modal-title profile_categorytitle">Add ProfileCategory</h4>
                    <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            </div>
            <div class="modal-body clearfix">

                <form class="edit-detail-content" id='addUpdateProfileCategory' method="post" enctype="multipart/form-data">
                {{ csrf_field() }}               
                    <input type="hidden" name="profile_category_id" id="profile_category_id" value="">

                    <div class="form-group">
                        <label for="categoryname">Profile Category Name</label>
                        <input type="text" class="form-control" id="profile_category_name" name="profile_category_name" placeholder="Enter Category Name">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoryprofile">Profile Category Profile</label>
                        <input type="file" class="form-control-file file-upload" id="profile_category_image" name="profile_category_image" >
                        <label id="profile_category_image-error" class="error image_error" for="profile_category_image"></label>
                    </div>
                    <div class="form-group preview_profilecatimg">

                    </div>
                    <div class="form-group form-action text-right m-t-10">
                    <a style="color: #fff" class="btn btn-danger btn-md bg-danger profile_category_cancel">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-md m-l-10" @if(Session::get('admin_id') == 2){{"disabled"}}@endif>Submit</button>
                    </div>

                </form>
            </div>
        </div>
	</div>
</div>

@endsection

@section('pageSpecificJs')

<script src="{{asset('assets/bundles/datatables/datatables.min.js')}}"></script>
<script src="{{asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('assets/js/page/datatables.js')}}"></script>
<script src="{{asset('assets/bundles/izitoast/js/iziToast.min.js')}}"></script>
<script src="{{asset('assets/bundles/summernote/summernote-bs4.js')}}"></script>

<script>
$(document).ready(function (){
  var dataTable = $('#profile-category-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [2], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showProfileCategoryList") }}',
        'data': function(data){
        }
    }
  });

  $(document).on('change', '#profile_category_image', function () {
    CheckFileExtention(this,'preview_profilecatimg');
  });

  var CheckFileExtention = function (input, cl, flag) {

    if (input.files) {
        var allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
        var msg = '.jpeg/.jpg/.png only';
        
        if (!allowedExtensions.exec(input.value)) {
            iziToast.error({
                title: 'Error!',
                message: 'Please upload file having extensions'+msg,
                position: 'topRight'
              });
            input.value = '';
            return false;
        } else {
            if(cl.length > 0){
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('.' + cl).html('<div class=""><img src="'+e.target.result+'" width="150" height="150"/> </div>');
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
    }
  };

  $('#profileCategoryModal').on('hidden.bs.modal', function(e) {
      $("#addUpdateProfileCategory")[0].reset();
      $('.modal-title').text('Add Profile Category');
      $('#profile_category_id').val("");
      $('.preview_profilecatimg').html('');
      var validator = $("#addUpdateProfileCategory").validate();
      validator.resetForm();
  });

  $("#profile-category-listing").on("click", ".UpdateProfileCategory", function() {
      $('.loader').show();
      $('.modal-title').text('Edit ProfileCategory');
      $('#profile_category_id').val($(this).attr('data-id'));
      $('#profile_category_name').val($(this).attr('data-name'));
      var src = $(this).attr('data-src');
      if(src){
        $('.preview_profilecatimg').html('<div class=""><img src="'+src+'" width="150" height="150"/> </div>');
      }
      $('.loader').hide();
  });

  $("#addUpdateProfileCategory").validate({
    rules: {
        profile_category_name:{
                required: true    
        },
        profile_category_image:{
            required: {
                depends: function(element) {
                    return ($('#profile_category_id').val() == 0)
                }
            }    
        }   
    },
    messages: {
        profile_category_name: {
            required: "Please enter profile category name"  
         },
        profile_category_image: {
            required: "Please upload image"
        }
    }
  });

  $(document).on('submit', '#addUpdateProfileCategory', function (e) {
    e.preventDefault();
    
    var formdata = new FormData($("#addUpdateProfileCategory")[0]);
    $('.loader').show();
    $.ajax({
        url: '{{ route("addUpdateProfileCategory") }}',
        type: 'POST',
        data: formdata,
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
            $('.loader').hide();
            $('#profileCategoryModal').modal('hide');
            if (data.success == 1) {

              $('#profile-category-listing').DataTable().ajax.reload(null, false);
              $('.total_profile_category').text(data.total_profile_category);
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
        error: function (jqXHR, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
  });

  $(document).on('click', '.DeleteProfileCategory', function (e) {
    e.preventDefault();
    var profile_category_id = $(this).attr('data-id');
    var text = 'You will not be able to recover Profile Category data!';   
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
    function(isConfirm){
        if (isConfirm){
            $('.loader').show();
            $.ajax({
                url: '{{ route("deleteProfileCategory") }}',
                type: 'POST',
                data: {"profile_category_id":profile_category_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#profile-category-listing').DataTable().ajax.reload(null, false);
                    $('.total_profile_category').text(data.total_profile_category);
                    if (data.success == 1) {
                      swal("Confirm!", "Profile Category has been deleted!", "success");
                    } else {
                      swal("Confirm!", "Profile Category has not been deleted!", "error");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
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
