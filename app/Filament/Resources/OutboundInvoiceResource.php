<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboundInvoiceResource\Pages;
use App\Models\OutboundInvoice;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OutboundInvoiceResource extends Resource
{
    protected static ?string $model = OutboundInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Customer')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Customer')
                            ->relationship('client', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                // Auto set dates when client is selected
                                if (! $get('issue_date')) {
                                    $set('issue_date', now()->format('Y-m-d'));
                                }
                                if (! $get('due_date')) {
                                    // Default due date +30 days
                                    $set('due_date', now()->addDays(30)->format('Y-m-d'));
                                }
                                // Auto generate invoice number JL-YMD-RAND
                                if (! $get('invoice_number')) {
                                    $set('invoice_number', 'JL'.now()->format('ymd').rand(10, 99));
                                }
                            }),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('No Faktur')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Tanggal')
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
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
                        Forms\Components\TextInput::make('sales')
                            ->label('Sales')
                            ->placeholder('Contoh: Microbiologi, Diagnostic 2, NTB 2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('division')
                            ->label('Divisi')
                            ->placeholder('Contoh: Reagen, Alkes, Laboratorium')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Daftar Barang (Items)')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship() // Requires relationship `items` in OutboundInvoice model
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
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemSubtotal($set, $get))
                                    ->columnSpan(1),
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
                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('Diskon (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemSubtotal($set, $get))
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->readOnly()
                                    ->columnSpan(2),
                            ])
                            ->columns(10)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                    ]),

                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_amount')
                            ->label('Subtotal')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('PPN (%)')
                            ->numeric()
                            ->default(11)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                        Forms\Components\Hidden::make('tax_amount')->default(0),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Tagihan')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function updateItemSubtotal(Forms\Set $set, Forms\Get $get)
    {
        $qty = (float) $get('quantity') ?: 0;
        $price = (float) $get('unit_price') ?: 0;
        $discount = (float) $get('discount_percentage') ?: 0;

        $subtotal = $qty * $price * (1 - ($discount / 100));
        $set('subtotal', round($subtotal));
    }

    public static function updateTotals(Forms\Set $set, Forms\Get $get)
    {
        $items = $get('items') ?? [];
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales')
                    ->label('Sales')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                Tables\Columns\TextColumn::make('subtotal_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (OutboundInvoice $record) => route('outbound-invoice.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('surat_jalan')
                    ->label('Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->url(fn (OutboundInvoice $record) => route('surat-jalan.pdf', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListOutboundInvoices::route('/'),
            'create' => Pages\CreateOutboundInvoice::route('/create'),
            'edit' => Pages\EditOutboundInvoice::route('/{record}/edit'),
        ];
    }
}
