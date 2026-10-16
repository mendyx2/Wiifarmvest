@php
    $sideBarLinks = json_decode($sidenav);

    // Only show links this admin can actually open — same rules the routes
    // enforce (config/admin_access.php). Dropdowns left empty are removed.
    // A section header rides on its section's first entry, so when that entry
    // is hidden the header moves to the next visible one; a section with
    // nothing visible loses its header altogether.
    $sidebarAdmin  = auth('admin')->user();
    $canOpen       = fn ($item) => \App\Http\Middleware\AdminAccessMiddleware::allows($sidebarAdmin, (string) @$item->route_name);
    $pendingHeader = null;

    foreach ($sideBarLinks as $key => $data) {
        if (@$data->header) {
            $pendingHeader = $data->header;
            unset($data->header);
        }

        if (@$data->submenu) {
            $data->submenu = array_values(array_filter($data->submenu, $canOpen));
            $visible = (bool) $data->submenu;
        } else {
            $visible = $canOpen($data);
        }

        if (!$visible) {
            unset($sideBarLinks->$key);
            continue;
        }

        if ($pendingHeader) {
            $data->header  = $pendingHeader;
            $pendingHeader = null;
        }
    }
@endphp

<div class="sidebar bg--dark">
    <button class="res-sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="sidebar__inner">
        <div class="sidebar__logo">
            <a href="{{route('admin.dashboard')}}" class="sidebar__main-logo"><img src="{{siteLogo('dark')}}" alt="image"></a>
        </div>
        <div class="sidebar__menu-wrapper">
            <ul class="sidebar__menu">
                @foreach($sideBarLinks as $key => $data)
                    @if (@$data->header)
                        <li class="sidebar__menu-header">{{ __($data->header) }}</li>
                    @endif
                    @if(@$data->submenu)
                        <li class="sidebar-menu-item sidebar-dropdown">
                            <a href="javascript:void(0)" class="{{ menuActive(@$data->menu_active, 3) }}">
                                <i class="menu-icon {{ @$data->icon }}"></i>
                                <span class="menu-title">{{ __(@$data->title) }}</span>
                                @foreach(@$data->counters ?? [] as $counter)
                                    @if($$counter > 0)
                                        <span class="menu-badge menu-badge-level-one bg--warning ms-auto">
                                            <i class="fas fa-exclamation"></i>
                                        </span>
                                        @break
                                    @endif
                                @endforeach
                            </a>
                            <div class="sidebar-submenu {{ menuActive(@$data->menu_active, 2) }} ">
                                <ul>
                                    @foreach($data->submenu as $menu)
                                    @php
                                        // Skip items restricted to roles the current admin doesn't have
                                        if (!empty($menu->roles)) {
                                            $allowed = false;
                                            foreach ($menu->roles as $requiredRole) {
                                                if (auth('admin')->user()->hasRole($requiredRole)) {
                                                    $allowed = true;
                                                    break;
                                                }
                                            }
                                            if (!$allowed) continue;
                                        }

                                        $submenuParams = null;
                                        if (@$menu->params) {
                                            foreach ($menu->params as $submenuParamVal) {
                                                $submenuParams[] = array_values((array)$submenuParamVal)[0];
                                            }
                                        }
                                    @endphp
                                        <li class="sidebar-menu-item {{ menuActive(@$menu->menu_active) }} ">
                                            <a href="{{ route(@$menu->route_name,$submenuParams) }}" class="nav-link">
                                                <i class="menu-icon las la-dot-circle"></i>
                                                <span class="menu-title">{{ __($menu->title) }}</span>
                                                @php $counter = @$menu->counter; @endphp
                                                @if(@$$counter)
                                                    <span class="menu-badge bg--info ms-auto">{{ @$$counter }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        @php
                            $mainParams = null;
                            if (@$data->params) {
                                foreach ($data->params as $paramVal) {
                                    $mainParams[] = array_values((array)$paramVal)[0];
                                }
                            }
                        @endphp
                        <li class="sidebar-menu-item {{ menuActive(@$data->menu_active) }}">
                            <a href="{{ route(@$data->route_name,$mainParams) }}" class="nav-link ">
                                <i class="menu-icon {{ $data->icon }}"></i>
                                <span class="menu-title">{{ __(@$data->title) }}</span>
                                @php $counter = @$data->counter; @endphp
                                @if (@$$counter)
                                    <span class="menu-badge bg--info ms-auto">{{ @$$counter }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="version-info text-center text-uppercase">
            <span class="text--primary">{{__(systemDetails()['name'])}}</span>
            <span class="text--success">{{systemDetails()['version']}}<br/>
            @lang('Society') </span>
        </div>
    </div>
</div>
<!-- sidebar end -->
 
@push('script')
    <script>
        if($('li').hasClass('active')){
            $('.sidebar__menu-wrapper').animate({
                scrollTop: eval($(".active").offset().top - 320)
            },500);
        }
    </script>
@endpush
