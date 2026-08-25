<?php

namespace Packstub\AccountSwitcher\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Events\AccountSwitched;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Packstub\AccountSwitcher\Tests\TestCase;

class ImpersonationTest extends TestCase
{
    public function test_admin_can_impersonate_a_user_and_it_is_logged(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin);
        Event::fake([AccountSwitched::class]);

        AccountSwitcher::impersonate($user);

        $this->assertTrue(Filament::auth()->user()->is($user));
        $this->assertTrue(AccountSwitcher::isImpersonating());
        $this->assertTrue(AccountSwitcher::impersonator()->is($admin));

        $switch = AccountSwitch::query()->sole();
        $this->assertSame($admin->id, $switch->from_user_id);
        $this->assertSame($user->id, $switch->to_user_id);
        $this->assertSame(SwitchReason::Impersonation, $switch->reason);
        $this->assertSame('admin', $switch->panel);

        Event::assertDispatched(AccountSwitched::class, fn (AccountSwitched $event): bool => $event->reason === SwitchReason::Impersonation && $event->to->is($user));
    }

    public function test_non_admin_cannot_impersonate(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $this->actingAs($user);

        $this->assertFalse(AccountSwitcher::canImpersonate($user, $other));

        $this->expectException(AccountSwitchDenied::class);

        AccountSwitcher::impersonate($other);
    }

    public function test_admins_cannot_be_impersonated_or_impersonate_themselves(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();

        $this->assertFalse(AccountSwitcher::canImpersonate($admin, $otherAdmin));
        $this->assertFalse(AccountSwitcher::canImpersonate($admin, $admin));
    }

    public function test_nested_impersonation_is_denied(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $another = $this->createUser();

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->assertFalse(AccountSwitcher::canImpersonate($user, $another));
    }

    public function test_plugin_gate_can_deny_impersonation(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        AccountSwitcher::plugin()->canImpersonateUsing(fn (): bool => false);

        $this->assertFalse(AccountSwitcher::canImpersonate($admin, $user));

        AccountSwitcher::plugin()->canImpersonateUsing(null);
    }

    public function test_switch_back_restores_the_impersonator(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->post(route('filament.admin.account-switcher.switch-back'))
            ->assertRedirect();

        $this->assertTrue(Filament::auth()->user()->is($admin));
        $this->assertFalse(AccountSwitcher::isImpersonating());
        $this->assertSame(2, AccountSwitch::query()->count());
        $this->assertSame(SwitchReason::ImpersonationEnded, AccountSwitch::query()->latest('id')->first()->reason);
    }

    public function test_banner_renders_while_impersonating(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('fi-account-switcher-banner')
            ->assertSee('Switch back')
            ->assertSee($admin->name);
    }

    public function test_logging_can_be_disabled(): void
    {
        config()->set('packstub-account-switcher.log_switches', false);

        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->assertSame(0, AccountSwitch::query()->count());
    }
}
