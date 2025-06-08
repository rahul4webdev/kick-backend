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
                        <h4>Redeem Request List (<span class="total_hashtags">{{($total_pending_request+$total_confirm_request)}}</span>)</h4>
                    </div>
                    <div class="tab" role="tabpanel">
                      <ul class="nav nav-pills border-b mb-0 p-3">
                        <li role="presentation" class="nav-item"><a class="nav-link pointer active" href="#Section1" aria-controls="home" role="tab" data-toggle="tab">Pending Request<span class="badge badge-transparent total_pending_request">{{$total_pending_request}}</span></a></li>
                        <li role="presentation" class="nav-item"><a class="nav-link pointer" href="#Section2" role="tab" data-toggle="tab">Confirm Request<span class="badge badge-transparent total_confirm_request">{{$total_confirm_request}}</span></a></li>
                      </ul>

                      <div class="tab-content tabs" id="home">
                        <div role="tabpanel" class="tab-pane active" id="Section1">
                          <div class="card-body">	
                              <div class="table-responsive">
                                  <table class="table table-striped" id="pending-request-listing">
                                      <thead>
                                      <tr>
                                        <th>Request Type</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>User</th>
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
                                  <table class="table table-striped" id="confirm-request-listing" style="width:100%">
                                      <thead>
                                      <tr>
                                        <th>Request Type</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>User</th>
                                        <th>Created Date</th>
                                        <th>Status</th>
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
  var dataTable = $('#pending-request-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [3,4], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showRedeemRequestList") }}',
        'data': function(data){
          data.status = 0;
        }
    }
  });

  var dataTable = $('#confirm-request-listing').dataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    "order": [[ 0, "desc" ]],
    'columnDefs': [ {
          'targets': [3,4], /* column index */
          'orderable': false, /* true or false */
        }],
    'ajax': {
        'url':'{{ route("showRedeemRequestList") }}',
        'data': function(data){
          data.status = 1;
        }
    }
  });

  $(document).on('click', '.changeRedeemRequestStatus', function (e) {
    e.preventDefault();
    var redeem_request_id = $(this).attr('data-id');
    var text = 'Your redeem request confirm!';   
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
    function(isConfirm){
        if (isConfirm){
            $('.loader').show();
            $.ajax({
                url: '{{ route("changeRedeemRequestStatus") }}',
                type: 'POST',
                data: {"redeem_request_id":redeem_request_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.loader').hide();
                    $('#pending-request-listing').DataTable().ajax.reload(null, false);
                    $('#confirm-request-listing').DataTable().ajax.reload(null, false);
                    $('.total_pending_request').text(data.total_pending_request);
                    $('.total_confirm_request').text(data.total_confirm_request);
                    if (data.success == 1) {
                        swal("Confirm!", "Your redeem request has been confirm!", "success");
                    } else {
                      swal("Confirm!", "Redeem Request has not been Removed!", "error");
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
