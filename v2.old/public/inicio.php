<?php
require_once __DIR__ . '/../app/layout/topbar.php';
$empresa_info = $GLOBALS['empresa_info'] ?? null;
$empresa_id = $GLOBALS['empresa_id'] ?? null;
$id_e = $empresa_info['slug'] ?? request_id_e();

function home_default_config()
{
  return [
    'hero' => [
      'enabled' => true,
      'title' => 'Reserva en línea en minutos',
      'subtitle' => 'Agenda tu cita en línea y gestiona tu negocio desde un solo lugar.',
      'images' => [],
      'btn1' => ['text' => 'Agendar Cita', 'url' => 'public/agendar_cita.php'],
      'btn2' => ['text' => 'Ver Sedes', 'url' => 'public/sedes.php'],
    ],
    'about' => [
      'enabled' => true,
      'title' => 'Nuestra Misión y Visión',
      'mission' => 'Brindar un servicio de calidad, centrado en la experiencia de nuestros clientes.',
      'vision' => 'Ser reconocidos como un negocio líder en innovación, calidad humana y excelencia.',
    ],
    'blog' => [
      'enabled' => true,
      'post_ids' => [],
      'limit' => 3,
    ],
    'team' => [
      'enabled' => true,
      'user_ids' => [],
      'limit' => 4,
    ],
    'services' => [
      'enabled' => true,
      'service_ids' => [],
      'limit' => 4,
    ],
    'reviews' => [
      'enabled' => true,
      'review_ids' => [],
      'limit' => 3,
    ],
    'contact' => [
      'enabled' => true,
      'phone' => null,
      'email' => null,
      'address' => null,
      'social' => [],
    ],
  ];
}

function deep_merge_array($base, $over)
{
  if (!is_array($base))
    return $over;
  if (!is_array($over))
    return $base;
  foreach ($over as $k => $v) {
    if (array_key_exists($k, $base) && is_array($base[$k]) && is_array($v)) {
      $base[$k] = deep_merge_array($base[$k], $v);
    } else {
      $base[$k] = $v;
    }
  }
  return $base;
}

function v2_public_url($path, $id_e)
{
  $path = ltrim((string) $path, '/');
  $url = $path;
  if ($id_e) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . 'id_e=' . rawurlencode((string) $id_e);
  }
  return $url;
}

$cfg = home_default_config();
$home_cfg = null;
$extra_empresa = null;

if ($empresa_info && $empresa_id) {
  try {
    $stmt = $pdo->prepare('SELECT data_json FROM empresa_home_config WHERE empresa_id = ? LIMIT 1');
    $stmt->execute([(int) $empresa_id]);
    $json = $stmt->fetchColumn();
    $home_cfg = $json ? json_decode((string) $json, true) : null;
    if (is_array($home_cfg)) {
      $cfg = deep_merge_array($cfg, $home_cfg);
    }

    // Cargar datos extra de empresa para contacto/social
    $stmt = $pdo->prepare('SELECT slogan, descripcion, redes_json FROM empresas WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $empresa_id]);
    $extra_empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  } catch (Throwable $e) {
    $extra_empresa = null;
  }
}

$social = [];
if ($extra_empresa && !empty($extra_empresa['redes_json'])) {
  $sj = json_decode((string) $extra_empresa['redes_json'], true);
  if (is_array($sj))
    $social = $sj;
}

$posts = [];
$team = [];
$services = [];
$reviews = [];

if ($empresa_info && $empresa_id) {
  try {
    if (!empty($cfg['blog']['enabled'])) {
      $limit = max(1, (int) ($cfg['blog']['limit'] ?? 3));
      $ids = $cfg['blog']['post_ids'] ?? [];
      if (is_array($ids) && count($ids) > 0) {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));
        if ($ids) {
          $in = implode(',', array_fill(0, count($ids), '?'));
          $stmt = $pdo->prepare("SELECT id, titulo, contenido, imagen_path, publicado, publicado_at FROM blog_posts WHERE empresa_id=? AND id IN ($in) ORDER BY FIELD(id,$in)");
          $params = array_merge([(int) $empresa_id], $ids, $ids);
          $stmt->execute($params);
          $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      } else {
        $stmt = $pdo->prepare('SELECT id, titulo, contenido, imagen_path, publicado, publicado_at FROM blog_posts WHERE empresa_id=? AND publicado=1 ORDER BY publicado_at DESC, id DESC LIMIT ?');
        $stmt->bindValue(1, (int) $empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }

    if (!empty($cfg['team']['enabled'])) {
      $limit = max(1, (int) ($cfg['team']['limit'] ?? 4));
      $ids = $cfg['team']['user_ids'] ?? [];
      if (is_array($ids) && count($ids) > 0) {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));
        if ($ids) {
          $in = implode(',', array_fill(0, count($ids), '?'));
          $stmt = $pdo->prepare("SELECT id, nombre, foto_path FROM usuarios WHERE empresa_id=? AND id IN ($in) AND activo=1 ORDER BY FIELD(id,$in)");
          $params = array_merge([(int) $empresa_id], $ids, $ids);
          $stmt->execute($params);
          $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      } else {
        $stmt = $pdo->prepare("SELECT id, nombre, foto_path FROM usuarios WHERE empresa_id=? AND rol IN ('empleado','gerente') AND activo=1 ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, (int) $empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }

    if (!empty($cfg['services']['enabled'])) {
      $limit = max(1, (int) ($cfg['services']['limit'] ?? 4));
      $ids = $cfg['services']['service_ids'] ?? [];
      if (is_array($ids) && count($ids) > 0) {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));
        if ($ids) {
          $in = implode(',', array_fill(0, count($ids), '?'));
          $stmt = $pdo->prepare("SELECT id, nombre, descripcion, duracion_minutos, precio_base FROM servicios WHERE empresa_id=? AND id IN ($in) AND activo=1 ORDER BY FIELD(id,$in)");
          $params = array_merge([(int) $empresa_id], $ids, $ids);
          $stmt->execute($params);
          $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      } else {
        $stmt = $pdo->prepare('SELECT id, nombre, descripcion, duracion_minutos, precio_base FROM servicios WHERE empresa_id=? AND activo=1 ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, (int) $empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }

    if (!empty($cfg['reviews']['enabled'])) {
      $limit = max(1, (int) ($cfg['reviews']['limit'] ?? 3));
      $ids = $cfg['reviews']['review_ids'] ?? [];
      if (is_array($ids) && count($ids) > 0) {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));
        if ($ids) {
          $in = implode(',', array_fill(0, count($ids), '?'));
          $stmt = $pdo->prepare("SELECT id, autor_nombre, rating, titulo, comentario FROM resenas WHERE empresa_id=? AND id IN ($in) AND activo=1 ORDER BY FIELD(id,$in)");
          $params = array_merge([(int) $empresa_id], $ids, $ids);
          $stmt->execute($params);
          $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      } else {
        $stmt = $pdo->prepare('SELECT id, autor_nombre, rating, titulo, comentario FROM resenas WHERE empresa_id=? AND activo=1 AND visible_en_home=1 ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, (int) $empresa_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }
  } catch (Throwable $e) {
    // ignore
  }
}
?>

<?php if (!$empresa_info || !$id_e): ?>

    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow border">
      <div class="text-xl font-semibold text-gray-900">Empresa no definida</div>
      <div class="mt-2 text-gray-700">Esta vista pública requiere el parámetro <span class="font-mono">id_e</span>.</div>
      <div class="mt-4">
        <a class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg" href="../login.php">Ir al login</a>
      </div>
    </div>

<?php else: ?>

    <?php
    $hero = $cfg['hero'] ?? [];
    $about = $cfg['about'] ?? [];
    $contact = $cfg['contact'] ?? [];

    $hero_title = (string) ($hero['title'] ?? '');
    $hero_sub = (string) ($hero['subtitle'] ?? '');
    $btn1 = $hero['btn1'] ?? ['text' => 'Agendar Cita', 'url' => 'public/agendar_cita.php'];
    $btn2 = $hero['btn2'] ?? ['text' => 'Ver Sedes', 'url' => 'public/sedes.php'];
    $btn1_url = v2_public_url((string) ($btn1['url'] ?? 'public/agendar_cita.php'), $id_e);
    $btn2_url = v2_public_url((string) ($btn2['url'] ?? 'public/sedes.php'), $id_e);

    $about_title = (string) ($about['title'] ?? 'Nuestra Misión y Visión');
    $mission = (string) ($about['mission'] ?? '');
    $vision = (string) ($about['vision'] ?? '');

    $c_phone = $contact['phone'] ?? null;
    $c_email = $contact['email'] ?? null;
    $c_address = $contact['address'] ?? null;
    $c_social = is_array($contact['social'] ?? null) ? $contact['social'] : [];
    // Merge con redes_json de empresa si no se definió en home config
    $c_social = array_merge($social ?: [], $c_social);
    ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

      <?php if (!empty($hero['enabled'])): ?>
          <section class="bg-white rounded-3xl shadow-2xl overflow-hidden my-10">
            <div class="flex flex-col md:flex-row">
              <div class="md:w-1/2 p-8 md:p-12 lg:p-16 flex flex-col justify-center">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight"><?= htmlspecialchars($hero_title) ?>
                </h1>
                <p class="mt-4 text-xl text-gray-600"><?= htmlspecialchars($hero_sub) ?></p>
                <div class="mt-8 space-y-4 sm:space-y-0 sm:space-x-4">
                  <a href="<?= htmlspecialchars($btn1_url) ?>"
                    class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-full shadow-md text-white bg-teal-600 hover:bg-teal-700 transition">
                    <i class="fas fa-calendar-check mr-2"></i>
                    <?= htmlspecialchars((string) ($btn1['text'] ?? 'Agendar Cita')) ?>
                  </a>
                  <a href="<?= htmlspecialchars($btn2_url) ?>"
                    class="inline-flex items-center justify-center px-8 py-3 border border-teal-600 text-base font-bold rounded-full text-teal-700 bg-white hover:bg-teal-50 transition">
                    <i class="fas fa-store mr-2"></i> <?= htmlspecialchars((string) ($btn2['text'] ?? 'Ver Sedes')) ?>
                  </a>
                </div>
              </div>

              <div
                class="md:w-1/2 h-72 md:h-auto bg-gradient-to-br from-teal-50 to-white flex items-center justify-center p-10">
                <div class="text-center">
                  <div class="text-sm text-gray-500">Empresa</div>
                  <div class="mt-1 text-2xl font-extrabold text-gray-900">
                    <?= htmlspecialchars((string) ($empresa_info['nombre'] ?? '')) ?></div>
                  <?php if ($extra_empresa && !empty($extra_empresa['slogan'])): ?>
                      <div class="mt-2 text-gray-600"><?= htmlspecialchars((string) $extra_empresa['slogan']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($about['enabled']) && ($mission || $vision)): ?>
          <section class="my-16">
            <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left"><?= htmlspecialchars($about_title) ?>
            </h2>
            <div class="grid md:grid-cols-2 gap-8">
              <?php if ($mission): ?>
                  <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600">
                    <i class="fas fa-bullseye text-teal-600 text-4xl mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Nuestra Misión</h3>
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($mission)) ?></p>
                  </div>
              <?php endif; ?>
              <?php if ($vision): ?>
                  <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600">
                    <i class="fas fa-eye text-teal-600 text-4xl mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Nuestra Visión</h3>
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($vision)) ?></p>
                  </div>
              <?php endif; ?>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($cfg['team']['enabled']) && !empty($team)): ?>
          <section class="my-16">
            <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left"><i class="fas fa-users mr-2"></i> Nuestro
              Equipo</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
              <?php foreach ($team as $m): ?>
                  <?php
                  $img = !empty($m['foto_path']) ? (string) $m['foto_path'] : ('https://guillepalma.xo.je/placeholder/api.php?text=' . rawurlencode((string) ($m['nombre'] ?? '')));
                  ?>
                  <div class="bg-white shadow-md rounded-xl p-6 text-center hover:shadow-lg transition">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string) ($m['nombre'] ?? '')) ?>"
                      class="w-28 h-28 mx-auto rounded-full object-cover mb-4 border-4 border-teal-500">
                    <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars((string) ($m['nombre'] ?? '')) ?></h3>
                  </div>
              <?php endforeach; ?>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($cfg['services']['enabled']) && !empty($services)): ?>
          <section class="my-16">
            <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left"><i
                class="fas fa-concierge-bell mr-2"></i> Nuestros Servicios</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
              <?php foreach ($services as $s): ?>
                  <div class="bg-white rounded-2xl shadow-xl p-6 border-t-4 border-teal-600">
                    <div class="text-lg font-bold text-gray-900"><?= htmlspecialchars((string) ($s['nombre'] ?? '')) ?></div>
                    <?php if (!empty($s['descripcion'])): ?>
                        <div class="mt-2 text-sm text-gray-600"><?= htmlspecialchars((string) $s['descripcion']) ?></div>
                    <?php endif; ?>
                    <div class="mt-4 text-sm text-gray-500">
                      Duración: <span class="font-semibold"><?= (int) ($s['duracion_minutos'] ?? 0) ?> min</span>
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                      Precio: <span class="font-semibold">$<?= number_format((float) ($s['precio_base'] ?? 0), 2) ?></span>
                    </div>
                  </div>
              <?php endforeach; ?>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($cfg['reviews']['enabled']) && !empty($reviews)): ?>
          <section class="my-20">
            <h2 class="text-3xl font-bold text-teal-700 mb-10"><i class="fas fa-comments mr-2"></i> Lo que dicen nuestros
              clientes</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              <?php foreach ($reviews as $r): ?>
                  <?php
                  $autor = (string) ($r['autor_nombre'] ?? 'Cliente');
                  $rating = (int) ($r['rating'] ?? 5);
                  $coment = (string) ($r['comentario'] ?? '');
                  ?>
                  <div class="bg-white rounded-xl shadow-lg p-6 transition hover:shadow-xl">
                    <div class="flex items-center mb-4">
                      <div
                        class="w-12 h-12 rounded-full mr-4 border-2 border-teal-600 bg-teal-50 flex items-center justify-center text-teal-600 font-bold">
                        <?= htmlspecialchars(mb_substr($autor, 0, 1)) ?></div>
                      <div>
                        <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($autor) ?></h4>
                        <p class="text-xs text-gray-400">Cliente</p>
                      </div>
                    </div>
                    <p class="text-gray-600 italic text-sm">"<?= htmlspecialchars($coment) ?>"</p>
                    <div class="mt-4 flex text-sm">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                          <?php if ($i <= $rating): ?>
                              <i class="fas fa-star text-yellow-500 mr-1"></i>
                          <?php else: ?>
                              <i class="far fa-star text-yellow-300 mr-1"></i>
                          <?php endif; ?>
                      <?php endfor; ?>
                    </div>
                  </div>
              <?php endforeach; ?>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($cfg['blog']['enabled'])): ?>
          <section class="my-16">
            <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left"><i class="fas fa-newspaper mr-2"></i>
              Nuestro Blog</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
              <?php if (!empty($posts)): ?>
                  <?php foreach ($posts as $p): ?>
                      <?php
                      $img = !empty($p['imagen_path']) ? (string) $p['imagen_path'] : ('https://guillepalma.xo.je/placeholder/api.php?text=' . rawurlencode((string) ($p['titulo'] ?? 'Blog')));
                      $text = strip_tags((string) ($p['contenido'] ?? ''));
                      $excerpt = (mb_strlen($text) > 120) ? (mb_substr($text, 0, 120) . '...') : $text;
                      ?>
                      <article class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col border-t-4 border-teal-600">
                        <div class="h-48 bg-gray-100 overflow-hidden">
                          <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover" alt="">
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                          <h3 class="text-xl font-bold text-gray-800 hover:text-teal-700 transition">
                            <?= htmlspecialchars((string) ($p['titulo'] ?? '')) ?></h3>
                          <p class="text-sm text-gray-600 mt-2 flex-1"><?= htmlspecialchars($excerpt) ?></p>
                          <div class="mt-4">
                            <a href="<?= htmlspecialchars(v2_public_url('public/blog.php', $id_e)) ?>"
                              class="inline-flex items-center text-teal-600 font-semibold text-sm hover:text-teal-800">Ver blog <i
                                class="fas fa-chevron-right ml-1 text-xs"></i></a>
                          </div>
                        </div>
                      </article>
                  <?php endforeach; ?>
              <?php else: ?>
                  <div class="col-span-full text-center text-gray-500 py-6">Aún no hay publicaciones.</div>
              <?php endif; ?>
            </div>
            <div class="mt-10 text-center">
              <a href="<?= htmlspecialchars(v2_public_url('public/blog.php', $id_e)) ?>"
                class="inline-flex items-center text-xl font-semibold text-teal-600 hover:text-teal-800 transition">Ver todas
                las publicaciones <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
          </section>
      <?php endif; ?>

      <?php if (!empty($contact['enabled'])): ?>
          <section class="my-16">
            <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left"><i class="fas fa-envelope mr-2"></i>
              Contáctanos</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600">
                <h3 class="text-xl font-bold text-gray-800 mb-3">Información</h3>
                <p class="text-gray-600 mb-4">Estos datos se configuran por empresa.</p>

                <?php if ($c_phone): ?>
                    <p class="mb-2"><i class="fas fa-phone text-teal-600 w-6"></i> <?= htmlspecialchars((string) $c_phone) ?></p>
                <?php endif; ?>
                <?php if ($c_email): ?>
                    <p class="mb-2"><i class="fas fa-envelope text-teal-600 w-6"></i> <?= htmlspecialchars((string) $c_email) ?></p>
                <?php endif; ?>
                <?php if ($c_address): ?>
                    <p class="mb-2"><i class="fas fa-map-marker-alt text-teal-600 w-6"></i>
                      <?= htmlspecialchars((string) $c_address) ?></p>
                <?php endif; ?>

                <?php if (!empty($c_social)): ?>
                    <div class="mt-4 border-t pt-4">
                      <div class="text-sm font-semibold text-gray-900 mb-2">Redes</div>
                      <div class="flex flex-wrap gap-2">
                        <?php foreach ($c_social as $k => $v): ?>
                            <?php if (!is_string($v) || trim($v) === '')
                              continue; ?>
                            <a class="px-3 py-1 rounded-full border text-sm hover:bg-gray-50" href="<?= htmlspecialchars($v) ?>"
                              target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string) $k) ?></a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                <?php endif; ?>

                <div class="mt-6 border-t pt-4 text-sm text-gray-500">Soporte disponible de lunes a viernes en horarios hábiles.
                </div>
              </div>

              <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600">
                <h3 class="text-xl font-bold text-gray-800 mb-3">Envíanos un mensaje</h3>
                <p class="text-gray-600 mb-6">Próximamente: formulario de contacto.</p>
                <a class="inline-flex items-center justify-center px-6 py-3 rounded-full text-white bg-teal-600 hover:bg-teal-700 font-bold transition"
                  href="<?= htmlspecialchars(v2_public_url('public/agendar_cita.php', $id_e)) ?>">Agendar ahora</a>
              </div>
            </div>
          </section>
      <?php endif; ?>

    </div>

<?php endif; ?>

<?php
require_once __DIR__ . '/../app/layout/footer.php';
