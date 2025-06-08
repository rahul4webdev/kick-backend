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
              <h4>User Post List (<span class="total_videos">{{$total_videos}}</span>)</h4>
            </div>
            <input type="hidden" name="user_id" id="user_id" value="{{$user_id}}">
            <div class="card-body">
              <div class="table-responsive">
                  <table class="table table-striped" id="post-user-listing">
                    <thead>
                      <tr>
                        <th>Post Video</th>
                        <th>Post Image</th>
                        <th>User Name</th>
                        <th>Post Description</th>
                        <th>Post Hashtag</th>
                        <th>Total View</th>
                        <th>Trending</th>
                        <th>Status</th>
                        <th>Created Date</th>
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
@endsection

@section('pageSpecificJs')

<script src="{{asset('assets/bundles/datatables/datatables.min.js')}}"></script>
<script src="{{asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('assets/js/page/datatables.js')}}"></script>
<script src="{{asset('assets/bundles/izitoast/js/iziToast.min.js')}}"></script>

<script>
$(document).ready(function (){
  var dataTable = $('#post-user-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [5,6], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showUserPostList") }}',
        'data': function(data){
            data.user_id = $('#user_id').val()
        }
    }
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
        closeOnConfirm: false,
        closeOnCancel: false
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
                    $('#post-user-listing').DataTable().ajax.reload(null, false);
                    $('.total_videos').text(data.total_videos);
                    if (data.success == 1) {
                      swal("Confirm!", "Post has been deleted!", "success");
                    } else {
                      swal("Confirm!", "Post has not been deleted!", "error");
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
