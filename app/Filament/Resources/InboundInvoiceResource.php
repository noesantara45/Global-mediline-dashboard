<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InboundInvoiceResource\Pages;
use App\Models\InboundInvoice;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

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
                    ->description('Pilih Supplier dan upload PDF atau gambar faktur untuk mengisi Data Faktur dan Daftar Barang secara otomatis.')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                if ($state && filled($get('pdf_file'))) {
                                    self::processOcrScan($get('pdf_file'), (int) $state, $set, $get);
                                }
                            }),

                        Forms\Components\FileUpload::make('pdf_file')
                            ->label('Upload Faktur (PDF atau Gambar)')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->live()
                            ->hintAction(
                                Forms\Components\Actions\Action::make('rescanOcr')
                                    ->label('Scan Ulang OCR')
                                    ->icon('heroicon-m-document-magnifying-glass')
                                    ->visible(fn (Forms\Get $get): bool => filled($get('pdf_file')) && filled($get('supplier_id')))
                                    ->action(function (Forms\Set $set, Forms\Get $get): void {
                                        if (filled($get('pdf_file')) && filled($get('supplier_id'))) {
                                            self::processOcrScan($get('pdf_file'), (int) $get('supplier_id'), $set, $get);
                                        }
                                    })
                            )
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                if (! $state) {
                                    return;
                                }

                                if (! $get('supplier_id')) {
                                    Notification::make()
                                        ->title('Pilih Supplier terlebih dahulu')
                                        ->body('Faktur berhasil diunggah. Silakan pilih Supplier agar OCR memproses faktur secara otomatis.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                self::processOcrScan($state, (int) $get('supplier_id'), $set, $get);
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Daftar Barang Pembelian (Items)')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->label('Nama Barang')
                                    ->placeholder('Ketik nama barang pembelian...')
                                    ->required()
                                    ->maxLength(255)
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
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, ',', '.') : '')
                                    ->dehydrateStateUsing(fn ($state) => InboundInvoiceResource::parseIndonesianAmount((string) $state))
                                    ->required()
                                    ->live(onBlur: true)
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
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => InboundInvoiceResource::parseIndonesianAmount((string) $state))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get, $state) => $set('tax_amount', InboundInvoiceResource::parseIndonesianAmount((string) $state) * ((float) $get('tax_percentage') / 100))
                            )
                            ->default(0.00),

                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('PPN (%)')
                            ->numeric()
                            ->live()
                            ->dehydrated(false) // Tidak disimpan langsung ke DB
                            ->default(11)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get, $state) => $set('tax_amount', InboundInvoiceResource::parseIndonesianAmount((string) $get('subtotal_amount')) * ((float) $state / 100))
                            ),

                        Forms\Components\Hidden::make('tax_amount')
                            ->default(0),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Tagihan (Rp)')
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => InboundInvoiceResource::parseIndonesianAmount((string) $state))
                            ->required()
                            ->default(0.00),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Sudah Dibayar (Rp)')
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => InboundInvoiceResource::parseIndonesianAmount((string) $state))
                            ->required()
                            ->default(0.00),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function processOcrScan(mixed $fileState, int $supplierId, Forms\Set $set, Forms\Get $get): void
    {
        $supplier = Supplier::find($supplierId);
        if (! $supplier) {
            return;
        }

        $filePath = self::resolveUploadedFilePath($fileState);
        if (! $filePath || ! is_file($filePath)) {
            return;
        }

        try {
            $mimeType = mime_content_type($filePath) ?: '';
            $text = self::extractOcrText($filePath, $mimeType, 4);

            if (blank($text)) {
                Log::info("OCR Extracted empty text for {$filePath}");

                Notification::make()
                    ->title('Teks faktur tidak terbaca')
                    ->body('OCR tidak menemukan teks pada file yang diunggah.')
                    ->warning()
                    ->send();

                return;
            }

            Log::info("OCR Extracted Text:\n".$text);

            $parsed = self::parseOcrInvoiceData($text, $supplier);

            $needsFallbackPass = in_array($mimeType, ['image/jpeg', 'image/png'], true)
                && (($supplier->ocr_invoice_keyword && $parsed['invoice_number'] === null)
                    || ($supplier->ocr_date_keyword && $parsed['issue_date'] === null)
                    || ($supplier->ocr_total_keyword && $parsed['total_amount'] === null));

            if ($needsFallbackPass) {
                $fallbackText = self::extractOcrText($filePath, $mimeType, 3);
                if (filled($fallbackText)) {
                    $fallbackParsed = self::parseOcrInvoiceData($fallbackText, $supplier);
                    $parsed['invoice_number'] ??= $fallbackParsed['invoice_number'];
                    $parsed['issue_date'] ??= $fallbackParsed['issue_date'];
                    $parsed['due_date'] ??= $fallbackParsed['due_date'];
                    $parsed['subtotal_amount'] ??= $fallbackParsed['subtotal_amount'];
                    $parsed['total_amount'] ??= $fallbackParsed['total_amount'];

                    if (empty($parsed['items']) && ! empty($fallbackParsed['items'])) {
                        $parsed['items'] = $fallbackParsed['items'];
                    }
                }
            }

            $matchedFields = [];

            if (! empty($parsed['items'])) {
                $repeaterItems = [];
                foreach ($parsed['items'] as $itemData) {
                    $repeaterItems[(string) Str::uuid()] = [
                        'item_name' => $itemData['item_name'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['subtotal'],
                    ];
                }

                $set('items', $repeaterItems);
                $matchedFields[] = count($repeaterItems).' item barang';
            } else {
                $existingItems = $get('items') ?? [];
                if (count($existingItems) === 1) {
                    $firstRow = Arr::first($existingItems);
                    if (blank($firstRow['item_name'] ?? null) && blank($firstRow['unit_price'] ?? null)) {
                        $set('items', []);
                    }
                }
            }

            if ($parsed['invoice_number'] !== null) {
                $set('invoice_number', $parsed['invoice_number']);
                $matchedFields[] = 'No. Faktur';
            }

            if ($parsed['issue_date'] !== null) {
                $set('issue_date', $parsed['issue_date']);
                $matchedFields[] = 'Tanggal';

                if ($parsed['due_date'] !== null) {
                    $set('due_date', $parsed['due_date']);
                }
            }

            $itemsSubtotal = array_sum(array_column($parsed['items'], 'subtotal'));
            $subtotalAmount = $parsed['subtotal_amount'] ?? ($itemsSubtotal > 0 ? $itemsSubtotal : null);
            $taxPercentage = (float) ($get('tax_percentage') ?? 11);

            if ($subtotalAmount !== null) {
                $set('subtotal_amount', round($subtotalAmount));
                $taxAmount = round($subtotalAmount * ($taxPercentage / 100), 2);
                $set('tax_amount', $taxAmount);
                $matchedFields[] = 'Subtotal';
            }

            if ($parsed['total_amount'] !== null) {
                $set('total_amount', round($parsed['total_amount']));
                $matchedFields[] = 'Total Tagihan';
            } elseif ($subtotalAmount !== null) {
                $taxAmount = round($subtotalAmount * ($taxPercentage / 100), 2);
                $set('total_amount', round($subtotalAmount + $taxAmount));
            }

            if (! empty($matchedFields)) {
                Notification::make()
                    ->title('OCR berhasil memproses faktur')
                    ->body('Data terisi otomatis: '.implode(', ', $matchedFields).'.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('OCR selesai, namun kata kunci tidak cocok')
                    ->body('Periksa pengaturan Kata Kunci OCR pada Master Supplier.')
                    ->warning()
                    ->send();
            }
        } catch (Throwable $exception) {
            Log::error('OCR Scan failed: '.$exception->getMessage(), [
                'file_path' => $filePath,
                'supplier_id' => $supplierId,
                'exception' => $exception,
            ]);

            Notification::make()
                ->title('Gagal menjalankan OCR Scanner')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function resolveUploadedFilePath(mixed $fileState): ?string
    {
        if ($fileState instanceof TemporaryUploadedFile) {
            return $fileState->getRealPath() ?: null;
        }

        if (is_array($fileState)) {
            $firstFile = Arr::first($fileState);

            return $firstFile ? self::resolveUploadedFilePath($firstFile) : null;
        }

        if (is_string($fileState) && $fileState !== '') {
            if (is_file($fileState)) {
                return $fileState;
            }

            foreach (['public', 'local'] as $disk) {
                if (Storage::disk($disk)->exists($fileState)) {
                    return Storage::disk($disk)->path($fileState);
                }
            }
        }

        return null;
    }

    public static function resolveTesseractExecutable(): string
    {
        foreach (['/opt/homebrew/bin/tesseract', '/usr/local/bin/tesseract', '/usr/bin/tesseract'] as $candidatePath) {
            if (is_executable($candidatePath)) {
                return $candidatePath;
            }
        }

        return 'tesseract';
    }

    public static function extractOcrText(string $filePath, ?string $mimeType = null, int $psm = 4): string
    {
        $resolvedMimeType = $mimeType ?: (mime_content_type($filePath) ?: '');

        if ($resolvedMimeType === 'application/pdf') {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        }

        if (in_array($resolvedMimeType, ['image/jpeg', 'image/png'], true)) {
            return (new TesseractOCR($filePath))
                ->executable(self::resolveTesseractExecutable())
                ->psm($psm)
                ->run();
        }

        return '';
    }

    /**
     * @return array{
     *     invoice_number: ?string,
     *     issue_date: ?string,
     *     due_date: ?string,
     *     subtotal_amount: ?float,
     *     total_amount: ?float,
     *     items: list<array{item_name: string, quantity: int, unit: string, unit_price: float, subtotal: float}>
     * }
     */
    public static function parseOcrInvoiceData(string $text, Supplier $supplier): array
    {
        $invoiceNumber = null;
        $issueDate = null;
        $dueDate = null;
        $subtotalAmount = null;
        $totalAmount = null;

        if ($supplier->ocr_invoice_keyword) {
            $pattern = '/'.self::buildFlexibleKeywordPattern($supplier->ocr_invoice_keyword).'[\s\W]*([A-Za-z0-9\-\/]+)/iu';
            if (preg_match($pattern, $text, $matches)) {
                $invoiceNumber = trim($matches[1]);
            }
        }

        if ($supplier->ocr_date_keyword) {
            $pattern = '/'.self::buildFlexibleKeywordPattern($supplier->ocr_date_keyword).'[\s\W]*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/iu';
            if (preg_match($pattern, $text, $matches)) {
                $issueDate = self::parseIndonesianDate(trim($matches[1]));
                if ($issueDate !== null && $supplier->top_days > 0) {
                    $dueDate = date('Y-m-d', strtotime($issueDate.' + '.$supplier->top_days.' days'));
                }
            }
        }

        if ($supplier->ocr_subtotal_keyword) {
            $pattern = '/'.self::buildFlexibleKeywordPattern($supplier->ocr_subtotal_keyword).'[\s:\|\-\=\(\[\{\}]*(?:Rp\.?\s*)?(\d[\d\.,]*)/iu';
            if (preg_match($pattern, $text, $matches)) {
                $parsedSubtotal = self::parseIndonesianAmount($matches[1]);
                if ($parsedSubtotal > 0) {
                    $subtotalAmount = $parsedSubtotal;
                }
            }
        }

        if ($supplier->ocr_total_keyword) {
            $pattern = '/'.self::buildFlexibleKeywordPattern($supplier->ocr_total_keyword).'[\s:\|\-\=\(\[\{\}]*(?:Rp\.?\s*)?(\d[\d\.,]*)/iu';
            if (preg_match($pattern, $text, $matches)) {
                $parsedTotal = self::parseIndonesianAmount($matches[1]);
                if ($parsedTotal > 0) {
                    $totalAmount = $parsedTotal;
                }
            }
        }

        return [
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal_amount' => $subtotalAmount,
            'total_amount' => $totalAmount,
            'items' => self::parseOcrLineItems($text),
        ];
    }

    /**
     * @return list<array{item_name: string, quantity: int, unit: string, unit_price: float, subtotal: float}>
     */
    public static function parseOcrLineItems(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $items = [];
        $skipKeywords = '/\b(sub\s*total|subtotal|total\s*faktur|total\s*harga|grand\s*total|total\s*keseluruhan|ppn|diskon|biaya\s*kirim|terbilang|npwp|jatuh\s*tempo|tgl\s*faktur|no\.?\s*faktur|kode\s*barang|deskripsi\s*barang|nama\s*barang|harga\s*satuan|account|bank\s*central)\b/iu';

        $pattern = '/^'
            .'(?:\d{1,3}\s*[\|\.\)]\s*|\d{1,3}\s+)?'
            .'(?:[A-Za-z0-9_]{2,}-[A-Za-z0-9\-_]+\s*\|?\s*|[A-Z0-9_]{3,}\s*\|\s*)?'
            .'(.+?)'
            .'[\s\|]+'
            .'(\d{1,6})'
            .'(?:[\s\|]+(?!Rp\b)([A-Za-z]{2,10}))?'
            .'[\s\|]+'
            .'(?:Rp\.?\s*)?(\d[\d\.,]*)'
            .'(?:[\s\|]+\]?(?:\d{1,2}(?:[\.,]\d{1,2})?%?|[A-Za-z])\]?)?'
            .'[\s\|\]\[]+'
            .'(?:Rp\.?\s*)?(\d[\d\.,]*)'
            .'[\s\|\]]*$/iu';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '' || preg_match($skipKeywords, $trimmedLine)) {
                continue;
            }

            $normalizedLine = preg_replace('/(\d)\s+([,\.]\d{2}\b)/u', '$1$2', $trimmedLine) ?? $trimmedLine;

            if (! preg_match($pattern, $normalizedLine, $matches)) {
                continue;
            }

            $itemName = trim($matches[1], " \t|.-_[]");
            $quantity = (int) $matches[2];
            $unit = ! empty($matches[3]) ? $matches[3] : 'Pcs';
            $unitPrice = self::parseIndonesianAmount($matches[4]);
            $subtotal = self::parseIndonesianAmount($matches[5]);

            if ($itemName === '' || ! preg_match('/[A-Za-z]/u', $itemName) || $quantity <= 0 || $unitPrice <= 0 || $subtotal <= 0) {
                continue;
            }

            $items[] = [
                'item_name' => $itemName,
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        return $items;
    }

    public static function buildFlexibleKeywordPattern(string $keyword): string
    {
        $cleaned = rtrim(trim($keyword), " \t:|");
        $quoted = preg_quote($cleaned, '/');
        $quoted = preg_replace('/\\\\\s+/u', '\\s*', $quoted) ?? $quoted;

        return str_replace('\\.', '\\.?', $quoted);
    }

    public static function parseIndonesianDate(string $raw): ?string
    {
        $cleaned = trim($raw);
        if ($cleaned === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $cleaned, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $monthReplacements = [
            'Januari' => 'Jan',
            'Februari' => 'Feb',
            'Pebruari' => 'Feb',
            'Peb' => 'Feb',
            'Maret' => 'Mar',
            'April' => 'Apr',
            'Mei' => 'May',
            'Juni' => 'Jun',
            'Juli' => 'Jul',
            'Agustus' => 'Aug',
            'Ags' => 'Aug',
            'Agu' => 'Aug',
            'September' => 'Sep',
            'Oktober' => 'Oct',
            'Okt' => 'Oct',
            'November' => 'Nov',
            'Nopember' => 'Nov',
            'Nop' => 'Nov',
            'Desember' => 'Dec',
            'Des' => 'Dec',
        ];

        $normalized = str_ireplace(array_keys($monthReplacements), array_values($monthReplacements), $cleaned);
        $timestamp = strtotime($normalized);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    public static function parseIndonesianAmount(string $raw): float
    {
        $cleaned = preg_replace('/(\d)\s+([,\.]\d{2}\b)/u', '$1$2', trim($raw)) ?? trim($raw);
        $cleaned = preg_replace('/[^0-9,\.]/', '', $cleaned) ?? '';
        $cleaned = trim($cleaned, '.,');

        if ($cleaned === '') {
            return 0.0;
        }

        if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
            if (strrpos($cleaned, ',') > strrpos($cleaned, '.')) {
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                $cleaned = str_replace(',', '', $cleaned);
            }

            return (float) $cleaned;
        }

        if (str_contains($cleaned, ',')) {
            if (preg_match('/,\d{1,2}$/', $cleaned) && substr_count($cleaned, ',') === 1) {
                return (float) str_replace(',', '.', $cleaned);
            }

            return (float) str_replace(',', '', $cleaned);
        }

        if (str_contains($cleaned, '.')) {
            if (substr_count($cleaned, '.') > 1 || preg_match('/\.\d{3}$/', $cleaned)) {
                return (float) str_replace('.', '', $cleaned);
            }

            return (float) $cleaned;
        }

        return (float) $cleaned;
    }

    public static function updateItemSubtotal(Forms\Set $set, Forms\Get $get): void
    {
        $qty = (float) $get('quantity') ?: 0;
        $price = self::parseIndonesianAmount((string) $get('unit_price'));
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
