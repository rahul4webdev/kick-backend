$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".templates").addClass("menuitem-active");

    $("#templatesTable").DataTable({
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
            url: `${domainUrl}listTemplates`,
            data: function (data) {},
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

    // Add Template
    $("#addTemplateForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}addTemplate`;
            var formId = "#addTemplateForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["templatesTable"]);
                        modalHide("#addTemplateModal");
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

    // Edit Template
    $("#editTemplateForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}editTemplate`;
            var formId = "#editTemplateForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["templatesTable"]);
                        modalHide("#editTemplateModal");
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

    // Edit button click
    $(document).on("click", ".edit", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        $("#editTemplateId").val(id);
        $("#editTemplateName").val($(this).data("name"));
        $("#editTemplateDescription").val($(this).data("description"));
        $("#editClipCount").val($(this).data("clip-count"));
        $("#editDurationSec").val($(this).data("duration-sec"));
        $("#editTemplateCategory").val($(this).data("category"));
        $("#editTemplateSortOrder").val($(this).data("sort-order"));
        modalShow("#editTemplateModal");
    });

    // Toggle active status
    $(document).on("click", ".toggle-active", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        var url = `${domainUrl}toggleTemplateStatus`;
        var formData = new FormData();
        formData.append("id", id);
        try {
            doAjax(url, formData).then(function (response) {
                if (response.status) {
                    reloadDataTables(["templatesTable"]);
                    showSuccessToast(response.message);
                } else {
                    showErrorToast(response.message);
                }
            });
        } catch (error) {
            console.log("Error! : ", error.message);
            showErrorToast(error.message);
        }
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
                    var url = `${domainUrl}deleteTemplate`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadDataTables(["templatesTable"]);
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

    previewImage("#inputAddTemplateThumbnail", "#imgAddTemplatePreview");

    $("#addTemplateModal").on("hidden.bs.modal", function () {
        removeImageSource("#imgAddTemplatePreview");
        resetForm("#addTemplateForm");
    });
});
