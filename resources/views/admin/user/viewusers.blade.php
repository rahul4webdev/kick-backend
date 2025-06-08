@extends('admin_layouts/main')
@section('pageSpecificCss')
<link href="{{asset('assets/bundles/izitoast/css/iziToast.min.css')}}" rel="stylesheet">
@stop
@section('content')

<section class="section">
  <div class="row">

    <div class="col-md-6">
      <div class="card">

        <div class="card-body padd-0 text-center">
          <div class="card-avatar style-2">
            <?php
              if(!empty($data['user_profile']))
              {
                ?>
                <img height="150px" width="150px" src="{{url(env('DEFAULT_IMAGE_URL').$data['user_profile'])}}" class="rounded-circle author-box-picture mb-2" alt="">
                <?php
              }
              else
              {
                ?>
                <img height="150px" width="150px" src="{{asset('assets/dist/img/default.png')}}" class="rounded-circle author-box-picture mb-2" alt="">
                <?php
              }
            ?>

          </div>
          <p class="card-small-text">@if($data['is_verify'] == 1){{"Verified"}}@endif</p>
          <h5 class="font-normal mrg-bot-0 font-18 card-title">{{$data['full_name']}}</h5>
          <h6 class="font-normal mrg-bot-0 font-15 card-title">{{$data['user_name']}}</h6>
          <p class="card-small-text">{{$data['user_email']}}</p>
          <p class="card-small-text">{{$data['bio']}}</p>
        </div>
        <div class="bottom">
          <ul class="social-detail">
            <li><a target="_blank" href="{{$data['fb_url']}}" class="fab fa-facebook-f font-20 pointer p-l-5 p-r-5"></a></li>
            <li><a target="_blank" href="{{$data['insta_url']}}" class="fab fa-instagram font-20 pointer p-l-5 p-r-5"></a></li>
            <li><a target="_blank" href="{{$data['youtube_url']}}" class="fab fa-youtube font-20 pointer p-l-5 p-r-5"></a></li>
          </ul>
        </div>
      </div>

      <!-- About Me Box -->
      <div class="card">
      <div class="card-header">
        <h4 class="box-title">User Details</h4>

      </div>
      <!-- /.box-header -->
      <div class="card-body">

        <div class="card-body">
          @if ($profile_category_data != null)
         <p>
            <strong> Profile Category</strong> : {{$profile_category_data['profile_category_name']}}
        </p>
          @endif


        <p>
            <strong> Total Followers</strong> : {{$followers_count}}
        </p>
        <p>
            <strong> Total Following</strong> : {{$following_count}}
        </p>
        <p>
            <strong> Total Post</strong> : {{$total_videos}}
        </p>
        <p>
            <strong>Wallet</strong> : {{$data['my_wallet']}}
        </p>

      </div>

      </div>
      <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
    <div class="col-md-6">

      <div class="card">
        <div class="card-header">
        <h4 class="box-title">Send Notification</h4>
        </div>
      <!-- /.box-header -->
        <div class="col-md-12">
          <div class="col-md-12">
            <div class="card-body">
              <input type="hidden" name="user_id" id="user_id" value="{{$data['user_id']}}">
              <input type="text" placeholder="Please enter message" class="form-control" name="message" id="message">
            </div>
          </div>
          <div class="text-center mb-4">
            <input type="button" class="btn btn-primary btn-md" id="sendNotification" value="Send Notification">
          </div>
        </div>
      <!-- /.card-body -->
      </div>
    </div>
  </div>
</section>

@stop

@section('pageSpecificJs')

<script src="{{asset('assets/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('assets/bundles/izitoast/js/iziToast.min.js')}}"></script>

<script>

$(document).ready(function (){

  $(document).on('click', '#sendNotification', function (e) {

    var message = $("#message").val();
    var user_id = $("#user_id").val();

    if(message == "")
    {
        return false;
    }

    $.ajax({
      url: '{{ route("sendNotification") }}',
      data: {message:message,user_id:user_id},
      type: 'POST',
      dataType: "json",
      cache: false,
      success: function ( data ) {
          if(data.success == 1)
          {
            iziToast.success({
              title: 'Success!',
              message: 'Notification send successfully',
              position: 'topRight'
            });

          }
          else
          {
            iziToast.error({
              title: 'Error!',
              message: 'Notification send failed',
              position: 'topRight'
            });
          }
      }
  });
});

});
</script>

@endsection
