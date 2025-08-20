<?php

namespace App\Filament\Exports;

use App\Models\Company;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CompanyExporter extends Exporter
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Company Name'),
            ExportColumn::make('legal_name')
                ->label('Legal Name'),
            ExportColumn::make('tax_id')
                ->label('Tax ID'),
            ExportColumn::make('registration_number')
                ->label('Registration Number'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Phone'),
            ExportColumn::make('mobile')
                ->label('Mobile'),
            ExportColumn::make('fax')
                ->label('Fax'),
            ExportColumn::make('website')
                ->label('Website'),
            ExportColumn::make('address')
                ->label('Address'),
            ExportColumn::make('city')
                ->label('City'),
            ExportColumn::make('state')
                ->label('State'),
            ExportColumn::make('country')
                ->label('Country'),
            ExportColumn::make('postal_code')
                ->label('Postal Code'),
            ExportColumn::make('currency')
                ->label('Currency'),
            ExportColumn::make('timezone')
                ->label('Timezone'),
            ExportColumn::make('is_active')
                ->label('Active Status'),
            ExportColumn::make('created_at')
                ->label('Created Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your company export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
