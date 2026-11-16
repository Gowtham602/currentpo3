
<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">

    <title>SMS Recipient Report</title>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin-bottom: 5px;
        }

        .details {
            margin-bottom: 15px;
            text-align: center;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #555;
            padding: 8px;
        }

        th {
            background-color: #eeeeee;
            text-align: center;
        }

        td:first-child {
            text-align: center;
            width: 60px;
        }

        .footer {
            margin-top: 15px;
            text-align: right;
            font-weight: bold;
        }

    </style>

</head>

<body>

    <div class="header">

        <h2>POTHYS</h2>

        <h3>SMS RECIPIENT REPORT</h3>

    </div>


    <div class="details">

        <strong>Month & Year:</strong>
        {{ $monthYear }}

        <br>

        <strong>Branch:</strong>
        {{ $branch }}

        <br>

        <strong>Total Unique Recipients:</strong>
        {{ number_format($total) }}

    </div>


    <table>

        <thead>

            <tr>
                <th>S.No</th>
                <th>Branch</th>
                <th>Phone Number</th>
            </tr>

        </thead>


        <tbody>

            @forelse ($recipients as $item)

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>{{ $branch }}</td>

                    <td>{{ $item->recipient }}</td>

                </tr>

            @empty

                <tr>
                    <td colspan="3" style="text-align:center;">
                        No recipient records found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>


    
    <div class="footer">

        Total: {{ number_format($total) }}

    </div>

</body>
</html>
