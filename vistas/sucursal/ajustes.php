<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'ajustes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <div class="font-semibold text-gray-900">Configuración de Mi Empresa</div>
    <div class="mt-2 text-sm text-gray-600">Personaliza los colores, logo y tu landing page (inicio).</div>

    <form id="tenantForm" class="mt-4 space-y-4">
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Logo (URL o ruta local)</label>
          <input id="system_logo_path" name="system_logo_path" class="border rounded-lg p-2 w-full"
            placeholder="assets/logo.avif">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Color Primario (Hexadecimal)</label>
          <input id="ui_primary_color" name="ui_primary_color" class="border rounded-lg p-2 w-full"
            placeholder="#0d9488">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Email de Soporte/Contacto</label>
          <input id="support_email" name="support_email" class="border rounded-lg p-2 w-full"
            placeholder="soporte@dominio.com">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Teléfono de Soporte/Contacto</label>
          <input id="support_phone" name="support_phone" class="border rounded-lg p-2 w-full" placeholder="+52 ...">
        </div>
      </div>

      <div class="mt-4 pt-4 border-t border-gray-100">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Configuración de Landing Page (Inicio)</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Título del Hero Banner</label>
            <input id="hero_titulo" name="hero_titulo"
              class="border border-gray-300 focus:ring-2 focus:ring-teal-500 rounded-lg p-2 w-full mt-1">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Subtítulo del Hero Banner</label>
            <input id="hero_subtitulo" name="hero_subtitulo"
              class="border border-gray-300 focus:ring-2 focus:ring-teal-500 rounded-lg p-2 w-full mt-1">
          </div>
        </div>
      </div>

      <div class="mt-4 flex items-center justify-end gap-2 border-t pt-4">
        <button type="button" id="btnReload" class="px-4 py-2 border rounded-lg">Recargar Datos</button>
        <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Guardar
          Apariencia</button>
      </div>
      <div id="saveInfo" class="hidden text-sm text-teal-700 text-right mt-2"></div>
    </form>
  </div>
</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/ajustes.php') ?>'; // Gerente usa su propia API
  $(function () {
    function showInfo(msg, ok) {
      const el = $('#saveInfo');
      el.removeClass('hidden').toggleClass('text-teal-700', !!ok).toggleClass('text-red-600', !ok).text(msg);
      setTimeout(() => el.addClass('hidden'), 2500);
    }

    function loadData() {
      $.get(API_URL, { action: 'get' }, function (res) {
        if (!res.success) return;
        const d = res.data || {};
        $('#system_logo_path').val(d.logo_path || '');
        $('#ui_primary_color').val(d.color_principal || '');
        $('#support_email').val(d.email_contacto || '');
        $('#support_phone').val(d.telefono_contacto || '');
        $('#hero_titulo').val(d.hero_titulo || '');
        $('#hero_subtitulo').val(d.hero_subtitulo || '');
      }, 'json');
    }

    $('#btnReload').click(loadData);

    $('#tenantForm').on('submit', function (ev) {
      ev.preventDefault();
      $.post(API_URL, $(this).serialize() + '&action=save', function (res) {
        if (res.success) {
          showInfo('Apariencia guardada con éxito.', true);
          loadData();
        } else {
          showInfo(res.message || 'Error al guardar', false);
        }
      }, 'json');
    });

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>