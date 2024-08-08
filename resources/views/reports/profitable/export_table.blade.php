<style>
    table,
    th,
    td {
        border: 1px solid black;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 15px;
    }
</style>
<table cellpadding="20">
    <tr>
        <th colspan="8" style="font-size: 18px;font-weight:bold;">Profitable Report from (
            {{ date('d M Y', strtotime($from)) }} to {{ date('d M Y', strtotime($to)) }} )</th>
    </tr>
</table>
<table cellpadding="20" style="width:100%">
    <tr>
        <th style="text-align: center; font-weight:bold;">No.</th>
        <th style="text-align: center; font-weight:bold;">Customer Name</th>
        <th style="text-align: center; font-weight:bold;">Sales Partner Name</th>
        <th style="text-align: center; font-weight:bold;">Contract amount</th>
        <th style="text-align: center; font-weight:bold;">Dealer Fee</th>
        <th style="text-align: center; font-weight:bold;">Redline Cost</th>
        <th style="text-align: center; font-weight:bold;">Adders</th>
        <th style="text-align: center; font-weight:bold;">Commission</th>
        <th style="text-align: center; font-weight:bold;">Actual Material Cost</th>
        <th style="text-align: center; font-weight:bold;">Actual Labor Cost</th>
        <th style="text-align: center; font-weight:bold;">Actual Job Cost</th>
        <th style="text-align: center; font-weight:bold;">Profit</th>
        <th style="text-align: center; font-weight:bold;">Profit %</th>
    </tr>
    <?php
    $totalContractAmount = 0;
    $totalDealerFee = 0;
    $totalRedlineCosts = 0;
    $totalAddersAmount = 0;
    $totalCommissionAmount = 0;
    $totalProfitAmount = 0;
    $totalProfitPercentage = 0;
    $totalActualJob = 0;
    $totalActualMaterialCost = 0;
    $totalActualLaborCost = 0;
    ?>
    @foreach ($customers as $key => $customer)
        <?php
        $totalContractAmount += $customer->finances->contract_amount;
        $totalDealerFee += $customer->finances->dealer_fee_amount;
        $totalRedlineCosts += $customer->finances->redline_costs;
        $totalAddersAmount += $customer->finances->adders;
        $totalCommissionAmount += $customer->finances->commission;
        $actualJob = $customer->project->actual_permit_fee + $customer->project->actual_labor_cost + $customer->project->actual_material_cost + $customer->project->office_cost;
        $totalActualJob += $actualJob;
        $totalActualMaterialCost += $customer->project->actual_material_cost;
        $totalActualLaborCost += $customer->project->actual_labor_cost;
        $profitAmount = $customer->finances->redline_costs + ($customer->finances->adders - $actualJob);
        $profitPercentage = $profitAmount / ($customer->finances->redline_costs + $customer->finances->adders);
        $totalProfitAmount += $profitAmount;
        $totalProfitPercentage += $profitPercentage;
        ?>
        <tr>
            <td style="text-align: center;padding: 15px;">{{ ++$key }}</td>
            <td style="text-align: center;padding: 15px;">{{ $customer->first_name . ' ' . $customer->last_name }}</td>
            <td style="text-align: center;padding: 15px;">{{ $customer->salespartner->name }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->contract_amount, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->dealer_fee_amount, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->redline_costs, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->adders, 2) }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->commission, 2) }}</td>
            <td style="text-align: center;padding: 15px;">
                {{ number_format($customer->project->actual_material_cost, 2) }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->project->actual_labor_cost, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($actualJob, 2) }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($profitAmount, 2) }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($profitPercentage * 100, 2) }}%</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="3" class="text-center">
            <h4 class="fw-bold mt-3">Total</h4>
        </td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalContractAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalDealerFee, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalRedlineCosts, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalAddersAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalCommissionAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalActualMaterialCost, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalActualLaborCost, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalActualJob, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalProfitAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalProfitPercentage * 100, 2) }}%</td>
    </tr>
</table>
