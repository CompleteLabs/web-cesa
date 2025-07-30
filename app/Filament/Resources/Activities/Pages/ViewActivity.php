<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore')
                ->label('Restore Data')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => in_array($this->record->event, ['updated', 'deleted']) &&
                                  $this->record->properties &&
                                  (isset($this->record->properties['old']) || isset($this->record->properties['attributes'])))
                ->requiresConfirmation()
                ->modalHeading('Restore Data dari Log')
                ->modalDescription(function () {
                    $record = $this->record;
                    if ($record->event === 'deleted') {
                        // Cek apakah ini force delete atau soft delete
                        $modelClass = $record->subject_type;
                        $model = $modelClass::withTrashed()->find($record->subject_id);

                        if (!$model) {
                            return "Data telah dihapus permanen. Apakah Anda yakin ingin membuat ulang data ini?";
                        } else {
                            return "Apakah Anda yakin ingin mengembalikan data yang telah dihapus?";
                        }
                    }

                    return "Apakah Anda yakin ingin mengembalikan data ke kondisi sebelum {$record->event}?";
                })
                ->action(function () {
                    try {
                        $record = $this->record;

                        if (!$record->subject_type || !$record->subject_id) {
                            throw new \Exception('Subject tidak ditemukan');
                        }

                        $modelClass = $record->subject_type;

                        if ($record->event === 'deleted') {
                            // Cek apakah model masih ada (soft delete) atau sudah benar-benar terhapus (force delete)
                            $model = $modelClass::withTrashed()->find($record->subject_id);

                            if (!$model) {
                                // Force delete - recreate dari attributes
                                $attributes = $record->properties['attributes'] ?? [];

                                if (empty($attributes)) {
                                    throw new \Exception('Tidak ada data untuk direstore');
                                }

                                // Create new model dengan data lama
                                $model = new $modelClass();
                                $model->fill($attributes);

                                // Force save dengan ID yang sama jika memungkinkan
                                if (isset($attributes['id'])) {
                                    $model->id = $record->subject_id;
                                    $model->exists = false;
                                }

                                $model->save();

                                $message = 'Data berhasil dibuat ulang dari backup log';
                            } else {
                                // Soft delete - restore biasa
                                if (method_exists($model, 'restore')) {
                                    $model->restore();
                                }

                                // Update dengan data lama jika ada
                                $oldProperties = $record->properties['old'] ?? [];
                                if (!empty($oldProperties)) {
                                    $model->update($oldProperties);
                                }

                                $message = 'Data berhasil dikembalikan dari soft delete';
                            }
                        } else {
                            // Untuk updated biasa
                            $model = $modelClass::withTrashed()->find($record->subject_id);

                            if (!$model) {
                                throw new \Exception('Model tidak ditemukan');
                            }

                            $oldProperties = $record->properties['old'] ?? [];

                            if (empty($oldProperties)) {
                                throw new \Exception('Tidak ada data lama untuk direstore');
                            }

                            // Update dengan data lama
                            $model->update($oldProperties);

                            $message = 'Data berhasil dikembalikan ke kondisi sebelumnya';
                        }

                        Notification::make()
                            ->title('Berhasil!')
                            ->body($message)
                            ->success()
                            ->send();

                        // Redirect ke list setelah berhasil restore
                        return redirect()->to(ActivityResource::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal!')
                            ->body('Gagal mengembalikan data: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
