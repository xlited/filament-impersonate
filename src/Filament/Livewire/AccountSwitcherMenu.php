<?php

namespace Packstub\AccountSwitcher\Filament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Packstub\AccountSwitcher\AccountSwitcher;
use Packstub\AccountSwitcher\AccountSwitcherPlugin;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;

/**
 * The "Switch to" dropdown rendered next to the user menu.
 */
class AccountSwitcherMenu extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function switchAction(): Action
    {
        return Action::make('switch')
            ->label(fn (): string => __('packstub-account-switcher::account-switcher.menu.switch'))
            ->modalHeading(fn (array $arguments): string => __('packstub-account-switcher::account-switcher.menu.confirm_heading', [
                'account' => $this->accountLabel($this->resolveAccount($arguments)),
            ]))
            ->modalDescription(fn (): string => __('packstub-account-switcher::account-switcher.menu.confirm_description'))
            ->modalSubmitActionLabel(fn (): string => __('packstub-account-switcher::account-switcher.menu.switch'))
            ->modalWidth('sm')
            ->schema(function (array $arguments): array {
                $account = $this->resolveAccount($arguments);
                $user = Filament::auth()->user();

                if (! $account || ! $user || ! app(AccountSwitcher::class)->requiresPassword($user, $account)) {
                    return [];
                }

                return [
                    TextInput::make('password')
                        ->label(__('packstub-account-switcher::account-switcher.menu.password'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->autocomplete('current-password'),
                ];
            })
            ->action(function (Action $action, array $arguments, array $data, AccountSwitcher $switcher): void {
                $account = $this->resolveAccount($arguments);

                if (! $account) {
                    $action->halt();
                }

                try {
                    $switcher->switchToLinkedAccount($account, $data['password'] ?? null);
                } catch (AccountSwitchDenied $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();
                }

                $this->redirect($switcher->redirectUrlFor($account), navigate: false);
            });
    }

    /**
     * @return Collection<int, Model&Authenticatable>
     */
    public function getAccounts(): Collection
    {
        $user = Filament::auth()->user();
        $switcher = app(AccountSwitcher::class);

        if (! $switcher->supportsLinkedAccounts($user) || $switcher->isImpersonating()) {
            return new Collection;
        }

        return $user->linkedAccounts
            ->filter(fn (Model $account): bool => $switcher->canSwitchToLinkedAccount($user, $account))
            ->values();
    }

    public function getManageUrl(): ?string
    {
        $plugin = app(AccountSwitcher::class)->plugin();

        if (! $plugin?->hasLinkedAccounts()) {
            return null;
        }

        $page = $plugin->getLinkedAccountsPage();

        return $page::canAccess() ? $page::getUrl() : null;
    }

    public function accountLabel(?Model $account): string
    {
        if (! $account) {
            return '';
        }

        return $account->pivot?->label ?: Filament::getUserName($account);
    }

    public function render(): View
    {
        return view('packstub-account-switcher::menu', [
            'accounts' => $this->getAccounts(),
            'manageUrl' => $this->getManageUrl(),
            'plugin' => AccountSwitcherPlugin::get(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return (Model&Authenticatable)|null
     */
    protected function resolveAccount(array $arguments): ?Model
    {
        $key = $arguments['account'] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->getAccounts()->first(
            fn (Model $account): bool => (string) $account->getKey() === (string) $key,
        );
    }
}
