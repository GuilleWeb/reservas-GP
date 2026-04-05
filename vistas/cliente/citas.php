<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$module = 'mis-citas';
include __DIR__ . '/../../includes/topbar.php';
if (!$user || (string) ($user['rol'] ?? '') !== 'cliente') {
    http_response_code(403);
    include __DIR__ . '/../403.php';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}
?>

<div class="max-w-7xl mx-auto grid grid-cols-1 xl:grid-cols-12 gap-6">
  <div class="xl:col-span-4">
    <div class="bg-white rounded-2xl border shadow p-5">
      <div class="font-bold text-gray-900 text-lg">Mis Citas</div>
      <div class="text-xs text-gray-500 mb-4">Se listan por tu usuario y por tu correo registrado.</div>
      <div id="listCitas" class="space-y-3 max-h-[72vh] overflow-auto"></div>
      <div id="pagCitas" class="mt-4 flex flex-wrap gap-2"></div>
    </div>
  </div>

  <div class="xl:col-span-8">
    <div class="bg-white rounded-2xl border shadow p-6">
      <h1 class="text-2xl font-black text-gray-900">Agendar Nueva Cita</h1>
      <p class="text-sm text-gray-500 mt-1">Usa el mismo flujo de la vista pública, adaptado para tu cuenta cliente.</p>

      <div class="relative flex justify-between items-center max-w-2xl mx-auto my-8">
        <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 -z-10"></div>
        <div id="progressLine" class="absolute top-1/2 left-0 w-0 h-1 bg-teal-500 -translate-y-1/2 -z-10 transition-all duration-500"></div>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <div class="step-nav flex flex-col items-center gap-2 <?= $i === 1 ? '' : 'disabled' ?>" data-step="<?= $i ?>">
            <div class="step-dot <?= $i === 1 ? 'active' : '' ?> w-10 h-10 rounded-full flex items-center justify-center bg-white border-4 border-gray-100 text-sm font-black text-gray-400"><?= $i ?></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400"><?= ['Sede', 'Servicio', 'Empleado', 'Fecha', 'Confirmar'][$i - 1] ?></span>
          </div>
        <?php endfor; ?>
      </div>

      <div id="wizard" class="space-y-6">
        <div id="step1" class="step-view">
          <h3 class="text-xl font-black text-gray-900">Elige una sede</h3>
          <div id="sedesGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"></div>
        </div>

        <div id="step2" class="step-view hidden">
          <h3 class="text-xl font-black text-gray-900">Elige un servicio</h3>
          <div id="serviciosGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"></div>
        </div>

        <div id="step3" class="step-view hidden">
          <h3 class="text-xl font-black text-gray-900">Elige colaborador</h3>
          <div id="empleadosGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4"></div>
        </div>

        <div id="step4" class="step-view hidden">
          <h3 class="text-xl font-black text-gray-900">Selecciona fecha y hora</h3>
          <div id="diaHorarioInfo" class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 mt-2">Selecciona una fecha para ver disponibilidad.</div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
            <div id="calendar" class="bg-white p-4 rounded-2xl border"></div>
            <div>
              <div class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Horas disponibles</div>
              <div id="timesGrid" class="grid grid-cols-3 gap-2"></div>
            </div>
          </div>
        </div>

        <div id="step5" class="step-view hidden">
          <h3 class="text-xl font-black text-gray-900">Confirmación</h3>
          <div class="bg-gray-50 rounded-2xl border p-5 mt-4 space-y-3 text-sm">
            <div><span class="text-gray-500">Sede:</span> <span id="rSede" class="font-semibold"></span></div>
            <div><span class="text-gray-500">Servicio:</span> <span id="rServicio" class="font-semibold"></span></div>
            <div><span class="text-gray-500">Empleado:</span> <span id="rEmpleado" class="font-semibold"></span></div>
            <div><span class="text-gray-500">Fecha:</span> <span id="rFecha" class="font-semibold"></span></div>
            <div><span class="text-gray-500">Hora:</span> <span id="rHora" class="font-semibold"></span></div>
            <div class="pt-2">
              <label class="text-xs font-black uppercase tracking-widest text-gray-400">Notas</label>
              <textarea id="notas" class="w-full border rounded-xl p-3 mt-2" rows="3" placeholder="Opcional"></textarea>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t flex items-center justify-between">
          <button id="prevBtn" class="invisible px-6 py-3 rounded-xl border font-bold text-gray-600 hover:bg-gray-50">Volver</button>
          <button id="nextBtn" class="px-8 py-3 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API = <?= json_encode(app_url('api/cliente/citas.php')) ?>;
    let page = 1;
    let currentStep = 1;
    let maxReachedStep = 1;
    const DAY_KEYS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
    const selection = { sede: null, servicio: null, empleado: null, fecha: null, hora: null, monthCursor: new Date() };

    function badge(estado) {
      const map = { pendiente: 'bg-amber-50 text-amber-700 border-amber-200', confirmada: 'bg-blue-50 text-blue-700 border-blue-200', completada: 'bg-emerald-50 text-emerald-700 border-emerald-200', cancelada: 'bg-red-50 text-red-700 border-red-200' };
      return `<span class="inline-flex px-2 py-1 rounded-full text-xs border ${map[estado] || 'bg-gray-100 text-gray-700 border-gray-200'}">${estado || '-'}</span>`;
    }
    function loadMyCitas() {
      $.get(API, { action: 'list', page, per: 10 }, function (res) {
        const box = $('#listCitas').empty();
        const pag = $('#pagCitas').empty();
        if (!(res && res.success && (res.data || []).length)) {
          box.html('<div class="text-sm text-gray-500">No tienes citas registradas.</div>');
          return;
        }
        (res.data || []).forEach(c => {
          box.append(`<div class="p-3 rounded-xl border bg-gray-50">
            <div class="flex justify-between items-center">${badge(c.estado)}<span class="text-xs text-gray-500">${(c.inicio || '').slice(0,16).replace('T',' ')}</span></div>
            <div class="font-bold text-gray-900 mt-1">${c.servicio_nombre || '-'}</div>
            <div class="text-xs text-gray-500">${c.sucursal_nombre || '-'}</div>
          </div>`);
        });
        const totalPages = parseInt(res.total_pages || 1, 10);
        for (let i = 1; i <= totalPages; i++) {
          pag.append(`<button class="px-3 py-1 rounded ${i===page?'bg-teal-600 text-white':'border'} pgBtn" data-page="${i}">${i}</button>`);
        }
      }, 'json');
    }

    function updateDots() {
      $('.step-view').addClass('hidden');
      $(`#step${currentStep}`).removeClass('hidden');
      $('.step-dot').removeClass('active completed text-white').addClass('text-gray-400 border-gray-100').text(function(){ return $(this).closest('.step-nav').data('step'); });
      for (let i = 1; i < currentStep; i++) {
        $(`.step-nav[data-step="${i}"] .step-dot`).addClass('completed text-white').html('<i data-lucide="check"></i>');
      }
      $(`.step-nav[data-step="${currentStep}"] .step-dot`).addClass('active text-teal-700 border-teal-500');
      $('#progressLine').css('width', ((currentStep - 1) / 4 * 100) + '%');
      $('#prevBtn').toggleClass('invisible', currentStep === 1);
      $('#nextBtn').text(currentStep === 5 ? 'Confirmar cita' : 'Siguiente');
      $('.step-nav').each(function () {
        const st = parseInt($(this).data('step'), 10);
        $(this).toggleClass('disabled', st > maxReachedStep);
      });
      if (window.lucide) lucide.createIcons();
      validateStep();
    }

    function validateStep() {
      let ok = false;
      if (currentStep === 1) ok = !!selection.sede;
      if (currentStep === 2) ok = !!selection.servicio;
      if (currentStep === 3) ok = !!selection.empleado;
      if (currentStep === 4) ok = !!(selection.fecha && selection.hora);
      if (currentStep === 5) ok = true;
      $('#nextBtn').prop('disabled', !ok);
    }

    function loadSedes() {
      $.get(API, { action: 'get_sedes' }, function (res) {
        const box = $('#sedesGrid').empty();
        (res.data || []).forEach(s => {
          const img = s.foto_path ? `/${String(s.foto_path).replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=900';
          box.append(`<button type="button" class="card-choice text-left p-4 rounded-xl border hover:border-teal-300" data-type="sede" data-id="${s.id}" data-nombre="${(s.nombre||'').replace(/"/g,'&quot;')}" data-horarios='${JSON.stringify(s.horarios_json || "{}").replace(/'/g, '&#39;')}'>
            <div class="h-32 rounded-lg overflow-hidden bg-gray-100 mb-3"><img src="${img}" class="w-full h-full object-cover"></div>
            <div class="font-bold text-gray-900">${s.nombre || '-'}</div>
            <div class="text-xs text-gray-500">${s.direccion || ''}</div>
          </button>`);
        });
      }, 'json');
    }

    function loadServicios() {
      $.get(API, { action: 'get_servicios' }, function (res) {
        const box = $('#serviciosGrid').empty();
        (res.data || []).forEach(s => {
          box.append(`<button type="button" class="card-choice text-left p-4 rounded-xl border hover:border-teal-300" data-type="servicio" data-id="${s.id}" data-nombre="${(s.nombre||'').replace(/"/g,'&quot;')}" data-duracion="${s.duracion_minutos || 30}">
            <div class="font-bold text-gray-900">${s.nombre || '-'}</div>
            <div class="text-xs text-gray-500">${s.duracion_minutos || 30} min · ${(window.APP_CURRENCY?.symbol||'Q')}${parseFloat(s.precio_base || 0).toFixed(2)}</div>
          </button>`);
        });
      }, 'json');
    }

    function loadEmpleados() {
      if (!selection.sede || !selection.servicio) return;
      $.get(API, { action: 'get_empleados', sede_id: selection.sede.id, servicio_id: selection.servicio.id }, function (res) {
        const box = $('#empleadosGrid').empty();
        (res.data || []).forEach(e => {
          box.append(`<button type="button" class="card-choice text-center p-4 rounded-xl border hover:border-teal-300" data-type="empleado" data-id="${e.id}" data-nombre="${(e.nombre||'').replace(/"/g,'&quot;')}">
            <div class="font-bold text-gray-900">${e.nombre || '-'}</div>
            <div class="text-xs text-gray-500">${e.puesto || ''}</div>
          </button>`);
        });
      }, 'json');
    }

    function parseHorarios(raw) {
      try { return typeof raw === 'string' ? JSON.parse(raw || '{}') : (raw || {}); } catch (e) { return {}; }
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
      return { active: true, inicio: null, fin: null };
    }

    function renderCalendar() {
      const cal = $('#calendar').empty();
      const now = new Date();
      const view = selection.monthCursor || new Date();
      cal.append(`<div class="mb-3 flex items-center justify-between">
        <div class="inline-flex items-center gap-2">
          <button id="btnPrevMonth" type="button" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="chevron-left"></i></button>
          <button id="btnTodayMonth" type="button" class="px-3 h-9 rounded-lg border text-sm font-semibold hover:bg-gray-50">Hoy</button>
          <button id="btnNextMonth" type="button" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50"><i data-lucide="chevron-right"></i></button>
        </div>
        <div class="text-sm font-black text-gray-700">${new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(view)}</div>
      </div>`);
      cal.append('<div class="grid grid-cols-7 gap-1 text-center font-black text-[10px] text-gray-300 uppercase mb-2"><div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div></div>');
      const grid = $('<div class="grid grid-cols-7 gap-2"></div>');
      const firstDay = new Date(view.getFullYear(), view.getMonth(), 1).getDay();
      const days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
      for (let i = 0; i < firstDay; i++) grid.append('<div></div>');
      for (let d = 1; d <= days; d++) {
        const date = `${view.getFullYear()}-${String(view.getMonth()+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dateObj = new Date(`${date}T00:00:00`);
        const isPast = dateObj < new Date(now.toDateString());
        const isClosed = selection.sede ? !getSedeDayRange(dateObj).active : false;
        const disabled = isPast || isClosed;
        grid.append(`<button type="button" data-date="${date}" ${disabled?'disabled':''} class="w-10 h-10 flex items-center justify-center rounded-xl font-bold transition-all ${disabled?'text-gray-200 bg-gray-50 cursor-not-allowed':'hover:bg-teal-50'}">${d}</button>`);
      }
      cal.append(grid);
      if (window.lucide) lucide.createIcons();
    }

    function loadSlots(fecha) {
      $('#timesGrid').html('<div class="col-span-3 text-center py-8 text-gray-400">Cargando horarios...</div>');
      $.get(API, {
        action: 'get_horarios',
        sede_id: selection.sede.id,
        servicio_id: selection.servicio.id,
        empleado_id: selection.empleado.id,
        fecha,
        client_today: new Date().toLocaleDateString('en-CA'),
        client_time: new Date().toTimeString().slice(0, 5),
      }, function (res) {
        const box = $('#timesGrid').empty();
        if (res && res.meta && res.meta.horario && res.meta.horario.inicio) {
          $('#diaHorarioInfo').text(`Horario de atención: ${res.meta.horario.inicio} - ${res.meta.horario.fin}`);
        }
        if (!(res && res.success && (res.data || []).length)) {
          box.html('<div class="col-span-3 text-center py-8 text-gray-400">No hay horarios disponibles.</div>');
          selection.hora = null;
          validateStep();
          return;
        }
        (res.data || []).forEach(s => {
          box.append(`<button type="button" class="time-slot py-2 rounded-lg border text-sm font-bold ${s.disponible ? 'hover:border-teal-400' : 'bg-gray-50 text-gray-300 cursor-not-allowed'}" data-hora="${s.hora}" ${s.disponible ? '' : 'disabled'}>${s.hora}</button>`);
        });
      }, 'json');
    }

    function fillResume() {
      $('#rSede').text(selection.sede?.nombre || '-');
      $('#rServicio').text(selection.servicio?.nombre || '-');
      $('#rEmpleado').text(selection.empleado?.nombre || '-');
      $('#rFecha').text(selection.fecha || '-');
      $('#rHora').text(selection.hora || '-');
    }

    $('#nextBtn').on('click', function () {
      if (currentStep === 1) loadServicios();
      if (currentStep === 2) loadEmpleados();
      if (currentStep === 4) fillResume();
      if (currentStep === 5) {
        $(this).prop('disabled', true).text('Guardando...');
        $.post(API, {
          action: 'save',
          sede_id: selection.sede.id,
          servicio_id: selection.servicio.id,
          empleado_id: selection.empleado.id,
          fecha: selection.fecha,
          hora: selection.hora,
          notas: $('#notas').val(),
          client_today: new Date().toLocaleDateString('en-CA'),
          client_time: new Date().toTimeString().slice(0, 5),
        }, (res) => {
          if (res && res.success) {
            showCustomAlert('Cita agendada correctamente.', 2800, 'success');
            selection.sede = selection.servicio = selection.empleado = null;
            selection.fecha = selection.hora = null;
            currentStep = 1; maxReachedStep = 1;
            loadMyCitas(); loadSedes(); renderCalendar(); updateDots();
          } else {
            showCustomAlert((res && res.message) || 'No se pudo agendar.', 3500, 'error');
          }
          $('#nextBtn').prop('disabled', false).text('Confirmar cita');
        }, 'json').fail(() => {
          showCustomAlert('No se pudo agendar la cita.', 3500, 'error');
          $('#nextBtn').prop('disabled', false).text('Confirmar cita');
        });
        return;
      }
      currentStep++;
      maxReachedStep = Math.max(maxReachedStep, currentStep);
      updateDots();
    });
    $('#prevBtn').on('click', function () { if (currentStep > 1) { currentStep--; updateDots(); } });
    $('body').on('click', '.pgBtn', function () { page = parseInt($(this).data('page') || '1', 10); loadMyCitas(); });
    $('body').on('click', '.step-nav', function () {
      const t = parseInt($(this).data('step') || '1', 10);
      if (t <= maxReachedStep && t < currentStep) { currentStep = t; updateDots(); }
    });
    $('body').on('click', '.card-choice', function () {
      const type = $(this).data('type');
      $(this).siblings('.card-choice').removeClass('border-teal-500 bg-teal-50');
      $(this).addClass('border-teal-500 bg-teal-50');
      if (type === 'sede') {
        selection.sede = { id: parseInt($(this).data('id'), 10), nombre: $(this).data('nombre'), horarios_json: $(this).data('horarios') };
        selection.servicio = selection.empleado = selection.fecha = selection.hora = null;
      } else if (type === 'servicio') {
        selection.servicio = { id: parseInt($(this).data('id'), 10), nombre: $(this).data('nombre') };
        selection.empleado = selection.fecha = selection.hora = null;
      } else if (type === 'empleado') {
        selection.empleado = { id: parseInt($(this).data('id'), 10), nombre: $(this).data('nombre') };
        selection.fecha = selection.hora = null;
        renderCalendar();
      }
      validateStep();
    });
    $('body').on('click', '#btnPrevMonth', function () { const c = selection.monthCursor || new Date(); selection.monthCursor = new Date(c.getFullYear(), c.getMonth() - 1, 1); renderCalendar(); });
    $('body').on('click', '#btnNextMonth', function () { const c = selection.monthCursor || new Date(); selection.monthCursor = new Date(c.getFullYear(), c.getMonth() + 1, 1); renderCalendar(); });
    $('body').on('click', '#btnTodayMonth', function () { const n = new Date(); selection.monthCursor = new Date(n.getFullYear(), n.getMonth(), 1); renderCalendar(); });
    $('body').on('click', '[data-date]', function () {
      $('[data-date]').removeClass('bg-teal-600 text-white');
      $(this).addClass('bg-teal-600 text-white');
      selection.fecha = $(this).data('date');
      selection.hora = null;
      loadSlots(selection.fecha);
      validateStep();
    });
    $('body').on('click', '.time-slot', function () {
      $('.time-slot').removeClass('bg-teal-600 text-white border-teal-600');
      $(this).addClass('bg-teal-600 text-white border-teal-600');
      selection.hora = $(this).data('hora');
      validateStep();
    });

    loadMyCitas();
    loadSedes();
    renderCalendar();
    updateDots();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

