@extends('include.app')
@section('script')
    <script src="{{ asset('assets/script/settings.js') }}"></script>
    <!-- Quill Editor js -->
    <script src="{{ asset('assets/vendor/quill/quill.js') }}"></script>
    <script>
        // Add new SHA field
        $("#addSha").on("click", function() {
            let field = `
            <div class="input-group mb-2 sha-field">
                <input type="text" class="form-control sha-input" name="sha_256[]" placeholder="Enter SHA 256">
                <button type="button" class="btn btn-danger remove-sha">-</button>
            </div>`;
            $("#shaContainer").append(field);
        });

        // Remove SHA field
        $(document).on("click", ".remove-sha", function() {
            $(this).closest(".sha-field").remove();
        });

        $(document).ready(function() {
            $("#checkValidationOfApple").on("click", function() {
                let baseUrl = "https://app-site-association.cdn-apple.com/a/v1/baseUrl";

                let appUrl = "{{ config('app.url') }}";
                // Remove trailing slash
                let domainOnly = appUrl.replace(/^https?:\/\//, '').replace(/\/$/, '');

                let newUrl = baseUrl.replace("baseUrl", domainOnly);

                window.open(newUrl, "_blank");
            });

            $("#checkValidationOfAndroid").on("click", function() {
                let baseUrl =
                    "https://digitalassetlinks.googleapis.com/v1/statements:list?source.web.site=baseUrl&relation=delegate_permission/common.handle_all_urls";

                let appUrl = "{{ config('app.url') }}";
                // Remove trailing slash
                let cleanUrl = appUrl.replace(/\/$/, '');

                let newUrl = baseUrl.replace("baseUrl", cleanUrl);

                window.open(newUrl, "_blank");
            });
        });
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-2 mb-2 mb-sm-0">
            <div class="card">
                <div class="card-body p-2">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="main-nav-link nav-link first-nav-link" id="v-pills-appSettings-tab" data-bs-toggle="pill"
                            href="#v-pills-appSettings" role="tab" aria-controls="v-pills-password"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('App Settings') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-limits-tab" data-bs-toggle="pill"
                            href="#v-pills-limits" role="tab" aria-controls="v-pills-limits" aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Limits') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-livestream-tab" data-bs-toggle="pill"
                            href="#v-pills-livestream" role="tab" aria-controls="v-pills-livestream"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Livestream') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-gif-tab" data-bs-toggle="pill" href="#v-pills-gif"
                            role="tab" aria-controls="v-pills-gif" aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('GIPHY') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-sightEngine-tab" data-bs-toggle="pill"
                            href="#v-pills-sightEngine" role="tab" aria-controls="v-pills-sightEngine"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('SightEngine') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-admob-tab" data-bs-toggle="pill" href="#v-pills-admob"
                            role="tab" aria-controls="v-pills-admob" aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Admob') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-onBoarding-tab" data-bs-toggle="pill"
                            href="#v-pills-onBoarding" role="tab" aria-controls="v-pills-onBoarding"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Onboarding') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-userLevels-tab" data-bs-toggle="pill"
                            href="#v-pills-userLevels" role="tab" aria-controls="v-pills-userLevels"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('User Levels') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-reportReasons-tab" data-bs-toggle="pill"
                            href="#v-pills-reportReasons" role="tab" aria-controls="v-pills-reportReasons"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Report Reasons') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-withdrawalGateways-tab" data-bs-toggle="pill"
                            href="#v-pills-withdrawalGateways" role="tab" aria-controls="v-pills-withdrawalGateways"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Withdrawal Gateways') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-cameraeffects-tab" data-bs-toggle="pill"
                            href="#v-pills-cameraeffects" role="tab" aria-controls="v-pills-cameraeffects" aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Camera Effects') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-deeplinking-tab" data-bs-toggle="pill"
                            href="#v-pills-deeplinking" role="tab" aria-controls="v-pills-deeplinking"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Deeplink Settings') }}</span>
                        </a>
                        <hr>
                        <a class="main-nav-link nav-link" id="v-pills-privacy-policy-tab" data-bs-toggle="pill"
                            href="#v-pills-privacy-policy" role="tab" aria-controls="v-pills-privacy-policy"
                            aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Privacy Policy') }}</span>
                        </a>
                        <a class="main-nav-link nav-link" id="v-pills-terms-tab" data-bs-toggle="pill"
                            href="#v-pills-terms" role="tab" aria-controls="v-pills-terms" aria-selected="false">
                            <i class="mdi mdi-settings-outline d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Terms Of Uses') }}</span>
                        </a>
                        <hr>
                        <a class="main-nav-link nav-link " id="v-pills-setting-tab" data-bs-toggle="pill"
                            href="#v-pills-setting" role="tab" aria-controls="v-pills-setting" aria-selected="true">
                            <i class="mdi mdi-home-variant d-md-none d-block"></i>
                            <span class="d-none d-md-block">{{ __('Admin Settings') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-10">
            <div class="tab-content" id="v-pills-tabContent">
                {{-- Admin Settings --}}
                <div class="tab-pane fade " id="v-pills-setting" role="tabpanel" aria-labelledby="v-pills-setting-tab">
                    {{-- 1st card --}}
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('Admin Settings') }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="brandSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="title" class="form-label">{{ __('Title') }}</label>
                                            <input type="text" class="form-control" id="app_name" name="app_name"
                                                placeholder="Enter title" value="{{ $setting->app_name }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="favicon" class="form-label">{{ __('Favicon') }}</label>
                                            <input type="file" id="favicon" name="favicon" class="form-control">
                                            <img class="mt-2" width="80"
                                                src="{{ asset('assets/img/favicon.png') }}" alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="logo_dark" class="form-label">{{ __('Logo (Dark)') }}</label>
                                            <input type="file" id="logo_dark" name="logo_dark" class="form-control">
                                            <img class="mt-2" width="80"
                                                src="{{ asset('assets/img/logo-dark.png') }}" alt="">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="logo_light" class="form-label">{{ __('Logo (Light)') }}</label>
                                            <input type="file" id="logo_light" name="logo_light"
                                                class="form-control">
                                            <img class="mt-2" width="80" src="{{ asset('assets/img/logo.png') }}"
                                                alt="">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                    {{-- Password --}}
                    @if ($userType == 1)
                        <div class="card">
                            <div class="card-header border-bottom">
                                <h4 class="m-0 header-title">{{ __('Password') }}</h4>
                            </div>
                            <div class="card-body">
                                <form id="changePasswordForm" method="POST">
                                    <input type="hidden" name="user_type" value="{{ $userType }}">
                                    <div class="row mb-3">
                                        <div class="col-md-3 mb-3">
                                            <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                                <label for="password" class="form-label">{{ __('Old Password') }}</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="password" id="password" name="old_password"
                                                        class="form-control" placeholder="Enter your password">
                                                    <div class="input-group-text" data-password="false">
                                                        <span class="password-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                                <label for="password" class="form-label">{{ __('New Password') }}</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="password" id="new_password" name="new_password"
                                                        class="form-control" placeholder="Enter your password">
                                                    <div class="input-group-text" data-password="false">
                                                        <span class="password-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </form>
                            </div>
                        </div>
                    @endif

                </div>
                {{-- App Settings --}}
                <div class="tab-pane fade first-tab-pane" id="v-pills-appSettings" role="tabpanel"
                    aria-labelledby="v-pills-password-tab">
                    {{-- 1st card --}}
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('App Settings') }}</h4>
                        </div>
                        <div class="card-body">
                            <span class="fs-6">*Make sure to set coin value according to your currency.</span><br>
                            <span class="fs-6">*Users can use withdrawal functions only if it the switch is on
                                below.</span>
                            <form class="mt-2" id="basicSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="currency" class="form-label">{{ __('Currency') }}</label>
                                            <input type="text" class="form-control" id="currency" name="currency"
                                                value="{{ $setting->currency }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="coin_value" class="form-label">1 {{ __('Coin Value') }}</label>
                                            <input type="number" step="any" class="form-control" id="coin_value"
                                                name="coin_value" value="{{ $setting->coin_value }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="min_redeem_coins"
                                                class="form-label">{{ __('Min. Coins To Withdraw') }}</label>
                                            <input type="number" min="1" step="1" class="form-control"
                                                id="min_redeem_coins" name="min_redeem_coins"
                                                value="{{ $setting->min_redeem_coins }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="help_mail" class="form-label">{{ __('Help Email') }}</label>
                                            <input type="email" class="form-control" id="help_mail" name="help_mail"
                                                value="{{ $setting->help_mail }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Compress Post/Story Videos') }}</label>
                                            <div class="mb-0">
                                                <input name="is_compress" type="checkbox" id="switchCompressVideosStatus"
                                                    {{ $setting->is_compress == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchCompressVideosStatus"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Allow Withdrawal Of Coins') }}</label>
                                            <div class="mb-0">
                                                <input name="is_withdrawal_on" type="checkbox" id="switchWithdrawal"
                                                    {{ $setting->is_withdrawal_on == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchWithdrawal"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Watermark --}}
                                <h5>{{ __('REWARD SETTINGS') }}</h5>
                                <hr>
                                <span class="fs-6">*Users will get the following number of coins as a bonus when they
                                    register, if the switch below is turned on.</span><br>
                                <div class="row mt-2">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Registration Bonus Status') }}</label>
                                            <div class="mb-0">
                                                <input name="registration_bonus_status" type="checkbox"
                                                    id="switcRegistrationBonusStatus"
                                                    {{ $setting->registration_bonus_status == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switcRegistrationBonusStatus"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="registration_bonus_amount"
                                                class="form-label">{{ __('Registration Bonus Amount (Coins)') }}</label>
                                            <input type="number" min="1" step="1" class="form-control"
                                                id="registration_bonus_amount" name="registration_bonus_amount"
                                                value="{{ $setting->registration_bonus_amount }}">
                                        </div>
                                    </div>
                                </div>
                                {{-- Watermark --}}
                                <h5>{{ __('WATERMARK SETTINGS') }}</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="" class="form-label">{{ __('Watermark Videos') }}</label>
                                            <div class="mb-0">
                                                <input name="watermark_status" type="checkbox" id="switchWatermarkStatus"
                                                    {{ $setting->watermark_status == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchWatermarkStatus"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="watermark_image"
                                                class="form-label">{{ __('Watermark Image') }}</label>
                                            <input type="file" id="watermark_image" name="watermark_image"
                                                class="form-control">
                                            <img class="mt-2" width="80"
                                                src="{{ $baseUrl }}{{ $setting->watermark_image }}" alt="">
                                        </div>
                                    </div>
                                </div>

                                {{-- Authentication Settings --}}
                                <h5>{{ __('AUTHENTICATION SETTINGS') }}</h5>
                                <hr>
                                <span class="fs-6">*When enabled, users must verify their email address after registration before they can log in.</span><br>
                                <div class="row mt-2">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Email Verification Required') }}</label>
                                            <div class="mb-0">
                                                <input name="email_verification_enabled" type="checkbox"
                                                    id="switchEmailVerification"
                                                    {{ $setting->email_verification_enabled == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchEmailVerification"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>

                        </div>
                    </div>

                    {{-- SMTP Settings --}}
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('SMTP / Email Settings') }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="smtpSettingsForm" method="POST">
                                <span class="fs-6">*Configure SMTP to send verification and password reset emails. Use Gmail, SendGrid, Mailgun, or any SMTP provider.</span><br><br>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_host" class="form-label">{{ __('SMTP Host') }}</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                                placeholder="e.g. smtp.gmail.com" value="{{ $setting->smtp_host }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_port" class="form-label">{{ __('SMTP Port') }}</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                                placeholder="587" value="{{ $setting->smtp_port }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_username" class="form-label">{{ __('SMTP Username') }}</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                                placeholder="your@email.com" value="{{ $setting->smtp_username }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_password" class="form-label">{{ __('SMTP Password') }}</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                                placeholder="App password" value="{{ $setting->smtp_password }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_encryption" class="form-label">{{ __('Encryption') }}</label>
                                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                <option value="tls" {{ ($setting->smtp_encryption ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ ($setting->smtp_encryption ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_from_email" class="form-label">{{ __('From Email') }}</label>
                                            <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email"
                                                placeholder="noreply@yourapp.com" value="{{ $setting->smtp_from_email }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="smtp_from_name" class="form-label">{{ __('From Name') }}</label>
                                            <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                                                placeholder="Your App Name" value="{{ $setting->smtp_from_name }}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save SMTP Settings') }}</button>
                            </form>
                        </div>
                    </div>

                </div>
                {{-- Admob --}}
                <div class="tab-pane fade" id="v-pills-admob" role="tabpanel" aria-labelledby="v-pills-admob-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('Admob') }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="admobForm" method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mt-0 d-inline">{{ __('Android') }}</h4>
                                                    <!-- Admob Android Switch-->
                                                    <div class="d-inline ms-2 mb-0">
                                                        <input type="checkbox" id="switchAdmobAndroidStatus"
                                                            {{ $setting->admob_android_status == 1 ? 'checked' : '' }}
                                                            data-switch="primary" />
                                                        <label for="switchAdmobAndroidStatus"></label>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="mb-3">
                                                    <label for="admob_banner"
                                                        class="form-label">{{ __('Banner Ad Unit') }}</label>
                                                    <input class="form-control" type="text" name="admob_banner"
                                                        placeholder="Enter Ad Unit" required=""
                                                        value="{{ $setting->admob_banner }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="admob_int"
                                                        class="form-label">{{ __('Interstitial Ad Unit') }}</label>
                                                    <input class="form-control" type="text" name="admob_int"
                                                        placeholder="Enter Ad Unit" required=""
                                                        value="{{ $setting->admob_int }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mt-0 d-inline">{{ __('iOS') }}</h4>
                                                    <!-- Admob iOS Switch-->
                                                    <div class="d-inline ms-2 mb-0">
                                                        <input type="checkbox" id="switchAdmobiOSStatus"
                                                            {{ $setting->admob_ios_status == 1 ? 'checked' : '' }}
                                                            data-switch="primary" />
                                                        <label for="switchAdmobiOSStatus"></label>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="mb-3">
                                                    <label for="admob_banner_ios"
                                                        class="form-label">{{ __('Banner Ad Unit') }}</label>
                                                    <input class="form-control" type="text" name="admob_banner_ios"
                                                        placeholder="Enter ID" required=""
                                                        value="{{ $setting->admob_banner_ios }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="admob_int_ios"
                                                        class="form-label">{{ __('Interstitial Ad Unit') }}</label>
                                                    <input class="form-control" type="text" name="admob_int_ios"
                                                        placeholder="Enter ID" required=""
                                                        value="{{ $setting->admob_int_ios }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- App Open Ad --}}
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mt-0 d-inline">{{ __('App Open Ad') }}</h4>
                                                    <div class="d-inline ms-2 mb-0">
                                                        <input type="checkbox" id="switchAppOpenAdEnabled"
                                                            name="app_open_ad_enabled"
                                                            {{ $setting->app_open_ad_enabled ? 'checked' : '' }}
                                                            data-switch="primary" />
                                                        <label for="switchAppOpenAdEnabled"></label>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="admob_app_open_android"
                                                            class="form-label">{{ __('Android Ad Unit') }}</label>
                                                        <input class="form-control" type="text" name="admob_app_open_android"
                                                            placeholder="Enter App Open Ad Unit"
                                                            value="{{ $setting->admob_app_open_android }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="admob_app_open_ios"
                                                            class="form-label">{{ __('iOS Ad Unit') }}</label>
                                                        <input class="form-control" type="text" name="admob_app_open_ios"
                                                            placeholder="Enter App Open Ad Unit"
                                                            value="{{ $setting->admob_app_open_ios }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Part Transition Ads --}}
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mt-0 d-inline">{{ __('Part Transition Ads') }}</h4>
                                                    <div class="d-inline ms-2 mb-0">
                                                        <input type="checkbox" id="switchPartTransitionAdEnabled"
                                                            name="part_transition_ad_enabled"
                                                            {{ $setting->part_transition_ad_enabled ? 'checked' : '' }}
                                                            data-switch="primary" />
                                                        <label for="switchPartTransitionAdEnabled"></label>
                                                    </div>
                                                </div>
                                                <p class="text-muted mt-1 mb-2">Show interstitial ads when users navigate between linked video parts (Part 1 → Part 2 → Part 3).</p>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="part_transition_ad_start_at"
                                                            class="form-label">{{ __('Start Showing Ads At Part #') }}</label>
                                                        <input class="form-control" type="number" name="part_transition_ad_start_at"
                                                            min="2" max="20"
                                                            placeholder="3"
                                                            value="{{ $setting->part_transition_ad_start_at ?? 3 }}">
                                                        <small class="text-muted">Skip ads for first N-1 parts to let users get hooked.</small>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="part_transition_ad_interval"
                                                            class="form-label">{{ __('Ad Interval (every N parts)') }}</label>
                                                        <input class="form-control" type="number" name="part_transition_ad_interval"
                                                            min="1" max="10"
                                                            placeholder="2"
                                                            value="{{ $setting->part_transition_ad_interval ?? 2 }}">
                                                        <small class="text-muted">After start, show ad every N parts.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Custom App Open Ad --}}
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mt-0 d-inline">{{ __('Custom App Open Ad') }}</h4>
                                                    <div class="d-inline ms-2 mb-0">
                                                        <input type="checkbox" id="switchCustomAppOpenAdEnabled"
                                                            name="custom_app_open_ad_enabled"
                                                            {{ $setting->custom_app_open_ad_enabled ? 'checked' : '' }}
                                                            data-switch="primary" />
                                                        <label for="switchCustomAppOpenAdEnabled"></label>
                                                    </div>
                                                </div>
                                                <p class="text-muted mt-1 mb-2">Show a video/reel post as a full-screen ad when the app opens, with a skip timer.</p>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="custom_app_open_ad_post_id"
                                                            class="form-label">{{ __('Post ID') }}</label>
                                                        <input class="form-control" type="number" name="custom_app_open_ad_post_id"
                                                            placeholder="Enter Post ID (video/reel)"
                                                            value="{{ $setting->custom_app_open_ad_post_id }}">
                                                        <small class="text-muted">ID of the video/reel post to show as ad.</small>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="custom_app_open_ad_skip_seconds"
                                                            class="form-label">{{ __('Skip Timer (seconds)') }}</label>
                                                        <input class="form-control" type="number" name="custom_app_open_ad_skip_seconds"
                                                            min="1" max="30"
                                                            placeholder="5"
                                                            value="{{ $setting->custom_app_open_ad_skip_seconds ?? 5 }}">
                                                        <small class="text-muted">Users can skip after this many seconds.</small>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="custom_app_open_ad_url"
                                                            class="form-label">{{ __('Click URL (optional)') }}</label>
                                                        <input class="form-control" type="url" name="custom_app_open_ad_url"
                                                            placeholder="https://example.com"
                                                            value="{{ $setting->custom_app_open_ad_url }}">
                                                        <small class="text-muted">External link when user taps the ad.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- VAST Video Ads (Pre-Roll / Mid-Roll / Post-Roll) --}}
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h4 class="mt-0">{{ __('VAST Video Ads') }}</h4>
                                                <p class="text-muted mt-1 mb-3">Configure pre-roll, mid-roll, and post-roll video ads using VAST tags. Ads play seamlessly using the app\'s own video player.</p>
                                                <hr>

                                                {{-- Pre-Roll --}}
                                                <div class="card bg-light mb-3">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h5 class="mt-0 d-inline mb-0">{{ __('Pre-Roll Ads') }}</h5>
                                                            <div class="d-inline ms-2 mb-0">
                                                                <input type="checkbox" id="switchImaPrerollEnabled"
                                                                    name="ima_preroll_enabled"
                                                                    {{ $setting->ima_preroll_enabled ? 'checked' : '' }}
                                                                    data-switch="primary" />
                                                                <label for="switchImaPrerollEnabled"></label>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted small mb-2">Shown before the video starts playing.</p>
                                                        <div class="row">
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Frequency (every N videos)') }}</label>
                                                                <input class="form-control" type="number" name="ima_preroll_frequency"
                                                                    min="0" placeholder="3"
                                                                    value="{{ $setting->ima_preroll_frequency ?? 0 }}">
                                                                <small class="text-muted">0 = disabled</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Min Video Length (sec)') }}</label>
                                                                <input class="form-control" type="number" name="ima_preroll_min_video_length"
                                                                    min="0" placeholder="0"
                                                                    value="{{ $setting->ima_preroll_min_video_length ?? 0 }}">
                                                                <small class="text-muted">0 = all videos</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Android VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_ad_tag_android"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_ad_tag_android }}">
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('iOS VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_ad_tag_ios"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_ad_tag_ios }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Mid-Roll --}}
                                                <div class="card bg-light mb-3">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h5 class="mt-0 d-inline mb-0">{{ __('Mid-Roll Ads') }}</h5>
                                                            <div class="d-inline ms-2 mb-0">
                                                                <input type="checkbox" id="switchImaMidrollEnabled"
                                                                    name="ima_midroll_enabled"
                                                                    {{ $setting->ima_midroll_enabled ? 'checked' : '' }}
                                                                    data-switch="primary" />
                                                                <label for="switchImaMidrollEnabled"></label>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted small mb-2">Shown after the first video loop completes.</p>
                                                        <div class="row">
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Frequency (every N loops)') }}</label>
                                                                <input class="form-control" type="number" name="ima_midroll_frequency"
                                                                    min="0" placeholder="5"
                                                                    value="{{ $setting->ima_midroll_frequency ?? 0 }}">
                                                                <small class="text-muted">0 = disabled</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Min Video Length (sec)') }}</label>
                                                                <input class="form-control" type="number" name="ima_midroll_min_video_length"
                                                                    min="0" placeholder="30"
                                                                    value="{{ $setting->ima_midroll_min_video_length ?? 30 }}">
                                                                <small class="text-muted">0 = all videos</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Android VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_midroll_ad_tag_android"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_midroll_ad_tag_android }}">
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('iOS VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_midroll_ad_tag_ios"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_midroll_ad_tag_ios }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Post-Roll --}}
                                                <div class="card bg-light mb-3">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h5 class="mt-0 d-inline mb-0">{{ __('Post-Roll Ads (End-Roll)') }}</h5>
                                                            <div class="d-inline ms-2 mb-0">
                                                                <input type="checkbox" id="switchImaPostrollEnabled"
                                                                    name="ima_postroll_enabled"
                                                                    {{ $setting->ima_postroll_enabled ? 'checked' : '' }}
                                                                    data-switch="primary" />
                                                                <label for="switchImaPostrollEnabled"></label>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted small mb-2">Shown after the video reaches its Nth loop.</p>
                                                        <div class="row">
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Frequency (every N loops)') }}</label>
                                                                <input class="form-control" type="number" name="ima_postroll_frequency"
                                                                    min="0" placeholder="5"
                                                                    value="{{ $setting->ima_postroll_frequency ?? 0 }}">
                                                                <small class="text-muted">0 = disabled</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Min Video Length (sec)') }}</label>
                                                                <input class="form-control" type="number" name="ima_postroll_min_video_length"
                                                                    min="0" placeholder="15"
                                                                    value="{{ $setting->ima_postroll_min_video_length ?? 15 }}">
                                                                <small class="text-muted">0 = all videos</small>
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('Android VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_postroll_ad_tag_android"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_postroll_ad_tag_android }}">
                                                            </div>
                                                            <div class="col-md-3 mb-2">
                                                                <label class="form-label">{{ __('iOS VAST Tag') }}</label>
                                                                <input class="form-control" type="text" name="ima_postroll_ad_tag_ios"
                                                                    placeholder="VAST tag URL"
                                                                    value="{{ $setting->ima_postroll_ad_tag_ios }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- General VAST Settings --}}
                                                <div class="card bg-light mb-3">
                                                    <div class="card-body">
                                                        <h5 class="mt-0 mb-2">{{ __('Preload Settings') }}</h5>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-2">
                                                                <label class="form-label">{{ __('Preload Seconds Before Video End') }}</label>
                                                                <input class="form-control" type="number" name="ima_preload_seconds_before"
                                                                    min="3" max="30" placeholder="10"
                                                                    value="{{ $setting->ima_preload_seconds_before ?? 10 }}">
                                                                <small class="text-muted">Start downloading the next ad N seconds before the video ends for smoother transitions.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Feed Ad Type Selector --}}
                                                <div class="card border-info mb-0">
                                                    <div class="card-header bg-info bg-opacity-10">
                                                        <h5 class="mt-0 mb-0 text-info">{{ __('In-Feed Ad Type') }}</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="text-muted small mb-3">Choose which ad type to show between posts in the feed. Only one type is active at a time — IMA Video takes priority over Native when both are enabled.</p>

                                                        {{-- VAST / IMA Feed Video Ads --}}
                                                        <div class="card bg-light mb-3">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <h6 class="mt-0 d-inline mb-0">{{ __('Option A: IMA / VAST Video Ads') }}</h6>
                                                                    <div class="d-inline ms-2 mb-0">
                                                                        <input type="checkbox" id="switchVastFeedAdEnabled"
                                                                            name="vast_feed_ad_enabled"
                                                                            {{ $setting->vast_feed_ad_enabled ? 'checked' : '' }}
                                                                            data-switch="primary" />
                                                                        <label for="switchVastFeedAdEnabled"></label>
                                                                    </div>
                                                                </div>
                                                                <p class="text-muted small mb-2">Full video ads using IMA SDK / VAST tag. Requires a VAST-compatible ad network (e.g. AppLovin, IronSource, AdColony).</p>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-2">
                                                                        <label class="form-label">{{ __('Android VAST Tag URL') }}</label>
                                                                        <input class="form-control" type="text" name="vast_feed_ad_tag_android"
                                                                            placeholder="https://ad-server.com/vast?..."
                                                                            value="{{ $setting->vast_feed_ad_tag_android }}">
                                                                    </div>
                                                                    <div class="col-md-6 mb-2">
                                                                        <label class="form-label">{{ __('iOS VAST Tag URL') }}</label>
                                                                        <input class="form-control" type="text" name="vast_feed_ad_tag_ios"
                                                                            placeholder="https://ad-server.com/vast?..."
                                                                            value="{{ $setting->vast_feed_ad_tag_ios }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- AdMob Native Feed Ads --}}
                                                        <div class="card bg-light mb-0">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <h6 class="mt-0 d-inline mb-0">{{ __('Option B: AdMob Native Ads') }}</h6>
                                                                    <div class="d-inline ms-2 mb-0">
                                                                        <input type="checkbox" id="switchNativeAdFeedEnabled"
                                                                            name="native_ad_feed_enabled"
                                                                            {{ $setting->native_ad_feed_enabled ? 'checked' : '' }}
                                                                            data-switch="primary" />
                                                                        <label for="switchNativeAdFeedEnabled"></label>
                                                                    </div>
                                                                </div>
                                                                <p class="text-muted small mb-2">AdMob native ads shown inline in the post feed. Active only when IMA Video Ads (Option A) is disabled. Requires a Native Ad unit from AdMob.</p>
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-2">
                                                                        <label class="form-label">{{ __('AdMob Native — Android Unit ID') }}</label>
                                                                        <input class="form-control" type="text" name="admob_native_android"
                                                                            placeholder="ca-app-pub-XXXXXXXX/XXXXXXXXXX"
                                                                            value="{{ $setting->admob_native_android }}">
                                                                    </div>
                                                                    <div class="col-md-6 mb-2">
                                                                        <label class="form-label">{{ __('AdMob Native — iOS Unit ID') }}</label>
                                                                        <input class="form-control" type="text" name="admob_native_ios"
                                                                            placeholder="ca-app-pub-XXXXXXXX/XXXXXXXXXX"
                                                                            value="{{ $setting->admob_native_ios }}">
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <label class="form-label">{{ __('Min Posts Between Ads') }}</label>
                                                                        <input class="form-control" type="number" min="1" name="native_ad_min_interval"
                                                                            placeholder="4"
                                                                            value="{{ $setting->native_ad_min_interval ?? 4 }}">
                                                                        <small class="text-muted">Minimum posts between native ad slots.</small>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <label class="form-label">{{ __('Max Posts Between Ads') }}</label>
                                                                        <input class="form-control" type="number" min="1" name="native_ad_max_interval"
                                                                            placeholder="8"
                                                                            value="{{ $setting->native_ad_max_interval ?? 8 }}">
                                                                        <small class="text-muted">Maximum posts between native ad slots.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Limits --}}
                <div class="tab-pane fade" id="v-pills-limits" role="tabpanel" aria-labelledby="v-pills-limits-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('Limits') }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="limitSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_upload_daily"
                                                class="form-label">{{ __('Max. Post Upload/Day') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_upload_daily" name="max_upload_daily"
                                                value="{{ $setting->max_upload_daily }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_story_daily"
                                                class="form-label">{{ __('Max. Stories/Day') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_story_daily" name="max_story_daily"
                                                value="{{ $setting->max_story_daily }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_comment_daily"
                                                class="form-label">{{ __('Max. Comments/Day') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_comment_daily" name="max_comment_daily"
                                                value="{{ $setting->max_comment_daily }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_comment_reply_daily"
                                                class="form-label">{{ __('Max. Comment Reply/Day') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_comment_reply_daily" name="max_comment_reply_daily"
                                                value="{{ $setting->max_comment_reply_daily }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_post_pins"
                                                class="form-label">{{ __('Max. Post Pins') }}</label>
                                            <input type="number" min="1" class="form-control" id="max_post_pins"
                                                name="max_post_pins" value="{{ $setting->max_post_pins }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_comment_pins"
                                                class="form-label">{{ __('Max. Comment Pins') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_comment_pins" name="max_comment_pins"
                                                value="{{ $setting->max_comment_pins }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_images_per_post"
                                                class="form-label">{{ __('Max. Images Per Post') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_images_per_post" name="max_images_per_post"
                                                value="{{ $setting->max_images_per_post }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="max_user_links"
                                                class="form-label">{{ __('Max. User Links') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                id="max_user_links" name="max_user_links"
                                                value="{{ $setting->max_user_links }}">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mt-3">{{ __('Creator Monetization') }}</h5>
                                <div class="row mt-2">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="ecpm_rate" class="form-label">{{ __('eCPM Rate ($)') }}</label>
                                            <input type="number" min="0" step="0.01" class="form-control"
                                                id="ecpm_rate" name="ecpm_rate"
                                                value="{{ $setting->ecpm_rate ?? 2.00 }}">
                                            <small class="text-muted">Revenue per 1000 impressions (views).</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="creator_revenue_share" class="form-label">{{ __('Creator Revenue Share (%)') }}</label>
                                            <input type="number" min="0" max="100" class="form-control"
                                                id="creator_revenue_share" name="creator_revenue_share"
                                                value="{{ $setting->creator_revenue_share ?? 55 }}">
                                            <small class="text-muted">% of ad revenue shared with creator.</small>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Livestream --}}
                <div class="tab-pane fade" id="v-pills-livestream" role="tabpanel"
                    aria-labelledby="v-pills-livestream-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('Livestream') }}</h4>
                        </div>
                        <div class="card-body">
                            <span class="fs-6">* Set 0 as a value either in Timeout Minutes or Min. Viewers required to
                                stop Livestream Timeout function.</span><br>
                            <span class="fs-6">* If you turn ON dummy live streams, It will display dummy lives on the
                                app. In order to show dummy lives, There must be dummy live videos added in the list.</span>
                            <form class="mt-2" id="livestreamSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="min_followers_for_live"
                                                class="form-label">{{ __('Min. Followers needed to go Live') }}</label>
                                            <input type="number" step="1" class="form-control"
                                                id="min_followers_for_live" name="min_followers_for_live"
                                                value="{{ $setting->min_followers_for_live }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="live_min_viewers"
                                                class="form-label">{{ __('Min. Viewers Required to continue live') }}</label>
                                            <input type="number" step="1" class="form-control"
                                                id="live_min_viewers" name="live_min_viewers"
                                                value="{{ $setting->live_min_viewers }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="live_timeout"
                                                class="form-label">{{ __('Time Out Minutes (if not get min. viewers)') }}</label>
                                            <input type="number" step="1" class="form-control" id="live_timeout"
                                                name="live_timeout" value="{{ $setting->live_timeout }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="" class="form-label">{{ __('PK Battle') }}</label>
                                            <div class="mb-0">
                                                <input name="live_battle" type="checkbox" id="switchPKBattle"
                                                    {{ $setting->live_battle == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchPKBattle"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Dummy Live Streams') }}</label>
                                            <div class="mb-0">
                                                <input name="live_dummy_show" type="checkbox" id="switchDummyLiveShow"
                                                    {{ $setting->live_dummy_show == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchDummyLiveShow"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Watermark --}}
                                <h5>{{ __('LIVEKIT SETTINGS') }}</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="livekit_host"
                                                class="form-label">{{ __('LiveKit Host URL') }}</label>
                                            <input type="text" class="form-control" id="livekit_host"
                                                name="livekit_host" placeholder="wss://livekit.mybd.in"
                                                value="{{ $userType == 0 ? '---------' : $setting->livekit_host }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="livekit_api_key"
                                                class="form-label">{{ __('LiveKit API Key') }}</label>
                                            <input type="text" class="form-control" id="livekit_api_key"
                                                name="livekit_api_key"
                                                value="{{ $userType == 0 ? '---------' : $setting->livekit_api_key }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="livekit_api_secret"
                                                class="form-label">{{ __('LiveKit API Secret') }}</label>
                                            <input type="text" class="form-control" id="livekit_api_secret"
                                                name="livekit_api_secret"
                                                value="{{ $userType == 0 ? '---------' : $setting->livekit_api_secret }}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- GIF --}}
                <div class="tab-pane fade" id="v-pills-gif" role="tabpanel" aria-labelledby="v-pills-gif-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('GIPHY') }}</h4>
                        </div>
                        <div class="card-body">
                            <span class="fs-6">*If you turn this On, Users will have GIF options in Chat &
                                Comment.</span><br>
                            <span class="fs-6">*Make sure you have added correct GIPHY keys properly.</span>
                            <form class="mt-2" id="gifSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="" class="form-label">{{ __('GIF Supported') }}</label>
                                            <div class="mb-0">
                                                <input name="gif_support" type="checkbox" id="switchGifSupport"
                                                    {{ $setting->gif_support == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchGifSupport"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="giphy_key" class="form-label">{{ __('GIPHY API Key') }}</label>
                                            <input type="text" class="form-control" id="giphy_key" name="giphy_key"
                                                value="{{ $userType == 0 ? '---------' : $setting->giphy_key }}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Content Moderation --}}
                <div class="tab-pane fade" id="v-pills-sightEngine" role="tabpanel"
                    aria-labelledby="v-pills-sightEngine-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="m-0 header-title">{{ __('Content Moderation') }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="contentModerationSettingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for=""
                                                class="form-label">{{ __('Content Moderation') }}</label>
                                            <div class="mb-0">
                                                <input name="is_content_moderation" type="checkbox"
                                                    id="switchContentModeration"
                                                    {{ $setting->is_content_moderation == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchContentModeration"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="moderation_cloudflare_url"
                                                class="form-label">{{ __('Cloudflare Worker URL') }}</label>
                                            <input type="text" class="form-control" id="moderation_cloudflare_url"
                                                name="moderation_cloudflare_url"
                                                placeholder="https://moderation.your-worker.workers.dev"
                                                value="{{ $userType == 0 ? '---------' : $setting->moderation_cloudflare_url }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="moderation_cloudflare_token"
                                                class="form-label">{{ __('Cloudflare Token') }}</label>
                                            <input type="text" class="form-control" id="moderation_cloudflare_token"
                                                name="moderation_cloudflare_token"
                                                value="{{ $userType == 0 ? '---------' : $setting->moderation_cloudflare_token }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="moderation_self_hosted_url"
                                                class="form-label">{{ __('Self-Hosted URL') }}</label>
                                            <input type="text" class="form-control" id="moderation_self_hosted_url"
                                                name="moderation_self_hosted_url"
                                                placeholder="http://localhost:5050"
                                                value="{{ $userType == 0 ? '---------' : $setting->moderation_self_hosted_url }}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Onboarding --}}
                <div class="tab-pane fade" id="v-pills-onBoarding" role="tabpanel"
                    aria-labelledby="v-pills-onBoarding-tab">
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Onboarding') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addOnBoardingScreenModal"
                                class="btn btn-dark ms-auto">{{ __('Add Onboarding') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="onboardingScreenTable"
                                    class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th> {{ __('Sortable') }}</th>
                                            <th> {{ __('Position') }}</th>
                                            <th>{{ __('Image') }}</th>
                                            <th>{{ __('Details') }}</th>
                                            <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- user Levels --}}
                <div class="tab-pane fade" id="v-pills-userLevels" role="tabpanel"
                    aria-labelledby="v-pills-userLevels-tab">
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('User Levels') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addUserLevelModal"
                                class="btn btn-dark ms-auto">{{ __('Add User Level') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive smallSearchBar">
                                <table id="userLevelTable"
                                    class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Level') }}</th>
                                            <th>{{ __('Coins Collection') }}</th>
                                            <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Report Reasons --}}
                <div class="tab-pane fade" id="v-pills-reportReasons" role="tabpanel"
                    aria-labelledby="v-pills-reportReasons-tab">
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Report Reasons') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addReportReasonModal"
                                class="btn btn-dark ms-auto">{{ __('Add Report Reason') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive smallSearchBar">
                                <table id="reportReasonsTable"
                                    class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Title') }}</th>
                                            <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Withdrawal Gateways --}}
                <div class="tab-pane fade" id="v-pills-withdrawalGateways" role="tabpanel"
                    aria-labelledby="v-pills-withdrawalGateways-tab">
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Withdrawal Gateways') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addWithdrawalGatewayModal"
                                class="btn btn-dark ms-auto">{{ __('Add Withdrawal Gateways') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive smallSearchBar">
                                <table id="withdrawalGatewayTable"
                                    class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Title') }}</th>
                                            <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Camera Effects Settings --}}
                <div class="tab-pane fade" id="v-pills-cameraeffects" role="tabpanel" aria-labelledby="v-pills-cameraeffects-tab">
                    {{-- General Camera Effects Settings --}}
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Camera Effects Settings') }}</h4>
                        </div>
                        <div class="card-body">
                            <form class="mt-2" id="cameraEffectSettingsForm" method="POST">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label class="form-label">{{ __('Enable Camera Effects') }}</label>
                                            <div class="mb-0">
                                                <input name="is_camera_effects" type="checkbox" id="switchCameraEffects"
                                                    {{ $setting->is_camera_effects == 1 ? 'checked' : '' }}
                                                    data-switch="primary" />
                                                <label for="switchCameraEffects"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="snap_camera_kit_app_id" class="form-label">{{ __('Snap Camera Kit App ID') }}</label>
                                            <input type="text" class="form-control" id="snap_camera_kit_app_id"
                                                name="snap_camera_kit_app_id"
                                                value="{{ $userType == 0 ? '---------' : $setting->snap_camera_kit_app_id }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="snap_camera_kit_api_token" class="form-label">{{ __('Snap Camera Kit API Token') }}</label>
                                            <input type="text" class="form-control" id="snap_camera_kit_api_token"
                                                name="snap_camera_kit_api_token"
                                                value="{{ $userType == 0 ? '---------' : $setting->snap_camera_kit_api_token }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="mb-0 bg-secondary-lighten border p-2 rounded-3">
                                            <label for="snap_camera_kit_group_id" class="form-label">{{ __('Snap Lens Group ID') }}</label>
                                            <input type="text" class="form-control" id="snap_camera_kit_group_id"
                                                name="snap_camera_kit_group_id"
                                                value="{{ $userType == 0 ? '---------' : $setting->snap_camera_kit_group_id }}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>

                    {{-- Color Filters --}}
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Color Filters') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addColorFilterModal"
                                class="btn btn-dark ms-auto">{{ __('Add Filter') }}</a>
                        </div>
                        <div class="card-body">
                            <table id="colorFiltersTable"
                                class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    {{-- Face Stickers --}}
                    <div class="card">
                        <div class="card-header d-flex align-items-center border-bottom">
                            <h4 class="m-0 header-title">{{ __('Face Stickers') }}</h4>
                            <a data-bs-toggle="modal" data-bs-target="#addFaceStickerModal"
                                class="btn btn-dark ms-auto">{{ __('Add Sticker') }}</a>
                        </div>
                        <div class="card-body">
                            <table id="faceStickersTable"
                                class="table table-centered table-hover w-100 dt-responsive nowrap mt-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Thumbnail') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Sticker') }}</th>
                                        <th>{{ __('Anchor') }}</th>
                                        <th style="width: 200px;" class="text-end">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- Deeplinking --}}
                <div class="tab-pane fade" id="v-pills-deeplinking" role="tabpanel"
                    aria-labelledby="v-pills-deeplinking-tab">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h5 class="m-0">{{ __('Deep Linking') }}</h5>
                        </div>
                        <div class="card-body">
                            <form id="deepLinkingForm" method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="uri_scheme" class="form-label">{{ __('URI Schema') }} <button
                                                    type="button" class="btn btn-secondary p-0 tooltip-icon"
                                                    data-bs-trigger="focus" data-bs-toggle="popover"
                                                    data-bs-title="How to make a Scheme"
                                                    data-bs-content="Use your app name in lowercase with no spaces or special characters (e.g., shortzz, cinereel, myapp2025).">
                                                    ?
                                                </button></label>
                                            <input type="text" class="form-control" id="uri_scheme" name="uri_scheme"
                                                value="{{ $setting->uri_scheme }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="play_store_download_link"
                                                class="form-label">{{ __('Play Store Download Link') }}</label>
                                            <input type="text" class="form-control" id="play_store_download_link"
                                                name="play_store_download_link"
                                                value="{{ $setting->play_store_download_link }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="app_store_download_link"
                                                class="form-label">{{ __('App Store Download Link') }}</label>
                                            <input type="text" class="form-control" id="app_store_download_link"
                                                name="app_store_download_link"
                                                value="{{ $setting->app_store_download_link }}">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                        aria-hidden="true"></span>
                                    {{ __('Save') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header border-bottom">
                                    <h5 class="m-0">{{ __('Android') }}</h5>
                                </div>
                                <div class="card-body">
                                    <form id="androidDeepLinkingForm" method="POST">
                                        <div class="row">
                                            <div class="mb-3">
                                                <label for="package_name"
                                                    class="form-label">{{ __('Package Name') }} <a href="https://docs.retrytech.com/find_bundle_id_android" target="_blank" type="button" class="btn btn-secondary p-0 tooltip-icon">
                                                    ?
                                                </a></label>
                                                <input type="text" class="form-control" id="package_name"
                                                    name="package_name" value="{{ $packageName }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('SHA 256 Keys') }} <a href="https://docs.retrytech.com/how_to_get_sha1_key" target="_blank" type="button" class="btn btn-secondary p-0 tooltip-icon">
                                                    ?
                                                </a></label>
                                                <div id="shaContainer">
                                                    @if (!empty($sha256))
                                                        @foreach (explode(',', $sha256) as $sha)
                                                            <div class="input-group mb-2 sha-field">
                                                                <input type="text" class="form-control sha-input"
                                                                    name="sha_256[]" value="{{ trim($sha) }}">
                                                                <button type="button"
                                                                    class="btn btn-danger remove-sha">-</button>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="input-group mb-2 sha-field">
                                                            <input type="text" class="form-control sha-input"
                                                                name="sha_256[]" placeholder="Enter SHA 256">
                                                            <button type="button"
                                                                class="btn btn-danger remove-sha">-</button>
                                                        </div>
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success mt-1"
                                                    id="addSha">+ Add SHA</button>
                                            </div>
                                        </div>
                                        <hr>
                                        <button type="submit" class="btn btn-primary">
                                            <span class="spinner-border spinner-border-sm me-1 spinner hide"
                                                role="status" aria-hidden="true"></span>
                                            {{ __('Save') }}
                                        </button>
                                        <button type="button" id="checkValidationOfAndroid" class="btn btn-success">
                                            {{ __('Check Validation') }}
                                        </button>

                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header border-bottom">
                                    <h5 class="m-0">{{ __('iOS') }}</h5>
                                </div>
                                <div class="card-body">
                                    <form id="iOSDeepLinkingForm" method="POST">
                                        <div class="row">
                                            <div class="mb-3">
                                                <label for="package_name_ios"
                                                    class="form-label">{{ __('Bundle Id / Package Name') }} <a href="https://docs.retrytech.com/find_bundle_id_ios" target="_blank" type="button" class="btn btn-secondary p-0 tooltip-icon">
                                                    ?
                                                </a></label>
                                                <input type="text" class="form-control" id="package_name_ios"
                                                    name="package_name" value="{{ $iosPackageName }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="team_id" class="form-label">{{ __('Team Id') }} <a href="https://docs.retrytech.com/find_team_id" target="_blank" type="button" class="btn btn-secondary p-0 tooltip-icon">
                                                    ?
                                                </a></label>
                                                <input type="text" class="form-control" id="team_id" name="team_id"
                                                    value="{{ $iosTeamId }}" required>
                                            </div>
                                        </div>
                                        <hr>
                                        <button type="submit" class="btn btn-primary">
                                            <span class="spinner-border spinner-border-sm me-1 spinner hide"
                                                role="status" aria-hidden="true"></span>
                                            {{ __('Save') }}
                                        </button>

                                        <button type="button" id="checkValidationOfApple" class="btn btn-success">
                                            {{ __('Check Validation') }}
                                        </button>

                                        <hr>

                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                {{-- Privacy Policy --}}
                <div class="tab-pane fade" id="v-pills-privacy-policy" role="tabpanel"
                    aria-labelledby="v-pills-privacy-policy-tab">
                    <div class="card">
                        <div class="card-header border-bottom d-flex align-items-center">
                            <h4 class="m-0 header-title">{{ __('Privacy Policy') }}</h4>
                            <a href="{{ url('privacy_policy') }}" target="_blank"
                                class="btn btn-primary rounded-5 ms-2">{{ __('View') }}</a>
                        </div>
                        <div class="card-body">
                            <form id="privacyPolicyForm" method="POST">
                                <div id="privacyEditor">{!! $setting->privacy_policy !!}</div>
                                <br>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Terms Of Use --}}
                <div class="tab-pane fade" id="v-pills-terms" role="tabpanel" aria-labelledby="v-pills-terms-tab">
                    <div class="card">
                        <div class="card-header border-bottom d-flex align-items-center">
                            <h4 class="m-0 header-title">{{ __('Terms Of Uses') }}</h4>
                            <a href="{{ url('terms_of_uses') }}" target="_blank"
                                class="btn btn-primary rounded-5 ms-2">{{ __('View') }}</a>
                        </div>
                        <div class="card-body">
                            <form id="termsOfUsesForm" method="POST">
                                <div id="termsOfUsesEditor">{!! $setting->terms_of_uses !!}</div>
                                <br>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add DeepAR Filter Modal --}}
    {{-- Add Color Filter Modal --}}
    <div id="addColorFilterModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('Add Color Filter') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addColorFilterForm" method="POST">
                    <div class="modal-body">
                        <img id="imgColorFilterPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" height="100" width="100">
                        <div class="my-2">
                            <label class="form-label">{{ __('Thumbnail Image') }}</label>
                            <input id="inputAddColorFilterImage" class="form-control" type="file"
                                accept="image/*" name="image" required>
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Brightness') }}</label>
                                <input class="form-control" type="number" step="0.1" name="brightness" value="0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Contrast') }}</label>
                                <input class="form-control" type="number" step="0.1" name="contrast" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Saturation') }}</label>
                                <input class="form-control" type="number" step="0.1" name="saturation" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Warmth') }}</label>
                                <input class="form-control" type="number" step="0.1" name="warmth" value="0">
                            </div>
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Color Matrix (JSON array of 20 doubles, optional)') }}</label>
                            <textarea class="form-control" name="color_matrix" rows="2" placeholder="[1,0,0,0,0, 0,1,0,0,0, 0,0,1,0,0, 0,0,0,1,0]"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Color Filter Modal --}}
    <div id="editColorFilterModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('Edit Color Filter') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editColorFilterForm" method="POST">
                    <input type="hidden" name="id" id="editColorFilterId">
                    <div class="modal-body">
                        <img id="imgEditColorFilterPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" height="100" width="100">
                        <div class="my-2">
                            <label class="form-label">{{ __('Thumbnail Image') }}</label>
                            <input id="inputEditColorFilterImage" class="form-control" type="file" accept="image/*" name="image">
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" id="editColorFilterTitle" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Brightness') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editColorFilterBrightness" name="brightness" value="0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Contrast') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editColorFilterContrast" name="contrast" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Saturation') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editColorFilterSaturation" name="saturation" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Warmth') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editColorFilterWarmth" name="warmth" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add Face Sticker Modal --}}
    <div id="addFaceStickerModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('Add Face Sticker') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addFaceStickerForm" method="POST">
                    <div class="modal-body">
                        <img id="imgFaceStickerPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" height="100" width="100">
                        <div class="my-2">
                            <label class="form-label">{{ __('Thumbnail') }}</label>
                            <input id="inputAddFaceStickerThumbnail" class="form-control" type="file" accept="image/*" name="thumbnail" required>
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Sticker Image (PNG with transparency)') }}</label>
                            <input class="form-control" type="file" accept="image/png" name="sticker_image" required>
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Anchor Landmark') }}</label>
                                <select class="form-control" name="anchor_landmark">
                                    <option value="nose">Nose</option>
                                    <option value="forehead">Forehead</option>
                                    <option value="left_eye">Left Eye</option>
                                    <option value="right_eye">Right Eye</option>
                                    <option value="mouth">Mouth</option>
                                    <option value="face_center">Face Center</option>
                                </select>
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Scale') }}</label>
                                <input class="form-control" type="number" step="0.1" name="scale" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Offset X') }}</label>
                                <input class="form-control" type="number" step="0.1" name="offset_x" value="0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Offset Y') }}</label>
                                <input class="form-control" type="number" step="0.1" name="offset_y" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Face Sticker Modal --}}
    <div id="editFaceStickerModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('Edit Face Sticker') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editFaceStickerForm" method="POST">
                    <input type="hidden" name="id" id="editFaceStickerId">
                    <div class="modal-body">
                        <img id="imgEditFaceStickerPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" height="100" width="100">
                        <div class="my-2">
                            <label class="form-label">{{ __('Thumbnail') }}</label>
                            <input id="inputEditFaceStickerThumbnail" class="form-control" type="file" accept="image/*" name="thumbnail">
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Sticker Image') }}</label>
                            <input class="form-control" type="file" accept="image/png" name="sticker_image">
                        </div>
                        <div class="my-2">
                            <label class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" id="editFaceStickerTitle" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Anchor Landmark') }}</label>
                                <select class="form-control" id="editFaceStickerAnchor" name="anchor_landmark">
                                    <option value="nose">Nose</option>
                                    <option value="forehead">Forehead</option>
                                    <option value="left_eye">Left Eye</option>
                                    <option value="right_eye">Right Eye</option>
                                    <option value="mouth">Mouth</option>
                                    <option value="face_center">Face Center</option>
                                </select>
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Scale') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editFaceStickerScale" name="scale" value="1.0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Offset X') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editFaceStickerOffsetX" name="offset_x" value="0">
                            </div>
                            <div class="col-md-6 my-2">
                                <label class="form-label">{{ __('Offset Y') }}</label>
                                <input class="form-control" type="number" step="0.1" id="editFaceStickerOffsetY" name="offset_y" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status" aria-hidden="true"></span>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit User Level --}}
    <div id="editUserLevelModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit User Level') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editUserLevelForm" method="POST">
                    <input type="hidden" name="id" id="editUserLevelId">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_level" class="form-label">{{ __('Level') }}</label>
                            <input class="form-control" type="text" id="edit_level" name="level" required
                                disabled>
                        </div>
                        <div class="mb-2">
                            <label for="edit_coins_collection" class="form-label">{{ __('Coins Collection') }}</label>
                            <input class="form-control" type="text" id="edit_coins_collection"
                                name="coins_collection" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Withdrawal Gateway --}}
    <div id="editWithdrawalGatewayModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Withdrawal Gateway') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editWithdrawalGatewayForm" method="POST">
                    <input type="hidden" name="id" id="editWithdrawalGatewayId">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input id="editWithdrawalGatewayTitle" class="form-control" type="text"
                                id="title" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Withdrawal Gateway --}}
    <div id="editWithdrawalGatewayModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Withdrawal Gateway') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editWithdrawalGatewayForm" method="POST">
                    <input type="hidden" name="id" id="editWithdrawalGatewayId">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input id="editWithdrawalGatewayTitle" class="form-control" type="text"
                                id="title" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Report Reason --}}
    <div id="editReportReasonModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Edit Report Reason') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editReportReasonForm" method="POST">
                    <input type="hidden" name="id" id="editReportReasonId">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input id="editReportReasonTitle" class="form-control" type="text" id="title"
                                name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add User Level --}}
    <div id="addUserLevelModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Add User Level') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addUserLevelForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="level" class="form-label">{{ __('Level') }}</label>
                            <input class="form-control" type="text" id="level" name="level" required>
                        </div>
                        <div class="mb-2">
                            <label for="coins_collection" class="form-label">{{ __('Coins Collection') }}</label>
                            <input class="form-control" type="text" id="coins_collection" name="coins_collection"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add Withdrawal Gateways --}}
    <div id="addWithdrawalGatewayModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Withdrawal Gateway') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addWithdrawalGatewayForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add Report Reason --}}
    <div id="addReportReasonModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Report Reason') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addReportReasonForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Edit Onboarding Screen --}}
    <div id="editOnBoardingScreenModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Onboarding Screen') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="editOnBoardingScreenForm" method="POST">
                    <input type="hidden" name="id" id="editOnboardingScreenId">
                    <div class="modal-body">
                        <img id="imgEditOnBoradingPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" width="200">
                        <div class="my-2">
                            <label for="image" class="form-label">{{ __('Image') }}</label>
                            <input id="inputEditOnboardingImage" class="form-control" type="file"
                                accept="image/*" id="image" name="image">
                        </div>
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input id="editOnboardingTitle" class="form-control" type="text" id="title"
                                name="title" required>
                        </div>
                        <div class="mb-2">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea id="editOnboardingDesc" class="form-control" id="description" name="description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add Onboarding Screen --}}
    <div id="addOnBoardingScreenModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="standard-modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="standard-modalLabel">{{ __('Add Onboarding Screen') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <form id="addOnBoardingScreenForm" method="POST">
                    <div class="modal-body">
                        <img id="imgAddOnBoradingPreview" src="{{ url('assets/img/placeholder.png') }}"
                            alt="" class="rounded" width="200">
                        <div class="my-2">
                            <label for="image" class="form-label">{{ __('Image') }}</label>
                            <input id="inputAddOnboardingImage" class="form-control" type="file" accept="image/*"
                                id="image" name="image" required>
                        </div>
                        <div class="mb-2">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input class="form-control" type="text" id="title" name="title" required>
                        </div>
                        <div class="mb-2">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 spinner hide" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
