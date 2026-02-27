@extends('include.app')
@section('script')
<script>
$(document).ready(function () {
    let currentPeriod = '30d';
    const periodBtns = document.querySelectorAll('.period-btn');

    periodBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            periodBtns.forEach(b => b.classList.remove('btn-primary'));
            periodBtns.forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            currentPeriod = this.dataset.period;
            loadAllData();
        });
    });

    function loadAllData() {
        loadOverview();
        loadEngagement();
        loadDevices();
        loadLocations();
        loadTopPosts();
        loadTopUsers();
    }

    function loadOverview() {
        $.get('/fetchAnalyticsOverview', { period: currentPeriod }, function (res) {
            if (res.status && res.data) {
                const d = res.data;
                $('#metric-dau').text(formatNumber(d.dau || 0));
                $('#metric-views').text(formatNumber(d.totalViews || 0));
                $('#metric-likes').text(formatNumber(d.totalLikes || 0));
                $('#metric-comments').text(formatNumber(d.totalComments || 0));
                $('#metric-shares').text(formatNumber(d.totalShares || 0));
                $('#metric-gifts').text(formatNumber(d.totalGifts || 0));
            }
        });
    }

    function loadEngagement() {
        $.get('/fetchAnalyticsEngagement', { period: currentPeriod }, function (res) {
            if (res.status && res.data && Array.isArray(res.data)) {
                const dates = res.data.map(d => d.date || d._id);
                const views = res.data.map(d => d.views || 0);
                const likes = res.data.map(d => d.likes || 0);
                const comments = res.data.map(d => d.comments || 0);

                if (window.engagementChart) window.engagementChart.destroy();
                window.engagementChart = new ApexCharts(document.querySelector('#engagement-chart'), {
                    chart: { type: 'area', height: 320, toolbar: { show: false } },
                    series: [
                        { name: 'Views', data: views },
                        { name: 'Likes', data: likes },
                        { name: 'Comments', data: comments }
                    ],
                    xaxis: { categories: dates, labels: { rotate: -45 } },
                    colors: ['#3b82f6', '#ef4444', '#f59e0b'],
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
                    dataLabels: { enabled: false },
                    tooltip: { shared: true }
                });
                window.engagementChart.render();
            }
        });
    }

    function loadDevices() {
        $.get('/fetchAnalyticsDevices', { period: currentPeriod }, function (res) {
            if (res.status && res.data) {
                const d = res.data;
                const breakdown = d.deviceBreakdown || d;
                const android = breakdown.android || 0;
                const ios = breakdown.ios || 0;

                if (window.deviceChart) window.deviceChart.destroy();
                window.deviceChart = new ApexCharts(document.querySelector('#device-chart'), {
                    chart: { type: 'donut', height: 280 },
                    series: [android, ios],
                    labels: ['Android', 'iOS'],
                    colors: ['#10b981', '#6366f1'],
                    legend: { position: 'bottom' }
                });
                window.deviceChart.render();

                // Brand breakdown table
                let brandHtml = '';
                const brands = d.brands || d.topBrands || [];
                if (Array.isArray(brands)) {
                    brands.slice(0, 10).forEach((b, i) => {
                        brandHtml += `<tr><td>${i + 1}</td><td>${b._id || b.brand || b.name || '-'}</td><td>${formatNumber(b.count || 0)}</td></tr>`;
                    });
                }
                $('#brand-table-body').html(brandHtml || '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
            }
        });
    }

    function loadLocations() {
        $.get('/fetchAnalyticsLocations', { period: currentPeriod }, function (res) {
            if (res.status && res.data) {
                const countries = res.data.topCountries || res.data || [];
                let html = '';
                if (Array.isArray(countries)) {
                    countries.slice(0, 15).forEach((c, i) => {
                        html += `<tr><td>${i + 1}</td><td>${c._id || c.country || c.name || '-'}</td><td>${formatNumber(c.count || c.dau || 0)}</td></tr>`;
                    });
                }
                $('#location-table-body').html(html || '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
            }
        });
    }

    function loadTopPosts() {
        $.get('/fetchAnalyticsTopPosts', { period: currentPeriod, limit: 10 }, function (res) {
            if (res.status && res.data && Array.isArray(res.data)) {
                let html = '';
                res.data.forEach((p, i) => {
                    html += `<tr>
                        <td>${i + 1}</td>
                        <td>${p.postId || p._id || '-'}</td>
                        <td>${formatNumber(p.views || 0)}</td>
                        <td>${formatNumber(p.likes || 0)}</td>
                        <td>${formatNumber(p.comments || 0)}</td>
                        <td>${formatNumber(p.shares || 0)}</td>
                        <td>${formatNumber(p.engagementScore || 0)}</td>
                    </tr>`;
                });
                $('#top-posts-body').html(html || '<tr><td colspan="7" class="text-center text-muted">No data</td></tr>');
            }
        });
    }

    function loadTopUsers() {
        $.get('/fetchAnalyticsTopUsers', { period: currentPeriod, limit: 10 }, function (res) {
            if (res.status && res.data && Array.isArray(res.data)) {
                let html = '';
                res.data.forEach((u, i) => {
                    html += `<tr>
                        <td>${i + 1}</td>
                        <td>${u.userId || u._id || '-'}</td>
                        <td>${formatNumber(u.views || 0)}</td>
                        <td>${formatNumber(u.likes || 0)}</td>
                        <td>${formatNumber(u.comments || 0)}</td>
                        <td>${formatNumber(u.totalActivity || 0)}</td>
                    </tr>`;
                });
                $('#top-users-body').html(html || '<tr><td colspan="6" class="text-center text-muted">No data</td></tr>');
            }
        });
    }

    function formatNumber(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toString();
    }

    // Initial load
    loadAllData();
});
</script>
@endsection
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">{{ __('Platform Analytics') }}</h4>
        </div>
    </div>
</div>

{{-- Period selector --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="btn-group">
            <button class="btn btn-outline-primary period-btn" data-period="7d">7 Days</button>
            <button class="btn btn-primary period-btn" data-period="30d">30 Days</button>
            <button class="btn btn-outline-primary period-btn" data-period="90d">90 Days</button>
        </div>
    </div>
</div>

{{-- Overview metrics --}}
<div class="row">
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">DAU</h5>
                <h3 class="mb-0" id="metric-dau">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">Views</h5>
                <h3 class="mb-0" id="metric-views">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">Likes</h5>
                <h3 class="mb-0" id="metric-likes">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">Comments</h5>
                <h3 class="mb-0" id="metric-comments">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">Shares</h5>
                <h3 class="mb-0" id="metric-shares">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="text-muted text-uppercase fs-13">Gifts</h5>
                <h3 class="mb-0" id="metric-gifts">0</h3>
            </div>
        </div>
    </div>
</div>

{{-- Engagement chart --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Engagement Trends') }}</h4>
            </div>
            <div class="card-body">
                <div id="engagement-chart"></div>
            </div>
        </div>
    </div>
</div>

{{-- Devices & Locations --}}
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Device Breakdown') }}</h4>
            </div>
            <div class="card-body">
                <div id="device-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Top Device Brands') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-centered">
                        <thead><tr><th>#</th><th>Brand</th><th>Users</th></tr></thead>
                        <tbody id="brand-table-body">
                            <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Top Countries') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-centered">
                        <thead><tr><th>#</th><th>Country</th><th>Users</th></tr></thead>
                        <tbody id="location-table-body">
                            <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Posts & Top Users --}}
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Top Performing Posts') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-centered table-hover">
                        <thead class="table-light">
                            <tr><th>#</th><th>Post ID</th><th>Views</th><th>Likes</th><th>Comments</th><th>Shares</th><th>Score</th></tr>
                        </thead>
                        <tbody id="top-posts-body">
                            <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="header-title">{{ __('Most Active Users') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-centered table-hover">
                        <thead class="table-light">
                            <tr><th>#</th><th>User ID</th><th>Views</th><th>Likes</th><th>Comments</th><th>Activity</th></tr>
                        </thead>
                        <tbody id="top-users-body">
                            <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
