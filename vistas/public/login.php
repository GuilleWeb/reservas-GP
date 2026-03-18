<?php
$module = 'login';
require_once __DIR__ . '/../../includes/bootstrap.php';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div
            class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 transform transition-all duration-500 hover:shadow-teal-100/50">
            <!-- Header -->
            <div class="p-8 text-center bg-gray-50/50 border-b border-gray-100">
                <div
                    class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-sm mb-6 overflow-hidden">
                    <img src="<?= $logo_path ?>" class="w-full h-full object-contain p-2">
                </div>
                <h2 class="text-1xl font-black text-gray-900 leading-tight">Bienvenid@ a</h2>
                <h2 class="text-3xl font-black text-teal-900 leading-tight"><?= htmlspecialchars($empresa_nombre) ?></h2>
                <p class="text-gray-500 text-sm mt-2 font-medium italic"><?= htmlspecialchars($empresa_slogan) ?>
                    </p>
            </div>

            <!-- Form -->
            <form id="publicLoginForm" class="p-8 space-y-6">
                <div id="loginAlert" class="hidden p-4 rounded-xl text-sm font-bold text-center"></div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Correo
                            Electrónico</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="email" id="email" name="email" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="tu@email.com">
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Contraseña</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="password" id="password" name="password" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs px-1">
                    <label class="flex items-center gap-2 text-gray-500 cursor-pointer select-none">
                        <input type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        Recordarme
                    </label>
                    <a href="#" class="text-teal-600 font-bold hover:underline">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" id="btnLogin"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-5 rounded-2xl font-black shadow-xl shadow-teal-100 transition transform active:scale-[0.98] flex items-center justify-center gap-3">
                    <span>Iniciar Sesión</span>
                    <i data-lucide="arrow-right" class="text-sm"></i>
                </button>
            </form>

            <!-- Footer -->
            <?php if (empty($id_e)): ?>
                <div class="px-8 pb-8 text-center border-t border-gray-50 pt-6">
                    <p class="text-xs text-gray-400 font-medium">¿Aún no tienes cuenta? <a href="#"
                            class="text-teal-600 font-bold hover:underline">Comienza ahora gratis</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center">
            <a href="<?= view_url('vistas/public/inicio.php', $slug) ?>"
                class="text-xs font-black text-gray-400 uppercase tracking-widest hover:text-teal-600 transition">&larr;
                Volver al inicio</a>
        </div>
    </div>
</div>

<script>
    $(function () {
        const API_AUTH = '<?= app_url('api/api-auth.php') ?>';

        $('#publicLoginForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $('#btnLogin');
            const alert = $('#loginAlert').addClass('hidden');

            btn.prop('disabled', true).html('<i data-lucide="loader-2" class="mr-2 animate-spin"></i> Verificando...');

            $.ajax({
                url: API_AUTH,
                type: 'POST',
                data: {
                    action: 'login',
                    email: $('#email').val(),
                    password: $('#password').val()
                },
                success: function (res) {
                    if (res.success) {
                        alert.removeClass('hidden bg-red-50 text-red-600').addClass('bg-teal-50 text-teal-600').text('Acceso exitoso. Redirigiendo...');
                        setTimeout(() => window.location.href = res.redirect_url, 1000);
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<span>Iniciar Sesión</span><i data-lucide="arrow-right" class="text-sm"></i>');
                    let msg = 'Credenciales inválidas.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        msg = res.message || msg;
                        if (res.error === 'session_active') {
                            confirm(
                                "Sesión activa",
                                res.message,
                                "Forzar ingreso",
                                function () {

                                    $.post(API_AUTH, {
                                        action: 'login',
                                        email: $('#email').val(),
                                        password: $('#password').val(),
                                        force_login: '1'
                                    }, function (res) {
                                        if (res.success) window.location.href = res.redirect_url;
                                    });

                                }
                            );

                            return;
                            //msg = 'Inicio de sesión cancelado.';
                        }
                    } catch (e) { }
                    alert.removeClass('hidden bg-teal-50 text-teal-600').addClass('bg-red-50 text-red-600').text(msg);
                }
            });
        });

        function forceLogin() {
            $.post(API_AUTH, {
                action: 'login',
                email: $('#email').val(),
                password: $('#password').val(),
                force_login: '1'
            }, function (res) {
                if (res.success) window.location.href = res.redirect_url;
            });
        }
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
