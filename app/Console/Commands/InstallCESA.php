<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCESA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cesa:install 
                           {--skip-migrations : Skip database migrations}
                           {--skip-permissions : Skip roles and permissions generation}
                           {--skip-company : Skip company setup}
                           {--skip-admin : Skip admin user creation}
                           {--with-seeders : Run database seeders}
                           {--detect-export-import : Detect and create Export/Import permissions}
                           {--with-horizon : Create Horizon permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install CESA ERP System with initial setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting CESA ERP System Installation...');
        $this->newLine();

        // Step 1: Run migrations
        if (confirm('Do you want to run database migrations?', true)) {
            $this->info('⚙️  Running database migrations...');
            Artisan::call('migrate', [], $this->getOutput());
            $this->info('✅ Migrations completed successfully.');
            $this->newLine();
        }

        // Step 2: Generate roles and permissions
        if (!$this->option('skip-permissions') && confirm('Do you want to generate roles and permissions?', true)) {
            $this->info('🛡️  Generating roles and permissions...');
            
            // Create super_admin role if not exists
            $adminRole = Role::firstOrCreate(['name' => 'super_admin']);
            
            // Generate Shield permissions
            Artisan::call('shield:generate', ['--all' => true], $this->getOutput());
            
            // Detect and create Export/Import permissions if requested
            if ($this->option('detect-export-import') || confirm('Do you want to detect and create Export/Import permissions?', true)) {
                $this->detectAndCreateExportImportPermissions($adminRole);
            }
            
            // Create Horizon permissions if requested
            if ($this->option('with-horizon') || confirm('Do you want to create Horizon permissions?', false)) {
                $this->createHorizonPermissions($adminRole);
            }
            
            $this->info('✅ Roles and permissions generated successfully.');
            $this->newLine();
        }

        // Step 3: Create storage link
        if (confirm('Do you want to create storage link?', true)) {
            if (!file_exists(public_path('storage'))) {
                $this->info('🔗 Creating storage link...');
                Artisan::call('storage:link', [], $this->getOutput());
                $this->info('✅ Storage link created successfully.');
            } else {
                $this->info('ℹ️  Storage link already exists.');
            }
            $this->newLine();
        }

        // Step 4: Setup Company
        if (!$this->option('skip-company') && confirm('Do you want to setup a company?', true)) {
            $this->setupCompany();
            $this->newLine();
        }

        // Step 5: Create Admin User
        if (!$this->option('skip-admin') && confirm('Do you want to create an admin user?', true)) {
            $this->createAdminUser();
            $this->newLine();
        }

        // Step 6: Run seeders
        if ($this->option('with-seeders') || confirm('Do you want to run database seeders?', false)) {
            $this->info('🌱 Running database seeders...');
            Artisan::call('db:seed', [], $this->getOutput());
            $this->info('✅ Seeders completed successfully.');
            $this->newLine();
        }

        $this->info('🎉 CESA ERP System installation completed successfully!');
        $this->info('📌 You can now login at: ' . url('/admin'));
        
        return Command::SUCCESS;
    }

    /**
     * Setup company
     */
    protected function setupCompany(): void
    {
        $this->info('🏢 Setting up company...');
        
        $setupType = select(
            'How would you like to setup the company?',
            [
                'default' => 'Use default company (PT CESA Indonesia)',
                'custom' => 'Create custom company',
                'skip' => 'Skip company setup'
            ],
            'default'
        );

        if ($setupType === 'skip') {
            return;
        }

        DB::transaction(function () use ($setupType) {
            if ($setupType === 'default') {
                // Run CompanySeeder
                Artisan::call('db:seed', ['--class' => 'CompanySeeder'], $this->getOutput());
            } else {
                // Create custom company
                $companyData = [
                    'name' => text(
                        'Company name',
                        required: true
                    ),
                    'legal_name' => text(
                        'Legal name (optional)'
                    ),
                    'tax_id' => text(
                        'Tax ID/NPWP (optional)'
                    ),
                    'email' => text(
                        'Company email',
                        default: 'info@company.com',
                        validate: fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email format'
                    ),
                    'phone' => text(
                        'Company phone (optional)'
                    ),
                    'address' => text(
                        'Company address (optional)'
                    ),
                    'city' => text(
                        'City',
                        default: 'Jakarta'
                    ),
                    'country' => text(
                        'Country',
                        default: 'Indonesia'
                    ),
                    'currency' => select(
                        'Currency',
                        ['IDR' => 'IDR', 'USD' => 'USD', 'EUR' => 'EUR'],
                        'IDR'
                    ),
                    'timezone' => select(
                        'Timezone',
                        [
                            'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                            'Asia/Makassar' => 'Asia/Makassar (WITA)',
                            'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                        ],
                        'Asia/Jakarta'
                    ),
                    'is_active' => true,
                    'creator_id' => User::first()?->id,
                ];

                $company = Company::create($companyData);
                $this->info("✅ Company '{$company->name}' created successfully.");
            }
        });
    }

    /**
     * Create admin user
     */
    protected function createAdminUser(): void
    {
        $this->info('👤 Creating admin user...');
        
        DB::transaction(function () {
            $userData = [
                'name' => text(
                    'Admin name',
                    default: 'Administrator',
                    required: true
                ),
                'email' => text(
                    'Admin email',
                    default: 'admin@cesa.com',
                    required: true,
                    validate: function ($email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            return 'Invalid email format';
                        }
                        if (User::where('email', $email)->exists()) {
                            return 'Email already exists';
                        }
                        return null;
                    }
                ),
                'password' => Hash::make(
                    password(
                        'Admin password',
                        required: true,
                        validate: fn ($value) => strlen($value) >= 8 ? null : 'Password must be at least 8 characters'
                    )
                ),
                'resource_permission' => select(
                    'Admin permission level',
                    [
                        'global' => 'Global - Access all data',
                        'group' => 'Group - Access company data',
                        'individual' => 'Individual - Access own data only'
                    ],
                    'global'
                ),
            ];

            // Get default company
            $company = Company::first();
            if ($company) {
                $userData['default_company_id'] = $company->id;
            }

            // Create user
            $user = User::create($userData);

            // Assign super_admin role
            $user->assignRole('super_admin');

            // Attach to company if exists
            if ($company) {
                $company->users()->attach($user->id, [
                    'role' => 'admin',
                    'is_active' => true,
                ]);
            }

            $this->info("✅ Admin user '{$user->name}' created successfully.");
        });
    }

    /**
     * Detect and create Export/Import permissions
     */
    protected function detectAndCreateExportImportPermissions(Role $adminRole): void
    {
        $this->info('🔍 Detecting Export/Import capabilities...');
        
        $exportPermissions = $this->detectExportCapabilities();
        $importPermissions = $this->detectImportCapabilities();
        
        $this->createPermissions($exportPermissions, 'export');
        $this->createPermissions($importPermissions, 'import');
        
        // Assign to super_admin role
        $allPermissions = array_merge($exportPermissions, $importPermissions);
        $permissionNames = array_column($allPermissions, 'name');
        
        foreach ($permissionNames as $permissionName) {
            $adminRole->givePermissionTo($permissionName);
        }
        
        $this->displayExportImportSummary($exportPermissions, $importPermissions);
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
                    
                    $this->line("   • Found exporter: {$className} -> {$permissionName}");
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
                    
                    $this->line("   • Found importer: {$className} -> {$permissionName}");
                }
            }
        }
        
        return $permissions;
    }
    
    /**
     * Create permissions
     */
    protected function createPermissions(array $permissions, string $type): void
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
    
    /**
     * Display Export/Import summary
     */
    protected function displayExportImportSummary(array $exportPermissions, array $importPermissions): void
    {
        $this->newLine();
        $this->info('📋 Export/Import Detection Summary:');
        
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
    }
    
    /**
     * Create Horizon permissions
     */
    protected function createHorizonPermissions(Role $adminRole): void
    {
        $this->info('🔭 Creating Horizon permissions...');
        
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
            
            // Assign to super_admin role
            $adminRole->givePermissionTo($permission);
            
            $this->line("   • Created permission: {$horizonPermission['name']} - {$horizonPermission['description']}");
        }
        
        $this->info('✅ Horizon permissions created and assigned to super_admin role.');
    }
}
