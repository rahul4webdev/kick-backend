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
              <h4>Coin Plan List (<span class="total_coin_plan">{{$total_coin_plan}}</span>)</h4>
            </div>
            <div class="card-body">
                <div class="pull-right">
                    <div class="buttons"> 
                        <button class="btn btn-primary text-light" data-toggle="modal" data-target="#coinPlanModal" data-whatever="@mdo" @if(Session::get('admin_id') == 2){{"disabled"}}@endif>Add Coin Plan</button>
                    </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-striped" id="coin-plan-listing">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Coin Plan Price</th>
                        <th>Coin Amount</th>
                        <th>Playstore Product Id</th>
                        <th>Appstore Product Id</th>
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

<div tabindex="-1" role="dialog" class="modal fade slide-right" id="coinPlanModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true" modal-animation="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" uib-modal-transclude="">
            <div class="modal-header">
                <div class="position-relative title-content w-100">
                    <h4 class="modal-title soundtitle">Add Coin Plan</h4>
                    <button type="button" class="pointer action-button close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            </div>
            <div class="modal-body clearfix">

                <form class="edit-detail-content" id='addUpdateCoinPlan' method="post" enctype="multipart/form-data">
                {{ csrf_field() }}

                    <input type="hidden" name="coin_plan_id" id="coin_plan_id" value="">

                    <div class="form-group">
                        <label for="categoryname">Coin Plan Name</label>
                        <input type="text" class="form-control" id="coin_plan_name" name="coin_plan_name" placeholder="Coin Plan Name">
                    </div>			
                
                    <div class="form-group">
                        <label for="categoryname">Coin Plan Description</label>
                        <input type="text" class="form-control" id="coin_plan_description" name="coin_plan_description" placeholder="Coin Plan Description">
                    </div>	

            
                    <div class="form-group">
                        <label for="categoryname">Coin Plan Price</label>
                        <input type="text" class="form-control" id="coin_plan_price" name="coin_plan_price" placeholder="Coin Plan Price">
                    </div>
                
                    <div class="form-group">
                        <label for="categoryname">Coin amount</label>
                        <input type="text" class="form-control" id="coin_amount" name="coin_amount" placeholder="Coin amount">
                    </div>
                
            
                    <div class="form-group">
                        <label for="categoryname">Playstore Product Id</label>
                        <input type="text" class="form-control" id="playstore_product_id" name="playstore_product_id" placeholder="Playstore Product Id">
                    </div>
                
                    <div class="form-group">
                        <label for="categoryname">Appstore Product Id</label>
                        <input type="text" class="form-control" id="appstore_product_id" name="appstore_product_id" placeholder="Appstore Product Id">
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
    var dataTable = $('#coin-plan-listing').dataTable({
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        "order": [[ 0, "desc" ]],
        'columnDefs': [ {
            'targets': [6,7], /* column index */
            'orderable': false, /* true or false */
            }],
        'ajax': {
            'url':'{{ route("showCoinPlanList") }}',
            'data': function(data){
            }
        }
    });

    
    $('#coinPlanModal').on('hidden.bs.modal', function(e) {
        $("#addUpdateCoinPlan")[0].reset();
        $('.modal-title').text('Add Coin Plan');
        $('#coin_plan_id').val("");
        var validator = $("#addUpdateCoinPlan").validate();
        validator.resetForm();
    });

    $("#coin-plan-listing").on("click", ".UpdateCoinPlan", function() {
        $('.modal-title').text('Edit Coin Plan');
        $('#coin_plan_id').val($(this).attr('data-id'));
        $('#coin_plan_name').val($(this).attr('data-name'));
        $('#coin_plan_description').val($(this).attr('data-description'));
        $('#coin_plan_price').val($(this).attr('data-price'));
        $('#coin_amount').val($(this).attr('data-amount'));
        $('#playstore_product_id').val($(this).attr('data-playstore_product_id'));
        $('#appstore_product_id').val($(this).attr('data-appstore_product_id'));
    });

    $("#addUpdateCoinPlan").validate({
        rules: {
            coin_plan_name:{
                required: true    
            },
            coin_plan_description:{
                required: true    
            },
            coin_plan_price:{
                required: true    
            },
            coin_amount:{
                required: true    
            },
            playstore_product_id:{
                required: true    
            },
        },
        messages: {
            coin_plan_name: {
                required: "Please enter coin plan name"
            },
            coin_plan_description: {
                required: "Please enter coin plan description"
            },
            coin_plan_price: {
                required: "Please enter coin plan price"
            },
            coin_amount: {
                required: "Please enter coin amount"
            },
            playstore_product_id: {
                required: "Please enter playstore id"
            },
            // appstore_product_id: "Please enter appstore id",
        }
    });

    $(document).on('submit', '#addUpdateCoinPlan', function (e) {
        e.preventDefault();
        
        var formdata = new FormData($("#addUpdateCoinPlan")[0]);
        $('.loader').show();
        $.ajax({
            url: '{{ route("addUpdateCoinPlan") }}',
            type: 'POST',
            data: formdata,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                $('.loader').hide();
                $('#coinPlanModal').modal('hide');
                if (data.success == 1) {
                    $('#coin-plan-listing').DataTable().ajax.reload(null, false);
                    $('.total_coin_plan').text(data.total_coin_plan);
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


    $(document).on('click', '#deleteCoinPlan', function (e) {
      e.preventDefault();
      var coin_plan_id = $(this).attr('data-id');
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
                url: '{{ route("deleteCoinPlan") }}',
                type: 'POST',
                data: {"coin_plan_id":coin_plan_id},
                dataType: "json",
                cache: false,
                success: function (data) {
                    $('.preloader').hide();
                    $('#coin-plan-listing').DataTable().ajax.reload(null, false);
                    $('.total_coin_plan').text(data.total_coin_plan);
                    if (data.success == 1) {
                      swal("Confirm!", "Coin Plan has been deleted!", "success");
                    } else {
                      swal("Confirm!", "Coin Plan has not been deleted!", "error");
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
