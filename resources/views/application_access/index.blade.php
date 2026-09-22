
<x-app-layout>

    <x-slot name="title">
        Application API Access
    </x-slot>


    <div class="container">


        {{-- ============================================================= --}}
        {{-- SUCCESS MESSAGE + NEW API TOKEN --}}
        {{-- ============================================================= --}}

        @if(session('api_credentials'))

            <div class="alert alert-success">

                <h5>
                    Application Created Successfully
                </h5>

                <hr>


                {{-- Application Name --}}
                <p>
                    <strong>
                        Application Name:
                    </strong>

                    {{ session('api_credentials.application_name') }}
                </p>


                {{-- API Token --}}
                <p>
                    <strong>
                        API Token:
                    </strong>
                </p>

                <div class="input-group mb-3">

                    <input type="text"
                           class="form-control"
                           value="{{ session('api_credentials.api_token') }}"
                           readonly
                           id="apiToken">

                    <button type="button"
                            class="btn btn-outline-secondary"
                            onclick="copyText(document.getElementById('apiToken').value)">

                        Copy

                    </button>

                </div>


                <div class="alert alert-warning mb-0">

                    <strong>
                        Important:
                    </strong>

                    Save this API token now.
                    It will not be shown again.

                </div>

            </div>

        @endif


        {{-- ============================================================= --}}
        {{-- SUCCESS MESSAGE --}}
        {{-- ============================================================= --}}

        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif


        {{-- ============================================================= --}}
        {{-- PAGE HEADER --}}
        {{-- ============================================================= --}}

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h4>
                Application Access
            </h4>

            <a href="{{ route('application-access.create') }}"
               class="btn btn-primary">

                + Create Application

            </a>

        </div>


        {{-- ============================================================= --}}
        {{-- APPLICATION TABLE --}}
        {{-- ============================================================= --}}

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Application
                                </th>

                                <th>
                                    API Token
                                </th>

                                <th>
                                    CORS
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($applications as $application)

                                <tr>


                                    {{-- Number --}}
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>


                                    {{-- Application Name --}}
                                    <td>
                                        {{ $application->application_name }}
                                    </td>


                                    {{-- API Token --}}
                                    <td>

                                        <span class="text-muted">
                                            Hidden
                                        </span>

                                    </td>


                                    {{-- CORS --}}
                                    <td>

                                        {{ $application->allowed_cors_domains ?: '-' }}

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if($application->status)

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Disabled
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Action --}}
                                    <td>

                                        <form method="POST"
                                              action="{{ route('application-access.regenerate-token', $application->id) }}"
                                              onsubmit="return confirm('Regenerate API token? The old token will stop working.');">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm btn-warning">

                                                Regenerate Token

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6"
                                        class="text-center">

                                        No applications found.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- COPY API TOKEN --}}
    {{-- ============================================================= --}}

    <script>

        function copyText(text)
        {
            navigator.clipboard.writeText(text)
                .then(function () {

                    alert('API token copied!');

                })
                .catch(function () {

                    alert('Unable to copy API token.');

                });
        }

    </script>


</x-app-layout>

