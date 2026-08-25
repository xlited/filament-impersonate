<div class="fi-account-switcher-menu">
    @if ($accounts->isNotEmpty())
        <x-filament::dropdown placement="bottom-end" width="xs">
            <x-slot name="trigger">
                <x-filament::button color="gray" size="sm" icon="heroicon-o-user" outlined>
                    {{ __('packstub-account-switcher::account-switcher.menu.switch_to') }}
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($accounts as $account)
                    <x-filament::dropdown.list.item
                        wire:click="mountAction('switch', {{ \Illuminate\Support\Js::from(['account' => $account->getKey()]) }})"
                        :icon="$this->accountLabel($account) !== \Filament\Facades\Filament::getUserName($account) ? 'heroicon-o-tag' : 'heroicon-o-user'"
                    >
                        <span class="fi-account-switcher-menu-label">{{ $this->accountLabel($account) }}</span>
                        @if ($account->email ?? null)
                            <span class="fi-account-switcher-menu-email fi-color-gray" style="display:block;font-size:0.75rem;opacity:.7">{{ $account->email }}</span>
                        @endif
                    </x-filament::dropdown.list.item>
                @endforeach

                @if ($manageUrl)
                    <x-filament::dropdown.list.item :href="$manageUrl" tag="a" icon="heroicon-o-cog-6-tooth">
                        {{ __('packstub-account-switcher::account-switcher.menu.manage') }}
                    </x-filament::dropdown.list.item>
                @endif
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @endif

    <x-filament-actions::modals />
</div>
