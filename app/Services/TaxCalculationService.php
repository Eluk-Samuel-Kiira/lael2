<?php
// app/Services/TaxCalculationService.php

namespace App\Services;

use App\Models\Tax;
use App\Models\Employee;
use App\Models\EmployeePayment;
use Illuminate\Support\Collection;

class TaxCalculationService
{
    /**
     * Calculate taxes for an employee payment
     *
     * @param float $grossAmount
     * @param array $selectedTaxIds
     * @param int $tenantId
     * @param Employee|null $employee
     * @return array
     */
    public function calculateTaxes(float $grossAmount, array $selectedTaxIds, int $tenantId, ?Employee $employee = null): array
    {
        $taxes = Tax::whereIn('id', $selectedTaxIds)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $totalTaxAmount = 0;
        $appliedTaxes = [];

        foreach ($taxes as $tax) {
            $taxAmount = $this->calculateTaxAmount($tax, $grossAmount, $employee);
            $totalTaxAmount += $taxAmount;
            
            $appliedTaxes[] = [
                'tax_id' => $tax->id,
                'tax_name' => $tax->name,
                'tax_code' => $tax->code,
                'rate' => $tax->rate,
                'type' => $tax->type,
                'amount' => $taxAmount,
                'is_computed' => true
            ];
        }

        $netAmount = $grossAmount - $totalTaxAmount;

        return [
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'total_tax_amount' => $totalTaxAmount,
            'applied_taxes' => $appliedTaxes,
            'tax_breakdown' => $this->formatTaxBreakdown($appliedTaxes)
        ];
    }

    /**
     * Calculate individual tax amount
     *
     * @param Tax $tax
     * @param float $grossAmount
     * @param Employee|null $employee
     * @return float
     */
    private function calculateTaxAmount(Tax $tax, float $grossAmount, ?Employee $employee = null): float
    {
        if ($tax->type === 'percentage') {
            return round($grossAmount * ($tax->rate / 100), 2);
        } else {
            // Fixed amount tax
            return round($tax->rate, 2);
        }
    }

    /**
     * Format tax breakdown for display
     *
     * @param array $appliedTaxes
     * @return array
     */
    private function formatTaxBreakdown(array $appliedTaxes): array
    {
        $breakdown = [];
        
        foreach ($appliedTaxes as $tax) {
            $breakdown[] = [
                'label' => $tax['tax_name'] . ' (' . $tax['tax_code'] . ')',
                'rate' => $tax['rate'] . ($tax['type'] === 'percentage' ? '%' : ' fixed'),
                'amount' => number_format($tax['amount'], 2),
                'currency' => 'USD' // You might want to make this dynamic
            ];
        }

        return $breakdown;
    }

    /**
     * Get available taxes for tenant
     *
     * @param int $tenantId
     * @return Collection
     */
    public function getAvailableTaxes(int $tenantId): Collection
    {
        return Tax::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(function ($tax) {
                return [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'code' => $tax->code,
                    'rate' => $tax->rate,
                    'type' => $tax->type,
                    'display_rate' => $tax->type === 'percentage' ? $tax->rate . '%' : '$' . number_format($tax->rate, 2),
                    'is_active' => $tax->is_active
                ];
            });
    }

    /**
     * Recalculate payment taxes
     *
     * @param EmployeePayment $payment
     * @param array $selectedTaxIds
     * @return EmployeePayment
     */
    public function recalculatePaymentTaxes(EmployeePayment $payment, array $selectedTaxIds): EmployeePayment
    {
        $grossAmount = $payment->gross_amount ?? $payment->amount;
        
        $calculation = $this->calculateTaxes(
            $grossAmount,
            $selectedTaxIds,
            $payment->tenant_id,
            $payment->employee
        );

        $payment->gross_amount = $calculation['gross_amount'];
        $payment->net_amount = $calculation['net_amount'];
        $payment->total_tax_amount = $calculation['total_tax_amount'];
        $payment->applied_taxes = $calculation['applied_taxes'];
        $payment->is_tax_computed = true;
        
        // Update the main amount to net amount
        $payment->amount = $calculation['net_amount'];

        return $payment;
    }
}