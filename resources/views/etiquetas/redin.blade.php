<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiqueta Dr Redin – {{ $formula->codigo }}</title>
<style>
  body{ font-family: Arial, sans-serif; font-size:14px; }
  .wrap{ width: 100%; max-width: 800px; margin: 0 auto; }
  .header{ text-align:center; margin-bottom:12px; }
  .header h1{ margin:0; font-size:22px; }
  .pair{ display:flex; justify-content:space-between; }
  table{ width:100%; border-collapse:collapse; margin-top:12px; }
  td{ padding:4px 0; }
  .right{ text-align:right; }
  .muted{ color:#555; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>CONSULTORIO DR. REDIN</h1>
    <div class="muted">{{ $formula->codigo }} &middot; {{ $formula->nombre_etiqueta }}</div>
  </div>

  <table>
    @foreach($items as $it)
      <tr>
        <td>{{ mb_strtoupper($it->activo) }}</td>
        <td class="right">{{ number_format((float)$it->cantidad, 2) }} {{ $it->unidad ?? 'mg' }}</td>
      </tr>
    @endforeach
  </table>

  <div style="margin-top:10px">
    <div class="pair"><span class="muted">Paciente:</span><span>-</span></div>
    <div class="pair"><span class="muted">Médico:</span><span>{{ $formula->medico ?? '—' }}</span></div>
    <div class="pair"><span class="muted">Posología:</span><span>3 cápsulas diarias</span></div>
    <div class="pair"><span class="muted">Elab.:</span><span>{{ optional($formula->created_at)->format('d-m-Y') }}</span></div>
  </div>
</div>
</body>
</html>
