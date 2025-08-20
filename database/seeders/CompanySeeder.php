<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create default company
            $company = Company::firstOrCreate(
                ['name' => 'PT CESA Indonesia'],
                [
                    'legal_name' => 'PT Complete Enterprise Solution Application',
                    'tax_id' => '00.000.000.0-000.000',
                    'registration_number' => 'REG-2025-001',
                    'email' => 'info@cesa.co.id',
                    'phone' => '021-12345678',
                    'mobile' => '08123456789',
                    'website' => 'https://cesa.co.id',
                    'address' => 'Jl. Sudirman No. 1',
                    'city' => 'Jakarta',
                    'state' => 'DKI Jakarta',
                    'country' => 'Indonesia',
                    'postal_code' => '12190',
                    'description' => 'Default company for CESA ERP System',
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'date_format' => 'd/m/Y',
                    'fiscal_year_start' => now()->startOfYear(),
                    'is_active' => true,
                    'creator_id' => User::first()?->id,
                ]
            );

            // If there are existing users without company, attach them to default company
            $usersWithoutCompany = User::whereDoesntHave('companies')->get();
            
            foreach ($usersWithoutCompany as $user) {
                // Attach user to company
                $company->users()->attach($user->id, [
                    'role' => $user->hasRole('super_admin') ? 'admin' : 'member',
                    'is_active' => true,
                ]);
                
                // Set as default company if user doesn't have one
                if (!$user->default_company_id) {
                    $user->update(['default_company_id' => $company->id]);
                }
            }

            $this->command->info('✅ Default company created: ' . $company->name);
            $this->command->info('✅ Attached ' . $usersWithoutCompany->count() . ' users to default company');
        });
    }
}
