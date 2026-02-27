$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".contentLanguages").addClass("menuitem-active");

    $("#contentLanguagesTable").DataTable({
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
            url: `${domainUrl}listContentLanguages`,
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

    // Add Language
    $("#addLanguageForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}addContentLanguage`;
            var formId = "#addLanguageForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["contentLanguagesTable"]);
                        modalHide("#addLanguageModal");
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

    // Edit Language
    $("#editLanguageForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}editContentLanguage`;
            var formId = "#editLanguageForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["contentLanguagesTable"]);
                        modalHide("#editLanguageModal");
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
        var code = $(this).data("code");
        var sortOrder = $(this).data("sort-order");

        $("#editLanguageId").val(id);
        $("#editLanguageName").val(name);
        $("#editLanguageCode").val(code);
        $("#editLanguageSortOrder").val(sortOrder);

        modalShow("#editLanguageModal");
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
                    var url = `${domainUrl}deleteContentLanguage`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadDataTables(["contentLanguagesTable"]);
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
