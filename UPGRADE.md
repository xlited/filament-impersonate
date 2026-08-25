# Upgrading from `xlite-dev/filament-impersonate` 3.x

The package was renamed to `packstub/filament-account-switcher` and rebuilt with a shared session core, linked accounts, developer logins and an audit log. Impersonation keeps the same authorization hooks (`canImpersonate()` / `canBeImpersonated()` on your model) but the classes moved.

## 1. Swap the package

```bash
composer remove xlite-dev/filament-impersonate
composer require packstub/filament-account-switcher
php artisan packstub-account-switcher:install
```

`lab404/laravel-impersonate` is no longer required. If nothing else in your app uses it, remove it too.

## 2. Register the plugin

The old package worked without a plugin; the new one integrates through the panel:

```php
use Packstub\AccountSwitcher\AccountSwitcherPlugin;

$panel->plugin(AccountSwitcherPlugin::make());
```

If you only want impersonation, disable the rest:

```php
AccountSwitcherPlugin::make()
    ->linkedAccounts(false)
```

## 3. Update imports

| Before | After |
| --- | --- |
| `XliteDev\FilamentImpersonate\Actions\ImpersonateAction` | `Packstub\AccountSwitcher\Filament\Actions\ImpersonateAction` |
| `XliteDev\FilamentImpersonate\FilamentImpersonatePlugin` | `Packstub\AccountSwitcher\AccountSwitcherPlugin` |
| `route('filament-impersonate.leave')` (GET) | `route('filament.{panel}.account-switcher.switch-back')` (POST) |
| `<x-filament-impersonate::banner />` | rendered automatically; customize via `->banner()` or publish `packstub-account-switcher::banner` |

## 4. Config

Delete `config/filament-impersonate.php` and publish the new file if you need it:

```bash
php artisan vendor:publish --tag="packstub-account-switcher-config"
```

- `guard`, `redirect_to`, `leave_middlewares` — removed. The panel's own guard and route group are used; set `->redirectTo()` on the plugin to override the landing URL.
- `banner.style`, `banner.position` — kept (`top` / `bottom`). `banner.fixed` was removed; the banner is always fixed.

## 5. Migrations

Two new tables (`linked_accounts`, `account_switches`) are published by the install command. Both are optional if you disable linked accounts and switch logging, but running them is harmless.
