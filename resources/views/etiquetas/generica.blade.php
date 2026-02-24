@php
    $prefill = session('etiqueta_preview', []);
    $soPrefill = $prefill['so'] ?? null;
    $medicoPrefill = $prefill['medico'] ?? null;

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

    function fmtCantidad($v, $maxDec = 2) {
        if (!is_numeric($v)) return (string)$v;
        $n = (float)$v;
        $s = number_format($n, $maxDec, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return $s === '' ? '0' : $s;
    }

    // === Distribución asimétrica: balancea por "peso" del texto (nombres largos pesan más) ===
    function distribuirActivosAsimetrico($items, int $cols = 2) {
        $cols = max(1, $cols);
        $buckets = array_fill(0, $cols, ['w' => 0, 'items' => []]);

        foreach ($items as $it) {
            $nombre = abreviarNombreActivoView($it->activo ?? '');
            $len = mb_strlen($nombre);
            $spaces = substr_count($nombre, ' ');
            $weight = $len + ($spaces * 4);

            $minIdx = 0;
            for ($i=1; $i<$cols; $i++) {
                if ($buckets[$i]['w'] < $buckets[$minIdx]['w']) $minIdx = $i;
            }
            $buckets[$minIdx]['items'][] = $it;
            $buckets[$minIdx]['w'] += $weight;
        }

        return array_map(fn($b) => collect($b['items']), $buckets);
    }

    $items = $items ?? collect();
    $totalActivos = $items->count();

    // columnas: 2 por defecto, 3 si vienen muchos
    $columnas = 2;
    if ($totalActivos >= 18) $columnas = 3;

    // tipografía dinámica para compactar
    $fsActivo = 20; // px
    $fsCant  = 20;
    $lh      = 1.05;

    if ($totalActivos >= 10 && $totalActivos <= 14) { $fsActivo = 18; $fsCant = 18; }
    if ($totalActivos >= 15 && $totalActivos <= 20) { $fsActivo = 16; $fsCant = 16; }
    if ($totalActivos >= 21) { $fsActivo = 14; $fsCant = 14; }

    // columnas asimétricas
    $cols = distribuirActivosAsimetrico($items, $columnas);

    // Pie
    $tomas = (int) ($formula->tomas_diarias ?? 3);
    $dias  = 30;
    $contieneCaps = $tomas * $dias;

    // Título
    $nombreEtiqueta = (string) ($formula->nombre_etiqueta ?? '');
    $fontSizeTitulo = (mb_strlen($nombreEtiqueta) > 31) ? '25px' : '28px';

    $fechaElaboracion = $fechaElaboracion ?? now()->format('d-m-Y');
@endphp

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiqueta – {{ $formula->codigo }}</title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
  .wrap { padding: 10px 12px; } /* margen interno general, SIN fijar tamaño total */

  /* “Área blanca” lógica: simula tu layout sin forzar 50x142 */
  .label-grid {
    display: grid;
    grid-template-columns: 22% 1fr 42px;  /* izq reservado / centro / banda vertical */
    gap: 10px;
    align-items: start;
  }

  /* Bloque izquierdo: NO mostramos nada (solo reserva espacio). */
  .left-spacer { min-height: 1px; }

  .center { min-width: 0; }

  /* ✅ TÍTULO centrado */
  .title{
    width: 100%;                 /* asegura que ocupe todo el ancho */
    display: block;              /* comportamiento de bloque completo */
    text-align: center;          /* centra el texto */
    font-size: {{ $fontSizeTitulo }};
    font-weight: 800;
    margin: 6px 0 10px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Composición */
  .comp {
    display: grid;
    grid-template-columns: repeat({{ $columnas }}, 1fr);
    gap: 14px;
  }

  .comp-col { min-width: 0; }

  .comp-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    align-items: baseline;
    margin: 2px 0;
  }

  /* ✅ Composición SIN negrita */
  .comp-nombre {
    font-weight: 400;              /* ✅ antes 800 */
    font-size: {{ $fsActivo }}px;
    line-height: {{ $lh }};
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* ✅ Composición SIN negrita */
  .comp-cant {
    font-weight: 400;              /* ✅ antes 800 */
    font-size: {{ $fsCant }}px;
    white-space: nowrap;
    text-align: right;
    display: inline-flex;
    gap: 8px;
  }

  /* Banda vertical derecha */
  .vertical {
    font-weight: 800;
    font-size: 16px;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 10px;
  }

  /* ✅ De abajo hacia arriba: rotamos SOLO el texto */
  .vertical .vertical-text{
    display: inline-block;
    transform: rotate(180deg);
    transform-origin: center;
  }

  /* Pie */
  .footer {
    margin-top: 14px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    align-items: start;
    font-size: 18px;
    font-weight: 200;
  }

  .so { font-size: 20px; margin-top: 6px; }

  /* Editables */
  .editable { border-bottom: 1px dashed #bbb; padding: 2px 4px; display: inline-block; }
  .editable:focus { outline: 2px solid #ddeaff; border-bottom-color: transparent; }

  @media print { .editable { border: none !important; padding: 0 !important; } }
</style>
</head>
<body>

<div class="wrap">
  <div class="label-grid">

    {{-- Reserva espacio izquierdo (precauciones del diseño físico) --}}
    <div class="left-spacer"></div>

    {{-- Centro --}}
    <div class="center">
      <div class="title editable" contenteditable="true">{{ $nombreEtiqueta }}</div>

      <div class="comp">
        @foreach($cols as $col)
          <div class="comp-col">
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

      <div class="footer">
        <div>
          DR.(A): <span class="editable" contenteditable="true">{{ $medicoCorto ?? ($formula->medico ?? '-') }}</span><br>
          Pte: <span class="editable" contenteditable="true"></span><br>
          POSOLOGÍA: TOMAR <span class="editable js-only-numbers" contenteditable="true">{{ $tomas }}</span> CÁPSULAS DIARIAS
        </div>

        <div>
          ELAB: <span class="editable" contenteditable="true">{{ $fechaElaboracion }}</span><br>
          Despues de abierto, consumir en un periodo maximo de 60 días
        </div>
      </div>
    </div>

    {{-- Banda vertical derecha --}}
    <div class="vertical editable" contenteditable="true">
      <span class="vertical-text">CONTENIDO: {{ $contieneCaps }} CAPSULAS</span>
    </div>

  </div>
</div>

<script>
  // Evitar saltos de línea
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

  // Decimal opcional
  document.querySelectorAll('.js-num-dec').forEach(el => {
    const clean = (txt) => {
      txt = (txt || '').replace(/,/g, '.');
      txt = txt.replace(/[^\d.]/g, '');
      const parts = txt.split('.');
      if (parts.length > 2) txt = parts[0] + '.' + parts.slice(1).join('');
      return txt;
    };

    el.addEventListener('input', () => { el.textContent = clean(el.textContent); });
    el.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      document.execCommand('insertText', false, clean(text));
    });
  });
</script>

</body>
</html>