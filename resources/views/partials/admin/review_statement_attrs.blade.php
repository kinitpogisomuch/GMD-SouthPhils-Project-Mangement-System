{{-- data-* attributes that make an element open $statement in the payment page's Review & Send modal --}}
data-review-statement
data-review-url="{{ route('admin.payments.billing_statements.show', [$statement->payment_id, $statement->id]) }}?embed=1"
data-send-url="{{ route('admin.payments.billing_statements.send', [$statement->payment_id, $statement->id]) }}"
data-edit-url="{{ route('admin.payments.show', ['id' => $statement->payment_id, 'edit_statement' => $statement->id]) }}"
data-title="{{ $statement->subject ?: 'Billing Statement' }} · {{ $statement->statement_date?->format('M d, Y') }}"
data-sent="{{ $statement->sent_at?->format('M d, Y g:i A') }}"
