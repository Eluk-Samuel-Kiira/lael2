<?php
// app/Http/Controllers/Tenant/EmployeeDocumentController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasPermissionTo('edit employee')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'name' => 'required|string|max:255',
                'category' => 'required|in:cv,contract,id_proof,certificate,photo,offer_letter,other',
                'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
                'description' => 'nullable|string|max:500',
            ]);

            $employee = Employee::findOrFail($request->employee_id);
            
            // Handle file upload
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('employee_documents/' . $employee->id, $filename, 'public');

            // Get existing documents
            $documents = $employee->documents ? json_decode($employee->documents, true) : [];
            
            // Add new document
            $documents[] = [
                'name' => $request->name,
                'original_name' => $originalName,
                'path' => $path,
                'type' => $request->category,
                'extension' => $extension,
                'description' => $request->description,
                'uploaded_by' => $user->id,
                'uploaded_at' => now()->toDateTimeString(),
                'size' => $file->getSize(),
            ];

            // Update employee with new documents array
            $employee->update([
                'documents' => json_encode($documents)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'documents' => $documents
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            Log::error('Document upload error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasPermissionTo('edit employee')) {
                return response()->json([
                    'success' => false,
                    'message' => __('payments.not_authorized'),
                ]);
            }

            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'document_index' => 'required|integer',
            ]);

            $employee = Employee::findOrFail($request->employee_id);
            $documents = $employee->documents ? json_decode($employee->documents, true) : [];
            $index = $request->document_index;

            if (isset($documents[$index])) {
                // Delete file from storage
                $filePath = $documents[$index]['path'];
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                // Remove document from array
                unset($documents[$index]);
                $documents = array_values($documents); // Reindex array

                // Update employee
                $employee->update([
                    'documents' => !empty($documents) ? json_encode($documents) : null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ]);

        } catch (\Exception $e) {
            Log::error('Document delete error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage()
            ], 500);
        }
    }
}