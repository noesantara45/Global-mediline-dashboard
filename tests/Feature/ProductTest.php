<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
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
}
