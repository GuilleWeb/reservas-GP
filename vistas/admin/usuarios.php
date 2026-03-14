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
            <input type="text"
              class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 transition"
              id="nombre" name="nombre" required placeholder="Ej: Maria López">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email Académico/Personal <span
                class="text-red-500">*</span></label>
            <input type="email"
              class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 transition"
              id="email" name="email" required placeholder="ejemplo@correo.com">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="telefono"
              name="telefono" placeholder="Ej: +502 ...">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
              <select
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
                id="rol" name="rol" required>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado" selected>Empleado</option>
                <option value="cliente">Cliente</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado</label>
              <select class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="activo" name="activo">
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
              </select>
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
              class="bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition transform hover:scale-[1.02]">Crear
              Usuario</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lado Derecho: Listado -->
    <div class="lg:col-span-8">
      <div
        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-140px)] min-h-[600px]">

        <!-- Header del Listado -->
        <div class="p-6 border-b border-gray-50 bg-white sticky top-0 z-10">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h2 class="text-xl font-bold text-gray-900">Equipo y Clientes</h2>
              <p class="text-sm text-gray-500">Gestión de accesos y perfiles.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-300"></i>
                <input id="searchUser" type="text" placeholder="Buscar..."
                  class="pl-9 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 w-44">
              </div>
              <select id="fRol" class="border border-gray-300 rounded-lg px-2 py-2 text-sm">
                <option value="">Todos los Roles</option>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado">Empleado</option>
                <option value="cliente">Cliente</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Tabla -->
        <div class="flex-1 overflow-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10 shadow-sm">
              <tr>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer hover:text-teal-600"
                  data-sort="nombre">Nombre <i class="fas fa-sort ml-1 opacity-20"></i></th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer hover:text-teal-600"
                  data-sort="rol">Rol <i class="fas fa-sort ml-1 opacity-20"></i></th>
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

        <!-- Footer / Paginación -->
        <div class="p-4 border-t border-gray-50 bg-white">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-gray-400 font-medium" id="totalReg">Cargando...</div>
            <div id="pagination" class="flex items-center space-x-1"></div>
          </div>
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

    function resetForm() {
      $('#userForm')[0].reset();
      $('#userId').val('0');
      $('#btnSubmit').text('Crear Usuario');
      $('#sucursal_id').val('');
      $('#rol').val('empleado');
      $('#activo').val('1');
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
                <button class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-lg transition editBtn" data-id="${u.id}"><i class="fas fa-edit"></i></button>
                <button class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg transition deleteBtn" data-id="${u.id}"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        `);
        });
        $('#totalReg').text(`Mostrando ${res.data.length} de ${res.total} registros`);
        renderPagination(res.total);
      }, 'json');
    }

    function renderPagination(total) {
      const totalPages = Math.ceil(total / per) || 1;
      const pag = $('#pagination').empty();
      if (totalPages <= 1) return;

      for (let i = 1; i <= totalPages; i++) {
        let cls = i === page ? 'bg-teal-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100 border border-gray-200';
        pag.append(`<button class="h-8 w-8 rounded-md text-xs font-bold transition ${cls}" data-page="${i}">${i}</button>`);
      }
    }

    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page')); loadUsers(); });
    $('#searchUser').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fRol').on('change', function () { rol = $(this).val(); page = 1; loadUsers(); });
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
        $('#activo').val(u.activo);
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

    loadUsers();
  });
</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>