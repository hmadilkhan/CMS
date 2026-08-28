<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Project;
use App\Models\Zone;
use App\Services\ZoneService;
use Illuminate\Http\Request;

/**
 * The Zones board - the funding side's view of the pipeline.
 *
 * It lives inside the projects page as the "Zones" tab and is fetched into it,
 * so this controller returns a fragment, not a page. It reads projects and
 * writes nothing but zone rows.
 */
class ZoneController extends Controller
{
    public function __construct(private ZoneService $zones) {}

    /**
     * The projects page owns the board now; the old standalone URL keeps
     * working by handing the user to that tab.
     */
    public function index()
    {
        return redirect()->route('projects.index', ['tab' => 'zones']);
    }

    /**
     * The board itself, as a fragment for the projects page's Zones tab.
     *
     * `archived=1` swaps the open lanes for the archive, which is otherwise kept
     * off the board entirely.
     */
    public function board(Request $request)
    {
        $showArchive = $request->boolean('archived');
        $archive = $this->zones->archiveZone();

        $lanes = $showArchive && $archive
            ? collect([$archive])
            : $this->zones->boardZones();

        return view('zones.partials.board', [
            'lanes' => $lanes,
            'projectsByZone' => $this->projectsFor($lanes->pluck('id')->all(), $request),
            'departments' => Department::orderBy('id')->get(),
            'departmentFilter' => $request->input('department', 'all'),
            'search' => (string) $request->input('search', ''),
            'archivedCount' => $this->archivedCount(),
            'movableZones' => $this->zones->movableZones(),
            'isArchiveView' => $showArchive,
        ]);
    }

    /**
     * Move one project to another zone. The only write this board makes.
     *
     * No zone move is gated on a project field. The NTP Approval Date is asked
     * for on the department side instead, on Permitting -> Installation
     * (`ProjectController::ntpApprovalGate`), where it is followed by the MPU
     * chase - see docs/follow-ups.md.
     */
    public function move(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'zone_id' => 'required|integer|exists:zones,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        $moved = $this->zones->move(
            $project,
            (int) $validated['zone_id'],
            $validated['note'] ?? null
        );

        if (! $moved) {
            return response()->json([
                'status' => 422,
                'message' => 'This project is already in that zone.',
            ], 422);
        }

        $zone = Zone::find($validated['zone_id']);

        return response()->json([
            'status' => 200,
            'message' => 'Project moved to '.$zone->name.'.',
        ]);
    }

    /**
     * Save the fields a zone's own tab collects on the project.
     *
     * Only the zone the project is actually in may be written - every other
     * zone tab is read-only, exactly as its notes and files are - and only the
     * columns that zone declares in `config('zones.zone_fields')`, so this
     * endpoint can never be talked into writing an arbitrary project column.
     */
    public function fields(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'zone_id' => 'required|integer|exists:zones,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $zone = Zone::findOrFail($validated['zone_id']);

        if ((int) $project->zone_id !== (int) $zone->id) {
            return response()->json([
                'status' => 422,
                'message' => 'Only the zone the project is in can be edited.',
            ], 422);
        }

        $fields = $this->zones->fieldsFor($zone);

        if (empty($fields)) {
            return response()->json([
                'status' => 422,
                'message' => 'This zone has no fields to fill in.',
            ], 422);
        }

        $updates = [];

        foreach ($fields as $column => $field) {
            if (! $request->has($column)) {
                continue;
            }

            $value = $this->normalisedFieldValue($request->input($column), $field);

            if ($value === null && $request->filled($column)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Please enter a valid '.$field['label'].'.',
                    'field' => $column,
                ], 422);
            }

            $updates[$column] = $value;
        }

        if (empty($updates)) {
            return response()->json([
                'status' => 422,
                'message' => 'Nothing to save.',
            ], 422);
        }

        $project->forceFill($updates)->save();

        foreach ($updates as $column => $value) {
            $this->logFieldChange($project, $fields[$column]['label'], $value, 'from the '.$zone->name.' zone');
        }

        return response()->json([
            'status' => 200,
            'message' => $zone->name.' fields saved.',
        ]);
    }

    /**
     * A submitted zone field as it should be stored: NULL when it was left
     * empty (the tab stores that as "cleared") and NULL when a date cannot be
     * read at all, which the caller turns into a 422.
     *
     * @param  array{label?: string, type?: string}  $field
     */
    private function normalisedFieldValue($value, array $field): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            return null;
        }

        if (($field['type'] ?? 'text') !== 'date') {
            return (string) $value;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /** One activity line per zone field written, named the way the user sees it. */
    private function logFieldChange(Project $project, string $label, ?string $value, string $context): void
    {
        $actor = optional(auth()->user())->name ?? 'A user';

        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->setEvent('updated')
            ->withProperties([$label => $value])
            ->log($value === null
                ? "{$actor} cleared the {$label} {$context}."
                : "{$actor} set the {$label} to {$value} {$context}.");
    }

    /**
     * Projects grouped by zone, carrying everything the card draws. The search
     * and the department filter are applied here so a lane's count always
     * matches the cards under it.
     */
    private function projectsFor(array $zoneIds, Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $department = $request->input('department', 'all');

        return Project::with([
            'customer:id,first_name,last_name,sales_partner_id,sold_date',
            'customer.salespartner:id,name,image',
            'customer.finances:id,customer_id,finance_option_id',
            'customer.finances.finance:id,name',
            'department:id,name',
            'zone:id,name,slug',
            'assignedPerson.employee:id,name',
            'notes',
            'projectAcceptance',
        ])
            ->withCount(['emails as viewed_emails_count' => function ($query) {
                $query->where('is_view', 1);
            }])
            ->whereIn('zone_id', $zoneIds)
            ->when($department !== 'all' && $department !== null && $department !== '', function ($query) use ($department) {
                $query->where('department_id', $department);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('project_name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderByDesc('zone_entered_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('zone_id');
    }

    private function archivedCount(): int
    {
        $archive = $this->zones->archiveZone();

        return $archive ? Project::where('zone_id', $archive->id)->count() : 0;
    }
}
