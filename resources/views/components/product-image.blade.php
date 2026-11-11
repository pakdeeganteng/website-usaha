@props(['product', 'class' => '', 'loading' => 'lazy'])

@php
    $categoryImages = [
        'makanan-utama' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=720&q=80',
        'lauk' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=720&q=80',
        'minuman' => 'https://images.unsplash.com/photo-1513558161293-cdab5e56df1d?auto=format&fit=crop&w=720&q=80',
    ];
    $imageUrl = $product->photo
        ? (\Illuminate\Support\Str::startsWith($product->photo, ['http://', 'https://'])
            ? $product->photo
            : \Illuminate\Support\Facades\Storage::disk('public')->url($product->photo))
        : ($categoryImages[$product->category?->slug ?? ''] ?? 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=720&q=80');
@endphp

<img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="{{ $class }}" loading="{{ $loading }}" decoding="async">
