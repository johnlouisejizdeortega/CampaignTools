<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 34px 34px 56px 34px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #1f2933; font-size: 10px; }

        .header { border-bottom: 2px solid #1a73e8; padding-bottom: 10px; margin-bottom: 16px; }
        .header .brand { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #5f6368; }
        .header h1 { font-size: 19px; margin: 4px 0 2px 0; color: #202124; font-weight: bold; }
        .header .meta { font-size: 10px; color: #5f6368; }
        .header .meta span { margin-right: 14px; }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #202124; color: #ffffff; font-size: 9px; text-transform: uppercase;
            letter-spacing: .4px; text-align: left; padding: 7px 8px; font-weight: bold;
        }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #e8eaed; font-size: 10px; }
        tbody tr:nth-child(even) td { background: #f8f9fa; }
        .num { text-align: right; }
        .totals td { border-top: 2px solid #202124; font-weight: bold; background: #ffffff; }

        .footer {
            position: fixed; bottom: -34px; left: 0; right: 0; height: 30px;
            font-size: 8px; color: #9aa0a6; border-top: 1px solid #e8eaed; padding-top: 6px;
        }
        .footer .page:after { content: "Page " counter(page) " of " counter(pages); }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $brand ?? 'Local Services Ads' }}</div>
        <h1>{{ $title }}</h1>
        <div class="meta">
            @foreach ($meta as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $i => $h)
                    <th @class(['num' => ($aligns[$i] ?? 'left') === 'right'])>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td @class(['num' => ($aligns[$i] ?? 'left') === 'right'])>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" style="text-align:center; padding:24px; color:#9aa0a6;">No data for this report.</td></tr>
            @endforelse
            @isset($totals)
                <tr class="totals">
                    @foreach ($totals as $i => $cell)
                        <td @class(['num' => ($aligns[$i] ?? 'left') === 'right'])>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endisset
        </tbody>
    </table>

    <div class="footer">
        <span>{{ $brand ?? 'Local Services Ads' }} &middot; Generated {{ $generatedAt }}</span>
        <span class="page" style="float:right;"></span>
    </div>
</body>
</html>
