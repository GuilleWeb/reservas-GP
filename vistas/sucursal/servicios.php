<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'servicios';
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
        <label class="block text-sm font-medium text-gray-700">Nombre del Servicio <span
            class="text-red-500">*</span></label>
        <input type="text" id="nombre" name="nombre" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2"
          required>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio ($) <span class="text-red-500">*</span></label>
          <input type="number" step="0.01" min="0" id="precio_base" name="precio_base"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500" required
            placeholder="Ej: 15.00">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Duración (min.) <span class="text-red-500">*</span></label>
          <input type="number" step="5" min="5" id="duracion_minutos" name="duracion_minutos"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500" required
            placeholder="Ej: 30">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Descripción Corta</label>
        <textarea id="descripcion" name="descripcion" rows="3"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked>
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700">Activo</span>
        </label>
      </div>

      <div class="pt-4 flex items-center justify-between border-t border-gray-100">
        <button type="button" onclick="resetForm()"
          class="text-sm text-gray-500 hover:text-gray-800 border border-gray-300 rounded-lg px-2 py-2">Nuevo</button>
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar</button>
      </div>
    </form>
  </div>
  </div>

  <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h2 class="text-xl font-bold text-gray-800">Servicios Disponibles</h2>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Servicio</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Precio</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Duración</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>
  </div>
</div>
</div>

</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/servicios.php') ?>';
  let currentPage = 1;

  function loadData(page = 1) {
    currentPage = page;
    $.get(API_URL, { action: 'list', page: currentPage, per: 15 }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors">
            <td class="py-3 px-4 font-semibold text-gray-800 text-sm">${item.nombre}</td>
            <td class="py-3 px-4 text-sm text-gray-700 font-mono">$${parseFloat(item.precio_base).toFixed(2)}</td>
            <td class="py-3 px-4 text-sm text-gray-600">${item.duracion_minutos} min</td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i data-lucide="pen"></i></button>
            </td>
          </tr>
        `);
        });
      } else {
        tbody.html('<tr><td colspan="4" class="py-10 text-center text-gray-500">No hay servicios.</td></tr>');
      }
    }, 'json');
  }

  function resetForm() {
    $('#formServicio')[0].reset();
    $('#servicio_id').val(0);
    $('#btnSave').text('Guardar');
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
        $('#btnSave').text('Actualizar');
      }
    }, 'json');
  }

  $(function () {
    $('#formServicio').on('submit', function (e) {
      e.preventDefault();
      let data = $(this).serializeArray();
      if (!$("#activo").is(":checked")) data.push({ name: 'activo', value: '0' });
      $.post(API_URL + '?action=save', data, function (res) {
        if (res.success) {
          resetForm();
          loadData(currentPage);
        } else {
          alert(res.message || 'Error');
        }
      }, 'json');
    });
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>