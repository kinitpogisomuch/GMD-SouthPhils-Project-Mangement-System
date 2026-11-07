<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An uploaded e-signature image. The most recent one per context is the default signature for the next
 * document of that kind — an upload becomes the default straight away, before the document is saved.
 */
class ESignature extends Model
{
    public const QUOTATION           = 'quotation';
    public const BILLING_PREPARED_BY = 'billing_prepared_by';
    public const BILLING_APPROVED_BY = 'billing_approved_by';

    protected $table = 'e_signatures';

    protected $fillable = ['context', 'url', 'uploaded_by'];

    public static function record(string $context, string $url): self
    {
        return static::create([
            'context'     => $context,
            'url'         => $url,
            'uploaded_by' => auth()->user()->name ?? session('full_name') ?? session('name'),
        ]);
    }

    /** The most recently uploaded signature for this context, or null when none has been uploaded yet */
    public static function latestUrl(string $context): ?string
    {
        return static::where('context', $context)->latest('id')->value('url');
    }
}
