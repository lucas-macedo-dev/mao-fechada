<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; }
        .amount { text-align: right; }
        .income { color: #1b7a3d; }
        .expense { color: #b3261e; }
        .totals { margin-top: 16px; }
        .totals td { border: none; padding: 2px 8px; }
    </style>
</head>
<body>
    <h1>Transactions Report</h1>
    <div class="meta">
        {{ $user->name }} &mdash; generated {{ now()->format('Y-m-d H:i') }}
        @if (!empty($filters['month']))
            &mdash; period {{ $filters['month'] }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Type</th>
                <th>Payment Method</th>
                <th class="amount">Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transacted_at->format('Y-m-d') }}</td>
                    <td>{{ $transaction->category?->name }}</td>
                    <td class="{{ $transaction->type }}">{{ ucfirst($transaction->type) }}</td>
                    <td>{{ $transaction->payment_method }}</td>
                    <td class="amount">{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ $transaction->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total income</td><td class="amount income">{{ number_format($totalIncome, 2) }}</td></tr>
        <tr><td>Total expense</td><td class="amount expense">{{ number_format($totalExpense, 2) }}</td></tr>
        <tr><td><strong>Net</strong></td><td class="amount"><strong>{{ number_format($totalNet, 2) }}</strong></td></tr>
    </table>
</body>
</html>
