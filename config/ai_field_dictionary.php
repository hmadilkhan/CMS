<?php

/*
|--------------------------------------------------------------------------
| AI Field Dictionary
|--------------------------------------------------------------------------
|
| Plain-language, business-level descriptions of the CRM's tables, columns
| and coded values. The AI assistant uses this so it can explain ANY field a
| user asks about ("meter_spot_result kya hai?", "acceptance status ki values
| kya hoti hain?"), and so it can guide users who ask about something that
| does not exist.
|
| This is documentation only — it grants no data access. Access is still
| controlled entirely by config/ai_schema.php + AiPermissionService.
|
| Keep this in sync with config/ai_schema.php. The `ai:schema-audit` command
| reports columns that are allowed in the schema but undocumented here.
|
| Shape:
|   'tables' => [
|       '<table>' => [
|           'label'       => 'Human friendly name',
|           'description' => 'What the table represents.',
|           'columns'     => ['<column>' => 'Plain-language meaning.'],
|           'value_maps'  => ['<column>' => ['<stored value>' => 'meaning']],
|       ],
|   ],
|   'glossary' => ['TERM' => 'definition'],
|
*/

return [

    'tables' => [

        'projects' => [
            'label' => 'Projects',
            'description' => 'The core table. Each row is one solar installation job for a customer, moving through the milestone workflow from sale to Permission to Operate (PTO).',
            'columns' => [
                'id' => 'Internal unique project ID.',
                'customer_id' => 'The customer (homeowner) this project belongs to.',
                'department_id' => 'The department that currently owns/handles the project (its current lane).',
                'sub_department_id' => 'The sub-department / sub-lane the project currently sits in.',
                'sales_partner_user_id' => 'The user account of the sales partner/rep who sold this project.',
                'sub_contractor_user_id' => 'The user account of the sub-contractor assigned to this project.',
                'project_name' => 'The name/title of the project (usually the homeowner name).',
                'code' => 'The human-facing project code/number used to look the project up.',
                'start_date' => 'Date work on the project started.',
                'end_date' => 'Date the project work ended.',
                'completion_date' => 'Date the project was marked complete.',
                'budget' => 'Budgeted amount for the project (financial — restricted).',
                'description' => 'Free-text notes/description of the project.',
                'utility_company' => 'The electric utility company serving the property.',
                'ntp_approval_date' => 'Date Notice To Proceed (NTP) was approved — authorization to begin installation work.',
                'site_survey_link' => 'Link to the site survey document/report for the property.',
                'hoa' => 'Homeowners Association name (if the property is in an HOA).',
                'hoa_phone_number' => 'Contact phone number for the HOA.',
                'ahj' => 'Authority Having Jurisdiction — the local government body that issues permits.',
                'adders_approve_checkbox' => 'Flag indicating whether the project adders have been approved.',
                'mpu_required' => 'Whether a Main Panel Upgrade (electrical panel upgrade) is required before solar.',
                'meter_spot_requestd_date' => 'Date the utility meter-spot was requested.',
                'meter_spot_requestd_number' => 'Reference number for the meter-spot request.',
                'meter_spot_result' => 'Outcome/result of the utility meter-spot request.',
                'permitting_submittion_date' => 'Date the building permit was submitted to the AHJ.',
                'permitting_approval_date' => 'Date the building permit was approved by the AHJ.',
                'hoa_approval_request_date' => 'Date HOA approval was requested.',
                'hoa_approval_date' => 'Date HOA approval was received.',
                'solar_install_date' => 'Date the solar panels were physically installed on the roof.',
                'battery_install_date' => 'Date the battery storage system was installed (if included).',
                'monitoring_link' => 'Link to the system production monitoring portal.',
                'mpu_install_date' => 'Date the Main Panel Upgrade was installed.',
                'rough_inspection_date' => 'Date of the mid-construction (rough) inspection.',
                'final_inspection_date' => 'Date of the final post-installation inspection by the AHJ.',
                'pto_submission_date' => 'Date the PTO (Permission To Operate) request was submitted to the utility.',
                'pto_approval_date' => 'Date the utility granted PTO and the system was allowed to turn on.',
                'coc_packet_mailed_out_date' => 'Date the Certificate of Completion (COC) packet was mailed to the customer.',
                'placards_ordered' => 'Whether the required electrical placards/labels have been ordered.',
                'placards_note' => 'Notes about the placards.',
                'production_value_achieved' => 'The actual energy production value the system achieved.',
                'fire_review_required' => 'Whether a fire department review/inspection is required.',
                'fire_inspection_date' => 'Date of the fire department inspection.',
                'office_cost' => 'Office overhead cost allocated to the project (financial — restricted).',
                'actual_permit_fee' => 'Actual permit fee paid (financial — restricted).',
                'actual_labor_cost' => 'Actual labor cost incurred (financial — restricted).',
                'actual_material_cost' => 'Actual material cost incurred (financial — restricted).',
                'overwrite_base_price' => 'Manual override of the base price (financial — restricted).',
                'overwrite_panel_price' => 'Manual override of the per-panel price (financial — restricted).',
                'pre_estimated_material_costs' => 'Estimated material cost before installation (financial — restricted).',
                'pre_estimated_labor_costs' => 'Estimated labor cost before installation (financial — restricted).',
                'pre_estimated_permit_costs' => 'Estimated permit cost before installation (financial — restricted).',
                'created_at' => 'When the project record was created.',
                'updated_at' => 'When the project was last updated.',
                'deleted_at' => 'When the project was soft-deleted (NULL means active).',
            ],
            'value_maps' => [
                'mpu_required' => ['1' => 'Yes — MPU required', '0' => 'No', 'Yes' => 'MPU required', 'No' => 'Not required'],
                'fire_review_required' => ['1' => 'Fire review required', '0' => 'Not required'],
            ],
        ],

        'customers' => [
            'label' => 'Customers',
            'description' => 'The homeowner/client for each project, including address, contact details and the sold system specs.',
            'columns' => [
                'id' => 'Internal unique customer ID.',
                'first_name' => 'Customer first name.',
                'last_name' => 'Customer last name.',
                'street' => 'Street address of the property.',
                'city' => 'City of the property.',
                'state' => 'State of the property.',
                'zipcode' => 'ZIP/postal code.',
                'phone' => 'Customer phone number.',
                'email' => 'Customer email address.',
                'preferred_language' => 'The language the customer prefers to communicate in.',
                'sales_partner_id' => 'The sales partner who sold to this customer.',
                'sub_contractor_id' => 'The sub-contractor associated with this customer.',
                'sold_date' => 'Date the deal was sold/signed.',
                'panel_qty' => 'Number of solar panels sold.',
                'inverter_type_id' => 'The type of inverter selected.',
                'module_type_id' => 'The type of solar module/panel selected.',
                'inverter_qty' => 'Number of inverters.',
                'module_value' => 'The wattage/value rating of each module.',
                'notes' => 'Free-text notes about the customer.',
                'is_adu' => 'Whether the property includes an Accessory Dwelling Unit (a secondary home unit).',
                'loan_id' => 'External loan/financing reference ID (restricted).',
                'sold_production_value' => 'The production value promised at point of sale (restricted).',
                'created_at' => 'When the customer record was created.',
                'updated_at' => 'When the customer was last updated.',
                'deleted_at' => 'When the customer was soft-deleted (NULL means active).',
            ],
            'value_maps' => [
                'is_adu' => ['1' => 'Yes — has an ADU', '0' => 'No ADU'],
            ],
        ],

        'customer_finances' => [
            'label' => 'Customer Finances',
            'description' => 'The financial breakdown of each customer deal — contract amount, costs, fees, commission and holdback. Restricted to Finance/Admin roles.',
            'columns' => [
                'id' => 'Internal unique record ID.',
                'customer_id' => 'The customer this finance record belongs to.',
                'finance_option_id' => 'The selected financing option/product.',
                'loan_term_id' => 'The loan term (length in years) selected.',
                'loan_apr_id' => 'The loan APR (interest rate) selected.',
                'contract_amount' => 'Total signed contract/deal amount.',
                'redline_costs' => 'The redline (baseline) cost of the deal.',
                'adders' => 'Adders applied to the deal (extra options/upgrades).',
                'commission' => 'Commission amount on the deal.',
                'dealer_fee' => 'Dealer fee percentage charged by the financier.',
                'dealer_fee_amount' => 'Dealer fee in dollars.',
                'total_overwrite_base_price' => 'Total overridden base price.',
                'total_overwrite_panel_price' => 'Total overridden per-panel price.',
                'module_type_cost' => 'Cost of the module type used.',
                'inverter_base_cost' => 'Base cost of the inverter.',
                'holdback_amount' => 'Amount held back from payout until milestones are met.',
                'third_party_credit' => 'Any third-party credit applied to the deal.',
                'customer_portion' => 'The portion the customer pays directly (cash/down payment).',
                'created_at' => 'When the record was created.',
                'updated_at' => 'When the record was last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'finance_options' => [
            'label' => 'Finance Options',
            'description' => 'The financing products/plans customers can choose (loan, cash, PPA, etc.), with their rules.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Name of the financing option.',
                'loan_id' => 'Whether this option is a loan product (and its reference).',
                'production_requirements' => 'Minimum production requirement tied to this option.',
                'positive_variance' => 'Allowed positive variance threshold.',
                'negative_variance' => 'Allowed negative variance threshold.',
                'dealer_fee' => 'Whether/what dealer fee applies (restricted).',
                'pto_restriction' => 'Any PTO-related restriction on payout for this option.',
                'no_of_days' => 'Number of days associated with the option (e.g. payment window).',
                'holdback' => 'Holdback rule for this option.',
                'dollar_watt_value' => 'Dollar-per-watt value used for this option (restricted).',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'account_transactions' => [
            'label' => 'Account Transactions',
            'description' => 'Financial transactions / remittances against projects — milestone payments to payees, with deductions. Restricted to Finance/Admin.',
            'columns' => [
                'id' => 'Internal unique transaction ID.',
                'project_id' => 'The project this transaction relates to.',
                'payee' => 'Who was paid.',
                'milestone' => 'The milestone the payment is tied to (e.g. install, PTO).',
                'amount' => 'Transaction amount.',
                'deduction_amount' => 'Amount deducted from the payment.',
                'transaction_date' => 'Date of the transaction.',
                'transaction_details' => 'Free-text details/notes about the transaction.',
                'created_at' => 'When the record was created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'project_invoice_details' => [
            'label' => 'Invoice Details',
            'description' => 'Project invoice records with labor/material type, invoice date, amount, uploaded file, and uploader. Requires Invoice Details permission.',
            'columns' => [
                'id' => 'Internal unique invoice detail ID.',
                'project_id' => 'The project this invoice belongs to.',
                'invoice_type' => 'Invoice type: labor or material.',
                'invoice_date' => 'Invoice date.',
                'amount' => 'Invoice amount.',
                'file_name' => 'Stored invoice file name.',
                'original_file_name' => 'Original uploaded invoice file name.',
                'file_path' => 'Stored public file path for the invoice upload.',
                'uploaded_by' => 'User who uploaded the invoice file.',
                'created_at' => 'When the invoice detail was created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'project_acceptances' => [
            'label' => 'Project Acceptances',
            'description' => 'Sales-partner sign-off on a project\'s details (specs and financials). Tracks pending/approved/rejected status. Financial fields restricted.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project being accepted.',
                'sales_partner_id' => 'The sales partner whose acceptance is recorded.',
                'image' => 'Attached image/signature for the acceptance.',
                'action_by' => 'The user who actioned (approved/rejected) the acceptance.',
                'status' => 'Acceptance status: pending, approved or rejected.',
                'approved_date' => 'Date the acceptance was approved.',
                'reason' => 'Reason given (typically on rejection).',
                'panel_qty' => 'Panel quantity recorded at acceptance.',
                'inverter_name' => 'Inverter recorded at acceptance.',
                'inverter_base_price' => 'Inverter base price at acceptance (restricted).',
                'dealer_fee_amount' => 'Dealer fee amount at acceptance (restricted).',
                'module_qty_price' => 'Module quantity price at acceptance (restricted).',
                'modules_amount' => 'Modules amount at acceptance (restricted).',
                'contract_amount' => 'Contract amount at acceptance (restricted).',
                'redline_costs' => 'Redline costs at acceptance (restricted).',
                'adders_amount' => 'Adders amount at acceptance (restricted).',
                'commission_amount' => 'Commission amount at acceptance (restricted).',
                'adders_list' => 'List of adders recorded at acceptance.',
                'notes' => 'Notes recorded at acceptance.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'status' => ['0' => 'Pending', '1' => 'Approved', '2' => 'Rejected'],
            ],
        ],

        'tasks' => [
            'label' => 'Tasks',
            'description' => 'Work items inside a project. Each task records the department/sub-department lane, the assigned employee, and a status. The latest task usually reflects where the project currently sits.',
            'columns' => [
                'id' => 'Internal unique task ID.',
                'project_id' => 'The project this task belongs to.',
                'employee_id' => 'The employee assigned to the task.',
                'user_id' => 'The user who created/owns the task.',
                'department_id' => 'The department (lane) of the task.',
                'sub_department_id' => 'The sub-department (sub-lane) of the task.',
                'notes' => 'Task notes.',
                'assign_to_notes' => 'Notes left for the assignee.',
                'status' => 'Task status.',
                'created_at' => 'When the task was created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
            'value_maps' => [
                'status' => [
                    'In-Progress' => 'Currently being worked on',
                    'Completed' => 'Finished',
                    'Hold' => 'Paused / on hold',
                    'Cancelled' => 'Cancelled',
                ],
            ],
        ],

        'service_tickets' => [
            'label' => 'Service Tickets',
            'description' => 'Support issues, service requests or bugs raised against a project, with a priority and an open/resolved status.',
            'columns' => [
                'id' => 'Internal unique ticket ID.',
                'project_id' => 'The project the ticket is about.',
                'user_id' => 'The user who created the ticket.',
                'subject' => 'Short summary of the ticket.',
                'assigned_to' => 'The user the ticket is assigned to.',
                'priority' => 'How urgent the ticket is.',
                'notes' => 'Ticket details/notes.',
                'status' => 'Whether the ticket is still open or resolved.',
                'created_at' => 'When the ticket was created.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'priority' => ['High' => 'High priority', 'Medium' => 'Medium priority', 'Low' => 'Low priority'],
                'status' => ['Pending' => 'Open / not yet resolved', 'Resolved' => 'Resolved / closed'],
            ],
        ],

        'service_ticket_comments' => [
            'label' => 'Service Ticket Comments',
            'description' => 'Comment thread on a service ticket.',
            'columns' => [
                'id' => 'Internal unique comment ID.',
                'service_ticket_id' => 'The ticket this comment belongs to.',
                'user_id' => 'The user who wrote the comment.',
                'comment' => 'The comment text.',
                'created_at' => 'When the comment was posted.',
                'updated_at' => 'When last edited.',
            ],
        ],

        'service_ticket_files' => [
            'label' => 'Service Ticket Files',
            'description' => 'File attachments uploaded to a service ticket or one of its comments.',
            'columns' => [
                'id' => 'Internal unique file ID.',
                'service_ticket_id' => 'The ticket the file belongs to.',
                'comment_id' => 'The comment the file is attached to (if any).',
                'file_name' => 'Original file name.',
                'file_path' => 'Stored path of the file.',
                'file_type' => 'File MIME/type.',
                'file_size' => 'File size in bytes.',
                'uploaded_by' => 'The user who uploaded the file.',
                'created_at' => 'When uploaded.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'project_follow_ups' => [
            'label' => 'Project Follow Ups',
            'description' => 'Scheduled follow-up actions on a project, with a due date and pending/resolved status.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project to follow up on.',
                'employee_id' => 'The employee responsible for the follow-up.',
                'created_by' => 'The user who scheduled the follow-up.',
                'department_id' => 'The department for the follow-up.',
                'sub_department_id' => 'The sub-department for the follow-up.',
                'follow_up_date' => 'The date the follow-up is due.',
                'notes' => 'Follow-up notes.',
                'status' => 'Whether the follow-up is still pending or resolved.',
                'resolved_date' => 'Date the follow-up was resolved.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'status' => ['Pending' => 'Not yet done', 'Resolved' => 'Completed'],
            ],
        ],

        'project_call_logs' => [
            'label' => 'Project Call Logs',
            'description' => 'Log of phone calls made on a project, per department.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project the call relates to.',
                'department_id' => 'The department that made the call.',
                'user_id' => 'The user who logged the call.',
                'call_no' => 'The call number/sequence.',
                'notes' => 'Notes about the call.',
                'created_at' => 'When the call was logged.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'project_files' => [
            'label' => 'Project Files',
            'description' => 'Documents and files attached to a project, organised by department/task.',
            'columns' => [
                'id' => 'Internal unique file ID.',
                'project_id' => 'The project the file belongs to.',
                'task_id' => 'The task the file is linked to.',
                'department_id' => 'The department the file belongs to.',
                'filename' => 'The stored file name.',
                'header_text' => 'A heading/label describing the file.',
                'created_at' => 'When uploaded.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'project_design_details' => [
            'label' => 'Project Design Details',
            'description' => 'Technical design specifications for a project — roof, array, electrical and engineering details.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project being designed.',
                'task_id' => 'The related task.',
                'employee_id' => 'The assigned designer/employee.',
                'created_by' => 'The user who created the design record.',
                'department_id' => 'The department.',
                'sub_department_id' => 'The sub-department.',
                'name' => 'Contact/site name on the design.',
                'phone' => 'Contact phone for the design/site.',
                'address' => 'Site address.',
                'ahj' => 'Authority Having Jurisdiction for permitting.',
                'roof_area' => 'Total roof area available.',
                'mod' => 'Module specification/count.',
                'array_area' => 'Area covered by the solar array.',
                'inv' => 'Inverter specification.',
                'utility_meter' => 'Utility meter details.',
                'kw_rating' => 'System size in kW.',
                'ac_cec' => 'AC CEC rating of the system.',
                'apn' => 'Assessor\'s Parcel Number of the property.',
                'stories' => 'Number of stories of the building.',
                'roof_type' => 'Type of roof.',
                'rafter' => 'Rafter details.',
                'slope' => 'Roof slope.',
                'msp' => 'Main Service Panel details.',
                'array_azi' => 'Array azimuth (orientation).',
                'design_notes' => 'Design notes.',
                'assign_notes' => 'Assignment notes.',
                'follow_up' => 'Whether a follow-up is needed on the design.',
                'follow_up_date' => 'Design follow-up date.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'site_surveys' => [
            'label' => 'Site Surveys',
            'description' => 'Scheduled physical site inspections of the customer property by a technician, with planned vs actual times/locations.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project being surveyed.',
                'technician_id' => 'The technician (user) assigned to the survey.',
                'survey_date' => 'The scheduled survey date.',
                'start_time' => 'Planned start time.',
                'end_time' => 'Planned end time.',
                'customer_address' => 'Address where the survey takes place.',
                'customer_lat' => 'Latitude of the customer property.',
                'customer_lng' => 'Longitude of the customer property.',
                'estimated_travel_time' => 'Estimated travel time to the site.',
                'estimated_distance' => 'Estimated travel distance to the site.',
                'status' => 'Survey status.',
                'actual_start_time' => 'Actual time the technician started.',
                'actual_end_time' => 'Actual time the technician finished.',
                'actual_lat' => 'Actual latitude recorded.',
                'actual_lng' => 'Actual longitude recorded.',
                'notes' => 'Survey notes.',
                'created_at' => 'When scheduled.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'status' => [
                    'scheduled' => 'Scheduled',
                    'in_progress' => 'In progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ],
        ],

        'technician_schedules' => [
            'label' => 'Technician Schedules',
            'description' => 'Daily availability and live location of technicians, used to plan site surveys.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'technician_id' => 'The technician (user).',
                'date' => 'The schedule date.',
                'start_time' => 'Shift start time.',
                'end_time' => 'Shift end time.',
                'start_location_address' => 'Where the technician starts the day.',
                'start_lat' => 'Start latitude.',
                'start_lng' => 'Start longitude.',
                'current_lat' => 'Current latitude.',
                'current_lng' => 'Current longitude.',
                'is_available' => 'Whether the technician is available that day.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'is_available' => ['1' => 'Available', '0' => 'Not available'],
            ],
        ],

        'departments' => [
            'label' => 'Departments',
            'description' => 'The departments (workflow lanes) a project moves through.',
            'columns' => [
                'id' => 'Internal unique department ID.',
                'name' => 'Department name (the lane).',
                'document_length' => 'Required number of documents for this department.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'sub_departments' => [
            'label' => 'Sub Departments',
            'description' => 'Sub-lanes within a department, ordered by workflow sequence.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'department_id' => 'The parent department.',
                'name' => 'Sub-department name.',
                'order' => 'The position of this sub-lane in the workflow order.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'department_notes' => [
            'label' => 'Department Notes',
            'description' => 'Notes left on a project by a department, optionally visible to the customer.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project the note is on.',
                'task_id' => 'The related task.',
                'department_id' => 'The department that wrote the note.',
                'notes' => 'The note text.',
                'show_to_customer' => 'Whether the note is shown to the customer.',
                'user_id' => 'The user who wrote the note.',
                'created_at' => 'When written.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'show_to_customer' => ['1' => 'Visible to customer', '0' => 'Internal only'],
            ],
        ],

        'notes_mentions' => [
            'label' => 'Notes Mentions',
            'description' => 'Records where an employee is @mentioned in a project/department note.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project.',
                'department_id' => 'The department.',
                'employee_id' => 'The employee who was mentioned.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'project_adders_locks' => [
            'label' => 'Project Adders Locks',
            'description' => 'Tracks whether a project\'s adders are locked or unlocked for editing.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The project.',
                'user_id' => 'The user who locked/unlocked.',
                'status' => 'Lock state.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'status' => ['locked' => 'Adders locked', 'unlocked' => 'Adders unlocked'],
            ],
        ],

        'employees' => [
            'label' => 'Employees',
            'description' => 'Staff members who do the work on projects. Each employee links to a user login account.',
            'columns' => [
                'id' => 'Internal unique employee ID.',
                'name' => 'Employee full name.',
                'code' => 'Employee code.',
                'email' => 'Employee email.',
                'phone' => 'Employee phone number.',
                'image' => 'Profile photo.',
                'joined_date' => 'Date the employee joined.',
                'user_id' => 'The login user account linked to this employee.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'employee_departments' => [
            'label' => 'Employee Departments',
            'description' => 'Which departments each employee is allowed to work in (many-to-many link).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'employee_id' => 'The employee.',
                'department_id' => 'A department the employee is assigned to.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'assign_departments' => [
            'label' => 'Assign Departments',
            'description' => 'Department assignment records linking employees to departments.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'department_id' => 'The department.',
                'employee_id' => 'The employee assigned.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'project_department_fields' => [
            'label' => 'Project Department Fields',
            'description' => 'Configurable custom field names each department tracks on a project.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'department_id' => 'The department the field belongs to.',
                'field_name' => 'The name of the custom field.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'users' => [
            'label' => 'Users',
            'description' => 'Login accounts for everyone who uses the CRM (employees, sales partners, sub-contractors, admins).',
            'columns' => [
                'id' => 'Internal unique user ID.',
                'name' => 'Display name.',
                'email' => 'Login email.',
                'username' => 'Login username.',
                'email_verified_at' => 'When the email was verified.',
                'user_type_id' => 'The type/category of user.',
                'sales_partner_id' => 'Linked sales partner (if the user is a sales partner).',
                'image' => 'Profile photo.',
                'phone' => 'Phone number.',
                'email_preference' => 'Email notification preference.',
                'address' => 'User address.',
                'latitude' => 'User location latitude.',
                'longitude' => 'User location longitude.',
                'created_at' => 'When the account was created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'user_types' => [
            'label' => 'User Types',
            'description' => 'Categories of user accounts.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'The user type name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'roles' => [
            'label' => 'Roles',
            'description' => 'Security roles that determine what each user can access (e.g. Admin, Manager, Employee).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Role name.',
                'guard_name' => 'The auth guard the role applies to.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'permissions' => [
            'label' => 'Permissions',
            'description' => 'Individual permissions that can be granted to roles or users.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Permission name.',
                'guard_name' => 'The auth guard the permission applies to.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'sales_partners' => [
            'label' => 'Sales Partners',
            'description' => 'The sales partners/organisations that sell solar deals.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Sales partner name.',
                'image' => 'Logo/photo.',
                'email' => 'Contact email.',
                'phone' => 'Contact phone.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'sub_contractors' => [
            'label' => 'Sub Contractors',
            'description' => 'The sub-contractor companies/people who carry out installation work.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Sub-contractor name.',
                'email' => 'Contact email.',
                'phone' => 'Contact phone.',
                'image' => 'Logo/photo.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'loan_terms' => [
            'label' => 'Loan Terms',
            'description' => 'Available loan term lengths (in years) per financing option.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'finance_option_id' => 'The financing option this term belongs to.',
                'year' => 'The loan term length in years.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'loan_aprs' => [
            'label' => 'Loan APRs',
            'description' => 'Interest rates (APR) and dealer fees per loan term / financing option.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'loan_term_id' => 'The loan term this APR belongs to.',
                'finance_option_id' => 'The financing option.',
                'apr' => 'The annual percentage rate.',
                'dealer_fee' => 'The dealer fee for this APR (restricted).',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'office_costs' => [
            'label' => 'Office Costs',
            'description' => 'Configured office overhead cost values used in profitability calculations. Restricted.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'cost' => 'The office cost amount.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'labor_costs' => [
            'label' => 'Labor Costs',
            'description' => 'Configured labor cost values used in cost/profitability calculations. Restricted.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'cost' => 'The labor cost amount.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'adder_types' => [
            'label' => 'Adder Types',
            'description' => 'Categories of adders (extra options/upgrades) that can be added to a deal.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Adder type name.',
                'tag' => 'Optional tag/label for grouping.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'adder_sub_types' => [
            'label' => 'Adder Sub Types',
            'description' => 'Sub-categories within an adder type.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'adder_type_id' => 'The parent adder type.',
                'name' => 'Adder sub-type name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'adder_units' => [
            'label' => 'Adder Units',
            'description' => 'Units of measure for adders (e.g. each, per foot).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Unit name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'adders' => [
            'label' => 'Adders',
            'description' => 'Priced adder definitions (an adder type + unit + price). The price is restricted.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'adder_type_id' => 'The adder type.',
                'adder_unit_id' => 'The unit of measure.',
                'price' => 'The price of the adder (restricted).',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'customer_adders' => [
            'label' => 'Customer Adders',
            'description' => 'The specific adders selected for a customer\'s deal, with the agreed amount.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'customer_id' => 'The customer.',
                'adder_type_id' => 'The adder type chosen.',
                'adder_sub_type_id' => 'The adder sub-type chosen.',
                'adder_unit_id' => 'The unit of measure.',
                'amount' => 'The amount/price for this adder on the deal (restricted).',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'battery_types' => [
            'label' => 'Battery Types',
            'description' => 'Catalogue of battery storage product types.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Battery type name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'inverter_types' => [
            'label' => 'Inverter Types',
            'description' => 'Catalogue of inverter product types.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Inverter type name.',
                'tags' => 'Tags/labels for the inverter type.',
                'inverter_efficiency_rating' => 'Inverter efficiency rating percentage.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'inverter_type_rates' => [
            'label' => 'Inverter Type Rates',
            'description' => 'Cost rates per inverter type (base and internal costs). Restricted to Finance/Admin.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'inverter_type_id' => 'The inverter type.',
                'base_cost' => 'Base cost (restricted).',
                'internal_base_cost' => 'Internal base cost (restricted).',
                'internal_labor_cost' => 'Internal labor cost (restricted).',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'module_types' => [
            'label' => 'Module Types',
            'description' => 'Catalogue of solar module/panel types, their production value and cost (cost restricted).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'inverter_type_id' => 'The inverter type this module pairs with.',
                'name' => 'Module type name.',
                'value' => 'The production value/wattage rating of the module.',
                'amount' => 'The module amount/cost (restricted).',
                'internal_module_cost' => 'Internal module cost (restricted).',
                'ptc_rating' => 'Module PTC rating.',
                'voc_rating' => 'Module VOC rating.',
                'isc_rating' => 'Module ISC rating.',
                'weight' => 'Module weight.',
                'square_footage' => 'Module square footage.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'utility_companies' => [
            'label' => 'Utility Companies',
            'description' => 'The electric utility companies that serve customer properties.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Utility company name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'tools' => [
            'label' => 'Tools',
            'description' => 'Resources/tool files shared per department.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'department_id' => 'The department the tool belongs to.',
                'name' => 'Tool name.',
                'description' => 'Tool description.',
                'file' => 'Attached file.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'calls' => [
            'label' => 'Calls',
            'description' => 'Named call types used to organise call scripts.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Call name/type.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'call_scripts' => [
            'label' => 'Call Scripts',
            'description' => 'Scripted talking points for a call type, per department.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'call_id' => 'The call type this script is for.',
                'department_id' => 'The department.',
                'script' => 'The script text.',
                'extra_filter' => 'An optional extra filter/condition for when to use the script.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'email_types' => [
            'label' => 'Email Types',
            'description' => 'Categories of emails used to organise email scripts/templates.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Email type name.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'email_scripts' => [
            'label' => 'Email Scripts',
            'description' => 'Email templates/scripts per email type and department.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'email_type_id' => 'The email type.',
                'department_id' => 'The department.',
                'script' => 'The email template body.',
                'extra_filter' => 'Optional extra filter/condition for when to use the template.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
        ],

        'emails' => [
            'label' => 'Emails',
            'description' => 'Emails exchanged on projects/customers. Contains customer correspondence — Admin only.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'project_id' => 'The related project.',
                'department_id' => 'The department.',
                'customer_id' => 'The related customer.',
                'subject' => 'Email subject.',
                'body' => 'Email body content.',
                'user_id' => 'The user associated with the email.',
                'received_date' => 'When the email was received.',
                'is_view' => 'Whether the email has been viewed.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
                'deleted_at' => 'When soft-deleted (NULL means active).',
            ],
            'value_maps' => [
                'is_view' => ['1' => 'Viewed', '0' => 'Unread'],
            ],
        ],

        'email_attachments' => [
            'label' => 'Email Attachments',
            'description' => 'File attachments on emails. Admin only.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'email_id' => 'The email the attachment belongs to.',
                'file' => 'The attached file.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'new_tickets' => [
            'label' => 'Website Leads (New Tickets)',
            'description' => 'Contact/lead submissions from the public website. Admin only (contains lead PII).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Lead name.',
                'email' => 'Lead email.',
                'address' => 'Lead address.',
                'phone' => 'Lead phone.',
                'message' => 'The message the lead submitted.',
                'status' => 'Whether the lead has been handled.',
                'created_at' => 'When submitted.',
                'updated_at' => 'When last updated.',
            ],
            'value_maps' => [
                'status' => ['Pending' => 'Not yet handled', 'Done' => 'Handled'],
            ],
        ],

        'saved_reports' => [
            'label' => 'Saved Reports',
            'description' => 'Custom report definitions saved by users in the report builder. Admin only.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'name' => 'Report name.',
                'report_type' => 'The type of report.',
                'user_id' => 'The user who saved the report.',
                'created_at' => 'When created.',
                'updated_at' => 'When last updated.',
            ],
        ],

        'deploy_logs' => [
            'label' => 'Deploy Logs',
            'description' => 'Records of application deploy/rollback actions. Admin only (DevOps).',
            'columns' => [
                'id' => 'Internal unique ID.',
                'action' => 'The deploy action performed.',
                'run_by' => 'Who ran the action.',
                'status' => 'Outcome of the action.',
                'created_at' => 'When it ran.',
            ],
        ],

        'activity_log' => [
            'label' => 'Activity Log',
            'description' => 'Audit trail of project activity — department/lane moves, changes and approvals — recording who did what and when.',
            'columns' => [
                'id' => 'Internal unique ID.',
                'log_name' => 'The log category.',
                'description' => 'Human-readable description of the activity.',
                'subject_type' => 'The model type the activity is about.',
                'subject_id' => 'The ID of the subject (usually the project ID).',
                'event' => 'The event type (e.g. "move" for a lane/department change).',
                'causer_id' => 'The user who performed the action.',
                'properties' => 'JSON details of the change (e.g. old_lane and new_lane for a move).',
                'created_at' => 'When the activity happened.',
                'updated_at' => 'When the log was last updated.',
            ],
        ],

        'model_has_roles' => [
            'label' => 'User Roles (assignment)',
            'description' => 'Links users to their assigned security roles.',
            'columns' => [
                'role_id' => 'The role assigned.',
                'model_type' => 'The model type the role is assigned to (usually User).',
                'model_id' => 'The user ID the role is assigned to.',
            ],
        ],

        'model_has_permissions' => [
            'label' => 'User Permissions (assignment)',
            'description' => 'Links users directly to individual permissions. Admin only.',
            'columns' => [
                'permission_id' => 'The permission assigned.',
                'model_type' => 'The model type (usually User).',
                'model_id' => 'The user ID.',
            ],
        ],

        'role_has_permissions' => [
            'label' => 'Role Permissions (assignment)',
            'description' => 'Links roles to the permissions they grant. Admin only.',
            'columns' => [
                'permission_id' => 'The permission granted.',
                'role_id' => 'The role it is granted to.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Glossary — solar industry abbreviations and CRM concepts
    |--------------------------------------------------------------------------
    */
    'glossary' => [
        'NTP' => 'Notice To Proceed — authorization to begin solar installation work.',
        'PTO' => 'Permission To Operate — utility approval to turn the solar system on.',
        'HOA' => 'Homeowners Association — may need to approve installation in some neighborhoods.',
        'AHJ' => 'Authority Having Jurisdiction — local government body that approves permits.',
        'MPU' => 'Main Panel Upgrade — electrical panel upgrade sometimes required before solar.',
        'COC' => 'Certificate of Completion — final document packet mailed to the customer after PTO.',
        'ADU' => 'Accessory Dwelling Unit — a secondary home unit on the same property.',
        'APN' => 'Assessor\'s Parcel Number — the parcel identifier for the property.',
        'MSP' => 'Main Service Panel — the home\'s main electrical panel.',
        'Ghost project' => 'A project in the early Pre-Inspection Lane, waiting to enter the active workflow.',
        'Pre-Inspection Lane' => 'The early stage where projects wait before entering the active workflow.',
        'Lane' => 'A department/sub-department stage in the project workflow.',
        'Redline' => 'The baseline cost figure used as a reference for a deal.',
        'Adder' => 'An extra option or upgrade added to a deal, with its own price.',
        'Holdback' => 'Money withheld from a payout until certain milestones are met.',
        'Dealer fee' => 'The fee a financier charges the dealer for offering financing.',
    ],
];
