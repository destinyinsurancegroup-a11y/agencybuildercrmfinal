<?php

namespace App\Models\Concerns;

use App\Helpers\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    /**
     * Boot the tenant scoping for the model.
     *
     * Applies a global scope so all queries are filtered by agency_id,
     * and sets agency_id automatically when creating new records.
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Only apply in authenticated tenant contexts
            if (Tenant::active()) {
                $table = $builder->getModel()->getTable();
                $builder->where($table . '.agency_id', Tenant::id());
            }
        });

        static::creating(function ($model) {
            if (Tenant::active() && empty($model->agency_id)) {
                $model->agency_id = Tenant::id();
            }
        });
    }

    /**
     * Local scope if ever needed manually.
     */
    public function scopeForCurrentAgency(Builder $builder): Builder
    {
        if (Tenant::active()) {
            $table = $builder->getModel()->getTable();
            $builder->where($table . '.agency_id', Tenant::id());
        }

        return $builder;
    }
}
