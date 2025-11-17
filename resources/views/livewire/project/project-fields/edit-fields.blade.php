<div>
    @if ($message)
        <div class="alert alert-{{ $messageType == 'success' ? 'success' : 'danger' }}">
            {{ $message }}
        </div>
    @endif
    <form id="mainForm" wire:submit.prevent="updateProjectFields">
        <div class="row">
            <input type="hidden" id="project_id" wire:model="projectId" name="project_id" />
            <input type="hidden" id="forward" name="forward" />
            @if ($departmentId == 1)
                <div class="col-sm-4 mb-3 ">
                    <label for="utility_company" class="form-label" id="requiredfiles">Utility Company</label>
                    <select class="form-select" aria-label="Default select HOA" wire:model.live="utility_company"
                        id="utility_company" name="utility_company">
                        <option value="">Select Utility Company</option>
                        @foreach ($utilityCompanies as $utility)
                            <option
                                {{ $project->utility_company != '' && $project->utility_company == $utility->name ? 'selected' : '' }}
                                value="{{ $utility->name }}">{{ $utility->name }}</option>
                        @endforeach
                    </select>
                    {{-- <input class="form-control" type="text" id="utility_company" name="utility_company"
                        wire:model="utility_company"> --}}
                    @error('utility_company')
                        <div id="utility_company_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="ntp_approval_date" class="form-label" id="requiredfiles">NTP Approval Date</label>
                    <input class="form-control" type="date" id="ntp_approval_date" name="ntp_approval_date"
                        wire:model="ntp_approval_date">
                    @error('ntp_approval_date')
                        <div id="ntp_approval_date_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 ">
                    <label for="hoa" class="form-label">HOA</label>
                    <select class="form-select" aria-label="Default select HOA" wire:model.live="hoa" id="hoa"
                        name="hoa">
                        <option value="">Select HOA</option>
                        <option {{ $project->hoa != '' && $project->hoa == 'yes' ? 'selected' : '' }} value="yes">Yes
                        </option>
                        <option {{ $project->hoa != '' && $project->hoa == 'no' ? 'selected' : '' }} value="no">No
                        </option>
                    </select>
                    @error('hoa')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                @if ($hoa == 'yes')
                    <div class="col-sm-4 mb-3" id="hoa_select">
                        <label for="hoa_phone_number" class="form-label" id="requiredfiles">Phone Number Field</label>
                        <input class="form-control" type="text" id="hoa_phone_number" name="hoa_phone_number"
                            wire:model="hoa_phone_number">
                        @error('hoa_phone_number')
                            <div id="hoa_phone_number_message" class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            @endif
            @if ($departmentId == 2)
                <div class="col-sm-4 mb-3 ">
                    <label for="site_survey_link" class="form-label" id="requiredfiles">Site Survey Link</label>
                    <input class="form-control" type="text" id="site_survey_link" name="site_survey_link"
                        wire:model="site_survey_link">
                    @error('site_survey_link')
                        <div id="site_survey_link_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
            @endif
            @if ($departmentId == 3)
                <div class="col-sm-4 ">
                    <label for="hoa" class="form-label">Adders Approved</label>
                    <select class="form-select" aria-label="Default select Adders Approved" id="adders_approve_checkbox"
                        name="adders_approve_checkbox" wire:model="adders_approve_checkbox">
                        <option value="">Select Adders Approved</option>
                        <option
                            {{ $project->adders_approve_checkbox != '' && $project->adders_approve_checkbox == 'yes' ? 'selected' : '' }}
                            value="yes">Yes</option>
                        <option
                            {{ $project->adders_approve_checkbox != '' && $project->adders_approve_checkbox == 'no' ? 'selected' : '' }}
                            value="no">No</option>
                    </select>
                    @error('adders_approve_checkbox')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 ">
                    <label for="mpu_required" class="form-label">MPU Required {{ $mpu_required }}</label>
                    <select class="form-select" aria-label="Default select MPU Required" id="mpu_required"
                        name="mpu_required" wire:model.live="mpu_required">
                        <option value="">Select MPU Required</option>
                        <option {{ $project->mpu_required != '' && $project->mpu_required == 'yes' ? 'selected' : '' }}
                            value="yes">Yes</option>
                        <option {{ $project->mpu_required != '' && $project->mpu_required == 'no' ? 'selected' : '' }}
                            value="no">No</option>
                    </select>
                    @error('mpu_required')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                @if ($mpu_required == 'yes')
                    <div class="col-sm-4 mb-3 mpuselect">
                        <label for="meter_spot_request_date" class="form-label" id="requiredfiles">Meter Spot Request
                            Date</label>
                        <input class="form-control" type="date" id="meter_spot_request_date"
                            name="meter_spot_request_date" wire:model="meter_spot_request_date">
                        @error('meter_spot_request_date')
                            <div id="meter_spot_request_date_message" class="text-danger message mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-sm-4 mb-3 mpuselect">
                        <label for="meter_spot_request_number" class="form-label" id="requiredfiles">Meter Spot
                            Request
                            Number</label>
                        <input class="form-control" type="text" id="meter_spot_request_number"
                            name="meter_spot_request_number" wire:model="meter_spot_request_number">
                        @error('meter_spot_request_number')
                            <div id="meter_spot_request_number_message" class="text-danger message mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                @endif
                {{-- <div class="col-sm-4 mb-3">
                    <label for="meter_spot_result" class="form-label">Meter Spot Result</label>
                    <select class="form-select" aria-label="Default select Meter Spot Result" id="meter_spot_result"
                        name="meter_spot_result" wire:model="meter_spot_result">
                        <option value="">Select Meter Spot Result</option>
                        <option
                            {{ $project->meter_spot_result != '' && $project->meter_spot_result == 'same' ? 'selected' : '' }}
                            value="same">Same Location</option>
                        <option
                            {{ $project->meter_spot_result != '' && $project->meter_spot_result == 'relocation' ? 'selected' : '' }}
                            value="relocation">Relocation</option>
                    </select>
                    @error('meter_spot_result')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div> --}}
                @if ($production_requirement == 1)
                    <div class=" col-sm-5 mb-3 mt-1 ">
                        <label for="production_value_achieved" class="form-label">Production Value Achieved</label>
                        <input class="form-control" type="text" id="production_value_achieved"
                            name="production_value_achieved" wire:model="production_value_achieved">
                    </div>
                    @error('production_value_achieved')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                @endif
            @endif

            @if ($departmentId == 4)
                <div class="col-sm-4 mb-3 ">
                    <label for="permitting_submittion_date" class="form-label">Permit Submission Date</label>
                    <input class="form-control" type="date" id="permitting_submittion_date"
                        name="permitting_submittion_date" wire:model="permitting_submittion_date">
                    @error('permitting_submittion_date')
                        <div id="permitting_submittion_date_message" class="text-danger message mt-2">{{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="actual_permit_fee" class="form-label">Actual Permit Fee</label>
                    <input class="form-control" type="text" id="actual_permit_fee" name="actual_permit_fee"
                        wire:model="actual_permit_fee">
                    @error('actual_permit_fee')
                        <div id="actual_permit_fee_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="permitting_approval_date" class="form-label">Permit Approval Date</label>
                    <input class="form-control" type="date" id="permitting_approval_date"
                        name="permitting_approval_date" wire:model="permitting_approval_date">
                    @error('permitting_approval_date')
                        <div id="permitting_approval_date_message" class="text-danger message mt-2">{{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-sm-4 ">
                    <label for="placards_ordered" class="form-label">Fire Review Required</label>
                    <select class="form-select" aria-label="Default select Fire Review Required"
                        id="fire_review_required" name="fire_review_required" wire:model.live="fire_review_required">
                        <option value="">Fire Review Required</option>
                        <option @selected($project->fire_review_required) value="1">Yes</option>
                        <option @selected($project->fire_review_required) value="0">No</option>
                    </select>
                    @error('placards_ordered')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <input type="hidden" name="projecthoa" value="{{ $project->hoa }}" />
                @if ($project->hoa == 'yes')
                    <div class="col-sm-4 mb-3 ">
                        <label for="hoa_approval_request_date" class="form-label">HOA Approval Request Date</label>
                        <input class="form-control" type="date" id="hoa_approval_request_date"
                            name="hoa_approval_request_date" wire:model="hoa_approval_request_date">
                        @error('hoa_approval_request_date')
                            <div id="hoa_approval_request_date_message" class="text-danger message mt-2">
                                {{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3 ">
                        <label for="hoa_approval_date" class="form-label">HOA Approval Date</label>
                        <input class="form-control" type="date" id="hoa_approval_date" name="hoa_approval_date"
                            wire:model="hoa_approval_date">
                        @error('hoa_approval_date')
                            <div id="hoa_approval_date_message" class="text-danger message mt-2">{{ $message }}
                            </div>
                        @enderror
                    </div>
                @endif
            @endif
            @if ($departmentId == 5)
                <div class="col-sm-4 mb-3 ">
                    <label for="solar_install_date" class="form-label">Solar Install Date </label>
                    <input class="form-control" type="date" id="solar_install_date" name="solar_install_date"
                        wire:model="solar_install_date">
                    @error('solar_install_date')
                        <div id="solar_install_date_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="battery_install_date" class="form-label">Battery Install Date</label>
                    <input class="form-control" type="date" id="battery_install_date" name="battery_install_date"
                        wire:model="battery_install_date">
                    @error('battery_install_date')
                        <div id="battery_install_date_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Sub-Contractors</label>
                    <select class="form-select" aria-label="Default select Sub-Contractors" id="sub_contractor_id"
                        name="sub_contractor_id" wire:model="sub_contractor_id"
                        onchange="loadSubContractorUsers(this.value)">
                        <option value="">Select Sub-Contractors</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}">
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sub_contractor_id')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Sub-Contractor User</label>
                    <select class="form-select" aria-label="Default select Sub-Contractor"
                        id="sub_contractor_user_id" name="sub_contractor_user_id"
                        wire:model="sub_contractor_user_id">
                        <option value="">Select Sub-Contractor User</option>
                    </select>
                    @error('sub_contractor_user_id')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                {{-- <div class="col-sm-4 ">
                    <label for="placards_ordered" class="form-label">Placards Required</label>
                    <select class="form-select" aria-label="Default select MPU Required" id="placards_ordered"
                        name="placards_ordered" wire:model.live="placards_ordered">
                        <option value="">Select Placards Required</option>
                        <option
                            {{ $project->placards_ordered != '' && $project->placards_ordered == 'yes' ? 'selected' : '' }}
                            value="yes">Yes</option>
                        <option
                            {{ $project->placards_ordered != '' && $project->placards_ordered == 'no' ? 'selected' : '' }}
                            value="no">No</option>
                    </select>
                    @error('placards_ordered')
                        <div class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="placards_note" class="form-label">Placards Note</label>
                    <input class="form-control" type="text" id="placards_note" name="placards_note"
                        wire:model="placards_note">
                    @error('placards_note')
                        <div id="placards_note_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div> --}}
                {{-- <div class="col-sm-4 mb-3 ">
                    <label for="actual_labor_cost" class="form-label">Actual Labor Cost</label>
                    <input class="form-control" type="text" id="actual_labor_cost" name="actual_labor_cost"
                        wire:model="actual_labor_cost">
                    @error('actual_labor_cost')
                        <div id="actual_labor_cost_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="actual_material_cost" class="form-label">Actual Material Cost</label>
                    <input class="form-control" type="text" id="actual_material_cost" name="actual_material_cost"
                        wire:model="actual_material_cost">
                    @error('actual_material_cost')
                        <div id="actual_material_cost_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div> --}}
                @if ($project->mpu_required == 'yes')
                    <div class="col-sm-4 mb-3 ">
                        <label for="mpu_install_date" class="form-label">MPU Install Date</label>
                        <input class="form-control" type="date" id="mpu_install_date" name="mpu_install_date"
                            wire:model="mpu_install_date">
                        @error('mpu_install_date')
                            <div id="mpu_install_date_message" class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-4 mb-3">
                        <label for="meter_spot_result" class="form-label">Meter Spot Result</label>
                        <select class="form-select" aria-label="Default select Meter Spot Result"
                            id="meter_spot_result" name="meter_spot_result" wire:model="meter_spot_result">
                            <option value="">Select Meter Spot Result</option>
                            <option
                                {{ $project->meter_spot_result != '' && $project->meter_spot_result == 'same' ? 'selected' : '' }}
                                value="same">Same Location</option>
                            <option
                                {{ $project->meter_spot_result != '' && $project->meter_spot_result == 'relocation' ? 'selected' : '' }}
                                value="relocation">Relocation</option>
                        </select>
                        @error('meter_spot_result')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            @endif
            @if ($departmentId == 6)
                <div class="col-sm-4 mb-3 ">
                    <label for="rough_inspection_date" class="form-label">Rough Inspection Date</label>
                    <input class="form-control" type="date" id="rough_inspection_date"
                        name="rough_inspection_date" wire:model="rough_inspection_date">
                    @error('rough_inspection_date')
                        <div id="rough_inspection_date_message" class="text-danger message mt-2">{{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="final_inspection_date" class="form-label">Final Inspection Date</label>
                    <input class="form-control" type="date" id="final_inspection_date"
                        name="final_inspection_date" wire:model="final_inspection_date">
                    @error('final_inspection_date')
                        <div id="final_inspection_date_message" class="text-danger message mt-2">{{ $message }}
                        </div>
                    @enderror
                </div>
                @if ($project->fire_review_required == 1)
                    <div class="col-sm-4 mb-3 ">
                        <label for="final_inspection_date" class="form-label">Fire Inspection Date</label>
                        <input class="form-control" type="date" id="fire_inspection_date"
                            name="fire_inspection_date" wire:model="fire_inspection_date">
                        @error('fire_inspection_date')
                            <div id="fire_inspection_date_message" class="text-danger message mt-2">{{ $message }}
                            </div>
                        @enderror
                    </div>
                @endif
            @endif
            @if ($departmentId == 7)
                <div class="col-sm-4 mb-3 ">
                    <label for="pto_submission_date" class="form-label">PTO Submission Date</label>
                    <input class="form-control" type="date" id="pto_submission_date" name="pto_submission_date"
                        wire:model="pto_submission_date">
                    @error('pto_submission_date')
                        <div id="pto_submission_date_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4 mb-3 ">
                    <label for="pto_approval_date" class="form-label">PTO Approval Date</label>
                    <input class="form-control" type="date" id="pto_approval_date" name="pto_approval_date"
                        wire:model="pto_approval_date">
                    @error('pto_approval_date')
                        <div id="pto_approval_date_message" class="text-danger message mt-2">{{ $message }}</div>
                    @enderror
                </div>
            @endif
            @if ($departmentId == 8)
                <div class="col-sm-4 mb-3 ">
                    <label for="coc_packet_mailed_out_date" class="form-label">COC Packet</label>
                    <input class="form-control" type="date" id="coc_packet_mailed_out_date"
                        name="coc_packet_mailed_out_date" wire:model="coc_packet_mailed_out_date">
                    @error('coc_packet_mailed_out_date')
                        <div id="coc_packet_mailed_out_date_message" class="text-danger message mt-2">{{ $message }}
                        </div>
                    @enderror
                </div>
            @endif
            <div class="row ">
                <div class="col-sm-12 mb-3 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="icofont-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
        <form>
</div>
@script
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let contractorId = '{{ $sub_contractor_id }}';
        let userId = '{{ $sub_contractor_user_id }}';
        console.log(userId,contractorId);
        
        if (contractorId) {
            $('#sub_contractor_id').val(contractorId);
            loadSubContractorUsers(contractorId, userId);
        }
    });

    function loadSubContractorUsers(contractorId, selectedUserId = null) {
        if (!contractorId) {
            $('#sub_contractor_user_id').html('<option value="">Select Sub-Contractor User</option>');
            return;
        }

        $.ajax({
            url: "{{ route('get.subcontractors.users') }}",
            method: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                id: contractorId
            },
            success: function(response) {
                $('#sub_contractor_user_id').html('<option value="">Select Sub-Contractor User</option>');
                $.each(response.users, function(i, user) {
                    $('#sub_contractor_user_id').append('<option value="' + user.id + '">' + user.name + '</option>');
                });
                if (selectedUserId) {
                    $('#sub_contractor_user_id').val(selectedUserId);
                }
            }
        });
    }
</script>
@endscript
