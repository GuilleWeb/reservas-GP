<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'clientes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

  <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
    <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Empresa</div>
    <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Cliente</div>
    <div class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2">Los clientes se crean automáticamente por autogestión o registro de citas. Aquí solo puedes editar/inhabilitar existentes.</div>

    <form id="formCliente" class="space-y-4">
      <input type="hidden" id="cliente_id" name="id" value="0">

      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre Completo <span
            class="text-red-500">*</span></label>
        <input type="text" id="nombre" name="nombre"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500" required
          placeholder="Ej: Juan Pérez">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" id="email" name="email"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
            placeholder="ejemplo@correo.com">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Teléfono</label>
          <input type="text" id="telefono" name="telefono"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ej: +502 ...">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Notas / Antecedentes</label>
        <textarea id="notas" name="notas" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 mt-1" rows="3"
          placeholder="Información relevante..."></textarea>
      </div>

      <div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked>
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700" id="estadoLabel">Activo</span>
        </label>
      </div>

      <div id="formAlert" class="hidden rounded p-3 text-sm"></div>

      <div class="pt-4 flex items-center justify-between border-t border-gray-100">
        <button type="button" disabled
          class="text-sm text-gray-400 border border-gray-200 rounded-lg px-2 py-2 cursor-not-allowed">Nuevo</button>
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar Cliente</button>
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
        <select id="selLimit" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
          <option value="10">10 por pág</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
        <div class="relative">
          <i data-lucide="search" class="absolute left-3 top-2.5 text-gray-400"></i>
          <input type="text" id="txtSearch"
            class="pl-9 border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500 w-48"
            placeholder="Buscar cliente...">
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Cliente</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Contacto</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500" id="pageInfo"></div>
      <div class="flex items-center space-x-1" id="pagination"></div>
  </div>
</div>
</div>

</div>
</div>

<script>
  let currentPage = 1;

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

  function loadData(page = 1) {
    currentPage = page;
    const per = $('#selLimit').val();
    const search = $('#txtSearch').val().trim();

    $.get('<?= app_url('api/admin/clientes.php') ?>', { action: 'list', page: currentPage, per: per, search: search }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let badge = parseInt(item.activo) === 1
            ? `<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Activo</span>`
            : `<span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Inactivo</span>`;

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 font-semibold text-gray-800">
                ${item.nombre} <div class="text-xs text-gray-400 font-normal">Nacimiento: ${item.fecha_nacimiento || '-'}</div>
            </td>
            <td class="py-3 px-4 text-xs text-gray-600">
                <div><i data-lucide="phone" class="text-gray-400 w-4"></i> ${item.telefono || '-'}</div>
                <div class="mt-1"><i data-lucide="mail" class="text-gray-400 w-4"></i> ${item.email || '-'}</div>
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
        tbody.html('<tr><td colspan="4" class="py-8 text-center text-gray-500">No hay clientes registrados.</td></tr>');
        $('#pageInfo').text('Mostrando 0 resultados');
      }
      renderPagination(res.total_pages, currentPage);
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function resetForm() {
    $('#formCliente')[0].reset();
    $('#cliente_id').val(0);
    $('#formAlert').addClass('hidden');
    $('#btnSave').text('Guardar Cliente');
    $('#activo').prop('checked', true);
    $('#estadoLabel').text('Activo');
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/clientes.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#cliente_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#email').val(d.email);
        $('#telefono').val(d.telefono);
        $('#fecha_nacimiento').val(d.fecha_nacimiento);
        $('#notas').val(d.notas);

        const st = parseInt(d.activo) === 1;
        $('#activo').prop('checked', st);
        $('#estadoLabel').text(st ? 'Activo' : 'Inactivo');
        $('#btnSave').text('Actualizar Cliente');
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas dar de baja a este cliente?", function () {
      $.post('<?= app_url('api/admin/clientes.php') ?>', { action: 'delete', id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Cliente dado de baja.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al eliminar', 5000, 'error');
        }
      }, 'json');
    });
  }

  $(function () {
    $('#activo').on('change', function () { $('#estadoLabel').text(this.checked ? 'Activo' : 'Inactivo'); });

    $('#formCliente').on('submit', function (e) {
      e.preventDefault();
      if (parseInt($('#cliente_id').val() || '0', 10) === 0) {
        showCustomAlert('No se pueden crear clientes manualmente desde este módulo.', 5000, 'warning');
        return;
      }
      let data = $(this).serializeArray();
      if (!$("#activo").is(":checked")) data.push({ name: 'activo', value: '0' });

      $.post('<?= app_url('api/admin/clientes.php') ?>?action=save', data, function (res) {
        if (res.success) {
          resetForm();
          loadData(currentPage);
          showCustomAlert('Cliente guardado correctamente.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    $('#selLimit').on('change', () => loadData(1));
    $('#txtSearch').on('keyup', function (e) { if (e.key === 'Enter') loadData(1); });

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
