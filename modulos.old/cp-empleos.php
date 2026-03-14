<?php
// /modulos/empleos.php
?>

    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Empleos</h2>
        <button id="btnNuevoEmpleo" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nuevo Empleo</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchEmpleo" type="text" placeholder="Buscar por título o ubicación..." 
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
              <th class="p-2 border">Título</th>
              <th class="p-2 border">Ubicación</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Fecha creación</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="empleosTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>

<!-- Modal -->
<div id="empleoModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nuevo Empleo</h3>
    <form id="empleoForm" class="space-y-4">
      <input type="hidden" name="id" id="empleoId">

      <div>
        <label class="block text-sm font-medium">Título</label>
        <input type="text" name="titulo" id="titulo" class="border rounded p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Descripción</label>
        <textarea name="descripcion" id="descripcion" class="border rounded p-2 w-full" cols="10" required></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium">Sede</label>
        <select name="sede" id="sede" class="border rounded p-2 w-full" required>
          <option value="">Cargando sedes...</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Activo</label>
        <select name="activo" id="activo" class="border rounded p-2 w-full">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 mt-4">
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
  // 🔹 Función para cargar sedes dinámicamente
  function loadSedes(selectedId = null) {
    const $sedeSelect = $("#sede");
    $sedeSelect.html('<option value="">Cargando sedes...</option>');

    $.get('api/api-sedes.php', { action: 'list', page: 1, per: 9999, csrf }, function (res) {
      if (res.success) {
        $sedeSelect.empty().append('<option value="">Seleccione una sede</option>');
        res.data.forEach(s => {
          const option = $('<option>', { value: s.id, text: s.nombre });
          if (selectedId && parseInt(selectedId) === parseInt(s.id)) {
            option.attr('selected', true);
          }
          $sedeSelect.append(option);
        });
      } else {
        $sedeSelect.html('<option value="">Error al cargar sedes</option>');
      }
    }).fail(() => {
      $sedeSelect.html('<option value="">Error al conectar con la API</option>');
    });
  }
  function loadEmpleos(){
    $.get('api/api-empleos.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#empleosTable").empty();
        res.data.forEach(e=>{
          tbody.append(`<tr>
            <td class="p-2 border">${e.id}</td>
            <td class="p-2 border">${e.titulo}</td>
            <td class="p-2 border">${e.sede}</td>
            <td class="p-2 border">${e.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">${e.fecha_publicacion}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${e.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${e.id}">Eliminar</button>
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
    loadEmpleos();
  });

  $("#searchEmpleo").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadEmpleos();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadEmpleos();
  });

  $("#btnNuevoEmpleo").click(function(){
    $("#empleoForm")[0].reset();
    $("#empleoId").val('');
    $("#modalTitle").text("Nuevo Empleo");
    loadSedes();
    $("#empleoModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#empleoModal").addClass("hidden"));

  $("#empleoForm").submit(function(e){
    e.preventDefault();
    $.post('api/api-empleos.php',$(this).serialize()+"&action=save&csrf_token="+csrf,function(res){
      if(res.success){
        loadEmpleos();
        alert("Empleo actualizado o creado exitosamente",7000,"success");
        $("#empleoModal").addClass("hidden");
      } else {
        alert(res.message||"Error");
      }
    });
  });

  $("#empleosTable").on('click','.editBtn',function(){
    const id=$(this).data('id');
    $.get('api/api-empleos.php',{action:'get',id:id},function(res){
      if(res.success){
        const e=res.data;
        $("#empleoId").val(e.id);
        $("#titulo").val(e.titulo);
        $("#descripcion").val(e.descripcion);
        //$("#sede").val(e.sede);
        $("#activo").val(e.activo);
        $("#modalTitle").text("Editar Empleo");
        loadSedes(e.sede_id);
        $("#empleoModal").removeClass("hidden");
      }
    });
  });

  $("#empleosTable").on('click', '.deleteBtn', function() {
    const id = $(this).data('id');

    confirm(
      "¿Eliminar empleo?",
      "Esta acción no se podrá revertir.",
      "Eliminar",
      function() {
        // Acción si el usuario confirma
        $.post('api/api-empleos.php', { action: 'delete', id: id, csrf_token: csrf }, function(res) {
          if (res.success) {
            loadEmpleos();
            alert("Empleo eliminado exitosamente", 7000, "success");
          } else {
            alert("No se pudo eliminar");
          }
        });
      }
    );
  });


  loadEmpleos();
});
</script>
