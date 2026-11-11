<header class="admin-topbar">
    <div class="container-fluid admin-container d-flex align-items-center justify-content-between gap-3">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span class="admin-brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
            <span>{{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }} <small>Admin</small></span>
        </a>

        <nav class="admin-nav" aria-label="Navigasi administrator">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Ringkasan</a>
            <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">Pesanan</a>
            <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Laporan keuangan</a>
            <a class="{{ request()->routeIs('admin.password.*') ? 'active' : '' }}" href="{{ route('admin.password.edit') }}">Ubah kata sandi</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer">Lihat website</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Keluar <i class="bi bi-box-arrow-right ms-1" aria-hidden="true"></i></button>
            </form>
        </nav>
    </div>
</header>
