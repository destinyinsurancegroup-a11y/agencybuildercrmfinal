<?php

namespace App\Services\Messaging;

use App\Models\Contact;
use App\Models\User;

class TemplateVariableResolver
{
    /**
     * Replace {{variables}} in subject/body using Contact + User.
     */
    public static function resolve(string $text, Contact $contact, User $user): string
    {
        $fullName = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));

        $replacements = [
            '{{first_name}}'  => $contact->first_name ?? '',
            '{{last_name}}'   => $contact->last_name ?? '',
            '{{full_name}}'   => $contact->full_name ?? $fullName,
            '{{phone}}'       => $contact->phone ?? '',
            '{{email}}'       => $contact->email ?? '',
            '{{agent_name}}'  => $user->name ?? '',
            '{{agency_name}}' => $user->agency_name ?? '', // change if you have an Agency relation
        ];

        // Replace known variables
        $out = str_replace(array_keys($replacements), array_values($replacements), $text);

        // Optional: remove any unreplaced {{...}} tokens to avoid showing raw placeholders
        // Comment this out if you want to keep unknown variables visible for debugging
        $out = preg_replace('/\{\{\s*[^}]+\s*\}\}/', '', $out);

        return trim($out);
    }
}
