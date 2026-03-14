<?php
// /modulos/galeria.php
?>
<div>
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold text-teal-700">Galería</h2>
    <button id="btnNuevaImagen" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nueva Imagen</button>
  </div>

  <!-- Filtros -->
  <div class="flex items-center mb-4">
    <input id="searchImagen" type="text" placeholder="Buscar por título..." class="border rounded p-2 flex-1 mr-3">
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
          <th class="p-2 border">Descripcion</th>
          <th class="p-2 border">Vista previa</th>
          <th class="p-2 border">Activo</th>
          <th class="p-2 border">Acciones</th>
        </tr>
      </thead>
      <tbody id="galeriaTable"></tbody>
    </table>
  </div>

  <!-- Paginación -->
  <div class="flex justify-between items-center mt-4">
    <span id="totalReg" class="text-sm text-gray-600"></span>
    <div id="pagination" class="flex space-x-2"></div>
  </div>
</div>

<!-- Modal -->
<div id="galeriaModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white rounded-lg shadow p-6 w-full max-w-lg relative max-h-[90vh] overflow-y-auto">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4">Nueva Imagen</h3>

    <form id="galeriaForm" class="space-y-4" enctype="multipart/form-data">
      <input type="hidden" name="id" id="imagenId">

      <div>
        <label class="block text-sm font-medium">Título</label>
        <input type="text" name="titulo" id="titulo" class="border rounded p-2 w-full" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Descripcion</label>
        <textarea type="text" name="descripcion" id="descripcion" class="border rounded p-2 w-full"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium">Imagen</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" class="border rounded p-2 w-full">
        <img id="previewImagen" src="" alt="Vista previa" class="mt-2 w-full h-48 object-cover rounded hidden">
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

  function loadGaleria(){
    $.get('api/api-galeria.php',{action:'list', page, per, search, csrf_token:csrf},function(res){
      if(res.success){
        const tbody=$("#galeriaTable").empty();
        res.data.forEach(i=>{
          tbody.append(`<tr>
            <td class="p-2 border">${i.id}</td>
            <td class="p-2 border">${i.titulo}</td>
            <td class="p-2 border">${i.descripcion}</td>
            <td class="p-2 border"><img src="${i.url_imagen}" class="h-12 w-20 object-cover rounded"></td>
            <td class="p-2 border">${i.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${i.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${i.id}">Eliminar</button>
            </td>
          </tr>`);
        });
        $("#totalReg").text(`Total: ${res.total}`);
        renderPagination(res.total);
      }
    },'json');
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
    loadGaleria();
  });

  $("#searchImagen").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadGaleria();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadGaleria();
  });

  $("#btnNuevaImagen").click(function(){
    $("#galeriaForm")[0].reset();
    $("#imagenId").val('');
    $("#previewImagen").attr("src","").addClass("hidden");
    $("#modalTitle").text("Nueva Imagen");
    $("#galeriaModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#galeriaModal").addClass("hidden"));

  // Vista previa imagen
  $("#imagen").on('change', function(){
    const file = this.files[0];
    if(file){
      const reader = new FileReader();
      reader.onload = e => $("#previewImagen").attr("src", e.target.result).removeClass("hidden");
      reader.readAsDataURL(file);
    }
  });

  $("#galeriaForm").submit(function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action','save');
    formData.append('csrf_token',csrf);

    $.ajax({
      url:'api/api-galeria.php',
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      dataType:'json',
      success:function(res){
        if(res.success){
          loadGaleria();
          alert("Entrada Actualizado o creado correctamente", 5000, "success");
          $("#galeriaModal").addClass("hidden");
        } else {
          alert(res.message||"Error al guardar");
        }
      }
    });
  });

  $("#galeriaTable").on('click','.editBtn',function(){
    const id=$(this).data('id');
    $.get('api/api-galeria.php',{action:'get',id:id},function(res){
      if(res.success){
        const i=res.data;
        $("#imagenId").val(i.id);
        $("#titulo").val(i.titulo);
        $("#descripcion").val(i.descripcion);
        $("#activo").val(i.activo);
        if(i.url_imagen){
          $("#previewImagen").attr("src",i.url_imagen).removeClass("hidden");
        }
        $("#modalTitle").text("Editar Imagen");
        $("#galeriaModal").removeClass("hidden");
      }
    },'json');
  });

  $("#galeriaTable").on('click', '.deleteBtn', function () {
    const id = $(this).data('id');
    if(confirm("¿Eliminar esta imagen?")){
      $.post('api/api-galeria.php', { action: 'delete', id, csrf_token: csrf }, function (res) {
        if (res.success) {
          loadGaleria();
          alert("Imagen eliminada correctamente");
        } else {
          alert("No se pudo eliminar la imagen.");
        }
      },'json');
    }
  });

  loadGaleria();
});
</script>
