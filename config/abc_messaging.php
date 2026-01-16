<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact Field Mapping (safe defaults)
    |--------------------------------------------------------------------------
    | If your contacts table uses different column names, update these values.
    */

    'contact_fields' => [
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'email'      => 'email',
        'phone'      => 'phone',

        // Optional date fields for milestone drips:
        'birthday'     => 'birthday',        // date or nullable
        'client_since' => 'client_since',    // date or nullable

        /*
         * How to tell if a contact is a "client":
         * - mode: 'boolean' uses field true/false
         * - mode: 'string' uses status_value match
         * - mode: 'date_not_null' uses client_since not null
         */
        'is_client' => [
            'mode' => 'string',        // 'boolean' | 'string' | 'date_not_null'
            'field' => 'status',       // e.g. 'status' or 'is_client'
            'status_value' => 'client' // used only when mode='string'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Opt-Out Method Names
    |--------------------------------------------------------------------------
    | If your Contact model has these methods, we'll respect them.
    | Otherwise we won't block sending (Tier-1 simple).
    */
    'opt_out_methods' => [
        'sms'   => 'isSmsOptedOut',
        'email' => 'isEmailOptedOut',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Send Time
    |--------------------------------------------------------------------------
    | Used if a step doesn't specify send_time_local.
    */
    'default_send_time_local' => '09:00:00',
];
