<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vista previa — {{ $f->codigo }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 12px; color:#111; }
    h1 { margin: 0 0 6px 0; font-size: 22px; }
    h2 { margin: 0 0 4px 0; font-size: 16px; }
    .sub { margin: 0 0 10px 0; color:#555; }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 6px; }
    th { background: #f2f2f2; text-align: left; }
    .right { text-align: right; }

    .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:10px; }
    .btn { padding:6px 10px; border:1px solid #333; background:#fff; cursor:pointer; border-radius:4px; font-size:12px; }
    .btn-primary { background:#111; color:#fff; }
    .btn-outline { background:#fff; color:#111; }

    .block { break-inside: avoid; page-break-inside: avoid; }

    .op-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:8px; }
    .op-left { flex: 1; min-width: 320px; }
    .op-right { width: 340px; }
    .op-box { border:1px solid #333; border-radius:6px; padding:10px; }

    .op-grid { display:grid; grid-template-columns: 1fr; gap:10px; }
    .op-field label { display:block; font-size:11px; font-weight:bold; margin-bottom:4px; }
    .op-field input { width:100%; padding:6px 8px; border:1px solid #333; border-radius:4px; font-size:12px; }

    .op-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:8px; }

    /* ✅ imprime el bloque derecho como texto */
    .print-only { display:none; }

    @media print {
      @page { size: A4; margin: 8mm; }
      body { padding: 0; }

      .no-print { display:none !important; }
      .print-only { display:block !important; }
    }
  </style>
</head>

<body>
  <div class="toolbar no-print">
    <button id="btn-print-now" class="btn btn-primary" {{ empty($op?->id) ? 'disabled' : '' }}>Imprimir</button>
  </div>

  <div class="block op-header">
    <div class="op-left">
      <h1>Orden de Producción</h1>
      @php
        $fecha  = $op?->fecha_produccion ? $op->fecha_produccion->format('Y-m-d H:i') : now()->format('Y-m-d H:i');
        $numero = $op?->numero;
      @endphp

      <p class="sub" style="margin-top:6px;">
        Fecha: <strong>{{ $fecha }}</strong>
        @if($numero)
          &nbsp;|&nbsp; OP #: <strong>{{ $numero }}</strong>
        @endif
      </p>
    </div>

    <div class="op-right">
      {{-- ========= PANEL EN PANTALLA (inputs) ========= --}}
      <div class="op-box no-print">
        <div class="op-grid">
          <div class="op-field">
            <label>NÚMERO DE TRANSFERENCIA</label>
            <input type="text" id="inp-transferencia" value="{{ $op?->transferencia ?? '' }}" placeholder="BBL/INT/">
          </div>

          <div class="op-field">
            <label>LOTE INTERNO PRODUCCIÓN</label>
            <input type="text" id="inp-lote-interno" value="{{ $op?->lote_interno ?? '' }}" placeholder="BBL/MO/">
          </div>

          <div class="op-field">
            <label>LOTE (multiplicador)</label>
            <input type="number" id="inp-lote" min="1" step="1" value="{{ (int)($op?->lote ?? 1) }}">
          </div>

          <div class="op-actions">
            <button type="button" id="btn-aplicar-lote" class="btn btn-outline">Aplicar</button>
            <button type="button" id="btn-reset-lote" class="btn btn-outline">Reset</button>
            <button type="button" id="btn-guardar-meta" class="btn btn-outline">Guardar</button>
          </div>
        </div>
      </div>

      {{-- ========= PANEL PARA IMPRESIÓN (texto) ========= --}}
      <div class="op-box print-only">
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <th style="border:1px solid #333; padding:6px; width:55%;">NÚMERO DE TRANSFERENCIA</th>
            <td style="border:1px solid #333; padding:6px;">
              <span id="print-transferencia">{{ $op?->transferencia ?? '' }}</span>
            </td>
          </tr>
          <tr>
            <th style="border:1px solid #333; padding:6px;">LOTE INTERNO PRODUCCIÓN</th>
            <td style="border:1px solid #333; padding:6px;">
              <span id="print-lote-interno">{{ $op?->lote_interno ?? '' }}</span>
            </td>
          </tr>
          <tr>
            <th style="border:1px solid #333; padding:6px;">LOTE</th>
            <td style="border:1px solid #333; padding:6px;">
              <span id="print-lote">{{ (int)($op?->lote ?? 1) }}</span>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="block">
    <h2>Ítems de la fórmula</h2>
    <p class="sub">{{ $f->codigo }} — {{ $f->nombre_etiqueta }}</p>
  </div>

  {{-- Tabla ítems --}}
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

            {{-- ✅ solo esta columna cambia con lote --}}
            <td class="right masa-g" data-base="{{ number_format($masaG, 4, '.', '') }}">
              {{ number_format($masaG, 4, '.', '') }}
            </td>
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

  <script>
  (function(){
    const csrf = '{{ csrf_token() }}';
    const opId = @json($op?->id ?? null);

    // URLs
    const baseOP  = @json(url('/ordenes-produccion'));
    const urlLog  = opId ? `${baseOP}/${opId}/print-log` : null;
    const urlMeta = opId ? `${baseOP}/${opId}/meta` : null;

    const inpTransfer = document.getElementById('inp-transferencia');
    const inpLoteInt  = document.getElementById('inp-lote-interno');
    const inpLote     = document.getElementById('inp-lote');

    const btnAplicar  = document.getElementById('btn-aplicar-lote');
    const btnReset    = document.getElementById('btn-reset-lote');
    const btnGuardar  = document.getElementById('btn-guardar-meta');
    const btnPrint    = document.getElementById('btn-print-now');

    function toNum(v){
      const n = parseFloat((v ?? '').toString().replace(',', '.'));
      return Number.isFinite(n) ? n : 0;
    }
    function fmt4(n){
      return (Math.round(n * 10000) / 10000).toFixed(4);
    }
    function getLote(){
      const v = Math.max(1, parseInt(inpLote?.value || '1', 10) || 1);
      if (inpLote) inpLote.value = v;
      return v;
    }

    function aplicarLote(mult){
      // ✅ solo cambia Masa G
      document.querySelectorAll('td.masa-g').forEach(td => {
        const base = toNum(td.dataset.base);
        td.textContent = fmt4(base * mult);
      });
    }

    function syncPrintFields(){
      const t  = document.getElementById('print-transferencia');
      const li = document.getElementById('print-lote-interno');
      const l  = document.getElementById('print-lote');

      if (t && inpTransfer) t.textContent = inpTransfer.value || '';
      if (li && inpLoteInt) li.textContent = inpLoteInt.value || '';
      if (l && inpLote) l.textContent = (parseInt(inpLote.value || '1', 10) || 1);
    }

    async function guardarMeta(){
      if (!opId || !urlMeta) {
        alert('No hay OP asociada.');
        return false;
      }

      const payload = {
        transferencia: (inpTransfer?.value || '').trim() || null,
        lote_interno:  (inpLoteInt?.value || '').trim() || null,
        lote: getLote(),
      };

      const res = await fetch(urlMeta, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        alert(data.message || 'No se pudo guardar meta.');
        return false;
      }

      syncPrintFields();
      return true;
    }

    // eventos sync (para que al imprimir salga igual)
    inpTransfer?.addEventListener('input', syncPrintFields);
    inpLoteInt?.addEventListener('input', syncPrintFields);
    inpLote?.addEventListener('input', syncPrintFields);

    if (btnAplicar) btnAplicar.addEventListener('click', async () => {
      const lote = getLote();
      aplicarLote(lote);
      await guardarMeta().catch(()=>{});
    });

    if (btnReset) btnReset.addEventListener('click', async () => {
      if (inpLote) inpLote.value = 1;
      aplicarLote(1);
      await guardarMeta().catch(()=>{});
    });

    if (btnGuardar) btnGuardar.addEventListener('click', async () => {
      try {
        const ok = await guardarMeta();
        if (ok) alert('Guardado.');
      } catch(e) {
        console.error(e);
        alert('Error al guardar.');
      }
    });

    // aplica lote inicial (si op trae lote)
    const loteInicial = getLote();
    aplicarLote(loteInicial);
    syncPrintFields();

    if (btnPrint) btnPrint.addEventListener('click', async () => {
      if (!opId || !urlLog) {
        alert('Esta vista no tiene OP asociada.');
        return;
      }

      btnPrint.disabled = true;

      try {
        // guarda meta + sincroniza texto impreso antes de imprimir
        await guardarMeta();
        syncPrintFields();

        const res = await fetch(urlLog, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ copies: 1 })
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
          alert(data.message || 'No se pudo registrar la impresión.');
          return;
        }

        window.print();
      } catch (e) {
        console.error(e);
        alert('Error de red/servidor.');
      } finally {
        btnPrint.disabled = false;
      }
    });
  })();
  </script>
</body>
</html>
