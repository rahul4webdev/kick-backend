@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/content_genres.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Content Genres')}}
        </h4>
        <a data-bs-toggle="modal" data-bs-target="#addGenreModal" class="btn btn-dark ms-auto">{{ __('Add Genre')}}</a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3 mb-2 mb-sm-0">
                <div class="nav nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active show" id="v-pills-allGenres-tab" data-bs-toggle="pill" href="#v-pills-allGenres" role="tab" aria-controls="v-pills-allGenres" aria-selected="true" data-content-type="">
                        <span class="d-md-block">{{ __('All') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-musicGenres-tab" data-bs-toggle="pill" href="#v-pills-musicGenres" role="tab" aria-controls="v-pills-musicGenres" aria-selected="false" data-content-type="1">
                        <span class="d-md-block">{{ __('Music Video') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-trailerGenres-tab" data-bs-toggle="pill" href="#v-pills-trailerGenres" role="tab" aria-controls="v-pills-trailerGenres" aria-selected="false" data-content-type="2">
                        <span class="d-md-block">{{ __('Trailer') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-newsGenres-tab" data-bs-toggle="pill" href="#v-pills-newsGenres" role="tab" aria-controls="v-pills-newsGenres" aria-selected="false" data-content-type="3">
                        <span class="d-md-block">{{ __('News') }}</span>
                    </a>
                    <a class="nav-link" id="v-pills-storyGenres-tab" data-bs-toggle="pill" href="#v-pills-storyGenres" role="tab" aria-controls="v-pills-storyGenres" aria-selected="false" data-content-type="4">
                        <span class="d-md-block">{{ __('Short Story') }}</span>
                    </a>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="tab-content mt-3" id="v-pills-tabContent">
                    {{-- All Genres --}}
                    <div class="tab-pane fade active show" id="v-pills-allGenres" role="tabpanel" aria-labelledby="v-pills-allGenres-tab">
                        <div class="table-responsive">
                            <table id="allGenresTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Content Type')}}</th>
                                        <th>{{ __('Sort Order')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Music Video Genres --}}
                    <div class="tab-pane fade" id="v-pills-musicGenres" role="tabpanel" aria-labelledby="v-pills-musicGenres-tab">
                        <div class="table-responsive">
                            <table id="musicGenresTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Content Type')}}</th>
                                        <th>{{ __('Sort Order')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Trailer Genres --}}
                    <div class="tab-pane fade" id="v-pills-trailerGenres" role="tabpanel" aria-labelledby="v-pills-trailerGenres-tab">
                        <div class="table-responsive">
                            <table id="trailerGenresTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Content Type')}}</th>
                                        <th>{{ __('Sort Order')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- News Genres --}}
                    <div class="tab-pane fade" id="v-pills-newsGenres" role="tabpanel" aria-labelledby="v-pills-newsGenres-tab">
                        <div class="table-responsive">
                            <table id="newsGenresTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Content Type')}}</th>
                                        <th>{{ __('Sort Order')}}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Short Story Genres --}}
                    <div class="tab-pane fade" id="v-pills-storyGenres" role="tabpanel" aria-labelledby="v-pills-storyGenres-tab">
                        <div class="table-responsive">
                            <table id="storyGenresTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name')}}</th>
                                        <th>{{ __('Content Type')}}</th>
                                        <th>{{ __('Sort Order')}}</th>
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

{{-- Add Genre Modal --}}
<div id="addGenreModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Genre')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="addGenreForm" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name')}}</label>
                        <input class="form-control" type="text" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="content_type" class="form-label">{{ __('Content Type')}}</label>
                        <select name="content_type" class="form-control" required>
                            <option value="1">{{ __('Music Video') }}</option>
                            <option value="2">{{ __('Trailer') }}</option>
                            <option value="3">{{ __('News') }}</option>
                            <option value="4">{{ __('Short Story') }}</option>
                        </select>
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

{{-- Edit Genre Modal --}}
<div id="editGenreModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Genre')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="editGenreForm" method="POST">
                <input type="hidden" name="id" id="editGenreId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name')}}</label>
                        <input class="form-control" type="text" id="editGenreName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="content_type" class="form-label">{{ __('Content Type')}}</label>
                        <select name="content_type" id="editGenreContentType" class="form-control" required>
                            <option value="1">{{ __('Music Video') }}</option>
                            <option value="2">{{ __('Trailer') }}</option>
                            <option value="3">{{ __('News') }}</option>
                            <option value="4">{{ __('Short Story') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">{{ __('Sort Order')}}</label>
                        <input class="form-control" type="number" id="editGenreSortOrder" name="sort_order" value="0">
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
