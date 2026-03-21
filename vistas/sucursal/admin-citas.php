<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'admin-citas';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

  <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
    <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Empresa</div>
    <div class="text-xl font-extrabold text-gray-900 mb-6">Agendar Cita (Gerente)</div>

    <form id="formCita" class="space-y-4">
      <input type="hidden" id="cita_id" name="id" value="0">

      <div>
        <label class="block text-sm font-medium text-gray-700">Cliente (Nombre) <span
            class="text-red-500">*</span></label>
        <input type="text" id="cliente_nombre" name="cliente_nombre"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="Nombre del cliente">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Teléfono</label>
          <input type="text" id="cliente_telefono" name="cliente_telefono"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" id="cliente_email" name="cliente_email"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Sucursal <span class="text-red-500">*</span></label>
        <select id="sucursal_id" name="sucursal_id" class="mt-1 flex w-full border border-gray-300 rounded-lg px-3 py-2"
          required></select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Servicio <span class="text-red-500">*</span></label>
        <select id="servicio_id" name="servicio_id" class="mt-1 flex w-full border border-gray-300 rounded-lg px-3 py-2"
          required></select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Atendido por <span class="text-red-500">*</span></label>
        <select id="empleado_usuario_id" name="empleado_usuario_id"
          class="mt-1 flex w-full border border-gray-300 rounded-lg px-3 py-2" required></select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">F. Inicio <span class="text-red-500">*</span></label>
          <input type="datetime-local" id="inicio" name="inicio"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">F. Fin (Opcional)</label>
          <input type="datetime-local" id="fin" name="fin"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Estado de Cita</label>
        <select id="estado" name="estado" class="mt-1 flex w-full border border-gray-300 rounded-lg px-3 py-2">
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="cancelada">Cancelada</option>
          <option value="completada">Completada</option>
          <option value="no_asistio">No Asistió</option>
        </select>
      </div>

      <div id="formAlert" class="hidden rounded p-3 text-sm"></div>

      <div class="pt-4 flex items-center justify-between border-t border-gray-100">
        <button type="button" onclick="resetForm()"
          class="text-sm text-gray-500 hover:text-gray-800 border border-gray-300 rounded-lg px-2 py-2">Nuevo</button>
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar Cita</button>
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
        <input type="text" id="txtSearch" placeholder="Buscar por cliente o atención..."
          class="border rounded-lg p-2 md:col-span-2">
        <select id="filterStatus" class="border rounded-lg p-2">
          <option value="">Estado: todos</option>
          <option value="pendiente">Pendientes</option>
          <option value="confirmada">Confirmadas</option>
          <option value="cancelada">Canceladas</option>
          <option value="completada">Completadas</option>
        </select>
        <select id="selLimit" class="border rounded-lg p-2">
          <option value="10" selected>10</option>
          <option value="25">25</option>
        </select>
        <div id="pageInfo" class="text-sm text-gray-600 self-center md:col-span-2"></div>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Fecha / Hora</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Cliente / Servicio</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500"></div>
      <div class="flex items-center space-x-1" id="pagination"></div>
  </div>
</div>
</div>

</div>
</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/citas.php') ?>';
  let currentPage = 1;

  function populateSelect(id, items, valueField, textField) {
    const s = $(id).empty();
    s.append(new Option('Seleccione una opción', ''));
    if (items) items.forEach(itm => {
      s.append(new Option(itm[textField], itm[valueField]));
    });
  }

  function loadOptions() {
    $.get(API_URL, { action: 'get_options' }, function (res) {
      if (res.success) {
        populateSelect('#sucursal_id', res.sucursales, 'id', 'nombre');
        populateSelect('#servicio_id', res.servicios, 'id', 'nombre');
        populateSelect('#empleado_usuario_id', res.empleados, 'id', 'nombre');
      }
    });
  }

  function renderPagination(total_pages, current) {
    let html = '';
    if (total_pages <= 1) { $('#pagination').empty(); return; }

    html += `<button onclick="loadData(${current - 1})" class="px-3 py-1 rounded-md border ${current === 1 ? 'opacity-50 pointer-events-none' : ''}"><i data-lucide="chevron-left"></i></button>`;
    for (let i = 1; i <= total_pages; i++) {
      let active = i === current ? 'bg-teal-600 text-white shadow' : 'border hover:bg-gray-50 text-gray-700';
      if (i === 1 || i === total_pages || (i >= current - 1 && i <= current + 1)) {
        html += `<button onclick="loadData(${i})" class="px-3 py-1 rounded-md ${active}">${i}</button>`;
      } else if (i === current - 2 || i === current + 2) html += `<span class="px-2">...</span>`;
    }
    html += `<button onclick="loadData(${current + 1})" class="px-3 py-1 rounded-md border ${current === total_pages ? 'opacity-50 pointer-events-none' : ''}"><i data-lucide="chevron-right"></i></button>`;
    $('#pagination').html(html);
  }

  function getEstadoBadge(estado) {
    switch (estado) {
      case 'pendiente': return `<span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">Pendiente</span>`;
      case 'confirmada': return `<span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Confirmada</span>`;
      case 'cancelada': return `<span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Cancelada</span>`;
      case 'completada': return `<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Completada</span>`;
      case 'no_asistio': return `<span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded text-xs">No Asistió</span>`;
      default: return `<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs">${estado}</span>`;
    }
  }

  function loadData(page = 1) {
    currentPage = page;
    const per = parseInt($('#selLimit').val() || '10');
    const search = ($('#txtSearch').val() || '').trim();
    const estado = $('#filterStatus').val() || '';
    $.get(API_URL, { action: 'list', page: currentPage, per: per, search: search, estado: estado }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let badge = getEstadoBadge(item.estado);
          let d = new Date(item.inicio.replace(' ', 'T'));
          let dateStr = d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 font-semibold text-gray-800 text-sm">
                ${dateStr} <br><span class="text-xs text-gray-500 font-normal">Sede: ${item.sucursal_nombre || 'N/A'}</span>
            </td>
            <td class="py-3 px-4">
                <div class="text-sm font-semibold">${item.cliente_nombre || '-'}</div>
                <div class="text-xs text-gray-500">${item.servicio_nombre || '-'} por ${item.empleado_nombre || '-'}</div>
            </td>
            <td class="py-3 px-4">${badge}</td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i data-lucide="pen"></i></button>
                <button onclick="deleteItem(${item.id})" class="text-red-500 hover:text-red-700 bg-red-50 px-2.5 py-1.5 rounded-lg border border-red-200"><i data-lucide="trash-2"></i></button>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Mostrando ${res.data.length} de ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="4" class="py-10 text-center text-gray-500">No hay citas registradas.</td></tr>');
        $('#pageInfo').text('Mostrando 0 resultados');
      }
      renderPagination(res.total_pages, currentPage);
    }, 'json');
  }

  function resetForm() {
    $('#formCita')[0].reset();
    $('#cita_id').val(0);
    $('#formAlert').addClass('hidden');
    $('#btnSave').text('Guardar Cita');
  }

  function editItem(id) {
    $.get(API_URL, { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#cita_id').val(d.id);
        $('#cliente_nombre').val(d.cliente_nombre);
        $('#cliente_telefono').val(d.cliente_telefono);
        $('#cliente_email').val(d.cliente_email);
        $('#sucursal_id').val(d.sucursal_id);
        $('#servicio_id').val(d.servicio_id);
        $('#empleado_usuario_id').val(d.empleado_usuario_id);
        $('#inicio').val(d.inicio.replace(' ', 'T').substring(0, 16));
        if (d.fin) $('#fin').val(d.fin.replace(' ', 'T').substring(0, 16));
        $('#estado').val(d.estado);
        $('#btnSave').text('Actualizar Cita');
      }
    }, 'json');
  }

  function deleteItem(id) {
    if (!confirm("¿Deseas de cancelar/eliminar esta cita?")) return;
    $.post(API_URL, { action: 'delete', id: id }, function (res) {
      if (res.success) loadData(currentPage);
    }, 'json');
  }

  $(function () {
    loadOptions();
    $('#selLimit, #filterStatus').on('change', () => loadData(1));
    $('#txtSearch').on('keyup', function (e) { if (e.key === 'Enter') loadData(1); });
    $('#formCita').on('submit', function (e) {
      e.preventDefault();
      $.post(API_URL + '?action=save', $(this).serialize(), function (res) {
        if (res.success) {
          resetForm();
          loadData(currentPage);
        } else {
          alert(res.message || 'Error al agendar cita');
        }
      }, 'json');
    });
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
