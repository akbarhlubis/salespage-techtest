<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit');
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        if ($request->hasFile('site_logo')) {
            $old = Setting::get('site_logo');
            if ($old) {
                Storage::disk('public')->delete($old);
            }

            $path = $request->file('site_logo')->store('logo', 'public');
            Setting::set('site_logo', $path, 'image');
        }

        return back()->with('success', 'Settings saved.');
    }

    public function destroyLogo()
    {
        $old = Setting::get('site_logo');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        Setting::where('key', 'site_logo')->delete();

        return back()->with('success', 'Logo removed. Default logo restored.');
    }
}
