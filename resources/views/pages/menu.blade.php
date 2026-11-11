@extends('layouts.app')

@section('title', 'Daftar Menu')
@section('meta_description', 'Lihat pilihan menu masakan rumahan DapurHarian dan pilih sajian favoritmu.')

@section('content')
    <section class="page-hero py-5">
        <div class="container py-lg-4">
            <p class="section-eyebrow mb-2">Dimasak dengan hati</p>
            <h1 class="page-title mb-3">Temukan menu favoritmu.</h1>
            <p class="hero-description mb-0">Sajian rumahan dari bahan pilihan, siap menemani hari-harimu.</p>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <div class="d-flex flex-wrap gap-2 mb-4 mb-lg-5" aria-label="Filter kategori menu">
                <a class="category-filter px-3 py-2 {{ $selectedCategory === '' ? 'active' : '' }}" href="{{ route('menu.index') }}" @if($selectedCategory === '') aria-current="page" @endif>Semua menu</a>
                @foreach($categories as $category)
                    <a class="category-filter px-3 py-2 {{ $selectedCategory === $category->slug ? 'active' : '' }}" href="{{ route('menu.index', ['kategori' => $category->slug]) }}" @if($selectedCategory === $category->slug) aria-current="page" @endif>{{ $category->name }}</a>
                @endforeach
            </div>

            @if($products->isNotEmpty())
                <div class="row g-4">
                    @foreach($products as $product)
                        <div class="col-sm-6 col-lg-4">
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
                <div class="mt-5">{{ $products->links() }}</div>
            @else
                <div class="soft-section rounded-4 p-4 p-lg-5 text-center">
                    <i class="bi bi-egg-fried fs-1 text-warning" aria-hidden="true"></i>
                    <h2 class="h3 mt-3">Menu belum tersedia</h2>
                    <p class="text-muted-custom mb-4">Pilihan baru sedang disiapkan. Silakan cek kembali nanti.</p>
                    @if($selectedCategory !== '')
                        <a class="btn btn-outline-dark" href="{{ route('menu.index') }}">Lihat semua menu</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
