<?php

namespace App\Filament\Resources\Companies;

use App\Enums\PermissionType;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Companies\Schemas\CompanyInfolist;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static string | UnitEnum | null $navigationGroup = 'System';
    
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query()->where('is_active', true);
        
        // Apply scope for navigation badge count
        $query = static::scopeQuery($query);
        
        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
            
        return static::scopeQuery($query);
    }
    
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    
    /**
     * Apply scope based on user's permission level
     */
    protected static function scopeQuery(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            // No user logged in, return empty result
            return $query->whereRaw('1 = 0');
        }
        
        // Super admin can see all companies
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        
        // Check resource permission level
        switch ($user->resource_permission) {
            case PermissionType::GLOBAL:
                // Can see all companies
                return $query;
                
            case PermissionType::GROUP:
            case PermissionType::INDIVIDUAL:
                // Can only see companies they belong to
                $companyIds = $user->companies()->pluck('companies.id')->toArray();
                
                if (empty($companyIds)) {
                    // User has no companies, return empty result
                    return $query->whereRaw('1 = 0');
                }
                
                return $query->whereIn('id', $companyIds);
                
            default:
                // Default to no access
                return $query->whereRaw('1 = 0');
        }
    }
    
    /**
     * Check if user can view a specific company
     */
    public static function canView(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check permission level
        switch ($user->resource_permission) {
            case PermissionType::GLOBAL:
                return true;
                
            case PermissionType::GROUP:
            case PermissionType::INDIVIDUAL:
                // Can view if user belongs to this company
                return $user->hasAccessToCompany($record->id);
                
            default:
                return false;
        }
    }
    
    /**
     * Check if user can create companies
     */
    public static function canCreate(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Only super admin and global permission users can create companies
        return $user->hasRole('super_admin') || $user->resource_permission === PermissionType::GLOBAL;
    }
    
    /**
     * Check if user can edit a specific company
     */
    public static function canEdit(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check permission level
        switch ($user->resource_permission) {
            case PermissionType::GLOBAL:
                return true;
                
            case PermissionType::GROUP:
                // Can edit if user belongs to this company and has appropriate role
                if (!$user->hasAccessToCompany($record->id)) {
                    return false;
                }
                // Check if user has admin role in this company
                $pivot = $user->companies()->where('companies.id', $record->id)->first();
                return $pivot && $pivot->pivot->role === 'admin';
                
            case PermissionType::INDIVIDUAL:
            default:
                // Cannot edit companies
                return false;
        }
    }
    
    /**
     * Check if user can delete a specific company  
     */
    public static function canDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Only super admin can delete companies
        return $user->hasRole('super_admin');
    }
    
    /**
     * Check if user can restore a specific company
     */
    public static function canRestore(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Only super admin can restore companies
        return $user->hasRole('super_admin');
    }
    
    /**
     * Check if user can force delete a specific company
     */
    public static function canForceDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Only super admin can force delete companies
        return $user->hasRole('super_admin');
    }
}
