<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        // Get tenant-specific settings
        $app_info = Setting::where('tenant_id', $tenantId)->first();
        
        // If no tenant settings exist, create defaults
        if (!$app_info) {
            $app_info = $this->createDefaultSettings($tenantId, $user->id);
        }

        return view('settings.index', [
            'app_info' => $app_info
        ]);
    }

    // Create default settings for a tenant
    private function createDefaultSettings($tenantId, $userId)
    {
        $globalSettings = Setting::whereNull('tenant_id')->first();
        
        $defaults = [
            'tenant_id' => $tenantId,
            'app_name' => 'LAEL POS - Tenant ' . $tenantId,
            'app_email' => '',
            'app_contact' => '',
            'currency' => 'USD',
            'locale' => 'en',
            'menu_nav_color' => '#3498db',
            'font_family' => 'Cursive',
            'font_size' => '1.3',
            'created_by' => $userId,
        ];

        // Copy from global settings if they exist
        if ($globalSettings) {
            $defaults = array_merge($defaults, [
                'mail_status' => $globalSettings->mail_status,
                'mail_mailer' => $globalSettings->mail_mailer,
                'mail_host' => $globalSettings->mail_host,
                'mail_port' => $globalSettings->mail_port,
                'mail_username' => $globalSettings->mail_username,
                'mail_password' => $globalSettings->mail_password,
                'mail_encryption' => $globalSettings->mail_encryption,
                'mail_address' => $globalSettings->mail_address,
                'mail_name' => $globalSettings->mail_name,
                'meta_keyword' => $globalSettings->meta_keyword,
                'meta_descrip' => $globalSettings->meta_descrip,
            ]);
        }

        return Setting::create($defaults);
    }

    // Languages
    public function switchLocale($locale)
    {
        if (in_array($locale, ['en', 'fr', 'lg'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        // Update tenant-specific language
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $app_locale = Setting::where('tenant_id', $tenantId)->first();
        
        if ($app_locale) {
            $app_locale->update([
                'locale' => $locale
            ]);
        } else {
            Setting::create([
                'tenant_id' => $tenantId,
                'locale' => $locale,
                'created_by' => $user->id,
            ]);
        }

        return redirect()->back();
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('update settings')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the incoming request data
        $validatedData = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_email' => 'nullable|email|max:255',
            'app_contact' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'meta_keyword' => 'nullable|string|max:255',
            'meta_descrip' => 'nullable|string|max:500',
            'menu_nav_color' => 'nullable|string|max:50',
            'font_family' => 'nullable|string|max:100',
            'font_size' => 'nullable|string|max:10',
            'mail_status' => 'nullable|in:enabled,disabled',
            'mail_mailer' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_address' => 'nullable|string|max:255',
            'mail_name' => 'nullable|string|max:255',
        ]);

        // Retrieve the tenant-specific settings
        $setting = Setting::where('tenant_id', $tenantId)->first();

        if (!$setting) {
            // Create default settings if they don't exist
            $setting = $this->createDefaultSettings($tenantId, $user->id);
        }

        $componentToReload = null;

        // Determine what to update based on provided inputs
        if ($request->hasAny(['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_address', 'mail_name', 'mail_status'])) {
            $setting->update($request->only([
                'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 
                'mail_password', 'mail_encryption', 'mail_address', 
                'mail_name', 'mail_status',
            ]));
            $componentToReload = 'updateSMTPForm';

        } elseif ($request->hasAny(['app_name', 'app_email', 'app_contact', 'currency'])) {
            $setting->update($request->only(['app_name', 'app_email', 'app_contact','currency']));
            $componentToReload = 'updateAppInfoForm';

        } elseif ($request->hasAny(['meta_keyword', 'meta_descrip'])) {
            $setting->update($request->only(['meta_keyword', 'meta_descrip']));
            $componentToReload = 'updateMetaInfoForm';

        } elseif ($request->hasAny(['menu_nav_color', 'font_family', 'font_size'])) {
            $setting->update($request->only(['menu_nav_color', 'font_family', 'font_size']));
            $componentToReload = 'updateUISettingsForm';

        } else {
            return response()->json([
                'success' => false,
                'message' => __('auth.something_wrong'),
            ], 400);
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => __('auth._updated'),
            'reload' => true,
            'component' => $componentToReload,
            'redirect' => route('settings.index'),
        ], 200);
    }

    public function uploadLogo(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $request->validate([
            'logo_image' => 'required|image|mimes:jpeg,png,gif|max:5120', // Max size: 5MB
        ]);

        if ($request->hasFile('logo_image')) {
            $file = $request->file('logo_image');

            // Store file in storage/app/public/logos with tenant prefix
            $filename = 'tenant_' . $tenantId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('logos', $filename, 'public');

            // Get or create tenant settings
            $setting = Setting::where('tenant_id', $tenantId)->first();
            
            if (!$setting) {
                $setting = $this->createDefaultSettings($tenantId, $user->id);
            }

            // Delete old file if it exists
            if ($setting && $setting->logo) {
                Storage::disk('public')->delete('logos/' . $setting->logo);
            }

            // Save the filename
            $setting->update(['logo' => $filename]);

            return response()->json([
                'success' => true,
                'message' => __('auth._uploaded'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('auth.upload_failed'),
        ], 400);
    }

    public function uploadFavicon(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $request->validate([
            'favicon_image' => 'required|mimes:ico,png|max:2048', // Max size: 2MB
        ]);

        try {
            if ($request->hasFile('favicon_image')) {
                $file = $request->file('favicon_image');

                // Store in storage/app/public/favicons with tenant prefix
                $extension = $file->getClientOriginalExtension();
                $filename = 'favicon_tenant_' . $tenantId . '_' . time() . '.' . $extension;
                $path = $file->storeAs('favicons', $filename, 'public');

                // Get or create tenant settings
                $setting = Setting::where('tenant_id', $tenantId)->first();
                
                if (!$setting) {
                    $setting = $this->createDefaultSettings($tenantId, $user->id);
                }

                // Delete old favicon if it exists
                if ($setting && $setting->favicon) {
                    Storage::disk('public')->delete('favicons/' . $setting->favicon);
                }

                // Save the filename
                $setting->update(['favicon' => $filename]);

                return response()->json([
                    'success' => true,
                    'message' => __('auth._uploaded'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('auth.upload_failed'),
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Upload Favicon Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Favicon upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}