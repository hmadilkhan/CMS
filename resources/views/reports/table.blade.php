<div class="card mt-3">
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Customer Name</th>
                    <th>Sales Partner Name</th>
                    <th>Contract amount</th>
                    <th>Dealer Fee</th>
                    <th>Redline Cost</th>
                    <th>Adders</th>
                    <th>Commission</th>
                    <th>Actual Job Cost</th>
                    <th>Profit</th>
                    <th>Profit %</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalContractAmount = 0;
                $totalDealerFee = 0;
                $totalRedlineCosts = 0;
                $totalAddersAmount = 0;
                $totalCommissionAmount = 0;
                $totalProfitAmount = 0;
                $totalProfitPercentage = 0;
                $totalActualJob = 0;
                ?>
                @foreach ($customers as $key => $customer)
                <?php 
                $totalContractAmount += $customer->finances->contract_amount;
                $totalDealerFee += $customer->finances->dealer_fee_amount;
                $totalRedlineCosts += $customer->finances->redline_costs;
                $totalAddersAmount += $customer->finances->adders;
                $totalCommissionAmount += $customer->finances->commission;
                $actualJob = $customer->project->actual_permit_fee + $customer->project->actual_labor_cost + $customer->project->actual_material_cost + $officeCost->cost;
                $totalActualJob += $actualJob;
                $profitAmount = $customer->finances->redline_costs - $actualJob;
                $profitPercentage = $profitAmount / $customer->finances->redline_costs;
                $totalProfitAmount += $profitAmount;
                $totalProfitPercentage += $profitPercentage;
                ?>
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{$customer->first_name." ".$customer->last_name }}</td>
                    <td>{{$customer->salespartner->name }}</td>
                    <td>{{number_format($customer->finances->contract_amount,2) }}</td>
                    <td>{{number_format($customer->finances->dealer_fee_amount,2) }}</td>
                    <td>{{number_format($customer->finances->redline_costs,2) }}</td>
                    <td>{{number_format($customer->finances->adders,2) }}</td>
                    <td>{{number_format($customer->finances->commission,2) }}</td>
                    <td>{{number_format($actualJob,2) }}</td>
                    <td>{{number_format($profitAmount,2) }}</td>
                    <td>{{number_format($profitPercentage,2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-center"><h4 class="fw-bold mt-3">Total</h4></td>
                    <td class="fw-bold">{{number_format($totalContractAmount,2)}}</td>
                    <td class="fw-bold">{{number_format($totalDealerFee,2)}}</td>
                    <td class="fw-bold">{{number_format($totalRedlineCosts,2)}}</td>
                    <td class="fw-bold">{{number_format($totalAddersAmount,2)}}</td>
                    <td class="fw-bold">{{number_format($totalCommissionAmount,2)}}</td> 
                    <td class="fw-bold">{{number_format($totalActualJob,2)}}</td> 
                    <td class="fw-bold">{{number_format($totalRedlineCosts - $totalActualJob ,2)}}</td> 
                    <td class="fw-bold">{{number_format((($totalRedlineCosts - $totalActualJob) / $totalRedlineCosts),2)}}%</td> 
                </tr>
            </tbody>
        </table>
        <table class="table table-bordered table-striped datatable">
            <tbody>
                
            </tbody>
        </table>
    </div>
</div> <!-- ROW END -->