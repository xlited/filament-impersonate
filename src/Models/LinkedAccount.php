<?php

namespace Packstub\AccountSwitcher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Packstub\AccountSwitcher\AccountSwitcher;

/**
 * One directed link from `user` to `linked_user`. Links are created in
 * both directions, so every pair has two rows and each side can carry
 * its own label and password requirement.
 *
 * @property int $id
 * @property int|string $user_id
 * @property int|string $linked_user_id
 * @property string|null $label
 * @property bool $requires_password
 */
class LinkedAccount extends Pivot
{
    public $incrementing = true;

    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        return $this->connection ?? AccountSwitcher::connectionName();
    }

    public function getTable(): string
    {
        return config('packstub-account-switcher.tables.linked_accounts', 'linked_accounts');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_password' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AccountSwitcher::userModel(), 'user_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(AccountSwitcher::userModel(), 'linked_user_id');
    }
}
