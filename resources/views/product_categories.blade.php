@extends('include.app')
@section('script')
<script src="{{ asset('assets/script/product_categories.js') }}"></script>
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center border-bottom justify-content-between">
        <h4 class="card-title mb-0 header-title">
            {{ __('Product Categories')}}
        </h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="uil-plus"></i> {{ __('Add Category') }}
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="categoriesTable" class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Icon</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th style="width: 150px;" class="text-end">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="addCategoryName">
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon (optional)</label>
                    <input type="text" class="form-control" id="addCategoryIcon" placeholder="e.g. ri-t-shirt-line">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" class="form-control" id="addCategorySortOrder" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnAddCategory">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editCategoryId">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="editCategoryName">
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon</label>
                    <input type="text" class="form-control" id="editCategoryIcon">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" class="form-control" id="editCategorySortOrder">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnEditCategory">Save</button>
            </div>
        </div>
    </div>
</div>

@endsection
