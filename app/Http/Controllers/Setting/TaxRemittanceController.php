<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaxRemittanceController extends Controller
{
    public function remitTaxes(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            
            $request->validate([
                'tax_liability_ids' => 'required|array',
                'tax_liability_ids.*' => 'exists:tax_liabilities,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'remittance_date' => 'required|date',
                'reference' => 'nullable|string|max:100',
            ]);

            DB::beginTransaction();

            $taxLiabilities = TaxLiability::whereIn('id', $request->tax_liability_ids)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'overdue'])
                ->get();

            if ($taxLiabilities->isEmpty()) {
                throw new \Exception('No pending tax liabilities found');
            }

            $totalAmount = $taxLiabilities->sum('amount');
            $paymentMethod = PaymentMethod::findForTenant($request->payment_method_id, $tenantId);

            // Record the tax remittance transaction
            $transactionData = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type' => 'WITHDRAWAL',
                'transaction_category' => 'TAX_REMITTANCE',
                'amount' => $totalAmount,
                'currency_id' => $paymentMethod->currency_id ?? Currency::default()->id,
                'reference_table' => 'tax_liabilities',
                'reference_id' => implode(',', $request->tax_liability_ids),
                'description' => 'Tax remittance for period',
                'notes' => 'Monthly PAYE/Income Tax remittance',
                'metadata' => [
                    'remittance_date' => $request->remittance_date,
                    'reference' => $request->reference,
                    'tax_liability_count' => $taxLiabilities->count(),
                    'employee_count' => $taxLiabilities->pluck('employee_id')->unique()->count(),
                    'periods' => $taxLiabilities->groupBy('tax_year')->map(function($items) {
                        return $items->groupBy('tax_month')->keys();
                    }),
                ],
            ];

            $transaction = app('payment-transaction')->recordTransaction($transactionData);

            // Mark each liability as remitted
            foreach ($taxLiabilities as $liability) {
                $liability->markAsRemitted([
                    'remitted_at' => $request->remittance_date,
                    'remitted_by' => $user->id,
                    'remittance_reference' => $request->reference,
                    'remittance_transaction_ref' => $transaction->transaction_ref,
                    'remittance_payment_method_id' => $paymentMethod->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Taxes remitted successfully',
                'data' => [
                    'total_amount' => $totalAmount,
                    'transaction_ref' => $transaction->transaction_ref,
                    'liabilities_cleared' => $taxLiabilities->count(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tax remittance error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
