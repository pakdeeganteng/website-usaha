<div class="announcement-bar py-2 text-center">
    <span><i class="bi bi-heart-fill me-1" aria-hidden="true"></i> Masakan rumahan, dibuat dengan sepenuh hati.</span>
</div>

<nav class="navbar navbar-expand-lg bg-white sticky-top site-navbar" aria-label="Navigasi utama">
    <div class="container py-1">
        <a class="navbar-brand d-inline-flex align-items-center gap-2" href="{{ route('home') }}">
            <span class="brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
            <span>{{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavigation" aria-controls="customerNavigation" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="customerNavigation">
            <ul class="navbar-nav mx-auto my-3 my-lg-0 gap-lg-3">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif href="{{ route('home') }}">Beranda</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('menu.*') ? 'active' : '' }}" @if(request()->routeIs('menu.*')) aria-current="page" @endif href="{{ route('menu.index') }}">Menu</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif href="{{ route('about') }}">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" @if(request()->routeIs('contact')) aria-current="page" @endif href="{{ route('contact') }}">Kontak</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif href="{{ route('orders.index') }}">Riwayat pesanan</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2 ms-lg-3">
                <div class="theme-switcher d-flex align-items-center gap-2 flex-wrap" aria-label="Pilih gaya desain website">
                    <button type="button" class="theme-chip active" data-theme-option="luxury" aria-label="Gaya Luxury Corporate">Luxury</button>
                    <button type="button" class="theme-chip" data-theme-option="startup" aria-label="Gaya Startup Pitch">Startup</button>
                    <button type="button" class="theme-chip" data-theme-option="reporting" aria-label="Gaya Internal Reporting">Reporting</button>
                    <button type="button" class="theme-chip" data-theme-option="tender" aria-label="Gaya Formal Tender Proposal">Tender</button>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="nav-account-name d-none d-xl-inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-dark btn-sm px-4" type="submit">
                            Keluar <i class="bi bi-box-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>
                    <a class="btn btn-primary btn-sm px-4" href="{{ route('menu.index') }}">
                        Pesan sekarang <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
