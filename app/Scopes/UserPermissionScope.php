<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use App\Enums\PermissionType;

class UserPermissionScope implements Scope
{
    protected $ownerRelation;

    /**
     * Create a new scope instance.
     */
    public function __construct(string $ownerRelation = 'user')
    {
        $this->ownerRelation = $ownerRelation;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user) {
            // If no user, show nothing
            $builder->where('id', '<', 0);
            return;
        }

        // Global permission - can see everything
        if ($user->resource_permission === PermissionType::GLOBAL->value) {
            return;
        }

        // Individual permission - can see only own resources
        if ($user->resource_permission === PermissionType::INDIVIDUAL->value) {
            $builder->whereHas($this->ownerRelation, function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

            // Also include records where user is a follower/participant
            if ($model->getTable() !== 'users') {
                $builder->orWhereHas('followers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
        }

        // Group permission - can see resources of users in the same companies
        if ($user->resource_permission === PermissionType::GROUP->value) {
            // Get all company IDs that user has access to
            $companyIds = $user->companies()->pluck('companies.id');

            if ($companyIds->isNotEmpty()) {
                // Get resources owned by users in the same companies
                $builder->whereHas("$this->ownerRelation", function ($q) use ($companyIds) {
                    $q->whereHas('companies', function ($q2) use ($companyIds) {
                        $q2->whereIn('companies.id', $companyIds);
                    });
                });
            } else {
                // If user has no companies, can only see own resources
                $builder->whereHas($this->ownerRelation, function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
        }
    }
}
