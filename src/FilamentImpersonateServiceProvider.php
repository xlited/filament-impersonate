<?php

namespace XliteDev\FilamentImpersonate;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;
use XliteDev\FilamentImpersonate\Middleware\ImpersonationBanner;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function configurePackage(Package $package): void
    {
        parent::configurePackage($package);

        $package->hasRoute('web');

        $package->hasViews('filament-impersonate');

        $this->app['config']->push('filament.middleware.base', ImpersonationBanner::class);
    }
}
