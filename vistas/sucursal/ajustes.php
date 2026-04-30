<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'ajustes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow border">
    <div class="p-5 border-b">
      <div class="font-semibold text-gray-900">Configuración de Sucursal</div>
      <div class="text-sm text-gray-500">Gestiona la información de tu sucursal y tus datos personales.</div>
      <div class="mt-4 inline-flex rounded-xl border border-gray-200 p-1 bg-gray-50">
        <button id="tabSucursal" class="px-4 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white">Sucursal</button>
        <button id="tabPerfil" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700">Mi perfil</button>
      </div>
    </div>

    <div class="p-5">
      <form id="formSucursal" class="space-y-4" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre sucursal</label>
            <input id="s_nombre" name="nombre" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Foto (URL o ruta)</label>
            <input type="hidden" id="s_foto_path" name="foto_path">
            <input type="file" id="s_foto_file" name="foto_file" accept="image/*" class="border rounded-lg p-2 w-full bg-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input id="s_telefono" name="telefono" class="border rounded-lg p-2 w-full">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input id="s_email" name="email" type="email" class="border rounded-lg p-2 w-full">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Dirección</label>
            <input id="s_direccion" name="direccion" class="border rounded-lg p-2 w-full">
          </div>
        </div>

        <div class="pt-4 border-t">
          <div class="text-sm font-semibold text-gray-900 mb-3">Horarios</div>
          <div id="horariosWrap" class="space-y-2"></div>
        </div>

        <div class="pt-4 border-t flex justify-end">
          <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 text-white font-semibold hover:bg-teal-700">Guardar sucursal</button>
        </div>
      </form>

      <form id="formPerfil" class="space-y-4 hidden" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input id="p_nombre" name="nombre" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input id="p_email" name="email" type="email" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input id="p_telefono" name="telefono" class="border rounded-lg p-2 w-full">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Foto (URL o ruta)</label>
            <input type="hidden" id="p_foto_path" name="foto_path">
            <input type="file" id="p_foto_file" name="foto_file" accept="image/*" class="border rounded-lg p-2 w-full bg-white">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nueva contraseña (opcional)</label>
            <input id="p_password" name="password" type="password" class="border rounded-lg p-2 w-full" placeholder="Dejar vacío para mantener la actual">
          </div>
        </div>

        <div class="pt-4 border-t flex justify-end">
          <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 text-white font-semibold hover:bg-teal-700">Guardar perfil</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_URL = <?= json_encode(app_url('api/sucursal/ajustes.php') . '?id_e=' . urlencode((string) request_id_e())) ?>;
    const dayLabels = [
      ['lunes', 'Lunes'], ['martes', 'Martes'], ['miercoles', 'Miércoles'], ['jueves', 'Jueves'],
      ['viernes', 'Viernes'], ['sabado', 'Sábado'], ['domingo', 'Domingo']
    ];

    function renderHorarios(horarios) {
      const box = $('#horariosWrap').empty();
      dayLabels.forEach(([key, label]) => {
        const d = horarios && horarios[key] ? horarios[key] : {};
        const activo = parseInt(d.activo || 0, 10) === 1;
        const ini = String(d.inicio || '09:00');
        const fin = String(d.fin || '18:00');
        box.append(`
          <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-center border rounded-lg p-3 bg-gray-50">
            <div class="font-medium text-gray-800">${label}</div>
            <div>
              <input type="hidden" name="${key}_activo" value="${activo ? '1' : '0'}">
              <button type="button" class="day-switch relative inline-flex h-6 w-11 items-center rounded-full ${activo ? 'bg-teal-600' : 'bg-gray-300'}" data-day="${key}">
                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform ${activo ? 'translate-x-5' : 'translate-x-0'}"></span>
              </button>
            </div>
            <div>
              <input name="${key}_inicio" type="time" value="${ini}" class="border rounded-lg p-2 w-full">
            </div>
            <div>
              <input name="${key}_fin" type="time" value="${fin}" class="border rounded-lg p-2 w-full">
            </div>
            <div class="text-xs text-gray-500">Activo / inicio / fin</div>
          </div>
        `);
      });
    }

    function setTab(tab) {
      const s = tab === 'sucursal';
      $('#formSucursal').toggleClass('hidden', !s);
      $('#formPerfil').toggleClass('hidden', s);
      $('#tabSucursal').toggleClass('bg-teal-600 text-white', s).toggleClass('text-gray-700', !s);
      $('#tabPerfil').toggleClass('bg-teal-600 text-white', !s).toggleClass('text-gray-700', s);
    }

    function loadData() {
      $.get(API_URL, { action: 'get' }, function (res) {
        if (!res || !res.success) {
          renderHorarios({});
          showCustomAlert((res && res.message) || 'No se pudo cargar ajustes.', 5000, 'error');
          return;
        }
        const d = res.data || {};
        const s = d.sucursal || {};
        const p = d.perfil || {};
        $('#s_nombre').val(s.nombre || '');
        $('#s_direccion').val(s.direccion || '');
        $('#s_telefono').val(s.telefono || '');
        $('#s_email').val(s.email || '');
        $('#s_foto_path').val(s.foto_path || '');
        $('#p_nombre').val(p.nombre || '');
        $('#p_email').val(p.email || '');
        $('#p_telefono').val(p.telefono || '');
        $('#p_foto_path').val(p.foto_path || '');
        $('#p_password').val('');
        renderHorarios(d.horarios || {});
      }, 'json');
    }

    $('#tabSucursal').on('click', () => setTab('sucursal'));
    $('#tabPerfil').on('click', () => setTab('perfil'));

    $('#horariosWrap').on('click', '.day-switch', function () {
      const active = !$(this).hasClass('bg-teal-600');
      $(this).toggleClass('bg-teal-600', active).toggleClass('bg-gray-300', !active);
      $(this).find('span').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
      const day = $(this).data('day');
      $(this).closest('.grid').find(`input[name="${day}_activo"]`).val(active ? '1' : '0');
    });

    $('#formSucursal').on('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', 'save_branch');
      $.ajax({
        url: API_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res && res.success) {
            showCustomAlert('Sucursal actualizada correctamente.', 3000, 'success');
            loadData();
            return;
          }
          showCustomAlert((res && res.message) || 'No se pudo actualizar la sucursal.', 5000, 'error');
        }
      });
    });

    $('#formPerfil').on('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', 'save_profile');
      $.ajax({
        url: API_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res && res.success) {
            showCustomAlert('Perfil actualizado correctamente.', 3000, 'success');
            loadData();
            return;
          }
          showCustomAlert((res && res.message) || 'No se pudo actualizar el perfil.', 5000, 'error');
        }
      });
    });

    setTab('sucursal');
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
