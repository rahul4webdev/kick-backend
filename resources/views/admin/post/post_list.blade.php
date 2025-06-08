@extends('admin_layouts/main')
@section('pageSpecificCss')
<link href="{{asset('assets/bundles/datatables/datatables.min.css')}}" rel="stylesheet">
<link href="{{asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@stop
@section('content')
<section class="section">
  <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Post List (<span class="total_videos">{{$total_videos}}</span>)</h4>
            </div>

            <div class="tab" role="tabpanel">
              <ul class="nav nav-pills border-b mb-0 p-3">
                <li role="presentation" class="nav-item"><a class="nav-link pointer active" href="#Section1" aria-controls="home" role="tab" data-toggle="tab">All Post<span class="badge badge-transparent total_videos">{{$total_videos}}</span></a></li>
                <li role="presentation" class="nav-item"><a class="nav-link pointer" href="#Section2" role="tab" data-toggle="tab">Suggested <span class="badge badge-transparent total_suggested">{{$total_suggested}}</span></a></li>
              </ul>
              <div class="tab-content tabs" id="home">
                <div role="tabpanel" class="tab-pane active" id="Section1">
                  <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="post-listing">
                          <thead>
                            <tr>
                              <th>Post Video</th>
                              <th>Post Image</th>
                              <th>User</th>
                              <th>Post Description</th>
                              <th>Post Hashtag</th>
                              <th>Total View</th>
                              <th>Suggested</th>
                              <th>Created Date</th>
                              <th>Suggested</th>
                              <th>Action</th>
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
                        <table class="table table-striped" id="suggested-post-listing" width="100%">
                          <thead>
                            <tr>
                              <th>Post Video</th>
                              <th>Post Image</th>
                              <th>User</th>
                              <th>Post Description</th>
                              <th>Post Hashtag</th>
                              <th>Total View</th>
                              <th>Suggested</th>
                              <th>Created Date</th>
                              <th>Suggested</th>
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
    </div>
  </div>
</section>
@endsection

@section('pageSpecificJs')

<script src="{{asset('assets/bundles/datatables/datatables.min.js')}}"></script>
<script src="{{asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('assets/js/page/datatables.js')}}"></script>
<script src="{{asset('assets/bundles/izitoast/js/iziToast.min.js')}}"></script>

<script>
$(document).ready(function (){
  var dataTable = $('#post-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [6,8,9], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showPostList") }}',
        'data': function(data){
          data.is_trending = 0;
        }
    }
  });

  var dataTable = $('#suggested-post-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [6,8,9], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showPostList") }}',
        'data': function(data){
          data.is_trending = 1;
        }
    }
  });

  $(document).on('click', '#ChangeTrendingStatus', function (e) {
      e.preventDefault();
      var post_id = $(this).attr('data-id');
      var is_status = $(this).attr('data-status');
      if(is_status == 1){
        status = 0;
        var text = 'Your post remove to trending!';
        var confirmButtonText = 'Yes, Remove it!';
        var btn = 'btn-danger';
      }else{
        status = 1;
        var text = 'Your post move to suggested?';
        var confirmButtonText = 'Yes, Confirm it!';
        var btn = 'btn-success';
      }

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
            $('.preloader').show();
            $.ajax({
                url: '{{ route("ChangeTrendingStatus") }}',
                type: 'POST',
                data: {"post_id":post_id,"status":status},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.preloader').hide();
                    $('#post-listing').DataTable().ajax.reload(null, false);
                    $('.total_videos').text(data.total_videos);
                    $('.total_suggested').text(data.total_suggested);
                    if (data.success == 1) {
                      if(is_status == 1){
                        swal("Disable!", "Your post has remove to tranding!", "success");
                      }else{
                        swal("Confirm!", "Your post has move move to tranding!", "success");
                      }
                    } else {
                      swal("Delete!", "Your data has not been deleted!", "success");
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

    $(document).on('click', '#postDelete', function (e) {
      e.preventDefault();
      var post_id = $(this).attr('data-id');
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
      function(isConfirm){
          if (isConfirm){
            $('.preloader').show();
            $.ajax({
                url: '{{ route("deletePost") }}',
                type: 'POST',
                data: {"post_id":post_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.preloader').hide();
                    $('#post-listing').DataTable().ajax.reload(null, false);
                    $('.total_videos').text(data.total_videos);
                    $('.total_suggested').text(data.total_suggested);
                    if (data.success == 1) {

                    } else {
                      swal("Confirm!", "Post has not been deleted!", "error");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(errorThrown);
                }
            });
          } else {

        }
      });
    });

});
</script>

@endsection
