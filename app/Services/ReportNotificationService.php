<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaymentTransactionLog;
use App\Models\PaymentMethod;
use App\Models\Location;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\ReportSummaryMail;

class ReportNotificationService
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Get report data for a specific period
     */
    public function getReportData($period, $tenantId, $locationId = null)
    {
        $now = now()->setTimezone('Africa/Nairobi');
        
        switch ($period) {
            case 'daily':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Today';
                break;
            case 'weekly':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'This Week';
                break;
            case 'monthly':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'This Month';
                break;
            default:
                throw new \Exception("Invalid period: {$period}");
        }

        // Build base query
        $query = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED');

        // Apply location filter if specified
        if ($locationId) {
            $query->whereHas('paymentMethod', function($q) use ($locationId) {
                $q->where(function($sub) use ($locationId) {
                    $sub->whereNull('location_id')
                        ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$locationId)]);
                });
            });
        }

        $transactions = $query->get();

        // Calculate summary
        $totalDeposits = $transactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
        $totalWithdrawals = $transactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
        $netProfit = $totalDeposits - $totalWithdrawals;

        // Get location breakdown
        $locationBreakdown = [];
        if (!$locationId) {
            $locationData = PaymentTransactionLog::where('tenant_id', $tenantId)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'COMPLETED')
                ->with(['paymentMethod'])
                ->get()
                ->groupBy(function($item) {
                    return $item->paymentMethod->location_id ?? 'unassigned';
                });

            foreach ($locationData as $locId => $locTransactions) {
                $locDeposits = $locTransactions->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
                $locWithdrawals = $locTransactions->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
                
                $locationName = 'Unassigned';
                if ($locId !== 'unassigned') {
                    $location = Location::find($locId);
                    $locationName = $location ? $location->name : 'Unknown';
                }

                $locationBreakdown[] = [
                    'location_name' => $locationName,
                    'deposits' => $locDeposits,
                    'withdrawals' => $locWithdrawals,
                    'net' => $locDeposits - $locWithdrawals,
                    'transaction_count' => $locTransactions->count(),
                ];
            }
        }

        // Get payment method breakdown
        $methodBreakdown = $transactions->groupBy('payment_method_id')
            ->map(function($group, $methodId) {
                $method = PaymentMethod::find($methodId);
                $deposits = $group->whereIn('transaction_type', ['DEPOSIT', 'TRANSFER_IN', 'REFUND'])->sum('amount');
                $withdrawals = $group->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER_OUT', 'FEE'])->sum('amount');
                
                return [
                    'method_name' => $method ? $method->name : 'Unknown',
                    'deposits' => $deposits,
                    'withdrawals' => $withdrawals,
                    'net' => $deposits - $withdrawals,
                    'transaction_count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();

        // Get top users
        $topUsers = PaymentTransactionLog::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->whereNotNull('user_id')
            ->select(
                'user_id',
                DB::raw('SUM(CASE WHEN transaction_type IN ("DEPOSIT", "TRANSFER_IN", "REFUND") THEN amount ELSE 0 END) as total_deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("WITHDRAWAL", "TRANSFER_OUT", "FEE") THEN amount ELSE 0 END) as total_withdrawals'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('user_id')
            ->get()
            ->map(function($item) {
                $user = User::find($item->user_id);
                return [
                    'user_name' => $user ? $user->name : 'Unknown',
                    'total_deposits' => $item->total_deposits,
                    'total_withdrawals' => $item->total_withdrawals,
                    'net' => $item->total_deposits - $item->total_withdrawals,
                    'transaction_count' => $item->transaction_count,
                ];
            })
            ->sortByDesc('net')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'total_transactions' => $transactions->count(),
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'net_profit' => $netProfit,
            'profit_percentage' => $totalDeposits > 0 ? ($netProfit / $totalDeposits) * 100 : 0,
            'location_breakdown' => $locationBreakdown,
            'method_breakdown' => $methodBreakdown,
            'top_users' => $topUsers,
            'currency_symbol' => currency_symbol(),
        ];
    }

    /**
     * Send report to users via email and WhatsApp
     */
    public function sendReport($period, $tenantId, $locationId = null, $channels = ['email', 'whatsapp'])
    {
        $reportData = $this->getReportData($period, $tenantId, $locationId);
        
        // Get all admin users for this tenant
        $users = User::where('tenant_id', $tenantId)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'manager']);
            })
            ->where('status', 'active')
            ->get();

        $results = [];

        foreach ($users as $user) {
            $sent = [];

            // Send email
            if (in_array('email', $channels) && $user->email) {
                try {
                    Mail::to($user->email)->send(new ReportSummaryMail($reportData, $user, $period));
                    $sent[] = 'email';
                    Log::info("Report email sent to {$user->email} for tenant {$tenantId}");
                } catch (\Exception $e) {
                    Log::error("Failed to send report email to {$user->email}: " . $e->getMessage());
                }
            }

            // Send WhatsApp
            if (in_array('whatsapp', $channels) && $user->telephone_number) {
                try {
                    // Format phone number (ensure it has country code)
                    $phone = $this->formatPhoneNumber($user->telephone_number);
                    
                    // Build template parameters for WhatsApp
                    $templateParams = $this->buildWhatsAppTemplateParams($reportData, $user);
                    
                    // Send using your existing MessagingService
                    $result = $this->messagingService->sendWhatsApp($phone, $templateParams);
                    
                    if ($result['success']) {
                        $sent[] = 'whatsapp';
                        Log::info("Report WhatsApp sent to {$user->telephone_number} for tenant {$tenantId}", [
                            'provider_message_id' => $result['provider_message_id'] ?? null
                        ]);
                    } else {
                        Log::error("Failed to send report WhatsApp to {$user->telephone_number}: " . ($result['error'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send report WhatsApp to {$user->telephone_number}: " . $e->getMessage());
                }
            }

            $results[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->telephone_number,
                'sent_via' => $sent,
            ];
        }

        return $results;
    }

    /**
     * Build WhatsApp template parameters for MessageBird
     */
    public function buildWhatsAppTemplateParams($reportData, $user)
    {
        $netStatus = $reportData['net_profit'] >= 0 ? 'PROFIT' : 'LOSS';
        $netEmoji = $reportData['net_profit'] >= 0 ? '💰' : '⚠️';
        
        // Format period
        $startDate = date('M d', strtotime($reportData['start_date']));
        $endDate = date('M d, Y', strtotime($reportData['end_date']));
        
        // Build location summary
        $locationSummary = '';
        if (!empty($reportData['location_breakdown'])) {
            $locationSummary = "\n📍 *Location Breakdown:*\n";
            foreach ($reportData['location_breakdown'] as $loc) {
                $icon = $loc['net'] >= 0 ? '✅' : '❌';
                $locationSummary .= "  {$icon} {$loc['location_name']}: " . 
                    $reportData['currency_symbol'] . number_format($loc['net'], 2) . "\n";
            }
        }
        
        // Build top performers summary
        $topUsersSummary = '';
        if (!empty($reportData['top_users'])) {
            $topUsersSummary = "\n🏆 *Top Performers:*\n";
            foreach ($reportData['top_users'] as $index => $u) {
                $icon = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : '👤'));
                $status = $u['net'] >= 0 ? '✅' : '❌';
                $topUsersSummary .= "  {$icon} {$u['user_name']}: " . 
                    $reportData['currency_symbol'] . number_format($u['net'], 2) . "\n";
            }
        }
        
        // Return template parameters for MessageBird
        // The template should have placeholders like {{1}}, {{2}}, {{3}}, etc.
        return [
            'user_name' => $user->name,
            'period' => $reportData['period_label'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_deposits' => $reportData['currency_symbol'] . number_format($reportData['total_deposits'], 2),
            'total_withdrawals' => $reportData['currency_symbol'] . number_format($reportData['total_withdrawals'], 2),
            'net' => $reportData['currency_symbol'] . number_format(abs($reportData['net_profit']), 2),
            'net_status' => $netStatus,
            'profit_margin' => number_format($reportData['profit_percentage'], 1) . '%',
            'total_transactions' => number_format($reportData['total_transactions']),
            'location_summary' => $locationSummary,
            'top_users_summary' => $topUsersSummary,
            'generated_at' => now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . ' EAT',
        ];
    }

    /**
     * Build WhatsApp message (fallback if template not available)
     */
    public function buildWhatsAppMessage($reportData, $user)
    {
        $netSymbol = $reportData['net_profit'] >= 0 ? '💰' : '⚠️';
        $netStatus = $reportData['net_profit'] >= 0 ? 'PROFIT' : 'LOSS';
        
        $message = "📊 *{$reportData['period_label']} Report*\n\n";
        $message .= "👤 *User:* {$user->name}\n";
        $message .= "📅 *Period:* " . date('M d', strtotime($reportData['start_date'])) . " - " . date('M d, Y', strtotime($reportData['end_date'])) . "\n";
        $message .= "──────────────────\n";
        $message .= "📈 *Total Deposits:* {$reportData['currency_symbol']}" . number_format($reportData['total_deposits'], 2) . "\n";
        $message .= "📉 *Total Withdrawals:* {$reportData['currency_symbol']}" . number_format($reportData['total_withdrawals'], 2) . "\n";
        $message .= "{$netSymbol} *Net {$netStatus}:* {$reportData['currency_symbol']}" . number_format(abs($reportData['net_profit']), 2) . "\n";
        $message .= "📊 *Profit Margin:* " . number_format($reportData['profit_percentage'], 1) . "%\n";
        $message .= "🔄 *Transactions:* " . number_format($reportData['total_transactions']) . "\n";
        
        // Location breakdown
        if (!empty($reportData['location_breakdown'])) {
            $message .= "\n📍 *Location Breakdown:*\n";
            foreach ($reportData['location_breakdown'] as $location) {
                $locStatus = $location['net'] >= 0 ? '✅' : '❌';
                $message .= "  {$locStatus} {$location['location_name']}: {$reportData['currency_symbol']}" . number_format($location['net'], 2) . "\n";
            }
        }
        
        // Top users
        if (!empty($reportData['top_users'])) {
            $message .= "\n🏆 *Top Performers:*\n";
            foreach ($reportData['top_users'] as $index => $userData) {
                $icon = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : '👤'));
                $status = $userData['net'] >= 0 ? '✅' : '❌';
                $message .= "  {$icon} {$userData['user_name']}: {$status} {$reportData['currency_symbol']}" . number_format($userData['net'], 2) . "\n";
            }
        }

        $message .= "\n──────────────────\n";
        $message .= "📅 Generated: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT";

        return $message;
    }

    /**
     * Format phone number for WhatsApp (ensure it has country code)
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If phone doesn't start with country code, add default (e.g., 254 for Kenya)
        // You may want to make this configurable per tenant
        if (strlen($phone) === 9) {
            $phone = '254' . $phone; // Kenya default
        }
        
        // Ensure it starts with +
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }
        
        return '+' . $phone;
    }

    // 📊 *{{1}} Report*

    // 👤 *User:* {{2}}
    // 📅 *Period:* {{3}} - {{4}}

    // ──────────────────
    // 📈 *Total Deposits:* {{5}}
    // 📉 *Total Withdrawals:* {{6}}
    // {{7}} *Net {{8}}:* {{9}}
    // 📊 *Profit Margin:* {{10}}
    // 🔄 *Transactions:* {{11}}

    // {{12}}
    // {{13}}

    // ──────────────────
    // 📅 Generated: {{14}}


}