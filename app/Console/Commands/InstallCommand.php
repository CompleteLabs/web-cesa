<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install
                           {--fresh : Run fresh migration (WARNING: This will drop all tables)}
                           {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and setup application with database, permissions, and super admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Application Installation...');

        // Check if we're in production
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Cannot run in production without --force flag');
            return 1;
        }

        $this->newLine();
        $this->info('📋 Setup Overview:');
        $this->line('   • Database migration');
        $this->line('   • Permissions & roles setup');
        $this->line('   • Super admin user creation');
        $this->line('   • Filament Shield configuration');
        $this->line('   • Auto-detect Export/Import capabilities');
        $this->newLine();

        if (!$this->confirm('Do you want to continue?', true)) {
            $this->info('Installation cancelled.');
            return 0;
        }

        try {
            // Step 1: Database Migration
            $this->step1_DatabaseMigration();

            // Step 2: Custom Permissions
            $this->step2_CreateCustomPermissions();

            // Step 3: Setup Roles
            $this->step3_SetupRoles();

            // Step 4: Generate Shield Permissions
            $this->step4_GenerateShieldPermissions();

            // Step 5: Auto-detect Export/Import
            $this->step5_DetectExportImport();

            // Step 6: Create Super Admin
            $this->step6_CreateSuperAdmin();

            // Step 7: Final Setup
            $this->step7_FinalSetup();

            $this->displaySuccessMessage();
        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function step1_DatabaseMigration()
    {
        $this->info('📊 Step 1: Database Migration');

        if ($this->option('fresh')) {
            $this->warn('⚠️  Running fresh migration - all data will be lost!');
            if (!$this->confirm('Are you sure?', false)) {
                throw new \Exception('Installation cancelled by user');
            }
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            Artisan::call('migrate', ['--force' => true]);
        }

        $this->info('✅ Database migration completed');
    }

    private function step2_CreateCustomPermissions()
    {
        $this->info('🔑 Step 2: Creating Custom Permissions');

        $permissions = [
            'view_horizon' => 'Access to Laravel Horizon',
            'view_telescope' => 'Access to Laravel Telescope',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $this->line("   • Created permission: {$name}");
        }

        $this->info('✅ Custom permissions created');
    }

    private function step3_SetupRoles()
    {
        $this->info('👥 Step 3: Setting up Roles');

        $roles = [
            'super_admin' => 'Full access to all features',
            'user' => 'Basic user access',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $this->line("   • Created role: {$roleName}");
        }

        $this->info('✅ Roles setup completed');
    }

    private function step4_GenerateShieldPermissions()
    {
        $this->info('🛡️  Step 4: Generating Filament Shield Permissions');

        // Run shield generate command
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin'
        ]);

        // Assign all permissions to super admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::all();
            $superAdminRole->syncPermissions($allPermissions);
            $this->line("   • Assigned {$allPermissions->count()} permissions to super_admin");
        }

        $this->info('✅ Shield permissions generated and assigned');
    }

    private function step5_DetectExportImport()
    {
        $this->info('🔍 Step 5: Auto-detecting Export/Import Capabilities');

        // Run our custom detection command
        Artisan::call('shield:detect-export-import', [
            '--assign-to-super-admin' => true
        ]);

        // Get the output from the command
        $output = Artisan::output();

        // Parse and display relevant information
        $this->displayDetectionResults($output);

        $this->info('✅ Export/Import permissions detected and configured');
    }

    private function displayDetectionResults($output)
    {
        // Extract useful information from detection command output
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_contains($line, 'Found exporter:') || str_contains($line, 'Found importer:')) {
                $this->line("   • " . $line);
            }
        }
    }

    private function step6_CreateSuperAdmin()
    {
        $this->info('👑 Step 6: Creating Super Admin User');

        // Email validation loop
        $email = $this->askValidEmail();
        $name = $this->ask('Super Admin Name', 'Super Admin');

        // Generate secure password
        $password = $this->secret('Super Admin Password (leave empty for auto-generated)');
        if (empty($password)) {
            $password = Str::random(12);
            $this->warn("Generated password: {$password}");
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Assign super admin role
        $user->assignRole('super_admin');

        $this->info('✅ Super Admin created successfully');
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Name', $name],
                ['Password', $password],
                ['Login URL', url('/admin')]
            ]
        );
    }

    private function askValidEmail($question = 'Super Admin Email', $default = 'admin@example.com')
    {
        while (true) {
            $email = $this->ask($question, $default);
            $email = strtolower(trim($email));

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error("❌ Please enter a valid email address.");
                continue;
            }

            $this->info("✅ Email '{$email}' is valid");
            return $email;
        }
    }
    private function step7_FinalSetup()
    {
        $this->info('🔧 Step 7: Final Setup');

        if (app()->environment('production')) {
            // Production: Optimize everything for performance
            $this->line('   • Optimizing for production...');

            Artisan::call('optimize');
            $this->line('   • Laravel optimization completed');

            Artisan::call('filament:optimize');
            $this->line('   • Filament optimization completed');

        } else {
            // Development: Clear caches to avoid issues
            $this->line('   • Clearing caches for development...');

            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            $this->line('   • Development caches cleared');
        }

        $this->info('✅ Application setup completed');
    }

    private function displaySuccessMessage()
    {
        $this->newLine(2);
        $this->info('🎉 Application Installation Completed Successfully!');
        $this->newLine();

        $this->info('📋 Installation Summary:');
        $this->line('   ✅ Database migrated');
        $this->line('   ✅ Custom permissions created');
        $this->line('   ✅ Roles configured');
        $this->line('   ✅ Shield permissions generated');
        $this->line('   ✅ Export/Import capabilities detected');
        $this->line('   ✅ Super admin user created');
        $this->line('   ✅ Application caches updated');

        $this->newLine();
        $this->info('🔗 Quick Links:');
        $this->line('   • Admin Panel: ' . url('/admin'));
        $this->line('   • Horizon: ' . url('/' . config('horizon.path', 'horizon')));
        $this->line('   • Telescope: ' . url('/' . config('telescope.path', 'telescope')));

        $this->newLine();
        $this->info('📖 Next Steps:');
        $this->line('   1. Login to admin panel with super admin credentials');
        $this->line('   2. Create additional users and assign roles');
        $this->line('   3. Configure application settings as needed');
        $this->line('   4. Start monitoring with Horizon and Telescope');

        $this->newLine();
        $this->info('🚀 Your application is ready to use!');
    }
}
