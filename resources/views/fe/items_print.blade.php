<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vista previa — {{ $f->codigo }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 12px; color:#111; }
    h2 { margin: 0 0 4px 0; font-size: 16px; }
    .sub { margin: 0 0 10px 0; color:#555; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 6px; }
    th { background: #f2f2f2; text-align: left; }
    .right { text-align: right; }
    .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:10px; }
    .btn { padding:6px 10px; border:1px solid #333; background:#fff; cursor:pointer; border-radius:4px; font-size:12px; }
    .btn-primary { background:#111; color:#fff; }
    .block { break-inside: avoid; page-break-inside: avoid; }

    @media print {
      @page { size: A4; margin: 8mm; }
      body { padding: 0; }
      .no-print { display:none !important; }
    }
  </style>
</head>

<body>
  <div class="toolbar no-print">
    <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
  </div>

  <div class="block op-header">
    <h1>Orden de Producción</h1>
    <p class="sub">Fecha: <span id="op-fecha"><?= htmlspecialchars($fechaProduccion) ?></span></p>
    <!-- <p class="sub">OP #: <strong><?= htmlspecialchars($numeroOP) ?></strong></p> -->
  </div>


  <div class="block">
    <h2>Ítems de la fórmula</h2>
    <p class="sub">{{ $f->codigo }} — {{ $f->nombre_etiqueta }}</p>
  </div>

  {{-- Tabla ítems (ya viene filtrada desde el controller) --}}
  <div class="block">
    <table>
      <thead>
        <tr>
          <th style="width:90px;">Cod. Odoo</th>
          <th>Activo</th>
          <th class="right" style="width:110px;">Cantidad</th>
          <th style="width:70px;">Unidad</th>
          <th class="right" style="width:110px;">Masa G</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $it)
          @php $masaG = $it->masa_mes !== null ? (float)$it->masa_mes : 0.0; @endphp
          <tr>
            <td>{{ $it->cod_odoo }}</td>
            <td>{{ $it->activo }}</td>
            <td class="right">{{ number_format((float)$it->cantidad, 2, '.', '') }}</td>
            <td>{{ $it->unidad }}</td>
            <td class="right">{{ number_format($masaG, 4, '.', '') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Resumen --}}
  <div class="block" style="margin-top: 10px;">
    <table>
      <tbody>
        <tr>
          <th style="width:260px;">TOTAL PRINCIPIOS ACTIVOS</th>
          <td class="right">{{ number_format((float)($resumen['total_principios_mg_dia'] ?? 0), 2, '.', '') }}</td>
          <td style="width:80px;">mg</td>
        </tr>
        <tr>
          <th>Dosis diaria para 1 cápsula</th>
          <td class="right">{{ number_format((float)($resumen['dosis_caps_mg'] ?? 0), 2, '.', '') }}</td>
          <td>mg</td>
        </tr>
        <tr>
          <th>Celulosa microcristalina (Avicel PH10)</th>
          <td class="right">{{ number_format((float)($resumen['celulosa_caps_mg'] ?? 0), 2, '.', '') }}</td>
          <td>mg</td>
        </tr>
        <tr>
          <th>Contenido total para cápsula 0</th>
          <td class="right">{{ number_format((float)($resumen['contenido_caps_mg'] ?? 0), 2, '.', '') }}</td>
          <td>mg</td>
        </tr>
        <tr>
          <th>DOSIFICACIÓN</th>
          <td class="right">{{ number_format((float)($resumen['dosificacion_caps_dia'] ?? 0), 0, '.', '') }}</td>
          <td>cápsulas diarias</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
