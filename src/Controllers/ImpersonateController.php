<?php

namespace XliteDev\FilamentImpersonate\Controllers;

use Filament\Facades\Filament;
use Filament\PanelProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonateController
{
    public static function allowed($current, $target): bool
    {
        return $current->isNot($target)
            && !app(ImpersonateManager::class)->isImpersonating()
            && (!method_exists($current, 'canImpersonate') || $current->canImpersonate($target))
            && (!method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated($current));
    }

    public static function impersonate($record): bool | Redirector | RedirectResponse
    {

        if (!static::allowed(Filament::auth()->user(), $record)) {
            return false;
        }

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            config('filament-impersonate.guard')
        );

        session()->forget(array_unique([
            'password_hash_' . config('filament-impersonate.guard'),
            'password_hash_' . config('filament.auth.guard'),
        ]));

        session()->put('impersonate.back_to', request()->header('referer'));

        return redirect(config('filament-impersonate.redirect_to'));
    }

    public static function leave(): bool | Redirector | RedirectResponse
    {
        if (!app(ImpersonateManager::class)->isImpersonating()) {
            return redirect('/');
        }

        app(ImpersonateManager::class)->leave();

        session()->forget(array_unique([
            'password_hash_' . config('filament-impersonate.guard'),
            'password_hash_' . config('filament.auth.guard'),
        ]));

        return redirect(
            session()->pull('impersonate.back_to') ?? filament()->getUrl()
        );
    }
}
