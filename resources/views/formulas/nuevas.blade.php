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

        {{-- === Total + Botones de cotización === --}}
        <div class="row align-items-center mt-3">
          <div class="col-lg-10">
            <h5 id="total" class="mb-0">Total: 0.00 mg</h5>
          </div>
          <div class="col-lg-2">
            <button type="button" id="btnCapsulas" class="btn btn-success w-100">Cotizar</button>
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

        // Lee valores para mostrar (compatibilidad con catálogos antiguos)
        const minRaw = $(this).data('minimo');
        const maxRaw = $(this).data('maximo');
        const minNum = (minRaw === '' || minRaw === null || isNaN(Number(minRaw))) ? null : Number(minRaw);
        const maxNum = (maxRaw === '' || maxRaw === null || isNaN(Number(maxRaw))) ? null : Number(maxRaw);
        const unidad = (($(this).data('unidad') || 'mg') + '').trim();

        $('#activo').val(nombre).data('cod_odoo', cod);

        const fmtUM = (v, u) => (v === null ? '—' : `${v} ${u}`);
        $('#minMaxLabel').text(`Mínimo: ${fmtUM(minNum, unidad)} ; Máximo: ${fmtUM(maxNum, unidad)}`);

        $('#cant')
          .attr('min', minNum === null ? '' : minNum)
          .attr('max', maxNum === null ? '' : maxNum)
          .attr('step', unidad === 'mcg' ? 1 : (unidad === 'mg' ? 0.01 : 1));

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

  // Agregar a temp (sin restricción de cantidad de filas)
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
      activo: activo, cod_odoo: codOdoo, cantidad: cant, unidad: unidad
    }, function (res) {
      let data = res;
      if (typeof res === 'string') {
        try { data = JSON.parse(res); } catch(e) { /* compat con respuestas antiguas */ }
      }

      if (typeof data === 'object' && data !== null) {
        if (data.status === 'ok') {
          // limpiar inputs
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
      if (res.trim() === 'ok') mostrarActivos();
      else alert('Error al eliminar: ' + res);
    });
  }
  window.eliminarFila = eliminarFila;

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

  // === TOTAL EN MG (sin límites ni bloqueos) ===
  function verificarCondiciones() {
    $.get("{{ route('formulas.listar') }}?json=1", function (activos) {
      const codigosProhibidos = [4520, 1205, 1044, 1086, 70136, 1163];
      let totalMg = 0;
      let contieneProhibido = false;

      (Array.isArray(activos) ? activos : []).forEach(item => {
        const cantidad = parseFloat(item.cantidad);
        const unidad   = item.unidad;
        const codOdoo  = parseInt(item.cod_odoo);

        let cantidadMg = 0;
        switch (unidad) {
          case 'mg':  cantidadMg = cantidad;        break;
          case 'mcg': cantidadMg = cantidad / 1000; break;
          case 'UI':  cantidadMg = (codOdoo === 1343)
                      ? (cantidad * 0.000025 / 1000)
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

      // (Opcional) Si usas botón de sobres en otro layout
      const $btnSobres = $('#btnSobres');
      if ($btnSobres.length) $btnSobres.prop('disabled', contieneProhibido || totalMg < 2499);

      localStorage.setItem('totalMg', totalMg.toFixed(2));
    });
  }

  // Llama verificarCondiciones cada vez que recargas la tabla
  const __oldMostrarActivos = mostrarActivos;
  window.mostrarActivos = function() {
    __oldMostrarActivos();
    setTimeout(verificarCondiciones, 120);
  };

  // También al cargar por primera vez
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
