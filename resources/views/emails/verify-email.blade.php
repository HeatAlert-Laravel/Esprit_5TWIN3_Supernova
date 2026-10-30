@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#1C1A17;font-family:Arial,Helvetica,sans-serif;">
        Confirmez votre adresse e-mail
    </h1>

    <p style="margin:0 0 20px;color:#5E574E;font-size:15px;line-height:1.6;">
        Bonjour {{ $recipientName }},
    </p>

    <p style="margin:0 0 28px;color:#5E574E;font-size:15px;line-height:1.6;">
        Confirmez votre adresse e-mail pour activer votre compte HeatAlert
        et accéder aux fonctionnalités de sécurité de votre foyer.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#C2410C;">
                <a href="{{ $verificationUrl }}"
                   style="display:inline-block;padding:13px 28px;border-radius:10px;background-color:#C2410C;color:#FFFFFF;font-size:15px;font-weight:700;text-decoration:none;font-family:Arial,Helvetica,sans-serif;">
                    Confirmer mon adresse
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;color:#8A8278;font-size:13px;line-height:1.6;">
        Si vous n’avez pas créé ce compte, ignorez simplement cet e-mail.
    </p>
@endsection
