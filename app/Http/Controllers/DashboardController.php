<?php

namespace App\Http\Controllers;

use App\Services\PriceLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PriceLookupService $priceLookup): View|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
        ]);
        $code = trim($validated['code'] ?? '');
        $data = [
            'code' => $code,
            'hasSearched' => $request->has('code'),
            ...$priceLookup->search($code),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('dashboard.partials.price-search-results', $data)->render(),
            ]);
        }

        return view('dashboard', $data);
    }
}
