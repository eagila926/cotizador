@extends('layouts.app')
@section('title','Fórmulas Establecidas')

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <h4 class="mb-3">Fórmulas Establecidas</h4>

    <form action="{{ route('fe.add') }}" method="POST" class="row g-2 align-items-end" autocomplete="off">
      @csrf
      <div class="col-12 col-md-6 position-relative">
        <label class="form-label">Fórmula:</label>
        <input type="text" id="buscador" class="form-control" placeholder="Ingrese el código o nombre de la fórmula">
        <input type="hidden" name="formula_id" id="formula_id">
        <div id="sugerencias" class="list-group position-absolute w-100 shadow-sm"
             style="z-index:1000; display:none; max-height:260px; overflow:auto;"></div>
      </div>
      <div class="col-auto">
        <button type="submit" id="btn-add" class="btn btn-primary" disabled>Añadir</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Fórmulas seleccionadas</h5>
      <form action="{{ route('fe.clear') }}" method="POST" onsubmit="return confirm('¿Eliminar todas?');">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm">Eliminar todas las fórmulas</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Nombre Fórmula</th>
            <th>Acciones</th>
            <th>P Médico</th>
            <th>P Distribuidor</th>
            <th>P Paciente</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr data-id="{{ $r->id }}">
              <td>
                <div class="fw-semibold">{{ $r->codigo }}</div>
                <small class="text-muted">{{ $r->nombre_etiqueta }}</small>
              </td>
              
              <td class="text-nowrap">
                <a class="btn btn-secondary btn-sm" href="{{ route('fe.print',$r->id) }}" target="_blank" title="Imprimir">
                  <i class="bi bi-printer"></i>
                </a>

                <a class="btn btn-success btn-sm" href="{{ route('fe.items',$r->id) }}" title="Ver ítems">
                  <i class="bi bi-file-earmark-excel"></i>
                </a>

                <button type="button" class="btn btn-primary btn-sm btn-save-precios" title="Guardar precios">
                  <i class="bi bi-save"></i>
                </button>
                {{-- Editar (carga ítems en activo_temps y redirige a /formulas/nuevas) --}}
              <!-- <a class="btn btn-primary btn-sm"
                href="{{ route('formulas.editar.cargar', $r->id) }}"
                title="Editar">
                <i class="bi bi-pencil-square"></i>
              </a> -->

              </td>
              <!-- <td>{{ number_format($r->precio_medico,2) }}</td>
              <td>{{ number_format($r->precio_distribuidor,2) }}</td>
              <td>{{ number_format($r->precio_publico,2) }}</td> -->
              <td style="min-width:140px">
                <input type="number" step="0.01" min="0"
                      class="form-control form-control-sm precio-input"
                      data-field="precio_medico"
                      value="{{ number_format((float)$r->precio_medico, 2, '.', '') }}">
              </td>

              <td style="min-width:140px">
                <input type="number" step="0.01" min="0"
                      class="form-control form-control-sm precio-input"
                      data-field="precio_distribuidor"
                      value="{{ number_format((float)$r->precio_distribuidor, 2, '.', '') }}">
              </td>

              <td style="min-width:140px">
                <input type="number" step="0.01" min="0"
                      class="form-control form-control-sm precio-input"
                      data-field="precio_publico"
                      value="{{ number_format((float)$r->precio_publico, 2, '.', '') }}">
              </td>

              <td>
                <form action="{{ route('fe.remove',$r->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta fila?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Sin registros.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Iconos Bootstrap si no están en tu layout --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
(function(){
  const $buscador = document.getElementById('buscador');
  const $sugs     = document.getElementById('sugerencias');
  const $idHidden = document.getElementById('formula_id');
  const $btnAdd   = document.getElementById('btn-add');

  let t=null;
  $buscador.addEventListener('input', function(){
    const q = this.value.trim();
    $idHidden.value=''; $btnAdd.disabled = true;

    if (t) clearTimeout(t);
    if (q.length < 2) { $sugs.style.display='none'; $sugs.innerHTML=''; return; }

    t=setTimeout(()=>{
      fetch(`{{ route('fe.buscar') }}?q=`+encodeURIComponent(q))
        .then(r=>r.json()).then(data=>{
          $sugs.innerHTML='';
          if (!data.length){ $sugs.style.display='none'; return; }
          data.forEach(it=>{
            const a=document.createElement('a');
            a.href='#'; a.className='list-group-item list-group-item-action';
            a.textContent = it.display;
            a.onclick=(e)=>{ e.preventDefault();
              $buscador.value = it.display;
              $idHidden.value = it.id;
              $btnAdd.disabled = false;
              $sugs.style.display='none'; $sugs.innerHTML='';
            };
            $sugs.appendChild(a);
          });
          $sugs.style.display='block';
        });
    },220);
  });

  document.addEventListener('click',(e)=>{
    if (!e.target.closest('#sugerencias') && e.target!==$buscador) $sugs.style.display='none';
  });

  document.querySelectorAll('.sel-tipo').forEach(sel=>{
    sel.addEventListener('change', function(){
      const id = this.closest('tr').dataset.id;
      fetch(`{{ route('fe.update') }}`,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ formula_id:id, tipo:this.value })
      });
    });
  });
})();
</script>
<script>
  (function(){
    const $buscador = document.getElementById('buscador');
    const $sugs     = document.getElementById('sugerencias');
    const $idHidden = document.getElementById('formula_id');
    const $btnAdd   = document.getElementById('btn-add');

    let t=null;
    $buscador.addEventListener('input', function(){
      const q = this.value.trim();
      $idHidden.value=''; $btnAdd.disabled = true;

      if (t) clearTimeout(t);
      if (q.length < 2) { $sugs.style.display='none'; $sugs.innerHTML=''; return; }

      t=setTimeout(()=>{
        fetch(`{{ route('fe.buscar') }}?q=`+encodeURIComponent(q))
          .then(r=>r.json()).then(data=>{
            $sugs.innerHTML='';
            if (!data.length){ $sugs.style.display='none'; return; }
            data.forEach(it=>{
              const a=document.createElement('a');
              a.href='#'; a.className='list-group-item list-group-item-action';
              a.textContent = it.display;
              a.onclick=(e)=>{ e.preventDefault();
                $buscador.value = it.display;
                $idHidden.value = it.id;
                $btnAdd.disabled = false;
                $sugs.style.display='none'; $sugs.innerHTML='';
              };
              $sugs.appendChild(a);
            });
            $sugs.style.display='block';
          });
      },220);
    });

    document.addEventListener('click',(e)=>{
      if (!e.target.closest('#sugerencias') && e.target!==$buscador) $sugs.style.display='none';
    });

    // ===== NUEVO: Guardar precios por fila =====
    function getRowPrices(tr){
      const prices = {};
      tr.querySelectorAll('.precio-input').forEach(inp=>{
        prices[inp.dataset.field] = inp.value;
      });
      return prices;
    }

    document.querySelectorAll('.btn-save-precios').forEach(btn=>{
      btn.addEventListener('click', async function(){
        const tr = this.closest('tr');
        const id = tr.dataset.id;

        const payload = {
          id,
          ...getRowPrices(tr),
        };

        // UI simple
        this.disabled = true;

        try{
          const res = await fetch(`{{ route('fe.updatePrices') }}`, {
            method: 'POST',
            headers: {
              'Content-Type':'application/json',
              'X-CSRF-TOKEN':'{{ csrf_token() }}',
              'Accept':'application/json'
            },
            body: JSON.stringify(payload)
          });

          const data = await res.json().catch(()=> ({}));

          if(!res.ok){
            alert(data.message || 'No se pudo guardar. Revisa los valores.');
            return;
          }

          // opcional: feedback rápido
          // Puedes cambiarlo por toast de Bootstrap si ya lo usas
          alert('Precios guardados correctamente.');
        }catch(err){
          console.error(err);
          alert('Error de red o servidor al guardar.');
        }finally{
          this.disabled = false;
        }
      });
    });

  })();
  </script>

@endsection
