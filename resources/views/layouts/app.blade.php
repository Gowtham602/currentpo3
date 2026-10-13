    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }} | {{ config('app.name') }}</title>

        <!-- App favicon -->
		<link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
		
		<!--datatable css-->
		<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
		<!--datatable responsive css-->
		<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />

		<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
	
		<!-- dropzone css -->
		<link rel="stylesheet" href="{{ asset('assets/libs/dropzone/dropzone.css') }}" type="text/css" />



		<!-- Filepond css -->
		<link rel="stylesheet" href="{{ asset('assets/libs/filepond/filepond.min.css') }}" type="text/css" />
		<link rel="stylesheet" href="{{ asset('assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css') }}">

		<!-- Layout config Js -->
		<script src="{{ asset('assets/js/layout.js') }}"></script>

		<!-- Bootstrap Css -->
		<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />

		<!-- Icons Css -->
		<link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

		<!-- App Css -->
		<link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

		<!-- Custom Css -->
		<link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

	<style>
	
/* ================================
   OVERLAY
================================ */
.expiry-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 100000;
    background: rgba(0, 0, 0, 0.35);
    align-items: center;
    justify-content: center;
    padding: 12px;
    box-sizing: border-box;
}

/* ================================
   SAME SIZE FOR CALENDAR & TIME
================================ */
.expiry-popup {
    width: 340px;
    max-width: 100%;
    max-height: calc(100dvh - 24px);
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px;
    box-sizing: border-box;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

/* Keep both views at the same width */
#expiryDateView,
#expiryTimeView {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

/* ================================
   HEADER
================================ */
.expiry-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 16px;
}

.expiry-header button {
    flex-shrink: 0;
    border: 0;
    border-radius: 5px;
    padding: 7px 12px;
    background: #40538b;
    color: #fff;
    cursor: pointer;
}

.expiry-header button:hover {
    background: #334575;
}

/* ================================
   CALENDAR
================================ */
.expiry-weekdays,
.expiry-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    width: 100%;
    text-align: center;
    box-sizing: border-box;
}

.expiry-weekdays {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}

.expiry-day {
    width: 100%;
    min-width: 0;
    aspect-ratio: 1 / 1;
    border: 0;
    border-radius: 50%;
    background: #fff;
    color: #222;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
}

.expiry-day:disabled {
    color: #ccc;
    cursor: not-allowed;
}

.expiry-day.selected,
.expiry-day:hover:not(:disabled) {
    background: #1686e8;
    color: #fff;
}

/* ================================
   TIME PICKER
================================ */
.expiry-hours {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 7px;
    width: 100%;
    box-sizing: border-box;
}

.expiry-hours button {
    width: 100%;
    min-width: 0;
    min-height: 44px;
    padding: 10px 2px;
    border: 0;
    border-radius: 7px;
    background: #f0f2f5;
    color: #222;
    font-size: 13px;
    cursor: pointer;
    box-sizing: border-box;
}

.expiry-hours button:hover {
    background: #e0e7f1;
}

.expiry-hours button.active {
    background: #1686e8;
    color: #fff;
}

/* AM / PM labels */
.expiry-period-title {
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin: 10px 0 8px;
}

/* Minutes dropdown */
.expiry-minutes {
    width: 100%;
    min-height: 40px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    background: #fff;
    box-sizing: border-box;
}

/* Cancel and Confirm buttons */
.expiry-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin-top: 16px;
}

.expiry-actions button {
    width: 100%;
    min-height: 40px;
    border: 0;
    border-radius: 5px;
    padding: 9px 8px;
    color: #fff;
    cursor: pointer;
}

.expiry-actions .cancel-btn {
    background: #3674f5;
}

.expiry-actions .confirm-btn {
    background: #40538b;
}

/* ================================
   RESPONSIVE
================================ */
@media (max-width: 480px) {
    .expiry-overlay {
        padding: 10px;
    }

    .expiry-popup {
        width: 340px;
        max-width: 100%;
        padding: 14px;
        border-radius: 10px;
    }

    .expiry-header {
        margin-bottom: 12px;
    }

    .expiry-weekdays {
        font-size: 11px;
    }

    .expiry-day {
        font-size: 12px;
    }

    .expiry-hours {
        gap: 5px;
    }

    .expiry-hours button {
        min-height: 40px;
        font-size: 12px;
    }
}

@media (max-width: 350px) {
    .expiry-popup {
        padding: 10px;
    }

    .expiry-hours {
        gap: 4px;
    }

    .expiry-hours button {
        min-height: 36px;
        font-size: 11px;
    }
}

/* Short screens */
@media (max-height: 500px) {
    .expiry-popup {
        max-height: calc(100dvh - 20px);
    }
}
</style>
	
    </head>

    <body>
        <div id="layout-wrapper">
            @include('layouts.navigation')

            <span class="navbar-menu" id="two-column-menu" style="display:none"></span>
			<span id="scrollbar" style="display:none"></span>
			<span id="navbar-nav" style="display:none"></span>
			<span id="vertical-hover" style="display:none"></span>
			<span id="removeNotificationModal" style="display:none"></span>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- JAVASCRIPT -->
		<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
		<script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
		<script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
		<script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
		<script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
		<script src="{{ asset('assets/js/plugins.js') }}"></script>
		
		<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

		<!--datatable js-->
		<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
		<!-- jquery  -->
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
		<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
		<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

		<script src="{{ asset('assets/js/pages/datatables.init.js') }}"></script>
	
		<!-- dropzone min -->
		<script src="{{ asset('assets/libs/dropzone/dropzone-min.js') }}"></script>
		<!-- filepond js -->
		<script src="{{ asset('assets/libs/filepond/filepond.min.js') }}"></script>
		<script src="{{ asset('assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') }}"></script>
		<script src="{{ asset('assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') }}"></script>
		<script src="{{ asset('assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') }}"></script>
		<script src="{{ asset('assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') }}"></script>

		<!--<script src="{{ asset('assets/js/pages/form-file-upload.init.js') }}"></script>-->
		
		<!-- App js -->
		<script src="{{ asset('assets/js/app.js') }}"></script>
		<!-- sweet alert   -->
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	



			
		@stack('scripts')
    </body>

    </html>