@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/series.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Short Stories (Series)')}}
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3 mb-2 mb-sm-0">
                <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active show" id="v-pills-allSeries-tab" data-bs-toggle="pill" href="#v-pills-allSeries" role="tab" aria-controls="v-pills-allSeries" aria-selected="true" data-status="">
                        <span class="d-md-block">{{ __('All') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-pendingSeries-tab" data-bs-toggle="pill" href="#v-pills-pendingSeries" role="tab" aria-controls="v-pills-pendingSeries" aria-selected="false" data-status="1">
                        <span class="d-md-block">{{ __('Pending') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-approvedSeries-tab" data-bs-toggle="pill" href="#v-pills-approvedSeries" role="tab" aria-controls="v-pills-approvedSeries" aria-selected="false" data-status="2">
                        <span class="d-md-block">{{ __('Approved') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-rejectedSeries-tab" data-bs-toggle="pill" href="#v-pills-rejectedSeries" role="tab" aria-controls="v-pills-rejectedSeries" aria-selected="false" data-status="3">
                        <span class="d-md-block">{{ __('Rejected') }}</span>
                    </a>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="tab-content mt-3" id="v-pills-tabContent">
                    {{-- All --}}
                    <div class="tab-pane fade active show" id="v-pills-allSeries" role="tabpanel">
                        <div class="table-responsive">
                            <table id="allSeriesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Cover')}}</th>
                                        <th>{{ __('Title')}}</th>
                                        <th>{{ __('Creator')}}</th>
                                        <th>{{ __('Genre')}}</th>
                                        <th>{{ __('Language')}}</th>
                                        <th>{{ __('Episodes')}}</th>
                                        <th>{{ __('Views')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th>{{ __('Created')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Pending --}}
                    <div class="tab-pane fade" id="v-pills-pendingSeries" role="tabpanel">
                        <div class="table-responsive">
                            <table id="pendingSeriesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Cover')}}</th>
                                        <th>{{ __('Title')}}</th>
                                        <th>{{ __('Creator')}}</th>
                                        <th>{{ __('Genre')}}</th>
                                        <th>{{ __('Language')}}</th>
                                        <th>{{ __('Episodes')}}</th>
                                        <th>{{ __('Views')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th>{{ __('Created')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Approved --}}
                    <div class="tab-pane fade" id="v-pills-approvedSeries" role="tabpanel">
                        <div class="table-responsive">
                            <table id="approvedSeriesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Cover')}}</th>
                                        <th>{{ __('Title')}}</th>
                                        <th>{{ __('Creator')}}</th>
                                        <th>{{ __('Genre')}}</th>
                                        <th>{{ __('Language')}}</th>
                                        <th>{{ __('Episodes')}}</th>
                                        <th>{{ __('Views')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th>{{ __('Created')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Rejected --}}
                    <div class="tab-pane fade" id="v-pills-rejectedSeries" role="tabpanel">
                        <div class="table-responsive">
                            <table id="rejectedSeriesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Cover')}}</th>
                                        <th>{{ __('Title')}}</th>
                                        <th>{{ __('Creator')}}</th>
                                        <th>{{ __('Genre')}}</th>
                                        <th>{{ __('Language')}}</th>
                                        <th>{{ __('Episodes')}}</th>
                                        <th>{{ __('Views')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th>{{ __('Created')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
