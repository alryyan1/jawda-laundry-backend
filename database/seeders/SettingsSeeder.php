<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Company Information
            [
                'key' => 'company_name',
                'value' => 'زاد الرميس',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Name',
                'description' => 'The name of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_address',
                'value' => 'الرميس مقابل مركز النسيم الصحي',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Address',
                'description' => 'The address of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_phone',
                'value' => '98889761',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Phone',
                'description' => 'The phone number of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_phone_2',
                'value' => '92552558',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Phone 2',
                'description' => 'The second phone number of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_email',
                'value' => 'contact@example.com',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Email',
                'description' => 'The email address of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_logo_url',
                'value' => 'http://127.0.0.1/laundry/jawda-laundry-backend/public/storage/logos/59gydcN8h4DVDbmCHwZvMOShIVG3L7jWBhMklHyU.png',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Logo URL',
                'description' => 'URL to your company logo',
                'is_public' => true,
            ],

            // App Branding
            [
                'key' => 'app_name',
                'value' => 'Jawda Restaurant',
                'type' => 'string',
                'group' => 'app',
                'display_name' => 'App Name',
                'description' => 'The name displayed in the app bar and login page',
                'is_public' => true,
            ],
            [
                'key' => 'app_description',
                'value' => 'RESTAURANT MANAGEMENT SYSTEM',
                'type' => 'string',
                'group' => 'app',
                'display_name' => 'App Description',
                'description' => 'The description displayed on the login page',
                'is_public' => true,
            ],

            // General Settings
            [
                'key' => 'currency_symbol',
                'value' => 'OMR',
                'type' => 'string',
                'group' => 'general',
                'display_name' => 'Currency Symbol',
                'description' => 'The currency symbol used throughout the application',
                'is_public' => true,
            ],
            [
                'key' => 'date_format',
                'value' => 'YYYY-MM-DD',
                'type' => 'string',
                'group' => 'general',
                'display_name' => 'Date Format',
                'description' => 'The date format used throughout the application',
                'is_public' => true,
            ],
            [
                'key' => 'global_low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
                'group' => 'general',
                'display_name' => 'Low Stock Threshold',
                'description' => 'The threshold for low stock warnings',
                'is_public' => false,
            ],

            // Invoice Settings
            [
                'key' => 'invoice_prefix',
                'value' => 'INV-',
                'type' => 'string',
                'group' => 'invoice',
                'display_name' => 'Invoice Prefix',
                'description' => 'Prefix for invoice numbers',
                'is_public' => false,
            ],
            [
                'key' => 'purchase_order_prefix',
                'value' => 'PO-',
                'type' => 'string',
                'group' => 'invoice',
                'display_name' => 'Purchase Order Prefix',
                'description' => 'Prefix for purchase order numbers',
                'is_public' => false,
            ],
            [
                'key' => 'invoice_thermal_footer',
                'value' => 'شكراً لزيارتكم!زورونا مرة أخرى!',
                'type' => 'string',
                'group' => 'invoice',
                'display_name' => 'Thermal Invoice Footer',
                'description' => 'Footer text for thermal invoices',
                'is_public' => false,
            ],

            // Payment Methods (Arabic)
            [
                'key' => 'payment_methods_ar',
                'value' => json_encode([
                    'cash' => 'نقدي',
                    'visa' => 'فيزا',
                    'mastercard' => 'ماستركارد',
                    'bank_transfer' => 'تحويل بنكي',
                    'mada' => 'مدى',
                    'store_credit' => 'رصيد متجر',
                    'other' => 'أخرى',
                ]),
                'type' => 'json',
                'group' => 'payment',
                'display_name' => 'Payment Methods (Arabic)',
                'description' => 'Arabic translations for payment methods',
                'is_public' => true,
            ],

            // WhatsApp Settings (UltraMsg Only)
            [
                'key' => 'whatsapp_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'display_name' => 'Enable WhatsApp',
                'description' => 'Enable WhatsApp notifications using UltraMsg API',
                'is_public' => false,
            ],
            [
                'key' => 'ultramsg_token',
                'value' => 'b6ght2y2ff7rbha6',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'UltraMsg Token',
                'description' => 'Your UltraMsg API token',
                'is_public' => false,
            ],
            [
                'key' => 'ultramsg_instance_id',
                'value' => 'instance139458',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'UltraMsg Instance ID',
                'description' => 'Your UltraMsg instance ID',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_notification_number',
                'value' => '78622990',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WhatsApp Notification Number',
                'description' => 'The phone number for WhatsApp notifications',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_country_code',
                'value' => '968',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WhatsApp Country Code',
                'description' => 'The country code for WhatsApp',
                'is_public' => false,
            ],

            // POS Settings
            [
                'key' => 'pos_auto_show_pdf',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Show PDF',
                'description' => 'Automatically show PDF after order completion',
                'is_public' => false,
            ],
            [
                'key' => 'pos_show_products_as_list',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Show Products as List',
                'description' => 'Show products in list format instead of grid',
                'is_public' => false,
            ],
            [
                'key' => 'pos_auto_send_whatsapp_invoice',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Send WhatsApp Invoice',
                'description' => 'Automatically send PDF invoice via WhatsApp when order is completed',
                'is_public' => false,
            ],
            [
                'key' => 'pos_auto_send_whatsapp_text',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Send WhatsApp Text',
                'description' => 'Automatically send WhatsApp text message when order is completed',
                'is_public' => false,
            ],

            // Theme Settings
            [
                'key' => 'theme_primary_color',
                'value' => 'sky',
                'type' => 'string',
                'group' => 'theme',
                'display_name' => 'Primary Color',
                'description' => 'The primary color theme',
                'is_public' => true,
            ],
            [
                'key' => 'theme_secondary_color',
                'value' => 'blue',
                'type' => 'string',
                'group' => 'theme',
                'display_name' => 'Secondary Color',
                'description' => 'The secondary color theme',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
            $existing = DB::table('settings')->where('key', $setting['key'])->first();
            
            if (!$existing) {
                DB::table('settings')->insert($setting);
            } else {
                // Update existing setting with new values
                DB::table('settings')
                    ->where('key', $setting['key'])
                    ->update([
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'display_name' => $setting['display_name'],
                        'description' => $setting['description'],
                        'is_public' => $setting['is_public'],
                    ]);
            }
        }
        
        // Clear settings cache after seeding to ensure fresh data
        \App\Models\Setting::clearCache();
    }
}
