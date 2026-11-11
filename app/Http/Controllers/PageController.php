<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(): View
    {
        $featuredProducts = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->where(fn ($query) => $query->whereNull('available_date')->orWhereDate('available_date', '>=', today()))
            ->latest()
            ->limit(4)
            ->get();

        return view('pages.home', compact('featuredProducts'));
    }

    public function menu(Request $request): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedCategory = $request->string('kategori')->toString();

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->where(fn ($query) => $query->whereNull('available_date')->orWhereDate('available_date', '>=', today()))
            ->when(
                $selectedCategory !== '',
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($categoryQuery) => $categoryQuery->where('slug', $selectedCategory)->where('is_active', true),
                ),
            )
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('pages.menu', compact('categories', 'products', 'selectedCategory'));
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
