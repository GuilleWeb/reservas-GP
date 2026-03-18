<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'ajustes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-6 border">
    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8" aria-label="Tabs">
        <button id="tabCompanyBtn" class="tab-btn active px-1 py-2 text-sm font-medium text-teal-600 border-b-2 border-teal-600">Empresa</button>
        <?php if($user && $role === 'admin'): ?>
          <button id="tabProfileBtn" class="tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Mi Perfil</button>
        <?php endif; ?>
      </nav>
    </div>

    <!-- Formulario Empresa -->
    <div id="companyTab" class="tab-pane">
      <div class="font-semibold text-gray-900">Configuración de mi empresa</div>
      <div class="mt-2 text-sm text-gray-600">Personaliza la información, colores, logo y contenido público.</div>

      <form id="companyForm" class="mt-4 space-y-4" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Nombre (solo lectura) -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre de la empresa</label>
            <input id="company_nombre" name="nombre" class="border rounded-lg p-2 w-full bg-gray-100" readonly disabled>
          </div>
          <!-- Slug (solo lectura) -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Slug (identificador)</label>
            <input id="company_slug" name="slug" class="border rounded-lg p-2 w-full bg-gray-100" readonly disabled>
          </div>
          <!-- Slogan -->
          <div>
            <label class="text-sm font-medium text-gray-700">Slogan</label>
            <input id="company_slogan" name="slogan" class="border rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-teal-500">
          </div>
          <!-- Email contacto -->
          <div>
            <label class="text-sm font-medium text-gray-700">Email contacto</label>
            <input id="company_email_contacto" name="email_contacto" class="border rounded-lg px-3 py-2 w-full">
          </div>
          <!-- Teléfono -->
          <div>
            <label class="text-sm font-medium text-gray-700">Teléfono</label>
            <input id="company_telefono_contacto" name="telefono_contacto" class="border rounded-lg px-3 py-2 w-full">
          </div>
          <!-- Dirección general (nuevo) -->
          <div>
            <label class="text-sm font-medium text-gray-700">Dirección general</label>
            <input id="company_direccion_general" name="direccion_general" class="border rounded-lg px-3 py-2 w-full" placeholder="Ej: Av. Principal #123">
          </div>
          <!-- Horario general (nuevo) -->
          <div>
            <label class="text-sm font-medium text-gray-700">Horario general</label>
            <input id="company_horario_general" name="horario_general" class="border rounded-lg px-3 py-2 w-full" placeholder="Ej: Lun-Vie 9am-6pm">
          </div>
          <!-- Moneda -->
          <div>
            <label class="text-sm font-medium text-gray-700">Moneda</label>
            <select id="company_moneda" name="moneda" class="border rounded-lg p-2 w-full">
              <option value="GTQ">Quetzal (Q)</option>
              <option value="USD">Dólar ($)</option>
              <option value="EUR">Euro (€)</option>
            </select>
          </div>
          <!-- Color primario con preview -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Color primario</label>
            <div class="flex items-center gap-2">
              <input type="color" id="company_primary_color_picker" class="w-10 h-10 border-0 p-0 rounded" value="#0d9488">
              <input type="text" id="company_primary_color" name="primary_color" class="border rounded-lg p-2 flex-1" placeholder="#0d9488" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#0d9488">
            </div>
            <p class="text-xs text-gray-500 mt-1">No uses blanco/negro puros.</p>
            <!-- Muestra de color en vivo -->
            <div class="mt-2 flex items-center gap-2">
              <span class="text-sm">Vista previa:</span>
              <div id="colorPreview" class="w-8 h-8 rounded-full border" style="background-color: #0d9488;"></div>
              <button id="testColorBtn" class="px-2 py-1 text-xs text-white rounded" style="background-color: #0d9488;">Botón prueba</button>
            </div>
          </div>
        </div>

        <!-- Descripción (ocupa ancho completo) -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Descripción</label>
          <textarea id="company_descripcion" name="descripcion" rows="3" maxlength="200" class="border rounded-lg p-2 w-full" placeholder="Breve descripción de tu empresa..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Describe tu negocio en 300 caracteres máximo.</p>
        </div>

        <!-- Logo: diseño moderno con preview -->
        <div class="border rounded-lg p-4 bg-gray-50">
          <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la empresa</label>
          <div class="flex flex-col sm:flex-row gap-4 items-start">
            <!-- Preview del logo -->
            <div id="logo_preview_container" class="hidden flex-shrink-0">
              <img id="logo_preview" src="#" alt="Logo" class="h-20 w-20 object-cover rounded-lg border-2 border-gray-200">
            </div>
            <div class="flex-1 space-y-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Subir archivo</label>
                <input type="file" id="company_logo_file" name="logo_file" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">O usar URL pública</label>
                <input type="url" id="company_logo_url" name="logo_url" class="border rounded-lg p-2 w-full" placeholder="https://ejemplo.com/logo.png">
              </div>
            </div>
          </div>
          <input type="hidden" id="company_logo_path" name="logo_path">
        </div>

        <!-- Redes sociales con iconos -->
        <div class="mt-4 pt-4 border-t">
          <h4 class="font-medium text-gray-900 mb-3">Redes sociales</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="facebook" class="text-gray-500"></i></span>
              <input id="social_input_facebook" name="redes[facebook]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://facebook link...">
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="instagram" class="text-gray-500"></i></span>
              <input id="social_input_instagram" name="redes[instagram]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://instagram.com/...">
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="message-circle" class="text-gray-500"></i></span>
              <input id="social_whatsapp" name="redes[whatsapp]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://wa.me/...">
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="music" class="text-gray-500"></i></span>
              <input id="social_tiktok" name="redes[tiktok]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://tiktok.com/...">
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="twitter" class="text-gray-500"></i></span>
              <input id="social_input_twitter" name="redes[x]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://x.com/...">
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i data-lucide="globe" class="text-gray-500"></i></span>
              <input id="social_otro" name="redes[otro]" class="border rounded-lg p-2 pl-10 w-full" placeholder="https://dominio.com/...">
            </div>
            <p class="text-xs text-gray-500 mt-1">Solo los enlaces que agregues se mostraran en las secciones correspondientes.</p>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2 border-t pt-4">
          <button type="button" class="reload-btn px-4 py-2 border rounded-lg" data-tab="company">Recargar Datos</button>
          <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Guardar Cambios</button>
        </div>
      </form>
    </div>

    <!-- Formulario Perfil (mejorado con foto) -->
    <div id="profileTab" class="tab-pane hidden">
      <div class="font-semibold text-gray-900">Mi perfil</div>
      <div class="mt-2 text-sm text-gray-600">Actualiza tus datos personales, foto y contraseña.</div>

      <form id="profileForm" class="mt-4 space-y-4" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
            <input id="profile_nombre" name="nombre" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input id="profile_email" name="email" type="email" class="border rounded-lg p-2 w-full" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input id="profile_telefono" name="telefono" class="border rounded-lg p-2 w-full">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Rol</label>
            <input id="profile_rol" class="border rounded-lg p-2 w-full bg-gray-100" readonly disabled>
          </div>
        </div>

        <!-- Foto de perfil con preview y subida -->
        <div class="border rounded-lg p-4 bg-gray-50">
          <label class="block text-sm font-medium text-gray-700 mb-2">Foto de perfil</label>
          <div class="flex flex-col sm:flex-row gap-4 items-start">
            <div id="profile_photo_preview_container" class="hidden flex-shrink-0">
              <img id="profile_photo_preview" src="#" alt="Foto perfil" class="h-20 w-20 object-cover rounded-full border-2 border-gray-200">
            </div>
            <div class="flex-1">
              <input type="file" id="profile_foto_file" name="foto_file" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, AVIF, WEBP (max 2MB)</p>
            </div>
          </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="mt-4 pt-4 border-t">
          <h4 class="font-medium text-gray-900 mb-3">Cambiar contraseña</h4>
          <p class="text-xs text-gray-500 mb-3">Deja en blanco si no deseas cambiarla.</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Contraseña actual</label>
              <input id="profile_current_password" name="current_password" type="password" class="border rounded-lg p-2 w-full">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
              <input id="profile_new_password" name="new_password" type="password" class="border rounded-lg p-2 w-full">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Confirmar nueva</label>
              <input id="profile_confirm_password" name="confirm_password" type="password" class="border rounded-lg p-2 w-full">
            </div>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2 border-t pt-4">
          <button type="button" class="reload-btn px-4 py-2 border rounded-lg" data-tab="profile">Recargar Datos</button>
          <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Actualizar Perfil</button>
        </div>
      </form>
    </div>

    <div id="saveInfo" class="hidden text-sm text-teal-700 text-right mt-2"></div>
  </div>
</div>

<script>
  $(function () {
    function showInfo(msg, ok) {
      const el = $('#saveInfo');
      el.removeClass('hidden');
      el.toggleClass('text-teal-700', !!ok);
      el.toggleClass('text-red-600', !ok);
      el.text(msg);
      setTimeout(() => el.addClass('hidden'), 2500);
    }

    function setVal(id, v, fallback = '') {
      $('#' + id).val(v !== undefined && v !== null && v !== '' ? String(v) : fallback);
    }

    // Previsualización de color en vivo
    function updateColorPreview(color) {
      $('#colorPreview').css('background-color', color);
      $('#testColorBtn').css('background-color', color);
      // También podrías aplicar a algún elemento del sistema si lo deseas
      document.documentElement.style.setProperty('--primary-color', color);
    }

    $('#company_primary_color_picker').on('input', function () {
      const color = $(this).val();
      $('#company_primary_color').val(color);
      updateColorPreview(color);
    });
    $('#company_primary_color').on('input', function () {
      let val = $(this).val();
      if (/^#[A-Fa-f0-9]{6}$/.test(val) || /^#[A-Fa-f0-9]{3}$/.test(val)) {
        $('#company_primary_color_picker').val(val);
        updateColorPreview(val);
      }
    });

    // Previsualización de logo empresa
    $('#company_logo_file').on('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          $('#logo_preview').attr('src', event.target.result);
          $('#logo_preview_container').removeClass('hidden');
          $('#company_logo_url').val('');
        };
        reader.readAsDataURL(file);
      }
    });

    $('#company_logo_url').on('input', function () {
      const url = $(this).val();
      if (url) {
        $('#logo_preview').attr('src', url);
        $('#logo_preview_container').removeClass('hidden');
        $('#company_logo_file').val('');
      } else {
        $('#logo_preview_container').addClass('hidden');
      }
    });

    // Previsualización de foto perfil
    $('#profile_foto_file').on('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          $('#profile_photo_preview').attr('src', event.target.result);
          $('#profile_photo_preview_container').removeClass('hidden');
        };
        reader.readAsDataURL(file);
      }
    });

    // Carga datos de empresa
    function loadCompanyData() {
      $.get('<?= app_url('api/admin/ajustes.php') ?>', { action: 'get_company' }, function (res) {
        if (!res.success) {
          showCustomAlert('Error al cargar datos de empresa', 3000, 'error');
          return;
        }
        const d = res.data;
        setVal('company_nombre', d.nombre);
        setVal('company_slug', d.slug);
        setVal('company_slogan', d.slogan);
        setVal('company_descripcion', d.descripcion);
        setVal('company_email_contacto', d.email_contacto);
        setVal('company_telefono_contacto', d.telefono_contacto);
        setVal('company_direccion_general', d.direccion_general);
        setVal('company_horario_general', d.horario_general);
        setVal('company_moneda', d.moneda);
        setVal('company_primary_color', d.primary_color);
        $('#company_primary_color_picker').val(d.primary_color || '#0d9488');
        updateColorPreview(d.primary_color || '#0d9488');

        // Logo preview
        if (d.logo_path) {
          $('#logo_preview').attr('src', '../../' + d.logo_path);
          $('#logo_preview_container').removeClass('hidden');
          if (d.logo_path.startsWith('http')) {
            $('#company_logo_url').val(d.logo_path);
          } else {
            $('#company_logo_url').val('');
            $('#company_logo_file').val('');
          }
        } else {
          $('#logo_preview_container').addClass('hidden');
        }

        const redes = d.redes || {};
        setVal('social_input_facebook', redes.facebook);
        setVal('social_input_instagram', redes.instagram);
        setVal('social_whatsapp', redes.whatsapp);
        setVal('social_tiktok', redes.tiktok);
        setVal('social_input_twitter', redes.x || redes.twitter);
        setVal('social_otro', redes.otro);
      }, 'json').fail(() => showCustomAlert('Error de conexión', 3000, 'error'));
    }

    // Carga datos del perfil
    function loadProfileData() {
      $.get('<?= app_url('api/admin/ajustes.php') ?>', { action: 'get_profile' }, function (res) {
        if (!res.success) {
          showCustomAlert('Error al cargar perfil', 3000, 'error');
          return;
        }
        const d = res.data;
        setVal('profile_nombre', d.nombre);
        setVal('profile_email', d.email);
        setVal('profile_telefono', d.telefono);
        setVal('profile_rol', d.rol);
        // Foto preview
        if (d.foto_path) {
          $('#profile_photo_preview').attr('src', '../../' + d.foto_path);
          $('#profile_photo_preview_container').removeClass('hidden');
        } else {
          $('#profile_photo_preview_container').addClass('hidden');
        }
        $('#profile_current_password, #profile_new_password, #profile_confirm_password').val('');
        $('#profile_foto_file').val(''); // limpiar input file
      }, 'json').fail(() => showCustomAlert('Error de conexión', 3000, 'error'));
    }

    // Tabs
    $('#tabCompanyBtn').on('click', function () {
      $('.tab-btn').removeClass('active text-teal-600 border-teal-600').addClass('text-gray-500');
      $(this).addClass('active text-teal-600 border-teal-600');
      $('.tab-pane').addClass('hidden');
      $('#companyTab').removeClass('hidden');
      loadCompanyData();
    });

    $('#tabProfileBtn').on('click', function () {
      $('.tab-btn').removeClass('active text-teal-600 border-teal-600').addClass('text-gray-500');
      $(this).addClass('active text-teal-600 border-teal-600');
      $('.tab-pane').addClass('hidden');
      $('#profileTab').removeClass('hidden');
      loadProfileData();
    });

    $('.reload-btn').on('click', function () {
      const tab = $(this).data('tab');
      if (tab === 'company') loadCompanyData();
      else loadProfileData();
    });

    // Submit empresa con FormData
    $('#companyForm').on('submit', function (ev) {
      ev.preventDefault();
      const formData = new FormData(this);
      formData.append('action', 'save_company');

      const color = $('#company_primary_color').val().toUpperCase();
      if (color === '#FFFFFF' || color === '#FFF' || color === '#000000' || color === '#000') {
        showCustomAlert('El color primario no puede ser blanco puro ni negro puro.', 5000, 'error');
        return;
      }

      $.ajax({
        url: '<?= app_url('api/admin/ajustes.php') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res.success) {
            showCustomAlert('Datos de empresa guardados', 3000, 'success');
            loadCompanyData();
          } else {
            showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
          }
        },
        error: function () {
          showCustomAlert('Error de red', 3000, 'error');
        }
      });
    });

    // Submit perfil con FormData (incluye archivo)
    $('#profileForm').on('submit', function (ev) {
      ev.preventDefault();
      const formData = new FormData(this);
      formData.append('action', 'save_profile');

      $.ajax({
        url: '<?= app_url('api/admin/ajustes.php') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res.success) {
            showCustomAlert('Perfil actualizado', 3000, 'success');
            loadProfileData();
          } else {
            showCustomAlert(res.message || 'Error al actualizar', 5000, 'error');
          }
        },
        error: function () {
          showCustomAlert('Error de red', 3000, 'error');
        }
      });
    });

    // Cargar pestaña inicial (Empresa)
    loadCompanyData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
