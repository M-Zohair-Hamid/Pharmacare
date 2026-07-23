<?php

use App\Http\Controllers\ReceiptController;
use App\Livewire\Bin;
use App\Livewire\Dashboard;
use App\Livewire\Medicines\Index as MedicinesIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Sales\History as SalesHistory;
use App\Livewire\Sales\Pos;
use App\Livewire\Settings;
use App\Livewire\Suppliers\Index as SuppliersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/medicines', MedicinesIndex::class)->name('medicines.index');
Route::get('/sales', Pos::class)->name('sales.pos');
Route::get('/sales/history', SalesHistory::class)->name('sales.history');
Route::get('/sales/{sale}/receipt', [ReceiptController::class, 'show'])->name('sales.receipt');
Route::get('/purchases', PurchasesIndex::class)->name('purchases.index');
Route::get('/suppliers', SuppliersIndex::class)->name('suppliers.index');
Route::get('/settings', Settings::class)->name('settings.index');
Route::get('/bin', Bin::class)->name('bin.index');
