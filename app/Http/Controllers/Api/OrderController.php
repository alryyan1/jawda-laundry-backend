<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\ServiceOffering;
use App\Models\DiningTable;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Pdf\InvoicePdf;
use App\Pdf\PosInvoicePdf;
use App\Services\PricingService; // <-- Import the service
use App\Services\WhatsAppService;
use App\Actions\NotifyCustomerForOrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        // Use Laravel's service container to automatically inject the PricingService
        $this->pricingService = $pricingService;

        // Apply Spatie permissions middleware
        $this->middleware('can:order:list')->only('index');
        $this->middleware('can:order:view')->only('show');
        $this->middleware('can:order:create')->only(['store', 'quoteOrderItem']);
        $this->middleware('can:order:update')->only('update');
        $this->middleware('can:order:update-status')->only('updateStatus');
        $this->middleware('can:order:record-payment')->only('recordPayment');
        $this->middleware('can:order:delete')->only('destroy');
    }

     // Refactor the index method to use the helper
     public function index(Request $request)
     {
         $query = $this->buildOrderQuery($request);
         $orders = $query->paginate($request->get('per_page', 15));
         return OrderResource::collection($orders);
     }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date_format:Y-m-d',
            'items' => 'required|array|min:1',
            'items.*.service_offering_id' => 'required|exists:service_offerings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_description_custom' => 'nullable|string|max:255',
            'items.*.length_meters' => 'nullable|numeric|min:0',
            'items.*.width_meters' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
            'order_type' => 'sometimes|in:in_house,take_away,delivery',
            'dining_table_id' => 'nullable|exists:dining_tables,id', // Add validation for dining table
        ]);

        $customer = Customer::findOrFail($validatedData['customer_id']);
        $orderTotalAmount = 0;
        $orderItemsToCreate = [];

        DB::beginTransaction();
        try {
            foreach ($validatedData['items'] as $itemData) {
                $serviceOffering = ServiceOffering::findOrFail($itemData['service_offering_id']);

                $priceDetails = $this->pricingService->calculatePrice(
                    $serviceOffering,
                    $customer,
                    $itemData['quantity'],
                    $itemData['length_meters'] ?? null,
                    $itemData['width_meters'] ?? null
                );

                $orderItemsToCreate[] = [
                    'service_offering_id' => $serviceOffering->id,
                    'product_description_custom' => $itemData['product_description_custom'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'length_meters' => $itemData['length_meters'] ?? null,
                    'width_meters' => $itemData['width_meters'] ?? null,
                    'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                    'sub_total' => $priceDetails['sub_total'],
                    'notes' => $itemData['notes'] ?? null,
                ];
                $orderTotalAmount += $priceDetails['sub_total'];
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'order_type' => $validatedData['order_type'] ?? 'in_house',
                'dining_table_id' => $validatedData['dining_table_id'] ?? null, // Add dining table ID
                'total_amount' => $orderTotalAmount,
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'notes' => $validatedData['notes'] ?? null,
                'due_date' => $validatedData['due_date'] ?? null,
                'order_date' => now(),
            ]);

            $order->items()->createMany($orderItemsToCreate);
            
            // Update dining table status to occupied if the order has a dining table
            if ($order->dining_table_id) {
                $diningTable = DiningTable::find($order->dining_table_id);
                if ($diningTable) {
                    $diningTable->update(['status' => 'occupied']);
                }
            }
            
            DB::commit();

            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);
            return new OrderResource($order);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating order: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create order. An internal error occurred.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer.customerType', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'payments', 'diningTable']);
        return new OrderResource($order);
    }
  /**
     * Update the specified resource in storage.
     * This now handles updates for notes, due_date, status, and pickup_date.
     */
    public function update(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('update', $order);

        $validatedData = $request->validate([
            'notes' => 'sometimes|nullable|string|max:2000',
            'due_date' => 'sometimes|nullable|date_format:Y-m-d',
            'status' => ['sometimes', 'required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])],
            'pickup_date' => 'sometimes|nullable|date_format:Y-m-d H:i:s', // Expects a full datetime string from frontend
            'order_type' => 'sometimes|in:in_house,take_away,delivery',
        ]);
        
        $oldStatus = $order->status;
        $order->fill($validatedData); // Fill all validated data
        $newStatus = $order->status;

        // If status changed to 'completed' and no pickup date was explicitly provided, set it to now.
        if ($oldStatus !== 'completed' && $newStatus === 'completed' && !$request->has('pickup_date')) {
            $order->pickup_date = now();
        }
        
        // If status changed to something else, clear the pickup date unless it was explicitly sent.
        if ($newStatus !== 'completed' && !$request->has('pickup_date')) {
             $order->pickup_date = null;
        }


        // Save all changes
        $order->save();

        // If status changed, log it and send notification
        if ($oldStatus !== $newStatus) {
            // Update dining table status to available if order is completed and has a dining table
            if ($newStatus === 'completed' && $order->dining_table_id) {
                $diningTable = DiningTable::find($order->dining_table_id);
                if ($diningTable) {
                    $diningTable->update(['status' => 'available']);
                }
            }
            
            $order->logActivity("Status changed from '{$oldStatus}' to '{$newStatus}'.");
            $notifier->execute($order);
        }

        if ($request->has('pickup_date')) {
             $order->logActivity("Pickup date was updated.");
        }

        // Return the fresh resource with all relations
        return new OrderResource($order->fresh(['customer', 'user', 'items', 'diningTable']));
    }
    /**
     * Update only the status of the specified order and trigger notifications.
     */
    public function updateStatus(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('updateStatus', $order);
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])]]);
        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        if ($oldStatus !== $newStatus) {
            $order->status = $newStatus;
            if ($newStatus === 'completed' && !$order->pickup_date) $order->pickup_date = now();
            $order->save();
            
            // Update dining table status to available if order is completed and has a dining table
            if ($newStatus === 'completed' && $order->dining_table_id) {
                $diningTable = DiningTable::find($order->dining_table_id);
                if ($diningTable) {
                    $diningTable->update(['status' => 'available']);
                }
            }
            
            $order->logActivity("Status changed from '{$oldStatus}' to '{$newStatus}'.");
            $notifier->execute($order); // Call the action to handle notification logic
        }
        $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'payments', 'diningTable']);
        return new OrderResource($order);
    }

    /**
     * Record a payment for the specified order.
     */
    public function recordPayment(Request $request, Order $order)
    {
        $validated = $request->validate(['amount_paid' => 'required|numeric|min:0.01']);

        if ($order->paid_amount >= $order->total_amount) {
            return response()->json(['message' => 'Order is already fully paid.'], 400);
        }

        $order->paid_amount = (float) $order->paid_amount + (float) $validated['amount_paid'];
        if ($order->paid_amount >= $order->total_amount) {
            $order->payment_status = 'paid';
            $order->paid_amount = $order->total_amount; // Cap payment at total
        } else {
            $order->payment_status = 'partially_paid';
        }
        $order->save();
        return new OrderResource($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // Orders are usually cancelled via status change, not deleted.
        // Using soft-deletes on the Order model is recommended.
        if ($order->status !== 'cancelled') {
            return response()->json(['message' => 'Only cancelled orders can be deleted.'], 400);
        }
        $order->delete(); // This will soft delete if the trait is used on the model.
        return response()->noContent();
    }

    /**
     * Quote a potential order item.
     */
    public function quoteOrderItem(Request $request)
    {
        $validatedData = $request->validate([
            'service_offering_id' => 'required|exists:service_offerings,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity' => 'required|integer|min:1',
            'length_meters' => 'nullable|numeric|min:0',
            'width_meters' => 'nullable|numeric|min:0',
        ]);

        try {
            $serviceOffering = ServiceOffering::findOrFail($validatedData['service_offering_id']);
            $customer = Customer::findOrFail($validatedData['customer_id']);
            $priceDetails = $this->pricingService->calculatePrice(
                $serviceOffering,
                $customer,
                $validatedData['quantity'],
                $validatedData['length_meters'] ?? null,
                $validatedData['width_meters'] ?? null
            );
            return response()->json($priceDetails);
        } catch (\Exception $e) {
            Log::error("Error quoting order item: " . $e->getMessage());
            return response()->json(['message' => 'Failed to calculate price quote.'], 500);
        }
    }
    
    /**
     * Generate and download a PDF invoice for the specified order using raw TCPDF methods.
     */
    public function downloadInvoice(Order $order)
    {
        // $this->authorize('view', $order); // Check permission using Spatie/Policies

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // Instantiate your custom PDF class
        $pdf = new InvoicePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Pass data to the PDF class
        $pdf->setOrder($order);
        $pdf->setCompanyDetails(
            config('app_settings.company_name', config('app.name')),
            config('app_settings.company_address') . "\nPhone: " . config('app_settings.company_phone')
        );

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Invoice ' . $order->order_number);
        $pdf->SetSubject('Order Invoice');

        // Set default header/footer data (if not already set in your custom class)
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

        // Set margins
        $pdf->SetMargins(5, 38, 5); // Left, Top, Right
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);


        // Set font
        $pdf->SetFont('arial', '', 10);

        // Call your custom generate method which builds the PDF
        $pdf->generate();

        // Close and output PDF document
        // 'I' for inline browser display, 'D' for download
        $pdf->Output('invoice-'.$order->id.'.pdf', 'I');
        exit; // TCPDF's output can sometimes interfere with Laravel's response cycle. exit() is a safeguard.
    }
     /**
     * Generate and download a PDF invoice formatted for a POS thermal printer.
     */
    public function downloadPosInvoice(Order $order, bool $base64 = false)
    {
        // $this->authorize('view', $order);

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // --- PDF Generation ---
        // Page format arguments: orientation, unit, format (array(width, height) in mm for custom roll)
        // 80mm is a common POS paper width
        // Calculate dynamic page height based on number of items (10mm per item)
        $baseHeight = 120;
        $itemHeight = 10;
        $pageHeight = $baseHeight + ($order->items->count() * $itemHeight);
        $pdf = new PosInvoicePdf('P', 'mm', [80, $pageHeight], true, 'UTF-8', false);

        // Pass data to the PDF class
        $pdf->setOrder($order);
        $settings = [
            'general_company_name' => config('app_settings.company_name', config('app.name')),
            'general_company_name_ar' => config('app_settings.company_name_ar', 'شاي خدري'),
            'general_company_address' => config('app_settings.company_address'),
            'general_company_address_ar' => config('app_settings.company_address_ar', 'مسقط'),
            'general_company_phone' => config('app_settings.company_phone'),
            'general_company_phone_ar' => config('app_settings.company_phone_ar', '--'),
            'general_default_currency_symbol' => config('app_settings.currency_symbol', 'OMR'),
            'company_logo_url' => config('app_settings.company_logo_url'),
            'language' => 'en', // Default language, can be made configurable
        ];
        $pdf->setSettings($settings);

        // Set document information
        $pdf->SetTitle('Receipt ' . $order->order_number);
        $pdf->SetAuthor(config('app.name'));
        $pdf->setPrintHeader(false); // We can control header in generate()
        $pdf->setPrintFooter(true);  // Use our custom footer

        // Set margins: left, top, right
        $pdf->SetMargins(4, 5, 4);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15); // Margin from bottom

        // Call your custom generate method which builds the PDF with Cell()
        $pdf->generate();

        if ($base64) {
            // Return base64 encoded PDF content
            return $pdf->Output('receipt-'.$order->order_number.'.pdf', 'S');
        } else {
            // Close and output PDF document
            // 'I' for inline browser display. This is best for POS printing.
            // The browser's PDF viewer will handle the print dialog.
            $pdf->Output('receipt-'.$order->order_number.'.pdf', 'I');
            exit;
        }
    }
    
    /**
     * Generates an invoice PDF and sends it via WhatsApp.
     */
    public function sendWhatsappInvoice(Order $order, WhatsAppService $whatsAppService)
    {
        $this->authorize('view', $order); // Or a specific 'order:send-invoice' permission

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        // --- 1. Generate the PDF using the refactored method ---
        $pdfContent = $this->downloadPosInvoice($order, true); // true for base64

        // --- 2. Base64 Encode the PDF Content ---
        $base64Pdf = base64_encode($pdfContent);

        // --- 3. Send via WhatsApp Service ---
        $fileName = 'Invoice-' . $order->order_number . '.pdf';
        $caption = "Hello {$customer->name}, here is the invoice for your order #{$order->order_number}. Thank you!";
        
        // Sanitize phone number - remove '+', spaces, dashes
        $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone);

        $result = $whatsAppService->sendMediaBase64($phoneNumber, $base64Pdf, $fileName, $caption);

        // --- 4. Return Response to Frontend ---
        if ($result['status'] === 'success') {
            // Update the tracking field
            $order->update(['whatsapp_pdf_sent' => true]);
            // Optionally log this action
            $order->logActivity("Invoice sent to customer via WhatsApp.");
            return response()->json(['message' => 'Invoice sent successfully via WhatsApp!']);
        } else {
            return response()->json([
                'message' => 'Failed to send WhatsApp invoice.',
                'details' => $result['message'] ?? 'Unknown API error.',
                'api_response' => $result['data'] ?? null
            ], 500);
        }
    }

    /**
     * Sends a custom WhatsApp message to the customer about their order.
     */
    public function sendWhatsappMessage(Request $request, Order $order, WhatsAppService $whatsAppService)
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        // Sanitize phone number - remove '+', spaces, dashes
        $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone);

        $result = $whatsAppService->sendMessage($phoneNumber, $validated['message']);

        if ($result['status'] === 'success') {
            // Update the tracking field
            $order->update(['whatsapp_text_sent' => true]);
            // Log this action
            $order->logActivity("Custom WhatsApp message sent to customer: " . substr($validated['message'], 0, 50) . "...");
            return response()->json(['message' => 'Message sent successfully via WhatsApp!']);
        } else {
            return response()->json([
                'message' => 'Failed to send WhatsApp message.',
                'details' => $result['message'] ?? 'Unknown API error.',
                'api_response' => $result['data'] ?? null
            ], 500);
        }
    }
     /**
     * Export a filtered list of orders to a CSV file.
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('order:list'); // Or a new 'report:export' permission

        // Reuse the same query builder logic from the index method
        $query = $this->buildOrderQuery($request);
        
        // Get all matching orders without pagination for the export
        $orders = $query->get();
        
        $fileName = 'orders_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // Add Header Row
            fputcsv($file, [
                'ID', 'Order Number', 'Customer Name', 'Customer Phone', 'Status',
                'Order Date', 'Due Date', 'Pickup Date', 'Total Amount', 'Amount Paid', 'Amount Due'
            ]);

            // Add Data Rows
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->order_number,
                    $order->customer->name,
                    $order->customer->phone,
                    $order->status,
                    $order->order_date->format('Y-m-d H:i:s'),
                    $order->due_date ? $order->due_date->format('Y-m-d') : '',
                    $order->pickup_date ? $order->pickup_date->format('Y-m-d H:i:s') : '',
                    $order->total_amount,
                    $order->paid_amount,
                    $order->amount_due,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Helper function to build the order query based on request filters.
     * Reused by both index() and exportCsv().
     */
    private function buildOrderQuery(Request $request)
    {
        $query = Order::with(['customer:id,name,phone', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable'])->latest('order_date');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('date_from')) $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('order_date', '<=', $request->date_to);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(fn($q) => $q->where('id', $searchTerm)
                ->orWhere('order_number', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'LIKE', "%{$searchTerm}%")));
        }

        if ($request->filled('product_type_id')) {
            $query->whereHas('items.serviceOffering.productType', fn($q) => $q->where('id', $request->product_type_id));
        }
        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }
        
        // Handle today parameter
        if ($request->boolean('today')) {
            $query->whereDate('created_at', now()->toDateString());
        }
        return $query;
    }

    /**
     * Get order statistics for the specified date range.
     */
    public function statistics(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Order::query();

        if ($dateFrom) {
            $query->whereDate('order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('order_date', '<=', $dateTo);
        }

        // Get total orders
        $totalOrders = $query->count();

        // Get total amount paid
        $totalAmountPaid = $query->sum('paid_amount');

        // Get payment breakdown
        $paymentBreakdown = $query->join('payments', 'orders.id', '=', 'payments.order_id')
            ->selectRaw('payments.method, SUM(payments.amount) as total_amount')
            ->groupBy('payments.method')
            ->pluck('total_amount', 'method')
            ->toArray();

        // Ensure all payment methods are present with 0 values
        $allPaymentMethods = ['cash', 'card', 'online', 'credit'];
        $paymentBreakdown = array_merge(
            array_fill_keys($allPaymentMethods, 0),
            $paymentBreakdown
        );

        return response()->json([
            'totalOrders' => $totalOrders,
            'totalAmountPaid' => $totalAmountPaid,
            'paymentBreakdown' => $paymentBreakdown,
        ]);
    }

 
}