@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/products.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Products / Shop')}}
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3 mb-2 mb-sm-0">
                <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active show" id="v-pills-allProducts-tab" data-bs-toggle="pill" href="#v-pills-allProducts" role="tab" aria-controls="v-pills-allProducts" aria-selected="true" data-status="">
                        <span class="d-md-block">{{ __('All') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-pendingProducts-tab" data-bs-toggle="pill" href="#v-pills-pendingProducts" role="tab" aria-controls="v-pills-pendingProducts" aria-selected="false" data-status="1">
                        <span class="d-md-block">{{ __('Pending') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-approvedProducts-tab" data-bs-toggle="pill" href="#v-pills-approvedProducts" role="tab" aria-controls="v-pills-approvedProducts" aria-selected="false" data-status="2">
                        <span class="d-md-block">{{ __('Approved') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-rejectedProducts-tab" data-bs-toggle="pill" href="#v-pills-rejectedProducts" role="tab" aria-controls="v-pills-rejectedProducts" aria-selected="false" data-status="3">
                        <span class="d-md-block">{{ __('Rejected') }}</span>
                    </a>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="tab-content mt-3" id="v-pills-tabContent">
                    @php
                    $columns = ['Image', 'Name', 'Seller', 'Category', 'Price', 'Stock', 'Sold', 'Rating', 'Status', 'Created', 'Action'];
                    $tables = [
                        ['id' => 'allProductsTable', 'pane' => 'allProducts', 'active' => true],
                        ['id' => 'pendingProductsTable', 'pane' => 'pendingProducts', 'active' => false],
                        ['id' => 'approvedProductsTable', 'pane' => 'approvedProducts', 'active' => false],
                        ['id' => 'rejectedProductsTable', 'pane' => 'rejectedProducts', 'active' => false],
                    ];
                    @endphp

                    @foreach ($tables as $table)
                    <div class="tab-pane fade {{ $table['active'] ? 'active show' : '' }}" id="v-pills-{{ $table['pane'] }}" role="tabpanel">
                        <div class="table-responsive">
                            <table id="{{ $table['id'] }}" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        @foreach ($columns as $col)
                                        <th @if($col === 'Action') style="width: 200px;" class="text-end" @endif>{{ __($col) }}</th>
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
