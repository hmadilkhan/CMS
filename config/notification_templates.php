<?php

/*
|--------------------------------------------------------------------------
| System Email Templates
|--------------------------------------------------------------------------
|
| Every automatic email the CRM sends is registered here with its default
| subject/body and the placeholders it can fill. Admins edit these on
| Operations > Email Scripts; an edited copy is stored in the
| `notification_templates` table and overrides the default below.
|
| Placeholders use single curly braces: {customer_first_name}. Only the
| placeholders listed for a template are available to it — the code that
| sends that email supplies exactly those values. An unknown placeholder is
| rejected when saving, so a typo can never ship an email reading
| "Hello {frist_name}".
|
| Bodies are HTML (the editor produces HTML) and are wrapped in the branded
| mail layout, so no header, footer or signature is needed here.
|
*/

return [

    'project_assigned' => [
        'name' => 'Project Assigned',
        'group' => 'Projects',
        'description' => 'Sent to an employee when a project is assigned to them in a department.',
        'placeholders' => [
            'recipient_name' => 'Name of the employee receiving the email',
            'project_name' => 'Project name',
            'project_code' => 'Project code, e.g. 1001',
            'customer_name' => 'Customer full name',
            'department_name' => 'Department the project moved into',
            'assigned_by' => 'Name of the user who assigned it',
            'notes' => 'Assignment notes (blank when none were entered)',
            'project_url' => 'Link to the project page',
        ],
        'subject' => 'Project Assigned: {project_name}',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>You have been assigned a project in the CRM.</p>'
            .'<p><strong>Project:</strong> {project_name}<br>'
            .'<strong>Customer:</strong> {customer_name}<br>'
            .'<strong>Department:</strong> {department_name}<br>'
            .'<strong>Assigned by:</strong> {assigned_by}<br>'
            .'<strong>Notes:</strong> {notes}</p>'
            .'<p><a href="{project_url}">View Project</a></p>'
            .'<p>Please review the project and take the necessary next steps.</p>',
    ],

    'project_date_updated' => [
        'name' => 'Project Milestone Date Updated',
        'group' => 'Projects',
        'description' => 'Sent to the sales person when the Permit, Installation, Inspection or PTO date is entered or changed. {milestone_details} expands to one headline and sentence per changed date.',
        'placeholders' => [
            'recipient_name' => 'Name of the sales person receiving the email',
            'project_name' => 'Project name',
            'project_code' => 'Project code, e.g. 1001',
            'customer_name' => 'Customer full name',
            'milestone_headline' => 'Short headline of the first change, e.g. "PTO Approved"',
            'milestone_details' => 'The changed dates, each as a headline and a sentence',
            'updated_by' => 'Name of the user who changed the date',
            'updated_at' => 'Date the change was made',
            'project_url' => 'Link to the project page',
        ],
        'subject' => '{milestone_headline} - {project_name} [{project_code}]',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>Here are the latest updates on your project <strong>{project_name}</strong> ({project_code}).</p>'
            .'{milestone_details}'
            .'<p><strong>Customer:</strong> {customer_name}<br>'
            .'<strong>Updated by:</strong> {updated_by} on {updated_at}</p>'
            .'<p><a href="{project_url}">View Project</a></p>'
            .'<p>If anything here looks incorrect, please reply to this email or reach out to the project team.</p>',
    ],

    'note_mentioned' => [
        'name' => 'Mentioned in a Project Note',
        'group' => 'Projects',
        'description' => 'Sent to an employee tagged with @ in a project note.',
        'placeholders' => [
            'recipient_name' => 'Name of the employee receiving the email',
            'mentioned_by' => 'Name of the user who wrote the note',
            'project_name' => 'Project name',
            'note' => 'The note text',
            'project_url' => 'Link to the project page',
        ],
        'subject' => 'You were mentioned in a project note',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>{mentioned_by} mentioned you in a note on project: {project_name}</p>'
            .'<p><strong>Note:</strong> {note}</p>'
            .'<p><a href="{project_url}">View Project</a></p>'
            .'<p>Thank you for using our application!</p>',
    ],

    'email_received' => [
        'name' => 'New Email Received for a Project',
        'group' => 'Projects',
        'description' => 'Sent to the assigned employee when a customer replies to a project email.',
        'placeholders' => [
            'recipient_name' => 'Name of the employee receiving the email',
            'project_name' => 'Project name',
            'email_subject' => 'Subject of the received email',
            'sender' => 'Who the email came from',
            'project_url' => 'Link to the project page',
        ],
        'subject' => 'New Email Received for Project: {project_name}',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>A new email was received for the project: {project_name}</p>'
            .'<p><strong>Subject:</strong> {email_subject}<br>'
            .'<strong>From:</strong> {sender}</p>'
            .'<p><a href="{project_url}">View Project</a></p>'
            .'<p>Thank you for using our application!</p>',
    ],

    'service_ticket_created' => [
        'name' => 'Service Ticket Assigned',
        'group' => 'Service Tickets',
        'description' => 'Sent to the assigned user when a new service ticket is created.',
        'placeholders' => [
            'recipient_name' => 'Name of the user receiving the email',
            'ticket_id' => 'Ticket number',
            'ticket_subject' => 'Ticket subject',
            'priority' => 'Ticket priority',
            'project_name' => 'Project the ticket belongs to',
            'notes' => 'Ticket notes',
            'ticket_url' => 'Link to the service dashboard',
        ],
        'subject' => 'New Service Ticket Assigned - #{ticket_id}',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>A new service ticket has been assigned to you.</p>'
            .'<p><strong>Subject:</strong> {ticket_subject}<br>'
            .'<strong>Priority:</strong> {priority}<br>'
            .'<strong>Project:</strong> {project_name}<br>'
            .'<strong>Notes:</strong> {notes}</p>'
            .'<p><a href="{ticket_url}">View Ticket</a></p>'
            .'<p>Please review and take necessary action.</p>',
    ],

    'service_ticket_resolved' => [
        'name' => 'Service Ticket Resolved',
        'group' => 'Service Tickets',
        'description' => 'Sent to the person who raised the ticket once it is resolved.',
        'placeholders' => [
            'recipient_name' => 'Name of the user receiving the email',
            'ticket_id' => 'Ticket number',
            'ticket_subject' => 'Ticket subject',
            'project_name' => 'Project the ticket belongs to',
            'priority' => 'Ticket priority',
            'resolved_by' => 'Name of the user who resolved it',
            'ticket_url' => 'Link to the service dashboard',
        ],
        'subject' => 'Ticket Resolved - #{ticket_id}',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>Great news! Your service ticket has been resolved.</p>'
            .'<p><strong>Ticket Subject:</strong> {ticket_subject}<br>'
            .'<strong>Project:</strong> {project_name}<br>'
            .'<strong>Priority:</strong> {priority}<br>'
            .'<strong>Resolved by:</strong> {resolved_by}</p>'
            .'<p><a href="{ticket_url}">View Ticket</a></p>'
            .'<p>Thank you for using our service!</p>',
    ],

    'service_ticket_comment' => [
        'name' => 'New Comment on a Service Ticket',
        'group' => 'Service Tickets',
        'description' => 'Sent to the person who raised the ticket when someone comments on it.',
        'placeholders' => [
            'recipient_name' => 'Name of the user receiving the email',
            'ticket_id' => 'Ticket number',
            'ticket_subject' => 'Ticket subject',
            'comment_by' => 'Name of the user who commented',
            'comment' => 'The comment text',
            'ticket_url' => 'Link to the service dashboard',
        ],
        'subject' => 'New Comment on Ticket #{ticket_id}',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>A new comment has been added to your ticket.</p>'
            .'<p><strong>Ticket Subject:</strong> {ticket_subject}<br>'
            .'<strong>Comment by:</strong> {comment_by}<br>'
            .'<strong>Comment:</strong> {comment}</p>'
            .'<p><a href="{ticket_url}">View Ticket</a></p>'
            .'<p>Thank you for using our service!</p>',
    ],

    'site_survey_scheduled' => [
        'name' => 'Site Survey Scheduled',
        'group' => 'Site Surveys',
        'description' => 'Sent to the technician when a site survey is booked for them.',
        'placeholders' => [
            'recipient_name' => 'Name of the technician receiving the email',
            'survey_date' => 'Survey date',
            'start_time' => 'Start time',
            'end_time' => 'End time',
            'customer_address' => 'Address of the survey',
            'survey_url' => 'Link to the survey',
        ],
        'subject' => 'New Site Survey Scheduled',
        'body' => '<p>Hello {recipient_name},</p>'
            .'<p>A new site survey has been scheduled for you.</p>'
            .'<p><strong>Date:</strong> {survey_date}<br>'
            .'<strong>Time:</strong> {start_time} - {end_time}<br>'
            .'<strong>Address:</strong> {customer_address}</p>'
            .'<p><a href="{survey_url}">View Details</a></p>',
    ],

    'acceptance_review_sent' => [
        'name' => 'Acceptance Review Sent',
        'group' => 'Acceptance',
        'description' => 'Sent to the sales person when a Project Acceptance Review is submitted for their approval.',
        'placeholders' => [
            'recipient_name' => 'Name of the sales person receiving the email',
            'customer_name' => 'Customer full name',
            'project_name' => 'Project name',
            'project_url' => 'Link to the project page',
            'support_email' => 'Support address shown in the closing line',
        ],
        'subject' => 'Project Acceptance Review - {customer_name}',
        'body' => '<p>Hi {recipient_name}</p>'
            .'<p>The Project Acceptance Review for the project {customer_name} is ready to be approved.</p>'
            .'<p>Please login to the CRM and navigate to the &ldquo;Acceptance&rdquo; tab within the project to approve or dispute the commission amount.</p>'
            .'<p>Project URL: <a href="{project_url}">{project_url}</a></p>'
            .'<p>We look forward to getting a reply within the next 24 hours, after which we will assume the commission as approved.</p>'
            .'<p>If you have any questions, please reach out to us at {support_email}</p>'
            .'<p>Thank you for your continued support!</p>'
            .'<p>The Solen Energy Construction Engineering Team</p>',
    ],

    'acceptance_review_status' => [
        'name' => 'Acceptance Review Approved / Rejected',
        'group' => 'Acceptance',
        'description' => 'Sent to the engineering team once the sales person approves or rejects an Acceptance Review. {status} becomes "approved" or "rejected".',
        'placeholders' => [
            'recipient_name' => 'Name of the assigned employee',
            'customer_name' => 'Customer full name',
            'project_name' => 'Project name',
            'status' => 'Either "approved" or "rejected"',
            'project_url' => 'Link to the project page',
        ],
        'subject' => 'Project Acceptance Review Status - {customer_name}',
        'body' => '<p>Hi {recipient_name}</p>'
            .'<p>The Project Acceptance Review for {customer_name} has been {status}</p>'
            .'<p>Project URL: <a href="{project_url}">{project_url}</a></p>'
            .'<p>Please take the necessary steps to continue moving the job forward.</p>'
            .'<p>Thank you!.</p>',
    ],
    'finance_milestone_triggered' => [
        'name' => 'Finance Milestone Triggered',
        'group' => 'Finance',
        'description' => 'Sent to the accounting recipients when a finance milestone payment becomes collectable.',
        'placeholders' => [
            'project_name' => 'Project name',
            'milestone_label' => 'Milestone that was triggered',
            'customer_name' => 'Customer full name',
            'amount' => 'Amount to collect, formatted, e.g. 1,250.00',
            'project_url' => 'Link to the project page',
        ],
        'subject' => 'Finance Milestone Triggered: {project_name} - {milestone_label}',
        'body' => '<p>Dear Accounting Team,</p>'
            .'<p>Please collect ${amount} from <a href="{project_url}">{customer_name}</a>.</p>'
            .'<p>Thank you</p>',
    ],

];
