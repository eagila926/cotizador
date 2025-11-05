<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 28px; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#000; }
  h1 { font-size: 20px; margin: 0 0 12px; font-weight: 700; }
  .row { width:100%; }
  .col-6 { display:inline-block; width:49%; vertical-align:top; }
  .right { text-align:right; }
  .label { font-weight:700; }
  .mt-1{ margin-top:6px; } .mt-2{ margin-top:10px; } .mt-3{ margin-top:16px; } .mt-4{ margin-top:22px; }
  .mb-1{ margin-bottom:6px; } .mb-2{ margin-bottom:10px; } .mb-3{ margin-bottom:16px; }
  .hr { border:0; height:1px; background:#000; margin: 10px 0 14px; }
  .comp { width:100%; border-collapse:collapse; }
  .comp td { padding:3px 0; }
  .comp td:nth-child(1){ font-weight:700; }
  .comp td:nth-child(2){ text-align:right; font-weight:700; white-space:nowrap; }
  .firma-box { margin-top:30px; }
  .firma-img { height:70px; }
  .footer { font-size:11px; margin-top:8px; line-height:1.3; }
</style>
</head>
<body>

  {{-- Encabezado --}}
  <div class="row">
    <div class="col-6">
      <h1>RECETARIO MEDICO</h1>
    </div>
    <div class="col-6 right">
        <div class="label">{{ 'DR/A '.mb_strtoupper($doctorDisplay ?? '', 'UTF-8') }}</div>
    </div>
  </div>
  <hr class="hr">

  {{-- Fecha / SO / Paciente --}}
  <div class="row">
    <div class="col-6">
      <div class="mb-1"><span class="label">Fecha:</span> {{ \Carbon\Carbon::parse($receta->fecha)->format('d-m-Y') }}</div>
      <div class="mb-1"><span class="label">SO:</span> {{ $receta->so }}</div>
    </div>
    <div class="col-6 right">
      <div class="mb-1"><span class="label">Paciente:</span> {{ $receta->paciente }}</div>
    </div>
  </div>

  {{-- Producto --}}
  <div class="mt-3">
    <div class="label">Producto:</div>
    <div>{{ $formula->nombre_etiqueta ?? $receta->codigo_formula }}</div>
  </div>

  {{-- Posología --}}
  @php
    $tomas = (int) ($formula->tomas_diarias ?? 2);
  @endphp
  <div class="mt-3">
    <div class="label">Posología:</div>
    <div class="label">TOMAR {{ $tomas }} CÁPSULAS DIARIAS</div>
  </div>

  {{-- Composición --}}
  <div class="mt-3">
    <div class="label">Composición:</div>
    <table class="comp">
      @php
        // Si llegan todos los items, filtra auxiliares aquí por seguridad
        $excluir = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];
        $itemsPdf = collect($items ?? [])->filter(fn($it) => !in_array((int)($it->cod_odoo ?? 0), $excluir));
      @endphp
      @foreach($itemsPdf as $it)
        <tr>
          <td>{{ $it->activo }}</td>
          <td>{{ number_format((float)$it->cantidad, 2) }} {{ $it->unidad ?? 'mg' }}</td>
        </tr>
      @endforeach
    </table>
  </div>

  {{-- Firma --}}
  <div class="firma-box">
    @if(!empty($firmaBase64))
        <img class="firma-img" src="data:image/png;base64,{{ $firmaBase64 }}" alt="Firma">
    @endif
    <div class="mt-2">---------------------</div>
    <div>Firma</div>
  </div>

  {{-- Contacto médico (opcional) --}}
  <div class="footer mt-3">
    @php
      $correo = $medico->email ?? $medico->correo ?? null;
      $tel    = $medico->telefono ?? $medico->celular ?? null;
      $dir    = $medico->direccion ?? null;
    @endphp
    @if($correo) Correo: {{ $correo }}<br>@endif
    @if($tel) Teléfono: {{ $tel }}<br>@endif
    @if($dir) Dirección: {{ $dir }} @endif
  </div>

</body>
</html>
