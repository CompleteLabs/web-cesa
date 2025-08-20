<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        
        // Load companies relationship
        $data['companies'] = $this->record->companies()->pluck('companies.id')->toArray();
        
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

        // Remove companies from data as it will be handled separately
        unset($data['companies']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Handle company assignments
        $formData = $this->form->getState();
        
        DB::transaction(function () use ($formData) {
            // Sync companies with pivot data
            if (isset($formData['companies'])) {
                $companyData = [];
                foreach ($formData['companies'] as $companyId) {
                    $companyData[$companyId] = [
                        'role' => 'member', // Default role, could be made configurable
                        'is_active' => true,
                    ];
                }
                $this->record->companies()->sync($companyData);
            } else {
                // If no companies selected, detach all
                $this->record->companies()->detach();
            }

            // Ensure default company is in the assigned companies
            if ($this->record->default_company_id && isset($formData['companies'])) {
                if (!in_array($this->record->default_company_id, $formData['companies'])) {
                    // If default company is not in the list, add it
                    $this->record->companies()->attach($this->record->default_company_id, [
                        'role' => 'member',
                        'is_active' => true,
                    ]);
                }
            }
            
            // If default company is removed from companies, clear it
            if ($this->record->default_company_id && !isset($formData['companies'])) {
                $this->record->update(['default_company_id' => null]);
            } elseif ($this->record->default_company_id && isset($formData['companies']) && !in_array($this->record->default_company_id, $formData['companies'])) {
                // Set first company as default if current default is not in the list
                $this->record->update(['default_company_id' => $formData['companies'][0] ?? null]);
            }
        });

        Notification::make()
            ->title('User updated successfully')
            ->success()
            ->send();
    }
}
