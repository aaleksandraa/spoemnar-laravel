<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('mail.tribute_notification.subject', ['memorial' => $memorialName], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:#f7f6f2;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fcfbf8;border:1px solid #e6dfd2;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px;">
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#1a1a1a;">{{ $appName }}</h1>
                            <p style="margin:0 0 12px;font-size:16px;line-height:1.5;">{{ trans('mail.tribute_notification.greeting', ['name' => $ownerName], $locale) }}</p>
                            <p style="margin:0 0 10px;font-size:15px;line-height:1.6;">{{ trans('mail.tribute_notification.line_1', ['memorial' => $memorialName], $locale) }}</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">{{ trans('mail.tribute_notification.line_2', [], $locale) }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;border:1px solid #e6dfd2;border-radius:10px;background:#f8f5ef;">
                                <tr>
                                    <td style="padding:16px;">
                                        <p style="margin:0 0 8px;font-size:14px;line-height:1.6;"><strong>{{ trans('mail.tribute_notification.author_name', [], $locale) }}:</strong> {{ $authorName }}</p>
                                        <p style="margin:0 0 12px;font-size:14px;line-height:1.6;"><strong>{{ trans('mail.tribute_notification.author_email', [], $locale) }}:</strong> {{ $authorEmail }}</p>
                                        <p style="margin:0 0 8px;font-size:14px;line-height:1.6;"><strong>{{ trans('mail.tribute_notification.message_label', [], $locale) }}:</strong></p>
                                        <div style="font-size:14px;line-height:1.7;color:#374151;background:#ffffff;border:1px solid #e6dfd2;border-radius:8px;padding:12px;">
                                            {!! nl2br(e($tributeMessage)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 22px;">
                                <a href="{{ $memorialUrl }}" style="display:inline-block;padding:12px 18px;background:#daa520;color:#1f2937;text-decoration:none;border-radius:8px;font-weight:700;">
                                    {{ trans('mail.tribute_notification.cta', [], $locale) }}
                                </a>
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">{{ trans('mail.tribute_notification.footer', [], $locale) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
