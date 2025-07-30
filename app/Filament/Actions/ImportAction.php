<?php

namespace App\Filament\Actions;

use Filament\Actions\ImportAction as BaseImportAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ImportAction extends BaseImportAction
{
    public static function make(?string $name = 'import'): static
    {
        $static = parent::make($name);

        return $static;
    }

    public function importer(string $importer): static
    {
        parent::importer($importer);

        // Configure permission after importer is set
        $this->configureAutoPermission($importer);

        return $this;
    }

    protected function configureAutoPermission(string $importerClass): void
    {
        // Extract resource name from importer class
        // Example: App\Filament\Imports\UserImporter -> UserImporter -> User -> users::user
        $className = class_basename($importerClass);

        if (str_ends_with($className, 'Importer')) {
            $resourceName = str_replace('Importer', '', $className);
            $modelName = strtolower($resourceName);
            $resourcePlural = strtolower(Str::plural($resourceName));

            // Follow Shield convention: import_users::user
            $permissionName = "import_{$resourcePlural}::{$modelName}";

            // Auto-configure visibility and authorization
            $this->visible(fn () => Auth::user()?->can($permissionName) ?? false);
            $this->authorize(fn () => Auth::user()?->can($permissionName) ?? false);

            // Set default label if not already set
            if (!$this->getLabel()) {
                $this->label("Import " . ucfirst($resourcePlural));
            }
        }
    }
}
