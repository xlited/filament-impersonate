<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Events\AccountSwitched;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Models\AccountSwitch;

it('lets an admin impersonate a user and logs it', function (): void {
    $admin = createAdmin();
    $user = createUser();

    $this->actingAs($admin);
    Event::fake([AccountSwitched::class]);

    AccountSwitcher::impersonate($user);

    expect(Filament::auth()->user()->is($user))->toBeTrue()
        ->and(AccountSwitcher::isImpersonating())->toBeTrue()
        ->and(AccountSwitcher::impersonator()->is($admin))->toBeTrue();

    $switch = AccountSwitch::query()->sole();

    expect($switch->from_user_id)->toBe($admin->id)
        ->and($switch->to_user_id)->toBe($user->id)
        ->and($switch->reason)->toBe(SwitchReason::Impersonation)
        ->and($switch->panel)->toBe('admin');

    Event::assertDispatched(AccountSwitched::class, fn (AccountSwitched $event): bool => $event->reason === SwitchReason::Impersonation && $event->to->is($user));
});

it('denies impersonation to non-admins', function (): void {
    $user = createUser();
    $other = createUser();

    $this->actingAs($user);

    expect(AccountSwitcher::canImpersonate($user, $other))->toBeFalse();

    AccountSwitcher::impersonate($other);
})->throws(AccountSwitchDenied::class);

it('does not let admins be impersonated or impersonate themselves', function (): void {
    $admin = createAdmin();
    $otherAdmin = createAdmin();

    expect(AccountSwitcher::canImpersonate($admin, $otherAdmin))->toBeFalse()
        ->and(AccountSwitcher::canImpersonate($admin, $admin))->toBeFalse();
});

it('denies nested impersonation', function (): void {
    $admin = createAdmin();
    $user = createUser();
    $another = createUser();

    $this->actingAs($admin);
    AccountSwitcher::impersonate($user);

    expect(AccountSwitcher::canImpersonate($user, $another))->toBeFalse();
});

it('lets the plugin gate deny impersonation', function (): void {
    $admin = createAdmin();
    $user = createUser();

    AccountSwitcher::plugin()->canImpersonateUsing(fn (): bool => false);

    expect(AccountSwitcher::canImpersonate($admin, $user))->toBeFalse();

    AccountSwitcher::plugin()->canImpersonateUsing(null);
});

it('restores the impersonator on switch back', function (): void {
    $admin = createAdmin();
    $user = createUser();

    $this->actingAs($admin);
    AccountSwitcher::impersonate($user);

    $this->post(route('filament.admin.account-switcher.switch-back'))
        ->assertRedirect();

    expect(Filament::auth()->user()->is($admin))->toBeTrue()
        ->and(AccountSwitcher::isImpersonating())->toBeFalse()
        ->and(AccountSwitch::query()->count())->toBe(2)
        ->and(AccountSwitch::query()->latest('id')->first()->reason)->toBe(SwitchReason::ImpersonationEnded);
});

it('renders the banner while impersonating', function (): void {
    $admin = createAdmin();
    $user = createUser();

    $this->actingAs($admin);
    AccountSwitcher::impersonate($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('fi-account-switcher-banner')
        ->assertSee('Switch back')
        ->assertSee($admin->name);
});

it('can disable logging', function (): void {
    config()->set('packstub-account-switcher.log_switches', false);

    $admin = createAdmin();
    $user = createUser();

    $this->actingAs($admin);
    AccountSwitcher::impersonate($user);

    expect(AccountSwitch::query()->count())->toBe(0);
});
