<?php

namespace App\Filament\Resources\Users\Schemas;

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
                                            ->label('Name'),
                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->copyable(),
                                    ]),
                            ]),

                        Section::make('Account Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Joined')
                                            ->dateTime()
                                            ->since(),
                                        TextEntry::make('updated_at')
                                            ->label('Last Updated')
                                            ->dateTime()
                                            ->since(),
                                    ]),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime()
                                    ->placeholder('Not verified'),

                                TextEntry::make('roles.name')
                                    ->label('Roles')
                                    ->badge(),

                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime()
                                    ->placeholder('Active')
                                    ->color('danger')
                                    ->visible(fn($record) => !is_null($record->deleted_at)),
                            ]),
                    ]),
            ])->columns(3);
    }
}
