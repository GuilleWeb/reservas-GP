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
                <label class="block text-sm font-medium text-gray-700">Nombre
                  Completo</label>
                <input type="text" id="nombre" name="nombre"
                  class="w-full border rounded-xl p-3 focus:ring-2 focus:ring-teal-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="admin_email" name="email"
                  class="w-full border rounded-xl p-3 focus:ring-2 focus:ring-teal-500" required>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" class="border rounded-lg p-2 w-full" id="telefono" name="telefono">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
              <select class="border rounded-lg p-2 w-full" id="rol" name="rol" required>
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="empleado">Empleado</option>
                <option value="cliente">Cliente</option>
                <option value="superadmin">SuperAdmin</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="hidden" id="activo" name="activo" value="1">
                <button type="button" id="activoSAUsuarioSwitch"
                  class="relative inline-flex h-6 w-11 items-center rounded-full bg-teal-600 transition-colors"
                  aria-pressed="true">
                  <span id="activoSAUsuarioKnob"
                    class="inline-block h-5 w-5 translate-x-5 rounded-full bg-white shadow transition-transform"></span>
                </button>
                <span id="activoSAUsuarioLabel" class="text-sm font-medium text-gray-700">Activo</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Empresa (Tenant)</label>
            <select class="border rounded-lg p-2 w-full" id="empresa_id" name="empresa_id">
              <option value="">Sin empresa (Global)</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="text" class="border rounded-lg p-2 w-full" id="password" name="password"
              placeholder="Dejar vacío para no cambiar">
          </div>

          <div class="pt-4 flex items-center justify-between border-t border-gray-100 mt-6">
            <button type="button" id="btnReset" class="px-2 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnSubmit" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Crear
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
            <select id="fEmpresa" class="border rounded-lg p-2">
              <option value="0">Empresa: todas</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="fRol" class="border rounded-lg p-2">
              <option value="">Rol: todos</option>
              <option value="admin">Admin</option>
              <option value="gerente">Gerente</option>
              <option value="empleado">Empleado</option>
              <option value="superadmin">SuperAdmin</option>
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
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 text-gray-700">
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

        <div class="p-4 border-t">
          <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
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
    function setSAUsuarioActivoSwitch(val) {
      const active = String(val) === '1';
      $('#activo').val(active ? '1' : '0');
      $('#activoSAUsuarioLabel').text(active ? 'Activo' : 'Inactivo');
      $('#activoSAUsuarioSwitch').attr('aria-pressed', active ? 'true' : 'false')
        .toggleClass('bg-teal-600', active)
        .toggleClass('bg-gray-300', !active);
      $('#activoSAUsuarioKnob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
    }

    function resetForm() {
      $('#userForm')[0].reset();
      $('#userId').val('0');
      $('#btnSubmit').text('Crear Usuario');
      $('#empresa_id').val('');
      setSAUsuarioActivoSwitch('1');
    }


    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadUsers, 500); }

    function loadUsers() {
      $.get(API_URL, { action: 'list_sa', page: page, per: per, search: search, empresa_id: empresa_id, rol: rol, activo: activo, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $('#usersTable').empty();
        res.data.forEach(u => {
          const badge = u.activo == 1
            ? '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Activo</span>'
            : '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Inactivo</span>';

          tbody.append(`
          <tr class="hover:bg-teal-50/20 transition-colors">
            <td class="px-6 py-4">
                <div class="text-sm font-bold text-gray-900">${u.nombre}</div>
                <div class="text-[11px] text-gray-400">${u.email}</div>
            </td>
            <td class="px-6 py-4 text-sm capitalize">${u.rol}</td>
            <td class="px-6 py-4">
                <div class="text-xs font-semibold text-gray-700">${u.empresa_nombre || 'GLOBAL'}</div>
                <div class="text-[10px] text-gray-400">${u.sucursal_nombre || ''}</div>
            </td>
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
        renderPagination(res.total);
      }, 'json').fail(function (xhr) {
        console.error("Error cargando usuarios:", xhr.responseText);
        $('#usersTable').html('<tr><td colspan="5" class="p-10 text-center text-red-500 font-bold">Error al cargar datos. Revisa la consola.</td></tr>');
      });
    }

    function renderPagination(total) {
      const totalPages = Math.ceil(total / per) || 1;
      const pag = $("#pagination").empty();
      for (let i = 1; i <= totalPages; i++) {
        pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-teal-600 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    $('#pagination').on('click', 'button', function () { page = parseInt($(this).data('page')); loadUsers(); });
    $('#perPage').on('change', function () { per = parseInt($(this).val() || '10'); page = 1; loadUsers(); });
    $('#searchUser').on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });
    $('#fEmpresa').on('change', function () { empresa_id = parseInt($(this).val()); page = 1; loadUsers(); });
    $('#fRol').on('change', function () { rol = $(this).val(); page = 1; loadUsers(); });
    $('#fActivo').on('change', function () { activo = $(this).val(); page = 1; loadUsers(); });
    $('#activoSAUsuarioSwitch').on('click', function () {
      setSAUsuarioActivoSwitch($('#activo').val() === '1' ? '0' : '1');
    });
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
        setSAUsuarioActivoSwitch(u.activo);
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

    setSAUsuarioActivoSwitch('1');
    loadUsers();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
