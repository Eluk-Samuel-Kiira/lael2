<?php
// app/Http/Controllers/Tenant/LeaveController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    /**
     * Display leave calendar and list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view leave')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $query = Leave::with(['employee', 'approver'])
            ->where('tenant_id', $tenantId);

        // Filters
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('employee_id') && !empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('leave_type') && !empty($request->leave_type)) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('end_date', '<=', $request->date_to);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();

        // Get employees for filter dropdown
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name']);

        // Get statistics
        $statistics = $this->getStatistics($tenantId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'leaves' => $leaves,
                'statistics' => $statistics,
            ]);
        }

        return view('department.leave-index', [
            'leaves' => $leaves,
            'employees' => $employees,
            'statistics' => $statistics,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Get calendar events
     */
    public function getCalendarEvents(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = Leave::with('employee')
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['approved', 'ongoing']);

            if ($request->has('start')) {
                $query->where('end_date', '>=', $request->start);
            }

            if ($request->has('end')) {
                $query->where('start_date', '<=', $request->end);
            }

            $leaves = $query->get();

            $events = $leaves->map(function ($leave) {
                $colors = [
                    'annual' => '#009ef7',
                    'sick' => '#f6c000',
                    'maternity' => '#7239ea',
                    'paternity' => '#50cd89',
                    'bereavement' => '#7e8299',
                    'study' => '#1c3253',
                    'unpaid' => '#b5b5c3',
                ];

                return [
                    'id' => $leave->id,
                    'title' => $leave->employee->first_name . ' ' . $leave->employee->last_name . ' - ' . $leave->leave_type_label,
                    'start' => $leave->start_date->format('Y-m-d'),
                    'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar needs exclusive end date
                    'backgroundColor' => $colors[$leave->leave_type] ?? '#009ef7',
                    'borderColor' => $colors[$leave->leave_type] ?? '#009ef7',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'employee_name' => $leave->employee->first_name . ' ' . $leave->employee->last_name,
                        'employee_id' => $leave->employee_id,
                        'leave_type' => $leave->leave_type_label,
                        'status' => $leave->status,
                        'is_paid' => $leave->is_paid,
                        'total_days' => $leave->total_days,
                        'reason' => $leave->reason,
                    ],
                ];
            });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error('Error fetching calendar events', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch events'], 500);
        }
    }

    /**
     * Store leave request
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('create leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type' => 'required|in:annual,sick,maternity,paternity,bereavement,study,unpaid,other',
                'custom_type' => 'required_if:leave_type,other|nullable|string|max:100',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|string|max:500',
                'is_paid' => 'sometimes|boolean',
                'alternate_contact' => 'nullable|string|max:20',
                'emergency_contact' => 'nullable|string|max:20',
                'handover_notes' => 'nullable|string',
                'handover_to' => 'nullable|array',
                'handover_to.*' => 'exists:employees,id',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Check if employee belongs to tenant
            $employee = Employee::where('id', $validated['employee_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.employee_not_found'),
                ]);
            }

            // Check for overlapping leave
            $overlapping = Leave::where('employee_id', $validated['employee_id'])
                ->whereIn('status', ['approved', 'ongoing', 'pending'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.overlapping_leave'),
                ]);
            }

            // Handle attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments/' . $employee->id, 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }

            DB::beginTransaction();

            $leave = Leave::create([
                'employee_id' => $validated['employee_id'],
                'tenant_id' => $tenantId,
                'leave_type' => $validated['leave_type'],
                'custom_type' => $validated['custom_type'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'is_paid' => $request->has('is_paid'),
                'alternate_contact' => $validated['alternate_contact'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'handover_notes' => $validated['handover_notes'] ?? null,
                'handover_to' => $validated['handover_to'] ?? null,
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
                'status' => 'pending',
                'applied_at' => now(),
            ]);

            DB::commit();

            // Send notification to managers
            $this->sendLeaveRequestNotification($leave);

            return response()->json([
                'success' => true,
                'message' => __('payments.request_submitted'),
                'leave' => $leave->load('employee'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation error',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating leave', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_creating') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update leave request
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('edit leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $leave = Leave::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Only pending leaves can be edited
            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_edit_processed'),
                ]);
            }

            $validated = $request->validate([
                'leave_type' => 'required|in:annual,sick,maternity,paternity,bereavement,study,unpaid,other',
                'custom_type' => 'required_if:leave_type,other|nullable|string|max:100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|string|max:500',
                'is_paid' => 'sometimes|boolean',
                'alternate_contact' => 'nullable|string|max:20',
                'emergency_contact' => 'nullable|string|max:20',
                'handover_notes' => 'nullable|string',
                'handover_to' => 'nullable|array',
                'handover_to.*' => 'exists:employees,id',
            ]);

            // Check for overlapping leave (excluding current)
            $overlapping = Leave::where('employee_id', $leave->employee_id)
                ->where('id', '!=', $id)
                ->whereIn('status', ['approved', 'ongoing', 'pending'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.overlapping_leave'),
                ]);
            }

            $leave->update([
                'leave_type' => $validated['leave_type'],
                'custom_type' => $validated['custom_type'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'is_paid' => $request->has('is_paid'),
                'alternate_contact' => $validated['alternate_contact'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'handover_notes' => $validated['handover_notes'] ?? null,
                'handover_to' => $validated['handover_to'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('payments.updated'),
                'leave' => $leave->fresh()->load('employee'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating leave', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_updating') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve leave
     */
    public function approve($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('approve leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $leave = Leave::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.already_processed'),
                ]);
            }

            DB::beginTransaction();

            $leave->approve($user->id);

            DB::commit();

            // Send notification to employee
            $this->sendLeaveApprovedNotification($leave);

            return response()->json([
                'success' => true,
                'message' => __('payments.approved'),
                'leave' => $leave->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving leave', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_approving') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject leave
     */
    public function reject(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('reject leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $leave = Leave::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.already_processed'),
                ]);
            }

            DB::beginTransaction();

            $leave->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('payments.rejected'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation error',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting leave', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_rejecting') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel leave
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('cancel leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $leave = Leave::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Can only cancel pending or approved leaves that haven't started
            if (!in_array($leave->status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_cancel'),
                ]);
            }

            if ($leave->status === 'approved' && $leave->start_date->lte(now())) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_cancel_started'),
                ]);
            }

            DB::beginTransaction();

            $leave->cancel();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('payments.cancelled'),
                'leave' => $leave->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling leave', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_cancelling') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete leave
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            if (!$user->hasPermissionTo('delete leave')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $leave = Leave::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            // Only pending leaves can be deleted
            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.cannot_delete_processed'),
                ]);
            }

            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => __('payments.deleted'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting leave', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => __('payments.error_deleting') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get leave statistics
     */
    private function getStatistics($tenantId)
    {
        $today = now()->startOfDay();

        return [
            'pending_count' => Leave::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->count(),
            
            'ongoing_count' => Leave::where('tenant_id', $tenantId)
                ->where('status', 'ongoing')
                ->count(),
            
            'approved_upcoming' => Leave::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->where('start_date', '>', $today)
                ->count(),
            
            'completed_this_month' => Leave::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereMonth('end_date', $today->month)
                ->whereYear('end_date', $today->year)
                ->count(),
            
            'total_days_this_month' => Leave::where('tenant_id', $tenantId)
                ->whereIn('status', ['approved', 'ongoing', 'completed'])
                ->where(function($q) use ($today) {
                    $q->whereMonth('start_date', $today->month)
                      ->orWhereMonth('end_date', $today->month);
                })
                ->get()
                ->sum(function($leave) use ($today) {
                    if ($leave->start_date->month == $today->month && $leave->end_date->month == $today->month) {
                        return $leave->total_days;
                    }
                    
                    if ($leave->start_date->month == $today->month) {
                        return $leave->start_date->diffInDays($leave->start_date->copy()->endOfMonth()) + 1;
                    }
                    
                    if ($leave->end_date->month == $today->month) {
                        return $leave->end_date->copy()->startOfMonth()->diffInDays($leave->end_date) + 1;
                    }
                    
                    return $today->daysInMonth;
                }),
        ];
    }

    /**
     * Notification methods (implement as needed)
     */
    private function sendLeaveRequestNotification($leave) {}
    private function sendLeaveApprovedNotification($leave) {}
    private function sendLeaveRejectedNotification($leave) {}
}