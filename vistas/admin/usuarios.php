<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = current_user();
$role = $user['rol'] ?? null;
$id_e = request_id_e();
if (!$user || !in_array($role, ['superadmin', 'admin'], true)) {
  http_response_code(403);
  echo 'No autorizado.';
  exit;
}

$empresa_id_user = $user['empresa_id'] ?? null;
$stmt = $pdo->prepare("SELECT id, nombre FROM sucursales WHERE empresa_id = ? ORDER BY nombre ASC");
$stmt->execute([$empresa_id_user]);
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$module = 'usuarios';
include __DIR__ . '/../../includes/topbar.php';

?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- Lado Izquierdo: Formulario -->
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Administración</div>
        <div class="text-2xl font-extrabold text-gray-900 mb-6">Gestionar Usuario</div>

        <form id="userForm" class="space-y-4">
          <input type="hidden" id="userId" name="id" value="0">

          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre Completo <span
                class="text-red-500">*</span></label>
            <input type="text" class="border rounded-lg p-2 w-full transition" id="nombre" name="nombre" required
              placeholder="Ej: Maria López">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email Académico/Personal <span
                class="text-red-500">*</span></label>
            <input type="email" class="border rounded-lg p-2 w-full transition" id="email" name="email" required
              placeholder="ejemplo@correo.com">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="telefono"
              name="telefono" placeholder="Ej: +502 ...">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
              <select class="border rounded-lg p-2 w-full" id="rol" name="rol" required>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado" selected>Empleado</option>
                <option value="cliente">Cliente</option>
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
            <label class="block text-sm font-medium text-gray-700">Sucursal Asignada</label>
            <select class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="sucursal_id"
              name="sucursal_id">
              <option value="">Sin sucursal específica</option>
              <?php foreach ($sucursales as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="text" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm"
              id="password" name="password" placeholder="Dejar vacío para no cambiar">
            <p class="text-[10px] text-gray-400 mt-1">Si es un usuario nuevo y dejas el campo vacío, se generará una
              aleatoria.</p>
          </div>

          <div class="pt-4 flex items-center justify-between border-t border-gray-100 mt-6">
            <button type="button" id="btnReset"
              class="text-sm text-gray-500 hover:text-gray-800 font-medium px-2 py-1">Cancelar</button>
            <button type="submit" id="btnSubmit"
              class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition transform hover:scale-[1.02]">Crear
              Usuario</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lado Derecho: Listado -->
    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">

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
            <div id="totalReg" class="text-sm text-gray-600 self-center"></div>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer hover:text-teal-600"
                  data-sort="nombre">Nombre <i data-lucide="arrow-up-down" class="ml-1 opacity-20"></i></th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer hover:text-teal-600"
                  data-sort="rol">Rol <i data-lucide="arrow-up-down" class="ml-1 opacity-20"></i></th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Sucursal</th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-center">Estado</th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-right">Acciones</th>
              </tr>
            </thead>
            <tbody id="usersTable" class="divide-y divide-gray-100 bg-white">
              <!-- Se carga vía AJAX -->
            </tbody>
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
    const API_URL = '<?= app_url('api/admin/usuarios.php') ?>';
    let page = 1, per = 10, search = '', rol = '', activo = '', sort = 'id', dir = 'desc';
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
      $('#userId').val('0');
      $('#btnSubmit').text('Crear Usuario');
      $('#sucursal_id').val('');
      $('#rol').val('empleado');
      setUsuarioActivoSwitch('1');
    }

    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadUsers, 500); }

    function loadUsers() {
      $.get(API_URL, { action: 'list', page: page, per: per, search: search, rol: rol, activo: activo, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $('#usersTable').empty();
        res.data.forEach(u => {
          const badge = u.activo == 1
            ? '<span class="bg-green-50 text-green-700 px-2.5 py-1 rounded-full text-[10px] font-bold border border-green-100">ACTIVO</span>'
            : '<span class="bg-gray-50 text-gray-400 px-2.5 py-1 rounded-full text-[10px] font-bold border border-gray-100">INACTIVO</span>';

          tbody.append(`
          <tr class="hover:bg-teal-50/20 transition-colors group">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold text-xs mr-3">${u.nombre.charAt(0)}</div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">${u.nombre}</div>
                        <div class="text-[11px] text-gray-400">${u.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-600 capitalize">${u.rol}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${u.sucursal_nombre || '<span class="italic text-gray-300">Global</span>'}</td>
            <td class="px-6 py-4 text-center">${badge}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-center gap-1">  
                <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar" data-id="${u.id}"><i data-lucide="pen"></i></button>
                <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 deleteBtn" data-id="${u.id}"><i data-lucide="trash-2"></i></button>
              </div>
            </td>
          </tr>
        `);
        });
        $('#totalReg').text(`Total: ${res.total}`);
        renderPagination(res.total);
      }, 'json');
    }

    function renderPagination(total) {
      const totalPages = Math.ceil(total / per) || 1;
      const pag = $('#pagination').empty();
      if (totalPages <= 1) return;

      for (let i = 1; i <= totalPages; i++) {
        let cls = i === page ? 'bg-teal-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100 border border-gray-200';
        pag.append(`<button class="px-3 py-1 rounded-md text-xs font-bold transition ${cls}" data-page="${i}">${i}</button>`);
      }
    }

    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page')); loadUsers(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '10'); page = 1; loadUsers(); });
    $('#searchUser').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fRol').on('change', function () { rol = $(this).val(); page = 1; loadUsers(); });
    $('#fActivo').on('change', function () { activo = $(this).val(); page = 1; loadUsers(); });
    $('#activoUsuarioSwitch').on('click', function () {
      setUsuarioActivoSwitch($('#activo').val() === '1' ? '0' : '1');
    });
    $('#btnReset').click(resetForm);

    $('table thead').on('click', 'th[data-sort]', function () {
      const nextSort = $(this).data('sort');
      dir = (sort === nextSort && dir === 'asc') ? 'desc' : 'asc';
      sort = nextSort;
      page = 1;
      loadUsers();
    });

    $('#userForm').on('submit', function (ev) {
      ev.preventDefault();
      $.post(API_URL + '?action=save', $(this).serialize(), function (res) {
        if (res.success) {
          resetForm();
          loadUsers();
          showCustomAlert('Usuario guardado correctamente.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    $('#usersTable').on('click', '.editBtn', function () {
      const id = $(this).data('id');
      $.get(API_URL, { action: 'get', id: id }, function (res) {
        if (!res.success) return;
        const u = res.data;
        $('#userId').val(u.id);
        $('#nombre').val(u.nombre);
        $('#email').val(u.email);
        $('#telefono').val(u.telefono || '');
        $('#rol').val(u.rol);
        $('#sucursal_id').val(u.sucursal_id || '');
        setUsuarioActivoSwitch(u.activo);
        $('#password').val('');
        $('#btnSubmit').text('Actualizar Usuario');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 'json');
    });

    $('#usersTable').on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      if (!confirm('Deseas dar de baja a este usuario?')) return;
      $.post(API_URL, { action: 'delete', id: id }, function (res) {
        if (res.success) loadUsers();
      }, 'json');
    });

    setUsuarioActivoSwitch('1');
    loadUsers();
  });
</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>
