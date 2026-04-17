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
$empresa_slug = $empresa['slug'] ?? null;

$stmt = $pdo->prepare("SELECT data_json FROM empresa_home_config WHERE empresa_id = ? LIMIT 1");
$stmt->execute([$empresa_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$cfg = json_decode($row['data_json'] ?? '{}', true);
if (!is_array($cfg)) {
    $cfg = [];
}

$hero_visible = (int) ($cfg['hero_visible'] ?? 1) === 1;
$hero_tipo = max(1, min(3, (int) ($cfg['hero_tipo'] ?? 1)));
$hero_titulo = $cfg['hero_titulo'] ?? 'Bienvenid@';
$hero_subtitulo = $cfg['hero_subtitulo'] ?? $empresa_descripcion;
$hero_btn_texto = $cfg['hero_btn_texto'] ?? 'Agendar cita';
$hero_btn_link = $cfg['hero_btn_link'] ?? view_url('vistas/public/citas.php', $empresa_slug ?: $empresa_id);
$hero_imagen = $cfg['hero_imagen'] ?? 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=1200';

$module = 'inicio';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
  <?php if ($hero_visible): ?>
    <?php if ($hero_tipo === 2): ?>
      <section class="relative rounded-3xl overflow-hidden shadow-2xl border border-gray-100 min-h-[420px]">
        <img src="<?= htmlspecialchars((string) $hero_imagen) ?>" alt="Banner" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/45"></div>
        <div class="relative z-10 p-8 md:p-12 min-h-[420px] flex flex-col justify-center max-w-3xl">
          <h1 class="text-4xl md:text-5xl font-black text-white leading-tight"><?= htmlspecialchars((string) $hero_titulo) ?></h1>
          <p class="text-lg text-white/90 mt-3"><?= htmlspecialchars((string) $hero_subtitulo) ?></p>
          <div class="mt-6">
            <a href="<?= htmlspecialchars((string) $hero_btn_link) ?>" class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition">
              <i data-lucide="calendar-plus"></i><?= htmlspecialchars((string) $hero_btn_texto) ?>
            </a>
          </div>
        </div>
      </section>
    <?php elseif ($hero_tipo === 3): ?>
      <section class="relative rounded-3xl overflow-hidden shadow-2xl border border-gray-100 min-h-[320px] p-8 md:p-12 flex flex-col items-center justify-center text-center">
        <img src="<?= htmlspecialchars((string) $hero_imagen) ?>" alt="Banner" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/35"></div>
        <h1 class="relative z-10 text-4xl md:text-5xl font-black text-white leading-tight"><?= htmlspecialchars((string) $hero_titulo) ?></h1>
        <p class="relative z-10 text-lg text-white/90 mt-3 max-w-3xl"><?= htmlspecialchars((string) $hero_subtitulo) ?></p>
        <div class="relative z-10 mt-6">
          <a href="<?= htmlspecialchars((string) $hero_btn_link) ?>" class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition">
            <i data-lucide="calendar-plus"></i><?= htmlspecialchars((string) $hero_btn_texto) ?>
          </a>
        </div>
      </section>
    <?php else: ?>
      <section class="relative bg-white rounded-3xl overflow-hidden shadow-2xl border border-gray-100 flex flex-col md:flex-row min-h-[420px]">
        <div class="p-8 md:p-12 flex flex-col justify-center md:w-1/2 space-y-5">
          <h1 class="text-4xl md:text-5xl font-black text-gray-900 leading-tight"><?= htmlspecialchars((string) $hero_titulo) ?></h1>
          <p class="text-lg text-gray-600"><?= htmlspecialchars((string) $hero_subtitulo) ?></p>
          <div>
            <a href="<?= htmlspecialchars((string) $hero_btn_link) ?>"
              class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition">
              <i data-lucide="calendar-plus"></i><?= htmlspecialchars((string) $hero_btn_texto) ?>
            </a>
          </div>
        </div>
        <div class="md:w-1/2 bg-gray-100 relative">
          <img src="<?= htmlspecialchars((string) $hero_imagen) ?>" alt="Banner"
            class="absolute inset-0 w-full h-full object-cover">
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <section id="homeSections" class="space-y-14">
    <div id="homeLoading" class="bg-white rounded-2xl border p-6 text-gray-500">Cargando contenido...</div>
  </section>
</div>

<script>
  (function () {
    const apiHome = <?= json_encode(app_url('api/public/home_page.php')) ?>;
    const empresaRef = <?= json_encode($empresa_slug ?: (string) $empresa_id) ?>;
    const wrap = document.getElementById('homeSections');
    const loading = document.getElementById('homeLoading');

    function esc(v) {
      return $('<div>').text(v ?? '').html();
    }

    function sectionTitle(icon, title) {
      return `
        <div class="flex items-center gap-2 mb-6">
          <i data-lucide="${icon}" class="text-teal-600"></i>
          <h2 class="text-3xl font-bold text-teal-700">${esc(title)}</h2>
        </div>
      `;
    }

    function renderBlog(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(p => {
        const image = p.imagen_path ? `/${p.imagen_path.replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1550831107-1553da8c8464?auto=format&fit=crop&q=80&w=900';
        const preview = (p.contenido || '').replace(/<[^>]+>/g, '').slice(0, 120);
        const baseBlogUrl = <?= json_encode(view_url('vistas/public/blog.php', $empresa_slug ?: $empresa_id)) ?>;
        const url = baseBlogUrl + (baseBlogUrl.includes('?') ? '&' : '?') + 'id=' + encodeURIComponent(p.id);
        return `
          <article class="w-full max-w-md bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition">
            <div class="h-48 bg-gray-100"><img src="../../${esc(image)}" class="w-full h-full object-cover" alt="${esc(p.titulo)}"></div>
            <div class="p-5">
              <h3 class="text-lg font-bold text-gray-800">${esc(p.titulo)}</h3>
              <p class="text-sm text-gray-500 mt-2">${esc(preview)}...</p>
              <a href="${esc(url)}" class="inline-flex items-center text-teal-600 font-semibold text-sm mt-4">Leer más</a>
            </div>
          </article>
        `;
      }).join('');
      const blogUrl = <?= json_encode(view_url('vistas/public/blog.php', $empresa_slug ?: $empresa_id)) ?>;
      return `<section>${sectionTitle('newspaper', sec.titulo)}<div class="flex flex-wrap justify-center gap-6">${cards}</div><div class="mt-6 text-center"><a href="${esc(blogUrl)}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-teal-600 text-white font-semibold hover:bg-teal-700">Ver blog completo</a></div></section>`;
    }

    function renderUsuarios(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(u => {
        const img = u.foto_path ? `/${String(u.foto_path).replace(/^\/+/, '')}` : <?= json_encode(app_url('assets/logo.avif')) ?>;
        return `
          <div class="w-full max-w-sm bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
            <img src="${esc(img)}" class="w-24 h-24 rounded-full object-cover mx-auto border-4 border-teal-100" alt="${esc(u.nombre)}">
            <div class="mt-4 font-bold text-gray-800">${esc(u.nombre)}</div>
            <div class="text-sm text-teal-600 capitalize">${esc(u.rol || 'miembro')}</div>
          </div>
        `;
      }).join('');
      return `<section>${sectionTitle('users', sec.titulo)}<div class="flex flex-wrap justify-center gap-6">${cards}</div></section>`;
    }

    function renderResenas(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(r => {
        const rating = Math.max(1, Math.min(5, parseInt(r.rating || 5, 10)));
        const stars = Array.from({ length: 5 }, (_, i) => `<i data-lucide="star" class="${i < rating ? 'text-yellow-500' : 'text-yellow-200'} w-4 h-4"></i>`).join('');
        return `
          <div class="w-full max-w-md bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-1">${stars}</div>
            <p class="mt-3 text-sm text-gray-600">"${esc(String(r.comentario || '').slice(0, 100))}"</p>
            <div class="mt-4 font-semibold text-gray-800">${esc(r.autor_nombre || 'Cliente')}</div>
          </div>
        `;
      }).join('');
      return `<section>${sectionTitle('message-square-heart', sec.titulo)}<div class="flex flex-wrap justify-center gap-6">${cards}</div></section>`;
    }

    function renderServicios(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(s => `
        <div class="w-full max-w-md bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
          <div class="text-lg font-bold text-gray-900">${esc(s.nombre)}</div>
          <p class="text-sm text-gray-500 mt-2">${esc(s.descripcion || '')}</p>
          <div class="mt-4 text-sm text-teal-700 font-semibold">$${Number(s.precio_base || 0).toFixed(2)} · ${esc(s.duracion_minutos)} min</div>
        </div>
      `).join('');
      const serviciosUrl = <?= json_encode(view_url('vistas/public/servicios.php', $empresa_slug ?: $empresa_id)) ?>;
      return `<section>${sectionTitle('stethoscope', sec.titulo)}<div class="flex flex-wrap justify-center gap-6">${cards}</div><div class="mt-6 text-center"><a href="${esc(serviciosUrl)}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-teal-600 text-white font-semibold hover:bg-teal-700">Ver todos los servicios</a></div></section>`;
    }

    function renderSucursales(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(s => {
        const img = s.foto_path ? `/${String(s.foto_path).replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=900';
        return `
        <div class="w-full max-w-md bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
          <div class="h-44 rounded-xl overflow-hidden bg-gray-100 mb-4">
            <img src="${esc(img)}" class="w-full h-full object-cover" alt="${esc(s.nombre || 'sucursal')}">
          </div>
          <div class="font-bold text-gray-900">${esc(s.nombre)}</div>
          <div class="text-sm text-gray-500 mt-2">${esc(s.direccion || 'Dirección no disponible')}</div>
          <div class="text-sm text-gray-500 mt-1">${esc(s.telefono || '')}</div>
        </div>
      `;
      }).join('');
      return `<section>${sectionTitle('map-pin', sec.titulo)}<div class="flex flex-wrap justify-center gap-6">${cards}</div></section>`;
    }

    function renderSection(sec) {
      switch ((sec.modulo || '').toLowerCase()) {
        case 'blog': return renderBlog(sec);
        case 'usuarios': return renderUsuarios(sec);
        case 'resenas': return renderResenas(sec);
        case 'servicios': return renderServicios(sec);
        case 'sucursales': return renderSucursales(sec);
        default: return '';
      }
    }

    $.get(apiHome, { empresa: empresaRef }, function (res) {
      loading?.remove();
      if (!res || !res.success || !Array.isArray(res.sections)) {
        wrap.insertAdjacentHTML('beforeend', '<div class="bg-white rounded-2xl border p-6 text-gray-500">No se pudo cargar el contenido del inicio.</div>');
        return;
      }
      const html = res.sections.map(renderSection).join('');
      wrap.insertAdjacentHTML('beforeend', html || '<div class="bg-white rounded-2xl border p-6 text-gray-500">No hay secciones activas configuradas.</div>');
      if (window.lucide) lucide.createIcons();
    }, 'json').fail(function () {
      loading?.remove();
      wrap.insertAdjacentHTML('beforeend', '<div class="bg-white rounded-2xl border p-6 text-red-500">Error al obtener secciones del inicio.</div>');
    });
  })();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
