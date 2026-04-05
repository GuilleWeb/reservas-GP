<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'servicios';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
$id_e = request_id_e();
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['superadmin', 'admin']));

if (!$is_tenant_admin) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado. Solo gerentes y administradores pueden gestionar servicios.</div>';
  return;
}
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Empresa</div>
        <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Servicio</div>

        <form id="formServicio" class="space-y-4">
          <input type="hidden" id="servicio_id" name="id" value="0">

          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre del Servicio <span
                class="text-red-500">*</span></label>
            <input type="text" id="nombre" name="nombre"
              class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
              required placeholder="Ej: Corte Clásico">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Precio (<?= htmlspecialchars((string) ($currency_symbol ?? '$')) ?>) <span
                  class="text-red-500">*</span></label>
              <input type="number" step="0.01" min="0" id="precio_base" name="precio_base"
                class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
                required placeholder="Ej: 15.00">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Duración (min.) <span
                  class="text-red-500">*</span></label>
              <input type="number" step="5" min="5" id="duracion_minutos" name="duracion_minutos"
                class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
                required placeholder="Ej: 30">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Descripción Corta</label>
            <textarea id="descripcion" name="descripcion" rows="3"
              class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 text-sm"
              placeholder="Detalle del servicio..."></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Asignación de empleados</label>
            <button type="button" id="btnOpenAssignModal"
              class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-left bg-white hover:bg-gray-50 text-sm font-medium text-gray-700">
              Asignar servicio a empleados
            </button>
            <div id="empleadoSelectedInfo" class="mt-1 text-xs text-gray-500">Sin empleados asignados.</div>
            <div id="empleadoRequiredState" class="mt-1 text-xs font-medium text-red-600">Requerido: asigna al menos 1 empleado.</div>
            <select id="empleado_ids" name="empleado_ids[]" multiple class="hidden"></select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked>
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
              </div>
              <span class="ml-3 text-sm font-medium text-gray-700" id="estadoLabel">Activo</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mostrar en Home Page</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="show_in_home" name="show_in_home" value="1" class="sr-only peer">
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full
                peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                peer-checked:bg-teal-600"></div>
              <span class="ml-3 text-sm font-medium text-gray-700">Incluir en sección de servicios del inicio</span>
            </label>
          </div>

          <div id="formAlert" class="hidden rounded p-3 text-sm"></div>

          <div class="pt-4 flex items-center justify-between border-t border-gray-100">
            <button type="button" onclick="resetForm()"
              class="text-sm text-gray-500 hover:text-gray-800 border border-gray-300 rounded-lg px-2 py-2">Nuevo</button>
            <button type="submit"
              class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition"
              id="btnSave">Guardar Servicio</button>
          </div>
        </form>
      </div>
    </div>
    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
          <div>
              <div class="font-semibold text-gray-900">Listado</div>
              <div class="text-sm text-gray-500">Acciones: entrar, editar y eliminar.</div>
            </div>
            <!-- <div class="text-sm text-gray-500">Tip: /{slug}/dashboard</div> -->

          <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
           <input type="text" id="txtSearch"
                class="pl-9 border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500 w-48 p-2 md:col-span-2"
                placeholder="Buscar...">
            <select id="filterStatus"
              class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500">
              <option value="">Todos (Estado)</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
            <select id="selLimit"
              class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500">
              <option value="10">10 por pág</option>
              <option value="25">25 por pág</option>
              <option value="50">50 por pág</option>
            </select>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
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

        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
          <div class="text-sm text-gray-500 mb-2 sm:mb-0" id="pageInfo">Mostrando 0 resultados</div>
          <div class="flex items-center space-x-1" id="pagination"></div>
        </div>

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
        <div class="text-xs text-gray-500">Selecciona qué empleados pueden realizar este servicio.</div>
      </div>
      <button type="button" id="btnCloseAssignModal" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50">
        <i data-lucide="x"></i>
      </button>
    </div>
    <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <button type="button" id="btnSelectAllEmp" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Seleccionar todos</button>
      <button type="button" id="btnClearAllEmp" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Limpiar selección</button>
    </div>
    <div id="assignModalBody" class="p-4 max-h-[60vh] overflow-auto space-y-4 bg-gray-50"></div>
    <div class="p-4 border-t flex items-center justify-end gap-2">
      <button type="button" id="btnCancelAssign" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Cancelar</button>
      <button type="button" id="btnSaveAssign" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">Guardar selección</button>
    </div>
  </div>
</div>

<script>
  let currentPage = 1;
  let optionsLoaded = false;
  let empleadosCatalog = [];
  let tempSelectedEmpleados = new Set();

  function loadOptions() {
    $.get('<?= app_url('api/admin/servicios.php') ?>', { action: 'get_options', id_e: <?= json_encode(request_id_e()) ?> }, function (res) {
      if (!res.success) return;
      const emp = $('#empleado_ids').empty();
      empleadosCatalog = (res.empleados || []).map(u => ({
        id: String(u.id),
        nombre: u.nombre || '',
        sucursal_nombre: u.sucursal_nombre || 'Sin sucursal',
      }));
      empleadosCatalog.forEach(u => emp.append(`<option value="${u.id}">${u.nombre}</option>`));
      updateEmpleadoSelectedInfo();
      optionsLoaded = true;
    }, 'json');
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

  function renderAssignModalBody() {
    const body = $('#assignModalBody').empty();
    if (!empleadosCatalog.length) {
      body.html('<div class="text-sm text-gray-500">No hay empleados disponibles.</div>');
      return;
    }
    const groups = {};
    empleadosCatalog.forEach(u => {
      const key = u.sucursal_nombre || 'Sin sucursal';
      if (!groups[key]) groups[key] = [];
      groups[key].push(u);
    });
    Object.keys(groups).forEach(sucursal => {
      const wrap = $(`<div class="bg-white border rounded-xl p-3"></div>`);
      wrap.append(`<div class="text-sm font-semibold text-gray-800 mb-2">${sucursal}</div>`);
      const grid = $('<div class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>');
      groups[sucursal].forEach(u => {
        const checked = tempSelectedEmpleados.has(String(u.id)) ? 'checked' : '';
        grid.append(`
          <label class="flex items-center gap-2 border rounded-lg px-3 py-2 hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" class="emp-check h-4 w-4 accent-teal-600" value="${u.id}" ${checked}>
            <span class="text-sm text-gray-700">${u.nombre}</span>
          </label>
        `);
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

  function closeAssignModal() {
    $('#assignModal').addClass('hidden').removeClass('flex');
  }

  function saveAssignModal() {
    $('#empleado_ids').val(Array.from(tempSelectedEmpleados));
    updateEmpleadoSelectedInfo();
    closeAssignModal();
  }

  function renderPagination(total_pages, current) {
    let html = '';
    if (total_pages <= 1) {
      $('#pagination').html('');
      return;
    }

    html += `<button onclick="loadData(${current - 1})" class="px-3 py-1 rounded-md border bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50" ${current === 1 ? 'disabled' : ''}><i data-lucide="chevron-left"></i></button>`;

    for (let i = 1; i <= total_pages; i++) {
      if (i === 1 || i === total_pages || (i >= current - 1 && i <= current + 1)) {
        let activeClass = i === current ? 'bg-teal-600 text-white font-medium shadow' : 'border bg-white text-gray-700 hover:bg-gray-50';
        html += `<button onclick="loadData(${i})" class="px-3 py-1 rounded-md ${activeClass}">${i}</button>`;
      } else if (i === current - 2 || i === current + 2) {
        html += `<span class="px-2 text-gray-400">...</span>`;
      }
    }

    html += `<button onclick="loadData(${current + 1})" class="px-3 py-1 rounded-md border bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50" ${current === total_pages ? 'disabled' : ''}><i data-lucide="chevron-right"></i></button>`;

    $('#pagination').html(html);
  }

  function loadData(page = 1) {
    currentPage = page;
    const per = $('#selLimit').val();
    const search = $('#txtSearch').val().trim();
    const status = $('#filterStatus').val();

    const tbody = $('#tableBody');
    tbody.html('<tr><td colspan="5" class="py-10 text-center"><i data-lucide="loader-2" class="text-teal-600 text-3xl mb-2 animate-spin"></i><br><span class="text-gray-500">Cargando servicios...</span></td></tr>');

    $.get('<?= app_url('api/admin/servicios.php') ?>', { action: 'list', id_e: <?= json_encode(request_id_e()) ?>, page: currentPage, per: per, search: search, status: status }, function (res) {
      tbody.empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let badge = parseInt(item.activo) === 1
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">Activo</span>`
            : `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">Inactivo</span>`;
          let price = parseFloat(item.precio_base).toFixed(2);

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4">
                <div class="font-semibold text-gray-800">${item.nombre}</div>
                <div class="text-xs text-gray-500 truncate max-w-xs" title="${item.descripcion}">${item.descripcion || '-'}</div>
            </td>
            <td class="py-3 px-4 font-mono text-sm text-gray-700">${window.APP_CURRENCY?.symbol || '<?= htmlspecialchars((string) ($currency_symbol ?? '$')) ?>'}${price}</td>
            <td class="py-3 px-4 text-sm text-gray-600"><i data-lucide="clock" class="text-gray-400 mr-1"></i> ${item.duracion_minutos} min</td>
            <td class="py-3 px-4">${badge}</td>
            <td class="py-3 px-4 text-right">
                <div class="flex justify-end space-x-2">
                    <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg border border-blue-200 shadow-sm" title="Editar">
                        <i data-lucide="pen"></i>
                    </button>
                    <button onclick="deleteItem(${item.id})" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg border border-red-200 shadow-sm" title="Eliminar">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Mostrando ${res.data.length} de ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="5" class="py-8 text-center text-gray-500">No se encontraron servicios registrados.</td></tr>');
        $('#pageInfo').text('Mostrando 0 resultados');
      }
      renderPagination(res.total_pages, currentPage);
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function resetForm() {
    $('#formServicio')[0].reset();
    $('#servicio_id').val(0);
    $('#formAlert').addClass('hidden');
    $('#btnSave').text('Guardar Servicio');
    $('#activo').prop('checked', true);
    $('#estadoLabel').text('Activo');
    $('#show_in_home').prop('checked', false);
    $('#empleado_ids').val([]);
    updateEmpleadoSelectedInfo();
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/servicios.php') ?>', { action: 'get', id_e: <?= json_encode(request_id_e()) ?>, id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#servicio_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#precio_base').val(d.precio_base);
        $('#duracion_minutos').val(d.duracion_minutos);
        $('#descripcion').val(d.descripcion);

        const st = parseInt(d.activo) === 1;
        $('#activo').prop('checked', st);
        $('#estadoLabel').text(st ? 'Activo' : 'Inactivo');
        $('#show_in_home').prop('checked', parseInt(d.show_in_home || 0, 10) === 1);
        if (optionsLoaded) {
          $('#empleado_ids').val((d.empleado_ids || []).map(String));
          updateEmpleadoSelectedInfo();
        }

        $('#btnSave').text('Actualizar Servicio');
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas de eliminar este servicio permanentemente?", function () {
      $.post('<?= app_url('api/admin/servicios.php') ?>', { action: 'delete', id_e: <?= json_encode(request_id_e()) ?>, id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Servicio eliminado.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al eliminar', 5000, 'error');
        }
      }, 'json');
    });
  }

  function showAlert(msg, isError = false) {
    const al = $('#formAlert');
    al.removeClass('hidden bg-red-50 text-red-700 border-red-200 bg-green-50 text-green-700 border-green-200 border');
    if (isError) {
      al.addClass('bg-red-50 text-red-700 border-red-200 border').html(`<i data-lucide="alert-circle" class="mr-2"></i> ${msg}`);
    } else {
      al.addClass('bg-green-50 text-green-700 border-green-200 border').html(`<i data-lucide="check-circle-2" class="mr-2"></i> ${msg}`);
    }
    setTimeout(() => al.addClass('hidden'), 5000);
  }

  $(function () {
    loadOptions();

    $('#btnOpenAssignModal').on('click', openAssignModal);
    $('#btnCloseAssignModal, #btnCancelAssign').on('click', closeAssignModal);
    $('#assignModal').on('click', function (e) {
      if (e.target === this) closeAssignModal();
    });
    $('#assignModalBody').on('change', '.emp-check', function () {
      const v = String($(this).val());
      if ($(this).is(':checked')) tempSelectedEmpleados.add(v);
      else tempSelectedEmpleados.delete(v);
    });
    $('#btnSelectAllEmp').on('click', function () {
      tempSelectedEmpleados = new Set(empleadosCatalog.map(u => String(u.id)));
      renderAssignModalBody();
    });
    $('#btnClearAllEmp').on('click', function () {
      tempSelectedEmpleados = new Set();
      renderAssignModalBody();
    });
    $('#btnSaveAssign').on('click', saveAssignModal);

    $('#activo').on('change', function () {
      $('#estadoLabel').text(this.checked ? 'Activo' : 'Inactivo');
    });

    $('#formServicio').on('submit', function (e) {
      e.preventDefault();
      if ((($('#empleado_ids').val() || []).length) === 0) {
        showCustomAlert('Debes asignar al menos un empleado a este servicio.', 5000, 'warning');
        return;
      }
      const btn = $('#btnSave');
      const oldHtml = btn.html();
      btn.prop('disabled', true).html('<i data-lucide="loader-2" class="mr-2 animate-spin"></i> Guardando...');

      let data = $(this).serializeArray();
      if (!$("#activo").is(":checked")) {
        data.push({ name: 'activo', value: '0' });
      }
      if (!$("#show_in_home").is(":checked")) {
        data.push({ name: 'show_in_home', value: '0' });
      }
      data.push({ name: 'id_e', value: '<?= htmlspecialchars((string) request_id_e(), ENT_QUOTES) ?>' });

      $.post('<?= app_url('api/admin/servicios.php') ?>?action=save', data, function (res) {
        btn.prop('disabled', false).html(oldHtml);
        if (res.success) {
          showCustomAlert('Servicio guardado con éxito.', 5000, 'success');
          resetForm();
          loadData(currentPage);
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    $('#selLimit, #filterStatus').on('change', () => loadData(1));
    $('#txtSearch').on('keyup', function (e) {
      if (e.key === 'Enter') loadData(1);
    });

    loadData();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
