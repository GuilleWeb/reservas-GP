<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'mis-citas';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-6">

    <!-- 1. MIS CITAS AGENDADAS (Lista) -->
    <div class="lg:w-1/3 bg-white shadow rounded-2xl p-6 border flex flex-col h-[calc(100vh-140px)] min-h-[500px]">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800">Mis Citas</h2>
            <p class="text-xs text-gray-500">Historial de reservas.</p>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg p-2 space-y-3" id="mis-citas-lista">
            <div class="animate-pulse text-center py-10">Cargando mis citas...</div>
        </div>

        <div class="mt-4 pt-4 border-t" id="pagination-citas"></div>
    </div>

    <!-- 2. WIZARD PARA NUEVA CITA -->
    <div
        class="lg:w-2/3 bg-white shadow rounded-2xl overflow-hidden border flex flex-col h-[calc(100vh-140px)] min-h-[500px]">
        <div class="bg-teal-600 text-white p-4 flex justify-between items-center">
            <h2 class="font-bold uppercase tracking-widest text-sm">Nueva Cita</h2>
            <div class="flex items-center space-x-2 text-xs">
                <span id="step-count">Paso 1 de 4</span>
                <div class="w-24 h-2 bg-teal-800 rounded-full overflow-hidden">
                    <div id="progress-bar" class="w-1/4 h-full bg-teal-300 transition-all"></div>
                </div>
            </div>
        </div>

        <div class="flex-1 p-6 overflow-auto" id="wizard-container">
            <!-- Step 1: Sede -->
            <div id="step-1" class="step">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">¿Dónde te esperamos?</h3>
                    <p class="text-gray-500">Selecciona una de nuestras sucursales.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="sede-list"></div>
            </div>

            <!-- Step 2: Servicio -->
            <div id="step-2" class="step hidden">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">¿Qué servicio necesitas?</h3>
                    <p class="text-gray-500">Elige lo que deseas realizarte hoy.</p>
                    <button onclick="goToStep(1)" class="text-teal-600 text-xs mt-2 hover:underline"><i
                            class="fas fa-arrow-left"></i> Cambiar Sede</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="servicio-list"></div>
            </div>

            <!-- Step 3: Fecha y Hora -->
            <div id="step-3" class="step hidden">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Agenda tu visita</h3>
                    <p class="text-gray-500">Selecciona una fecha disponible en el calendario.</p>
                    <button onclick="goToStep(2)" class="text-teal-600 text-xs mt-2 hover:underline"><i
                            class="fas fa-arrow-left"></i> Cambiar Servicio</button>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <div id="calendar-wrapper" class="w-full md:w-1/2 p-4 bg-gray-50 rounded-xl border">
                        <div class="flex justify-between items-center mb-4">
                            <button onclick="changeMonth(-1)" class="p-1 hover:bg-white rounded"><i
                                    class="fas fa-chevron-left"></i></button>
                            <span id="calendar-title" class="font-bold text-sm">Mes Año</span>
                            <button onclick="changeMonth(1)" class="p-1 hover:bg-white rounded"><i
                                    class="fas fa-chevron-right"></i></button>
                        </div>
                        <div
                            class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-gray-400 mb-2 uppercase">
                            <span>D</span><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span>
                        </div>
                        <div id="calendar-days" class="grid grid-cols-7 gap-1"></div>
                    </div>

                    <div class="w-full md:w-1/2">
                        <h4 class="font-bold text-gray-700 text-sm mb-3">Horas Disponibles</h4>
                        <div id="hour-list" class="grid grid-cols-3 gap-2">
                            <div class="col-span-3 text-center text-gray-400 py-10">Selecciona una fecha...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Resumen -->
            <div id="step-4" class="step hidden">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">¿Confirmamos tu cita?</h3>
                    <p class="text-gray-500">Revisa los detalles finales antes de agendar.</p>
                </div>
                <div class="bg-teal-50 rounded-2xl p-6 border border-teal-200">
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-teal-100 pb-2">
                            <span class="text-teal-700 font-medium">Sede:</span>
                            <span id="res-sede" class="font-bold text-teal-900"></span>
                        </div>
                        <div class="flex justify-between border-b border-teal-100 pb-2">
                            <span class="text-teal-700 font-medium">Servicio:</span>
                            <span id="res-servicio" class="font-bold text-teal-900"></span>
                        </div>
                        <div class="flex justify-between border-b border-teal-100 pb-2">
                            <span class="text-teal-700 font-medium">Fecha:</span>
                            <span id="res-fecha" class="font-bold text-teal-900"></span>
                        </div>
                        <div class="flex justify-between border-b border-teal-100 pb-2">
                            <span class="text-teal-700 font-medium">Hora:</span>
                            <span id="res-hora" class="font-bold text-teal-900"></span>
                        </div>
                        <div>
                            <span class="block text-teal-700 font-medium mb-1 text-sm">Notas Adicionales:</span>
                            <textarea id="cita-notas"
                                class="w-full border-teal-200 rounded-xl p-3 text-sm focus:ring-teal-500 border"
                                placeholder="Algo que debamos saber..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button onclick="goToStep(3)"
                        class="flex-1 py-3 border border-teal-600 text-teal-600 rounded-xl font-bold hover:bg-teal-50">Modificar
                        Hora</button>
                    <button onclick="saveCita()" id="btn-confirm"
                        class="flex-[2] py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 shadow-lg shadow-teal-100">Confirmar
                        Reservación</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const API_URL = '<?= app_url('api/cliente/citas.php') ?>';
    const State = {
        step: 1,
        sede: null,
        servicio: null,
        fecha: null,
        hora: null,
        month: new Date()
    };

    function getEstadoBadge(estado) {
        const s = {
            'pendiente': 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'confirmada': 'bg-blue-100 text-blue-800 border-blue-200',
            'completada': 'bg-green-100 text-green-800 border-green-200',
            'cancelada': 'bg-red-100 text-red-800 border-red-200'
        };
        return `<span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold border ${s[estado] || 'bg-gray-100 border-gray-200'}">${estado}</span>`;
    }

    function loadMyCitas(page = 1) {
        $.get(API_URL, { action: 'list', page: page }, function (res) {
            const list = $('#mis-citas-lista').empty();
            if (res.success && res.data.length > 0) {
                res.data.forEach(c => {
                    const d = new Date(c.inicio.replace(' ', 'T'));
                    list.append(`
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-bold text-gray-800 text-sm">${c.servicio_nombre}</span>
                            ${getEstadoBadge(c.estado)}
                        </div>
                        <div class="text-[11px] text-gray-500 mb-2">
                             <i class="far fa-calendar-alt mr-1"></i> ${d.toLocaleDateString()} a las ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </div>
                        <div class="text-[10px] text-teal-600 bg-teal-50 px-2 py-1 rounded inline-block">
                             <i class="fas fa-map-marker-alt mr-1"></i> ${c.sucursal_nombre}
                        </div>
                    </div>
                `);
                });
            } else {
                list.html('<div class="text-center py-20 text-gray-400">Aún no tienes citas agendadas.</div>');
            }
        });
    }

    function goToStep(s) {
        State.step = s;
        $('.step').addClass('hidden');
        $(`#step-${s}`).removeClass('hidden');
        $('#step-count').text(`Paso ${s} de 4`);
        $('#progress-bar').css('width', `${(s / 4) * 100}%`);

        if (s === 1) loadSedes();
        if (s === 2) loadServicios();
        if (s === 3) renderCalendar();
        if (s === 4) renderSummary();
    }

    function loadSedes() {
        $('#sede-list').html('<div class="col-span-2 text-center py-10 animate-pulse">Cargando sedes...</div>');
        $.get(API_URL, { action: 'get_sedes' }, function (res) {
            const list = $('#sede-list').empty();
            res.data.forEach(s => {
                const isSel = State.sede && State.sede.id === s.id;
                list.append(`
                <button onclick='selectSede(${JSON.stringify(s)})' class="text-left p-4 rounded-2xl border-2 transition-all hover:border-teal-400 ${isSel ? 'border-teal-500 bg-teal-50 shadow-md shadow-teal-100' : 'border-gray-100 bg-white'}">
                    <div class="font-bold text-gray-800">${s.nombre}</div>
                    <div class="text-[11px] text-gray-500 mt-1">${s.direccion || ''}</div>
                </button>
            `);
            });
        });
    }

    function selectSede(s) {
        State.sede = s;
        goToStep(2);
    }

    function loadServicios() {
        $('#servicio-list').html('<div class="col-span-2 text-center py-10 animate-pulse">Cargando servicios...</div>');
        $.get(API_URL, { action: 'get_servicios' }, function (res) {
            const list = $('#servicio-list').empty();
            res.data.forEach(s => {
                const isSel = State.servicio && State.servicio.id === s.id;
                list.append(`
                <button onclick='selectServicio(${JSON.stringify(s)})' class="text-left p-4 rounded-2xl border-2 transition-all hover:border-teal-400 ${isSel ? 'border-teal-500 bg-teal-50 shadow-md shadow-teal-100' : 'border-gray-100 bg-white'}">
                    <div class="flex justify-between">
                        <div class="font-bold text-gray-800">${s.nombre}</div>
                        <div class="text-teal-600 font-bold">$${parseFloat(s.precio_base).toFixed(2)}</div>
                    </div>
                    <div class="text-[10px] text-gray-500 mt-1">${s.duracion_minutos} minutos • ${s.descripcion || 'Sin descripción'}</div>
                </button>
            `);
            });
        });
    }

    function selectServicio(s) {
        State.servicio = s;
        goToStep(3);
    }

    function changeMonth(dir) {
        State.month.setMonth(State.month.getMonth() + dir);
        renderCalendar();
    }

    function renderCalendar() {
        const $days = $('#calendar-days').empty();
        const date = State.month;
        const year = date.getFullYear();
        const month = date.getMonth();
        const today = new Date(); today.setHours(0, 0, 0, 0);

        const names = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        $('#calendar-title').text(`${names[month]} ${year}`);

        const start = new Date(year, month, 1).getDay();
        const end = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < start; i++) $days.append('<div></div>');

        for (let d = 1; d <= end; d++) {
            const dStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
            const curr = new Date(year, month, d);
            const isPast = curr < today;
            const isSel = State.fecha === dStr;

            $days.append(`
            <button onclick="selectDate('${dStr}')" ${isPast ? 'disabled' : ''} class="h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all ${isPast ? 'text-gray-200 cursor-not-allowed' : isSel ? 'bg-teal-600 text-white' : 'hover:bg-teal-100 text-gray-700'}">
                ${d}
            </button>
        `);
        }
    }

    function selectDate(d) {
        State.fecha = d;
        renderCalendar();
        loadSlots(d);
    }

    function loadSlots(d) {
        $('#hour-list').html('<div class="col-span-3 text-center py-5 animate-pulse text-xs">Calculando disponibilidad...</div>');
        $.get(API_URL, { action: 'get_booked_slots', sede_id: State.sede.id, fecha: d }, function (res) {
            const booked = res.data || [];
            const slots = $('#hour-list').empty();

            // Simulación de slots de 8am a 6pm
            for (let h = 8; h < 18; h++) {
                const time = `${h.toString().padStart(2, '0')}:00`;
                const isBooked = booked.includes(time);
                const isSel = State.hora === time;

                slots.append(`
                <button onclick="selectHour('${time}')" ${isBooked ? 'disabled' : ''} class="py-2 rounded-lg text-xs font-bold border transition-all ${isBooked ? 'bg-red-50 text-red-200 border-red-50 cursor-not-allowed' : 'hover:border-teal-500 ' + (isSel ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-100' : 'text-gray-700')}">
                    ${time}
                </button>
            `);
            }
        });
    }

    function selectHour(h) {
        State.hora = h;
        goToStep(4);
    }

    function renderSummary() {
        $('#res-sede').text(State.sede.nombre);
        $('#res-servicio').text(State.servicio.nombre);
        $('#res-fecha').text(State.fecha);
        $('#res-hora').text(State.hora);
    }

    function saveCita() {
        const btn = $('#btn-confirm');
        const oldHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Agendando...');

        $.post(API_URL + '?action=save', {
            sede_id: State.sede.id,
            servicio_id: State.servicio.id,
            fecha: `${State.fecha} ${State.hora}:00`,
            notas: $('#cita-notas').val()
        }, function (res) {
            if (res.success) {
                alert('¡Cita Confirmada!');
                State.sede = null; State.servicio = null; State.fecha = null; State.hora = null;
                loadMyCitas();
                goToStep(1);
            } else {
                alert(res.message || 'Error al agendar');
                btn.prop('disabled', false).html(oldHtml);
            }
        }, 'json');
    }

    $(function () {
        loadMyCitas();
        goToStep(1);
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>