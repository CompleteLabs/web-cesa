<?php

namespace Cesa\Contact\Filament\Resources\ContactResource\Pages;

use Cesa\Contact\Filament\Resources\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign user_id if not global admin
        if (!auth()->user()->hasGlobalPermission()) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
