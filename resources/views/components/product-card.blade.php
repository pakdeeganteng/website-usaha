@props(['product'])

<article class="food-card">
    <a class="food-card-image-wrap d-block" href="{{ route('menu.show', $product) }}" aria-label="Lihat {{ $product->name }}">
        <x-product-image :product="$product" class="food-card-image" />
    </a>
    <div class="p-3 p-lg-4">
        @if($product->category)
            <p class="food-card-category mb-2">{{ $product->category->name }}</p>
        @endif
        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
            <h3 class="food-card-title mb-0">{{ $product->name }}</h3>
            <span class="price-tag text-nowrap">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</span>
        </div>
        <p class="food-card-description mb-3">{{ $product->description ?: 'Masakan rumahan dengan bahan pilihan, dibuat fresh untuk menemani harimu.' }}</p>
        <a class="small fw-semibold link-dark" href="{{ route('menu.show', $product) }}">Lihat detail <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
    </div>
</article>
