<?php

namespace App\Http\Controllers;

use App\Models\Sale;

class ReceiptController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.medicine']);

        return view('receipts.sale', ['sale' => $sale]);
    }
}
