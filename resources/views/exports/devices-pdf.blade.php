<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        h2 { margin-bottom: 10px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f2f2f2;
        }
        .table-title {
            margin-top: 15px;
            font-weight: bold;
        }
        .empty-text {
            margin: 8px 0 14px;
            color: #666;
        }
    </style>
</head>
<body>

<h2>{{ $title }}</h2>

@foreach($tables as $tableName => $rows)

    <div class="table-title">
        {{ strtoupper(str_replace('_', ' ', $tableName)) }}
    </div>

    @php
        $tableColumns = $columns[$tableName] ?? [];
    @endphp

    @if(($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty()) || (is_array($rows) && count($rows) === 0))
        <div class="empty-text">Tidak ada data.</div>
    @else
        <table>
            <thead>
            <tr>
                @foreach($tableColumns ?: array_keys((array) $rows->first()) as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
            </thead>

            <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($tableColumns ?: array_keys((array) $row) as $col)
                        <td>{{ data_get($row, $col) }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

@endforeach

</body>
</html>