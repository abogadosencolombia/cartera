{{-- resources/views/honorarios/liquidacion.blade.php --}}
@php
  // CORREGIDO: Usamos la variable $cliente que envía el controlador.
  $clienteNombre = $cliente->nombre ?? 'ID ' . ($contrato->cliente_id);
@endphp
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Liquidación</title></head>
<body>
    {{-- CORREGIDO: Se usa la variable $contrato en lugar de $c --}}
    <h2>Liquidación Contrato #{{ $contrato->id }}</h2>
    <p><strong>Cliente:</strong> {{ $clienteNombre }}</p>
    <p><strong>Monto:</strong> $ {{ number_format((int)$contrato->monto_total, 0, ',', '.') }}</p>

    <h3>Pagos</h3>
    <table width="100%" border="1" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th>Fecha</th><th>Cuota</th><th>Valor</th><th>Método</th><th>Nota</th>
            </tr>
        </thead>
        <tbody>
        {{-- CORREGIDO: Se itera sobre la variable $pagos --}}
        @forelse($pagos as $p)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                <td>{{ $p->cuota_id ? '#'.$p->cuota_id : '-' }}</td>
                <td>$ {{ number_format((int)$p->valor, 0, ',', '.') }}</td>
                <td>{{ $p->metodo ?? '-' }}</td>
                <td>{{ $p->nota ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin pagos aún.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>