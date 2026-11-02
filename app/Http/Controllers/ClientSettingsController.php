<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Client;

class ClientSettingsController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        return view('admin.settings', compact('clients'));
    }

    public function pendingCount()
    {
        return response()->json([
            'count' => Client::awaitingApproval()->count(),   // only email-verified sign-ups
        ]);
    }

    public function destroy($id)
    {
        Client::findOrFail($id)->delete();

        return redirect()->route('admin.clients')
            ->with('success', 'Client deleted successfully!');
    }

    public function archive($id)
    {
        $client    = Client::findOrFail($id);
        $restore   = ($client->status === 'Inactive');
        $newStatus = $restore ? 'Active' : 'Inactive';

        $client->update(['status' => $newStatus]);

        return redirect()->route('admin.clients')
            ->with('success', $restore ? 'Client restored successfully.' : 'Client archived successfully.');
    }

    /** Only email-verified sign-ups can be approved or rejected */
    private function unverifiedRedirect(Client $client)
    {
        if ($client->status === 'Pending' && !$client->hasVerifiedEmail()) {
            return redirect()->route('admin.clients')
                ->with('error', $client->name . ' hasn\'t verified their email yet, so the account can\'t be reviewed.');
        }
        return null;
    }

    /**
     * Approve a verified registration request → status Approved (Account Setup Required) and email
     * the client a one-time Set Up Account link. They become Active only once they've set their
     * password on that page — approval itself never lets anyone log in.
     */
    public function approve(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        if ($blocked = $this->unverifiedRedirect($client)) return $blocked;

        // Only a request that's pending admin approval can be approved (not active/rejected/archived ones)
        if ($client->status !== 'Pending') {
            return redirect()->route('admin.clients')
                ->with('error', $client->name . ' is not awaiting approval (status: ' . $client->registrationStatusLabel() . ').');
        }

        $approvedAt = now();

        $client->update(['status' => 'Approved']);   // Approved – Account Setup Required

        \App\Services\NotificationService::clientApproved($client, $approvedAt);

        // Sent only after the approval is saved
        $sent = AccountSetupController::sendSetupEmail($client);

        return redirect()->route('admin.clients')->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Registration approved. ' . $client->name . ' has been emailed a link to set up their account.'
                : 'Registration approved, but the setup email could not be sent. Use "Resend setup link" to try again.'
        );
    }

    /** Re-send the Set Up Account link to an approved client (e.g. their link expired or the email failed) */
    public function resendSetupLink($id)
    {
        $client = Client::findOrFail($id);

        if ($client->status !== 'Approved') {
            return redirect()->route('admin.clients')
                ->with('error', $client->name . ' is not waiting for account setup.');
        }

        $sent = AccountSetupController::sendSetupEmail($client);

        return redirect()->route('admin.clients')->with(
            $sent ? 'success' : 'error',
            $sent ? 'A new account setup link has been emailed to ' . $client->email . '.' : 'The setup email could not be sent. Please try again.'
        );
    }

    public function reject(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        if ($blocked = $this->unverifiedRedirect($client)) return $blocked;
        $client->update([
            'status'            => 'Rejected',
            'rejection_reason'  => $request->input('reason'),
        ]);

        \App\Services\NotificationService::clientRejected($client, $request->input('reason'));

        if ($client->email) {
            try {
                Mail::html(
                    $this->buildRejectionEmailHtml($client->name),
                    function ($message) use ($client) {
                        $message->to($client->email, $client->name)
                                ->from(config('mail.from.address'), config('mail.from.name'))
                                ->replyTo(config('mail.from.address'), config('mail.from.name'))
                                ->subject('Update on Your GMD South Phils Account Application');
                    }
                );
                Log::info('ClientSettings: rejection email sent', ['email' => $client->email]);
            } catch (\Exception $e) {
                Log::error('ClientSettings: rejection email failed', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.clients')
            ->with('success', 'Client application rejected.');
    }

    public function list()
    {
        $clients = Client::orderBy('last_name')->orderBy('first_name')
                         ->get(['id', 'name', 'first_name', 'last_name', 'address', 'province', 'city', 'contact', 'email'])
                         ->map(function ($c) {
                             return [
                                 'id'      => $c->id,
                                 'name'    => $c->full_name,
                                 'address' => ($c->province && $c->city)
                                              ? $c->province . ', ' . $c->city
                                              : ($c->address ?? ''),
                                 'contact' => $c->contact,
                                 'email'   => $c->email,
                             ];
                         });

        return response()->json($clients);
    }

    private function buildRejectionEmailHtml(string $name): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                .container { background: #fff; max-width: 520px; margin: 0 auto; border-radius: 12px; padding: 36px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
                .logo { font-size: 20px; font-weight: 900; color: #1a1a2e; margin-bottom: 24px; }
                .logo span { color: #e8900a; }
                .badge { display: inline-block; background: #fee2e2; color: #b91c1c; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 999px; padding: 6px 14px; margin-bottom: 18px; }
                h2 { font-size: 18px; color: #1a1a2e; margin-bottom: 8px; }
                p { font-size: 14px; color: #444; line-height: 1.6; }
                ul { margin: 0 0 14px; padding-left: 20px; }
                li { font-size: 14px; color: #444; line-height: 1.6; margin-bottom: 4px; }
                .footer { margin-top: 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">GMD <span>South Phils</span></div>
                <div class="badge">Application Not Approved</div>
                <h2>Update on your account application</h2>
                <p>Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Thank you for your interest in the GMD South Phils client portal. After careful review of your application, we regret to inform you that we are unable to approve your account at this time.</p>
                <p>Applications are generally not approved for one or more of the following reasons:</p>
                <ul>
                    <li>The information provided during sign-up was incomplete or inaccurate.</li>
                    <li>We were unable to verify the identity or contact details submitted.</li>
                    <li>An account has already been registered under the same name or email address.</li>
                    <li>The application does not correspond to an existing or prospective project with our company.</li>
                </ul>
                <p>Should you have any questions regarding this decision, or if you believe it was made in error, you may reply to this email and our team will be glad to assist you.</p>
                <p>We appreciate your understanding.</p>

                <div class="footer">
                    Respectfully,<br>
                    <strong>GMD SouthPhils Metal Fabrication Works</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}
