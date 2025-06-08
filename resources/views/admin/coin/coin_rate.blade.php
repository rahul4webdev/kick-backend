@extends('admin_layouts/main')

@section('content')
<section class="section">
    <div class="row justify-content-md-center">
        <div class="col col-lg-2"> </div>
                
            <div class="col-md-8">

                <div class="card">
                    <div class="card-header">
                        <h4>Edit Coin Rate</h4>
                    </div>
                    <div class="card-body">
                        <form class="edit-detail-content" id='updateCoinRate' method="post" enctype="multipart/form-data">
                        {{ csrf_field() }}
                            <input type="hidden" name="coin_rate_id" id="coin_rate_id" value="{{$coin_rate['coin_rate_id']}}">

                            <div class="form-group">
                                <label for="categoryname">Usd Rate</label>
                                <input type="text" class="form-control" id="usd_rate" value="{{$coin_rate['usd_rate']}}" name="usd_rate" placeholder="Enter USD Rate">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" @if(Session::get('admin_id') == 2){{"disabled"}}@endif>Submit</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col col-lg-2">  </div>
        </div>
    </div>
</section>


@stop

@section('pageSpecificJs')

<script src="{{asset('assets/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('assets/bundles/izitoast/js/iziToast.min.js')}}"></script>

<script>

$(document).ready(function (){

    $("#updateCoinRate").validate({
    rules: {
        usd_rate:{
                required: true    
        } 
    },
    messages: {
        usd_rate: {
            required: "Please enter Usd Rate"  
        }
    }
  });

  $(document).on('submit', '#updateCoinRate', function (e) {
    e.preventDefault();
    
    var formdata = new FormData($("#updateCoinRate")[0]);
    $('.loader').show();
    $.ajax({
        url: '{{ route("updateCoinRate") }}',
        type: 'POST',
        data: formdata,
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
            $('.loader').hide();
            if (data.success == 1) {
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
