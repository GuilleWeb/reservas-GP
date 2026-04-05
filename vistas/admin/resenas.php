<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'resenas';
include __DIR__ . '/../../includes/topbar.php';
$user = current_user();
$id_e = request_id_e();
$is_tenant_admin = ($user && $id_e && in_array($user['rol'] ?? null, ['superadmin', 'admin'], true));
if (!$is_tenant_admin) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow border p-5">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div>
        <div class="font-semibold text-gray-900">Listado</div>
        <div class="text-sm text-gray-500">Acciones: entrar, editar y eliminar.</div>
      </div>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
        <input id="txtSearch" type="text" placeholder="Buscar por autor o comentario..." class="border rounded-lg p-2 md:col-span-2">
        <select id="filterStatus" class="border rounded-lg p-2">
          <option value="">Estado: todos</option>
          <option value="1">Aprobadas</option>
          <option value="0">Rechazadas</option>
        </select>
        <select id="sortBy" class="border rounded-lg p-2">
          <option value="new">Más nuevas</option>
          <option value="old">Más antiguas</option>
          <option value="rating_desc">Rating desc</option>
          <option value="rating_asc">Rating asc</option>
        </select>
        <select id="selLimit" class="border rounded-lg p-2">
          <option value="10" selected>10</option>
          <option value="25">25</option>
        </select>
        <div id="pageInfo" class="text-sm text-gray-600 self-center"></div>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Autor</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Puntuación</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Comentario</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado / Home</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500" id="totalReg"></div>
      <div class="flex items-center space-x-1" id="pagination"></div>
    </div>
  </div>
</div>

<div id="resenaModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border w-full max-w-2xl p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900">Detalle de reseña</h3>
      <button type="button" id="btnCloseModal" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="x"></i></button>
    </div>
    <div id="modalInfo" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-4"></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="md:col-span-2">
        <label class="text-xs text-gray-600">Mostrar en Home Page</label>
        <label class="relative inline-flex items-center cursor-pointer mt-1">
          <input type="checkbox" id="mShowHome" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-teal-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
          <span class="ml-3 text-sm text-gray-700">Visible</span>
        </label>
      </div>
      <div>
        <label class="text-xs text-gray-600">Estado</label>
        <select id="mEstado" class="border rounded-lg p-2 w-full">
          <option value="1">Aprobada</option>
          <option value="0">Rechazada</option>
        </select>
      </div>
      <div class="text-xs text-gray-500 self-end">Una reseña aprobada solo puede cambiar de estado por superadmin.</div>
    </div>
    <div class="mt-4 pt-4 border-t flex justify-end">
      <button type="button" id="btnSaveModal" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">Guardar cambios</button>
    </div>
  </div>
</div>

<script>
  const ROLE = <?= json_encode($user['rol'] ?? '') ?>;
  let currentPage = 1;
  let modalItem = null;

  function renderPagination(totalPages, current) {
    const pag = $('#pagination').empty();
    if (!totalPages || totalPages <= 1) return;
    const prev = current <= 1 ? 'opacity-50 pointer-events-none' : '';
    const next = current >= totalPages ? 'opacity-50 pointer-events-none' : '';
    pag.append(`<button onclick="loadData(${current - 1})" class="px-3 py-1 rounded border ${prev}"><i data-lucide="chevron-left"></i></button>`);
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= current - 1 && i <= current + 1)) {
        const cls = i === current ? 'bg-teal-600 text-white' : 'border hover:bg-gray-50';
        pag.append(`<button onclick="loadData(${i})" class="px-3 py-1 rounded ${cls}">${i}</button>`);
      } else if (i === current - 2 || i === current + 2) pag.append('<span class="px-2 text-gray-400">...</span>');
    }
    pag.append(`<button onclick="loadData(${current + 1})" class="px-3 py-1 rounded border ${next}"><i data-lucide="chevron-right"></i></button>`);
    if (window.lucide) lucide.createIcons();
  }

  function loadData(page = 1) {
    currentPage = page;
    $.get('<?= app_url('api/admin/resenas.php') ?>', {
      action: 'list',
      page: currentPage,
      per: $('#selLimit').val() || 10,
      search: ($('#txtSearch').val() || '').trim(),
      status: $('#filterStatus').val() || '',
      order: $('#sortBy').val() || 'new'
    }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length) {
        res.data.forEach(item => {
          const activo = parseInt(item.activo || 0, 10) === 1;
          const enHome = parseInt(item.visible_en_home || 0, 10) === 1;
          const score = parseInt(item.rating || 0, 10) || 0;
          const comentario = String(item.comentario || '');
          const short = comentario.length > 120 ? comentario.substring(0, 120) + '...' : comentario;
          tbody.append(`
            <tr class="hover:bg-teal-50/30 transition-colors">
              <td class="py-3 px-4 font-semibold text-gray-800">${item.autor_nombre || '-'}</td>
              <td class="py-3 px-4"><span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800 border border-amber-200">${score}/5</span></td>
              <td class="py-3 px-4 text-sm text-gray-600 truncate max-w-md" title="${comentario.replace(/"/g, '&quot;')}">${short || '-'}</td>
              <td class="py-3 px-4">${activo ? '<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Aprobada</span>' : '<span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Rechazada</span>'} ${enHome ? '<span class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded text-xs ml-1">Home</span>' : ''}</td>
              <td class="py-3 px-4 text-right">
                <button onclick="openModalById(${item.id})" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50 text-blue-600" title="Editar">
                  <i data-lucide="pen"></i>
                </button>
              </td>
            </tr>
          `);
        });
        $('#pageInfo').text(`Mostrando ${res.data.length} de ${res.total || res.data.length}`);
      } else {
        tbody.html('<tr><td colspan="5" class="py-10 text-center text-gray-500">No hay reseñas registradas.</td></tr>');
        $('#pageInfo').text('Mostrando 0 resultados');
      }
      renderPagination(res.total_pages || 1, currentPage);
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function openModal(item) {
    modalItem = item || null;
    if (!modalItem) return;
    const info = $('#modalInfo').empty();
    const rows = [
      ['Autor', modalItem.autor_nombre || '-'],
      ['Puntuación', `${modalItem.rating || 0}/5`],
      ['Comentario', modalItem.comentario || '-']
    ];
    rows.forEach(r => info.append(`<div class="bg-gray-50 border rounded-lg p-2 md:col-span-${r[0] === 'Comentario' ? '2' : '1'}"><div class="text-xs text-gray-500">${r[0]}</div><div class="font-medium text-gray-800 whitespace-pre-wrap">${r[1]}</div></div>`));
    $('#mShowHome').prop('checked', parseInt(modalItem.visible_en_home || 0, 10) === 1);
    const approved = parseInt(modalItem.activo || 0, 10) === 1;
    $('#mEstado').val(approved ? '1' : '0');
    const canChangeEstado = !(approved && ROLE !== 'superadmin');
    $('#mEstado').prop('disabled', !canChangeEstado).toggleClass('bg-gray-100', !canChangeEstado);
    $('#resenaModal').removeClass('hidden').addClass('flex');
    if (window.lucide) lucide.createIcons();
  }

  function openModalById(id) {
    $.get('<?= app_url('api/admin/resenas.php') ?>', { action: 'get', id }, function (res) {
      if (res && res.success && res.data) {
        openModal(res.data);
      } else {
        showCustomAlert('No se pudo cargar la reseña.', 4000, 'error');
      }
    }, 'json');
  }

  function closeModal() {
    $('#resenaModal').addClass('hidden').removeClass('flex');
  }

  function saveModal() {
    if (!modalItem) return;
    $.post('<?= app_url('api/admin/resenas.php') ?>?action=save', {
      id: modalItem.id,
      activo: parseInt($('#mEstado').val() || '0', 10),
      show_in_home: $('#mShowHome').is(':checked') ? 1 : 0
    }, function (res) {
      if (res && res.success) {
        showCustomAlert('Reseña actualizada.', 3000, 'success');
        closeModal();
        loadData(currentPage);
      } else {
        showCustomAlert((res && res.message) || 'No se pudo actualizar.', 5000, 'error');
      }
    }, 'json');
  }

  $(function () {
    loadData();
    $('#txtSearch').on('keyup', function (e) { if (e.key === 'Enter') loadData(1); });
    $('#filterStatus,#sortBy,#selLimit').on('change', () => loadData(1));
    $('#btnCloseModal').on('click', closeModal);
    $('#resenaModal').on('click', function (e) { if (e.target === this) closeModal(); });
    $('#btnSaveModal').on('click', saveModal);
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
