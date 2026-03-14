<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'admin-blog';
include __DIR__ . '/../../includes/topbar.php';
?>

<!-- Quill Styles -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-6">

  <!-- Formulario Izquierdo -->
  <div class="w-full md:w-1/3 bg-white shadow rounded-2xl p-6 border self-start">
    <div class="text-xs text-gray-500 font-semibold tracking-wider uppercase mb-1">Empresa</div>
    <div class="text-xl font-extrabold text-gray-900 mb-6">Gestionar Blog</div>

    <form id="formBlog" class="space-y-4" enctype="multipart/form-data">
      <input type="hidden" id="post_id" name="id" value="0">

      <div>
        <label class="block text-sm font-medium text-gray-700">Título de la Entrada <span
            class="text-red-500">*</span></label>
        <input type="text" id="titulo" name="titulo"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500" required
          placeholder="Ej: Consejos para una sonrisa saludable">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Slug (URL)</label>
        <input type="text" id="slug" name="slug" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2"
          placeholder="ej-consejos-sonrisa">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Imagen Destacada</label>
        <input type="file" id="imagen" name="imagen"
          class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
        <div id="previewImg" class="mt-2 hidden">
          <img src="" class="h-20 w-auto rounded border">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido</label>
        <div id="editor-container" class="bg-white" style="height: 300px;"></div>
        <input type="hidden" id="contenido" name="contenido">
      </div>

      <div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="publicado" name="publicado" value="1" class="sr-only peer" checked>
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 text-sm peer-checked:bg-teal-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all">
          </div>
          <span class="ml-3 text-sm font-medium text-gray-700" id="estadoLabel">Publicado</span>
        </label>
      </div>

      <div id="formAlert" class="hidden rounded p-3 text-sm"></div>

      <div class="pt-4 flex items-center justify-between border-t border-gray-100">
        <button type="button" onclick="resetForm()"
          class="text-sm text-gray-500 hover:text-gray-800 font-medium">Cancelar</button>
        <button type="submit"
          class="bg-gray-900 hover:bg-black text-white px-5 py-2 rounded-lg font-semibold transition"
          id="btnSave">Guardar Post</button>
      </div>
    </form>
  </div>

  <div class="w-full md:w-2/3 bg-white shadow rounded-2xl p-6 border flex flex-col h-[calc(100vh-140px)] min-h-[500px]">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Entradas del Blog</h2>
        <p class="text-sm text-gray-500">Administra las publicaciones para tus clientes.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <select id="selLimit" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
          <option value="10">10 por pág</option>
          <option value="25">25</option>
        </select>
        <div class="relative">
          <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
          <input type="text" id="txtSearch"
            class="pl-9 border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500 w-48"
            placeholder="Buscar título...">
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
      <table class="w-full text-left border-collapse min-w-max">
        <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Entrada</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Autor / Fecha</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
            <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
      </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
      <div class="text-sm text-gray-500" id="pageInfo"></div>
      <div class="flex items-center space-x-1" id="pagination"></div>
    </div>
  </div>
</div>

<script>
  let currentPage = 1;
  let quill;

  $(function () {
    // Initialize Quill
    quill = new Quill('#editor-container', {
      theme: 'snow',
      placeholder: 'Escribe aquí el contenido del post...',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          ['link', 'image'],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          ['clean']
        ]
      }
    });
  });

  function renderPagination(total_pages, current) {
    let html = '';
    if (total_pages <= 1) { $('#pagination').empty(); return; }

    html += `<button onclick="loadData(${current - 1})" class="px-3 py-1 rounded-md border ${current === 1 ? 'opacity-50 pointer-events-none' : ''}"><i class="fas fa-chevron-left"></i></button>`;
    for (let i = 1; i <= total_pages; i++) {
      let active = i === current ? 'bg-teal-600 text-white shadow' : 'border hover:bg-gray-50 text-gray-700';
      if (i === 1 || i === total_pages || (i >= current - 1 && i <= current + 1)) {
        html += `<button onclick="loadData(${i})" class="px-3 py-1 rounded-md ${active}">${i}</button>`;
      } else if (i === current - 2 || i === current + 2) html += `<span class="px-2">...</span>`;
    }
    html += `<button onclick="loadData(${current + 1})" class="px-3 py-1 rounded-md border ${current === total_pages ? 'opacity-50 pointer-events-none' : ''}"><i class="fas fa-chevron-right"></i></button>`;
    $('#pagination').html(html);
  }

  function loadData(page = 1) {
    currentPage = page;
    const per = $('#selLimit').val();
    const search = $('#txtSearch').val().trim();

    $.get('<?= app_url('api/admin/blog.php') ?>', { action: 'list', page: currentPage, per: per, search: search }, function (res) {
      const tbody = $('#tableBody').empty();
      if (res.success && res.data.length > 0) {
        res.data.forEach(item => {
          let badge = parseInt(item.publicado) === 1
            ? `<span class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded text-xs">Publicado</span>`
            : `<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs">Borrador</span>`;

          let img = item.imagen_path ? `<img src="../../${item.imagen_path}" class="w-10 h-10 rounded object-cover mr-3 bg-gray-200">` : `<div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center text-gray-400"><i class="far fa-image"></i></div>`;

          tbody.append(`
          <tr class="hover:bg-teal-50/30 transition-colors group">
            <td class="py-3 px-4 flex items-center">
                ${img}
                <div>
                   <div class="font-semibold text-gray-800">${item.titulo}</div>
                   <div class="text-xs text-gray-400 font-normal">Slug: ${item.slug}</div>
                </div>
            </td>
            <td class="py-3 px-4 text-xs text-gray-600">
                <div><i class="fas fa-user text-gray-400 w-4"></i> ${item.autor || 'N/A'}</div>
                <div class="mt-1"><i class="far fa-calendar text-gray-400 w-4"></i> ${item.created_at.split(' ')[0]}</div>
            </td>
            <td class="py-3 px-4">${badge}</td>
            <td class="py-3 px-4 text-right">
                <button onclick="editItem(${item.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200"><i class="fas fa-edit"></i></button>
                <button onclick="deleteItem(${item.id})" class="text-red-500 hover:text-red-700 bg-red-50 px-2.5 py-1.5 rounded-lg border border-red-200"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Mostrando ${res.data.length} de ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="4" class="py-8 text-center text-gray-500">No hay entradas registradas.</td></tr>');
        $('#pageInfo').text('Mostrando 0 resultados');
      }
      renderPagination(res.total_pages, currentPage);
    }, 'json');
  }

  function resetForm() {
    $('#formBlog')[0].reset();
    $('#post_id').val(0);
    $('#formAlert').addClass('hidden');
    $('#btnSave').text('Guardar Post');
    $('#publicado').prop('checked', true);
    $('#estadoLabel').text('Publicado');
    $('#previewImg').addClass('hidden').find('img').attr('src', '');
    quill.setContents([]);
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/blog.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#post_id').val(d.id);
        $('#titulo').val(d.titulo);
        $('#slug').val(d.slug);
        quill.root.innerHTML = d.contenido || '';

        const st = parseInt(d.publicado) === 1;
        $('#publicado').prop('checked', st);
        $('#estadoLabel').text(st ? 'Publicado' : 'Borrador');

        if (d.imagen_path) {
          $('#previewImg').removeClass('hidden').find('img').attr('src', '../../' + d.imagen_path);
        }

        $('#btnSave').text('Actualizar Post');
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas eliminar este post definitivamente?", function () {
      $.post('<?= app_url('api/admin/blog.php') ?>', { action: 'delete', id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Post eliminado.', 5000, 'success');
        } else {
          showCustomAlert(res.message || 'Error al eliminar', 5000, 'error');
        }
      }, 'json');
    });
  }

  $(function () {
    $('#publicado').on('change', function () { $('#estadoLabel').text(this.checked ? 'Publicado' : 'Borrador'); });

    $('#formBlog').on('submit', function (e) {
      e.preventDefault();

      // Sync Quill content to hidden input
      $('#contenido').val(quill.root.innerHTML);

      let formData = new FormData(this);
      formData.append('action', 'save');
      if (!$("#publicado").is(":checked")) formData.set('publicado', '0');

      $.ajax({
        url: '<?= app_url('api/admin/blog.php') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
          if (res.success) {
            resetForm();
            loadData(currentPage);
            showCustomAlert('Post guardado correctamente.', 5000, 'success');
          } else {
            showCustomAlert(res.message || 'Error al guardar', 5000, 'error');
          }
        }
      });
    });

    $('#selLimit').on('change', () => loadData(1));
    $('#txtSearch').on('keyup', function (e) { if (e.key === 'Enter') loadData(1); });

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>