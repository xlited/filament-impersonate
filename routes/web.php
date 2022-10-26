<?php

use Illuminate\Support\Facades\Route;
use XliteDev\FilamentImpersonate\ImpersonateAction;

Route::get('filament-impersonate/leave', fn () => ImpersonateAction::leave())
    ->name('filament-impersonate.leave')
    ->middleware(config('filament-impersonate.leave_middlewares'));
