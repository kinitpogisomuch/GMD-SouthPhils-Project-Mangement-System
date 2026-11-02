{{-- One status badge for a project row (Projects page Active tab and the client page).
     Params: $project, $kind = phase | delivery | payment | progress --}}
@php
    $phaseColors = [
        'planning'    => ['bg'=>'#475569','color'=>'#fff','shadow'=>'rgba(71,85,105,.3)'],
        'procurement' => ['bg'=>'#1E3A5F','color'=>'#fff','shadow'=>'rgba(30,58,95,.3)'],
        'matl_prep'   => ['bg'=>'#7C5A10','color'=>'#fff','shadow'=>'rgba(124,90,16,.3)'],
        'fabrication' => ['bg'=>'#2A4EAA','color'=>'#fff','shadow'=>'rgba(42,78,170,.3)'],
        'inspection'  => ['bg'=>'#9A3412','color'=>'#fff','shadow'=>'rgba(154,52,18,.3)'],
        'painting'    => ['bg'=>'#4D5B2A','color'=>'#fff','shadow'=>'rgba(77,91,42,.3)'],
        'completion'  => ['bg'=>'#207A3A','color'=>'#fff','shadow'=>'rgba(32,122,58,.3)'],
        'delivery'    => ['bg'=>'#14532D','color'=>'#fff','shadow'=>'rgba(20,83,45,.3)'],
        'delayed'     => ['bg'=>'#B42318','color'=>'#fff','shadow'=>'rgba(180,35,24,.3)'],
        'completed'   => ['bg'=>'#E7F6EC','color'=>'#207A3A','shadow'=>'rgba(32,122,58,.15)'],
        'awaiting_delivery' => ['bg'=>'#FFF4E0','color'=>'#B45309','shadow'=>'rgba(245,158,11,.2)'],
        'delivered'   => ['bg'=>'#207A3A','color'=>'#fff','shadow'=>'rgba(32,122,58,.3)'],
    ];
@endphp
@if($kind === 'phase')
    @php
        // the current phase, or "Completed" once the Completion phase is done
        $phase = $project->status === 'completed' ? 'completed' : strtolower($project->current_phase ?? 'planning');
        $pc = $phaseColors[$phase] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','shadow'=>'rgba(0,0,0,.1)'];
    @endphp
    <span class="status-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};box-shadow:0 0 0 1px {{ $pc['shadow'] }},0 2px 8px {{ $pc['shadow'] }};">
        {{ ucwords(\App\Models\Project::phaseLabel($phase)) }}
    </span>
@elseif($kind === 'delivery')
    {{-- tracked separately from completion, and only shown once the project is completed --}}
    @if($project->status === 'completed')
        @php $dc = $phaseColors[$project->deliveryStatus()]; @endphp
        <span class="status-badge" style="background:{{ $dc['bg'] }};color:{{ $dc['color'] }};box-shadow:0 0 0 1px {{ $dc['shadow'] }},0 2px 8px {{ $dc['shadow'] }};">
            {{ $project->deliveryStatusLabel() }}
        </span>
    @else
        <span style="color:var(--muted);">—</span>
    @endif
@elseif($kind === 'payment')
    @php
        $projPayment = $project->getPaymentRecord();
        if ($project->status === 'completed' && !$project->isPaymentStageSettled('final_payment')) {
            // Delivered before the final payment came in ("Proceed Anyway") — still owed
            [$payLabel, $payClass] = ['Final Payment Pending', 'pending'];
        } elseif ($projPayment) {
            [$payLabel, $payClass] = [$projPayment->status, \App\Models\Payment::statusBadgeClass($projPayment->status)];
        } else {
            [$payLabel, $payClass] = [null, null];
        }
    @endphp
    @if($payLabel)
    <a href="{{ $projPayment ? route('admin.payments.show', $projPayment->id) : route('admin.payments') }}" title="Open this project's payments" style="text-decoration:none;" onclick="event.stopPropagation()">
        <span class="status-badge {{ $payClass }}" style="font-size:11px;line-height:1.3;text-align:center;white-space:normal;">
            @if($payLabel === 'Final Payment Pending')<i data-lucide="alert-triangle" style="width:11px;height:11px;vertical-align:-1px;"></i> @endif{{ $payLabel }}
        </span>
    </a>
    @else
    <span style="color:var(--muted);">—</span>
    @endif
@elseif($kind === 'progress')
    @php
        $prog = $project->progress ?? 0;
        $progColor = $prog == 0 ? '#6B7280' : ($prog <= 25 ? '#7C5A10' : ($prog <= 50 ? '#475569' : ($prog <= 75 ? '#2A4EAA' : ($prog < 100 ? '#207A3A' : '#14532D'))));
        $progBg    = $prog == 0 ? '#F3F4F6' : ($prog <= 25 ? '#F5EDD8' : ($prog <= 50 ? '#F1F5F9' : ($prog <= 75 ? '#EAF0FF' : '#E7F6EC')));
    @endphp
    <span class="status-badge" style="background:{{ $progBg }};color:{{ $progColor }};box-shadow:0 0 0 1px {{ $progColor }}22,0 2px 8px {{ $progColor }}33;font-weight:800;">
        {{ $prog }}%
    </span>
@endif
