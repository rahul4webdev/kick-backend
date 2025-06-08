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
                        <h4>Report List (<span class="total_report">{{$total_report}}</span>)</h4>
                    </div>
                    <div class="tab" role="tabpanel">
                      <ul class="nav nav-pills border-b mb-0 p-3">
                        <li role="presentation" class="nav-item"><a class="nav-link pointer active" href="#Section1" aria-controls="home" role="tab" data-toggle="tab">Report Video<span class="badge badge-transparent total_report_video">{{$total_report_video}}</span></a></li>
                        <li role="presentation" class="nav-item"><a class="nav-link pointer" href="#Section2" role="tab" data-toggle="tab">Report User <span class="badge badge-transparent total_report_user">{{$total_report_user}}</span></a></li>
                      </ul>

                      <div class="tab-content tabs" id="home">
                        <div role="tabpanel" class="tab-pane active" id="Section1">
                          <div class="card-body">	
                              <div class="table-responsive">
                                  <table class="table table-striped" id="report-video-listing">
                                      <thead>
                                      <tr>
                                        <th>Report Type</th>
                                        <th>User Name</th>
                                        <th>Video</th>
                                        <th>Reason</th>
                                        <th>Description</th>
                                        <th>Contact Info</th>
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

                        <div role="tabpanel" class="tab-pane" id="Section2">
                          <div class="card-body">	
                              <div class="table-responsive">
                                  <table class="table table-striped" id="report-user-listing" style="width:100%">
                                      <thead>
                                      <tr>
                                        <th>Report Type</th>
                                        <th>User Name</th>
                                        <th>User Profile</th>
                                        <th>Reason</th>
                                        <th>Description</th>
                                        <th>Contact Info</th>
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
<script src="{{asset('assets/bundles/summernote/summernote-bs4.js')}}"></script>

<script>
$(document).ready(function (){
  var dataTable = $('#report-video-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [7,8], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showReportList") }}',
        'data': function(data){
          data.report_type = 2;
        }
    }
  });

  var dataTable = $('#report-user-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [7,8], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showReportList") }}',
        'data': function(data){
          data.report_type = 1;
        }
    }
  });

  $(document).on('click', '.confirmReport', function (e) {
    e.preventDefault();
    var report_id = $(this).attr('data-id');
    var text = 'Your report able to submit data!';   
    var confirmButtonText = 'Yes, Confirm it!';
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
                url: '{{ route("confirmReport") }}',
                type: 'POST',
                data: {"report_id":report_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#report-video-listing').DataTable().ajax.reload(null, false);
                    $('#report-user-listing').DataTable().ajax.reload(null, false);
                    $('.total_report').text(data.total_report);
                    $('.total_report_video').text(data.total_report_video);
                    $('.total_report_user').text(data.total_report_user);
                    if (data.success == 1) {
                      swal("Confirm!", "Your Report has been confirm!", "success");
                    } else {
                      swal("Confirm!", "Report has not been confirm!", "error");
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

  $(document).on('click', '.DeleteReport', function (e) {
    e.preventDefault();
    var report_id = $(this).attr('data-id');
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
                url: '{{ route("deleteReport") }}',
                type: 'POST',
                data: {"report_id":report_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#report-video-listing').DataTable().ajax.reload(null, false);
                    $('#report-user-listing').DataTable().ajax.reload(null, false);
                    $('.total_report').text(data.total_report);
                    $('.total_report_video').text(data.total_report_video);
                    $('.total_report_user').text(data.total_report_user);
                    if (data.success == 1) {
                      swal("Confirm!", "Your Report has remove to explore!", "success");
                    } else {
                      swal("Confirm!", "Report has not been Removed!", "error");
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
