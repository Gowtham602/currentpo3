
<x-app-layout>

    <x-slot name="title">
        Application API Access
    </x-slot>

    <div class="main-content">

        <div class="card">

            <div class="card-header">
                <h4>Create Application API Access</h4>
            </div>

            <div class="card-body">

                <form method="POST"
                      action="{{ route('application-access.store') }}">

                    @csrf

                    {{-- Application Name --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Application Name
                        </label>

                        <input type="text"
                               name="application_name"
                               class="form-control"
                               placeholder="Example: Mobile App"
                               value="{{ old('application_name') }}"
                               required>

                        @error('application_name')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Allowed CORS Domains --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Allowed CORS Domains
                        </label>

                        <textarea name="allowed_cors_domains"
                                  class="form-control"
                                  rows="3"
                                  placeholder="https://mobile.pothys.com">{{ old('allowed_cors_domains') }}</textarea>

                        <small class="text-muted">
                            You can enter multiple domains separated by comma.
                        </small>

                        @error('allowed_cors_domains')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Create Button --}}
                    <button type="submit"
                            class="btn btn-primary">

                        Create Application

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>

