<x-app-layout>
    @section('content')
    
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-heading">{{__('auth._users_table')}}</h1>
                
                <div class="d-flex gap-3">
                    {{-- Reusable search component --}}
                    <x-liveblade-search 
                        id="employeeSearchInput"
                        componentId="reloadEmployeeComponent"
                        route="{{ route('employee.index') }}"
                        placeholder="{{__('auth._search')}} {{__('auth._users')}}"
                    />
                    
                    @can('create user')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_employee">
                        <i class="ki-duotone ki-plus"></i> {{__('auth.new_user')}}
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    
    <!-- Table Component -->
    <div class="card">
        @include('human-resource.partial.user-componenet')
    </div>
    
    @endsection
</x-app-layout>