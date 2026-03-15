<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'equipo';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

  <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
    <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Miembro</div>

    <form id="formEquipo" class="space-y-4">
      <input type="hidden" id="miembro_id" name="id" value="0">

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Nombre <span class="text-red-500">*</span></label>
          <input type="text" id="nombre" name="nombre" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="Ej: Carlos Barbero">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Especialidad / Título</label>
          <input type="text" id="especialidad" name="especialidad" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ej: Barber Master">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Biografía Corta</label>
        <textarea id="bio" name="bio" rows="3"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Foto / Imagen</label>
        <input type="file" id="foto" name="foto"
          class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
      </div>

      <div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="visible_en_home" name="visible_en_home" value="1" class="sr-only peer" checked>
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700">Visible en Home</span>
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
      <h2 class="text-xl font-bold text-gray-800">Nuestro Equipo</h2>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Miembro</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Cargo</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500" id="pageInfo"></div>
      <div id="pagination" class="flex items-center space-x-1"></div>
  </div>
</div>
</div>

</div>
</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/equipo.php') ?>';
  let currentPage = 1;

  function loadData(page = 1) {
    currentPage = page;
    $.get(API_URL, { action: 'list', page: currentPage, per: 15 }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors">
            <td class="py-3 px-4 font-semibold text-gray-800 text-sm italic">${item.nombre}</td>
            <td class="py-3 px-4 text-sm text-gray-600">${item.especialidad || '-'}</td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i data-lucide="pen"></i></button>
                <button onclick="deleteItem(${item.id})" class="text-red-500 hover:text-red-700 bg-red-50 px-2.5 py-1.5 rounded-lg border border-red-200"><i data-lucide="trash-2"></i></button>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Total: ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="3" class="py-10 text-center text-gray-500">No hay registros.</td></tr>');
      }
    }, 'json');
  }

  function resetForm() {
    $('#formEquipo')[0].reset();
    $('#miembro_id').val(0);
    $('#btnSave').text('Guardar');
  }

  function editItem(id) {
    $.get(API_URL, { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#miembro_id').val(d.id);
        $('#nombre').val(d.nombre);
        $('#especialidad').val(d.especialidad);
        $('#bio').val(d.bio);
        $('#visible_en_home').prop('checked', parseInt(d.visible_en_home) === 1);
        $('#btnSave').text('Actualizar');
      }
    }, 'json');
  }

  function deleteItem(id) {
    if (!confirm("¿Deseas eliminar definitivamente este registro?")) return;
    $.post(API_URL, { action: 'delete', id: id }, function (res) {
      if (res.success) loadData(currentPage);
    }, 'json');
  }

  $(function () {
    $('#formEquipo').on('submit', function (e) {
      e.preventDefault();
      let fd = new FormData(this);
      if (!$("#visible_en_home").is(":checked")) fd.set('visible_en_home', '0');

      $.ajax({
        url: API_URL,
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (res) {
          if (res.success) {
            resetForm();
            loadData(currentPage);
          } else {
            alert(res.message || 'Error');
          }
        }
      });
    });
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>