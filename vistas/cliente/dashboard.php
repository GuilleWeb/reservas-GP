<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="text-sm text-gray-500">Mi Portal</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      Bienvenido, <?= htmlspecialchars($user['nombre'] ?? '') ?>
    </div>
    <div class="mt-2 text-gray-700">
      Desde aquí puedes gestionar tus citas y tu perfil.
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <a href="<?= view_url('vistas/cliente/citas.php', $id_e) ?>" class="block rounded-xl border p-4 hover:bg-gray-50">
        <div class="font-semibold text-gray-900">Mis Citas</div>
        <div class="text-sm text-gray-600">Ver y programar nuevas citas.</div>
      </a>
      <a href="<?= view_url('vistas/cliente/ajustes.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-gray-50">
        <div class="font-semibold text-gray-900">Mi Perfil</div>
        <div class="text-sm text-gray-600">Actualizar mis datos de contacto.</div>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>