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
$module = 'sedes';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-teal-800 tracking-tight sm:text-5xl">
            Nuestras Sedes - <?= htmlspecialchars($empresa['nombre']) ?>
        </h1>
        <p class="mt-4 text-xl text-gray-600">
            Encuentra la ubicación más conveniente para ti.
        </p>
    </div>

    <div id="sedesGrid" class="flex flex-wrap justify-center gap-8">
        <div id="loading" class="md:col-span-2 lg:col-span-3 text-center text-gray-500 text-lg">
            Cargando sedes...
        </div>
    </div>
</div>

<script>
    $(function () {
        const citaBase = '<?= view_url('vistas/public/citas.php', $slug) ?>';
        const withQuery = (url, key, value) => `${url}${String(url).includes('?') ? '&' : '?'}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
        $.get('<?= app_url('api/sucursal/sucursales.php') ?>', {
            action: 'list',
            id_e: '<?= $slug ?>'
        }, function (res) {
            $('#loading').remove();
            const grid = $('#sedesGrid');

            if (res.success && res.data.length > 0) {
                res.data.forEach(s => {
                    const imageUrl = s.imagen_path || "https://guillepalma.xo.je/placeholder/api.php?text=" + encodeURIComponent(s.nombre);

                    const card = $(`
                        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col">
                            <div class="h-48 bg-gray-100 overflow-hidden">
                                <img src="${imageUrl}" class="w-full h-full object-cover">
                            </div>
                            
                            <div class="p-6 flex-1 space-y-4">
                                <h2 class="text-2xl font-black text-gray-900">${s.nombre}</h2>
                                
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="map-pin" class="text-teal-500 mt-1"></i>
                                        <span>${s.direccion || 'Dirección no disponible'}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="phone" class="text-teal-500"></i>
                                        <span>${s.telefono || 'Sin teléfono'}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 border-t bg-gray-50 flex gap-3">
                                <a href="${withQuery(citaBase, 'sede_id', s.id)}" 
                                   class="flex-1 bg-teal-600 text-white font-bold py-3 rounded-xl text-center text-sm shadow-md hover:bg-teal-700 transition">
                                    Agendar Aquí
                                </a>
                                <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(s.direccion + ' ' + s.nombre)}" target="_blank"
                                   class="px-4 bg-white text-gray-700 border border-gray-200 font-bold py-3 rounded-xl text-center text-sm hover:bg-gray-100 transition">
                                    <i data-lucide="map"></i>
                                </a>
                            </div>
                        </div>
                    `);
                    grid.append(card);
                });
            } else {
                grid.append('<div class="w-full text-center text-gray-500 py-20">No hemos encontrado sedes activas para esta empresa.</div>');
            }
        });
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
