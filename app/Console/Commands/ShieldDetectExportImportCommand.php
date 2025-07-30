<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldDetectExportImportCommand extends Command
{
    protected $signature = 'shield:detect-export-import
                           {--assign-to-super-admin : Assign detected permissions to super_admin role}';

    protected $description = 'Detect Export/Import classes and create permissions automatically';

    public function handle()
    {
        $this->info('🔍 Detecting Export/Import capabilities...');

        $exportPermissions = $this->detectExportCapabilities();
        $importPermissions = $this->detectImportCapabilities();

        $this->createPermissions($exportPermissions, 'export');
        $this->createPermissions($importPermissions, 'import');

        if ($this->option('assign-to-super-admin')) {
            $this->assignToSuperAdmin($exportPermissions, $importPermissions);
        }

        $this->displaySummary($exportPermissions, $importPermissions);
    }

    private function detectExportCapabilities(): array
    {
        $permissions = [];
        $exportersPath = app_path('Filament/Exports');

        if (!File::exists($exportersPath)) {
            return $permissions;
        }

        $files = File::files($exportersPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $className = $file->getFilenameWithoutExtension();

                // Extract resource name from exporter class name
                // Example: UserExporter -> User -> users::user (following Shield convention)
                if (str_ends_with($className, 'Exporter')) {
                    $resourceName = str_replace('Exporter', '', $className);
                    $modelName = strtolower($resourceName);
                    $resourcePlural = strtolower(str($resourceName)->plural());

                    // Follow Shield convention: export_users::user
                    $permissionName = "export_{$resourcePlural}::{$modelName}";

                    $permissions[] = [
                        'name' => $permissionName,
                        'class' => $className,
                        'resource' => $resourceName,
                        'model' => $modelName
                    ];

                    $this->line("   • Found exporter: {$className} -> {$permissionName}");
                }
            }
        }

        return $permissions;
    }

    private function detectImportCapabilities(): array
    {
        $permissions = [];
        $importersPath = app_path('Filament/Imports');

        if (!File::exists($importersPath)) {
            return $permissions;
        }

        $files = File::files($importersPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $className = $file->getFilenameWithoutExtension();

                // Extract resource name from importer class name
                // Example: UserImporter -> User -> users::user (following Shield convention)
                if (str_ends_with($className, 'Importer')) {
                    $resourceName = str_replace('Importer', '', $className);
                    $modelName = strtolower($resourceName);
                    $resourcePlural = strtolower(str($resourceName)->plural());

                    // Follow Shield convention: import_users::user
                    $permissionName = "import_{$resourcePlural}::{$modelName}";

                    $permissions[] = [
                        'name' => $permissionName,
                        'class' => $className,
                        'resource' => $resourceName,
                        'model' => $modelName
                    ];

                    $this->line("   • Found importer: {$className} -> {$permissionName}");
                }
            }
        }

        return $permissions;
    }

    private function createPermissions(array $permissions, string $type): void
    {
        if (empty($permissions)) {
            $this->line("   • No {$type} capabilities detected");
            return;
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]);
        }

        $this->info("✅ Created {$type} permissions");
    }

    private function assignToSuperAdmin(array $exportPermissions, array $importPermissions): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();

        if (!$superAdmin) {
            $this->warn('⚠️  Super admin role not found');
            return;
        }

        $allPermissions = array_merge($exportPermissions, $importPermissions);
        $permissionNames = array_column($allPermissions, 'name');

        foreach ($permissionNames as $permissionName) {
            $superAdmin->givePermissionTo($permissionName);
        }

        $this->info("✅ Assigned permissions to super_admin role");
    }

    private function displaySummary(array $exportPermissions, array $importPermissions): void
    {
        $this->newLine();
        $this->info('📋 Detection Summary:');

        $this->table(
            ['Type', 'Permissions Created'],
            [
                ['Export', count($exportPermissions)],
                ['Import', count($importPermissions)],
                ['Total', count($exportPermissions) + count($importPermissions)]
            ]
        );

        if (!empty($exportPermissions)) {
            $this->newLine();
            $this->info('🔄 Export Permissions:');
            foreach ($exportPermissions as $permission) {
                $this->line("   • {$permission['name']} ({$permission['class']})");
            }
        }

        if (!empty($importPermissions)) {
            $this->newLine();
            $this->info('📥 Import Permissions:');
            foreach ($importPermissions as $permission) {
                $this->line("   • {$permission['name']} ({$permission['class']})");
            }
        }

        $this->newLine();
        $this->info('🚀 Detection completed!');
    }
}
