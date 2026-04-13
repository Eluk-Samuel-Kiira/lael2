@props(['status' => 'offline', 'pendingCount' => 0, 'lastSync' => null])

@php
    $statusConfig = [
        'online' => ['class' => 'bg-success', 'icon' => 'check-circle', 'text' => 'Online'],
        'offline' => ['class' => 'bg-secondary', 'icon' => 'wifi-off', 'text' => 'Offline'],
        'syncing' => ['class' => 'bg-warning', 'icon' => 'refresh', 'text' => 'Syncing...'],
        'error' => ['class' => 'bg-danger', 'icon' => 'exclamation-circle', 'text' => 'Error'],
    ];
    
    $config = $statusConfig[$status] ?? $statusConfig['offline'];
@endphp

<div {{ $attributes->merge(['class' => 'd-inline-flex align-items-center gap-2']) }}>
    {{-- Badge --}}
    <span class="badge {{ $config['class'] }} d-inline-flex align-items-center gap-1 px-2 py-1">
        <i class="fas fa-{{ $config['icon'] }} fs-10"></i>
        <span class="fw-semibold">{{ $config['text'] }}</span>
    </span>
    
    {{-- Pending Count (if > 0) --}}
    @if($pendingCount > 0)
        <span class="badge bg-info d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="fas fa-clock fs-10"></i>
            <span>{{ $pendingCount }} pending</span>
        </span>
    @endif
    
    {{-- Last Sync Time --}}
    @if($lastSync)
        <span class="text-muted small">
            <i class="far fa-clock me-1"></i>
            {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}
        </span>
    @endif
</div>