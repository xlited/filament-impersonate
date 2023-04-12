<?php

namespace XliteDev\FilamentImpersonate;

use Filament\Facades\Filament;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Blade;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\LaravelPackageTools\Package;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    public function configurePackage(Package $package): void
    {
        parent::configurePackage($package);

        $package->hasRoute('web');

        $package->hasViews('filament-impersonate');
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        $renderHook = match (config('filament-impersonate.banner.position')) {
            'top' => 'body.start',
            default => 'body.end',
        };

        Filament::registerRenderHook(
            $renderHook,
            function (): string {
                if (! app(ImpersonateManager::class)->isImpersonating()) {
                    return '';
                }

                return Blade::render('<x-filament-impersonate::banner />');
            },
        );
    }
}
