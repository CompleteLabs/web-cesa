<?php

namespace App\Filament\Imports;

use App\Models\Company;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CompanyImporter extends Importer
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('PT Example Company'),
            ImportColumn::make('legal_name')
                ->rules(['max:255'])
                ->example('PT Example Company Indonesia'),
            ImportColumn::make('tax_id')
                ->rules(['max:100'])
                ->example('12.345.678.9-012.000'),
            ImportColumn::make('registration_number')
                ->rules(['max:100']),
            ImportColumn::make('email')
                ->rules(['email', 'max:255'])
                ->example('info@company.com'),
            ImportColumn::make('phone')
                ->rules(['max:50'])
                ->example('+62 21 1234567'),
            ImportColumn::make('mobile')
                ->rules(['max:50']),
            ImportColumn::make('fax')
                ->rules(['max:50']),
            ImportColumn::make('website')
                ->rules(['url', 'max:255'])
                ->example('https://www.company.com'),
            ImportColumn::make('address')
                ->rules(['max:500']),
            ImportColumn::make('city')
                ->rules(['max:100'])
                ->example('Jakarta'),
            ImportColumn::make('state')
                ->rules(['max:100']),
            ImportColumn::make('country')
                ->rules(['max:100'])
                ->example('Indonesia'),
            ImportColumn::make('postal_code')
                ->rules(['max:20'])
                ->example('12345'),
            ImportColumn::make('currency')
                ->rules(['max:10'])
                ->example('IDR'),
            ImportColumn::make('timezone')
                ->rules(['max:50'])
                ->example('Asia/Jakarta'),
            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['boolean'])
                ->example('true'),
        ];
    }

    public function resolveRecord(): ?Company
    {
        // Check if company already exists by name
        if ($this->data['name'] ?? false) {
            return Company::firstOrNew([
                'name' => $this->data['name'],
            ]);
        }

        return new Company();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your company import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
