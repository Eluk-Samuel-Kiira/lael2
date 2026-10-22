<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fw-bold">
                    <i class="ki-duotone ki-information fs-2 me-2 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    {{ __('accounting.transaction_details') }}
                </h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body">
                <!-- Transaction Reference -->
                <div class="d-flex align-items-center bg-light rounded-3 p-5 mb-8">
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transaction_ref') }}</span>
                        <span class="fs-4 fw-bold text-gray-800" id="modalTransactionRef">-</span>
                    </div>
                    <div class="d-flex flex-column ms-8">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.receipt_number') }}</span>
                        <span class="fs-4 fw-bold text-gray-800" id="modalReceiptNumber">-</span>
                    </div>
                    <div class="d-flex flex-column ms-8">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span>
                        <span class="fs-4 fw-bold text-gray-800" id="modalDate">-</span>
                    </div>
                </div>
                
                <!-- Amount Details -->
                <div class="row g-4 mb-8">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-primary h-100" id="modalAmountCard">
                            <div class="card-body text-center py-5 d-flex flex-column justify-content-center">
                                <span class="text-gray-500 fw-semibold fs-7 mb-2">{{ __('accounting.amount') }}</span>
                                <!-- Changed to fs-3, added fw-bolder and text-nowrap -->
                                <div class="fs-3 fw-bolder text-nowrap" id="modalAmount">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-flush bg-light-info h-100">
                            <div class="card-body text-center py-5 d-flex flex-column justify-content-center">
                                <span class="text-gray-500 fw-semibold fs-7 mb-2">{{ __('accounting.balance_before') }}</span>
                                <div class="fs-3 fw-bolder text-gray-800 text-nowrap" id="modalBalanceBefore">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-primary h-100">
                            <div class="card-body text-center py-5 d-flex flex-column justify-content-center">
                                <span class="text-gray-500 fw-semibold fs-7 mb-2">{{ __('accounting.balance_after') }}</span>
                                <div class="fs-3 fw-bolder text-gray-800 text-nowrap" id="modalBalanceAfter">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Info -->
                <div class="row g-5 mb-8">
                    <div class="col-md-3">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.payment_method') }}</span>
                            <span class="fs-6 fw-bold text-gray-800" id="modalPaymentMethod">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.type') }}</span>
                            <span id="modalTransactionType">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.category') }}</span>
                            <span class="fs-6 fw-bold text-gray-800" id="modalCategory">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.status') }}</span>
                            <span id="modalStatus">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="mb-8">
                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.description') }}</span>
                    <div class="bg-light rounded-3 p-4 mt-2">
                        <span class="fs-6 text-gray-800" id="modalDescription">-</span>
                    </div>
                </div>
                
                <!-- User Information -->
                <div class="row g-5 mb-8">
                    <div class="col-md-6">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.processed_by') }}</span>
                            <span class="fs-6 fw-bold text-gray-800" id="modalProcessedBy">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.customer') }}</span>
                            <span class="fs-6 fw-bold text-gray-800" id="modalCustomer">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Reference Information -->
                <div class="mb-8">
                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.reference_information') }}</span>
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="w-50">{{ __('accounting.reference_table') }}</th>
                                    <td class="w-50" id="modalReferenceTable">-</td>
                                </tr>
                                <tr>
                                    <th>{{ __('accounting.reference_id') }}</th>
                                    <td id="modalReferenceId">-</td>
                                </tr>
                                <tr id="externalReferenceRow" style="display: none;">
                                    <th>{{ __('accounting.external_reference') }}</th>
                                    <td id="modalExternalReference">-</td>
                                </tr>
                                <tr id="bankReferenceRow" style="display: none;">
                                    <th>{{ __('accounting.bank_reference') }}</th>
                                    <td id="modalBankReference">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Metadata -->
                <div class="mb-0">
                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.metadata') }}</span>
                    <div class="mt-2">
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="metadataTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#metadataTab1">{{ __('accounting.formatted_view') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#metadataTab2">{{ __('accounting.raw_json') }}</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="metadataContent">
                            <div class="tab-pane fade show active" id="metadataTab1" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="formattedMetadataTable">
                                        <thead>
                                            <tr>
                                                <th class="w-50">{{ __('accounting.field') }}</th>
                                                <th class="w-50">{{ __('accounting.value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="metadataTab2" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="m-0" id="rawMetadata" style="max-height: 300px; overflow: auto;"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('accounting.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="ki-duotone ki-printer fs-2 me-2"></i>
                    {{ __('accounting.print') }}
                </button>
            </div>
        </div>
    </div>
</div>