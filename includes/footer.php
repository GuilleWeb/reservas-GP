<?php
// includes/footer.php

?>
<!-- ===== FOOTER ===== -->
<footer class="bg-teal-700 text-white mt-8 rounded-lg">
  <div class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

    <!-- Columna 1: Logo + descripción -->
    <div class="flex flex-col items-center text-center space-y-3">
      <img src="<?= $logo_path ?>" alt="Logo" class="h-16 w-16 rounded-full object-cover">
      <h3 class="text-lg font-semibold border-b border-teal-500 pb-1"><?= $empresa_nombre ?></h3>
      <p class="text-sm text-white-100 max-w-xs"><?= $empresa_descripcion ?></p>
    </div>

    <!-- Columna 2: Enlaces 
    <div>
      <h4 class="font-semibold text-lg mb-3 border-b border-teal-500 pb-1">Enlaces</h4>
      <ul class="space-y-2 text-sm">
        <?php $id_e = request_id_e(); ?>
        <li><a href="<?= htmlspecialchars(app_link_with_slug('vistas/public/inicio.php', $id_e)) ?>" class="hover:text-teal-300 transition">Inicio</a></li>
        <li><a href="<?= htmlspecialchars(app_link_with_slug('vistas/public/sedes.php', $id_e)) ?>" class="hover:text-teal-300 transition">Sedes</a></li>
        <li><a href="<?= htmlspecialchars(app_link_with_slug('vistas/public/citas.php', $id_e)) ?>" class="hover:text-teal-300 transition">Citas</a></li>
        <li><a href="<?= htmlspecialchars(app_link_with_slug('vistas/public/blog.php', $id_e)) ?>" class="hover:text-teal-300 transition">Blog</a></li>
      </ul>
    </div>-->

    <!-- Columna 3: Contacto -->
    <div>
      <h4 class="font-semibold text-lg mb-3 border-b border-teal-500 pb-1">Contacto</h4>
      <ul class="text-sm space-y-2">
        <li><i class="fas fa-map-marker-alt w-5"></i> Dirección: <?= htmlspecialchars($direccion) ?></li>
        <li><i class="fas fa-phone w-5"></i> Teléfono: <?= htmlspecialchars($telefono_contacto) ?></li>
        <li><i class="fas fa-envelope w-5"></i> Email: <?= htmlspecialchars($email_contacto) ?></li>
        <li><i class="fas fa-clock w-5"></i> Horario: <?= htmlspecialchars($horaios) ?></li>
      </ul>
    </div>

    <!-- Columna 4: Redes + créditos -->
    <div>
      <?php if (!empty($redes)): ?>
      <h4 class="font-semibold text-lg mb-3 border-b border-teal-500 pb-1">Síguenos</h4>
      <div class="flex space-x-4 text-2xl items-center text-center">
        <?php if (!empty($redes['facebook'])): ?>
          <a href="<?= htmlspecialchars($redes['facebook']) ?>" target="_blank" class="hover:text-gray-300"><i class="fab fa-facebook"></i></a>
        <?php endif; ?>
        <?php if (!empty($redes['instagram'])): ?>
          <a href="<?= htmlspecialchars($redes['instagram']) ?>" target="_blank" class="hover:text-gray-300"><i class="fab fa-instagram"></i></a>
        <?php endif; ?>
        <?php if (!empty($redes['whatsapp'])): ?>
          <a href="<?= htmlspecialchars($redes['whatsapp']) ?>" target="_blank" class="hover:text-gray-300"><i class="fab fa-whatsapp"></i></a>
        <?php endif; ?>
        <?php if (!empty($redes['tiktok'])): ?>
          <a href="<?= htmlspecialchars($redes['tiktok']) ?>" target="_blank" class="hover:text-gray-300"><i class="fab fa-tiktok"></i></a>
        <?php endif; ?>
        <?php if (!empty($redes['x'])): ?>
          <a href="<?= htmlspecialchars($redes['x']) ?>" target="_blank" class="hover:text-gray-300"><i class="fab fa-x-twitter"></i></a>
        <?php endif; ?>
        <?php if (!empty($redes['otro'])): ?>
          <a href="<?= htmlspecialchars($redes['otro']) ?>" target="_blank" class="hover:text-gray-300"><i class="fa fa-globe"></i></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="flex items-center space-x-3 mt-5 p-4 border-t border-teal-500 pb-1">
        <img src="../../assets/logo.avif" alt="Sistema" class="h-5 w-5 rounded-full">
        <small class="text-m font-semibold">by: <b>Sistema de reservas GP</b></small>
      </div>
    </div>

  </div>

  <!-- Línea inferior (texto fijo del sistema) -->
  <div class="border-t border-teal-500 pb-1 text-center py-4 text-sm text-white-100">
    © <span id="year"></span> <b>Sistema de reservas GP</b> — Todos los derechos reservados.
  </div>
</footer>
</main>
</div>
<!-- Modales globales (vacío para clonar) -->
<div id="global-modals"></div>
<!-- Contenedor de Toasts (posicionado en la esquina inferior derecha) -->
<div id="toastContainer"
  class="fixed bottom-4 right-4 w-full max-w-sm z-[9999] space-y-3 pointer-events-none shadow-xl"></div>
<!-- Modal de Confirmación -->
<div id="customConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
    <h3 id="confirmTitle" class="text-lg font-semibold text-gray-800 mb-2">Confirmar acción</h3>
    <p id="confirmText" class="text-gray-600 mb-6">¿Estás seguro de continuar?</p>
    <div class="flex justify-end gap-3">
      <button id="confirmCancelBtn"
        class="px-4 py-2 rounded border border-gray-300 text-gray-700 font-semibold hover:bg-gray-100">
        Cancelar
      </button>
      <button id="confirmOkBtn" class="px-4 py-2 rounded text-white font-semibold bg-teal-600 hover:bg-teal-700">
        Aceptar
      </button>
    </div>
  </div>
</div>
<script>
  // set up ajax csrf header and global error handler
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    },
    error: function (xhr) {
      if (xhr.status === 401 || xhr.status === 403) {
        let res = {};
        try { res = JSON.parse(xhr.responseText); } catch (e) { }
        // Corregido: Solo redirigir si no estamos ya en una vista pública que NO requiere auth
        const isPublic = <?= json_encode($is_public_view) ?>;
        if (!isPublic && (res.error === 'unauthorized' || res.error === 'no_auth')) {
          window.location.href = <?= json_encode(app_url('vistas/public/login.php')) ?>;
        }
      }
    }
  });
  document.getElementById('year').textContent = new Date().getFullYear();

  // logout
  $('#logoutBtn').on('click', function () {
    $.post(<?= json_encode(app_url('api/api-auth.php')) ?>, { action: 'logout' }, function (resp) {
      location.href = <?= json_encode(app_url('vistas/public/login.php')) ?>;
    }).fail(function () {
      location.href = <?= json_encode(app_url('vistas/public/login.php')) ?>;
    });
  });
  // === MARCAR ENLACE ACTIVO ===
  $(document).ready(function () {
    const path = window.location.pathname.split('/').pop() || 'inicio';
    $('.nav-link').each(function () {
      const href = $(this).attr('href');
      if (path.startsWith(href)) {
        $(this)
          .addClass('bg-teal-100 text-teal-700 font-semibold')
          .find('i')
          .addClass('text-teal-600');
      }
    });
  });

  // === TOGGLE SIDEBAR EN MÓVIL ===
  const sidebar = $('#sidebar');
  const overlay = $('#overlay');
  $('#menuToggle').on('click', () => {
    sidebar.toggleClass('-translate-x-full');
    overlay.toggleClass('hidden');
  });
  overlay.on('click', () => {
    sidebar.addClass('-translate-x-full');
    overlay.addClass('hidden');
  });

  // Configuración de íconos y estilos
  const alertConfigs = {
    success: {
      title: 'Éxito',
      bg: 'bg-green-600',
      color: 'text-white',
      icon: '<i class="fas fa-check-circle text-xl"></i>'
    },
    error: {
      title: 'Error',
      bg: 'bg-red-600',
      color: 'text-white',
      icon: '<i class="fas fa-exclamation-triangle text-xl"></i>'
    },
    warning: {
      title: 'Aviso',
      bg: 'bg-amber-500',
      color: 'text-white',
      icon: '<i class="fas fa-exclamation-circle text-xl"></i>'
    },
    info: {
      title: 'Info',
      bg: 'bg-teal-600',
      color: 'text-white',
      icon: '<i class="fas fa-info-circle text-xl"></i>'
    }
  };

  function showCustomAlert(message, time = 5000, type = 'success') {
    const config = alertConfigs[type.toLowerCase()] || alertConfigs.error;
    const container = document.getElementById('toastContainer');

    // Crear toast dinámico
    const toast = document.createElement('div');
    toast.className = `
    ${config.bg} ${config.color} rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-500 ease-in-out pointer-events-auto border-4 border-white/20
  `;
    toast.innerHTML = `
    <div class="flex items-center p-5 space-x-4 min-w-[300px]">
      <div class="flex-shrink-0">${config.icon}</div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-black uppercase tracking-widest opacity-70">${config.title}</p>
        <p class="text-sm font-bold mt-0.5 leading-tight">${message.replace(/\n/g, '<br>')}</p>
      </div>
      <button class="flex-shrink-0 hover:scale-110 transition-transform focus:outline-none" aria-label="Cerrar">
        <i class="fas fa-times"></i>
      </button>
    </div>
  `;

    // Añadir al contenedor
    container.appendChild(toast);

    // Activar animación de entrada
    requestAnimationFrame(() => {
      toast.classList.remove('translate-x-full', 'opacity-0');
      toast.classList.add('translate-x-0', 'opacity-100');
    });

    // Cerrar manualmente al hacer clic en el botón
    toast.querySelector('button').addEventListener('click', () => hideToast(toast));

    // Autocierre
    if (time > 0) {
      setTimeout(() => hideToast(toast), time);
    }
  }

  /**
   * Oculta y elimina un toast con animación.
   */
  function hideToast(toast) {
    toast.classList.remove('translate-x-0', 'opacity-100');
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => toast.remove(), 500); // espera a que termine la transición
  }

  /**
   * Sobrescribe la función alert() nativa.
   */
  window.alert = function (message, time = 5000, type = 'error') {
    showCustomAlert(message, time, type);
  };
  // Sobrescribimos confirm() con nuestra versión personalizada
  window.confirm = function (titulo, descripcion = "", btnText = "Aceptar", onConfirm = null) {
    // Obtener elementos del modal
    const modal = document.getElementById("customConfirmModal");
    const title = document.getElementById("confirmTitle");
    const text = document.getElementById("confirmText");
    const cancelBtn = document.getElementById("confirmCancelBtn");
    const okBtn = document.getElementById("confirmOkBtn");

    // Asignar contenido dinámico
    title.textContent = titulo || "Confirmar acción";
    text.textContent = descripcion || "¿Estás seguro de continuar?";
    okBtn.textContent = btnText || "Aceptar";

    // Mostrar el modal
    modal.classList.remove("hidden");

    // Limpiar eventos anteriores para evitar duplicados
    cancelBtn.onclick = okBtn.onclick = null;

    // Botón cancelar
    cancelBtn.onclick = () => {
      modal.classList.add("hidden");
    };

    // Botón aceptar
    okBtn.onclick = () => {
      modal.classList.add("hidden");
      if (typeof onConfirm === "function") {
        onConfirm(); // Ejecuta la acción
      }
    };
  };

  // Al iniciar, asegurarse de que el toast esté oculto
  $(document).ready(function () {
    $("#customAlertToast").addClass('translate-x-full opacity-0');
  });
</script>

</body>

</html>