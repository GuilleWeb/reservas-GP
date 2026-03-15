<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Página no encontrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans">
    <div class="max-w-lg w-full mx-4 bg-white rounded-2xl shadow-lg p-8 text-center border-t-4 border-teal-500">
        <div class="text-teal-400 text-6xl mb-4">
            <i data-lucide="map-signs"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">404</h1>
        <p class="text-lg text-gray-700 mb-1">La página que buscás no existe.</p>
        <p class="text-gray-500 text-sm mb-6">
            Es posible que la empresa o recurso solicitado no esté disponible o haya sido eliminado.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
            <a href="javascript:history.back()"
                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                <i data-lucide="arrow-left" class="mr-1"></i> Volver
            </a>
            <a href="<?php
            $parts = array_filter(explode('/', rtrim($_SERVER['SCRIPT_NAME'] ?? '', '/')), fn($p) => $p !== '');
            $root = '/' . (reset($parts) ?: '');
            echo htmlspecialchars($root . '/vistas/public/landing.php');
            ?>" class="px-5 py-2 bg-teal-600 text-white rounded-lg font-medium hover:bg-teal-700 transition">
                <i data-lucide="home" class="mr-1"></i> Ir al inicio
            </a>
        </div>

        <div class="border-t border-gray-100 pt-6">
            <p class="text-sm text-gray-500 mb-3">¿Tenés un negocio? Gestioná tus citas en línea con</p>
            <a href="https://reservasgp.com" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 text-white rounded-xl font-semibold shadow hover:bg-teal-700 transition text-sm">
                <i data-lucide="calendar-check"></i>
                Registrate gratis en Reservas-GP
            </a>
        </div>
    </div>
</body>

</html>