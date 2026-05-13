<div class="mb-4">
    <h5 class="mb-1">{{ __('Profile Information') }}</h5>
    <p class="text-muted small mb-3">{{ __("Update your account's profile information, email address, and display image.") }}</p>
</div>
<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>
@php
    $profileImage = $user->image ? asset('storage/users/' . $user->image) : asset('assets/images/profile_av.png');
@endphp
<style>
    .profile-image-panel {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .profile-image-preview {
        position: relative;
        display: grid;
        width: 104px;
        height: 104px;
        flex: 0 0 104px;
        place-items: center;
        overflow: hidden;
        border: 4px solid #fff;
        border-radius: 50%;
        background: #f3f4f6;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .15);
    }

    .profile-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-image-badge {
        position: absolute;
        right: 0;
        bottom: 4px;
        display: grid;
        width: 30px;
        height: 30px;
        place-items: center;
        border: 2px solid #fff;
        border-radius: 50%;
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 6px 14px rgba(13, 110, 253, .25);
    }

    .profile-image-help {
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    @media (max-width: 575px) {
        .profile-image-panel {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
<form method="post" action="{{ route('profile.update') }}" class="row g-3" enctype="multipart/form-data">
    @csrf
    @method('patch')

    <div class="col-12">
        <div class="profile-image-panel">
            <div class="profile-image-preview">
                <img id="profileImagePreview" src="{{ $profileImage }}" alt="{{ $user->name }} profile image"
                    onerror="this.onerror=null;this.src='{{ asset('assets/images/profile_av.png') }}';">
                <span class="profile-image-badge" aria-hidden="true">
                    <i class="icofont-camera"></i>
                </span>
            </div>
            <div class="flex-fill">
                <label for="profile_file" class="form-label mb-1">{{ __('Profile Image') }}</label>
                <p class="profile-image-help mb-3">
                    {{ __('Upload a clear JPG, PNG, or WebP image. Maximum size is 2MB.') }}
                </p>
                <input id="profile_file" name="file" type="file"
                    class="form-control @error('file') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                <x-input-error class="mt-2" :messages="$errors->get('file')" />
            </div>
        </div>
    </div>

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
<script>
    document.getElementById('profile_file')?.addEventListener('change', function(event) {
        const [file] = event.target.files;
        const preview = document.getElementById('profileImagePreview');

        if (file && preview) {
            preview.src = URL.createObjectURL(file);
            preview.onload = function() {
                URL.revokeObjectURL(preview.src);
            };
        }
    });
</script>
