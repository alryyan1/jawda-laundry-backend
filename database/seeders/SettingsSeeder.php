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
                'value' => 'My Awesome Company',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Name',
                'description' => 'The name of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_address',
                'value' => '123 Main St, Anytown, USA',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Address',
                'description' => 'The address of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_phone',
                'value' => '+1-555-123-4567',
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Phone',
                'description' => 'The phone number of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company_phone_2',
                'value' => '',
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
                'value' => null,
                'type' => 'string',
                'group' => 'company',
                'display_name' => 'Company Logo URL',
                'description' => 'URL to your company logo',
                'is_public' => true,
            ],

            // App Branding
            [
                'key' => 'app_name',
                'value' => 'Jawda Laundry',
                'type' => 'string',
                'group' => 'app',
                'display_name' => 'App Name',
                'description' => 'The name displayed in the app bar and login page',
                'is_public' => true,
            ],
            [
                'key' => 'app_description',
                'value' => 'LAUNDRY MANAGEMENT SYSTEM',
                'type' => 'string',
                'group' => 'app',
                'display_name' => 'App Description',
                'description' => 'The description displayed on the login page',
                'is_public' => true,
            ],

            // General Settings
            [
                'key' => 'currency_symbol',
                'value' => '$',
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

            // WhatsApp Settings
            [
                'key' => 'whatsapp_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'display_name' => 'Enable WhatsApp',
                'description' => 'Enable WhatsApp notifications',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_api_url',
                'value' => '',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WhatsApp API URL',
                'description' => 'The WhatsApp API URL',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_api_token',
                'value' => '',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WhatsApp API Token',
                'description' => 'The WhatsApp API token',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_notification_number',
                'value' => '',
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
                // Update existing setting with new columns
                DB::table('settings')
                    ->where('key', $setting['key'])
                    ->update([
                        'type' => $setting['type'],
                        'display_name' => $setting['display_name'],
                        'description' => $setting['description'],
                        'is_public' => $setting['is_public'],
                    ]);
            }
        }
    }
}
