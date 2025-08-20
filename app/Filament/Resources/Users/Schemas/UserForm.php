<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\PermissionType;
use App\Models\Company;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('User Information')
                    ->description('Basic user account information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->nullable(),
                    ])
                    ->columns(2),
                    
                Section::make('Roles & Permissions')
                    ->description('Configure user roles and access permissions')
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Select::make('resource_permission')
                            ->label('Resource Permission Level')
                            ->options(PermissionType::options())
                            ->default(PermissionType::INDIVIDUAL->value)
                            ->required()
                            ->helperText('Determines what data the user can access')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Company Assignment')
                    ->description('Assign user to companies and set default company')
                    ->schema([
                        Select::make('default_company_id')
                            ->label('Default Company')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('The primary company for this user'),
                        Select::make('companies')
                            ->label('Assigned Companies')
                            ->relationship('companies', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('All companies this user has access to')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required(),
                            ])
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required(),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }
}
