<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;

class ReceiptController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.medicine']);

        return view('receipts.sale', [
            'sale' => $sale,
            'settings' => Setting::current(),
        ]);
    }
}
