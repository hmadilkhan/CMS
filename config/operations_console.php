<?php

use App\Services\Operations\AddersPanel;
use App\Services\Operations\AdderTypesPanel;
use App\Services\Operations\AssignDepartmentPanel;
use App\Services\Operations\DealerFeePanel;
use App\Services\Operations\DepartmentsPanel;
use App\Services\Operations\LaborCostPanel;
use App\Services\Operations\OfficeCostPanel;
use App\Services\Operations\SubDepartmentsPanel;

/**
 * The Operations console - one window for the twenty-odd Operations screens.
 *
 * This file IS the console: each section names the route it opens, the
 * permission that may see it and the group it sits under. A screen that moves,
 * arrives or is renamed is a change here, not in the page.
 *
 * A section is drawn one of two ways, and only this file decides which:
 *
 *  - with a `panel`, the console draws the screen itself, in the page, sharing
 *    its scroll, its chrome and its history;
 *  - without one, the console loads the screen's existing page embedded (it
 *    renders without the sidebar and header - see `layouts.master`).
 *
 * That is why the console covered every screen from day one: a screen joins as
 * a frame and is promoted to a panel later, and nothing outside this file
 * changes when it is. Either way the screen keeps its own route, its own
 * controller and its own permission.
 */
return [
    /*
     * Everything in here is Operations, which is one permission today. A
     * section may name its own instead, and the console then hides what the
     * viewer cannot open.
     */
    'permission' => 'User Management',

    'groups' => [
        [
            'name' => 'Pipeline',
            'sections' => [
                ['key' => 'departments', 'label' => 'Departments', 'route' => 'departments.list', 'icon' => 'icofont-network-tower', 'panel' => DepartmentsPanel::class],
                ['key' => 'sub-departments', 'label' => 'Sub Departments', 'route' => 'sub.departments.list', 'icon' => 'icofont-hierarchy-structure', 'panel' => SubDepartmentsPanel::class],
                ['key' => 'assign-department', 'label' => 'Assign Department', 'route' => 'assign-department.index', 'icon' => 'icofont-users-alt-4', 'panel' => AssignDepartmentPanel::class],
            ],
        ],
        [
            'name' => 'Equipment',
            'sections' => [
                ['key' => 'module-types', 'label' => 'Module Types', 'route' => 'module-types.index', 'icon' => 'icofont-solar-panel'],
                ['key' => 'inverter-types', 'label' => 'Inverter Types', 'route' => 'view-inverter-type', 'icon' => 'icofont-power'],
                ['key' => 'inverter-base-cost', 'label' => 'Inverter Base Cost', 'route' => 'view-redline-cost', 'icon' => 'icofont-price'],
                ['key' => 'tools', 'label' => 'Tools', 'route' => 'tools.manage', 'icon' => 'icofont-tools'],
            ],
        ],
        [
            'name' => 'Pricing',
            'sections' => [
                ['key' => 'adders', 'label' => 'Adders', 'route' => 'view-adders', 'icon' => 'icofont-plus-square', 'panel' => AddersPanel::class],
                ['key' => 'adder-types', 'label' => 'Adder Types', 'route' => 'view.adder.types', 'icon' => 'icofont-list', 'panel' => AdderTypesPanel::class],
                ['key' => 'dealer-fee', 'label' => 'Dealer Fee', 'route' => 'view-dealer-fee', 'icon' => 'icofont-sale-discount', 'panel' => DealerFeePanel::class],
                ['key' => 'office-costs', 'label' => 'Office Cost', 'route' => 'office-costs.index', 'icon' => 'icofont-building-alt', 'panel' => OfficeCostPanel::class],
                ['key' => 'labor-costs', 'label' => 'Labor Cost', 'route' => 'labor-costs.index', 'icon' => 'icofont-worker', 'panel' => LaborCostPanel::class],
            ],
        ],
        [
            'name' => 'Finance',
            'sections' => [
                ['key' => 'finance-options', 'label' => 'Finance Options', 'route' => 'finance.option.types', 'icon' => 'icofont-bank-alt'],
                ['key' => 'loan-terms', 'label' => 'Loan Terms', 'route' => 'loan.term', 'icon' => 'icofont-calendar'],
            ],
        ],
        [
            'name' => 'Partners',
            'sections' => [
                ['key' => 'sales-partners', 'label' => 'Sales Partners', 'route' => 'sales.partner.types', 'icon' => 'icofont-handshake-deal'],
                ['key' => 'sub-contractors', 'label' => 'Sub-Contractors', 'route' => 'sub.contractor', 'icon' => 'icofont-users'],
                ['key' => 'utility-company', 'label' => 'Utility Company', 'route' => 'view.utility.types', 'icon' => 'icofont-flash'],
            ],
        ],
        [
            'name' => 'Scripts',
            'sections' => [
                ['key' => 'call-types', 'label' => 'Call Types', 'route' => 'call.types.list', 'icon' => 'icofont-phone'],
                ['key' => 'call-scripts', 'label' => 'Call Scripts', 'route' => 'call.scripts.list', 'icon' => 'icofont-ui-call'],
                ['key' => 'email-types', 'label' => 'Email Types', 'route' => 'email.types.list', 'icon' => 'icofont-email'],
                ['key' => 'email-scripts', 'label' => 'Email Scripts', 'route' => 'email.scripts.list', 'icon' => 'icofont-envelope-open'],
            ],
        ],
    ],
];
