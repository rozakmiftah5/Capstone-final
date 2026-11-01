<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form LPPM {{ $materialRequest->request_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0 0 4px; font-size: 18px; text-align: center; }
        h2 { margin: 18px 0 8px; border-bottom: 1px solid #9ca3af; padding-bottom: 5px; font-size: 13px; }
        p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .meta th { width: 22%; }
        .material { page-break-inside: avoid; }
        .fields th { width: 30%; }
        .subtitle { margin-bottom: 16px; text-align: center; color: #4b5563; }
    </style>
</head>
<body>
    <h1>FORMULIR PENGAJUAN PENAMBAHAN KODE MATERIAL</h1>
    <p class="subtitle">Form LPPM · {{ $materialRequest->request_number }}</p>

    <table class="meta">
        <tbody>
            <tr><th>Nomor Surat</th><td>{{ $materialRequest->letter_number ?: '-' }}</td></tr>
            <tr><th>Divisi Peminta</th><td>{{ $materialRequest->requesting_division ?: '-' }}</td></tr>
            <tr><th>Dinas Peminta</th><td>{{ $materialRequest->requesting_department ?: '-' }}</td></tr>
            <tr><th>Tanggal Pengajuan</th><td>{{ $materialRequest->request_date?->format('d/m/Y') ?? '-' }}</td></tr>
            <tr><th>Pemohon</th><td>{{ $materialRequest->requester?->username ?? $materialRequest->requester?->email ?? 'Pengguna terhapus' }}</td></tr>
            <tr><th>Status</th><td>{{ $materialRequest->status === 'completed' ? 'Selesai' : $materialRequest->status }}</td></tr>
        </tbody>
    </table>

    @php
        $materialFields = [
            'mtart' => 'Material Type (MTART)',
            'werks' => 'Plant (WERKS)',
            'lgort' => 'Storage Location (LGORT)',
            'maktx' => 'Material Description (MAKTX)',
            'meins' => 'Base Unit of Measure (MEINS)',
            'purchasingGroup' => 'Purchasing Group',
            'matkl' => 'Material Group (MATKL)',
            'dismm' => 'MRP Type (DISMM)',
            'mtvfp' => 'Checking Group (MTVFP)',
            'sernp' => 'Serial Number Profile (SERNP)',
            'prctr' => 'Profit Center (PRCTR)',
            'bklas' => 'Valuation Class (BKLAS)',
            'vprsv' => 'Price Control Indicator (VPRSV)',
            'peinh' => 'Price Unit (PEINH)',
            'verpr' => 'Moving Average Price (VERPR)',
            'bwtty' => 'Valuation Category (BWTTY)',
            'longText' => 'Long Text Material',
        ];
    @endphp

    @foreach($materialRequest->items as $index => $item)
        <section class="material">
            <h2>Material {{ $index + 1 }}: {{ $item->material_description }}</h2>
            <table class="fields">
                <tbody>
                    <tr><th>Nomor Kode Material SAP</th><td>{{ $item->sap_material_code ?: 'Belum ada feedback' }}</td></tr>
                    @foreach($materialFields as $field => $label)
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ filled($item->details[$field] ?? null) ? $item->details[$field] : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</body>
</html>
