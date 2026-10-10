<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin User
        User::factory()->create([
            'name' => 'Admin GBI',
            'email' => 'gbi@gmail.com',
            'password' => bcrypt('gbi123123'),
        ]);

        // 2. Create Suppliers
        Supplier::create([
            'name' => 'PT. Medika Farma',
            'contact_person' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@medikafarma.com',
            'address' => 'Jl. Kesehatan No. 1, Jakarta',
            'tax_id' => '12.345.678.9-001.000',
            'top_days' => 30,
            'ocr_invoice_keyword' => 'No. Faktur:',
            'ocr_date_keyword' => 'Tanggal:',
            'ocr_total_keyword' => 'Total Harga:',
        ]);

        Supplier::create([
            'name' => 'CV. Alat Medis Indo',
            'contact_person' => 'Siti Aminah',
            'phone' => '089876543210',
            'email' => 'siti@alatmedis.co.id',
            'address' => 'Jl. Industri No. 2, Bandung',
            'top_days' => 14,
            'ocr_invoice_keyword' => 'Invoice No.',
            'ocr_date_keyword' => 'Issue Date:',
            'ocr_total_keyword' => 'Grand Total:',
        ]);

        // 3. Create Clients
        Client::create([
            'name' => 'RSUD Harapan Bunda',
            'type' => 'Rumah Sakit',
            'contact_person' => 'Dr. Andi',
            'phone' => '021-9876543',
            'email' => 'admin@rsudharapan.com',
            'address' => 'Jl. Raya Sehat No. 10, Depok',
        ]);

        Client::create([
            'name' => 'Apotek Sejahtera',
            'type' => 'Apotek',
            'contact_person' => 'Bpk. Rudi',
            'phone' => '021-1234567',
            'address' => 'Jl. Kemerdekaan No. 45, Bogor',
        ]);
    }
}
