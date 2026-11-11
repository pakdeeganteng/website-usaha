<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless(
            $product->is_active
                && ($product->available_date === null || $product->available_date->isToday() || $product->available_date->isFuture())
                && $product->category()->where('is_active', true)->exists(),
            404,
        );

        $product->load('category');

        $relatedProducts = Product::query()
            ->with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->getKey())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('available_date')->orWhereDate('available_date', '>=', today()))
            ->limit(3)
            ->get();

        return view('pages.product', compact('product', 'relatedProducts'));
    }
}
