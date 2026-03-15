<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      Panel Empleado - <?= htmlspecialchars($id_e) ?>
    </div>
    <div class="mt-2 text-gray-700">
      Hola, <span class="font-semibold"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
    </div>

    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Citas hoy</div>
        <div class="text-3xl font-black text-teal-600" id="stat-hoy">--</div>
      </div>
      <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Pendientes próximas</div>
        <div class="text-3xl font-black text-teal-600" id="stat-pendientes">--</div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <a href="<?= view_url('vistas/empleado/citas.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Agenda Completa</div>
        <div class="text-sm text-teal-700">Explorar calendario y disponibilidad.</div>
      </a>
      <a href="<?= view_url('vistas/empleado/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-teal-100 bg-teal-50 border-teal-100 transition">
        <div class="font-semibold text-teal-900">Configuración</div>
        <div class="text-sm text-teal-700">Actualizar perfil y horario.</div>
      </a>
    </div>

    <div class="mt-10">
      <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
        <i data-lucide="calendar-check" class="mr-2 text-teal-600"></i> Próximas Citas
      </h3>
      <div class="overflow-hidden bg-white rounded-2xl border">
        <table class="w-full text-left">
          <thead class="bg-gray-50 text-gray-600 text-xs font-bold uppercase">
            <tr>
              <th class="px-6 py-3">Cliente</th>
              <th class="px-6 py-3">Servicio</th>
              <th class="px-6 py-3">Fecha y Hora</th>
              <th class="px-6 py-3">Sede</th>
            </tr>
          </thead>
          <tbody id="proximas-table" class="divide-y text-sm">
            <tr>
              <td colspan="4" class="px-6 py-8 text-center text-gray-400">Cargando próximos compromisos...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_DASH = '<?= app_url('api/empleado/dashboard.php') ?>';
    $.get(API_DASH, function (res) {
      if (!res.success) return;
      $('#stat-hoy').text(res.stats.citas_hoy);
      $('#stat-pendientes').text(res.stats.pendientes);

      const tbody = $('#proximas-table').empty();
      if (res.proximas && res.proximas.length > 0) {
        res.proximas.forEach(c => {
          const d = new Date(c.inicio.replace(' ', 'T'));
          tbody.append(`
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-bold text-gray-800">${c.cliente_nombre}</div>
                <div class="text-xs text-gray-500">${c.cliente_telefono || ''}</div>
              </td>
              <td class="px-6 py-4 font-medium text-teal-600">${c.servicio_nombre}</td>
              <td class="px-6 py-4">
                <div class="font-semibold text-gray-700">${d.toLocaleDateString()}</div>
                <div class="text-xs text-gray-400">${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
              </td>
              <td class="px-6 py-4 text-xs text-teal-700 font-bold uppercase tracking-wider">${c.sucursal_nombre}</td>
            </tr>
          `);
        });
      } else {
        tbody.append('<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No tienes citas próximas agendadas.</td></tr>');
      }
    });
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>