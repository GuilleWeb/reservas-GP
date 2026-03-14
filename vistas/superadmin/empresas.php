<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'empresas';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
// vistas/superadmin.php
$user = current_user();
$role = $user['rol'] ?? null;
if (!$user || $role !== 'superadmin') {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}

$planes = $pdo->query("SELECT id, nombre FROM planes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
      <div class="bg-white rounded-2xl shadow p-5 border">
        <div class="text-sm text-gray-500">SuperAdmin</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Empresas</div>

        <!-- Debe existir este bloque para mostrar la contraseña -->
        <div id="credBox" class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <h4 class="font-bold text-green-800 mb-2">✅ Credenciales de acceso</h4>
          <p><strong>URL:</strong> <span id="credUrl" class="font-mono"></span></p>
          <p><strong>Email:</strong> <span id="credEmail"></span></p>
          <p><strong>Contraseña temporal:</strong> <span id="credPass"
              class="font-mono bg-yellow-100 px-2 py-1 rounded"></span></p>
          <p class="text-xs text-gray-600 mt-2">⚠️ Guarda esta contraseña, solo se muestra una vez.</p>
        </div>

        <form id="empresaForm" class="mt-4 space-y-3">
          <input type="hidden" id="empresaId" name="id">

          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input class="border rounded-lg p-2 w-full" id="nombre" name="nombre" required>
          </div>
          <!-- <div>
            <label class="block text-sm font-medium text-gray-700">Slug</label>
            <input class="border rounded-lg p-2 w-full font-mono" id="slug" name="slug" placeholder="barberiaeyg"
              required>
            <div class="mt-1 text-xs text-gray-500">Se usa en la URL: /{slug}/</div>
          </div> -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Plan</label>
              <select class="border rounded-lg p-2 w-full" id="plan_id" name="plan_id">
                <option value="">Sin plan</option>
                <?php foreach ($planes as $p): ?>
                  <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado</label>
              <select class="border rounded-lg p-2 w-full" id="activo_form" name="activo">
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
            </div>
          </div>
          <div id="adminFields" class="space-y-3">
            <div class="pt-2 border-t"></div>
            <div class="text-sm font-semibold text-gray-900">Admin inicial</div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Nombre</label>
              <input class="border rounded-lg p-2 w-full" id="admin_nombre" name="admin_nombre">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input type="email" class="border rounded-lg p-2 w-full" id="admin_email" name="admin_email">
            </div>
            <div class="text-xs text-gray-500">Estos campos se usan solo al crear una empresa.</div>
          </div>

          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" id="btnReset" class="px-4 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnSubmit" class="px-4 py-2 bg-gray-900 text-white rounded-lg">Crear</button>
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
              <div class="text-sm text-gray-500">Acciones: entrar, editar y eliminar.</div>
            </div>
            <div class="text-sm text-gray-500">Tip: /{slug}/dashboard</div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input id="searchEmpresa" type="text" placeholder="Buscar por nombre o slug..."
              class="border rounded-lg p-2 md:col-span-2">
            <select id="fPlan" class="border rounded-lg p-2">
              <option value="0">Plan: todos</option>
              <?php foreach ($planes as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="fActivo" class="border rounded-lg p-2">
              <option value="">Estado: todos</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
            <select id="perPage" class="border rounded-lg p-2">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="20">20</option>
            </select>
            <div id="totalReg" class="text-sm text-gray-600 self-center"></div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="nombre">Empresa <span
                    class="sort-ind" data-for="nombre"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="plan">Plan <span class="sort-ind"
                    data-for="plan"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="activo">Estado <span
                    class="sort-ind" data-for="activo"></span></th>
                <th class="text-left px-4 py-3 cursor-pointer select-none" data-sort="created_at">Creado <span
                    class="sort-ind" data-for="created_at"></span></th>
                <th class="text-right px-4 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody id="empresasTable" class="divide-y"></tbody>
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
    const API_EMPRESAS = <?= json_encode(app_url('api/superadmin/empresas.php')) ?>;
    let page = 1, per = 10, search = '', activo = '', plan_id = 0;
    let sort = 'id', dir = 'desc';
    let t = null;

    function resetForm() {
      $('#credBox').addClass('hidden');
      $('#empresaForm')[0].reset();
      $('#empresaId').val('');
      $('#activo_form').val('1');
      $('#btnSubmit').text('Crear');
      $('#adminFields').removeClass('opacity-50');
      $('#admin_nombre,#admin_email').prop('disabled', false);
    }

    function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(loadEmpresas, 1000); }

    function loadEmpresas() {
      $.get(API_EMPRESAS, { action: 'list', page: page, per: per, search: search, activo: activo, plan_id: plan_id, sort: sort, dir: dir }, function (res) {
        if (!res.success) return;
        const tbody = $("#empresasTable").empty();
        res.data.forEach(e => {
          const estado = e.activo == 1
            ? '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Activa</span>'
            : '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Inactiva</span>';
          tbody.append(`<tr class="hover:bg-gray-50">
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900">${e.nombre || ''}</div>
            <div class="text-xs text-gray-500 font-mono">${e.slug || ''}</div>
          </td>
          <td class="px-4 py-3">${e.plan_nombre || ''}</td>
          <td class="px-4 py-3">${estado}</td>
          <td class="px-4 py-3">${e.created_at || ''}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <a class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white" title="Entrar" href="<?= view_url('vistas/admin/dashboard.php') ?>?id_e=${e.id}"><i class="fas fa-arrow-right"></i></a>
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar" data-id="${e.id}"><i class="fas fa-pen"></i></button>
              <button class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 deleteBtn" title="Eliminar" data-id="${e.id}"><i class="fas fa-trash"></i></button>
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
        pag.append(`<button class="px-3 py-1 rounded ${i === page ? 'bg-gray-900 text-white' : 'border'}" data-page="${i}">${i}</button>`);
      }
    }

    $("#pagination").on('click', 'button', function () { page = parseInt($(this).data('page')); loadEmpresas(); });
    $("#perPage").on('change', function () { per = parseInt($(this).val()); page = 1; loadEmpresas(); });
    $("#fActivo").on('change', function () { activo = $(this).val(); page = 1; loadEmpresas(); });
    $("#fPlan").on('change', function () { plan_id = parseInt($(this).val() || '0'); page = 1; loadEmpresas(); });
    $("#searchEmpresa").on('keyup', function () { search = $(this).val(); page = 1; debounceLoad(); });

    $('table thead').on('click', 'th[data-sort]', function () {
      const nextSort = $(this).data('sort');
      if (sort === nextSort) {
        dir = (dir === 'asc') ? 'desc' : 'asc';
      } else {
        sort = nextSort;
        dir = 'asc';
      }
      page = 1;
      loadEmpresas();
    });

    $("#btnReset").click(resetForm);

    $("#empresaForm").submit(function (ev) {
      ev.preventDefault();
      const id = parseInt($('#empresaId').val() || '0');

      if (id > 0) {
        $.post(API_EMPRESAS, $(this).serialize() + '&action=save', function (res) {
          if (res.success) {
            resetForm();
            loadEmpresas();
            showCustomAlert('Empresa actualizada correctamente', 5000, 'success');
          } else {
            showCustomAlert(res.message || 'Error', 5000, 'error');
          }
        }, 'json');
        return;
      }

      const adminNombre = ($('#admin_nombre').val() || '').trim();
      const adminEmail = ($('#admin_email').val() || '').trim();
      if (!adminNombre || !adminEmail) {
        showCustomAlert('Nombre y email del admin son obligatorios.', 5000, 'error');
        return;
      }

      $.post(API_EMPRESAS, $(this).serialize() + '&action=create_empresa', function (res) {
        if (res.success) {
          // Mostrar credenciales - esto ya funciona con tu código actual
          $('#credUrl').text(res.id_e);
          $('#credEmail').text(res.admin_email);
          $('#credPass').text(res.temp_password);
          $('#credBox').removeClass('hidden');

          resetForm();
          loadEmpresas();
          showCustomAlert('Empresa y Admin creados con éxito', 8000, 'success');
        } else {
          showCustomAlert(res.message || 'Error', 5000, 'error');
        }
      }, 'json').fail(function () {
        showCustomAlert('Error de conexión con el servidor', 5000, 'error');
      });
    });

    $("#empresasTable").on('click', '.editBtn', function () {
      const id = $(this).data('id');
      $.get(API_EMPRESAS, { action: 'get', id: id }, function (res) {
        if (!res.success) return;
        const e = res.data;
        $("#empresaId").val(e.id);
        $("#plan_id").val(e.plan_id || '');
        $("#slug").val(e.slug || '');
        $("#nombre").val(e.nombre || '');
        $("#activo_form").val(e.activo || '0');
        $('#btnSubmit').text('Guardar');
        $('#adminFields').addClass('opacity-50');
        $('#admin_nombre,#admin_email').val('').prop('disabled', true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }, 'json');
    });

    $("#empresasTable").on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      showCustomConfirm('¿Eliminar esta empresa? Esto borrará información relacionada.', function () {
        $.post(API_EMPRESAS, { action: 'delete', id: id }, function (res) {
          if (res.success) {
            loadEmpresas();
            showCustomAlert('Empresa eliminada', 3000, 'info');
          }
          else showCustomAlert('No se pudo eliminar: ' + (res.message || ''), 5000, 'error');
        }, 'json');
      });
    });

    resetForm();
    loadEmpresas();
  });
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
