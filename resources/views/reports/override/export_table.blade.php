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
        <th colspan="8" style="font-size: 18px;font-weight:bold;">Override Report from (
            {{ date('d M Y', strtotime($from)) }} to {{ date('d M Y', strtotime($to)) }} )</th>
    </tr>
</table>
<table cellpadding="20" style="width:100%">
    <thead>
        <tr>
            <th style="text-align: center; font-weight:bold;">No.</th>
            <th style="text-align: center; font-weight:bold;">Customer Name</th>
            <th style="text-align: center; font-weight:bold;">Sales Partner Name</th>
            <th style="text-align: center; font-weight:bold;">Sales Person Name</th>
            <th style="text-align: center; font-weight:bold;">Redline Cost</th>
            <th style="text-align: center; font-weight:bold;">Panel Qty</th>
            <th style="text-align: center; font-weight:bold;">Override Base Cost</th>
            <th style="text-align: center; font-weight:bold;">Override Panel Cost</th>
            <th style="text-align: center; font-weight:bold;">Total Override Panel Cost</th>
            <th style="text-align: center; font-weight:bold;">Total Override Cost</th>
            <th style="text-align: center; font-weight:bold;">Actual Redline Cost</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $totalCount = 0;
        $totalRedlineCosts = 0;
        $totalOverridePanelCost = 0;
        $totalOverride = 0;
        $actualRedlineCost = 0;
        $totalPanelQty = 0;
        $totalBaseCost = 0;
        $totalSinglePanelCost = 0;
        $totalPanelCost = 0;
        $totalOverrideCost = 0;
        $totalActualRedlineCost = 0;
        
        ?>
        @foreach ($customers as $key => $customer)
            <?php
            $totalCount++;
            $totalRedlineCosts += $customer->finances->redline_costs;
            $totalOverridePanelCost = $customer->panel_qty * $customer->project->overwrite_panel_price;
            $totalOverride = $totalOverridePanelCost + $customer->project->overwrite_base_price;
            $actualRedlineCost = $customer->finances->redline_costs - $totalOverride;
            
            $totalPanelQty += $customer->panel_qty;
            $totalBaseCost += $customer->project->overwrite_base_price;
            $totalSinglePanelCost += $customer->project->overwrite_panel_price;
            $totalPanelCost += $totalOverridePanelCost;
            $totalOverrideCost += $totalOverride;
            $totalActualRedlineCost += $actualRedlineCost;
            
            ?>
            <tr>
                <td style="text-align: center;padding: 15px;">{{ ++$key }}</td>
                <td style="text-align: center;padding: 15px;">{{ $customer->first_name . ' ' . $customer->last_name }}</td>
                <td style="text-align: center;padding: 15px;">{{ $customer->salespartner->name }}</td>
                <td style="text-align: center;padding: 15px;">{{ $customer->project->salesPartnerUser->name }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ number_format($customer->finances->redline_costs, 2) }}</td>
                <td style="text-align: center;padding: 15px;">{{ $customer->panel_qty }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ $customer->project->overwrite_base_price }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ $customer->project->overwrite_panel_price }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ $totalOverridePanelCost }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ $totalOverride }}</td>
                <td style="text-align: center;padding: 15px;">$ {{ $actualRedlineCost }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="text-align: center;font-weight:bold;" colspan="4">
                <h4 style="font-weight:bold;">Total</h4>
            </td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalRedlineCosts, 2) }}</td>
            <td style="text-align: center;font-weight: bold">{{ number_format($totalPanelQty, 0) }}</td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalBaseCost, 2) }}</td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalSinglePanelCost, 2) }}</td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalPanelCost, 2) }}</td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalOverrideCost, 2) }}</td>
            <td style="text-align: center;font-weight: bold">$ {{ number_format($totalActualRedlineCost, 2) }}</td>

        </tr>
    </tbody>
</table>
