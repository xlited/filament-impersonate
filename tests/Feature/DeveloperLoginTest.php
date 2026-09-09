<?php

use Filament\Facades\Filament;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;

it('renders buttons only in allowed environments', function (): void {
    $dev = createUser(['email' => 'dev-alice@example.com']);
    createUser(['email' => 'customer@example.com']);

    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('Developer logins');

    config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Developer logins')
        ->assertSee($dev->name)
        ->assertDontSee('customer@example.com');
});

it('signs in a listed user', function (): void {
    config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

    $dev = createUser(['email' => 'dev-alice@example.com']);

    $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $dev->id])
        ->assertRedirect(Filament::getPanel('admin')->getUrl());

    expect(Filament::auth()->user()->is($dev))->toBeTrue()
        ->and(AccountSwitch::query()->sole()->reason)->toBe(SwitchReason::DeveloperLogin);
});

it('rejects users outside the list', function (): void {
    config()->set('packstub-account-switcher.developer_logins.environments', ['testing']);

    $customer = createUser(['email' => 'customer@example.com']);

    $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $customer->id])
        ->assertNotFound();

    expect(Filament::auth()->user())->toBeNull();
});

it('is unreachable outside allowed environments', function (): void {
    $dev = createUser(['email' => 'dev-alice@example.com']);

    expect(AccountSwitcher::developerLoginsEnabled())->toBeFalse();

    $this->post(route('filament.admin.account-switcher.developer-login'), ['user' => $dev->id])
        ->assertNotFound();

    expect(Filament::auth()->user())->toBeNull();
});
