<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Katalog publik (§4.1).
     *
     * Hanya menampilkan status tersedia/habis — tidak pernah angka stok,
     * harga modal, atau data laporan (§7.2).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('kategori', '');

        $categories = Category::query()
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->active()
            ->with('category')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when(
                $categorySlug !== '',
                fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $categorySlug)),
            )
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Product $product) => $product->category->name);

        return view('public.katalog', [
            'storeName' => Setting::get('store_name', config('app.name')),
            'storeAddress' => Setting::get('store_address'),
            'storeHours' => Setting::get('store_hours'),
            'whatsapp' => $this->normalizePhone(Setting::get('store_whatsapp')),
            'categories' => $categories,
            'grouped' => $products,
            'search' => $search,
            'activeCategory' => $categorySlug,
        ]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
