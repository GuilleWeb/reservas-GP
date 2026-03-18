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

$empresa_slug = $empresa['slug'] ?? null;
$module = 'inicio';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-20">

    <!-- Hero Section - Skeleton -->
    <section id="hero-section"
        class="relative bg-white rounded-3xl overflow-hidden shadow-2xl border border-gray-100 flex flex-col md:flex-row min-h-[500px]">
        <div class="p-10 md:p-16 flex flex-col justify-center md:w-1/2 space-y-6">
            <div class="skeleton h-16 w-3/4 mb-4"></div>
            <div class="skeleton h-8 w-full mb-2"></div>
            <div class="skeleton h-8 w-5/6 mb-6"></div>
            <div class="skeleton h-14 w-48 rounded-full"></div>
        </div>
        <div class="md:w-1/2 bg-gray-200 skeleton-image"></div>
    </section>

    <!-- About Section - Skeleton -->
    <section id="about-section" class="grid grid-cols-1 md:grid-cols-2 gap-10">
        <div class="bg-teal-50 p-10 rounded-3xl border border-teal-100">
            <div class="skeleton h-8 w-48 mb-6"></div>
            <div class="skeleton h-4 w-full mb-3"></div>
            <div class="skeleton h-4 w-full mb-3"></div>
            <div class="skeleton h-4 w-3/4 mb-6"></div>
            <div class="skeleton h-4 w-full mb-3"></div>
            <div class="skeleton h-4 w-5/6"></div>
        </div>
    </section>

    <!-- Servicios Section - Skeleton -->
    <section id="servicios-section">
        <div class="flex items-center justify-between mb-10">
            <div class="skeleton h-10 w-64"></div>
            <div class="skeleton h-6 w-24"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="bg-white p-6 rounded-2xl border border-gray-200">
                    <div class="skeleton h-12 w-12 rounded-xl mb-4"></div>
                    <div class="skeleton h-6 w-32 mb-3"></div>
                    <div class="skeleton h-4 w-full mb-2"></div>
                    <div class="skeleton h-4 w-3/4"></div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Blog Section - Skeleton -->
    <section class="my-16 reveal visible">
        <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left">
            <i data-lucide="newspaper" class="mr-2"></i> Nuestro Blog
        </h2>
        <div id="postsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        </div>
        <div class="mt-10 text-center">
            <a href="blog"
                class="inline-flex items-center text-xl font-semibold text-teal-600 hover:text-teal-800 transition duration-300">
                Ver todas las publicaciones <i data-lucide="arrow-right" class="ml-2"></i>
            </a>
        </div>
    </section>

    <!-- Equipo Section - Skeleton -->
    <section class="my-16 reveal visible">
        <h2 class="text-3xl font-bold text-teal-700 mb-8 text-center sm:text-left">
            <i data-lucide="user" class="mr-2"></i> Nuestro Equipo
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

        </div>
    </section>

    <!-- Contacto Section - Skeleton -->
    <section id="contacto-section">
        <div class="skeleton h-10 w-48 mx-auto mb-10"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="bg-white p-8 rounded-3xl border shadow-xl">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="skeleton h-12 w-full rounded-xl"></div>
                        <div class="skeleton h-12 w-full rounded-xl"></div>
                    </div>
                    <div class="skeleton h-12 w-full rounded-xl"></div>
                    <div class="skeleton h-32 w-full rounded-xl"></div>
                    <div class="skeleton h-14 w-full rounded-xl"></div>
                </div>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <div class="skeleton h-4 w-32 mb-4"></div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="skeleton h-10 w-10 rounded-full"></div>
                            <div class="skeleton h-6 w-40"></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="skeleton h-10 w-10 rounded-full"></div>
                            <div class="skeleton h-6 w-40"></div>
                        </div>
                    </div>
                </div>
                <div class="pt-4 border-t">
                    <div class="skeleton h-4 w-32 mb-4"></div>
                    <div id="miniSedes-skeleton" class="grid grid-cols-1 gap-3">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="skeleton h-16 w-full rounded-xl"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<style>
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
    }

    .skeleton-image {
        background: linear-gradient(90deg, #e0e0e0 25%, #d0d0d0 50%, #e0e0e0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }
</style>

<script>
    $(function () {
        const empresa_slug = '<?= $empresa['slug'] ?>';
        const slug = '<?= $empresa['slug'] ?>';
        const apiUrl = '<?= app_url('api/public/inicio.php') ?>';

        function loadSection(section, action, callback) {
            $.ajax({
                url: apiUrl,
                method: 'GET',
                data: {
                    action: action,
                    empresa_id: empresa_slug,
                    slug: slug
                },
                success: function (response) {
                    if (response.success) {
                        callback(response.data);
                    } else {
                        console.error('Error cargando ' + section, response.error);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error en petición de ' + section, error);
                }
            });
        }

        // Cargar Hero
        loadSection('hero', 'get_hero', function (data) {
            if (!data.hero_visible) {
                $('#hero-section').remove();
                return;
            }

            const heroHtml = `
                <div class="p-10 md:p-16 flex flex-col justify-center md:w-1/2 space-y-6">
                    <h1 class="text-5xl font-black text-gray-900 leading-tight">
                        ${data.hero_titulo}
                    </h1>
                    <p class="text-xl text-gray-600">
                        ${data.hero_subtitulo}
                    </p>
                    <div class="pt-4 flex flex-wrap gap-4">
                        <a href="<?= view_url('vistas/public/citas.php', $empresa_slug) ?>"
                            class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-4 rounded-full font-bold shadow-lg transition transform hover:scale-105">
                            ${data.hero_btn_texto}
                        </a>
                    </div>
                </div>
                <div class="md:w-1/2 bg-gray-100 relative">
                    <img src="${data.hero_imagen}" class="absolute inset-0 w-full h-full object-cover">
                </div>
            `;
            $('#hero-section').html(heroHtml);
        });

        // Cargar About
        loadSection('about', 'get_about', function (data) {
            if (!data.about_visible) {
                $('#about-section').remove();
                return;
            }

            const aboutHtml = `
                <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600 text-center">
                    <i data-lucide="bullseye" class="text-teal-600 text-4xl mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Nuestra Misión</h3>
                    <p class="text-gray-600 leading-relaxed">${data.mision.replace(/\n/g, '<br>')}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-teal-600 text-center">
                    <i data-lucide="eye" class="text-teal-600 text-4xl mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Nuestra Vision</h3>
                    <p class="text-gray-600 leading-relaxed">${data.vision.replace(/\n/g, '<br>')}</p>
                </div>
            `;
            $('#about-section').html(aboutHtml);
        });

        // Cargar Servicios
        loadSection('servicios', 'get_servicios', function (data) {
            if (!data.visible || !data.items.length) {
                $('#servicios-section').remove();
                return;
            }

            let serviciosHtml = `
                <div class="flex items-center justify-between mb-10">
                    <h2 class="text-3xl font-black text-gray-900">Nuestros Servicios Destacados</h2>
                    <a href="<?= app_url('vistas/public/servicios.php') ?>?id_e=${empresa_slug}" class="text-teal-600 font-bold hover:underline">Ver todos &rarr;</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            `;

            data.items.forEach(s => {
                serviciosHtml += `
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm transition hover:shadow-md">
                        <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600 text-xl mb-4">
                            <i data-lucide="${s.icono" class="|| 'magic'}"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 text-lg mb-2">${s.nombre}</h3>
                        <p class="text-gray-500 text-sm line-clamp-2">${s.descripcion || ''}</p>
                    </div>
                `;
            });

            serviciosHtml += '</div>';
            $('#servicios-section').html(serviciosHtml);
        });

        // Cargar Blog
        loadSection('blog', 'get_blog', function (data) {
            if (!data.visible || !data.items.length) {
                $('#blog-section').remove();
                return;
            }

            let blogHtml = `
                <h2 class="text-3xl font-black text-gray-900 mb-10">Lo último en nuestro blog</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            `;

            data.items.forEach(p => {
                const imagen = p.imagen_path || 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=500';
                const contenidoCorto = p.contenido ? p.contenido.substring(0, 100) + '...' : '';

                blogHtml += `
                    <article class="bg-white rounded-lg shadow-lg post-card overflow-hidden">
                        <div class="h-48 bg-gray-100 overflow-hidden">
                            <img src="../../${imagen}"
                                alt="El Impacto de la Contaminación en la Salud Respiratoria"
                                class="w-full h-full object-cover">
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold text-gray-800 hover:text-teal-700 transition duration-150">${p.titulo}</h3>
                            <p class="text-sm text-gray-600 mt-2">${contenidoCorto}...</p>
                            <div class="mt-4">
                                <a href="<?= view_url('vistas/public/blog.php', $empresa_slug) ?>&id=${p.id}"
                                    class="inline-flex items-center text-teal-600 font-semibold text-sm hover:text-teal-800 transition duration-150">
                                    Leer artículo completo <i data-lucide="chevron-right" class="ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                `;
            });

            blogHtml += '</div>';
            $('#blog-section').html(blogHtml);
        });

        // Cargar Equipo
        loadSection('equipo', 'get_equipo', function (data) {
            if (!data.visible || !data.items.length) {
                $('#equipo-section').remove();
                return;
            }

            let equipoHtml = `
            `;

            data.items.forEach(e => {
                const imagen = e.imagen_path || 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=300';

                equipoHtml += `
                    <div class="bg-white shadow-md rounded-xl p-6 text-center hover:shadow-lg transition duration-300">
                        <img src="${imagen}" alt="Dra. Ana López" class="w-32 h-32 mx-auto rounded-full object-cover mb-4 border-4 border-teal-500">
                        <h3 class="text-lg font-semibold text-gray-800">${e.nombre}</h3>
                        <p class="text-sm text-teal-600 font-medium">${e.especialidad || 'Profesional'}</p>
                        <p class="mt-3 text-sm text-gray-500">${e.descripcion || 'Profesional'}</p>
                    </div>
                `;
            });

            equipoHtml += '</div>';
            $('#equipo-section').html(equipoHtml);
        });

        // Cargar Información de Contacto
        loadSection('contacto', 'get_contacto', function (data) {
            if (!data.contacto_visible) {
                $('#contacto-section').remove();
                return;
            }

            const contactoHtml = `
                <h2 class="text-3xl font-black text-gray-900 mb-10 text-center">Contáctanos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
                    <div class="bg-white p-8 rounded-3xl border shadow-xl">
                        <form id="publicContactForm" class="space-y-4">
                            <input type="hidden" name="empresa_id" value="${data.empresa_id}">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" name="nombre" placeholder="Nombre" class="w-full p-3 bg-gray-50 border rounded-xl" required>
                                <input type="email" name="email" placeholder="Email" class="w-full p-3 bg-gray-50 border rounded-xl" required>
                            </div>
                            <input type="text" name="asunto" placeholder="Asunto" class="w-full p-3 bg-gray-50 border rounded-xl" required>
                            <textarea name="mensaje" placeholder="Tu mensaje..." class="w-full p-3 bg-gray-50 border rounded-xl" rows="4" required></textarea>
                            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white py-4 rounded-xl font-bold shadow-lg transition transform hover:scale-[1.02]">
                                Enviar Mensaje
                            </button>
                            <div id="contactResult" class="hidden text-center font-bold text-sm"></div>
                        </form>
                    </div>
                    <div class="p-8 space-y-6">
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Canales de Atención</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center">
                                        <i data-lucide="phone"></i>
                                    </div>
                                    <span class="font-bold text-gray-700">${data.telefono}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center">
                                        <i data-lucide="mail"></i>
                                    </div>
                                    <span class="font-bold text-gray-700">${data.email}</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Nuestras Sedes</h3>
                            <div id="miniSedes" class="grid grid-cols-1 gap-3">
                                <!-- Se cargarán por separado -->
                            </div>
                            <a href="<?= view_url('vistas/public/ver-sedes.php', $empresa_slug) ?>" class="block text-center mt-6 text-sm font-bold text-gray-500 hover:text-teal-600 transition">
                                Ver en el mapa &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            `;

            $('#contacto-section').html(contactoHtml);

            // Cargar mini sedes
            $.ajax({
                url: apiUrl,
                method: 'GET',
                data: {
                    action: 'get_sucursales_mini',
                    empresa_id: empresa_slug,
                    slug: slug
                },
                success: function (res) {
                    if (res.success && res.data) {
                        const div = $('#miniSedes');
                        div.empty();
                        res.data.forEach(s => {
                            div.append(`
                                <div class="p-3 border rounded-xl flex items-center justify-between text-sm bg-gray-50">
                                    <span class="font-bold text-gray-800">${s.nombre}</span>
                                    <span class="text-gray-500 text-xs">${s.direccion || ''}</span>
                                </div>
                            `);
                        });
                    }
                }
            });
        });

        // Manejar envío del formulario de contacto (se delegará al formulario cuando exista)
        $(document).on('submit', '#publicContactForm', function (e) {
            e.preventDefault();
            const btn = $(this).find('button');
            const resDiv = $('#contactResult').removeClass('text-teal-600 text-red-600').addClass('hidden');
            btn.prop('disabled', true).text('Enviando...');

            // Aquí iría la llamada real a la API de contacto
            setTimeout(() => {
                btn.prop('disabled', false).text('Enviar Mensaje');
                resDiv.removeClass('hidden').addClass('text-teal-600').text('¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.');
                $('#publicContactForm')[0].reset();
            }, 1500);
        });
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
