<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

class CreateCustomPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cesa:permissions 
                           {--all : Create all custom permissions}
                           {--export-import : Create Export/Import permissions}
                           {--horizon : Create Horizon permissions}
                           {--assign-to-super-admin : Assign permissions to super_admin role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create custom permissions for Export/Import and Horizon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creating Custom Permissions...');
        $this->newLine();

        $options = [];
        
        // Determine what to create based on options
        if ($this->option('all')) {
            $options = ['export-import', 'horizon'];
        } else {
            // Interactive selection if no specific options provided
            if (!$this->option('export-import') && !$this->option('horizon')) {
                $selected = multiselect(
                    'Which custom permissions do you want to create?',
                    [
                        'export-import' => 'Export/Import permissions',
                        'horizon' => 'Horizon permissions',
                    ],
                    default: ['export-import', 'horizon']
                );
                $options = $selected;
            } else {
                if ($this->option('export-import')) {
                    $options[] = 'export-import';
                }
                if ($this->option('horizon')) {
                    $options[] = 'horizon';
                }
            }
        }

        $createdPermissions = [];

        // Create Export/Import permissions
        if (in_array('export-import', $options)) {
            $exportImportPermissions = $this->createExportImportPermissions();
            $createdPermissions = array_merge($createdPermissions, $exportImportPermissions);
        }

        // Create Horizon permissions
        if (in_array('horizon', $options)) {
            $horizonPermissions = $this->createHorizonPermissions();
            $createdPermissions = array_merge($createdPermissions, $horizonPermissions);
        }

        // Assign to super_admin role if requested
        if ($this->option('assign-to-super-admin') || 
            (!empty($createdPermissions) && confirm('Do you want to assign these permissions to super_admin role?', true))) {
            $this->assignToSuperAdmin($createdPermissions);
        }

        $this->newLine();
        $this->info('🎉 Custom permissions created successfully!');
        $this->info('📊 Total permissions created: ' . count($createdPermissions));
        
        return Command::SUCCESS;
    }

    /**
     * Create Export/Import permissions
     */
    protected function createExportImportPermissions(): array
    {
        $this->info('🔍 Detecting Export/Import capabilities...');
        
        $permissions = [];
        
        // Detect exporters
        $exportPermissions = $this->detectExportCapabilities();
        foreach ($exportPermissions as $permission) {
            $perm = Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]);
            $permissions[] = $perm;
            $this->line("   ✅ Created: {$permission['name']} ({$permission['class']})");
        }
        
        // Detect importers
        $importPermissions = $this->detectImportCapabilities();
        foreach ($importPermissions as $permission) {
            $perm = Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]);
            $permissions[] = $perm;
            $this->line("   ✅ Created: {$permission['name']} ({$permission['class']})");
        }
        
        $this->info('✅ Export/Import permissions created: ' . count($permissions));
        
        return $permissions;
    }

    /**
     * Detect Export capabilities
     */
    protected function detectExportCapabilities(): array
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
                }
            }
        }
        
        return $permissions;
    }
    
    /**
     * Detect Import capabilities
     */
    protected function detectImportCapabilities(): array
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
                }
            }
        }
        
        return $permissions;
    }

    /**
     * Create Horizon permissions
     */
    protected function createHorizonPermissions(): array
    {
        $this->info('🔭 Creating Horizon permissions...');
        
        $permissions = [];
        
        $horizonPermissions = [
            [
                'name' => 'horizon::view',
                'description' => 'View Horizon dashboard'
            ],
            [
                'name' => 'horizon::manage',
                'description' => 'Manage Horizon jobs'
            ],
            [
                'name' => 'horizon::pause',
                'description' => 'Pause Horizon workers'
            ],
            [
                'name' => 'horizon::continue',
                'description' => 'Continue Horizon workers'
            ],
            [
                'name' => 'horizon::terminate',
                'description' => 'Terminate Horizon workers'
            ],
            [
                'name' => 'horizon::retry',
                'description' => 'Retry failed jobs'
            ],
            [
                'name' => 'horizon::delete',
                'description' => 'Delete jobs'
            ],
        ];
        
        foreach ($horizonPermissions as $horizonPermission) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $horizonPermission['name'],
                    'guard_name' => 'web',
                ]
            );
            
            $permissions[] = $permission;
            $this->line("   ✅ Created: {$horizonPermission['name']} - {$horizonPermission['description']}");
        }
        
        $this->info('✅ Horizon permissions created: ' . count($permissions));
        
        return $permissions;
    }

    /**
     * Assign permissions to super_admin role
     */
    protected function assignToSuperAdmin(array $permissions): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'super_admin']);
        
        foreach ($permissions as $permission) {
            $adminRole->givePermissionTo($permission);
        }
        
        $this->info('✅ Permissions assigned to super_admin role');
    }
}
