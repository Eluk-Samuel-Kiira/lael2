<?php
// app/Models/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'department_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'date_of_birth',
        'residence',
        'hire_date',
        'termination_date',
        'job_title',
        'employee_type',
        'salary',
        'salary_type',
        'is_salary_recurring',
        'recurring_day',
        'nssf_number',
        'tin_number',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'id_type',
        'id_number',
        'qualification',
        'next_of_kin_name',
        'next_of_kin_contact',
        'next_of_kin_relationship',
        'documents',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'is_salary_recurring' => 'boolean',
        'salary' => 'integer',
        'documents' => 'array',
        'recurring_day' => 'integer',
    ];

    protected $appends = ['full_name', 'age'];

    /**
     * Get the user associated with the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant that owns the employee.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the department that owns the employee.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the full name of the employee.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Calculate age from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return $this->date_of_birth->age;
    }

    /**
     * Get employment status label.
     */
    public function getEmploymentStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Terminated';
        }
        
        return match($this->employee_type) {
            'permanent' => 'Permanent',
            'contract' => 'Contract',
            'casual' => 'Casual',
            'temporary' => 'Temporary',
            'intern' => 'Intern',
            'probation' => 'Probation',
            default => 'Unknown',
        };
    }

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getSalaryAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setSalaryAttribute($value): void
    {
        $this->attributes['salary'] = to_base_currency($value);
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include employees of a given type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('employee_type', $type);
    }

    /**
     * Scope a query to only include employees with recurring salary.
     */
    public function scopeRecurringSalary($query)
    {
        return $query->where('is_salary_recurring', true);
    }

    /**
     * Get salary with currency symbol.
     */
    protected function salary(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
        );
    }

    /**
     * Get the payment day of month.
     */
    public function getPaymentDayAttribute(): ?string
    {
        if (!$this->is_salary_recurring || !$this->recurring_day) {
            return null;
        }
        
        $suffix = match(true) {
            in_array($this->recurring_day % 100, [11,12,13]) => 'th',
            $this->recurring_day % 10 == 1 => 'st',
            $this->recurring_day % 10 == 2 => 'nd',
            $this->recurring_day % 10 == 3 => 'rd',
            default => 'th',
        };
        
        return $this->recurring_day . $suffix . ' of each month';
    }
}