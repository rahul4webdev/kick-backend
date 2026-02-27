$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".liveChannels").addClass("menuitem-active");

    $("#liveChannelsTable").DataTable({
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
            url: `${domainUrl}listLiveChannels_Admin`,
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

    // Add Channel
    $("#addChannelForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}addLiveChannel_Admin`;
            var formId = "#addChannelForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["liveChannelsTable"]);
                        modalHide("#addChannelModal");
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

    // Edit Channel
    $("#editChannelForm").on("submit", function (e) {
        e.preventDefault();
        checkUserType(() => {
            var url = `${domainUrl}editLiveChannel_Admin`;
            var formId = "#editChannelForm";
            var formdata = collectFormData(formId);
            showFormSpinner(formId);
            try {
                doAjax(url, formdata).then(function (response) {
                    hideFormSpinner(formId);
                    if (response.status) {
                        reloadDataTables(["liveChannelsTable"]);
                        modalHide("#editChannelModal");
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
        var channelName = $(this).data("channel-name");
        var streamUrl = $(this).data("stream-url");
        var streamType = $(this).data("stream-type");
        var category = $(this).data("category");
        var language = $(this).data("language");
        var sortOrder = $(this).data("sort-order");

        $("#editChannelId").val(id);
        $("#editChannelName").val(channelName);
        $("#editStreamUrl").val(streamUrl);
        $("#editStreamType").val(streamType);
        $("#editCategory").val(category);
        $("#editLanguage").val(language);
        $("#editSortOrder").val(sortOrder);

        modalShow("#editChannelModal");
    });

    // Toggle active status
    $(document).on("click", ".toggle-active", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        var url = `${domainUrl}toggleLiveChannelStatus`;
        var formData = new FormData();
        formData.append("id", id);
        try {
            doAjax(url, formData).then(function (response) {
                if (response.status) {
                    reloadDataTables(["liveChannelsTable"]);
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
                    var url = `${domainUrl}deleteLiveChannel_Admin`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadDataTables(["liveChannelsTable"]);
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

    previewImage("#inputAddChannelLogo", "#imgAddChannelPreview");

    $("#addChannelModal").on("hidden.bs.modal", function () {
        removeImageSource("#imgAddChannelPreview");
        resetForm("#addChannelForm");
    });
});
