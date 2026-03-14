<?php
// /modulos/roles.php
?>

    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Roles y Permisos</h2>
        <button id="btnNuevoRol" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nuevo Rol</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchRol" type="text" placeholder="Buscar por nombre..." 
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
              <th class="p-2 border">Rol</th>
              <th class="p-2 border">Crear</th>
              <th class="p-2 border">Leer</th>
              <th class="p-2 border">Actualizar</th>
              <th class="p-2 border">Borrar</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="rolesTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>


<!-- Modal -->
<div id="rolesModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow p-6 w-full max-w-lg">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nuevo Rol</h3>
    <form id="rolesForm" class="space-y-4">
      <input type="hidden" name="id" id="rolId">

      <div>
        <label class="block text-sm font-medium">Nombre del Rol</label>
        <input type="text" name="nombre_rol" id="nombre_rol" class="border rounded p-2 w-full" required>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div><label><input type="checkbox" name="permiso_crear" id="permiso_crear"> Crear</label></div>
        <div><label><input type="checkbox" name="permiso_leer" id="permiso_leer"> Leer</label></div>
        <div><label><input type="checkbox" name="permiso_actualizar" id="permiso_actualizar"> Actualizar</label></div>
        <div><label><input type="checkbox" name="permiso_borrar" id="permiso_borrar"> Borrar</label></div>
      </div>

      <div>
        <label class="block text-sm font-medium">Activo</label>
        <select name="estado" id="estado" class="border rounded p-2 w-full">
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

  function loadRoles(){
    $.get('api/api-roles.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#rolesTable").empty();
        res.data.forEach(r=>{
          tbody.append(`<tr>
            <td class="p-2 border">${r.id}</td>
            <td class="p-2 border">${r.nombre_rol}</td>
            <td class="p-2 border">${r.permiso_crear? 'Sí':'No'}</td>
            <td class="p-2 border">${r.permiso_leer? 'Sí':'No'}</td>
            <td class="p-2 border">${r.permiso_actualizar? 'Sí':'No'}</td>
            <td class="p-2 border">${r.permiso_borrar? 'Sí':'No'}</td>
            <td class="p-2 border">${r.estado? 'Sí':'No'}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${r.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${r.id}">Eliminar</button>
            </td>
          </tr>`);
        });
        $("#totalReg").text(`Total: ${res.total}`);
        renderPagination(res.total);
      }
    });
  }

  function renderPagination(total){
    const totalPages=Math.ceil(total/per);
    const pag=$("#pagination").empty();
    for(let i=1;i<=totalPages;i++){
      pag.append(`<button class="px-3 py-1 rounded ${i===page?'bg-teal-600 text-white':'border'}" data-page="${i}">${i}</button>`);
    }
  }

  $("#pagination").on('click','button',function(){
    page=parseInt($(this).data('page'));
    loadRoles();
  });

  $("#searchRol").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadRoles();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadRoles();
  });

  $("#btnNuevoRol").click(function(){
    $("#rolesForm")[0].reset();
    $("#rolId").val('');
    $("#modalTitle").text("Nuevo Rol");
    $("#rolesModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#rolesModal").addClass("hidden"));

  $("#rolesForm").submit(function(e){
    e.preventDefault();
    const data = {
      action:'save',
      id: $("#rolId").val(),
      nombre_rol: $("#nombre_rol").val(),
      permiso_crear: $("#permiso_crear").prop('checked')?1:0,
      permiso_leer: $("#permiso_leer").prop('checked')?1:0,
      permiso_actualizar: $("#permiso_actualizar").prop('checked')?1:0,
      permiso_borrar: $("#permiso_borrar").prop('checked')?1:0,
      estado: $("#estado").val(),
      csrf_token: csrf
    };
    $.post('api/api-roles.php', data, function(res){
      if(res.success){
        loadRoles();
        $("#rolesModal").addClass("hidden");
        alert("Rol Actualizado o creado correctamente", 5000, "success");
      } else {
        alert(res.message||"Error");
      }
    },'json');
  });

  $("#rolesTable").on('click','.editBtn',function(){
    const id=$(this).data('id');
    $.get('api/api-roles.php',{action:'get',id:id},function(res){
      if(res.success){
        const r=res.data;
        $("#rolId").val(r.id);
        $("#nombre_rol").val(r.nombre_rol);
        $("#permiso_crear").prop('checked',r.permiso_crear==1);
        $("#permiso_leer").prop('checked',r.permiso_leer==1);
        $("#permiso_actualizar").prop('checked',r.permiso_actualizar==1);
        $("#permiso_borrar").prop('checked',r.permiso_borrar==1);
        $("#estado").val(r.estado);
        $("#modalTitle").text("Editar Rol");
        $("#rolesModal").removeClass("hidden");
      }
    },'json');
  });

  $("#rolesTable").on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      confirm(
          "¿Eliminar este rol?",
          "Esta acción no se podrá revertir.",
          "Eliminar",
          function () {
              $.post('api/api-roles.php', { action: 'delete', id: id, csrf_token: csrf }, function (res) {
                  if (res.success) {
                      loadRoles();
                      alert("Rol eliminado correctamente", 5000, "success");
                  } else {
                      alert("No se pudo eliminar el rol.");
                  }
              }, 'json');
          }
      );
  });

  loadRoles();
});
</script>
