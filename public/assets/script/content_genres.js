$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".contentGenres").addClass("menuitem-active");

    var genreTables = {};

    // Initialize DataTable helper
    function initGenreTable(tableId, contentType) {
        genreTables[tableId] = $("#" + tableId).DataTable({
            autoWidth: false,
            processing: true,
            serverSide: true,
            serverMethod: "post",
            ordering: false,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>",
                },
            },
            ajax: {
                url: `${domainUrl}listContentGenres`,
                data: function (data) {
                    data.content_type = contentType;
                },
                error: (error) => {
                    console.log(error);
                },
            },
            drawCallback: function () {
                $(".dataTables_paginate > .pagination").addClass(
                    "pagination-rounded"
                );
            },
        });
    }

    // All genres (no filter)
    initGenreTable("allGenresTable", "");
    // Music Video genres
    initGenreTable("musicGenresTable", 1);
    // Trailer genres
    initGenreTable("trailerGenresTable", 2);
    // News genres
    initGenreTable("newsGenresTable", 3);
    // Short Story genres
    initGenreTable("storyGenresTable", 4);

    function reloadAllGenreTables() {
        Object.keys(genreTables).forEach(function (key) {
            genreTables[key].ajax.reload();
        });
    }

    // Add Genre
    $("#addGenreForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}addContentGenre`;
            var formId = "#addGenreForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadAllGenreTables();
                        modalHide("#addGenreModal");
                        resetForm(formId);
                        showSuccessToast(response.message);
                    } else {
                        showErrorToast(response.message);
                    }
                });
            } catch (error) {
                console.log("Error! : ", error.message);
                hideFormSpinner(formId);
                showErrorToast(error.message);
            }
        });
    });

    // Edit Genre
    $("#editGenreForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}editContentGenre`;
            var formId = "#editGenreForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadAllGenreTables();
                        modalHide("#editGenreModal");
                        resetForm(formId);
                        showSuccessToast(response.message);
                    } else {
                        showErrorToast(response.message);
                    }
                });
            } catch (error) {
                console.log("Error! : ", error.message);
                hideFormSpinner(formId);
                showErrorToast(error.message);
            }
        });
    });

    // Edit button click — populate modal
    $(document).on("click", ".edit", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        var name = $(this).data("name");
        var contentType = $(this).data("content-type");
        var sortOrder = $(this).data("sort-order");

        $("#editGenreId").val(id);
        $("#editGenreName").val(name);
        $("#editGenreContentType").val(contentType);
        $("#editGenreSortOrder").val(sortOrder);

        modalShow("#editGenreModal");
    });

    // Delete button click
    $(document).on("click", ".delete", function (e) {
        e.preventDefault();

        checkUserType(() => {
            Swal.fire({
                icon: "info",
                title: "Are you sure?",
                showDenyButton: true,
                denyButtonText: `Cancel`,
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.isConfirmed) {
                    var id = $(this).attr("rel");
                    var url = `${domainUrl}deleteContentGenre`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadAllGenreTables();
                                showSuccessToast(response.message);
                            } else {
                                showErrorToast(response.message);
                            }
                        });
                    } catch (error) {
                        console.log("Error! : ", error.message);
                        showErrorToast(error.message);
                    }
                }
            });
        });
    });
});
