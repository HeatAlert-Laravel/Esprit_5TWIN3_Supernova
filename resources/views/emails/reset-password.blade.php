@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#1C1A17;font-family:Arial,Helvetica,sans-serif;">
        Réinitialiser votre mot de passe
    </h1>

    <p style="margin:0 0 20px;color:#5E574E;font-size:15px;line-height:1.6;">
        Bonjour {{ $recipientName }},
    </p>

    <p style="margin:0 0 28px;color:#5E574E;font-size:15px;line-height:1.6;">
        Une demande de réinitialisation a été reçue pour votre compte HeatAlert.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#C2410C;">
                <a href="{{ $resetUrl }}"
                   style="display:inline-block;padding:13px 28px;border-radius:10px;background-color:#C2410C;color:#FFFFFF;font-size:15px;font-weight:700;text-decoration:none;font-family:Arial,Helvetica,sans-serif;">
                    Réinitialiser mon mot de passe
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;color:#5E574E;font-size:13px;line-height:1.6;">
        Ce lien expire dans {{ $expiresInMinutes }} minutes.
    </p>

    <p style="margin:0;color:#8A8278;font-size:13px;line-height:1.6;">
        Si vous n’avez pas demandé cette opération, ignorez cet e-mail.
    </p>
@endsection
