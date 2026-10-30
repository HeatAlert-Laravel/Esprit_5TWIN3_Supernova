@extends('layouts.app')
@section('title', 'Account and security')
@section('content')
<div class="ha-account-heading">
    <div><p class="ha-eyebrow">Your HeatAlert account</p><h1>Account and security</h1><p class="ha-muted">Identity and sign-in security. Your household data (phone, address, equipment) lives in the household profile.</p></div>
    <a href="{{ route('my-profile') }}" class="ha-btn ha-btn--outline">Back to household profile</a>
</div>
<x-ha.error-summary :all="true" />
<div class="ha-account-grid">
    <section class="ha-card ha-account-section" aria-labelledby="account-information">
        <h2 id="account-information">Account information</h2>
        <p>Your household address, phone and equipment stay in your <a class="ha-link" href="{{ route('my-profile') }}">HeatAlert profile</a>.</p>
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="ha-form-grid">@csrf @method('PUT')
            <x-ha.input name="name" error-bag="updateProfileInformation" label="Name" :value="old('name', $user->name)" autocomplete="name" required />
            <x-ha.input name="email" error-bag="updateProfileInformation" label="Email" type="email" :value="old('email', $user->email)" autocomplete="email" required />
            <x-ha.input name="current_password" error-bag="updateProfileInformation" id="email-current-password" label="Current password (required to change email)" type="password" autocomplete="current-password" toggle />
            <button class="ha-btn ha-btn--primary">Save account information</button>
        </form>
    </section>
    <section class="ha-card ha-account-section" aria-labelledby="profile-picture">
        <h2 id="profile-picture">Profile picture</h2>
        <div class="ha-account-avatar"><x-ha.avatar :name="$user->name" :src="$user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null" size="lg" /></div>
        @if($user->hasVerifiedEmail())
            <form method="POST" action="{{ route('account.avatar.update') }}" enctype="multipart/form-data" class="ha-form-grid">@csrf
                <label for="avatar">Choose a profile picture</label>
                <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                <p class="ha-help">JPG, PNG or WebP, up to 2 MB.</p>
                <button class="ha-btn ha-btn--primary">Upload picture</button>
            </form>
            @if($user->avatar_path)
                <form method="POST" action="{{ route('account.avatar.destroy') }}" class="mt-6">@csrf @method('DELETE')<button class="ha-btn ha-btn--outline">Remove picture</button></form>
            @endif
        @else
            <p class="ha-muted">Verify your email address to add or change your profile picture.</p>
        @endif
    </section>
    <section class="ha-card ha-account-section" aria-labelledby="account-password">
        <h2 id="account-password">Password</h2>
        <p>Use at least eight characters. Changing your password signs out your other browser sessions.</p>
        <form method="POST" action="{{ route('user-password.update') }}" class="ha-form-grid">@csrf @method('PUT')
            <x-ha.input name="current_password" error-bag="updatePassword" id="password-current-password" label="Current password" type="password" autocomplete="current-password" required toggle />
            <x-ha.input name="password" error-bag="updatePassword" id="new-password" label="New password" type="password" autocomplete="new-password" required toggle />
            <x-ha.input name="password_confirmation" error-bag="updatePassword" label="Confirm new password" type="password" autocomplete="new-password" required toggle />
            <button class="ha-btn ha-btn--primary">Update password</button>
        </form>
    </section>
    <section class="ha-card ha-account-section" aria-labelledby="email-verification">
        <h2 id="email-verification">Email verification</h2>
        <p><span class="ha-badge {{ $user->hasVerifiedEmail() ? 'ha-badge--success' : 'ha-badge--warning' }}">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</span></p>
        <p>{{ $user->email }}</p>
        @if(!$user->hasVerifiedEmail())
            <p>Verify this address to use your personal HeatAlert features.</p>
            <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="ha-btn ha-btn--primary">Resend verification email</button></form>
        @else
            <p class="ha-muted">Your email address is verified. Changing it requires a new verification.</p>
        @endif
    </section>
    <section class="ha-card ha-account-section ha-account-wide" id="two-factor" aria-labelledby="two-factor-heading">
        <h2 id="two-factor-heading">Two-factor authentication</h2>
        <p>Add an authenticator app to protect your account. Google Authenticator, Microsoft Authenticator and other TOTP apps work with HeatAlert.</p>
        <p><span class="ha-badge {{ $user->two_factor_confirmed_at ? 'ha-badge--success' : 'ha-badge--warning' }}">{{ $user->two_factor_confirmed_at ? 'Enabled' : ($user->two_factor_secret ? 'Awaiting confirmation' : 'Not enabled') }}</span></p>
        @if(!$securityUnlocked)
            <a href="{{ route('account.security') }}#two-factor" class="ha-btn ha-btn--outline">Confirm password to manage security</a>
        @elseif(!$user->two_factor_secret)
            <form method="POST" action="{{ route('two-factor.enable') }}">@csrf<button class="ha-btn ha-btn--primary">Enable 2FA</button></form>
        @else
            @if(!$user->two_factor_confirmed_at)
                <div class="ha-two-factor-setup">
                    <div class="ha-two-factor-qr" role="img" aria-label="Scan this QR code with your authenticator app">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <div><h3>Finish setting up your authenticator</h3><p>Scan the QR code, then enter the six-digit code. Two-factor protection starts only after confirmation.</p>
                        <details><summary>Enter a setup key manually</summary><code class="ha-account-secret">{{ decrypt($user->two_factor_secret) }}</code></details>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="ha-form-grid mt-6">@csrf
                            <x-ha.input name="code" error-bag="confirmTwoFactorAuthentication" label="Authentication code" autocomplete="one-time-code" inputmode="numeric" maxlength="6" required />
                            <button class="ha-btn ha-btn--primary">Confirm 2FA</button>
                        </form>
                    </div>
                </div>
            @else
                <details class="ha-recovery-codes"><summary>Show recovery codes</summary>
                    <p>Store these codes somewhere safe. Each code can be used once if you lose your phone.</p>
                    <ul>@foreach($user->recoveryCodes() as $recoveryCode)<li><code>{{ $recoveryCode }}</code></li>@endforeach</ul>
                </details>
                <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mt-6">@csrf<button class="ha-btn ha-btn--outline">Regenerate recovery codes</button></form>
            @endif
            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6">@csrf @method('DELETE')<button class="ha-btn ha-btn--danger-soft">{{ $user->two_factor_confirmed_at ? 'Disable 2FA' : 'Cancel 2FA setup' }}</button></form>
        @endif
    </section>
    <section class="ha-card ha-account-section ha-account-wide ha-account-danger" aria-labelledby="danger-zone">
        <h2 id="danger-zone">Danger zone</h2>
        <p>Deleting your account permanently removes your household profile, equipment, saved advice, votes and outage reports. This cannot be undone.</p>
        @if(!$securityUnlocked)
            <a href="{{ route('account.security') }}" class="ha-btn ha-btn--outline">Confirm password to manage security</a>
        @else
            <form method="POST" action="{{ route('account.destroy') }}" class="ha-form-grid" data-confirm="Delete your account and all personal HeatAlert data permanently?">@csrf @method('DELETE')
                <x-ha.input name="password" error-bag="deleteAccount" id="delete-password" label="Current password" type="password" autocomplete="current-password" required toggle />
                <label class="ha-check"><input type="checkbox" name="confirmation" value="1" required><span>I understand that my account and personal data will be permanently deleted.</span></label>
                <button class="ha-btn ha-btn--danger-soft">Delete my account</button>
            </form>
        @endif
    </section>
</div>
@endsection
