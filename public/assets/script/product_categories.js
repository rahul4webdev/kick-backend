$(document).ready(function () {
    var table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'listProductCategories_Admin',
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ],
        order: [],
    });

    // Add Category
    $('#btnAddCategory').click(function () {
        var name = $('#addCategoryName').val();
        if (!name) { alert('Name is required'); return; }

        $.ajax({
            url: 'addProductCategory',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: name,
                icon: $('#addCategoryIcon').val(),
                sort_order: $('#addCategorySortOrder').val()
            },
            success: function (res) {
                if (res.status) {
                    table.ajax.reload(null, false);
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryName').val('');
                    $('#addCategoryIcon').val('');
                    $('#addCategorySortOrder').val('0');
                }
                alert(res.message);
            }
        });
    });

    // Edit - populate modal
    $(document).on('click', '.edit', function (e) {
        e.preventDefault();
        $('#editCategoryId').val($(this).attr('rel'));
        $('#editCategoryName').val($(this).data('name'));
        $('#editCategoryIcon').val($(this).data('icon'));
        $('#editCategorySortOrder').val($(this).data('sort'));
        $('#editCategoryModal').modal('show');
    });

    // Save Edit
    $('#btnEditCategory').click(function () {
        $.ajax({
            url: 'editProductCategory',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: $('#editCategoryId').val(),
                name: $('#editCategoryName').val(),
                icon: $('#editCategoryIcon').val(),
                sort_order: $('#editCategorySortOrder').val()
            },
            success: function (res) {
                if (res.status) {
                    table.ajax.reload(null, false);
                    $('#editCategoryModal').modal('hide');
                }
                alert(res.message);
            }
        });
    });

    // Delete
    $(document).on('click', '.delete', function (e) {
        e.preventDefault();
        var id = $(this).attr('rel');
        if (!confirm('Are you sure you want to delete this category?')) return;

        $.ajax({
            url: 'deleteProductCategory',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), id: id },
            success: function (res) {
                if (res.status) {
                    table.ajax.reload(null, false);
                }
                alert(res.message);
            }
        });
    });
});
