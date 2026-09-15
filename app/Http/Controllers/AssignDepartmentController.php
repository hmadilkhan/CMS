<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReturnsToOperationsConsole;
use App\Models\AssignDepartment;
use App\Services\Operations\AssignDepartmentPanel;
use Illuminate\Http\Request;

class AssignDepartmentController extends Controller
{
    use ReturnsToOperationsConsole;

    public function index(Request $request)
    {
        return view('operations.assign-department.index', app(AssignDepartmentPanel::class)->data($request));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $count = AssignDepartment::where('department_id', $request->department_id)
                ->where('employee_id', $request->employee_id)
                ->count();

            if ($count == 0) {
                AssignDepartment::create([
                    'department_id' => $request->department_id,
                    'employee_id' => $request->employee_id,
                ]);
                return $this->backToOperations($request, 'assign-department.index')->with('success', 'Data Saved Successfully');
            } else {
                return $this->backToOperations($request, 'assign-department.index')->with('error', 'This assignment already exists');
            }
        } catch (\Throwable $th) {
            return $this->backToOperations($request, 'assign-department.index')->with('error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $assignDepartment = AssignDepartment::findOrFail($request->id);
            $assignDepartment->department_id = $request->department_id;
            $assignDepartment->employee_id = $request->employee_id;
            $assignDepartment->save();

            return $this->backToOperations($request, 'assign-department.index')->with('success', 'Data Updated Successfully');
        } catch (\Throwable $th) {
            return $this->backToOperations($request, 'assign-department.index')->with('error', $th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            AssignDepartment::where('id', $request->id)->delete();
            return response()->json(['status' => 200, 'message' => 'Assign Department deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()]);
        }
    }
}
