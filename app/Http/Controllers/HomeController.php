<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Artisan, Hash, Mail, Auth, Log, DB, Config  };


class HomeController extends Controller
{
    /**
     * Help Center Page
     */
    public function help()
    {
        return view('home.help');
    }

    /**
     * Documentation Page
     */
    public function docs()
    {
        return view('home.docs');
    }

    /**
     * Blog Page
     */
    public function blog()
    {
        return view('home.blog');
    }

    /**
     * Privacy Policy Page
     */
    public function privacyPolicy()
    {
        return view('home.privacy-policy');
    }

    /**
     * Terms of Service Page
     */
    public function termsOfService()
    {
        return view('home.terms-of-service');
    }


    

    public function send(Request $request)
    {
        // Log::info($request);
        
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'phone'    => 'nullable|string|max:30',
            'business' => 'nullable|string|max:100',
            'plan'     => 'nullable|string|max:100',
            'message'  => 'nullable|string|max:2000',
        ]);

        try {
            Config::set('mail.mailers.stardena', [
                'transport'  => 'smtp',
                'host'       => env('STARDENA_POS_MAIL_HOST'),
                'port'       => env('STARDENA_POS_MAIL_PORT'),
                'encryption' => env('STARDENA_POS_MAIL_ENCRYPTION'),
                'username'   => env('STARDENA_POS_MAIL_USERNAME'),
                'password'   => env('STARDENA_POS_MAIL_PASSWORD'),
                'timeout'    => null,
                'auth_mode'  => null,
            ]);

            Config::set('mail.from.address', env('STARDENA_POS_MAIL_FROM_ADDRESS'));
            Config::set('mail.from.name', env('STARDENA_POS_MAIL_FROM_NAME'));

            $stardenaMailer = Mail::mailer('stardena');

            $toEmail   = env('STARDENA_POS_MAIL_FROM_ADDRESS');
            $fromEmail = env('STARDENA_POS_MAIL_FROM_ADDRESS');
            $fromName  = env('STARDENA_POS_MAIL_FROM_NAME');
            $ccEmail   = 'samuelkiiraeluk@gmail.com';

            $subject = "New STARPOSS Inquiry — " . ($validated['plan'] ?? 'No Plan') . " — {$validated['name']}";

            $html = "
            <html><body style='font-family:Arial,sans-serif;color:#1A2535;'>
            <div style='max-width:600px;margin:0 auto;background:#fff;border:1px solid #eee;border-radius:12px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg,#FF6B2C,#E8541A);padding:24px 32px;'>
                    <h2 style='color:#fff;margin:0;font-size:1.4rem;'>⭐ New STARPOSS Inquiry</h2>
                    <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:0.9rem;'>Received from starposs.stardena.org</p>
                </div>
                <div style='padding:32px;'>
                    <table style='width:100%;border-collapse:collapse;'>
                        <tr><td style='padding:10px 0;color:#888;font-size:0.85rem;width:130px;'>Name</td>
                            <td style='padding:10px 0;font-weight:600;'>{$validated['name']}</td>
                        </tr>
                        <tr style='border-top:1px solid #f0f0f0;'>
                            <td style='padding:10px 0;color:#888;font-size:0.85rem;'>Email</td>
                            <td style='padding:10px 0;'><a href='mailto:{$validated['email']}'>{$validated['email']}</a></td>
                        </tr>
                        <tr style='border-top:1px solid #f0f0f0;'>
                            <td style='padding:10px 0;color:#888;font-size:0.85rem;'>Phone</td>
                            <td style='padding:10px 0;'>" . ($validated['phone'] ?? 'Not provided') . "</td>
                        </tr>
                        <tr style='border-top:1px solid #f0f0f0;'>
                            <td style='padding:10px 0;color:#888;font-size:0.85rem;'>Business Type</td>
                            <td style='padding:10px 0;'>" . ($validated['business'] ?? 'Not specified') . "</td>
                        </tr>
                        <tr style='border-top:1px solid #f0f0f0;'>
                            <td style='padding:10px 0;color:#888;font-size:0.85rem;'>Interested Plan</td>
                            <td style='padding:10px 0;'><strong style='color:#FF6B2C;'>" . ($validated['plan'] ?? 'Not specified') . "</strong></td>
                        </tr>
                    </table>
                    " . (!empty($validated['message']) ? "
                    <div style='margin-top:20px;background:#f9f9f9;border-radius:8px;padding:16px;border-left:4px solid #FF6B2C;'>
                        <p style='margin:0 0 6px;font-weight:600;font-size:0.85rem;color:#888;'>MESSAGE:</p>
                        <p style='margin:0;font-size:0.95rem;'>{$validated['message']}</p>
                    </div>" : "") . "
                </div>
                <div style='background:#f8f8f8;padding:16px 32px;border-top:1px solid #eee;font-size:0.8rem;color:#aaa;'>
                    STARPOSS — by Stardena · pos@stardena.org · starposs.stardena.org
                </div>
            </div>
            </body></html>";

            $stardenaMailer->html($html, function ($mail) use ($toEmail, $ccEmail, $fromEmail, $fromName, $subject, $validated) {
                $mail->to($toEmail)
                    ->cc($ccEmail)
                    ->from($fromEmail, $fromName)
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject($subject);
            });

            Log::info('Email sent successfully to ' . $toEmail);

            return response()->json(['success' => true, 'message' => 'Inquiry sent successfully.']);

        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Failed to send inquiry: ' . $e->getMessage()], 500);
        }
    }


}
