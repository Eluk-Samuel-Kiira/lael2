<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Employee };
use Illuminate\Support\Facades\{ Mail, Auth };

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build the query
        $query = Employee::query();
        
        // If user is NOT super_admin, filter by tenant
        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', current_tenant_id());
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

            if ($employee->termination_date) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.terminated_empl'),
                ]);
            }

            // Validate the request
            $validated = $request->validate([
                'salary' => 'required|numeric|min:0',
                'salary_type' => 'required|in:hourly,weekly,monthly,annual',
                'hire_date' => 'required|date',
                'termination_date' => 'nullable|date',
            ]);

            // Update employee fields
            $employee->update([
                'salary' => $validated['salary'],
                'salary_type' => $validated['salary_type'],
                'hire_date' => $validated['hire_date'],
                'termination_date' => $validated['termination_date'],
                'is_active' => $validated['termination_date'] ? false : true,
            ]);

            // Sync with User model if user exists and belongs to same tenant
            if ($employee->user && $employee->user->tenant_id === $tenantId) {
                $employee->user->update([
                    'job_title' => $employee->job_title, 
                    'first_name' => $employee->first_name, 
                    'last_name' => $employee->last_name, 
                    'email' => $employee->email, 
                    'phone' => $employee->telephone_number, 
                    'department_id' => $employee->department_id, 
                ]);
            }

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
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating employee', ['error' => $e->getMessage(), 'employee_id' => $id]);
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
}
