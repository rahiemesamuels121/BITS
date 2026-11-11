<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', config('app.locale')) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;padding:24px 12px;background:#f3f5f9;color:#172b43;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;border-spacing:0;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
        <tr><td style="padding:28px 32px;border-bottom:3px solid #087f83;font-size:23px;font-weight:bold;">{{ config('app.name', 'BITS') }}</td></tr>
        <tr><td style="padding:32px;">
            <h1 style="margin:0 0 22px;font-size:22px;line-height:1.4;">{{ $subjectLine }}</h1>
            <div style="font-size:15px;line-height:1.8;color:#45566c;">{!! nl2br(e($bodyText)) !!}</div>
            @if($actionUrl)
                <p style="margin:28px 0;"><a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 22px;border-radius:6px;background:#087f83;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">{{ $actionText }}</a></p>
                <p style="font-size:12px;line-height:1.6;color:#627187;">If the button does not work, copy this address into your browser:<br><a href="{{ $actionUrl }}" style="color:#087f83;word-break:break-all;">{{ $actionUrl }}</a></p>
            @endif
        </td></tr>
        <tr><td style="padding:20px 32px;border-top:1px solid #e2e8f0;color:#627187;font-size:12px;line-height:1.7;">This is an automated message from {{ config('app.name', 'BITS') }}.</td></tr>
    </table>
</body>
</html>
