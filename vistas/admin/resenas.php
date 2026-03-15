<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'resenas';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
$id_e = request_id_e();
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['superadmin', 'admin'], true));

if (!$is_tenant_admin) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

  <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
    <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Empresa</div>
    <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Reseñas</div>

    <form id="formResena" class="space-y-4">
      <input type="hidden" id="resena_id" name="id" value="0">

      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre del Autor <span
            class="text-red-500">*</span></label>
        <input type="text" id="autor_nombre" name="autor_nombre"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="Ej: Juan Pérez">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Calificación (1 a 5) <span
            class="text-red-500">*</span></label>
        <input type="number" min="1" max="5" id="rating" name="rating"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required value="5">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Comentario <span class="text-red-500">*</span></label>
        <textarea id="comentario" name="comentario" rows="4"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" required
          placeholder="Excelente servicio..."></textarea>
      </div>

      <div class="space-y-3">
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="visible_en_home" name="visible_en_home" value="1" class="sr-only peer">
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700">Mostrar en el inicio (Landing)</span>
        </label>

        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer" checked>
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700">Activa</span>
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
      <div>
        <h2 class="text-xl font-bold text-gray-800">Testimonios</h2>
        <p class="text-sm text-gray-500">Solo reseñas marcadas como visibles (Y activas) saldrán en el Home Page.</p>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Autor</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Comentario</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Visible en Home</th>
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

    html += `<button onclick="loadData(${current - 1})" class="px-3 py-1 rounded-md border text-gray-600 disabled:opacity-50 hover:bg-gray-50"><i data-lucide="chevron-left"></i></button>`;
    for (let i = 1; i <= total_pages; i++) {
      let activeClass = i === current ? 'bg-teal-600 text-white font-medium shadow' : 'border bg-white text-gray-700 hover:bg-gray-50';
      if (i === 1 || i === total_pages || (i >= current - 1 && i <= current + 1)) {
        html += `<button onclick="loadData(${i})" class="px-3 py-1 rounded-md ${activeClass}">${i}</button>`;
      } else if (i === current - 2 || i === current + 2) html += `<span class="px-2 text-gray-400">...</span>`;
    }
    html += `<button onclick="loadData(${current + 1})" class="px-3 py-1 rounded-md border text-gray-600 hover:bg-gray-50"><i data-lucide="chevron-right"></i></button>`;
    $('#pagination').html(html);
  }

  function loadData(page = 1) {
    currentPage = page;
    $.get('<?= app_url('api/admin/resenas.php') ?>', { action: 'list', page: currentPage, per: 15 }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let stars = '';
          for (let i = 1; i <= 5; i++) {
            stars += i <= parseInt(item.rating) ? '<i data-lucide="star" class="text-yellow-500"></i>' : '<i data-lucide="star" class="text-yellow-300"></i>';
          }

          let visible = parseInt(item.visible_en_home) ? `<span class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded text-xs">Home Page</span>` : '';
          let status = parseInt(item.activo) === 0 ? `<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs ml-1">Inactiva</span>` : '';

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 font-semibold text-gray-800">
                ${item.autor_nombre} <div class="text-[0.6rem] text-gray-400 space-x-1">${stars}</div>
            </td>
            <td class="py-3 px-4 text-sm text-gray-600 truncate max-w-xs" title="${item.comentario}">${item.comentario}</td>
            <td class="py-3 px-4">${visible}${status}</td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i data-lucide="pen"></i></button>
                <button onclick="deleteItem(${item.id})" class="text-red-500 hover:text-red-700 bg-red-50 px-2.5 py-1.5 rounded-lg border border-red-200"><i data-lucide="trash-2"></i></button>
            </td>
          </tr>
        `);
        });
      } else {
        tbody.html('<tr><td colspan="4" class="py-10 text-center text-gray-500">No hay reseñas registradas.</td></tr>');
      }
      renderPagination(res.total_pages, currentPage);
    }, 'json');
  }

  function resetForm() {
    $('#formResena')[0].reset();
    $('#resena_id').val(0);
    $('#btnSave').text('Guardar');
    $('#activo').prop('checked', true);
    $('#visible_en_home').prop('checked', false);
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/resenas.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#resena_id').val(d.id);
        $('#autor_nombre').val(d.autor_nombre);
        $('#rating').val(d.rating);
        $('#comentario').val(d.comentario);
        $('#activo').prop('checked', parseInt(d.activo) === 1);
        $('#visible_en_home').prop('checked', parseInt(d.visible_en_home) === 1);
        $('#btnSave').text('Actualizar');
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas desactivar esta reseña?", function () {
      $.post('<?= app_url('api/admin/resenas.php') ?>', { action: 'delete', id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Reseña desactivada.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al desactivar', 5000, 'error');
        }
      }, 'json');
    });
  }

  $(function () {
    $('#formResena').on('submit', function (e) {
      e.preventDefault();
      let data = $(this).serializeArray();
      if (!$("#activo").is(":checked")) data.push({ name: 'activo', value: '0' });
      if (!$("#visible_en_home").is(":checked")) data.push({ name: 'visible_en_home', value: '0' });

      $.post('<?= app_url('api/admin/resenas.php') ?>?action=save', data, function (res) {
        if (res.success) {
          resetForm();
          loadData(currentPage);
          showCustomAlert('Reseña guardada correctamente.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    loadData();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
