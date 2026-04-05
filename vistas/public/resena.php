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
$empresa_ref = (string) (($empresa['slug'] ?? '') ?: ($empresa['id'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));
$module = 'resena';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-3xl mx-auto px-4 py-10">
  <section class="bg-white border border-gray-100 rounded-3xl shadow-sm p-6 md:p-8">
    <h1 class="text-3xl font-black text-gray-900">Tu reseña</h1>
    <p class="text-sm text-gray-500 mt-1">Gracias por tu cita. Tu opinión nos ayuda a mejorar.</p>

    <div id="previewBox" class="mt-5 hidden bg-gray-50 border border-gray-100 rounded-2xl p-4 text-sm"></div>

    <form id="resenaForm" class="mt-6 space-y-4">
      <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
      <div>
        <label class="text-xs uppercase tracking-widest text-gray-500 font-black">Puntuación</label>
        <div id="stars" class="mt-2 flex items-center gap-2">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" data-rate="<?= $i ?>" class="starBtn h-10 w-10 rounded-xl border bg-white hover:bg-amber-50 text-gray-400">
              <i data-lucide="star" class="mx-auto"></i>
            </button>
          <?php endfor; ?>
        </div>
      </div>
      <div>
        <label class="text-xs uppercase tracking-widest text-gray-500 font-black">Comentario</label>
        <textarea id="comentario" class="mt-2 w-full border rounded-xl p-3 min-h-[120px]" maxlength="500" placeholder="Cuéntanos cómo fue tu experiencia..."></textarea>
      </div>
      <button id="btnEnviar" type="submit" class="px-5 py-3 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700">Enviar reseña</button>
    </form>
    <div id="msg" class="hidden mt-4 text-sm"></div>
  </section>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/public/resenas.php')) ?>;
    const empresaRef = <?= json_encode($empresa_ref) ?>;
    let rating = 0;

    function paintStars() {
      $('.starBtn').each(function () {
        const r = parseInt($(this).data('rate'), 10);
        const on = r <= rating;
        $(this).toggleClass('text-amber-500 border-amber-300 bg-amber-50', on)
               .toggleClass('text-gray-400', !on);
      });
      if (window.lucide) lucide.createIcons();
    }

    function showMsg(text, ok = false) {
      $('#msg').removeClass('hidden text-red-600 text-green-700').addClass(ok ? 'text-green-700' : 'text-red-600').text(text);
    }

    async function loadPreview() {
      const token = String($('#token').val() || '').trim();
      if (!token) {
        showMsg('Enlace de reseña inválido.');
        $('#resenaForm').hide();
        return;
      }
      try {
        const res = await $.get(API, { action: 'preview', id_e: empresaRef, token });
        if (res && res.success && res.data) {
          $('#previewBox').removeClass('hidden').html(`
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1">
                <div><span class="text-gray-500">Servicio:</span> <span class="font-semibold">${res.data.servicio_nombre || '-'}</span></div>
                <div><span class="text-gray-500">Sucursal:</span> <span class="font-semibold">${res.data.sucursal_nombre || '-'}</span></div>
                <div><span class="text-gray-500">Colaborador:</span> <span class="font-semibold">${res.data.empleado_nombre || '-'}</span></div>
              </div>
              <div class="space-y-1">
                <div><span class="text-gray-500">Fecha:</span> <span class="font-semibold">${res.data.fecha || '-'}</span></div>
                <div><span class="text-gray-500">Hora:</span> <span class="font-semibold">${res.data.hora || '-'}</span></div>
                <div><span class="text-gray-500">Duración:</span> <span class="font-semibold">${res.data.duracion ? (res.data.duracion + ' min') : '-'}</span></div>
                <div><span class="text-gray-500">Costo:</span> <span class="font-semibold">${res.data.precio || '-'}</span></div>
              </div>
            </div>
          `);
          return;
        }
        showMsg((res && res.message) || 'No se pudo validar el enlace.');
        $('#resenaForm').hide();
      } catch (e) {
        showMsg('No se pudo validar el enlace de reseña.');
        $('#resenaForm').hide();
      }
    }

    $('body').on('click', '.starBtn', function () {
      rating = parseInt($(this).data('rate'), 10) || 0;
      paintStars();
    });

    $('#resenaForm').on('submit', async function (e) {
      e.preventDefault();
      const token = String($('#token').val() || '').trim();
      const comentario = String($('#comentario').val() || '').trim();
      if (!token || rating < 1 || comentario.length < 5) {
        showMsg('Completa la puntuación y un comentario válido.');
        return;
      }
      const btn = $('#btnEnviar');
      const prev = btn.text();
      btn.prop('disabled', true).text('Enviando...');
      try {
        const res = await $.post(API, { action: 'submit', id_e: empresaRef, token, rating, comentario });
        if (res && res.success) {
          showMsg(res.message || 'Reseña enviada.', true);
          $('#resenaForm').hide();
        } else {
          showMsg((res && res.message) || 'No se pudo enviar la reseña.');
        }
      } catch (err) {
        showMsg('No se pudo enviar la reseña.');
      } finally {
        btn.prop('disabled', false).text(prev);
      }
    });

    paintStars();
    loadPreview();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
