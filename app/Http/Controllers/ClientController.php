<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Payment;
use App\Models\Client;

class ClientController extends Controller
{
    private function clientName(): ?string
    {
        $email = session('email');
        if (!$email) return null;
        return Client::where('email', $email)->value('name');
    }

    public function dashboard()
    {
        $clientName = $this->clientName();
        $projects = $clientName
            ? Project::where('client', $clientName)->orderBy('created_at', 'desc')->take(3)->get()
            : collect();

        $payments = $clientName
            ? Payment::where('client', $clientName)->orderBy('created_at', 'desc')->take(3)->get()
            : collect();

        return view('client.dashboard', compact('projects', 'payments'));
    }

    public function projectList()
    {
        $clientName = $this->clientName();
        $projects = $clientName
            ? Project::where('client', $clientName)->orderBy('created_at', 'desc')->get()
            : collect();

        return view('client.projects', compact('projects'));
    }

    public function documents()
    {
        return view('client.documents');
    }

    public function messages()
    {
        return view('client.messages');
    }

    public function settings()
    {
        $client = Client::findOrFail(session('user_id'));

        return view('client.settings', compact('client'));
    }

    public function payments()
    {
        $clientName = $this->clientName();

        if ($clientName) {
            $payments     = Payment::where('client', $clientName)->orderBy('created_at', 'desc')->get();
            $totalPaid    = Payment::where('client', $clientName)->where('status', 'Paid')->sum('contract_amount');
            $totalPending = Payment::where('client', $clientName)->where('status', 'Pending')->sum('balance');
        } else {
            $payments     = collect();
            $totalPaid    = 0;
            $totalPending = 0;
        }

        return view('client.payments', compact('payments', 'totalPaid', 'totalPending'));
    }
}