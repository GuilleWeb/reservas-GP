<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'sucursales';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

  <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
    <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Sucursal</div>

    <form id="formSucursal" class="space-y-4">
      <input type="hidden" id="sucursal_id" name="id" value="0">

      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre de la Sucursal <span
            class="text-red-500">*</span></label>
        <input type="text" id="nombre" name="nombre" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2"
          required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Dirección</label>
        <input type="text" id="direccion" name="direccion"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
        <input type="text" id="telefono" name="telefono"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Horario Visible</label>
        <input type="text" id="horario" name="horario"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 mt-1">
      </div>

      <div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked disabled>
          <div
            class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-teal-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700">Activo (Contacta al admin para cambiar)</span>
        </label>
      </div>

      <div class="pt-4 flex items-center justify-between border-t border-gray-100">
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-2 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar Cambios</button>
      </div>
    </form>
  </div>
  </div>

  <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h2 class="text-xl font-bold text-gray-800">Ubicaciones clínicas</h2>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Sucursal</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Dirección / Tel</th>
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
  const API_URL = '<?= app_url('api/sucursal/sucursales.php') ?>';
  function loadData() {
    $.get(API_URL, { action: 'list' }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 font-semibold text-gray-800">${item.nombre}</td>
            <td class="py-3 px-4 text-xs text-gray-600">
                <div>${item.direccion || '-'}</div>
                <div class="mt-1">${item.telefono || '-'}</div>
            </td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i data-lucide="pen"></i></button>
            </td>
          </tr>
        `);
        });
      } else {
        tbody.html('<tr><td colspan="3" class="py-8 text-center text-gray-500">No hay sucursales.</td></tr>');
      }
    }, 'json');
  }

  function editItem(id) {
    $.get(API_URL, { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        const d = res.data;
        $('#sucursal_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#direccion').val(d.direccion);
        $('#telefono').val(d.telefono);
        let h = {}; try { h = JSON.parse(d.horarios_json) || {}; } catch (e) { }
        $('#horario').val(h.texto || "");
        $('#activo').prop('checked', parseInt(d.activo) === 1);
      }
    }, 'json');
  }

  $(function () {
    $('#formSucursal').on('submit', function (e) {
      e.preventDefault();
      $.post(API_URL + '?action=save', $(this).serialize(), function (res) {
        if (res.success) {
          loadData();
          alert('Guardado correctamente');
        } else {
          alert(res.message || 'Error');
        }
      }, 'json');
    });
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>