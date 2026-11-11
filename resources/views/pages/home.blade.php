@extends('layouts.app')

@section('title', 'Masakan Rumahan yang Bikin Rindu')
@section('meta_description', 'Temukan menu masakan rumahan DapurHarian yang dibuat fresh dengan bahan pilihan. Lihat menu dan pesan untuk jadwal pre-order.')

@section('content')
    <section class="hero-section py-5 py-lg-0">
        <div class="container py-lg-5">
            <div class="row align-items-center g-5 py-lg-4">
                <div class="col-lg-6 hero-copy">
                    <span class="section-eyebrow d-inline-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-sparkle" aria-hidden="true"></i> Rasa rumah, setiap hari
                    </span>
                    <h1 class="hero-title mb-4">Masakan rumahan yang <em>bikin rindu.</em></h1>
                    <p class="hero-description mb-4">Dimasak dengan bahan pilihan, bumbu yang akrab, dan perhatian di setiap sajian. Hangatnya masakan rumah kini tinggal selangkah.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-primary" href="{{ route('menu.index') }}">Jelajahi menu <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                        <a class="btn btn-outline-dark" href="{{ route('about') }}">Cerita kami</a>
                    </div>
                    <div class="trust-row d-flex flex-wrap gap-3 gap-sm-4 mt-4">
                        <span><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Bahan pilihan</span>
                        <span><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Dimasak fresh</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual">
                        <img class="hero-photo" src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&q=80" alt="Hidangan rumahan segar dengan sayuran dan lauk pilihan" fetchpriority="high">
                        <div class="hero-note d-flex align-items-center gap-3 p-3">
                            <span class="hero-note-icon"><i class="bi bi-heart-fill" aria-hidden="true"></i></span>
                            <span><strong class="d-block">Dari dapur kami</strong><small class="text-muted-custom">Untuk meja makanmu</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="menu-unggulan">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4 mb-lg-5">
                <div>
                    <p class="section-eyebrow mb-2">Pilihan dari dapur</p>
                    <h2 class="section-heading mb-0">Menu favorit, dibuat sepenuh hati.</h2>
                </div>
                <a class="btn btn-outline-dark flex-shrink-0" href="{{ route('menu.index') }}">Lihat semua menu <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
            </div>

            <div class="row g-4">
                @forelse($featuredProducts as $product)
                    <div class="col-sm-6 col-lg-3">
                        <x-product-card :product="$product" />
                    </div>
                @empty
                    <div class="col-12">
                        <div class="soft-section rounded-4 p-4 p-lg-5 text-center">
                            <i class="bi bi-egg-fried fs-1 text-warning" aria-hidden="true"></i>
                            <h3 class="h4 mt-3">Menu pilihan sedang disiapkan</h3>
                            <p class="text-muted-custom mb-0">Silakan kembali sebentar lagi untuk melihat sajian terbaru dari dapur kami.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="soft-section section-space">
        <div class="container">
            <div class="text-center mx-auto mb-4 mb-lg-5" style="max-width: 42rem;">
                <p class="section-eyebrow mb-2">Semudah itu</p>
                <h2 class="section-heading mx-auto mb-3">Dari dapur kami ke meja makanmu.</h2>
                <p class="text-muted-custom mb-0">Nikmati hidangan favorit tanpa repot. Pilih menu, tentukan waktu, lalu ambil pesananmu.</p>
            </div>

            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="step-number mb-4">01</span>
                        <h3 class="h4 mb-2">Pilih menu</h3>
                        <p class="text-muted-custom mb-0">Temukan masakan yang paling pas untuk menemani harimu.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="step-number mb-4">02</span>
                        <h3 class="h4 mb-2">Hubungi kami</h3>
                        <p class="text-muted-custom mb-0">Konfirmasi pilihan dan jadwal pre-order melalui kontak usaha.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="step-number mb-4">03</span>
                        <h3 class="h4 mb-2">Ambil pesanan</h3>
                        <p class="text-muted-custom mb-0">Datang sesuai waktu yang telah disepakati dan nikmati selagi hangat.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-6">
                    <img class="story-image" src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=80" alt="Sajian rumahan yang dinikmati bersama" loading="lazy" decoding="async">
                </div>
                <div class="col-lg-6 col-xl-5 offset-xl-1">
                    <p class="section-eyebrow mb-2">Sedikit cerita</p>
                    <h2 class="section-heading mb-3">Bukan sekadar kenyang, tapi terasa seperti pulang.</h2>
                    <p class="text-muted-custom lh-lg">DapurHarian hadir dari kecintaan pada masakan rumah—rasa sederhana yang selalu punya tempat istimewa. Setiap menu disiapkan dengan bahan pilihan dan perhatian pada rasa.</p>
                    <p class="story-accent ps-3 py-1 mb-4">Kami percaya, makanan terbaik selalu dibuat dengan hati.</p>
                    <a class="btn btn-outline-dark" href="{{ route('about') }}">Kenali DapurHarian <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5 pb-lg-5">
        <div class="container">
            <div class="contact-banner p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <p class="section-eyebrow mb-2">Ada yang ingin ditanyakan?</p>
                        <h2 class="h1 mb-2">Kami senang menyapa kamu.</h2>
                        <p class="mb-0 text-white-50">Tanya menu, jadwal pre-order, atau hal lain seputar DapurHarian.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a class="btn btn-light" href="{{ route('contact') }}">Hubungi kami <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
