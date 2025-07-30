<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Informasi Aktivitas')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('log_name')
                                            ->label('Jenis Log')
                                            ->badge(),
                                        TextEntry::make('event')
                                            ->label('Event')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'created' => 'success',
                                                'updated' => 'warning',
                                                'deleted' => 'danger',
                                                'restored' => 'info',
                                                default => 'gray'
                                            }),
                                    ]),
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Section::make('Detail Subjek')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('subject_type')
                                            ->label('Tipe Subjek')
                                            ->formatStateUsing(fn ($state) => class_basename($state)),
                                        TextEntry::make('subject_id')
                                            ->label('ID Subjek')
                                            ->copyable(),
                                    ]),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Informasi Pengguna')
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label('Nama Pengguna')
                                    ->default('System'),
                                TextEntry::make('causer.email')
                                    ->label('Email Pengguna')
                                    ->default('-')
                                    ->copyable(),
                                TextEntry::make('created_at')
                                    ->label('Waktu Aktivitas')
                                    ->dateTime()
                                    ->since(),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(3)
                    ->schema([
                        Section::make('Perubahan Data')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        KeyValueEntry::make('properties.old')
                                            ->label('Data Sebelum Perubahan')
                                            ->helperText('Data yang ada sebelum aktivitas ini dilakukan')
                                            ->keyLabel('Field')
                                            ->valueLabel('Nilai Sebelumnya')
                                            ->visible(fn($record) => isset($record->properties['old']) && !empty($record->properties['old'])),

                                        KeyValueEntry::make('properties.attributes')
                                            ->label('Data Setelah Perubahan')
                                            ->helperText('Data yang tersimpan setelah aktivitas ini selesai')
                                            ->keyLabel('Field')
                                            ->valueLabel('Nilai Terbaru')
                                            ->visible(fn($record) => isset($record->properties['attributes']) && !empty($record->properties['attributes'])),
                                    ]),

                                // Tampilkan full properties jika tidak ada old/attributes
                                KeyValueEntry::make('properties')
                                    ->label('Semua Data Properties')
                                    ->helperText('Seluruh informasi properties yang tersimpan dalam log ini')
                                    ->keyLabel('Property')
                                    ->valueLabel('Nilai')
                                    ->visible(fn($record) => (!isset($record->properties['old']) && !isset($record->properties['attributes'])) && !empty($record->properties)),
                                ]),
                    ]),
            ])->columns(3);
    }
}
