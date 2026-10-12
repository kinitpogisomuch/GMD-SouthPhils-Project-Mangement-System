{{-- "Resubmit" for one proof of payment — opens the Resubmit modal on the client Payment Detail page.
     Expects $proof, $payment and $amountsDue (Payment::stageAmountsDue()). --}}
<button type="button" class="pfm-resubmit-btn {{ $proof->needsCorrection() ? 'is-urgent' : '' }}"
        data-resubmit
        data-action="{{ route('client.payments.proofs.resubmit', [$payment->id, $proof->id]) }}"
        data-stage="{{ $proof->payment_stage }}"
        data-stage-label="{{ \App\Models\PaymentTransaction::stageLabel($proof->payment_stage) }}"
        data-amount="{{ $proof->amount }}"
        data-fixed="{{ $proof->payment_stage !== 'progress_payment' && ($amountsDue[$proof->payment_stage] ?? 0) > 0 ? $amountsDue[$proof->payment_stage] : '' }}"
        data-max="{{ $amountsDue['final_payment'] ?? 0 }}"
        data-mode="{{ $proof->mode_of_payment }}"
        data-has-file="{{ $proof->file_url ? '1' : '0' }}"
        data-notes="{{ $proof->notes }}"
        data-reason="{{ $proof->correction_reason }}">
    <i data-lucide="refresh-ccw" style="width:13px;height:13px;"></i> Resubmit
</button>
