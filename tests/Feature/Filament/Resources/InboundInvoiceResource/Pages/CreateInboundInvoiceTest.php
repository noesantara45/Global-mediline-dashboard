<?php

namespace Tests\Feature\Filament\Resources\InboundInvoiceResource\Pages;

use App\Filament\Resources\InboundInvoiceResource;
use App\Filament\Resources\InboundInvoiceResource\Pages\CreateInboundInvoice;
use App\Models\InboundInvoice;
use App\Models\Supplier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CreateInboundInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_invoice_after_selecting_supplier_populates_fields_items_and_creates_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'top_days' => 30,
            'ocr_invoice_keyword' => 'No. Faktur:',
            'ocr_date_keyword' => 'Tgl Faktur:',
            'ocr_subtotal_keyword' => 'Sub Total :',
            'ocr_total_keyword' => 'Total Faktur:',
            'is_active' => true,
        ]);
        $pdfContent = Pdf::loadHTML($this->sampleInvoiceHtml())->output();
        $uploadedFile = UploadedFile::fake()->createWithContent('faktur.pdf', $pdfContent);
        $fileKey = (string) Str::uuid();

        $component = Livewire::actingAs($user)
            ->test(CreateInboundInvoice::class)
            ->set('data.supplier_id', $supplier->id)
            ->set("data.pdf_file.{$fileKey}", $uploadedFile)
            ->assertNotified('OCR berhasil memproses faktur')
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = InboundInvoice::query()->where('invoice_number', 'INV/BS25/XI/3355')->first();
        $this->assertNotNull($invoice);
        $this->assertModelExists($invoice);
        $this->assertSame('2025-11-19', $invoice->issue_date);
        $this->assertSame('2025-12-19', $invoice->due_date);
        $this->assertSame(5866501.0, (float) $invoice->subtotal_amount);
        $this->assertSame(7081816.0, (float) $invoice->total_amount);
        $this->assertDatabaseCount('inbound_invoice_items', 2);
        $this->assertDatabaseHas('inbound_invoice_items', [
            'inbound_invoice_id' => $invoice->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 25,
            'unit' => 'TST',
        ]);
        $this->assertDatabaseHas('inbound_invoice_items', [
            'inbound_invoice_id' => $invoice->id,
            'item_name' => 'DrACCU Alpha Fetal Protein',
            'quantity' => 25,
            'unit' => 'TST',
        ]);
        $this->assertDatabaseCount('products', 0);
        $component->assertRedirect();
    }

    public function test_entering_formatted_indonesian_paid_amount_with_dots_saves_full_value_correctly(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT Mensa Bina Sukses',
            'top_days' => 14,
        ]);

        Livewire::actingAs($user)
            ->test(CreateInboundInvoice::class)
            ->fillForm([
                'supplier_id' => $supplier->id,
                'invoice_number' => 'INV-TEST-PAID-01',
                'issue_date' => '2026-11-06',
                'due_date' => '2026-11-20',
                'status' => 'Partial',
                'subtotal_amount' => '5.866.501',
                'total_amount' => '7.081.816',
                'paid_amount' => '3.540.908',
                'items' => [
                    [
                        'item_name' => 'Reagen Alkes',
                        'quantity' => 1,
                        'unit' => 'Pcs',
                        'unit_price' => '5.866.501',
                        'subtotal' => 5866501,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = InboundInvoice::where('invoice_number', 'INV-TEST-PAID-01')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(3540908.0, (float) $invoice->paid_amount);
        $this->assertEquals(6511816.0, (float) $invoice->total_amount);
        $this->assertEquals(5866501.0, (float) $invoice->subtotal_amount);
    }

    public function test_selecting_supplier_after_uploading_invoice_triggers_ocr_automatically(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'top_days' => 30,
            'ocr_invoice_keyword' => 'No. Faktur',
            'ocr_date_keyword' => 'Tgl Faktur',
            'ocr_subtotal_keyword' => 'Sub Total :',
            'ocr_total_keyword' => 'Total Faktur',
            'is_active' => true,
        ]);
        $pdfContent = Pdf::loadHTML($this->sampleInvoiceHtml())->output();
        $uploadedFile = UploadedFile::fake()->createWithContent('faktur.pdf', $pdfContent);
        $fileKey = (string) Str::uuid();

        $component = Livewire::actingAs($user)
            ->test(CreateInboundInvoice::class)
            ->set("data.pdf_file.{$fileKey}", $uploadedFile)
            ->assertNotified('Pilih Supplier terlebih dahulu')
            ->set('data.supplier_id', $supplier->id);

        $this->assertSame('INV/BS25/XI/3355', $component->get('data.invoice_number'));
        $this->assertSame('2025-11-19', $component->get('data.issue_date'));
        $this->assertSame('2025-12-19', $component->get('data.due_date'));
        $this->assertSame(5866501.0, (float) $component->get('data.subtotal_amount'));
        $this->assertSame(7081816.0, (float) $component->get('data.total_amount'));
        $this->assertCount(2, $component->get('data.items'));
    }

    public function test_parses_ocr_text_with_indonesian_dates_decimals_and_sku_columns(): void
    {
        $supplier = new Supplier([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'top_days' => 30,
            'ocr_invoice_keyword' => 'No. Faktur',
            'ocr_date_keyword' => 'Tgl Faktur',
            'ocr_subtotal_keyword' => 'Sub Total :',
            'ocr_total_keyword' => 'Total Faktur',
        ]);
        $ocrText = implode("\n", [
            'OKA WAHYU SABARUDIN, BAPAK Tgl Faktur || 19 Nop 2025 Jatuh Tempo',
            'DENPASAR No. Faktur || INV/BS25/XI/3355 =',
            'CEA-W23F3 |DrACCU CEA FIA 25 | TST 135.440,00 25 2.539.500,00',
            'AFP-W23F3 |DrACCU Alpha Fetal Protein 25 | TST 135.440,00 25 2.539.500,00',
            'HbA1c-W23F3|DrACCU HbA1c FIA (Whole Blood) 25 |PCS| 89.375,00| 64.76 787.501,00',
            'Sub Total :| Rp 5.866.501',
            'Total Faktur|:Rp 7.081.816|',
        ]);

        $parsed = InboundInvoiceResource::parseOcrInvoiceData($ocrText, $supplier);

        $this->assertSame('INV/BS25/XI/3355', $parsed['invoice_number']);
        $this->assertSame('2025-11-19', $parsed['issue_date']);
        $this->assertSame('2025-12-19', $parsed['due_date']);
        $this->assertSame(5866501.0, $parsed['subtotal_amount']);
        $this->assertSame(7081816.0, $parsed['total_amount']);
        $this->assertCount(3, $parsed['items']);
        $this->assertSame('DrACCU CEA FIA', $parsed['items'][0]['item_name']);
        $this->assertSame(25, $parsed['items'][0]['quantity']);
        $this->assertSame('TST', $parsed['items'][0]['unit']);
        $this->assertSame(135440.0, $parsed['items'][0]['unit_price']);
        $this->assertSame(2539500.0, $parsed['items'][0]['subtotal']);
    }

    private function sampleInvoiceHtml(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <p>Tgl Faktur: 19 Nop 2025</p>
            <p>No. Faktur: INV/BS25/XI/3355</p>
            <p>1 | CEA-W23F3 | DrACCU CEA FIA | 25 | TST | 135.440,00 | 25 | 2.539.500,00</p>
            <p>2 | AFP-W23F3 | DrACCU Alpha Fetal Protein | 25 | TST | 135.440,00 | 25 | 3.327.001,00</p>
            <p>Sub Total : Rp 5.866.501</p>
            <p>Total Faktur: Rp 7.081.816</p>
        </body>
        </html>
        HTML;
    }
}
