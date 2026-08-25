<?php

namespace Packstub\AccountSwitcher\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class AccountSwitchDenied extends AuthorizationException
{
    public static function notLinked(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.not_linked'));
    }

    public static function invalidPassword(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.invalid_password'));
    }

    public static function whileImpersonating(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.while_impersonating'));
    }

    public static function cannotImpersonate(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.cannot_impersonate'));
    }

    public static function developerLoginsDisabled(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.developer_logins_disabled'));
    }

    public static function featureDisabled(): self
    {
        return new self(__('packstub-account-switcher::account-switcher.errors.feature_disabled'));
    }
}
