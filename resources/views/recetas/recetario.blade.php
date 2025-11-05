@php
  // Utilidades
  function nombreCorto($full) {
    $p = preg_split('/\s+/u', trim((string)$full));
    return trim(($p[0] ?? '').' '.($p[count($p)-1] ?? ''));
  }
  function nf($n) { return number_format((float)$n, 2); }

  $doctor = $medico->full_name;
  $pac    = $receta->paciente;
  $prod   = $formula->nombre_etiqueta ?? $formula->nombre ?? $formula->codigo;
  $tomas  = (int)($formula->tomas_diarias ?? 1);
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Recetario – #{{ $receta->id_receta }}</title>
<style>
  :root { --fg:#111; --muted:#666; --line:#ccc; }
  * { box-sizing: border-box; }
  body { font-family: Helvetica, Arial, sans-serif; color:var(--fg); margin:28px; }
  .row { display:flex; justify-content:space-between; align-items:center; gap:12px; }
  .muted { color:var(--muted); }
  .h1 { font-size:18px; font-weight:600; letter-spacing:.3px; }
  hr { border:none; border-top:1px solid var(--line); margin:14px 0 18px; }
  .label { font-weight:600; }
  .title { font-weight:700; margin:12px 0 6px; }
  .poso { font-weight:700; }
  .grid { display:grid; grid-template-columns: 1fr 110px; gap:4px 18px; max-width:520px; }
  .firma { height:110px; display:flex; flex-direction:column; justify-content:flex-end; gap:6px; }
  .firma img { max-height:80px; max-width:320px; object-fit:contain; }
  .subtle { font-size:13px; color:var(--muted); }
  @media print { .no-print { display:none !important; } body { margin:24px; } }
</style>
</head>
<body>

<div class="no-print" style="text-align:right;margin-bottom:10px">
  <button onclick="window.print()">Imprimir</button>
</div>

<div class="row">
  <div class="h1">RECETARIO MEDICO</div>
  <div class="h1">DR/A {{ $doctor }}</div>
</div>

<hr>

<div class="row">
  <div><span class="label">Fecha:</span> {{ \Carbon\Carbon::parse($receta->fecha)->format('d-m-Y') }}</div>
  <div><span class="label">Paciente:</span> {{ $pac ?: '—' }}</div>
</div>

<div style="margin-top:10px">
  <div><span class="label">SO:</span> {{ $receta->so }}</div>
</div>

<div style="margin-top:16px">
  <div class="label">Producto:</div>
  <div style="margin-top:2px">{{ $prod }}</div>
</div>

<div style="margin-top:16px">
  <div class="label">Posología:</div>
  <div class="poso" style="margin-top:6px">TOMAR {{ $tomas }} CÁPSULAS DIARIAS</div>
</div>

<div style="margin-top:22px">
  <div class="label">Composición:</div>
  <div class="grid" style="margin-top:10px">
    @foreach($items as $it)
      <div style="font-weight:600">{{ $it->activo }}</div>
      <div style="text-align:right">{{ nf($it->cantidad) }} {{ $it->unidad ?? 'mg' }}</div>
    @endforeach
  </div>
</div>

<div style="margin-top:60px">
  <div class="firma">
    @if(!empty($firmaUrl))
      <img src="{{ $firmaUrl }}" alt="Firma del médico" style="height:70px;">
    @else
      <em>Sin firma registrada</em>
    @endif
    <div>----------------------</div>
    <div>Firma</div>
  </div>

  <div class="subtle" style="margin-top:16px">
    <div>Correo: {{ $medico->correo ?? '—' }}</div>
    <div>Teléfono: {{ $medico->telefono ?? '—' }}</div>
    <div>Dirección: {{ $medico->direccion ?? '—' }}</div>
  </div>
</div>

</body>
</html>
