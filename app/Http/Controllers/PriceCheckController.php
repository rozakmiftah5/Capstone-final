<?php

namespace App\Http\Controllers;

use App\Services\PriceLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceCheckController extends Controller
{
    public function __invoke(Request $request, PriceLookupService $priceLookup): View
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
        ]);
        $code = trim($validated['code'] ?? '');

        return view('price-check.index', [
            'code' => $code,
            ...$priceLookup->search($code),
            'hasSearched' => $request->has('code'),
        ]);
    }
}
