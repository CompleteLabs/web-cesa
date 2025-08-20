<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PermissionType;
use App\Models\User;

trait HasScopedPermissions
{
    /**
     * Check if the user has global access to any resource.
     */
    protected function hasGlobalAccess(User $user): bool
    {
        return $user->resource_permission === PermissionType::GLOBAL->value;
    }

    /**
     * Check if the user has group access to resources of users in the same companies.
     */
    protected function hasGroupAccess(User $user, Model $model, string $ownerAttribute = 'user'): bool
    {
        if ($user->resource_permission !== PermissionType::GROUP->value) {
            return false;
        }

        $owner = $model->{$ownerAttribute};

        if (!$owner) {
            return false;
        }

        // Get user's company IDs
        $userCompanyIds = $user->companies()->pluck('companies.id');
        
        // If user has no companies, no group access
        if ($userCompanyIds->isEmpty()) {
            return false;
        }

        if ($owner instanceof Collection) {
            // If the user is one of the owners
            if ($owner->pluck('id')->contains($user->id)) {
                return true;
            }

            // Check if any of the owners share companies with the user
            foreach ($owner as $singleOwner) {
                $ownerCompanyIds = $singleOwner->companies()->pluck('companies.id');
                if ($userCompanyIds->intersect($ownerCompanyIds)->isNotEmpty()) {
                    return true;
                }
            }
            return false;
        } else {
            // If the user is the owner
            if ($owner->id === $user->id) {
                return true;
            }

            // Check if owner shares any company with the user
            $ownerCompanyIds = $owner->companies()->pluck('companies.id');
            return $userCompanyIds->intersect($ownerCompanyIds)->isNotEmpty();
        }
    }

    /**
     * Check if the user has individual access to their own resources only.
     */
    protected function hasIndividualAccess(User $user, Model $model, $ownerAttribute = 'user'): bool
    {
        if ($user->resource_permission !== PermissionType::INDIVIDUAL->value) {
            return false;
        }

        $owner = $model->{$ownerAttribute};

        if (!$owner) {
            return false;
        }

        return $owner instanceof Collection
            ? $owner->pluck('id')->contains($user->id)
            : $owner->id === $user->id;
    }

    /**
     * Main access method that combines all permissions.
     */
    public function hasAccess(User $user, Model $model, string $ownerAttribute = 'user'): bool
    {
        return $this->hasGlobalAccess($user)
            || $this->hasGroupAccess($user, $model, $ownerAttribute)
            || $this->hasIndividualAccess($user, $model, $ownerAttribute);
    }

    /**
     * Check if user can view the model
     */
    public function canView(User $user, Model $model, string $ownerAttribute = 'user'): bool
    {
        return $this->hasAccess($user, $model, $ownerAttribute);
    }

    /**
     * Check if user can update the model
     */
    public function canUpdate(User $user, Model $model, string $ownerAttribute = 'user'): bool
    {
        // For update, typically only owner or global admin can update
        if ($this->hasGlobalAccess($user)) {
            return true;
        }

        $owner = $model->{$ownerAttribute};
        
        if (!$owner) {
            return false;
        }

        return $owner instanceof Collection
            ? $owner->pluck('id')->contains($user->id)
            : $owner->id === $user->id;
    }

    /**
     * Check if user can delete the model
     */
    public function canDelete(User $user, Model $model, string $ownerAttribute = 'user'): bool
    {
        // Same as update permission for delete
        return $this->canUpdate($user, $model, $ownerAttribute);
    }

    /**
     * Apply permission scope to a query
     */
    public function scopeWithPermissions($query, string $ownerRelation = 'user')
    {
        return $query->withGlobalScope('permission', new \App\Scopes\UserPermissionScope($ownerRelation));
    }
}
