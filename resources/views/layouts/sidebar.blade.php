<div class="sidebar px-4 py-4 py-md-5 me-0">
    <div class="d-flex flex-column h-100">
        <a href="{{route('dashboard')}}" class="mb-0 brand-icon">
            <span class="logo-icon">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Solen Energy Co." width="90" height="90"
                    style="object-fit: contain;">
            </span>
            <span class="logo-text">{{auth()->user()->name}}</span>
        </a>
        {{-- <small >{{auth()->user()->name}}</small> --}}
        <!-- Menu: main ul -->

        <ul class="menu-list flex-grow-1 mt-3">
            @can('Dashboard')
            <li class="collapsed">
                <a class="m-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}" href="{{route('dashboard')}}">
                    <i class="icofont-home fs-5"></i> <span>Dashboard</span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>
            @endcan
            @can('User Management')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'get.register' or Route::currentRouteName() == 'role' or Route::currentRouteName() == 'permission' or Route::currentRouteName() == 'role.permission' or Route::currentRouteName() == 'user.permission') ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#user-Components" href="#">
                    <i class="icofont-briefcase"></i><span>User Management</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'get.register' or Route::currentRouteName() == 'role' or Route::currentRouteName() == 'permission' or Route::currentRouteName() == 'role.permission' or Route::currentRouteName() == 'user.permission') ? 'show' : '' }}" id="user-Components">
                    <li><a class="ms-link {{ Route::currentRouteName() == 'get.register' ? 'active' : '' }}" href="{{route('get.register')}}"><span>Register</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'role' ? 'active' : '' }}"" href=" {{ route('role') }}"><span>Roles</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'permission' ? 'active' : '' }}" href="{{ route('permission') }}"><span>Permissions</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'role.permission' ? 'active' : '' }}" href="{{ route('role.permission') }}"><span>Roles Permissions</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'user.permission' ? 'active' : '' }}" href="{{ route('user.permission') }}"><span>Users Permissions</span></a></li>
                </ul>
            </li>
            @endcan
            @can('View Employees')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'employees.index' or Route::currentRouteName() == 'employees.create') ? 'active' : '' }}" href="{{route('employees.index')}}">
                    <i class="icofont-users-alt-5 "></i> <span>Employees </span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>
            @endcan
            @can('View Customer')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'customers.index' or Route::currentRouteName() == 'customers.create') ? 'active' : '' }}" href="{{route('customers.index')}}">
                    <i class="icofont-user-suited "></i> <span>Customers </span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>
            @endcan
            @can('View Intakeform')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'intake-form.index' or Route::currentRouteName() == 'intake-form.create') ? 'active' : '' }}" href="{{route('intake-form.index')}}">
                    <i class="icofont-user-suited "></i> <span>In-take Form </span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>
            @endcan
            @can('View Project')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit'  or Route::currentRouteName() == 'projects' or Route::currentRouteName() == 'projects.show' ) ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#project-Components" href="#">
                    <i class="icofont-briefcase"></i><span>Projects</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit' or Route::currentRouteName() == 'tasks.index' or Route::currentRouteName() == 'projects' or Route::currentRouteName() == 'projects.show'  ) ? 'show' : '' }}" id="project-Components">
                    <li><a class="ms-link {{ Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.show'  ? 'active' : '' }}" href="{{route('projects.index')}}"><span>Projects</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'projects' ? 'active' : '' }}" href="{{route('projects')}}"><span>Project List</span></a></li>
                </ul>
            </li>
            @endcan
            @can('User Management')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'module-types.index' or Route::currentRouteName() == 'view-redline-cost' or Route::currentRouteName() == 'view-dealer-fee' or Route::currentRouteName() == 'tools.index' or Route::currentRouteName() == 'office-costs.index' or Route::currentRouteName() == 'view-adders' or Route::currentRouteName() == 'view.adder.types' or Route::currentRouteName() == 'view-inverter-type' or Route::currentRouteName() == 'sales.partner.types' or Route::currentRouteName() == 'finance.option.types' or Route::currentRouteName() == 'call.scripts.list'  or Route::currentRouteName() == 'email.scripts.list' or Route::currentRouteName() == 'loan.term' or Route::currentRouteName() == 'view.utility.types' or Route::currentRouteName() == 'sub.contractor' or Route::currentRouteName() == 'assign-department.index' ) ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#module-types" href="#">
                    <i class="icofont-briefcase"></i><span>Operations</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'module-types.index' or Route::currentRouteName() == 'view-redline-cost' or Route::currentRouteName() == 'view-dealer-fee' or Route::currentRouteName() == 'tools.index' or Route::currentRouteName() == 'office-costs.index' or Route::currentRouteName() == 'view-adders' or Route::currentRouteName() == 'view.adder.types' or Route::currentRouteName() == 'view-inverter-type' or Route::currentRouteName() == 'sales.partner.types' or Route::currentRouteName() == 'finance.option.types' or Route::currentRouteName() == 'call.scripts.list' or Route::currentRouteName() == 'email.scripts.list' or Route::currentRouteName() == 'loan.term' or Route::currentRouteName() == 'view.utility.types' or Route::currentRouteName() == 'sub.contractor' or Route::currentRouteName() == 'assign-department.index' ) ? 'show' : '' }}" id="module-types">
                    <li><a class="ms-link {{ Route::currentRouteName() == 'module-types.index' ? 'active' : '' }}" href="{{route('module-types.index')}}"><span>Module Types</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view-dealer-fee' ? 'active' : '' }}" href="{{route('view-dealer-fee')}}"><span>Dealer Fee</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'tools.index' ? 'active' : '' }}" href="{{route('tools.index')}}"><span>Tools</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'office-costs.index' ? 'active' : '' }}" href="{{route('office-costs.index')}}"><span>Office Cost</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'labor-costs.index' ? 'active' : '' }}" href="{{route('labor-costs.index')}}"><span>Labor Cost</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view-adders' ? 'active' : '' }}" href="{{route('view-adders')}}"><span>Adders</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view.adder.types' ? 'active' : '' }}" href="{{route('view.adder.types')}}"><span>Adder Types</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view-inverter-type' ? 'active' : '' }}" href="{{route('view-inverter-type')}}"><span>Inverter Types</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view-redline-cost' ? 'active' : '' }}" href="{{route('view-redline-cost')}}"><span>Inverter Base Cost</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'sales.partner.types' ? 'active' : '' }}" href="{{route('sales.partner.types')}}"><span>Sales Partners</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'sub.contractor' ? 'active' : '' }}" href="{{route('sub.contractor')}}"><span>Sub-Contractors</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'finance.option.types' ? 'active' : '' }}" href="{{route('finance.option.types')}}"><span>Finance Options</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'loan.term' ? 'active' : '' }}" href="{{route('loan.term')}}"><span>Loan Terms</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'call.scripts.list' ? 'active' : '' }}" href="{{route('call.scripts.list')}}"><span>Call Scripts</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'email.scripts.list' ? 'active' : '' }}" href="{{route('email.scripts.list')}}"><span>Email Scripts</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'view.utility.types' ? 'active' : '' }}" href="{{route('view.utility.types')}}"><span>Utility Company</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'assign-department.index' ? 'active' : '' }}" href="{{route('assign-department.index')}}"><span>Assign Department</span></a></li>
                </ul>
            </li>
            @endcan

            @can('Reports')
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'reports.profit' or Route::currentRouteName() == 'forecast.report' or Route::currentRouteName() == 'report-builder' or Route::currentRouteName() == 'report-runner'  or Route::currentRouteName() == 'get.transaction.report'  ) ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#reports" href="#">
                    <i class="icofont-briefcase"></i><span>Reports</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'reports.profit' or Route::currentRouteName() == 'forecast.report' or Route::currentRouteName() == 'get.transaction.report'  ) ? 'show' : '' }}" id="reports">
                    @can('Report Builder')
                        <li><a class="ms-link {{ Route::currentRouteName() == 'report-builder' ? 'active' : '' }}" href="{{route('report-builder')}}"><span>Report Builder</span></a></li>
                        <li><a class="ms-link {{ Route::currentRouteName() == 'report-runner' ? 'active' : '' }}" href="{{route('report-runner')}}"><span>Report Runner</span></a></li>
                    @endcan
                    @can('Profitability Report')
                        <li><a class="ms-link {{ Route::currentRouteName() == 'reports.profit' ? 'active' : '' }}" href="{{route('reports.profit')}}"><span>Profitability Report</span></a></li>
                    @endcan
                    @can('Forecast Report')
                        <li><a class="ms-link {{ Route::currentRouteName() == 'forecast.report' ? 'active' : '' }}" href="{{route('forecast.report')}}"><span>Forecast Report</span></a></li>
                    @endcan
                    @can('Override Report')
                        <li><a class="ms-link {{ Route::currentRouteName() == 'override.report' ? 'active' : '' }}" href="{{route('override.report')}}"><span>Override Report</span></a></li>
                    @endcan
                    @can('Transaction Report')
                        <li><a class="ms-link {{ Route::currentRouteName() == 'get.transaction.report' ? 'active' : '' }}" href="{{route('get.transaction.report')}}"><span>Transaction Report</span></a></li>
                    @endcan
                </ul>
            </li>
            @endcan
            @can('Tickets')
            <li class="collapsed">
                <a class="m-link {{ Route::currentRouteName() == 'tickets' ? 'active' : '' }}" href="{{route('tickets')}}">
                    <i class="icofont-ticket fs-5"></i> <span>Tickets</span> <span class=" ms-auto text-end fs-5"></span>
                </a>
            </li>
            @endcan

            <li class="collapsed">
                <a class="m-link " href="{{route('profile.logout')}}">
                    <i class="icofont-logout fs-5"></i> <span>Logout</span> <span class=" ms-auto text-end fs-5"></span>
                </a>
            </li>
        </ul>




        <!-- Theme: Switch Theme -->
        <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-center justify-content-center">
                <div class="form-check form-switch theme-switch">
                    <input class="form-check-input" type="checkbox" id="theme-switch">
                    <label class="form-check-label" for="theme-switch">Enable Dark Mode!</label>
                </div>
            </li>
        </ul>

        <!-- Menu: menu collepce btn -->
        <button type="button" class="btn btn-link sidebar-mini-btn text-light">
            <span class="ms-2"><i class="icofont-bubble-right"></i></span>
        </button>
    </div>
</div>
