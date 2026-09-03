<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Packstub\AccountSwitcher\AccountSwitcher;

return new class extends Migration
{
    public function up(): void
    {
        $userModel = AccountSwitcher::userModel();

        Schema::connection(AccountSwitcher::connectionName())->create(config('packstub-account-switcher.tables.linked_accounts', 'linked_accounts'), function (Blueprint $table) use ($userModel): void {
            $table->id();
            $table->foreignIdFor($userModel, 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor($userModel, 'linked_user_id')->constrained((new $userModel)->getTable())->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->boolean('requires_password')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'linked_user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection(AccountSwitcher::connectionName())->dropIfExists(config('packstub-account-switcher.tables.linked_accounts', 'linked_accounts'));
    }
};
