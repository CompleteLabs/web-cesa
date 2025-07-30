<?php

namespace App\Filament\Actions;

use Filament\Actions\ExportAction as BaseExportAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExportAction extends BaseExportAction
{
    public static function make(?string $name = 'export'): static
    {
        $static = parent::make($name);

        return $static;
    }

    public function exporter(string $exporter): static
    {
        parent::exporter($exporter);

        // Configure permission after exporter is set
        $this->configureAutoPermission($exporter);

        return $this;
    }

    protected function configureAutoPermission(string $exporterClass): void
    {
        // Extract resource name from exporter class
        // Example: App\Filament\Exports\UserExporter -> UserExporter -> User -> users::user
        $className = class_basename($exporterClass);

        if (str_ends_with($className, 'Exporter')) {
            $resourceName = str_replace('Exporter', '', $className);
            $modelName = strtolower($resourceName);
            $resourcePlural = strtolower(Str::plural($resourceName));

            // Follow Shield convention: export_users::user
            $permissionName = "export_{$resourcePlural}::{$modelName}";

            // Auto-configure visibility and authorization
            $this->visible(fn () => Auth::user()?->can($permissionName) ?? false);
            $this->authorize(fn () => Auth::user()?->can($permissionName) ?? false);

            // Set default label if not already set
            if (!$this->getLabel()) {
                $this->label("Export " . ucfirst($resourcePlural));
            }
        }
    }
}
