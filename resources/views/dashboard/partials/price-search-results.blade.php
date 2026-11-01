@if($hasSearched)
    <div class="app-dashboard-price-result">
        <div class="app-dashboard-price-result-heading">
            <strong>Hasil Quick Check</strong>
            <button type="button" class="app-dashboard-price-dismiss" data-dismiss-price-results>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Tutup hasil</span>
            </button>
        </div>
        @if($latest)
            <div class="app-dashboard-price-summary">
                <div>
                    <span class="badge badge-success">Data ditemukan</span>
                    <h3>{{ $material?->short_text ?? $latest->short_text ?? 'Material ' . $term }}</h3>
                    <p>Kode: {{ $material?->material_code ?? $latest->material_code }} · Grup: {{ $material?->material_group ?? $latest->material_group ?? '-' }}</p>
                </div>
                <div class="app-dashboard-price-value">
                    <span>Harga satuan terakhir</span>
                    <strong>{{ $latest->currency }} {{ number_format((float) $latest->unit_price, 0, ',', '.') }}</strong>
                    <small>{{ $latest->po_date?->format('d/m/Y') ?? '-' }} · {{ $latest->vendor ?: 'Vendor tidak tercatat' }}</small>
                </div>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead><tr><th>Tanggal</th><th>PO</th><th>Vendor</th><th>Qty</th><th>Harga Satuan</th></tr></thead>
                    <tbody>
                        @foreach($history as $item)
                            <tr>
                                <td>{{ $item->po_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="app-po-detail-toggle"
                                        data-toggle-po-details
                                        aria-controls="po-detail-{{ $item->id }}"
                                        aria-expanded="false"
                                        aria-label="Tampilkan detail PO {{ $item->po_number }}"
                                    >{{ $item->po_number }}</button>
                                </td>
                                <td>{{ $item->vendor ?: '-' }}</td>
                                <td>{{ number_format((float) $item->qty, 3, ',', '.') }} {{ $item->uom }}</td>
                                <td>{{ $item->currency }} {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            </tr>
                            <tr id="po-detail-{{ $item->id }}" class="app-po-detail-row" hidden>
                                <td colspan="5">
                                    <dl class="app-po-detail-grid">
                                        <div><dt>Nomor PO</dt><dd>{{ $item->po_number }}</dd></div>
                                        <div><dt>Tanggal</dt><dd>{{ $item->po_date?->format('d/m/Y') ?? '-' }}</dd></div>
                                        <div><dt>Kode material</dt><dd>{{ $item->material_code }}</dd></div>
                                        <div><dt>Deskripsi material</dt><dd>{{ $item->short_text ?: '-' }}</dd></div>
                                        <div><dt>Grup material</dt><dd>{{ $item->material_group ?: '-' }}</dd></div>
                                        <div><dt>Vendor</dt><dd>{{ $item->vendor ?: '-' }}</dd></div>
                                        <div><dt>Jumlah</dt><dd>{{ number_format((float) $item->qty, 3, ',', '.') }} {{ $item->uom }}</dd></div>
                                        <div><dt>Harga satuan</dt><dd>{{ $item->currency }} {{ number_format((float) $item->unit_price, 0, ',', '.') }}</dd></div>
                                        <div><dt>Total harga</dt><dd>{{ $item->currency }} {{ number_format((float) $item->total_price, 0, ',', '.') }}</dd></div>
                                    </dl>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="app-pagination">{{ $history->links() }}</div>
        @else
            <div class="app-dashboard-price-empty" role="status">
                <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                <div><strong>Material tidak ditemukan</strong><p>Periksa kembali nomor atau nama material yang Anda masukkan.</p></div>
            </div>
        @endif
    </div>
@endif
