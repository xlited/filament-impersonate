# Filament Account Switcher

![Filament Account Switcher — switch accounts, never sign out](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/art/banner.jpg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/packstub/filament-account-switcher.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-account-switcher)
[![Tests](https://img.shields.io/github/actions/workflow/status/packstub/filament-account-switcher/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/packstub/filament-account-switcher/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/packstub/filament-account-switcher.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-account-switcher)
[![License](https://img.shields.io/packagist/l/packstub/filament-account-switcher.svg?style=flat-square)](LICENSE.md)

Switch between accounts in a Filament panel without signing out — safely, in production.

## Features

- 🔀 **Linked accounts** — work from a low-privilege account day to day and switch to your full admin account only when you need it. A *Switch to* menu in the topbar lists the accounts you've linked; switching *up* asks for that account's password, switching *down* is one click.
- 🕵️ **Impersonation** — an `ImpersonateAction` for your user resource, a persistent banner with a *Switch back* button, and `canImpersonate()` / `canBeImpersonated()` hooks on your model.
- ⚡ **Developer logins** — one-click sign-in buttons on the login page for the accounts you seed locally. Never rendered outside the environments you allow.
- 📜 **Audit trail** — every switch is recorded (who, to whom, why, panel, IP, user agent) and fires `AccountSwitching` / `AccountSwitched` events.
- 🎛️ **Fluent plugin API** — turn each feature on or off per panel, add your own authorization rules, move the banner, replace the Linked accounts page.
- 🌙 **Dark mode ready** · 🌍 **Translatable** — every string ships in a language file.

Formerly `xlite-dev/filament-impersonate`. See [UPGRADE.md](UPGRADE.md) for the migration.

## Compatibility

| Plugin | Filament | Laravel | PHP |
| --- | --- | --- | --- |
| 4.x | 4.x, 5.x | 12.x, 13.x | 8.2+ |
| 3.x (`xlite-dev/filament-impersonate`) | 4.x, 5.x | 9.x – 12.x | 8.1+ |

## Screenshots

### Linked accounts

The *Switch to* menu lists the accounts you've linked. Going *down* to a lower-privilege account is one click; going *up* asks for the target account's password.

![The Switch to menu](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/switch-to-menu.png)

![Password confirmation when switching up](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/switch-password-modal.png)

Manage links, labels and the per-link password rule from the Linked accounts page — or create a sub-account right there.

![The Linked accounts page](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/linked-accounts-page.png)

### Impersonation

Drop `ImpersonateAction` into your users table. While impersonating, a banner stays visible with a *Switch back* button.

![The Impersonate action in a users table](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/impersonate-action.png)

![The impersonation banner](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/impersonation-banner.png)

### Developer logins

One-click sign-in for seeded accounts, only in the environments you allow.

![Developer login buttons on the login page](https://raw.githubusercontent.com/packstub/filament-account-switcher/main/docs/images/developer-logins.png)

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

## Configuration at a glance

```php
AccountSwitcherPlugin::make()
    ->impersonation()                                  // on by default
    ->linkedAccounts()                                 // on by default
    ->developerLogins(['admin@example.com'])           // off by default
    ->canImpersonateUsing(fn (User $by, User $target) => $by->can('impersonate', $target))
    ->canSwitchUsing(fn (User $from, User $to) => ! $to->is_locked)
    ->banner(position: 'top', style: 'light')
    ->redirectTo('/admin')
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
