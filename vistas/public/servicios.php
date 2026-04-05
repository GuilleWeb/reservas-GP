<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$empresa = get_current_empresa();
if (!$empresa) {
    http_response_code(404);
    $module = '404';
    include __DIR__ . '/../../includes/topbar.php';
    include __DIR__ . '/../404.php';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

$empresa_id = (int) ($empresa['id'] ?? 0);
$empresa_slug = (string) ($empresa['slug'] ?? '');
$module = 'servicios';

$stmt = $pdo->prepare("SELECT id, nombre, descripcion, duracion_minutos, precio_base
                       FROM servicios
                       WHERE empresa_id = ? AND activo = 1
                       ORDER BY nombre ASC");
$stmt->execute([$empresa_id]);
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <section class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 md:p-8">
    <div class="flex items-center gap-3">
      <div class="h-12 w-12 rounded-2xl bg-teal-50 text-teal-600 grid place-items-center">
        <i data-lucide="stethoscope"></i>
      </div>
      <div>
        <h1 class="text-3xl font-black text-gray-900">Todos Nuestros Servicios</h1>
        <p class="text-sm text-gray-500">Conoce cada servicio disponible en <?= htmlspecialchars((string) ($empresa['nombre'] ?? 'esta empresa')) ?>.</p>
      </div>
    </div>
  </section>

  <?php if (empty($servicios)): ?>
    <section class="mt-6 bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-500">
      Por el momento no hay servicios publicados.
    </section>
  <?php else: ?>
    <section class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 justify-items-center">
      <?php foreach ($servicios as $s): ?>
        <article class="w-full max-w-md bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-lg font-black text-gray-900"><?= htmlspecialchars((string) ($s['nombre'] ?? 'Servicio')) ?></h2>
          <p class="text-sm text-gray-600 mt-2 min-h-[54px]"><?= htmlspecialchars((string) ($s['descripcion'] ?? '')) ?></p>
          <div class="mt-4 flex items-center justify-between">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-bold">
              <?= (int) ($s['duracion_minutos'] ?? 0) ?> min
            </span>
            <span class="text-lg font-black text-teal-700">
              <?= htmlspecialchars(format_currency_amount((float) ($s['precio_base'] ?? 0), $empresa)) ?>
            </span>
          </div>
          <div class="mt-4">
            <a href="<?= htmlspecialchars(view_url('vistas/public/citas.php', $empresa_slug ?: $empresa_id)) ?>"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 text-white font-semibold hover:bg-teal-700 transition">
              <i data-lucide="calendar-plus" class="w-4 h-4"></i>Agendar este servicio
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
