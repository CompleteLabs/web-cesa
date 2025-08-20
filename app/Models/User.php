<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Enums\PermissionType;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'resource_permission',
        'default_company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'resource_permission' => PermissionType::class,
        ];
    }

    /**
     * Get the default company for the user.
     */
    public function defaultCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'default_company_id');
    }

    /**
     * The companies that the user belongs to.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * Get all company IDs that the user has access to
     */
    public function getCompanyIds(): array
    {
        return $this->companies->pluck('id')->toArray();
    }

    /**
     * Check if user has access to a specific company
     */
    public function hasAccessToCompany($companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    /**
     * Check if user belongs to the same company as another user
     */
    public function belongsToSameCompany(User $otherUser): bool
    {
        // Check if users share any company
        $userCompanyIds = $this->companies->pluck('id');
        $otherUserCompanyIds = $otherUser->companies->pluck('id');
        
        return $userCompanyIds->intersect($otherUserCompanyIds)->isNotEmpty();
    }

    /**
     * Check if user has global permission
     */
    public function hasGlobalPermission(): bool
    {
        return $this->resource_permission === PermissionType::GLOBAL;
    }

    /**
     * Check if user has group permission
     */
    public function hasGroupPermission(): bool
    {
        return $this->resource_permission === PermissionType::GROUP;
    }

    /**
     * Check if user has individual permission
     */
    public function hasIndividualPermission(): bool
    {
        return $this->resource_permission === PermissionType::INDIVIDUAL;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return is_null($this->deleted_at);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'password', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getCustomDescription($eventName));
    }

    protected function getCustomDescription(string $eventName): string
    {
        $userIdentifier = $this->email ?? "ID#{$this->id}" ?? 'Unknown User';
        $userName = $this->name ?? 'Unknown User';

        return match($eventName) {
            'created' => "Pengguna baru {$userIdentifier} ({$userName}) berhasil didaftarkan",
            'updated' => "Profil {$userIdentifier} telah diperbarui",
            'deleted' => "Pengguna {$userIdentifier} dihapus dari sistem",
            'restored' => "Pengguna {$userIdentifier} dikembalikan ke sistem",
            default => "Aktivitas {$eventName} dilakukan pada {$userIdentifier}",
        };
    }
}
