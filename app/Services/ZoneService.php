<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectZoneMovement;
use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Zones module's brain - see docs/zones.md.
 *
 * Zones are a funding-side pipeline that runs beside the department pipeline
 * and never writes to it. Only two things happen on their own:
 *
 *   1. a project reaching the entry department (Deal Review) with no zone yet
 *      is enrolled at Pre NTP;
 *   2. a project reaching the promotion department (Site Survey) *while still
 *      in Pre NTP* is promoted to NTP.
 *
 * Everything after that is the Funding Manager's manual move. Rule 2 is
 * deliberately one-directional: once a project has been moved on to M1/M2, a
 * department move can never pull its zone back.
 */
class ZoneService
{
    /** Zones in board order, memoised for the request. */
    private ?Collection $zones = null;

    /** @return Collection<int, Zone> keyed by slug */
    public function zones(): Collection
    {
        if ($this->zones === null) {
            $this->zones = Zone::ordered()->get()->keyBy('slug');
        }

        return $this->zones;
    }

    public function zoneBySlug(string $slug): ?Zone
    {
        return $this->zones()->get($slug);
    }

    /** The lanes the board draws. Archived is not one of them. */
    public function boardZones(): Collection
    {
        return $this->zones()->filter(fn (Zone $zone) => $zone->show_in_list)->values();
    }

    public function archiveZone(): ?Zone
    {
        return $this->zoneBySlug(config('zones.archived_zone', 'archived'));
    }

    /** Every lane a move dropdown may offer, archive included. */
    public function movableZones(): Collection
    {
        return $this->zones()->values();
    }

    /**
     * A project arrived in a department. Applies whichever automatic rule fits,
     * or nothing at all. Safe to call after every department move.
     */
    public function handleDepartmentArrival(Project $project, ?int $departmentId = null): void
    {
        $departmentId = $departmentId ?? $project->department_id;

        $entry = config('zones.entry');

        if ((int) $departmentId === (int) $entry['department_id'] && ! $project->zone_id) {
            $this->enroll($project);

            return;
        }

        $promotion = config('zones.promotion');

        if ((int) $departmentId !== (int) $promotion['department_id']) {
            return;
        }

        $from = $this->zoneBySlug($promotion['from_zone']);
        $to = $this->zoneBySlug($promotion['to_zone']);

        if (! $from || ! $to) {
            return;
        }

        // A project created straight into this department (the intake form can
        // do that) never passed the entry rule, so it enters here instead of
        // staying outside the module for good.
        if (! $project->zone_id) {
            $this->applyMove($project, $to, null, 'Enrolled in Zones.', true);

            return;
        }

        // Otherwise only while the project is still in the "from" zone: a
        // Funding Manager who has already pushed it on to M1/M2 outranks the
        // department pipeline.
        if ((int) $project->zone_id !== (int) $from->id) {
            return;
        }

        $this->applyMove($project, $to, null, 'Moved automatically on reaching '.$to->name.'.', true);
    }

    /** Pull a project into the module at its entry zone. */
    public function enroll(Project $project): void
    {
        if ($project->zone_id) {
            return;
        }

        $zone = $this->zoneBySlug(config('zones.entry.zone', 'pre_ntp'));

        if (! $zone) {
            return;
        }

        $this->applyMove($project, $zone, null, 'Enrolled in Zones.', true);
    }

    /**
     * The Funding Manager's own move. Returns false when the target zone does
     * not exist or the project is already sitting in it.
     */
    public function move(Project $project, int $zoneId, ?string $note = null, ?int $userId = null): bool
    {
        $zone = $this->zones()->firstWhere('id', $zoneId);

        if (! $zone || (int) $project->zone_id === (int) $zone->id) {
            return false;
        }

        $this->applyMove($project, $zone, $userId ?? auth()->id(), $note, false);

        return true;
    }

    /**
     * The project fields a zone's own tab collects, keyed by column name. Empty
     * for every zone that collects nothing - which is all of them but NTP.
     *
     * @return array<string, array{label: string, type: string}>
     */
    public function fieldsFor(Zone $zone): array
    {
        $fields = config('zones.zone_fields.'.$zone->slug, []);

        return is_array($fields) ? $fields : [];
    }

    /**
     * Write the move: the project's current zone, the clock the board's "days in
     * zone" reads, and the history row the zone tabs are built from.
     */
    private function applyMove(Project $project, Zone $zone, ?int $userId, ?string $note, bool $isAuto): void
    {
        $fromZoneId = $project->zone_id;

        DB::transaction(function () use ($project, $zone, $fromZoneId, $userId, $note, $isAuto) {
            $project->forceFill([
                'zone_id' => $zone->id,
                'zone_entered_at' => now(),
            ])->save();

            ProjectZoneMovement::create([
                'project_id' => $project->id,
                'from_zone_id' => $fromZoneId,
                'to_zone_id' => $zone->id,
                'user_id' => $userId,
                'note' => $note,
                'is_auto' => $isAuto,
            ]);
        });

        $actor = $userId ? (optional(auth()->user())->name ?? 'A user') : 'The system';

        activity('project')
            ->performedOn($project)
            ->causedBy($userId ? auth()->user() : null)
            ->setEvent('updated')
            ->withProperties([
                'from_zone_id' => $fromZoneId,
                'to_zone_id' => $zone->id,
                'note' => $note,
                'is_auto' => $isAuto,
            ])
            ->log("{$actor} moved the project to the {$zone->name} zone.");
    }

    /** A project's zone history, newest first. */
    public function history(Project $project): Collection
    {
        return ProjectZoneMovement::with('fromZone', 'toZone', 'user')
            ->where('project_id', $project->id)
            ->orderByDesc('id')
            ->get();
    }

    /** Whole days the project has been sitting in its current zone. */
    public function daysInZone(Project $project): int
    {
        if (! $project->zone_entered_at) {
            return 0;
        }

        return (int) $project->zone_entered_at->startOfDay()->diffInDays(now()->startOfDay());
    }
}
