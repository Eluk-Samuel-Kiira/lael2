<!-- Documents Modal for Employee {{ $employee->id }} -->
<div class="modal fade" id="documentsModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    {{ __('auth.employee_documents') }} - {{ $employee->first_name }} {{ $employee->last_name }}
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7" style="max-height: 70vh;">
                
                <!-- Employee Info -->
                <div class="row mb-8">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center p-5">
                            <i class="bi bi-person-badge fs-2hx me-4"></i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-info">{{ $employee->first_name }} {{ $employee->last_name }}</h4>
                                <span>{{ __('auth.employee_id') }}: <strong>ID-{{ $employee->id }}</strong> | 
                                      {{ __('auth.department') }}: <strong>{{ $employee->department->name ?? 'N/A' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload New Document Section -->
                <div class="separator separator-dashed my-10">
                    <h3 class="text-dark">{{ __('auth.upload_new_document') }}</h3>
                </div>

                <form id="uploadDocumentForm{{ $employee->id }}" class="form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    
                    <div class="row g-9 mb-8">
                        <!-- Document Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.document_name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Employment Contract" />
                            <div id="document_name_error{{ $employee->id }}"></div>
                        </div>

                        <!-- Document Category -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.document_category')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="category">
                                <option value="">{{ __('auth.select_category') }}</option>
                                <option value="cv">📄 {{ __('auth.cv_resume') }}</option>
                                <option value="contract">📑 {{ __('auth.employment_contract') }}</option>
                                <option value="id_proof">🪪 {{ __('auth.id_proof') }}</option>
                                <option value="certificate">🎓 {{ __('auth.certificate') }}</option>
                                <option value="photo">📸 {{ __('auth.photo') }}</option>
                                <option value="offer_letter">✉️ {{ __('auth.offer_letter') }}</option>
                                <option value="other">📁 {{ __('auth.other') }}</option>
                            </select>
                            <div id="category_error{{ $employee->id }}"></div>
                        </div>

                        <!-- File Upload -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth.select_file')}}</span>
                            </label>
                            <input type="file" class="form-control form-control-solid" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                            <div class="form-text">{{ __('auth.supported_files_pdf_images_doc') }} (Max 10MB)</div>
                            <div id="document_error{{ $employee->id }}"></div>
                        </div>

                        <!-- Description -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span>{{__('auth.description_label')}}</span>
                            </label>
                            <textarea class="form-control form-control-solid" name="description" rows="2" placeholder="Optional description"></textarea>
                            <div id="description_error{{ $employee->id }}"></div>
                        </div>
                    </div>

                    <div class="text-end mb-10">
                        <button type="button" onclick="uploadEmployeeDocument({{ $employee->id }})" class="btn btn-primary" id="uploadBtn{{ $employee->id }}">
                            <span class="indicator-label">
                                <i class="bi bi-cloud-upload me-2"></i>
                                {{ __('auth.upload_document') }}
                            </span>
                            <span class="indicator-progress" style="display: none;">
                                {{__('auth.uploading')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Existing Documents Section -->
                <div class="separator separator-dashed my-10">
                    <h3 class="text-dark">{{ __('auth.existing_documents') }}</h3>
                </div>

                <div id="documentsList{{ $employee->id }}">
                    @php
                        $documents = $employee->documents ? json_decode($employee->documents, true) : [];
                    @endphp

                    @if(count($documents) > 0)
                        <div class="table-responsive">
                            <table class="table table-row-bordered align-middle gy-4 gs-9">
                                <thead class="border-bottom border-gray-200 fs-6 fw-bold bg-light">
                                    <tr>
                                        <th class="min-w-150px">{{ __('auth.document_name') }}</th>
                                        <th class="min-w-100px">{{ __('auth.category') }}</th>
                                        <th class="min-w-100px">{{ __('auth.uploaded') }}</th>
                                        <th class="min-w-80px">{{ __('auth.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $index => $doc)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-{{ $doc['type'] == 'pdf' ? 'pdf' : 'text' }} fs-2 me-3 text-primary"></i>
                                                    <div>
                                                        <span class="fw-bold">{{ $doc['original_name'] ?? 'Document' }}</span>
                                                        <br>
                                                        <small class="text-muted">{{ $doc['type'] ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $categoryLabels = [
                                                        'cv' => 'CV/Resume',
                                                        'contract' => 'Contract',
                                                        'id_proof' => 'ID Proof',
                                                        'certificate' => 'Certificate',
                                                        'photo' => 'Photo',
                                                        'offer_letter' => 'Offer Letter',
                                                        'other' => 'Other'
                                                    ];
                                                @endphp
                                                <span class="badge badge-light-primary">{{ $categoryLabels[$doc['type']] ?? 'Other' }}</span>
                                            </td>
                                            <td>
                                                <div>
                                                    <span>{{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($doc['uploaded_at'])->diffForHumans() }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('employee.documents.view', ['employeeId' => $employee->id, 'index' => $index]) }}"
                                                    target="_blank"
                                                    class="btn btn-sm btn-icon btn-light btn-active-light-primary">
                                                        <i class="bi bi-eye fs-4"></i>
                                                    </a>
                                                    <a href="{{ route('employee.documents.download', ['employeeId' => $employee->id, 'index' => $index]) }}"
                                                    class="btn btn-sm btn-icon btn-light btn-active-light-success">
                                                        <i class="bi bi-download fs-4"></i>
                                                    </a>
                                                    <button type="button" onclick="deleteEmployeeDocument({{ $employee->id }}, {{ $index }})"
                                                            class="btn btn-sm btn-icon btn-light btn-active-light-danger">
                                                        <i class="bi bi-trash fs-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-10">
                            <i class="bi bi-folder-x fs-3x text-muted mb-3 d-block"></i>
                            <span class="text-muted">{{ __('auth.no_documents_uploaded') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth.close') }}</button>
            </div>
        </div>
    </div>
</div>

