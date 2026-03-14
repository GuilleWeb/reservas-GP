<?php
// /modulos/sedes.php
?>

    <div>
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-teal-700">Sedes</h2>
        <button id="btnNuevaSede" class="px-4 py-2 bg-teal-600 text-white rounded">+ Nueva Sede</button>
      </div>

      <!-- Filtros -->
      <div class="flex items-center mb-4">
        <input id="searchSede" type="text" placeholder="Buscar por nombre o dirección..." 
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
              <th class="p-2 border">Foto</th>
              <th class="p-2 border">Dirección</th>
              <th class="p-2 border">Teléfono</th>
              <th class="p-2 border">Horario</th>
              <th class="p-2 border">Activo</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody id="sedesTable"></tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex justify-between items-center mt-4">
        <span id="totalReg" class="text-sm text-gray-600"></span>
        <div id="pagination" class="flex space-x-2"></div>
      </div>
    </div>


<!-- Modal -->
<div id="sedeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
  <!-- Contenedor del modal con scroll interno -->
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
    <h3 id="modalTitle" class="text-xl font-bold text-teal-700 mb-4 sticky top-0 bg-white pb-2 border-b border-gray-200">
      Nueva Sede
    </h3>

    <form id="sedeForm" class="space-y-4" enctype="multipart/form-data">
      <input type="hidden" name="id" id="sedeId">

      <!-- Nombre -->
      <div>
        <label class="block text-sm font-medium mb-1">Nombre</label>
        <input type="text" name="nombre" id="nombre" class="border rounded p-2 w-full" required>
      </div>

      <!-- Dirección -->
      <div>
        <label class="block text-sm font-medium mb-1">Dirección</label>
        <textarea name="direccion" id="direccion" class="border rounded p-2 w-full" required></textarea>
      </div>

      <!-- Teléfono -->
      <div>
        <label class="block text-sm font-medium mb-1">Teléfono</label>
        <input type="text" name="telefono" id="telefono" class="border rounded p-2 w-full" required>
      </div>

      <!-- Imagen -->
      <div>
        <label class="block text-sm font-medium mb-1">Imagen</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" class="border rounded p-2 w-full">
        <img id="previewImagen" src="" alt="Vista previa" class="mt-2 w-full h-48 object-cover rounded hidden border">
      </div>

      <!-- Horario -->
      <div>
        <label class="block text-sm font-medium mb-2">Horario de atención</label>
        <div id="horarioContainer" class="space-y-3">

          <!-- Lunes a Viernes -->
          <div class="flex flex-wrap items-center justify-between border p-2 rounded">
            <span class="w-24 font-medium">Lun–Vie</span>
            <label class="flex items-center space-x-1">
              <input type="checkbox" class="abierto" data-dia="lun-vie" checked>
              <span>Abierto</span>
            </label>
            <div class="flex items-center space-x-1 ml-auto">
              <input type="time" class="inicio border rounded p-1" data-dia="lun-vie" value="08:00">
              <span>a</span>
              <input type="time" class="fin border rounded p-1" data-dia="lun-vie" value="17:00">
            </div>
          </div>

          <!-- Sábado -->
          <div class="flex flex-wrap items-center justify-between border p-2 rounded">
            <span class="w-24 font-medium">Sábado</span>
            <label class="flex items-center space-x-1">
              <input type="checkbox" class="abierto" data-dia="sab" checked>
              <span>Abierto</span>
            </label>
            <div class="flex items-center space-x-1 ml-auto">
              <input type="time" class="inicio border rounded p-1" data-dia="sab" value="08:00">
              <span>a</span>
              <input type="time" class="fin border rounded p-1" data-dia="sab" value="12:00">
            </div>
          </div>

          <!-- Domingo -->
          <div class="flex flex-wrap items-center justify-between border p-2 rounded">
            <span class="w-24 font-medium">Domingo</span>
            <label class="flex items-center space-x-1">
              <input type="checkbox" class="abierto" data-dia="dom">
              <span>Abierto</span>
            </label>
            <div class="flex items-center space-x-1 ml-auto">
              <input type="time" class="inicio border rounded p-1" data-dia="dom" value="09:00">
              <span>a</span>
              <input type="time" class="fin border rounded p-1" data-dia="dom" value="13:00">
            </div>
          </div>
        </div>
        <input type="hidden" name="horario" id="horario">
      </div>

      <!-- Activo -->
      <div>
        <label class="block text-sm font-medium mb-1">Activo</label>
        <select name="activo" id="activo" class="border rounded p-2 w-full">
          <option value="1">Sí</option>
          <option value="0">No</option>
        </select>
      </div>

      <!-- Botones -->
      <div class="flex justify-end space-x-3 pt-4 border-t mt-6 sticky bottom-0 bg-white py-3">
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

  function loadSedes(){
    $.get('api/api-sedes.php',{action:'list', page:page, per:per, search:search, csrf_token:csrf},function(res){
      if(res.success){
        alert("Sedes cargadas",5000,"success");
        const tbody=$("#sedesTable").empty();
        res.data.forEach(s=>{
          let horarioText = '';
          try {
            const h = JSON.parse(s.horario);

            if (h['lun-vie']) {
              horarioText += `Lun-Vie: ${h['lun-vie'].inicio}–${h['lun-vie'].fin}<br>`;
            }
            if (h['sab']) {
              horarioText += `Sáb: ${h['sab'].inicio}–${h['sab'].fin}<br>`;
            } else if (h['sab'] === false) {
              horarioText += `Sáb: Cerrado<br>`;
            }
            if (h['dom']) {
              horarioText += `Dom: ${h['dom'].inicio}–${h['dom'].fin}`;
            } else if (h['dom'] === false) {
              horarioText += `Dom: Cerrado`;
            }
          } catch (e) {
            horarioText = 'Sin horario';
          }
          tbody.append(`<tr>
            <td class="p-2 border">${s.id}</td>
            <td class="p-2 border">${s.nombre}</td>
            <td class="p-2 border"><img src="${s.imagen}" class="h-12 w-20 object-cover rounded"></td>
            <td class="p-2 border">${s.direccion}</td>
            <td class="p-2 border">${s.telefono}</td>
            <td class="p-2 border">${horarioText}</td>
            <td class="p-2 border">${s.activo==1?'Sí':'No'}</td>
            <td class="p-2 border">
              <button class="text-teal-600 editBtn" data-id="${s.id}">Editar</button>
              <button class="text-red-600 ml-2 deleteBtn" data-id="${s.id}">Eliminar</button>
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
    loadSedes();
  });

  $("#searchSede").on('keyup',function(){
    search=$(this).val();
    page=1;
    loadSedes();
  });

  $("#perPage").on('change',function(){
    per=parseInt($(this).val());
    loadSedes();
  });

  $("#btnNuevaSede").click(function(){
    $("#sedeForm")[0].reset();
    $("#sedeId").val('');
    $("#modalTitle").text("Nueva Sede");
    $("#sedeModal").removeClass("hidden");
  });

  $("#btnCancel").click(()=>$("#sedeModal").addClass("hidden"));
  $("#imagen").on("change", function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        $("#previewImagen").attr("src", evt.target.result).removeClass("hidden");
      };
      reader.readAsDataURL(file);
    } else {
      $("#previewImagen").addClass("hidden").attr("src", "");
    }
  });
  $("#sedeForm").submit(function(e){
    e.preventDefault();

    // Construir objeto horario
    let horario = {};
    $("#horarioContainer .abierto").each(function(){
      const dia = $(this).data('dia');
      const abierto = $(this).is(':checked');
      if (!abierto) {
        horario[dia] = false;
      } else {
        const inicio = $(`.inicio[data-dia='${dia}']`).val();
        const fin = $(`.fin[data-dia='${dia}']`).val();
        horario[dia] = { inicio, fin };
      }
    });
    $("#horario").val(JSON.stringify(horario));

    // Enviar con FormData para permitir imagen
    const formData = new FormData(this);
    formData.append("action", "save");
    formData.append("csrf_token", csrf);

    $.ajax({
      url: "api/api-sedes.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function(res){
        if(res.success){
          loadSedes();
          $("#sedeModal").addClass("hidden");
          alert("Sede guardada correctamente", 5000, "success");
        } else {
          alert(res.message || "Error al guardar la sede");
        }
      }
    });
  });

  $("#sedesTable").on("click", ".editBtn", function(){
    const id = $(this).data("id");
    $.get("api/api-sedes.php", {action: "get", id: id}, function(res){
      if(res.success){
        const s = res.data;
        $("#sedeId").val(s.id);
        $("#nombre").val(s.nombre);
        $("#direccion").val(s.direccion);
        $("#telefono").val(s.telefono);
        $("#activo").val(s.activo);

        if(s.imagen){
          $("#previewImagen").attr("src", s.imagen).removeClass("hidden");
        } else {
          $("#previewImagen").addClass("hidden");
        }

        try {
          const horario = JSON.parse(s.horario);
          $("#horarioContainer .abierto").each(function(){
            const dia = $(this).data("dia");
            if (horario[dia] === false) {
              $(this).prop("checked", false);
            } else if (horario[dia]) {
              $(this).prop("checked", true);
              $(`.inicio[data-dia='${dia}']`).val(horario[dia].inicio);
              $(`.fin[data-dia='${dia}']`).val(horario[dia].fin);
            }
          });
        } catch(e) {}

        $("#modalTitle").text("Editar Sede");
        $("#sedeModal").removeClass("hidden");
      }
    });
  });

  $("#sedesTable").on('click', '.deleteBtn', function() {
    const id = $(this).data('id');

    confirm(
      "¿Eliminar sede?",
      "Esta acción no se podrá revertir.",
      "Eliminar",
      function() {
        // Acción si el usuario confirma
        $.post('api/api-sedes.php', { action: 'delete', id: id, csrf_token: csrf }, function(res) {
          if (res.success) {
            loadSedes();
            alert("Sede eliminada exitosamente", 7000, "success");
          } else {
            alert("No se pudo eliminar");
          }
        });
      }
    );
  });


  loadSedes();
});
</script>
