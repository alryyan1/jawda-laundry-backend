<?php
namespace App\Actions;
use App\Models\Order;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use App\Pdf\PosInvoicePdf;
use Illuminate\Support\Facades\Log;

class NotifyCustomerForOrderStatus {
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    public function execute(Order $order): void
    {
        if (!$this->whatsAppService->isConfigured()) return;
        if (!$order->customer?->phone) return;

        $template = WhatsappTemplate::where('status', $order->status)->where('is_active', true)->first();
        if (!$template) return; // No active template for this status, do nothing

        // Replace placeholders in the message template
        $message = $this->parseTemplate($template->message_template, $order);
        $phoneNumber = preg_replace('/[^0-9]/', '', $order->customer->phone);

        if ($template->attach_invoice) {
            // Generate PDF in memory
            $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false);
            $order->load(['customer', 'user', 'items.serviceOffering']);
            $settings = [
                'general_company_name' => config('app_settings.company_name', config('app.name')),
                'general_company_name_ar' => config('app_settings.company_name_ar', 'شاي خدري'),
                'general_company_address' => config('app_settings.company_address'),
                'general_company_address_ar' => config('app_settings.company_address_ar', 'مسقط'),
                'general_company_phone' => config('app_settings.company_phone'),
                'general_company_phone_ar' => config('app_settings.company_phone_ar', '--'),
                'general_default_currency_symbol' => config('app_settings.currency_symbol', 'OMR'),
                'company_logo_url' => config('app_settings.company_logo_url'),
                'language' => 'en',
            ];
            $pdf->setOrder($order);
            $pdf->setSettings($settings);
            $pdf->generate();
            $pdfContent = $pdf->Output('invoice.pdf', 'S');
            $base64Pdf = base64_encode($pdfContent);
            $fileName = 'Invoice-' . $order->order_number . '.pdf';
            
            // Send via WhatsApp service
            $this->whatsAppService->sendMediaBase64($phoneNumber, $base64Pdf, $fileName, $message);
        } else {
            // Send a simple text message
            $this->whatsAppService->sendMessage($phoneNumber, $message);
        }
    }

    private function parseTemplate(string $template, Order $order): string
    {
        $placeholders = [
            '{customer_name}' => $order->customer->name,
            '{order_number}' => $order->order_number,
            '{order_status}' => ucwords(str_replace('_', ' ', $order->status)),
            '{total_amount}' => number_format($order->total_amount, 3),
            '{amount_due}' => number_format($order->amount_due, 3),
            '{company_name}' => config('app_settings.company_name', config('app.name')),
        ];
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
}