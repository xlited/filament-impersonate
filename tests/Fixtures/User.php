<?php

namespace Packstub\AccountSwitcher\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Packstub\AccountSwitcher\Concerns\HasLinkedAccounts;

class User extends Authenticatable implements FilamentUser
{
    use HasLinkedAccounts;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'is_admin' => false,
        'can_access_panel' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'can_access_panel' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can_access_panel;
    }

    public function canImpersonate(self $target): bool
    {
        return $this->is_admin;
    }

    public function canBeImpersonated(self $by): bool
    {
        return ! $this->is_admin;
    }
}
