<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiqueta – {{ $formula->codigo }}</title>
<style>
  body{ font-family: Arial, sans-serif; font-size:14px; }
  .wrap{ width: 100%; max-width: 1000px; margin: 0 auto; }
  .title{ text-align:center; font-weight:700; font-size:28px; letter-spacing:1px; }
  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:20px; }
  .col h4{ margin:0 0 8px; }
  .row{ display:flex; justify-content:space-between; margin:4px 0; }
  .muted{ color:#444; }
</style>
</head>
<body>
<div class="wrap">
  <div class="title">{{ mb_strtoupper($formula->nombre_etiqueta) }}</div>

  <div class="grid">
    <div class="col">
      <h4 class="muted">PTE: -</h4>
      @foreach($items as $it)
        <div class="row">
          <div>{{ mb_strtoupper($it->activo) }}</div>
          <div>{{ number_format((float)$it->cantidad, 2) }} {{ $it->unidad ?? 'mg' }}</div>
        </div>
      @endforeach
    </div>

    <div class="col">
      <div class="row"><div class="muted">CÓDIGO</div><div>{{ $formula->codigo }}</div></div>
      <div class="row"><div class="muted">DR.(A):</div><div>{{ $formula->medico ?? '-' }}</div></div>
      <div class="row"><div class="muted">CONTENIDO</div><div>90 CÁPSULAS</div></div>
      <div class="row"><div class="muted">POSOLOGÍA</div><div>TOMAR 3 CÁPSULAS DIARIAS</div></div>
      <div class="row"><div class="muted">ELAB:</div><div>{{ optional($formula->created_at)->format('d-m-Y') }}</div></div>
      <div class="row"><div class="muted">Q.F.:</div><div>EVENLYN GARCÍA</div></div>
      <div class="row"><div class="muted">SO.</div><div>&nbsp;</div></div>
    </div>
  </div>
</div>
</body>
</html>
