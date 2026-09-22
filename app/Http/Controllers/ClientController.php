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
                $unresolvedRequest = QuotationRequest::where('client_id', $client->id)
                    ->unresolved()
                    ->latest()
                    ->first();

                return $unresolvedRequest
                    ? redirect()->route('client.quotation.status')
                    : redirect()->route('client.quotation.create');
            }
        }

        $projects = $this->ownProjectsQuery()->orderBy('created_at', 'desc')->take(3)->get();
        $payments = $this->ownPaymentsQuery()->orderBy('created_at', 'desc')->take(3)->get();

        return view('client.dashboard', compact('projects', 'payments'));
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
        $payments = $this->ownPaymentsQuery()->orderBy('created_at', 'desc')->get();

        return view('client.payments', compact('payments'));
    }
}