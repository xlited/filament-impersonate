<?php

namespace Packstub\AccountSwitcher\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Packstub\AccountSwitcher\AccountSwitcher;
use Packstub\AccountSwitcher\AccountSwitcherPlugin;
use Packstub\AccountSwitcher\Models\LinkedAccount;

/**
 * Manage the accounts the signed-in user can switch to.
 */
class LinkedAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'linked-accounts';

    protected string $view = 'packstub-account-switcher::pages.linked-accounts';

    public static function getLabel(): string
    {
        return __('packstub-account-switcher::account-switcher.linked_accounts.title');
    }

    public function getTitle(): string
    {
        return static::getLabel();
    }

    public static function canAccess(): bool
    {
        $switcher = app(AccountSwitcher::class);

        return parent::canAccess()
            && $switcher->supportsLinkedAccounts(Filament::auth()->user())
            && ! $switcher->isImpersonating();
    }

    public function getSubheading(): ?string
    {
        return __('packstub-account-switcher::account-switcher.linked_accounts.subheading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LinkedAccount::query()
                ->whereBelongsTo(Filament::auth()->user(), 'user')
                ->with('linkedUser'))
            ->defaultSort('label')
            ->emptyStateHeading(__('packstub-account-switcher::account-switcher.linked_accounts.empty_heading'))
            ->emptyStateDescription(__('packstub-account-switcher::account-switcher.linked_accounts.empty_description'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.label'))
                    ->placeholder('—'),
                TextColumn::make('linkedUser.name')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.account'))
                    ->state(fn (LinkedAccount $record): string => Filament::getUserName($record->linkedUser))
                    ->description(fn (LinkedAccount $record): ?string => $record->linkedUser->email ?? null),
                ToggleColumn::make('requires_password')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password'))
                    ->tooltip(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password_help')),
                TextColumn::make('created_at')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.linked_at'))
                    ->since(),
            ])
            ->recordActions([
                Action::make('rename')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.actions.rename'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (LinkedAccount $record): array => ['label' => $record->label])
                    ->schema([
                        TextInput::make('label')
                            ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.label'))
                            ->maxLength(255),
                    ])
                    ->action(fn (LinkedAccount $record, array $data) => $record->update(['label' => $data['label'] ?: null])),
                Action::make('unlink')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.actions.unlink'))
                    ->icon(Heroicon::OutlinedLinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (LinkedAccount $record): void {
                        Filament::auth()->user()->unlinkAccount($record->linkedUser);

                        Notification::make()
                            ->title(__('packstub-account-switcher::account-switcher.linked_accounts.notifications.unlinked'))
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                $this->linkExistingAction(),
                $this->createSubAccountAction(),
            ]);
    }

    protected function linkExistingAction(): Action
    {
        return Action::make('link')
            ->label(__('packstub-account-switcher::account-switcher.linked_accounts.actions.link'))
            ->icon(Heroicon::OutlinedLink)
            ->modalDescription(__('packstub-account-switcher::account-switcher.linked_accounts.link_description'))
            ->modalWidth('md')
            ->schema([
                TextInput::make('email')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.email'))
                    ->email()
                    ->required()
                    ->autocomplete('off'),
                TextInput::make('password')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.account_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->autocomplete('off'),
                TextInput::make('label')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.label'))
                    ->maxLength(255),
                Toggle::make('requires_password')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password'))
                    ->helperText(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password_help'))
                    ->default(true),
            ])
            ->action(function (Action $action, array $data, AccountSwitcher $switcher): void {
                $user = Filament::auth()->user();
                $model = AccountSwitcher::userModel();

                /** @var (Model&Authenticatable)|null $account */
                $account = $model::query()->where('email', $data['email'])->first();

                if (! $account || $account->is($user) || ! $switcher->passwordMatches($account, $data['password'])) {
                    Notification::make()
                        ->title(__('packstub-account-switcher::account-switcher.linked_accounts.notifications.invalid_credentials'))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                $user->linkAccount($account, $data['label'] ?: null, (bool) $data['requires_password']);

                Notification::make()
                    ->title(__('packstub-account-switcher::account-switcher.linked_accounts.notifications.linked'))
                    ->success()
                    ->send();
            });
    }

    protected function createSubAccountAction(): Action
    {
        return Action::make('create')
            ->label(__('packstub-account-switcher::account-switcher.linked_accounts.actions.create'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalDescription(__('packstub-account-switcher::account-switcher.linked_accounts.create_description'))
            ->modalWidth('md')
            ->schema([
                TextInput::make('name')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.email'))
                    ->email()
                    ->required()
                    ->rule(Rule::unique((new (AccountSwitcher::userModel()))->getTable(), 'email'))
                    ->autocomplete('off'),
                TextInput::make('password')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule('min:8')
                    ->autocomplete('new-password'),
                TextInput::make('label')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.label'))
                    ->maxLength(255),
                Toggle::make('requires_password')
                    ->label(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password'))
                    ->helperText(__('packstub-account-switcher::account-switcher.linked_accounts.fields.requires_password_help'))
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $user = Filament::auth()->user();

                $account = AccountSwitcherPlugin::get()->createSubAccount([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ], $user);

                $user->linkAccount($account, $data['label'] ?: null, (bool) $data['requires_password']);

                Notification::make()
                    ->title(__('packstub-account-switcher::account-switcher.linked_accounts.notifications.created'))
                    ->success()
                    ->send();
            });
    }
}
