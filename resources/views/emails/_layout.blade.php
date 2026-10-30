{{-- Email-safe HeatAlert layout: table-based, inline styles, 600px max width. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#FAF7F2;font-family:'Segoe UI',Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF7F2;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    {{-- Brand header --}}
                    <tr>
                        <td style="padding:4px 8px 16px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="34" height="34" align="center" valign="middle"
                                        style="width:34px;height:34px;background-color:#E8590C;border-radius:8px;">
                                        <span style="display:inline-block;width:14px;height:14px;background-color:#FFFFFF;border-radius:4px;"></span>
                                    </td>
                                    <td width="10"></td>
                                    <td style="font-size:20px;font-weight:700;color:#0F3D3E;font-family:Arial,Helvetica,sans-serif;letter-spacing:-0.01em;">
                                        HeatAlert
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color:#FFFFFF;border:1px solid #E7DFD3;border-radius:14px;padding:32px 28px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 12px 0;color:#8A8278;font-size:12px;line-height:1.6;text-align:center;">
                            HeatAlert — Aide aux foyers pendant les fortes chaleurs.<br>
                            Cet e-mail a été envoyé automatiquement. Merci de ne pas y répondre.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
