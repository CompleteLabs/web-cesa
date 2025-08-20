<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Company;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hash password sebelum menyimpan
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Remove companies from data as it will be handled after creation
        unset($data['companies']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Handle company assignments
        $formData = $this->form->getState();
        
        if (!empty($formData['companies'])) {
            // Attach companies to the user
            $companyData = [];
            foreach ($formData['companies'] as $companyId) {
                $companyData[$companyId] = [
                    'role' => 'member', // Default role
                    'is_active' => true,
                ];
            }
            $this->record->companies()->attach($companyData);
        }

        // If default company is set and not in companies list, add it
        if ($this->record->default_company_id && !empty($formData['companies'])) {
            if (!in_array($this->record->default_company_id, $formData['companies'])) {
                $this->record->companies()->attach($this->record->default_company_id, [
                    'role' => 'member',
                    'is_active' => true,
                ]);
            }
        }
        
        // Auto-assign default company if not set but companies are assigned
        if (!$this->record->default_company_id && !empty($formData['companies'])) {
            $this->record->update([
                'default_company_id' => $formData['companies'][0]
            ]);
        }

        Notification::make()
            ->title('User created successfully')
            ->success()
            ->send();
    }
}
