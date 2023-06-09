<div class="sidebar px-4 py-4 py-md-5 me-0">
    <div class="d-flex flex-column h-100">
        <a href="index.html" class="mb-0 brand-icon">
            <span class="logo-icon">
                <svg width="35" height="35" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z" />
                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z" />
                </svg>
            </span>
            <span class="logo-text">CRM</span>
        </a>
        <!-- Menu: main ul -->

        <ul class="menu-list flex-grow-1 mt-3">
            <li class="collapsed">
                <a class="m-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}" href="{{route('dashboard')}}">
                    <i class="icofont-home fs-5"></i> <span>Dashboard</span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>
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

            <li class="collapsed">
                <a class="m-link {{Route::currentRouteName() == 'employees.index' ? 'active' : 
                        ''}}" href="#" data-bs-toggle="collapse" data-bs-target="#employee-Components">
                    <i class="icofont-users-alt-5"></i> <span>Employees</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span>
                </a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'employees.index' ? 'show' : 
                        '')}}" id="employee-Components">
                    <li>
                        <a class="ms-link {{Route::currentRouteName() == 'employees.index' ? 'active' : 
                        ''}}" href="{{route('employees.index')}}"> <span>Employees</span></a>
                    </li>
                </ul>
            </li>
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'customers.index' or Route::currentRouteName() == 'customers.create') ? 'active' : '' }}" href="{{route('customers.index')}}">
                    <i class="icofont-user-suited "></i> <span>Customers </span> <span class=" ms-auto text-end fs-5"></span></a>
            </li>

            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit' ) ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#project-Components" href="#">
                    <i class="icofont-briefcase"></i><span>Projects</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit' or Route::currentRouteName() == 'tasks.index' ) ? 'show' : '' }}" id="project-Components">
                    <li><a class="ms-link {{ Route::currentRouteName() == 'projects.index' ? 'active' : '' }}" href="{{route('projects.index')}}"><span>Projects</span></a></li>
                    <li><a class="ms-link {{ Route::currentRouteName() == 'tasks.index' ? 'active' : '' }}" href=" {{ route('tasks.index') }}"><span>Tasks</span></a></li>
                </ul>
            </li>
            <li class="collapsed">
                <a class="m-link {{ (Route::currentRouteName() == 'projects.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit' ) ? 'show' : '' }}" data-bs-toggle="collapse" data-bs-target="#module-types" href="#">
                    <i class="icofont-briefcase"></i><span>Operations</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                <!-- Menu: Sub menu ul -->
                <ul class="sub-menu collapse {{ (Route::currentRouteName() == 'module-types.index' or Route::currentRouteName() == 'projects.create' or Route::currentRouteName() == 'projects.edit' ) ? 'show' : '' }}" id="module-types">
                    <li><a class="ms-link {{ Route::currentRouteName() == 'module-types.index' ? 'active' : '' }}" href="{{route('module-types.index')}}"><span>Module Types</span></a></li>
                </ul>
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