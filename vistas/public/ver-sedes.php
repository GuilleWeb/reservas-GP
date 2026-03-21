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

  <div id="sedesGrid" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4"></div>
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
  const csrf = $('meta[name="csrf-token"]').attr('content');
  const apiPublic = <?= json_encode(app_url('api/public/sucursales/agregar_cita.php')) ?>;
  const empresaRef = <?= json_encode($empresa_ref) ?>;

  function cargarSedes(){
    $.get(apiPublic, {action:'get_sucursales', empresa: empresaRef, csrf_token:csrf}, function(res){
      if(res.success){
        const c = $('#sedesGrid').empty();
        res.data.forEach(s=>{
          c.append(`<div class="bg-white p-4 rounded shadow">
            <h3 class="font-semibold text-teal-700">${s.nombre}</h3>
            <div class="text-sm text-gray-600">${s.direccion||''}</div>
            <div class="mt-3 flex justify-between items-center">
              <div class="text-xs text-gray-500">${s.horario||''}</div>
              <button class="reservarBtn px-3 py-1 bg-teal-600 text-white rounded" data-id="${s.id}" data-nombre="${s.nombre}">Reservar cita</button>
            </div>
          </div>`);
        });
      } else $('#sedesGrid').html('<div>No hay sedes</div>');
    });
  }
  cargarSedes();

  $(document).on('click', '.reservarBtn', function(){
    const id = $(this).data('id');
    $('#sede_id').val(id);
    $('#modalReservar').removeClass('hidden').addClass('flex');
  });
  $('#cancelReserva').on('click', function(){ $('#modalReservar').addClass('hidden').removeClass('flex'); });

  $('#formReservar').on('submit', function(e){
    e.preventDefault();
    const data = $(this).serializeArray();

    // Create patient first (public) -> then create appointment
    const paciente = {
      nombre: $('#pac_nombre').val(),
      telefono: $('#pac_tel').val(),
      email: $('#pac_email').val()
    };
    const payload = {
        action: 'save_cita',
        empresa: empresaRef,
        nombre: paciente.nombre,
        telefono: paciente.telefono,
        email: paciente.email,
        sede_id: $('#sede_id').val(),
        fecha: ($('#fecha_hora').val() || '').split('T')[0],
        hora: ($('#fecha_hora').val() || '').split('T')[1],
        notas: 'Reservada vía web pública',
        csrf_token: csrf
      };
      $.post(apiPublic, payload, function(r2){
        if(r2.success){
          alert('Cita reservada correctamente');
          $('#modalReservar').addClass('hidden').removeClass('flex');
        } else {
          alert('Error: ' + (r2.error || ''));
        }
      }, 'json');
    });
  });
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
