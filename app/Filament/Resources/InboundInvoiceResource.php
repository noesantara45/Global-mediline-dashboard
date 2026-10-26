<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InboundInvoiceResource\Pages;
use App\Models\InboundInvoice;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class InboundInvoiceResource extends Resource
{
    protected static ?string $model = InboundInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Inbound Invoices';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('OCR Scanner (Opsional)')
                    ->description('Pilih Supplier terlebih dahulu, lalu upload PDF faktur untuk mengisi form secara otomatis berdasarkan setting OCR Supplier.')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->live(),

                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('Upload Faktur (PDF atau Gambar)')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if (! $state || ! $get('supplier_id')) {
                                    return;
                                }

                                $supplier = Supplier::find($get('supplier_id'));
                                if (! $supplier) {
                                    return;
                                }

                                $filePath = $state->getRealPath();
                                $mimeType = mime_content_type($filePath);
                                $text = '';

                                if ($mimeType === 'application/pdf') {
                                    // Parse PDF
                                    $parser = new Parser;
                                    $pdf = $parser->parseFile($filePath);
                                    $text = $pdf->getText();
                                } elseif (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                                    // Parse Image with Tesseract
                                    $text = (new TesseractOCR($filePath))->run();
                                }

                                if (empty($text)) {
                                    Log::info("OCR Extracted empty text for $filePath");

                                    return;
                                }

                                Log::info("OCR Extracted Text:\n".$text);

                                // Parse Invoice Number
                                if ($supplier->ocr_invoice_keyword) {
                                    $pattern = '/'.preg_quote($supplier->ocr_invoice_keyword, '/').'[\s\W]*([A-Za-z0-9\-\/]+)/i';
                                    if (preg_match($pattern, $text, $matches)) {
                                        $set('invoice_number', trim($matches[1]));
                                    }
                                }

                                // Parse Date
                                if ($supplier->ocr_date_keyword) {
                                    // Match "19 Nop 2025" or "19/11/2025"
                                    $pattern = '/'.preg_quote($supplier->ocr_date_keyword, '/').'[\s\W]*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4}|[\d\/\-]+)/i';
                                    if (preg_match($pattern, $text, $matches)) {
                                        $dateStr = trim($matches[1]);

                                        // Handle Indonesian "Nop" to "Nov"
                                        $dateStr = str_ireplace('Nop', 'Nov', $dateStr);

                                        $timestamp = strtotime($dateStr);
                                        if ($timestamp) {
                                            $issueDate = date('Y-m-d', $timestamp);
                                            $set('issue_date', $issueDate);

                                            // Otomatis hitung Due Date berdasarkan TOP Supplier
                                            if ($supplier->top_days > 0) {
                                                $dueDate = date('Y-m-d', strtotime($issueDate.' + '.$supplier->top_days.' days'));
                                                $set('due_date', $dueDate);
                                            }
                                        } else {
                                            $set('issue_date', $dateStr);
                                        }
                                    }
                                }

                                // Parse Subtotal
                                if ($supplier->ocr_subtotal_keyword) {
                                    $pattern = '/'.preg_quote($supplier->ocr_subtotal_keyword, '/').'[\s\W]*((?:Rp)?\s*[\d\.\,]+)/i';
                                    if (preg_match($pattern, $text, $matches)) {
                                        $cleanNumber = preg_replace('/[^0-9]/', '', $matches[1]);
                                        $set('subtotal_amount', $cleanNumber);
                                        // Trigger tax calculation if tax_percentage is set
                                        if ($get('tax_percentage')) {
                                            $set('tax_amount', (float) $cleanNumber * ((float) $get('tax_percentage') / 100));
                                        }
                                    }
                                }

                                // Parse Total
                                if ($supplier->ocr_total_keyword) {
                                    $pattern = '/'.preg_quote($supplier->ocr_total_keyword, '/').'[\s\W]*((?:Rp)?\s*[\d\.\,]+)/i';
                                    if (preg_match($pattern, $text, $matches)) {
                                        $cleanNumber = preg_replace('/[^0-9]/', '', $matches[1]);
                                        $set('total_amount', $cleanNumber);
                                    }
                                }
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Daftar Barang Pembelian (Items)')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('item_name')
                                    ->label('Nama Barang')
                                    ->options(Product::pluck('name', 'name'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = Product::where('name', $state)->first();
                                        if ($product) {
                                            $set('unit', $product->unit);
                                            $set('unit_price', $product->price);
                                            self::updateItemSubtotal($set, $get);
                                        }
                                    })
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemSubtotal($set, $get))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Satuan')
                                    ->default('Pcs')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemSubtotal($set, $get))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->readOnly()
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                    ]),

                Forms\Components\Section::make('Data Faktur')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'Unpaid' => 'Unpaid',
                                'Partial' => 'Partial',
                                'Paid' => 'Paid',
                                'Cancelled' => 'Cancelled',
                            ])
                            ->default('Unpaid')
                            ->required(),
                        Forms\Components\TextInput::make('subtotal_amount')
                            ->label('Subtotal (Rp)')
                            ->required()
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get, $state) => $set('tax_amount', (float) $state * ((float) $get('tax_percentage') / 100))
                            )
                            ->default(0.00),

                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('PPN (%)')
                            ->numeric()
                            ->live()
                            ->dehydrated(false) // Tidak disimpan langsung ke DB
                            ->default(11)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get, $state) => $set('tax_amount', (float) $get('subtotal_amount') * ((float) $state / 100))
                            ),

                        Forms\Components\Hidden::make('tax_amount')
                            ->default(0),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Tagihan (Rp)')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\TextInput::make('paid_amount')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function updateItemSubtotal(Forms\Set $set, Forms\Get $get): void
    {
        $qty = (float) $get('quantity') ?: 0;
        $price = (float) $get('unit_price') ?: 0;
        $subtotal = $qty * $price;
        $set('subtotal', round($subtotal));
    }

    public static function updateTotals(Forms\Set $set, Forms\Get $get): void
    {
        $items = $get('items') ?? [];
        if (! empty($items)) {
            $subtotalAmount = 0;
            foreach ($items as $item) {
                $subtotalAmount += (float) ($item['subtotal'] ?? 0);
            }

            $taxPercentage = (float) ($get('tax_percentage') ?? 11);
            $taxAmount = $subtotalAmount * ($taxPercentage / 100);
            $totalAmount = $subtotalAmount + $taxAmount;

            $set('subtotal_amount', round($subtotalAmount));
            $set('tax_amount', round($taxAmount));
            $set('total_amount', round($totalAmount));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger' => 'Unpaid',
                        'warning' => 'Partial',
                        'success' => 'Paid',
                        'secondary' => 'Cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInboundInvoices::route('/'),
            'create' => Pages\CreateInboundInvoice::route('/create'),
            'edit' => Pages\EditInboundInvoice::route('/{record}/edit'),
        ];
    }
}
