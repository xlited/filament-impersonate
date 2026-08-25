<?php

use Filament\Facades\Filament;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;

it('links symmetrically and idempotently', function (): void {
    $admin = createAdmin();
    $daily = createUser();

    $admin->linkAccount($daily, label: 'Daily', requiresPassword: false);
    $admin->linkAccount($daily, label: 'Daily work');

    expect($admin->isLinkedTo($daily))->toBeTrue()
        ->and($daily->isLinkedTo($admin))->toBeTrue()
        ->and($admin->linkedAccounts()->count())->toBe(1)
        ->and($admin->linkedAccounts()->first()->pivot->label)->toBe('Daily work');

    $admin->unlinkAccount($daily);

    expect($admin->isLinkedTo($daily))->toBeFalse()
        ->and($daily->isLinkedTo($admin))->toBeFalse();
});

it('switches down without a password requirement', function (): void {
    $admin = createAdmin();
    $daily = createUser();
    $admin->linkAccount($daily, requiresPassword: false);

    $this->actingAs($admin);

    expect(AccountSwitcher::requiresPassword($admin, $daily))->toBeFalse();

    AccountSwitcher::switchToLinkedAccount($daily);

    expect(Filament::auth()->user()->is($daily))->toBeTrue()
        ->and(AccountSwitcher::isImpersonating())->toBeFalse()
        ->and(AccountSwitch::query()->sole()->reason)->toBe(SwitchReason::LinkedAccount);
});

it('requires the target password when switching up', function (): void {
    $admin = createAdmin();
    $daily = createUser();
    $daily->linkAccount($admin, requiresPassword: true);

    $this->actingAs($daily);

    expect(AccountSwitcher::requiresPassword($daily, $admin))->toBeTrue();

    expect(fn () => AccountSwitcher::switchToLinkedAccount($admin, 'wrong'))
        ->toThrow(AccountSwitchDenied::class);

    expect(Filament::auth()->user()->is($daily))->toBeTrue();

    AccountSwitcher::switchToLinkedAccount($admin, 'secret');

    expect(Filament::auth()->user()->is($admin))->toBeTrue();
});

it('cannot switch to an unlinked account', function (): void {
    $user = createUser();
    $other = createUser();

    $this->actingAs($user);

    expect(AccountSwitcher::canSwitchToLinkedAccount($user, $other))->toBeFalse();

    AccountSwitcher::switchToLinkedAccount($other, 'secret');
})->throws(AccountSwitchDenied::class);

it('cannot switch while impersonating', function (): void {
    $admin = createAdmin();
    $user = createUser();
    $userAdmin = createAdmin();
    $user->linkAccount($userAdmin, requiresPassword: false);

    $this->actingAs($admin);
    AccountSwitcher::impersonate($user);

    expect(AccountSwitcher::canSwitchToLinkedAccount($user, $userAdmin))->toBeFalse();

    AccountSwitcher::switchToLinkedAccount($userAdmin);
})->throws(AccountSwitchDenied::class);

it('cannot switch to an account without panel access', function (): void {
    $user = createUser();
    $locked = createUser(['can_access_panel' => false]);
    $user->linkAccount($locked, requiresPassword: false);

    expect(AccountSwitcher::canSwitchToLinkedAccount($user, $locked))->toBeFalse();
});

it('lets the plugin gate deny switching', function (): void {
    $user = createUser();
    $other = createUser();
    $user->linkAccount($other, requiresPassword: false);

    AccountSwitcher::plugin()->canSwitchUsing(fn (): bool => false);

    expect(AccountSwitcher::canSwitchToLinkedAccount($user, $other))->toBeFalse();

    AccountSwitcher::plugin()->canSwitchUsing(null);
});
