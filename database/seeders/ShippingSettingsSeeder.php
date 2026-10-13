<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ShippingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(['key' => 'shipping_fee_cents'], ['value' => '10000']);
    }
}
