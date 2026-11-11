
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Monthly SMS Summary</title>

    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        p {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .total-points {
            background-color: #dbeafe;
            font-weight: bold;
        }

        .total-otp {
            background-color: #fef3c7;
            font-weight: bold;
        }

        .overall {
            background-color: #dcfce7;
            font-weight: bold;
        }

        .branch {
            font-weight: bold;
        }

        .no-records {
            text-align: center;
            padding: 12px;
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
                <th>Points Returned Count</th>
                <th>Total Points Count</th>
                <th>OTP Redeem Count</th>
                <th>OTP Profile Updated Count</th>
                <th>Total OTP Count</th>
                <th>Overall Count</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($summary as $item)

                <tr>
                    <td class="branch">
                        {{ $item->branch ?? '-' }}
                    </td>

                    <td>
                        {{ $item->points_added_count ?? 0 }}
                    </td>

                    <td>
                        {{ $item->points_redeemed_count ?? 0 }}
                    </td>

                    <td>
                        {{ $item->points_returned_count ?? 0 }}
                    </td>

                    <td class="total-points">
                        {{ $item->total_points_count ?? 0 }}
                    </td>

                    <td>
                        {{ $item->otp_redeem_count ?? 0 }}
                    </td>

                    <td>
                        {{ $item->otp_profile_updated_count ?? 0 }}
                    </td>

                    <td class="total-otp">
                        {{ $item->total_otp_count ?? 0 }}
                    </td>

                    <td class="overall">
                        {{ $item->overall_count ?? 0 }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="9" class="no-records">
                        No SMS records found for this month.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>

</body>
</html>
