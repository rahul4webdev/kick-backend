<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $headline }} — {{ $appName }}</title>

    <!-- Open Graph -->
    <meta property="og:title" content="{{ Str::limit($headline, 100) }}" />
    <meta property="og:description" content="{{ Str::limit($bio ?? '', 300) }}" />
    <meta property="og:image" content="{{ $profilePhoto }}" />
    <meta property="og:type" content="profile" />
    <meta property="og:url" content="{{ url('/u/' . $portfolio->slug) }}" />
    <meta property="og:site_name" content="{{ $appName }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{ Str::limit($headline, 70) }}" />
    <meta name="twitter:description" content="{{ Str::limit($bio ?? '', 200) }}" />
    <meta name="twitter:image" content="{{ $profilePhoto }}" />

    <!-- App Deep Link -->
    <meta property="al:ios:app_name" content="{{ $appName }}" />
    <meta property="al:android:app_name" content="{{ $appName }}" />

    <link rel="icon" type="image/x-icon" href="{{ $profilePhoto }}">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* Theme colors */
        @php
            $themes = [
                'default' => ['bg' => '#ffffff', 'text' => '#1a1a1a', 'accent' => '#6366f1', 'card' => '#f8f9fa', 'muted' => '#6b7280'],
                'dark' => ['bg' => '#0f0f0f', 'text' => '#ffffff', 'accent' => '#818cf8', 'card' => '#1a1a2e', 'muted' => '#9ca3af'],
                'minimal' => ['bg' => '#fafafa', 'text' => '#333333', 'accent' => '#333333', 'card' => '#ffffff', 'muted' => '#888888'],
                'vibrant' => ['bg' => '#1a0a2e', 'text' => '#ffffff', 'accent' => '#f472b6', 'card' => '#2a1a3e', 'muted' => '#c4b5fd'],
                'gradient' => ['bg' => '#0f172a', 'text' => '#ffffff', 'accent' => '#38bdf8', 'card' => '#1e293b', 'muted' => '#94a3b8'],
            ];
            $colors = $themes[$portfolio->theme] ?? $themes['default'];
            if ($portfolio->custom_colors) {
                $colors = array_merge($colors, $portfolio->custom_colors);
            }
        @endphp

        body {
            background: {{ $colors['bg'] }};
            color: {{ $colors['text'] }};
        }

        .container {
            max-width: 640px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        /* Header */
        .profile-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid {{ $colors['accent'] }};
            margin-bottom: 16px;
        }
        .headline {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .username {
            font-size: 14px;
            color: {{ $colors['muted'] }};
            margin-bottom: 12px;
        }
        .bio {
            font-size: 15px;
            line-height: 1.5;
            color: {{ $colors['muted'] }};
            max-width: 480px;
            margin: 0 auto;
        }

        /* Stats */
        .stats {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin: 20px 0;
        }
        .stat { text-align: center; }
        .stat-value { font-size: 20px; font-weight: 700; }
        .stat-label { font-size: 12px; color: {{ $colors['muted'] }}; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Links */
        .links-section { margin-bottom: 32px; }
        .link-item {
            display: block;
            padding: 14px 20px;
            margin-bottom: 10px;
            background: {{ $colors['card'] }};
            border-radius: 12px;
            text-decoration: none;
            color: {{ $colors['text'] }};
            font-weight: 500;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            border: 1px solid {{ $colors['accent'] }}22;
        }
        .link-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px {{ $colors['accent'] }}33;
        }

        /* Section */
        .section { margin-bottom: 32px; }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid {{ $colors['accent'] }}33;
        }
        .section-content {
            font-size: 15px;
            line-height: 1.6;
            color: {{ $colors['muted'] }};
        }

        /* Featured Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            margin-bottom: 32px;
        }
        .post-thumb {
            aspect-ratio: 1;
            width: 100%;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .post-thumb:hover { opacity: 0.85; }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 32px;
        }
        .product-card {
            background: {{ $colors['card'] }};
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: {{ $colors['text'] }};
        }
        .product-card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }
        .product-info { padding: 10px 12px; }
        .product-name { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .product-price { font-size: 13px; color: {{ $colors['accent'] }}; font-weight: 600; }

        /* Subscribe CTA */
        .subscribe-cta {
            display: block;
            width: 100%;
            padding: 16px;
            background: {{ $colors['accent'] }};
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-bottom: 32px;
            transition: opacity 0.15s;
        }
        .subscribe-cta:hover { opacity: 0.9; }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid {{ $colors['accent'] }}22;
        }
        .footer a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: {{ $colors['muted'] }};
            text-decoration: none;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .posts-grid { grid-template-columns: repeat(2, 1fr); }
            .stats { gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <img src="{{ $profilePhoto }}" alt="{{ $headline }}" class="avatar"
                 onerror="this.style.display='none'">
            <h1 class="headline">{{ $headline }}</h1>
            <p class="username">{{ '@' . $user->username }}</p>
            @if($bio)
            <p class="bio">{{ $bio }}</p>
            @endif
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ number_format($user->follower_count ?? 0) }}</div>
                <div class="stat-label">Followers</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ number_format($user->following_count ?? 0) }}</div>
                <div class="stat-label">Following</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ number_format($portfolio->view_count ?? 0) }}</div>
                <div class="stat-label">Views</div>
            </div>
        </div>

        <!-- Subscribe CTA -->
        @if($portfolio->show_subscription_cta)
        <a href="{{ $setting->uri_scheme }}://u/{{ $user->username }}" class="subscribe-cta">
            Follow on {{ $appName }}
        </a>
        @endif

        <!-- Custom Sections -->
        @foreach($sections as $section)
        <div class="section">
            @if($section->title)
            <h2 class="section-title">{{ $section->title }}</h2>
            @endif
            @if($section->content)
            <div class="section-content">{!! nl2br(e($section->content)) !!}</div>
            @endif
        </div>
        @endforeach

        <!-- Links -->
        @if(count($links) > 0)
        <div class="links-section">
            <h2 class="section-title">Links</h2>
            @foreach($links as $link)
            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="link-item">
                {{ $link->title ?? $link->url }}
            </a>
            @endforeach
        </div>
        @endif

        <!-- Featured Posts -->
        @if(count($featuredPosts) > 0)
        <div class="section">
            <h2 class="section-title">Featured</h2>
            <div class="posts-grid">
                @foreach($featuredPosts as $post)
                <a href="{{ url('/p/' . $post['id']) }}">
                    <img src="{{ $post['thumbnail'] }}" alt="" class="post-thumb"
                         onerror="this.style.display='none'">
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Products -->
        @if(count($products) > 0)
        <div class="section">
            <h2 class="section-title">Shop</h2>
            <div class="products-grid">
                @foreach($products as $product)
                <div class="product-card">
                    @if($product['image'])
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                         onerror="this.style.display='none'">
                    @endif
                    <div class="product-info">
                        <div class="product-name">{{ Str::limit($product['name'], 40) }}</div>
                        <div class="product-price">{{ number_format($product['price_coins']) }} coins</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <a href="{{ $setting->play_store_download_link ?? '#' }}">
                Get {{ $appName }}
            </a>
        </div>
    </div>

    <script>
        // Try to open in app first
        document.addEventListener("DOMContentLoaded", function() {
            var appUrl = "{{ $setting->uri_scheme }}://u/{{ $user->username }}";
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = appUrl;
            document.body.appendChild(iframe);
            setTimeout(function() { document.body.removeChild(iframe); }, 2000);
        });
    </script>
</body>
</html>
