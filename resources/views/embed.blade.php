<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ Str::limit($title, 60) }}</title>
    <meta property="og:title" content="{{ Str::limit($title, 60) }}">
    <meta property="og:image" content="{{ $thumbUrl }}">
    <meta property="og:type" content="video.other">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ Str::limit($title, 60) }}">
    <meta name="twitter:image" content="{{ $thumbUrl }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
            overflow: hidden;
        }
        .embed-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            background: #111;
            border-radius: 12px;
            overflow: hidden;
        }
        .media-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 9/16;
            background: #000;
        }
        .media-wrapper video,
        .media-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .username {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }
        .description {
            font-size: 13px;
            color: rgba(255,255,255,0.85);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .branding {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            background: #111;
            font-size: 11px;
            color: #888;
            gap: 4px;
        }
        .branding a {
            color: #aaa;
            text-decoration: none;
            font-weight: 600;
        }
        .branding a:hover { text-decoration: underline; }
        .play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(4px);
        }
        .play-btn svg { fill: #fff; width: 28px; height: 28px; margin-left: 3px; }
        .play-btn.hidden { display: none; }
    </style>
</head>
<body>
    <div class="embed-container">
        <div class="media-wrapper">
            @if(in_array($post->post_type, [\App\Models\Constants::postTypeVideo, \App\Models\Constants::postTypeReel]))
                <video
                    id="embed-video"
                    src="{{ $videoUrl }}"
                    poster="{{ $thumbUrl }}"
                    playsinline
                    preload="metadata"
                    loop
                ></video>
                <div class="play-btn" id="play-btn" onclick="togglePlay()">
                    <svg viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
                </div>
            @elseif($post->post_type == \App\Models\Constants::postTypeImage)
                <img src="{{ $thumbUrl }}" alt="{{ Str::limit($title, 100) }}">
            @else
                <div style="display:flex;align-items:center;justify-content:center;height:100%;padding:20px;text-align:center;">
                    <p style="font-size:18px;color:#ccc;">{{ $title }}</p>
                </div>
            @endif

            <div class="overlay">
                <div class="user-info">
                    <img class="avatar" src="{{ $profilePhoto }}" alt="{{ $username }}" onerror="this.style.display='none'">
                    <span class="username">{{ '@' . $username }}</span>
                </div>
                @if($post->description)
                    <p class="description">{{ $post->description }}</p>
                @endif
            </div>
        </div>
        <div class="branding">
            <span>Powered by</span>
            <a href="{{ url('/') }}" target="_blank" rel="noopener">{{ $appName }}</a>
        </div>
    </div>

    <script>
        function togglePlay() {
            var video = document.getElementById('embed-video');
            var btn = document.getElementById('play-btn');
            if (!video) return;
            if (video.paused) {
                video.play();
                btn.classList.add('hidden');
            } else {
                video.pause();
                btn.classList.remove('hidden');
            }
        }
        var video = document.getElementById('embed-video');
        if (video) {
            video.addEventListener('click', togglePlay);
            video.addEventListener('pause', function() {
                document.getElementById('play-btn').classList.remove('hidden');
            });
        }
    </script>
</body>
</html>
