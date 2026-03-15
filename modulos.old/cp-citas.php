<?php
// /modulos/citas.php
?>

    <div id="tablaCitasView">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Citas</h2>
        <button id="" class="btnNuevaCita px-4 py-2 bg-teal-600 text-white rounded">+ Nueva Cita</button>
        <button id="btnCalendario" class="px-4 py-2 bg-blue-600 text-white rounded">📅 Ver en Calendario</button>

      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchCita" type="text" placeholder="Buscar por paciente..." 
          class="border rounded p-2 flex-1 mr-3">
        <select id="perPage" class="border rounded p-2">
          <option value="5">5</option>
          <option value="10" selected>10</option>
          <option value="20">20</option>
        </select>
      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border">
          <thead>
            <tr class="bg-gray-100 text-gray-700">
              <th class="p-2 border">ID</th>
              <th class="p-2 border">Paciente</th>
              <th class="p-2 border">Fecha</th>
              <th class="p-2 border">Sede</th>
              <th class="p-2 border">Nota</th>
              <th class="p-2 border">Estado</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="citasTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>
    <!-- Vista calendario -->
    <div id="calendarView" style="height: 550px; min-height: 400px;" class="hidden z-2">
      <button id="btnTabla" class="px-4 py-2 bg-gray-600 text-white rounded hidden">📋 Ver en Tabla</button>
      <button id="" class="btnNuevaCita px-4 py-2 bg-teal-600 text-white rounded">+ Nueva Cita</button>
      <div id="calendario" style="width: 100%; height: 95%; font-size: 20px;"></div>
    </div>


<!-- Modal nueva cita-->
<div id="citaModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow p-6 w-full max-w-md bg-opacity-100 z-100">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nueva Cita</h3>
    <form id="citaForm" class="space-y-4">
      <input type="hidden" name="id" id="citaId">

      <div>
          <label class="block text-sm font-medium mb-1">Paciente</label>
          <div class="flex items-center space-x-2">
            <select name="paciente_id" id="paciente_id" class="border rounded p-2 flex-1" required>
              <option selected disabled>Seleccione un paciente</option>
            </select>
            <button type="button" id="btnNuevoPaciente" 
              class="px-3 py-2 bg-teal-600 text-white rounded text-sm flex items-center justify-center" title="agregar nuevo paciente">
              <i data-lucide="user-plus"></i>
            </button>
          </div>
        </div>

      <div>
        <label class="block text-sm font-medium">Sede</label>
        <select name="sede_id" id="sede_id" class="border rounded p-2 w-full" required>
          <option selected disabled>Seleccione una sede</option>
        </select>
      </div>
      <div>
          <label class="block text-sm font-medium">Fecha</label>
          <input type="datetime-local" name="fecha" id="fecha" class="border rounded p-2 w-full" disabled required>
          <p id="fechaError" class="text-red-500 text-xs mt-1 hidden"></p>
      </div>
      <div>
        <label class="block text-sm font-medium">Nota</label>
        <textarea name="nota" id="nota" class="border rounded p-2 w-full"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium">Estado</label>
        <select name="estado" id="estado" class="border rounded p-2 w-full" required>
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 mt-4">
        <button type="button" id="btnCancel" class="px-4 py-2 border rounded">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal paciente nuevo -->
<div id="pacienteModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto p-6 relative">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nuevo Paciente</h3>
    <form id="pacienteForm" class="space-y-4">
      <input type="hidden" name="id" id="pacienteId">

      <div>
        <label class="block text-sm font-medium">Nombre</label>
        <input type="text" name="nombre" id="nombre" class="border rounded p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" id="email" class="border rounded p-2 w-full">
      </div>
      <div>
        <label class="block text-sm font-medium">Teléfono</label>
        <input type="text" name="telefono" id="telefono" class="border rounded p-2 w-full" required> 
      </div>
      <div>
        <label class="block text-sm font-medium">Fecha de Nacimiento</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="border rounded p-2 w-full">
      </div>
      <div>
        <label class="block text-sm font-medium">Dirección</label>
        <textarea name="direccion" id="direccion" class="border rounded p-2 w-full"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium">Activo</label>
        <select name="activo" id="activo" class="border rounded p-2 w-full">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 mt-4 sticky bottom-0 bg-white pt-4">
        <button type="button" id="btnCancelP" class="px-4 py-2 border rounded">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="calendar.js"></script>
<script>
$(function(){
    // Variables de estado y configuración
    let pacientes = [];
    let sedes = [];
    let currentHorario = null; // Almacena el horario JSON de la sede seleccionada
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let page=1, per=10, search='';
    let calendar = null;

    // Elementos del DOM
    const $fecha = $("#fecha"); // Input datetime-local
    const $sedeId = $("#sede_id");
    const $fechaError = $("#fechaError"); // Mensaje de error
    const $citaModal = $("#citaModal");
    const $citaForm = $("#citaForm");

    // Obtener la fecha y hora mínima de hoy en formato ISO (YYYY-MM-DDTHH:MM)
    const now = new Date();
    // Ajuste para la zona horaria: Asegura que 'now' refleje el momento actual local sin desplazamiento
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const nowISO = now.toISOString().substring(0, 16);

    // =================================================================
    // FUNCIONES DE CARGA INICIAL
    // =================================================================

    function loadPacientesAndSedes() {
        $.get('api/api-pacientes.php', {action:'list', per:100, csrf_token:csrf}, function(res) {
            if (res.success) {
                pacientes = res.data;
                const sel = $("#paciente_id").empty().append('<option selected disabled>Seleccione un cliente</option>');
                pacientes.forEach(p => sel.append(`<option value="${p.id}">${p.nombre}</option>`));
            }
        });

        $.get('api/api-sedes.php', {action:'list', per:100, csrf_token:csrf}, function(res) {
            if (res.success) {
                sedes = res.data;
                const sel = $sedeId.empty().append('<option selected disabled>Seleccione una sede</option>');
                sedes.forEach(s => sel.append(`<option value="${s.id}">${s.nombre}</option>`));
            }
        });
    }

    function loadCitas(){
        $.get('api/api-citas.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
            if(res.success){
                const tbody=$("#citasTable").empty();
                res.data.forEach(c=>{
                    // **CORRECCIÓN: Usar c.notas para consistencia**
                    tbody.append(`<tr>
                        <td class="p-2 border">${c.id}</td>
                        <td class="p-2 border">${c.paciente}</td>
                        <td class="p-2 border">${c.fecha_hora_inicio}</td>
                        <td class="p-2 border">${c.sede}</td>
                        <td class="p-2 border">${c.notas||'N/A'}</td> 
                        <td class="p-2 border">${c.estado}</td>
                        <td class="p-2 border">
                            <button class="text-teal-600 editBtn" data-id="${c.id}">Editar</button>
                            <button class="text-red-600 ml-2 deleteBtn" data-id="${c.id}">Eliminar</button>
                        </td>
                    </tr>`);
                });
                $("#totalReg").text(`Total: ${res.total}`);
                renderPagination(res.total);
            }
        });
    }

    // =================================================================
    // LÓGICA DE HORARIOS Y VALIDACIÓN
    // =================================================================
    
    function getHorarioForDate(dateTimeString, horario) {
        if (!horario || !dateTimeString) return null;

        const selectedDate = new Date(dateTimeString.replace('T', ' ').replace(/-/g, '/')); 
        const dayOfWeek = selectedDate.toLocaleString('es-ES', { weekday: 'short' }).toLowerCase();

        let key;
        if (['lun', 'mar', 'mié', 'mie', 'jue', 'vie'].includes(dayOfWeek)) {
            key = 'lun-vie';
        } else if (dayOfWeek === 'sáb' || dayOfWeek === 'sab') {
            key = 'sab';
        } else if (dayOfWeek === 'dom') {
            key = 'dom';
        } else {
            return null;
        }
        return horario[key];
    }
    
    function validateDateTime(dateTimeString, horario) {
        if (!dateTimeString || !horario) return { valid: true, message: "" };
        
        // 1. Verificación de fecha/hora pasada
        if (dateTimeString < nowISO) {
             return { valid: false, message: "No se puede seleccionar una fecha u hora pasada." };
        }

        const horarioDia = getHorarioForDate(dateTimeString, horario);

        // 2. Validar si el día está cerrado
        if (horarioDia === false) {
            return { valid: false, message: "Día no laborable para esta sede." }; 
        }

        // 3. Validar la hora (si hay un horario definido)
        if (horarioDia && horarioDia.inicio && horarioDia.fin) {
            const timeSelected = dateTimeString.substring(11, 16); 
            const start = horarioDia.inicio;
            const end = horarioDia.fin;

            if (timeSelected >= start && timeSelected < end) {
                return { valid: true, message: "" }; 
            } else {
                return { valid: false, message: `Horario fuera de rango. La atención es de ${start} a ${end}.` }; 
            }
        }

        return { valid: true, message: "" };
    }
    
    function handleValidation() {
        const dateTimeString = $fecha.val();
        $fecha.removeClass("border-red-500");
        $fechaError.addClass("hidden").text("");
        
        if (!dateTimeString || !currentHorario) return true;

        const result = validateDateTime(dateTimeString, currentHorario);

        if (result.valid) {
            return true;
        } else {
            // Mostrar mensaje de error y resaltar el campo
            $fecha.addClass("border-red-500");
            $fechaError.text(result.message).removeClass("hidden");
            return false;
        }
    }


    // =================================================================
    // MANEJADORES DE VISTAS Y PAGINACIÓN
    // =================================================================

    function loadCitasCalendar(){
        $.get('api/api-citas.php',{action:'list', per:1000, csrf_token:csrf},function(res){
            if(res.success){
                const events = res.data.map(c => {
                    const [fecha, horaCompleta] = c.fecha_hora_inicio.split(' ');
                    const hora = horaCompleta ? horaCompleta.substring(0, 5) : '';
                    let categoria = (c.estado === 'pendiente') ? 'work' : (c.estado === 'confirmada' ? 'personal' : 'other'); 

                    return {
                        date: fecha,
                        time: hora,
                        description: `${c.paciente} (${c.estado})`,
                        details: `Sede: ${c.sede}. ${c.notas || ''}`,
                        category: categoria,
                        id: c.id,
                        paciente_id: c.paciente_id,
                        sede: c.sede,
                        notas: c.notas || ''
                    };
                });
                
                inicializarPlanificador({
                    node: 'calendario',
                    eventos: events,
                    colorPrimario: '#3b82f6',
                    modoOscuro: false,
                    vistaInicial: 'month',
                    alClicEnDia: (fecha, eventos) => {
                        if(eventos.length < 1){
                            $citaForm[0].reset();
                            $("#citaId").val('');
                            $fecha.val(`${fecha}T09:00`); 
                            $fecha.prop('min', nowISO); 
                            $fecha.prop('disabled', true); // Desactivar hasta que se seleccione sede
                            $("#modalTitle").text("Nueva Cita");
                            $fechaError.addClass("hidden");
                            $citaModal.removeClass("hidden");
                        }
                    },
                    alClicEnEvento: (evento) => {
                        $.get('api/api-citas.php',{action:'get',id:evento.id},function(res){
                            if(res.success){
                                const c=res.data;
                                const fechaHoraInput = c.fecha_hora_inicio
                                    .replace(' ', 'T')
                                    .substring(0, 16); 
                                
                                $("#citaId").val(c.id);
                                $("#paciente_id").val(c.paciente_id);
                                $sedeId.val(c.sede_id).trigger('change'); 
                                $("#estado").val(c.estado);
                                $fecha.val(fechaHoraInput);
                                $fecha.prop('min', nowISO); 
                                $("#nota").val(c.notas);
                                
                                $("#modalTitle").text("Editar Cita");
                                $citaModal.removeClass("hidden");
                            }
                        });
                    },
                    onNewEvent: (fecha) => {
                        $citaForm[0].reset();
                        $("#citaId").val('');
                        $fecha.val(`${fecha}T09:00`); 
                        $fecha.prop('min', nowISO);
                        $fecha.prop('disabled', true); // Desactivar hasta que se seleccione sede
                        $("#modalTitle").text("Nueva Cita");
                        $fechaError.addClass("hidden");
                        $citaModal.removeClass("hidden");
                    }
                });
            }
        });
    }

    $("#btnCalendario").click(function(){
        $("#tablaCitasView").addClass("hidden");
        $("#calendarView").removeClass("hidden");
        $("#btnCalendario").addClass("hidden");
        $("#btnTabla").removeClass("hidden");
        loadCitasCalendar();
    });

    $("#btnTabla").click(function(){
        $("#calendarView").addClass("hidden");
        $("#tablaCitasView").removeClass("hidden");
        $("#btnTabla").addClass("hidden");
        $("#btnCalendario").removeClass("hidden");
        loadCitas();
    });
    
    function renderPagination(total){
        const totalPages = Math.ceil(total/per);
        const pag=$("#pagination").empty();
        for(let i=1;i<=totalPages;i++){
            pag.append(`<button class="px-3 py-1 rounded ${i===page?'bg-teal-600 text-white':'border'}" data-page="${i}">${i}</button>`);
        }
    }

    $("#pagination").on('click','button',function(){
        page=parseInt($(this).data('page'));
        loadCitas();
    });

    $("#searchCita").on('keyup',function(){
        search=$(this).val();
        page=1;
        loadCitas();
    });

    $("#perPage").on('change',function(){
        per=parseInt($(this).val());
        loadCitas();
    });


    // =================================================================
    // MANEJADORES DEL MODAL (CRUD)
    // =================================================================

    // Botón Nueva Cita
    $(".btnNuevaCita").click(function(){
        $citaForm[0].reset();
        $("#citaId").val('');
        $("#modalTitle").text("Nueva Cita");
        $fecha.prop('min', nowISO); 
        $fecha.prop('disabled', true); // Desactivar fecha hasta seleccionar sede
        $fechaError.addClass("hidden");
        currentHorario = null;
        $citaModal.removeClass("hidden");
    });

    //abrir modal de paciente nuevo
      $("#btnNuevoPaciente").click(function(){
        $("#pacienteForm")[0].reset();
        $("#pacienteId").val('');
        $("#modalTitle").text("Nuevo Paciente");
        $("#pacienteModal").removeClass("hidden");
      });
      //accion al madar formulario de paciente nuevo
        $("#pacienteForm").submit(function(e){
            e.preventDefault();
            $.post('api/api-pacientes.php',$(this).serialize()+"&action=save&csrf_token="+csrf,function(res){
              if(res.success){
                //loadPacientes();
                alert("Paciente creado correctamente", 5000, "success");
                $("#pacienteModal").addClass("hidden");
                $citaForm[0].reset();
                $("#citaId").val('');
                $("#modalTitle").text("Nueva Cita");
                $fecha.prop('min', nowISO); 
                $fecha.prop('disabled', true); // Desactivar fecha hasta seleccionar sede
                $fechaError.addClass("hidden");
                currentHorario = null;
                loadPacientesAndSedes()
                $citaModal.removeClass("hidden");
              } else {
                alert(res.message||"Error");
              }
            });
          });

    // Evento: Al cambiar la Sede
    $sedeId.on('change', function() {
        const sedeId = $(this).val();
        const sede = sedes.find(s => s.id == sedeId);
        
        currentHorario = null;
        $fecha.prop('disabled', true); // Deshabilitar por defecto en caso de error o sede vacía
        $fechaError.addClass("hidden").text(""); // Limpiar errores

        if (!sedeId) {
             $fechaError.text("Seleccione una sede para habilitar la fecha.").removeClass("hidden");
             return;
        }
        
        if (!sede || !sede.horario) {
             $fechaError.text("Sede sin horario definido. La validación de hora estará deshabilitada.").removeClass("hidden");
             $fecha.prop('disabled', false); // Habilitar si no hay horario, pero advertir
             return;
        }

        try {
            currentHorario = JSON.parse(sede.horario);
            $fechaError.addClass("hidden");
            $fecha.prop('disabled', false); // Habilitar fecha
        } catch (e) {
            console.error('Horario inválido:', sede.horario, e);
            $fechaError.text("Error al cargar el horario de la sede.").removeClass("hidden");
            return;
        }
        
        // Ejecutar validación (si hay fecha ya seleccionada)
        handleValidation();
    });
    
    // Evento: Al cambiar la Fecha/Hora
    $fecha.on('change', handleValidation);

    // Envío del Formulario (Validación final)
    $citaForm.submit(function(e){
        e.preventDefault();
        
        // 1. Validar por última vez antes de enviar
        if (!currentHorario) {
            $fechaError.text("Debe seleccionar una Sede válida para validar el horario.").removeClass("hidden");
            return; 
        }
        
        if (!handleValidation()) {
            return;
        }

        // 2. Enviar la cita
        // NOTA: El campo 'nota' en el formulario HTML se mapea a 'notas' en el backend si tu API lo acepta.
        $.post('api/api-citas.php',$(this).serialize()+"&action=save&csrf_token="+csrf,function(res){
            if(res.success){
                loadCitas();
                alert("Cita Actualizado o creado correctamente", 5000, "success");

                if ($("#calendarView").is(":visible")) loadCitasCalendar(); 
                $citaModal.addClass("hidden");
            } else {
                console.log(res.message||"Error al guardar")
                alert(res.message||"Error al guardar",7000,'warning');
            }
        });
    });

    // Botón Editar (Desde la tabla)
    $("#citasTable").on('click','.editBtn',function(){
        const id=$(this).data('id');
        $.get('api/api-citas.php',{action:'get',id:id},function(res){
            if(res.success){
                const c=res.data;
                const fechaHoraInput = c.fecha_hora_inicio
                    .replace(' ', 'T')
                    .substring(0, 16); 
                
                $("#citaId").val(c.id);
                $("#paciente_id").val(c.paciente_id);
                
                // Cargar sede y disparar change para cargar restricciones y habilitar el campo de fecha
                $sedeId.val(c.sede_id).trigger('change'); 
                
                $("#estado").val(c.estado);
                $fecha.val(fechaHoraInput);
                $fecha.prop('min', nowISO); 
                $("#nota").val(c.notas); // **CORRECCIÓN: Usar c.notas para precargar**
                
                $("#modalTitle").text("Editar Cita");
                $citaModal.removeClass("hidden");
            }
        });
    });

    // Botón Cancelar
    $("#btnCancel").click(()=>$citaModal.addClass("hidden"));
    $("#btnCancelP").click(function(){$("#pacienteModal").addClass("hidden");});

    //eliminar cita
    $("#citasTable").on('click', '.deleteBtn', function () {
        const id = $(this).data('id');

        confirm(
            "¿Eliminar cita?",
            "Esta acción no se podrá revertir.",
            "Eliminar",
            function () {
                // Acción ejecutada SOLO si el usuario confirma
                $.post('api/api-citas.php', { action: 'delete', id: id, csrf_token: csrf }, function (res) {
                    if (res.success) {
                        loadCitas();
                        alert("Cita eliminada correctamente", 7000, "success");
                    } else {
                        alert("No se pudo eliminar la cita.");
                    }
                });
            }
        );
    });


    // =================================================================
    // INICIALIZACIÓN
    // =================================================================
    
    // Desactivar la fecha al inicio y establecer el mínimo
    $fecha.prop('min', nowISO);
    $fecha.prop('disabled', true); 
    loadCitas();
    loadPacientesAndSedes();
});
</script>