<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = current_user();
$role = $user['rol'] ?? null;
$requested_id_e = request_id_e();
$resolved_id_e = resolve_private_empresa_id($user);
$id_e = $role === 'superadmin' ? $requested_id_e : $resolved_id_e;
if (!$user || !in_array($role, ['superadmin', 'admin'], true)) {
  http_response_code(403);
  echo 'No autorizado.';
  exit;
}
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['superadmin', 'admin']));
if (!$is_tenant_admin) {
  http_response_code(403);
  include __DIR__ . '/../../includes/errors/403.php';
  exit;
}
$module = 'sucursales';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
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

          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" id="telefono" name="telefono" class="mt-1 w-full border rounded-lg p-2" placeholder="Ej: +502 123456">
          </div>

          <input type="hidden" id="horarios_json" name="horarios_json" value="">
          <div class="rounded-xl border p-3 bg-gray-50 dark:bg-slate-950/30 dark:border-slate-800">
            <div class="text-sm font-semibold text-gray-800 dark:text-slate-100 mb-2">Horarios avanzados</div>
            <div id="horariosGrid" class="space-y-2">
              <?php
              $dias = [
                'lunes' => 'Lunes',
                'martes' => 'Martes',
                'miercoles' => 'Miércoles',
                'jueves' => 'Jueves',
                'viernes' => 'Viernes',
                'sabado' => 'Sábado',
                'domingo' => 'Domingo',
              ];
              foreach ($dias as $k => $lbl):
              ?>
                <div class="grid grid-cols-12 gap-2 items-center" data-dia="<?= $k ?>">
                  <div class="col-span-4 text-sm text-gray-700"><?= $lbl ?></div>
                  <div class="col-span-2">
                    <label class="inline-flex items-center cursor-pointer">
                      <input type="checkbox" class="day-active sr-only peer" checked>
                      <span class="px-2 py-1 text-xs rounded-lg border peer-checked:bg-teal-600 peer-checked:text-white">Activo</span>
                    </label>
                  </div>
                  <div class="col-span-3">
                    <input type="time" class="day-inicio border rounded-lg p-2 w-full" value="09:00">
                  </div>
                  <div class="col-span-3">
                    <input type="time" class="day-fin border rounded-lg p-2 w-full" value="18:00">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
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

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mostrar en Home Page</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="show_in_home" name="show_in_home" value="1" class="sr-only peer">
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full
                peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                peer-checked:bg-teal-600"></div>
              <span class="ml-3 text-sm font-medium text-gray-700">Incluir en sección de sucursales del inicio</span>
            </label>
          </div>

          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" onclick="resetForm()" class="px-2 py-2 border rounded-lg">Nuevo</button>
            <button type="button" id="btnAssignUsers" class="px-2 py-2 border rounded-lg">Asignar usuarios</button>
            <button type="submit" id="btnSave" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Guardar</button>
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

        <div class="flex-1 overflow-auto bg-gray-50 dark:bg-slate-950/30 rounded-lg border border-gray-100 dark:border-slate-800">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 dark:bg-slate-950/50 text-gray-700 dark:text-slate-300">
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

<div id="assignUsersModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border w-full max-w-3xl">
    <div class="p-4 border-b flex items-center justify-between">
      <div>
        <div class="font-semibold text-gray-900">Asignar Usuarios a Sucursal</div>
        <div class="text-xs text-gray-500">Solo se listan usuarios sin sucursal o ya asignados a esta sucursal.</div>
      </div>
      <button type="button" id="closeAssignUsersModal" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50 dark:hover:bg-slate-800 dark:border-slate-700"><i data-lucide="x"></i></button>
    </div>
    <div class="p-4">
      <div id="assignUsersBody" class="space-y-4 max-h-[60vh] overflow-auto"></div>
    </div>
    <div class="p-4 border-t dark:border-slate-800 flex items-center justify-end gap-2">
      <button type="button" id="cancelAssignUsers" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50 dark:hover:bg-slate-800 dark:border-slate-700">Cancelar</button>
      <button type="button" id="saveAssignUsers" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">Guardar</button>
    </div>
  </div>
</div>

<script>
  let currentPage = 1;
  let t = null;
  let assignUsersTemp = new Set();
  function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(() => loadData(1), 800); }
  function isDayActiveVal(v) {
    return !(v === false || v === 0 || v === '0' || v === null || typeof v === 'undefined');
  }
  function formatHorarioDisplay(raw) {
    try {
      const h = typeof raw === 'string' ? (JSON.parse(raw || '{}') || {}) : (raw || {});
      const orderDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
      const dayLabel = {
        lunes: 'Lun',
        martes: 'Mar',
        miercoles: 'Mié',
        jueves: 'Jue',
        viernes: 'Vie',
        sabado: 'Sáb',
        domingo: 'Dom'
      };

      const active = orderDays
        .filter(d => h[d] && isDayActiveVal(h[d].activo) && h[d].inicio && h[d].fin)
        .map(d => ({ day: d, inicio: h[d].inicio, fin: h[d].fin }));

      if (!active.length) {
        return (h.texto && String(h.texto).trim()) ? String(h.texto).trim() : '-';
      }

      const allSame = active.length === 7 && active.every(v => v.inicio === active[0].inicio && v.fin === active[0].fin);
      if (allSame) {
        return `Todos los días: ${active[0].inicio} - ${active[0].fin}`;
      }

      // Agrupar días consecutivos con mismo horario (ej. Lun-Vie).
      const groups = [];
      let cur = null;
      for (const d of orderDays) {
        const row = h[d];
        const on = row && isDayActiveVal(row.activo) && row.inicio && row.fin;
        if (!on) {
          if (cur) { groups.push(cur); cur = null; }
          continue;
        }
        const range = `${row.inicio} - ${row.fin}`;
        if (!cur) {
          cur = { start: d, end: d, range };
        } else if (cur.range === range) {
          cur.end = d;
        } else {
          groups.push(cur);
          cur = { start: d, end: d, range };
        }
      }
      if (cur) groups.push(cur);
      if (!groups.length) {
        return '-';
      }

      return groups.map(g => {
        const d1 = dayLabel[g.start] || g.start;
        const d2 = dayLabel[g.end] || g.end;
        const ds = g.start === g.end ? d1 : `${d1}-${d2}`;
        return `${ds}: ${g.range}`;
      }).join(', ');
    } catch (e) {
      const txt = String(raw || '').trim();
      return txt !== '' ? txt : '-';
    }
  }
  function setActivoSwitch(val) {
    const active = String(val) === '1';
    $('#activo').val(active ? '1' : '0');
    $('#activoLabel').text(active ? 'Activa' : 'Inactiva');
    $('#activoSwitch').attr('aria-pressed', active ? 'true' : 'false')
      .toggleClass('bg-teal-600', active)
      .toggleClass('bg-gray-300', !active);
    $('#activoKnob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
  }

  function getHorariosFromUI() {
    const out = {};
    $('#horariosGrid [data-dia]').each(function() {
      const dia = $(this).data('dia');
      out[dia] = {
        activo: $(this).find('.day-active').is(':checked'),
        inicio: $(this).find('.day-inicio').val() || '09:00',
        fin: $(this).find('.day-fin').val() || '18:00'
      };
    });
    return out;
  }
  function setHorariosToUI(h) {
    const data = h || {};
    $('#horariosGrid [data-dia]').each(function() {
      const dia = $(this).data('dia');
      const row = data[dia] || {};
      const hasExplicit = Object.prototype.hasOwnProperty.call(data, dia) && typeof row === 'object';
      const active = hasExplicit ? isDayActiveVal(row.activo) : true;
      $(this).find('.day-active').prop('checked', active);
      $(this).find('.day-inicio').val(row.inicio || '09:00');
      $(this).find('.day-fin').val(row.fin || '18:00');
    });
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

          const horText = formatHorarioDisplay(item.horarios_json);

          tbody.append(`
          <tr class="hover:bg-gray-50 border-b last:border-0 border-gray-100">
            <td class="px-4 py-3">
                <div class="font-medium text-gray-900">${item.nombre}</div>
                <div class="text-xs text-gray-500">Empleados: ${parseInt(item.empleados_count || 0, 10)}</div>
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
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function resetForm() {
    $('#formSucursal')[0].reset();
    $('#sucursal_id').val(0);
    $('#btnAssignUsers').attr('data-sucursal-id', '0');
    $('#btnSave').text('Guardar');
    setActivoSwitch('1');
    $('#show_in_home').prop('checked', false);
    setHorariosToUI({});
    $('#horarios_json').val(JSON.stringify(getHorariosFromUI()));
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/sucursales.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#sucursal_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#direccion').val(d.direccion);
        $('#telefono').val(d.telefono);

        let h = {};
        try { h = JSON.parse(d.horarios_json) || {}; } catch (e) { }
        setHorariosToUI(h);
        $('#horarios_json').val(JSON.stringify(getHorariosFromUI()));

        setActivoSwitch(d.activo || '0');
        $('#show_in_home').prop('checked', parseInt(d.show_in_home || 0, 10) === 1);
        $('#btnSave').text('Actualizar');
        $('#btnAssignUsers').attr('data-sucursal-id', d.id);
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
    function renderAssignUsers(groups) {
      const box = $('#assignUsersBody').empty();
      const roles = ['admin', 'gerente', 'empleado'];
      roles.forEach(r => {
        const rows = (groups[r] || []);
        if (!rows.length) return;
        const wrap = $('<div class="bg-gray-50 border rounded-xl p-3"></div>');
        wrap.append(`<div class="text-sm font-semibold text-gray-800 mb-2 uppercase">${r}</div>`);
        const grid = $('<div class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>');
        rows.forEach(u => {
          const checked = assignUsersTemp.has(String(u.id)) ? 'checked' : '';
          grid.append(`<label class="flex items-center gap-2 border rounded-lg px-3 py-2 bg-white hover:bg-gray-50"><input type="checkbox" class="assign-user-check h-4 w-4 accent-teal-600" value="${u.id}" ${checked}><span class="text-sm text-gray-700">${u.nombre}</span></label>`);
        });
        wrap.append(grid);
        box.append(wrap);
      });
    }

    async function openAssignUsersModal() {
      const sid = parseInt($('#sucursal_id').val() || $('#btnAssignUsers').attr('data-sucursal-id') || '0', 10);
      if (sid <= 0) {
        showCustomAlert('Primero guarda o selecciona una sucursal.', 4000, 'warning');
        return;
      }
      $.get('<?= app_url('api/admin/sucursales.php') ?>', { action: 'get_assign_users', sucursal_id: sid }, function (res) {
        if (!res || !res.success) {
          showCustomAlert((res && res.message) || 'No se pudieron cargar usuarios.', 4000, 'error');
          return;
        }
        assignUsersTemp = new Set((res.data || []).filter(u => parseInt(u.sucursal_id || 0, 10) === sid).map(u => String(u.id)));
        const groups = { admin: [], gerente: [], empleado: [] };
        (res.data || []).forEach(u => {
          if (groups[u.rol]) groups[u.rol].push(u);
        });
        renderAssignUsers(groups);
        $('#assignUsersModal').removeClass('hidden').addClass('flex');
        if (window.lucide) lucide.createIcons();
      }, 'json');
    }

    function closeAssignUsersModal() {
      $('#assignUsersModal').addClass('hidden').removeClass('flex');
    }

    $('#btnAssignUsers').on('click', openAssignUsersModal);
    $('#closeAssignUsersModal, #cancelAssignUsers').on('click', closeAssignUsersModal);
    $('#assignUsersModal').on('click', function (e) { if (e.target === this) closeAssignUsersModal(); });
    $('#assignUsersBody').on('change', '.assign-user-check', function () {
      const v = String($(this).val());
      if ($(this).is(':checked')) assignUsersTemp.add(v);
      else assignUsersTemp.delete(v);
    });
    $('#saveAssignUsers').on('click', function () {
      const sid = parseInt($('#sucursal_id').val() || $('#btnAssignUsers').attr('data-sucursal-id') || '0', 10);
      if (sid <= 0) return;
      $.post('<?= app_url('api/admin/sucursales.php') ?>', {
        action: 'save_assign_users',
        sucursal_id: sid,
        user_ids: Array.from(assignUsersTemp)
      }, function (res) {
        if (res && res.success) {
          showCustomAlert('Usuarios asignados correctamente.', 3000, 'success');
          closeAssignUsersModal();
          loadData(currentPage);
        } else {
          showCustomAlert((res && res.message) || 'No se pudo guardar.', 5000, 'error');
        }
      }, 'json');
    });

    $('#activoSwitch').on('click', function () {
      setActivoSwitch($('#activo').val() === '1' ? '0' : '1');
    });

    $('#formSucursal').on('submit', function (e) {
      e.preventDefault();
      $('#horarios_json').val(JSON.stringify(getHorariosFromUI()));
      const data = $(this).serializeArray();
      if (!$('#show_in_home').is(':checked')) data.push({ name: 'show_in_home', value: '0' });
      $.post('<?= app_url('api/admin/sucursales.php') ?>?action=save', data, function (res) {
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
    setHorariosToUI({});
    $('#horarios_json').val(JSON.stringify(getHorariosFromUI()));
    loadData();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
