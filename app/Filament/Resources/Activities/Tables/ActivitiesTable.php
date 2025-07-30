<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Jenis Log')
                    ->badge()
                    ->searchable(),

                TextColumn::make('event')
                    ->label('Jenis Event')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray'
                    }),

                TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Subjek')
                    ->formatStateUsing(fn($state) => class_basename($state)),

                TextColumn::make('causer.name')
                    ->label('Pengguna')
                    ->default('System'),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Tipe Log')
                    ->options(\Spatie\Activitylog\Models\Activity::distinct()->pluck('log_name', 'log_name')->toArray()),

                SelectFilter::make('subject_type')
                    ->label('Tipe Subjek')
                    ->options(function () {
                        $types = \Spatie\Activitylog\Models\Activity::distinct()
                            ->pluck('subject_type', 'subject_type')
                            ->mapWithKeys(fn($value, $key) => [$key => class_basename($key)])
                            ->toArray();
                        return $types;
                    }),

                SelectFilter::make('event')
                    ->label('Jenis Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                Filter::make('created_at')
                    ->label('Rentang Waktu')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}
