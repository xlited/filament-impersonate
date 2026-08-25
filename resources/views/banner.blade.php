@php
    use Filament\Facades\Filament;
    use Packstub\AccountSwitcher\Facades\AccountSwitcher;

    $impersonated = Filament::auth()->user();
    $impersonator = AccountSwitcher::impersonator();
    $panel = Filament::getCurrentPanel();
    $dark = $style === 'dark';
@endphp

<style>
    .fi-account-switcher-banner {
        position: fixed;
        {{ $position === 'top' ? 'top: 0;' : 'bottom: 0;' }}
        inset-inline: 0;
        z-index: 39;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        height: 3rem;
        padding-inline: 1rem;
        font-size: 0.875rem;
        background-color: {{ $dark ? '#111827' : '#f3f4f6' }};
        color: {{ $dark ? '#f9fafb' : '#111827' }};
        border-{{ $position === 'top' ? 'bottom' : 'top' }}: 1px solid {{ $dark ? '#374151' : '#d1d5db' }};
    }

    .fi-account-switcher-banner form {
        display: inline;
    }

    .fi-account-switcher-banner button {
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 600;
        background-color: {{ $dark ? '#f9fafb' : '#111827' }};
        color: {{ $dark ? '#111827' : '#f9fafb' }};
    }

    body .fi-layout {
        {{ $position === 'top' ? 'padding-top: 3rem;' : 'padding-bottom: 3rem;' }}
    }

    @if ($position === 'top')
        body .fi-topbar,
        body .fi-sidebar {
            top: 3rem;
        }
    @endif

    @media print {
        .fi-account-switcher-banner {
            display: none;
        }
    }
</style>

<div class="fi-account-switcher-banner" role="status">
    <span>
        {!! __('packstub-account-switcher::account-switcher.banner.message', [
            'impersonated' => '<strong>'.e(Filament::getUserName($impersonated)).'</strong>',
            'impersonator' => '<strong>'.e($impersonator ? Filament::getUserName($impersonator) : '—').'</strong>',
        ]) !!}
    </span>

    @if ($panel?->hasPlugin(\Packstub\AccountSwitcher\AccountSwitcherPlugin::ID))
        <form method="POST" action="{{ route($panel->generateRouteName('account-switcher.switch-back')) }}">
            @csrf
            <button type="submit">{{ __('packstub-account-switcher::account-switcher.banner.switch_back') }}</button>
        </form>
    @endif
</div>
