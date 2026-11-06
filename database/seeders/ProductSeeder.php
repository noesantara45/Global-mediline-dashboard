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
                'name' => 'Hematology',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'DIRUI',
                'photo' => 'hematology.png',
                'description' => 'Multi-Parameter Access.',
                'unit' => 'Pcs',
                'price' => 300000.00,
            ],
            [
                'name' => 'Dry Electrolyte',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'Prolyte',
                'photo' => 'dry-electrolyte.png',
                'description' => 'Electrolyte Analyzer',
                'unit' => 'Pcs',
                'price' => 10000.00,
            ],
            [
                'name' => 'IMMUNOASSAY',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'SD BIOSENSOR',
                'photo' => 'immunoassay-fluorescence.png',
                'description' => "SD Biosensor, fLOWRESENCE\niMMUNOASSAY",
                'unit' => 'Pcs',
                'price' => 9971.00,
            ],
            [
                'name' => 'BLOOD CULTURE',
                'division' => Product::DIVISION_MICROBIOLOGY,
                'brand' => 'Zhuhai Meihua',
                'photo' => 'blood-culture.png',
                'description' => 'KSO Unit. Zhuhai BT24',
                'unit' => 'Pcs',
                'price' => 11111.00,
            ],
            [
                'name' => 'IMMUNOASSAY CLIA',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'SD BIOSENSOR',
                'photo' => 'immunoassay-clia.png',
                'description' => "SD BIOSENSOR, Chemiluminescence\nImmunoassay (CLIA)",
                'unit' => 'Pcs',
                'price' => 11111110.00,
            ],
            [
                'name' => 'Real Time PCR',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'SD BIOSENSOR',
                'photo' => 'real-time-pcr.png',
                'description' => 'SD BIOSENSOR M10',
                'unit' => 'Pcs',
                'price' => 62.00,
            ],
            [
                'name' => 'Electrolyte Analyzer',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'Prolyte',
                'photo' => 'electrolyte-analyzer.png',
                'description' => 'Prolyte, Electrolyte Analyzer',
                'unit' => 'Pcs',
                'price' => 11110.00,
            ],
            [
                'name' => 'HPLC Automatic',
                'division' => Product::DIVISION_DIAGNOSTIC,
                'brand' => 'BIO-RAD',
                'photo' => 'hplc-automatic.png',
                'description' => "BIORAD D10, HPLC AUTOMATIC\nANALYZER",
                'unit' => 'Pcs',
                'price' => 11111110.00,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                [
                    'name' => $productData['name'],
                ],
                $productData
            );
        }
    }
}
