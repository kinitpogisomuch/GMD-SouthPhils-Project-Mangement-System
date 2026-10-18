{{-- Operational progress and financial status, tracked separately, plus why the project is or isn't fully
     closed (completed, delivered and fully paid). Shared by the admin and client project pages.
     Expects: $project, $portal ('admin' | 'client'). --}}
@php
    $settlePayment = $project->getPaymentRecord();
    $settlePayment?->loadMissing('transactions');
    $contract  = (float) ($settlePayment->contract_amount ?? 0);
    $paidTotal = $settlePayment ? $settlePayment->totalPaid() : 0;
    $balance   = $settlePayment ? $settlePayment->currentBalance() : 0;
    $payStatus = $settlePayment ? $settlePayment->computeStatus() : 'No payment setup';
    $completed = $project->status === 'completed';
    $delivered = $project->isDelivered();
    $gate      = $project->phaseData('payment_gates.delivery') ?? [];
    $exception = $delivered && !empty($gate['without_payment']) && $balance > 0.009;
    $peso      = fn ($v) => '₱' . number_format((float) $v, 2);

    if ($project->status === 'archived') {
        $closure = ['tone' => 'muted', 'icon' => 'archive', 'text' => 'This project is archived.'];
    } elseif ($delivered && $balance <= 0.009 && $settlePayment) {
        $closure = ['tone' => 'good', 'icon' => 'badge-check', 'text' => 'Fully closed — completed, delivered and fully paid.'];
    } elseif ($exception) {
        $closure = ['tone' => 'warn', 'icon' => 'shield-alert', 'text' => 'Delivered with ' . $peso($balance) . ' still outstanding. Delivery before full payment was authorized'
            . (!empty($gate['authorized_by']) ? ' by ' . $gate['authorized_by'] : '')
            . (!empty($gate['confirmed_at']) ? ' on ' . \Carbon\Carbon::parse($gate['confirmed_at'])->format('M d, Y') : '')
            . '. The project is not fully closed until the balance is paid.'];
    } elseif ($delivered) {
        $closure = ['tone' => 'warn', 'icon' => 'wallet', 'text' => 'Delivered. ' . $peso($balance) . ' is still outstanding, so the project is not fully closed yet.'];
    } elseif ($completed) {
        $closure = ['tone' => 'info', 'icon' => 'truck', 'text' => $balance > 0.009
            ? 'Fabrication is complete. Delivery / pick-up follows once the final payment of ' . $peso($balance) . ' is settled' . ($portal === 'admin' ? ' (or you authorize an exception with "Proceed Anyway").' : '.')
            : 'Fabrication is complete and fully paid — awaiting delivery / pick-up.'];
    } else {
        $closure = ['tone' => 'info', 'icon' => 'hammer', 'text' => 'In production. The project is fully closed once it is completed, delivered and fully paid'
            . ($balance > 0.009 ? ' (' . $peso($balance) . ' remaining).' : '.')];
    }
@endphp
<div class="pss" role="region" aria-label="Project and payment status">
    <div class="pss-col">
        <div class="pss-label">Project</div>
        <div class="pss-value">{{ $completed ? 'Completed' : ($project->status === 'archived' ? 'Archived' : ucfirst(str_replace('_', ' ', $project->current_phase ?? $project->status))) }}</div>
        <div class="pss-sub">{{ $project->deliveryStatusLabel() }}</div>
    </div>
    <div class="pss-col">
        <div class="pss-label">Payment</div>
        <div class="pss-value">{{ $payStatus }}</div>
        <div class="pss-sub">{{ $peso($paidTotal) }} paid of {{ $peso($contract) }}</div>
    </div>
    <div class="pss-col">
        <div class="pss-label">Outstanding Balance</div>
        <div class="pss-value {{ $balance > 0.009 ? 'is-due' : 'is-clear' }}">{{ $peso($balance) }}</div>
        <div class="pss-sub">
            @if($settlePayment)
            <a href="{{ $portal === 'admin' ? route('admin.payments.show', $settlePayment->id) : route('client.payments.show', $settlePayment->id) }}">{{ $portal === 'client' && $balance > 0.009 ? 'Pay / view payments' : 'View payments' }} →</a>
            @else
            —
            @endif
        </div>
    </div>
    <div class="pss-closure pss-{{ $closure['tone'] }}">
        <i data-lucide="{{ $closure['icon'] }}"></i>
        <span>{{ $closure['text'] }}</span>
    </div>
</div>
@once
<style>
    .pss { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; margin-bottom: 20px; background: var(--white, #fff); border: 1px solid var(--border, #e0e0e0); border-radius: 18px; overflow: hidden; }
    .pss-col { padding: 14px 18px; border-right: 1px solid var(--border, #e0e0e0); min-width: 0; }
    .pss-col:nth-child(3) { border-right: none; }
    .pss-label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--muted, #666); }
    .pss-value { font-size: 16px; font-weight: 900; color: var(--dark, #333); margin-top: 3px; }
    .pss-value.is-due { color: #B42318; }
    .pss-value.is-clear { color: #207A3A; }
    .pss-sub { font-size: 12px; color: var(--muted, #666); margin-top: 2px; }
    .pss-sub a { color: var(--dark, #333); font-weight: 800; text-decoration: none; }
    .pss-closure { grid-column: 1 / -1; display: flex; align-items: flex-start; gap: 9px; padding: 11px 18px; font-size: 12.5px; font-weight: 600; line-height: 1.5; border-top: 1px solid var(--border, #e0e0e0); }
    .pss-closure i, .pss-closure svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
    .pss-good { background: #E7F6EC; color: #14532d; }
    .pss-warn { background: #FFF7E6; color: #7A4B00; }
    .pss-info { background: #F5F8FF; color: #1e3a8a; }
    .pss-muted { background: #F3F4F6; color: #4b5563; }
    @media (max-width: 640px) {
        .pss { grid-template-columns: 1fr; }
        .pss-col { border-right: none; border-bottom: 1px solid var(--border, #e0e0e0); }
        .pss-closure { border-top: none; }
    }
</style>
@endonce
