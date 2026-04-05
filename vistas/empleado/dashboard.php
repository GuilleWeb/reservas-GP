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
    <div class="mt-1 text-2xl font-extrabold text-gray-900">Panel Empleado - <?= htmlspecialchars((string) $id_e) ?></div>
    <div class="mt-2 text-gray-700">Hola, <span class="font-semibold"><?= htmlspecialchars((string) ($user['nombre'] ?? '')) ?></span></div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Citas hoy</div>
        <div id="stat-hoy" class="mt-1 text-3xl font-black text-gray-900">--</div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Pendientes próximas</div>
        <div id="stat-pendientes" class="mt-1 text-3xl font-black text-gray-900">--</div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Completadas</div>
        <div id="stat-completadas" class="mt-1 text-3xl font-black text-gray-900">--</div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total histórico</div>
        <div id="stat-total" class="mt-1 text-3xl font-black text-gray-900">--</div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Citas del mes</div>
        <div id="stat-mes" class="mt-1 text-2xl font-extrabold text-gray-900">--</div>
        <div class="text-xs text-gray-500">Mes anterior: <span id="stat-mes-prev">--</span></div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Ingresos estimados mes</div>
        <div id="stat-ingresos" class="mt-1 text-2xl font-extrabold text-gray-900">$0.00</div>
        <div class="text-xs text-gray-500">Mes anterior: <span id="stat-ingresos-prev">$0.00</span></div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-gray-800 mb-2">Citas: mes actual vs anterior</div>
        <div class="relative h-56">
          <canvas id="chartEmpCitas"></canvas>
        </div>
      </div>
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-gray-800 mb-2">Ingresos: mes actual vs anterior</div>
        <div class="relative h-56">
          <canvas id="chartEmpIngresos"></canvas>
        </div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <a href="<?= view_url('vistas/empleado/citas.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Agenda Completa</div>
        <div class="text-sm text-teal-700">Explorar calendario y disponibilidad.</div>
      </a>
      <a href="<?= view_url('vistas/empleado/ajustes.php', $id_e) ?>" class="block rounded-xl border p-4 border-teal-100 bg-teal-50 hover:bg-teal-100 transition">
        <div class="font-semibold text-teal-900">Configuración</div>
        <div class="text-sm text-teal-700">Actualizar perfil y horario.</div>
      </a>
    </div>

    <div class="mt-8 rounded-2xl border bg-white p-4 shadow-sm">
      <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  $(function () {
    const API_DASH = '<?= app_url('api/empleado/dashboard.php') . '?id_e=' . urlencode((string) request_id_e()) ?>';
    const BRAND = <?= json_encode($color_p ?: '#0d9488') ?>;
    const fmtMoney = (n) => '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let chartCitas = null;
    let chartIngresos = null;

    function drawCharts(stats) {
      const labels = ['Mes anterior', 'Mes actual'];
      const citasData = [Number(stats.citas_mes_prev || 0), Number(stats.citas_mes || 0)];
      const ingresosData = [Number(stats.ingresos_mes_prev || 0), Number(stats.ingresos_mes || 0)];

      const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#64748b' } },
          y: { grid: { color: 'rgba(148,163,184,.2)' }, ticks: { color: '#64748b' }, beginAtZero: true }
        }
      };

      const c1 = document.getElementById('chartEmpCitas');
      const c2 = document.getElementById('chartEmpIngresos');
      if (chartCitas) chartCitas.destroy();
      if (chartIngresos) chartIngresos.destroy();

      if (c1) {
        chartCitas = new Chart(c1, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              data: citasData,
              borderRadius: 10,
              backgroundColor: ['rgba(15,23,42,0.14)', hexToRgba(BRAND, 0.72)],
              borderColor: ['rgba(15,23,42,0.25)', hexToRgba(BRAND, 1)],
              borderWidth: 1.2
            }]
          },
          options: commonOpts
        });
      }

      if (c2) {
        chartIngresos = new Chart(c2, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              data: ingresosData,
              borderColor: BRAND,
              pointBackgroundColor: BRAND,
              pointRadius: 4,
              pointHoverRadius: 5,
              borderWidth: 2.5,
              tension: 0.35,
              fill: true,
              backgroundColor: hexToRgba(BRAND, 0.14)
            }]
          },
          options: commonOpts
        });
      }
    }

    $.get(API_DASH, function (res) {
      if (!res || !res.success) return;
      const stats = res.stats || {};
      $('#stat-hoy').text(stats.citas_hoy ?? 0);
      $('#stat-pendientes').text(stats.pendientes ?? 0);
      $('#stat-completadas').text(stats.citas_completadas ?? 0);
      $('#stat-total').text(stats.citas_total ?? 0);
      $('#stat-mes').text(stats.citas_mes ?? 0);
      $('#stat-mes-prev').text(stats.citas_mes_prev ?? 0);
      $('#stat-ingresos').text(fmtMoney(stats.ingresos_mes || 0));
      $('#stat-ingresos-prev').text(fmtMoney(stats.ingresos_mes_prev || 0));
      drawCharts(stats);

      const tbody = $('#proximas-table').empty();
      const proximas = Array.isArray(res.proximas) ? res.proximas : [];
      if (proximas.length > 0) {
        proximas.forEach(c => {
          const d = new Date(String(c.inicio || '').replace(' ', 'T'));
          tbody.append(`
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-bold text-gray-800">${c.cliente_nombre || '-'}</div>
                <div class="text-xs text-gray-500">${c.cliente_telefono || ''}</div>
              </td>
              <td class="px-6 py-4 font-medium text-teal-700">${c.servicio_nombre || '-'}</td>
              <td class="px-6 py-4">
                <div class="font-semibold text-gray-700">${d.toLocaleDateString('es-MX')}</div>
                <div class="text-xs text-gray-400">${d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}</div>
              </td>
              <td class="px-6 py-4 text-xs text-teal-700 font-bold uppercase tracking-wider">${c.sucursal_nombre || 'N/A'}</td>
            </tr>
          `);
        });
      } else {
        tbody.append('<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No tienes citas próximas agendadas.</td></tr>');
      }
      if (window.lucide) lucide.createIcons();
    }, 'json');
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
    function hexToRgba(hex, alpha) {
      const h = String(hex || '').replace('#', '');
      const full = h.length === 3 ? h.split('').map(ch => ch + ch).join('') : h.padEnd(6, '0').slice(0, 6);
      const n = parseInt(full, 16);
      const r = (n >> 16) & 255;
      const g = (n >> 8) & 255;
      const b = n & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
