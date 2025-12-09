<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Lead model
 *
 * This is a thin wrapper around Contact:
 * - Uses the same "contacts" table
 * - Automatically filters to rows where contact_type = 'Lead'
 *
 * From Gideon's point of view, this is "the leads drawer"
 * even though the data lives in the contacts table.
 */
class Lead extends Contact
{
    /**
     * Optionally override the table name just to be explicit.
     * (Contact already uses 'contacts', but this makes it clear.)
     */
    protected $table = 'contacts';

    /**
     * Add a global scope so any query on Lead only returns leads.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('only_leads', function (Builder $query) {
            $query->where('contact_type', 'Lead');
        });
    }
}
