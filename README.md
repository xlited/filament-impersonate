# Filament Account Switcher

[![Latest Version on Packagist](https://img.shields.io/packagist/v/packstub/filament-account-switcher.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-account-switcher)
[![Tests](https://img.shields.io/github/actions/workflow/status/packstub/filament-account-switcher/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/packstub/filament-account-switcher/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/packstub/filament-account-switcher.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-account-switcher)

Switch between accounts in a Filament panel without signing out — safely, in production.

- **Linked accounts** — work from a low-privilege account day to day and switch to your full admin account only when you need it. A "Switch to" menu in the topbar lists the accounts you've linked; switching *up* asks for that account's password, switching *down* is one click.
- **Impersonation** — an `ImpersonateAction` for your user resource, a persistent banner with a "Switch back" button, and `canImpersonate()` / `canBeImpersonated()` hooks on your model.
- **Developer logins** — one-click sign-in buttons on the login page for the accounts you seed locally. Never rendered outside the environments you allow.
- **Audit trail** — every switch is recorded (who, to whom, why, panel, IP, user agent) and fires `AccountSwitching` / `AccountSwitched` events.

Formerly `xlite-dev/filament-impersonate`. See [UPGRADE.md](UPGRADE.md) for the migration.

## Requirements

PHP 8.2+, Laravel 12 or 13, Filament 4 or 5.

## Installation

```bash
composer require packstub/filament-account-switcher
php artisan packstub-account-switcher:install
```

Add the trait to your user model and the plugin to your panel:

```php
use Packstub\AccountSwitcher\Concerns\HasLinkedAccounts;

class User extends Authenticatable implements FilamentUser
{
    use HasLinkedAccounts;

    public function canImpersonate(User $target): bool
    {
        return $this->hasRole('super-admin');
    }
}
```

```php
use Packstub\AccountSwitcher\AccountSwitcherPlugin;

$panel->plugin(
    AccountSwitcherPlugin::make()
        ->developerLogins(['admin@example.com', 'user@example.com']),
);
```

Then drop the action into your `UserResource` table:

```php
use Packstub\AccountSwitcher\Filament\Actions\ImpersonateAction;

->recordActions([
    ImpersonateAction::make(),
])
```

## Documentation

Full documentation lives at **[packstub.dev/docs/filament-account-switcher](https://packstub.dev/docs/filament-account-switcher)** and in the [`docs/`](docs/README.md) directory:

- [Installation](docs/installation.md)
- [Linked accounts](docs/linked-accounts.md)
- [Impersonation](docs/impersonation.md)
- [Developer logins](docs/developer-logins.md)
- [Configuration](docs/configuration.md)
- [Security](docs/security.md)

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Security vulnerabilities

Please e-mail [support@packstub.dev](mailto:support@packstub.dev) rather than opening a public issue.

## Credits

- [Ion Caliman](https://github.com/icaliman)
- [All contributors](../../contributors)

## License

MIT. See [LICENSE.md](LICENSE.md).
