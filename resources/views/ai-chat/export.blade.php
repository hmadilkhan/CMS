<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title !== '' ? $title : 'SolenAssist Export' }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #2b2b2b; font-size: 11px; margin: 24px; }
        .brand { color: #b9791f; font-size: 16px; font-weight: bold; margin: 0 0 2px; }
        .meta { color: #888; font-size: 10px; margin: 0 0 14px; }
        .title { font-size: 12px; font-weight: bold; margin: 0 0 10px; color: #3a2310; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e7d9c4; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #fff4e8; color: #6b4a1c; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
        tr:nth-child(even) td { background: #fbf7f0; }
        .footer { margin-top: 14px; color: #aaa; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <p class="brand">SolenAssist — Solen Energy CRM</p>
    <p class="meta">Generated {{ $generatedAt }} · {{ count($rows) }} row{{ count($rows) === 1 ? '' : 's' }}</p>

    @if ($title !== '')
        <p class="title">{{ $title }}</p>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ $row[$column] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Read-only export · SolenAssist</p>
</body>
</html>
