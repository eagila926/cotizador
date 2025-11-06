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

    // Helpers de vista
    function abreviarNombreActivoView($nombre) {
        $nombre = preg_replace('/\s*\(.*?\)\s*/', '', (string)$nombre);
        if (stripos($nombre, 'BIFIDUMBACTERIUM') === 0) {
            return 'BIFIDUM' . substr($nombre, strlen('BIFIDUMBACTERIUM'));
        } elseif (stripos($nombre, 'LACTOBACILUS') === 0) {
            return 'LACTO' . substr($nombre, strlen('LACTOBACILUS'));
        }
        return $nombre;
    }

    // Excluir auxiliares (códigos nuevos + compat)
    $excluir = [3740,3742,3744,3743,3739,1078,1077,1219,70276,70271,71497];
    $items  = $items ?? $formula->items->filter(fn($it) => !in_array((int)$it->cod_odoo, $excluir))->values();

    $totalActivos = $items->count();

    // Espaciado extra
    if ($totalActivos >= 1 && $totalActivos <= 4) {
        $espaciadoExtra = 'margin-top: 60px; margin-bottom: 40px;';
    } elseif ($totalActivos >= 5 && $totalActivos <= 10) {
        $espaciadoExtra = 'margin-top: 30px; margin-bottom: 20px;';
    } else {
        $espaciadoExtra = '';
    }

    // Columnas
    $columnas = 2;
    if ($totalActivos >= 16 && $totalActivos <= 30) $columnas = 3;

    // Partir ítems en columnas equilibradas
    $porCol   = max(1, (int) ceil($totalActivos / $columnas));
    $chunks   = $items->chunk($porCol);

    // Pie
    $tomas   = (int) ($formula->tomas_diarias ?? 3);
    $dias    = 30;
    $contieneCaps = $tomas * $dias;

    // Título dinámico
    $nombreEtiqueta = (string) ($formula->nombre_etiqueta ?? '');
    $fontSizeTitulo = (strlen($nombreEtiqueta) > 31) ? '25px' : '28px';

    $qf               = $qf ?? 'Q.F. Jose Perez';
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
    .col-half { width: 65%; }
    .header { text-align: left; margin-top: 10px; margin-left: 390px; font-weight: bold; margin-bottom: 45px; }
    .footer { margin-top: 10px; font-size: 25px; }

    .editable { border-bottom: 1px dashed #bbb; padding: 2px 4px; display: inline-block; }
    .editable:focus { outline: 2px solid #ddeaff; border-bottom-color: transparent; }
    .nombre-etiqueta { font-size: {{ $fontSizeTitulo }}; font-weight: 700; background: transparent; width: 100%; text-align: left; }
    .pte { font-weight: bold; font-size: 20px; background: transparent; width: 100%; text-align: left; }
    .so  { font-weight: bold; font-size: 24px; background: transparent; width: 100%; text-align: left; }

    .comp-row { display:flex; justify-content:space-between; margin: 2px 0; }
    .comp-nombre { font-weight: bold; font-size: 20px; }
    .comp-cant   { text-align: right; font-weight: bold; font-size: 20px; }

    @media print {
      .editable { border: none !important; }
    }
</style>
</head>
<body>

<div class="container">
    {{-- Título (EDITABLE) --}}
    <div class="header">
        <div class="editable nombre-etiqueta" contenteditable="true">
            {{ $nombreEtiqueta }}
        </div>
    </div>

    {{-- Paciente y Código (EDITABLES) --}}
    <div class="row">
        <div class="editable pte" contenteditable="true">
            PTE: {{ $pacientePrefill ?? ($formula->paciente ?? '-') }}
        </div>
        <div class="editable pte" contenteditable="true" style="text-align:center;">
            {{ $formula->codigo }}
        </div>
    </div>

    {{-- Composición (NO editable) --}}
    <div class="row" style="{{ $espaciadoExtra }}">
        @foreach($chunks as $col)
            <div class="col col-half" style="width: {{ floor(100 / $columnas) }}%; padding-right: 15px;">
                @foreach($col as $it)
                    <div class="comp-row">
                        <div class="comp-nombre">
                            {{ abreviarNombreActivoView($it->activo) }}
                        </div>
                        <div class="comp-cant">
                            {{ number_format((float)$it->cantidad, 2) }} {{ $it->unidad ?? 'mg' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Pie (todos EDITABLES) --}}
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
                    <strong><span class="editable js-only-numbers" contenteditable="true">{{ $contieneCaps }}</span>
                     CÁPSULAS</strong>
                </div>

                <div>
                    <strong>POSOLOGÍA: TOMAR </strong>
                    <strong><span class="editable js-only-numbers" contenteditable="true">{{ $tomas }}</span>
                     CÁPSULAS DIARIAS</strong>
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

  // Limitar a números los campos marcados
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
</script>
</body>
</html>
