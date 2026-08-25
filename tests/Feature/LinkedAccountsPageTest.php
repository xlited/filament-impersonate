<?php

namespace Packstub\AccountSwitcher\Tests\Feature;

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Packstub\AccountSwitcher\Facades\AccountSwitcher;
use Packstub\AccountSwitcher\Filament\Pages\LinkedAccounts;
use Packstub\AccountSwitcher\Models\LinkedAccount;
use Packstub\AccountSwitcher\Tests\Fixtures\User;
use Packstub\AccountSwitcher\Tests\TestCase;

class LinkedAccountsPageTest extends TestCase
{
    public function test_page_is_reachable_from_the_user_menu(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Linked accounts');

        $this->get(LinkedAccounts::getUrl())
            ->assertOk()
            ->assertSee('No linked accounts yet');
    }

    public function test_page_is_forbidden_while_impersonating(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin);
        AccountSwitcher::impersonate($user);

        $this->get(LinkedAccounts::getUrl())->assertForbidden();
    }

    public function test_link_existing_account_verifies_its_credentials(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();

        $this->actingAs($admin);

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('link')->table(), [
                'email' => $daily->email,
                'password' => 'wrong',
                'label' => 'Daily',
                'requires_password' => false,
            ])
            ->assertNotified('No account matches those credentials.');

        $this->assertFalse($admin->isLinkedTo($daily));

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('link')->table(), [
                'email' => $daily->email,
                'password' => 'secret',
                'label' => 'Daily',
                'requires_password' => false,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Account linked.');

        $this->assertTrue($admin->fresh()->isLinkedTo($daily));
        $this->assertTrue($daily->fresh()->isLinkedTo($admin));
        $this->assertSame('Daily', $admin->linkedAccounts()->first()->pivot->label);
    }

    public function test_cannot_link_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin);

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('link')->table(), [
                'email' => $admin->email,
                'password' => 'secret',
                'requires_password' => true,
            ])
            ->assertNotified('No account matches those credentials.');

        $this->assertSame(0, LinkedAccount::query()->count());
    }

    public function test_create_sub_account_creates_and_links_a_user(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin);

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('create')->table(), [
                'name' => 'Admin (daily)',
                'email' => 'admin+daily@example.com',
                'password' => 'another-secret',
                'label' => 'Daily',
                'requires_password' => false,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Sub-account created and linked.');

        $sub = User::query()->where('email', 'admin+daily@example.com')->sole();

        $this->assertTrue(Hash::check('another-secret', $sub->password));
        $this->assertTrue($admin->isLinkedTo($sub));
        $this->assertFalse(AccountSwitcher::requiresPassword($admin, $sub));
        $this->assertTrue(AccountSwitcher::requiresPassword($sub, $admin));
    }

    public function test_create_sub_account_validates_unique_email(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin);

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('create')->table(), [
                'name' => 'Dup',
                'email' => $admin->email,
                'password' => 'another-secret',
                'requires_password' => false,
            ])
            ->assertHasFormErrors(['email']);
    }

    public function test_rename_and_unlink(): void
    {
        $admin = $this->createAdmin();
        $daily = $this->createUser();
        $admin->linkAccount($daily, label: 'Old');

        $this->actingAs($admin);

        $link = LinkedAccount::query()->whereBelongsTo($admin, 'user')->sole();

        Livewire::test(LinkedAccounts::class)
            ->assertCanSeeTableRecords([$link])
            ->callAction(TestAction::make('rename')->table($link), ['label' => 'New'])
            ->assertHasNoFormErrors();

        $this->assertSame('New', $link->fresh()->label);

        Livewire::test(LinkedAccounts::class)
            ->callAction(TestAction::make('unlink')->table($link))
            ->assertNotified('Account unlinked.');

        $this->assertFalse($admin->isLinkedTo($daily));
        $this->assertFalse($daily->isLinkedTo($admin));
    }
}
