<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'anuncios';
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
        <div class="mt-1 text-2xl font-extrabold text-gray-900">Anuncios del sistema</div>
        <div class="text-sm text-gray-500 mt-1">Se mostrarán solo en paneles privados y únicamente para planes básicos.</div>
      </div>
      <button id="saveAdsBtn" class="px-4 py-2 bg-teal-600 text-white rounded-lg">Guardar cambios</button>
    </div>

    <form id="adsForm" class="mt-6 space-y-6" enctype="multipart/form-data">
      <div class="bg-gray-50 border rounded-2xl p-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <div class="font-semibold text-gray-900">Anuncio Sidebar</div>
            <div class="text-sm text-gray-500">Tamaño sugerido: 200x200 px.</div>
          </div>
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="sidebar_activo" id="sidebar_activo" checked>
            Activo
          </label>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4">
          <div class="w-[200px] h-[200px] border rounded-xl bg-white overflow-hidden flex items-center justify-center">
            <img id="preview_sidebar" src="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>" alt="Preview" class="w-full h-full object-cover">
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Imagen</label>
              <input type="file" name="sidebar_file" id="sidebar_file" accept="image/*">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">URL (opcional)</label>
              <input type="url" name="sidebar_url" id="sidebar_url" placeholder="https://...">
            </div>
          </div>
        </div>
      </div>

      <div class="bg-gray-50 border rounded-2xl p-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <div class="font-semibold text-gray-900">Anuncio Footer</div>
            <div class="text-sm text-gray-500">Tamaño sugerido: 200x200 px.</div>
          </div>
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="footer_activo" id="footer_activo" checked>
            Activo
          </label>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4">
          <div class="w-[200px] h-[200px] border rounded-xl bg-white overflow-hidden flex items-center justify-center">
            <img id="preview_footer" src="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>" alt="Preview" class="w-full h-full object-cover">
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Imagen</label>
              <input type="file" name="footer_file" id="footer_file" accept="image/*">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">URL (opcional)</label>
              <input type="url" name="footer_url" id="footer_url" placeholder="https://...">
            </div>
          </div>
        </div>
      </div>

      <div class="bg-gray-50 border rounded-2xl p-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <div class="font-semibold text-gray-900">Anuncio Panel</div>
            <div class="text-sm text-gray-500">Tamaño sugerido: 1200x200 px (banner horizontal).</div>
          </div>
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="panel_activo" id="panel_activo" checked>
            Activo
          </label>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4">
          <div class="w-full max-w-[520px] h-[120px] border rounded-xl bg-white overflow-hidden flex items-center justify-center">
            <img id="preview_panel" src="<?= htmlspecialchars(app_url('assets/logo.avif')) ?>" alt="Preview" class="w-full h-full object-cover">
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Imagen</label>
              <input type="file" name="panel_file" id="panel_file" accept="image/*">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">URL (opcional)</label>
              <input type="url" name="panel_url" id="panel_url" placeholder="https://...">
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  const API = <?= json_encode(app_url('api/superadmin/anuncios.php')) ?>;
  const defaultPreview = <?= json_encode(app_url('assets/logo.avif')) ?>;

  function setPreview(slot, data) {
    const img = document.getElementById(`preview_${slot}`);
    let url = defaultPreview;
    if (data && data.imagen_path) {
      const raw = String(data.imagen_path);
      if (/^https?:\/\//i.test(raw)) {
        url = raw;
      } else {
        url = <?= json_encode(rtrim(app_url(''), '/')) ?> + '/' + raw.replace(/^\/+/, '');
      }
    }
    img.src = url;
    document.getElementById(`${slot}_url`).value = data && data.link_url ? data.link_url : '';
    document.getElementById(`${slot}_activo`).checked = data ? (parseInt(data.activo || 0) === 1) : false;
  }

  function loadAds() {
    $.get(API, { action: 'list' }, function (resp) {
      if (!resp || !resp.success) return;
      setPreview('sidebar', resp.data.sidebar || null);
      setPreview('footer', resp.data.footer || null);
      setPreview('panel', resp.data.panel || null);
    }, 'json');
  }

  function saveAds() {
    const form = document.getElementById('adsForm');
    const fd = new FormData(form);
    fd.append('action', 'save');

    $.ajax({
      url: API,
      method: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.success) {
          loadAds();
          alert('Anuncios actualizados correctamente.', 3500, 'success');
        } else {
          alert((resp && resp.message) ? resp.message : 'No se pudo guardar.', 3500, 'error');
        }
      },
      error: function () {
        alert('No se pudo guardar.', 3500, 'error');
      }
    });
  }

  function bindPreview(slot) {
    const input = document.getElementById(`${slot}_file`);
    input.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function (ev) {
        document.getElementById(`preview_${slot}`).src = ev.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  $('#saveAdsBtn').on('click', function () {
    saveAds();
  });

  ['sidebar', 'footer', 'panel'].forEach(bindPreview);
  loadAds();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
