<x-form-section submit="updatePassword">
    <x-slot name="title">
        {{ __('profile.password.title') }}
    </x-slot>

    <x-slot name="description">
        {{ __('profile.password.description') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="current_password" value="{{ __('profile.password.current') }}" />
            <x-input id="current_password" type="password" class="mt-1 block w-full" wire:model.live="state.current_password" autocomplete="current-password" />
            <x-input-error for="current_password" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password" value="{{ __('profile.password.new') }}" />
            <x-input id="password" type="password" class="mt-1 block w-full" wire:model.live="state.password" autocomplete="new-password" />
            <x-input-error for="password" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="password_confirmation" value="{{ __('profile.password.confirm') }}" />
            <x-input id="password_confirmation" type="password" class="mt-1 block w-full" wire:model.live="state.password_confirmation" autocomplete="new-password" />
            <x-input-error for="password_confirmation" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('profile.password.saved') }}
        </x-action-message>

        <x-button>
            {{ __('profile.password.save') }}
        </x-button>
    </x-slot>
</x-form-section>
