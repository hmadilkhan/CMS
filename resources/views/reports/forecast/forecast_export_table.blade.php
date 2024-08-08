<style>
    table, th, td {
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
        <th colspan="8" style="font-size: 18px;font-weight:bold;">Forecast Report from  ( {{date("d M Y",strtotime($from))}} to {{date("d M Y",strtotime($to))}} )</th>
    </tr>
</table>
<table cellpadding="20"  style="width:100%">
    <tr>
        <th style="text-align: center; font-weight:bold;">No.</th>
        <th style="text-align: center; font-weight:bold;">Sold Date</th>
        <th style="text-align: center; font-weight:bold;">Customer Name</th>
        <th style="text-align: center; font-weight:bold;">Sales Partner Name</th>
        <th style="text-align: center; font-weight:bold;">Contract Amount</th>
        <th style="text-align: center; font-weight:bold;">Commission</th>
        <th style="text-align: center; font-weight:bold;">Dealer Fee</th>
        <th style="text-align: center; font-weight:bold;">Net Sales</th>
    </tr>

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
            <td style="text-align: center;padding: 15px;">{{ ++$key }}</td>
            <td style="text-align: center;padding: 15px;">{{ $customer->sold_date }}</td>
            <td style="text-align: center;padding: 15px;">{{ $customer->first_name . ' ' . $customer->last_name }}</td>
            <td style="text-align: left;padding: 15px;">{{ $customer->salespartner->name }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->contract_amount, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->commission, 2) }}</td>
            <td style="text-align: center;padding: 15px;">{{ number_format($customer->finances->dealer_fee_amount, 2) }}
            </td>
            <td style="text-align: center;padding: 15px;">{{ number_format($netAmount, 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="4" style="text-align: center;font-weight: bold">
            <h4 style="font-weight: bold">Total</h4>
        </td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalContractAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalCommissionAmount, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalDealerFee, 2) }}</td>
        <td style="text-align: center;font-weight: bold">{{ number_format($totalNetAmount, 2) }}</td>
    </tr>
</table>
