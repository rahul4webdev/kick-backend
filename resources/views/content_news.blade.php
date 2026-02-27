@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/content_news.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('News')}}
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="newsPostsTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Content')}}</th>
                        <th>{{ __('Type')}}</th>
                        <th>{{ __('User')}}</th>
                        <th>{{ __('Metadata')}}</th>
                        <th>{{ __('Featured')}}</th>
                        <th>{{ __('Description & Stats')}}</th>
                        <th>{{ __('Created Date')}}</th>
                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Video Post Modal --}}
<div id="videoPostModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('View Content')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div class="postDescription mb-2" id="videoDescription"></div>
                <video rel="" id="video" width="450" height="450" controls>
                    <source src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</div>

@endsection
