<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Reagen Test Hba1C 25T', 'unit' => '25', 'price' => 1375000],
            ['name' => 'Reagent Test CEA', 'unit' => '25', 'price' => 6500000],
            ['name' => 'Reagent Test AFP', 'unit' => '25', 'price' => 6500000],
            ['name' => 'Catrige Electrolyte', 'unit' => '25', 'price' => 3000000],
            ['name' => 'Control Electrolyte', 'unit' => '1', 'price' => 1500000],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
