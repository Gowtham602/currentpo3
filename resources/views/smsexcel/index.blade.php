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
                            433 Import records into monthly database tables.
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
                                    accept=".xlsx,.xls,.csv" required>

                                <small class="text-muted">
                                    Supported formats: CSV XLSX and XLS. Maximum size: 200 MB.
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

                            <div id="uploadNotice" class="mt-4 d-none">
                                <div class="card border-primary shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="spinner-border text-primary" role="status"
                                                aria-label="Import in progress">
                                            </div>

                                            <div>
                                                <h6 class="mb-1" id="progressTitle">
                                                    Preparing Excel import...
                                                </h6>
                                                <small class="text-muted" id="progressMessage">
                                                    Please wait while the server processes your file.
                                                </small>
                                            </div>
                                        </div>

                                        <div class="progress mb-2" style="height: 22px;" role="progressbar"
                                            aria-label="File upload progress" aria-valuemin="0" aria-valuemax="100"
                                            aria-valuenow="0" id="progressContainer">

                                            <div id="uploadProgressBar"
                                                class="progress-bar progress-bar-striped progress-bar-animated"
                                                style="width: 0%">
                                                0%
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between flex-wrap gap-2">
                                            <small class="text-muted" id="progressPercent">
                                                File upload: 0%
                                            </small>

                                            <small class="text-muted" id="elapsedTime">
                                                Elapsed time: 00:00
                                            </small>
                                        </div>

                                        <div class="alert alert-info mt-3 mb-0">
                                            Do not close or refresh this page while importing.
                                        </div>
                                    </div>
                                </div>
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
                <div class="card border-0 shadow-sm mt-4">

                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-semibold">Monthly SMS Records</h5>
                    </div>

                    <div class="card-body">

                        <form method="GET" action="{{ route('sms-import.index') }}" class="row g-3 align-items-end">

                            <div class="col-md-4">
                                <label for="month_year" class="form-label fw-medium">
                                    Select Month & Year
                                </label>

                                <input type="text" name="month_year" id="month_year" class="form-control"
                                    placeholder="Select month and year"
                                    value="{{ request('month_year', now()->format('Y-m')) }}" required readonly>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    Show Records
                                </button>
                            </div>

                        </form>

                    </div>
                </div>


                @if ($selectedMonth && $selectedYear)

                    @if (!$tableExists)

                        <div class="alert alert-warning mt-4">
                            <strong>No monthly table found!</strong>
                            No SMS data available for
                            {{ \Carbon\Carbon::create()->month((int) $selectedMonth)->format('F') }}
                            {{ $selectedYear }}.
                        </div>

                    @else

                                {{-- Monthly Summary --}}
                                <div class="card border-0 shadow-sm mt-4">

                                    <div class="card-header bg-white py-3">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                            <div>
                                                <h5 class="mb-1 fw-semibold">
                                                    SMS Summary by Branch
                                                </h5>

                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::create()
                            ->month((int) $selectedMonth)
                            ->format('F') }}
                                                    {{ $selectedYear }}
                                                </small>
                                            </div>

                                            <div class="d-flex gap-2">

                                                <a href="{{ route('sms-summary.export.excel', [
                            'month_year' => sprintf('%04d-%02d', $selectedYear, $selectedMonth)
                        ]) }}" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-file-excel me-1"></i>
                                                    Excel
                                                </a>

                                                <a href="{{ route('sms-summary.export.pdf', [
                            'month_year' => sprintf('%04d-%02d', $selectedYear, $selectedMonth)
                        ]) }}" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-file-pdf me-1"></i>
                                                    PDF
                                                </a>

                                            </div>

                                        </div>
                                    </div>

                                    <div class="card-body p-0">

                                        <div class="table-responsive">

                                            <table class="table table-bordered table-hover align-middle mb-0">

                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="60">S.No</th>
                                                        <th>Branch</th>
                                                        <th>Points Added</th>
                                                        <th>Points Redeemed</th>
                                                        <th>Points Returned</th>
                                                        <th class="text-success">Total Points</th>
                                                        <th>OTP Redeem</th>
                                                        <th>OTP Profile Updated</th>
                                                        <th class="text-success">Total OTP</th>
                                                        <th class="text-primary">Overall Count</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    @forelse ($summary as $item)

                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td class="fw-semibold">
                                                                {{ $item->branch ?? 'Unknown' }}
                                                            </td>

                                                            <td>{{ number_format($item->points_added_count) }}</td>
                                                            <td>{{ number_format($item->points_redeemed_count) }}</td>
                                                            <td>{{ number_format($item->points_returned_count) }}</td>

                                                            <td class="fw-semibold text-success">
                                                                {{ number_format($item->total_points_count) }}
                                                            </td>

                                                            <td>{{ number_format($item->otp_redeem_count) }}</td>
                                                            <td>{{ number_format($item->otp_profile_updated_count) }}</td>

                                                            <td class="fw-semibold text-success">
                                                                {{ number_format($item->total_otp_count) }}
                                                            </td>

                                                            <td class="fw-semibold text-primary">
                                                                {{ number_format($item->overall_count) }}
                                                            </td>
                                                        </tr>

                                                    @empty

                                                        <tr>
                                                            <td colspan="9" class="text-center text-muted py-4">
                                                                No SMS records found for this month.
                                                            </td>
                                                        </tr>

                                                    @endforelse

                                                </tbody>

                                                @if ($summary->isNotEmpty())

                                                    <tfoot>
                                                        <tr class="table-primary fw-bold">
                                                            <td></td>
                                                            <td>GRAND TOTAL</td>

                                                            <td>{{ number_format($grandTotal['points_added_count'] ?? 0) }}</td>
                                                            <td>{{ number_format($grandTotal['points_redeemed_count'] ?? 0) }}</td>
                                                            <td>{{ number_format($grandTotal['points_returned_count'] ?? 0) }}</td>

                                                            <td>{{ number_format($grandTotal['total_points_count'] ?? 0) }}</td>

                                                            <td>{{ number_format($grandTotal['otp_redeem_count'] ?? 0) }}</td>
                                                            <td>{{ number_format($grandTotal['otp_profile_updated_count'] ?? 0) }}</td>

                                                            <td>{{ number_format($grandTotal['total_otp_count'] ?? 0) }}</td>

                                                            <td>{{ number_format($grandTotal['overall_count'] ?? 0) }}</td>

                                                        </tr>
                                                    </tfoot>

                                                @endif

                                            </table>

                                        </div>

                                    </div>

                                </div>

                    @endif

                @endif






            </div>

        </div>

    </div>





    <script>


        (function () {
            function initializeExcelUpload() {
                const form = document.getElementById('smsImportForm');

                if (!form) return;

                const fileInput = document.getElementById('excel_file');
                const uploadButton = document.getElementById('uploadButton');
                const buttonText = document.getElementById('buttonText');
                const notice = document.getElementById('uploadNotice');
                const progressBar = document.getElementById('uploadProgressBar');
                const progressTitle = document.getElementById('progressTitle');
                const progressMessage = document.getElementById('progressMessage');
                const progressPercent = document.getElementById('progressPercent');
                const elapsedTime = document.getElementById('elapsedTime');
                const progressContainer = document.getElementById('progressContainer');

                let requestRunning = false;
                let timerInterval = null;
                let startTime = 0;

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (requestRunning) return;

                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    if (!fileInput.files || !fileInput.files.length) {
                        alert('Please select an Excel file.');
                        return;
                    }

                    requestRunning = true;
                    startTime = Date.now();

                    // SHOW LOADING IMMEDIATELY
                    notice.classList.remove('d-none');
                    notice.style.display = 'block';
                    notice.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    uploadButton.disabled = true;
                    buttonText.textContent = 'Uploading...';

                    progressTitle.textContent = 'Uploading Excel file...';
                    progressMessage.textContent =
                        'Please wait while your Excel file is being uploaded.';

                    progressBar.className =
                        'progress-bar progress-bar-striped progress-bar-animated';

                    progressBar.style.width = '0%';
                    progressBar.textContent = '0%';

                    progressContainer.setAttribute('aria-valuenow', '0');
                    progressPercent.textContent = 'File upload: 0%';

                    elapsedTime.textContent = 'Elapsed time: 00:00';

                    timerInterval = setInterval(function () {
                        const seconds = Math.floor(
                            (Date.now() - startTime) / 1000
                        );

                        const minutes = Math.floor(seconds / 60);
                        const remaining = seconds % 60;

                        elapsedTime.textContent =
                            'Elapsed time: ' +
                            String(minutes).padStart(2, '0') + ':' +
                            String(remaining).padStart(2, '0');
                    }, 1000);

                    const xhr = new XMLHttpRequest();
                    const formData = new FormData(form);

                    xhr.open('POST', form.action, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'text/html');

                    // Actual file transfer percentage
                    xhr.upload.addEventListener('progress', function (e) {
                        if (!e.lengthComputable) return;

                        const percent = Math.round(
                            (e.loaded / e.total) * 100
                        );

                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';

                        progressContainer.setAttribute(
                            'aria-valuenow',
                            String(percent)
                        );

                        progressPercent.textContent =
                            'File upload: ' + percent + '%';

                        if (percent >= 100) {
                            progressTitle.textContent =
                                'Processing Excel records...';

                            progressMessage.textContent =
                                'Upload complete. Saving records to the database. ' +
                                'Large files may take several minutes.';

                            buttonText.textContent = 'Importing...';

                            // Indeterminate processing animation
                            progressBar.textContent = 'Processing...';
                            progressBar.style.width = '100%';

                            progressPercent.textContent =
                                'File uploaded — waiting for import to finish';
                        }
                    });


                    xhr.addEventListener('load', function () {
                        clearInterval(timerInterval);

                        if (xhr.status >= 200 && xhr.status < 300) {
                            progressTitle.textContent = 'Import response received';
                            progressMessage.textContent =
                                'Displaying the saved records and import results...';

                            progressBar.className = 'progress-bar bg-success';
                            progressBar.style.width = '100%';
                            progressBar.textContent = 'Complete';

                            progressContainer.setAttribute('aria-valuenow', '100');
                            progressPercent.textContent = 'Import request completed';

                            /*
                             * Laravel may have redirected back to the import page.
                             * xhr.responseText contains that page, including flashed
                             * import results and validation errors.
                             *
                             * Do not reload again: that could consume the flash
                             * message before the user sees it.
                             */
                            if (xhr.responseURL &&
                                new URL(xhr.responseURL).origin !== window.location.origin) {
                                showError(
                                    'Unexpected response',
                                    'The server redirected to another website. Please check authentication.'
                                );
                                return;
                            }

                            if (!xhr.responseText || !xhr.responseText.trim()) {
                                showError(
                                    'Empty server response',
                                    'The server returned no page content. Check your Laravel controller.'
                                );
                                return;
                            }

                            document.open();
                            document.write(xhr.responseText);
                            document.close();

                        } else {
                            showError(
                                'Import failed',
                                'HTTP ' + xhr.status +
                                '. Check the Laravel validation errors and storage/logs/laravel.log.'
                            );
                        }
                    });


                    xhr.addEventListener('error', function () {
                        clearInterval(timerInterval);

                        showError(
                            'Network error',
                            'Unable to communicate with the server. ' +
                            'Please check your connection.'
                        );
                    });

                    xhr.addEventListener('abort', function () {
                        clearInterval(timerInterval);
                        showError('Upload cancelled', 'The request was cancelled.');
                    });

                    function showError(title, message) {
                        requestRunning = false;

                        uploadButton.disabled = false;
                        buttonText.textContent = 'Import Excel';

                        progressTitle.textContent = title;
                        progressMessage.textContent = message;

                        progressBar.className = 'progress-bar bg-danger';
                        progressBar.textContent = 'Failed';

                        progressPercent.textContent = 'Import unsuccessful';
                    }

                    xhr.send(formData);
                });
            }

            // Works whether the DOM is still loading or already ready.
            if (document.readyState === 'loading') {
                document.addEventListener(
                    'DOMContentLoaded',
                    initializeExcelUpload
                );
            } else {
                initializeExcelUpload();
            }
        })();


        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('month_year');

            if (!input || typeof flatpickr === 'undefined' ||
                typeof monthSelectPlugin === 'undefined') {
                console.error('Flatpickr or monthSelect plugin is not loaded.');
                return;
            }

            flatpickr(input, {
                plugins: [
                    new monthSelectPlugin({
                        shorthand: false,
                        dateFormat: 'Y-m',
                        altFormat: 'F Y'
                    })
                ],

                altInput: true,
                altFormat: 'F Y',
                dateFormat: 'Y-m',

                defaultDate: input.value || new Date(),

                minDate: '2026-01',
                maxDate: '2030-12',

                allowInput: false
            });
        });

    </script>
</x-app-layout>