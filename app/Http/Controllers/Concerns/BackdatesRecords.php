<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

trait BackdatesRecords
{
    /**
     * Override created_at/updated_at on a not-yet-saved model when backfilling
     * historical data. Leaves the model's normal auto-timestamping untouched
     * when no date is given.
     */
    protected function applyBackdate(Model $model, ?string $date, ?string $time = null): void
    {
        if (!$date) {
            return;
        }

        $at = Carbon::parse($date . ' ' . ($time ?: '00:00'));
        $model->created_at = $at;
        $model->updated_at = $at;
    }
}
