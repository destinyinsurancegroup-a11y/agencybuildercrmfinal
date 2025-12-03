<?php

namespace App\Models;

/**
 * Legacy alias for Note.
 *
 * This model exists only for backwards compatibility with any
 * old code that still references ContactNote. It uses the same
 * table, fillable fields, relationships, and tenant/agency
 * scoping as the core Note model.
 */
class ContactNote extends Note
{
    // No extra code needed – everything is inherited from Note.
}
