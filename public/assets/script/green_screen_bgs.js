$(document).ready(function () {
    var table = $('#greenScreenBgTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'listGreenScreenBgs',
            type: 'POST'
        },
        columns: [
            { data: 0, name: 'preview', orderable: false, searchable: false },
            { data: 1, name: 'title' },
            { data: 2, name: 'type', orderable: false },
            { data: 3, name: 'category' },
            { data: 4, name: 'sort_order' },
            { data: 5, name: 'status', orderable: false },
            { data: 6, name: 'action', orderable: false, searchable: false },
        ]
    });

    // Toggle type fields in add modal
    $('#addBgType').on('change', function () {
        if ($(this).val() === 'video') {
            $('#addImageField').addClass('d-none');
            $('#addVideoField').removeClass('d-none');
        } else {
            $('#addImageField').removeClass('d-none');
            $('#addVideoField').addClass('d-none');
        }
    });

    // Image preview
    $('#inputAddBgImage').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#imgAddBgPreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Add
    $('#addBgForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var spinner = $(this).find('.spinner');
        spinner.removeClass('hide');

        $.ajax({
            type: 'POST',
            url: 'addGreenScreenBg',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                spinner.addClass('hide');
                if (response.status) {
                    $('#addBgModal').modal('hide');
                    $('#addBgForm')[0].reset();
                    $('#imgAddBgPreview').attr('src', 'assets/img/placeholder.png');
                    table.ajax.reload();
                    successMsg(response.message);
                } else {
                    errorMsg(response.message);
                }
            },
            error: function () {
                spinner.addClass('hide');
                errorMsg('Something went wrong');
            }
        });
    });

    // Edit button click
    $(document).on('click', '.edit', function () {
        var id = $(this).attr('rel');
        var title = $(this).data('title');
        var category = $(this).data('category');
        var type = $(this).data('type');
        var sortOrder = $(this).data('sort-order');

        $('#editBgId').val(id);
        $('#editBgTitle').val(title);
        $('#editBgCategory').val(category);
        $('#editBgType').val(type);
        $('#editBgSortOrder').val(sortOrder);
        $('#editBgModal').modal('show');
    });

    // Edit submit
    $('#editBgForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var spinner = $(this).find('.spinner');
        spinner.removeClass('hide');

        $.ajax({
            type: 'POST',
            url: 'editGreenScreenBg',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                spinner.addClass('hide');
                if (response.status) {
                    $('#editBgModal').modal('hide');
                    table.ajax.reload();
                    successMsg(response.message);
                } else {
                    errorMsg(response.message);
                }
            },
            error: function () {
                spinner.addClass('hide');
                errorMsg('Something went wrong');
            }
        });
    });

    // Toggle active
    $(document).on('click', '.toggle-active', function () {
        var id = $(this).attr('rel');
        $.post('toggleGreenScreenBgStatus', { id: id }, function (response) {
            if (response.status) {
                table.ajax.reload();
                successMsg(response.message);
            } else {
                errorMsg(response.message);
            }
        });
    });

    // Delete
    $(document).on('click', '.delete', function () {
        var id = $(this).attr('rel');
        if (confirm('Are you sure you want to delete this background?')) {
            $.post('deleteGreenScreenBg', { id: id }, function (response) {
                if (response.status) {
                    table.ajax.reload();
                    successMsg(response.message);
                } else {
                    errorMsg(response.message);
                }
            });
        }
    });
});
