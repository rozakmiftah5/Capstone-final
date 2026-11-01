@extends('layouts.app')

@section('title', 'Edit Stock Bahan Kimia (OS)')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">@include('layouts.navigation')
<div class="content-body"><section class="card app-form-card" style="max-width:900px;margin:auto;">
<h1>Edit Stock Bahan Kimia (OS)</h1>
@if($errors->any())<div class="app-flash" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('stock-os.update', $item) }}">@csrf @method('PUT')
<div class="app-form-grid">
<label>Nama Material<input name="material_name" value="{{ old('material_name', $item->material_name) }}" required maxlength="255"></label>
<label>Gudang<select name="warehouse_name" required>
    <option value="">Pilih gudang</option>
    @foreach($managedWarehouses as $warehouse)
        <option value="{{ $warehouse->warehouse_name }}" @selected(old('warehouse_name', $item->warehouse_name) === $warehouse->warehouse_name)>{{ $warehouse->warehouse_name }}</option>
    @endforeach
</select></label>
<label>Stok (kg)<input type="number" name="stock_qty" min="0" step="0.001" value="{{ old('stock_qty', $item->stock_qty) }}" required></label>
<label>Sisa PO (kg)<input type="number" name="outstanding_qty" min="0" step="0.001" value="{{ old('outstanding_qty', $item->outstanding_qty) }}" required></label>
<label>Safety Stock (kg)<input type="number" name="safety_stock" min="0" step="0.001" value="{{ old('safety_stock', $item->safety_stock) }}" required></label>
<label>Catatan<input name="notes" value="{{ old('notes', $item->notes) }}" maxlength="2000"></label>
</div><div class="app-inline-actions"><button class="btn btn-primary" type="submit">Simpan Perubahan</button><a class="btn" href="{{ route('stock-os.index') }}">Kembali</a></div>
</form></section></div></main></div>
@endsection
