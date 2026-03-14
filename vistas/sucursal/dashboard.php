<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'dashboard';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="text-sm text-gray-500">Dashboard</div>
    <div class="mt-1 text-2xl font-extrabold text-gray-900">
      Panel Gerente - <?= htmlspecialchars($id_e) ?>
    </div>
    <div class="mt-2 text-gray-700">
      Usuario: <span class="font-semibold"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
      (<?= htmlspecialchars($role) ?>)
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <a href="<?= view_url('vistas/sucursal/admin-citas.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-gray-50">
        <div class="font-semibold text-gray-900">Citas</div>
        <div class="text-sm text-gray-600">Ver y gestionar agenda de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/sucursales.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-gray-50">
        <div class="font-semibold text-gray-900">Mi Sucursal</div>
        <div class="text-sm text-gray-600">Ver información de la sucursal.</div>
      </a>
      <a href="<?= view_url('vistas/sucursal/ajustes.php', $id_e) ?>"
        class="block rounded-xl border p-4 hover:bg-gray-50">
        <div class="font-semibold text-gray-900">Ajustes</div>
        <div class="text-sm text-gray-600">Configuración de la cuenta.</div>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>