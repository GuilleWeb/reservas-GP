<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'admin-blog';
include __DIR__ . '/../../includes/topbar.php';
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="max-w-7xl mx-auto">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4" id="formCol">
      <div id="formContainer" class="bg-white rounded-2xl shadow p-5 border transition-all duration-300">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Admin</div>
            <div class="mt-1 text-2xl font-extrabold text-gray-900">Gestionar Blog</div>
          </div>
          <button type="button" onclick="toggleExpandForm()"
            class="text-gray-400 hover:text-teal-600 focus:outline-none p-2 rounded hover:bg-gray-100">
            <i id="expandIcon" data-lucide="expand" class="text-lg"></i>
          </button>
        </div>

        <form id="formBlog" class="mt-4 space-y-3" enctype="multipart/form-data">
          <input type="hidden" id="post_id" name="id" value="0">

          <div>
            <label class="block text-sm font-medium text-gray-700">Título de la Entrada <span
                class="text-red-500">*</span></label>
            <input type="text" id="titulo" name="titulo"
              class="mt-1 w-full border rounded-lg p-2 focus:ring-2 focus:ring-teal-500" required
              placeholder="Ej: Consejos para una sonrisa saludable">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select id="publicado" name="publicado" class="mt-1 w-full border rounded-lg p-2">
              <option value="1">Publicado</option>
              <option value="0">Borrador</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Portada del Post (opcional)</label>
            <input type="file" id="imagen" name="imagen"
              class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
            <div id="previewImg" class="mt-2 hidden">
              <img src="" class="h-20 w-auto rounded border object-cover">
            </div>
          </div>

          <div class="flex-1 flex flex-col">
            <label class="block text-sm font-medium text-gray-700 mb-1">Contenido</label>
            <div id="editor-container" class="bg-white min-h-[300px] flex-1 rounded-b-lg"></div>
            <input type="hidden" id="contenido" name="contenido">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mostrar en Home Page</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="show_in_home" name="show_in_home" value="1" class="sr-only peer">
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full
                peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                peer-checked:bg-teal-600"></div>
              <span class="ml-3 text-sm font-medium text-gray-700">Incluir esta entrada en el inicio</span>
            </label>
          </div>

          <div class="pt-2 flex items-center justify-between gap-2">
            <button type="button" onclick="resetForm()" class="px-2 py-2 border rounded-lg">Nuevo</button>
            <button type="submit" id="btnSave" class="px-2 py-2 bg-teal-600 text-white rounded-lg">Guardar Post</button>
          </div>
        </form>
      </div>
    </div>

    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl shadow border p-5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
          <div>
              <div class="font-semibold text-gray-900">Listado</div>
              <div class="text-sm text-gray-500">Acciones: entrar, editar y eliminar.</div>
            </div>
            <!-- <div class="text-sm text-gray-500">Tip: /{slug}/dashboard</div> -->

          <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input id="txtSearch" type="text" placeholder="Buscar título..."
              class="border rounded-lg p-2 md:col-span-2">
            <select id="selLimit" class="border rounded-lg p-2">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="25">25</option>
            </select>
            <div id="pageInfo" class="text-sm text-gray-600 self-center"></div>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
          <table class="w-full text-left border-collapse min-w-max">
            <thead class="bg-gray-50 text-gray-700">
              <tr>
                <th class="text-left px-4 py-3 select-none">Entrada</th>
                <th class="text-left px-4 py-3 select-none">Fecha</th>
                <th class="text-left px-4 py-3 select-none">Estado</th>
                <th class="text-right px-4 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody id="tableBody" class="divide-y"></tbody>
          </table>
        </div>

        <div class="p-4 border-t">
          <div id="pagination" class="flex flex-wrap gap-2 justify-end"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let currentPage = 1;
  let quill;
  let t = null;

  function toggleExpandForm() {
    const formBox = document.getElementById('formContainer');
    const expandIcon = document.getElementById('expandIcon');
    const isExpanded = formBox.classList.contains('fixed');

    if (!isExpanded) {
      formBox.classList.add('fixed', 'inset-4', 'z-[100]', 'border-2', 'border-teal-500', 'overflow-y-auto', 'flex', 'flex-col');
      const overlay = document.createElement('div');
      overlay.id = 'formOverlay';
      overlay.className = 'fixed inset-0 bg-black/50 z-[90]';
      document.body.appendChild(overlay);
      expandIcon.setAttribute('data-lucide', 'minimize-2');
      document.getElementById('formBlog').classList.add('flex-1', 'flex', 'flex-col');
    } else {
      formBox.classList.remove('fixed', 'inset-4', 'z-[100]', 'border-2', 'border-teal-500', 'overflow-y-auto', 'flex', 'flex-col');
      const overlay = document.getElementById('formOverlay');
      if (overlay) overlay.remove();
      expandIcon.setAttribute('data-lucide', 'expand');
      document.getElementById('formBlog').classList.remove('flex-1', 'flex', 'flex-col');
    }
    if (window.lucide) lucide.createIcons();
  }

  $(function () {
    quill = new Quill('#editor-container', {
      theme: 'snow',
      placeholder: 'Escribe aquí el contenido del post...',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          ['link'],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          ['clean']
        ]
      }
    });
  });

  function renderPagination(total_pages, current) {
    let ht = '';
    if (total_pages <= 1) { $('#pagination').empty(); return; }
    for (let i = 1; i <= total_pages; i++) {
      ht += `<button onclick="loadData(${i})" class="px-3 py-1 rounded ${i === current ? 'bg-teal-600 text-white' : 'border'}">${i}</button>`;
    }
    $('#pagination').html(ht);
  }

  function debounceLoad() { if (t) clearTimeout(t); t = setTimeout(() => loadData(1), 500); }

  function loadData(page = 1) {
    currentPage = page;
    const per = $('#selLimit').val();
    const search = $('#txtSearch').val().trim();

    $.get('<?= app_url('api/admin/blog.php') ?>', { action: 'list', page: currentPage, per: per, search: search }, function (res) {
      if (!res.success) return;
      const tbody = $('#tableBody').empty();

      if (res.data.length > 0) {
        res.data.forEach(item => {
          let badge = parseInt(item.publicado) === 1
            ? `<span class="inline-flex px-2 py-1 rounded-full text-xs bg-teal-50 text-teal-800 border border-teal-100">Publicado</span>`
            : `<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 border">Borrador</span>`;

          let img = item.imagen_path ? `<img src="../../${item.imagen_path}" class="w-10 h-10 rounded object-cover mr-3 bg-gray-200">` : `<div class="w-10 h-10 rounded bg-gray-200 mr-3 flex items-center justify-center text-gray-400"><i data-lucide="image"></i></div>`;

          const publicUrl = <?= json_encode(view_url('vistas/public/blog.php', (get_current_empresa()['slug'] ?? request_id_e()))) ?> + '&id=' + encodeURIComponent(item.id);
          tbody.append(`
          <tr class="hover:bg-gray-50 border-b last:border-0 border-gray-100">
            <td class="px-4 py-3 flex items-center min-w-[200px]">
                ${img}
                <div>
                   <div class="font-medium text-gray-900">${item.titulo}</div>
                   <div class="text-xs text-gray-500 font-mono">${item.slug}</div>
                </div>
            </td>
            <td class="px-4 py-3 text-xs text-gray-600">
                <div><i data-lucide="calendar" class="text-gray-400 w-4"></i> ${String(item.created_at || '').split(' ')[0] || '-'}</div>
            </td>
            <td class="px-4 py-3">${badge}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <a href="${publicUrl}" target="_blank" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-teal-600" title="Ver publicación">
                  <i data-lucide="external-link"></i>
                </a>
                <button onclick="editItem(${item.id})" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white editBtn" title="Editar"><i data-lucide="pen"></i></button>
                <button onclick="deleteItem(${item.id})" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-white text-red-600 deleteBtn" title="Eliminar"><i data-lucide="trash-2"></i></button>
              </div>
            </td>
          </tr>
        `);
        });
        $('#pageInfo').text(`Total: ${res.total}`);
      } else {
        tbody.html('<tr><td colspan="4" class="py-4 text-center text-gray-500">No hay entradas registradas.</td></tr>');
        $('#pageInfo').text('Total: 0');
      }
      renderPagination(res.total_pages || Math.ceil(res.total / per), currentPage);
      if (window.lucide) lucide.createIcons();
    }, 'json');
  }

  function resetForm() {
    $('#formBlog')[0].reset();
    $('#post_id').val(0);
    $('#btnSave').text('Guardar Post');
    $('#publicado').val('1');
    $('#previewImg').addClass('hidden').find('img').attr('src', '');
    quill.setContents([]);
    $('#show_in_home').prop('checked', false);
  }

  function editItem(id) {
    $.get('<?= app_url('api/admin/blog.php') ?>', { action: 'get', id: id }, function (res) {
      if (res.success && res.data) {
        resetForm();
        const d = res.data;
        $('#post_id').val(d.id);
        $('#titulo').val(d.titulo);
        quill.root.innerHTML = d.contenido || '';
        $('#publicado').val(d.publicado || '0');
        $('#show_in_home').prop('checked', parseInt(d.show_in_home || 0, 10) === 1);

        if (d.imagen_path) {
          $('#previewImg').removeClass('hidden').find('img').attr('src', '../../' + d.imagen_path);
        }

        $('#btnSave').text('Actualizar Post');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }, 'json');
  }

  function deleteItem(id) {
    showCustomConfirm("¿Deseas eliminar este post definitivamente?", function () {
      $.post('<?= app_url('api/admin/blog.php') ?>', { action: 'delete', id: id }, function (res) {
        if (res.success) {
          loadData(currentPage);
          showCustomAlert('Post eliminado.', 3000, 'info');
        } else {
          showCustomAlert(res.message || 'Error al eliminar', 5000, 'error');
        }
      }, 'json');
    });
  }

  $(function () {
    $('#formBlog').on('submit', function (e) {
      e.preventDefault();
      $('#contenido').val(quill.root.innerHTML);

      let formData = new FormData(this);
      formData.append('action', 'save');
      if (!$('#show_in_home').is(':checked')) formData.set('show_in_home', '0');

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
    $('#txtSearch').on('keyup', debounceLoad);

    loadData();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
