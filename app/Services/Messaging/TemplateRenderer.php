<?php

namespace App\Services\Messaging;

use Illuminate\Database\Eloquent\Model;

final class TemplateRenderer
{
    public function render(string $text, Model $contact, ?Model $agent = null, array $agency = []): string
    {
        $cfg = config('abc_messaging.contact_fields');

        $first = data_get($contact, $cfg['first_name']) ?: 'there';
        $last  = data_get($contact, $cfg['last_name']) ?: '';
        $full  = trim($first . ' ' . $last) ?: 'there';

        $map = [
            '{{first_name}}' => $first,
            '{{last_name}}'  => $last,
            '{{full_name}}'  => $full,
            '{{email}}'      => (string) (data_get($contact, $cfg['email']) ?? ''),
            '{{phone}}'      => (string) (data_get($contact, $cfg['phone']) ?? ''),

            '{{agent_name}}'  => (string) (data_get($agent, 'name') ?? 'your agent'),
            '{{agent_email}}' => (string) (data_get($agent, 'email') ?? ''),
            '{{agent_phone}}' => (string) (data_get($agent, 'phone') ?? ''),

            '{{agency_name}}'  => (string) ($agency['name'] ?? ''),
            '{{agency_email}}' => (string) ($agency['email'] ?? ''),
            '{{agency_phone}}' => (string) ($agency['phone'] ?? ''),
        ];

        return strtr($text, $map);
    }
}
