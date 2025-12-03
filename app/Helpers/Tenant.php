<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class Tenant
{
    /**
     * Get the current tenant (agency) ID, or null if not authenticated.
     */
    public static function id(): ?int
    {
        $user = Auth::user();

        if ($user instanceof User) {
            return $user->agency_id;
        }

        return null;
    }

    /**
     * Get the current authenticated user, or null.
     */
    public static function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * True if we have an authenticated user with an agency.
     */
    public static function active(): bool
    {
        return static::id() !== null;
    }
}
