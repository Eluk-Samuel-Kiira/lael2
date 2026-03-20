<?php
// app/Models/Leave.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'approved_by',
        'leave_type',
        'custom_type',
        'start_date',
        'end_date',
        'status',
        'is_paid',
        'deduction_amount',
        'is_deducted_from_salary',
        'reason',
        'rejection_reason',
        'notes',
        'attachments',
        'alternate_contact',
        'emergency_contact',
        'handover_notes',
        'handover_to',
        'applied_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applied_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_paid' => 'boolean',
        'is_deducted_from_salary' => 'boolean',
        'deduction_amount' => 'decimal:2',
        'attachments' => 'array',
        'handover_to' => 'array',
    ];
    

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->where(function($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end])
              ->orWhere(function($sub) use ($start, $end) {
                  $sub->where('start_date', '<=', $start)
                       ->where('end_date', '>=', $end);
              });
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    /**
     * Accessors
     */
    public function getTotalDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'info',
            'ongoing' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
        ];
        
        $color = $colors[$this->status] ?? 'secondary';
        
        return '<span class="badge badge-light-' . $color . ' py-2 px-3 fs-7">' . 
               ucfirst($this->status) . 
               '</span>';
    }

    public function getTypeBadgeAttribute()
    {
        $colors = [
            'annual' => 'primary',
            'sick' => 'warning',
            'maternity' => 'success',
            'paternity' => 'info',
            'bereavement' => 'dark',
            'study' => 'info',
            'unpaid' => 'secondary',
            'other' => 'secondary',
        ];
        
        $color = $colors[$this->leave_type] ?? 'secondary';
        $label = $this->leave_type_label;
        
        return '<span class="badge badge-light-' . $color . ' py-2 px-3 fs-7">' . 
               $label . 
               '</span>';
    }

    public function getLeaveTypeLabelAttribute()
    {
        $types = [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'bereavement' => 'Bereavement Leave',
            'study' => 'Study Leave',
            'unpaid' => 'Unpaid Leave',
            'other' => $this->custom_type ?? 'Other',
        ];
        
        return $types[$this->leave_type] ?? ucfirst($this->leave_type);
    }

    public function getProgressAttribute()
    {
        if ($this->status === 'completed') return 100;
        if ($this->status === 'pending') return 0;
        
        $today = now();
        
        if ($today->lt($this->start_date)) return 0;
        if ($today->gt($this->end_date)) return 100;
        
        $totalDays = $this->total_days;
        $elapsedDays = $today->diffInDays($this->start_date);
        
        return round(($elapsedDays / $totalDays) * 100);
    }

    public function getIsOngoingAttribute()
    {
        $today = now()->startOfDay();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Methods
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject($reason, $userId)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function updateStatusBasedOnDates()
    {
        $today = now()->startOfDay();
        
        if ($this->status === 'approved') {
            if ($today->between($this->start_date, $this->end_date)) {
                $this->update(['status' => 'ongoing']);
            } elseif ($today->gt($this->end_date)) {
                $this->update(['status' => 'completed']);
            }
        }
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::saving(function ($leave) {
            // Auto-update status based on dates when saving
            $today = now()->startOfDay();
            
            if ($leave->status === 'approved') {
                if ($today->gt($leave->end_date)) {
                    $leave->status = 'completed';
                } elseif ($today->between($leave->start_date, $leave->end_date)) {
                    $leave->status = 'ongoing';
                }
            }
        });
    }


    /**
     * Get attachments as array safely
     */
    public function getAttachmentsArrayAttribute(): array
    {
        if (is_null($this->attachments)) {
            return [];
        }
        
        if (is_array($this->attachments)) {
            return $this->attachments;
        }
        
        if (is_string($this->attachments)) {
            $decoded = json_decode($this->attachments, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Get handover_to as array safely
     */
    public function getHandoverToArrayAttribute(): array
    {
        if (is_null($this->handover_to)) {
            return [];
        }
        
        if (is_array($this->handover_to)) {
            return $this->handover_to;
        }
        
        if (is_string($this->handover_to)) {
            $decoded = json_decode($this->handover_to, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return count($this->attachments_array);
    }
}