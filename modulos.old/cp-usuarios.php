<?php
// /modulos/usuarios.php
?>
    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Usuarios</h2>
        <button id="btnNuevoUsuario" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nuevo Usuario</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchUsuario" type="text" placeholder="Buscar por nombre o email..." 
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
              <th class="p-2 border">Rol</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Fecha creación</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="usuariosTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>

<!-- Modal -->
<div id="usuarioModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nuevo Usuario</h3>
    <form id="usuarioForm" class="space-y-4">
      <input type="hidden" name="id" id="usuarioId">

      <div>
        <label class="block text-sm font-medium">Nombre</label>
        <input type="text" name="nombre" id="nombre" class="border rounded p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" id="email" class="border rounded p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Rol</label>
        <select name="rol_id" id="rol_id" class="border rounded p-2 w-full" required>
          <option value="1">Administrador</option>
          <option value="2">Usuario</option>
          <option value="3">Visitante</option>
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

  function loadUsuarios(){
    $.get('api/api-usuarios.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#usuariosTable").empty();
        res.data.forEach(u=>{
          tbody.append(`<tr>
            <td class="p-2 border">${u.id}</td>
            <td class="p-2 border">${u.nombre}</td>
            <td class="p-2 border">${u.email}</td>
            <td class="p-2 border">${u.rol}</td>
            <td class="p-2 border">${u.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">${u.fecha_creacion}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${u.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${u.id}">Eliminar</button>
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
    loadUsuarios();
  });

  $("#searchUsuario").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadUsuarios();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadUsuarios();
  });

  $("#btnNuevoUsuario").click(function(){
    $("#usuarioForm")[0].reset();
    $("#usuarioId").val('');
    $("#modalTitle").text("Nuevo Usuario");
    $("#usuarioModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#usuarioModal").addClass("hidden"));

  $("#usuarioForm").submit(function(e){
    e.preventDefault();
    $.post('api/api-usuarios.php',$(this).serialize()+"&action=save&csrf_token="+csrf,function(res){
      if(res.success){
        loadUsuarios();
        alert("Usuario Actualizado o creado correctamente", 5000, "success");
        $("#usuarioModal").addClass("hidden");
      } else {
        alert(res.message||"Error");
      }
    });
  });

  $("#usuariosTable").on('click','.editBtn',function(){
    const id=$(this).data('id');
    $.get('api/api-usuarios.php',{action:'get',id:id},function(res){
      if(res.success){
        const u=res.data;
        $("#usuarioId").val(u.id);
        $("#nombre").val(u.nombre);
        $("#email").val(u.email);
        $("#rol_id").val(u.rol_id);
        $("#activo").val(u.activo);
        $("#modalTitle").text("Editar Usuario");
        $("#usuarioModal").removeClass("hidden");
      }
    });
  });

  $("#usuariosTable").on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      confirm(
          "¿Eliminar usuario?",
          "Esta acción no se podrá revertir.",
          "Eliminar",
          function () {
              $.post('api/api-usuarios.php', { action: 'delete', id: id, csrf_token: csrf }, function (res) {
                  if (res.success) {
                      loadUsuarios();
                      alert("Usuario eliminado correctamente", 5000, "success");
                  } else {
                      alert("No se pudo eliminar el usuario.");
                  }
              });
          }
      );
  });

  loadUsuarios();
});
</script>
