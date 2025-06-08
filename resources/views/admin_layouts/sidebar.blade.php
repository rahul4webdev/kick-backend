<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ url('/dashboard') }}"> <img alt="image" src="{{ asset('assets/dist/img/logo-impilo.png') }}"
                    class="header-logo"> <span class="logo-name"></span>
            </a>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown {{ active_class(['dashboard']) }}">
                <a href="{{ url('/dashboard') }}" class="nav-link"><i
                        data-feather="monitor"></i><span>Dashboard</span></a>
            </li>

            <li class="menu-header">Pages</li>

            <li class="dropdown @if (request()->is('hash_tag/*')) {{ 'active' }} @endif ">
                <a href="{{ url('/hash_tag/list') }}" class="nav-link"><i data-feather="tag"></i><span>Hash
                        Tag</span></a>
            </li>

            <li class="dropdown @if (request()->is('user/*')) {{ 'active' }} @endif ">
                <a href="{{ url('/user/list') }}" class="nav-link"><i data-feather="users"></i><span>Users</span></a>
            </li>
            <li class="dropdown {{ active_class(['post/list']) }}">
                <a href="{{ url('/post/list') }}" class="nav-link"><i data-feather="video"></i><span>Post</span></a>
            </li>
            <li class="dropdown {{ active_class(['sound_category/list']) }}">
                <a href="{{ url('/sound_category/list') }}" class="nav-link"><i
                        data-feather="file-text"></i><span>Sound Category</span></a>
            </li>
            <li class="dropdown {{ active_class(['sound/list']) }}">
                <a href="{{ url('/sound/list') }}" class="nav-link"><i data-feather="music"></i><span>Sound</span></a>
            </li>
            <li class="dropdown @if (request()->is('profile_category/list')) {{ 'active' }} @endif">
                <a href="{{ url('/profile_category/list') }}" class="nav-link"><i
                        data-feather="users"></i><span>Profile Categories</span></a>
            </li>
            <li class="dropdown {{ active_class(['verification_request/list']) }}">
                <a href="{{ url('/verification_request/list') }}" class="nav-link"><i
                        data-feather="check-square"></i><span>Verification Request</span></a>
            </li>

            <li class="dropdown @if (request()->is('report/list')) {{ 'active' }} @endif">
                <a href="{{ url('/report/list') }}" class="nav-link"><i
                        data-feather="file-text"></i><span>Report</span></a>
            </li>

            <li class="dropdown {{ active_class(['coin_plan/list']) }}">
                <a href="{{ url('/coin_plan/list') }}" class="nav-link"><i data-feather="list"></i><span>Coin
                        Plan</span></a>
            </li>
            <li class="dropdown {{ active_class(['gifts/list']) }}">
                <a href="{{ url('/gifts/list') }}" class="nav-link"><i data-feather="gift"></i><span>Gifts</span></a>
            </li>

            <li class="dropdown {{ active_class(['redeem_request/list']) }}">
                <a href="{{ url('/redeem_request/list') }}" class="nav-link"><i data-feather="share"></i><span>Redeem
                        Request</span></a>
            </li>
            <li class="dropdown {{ active_class(['settings/list']) }}">
                <a href="{{ url('/settings/list') }}" class="nav-link"><i
                        data-feather="settings"></i><span>Settings</span></a>
            </li>

            <li class="menu-header">{{ __('Pages') }}</li>

            <li class="dropdown {{ active_class(['viewPrivacy']) }}">
                <a href="{{ url('/viewPrivacy') }}" class="nav-link"><i
                        data-feather="info"></i><span>Privacy Policy</span></a>
            </li>

            <li class="dropdown {{ active_class(['viewTerms']) }}">
                <a href="{{ url('/viewTerms') }}" class="nav-link"><i
                        data-feather="info"></i><span>Terms Of Use</span></a>
            </li>

        </ul>
    </aside>
</div>
