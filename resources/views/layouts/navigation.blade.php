

    <header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
			<div>
				<a href="/" class="d-inline-block auth-logo">
					<img src="{{ asset('assets/images/pothys-logo-white-text.png') }}" alt="" height="50">
				</a>
			</div>
            

            <div class="d-flex align-items-center">

                <div class="dropdown d-md-none topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle" id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-menu-line fs-2"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-search-dropdown">
                        <a class="dropdown-item" href="{{ route('dashboard') }}"> <span class="align-middle">Image Merger</span></a>
                        <a class="dropdown-item" href="{{ route('url-shortener.index') }}"> <span class="align-middle">URL Merger</span></a>
						<a class="dropdown-item" href="{{ route('pdf.index') }}"> <span class="align-middle">PDF -> Image</span></a>
						<a class="dropdown-item" href="{{ route('sms.index') }}"> <span class="align-middle">Pothys Reports</span></a>
                    </div>
                </div>
                
				<div class="ms-1 header-item d-none d-sm-flex {{ request()->routeIs('dashboard') ? 'topbar-user' : '' }}">
					<a href="{{ route('dashboard') }}"
						class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
						Image Merger
					</a>
				</div>
					<div class="ms-1 header-item d-none d-sm-flex {{ request()->routeIs('url-shortener.index') ? 'topbar-user' : '' }}">
                    <a href="{{ route('url-shortener.index') }}"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                        URL Merge
                    </a>
                </div>

				<div class="ms-1 header-item d-none d-sm-flex {{ request()->routeIs('pdf.*') ? 'topbar-user' : '' }}">
                    <a href="{{ route('pdf.index') }}"
						class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
						PDF -> Image
					</a>
				</div>
				<div class="ms-1 header-item d-none d-sm-flex">
                    <a href="{{ route('sms.index') }}"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                        Pothys Reports 
                    </a>
                </div>
				
				
				<div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user" src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome {{ Auth::user()->name }}!</h6>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                        <form method="POST" action="{{ route('logout') }}">
							@csrf
							<button type="submit"
								class="dropdown-item border-0 bg-transparent text-start w-100">
								<i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
								<span class="align-middle" data-key="t-logout">Logout</span>
							</button>
						</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>