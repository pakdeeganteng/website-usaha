@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
    @include('partials.admin-navigation')

    <main class="admin-dashboard">
        <div class="container-fluid admin-container">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Panel administrasi</span>
                    <h1>Selamat datang, {{ auth()->user()->name }}</h1>
                    <p>Kelola informasi usaha dan akses website dari satu tempat.</p>
                </div>
                <span class="admin-user-badge"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Administrator</span>
            </div>

            <section class="admin-dashboard-card" aria-labelledby="business-profile-title">
                <div class="admin-card-heading">
                    <span class="admin-card-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="business-profile-title">Profil usaha</h2>
                        <p>Informasi ditampilkan dari pengaturan usaha yang tersimpan.</p>
                    </div>
                </div>

                <dl class="admin-business-details">
                    @foreach(['name' => 'Nama usaha', 'phone' => 'Telepon', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'address' => 'Alamat', 'opening_hours' => 'Jam operasional'] as $key => $label)
                        @if(filled(data_get($businessSettings, $key)))
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ data_get($businessSettings, $key) }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                @if($businessSettings->isEmpty())
                    <p class="admin-empty-note mb-0">Informasi usaha belum tersedia di pengaturan bisnis.</p>
                @endif
            </section>

            <section class="admin-quick-links" aria-label="Tautan cepat">
                <a class="admin-quick-link" href="{{ route('menu.index') }}" target="_blank" rel="noopener noreferrer">
                    <span class="admin-card-icon"><i class="bi bi-card-list" aria-hidden="true"></i></span>
                    <span><strong>Lihat daftar menu</strong><small>Buka katalog menu pelanggan</small></span>
                    <i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i>
                </a>
                <a class="admin-quick-link" href="{{ route('admin.password.edit') }}">
                    <span class="admin-card-icon"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <span><strong>Keamanan akun</strong><small>Perbarui kata sandi administrator</small></span>
                    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                </a>
                <a class="admin-quick-link" href="{{ route('admin.reports.index') }}">
                    <span class="admin-card-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                    <span><strong>Laporan penjualan &amp; keuangan</strong><small>Pantau transaksi, pengeluaran, dan keuntungan</small></span>
                    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                </a>
            </section>
        </div>
    </main>
@endsection
