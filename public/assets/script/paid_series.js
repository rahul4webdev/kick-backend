$(document).ready(function () {
    $(".side-nav-item").removeClass("menuitem-active");
    $(".paidSeriesAdmin").addClass("menuitem-active");

    var paidSeriesTables = {};

    function initPaidSeriesTable(tableId, statusFilter) {
        paidSeriesTables[tableId] = $("#" + tableId).DataTable({
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
                url: `${domainUrl}listPaidSeries_Admin`,
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

    initPaidSeriesTable("allPaidSeriesTable", "");
    initPaidSeriesTable("pendingPaidSeriesTable", 1);
    initPaidSeriesTable("approvedPaidSeriesTable", 2);
    initPaidSeriesTable("rejectedPaidSeriesTable", 3);

    function reloadAllPaidSeriesTables() {
        Object.keys(paidSeriesTables).forEach(function (key) {
            paidSeriesTables[key].ajax.reload();
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
                title: `Are you sure you want to ${actionText} this paid series?`,
                showDenyButton: true,
                denyButtonText: `Cancel`,
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = `${domainUrl}updatePaidSeriesStatus`;
                    var formData = new FormData();
                    formData.append("id", id);
                    formData.append("status", status);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadAllPaidSeriesTables();
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
                    var url = `${domainUrl}deletePaidSeries_Admin`;
                    var formData = new FormData();
                    formData.append("id", id);
                    try {
                        doAjax(url, formData).then(function (response) {
                            if (response.status) {
                                reloadAllPaidSeriesTables();
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
