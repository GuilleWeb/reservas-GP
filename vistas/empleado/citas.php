<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'citas-empleado';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white shadow rounded-2xl p-6 border flex flex-col h-[calc(100vh-140px)] min-h-[500px]">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Mi Agenda Personal</h2>
        <p class="text-sm text-gray-500">Aquí puedes ver tus próximas citas.</p>
      </div>
    </div>

    <!-- Contenedor Tabla -->
    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Fecha / Hora</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Cliente / Servicio</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white">
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500" id="pageInfo"></div>
      <div class="flex items-center space-x-1" id="pagination"></div>
    </div>
  </div>
</div>

<script>
  const API_URL = '<?= app_url('api/empleado/citas.php') ?>';
  let currentPage = 1;

  function getEstadoBadge(estado) {
    switch (estado) {
      case 'pendiente': return `<span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">Pendiente</span>`;
      case 'confirmada': return `<span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Confirmada</span>`;
      case 'cancelada': return `<span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Cancelada</span>`;
      case 'completada': return `<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Completada</span>`;
      case 'no_asistio': return `<span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded text-xs">No Asistió</span>`;
      default: return `<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs">${estado}</span>`;
    }
  }

  function loadData(page = 1) {
    currentPage = page;
    $.get(API_URL, { action: 'list', page: currentPage, per: 15 }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let badge = getEstadoBadge(item.estado);
          let d = new Date(item.inicio.replace(' ', 'T'));
          let dateStr = d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 font-semibold text-gray-800 text-sm">
                ${dateStr} <br><span class="text-xs text-gray-500 font-normal">Sede: ${item.sucursal_nombre || 'N/A'}</span>
            </td>
            <td class="py-3 px-4">
                <div class="text-sm font-semibold">${item.cliente_nombre || '-'}</div>
                <div class="text-xs text-gray-500">${item.servicio_nombre || '-'}</div>
            </td>
            <td class="py-3 px-4">${badge}</td>
            <td class="py-3 px-4 text-right">
                <button onclick="viewDetails(${item.id})" class="text-teal-600 hover:text-teal-800 bg-teal-50 px-2.5 py-1.5 rounded-lg border border-teal-200"><i data-lucide="eye"></i></button>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Total: ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="4" class="py-10 text-center text-gray-500">No tienes citas programadas.</td></tr>');
      }
    }, 'json');
  }

  function viewDetails(id) {
    alert('Función para ver detalles en desarrollo.');
  }

  $(function () {
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>