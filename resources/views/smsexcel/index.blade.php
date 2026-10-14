
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
                            Import SMS records into monthly database tables.
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

                        <form
                            id="smsImportForm"
                            action="{{ route('sms-import.upload') }}"
                            method="POST"
                            enctype="multipart/form-data"
                        >
                            @csrf

                            <div class="mb-3">
                                <label for="excel_file" class="form-label">
                                    Select Excel File
                                </label>

                                <input
                                    type="file"
                                    name="excel_file"
                                    id="excel_file"
                                    class="form-control"
                                    accept=".xlsx,.xls"
                                    required
                                >

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
                                <button
                                    type="submit"
                                    id="uploadButton"
                                    class="btn btn-primary"
                                >
                                    <span id="buttonText">
                                        Import Excel
                                    </span>
                                </button>

                                <button
                                    type="reset"
                                    class="btn btn-outline-secondary"
                                >
                                    Reset
                                </button>
                            </div>

                            <div
                                id="uploadNotice"
                                class="alert alert-warning mt-3 d-none"
                            >
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

                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">
                                            Rows Processed
                                        </div>
                                        <h3 class="mb-0">
                                            {{ $result['processed'] }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">
                                            Rows Inserted
                                        </div>
                                        <h3 class="text-success mb-0">
                                            {{ $result['inserted'] }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">
                                            Invalid Dates
                                        </div>
                                        <h3 class="text-warning mb-0">
                                            {{ $result['invalid'] }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted">
                                            Database Failures
                                        </div>
                                        <h3 class="text-danger mb-0">
                                            {{ $result['failed'] }}
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
</x-app-layout>
