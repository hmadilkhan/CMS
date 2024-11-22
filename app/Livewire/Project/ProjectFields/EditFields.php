<?php

namespace App\Livewire\Project\ProjectFields;

use Illuminate\Validation\Rule;
use Livewire\Component;

class EditFields extends Component
{
    public $project;
    public $departmentId;
    public $formData = [
        'project_id' => null,
        'forward' => null,
        'utility_company' => null,
        'ntp_approval_date' => null,
    ];

    // protected $rules = [
    //     'formData.utility_company' => 'required_if:formData.forward,1|string',
    //     'formData.ntp_approval_date' => 'required_if:formData.forward,1|date',
    //     'formData.hoa' => 'required_if:formData.forward,1|string',
    //     'formData.hoa_phone_number' => 'required_if:formData.forward,1|nullable|string',
    //     'formData.site_survey_link' => 'required_if:formData.forward,2|url',
    //     'formData.adders_approve_checkbox' => 'required_if:formData.forward,3|boolean',
    //     'formData.mpu_required' => 'required_if:formData.forward,3|in:yes,no',
    //     'formData.meter_spot_request_date' => 'required_if:formData.mpu_required,yes|date',
    //     'formData.meter_spot_request_number' => 'required_if:formData.mpu_required,yes|string',
    //     'formData.meter_spot_result' => 'required_if:formData.forward,3|string',
    //     'formData.permitting_submittion_date' => 'required_if:formData.forward,4|date',
    //     'formData.permitting_approval_date' => 'required_if:formData.forward,4|date',
    //     'formData.hoa_approval_request_date' => 'required_if:formData.projecthoa,yes|date',
    //     'formData.hoa_approval_date' => 'required_if:formData.projecthoa,yes|date',
    //     'formData.solar_install_date' => 'required_if:formData.forward,5|date',
    //     'formData.battery_install_date' => 'required_if:formData.forward,5|date',
    //     'formData.mpu_install_date' => 'required_if:formData.forward,5|date',
    //     'formData.rough_inspection_date' => 'required_if:formData.forward,6|date',
    //     'formData.final_inspection_date' => 'required_if:formData.forward,6|date',
    //     'formData.pto_submission_date' => 'required_if:formData.forward,7|date',
    //     'formData.pto_approval_date' => 'required_if:formData.forward,7|date',
    //     'formData.coc_packet_mailed_out_date' => 'required_if:formData.forward,8|date',
    // ];
    

    public function updateProjectFields()
    {
        if (empty($this->formData)) {
            dd('formData is empty');
        }
        dump($this->formData);
        // Add default values for keys if not set

        $validatedData = $this->validate(
        //     [
        //     'formData.hoa_phone_number' => Rule::requiredIf(function () {
        //         return $this->formData['forward'] == 1 && $this->formData['hoa'] != 'yes';
        //     }),
        //     'formData.mpu_install_date' => Rule::requiredIf(function () {
        //         return $this->formData['forward'] == 5 && $this->formData['projectmpu'] != 'yes';
        //     }),
        // ]
    );
        // $validatedData = validator($data, [
        //     'utility_company' => 'required_if:forward,1',
        //     'ntp_approval_date' => 'required_if:forward,1',
        //     'hoa' => 'required_if:forward,1',
        //     'hoa_phone_number' => Rule::requiredIf(function () use ($data) {
        //         return $data["forward"] == 1 && !$data["hoa"] == "yes";
        //     }),
        //     'site_survey_link' => 'required_if:forward,2',
        //     'adders_approve_checkbox' => 'required_if:forward,3',
        //     'mpu_required' => 'required_if:forward,3',
        //     'meter_spot_request_date' => 'required_if:mpu_required,yes',
        //     'meter_spot_request_number' => 'required_if:mpu_required,yes',
        //     'meter_spot_result' => 'required_if:forward,3',
        //     'permitting_submittion_date' => 'required_if:forward,4',
        //     'permitting_approval_date' => 'required_if:forward,4',
        //     'hoa_approval_request_date' => 'required_if:projecthoa,yes',
        //     'hoa_approval_date' => 'required_if:projecthoa,yes',
        //     'solar_install_date' => 'required_if:forward,5',
        //     'battery_install_date' => 'required_if:forward,5',
        //     'mpu_install_date' =>   Rule::requiredIf(function () use ($data) {
        //         return $data["forward"] == 5 && !$data["projectmpu"] == "yes";
        //     }),
        //     'rough_inspection_date' => 'required_if:forward,6',
        //     'final_inspection_date' => 'required_if:forward,6',
        //     'pto_submission_date' => 'required_if:forward,7',
        //     'pto_approval_date' => 'required_if:forward,7',
        //     'coc_packet_mailed_out_date' => 'required_if:forward,8',
        // ])->validate();

        dump($validatedData);
        dd("Submit");
    }

    public function render()
    {
        return view('livewire.project.project-fields.edit-fields');
    }
}
