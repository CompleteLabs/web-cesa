<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->hidden(fn () => $this->record->getKey() === Auth::id()),
            ForceDeleteAction::make()
                ->hidden(fn () => $this->record->getKey() === Auth::id()),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Kosongkan password field saat editing
        $data['password'] = '';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hash password jika diubah, atau hapus jika kosong
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Action setelah user diupdate
        // Misalnya: kirim notifikasi, log activity, dll
    }
}
