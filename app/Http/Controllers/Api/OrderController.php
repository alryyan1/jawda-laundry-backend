<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Pdf\InvoicePdf;
use App\Pdf\PosInvoicePdf;
use App\Services\PricingService; // <-- Import the service
use App\Services\SettingsService;
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

    /**
     * Display a listing of the resource with filtering and sorting.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer:id,name', 'user:id,name'])->latest();

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // Sorting


        $orders = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 10));
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
                'total_amount' => $orderTotalAmount,
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'notes' => $validatedData['notes'] ?? null,
                'due_date' => $validatedData['due_date'] ?? null,
                'order_date' => now(),
            ]);

            $order->items()->createMany($orderItemsToCreate);
            DB::commit();

            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction']);
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
        $order->load(['customer.customerType', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction']);
        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // This is a complex endpoint. The logic from a previous step would go here.
        // For brevity, we assume the 'updateStatus' and 'recordPayment' are the primary update actions.
        // A full update would look very similar to the 'store' method but start with an existing $order.
        // For now, let's just update order-level details.
        $validatedData = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);
        $order->update($validatedData);
        $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction']);
        return new OrderResource($order);
    }

    /**
     * Update only the status of the specified order and trigger notifications.
     */
    public function updateStatus(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('updateStatus', $order);
        $validated = $request->validate(['status' => ['required', Rule::in(config('app.order_statuses'))]]); // Assuming you have this config
        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        if ($oldStatus !== $newStatus) {
            $order->status = $newStatus;
            if ($newStatus === 'completed' && !$order->pickup_date) $order->pickup_date = now();
            $order->save();
            $order->logActivity("Status changed from '{$oldStatus}' to '{$newStatus}'.");
            $notifier->execute($order); // Call the action to handle notification logic
        }
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
            config('app.name', 'LaundryPro'),
            "123 Laundry Lane, Clean City, ST 12345\nPhone: (555) 123-4567" // Get from config
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
        $pdf->SetMargins(15, 38, 15); // Left, Top, Right
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Call your custom generate method which builds the PDF
        $pdf->generate();

        // Close and output PDF document
        // 'I' for inline browser display, 'D' for download
        $pdf->Output('invoice-'.$order->order_number.'.pdf', 'I');
        exit; // TCPDF's output can sometimes interfere with Laravel's response cycle. exit() is a safeguard.
    }
     /**
     * Generate and download a PDF invoice formatted for a POS thermal printer.
     */
    public function downloadPosInvoice(Order $order, SettingsService $settingsService)
    {
        // $this->authorize('view', $order);

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // --- PDF Generation ---
        // Page format arguments: orientation, unit, format (array(width, height) in mm for custom roll)
        // 80mm is a common POS paper width
        $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false);

        // Pass data to the PDF class
        $pdf->setOrder($order);
        $settings = [
            'general_company_name' => $settingsService->get('general_company_name', config('app.name')),
            'general_company_address' => $settingsService->get('general_company_address'),
            'general_company_phone' => $settingsService->get('general_company_phone'),
            'general_default_currency_symbol' => '$', // Get from settings later
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

        // Close and output PDF document
        // 'I' for inline browser display. This is best for POS printing.
        // The browser's PDF viewer will handle the print dialog.
        $pdf->Output('receipt-'.$order->order_number.'.pdf', 'I');
        exit;
    }
    
    /**
     * Generates an invoice PDF and sends it via WhatsApp.
     */
    public function sendWhatsappInvoice(Order $order, SettingsService $settingsService, WhatsAppService $whatsAppService)
    {
        $this->authorize('view', $order); // Or a specific 'order:send-invoice' permission

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        // --- 1. Generate the PDF in memory ---
        $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false);
        $order->load(['customer', 'user', 'items.serviceOffering']); // Eager load necessary data
        
        $settings = [
            'general_company_name' => $settingsService->get('general_company_name', config('app.name')),
            // ... fetch other settings needed for the PDF
        ];
        $pdf->setOrder($order);
        $pdf->setSettings($settings);
        $pdf->generate(); // This builds the PDF content

        // Get the raw PDF content as a string
        // 'S' tells TCPDF to output as a string instead of to the browser
        $pdfContent = $pdf->Output('invoice.pdf', 'S');

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
}