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
                        <h4>HashTags List (<span class="total_hashtags">{{$total_hashtags}}</span>)</h4>
                    </div>
                    <div class="tab" role="tabpanel">
                      <ul class="nav nav-pills border-b mb-0 p-3">
                        <li role="presentation" class="nav-item"><a class="nav-link pointer active" href="#Section1" aria-controls="home" role="tab" data-toggle="tab">All HashTag<span class="badge badge-transparent total_hashtags">{{$total_hashtags}}</span></a></li>
                        <li role="presentation" class="nav-item"><a class="nav-link pointer" href="#Section2" role="tab" data-toggle="tab">On Explore <span class="badge badge-transparent total_explore_tag">{{$total_explore_tag}}</span></a></li>
                      </ul>

                      <div class="tab-content tabs" id="home">
                        <div role="tabpanel" class="tab-pane active" id="Section1">
                          <div class="card-body">	
                              <div class="table-responsive">
                                  <table class="table table-striped" id="hashtags-listing">
                                      <thead>
                                      <tr>
                                          <th>Hash Tag</th>
                                          <th>Image</th>
                                          <th>Video Count</th>
                                          <th>Status</th>
                                          <th>Move to Explore</th>
                                      </tr>
                                      </thead>
                                      <tbody>

                                      </tbody>
                                  </table>
                              </div>
                          </div>
                        </div>

                        <div role="tabpanel" class="tab-pane" id="Section2">
                          <div class="card-body">	
                              <div class="table-responsive">
                                  <table class="table table-striped" id="explore-hashtags-listing" style="width:100%">
                                      <thead>
                                      <tr>
                                        <th>Hash Tag</th>
                                        <th>Image</th>
                                        <th>Video Count</th>
                                        <th>Status</th>
                                        <th>Move to Explore</th>
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
            </div>
        </div>
    </div>
</section>


<div tabindex="-1" role="dialog" class="modal fade slide-right" id="hashtagsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" uib-modal-transclude="">
            <div class="modal-header">
                <div class="position-relative title-content w-100">
                <h4 class="modal-title hashtagtitle">Edit Explore Hash Tag Image</h4>
                <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                </div>
            </div>
            <div class="modal-body clearfix">
                <form class="edit-detail-content" id='ExploreHashtagImage' name="ExploreHashtagImage" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                    <input type="hidden" name="hash_tag_id" id="hash_tag_id" value="">
                    <input type="hidden" name="hdn_hash_tag_image" id="hdn_hash_tag_image" value="">
                    <div class="form-group">
                        <label for="categoryname">Explore Hash Tag Image</label>
                        <input type="file" name="hash_tag_profile" id="hash_tag_profile" class="form-control file-upload">
                        <label id="hash_tag_profile-error" class="error image_error" for="hash_tag_profile"></label>
                    </div>
                    <div class="form-group preview_media">

                    </div>
                    <div class="form-group form-action text-right m-t-10">
                        <a style="color: #fff" class="btn btn-danger btn-md bg-danger hashtag_cancel">Cancel</a>
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
  var dataTable = $('#hashtags-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [3,4], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showHashTagsList") }}',
        'data': function(data){
          data.move_explore = 0;
        }
    }
  });

  var dataTable = $('#explore-hashtags-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [3,4], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showHashTagsList") }}',
        'data': function(data){
          data.move_explore = 1;
        }
    }
  });

  $(document).on('change', '#hash_tag_profile', function () {
    CheckFileExtention(this,'preview_media');
  });


  var CheckFileExtention = function (input, cl) {
    if (input.files) {
        var allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
        
        if (!allowedExtensions.exec(input.value)) {
            iziToast.error({
                title: 'Error!',
                message: 'Please upload Image and Video File only.',
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

                
                $('.loader').hide();
            }
        }
    }
  };

  $('#hashtagsModal').on('hidden.bs.modal', function(e) {
      $("#ExploreHashtagImage")[0].reset();
      $('#hash_tag_id').val("");
      $('.preview_media').html("");
      var validator = $("#ExploreHashtagImage").validate();
      validator.resetForm();
  });

  $(document).on("click", ".UpdateHashTags", function() {
      $('.loader').show();
      $('#hash_tag_id').val($(this).attr('data-id'));
      if($(this).attr('data-src')){
        $('.preview_media').html('<img src="{{env("DEFAULT_IMAGE_URL")}}'+$(this).attr('data-src')+'" width="150" height="150">');
        $('#hdn_hash_tag_image').val($(this).attr('data-src'));
      }
      $('.loader').hide();
  });

  $("#ExploreHashtagImage").validate({
      rules: {
        hash_tag_profile:{
            required: {
              depends: function(element) {
                    return ($('#hdn_hash_tag_image').val() == "")
                }
            },
          }
      },
      messages: {
        hash_tag_profile: {
            required: "Please Upload HashTags Image",
        }
      }
  });

  $(document).on('submit', '#ExploreHashtagImage', function (e) {
    e.preventDefault();
    
    var formdata = new FormData($("#ExploreHashtagImage")[0]);
    $('.loader').show();
    $.ajax({
        url: '{{ route("ExploreHashtagImage") }}',
        type: 'POST',
        data: formdata,
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
            $('.loader').hide();
            $('#hashtagsModal').modal('hide');
            if (data.success == 1) {

              $('#hashtags-listing').DataTable().ajax.reload(null, false);
              $('#explore-hashtags-listing').DataTable().ajax.reload(null, false);
              $('.total_hashtags').text(data.total_hashtags);
              $('.total_explore_tag').text(data.total_explore_tag);
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

  $(document).on('click', '.RemoveExploreHashTags', function (e) {
    e.preventDefault();
    var hash_tag_id = $(this).attr('data-id');
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
      closeOnConfirm: false,
      closeOnCancel: false
    },
    function(isConfirm){
        if (isConfirm){
            $('.loader').show();
            $.ajax({
                url: '{{ route("RemoveExploreHashTags") }}',
                type: 'POST',
                data: {"hash_tag_id":hash_tag_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#hashtags-listing').DataTable().ajax.reload(null, false);
                    $('#explore-hashtags-listing').DataTable().ajax.reload(null, false);
                    $('.total_hashtags').text(data.total_hashtags);
                    $('.total_explore_tag').text(data.total_explore_tag);
                    if (data.success == 1) {
                      swal("Confirm!", "Your hash tag has remove to explore!", "success");
                    } else {
                      swal("Confirm!", "HashTags has not been Removed!", "error");
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
