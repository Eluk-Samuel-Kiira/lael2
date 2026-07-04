<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display public invoice
     */
    public function show($token)
    {
        $invoice = Invoice::with(['order.orderItems', 'customer'])
            ->where('public_token', $token)
            ->firstOrFail();
            
        // Mark as viewed if not already
        if ($invoice->status === 'sent') {
            $invoice->markAsViewed();
        }
        
        return view('public.invoice.show', compact('invoice'));
    }
    
    /**
     * Show payment page for public invoice
     */
    public function pay($token)
    {
        $invoice = Invoice::with(['order.orderItems', 'customer'])
            ->where('public_token', $token)
            ->firstOrFail();
            
        if ($invoice->isPaid()) {
            return redirect()->route('public.invoice.show', $token)
                ->with('message', 'This invoice has already been paid.');
        }
        
        // Get available payment methods
        $paymentMethods = PaymentMethod::where('tenant_id', $invoice->tenant_id)
            ->where('is_active', 1)
            ->get();
        
        return view('public.invoice.pay', compact('invoice', 'paymentMethods'));
    }
    
    /**
     * Process payment for public invoice
     */
    public function processPayment(Request $request, $token)
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();
        
        // Validate payment
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
        ]);
        
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        
        try {
            // Here you would integrate with your payment gateway
            // For example: Stripe, Flutterwave, M-Pesa, etc.
            
            // For now, we'll just record the payment
            $invoice->recordPayment($request->amount);
            
            // Record payment in database
            $invoice->payments()->create([
                'amount' => $request->amount,
                'payment_method_id' => $paymentMethod->id,
                'transaction_id' => 'PUB_' . Str::random(16),
                'status' => 'completed',
                'type' => $request->amount >= $invoice->balance_due ? 'full' : 'partial',
                'payment_date' => now(),
                'notes' => 'Online payment via public link',
                'metadata' => [
                    'payment_via' => 'public_link',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'redirect' => route('public.invoice.show', $token),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Public payment failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'token' => $token
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again or contact support.',
            ], 500);
        }
    }
}