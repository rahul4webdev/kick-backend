@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/product_orders.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Product Orders')}}
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3 mb-2 mb-sm-0">
                <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active show" data-bs-toggle="pill" href="#v-pills-allOrders" role="tab" data-status="">
                        <span class="d-md-block">{{ __('All') }}</span>
                    </a>
                    <a class="nav-link" data-bs-toggle="pill" href="#v-pills-pendingOrders" role="tab" data-status="0">
                        <span class="d-md-block">{{ __('Pending') }}</span>
                    </a>
                    <a class="nav-link" data-bs-toggle="pill" href="#v-pills-confirmedOrders" role="tab" data-status="1">
                        <span class="d-md-block">{{ __('Confirmed') }}</span>
                    </a>
                    <a class="nav-link" data-bs-toggle="pill" href="#v-pills-deliveredOrders" role="tab" data-status="3">
                        <span class="d-md-block">{{ __('Delivered') }}</span>
                    </a>
                    <a class="nav-link" data-bs-toggle="pill" href="#v-pills-cancelledOrders" role="tab" data-status="4">
                        <span class="d-md-block">{{ __('Cancelled') }}</span>
                    </a>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="tab-content mt-3" id="v-pills-tabContent">
                    @php
                    $columns = ['#', 'Product', 'Buyer', 'Seller', 'Qty', 'Total', 'Status', 'Tracking', 'Created'];
                    $tables = [
                        ['id' => 'allOrdersTable', 'pane' => 'allOrders', 'active' => true],
                        ['id' => 'pendingOrdersTable', 'pane' => 'pendingOrders', 'active' => false],
                        ['id' => 'confirmedOrdersTable', 'pane' => 'confirmedOrders', 'active' => false],
                        ['id' => 'deliveredOrdersTable', 'pane' => 'deliveredOrders', 'active' => false],
                        ['id' => 'cancelledOrdersTable', 'pane' => 'cancelledOrders', 'active' => false],
                    ];
                    @endphp

                    @foreach ($tables as $table)
                    <div class="tab-pane fade {{ $table['active'] ? 'active show' : '' }}" id="v-pills-{{ $table['pane'] }}" role="tabpanel">
                        <div class="table-responsive">
                            <table id="{{ $table['id'] }}" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        @foreach ($columns as $col)
                                        <th>{{ __($col) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
