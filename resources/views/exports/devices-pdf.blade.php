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
        }
        th {
            background: #f2f2f2;
        }
        .table-title {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h2>{{ $title }}</h2>

@foreach($tables as $tableName => $rows)

    <div class="table-title">
        {{ strtoupper(str_replace('_', ' ', $tableName)) }}
    </div>

    <table>
        <thead>
        <tr>
            @foreach(($columns[$tableName] ?? array_keys((array)$rows->first())) as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
        </thead>

        <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach(($columns[$tableName] ?? array_keys((array)$row)) as $col)
                    <td>{{ data_get($row, $col) }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>

@endforeach

</body>
</html>