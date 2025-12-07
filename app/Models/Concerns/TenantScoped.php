<?php

namespace App\Models\Concerns;

use App\Helpers\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgencyScope implements Scope
{
    /**
     * Apply the agency (tenant) constraint to all queries.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Tenant::id();

        if ($tenantId) {
            $builder->where($model->getTable() . '.agency_id', $tenantId);
        }
    }
}

trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        // Add the global scope to every query on this model
        static::addGlobalScope(new AgencyScope);

        // When creating a new record, automatically set agency_id
        static::creating(function (Model $model) {
            if (!$model->agency_id && ($tenantId = Tenant::id())) {
                $model->agency_id = $tenantId;
            }
        });
    }
}
