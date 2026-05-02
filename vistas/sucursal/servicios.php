<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$is_gerente = ($role === 'gerente');
$id_e = request_id_e();
$module = 'servicios_sucursal';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Servicio</div>
        <form id="formServicio" class="space-y-4">
          <input type="hidden" id="servicio_id" name="id" value="0">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre del Servicio <span class="text-red-500">*</span></label>
            <input type="text" id="nombre" name="nombre" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Precio ($) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" min="0" id="precio_base" name="precio_base" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Duración (min.) <span class="text-red-500">*</span></label>
              <input type="number" step="5" min="5" id="duracion_minutos" name="duracion_minutos" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Descripción Corta</label>
            <textarea id="descripcion" name="descripcion" rows="3" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Asignación de empleados</label>
            <button type="button" id="btnOpenAssignModal" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-left bg-white hover:bg-gray-50 text-sm font-medium text-gray-700">Asignar servicio a empleados</button>
            <div id="empleadoSelectedInfo" class="mt-1 text-xs text-gray-500">Sin empleados asignados.</div>
            <div id="empleadoRequiredState" class="mt-1 text-xs font-medium text-red-600">Requerido: asigna al menos 1 empleado.</div>
            <select id="empleado_ids" name="empleado_ids[]" multiple class="hidden"></select>
          </div>
          <div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked>
              <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-teal-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
              <span class="ml-3 text-sm font-medium text-gray-700">Activo</span>
            </label>
          </div>
          <div class="pt-4 flex items-center justify-between border-t border-gray-100">
            <?php if (!$is_gerente): ?>
              <button type="button" onclick="resetForm()" class="text-sm text-gray-500 hover:text-gray-800 border border-gray-300 rounded-lg px-2 py-2">Nuevo</button>
            <?php endif; ?>
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition" id="btnSave">Guardar</button>
          </div>
        </form>
      </div>
    </div>
    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border">
        <div class="p-5 border-b">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <div class="font-semibold text-gray-900">Listado</div>
              <div class="text-sm text-gray-500">Acciones: editar y eliminar.</div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3">
            <input id="searchSrv" type="text" placeholder="Buscar servicio..." class="border rounded-lg p-2 md:col-span-5">
            <select id="fActivoSrv" class="border rounded-lg p-2 md:col-span-3">
              <option value="">Estado: todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
            <select id="perPageSrv" class="border rounded-lg p-2 md:col-span-2">
              <option value="10">10 / pág</option>
              <option value="15" selected>15 / pág</option>
              <option value="25">25 / pág</option>
              <option value="50">50 / pág</option>
            </select>
            <div class="md:col-span-2 flex items-center justify-end text-sm text-gray-500" id="totalSrv">&nbsp;</div>
          </div>
        </div>

        <div class="overflow-auto bg-gray-50 rounded-b-2xl">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
              <tr>
                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Servicio</th>
                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Precio</th>
                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Duración</th>
                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
              </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
          </table>
        </div>

        <div class="p-4 border-t">
          <div id="paginationSrv" class="flex flex-wrap gap-2 justify-end"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="assignModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border w-full max-w-3xl">
    <div class="p-4 border-b flex items-center justify-between">
      <div>
        <div class="font-semibold text-gray-900">Asignar Servicio a Empleados</div>
        <div class="text-xs text-gray-500">Selecciona los empleados de esta sucursal.</div>
      </div>
      <button type="button" id="btnCloseAssignModal" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="x"></i></button>
    </div>
    <div class="p-4 border-b flex items-center justify-between gap-2">
      <button type="button" id="btnSelectAllEmp" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Seleccionar todos</button>
      <button type="button" id="btnClearAllEmp" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Limpiar selección</button>
    </div>
    <div id="assignModalBody" class="p-4 max-h-[60vh] overflow-auto space-y-3 bg-gray-50"></div>
    <div class="p-4 border-t flex items-center justify-end gap-2">
      <button type="button" id="btnCancelAssign" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Cancelar</button>
      <button type="button" id="btnSaveAssign" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">Guardar selección</button>
    </div>
  </div>
</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/servicios.php') ?>';
  let page = 1;
  let per = 15;
  let search = '';
  let activo = '';
  let total = 0;
  let totalPages = 1;
  let empleadosCatalog = [];
  let tempSelectedEmpleados = new Set();

  function debounce(fn, ms) {
    let t;
    return function () {
      clearTimeout(t);
      const args = arguments;
      t = setTimeout(() => fn.apply(null, args), ms);
    };
  }

  function updateEmpleadoSelectedInfo() {
    const selected = new Set(($('#empleado_ids').val() || []).map(String));
    const names = empleadosCatalog.filter(u => selected.has(String(u.id))).map(u => u.nombre);
    if (!names.length) {
      $('#empleadoSelectedInfo').text('Sin empleados asignados.');
      $('#empleadoRequiredState').removeClass('text-teal-700').addClass('text-red-600').text('Requerido: asigna al menos 1 empleado.');
      return;
    }
    const preview = names.slice(0, 3).join(', ');
    const extra = names.length > 3 ? ` +${names.length - 3} más` : '';
    $('#empleadoSelectedInfo').text(`${names.length} empleado(s): ${preview}${extra}`);
    $('#empleadoRequiredState').removeClass('text-red-600').addClass('text-teal-700').text('Asignación completada.');
  }

  function loadOptions() {
    $.get(API_URL, { action: 'get_options' }, function (res) {
      if (!res || !res.success) return;
      empleadosCatalog = res.empleados || [];
      const emp = $('#empleado_ids').empty();
      empleadosCatalog.forEach(u => emp.append(`<option value="${u.id}">${u.nombre}</option>`));
      updateEmpleadoSelectedInfo();
    }, 'json');
  }

  function renderAssignModalBody() {
    const body = $('#assignModalBody').empty();
    if (!empleadosCatalog.length) {
      body.html('<div class="text-sm text-gray-500">No hay empleados disponibles.</div>');
      return;
    }
    const groups = {};
    empleadosCatalog.forEach(u => {
      const key = String(u.rol || 'empleado').toLowerCase();
      if (!groups[key]) groups[key] = [];
      groups[key].push(u);
    });
    Object.keys(groups).forEach(role => {
      const wrap = $(`<div class="bg-white border rounded-xl p-3"></div>`);
      wrap.append(`<div class="text-sm font-semibold text-gray-800 mb-2 uppercase">${role}</div>`);
      const grid = $('<div class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>');
      groups[role].forEach(u => {
        const checked = tempSelectedEmpleados.has(String(u.id)) ? 'checked' : '';
        grid.append(`<label class="flex items-center gap-2 border rounded-lg px-3 py-2 hover:bg-gray-50"><input type="checkbox" class="emp-check h-4 w-4 accent-teal-600" value="${u.id}" ${checked}><span class="text-sm text-gray-700">${u.nombre}</span></label>`);
      });
      wrap.append(grid);
      body.append(wrap);
    });
  }

  function openAssignModal() {
    tempSelectedEmpleados = new Set(($('#empleado_ids').val() || []).map(String));
    renderAssignModalBody();
    $('#assignModal').removeClass('hidden').addClass('flex');
    if (window.lucide) lucide.createIcons();
  }
  function closeAssignModal() { $('#assignModal').addClass('hidden').removeClass('flex'); }
  function saveAssignModal() { $('#empleado_ids').val(Array.from(tempSelectedEmpleados)); updateEmpleadoSelectedInfo(); closeAssignModal(); }

  function badgeActive(v) {
    const a = parseInt(v || 0, 10) === 1;
    return a
      ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Activo</span>'
      : '<span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-bold bg-slate-50 text-slate-600 border">Inactivo</span>';
  }

  function renderPagination() {
    const pag = $('#paginationSrv').empty();
    const tp = totalPages || 1;
    for (let i = 1; i <= tp; i++) {
      pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
    }
  }

  function loadData(p = 1) {
    page = p;
    $.get(API_URL, { action: 'list', page, per, search, activo }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res && res.success && (res.data || []).length > 0) {
        total = parseInt(res.total || 0, 10);
        totalPages = parseInt(res.total_pages || 1, 10) || 1;
        $('#totalSrv').text(`Total: ${total}`);
        renderPagination();
        (res.data || []).forEach(item => {
          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors">
            <td class="py-3 px-4 font-semibold text-gray-800 text-sm">${item.nombre}</td>
            <td class="py-3 px-4 text-sm text-gray-700 font-mono">$${parseFloat(item.precio_base).toFixed(2)}</td>
            <td class="py-3 px-4 text-sm text-gray-600">${item.duracion_minutos} min</td>
            <td class="py-3 px-4">${badgeActive(item.activo)}</td>
            <td class="py-3 px-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar" data-id="${item.id}"><i data-lucide="pen"></i></button>
                ${<?= json_encode($is_gerente) ?> ? '' : `<button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 delBtn" title="Eliminar" data-id="${item.id}"><i data-lucide="trash-2"></i></button>`}
              </div>
            </td>
          </tr>`);
        });
      } else {
        total = parseInt((res && res.total) || 0, 10) || 0;
        totalPages = parseInt((res && res.total_pages) || 1, 10) || 1;
        $('#totalSrv').text(`Total: ${total}`);
        renderPagination();
        tbody.html('<tr><td colspan="5" class="py-10 text-center text-gray-500">No hay servicios.</td></tr>');
      }
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function resetForm() {
    $('#formServicio')[0].reset();
    $('#servicio_id').val(0);
    $('#btnSave').text('Guardar');
    $('#empleado_ids').val([]);
    updateEmpleadoSelectedInfo();
  }

  function editItem(id) {
    $.get(API_URL, { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#servicio_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#precio_base').val(d.precio_base);
        $('#duracion_minutos').val(d.duracion_minutos);
        $('#descripcion').val(d.descripcion);
        $('#activo').prop('checked', parseInt(d.activo) === 1);
        $('#empleado_ids').val((d.empleado_ids || []).map(String));
        updateEmpleadoSelectedInfo();
        $('#btnSave').text('Actualizar');
      }
    }, 'json');
  }

  $(function () {
    loadOptions();
    $('#btnOpenAssignModal').on('click', openAssignModal);
    $('#btnCloseAssignModal,#btnCancelAssign').on('click', closeAssignModal);
    $('#assignModal').on('click', function (e) { if (e.target === this) closeAssignModal(); });
    $('#assignModalBody').on('change', '.emp-check', function () {
      const v = String($(this).val());
      if ($(this).is(':checked')) tempSelectedEmpleados.add(v); else tempSelectedEmpleados.delete(v);
    });
    $('#btnSelectAllEmp').on('click', function () { tempSelectedEmpleados = new Set(empleadosCatalog.map(u => String(u.id))); renderAssignModalBody(); });
    $('#btnClearAllEmp').on('click', function () { tempSelectedEmpleados = new Set(); renderAssignModalBody(); });
    $('#btnSaveAssign').on('click', saveAssignModal);

    $('#formServicio').on('submit', function (e) {
      e.preventDefault();
      if (<?= json_encode($is_gerente) ?> && parseInt($('#servicio_id').val() || '0', 10) <= 0) {
        showCustomAlert('Como gerente solo puedes editar servicios existentes.', 4500, 'warning');
        return;
      }
      if ((($('#empleado_ids').val() || []).length) === 0) {
        alert('Debes asignar al menos un empleado.');
        return;
      }
      let data = $(this).serializeArray();
      if (!$("#activo").is(":checked")) data.push({ name: 'activo', value: '0' });
      $.post(API_URL + '?action=save', data, function (res) {
        if (res.success) {
          resetForm();
          loadData(page);
        } else {
          alert(res.message || 'Error');
        }
      }, 'json');
    });

    $('#paginationSrv').on('click', 'button', function () {
      const p = parseInt($(this).data('page') || '1', 10) || 1;
      loadData(p);
    });

    const debounceLoad = debounce(() => loadData(1), 250);
    $('#searchSrv').on('keyup', function () { search = $(this).val(); debounceLoad(); });
    $('#fActivoSrv').on('change', function () { activo = $(this).val(); loadData(1); });
    $('#perPageSrv').on('change', function () { per = parseInt($(this).val() || '15', 10) || 15; loadData(1); });

    $('#tableBody').on('click', '.editBtn', function () {
      const id = parseInt($(this).data('id') || '0', 10) || 0;
      if (id > 0) editItem(id);
    });
    $('#tableBody').on('click', '.delBtn', function () {
      const id = parseInt($(this).data('id') || '0', 10) || 0;
      if (!id) return;
      if (!confirm('¿Eliminar este servicio?')) return;
      $.post(API_URL + '?action=delete', { id }, function (res) {
        if (res && res.success) {
          loadData(page);
          return;
        }
        alert((res && res.message) || 'No se pudo eliminar.');
      }, 'json');
    });

    loadData(1);
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
