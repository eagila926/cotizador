@extends('layouts.app')

@push('styles')
<style>
  @media (max-width: 767.98px) {
    #tablaFormula th:nth-child(1),
    #tablaFormula td:nth-child(1),
    #tablaFormula th:nth-child(2),
    #tablaFormula td:nth-child(2) {
      display: none;
    }
  }
</style>
@endpush

@section('title', 'Inicio')

@section('content')
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-2">Ingrese los activos de la fórmula</h5>

      <div class="row">
        <div class="col-lg-12">
          <form>
            <div class="mb-12">
              <label for="activo" class="form-label">Activo:</label>
              <input type="text" class="form-control" id="activo" name="activo"
                     onkeyup="buscarActivo(this.value)"
                     placeholder="Ingrese el nombre del activo" autocomplete="off">
            </div>
            <div id="resultados-activos" style="float:left;"></div>
          </form>
        </div>

        <div class="col-lg-12">
          <label for="minMaxLabel" id="minMaxLabel" class="form-label">Mínimo: ; Máximo: </label>
        </div>

        <div class="col-lg-6">
          <div class="mb-6">
            <label for="cant" class="form-label">Cantidad:</label>
            <input type="number" class="form-control" id="cant" name="cant" required>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="mb-3">
            <label for="unidad" class="form-label">Unidad:</label>
            <select class="form-select" id="unidad" name="unidad"></select>
          </div>
        </div>

        <div class="col-lg-12">
          <button type="button" id="btnAdd" class="btn btn-primary" onclick="agregarFila();">Añadir</button>
        </div>

        <div class="row align-items-center mt-3">
          <div class="col-lg-10">
            <h5 id="total" class="mb-0">Total: 0.00 mg</h5>
          </div>
          <div class="col-lg-2">
            <button type="button" id="btnCapsulas" class="btn btn-success w-100">Cotizar</button>
          </div>
        </div>
      </div>

      <div class="row" style="margin-top: 10px;">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h4 class="header-title">Activos Seleccionados</h4>
                <button class="btn btn-danger" onclick="eliminarTodosLosActivos();">Eliminar Todos</button>
              </div>
              <table id="tablaFormula" class="table activate-select dt-responsive nowrap w-100">
                <thead>
                  <tr>
                    <th class="d-none d-md-table-cell">#</th>
                    <th class="d-none d-md-table-cell">Cod. Odoo</th>
                    <th>Activo</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Eliminar</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
  });

  // ===== Probióticos (misma lista que backend) =====
  const PROBIO_CODES = new Set([
    3371,
    3278, 3277, 3321, 3320, 3325, 3322, 3323, 3324, 3370,
    3279, 3280, 3282, 3319, 3326, 3327, 3328, 3329, 3372, 3281, 3318
  ]);

  const PROBIO_UFC_PER_G = {
    3371: 20000000000,
    3278: 100000000000,
    3277: 100000000000,
    3321: 100000000000,
    3320: 100000000000,
    3325: 100000000000,
    3322: 100000000000,
    3323: 100000000000,
    3324: 100000000000,
    3370: 100000000000,
    3279: 200000000000,
    3280: 200000000000,
    3282: 200000000000,
    3319: 200000000000,
    3326: 200000000000,
    3327: 200000000000,
    3328: 200000000000,
    3329: 200000000000,
    3372: 200000000000,
    3281: 200000000000,
    3318: 200000000000,
  };

  function ufcToMg(ufc, codOdoo) {
    const pot = PROBIO_UFC_PER_G[codOdoo];
    if (!pot || pot <= 0) return 0;
    return (ufc / pot) * 1000;
  }

  // UI -> mg (alineado al controlador)
  const UI_TO_MG = {
    3388: 0.000025,
    3381: 0.00055,
    3375: 1.0,
  };
  const UI_FALLBACK_TO_MG = 0.00067;

  function uiToMg(ui, codOdoo) {
    const f = UI_TO_MG[codOdoo] ?? UI_FALLBACK_TO_MG;
    return ui * f;
  }

  // ===== Unidades disponibles para UI =====
  function buildUnitOptions(isProbio, selected) {
    const base = [
      {v:'mg',  t:'mg'},
      {v:'g',   t:'g'},
      {v:'mcg', t:'mcg'},
      {v:'UI',  t:'UI'},
    ];

    if (isProbio) base.push({v:'UFC', t:'UFC'});

    selected = (selected || 'mg').toString().trim();
    // normalizar selected para que empate con opciones
    const up = selected.toUpperCase();
    if (up === 'UFC') selected = 'UFC';
    else if (up === 'UI') selected = 'UI';
    else selected = selected.toLowerCase();

    // si selected no existe en la lista, default mg
    const exists = base.some(o => o.v === selected);
    if (!exists) selected = isProbio ? 'mg' : 'mg';

    return base.map(o => `<option value="${o.v}" ${o.v===selected?'selected':''}>${o.t}</option>`).join('');
  }

  function stepByUnit(u) {
    u = (u || '').toString().trim();
    if (u === 'UFC') return 1;
    if (u === 'g')   return 0.0001;
    if (u === 'mcg') return 1;
    if (u === 'UI')  return 1;
    return 0.01; // mg
  }

  // Buscar (sugerencias)
  function buscarActivo(valor) {
    if (!valor || valor.length < 1) {
      $('#resultados-activos').hide().empty();
      return;
    }

    $.post("{{ route('formulas.buscar') }}", { producto: valor }, function (data) {
      if (!data) { $('#resultados-activos').hide().empty(); return; }

      $('#resultados-activos').show().html(data);

      $('.suggest-element').off('click').on('click', function () {
        const cod    = $(this).data('cod_odoo');
        const nombre = $(this).text();

        const minRaw = $(this).data('minimo');
        const maxRaw = $(this).data('maximo');
        const minNum = (minRaw === '' || minRaw === null || isNaN(Number(minRaw))) ? null : Number(minRaw);
        const maxNum = (maxRaw === '' || maxRaw === null || isNaN(Number(maxRaw))) ? null : Number(maxRaw);

        const unidadDB = (($(this).data('unidad') || 'mg') + '').trim();
        const unidadBase = unidadDB.toLowerCase() === 'mcg' ? 'mcg' : (unidadDB.toLowerCase() === 'g' ? 'g' : 'mg');

        const codNum = parseInt(cod, 10);
        const isProbio = PROBIO_CODES.has(codNum);

        $('#activo').val(nombre).data('cod_odoo', cod);

        const fmtUM = (v, u) => (v === null ? '—' : `${v} ${u}`);
        $('#minMaxLabel').text(`Mínimo: ${fmtUM(minNum, unidadBase)} ; Máximo: ${fmtUM(maxNum, unidadBase)}`);

        // Unidades: siempre mostrar todas (y UFC solo probióticos)
        const selected = isProbio ? 'mg' : unidadBase;
        $('#unidad')
          .html(buildUnitOptions(isProbio, selected))
          .prop('disabled', false);

        const uSel = $('#unidad').val();
        $('#cant')
          .attr('min', minNum === null ? '' : minNum)
          .attr('max', maxNum === null ? '' : maxNum)
          .attr('step', stepByUnit(uSel));

        $('#resultados-activos').hide();
      });
    });
  }

  // Ajustar step cuando cambie la unidad
  $('#unidad').on('change', function() {
    const u = ($('#unidad').val() || '').trim();
    $('#cant').attr('step', stepByUnit(u));
  });

  // Listar tabla
  function mostrarActivos() {
    $.get("{{ route('formulas.listar') }}", function (html) {
      $('#tablaFormula tbody').html(html);
    });
  }

  // Agregar a temp
  function agregarFila() {
    const activo  = $('#activo').val();
    const codOdoo = $('#activo').data('cod_odoo');
    const cant    = $('#cant').val();
    const unidad  = $('#unidad').val();

    if (!activo || !codOdoo || !cant || !unidad) {
      alert('Por favor, complete todos los campos.');
      return;
    }

    $.post("{{ route('formulas.agregar') }}", {
      activo: activo,
      cod_odoo: codOdoo,
      cantidad: cant,
      unidad: unidad
    }, function (res) {
      let data = res;
      if (typeof res === 'string') {
        try { data = JSON.parse(res); } catch(e) {}
      }

      if (typeof data === 'object' && data !== null) {
        if (data.status === 'ok') {
          $('#activo').val('').data('cod_odoo','');
          $('#cant').val('').removeAttr('min').removeAttr('max').removeAttr('step');
          $('#unidad').html('').prop('disabled', false);
          $('#minMaxLabel').text('Mínimo: ; Máximo: ');
          mostrarActivos();
          return;
        }
        if (data.status === 'duplicado')       return alert('Este activo ya ha sido ingresado.');
        if (data.status === 'UNIDAD_INVALIDA') return alert('Unidad no permitida para este activo.');
        return alert('Error al guardar: ' + JSON.stringify(data));
      }

      const r = (res + '').trim();
      if (r === 'ok') {
        $('#activo').val('').data('cod_odoo','');
        $('#cant').val('').removeAttr('min').removeAttr('max').removeAttr('step');
        $('#unidad').html('').prop('disabled', false);
        $('#minMaxLabel').text('Mínimo: ; Máximo: ');
        mostrarActivos();
      } else if (r === 'duplicado') {
        alert('Este activo ya ha sido ingresado.');
      } else if (r === 'UNIDAD_INVALIDA') {
        alert('Unidad no permitida para este activo.');
      } else {
        alert('Error al guardar: ' + r);
      }
    });
  }

  // Eliminar una fila
  function eliminarFila(id) {
    if (!confirm('¿Estás seguro de eliminar este activo?')) return;
    $.post("{{ route('formulas.eliminar') }}", { id: id }, function (res) {
      if ((res + '').trim() === 'ok') mostrarActivos();
      else alert('Error al eliminar: ' + res);
    });
  }
  window.eliminarFila = eliminarFila;

  // Eliminar todos
  function eliminarTodosLosActivos() {
    if (!confirm('¿Estás seguro de eliminar todos los activos?')) return;
    $.post("{{ route('formulas.eliminarTodos') }}", {}, function (res) {
      if ((res + '').trim() === 'OK') mostrarActivos();
      else alert('Error al eliminar: ' + res);
    });
  }
  window.eliminarTodosLosActivos = eliminarTodosLosActivos;

  // Cargar al entrar
  document.addEventListener('DOMContentLoaded', mostrarActivos);

  // === TOTAL EN MG (incluye UI/UFC/mcg/g) ===
  function verificarCondiciones() {
    $.get("{{ route('formulas.listar') }}?json=1", function (activos) {
      const codigosProhibidos = [4520, 1205, 1044, 1086, 70136, 1163];

      let totalMg = 0;
      let contieneProhibido = false;

      (Array.isArray(activos) ? activos : []).forEach(item => {
        const cantidad = parseFloat(item.cantidad);
        const unidad   = (item.unidad || '').trim();
        const codOdoo  = parseInt(item.cod_odoo);

        let cantidadMg = 0;

        switch (unidad) {
          case 'mg':
            cantidadMg = cantidad; break;
          case 'mcg':
            cantidadMg = cantidad / 1000; break;
          case 'g':
            cantidadMg = cantidad * 1000; break;
          case 'UI':
            cantidadMg = uiToMg(cantidad, codOdoo); break;
          case 'UFC':
            cantidadMg = ufcToMg(cantidad, codOdoo); break;
          default:
            cantidadMg = 0;
        }

        totalMg += cantidadMg;
        if (codigosProhibidos.includes(codOdoo)) contieneProhibido = true;
      });

      const $total = $('#total');
      if ($total.length) $total.text(`Total: ${totalMg.toFixed(2)} mg`);

      const $btnSobres = $('#btnSobres');
      if ($btnSobres.length) $btnSobres.prop('disabled', contieneProhibido || totalMg < 2499);

      localStorage.setItem('totalMg', totalMg.toFixed(2));
    });
  }

  // Hook a mostrarActivos
  const __oldMostrarActivos = mostrarActivos;
  window.mostrarActivos = function() {
    __oldMostrarActivos();
    setTimeout(verificarCondiciones, 120);
  };

  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(verificarCondiciones, 200);
  });

  // === REDIRECCIÓN A RESUMEN DE CÁPSULAS ===
  $('#btnCapsulas').on('click', function() {
    const dias = $('#diasTratamiento').val() || 30;
    localStorage.setItem('diasTratamiento', dias);
    window.location.href = "{{ route('formulas.resumen_capsulas') }}";
  });
</script>
@endpush
