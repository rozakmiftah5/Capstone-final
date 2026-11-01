<?php

namespace App\Services;

use App\Models\MasterMaterial;
use App\Models\PoHistory;

class PriceLookupService
{
    public function search(string $term): array
    {
        $term = trim($term);
        $material = $term === ''
            ? null
            : MasterMaterial::query()
                ->where('material_code', $term)
                ->first();

        $historyQuery = PoHistory::query();
        if ($term === '') {
            $historyQuery->whereRaw('1 = 0');
        } elseif ($material !== null) {
            $historyQuery->where('material_code', $material->material_code);
        } else {
            $pattern = "%{$term}%";
            $matchingMaterialCodes = MasterMaterial::query()
                ->select('material_code')
                ->where('short_text', 'like', $pattern);

            $historyQuery->where(function ($query) use ($matchingMaterialCodes, $pattern): void {
                $query->whereIn('material_code', $matchingMaterialCodes)
                    ->orWhere('short_text', 'like', $pattern);
            });

            $material = MasterMaterial::query()
                ->where('short_text', 'like', $pattern)
                ->orderBy('material_code')
                ->first();
        }

        $latest = (clone $historyQuery)
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->first();
        $history = $historyQuery
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return compact('term', 'material', 'latest', 'history');
    }
}
