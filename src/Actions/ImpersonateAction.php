<?php

namespace XliteDev\FilamentImpersonate\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
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

        $this->icon('filament::impersonate-icon');

        $this->action(fn ($record) => ImpersonateController::impersonate($record));

        $this->authorize(fn ($record) => ImpersonateController::allowed(Filament::auth()->user(), $record));
    }
}
