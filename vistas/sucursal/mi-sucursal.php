<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'mi sucursal';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white rounded-2xl shadow border">
    <div class="p-5 border-b">
      <div class="font-semibold text-gray-900">Mi sucursal</div>
      <div class="text-sm text-gray-500">Ficha digital y actualización de datos de la sucursal.</div>
    </div>

    <div class="p-5">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <div class="lg:col-span-5">
          <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">
            <div class="h-40 bg-gray-100 relative">
              <img id="cardFoto" src="" alt="sucursal" class="h-full w-full object-cover hidden">
              <div id="cardFotoEmpty" class="h-full w-full grid place-items-center text-gray-400 font-semibold">Sin foto</div>
            </div>
            <div class="p-4">
              <div id="cardNombre" class="text-xl font-extrabold text-teal-700 dark:text-teal-400">-</div>
              <div id="cardDireccion" class="mt-1 text-sm text-gray-600">-</div>

              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl border bg-gray-50 p-3">
                  <div class="text-xs font-semibold text-gray-500 uppercase">Teléfono</div>
                  <div id="cardTelefono" class="mt-1 font-semibold text-gray-900">-</div>
                </div>
                <div class="rounded-xl border bg-gray-50 p-3">
                  <div class="text-xs font-semibold text-gray-500 uppercase">Email</div>
                  <div id="cardEmail" class="mt-1 font-semibold text-gray-900 break-all">-</div>
                </div>
              </div>

              <div class="mt-4 rounded-xl border bg-gray-50 p-3">
                <div class="text-xs font-semibold text-gray-500 uppercase">Horario</div>
                <div id="cardHorario" class="mt-1 text-sm text-gray-800">Configura tus horarios en el formulario.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-7">
          <form id="formSucursal" class="space-y-4" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Nombre sucursal</label>
                <input id="s_nombre" name="nombre" class="border rounded-lg p-2 w-full" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Foto</label>
                <input type="hidden" id="s_foto_path" name="foto_path">
                <input type="file" id="s_foto_file" name="foto_file" accept="image/*" class="border rounded-lg p-2 w-full bg-white">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input id="s_telefono" name="telefono" class="border rounded-lg p-2 w-full">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input id="s_email" name="email" type="email" class="border rounded-lg p-2 w-full">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <input id="s_direccion" name="direccion" class="border rounded-lg p-2 w-full">
              </div>
            </div>

            <div class="pt-4 border-t">
              <div class="text-sm font-semibold text-gray-900 mb-3">Horarios</div>
              <div class="mb-3 flex items-center justify-between rounded-xl border bg-white px-3 py-2">
                <div>
                  <div class="text-sm font-semibold text-gray-900">Editar por día</div>
                  <div class="text-xs text-gray-500">Actívalo para configurar horarios individuales para cada día.</div>
                </div>
                <button type="button" id="toggleIndividual" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300">
                  <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform translate-x-0"></span>
                </button>
              </div>

              <div id="horariosWrap" class="space-y-3">
                <div id="horariosGrouped" class="space-y-3">
                <div class="rounded-xl border bg-gray-50 p-3">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <div class="font-semibold text-gray-900">Lunes a viernes</div>
                      <div class="text-xs text-gray-500">Aplica el mismo horario a Lun, Mar, Mié, Jue y Vie.</div>
                    </div>
                    <button type="button" id="grp_lv_switch" class="day-switch relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300">
                      <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform translate-x-0"></span>
                    </button>
                  </div>
                  <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Inicio</label>
                      <input id="grp_lv_inicio" type="time" value="09:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Fin</label>
                      <input id="grp_lv_fin" type="time" value="18:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                  </div>
                </div>

                <div class="rounded-xl border bg-gray-50 p-3">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <div class="font-semibold text-gray-900">Sábado</div>
                      <div class="text-xs text-gray-500">Horario del sábado.</div>
                    </div>
                    <button type="button" id="grp_sab_switch" class="day-switch relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300">
                      <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform translate-x-0"></span>
                    </button>
                  </div>
                  <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Inicio</label>
                      <input id="grp_sab_inicio" type="time" value="09:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Fin</label>
                      <input id="grp_sab_fin" type="time" value="18:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                  </div>
                </div>

                <div class="rounded-xl border bg-gray-50 p-3">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <div class="font-semibold text-gray-900">Domingo</div>
                      <div class="text-xs text-gray-500">Horario del domingo.</div>
                    </div>
                    <button type="button" id="grp_dom_switch" class="day-switch relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300">
                      <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform translate-x-0"></span>
                    </button>
                  </div>
                  <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Inicio</label>
                      <input id="grp_dom_inicio" type="time" value="09:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 uppercase">Fin</label>
                      <input id="grp_dom_fin" type="time" value="18:00" class="border rounded-lg p-2 w-full bg-white">
                    </div>
                  </div>
                </div>

                </div>

                <div id="horariosIndividual" class="space-y-2 hidden"></div>

                <div id="hiddenDaysInputs" class="hidden"></div>
              </div>
            </div>

            <div class="pt-4 border-t flex justify-end">
              <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 text-white font-semibold hover:bg-teal-700">Guardar cambios</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    const API_URL = <?= json_encode(app_url('api/sucursal/ajustes.php') . '?id_e=' . urlencode((string) request_id_e())) ?>;
    const DAYS = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    const DAY_SHORT = { lunes:'Lun', martes:'Mar', miercoles:'Mié', jueves:'Jue', viernes:'Vie', sabado:'Sáb', domingo:'Dom' };
    const DAY_LONG = { lunes:'Lunes', martes:'Martes', miercoles:'Miércoles', jueves:'Jueves', viernes:'Viernes', sabado:'Sábado', domingo:'Domingo' };

    function ensureHiddenInputs() {
      const box = $('#hiddenDaysInputs').empty();
      DAYS.forEach(d => {
        box.append(`
          <input type="hidden" name="${d}_activo" value="0">
          <input type="hidden" name="${d}_inicio" value="09:00">
          <input type="hidden" name="${d}_fin" value="18:00">
        `);
      });
    }

    function setSwitch($btn, active) {
      $btn.toggleClass('bg-teal-600', !!active).toggleClass('bg-gray-300', !active);
      $btn.find('span').toggleClass('translate-x-5', !!active).toggleClass('translate-x-0', !active);
    }

    function updateResumenFromHidden() {
      const ht = buildHorarioText(getHorariosFromHidden());
      $('#cardHorario').text(ht || 'Sin horarios configurados.');
    }

    function renderIndividualFromHidden() {
      const box = $('#horariosIndividual').empty();
      const h = getHorariosFromHidden();
      DAYS.forEach(day => {
        const x = h[day] || {};
        const activo = parseInt(x.activo || 0, 10) === 1;
        const ini = String(x.inicio || '09:00');
        const fin = String(x.fin || '18:00');
        box.append(`
          <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-center border rounded-lg p-3 bg-gray-50">
            <div class="font-medium text-gray-800">${DAY_LONG[day] || day}</div>
            <div>
              <button type="button" class="day-switch day-individual relative inline-flex h-6 w-11 items-center rounded-full ${activo ? 'bg-teal-600' : 'bg-gray-300'}" data-day="${day}">
                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform ${activo ? 'translate-x-5' : 'translate-x-0'}"></span>
              </button>
            </div>
            <div>
              <input type="time" class="day-individual-inicio border rounded-lg p-2 w-full bg-white" data-day="${day}" value="${ini}">
            </div>
            <div>
              <input type="time" class="day-individual-fin border rounded-lg p-2 w-full bg-white" data-day="${day}" value="${fin}">
            </div>
            <div class="text-xs text-gray-500">Activo / inicio / fin</div>
          </div>
        `);
      });
    }

    function setIndividualMode(enabled) {
      setSwitch($('#toggleIndividual'), enabled);
      $('#horariosGrouped').toggleClass('hidden', enabled);
      $('#horariosIndividual').toggleClass('hidden', !enabled);
      if (enabled) {
        renderIndividualFromHidden();
      }
    }

    function setDayHidden(day, active, inicio, fin) {
      $(`input[name="${day}_activo"]`).val(active ? '1' : '0');
      if (inicio) $(`input[name="${day}_inicio"]`).val(String(inicio));
      if (fin) $(`input[name="${day}_fin"]`).val(String(fin));
    }

    function getHorariosFromHidden() {
      const h = {};
      DAYS.forEach(d => {
        h[d] = {
          activo: $(`input[name="${d}_activo"]`).val(),
          inicio: $(`input[name="${d}_inicio"]`).val(),
          fin: $(`input[name="${d}_fin"]`).val()
        };
      });
      return h;
    }

    function buildHorarioText(horarios) {
      if (!horarios || typeof horarios !== 'object') return '';
      const items = DAYS.map(d => {
        const x = horarios[d] || {};
        const activo = parseInt(x.activo || 0, 10) === 1;
        const ini = String(x.inicio || '').trim();
        const fin = String(x.fin || '').trim();
        const key = activo && ini && fin ? `${ini}-${fin}` : '';
        return { day: d, activo, ini, fin, key };
      });

      const parts = [];
      let i = 0;
      while (i < items.length) {
        const it = items[i];
        if (!it.activo || !it.key) {
          i++;
          continue;
        }
        let j = i;
        while (j + 1 < items.length && items[j + 1].activo && items[j + 1].key === it.key) {
          j++;
        }
        const a = items[i].day;
        const b = items[j].day;
        const range = a === b ? `${DAY_SHORT[a]}` : `${DAY_SHORT[a]} - ${DAY_SHORT[b]}`;
        parts.push(`${range} ${it.ini}-${it.fin}`);
        i = j + 1;
      }
      return parts.join(' · ');
    }

    function syncGroupsFromHorarios(horarios) {
      const hv = (d) => horarios && horarios[d] ? horarios[d] : {};
      const lvDays = ['lunes','martes','miercoles','jueves','viernes'];
      const lvFirst = hv('lunes');
      const lvActive = lvDays.every(d => parseInt(hv(d).activo || 0, 10) === 1);
      const lvSame = lvDays.every(d => String(hv(d).inicio || '') === String(lvFirst.inicio || '') && String(hv(d).fin || '') === String(lvFirst.fin || ''));
      setSwitch($('#grp_lv_switch'), lvActive);
      $('#grp_lv_inicio').val(String((lvSame ? lvFirst.inicio : (lvFirst.inicio || '09:00')) || '09:00'));
      $('#grp_lv_fin').val(String((lvSame ? lvFirst.fin : (lvFirst.fin || '18:00')) || '18:00'));

      const sab = hv('sabado');
      setSwitch($('#grp_sab_switch'), parseInt(sab.activo || 0, 10) === 1);
      $('#grp_sab_inicio').val(String(sab.inicio || '09:00'));
      $('#grp_sab_fin').val(String(sab.fin || '18:00'));

      const dom = hv('domingo');
      setSwitch($('#grp_dom_switch'), parseInt(dom.activo || 0, 10) === 1);
      $('#grp_dom_inicio').val(String(dom.inicio || '09:00'));
      $('#grp_dom_fin').val(String(dom.fin || '18:00'));
    }

    function applyGroupToDays(groupKey, active, inicio, fin) {
      if (groupKey === 'lv') {
        ['lunes','martes','miercoles','jueves','viernes'].forEach(d => setDayHidden(d, active, inicio, fin));
      } else if (groupKey === 'sab') {
        setDayHidden('sabado', active, inicio, fin);
      } else if (groupKey === 'dom') {
        setDayHidden('domingo', active, inicio, fin);
      }
      updateResumenFromHidden();
      if ($('#toggleIndividual').hasClass('bg-teal-600')) {
        renderIndividualFromHidden();
      }
    }

    function syncCardFromForm() {
      $('#cardNombre').text($('#s_nombre').val() || '-');
      $('#cardDireccion').text($('#s_direccion').val() || '-');
      $('#cardTelefono').text($('#s_telefono').val() || '-');
      $('#cardEmail').text($('#s_email').val() || '-');
    }

    function setCardFoto(path) {
      const img = $('#cardFoto');
      const empty = $('#cardFotoEmpty');
      if (path) {
        img.attr('src', path).removeClass('hidden');
        empty.addClass('hidden');
      } else {
        img.attr('src', '').addClass('hidden');
        empty.removeClass('hidden');
      }
    }

    function loadData() {
      $.get(API_URL, { action: 'get' }, function (res) {
        if (!res || !res.success) {
          showCustomAlert((res && res.message) || 'No se pudo cargar la información de la sucursal.', 5000, 'error');
          return;
        }
        const d = res.data || {};
        const s = d.sucursal || {};
        $('#s_nombre').val(s.nombre || '');
        $('#s_direccion').val(s.direccion || '');
        $('#s_telefono').val(s.telefono || '');
        $('#s_email').val(s.email || '');
        $('#s_foto_path').val(s.foto_path || '');
        ensureHiddenInputs();
        const h = d.horarios || {};
        DAYS.forEach(day => {
          const x = h[day] || {};
          setDayHidden(day, parseInt(x.activo || 0, 10) === 1, x.inicio || '09:00', x.fin || '18:00');
        });
        syncGroupsFromHorarios(h);

        const foto = s.foto_path ? <?= json_encode(rtrim(app_url(''), '/') . '/') ?> + String(s.foto_path).replace(/^\//, '') : '';
        setCardFoto(foto);
        syncCardFromForm();
        updateResumenFromHidden();
        setIndividualMode(false);
      }, 'json');
    }

    $('#toggleIndividual').on('click', function () {
      const enabled = !$(this).hasClass('bg-teal-600');
      setIndividualMode(enabled);
    });

    $('#horariosIndividual').on('click', '.day-individual', function () {
      const day = String($(this).data('day') || '').trim();
      if (!day) return;
      const active = !$(this).hasClass('bg-teal-600');
      setSwitch($(this), active);
      setDayHidden(day, active, $(`.day-individual-inicio[data-day="${day}"]`).val(), $(`.day-individual-fin[data-day="${day}"]`).val());
      updateResumenFromHidden();
    });

    $('#horariosIndividual').on('change', '.day-individual-inicio,.day-individual-fin', function () {
      const day = String($(this).data('day') || '').trim();
      if (!day) return;
      const btn = $(`.day-individual[data-day="${day}"]`);
      const active = btn.hasClass('bg-teal-600');
      setDayHidden(day, active, $(`.day-individual-inicio[data-day="${day}"]`).val(), $(`.day-individual-fin[data-day="${day}"]`).val());
      updateResumenFromHidden();
    });

    $('#grp_lv_switch').on('click', function () {
      const active = !$(this).hasClass('bg-teal-600');
      setSwitch($(this), active);
      applyGroupToDays('lv', active, $('#grp_lv_inicio').val(), $('#grp_lv_fin').val());
    });
    $('#grp_sab_switch').on('click', function () {
      const active = !$(this).hasClass('bg-teal-600');
      setSwitch($(this), active);
      applyGroupToDays('sab', active, $('#grp_sab_inicio').val(), $('#grp_sab_fin').val());
    });
    $('#grp_dom_switch').on('click', function () {
      const active = !$(this).hasClass('bg-teal-600');
      setSwitch($(this), active);
      applyGroupToDays('dom', active, $('#grp_dom_inicio').val(), $('#grp_dom_fin').val());
    });

    $('#grp_lv_inicio,#grp_lv_fin').on('change', function () {
      const active = $('#grp_lv_switch').hasClass('bg-teal-600');
      applyGroupToDays('lv', active, $('#grp_lv_inicio').val(), $('#grp_lv_fin').val());
    });
    $('#grp_sab_inicio,#grp_sab_fin').on('change', function () {
      const active = $('#grp_sab_switch').hasClass('bg-teal-600');
      applyGroupToDays('sab', active, $('#grp_sab_inicio').val(), $('#grp_sab_fin').val());
    });
    $('#grp_dom_inicio,#grp_dom_fin').on('change', function () {
      const active = $('#grp_dom_switch').hasClass('bg-teal-600');
      applyGroupToDays('dom', active, $('#grp_dom_inicio').val(), $('#grp_dom_fin').val());
    });

    $('#s_nombre,#s_direccion,#s_telefono,#s_email').on('input', syncCardFromForm);

    $('#s_foto_file').on('change', function () {
      const f = this.files && this.files[0] ? this.files[0] : null;
      if (!f) {
        const persisted = $('#s_foto_path').val() || '';
        const foto = persisted ? <?= json_encode(rtrim(app_url(''), '/') . '/') ?> + String(persisted).replace(/^\//, '') : '';
        setCardFoto(foto);
        return;
      }
      const url = URL.createObjectURL(f);
      setCardFoto(url);
    });

    $('#formSucursal').on('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', 'save_branch');
      $.ajax({
        url: API_URL,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res && res.success) {
            showCustomAlert('Sucursal actualizada correctamente.', 3000, 'success');
            loadData();
            return;
          }
          showCustomAlert((res && res.message) || 'No se pudo actualizar la sucursal.', 5000, 'error');
        }
      });
    });

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
