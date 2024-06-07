<div class="row">
    @if($department->id == 1)
    <div class="col-sm-6 mb-3">
        <label for="utility_company" class="form-label">Utility Company</label>
        <input disabled class="form-control" type="text" value="{{$project->utility_company}}">
    </div>
    <div class="col-sm-6 mb-3">
        <label for="ntp_approval_date" class="form-label">NTP Approval Date</label>
        <input disabled class="form-control" type="date" value="{{$project->ntp_approval_date}}">
    </div>
    @endif
    @if($department->id == 2)
    <div class="col-sm-6 mb-3">
        <label for="site_survey_link" class="form-label">Site Survey Link</label>
        <input disabled class="form-control" type="text" value="{{$project->site_survey_link}}">
    </div>
    <div class="col-sm-6 ">
        <label for="hoa" class="form-label">HOA</label>
        <input disabled class="form-control" type="text" value="{{$project->hoa}}">
    </div>
    @if($project->hoa == "yes")
    <div class="col-sm-6 mb-3" id="hoa_select">
        <label for="hoa_phone_number" class="form-label">Phone Number Field</label>
        <input disabled class="form-control" type="text" value="{{$project->hoa_phone_number}}">
    </div>
    @endif
    @endif
    @if($department->id == 3)
    <div class="col-sm-6 ">
        <label for="hoa" class="form-label">Adders Approved</label>
        <input disabled class="form-control" type="text" value="{{$project->adders_approve_checkbox}}">
    </div>
    <div class="col-sm-6 ">
        <label for="mpu_required" class="form-label">MPU Required</label>
        <input disabled class="form-control" type="text" value="{{$project->mpu_required}}">
    </div>
    @if($project->mpu_required == "yes")
    <div class="col-sm-6 mb-3 mpuselect">
        <label for="meter_spot_request_date" class="form-label">Meter Spot Request Date</label>
        <input disabled class="form-control" type="date" value="{{$project->meter_spot_request_date}}">
    </div>
    <div class="col-sm-6 mb-3 mpuselect">
        <label for="meter_spot_request_number" class="form-label">Meter Spot Request Number</label>
        <input disabled class="form-control" type="text" value="{{$project->meter_spot_request_number}}">
    </div>
    <div class="col-sm-6 ">
        <label for="meter_spot_result" class="form-label">Meter Spot Result</label>
        <input disabled class="form-control" type="text" value="{{$project->meter_spot_result}}">
    </div>
    @endif
    @endif
    @if($department->id == 4)
    <div class="col-sm-6 mb-3 ">
        <label for="permitting_submittion_date" class="form-label">Permit Submission Date</label>
        <input disabled class="form-control" type="date" value="{{$project->permitting_submittion_date}}">
    </div>
    @can('Actual Permit Fee')
    <div class="col-sm-6 mb-3 ">
        <label for="actual_permit_fee" class="form-label">Actual Permit Fee</label>
        <input disabled class="form-control" type="text" value="{{$project->actual_permit_fee}}">
    </div>
    @endcan
    <div class="col-sm-6 mb-3 ">
        <label for="permitting_approval_date" class="form-label">Permit Approval Date</label>
        <input disabled class="form-control" type="date" value="{{$project->permitting_approval_date}}">
    </div>
    @if($project->hoa == "yes")
    <div class="col-sm-6 mb-3 ">
        <label for="hoa_approval_request_date" class="form-label">HOA Approval Request Date</label>
        <input disabled class="form-control" type="date" value="{{$project->hoa_approval_request_date}}">
    </div>
    <div class="col-sm-6 mb-3 ">
        <label for="hoa_approval_date" class="form-label">HOA Approval Date</label>
        <input disabled class="form-control" type="date" value="{{$project->hoa_approval_date}}">
    </div>
    @endif
    @endif
    @if($department->id == 5)
    @can('Actual Labor Cost')
    <div class="col-sm-6 mb-3 ">
        <label for="actual_labor_cost" class="form-label">Actual Labor Cost</label>
        <input disabled class="form-control" type="text" value="{{$project->actual_labor_cost}}">
    </div>
    @endcan
    @can('Actual Material Cost')
    <div class="col-sm-6 mb-3 ">
        <label for="actual_material_cost" class="form-label">Actual Material Cost</label>
        <input disabled class="form-control" type="text" value="{{$project->actual_material_cost}}">
    </div>
    @endcan
    <div class="col-sm-6 mb-3 ">
        <label for="solar_install_date" class="form-label">Solar Install Date </label>
        <input disabled class="form-control" type="date" value="{{$project->solar_install_date}}">
    </div>
    <div class="col-sm-6 mb-3 ">
        <label for="battery_install_date" class="form-label">Battery Install Date</label>
        <input disabled class="form-control" type="date" value="{{$project->battery_install_date}}">
    </div>
    <input disabled type="hidden" name="projectmpu" value="{{$project->mpu_required}}" />
    @if($project->mpu_required == "yes")
    <div class="col-sm-6 mb-3 ">
        <label for="mpu_install_date" class="form-label">MPU Install Date</label>
        <input disabled class="form-control" type="date" value="{{$project->mpu_install_date}}">
    </div>
    @endif
    @endif
    @if($department->id == 6)
    <div class="col-sm-6 mb-3 ">
        <label for="rough_inspection_date" class="form-label">Rough Inspection Date</label>
        <input disabled class="form-control" type="date" value="{{$project->rough_inspection_date}}">
    </div>
    <div class="col-sm-6 mb-3 ">
        <label for="final_inspection_date" class="form-label">Final Inspection Date</label>
        <input disabled class="form-control" type="date"  value="{{$project->final_inspection_date}}">
    </div>
    @endif
    @if($department->id == 7)
    <div class="col-sm-6 mb-3 ">
        <label for="pto_submission_date" class="form-label">PTO Submission Date</label>
        <input disabled class="form-control" type="date" value="{{$project->pto_submission_date}}">
    </div>
    <div class="col-sm-6 mb-3 ">
        <label for="pto_approval_date" class="form-label">PTO Approval Date</label>
        <input disabled class="form-control" type="date" value="{{$project->pto_approval_date}}">
    </div>
    @endif
    @if($department->id == 8)
    <div class="col-sm-6 mb-3 ">
        <label for="coc_packet_mailed_out_date" class="form-label">COC Packet</label>
        <input disabled class="form-control" type="text" value="{{$project->coc_packet_mailed_out_date}}">
    </div>
    @endif
</div>