<?php

/*
|--------------------------------------------------------------------------
| Record Tags for the System Email Templates
|--------------------------------------------------------------------------
|
| Tags an admin can drop into ANY email template whose email carries that
| record. A template lists its records under 'records' in
| config/notification_templates.php; every tag of every listed record then
| shows up in its editor and is filled in at send time. So adding a tag here
| makes it available to every email about that kind of record at once - no
| notification class has to change.
|
| A tag is read from the record, never computed in code:
|   'path'   => 'code'                     attribute or dotted relation path
|   'path'   => ['first_name','last_name'] several values joined by a space
|   'format' => 'date'                     shown as "Jan 05, 2026"
|   'route'  => 'projects.show'            a link: route(name, record id)
|   'url'    => '/projects/{id}'           a link without a named route
|
| 'from' says how to reach the record from another one (a ticket's project,
| a project's customer), so a ticket email gets project and customer tags
| without the sender passing them.
|
| These emails go to sales partners, sub-contractors and employees. Never add
| a finance, cost or profitability field, or a credential, here.
|
*/

return [

    'project' => [
        'label' => 'Project',
        'from' => ['ticket' => 'project', 'survey' => 'project'],
        'tags' => [
            'project_name' => ['label' => 'Project name', 'path' => 'project_name'],
            'project_code' => ['label' => 'Project code, e.g. 1001', 'path' => 'code'],
            'project_url' => ['label' => 'Link to the project page', 'route' => 'projects.show'],
            'department_name' => ['label' => 'Department the project is in now', 'path' => 'department.name'],
            'sub_department_name' => ['label' => 'Lane (sub-department) the project is in now', 'path' => 'subdepartment.name'],
            'sales_person_name' => ['label' => 'Sales person on the project', 'path' => 'salesPartnerUser.name'],
            'sales_person_email' => ['label' => 'Sales person\'s email', 'path' => 'salesPartnerUser.email'],
            'sub_contractor_name' => ['label' => 'Sub-contractor user on the project', 'path' => 'subContractorUser.name'],
            'utility_company' => ['label' => 'Utility company', 'path' => 'utility_company'],
            'ahj' => ['label' => 'AHJ', 'path' => 'ahj'],
            'hoa' => ['label' => 'HOA', 'path' => 'hoa'],
            'site_survey_link' => ['label' => 'Site survey link', 'path' => 'site_survey_link'],
            'monitoring_link' => ['label' => 'Monitoring link', 'path' => 'monitoring_link'],
            'ntp_approval_date' => ['label' => 'NTP approval date', 'path' => 'ntp_approval_date', 'format' => 'date'],
            'permitting_submission_date' => ['label' => 'Permit submission date', 'path' => 'permitting_submittion_date', 'format' => 'date'],
            'permitting_approval_date' => ['label' => 'Permit approval date', 'path' => 'permitting_approval_date', 'format' => 'date'],
            'solar_install_date' => ['label' => 'Solar install date', 'path' => 'solar_install_date', 'format' => 'date'],
            'battery_install_date' => ['label' => 'Battery install date', 'path' => 'battery_install_date', 'format' => 'date'],
            'final_inspection_date' => ['label' => 'Final inspection date', 'path' => 'final_inspection_date', 'format' => 'date'],
            'pto_submission_date' => ['label' => 'PTO submission date', 'path' => 'pto_submission_date', 'format' => 'date'],
            'pto_approval_date' => ['label' => 'PTO approval date', 'path' => 'pto_approval_date', 'format' => 'date'],
        ],
    ],

    'customer' => [
        'label' => 'Customer',
        'from' => ['project' => 'customer'],
        'tags' => [
            'customer_name' => ['label' => 'Customer full name', 'path' => ['first_name', 'last_name']],
            'customer_first_name' => ['label' => 'Customer first name', 'path' => 'first_name'],
            'customer_last_name' => ['label' => 'Customer last name', 'path' => 'last_name'],
            'customer_email' => ['label' => 'Customer email', 'path' => 'email'],
            'customer_phone' => ['label' => 'Customer phone', 'path' => 'phone'],
            'customer_address' => ['label' => 'Customer street, city, state and zip', 'path' => ['street', 'city', 'state', 'zipcode']],
            'customer_city' => ['label' => 'Customer city', 'path' => 'city'],
            'sold_date' => ['label' => 'Sold date', 'path' => 'sold_date', 'format' => 'date'],
            'panel_qty' => ['label' => 'Number of panels', 'path' => 'panel_qty'],
            'sales_partner_name' => ['label' => 'Sales partner company', 'path' => 'salespartner.name'],
            'sub_contractor_company' => ['label' => 'Sub-contractor company', 'path' => 'subContractor.name'],
        ],
    ],

    'ticket' => [
        'label' => 'Service Ticket',
        'tags' => [
            'ticket_id' => ['label' => 'Ticket number', 'path' => 'id'],
            'ticket_subject' => ['label' => 'Ticket subject', 'path' => 'subject'],
            'ticket_priority' => ['label' => 'Ticket priority', 'path' => 'priority'],
            'ticket_status' => ['label' => 'Ticket status', 'path' => 'status'],
            'ticket_notes' => ['label' => 'Ticket notes', 'path' => 'notes'],
            'ticket_created_by' => ['label' => 'Who raised the ticket', 'path' => 'creator.name'],
            'ticket_assigned_to' => ['label' => 'Who the ticket is assigned to', 'path' => 'assignedUser.name'],
            'ticket_created_at' => ['label' => 'Date the ticket was raised', 'path' => 'created_at', 'format' => 'date'],
            'ticket_url' => ['label' => 'Link to the service dashboard', 'url' => '/service-dashboard'],
        ],
    ],

    'survey' => [
        'label' => 'Site Survey',
        'tags' => [
            'survey_date' => ['label' => 'Survey date', 'path' => 'survey_date', 'format' => 'date'],
            'start_time' => ['label' => 'Start time', 'path' => 'start_time'],
            'end_time' => ['label' => 'End time', 'path' => 'end_time'],
            'survey_address' => ['label' => 'Address of the survey', 'path' => 'customer_address'],
            'survey_status' => ['label' => 'Survey status', 'path' => 'status'],
            'survey_notes' => ['label' => 'Survey notes', 'path' => 'notes'],
            'technician_name' => ['label' => 'Technician', 'path' => 'technician.name'],
            'survey_url' => ['label' => 'Link to the survey', 'url' => '/site-surveys/{id}'],
        ],
    ],

];
