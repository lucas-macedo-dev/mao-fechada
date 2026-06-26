<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.email_verify_subject') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f1f5f9;padding:48px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#1e1b4b;border-radius:12px 12px 0 0;padding:32px 48px;text-align:center;">
                            <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">Mão Fechada</p>
                            <p style="margin:6px 0 0;font-size:12px;color:#a5b4fc;letter-spacing:0.5px;text-transform:uppercase;">Controle financeiro pessoal</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:40px 48px 32px;">
                            <h1 style="margin:0 0 8px;font-size:20px;font-weight:600;color:#1e1b4b;">
                                {{ __('messages.email_verify_heading') }}
                            </h1>
                            <p style="margin:0 0 6px;font-size:15px;color:#374151;line-height:1.6;">
                                {{ __('messages.email_verify_greeting', ['name' => $name]) }}
                            </p>
                            <p style="margin:0 0 32px;font-size:15px;color:#6b7280;line-height:1.6;">
                                {{ __('messages.email_verify_body') }}
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}"
                                           style="display:inline-block;background-color:#4f46e5;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 36px;border-radius:8px;letter-spacing:0.1px;">
                                            {{ __('messages.email_verify_action') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:32px 0 0;font-size:13px;color:#9ca3af;line-height:1.5;text-align:center;">
                                {{ __('messages.email_verify_footer') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Link fallback --}}
                    <tr>
                        <td style="background-color:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:24px 48px;">
                            <p style="margin:0 0 6px;font-size:12px;color:#9ca3af;">
                                {{ __('messages.email_verify_fallback') }}
                            </p>
                            <p style="margin:0;font-size:11px;color:#4f46e5;word-break:break-all;">{{ $url }}</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
