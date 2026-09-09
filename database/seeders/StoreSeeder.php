<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedSettings();
        $this->seedCatalog();
    }

    private function seedSettings(): void
    {
        $defaults = [
            'store_name' => "Nadi's Fotocopy",
            'store_address' => 'Jl. Contoh No. 1, Kota Anda',
            'store_hours' => 'Senin–Sabtu, 08.00–20.00 WIB',
            'store_whatsapp' => '628123456789',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function seedCatalog(): void
    {
        $catalog = [
            'Layanan Fotocopy' => [
                ['Fotocopy Hitam Putih A4', 300, 'lembar', ProductType::Jasa],
                ['Fotocopy Hitam Putih F4', 350, 'lembar', ProductType::Jasa],
                ['Fotocopy Warna A4', 1500, 'lembar', ProductType::Jasa],
            ],
            'Layanan Cetak' => [
                ['Print Hitam Putih A4', 500, 'lembar', ProductType::Jasa],
                ['Print Warna A4', 2000, 'lembar', ProductType::Jasa],
                ['Scan Dokumen', 2000, 'lembar', ProductType::Jasa],
            ],
            'Penjilidan' => [
                ['Jilid Spiral', 15000, 'buku', ProductType::Jasa],
                ['Jilid Lakban', 8000, 'buku', ProductType::Jasa],
                ['Laminating A4', 5000, 'lembar', ProductType::Jasa],
            ],
            'Alat Tulis' => [
                ['Pulpen Standard AE7', 3500, 'pcs', ProductType::Produk, 120],
                ['Pensil 2B', 3000, 'pcs', ProductType::Produk, 80],
                ['Penghapus', 2500, 'pcs', ProductType::Produk, 60],
                ['Map Kertas', 2000, 'pcs', ProductType::Produk, 150],
                ['Stopmap Plastik', 5000, 'pcs', ProductType::Produk, 40],
            ],
            'Kertas' => [
                ['Kertas HVS A4 70gr', 55000, 'rim', ProductType::Produk, 25],
                ['Kertas HVS F4 70gr', 60000, 'rim', ProductType::Produk, 20],
                ['Amplop Putih', 1000, 'pcs', ProductType::Produk, 200],
            ],
        ];

        $sortOrder = 0;

        foreach ($catalog as $categoryName => $products) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $sortOrder++],
            );

            foreach ($products as $row) {
                [$name, $price, $unit, $type] = $row;
                $stock = $row[4] ?? 0;

                Product::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'unit' => $unit,
                        'type' => $type,
                        'track_stock' => $type->tracksStockByDefault(),
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
