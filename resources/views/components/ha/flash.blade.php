{{-- Session status message (success state after save / reset link / etc.). --}}
@if(session('status'))
    @php($statusMessage = match(session('status')) {
        'verification-link-sent' => 'A new verification link has been sent to your email address.',
        'password-updated' => 'Your password has been updated.',
        'two-factor-authentication-enabled' => 'Scan the QR code and enter a code to finish enabling 2FA.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is now enabled.',
        'two-factor-authentication-disabled' => 'Two-factor authentication has been disabled.',
        'recovery-codes-generated' => 'New recovery codes generated. Your previous codes no longer work.',
        default => session('status'),
    })
    <div role="status" class="ha-alert ha-alert--success"><x-ha.icon name="circle-check" /><div>{{ $statusMessage }}</div></div>
@endif
{{-- Blocked action (for example deleting something that is still in use). --}}
@if(session('error'))
    <div role="alert" class="ha-alert ha-alert--error"><x-ha.icon name="alert-triangle" /><div>{{ session('error') }}</div></div>
@endif
