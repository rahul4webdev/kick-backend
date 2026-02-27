$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".seriesAdmin").addClass("menuitem-active");

    var seriesTables = {};

    function initSeriesTable(tableId, statusFilter) {
        seriesTables[tableId] = $("#" + tableId).DataTable({
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
                url: `${domainUrl}listSeries_Admin`,
                data: function (data) {
                    data.status_filter = statusFilter;
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

    initSeriesTable("allSeriesTable", "");
    initSeriesTable("pendingSeriesTable", 1);
    initSeriesTable("approvedSeriesTable", 2);
    initSeriesTable("rejectedSeriesTable", 3);

    function reloadAllSeriesTables() {
        Object.keys(seriesTables).forEach(function (key) {
            seriesTables[key].ajax.reload();
        });
    }

    // Update status (approve/reject)
    $(document).on("click", ".update-status", function (e) {
        e.preventDefault();
        var id = $(this).attr("rel");
        var status = $(this).data("status");
        var actionText = status == 2 ? "approve" : "reject";

        checkUserType(() => {
            Swal.fire({
                icon: "info",
                title: `Are you sure you want to ${actionText} this series?`,
                showDenyButton: true,
                denyButtonText: `Cancel`,
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = `${domainUrl}updateSeriesStatus`;
                    var formData = new FormData();
                    formData.append("id", id);
                    formData.append("status", status);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadAllSeriesTables();
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

    // Delete
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
                    var url = `${domainUrl}deleteSeries_Admin`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadAllSeriesTables();
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
