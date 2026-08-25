<?php

use Illuminate\Support\Facades\Hash;
use Packstub\AccountSwitcher\Tests\Fixtures\User;
use Packstub\AccountSwitcher\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * @param  array<string, mixed>  $attributes
 */
function createUser(array $attributes = []): User
{
    static $sequence = 0;

    $sequence++;

    return User::query()->create([
        'name' => "User {$sequence}",
        'email' => "user{$sequence}@example.com",
        'password' => Hash::make('secret'),
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createAdmin(array $attributes = []): User
{
    return createUser(['name' => 'Admin', 'is_admin' => true, ...$attributes]);
}
