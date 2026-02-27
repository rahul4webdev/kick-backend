@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/ad_revenue.js') }}"></script>
@endsection
@section('content')

<!-- Stats Cards -->
<div class="row mb-3" id="statsRow">
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Impressions</h6><h4 id="statImpressions">-</h4></div></div>
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Total Revenue</h6><h4 id="statRevenue">-</h4></div></div>
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Enrolled</h6><h4 id="statEnrolled">-</h4></div></div>
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Pending</h6><h4 id="statPending">-</h4></div></div>
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Paid Out</h6><h4 id="statPaidOut">-</h4></div></div>
    <div class="col-md-2"><div class="card text-center p-2"><h6 class="mb-0 text-muted">Platform Rev</h6><h4 id="statPlatformRev">-</h4></div></div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom justify-content-between">
        <h4 class="card-title mb-0 header-title">{{ __('Ad Revenue Share Program') }}</h4>
        <button class="btn btn-primary btn-sm" onclick="processMonthly()">Process Monthly Payouts</button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3 mb-2 mb-sm-0">
                <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active show" id="v-pills-enrollments-tab" data-bs-toggle="pill" href="#v-pills-enrollments" role="tab" aria-selected="true">
                        <span class="d-md-block">{{ __('Enrollments') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-payouts-tab" data-bs-toggle="pill" href="#v-pills-payouts" role="tab" aria-selected="false">
                        <span class="d-md-block">{{ __('Payouts') }}</span>
                    </a>
                </div>
            </div>
            <div class="col-sm-9">
                <div class="tab-content" id="v-pills-tabContent">
                    <!-- Enrollments Tab -->
                    <div class="tab-pane fade active show" id="v-pills-enrollments" role="tabpanel">
                        <table id="enrollmentsTable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Followers</th>
                                <th>Views</th>
                                <th>Impressions</th>
                                <th>Revenue</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <!-- Payouts Tab -->
                    <div class="tab-pane fade" id="v-pills-payouts" role="tabpanel">
                        <table id="payoutsTable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                            <tr>
                                <th>Username</th>
                                <th>Period</th>
                                <th>Impressions</th>
                                <th>Revenue</th>
                                <th>Creator Share</th>
                                <th>Coins</th>
                                <th>Status</th>
                                <th>Processed</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
