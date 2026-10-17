<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Reagen Test Hba1C 25T',
                'unit' => '25',
                'price' => 1375000,
                'description' => 'Reagen tes rata-rata gula darah.',
                'photo' => '1.png',
            ],
            [
                'name' => 'Reagent Test CEA',
                'unit' => '25',
                'price' => 6500000,
                'description' => 'Reagen deteksi penanda tumor kanker.',
                'photo' => '2.png',
            ],
            [
                'name' => 'Reagent Test AFP',
                'unit' => '25',
                'price' => 6500000,
                'description' => 'Reagen deteksi dini kanker hati.',
                'photo' => '3.png',
            ],
            [
                'name' => 'Catrige Electrolyte',
                'unit' => '25',
                'price' => 3000000,
                'description' => 'Kartrid pengukur elektrolit dalam darah.',
                'photo' => '4.png',
            ],
            [
                'name' => 'Control Electrolyte',
                'unit' => '1',
                'price' => 1500000,
                'description' => 'Cairan kontrol kalibrasi mesin elektrolit.',
                'photo' => '5.png',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
