<?php

namespace XliteDev\FilamentImpersonate\Tables\Actions;

use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use XliteDev\FilamentImpersonate\Controllers\ImpersonateController;

class ImpersonateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->iconButton();

        $this->icon('filament-impersonate::icon');

        $this->action(fn ($record) => ImpersonateController::impersonate($record));

        $this->hidden(fn ($record) => ! ImpersonateController::allowed(Filament::auth()->user(), $record));
    }
}
