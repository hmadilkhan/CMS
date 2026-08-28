{{-- The Zones section, inside Project Activity and built from the same pieces as
     the department section above it: the same tab bar, the same notes/files
     grid, the same panel under the notes column.

     EVERY zone gets a tab, not only the ones the project has been through - the
     others simply open empty and can be filled in ahead of time. Notes and files
     belong to the project + that one zone. The tab the page opens on is the zone
     the project is in right now. --}}
@php
    $currentZoneId = $project->zone_id;
    $activeZone = $zoneTabs->firstWhere('id', $currentZoneId) ?? $zoneTabs->first();
    $activeZoneId = optional($activeZone)->id;
    $zoneDays = $project->zone_entered_at
        ? (int) $project->zone_entered_at->startOfDay()->diffInDays(now()->startOfDay())
        : null;
@endphp

<div class="col-md-12 mt-5">
    <div class="department-detail-heading">
        <span><i class="icofont-layers me-2"></i>Zones</span>
    </div>

    {{-- One bar, aligned with the tab strip below it: where the project stands
         on the left, the only action on the right. --}}
    <div class="zone-status-bar">
        <div class="zone-status-info">
            <span class="zone-status-label">Current Zone</span>
            <span class="zone-status-name">{{ optional($project->zone)->name ?? 'No zone' }}</span>
            <span class="zone-status-meta">
                @if ($zoneDays === null)
                    No move recorded
                @elseif ($zoneDays === 0)
                    Moved here today
                @else
                    {{ $zoneDays }} {{ $zoneDays === 1 ? 'day' : 'days' }} in this zone
                @endif
            </span>
        </div>
        <button type="button" class="btn btn-primary btn-sm zone-status-action" data-zone-move
            data-project-id="{{ $project->id }}"
            data-project-name="{{ $project->project_name }}"
            data-zone-id="{{ $project->zone_id }}">
            <i class="icofont-exchange me-1"></i>Move Zone
        </button>
    </div>

    <ul class="nav nav-tabs project-department-tabs tab-body-header rounded justify-content-center mb-4"
        id="zoneDetailTabs" role="tablist">
        @foreach ($zoneTabs as $zone)
            @php $isCurrentZone = $zone->id == $activeZoneId; @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isCurrentZone ? 'active' : '' }}"
                    id="zone-detail-tab-{{ $zone->id }}"
                    data-bs-toggle="tab"
                    data-bs-target="#zone-detail-{{ $zone->id }}"
                    type="button"
                    role="tab"
                    aria-controls="zone-detail-{{ $zone->id }}"
                    aria-selected="{{ $isCurrentZone ? 'true' : 'false' }}">
                    <span class="department-detail-tab-title">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) . ' ' . $zone->name }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content" id="zoneDetailTabsContent">
        @foreach ($zoneTabs as $zone)
            @php $isCurrentZone = $zone->id == $activeZoneId; @endphp
            <div class="tab-pane fade {{ $isCurrentZone ? 'show active' : '' }}"
                id="zone-detail-{{ $zone->id }}"
                role="tabpanel"
                aria-labelledby="zone-detail-tab-{{ $zone->id }}">

                <div class="row clearfix sample-activity-grid">
                    <div class="col-lg-8 col-md-12 mb-3 sample-notes-column">
                        @livewire('project.notes-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $project->department_id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost, 'viewSource' => 'crm', 'zoneId' => $zone->id, 'projectZoneId' => $currentZoneId, 'sectionTitle' => $zone->name . ' Notes'], key('zone-notes-' . $zone->id))

                        {{-- The fields this zone owns, from config('zones.zone_fields').
                             Only NTP declares any today: the NTP Approval Date, which
                             left the Deal Review department fields. No zone move is
                             gated on it - Operations asks for it on the Permitting ->
                             Installation move. Editable in the project's current zone
                             only, like the notes and files beside it. --}}
                        @php $zoneFields = app(\App\Services\ZoneService::class)->fieldsFor($zone); @endphp
                        @if (!empty($zoneFields))
                            <div class="project-section-panel">
                                <div class="project-section-header">
                                    <i class="icofont-list me-2"></i>{{ $zone->name }} Fields
                                </div>
                                <div class="department-fields-frame">
                                    <form class="row zone-fields-form" data-zone-fields
                                        data-project-id="{{ $project->id }}" data-zone-id="{{ $zone->id }}">
                                        @foreach ($zoneFields as $column => $field)
                                            @php
                                                $fieldType = $field['type'] ?? 'text';
                                                $fieldValue = $project->{$column};
                                                $fieldValue = $fieldType === 'date' && !empty($fieldValue)
                                                    ? \Illuminate\Support\Carbon::parse($fieldValue)->format('Y-m-d')
                                                    : $fieldValue;
                                            @endphp
                                            <div class="col-sm-6 mb-3">
                                                <label class="form-label"
                                                    for="zone-field-{{ $zone->id }}-{{ $column }}">{{ $field['label'] }}</label>
                                                <input class="form-control" type="{{ $fieldType }}"
                                                    id="zone-field-{{ $zone->id }}-{{ $column }}"
                                                    name="{{ $column }}" value="{{ $fieldValue }}"
                                                    @disabled(!$isCurrentZone)>
                                            </div>
                                        @endforeach
                                        @if ($isCurrentZone)
                                            <div class="col-12 d-flex align-items-center gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="icofont-save me-1"></i>Save
                                                </button>
                                                <span class="zone-fields-feedback small"></span>
                                            </div>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="project-section-panel">
                            <div class="project-section-header">
                                <i class="icofont-history me-2"></i>Zone History
                            </div>
                            <div class="department-fields-frame">
                                @forelse ($zoneHistory as $movement)
                                    <div class="zone-history-item">
                                        <div>
                                            <strong>{{ optional($movement->fromZone)->name ?? 'Enrolled' }}</strong>
                                            <i class="icofont-long-arrow-right mx-1"></i>
                                            <strong>{{ optional($movement->toZone)->name }}</strong>
                                            @if ($movement->is_auto)
                                                <span class="badge bg-secondary ms-2">Automatic</span>
                                            @endif
                                        </div>
                                        @if ($movement->note)
                                            <div class="mt-1">{{ $movement->note }}</div>
                                        @endif
                                        <div class="zone-history-meta mt-1">
                                            <i class="icofont-user me-1"></i>{{ optional($movement->user)->name ?? 'System' }}
                                            <i class="icofont-clock-time ms-3 me-1"></i>{{ $movement->created_at->format('m/d/Y H:i') }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted fst-italic">No zone movements yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-12 mb-3 sample-files-column">
                        @livewire('project.enhanced-files-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $project->department_id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost, 'viewSource' => 'crm', 'zoneId' => $zone->id, 'projectZoneId' => $currentZoneId, 'sectionTitle' => $zone->name . ' Files', 'sectionIcon' => 'icofont-files-stack'], key('zone-files-' . $zone->id))
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
{{-- The move modal itself is included at the end of the page, outside the tab
     panes, so Bootstrap can stack it normally. --}}

<script>
    {{-- This whole section is server-rendered, so a move made from this page has
         to reload it: the status bar, which tab is editable and which fields the
         move just filled in all change at once. --}}
    document.addEventListener('zone:moved', function () {
        window.location.reload();
    });

    {{-- One delegated handler for every zone's field form: the panes are all in
         the DOM already, but the forms are Livewire's neighbours and a rerender
         next door must not cost us the binding. --}}
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-zone-fields]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('.zone-fields-feedback');
        const payload = {
            project_id: form.dataset.projectId,
            zone_id: form.dataset.zoneId
        };

        form.querySelectorAll('input[name]').forEach(function (input) {
            payload[input.name] = input.value;
        });

        if (button) {
            button.disabled = true;
        }

        if (feedback) {
            feedback.textContent = '';
            feedback.className = 'zone-fields-feedback small';
        }

        fetch('{{ route('zones.fields') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (button) {
                    button.disabled = false;
                }

                if (!feedback) {
                    return;
                }

                feedback.textContent = result.ok
                    ? (result.data.message || 'Saved.')
                    : (result.data.message || 'The fields could not be saved.');
                feedback.className = 'zone-fields-feedback small ' + (result.ok ? 'text-success' : 'text-danger');
            })
            .catch(function () {
                if (button) {
                    button.disabled = false;
                }

                if (feedback) {
                    feedback.textContent = 'The fields could not be saved. Please try again.';
                    feedback.className = 'zone-fields-feedback small text-danger';
                }
            });
    });
</script>
