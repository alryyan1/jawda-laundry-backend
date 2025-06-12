<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::firstOrCreate(
            ['name' => 'Standard Wash & Fold (per kg)'],
            [
                'description' => 'Standard washing, drying, and folding service, priced per kilogram.',
                'price' => 5.00,
                'category' => 'Washing',
            ]
        );
        Service::firstOrCreate(
            ['name' => 'Delicate Wash (per item)'],
            [
                'description' => 'Gentle washing for delicate items, priced per item.',
                'price' => 3.50,
                'category' => 'Washing',
            ]
        );
        Service::firstOrCreate(
            ['name' => 'Suit Dry Cleaning'],
            [
                'description' => 'Professional dry cleaning for a 2-piece suit.',
                'price' => 20.00,
                'category' => 'Dry Cleaning',
            ]
        );
        Service::firstOrCreate(
            ['name' => 'Shirt Ironing'],
            [
                'description' => 'Professional ironing service for shirts.',
                'price' => 2.50,
                'category' => 'Ironing',
            ]
        );
         Service::firstOrCreate(
            ['name' => 'Bedding Set (Queen)'],
            [
                'description' => 'Wash and fold for a queen-size bedding set (sheets, pillowcases, duvet cover).',
                'price' => 15.00,
                'category' => 'Specialty',
            ]
        );
    }
}