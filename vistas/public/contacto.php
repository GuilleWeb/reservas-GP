<?php
/**
 * Página de Contacto Público para Empresas
 * Muestra información de contacto + formulario para enviar mensajes al admin
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = get_empresa_slug();
if (!$slug) {
    header('Location: ' . app_url('vistas/public/login.php'));
    exit;
}

// Obtener información de la empresa
$empresa_info = get_current_empresa();
if (!$empresa_info) {
    http_response_code(404);
    include __DIR__ . '/../../includes/errors/404.php';
    exit;
}

$empresa_id = (int) ($empresa_info['id'] ?? 0);
$empresa_nombre = htmlspecialchars($empresa_info['nombre'] ?? 'Empresa');
$empresa_slogan = htmlspecialchars($empresa_info['slogan'] ?? '');
$logo_path = $empresa_info['logo_path'] ?? '';

// Configuración de contacto y colores
$config = json_decode($empresa_info['config_json'] ?? '{}', true);
$colores = json_decode($empresa_info['colores_json'] ?? '{}', true);
$color_p = $colores['principal'] ?? '#0d9488';
$email_contacto = htmlspecialchars($config['email_contacto'] ?? '');
$telefono_contacto = htmlspecialchars($config['telefono_contacto'] ?? '');
$direccion_general = htmlspecialchars($config['direccion_general'] ?? '');
$horario_general = htmlspecialchars($config['horario_general'] ?? '');

// Redes sociales
$redes = json_decode($empresa_info['redes_json'] ?? '{}', true) ?: [];

$module = 'Contacto';
include __DIR__ . '/../../includes/public_topbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-teal-900  shadow-lg mb-6">
            <?php if ($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="w-16 h-16 object-cover rounded-full">
            <?php else: ?>
                <i data-lucide="building-2" class="w-10 h-10 text-white"></i>
            <?php endif; ?>
        </div>
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2"><?= $empresa_nombre ?></h1>
        <?php if ($empresa_slogan): ?>
            <p class="text-lg text-teal-600 font-medium"><?= $empresa_slogan ?></p>
        <?php endif; ?>
    </div>

    <div class="grid lg:grid-cols-2 gap-12">
        <!-- Información de Contacto -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                    <i data-lucide="info" class="w-6 h-6 text-teal-600"></i>
                    Información de Contacto
                </h2>

                <div class="space-y-4">
                    <?php if ($email_contacto): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-teal-50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="mail" class="w-6 h-6 text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Correo electrónico</p>
                                <a href="mailto:<?= $email_contacto ?>" class="text-lg font-semibold text-gray-900 hover:text-teal-600 transition-colors">
                                    <?= $email_contacto ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($telefono_contacto): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-teal-50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="phone" class="w-6 h-6 text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Teléfono</p>
                                <a href="tel:<?= preg_replace('/[^0-9+]/', '', $telefono_contacto) ?>" class="text-lg font-semibold text-gray-900 hover:text-teal-600 transition-colors">
                                    <?= $telefono_contacto ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($direccion_general): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-teal-50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="map-pin" class="w-6 h-6 text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Dirección</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $direccion_general ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($horario_general): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-teal-50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="clock" class="w-6 h-6 text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Horario de atención</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $horario_general ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Redes Sociales -->
                <?php if (!empty($redes)): ?>
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <p class="text-sm font-medium text-gray-500 mb-4">Síguenos en redes sociales</p>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($redes as $platform => $url): 
                                if (empty($url)) continue;
                                $icon = match($platform) {
                                    'facebook' => 'facebook',
                                    'instagram' => 'instagram',
                                    'whatsapp' => 'message-circle',
                                    'tiktok' => 'clapperboard',
                                    'x' => 'twitter',
                                    'twitter' => 'twitter',
                                    default => 'globe'
                                };
                                $color = match($platform) {
                                    'facebook' => 'bg-blue-500 hover:bg-blue-600',
                                    'instagram' => 'bg-gradient-to-br from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600',
                                    'whatsapp' => 'bg-green-500 hover:bg-green-600',
                                    'tiktok' => 'bg-black hover:bg-gray-800',
                                    'x', 'twitter' => 'bg-gray-900 hover:bg-black',
                                    default => 'bg-teal-500 hover:bg-teal-600'
                                };
                            ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"
                                   class="w-12 h-12 rounded-xl <?= $color ?> flex items-center justify-center text-white transition-all transform hover:scale-110 shadow-md">
                                    <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de Contacto -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2 flex items-center gap-3">
                <i data-lucide="send" class="w-6 h-6 text-teal-600"></i>
                Envíanos un mensaje
            </h2>
            <p class="text-gray-500 mb-6">Completa el formulario y nos pondremos en contacto contigo lo antes posible.</p>

            <form id="contactForm" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
                <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre completo *</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                        <input type="text" name="nombre" required
                               class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                               placeholder="Tu nombre">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico *</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                        <input type="email" name="email" required
                               class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                               placeholder="tu@email.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa (opcional)</label>
                    <div class="relative">
                        <i data-lucide="building" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                        <input type="text" name="empresa_remitente"
                               class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none"
                               placeholder="Nombre de tu empresa">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje *</label>
                    <div class="relative">
                        <textarea name="mensaje" required rows="5"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all outline-none resize-none"
                                  placeholder="¿En qué podemos ayudarte?"></textarea>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-4 px-6 bg-teal-700 text-white font-bold rounded-xl shadow-lg shadow-teal-500/30 hover:shadow-xl hover:shadow-teal-500/40 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    Enviar mensaje
                </button>
            </form>

            <!-- Mensaje de éxito (oculto inicialmente) -->
            <div id="successMessage" class="hidden mt-6 p-4 rounded-xl bg-green-50 border border-green-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <i data-lucide="check" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-green-900">¡Mensaje enviado!</p>
                        <p class="text-sm text-green-700">Te contactaremos pronto.</p>
                    </div>
                </div>
            </div>

            <!-- Mensaje de error (oculto inicialmente) -->
            <div id="errorMessage" class="hidden mt-6 p-4 rounded-xl bg-red-50 border border-red-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-red-900">Error al enviar</p>
                        <p class="text-sm text-red-700" id="errorText">Intenta de nuevo más tarde.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Rate limiting: 15 minutos = 900 segundos
    var RATE_LIMIT_SECONDS = 900;
    var lastSentKey = 'contact_emp_last_sent_' + <?= $empresa_id ?>;
    
    function checkRateLimit() {
        var lastSent = localStorage.getItem(lastSentKey);
        if (!lastSent) return { allowed: true };
        
        var now = Date.now();
        var elapsed = (now - parseInt(lastSent)) / 1000;
        var remaining = Math.ceil(RATE_LIMIT_SECONDS - elapsed);
        
        if (elapsed < RATE_LIMIT_SECONDS) {
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            return { 
                allowed: false, 
                message: 'Has enviado un mensaje recientemente. Por favor espera ' + minutes + ' minutos' + (seconds > 0 ? ' y ' + seconds + ' segundos' : '') + ' antes de enviar otro.'
            };
        }
        return { allowed: true };
    }
    
    function setRateLimit() {
        localStorage.setItem(lastSentKey, Date.now().toString());
    }
    
    function showRateLimitError(message) {
        var $container = $('#contactForm');
        if (!$container.length) return;
        
        // Remover mensaje anterior
        $('#rateLimitMessage').remove();
        
        // Crear mensaje de error estilizado
        var errorHtml = '<div id="rateLimitMessage" class="mt-6 p-4 rounded-xl bg-red-50 border border-red-200">' +
            '<div class="flex items-start gap-3">' +
                '<div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">' +
                    '<i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>' +
                '</div>' +
                '<div class="flex-1">' +
                    '<p class="font-semibold text-red-900">' + message + '</p>' +
                    '<p class="text-sm text-red-700 mt-2">Mientras tanto, puedes contactarnos directamente por:</p>' +
                    '<div class="flex flex-wrap gap-3 mt-3">' +
                        '<?php if (!empty($telefono_contacto) || !empty($redes['whatsapp'])): ?>' +
                        '<a href="https://wa.me/<?= !empty($redes['whatsapp']) ? preg_replace('/[^0-9]/', '', $redes['whatsapp']) : preg_replace('/[^0-9]/', '', $telefono_contacto) ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">' +
                            '<i data-lucide="message-circle" class="w-4 h-4"></i>' +
                            'WhatsApp' +
                        '</a>' +
                        '<?php endif; ?>' +
                        '<?php if (!empty($email_contacto)): ?>' +
                        '<a href="mailto:<?= $email_contacto ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">' +
                            '<i data-lucide="mail" class="w-4 h-4"></i>' +
                            'Email' +
                        '</a>' +
                        '<?php endif; ?>' +
                        '<?php if (empty($telefono_contacto) && empty($redes['whatsapp']) && empty($email_contacto)): ?>' +
                        '<span class="text-sm text-gray-600">Revisa nuestra información de contacto arriba</span>' +
                        '<?php endif; ?>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $container.after(errorHtml);
        if (window.lucide) lucide.createIcons();
        
        // Scroll al mensaje
        $('#rateLimitMessage')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        
        // Verificar rate limit antes de enviar
        var rateCheck = checkRateLimit();
        if (!rateCheck.allowed) {
            showRateLimitError(rateCheck.message);
            return;
        }
        
        $btn.prop('disabled', true).html('<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Enviando...');
        if (window.lucide) lucide.createIcons();
        
        $.ajax({
            url: '<?= app_url('api/public/contacto.php') ?>',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    setRateLimit(); // Guardar timestamp
                    $form[0].reset();
                    $('#rateLimitMessage').remove(); // Quitar error si existe
                    $('#successMessage').removeClass('hidden');
                    $('#errorMessage').addClass('hidden');
                    setTimeout(function() { $('#successMessage').addClass('hidden'); }, 5000);
                } else {
                    // Error del backend (rate limit, validación, etc.)
                    showRateLimitError(res.message || 'Error al enviar el mensaje. Intenta de nuevo.');
                    $('#successMessage').addClass('hidden');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Error de conexión. Intenta de nuevo.';
                // Intentar obtener mensaje del error
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showRateLimitError(errorMsg + ' O contáctanos directamente por los canales de arriba.');
                $('#successMessage').addClass('hidden');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                if (window.lucide) lucide.createIcons();
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
