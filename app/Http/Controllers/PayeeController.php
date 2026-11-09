<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReturnsToOperationsConsole;
use App\Models\Payee;
use App\Services\Operations\PayeesPanel;
use Illuminate\Http\Request;

/**
 * Operations -> Payees. Built-in payees cannot be changed here: the commission
 * and labor payables are keyed on them. A payee's key never changes, so
 * renaming one relabels every row that already uses it.
 */
class PayeeController extends Controller
{
    use ReturnsToOperationsConsole;

    public function index(Request $request)
    {
        return view('operations.payees.index', app(PayeesPanel::class)->data($request));
    }

    public function store(Request $request)
    {
        $label = $this->validatedLabel($request);

        if (Payee::labelTaken($label)) {
            return $this->backToOperations($request, 'payees.index')
                ->withInput()
                ->withErrors(['label' => 'A payee with this name already exists.']);
        }

        $payee = Payee::findOrCreateByLabel($label);
        $this->log($payee, 'created', ['label' => $payee->label]);

        return $this->backToOperations($request, 'payees.index')->with('success', "Payee \"{$payee->label}\" added.");
    }

    public function update(Request $request, Payee $payee)
    {
        if ($payee->is_system) {
            return $this->locked($request);
        }

        $label = $this->validatedLabel($request);

        if (Payee::labelTaken($label, $payee->id)) {
            return $this->backToOperations($request, 'payees.index', ['id' => $payee->id])
                ->withInput()
                ->withErrors(['label' => 'A payee with this name already exists.']);
        }

        $old = $payee->label;
        $payee->update(['label' => Payee::normaliseLabel($label)]);
        $this->log($payee, 'renamed', ['from' => $old, 'to' => $payee->label]);

        return $this->backToOperations($request, 'payees.index')->with('success', "Payee renamed to \"{$payee->label}\".");
    }

    public function toggle(Request $request, Payee $payee)
    {
        if ($payee->is_system) {
            return $this->locked($request);
        }

        $payee->update(['is_active' => ! $payee->is_active]);
        $this->log($payee, $payee->is_active ? 'switched on' : 'switched off', ['is_active' => $payee->is_active]);

        return $this->backToOperations($request, 'payees.index')
            ->with('success', "Payee \"{$payee->label}\" ".($payee->is_active ? 'switched on.' : 'switched off. It is no longer offered, but rows that use it keep their label.'));
    }

    public function destroy(Request $request, Payee $payee)
    {
        if ($payee->is_system) {
            return $this->locked($request);
        }

        if ($payee->isInUse()) {
            return $this->backToOperations($request, 'payees.index')
                ->with('error', "\"{$payee->label}\" is used by account transactions or milestones, so it cannot be deleted. Switch it off instead.");
        }

        $label = $payee->label;
        $this->log($payee, 'deleted', ['key' => $payee->key, 'label' => $label]);
        $payee->delete();

        return $this->backToOperations($request, 'payees.index')->with('success', "Payee \"{$label}\" deleted.");
    }

    private function validatedLabel(Request $request): string
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100', 'regex:/[\pL\pN]/u'],
        ], [
            'label.regex' => 'The payee name needs at least one letter or number.',
        ]);

        return Payee::normaliseLabel($validated['label']);
    }

    private function locked(Request $request)
    {
        return $this->backToOperations($request, 'payees.index')
            ->with('error', 'Built-in payees cannot be changed.');
    }

    private function log(Payee $payee, string $event, array $properties): void
    {
        $user = auth()->user();

        activity('Payee')
            ->performedOn($payee)
            ->causedBy($user)
            ->withProperties($properties)
            ->log(($user?->name ?? 'System')." {$event} the payee \"{$payee->label}\".");
    }
}
