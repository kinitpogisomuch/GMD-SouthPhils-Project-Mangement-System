<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Writes the audit trail for quotation revisions, client decisions, payment confirmations and admin
 * exceptions. The actor is taken from the session (admin / client / employee) unless one is passed —
 * gateway webhooks and other background work record themselves as "provider" or "system".
 * A failure to write the log never breaks the action being logged.
 */
class AuditTrail
{
    public static function record(string $type, $id, string $action, array $details = [], ?array $actor = null): void
    {
        try {
            $actor ??= self::sessionActor();

            AuditLog::create([
                'auditable_type' => $type,
                'auditable_id'   => (string) $id,
                'action'         => $action,
                'actor_type'     => $actor['type'] ?? null,
                'actor_id'       => $actor['id'] ?? null,
                'actor_name'     => $actor['name'] ?? null,
                'details'        => $details ?: null,
                'ip_address'     => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function system(string $name = 'System'): array
    {
        return ['type' => 'system', 'id' => null, 'name' => $name];
    }

    public static function provider(string $name = 'PayMongo'): array
    {
        return ['type' => 'provider', 'id' => null, 'name' => $name];
    }

    private static function sessionActor(): array
    {
        if (!session('role')) {
            return self::system();
        }

        return [
            'type' => session('role'),
            'id'   => session('user_id'),
            'name' => session('name') ?: session('full_name'),
        ];
    }
}
