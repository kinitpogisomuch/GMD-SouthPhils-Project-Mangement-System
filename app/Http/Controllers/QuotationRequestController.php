<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\ProjectTankItem;
use App\Models\QuotationRequest;
use App\Services\NotificationService;
use App\Services\SupabaseStorageService;

class QuotationRequestController extends Controller
{
    protected $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /*
    |--------------------------------------------------------------------------
    | Client — Request a Quotation
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $client = Client::findOrFail(session('user_id'));

        // Clients can submit a new request any time, even with one still under review —
        // the page shows both a "New Request" form and a "Pending" tab listing whatever
        // is currently awaiting GMD's review, so neither ever has to hide the other.
        $pendingBatches = QuotationRequest::where('client_id', $client->id)
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        return view('client.quotation_request', [
            'client'         => $client,
            'tankTypes'      => ProjectTankItem::TANK_TYPES,
            'pendingBatches' => $pendingBatches,
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        $request->validate([
            'tank_items'                    => 'nullable|array',
            'tank_items.*.tank_type'        => 'required|string|in:' . implode(',', ProjectTankItem::TANK_TYPES),
            'tank_items.*.capacity'         => 'nullable|string|max:255',
            'tank_items.*.quantity'         => 'nullable|integer|min:1',
            'tank_items.*.target_timeline'  => 'nullable|date|after_or_equal:today',
            'location'                      => 'required|string|max:1000',
            'notes'                         => 'nullable|string|max:2000',
            'reference_files'               => 'nullable|array|max:5',
            // "extensions" (not "mimes") because CAD tools like AutoCAD don't produce a
            // MIME type PHP's file-info can reliably sniff — Laravel's "mimes" rule would
            // reject valid .dwg uploads, so we trust the file's extension instead.
            'reference_files.*'             => 'file|extensions:pdf,jpg,jpeg,png,dwg|max:10240',
        ]);

        $tankItems = $request->input('tank_items', []);

        // A client can fill in tank specs, attach photos of a tank they already
        // own, or both — but the request needs at least one of the two.
        if (empty($tankItems) && !$request->hasFile('reference_files')) {
            return redirect()->back()
                ->withErrors(['tank_items' => 'Please add at least one tank requirement, or attach a photo of your existing tank.'])
                ->withInput();
        }

        // Each tank the client adds becomes its own independent quotation request —
        // its own status, its own quotation file, its own approve/decline — tagged
        // with a shared batch_id purely so the UI can show "submitted together".
        $batchId = (string) Str::uuid();

        // Optional photos/files of a tank the client already owns — shared across
        // the whole batch, not per tank, so it's uploaded once and copied to each row.
        $referenceUrls = $this->storage->uploadMultiple(
            $request->file('reference_files', []),
            'quotation-requests/' . $batchId . '/reference'
        );

        // No tank specs at all means the client is only sending their own tank —
        // still create one row so the reference photos have somewhere to live.
        if (empty($tankItems)) {
            $tankItems = [['tank_type' => null, 'capacity' => null, 'quantity' => 1, 'target_timeline' => null]];
        }

        $created = collect($tankItems)->map(function ($item) use ($client, $batchId, $request, $referenceUrls) {
            return QuotationRequest::create([
                'client_id'       => $client->id,
                'batch_id'        => $batchId,
                'tank_type'       => $item['tank_type'] ?? null,
                'capacity'        => $item['capacity'] ?? null,
                'quantity'        => $item['quantity'] ?? 1,
                'target_timeline' => $item['target_timeline'] ?? null,
                'location'        => $request->location,
                'notes'           => $request->notes,
                'reference_files' => !empty($referenceUrls) ? $referenceUrls : null,
                'status'          => 'pending',
            ]);
        });

        $created->each(fn ($qr) => NotificationService::quotationRequestSubmitted($qr));

        $message = $created->count() > 1
            ? 'Your ' . $created->count() . ' quotation requests have been submitted! Our team will review them shortly.'
            : 'Your request has been submitted! Our team will review it shortly.';

        return redirect()->route('client.quotation.create')->with('success', $message);
    }

    public function status()
    {
        $client = Client::findOrFail(session('user_id'));

        // History is for finished business only — active/in-review requests live on
        // the "Request Quotation" page instead, so they aren't shown twice.
        $requests = QuotationRequest::where('client_id', $client->id)
            ->whereIn('status', ['converted', 'declined'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        return view('client.quotation_status', compact('requests'));
    }

    public function approveQuotation($id)
    {
        $client = Client::findOrFail(session('user_id'));

        $quotationRequest = QuotationRequest::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        if ($quotationRequest->status !== 'quotation_sent') {
            return redirect()->route('client.quotation.create')
                ->with('error', 'There is no quotation currently awaiting your approval.');
        }

        $quotationRequest->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        NotificationService::quotationRequestApproved($quotationRequest);

        return redirect()->route('client.quotation.create')
            ->with('success', 'Quotation approved! Our team will proceed with your project shortly.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin — Review Quotation Requests
    |--------------------------------------------------------------------------
    */
    public function adminIndex()
    {
        $requests = QuotationRequest::with('client')->orderBy('created_at', 'desc')->get();

        return view('admin.quotation_requests', compact('requests'));
    }

    /** GET /admin/quotation-requests/pending-count — powers the sidebar nav badge */
    public function pendingCount()
    {
        return response()->json([
            'count' => QuotationRequest::where('status', 'pending')->count(),
        ]);
    }

    public function decline(Request $request, $id)
    {
        $quotationRequest = QuotationRequest::findOrFail($id);
        $quotationRequest->update([
            'status'         => 'declined',
            'decline_reason' => $request->input('reason'),
        ]);

        NotificationService::quotationRequestDeclined($quotationRequest);

        return redirect()->route('admin.quotation_requests')
            ->with('success', 'Quotation request declined.');
    }

    public function sendQuotation(Request $request, $id)
    {
        $quotationRequest = QuotationRequest::findOrFail($id);

        $request->validate([
            'quotation_files.0'   => 'required|array|min:1',
            'quotation_files.0.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $quotationUrls = $this->storage->uploadMultiple(
            $request->file('quotation_files.0', []),
            'quotation-requests/' . $quotationRequest->id . '/quotations'
        );

        if (empty($quotationUrls)) {
            return redirect()->route('admin.quotation_requests')
                ->with('error', 'Quotation file upload failed. Please check your connection and try again.');
        }

        $quotationRequest->update([
            'quotation_files'   => $quotationUrls,
            'status'            => 'quotation_sent',
            'quotation_sent_at' => now(),
        ]);

        NotificationService::quotationRequestQuotationSent($quotationRequest);

        return redirect()->route('admin.quotation_requests')
            ->with('success', 'Quotation sent to the client. Waiting for their approval.');
    }

    public function convert($id)
    {
        $quotationRequest = QuotationRequest::findOrFail($id);

        if ($quotationRequest->status !== 'approved') {
            return redirect()->route('admin.quotation_requests')
                ->with('error', 'This request must be approved by the client before it can be converted to a project.');
        }

        return redirect()->route('admin.projects', ['prefill_quotation_request' => $id]);
    }

    public function prefillData($id)
    {
        $quotationRequest = QuotationRequest::with('client')->findOrFail($id);

        return response()->json([
            'quotation_request_id' => $quotationRequest->id,
            'client' => [
                'name'    => $quotationRequest->client->name,
                'contact' => $quotationRequest->client->contact,
                'email'   => $quotationRequest->client->email,
                'address' => $quotationRequest->client->address,
            ],
            'tank_items' => [[
                'tank_type'       => $quotationRequest->tank_type,
                'capacity'        => $quotationRequest->capacity,
                'quantity'        => $quotationRequest->quantity,
                'target_timeline' => $quotationRequest->target_timeline_display,
            ]],
            'summary' => [
                'location' => $quotationRequest->location,
                'notes'    => $quotationRequest->notes,
            ],
            'reference_files' => $quotationRequest->reference_files ?? [],
        ]);
    }
}
