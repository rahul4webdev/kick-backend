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
              <h4>Rewarding Action List (<span class="total_rewarding_action">{{$total_rewarding_action}}</span>)</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped" id="rewarding-listing">
                    <thead>
                      <tr>
                        <th>Action Name</th>
                        <th>Coin</th>
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

<div tabindex="-1" role="dialog" class="modal fade slide-right" id="updateRewardingActionModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" uib-modal-transclude="">
            <div class="modal-header">
                <div class="position-relative title-content w-100">
                    <h4 class="modal-title soundtitle">Add Rewarding Action</h4>
                    <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            </div>
            <div class="modal-body clearfix">

                <form class="edit-detail-content" id='updateRewardingAction' method="post" enctype="multipart/form-data">
                {{ csrf_field() }}

                    <input type="hidden" name="rewarding_action_id" id="rewarding_action_id" value="">
                    <div class="form-group">
                        <label for="categoryname">Action Name</label>
                        <input type="text" class="form-control" id="action_name" value="" name="action_name" placeholder="Action Name">
                    </div>

                    <div class="form-group">
                        <label for="categoryname">Coin</label>
                        <input type="text" class="form-control" id="coin" value="" name="coin" placeholder="Coin">
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
    var dataTable = $('#rewarding-listing').dataTable({
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        "order": [[ 0, "desc" ]],
        'columnDefs': [ {
            'targets': [2], /* column index */
            'orderable': false, /* true or false */
            }],
        'ajax': {
            'url':'{{ route("showRewardingActionList") }}',
            'data': function(data){
            }
        }
    });

    $('#updateRewardingActionModal').on('hidden.bs.modal', function(e) {
        $("#updateRewardingAction")[0].reset();
        $('.modal-title').text('Add Rewarding Action');
        $('#coin_plan_id').val("");
        var validator = $("#updateRewardingAction").validate();
        validator.resetForm();
    });

    $("#rewarding-listing").on("click", ".UpdateCoinPlan", function() {
        $('.modal-title').text('Edit Rewarding Action');
        $('#rewarding_action_id').val($(this).attr('data-id'));
        $('#action_name').val($(this).attr('data-name'));
        $('#coin').val($(this).attr('data-coin'));
    });

    $("#updateRewardingAction").validate({
        rules: {
            action_name:{
                required: true    
            },
            coin:{
                required: true    
            },
        },
        messages: {
            action_name: {
                required: "Please enter action name"
            },
            coin: {
                required: "Please enter coin"
            },
        }
    });

    $(document).on('submit', '#updateRewardingAction', function (e) {
        e.preventDefault();
        
        var formdata = new FormData($("#updateRewardingAction")[0]);
        $('.loader').show();
        $.ajax({
            url: '{{ route("updateRewardingAction") }}',
            type: 'POST',
            data: formdata,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                $('.loader').hide();
                $('#updateRewardingActionModal').modal('hide');
                if (data.success == 1) {
                    $('#rewarding-listing').DataTable().ajax.reload(null, false);
                    $('.total_rewarding_action').text(data.total_rewarding_action);
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
    
});
</script>

@endsection
