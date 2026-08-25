<?php

namespace Packstub\AccountSwitcher\Tests\Feature;

use Filament\Facades\Filament;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Packstub\AccountSwitcher\Tests\TestCase;

class LinkedAccountSwitchTest extends TestCase
{
    public function test_linking_is_symmetric_and_idempotent(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();

        $admin->linkAccount($daily, label: 'Daily', requiresPassword: false);
        $admin->linkAccount($daily, label: 'Daily work');

        $this->assertTrue($admin->isLinkedTo($daily));
        $this->assertTrue($daily->isLinkedTo($admin));
        $this->assertSame(1, $admin->linkedAccounts()->count());
        $this->assertSame('Daily work', $admin->linkedAccounts()->first()->pivot->label);

        $admin->unlinkAccount($daily);

        $this->assertFalse($admin->isLinkedTo($daily));
        $this->assertFalse($daily->isLinkedTo($admin));
    }

    public function test_switching_down_without_password_requirement(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();
        $admin->linkAccount($daily, requiresPassword: false);

        $this->actingAs($admin);

        $this->assertFalse(AccountSwitcher::requiresPassword($admin, $daily));

        AccountSwitcher::switchToLinkedAccount($daily);

        $this->assertTrue(Filament::auth()->user()->is($daily));
        $this->assertFalse(AccountSwitcher::isImpersonating());
        $this->assertSame(SwitchReason::LinkedAccount, AccountSwitch::query()->sole()->reason);
    }

    public function test_switching_up_requires_the_target_password(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();
        $daily->linkAccount($admin, requiresPassword: true);

        $this->actingAs($daily);

        $this->assertTrue(AccountSwitcher::requiresPassword($daily, $admin));

        try {
            AccountSwitcher::switchToLinkedAccount($admin, 'wrong');
            $this->fail('Expected the switch to be denied.');
        } catch (AccountSwitchDenied) {
        }

        $this->assertTrue(Filament::auth()->user()->is($daily));

        AccountSwitcher::switchToLinkedAccount($admin, 'secret');

        $this->assertTrue(Filament::auth()->user()->is($admin));
    }

    public function test_cannot_switch_to_an_unlinked_account(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $this->actingAs($user);

        $this->assertFalse(AccountSwitcher::canSwitchToLinkedAccount($user, $other));

        $this->expectException(AccountSwitchDenied::class);

        AccountSwitcher::switchToLinkedAccount($other, 'secret');
    }

    public function test_cannot_switch_while_impersonating(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $userAdmin = $this->createAdmin();
        $user->linkAccount($userAdmin, requiresPassword: false);

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->assertFalse(AccountSwitcher::canSwitchToLinkedAccount($user, $userAdmin));

        $this->expectException(AccountSwitchDenied::class);

        AccountSwitcher::switchToLinkedAccount($userAdmin);
    }

    public function test_cannot_switch_to_an_account_without_panel_access(): void
    {
        $user = $this->createUser();
        $locked = $this->createUser(['can_access_panel' => false]);
        $user->linkAccount($locked, requiresPassword: false);

        $this->assertFalse(AccountSwitcher::canSwitchToLinkedAccount($user, $locked));
    }

    public function test_plugin_gate_can_deny_switching(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $user->linkAccount($other, requiresPassword: false);

        AccountSwitcher::plugin()->canSwitchUsing(fn (): bool => false);

        $this->assertFalse(AccountSwitcher::canSwitchToLinkedAccount($user, $other));

        AccountSwitcher::plugin()->canSwitchUsing(null);
    }
}
