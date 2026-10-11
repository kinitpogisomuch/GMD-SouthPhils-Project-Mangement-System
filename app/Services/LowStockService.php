<?php

namespace App\Services;

use App\Models\MaterialPurchase;
use App\Models\MaterialUsage;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Consolidated low-stock alert.
 *
 * Stock rules are unchanged: a project material's stock is what was actually purchased minus what
 * has been used (active usage logs). It is Low Stock once 25% or less of the purchased quantity is
 * left, and Out of Stock once nothing is left. Only projects still in progress count — a completed
 * or archived project's leftovers don't need restocking.
 *
 * Instead of one notification per material, every admin has at most ONE "Low Stock Alert"
 * notification that always reflects the current list (sync()). It's removed when nothing needs
 * attention, updated in place when items drop off, and re-raised (unread, on top) only when a new
 * material starts needing attention or one runs out — never duplicated by repeated checks.
 */
class LowStockService
{
    public const TYPE = 'low_stock_summary';
    /** Low Stock when remaining stock is at or below this share of what was purchased */
    public const LOW_THRESHOLD = 0.25;

    private const SIGNATURE_KEY = 'low_stock_summary_signature';

    /**
     * Materials needing attention right now, Out of Stock first, then lowest remaining share.
     * Each: project_id, project_name, material_id, material_name, unit, purchased, used,
     * remaining, minimum (the low-stock level), status ('out' | 'low').
     */
    public static function currentItems(): Collection
    {
        $projects = Project::whereNotIn('status', ['completed', 'archived'])->pluck('name', 'id');
        if ($projects->isEmpty()) {
            return collect();
        }
        $projectIds = $projects->keys();

        $purchased = MaterialPurchase::whereIn('project_id', $projectIds)
            ->whereNotNull('project_material_id')
            ->selectRaw('project_material_id, SUM(qty_bought) AS qty')
            ->groupBy('project_material_id')
            ->pluck('qty', 'project_material_id');

        if ($purchased->isEmpty()) {
            return collect();
        }

        $used = MaterialUsage::whereIn('project_id', $projectIds)
            ->whereIn('project_material_id', $purchased->keys())
            ->where('status', 'active')
            ->selectRaw('project_material_id, SUM(quantity_used) AS qty')
            ->groupBy('project_material_id')
            ->pluck('qty', 'project_material_id');

        return ProjectMaterial::whereIn('id', $purchased->keys())
            ->where('status', 'active')
            ->get(['id', 'project_id', 'material_name', 'unit'])
            ->map(function (ProjectMaterial $m) use ($purchased, $used, $projects) {
                $bought    = (float) $purchased[$m->id];
                $usedQty   = (float) ($used[$m->id] ?? 0);
                $remaining = round($bought - $usedQty, 2);
                $minimum   = round($bought * self::LOW_THRESHOLD, 2);

                if ($bought <= 0 || $remaining > $minimum) {
                    return null;   // sufficiently stocked
                }

                return [
                    'project_id'    => $m->project_id,
                    'project_name'  => $projects[$m->project_id] ?? '—',
                    'material_id'   => $m->id,
                    'material_name' => $m->material_name,
                    'unit'          => $m->unit,
                    'purchased'     => $bought,
                    'used'          => $usedQty,
                    'remaining'     => max(0, $remaining),
                    'minimum'       => $minimum,
                    'status'        => $remaining <= 0 ? 'out' : 'low',
                ];
            })
            ->filter()
            ->sortBy(fn ($i) => [$i['status'] === 'out' ? 0 : 1, $i['purchased'] > 0 ? $i['remaining'] / $i['purchased'] : 0])
            ->values();
    }

    /**
     * Bring every admin's single Low Stock Alert notification in line with current stock.
     * Safe to call as often as needed — it never creates more than one notification per admin.
     */
    public static function sync(): void
    {
        $items = self::currentItems();

        // Old per-material alerts are superseded by the consolidated one
        Notification::where('user_type', 'admin')
            ->whereIn('title', ['Low Material Stock', 'Material Stock Depleted'])
            ->delete();

        $admins = User::where('role', 'admin')->pluck('id');

        if ($items->isEmpty()) {
            // nothing needs attention → no alert at all
            Notification::where('user_type', 'admin')->where('notification_type', self::TYPE)->delete();
            Cache::forget(self::SIGNATURE_KEY);
            return;
        }

        // What changed since the last sync? A material newly low/out, or one that ran out,
        // re-raises the alert; materials only dropping off just update it quietly.
        $current  = $items->map(fn ($i) => $i['material_id'] . ':' . $i['status'])->sort()->values()->all();
        $previous = Cache::get(self::SIGNATURE_KEY, []);
        $escalated = count(array_diff($current, $previous)) > 0;
        Cache::forever(self::SIGNATURE_KEY, $current);

        [$title, $message] = self::summaryText($items);

        foreach ($admins as $adminId) {
            $existing = Notification::where('user_type', 'admin')
                ->where('user_id', $adminId)
                ->where('notification_type', self::TYPE)
                ->orderByDesc('id')
                ->get();

            // collapse any accidental duplicates down to the newest one
            if ($existing->count() > 1) {
                Notification::whereIn('id', $existing->slice(1)->pluck('id'))->delete();
            }
            $notification = $existing->first();

            if (!$notification || $escalated) {
                // (re)raise: a fresh unread notification on top of the feed (also pops the toast)
                $notification?->delete();
                Notification::create([
                    'user_id'           => $adminId,
                    'user_type'         => 'admin',
                    'title'             => $title,
                    'message'           => $message,
                    'notification_type' => self::TYPE,
                    'priority'          => 'warning',
                    'action_url'        => '/admin/low-stock',
                    'is_read'           => false,
                ]);
            } elseif ($notification->message !== $message || $notification->title !== $title) {
                // same or fewer materials → refresh the numbers, keep it where it is and as read/unread
                $notification->timestamps = false;
                $notification->forceFill(['title' => $title, 'message' => $message])->save();
            }
        }
    }

    /** sync() at most once per $seconds — used by polled endpoints so page loads stay quick */
    public static function syncThrottled(int $seconds = 30): void
    {
        if (Cache::add('low_stock_summary_synced', true, $seconds)) {
            self::sync();
        }
    }

    /** ["Low Stock Alert", "5 Materials Need Attention\n2 out of stock · 3 low stock"] */
    private static function summaryText(Collection $items): array
    {
        $total = $items->count();
        $out   = $items->where('status', 'out')->count();
        $low   = $total - $out;

        $headline = $total . ' ' . ($total === 1 ? 'Material Needs' : 'Materials Need') . ' Attention';
        $parts = array_filter([
            $out ? $out . ' out of stock' : null,
            $low ? $low . ' low stock' : null,
        ]);

        return ['Low Stock Alert', $headline . "\n" . implode(' · ', $parts)];
    }
}
