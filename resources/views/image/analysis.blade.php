<x-app-layout>

    <x-slot name="title">
        Image Analysis
    </x-slot>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div
                            class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                            <h4 class="mb-sm-0">Image Analysis</h4>

                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        Image Analysis
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            {{ $image->image_name }}
                        </h5>
                    </div>

                    <div class="card-body">

                        <form method="GET">

                            <div class="row">

                                <div class="col-md-3">
                                    <label class="form-label">From Date</label>

                                    <input type="date" id="from" name="from" value="{{ old('from', $from) }}"
                                        min="{{ $image->created_at->toDateString() }}" max="{{ now()->toDateString() }}"
                                        class="form-control @error('from') is-invalid @enderror">

                                    @error('from')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">To Date</label>

                                    <input type="date" id="to" name="to" value="{{ old('to', $to) }}"
                                        min="{{ $image->created_at->toDateString() }}" max="{{ now()->toDateString() }}"
                                        class="form-control @error('to') is-invalid @enderror">

                                    @error('to')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-primary w-100">
                                        Search
                                    </button>
                                </div>

                                <div class="col-md-2 text-center">
                        <!-- <div class="card"> -->
                            <!-- <div class="card-body text-center"> -->
                                <h6>Today's Views</h6>
                                <h2>{{ $todayViews }}</h2>
                            <!-- </div> -->
                        <!-- </div> -->
                    </div>
                                

                            </div>

                        </form>

                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row">

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Select day's Views Count</h6>
                                <h2>{{ $totalViews }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Today's Views</h6>
                                <h2>{{ $todayViews }}</h2>
                            </div>
                        </div>
                    </div> -->

                    <!-- <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Unique Visitors</h6>
                                <h2>{{ $uniqueVisitors }}</h2>
                            </div>
                        </div>
                    </div> -->

                </div>

                <!-- DataTable -->
                <div class="card">

                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Viewer Details
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table id="example"
                                class="table table-bordered table-striped dt-responsive nowrap align-middle"
                                style="width:100%">

                                <thead>

                                    <tr>
                                        <th>#</th>
                                        <th>IP Address</th>
                                        <!-- <th>Browser</th> -->
                                        <!-- <th>Device</th> -->
                                        <th>City</th>
                                        <th>Country</th>
                                        <th>Date & Time</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    @foreach($clicks as $click)

                                        <tr>

                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $click->ip_address }}</td>

                                            <!-- <td>{{ $click->browser }}</td> -->

                                            <!-- <td>{{ $click->device_type }}</td> -->

                                            <td>{{ $click->city ?? '-' }}</td>

                                            <td>{{ $click->country ?? '-' }}</td>

                                            <td>{{ $click->created_at->format('d M Y h:i A') }}</td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    <script>

        $(document).ready(function () {

            $('#example').DataTable({

                responsive: true,

                pageLength: 10,

                ordering: true,

                searching: true,

                lengthChange: true,

                autoWidth: false,

                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                }

            });

        });




        document.querySelector("form").addEventListener("submit", function (e) {

            let from = document.getElementById("from").value;
            let to = document.getElementById("to").value;

            if (!from || !to) {
                alert("Please select both dates.");
                e.preventDefault();
                return;
            }

            if (from > to) {
                alert("From Date cannot be greater than To Date.");
                e.preventDefault();
                return;
            }

        });

    </script>

</x-app-layout>