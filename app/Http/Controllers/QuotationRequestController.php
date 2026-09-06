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

        // Clients can request a new quotation any time — even repeat clients with an
        // existing project — as long as their last request hasn't reached an outcome yet.
        if (QuotationRequest::where('client_id', $client->id)->unresolved()->exists()) {
            return redirect()->route('client.quotation.status');
        }

        return view('client.quotation_request', [
            'client'    => $client,
            'tankTypes' => ProjectTankItem::TANK_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        $request->validate([
            'tank_items'                    => 'required|array|min:1',
            'tank_items.*.tank_type'        => 'required|string|in:' . implode(',', ProjectTankItem::TANK_TYPES),
            'tank_items.*.capacity'         => 'nullable|string|max:255',
            'tank_items.*.quantity'         => 'nullable|integer|min:1',
            'tank_items.*.target_timeline'  => 'nullable|string|max:255',
            'location'                      => 'required|string|max:1000',
            'notes'                         => 'nullable|string|max:2000',
        ]);

        // Each tank the client adds becomes its own independent quotation request —
        // its own status, its own quotation file, its own approve/decline — tagged
        // with a shared batch_id purely so the UI can show "submitted together".
        $batchId = (string) Str::uuid();

        $created = collect($request->tank_items)->map(function ($item) use ($client, $batchId, $request) {
            return QuotationRequest::create([
                'client_id'       => $client->id,
                'batch_id'        => $batchId,
                'tank_type'       => $item['tank_type'],
                'capacity'        => $item['capacity'] ?? null,
                'quantity'        => $item['quantity'] ?? 1,
                'target_timeline' => $item['target_timeline'] ?? null,
                'location'        => $request->location,
                'notes'           => $request->notes,
                'status'          => 'pending',
            ]);
        });

        $created->each(fn ($qr) => NotificationService::quotationRequestSubmitted($qr));

        $message = $created->count() > 1
            ? 'Your ' . $created->count() . ' quotation requests have been submitted! Our team will review them shortly.'
            : 'Your request has been submitted! Our team will review it shortly.';

        return redirect()->route('client.quotation.status')->with('success', $message);
    }

    public function status()
    {
        $client = Client::findOrFail(session('user_id'));

        $requests = QuotationRequest::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->batch_id ?: ('single-' . $r->id));

        if ($requests->isEmpty()) {
            return redirect()->route('client.quotation.create');
        }

        return view('client.quotation_status', compact('requests'));
    }

    public function approveQuotation($id)
    {
        $client = Client::findOrFail(session('user_id'));

        $quotationRequest = QuotationRequest::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        if ($quotationRequest->status !== 'quotation_sent') {
            return redirect()->route('client.quotation.status')
                ->with('error', 'There is no quotation currently awaiting your approval.');
        }

        $quotationRequest->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        NotificationService::quotationRequestApproved($quotationRequest);

        return redirect()->route('client.quotation.status')
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
                'target_timeline' => $quotationRequest->target_timeline,
            ]],
            'summary' => [
                'location' => $quotationRequest->location,
                'notes'    => $quotationRequest->notes,
            ],
        ]);
    }
}
