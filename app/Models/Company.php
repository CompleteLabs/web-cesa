<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Company extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'registration_number',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'logo',
        'description',
        'currency',
        'timezone',
        'date_format',
        'fiscal_year_start',
        'is_active',
        'creator_id',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'fiscal_year_start' => 'date',
        'settings' => 'array',
    ];

    /**
     * Get the creator of the company.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * The users that belong to the company.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Active users in the company.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Users who have this as their default company.
     */
    public function defaultUsers(): HasMany
    {
        return $this->hasMany(User::class, 'default_company_id');
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a user is member of this company.
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if a user is active member of this company.
     */
    public function hasActiveUser(User $user): bool
    {
        return $this->activeUsers()->where('users.id', $user->id)->exists();
    }

    /**
     * Get user's role in this company.
     */
    public function getUserRole(User $user): ?string
    {
        $pivot = $this->users()->where('users.id', $user->id)->first()?->pivot;
        return $pivot?->role;
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'legal_name', 'tax_id', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getCustomDescription($eventName));
    }

    /**
     * Get custom description for activity log.
     */
    protected function getCustomDescription(string $eventName): string
    {
        $companyName = $this->name ?? "ID#{$this->id}";

        return match($eventName) {
            'created' => "Company {$companyName} has been created",
            'updated' => "Company {$companyName} has been updated",
            'deleted' => "Company {$companyName} has been deleted",
            'restored' => "Company {$companyName} has been restored",
            default => "Activity {$eventName} performed on company {$companyName}",
        };
    }
}
