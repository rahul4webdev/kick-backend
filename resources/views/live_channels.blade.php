@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/live_channels.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Live TV Channels')}}
        </h4>
        <a data-bs-toggle="modal" data-bs-target="#addChannelModal" class="btn btn-dark ms-auto">{{ __('Add Channel')}}</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="liveChannelsTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Logo')}}</th>
                        <th>{{ __('Channel Name')}}</th>
                        <th>{{ __('Owner')}}</th>
                        <th>{{ __('Stream Type')}}</th>
                        <th>{{ __('Category')}}</th>
                        <th>{{ __('Live Status')}}</th>
                        <th>{{ __('Active')}}</th>
                        <th>{{ __('Viewers')}}</th>
                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Add Channel Modal --}}
<div id="addChannelModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Live Channel')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="addChannelForm" method="POST">
                <div class="modal-body">
                    <div class="mb-2 d-flex align-items-center">
                        <img id="imgAddChannelPreview" src="{{ url('assets/img/placeholder.png')}}" alt="" class="rounded" height="80" width="80">
                    </div>
                    <div class="mb-2">
                        <label for="channel_logo" class="form-label">{{ __('Channel Logo')}}</label>
                        <input id="inputAddChannelLogo" class="form-control" type="file" accept="image/*" name="channel_logo">
                    </div>
                    <div class="mb-2">
                        <label for="channel_name" class="form-label">{{ __('Channel Name')}}</label>
                        <input class="form-control" type="text" name="channel_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="stream_url" class="form-label">{{ __('Stream URL (m3u8, YouTube Live, RTMP)')}}</label>
                        <input class="form-control" type="text" name="stream_url" required>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label for="stream_type" class="form-label">{{ __('Stream Type')}}</label>
                            <select name="stream_type" class="form-control">
                                <option value="hls">{{ __('HLS (m3u8)') }}</option>
                                <option value="youtube">{{ __('YouTube Live') }}</option>
                                <option value="rtmp">{{ __('RTMP') }}</option>
                            </select>
                        </div>
                        <div class="mb-2 col-6">
                            <label for="sort_order" class="form-label">{{ __('Sort Order')}}</label>
                            <input class="form-control" type="number" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label for="category" class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" name="category" placeholder="News, Entertainment...">
                        </div>
                        <div class="mb-2 col-6">
                            <label for="language" class="form-label">{{ __('Language')}}</label>
                            <input class="form-control" type="text" name="language" placeholder="Hindi, English...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close')}}</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                        {{ __('Submit')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Channel Modal --}}
<div id="editChannelModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Live Channel')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="editChannelForm" method="POST">
                <input type="hidden" name="id" id="editChannelId">
                <div class="modal-body">
                    <div class="mb-2">
                        <label for="channel_name" class="form-label">{{ __('Channel Name')}}</label>
                        <input class="form-control" type="text" id="editChannelName" name="channel_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="stream_url" class="form-label">{{ __('Stream URL')}}</label>
                        <input class="form-control" type="text" id="editStreamUrl" name="stream_url" required>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label for="stream_type" class="form-label">{{ __('Stream Type')}}</label>
                            <select name="stream_type" id="editStreamType" class="form-control">
                                <option value="hls">{{ __('HLS (m3u8)') }}</option>
                                <option value="youtube">{{ __('YouTube Live') }}</option>
                                <option value="rtmp">{{ __('RTMP') }}</option>
                            </select>
                        </div>
                        <div class="mb-2 col-6">
                            <label for="sort_order" class="form-label">{{ __('Sort Order')}}</label>
                            <input class="form-control" type="number" id="editSortOrder" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label for="category" class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" id="editCategory" name="category">
                        </div>
                        <div class="mb-2 col-6">
                            <label for="language" class="form-label">{{ __('Language')}}</label>
                            <input class="form-control" type="text" id="editLanguage" name="language">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="channel_logo" class="form-label">{{ __('Channel Logo (Optional)')}}</label>
                        <input class="form-control" type="file" accept="image/*" name="channel_logo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close')}}</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                        {{ __('Submit')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
