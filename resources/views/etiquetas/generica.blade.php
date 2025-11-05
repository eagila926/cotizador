@php
   
    $prefill = session('etiqueta_preview', []);
    $soPrefill = $prefill['so'] ?? null;
    $medicoPrefill = $prefill['medico'] ?? null;
    $pacientePrefill = $prefill['paciente'] ?? null;

    // DR(a): mostrar solo "primer nombre" + "primer/último apellido"
    function nombreCorto($full) {
        $full = trim((string)$full);
        if ($full === '') return null;
        $p = preg_split('/\s+/', $full);
        $first = $p[0] ?? '';
        $last  = $p[count($p)-1] ?? '';
        return trim($first.' '.$last);
    }
    $medicoCorto = nombreCorto($medicoPrefill);
    
    // ===== Helpers de vista =====
    function abreviarNombreActivoView($nombre) {
        $nombre = preg_replace('/\s*\(.*?\)\s*/', '', (string)$nombre);
        if (stripos($nombre, 'BIFIDUMBACTERIUM') === 0) {
            return 'BIFIDUM' . substr($nombre, strlen('BIFIDUMBACTERIUM'));
        } elseif (stripos($nombre, 'LACTOBACILUS') === 0) {
            return 'LACTO' . substr($nombre, strlen('LACTOBACILUS'));
        }
        return $nombre;
    }

    // Excluir auxiliares (cápsulas, estearato, pastilleros, sobres, etc.)
    $excluir = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];
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

    // Pie (valores por defecto que luego el usuario puede editar)
    $tomas   = (int) ($formula->tomas_diarias ?? 3);
    $dias    = 30;
    $contieneCaps = $tomas * $dias;

    // Título dinámico
    $nombreEtiqueta = (string) ($formula->nombre_etiqueta ?? '');
    $fontSizeTitulo = (strlen($nombreEtiqueta) > 31) ? '25px' : '28px';

    $qf               = $qf ?? 'Q.F. EVELYN GARCÍA';
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
    <!-- ===== MODAL de Recetas ===== -->
<style>
  /* Opcional: oscurecer fondo del <dialog> nativo */
  #dlg-recetas::backdrop { background: rgba(0,0,0,.35); }
  .btn-primary {
    padding: 8px 14px; background:#0d6efd; border:none; color:#fff; border-radius:8px;
  }
  .btn-outline { padding:8px 14px; border:1px solid #bbb; background:#fff; border-radius:8px; }
  .form-row { margin-bottom:10px; }
  .sugs-panel {
    position:absolute; left:0; top:calc(100% + 4px); z-index:1000; background:#fff;
    border:1px solid #ddd; width:100%; max-height:220px; overflow-y:auto;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
  }
  .sugs-item { padding:6px 10px; cursor:pointer; }
  .sugs-item:hover { background:#f5f7fb; }
</style>

<dialog id="dlg-recetas" style="border:none;border-radius:12px;padding:0;max-width:720px;width:95%">
  <form method="POST" action="{{ route('recetas.store') }}" id="frm-recetas" style="padding:20px 24px" novalidate>
    @csrf
    <h3 style="margin:0 0 12px">Datos para guardar recetas</h3>

    <!-- Número de etiquetas (controla si paciente es editable) -->
    <div class="form-row">
      <label><strong>Número de etiquetas a imprimir</strong></label><br>
      <input type="number" name="num_etiquetas" id="num_etiquetas" value="1" min="1" max="200"
             style="width:140px" required>
    </div>

    <!-- Código de fórmula (no editable) -->
    <div class="form-row">
      <label>Código de fórmula</label><br>
      <input type="text" name="codigo_formula" value="{{ $formula->codigo }}" readonly
             style="width:260px;background:#f6f6f6">
    </div>

    <!-- SO (solo números) -->
    <div class="form-row">
      <label>SO</label><br>
      <input type="text" name="so" id="so" inputmode="numeric" pattern="\d+" maxlength="50"
             placeholder="Solo números" required style="width:260px">
    </div>

    <!-- Fecha (hoy por defecto) -->
    <div class="form-row">
      <label>Fecha</label><br>
      <input type="date" name="fecha" value="{{ now()->toDateString() }}" required>
    </div>

    <!-- Médico: buscar por nombre y guardar cédula -->
    <div class="form-row">
      <label>Médico (buscar por nombre/apellido)</label><br>
      <div style="position:relative; width:360px">
        <input type="text" id="medico_search" placeholder="Ej. 'María López'" autocomplete="off" style="width:100%">
        <div id="medico_sugs"></div>
      </div>
      <input type="hidden" name="cedula_medico" id="cedula_medico" required>
      <small id="medico_sel" style="display:block;margin-top:4px;color:#555"></small>
      <!--nombreOculto- -->
      <input type="hidden" name="medico_nombre" id="medico_nombre">

    </div>

    <!-- Paciente (solo editable cuando num_etiquetas = 1) -->
    <div id="grp_paciente" class="form-row">
      <label>Paciente (opcional si solo 1 etiqueta)</label><br>
      <input type="text" name="paciente" id="paciente" maxlength="50" style="width:360px"
             placeholder="Dejar vacío para nombre aleatorio">
    </div>

    <!-- para regresar a esta misma vista -->
    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:10px">
      <button type="button" id="btn-cancel" class="btn-outline">Cancelar</button>
      <button type="submit" id="btn-save" class="btn-primary" disabled>Guardar recetas y continuar</button>
    </div>
  </form>
</dialog>

<script>
  // ---------- Apertura del modal ----------
    const mustOpen = {{ session()->has('recetas_guardadas') ? 'false' : 'true' }};
    const dlg = document.getElementById('dlg-recetas');
    if (mustOpen && dlg && !dlg.open) dlg.showModal();

    document.getElementById('btn-cancel')?.addEventListener('click', () => dlg.close());
    // ---------- Referencias ----------
    const form   = document.getElementById('frm-recetas');
    const btnSave= document.getElementById('btn-save');
    const so     = document.getElementById('so');
    const numEt  = document.getElementById('num_etiquetas');
    const grpPac = document.getElementById('grp_paciente');
    const inpPac = document.getElementById('paciente');
    const inpMed = document.getElementById('medico_search');
    const cedulaHidden = document.getElementById('cedula_medico');
    const selText= document.getElementById('medico_sel');
    const sugBox = document.getElementById('medico_sugs');

    // ---------- SO: solo dígitos + mensaje claro ----------
    so?.addEventListener('input', (e) => {
        e.target.value = (e.target.value || '').replace(/[^0-9]/g, '');
        e.target.setCustomValidity('');
        updateSaveEnabled();
    });
    so?.addEventListener('invalid', function() {
        if (this.validity.patternMismatch) {
        this.setCustomValidity('SO solo acepta dígitos (0-9).');
        }
    });

    // ---------- Mostrar/ocultar "paciente" según num_etiquetas ----------
    function togglePaciente() {
        const n = parseInt(numEt.value || '1', 10);
        if (n > 1) {
        grpPac.style.opacity = '0.5';
        inpPac.value = '';
        inpPac.disabled = true;
        inpPac.placeholder = 'Se generarán nombres aleatorios';
        } else {
        grpPac.style.opacity = '1';
        inpPac.disabled = false;
        inpPac.placeholder = 'Dejar vacío para nombre aleatorio';
        }
        updateSaveEnabled();
    }
    numEt?.addEventListener('input', togglePaciente);
    togglePaciente();

    // ---------- Autocompletar de médicos ----------
    let sugPanel = null;
    function clearSugs(){ if (sugPanel){ sugPanel.remove(); sugPanel=null; } }

    
  async function buscarMedicos(q){
    if (!q || q.length < 2) { clearSugs(); return; }
    const url = "{{ route('medicos.buscar') }}" + "?q=" + encodeURIComponent(q);

    let list = [];
    try {
      const resp = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      if (!resp.ok) {
        console.error('HTTP error', resp.status);
        clearSugs();
        return; // evita forEach si hubo 500
      }

      const data = await resp.json();
      // Asegura array:
      list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
    } catch (e) {
      console.error('Fetch/JSON error:', e);
      clearSugs();
      return;
    }

    clearSugs();
    sugPanel = document.createElement('div');
    sugPanel.className = 'sugs-panel';

    if (!list.length) {
      const empty = document.createElement('div');
      empty.className = 'sugs-item';
      empty.style.color = '#777';
      empty.textContent = 'Sin resultados';
      sugPanel.appendChild(empty);
      sugBox.appendChild(sugPanel);
      return;
    }

    list.forEach(item => {
      const opt = document.createElement('div');
      opt.className = 'sugs-item';
      opt.textContent = item.label + ' — C.I. ' + item.cedula;
      opt.addEventListener('click', () => {
        inpMed.value = item.label;
        cedulaHidden.value = item.cedula;
        document.getElementById('medico_nombre').value = item.label; // <—
        selText.textContent = 'Seleccionado: ' + item.label + ' (C.I. ' + item.cedula + ')';
        clearSugs();
        updateSaveEnabled();
      });
      sugPanel.appendChild(opt);
    });

    sugBox.appendChild(sugPanel);
  }

    inpMed?.addEventListener('input', (e)=> {
        cedulaHidden.value = '';
        selText.textContent='';
        buscarMedicos(e.target.value.trim());
        updateSaveEnabled();
    });

    document.addEventListener('click', (e)=> {
        if (!sugBox.contains(e.target) && e.target !== inpMed) clearSugs();
    });

    // ---------- Habilitar/Deshabilitar botón Guardar ----------
    function updateSaveEnabled(){
        const soOk = !!so.value && /^\d+$/.test(so.value);
        const medOk = !!cedulaHidden.value;
        const n = parseInt(numEt.value || '1', 10);
        // Si n==1 paciente es opcional; si n>1 paciente se ignora
        btnSave.disabled = !(soOk && medOk && n >= 1);
    }
    updateSaveEnabled();

    // ---------- Validación final en submit ----------
    form.addEventListener('submit', (e) => {
        if (!cedulaHidden.value) {
        e.preventDefault();
        alert('Selecciona un médico de la lista.');
        inpMed.focus();
        }
    });
    </script>
    <!-- ===== FIN MODAL ===== -->


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
  // Evitar saltos de línea en contenteditable; permitir solo una línea
  document.querySelectorAll('.editable').forEach(el => {
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
    });
  });

  // Limitar a números los campos marcados como solo-números
  document.querySelectorAll('.js-only-numbers').forEach(el => {
    el.addEventListener('input', () => {
      // Mantén solo dígitos
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
