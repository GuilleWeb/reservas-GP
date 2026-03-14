<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'home_page';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-5xl mx-auto space-y-6">

  <div class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-extrabold text-gray-900">Configuración de Home Page</h2>
        <a href="../../vistas/public/inicio.php?slug=<?= htmlspecialchars($empresa['slug']) ?>" class="nav-link text-teal-900">
          <i class="fas fa-home w-5"></i>
          <span class="ml-2 sidebar-label">Ver vista publica</span>
        </a>
        <p class="text-sm text-gray-500">Personaliza las secciones y elementos que tus clientes verán al entrar a tu
          sitio.</p>
      </div>
      <div id="saveStatus"
        class="hidden text-teal-600 font-bold text-sm bg-teal-50 px-3 py-1 rounded-full animate-pulse border border-teal-100">
        <i class="fas fa-check-circle mr-1"></i> Cambios guardados
      </div>
    </div>

    <form id="homePageForm" class="space-y-8">

      <!-- Secciones de Visibilidad -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Hero</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="hero_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Nosotros</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="about_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Blog</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="blog_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Equipo</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="equipo_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Servicios</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="servicios_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
        <div class="p-4 border rounded-2xl bg-gray-50 flex items-center justify-between">
          <span class="font-bold text-gray-800">Sección Contacto</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="contacto_visible" value="1" class="sr-only peer" checked>
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600">
            </div>
          </label>
        </div>
      </div>

      <div class="border-t pt-8 space-y-6">

        <!-- Blog -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Artículos Destacados (Max 3)</label>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3" id="featured-blog-list">
            <select name="featured_blog[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_blog[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_blog[]" class="border rounded-lg p-2 bg-white"></select>
          </div>
        </div>

        <!-- Equipo -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Miembros del Equipo Destacados (Max 4)</label>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3" id="featured-equipo-list">
            <select name="featured_equipo[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_equipo[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_equipo[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_equipo[]" class="border rounded-lg p-2 bg-white"></select>
          </div>
        </div>

        <!-- Servicios -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Servicios Destacados (Max 4)</label>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3" id="featured-servicios-list">
            <select name="featured_servicios[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_servicios[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_servicios[]" class="border rounded-lg p-2 bg-white"></select>
            <select name="featured_servicios[]" class="border rounded-lg p-2 bg-white"></select>
          </div>
        </div>

      </div>

      <div class="pt-6 border-t flex justify-end">
        <button type="submit"
          class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-teal-100 transition-all hover:scale-105 active:scale-95">
          Guardar Cambios en el Sitio
        </button>
      </div>

    </form>
  </div>

</div>

<script>
  $(function () {
    const API_PAGE = '<?= app_url('api/admin/home_page.php') ?>';

    function loadData() {
      // Load Catalog
      $.get(API_PAGE, { action: 'get_catalog' }, function (res) {
        if (!res.success) return;

        // Populate selects
        const blogSel = $('select[name="featured_blog[]"]');
        const equipoSel = $('select[name="featured_equipo[]"]');
        const servSel = $('select[name="featured_servicios[]"]');

        [blogSel, equipoSel, servSel].forEach(s => s.empty().append('<option value="">-- No seleccionado --</option>'));

        res.blog.forEach(item => blogSel.append(`<option value="${item.id}">${item.titulo}</option>`));
        res.equipo.forEach(item => equipoSel.append(`<option value="${item.id}">${item.nombre}</option>`));
        res.servicios.forEach(item => servSel.append(`<option value="${item.id}">${item.nombre}</option>`));

        // Now load settings
        loadSettings();
      }, 'json');
    }

    function loadSettings() {
      $.get(API_PAGE, { action: 'get_settings' }, function (res) {
        if (!res.success) return;
        const d = res.data;

        // Visibility
        $('input[name="hero_visible"]').prop('checked', d.hero_visible == 1);
        $('input[name="about_visible"]').prop('checked', d.about_visible == 1);
        $('input[name="blog_visible"]').prop('checked', d.blog_visible == 1);
        $('input[name="equipo_visible"]').prop('checked', d.equipo_visible == 1);
        $('input[name="servicios_visible"]').prop('checked', d.servicios_visible == 1);
        $('input[name="contacto_visible"]').prop('checked', d.contacto_visible == 1);

        // Featured
        (d.featured_blog || []).forEach((id, i) => {
          $('select[name="featured_blog[]"]').eq(i).val(id);
        });
        (d.featured_equipo || []).forEach((id, i) => {
          $('select[name="featured_equipo[]"]').eq(i).val(id);
        });
        (d.featured_servicios || []).forEach((id, i) => {
          $('select[name="featured_servicios[]"]').eq(i).val(id);
        });

      }, 'json');
    }

    $('#homePageForm').on('submit', function (e) {
      e.preventDefault();
      const payload = $(this).serialize() + '&action=save_settings';
      $.post(API_PAGE, payload, function (res) {
        if (res.success) {
          showCustomAlert('Cambios en el sitio guardados con éxito.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
        }
      }, 'json');
    });

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>