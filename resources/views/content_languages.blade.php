@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/content_languages.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Content Languages')}}
        </h4>
        <a data-bs-toggle="modal" data-bs-target="#addLanguageModal" class="btn btn-dark ms-auto">{{ __('Add Language')}}</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="contentLanguagesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name')}}</th>
                        <th>{{ __('Code')}}</th>
                        <th>{{ __('Sort Order')}}</th>
                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Add Language Modal --}}
<div id="addLanguageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Language')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="addLanguageForm" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name')}}</label>
                        <input class="form-control" type="text" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">{{ __('Code (e.g. hi, en, pa)')}}</label>
                        <input class="form-control" type="text" name="code">
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">{{ __('Sort Order')}}</label>
                        <input class="form-control" type="number" name="sort_order" value="0">
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

{{-- Edit Language Modal --}}
<div id="editLanguageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Language')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="editLanguageForm" method="POST">
                <input type="hidden" name="id" id="editLanguageId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name')}}</label>
                        <input class="form-control" type="text" id="editLanguageName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">{{ __('Code (e.g. hi, en, pa)')}}</label>
                        <input class="form-control" type="text" id="editLanguageCode" name="code">
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">{{ __('Sort Order')}}</label>
                        <input class="form-control" type="number" id="editLanguageSortOrder" name="sort_order" value="0">
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
