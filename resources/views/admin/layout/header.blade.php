<!-- partial:partials/_navbar.html -->
<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <b>Control Panel</b>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="icon-menu"></span>
        </button>
        <ul class="navbar-nav mr-lg-2">
            <li class="nav-item nav-search d-none d-lg-block">
                <div class="input-group">
                    <div class="input-group-prepend hover-cursor" id="navbar-search-icon">
                        <span class="input-group-text" id="search">
                            <i class="icon-search"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control" id="navbar-search-input" placeholder="Search now"
                        aria-label="search" aria-describedby="search">
                </div>
            </li>
        </ul>
        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle d-flex" href="{{ url('admin/update-admin-details') }}"
                    data-toggle="dropdown" id="profileDropdown">


                    @if (!empty(Auth::guard('admin')->user()->image))
                        <img src="{{ url('admin/images/photos/' . Auth::guard('admin')->user()->image) }}"
                            alt="profile">
                    @else
                        <img src="{{ url('admin/images/photos/no-image.gif') }}" alt="profile">
                    @endif

                    @if (!empty(Auth::guard('admin')->user()->type) && !empty(Auth::guard('admin')->user()->name))
                        <div style="display:flex;flex-direction:column;margin-left:0.5rem;margin-right:1rem;">
                            <span
                                style="line-height:1.4;text-transform:capitalize;color:#212529;font-weight:500;">{{ Auth::guard('admin')->user()->name }}</span>
                            <span
                                style="line-height:1;font-size:0.75rem;color:#8c8c8c;">({{ Auth::guard('admin')->user()->type }})</span>
                        </div>
                    @endif


                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                    <a href="{{ url('admin/update-admin-details') }}" class="dropdown-item">
                        <i class="ti-user text-primary"></i>
                        Profile
                    </a>
                    {{-- <a href="{{ url('admin/logout') }}" class="dropdown-item">
                        <i class="ti-power-off text-primary"></i>
                        Logout
                    </a> --}}
                </div>
            </li>
            <li class="nav-item nav-settings d-none d-lg-flex">
                <a class="nav-link" href="{{ url('admin/logout') }}" style="font-size: 0.5rem">
                    <button class="btn btn-warning" style="padding:4px 10px;font-size:.95rem;font-weight:600;">
                        <i class="ti-power-off text-black" style="font-size:0.9rem;padding-bottom:4px;"></i>
                        <span>Logout</span>
                    </button>
                </a>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas">
            <span class="icon-menu"></span>
        </button>
    </div>
</nav>
