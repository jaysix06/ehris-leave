<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $announcement->title }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.5; color: #111;">
    <div style="max-width: 680px; margin: 0 auto; padding: 24px;">
        <h2 style="margin: 0 0 12px 0;">{{ $announcement->title }}</h2>

        @if(!empty($announcement->content))
            <div style="white-space: pre-line; margin: 0 0 16px 0;">
                {{ $announcement->content }}
            </div>
        @endif

        @if(is_array($announcement->links) && count($announcement->links) > 0)
            <div style="margin-top: 12px;">
                <h3 style="margin: 0 0 8px 0; font-size: 14px;">Links</h3>
                <ul style="padding-left: 18px; margin: 0;">
                    @foreach($announcement->links as $link)
                        @php
                            $label = trim((string) ($link['label'] ?? ''));
                            $url = trim((string) ($link['url'] ?? ''));
                            $text = $label !== '' ? $label : $url;
                        @endphp
                        @if($url !== '')
                            <li style="margin: 4px 0;">
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $text }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
        <p style="margin: 0; font-size: 12px; color: #555;">
            This is an automated announcement from eHRIS.
        </p>
    </div>
</body>
</html>

