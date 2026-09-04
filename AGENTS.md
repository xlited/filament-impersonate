# packstub/filament-account-switcher

Free Filament v5 plugin: switch between accounts — user impersonation, linked sub-accounts (password-confirmed), dev logins. Published on Packagist; listed on packstub.dev and filamentphp.com under the same name.

## Commands

```bash
composer test               # Pest suite
composer test:filter <name>
composer lint               # Pint
```

## Layout

- `src/`, `config/`, `database/`, `resources/` — package code.
- `docs/` customer docs (synced to the store on release); `docs/images/` screenshots produced from `../../demos/filament-account-switcher-demo/scripts/`.
- `art/` brand/listing art sources.

## Conventions

- PHP 8.3+, Pint, Pest; every change needs a test. Keep `CHANGELOG.md`/`UPGRADE.md` current.
- Release = a `## <version>` heading in `CHANGELOG.md`, then a `v<version>` tag on `main`. `.github/workflows/github-release.yml` creates the GitHub release from that changelog section (manual run with a `tag` input for backfills) — no manual release step.
- Listing assets/copy: use the `filament-plugin-listing` skill from the workspace root.
