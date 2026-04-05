<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'suscripciones';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">SuperAdmin</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Gestionar Suscripciones</div>
        <form id="susForm" class="mt-4 space-y-3" enctype="multipart/form-data">
          <input type="hidden" id="sus_id" name="id" value="0">
          <div>
            <label class="block text-sm font-medium text-gray-700">Empresa</label>
            <select id="empresa_id" name="empresa_id" class="border rounded-lg p-2 w-full" required></select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Plan</label>
            <select id="plan_id" name="plan_id" class="border rounded-lg p-2 w-full" required></select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado</label>
              <select id="estado" name="estado" class="border rounded-lg p-2 w-full">
                <option value="activa">Activa</option>
                <option value="pendiente">Pendiente</option>
                <option value="vencida">Vencida</option>
                <option value="cancelada">Cancelada</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Plazo</label>
              <select id="plazo" name="plazo" class="border rounded-lg p-2 w-full">
                <option value="mensual">Mensual</option>
                <option value="anual">Anual</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Inicio</label>
              <input id="fecha_inicio" name="fecha_inicio" type="date" class="border rounded-lg p-2 w-full">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Fin</label>
              <input id="fecha_fin" name="fecha_fin" type="date" class="border rounded-lg p-2 w-full">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Detalle de pago</label>
            <textarea id="detalle_pago_texto" name="detalle_pago_texto" rows="3" class="border rounded-lg p-2 w-full" placeholder="Transferencia #1234 · Banco · referencia..."></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Adjunto comprobante</label>
            <input id="adjunto_pago_file" name="adjunto_pago_file" type="file" accept="image/*,.pdf" class="border rounded-lg p-2 w-full">
            <input id="adjunto_pago_path" name="adjunto_pago_path" type="hidden">
            <div id="adjuntoActual" class="text-xs text-gray-500 mt-1"></div>
          </div>
          <div id="historyMini" class="hidden border rounded-xl p-3 bg-gray-50">
            <div class="text-xs font-bold text-gray-700 mb-2">Historial reciente de esta empresa</div>
            <div id="historyMiniList" class="space-y-1 text-xs text-gray-600 max-h-36 overflow-auto"></div>
          </div>
          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" id="btnNuevo" class="px-2 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnGuardar" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Guardar</button>
          </div>
        </form>
      </div>
    </div>
    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
          <input id="search" type="text" placeholder="Buscar empresa..." class="border rounded-lg p-2 md:col-span-3">
          <select id="fEstado" class="border rounded-lg p-2">
            <option value="">Estado: todos</option>
            <option value="activa">Activa</option>
            <option value="pendiente">Pendiente</option>
            <option value="vencida">Vencida</option>
            <option value="cancelada">Cancelada</option>
          </select>
          <select id="perPage" class="border rounded-lg p-2">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
          <div id="total" class="text-sm text-gray-600 self-center"></div>
        </div>
        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100 mt-4">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
              <tr>
                <th class="px-4 py-3">Empresa</th>
                <th class="px-4 py-3">Plan</th>
                <th class="px-4 py-3">Periodo</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Días</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
        <div class="p-4 border-t">
          <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/superadmin/suscripciones.php')) ?>;
    let page = 1, per = 10, search = '', estado = '';
    let catalogs = { empresas: [], planes: [] };

    function loadHistory(empresaId) {
      if (!empresaId) {
        $('#historyMini').addClass('hidden');
        $('#historyMiniList').empty();
        return;
      }
      $.get(API, { action: 'history', empresa_id: empresaId }, function (res) {
        const list = $('#historyMiniList').empty();
        if (!(res && res.success && (res.data || []).length)) {
          $('#historyMini').addClass('hidden');
          return;
        }
        (res.data || []).slice(0, 8).forEach(h => {
          list.append(`<div class="rounded-lg border bg-white p-2">
            <div><span class="font-semibold">${h.plan_nombre || 'Plan'}</span> · ${h.estado || '-'} · ${h.plazo || '-'}</div>
            <div class="text-[11px] text-gray-500">${h.fecha_inicio || '-'} → ${h.fecha_fin || '-'}</div>
          </div>`);
        });
        $('#historyMini').removeClass('hidden');
      }, 'json');
    }

    function resetForm() {
      $('#susForm')[0].reset();
      $('#sus_id').val(0);
      $('#btnGuardar').text('Guardar');
      $('#fecha_inicio').val(new Date().toISOString().slice(0, 10));
      $('#plazo').val('mensual');
      $('#detalle_pago_texto').val('');
      $('#adjunto_pago_path').val('');
      $('#adjuntoActual').text('');
      $('#historyMini').addClass('hidden');
      $('#historyMiniList').empty();
      recalcFechaFin();
    }

    function recalcFechaFin() {
      const fi = ($('#fecha_inicio').val() || '').trim();
      const plazo = ($('#plazo').val() || 'mensual');
      if (!fi) return;
      const d = new Date(fi + 'T00:00:00');
      if (Number.isNaN(d.getTime())) return;
      if (plazo === 'anual') d.setFullYear(d.getFullYear() + 1);
      else d.setMonth(d.getMonth() + 1);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      $('#fecha_fin').val(`${y}-${m}-${day}`);
    }

    function fillCatalogs() {
      const emp = $('#empresa_id').empty();
      catalogs.empresas.forEach(e => emp.append(`<option value="${e.id}">${e.nombre} (${e.slug})</option>`));
      const plan = $('#plan_id').empty();
      catalogs.planes.forEach(p => plan.append(`<option value="${p.id}">${p.nombre} - ${(window.APP_CURRENCY?.symbol || '$')}${parseFloat(p.precio || 0).toFixed(2)}</option>`));
    }

    function loadCatalogs() {
      $.get(API, { action: 'catalogs' }, function (res) {
        if (!res || !res.success) return;
        catalogs = { empresas: res.empresas || [], planes: res.planes || [] };
        fillCatalogs();
        resetForm();
      }, 'json');
    }

    function renderPagination(total) {
      const pages = Math.max(1, Math.ceil((total || 0) / per));
      const box = $('#pagination').empty();
      for (let i = 1; i <= pages; i++) {
        box.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    function loadData() {
      $.get(API, { action: 'list', page, per, search, estado }, function (res) {
        if (!res || !res.success) return;
        const tb = $('#tbody').empty();
        (res.data || []).forEach(r => {
          const badge = `<span class="inline-flex px-2 py-1 rounded-full text-xs border ${r.estado === 'activa' ? 'bg-teal-50 text-teal-800 border-teal-100' : 'bg-gray-100 text-gray-700'}">${r.estado}</span>`;
          const dias = (r.dias_restantes === null || typeof r.dias_restantes === 'undefined') ? '-' : String(r.dias_restantes);
          tb.append(`<tr class="border-b last:border-0 border-gray-100">
            <td class="px-4 py-3"><div class="font-medium">${r.empresa_nombre}</div><div class="text-xs text-gray-500">${r.empresa_slug}</div></td>
            <td class="px-4 py-3"><div>${r.plan_nombre}</div><div class="text-xs text-gray-500">${(window.APP_CURRENCY?.symbol || '$')}${parseFloat(r.plan_precio || 0).toFixed(2)}</div></td>
            <td class="px-4 py-3 text-sm">${r.fecha_inicio || '-'}<br><span class="text-xs text-gray-500">${(r.plazo || 'mensual')} · hasta ${r.fecha_fin || 'sin fecha'}</span></td>
            <td class="px-4 py-3">${badge}</td>
            <td class="px-4 py-3 text-sm font-semibold ${parseInt(dias,10) <= 7 ? 'text-amber-700' : 'text-gray-700'}">${dias}</td>
            <td class="px-4 py-3"><div class="flex items-center justify-end gap-2">
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" data-id="${r.id}"><i data-lucide="pen"></i></button>
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 delBtn" data-id="${r.id}"><i data-lucide="trash-2"></i></button>
            </div></td>
          </tr>`);
        });
        $('#total').text(`Total: ${res.total || 0}`);
        renderPagination(res.total || 0);
        if (window.lucide) lucide.createIcons();
      }, 'json');
    }

    $('#susForm').on('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', 'save');
      $.ajax({
        url: API,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (res) {
          if (res && res.success) {
            showCustomAlert('Suscripción guardada.', 3000, 'success');
            resetForm();
            loadData();
          } else {
            showCustomAlert((res && res.message) || 'No se pudo guardar.', 5000, 'error');
          }
        },
        error: function () {
          showCustomAlert('No se pudo guardar la suscripción.', 5000, 'error');
        }
      });
    });

    $('#tbody').on('click', '.editBtn', function () {
      const id = parseInt($(this).data('id') || 0, 10);
      $.get(API, { action: 'list', page: 1, per: 200, search: '', estado: '' }, function (res) {
        if (!res || !res.success) return;
        const row = (res.data || []).find(x => parseInt(x.id, 10) === id);
        if (!row) return;
        $('#sus_id').val(row.id);
        $('#empresa_id').val(row.empresa_id);
        $('#plan_id').val(row.plan_id);
        $('#estado').val(row.estado);
        $('#fecha_inicio').val((row.fecha_inicio || '').slice(0, 10));
        $('#fecha_fin').val((row.fecha_fin || '').slice(0, 10));
        $('#plazo').val(row.plazo || 'mensual');
        $('#detalle_pago_texto').val(row.detalle_pago_json || '');
        $('#adjunto_pago_path').val(row.adjunto_pago_path || '');
        $('#adjuntoActual').html(row.adjunto_pago_path ? `Adjunto actual: <a class="text-teal-700 underline" href="${row.adjunto_pago_path}" target="_blank">ver archivo</a>` : '');
        $('#btnGuardar').text('Actualizar');
        loadHistory(row.empresa_id);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 'json');
    });

    $('#tbody').on('click', '.delBtn', function () {
      const id = parseInt($(this).data('id') || 0, 10);
      showCustomConfirm('¿Eliminar esta suscripción?', function () {
        $.post(API, { action: 'delete', id }, function (res) {
          if (res && res.success) {
            showCustomAlert('Suscripción eliminada.', 3000, 'info');
            loadData();
          } else {
            showCustomAlert((res && res.message) || 'No se pudo eliminar.', 5000, 'error');
          }
        }, 'json');
      });
    });

    $('#pagination').on('click', 'button', function () {
      page = parseInt($(this).data('page') || 1, 10);
      loadData();
    });
    $('#search').on('keyup', function () { search = $(this).val().trim(); page = 1; loadData(); });
    $('#fEstado').on('change', function () { estado = $(this).val(); page = 1; loadData(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '10', 10); page = 1; loadData(); });
    $('#btnNuevo').on('click', resetForm);
    $('#fecha_inicio, #plazo').on('change', recalcFechaFin);
    $('#empresa_id').on('change', function () { loadHistory(parseInt($(this).val() || '0', 10)); });

    loadCatalogs();
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
