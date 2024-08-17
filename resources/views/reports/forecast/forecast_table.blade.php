<div class="card mt-3">
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Sold Date</th>
                    <th>Customer Name</th>
                    <th>Sales Partner Name</th>
                    <th>Contract amount</th>
                    <th>Commission</th>
                    <th>Dealer Fee</th>
                    <th>Net Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $netAmount = 0;
                $totalContractAmount = 0;
                $totalDealerFee = 0;
                $totalCommissionAmount = 0;
                $totalNetAmount = 0;
                ?>
                @foreach ($customers as $key => $customer)
                <?php
                $netAmount = $customer->finances->contract_amount - $customer->finances->commission - $customer->finances->dealer_fee_amount;
                $totalContractAmount += $customer->finances->contract_amount;
                $totalDealerFee += $customer->finances->dealer_fee_amount;
                $totalCommissionAmount += $customer->finances->commission;
                $totalNetAmount += $netAmount;
                
                ?>
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{date("d M Y",strtotime($customer->sold_date)) }}</td>
                    <td>{{$customer->first_name." ".$customer->last_name }}</td>
                    <td>{{$customer->salespartner->name }}</td>
                    <td>$ {{number_format($customer->finances->contract_amount,2) }}</td>
                    <td>$ {{number_format($customer->finances->commission,2) }}</td>
                    <td>$ {{number_format($customer->finances->dealer_fee_amount,2) }}</td>
                    <td>$ {{number_format($netAmount,2)}}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="text-center"><h4 class="fw-bold mt-3">Total</h4></td>
                    <td class="fw-bold">$ {{number_format($totalContractAmount,2)}}</td>
                    <td class="fw-bold">$ {{number_format($totalCommissionAmount,2)}}</td> 
                    <td class="fw-bold">$ {{number_format($totalDealerFee,2)}}</td>
                    <td class="fw-bold">$ {{number_format($totalNetAmount ,2)}}</td> 
                </tr>
            </tbody>
        </table>
    </div>
</div> <!-- ROW END -->