<div class="mb-4">
    <h5 class="mb-1">{{ __('Update Password') }}</h5>
    <p class="text-muted small mb-3">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
</div>
<form method="post" action="{{ route('password.update') }}" class="row g-3">
    @csrf
    @method('put')

    <div class="col-12">
        <label for="current_password" class="form-label">{{ __('Current Password') }}</label>
        <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password">
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div class="col-12">
        <label for="password" class="form-label">{{ __('New Password') }}</label>
        <input id="password" name="password" type="password" class="form-control" autocomplete="new-password">
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div class="col-12">
        <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="col-12 d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        @if (session('status') === 'password-updated')
            <span class="text-success small ms-2">{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
