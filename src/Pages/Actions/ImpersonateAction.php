<?php

namespace XliteDev\FilamentImpersonate\Pages\Actions;

use Filament\Facades\Filament;
use Filament\Pages\Actions\Action;
use Filament\Support\Actions\Concerns\CanCustomizeProcess;
use XliteDev\FilamentImpersonate\Controllers\ImpersonateController;

class ImpersonateAction extends Action
{
    use CanCustomizeProcess;

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
