<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every form submitted the normal way carries a one-time `_submission_token` (added by the submit guard in
 * partials/submit_guard). The first request with a token goes through; a repeat with the same token — a
 * double click that slipped through, a browser refresh that re-posts the form, or two simultaneous
 * requests — is refused before it reaches the controller, so nothing is saved twice.
 *
 * The token is claimed with a unique insert, so two requests arriving at the same moment cannot both win.
 * If the request fails in a way that is safe to retry (validation errors, or a server error) the token is
 * released, so the same form can be submitted again.
 */
class PreventDuplicateSubmission
{
    /** Tokens older than this are cleaned up now and then. */
    private const KEEP_HOURS = 48;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('_submission_token');

        if (!$request->isMethod('POST') || !is_string($token) || !preg_match('/^[A-Za-z0-9-]{16,64}$/', $token)) {
            return $next($request);
        }

        $claimed = DB::table('submission_tokens')->insertOrIgnore([
            'token'      => $token,
            'path'       => mb_substr($request->path(), 0, 255),
            'created_at' => now(),
        ]);

        if (!$claimed) {
            $message = 'This form was already submitted, so it was not sent again.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'duplicate' => true], 409);
            }
            return redirect()->back()->with('error', $message);
        }

        if (random_int(1, 50) === 1) {
            DB::table('submission_tokens')->where('created_at', '<', now()->subHours(self::KEEP_HOURS))->delete();
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->release($token);
            throw $e;
        }

        // Nothing was saved when validation failed or the server errored — let the form be sent again.
        $failedValidation = $request->hasSession()
            && in_array('errors', (array) $request->session()->get('_flash.new', []), true);
        if ($response->getStatusCode() >= 422 || $failedValidation) {
            $this->release($token);
        }

        return $response;
    }

    private function release(string $token): void
    {
        DB::table('submission_tokens')->where('token', $token)->delete();
    }
}
