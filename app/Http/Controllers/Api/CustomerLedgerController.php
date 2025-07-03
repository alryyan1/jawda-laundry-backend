<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerLedgerController extends Controller
{
    public function __construct()
    {
        // A customer's financial data should be protected.
        // This could be a financial or high-level receptionist permission.
        $this->middleware('can:customer:view-ledger');
    }

    public function show(Customer $customer)
    {
        // --- CORRECTED DATA FETCHING ---
        // Eager load all orders for the customer, and for each of those orders, load their payments.
        $customer->load(['orders' => function ($query) {
            $query->with('payments')->select('id', 'customer_id', 'order_number', 'created_at', 'total_amount');
        }]);

        // --- Transform Orders into Ledger Entries (Debits) ---
        $debits = $customer->orders->map(function ($order) {
            return [
                'date' => $order->created_at,
                'type' => 'order',
                'description' => 'Order #' . $order->order_number,
                'debit' => (float) $order->total_amount,
                'credit' => 0,
                'reference_id' => $order->id,
            ];
        });

        // --- Transform Payments into Ledger Entries (Credits) ---
        // Now, we get the payments by iterating through the loaded orders
        $credits = $customer->orders->flatMap(function ($order) {
            return $order->payments->map(function ($payment) use ($order) {
                $description = 'Payment';
                if ($payment->type === 'refund') {
                    $description = 'Refund';
                }
                $description .= ' for Order #' . $order->order_number;

                return [
                    'date' => $payment->created_at,
                    'type' => $payment->type,
                    'description' => $description,
                    'debit' => $payment->type === 'refund' ? (float) $payment->amount : 0,
                    'credit' => $payment->type === 'payment' ? (float) $payment->amount : 0,
                    'reference_id' => $order->id, // Link back to the order
                ];
            });
        });

        // --- Merge, Sort, and Calculate Running Balance ---
        $transactions = $debits->concat($credits)->sortBy('date');
        
        $runningBalance = 0;
        $ledgerEntries = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += ($transaction['debit'] - $transaction['credit']);
            $transaction['balance'] = $runningBalance;
            $transaction['date'] = $transaction['date']->toIso8601String();
            return $transaction;
        });
        
        // --- Calculate Final Balances (More efficient way) ---
        $totalDebits = $customer->orders->sum('total_amount');

        // We need all payment amounts across all orders
        $totalCredits = $customer->orders->reduce(function ($carry, $order) {
            return $carry + $order->payments->where('type', 'payment')->sum('amount');
        }, 0);
        $totalRefunds = $customer->orders->reduce(function ($carry, $order) {
            return $carry + $order->payments->where('type', 'refund')->sum('amount');
        }, 0);
        
        $currentBalance = $totalDebits - $totalCredits + $totalRefunds;

        return response()->json([
            'data' => [
                'customer' => [ 'id' => $customer->id, 'name' => $customer->name ],
                'summary' => [
                    'total_debits' => (float) $totalDebits,
                    'total_credits' => (float) $totalCredits, // This is net credits (payments - refunds)
                    'current_balance' => (float) $currentBalance,
                ],
                'transactions' => $ledgerEntries->values()->all(),
            ]
        ]);
    }
}