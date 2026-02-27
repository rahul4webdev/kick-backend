@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/green_screen_bgs.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom">
        <h4 class="card-title mb-0 header-title">
            {{ __('Green Screen Backgrounds')}}
        </h4>
        <a data-bs-toggle="modal" data-bs-target="#addBgModal" class="btn btn-dark ms-auto">{{ __('Add Background')}}</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="greenScreenBgTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Preview')}}</th>
                        <th>{{ __('Title')}}</th>
                        <th>{{ __('Type')}}</th>
                        <th>{{ __('Category')}}</th>
                        <th>{{ __('Sort Order')}}</th>
                        <th>{{ __('Status')}}</th>
                        <th style="width: 200px;" class="text-end">{{ __('Action')}}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Add Background Modal --}}
<div id="addBgModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('Add Background')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="addBgForm" method="POST">
                <div class="modal-body">
                    <div class="mb-2 d-flex align-items-center">
                        <img id="imgAddBgPreview" src="{{ url('assets/img/placeholder.png')}}" alt="" class="rounded" height="80" width="80">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Title')}}</label>
                        <input class="form-control" type="text" name="title" required>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Type')}}</label>
                            <select class="form-control" name="type" id="addBgType" required>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                            </select>
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" name="category" placeholder="Nature, Abstract...">
                        </div>
                    </div>
                    <div class="mb-2" id="addImageField">
                        <label class="form-label">{{ __('Image')}}</label>
                        <input id="inputAddBgImage" class="form-control" type="file" accept="image/*" name="image">
                    </div>
                    <div class="mb-2 d-none" id="addVideoField">
                        <label class="form-label">{{ __('Video')}}</label>
                        <input class="form-control" type="file" accept="video/*" name="video">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Sort Order')}}</label>
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

{{-- Edit Background Modal --}}
<div id="editBgModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('Edit Background')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <form id="editBgForm" method="POST">
                <input type="hidden" name="id" id="editBgId">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">{{ __('Title')}}</label>
                        <input class="form-control" type="text" id="editBgTitle" name="title" required>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Type')}}</label>
                            <select class="form-control" name="type" id="editBgType" required>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                            </select>
                        </div>
                        <div class="mb-2 col-6">
                            <label class="form-label">{{ __('Category')}}</label>
                            <input class="form-control" type="text" id="editBgCategory" name="category">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Image (Optional)')}}</label>
                        <input class="form-control" type="file" accept="image/*" name="image">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Video (Optional)')}}</label>
                        <input class="form-control" type="file" accept="video/*" name="video">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Sort Order')}}</label>
                        <input class="form-control" type="number" id="editBgSortOrder" name="sort_order">
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
