<?php

namespace App\Http\Controllers;

use App\Models\StockOsItem;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use App\Services\StockOsExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockOsController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'warehouse' => ['nullable', 'string', 'max:120'],
            'critical' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,stock,outstanding'],
        ]);

        $sort = match ($validated['sort'] ?? 'name') {
            'stock' => 'stock_qty',
            'outstanding' => 'outstanding_qty',
            default => 'material_name',
        };
        $items = $this->filteredStockItems($validated, $sort)
            ->paginate(20)
            ->withQueryString();

        $warehouses = StockOsItem::query()->select('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name');
        $managedWarehouses = Warehouse::query()->orderBy('warehouse_name')->get(['warehouse_id', 'warehouse_name']);
        $stockTotal = StockOsItem::sum('stock_qty');
        $criticalCount = StockOsItem::query()->whereColumn('stock_qty', '<=', 'safety_stock')->count();

        return view('stock-os.index', compact('items', 'warehouses', 'managedWarehouses', 'stockTotal', 'criticalCount', 'validated'));
    }

    public function export(Request $request, StockOsExcelExport $exporter): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'warehouse' => ['nullable', 'string', 'max:120'],
            'critical' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,stock,outstanding'],
        ]);
        $sort = match ($validated['sort'] ?? 'name') {
            'stock' => 'stock_qty',
            'outstanding' => 'outstanding_qty',
            default => 'material_name',
        };
        $items = $this->filteredStockItems($validated, $sort);

        return response()->streamDownload(
            fn () => $exporter->stream($items),
            'laporan-stock-bahan-kimia-os-'.now()->format('Y-m-d_His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_name' => ['required', 'string', 'max:255'],
            'warehouse_name' => ['required', 'string', 'max:255', Rule::exists('warehouses', 'warehouse_name')],
            'stock_qty' => ['required', 'numeric', 'min:0'],
            'outstanding_qty' => ['required', 'numeric', 'min:0'],
            'safety_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['last_updated'] = today();

        StockOsItem::create($validated);

        return to_route('stock-os.index')->with('success', 'Data Stock Bahan Kimia (OS) berhasil ditambahkan.');
    }

    public function edit(StockOsItem $stockOsItem): View
    {
        $managedWarehouses = Warehouse::query()->orderBy('warehouse_name')->get(['warehouse_id', 'warehouse_name']);

        return view('stock-os.edit', ['item' => $stockOsItem, 'managedWarehouses' => $managedWarehouses]);
    }

    public function update(Request $request, StockOsItem $stockOsItem): RedirectResponse
    {
        $validated = $request->validate([
            'material_name' => ['required', 'string', 'max:255'],
            'warehouse_name' => ['required', 'string', 'max:255', Rule::exists('warehouses', 'warehouse_name')],
            'stock_qty' => ['required', 'numeric', 'min:0'],
            'outstanding_qty' => ['required', 'numeric', 'min:0'],
            'safety_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['last_updated'] = today();

        $stockOsItem->update($validated);

        return to_route('stock-os.index')->with('success', 'Data Stock Bahan Kimia (OS) berhasil diperbarui.');
    }

    public function destroy(StockOsItem $stockOsItem): RedirectResponse
    {
        $stockOsItem->delete();

        return to_route('stock-os.index')->with('success', 'Data Stock Bahan Kimia (OS) berhasil dihapus.');
    }

    public function useStock(Request $request, StockOsItem $stockOsItem): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $stockOsItem, $validated): void {
            $item = StockOsItem::query()->lockForUpdate()->findOrFail($stockOsItem->id);
            if ((float) $item->stock_qty < (float) $validated['qty']) {
                throw ValidationException::withMessages([
                    'qty' => 'Jumlah pemakaian melebihi stok yang tersedia.',
                ]);
            }

            $item->decrement('stock_qty', $validated['qty']);
            $item->update(['last_updated' => today()]);
            $item->transactions()->create([
                'user_id' => $request->user()->user_id,
                'type' => 'OUT',
                'qty' => $validated['qty'],
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return to_route('stock-os.history', $stockOsItem)
            ->with('success', 'Pemakaian stok berhasil dicatat.');
    }

    public function receiveStock(Request $request, StockOsItem $stockOsItem): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'gt:0'],
            'supplier' => ['required', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $stockOsItem, $validated): void {
            $item = StockOsItem::query()->lockForUpdate()->findOrFail($stockOsItem->id);
            $item->increment('stock_qty', $validated['qty']);
            $item->update([
                'outstanding_qty' => max(0, (float) $item->outstanding_qty - (float) $validated['qty']),
                'last_updated' => today(),
            ]);
            $item->transactions()->create([
                'user_id' => $request->user()->user_id,
                'type' => 'IN',
                'qty' => $validated['qty'],
                'supplier' => $validated['supplier'],
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return to_route('stock-os.history', $stockOsItem)
            ->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    public function history(StockOsItem $stockOsItem): View
    {
        $transactions = StockTransaction::query()
            ->whereBelongsTo($stockOsItem, 'item')
            ->with('user:user_id,email')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(50);

        return view('stock-os.history', compact('stockOsItem', 'transactions'));
    }

    private function filteredStockItems(array $filters, string $sort): Builder
    {
        $search = trim($filters['search'] ?? '');

        return StockOsItem::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('material_name', 'like', "%{$search}%")
                    ->orWhere('warehouse_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->when(($filters['warehouse'] ?? '') !== '', fn ($query) => $query->where('warehouse_name', $filters['warehouse']))
            ->when((bool) ($filters['critical'] ?? false), fn ($query) => $query->whereColumn('stock_qty', '<=', 'safety_stock'))
            ->orderBy($sort);
    }
}
