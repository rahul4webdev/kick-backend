@extends('include.app')
@section('header')
    <script src="{{ asset('assets/script/marketplace.js') }}"></script>
@endsection
@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Creator Marketplace - Campaigns</h4>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-bordered mb-3">
                    <li class="nav-item">
                        <a href="#all" data-bs-toggle="tab" class="nav-link active">All</a>
                    </li>
                    <li class="nav-item">
                        <a href="#active" data-bs-toggle="tab" class="nav-link">Active</a>
                    </li>
                    <li class="nav-item">
                        <a href="#completed" data-bs-toggle="tab" class="nav-link">Completed</a>
                    </li>
                    <li class="nav-item">
                        <a href="#cancelled" data-bs-toggle="tab" class="nav-link">Cancelled</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane show active" id="all">
                        <div class="card">
                            <div class="card-body">
                                <table id="allCampaignsTable" class="table dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Budget</th>
                                            <th>Applications</th>
                                            <th>Accepted</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="active">
                        <div class="card">
                            <div class="card-body">
                                <table id="activeCampaignsTable" class="table dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Budget</th>
                                            <th>Applications</th>
                                            <th>Accepted</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="completed">
                        <div class="card">
                            <div class="card-body">
                                <table id="completedCampaignsTable" class="table dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Budget</th>
                                            <th>Applications</th>
                                            <th>Accepted</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="cancelled">
                        <div class="card">
                            <div class="card-body">
                                <table id="cancelledCampaignsTable" class="table dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Budget</th>
                                            <th>Applications</th>
                                            <th>Accepted</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Action</th>
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
