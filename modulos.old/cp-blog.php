<?php
// /modulos/blog.php
?>

    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Blog</h2>
        <button id="btnNuevoPost" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nueva Entrada</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchPost" type="text" placeholder="Buscar por título..." 
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
              <th class="p-2 border">Autor</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Fecha publicación</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="blogTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>

<!-- Modal Blog -->
<div id="blogModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white rounded-lg shadow w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nueva Entrada</h3>

    <form id="blogForm" class="space-y-4" enctype="multipart/form-data">
      <input type="hidden" name="id" id="postId">

      <div>
        <label class="block text-sm font-medium">Título</label>
        <input type="text" name="titulo" id="titulo" class="border rounded p-2 w-full" required>
      </div>

      <div>
        <label class="block text-sm font-medium">Contenido</label>
        <textarea name="contenido" id="contenido" class="border rounded p-2 w-full" rows="6" required></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium">Imagen</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" class="border rounded p-2 w-full">
        <img id="previewImagen" src="" alt="Vista previa" class="mt-2 w-full h-48 object-cover rounded hidden">
      </div>

      <div>
        <label class="block text-sm font-medium">Autor</label>
        <input type="text" id="autor" class="border rounded p-2 w-full bg-gray-100 cursor-not-allowed" disabled>
      </div>

      <div>
        <label class="block text-sm font-medium">Fecha publicación</label>
        <input type="text" id="fecha_publicacion" class="border rounded p-2 w-full bg-gray-100 cursor-not-allowed" disabled>
      </div>

      <div>
        <label class="block text-sm font-medium">Activo</label>
        <select name="activo" id="activo" class="border rounded p-2 w-full">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 mt-4 sticky bottom-0 bg-white pt-4 pb-2 border-t">
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

  function loadPosts(){
    $.get('api/api-blog.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#blogTable").empty();
        res.data.forEach(p=>{
          tbody.append(`<tr>
            <td class="p-2 border">${p.id}</td>
            <td class="p-2 border">${p.titulo}</td>
            <td class="p-2 border">${p.autor}</td>
            <td class="p-2 border">${p.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">${p.fecha_publicacion}</td>
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
    loadPosts();
  });

  $("#searchPost").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadPosts();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadPosts();
  });

  $("#btnNuevoPost").click(function () {
    $("#blogForm")[0].reset();
    $("#postId").val('');
    $("#previewImagen").addClass("hidden").attr("src", "");
    $("#fecha_publicacion").val(new Date().toISOString().slice(0, 19).replace("T", " "));
    $("#modalTitle").text("Nueva Entrada");
    $("#blogModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#blogModal").addClass("hidden"));

  $("#blogForm").submit(function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "save");
    formData.append("csrf_token", csrf);

    $.ajax({
      url: "api/api-blog.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.success) {
          loadPosts();
          alert("Entrada guardada correctamente", 5000, "success");
          $("#blogModal").addClass("hidden");
        } else {
          alert(res.message || "Error al guardar");
        }
      },
      error: function () {
        alert("Error de conexión");
      },
    });
  });


  $("#blogTable").on('click', '.editBtn', function () {
    const id = $(this).data('id');

    $.get('api/api-blog.php', { action: 'get', id: id }, function (res) {
      if (res.success) {
        const p = res.data;

        $("#postId").val(p.id);
        $("#titulo").val(p.titulo);
        $("#contenido").val(p.contenido);
        $("#activo").val(p.activo);
        $("#autor").val(p.autor_id);
        $("#fecha_publicacion").val(p.fecha_publicacion || new Date().toISOString().slice(0, 19).replace("T", " "));
        
        if (p.imagen) {
          $("#previewImagen").attr("src", p.imagen).removeClass("hidden");
        } else {
          $("#previewImagen").addClass("hidden").attr("src", "");
        }

        $("#modalTitle").text("Editar Entrada");
        $("#blogModal").removeClass("hidden");
      } else {
        alert("No se pudo cargar la entrada seleccionada");
      }
    });
  });

  $("#blogTable").on('click', '.deleteBtn', function () {
      const id = $(this).data('id');
      confirm(
          "¿Eliminar esta entrada?",
          "Esta acción no se podrá revertir.",
          "Eliminar",
          function () {
              $.post('api/api-blog.php', { action: 'delete', id: id, csrf_token: csrf }, function (res) {
                  if (res.success) {
                      loadPosts();
                      alert("Entrada eliminada correctamente", 5000, "success");
                  } else {
                      alert("No se pudo eliminar la entrada.");
                  }
              });
          }
      );
  });

  loadPosts();
});
</script>
