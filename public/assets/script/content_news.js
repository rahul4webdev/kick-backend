$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".contentNews").addClass("menuitem-active");

    $("#newsPostsTable").DataTable({
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
            url: `${domainUrl}listNewsPosts_Content`,
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

    // Delete post
    $(document).on("click", ".delete-post", function (e) {
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
                    var delete_url = `${domainUrl}deletePost_Admin`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(delete_url, formData).then(function (response) {
                            if (response.status) {
                                reloadDataTables(["newsPostsTable"]);
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

    // Toggle featured
    $(document).on("click", ".toggle-featured", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        var url = `${domainUrl}toggleFeaturedPost`;
        var formData = new FormData();
        formData.append("id", id);
        try {
            doAjax(url, formData).then(function (response) {
                if (response.status) {
                    reloadDataTables(["newsPostsTable"]);
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

    // View video post
    $(document).on("click", ".viewReelPost, .viewVideoPost", function (event) {
        event.preventDefault();
        var contentUrl = $(this).data("videourl");
        var postId = $(this).data("postid");

        $("#video source").attr("src", contentUrl);
        $("#video")[0].load();
        fetchDescriptionAndDisplay(postId, "videoDescription");
        $("#videoPostModal").modal("show");
        $("#video").trigger("play");
    });

    $("#videoPostModal").on("hidden.bs.modal", function () {
        $("#video").trigger("pause");
    });

    function fetchDescriptionAndDisplay(postId, placeId) {
        var url = `${domainUrl}fetchFormattedPostDesc`;
        var formdata = new FormData();
        formdata.append("postId", postId);
        try {
            doAjax(url, formdata).then(function (response) {
                if (response.status) {
                    $("#" + placeId).html(response.data);
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
