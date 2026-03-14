<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'ajustes';
include __DIR__ . '/../../includes/topbar.php';

$role = $user['rol'] ?? null;
$id_e = request_id_e();
$is_superadmin = ($role === 'superadmin' && !$id_e);
$is_tenant_admin = ($id_e && in_array($role, ['admin', 'gerente']));
?>

<div class="max-w-7xl mx-auto space-y-6">
    <?php if ($is_superadmin): ?>
        <!-- AJUSTES GLOBALES (SOLO SUPERADMIN) -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">SuperAdmin</div>
            <div class="text-2xl font-extrabold text-gray-900 mb-6">Ajustes Globales del Sistema</div>

            <form id="panelForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre del Sistema</label>
                        <input id="system_name" name="system_name"
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50"
                            placeholder="Sistema">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mantenimiento</label>
                            <select id="maintenance_mode" name="maintenance_mode"
                                class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="0">Apagado</option>
                                <option value="1">Encendido</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Permitir Login</label>
                            <select id="allow_login" name="allow_login"
                                class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Registros</label>
                            <select id="allow_register" name="allow_register"
                                class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="1">Permitidos</option>
                                <option value="0">Bloqueados</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-tighter">Configuración SMTP (Correo)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Host SMTP</label>
                            <input id="smtp_host" name="smtp_host"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="smtp.mailtrap.io">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Puerto</label>
                            <input id="smtp_port" name="smtp_port" type="number"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="587">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Usuario</label>
                            <input id="smtp_user" name="smtp_user"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="user@smtp.com">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Password</label>
                            <input id="smtp_pass" name="smtp_pass" type="password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="********">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Email Remitente</label>
                            <input id="smtp_from_email" name="smtp_from_email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="no-reply@sistema.com">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nombre Remitente</label>
                            <input id="smtp_from_name" name="smtp_from_name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="Nombre Sistema">
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex items-center justify-end gap-3 border-t">
                    <button type="button" id="btnReloadGlobal"
                        class="px-4 py-2 text-sm font-bold text-gray-600 border rounded-xl hover:bg-gray-50 transition">Recargar</button>
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg transition transform hover:scale-[1.02]">Guardar
                        Cambios Globales</button>
                </div>
                <div id="saveInfoGlobal" class="hidden text-sm text-indigo-600 text-right font-bold mt-2"></div>
            </form>
        </div>
    <?php endif; ?>

    <!-- PERSONALIZACIÓN (SUPERADMIN Y ENTORNO TENANT) -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Apariencia</div>
        <div class="text-2xl font-extrabold text-gray-900 mb-6">
            <?= $is_superadmin ? 'Personalización Visual de la Plataforma' : 'Personalización de Mi Empresa' ?></div>

        <form id="tenantForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Logo del Sistema (URL)</label>
                    <input id="system_logo_path" name="system_logo_path"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="assets/logo.png">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Color Primario Hexadecimal</label>
                    <div class="flex gap-2">
                        <input id="ui_primary_color" name="ui_primary_color"
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="#0d9488">
                    </div>
                </div>
            </div>

            <?php if (!$is_superadmin): ?>
                <div class="pt-6 border-t border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email de Contacto Público</label>
                            <input id="support_email" name="support_email" class="mt-1 border rounded-lg p-2 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Teléfono de Contacto Público</label>
                            <input id="support_phone" name="support_phone" class="mt-1 border rounded-lg p-2 w-full">
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-tighter">Configuración de Landing
                            Page</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Título Hero</label>
                                <input id="hero_titulo" name="hero_titulo" class="w-full border rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Subtítulo Hero</label>
                                <input id="hero_subtitulo" name="hero_subtitulo" class="w-full border rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Texto Botón</label>
                                <input id="hero_btn_texto" name="hero_btn_texto" class="w-full border rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Link Botón</label>
                                <input id="hero_btn_link" name="hero_btn_link" class="w-full border rounded-lg p-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Sobre Nosotros (Título)</label>
                                <input id="about_titulo" name="about_titulo" class="w-full border rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Nuestra Misión</label>
                                <textarea id="mision" name="mision" class="w-full border rounded-lg p-2"
                                    rows="2"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Nuestra Visión</label>
                                <textarea id="vision" name="vision" class="w-full border rounded-lg p-2"
                                    rows="2"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Texto Footer</label>
                                <textarea id="public_footer_text" name="public_footer_text"
                                    class="w-full border rounded-lg p-2" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="pt-6 flex items-center justify-end gap-3 border-t">
                <button type="button" id="btnReloadTenant"
                    class="px-4 py-2 text-sm font-bold text-gray-600 border rounded-xl hover:bg-gray-50 transition">Recargar
                    Datos</button>
                <button type="submit"
                    class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold shadow-md transition transform hover:scale-[1.02]">Guardar
                    Apariencia</button>
            </div>
            <div id="saveInfoTenant" class="hidden text-sm text-teal-600 text-right font-bold mt-2"></div>
        </form>
    </div>
</div>

<script>
    $(function () {
        const API_AJUSTES = <?= json_encode(app_url('api/superadmin/ajustes.php')) ?>;
        const isSuperadmin = <?= $is_superadmin ? 'true' : 'false' ?>;

        function showInfo(msg, ok, target = 'Global') {
            showCustomAlert(msg, 5000, ok ? 'success' : 'error');
        }

        function loadPanelGlobal() {
            if (!isSuperadmin) return;
            $.get(API_AJUSTES, { action: 'get_panel' }, function (res) {
                if (!res.success) return;
                const d = res.data || {};
                $('#system_name').val(d.system_name || '');
                $('#maintenance_mode').val(d.maintenance_mode || '0');
                $('#allow_login').val(d.allow_login || '1');
                $('#allow_register').val(d.allow_register || '1');
                $('#smtp_host').val(d.smtp_host || '');
                $('#smtp_port').val(d.smtp_port || '587');
                $('#smtp_user').val(d.smtp_user || '');
                $('#smtp_from_email').val(d.smtp_from_email || '');
                $('#smtp_from_name').val(d.smtp_from_name || '');
            }, 'json');
        }

        function loadPanelTenant() {
            $.get(API_AJUSTES, { action: 'get_tenant' }, function (res) {
                if (!res.success) return;
                const d = res.data || {};
                $('#system_logo_path').val(d.logo_path || '');
                $('#ui_primary_color').val(d.color_principal || '');
                $('#support_email').val(d.email_contacto || '');
                $('#support_phone').val(d.telefono_contacto || '');
                $('#public_footer_text').val(d.texto_footer || '');
                $('#hero_titulo').val(d.hero_titulo || '');
                $('#hero_subtitulo').val(d.hero_subtitulo || '');
                $('#hero_btn_texto').val(d.hero_btn_texto || '');
                $('#hero_btn_link').val(d.hero_btn_link || '');
                $('#mision').val(d.mision || '');
                $('#vision').val(d.vision || '');
                $('#about_titulo').val(d.about_titulo || '');
            }, 'json');
        }

        $('#btnReloadGlobal').click(loadPanelGlobal);
        $('#btnReloadTenant').click(loadPanelTenant);

        $('#panelForm').on('submit', function (ev) {
            ev.preventDefault();
            $.post(API_AJUSTES, $(this).serialize() + '&action=save_panel', function (res) {
                if (res.success) showInfo('Ajustes globales guardados.', true, 'Global');
                else showInfo(res.message || 'Error', false, 'Global');
            }, 'json');
        });

        $('#tenantForm').on('submit', function (ev) {
            ev.preventDefault();
            $.post(API_AJUSTES, $(this).serialize() + '&action=save_tenant', function (res) {
                if (res.success) showInfo('Cambios visuales guardados.', true, 'Tenant');
                else showInfo(res.message || 'Error', false, 'Tenant');
            }, 'json');
        });

        if (isSuperadmin) loadPanelGlobal();
        loadPanelTenant();
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>