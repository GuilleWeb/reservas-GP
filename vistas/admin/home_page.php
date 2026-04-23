<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'home_page';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-5xl mx-auto pb-20">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Estructura del Home Page</h1>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                <i data-lucide="info" class="w-4 h-4 text-teal-500"></i>
                Gestiona el orden, visibilidad y límites de los módulos de la página pública.
            </p>
        </div>
        <a href="<?= htmlspecialchars(view_url('vistas/public/inicio.php', $empresa_info['slug'] ?? get_empresa_slug())) ?>"
           target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm">
            <i data-lucide="external-link" class="w-4 h-4"></i>
            Vista previa
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-900">Hero principal</h2>
                <p class="text-sm text-gray-500">Configura el banner principal del inicio público y su estilo visual.</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" id="hero_visible" value="1">
                <button type="button" id="heroVisibleSwitch" class="relative inline-flex h-6 w-11 items-center rounded-full bg-teal-600 transition-all">
                    <span id="heroVisibleKnob" class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform translate-x-5"></span>
                </button>
                <span id="heroVisibleLabel" class="text-sm text-gray-700 font-medium">Activo</span>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-7 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Tipo de hero</label>
                        <select id="hero_tipo" class="w-full border rounded-lg p-2">
                            <option value="1">Tipo 1: texto izquierda + imagen derecha</option>
                            <option value="2">Tipo 2: fondo completo con overlay</option>
                            <option value="3">Tipo 3: compacto centrado</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Imagen (URL o ruta)</label>
                        <input type="hidden" id="hero_imagen" value="">
                        <input type="file" id="hero_imagen_file" accept="image/*" class="w-full border rounded-lg p-2 bg-white">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Título</label>
                    <input id="hero_titulo" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Descripción</label>
                    <textarea id="hero_subtitulo" rows="2" class="w-full border rounded-lg p-2"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Texto del botón</label>
                        <input id="hero_btn_texto" class="w-full border rounded-lg p-2">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Link del botón</label>
                        <input id="hero_btn_link" class="w-full border rounded-lg p-2">
                    </div>
                </div>
            </div>
            <div class="md:col-span-5">
                <label class="text-xs text-gray-500">Vista previa</label>
                <div id="heroPreview" class="mt-1 border rounded-xl overflow-hidden min-h-[210px] bg-gray-50"></div>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" id="btnSaveHero" class="px-4 py-2 rounded-lg bg-teal-600 text-white font-semibold hover:bg-teal-700">Guardar hero</button>
        </div>
    </div>

    <div id="sectionsWrap" class="space-y-3">
        </div>
</div>

<template id="tplSection">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all hover:border-teal-200 group sec-container">
        <div class="p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors toggle-collapse">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-teal-50 group-hover:text-teal-600 transition-colors mod-icon">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 sec-modulo leading-none mb-1"></h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 uppercase tracking-wider badge-tipo">Lógica: -</span>
                        <span class="text-xs text-gray-400 flex items-center gap-1">
                            <i data-lucide="arrow-down-narrow-wide" class="w-3 h-3"></i>
                            Posición: <span class="badge-orden">0</span>
                        </span>
                        <span class="text-xs text-gray-400">Registros asignados: <span class="badge-count">0</span></span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 pr-4 border-r border-gray-100 hidden md:flex">
                    <input type="hidden" class="sec-estado" value="1">
                    <button type="button" class="sec-switch relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition-all shadow-inner">
                        <span class="sec-knob inline-block h-4 w-4 translate-x-0 rounded-full bg-white shadow-md transition-transform"></span>
                    </button>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 transition-transform chevron"></i>
            </div>
        </div>

        <div class="sec-content border-t border-gray-50 hidden">
            <div class="p-6 bg-gray-50/30">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    
                    <div class="md:col-span-7 space-y-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Título Público de la Sección</label>
                                <input type="text" class="sec-titulo w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all text-sm font-medium">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Lógica de Selección</label>
                                <select class="sec-tipo w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all text-sm bg-white">
                                    <option value="1">Personalizada (Manual)</option>
                                    <option value="2">Lo más reciente</option>
                                    <option value="3">Orden Aleatorio</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Posición (Orden)</label>
                                <input type="number" class="sec-orden w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all text-sm">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Límite Max.</label>
                                <input type="number" class="sec-limite w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all text-sm" max="30">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-5">
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Registros asignados (Vista previa)</label>
                        <div class="sec-selected bg-white border border-gray-200 rounded-2xl overflow-hidden min-h-[140px] max-h-[220px] overflow-y-auto shadow-inner">
                            </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-[11px] text-gray-400 italic font-medium">Actualizado: Los cambios se reflejarán inmediatamente en la web.</p>
                    <button type="button" class="sec-save flex items-center gap-2 px-6 py-2.5 rounded-xl bg-teal-600 text-white text-sm font-bold hover:bg-teal-700 transition-all shadow-lg active:scale-95">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
$(function () {
    const API_PAGE = <?= json_encode(app_url('api/admin/home_page.php')) ?>;
    const moduloLabels = {
        blog: { name: 'Blog de Noticias', icon: 'newspaper', color: 'text-blue-500' },
        usuarios: { name: 'Nuestro Equipo', icon: 'users', color: 'text-purple-500' },
        resenas: { name: 'Reseñas de Clientes', icon: 'star', color: 'text-orange-500' },
        servicios: { name: 'Servicios Profesionales', icon: 'briefcase', color: 'text-emerald-500' },
        sucursales: { name: 'Nuestras Sedes', icon: 'map-pin', color: 'text-red-500' },
    };

    let sections = [];
    function setHeroSwitch(v){
        const active = String(v) === '1';
        $('#hero_visible').val(active ? '1' : '0');
        $('#heroVisibleSwitch').toggleClass('bg-teal-600', active).toggleClass('bg-gray-300', !active);
        $('#heroVisibleKnob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
        $('#heroVisibleLabel').text(active ? 'Activo' : 'Inactivo');
    }

    let heroPreviewUrl = '';

    function heroPreviewHtml(){
        const tipo = parseInt($('#hero_tipo').val() || '1', 10);
        const titulo = $('<div>').text($('#hero_titulo').val() || 'Título hero').html();
        const subt = $('<div>').text($('#hero_subtitulo').val() || 'Descripción hero').html();
        const btn = $('<div>').text($('#hero_btn_texto').val() || 'Acción').html();
        const img = (heroPreviewUrl || ($('#hero_imagen').val() || '').trim()) || 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=1200';
        if (tipo === 2) {
            return `<div class="relative h-[210px]"><img src="${img}" class="absolute inset-0 w-full h-full object-cover"><div class="absolute inset-0 bg-black/45"></div><div class="absolute inset-0 p-4 text-white flex flex-col justify-center"><div class="text-lg font-bold">${titulo}</div><div class="text-sm opacity-90 mt-1">${subt}</div><span class="inline-flex mt-3 px-3 py-1.5 rounded-lg bg-teal-500 text-white text-xs font-semibold w-fit">${btn}</span></div></div>`;
        }
        if (tipo === 3) {
            return `<div class="relative h-[210px]"><img src="${img}" class="absolute inset-0 w-full h-full object-cover"><div class="absolute inset-0 bg-black/35"></div><div class="absolute inset-0 p-4 flex flex-col items-center justify-center text-center"><div class="text-xl font-bold text-white">${titulo}</div><div class="text-sm text-white/90 mt-2 max-w-sm">${subt}</div><span class="inline-flex mt-3 px-3 py-1.5 rounded-lg bg-teal-500 text-white text-xs font-semibold">${btn}</span></div></div>`;
        }
        return `<div class="h-[210px] grid grid-cols-2"><div class="p-4 bg-white flex flex-col justify-center"><div class="text-lg font-bold text-gray-900">${titulo}</div><div class="text-sm text-gray-600 mt-1">${subt}</div><span class="inline-flex mt-3 px-3 py-1.5 rounded-lg bg-teal-600 text-white text-xs font-semibold w-fit">${btn}</span></div><div class="bg-gray-100"><img src="${img}" class="w-full h-full object-cover"></div></div>`;
    }

    function refreshHeroPreview(){
        $('#heroPreview').html(heroPreviewHtml());
    }

    function loadHero(){
        $.get(API_PAGE, { action: 'get_hero' }, function(res){
            if(!(res && res.success)) return;
            const d = res.data || {};
            setHeroSwitch(parseInt(d.hero_visible || 0, 10) === 1 ? '1' : '0');
            $('#hero_tipo').val(String(d.hero_tipo || 1));
            $('#hero_titulo').val(d.hero_titulo || '');
            $('#hero_subtitulo').val(d.hero_subtitulo || '');
            $('#hero_btn_texto').val(d.hero_btn_texto || '');
            $('#hero_btn_link').val(d.hero_btn_link || '');
            $('#hero_imagen').val(d.hero_imagen || '');
            $('#hero_imagen_file').val('');
            heroPreviewUrl = '';
            refreshHeroPreview();
        }, 'json');
    }

    $('#hero_imagen_file').on('change', function(){
        const f = this.files && this.files[0] ? this.files[0] : null;
        if (f) {
            heroPreviewUrl = URL.createObjectURL(f);
        }
        refreshHeroPreview();
    });

    function updateSwitchUI(box, val) {
        const active = String(val) === '1';
        box.find('.sec-estado').val(active ? '1' : '0');

        const btn = box.find('.sec-switch');
        btn.toggleClass('bg-teal-600', active).toggleClass('bg-gray-200', !active).toggleClass('bg-gray-300', !active);
        btn.find('.sec-knob').toggleClass('translate-x-5', active).toggleClass('translate-x-0', !active);
    }

    function renderSelectedItems(box, items) {
        box.empty();
        if (!items || !items.length) {
            box.append(`
                <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <i data-lucide="package-open" class="w-8 h-8 mb-2 opacity-20"></i>
                    <p class="text-xs font-medium">Sin registros asignados</p>
                </div>
            `);
            if (window.lucide) lucide.createIcons();
            return;
        }
        
        items.forEach(item => {
            const row = $(`
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-50 last:border-0 hover:bg-gray-50 group/item transition-colors">
                    <span class="text-sm font-medium text-gray-600 truncate"></span>
                    <button type="button" class="rm-item p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover/item:opacity-100">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            `);
            row.attr('data-id', item.id);
            row.find('span').text(`${item.id} - ${item.nombre || item.titulo || ('ID ' + item.id)}`);
            box.append(row);
        });
        if (window.lucide) lucide.createIcons();
    }

    function render() {
        const wrap = $('#sectionsWrap').empty();
        const tipoLabel = { 1: 'Personalizada', 2: 'Más recientes', 3: 'Aleatoria' };
        
        sections.sort((a, b) => (a.orden || 0) - (b.orden || 0)).forEach(sec => {
            const config = moduloLabels[sec.modulo] || { name: sec.modulo, icon: 'layers', color: 'text-gray-500' };
            const tpl = $($('#tplSection').html());
            
            // Llenar datos básicos
            tpl.find('.sec-modulo').text(config.name);
            tpl.find('.badge-orden').text(sec.orden || 0);
            tpl.find('.sec-titulo').val(sec.titulo || '');
            tpl.find('.sec-tipo').val(String(sec.tipo || 1));
            tpl.find('.sec-orden').val(sec.orden || 0);
            tpl.find('.sec-limite').val(sec.limite || 3);
            tpl.find('.badge-tipo').text(`Lógica: ${tipoLabel[parseInt(sec.tipo || 1, 10)] || 'Personalizada'}`);
            tpl.find('.badge-count').text(Array.isArray(sec.valores) ? sec.valores.length : 0);
            tpl.find('.mod-icon i').attr('data-lucide', config.icon);
            tpl.find('.mod-icon').addClass(config.color);
            
            updateSwitchUI(tpl, sec.estado || 0);

            // Cargar items asignados
            const selectedBox = tpl.find('.sec-selected');
            $.get(API_PAGE, { action: 'get_selected', modulo: sec.modulo }, function (rs) {
                const items = (rs && rs.success) ? (rs.data || []) : [];
                renderSelectedItems(selectedBox, items);
                tpl.find('.badge-count').text(items.length);
            }, 'json');

            // --- EVENTOS ---

            // Colapsable
            tpl.find('.toggle-collapse').on('click', function(e) {
                if ($(e.target).closest('.sec-switch').length) return;
                const content = tpl.find('.sec-content');
                const isHidden = content.is(':hidden');
                
                $('.sec-content').slideUp(250); // Cerrar otros
                $('.chevron').removeClass('rotate-180');
                
                if (isHidden) {
                    content.slideDown(250);
                    tpl.find('.chevron').addClass('rotate-180');
                    tpl.addClass('ring-2 ring-teal-500/10 border-teal-200');
                } else {
                    tpl.removeClass('ring-2 ring-teal-500/10 border-teal-200');
                }
            });

            // Switch Estado
            tpl.find('.sec-switch').on('click', function () {
                const newVal = tpl.find('.sec-estado').val() === '1' ? '0' : '1';
                updateSwitchUI(tpl, newVal);
            });

            // Eliminar Item (Quitar asignación)
            selectedBox.on('click', '.rm-item', function () {
                const id = $(this).closest('[data-id]').attr('data-id');
                $(this).closest('[data-id]').fadeOut(200, function() { 
                    $(this).remove(); 
                    if (selectedBox.children().length === 0) renderSelectedItems(selectedBox, []);
                });
                sec.valores = (sec.valores || []).filter(v => String(v) !== String(id));
            });

            // Guardar
            tpl.find('.sec-save').on('click', function () {
                const btn = $(this);
                btn.prop('disabled', true).addClass('opacity-70');
                
                const payload = {
                    action: 'save_section',
                    modulo: sec.modulo,
                    titulo: tpl.find('.sec-titulo').val(),
                    tipo: parseInt(tpl.find('.sec-tipo').val() || '1', 10),
                    estado: parseInt(tpl.find('.sec-estado').val() || '0', 10),
                    orden: parseInt(tpl.find('.sec-orden').val() || '0', 10),
                    limite: parseInt(tpl.find('.sec-limite').val() || '3', 10),
                    valores: selectedBox.find('[data-id]').map(function() { return parseInt($(this).attr('data-id'), 10); }).get()
                };
                tpl.find('.badge-tipo').text(`Lógica: ${tipoLabel[payload.tipo] || 'Personalizada'}`);
                tpl.find('.badge-orden').text(payload.orden || 0);
                tpl.find('.badge-count').text(payload.valores.length);

                $.post(API_PAGE, payload, function (res) {
                    btn.prop('disabled', false).removeClass('opacity-70');
                    if (res && res.success) {
                        showCustomAlert('Configuración actualizada.', 3000, 'success');
                        loadSections();
                    } else {
                        showCustomAlert(res.message || 'Error al guardar.', 5000, 'error');
                    }
                }, 'json');
            });

            wrap.append(tpl);
        });
        if (window.lucide) lucide.createIcons();
    }

    function loadSections() {
        $.get(API_PAGE, { action: 'list_sections' }, function (res) {
            if (res && res.success) {
                sections = res.data || [];
                render();
            }
        }, 'json');
    }


<style>
    .rotate-180 { transform: rotate(180deg); }
    .sec-container.ring-2 { border-color: rgb(20 184 166 / 0.5); }
    /* Personalización de scrollbar para los seleccionados */
    .sec-selected::-webkit-scrollbar { width: 5px; }
    .sec-selected::-webkit-scrollbar-track { background: transparent; }
    .sec-selected::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .sec-selected::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
