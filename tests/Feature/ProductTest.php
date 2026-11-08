<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Client;
use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_description_and_photo(): void
    {
        $product = Product::create([
            'name' => 'Alat Cek Darah Portabel',
            'unit' => 'Unit',
            'price' => 500000,
            'description' => 'Alat portabel untuk pengecekan darah lengkap.',
            'photo' => 'sample.png',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Alat Cek Darah Portabel',
            'description' => 'Alat portabel untuk pengecekan darah lengkap.',
            'photo' => 'sample.png',
        ]);

        // Clean up
        $product->delete();
    }

    public function test_authenticated_admin_can_access_products_page(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/products');

        $response->assertSuccessful();
    }

    public function test_authenticated_admin_can_access_product_edit_page(): void
    {
        $user = User::first() ?? User::factory()->create();
        $product = Product::create([
            'name' => 'Produk Tes',
            'unit' => 'Box',
            'price' => 100000,
            'description' => 'Deskripsi produk tes.',
            'photo' => 'test.png',
        ]);

        $response = $this->actingAs($user)->get("/admin/products/{$product->id}/edit");

        $response->assertSuccessful();
    }

    public function test_can_create_product_with_uploaded_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('product.jpg');

        Livewire::actingAs($user)
            ->test(CreateProduct::class)
            ->fillForm([
                'name' => 'Produk Baru',
                'unit' => 'Pcs',
                'price' => 75000,
                'description' => 'Deskripsi produk baru',
                'photo' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Produk Baru')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->photo);
        Storage::disk('public')->assertExists($product->photo);
    }

    public function test_can_edit_product_page_loads_and_saves(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $oldFile = UploadedFile::fake()->image('old_photo.jpg');
        $oldPath = $oldFile->store('products', 'public');

        $product = Product::create([
            'name' => 'Produk Lama',
            'unit' => 'Pcs',
            'price' => 50000,
            'description' => 'Deskripsi lama',
            'photo' => $oldPath,
        ]);

        Livewire::actingAs($user)
            ->test(EditProduct::class, [
                'record' => $product->getRouteKey(),
            ])
            ->assertSuccessful()
            ->assertFormSet([
                'name' => 'Produk Lama',
                'description' => 'Deskripsi lama',
            ])
            ->fillForm([
                'name' => 'Produk Update',
                'description' => 'Deskripsi diperbarui',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertEquals('Produk Update', $product->name);
        $this->assertEquals('Deskripsi diperbarui', $product->description);
    }

    public function test_renaming_product_cascades_to_invoice_items_and_prevents_deleting_records_with_history(): void
    {
        $client = Client::create(['name' => 'RS Kasih Ibu', 'type' => 'Rumah Sakit']);
        $supplier = Supplier::create(['name' => 'PT Alkes Utama', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Reagen Typo',
            'division' => Product::DIVISION_DIAGNOSTIC,
            'brand' => 'DIRUI',
            'unit' => 'Kit',
            'price' => 500000,
        ]);

        $outbound = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-OUT-HIST-01',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 500000,
            'tax_amount' => 55000,
            'total_amount' => 555000,
            'paid_amount' => 555000,
        ]);
        $outboundItem = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $outbound->id,
            'item_name' => 'Reagen Typo',
            'quantity' => 1,
            'unit' => 'Kit',
            'unit_price' => 500000,
            'subtotal' => 500000,
        ]);

        $inbound = InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-INB-HIST-01',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 300000,
            'tax_amount' => 33000,
            'total_amount' => 333000,
            'paid_amount' => 333000,
        ]);
        $inboundItem = InboundInvoiceItem::create([
            'inbound_invoice_id' => $inbound->id,
            'item_name' => 'Reagen Typo',
            'quantity' => 1,
            'unit' => 'Kit',
            'unit_price' => 300000,
            'subtotal' => 300000,
        ]);

        $product->update(['name' => 'Reagen Benar']);

        $this->assertSame('Reagen Benar', $outboundItem->fresh()->item_name);
        $this->assertSame('Reagen Benar', $inboundItem->fresh()->item_name);

        $this->assertFalse($product->delete());
        $this->assertFalse($client->delete());
        $this->assertFalse($supplier->delete());

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('outbound_invoices', ['id' => $outbound->id]);
        $this->assertDatabaseHas('inbound_invoices', ['id' => $inbound->id]);
    }

    public function test_database_seeder_is_idempotent_and_photo_url_invalidates_when_photo_changes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('suppliers', 2);
        $this->assertDatabaseCount('clients', 2);

        $product = Product::create([
            'name' => 'Produk Foto Dinamis',
            'unit' => 'Pcs',
            'price' => 100000,
            'photo' => 'hematology.png',
        ]);

        $firstUrl = $product->photo_url;
        $product->update(['photo' => 'dry-electrolyte.png']);
        $secondUrl = $product->photo_url;

        $this->assertNotNull($firstUrl);
        $this->assertNotNull($secondUrl);
        $this->assertNotSame($firstUrl, $secondUrl);
    }
}
