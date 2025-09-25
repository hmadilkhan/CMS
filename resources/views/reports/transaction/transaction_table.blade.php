<div class="card mt-3">
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payee</th>
                    <th>Milestone</th>
                    <th>Amount</th>
                    <th>Deduction Amount</th>
                    <th>Remitted Amount</th>
                    <th>Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalCount = 0;
                $totalAmount = 0;
                $totalDeductionAmount = 0;
                $totalRemittedAmount = 0;
                ?>
                @foreach ($transactions as $i => $transaction)
                    <?php
                    $totalCount++;
                    $totalAmount += $transaction->amount;
                    $totalDeductionAmount += $transaction->deduction_amount;
                    $totalRemittedAmount += $transaction->remitted_amount;
                    
                    ?>
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $transaction->payee_label }}</td>
                        <td>{{ $transaction->milestone }}</td>
                        <td>${{ number_format($transaction->amount, 2) }}</td>
                        <td>${{ number_format($transaction->deduction_amount, 2) }}</td>
                        <td>${{ number_format($transaction->remitted_amount, 2) }}</td>
                        <td>{{ date('d M Y', strtotime($transaction->transaction_date)) }}</td>
                        <td>{{ $transaction->transaction_details }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-center">
                        <h4 class="fw-bold mt-3">Total</h4>
                    </td>
                    <td class="fw-bold">$ {{ number_format($totalAmount, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalDeductionAmount, 2) }}</td>
                    <td class="fw-bold">$ {{ number_format($totalRemittedAmount, 2) }}</td>
                    <td colspan="2" class="fw-bold text-center">-</td>
                </tr>
            </tbody>
        </table>
    </div>
</div> <!-- ROW END -->
