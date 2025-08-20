<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\RichEditor;


class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label('Tax ID (NPWP)')
                            ->maxLength(255),
                        TextInput::make('registration_number')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                    ])
                    ->columns(2),
                    
                Section::make('Contact')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('mobile')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('fax')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->url()
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Section::make('Address')
                    ->schema([
                        RichEditor::make('address')
                            ->columnSpanFull(),
                        TextInput::make('city')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->default('Indonesia')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->maxLength(20),
                    ])
                    ->columns(2),
                    
                Section::make('Settings')
                    ->schema([
                        Select::make('currency')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'SGD' => 'SGD - Singapore Dollar',
                            ])
                            ->required()
                            ->default('IDR'),
                        Select::make('timezone')
                            ->options([
                                'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                                'Asia/Makassar' => 'Asia/Makassar (WITA)',
                                'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                            ])
                            ->required()
                            ->default('Asia/Jakarta'),
                        Select::make('date_format')
                            ->options([
                                'd/m/Y' => 'd/m/Y',
                                'Y-m-d' => 'Y-m-d',
                                'm/d/Y' => 'm/d/Y',
                            ])
                            ->required()
                            ->default('d/m/Y'),
                        DatePicker::make('fiscal_year_start'),
                        FileUpload::make('logo')
                            ->image()
                            ->directory('companies/logos')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
