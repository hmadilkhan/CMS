<div class="card mt-3">
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Customer Name</th>
                    <th>Sales Partner Name</th>
                    <th>Sales Person Name</th>
                    <th>Redline Cost</th>
                    <th>Panel Qty</th>
                    <th>Override Base Cost</th>
                    <th>Override Panel Cost</th>
                    <th>Total Override Panel Cost</th>
                    <th>Total Override Cost</th>
                    <th>Actual Redline Cost</th>
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
                        <td>{{ ++$key }}</td>
                        <td>{{ $customer->first_name . ' ' . $customer->last_name }}</td>
                        <td>{{ $customer->salespartner->name }}</td>
                        <td>{{ $customer->project->salesPartnerUser->name }}</td>
                        <td>$ {{ number_format($customer->finances->redline_costs, 2) }}</td>
                        <td>{{ $customer->panel_qty }}</td>
                        <td>$ {{ $customer->project->overwrite_base_price }}</td>
                        <td>$ {{ $customer->project->overwrite_panel_price }}</td>
                        <td>$ {{ $totalOverridePanelCost }}</td>
                        <td>$ {{ $totalOverride }}</td>
                        <td>$ {{ $actualRedlineCost }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="text-center">
                        <h4 class="fw-bold mt-3">Total</h4>
                    </td>
                    <td class="fw-bold">$ {{ number_format($totalRedlineCosts, 2) }}</td>
                    <td class="fw-bold">{{ number_format($totalPanelQty, 0) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalBaseCost, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalSinglePanelCost, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalPanelCost, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalOverrideCost, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalActualRedlineCost, 2) }}</td>

                </tr>
            </tbody>
        </table>
    </div>
</div> <!-- ROW END -->
