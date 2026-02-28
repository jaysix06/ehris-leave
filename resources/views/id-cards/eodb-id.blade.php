<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; }
        .card {
            width: 100%;
            border: 1px solid #0f172a;
            border-radius: 8px;
            overflow: hidden;
        }
        .top {
            background: #1e40af;
            color: {{ $id_text_color ?? '#ffffff' }};
            padding: 10px 12px;
        }
        .name-last { font-size: 20px; font-weight: 700; line-height: 1; }
        .name-full { font-size: 12px; font-weight: 600; margin-top: 2px; }
        .division { font-size: 10px; font-weight: 700; margin-top: 6px; }
        .idno { font-size: 12px; font-weight: 700; margin-top: 6px; }
        .main { display: table; width: 100%; }
        .left, .right {
            display: table-cell;
            vertical-align: top;
            padding: 8px 10px;
        }
        .left { width: 60%; }
        .right { width: 40%; text-align: center; }
        .meta { font-size: 10px; line-height: 1.5; }
        .label { color: #334155; font-weight: 700; }
        .value { color: #0f172a; }
        .photo {
            width: 95px;
            height: 120px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            object-fit: cover;
            background: #f1f5f9;
        }
        .sign {
            margin-top: 6px;
            width: 90px;
            height: 30px;
            object-fit: contain;
        }
        .logo {
            margin-top: 8px;
            width: 55px;
            height: 55px;
            object-fit: contain;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="top">
        @php
            $parts = preg_split('/\s+/', trim((string) $fullname)) ?: [];
            $last = count($parts) > 0 ? strtoupper((string) array_pop($parts)) : strtoupper((string) $fullname);
            $firstMiddle = strtoupper(trim(implode(' ', $parts)));
        @endphp
        <div class="name-last">{{ $last }},</div>
        <div class="name-full">{{ $firstMiddle }}</div>
        <div class="division">{{ strtoupper((string) $division) }}</div>
        <div class="idno">ID NO. {{ (string) $employee_id }}</div>
    </div>
    <div class="main">
        <div class="left">
            <div class="meta">
                <div><span class="label">Emergency Name:</span> <span class="value">{{ (string) $emergency_name }}</span></div>
                <div><span class="label">Emergency Contact:</span> <span class="value">{{ (string) $emergency_contact }}</span></div>
            </div>
        </div>
        <div class="right">
            @if(!empty($photo_data_uri))
                <img class="photo" src="{{ $photo_data_uri }}" alt="Photo">
            @else
                <div class="photo"></div>
            @endif
            @if(!empty($sign_data_uri))
                <img class="sign" src="{{ $sign_data_uri }}" alt="Signature">
            @endif
            @if(!empty($logo_data_uri))
                <img class="logo" src="{{ $logo_data_uri }}" alt="Logo">
            @endif
        </div>
    </div>
</div>
</body>
</html>
