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
$preselected_sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
$module = 'citas';
include __DIR__ . '/../../includes/topbar.php';
?>

<style>
    .step-dot {
        transition: all 0.3s ease;
    }

    .step-dot.active {
        background-color: var(--twc-teal-600);
        color: #fff;
        transform: scale(1.08);
        box-shadow: 0 10px 18px rgb(var(--twc-teal-600-rgb) / 0.25);
    }

    .step-dot.completed {
        background-color: var(--twc-teal-500);
        color: #fff;
    }
    .step-nav {
        cursor: pointer;
    }
    .step-nav.disabled {
        pointer-events: none;
        opacity: .7;
        cursor: default;
    }
    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .6rem;
        border-radius: 9999px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        font-size: .78rem;
        line-height: 1rem;
        white-space: nowrap;
    }

    .card-choice {
        border: 2px solid transparent;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        transition: all .2s ease;
        cursor: pointer;
        border-radius: 1rem;
        padding: 1.25rem;
    }

    .card-choice.selected {
        border-color: var(--twc-teal-500);
        background: rgb(var(--twc-teal-500-rgb) / 0.08);
        box-shadow: 0 12px 24px rgb(var(--twc-teal-500-rgb) / 0.16);
    }

    .time-pill {
        padding: .75rem 1rem;
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        font-weight: 700;
        text-align: center;
        transition: all .2s ease;
        cursor: pointer;
    }

    .time-pill.available {
        background: #fff;
        border-color: #e5e7eb;
        color: #111827;
    }

    .time-pill.selected {
        background: var(--twc-teal-600);
        border-color: var(--twc-teal-600);
        color: #fff;
        box-shadow: 0 10px 18px rgb(var(--twc-teal-600-rgb) / 0.25);
    }

    .time-pill.occupied {
        background: #f9fafb;
        border-color: #f3f4f6;
        color: #d1d5db;
        cursor: not-allowed;
    }
</style>

<script>
    (function () {
        try {
            const probe = document.createElement('span');
            probe.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
            document.body.appendChild(probe);

            function setVars(cls, varName) {
                probe.className = cls;
                const c = getComputedStyle(probe).color;
                document.documentElement.style.setProperty(varName, c);
                const m = String(c || '').match(/rgba?\((\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
                if (m) {
                    document.documentElement.style.setProperty(varName + '-rgb', `${m[1]} ${m[2]} ${m[3]}`);
                }
            }

            setVars('text-teal-600', '--twc-teal-600');
            setVars('text-teal-500', '--twc-teal-500');

            probe.remove();
        } catch (e) {
        }
    })();
</script>

<div class="max-w-5xl mx-auto px-4 py-12">
    <!-- Wizard Header -->
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-black text-gray-900 mb-2">Reserva tu Cita</h1>
        <p class="text-gray-500 italic">Sigue los pasos para agendar tu espacio con nosotros.</p>
    </div>

    <!-- Stepper -->
    <div class="relative flex justify-between items-center max-w-2xl mx-auto mb-16">
        <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 -z-10"></div>
        <div id="progressLine"
            class="absolute top-1/2 left-0 w-0 h-1 bg-teal-500 -translate-y-1/2 -z-10 transition-all duration-500">
        </div>

        <div class="step-nav flex flex-col items-center gap-2 group" data-step="1">
            <div
                class="step-dot active w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                1</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Sede</span>
        </div>
        <div class="step-nav flex flex-col items-center gap-2 disabled" data-step="2">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                2</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Servicio</span>
        </div>
        <div class="step-nav flex flex-col items-center gap-2 disabled" data-step="3">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                3</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Empleado</span>
        </div>
        <div class="step-nav flex flex-col items-center gap-2 disabled" data-step="4">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                4</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Fecha</span>
        </div>
        <div class="step-nav flex flex-col items-center gap-2 disabled" data-step="5">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                5</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Datos</span>
        </div>
        <div class="step-nav flex flex-col items-center gap-2 disabled" data-step="6">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                6</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Confirmación</span>
        </div>
    </div>

    <!-- Wizard Steps Container -->
    <div id="wizardContainer"
        class="bg-white/50 backdrop-blur-sm rounded-[2.5rem] p-8 md:p-12 border border-white shadow-2xl relative min-h-[500px]">
        <div id="selectedSummary" class="hidden mb-5 bg-white rounded-2xl border border-teal-100 shadow-sm p-3 md:p-3.5">
            <div class="text-[11px] font-black uppercase tracking-widest text-teal-700 mb-2">Resumen de tu selección</div>
            <div id="selectedSummaryGrid" class="flex flex-wrap gap-2 overflow-x-auto"></div>
        </div>

        <!-- Step 1: Sede -->
        <div id="stepView1" class="step-view space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">¿A qué sede deseas asistir?
            </h2>
            <div id="sedesGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sedes loaded via dynamic JS -->
            </div>
        </div>

        <!-- Step 2: Servicio -->
        <div id="stepView2" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">¿Qué servicio necesitas?</h2>
            <div id="serviciosGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[400px] overflow-y-auto pr-2">
                <!-- Servicios loaded via dynamic JS -->
            </div>
        </div>

        <!-- Step 3: Empleado -->
        <div id="stepView3" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">¿Con quién deseas agendar?
            </h2>
            <div id="empleadosGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Empleados loaded via dynamic JS -->
            </div>
        </div>

        <!-- Step 4: Fecha y Hora -->
        <div id="stepView4" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">Elige el día y la hora</h2>
            <div id="sucursalHorarioResumen" class="text-sm text-teal-700 bg-teal-50 border border-teal-100 rounded-xl px-4 py-3 hidden"></div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div id="calendar" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <div class="inline-flex items-center gap-2">
                            <button id="btnPrevMonth" type="button" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="chevron-left"></i></button>
                            <button id="btnTodayMonth" type="button" class="px-3 h-9 rounded-lg border text-sm font-semibold hover:bg-gray-50">Hoy</button>
                            <button id="btnNextMonth" type="button" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="chevron-right"></i></button>
                        </div>
                        <div id="calendarMonthLabel" class="text-sm font-black text-gray-700"></div>
                    </div>
                    <!-- Custom Calendar JS -->
                </div>
                <div class="space-y-6">
                    <h3
                        class="font-black text-gray-900 uppercase text-xs tracking-widest text-center py-2 bg-gray-50 rounded-lg">
                        Horas Disponibles</h3>
                    <div id="diaHorarioInfo" class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2">Selecciona una fecha para ver el horario de la sucursal.</div>
                    <div id="timesGrid" class="grid grid-cols-3 gap-3">
                        <div class="col-span-full text-center py-10 text-gray-400 italic">Selecciona una fecha...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Datos Personales -->
        <div id="stepView5" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">Tus Datos de Contacto</h2>
            <form id="datosForm" class="w-full space-y-6">
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Correo</label>
                    <input type="email" id="cli_email" name="email"
                        class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all text-center"
                        required placeholder="correo@ejemplo.com" autocomplete="email">
                    <div id="clienteLookupHint" class="hidden text-xs text-teal-800 bg-white border border-teal-100 rounded-xl p-3"></div>
                </div>

                <div id="clienteExtraFields" class="hidden">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Nombre
                            Completo</label>
                        <input type="text" id="cli_nombre" name="nombre"
                            class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                            required placeholder="Juan Pérez" autocomplete="name">
                    </div>
                    <div class="mt-4 space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Teléfono</label>
                        <input type="tel" id="cli_telefono" name="telefono"
                            class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                            required placeholder="55 1234 5678" autocomplete="tel">
                    </div>
                    <div class="mt-4 space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Notas
                            Adicionales</label>
                        <textarea id="cli_notas" name="notas"
                            class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                            rows="3" placeholder="¿Algo que debamos saber?"></textarea>
                    </div>
                </div>
            </form>
        </div>

        <!-- Step 6: Confirmación -->
        <div id="stepView6" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">Verifica tu Reserva</h2>
            <div id="resumenCita" class="bg-white border rounded-[2.5rem] p-8 md:p-12 shadow-sm space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-teal-50 rounded-full flex items-center justify-center text-teal-600 mt-1">
                                <i data-lucide="map-pin"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sucursal
                                </div>
                                <div id="resSede" class="text-lg font-black text-gray-900">...</div>
                                <div id="resSedeDir" class="text-xs text-gray-500 mt-1">...</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-teal-50 rounded-full flex items-center justify-center text-teal-600 mt-1">
                                <i data-lucide="magic"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Servicio
                                </div>
                                <div id="resServicio" class="text-lg font-black text-gray-900">...</div>
                                <div id="resDuracion" class="text-xs text-gray-500 mt-1">Duración: --</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-purple-50 rounded-full flex items-center justify-center text-purple-600 mt-1">
                                <i data-lucide="user"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Especialista
                                </div>
                                <div id="resEmpleado" class="text-lg font-black text-gray-900">...</div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600 mt-1">
                                <i data-lucide="calendar-check"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha y Hora
                                </div>
                                <div id="resFecha" class="text-lg font-black text-gray-900">...</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 mt-1">
                                <i data-lucide="user"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Cliente
                                </div>
                                <div id="resCliente" class="text-lg font-black text-gray-900">...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-8 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-gray-500 text-sm">
                        <div>Al confirmar, tu cita quedará registrada.</div>
                        <div class="text-xs mt-1">Recomendación: llega 5-10 minutos antes para validar tu ingreso.</div>
                    </div>
                    <div class="text-3xl font-black text-teal-600" id="resPrecio">$0</div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-12 flex items-center justify-between border-t border-gray-100 pt-8">
            <button id="prevBtn"
                class="invisible px-8 py-3 rounded-2xl border font-black text-gray-500 hover:bg-gray-50 transition-all active:scale-95">
                Volver
            </button>
            <button id="nextBtn"
                class="px-10 py-4 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-black shadow-xl shadow-teal-100 transition-all transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente Paso &rarr;
            </button>
        </div>

    </div>
</div>

<script>
    $(function () {
        const slug = '<?= $slug ?>';
        const preselectedSedeId = <?= json_encode($preselected_sede_id) ?>;
        const API = '<?= app_url('api/public/sucursales/agregar_cita.php') ?>';
        const CURRENCY = window.APP_CURRENCY || { code: 'GTQ', symbol: 'Q' };
        const initialUser = <?= json_encode([
            'nombre' => (string) (($user['nombre'] ?? '')),
            'email' => (string) (($user['email'] ?? '')),
            'telefono' => (string) (($user['telefono'] ?? ''))
        ]) ?>;

        let currentStep = 1;
        let maxReachedStep = 1;
        let reservationCode = '';
        let autoFillLock = false;
        let clienteLookupToken = '';
        let clienteLookupData = null;
        const localProfileKey = `rgp_cliente_profile_${slug}`;
        const selection = {
            sede: null,
            servicio: null,
            empleado: null,
            fecha: null,
            hora: null,
            monthCursor: new Date(),
            p_nombre: '',
            p_email: '',
            p_telefono: '',
            p_notas: ''
        };
        const sedesMap = {};
        const DAY_KEYS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        const DAY_LABELS = { domingo: 'Dom', lunes: 'Lun', martes: 'Mar', miercoles: 'Mié', jueves: 'Jue', viernes: 'Vie', sabado: 'Sáb' };

        function fmtFechaHora(fecha, hora) {
            const d = new Date(`${fecha}T${hora}:00`);
            if (Number.isNaN(d.getTime())) return `${fecha} ${hora}`;
            return d.toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' });
        }

        function updateStepPerms() {
            $('.step-nav').each(function () {
                const step = parseInt($(this).data('step'), 10);
                const canGo = step <= maxReachedStep;
                $(this).toggleClass('disabled', !canGo);
            });
        }

        function maskName(name) {
            const words = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!words.length) return '***';
            return words.map(w => (w.length <= 1 ? '*' : `${w[0]}${'*'.repeat(Math.max(2, w.length - 1))}`)).join(' ');
        }

        function maskPhone(phone) {
            const d = String(phone || '').replace(/\D/g, '');
            if (!d) return '***';
            const keep = d.slice(-2);
            return `${'*'.repeat(Math.max(4, d.length - 2))}${keep}`;
        }

        function clearDownstream(fromStep) {
            if (fromStep <= 1) {
                selection.servicio = null;
                $('#serviciosGrid').empty();
                maxReachedStep = 1;
            }
            if (fromStep <= 2) {
                selection.empleado = null;
                $('#empleadosGrid').empty();
                maxReachedStep = Math.min(maxReachedStep, 2);
            }
            if (fromStep <= 3) {
                selection.fecha = null;
                selection.hora = null;
                $('[data-date]').removeClass('bg-teal-600 text-white').addClass('hover:bg-teal-50');
                $('#timesGrid').html('<div class="col-span-full text-center py-10 text-gray-400 italic">Selecciona una fecha...</div>');
                $('#diaHorarioInfo').text('Selecciona una fecha para ver el horario de la sucursal.');
                maxReachedStep = Math.min(maxReachedStep, 3);
            }
            if (fromStep <= 4) {
                maxReachedStep = Math.min(maxReachedStep, 4);
            }
        }

        function updateUrlReserva(code) {
            if (!window.history || !window.history.replaceState) return;
            const url = new URL(window.location.href);
            if (code) url.searchParams.set('reserva', code);
            else url.searchParams.delete('reserva');
            window.history.replaceState({}, '', url.toString());
        }

        function parseHorarios(raw) {
            try {
                if (!raw) return {};
                return typeof raw === 'string' ? (JSON.parse(raw) || {}) : (raw || {});
            } catch (e) {
                return {};
            }
        }

        function getSedeDayRange(dateObj) {
            const h = parseHorarios(selection.sede?.horarios_json);
            const key = DAY_KEYS[dateObj.getDay()];
            const row = h[key];
            if (row && typeof row === 'object') {
                const activo = !(row.activo === 0 || row.activo === '0' || row.activo === false);
                if (!activo) return { active: false, inicio: null, fin: null };
                if (row.inicio && row.fin) return { active: true, inicio: row.inicio, fin: row.fin };
            }
            const legacyKey = dateObj.getDay() === 0 ? 'dom' : (dateObj.getDay() === 6 ? 'sab' : 'lun-vie');
            const legacy = h[legacyKey];
            if (legacy && legacy.inicio && legacy.fin) return { active: true, inicio: legacy.inicio, fin: legacy.fin };
            return { active: true, inicio: null, fin: null };
        }

        function renderSedeHorarioResumen() {
            const box = $('#sucursalHorarioResumen');
            if (!selection.sede) {
                box.addClass('hidden').text('');
                return;
            }
            const h = parseHorarios(selection.sede.horarios_json);
            const order = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            const labels = { lunes: 'Lun', martes: 'Mar', miercoles: 'Mié', jueves: 'Jue', viernes: 'Vie', sabado: 'Sáb', domingo: 'Dom' };
            const state = order.map((d, idx) => {
                const row = (h && typeof h[d] === 'object') ? h[d] : null;
                const active = !!(row && !(row.activo === 0 || row.activo === '0' || row.activo === false) && row.inicio && row.fin);
                return { idx, day: d, active, range: active ? `${row.inicio}-${row.fin}` : null };
            });

            const openGroups = [];
            const closedGroups = [];
            let cur = null;
            for (const s of state) {
                const key = s.active ? `open:${s.range}` : 'closed';
                if (!cur || cur.key !== key || s.idx !== (cur.endIdx + 1)) {
                    if (cur) {
                        (cur.kind === 'open' ? openGroups : closedGroups).push(cur);
                    }
                    cur = {
                        key,
                        kind: s.active ? 'open' : 'closed',
                        range: s.range,
                        startIdx: s.idx,
                        endIdx: s.idx
                    };
                } else {
                    cur.endIdx = s.idx;
                }
            }
            if (cur) {
                (cur.kind === 'open' ? openGroups : closedGroups).push(cur);
            }

            const fmtSpan = (g) => {
                const d1 = labels[order[g.startIdx]];
                const d2 = labels[order[g.endIdx]];
                return g.startIdx === g.endIdx ? d1 : `${d1}-${d2}`;
            };

            const openText = openGroups.map(g => `${fmtSpan(g)}: ${g.range}`);
            const closedText = closedGroups.map(g => fmtSpan(g));

            if (!openText.length && !closedText.length) {
                box.removeClass('hidden').text(`Horario de ${selection.sede.nombre}: consulta disponibilidad por fecha.`);
                return;
            }
            const parts = [];
            if (openText.length) parts.push(openText.join(' · '));
            if (closedText.length) parts.push(`Cerrado: ${closedText.join(', ')}`);
            box.removeClass('hidden').text(`Horario de ${selection.sede.nombre}: ${parts.join(' | ')}`);
        }

        function updateWizard() {
            $('.step-view').addClass('hidden');
            $('#stepView' + currentStep).removeClass('hidden');

            // Update dots
            $('.step-dot').removeClass('active completed text-white border-teal-500').addClass('text-gray-400 border-gray-100');
            $(`[data-step="${currentStep}"] .step-dot`).addClass('active border-teal-500 text-teal-600');
            for (let i = 1; i < currentStep; i++) {
                $(`[data-step="${i}"] .step-dot`).addClass('completed text-white').html('<i data-lucide="check"></i>');
            }
            $(`[data-step="${currentStep}"] .step-dot`).text(currentStep);

            // Progress line
            $('#progressLine').css('width', ((currentStep - 1) / 5 * 100) + '%');

            // Buttons
            $('#prevBtn').toggleClass('invisible', currentStep === 1 || currentStep === 7);
            $('#nextBtn').toggleClass('hidden', currentStep === 7);
            $('#nextBtn').html(currentStep === 6 ? 'Confirmar Cita' : 'Siguiente Paso &rarr;');

            renderSelectedSummary();
            updateStepPerms();
            validateStep();
        }

        function renderSelectedSummary() {
            const rows = [];
            if (selection.sede) rows.push(['Sede', selection.sede.nombre]);
            if (selection.servicio) rows.push(['Servicio', `${selection.servicio.nombre} · ${CURRENCY.symbol}${selection.servicio.precio || 0}`]);
            if (selection.empleado) rows.push(['Especialista', selection.empleado.nombre]);
            if (selection.fecha && selection.hora) rows.push(['Fecha y hora', fmtFechaHora(selection.fecha, selection.hora)]);
            if (selection.p_email) rows.push(['Correo', selection.p_email]);

            const box = $('#selectedSummary');
            const grid = $('#selectedSummaryGrid').empty();
            if (!rows.length || currentStep === 1 || currentStep >= 6) {
                box.addClass('hidden');
                return;
            }
            rows.forEach(([k, v]) => {
                grid.append(`<span class="summary-chip"><span class="text-gray-500">${k}:</span><span class="font-semibold text-gray-800 truncate max-w-[220px]">${v}</span></span>`);
            });
            box.removeClass('hidden');
        }

        function validateStep() {
            let ok = false;
            if (currentStep === 1) ok = !!selection.sede;
            if (currentStep === 2) ok = !!selection.servicio;
            if (currentStep === 3) ok = !!selection.empleado;
            if (currentStep === 4) ok = !!(selection.fecha && selection.hora);
            if (currentStep === 5) {
                selection.p_nombre = $('#cli_nombre').val();
                selection.p_email = $('#cli_email').val();
                selection.p_telefono = $('#cli_telefono').val();
                const extrasVisible = !$('#clienteExtraFields').hasClass('hidden');
                ok = extrasVisible && (selection.p_nombre && selection.p_email && selection.p_telefono);
            }
            if (currentStep === 6) ok = true;

            $('#nextBtn').prop('disabled', !ok);
        }

        let clienteLookupTimer = null;

        function clearClienteLookupUI() {
            clienteLookupToken = '';
            clienteLookupData = null;
            $('#clienteLookupHint').addClass('hidden').text('');
            $('#clienteExtraFields').addClass('hidden');
            $('#cli_nombre').val('');
            $('#cli_telefono').val('');
            $('#cli_email').prop('readonly', false);
        }

        $(document).on('click', '#btnEditarCorreo', function (e) {
            e.preventDefault();
            $('#cli_email').prop('readonly', false).val('').trigger('input').trigger('focus');
            clearClienteLookupUI();
            validateStep();
            renderSelectedSummary();
        });

        async function lookupClienteByEmail(email) {
            if (autoFillLock) return;
            autoFillLock = true;
            try {
                const res = await $.get(API, { action: 'find_cliente_secure', id_e: slug, email });
                if (res && res.success && res.data && res.data.exists) {
                    clienteLookupToken = String(res.data.lookup_token || '');
                    clienteLookupData = res.data;
                    $('#clienteExtraFields').removeClass('hidden');
                    $('#cli_nombre').val(String(res.data.nombre || ''));
                    $('#cli_telefono').val(String(res.data.telefono || ''));
                    $('#cli_email').prop('readonly', true);
                    $('#clienteLookupHint')
                        .removeClass('hidden')
                        .html('Cliente encontrado. Puedes confirmar o actualizar tu nombre/teléfono si es necesario. <a href="#" id="btnEditarCorreo" class="font-semibold underline text-teal-700">Cambiar correo</a>');
                } else {
                    clienteLookupToken = '';
                    clienteLookupData = null;
                    $('#cli_email').prop('readonly', false);
                    $('#clienteExtraFields').removeClass('hidden');
                    $('#cli_nombre').val('');
                    $('#cli_telefono').val('');
                    $('#clienteLookupHint').addClass('hidden').text('');
                }
            } catch (e) {
                $('#cli_email').prop('readonly', false);
                $('#clienteExtraFields').removeClass('hidden');
                $('#clienteLookupHint').removeClass('hidden').text('No se pudo validar el correo en este momento. Continúa llenando tus datos.');
            } finally {
                autoFillLock = false;
                validateStep();
                renderSelectedSummary();
            }
        }

        $('#cli_email').on('input', function () {
            if ($(this).prop('readonly')) return;
            const email = String($(this).val() || '').trim();
            clearTimeout(clienteLookupTimer);
            clearClienteLookupUI();
            if (!email) {
                validateStep();
                renderSelectedSummary();
                return;
            }
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!isValid) {
                validateStep();
                renderSelectedSummary();
                return;
            }
            // Debounce para no consultar en cada tecla.
            clienteLookupTimer = setTimeout(() => {
                lookupClienteByEmail(email);
            }, 600);
            $('#clienteExtraFields').removeClass('hidden');
            validateStep();
            renderSelectedSummary();
        });

        $('#cli_nombre, #cli_telefono, #cli_notas').on('input', function () {
            validateStep();
            renderSelectedSummary();
        });

        if (initialUser && (initialUser.nombre || initialUser.email || initialUser.telefono)) {
            if (initialUser.nombre) $('#cli_nombre').val(initialUser.nombre);
            if (initialUser.email) $('#cli_email').val(initialUser.email);
            if (initialUser.telefono) $('#cli_telefono').val(initialUser.telefono);
        }
        try {
            const cached = JSON.parse(localStorage.getItem(localProfileKey) || 'null');
            if (cached && typeof cached === 'object') {
                if (!$('#cli_nombre').val() && cached.nombre) $('#cli_nombre').val(String(cached.nombre));
                if (!$('#cli_email').val() && cached.email) $('#cli_email').val(String(cached.email));
                if (!$('#cli_telefono').val() && cached.telefono) $('#cli_telefono').val(String(cached.telefono));
            }
        } catch (e) {
        }

        // Load Sedes
        $.get(API, { action: 'get_sucursales', id_e: slug }, function (res) {
            if (res.success && res.data) {
                const grid = $('#sedesGrid').empty();
                res.data.forEach(s => {
                    sedesMap[String(s.id)] = s;
                    grid.append(`
                    <div class="card-choice ${preselectedSedeId == s.id ? 'selected' : ''}" data-id="${s.id}" data-nombre="${s.nombre}" data-direccion="${s.direccion || ''}" data-type="sede">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600"><i data-lucide="building"></i></div>
                            <div>
                                <div class="font-black text-gray-900">${s.nombre}</div>
                                <div class="text-xs text-gray-400">${s.direccion || ''}</div>
                            </div>
                        </div>
                    </div>
                `);
                });
                if (preselectedSedeId) {
                    selection.sede = res.data.find(s => s.id == preselectedSedeId) || null;
                    if (selection.sede) {
                        selection.sede.horarios_json = selection.sede.horarios_json || '{}';
                        renderSedeHorarioResumen();
                    }
                    validateStep();
                }
            }
        });

        function loadServicios() {
            $('#serviciosGrid').html('<div class="text-center py-10"><i data-lucide="sync" class="animate-spin"></i></div>');
            $.get(API, { action: 'get_servicios', id_e: slug }, function (res) {
                const grid = $('#serviciosGrid').empty();
                if (res.success && res.data) {
                    res.data.forEach(s => {
                        grid.append(`
                        <div class="card-choice flex items-center justify-between" data-id="${s.id}" data-nombre="${s.nombre}" data-precio="${s.precio}" data-duracion="${s.duracion || 30}" data-type="servicio">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600"><i data-lucide="magic"></i></div>
                                <div>
                                    <div class="font-black text-gray-900">${s.nombre}</div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">${s.duracion || 30} MIN</div>
                                </div>
                            </div>
                            <div class="font-black text-teal-600">${CURRENCY.symbol}${s.precio || 0}</div>
                        </div>
                    `);
                    });
                }
            });
        }

        function loadEmpleados() {
            $('#empleadosGrid').html('<div class="text-center py-10 col-span-full"><i data-lucide="sync" class="animate-spin"></i></div>');
            $.get(API, { action: 'get_empleados', id_e: slug, sede_id: selection.sede.id, servicio_id: selection.servicio.id }, function (res) {
                const grid = $('#empleadosGrid').empty();
                if (res.success && res.data) {
                    res.data.forEach(e => {
                        let img = e.foto ? `src="${e.foto}"` : '';
                        let placeholder = !e.foto ? `<div class="w-full h-full bg-teal-50 flex items-center justify-center text-teal-600 text-2xl font-black">${e.nombre.charAt(0)}</div>` : `<img src="${e.foto}" class="w-full h-full object-cover">`;

                        grid.append(`
                        <div class="card-choice text-center" data-id="${e.id}" data-nombre="${e.nombre}" data-type="empleado">
                            <div class="w-20 h-20 rounded-full overflow-hidden mx-auto mb-4 border-4 border-white shadow-md">
                                ${placeholder}
                            </div>
                            <div class="font-black text-gray-900">${e.nombre}</div>
                            <div class="text-xs text-gray-400 uppercase font-bold tracking-widest mt-1">${e.puesto || ''}</div>
                        </div>
                    `);
                    });
                }
            });
        }

        // Step navigation
        $('#nextBtn').on('click', function () {
            if (currentStep === 1) loadServicios();
            if (currentStep === 2) loadEmpleados();
            if (currentStep === 5) prepareResumen();
            if (currentStep === 6) return finishBooking();

            currentStep++;
            maxReachedStep = Math.max(maxReachedStep, currentStep);
            updateWizard();
        });

        $('#prevBtn').on('click', function () {
            if (currentStep > 1) {
                currentStep--;
                updateWizard();
            }
        });

        $('body').on('click', '.step-nav', function () {
            const target = parseInt($(this).data('step'), 10);
            if (!target || target >= currentStep || target > maxReachedStep || currentStep === 7) return;
            currentStep = target;
            if (currentStep === 2 && !$('#serviciosGrid').children().length) loadServicios();
            if (currentStep === 3 && !$('#empleadosGrid').children().length && selection.sede && selection.servicio) loadEmpleados();
            if (currentStep === 6) prepareResumen();
            updateWizard();
        });

        // Selection Handlers
        $('body').on('click', '.card-choice', function () {
            const view = $(this).closest('.step-view');
            view.find('.card-choice').removeClass('selected');
            $(this).addClass('selected');

            const type = $(this).data('type');
            if (type === 'sede') {
                const sid = String($(this).data('id'));
                const row = sedesMap[sid] || {};
                const prevSedeId = selection.sede?.id || null;
                selection.sede = {
                    id: $(this).data('id'),
                    nombre: $(this).data('nombre'),
                    direccion: $(this).data('direccion') || '',
                    horarios_json: row.horarios_json || '{}'
                };
                if (!prevSedeId || String(prevSedeId) !== String(selection.sede.id)) {
                    clearDownstream(1);
                }
                renderSedeHorarioResumen();
                renderCalendar();
            }
            if (type === 'servicio') {
                const prevServicioId = selection.servicio?.id || null;
                selection.servicio = { id: $(this).data('id'), nombre: $(this).data('nombre'), precio: $(this).data('precio'), duracion: $(this).data('duracion') || 30 };
                if (!prevServicioId || String(prevServicioId) !== String(selection.servicio.id)) {
                    clearDownstream(2);
                }
            }
            if (type === 'empleado') {
                const prevEmp = selection.empleado?.id || null;
                selection.empleado = { id: $(this).data('id'), nombre: $(this).data('nombre') };
                if (!prevEmp || String(prevEmp) !== String(selection.empleado.id)) {
                    clearDownstream(3);
                }
            }

            validateStep();
            renderSelectedSummary();
        });

        // Simple Calendar and Slots
        function renderCalendar() {
            const cal = $('#calendar');
            cal.find('.js-cal-grid').remove();
            const now = new Date();
            const view = selection.monthCursor || new Date();
            $('#calendarMonthLabel').text(new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(view));
            const wrap = $('<div class="js-cal-grid"></div>');
            wrap.append(`<div class="grid grid-cols-7 gap-1 text-center font-black text-[10px] text-gray-300 uppercase mb-4">
            <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
        </div>`);
            const grid = $('<div class="grid grid-cols-7 gap-2"></div>');
            const firstDay = new Date(view.getFullYear(), view.getMonth(), 1).getDay();
            const days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) grid.append('<div></div>');
            for (let d = 1; d <= days; d++) {
                const date = `${view.getFullYear()}-${String(view.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const dateObj = new Date(`${date}T00:00:00`);
                const isPast = dateObj < new Date(now.toDateString());
                const dayRange = getSedeDayRange(dateObj);
                const isClosed = selection.sede ? !dayRange.active : false;
                const disabled = isPast || isClosed;
                const cls = disabled
                    ? 'text-gray-200 pointer-events-none bg-gray-50'
                    : 'hover:bg-teal-50 hover:text-teal-600';
                grid.append(`<div class="w-10 h-10 flex items-center justify-center rounded-xl font-bold cursor-pointer transition-all ${cls}" data-date="${date}" title="${isClosed ? 'Sucursal cerrada' : ''}">${d}</div>`);
            }
            wrap.append(grid);
            cal.append(wrap);
            if (window.lucide) lucide.createIcons();
        }
        function changeMonth(delta) {
            const c = selection.monthCursor || new Date();
            selection.monthCursor = new Date(c.getFullYear(), c.getMonth() + delta, 1);
            renderCalendar();
            selection.fecha = null;
            selection.hora = null;
            $('#timesGrid').html('<div class="col-span-full text-center py-10 text-gray-400 italic">Selecciona una fecha...</div>');
            validateStep();
        }
        function resetToTodayMonth() {
            const n = new Date();
            selection.monthCursor = new Date(n.getFullYear(), n.getMonth(), 1);
            renderCalendar();
            if (!selection.sede || !selection.servicio || !selection.empleado) return;
            const todayStr = n.toLocaleDateString('en-CA');
            const todayBtn = $(`[data-date="${todayStr}"]`);
            if (!todayBtn.length || todayBtn.hasClass('pointer-events-none')) return;
            $('[data-date]').removeClass('bg-teal-600 text-white').addClass('hover:bg-teal-50');
            todayBtn.removeClass('hover:bg-teal-50').addClass('bg-teal-600 text-white');
            selection.fecha = todayStr;
            selection.hora = null;
            loadSlots(todayStr);
            renderSelectedSummary();
            validateStep();
        }
        renderCalendar();
        $('#btnPrevMonth').on('click', () => changeMonth(-1));
        $('#btnNextMonth').on('click', () => changeMonth(1));
        $('#btnTodayMonth').on('click', resetToTodayMonth);

        $('body').on('click', '[data-date]', function () {
            $('[data-date]').removeClass('bg-teal-600 text-white').addClass('hover:bg-teal-50');
            $(this).removeClass('hover:bg-teal-50').addClass('bg-teal-600 text-white');
            selection.fecha = $(this).data('date');
            selection.hora = null;
            loadSlots(selection.fecha);
            renderSelectedSummary();
        });

        function loadSlots(fecha) {
            const grid = $('#timesGrid').html('<div class="col-span-full text-center py-10"><i data-lucide="sync" class="animate-spin"></i></div>');
            const selectedDay = new Date(`${fecha}T00:00:00`);
            const dayRange = getSedeDayRange(selectedDay);
            if (!dayRange.active) {
                $('#diaHorarioInfo').text('La sucursal está cerrada en este día. Selecciona otra fecha.');
                grid.html('<div class="col-span-full text-center py-10 text-gray-400 italic">Sucursal cerrada en esta fecha.</div>');
                selection.hora = null;
                validateStep();
                return;
            }
            $.get(API, {
                action: 'get_horarios',
                id_e: slug,
                sede_id: selection.sede.id,
                servicio_id: selection.servicio.id,
                empleado_id: selection.empleado.id,
                fecha: fecha,
                client_today: new Date().toLocaleDateString('en-CA'),
                client_time: new Date().toTimeString().slice(0, 5)
            }, function (res) {
                grid.empty();
                if (res && res.meta && res.meta.horario && res.meta.horario.inicio && res.meta.horario.fin) {
                    $('#diaHorarioInfo').text(`Horario de atención para este día: ${res.meta.horario.inicio} - ${res.meta.horario.fin}`);
                } else if (dayRange.inicio && dayRange.fin) {
                    $('#diaHorarioInfo').text(`Horario de atención para este día: ${dayRange.inicio} - ${dayRange.fin}`);
                } else {
                    $('#diaHorarioInfo').text('Horario de atención no definido para este día.');
                }
                if (res.success && res.data && res.data.length > 0) {
                    res.data.forEach(s => {
                        grid.append(`<div class="time-pill ${s.disponible ? 'available' : 'occupied'}" data-time="${s.hora}">${s.hora}</div>`);
                    });
                } else {
                    grid.html('<div class="col-span-full text-center py-10 text-gray-400 italic">No hay horarios disponibles para este día.</div>');
                }
                validateStep();
            });
        }

        $('body').on('click', '.time-pill.available', function () {
            $('.time-pill').removeClass('selected');
            $(this).addClass('selected');
            selection.hora = $(this).data('time');
            renderSelectedSummary();
            validateStep();
        });

        function prepareResumen() {
            $('#resSede').text(selection.sede.nombre);
            $('#resSedeDir').text(selection.sede.direccion || 'Dirección no disponible');
            $('#resServicio').text(selection.servicio.nombre);
            $('#resDuracion').text(`Duración: ${selection.servicio.duracion || 30} min`);
            $('#resEmpleado').text(selection.empleado.nombre);
            $('#resFecha').text(fmtFechaHora(selection.fecha, selection.hora));
            $('#resCliente').text(selection.p_nombre);
            $('#resPrecio').text(`${CURRENCY.symbol}${selection.servicio.precio || 0}`);
        }

        function googleCalendarUrl(data) {
            try {
                const start = new Date(String(data.inicio || '').replace(' ', 'T'));
                const end = new Date(String(data.fin || '').replace(' ', 'T'));
                const fmt = (d) => d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
                const details = [
                    `Servicio: ${data.servicio_nombre || ''}`,
                    `Código: ${data.codigo_publico || ''}`,
                    `Cliente: ${data.cliente_nombre || ''}`
                ].join('\n');
                const location = [data.sucursal_nombre || '', data.sucursal_direccion || ''].filter(Boolean).join(' - ');
                const base = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
                const p = new URLSearchParams({
                    text: `Cita: ${data.servicio_nombre || 'Servicio'}`,
                    dates: `${fmt(start)}/${fmt(end)}`,
                    details,
                    location
                });
                return `${base}&${p.toString()}`;
            } catch (e) {
                return '#';
            }
        }

        function renderConfirmation(data) {
            const code = data.codigo_publico || reservationCode || '';
            reservationCode = code;
            maxReachedStep = 6;
            currentStep = 7;
            updateWizard();

            const icsUrl = `${API}?action=calendar_file&id_e=${encodeURIComponent(slug)}&codigo=${encodeURIComponent(code)}`;
            const gcalUrl = googleCalendarUrl(data);
            const inicioTexto = data.inicio ? new Date(String(data.inicio).replace(' ', 'T')).toLocaleString('es-MX', { dateStyle: 'full', timeStyle: 'short' }) : `${selection.fecha} ${selection.hora}`;
            $('#wizardContainer').html(`
                <div class="text-center py-10 animate-fade-in">
                    <div class="w-28 h-28 bg-teal-50 rounded-full flex items-center justify-center text-teal-500 text-6xl mx-auto shadow-inner mb-6">
                        <i data-lucide="check"></i>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-3">¡Listo, tu cita fue agendada!</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto mb-6">Te enviamos los detalles al correo <span class="font-semibold text-gray-700">${data.cliente_email || selection.p_email || '-'}</span>.</p>
                    <div class="max-w-2xl mx-auto bg-white rounded-2xl border p-5 text-left space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div><span class="text-gray-500">Código de reserva:</span> <span class="font-black text-teal-700">${code || '-'}</span></div>
                            <div><span class="text-gray-500">Estado:</span> <span class="font-semibold capitalize">${data.estado || 'pendiente'}</span></div>
                            <div><span class="text-gray-500">Fecha y hora:</span> <span class="font-semibold">${inicioTexto}</span></div>
                            <div><span class="text-gray-500">Servicio:</span> <span class="font-semibold">${data.servicio_nombre || selection.servicio?.nombre || '-'}</span></div>
                            <div><span class="text-gray-500">Sede:</span> <span class="font-semibold">${data.sucursal_nombre || selection.sede?.nombre || '-'}</span></div>
                            <div><span class="text-gray-500">Especialista:</span> <span class="font-semibold">${data.empleado_nombre || selection.empleado?.nombre || '-'}</span></div>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="${icsUrl}" class="bg-teal-600 text-white px-8 py-3 rounded-2xl font-black shadow hover:bg-teal-700 transition">Guardar en mi calendario</a>
                        <a href="${gcalUrl}" target="_blank" rel="noopener" class="px-8 py-3 rounded-2xl border font-bold text-gray-700 hover:bg-gray-50 transition">Abrir en Google Calendar</a>
                    </div>
                </div>
            `);
            if (window.lucide) lucide.createIcons();
        }

        async function loadReservaFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const code = (params.get('reserva') || '').trim();
            if (!code) return false;
            try {
                const res = await $.get(API, { action: 'get_reserva', id_e: slug, codigo: code });
                if (res && res.success && res.data) {
                    reservationCode = code;
                    renderConfirmation(res.data);
                    return true;
                }
            } catch (e) {
            }
            return false;
        }

        async function finishBooking() {
            const btn = $('#nextBtn');
            btn.prop('disabled', true).html('<i data-lucide="loader-2" class="mr-2 animate-spin"></i> Procesando...');

            try {
                const res = await $.post(API, {
                    action: 'save_cita',
                    id_e: slug,
                    sede_id: selection.sede.id,
                    servicio_id: selection.servicio.id,
                    empleado_id: selection.empleado.id,
                    fecha: selection.fecha,
                    hora: selection.hora,
                    nombre: selection.p_nombre,
                    email: selection.p_email,
                    telefono: selection.p_telefono,
                    notas: $('#cli_notas').val(),
                    cliente_lookup_token: clienteLookupToken,
                    client_today: new Date().toLocaleDateString('en-CA'),
                    client_time: new Date().toTimeString().slice(0, 5)
                });

                if (res.success) {
                    try {
                        localStorage.setItem(localProfileKey, JSON.stringify({
                            nombre: selection.p_nombre || '',
                            email: selection.p_email || '',
                            telefono: selection.p_telefono || '',
                        }));
                    } catch (e) {
                    }
                    reservationCode = res.codigo || '';
                    if (reservationCode) {
                        updateUrlReserva(reservationCode);
                        const byCode = await $.get(API, { action: 'get_reserva', id_e: slug, codigo: reservationCode });
                        if (byCode && byCode.success && byCode.data) {
                            renderConfirmation(byCode.data);
                            return;
                        }
                    }
                    renderConfirmation({
                        codigo_publico: reservationCode || `RES-${res.id}`,
                        cliente_email: selection.p_email,
                        estado: 'pendiente',
                        inicio: `${selection.fecha} ${selection.hora}:00`,
                        servicio_nombre: selection.servicio?.nombre || '',
                        sucursal_nombre: selection.sede?.nombre || '',
                        empleado_nombre: selection.empleado?.nombre || ''
                    });
                } else {
                    throw new Error(res.message || 'Error al crear la cita');
                }

            } catch (e) {
                const msg = (e && e.message) ? e.message : 'No se pudo crear la cita.';
                if (typeof showCustomAlert === 'function') {
                    showCustomAlert(msg, 6000, 'error');
                } else {
                    alert('Error: ' + msg);
                }
                btn.prop('disabled', false).html('Confirmar Cita');
            }
        }

        (async function init() {
            const restored = await loadReservaFromUrl();
            if (restored) return;
            updateWizard();
            renderSedeHorarioResumen();
            renderSelectedSummary();
            validateStep();
        })();
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
