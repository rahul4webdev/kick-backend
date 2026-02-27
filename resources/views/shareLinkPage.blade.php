<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>

    <!-- Open Graph -->
    <meta property="og:title" content="{{ Str::limit($title, 100) }}" />
    <meta property="og:image" content="{{ $thumbUrl }}" />
    <meta property="og:type" content="{{ $ogType ?? 'article' }}" />
    @if(!empty($ogDescription))
    <meta property="og:description" content="{{ Str::limit($ogDescription, 300) }}" />
    @endif
    @if(!empty($canonicalUrl))
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    @endif
    <meta property="og:site_name" content="{{ $appName ?? $setting->app_name ?? 'App' }}" />
    @if(!empty($videoUrl))
    <meta property="og:video" content="{{ $videoUrl }}" />
    <meta property="og:video:type" content="video/mp4" />
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ Str::limit($title, 70) }}" />
    <meta name="twitter:image" content="{{ $thumbUrl }}" />
    @if(!empty($ogDescription))
    <meta name="twitter:description" content="{{ Str::limit($ogDescription, 200) }}" />
    @endif

    <!-- App Deep Link Meta (Facebook App Links) -->
    <meta property="al:ios:app_name" content="{{ $setting->app_name ?? 'Kick' }}" />
    <meta property="al:ios:app_store_id" content="{{ $setting->ios_app_id ?? '' }}" />
    <meta property="al:ios:url" content="{{ $setting->uri_scheme ?? 'kick' }}://s/{{ $encryptedId }}" />
    <meta property="al:android:app_name" content="{{ $setting->app_name ?? 'Kick' }}" />
    <meta property="al:android:package" content="com.kick.entertainment" />
    <meta property="al:android:url" content="{{ $setting->uri_scheme ?? 'kick' }}://s/{{ $encryptedId }}" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.png') }}">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 420px;
            width: 100%;
            padding: 24px;
            text-align: center;
        }
        .content-preview {
            width: 100%;
            max-width: 300px;
            margin: 0 auto 24px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 9/16;
            background: #111;
        }
        .content-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .content-preview .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        .play-icon::after {
            content: '';
            display: block;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 12px 0 12px 22px;
            border-color: transparent transparent transparent #fff;
            margin-left: 4px;
        }
        .app-name {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .description {
            font-size: 15px;
            color: #aaa;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        .open-app-btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 12px;
            transition: opacity 0.2s;
        }
        .open-app-btn:hover { opacity: 0.9; }
        .store-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #222;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border: 1px solid #333;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 8px;
            transition: background 0.2s;
        }
        .store-btn:hover { background: #333; }
        .store-buttons { margin-top: 16px; }
        .store-buttons a { display: inline-block; margin: 4px; }
        .store-buttons img { height: 44px; border-radius: 8px; }
        .status-text {
            font-size: 13px;
            color: #666;
            margin-top: 16px;
        }
        @media (max-width: 480px) {
            .content-preview { max-width: 240px; }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Content preview -->
        <div class="content-preview">
            <img src="{{ $thumbUrl }}" alt="{{ $title }}">
            @if(!empty($videoUrl))
            <div class="play-icon"></div>
            @endif
        </div>

        <div class="app-name">{{ $setting->app_name ?? 'Kick' }}</div>
        <p class="description">
            @if(!empty($ogDescription))
                {{ Str::limit($ogDescription, 120) }}
            @else
                Open in the {{ $setting->app_name ?? 'Kick' }} app to view this content
            @endif
        </p>

        <!-- Primary CTA: Open in App -->
        <a href="#" id="openAppBtn" class="open-app-btn">Open in App</a>

        <!-- Store fallback buttons -->
        <div class="store-buttons">
            @if(!empty($setting->play_store_download_link))
            <a href="{{ $setting->play_store_download_link }}" id="playStoreLink">
                <img src="{{ asset('assets/img/playstore.png') }}" alt="Get it on Google Play">
            </a>
            @endif
            @if(!empty($setting->app_store_download_link))
            <a href="{{ $setting->app_store_download_link }}" id="appStoreLink">
                <img src="{{ asset('assets/img/appstore.png') }}" alt="Download on the App Store">
            </a>
            @endif
        </div>

        <p class="status-text" id="statusText"></p>
    </div>

    <script>
    (function() {
        var uriScheme = "{{ $setting->uri_scheme ?? 'kick' }}";
        var deepLinkPath = "s/{{ $encryptedId }}";
        var appDeepLink = uriScheme + "://" + deepLinkPath;
        var playStoreUrl = "{{ $setting->play_store_download_link ?? '' }}";
        var appStoreUrl = "{{ $setting->app_store_download_link ?? '' }}";
        var currentUrl = window.location.href;

        var ua = navigator.userAgent || '';
        var isAndroid = /android/i.test(ua);
        var isIOS = /iphone|ipad|ipod/i.test(ua);
        var isMobile = isAndroid || isIOS;

        var openAppBtn = document.getElementById('openAppBtn');
        var statusText = document.getElementById('statusText');
        var playStoreLink = document.getElementById('playStoreLink');
        var appStoreLink = document.getElementById('appStoreLink');

        // Hide irrelevant store button based on platform
        if (isAndroid && appStoreLink) appStoreLink.style.display = 'none';
        if (isIOS && playStoreLink) playStoreLink.style.display = 'none';

        function tryOpenApp() {
            if (isAndroid) {
                // Android: Use intent:// URL with fallback to Play Store
                var intentUrl = "intent://" + deepLinkPath +
                    "#Intent;" +
                    "scheme=" + uriScheme + ";" +
                    "package=com.kick.entertainment;" +
                    "S.browser_fallback_url=" + encodeURIComponent(playStoreUrl || currentUrl) + ";" +
                    "end";
                window.location.href = intentUrl;
            } else if (isIOS) {
                // iOS: Try custom scheme, fall back to App Store after timeout
                var appOpened = false;
                var startTime = Date.now();

                // Listen for page becoming hidden (app opened successfully)
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) appOpened = true;
                });

                window.location.href = appDeepLink;

                // If app didn't open within 1.5s, redirect to App Store
                setTimeout(function() {
                    if (!appOpened && Date.now() - startTime < 3000) {
                        if (appStoreUrl) {
                            statusText.textContent = 'App not found. Redirecting to App Store...';
                            window.location.href = appStoreUrl;
                        }
                    }
                }, 1500);
            } else {
                // Desktop: Just show the page, no redirect
                statusText.textContent = 'Scan the QR code or visit from your phone to open in app';
            }
        }

        // Open App button handler
        openAppBtn.addEventListener('click', function(e) {
            e.preventDefault();
            tryOpenApp();
        });

        // Auto-attempt on mobile (like Instagram)
        if (isMobile) {
            // Small delay to ensure page renders first (for OG crawlers)
            setTimeout(tryOpenApp, 300);
        }
    })();
    </script>
</body>

</html>
