@extends('layouts.app')
@push('styles')
<style>
  /* < md (menos de 768px) */
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

@section('title', 'Inicio | Ortomed')

@section('content')
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-2">Ingrese los activos de la formula</h5>
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

        <!-- Aviso límite -->
        <div class="col-12 mt-2">
          <div id="alertLimite" class="alert alert-warning py-2 px-3 d-none" role="alert"></div>
        </div>

        {{-- === Total + Botones de cotización === --}}
        <div class="row align-items-center mt-3">
          <div class="col-lg-8">
            <h5 id="total" class="mb-0">Total: 0.00 mg</h5>
          </div>
          <div class="col-lg-2 text-end">
            <button type="button" id="btnSobres" class="btn btn-info w-100">Cotizar en Sobres</button>
          </div>
          <div class="col-lg-2">
            <button type="button" id="btnCapsulas" class="btn btn-success w-100">Cotizar en Cápsulas</button>
          </div>
        </div>
        {{-- === /Total + Botones === --}}
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
            </div><!-- end card body -->
          </div><!-- end card -->
        </div><!-- end col -->
      </div><!-- end row -->
    </div>
  </div>
@endsection
{{-- Asegúrate de tener en tu layout: <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
  });

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
        // lee como número (puede venir "null" o vacío)
        const minRaw = $(this).data('minimo');
        const maxRaw = $(this).data('maximo');
        const minNum = (minRaw === '' || minRaw === null || isNaN(Number(minRaw))) ? null : Number(minRaw);
        const maxNum = (maxRaw === '' || maxRaw === null || isNaN(Number(maxRaw))) ? null : Number(maxRaw);

        const unidad = (($(this).data('unidad') || 'mg') + '').trim();

        $('#activo').val(nombre).data('cod_odoo', cod);

        // helper para mostrar número + unidad o "—"
        const fmtUM = (v, u) => (v === null ? '—' : `${v} ${u}`);

        // etiqueta + límites numéricos del input
        $('#minMaxLabel').text(`Mínimo: ${fmtUM(minNum, unidad)} ; Máximo: ${fmtUM(maxNum, unidad)}`);
        $('#cant')
          .attr('min', minNum === null ? '' : minNum)
          .attr('max', maxNum === null ? '' : maxNum)
          .attr('step', unidad === 'mcg' ? 1 : (unidad === 'mg' ? 0.01 : 1));

        // fija la UNIDAD en el select y lo bloquea
        $('#unidad')
          .html(`<option value="${unidad}" selected>${unidad}</option>`)
          .prop('disabled', true);

        $('#resultados-activos').hide();
      });

    });
  }



  // Listar tabla
  function mostrarActivos() {
    $.get("{{ route('formulas.listar') }}", function (html) {
      $('#tablaFormula tbody').html(html);
    });
  }

  // Agregar a temp
  function agregarFila() {
    if ($('#btnAdd').prop('disabled')) {
      alert('Has alcanzado el máximo de 15 activos.');
      return;
    }
    
    const activo  = $('#activo').val();
    const codOdoo = $('#activo').data('cod_odoo');
    const cant    = $('#cant').val();
    const unidad  = $('#unidad').val();

    if (!activo || !codOdoo || !cant || !unidad) {
      alert('Por favor, complete todos los campos.');
      return;
    }

    // validación rápida cliente contra max/min
    // Lee min/max solo para armar el contexto del aviso (NO bloquea)
    const minAttr = $('#cant').attr('min');
    const maxAttr = $('#cant').attr('max');
    const min = (minAttr === undefined || minAttr === '') ? null : parseFloat(minAttr);
    const max = (maxAttr === undefined || maxAttr === '') ? null : parseFloat(maxAttr);
    const c   = parseFloat(cant);

    $.post("{{ route('formulas.agregar') }}", {
      activo: activo, cod_odoo: codOdoo, cantidad: cant, unidad: unidad
    }, function (res) {
      let data = res;
      if (typeof res === 'string') {
        try { data = JSON.parse(res); } catch(e) { /* compat con respuestas antiguas */ }
      }

      if (typeof data === 'object' && data !== null) {
        if (data.status === 'ok') {
          // limpia
          $('#activo').val('').data('cod_odoo','');
          $('#cant').val('').removeAttr('min').removeAttr('max').removeAttr('step');
          $('#unidad').html('').prop('disabled', false);
          $('#minMaxLabel').text('Mínimo: ; Máximo: ');
          mostrarActivos();

          // ⚠️ Avisos si fuera de rango
          if (Array.isArray(data.warnings) && data.warnings.length) {
            showRangeWarning(data.warnings, {
              min, max, cant: c, unidad
            });
          }
          return;
        }
        if (data.status === 'duplicado')     return alert('Este activo ya ha sido ingresado.');
        if (data.status === 'UNIDAD_INVALIDA') return alert('Unidad no permitida para este activo.');
        if (data.status === 'LIMITE_SUPERADO') return alert('Ya alcanzaste el máximo de 15 activos.');

        return alert('Error al guardar: ' + JSON.stringify(data));
      }

      // Compatibilidad si te llega string
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
      } else if (r === 'LIMITE_SUPERADO') {
        alert('Ya alcanzaste el máximo de 15 activos.');
      } else {
        alert('Error al guardar: ' + r);
      }
    });

  }

  function showRangeWarning(warnings, ctx, ms = 6000) {
    const { min, max, cant, unidad } = ctx;
    const msgs = [];

    if (Array.isArray(warnings) && warnings.includes('BAJO_MIN') && min !== null) {
      msgs.push(`⚠️ La cantidad ingresada (${cant} ${unidad}) está por <b>debajo</b> del mínimo (${min} ${unidad}).`);
    }
    if (Array.isArray(warnings) && warnings.includes('SOBRE_MAX') && max !== null) {
      msgs.push(`⚠️ La cantidad ingresada (${cant} ${unidad}) está por <b>encima</b> del máximo (${max} ${unidad}).`);
    }
    if (!msgs.length) return;

    const $alert = $('#alertLimite');

    // Marca como "activa" y limpia cualquier timer previo
    window.__rangeWarnActive && clearTimeout(window.__rangeWarnTimer);
    window.__rangeWarnActive = true;

    // Sello para evitar carreras (si llega otro aviso más nuevo)
    const stamp = Date.now();
    $alert.data('stamp', stamp);

    // Mostrar
    $alert
      .removeClass('d-none alert-danger')
      .addClass('alert-warning')
      .html(msgs.join('<br>'));

    // Ocultar solo cuando el sello coincide (sigue siendo el aviso vigente)
    window.__rangeWarnTimer = setTimeout(() => {
      // si otro aviso llegó después, no cierres este
      if ($alert.data('stamp') !== stamp) return;

      window.__rangeWarnActive = false;
      $alert.addClass('d-none').empty();
    }, ms);
  }



  // Eliminar una fila (lo llama el botón que viene en el HTML de listar)
  function eliminarFila(id) {
    if (!confirm('¿Estás seguro de eliminar este activo?')) return;
    $.post("{{ route('formulas.eliminar') }}", { id: id }, function (res) {
      if (res.trim() === 'ok') mostrarActivos();
      else alert('Error al eliminar: ' + res);
    });
  }
  window.eliminarFila = eliminarFila; // expone la función para los botones inline

  // Eliminar todos
  function eliminarTodosLosActivos() {
    if (!confirm('¿Estás seguro de eliminar todos los activos?')) return;
    $.post("{{ route('formulas.eliminarTodos') }}", {}, function (res) {
      if (res.trim() === 'OK') mostrarActivos();
      else alert('Error al eliminar: ' + res);
    });
  }
  window.eliminarTodosLosActivos = eliminarTodosLosActivos;

  // Cargar al entrar
  document.addEventListener('DOMContentLoaded', mostrarActivos);


  // === TOTAL EN MG + REGLAS DE BOTONES ===
    function verificarCondiciones() {
    $.get("{{ route('formulas.listar') }}?json=1", function (activos) {
      const codigosProhibidos = [4520, 1205, 1044, 1086, 70136, 1163];
      let totalMg = 0;
      let contieneProhibido = false;

      // === NUEVO: conteo de activos ===
      const n = Array.isArray(activos) ? activos.length : 0;

      activos.forEach(item => {
        const cantidad = parseFloat(item.cantidad);
        const unidad   = item.unidad;
        const codOdoo  = parseInt(item.cod_odoo);

        let cantidadMg = 0;
        switch (unidad) {
          case 'mg':  cantidadMg = cantidad;        break;
          case 'mcg': cantidadMg = cantidad / 1000; break;
          case 'UI':  cantidadMg = (codOdoo === 1343) ? (cantidad * 0.000025 / 1000)
                                                      : (cantidad * 0.00067);
                      break;
          default:    cantidadMg = 0;
        }

        totalMg += cantidadMg;
        if (codigosProhibidos.includes(codOdoo)) contieneProhibido = true;
      });

      // Total mg
      const $total = $('#total');
      if ($total.length) $total.text(`Total: ${totalMg.toFixed(2)} mg`);

      // Regla sobres
      const $btnSobres = $('#btnSobres');
      if ($btnSobres.length) $btnSobres.prop('disabled', contieneProhibido || totalMg < 2499);

      // === NUEVO: aviso visual y bloqueo por cantidad ===
      const $tabla = $('#tablaFormula');
      const $alert = $('#alertLimite');
      const $btnAdd = $('#btnAdd');

      // Quita estilos previos
      $tabla.removeClass('table-warning');

      // ❗ No ocultes el alert si hay un aviso de rango activo
      if (!window.__rangeWarnActive) {
        $alert.addClass('d-none').text('');
      }
      $btnAdd.prop('disabled', false);


      if (n >= 10 && n < 15) {
        // aviso naranja y pintar tabla
        $alert
          .removeClass('d-none')
          .text(`Llevas ${n} activos. El máximo permitido es 15.`);
        $tabla.addClass('table-warning'); // pinta la tabla (naranja suave)
      } else if (n >= 15) {
        // bloquear agregar
        $alert
          .removeClass('d-none')
          .text('Has alcanzado el máximo de 15 activos. El botón "Añadir" se ha bloqueado.');
        $btnAdd.prop('disabled', true);
        $tabla.addClass('table-warning');
      }

      // Guarda total si lo necesitas
      localStorage.setItem('totalMg', totalMg.toFixed(2));
    });
  }

    // Llama verificarCondiciones cada vez que recargas la tabla
    const __oldMostrarActivos = mostrarActivos;
    window.mostrarActivos = function() {
    __oldMostrarActivos();
    // Espera a que el HTML llegue y luego verifica (pequeño delay para asegurar el render)
    setTimeout(verificarCondiciones, 120);
    };

    // También al cargar por primera vez
    document.addEventListener('DOMContentLoaded', function () {
    setTimeout(verificarCondiciones, 200);
    });

    // === REDIRECCIONES A RESÚMENES ===
    $('#btnCapsulas').on('click', function() {
    // puedes guardar días de tratamiento si lo usas:
    const dias = $('#diasTratamiento').val() || 30;
    localStorage.setItem('diasTratamiento', dias);
    window.location.href = "{{ route('formulas.resumen_capsulas') }}";
    });

    $('#btnSobres').on('click', function() {
    const dias = $('#diasTratamiento').val() || 30;
    localStorage.setItem('diasTratamiento', dias);
    window.location.href = "{{ route('formulas.resumen_sobres') }}";
    });
</script>
@endpush
