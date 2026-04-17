<?php
$module = 'login';
require_once __DIR__ . '/../../includes/bootstrap.php';
$u = current_user();
if ($u) {
    $role = (string) ($u['rol'] ?? '');
    $eid = (int) (resolve_private_empresa_id($u) ?: ((int) ($u['empresa_id'] ?? 0)));
    if ($role === 'superadmin' && $eid <= 0) {
        header('Location: ' . view_url('vistas/superadmin/dashboard.php'));
        exit;
    }
    if (in_array($role, ['superadmin', 'admin'], true)) {
        header('Location: ' . view_url('vistas/admin/dashboard.php', $eid));
        exit;
    }
    if ($role === 'gerente') {
        header('Location: ' . view_url('vistas/sucursal/dashboard.php', $eid));
        exit;
    }
    if ($role === 'empleado') {
        header('Location: ' . view_url('vistas/empleado/dashboard.php', $eid));
        exit;
    }
    if ($role === 'cliente') {
        $empresa_ref = (string) (($empresa_info['slug'] ?? '') ?: ($eid > 0 ? (string) $eid : ''));
        header('Location: ' . view_url('vistas/public/citas.php', $empresa_ref !== '' ? $empresa_ref : null));
        exit;
    }
}
$recover_token = trim((string) ($_GET['token'] ?? ''));
$recover_mode = (($_GET['recover'] ?? '') === '1') || ($recover_token !== '');
$login_empresa_ref = '';
if (isset($empresa_info) && is_array($empresa_info)) {
    $login_empresa_ref = (string) (($empresa_info['slug'] ?? '') ?: ($empresa_info['id'] ?? ''));
}
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
                    <button id="btnOpenRecover" type="button" class="text-teal-600 font-bold hover:underline">¿Olvidaste tu contraseña?</button>
                </div>

                <button type="submit" id="btnLogin"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-5 rounded-2xl font-black shadow-xl shadow-teal-100 transition transform active:scale-[0.98] flex items-center justify-center gap-3">
                    <span>Iniciar Sesión</span>
                    <i data-lucide="arrow-right" class="text-sm"></i>
                </button>
            </form>

            <form id="recoverRequestForm" class="hidden p-8 pt-0 space-y-4">
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                    <div class="text-sm font-black text-gray-800">Recuperar contraseña</div>
                    <div class="text-xs text-gray-500 mt-1">Te enviaremos un enlace seguro a tu correo.</div>
                </div>
                <input type="email" id="recover_email" class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all" placeholder="tu@email.com" required>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-3 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700">Enviar enlace</button>
                    <button id="btnBackLogin" type="button" class="px-4 py-3 rounded-xl border font-semibold hover:bg-gray-50">Volver</button>
                </div>
            </form>

            <form id="recoverResetForm" class="<?= $recover_mode ? '' : 'hidden' ?> p-8 pt-0 space-y-4">
                <div class="bg-teal-50 border border-teal-100 rounded-2xl p-4">
                    <div class="text-sm font-black text-teal-800">Restablecer contraseña</div>
                    <div class="text-xs text-teal-700 mt-1">Define tu nueva contraseña para continuar.</div>
                </div>
                <input type="password" id="recover_new_password" class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all" placeholder="Nueva contraseña (mínimo 6 caracteres)" required>
                <input type="password" id="recover_new_password2" class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all" placeholder="Confirmar contraseña" required>
                <input type="hidden" id="recover_token" value="<?= htmlspecialchars($recover_token) ?>">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-3 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700">Actualizar contraseña</button>
                    <a href="<?= htmlspecialchars(view_url('vistas/public/login.php', $login_empresa_ref !== '' ? $login_empresa_ref : null)) ?>" class="px-4 py-3 rounded-xl border font-semibold hover:bg-gray-50">Cancelar</a>
                </div>
            </form>

            <!-- Formulario de Registro -->
            <form id="registerForm" class="hidden p-8 space-y-5">
                <div id="registerAlert" class="hidden p-4 rounded-xl text-sm font-bold text-center"></div>

                <div class="bg-teal-50 border border-teal-100 rounded-2xl p-4 mb-2">
                    <div class="text-sm font-black text-teal-800">Crear cuenta gratis</div>
                    <div class="text-xs text-teal-700 mt-1">Registra tu empresa y comienza a gestionar citas.</div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nombre completo</label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="text" id="reg_nombre" name="nombre_completo" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="Tu nombre completo">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Correo Electrónico</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="email" id="reg_email" name="email" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="tu@email.com">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Contraseña</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="password" id="reg_password" name="password" required minlength="6"
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nombre de tu empresa</label>
                        <div class="relative group">
                            <i data-lucide="building-2" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 transition-colors group-focus-within:text-teal-500"></i>
                            <input type="text" id="reg_empresa" name="nombre_empresa" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all placeholder:text-gray-300"
                                placeholder="Ej: Mi Clínica">
                        </div>
                        <p class="text-xs text-gray-400 mt-1 ml-1">Solo letras y números. Este nombre será único en el sistema.</p>
                    </div>
                </div>

                <button type="submit" id="btnRegister"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white py-5 rounded-2xl font-black shadow-xl shadow-teal-100 transition transform active:scale-[0.98] flex items-center justify-center gap-3">
                    <span>Crear mi cuenta</span>
                    <i data-lucide="user-plus" class="text-sm"></i>
                </button>

                <div class="text-center">
                    <button type="button" id="btnBackToLogin" class="text-teal-600 font-bold hover:underline text-sm">
                        Ya tengo cuenta, iniciar sesión
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <?php if (empty($id_e)): ?>
                <div id="loginFooter" class="px-8 pb-8 text-center border-t border-gray-50 pt-6 space-y-3">
                    <p class="text-xs text-gray-400 font-medium">El registro de clientes se realiza al agendar una cita.</p>
                    <div class="pt-2">
                        <span class="text-xs text-gray-500">¿No tienes cuenta?</span>
                        <button type="button" id="btnShowRegister" class="text-teal-600 font-bold hover:underline text-sm ml-1">
                            Crear cuenta gratis
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center">
            <?php
            $slugPublic = $slug ?? ($empresa_info['slug'] ?? '');
            $backHome = $slugPublic ? view_url('vistas/public/inicio.php', $slugPublic) : (rtrim(app_url(''), '/') . '/');
            ?>
            <a href="<?= htmlspecialchars($backHome) ?>"
                class="text-xs font-black text-gray-400 uppercase tracking-widest hover:text-teal-600 transition">&larr;
                Volver al inicio</a>
        </div>
    </div>
</div>

<script>
    $(function () {
        const API_AUTH = '<?= app_url('api/api-auth.php') ?>';
        const recoverMode = <?= json_encode($recover_mode) ?>;
        const urlParams = new URLSearchParams(window.location.search);
        const verifyMode = urlParams.get('verify') === '1';
        const verifyToken = String(urlParams.get('token') || '').trim();

        if (verifyMode && verifyToken) {
            $.post(API_AUTH, { action: 'verify_email', token: verifyToken }, function (res) {
                if (res && res.success) {
                    showCustomAlert(res.message || 'Correo verificado correctamente.', 4500, 'success');
                    urlParams.delete('verify');
                    urlParams.delete('token');
                    const clean = window.location.pathname + (urlParams.toString() ? ('?' + urlParams.toString()) : '');
                    window.history.replaceState({}, '', clean);
                } else {
                    showCustomAlert((res && res.message) || 'No se pudo verificar el correo.', 4500, 'error');
                }
            }, 'json').fail(function () {
                showCustomAlert('No se pudo verificar el correo.', 4500, 'error');
            });
        }

        function showRecoverRequest() {
            $('#publicLoginForm').addClass('hidden');
            $('#recoverResetForm').addClass('hidden');
            $('#recoverRequestForm').removeClass('hidden');
        }
        function showLoginForm() {
            $('#recoverRequestForm').addClass('hidden');
            if (!recoverMode) {
                $('#recoverResetForm').addClass('hidden');
                $('#publicLoginForm').removeClass('hidden');
            }
        }
        if (recoverMode) {
            $('#publicLoginForm').addClass('hidden');
            $('#recoverRequestForm').addClass('hidden');
            $('#recoverResetForm').removeClass('hidden');
        }
        $('#btnOpenRecover').on('click', showRecoverRequest);
        $('#btnBackLogin').on('click', showLoginForm);

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
                        if (res.error === 'email_not_verified') {
                            const canResend = window.confirm('Tu correo aún no está verificado. ¿Quieres reenviar el correo de verificación?');
                            if (canResend) {
                                $.post(API_AUTH, {
                                    action: 'resend_verification',
                                    email: $('#email').val()
                                }, function (r) {
                                    showCustomAlert((r && r.message) || 'Si la cuenta existe, enviaremos el correo de verificación.', 4500, 'info');
                                }, 'json').fail(function () {
                                    showCustomAlert('No se pudo procesar el reenvío.', 4000, 'error');
                                });
                            }
                        }
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

        $('#recoverRequestForm').on('submit', function (e) {
            e.preventDefault();
            const email = String($('#recover_email').val() || '').trim();
            if (!email) return;
            $.post(API_AUTH, { action: 'request_password_reset', email }, function (res) {
                if (res && res.success) {
                    showCustomAlert(res.message || 'Si el correo existe, enviaremos un enlace.', 4000, 'success');
                    showLoginForm();
                } else {
                    showCustomAlert((res && res.message) || 'No se pudo procesar la solicitud.', 4000, 'error');
                }
            }, 'json').fail(function () {
                showCustomAlert('No se pudo procesar la solicitud.', 4000, 'error');
            });
        });

        $('#recoverResetForm').on('submit', function (e) {
            e.preventDefault();
            const token = String($('#recover_token').val() || '').trim();
            const p1 = String($('#recover_new_password').val() || '');
            const p2 = String($('#recover_new_password2').val() || '');
            if (!token) {
                showCustomAlert('Enlace de recuperación inválido.', 4000, 'error');
                return;
            }
            if (p1.length < 6) {
                showCustomAlert('La contraseña debe tener mínimo 6 caracteres.', 3500, 'warning');
                return;
            }
            if (p1 !== p2) {
                showCustomAlert('Las contraseñas no coinciden.', 3500, 'warning');
                return;
            }
            $.post(API_AUTH, { action: 'reset_password', token, password: p1 }, function (res) {
                if (res && res.success) {
                    showCustomAlert('Contraseña actualizada. Ahora inicia sesión.', 4000, 'success');
                    const clean = <?= json_encode(view_url('vistas/public/login.php', $login_empresa_ref !== '' ? $login_empresa_ref : null)) ?>;
                    setTimeout(() => window.location.href = clean, 1200);
                } else {
                    showCustomAlert((res && res.message) || 'No se pudo actualizar la contraseña.', 4500, 'error');
                }
            }, 'json').fail(function () {
                showCustomAlert('No se pudo actualizar la contraseña.', 4500, 'error');
            });
        });

        // ── REGISTRO DE USUARIOS ─────────────────────────────────────────────
        const API_REGISTER = '<?= app_url('api/public/register.php') ?>';

        function showRegisterForm() {
            $('#publicLoginForm').addClass('hidden');
            $('#loginFooter').addClass('hidden');
            $('#recoverRequestForm').addClass('hidden');
            $('#recoverResetForm').addClass('hidden');
            $('#registerForm').removeClass('hidden');
            $('#registerAlert').addClass('hidden');
        }

        function hideRegisterForm() {
            $('#registerForm').addClass('hidden');
            $('#loginFooter').removeClass('hidden');
            if (!recoverMode) {
                $('#publicLoginForm').removeClass('hidden');
            }
        }

        $('#btnShowRegister').on('click', showRegisterForm);
        $('#btnBackToLogin').on('click', hideRegisterForm);

        $('#registerForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $('#btnRegister');
            const alert = $('#registerAlert').addClass('hidden');

            const data = {
                nombre_completo: $('#reg_nombre').val(),
                email: $('#reg_email').val(),
                password: $('#reg_password').val(),
                nombre_empresa: $('#reg_empresa').val()
            };

            // Validación básica en cliente
            if (data.password.length < 6) {
                alert.removeClass('hidden bg-teal-50 text-teal-600').addClass('bg-red-50 text-red-600').text('La contraseña debe tener al menos 6 caracteres.');
                return;
            }

            btn.prop('disabled', true).html('<i data-lucide="loader-2" class="mr-2 animate-spin"></i> Creando cuenta...');
            lucide.createIcons();

            $.ajax({
                url: API_REGISTER,
                type: 'POST',
                data: data,
                success: function (res) {
                    if (res.success) {
                        const nextUrl = String(res?.data?.redirect_url || '');
                        let countdown = 300;

                        alert.removeClass('hidden bg-red-50 text-teal-600 p-4').addClass('bg-teal-50 text-teal-800 p-6 rounded-xl');
                        alert.html(
                            '<div class="text-center">' +
                            '<div class="mb-4"><i data-lucide="check-circle" class="w-12 h-12 mx-auto text-teal-600"></i></div>' +
                            '<h3 class="font-bold text-lg mb-2">¡Registro exitoso!</h3>' +
                            '<p class="text-sm mb-4 leading-relaxed">' + (res.message || 'Cuenta creada correctamente.') + '</p>' +
                            '<div class="text-xs text-teal-700 mb-3">Serás redirigido al login de tu empresa en <span id="redirectCountdown">' + countdown + '</span> segundos...</div>' +
                            '<button id="btnGoNow" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-bold text-sm transition">Ir al login ahora</button>' +
                            '</div>'
                        );
                        lucide.createIcons();

                        const timer = setInterval(function() {
                            countdown--;
                            $('#redirectCountdown').text(countdown);
                            if (countdown <= 0) {
                                clearInterval(timer);
                                if (nextUrl) window.location.href = nextUrl;
                            }
                        }, 1000);

                        $(document).on('click', '#btnGoNow', function() {
                            clearInterval(timer);
                            if (nextUrl) window.location.href = nextUrl;
                        });

                        return;
                    } else {
                        btn.prop('disabled', false).html('<span>Crear mi cuenta</span><i data-lucide="user-plus" class="text-sm"></i>');
                        lucide.createIcons();
                        alert.removeClass('hidden bg-teal-50 text-teal-600').addClass('bg-red-50 text-red-600').text(res.message || 'No se pudo completar el registro.');
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<span>Crear mi cuenta</span><i data-lucide="user-plus" class="text-sm"></i>');
                    lucide.createIcons();
                    let msg = 'Error de conexión. Intente nuevamente.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        msg = res.message || msg;
                    } catch (e) { }
                    alert.removeClass('hidden bg-teal-50 text-teal-600').addClass('bg-red-50 text-red-600').text(msg);
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
