<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'mensajes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">Comunicación</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Mensajes</div>
        <form id="sendForm" class="mt-4 space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Enviar a</label>
            <select id="target_rol" name="target_rol" class="border rounded-lg p-2 w-full">
              <option value="gerente">Gerentes</option>
              <option value="empleado">Empleados</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Modo</label>
            <select id="modo" name="modo" class="border rounded-lg p-2 w-full">
              <option value="all">Todos</option>
              <option value="one">Usuario específico</option>
            </select>
          </div>
          <div id="targetWrap" class="hidden">
            <label class="block text-sm font-medium text-gray-700">Usuario destino</label>
            <select id="target_user_id" name="target_user_id" class="border rounded-lg p-2 w-full"></select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Título</label>
            <input name="titulo" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Mensaje</label>
            <textarea name="cuerpo" rows="5" class="border rounded-lg p-2 w-full" required></textarea>
          </div>
          <button type="submit" class="w-full px-3 py-2 rounded-lg bg-teal-600 text-white font-semibold">Enviar</button>
        </form>
      </div>
    </div>

    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div>
          <div class="font-semibold text-gray-900">Bandeja de mensajes</div>
          <div class="text-sm text-gray-500">Recibidos y enviados.</div>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
          <select id="fFolder" class="border rounded-lg p-2">
            <option value="inbox">Bandeja: recibidos</option>
            <option value="sent">Bandeja: enviados</option>
          </select>
          <input id="searchMsg" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
          <select id="fTipo" class="border rounded-lg p-2">
            <option value="">Tipo: todos</option>
            <option value="interno">Interno</option>
            <option value="externo">Externo</option>
          </select>
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
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Asunto</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Contacto / Destino</th>
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
      <div><span class="text-gray-500">Remitente / Destino:</span> <span id="mRemitente" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Fecha:</span> <span id="mFecha"></span></div>
      <div><span class="text-gray-500">Mensaje:</span></div>
      <div id="mCuerpo" class="border rounded-xl p-3 bg-gray-50 whitespace-pre-wrap"></div>
      <div class="pt-2 flex items-center justify-end gap-2">
        <button id="btnMarkRead" class="px-3 py-2 rounded-lg border">Marcar leído</button>
        <button id="btnArchive" class="px-3 py-2 rounded-lg border">Archivar</button>
        <button id="btnResend" class="px-3 py-2 rounded-lg bg-teal-600 text-white">Reenviar</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/admin/mensajes.php') . '?id_e=' . urlencode((string) request_id_e())) ?>;
    let page = 1, per = 10, search = '', estado = '', tipo = '', folder = 'inbox';
    let current = null;
    let timer = null;

    function debounceLoad() { if (timer) clearTimeout(timer); timer = setTimeout(loadInbox, 500); }
    function badge(s) {
      if (s === 'nuevo') return '<span class="px-2 py-0.5 rounded text-xs bg-teal-50 text-teal-700 border border-teal-100">Nuevo</span>';
      if (s === 'leido') return '<span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 border">Leído</span>';
      return '<span class="px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 border border-yellow-100">Archivado</span>';
    }
    function loadTargets() {
      const r = $('#target_rol').val();
      $.get(API, { action: 'list_targets', target_rol: r }, function (res) {
        const s = $('#target_user_id').empty();
        s.append('<option value="0">Selecciona usuario</option>');
        (res.data || []).forEach(u => s.append(`<option value="${u.id}">${u.nombre} (${u.email || ''})</option>`));
      }, 'json');
    }
    function loadInbox() {
      $.get(API, { action: 'list', page, per, search, estado, tipo, folder }, function (res) {
        if (!res || !res.success) return;
        $('#totalReg').text(`Total: ${res.total}`);
        const tb = $('#msgTable').empty();
        (res.data || []).forEach(m => {
          const tipoTxt = folder === 'sent' ? 'enviado' : (m.tipo || '');
          const contacto = folder === 'sent' ? ('Yo → ' + (m.destinatario || m.remitente || 'Destino')) : (m.remitente || '');
          tb.append(`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm">${tipoTxt}</td>
            <td class="px-4 py-3 text-sm font-semibold">${m.titulo || m.asunto || ''}</td>
            <td class="px-4 py-3 text-sm">${contacto}</td>
            <td class="px-4 py-3">${badge(m.estado || 'nuevo')}</td>
            <td class="px-4 py-3 text-xs text-gray-500">${m.created_at || ''}</td>
            <td class="px-4 py-3 text-right">
              <button class="h-8 w-8 grid place-items-center rounded-lg border viewBtn" data-id="${m.id}" data-tipo="${m.tipo || ''}">
                <i data-lucide="eye"></i>
              </button>
            </td>
          </tr>`);
        });
        const pag = $('#pagination').empty();
        const tp = Math.ceil((res.total || 0) / per) || 1;
        for (let i = 1; i <= tp; i++) pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
        if (window.lucide) lucide.createIcons();
      }, 'json');
    }

    $('#modo').on('change', function () {
      const one = $(this).val() === 'one';
      $('#targetWrap').toggleClass('hidden', !one);
      if (one) loadTargets();
    });
    $('#target_rol').on('change', function () { if ($('#modo').val() === 'one') loadTargets(); });
    $('#sendForm').on('submit', function (e) {
      e.preventDefault();
      $.post(API, $(this).serialize() + '&action=send', function (res) {
        if (res && res.success) {
          showCustomAlert(`Enviado a ${res.sent} usuario(s).`, 3500, 'success');
          $('#sendForm')[0].reset();
          $('#targetWrap').addClass('hidden');
        } else showCustomAlert((res && res.message) || 'No se pudo enviar', 4500, 'error');
      }, 'json');
    });

    $('#searchMsg').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fFolder').on('change', function () {
      folder = $(this).val() || 'inbox';
      page = 1;
      if (folder === 'sent') {
        $('#fTipo').val('').prop('disabled', true);
        tipo = '';
      } else {
        $('#fTipo').prop('disabled', false);
      }
      loadInbox();
    });
    $('#fTipo').on('change', function () { tipo = $(this).val(); page = 1; loadInbox(); });
    $('#fEstado').on('change', function () { estado = $(this).val(); page = 1; loadInbox(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '10', 10); page = 1; loadInbox(); });
    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page') || '1', 10); loadInbox(); });

    $('#msgTable').on('click', '.viewBtn', function () {
      const id = parseInt($(this).data('id') || '0', 10);
      const tipoV = String($(this).data('tipo') || '');
      $.get(API, { action: 'get', id, tipo: tipoV, folder }, function (res) {
        if (!res || !res.success) return;
        const d = res.data || {};
        current = { id, tipo: tipoV };
        $('#mTitulo').text(d.titulo || d.asunto || '');
        $('#mRemitente').text(folder === 'sent' ? ('Yo → ' + (d.remitente || 'Destino')) : (d.remitente || d.nombre || ''));
        $('#mFecha').text(d.created_at || '');
        $('#mCuerpo').text(d.cuerpo || d.mensaje || '');
        $('#msgModal').removeClass('hidden');
      }, 'json');
    });
    $('#btnCloseModal').on('click', () => $('#msgModal').addClass('hidden'));
    $('#msgModal').on('click', function (e) { if (e.target === this) $('#msgModal').addClass('hidden'); });
    $('#btnMarkRead').on('click', function () {
      if (!current || folder === 'sent') return;
      $.post(API, { action: 'set_estado', id: current.id, tipo: current.tipo, estado: 'leido' }, function () { $('#msgModal').addClass('hidden'); loadInbox(); }, 'json');
    });
    $('#btnArchive').on('click', function () {
      if (!current || folder === 'sent') return;
      $.post(API, { action: 'set_estado', id: current.id, tipo: current.tipo, estado: 'archivado' }, function () { $('#msgModal').addClass('hidden'); loadInbox(); }, 'json');
    });
    $('#btnResend').on('click', function () {
      const titulo = ($('#mTitulo').text() || '').trim();
      const cuerpo = ($('#mCuerpo').text() || '').trim();
      if (titulo) $('#sendForm [name="titulo"]').val(titulo);
      if (cuerpo) $('#sendForm [name="cuerpo"]').val(cuerpo);
      $('#msgModal').addClass('hidden');
      document.getElementById('sendForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
      showCustomAlert('Mensaje cargado para reenviar.', 3000, 'info');
    });

    loadInbox();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
