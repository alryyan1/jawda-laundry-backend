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
        // Protect these actions with permissions
        $this->middleware('can:order:record-payment')->only('store');
        $this->middleware('can:order:view')->only('index');
        // Deleting payments should be highly restricted, e.g., to admins only
        $this->middleware('can:admin-only-or-similar')->only('destroy'); // Define this gate or use a role check
    }

    /**
     * List all payments for a specific order.
     */
    public function index(Order $order)
    {
        $this->authorize('view', $order); // Ensure user can view the parent order
        $payments = $order->payments()->with('user:id,name')->get();
        return PaymentResource::collection($payments);
    }

    /**
     * Store a new payment or refund for a specific order.
     */
    public function store(Request $request, Order $order)
    {
        $this->authorize('recordPayment', $order); // From OrderPolicy

        // Dynamically get the list of allowed payment method keys from the config file.
        $allowedPaymentMethods = array_keys(config('app_settings.payment_methods_ar', []));

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', 'string', Rule::in($allowedPaymentMethods)], // Dynamic validation
            'type' => ['sometimes', 'required', Rule::in(['payment', 'refund'])],
            'payment_date' => 'required|date_format:Y-m-d',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paymentType = $validated['type'] ?? 'payment';
        $paymentAmount = (float) $validated['amount'];

        // --- Business Logic Checks ---
        if ($paymentType === 'payment' && ($order->paid_amount + $paymentAmount) > $order->total_amount) {
            return response()->json(['message' => 'Payment amount exceeds the amount due.'], 422);
        }
        if ($paymentType === 'refund' && $paymentAmount > $order->paid_amount) {
             return response()->json(['message' => 'Refund amount cannot exceed the total amount paid.'], 422);
        }

        // --- Database Transaction ---
        DB::beginTransaction();
        try {
            // A refund is stored as a positive number but subtracted from the total paid.
            // Or stored as negative, but this approach is clearer.
            $finalAmount = $paymentType === 'refund' ? -$paymentAmount : $paymentAmount;
            
            $payment = $order->payments()->create([
                'user_id' => Auth::id(),
                'amount' => $finalAmount,
                'method' => $validated['method'],
                'type' => $paymentType,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['payment_date'],
            ]);

            // Recalculate the order's aggregate amounts from the source of truth (the payments table)
            $totalPaid = (float) $order->payments()->where('type', 'payment')->sum('amount');
            $totalRefunded = (float) $order->payments()->where('type', 'refund')->sum('amount');
            $order->paid_amount = $totalPaid - $totalRefunded;

            // Update the order's payment status based on the new total
            if ($order->paid_amount >= $order->total_amount && $order->total_amount > 0) {
                $order->payment_status = 'paid';
            } elseif ($order->paid_amount > 0) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'pending';
            }
            
            $order->save();
            DB::commit();

            $payment->load('user');
            return new PaymentResource($payment);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error recording payment for order {$order->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to record payment due to a server error.'], 500);
        }
    }

    /**
     * Delete a payment record. (Use with caution)
     */
    public function destroy(Order $order, Payment $payment)
    {
        $this->authorize('delete', $payment); // Requires a PaymentPolicy

        if ($payment->order_id !== $order->id) {
            return response()->json(['message' => 'Payment does not belong to this order.'], 422);
        }

        DB::beginTransaction();
        try {
            $payment->delete();

            // Recalculate and update order totals after deletion
            $totalPaid = (float) $order->payments()->where('type', 'payment')->sum('amount');
            $totalRefunded = (float) $order->payments()->where('type', 'refund')->sum('amount');
            $order->paid_amount = $totalPaid - $totalRefunded;

            if ($order->paid_amount >= $order->total_amount) {
                $order->payment_status = 'paid';
            } elseif ($order->paid_amount > 0) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'pending';
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