<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that linked accounts and the switch log point to.
    | Leave null to use the model of the default auth provider
    | (config('auth.providers.users.model')).
    |
    */

    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | The connection the linked_accounts and account_switches tables live on.
    | Leave null to use the user model's connection, so multi-database
    | tenancy setups (e.g. a `CentralConnection` user model) keep both
    | tables next to the users table instead of on the tenant connection.
    |
    */

    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    'tables' => [
        'linked_accounts' => 'linked_accounts',
        'account_switches' => 'account_switches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Switch log
    |--------------------------------------------------------------------------
    |
    | Every impersonation, linked-account switch, and developer login is
    | recorded in the account_switches table with IP and user agent.
    | Disable if you keep your own audit trail via the events.
    |
    */

    'log_switches' => true,

    /*
    |--------------------------------------------------------------------------
    | Developer logins
    |--------------------------------------------------------------------------
    |
    | The environments in which the one-click login buttons may ever render.
    | The plugin's ->developerLogins() call is still required per panel; this
    | list is the hard server-side ceiling regardless of panel configuration.
    |
    */

    'developer_logins' => [
        'environments' => ['local'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation banner
    |--------------------------------------------------------------------------
    |
    | Position: 'top' or 'bottom'. Style: 'dark' or 'light'.
    |
    */

    'banner' => [
        'position' => 'bottom',
        'style' => 'dark',
    ],

];
