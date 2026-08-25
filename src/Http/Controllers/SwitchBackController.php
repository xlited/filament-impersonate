<?php

namespace Packstub\AccountSwitcher\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Packstub\AccountSwitcher\AccountSwitcher;

class SwitchBackController
{
    public function __invoke(AccountSwitcher $switcher): RedirectResponse
    {
        if (! $switcher->isImpersonating()) {
            return redirect()->to($switcher->redirectUrlFor(auth()->user()));
        }

        return redirect()->to($switcher->stopImpersonating());
    }
}
