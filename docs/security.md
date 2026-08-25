# Security

Account switching touches authentication, so here is exactly what the plugin guarantees and what stays your responsibility.

## Every switch goes through one path

Impersonation, linked-account switches and developer logins all end in the same internal method. It:

1. Re-authenticates the **panel's own guard** as the target (`Auth::guard($panel->getAuthGuard())->login()`), never a guard of its own.
2. **Regenerates the session id**, so a session fixed before the switch is useless after it.
3. Clears Laravel's per-guard `password_hash_*` session keys so `AuthenticateSession` does not immediately log the new user out — and does log them out again if their password changes later, as usual.
4. Writes the [audit row](configuration.md#the-switch-log) and dispatches the events.

Nothing is stored in cookies beyond the regular session cookie; "remember me" is never set by a switch.

## Linked accounts

**Both accounts must be proven.** Linking an existing account requires that account's e-mail *and* password. Creating a sub-account happens from the account that will own it. A user can only ever link accounts they can already sign in to.

**Escalation asks for the target's password.** Switching to an account whose link has *requires password* on means entering the **target account's** password. A compromised low-privilege session therefore cannot reach the admin account without the admin password. The reverse direction of any new link requires a password by default; only someone signed in as that account can relax it.

**Panel access is re-checked.** You cannot switch to a linked account that cannot access any of your panels (`canAccessPanel()`), and after switching you land on a panel it may access.

**Your rules run too.** `canSwitchUsing()` lets you require 2FA on the target, block locked accounts, or keep switches inside a tenant.

### No escalation through impersonation

While impersonating, the "Switch to" menu is hidden, the Linked accounts page returns 403, and `switchToLinkedAccount()` throws. Otherwise an admin impersonating a user could hop into that user's linked admin account — or a user could set up a link precisely to be "impersonated into". Nested impersonation is denied for the same reason.

## Impersonation

- The action is hidden and the service throws unless `canImpersonate()` on the impersonator, `canBeImpersonated()` on the target, and `canImpersonateUsing()` on the plugin all agree (each only if defined).
- **Define `canImpersonate()`** on your model or set `canImpersonateUsing()`. With neither, any panel user can impersonate any other — fine for a prototype, not for production.
- The impersonator's id is kept in the session, not the URL; **Switch back** is a CSRF-protected `POST` inside the panel's authenticated route group.
- A banner is always visible while impersonating so nobody forgets which account they are acting as.

## Developer logins

- Two gates, both server-side: the environment allow-list in config (`['local']` by default) and the plugin's user list. A button that somehow rendered elsewhere would still get a 404 on submit.
- The route is throttled (10/minute) and CSRF-protected.
- Keep the environment list tight. Never add `production`.

## Audit trail

Each switch records who, to whom, why, which panel and guard, IP and user agent. Point a Filament resource at `AccountSwitch` to review it, or listen to `AccountSwitched` to alert in real time. If you disable `log_switches`, keep your own listener — silent switching is the one thing this plugin should never do.

## Reporting a vulnerability

E-mail [support@packstub.dev](mailto:support@packstub.dev) rather than opening a public issue. You will get a reply within two business days.
