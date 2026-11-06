<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua Divisi')
                ->badge(fn (): int => Product::count()),
        ];

        foreach (Product::divisionOptions() as $divisionKey => $divisionLabel) {
            $tabs[$divisionKey] = Tab::make($divisionLabel)
                ->badge(fn (): int => Product::where('division', $divisionKey)->count())
                ->badgeColor(Product::divisionColor($divisionKey))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('division', $divisionKey));
        }

        return $tabs;
    }
}
