<?php

namespace Packstub\AccountSwitcher\Tests\Feature;

use Filament\Facades\Filament;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Packstub\AccountSwitcher\Tests\TestCase;

class DeveloperLoginTest extends TestCase
{
    public function test_buttons_render_only_in_allowed_environments(): void
    {
        $dev = $this->createUser(['email' => 'dev-alice@example.com']);
        $this->createUser(['email' => 'customer@example.com']);

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('Developer logins');

        config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Developer logins')
            ->assertSee($dev->name)
            ->assertDontSee('customer@example.com');
    }

    public function test_developer_login_signs_in_a_listed_user(): void
    {
        config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

        $dev = $this->createUser(['email' => 'dev-alice@example.com']);

        $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $dev->id])
            ->assertRedirect('http://localhost/admin');

        $this->assertTrue(Filament::auth()->user()->is($dev));
        $this->assertSame(SwitchReason::DeveloperLogin, AccountSwitch::query()->sole()->reason);
    }

    public function test_developer_login_rejects_users_outside_the_list(): void
    {
        config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

        $customer = $this->createUser(['email' => 'customer@example.com']);

        $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $customer->id])
            ->assertNotFound();

        $this->assertNull(Filament::auth()->user());
    }

    public function test_developer_login_is_unreachable_outside_allowed_environments(): void
    {
        $dev = $this->createUser(['email' => 'dev-alice@example.com']);

        $this->assertFalse(AccountSwitcher::developerLoginsEnabled());

        $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $dev->id])
            ->assertNotFound();

        $this->assertNull(Filament::auth()->user());
    }
}
