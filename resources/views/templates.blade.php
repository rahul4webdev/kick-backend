@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/templates.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Video Templates')}}
        </h4>
        <a data-bs-toggle="modal" data-bs-target="#addTemplateModal" class="btn btn-dark ms-auto">{{ __('Add Template')}}</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="templatesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Thumbnail')}}</th>
                        <th>{{ __('Name')}}</th>
                        <th>{{ __('Category')}}</th>
                        <th>{{ __('Clips')}}</th>
                        <th>{{ __('Duration')}}</th>
                        <th>{{ __('Uses')}}</th>
                        <th>{{ __('Status')}}</th>
                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Add Template Modal --}}
<div id="addTemplateModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('Add Template')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="addTemplateForm" method="POST">
                <div class="modal-body">
                    <div class="mb-2 d-flex align-items-center">
                        <img id="imgAddTemplatePreview" src="{{ url('assets/img/placeholder.png')}}" alt="" class="rounded" height="80" width="80">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Thumbnail')}}</label>
                        <input id="inputAddTemplateThumbnail" class="form-control" type="file" accept="image/*" name="thumbnail">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Preview Video (Optional)')}}</label>
                        <input class="form-control" type="file" accept="video/*" name="preview_video">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Template Name')}}</label>
                        <input class="form-control" type="text" name="name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Description')}}</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Number of Clips')}}</label>
                            <input class="form-control" type="number" name="clip_count" min="1" max="20" value="3" required>
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Total Duration (sec)')}}</label>
                            <input class="form-control" type="number" name="duration_sec" min="1" max="300" value="15" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" name="category" placeholder="Trending, Dance, Comedy...">
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Sort Order')}}</label>
                            <input class="form-control" type="number" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Music ID (Optional)')}}</label>
                        <input class="form-control" type="number" name="music_id" placeholder="Link to existing music ID">
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

{{-- Edit Template Modal --}}
<div id="editTemplateModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('Edit Template')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="editTemplateForm" method="POST">
                <input type="hidden" name="id" id="editTemplateId">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">{{ __('Template Name')}}</label>
                        <input class="form-control" type="text" id="editTemplateName" name="name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Description')}}</label>
                        <textarea class="form-control" id="editTemplateDescription" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Number of Clips')}}</label>
                            <input class="form-control" type="number" id="editClipCount" name="clip_count" min="1" max="20" required>
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Total Duration (sec)')}}</label>
                            <input class="form-control" type="number" id="editDurationSec" name="duration_sec" min="1" max="300" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" id="editTemplateCategory" name="category">
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Sort Order')}}</label>
                            <input class="form-control" type="number" id="editTemplateSortOrder" name="sort_order">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Thumbnail (Optional)')}}</label>
                        <input class="form-control" type="file" accept="image/*" name="thumbnail">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Preview Video (Optional)')}}</label>
                        <input class="form-control" type="file" accept="video/*" name="preview_video">
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
