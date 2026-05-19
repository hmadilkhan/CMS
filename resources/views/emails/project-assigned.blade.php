<x-mail::message>
<div style="text-align: center; margin-bottom: 24px;">
    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" width="90" height="90" style="object-fit: contain;">
</div>

# Hello {{ $userName }},

You have been assigned a project in the CRM.

**Project:** {{ $projectName }}

@if ($customerName !== '')
**Customer:** {{ $customerName }}
@endif

**Department:** {{ $departmentName }}

**Assigned by:** {{ $assignedBy }}

@if (!empty($notes))
**Notes:** {{ $notes }}
@endif

<x-mail::button :url="$projectUrl">
View Project
</x-mail::button>

Please review the project and take the necessary next steps.

Best Regards,<br>
{{ $companyName }}
</x-mail::message>
