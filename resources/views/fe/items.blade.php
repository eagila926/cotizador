@extends('layouts.app')
@section('title','Ítems de la fórmula')

@section('content')

@php
  $r = $resumen ?? [];
@endphp

<div class="card mb-3">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Ítems de la fórmula</h4>
        <small class="text-muted">{{ $f->codigo }} — {{ $f->nombre_etiqueta }}</small>
      </div>

      <div class="d-flex gap-2">
        <a href="{{ route('fe.index') }}" class="btn btn-secondary btn-sm">Regresar</a>
        <a href="{{ route('fe.items.print', $f->id) }}" target="_blank" class="btn btn-dark btn-sm">
          Imprimir
        </a>
        <a href="{{ route('fe.items.export', $f->id) }}" class="btn btn-success btn-sm">Exportar</a>
      </div>
    </div>

    {{-- ====== Tabla ítems ====== --}}
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Código</th>
            <th>Activo</th>
            <th class="text-end">Cantidad</th>
            <th>Unidad</th>
            <th class="text-end">Masa G</th>
            <th style="width:140px">Acción</th>
          </tr>
        </thead>

        <tbody>
          @forelse($items as $it)
            @php
              $esCelulosa = ((int)$it->cod_odoo === 3291);
              $masaG = $it->masa_mes !== null ? (float)$it->masa_mes : 0.0;
            @endphp

            <tr data-cod="{{ (int)$it->cod_odoo }}">
              <td>{{ $it->cod_odoo }}</td>
              <td>{{ $it->activo }}</td>

              <td class="text-end">
                @if($esCelulosa)
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control form-control-sm text-end celulosa-mg"
                    value="{{ number_format((float)$it->cantidad, 2, '.', '') }}"
                  >
                @else
                  {{ number_format((float)$it->cantidad, 2, '.', '') }}
                @endif
              </td>

              <td>{{ $it->unidad }}</td>

              <td class="text-end masa-g">
                {{ number_format($masaG, 4, '.', '') }}
              </td>

              <td class="text-center">
                @if($esCelulosa)
                  <button type="button" class="btn btn-primary btn-sm btn-save-celulosa">
                    Guardar
                  </button>
                @else
                  —
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">No existen ítems para esta fórmula.</td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

        {{-- ====== Resumen tipo imagen (1-3) ====== --}}
    <div class="table-responsive mb-3">
      <table class="table table-sm table-bordered align-middle">
        <tbody>
          <tr class="table-success">
            <th style="width:260px">TOTAL PRINCIPIOS ACTIVOS</th>
            <td class="text-end" id="sum-principios">
              {{ number_format((float)($r['total_principios_mg_dia'] ?? 0), 2, '.', '') }}
            </td>
            <td style="width:80px">mg</td>
          </tr>

          <tr>
            <th>Dosis diaria para 1 cápsula</th>
            <td class="text-end" id="dose-caps">
              {{ number_format((float)($r['dosis_caps_mg'] ?? 0), 2, '.', '') }}
            </td>
            <td>mg</td>
          </tr>

          <tr>
            <th>Celulosa microcristalina (Avicel PH10)</th>
            <td class="text-end" id="cel-caps">
              {{ number_format((float)($r['celulosa_caps_mg'] ?? 0), 2, '.', '') }}
            </td>
            <td>mg</td>
          </tr>

          <tr>
            <th>Contenido total para cápsula 0</th>
            <td class="text-end" id="total-caps">
              {{ number_format((float)($r['contenido_caps_mg'] ?? 0), 2, '.', '') }}
            </td>
            <td>mg</td>
          </tr>

          <tr class="table-success">
            <th>PRESENTACIÓN:</th>
            <td class="text-end">
              {{ number_format((float)($r['presentacion_caps'] ?? 0), 0, '.', '') }}
            </td>
            <td>cápsulas</td>
          </tr>

          <tr class="table-success">
            <th>DOSIFICACIÓN:</th>
            <td class="text-end" id="tomas-dia">
              {{ number_format((float)($r['dosificacion_caps_dia'] ?? 0), 0, '.', '') }}
            </td>
            <td>cápsulas diarias</td>
          </tr>
        </tbody>
      </table>
    </div>


  </div>
</div>

<script>
(function(){
  const csrf   = '{{ csrf_token() }}';
  const urlSave = @json(route('fe.updateCelulosa', $f->id));
  const tomasDia = parseFloat(document.getElementById('tomas-dia')?.textContent || '1') || 1;

  function toNum(v){
    const n = parseFloat((v ?? '').toString().replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  }
  function fmt2(n){ return (Math.round(n * 100) / 100).toFixed(2); }

  document.querySelectorAll('.btn-save-celulosa').forEach(btn => {
    btn.addEventListener('click', async function(){
      const tr = this.closest('tr');
      const input = tr.querySelector('.celulosa-mg');
      const mgDia = toNum(input.value);

      if (!Number.isFinite(mgDia) || mgDia < 0) {
        alert('Valor inválido. Ingresa mg/día >= 0.');
        return;
      }

      this.disabled = true;

      try{
        const res = await fetch(urlSave, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ mg_dia: mgDia })
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.ok) {
          alert(data.message || 'No se pudo guardar celulosa.');
          return;
        }

        // refrescar masa g en la fila
        tr.querySelector('.masa-g').textContent = (data.masa_g ?? 0).toFixed(4);

        // refrescar bloque resumen (celulosa por cápsula y contenido total)
        const doseCaps = toNum(document.getElementById('dose-caps')?.textContent);
        const celCaps  = (mgDia / tomasDia);
        document.getElementById('cel-caps').textContent = fmt2(celCaps);

        const totalCaps = doseCaps + celCaps;
        document.getElementById('total-caps').textContent = fmt2(totalCaps);

        alert('Celulosa actualizada.');
      } catch (e) {
        console.error(e);
        alert('Error de red/servidor.');
      } finally {
        this.disabled = false;
      }
    });
  });
})();
</script>

@endsection
