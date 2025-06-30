<div class="mb-4">
    <h5 class="mb-1">{{ __('Profile Information') }}</h5>
    <p class="text-muted small mb-3">{{ __("Update your account's profile information and email address.") }}</p>
</div>
<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>
<form method="post" action="{{ route('profile.update') }}" class="row g-3">
    @csrf
    @method('patch')

    <div class="col-12">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div class="col-12">
        <label for="email" class="form-label">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
        <x-input-error class="mt-2" :messages="$errors->get('email')" />

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-warning small mb-1">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="btn btn-link p-0 align-baseline">{{ __('Click here to re-send the verification email.') }}</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="text-success small mb-0">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm bg-light p-3 d-flex flex-row align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="bi bi-envelope-paper-fill text-primary me-2 fs-4"></i>
                <div>
                    <label class="form-label mb-0 fw-semibold" for="email_preference">{{ __('Email Preference') }}</label>
                    <div class="text-muted small">{{ __('Enable or disable email notifications for your account.') }}</div>
                </div>
            </div>
            <div class="form-check form-switch m-0" style="transform: scale(1.5);">
                <input type="hidden" name="email_preference" value="0">
                <input class="form-check-input" type="checkbox" id="email_preference" name="email_preference" value="1" {{ $user->email_preference ? 'checked' : '' }}>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success small ms-2">{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
