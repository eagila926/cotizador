@php
    $prefill = session('etiqueta_preview', []);
    $soPrefill = $prefill['so'] ?? null;
    $medicoPrefill = $prefill['medico'] ?? null;
    $pacientePrefill = $prefill['paciente'] ?? null;

    function nombreCorto($full) {
        $full = trim((string)$full);
        if ($full === '') return null;
        $p = preg_split('/\s+/', $full);
        $first = $p[0] ?? '';
        $last  = $p[count($p)-1] ?? '';
        return trim($first.' '.$last);
    }
    $medicoCorto = nombreCorto($medicoPrefill);

    function abreviarNombreActivoView($nombre) {
        $nombre = preg_replace('/\s*\(.*?\)\s*/', '', (string)$nombre);
        if (stripos($nombre, 'BIFIDUMBACTERIUM') === 0) {
            return 'BIFIDUM' . substr($nombre, strlen('BIFIDUMBACTERIUM'));
        } elseif (stripos($nombre, 'LACTOBACILUS') === 0) {
            return 'LACTO' . substr($nombre, strlen('LACTOBACILUS'));
        }
        return $nombre;
    }

    // ✅ cantidad: sin .00 y recorta ceros
    function fmtCantidad($v, $maxDec = 2) {
        if (!is_numeric($v)) return (string)$v;
        $n = (float)$v;
        $s = number_format($n, $maxDec, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return $s === '' ? '0' : $s;
    }

    // Columnas equilibradas
    $items = $items ?? collect();
    $totalActivos = $items->count();

    $espaciadoExtra = '';
    if ($totalActivos >= 1 && $totalActivos <= 4) {
        $espaciadoExtra = 'margin-top: 60px; margin-bottom: 40px;';
    } elseif ($totalActivos >= 5 && $totalActivos <= 10) {
        $espaciadoExtra = 'margin-top: 30px; margin-bottom: 20px;';
    }

    $columnas = 2;
    if ($totalActivos >= 16 && $totalActivos <= 30) $columnas = 3;

    $porCol = max(1, (int) ceil($totalActivos / $columnas));
    $chunks = $items->chunk($porCol);

    // Pie
    $tomas = (int) ($formula->tomas_diarias ?? 3);
    $dias  = 30;
    $contieneCaps = $tomas * $dias;

    // Título
    $nombreEtiqueta = (string) ($formula->nombre_etiqueta ?? '');
    $fontSizeTitulo = (mb_strlen($nombreEtiqueta) > 31) ? '25px' : '28px';

    $qf = $qf ?? 'Q.F. Jose Perez';
    $fechaElaboracion = $fechaElaboracion ?? now()->format('d-m-Y');
@endphp

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiqueta – {{ $formula->codigo }}</title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; margin: 22px; }
  .container { width: 90%; margin: 0 auto; }

  .row { display: flex; justify-content: space-between; margin-bottom: 2px; gap: 12px; }
  .col { flex: 1; padding: 1px; font-size: 21px; }

  .header { text-align: left; margin-top: 10px; margin-left: 390px; font-weight: bold; margin-bottom: 45px; }
  .footer { margin-top: 10px; font-size: 25px; }

  .editable { border-bottom: 1px dashed #bbb; padding: 2px 4px; display: inline-block; }
  .editable:focus { outline: 2px solid #ddeaff; border-bottom-color: transparent; }

  .nombre-etiqueta { font-size: {{ $fontSizeTitulo }}; font-weight: 700; background: transparent; width: 100%; text-align: left; }
  .pte { font-weight: bold; font-size: 20px; background: transparent; width: 100%; text-align: left; }
  .so  { font-weight: bold; font-size: 24px; background: transparent; width: 100%; text-align: left; }

  .comp-row { display:flex; justify-content:space-between; margin: 2px 0; gap: 10px; }
  .comp-nombre { font-weight: bold; font-size: 20px; max-width: 70%; }
  .comp-cant { text-align: right; font-weight: bold; font-size: 20px; display:flex; justify-content:flex-end; gap: 8px; white-space: nowrap; }

  @media print {
    .editable { border: none !important; }
  }
</style>
</head>
<body>

<div class="container">
  {{-- Título --}}
  <div class="header">
    <div class="editable nombre-etiqueta" contenteditable="true">{{ $nombreEtiqueta }}</div>
  </div>

  {{-- Paciente y Código --}}
  <div class="row">
    <div class="editable pte" contenteditable="true">
      PTE: {{ $pacientePrefill ?? ($formula->paciente ?? '-') }}
    </div>
    <div class="editable pte" contenteditable="true" style="text-align:center;">
      {{ $formula->codigo }}
    </div>
  </div>

  {{-- Composición (EDITABLE TODO) --}}
  <div class="row" style="{{ $espaciadoExtra }}">
    @foreach($chunks as $col)
      <div class="col" style="width: {{ floor(100 / $columnas) }}%; padding-right: 15px;">
        @foreach($col as $it)
          <div class="comp-row">
            <div class="comp-nombre editable" contenteditable="true">
              {{ abreviarNombreActivoView($it->activo) }}
            </div>

            <div class="comp-cant">
              <span class="editable js-num-dec" contenteditable="true">{{ fmtCantidad($it->cantidad, 2) }}</span>
              <span class="editable" contenteditable="true">{{ $it->unidad ?? 'mg' }}</span>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach
  </div>

  {{-- Pie --}}
  <div class="footer" style="{{ $espaciadoExtra }}">
    <div class="row" style="align-items:flex-start;">
      <div class="col">
        <div>
          <strong>DR.(A):
            <span class="editable" contenteditable="true">
              {{ $medicoCorto ?? ($formula->medico ?? '-') }}
            </span>
          </strong>
        </div>

        <div>
          <strong>CONTIENE: </strong>
          <strong><span class="editable js-only-numbers" contenteditable="true">{{ $contieneCaps }}</span> CÁPSULAS</strong>
        </div>

        <div>
          <strong>POSOLOGÍA: TOMAR </strong>
          <strong><span class="editable js-only-numbers" contenteditable="true">{{ $tomas }}</span> CÁPSULAS DIARIAS</strong>
        </div>
      </div>

      <div class="col" style="text-align:left;">
        <div><strong class="editable" contenteditable="true">{{ $qf }}</strong></div>
        <div><strong>ELAB: <span class="editable" contenteditable="true">{{ $fechaElaboracion }}</span></strong></div>
        <div class="editable so" contenteditable="true">
          SO.@if($soPrefill) {{ ' ' . $soPrefill }} @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Evitar saltos de línea en contenteditable
  document.querySelectorAll('.editable').forEach(el => {
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
    });
  });

  // Solo enteros
  document.querySelectorAll('.js-only-numbers').forEach(el => {
    el.addEventListener('input', () => {
      el.textContent = (el.textContent || '').replace(/[^\d]/g, '');
    });
    el.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      const clean = (text || '').replace(/[^\d]/g, '');
      document.execCommand('insertText', false, clean);
    });
  });

  // Números con decimal opcional (1 punto)
  document.querySelectorAll('.js-num-dec').forEach(el => {
    const clean = (txt) => {
      txt = (txt || '').replace(/,/g, '.');
      txt = txt.replace(/[^\d.]/g, '');
      const parts = txt.split('.');
      if (parts.length > 2) txt = parts[0] + '.' + parts.slice(1).join('');
      return txt;
    };

    el.addEventListener('input', () => {
      el.textContent = clean(el.textContent);
    });

    el.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      document.execCommand('insertText', false, clean(text));
    });
  });
</script>

</body>
</html>
