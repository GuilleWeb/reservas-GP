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

    // Templates variados para cada tipo de sección
    function renderBlog(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map((p, idx) => {
        const image = p.imagen_path ? `/${p.imagen_path.replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1550831107-1553da8c8464?auto=format&fit=crop&q=80&w=900';
        const preview = (p.contenido || '').replace(/<[^>]+>/g, '').slice(0, 120);
        const baseBlogUrl = <?= json_encode(view_url('vistas/public/blog.php', $empresa_slug ?: $empresa_id)) ?>;
        const url = baseBlogUrl.replace(/\/$/, '') + '/' + encodeURIComponent(p.slug || p.id);
        const isLarge = idx === 0; // Primera card más grande
        return `
          <article class="${isLarge ? 'md:col-span-2 md:row-span-2' : ''} group relative bg-white rounded-3xl overflow-hidden shadow-xl transition-all duration-300 border border-slate-100">
            <div class="${isLarge ? 'h-64 md:h-80' : 'h-48'} overflow-hidden">
              <img src="${esc(image)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="${esc(p.titulo)}">
            </div>
            <div class="p-6 ${isLarge ? 'md:p-8' : ''}">
              <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                <i data-lucide="calendar" class="w-3 h-3"></i>
                <span>${esc(p.publicado_at || p.created_at || 'Reciente')}</span>
              </div>
              <h3 class="${isLarge ? 'text-xl md:text-2xl' : 'text-lg'} font-bold text-slate-800 group-hover:text-teal-600 transition-colors">${esc(p.titulo)}</h3>
              <p class="text-sm text-slate-500 mt-3 line-clamp-2">${esc(preview)}...</p>
              <a href="${esc(url)}" class="inline-flex items-center gap-1 text-teal-600 font-semibold text-sm mt-4 group-hover:gap-2 transition-all">
                Leer más <i data-lucide="arrow-right" class="w-4 h-4"></i>
              </a>
            </div>
          </article>
        `;
      }).join('');
      const blogUrl = <?= json_encode(view_url('vistas/public/blog.php', $empresa_slug ?: $empresa_id)) ?>;
      return `
        <section class="py-8">
          <div class="flex items-center justify-between mb-8">
            <div>
              <span class="text-teal-600 font-semibold text-sm uppercase tracking-wide">Publicaciones</span>
              <h2 class="text-3xl font-bold text-slate-900 mt-1">${esc(sec.titulo)}</h2>
            </div>
            <a href="${esc(blogUrl)}" class="hidden md:inline-flex items-center gap-2 text-slate-600 hover:text-teal-600 font-medium transition">
              Ver todas <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
          </div>
          <div class="grid md:grid-cols-3 gap-6">${cards}</div>
          <div class="mt-6 text-center md:hidden">
            <a href="${esc(blogUrl)}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-teal-600 text-white font-semibold hover:bg-teal-700">Ver blog completo</a>
          </div>
        </section>`;
    }

    function renderUsuarios(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(u => {
        const img = u.foto_path ? `/${String(u.foto_path).replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop';
        return `
          <div class="group text-center">
            <div class="relative w-32 h-32 mx-auto mb-4">
              <div class="absolute inset-0 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300 -m-1"></div>
              <img src="${esc(img)}" class="relative w-full h-full rounded-full object-cover border-4 border-white shadow-lg" alt="${esc(u.nombre)}">
              <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-teal-500 rounded-full flex items-center justify-center text-white shadow-md">
                <i data-lucide="check" class="w-4 h-4"></i>
              </div>
            </div>
            <h3 class="font-bold text-slate-900">${esc(u.nombre)}</h3>
            <p class="text-sm text-teal-600 capitalize font-medium">${esc(u.rol || 'profesional')}</p>
          </div>
        `;
      }).join('');
      return `
        <section class="py-12  bg-teal-200 rounded-3xl px-8">
          <div class="text-center mb-10">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-wide">Nuestro equipo</span>
            <h2 class="text-3xl font-bold text-slate-900 mt-2">${esc(sec.titulo)}</h2>
            <p class="text-slate-500 mt-3 max-w-lg mx-auto">Profesionales dedicados a brindarte la mejor experiencia</p>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8">${cards}</div>
        </section>`;
    }

    function renderResenas(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map(r => {
        const rating = Math.max(1, Math.min(5, parseInt(r.rating || 5, 10)));
        const stars = Array.from({ length: 5 }, (_, i) => `<i data-lucide="star" class="${i < rating ? 'text-amber-400 fill-amber-400' : 'text-slate-200'} w-4 h-4"></i>`).join('');
        return `
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative">
            <div class="absolute -top-3 left-6 w-6 h-6 bg-teal-500 rounded-lg flex items-center justify-center shadow-lg">
              <i data-lucide="quote" class="w-3 h-3 text-white"></i>
            </div>
            <div class="flex gap-1 mt-2">${stars}</div>
            <p class="mt-4 text-slate-600 text-sm leading-relaxed">"${esc(String(r.comentario || '').slice(0, 150))}"</p>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center text-white font-bold text-sm">
                ${esc((r.autor_nombre || 'C').charAt(0))}
              </div>
              <div>
                <div class="font-semibold text-slate-900 text-sm">${esc(r.autor_nombre || 'Cliente')}</div>
                <div class="text-xs text-slate-400">Cliente verificado</div>
              </div>
            </div>
          </div>
        `;
      }).join('');
      return `
        <section class="py-12">
          <div class="text-center mb-10">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-wide">Testimonios</span>
            <h2 class="text-3xl font-bold text-slate-900 mt-2">${esc(sec.titulo)}</h2>
          </div>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">${cards}</div>
        </section>`;
    }

    function renderServicios(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map((s, idx) => `
        <div class="group bg-white rounded-2xl p-6 border border-slate-100 hover:border-teal-200 hover:shadow-lg transition-all duration-300 ${idx === 0 ? 'md:col-span-2 bg-gradient-to-br from-teal-50 to-white' : ''}">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center text-teal-600">
                  <i data-lucide="${idx % 2 === 0 ? 'sparkles' : 'zap'}" class="w-4 h-4"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">${esc(s.nombre)}</h3>
              </div>
              <p class="text-sm text-slate-500 mt-2 line-clamp-2">${esc(s.descripcion || 'Servicio profesional de alta calidad')}</p>
            </div>
            <div class="text-right ml-4">
              <div class="text-2xl font-bold text-teal-600">$${Number(s.precio_base || 0).toFixed(0)}</div>
              <div class="text-xs text-slate-400">${esc(s.duracion_minutos)} min</div>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400 flex items-center gap-1">
              <i data-lucide="clock" class="w-3 h-3"></i> ${esc(s.duracion_minutos)} minutos
            </span>
            <button class="text-sm font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 group-hover:gap-2 transition-all">
              Agendar <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
          </div>
        </div>
      `).join('');
      const serviciosUrl = <?= json_encode(view_url('vistas/public/servicios.php', $empresa_slug ?: $empresa_id)) ?>;
      return `
        <section class="py-12 rounded-3xl px-8">
          <div class="flex items-center justify-between mb-8">
            <div>
              <span class="text-teal-600 font-semibold text-sm uppercase tracking-wide">Catálogo</span>
              <h2 class="text-3xl font-bold text-slate-900 mt-1">${esc(sec.titulo)}</h2>
            </div>
            <a href="${esc(serviciosUrl)}" class="hidden md:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium hover:border-teal-300 hover:text-teal-600 transition">
              Ver todos <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
          </div>
          <div class="grid md:grid-cols-2 gap-4">${cards}</div>
          <div class="mt-6 text-center md:hidden">
            <a href="${esc(serviciosUrl)}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-teal-600 text-white font-semibold hover:bg-teal-700">Ver todos los servicios</a>
          </div>
        </section>`;
    }

    function renderSucursales(sec) {
      if (!sec.items || !sec.items.length) return '';
      const cards = sec.items.map((s, idx) => {
        const img = s.foto_path ? `/${String(s.foto_path).replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=900';
        return `
        <div class="group relative rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 ${idx === 0 ? 'md:col-span-2 md:row-span-2' : ''}">
          <div class="${idx === 0 ? 'h-64 md:h-80' : 'h-48'} relative">
            <img src="${esc(img)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="${esc(s.nombre || 'sucursal')}">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
              <h3 class="font-bold text-lg ${idx === 0 ? 'md:text-xl' : ''}">${esc(s.nombre)}</h3>
              <p class="text-sm text-white/80 mt-1 flex items-center gap-1">
                <i data-lucide="map-pin" class="w-3 h-3"></i> ${esc(s.direccion || 'Dirección no disponible')}
              </p>
              ${s.telefono ? `<p class="text-sm text-white/70 mt-1 flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> ${esc(s.telefono)}</p>` : ''}
            </div>
          </div>
        </div>
      `;
      }).join('');
      return `
        <section class="py-12">
          <div class="text-center mb-10">
            <span class="text-teal-600 font-semibold text-sm uppercase tracking-wide">Ubicaciones</span>
            <h2 class="text-3xl font-bold text-slate-900 mt-2">${esc(sec.titulo)}</h2>
            <p class="text-slate-500 mt-3">Visítanos en cualquiera de nuestras sedes</p>
          </div>
          <div class="grid md:grid-cols-3 gap-6">${cards}</div>
        </section>`;
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
