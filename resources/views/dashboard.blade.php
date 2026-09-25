<x-app-layout>
    <x-slot name="title">Image Merger</x-slot>

    <!-- Vertical Overlay-->
    <div class="vertical-overlay"></div>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- page title -->
                <div class="row">
                    <div class="col-12">
                        <div
                            class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                            <h4 class="mb-sm-0">Image Merger</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Image Merger</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                {{-- Routes & Flash --}}
                <script>
                    window.flashMessages = {
                        success: @json(session('success')),
                        error: @json(session('error')),
                        validationErrors: @json($errors->all())
                    };
                    window.appUrl = "{{ url('/') }}";
                    window.routes = {
                        saveImage: "{{ route('save.image') }}",
                        processImage: "{{ route('image.process') }}",
                        getImages: "{{ route('get.images') }}"
                    };
                </script>

                {{-- ===== IMAGE MERGER FORM CARD ===== --}}
                <div class="row">
                    <div class="col-lg-12">
                        <form id="mergeForm" enctype="multipart/form-data">
                            @csrf
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Image Merger</h4>
                                </div><!-- end card header -->


                                <div class="card-body">
                                    <div class="live-preview">
                                        <div class="row mb-3">
                                            <div class="col-lg-6">
                                                <label class="form-label">Merge Direction</label>
                                                <select name="mode" class="form-select">
                                                    <option value="vertical">Vertical (Top → Bottom)</option>
                                                    <option value="horizontal">Horizontal (Left → Right)</option>
                                                </select>
                                                <div id="dynamicOptions" class="mt-2"></div>
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label">District</label>
                                                <select name="district_id" id="district_id" class="form-select"
                                                    required>
                                                    <option value="">Select District</option>
                                                    @foreach($districts ?? [] as $district)
                                                        <option value="{{ $district->id }}"
                                                            data-shortcode="{{ strtoupper($district->district_shortcode) }}">
                                                            {{ $district->district_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label class="form-label">
                                                    URL Expiry Date
                                                </label>

                                                <input type="date" name="expires_at" id="expires_at"
                                                    class="form-control" min="{{ now()->format('Y-m-d') }}">

                                                <div class="form-text">
                                                    After this date, the image URL will automatically become inactive.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>




                                <div class="card-body">
                                    <div class="dropzone" id="myDropzone">
                                        <div class="fallback">
                                            <input name="images[]" type="file" multiple="multiple">
                                        </div>
                                        <div class="dz-message needsclick">
                                            <div class="mb-3">
                                                <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                                            </div>

                                            <h4>Drop files here or click to upload.</h4>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0" id="dropzone-preview">
                                        <li class="mt-2" id="dropzone-preview-list">
                                            <!-- This is used as the file preview template -->
                                            <div class="border rounded">
                                                <div class="d-flex p-2 align-items-center">
                                                    <div class="flex-shrink-0 me-2 dz-drag-handle" style="cursor: grab;"
                                                        title="Drag to reorder">
                                                        <i class="ri-drag-move-2-line fs-18 text-muted"></i>
                                                    </div>
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar-sm bg-light rounded position-relative">
                                                            <span class="badge bg-primary rounded-pill dz-order-badge"
                                                                style="position:absolute; top:-6px; left:-6px; font-size:10px;">1</span>
                                                            <img data-dz-thumbnail class="img-fluid rounded d-block"
                                                                src="assets/images/new-document.png"
                                                                alt="Dropzone-Image" />
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="pt-1">
                                                            <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                                            <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                                            <strong class="error text-danger"
                                                                data-dz-errormessage></strong>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-3">
                                                        <button data-dz-remove
                                                            class="btn btn-sm btn-danger">Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                    <!-- end dropzon-preview -->
                                </div>
                                <!-- end card body -->
                                <div class="card-header"> </div>
                                {{-- Submit --}}
                                <div class="mt-2 mb-2 text-center">
                                    <button type="submit" id="generateBtn" class="btn btn-primary">Generate
                                        Image</button>
                                </div>
                            </div>
                        </form>
                        <!-- end card -->
                    </div> <!-- end col -->
                </div>
                <!-- end row -->

                {{-- ===== UPLOADED IMAGES TABLE CARD ===== --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Uploaded Images21</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example"
                                        class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Image</th>
                                                <th>Action</th>
                                                <th>Short URL</th>
                                                <th>Total Views</th>
                                                <th>Analysis views</th>
                                                <!-- <th>City</th> -->
                                                <th>Expiry Date</th>
                                                <th>Expiry status</th>
                                                <th>Date</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($images as $image)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <a href="{{ asset('storage/' . $image->file_path) }}"
                                                            target="_blank"
                                                            class="text-dark fw-semibold text-decoration-none">
                                                            {{ $image->image_name }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="{{route('image.edit', $image->short_code) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="ri-edit-line"></i>
                                                        </a>

                                                        <form action="{{ route('image.destroy', $image->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Delete this image?')">

                                                            @csrf
                                                            @method('DELETE')

                                                            <button class="btn btn-sm btn-danger">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>

                                                        </form>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <a href="{{ url('/' . $image->short_code) }}" target="_blank"
                                                                class="text-primary small text-truncate"
                                                                style="max-width:220px;">
                                                                {{ url('/' . $image->short_code) }}
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-light border"
                                                                onclick="copyLink(this, '{{ url('/' . $image->short_code) }}')">
                                                                <i class="ri-file-copy-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>{{ $image->clicks_count }}</td>
                                                    <!-- <td>
                                                                                                        <span class="badge bg-success">
                                                                                                            {{ $image->today_clicks }}
                                                                                                        </span>
                                                                                                    </td> -->
                                                    <!-- //analyticsview -->
                                                    <td>
                                                        <a href="{{ route('image.analysis', $image->id) }}"
                                                            class="btn btn-sm btn-primary">

                                                            <i class="ri-bar-chart-line"></i>
                                                            Analysis

                                                        </a>
                                                    </td>


                                                    {{-- EXPIRY DATE --}}
                                                    {{-- EXPIRY DATE --}}

                                                    <td>

                                                        <div class="d-flex align-items-center gap-2">

                                                            <span id="expiry-text-{{ $image->id }}"
                                                                class="text-muted small">

                                                                @if($image->expires_at)

                                                                    {{ \Carbon\Carbon::parse($image->expires_at)->format('d M Y') }}

                                                                @else

                                                                    No Expiry

                                                                @endif

                                                            </span>


                                                            {{-- ADD / EDIT EXPIRY BUTTON --}}

                                                            <button type="button" class="btn btn-sm btn-light border"
                                                                onclick="openExpiryModal(
                                                        {{ $image->id }},
                                                        '{{ $image->short_code }}',
                                                        '{{ $image->expires_at ? \Carbon\Carbon::parse($image->expires_at)->format('Y-m-d') : '' }}'
                                                    )" title="Edit Expiry Date">

                                                                <i class="ri-edit-line"></i>

                                                            </button>

                                                        </div>

                                                    </td>


                                                    {{-- STATUS --}}
                                                    <td>

                                                        @if(
                                                                $image->expires_at &&
                                                                \Carbon\Carbon::parse($image->expires_at)->startOfDay()->lt(today())
                                                            )

                                                            <span class="badge bg-warning text-dark">
                                                                Expired
                                                            </span>

                                                        @elseif(!$image->is_active)

                                                            <span class="badge bg-danger">
                                                                Inactive
                                                            </span>

                                                        @else

                                                            <span class="badge bg-success">
                                                                Active
                                                            </span>

                                                        @endif

                                                    </td>


                                                    {{-- CREATED DATE --}}
                                                    <td class="text-muted small">

                                                        {{ $image->created_at->format('d M Y') }}

                                                        <br>

                                                        {{ $image->created_at->format('h:i A') }}

                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- container-fluid -->
        </div><!-- page-content -->

        @include('layouts.footer')
    </div><!-- main-content -->

    <!-- END layout-wrapper -->

    {{-- ===== LOADER OVERLAY ===== --}}
    <div id="loader"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; color:white; font-size:24px; justify-content:center; align-items:center;">
        Processing...
    </div>

    {{-- ===== RESULT OFFCANVAS ===== --}}
    <div class="offcanvas offcanvas-end border-0" tabindex="-1" id="resultModal" aria-labelledby="resultModalLabel">
        <div class="d-flex align-items-center bg-primary bg-gradient p-3 offcanvas-header">
            <h5 class="m-0 me-2 text-white" id="resultModalLabel">
                <i class="ri-image-line me-2"></i> Final Output
            </h5>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div class="text-center mb-3">
                <img id="modalImage" src="" alt="Merged Image">
            </div>
            <div>
                <label class="form-label fw-semibold">Image Name</label>
                <div class="form-control-plaintext fw-semibold text-primary" id="previewName">—</div>
                <div class="form-text text-muted">Auto-generated from district and today's date. This will be used as
                    the filename and short URL.</div>
                <div class="text-danger" id="img_error"></div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">
                    <i class="ri-close-line me-1"></i> Close
                </button>
                <button id="modalDownload" class="btn btn-success">
                    <i class="ri-download-2-line me-1"></i> Download
                </button>
                <button id="saveBtn" class="btn btn-primary">
                    <i class="ri-links-line me-1"></i> Create Short URL
                </button>
            </div>
        </div>
    </div>

    {{-- ===== BACK TO TOP ===== --}}
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>

    {{-- ===== PRELOADER ===== --}}
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>


    {{-- ==========================================
    EXPIRY DATE MODAL
    ========================================== --}}

    <div class="modal fade" id="expiryModal" tabindex="-1" aria-labelledby="expiryModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                {{-- HEADER --}}

                <div class="modal-header">

                    <h5 class="modal-title" id="expiryModalLabel">
                        <i class="ri-calendar-line me-2"></i>
                        Update Expiry Date
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>


                {{-- BODY --}}

                <div class="modal-body">

                    <input type="hidden" id="expiry_image_id">

                    <input type="hidden" id="expiry_short_code">


                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Expiry Date
                        </label>

                        <input type="date" id="expiry_date_input" class="form-control"
                            min="{{ now()->format('Y-m-d') }}">

                        <div class="form-text">
                            Leave empty if this URL should have no expiry.
                        </div>

                    </div>


                    <div id="expiry_error" class="text-danger small"></div>

                </div>


                {{-- FOOTER --}}

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-primary" id="saveExpiryBtn">
                        <i class="ri-save-line me-1"></i>
                        Save
                    </button>

                </div>

            </div>

        </div>

    </div>



    <!-- <style>
        #resultModal {
            width: 500px;
            max-width: 100%;
        }

        /* #resultModal .offcanvas-body {
            padding: 20px;
        } */

        /* #modalImage {
            display: block;
            width: 100%;
            max-width: 100%;
            height: auto;
            max-height: calc(100vh - 220px);
            object-fit: contain;
            margin: 0 auto;
            border-radius: 8px;
            border: 1px solid #e9ebec;
        } */

        /* #resultModal .offcanvas-header {
            min-height: 65px;
        } */

        #loader {
            display: none;
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: 99999;
            color: #fff;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        @media (max-width: 576px) {
            #resultModal {
                width: 100%;
            }

            #resultModal .offcanvas-body {
                padding: 15px;
            }
        }
    </style> -->
    {{-- ===== PAGE SCRIPTS ===== --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        window.onload = function () {
            Dropzone.autoDiscover = false;

            var el = document.querySelector("#myDropzone");
            if (el && el.dropzone) {
                el.dropzone.destroy();
            }

            // Grab template HTML before clearing the list
            var previewTemplate = document.querySelector("#dropzone-preview-list").outerHTML;

            // Clear the preview list (remove the placeholder li)
            document.querySelector("#dropzone-preview").innerHTML = "";

            var myDropzone = new Dropzone("#myDropzone", {
                autoProcessQueue: false,
                url: "/",
                clickable: "#myDropzone",
                previewsContainer: "#dropzone-preview",
                previewTemplate: previewTemplate,
            });

            // ── Reordering (drag & drop) ────────────────────────────────────────
            function updatePreviewOrderBadges() {
                document.querySelectorAll('#dropzone-preview > li').forEach(function (li, idx) {
                    var badge = li.querySelector('.dz-order-badge');
                    if (badge) badge.textContent = idx + 1;
                });
            }

            myDropzone.on("addedfile", function () {
                updatePreviewOrderBadges();
            });

            myDropzone.on("removedfile", function () {
                updatePreviewOrderBadges();
            });

            if (typeof Sortable !== 'undefined') {
                Sortable.create(document.getElementById('dropzone-preview'), {
                    animation: 150,
                    handle: '.dz-drag-handle',
                    onEnd: function () {
                        // Rebuild myDropzone.files to match the new visual order
                        var newOrder = [];
                        document.querySelectorAll('#dropzone-preview > li').forEach(function (li) {
                            var match = myDropzone.files.find(f => f.previewElement === li);
                            if (match) newOrder.push(match);
                        });
                        if (newOrder.length === myDropzone.files.length) {
                            myDropzone.files = newOrder;
                        }
                        updatePreviewOrderBadges();
                    }
                });
            }

            document.getElementById('mergeForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (myDropzone.files.length === 0) {
                    alert('Please add at least one image.');
                    return;
                }

                const loader = document.getElementById('loader');
                const generateBtn = document.getElementById('generateBtn');

                loader.style.display = 'flex';

                if (generateBtn) {
                    generateBtn.disabled = true;
                    generateBtn.innerHTML =
                        '<i class="ri-loader-4-line me-1"></i> Generating...';
                }

                const formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                formData.append('mode', document.querySelector('select[name="mode"]').value);
                formData.append('district_id', document.getElementById('district_id').value);
                myDropzone.files.forEach(file => formData.append('images[]', file));

                try {
                    const response = await fetch(window.routes.processImage, {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });

                    const text = await response.text();

                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        console.error('Server response:', text);
                        throw new Error('Unexpected server response. Check the browser console.');
                    }

                    if (!response.ok) {
                        throw new Error(data.message || 'Image processing failed.');
                    }

                    if (!data.image) {
                        throw new Error(data.message || 'Image processing failed.');
                    }

                    const modalImage = document.getElementById('modalImage');

                    modalImage.src = data.image;
                    modalImage.dataset.filePath = data.file_path || '';

                    document.getElementById('previewName').textContent =
                        computePreviewName();

                    const resultModal =
                        document.getElementById('resultModal');

                    const offcanvas =
                        bootstrap.Offcanvas.getOrCreateInstance(resultModal);

                    offcanvas.show();

                } catch (err) {
                    console.error('Image processing error:', err);

                    Swal.fire({
                        icon: 'error',
                        title: 'Processing Failed',
                        text: err.message || 'Network error. Please try again.'
                    });

                } finally {
                    loader.style.display = 'none';

                    if (generateBtn) {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML =
                            '<i class="ri-image-line me-1"></i> Generate Image';
                    }
                }
            });

            function todayCode() {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const d = new Date();
                const dd = String(d.getDate()).padStart(2, '0');
                const mon = months[d.getMonth()];
                const yy = String(d.getFullYear()).slice(-2);
                return `${dd}${mon}${yy}`;
            }

            function computePreviewName() {
                const sel = document.getElementById('district_id');
                const opt = sel.options[sel.selectedIndex];
                const shortcode = opt ? opt.dataset.shortcode : '';
                if (!shortcode) return '—';
                // Suffix (_1, _2...) is decided by the server at save time; this is a preview only.
                return `POTHYS_${shortcode}_${todayCode()}_1`;
            }

            document.getElementById('modalDownload').addEventListener('click', function () {
                const a = document.createElement('a');
                a.href = document.getElementById('modalImage').src;
                const name = document.getElementById('previewName').textContent;
                a.download = (name && name !== '—' ? name : 'merged-image') + '.jpeg';
                a.click();
            });

            document.getElementById('saveBtn').addEventListener('click', async function () {
                document.getElementById('img_error').textContent = '';

                const filePath = document.getElementById('modalImage').dataset.filePath;
                const districtId = document.getElementById('district_id').value;
                const expiresAt = document.getElementById('expires_at').value;

                if (!districtId) { alert('Please select a district before saving.'); return; }

                try {
                    const response = await fetch(window.routes.saveImage, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ file_path: filePath, district_id: districtId, expires_at: expiresAt })
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        bootstrap.Offcanvas.getInstance(document.getElementById('resultModal')).hide();
                        location.reload();
                    } else {
                        document.getElementById('img_error').textContent = data.message || 'Failed to create short URL.';
                    }
                } catch (err) {
                    alert('Error saving. Please try again.');
                    console.error(err);
                }
            });
        };


        // ==========================================
        // SAVE EXPIRY DATE
        // ==========================================

        document.getElementById('saveExpiryBtn').addEventListener('click', async function () {

            const button = this;

            const imageId = document.getElementById('expiry_image_id').value;

            const shortCode = document.getElementById('expiry_short_code').value;

            const expiryDate = document.getElementById('expiry_date_input').value;

            const errorBox = document.getElementById('expiry_error');


            errorBox.textContent = '';


            // Disable button
            button.disabled = true;

            button.innerHTML =
                '<i class="ri-loader-4-line me-1"></i> Saving...';


            try {

                const response = await fetch(
                    "{{ route('image.update.expiry') }}",
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',

                            'X-CSRF-TOKEN':
                                document.querySelector(
                                    'input[name="_token"]'
                                ).value,

                            'Accept': 'application/json'
                        },

                        body: JSON.stringify({

                            image_id: imageId,

                            short_code: shortCode,

                            expires_at: expiryDate || null

                        })
                    }
                );


                const data = await response.json();


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Failed to update expiry date.'
                    );

                }


                // ==========================================
                // UPDATE TABLE WITHOUT PAGE RELOAD
                // ==========================================

                const expiryText =
                    document.getElementById(
                        'expiry-text-' + imageId
                    );


                if (expiryDate) {

                    const date = new Date(
                        expiryDate + 'T00:00:00'
                    );

                    const formattedDate =
                        date.toLocaleDateString(
                            'en-GB',
                            {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            }
                        );

                    expiryText.textContent = formattedDate;

                } else {

                    expiryText.textContent = 'No Expiry';

                }


                // ==========================================
                // CLOSE MODAL
                // ==========================================

                const modalElement =
                    document.getElementById('expiryModal');

                const modal =
                    bootstrap.Modal.getInstance(modalElement);

                modal.hide();


                // Success message

                if (typeof Swal !== 'undefined') {

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Expiry date updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                } else {

                    alert('Expiry date updated successfully.');

                }


            } catch (error) {

                console.error(error);

                errorBox.textContent =
                    error.message ||
                    'Failed to update expiry date.';

            } finally {

                button.disabled = false;

                button.innerHTML =
                    '<i class="ri-save-line me-1"></i> Save';

            }

        });

        function copyLink(button, url) {
            navigator.clipboard.writeText(url).then(() => {
                button.innerHTML = '<i class="ri-check-line text-success"></i>';
                setTimeout(() => {
                    button.innerHTML = '<i class="ri-file-copy-line"></i>';
                }, 2000);

            }).catch(err => {
                console.error(err);
            });
        }

        // ==========================================
        // EXPIRY DATE MODAL
        // ==========================================

        function openExpiryModal(imageId, shortCode, expiryDate) {

            document.getElementById('expiry_image_id').value = imageId;

            document.getElementById('expiry_short_code').value = shortCode;

            document.getElementById('expiry_date_input').value = expiryDate || '';

            document.getElementById('expiry_error').textContent = '';

            const modalElement = document.getElementById('expiryModal');

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            modal.show();
        }
    </script>

</x-app-layout>