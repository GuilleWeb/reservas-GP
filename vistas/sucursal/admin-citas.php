<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$role = $user['rol'] ?? null;
$id_e = request_id_e();
$module = 'admin-citas';
include __DIR__ . '/../../includes/topbar.php';
?>

<div class="max-w-7xl mx-auto">
  <div class="bg-white shadow rounded-2xl p-6 border min-h-[500px]">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Agenda de Citas (Gerencia)</h2>
        <p class="text-sm text-gray-500">Vista tabla y calendario para citas de tu sucursal.</p>
      </div>
      <div class="inline-flex rounded-xl border border-gray-200 p-1 bg-gray-50">
        <button id="btnViewTable" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700">Tabla</button>
        <button id="btnViewCalendar" class="px-4 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white">Calendario</button>
      </div>
    </div>

    <!-- Creación manual deshabilitada temporalmente en frontend por decisión de negocio -->

    <div class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
      <input id="txtSearch" type="text" placeholder="Buscar por cliente o empleado..." class="border rounded-lg p-2 md:col-span-3">
      <select id="filterStatus" class="border rounded-lg p-2">
        <option value="">Estado: todos</option>
        <option value="pendiente">Pendiente</option>
        <option value="confirmada">Confirmada</option>
        <option value="cancelada">Cancelada</option>
        <option value="completada">Completada</option>
        <option value="no_asistio">No asistió</option>
      </select>
      <select id="selLimit" class="border rounded-lg p-2">
        <option value="10" selected>10</option>
        <option value="25">25</option>
        <option value="50">50</option>
      </select>
      <div id="pageInfo" class="text-sm text-gray-600 self-center"></div>
    </div>
    <div id="tableRangeControls" class="mb-4 flex flex-wrap items-center gap-2 hidden">
      <span class="text-xs font-semibold text-gray-500 uppercase">Rango tabla:</span>
      <button type="button" id="btnTableDay" class="px-3 py-1.5 rounded-lg border text-sm">Día</button>
      <button type="button" id="btnTableWeek" class="px-3 py-1.5 rounded-lg border text-sm">Semana</button>
      <button type="button" id="btnTableMonth" class="px-3 py-1.5 rounded-lg border text-sm">Mes</button>
      <button type="button" id="btnTableAll" class="px-3 py-1.5 rounded-lg border text-sm bg-teal-600 text-white border-teal-600">Todo</button>
      <div id="tableRangeLabel" class="text-sm text-gray-600"></div>
    </div>

    <div id="tableView" class="hidden">
      <div class="flex-1 overflow-auto bg-gray-50 rounded-lg border border-gray-100">
        <table class="w-full text-left border-collapse min-w-max">
          <thead class="bg-white border-b sticky top-0 z-10 shadow-sm">
            <tr>
              <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Fecha / Hora</th>
              <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Cliente / Servicio</th>
              <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Estado</th>
              <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase text-right">Acciones</th>
            </tr>
          </thead>
          <tbody id="tableBody" class="divide-y divide-gray-100 bg-white"></tbody>
        </table>
      </div>
      <div class="mt-4 flex flex-col sm:flex-row items-center justify-between border-t pt-4">
        <div></div>
        <div class="flex items-center space-x-1" id="pagination"></div>
      </div>
    </div>

    <div id="calendarView" class="hidden">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div class="lg:col-span-4 rounded-2xl border border-gray-200 bg-white overflow-hidden">
          <div class="px-4 py-3 border-b bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-white">
              <button id="btnModeMonth" class="px-3 h-8 rounded-md text-xs font-semibold text-gray-700">Mes</button>
              <button id="btnModeWeek" class="px-3 h-8 rounded-md text-xs font-semibold text-gray-700">Semana</button>
              <button id="btnModeDay" class="px-3 h-8 rounded-md text-xs font-semibold bg-teal-600 text-white">Día</button>
            </div>
            <div class="flex items-center gap-2">
              <button id="btnPrevRange" class="h-9 w-9 grid place-items-center rounded-lg border bg-white hover:bg-gray-50">
                <i data-lucide="chevron-left"></i>
              </button>
              <div id="rangeLabel" class="text-sm font-semibold text-gray-700 min-w-[190px] text-center"></div>
              <button id="btnNextRange" class="h-9 w-9 grid place-items-center rounded-lg border bg-white hover:bg-gray-50">
                <i data-lucide="chevron-right"></i>
              </button>
              <button id="btnToday" class="px-3 h-9 rounded-lg border bg-white text-sm font-medium hover:bg-gray-50">Hoy</button>
            </div>
          </div>
          <div id="weekdayHead" class="grid grid-cols-7 border-b text-xs font-semibold text-gray-600 bg-gray-50">
            <div class="p-2 text-center">Lun</div>
            <div class="p-2 text-center">Mar</div>
            <div class="p-2 text-center">Mié</div>
            <div class="p-2 text-center">Jue</div>
            <div class="p-2 text-center">Vie</div>
            <div class="p-2 text-center">Sáb</div>
            <div class="p-2 text-center">Dom</div>
          </div>
          <div id="calendarGrid" class="grid grid-cols-7 gap-2 p-3 bg-gray-50"></div>
        </div>
        <div class="lg:col-span-1 bg-gray-50 rounded-xl border border-gray-100 p-4">
          <div class="text-sm font-semibold text-gray-800 mb-2" id="dayAgendaTitle">Citas del día</div>
          <div id="dayAgenda" class="space-y-2 max-h-[420px] overflow-auto"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="citaModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border w-full max-w-2xl p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900">Detalle de cita</h3>
      <button type="button" id="btnCloseModal" class="h-9 w-9 grid place-items-center rounded-lg border hover:bg-gray-50">
        <i data-lucide="x"></i>
      </button>
    </div>
    <div id="modalInfo" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-4"></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="text-xs text-gray-600">Estado</label>
        <select id="mEstado" class="border rounded-lg p-2 w-full bg-gray-50" disabled>
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="cancelada">Cancelada</option>
          <option value="completada">Completada</option>
          <option value="no_asistio">No asistió</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs text-gray-600">Observación</label>
        <textarea id="mNotas" rows="3" class="border rounded-lg p-2 w-full bg-gray-50" disabled></textarea>
      </div>
    </div>
    <div class="mt-4 pt-4 border-t flex items-center justify-between">
      <div class="text-xs text-gray-500 max-w-md">Recomendación: confirma la cita con el cliente 10 minutos antes del inicio.</div>
      <button type="button" id="btnEditModal" class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">Editar</button>
      <button type="button" id="btnSaveModal" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700 hidden">Guardar cambios</button>
    </div>
  </div>
</div>

<script>
  const API_URL = '<?= app_url('api/sucursal/admin-citas.php') ?>';
  let currentPage = 1, currentView = 'calendar', calendarMode = 'day', tableRangeMode = 'all';
  let cursorDate = new Date(), selectedDate = toDateKey(new Date()), calendarItems = [], byId = {}, modalItemId = 0;
  const monthFmt = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' });
  const dayFmt = new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });

  function toDateKey(d){ const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),day=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${day}`; }
  function parseSqlDate(dt){ return new Date((dt||'').replace(' ','T')); }
  function startOfWeek(date){ const d=new Date(date.getFullYear(),date.getMonth(),date.getDate()); const day=(d.getDay()+6)%7; d.setDate(d.getDate()-day); return d; }
  function endOfWeek(date){ const s=startOfWeek(date); s.setDate(s.getDate()+6); return s; }
  function fmtDate(d){ return toDateKey(d); }
  function tableRangeParams(){
    const now = new Date();
    if(tableRangeMode==='all'){ $('#tableRangeLabel').text('Mostrando todo el histórico'); return {from:'',to:''}; }
    if(tableRangeMode==='week'){
      const s = startOfWeek(now), e = endOfWeek(now);
      $('#tableRangeLabel').text(`Semana actual: ${s.toLocaleDateString()} - ${e.toLocaleDateString()}`);
      return {from:fmtDate(s), to:fmtDate(e)};
    }
    if(tableRangeMode==='month'){
      const s = new Date(now.getFullYear(), now.getMonth(), 1);
      const e = new Date(now.getFullYear(), now.getMonth()+1, 0);
      $('#tableRangeLabel').text(`Mes actual: ${monthFmt.format(now)}`);
      return {from:fmtDate(s), to:fmtDate(e)};
    }
    $('#tableRangeLabel').text(`Hoy: ${now.toLocaleDateString()}`);
    return {from:fmtDate(now), to:fmtDate(now)};
  }
  function refreshTableRangeButtons(){
    const pairs = [['#btnTableDay','day'],['#btnTableWeek','week'],['#btnTableMonth','month'],['#btnTableAll','all']];
    pairs.forEach(([sel, mode]) => {
      $(sel).toggleClass('bg-teal-600 text-white border-teal-600', tableRangeMode===mode);
    });
  }
  function getRange(){ if(calendarMode==='day'){const d=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()); return {from:d,to:d};} if(calendarMode==='week'){return {from:startOfWeek(cursorDate),to:endOfWeek(cursorDate)};} return {from:new Date(cursorDate.getFullYear(),cursorDate.getMonth(),1),to:new Date(cursorDate.getFullYear(),cursorDate.getMonth()+1,0)}; }
  function getEstadoBadge(estado){ switch(estado){ case 'pendiente': return `<span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">Pendiente</span>`; case 'confirmada': return `<span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Confirmada</span>`; case 'cancelada': return `<span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">Cancelada</span>`; case 'completada': return `<span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Completada</span>`; case 'no_asistio': return `<span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded text-xs">No Asistió</span>`; default: return `<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs">${estado}</span>`; } }

  function renderPagination(totalPages,current){
    const pag=$('#pagination').empty(); if(!totalPages||totalPages<=1) return;
    const prevDisabled=current<=1?'opacity-50 pointer-events-none':''; const nextDisabled=current>=totalPages?'opacity-50 pointer-events-none':'';
    pag.append(`<button onclick="loadTable(${current-1})" class="px-3 py-1 rounded border ${prevDisabled}"><i data-lucide="chevron-left"></i></button>`);
    for(let i=1;i<=totalPages;i++){ if(i===1||i===totalPages||(i>=current-1&&i<=current+1)){ const active=i===current?'bg-teal-600 text-white border-teal-600':'border hover:bg-gray-50'; pag.append(`<button onclick="loadTable(${i})" class="px-3 py-1 rounded ${active}">${i}</button>`);} else if(i===current-2||i===current+2) pag.append('<span class="px-2 text-gray-400">...</span>'); }
    pag.append(`<button onclick="loadTable(${current+1})" class="px-3 py-1 rounded border ${nextDisabled}"><i data-lucide="chevron-right"></i></button>`);
    if(window.lucide) lucide.createIcons();
  }

  function loadTable(page=1){
    currentPage=page;
    const range = tableRangeParams();
    $.get(API_URL,{action:'list',page:currentPage,per:$('#selLimit').val()||10,search:($('#txtSearch').val()||'').trim(),estado:$('#filterStatus').val()||'',from:range.from,to:range.to},function(res){
      const tbody=$('#tableBody').empty(); byId={};
      if(res.success&&res.data.length){
        res.data.forEach(item=>{
          byId[item.id]=item;
          const d=parseSqlDate(item.inicio), dateStr=d.toLocaleDateString()+' '+d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
          tbody.append(`<tr class="hover:bg-teal-50/30 transition-colors"><td class="py-3 px-4 font-semibold text-gray-800 text-sm">${dateStr}<br><span class="text-xs text-gray-500 font-normal">Sede: ${item.sucursal_nombre || 'N/A'}</span></td><td class="py-3 px-4"><div class="text-sm font-semibold">${item.cliente_nombre || '-'}</div><div class="text-xs text-gray-500">${item.servicio_nombre || '-'} · ${item.empleado_nombre || '-'}</div></td><td class="py-3 px-4">${getEstadoBadge(item.estado)}</td><td class="py-3 px-4 text-right"><button onclick="viewDetails(${item.id})" class="text-teal-600 hover:text-teal-800 bg-teal-50 px-2.5 py-1.5 rounded-lg border border-teal-200"><i data-lucide="eye"></i></button></td></tr>`);
        });
        $('#pageInfo').text(`Mostrando ${res.data.length} de ${res.total}`);
      } else { tbody.html('<tr><td colspan="4" class="py-10 text-center text-gray-500">No hay citas registradas.</td></tr>'); $('#pageInfo').text('Mostrando 0 resultados'); }
      renderPagination(res.total_pages||1,currentPage); if(window.lucide) lucide.createIcons();
    },'json');
  }

  function loadCalendarRange(){ const r=getRange(); $.get(API_URL,{action:'calendar',from:toDateKey(r.from),to:toDateKey(r.to)},function(res){ calendarItems=(res.success&&Array.isArray(res.data))?res.data:[]; calendarItems.forEach(it=>byId[it.id]=it); renderCalendar(); renderDayAgenda(selectedDate); },'json'); }
  function renderCalendar(){
    const grouped={}; calendarItems.forEach(c=>{const k=toDateKey(parseSqlDate(c.inicio)); grouped[k]=grouped[k]||[]; grouped[k].push(c);});
    const grid=$('#calendarGrid').empty(); const todayKey=toDateKey(new Date()); let days=[]; let label='';
    if(calendarMode==='month'){ const firstDay=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),1); const lastDay=new Date(cursorDate.getFullYear(),cursorDate.getMonth()+1,0); const startOffset=(firstDay.getDay()+6)%7; for(let i=0;i<startOffset;i++) days.push(null); for(let d=1;d<=lastDay.getDate();d++) days.push(new Date(cursorDate.getFullYear(),cursorDate.getMonth(),d)); label=monthFmt.format(cursorDate); $('#weekdayHead').removeClass('hidden'); grid.removeClass('grid-cols-1').addClass('grid-cols-7'); }
    else if(calendarMode==='week'){ const s=startOfWeek(cursorDate); for(let i=0;i<7;i++) days.push(new Date(s.getFullYear(),s.getMonth(),s.getDate()+i)); const e=endOfWeek(cursorDate); label=`${s.toLocaleDateString()} - ${e.toLocaleDateString()}`; $('#weekdayHead').removeClass('hidden'); grid.removeClass('grid-cols-1').addClass('grid-cols-7'); }
    else { const d=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()); days=[d]; label=dayFmt.format(d); $('#weekdayHead').addClass('hidden'); grid.removeClass('grid-cols-7').addClass('grid-cols-1'); }
    $('#rangeLabel').text(label);
    days.forEach(day=>{ if(!day){ grid.append('<div class="h-20 bg-gray-50/50 rounded-lg"></div>'); return; } const key=toDateKey(day), count=(grouped[key]||[]).length; const selected=key===selectedDate?'ring-2 ring-teal-500 bg-teal-50':'bg-white'; const today=key===todayKey?'border-teal-300':'border-gray-100'; const badge=count>0?`<span class="inline-flex items-center justify-center text-[11px] px-2 py-0.5 rounded-full bg-teal-100 text-teal-700 font-semibold">${count}</span>`:'<span class="text-[11px] text-gray-300">Sin citas</span>'; const hClass=calendarMode==='day'?'h-32':'h-20'; grid.append(`<button type="button" class="${hClass} ${today} ${selected} p-2 text-left hover:bg-teal-50/60 transition day-cell rounded-xl border shadow-sm bg-white" data-date="${key}"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-gray-800">${day.getDate()}</span>${badge}</div><div class="mt-2 space-y-1">${(grouped[key]||[]).slice(0,calendarMode==='day'?5:2).map(it=>{const dt=parseSqlDate(it.inicio); const hh=dt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); return `<div class="text-[11px] truncate text-gray-600">${hh} · ${it.cliente_nombre || 'Cliente'}</div>`;}).join('')}</div></button>`); });
  }
  function renderDayAgenda(dateKey){
    selectedDate=dateKey; $('.day-cell').removeClass('ring-2 ring-teal-500 bg-teal-50'); $(`.day-cell[data-date="${dateKey}"]`).addClass('ring-2 ring-teal-500 bg-teal-50');
    const items=calendarItems.filter(c=>toDateKey(parseSqlDate(c.inicio))===dateKey).sort((a,b)=>parseSqlDate(a.inicio)-parseSqlDate(b.inicio)); const d=parseSqlDate(dateKey+' 00:00:00'); $('#dayAgendaTitle').text(`Citas del ${dayFmt.format(d)}`);
    const box=$('#dayAgenda').empty(); if(!items.length){ box.html('<div class="text-sm text-gray-500">No hay citas para este día.</div>'); return; }
    items.forEach(item=>{ const start=parseSqlDate(item.inicio).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); const end=item.fin?parseSqlDate(item.fin).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}):'--:--'; box.append(`<button type="button" onclick="viewDetails(${item.id})" class="w-full text-left bg-white rounded-lg border p-3 flex flex-col gap-2 hover:bg-teal-50/40"><div class="font-semibold text-gray-800">${start} - ${end}</div><div class="text-sm text-gray-600">${item.cliente_nombre || '-'} · ${item.servicio_nombre || '-'}</div><div class="text-xs text-gray-500">${item.sucursal_nombre || 'N/A'} · ${item.empleado_nombre || '-'}</div><div>${getEstadoBadge(item.estado)}</div></button>`); });
  }

  function switchView(mode){ currentView=mode; const isTable=mode==='table'; $('#tableView').toggleClass('hidden',!isTable); $('#calendarView').toggleClass('hidden',isTable); $('#tableRangeControls').toggleClass('hidden', !isTable); $('#btnViewTable').toggleClass('bg-teal-600 text-white',isTable).toggleClass('text-gray-700',!isTable); $('#btnViewCalendar').toggleClass('bg-teal-600 text-white',!isTable).toggleClass('text-gray-700',isTable); if(isTable) loadTable(currentPage || 1); else loadCalendarRange(); }
  function setCalendarMode(mode){ calendarMode=mode; $('#btnModeMonth').toggleClass('bg-teal-600 text-white',mode==='month').toggleClass('text-gray-700',mode!=='month'); $('#btnModeWeek').toggleClass('bg-teal-600 text-white',mode==='week').toggleClass('text-gray-700',mode!=='week'); $('#btnModeDay').toggleClass('bg-teal-600 text-white',mode==='day').toggleClass('text-gray-700',mode!=='day'); loadCalendarRange(); }
  function openModal(item){ modalItemId=parseInt(item.id||0,10); const info=$('#modalInfo').empty(); const fecha=parseSqlDate(item.inicio), fin=item.fin?parseSqlDate(item.fin):null; const total=parseFloat(item.servicio_precio||0).toFixed(2); [['Cliente',item.cliente_nombre||'-'],['Servicio',item.servicio_nombre||'-'],['Detalle servicio',item.servicio_descripcion||'-'],['Duración',`${item.servicio_duracion_minutos || '-'} min`],['Total',`$${total}`],['Empleado',item.empleado_nombre||'-'],['Sucursal',item.sucursal_nombre||'-'],['Dirección sede',item.sucursal_direccion||'-'],['Fecha',fecha.toLocaleDateString()],['Hora',`${fecha.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}${fin?' - '+fin.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}):''}`]].forEach(r=>info.append(`<div class="bg-gray-50 border rounded-lg p-2"><div class="text-xs text-gray-500">${r[0]}</div><div class="font-medium text-gray-800">${r[1]}</div></div>`)); $('#mEstado').val(item.estado||'pendiente').prop('disabled',true).removeClass('bg-white').addClass('bg-gray-50'); $('#mNotas').val(item.notas||'').prop('disabled',true).removeClass('bg-white').addClass('bg-gray-50'); $('#btnSaveModal').addClass('hidden'); $('#btnEditModal').removeClass('hidden'); $('#citaModal').removeClass('hidden').addClass('flex'); if(window.lucide) lucide.createIcons(); }
  function viewDetails(id){ const local=byId[id]; if(local){ openModal(local); return; } $.get(API_URL,{action:'get',id},res=>{ if(res&&res.success&&res.data) openModal(res.data); },'json'); }
  function closeModal(){ $('#citaModal').addClass('hidden').removeClass('flex'); }
  function saveModal(){ if(modalItemId<=0) return; $.post(API_URL,{action:'update_status',id:modalItemId,estado:$('#mEstado').val(),notas:$('#mNotas').val()},function(res){ if(res&&res.success){ showCustomAlert('Cita actualizada.',3000,'success'); closeModal(); loadTable(currentPage); if(currentView==='calendar') loadCalendarRange(); } else showCustomAlert((res&&res.message)||'No se pudo actualizar.',5000,'error'); },'json'); }

  $(function(){
    loadTable();
    switchView('calendar');
    setCalendarMode('day');
    $('#selLimit,#filterStatus').on('change',()=>loadTable(1));
    $('#txtSearch').on('keyup',e=>{ if(e.key==='Enter') loadTable(1); });
    $('#btnViewTable').on('click',()=>switchView('table'));
    $('#btnViewCalendar').on('click',()=>switchView('calendar'));
    $('#calendarGrid').on('click','.day-cell',function(){ renderDayAgenda($(this).data('date')); });
    $('#btnPrevRange').on('click',()=>{ if(calendarMode==='month') cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth()-1,1); else if(calendarMode==='week') cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()-7); else cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()-1); loadCalendarRange(); });
    $('#btnNextRange').on('click',()=>{ if(calendarMode==='month') cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth()+1,1); else if(calendarMode==='week') cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()+7); else cursorDate=new Date(cursorDate.getFullYear(),cursorDate.getMonth(),cursorDate.getDate()+1); loadCalendarRange(); });
    $('#btnToday').on('click',()=>{ const now=new Date(); cursorDate=new Date(now.getFullYear(),now.getMonth(),now.getDate()); selectedDate=toDateKey(now); loadCalendarRange(); });
    $('#btnModeMonth').on('click',()=>setCalendarMode('month'));
    $('#btnModeWeek').on('click',()=>setCalendarMode('week'));
    $('#btnModeDay').on('click',()=>setCalendarMode('day'));
    $('#btnCloseModal').on('click',closeModal);
    $('#citaModal').on('click',function(e){ if(e.target===this) closeModal(); });
    $('#btnEditModal').on('click',function(){
      $('#mEstado,#mNotas').prop('disabled',false).removeClass('bg-gray-50').addClass('bg-white');
      $('#mNotas').val('').trigger('focus');
      $('#btnEditModal').addClass('hidden');
      $('#btnSaveModal').removeClass('hidden');
    });
    $('#btnSaveModal').on('click',saveModal);
    $('#btnTableDay').on('click',()=>{ tableRangeMode='day'; refreshTableRangeButtons(); loadTable(1); });
    $('#btnTableWeek').on('click',()=>{ tableRangeMode='week'; refreshTableRangeButtons(); loadTable(1); });
    $('#btnTableMonth').on('click',()=>{ tableRangeMode='month'; refreshTableRangeButtons(); loadTable(1); });
    $('#btnTableAll').on('click',()=>{ tableRangeMode='all'; refreshTableRangeButtons(); loadTable(1); });
    refreshTableRangeButtons();
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
