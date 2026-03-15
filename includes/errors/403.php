<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso denegado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans">
    <div class="max-w-md w-full mx-4 bg-white rounded-2xl shadow-lg p-8 text-center border-t-4 border-red-500">
        <div class="text-red-400 text-6xl mb-4">
            <i data-lucide="lock"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Acceso denegado</h1>
        <p class="text-gray-600 mb-6">No tenés permiso para ver esta página o tu sesión ha expirado.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?php
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
            // Subir niveles para llegar a la raíz de la app
            $parts = array_filter(explode('/', $base), fn($p) => $p !== '');
            $root = '/' . (reset($parts) ?: '');
            echo htmlspecialchars($root . '/vistas/public/login.php');
            ?>" class="px-5 py-2 bg-teal-600 text-white rounded-lg font-medium hover:bg-teal-700 transition">
                <i data-lucide="log-in" class="mr-1"></i> Iniciar sesión
            </a>
            <a href="javascript:history.back()"
                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                <i data-lucide="arrow-left" class="mr-1"></i> Volver
            </a>
        </div>
    </div>
</body>

</html>