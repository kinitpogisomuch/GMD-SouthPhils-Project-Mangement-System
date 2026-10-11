<?php

namespace App\Http\Controllers;

use App\Services\LowStockService;

/** Opened from the consolidated "Low Stock Alert" notification: every material that needs attention. */
class LowStockController extends Controller
{
    public function index()
    {
        // Refresh first, so the page and the notification always agree with current stock
        LowStockService::sync();

        $items = LowStockService::currentItems();

        return view('admin.low_stock', [
            'items'    => $items,
            'outCount' => $items->where('status', 'out')->count(),
            'lowCount' => $items->where('status', 'low')->count(),
        ]);
    }
}
