@extends('layouts.app')

@section('title', 'Kontak')
@section('meta_description', 'Hubungi DapurHarian untuk bertanya tentang menu, ketersediaan, dan jadwal pre-order.')

@section('content')
    <section class="page-hero py-5">
        <div class="container py-lg-4">
            <p class="section-eyebrow mb-2">Kami siap menyapa</p>
            <h1 class="page-title mb-3">Ada yang bisa kami bantu?</h1>
            <p class="hero-description mb-0">Tanyakan menu, ketersediaan, atau jadwal pre-order. Kami senang mendengar darimu.</p>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <div class="row g-3 g-lg-4">
                @if(data_get($businessSettings, 'phone'))
                    <div class="col-md-6 col-lg-4">
                        <article class="info-card p-4 p-lg-5">
                            <span class="info-icon mb-4"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                            <h2 class="h4">Telepon</h2>
                            <p class="text-muted-custom mb-3">{{ data_get($businessSettings, 'phone') }}</p>
                            <a class="btn btn-primary btn-sm" href="tel:{{ preg_replace('/\D+/', '', (string) data_get($businessSettings, 'phone', '')) }}">Hubungi via telepon <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
                        </article>
                    </div>
                @endif

                <div class="col-md-6 col-lg-4">
                    <article class="info-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                        <h2 class="h4">WhatsApp</h2>
                        @if($whatsappUrl)
                            <p class="text-muted-custom">{{ data_get($businessSettings, 'whatsapp') }}</p>
                            <a class="btn btn-primary btn-sm" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Mulai percakapan <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
                        @else
                            <p class="text-muted-custom mb-0">Nomor WhatsApp usaha belum tersedia. Silakan hubungi kami melalui kanal yang tercantum di halaman ini.</p>
                        @endif
                    </article>
                </div>

                <div class="col-md-6 col-lg-4">
                    <article class="info-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                        <h2 class="h4">Lokasi</h2>
                        <p class="text-muted-custom mb-0">{{ data_get($businessSettings, 'address', 'Informasi lokasi akan diberikan saat konfirmasi pesanan.') }}</p>
                    </article>
                </div>

                <div class="col-md-6 col-lg-4">
                    <article class="info-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-clock" aria-hidden="true"></i></span>
                        <h2 class="h4">Jadwal</h2>
                        <p class="text-muted-custom mb-0">{{ data_get($businessSettings, 'opening_hours', 'Waktu pengambilan mengikuti jadwal pre-order yang dikonfirmasi.') }}</p>
                    </article>
                </div>

                @if(data_get($businessSettings, 'email'))
                    <div class="col-md-6 col-lg-4">
                        <article class="info-card p-4 p-lg-5">
                            <span class="info-icon mb-4"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <h2 class="h4">Email</h2>
                            <a href="mailto:{{ data_get($businessSettings, 'email') }}">{{ data_get($businessSettings, 'email') }}</a>
                        </article>
                    </div>
                @endif
            </div>

            <div class="soft-section rounded-4 p-4 p-lg-5 mt-4 mt-lg-5">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <h2 class="h3 mb-2">Sudah menemukan menu favorit?</h2>
                        <p class="text-muted-custom mb-0">Jelajahi menu kami dan tanyakan ketersediaannya sebelum memesan.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a class="btn btn-primary" href="{{ route('menu.index') }}">Lihat menu <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
