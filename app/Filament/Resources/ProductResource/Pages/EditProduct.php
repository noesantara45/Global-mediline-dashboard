<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['photo'])) {
            $filename = basename($data['photo']);
            $disk = Storage::disk('public');

            if (! $disk->exists('products/'.$filename)) {
                $publicFile = public_path('images/products/'.$filename);
                if (file_exists($publicFile)) {
                    $disk->put('products/'.$filename, file_get_contents($publicFile));
                }
            }

            if (! $disk->exists($filename)) {
                $publicFile = public_path('images/products/'.$filename);
                if (file_exists($publicFile)) {
                    $disk->put($filename, file_get_contents($publicFile));
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['photo']) && ! empty($data['name'])) {
            $data['photo'] = $this->syncAndCleanPhoto($data['photo'], $data['name']);
        } elseif (empty($data['photo']) && ! empty($this->record->photo)) {
            $data['photo'] = $this->record->photo;
        }

        return $data;
    }

    protected function syncAndCleanPhoto(string $photoPath, string $productName): string
    {
        $extension = pathinfo($photoPath, PATHINFO_EXTENSION) ?: 'png';
        $cleanFilename = Str::slug($productName).'.'.strtolower($extension);

        $disk = Storage::disk('public');

        if ($disk->exists($photoPath)) {
            $disk->copy($photoPath, $cleanFilename);
            $disk->copy($photoPath, 'products/'.$cleanFilename);

            try {
                $realPath = $disk->path($photoPath);
                if (file_exists($realPath)) {
                    $publicDir = public_path('images/products');
                    File::ensureDirectoryExists($publicDir);
                    File::copy($realPath, $publicDir.'/'.$cleanFilename);
                }
            } catch (\Throwable $e) {
                // Ignore for virtual/fake test disks
            }

            if ($photoPath !== $cleanFilename && $photoPath !== 'products/'.$cleanFilename) {
                $disk->delete($photoPath);
            }
        } elseif ($disk->exists('products/'.$photoPath)) {
            $disk->copy('products/'.$photoPath, $cleanFilename);
        }

        return $cleanFilename;
    }
}
