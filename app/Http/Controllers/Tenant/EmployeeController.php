<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Employee, User };
use Illuminate\Support\Facades\{ Mail, Auth };

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('view employee')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }
        
        // Build the query
        $query = Employee::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        
        $employees = $query->latest()->get();
        
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'employeeUserIndexTable':
                return view('department.employee.component', [
                    'employees' => $employees,
                ]);
            default:
                return view('department.employee-index', [
                    'employees' => $employees,
                ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }
    


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            
            if (!$user->hasPermissionTo('edit employee')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            // Find the employee and ensure it belongs to tenant
            $employee = Employee::where('id', $id)
                            ->where('tenant_id', $tenantId)
                            ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth._not_found'),
                ]);
            }

            
            // Validate only employee-specific fields - BUT INCLUDE ALL FIELDS FROM FORM
            $validated = $request->validate([
                // These are coming from the form but should be ignored/optional
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'phone' => 'sometimes|string|max:20',
                
                // Personal Information (Employee specific)
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date|before:today',
                'residence' => 'nullable|string|max:500',
                
                // Employment Details
                'department_id' => 'nullable|exists:departments,id',
                'job_title' => 'nullable|string|max:255',
                'employee_type' => 'required|in:permanent,contract,casual,temporary,intern,probation',
                'hire_date' => 'required|date',
                'termination_date' => 'nullable|date|after_or_equal:hire_date',
                'is_active' => 'sometimes|boolean',
                
                // Salary Information
                'salary' => 'required|numeric|min:0',
                'salary_type' => 'required|in:hourly,weekly,monthly,quarterly,annual',
                'is_salary_recurring' => 'sometimes|boolean',
                'recurring_day' => 'nullable|required_if:is_salary_recurring,1|integer|min:1|max:31',
                
                // Tax & Social Security
                'nssf_number' => 'nullable|string|max:50|unique:employees,nssf_number,' . $id . ',id,tenant_id,' . $tenantId,
                'tin_number' => 'nullable|string|max:50|unique:employees,tin_number,' . $id . ',id,tenant_id,' . $tenantId,
                
                // Bank Details
                'bank_name' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|max:50',
                'bank_branch' => 'nullable|string|max:255',
                
                // Identification
                'id_type' => 'nullable|in:national_id,passport,drivers_license,voters_card,other',
                'id_number' => 'nullable|string|max:50',
                'qualification' => 'nullable|string|max:255',
                
                // Next of Kin
                'next_of_kin_name' => 'nullable|string|max:255',
                'next_of_kin_contact' => 'nullable|string|max:20',
                'next_of_kin_relationship' => 'nullable|string|max:100',
                
                // Notes
                'notes' => 'nullable|string|max:1000',
                
                // Documents (handled separately)
                'documents' => 'nullable',
                'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            ]);


            // If terminated, automatically set inactive
            if (!empty($validated['termination_date'])) {
                $validated['is_active'] = false;
            } elseif ($request->has('is_active')) {
                $validated['is_active'] = true;
            }

            // Update only employee table with validated data
            $employee->update($validated);

            \Log::info('Employee updated successfully', ['employee_id' => $id]);

            // Return success response
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'employeeUserIndexTable',
                'refresh' => false,
                'message' => __('auth._updated'),
                'redirect' => route('user.index'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the validation errors in detail
            \Log::error('Employee update validation failed', [
                'employee_id' => $id,
                'errors' => $e->errors(),
                'request_data' => $request->except(['_token', '_method'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating employee', [
                'error' => $e->getMessage(),
                'employee_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating employee: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function changeUserStatus(Request $request, $id) 
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        if (!$user->hasPermissionTo('update employee')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:1,0',  // Ensures only 1 or 0 are allowed
        ]);

        // Find the employee and ensure it belongs to tenant
        $employee = Employee::where('id', $id)
                        ->where('tenant_id', $tenantId)
                        ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('auth._not_found'),
            ]);
        }

        // Check if employee has a user with protected roles (super_admin or admin)
        if ($employee->user && $employee->user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => __('auth.protected_user_not_updatable_status'),
            ]);
        }

        // Update the employee status and handle termination date
        $employee->is_active = $validated['status'];
        
        // If changing status to active (1), set termination_date to null
        if ($validated['status'] == 1) {
            $employee->termination_date = null;
        } 
        // If changing status to inactive (0) and termination_date is null, set it to current date
        else if ($validated['status'] == 0 && !$employee->termination_date) {
            $employee->termination_date = now()->toDateString(); // or Carbon::today()
        }
        // If changing status to inactive (0) and already has termination_date, keep it as is
        
        if ($employee->save()) {
            return response()->json([
                'success' => true,
                'reload' => true,
                'componentId' => 'employeeUserIndexTable',
                'refresh' => false,
                'message' => __('auth._updated'),
                'redirect' => route('user.index'),
            ]);
        }

        // If status update failed
        return response()->json([
            'success' => false,
            'message' => __('auth.update_failed'),
        ]);
    }


    
        
    public function syncUsersToEmployees(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            
            if (!$user->hasPermissionTo('edit employee')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            // Get all users for this tenant
            $users = User::where('tenant_id', $tenantId)->get();
            
            $syncStats = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0
            ];

            foreach ($users as $user) {
                try {
                    // Check if employee exists for this user
                    $employee = Employee::where('user_id', $user->id)
                                    ->where('tenant_id', $tenantId)
                                    ->first();

                    $employeeData = [
                        'tenant_id' => $tenantId,
                        'user_id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->telephone_number,
                        'job_title' => $user->job_title,
                        // 'department_id' => $user->department_id,
                        'hire_date' => $user->created_at ?? now(),
                        'is_active' => $user->status === 'active',
                    ];

                    if ($employee) {
                        // Update existing employee
                        $employee->update($employeeData);
                        $syncStats['updated']++;
                    } else {
                        // Create new employee
                        $employeeData['hire_date'] = now();
                        Employee::create($employeeData);
                        $syncStats['created']++;
                    }

                } catch (\Exception $e) {
                    $syncStats['errors']++;
                    \Log::error('Error syncing user to employee', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Get counts for summary
            $totalUsers = $users->count();
            $totalEmployees = Employee::where('tenant_id', $tenantId)->count();

            return response()->json([
                'success' => true,
                'message' => __('auth.sync_completed'),
                'stats' => $syncStats,
                'summary' => [
                    'total_users' => $totalUsers,
                    'total_employees' => $totalEmployees,
                    'sync_date' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in sync users to employees', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('auth.sync_error') . ': ' . $e->getMessage()
            ], 500);
        }
    }
}
