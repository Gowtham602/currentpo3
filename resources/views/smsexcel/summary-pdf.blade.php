
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly SMS Summary</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
        }

        h2, p {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #999;
            padding: 5px 3px;
            text-align: center;
        }

        th {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <h2>Monthly Green Cards Summary by Branch</h2>
    <p>{{ $monthYear }}</p>

    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Points Added Count</th>
                <th>Points Redeemed Count</th>
                <th>Total Points SMS</th>
                <th>OTP Redeem</th>
                <th>OTP Profile Updated</th>
                <th>Total OTP</th>
                <th>Total Activity</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($summary as $item)
                <tr>
                    <td>{{ $item->branch }}</td>
                    <td>{{ $item->points_added_count }}</td>
                    <td>{{ $item->points_redeemed_count }}</td>
                    <td>{{ $item->total_points_sms_count }}</td>
                    <td>{{ $item->otp_redeem_count }}</td>
                    <td>{{ $item->otp_profile_updated_count }}</td>
                    <td>{{ $item->total_otp_count }}</td>
                    <td>{{ $item->total_activity_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No SMS records found for this month.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
