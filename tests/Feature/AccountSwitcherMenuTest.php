<?php

namespace Packstub\AccountSwitcher\Tests\Feature;

use Filament\Facades\Filament;
use Livewire\Livewire;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Filament\Livewire\AccountSwitcherMenu;
use Packstub\AccountSwitcher\Tests\TestCase;

class AccountSwitcherMenuTest extends TestCase
{
    public function test_menu_lists_linked_accounts_in_the_topbar(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser(['name' => 'Daily Driver']);
        $admin->linkAccount($daily, label: 'Daily');

        $this->actingAs($admin);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Switch to')
            ->assertSee('Daily')
            ->assertSee($daily->email)
            ->assertSee('Manage linked accounts');
    }

    public function test_menu_is_hidden_without_linked_accounts(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('Switch to');
    }

    public function test_menu_is_hidden_while_impersonating(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $userAdmin = $this->createAdmin();
        $user->linkAccount($userAdmin, requiresPassword: false);

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('Switch to');
    }

    public function test_switching_without_password_requirement(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();
        $admin->linkAccount($daily, requiresPassword: false);

        $this->actingAs($admin);

        Livewire::test(AccountSwitcherMenu::class)
            ->callAction('switch', arguments: ['account' => $daily->id])
            ->assertRedirect('http://localhost/admin');

        $this->assertTrue(Filament::auth()->user()->is($daily));
    }

    public function test_switching_up_asks_for_the_target_password(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();
        $daily->linkAccount($admin, requiresPassword: true);

        $this->actingAs($daily);

        Livewire::test(AccountSwitcherMenu::class)
            ->mountAction('switch', arguments: ['account' => $admin->id])
            ->assertActionMounted('switch')
            ->callMountedAction()
            ->assertHasFormErrors(['password' => 'required']);

        $this->assertTrue(Filament::auth()->user()->is($daily));

        Livewire::test(AccountSwitcherMenu::class)
            ->callAction('switch', ['password' => 'wrong'], ['account' => $admin->id])
            ->assertNotified()
            ->assertNoRedirect();

        $this->assertTrue(Filament::auth()->user()->is($daily));

        Livewire::test(AccountSwitcherMenu::class)
            ->callAction('switch', ['password' => 'secret'], ['account' => $admin->id])
            ->assertRedirect('http://localhost/admin');

        $this->assertTrue(Filament::auth()->user()->is($admin));
    }

    public function test_unlinked_accounts_cannot_be_targeted(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $this->actingAs($user);

        Livewire::test(AccountSwitcherMenu::class)
            ->callAction('switch', arguments: ['account' => $other->id])
            ->assertNoRedirect();

        $this->assertTrue(Filament::auth()->user()->is($user));
    }
}
