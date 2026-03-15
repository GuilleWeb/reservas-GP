<?php
require_once __DIR__ . '/../app/layout/topbar.php';
$id_e = request_id_e();
?>
<?php if (!$id_e): ?>
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow border">Empresa no definida. Usa <span
        class="font-mono">?id_e=...</span></div>
<?php else: ?>
    <div class="max-w-7xl mx-auto py-6 px-1 sm:px-6 lg:px-8">

      <div class="bg-white shadow-2xl rounded-xl overflow-hidden flex flex-col md:flex-row border border-gray-200">

        <div class="md:w-1/3 bg-teal-600 text-white p-6 sm:p-8 space-y-6">
          <h2 class="text-xl font-bold border-b border-teal-500 pb-2">Progreso de Cita</h2>
          <div id="wizardSteps" class="space-y-3">
            <div class="stepItem" data-step="1"></div>
            <div class="stepItem" data-step="2"></div>
            <div class="stepItem" data-step="3"></div>
            <div class="stepItem" data-step="4"></div>
            <div class="stepItem" data-step="5"></div>
          </div>
          <div class="text-xs text-teal-50/90">
            Servicio, sede, empleado y horarios dependen de la empresa y sus configuraciones.
          </div>
        </div>

        <div class="md:w-2/3 p-5 sm:p-8">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h1 class="text-3xl font-extrabold text-gray-800" id="stepTitle">Agendar Cita</h1>
              <p class="text-gray-600 mt-1" id="stepSubtitle">Selecciona el servicio que deseas contratar.</p>
            </div>
            <a class="hidden sm:inline-flex items-center text-teal-700 font-semibold"
              href="<?= htmlspecialchars('inicio.php?id_e=' . rawurlencode($id_e)) ?>">
              <i data-lucide="arrow-left" class="mr-2"></i> Volver
            </a>
          </div>

          <div id="alertBox" class="mt-4 hidden rounded-lg border p-3 text-sm"></div>

          <!-- Paso 1: Servicio -->
          <section id="step1" class="mt-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="servicesGrid">
              <div class="col-span-full text-center text-gray-500 py-10">
                <i data-lucide="loader-2" class="mr-2 animate-spin"></i> Cargando servicios...
              </div>
            </div>
            <div class="mt-8 flex justify-end">
              <button id="btnTo2"
                class="px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition disabled:opacity-50"
                disabled>
                Siguiente <i data-lucide="arrow-right" class="ml-2"></i>
              </button>
            </div>
          </section>

          <!-- Paso 2: Sucursal -->
          <section id="step2" class="mt-6 hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="branchesGrid"></div>
            <div class="mt-8 flex justify-between">
              <button
                class="px-4 py-2 text-teal-600 font-semibold border border-teal-600 rounded-lg hover:bg-teal-50 transition"
                id="backTo1">
                <i data-lucide="arrow-left" class="mr-2"></i> Anterior
              </button>
              <button id="btnTo3"
                class="px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition disabled:opacity-50"
                disabled>
                Siguiente <i data-lucide="arrow-right" class="ml-2"></i>
              </button>
            </div>
          </section>

          <!-- Paso 3: Empleado -->
          <section id="step3" class="mt-6 hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="employeesGrid"></div>
            <div class="mt-8 flex justify-between">
              <button
                class="px-4 py-2 text-teal-600 font-semibold border border-teal-600 rounded-lg hover:bg-teal-50 transition"
                id="backTo2">
                <i data-lucide="arrow-left" class="mr-2"></i> Anterior
              </button>
              <button id="btnTo4"
                class="px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition disabled:opacity-50"
                disabled>
                Seleccionar fecha <i data-lucide="arrow-right" class="ml-2"></i>
              </button>
            </div>
          </section>

          <!-- Paso 4: Calendario y horas -->
          <section id="step4" class="mt-6 hidden">
            <div class="rounded-xl border bg-gray-50 p-4" id="calendarBox">
              <div class="flex items-center justify-between">
                <button type="button" id="prevMonth" class="p-2 rounded-full hover:bg-teal-100 text-teal-700">
                  <i data-lucide="chevron-left"></i>
                </button>
                <div class="text-lg font-bold text-gray-800" id="monthLabel">Mes</div>
                <button type="button" id="nextMonth" class="p-2 rounded-full hover:bg-teal-100 text-teal-700">
                  <i data-lucide="chevron-right"></i>
                </button>
              </div>

              <div class="mt-4 grid grid-cols-7 gap-2 text-center text-xs font-bold text-gray-500">
                <span>Dom</span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
              </div>
              <div class="mt-2 grid grid-cols-7 gap-2" id="calendarDays"></div>
              <div class="mt-3 text-xs text-gray-500">Selecciona un día habilitado para ver horarios disponibles.</div>
            </div>

            <div class="mt-6">
              <h3 class="text-xl font-semibold text-gray-800">Horas disponibles <span class="text-teal-700"
                  id="selectedDateLabel"></span></h3>
              <div class="mt-3 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3" id="slotsGrid">
                <div class="col-span-full text-gray-500">Selecciona una fecha.</div>
              </div>
            </div>

            <div class="mt-8 flex justify-between">
              <button
                class="px-4 py-2 text-teal-600 font-semibold border border-teal-600 rounded-lg hover:bg-teal-50 transition"
                id="backTo3">
                <i data-lucide="arrow-left" class="mr-2"></i> Anterior
              </button>
              <button id="btnTo5"
                class="px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition disabled:opacity-50"
                disabled>
                Continuar <i data-lucide="arrow-right" class="ml-2"></i>
              </button>
            </div>
          </section>

          <!-- Paso 5: Datos y confirmar -->
          <section id="step5" class="mt-6 hidden">
            <div class="bg-teal-50 p-5 rounded-xl border border-teal-200 shadow-inner">
              <div class="text-lg font-bold text-teal-800">Resumen</div>
              <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><span class="font-semibold">Servicio:</span> <span id="sumServicio"></span></div>
                <div><span class="font-semibold">Sucursal:</span> <span id="sumSucursal"></span></div>
                <div><span class="font-semibold">Empleado:</span> <span id="sumEmpleado"></span></div>
                <div><span class="font-semibold">Fecha:</span> <span id="sumFecha"></span></div>
                <div><span class="font-semibold">Hora:</span> <span id="sumHora"></span></div>
                <div><span class="font-semibold">Duración:</span> <span id="sumDur"></span></div>
                <div class="sm:col-span-2"><span class="font-semibold">Precio:</span> <span id="sumPrecio"></span></div>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nombre completo</label>
                <input id="cliente_nombre" class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"
                  required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono (opcional)</label>
                <input id="cliente_telefono"
                  class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Email (opcional)</label>
                <input id="cliente_email" type="email"
                  class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
              </div>
            </div>

            <div class="mt-8 flex justify-between">
              <button
                class="px-4 py-2 text-teal-600 font-semibold border border-teal-600 rounded-lg hover:bg-teal-50 transition"
                id="backTo4">
                <i data-lucide="arrow-left" class="mr-2"></i> Anterior
              </button>
              <button id="btnBook"
                class="px-8 py-3 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 transition">
                Confirmar Cita
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const SLUG = <?= json_encode($id_e) ?>;

        // Armar base real /v2 sin depender del router
        function v2Base() {
          const sn = String(<?= json_encode($_SERVER['SCRIPT_NAME'] ?? '') ?>).replaceAll('\\', '/');
          const pos = sn.indexOf('/v2/');
          if (pos === -1) return '';
          return sn.substring(0, pos) + '/v2';
        }
        const API_URL = v2Base() + '/api/public-citas.php';

        const elAlert = document.getElementById('alertBox');
        function alertMsg(type, text) {
          elAlert.classList.remove('hidden');
          elAlert.className = 'mt-4 rounded-lg border p-3 text-sm ' + (type === 'err' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-teal-50 border-teal-200 text-teal-800');
          elAlert.textContent = text;
        }
        function clearAlert() { elAlert.classList.add('hidden'); elAlert.textContent = ''; }

        let step = 1;
        const state = {
          servicio: null,
          sucursal: null,
          empleado: null,
          date: null,
          time: null,
          precio_total: null,
          precio_base: null,
          duracion: null,
        };

        const stepsMeta = {
          1: { title: 'Agendar Cita', subtitle: 'Selecciona el servicio que deseas contratar.' },
          2: { title: 'Selecciona Sucursal', subtitle: 'Mostramos solo sedes que ofrecen el servicio.' },
          3: { title: 'Selecciona Empleado', subtitle: 'Empleados disponibles en la sede para este servicio.' },
          4: { title: 'Fecha y Hora', subtitle: 'Elige un día hábil y un horario disponible.' },
          5: { title: 'Confirmación', subtitle: 'Ingresa tus datos y confirma tu cita.' },
        };

        function setStep(n) {
          step = n;
          clearAlert();
          document.getElementById('stepTitle').textContent = stepsMeta[n].title;
          document.getElementById('stepSubtitle').textContent = stepsMeta[n].subtitle;

          ['step1', 'step2', 'step3', 'step4', 'step5'].forEach((id, idx) => {
            document.getElementById(id).classList.toggle('hidden', (idx + 1) !== n);
          });

          document.querySelectorAll('#wizardSteps .stepItem').forEach((el) => {
            const s = parseInt(el.getAttribute('data-step'));
            const done = s < n;
            const active = s === n;
            el.innerHTML = `
          <div class="flex items-center gap-3 p-3 rounded-lg ${active ? 'bg-white/15' : 'bg-white/5'}">
            <div class="h-8 w-8 rounded-full grid place-items-center ${done ? 'bg-white text-teal-700' : 'bg-white/20 text-white'} font-extrabold">${s}</div>
            <div class="flex-1">
              <div class="font-semibold">${stepsMeta[s].title}</div>
              <div class="text-xs text-teal-50/90">${done ? 'Completado' : (active ? 'En curso' : 'Pendiente')}</div>
            </div>
          </div>`;
          });
        }

        async function apiGet(action, params) {
          const url = new URL(API_URL, window.location.origin);
          url.searchParams.set('action', action);
          url.searchParams.set('id_e', SLUG);
          Object.entries(params || {}).forEach(([k, v]) => url.searchParams.set(k, String(v)));
          const r = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
          const j = await r.json();
          if (!r.ok || !j.success) throw j;
          return j.data;
        }

        async function apiPost(action, payload) {
          const fd = new FormData();
          fd.append('action', action);
          fd.append('id_e', SLUG);
          Object.entries(payload || {}).forEach(([k, v]) => fd.append(k, String(v)));
          const r = await fetch(API_URL, { method: 'POST', body: fd });
          const j = await r.json();
          if (!r.ok || !j.success) throw j;
          return j;
        }

        function money(n) {
          const x = Number(n || 0);
          return '$' + x.toFixed(2);
        }

        function cardSelectable({ title, subtitle, metaHtml, selected }) {
          return `
        <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 overflow-hidden border ${selected ? 'border-teal-600 ring-2 ring-teal-200' : 'border-gray-100'}">
          <div class="p-5">
            <div class="text-lg font-bold text-teal-700">${title}</div>
            ${subtitle ? `<div class="mt-1 text-sm text-gray-600">${subtitle}</div>` : ''}
            ${metaHtml ? `<div class="mt-3 text-sm text-gray-600">${metaHtml}</div>` : ''}
          </div>
        </div>`;
        }

        // --- Paso 1: servicios ---
        const servicesGrid = document.getElementById('servicesGrid');
        async function loadServices() {
          try {
            const data = await apiGet('services');
            servicesGrid.innerHTML = '';
            if (!data.length) {
              servicesGrid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">No hay servicios disponibles.</div>';
              return;
            }
            data.forEach(s => {
              const selected = state.servicio && state.servicio.id === s.id;
              const meta = `<div class="flex items-center justify-between"><span>Duración: <b>${s.duracion_minutos} min</b></span><span>Precio: <b>${money(s.precio_base)}</b></span></div>`;
              const wrap = document.createElement('button');
              wrap.type = 'button';
              wrap.className = 'text-left';
              wrap.innerHTML = cardSelectable({ title: escapeHtml(s.nombre), subtitle: escapeHtml(s.descripcion || ''), metaHtml: meta, selected });
              wrap.addEventListener('click', () => {
                state.servicio = s;
                state.precio_base = Number(s.precio_base || 0);
                state.duracion = Number(s.duracion_minutos || 0);
                state.sucursal = null;
                state.empleado = null;
                state.date = null;
                state.time = null;
                document.getElementById('btnTo2').disabled = false;
                loadServices();
              });
              servicesGrid.appendChild(wrap);
            });
          } catch (e) {
            servicesGrid.innerHTML = '<div class="col-span-full text-center text-red-600 py-10">No se pudieron cargar los servicios.</div>';
          }
        }

        // --- Paso 2: sucursales ---
        const branchesGrid = document.getElementById('branchesGrid');
        async function loadBranches() {
          branchesGrid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10"><i data-lucide="loader-2" class="mr-2 animate-spin"></i> Cargando sedes...</div>';
          try {
            const data = await apiGet('branches', { servicio_id: state.servicio.id });
            branchesGrid.innerHTML = '';
            if (!data.length) {
              branchesGrid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">No hay sedes que ofrezcan este servicio.</div>';
              return;
            }
            data.forEach(b => {
              const selected = state.sucursal && state.sucursal.id === b.id;
              const meta = `${b.direccion ? `<div class="flex items-start"><i data-lucide="map-pin" class="w-5 text-teal-500 mr-2 mt-0.5"></i><span>${escapeHtml(b.direccion)}</span></div>` : ''}`
                + `${b.telefono ? `<div class="mt-2 flex items-center"><i data-lucide="phone" class="w-5 text-teal-500 mr-2"></i><span>${escapeHtml(b.telefono)}</span></div>` : ''}`;
              const wrap = document.createElement('button');
              wrap.type = 'button';
              wrap.className = 'text-left';
              wrap.innerHTML = cardSelectable({ title: escapeHtml(b.nombre), subtitle: null, metaHtml: meta, selected });
              wrap.addEventListener('click', () => {
                state.sucursal = b;
                state.empleado = null;
                state.date = null;
                state.time = null;
                document.getElementById('btnTo3').disabled = false;
                loadBranches();
              });
              branchesGrid.appendChild(wrap);
            });
          } catch (e) {
            branchesGrid.innerHTML = '<div class="col-span-full text-center text-red-600 py-10">No se pudieron cargar las sedes.</div>';
          }
        }

        // --- Paso 3: empleados ---
        const employeesGrid = document.getElementById('employeesGrid');
        async function loadEmployees() {
          employeesGrid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10"><i data-lucide="loader-2" class="mr-2 animate-spin"></i> Cargando empleados...</div>';
          try {
            const data = await apiGet('employees', { servicio_id: state.servicio.id, sucursal_id: state.sucursal.id });
            employeesGrid.innerHTML = '';
            if (!data.length) {
              employeesGrid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">No hay empleados disponibles para este servicio.</div>';
              return;
            }
            data.forEach(u => {
              const selected = state.empleado && state.empleado.id === u.id;
              const override = (u.precio_override !== null && u.precio_override !== undefined && u.precio_override !== '') ? Number(u.precio_override) : null;
              const base = Number(state.precio_base || 0);
              let extra = null;
              let total = base;
              if (override !== null && !Number.isNaN(override)) {
                total = override;
                extra = override - base;
              }
              const badge = (extra !== null && extra > 0.0001)
                ? `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-50 text-yellow-800 border border-yellow-100">+${money(extra)}</span>`
                : '';
              const meta = `<div class="flex items-center justify-between"><span>Precio: <b>${money(total)}</b></span>${badge}</div>`;

              const wrap = document.createElement('button');
              wrap.type = 'button';
              wrap.className = 'text-left';
              wrap.innerHTML = cardSelectable({ title: escapeHtml(u.nombre), subtitle: null, metaHtml: meta, selected });
              wrap.addEventListener('click', () => {
                state.empleado = u;
                state.precio_total = total;
                state.date = null;
                state.time = null;
                document.getElementById('btnTo4').disabled = false;
                loadEmployees();
              });
              employeesGrid.appendChild(wrap);
            });
          } catch (e) {
            employeesGrid.innerHTML = '<div class="col-span-full text-center text-red-600 py-10">No se pudieron cargar los empleados.</div>';
          }
        }

        // --- Paso 4: calendario / slots ---
        const monthLabel = document.getElementById('monthLabel');
        const calDays = document.getElementById('calendarDays');
        const slotsGrid = document.getElementById('slotsGrid');
        const selectedDateLabel = document.getElementById('selectedDateLabel');
        let currentMonth = new Date();
        currentMonth.setDate(1);
        currentMonth.setHours(0, 0, 0, 0);

        function monthKey(d) {
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          return `${y}-${m}`;
        }
        function formatMonthLabel(d) {
          return d.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
        }
        function daysInMonth(d) {
          return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        }

        async function loadCalendar() {
          calDays.innerHTML = '<div class="col-span-7 text-center text-gray-500 py-6"><i data-lucide="loader-2" class="mr-2 animate-spin"></i> Cargando calendario...</div>';
          slotsGrid.innerHTML = '<div class="col-span-full text-gray-500">Selecciona una fecha.</div>';
          selectedDateLabel.textContent = '';
          document.getElementById('btnTo5').disabled = true;
          state.date = null;
          state.time = null;

          const mk = monthKey(currentMonth);
          monthLabel.textContent = formatMonthLabel(currentMonth);

          try {
            const days = await apiGet('calendar', { servicio_id: state.servicio.id, empleado_id: state.empleado.id, month: mk });
            renderCalendar(days);
          } catch (e) {
            calDays.innerHTML = '<div class="col-span-7 text-center text-red-600 py-6">No se pudo cargar el calendario.</div>';
          }
        }

        function renderCalendar(apiDays) {
          const map = {};
          apiDays.forEach(d => map[d.date] = !!d.available);

          const firstWeekday = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1).getDay();
          const total = daysInMonth(currentMonth);

          calDays.innerHTML = '';
          for (let i = 0; i < firstWeekday; i++) {
            const ph = document.createElement('div');
            ph.className = 'h-10';
            calDays.appendChild(ph);
          }

          for (let day = 1; day <= total; day++) {
            const d = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
            const ymd = d.toISOString().slice(0, 10);
            const available = !!map[ymd];
            const selected = state.date === ymd;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'h-10 rounded-lg text-sm font-semibold border transition ' +
              (available ? (selected ? 'bg-teal-600 text-white border-teal-600' : 'bg-white hover:bg-teal-50 border-gray-200 text-gray-800') : 'bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed');
            btn.textContent = String(day);
            btn.disabled = !available;
            if (available) {
              btn.addEventListener('click', async () => {
                state.date = ymd;
                state.time = null;
                document.getElementById('btnTo5').disabled = true;
                renderCalendar(apiDays);
                await loadSlots(ymd);
              });
            }
            calDays.appendChild(btn);
          }
        }

        async function loadSlots(date) {
          selectedDateLabel.textContent = '· ' + new Date(date + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
          slotsGrid.innerHTML = '<div class="col-span-full text-gray-500"><i data-lucide="loader-2" class="mr-2 animate-spin"></i> Cargando horarios...</div>';
          try {
            const slots = await apiGet('slots', { servicio_id: state.servicio.id, empleado_id: state.empleado.id, date });
            slotsGrid.innerHTML = '';
            if (!slots.length) {
              slotsGrid.innerHTML = '<div class="col-span-full text-gray-500">No hay horarios disponibles este día.</div>';
              return;
            }
            slots.forEach(t => {
              const selected = state.time === t;
              const b = document.createElement('button');
              b.type = 'button';
              b.className = 'time-slot px-3 py-2 rounded-lg font-semibold text-center border transition ' +
                (selected ? 'bg-teal-600 text-white border-teal-600' : 'bg-white hover:bg-teal-50 text-teal-700 border-teal-200');
              b.textContent = t;
              b.addEventListener('click', () => {
                state.time = t;
                document.getElementById('btnTo5').disabled = false;
                [...slotsGrid.querySelectorAll('button')].forEach(x => x.classList.remove('bg-teal-600', 'text-white', 'border-teal-600'));
                b.classList.add('bg-teal-600', 'text-white', 'border-teal-600');
              });
              slotsGrid.appendChild(b);
            });
          } catch (e) {
            slotsGrid.innerHTML = '<div class="col-span-full text-red-600">No se pudieron cargar horarios.</div>';
          }
        }

        // Paso 5 resumen
        function fillSummary() {
          document.getElementById('sumServicio').textContent = state.servicio.nombre;
          document.getElementById('sumSucursal').textContent = state.sucursal.nombre;
          document.getElementById('sumEmpleado').textContent = state.empleado.nombre;
          document.getElementById('sumFecha').textContent = new Date(state.date + 'T00:00:00').toLocaleDateString('es-ES');
          document.getElementById('sumHora').textContent = state.time;
          document.getElementById('sumDur').textContent = String(state.duracion || 0) + ' min';
          document.getElementById('sumPrecio').textContent = money(state.precio_total ?? state.precio_base ?? 0);
        }

        function escapeHtml(str) {
          return String(str ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c] || c));
        }

        // Navegación
        document.getElementById('btnTo2').addEventListener('click', async () => { setStep(2); await loadBranches(); });
        document.getElementById('backTo1').addEventListener('click', async () => { setStep(1); await loadServices(); });
        document.getElementById('btnTo3').addEventListener('click', async () => { setStep(3); await loadEmployees(); });
        document.getElementById('backTo2').addEventListener('click', async () => { setStep(2); await loadBranches(); });
        document.getElementById('btnTo4').addEventListener('click', async () => { setStep(4); await loadCalendar(); });
        document.getElementById('backTo3').addEventListener('click', async () => { setStep(3); await loadEmployees(); });
        document.getElementById('btnTo5').addEventListener('click', () => { setStep(5); fillSummary(); });
        document.getElementById('backTo4').addEventListener('click', async () => { setStep(4); await loadCalendar(); });

        document.getElementById('prevMonth').addEventListener('click', async () => {
          currentMonth.setMonth(currentMonth.getMonth() - 1);
          await loadCalendar();
        });
        document.getElementById('nextMonth').addEventListener('click', async () => {
          currentMonth.setMonth(currentMonth.getMonth() + 1);
          await loadCalendar();
        });

        document.getElementById('btnBook').addEventListener('click', async () => {
          clearAlert();
          const nombre = String(document.getElementById('cliente_nombre').value || '').trim();
          const tel = String(document.getElementById('cliente_telefono').value || '').trim();
          const email = String(document.getElementById('cliente_email').value || '').trim();
          if (!nombre) {
            alertMsg('err', 'El nombre es obligatorio.');
            return;
          }
          const btn = document.getElementById('btnBook');
          btn.disabled = true;
          btn.textContent = 'Procesando...';
          try {
            const res = await apiPost('book', {
              servicio_id: state.servicio.id,
              sucursal_id: state.sucursal.id,
              empleado_id: state.empleado.id,
              date: state.date,
              time: state.time,
              cliente_nombre: nombre,
              cliente_telefono: tel,
              cliente_email: email,
            });
            alertMsg('ok', 'Cita creada correctamente. Código: #' + (res.cita_id || ''));
          } catch (e) {
            const err = (e && (e.message || e.error)) ? (e.message || e.error) : 'No se pudo crear la cita.';
            alertMsg('err', err);
          } finally {
            btn.disabled = false;
            btn.textContent = 'Confirmar Cita';
          }
        });

        // Init
        setStep(1);
        loadServices();
      })();
    </script>
<?php endif; ?>
<?php
require_once __DIR__ . '/../app/layout/footer.php';
