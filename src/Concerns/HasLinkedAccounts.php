<?php

namespace Packstub\AccountSwitcher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Packstub\AccountSwitcher\Models\LinkedAccount;

/**
 * Add to the user model to enable linked-account switching.
 *
 * @mixin Model
 */
trait HasLinkedAccounts
{
    /**
     * @return BelongsToMany<static, $this, LinkedAccount>
     */
    public function linkedAccounts(): BelongsToMany
    {
        return $this->belongsToMany(static::class, (new LinkedAccount)->getTable(), 'user_id', 'linked_user_id')
            ->using(LinkedAccount::class)
            ->withPivot(['id', 'label', 'requires_password'])
            ->withTimestamps()
            ->orderBy('label');
    }

    /**
     * Link both accounts to each other. This side gets the given label and
     * password requirement; the reverse direction always requires a password
     * until relaxed from that account. An existing link is updated, not duplicated.
     */
    public function linkAccount(Model $account, ?string $label = null, bool $requiresPassword = true): void
    {
        if ($this->is($account)) {
            return;
        }

        DB::transaction(function () use ($account, $label, $requiresPassword): void {
            $this->linkedAccounts()->syncWithoutDetaching([
                $account->getKey() => ['label' => $label, 'requires_password' => $requiresPassword],
            ]);

            $account->linkedAccounts()->syncWithoutDetaching([
                $this->getKey() => ['label' => null, 'requires_password' => true],
            ]);
        });
    }

    /**
     * Remove the link in both directions.
     */
    public function unlinkAccount(Model $account): void
    {
        DB::transaction(function () use ($account): void {
            $this->linkedAccounts()->detach($account->getKey());
            $account->linkedAccounts()->detach($this->getKey());
        });
    }

    public function isLinkedTo(Model $account): bool
    {
        return $this->linkedAccounts()->whereKey($account->getKey())->exists();
    }
}
