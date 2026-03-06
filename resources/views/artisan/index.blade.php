{{-- resources/views/artisan/index.blade.php --}}

<x-app-layout>
    @section('title', __('auth.artisan_commands'))
    @section('content')

    {{-- Toolbar Section --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-4">
            {{-- Page Title --}}
            <div class="page-title d-flex flex-column me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-1 flex-column my-0">
                    Artisan Command Runner
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url('/') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Admin</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Artisan Runner</li>
                </ul>
            </div>

            {{-- Toolbar Actions --}}
            <div class="d-flex align-items-center gap-3">
                <span class="badge badge-lg badge-light-primary fs-7 fw-semibold py-3 px-4">
                    <i class="ki-duotone ki-setting-3 fs-2 me-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        <span class="path4"></span><span class="path5"></span>
                    </i>
                    Laravel CLI
                </span>
            </div>
        </div>
    </div>

    {{-- Content Section --}}
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Alert Info --}}
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-10">
                <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold">
                        <h4 class="text-gray-900 fw-bold">Super Admin Use Only</h4>
                        <div class="fs-6 text-gray-700">Only whitelisted commands are available. Use carefully in production.</div>
                    </div>
                </div>
            </div>

            {{-- Command Form and Output --}}
            <div class="row g-5 g-xl-8">
                {{-- Command Form Card --}}
                <div class="col-xl-5">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Run Command</span>
                                <span class="text-muted mt-1 fw-semibold fs-7">Select and execute an Artisan command</span>
                            </h3>
                        </div>

                        <div class="card-body pt-5">
                            {{-- Command Select --}}
                            <div class="fv-row mb-8">
                                <label class="form-label fw-semibold text-gray-900 fs-6 required">Select Command</label>
                                <select id="artisan_command" class="form-select form-select-solid" data-control="select2" data-placeholder="-- Choose a command --">
                                    <option></option>
                                    @foreach($commands as $cmd => $description)
                                        <option value="{{ $cmd }}">php artisan {{ $cmd }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Description --}}
                            <div id="command_description" class="mb-8 d-none">
                                <div class="d-flex align-items-center bg-light-info rounded p-5">
                                    <i class="ki-duotone ki-document fs-2x text-info me-3">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-7 text-muted fw-semibold">Description</div>
                                        <div id="description_text" class="fs-6 text-gray-800 fw-bold"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Run Button --}}
                            <button id="run_btn" class="btn btn-primary w-100 py-4" disabled>
                                <span class="indicator-label">
                                    <i class="ki-duotone ki-rocket fs-3 me-2">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    Run Command
                                </span>
                                <span class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Output Terminal Card --}}
                <div class="col-xl-7">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Output</span>
                                <span class="text-muted mt-1 fw-semibold fs-7">Command execution result</span>
                            </h3>
                            <div class="card-toolbar">
                                <button id="clear_output" class="btn btn-sm btn-light-danger btn-flex">
                                    <i class="ki-duotone ki-trash fs-3 me-2">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                    </i>
                                    Clear Output
                                </button>
                            </div>
                        </div>

                        <div class="card-body pt-5">
                            {{-- Terminal Window --}}
                            <div class="terminal-wrapper bg-dark rounded-4 overflow-hidden mb-5" style="min-height: 380px;">
                                {{-- Terminal Top Bar --}}
                                <div class="terminal-header d-flex align-items-center px-5 py-3" style="background: #1e1e2e; border-bottom: 1px solid #313244;">
                                    <div class="d-flex gap-2 me-4">
                                        <span class="terminal-dot bg-danger rounded-circle" style="width: 12px; height: 12px;"></span>
                                        <span class="terminal-dot bg-warning rounded-circle" style="width: 12px; height: 12px;"></span>
                                        <span class="terminal-dot bg-success rounded-circle" style="width: 12px; height: 12px;"></span>
                                    </div>
                                    <span class="terminal-title text-gray-500 fs-8 fw-semibold" id="terminal_title">bash — artisan runner</span>
                                </div>
                                {{-- Terminal Body --}}
                                <div id="terminal_output" class="terminal-body p-5" 
                                     style="background: #1e1e2e; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 13px; color: #cdd6f4; min-height: 330px; white-space: pre-wrap; word-break: break-word;">
                                    <span class="text-gray-600">$ Waiting for command...</span>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div id="status_badge" class="d-none">
                                <!-- Dynamic content -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endsection

    @push('scripts')
    <script>
        const commands = @json($commands);

        // Initialize components when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const selectEl      = document.getElementById('artisan_command');
            const runBtn        = document.getElementById('run_btn');
            const terminalOut   = document.getElementById('terminal_output');
            const terminalTitle = document.getElementById('terminal_title');
            const statusBadge   = document.getElementById('status_badge');
            const cmdDesc       = document.getElementById('command_description');
            const descText      = document.getElementById('description_text');
            const clearBtn      = document.getElementById('clear_output');

            // Initialize Select2 if available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(selectEl).select2({
                    placeholder: "-- Choose a command --",
                    allowClear: true
                });
            }

            // On command select
            $(selectEl).on('change', function() {
                const val = $(this).val();
                if (val) {
                    runBtn.removeAttribute('disabled');
                    descText.textContent = commands[val] ?? '';
                    cmdDesc.classList.remove('d-none');
                } else {
                    runBtn.setAttribute('disabled', true);
                    cmdDesc.classList.add('d-none');
                }
            });

            // Run command
            runBtn.addEventListener('click', function() {
                const command = $(selectEl).val();
                if (!command) return;

                // Loading state
                runBtn.setAttribute('data-kt-indicator', 'on');
                runBtn.disabled = true;
                terminalTitle.textContent = 'bash — running: php artisan ' + command;
                terminalOut.innerHTML = '<span style="color:#89b4fa;">$ php artisan ' + command + '</span>\n<span class="text-gray-600">Running...</span>';
                statusBadge.classList.add('d-none');

                fetch('{{ route("artisan.run") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ command: command })
                })
                .then(res => res.json())
                .then(data => {
                    const color   = data.success ? '#a6e3a1' : '#f38ba8';
                    const prompt  = data.success ? '✅' : '❌';

                    terminalOut.innerHTML =
                        '<span style="color:#89b4fa;">$ php artisan ' + command + '</span>\n\n' +
                        '<span style="color:' + color + ';">' + escapeHtml(data.output) + '</span>';

                    terminalTitle.textContent = prompt + ' bash — php artisan ' + command;

                    statusBadge.classList.remove('d-none');
                    statusBadge.innerHTML = data.success
                        ? '<span class="badge badge-light-success fs-7 fw-bold px-5 py-3"><i class="ki-duotone ki-check-circle fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Command completed successfully</span>'
                        : '<span class="badge badge-light-danger fs-7 fw-bold px-5 py-3"><i class="ki-duotone ki-cross-circle fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Command failed</span>';
                })
                .catch(err => {
                    terminalOut.innerHTML =
                        '<span style="color:#89b4fa;">$ php artisan ' + command + '</span>\n\n' +
                        '<span style="color:#f38ba8;">❌ Network or server error: ' + escapeHtml(err.message) + '</span>';
                    terminalTitle.textContent = '❌ bash — php artisan ' + command;
                })
                .finally(() => {
                    runBtn.removeAttribute('data-kt-indicator');
                    runBtn.disabled = false;
                });
            });

            // Clear output
            clearBtn.addEventListener('click', function() {
                terminalOut.innerHTML = '<span class="text-gray-600">$ Waiting for command...</span>';
                terminalTitle.textContent = 'bash — artisan runner';
                statusBadge.classList.add('d-none');
            });

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }
        });
    </script>
    @endpush
</x-app-layout>