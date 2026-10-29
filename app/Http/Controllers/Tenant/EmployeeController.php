<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Employee, User, Department };
use Illuminate\Support\Facades\{ Mail, Auth };

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('view employee')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('department.employee-index', [
            'initialFilters' => [
                'search'   => (string) $request->get('search', ''),
                'per_page' => in_array((int) $request->get('per_page', 15), [15, 25, 50, 100], true)
                    ? (int) $request->get('per_page', 15)
                    : 15,
            ],
        ]);
    }

    public function data(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view employee')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = Employee::with(['user:id,profile_image', 'department:id,name']);

        if (!$user->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('job_title', 'like', "%{$s}%")
                ->orWhereHas('department', fn($d) => $d->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status') && in_array($request->status, ['1', '0'], true)) {
            $query->where('is_active', (int) $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->department_id);
        }

        $all = (clone $query)->get(['id', 'is_active']);
        $summary = [
            'total'    => $all->count(),
            'active'   => $all->where('is_active', 1)->count(),
            'inactive' => $all->where('is_active', 0)->count(),
        ];

        $paginator = $query->latest()->paginate($perPage);

        $rows = collect($paginator->items())->map(fn(Employee $e) => [
            'id'              => $e->id,
            'name'            => trim($e->first_name . ' ' . $e->last_name),
            'email'           => $e->email,
            'profile_image'   => employeeProfileImage($e->user->profile_image ?? null),
            'department_name' => $e->department?->name ?? '—',
            'salary'          => (float) $e->salary,
            'salary_type'     => $e->salary_type,
            'currency_symbol' => currency_symbol(),
            'hire_date'       => optional($e->hire_date)->format('d M Y'),
            'termination_date'=> optional($e->termination_date)->format('d M Y'),
            'is_active'       => (bool) $e->is_active,
            'documents_count' => $e->documents ? count(json_decode($e->documents, true) ?? []) : 0,
        ]);

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function drawerData(Request $request, $id)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('view employee')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }

        $employee = Employee::with(['user:id,name,profile_image', 'department:id,name'])
            ->when(!$user->hasRole('super_admin'), fn($q) => $q->where('tenant_id', $tenantId))
            ->find($id);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => __('auth._not_found')], 404);
        }

        $departments = Department::where('tenant_id', $tenantId)
            ->orderBy('name')->get(['id', 'name']);

        $documents = collect(json_decode($employee->documents ?? '[]', true) ?: [])->map(function ($d, $i) use ($employee) {
            $labels = [
                'cv' => 'CV/Resume', 'contract' => 'Contract', 'id_proof' => 'ID Proof',
                'certificate' => 'Certificate', 'photo' => 'Photo',
                'offer_letter' => 'Offer Letter', 'other' => 'Other',
            ];
            return [
                'index'         => $i,
                'name'          => $d['original_name'] ?? 'Document',
                'category'      => $labels[$d['category'] ?? $d['type'] ?? 'other'] ?? 'Other',
                'uploaded_at'   => isset($d['uploaded_at'])
                    ? \Carbon\Carbon::parse($d['uploaded_at'])->format('d M Y')
                    : '—',
                'view_url'      => route('employee.documents.view', ['employeeId' => $employee->id, 'index' => $i]),
                'download_url'  => route('employee.documents.download', ['employeeId' => $employee->id, 'index' => $i]),
            ];
        })->values();

        return response()->json([
            'success'  => true,
            'employee' => array_merge([
                'id'               => $employee->id,
                'first_name'       => $employee->first_name,
                'last_name'        => $employee->last_name,
                'name'             => trim($employee->first_name . ' ' . $employee->last_name),
                'email'            => $employee->email,
                'phone'            => $employee->phone,
                'profile_image'    => employeeProfileImage($employee->user->profile_image ?? null),
                'gender'           => $employee->gender,
                'date_of_birth'    => optional($employee->date_of_birth)->format('Y-m-d'),
                'residence'        => $employee->residence,
                'department_id'    => $employee->department_id,
                'department_name'  => $employee->department?->name ?? '—',
                'job_title'        => $employee->job_title,
                'employee_type'    => $employee->employee_type,
                'hire_date'        => optional($employee->hire_date)->format('Y-m-d'),
                'termination_date' => optional($employee->termination_date)->format('Y-m-d'),
                'is_active'        => (bool) $employee->is_active,
                'salary'           => (float) $employee->salary,
                'salary_type'      => $employee->salary_type,
                'is_salary_recurring' => (bool) $employee->is_salary_recurring,
                'recurring_day'    => $employee->recurring_day,
                'nssf_number'      => $employee->nssf_number,
                'tin_number'       => $employee->tin_number,
                'bank_name'        => $employee->bank_name,
                'bank_account_number' => $employee->bank_account_number,
                'bank_branch'      => $employee->bank_branch,
                'id_type'          => $employee->id_type,
                'id_number'        => $employee->id_number,
                'qualification'    => $employee->qualification,
                'next_of_kin_name' => $employee->next_of_kin_name,
                'next_of_kin_contact' => $employee->next_of_kin_contact,
                'next_of_kin_relationship' => $employee->next_of_kin_relationship,
                'notes'            => $employee->notes,
                'currency_symbol'  => currency_symbol(),
                'created_at'       => optional($employee->created_at)->format('d M Y, H:i'),
                'updated_at'       => optional($employee->updated_at)->format('d M Y, H:i'),
                'documents'        => $documents,
                'documents_count'  => $documents->count(),
                'all_departments'  => $departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]),
            ], [
                'can_edit'      => $user->can('edit employee'),
                'can_toggle_status' => $user->can('update employee'),
                'can_manage_documents' => $user->can('edit employee'),
            ]),
        ]);
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
        // \Log::info($request->all());
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            
            if (!$user->hasPermissionTo('edit employee')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            // Check if user has protected roles using Spatie Permission
            // if ($user->hasAnyRole(['super_admin', 'admin'])) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => __('auth.user_not_updatable'),
            //     ]);
            // }

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
        
        // Check if user has protected roles using Spatie Permission
        $targetUser = \App\Models\User::find($id);

        // \Log::info('status-change role debug', [
        //     'auth_user_id'   => $user->id,
        //     'route_id'       => $id,
        //     'target_user_id' => $targetUser?->id,
        //     'target_roles'   => $targetUser?->roles()->pluck('name')->toArray(),
        //     'guard_default'  => config('auth.defaults.guard'),
        //     'all_roles'      => \Spatie\Permission\Models\Role::all(['name', 'guard_name'])->toArray(),
        //     'has_any_roles'  => $user->hasAnyRole(['super_admin', 'admin']),
        //     'direct_check'   => $user->roles()->whereIn('name', ['super_admin', 'admin'])->exists(),
        // ]);

        // Validate the request data for status
        $validated = $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $newStatus = (int) $validated['status'];

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
                        'department_id' => $user->department_id,
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
