<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'movimientos';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow border p-5">
    <div class="font-semibold text-gray-900">Historial de movimientos</div>
    <div class="text-sm text-gray-500">Registro de acciones por usuario y módulo.</div>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-7 gap-3">
      <input id="search" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
      <input id="from" type="date" class="border rounded-lg p-2">
      <input id="to" type="date" class="border rounded-lg p-2">
      <input id="actorId" type="number" min="0" placeholder="Actor ID" class="border rounded-lg p-2">
      <select id="perPage" class="border rounded-lg p-2">
        <option value="20" selected>20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <div id="totalReg" class="text-sm text-gray-600 self-center"></div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100 mt-4">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Usuario</th>
            <th class="px-4 py-3">Tipo</th>
            <th class="px-4 py-3">Entidad</th>
            <th class="px-4 py-3">Descripción</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>

    <div class="p-4 border-t">
      <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/admin/movimientos.php')) ?>;
    let page = 1, per = 20, search = '', from = '', to = '', actor_id = 0;
    let t = null;
    function deb() { if (t) clearTimeout(t); t = setTimeout(() => load(1), 500); }
    function pagination(totalPages, current) {
      const box = $('#pagination').empty();
      totalPages = Math.max(1, totalPages || 1);
      for (let i = 1; i <= totalPages; i++) {
        box.append(`<button class="px-3 py-1 rounded ${i === current ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }
    function load(p = 1) {
      page = p;
      $.get(API, { action: 'list', page, per, search, from, to, actor_id }, function (res) {
        if (!res || !res.success) return;
        const tb = $('#tbody').empty();
        (res.data || []).forEach(r => {
          const actor = r.actor_nombre ? `${r.actor_nombre} (#${r.actor_usuario_id || '-'})` : `Sistema (#${r.actor_usuario_id || '-'})`;
          tb.append(`<tr class="border-b last:border-0 border-gray-100">
            <td class="px-4 py-3 text-xs font-mono text-gray-600">${r.created_at || ''}</td>
            <td class="px-4 py-3">${actor}</td>
            <td class="px-4 py-3">${r.tipo || ''}</td>
            <td class="px-4 py-3">${r.entidad || ''} #${parseInt(r.entidad_id || 0, 10)}</td>
            <td class="px-4 py-3 text-sm text-gray-700">${r.descripcion || ''}</td>
          </tr>`);
        });
        $('#totalReg').text(`Total: ${res.total || 0}`);
        pagination(res.total_pages || 1, page);
      }, 'json');
    }

    $('#search').on('keyup', function () { search = $(this).val().trim(); deb(); });
    $('#from').on('change', function () { from = $(this).val(); load(1); });
    $('#to').on('change', function () { to = $(this).val(); load(1); });
    $('#actorId').on('input', function () { actor_id = parseInt($(this).val() || '0', 10); deb(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '20', 10); load(1); });
    $('#pagination').on('click', 'button', function () { load(parseInt($(this).data('page') || '1', 10)); });
    load(1);
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

