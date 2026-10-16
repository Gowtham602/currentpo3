


<x-app-layout>
    <x-slot name="title">SMS Excel Import</x-slot>

    <!-- <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script> -->

    <div class="vertical-overlay"></div>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid py-4">

                <div class="d-flex flex-wrap justify-content-between
                            align-items-center mb-4">
                    <div>
                        <h3 class="mb-1">SMS Excel Import</h3>
                        <p class="text-muted mb-0">
                            Import  records into monthly database tables.
                        </p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please check the following:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Upload Excel File</h5>
                    </div>

                    <div class="card-body p-4">

                        <!-- <div class="alert alert-info">
                            <strong>How it works</strong>
                            <ul class="mb-0 mt-2">
                                <li>Upload an Excel file containing the 10 required columns.</li>
                                <li>The system extracts DATE from each Message.</li>
                                <li>Valid dates determine the monthly table name.</li>
                                <li>Missing or invalid dates are reported.</li>
                            </ul>
                        </div> -->

                        <form id="smsImportForm" action="{{ route('sms-import.upload') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="excel_file" class="form-label">
                                    Select Excel File
                                </label>

                                <input type="file" name="excel_file" id="excel_file" class="form-control"
                                    accept=".xlsx,.xls" required>

                                <small class="text-muted">
                                    Supported formats: XLSX and XLS. Maximum size: 20 MB.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Required Excel Columns
                                </label>

                                <!-- <div class="d-flex flex-wrap gap-2">
                                    @foreach ([
                                        'Recipient',
                                        'Status',
                                        'Description',
                                        'Credit Deducted',
                                        'Sent Time',
                                        'Sender ID',
                                        'TrackID',
                                        'Credit Type',
                                        'Circle',
                                        'Message'
                                    ] as $column)
                                        <span class="badge text-bg-light border text-dark">
                                            {{ $column }}
                                        </span>
                                    @endforeach
                                </div> -->
                            </div>

                            <!-- <div class="alert alert-light border">
                                <strong>Example routing:</strong>
                                <div class="mt-2">
                                    <code>DATE :27-09-2026</code>
                                    &rarr; <code>sep2026</code>
                                </div>
                                <div>
                                    <code>DATE :01-10-2026</code>
                                    &rarr; <code>oct2026</code>
                                </div>
                            </div> -->

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" id="uploadButton" class="btn btn-primary">
                                    <span id="buttonText">
                                        Import Excel
                                    </span>
                                </button>

                                <button type="reset" class="btn btn-outline-secondary">
                                    Reset
                                </button>
                            </div>

                            <div id="uploadNotice" class="alert alert-warning mt-3 d-none">
                                Upload in progress. Please do not refresh this page.
                            </div>
                        </form>
                    </div>
                </div>

                @if (session('import_result'))
                    @php
                        $result = session('import_result');
                    @endphp

                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Import Summary</h5>
                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                {{-- Rows Processed --}}
                                <div class="col-md-4 col-lg">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">Rows Processed</div>
                                        <h3 class="mb-0">
                                            {{ $result['processed'] ?? 0 }}
                                        </h3>
                                    </div>
                                </div>

                                {{-- Rows Inserted --}}
                                <div class="col-md-4 col-lg">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">Rows Inserted</div>
                                        <h3 class="text-success mb-0">
                                            {{ $result['inserted'] ?? 0 }}
                                        </h3>
                                    </div>
                                </div>

                                {{-- Rows Updated --}}
                                <div class="col-md-4 col-lg">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">Rows Updated</div>
                                        <h3 class="text-primary mb-0">
                                            {{ $result['updated'] ?? 0 }}
                                        </h3>
                                    </div>
                                </div>

                                {{-- Invalid Rows --}}
                                <div class="col-md-4 col-lg">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">Invalid Dates / Rows</div>
                                        <h3 class="text-warning mb-0">
                                            {{ $result['invalid'] ?? 0 }}
                                        </h3>
                                    </div>
                                </div>

                                {{-- Database Failures --}}
                                <div class="col-md-4 col-lg">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">Database Failures</div>
                                        <h3 class="text-danger mb-0">
                                            {{ $result['failed'] ?? 0 }}
                                        </h3>
                                    </div>
                                </div>

                            </div>


                            @if (!empty($result['errors']))
                                <div class="mt-4">
                                    <h6>Skipped or Failed Rows</h6>

                                    <div class="table-responsive">
                                        <table class="table table-bordered
                                                              table-sm align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 100px;">
                                                        No.
                                                    </th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach ($result['errors'] as $error)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $error }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif



                
{{-- Monthly SMS Records Filter --}}
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">View Monthly  Records</h5>
    </div>

    <div class="card-body">
        <form method="GET"
              action="{{ route('sms-import.index') }}"
              class="row g-3 align-items-end">

            <div class="col-md-4">
                <label for="month" class="form-label">
                    Select Month
                </label>

                <select name="month" id="month"
                        class="form-select" required>
                    <option value="">Choose Month</option>

                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}"
                            @selected((string) $selectedMonth === (string) $m)>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="year" class="form-label">
                    Select Year
                </label>

                <select name="year" id="year"
                        class="form-select" required>
                    <option value="">Choose Year</option>

                    @for ($y = 2026; $y <= 2030; $y++)
                        <option value="{{ $y }}"
                            @selected((string) $selectedYear === (string) $y)>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    Show Records
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Monthly SMS Summary by Branch --}}
@if ($selectedMonth && $selectedYear && $tableExists)
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                Monthly SMS Summary by Branch
            </h5>
            <small class="text-muted">
                {{ \Carbon\Carbon::create()
                    ->month((int) $selectedMonth)->format('F') }}
                {{ $selectedYear }}
            </small>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped
                              table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Branch</th>
                            <th>Total SMS</th>
                            <th>Points Added Count</th>
                            <th>Total Points Added</th>
                            <th>Redeem Count</th>
                            <th>Total Points Redeemed</th>
                            <th>OTP Count</th>
                            <th>Other SMS</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($summary as $item)
                            <tr>
                                <td>
                                    {{ $item->branch ?: 'Unknown' }}
                                </td>
                                <td>{{ $item->total_sms }}</td>
                                <td>{{ $item->points_added_count }}</td>
                                <td>{{ $item->points_added_total }}</td>
                                <td>{{ $item->points_redeemed_count }}</td>
                                <td>{{ $item->points_redeemed_total }}</td>
                                <td>{{ $item->otp_count }}</td>
                                <td>{{ $item->other_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    No  records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- Monthly SMS Records Table --}}
<!-- @if ($selectedMonth && $selectedYear)

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                SMS Records -
                {{ \Carbon\Carbon::create()->month((int) $selectedMonth)->format('F') }}
                {{ $selectedYear }}
            </h5>

            @if ($tableExists)
                <small class="text-muted">
                    Database table: {{ $table }}
                </small>
            @endif
        </div>

        <div class="card-body">
            @if (!$tableExists)

                <div class="alert alert-warning mb-0">
                    No monthly table exists for this month and year.
                </div>

            @elseif ($records->isEmpty())

                <div class="alert alert-info mb-0">
                    No SMS records found for this month.
                </div>

            @else

                <div class="table-responsive">
                    <table class="table table-bordered table-striped
                                  table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Credit Deducted</th>
                                <th>Sent Time</th>
                                <th>Sender ID</th>
                                <th>Track ID</th>
                                <th>Credit Type</th>
                                <th>Circle</th>
                                <th>Message</th>
                                <th>Message Date</th>
                                <th>Message Type</th>
                                <th>Card Number</th>
                                <th>Points Added</th>
                                <th>Points Redeemed</th>
                                <th>Total Points</th>
                                <th>Branch</th>
                                <th>SMS Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($records as $record)
                                <tr>
                                    <td>{{ $record->id }}</td>
                                    <td>{{ $record->recipient }}</td>
                                    <td>{{ $record->status }}</td>
                                    <td>{{ $record->description }}</td>
                                    <td>{{ $record->credit_deducted }}</td>
                                    <td>{{ $record->sent_time }}</td>
                                    <td>{{ $record->sender_id }}</td>
                                    <td>{{ $record->track_id }}</td>
                                    <td>{{ $record->credit_type }}</td>
                                    <td>{{ $record->circle }}</td>
                                    <td>{{ $record->message }}</td>
                                    <td>{{ $record->message_date }}</td>
                                    <td>{{ $record->message_type }}</td>
                                    <td>{{ $record->card_number }}</td>
                                    <td>{{ $record->points_added }}</td>
                                    <td>{{ $record->points_redeemed }}</td>
                                    <td>{{ $record->total_points }}</td>
                                    <td>{{ $record->branch }}</td>
                                    <td>{{ $record->sms_date }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
            {{ $records->links('pagination::bootstrap-5') }}
                </div>

            @endif
        </div>
    </div>

@endif -->





            </div>
            
        </div>
        
    </div>

    <script>
        $(function () {
            $('#smsImportForm').on('submit', function () {
                if (!this.checkValidity()) {
                    return;
                }

                $('#uploadButton').prop('disabled', true);
                $('#buttonText').text('Importing...');
                $('#uploadNotice').removeClass('d-none');
            });
        });
    </script>
<!--     
<style>
    /* Fix oversized Laravel pagination arrows */
    .pagination svg {
        width: 16px !important;
        height: 16px !important;
        max-width: 16px !important;
        max-height: 16px !important;
        display: inline-block !important;
        vertical-align: middle;
    }

    .pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
        padding-left: 0;
        list-style: none;
    }

    .pagination .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        min-height: 36px;
        padding: 6px 12px;
    }

    .pagination svg path {
        stroke-width: 1.5;
    }
</style> -->
</x-app-layout>