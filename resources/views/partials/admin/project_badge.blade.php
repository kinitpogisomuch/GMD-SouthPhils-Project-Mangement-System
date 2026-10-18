{{-- One status badge for a project row (Projects page Active tab and the client page).
     Params: $project, $kind = phase | delivery | payment | progress --}}
@php
    $phaseColors = [
        'planning'    => ['bg'=>'#FEF3C7','color'=>'#92400E','shadow'=>'rgba(245,158,11,.2)'],
        'procurement' => ['bg'=>'#EDE9FE','color'=>'#5B21B6','shadow'=>'rgba(139,92,246,.2)'],
        'matl_prep'   => ['bg'=>'#CFFAFE','color'=>'#0E7490','shadow'=>'rgba(6,182,212,.2)'],
        'fabrication' => ['bg'=>'#2563EB','color'=>'#fff','shadow'=>'rgba(37,99,235,.3)'],
        'inspection'  => ['bg'=>'#EC4899','color'=>'#fff','shadow'=>'rgba(236,72,153,.3)'],
        'painting'    => ['bg'=>'#14B8A6','color'=>'#fff','shadow'=>'rgba(20,184,166,.3)'],
        'completion'  => ['bg'=>'#10B981','color'=>'#fff','shadow'=>'rgba(16,185,129,.3)'],
        'delivery'    => ['bg'=>'#059669','color'=>'#fff','shadow'=>'rgba(5,150,105,.3)'],
        'delayed'     => ['bg'=>'#EF4444','color'=>'#fff','shadow'=>'rgba(239,68,68,.3)'],
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
        {{ ucwords(str_replace('_', ' ', $phase)) }}
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
        $progColor = $prog == 0 ? '#9CA3AF' : ($prog <= 25 ? '#F59E0B' : ($prog <= 50 ? '#3B82F6' : ($prog <= 75 ? '#6366F1' : ($prog < 100 ? '#10B981' : '#059669'))));
        $progBg    = $prog == 0 ? '#F3F4F6' : ($prog <= 25 ? '#FEF3C7' : ($prog <= 50 ? '#DBEAFE' : ($prog <= 75 ? '#E0E7FF' : '#D1FAE5')));
    @endphp
    <span class="status-badge" style="background:{{ $progBg }};color:{{ $progColor }};box-shadow:0 0 0 1px {{ $progColor }}22,0 2px 8px {{ $progColor }}33;font-weight:800;">
        {{ $prog }}%
    </span>
@endif
