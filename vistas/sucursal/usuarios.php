<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'usuarios';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
$role = $user['rol'] ?? null;
$es_super = ($role === 'superadmin');
$empresa_id_user = $user['empresa_id'] ?? null;

if (!$user || (!$es_super && !in_array($role, ['superadmin', 'admin', 'gerente']))) {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}

if ($es_super) {
  $empresas = $pdo->query("SELECT id, nombre, slug FROM empresas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
  $sucursales = $pdo->query("SELECT id, empresa_id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $empresas = [];
  $stmt = $pdo->prepare("SELECT id, empresa_id, nombre FROM sucursales WHERE empresa_id = ? ORDER BY nombre ASC");
  $stmt->execute([$empresa_id_user]);
  $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">Empresa</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Usuarios</div>

        <form id="userForm" class="mt-4 space-y-3">
          <input type="hidden" id="userId" name="id">

          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input class="border rounded-lg p-2 w-full" id="nombre" name="nombre" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" class="border rounded-lg p-2 w-full" id="email" name="email" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input class="border rounded-lg p-2 w-full" id="telefono" name="telefono">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol</label>
              <select class="border rounded-lg p-2 w-full" id="rol" name="rol" required>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado">Empleado</option>
                <option value="cliente">Cliente</option>
              </select>
            </div>
            <input type="hidden" id="empresa_id" name="empresa_id" value="<?= $empresa_id_user ?>">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Sucursal</label>
              <select class="border rounded-lg p-2 w-full" id="sucursal_id" name="sucursal_id">
                <option value="">Sin sucursal</option>
                <?php foreach ($sucursales as $s): ?>
                  <option value="<?= (int) $s['id'] ?>" data-empresa="<?= (int) $s['empresa_id'] ?>">
                    <?= htmlspecialchars($s['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="hidden" id="activo" name="activo" value="1">
                <button type="button" id="activoUsuarioSwitch"
                  class="relative inline-flex h-6 w-11 items-center rounded-full bg-teal-600 transition-colors"
                  aria-pressed="true">
                  <span id="activoUsuarioKnob"
                    class="inline-block h-5 w-5 translate-x-5 rounded-full bg-white shadow transition-transform"></span>
                </button>
                <span id="activoUsuarioLabel" class="text-sm font-medium text-gray-700">Activo</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Password (opcional)</label>
            <input type="text" class="border rounded-lg p-2 w-full font-mono" id="password" name="password"
              placeholder="Dejar vacío para no cambiar">
          </div>

          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" id="btnReset" class="px-4 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnSubmit" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Crear</button>
          </div>
        </form>
      </div>
    </div>

    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border">
        <div class="p-5 border-b">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <div class="font-semibold text-gray-900">Listado</div>
              <div class="text-sm text-gray-500">Acciones: editar y eliminar.</div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input id="searchUser" type="text" placeholder="Buscar por nombre o email..."
              class="border rounded-lg p-2 md:col-span-2">
            <input type="hidden" id="fEmpresa" value="<?= $empresa_id_user ?>">
            <select id="fRol" class="border rounded-lg p-2">
              <option value="">Rol: todos</option>
              <option value="admin">Admin</option>
              <option value="gerente">Gerente</option>
              <option value="empleado">Empleado</option>
              <option value="cliente">Cliente</option>
            </select>
            <select id="fActivo" class="border rounded-lg p-2">
              <option value="">Estado: todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
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
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="nombre">Usuario <span
                    class="sort-ind" data-for="nombre"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="rol">Rol <span class="sort-ind"
                    data-for="rol"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="sucursal">Sucursal <span
                    class="sort-ind" data-for="sucursal"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="activo">Estado <span
                    class="sort-ind" data-for="activo"></span></th>
                <th class="text-right px-4 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody id="usersTable" class="divide-y"></tbody>
          </table>
        </div>

        <div class="p-4 border-t">
          <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_USUARIOS = <?= json_encode(app_url('api/sucursal/usuarios.php')) ?>;
    let page = 1, per = 10, search = '', empresa_id = parseInt($('#fEmpresa').val() || '0'), rol = '', activo = '';
    let sort = 'id', dir = 'desc';
    let t = null;
    function setUsuarioActivoSwitch(val) {
      const active = String(val) === '1';
      $('#activo').val(active ? '1' : '0');
      $('#activoUsuarioLabel').text(active ? 'Activo' : 'Inactivo');
      $('#activoUsuarioSwitch').attr('aria-pressed', active ? 'true' : 'false')
        .toggleClass('bg-teal-600', active)
        .toggleClass('bg-gray-300', !active);
      $('#activoUsuarioKnob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
    }

    function resetForm() {
      $('#userForm')[0].reset();
      $('#userId').val('');
      $('#btnSubmit').text('Crear');
      setUsuarioActivoSwitch('1');
      $('#rol').val('empleado');
      $('#sucursal_id').val('');
    }

    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadUsers, 1000); }

    function badgeActive(a) {
      return a == 1
        ? '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Activo</span>'
        : '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Inactivo</span>';
    }

    function loadUsers() {
      $.get(API_USUARIOS, { action: 'list', page: page, per: per, search: search, rol: rol, activo: activo, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $('#usersTable').empty();
        res.data.forEach(u => {
          tbody.append(`<tr class="hover:bg-gray-50">
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900">${u.nombre || ''}</div>
            <div class="text-xs text-gray-500">${u.email || ''}</div>
          </td>
          <td class="px-4 py-3">${u.rol || ''}</td>
          <td class="px-4 py-3">${u.sucursal_nombre || ''}</td>
          <td class="px-4 py-3">${badgeActive(u.activo)}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar" data-id="${u.id}"><i data-lucide="pen"></i></button>
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 deleteBtn" title="Desactivar" data-id="${u.id}"><i data-lucide="trash-2"></i></button>
            </div>
          </td>
        </tr>`);
        });
        $('#totalReg').text(`Total: ${res.total}`);
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
      const pag = $('#pagination').empty();
      for (let i = 1; i <= totalPages; i++) {
        pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page')); loadUsers(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val()); page = 1; loadUsers(); });
    $('#searchUser').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fRol').on('change', function () { rol = $(this).val(); page = 1; loadUsers(); });
    $('#fActivo').on('change', function () { activo = $(this).val(); page = 1; loadUsers(); });
    $('#btnReset').click(resetForm);

    $('table thead').on('click', 'th[data-sort]', function () {
      const nextSort = $(this).data('sort');
      if (sort === nextSort) {
        dir = (dir === 'asc') ? 'desc' : 'asc';
      } else {
        sort = nextSort;
        dir = 'asc';
      }
      page = 1;
      loadUsers();
    });

    $('#userForm').on('submit', function (ev) {
      ev.preventDefault();
      const payload = $(this).serialize() + '&action=save';
      $.post(API_USUARIOS, payload, function (res) {
        if (res.success) {
          resetForm();
          loadUsers();
        } else {
          alert(res.message || 'Error');
        }
      }, 'json');
    });

    $('#usersTable').on('click', '.editBtn', function () {
      const id = $(this).data('id');
      $.get(API_USUARIOS, { action: 'get', id: id }, function (res) {
        if (!res.success) return;
        const u = res.data;
        $('#userId').val(u.id);
        $('#nombre').val(u.nombre || '');
        $('#email').val(u.email || '');
        $('#telefono').val(u.telefono || '');
        $('#rol').val(u.rol || 'empleado');
        $('#sucursal_id').val(u.sucursal_id || '');
        setUsuarioActivoSwitch(parseInt(u.activo || 0) === 1 ? '1' : '0');
        $('#password').val('');
        $('#btnSubmit').text('Guardar');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 'json');
    });

    $('#usersTable').on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      if (!window.confirm('¿Desactivar este usuario?')) return;
      $.post(API_USUARIOS, { action: 'delete', id: id }, function (res) {
        if (res.success) loadUsers();
        else alert(res.message || 'No se pudo desactivar');
      }, 'json');
    });

    $('#activoUsuarioSwitch').on('click', function () {
      setUsuarioActivoSwitch($('#activo').val() === '1' ? '0' : '1');
    });

    resetForm();
    loadUsers();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
