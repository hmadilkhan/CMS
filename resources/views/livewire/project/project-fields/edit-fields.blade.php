<div class="row">
    @if($departmentId == 1)
    <div class="col-sm-3 mb-3 ">
        <label for="utility_company" class="form-label" id="requiredfiles">Utility Company</label>
        <input class="form-control" type="text" id="utility_company" name="utility_company" value="{{$project->utility_company}}">
        @error("utility_company")
        <div id="utility_company_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="utility_company_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="ntp_approval_date" class="form-label" id="requiredfiles">NTP Approval Date</label>
        <input class="form-control" type="date" id="ntp_approval_date" name="ntp_approval_date" value="{{$project->ntp_approval_date}}">
        @error("ntp_approval_date")
        <div id="ntp_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="ntp_approval_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 ">
        <label for="hoa" class="form-label">HOA</label>
        <select class="form-select select2" aria-label="Default select HOA" id="hoa" name="hoa">
            <option value="">Select HOA</option>
            <option {{$project->hoa != "" && $project->hoa == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
            <option {{$project->hoa != "" && $project->hoa == 'no' ? 'selected' : '' }} value="no">No</option>
        </select>
        @error("hoa")
        <div class="text-danger message mt-2">{{$message}}</div>
        @enderror
    </div>
    <div class="col-sm-3 mb-3" id="hoa_select" style="display:none;">
        <label for="hoa_phone_number" class="form-label" id="requiredfiles">Phone Number Field</label>
        <input class="form-control" type="text" id="hoa_phone_number" name="hoa_phone_number" value="{{$project->hoa_phone_number}}">
        @error("hoa_phone_number")
        <div id="hoa_phone_number_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="hoa_phone_number_message" class="text-danger message mt-2"></div>
    </div>
    @endif
    @if($departmentId == 2)
    <div class="col-sm-3 mb-3 ">
        <label for="site_survey_link" class="form-label" id="requiredfiles">Site Survey Link</label>
        <input class="form-control" type="text" id="site_survey_link" name="site_survey_link" value="{{$project->site_survey_link}}">
        @error("site_survey_link")
        <div id="site_survey_link_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="site_survey_link_message" class="text-danger message mt-2"></div>
    </div>
   
    @endif
    @if($departmentId == 3)
    <div class="col-sm-3 ">
        <label for="hoa" class="form-label">Adders Approved</label>
        <select class="form-select select2" aria-label="Default select Adders Approved" id="adders_approve_checkbox" name="adders_approve_checkbox">
            <option value="">Select Adders Approved</option>
            <option {{$project->adders_approve_checkbox != "" && $project->adders_approve_checkbox == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
            <option {{$project->adders_approve_checkbox != "" && $project->adders_approve_checkbox == 'no' ? 'selected' : '' }} value="no">No</option>
        </select>
        @error("adders_approve_checkbox")
        <div class="text-danger message mt-2">{{$message}}</div>
        @enderror
    </div>
    <div class="col-sm-3 ">
        <label for="mpu_required" class="form-label">MPU Required</label>
        <select class="form-select select2" aria-label="Default select MPU Required" id="mpu_required" name="mpu_required">
            <option value="">Select MPU Required</option>
            <option {{$project->mpu_required != "" && $project->mpu_required == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
            <option {{$project->mpu_required != "" && $project->mpu_required == 'no' ? 'selected' : '' }} value="no">No</option>
        </select>
        @error("mpu_required")
        <div class="text-danger message mt-2">{{$message}}</div>
        @enderror
    </div>
    <div class="col-sm-3 mb-3 mpuselect" style="display:none;">
        <label for="meter_spot_request_date" class="form-label" id="requiredfiles">Meter Spot Request Date</label>
        <input class="form-control" type="date" id="meter_spot_request_date" name="meter_spot_request_date" value="{{$project->meter_spot_request_date}}">
        @error("meter_spot_request_date")
        <div id="meter_spot_request_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="meter_spot_request_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 mpuselect" style="display:none;">
        <label for="meter_spot_request_number" class="form-label" id="requiredfiles">Meter Spot Request Number</label>
        <input class="form-control" type="text" id="meter_spot_request_number" name="meter_spot_request_number" value="{{$project->meter_spot_request_number}}">
        @error("meter_spot_request_number")
        <div id="meter_spot_request_number_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="meter_spot_request_number_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 ">
        <label for="meter_spot_result" class="form-label">Meter Spot Result</label>
        <select class="form-select select2" aria-label="Default select Meter Spot Result" id="meter_spot_result" name="meter_spot_result">
            <option value="">Select Meter Spot Result</option>
            <option {{$project->meter_spot_result != "" && $project->meter_spot_result == 'same' ? 'selected' : '' }} value="same">Same Location</option>
            <option {{$project->meter_spot_result != "" && $project->meter_spot_result == 'relocation' ? 'selected' : '' }} value="relocation">Relocation</option>
        </select>
        @error("meter_spot_result")
        <div class="text-danger message mt-2">{{$message}}</div>
        @enderror
    </div>
    @endif
    @if($departmentId == 4)
    <div class="col-sm-3 mb-3 ">
        <label for="permitting_submittion_date" class="form-label">Permit Submission Date</label>
        <input class="form-control" type="date" id="permitting_submittion_date" name="permitting_submittion_date" value="{{$project->permitting_submittion_date}}">
        @error("permitting_submittion_date")
        <div id="permitting_submittion_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="permitting_submittion_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="actual_permit_fee" class="form-label">Actual Permit Fee</label>
        <input class="form-control" type="text" id="actual_permit_fee" name="actual_permit_fee" value="{{$project->actual_permit_fee}}">
        @error("actual_permit_fee")
        <div id="actual_permit_fee_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="actual_permit_fee_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="permitting_approval_date" class="form-label">Permit Approval Date</label>
        <input class="form-control" type="date" id="permitting_approval_date" name="permitting_approval_date" value="{{$project->permitting_approval_date}}">
        @error("permitting_approval_date")
        <div id="permitting_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="permitting_approval_date_message" class="text-danger message mt-2"></div>
    </div>
    <input type="hidden" name="projecthoa" value="{{$project->hoa}}" />
    @if($project->hoa == "yes")
    <div class="col-sm-3 mb-3 ">
        <label for="hoa_approval_request_date" class="form-label">HOA Approval Request Date</label>
        <input class="form-control" type="date" id="hoa_approval_request_date" name="hoa_approval_request_date" value="{{$project->hoa_approval_request_date}}">
        @error("hoa_approval_request_date")
        <div id="hoa_approval_request_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="hoa_approval_request_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="hoa_approval_date" class="form-label">HOA Approval Date</label>
        <input class="form-control" type="date" id="hoa_approval_date" name="hoa_approval_date" value="{{$project->hoa_approval_date}}">
        @error("hoa_approval_date")
        <div id="hoa_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="hoa_approval_date_message" class="text-danger message mt-2"></div>
    </div>
    @endif
    @endif
    @if($departmentId == 5)
    <div class="col-sm-3 mb-3 ">
        <label for="solar_install_date" class="form-label">Solar Install Date </label>
        <input class="form-control" type="date" id="solar_install_date" name="solar_install_date" value="{{$project->solar_install_date}}">
        @error("solar_install_date")
        <div id="solar_install_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="solar_install_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="battery_install_date" class="form-label">Battery Install Date</label>
        <input class="form-control" type="date" id="battery_install_date" name="battery_install_date" value="{{$project->battery_install_date}}">
        @error("battery_install_date")
        <div id="battery_install_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="battery_install_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="actual_labor_cost" class="form-label">Actual Labor Cost</label>
        <input class="form-control" type="text" id="actual_labor_cost" name="actual_labor_cost" value="{{$project->actual_labor_cost}}">
        @error("actual_labor_cost")
        <div id="actual_labor_cost_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="actual_labor_cost_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="actual_material_cost" class="form-label">Actual Material Cost</label>
        <input class="form-control" type="text" id="actual_material_cost" name="actual_material_cost" value="{{$project->actual_material_cost}}">
        @error("actual_material_cost")
        <div id="actual_material_cost_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="actual_material_cost_message" class="text-danger message mt-2"></div>
    </div>
    <input type="hidden" name="projectmpu" value="{{$project->mpu_required}}" />
    @if($project->mpu_required == "yes")
    <div class="col-sm-3 mb-3 ">
        <label for="mpu_install_date" class="form-label">MPU Install Date</label>
        <input class="form-control" type="date" id="mpu_install_date" name="mpu_install_date" value="{{$project->mpu_install_date}}">
        @error("mpu_install_date")
        <div id="mpu_install_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="mpu_install_date_message" class="text-danger message mt-2"></div>
    </div>
    @endif
    @endif
    @if($departmentId == 6)
    <div class="col-sm-3 mb-3 ">
        <label for="rough_inspection_date" class="form-label">Rough Inspection Date</label>
        <input class="form-control" type="date" id="rough_inspection_date" name="rough_inspection_date" value="{{$project->rough_inspection_date}}">
        @error("rough_inspection_date")
        <div id="rough_inspection_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="rough_inspection_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="final_inspection_date" class="form-label">Final Inspection Date</label>
        <input class="form-control" type="date" id="final_inspection_date" name="final_inspection_date" value="{{$project->final_inspection_date}}">
        @error("final_inspection_date")
        <div id="final_inspection_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="final_inspection_date_message" class="text-danger message mt-2"></div>
    </div>
    @endif
    @if($departmentId == 7)
    <div class="col-sm-3 mb-3 ">
        <label for="pto_submission_date" class="form-label">PTO Submission Date</label>
        <input class="form-control" type="date" id="pto_submission_date" name="pto_submission_date" value="{{$project->pto_submission_date}}">
        @error("pto_submission_date")
        <div id="pto_submission_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="pto_submission_date_message" class="text-danger message mt-2"></div>
    </div>
    <div class="col-sm-3 mb-3 ">
        <label for="pto_approval_date" class="form-label">PTO Approval Date</label>
        <input class="form-control" type="date" id="pto_approval_date" name="pto_approval_date" value="{{$project->pto_approval_date}}">
        @error("pto_approval_date")
        <div id="pto_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="pto_approval_date_message" class="text-danger message mt-2"></div>
    </div>
    @endif
    @if($departmentId == 8)
    <div class="col-sm-3 mb-3 ">
        <label for="coc_packet_mailed_out_date" class="form-label">COC Packet</label>
        <input class="form-control" type="text" id="coc_packet_mailed_out_date" name="coc_packet_mailed_out_date" value="{{$project->coc_packet_mailed_out_date}}">
        @error("coc_packet_mailed_out_date")
        <div id="coc_packet_mailed_out_date_message" class="text-danger message mt-2">{{$message}}</div>
        @enderror
        <div id="coc_packet_mailed_out_date_message" class="text-danger message mt-2"></div>
    </div>
    @endif
</div>

<script>
    $('.select2').select2();
    $("#hoa").change(function() {
        if ($(this).val() == "yes") {
            $("#hoa_select").css("display", "block")
        } else {
            $("#hoa_select").css("display", "none")
        }
    })
    $("#mpu_required").change(function() {
        if ($(this).val() == "yes") {
            $(".mpuselect").css("display", "block")
        } else {
            $(".mpuselect").css("display", "none")
        }
    })
</script>