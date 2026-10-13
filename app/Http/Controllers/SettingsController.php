<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function update(SettingsRequest $request): mixed
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated() as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        });

        return back()->with('success', 'Settings saved.');
    }
}
