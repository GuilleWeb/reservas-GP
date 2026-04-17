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
$module = 'ver-sedes';
include __DIR__ . '/../../includes/topbar.php';
$empresa_ref = $empresa['slug'] ?: (string) ((int) ($empresa['id'] ?? 0));
?>
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-teal-700">Nuestras sedes</h1>
  </div>

  <div id="sedesGrid" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 justify-items-center"></div>
</div>

<!-- modal reservar -->
<div id="modalReservar" class="fixed inset-0 hidden items-center justify-center z-50">
  <div class="absolute inset-0 bg-black bg-opacity-40"></div>
  <div class="bg-white rounded-lg p-6 z-10 w-full max-w-md">
    <h3 id="modalTitle" class="text-lg font-semibold">Reservar cita</h3>
    <form id="formReservar">
      <input type="hidden" id="sede_id" name="sede_id">
      <div class="mt-3">
        <label class="text-sm">Nombre del paciente</label>
        <input name="nombre" id="pac_nombre" class="w-full border rounded px-3 py-2" required>
      </div>
      <div class="grid grid-cols-2 gap-3 mt-3">
        <div>
          <label class="text-sm">Teléfono</label>
          <input name="telefono" id="pac_tel" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="text-sm">Email</label>
          <input name="email" id="pac_email" class="w-full border rounded px-3 py-2">
        </div>
      </div>
      <div class="mt-3">
        <label class="text-sm">Fecha y hora</label>
        <input name="fecha_hora_inicio" id="fecha_hora" type="datetime-local" class="w-full border rounded px-3 py-2" required>
      </div>
      <div class="mt-4 flex justify-end space-x-2">
        <button type="button" id="cancelReserva" class="px-4 py-2 rounded border">Cancelar</button>
        <button type="submit" class="px-4 py-2 rounded bg-teal-600 text-white">Reservar</button>
      </div>
    </form>
  </div>
</div>

<script>
$(function(){
  const apiPublic = <?= json_encode(app_url('api/public/sucursales/agregar_cita.php')) ?>;
  const empresaRef = <?= json_encode($empresa_ref) ?>;
  const citaBase = <?= json_encode(view_url('vistas/public/citas.php', $empresa_ref)) ?>;
  const appendParam = (url, key, val) => `${url}${String(url).includes('?') ? '&' : '?'}${encodeURIComponent(key)}=${encodeURIComponent(val)}`;

  function toHorarioText(raw){
    try {
      const h = JSON.parse(raw || '{}');
      if (h.texto) return h.texto;
      const orderDays = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
      const dayLabel = { lunes:'Lun', martes:'Mar', miercoles:'Mié', jueves:'Jue', viernes:'Vie', sabado:'Sáb', domingo:'Dom' };
      const isOn = (v) => !(v === false || v === 0 || v === '0' || v === null || typeof v === 'undefined');
      const active = orderDays
        .filter(d => h[d] && isOn(h[d].activo) && h[d].inicio && h[d].fin)
        .map(d => ({day:d, inicio:h[d].inicio, fin:h[d].fin}));
      if (active.length) {
        const allSame = active.length === 7 && active.every(v => v.inicio === active[0].inicio && v.fin === active[0].fin);
        if (allSame) return `Todos los días: ${active[0].inicio}-${active[0].fin}`;
        const groups = [];
        let cur = null;
        for (const d of orderDays) {
          const row = h[d];
          const on = row && isOn(row.activo) && row.inicio && row.fin;
          if (!on) {
            if (cur) { groups.push(cur); cur = null; }
            continue;
          }
          const range = `${row.inicio}-${row.fin}`;
          if (!cur) cur = {start:d, end:d, range};
          else if (cur.range === range) cur.end = d;
          else { groups.push(cur); cur = {start:d, end:d, range}; }
        }
        if (cur) groups.push(cur);
        return groups.map(g => {
          const d1 = dayLabel[g.start] || g.start;
          const d2 = dayLabel[g.end] || g.end;
          const ds = g.start === g.end ? d1 : `${d1}-${d2}`;
          return `${ds}: ${g.range}`;
        }).join(' · ');
      }
      const p = [];
      if (h['lun-vie']?.inicio && h['lun-vie']?.fin) p.push(`Lun-Vie: ${h['lun-vie'].inicio}-${h['lun-vie'].fin}`);
      if (h['sab']?.inicio && h['sab']?.fin) p.push(`Sáb: ${h['sab'].inicio}-${h['sab'].fin}`);
      if (h['dom']?.inicio && h['dom']?.fin) p.push(`Dom: ${h['dom'].inicio}-${h['dom'].fin}`);
      return p.join(' · ');
    } catch(e){ return ''; }
  }

  function cargarSedes(){
    $.get(apiPublic, {action:'get_sucursales', empresa: empresaRef}, function(res){
      const c = $('#sedesGrid').empty();
      if(!res || !res.success || !Array.isArray(res.data) || !res.data.length){
        c.html('<div class="text-gray-500">No hay sedes disponibles.</div>');
        return;
      }
      res.data.forEach(s=>{
        const horario = toHorarioText(s.horarios_json || '');
        const img = s.foto_path ? `/${String(s.foto_path).replace(/^\/+/, '')}` : 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&q=80&w=900';
        c.append(`<div class="w-full max-w-md bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <div class="h-44 rounded-xl overflow-hidden bg-gray-100 mb-4">
            <img src="${img}" class="w-full h-full object-cover" alt="${s.nombre || 'sucursal'}">
          </div>
          <h3 class="font-semibold text-teal-700 text-lg">${s.nombre || ''}</h3>
          <div class="text-sm text-gray-600 mt-2">${s.direccion || 'Dirección no disponible'}</div>
          <div class="text-xs text-gray-500 mt-1">${s.telefono || ''}</div>
          <div class="text-xs text-gray-500 mt-1">${horario || ''}</div>
          <div class="mt-3">
            <a href="${appendParam(citaBase, 'sede_id', s.id)}" class="inline-flex px-3 py-1 bg-teal-600 text-white rounded-lg text-sm">Reservar cita</a>
          </div>
        </div>`);
      });
    }, 'json');
  }
  cargarSedes();
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
