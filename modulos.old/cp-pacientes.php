<?php
// /modulos/pacientes.php
?>

    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Pacientes</h2>
        <button id="btnNuevoPaciente" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nuevo Paciente</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchPaciente" type="text" placeholder="Buscar por nombre o email..." 
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
              <th class="p-2 border">Nombre</th>
              <th class="p-2 border">Email</th>
              <th class="p-2 border">Teléfono</th>
              <th class="p-2 border">Fecha Nac.</th>
              <th class="p-2 border">Dirección</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="pacientesTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>

<!-- Modal -->
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
        <button type="button" id="btnCancel" class="px-4 py-2 border rounded">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded">Guardar</button>
      </div>
    </form>
  </div>
</div>


<script>
$(function(){
  const csrf = $('meta[name="csrf-token"]').attr('content');
  let page=1, per=10, search='';

  function loadPacientes(){
    $.get('api/api-pacientes.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#pacientesTable").empty();
        res.data.forEach(p=>{
          tbody.append(`<tr>
            <td class="p-2 border">${p.id}</td>
            <td class="p-2 border">${p.nombre}</td>
            <td class="p-2 border">${p.email}</td>
            <td class="p-2 border">${p.telefono}</td>
            <td class="p-2 border">${p.fecha_nacimiento}</td>
            <td class="p-2 border">${p.direccion}</td>
            <td class="p-2 border">${p.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${p.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${p.id}">Eliminar</button>
            </td>
          </tr>`);
        });
        $("#totalReg").text(`Total: ${res.total}`);
        renderPagination(res.total);
      }
    });
  }

  function renderPagination(total){
    const totalPages = Math.ceil(total/per);
    const pag=$("#pagination").empty();
    for(let i=1;i<=totalPages;i++){
      pag.append(`<button class="px-3 py-1 rounded ${i===page?'bg-teal-600 text-white':'border'}" data-page="${i}">${i}</button>`);
    }
  }

  $("#pagination").on('click','button',function(){
    page=parseInt($(this).data('page'));
    loadPacientes();
  });

  $("#searchPaciente").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadPacientes();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadPacientes();
  });

  $("#btnNuevoPaciente").click(function(){
    $("#pacienteForm")[0].reset();
    $("#pacienteId").val('');
    $("#modalTitle").text("Nuevo Paciente");
    $("#pacienteModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#pacienteModal").addClass("hidden"));

  $("#pacienteForm").submit(function(e){
    e.preventDefault();
    $.post('api/api-pacientes.php',$(this).serialize()+"&action=save&csrf_token="+csrf,function(res){
      if(res.success){
        loadPacientes();
        alert("Paciente Actualizado o creado correctamente", 5000, "success");
        $("#pacienteModal").addClass("hidden");
      } else {
        alert(res.message||"Error");
      }
    });
  });

  $("#pacientesTable").on('click','.editBtn',function(){
    const id=$(this).data('id');
    $.get('api/api-pacientes.php',{action:'get',id:id},function(res){
      if(res.success){
        const p=res.data;
        $("#pacienteId").val(p.id);
        $("#nombre").val(p.nombre);
        $("#email").val(p.email);
        $("#telefono").val(p.telefono);
        $("#fecha_nacimiento").val(p.fecha_nacimiento);
        $("#direccion").val(p.direccion);
        $("#activo").val(p.activo);
        $("#modalTitle").text("Editar Paciente");
        $("#pacienteModal").removeClass("hidden");
      }
    });
  });

  $("#pacientesTable").on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      confirm(
          "¿Eliminar paciente?",
          "Esta acción no se podrá revertir.",
          "Eliminar",
          function () {
              $.post('api/api-pacientes.php', { action: 'delete', id: id, csrf_token: csrf }, function (res) {
                  if (res.success) {
                      loadPacientes();
                      alert("Paciente eliminado correctamente", 5000, "success");
                  } else {
                      alert("No se pudo eliminar el paciente.");
                  }
              });
          }
      );
  });

  loadPacientes();
});
</script>
