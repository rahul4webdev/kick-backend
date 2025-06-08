@extends('admin_layouts/main')
@section('pageSpecificCss')
<style>

</style>
@stop
@section('content')
<section class="section">
  <div class="row">

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-user font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Today's Registration</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalTodayUser}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-users font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Users</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalUser}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-video font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Post</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalPost}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fas fa-file-audio font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Music Category</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalSoundCategory}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-music font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Music</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalSound}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-transgender-alt font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Explore Tags</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalHashTags}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fas fa-calendar-check font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Verification Request</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalVerificationRequest}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-list-alt font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Reports</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalReport}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fa fa-check-circle font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Verified Users</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalVerifyUser}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fas fa-dollar-sign font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Coin Rate</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$CoinRate}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="far fa-credit-card font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Coin Wallet</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$MyWallet}}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
            <div class="card-statistic-4 p-4">
                <i class="fas fa-share font-26 col-indigo float-right mt-2"></i>
                <div>
                    <h4 class="font-16 text-grayg">Total Redeem Request</h4>
                    <h2 class="font-18 mb-0 text-gray">{{$totalRedeemRequest}}</h2>
                </div>
            </div>
        </div>
    </div>

  </div>

</section>

@endsection
@section('pageSpecificJs')
<script src="{{asset('assets/bundles/chartjs/chart.min.js')}}"></script>
<script src="{{asset('assets/dist/js/custom.js')}}"></script>
@stop