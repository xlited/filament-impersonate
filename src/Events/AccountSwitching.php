<?php

namespace Packstub\AccountSwitcher\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Packstub\AccountSwitcher\Enums\SwitchReason;

/**
 * Fired after authorization passed and right before the session is
 * re-authenticated. Listeners may throw to abort the switch.
 */
class AccountSwitching
{
    use Dispatchable;

    public function __construct(
        public readonly ?Authenticatable $from,
        public readonly Authenticatable $to,
        public readonly SwitchReason $reason,
    ) {}
}
