<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'cron_jobs';
include __DIR__ . '/../../includes/topbar.php';
?>
<?php
$user = current_user();
$role = $user['rol'] ?? null;
if (!$user || $role !== 'superadmin') {
  echo '<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">No autorizado.</div>';
  return;
}
?>

<div class="max-w-6xl mx-auto">
  <div class="bg-white rounded-2xl shadow border p-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-sm text-gray-500">SuperAdmin</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Cron Jobs Manuales</div>
        <div class="text-sm text-gray-500 mt-1">Ejecuta tareas del sistema cuando sea necesario.</div>
      </div>
      <div class="flex gap-2">
        <button id="refreshLogBtn" class="px-3 py-2 border rounded-lg">Actualizar log</button>
        <button id="runSelectedBtn" class="px-4 py-2 bg-teal-600 text-white rounded-lg">Ejecutar seleccionados</button>
      </div>
    </div>

    <div class="mt-6">
      <div class="flex items-center gap-2 mb-3">
        <input type="checkbox" id="toggleAll" class="h-4 w-4">
        <label for="toggleAll" class="text-sm text-gray-700">Seleccionar todo</label>
      </div>
      <div id="tasksList" class="space-y-3"></div>
    </div>

    <div class="mt-8">
      <div class="font-semibold text-gray-900">Log reciente</div>
      <div class="text-sm text-gray-500">Últimas 200 líneas.</div>
      <pre id="cronLog" class="mt-3 p-4 bg-gray-50 border rounded-xl text-xs whitespace-pre-wrap"></pre>
    </div>
  </div>
</div>

<script>
  const API = <?= json_encode(app_url('api/superadmin/cron_jobs.php')) ?>;

  function loadTasks() {
    $.get(API, { action: 'list' }, function (resp) {
      if (!resp || !resp.success) return;
      const wrap = $('#tasksList').empty();
      resp.data.forEach(task => {
        const id = 'task_' + task.key;
        wrap.append(`
          <label class="flex items-start gap-3 p-3 border rounded-xl bg-gray-50">
            <input type="checkbox" class="taskCheck mt-1" data-key="${task.key}" id="${id}">
            <div>
              <div class="font-semibold text-gray-900">${task.title}</div>
              <div class="text-sm text-gray-500">${task.description}</div>
            </div>
          </label>
        `);
      });
    }, 'json');
  }

  function loadLog() {
    $.get(API, { action: 'log' }, function (resp) {
      if (!resp || !resp.success) return;
      $('#cronLog').text((resp.data || []).join("\n"));
    }, 'json');
  }

  function runSelected() {
    const tasks = [];
    $('.taskCheck:checked').each(function () {
      tasks.push($(this).data('key'));
    });
    if (!tasks.length) {
      alert('Selecciona al menos una tarea.', 3500, 'warning');
      return;
    }
    $.post(API, { action: 'run', tasks }, function (resp) {
      if (resp && resp.success) {
        loadLog();
        alert('Tareas ejecutadas correctamente.', 3500, 'success');
      } else {
        alert('No se pudieron ejecutar las tareas.', 3500, 'error');
      }
    }, 'json');
  }

  $('#toggleAll').on('change', function () {
    $('.taskCheck').prop('checked', this.checked);
  });
  $('#runSelectedBtn').on('click', runSelected);
  $('#refreshLogBtn').on('click', loadLog);

  loadTasks();
  loadLog();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

