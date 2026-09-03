<?php

namespace Packstub\AccountSwitcher;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Packstub\AccountSwitcher\Concerns\HasLinkedAccounts;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Events\AccountSwitched;
use Packstub\AccountSwitcher\Events\AccountSwitching;
use Packstub\AccountSwitcher\Exceptions\AccountSwitchDenied;
use Packstub\AccountSwitcher\Models\AccountSwitch;

/**
 * The session core shared by all three features. Every switch goes through
 * authenticateAs(): re-login on the panel guard, session regeneration, and
 * clearing Laravel's per-guard password hash so AuthenticateSession does not
 * log the new user out on the next request.
 */
class AccountSwitcher
{
    public const SESSION_KEY = 'packstub_account_switcher';

    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        return config('packstub-account-switcher.user_model')
            ?? config('auth.providers.users.model');
    }

    /**
     * The connection the plugin's tables live on: the configured one, or
     * the user model's own connection (null = the application default).
     */
    public static function connectionName(): ?string
    {
        $connection = config('packstub-account-switcher.connection');

        if (filled($connection)) {
            return $connection;
        }

        $userModel = static::userModel();

        return $userModel ? (new $userModel)->getConnectionName() : null;
    }

    public function plugin(): ?AccountSwitcherPlugin
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel?->hasPlugin(AccountSwitcherPlugin::ID)) {
            return null;
        }

        return $panel->getPlugin(AccountSwitcherPlugin::ID);
    }

    // ------------------------------------------------------------------
    // Impersonation
    // ------------------------------------------------------------------

    public function canImpersonate(Authenticatable $by, Authenticatable $target): bool
    {
        if ($by->getAuthIdentifier() === $target->getAuthIdentifier()) {
            return false;
        }

        if ($this->isImpersonating()) {
            return false;
        }

        $plugin = $this->plugin();

        if ($plugin && ! $plugin->hasImpersonation()) {
            return false;
        }

        if (method_exists($by, 'canImpersonate') && ! $by->canImpersonate($target)) {
            return false;
        }

        if (method_exists($target, 'canBeImpersonated') && ! $target->canBeImpersonated($by)) {
            return false;
        }

        if ($plugin && ! $plugin->evaluateCanImpersonate($by, $target)) {
            return false;
        }

        return true;
    }

    /**
     * @throws AccountSwitchDenied
     */
    public function impersonate(Authenticatable $target, ?Authenticatable $by = null): void
    {
        $by ??= $this->guard()->user();

        if (! $by || ! $this->canImpersonate($by, $target)) {
            throw AccountSwitchDenied::cannotImpersonate();
        }

        AccountSwitching::dispatch($by, $target, SwitchReason::Impersonation);

        session()->put(self::SESSION_KEY.'.impersonator_id', $by->getAuthIdentifier());
        session()->put(self::SESSION_KEY.'.impersonator_guard', $this->guardName());
        session()->put(self::SESSION_KEY.'.back_to', url()->previous());

        $this->authenticateAs($target);
        $this->record($by, $target, SwitchReason::Impersonation);

        AccountSwitched::dispatch($by, $target, SwitchReason::Impersonation);
    }

    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY.'.impersonator_id');
    }

    public function impersonator(): ?Authenticatable
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        $guard = session()->get(self::SESSION_KEY.'.impersonator_guard', $this->guardName());

        return auth()->guard($guard)->getProvider()->retrieveById(
            session()->get(self::SESSION_KEY.'.impersonator_id'),
        );
    }

    /**
     * Restore the impersonator's session and return the URL to send them to.
     */
    public function stopImpersonating(): string
    {
        $impersonator = $this->impersonator();
        $impersonated = $this->guard()->user();
        $backTo = session()->get(self::SESSION_KEY.'.back_to');

        session()->forget(self::SESSION_KEY);

        if (! $impersonator) {
            $this->guard()->logout();

            return Filament::getCurrentOrDefaultPanel()?->getLoginUrl() ?? url('/');
        }

        $this->authenticateAs($impersonator);

        if ($impersonated) {
            $this->record($impersonated, $impersonator, SwitchReason::ImpersonationEnded);
            AccountSwitched::dispatch($impersonated, $impersonator, SwitchReason::ImpersonationEnded);
        }

        return $backTo ?: $this->redirectUrlFor($impersonator);
    }

    // ------------------------------------------------------------------
    // Linked accounts
    // ------------------------------------------------------------------

    public function canSwitchToLinkedAccount(Authenticatable $from, Authenticatable $target): bool
    {
        if ($this->isImpersonating()) {
            return false;
        }

        if ($from->getAuthIdentifier() === $target->getAuthIdentifier()) {
            return false;
        }

        if (! $this->supportsLinkedAccounts($from) || ! $from->isLinkedTo($target)) {
            return false;
        }

        if ($this->accessiblePanel($target) === null) {
            return false;
        }

        $plugin = $this->plugin();

        if ($plugin && (! $plugin->hasLinkedAccounts() || ! $plugin->evaluateCanSwitch($from, $target))) {
            return false;
        }

        return true;
    }

    public function requiresPassword(Authenticatable $from, Authenticatable $target): bool
    {
        if (! $this->supportsLinkedAccounts($from)) {
            return true;
        }

        $link = $from->linkedAccounts()->whereKey($target->getAuthIdentifier())->first();

        return $link?->pivot->requires_password ?? true;
    }

    /**
     * @throws AccountSwitchDenied
     */
    public function switchToLinkedAccount(Authenticatable $target, ?string $password = null, ?Authenticatable $from = null): void
    {
        $from ??= $this->guard()->user();

        if ($this->isImpersonating()) {
            throw AccountSwitchDenied::whileImpersonating();
        }

        if (! $from || ! $this->canSwitchToLinkedAccount($from, $target)) {
            throw AccountSwitchDenied::notLinked();
        }

        if ($this->requiresPassword($from, $target) && ! $this->passwordMatches($target, $password)) {
            throw AccountSwitchDenied::invalidPassword();
        }

        AccountSwitching::dispatch($from, $target, SwitchReason::LinkedAccount);

        $this->authenticateAs($target);
        $this->record($from, $target, SwitchReason::LinkedAccount);

        AccountSwitched::dispatch($from, $target, SwitchReason::LinkedAccount);
    }

    public function supportsLinkedAccounts(?Authenticatable $user): bool
    {
        return $user !== null && in_array(HasLinkedAccounts::class, class_uses_recursive($user), true);
    }

    /**
     * Verify that the given plain-text password belongs to the account.
     */
    public function passwordMatches(Authenticatable $account, ?string $password): bool
    {
        $hash = $account->getAuthPassword();

        return filled($password) && filled($hash) && Hash::check($password, $hash);
    }

    // ------------------------------------------------------------------
    // Developer logins
    // ------------------------------------------------------------------

    public function developerLoginsEnabled(): bool
    {
        $environments = config('packstub-account-switcher.developer_logins.environments', ['local']);

        return app()->environment($environments)
            && ($this->plugin()?->hasDeveloperLogins() ?? false);
    }

    /**
     * @return Collection<int, Authenticatable>
     */
    public function developerLoginUsers(): Collection
    {
        if (! $this->developerLoginsEnabled()) {
            return collect();
        }

        return $this->plugin()->resolveDeveloperLoginUsers();
    }

    /**
     * @throws AccountSwitchDenied
     */
    public function developerLogin(Authenticatable $user): void
    {
        if (! $this->developerLoginsEnabled()) {
            throw AccountSwitchDenied::developerLoginsDisabled();
        }

        $allowed = $this->developerLoginUsers()->contains(
            fn (Authenticatable $candidate): bool => $candidate->getAuthIdentifier() === $user->getAuthIdentifier(),
        );

        if (! $allowed) {
            throw AccountSwitchDenied::developerLoginsDisabled();
        }

        $from = $this->guard()->user();

        AccountSwitching::dispatch($from, $user, SwitchReason::DeveloperLogin);

        session()->forget(self::SESSION_KEY);
        $this->authenticateAs($user);
        $this->record($from, $user, SwitchReason::DeveloperLogin);

        AccountSwitched::dispatch($from, $user, SwitchReason::DeveloperLogin);
    }

    // ------------------------------------------------------------------
    // Panels & redirects
    // ------------------------------------------------------------------

    /**
     * Where to land after switching: the plugin's redirectTo() if set, else the
     * current panel when the user may access it, else the first panel they may.
     */
    public function redirectUrlFor(Authenticatable $user): string
    {
        $redirect = $this->plugin()?->getRedirectTo();

        if ($redirect instanceof Closure) {
            $redirect = $redirect($user);
        }

        if (filled($redirect)) {
            return $redirect;
        }

        $panel = $this->accessiblePanel($user) ?? Filament::getCurrentOrDefaultPanel();

        return $panel?->getUrl() ?? url('/');
    }

    public function accessiblePanel(Authenticatable $user): ?Panel
    {
        $current = Filament::getCurrentOrDefaultPanel();

        if ($current && $this->canAccessPanel($user, $current)) {
            return $current;
        }

        return collect(Filament::getPanels())
            ->first(fn (Panel $panel): bool => $this->canAccessPanel($user, $panel));
    }

    /**
     * Mirrors Filament's own Authenticate middleware rule.
     */
    public function canAccessPanel(Authenticatable $user, Panel $panel): bool
    {
        if ($user instanceof FilamentUser) {
            return $user->canAccessPanel($panel);
        }

        return app()->environment('local');
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function guard(): StatefulGuard
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        return $panel ? $panel->auth() : auth()->guard();
    }

    protected function guardName(): string
    {
        return Filament::getCurrentOrDefaultPanel()?->getAuthGuard() ?? auth()->getDefaultDriver();
    }

    protected function authenticateAs(Authenticatable $user): void
    {
        $this->guard()->login($user);

        session()->regenerate();

        session()->forget(array_unique([
            'password_hash_'.$this->guardName(),
            'password_hash_'.auth()->getDefaultDriver(),
            'password_hash_'.config('auth.defaults.guard'),
        ]));
    }

    protected function record(?Authenticatable $from, Authenticatable $to, SwitchReason $reason): void
    {
        $enabled = $this->plugin()?->shouldLogSwitches() ?? config('packstub-account-switcher.log_switches', true);

        if (! $enabled) {
            return;
        }

        AccountSwitch::query()->create([
            'from_user_id' => $from?->getAuthIdentifier(),
            'to_user_id' => $to->getAuthIdentifier(),
            'reason' => $reason,
            'panel' => Filament::getCurrentOrDefaultPanel()?->getId(),
            'guard' => $this->guardName(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            'created_at' => now(),
        ]);
    }
}
