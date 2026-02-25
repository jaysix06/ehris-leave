@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $logoPath = public_path('dous.png');
    $inlineLogo = '';

    if (file_exists($logoPath)) {
        if (isset($message)) {
            $inlineLogo = $message->embed($logoPath);
        } else {
            $mimeType = mime_content_type($logoPath) ?: 'image/png';
            $inlineLogo = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
@endphp
<img src="{{ $inlineLogo }}" class="logo" alt="DepEd Ozamiz DAWN Protocol Logo">
</a>
</td>
</tr>
