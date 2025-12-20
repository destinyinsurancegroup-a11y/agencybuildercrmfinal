<?php

namespace App\Models\Concerns;

use App\Helpers\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Backward-compatible tenant scope:
 * - Prefer tenant_id if column exists
 * - Else fallback to agency_id if column exists
 *
 * This prevents 500 errors while the codebase is transitioning.
 */
class TenantScope implements Scope
{
    /**
     * Cache table->tenantColumn to avoid Schema::hasColumn calls on every query.
     * @var array<string, string|null>
     */
    protected static array $tenantColumnCache = [];

    public static function tenantColumnFor(Model $model): ?string
    {
        $table = $model->getTable();

        if (array_key_exists($table, self::$tenantColumnCache)) {
            return self::$tenantColumnCache[$table];
        }

        // Prefer tenant_id
        if (Schema::hasColumn($table, 'tenant_id')) {
            return self::$tenantColumnCache[$table] = 'tenant_id';
        }

        // Backward compatibility fallback
        if (Schema::hasColumn($table, 'agency_id')) {
            return self::$tenantColumnCache[$table] = 'agency_id';
        }

        return self::$tenantColumnCache[$table] = null;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Tenant::id();
        if (! $tenantId) return;

        $col = self::tenantColumnFor($model);
        if (! $col) return;

        $builder->where($model->getTable() . '.' . $col, $tenantId);
    }
}

trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            $tenantId = Tenant::id();
            if (! $tenantId) return;

            $col = TenantScope::tenantColumnFor($model);
            if (! $col) return;

            if (empty($model->{$col})) {
                $model->{$col} = $tenantId;
            }
        });
    }
}
