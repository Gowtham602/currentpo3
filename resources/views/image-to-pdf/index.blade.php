
<x-app-layout>
    <x-slot name="title">Multiple Images to PDF</x-slot>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <div class="row mb-3">
                    <div class="col-12">
                        <h4>Multiple Images to PDF</h4>
                        <p class="text-muted">
                            Upload images, arrange their order and convert them into one PDF.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upload Images</h5>
                    </div>

                    <div class="card-body">

                        <form id="imagePdfForm">
                            @csrf

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        PDF Orientation
                                    </label>

                                    <select name="orientation"
                                            id="orientation"
                                            class="form-select"
                                            required>
                                        <option value="portrait">
                                            Vertical (A4 Portrait)
                                        </option>

                                        <option value="landscape">
                                            Horizontal (A4 Landscape)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="dropzone" id="imageDropzone">
                                <div class="fallback">
                                    <input name="images[]"
                                           type="file"
                                           multiple
                                           accept="image/jpeg,image/png">
                                </div>

                                <div class="dz-message needsclick">
                                    <div class="mb-2">
                                        <i class="ri-upload-cloud-2-line display-4 text-muted"></i>
                                    </div>

                                    <h5>Drop images here or click to upload</h5>

                                    <p class="text-muted mb-0">
                                        JPG, JPEG and PNG. Maximum 20 images,
                                        5 MB per image.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Image Preview</h6>

                                <ul class="list-unstyled mb-0"
                                    id="image-preview-list">
                                </ul>
                            </div>

                            <div class="mt-4" id="progressContainer"
                                 style="display:none;">

                                <div class="d-flex justify-content-between mb-2">
                                    <span id="progressStatus">
                                        Uploading images...
                                    </span>

                                    <strong id="progressPercentage">0%</strong>
                                </div>

                                <div class="progress" style="height: 12px;">
                                    <div id="uploadProgress"
                                         class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar"
                                         style="width:0%">
                                    </div>
                                </div>
                            </div>

                            <div id="errorMessage"
                                 class="alert alert-danger mt-3"
                                 style="display:none;">
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit"
                                        id="generatePdfBtn"
                                        class="btn btn-primary">
                                    <i class="ri-file-pdf-line me-1"></i>
                                    Convert to PDF
                                </button>

                                <button type="button"
                                        id="clearImagesBtn"
                                        class="btn btn-secondary ms-2">
                                    <i class="ri-delete-bin-line me-1"></i>
                                    Clear All
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        #imageDropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            min-height: 150px;
            cursor: pointer;
        }

        #imageDropzone .dz-message {
            margin: 30px 0;
        }

        #image-preview-list > li {
            margin-top: 10px;
        }

        .image-preview-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .image-preview-thumbnail {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
        }

        .image-drag-handle {
            cursor: grab;
        }

        .image-order-badge {
            min-width: 24px;
        }
    </style>

    {{-- SortableJS for drag and drop ordering --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            Dropzone.autoDiscover = false;

            const form = document.getElementById('imagePdfForm');
            const previewList = document.getElementById('image-preview-list');
            const generateBtn = document.getElementById('generatePdfBtn');
            const clearBtn = document.getElementById('clearImagesBtn');

            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('uploadProgress');
            const progressPercentage = document.getElementById('progressPercentage');
            const progressStatus = document.getElementById('progressStatus');
            const errorMessage = document.getElementById('errorMessage');

            const csrfToken = document.querySelector(
                '#imagePdfForm input[name="_token"]'
            ).value;

            const dropzone = new Dropzone('#imageDropzone', {
                url: "{{ route('image.pdf.convert') }}",

                autoProcessQueue: false,
                clickable: true,

                previewsContainer: '#image-preview-list',

                maxFiles: 20,
                maxFilesize: 5,

                acceptedFiles: '.jpg,.jpeg,.png,image/jpeg,image/png',

                addRemoveLinks: false,

                previewTemplate: `
                    <li>
                        <div class="image-preview-card d-flex align-items-center p-2 gap-3">

                            <div class="image-drag-handle px-2"
                                 title="Drag to reorder">
                                <i class="ri-drag-move-2-line fs-4 text-muted"></i>
                            </div>

                            <span class="badge bg-primary image-order-badge">
                                1
                            </span>

                            <img data-dz-thumbnail
                                 class="image-preview-thumbnail">

                            <div class="flex-grow-1">
                                <h6 class="mb-1" data-dz-name></h6>

                                <small class="text-muted" data-dz-size></small>

                                <div class="text-danger small"
                                     data-dz-errormessage></div>
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-danger image-remove-btn">
                                <i class="ri-delete-bin-line"></i>
                                Delete
                            </button>

                        </div>
                    </li>
                `,

                dictDefaultMessage: 'Drop images here or click to upload'
            });

            // Update order numbers shown in the preview.
            function updateOrderBadges() {

                previewList.querySelectorAll('li').forEach(function (item, index) {

                    const badge = item.querySelector('.image-order-badge');

                    if (badge) {
                        badge.textContent = index + 1;
                    }
                });
            }

            // Delete individual image.
            dropzone.on('addedfile', function (file) {

                const removeButton = file.previewElement.querySelector(
                    '.image-remove-btn'
                );

                removeButton.addEventListener('click', function () {
                    dropzone.removeFile(file);
                });

                updateOrderBadges();
            });

            dropzone.on('removedfile', function () {
                updateOrderBadges();
            });

            dropzone.on('maxfilesexceeded', function (file) {
                dropzone.removeFile(file);

                showError('You can upload a maximum of 20 images.');
            });

            dropzone.on('error', function (file, message) {
                showError(
                    typeof message === 'string'
                        ? message
                        : 'Invalid image. Please upload JPG or PNG.'
                );
            });

            // Drag and drop sorting.
            Sortable.create(previewList, {
                animation: 150,
                handle: '.image-drag-handle',

                onEnd: function () {

                    const orderedFiles = [];

                    previewList.querySelectorAll('li').forEach(function (item) {

                        const matchedFile = dropzone.files.find(function (file) {
                            return file.previewElement === item;
                        });

                        if (matchedFile) {
                            orderedFiles.push(matchedFile);
                        }
                    });

                    dropzone.files.splice(
                        0,
                        dropzone.files.length,
                        ...orderedFiles
                    );

                    updateOrderBadges();
                }
            });

            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }

            function clearError() {
                errorMessage.textContent = '';
                errorMessage.style.display = 'none';
            }

            function updateProgress(percent) {

                percent = Math.min(100, Math.max(0, percent));

                progressBar.style.width = percent + '%';
                progressPercentage.textContent = percent + '%';
            }

            // AJAX PDF generation with actual upload progress.
            form.addEventListener('submit', function (event) {

                event.preventDefault();

                clearError();

                if (dropzone.files.length === 0) {
                    showError('Please upload at least one image.');
                    return;
                }

                const orientation = document.getElementById('orientation').value;

                const formData = new FormData();

                formData.append('_token', csrfToken);
                formData.append('orientation', orientation);

                // Preserve the current drag-and-drop order.
                dropzone.files.forEach(function (file) {
                    formData.append('images[]', file, file.name);
                });

                generateBtn.disabled = true;
                clearBtn.disabled = true;

                generateBtn.innerHTML =
                    '<i class="ri-loader-4-line me-1"></i> Generating PDF...';

                progressContainer.style.display = 'block';
                progressStatus.textContent = 'Uploading images...';

                updateProgress(0);

                const xhr = new XMLHttpRequest();

                xhr.open(
                    'POST',
                    "{{ route('image.pdf.convert') }}",
                    true
                );

                xhr.setRequestHeader('Accept', 'application/pdf, application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                // Track actual browser upload progress.
                xhr.upload.addEventListener('progress', function (event) {

                    if (event.lengthComputable) {

                        const percent = Math.round(
                            (event.loaded / event.total) * 100
                        );

                        updateProgress(percent);

                        if (percent >= 100) {
                            progressStatus.textContent =
                                'Images uploaded. Generating PDF...';
                        }
                    }
                });

                xhr.responseType = 'blob';

                xhr.onload = async function () {

                    if (
                        xhr.status >= 200 &&
                        xhr.status < 300 &&
                        xhr.getResponseHeader('Content-Type')?.includes('application/pdf')
                    ) {

                        updateProgress(100);

                        progressStatus.textContent = 'PDF generated successfully!';

                        const blob = xhr.response;

                        const url = window.URL.createObjectURL(blob);

                        const downloadLink = document.createElement('a');

                        downloadLink.href = url;
                     downloadLink.download = 'ideal-convert.pdf';

                        document.body.appendChild(downloadLink);

                        downloadLink.click();

                        downloadLink.remove();

                        setTimeout(function () {
                            window.URL.revokeObjectURL(url);
                        }, 1000);

                        generateBtn.innerHTML =
                            '<i class="ri-check-line me-1"></i> PDF Downloaded';

                        setTimeout(function () {
                            generateBtn.innerHTML =
                                '<i class="ri-file-pdf-line me-1"></i> Convert to PDF';
                        }, 2000);

                    } else {

                        let message = 'PDF generation failed. Please try again.';

                        try {

                            const responseText = await xhr.response.text();

                            const responseData = JSON.parse(responseText);

                            message = responseData.message || message;

                            if (responseData.errors) {

                                const firstError = Object.values(
                                    responseData.errors
                                )[0];

                                if (firstError && firstError.length) {
                                    message = firstError[0];
                                }
                            }

                        } catch (error) {
                            console.error('Error response:', error);
                        }

                        showError(message);

                        progressStatus.textContent = 'PDF generation failed.';
                    }

                    finishRequest();
                };

                xhr.onerror = function () {

                    showError('Network error. Please check your connection.');

                    finishRequest();
                };

                xhr.onabort = function () {

                    showError('Upload was cancelled.');

                    finishRequest();
                };

                xhr.send(formData);
            });

            function finishRequest() {

                generateBtn.disabled = false;
                clearBtn.disabled = false;

                if (progressBar.classList.contains('progress-bar-animated')) {
                    progressBar.classList.remove('progress-bar-animated');
                }
            }

            // Clear all uploaded images.
            clearBtn.addEventListener('click', function () {

                if (dropzone.files.length === 0) {
                    return;
                }

                if (!confirm('Are you sure you want to remove all images?')) {
                    return;
                }

                dropzone.removeAllFiles(true);

                clearError();

                progressContainer.style.display = 'none';

                updateProgress(0);
            });

        });
    </script>

</x-app-layout>
