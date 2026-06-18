<?php

/*
|--------------------------------------------------------------------------
| AI CRM Schema Allowlist
|--------------------------------------------------------------------------
|
| Review this mapping before enabling AI query execution.
| Only add columns that AI is allowed to access.
| Sensitive columns require explicit permission checks.
|
| This file is a conservative schema validation layer for the CRM AI chat.
| It does not execute SQL and should be reviewed whenever migrations change.
|
*/

return [
    'tables' => [
        'projects' => [
            'model' => \App\Models\Project::class,
            'table' => 'projects',
            'allowed_columns' => [
                'id',
                'customer_id',
                'department_id',
                'sub_department_id',
                'sales_partner_user_id',
                'sub_contractor_user_id',
                'project_name',
                'code',
                'start_date',
                'end_date',
                'completion_date',
                'budget',
                'description',
                'utility_company',
                'ntp_approval_date',
                'site_survey_link',
                'hoa',
                'hoa_phone_number',
                'ahj',
                'adders_approve_checkbox',
                'mpu_required',
                'meter_spot_requestd_date',
                'meter_spot_requestd_number',
                'meter_spot_result',
                'permitting_submittion_date',
                'permitting_approval_date',
                'hoa_approval_request_date',
                'hoa_approval_date',
                'solar_install_date',
                'battery_install_date',
                'monitoring_link',
                'mpu_install_date',
                'rough_inspection_date',
                'final_inspection_date',
                'pto_submission_date',
                'pto_approval_date',
                'coc_packet_mailed_out_date',
                'placards_ordered',
                'placards_note',
                'production_value_achieved',
                'fire_review_required',
                'fire_inspection_date',
                'office_cost',
                'actual_permit_fee',
                'actual_labor_cost',
                'actual_material_cost',
                'overwrite_base_price',
                'overwrite_panel_price',
                'pre_estimated_material_costs',
                'pre_estimated_labor_costs',
                'pre_estimated_permit_costs',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'project_name',
                'code',
                'description',
                'customer_id',
                'department_id',
                'sub_department_id',
                'sales_partner_user_id',
                'sub_contractor_user_id',
                'utility_company',
                'hoa',
                'mpu_required',
                'meter_spot_result',
                'ahj',
            ],
            'relationships' => [
                'customer' => [
                    'table' => 'customers',
                    'local_key' => 'customer_id',
                    'foreign_key' => 'id',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
                'subdepartment' => [
                    'table' => 'sub_departments',
                    'local_key' => 'sub_department_id',
                    'foreign_key' => 'id',
                ],
                'salesPartnerUser' => [
                    'table' => 'users',
                    'local_key' => 'sales_partner_user_id',
                    'foreign_key' => 'id',
                ],
                'subContractorUser' => [
                    'table' => 'users',
                    'local_key' => 'sub_contractor_user_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'budget',
                'office_cost',
                'actual_permit_fee',
                'actual_labor_cost',
                'actual_material_cost',
                'overwrite_base_price',
                'overwrite_panel_price',
                'pre_estimated_material_costs',
                'pre_estimated_labor_costs',
                'pre_estimated_permit_costs',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'customers' => [
            'model' => \App\Models\Customer::class,
            'table' => 'customers',
            'allowed_columns' => [
                'id',
                'first_name',
                'last_name',
                'street',
                'city',
                'state',
                'zipcode',
                'phone',
                'email',
                'preferred_language',
                'sales_partner_id',
                'sub_contractor_id',
                'sold_date',
                'panel_qty',
                'inverter_type_id',
                'module_type_id',
                'inverter_qty',
                'module_value',
                'notes',
                'is_adu',
                'loan_id',
                'sold_production_value',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'first_name',
                'last_name',
                'street',
                'city',
                'state',
                'zipcode',
                'phone',
                'email',
                'preferred_language',
                'notes',
                'loan_id',
            ],
            'relationships' => [
                'finances' => [
                    'table' => 'customer_finances',
                    'local_key' => 'id',
                    'foreign_key' => 'customer_id',
                ],
                'salespartner' => [
                    'table' => 'sales_partners',
                    'local_key' => 'sales_partner_id',
                    'foreign_key' => 'id',
                ],
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'id',
                    'foreign_key' => 'customer_id',
                ],
            ],
            'sensitive_columns' => [
                'loan_id',
                'sold_production_value',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'customer_access',
        ],

        'customer_finances' => [
            'model' => \App\Models\CustomerFinance::class,
            'table' => 'customer_finances',
            'allowed_columns' => [
                'id',
                'customer_id',
                'finance_option_id',
                'loan_term_id',
                'loan_apr_id',
                'contract_amount',
                'redline_costs',
                'adders',
                'commission',
                'dealer_fee',
                'dealer_fee_amount',
                'total_overwrite_base_price',
                'total_overwrite_panel_price',
                'module_type_cost',
                'inverter_base_cost',
                'holdback_amount',
                'third_party_credit',
                'customer_portion',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'adders',
            ],
            'relationships' => [
                'customer' => [
                    'table' => 'customers',
                    'local_key' => 'customer_id',
                    'foreign_key' => 'id',
                ],
                'finance' => [
                    'table' => 'finance_options',
                    'local_key' => 'finance_option_id',
                    'foreign_key' => 'id',
                ],
                'term' => [
                    'table' => 'loan_terms',
                    'local_key' => 'loan_term_id',
                    'foreign_key' => 'id',
                ],
                'apr' => [
                    'table' => 'loan_aprs',
                    'local_key' => 'loan_apr_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'contract_amount',
                'redline_costs',
                'commission',
                'dealer_fee',
                'dealer_fee_amount',
                'total_overwrite_base_price',
                'total_overwrite_panel_price',
                'module_type_cost',
                'inverter_base_cost',
                'holdback_amount',
                'third_party_credit',
                'customer_portion',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'finance_access',
        ],

        'project_acceptances' => [
            'model' => \App\Models\ProjectAcceptance::class,
            'table' => 'project_acceptances',
            'allowed_columns' => [
                'id',
                'project_id',
                'sales_partner_id',
                'image',
                'action_by',
                'status',
                'approved_date',
                'reason',
                'panel_qty',
                'inverter_name',
                'inverter_base_price',
                'dealer_fee_amount',
                'module_qty_price',
                'modules_amount',
                'contract_amount',
                'redline_costs',
                'adders_amount',
                'commission_amount',
                'adders_list',
                'notes',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'image',
                'reason',
                'inverter_name',
                'adders_list',
                'notes',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'user' => [
                    'table' => 'users',
                    'local_key' => 'action_by',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'inverter_base_price',
                'dealer_fee_amount',
                'module_qty_price',
                'modules_amount',
                'contract_amount',
                'redline_costs',
                'adders_amount',
                'commission_amount',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'profitability_access',
        ],

        'account_transactions' => [
            'model' => \App\Models\AccountTransaction::class,
            'table' => 'account_transactions',
            'allowed_columns' => [
                'id',
                'project_id',
                'payee',
                'milestone',
                'amount',
                'deduction_amount',
                'transaction_date',
                'transaction_details',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'payee',
                'milestone',
                'transaction_details',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'amount',
                'deduction_amount',
            ],
            'default_sort_column' => 'transaction_date',
            'access_rule' => 'finance_access',
        ],

        'users' => [
            'model' => \App\Models\User::class,
            'table' => 'users',
            'allowed_columns' => [
                'id',
                'name',
                'email',
                'username',
                'email_verified_at',
                'user_type_id',
                'sales_partner_id',
                'image',
                'phone',
                'email_preference',
                'address',
                'latitude',
                'longitude',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
                'email',
                'username',
                'phone',
                'address',
            ],
            'relationships' => [
                'type' => [
                    'table' => 'user_types',
                    'local_key' => 'user_type_id',
                    'foreign_key' => 'id',
                ],
                'salesPartner' => [
                    'table' => 'sales_partners',
                    'local_key' => 'sales_partner_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'password',
                'remember_token',
                'api_token',
                'token',
                'secret',
                'overwrite_base_price',
                'overwrite_panel_price',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'user_access',
        ],

        'employees' => [
            'model' => \App\Models\Employee::class,
            'table' => 'employees',
            'allowed_columns' => [
                'id',
                'name',
                'code',
                'email',
                'phone',
                'image',
                'joined_date',
                'user_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
                'code',
                'email',
                'phone',
            ],
            'relationships' => [
                'user' => [
                    'table' => 'users',
                    'local_key' => 'user_id',
                    'foreign_key' => 'id',
                ],
                'employeeDepartments' => [
                    'table' => 'employee_departments',
                    'local_key' => 'id',
                    'foreign_key' => 'employee_id',
                ],
            ],
            'sensitive_columns' => [
                'salary',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'user_access',
        ],

        'departments' => [
            'model' => \App\Models\Department::class,
            'table' => 'departments',
            'allowed_columns' => [
                'id',
                'name',
                'document_length',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
            ],
            'relationships' => [
                'subdepartments' => [
                    'table' => 'sub_departments',
                    'local_key' => 'id',
                    'foreign_key' => 'department_id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'sub_departments' => [
            'model' => \App\Models\SubDepartment::class,
            'table' => 'sub_departments',
            'allowed_columns' => [
                'id',
                'department_id',
                'name',
                'order',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
            ],
            'relationships' => [
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'order',
            'access_rule' => 'department_access',
        ],

        'employee_departments' => [
            'model' => \App\Models\EmployeeDepartment::class,
            'table' => 'employee_departments',
            'allowed_columns' => [
                'id',
                'employee_id',
                'department_id',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [],
            'relationships' => [
                'employee' => [
                    'table' => 'employees',
                    'local_key' => 'employee_id',
                    'foreign_key' => 'id',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'department_access',
        ],

        'tasks' => [
            'model' => \App\Models\Task::class,
            'table' => 'tasks',
            'allowed_columns' => [
                'id',
                'project_id',
                'employee_id',
                'user_id',
                'department_id',
                'sub_department_id',
                'notes',
                'assign_to_notes',
                'status',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'notes',
                'assign_to_notes',
                'status',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'employee' => [
                    'table' => 'employees',
                    'local_key' => 'employee_id',
                    'foreign_key' => 'id',
                ],
                'user' => [
                    'table' => 'users',
                    'local_key' => 'user_id',
                    'foreign_key' => 'id',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
                'subdepartment' => [
                    'table' => 'sub_departments',
                    'local_key' => 'sub_department_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'service_tickets' => [
            'model' => \App\Models\ServiceTicket::class,
            'table' => 'service_tickets',
            'allowed_columns' => [
                'id',
                'project_id',
                'user_id',
                'subject',
                'assigned_to',
                'priority',
                'notes',
                'status',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'subject',
                'priority',
                'notes',
                'status',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'creator' => [
                    'table' => 'users',
                    'local_key' => 'user_id',
                    'foreign_key' => 'id',
                ],
                'assignedUser' => [
                    'table' => 'users',
                    'local_key' => 'assigned_to',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'ticket_access',
        ],

        'service_ticket_comments' => [
            'model' => \App\Models\ServiceTicketComment::class,
            'table' => 'service_ticket_comments',
            'allowed_columns' => [
                'id',
                'service_ticket_id',
                'user_id',
                'comment',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'comment',
            ],
            'relationships' => [
                'ticket' => [
                    'table' => 'service_tickets',
                    'local_key' => 'service_ticket_id',
                    'foreign_key' => 'id',
                ],
                'user' => [
                    'table' => 'users',
                    'local_key' => 'user_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'ticket_access',
        ],

        'project_follow_ups' => [
            'model' => \App\Models\ProjectFollowUp::class,
            'table' => 'project_follow_ups',
            'allowed_columns' => [
                'id',
                'project_id',
                'employee_id',
                'created_by',
                'department_id',
                'sub_department_id',
                'follow_up_date',
                'notes',
                'status',
                'resolved_date',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'notes',
                'status',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'employee' => [
                    'table' => 'employees',
                    'local_key' => 'employee_id',
                    'foreign_key' => 'id',
                ],
                'creator' => [
                    'table' => 'users',
                    'local_key' => 'created_by',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'follow_up_date',
            'access_rule' => 'project_access',
        ],

        'project_call_logs' => [
            'model' => \App\Models\ProjectCallLog::class,
            'table' => 'project_call_logs',
            'allowed_columns' => [
                'id',
                'project_id',
                'department_id',
                'user_id',
                'call_no',
                'notes',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'call_no',
                'notes',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
                'user' => [
                    'table' => 'users',
                    'local_key' => 'user_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'project_files' => [
            'model' => \App\Models\ProjectFile::class,
            'table' => 'project_files',
            'allowed_columns' => [
                'id',
                'project_id',
                'task_id',
                'department_id',
                'filename',
                'header_text',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'filename',
                'header_text',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'task' => [
                    'table' => 'tasks',
                    'local_key' => 'task_id',
                    'foreign_key' => 'id',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_key' => 'department_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'project_invoice_details' => [
            'model' => \App\Models\ProjectInvoiceDetail::class,
            'table' => 'project_invoice_details',
            'allowed_columns' => [
                'id',
                'project_id',
                'invoice_type',
                'invoice_date',
                'amount',
                'file_name',
                'original_file_name',
                'file_path',
                'uploaded_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'invoice_type',
                'file_name',
                'original_file_name',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'uploader' => [
                    'table' => 'users',
                    'local_key' => 'uploaded_by',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'invoice_date',
            'access_rule' => 'invoice_details_access',
        ],

        'project_design_details' => [
            'model' => \App\Models\ProjectDesignDetail::class,
            'table' => 'project_design_details',
            'allowed_columns' => [
                'id',
                'project_id',
                'task_id',
                'employee_id',
                'created_by',
                'department_id',
                'sub_department_id',
                'name',
                'phone',
                'address',
                'ahj',
                'roof_area',
                'mod',
                'array_area',
                'inv',
                'utility_meter',
                'kw_rating',
                'ac_cec',
                'apn',
                'stories',
                'roof_type',
                'rafter',
                'slope',
                'msp',
                'array_azi',
                'design_notes',
                'assign_notes',
                'follow_up',
                'follow_up_date',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'name',
                'phone',
                'address',
                'ahj',
                'apn',
                'design_notes',
                'assign_notes',
            ],
            'relationships' => [
                'project' => [
                    'table' => 'projects',
                    'local_key' => 'project_id',
                    'foreign_key' => 'id',
                ],
                'task' => [
                    'table' => 'tasks',
                    'local_key' => 'task_id',
                    'foreign_key' => 'id',
                ],
                'employee' => [
                    'table' => 'employees',
                    'local_key' => 'employee_id',
                    'foreign_key' => 'id',
                ],
                'creator' => [
                    'table' => 'users',
                    'local_key' => 'created_by',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'finance_options' => [
            'model' => \App\Models\FinanceOption::class,
            'table' => 'finance_options',
            'allowed_columns' => [
                'id',
                'name',
                'loan_id',
                'production_requirements',
                'positive_variance',
                'negative_variance',
                'dealer_fee',
                'pto_restriction',
                'no_of_days',
                'holdback',
                'dollar_watt_value',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
            ],
            'relationships' => [],
            'sensitive_columns' => [
                'dealer_fee',
                'dollar_watt_value',
                'positive_variance',
                'negative_variance',
            ],
            'default_sort_column' => 'name',
            'access_rule' => 'finance_access',
        ],

        'loan_terms' => [
            'model' => \App\Models\LoanTerm::class,
            'table' => 'loan_terms',
            'allowed_columns' => [
                'id',
                'finance_option_id',
                'year',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'year',
            ],
            'relationships' => [
                'finance' => [
                    'table' => 'finance_options',
                    'local_key' => 'finance_option_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'year',
            'access_rule' => 'finance_access',
        ],

        'loan_aprs' => [
            'model' => \App\Models\LoanApr::class,
            'table' => 'loan_aprs',
            'allowed_columns' => [
                'id',
                'loan_term_id',
                'finance_option_id',
                'apr',
                'dealer_fee',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [],
            'relationships' => [
                'loan' => [
                    'table' => 'loan_terms',
                    'local_key' => 'loan_term_id',
                    'foreign_key' => 'id',
                ],
                'finance' => [
                    'table' => 'finance_options',
                    'local_key' => 'finance_option_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [
                'dealer_fee',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'finance_access',
        ],

        'office_costs' => [
            'model' => \App\Models\OfficeCost::class,
            'table' => 'office_costs',
            'allowed_columns' => [
                'id',
                'cost',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [],
            'relationships' => [],
            'sensitive_columns' => [
                'cost',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'finance_access',
        ],

        'labor_costs' => [
            'model' => \App\Models\LaborCost::class,
            'table' => 'labor_costs',
            'allowed_columns' => [
                'id',
                'cost',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [],
            'relationships' => [],
            'sensitive_columns' => [
                'cost',
            ],
            'default_sort_column' => 'created_at',
            'access_rule' => 'finance_access',
        ],

        'sales_partners' => [
            'model' => \App\Models\SalesPartner::class,
            'table' => 'sales_partners',
            'allowed_columns' => [
                'id',
                'name',
                'image',
                'email',
                'phone',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            'searchable_columns' => [
                'name',
                'email',
                'phone',
            ],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'customer_access',
        ],

        /*
         * NOTE: project_finances, project_expenses, project_revenue and
         * profitability_reports were placeholder tables that never existed in the
         * database — they only caused "Base table not found" errors. The real data
         * lives in customer_finances (financing, contract amount = revenue),
         * account_transactions (payments/remittances) and the projects.actual_*
         * cost columns. The finance_summary, customer_revenue and profitability_report
         * intents now map to those real tables, so these placeholders were removed.
         */

        'roles' => [
            'model' => \Spatie\Permission\Models\Role::class,
            'table' => 'roles',
            'allowed_columns' => [
                'id',
                'name',
                'guard_name',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'name',
                'guard_name',
            ],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'admin_only',
        ],

        'model_has_roles' => [
            'model' => null,
            'table' => 'model_has_roles',
            'allowed_columns' => [
                'role_id',
                'model_type',
                'model_id',
            ],
            'searchable_columns' => [
                'model_type',
            ],
            'relationships' => [
                'role' => [
                    'table' => 'roles',
                    'local_key' => 'role_id',
                    'foreign_key' => 'id',
                ],
                'user' => [
                    'table' => 'users',
                    'local_key' => 'model_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'role_id',
            'access_rule' => 'admin_only',
        ],

        'permissions' => [
            'model' => \Spatie\Permission\Models\Permission::class,
            'table' => 'permissions',
            'allowed_columns' => [
                'id',
                'name',
                'guard_name',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'name',
                'guard_name',
            ],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'admin_only',
        ],

        // Project activity / department-move history
        'activity_log' => [
            'model' => null,
            'table' => 'activity_log',
            'allowed_columns' => [
                'id',
                'log_name',
                'description',
                'subject_type',
                'subject_id',
                'event',
                'causer_id',
                'properties',
                'created_at',
                'updated_at',
            ],
            'searchable_columns' => [
                'description',
                'log_name',
                'event',
            ],
            'relationships' => [
                'project' => [
                    'table'       => 'projects',
                    'local_key'   => 'subject_id',
                    'foreign_key' => 'id',
                ],
                'causer' => [
                    'table'       => 'users',
                    'local_key'   => 'causer_id',
                    'foreign_key' => 'id',
                ],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        /*
        |----------------------------------------------------------------------
        | Configuration / lookup tables (global, non-row-scoped)
        |----------------------------------------------------------------------
        */

        'adder_types' => [
            'model' => \App\Models\AdderType::class,
            'table' => 'adder_types',
            'allowed_columns' => ['id', 'name', 'tag', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name', 'tag'],
            'relationships' => [
                'subTypes' => ['table' => 'adder_sub_types', 'local_key' => 'id', 'foreign_key' => 'adder_type_id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'adder_sub_types' => [
            'model' => \App\Models\AdderSubType::class,
            'table' => 'adder_sub_types',
            'allowed_columns' => ['id', 'adder_type_id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [
                'adderType' => ['table' => 'adder_types', 'local_key' => 'adder_type_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'adder_units' => [
            'model' => \App\Models\AdderUnit::class,
            'table' => 'adder_units',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'adders' => [
            'model' => \App\Models\Adder::class,
            'table' => 'adders',
            'allowed_columns' => ['id', 'adder_type_id', 'adder_unit_id', 'price', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => [],
            'relationships' => [
                'adderType' => ['table' => 'adder_types', 'local_key' => 'adder_type_id', 'foreign_key' => 'id'],
                'adderUnit' => ['table' => 'adder_units', 'local_key' => 'adder_unit_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => ['price'],
            'default_sort_column' => 'created_at',
            'access_rule' => 'department_access',
        ],

        'customer_adders' => [
            'model' => \App\Models\CustomerAdder::class,
            'table' => 'customer_adders',
            'allowed_columns' => ['id', 'customer_id', 'adder_type_id', 'adder_sub_type_id', 'adder_unit_id', 'amount', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => [],
            'relationships' => [
                'customer' => ['table' => 'customers', 'local_key' => 'customer_id', 'foreign_key' => 'id'],
                'adderType' => ['table' => 'adder_types', 'local_key' => 'adder_type_id', 'foreign_key' => 'id'],
                'adderSubType' => ['table' => 'adder_sub_types', 'local_key' => 'adder_sub_type_id', 'foreign_key' => 'id'],
                'adderUnit' => ['table' => 'adder_units', 'local_key' => 'adder_unit_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => ['amount'],
            'default_sort_column' => 'created_at',
            'access_rule' => 'customer_access',
        ],

        'battery_types' => [
            'model' => \App\Models\BatteryType::class,
            'table' => 'battery_types',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'inverter_types' => [
            'model' => \App\Models\InverterType::class,
            'table' => 'inverter_types',
            'allowed_columns' => ['id', 'name', 'tags', 'inverter_efficiency_rating', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name', 'tags'],
            'relationships' => [
                'rates' => ['table' => 'inverter_type_rates', 'local_key' => 'id', 'foreign_key' => 'inverter_type_id'],
                'modules' => ['table' => 'module_types', 'local_key' => 'id', 'foreign_key' => 'inverter_type_id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'inverter_type_rates' => [
            'model' => \App\Models\InverterTypeRate::class,
            'table' => 'inverter_type_rates',
            'allowed_columns' => ['id', 'inverter_type_id', 'base_cost', 'internal_base_cost', 'internal_labor_cost', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => [],
            'relationships' => [
                'inverterType' => ['table' => 'inverter_types', 'local_key' => 'inverter_type_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => ['base_cost', 'internal_base_cost', 'internal_labor_cost'],
            'default_sort_column' => 'created_at',
            'access_rule' => 'finance_access',
        ],

        'module_types' => [
            'model' => \App\Models\ModuleType::class,
            'table' => 'module_types',
            'allowed_columns' => ['id', 'inverter_type_id', 'name', 'value', 'amount', 'internal_module_cost', 'ptc_rating', 'voc_rating', 'isc_rating', 'weight', 'square_footage', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [
                'inverterType' => ['table' => 'inverter_types', 'local_key' => 'inverter_type_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => ['amount', 'internal_module_cost'],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'utility_companies' => [
            'model' => \App\Models\UtilityCompany::class,
            'table' => 'utility_companies',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'sub_contractors' => [
            'model' => \App\Models\SubContractor::class,
            'table' => 'sub_contractors',
            'allowed_columns' => ['id', 'name', 'email', 'phone', 'image', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name', 'email', 'phone'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'customer_access',
        ],

        'user_types' => [
            'model' => \App\Models\UserType::class,
            'table' => 'user_types',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'user_access',
        ],

        'tools' => [
            'model' => \App\Models\Tool::class,
            'table' => 'tools',
            'allowed_columns' => ['id', 'department_id', 'name', 'description', 'file', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name', 'description'],
            'relationships' => [
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'calls' => [
            'model' => \App\Models\Call::class,
            'table' => 'calls',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'call_scripts' => [
            'model' => \App\Models\CallScript::class,
            'table' => 'call_scripts',
            'allowed_columns' => ['id', 'call_id', 'department_id', 'script', 'extra_filter', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['script'],
            'relationships' => [
                'call' => ['table' => 'calls', 'local_key' => 'call_id', 'foreign_key' => 'id'],
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'department_access',
        ],

        'assign_departments' => [
            'model' => \App\Models\AssignDepartment::class,
            'table' => 'assign_departments',
            'allowed_columns' => ['id', 'department_id', 'employee_id', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => [],
            'relationships' => [
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
                'employee' => ['table' => 'employees', 'local_key' => 'employee_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'department_access',
        ],

        'project_department_fields' => [
            'model' => \App\Models\ProjectDepartmentField::class,
            'table' => 'project_department_fields',
            'allowed_columns' => ['id', 'department_id', 'field_name', 'created_at', 'updated_at'],
            'searchable_columns' => ['field_name'],
            'relationships' => [
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'department_id',
            'access_rule' => 'department_access',
        ],

        'technician_schedules' => [
            'model' => \App\Models\TechnicianSchedule::class,
            'table' => 'technician_schedules',
            'allowed_columns' => ['id', 'technician_id', 'date', 'start_time', 'end_time', 'start_location_address', 'start_lat', 'start_lng', 'current_lat', 'current_lng', 'is_available', 'created_at', 'updated_at'],
            'searchable_columns' => ['start_location_address'],
            'relationships' => [
                'technician' => ['table' => 'users', 'local_key' => 'technician_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'date',
            'access_rule' => 'user_access',
        ],

        /*
        |----------------------------------------------------------------------
        | Project-linked operational tables (row-scoped for non-admin roles)
        |----------------------------------------------------------------------
        */

        'department_notes' => [
            'model' => \App\Models\DepartmentNote::class,
            'table' => 'department_notes',
            'allowed_columns' => ['id', 'project_id', 'task_id', 'department_id', 'notes', 'show_to_customer', 'user_id', 'created_at', 'updated_at'],
            'searchable_columns' => ['notes'],
            'relationships' => [
                'project' => ['table' => 'projects', 'local_key' => 'project_id', 'foreign_key' => 'id'],
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
                'user' => ['table' => 'users', 'local_key' => 'user_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'notes_mentions' => [
            'model' => \App\Models\NotesMention::class,
            'table' => 'notes_mentions',
            'allowed_columns' => ['id', 'project_id', 'department_id', 'employee_id', 'created_at', 'updated_at'],
            'searchable_columns' => [],
            'relationships' => [
                'project' => ['table' => 'projects', 'local_key' => 'project_id', 'foreign_key' => 'id'],
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
                'employee' => ['table' => 'employees', 'local_key' => 'employee_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'project_adders_locks' => [
            'model' => \App\Models\ProjectAddersLock::class,
            'table' => 'project_adders_locks',
            'allowed_columns' => ['id', 'project_id', 'user_id', 'status', 'created_at', 'updated_at'],
            'searchable_columns' => ['status'],
            'relationships' => [
                'project' => ['table' => 'projects', 'local_key' => 'project_id', 'foreign_key' => 'id'],
                'user' => ['table' => 'users', 'local_key' => 'user_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'project_access',
        ],

        'site_surveys' => [
            'model' => \App\Models\SiteSurvey::class,
            'table' => 'site_surveys',
            'allowed_columns' => ['id', 'project_id', 'technician_id', 'survey_date', 'start_time', 'end_time', 'customer_address', 'customer_lat', 'customer_lng', 'estimated_travel_time', 'estimated_distance', 'status', 'actual_start_time', 'actual_end_time', 'actual_lat', 'actual_lng', 'notes', 'created_at', 'updated_at'],
            'searchable_columns' => ['customer_address', 'status', 'notes'],
            'relationships' => [
                'project' => ['table' => 'projects', 'local_key' => 'project_id', 'foreign_key' => 'id'],
                'technician' => ['table' => 'users', 'local_key' => 'technician_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'survey_date',
            'access_rule' => 'project_access',
        ],

        'service_ticket_files' => [
            'model' => \App\Models\ServiceTicketFile::class,
            'table' => 'service_ticket_files',
            'allowed_columns' => ['id', 'service_ticket_id', 'comment_id', 'file_name', 'file_path', 'file_type', 'file_size', 'uploaded_by', 'created_at', 'updated_at'],
            'searchable_columns' => ['file_name', 'file_type'],
            'relationships' => [
                'ticket' => ['table' => 'service_tickets', 'local_key' => 'service_ticket_id', 'foreign_key' => 'id'],
                'uploader' => ['table' => 'users', 'local_key' => 'uploaded_by', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'ticket_access',
        ],

        /*
        |----------------------------------------------------------------------
        | Email module + admin-only tables
        |----------------------------------------------------------------------
        */

        'email_types' => [
            'model' => \App\Models\EmailType::class,
            'table' => 'email_types',
            'allowed_columns' => ['id', 'name', 'created_at', 'updated_at'],
            'searchable_columns' => ['name'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'name',
            'access_rule' => 'department_access',
        ],

        'email_scripts' => [
            'model' => \App\Models\EmailScript::class,
            'table' => 'email_scripts',
            'allowed_columns' => ['id', 'email_type_id', 'department_id', 'script', 'extra_filter', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['script'],
            'relationships' => [
                'emailType' => ['table' => 'email_types', 'local_key' => 'email_type_id', 'foreign_key' => 'id'],
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'department_access',
        ],

        'emails' => [
            'model' => \App\Models\Email::class,
            'table' => 'emails',
            'allowed_columns' => ['id', 'project_id', 'department_id', 'customer_id', 'subject', 'body', 'user_id', 'received_date', 'is_view', 'created_at', 'updated_at', 'deleted_at'],
            'searchable_columns' => ['subject', 'body'],
            'relationships' => [
                'project' => ['table' => 'projects', 'local_key' => 'project_id', 'foreign_key' => 'id'],
                'department' => ['table' => 'departments', 'local_key' => 'department_id', 'foreign_key' => 'id'],
                'customer' => ['table' => 'customers', 'local_key' => 'customer_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'received_date',
            'access_rule' => 'admin_only',
        ],

        'email_attachments' => [
            'model' => \App\Models\EmailAttachment::class,
            'table' => 'email_attachments',
            'allowed_columns' => ['id', 'email_id', 'file', 'created_at', 'updated_at'],
            'searchable_columns' => ['file'],
            'relationships' => [
                'email' => ['table' => 'emails', 'local_key' => 'email_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'admin_only',
        ],

        'new_tickets' => [
            'model' => \App\Models\NewTicket::class,
            'table' => 'new_tickets',
            'allowed_columns' => ['id', 'name', 'email', 'address', 'phone', 'message', 'status', 'created_at', 'updated_at'],
            'searchable_columns' => ['name', 'email', 'phone', 'message', 'status'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'admin_only',
        ],

        'saved_reports' => [
            'model' => \App\Models\SavedReport::class,
            'table' => 'saved_reports',
            'allowed_columns' => ['id', 'name', 'report_type', 'user_id', 'created_at', 'updated_at'],
            'searchable_columns' => ['name', 'report_type'],
            'relationships' => [
                'user' => ['table' => 'users', 'local_key' => 'user_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'admin_only',
        ],

        'deploy_logs' => [
            'model' => \App\Models\DeployLog::class,
            'table' => 'deploy_logs',
            'allowed_columns' => ['id', 'action', 'run_by', 'status', 'created_at'],
            'searchable_columns' => ['action', 'run_by', 'status'],
            'relationships' => [],
            'sensitive_columns' => [],
            'default_sort_column' => 'created_at',
            'access_rule' => 'admin_only',
        ],

        'model_has_permissions' => [
            'model' => null,
            'table' => 'model_has_permissions',
            'allowed_columns' => ['permission_id', 'model_type', 'model_id'],
            'searchable_columns' => ['model_type'],
            'relationships' => [
                'permission' => ['table' => 'permissions', 'local_key' => 'permission_id', 'foreign_key' => 'id'],
                'user' => ['table' => 'users', 'local_key' => 'model_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'permission_id',
            'access_rule' => 'admin_only',
        ],

        'role_has_permissions' => [
            'model' => null,
            'table' => 'role_has_permissions',
            'allowed_columns' => ['permission_id', 'role_id'],
            'searchable_columns' => [],
            'relationships' => [
                'permission' => ['table' => 'permissions', 'local_key' => 'permission_id', 'foreign_key' => 'id'],
                'role' => ['table' => 'roles', 'local_key' => 'role_id', 'foreign_key' => 'id'],
            ],
            'sensitive_columns' => [],
            'default_sort_column' => 'role_id',
            'access_rule' => 'admin_only',
        ],
    ],

    'manual_review' => [
        'projects',
        'customers',
        'customer_finances',
        'project_acceptances',
        'account_transactions',
        'project_invoice_details',
        'users',
        'roles',
        'permissions',
        'inverter_type_rates',
        'module_types',
        'adders',
        'customer_adders',
        'emails',
        'email_attachments',
        'new_tickets',
        'saved_reports',
        'deploy_logs',
        'model_has_permissions',
        'role_has_permissions',
    ],
];
