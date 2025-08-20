<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\PermissionType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('User Information')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Name')
                                            ->weight('bold'),
                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable(),
                                    ]),
                            ]),

                        Section::make('Company Assignment')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('defaultCompany.name')
                                            ->label('Default Company')
                                            ->badge()
                                            ->color('primary')
                                            ->placeholder('No default company'),
                                        TextEntry::make('companies.name')
                                            ->label('Assigned Companies')
                                            ->badge()
                                            ->separator(', ')
                                            ->placeholder('No companies assigned'),
                                    ]),
                            ]),

                        Section::make('Permissions & Access')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('roles.name')
                                            ->label('Roles')
                                            ->badge()
                                            ->color('warning'),
                                        TextEntry::make('resource_permission')
                                            ->label('Resource Permission')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                PermissionType::GLOBAL->value => 'success',
                                                PermissionType::GROUP->value => 'info',
                                                PermissionType::INDIVIDUAL->value => 'gray',
                                                default => 'gray'
                                            })
                                            ->formatStateUsing(fn ($state) => match($state) {
                                                PermissionType::GLOBAL->value => 'Global Access',
                                                PermissionType::GROUP->value => 'Group Access',
                                                PermissionType::INDIVIDUAL->value => 'Individual Access',
                                                default => $state
                                            }),
                                    ]),
                            ]),

                        Section::make('Account Details')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Joined')
                                            ->dateTime()
                                            ->since(),
                                        TextEntry::make('updated_at')
                                            ->label('Last Updated')
                                            ->dateTime()
                                            ->since(),
                                        TextEntry::make('email_verified_at')
                                            ->label('Email Verified')
                                            ->dateTime()
                                            ->placeholder('Not verified')
                                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                                    ]),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Account Status')
                            ->schema([
                                IconEntry::make('email_verified_at')
                                    ->label('Email Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-badge')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('warning'),
                                    
                                IconEntry::make('deleted_at')
                                    ->label('Account Active')
                                    ->boolean()
                                    ->getStateUsing(fn ($record) => is_null($record->deleted_at))
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-trash')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                            
                        Section::make('Activity')
                            ->schema([
                                TextEntry::make('companies_count')
                                    ->label('Total Companies')
                                    ->getStateUsing(fn ($record) => $record->companies()->count())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('last_login')
                                    ->label('Last Login')
                                    ->placeholder('Never logged in')
                                    ->dateTime()
                                    ->since(),
                            ])
                            ->collapsible(),
                    ]),
            ])->columns(3);
    }
}
