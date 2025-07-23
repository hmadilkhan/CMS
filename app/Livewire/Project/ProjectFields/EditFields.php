<?php

namespace App\Livewire\Project\ProjectFields;

use App\Models\Project;
use App\Models\UtilityCompany;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditFields extends Component
{
    // PROJECT OBJECT
    public $project;
    public $ghost;
    public $production_requirement;

    // MAIN FIELDS
    public $projectId;
    public $departmentId;

    // FIRST DEPARTMENT
    public $utility_company;
    public $ntp_approval_date;
    public $hoa;
    public $hoa_phone_number;

    // SECOND DEPARTMENT
    public $site_survey_link;

    // THIRD DEPARTMENT
    public $adders_approve_checkbox;
    public $mpu_required;
    public $meter_spot_request_date;
    public $meter_spot_request_number;
    public $meter_spot_result;
    public $production_value_achieved;

    // FORTH DEPARTMENT
    public $permitting_submittion_date;
    public $actual_permit_fee;
    public $fire_review_required;
    public $permitting_approval_date;
    public $hoa_approval_request_date;
    public $hoa_approval_date;

    // FIFTH DEPARTMENT
    public $solar_install_date;
    public $battery_install_date;
    // public $actual_labor_cost;
    // public $actual_material_cost;
    public $placards_ordered;
    public $placards_note;
    public $mpu_install_date;

    // SIXTH DEPARTMENT
    public $rough_inspection_date;
    public $final_inspection_date;
    public $fire_inspection_date;

    // SEVENTH DEPARTMENT
    public $pto_submission_date;
    public $pto_approval_date;

    // EIGHT DEPARTMENT
    public $coc_packet_mailed_out_date;

    public $message;
    public $messageType;

    public $utilityCompanies;

    public function mount()
    {
        $this->projectId = $this->project->id;
        $this->departmentId = $this->project->department_id;
        $this->project = Project::with('customer', 'customer.finances', 'customer.finances.finance', 'customer.finances.term', 'customer.finances.apr')->findOrFail($this->projectId);
        $this->production_requirement = $this->project->customer->finances->finance->production_requirements;

        // FIRST DEPARTMENT
        $this->utility_company = $this->project->utility_company;
        $this->ntp_approval_date = $this->project->ntp_approval_date;
        $this->hoa = $this->project->hoa;
        $this->hoa_phone_number = $this->project->hoa_phone_number;

        // SECOND DEPARTMENT
        $this->site_survey_link = $this->project->site_survey_link;

        // THIRD DEPARTMENT
        $this->adders_approve_checkbox = $this->project->adders_approve_checkbox;
        $this->mpu_required = $this->project->mpu_required;
        $this->meter_spot_request_date = $this->project->meter_spot_request_date;
        $this->meter_spot_request_number = $this->project->meter_spot_request_number;
        $this->meter_spot_result = $this->project->meter_spot_result;
        $this->production_value_achieved = $this->project->production_value_achieved;

        // FORTH DEPARTMENT
        $this->permitting_submittion_date = $this->project->permitting_submittion_date;
        $this->actual_permit_fee = $this->project->actual_permit_fee;
        $this->permitting_approval_date = $this->project->permitting_approval_date;
        $this->fire_review_required = $this->project->fire_review_required;
        $this->hoa_approval_request_date = $this->project->hoa_approval_request_date;
        $this->hoa_approval_date = $this->project->hoa_approval_date;

        // FIFTH DEPARTMENT
        $this->solar_install_date = $this->project->solar_install_date;
        $this->battery_install_date = $this->project->battery_install_date;
        // $this->actual_labor_cost = $this->project->actual_labor_cost;
        // $this->actual_material_cost = $this->project->actual_material_cost;
        $this->placards_ordered = $this->project->placards_ordered;
        $this->placards_note = $this->project->placards_note;
        $this->mpu_install_date = $this->project->mpu_install_date;

        // SIXTH DEPARTMENT
        $this->rough_inspection_date = $this->project->rough_inspection_date;
        $this->final_inspection_date = $this->project->final_inspection_date;
        $this->fire_inspection_date  = $this->project->fire_inspection_date;

        // SEVENTH DEPARTMENT
        $this->pto_submission_date = $this->project->pto_submission_date;
        $this->pto_approval_date = $this->project->pto_approval_date;

        // EIGHT DEPARTMENT
        $this->coc_packet_mailed_out_date = $this->project->coc_packet_mailed_out_date;

        $this->utilityCompanies = UtilityCompany::all();
    }


    public function updateProjectFields()
    {
        $customMessages = [];
        $project = $this->project; //Project::with('customer','customer.finances','customer.finances.finance','customer.finances.term','customer.finances.apr')->findOrFail($this->projectId);
        $this->production_requirement = $project->customer->finances->finance->production_requirements;

        // if ($this->departmentId == 1) {
        //     $data = [
        //         'utility_company' => 'required_if:departmentId,1|string',
        //         'ntp_approval_date' => 'required_if:departmentId,1|date',
        //         'hoa' => 'required_if:departmentId,1|string',
        //         'hoa_phone_number' =>  Rule::requiredIf(function () {
        //             return  $this->hoa == "yes";
        //         }),
        //     ];

        //     $customMessages = [
        //         'utility_company.required_if' => 'The utility company field is required for this department.',
        //         'ntp_approval_date.required_if' => 'The NTP approval date is required for this department.',
        //         'hoa.required_if' => 'The HOA field is required for this department.',
        //         'hoa_phone_number.required_if' => 'The HOA phone number is required when HOA is marked as "yes".',
        //     ];
        // }
        // if ($this->departmentId == 2) {
        //     $data = [
        //         'site_survey_link' => 'required_if:departmentId,2|url',
        //     ];

        //     $customMessages = [
        //         'site_survey_link.required_if' => 'The site survey link is required for this department.',
        //     ];
        // }
        // if ($this->departmentId == 3) {
        //     $data = [
        //         'adders_approve_checkbox' => 'required_if:departmentId,3',
        //         'mpu_required' => 'required_if:departmentId,3|in:yes,no',
        //         'meter_spot_request_date' =>  Rule::requiredIf(function () {
        //             return  $this->mpu_required == "yes";
        //         }),
        //         'meter_spot_request_number' =>  Rule::requiredIf(function () {
        //             return  $this->mpu_required == "yes";
        //         }),
        //         'meter_spot_result' => 'required_if:departmentId,3|string',
        //         'production_value_achieved' =>  Rule::requiredIf(function () {
        //             return  $this->production_requirement == 1;
        //         }),
        //     ];


        //     $customMessages = [
        //         'adders_approve_checkbox.required_if' => 'The adders approve checkbox is required for this department.',
        //         'mpu_required.required_if' => 'Please specify if MPU is required.',
        //         'meter_spot_request_date.required_if' => 'The meter spot request date is required if MPU is required.',
        //         'meter_spot_request_number.required_if' => 'The meter spot request number is required if MPU is required.',
        //         'meter_spot_result.required_if' => 'The meter spot result is required for this department.',
        //         'production_value_achieved.required_if' => 'The production value achieved is required for this department.',
        //     ];
        // }
        // if ($this->departmentId == 4) {
        //     $data = [
        //         'permitting_submittion_date' => 'required_if:departmentId,4|date',
        //         'permitting_approval_date' => 'required_if:departmentId,4|date',
        //         'fire_review_required' => 'required',
        //         'hoa_approval_request_date' =>  Rule::requiredIf(function () {
        //             return  $this->hoa == "yes";
        //         }),
        //         'hoa_approval_date' =>  Rule::requiredIf(function () {
        //             return  $this->hoa == "yes";
        //         }),
        //     ];

        //     $customMessages = [
        //         'permitting_submittion_date.required_if' => 'The permitting submission date is required for this department.',
        //         'permitting_approval_date.required_if' => 'The permitting approval date is required for this department.',
        //         'fire_review_required.required_if' => 'The fire review field is required for this department.',
        //         'hoa_approval_request_date.required_if' => 'The HOA approval request date is required when HOA is "yes".',
        //         'hoa_approval_date.required_if' => 'The HOA approval date is required when HOA is "yes".',
        //     ];
        // }
        // if ($this->departmentId == 5) {
        //     $data = [
        //         'solar_install_date' => 'required_if:departmentId,5|date',
        //         'battery_install_date' => 'required_if:departmentId,5|date',
        //         'placards_ordered' => 'required_if:departmentId,5',
        //         'placards_note' => 'required_if:departmentId,5',
        //         'mpu_install_date' => Rule::requiredIf(function () use ($project) {
        //             return $this->departmentId == 5 && $project->mpu_required == "yes";
        //         }),
        //     ];

        //     $customMessages = [
        //         'solar_install_date.required_if' => 'The solar install date is required for this department.',
        //         'battery_install_date.required_if' => 'The battery install date is required for this department.',
        //         'mpu_install_date.required_if' => 'The MPU install date is required if MPU is marked as required for this project.',
        //         'placards_ordered.required_if' => 'The Placard Ordered field is required.',
        //         'placards_note.required_if' => 'The Placard Note field is required.',
        //     ];
        // }
        // if ($this->departmentId == 6) {
        //     $data = [
        //         'rough_inspection_date' => 'required_if:departmentId,6|date',
        //         'final_inspection_date' => 'required_if:departmentId,6|date',
        //         'fire_inspection_date' => 'required_if:departmentId,6|date',
        //     ];

        //     $customMessages = [
        //         'rough_inspection_date.required_if' => 'The rough inspection date is required for this department.',
        //         'final_inspection_date.required_if' => 'The final inspection date is required for this department.',
        //         'fire_inspection_date.required_if'  => 'The fire inspection date is required for this department.',
        //     ];
        // }
        // if ($this->departmentId == 7) {
        //     $data = [
        //         'pto_submission_date' => 'required_if:departmentId,7|date',
        //         'pto_approval_date' => 'required_if:departmentId,7|date',
        //     ];

        //     $customMessages = [
        //         'pto_submission_date.required_if' => 'The PTO submission date is required for this department.',
        //         'pto_approval_date.required_if' => 'The PTO approval date is required for this department.',
        //     ];
        // }
        // if ($this->departmentId == 8) {
        //     $data = [
        //         'coc_packet_mailed_out_date' => 'required_if:departmentId,8|date',
        //     ];

        //     $customMessages = [
        //         'coc_packet_mailed_out_date.required_if' => 'The COC packet mailed out date is required for this department.',
        //     ];
        // }
        // $this->validate($data, $customMessages);

        $updateItems = [];
        if ($this->departmentId == 1) {
            $updateItems = array_merge($updateItems, [
                "utility_company" => $this->utility_company,
                "ntp_approval_date" => $this->ntp_approval_date,
                "hoa" => $this->hoa,
                "hoa_phone_number" => $this->hoa_phone_number,
            ]);
        }
        if ($this->departmentId == 2) {
            $updateItems = array_merge($updateItems, [
                "site_survey_link" => $this->site_survey_link,
                // "hoa" => $this->hoa,
                // "hoa_phone_number" => $this->hoa_phone_number,
            ]);
        }

        if ($this->departmentId == 3) {

            if ($this->production_requirement == 1) {
                $positive = $project->customer->finances->finance->positive_variance;
                $negative = $project->customer->finances->finance->negative_variance * -1; // Make negative
                $sold_production_value = $project->customer->sold_production_value;

                // if ($sold_production_value == "" || $sold_production_value == null) {
                //     $this->addError('production_value_achieved', "Sold Production Value is required.");
                //     return;
                // }
                if ($sold_production_value != "" || $sold_production_value != null) {
                    $calculatePercentage = ((($this->production_value_achieved / $sold_production_value) - 1) * 100);

                    if (bccomp($calculatePercentage, $negative, 2) <= 0 || bccomp($calculatePercentage, $positive, 2) >= 0) {
                        $this->addError('production_value_achieved', "Calculated Percentage (" . number_format($calculatePercentage, 2) . "%) is exceeding the allowed variance range.");
                        return;
                    }
                }
            }

            $updateItems = array_merge($updateItems, [
                "adders_approve_checkbox" => $this->adders_approve_checkbox,
                "mpu_required" => $this->mpu_required,
                "meter_spot_request_date" => $this->meter_spot_request_date,
                "meter_spot_request_number" => $this->meter_spot_request_number,
                "meter_spot_result" => $this->meter_spot_result,
            ], $this->production_requirement == 1 ? [
                "production_value_achieved" => $this->production_value_achieved,
            ] : []);
        }

        if ($this->departmentId == 4) {
            $updateItems = array_merge($updateItems, [
                "permitting_submittion_date" => $this->permitting_submittion_date,
                "actual_permit_fee" => $this->actual_permit_fee,
                "permitting_approval_date" => $this->permitting_approval_date,
                "hoa_approval_request_date" => $this->hoa_approval_request_date,
                "hoa_approval_date" => $this->hoa_approval_date,
                "fire_review_required" => $this->fire_review_required,
            ]);
            
        }

        if ($this->departmentId == 5) {
            $updateItems = array_merge($updateItems, [
                "solar_install_date" => $this->solar_install_date,
                // "actual_labor_cost" => $this->actual_labor_cost,
                // "actual_material_cost" => $this->actual_material_cost,
                "placards_ordered" => $this->placards_ordered,
                "placards_note" => $this->placards_note,
                "battery_install_date" => $this->battery_install_date,
                "mpu_install_date" => $this->mpu_install_date,
            ]);
        }

        if ($this->departmentId == 6) {
            $updateItems = array_merge($updateItems, [
                "rough_inspection_date" => $this->rough_inspection_date,
                "final_inspection_date" => $this->final_inspection_date,
                "fire_inspection_date" => $this->fire_inspection_date,
            ]);
        }

        if ($this->departmentId == 7) {
            $updateItems = array_merge($updateItems, [
                "pto_submission_date" => $this->pto_submission_date,
                "pto_approval_date" => $this->pto_approval_date,
            ]);
        }

        if ($this->departmentId == 8) {
            $updateItems = array_merge($updateItems, [
                "coc_packet_mailed_out_date" => $this->coc_packet_mailed_out_date,
            ]);
        }

        try {
            Project::where("id", $this->projectId)->update($updateItems);
            $username = auth()->user()->name;
            // Get the changed field names
            $changedFields = collect($updateItems)->keys()->implode(', ');
            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user()) // Log who did the action
                ->withProperties($updateItems)
                ->setEvent("updated")
                ->log("{$username} updated the project fields: {$changedFields}.");

            $this->message = 'Data updated successfully!';
            $this->messageType = 'success';
        } catch (\Exception $e) {
            $this->message = 'Failed to update data!';
            $this->messageType = 'error';
            dd($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.project.project-fields.edit-fields');
    }
}
