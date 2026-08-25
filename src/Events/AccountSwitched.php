<?php

namespace Packstub\AccountSwitcher\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Packstub\AccountSwitcher\Enums\SwitchReason;

/**
 * Fired after the session has been re-authenticated as a different user.
 *
 * `$from` is null for a developer login from the login page.
 */
class AccountSwitched
{
    use Dispatchable;

    public function __construct(
        public readonly ?Authenticatable $from,
        public readonly Authenticatable $to,
        public readonly SwitchReason $reason,
    ) {}
}
