@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', $product->description ?: $product->name.' — menu masakan rumahan DapurHarian.')

@section('content')
    <section class="page-hero py-4 py-lg-5">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('menu.index') }}">Menu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>
        </div>
    </section>

    <section class="section-space pt-4 pt-lg-5">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-6">
                    <x-product-image :product="$product" class="product-detail-image" loading="eager" />
                </div>
                <div class="col-lg-6 col-xl-5 offset-xl-1">
                    @if($product->category)
                        <p class="section-eyebrow mb-2">{{ $product->category->name }}</p>
                    @endif
                    <h1 class="page-title mb-3">{{ $product->name }}</h1>
                    <p class="detail-price mb-3">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</p>
                    <p class="text-muted-custom lh-lg mb-4">{{ $product->description ?: 'Masakan rumahan dengan bahan pilihan, dibuat fresh untuk menemani harimu.' }}</p>

                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                        @if($whatsappUrl)
                            <a class="btn btn-primary" href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo, saya ingin bertanya dan memesan '.$product->name.'.') }}" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Pesan lewat WhatsApp
                            </a>
                        @else
                            <a class="btn btn-primary" href="{{ route('contact') }}">Tanyakan ketersediaan <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                        @endif
                        <a class="btn btn-outline-dark" href="{{ route('menu.index') }}">Kembali ke menu</a>
                    </div>

                    <div class="d-flex flex-wrap gap-3 small text-muted-custom">
                        <span><i class="bi bi-heart me-1 text-warning" aria-hidden="true"></i> Dimasak fresh</span>
                        @if($product->available_date)
                            <span><i class="bi bi-calendar-check me-1 text-warning" aria-hidden="true"></i> Tersedia {{ $product->available_date->translatedFormat('d F Y') }}</span>
                        @endif
                        <span><i class="bi bi-box-seam me-1 text-warning" aria-hidden="true"></i> Pre-order</span>
                    </div>

                    @if($product->stock_limit > 0)
                        <section class="customer-order-form mt-4" aria-labelledby="order-form-title">
                            <h2 id="order-form-title" class="h4">Buat pre-order</h2>
                            <p class="small text-muted-custom">Pesanan akan berstatus Pending sampai admin mengonfirmasi.</p>

                            @if($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('orders.store', $product) }}" class="customer-order-fields">
                                @csrf
                                <div>
                                    <label class="form-label" for="customer_name">Nama pemesan</label>
                                    <input class="form-control" id="customer_name" name="customer_name" type="text" maxlength="255" value="{{ old('customer_name', auth()->user()->name) }}" autocomplete="name" required>
                                    @error('customer_name')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="customer_whatsapp">Nomor WhatsApp</label>
                                    <input class="form-control" id="customer_whatsapp" name="customer_whatsapp" type="tel" maxlength="30" value="{{ old('customer_whatsapp') }}" autocomplete="tel" required>
                                    @error('customer_whatsapp')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="quantity">Jumlah</label>
                                    <input class="form-control" id="quantity" name="quantity" type="number" min="1" max="{{ $product->stock_limit }}" value="{{ old('quantity', 1) }}" required>
                                    @error('quantity')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="pickup_date">Tanggal pengambilan</label>
                                    <input
                                        class="form-control"
                                        id="pickup_date"
                                        name="pickup_date"
                                        type="date"
                                        min="{{ $product->available_date?->toDateString() ?? today()->toDateString() }}"
                                        @if($product->available_date) max="{{ $product->available_date->toDateString() }}" @endif
                                        value="{{ old('pickup_date', $product->available_date?->toDateString() ?? today()->toDateString()) }}"
                                        required
                                    >
                                    @error('pickup_date')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="pickup_time">Waktu pengambilan</label>
                                    <input class="form-control" id="pickup_time" name="pickup_time" type="time" value="{{ old('pickup_time') }}" required>
                                    @error('pickup_time')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="note">Catatan <span class="text-muted">(opsional)</span></label>
                                    <textarea class="form-control" id="note" name="note" rows="2" maxlength="2000">{{ old('note') }}</textarea>
                                    @error('note')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
                                </div>
                                <button class="btn btn-primary" type="submit">
                                    Kirim pre-order <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                                </button>
                            </form>
                        </section>
                    @else
                        <p class="small text-muted-custom mt-4 mb-0">Batas pre-order menu ini belum tersedia. Silakan hubungi kami untuk informasi.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($relatedProducts->isNotEmpty())
        <section class="soft-section section-space">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
                    <div>
                        <p class="section-eyebrow mb-2">Mungkin kamu suka</p>
                        <h2 class="h2 mb-0">Menu lainnya</h2>
                    </div>
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('menu.index') }}">Lihat semua</a>
                </div>
                <div class="row g-4">
                    @foreach($relatedProducts as $relatedProduct)
                        <div class="col-sm-6 col-lg-4">
                            <x-product-card :product="$relatedProduct" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
