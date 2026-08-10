@can('view inventory')
<div class="card-body py-4" id="reloadBatchComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" id="selectAllBatches" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('passwords.batch_number')}}</th>
                    <th class="min-w-125px">{{__('passwords.product')}}</th>
                    <th class="min-w-100px text-center">{{__('passwords.quantity')}}</th>
                    <th class="min-w-125px">{{__('passwords.expiry_date')}}</th>
                    <th class="min-w-125px">{{__('passwords.location')}}</th>
                    <th class="min-w-125px">{{__('passwords.department')}}</th>
                    <th class="min-w-100px text-end">{{__('passwords.status')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($batches) && $batches->count() > 0)
                    @foreach ($batches as $batch)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input batch-checkbox" type="checkbox" value="{{ $batch->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light-info fw-bold">{{ $batch->batch_number ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-800 text-hover-primary fw-bold">{{ $batch->variant_name ?? $batch->product_name ?? 'N/A' }}</span>
                                        <span class="text-muted fs-7">{{ $batch->sku ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-warning">{{ $batch->quantity_remaining ?? $batch->quantity_received }}</td>
                            <td>
                                @if($batch->expiry_date)
                                    <span class="badge badge-light-{{ $batch->expiry_date->isPast() ? 'danger' : 'warning' }}">
                                        {{ $batch->expiry_date->format('Y-m-d') }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light fw-bold">{{ $batch->location_name ?? 'Not Assigned' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light fw-bold">{{ $batch->department_name ?? 'Not Assigned' }}</span>
                            </td>
                            <td class="text-end">
                                @if($batch->location_id && $batch->department_id)
                                    <span class="badge badge-light-success">{{ __('passwords.assigned') }}</span>
                                @elseif($batch->location_id || $batch->department_id)
                                    <span class="badge badge-light-warning">{{ __('passwords.partial') }}</span>
                                @else
                                    <span class="badge badge-light-info">{{ __('passwords.available') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <x-liveblade-pagination
            :paginator="$batches"
            id="batchPagination"
            route="{{ route('batches.index') }}"
            search-input-id="batchSearchInput"
            :show-info="true"
            :show-per-page="true"
            :per-page-options="[15, 25, 50, 100]"
            data-lb-component="reloadBatchComponent"
        />
    </div>
</div>
@endcan

<script>
document.getElementById('selectAllBatches')?.addEventListener('change', function() {
    document.querySelectorAll('.batch-checkbox').forEach(cb => cb.checked = this.checked);
});

function assignSelectedBatches() {
    const selected = document.querySelectorAll('.batch-checkbox:checked');
    if (selected.length === 0) {
        toastr.warning('{{ __("passwords.no_batches_selected") }}');
        return;
    }
    const batchIds = Array.from(selected).map(cb => cb.value);
    openAssignBatchModal(batchIds);
}
</script>