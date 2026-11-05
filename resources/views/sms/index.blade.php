
<x-app-layout>

    <x-slot name="title">
        SMS Credit
    </x-slot>

    <div class="container-fluid py-4">

        <div class="row justify-content-center">

            <div class="col-lg-8 col-md-10">

                <div class="card border-0 shadow-sm">

                    <div class="card-header bg-white py-3">
                        <h5 class="mb-1 fw-bold">SMS Excel Import</h5>

                        <small class="text-muted">
                            Upload SMS Excel records into monthly database tables.
                        </small>
                    </div>

                    <div class="card-body p-4">

                        <form id="smsImportForm"
                              enctype="multipart/form-data">

                            @csrf

                            <div class="mb-4">

                                <label for="excel_file"
                                       class="form-label fw-semibold">
                                    Select Excel File
                                </label>

                                <input type="file"
                                       name="excel_file"
                                       id="excel_file"
                                       class="form-control"
                                       accept=".xlsx,.xls"
                                       required>

                                <small class="text-muted">
                                    Supported formats: XLSX, XLS
                                </small>

                            </div>

                            <button type="submit"
                                    id="uploadBtn"
                                    class="btn btn-primary w-100">

                                <span id="uploadBtnText">
                                    <i class="fas fa-upload me-2"></i>
                                    Upload Excel
                                </span>

                                <span id="uploadSpinner"
                                      class="spinner-border spinner-border-sm d-none"
                                      role="status">
                                </span>

                            </button>

                        </form>


                        <!-- Progress Section -->

                        <div id="progressArea" class="mt-4 d-none">

                            <div class="d-flex justify-content-between mb-2">

                                <span id="progressText"
                                      class="fw-semibold">
                                    Uploading Excel...
                                </span>

                                <strong id="progressPercent">
                                    0%
                                </strong>

                            </div>

                            <div class="progress"
                                 style="height: 22px;">

                                <div id="progressBar"
                                     class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar"
                                     style="width: 0%;">

                                    0%

                                </div>

                            </div>

                            <div id="progressMessage"
                                 class="text-muted small mt-2">

                                Please wait while your file is uploading.

                            </div>

                        </div>


                        <!-- Import Results -->

                        <div id="resultArea" class="mt-4"></div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <style>

        #progressArea .progress {
            border-radius: 6px;
            background: #e9ecef;
        }

        #progressBar {
            transition: width .3s ease;
        }

        .card {
            border-radius: 12px;
        }

    </style>


    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const form = document.getElementById('smsImportForm');

            if (!form) return;

            form.addEventListener('submit', function (e) {

                e.preventDefault();

                const fileInput = document.getElementById('excel_file');

                if (!fileInput.files.length) {
                    alert('Please select an Excel file.');
                    return;
                }

                const formData = new FormData(form);

                const xhr = new XMLHttpRequest();

                const button = document.getElementById('uploadBtn');
                const buttonText = document.getElementById('uploadBtnText');
                const spinner = document.getElementById('uploadSpinner');

                const progressArea = document.getElementById('progressArea');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const progressPercent = document.getElementById('progressPercent');
                const progressMessage = document.getElementById('progressMessage');

                const resultArea = document.getElementById('resultArea');

                button.disabled = true;
                spinner.classList.remove('d-none');
                buttonText.innerHTML = 'Processing...';

                resultArea.innerHTML = '';

                progressArea.classList.remove('d-none');

                progressBar.style.width = '0%';
                progressBar.innerText = '0%';

                progressPercent.innerText = '0%';

                progressText.innerText = 'Uploading Excel...';

                progressMessage.innerText = 'Please wait...';

                xhr.open(
                    'POST',
                    '{{ route("sms.import.store") }}',
                    true
                );

                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    '{{ csrf_token() }}'
                );

                // File upload progress

                xhr.upload.addEventListener('progress', function (e) {

                    if (e.lengthComputable) {

                        const percent = Math.round(
                            (e.loaded / e.total) * 100
                        );

                        progressBar.style.width = percent + '%';
                        progressBar.innerText = percent + '%';

                        progressPercent.innerText = percent + '%';

                        if (percent === 100) {

                            progressText.innerText = 'Importing records...';

                            progressMessage.innerText =
                                'File uploaded. Saving SMS records into monthly tables...';

                            progressBar.classList.remove(
                                'progress-bar-animated'
                            );

                        }

                    }

                });


                // Server response

                xhr.onload = function () {

                    button.disabled = false;

                    spinner.classList.add('d-none');

                    buttonText.innerHTML =
                        '<i class="fas fa-upload me-2"></i> Upload Excel';

                    let response;

                    try {

                        response = JSON.parse(xhr.responseText);

                    } catch (error) {

                        resultArea.innerHTML = `
                            <div class="alert alert-danger">
                                Invalid server response. Please check Laravel logs.
                            </div>
                        `;

                        return;
                    }

                    if (xhr.status >= 200 &&
                        xhr.status < 300 &&
                        response.success) {

                        progressBar.style.width = '100%';
                        progressBar.innerText = '100%';

                        progressPercent.innerText = '100%';

                        progressText.innerText = 'Import Completed';

                        progressMessage.innerText =
                            'All available Excel chunks have been processed.';

                        progressBar.classList.remove(
                            'progress-bar-animated'
                        );

                        resultArea.innerHTML = `

                            <div class="alert alert-success">

                                <h5 class="fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Import Successful!
                                </h5>

                                <hr>

                                <div class="row text-center">

                                    <div class="col-6 col-md-4 mb-3">
                                        <h4>${response.total_rows}</h4>
                                        <small>Total Rows</small>
                                    </div>

                                    <div class="col-6 col-md-4 mb-3">
                                        <h4>${response.imported}</h4>
                                        <small>Imported</small>
                                    </div>

                                    <div class="col-6 col-md-4 mb-3">
                                        <h4>${response.duplicates}</h4>
                                        <small>Duplicates</small>
                                    </div>

                                    <div class="col-6 col-md-4">
                                        <h4>${response.failed}</h4>
                                        <small>Failed</small>
                                    </div>

                                    <div class="col-6 col-md-4">
                                        <h4>${response.processed}</h4>
                                        <small>Processed</small>
                                    </div>

                                </div>

                            </div>

                        `;

                        // Clear selected file
                        form.reset();

                    } else {

                        progressText.innerText = 'Import Failed';

                        resultArea.innerHTML = `

                            <div class="alert alert-danger">
                                <strong>Import Failed!</strong>
                                <p class="mb-0">
                                    ${response.message || 'Something went wrong.'}
                                </p>
                            </div>

                        `;

                    }

                };


                xhr.onerror = function () {

                    button.disabled = false;

                    spinner.classList.add('d-none');

                    buttonText.innerHTML = 'Upload Excel';

                    resultArea.innerHTML = `

                        <div class="alert alert-danger">
                            Network error. Please try again.
                        </div>

                    `;

                };


                xhr.send(formData);

            });

        });

    </script>

</x-app-layout>
