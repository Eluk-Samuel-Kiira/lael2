<?php
// app/Http/Controllers/Tenant/EmployeeDocumentController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Store on the 'public' disk (storage/app/public). We never rely on
            // the public symlink to serve it — see view()/download() below.
            $path = $file->storeAs('employee_documents/' . $employee->id, $filename, 'public');

            $documents = $employee->documents ? json_decode($employee->documents, true) : [];

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

            $employee->update([
                'documents' => json_encode($documents),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'documents' => $documents,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('Document upload error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream a document inline (view in browser).
     */
    public function view(Request $request, $employeeId, $index)
    {
        $document = $this->resolveDocument($employeeId, $index);

        if (!$document) {
            abort(404, 'Document not found');
        }

        if (!Storage::disk('public')->exists($document['path'])) {
            abort(404, 'File not found on disk');
        }

        $mime = Storage::disk('public')->mimeType($document['path'])
            ?? $this->guessMime($document['extension'] ?? '');

        return new StreamedResponse(function () use ($document) {
            $stream = Storage::disk('public')->readStream($document['path']);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . ($document['original_name'] ?? 'document') . '"',
            'Content-Length' => Storage::disk('public')->size($document['path']),
        ]);
    }

    /**
     * Force download of a document.
     */
    public function download(Request $request, $employeeId, $index)
    {
        $document = $this->resolveDocument($employeeId, $index);

        if (!$document) {
            abort(404, 'Document not found');
        }

        if (!Storage::disk('public')->exists($document['path'])) {
            abort(404, 'File not found on disk');
        }

        return Storage::disk('public')->download(
            $document['path'],
            $document['original_name'] ?? basename($document['path'])
        );
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
                $filePath = $documents[$index]['path'];
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                unset($documents[$index]);
                $documents = array_values($documents);

                $employee->update([
                    'documents' => !empty($documents) ? json_encode($documents) : null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document deleted successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ]);

        } catch (\Exception $e) {
            Log::error('Document delete error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Look up a document by employee + index from the JSON column.
     */
    private function resolveDocument($employeeId, $index)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return null;
        }

        $documents = $employee->documents ? json_decode($employee->documents, true) : [];

        return $documents[$index] ?? null;
    }

    private function guessMime(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}