<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = current_user();
$role = $user['rol'] ?? null;
if (!$user || !in_array($role, ['superadmin', 'admin'], true)) {
  http_response_code(403);
  echo 'No autorizado.';
  exit;
}
$module = 'sucursales';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
$id_e = request_id_e();
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['superadmin', 'admin']));

if (!$is_tenant_admin) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">Admin</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Gestionar Sucursal</div>

        <form id="formSucursal" class="mt-4 space-y-3">
          <input type="hidden" id="sucursal_id" name="id" value="0">

          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre de la Sucursal <span class="text-red-500">*</span></label>
            <input type="text" id="nombre" name="nombre" class="mt-1 w-full border rounded-lg p-2 focus:ring-2 focus:ring-teal-500" required placeholder="Ej: Zona 10">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" id="direccion" name="direccion" class="mt-1 w-full border rounded-lg p-2" placeholder="Ej: 10ma Calle...">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Slug (URL)</label>
              <input type="text" id="slug" name="slug" class="mt-1 w-full border rounded-lg p-2 focus:ring-2 focus:ring-teal-500 font-mono text-sm" placeholder="Ej: zona-10">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Teléfono</label>
              <input type="text" id="telefono" name="telefono" class="mt-1 w-full border rounded-lg p-2" placeholder="Ej: +502 123456">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Horario Visible</label>
            <input type="text" id="horario" name="horario" class="mt-1 w-full border rounded-lg p-2" placeholder="Ej: Lun-Vie 8am-6pm">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <div class="flex items-center gap-3">
              <input type="hidden" id="activo" name="activo" value="1">
              <button type="button" id="activoSwitch"
                class="relative inline-flex h-6 w-11 items-center rounded-full bg-teal-600 transition-colors"
                aria-pressed="true">
                <span id="activoKnob"
                  class="inline-block h-5 w-5 translate-x-5 rounded-full bg-white shadow transition-transform"></span>
              </button>
              <span id="activoLabel" class="text-sm font-medium text-gray-700">Activa</span>
            </div>
          </div>

          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" onclick="resetForm()" class="px-2 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnSave" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Guardar</button>
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

          <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            <input id="txtSearch" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
            <select id="filterStatus" class="border rounded-lg p-2">
              <option value="">Estado: todas</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
            <select id="selLimit" class="border rounded-lg p-2">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <div id="pageInfo" class="text-sm text-gray-600 self-center"></div>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-4 py-3 cursor-pointer select-none">Sucursal</th>
                <th class="text-left px-4 py-3 cursor-pointer select-none">Dirección</th>
                <th class="text-left px-4 py-3 cursor-pointer select-none">Tel / Horario</th>
                <th class="text-left px-4 py-3 cursor-pointer select-none">Estado</th>
                <th class="text-right px-4 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody id="tableBody" class="divide-y"></tbody>
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
  let currentPage = 1;
  let t = null;
  function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(() => loadData(1), 800); }
  function setActivoSwitch(val) {
    const active = String(val) === '1';
    $('#activo').val(active ? '1' : '0');
    $('#activoLabel').text(active ? 'Activa' : 'Inactiva');
    $('#activoSwitch').attr('aria-pressed', active ? 'true' : 'false')
      .toggleClass('bg-teal-600', active)
      .toggleClass('bg-gray-300', !active);
    $('#activoKnob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
  }

  function renderPagination(total_pages, current) {
    let ht = '';
    if (total_pages <= 1) { $('#pagination').empty(); return; }
    
    for (let i = 1; i <= total_pages; i++) {
        ht += `<button onclick="loadData(${i})" class="px-3 py-1 rounded ${i === current ? 'bg-teal-600 text-white' : 'border'}">${i}</button>`;
    }
    $('#pagination').html(ht);
  }

  function loadData(page = 1) {
    currentPage = page;
    const per = $('#selLimit').val();
    const search = $('#txtSearch').val().trim();
    const status = $('#filterStatus').val();

    $.get('<?= app_url('api/admin/sucursales.php') ?>', { action: 'list', page: currentPage, per: per, search: search, status: status }, function (res) {
      if(!res.success) return;
      const tbody = $('#tableBody').empty();
      if (res.data.length > 0) {
        res.data.forEach(item => {
          let badge = parseInt(item.activo) === 1
            ? `<span class="inline-flex px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Activo</span>`
            : `<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Inactivo</span>`;

          let h = {};
          let horText = '-';
          try {
            h = JSON.parse(item.horarios_json) || {};
            if (h.texto) {
              horText = h.texto;
            } else {
              let parts = [];
              for (let key in h) {
                if (key !== 'texto' && h[key] && h[key].inicio && h[key].fin) {
                  let dias = key;
                  if (key === 'lun-vie') dias = 'Lun-Vie';
                  else if (key === 'sab') dias = 'Sáb';
                  else if (key === 'dom') dias = 'Dom';
                  parts.push(`${dias}: ${h[key].inicio} - ${h[key].fin}`);
                }
              }
              if (parts.length > 0) horText = parts.join(', ');
              else if (item.horarios_json && item.horarios_json !== '{}') horText = item.horarios_json;
            }
          } catch (e) {
            if (item.horarios_json) horText = item.horarios_json;
          }

          tbody.append(`
          <tr class="hover:bg-gray-50 border-b last:border-0 border-gray-100">
            <td class="px-4 py-3">
                <div class="font-medium text-gray-900">${item.nombre}</div>
                <div class="text-xs text-gray-500 font-mono">${item.slug || ''}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 truncate max-w-[150px]">${item.direccion || '-'}</td>
            <td class="px-4 py-3 text-xs text-gray-600">
                <div><i data-lucide="phone" class="text-gray-400 w-4"></i> ${item.telefono || '-'}</div>
                <div class="mt-1"><i data-lucide="clock" class="text-gray-400 w-4"></i> <span class="text-gray-500">${horText}</span></div>
            </td>
            <td class="px-4 py-3">${badge}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <button onclick="editItem(${item.id})" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar"><i data-lucide="pen"></i></button>
                <button onclick="deleteItem(${item.id})" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 deleteBtn" title="Eliminar"><i data-lucide="trash-2"></i></button>
              </div>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Total: ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="5" class="py-4 text-center text-gray-500">No hay sucursales registradas.</td></tr>');
        $('#pageInfo').text('Total: 0');
      }
      renderPagination(res.total_pages || Math.ceil(res.total / per), currentPage);
    }, 'json');
  }

  function resetForm() {
    $('#formSucursal')[0].reset();
    $('#sucursal_id').val(0);
    $('#btnSave').text('Guardar');
    setActivoSwitch('1');
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/sucursales.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#sucursal_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#slug').val(d.slug);
        $('#direccion').val(d.direccion);
        $('#telefono').val(d.telefono);

        let h = {};
        try { h = JSON.parse(d.horarios_json) || {}; } catch (e) { }
        $('#horario').val(h.texto || "");

        setActivoSwitch(d.activo || '0');
        $('#btnSave').text('Actualizar');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas eliminar definitivamente esta sucursal?", function () {
      $.post('<?= app_url('api/admin/sucursales.php') ?>', { action: 'delete', id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Sucursal eliminada.', 3000, 'info');
        } else {
          showCustomAlert(res.message || 'Error al eliminar', 5000, 'error');
        }
      }, 'json');
    });
  }

  $(function () {
    $('#activoSwitch').on('click', function () {
      setActivoSwitch($('#activo').val() === '1' ? '0' : '1');
    });

    $('#formSucursal').on('submit', function (e) {
      e.preventDefault();
      $.post('<?= app_url('api/admin/sucursales.php') ?>?action=save', $(this).serialize(), function (res) {
        if (res.success) {
          resetForm();
          loadData(currentPage);
          showCustomAlert('Sucursal guardada correctamente.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    $('#selLimit, #filterStatus').on('change', () => loadData(1));
    $('#txtSearch').on('keyup', debounceLoad);

    setActivoSwitch('1');
    loadData();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
