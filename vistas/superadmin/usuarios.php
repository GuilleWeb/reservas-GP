<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$empresas = $pdo->query("SELECT id, nombre, slug FROM empresas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$sucursales = $pdo->query("SELECT id, empresa_id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$module = 'usuarios';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- Lado Izquierdo: Formulario -->
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">SuperAdmin</div>
        <div class="text-2xl font-extrabold text-gray-900 mb-6">Gestionar Usuarios</div>

        <form id="userForm" class="space-y-4">
          <input type="hidden" id="userId" name="id" value="0">

          <div id="adminFields">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Nombre
                  Completo</label>
                <input type="text" id="nombre" name="nombre"
                  class="w-full border rounded-xl p-3 focus:ring-2 focus:ring-indigo-500" required>
              </div>
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Email</label>
                <input type="email" id="admin_email" name="email"
                  class="w-full border rounded-xl p-3 focus:ring-2 focus:ring-indigo-500" required>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="telefono"
              name="telefono">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
              <select
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
                id="rol" name="rol" required>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado">Empleado</option>
                <option value="cliente">Cliente</option>
                <option value="superadmin">SuperAdmin</option>
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
            <label class="block text-sm font-medium text-gray-700">Empresa (Tenant)</label>
            <select class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" id="empresa_id"
              name="empresa_id">
              <option value="">Sin empresa (Global)</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="text" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm"
              id="password" name="password" placeholder="Dejar vacío para no cambiar">
          </div>

          <div class="pt-4 flex items-center justify-between border-t border-gray-100 mt-6">
            <button type="button" id="btnReset"
              class="text-sm text-gray-500 hover:text-gray-800 font-medium">Cancelar</button>
            <button type="submit" id="btnSubmit"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition transform hover:scale-[1.02]">Crear
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
              <h2 class="text-xl font-bold text-gray-900">Listado de Usuarios</h2>
              <p class="text-sm text-gray-500">Gestión global de usuarios y permisos.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <input id="searchUser" type="text" placeholder="Buscar..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 w-44">
              <select id="fEmpresa" class="border border-gray-300 rounded-lg px-2 py-2 text-sm max-w-[150px]">
                <option value="0">Empresa: Todas</option>
                <?php foreach ($empresas as $e): ?>
                  <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <select id="fRol" class="border border-gray-300 rounded-lg px-2 py-2 text-sm">
                <option value="">Rol: Todos</option>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado">Empleado</option>
                <option value="superadmin">SuperAdmin</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Tabla -->
        <div class="flex-1 overflow-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10 shadow-sm">
              <tr>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer" data-sort="nombre">
                  Usuario</th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer" data-sort="rol">Rol</th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider cursor-pointer" data-sort="empresa">E/S
                </th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-center">Estado</th>
                <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-right">Acciones</th>
              </tr>
            </thead>
            <tbody id="usersTable" class="divide-y divide-gray-100 bg-white">
            </tbody>
          </table>
        </div>

        <!-- Footer / Paginación -->
        <div class="p-4 border-t border-gray-50 bg-white">
          <div id="pagination" class="flex items-center justify-end space-x-1"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_URL = <?= json_encode(app_url('api/superadmin/usuarios.php')) ?>;
    let page = 1, per = 10, search = '', empresa_id = 0, rol = '', activo = '', sort = 'id', dir = 'desc';
    let t = null;

    function resetForm() {
      $('#userForm')[0].reset();
      $('#userId').val('0');
      $('#btnSubmit').text('Crear Usuario');
      $('#empresa_id').val('');
    }


    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadUsers, 500); }

    function loadUsers() {
      $.get(API_URL, { action: 'list_sa', page: page, per: per, search: search, empresa_id: empresa_id, rol: rol, activo: activo, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $('#usersTable').empty();
        res.data.forEach(u => {
          const badge = u.activo == 1
            ? '<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[10px] font-bold">ACTIVO</span>'
            : '<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-[10px] font-bold">INACTIVO</span>';

          tbody.append(`
          <tr class="hover:bg-indigo-50/20 transition-colors">
            <td class="px-6 py-4">
                <div class="text-sm font-bold text-gray-900">${u.nombre}</div>
                <div class="text-[11px] text-gray-400">${u.email}</div>
            </td>
            <td class="px-6 py-4 text-sm font-medium text-indigo-600 capitalize">${u.rol}</td>
            <td class="px-6 py-4">
                <div class="text-xs font-semibold text-gray-700">${u.empresa_nombre || 'GLOBAL'}</div>
                <div class="text-[10px] text-gray-400">${u.sucursal_nombre || ''}</div>
            </td>
            <td class="px-6 py-4 text-center">${badge}</td>
            <td class="px-6 py-4 text-right">
                <button class="text-indigo-600 hover:text-indigo-800 p-2 editBtn" data-id="${u.id}"><i class="fas fa-edit"></i></button>
                <button class="text-red-500 hover:text-red-700 p-2 deleteBtn" data-id="${u.id}"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        `);
        });
        renderPagination(res.total);
      }, 'json').fail(function (xhr) {
        console.error("Error cargando usuarios:", xhr.responseText);
        $('#usersTable').html('<tr><td colspan="5" class="p-10 text-center text-red-500 font-bold">Error al cargar datos. Revisa la consola.</td></tr>');
      });
    }

    function renderPagination(total) {
      const totalPages = Math.ceil(total / per) || 1;
      const pag = $('#pagination').empty();
      if (totalPages <= 1) return;
      for (let i = 1; i <= totalPages; i++) {
        pag.append(`<button class="h-8 w-8 rounded ${i === page ? 'bg-indigo-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page')); loadUsers(); });
    $('#searchUser').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fEmpresa').on('change', function () { empresa_id = parseInt($(this).val()); page = 1; loadUsers(); });
    $('#fRol').on('change', function () { rol = $(this).val(); page = 1; loadUsers(); });
    $('#btnReset').click(resetForm);

    $('#userForm').on('submit', function (ev) {
      ev.preventDefault();
      $.post(API_URL + '?action=save_sa', $(this).serialize(), function (res) {
        if (res.success) {
          resetForm();
          loadUsers();
          showCustomAlert(res.message || 'Usuario guardado correctamente', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error', 5000, 'error');
        }
      }, 'json');
    });

    $('#usersTable').on('click', '.editBtn', function () {
      const id = $(this).data('id');
      $.get(API_URL, { action: 'get_sa', id: id }, function (res) {
        if (!res.success) return;
        const u = res.data;
        $('#userId').val(u.id);
        $('#nombre').val(u.nombre);
        $('#email').val(u.email);
        $('#telefono').val(u.telefono || '');
        $('#rol').val(u.rol);
        $('#empresa_id').val(u.empresa_id || '');
        $('#activo').val(u.activo);
        $('#password').val('');
        $('#btnSubmit').text('Actualizar Usuario');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 'json');
    });

    $('#usersTable').on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      if (!confirm('¿Eliminar definitivamente?')) return;
      $.post(API_URL, { action: 'delete_sa', id: id }, function (res) {
        if (res.success) {
          loadUsers();
          showCustomAlert('Usuario eliminado', 3000, 'info');
        }
      }, 'json');
    });

    loadUsers();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>