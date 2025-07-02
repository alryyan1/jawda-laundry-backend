<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Use permissions defined for orders
        $this->middleware('can:order:record-payment')->only('store');
        $this->middleware('can:order:view')->only('index');
        // Deleting payments should be highly restricted
        $this->middleware('can:admin-only-or-similar')->only('destroy'); // Example
    }

    /**
     * List all payments for a specific order.
     */
    public function index(Order $order)
    {
        $this->authorize('view', $order);
        $payments = $order->payments()->with('user:id,name')->get();
        return PaymentResource::collection($payments);
    }

    /**
     * Store a new payment for a specific order.
     */
    public function store(Request $request, Order $order)
    {
        $this->authorize('recordPayment', $order); // From OrderPolicy

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', 'string', Rule::in(['cash', 'card', 'online', 'credit'])],
            'type' => ['sometimes', 'required', Rule::in(['payment', 'refund'])],
            'payment_date' => 'required|date_format:Y-m-d',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paymentType = $validated['type'] ?? 'payment';
        $paymentAmount = (float) $validated['amount'];

        // Prevent overpayment
        if ($paymentType === 'payment' && ($order->paid_amount + $paymentAmount) > $order->total_amount) {
            return response()->json(['message' => 'Payment amount exceeds amount due.'], 422);
        }
        // Prevent refunding more than has been paid
        if ($paymentType === 'refund' && $paymentAmount > $order->paid_amount) {
             return response()->json(['message' => 'Refund amount cannot exceed amount paid.'], 422);
        }

        DB::beginTransaction();
        try {
            $finalAmount = $paymentType === 'refund' ? -$paymentAmount : $paymentAmount;
            
            // Create the payment record
            $payment = $order->payments()->create([
                'user_id' => Auth::id(),
                'amount' => $finalAmount,
                'method' => $validated['method'],
                'type' => $paymentType,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['payment_date'],
            ]);

            // Update the order's aggregate amounts
            $order->paid_amount = (float) $order->payments()->where('type', 'payment')->sum('amount') - (float) $order->payments()->where('type', 'refund')->sum('amount');

            // Update the order's payment status
            if ($order->paid_amount <= 0) {
                $order->payment_status = 'pending';
            } elseif ($order->paid_amount < $order->total_amount) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'paid';
            }
            
            $order->save();
            DB::commit();

            $payment->load('user');
            return new PaymentResource($payment);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error recording payment for order {$order->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to record payment.'], 500);
        }
    }

    /**
     * Delete a payment record.
     * This should be used with extreme caution.
     */
    public function destroy(Order $order, Payment $payment)
    {
        // Ensure payment belongs to the order
        if ($payment->order_id !== $order->id) {
            return response()->json(['message' => 'Payment does not belong to this order.'], 422);
        }

        DB::beginTransaction();
        try {
            $payment->delete();

            // Recalculate and update order totals after deletion
            $order->paid_amount = (float) $order->payments()->where('type', 'payment')->sum('amount') - (float) $order->payments()->where('type', 'refund')->sum('amount');
            if ($order->paid_amount <= 0) {
                $order->payment_status = 'pending';
            } elseif ($order->paid_amount < $order->total_amount) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'paid';
            }
            $order->save();
            DB::commit();
            
            return response()->json(['message' => 'Payment record deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting payment {$payment->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete payment.'], 500);
        }
    }
}