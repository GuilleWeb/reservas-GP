<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'mensajes';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
// vistas/mensajes.php
$user = current_user();
$role = $user['rol'] ?? null;
if (!$user || $role !== 'superadmin') {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}

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
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?>
                  (<?= htmlspecialchars($e['slug']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="mt-1 text-xs text-gray-500">Si eliges empresa, se envía solo a admins de esa empresa.</div>
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
          <div id="sentInfo" class="hidden text-sm text-teal-700"></div>
        </form>
      </div>
    </div>

    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div class="p-5 border-b">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <div class="font-semibold text-gray-900">Listado</div>
              <div class="text-sm text-gray-500">Acciones: ver y actualizar estado.</div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            <input id="searchMsg" type="text" placeholder="Buscar..." class="border rounded-lg p-2 md:col-span-2">
            <select id="fEmpresa" class="border rounded-lg p-2">
              <option value="0">Empresa: todas</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?>
                  (<?= htmlspecialchars($e['slug']) ?>)</option>
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
          </div>
          <div class="mt-3 text-sm text-gray-600" id="totalReg"></div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="empresa">Empresa <span
                    class="sort-ind" data-for="empresa"></span></th>
                <th class="text-left px-4 py-3">Contacto</th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="asunto">Asunto <span
                    class="sort-ind" data-for="asunto"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="estado">Estado <span
                    class="sort-ind" data-for="estado"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="created_at">Fecha <span
                    class="sort-ind" data-for="created_at"></span></th>
                <th class="text-right px-4 py-3">Acciones</th>
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
      <div><span class="text-gray-500">Nombre:</span> <span id="mNombre" class="font-semibold"></span></div>
      <div><span class="text-gray-500">Email:</span> <span id="mEmail" class="font-mono"></span></div>
      <div><span class="text-gray-500">Teléfono:</span> <span id="mTel" class="font-mono"></span></div>
      <div><span class="text-gray-500">Asunto:</span> <span id="mAsunto" class="font-semibold"></span></div>
      <div class="pt-2">
        <div class="text-gray-500">Mensaje:</div>
        <div id="mCuerpo" class="mt-2 whitespace-pre-wrap border rounded-xl p-3 bg-gray-50"></div>
      </div>
      <div class="pt-2 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
        <div class="text-gray-500">Estado actual: <span id="mEstado" class="font-semibold text-gray-900"></span></div>
        <div class="flex gap-2">
          <button id="btnLeido" class="px-3 py-2 rounded-lg border">Marcar leído</button>
          <button id="btnArchivar" class="px-3 py-2 rounded-lg border">Archivar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_MENSAJES = <?= json_encode(app_url('api/superadmin/mensajes.php')) ?>;
    let page = 1, per = 10, search = '', estado = '', empresa_id = 0;
    let sort = 'id', dir = 'desc';
    let t = null;
    let currentId = null;

    function loadTargets() {
      const empresa = parseInt($('#empresa_broadcast').val() || '0');
      $.get(API_MENSAJES, { action: 'list_targets', empresa_id: empresa }, function (res) {
        if (!res.success) return;
        const sel = $('#para_usuario_id').empty();
        sel.append('<option value="0">Selecciona un admin</option>');
        res.data.forEach(u => {
          sel.append(`<option value="${u.id}">${u.nombre || ''} - ${u.email || ''}</option>`);
        });
      }, 'json');
    }

    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadMensajes, 1000); }

    function badge(st) {
      if (st === 'nuevo') return '<span class="px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Nuevo</span>';
      if (st === 'leido') return '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Leído</span>';
      if (st === 'archivado') return '<span class="px-2 py-1 rounded-full text-xs bg-yellow-50 text-yellow-800 border border-yellow-100">Archivado</span>';
      return st || '';
    }

    function loadMensajes() {
      $.get(API_MENSAJES, { action: 'list', page: page, per: per, search: search, estado: estado, empresa_id: empresa_id, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $("#mensajesTable").empty();
        res.data.forEach(m => {
          tbody.append(`<tr class="hover:bg-gray-50">
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900">${m.empresa_nombre}</div>
            <div class="text-xs text-gray-500">${m.id_e}</div>
          </td>
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900">${m.nombre || ''}</div>
            <div class="text-xs text-gray-500">${m.email || ''}</div>
          </td>
          <td class="px-4 py-3">${m.asunto || ''}</td>
          <td class="px-4 py-3">${badge(m.estado)}</td>
          <td class="px-4 py-3">${m.created_at || ''}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white viewBtn" title="Ver" data-id="${m.id}"><i data-lucide="eye"></i></button>
            </div>
          </td>
        </tr>`);
        });
        $("#totalReg").text(`Total: ${res.total}`);
        renderPagination(res.total);
        updateSortIndicators();
      }, 'json');
    }

    function updateSortIndicators() {
      $('.sort-ind').text('');
      const el = $(`.sort-ind[data-for="${sort}"]`);
      if (!el.length) return;
      el.text(dir === 'asc' ? '▲' : '▼');
    }

    function renderPagination(total) {
      const totalPages = Math.ceil(total / per) || 1;
      const pag = $("#pagination").empty();
      for (let i = 1; i <= totalPages; i++) {
        pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    function openModal() { $("#msgModal").removeClass('hidden'); }
    function closeModal() { $("#msgModal").addClass('hidden'); currentId = null; }

    function setEstado(st) {
      if (!currentId) return;
      $.post(API_MENSAJES, { action: 'set_estado', id: currentId, estado: st }, function (res) {
        if (res.success) {
          closeModal();
          loadMensajes();
          showCustomAlert('Estado actualizado', 3000, 'success');
        } else {
          showCustomAlert(res.message || 'Error', 5000, 'error');
        }
      }, 'json');
    }

    $("#pagination").on('click', 'button', function () { page = parseInt($(this).data('page')); loadMensajes(); });
    $("#perPage").on('change', function () { per = parseInt($(this).val()); page = 1; loadMensajes(); });
    $("#fEmpresa").on('change', function () { empresa_id = parseInt($(this).val()); page = 1; loadMensajes(); });
    $("#fEstado").on('change', function () { estado = $(this).val(); page = 1; loadMensajes(); });

    $('table thead').on('click', 'th[data-sort]', function () {
      const nextSort = $(this).data('sort');
      if (sort === nextSort) {
        dir = (dir === 'asc') ? 'desc' : 'asc';
      } else {
        sort = nextSort;
        dir = 'asc';
      }
      page = 1;
      loadMensajes();
    });
    $("#searchMsg").on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });

    $('#modo').on('change', function () {
      const v = $(this).val();
      if (v === 'one') {
        $('#targetWrap').removeClass('hidden');
        loadTargets();
      } else {
        $('#targetWrap').addClass('hidden');
      }
    });
    $('#empresa_broadcast').on('change', function () {
      if ($('#modo').val() === 'one') loadTargets();
    });
    $('#broadcastForm').on('submit', function (ev) {
      ev.preventDefault();
      $('#sentInfo').addClass('hidden').text('');
      $.post(API_MENSAJES, $(this).serialize() + '&action=send_interno', function (res) {
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
      $.get(API_MENSAJES, { action: 'get', id: id }, function (res) {
        if (!res.success) return;
        const m = res.data;
        currentId = m.id;
        $("#mEmpresa").text((m.empresa_nombre || '') + ' (' + (m.id_e || '') + ')');
        $("#mNombre").text(m.nombre || '');
        $("#mEmail").text(m.email || '');
        $("#mTel").text(m.telefono || '');
        $("#mAsunto").text(m.asunto || '');
        $("#mCuerpo").text(m.mensaje || '');
        $("#mEstado").text(m.estado || '');
        openModal();
      }, 'json');
    });

    $("#btnClose").click(closeModal);
    $("#btnLeido").click(function () { setEstado('leido'); });
    $("#btnArchivar").click(function () { setEstado('archivado'); });

    loadMensajes();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
