<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'ajustes-empleado';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-3xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-xl font-extrabold text-gray-900 mb-6">Mi Perfil</div>

    <form id="profileForm" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
        <input type="text" id="nombre" name="nombre"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 cursor-not-allowed"
          value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" readonly>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="email" name="email"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 cursor-not-allowed"
          value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
        <input type="text" id="telefono" name="telefono" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2"
          value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
      </div>

      <div class="pt-4 border-t">
        <label class="block text-sm font-medium text-gray-700 font-bold mb-2">Cambiar Contraseña</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-500">Nueva Contraseña</label>
            <input type="password" id="password" name="password"
              class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="********">
          </div>
          <div>
            <label class="block text-xs text-gray-500">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password"
              class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="********">
          </div>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-end border-t border-gray-100">
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar Perfil</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API_URL = '<?= app_url('api/empleado/ajustes.php') ?>';
  $(function () {
    $('#profileForm').on('submit', function (e) {
      e.preventDefault();
      if ($('#password').val() && $('#password').val() !== $('#confirm_password').val()) {
        alert('Las contraseñas no coinciden.');
        return;
      }

      $.post(API_URL + '?action=save', $(this).serialize(), function (res) {
        if (res.success) {
          alert('Perfil actualizado con éxito.');
          $('#password, #confirm_password').val('');
        } else {
          alert(res.message || 'Error al actualizar perfil');
        }
      }, 'json');
    });
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>