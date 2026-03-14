<?php
require_once __DIR__ . '/../app/layout/topbar.php';
$id_e = request_id_e();
?>
<?php if (!$id_e): ?>
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow border">Empresa no definida. Usa <span
        class="font-mono">?id_e=...</span></div>
<?php else: ?>
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h1 class="text-5xl font-extrabold text-gray-900 tracking-tight sm:text-6xl pb-2">
          <span class="bg-clip-text text-transparent bg-gradient-to-r from-teal-600 to-teal-800">Blog</span>
        </h1>
        <p class="mt-4 text-xl text-gray-500 max-w-2xl mx-auto">Próximamente: publicaciones por empresa.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        <div class="md:col-span-2 lg:col-span-3 text-center text-gray-500 text-lg py-10">Sin publicaciones por ahora.</div>
      </div>
    </div>
<?php endif; ?>
<?php
require_once __DIR__ . '/../app/layout/footer.php';
