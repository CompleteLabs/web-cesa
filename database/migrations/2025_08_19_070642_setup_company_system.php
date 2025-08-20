<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create companies table
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('legal_name')->nullable();
                $table->string('tax_id')->nullable();
                $table->string('registration_number')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('mobile')->nullable();
                $table->string('fax')->nullable();
                $table->string('website')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('logo')->nullable();
                $table->text('description')->nullable();
                $table->string('currency', 3)->default('IDR');
                $table->string('timezone')->default('Asia/Jakarta');
                $table->string('date_format')->default('d/m/Y');
                $table->date('fiscal_year_start')->nullable();
                $table->json('settings')->nullable();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes for better performance
                $table->index('is_active');
                $table->index('creator_id');
                $table->index('tax_id');
                $table->index('name');
            });
        }

        // Add default_company_id to users table if not exists
        if (!Schema::hasColumn('users', 'default_company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('default_company_id')
                    ->nullable()
                    ->after('resource_permission')
                    ->constrained('companies')
                    ->nullOnDelete();
                    
                $table->index('default_company_id');
            });
        }

        // Create company_user pivot table for many-to-many relationship
        if (!Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role')->default('member'); // member, manager, admin
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                // Ensure unique combination of company and user
                $table->unique(['company_id', 'user_id']);
                
                // Add indexes for better query performance
                $table->index('company_id');
                $table->index('user_id');
                $table->index('role');
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop company_user pivot table
        Schema::dropIfExists('company_user');
        
        // Remove default_company_id from users table
        if (Schema::hasColumn('users', 'default_company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['default_company_id']);
                $table->dropColumn('default_company_id');
            });
        }
        
        // Drop companies table
        Schema::dropIfExists('companies');
    }
};
