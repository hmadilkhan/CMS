{{-- One project on the Zones board.

     The card is the projects page card - same header, same info rows, same
     progress bar - with the two things only the board needs: how long the
     project has sat in this zone, and the Move button. Everything is written
     null-safe because a zone can hold a project that has no active task, no
     finance option or no sales partner yet; the projects page never sees those. --}}
@php
    $acceptanceStatus = 'Not Initiated';
    $acceptanceBadge = 'badge-not-initiated';

    if (! empty($project->projectAcceptance)) {
        if ($project->projectAcceptance->status == 0) {
            $acceptanceStatus = 'Pending';
            $acceptanceBadge = 'bg-warning';
        } elseif ($project->projectAcceptance->status == 1) {
            $acceptanceStatus = 'Approved';
            $acceptanceBadge = 'bg-success';
        } elseif ($project->projectAcceptance->status == 2) {
            $acceptanceStatus = 'Rejected';
            $acceptanceBadge = 'bg-secondary';
        }
    }

    $salesPartner = optional(optional($project->customer)->salespartner);
    $partnerImage = $salesPartner->image
        ? asset('storage/salespartners/' . $salesPartner->image)
        : asset('assets/images/profile_av.png');

    $soldDate = optional($project->customer)->sold_date;
    $ageInDays = $soldDate
        ? (int) \Carbon\Carbon::parse($project->pto_approval_date ?: now())->diffInDays(\Carbon\Carbon::parse($soldDate))
        : null;

    $activeTask = optional($project->assignedPerson)->first();
    $financeName = optional(optional(optional($project->customer)->finances)->finance)->name;
    $assignedTo = optional(optional($activeTask)->employee)->name;

    $daysInZone = $project->zone_entered_at
        ? (int) $project->zone_entered_at->startOfDay()->diffInDays(now()->startOfDay())
        : null;

    $progress = min(100, round((((int) $project->department_id) / 8) * 100));
@endphp

<div class="zone-card-wrap">
    <a href="{{ url('projects/' . $project->id) }}" class="project-link">
        <div class="card project-card border-0">
            <div class="project-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1 min-w-0">
                        <img src="{{ $partnerImage }}" alt=""
                            class="rounded-circle flex-shrink-0"
                            style="width: 34px; height: 34px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);"
                            onerror="this.onerror=null;this.src='{{ asset('assets/images/profile_av.png') }}';">
                        <div class="ms-2 min-w-0">
                            <h5 class="mb-0 fw-bold text-white text-truncate zone-card-title">
                                {{ str_replace('-', ' ', $project->project_name) }}</h5>
                            {{-- Code moved up here: it costs no row of its own. --}}
                            <span class="zone-card-code">{{ $project->code ?: '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-2 flex-shrink-0">
                        @if ($ageInDays !== null)
                            <span class="days-badge">{{ $ageInDays }}<small>d</small></span>
                        @endif
                        @if ($project->viewed_emails_count)
                            <i class="icofont-email text-white blink fs-5 ms-2"></i>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-wrap: break-word; word-wrap: break-word;">
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-briefcase me-2"></i>Department</span>
                    <span class="info-value text-truncate ms-2">{{ optional($project->department)->name ?: '-' }}</span>
                </div>
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-ui-user me-2"></i>Finance</span>
                    <span class="info-value text-success text-truncate ms-2">{{ $financeName ?: '-' }}</span>
                </div>
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-sand-clock me-2"></i>Status</span>
                    <span class="badge bg-danger text-truncate ms-2">{{ optional($activeTask)->status ?: 'No task' }}</span>
                </div>
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-group-students me-2"></i>Assigned To</span>
                    <span class="info-value text-truncate ms-2">{{ $assignedTo ?: '-' }}</span>
                </div>
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-check-circled me-2"></i>Acceptance</span>
                    <span class="badge {{ $acceptanceBadge }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                </div>
                <div class="info-row d-flex justify-content-between align-items-center">
                    <span class="info-label"><i class="icofont-clock-time me-2"></i>In this zone</span>
                    <span class="info-value text-truncate ms-2">
                        @if ($daysInZone === null)
                            -
                        @elseif ($daysInZone === 0)
                            Today
                        @else
                            {{ $daysInZone }} {{ $daysInZone === 1 ? 'day' : 'days' }}
                        @endif
                    </span>
                </div>

                <div class="zone-card-progress">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="info-label">Progress</span>
                        <span class="fw-bold" style="color: #2c3e50;">{{ $progress }}%</span>
                    </div>
                    <div class="progress progress-modern">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;"
                            aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                {{-- One line only: a long note would push the next card out of
                     sight, which is the whole thing this card is kept short for. --}}
                <div class="notes-section text-truncate"
                    title="{{ optional($project->notes)->assign_to_notes ?: 'Currently no notes attached' }}">
                    <i class="icofont-ui-note me-2"></i>{{ optional($project->notes)->assign_to_notes ?: 'Currently no notes attached' }}
                </div>
            </div>
        </div>
    </a>

    {{-- Outside the link, so moving a project never opens it. --}}
    <div class="zone-card-action">
        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-zone-move
            data-project-id="{{ $project->id }}"
            data-project-name="{{ $project->project_name }}"
            data-zone-id="{{ $project->zone_id }}">
            <i class="icofont-exchange me-1"></i>Move Zone
        </button>
    </div>
</div>
