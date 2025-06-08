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
                        <h4>Sound List (<span class="total_sound">{{$total_sound}}</span>)</h4>
                    </div>
                   
                    <div class="card-body">	
                        <div class="pull-right">
                            <div class="buttons"> 
                                <button class="btn btn-primary text-light" data-toggle="modal" data-target="#soundModal" data-whatever="@mdo" @if(Session::get('admin_id') == 2){{"disabled"}}@endif>Add Sound</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped" id="sound-listing">
                                <thead>
                                <tr>
                                  <th>Sound Image</th>
                                  <th>Sound</th>
                                  <th>Sound Category</th>
                                  <th>Sound Title</th>
                                  <th>Duration</th>
                                  <th>Singer</th>
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


<div tabindex="-1" role="dialog" class="modal fade slide-right" id="soundModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" uib-modal-transclude="">
            <div class="modal-header">
                <div class="position-relative title-content w-100">
                    <h4 class="modal-title soundtitle">Add Sound</h4>
                    <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            </div>
            <div class="modal-body clearfix">

                <form class="edit-detail-content" id='addUpdateSound' method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                    <input type="hidden" name="sound_id" id="sound_id" value="">

                    <div class="form-group">
                        <label for="categoryname">Sound Category</label>
                        <select class="form-control" id="sound_category_id" name="sound_category_id">
                            <option value="">Select Category</option>
                            <?php
                            foreach ($sound_category_data as $value) {
                                ?>
                                <option value="{{$value['sound_category_id']}}">{{$value['sound_category_name']}}</option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
            
                    <div class="form-group">
                        <label for="categoryname">Sound Title</label>
                        <input type="text" class="form-control" id="sound_title" name="sound_title" placeholder="Sound Title">
                    </div>
                        
                    <div class="form-group">
                        <label for="categoryname">Singer</label>
                        <input type="text" class="form-control" id="singer" name="singer" placeholder="Singer">
                    </div>
            
                    <div class="form-group">
                        <label for="categoryname">Duration</label>
                        <input type="text" class="form-control" id="duration" name="duration" placeholder="Duration">
                    </div>

                    <div class="form-group">
                        <label for="categoryprofile">Sound</label>
                        <input type="file" class="form-control-file file-upload1" id="sound" name="sound">
                        <label id="sound-error" class="error sound_error" for="sound"></label>
                    </div>
                    <div class="form-group preview_sound">

                    </div>
                    <div class="form-group">
                        <label for="categoryprofile">Sound Image</label>
                        <input type="file" class="form-control-file  file-upload" id="sound_image" name="sound_image">
                        <label id="sound_image-error" class="error image_error" for="sound_image"></label>
                    </div>
                    <div class="form-group preview_soundimg">

                        </div> 
                    <div class="form-group form-action text-right m-t-10">
                    <a style="color: #fff" class="btn btn-danger btn-md bg-danger sound_cancel">Cancel</a>
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

<script>
$(document).ready(function (){
  var dataTable = $('#sound-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [6], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showSoundList") }}',
        'data': function(data){
        }
    }
  });

  $(document).on('change', '#sound', function () {
    CheckFileExtention(this,'preview_sound',1);
  });

  $(document).on('change', '#sound_image', function () {
    CheckFileExtention(this,'preview_soundimg',2);
  });

  var CheckFileExtention = function (input, cl, flag) {

    if (input.files) {
        if(flag == 1){
            var allowedExtensions = /(\.mp3|\.aac)$/i;
            var msg = '.mp3/.aac only';
        }else{
            var allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
            var msg = '.jpeg/.jpg/.png only';
        }
        
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
                    if(flag == 1){
                        $('.' + cl).html('<audio controls> <source src="'+e.target.result+'" type="audio/mpeg"> </audio>');
                    }else{
                        $('.' + cl).html('<div class=""><img src="'+e.target.result+'" width="150" height="150"/> </div>');
                    }
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
    }
  };

  $('#soundModal').on('hidden.bs.modal', function(e) {
      $("#addUpdateSound")[0].reset();
      $('.modal-title').text('Add Sound');
      $('#sound_id').val("");
      $('.preview_sound').html('');
      $('.preview_soundimg').html('');
      var validator = $("#addUpdateSound").validate();
      validator.resetForm();
  });

  $("#sound-listing").on("click", ".UpdateSound", function() {
      $('.loader').show();
      $('.modal-title').text('Edit Sound');
      $('#sound_id').val($(this).attr('data-id'));
      var sound_id = $(this).attr('data-id');
      $.ajax({
          url: '{{ route("getSoundByID") }}',
          type: 'POST',
          data: {sound_id:sound_id},
          dataType: "json",
          cache: false,
          success: function (data) {
              $('.loader').hide();
              $('#sound_category_id').val(data.sound_category_id);
              $('#sound_title').val(data.sound_title);
              $('.preview_sound').html('<audio controls> <source src="'+data.sound+'" type="audio/mpeg"> </audio>');
              $('.preview_soundimg').html('<div class=""><img src="'+data.sound_image+'" width="150" height="150"/> </div>');
              $('#singer').val(data.singer);
              $('#duration').val(data.duration);
          },
          error: function (jqXHR, textStatus, errorThrown) {
              alert(errorThrown);
          }
      });
  });

  $("#addUpdateSound").validate({
    rules: {
        sound_category_id:{
                required: true    
        },
        sound_title:{
            required: true    
        },
        sound:{
            required: {
                depends: function(element) {
                    return ($('#sound_id').val() == 0)
                } 
            }
        }, 
        sound_image:{
            required: {
                depends: function(element) {
                    return ($('#sound_id').val() == 0)
                } 
            }  
        }   
    },
    messages: {
        sound_category_id: {
            required: "Please enter sound category"  
         },
         sound_title: {
            required: "Please enter sound title"  
         },
         sound:{
            required: "Please upload sound",    
        },
        sound_image: {
            required: "Please upload image"
        }
    }
  });

  $(document).on('submit', '#addUpdateSound', function (e) {
    e.preventDefault();
    
    var formdata = new FormData($("#addUpdateSound")[0]);
    $('.loader').show();
    $.ajax({
        url: '{{ route("addUpdateSound") }}',
        type: 'POST',
        data: formdata,
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
            $('.loader').hide();
            $('#soundModal').modal('hide');
            if (data.success == 1) {

              $('#sound-listing').DataTable().ajax.reload(null, false);
              $('.total_sound').text(data.total_sound);
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

  $(document).on('click', '.DeleteSound', function (e) {
    e.preventDefault();
    var sound_id = $(this).attr('data-id');
    var text = 'You will not be able to recover Sound data!';   
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
                url: '{{ route("deleteSound") }}',
                type: 'POST',
                data: {"sound_id":sound_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#sound-listing').DataTable().ajax.reload(null, false);
                    $('.total_sound').text(data.total_sound);
                    if (data.success == 1) {
                      swal("Confirm!", "Sound has been deleted!", "success");
                    } else {
                      swal("Confirm!", "Sound has not been deleted!", "error");
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
