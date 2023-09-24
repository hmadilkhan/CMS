<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string("utility_company")->nullable();
            $table->date("ntp_approval_date")->nullable();
            $table->string("site_survey_link")->nullable();
            $table->string("hoa")->nullable();// Dropdown YES / NO
            $table->string("hoa_phone_number")->nullable();
            $table->string("adders_approve_checkbox")->nullable();
            $table->string("mpu_required")->nullable();// Dropdown YES / NO
            $table->string("meter_spot_requestd_date")->nullable();
            $table->string("meter_spot_requestd_number")->nullable();
            $table->string("meter_spot_result")->nullable();// Dropdown YES / NO
            $table->string("permitting_submittion_date")->nullable();
            $table->string("permitting_approval_date")->nullable();
            $table->string("hoa_approval_request_date")->nullable();
            $table->string("hoa_approval_date")->nullable();
            $table->string("solar_install_date")->nullable();
            $table->string("battery_install_date")->nullable();
            $table->string("mpu_install_date")->nullable();
            $table->string("rough_inspection_date")->nullable();
            $table->string("final_inspection_date")->nullable();
            $table->string("pto_submission_date")->nullable();
            $table->string("pto_approval_date")->nullable();
            $table->string("coc_packet_mailed_out_date")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("utility_company");
            $table->dropColumn("utility_company");
            $table->dropColumn("ntp_approval_date");
            $table->dropColumn("site_survey_link");
            $table->dropColumn("hoa");// Dropdown YES / NO
            $table->dropColumn("hoa_phone_number");
            $table->dropColumn("adders_approve_checkbox");
            $table->dropColumn("mpu_required");// Dropdown YES / NO
            $table->dropColumn("meter_spot_requestd_date");
            $table->dropColumn("meter_spot_requestd_number");
            $table->dropColumn("meter_spot_result");// Dropdown YES / NO
            $table->dropColumn("permitting_submittion_date");
            $table->dropColumn("permitting_approval_date");
            $table->dropColumn("hoa_approval_request_date");
            $table->dropColumn("hoa_approval_date");
            $table->dropColumn("solar_install_date");
            $table->dropColumn("battery_install_date");
            $table->dropColumn("mpu_install_date");
            $table->dropColumn("rough_inspection_date");
            $table->dropColumn("final_inspection_date");
            $table->dropColumn("pto_submission_date");
            $table->dropColumn("pto_approval_date");
            $table->dropColumn("coc_packet_mailed_out_date");
        });
    }
};
