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

    <style>
        /* Reduce toolbar height */
        .app-toolbar.py-3.py-lg-6 {
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
        }
        
        /* Reduce heading size */
        .page-heading {
            font-size: 1.1rem !important;
            margin: 0 !important;
        }
        
        /* Reduce search input height */
        .input-group {
            height: 42px !important;
        }
        
        .form-control,
        .input-group-text {
            padding: 0.2rem 0.5rem !important;
            height: 42px !important;
            font-size: 1.0rem !important;
        }
        
        /* Reduce button size */
        .btn-primary {
            padding: 0.2rem 0.75rem !important;
            font-size: 1.0rem !important;
            height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }
        
        /* Reduce icon size */
        .btn-primary i,
        .input-group-text i {
            font-size: 1.0rem !important;
        }
        
        /* Reduce gap between elements */
        .d-flex.gap-3 {
            gap: 0.5rem !important;
        }
        
        /* Make search input narrower */
        .w-sm-250px {
            width: 180px !important;
        }
        
        /* Responsive - keep readable on mobile */
        @media (max-width: 768px) {
            .w-sm-250px {
                width: 100% !important;
            }
            
            .page-heading {
                font-size: 1rem !important;
            }
        }
    </style>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <!--begin::Content-->
                <div class="card">
                    @include('human-resource.partial.user-componenet')
                </div>
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>