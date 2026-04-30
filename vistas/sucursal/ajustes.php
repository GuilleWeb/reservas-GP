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
      <div class="font-semibold text-gray-900">Ajustes</div>
      <div class="text-sm text-gray-500">Gestiona tus datos personales.</div>
    </div>

    <div class="p-5">
      <form id="formPerfil" class="space-y-4" enctype="multipart/form-data">
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

    function loadData() {
      $.get(API_URL, { action: 'get' }, function (res) {
        if (!res || !res.success) {
          showCustomAlert((res && res.message) || 'No se pudo cargar ajustes.', 5000, 'error');
          return;
        }
        const d = res.data || {};
        const p = d.perfil || {};
        $('#p_nombre').val(p.nombre || '');
        $('#p_email').val(p.email || '');
        $('#p_telefono').val(p.telefono || '');
        $('#p_foto_path').val(p.foto_path || '');
        $('#p_password').val('');
      }, 'json');
    }

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
    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
