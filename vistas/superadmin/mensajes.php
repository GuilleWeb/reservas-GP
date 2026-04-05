<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'mensajes';
include __DIR__ . '/../../includes/topbar.php';
$empresas = $pdo->query('SELECT id, nombre, slug FROM empresas ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">SuperAdmin</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Mensajes</div>
        <form id="broadcastForm" class="mt-3 space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Enviar a</label>
            <select id="modo" name="modo" class="border rounded-lg p-2 w-full">
              <option value="all">Todos los admins</option>
              <option value="one">Un admin específico</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Empresa (opcional)</label>
            <select id="empresa_broadcast" name="empresa_id" class="border rounded-lg p-2 w-full">
              <option value="0">Todas</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars((string) $e['nombre']) ?> (<?= htmlspecialchars((string) $e['slug']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="targetWrap" class="hidden">
            <label class="block text-sm font-medium text-gray-700">Admin destino</label>
            <select id="para_usuario_id" name="para_usuario_id" class="border rounded-lg p-2 w-full"></select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Título</label>
            <input id="titulo" name="titulo" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Mensaje</label>
            <textarea id="cuerpo" name="cuerpo" class="border rounded-lg p-2 w-full" rows="6" required></textarea>
          </div>
          <button type="submit" class="w-full px-2 py-2 bg-teal-600 text-white rounded-lg">Enviar</button>
        </form>
      </div>
    </div>

    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div>
          <div class="font-semibold text-gray-900">Bandeja de mensajes</div>
          <div class="text-sm text-gray-500">Externos recibidos y enviados internos.</div>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
          <select id="fFolder" class="border rounded-lg p-2">
            <option value="inbox">Bandeja: recibidos</option>
            <option value="sent">Bandeja: enviados</option>
          </select>
          <input id="searchMsg" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
          <select id="fEmpresa" class="border rounded-lg p-2">
            <option value="0">Empresa: todas</option>
            <?php foreach ($empresas as $e): ?>
              <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars((string) $e['nombre']) ?></option>
            <?php endforeach; ?>
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
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Empresa</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Contacto / Destino</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Asunto</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Estado</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                <th class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
              </tr>
            </thead>
            <tbody id="mensajesTable" class="divide-y"></tbody>
          </table>
        </div>
        <div class="p-4 border-t">
          <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="msgModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-2xl shadow p-6 w-full max-w-2xl">
    <div class="flex items-center justify-between">
      <h3 class="text-xl font-bold text-gray-900">Detalle del mensaje</h3>
      <button type="button" id="btnClose" class="text-gray-500 hover:text-gray-900">✕</button>
    </div>
    <div class="mt-4 space-y-2 text-sm">
      <div><span class="text-gray-500">Empresa:</span> <span id="mEmpresa" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Contacto / Destino:</span> <span id="mNombre" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Asunto:</span> <span id="mAsunto" class="font-semibold"></span></div>
      <div class="pt-2">
        <div class="text-gray-500">Mensaje:</div>
        <div id="mCuerpo" class="mt-2 whitespace-pre-wrap border rounded-xl p-3 bg-gray-50"></div>
      </div>
      <div class="pt-2 flex gap-2 justify-end">
        <button id="btnLeido" class="px-3 py-2 rounded-lg border">Marcar leído</button>
        <button id="btnArchivar" class="px-3 py-2 rounded-lg border">Archivar</button>
        <button id="btnResend" class="px-3 py-2 rounded-lg bg-teal-600 text-white">Reenviar</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/superadmin/mensajes.php')) ?>;
    let page = 1, per = 10, search = '', estado = '', empresa_id = 0, folder = 'inbox';
    let currentId = 0;

    function badge(st) {
      if (st === 'nuevo' || st === 'enviado') return '<span class="px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Nuevo</span>';
      if (st === 'leido') return '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Leído</span>';
      if (st === 'archivado') return '<span class="px-2 py-1 rounded-full text-xs bg-yellow-50 text-yellow-800 border border-yellow-100">Archivado</span>';
      return st || '';
    }
    function loadTargets() {
      const empresa = parseInt($('#empresa_broadcast').val() || '0', 10);
      $.get(API, { action: 'list_targets', empresa_id: empresa }, function (res) {
        const sel = $('#para_usuario_id').empty();
        sel.append('<option value="0">Selecciona un admin</option>');
        (res.data || []).forEach(u => sel.append(`<option value="${u.id}">${u.nombre || ''} - ${u.email || ''}</option>`));
      }, 'json');
    }
    function loadMensajes() {
      $.get(API, { action: 'list', page, per, search, estado, empresa_id, folder }, function (res) {
        if (!res || !res.success) return;
        const tbody = $("#mensajesTable").empty();
        (res.data || []).forEach(m => {
          const contacto = folder === 'sent' ? ('Yo → ' + (m.nombre || 'Destino')) : (m.nombre || '');
          tbody.append(`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3"><div class="font-medium text-gray-900">${m.empresa_nombre || ''}</div><div class="text-xs text-gray-500">${m.id_e || ''}</div></td>
            <td class="px-4 py-3">${contacto}</td>
            <td class="px-4 py-3">${m.asunto || ''}</td>
            <td class="px-4 py-3">${badge(m.estado)}</td>
            <td class="px-4 py-3">${m.created_at || ''}</td>
            <td class="px-4 py-3"><div class="flex items-center justify-end gap-2"><button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white viewBtn" title="Ver" data-id="${m.id}"><i data-lucide="eye"></i></button></div></td>
          </tr>`);
        });
        $("#totalReg").text(`Total: ${res.total || 0}`);
        const totalPages = Math.ceil((res.total || 0) / per) || 1;
        const pag = $("#pagination").empty();
        for (let i = 1; i <= totalPages; i++) pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
        if (window.lucide) lucide.createIcons();
      }, 'json');
    }

    $("#pagination").on('click', 'button', function () { page = parseInt($(this).data('page'), 10); loadMensajes(); });
    $("#perPage").on('change', function () { per = parseInt($(this).val(), 10); page = 1; loadMensajes(); });
    $("#fEmpresa").on('change', function () { empresa_id = parseInt($(this).val(), 10); page = 1; loadMensajes(); });
    $("#fEstado").on('change', function () { estado = $(this).val(); page = 1; loadMensajes(); });
    $("#searchMsg").on('keyup', function () { search = $(this).val(); page = 1; loadMensajes(); });
    $("#fFolder").on('change', function () { folder = $(this).val() || 'inbox'; page = 1; loadMensajes(); });

    $('#modo').on('change', function () {
      const v = $(this).val();
      if (v === 'one') { $('#targetWrap').removeClass('hidden'); loadTargets(); } else { $('#targetWrap').addClass('hidden'); }
    });
    $('#empresa_broadcast').on('change', function () { if ($('#modo').val() === 'one') loadTargets(); });
    $('#broadcastForm').on('submit', function (ev) {
      ev.preventDefault();
      $.post(API, $(this).serialize() + '&action=send_interno', function (res) {
        if (res.success) {
          showCustomAlert(`Enviado a ${res.sent} admin(s).`, 5000, 'success');
          $('#broadcastForm')[0].reset();
          $('#targetWrap').addClass('hidden');
        } else {
          showCustomAlert(res.message || 'Error', 5000, 'error');
        }
      }, 'json');
    });

    $("#mensajesTable").on('click', '.viewBtn', function () {
      const id = $(this).data('id');
      $.get(API, { action: 'get', id, folder }, function (res) {
        if (!res || !res.success) return;
        const m = res.data || {};
        currentId = parseInt(m.id || id, 10);
        $("#mEmpresa").text((m.empresa_nombre || '') + ' (' + (m.id_e || '') + ')');
        $("#mNombre").text(folder === 'sent' ? ('Yo → ' + (m.nombre || m.remitente || 'Destino')) : (m.nombre || ''));
        $("#mAsunto").text(m.asunto || m.titulo || '');
        $("#mCuerpo").text(m.mensaje || m.cuerpo || '');
        $("#msgModal").removeClass('hidden');
      }, 'json');
    });

    $("#btnClose").click(() => $("#msgModal").addClass('hidden'));
    $("#btnLeido").click(function () {
      if (folder === 'sent') return;
      $.post(API, { action: 'set_estado', id: currentId, estado: 'leido' }, function () { $("#msgModal").addClass('hidden'); loadMensajes(); }, 'json');
    });
    $("#btnArchivar").click(function () {
      if (folder === 'sent') return;
      $.post(API, { action: 'set_estado', id: currentId, estado: 'archivado' }, function () { $("#msgModal").addClass('hidden'); loadMensajes(); }, 'json');
    });
    $("#btnResend").click(function () {
      const titulo = ($("#mAsunto").text() || '').trim();
      const cuerpo = ($("#mCuerpo").text() || '').trim();
      if (titulo) $('#titulo').val(titulo);
      if (cuerpo) $('#cuerpo').val(cuerpo);
      $("#msgModal").addClass('hidden');
      document.getElementById('broadcastForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
      showCustomAlert('Mensaje cargado para reenviar.', 3000, 'info');
    });

    loadMensajes();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
