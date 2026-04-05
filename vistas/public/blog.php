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

$slug = $empresa['slug'] ?? null;
$postId = isset($_GET['id']) ? intval($_GET['id']) : null;
$module = 'blog';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header dinámico -->
    <div id="blogHeader" class="text-center mb-16 <?= $postId ? 'hidden' : '' ?>">
        <h1 class="text-5xl font-black text-gray-900 tracking-tight leading-tight">
            Blog de <span class="text-teal-600"><?= htmlspecialchars($empresa['nombre']) ?></span>
        </h1>
        <p class="mt-4 text-xl text-gray-500 max-w-2xl mx-auto italic">
            Artículos, consejos y noticias sobre nuestra labor.
        </p>
    </div>

    <!-- Contenedor de Listado -->
    <div id="listView" class="<?= $postId ? 'hidden' : '' ?>">
        <div id="postsGrid" class="flex flex-wrap justify-center gap-10">
            <!-- Cargado por JS -->
            <div class="col-span-full text-center py-20 text-gray-400">
                <i data-lucide="sync" class="text-4xl mb-4 text-teal-200 animate-spin"></i>
                <p>Cargando publicaciones...</p>
            </div>
        </div>

        <div id="pagination" class="mt-16 flex items-center justify-center gap-4">
            <!-- Paginación dinámica -->
        </div>
    </div>

    <!-- Contenedor de Detalle -->
    <div id="detailView" class="<?= $postId ? '' : 'hidden' ?>">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <aside class="lg:col-span-1 bg-white rounded-2xl border border-gray-100 p-4 h-fit shadow-sm">
                <div class="font-semibold text-gray-900 mb-3">Artículos Relacionados</div>
                <div id="relatedPosts" class="space-y-3 text-sm text-gray-600">
                    <div class="text-gray-400">Cargando...</div>
                </div>
            </aside>
            <div id="postContent"
                class="lg:col-span-3 bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
                <!-- Cargado por JS -->
            </div>
        </div>
    </div>

</div>

<script>
    $(function () {
        const slug = '<?= $slug ?>';
        const API_BLOG = '<?= app_url('api/public/blog.php') ?>';
        let currentPage = 1;

        function getExcerpt(html, length = 150) {
            const div = document.createElement('div');
            div.innerHTML = html;
            let text = div.textContent || div.innerText || "";
            return text.length > length ? text.substring(0, length) + '...' : text;
        }

        function loadList(page = 1) {
            currentPage = page;
            $('#postsGrid').html('<div class="col-span-full text-center py-20"><i data-lucide="sync" class="text-4xl text-teal-400 animate-spin"></i></div>');

            $.get(API_BLOG, { action: 'list', id_e: slug, page: page, per: 10 }, function (res) {
                const grid = $('#postsGrid').empty();
                const pag = $('#pagination').empty();

                if (res.success && res.data && res.data.length > 0) {
                    res.data.forEach(p => {
                        const img = p.imagen_path || 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=500';
                        const date = p.publicado_at ? new Date(p.publicado_at).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' }) : '';

                        grid.append(`
                        <article class="w-full max-w-md bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col h-full transform hover:-translate-y-2">
                            <div class="h-56 bg-gray-100 relative">
                                <img src="../../${img}" class="w-full h-full object-cover">
                                <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-[10px] font-black uppercase text-gray-900 tracking-widest shadow-sm">
                                    ${date}
                                </div>
                            </div>
                            <div class="p-8 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900 mb-3 leading-tight">${p.titulo}</h3>
                                    <p class="text-gray-500 text-sm mb-6">${getExcerpt(p.contenido)}</p>
                                </div>
                                <a href="<?= view_url('vistas/public/blog.php', $slug) ?>&id=${p.id}" class="inline-flex items-center text-teal-600 font-black text-sm uppercase tracking-wider group">
                                    Seguir leyendo
                                    <i data-lucide="arrow-right" class="ml-2 transition group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </article>
                    `);
                    });

                    // Simple pagination
                    if (res.total_pages > 1) {
                        for (let i = 1; i <= res.total_pages; i++) {
                            pag.append(`
                            <button class="w-10 h-10 rounded-full font-bold transition-all ${i === page ? 'bg-teal-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}" data-page="${i}">
                                ${i}
                            </button>
                        `);
                        }
                    }
                } else {
                    grid.html('<div class="col-span-full text-center py-20 text-gray-400 italic">No hay publicaciones disponibles de momento.</div>');
                }
            });
        }

        function loadDetail(id) {
            $('#postContent').html('<div class="p-20 text-center"><i data-lucide="sync" class="text-5xl text-teal-400 animate-spin"></i></div>');
            $.get(API_BLOG, { action: 'get', id_e: slug, id: id }, function (res) {
                if (res.success && res.data) {
                    const p = res.data;
                    const img = p.imagen_path || 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=1000';
                    const date = p.publicado_at ? new Date(p.publicado_at).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' }) : '';

                    $('#postContent').html(`
                    <div class="h-[450px] relative overflow-hidden">
                        <img src="../../${img}" class="w-full h-full object-cover">
                        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-white to-transparent"></div>
                    </div>
                    <div class="px-8 md:px-16 pb-16 -mt-10 relative">
                        <a href="<?= view_url('vistas/public/blog.php', $slug) ?>" class="inline-flex items-center text-xs font-black uppercase text-teal-600 tracking-widest mb-8 border-b-2 border-teal-600 pb-1">
                            &larr; Volver al blog
                        </a>
                        <h1 class="text-5xl font-black text-gray-900 mb-6 leading-tight">${p.titulo}</h1>
                        <div class="flex items-center gap-6 text-sm text-gray-500 mb-10 border-b pb-8">
                             <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="text-teal-500"></i>
                                <span>${date}</span>
                             </div>
                             <div class="flex items-center gap-2">
                                <i data-lucide="user" class="text-teal-600"></i>
                                <span>Por ${p.autor || 'Redacción'}</span>
                             </div>
                        </div>
                        <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed font-serif">
                            ${p.contenido}
                        </div>
                    </div>
                `);
                } else {
                    $('#postContent').html('<div class="p-20 text-center text-red-500 font-bold">Error: Publicación no encontrada.</div>');
                }
            });
            $.get(API_BLOG, { action: 'list', id_e: slug, page: 1, per: 10 }, function (res) {
                const box = $('#relatedPosts').empty();
                if (!(res && res.success && Array.isArray(res.data))) {
                    box.html('<div class="text-gray-400">Sin relacionados.</div>');
                    return;
                }
                const related = res.data.filter(p => parseInt(p.id, 10) !== parseInt(id, 10)).slice(0, 5);
                if (!related.length) {
                    box.html('<div class="text-gray-400">Sin relacionados.</div>');
                    return;
                }
                related.forEach(p => {
                    box.append(`<a href="<?= view_url('vistas/public/blog.php', $slug) ?>&id=${p.id}" class="block p-3 rounded-lg border hover:bg-gray-50"><div class="font-semibold text-gray-800 line-clamp-2">${p.titulo}</div></a>`);
                });
            });
        }

        $('body').on('click', '#pagination button', function () {
            loadList($(this).data('page'));
        });

        <?php if ($postId): ?>
            loadDetail(<?= $postId ?>);
        <?php else: ?>
            loadList(1);
        <?php endif; ?>
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
