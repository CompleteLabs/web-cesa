<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\ExportAction;
use App\Filament\Actions\ImportAction;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(UserExporter::class),
            ImportAction::make()
                ->importer(UserImporter::class),
        ];
    }
}
