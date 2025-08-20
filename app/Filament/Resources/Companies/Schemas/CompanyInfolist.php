<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextEntry::make('name')
                            ->weight('bold'),
                        TextEntry::make('legal_name'),
                        TextEntry::make('tax_id'),
                        TextEntry::make('registration_number'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Contact')
                    ->schema([
                        TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->icon('heroicon-m-phone'),
                        TextEntry::make('mobile'),
                        TextEntry::make('fax'),
                        TextEntry::make('website')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Address')
                    ->schema([
                        TextEntry::make('address')
                            ->columnSpanFull(),
                        TextEntry::make('city'),
                        TextEntry::make('state'),
                        TextEntry::make('country')
                            ->badge(),
                        TextEntry::make('postal_code'),
                    ])
                    ->columns(2),
                    
                Section::make('Settings')
                    ->schema([
                        ImageEntry::make('logo')
                            ->circular()
                            ->size(100),
                        TextEntry::make('currency')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('timezone'),
                        TextEntry::make('date_format'),
                        TextEntry::make('fiscal_year_start')
                            ->date(),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->label('Status'),
                    ])
                    ->columns(2),
            ]);
    }
}
