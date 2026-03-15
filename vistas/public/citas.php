<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$slug = $_GET['id_e'] ?? null;
if (!$slug) {
    http_response_code(404);
    $module = '404';
    include __DIR__ . '/../../includes/topbar.php';
    include __DIR__ . '/../404.php';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
$stmt->execute([$slug]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    http_response_code(404);
    $_GET['id_e'] = $_GET['_empresa'] = null; // Limpiar contexto
    $module = '404';
    include __DIR__ . '/../../includes/topbar.php';
    include __DIR__ . '/../404.php';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

$preselected_sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
$module = 'citas';
include __DIR__ . '/../../includes/topbar.php';
?>

<style>
    .step-dot {
        transition: all 0.3s ease;
    }

    .step-dot.active {
        @apply bg-teal-600 scale-125 shadow-lg shadow-teal-200;
    }

    .step-dot.completed {
        @apply bg-teal-500;
    }

    .card-choice {
        @apply border-2 border-transparent bg-white shadow-sm hover:shadow-md hover:border-teal-200 transition-all cursor-pointer rounded-2xl p-5;
    }

    .card-choice.selected {
        @apply border-teal-500 bg-teal-50/30 shadow-teal-100;
    }

    .time-pill {
        @apply px-4 py-3 rounded-xl border font-bold text-center transition-all cursor-pointer;
    }

    .time-pill.available {
        @apply bg-white border-gray-200 hover:border-teal-500 hover:text-teal-600;
    }

    .time-pill.selected {
        @apply bg-teal-600 border-teal-600 text-white shadow-lg;
    }

    .time-pill.occupied {
        @apply bg-gray-50 border-gray-100 text-gray-300 cursor-not-allowed;
    }
</style>

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

        <div class="flex flex-col items-center gap-2 group" data-step="1">
            <div
                class="step-dot active w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                1</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Sede</span>
        </div>
        <div class="flex flex-col items-center gap-2" data-step="2">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                2</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Servicio</span>
        </div>
        <div class="flex flex-col items-center gap-2" data-step="3">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                3</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Empleado</span>
        </div>
        <div class="flex flex-col items-center gap-2" data-step="4">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                4</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Fecha</span>
        </div>
        <div class="flex flex-col items-center gap-2" data-step="5">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                5</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Datos</span>
        </div>
        <div class="flex flex-col items-center gap-2" data-step="6">
            <div
                class="step-dot w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400">
                6</div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Confirmación</span>
        </div>
    </div>

    <!-- Wizard Steps Container -->
    <div id="wizardContainer"
        class="bg-white/50 backdrop-blur-sm rounded-[2.5rem] p-8 md:p-12 border border-white shadow-2xl relative min-h-[500px]">

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
            <div id="serviciosGrid" class="grid grid-cols-1 gap-4 max-h-[400px] overflow-y-auto pr-2">
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div id="calendar" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <!-- Custom Calendar JS -->
                </div>
                <div class="space-y-6">
                    <h3
                        class="font-black text-gray-900 uppercase text-xs tracking-widest text-center py-2 bg-gray-50 rounded-lg">
                        Horas Disponibles</h3>
                    <div id="timesGrid" class="grid grid-cols-3 gap-3">
                        <div class="col-span-full text-center py-10 text-gray-400 italic">Selecciona una fecha...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Datos Personales -->
        <div id="stepView5" class="step-view hidden space-y-8">
            <h2 class="text-2xl font-black text-gray-900 border-l-4 border-teal-500 pl-4">Tus Datos de Contacto</h2>
            <form id="datosForm" class="max-w-2xl space-y-6">
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Nombre
                        Completo</label>
                    <input type="text" id="cli_nombre" name="nombre"
                        class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                        required placeholder="Juan Pérez">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Email</label>
                        <input type="email" id="cli_email" name="email"
                            class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                            required placeholder="juan@ejemplo.com">
                    </div>
                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Teléfono</label>
                        <input type="tel" id="cli_telefono" name="telefono"
                            class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                            required placeholder="55 1234 5678">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest ml-1">Notas
                        Adicionales</label>
                    <textarea id="cli_notas" name="notas"
                        class="w-full p-4 bg-white border border-gray-100 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all"
                        rows="3" placeholder="¿Algo que debamos saber?"></textarea>
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
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 bg-purple-50 rounded-full flex items-center justify-center text-purple-600 mt-1">
                                <i data-lucide="user-tie"></i>
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
                    <div class="text-gray-400 italic text-sm">Al confirmar, tu cita quedará registrada.</div>
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

        let currentStep = 1;
        const selection = {
            sede: null,
            servicio: null,
            empleado: null,
            fecha: null,
            hora: null,
            p_nombre: '',
            p_email: '',
            p_telefono: '',
            p_notas: ''
        };

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

            validateStep();
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
                ok = (selection.p_nombre && selection.p_email && selection.p_telefono);
            }
            if (currentStep === 6) ok = true;

            $('#nextBtn').prop('disabled', !ok);
        }

        $('#cli_nombre, #cli_email, #cli_telefono').on('input', validateStep);

        // Load Sedes
        $.get(API, { action: 'get_sucursales', id_e: slug }, function (res) {
            if (res.success && res.data) {
                const grid = $('#sedesGrid').empty();
                res.data.forEach(s => {
                    grid.append(`
                    <div class="card-choice ${preselectedSedeId == s.id ? 'selected' : ''}" data-id="${s.id}" data-nombre="${s.nombre}" data-type="sede">
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
                    selection.sede = res.data.find(s => s.id == preselectedSedeId);
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
                        <div class="card-choice flex items-center justify-between" data-id="${s.id}" data-nombre="${s.nombre}" data-precio="${s.precio}" data-type="servicio">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600"><i data-lucide="magic"></i></div>
                                <div>
                                    <div class="font-black text-gray-900">${s.nombre}</div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">${s.duracion || 30} MIN</div>
                                </div>
                            </div>
                            <div class="font-black text-teal-600">$${s.precio || 0}</div>
                        </div>
                    `);
                    });
                }
            });
        }

        function loadEmpleados() {
            $('#empleadosGrid').html('<div class="text-center py-10 col-span-full"><i data-lucide="sync" class="animate-spin"></i></div>');
            $.get(API, { action: 'get_empleados', id_e: slug, sede_id: selection.sede.id }, function (res) {
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
            updateWizard();
        });

        $('#prevBtn').on('click', function () {
            if (currentStep > 1) {
                currentStep--;
                updateWizard();
            }
        });

        // Selection Handlers
        $('body').on('click', '.card-choice', function () {
            const view = $(this).closest('.step-view');
            view.find('.card-choice').removeClass('selected');
            $(this).addClass('selected');

            const type = $(this).data('type');
            if (type === 'sede') selection.sede = { id: $(this).data('id'), nombre: $(this).data('nombre') };
            if (type === 'servicio') selection.servicio = { id: $(this).data('id'), nombre: $(this).data('nombre'), precio: $(this).data('precio') };
            if (type === 'empleado') selection.empleado = { id: $(this).data('id'), nombre: $(this).data('nombre') };

            validateStep();
        });

        // Simple Calendar and Slots
        function renderCalendar() {
            const cal = $('#calendar').empty();
            const now = new Date();
            cal.append(`<div class="grid grid-cols-7 gap-1 text-center font-black text-[10px] text-gray-300 uppercase mb-4">
            <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
        </div>`);
            const grid = $('<div class="grid grid-cols-7 gap-2"></div>');
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).getDay();
            const days = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) grid.append('<div></div>');
            for (let d = 1; d <= days; d++) {
                const date = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const isPast = new Date(date) < new Date(now.toDateString());
                grid.append(`<div class="w-10 h-10 flex items-center justify-center rounded-xl font-bold cursor-pointer transition-all ${isPast ? 'text-gray-200 pointer-events-none' : 'hover:bg-teal-50 hover:text-teal-600'}" data-date="${date}">${d}</div>`);
            }
            cal.append(grid);
        }
        renderCalendar();

        $('body').on('click', '[data-date]', function () {
            $('[data-date]').removeClass('bg-teal-600 text-white').addClass('hover:bg-teal-50');
            $(this).removeClass('hover:bg-teal-50').addClass('bg-teal-600 text-white');
            selection.fecha = $(this).data('date');
            loadSlots(selection.fecha);
        });

        function loadSlots(fecha) {
            const grid = $('#timesGrid').html('<div class="col-span-full text-center py-10"><i data-lucide="sync" class="animate-spin"></i></div>');
            $.get(API, {
                action: 'get_horarios',
                id_e: slug,
                sede_id: selection.sede.id,
                servicio_id: selection.servicio.id,
                empleado_id: selection.empleado.id,
                fecha: fecha
            }, function (res) {
                grid.empty();
                if (res.success && res.data && res.data.length > 0) {
                    res.data.forEach(s => {
                        grid.append(`<div class="time-pill ${s.disponible ? 'available' : 'occupied'}" data-time="${s.hora}">${s.hora}</div>`);
                    });
                } else {
                    grid.html('<div class="col-span-full text-center py-10 text-gray-400 italic">No hay horarios disponibles para este día.</div>');
                }
            });
        }

        $('body').on('click', '.time-pill.available', function () {
            $('.time-pill').removeClass('selected');
            $(this).addClass('selected');
            selection.hora = $(this).data('time');
            validateStep();
        });

        function prepareResumen() {
            $('#resSede').text(selection.sede.nombre);
            $('#resServicio').text(selection.servicio.nombre);
            $('#resEmpleado').text(selection.empleado.nombre);
            $('#resFecha').text(`${selection.fecha} a las ${selection.hora}`);
            $('#resCliente').text(selection.p_nombre);
            $('#resPrecio').text(`$${selection.servicio.precio || 0}`);
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
                    notas: $('#cli_notas').val()
                });

                if (res.success) {
                    currentStep = 7;
                    updateWizard();
                    $('#wizardContainer').html(`
                    <div class="text-center py-20 animate-fade-in">
                        <div class="w-32 h-32 bg-teal-50 rounded-full flex items-center justify-center text-teal-500 text-6xl mx-auto shadow-inner mb-8">
                            <i data-lucide="check"></i>
                        </div>
                        <h2 class="text-4xl font-black text-gray-900 mb-4">¡Listo, ${selection.p_nombre.split(' ')[0]}!</h2>
                        <p class="text-gray-500 max-w-sm mx-auto mb-10 text-lg">Tu cita ha sido agendada con éxito para el <span class="font-bold text-gray-900">${selection.fecha} a las ${selection.hora}</span>. Te esperamos.</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="<?= view_url('vistas/public/inicio.php', $slug) ?>" class="bg-gray-900 text-white px-10 py-4 rounded-2xl font-black shadow-xl hover:bg-black transition-all">Volver al Inicio</a>
                        </div>
                    </div>
                `);
                } else {
                    throw new Error(res.message || 'Error al crear la cita');
                }

            } catch (e) {
                alert("Error: " + e.message);
                btn.prop('disabled', false).html('Confirmar Cita');
            }
        }
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>