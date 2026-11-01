<?php

namespace App\Livewire\Concerns;

use App\Models\Department;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\SubDepartment;
use App\Models\Zone;

/**
 * What a report field is: the input its filter needs, the options that input
 * offers, and the column alias it is selected under. Both report screens ask
 * this trait, so they cannot disagree.
 *
 * The id columns matter most here: customer_finances stores the finance option,
 * loan term and APR as ids, so without a dropdown the filter asked the user to
 * type "17" for a plan they know as "Cash". Every id column that has a name
 * somewhere is therefore a dropdown - it shows the name and files the id.
 */
trait DescribesReportFields
{
    public function getFieldType($field)
    {
        $fieldTypes = [
            // Date fields
            'customers.created_at' => 'date',
            'customers.sold_date' => 'date',
            'projects.start_date' => 'date',
            'projects.end_date' => 'date',
            'projects.completion_date' => 'date',
            'projects.created_at' => 'date',
            'projects.updated_at' => 'date',
            'projects.ntp_approval_date' => 'date',
            'projects.meter_spot_request_date' => 'date',
            'projects.inspection_approval_date' => 'date',
            'projects.zone_entered_at' => 'date',
            'projects.permitting_submittion_date' => 'date',
            'projects.permitting_approval_date' => 'date',
            'projects.hoa_approval_request_date' => 'date',
            'projects.hoa_approval_date' => 'date',
            'projects.solar_install_date' => 'date',
            'projects.battery_install_date' => 'date',
            'projects.mpu_install_date' => 'date',
            'projects.rough_inspection_date' => 'date',
            'projects.final_inspection_date' => 'date',
            'projects.pto_submission_date' => 'date',
            'projects.pto_approval_date' => 'date',
            'projects.coc_packet_mailed_out_date' => 'date',
            'projects.fire_inspection_date' => 'date',
            'customer_finances.created_at' => 'date',
            'customer_finances.updated_at' => 'date',

            // Number fields
            'customers.panel_qty' => 'number',
            'customers.inverter_qty' => 'number',
            'projects.budget' => 'number',
            'customers.sold_production_value' => 'number',
            'projects.production_value_achieved' => 'number',
            'module_types.value' => 'number',
            'inverter_types.inverter_efficiency_rating' => 'number',
            'customer_finances.contract_amount' => 'number',
            'customer_finances.redline_costs' => 'number',
            'customer_finances.adders' => 'number',
            'customer_finances.commission' => 'number',
            'customer_finances.dealer_fee_amount' => 'number',
            'customer_finances.third_party_credit' => 'number',
            'customer_finances.customer_portion' => 'number',
            'customer_finances.holdback_amount' => 'number',
            'customer_finances.module_type_cost' => 'number',
            'customer_finances.inverter_base_cost' => 'number',
            'customer_finances.total_overwrite_base_price' => 'number',
            'customer_finances.total_overwrite_panel_price' => 'number',
            'projects.actual_material_cost' => 'number',
            'projects.actual_labor_cost' => 'number',
            'projects.actual_permit_fee' => 'number',
            'projects.office_cost' => 'number',
            'loan_terms.year' => 'number',
            'loan_aprs.apr' => 'number',

            // Dropdown fields
            'departments.name' => 'dropdown',
            'projects.department_id' => 'dropdown',
            'projects.sub_department_id' => 'dropdown',
            'sub_departments.name' => 'dropdown',
            'sales_partners.name' => 'dropdown',
            'sub_contractors.name' => 'dropdown',
            'zones.name' => 'dropdown',
            'module_types.name' => 'dropdown',
            'inverter_types.name' => 'dropdown',
            'finance_options.name' => 'dropdown',
            'customer_finances.finance_option_id' => 'dropdown',
            'customer_finances.loan_term_id' => 'dropdown',
            'customer_finances.loan_apr_id' => 'dropdown',
        ];

        return $fieldTypes[$field] ?? 'text';
    }

    /**
     * Options for a dropdown filter, as value => label. An id column files the
     * id; a name column files the name, because that is what it is compared to.
     *
     * @return array<int|string, string>
     */
    public function getDropdownOptions($field)
    {
        return match ($field) {
            'projects.department_id' => Department::pluck('name', 'id')->toArray(),
            'departments.name' => Department::pluck('name', 'name')->toArray(),
            'projects.sub_department_id' => SubDepartment::orderBy('name')->pluck('name', 'id')->toArray(),
            'sub_departments.name' => SubDepartment::orderBy('name')->pluck('name', 'name')->toArray(),
            'sales_partners.name' => SalesPartner::orderBy('name')->pluck('name', 'name')->toArray(),
            'sub_contractors.name' => SubContractor::orderBy('name')->pluck('name', 'name')->toArray(),
            'zones.name' => Zone::orderBy('order')->pluck('name', 'name')->toArray(),
            'module_types.name' => ModuleType::orderBy('name')->pluck('name', 'name')->toArray(),
            'inverter_types.name' => InverterType::orderBy('name')->pluck('name', 'name')->toArray(),
            'finance_options.name' => FinanceOption::orderBy('name')->pluck('name', 'name')->toArray(),
            'customer_finances.finance_option_id' => FinanceOption::orderBy('name')->pluck('name', 'id')->toArray(),
            'customer_finances.loan_term_id' => LoanTerm::orderBy('year')->get()
                ->mapWithKeys(fn ($term) => [$term->id => $term->year.' years'])->toArray(),
            'customer_finances.loan_apr_id' => LoanApr::orderBy('apr')->get()
                ->mapWithKeys(fn ($apr) => [$apr->id => $apr->apr.'%'])->toArray(),
            default => [],
        };
    }

    /**
     * Fields whose column name would collide with another table's. The select
     * aliases every field to its column name, so departments.name and
     * finance_options.name would both land in "name" and the second would win.
     */
    private const FIELD_ALIASES = [
        'customer_finances.adders' => 'adders_amount',
        // Same-named columns on several tables. The customer's (and the sales
        // partner's name) keeps the bare name - calculated fields and saved
        // reports already read it that way - the others get a prefixed alias, so
        // picking two of them no longer lets the second overwrite the first.
        'projects.id' => 'project_id',
        'customer_finances.id' => 'customer_finance_id',
        'customer_finances.customer_id' => 'customer_finance_customer_id',
        'projects.created_at' => 'project_created_at',
        'customer_finances.created_at' => 'customer_finance_created_at',
        'projects.updated_at' => 'project_updated_at',
        'customer_finances.updated_at' => 'customer_finance_updated_at',
        'departments.name' => 'department_name',
        'sub_departments.name' => 'sub_department_name',
        'module_types.name' => 'module_type_name',
        'inverter_types.name' => 'inverter_type_name',
        'sales_partner_users.name' => 'sales_partner_user_name',
        'sales_partners.email' => 'sales_partner_email',
        'sales_partners.phone' => 'sales_partner_phone',
        'sub_contractors.name' => 'sub_contractor_name',
        'sub_contractors.email' => 'sub_contractor_email',
        'sub_contractors.phone' => 'sub_contractor_phone',
        'zones.name' => 'zone_name',
        'module_types.value' => 'module_wattage',
        'sub_contractor_users.name' => 'sub_contractor_user_name',
        'finance_options.name' => 'finance_option_name',
        'loan_terms.year' => 'loan_term_years',
        'loan_aprs.apr' => 'loan_apr',
    ];

    /** The column name a field is selected - and read back - under. */
    protected function fieldAlias(string $field): string
    {
        return self::FIELD_ALIASES[$field]
            ?? (str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field);
    }

    /**
     * True while a saved filter's value should be picked from the list rather
     * than typed. Several picks are allowed, so it covers IN / NOT IN too.
     *
     * @param  array{field?: string, operator?: string}  $filter
     */
    public function filterUsesPicker(array $filter): bool
    {
        return $this->getFieldType($filter['field'] ?? '') === 'dropdown'
            && in_array($filter['operator'] ?? '', ['=', '!=', 'IN', 'NOT IN'], true);
    }

    /** The label a dropdown value is shown with, for the chip and the header. */
    protected function dropdownLabel(string $field, $value): ?string
    {
        if ($this->getFieldType($field) !== 'dropdown' || $value === null || $value === '') {
            return null;
        }

        return $this->getDropdownOptions($field)[$value] ?? null;
    }
}
