@extends('admin_layouts/main')
@section('pageSpecificCss')
    <link href="{{ asset('assets/bundles/datatables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet">
@stop
@section('content')
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Settings</h4>
                        </div>
                        <div class="card-body">
                            <form Autocomplete="off" class="form-group form-border" id="globalSettingsForm" action=""
                                method="post">

                                @csrf

                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Currency') }}</label>
                                        <input value="{{ $data->currency }}" type="text" class="form-control"
                                            name="currency" required>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Coin Value (According to the Currency)') }}</label>
                                        <input value="{{ $data->coin_value }}" step=".0001" type="number"
                                            class="form-control" name="coin_value" required>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Reward : Video Upload') }}</label>
                                        <input value="{{ $data->reward_video_upload }}" type="number" class="form-control"
                                            name="reward_video_upload" value="" required>
                                    </div>
                                </div>

                                <div class="form-row ">

                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Min. Fans For Verification') }}</label>
                                        <input value="{{ $data->min_fans_verification }}" type="number"
                                            class="form-control" name="min_fans_verification" value="" required>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Min. Coins To Redeem') }}</label>
                                        <input value="{{ $data->min_redeem_coins }}" type="number" class="form-control"
                                            name="min_redeem_coins" value="" required>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="">{{ __('Maximum Videos User Can Upload Daily') }}</label>
                                        <input value="{{ $data->max_upload_daily }}" type="number" class="form-control"
                                            name="max_upload_daily" value="" required>
                                    </div>
                                </div>


                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label for="">Min. Fans required for livestream</label>
                                        <input type="text" class="form-control" name="min_fans_for_live"
                                            value="{{ $data->min_fans_for_live }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="">Help Email</label>
                                        <input type="text" class="form-control" name="help_mail"
                                            value="{{ $data->help_mail }}">
                                    </div>


                                </div>
                                <div class="form-row ">
                                <div class="form-group col-md-4">
                                    <label class="d-block" for="">Compress Video before upload <br>(If Video size is more than 20MB. Video quality will be down.)</label>
                                    <label class="switch ml-1">
                                        <input type="checkbox" name="is_compress" id="compressSwitch"
                                            {{ $data->is_compress == 1 ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                </div>



                                <div class="my-4">
                                    <h5 class="text-dark">{{ __('Admob Ad Units') }}</h5>
                                </div>
                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label for="">Admob Banner Ad Unit : Android</label>
                                        <input type="text" class="form-control" name="admob_banner"
                                            value="{{ $data->admob_banner }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="">Admob Interstitial Ad Unit : Android</label>
                                        <input type="text" class="form-control" name="admob_int"
                                            value="{{ $data->admob_int }}">
                                    </div>
                                </div>
                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label for="">Admob Banner Ad Unit : iOS</label>
                                        <input type="text" class="form-control" name="admob_banner_ios"
                                            value="{{ $data->admob_banner_ios }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="">Admob Interstitial Ad Unit : iOS</label>
                                        <input type="text" class="form-control" name="admob_int_ios"
                                            value="{{ $data->admob_int_ios }}">
                                    </div>
                                </div>
                                <div class="my-4">
                                    <h5 class="text-dark">{{ __('Livestream Control') }}</h5>
                                    <spanm>If you set any of the below values to <strong>0</strong> The livestream Timeout
                                        function will
                                        stop working.</span>
                                </div>
                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label
                                            for="">{{ __('Minimum Viewers Required (To Contine Livestreaming)') }}</label>
                                        <input type="number" class="form-control" name="live_min_viewers"
                                            value="{{ $data->live_min_viewers }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label
                                            for="">{{ __("Livestream Timeout Minutes (if don't get minimum viewers)") }}</label>
                                        <input type="number" class="form-control" name="live_timeout"
                                            value="{{ $data->live_timeout }}">
                                    </div>

                                </div>
                                <div class="mt-4 mb-2">
                                    <h5 class="text-dark">{{ __('Content Moderation (Sight Engine)') }}</h5>
                                    <spanm>If You keep this on and add all required credentials from SightEngine, App will refuse sensitive videos to be uploaded.</span>
                                </div>

                                <label class="switch ">
                                    <input type="checkbox" name="is_content_moderation" id="contentModerationSwitch"
                                        {{ $data->is_content_moderation == 1 ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>

                                <div class="form-row ">
                                    <div class="form-group col-md-4">
                                        <label
                                            for="">{{ __('API User') }}</label>
                                        <input type="text" class="form-control" name="sight_engine_api_user"
                                            value="{{ $data->sight_engine_api_user }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label
                                            for="">{{ __("API Secret") }}</label>
                                        <input type="text" class="form-control" name="sight_engine_api_secret"
                                            value="{{ $data->sight_engine_api_secret }}">
                                    </div>

                                </div>

                                <div class="form-group-submit">
                                    <button @if (Session::get('admin_id') == 2) {{ 'disabled' }} @endif
                                        class="btn btn-primary " type="submit">{{ __('Save') }}</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </section>
@endsection

@section('pageSpecificJs')

    <script src="{{ asset('assets/bundles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/bundles/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/page/datatables.js') }}"></script>
    <script src="{{ asset('assets/bundles/izitoast/js/iziToast.min.js') }}"></script>

    <script>

        $(document).ready(function() {

            $(document).on("change", "#compressSwitch", function (event) {
        event.preventDefault();

        if ($(this).prop("checked") == true) {
            var value = 1;
        } else {
            value = 0;
        }


        $.ajax({
                url: '{{ route("changeCompressStatus") }}',
                type: 'POST',
                data: {"status":value},
                dataType: "json",
                cache: false,
                success: function (data) {
                    if (data.status) {
                        swal("Confirm!", "Settings updated successfully!", "success");
                    } else {
                        swal("Confirm!", "Settings not updated!", "error");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(errorThrown);
                }
            });
    });

            $(document).on("change", "#contentModerationSwitch", function (event) {
        event.preventDefault();

        if ($(this).prop("checked") == true) {
            var value = 1;
        } else {
            value = 0;
        }


        $.ajax({
                url: '{{ route("changeContentModerationStatus") }}',
                type: 'POST',
                data: {"status":value},
                dataType: "json",
                cache: false,
                success: function (data) {
                    if (data.status) {
                        swal("Confirm!", "Settings updated successfully!", "success");
                    } else {
                        swal("Confirm!", "Settings not updated!", "error");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(errorThrown);
                }
            });
    });


            $(document).on('submit', '#globalSettingsForm', function(e) {
                e.preventDefault();
                var formdata = new FormData($("#globalSettingsForm")[0]);
                $('.loader').show();
                $.ajax({
                    url: '{{ route('updateGlobalSettings') }}',
                    type: 'POST',
                    data: formdata,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(data) {
                        // $('.loader').hide();
                        if (data.status) {
                            console.log(data);
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        } else {
                            iziToast.error({
                                title: 'Error!',
                                message: data.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            });

        });
    </script>

@endsection
