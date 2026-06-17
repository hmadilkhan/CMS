<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Adder;
use App\Models\AdderSubType;
use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\BatteryType;
use App\Models\Customer;
use App\Models\CustomerAdder;
use App\Models\CustomerFinance;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\OfficeCost;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectAssignmentService;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    use MediaTrait;

    protected function financeOptionRequiresLoanId(?int $financeOptionId): bool
    {
        if (empty($financeOptionId)) {
            return false;
        }

        return (int) FinanceOption::where('id', $financeOptionId)->value('loan_id') === 1;
    }

    protected function financeOptionIsPrepaidPpa(?int $financeOptionId): bool
    {
        if (empty($financeOptionId)) {
            return false;
        }

        $financeOption = FinanceOption::find($financeOptionId);

        return !empty($financeOption)
            && ((int) $financeOption->id === 9 || strcasecmp(trim($financeOption->name), 'Prepaid PPA') === 0);
    }

    protected function customerValidationRules(Request $request): array
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'street' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipcode' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'panel_qty' => 'required|numeric',
            'sold_date' => 'required|date',
            'sales_partner_id' => 'required|exists:sales_partners,id',
            'sales_partner_user_id' => 'required|exists:users,id',
            'inverter_type_id' => 'required|exists:inverter_types,id',
            'module_type_id' => 'required|exists:module_types,id',
            'module_qty' => 'required|numeric',
            'inverter_qty' => 'required|numeric',
            'finance_option_id' => 'required|exists:finance_options,id',
            'loan_term_id' => 'nullable|exists:loan_terms,id',
            'loan_apr_id' => 'nullable|exists:loan_aprs,id',
            'contract_amount' => 'required|numeric',
            'redline_costs' => 'required|numeric',
            'adders_amount' => 'nullable|numeric',
            'commission' => 'required|numeric',
            'dealer_fee' => 'required|numeric',
            'dealer_fee_amount' => 'nullable|numeric',
            'overwrite_base_price' => 'nullable|numeric',
            'overwrite_panel_price' => 'nullable|numeric',
            'third_party_credit' => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () use ($request) {
                    return $this->financeOptionIsPrepaidPpa((int) $request->finance_option_id);
                }),
            ],
            'customer_portion' => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () use ($request) {
                    return $this->financeOptionIsPrepaidPpa((int) $request->finance_option_id);
                }),
            ],
            'loanId' => [
                Rule::requiredIf(function () use ($request) {
                    return $this->financeOptionRequiresLoanId((int) $request->finance_option_id);
                }),
            ],
            'adders' => 'nullable|array',
            'adders.*' => 'required_with:uom.*|exists:adder_types,id',
            'uom' => 'nullable|array',
            'uom.*' => 'required_with:adders.*|exists:adder_units,id',
            'amount' => 'nullable|array',
            'amount.*' => 'required_with:adders.*|numeric',
        ];
    }

    protected function resolveCustomerCosts(Request $request): array
    {
        $inverterBaseCost = InverterTypeRate::where("inverter_type_id", $request->inverter_type_id)->first();
        $moduleCost = ModuleType::where("inverter_type_id", $request->inverter_type_id)
            ->where("id", $request->module_type_id)
            ->first();
        $financeOption = FinanceOption::find($request->finance_option_id);

        if (empty($inverterBaseCost)) {
            throw ValidationException::withMessages([
                'inverter_type_id' => 'No base cost is configured for the selected inverter type.',
            ]);
        }

        if (empty($moduleCost)) {
            throw ValidationException::withMessages([
                'module_type_id' => 'The selected module type is not configured for this inverter type.',
            ]);
        }

        if (empty($financeOption)) {
            throw ValidationException::withMessages([
                'finance_option_id' => 'The selected finance option is invalid.',
            ]);
        }

        return [$inverterBaseCost, $moduleCost, $financeOption];
    }

    protected function resolveInitialSubDepartment(Request $request): SubDepartment
    {
        $query = SubDepartment::query();

        if ((int) $request->adu === 1) {
            $query->where("name", "New Construction");
        } else {
            $query->where("department_id", 1);
        }

        $subDepartment = $query->first();

        if (empty($subDepartment)) {
            throw ValidationException::withMessages([
                'adu' => 'No starting subdepartment is configured for this ADU selection.',
            ]);
        }

        return $subDepartment;
    }

    protected function holdBackAmount(Request $request, FinanceOption $financeOption): float
    {
        if ((int) $financeOption->holdback !== 1) {
            return 0;
        }

        return (float) $request->module_qty * (float) $financeOption->dollar_watt_value;
    }

    protected function customerData(Request $request): array
    {
        return [
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "street" => $request->street,
            "city" => $request->city,
            "state" => $request->state,
            "zipcode" => $request->zipcode,
            "phone" => $request->phone,
            "email" => $request->email,
            "sales_partner_id" => $request->sales_partner_id,
            "sub_contractor_id" => $request->sub_contractor_id,
            "sold_date" => $request->sold_date,
            "panel_qty" => $request->panel_qty,
            "inverter_type_id" => $request->inverter_type_id,
            "module_type_id" => $request->module_type_id,
            "inverter_qty" => $request->inverter_qty,
            "module_value" => $request->module_qty,
            "notes" => $request->notes,
            "is_adu" => $request->adu,
            "loan_id" => $request->loanId,
            "sold_production_value" => $request->sold_production_value,
            "preferred_language" => $request->preferred_language,
        ];
    }

    protected function financeData(Request $request, ModuleType $moduleCost, InverterTypeRate $inverterBaseCost, float $holdBackAmount): array
    {
        $isPrepaidPpa = $this->financeOptionIsPrepaidPpa((int) $request->finance_option_id);

        return [
            "finance_option_id" => $request->finance_option_id,
            "loan_term_id" => $request->loan_term_id,
            "loan_apr_id" => $request->loan_apr_id,
            "contract_amount" => $request->contract_amount,
            "redline_costs" => $request->redline_costs,
            "adders" => $request->adders_amount ?? 0,
            "commission" => $request->commission,
            "dealer_fee" => $request->dealer_fee,
            "dealer_fee_amount" => $request->dealer_fee_amount ?? 0,
            "third_party_credit" => ($isPrepaidPpa ? $request->third_party_credit : 0),
            "customer_portion" => ($isPrepaidPpa ? $request->customer_portion : 0),
            "total_overwrite_base_price" => $request->overwrite_base_price ?? 0,
            "total_overwrite_panel_price" => (($request->overwrite_panel_price ?? 0) * $request->panel_qty),
            "module_type_cost" => $moduleCost->amount,
            "inverter_base_cost" => $inverterBaseCost->base_cost,
            "holdback_amount" => $holdBackAmount,
        ];
    }

    protected function syncCustomerAdders(Customer $customer, Request $request): void
    {
        $customer->adders()->delete();

        foreach ($request->uom ?? [] as $index => $uomId) {
            $customer->adders()->create([
                "adder_type_id" => $request->adders[$index],
                "adder_unit_id" => $uomId,
                "amount" => $request->amount[$index],
            ]);
        }
    }

    protected function ajaxError(\Throwable $th)
    {
        return response()->json(["status" => 500, "message" => $th->getMessage()], 500);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("customer.index", [
            "customers" => Customer::getCustomers()->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("customer.create", [
            "financeoptions" => FinanceOption::all(),
            "partners" => SalesPartner::all(), //User::filterByRole('Sales Person')->get(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "contractors" => SubContractor::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $officeCost = OfficeCost::first();
        $request->validate($this->customerValidationRules($request));

        try {
            DB::beginTransaction();

            [$inverterBaseCost, $moduleCost, $financeOption] = $this->resolveCustomerCosts($request);
            $subdepartment = $this->resolveInitialSubDepartment($request);
            $holdBackAmount = $this->holdBackAmount($request, $financeOption);

            $customer = Customer::create($this->customerData($request));

            CustomerFinance::create($this->financeData($request, $moduleCost, $inverterBaseCost, $holdBackAmount) + [
                "customer_id" => $customer->id,
            ]);

            $this->syncCustomerAdders($customer, $request);

            $avgPermitFee = DB::table('projects')
            ->selectRaw('AVG(actual_permit_fee) as avg_permit_fee')
            ->whereNotNull('actual_permit_fee')
            ->first();

            $project = Project::create([
                "customer_id" => $customer->id,
                "project_name" => $request->first_name . "-" . $request->last_name,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
                "description" =>  $request->notes,
                "office_cost" => (!empty($officeCost) ? $officeCost->cost : ""),
                "sales_partner_user_id" => $request->sales_partner_user_id,
                "sub_contractor_user_id" => $request->sub_contractor_user_id,
                "code" => $this->generateProjectCode(),
                "overwrite_base_price" =>  $request->overwrite_base_price ?? 0,
                "overwrite_panel_price" =>  $request->overwrite_panel_price ?? 0,
                "pre_estimated_permit_costs" =>  $avgPermitFee->avg_permit_fee ?? 0,
            ]);
            $username = auth()->user()->name;
            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user()) // Log who did the action
                ->setEvent("update")
                ->log("{$username} created the project.");

            $assignedEmployee = app(ProjectAssignmentService::class)->employeeForDepartment(1) ?? Employee::with('user')->find(1);
            $task = Task::create([
                "project_id" => $project->id,
                "employee_id" => $assignedEmployee?->id ?? 1,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
            ]);
            app(ProjectAssignmentService::class)->notifyAssignedEmployee($assignedEmployee, $project, $task);
            DB::commit();
            return redirect()->route("customers.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof ValidationException) {
                throw $th;
            }
            return redirect()->route("customers.create")->withInput()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    public function generateProjectCode()
    {
        $project = Project::lockForUpdate()->orderBy("id", "DESC")->first("code");

        if (empty($project)) {
            $code = "1001";
            return $code;
        } else {
            return $project->code + 1;
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view("customer.edit", [
            "customer" => $customer,
            "financeoptions" => FinanceOption::all(),
            "partners" =>  SalesPartner::all(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "users" => User::where("sales_partner_id", $customer->sales_partner_id)->get(),
            "contractors" => SubContractor::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate($this->customerValidationRules($request));

        try {
            DB::beginTransaction();

            [$inverterBaseCost, $moduleCost, $financeOption] = $this->resolveCustomerCosts($request);
            $subdepartment = $this->resolveInitialSubDepartment($request);
            $holdBackAmount = $this->holdBackAmount($request, $financeOption);
            $project = $customer->project()->first();

            if (empty($project)) {
                throw ValidationException::withMessages([
                    'project' => 'This customer does not have a project record to update.',
                ]);
            }

            $customer->update($this->customerData($request));

            $project->update([
                "project_name" => $request->first_name . "-" . $request->last_name,
                "sub_department_id" => $subdepartment->id,
                "description" =>  $request->notes,
                "sales_partner_user_id" => $request->sales_partner_user_id,
                "sub_contractor_user_id" => $request->sub_contractor_user_id,
                "overwrite_base_price" =>  $request->overwrite_base_price ?? 0,
                "overwrite_panel_price" =>  $request->overwrite_panel_price ?? 0,
            ]);

            Task::where("project_id", $project->id)
                ->where("department_id", 1)
                ->update(["sub_department_id" => $subdepartment->id]);

            $this->syncCustomerAdders($customer, $request);

            CustomerFinance::updateOrCreate(
                ["customer_id" => $customer->id],
                $this->financeData($request, $moduleCost, $inverterBaseCost, $holdBackAmount)
            );

            DB::commit();
            return redirect()->route("customers.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof ValidationException) {
                throw $th;
            }
            return redirect()->route("customers.edit", $customer->id)->withInput()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            Project::where("customer_id", $request->id)->delete();
            Customer::where("id", $request->id)->delete();
            DB::commit();
            return response()->json(["status" => 200, "message" => "Customer Deleted Successfully."]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => "Error. " . $th->getMessage()]);
        }
    }

    public function getLoanTerms(Request $request)
    {
        try {
            $terms = LoanTerm::where("finance_option_id", $request->id)->get();
            return response()->json(["status" => 200, "terms" => $terms]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getFinanceOptionById(Request $request)
    {
        try {
            $financeOptions = FinanceOption::where("id", $request->id)->first();
            if (empty($financeOptions)) {
                return response()->json(["status" => 404, "message" => "Finance option not found."], 404);
            }
            return response()->json(["status" => 200, "finance_options" => $financeOptions]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getLoanAprs(Request $request)
    {
        try {
            $aprs = LoanApr::where("loan_term_id", $request->id)->where("finance_option_id", $request->finance_option_id)->get();
            return response()->json(["status" => 200, "aprs" => $aprs]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getDealerFee(Request $request)
    {
        try {
            $dealer_fee = LoanApr::where("id", $request->id)->first("dealer_fee");
            if (empty($dealer_fee)) {
                return response()->json(["status" => 404, "message" => "Dealer fee not found."], 404);
            }
            return response()->json(["status" => 200, "dealerfee" => $dealer_fee->dealer_fee]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getRedlineCost(Request $request)
    {
        try {
            // $cost = InverterTypeRate::where("inverter_type_id", $request->inverterType)->where("panels_qty", $request->qty)->first("redline_cost");
            $cost = InverterTypeRate::where("inverter_type_id", $request->inverterType)->first("base_cost");
            $modules = ModuleType::where("inverter_type_id", $request->inverterType)->get();
            if (empty($cost)) {
                return response()->json(["status" => 404, "message" => "Redline cost not found for this inverter type."], 404);
            }
            return response()->json(["status" => 200, "redlinecost" => $cost->base_cost, "modules" => $modules]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getSubAdders(Request $request)
    {
        try {
            $subadders = AdderSubType::where("adder_type_id", $request->id)->get();
            return response()->json(["status" => 200, "subadders" => $subadders]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getAdderDetails(Request $request)
    {
        try {
            $adders = Adder::where("adder_type_id", $request->adder)
                //  ->where("adder_sub_type_id", $request->subadder)
                ->first();
            if (empty($adders)) {
                return response()->json(["status" => 404, "message" => "Adder details not found."], 404);
            }
            return response()->json(["status" => 200, "adders" => $adders]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getModulTypevalue(Request $request)
    {
        try {
            $types = ModuleType::where("id", $request->id)->where("inverter_type_id", $request->inverterTypeId)->first();
            if (empty($types)) {
                return response()->json(["status" => 404, "message" => "Module type not found for this inverter type."], 404);
            }
            return response()->json(["status" => 200, "types" => $types]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getSalesPartnerUsers(Request $request)
    {
        try {
            $users = User::where("sales_partner_id", $request->id)->where("user_type_id",3)->get();
            return response()->json(["status" => 200, "users" => $users]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function getSubContractorUsers(Request $request)
    {
        try {
            $users = User::where("sales_partner_id", $request->id)->where("user_type_id",4)->get();
            return response()->json(["status" => 200, "users" => $users]);
        } catch (\Throwable $th) {
            return $this->ajaxError($th);
        }
    }

    public function sendEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                "project_id" => ["required", "exists:projects,id"],
                "department_id" => ["required", "exists:departments,id"],
                "customer_id" => ["required", "exists:customers,id"],
                "subject" => ["required", "string", "max:255"],
                "content" => ["required", "string"],
                "ccEmails" => ["nullable", "string"],
                "images" => ["nullable", "array"],
                "images.*" => ["file", "max:10240"],
            ]);

            $project = Project::with(["customer", "salesPartnerUser"])
                ->where("id", $validated["project_id"])
                ->where("customer_id", $validated["customer_id"])
                ->firstOrFail();

            if (empty($project->customer?->email)) {
                return response()->json(["status" => 422, "message" => "Customer email is missing."], 422);
            }

            $attachments = [];
            $ccEmails = [];
            $subject = $validated["subject"];
            if (!empty($project->code) && stripos($subject, $project->code) === false) {
                $subject .= " [" . $project->code . "]";
            }

            $details = [
                "subject" => $subject,
                "body" => $validated["content"],
                "project_id" => $project->id,
                "department_id" => $validated["department_id"],
                "customer_id" => $project->customer_id,
                "customer_email" => $project->customer->email,
                "user_id" => auth()->user()->id,
                "message_id" => $this->makeProjectMessageId($project),
            ];

            if (!empty($validated["ccEmails"])) {
                $ccEmails = array_filter($this->handleCommaSeparatedValues($validated["ccEmails"]));
            }

            foreach ($ccEmails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return response()->json(["status" => 422, "message" => "Invalid CC email: {$email}"], 422);
                }
            }

            if (!empty($project->salesPartnerUser?->email)) {
                $ccEmails[] = $project->salesPartnerUser->email;
            }

            $ccEmails = array_values(array_unique($ccEmails));

            if ($request->hasFile("images")) {
                foreach ($request->file("images") as $file) {
                    $savedFile = $this->uploads($file, 'emails/');
                    array_push($attachments, $savedFile['fileName']);
                }
            }

            SendEmailJob::dispatchSync($details, $attachments, $ccEmails);

            return response()->json(["status" => 200, "message" => "Email has been sent", "ccEmails" => $ccEmails]);
        } catch (ValidationException $th) {
            return response()->json([
                "status" => 422,
                "message" => $th->validator->errors()->first(),
                "errors" => $th->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => "Error : " . $th->getMessage()]);
        }
    }

    function handleCommaSeparatedValues($input)
    {
        // Check if the input contains a comma
        if (strpos($input, ',') !== false) {
            // Explode the string into an array of values
            $values = explode(',', $input);

            // Trim whitespace around each value
            $values = array_map('trim', $values);
        } else {
            // If no comma, treat it as a normal string
            $values = [$input];
        }

        return $values;
    }

    private function makeProjectMessageId(Project $project): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'solaroperations.info';

        return 'crm-project-' . $project->id . '-' . Str::uuid() . '@' . $host;
    }
}
