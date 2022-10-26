<?php

namespace XliteDev\FilamentImpersonate;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FilamentImpersonateServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-impersonate';

    protected array $resources = [
        // CustomResource::class,
    ];

    protected array $pages = [
        // CustomPage::class,
    ];

    protected array $widgets = [
        // CustomWidget::class,
    ];

    protected array $styles = [
        'plugin-filament-impersonate' => __DIR__.'/../resources/dist/filament-impersonate.css',
    ];

    protected array $scripts = [
        'plugin-filament-impersonate' => __DIR__.'/../resources/dist/filament-impersonate.js',
    ];

    // protected array $beforeCoreScripts = [
    //     'plugin-filament-impersonate' => __DIR__ . '/../resources/dist/filament-impersonate.js',
    // ];

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);
    }
}
