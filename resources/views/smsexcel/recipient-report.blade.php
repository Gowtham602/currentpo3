
<x-app-layout>

    <x-slot name="title">SMS Recipient Report</x-slot>

    <div class="vertical-overlay"></div>

    <div class="main-content">
        <div class="page-content">

            <div class="container-fluid py-4">

                {{-- Page Heading --}}
                <div class="d-flex justify-content-between
                            align-items-center flex-wrap mb-4">

                    <div>
                        <h3 class="mb-1">SMS Recipient Report</h3>

                        <p class="text-muted mb-0">
                            View unique phone numbers branch-wise.
                        </p>
                    </div>

                    <a href="{{ route('sms-import.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to SMS Import
                    </a>

                </div>


                {{-- Filter Card --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            Select Report Details
                        </h5>
                    </div>

                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="GET"
                              action="{{ route('sms-recipient-report.index') }}"
                              class="row g-3 align-items-end">

                            {{-- Month & Year --}}
                            <div class="col-md-4">

                                <label for="month_year"
                                       class="form-label fw-medium">
                                    Select Month & Year
                                </label>

                                <input type="month"
                                       name="month_year"
                                       id="month_year"
                                       class="form-control"
                                       value="{{ $monthYear }}"
                                       min="2026-01"
                                       max="2030-12"
                                       required>

                            </div>


                            {{-- Branch --}}
                            <div class="col-md-4">

                                <label for="branch"
                                       class="form-label fw-medium">
                                    Select Branch
                                </label>

                                <select name="branch"
                                        id="branch"
                                        class="form-select"
                                        required>

                                    <option value="">
                                        Select Branch
                                    </option>

                                    @foreach ($branches as $branch)

                                        <option value="{{ $branch }}"
                                            {{ $selectedBranch === $branch ? 'selected' : '' }}>

                                            {{ $branch }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- Submit --}}
                            <div class="col-md-2">

                                <button type="submit"
                                        class="btn btn-primary w-100">

                                    <i class="fas fa-search me-1"></i>
                                    Show Report

                                </button>

                            </div>


                            {{-- Reset --}}
                            <div class="col-md-2">

                                <a href="{{ route('sms-recipient-report.index') }}"
                                   class="btn btn-outline-secondary w-100">

                                    Reset

                                </a>

                            </div>

                        </form>

                    </div>

                </div>


                {{-- Report Result --}}
                @if ($selectedBranch !== '')

                    @if (!$tableExists)

                        <div class="alert alert-warning">
                            <strong>No monthly table found!</strong>

                            No SMS data available for
                            {{ \Carbon\Carbon::createFromFormat(
                                'Y-m', $monthYear
                            )->format('F Y') }}.
                        </div>

                    @elseif (!$schemaReady)

                        <div class="alert alert-danger">
                            The selected monthly table does not contain
                            the required branch or recipient columns.
                        </div>

                    @else

                        <div class="card border-0 shadow-sm">

                            {{-- Report Header --}}
                            <div class="card-header bg-white py-3">

                                <div class="d-flex justify-content-between
                                            align-items-center flex-wrap gap-3">

                                    <div>

                                        <h5 class="mb-1 fw-semibold">

                                            {{ $selectedBranch }}
                                            Recipient Report

                                        </h5>

                                        <small class="text-muted">

                                            {{ \Carbon\Carbon::createFromFormat(
                                                'Y-m', $monthYear
                                            )->format('F Y') }}

                                        </small>

                                    </div>


                                    {{-- Export Buttons --}}
                                    <div class="d-flex flex-wrap gap-2">

                                        <a href="{{ route(
                                            'sms-recipient-report.excel',
                                            [
                                                'month_year' => $monthYear,
                                                'branch' => $selectedBranch
                                            ]
                                        ) }}"
                                           class="btn btn-outline-success">

                                            <i class="fas fa-file-excel me-1"></i>
                                            Export Excel

                                        </a>


                                        <!-- <a href="{{ route(
                                            'sms-recipient-report.pdf',
                                            [
                                                'month_year' => $monthYear,
                                                'branch' => $selectedBranch
                                            ]
                                        ) }}"
                                           class="btn btn-outline-danger">

                                            <i class="fas fa-file-pdf me-1"></i>
                                            Export PDF

                                        </a> -->

                                    </div>

                                </div>

                            </div>


                            <div class="card-body">


                                {{-- Total Count --}}
                                <div class="row mb-3">

                                    <div class="col-md-4">

                                        <div class="border rounded p-3">

                                            <div class="text-muted">
                                                Total Unique Recipients
                                            </div>

                                            <h3 class="mb-0 text-primary">
                                                {{ number_format($recipients->count()) }}
                                            </h3>

                                        </div>

                                    </div>

                                </div>


                                {{-- Recipient Table --}}
                                @if ($recipients->isNotEmpty())

                                    <div class="table-responsive">

                                        <table class="table table-bordered
                                                      table-hover align-middle mb-0">

                                            <thead class="table-light">

                                                <tr>

                                                    <th width="80">S.No</th>

                                                    <th>Branch</th>

                                                    <th>Phone Number</th>

                                                </tr>

                                            </thead>


                                            <tbody>

                                                @foreach ($recipients as $item)

                                                    <tr>

                                                        <td>
                                                            {{ $loop->iteration }}
                                                        </td>

                                                        <td class="fw-semibold">
                                                            {{ $selectedBranch }}
                                                        </td>


                                                        <td>
                                                            {{ $item->recipient }}
                                                        </td>

                                                    </tr>

                                                @endforeach

                                            </tbody>

                                        </table>

                                    </div>

                                @else

                                    
                                <div class="alert alert-warning mb-0">

                                        No unique recipient phone numbers
                                        found for this branch and month.

                                    </div>

                                @endif

                            </div>

                        </div>

                    @endif

                @endif

            </div>

        </div>
    </div>

</x-app-layout>
