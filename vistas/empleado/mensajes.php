<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'mensajes';
include __DIR__ . '/../../includes/topbar.php';
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow border p-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <div class="font-semibold text-gray-900">Mensajes</div>
        <div class="text-sm text-gray-500">Bandeja de entrada (internos).</div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
      <input id="searchMsg" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
      <select id="fEstado" class="border rounded-lg p-2">
        <option value="">Estado: todos</option>
        <option value="nuevo">Nuevo</option>
        <option value="leido">Leído</option>
        <option value="archivado">Archivado</option>
      </select>
      <select id="perPage" class="border rounded-lg p-2">
        <option value="5">5</option>
        <option value="10" selected>10</option>
        <option value="20">20</option>
      </select>
      <div id="totalReg" class="text-sm text-gray-600 self-center"></div>
    </div>

    <div class="mt-4 flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Asunto</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Remitente</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Fecha</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="msgTable" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>
    <div class="p-4 border-t">
      <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
    </div>
  </div>
</div>

<div id="msgModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-2xl shadow p-6 w-full max-w-2xl">
    <div class="flex items-center justify-between">
      <div class="font-bold text-gray-900">Detalle</div>
      <button id="btnCloseModal" class="h-8 w-8 grid place-items-center border rounded-lg">×</button>
    </div>
    <div class="mt-3 text-sm space-y-2">
      <div><span class="text-gray-500">Asunto:</span> <span id="mTitulo" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Remitente:</span> <span id="mRemitente" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Fecha:</span> <span id="mFecha"></span></div>
      <div><span class="text-gray-500">Mensaje:</span></div>
      <div id="mCuerpo" class="border rounded-xl bg-gray-50 ql-editor"></div>
      <div class="pt-2 flex items-center justify-end gap-2">
        <button id="btnDelete" class="px-3 py-2 rounded-lg border text-red-600">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/empleado/mensajes.php') . '?id_e=' . urlencode((string) request_id_e())) ?>;
    let page = 1, per = 10, search = '', estado = '';
    let currentId = 0;
    let timer = null;

    function badge(s) {
      if (s === 'nuevo') return '<span class="px-2 py-0.5 rounded text-xs bg-teal-50 text-teal-700 border border-teal-100">Nuevo</span>';
      if (s === 'leido') return '<span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 border">Leído</span>';
      return '<span class="px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 border border-yellow-100">Archivado</span>';
    }

    function debounceLoad() {
      if (timer) clearTimeout(timer);
      timer = setTimeout(loadInbox, 500);
    }

    function loadInbox() {
      $.get(API, { action: 'list', page, per, search, estado }, function (res) {
        if (!res || !res.success) return;
        $('#totalReg').text(`Total: ${res.total || 0}`);
        const tb = $('#msgTable').empty();
        (res.data || []).forEach(m => {
          tb.append(`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-semibold">${m.titulo || ''}</td>
            <td class="px-4 py-3 text-sm">${m.remitente || ''}</td>
            <td class="px-4 py-3">${badge(m.estado || 'nuevo')}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${m.created_at || ''}</td>
            <td class="px-4 py-3 text-right">
              <button class="h-8 w-8 grid place-items-center rounded-lg border viewBtn" data-id="${m.id}">
                <i data-lucide="eye"></i>
              </button>
            </td>
          </tr>`);
        });
        const pag = $('#pagination').empty();
        const tp = Math.ceil((res.total || 0) / per) || 1;
        for (let i = 1; i <= tp; i++) {
          pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
        }
        if (window.lucide) lucide.createIcons();
      }, 'json');
    }

    $('#searchMsg').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fEstado').on('change', function () { estado = $(this).val(); page = 1; loadInbox(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '10', 10); page = 1; loadInbox(); });
    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page') || '1', 10); loadInbox(); });

    $('#msgTable').on('click', '.viewBtn', function () {
      const id = parseInt($(this).data('id') || '0', 10);
      $.get(API, { action: 'get', id }, function (res) {
        if (!res || !res.success) return;
        const d = res.data || {};
        currentId = id;
        $('#mTitulo').text(d.titulo || '');
        $('#mRemitente').text(d.remitente || '');
        $('#mFecha').text(d.created_at || '');
        $('#mCuerpo').html(d.cuerpo || '');
        $('#msgModal').removeClass('hidden');
        if (String(d.estado || '') === 'nuevo') {
          $.post(API, { action: 'set_estado', id: currentId, estado: 'leido' }, function () { loadInbox(); }, 'json');
        }
      }, 'json');
    });

    $('#btnCloseModal').on('click', function () { $('#msgModal').addClass('hidden'); });
    $('#msgModal').on('click', function (e) { if (e.target === this) $('#msgModal').addClass('hidden'); });
    $('#btnDelete').on('click', function () {
      if (!currentId) return;
      if (!confirm('¿Eliminar este mensaje?')) return;
      $.post(API, { action: 'set_estado', id: currentId, estado: 'eliminado' }, function () {
        $('#msgModal').addClass('hidden');
        loadInbox();
      }, 'json');
    });

    loadInbox();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
