<?php

namespace Packstub\AccountSwitcher;

use BladeUI\Icons\Factory as IconFactory;
use Livewire\Livewire;
use Packstub\AccountSwitcher\Filament\Livewire\AccountSwitcherMenu;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AccountSwitcherServiceProvider extends PackageServiceProvider
{
    public static string $name = 'packstub-account-switcher';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$name)
            ->hasTranslations()
            ->hasMigrations(['create_linked_accounts_table', 'create_account_switches_table'])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('packstub/filament-account-switcher');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AccountSwitcher::class);

        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('packstub-account-switcher-icons', [
                'path' => __DIR__.'/../resources/svg',
                'prefix' => 'packstub-account-switcher',
            ]);
        });
    }

    public function packageBooted(): void
    {
        Livewire::component('packstub-account-switcher-menu', AccountSwitcherMenu::class);
    }
}
