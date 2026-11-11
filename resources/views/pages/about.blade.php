@extends('layouts.app')

@section('title', 'Tentang Usaha')
@section('meta_description', 'Kenali DapurHarian, dapur masakan rumahan yang mengutamakan rasa akrab, bahan pilihan, dan perhatian di setiap sajian.')

@section('content')
    <section class="page-hero py-5">
        <div class="container py-lg-4">
            <p class="section-eyebrow mb-2">Cerita di balik setiap sajian</p>
            <h1 class="page-title mb-3">Berawal dari rasa yang selalu dirindukan.</h1>
            <p class="hero-description mb-0">DapurHarian membawa hangatnya masakan rumah lebih dekat ke keseharianmu.</p>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-6">
                    <img class="story-image" src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=80" alt="Hidangan rumahan tersaji hangat di meja" loading="lazy" decoding="async">
                </div>
                <div class="col-lg-6 col-xl-5 offset-xl-1">
                    <p class="section-eyebrow mb-2">Dari dapur, dengan hati</p>
                    <h2 class="section-heading mb-3">Rasa sederhana yang punya arti.</h2>
                    <p class="text-muted-custom lh-lg">Ada sesuatu yang istimewa dari masakan rumah: aromanya yang akrab, bumbu yang meresap, dan rasa nyaman di setiap suapan. Itulah semangat yang ingin kami hadirkan lewat setiap menu DapurHarian.</p>
                    <p class="text-muted-custom lh-lg mb-0">Kami menyiapkan sajian berdasarkan pre-order agar setiap pesanan bisa dibuat dengan lebih cermat. Mulai dari memilih bahan hingga mengemasnya, semuanya dilakukan dengan perhatian yang sama seperti memasak untuk keluarga sendiri.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="soft-section section-space">
        <div class="container">
            <div class="text-center mx-auto mb-4 mb-lg-5" style="max-width: 40rem;">
                <p class="section-eyebrow mb-2">Yang selalu kami jaga</p>
                <h2 class="section-heading mx-auto mb-0">Kebaikan kecil di setiap hidangan.</h2>
            </div>
            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-basket2" aria-hidden="true"></i></span>
                        <h3 class="h4">Bahan pilihan</h3>
                        <p class="text-muted-custom mb-0">Kami mengutamakan bahan yang baik agar rasa dan kualitas tetap terjaga.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-fire" aria-hidden="true"></i></span>
                        <h3 class="h4">Dimasak fresh</h3>
                        <p class="text-muted-custom mb-0">Pesanan disiapkan dengan cermat sesuai jadwal pre-order yang disepakati.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="step-card p-4 p-lg-5">
                        <span class="info-icon mb-4"><i class="bi bi-heart" aria-hidden="true"></i></span>
                        <h3 class="h4">Penuh perhatian</h3>
                        <p class="text-muted-custom mb-0">Setiap hidangan kami buat dengan kepedulian pada rasa dan pengalamanmu.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space">
        <div class="container text-center">
            <p class="section-eyebrow mb-2">Yuk, makan enak</p>
            <h2 class="section-heading mx-auto mb-4">Ada menu yang ingin kamu coba?</h2>
            <a class="btn btn-primary" href="{{ route('menu.index') }}">Lihat daftar menu <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
        </div>
    </section>
@endsection
