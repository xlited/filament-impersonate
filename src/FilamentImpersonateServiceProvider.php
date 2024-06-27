<?php

namespace XliteDev\FilamentImpersonate;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Icon;
use Filament\View\PanelsRenderHook;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Lab404\Impersonate\Services\ImpersonateManager;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use XliteDev\FilamentImpersonate\Commands\FilamentImpersonateCommand;

class FilamentImpersonateServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-impersonate';

    public static string $viewNamespace = 'filament-impersonate';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('xlite-dev/filament-impersonate');
            });


        $package->hasConfigFile();

        $package->hasTranslations();

        $package->hasViews(static::$viewNamespace);

        $package->hasRoutes($this->getRoutes());
    }

    public function packageRegistered(): void
    {
        // Icon Registration
        $this->callAfterResolving(\BladeUI\Icons\Factory::class, function (\BladeUI\Icons\Factory $factory) {
            $factory->add('filament-impersonate-icons', [
                'path' => __DIR__ . '/../resources/svg',
                'prefix' => 'filament::impersonate',
            ]);
        });
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        $renderHook = match (config('filament-impersonate.banner.position')) {
            'top' => PanelsRenderHook::BODY_START,
            'bottom' => PanelsRenderHook::BODY_END,
            'page-start' => PanelsRenderHook::PAGE_START,
            default => PanelsRenderHook::PAGE_END,
        };

        FilamentView::registerRenderHook(
            $renderHook,
            function (): string {
                if (!app(ImpersonateManager::class)->isImpersonating()) {
                    return '';
                }

                return Blade::render('<x-filament-impersonate::banner />');
            },
        );
    }

    protected function getAssetPackageName(): ?string
    {
        return 'xlite-dev/filament-impersonate';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('filament-impersonate', __DIR__ . '/../resources/dist/components/filament-impersonate.js'),
            // Css::make('filament-impersonate-styles', __DIR__ . '/../resources/dist/filament-impersonate.css'),
            // Js::make('filament-impersonate-scripts', __DIR__ . '/../resources/dist/filament-impersonate.js'),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return ['filament'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }
}
