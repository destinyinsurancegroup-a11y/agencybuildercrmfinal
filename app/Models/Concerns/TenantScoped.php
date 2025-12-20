<?php

namespace App\Models\Concerns;

use App\Helpers\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global tenant scope using tenant_id.
 *
 * IMPORTANT:
 * - This must match your DB schema. Your activities migration uses tenant_id.
 * - Tenant::id() must return the current tenant's ID.
 */
class TenantScope implements Scope
{
    /**
     * Apply the tenant constraint to all queries.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Tenant::id();

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        // Add global tenant scope to every query on this model
        static::addGlobalScope(new TenantScope);

        // Auto-set tenant_id on creation
        static::creating(function (Model $model) {
            if (
                empty($model->tenant_id) &&
                ($tenantId = Tenant::id())
            ) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
