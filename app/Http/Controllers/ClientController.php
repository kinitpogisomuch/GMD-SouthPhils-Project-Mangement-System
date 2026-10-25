<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Review;
use App\Models\QuotationRequest;

class ClientController extends Controller
{
    private function clientName(): ?string
    {
        $email = session('email');
        if (!$email) return null;
        return Client::where('email', $email)->value('name');
    }

    /**
     * This client's own projects. Scoped primarily by client_id (stable —
     * survives the client renaming or editing their profile), with a
     * name-matching fallback for the rare project that predates the link.
     */
    private function ownProjectsQuery()
    {
        $clientId   = session('user_id');
        $clientName = $this->clientName();

        return Project::where(function ($q) use ($clientId, $clientName) {
            $q->where('client_id', $clientId);
            if ($clientName) {
                $q->orWhere(function ($q2) use ($clientName) {
                    $q2->whereNull('client_id')->where('client', $clientName);
                });
            }
        });
    }

    /** This client's own payments, scoped the same way via their project. */
    private function ownPaymentsQuery()
    {
        $clientId   = session('user_id');
        $clientName = $this->clientName();

        return Payment::with('project')->whereHas('project', function ($q) use ($clientId, $clientName) {
            $q->where(function ($q2) use ($clientId, $clientName) {
                $q2->where('client_id', $clientId);
                if ($clientName) {
                    $q2->orWhere(function ($q3) use ($clientName) {
                        $q3->whereNull('client_id')->where('client', $clientName);
                    });
                }
            });
        });
    }

    public function dashboard()
    {
        $hasProject = $this->ownProjectsQuery()->exists();

        if (!$hasProject) {
            $client = Client::find(session('user_id'));

            if ($client) {
                // One Quotation module: it opens on the Pending tab by itself when something is under review.
                return redirect()->route('client.quotation.create');
            }
        }

        $projects = $this->ownProjectsQuery()->orderBy('created_at', 'desc')->get();
        $payments = $this->ownPaymentsQuery()->with('transactions')->orderBy('created_at', 'desc')->get();

        // what's going on: projects still being built vs. finished ones
        $activeProjects = $projects->reject(fn ($p) => in_array($p->status, ['completed', 'archived'], true))->values();
        $completedCount = $projects->where('status', 'completed')->count();

        // money: contract value, what's been paid, what's left — over every project's payment record
        $contractTotal = round($payments->sum(fn ($p) => (float) $p->contract_amount), 2);
        $paidTotal     = round($payments->sum(fn ($p) => $p->totalPaid()), 2);
        $balanceTotal  = max(0, round($contractTotal - $paidTotal, 2));

        // action needed: payments the client can make right now, and quotations waiting for their answer
        $paymentsDue = $payments->map(function ($p) {
            $due = $p->onlinePayableStage();
            return $due ? ['payment' => $p, 'stage' => ucwords(str_replace('_', ' ', $due['stage'])), 'amount' => $due['amount']] : null;
        })->filter()->values();

        $openQuotes = QuotationRequest::with('quotationBatch')
            ->where('client_id', session('user_id'))
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get();
        $quotesAwaiting = $openQuotes->where('status', QuotationRequest::STATUS_SENT)->values();
        // approved quotations still waiting for the client's Purchase Order (one per quotation)
        $quotesNeedPo = $openQuotes->filter(fn ($q) => $q->status === QuotationRequest::STATUS_APPROVED && !$q->hasPurchaseOrder())
            ->unique('batch_id')->values();

        // the latest money in, across all projects
        $recentTransactions = $payments->flatMap(fn ($p) => $p->transactions->map(fn ($t) => [
            'project' => $p->project->name ?? '—',
            'stage'   => ucwords(str_replace('_', ' ', (string) $t->payment_stage)),
            'amount'  => (float) $t->amount_paid,
            'date'    => $t->payment_date,
            'payment' => $p,
        ]))->sortByDesc('date')->take(6)->values();

        return view('client.dashboard', compact(
            'projects', 'payments', 'activeProjects', 'completedCount',
            'contractTotal', 'paidTotal', 'balanceTotal',
            'paymentsDue', 'openQuotes', 'quotesAwaiting', 'quotesNeedPo', 'recentTransactions'
        ));
    }

    public function projectList()
    {
        $projects = $this->ownProjectsQuery()->orderBy('created_at', 'desc')->get();

        $reviews = Review::whereIn('project_id', $projects->pluck('id'))->get()->keyBy('project_id');

        return view('client.projects', compact('projects', 'reviews'));
    }

    public function settings()
    {
        $client = Client::findOrFail(session('user_id'));

        return view('client.settings', compact('client'));
    }

    public function payments()
    {
        $payments      = $this->ownPaymentsQuery()->orderBy('created_at', 'desc')->get();
        $onlineEnabled = app(\App\Services\OnlinePayments::class)->enabled();

        return view('client.payments', compact('payments', 'onlineEnabled'));
    }
}