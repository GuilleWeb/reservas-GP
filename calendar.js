/**
 * Librería de Planificador de Eventos (PLC) - Versión Container-Responsive
 * CORREGIDO: Ahora respeta completamente las dimensiones del contenedor padre
 */
(function() {

    class PlanificadorCalendarioCore {
        constructor(selectorID, opcionesUsuario = {}) {
            this.plc_contenedorPrincipal = document.getElementById(selectorID);
            if (!this.plc_contenedorPrincipal) {
                console.error(`Error: El elemento con ID "${selectorID}" no fue encontrado.`);
                return;
            }

            const opcionesPorDefecto = {
                fechaInicial: new Date(),
                eventos: [],
                colorPrimario: '#10b981', 
                modoOscuro: true,
                vistaInicial: 'month',
                panelOcultoInicial: false, 
                alClicEnDia: null, 
                alClicEnEvento: (evento) => console.log('Evento clickeado:', evento),
                onNewEvent: (fecha) => console.log('Nuevo Evento para:', fecha),
            };
            
            this.plc_opciones = { 
                ...opcionesPorDefecto, 
                ...opcionesUsuario, 
                tipoVista: 'full', 
                eventos: opcionesUsuario.eventos || opcionesUsuario.eventosLista,
            };
            
            const fechaValida = this.plc_opciones.fechaInicial instanceof Date && !isNaN(this.plc_opciones.fechaInicial.getTime());
            this.plc_fechaActual = fechaValida ? this.plc_opciones.fechaInicial : new Date();
            this.plc_fechaSeleccionada = this.plc_formatoFecha(this.plc_fechaActual);
            this.plc_vistaActual = (this.plc_opciones.vistaInicial === 'month' || this.plc_opciones.vistaInicial === 'week') ? this.plc_opciones.vistaInicial : 'month'; 

            this.plc_listaEventos = (this.plc_opciones.eventos || [])
                .map(e => this.plc_normalizarEvento(e))
                .filter(e => e !== null);
                
            this.plc_panelOculto = this.plc_opciones.panelOcultoInicial; 

            this.plc_iniciarComponente();
        }
        
        // ************************************************
        // Métodos Auxiliares
        // ************************************************

        plc_formatoFecha(fecha) {
            try {
                if (!fecha || isNaN(new Date(fecha).getTime())) return ''; 
                const d = new Date(fecha);
                const anio = d.getFullYear();
                const mes = String(d.getMonth() + 1).padStart(2, '0');
                const dia = String(d.getDate()).padStart(2, '0');
                return `${anio}-${mes}-${dia}`;
            } catch (e) {
                return '';
            }
        }
        
        plc_normalizarEvento(evento) {
             let fechaISO = evento.date || '';
             if (!fechaISO) return null;

             const horaString = evento.time || "00:00";
             const fechaObjeto = new Date(`${fechaISO}T${horaString}`); 

             if (isNaN(fechaObjeto.getTime())) {
                 return null;
             }

             return {
                 ...evento,
                 date: fechaISO,
                 time: horaString.substring(0, 5),               
                 timestamp: fechaObjeto.getTime()    
             };
         }
        
        plc_obtenerEventosPorDia(fecha) {
            return this.plc_listaEventos
                .filter(evento => evento.date === fecha)
                .sort((a, b) => (a.timestamp || 0) - (b.timestamp || 0));
        }
        
        plc_obtenerInicioSemana(fecha) {
            const d = new Date(fecha);
            d.setHours(0, 0, 0, 0);
            const dia = d.getDay(); 
            d.setDate(d.getDate() - dia); 
            return d;
        }
        
        plc_hexATrailing(hex, alpha) {
            let color = hex;
            
            const isHex = /^#([0-9A-F]{3}){1,2}$/i.test(color);
            
            if (!isHex) {
                return `rgba(107, 114, 128, ${alpha})`; 
            }

            if (color.startsWith('#')) color = color.slice(1);
            if (color.length === 3) color = color.split('').map(c => c + c).join(''); 

            const r = parseInt(color.slice(0, 2), 16),
                  g = parseInt(color.slice(2, 4), 16),
                  b = parseInt(color.slice(4, 6), 16);
            
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        
        // ************************************************
        // Métodos de Inicialización y DOM
        // ************************************************

        plc_iniciarComponente() {
            this.plc_aplicarEstilosBase();
            this.plc_contenedorPrincipal.innerHTML = this.plc_generarMarkup();
            this.plc_obtenerReferenciasDOM();
            this.plc_adjuntarManejadoresDeEventos();
            this.plc_renderizarPlanificador();
        }

        plc_aplicarEstilosBase() {
            let estiloElemento = document.getElementById('plc_estilos_base');
            if (!estiloElemento) {
                estiloElemento = document.createElement('style');
                estiloElemento.id = 'plc_estilos_base';
                document.head.appendChild(estiloElemento);
            }
            
            const colorP = this.plc_opciones.colorPrimario;
            const colorP50 = this.plc_hexATrailing(colorP, 0.5); 
            const colorP40 = this.plc_hexATrailing(colorP, 0.4); 
            
            const bgPrincipal = this.plc_opciones.modoOscuro ? '#1f2937' : '#ffffff';
            const textoPrincipal = this.plc_opciones.modoOscuro ? '#f3f4f6' : '#111827';
            const bordeBase = this.plc_opciones.modoOscuro ? '#4b5563' : '#e5e7eb';
            const bgPanel = this.plc_opciones.modoOscuro ? '#374151' : '#f9fafb';
            const textoMuted = this.plc_opciones.modoOscuro ? '#9ca3af' : '#6b7280';
            
            
            estiloElemento.textContent = `
                /* CONTENEDOR PRINCIPAL - RESPETA DIMENSIONES EXTERNAS */
                #${this.plc_contenedorPrincipal.id} {
                    font-family: ui-sans-serif, system-ui, sans-serif;
                    border-radius: 0.5em;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    background-color: ${bgPrincipal};
                    color: ${textoPrincipal}; /* Color base para herencia */
                    padding: 1%;
                    overflow: hidden;
                    position: relative;
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                }

                /* CLASES PRINCIPALES */
                #${this.plc_contenedorPrincipal.id} .plc_color_primario { color: ${colorP} !important; }
                #${this.plc_contenedorPrincipal.id} .plc_bg_primario { 
                    background-color: ${colorP} !important; 
                    color: #fff !important; 
                }
                #${this.plc_contenedorPrincipal.id} .plc_borde_primario { border: 1px solid ${colorP} !important; }
                #${this.plc_contenedorPrincipal.id} .plc_borde_seleccion { 
                    border: 2px solid ${colorP} !important;
                    background-color: ${colorP50} !important;
                }
                #${this.plc_contenedorPrincipal.id} .plc_texto_muted { color: ${textoMuted} !important; }
                
                /* BOTONES - ESCALABLES */
                #${this.plc_contenedorPrincipal.id} button {
                    border: none;
                    background-color: transparent;
                    cursor: pointer;
                    padding: 0.5%;
                    border-radius: 0.3em;
                    transition: background-color 150ms;
                    color: ${textoPrincipal}; /* Asegura que los botones hereden el color */
                    font-size: max(0.6em, 8px);
                }
                
                /* GRID CABECERA */
                #${this.plc_contenedorPrincipal.id} .plc_grid_cabecera { 
                    display: grid; 
                    grid-template-columns: repeat(7, 1fr); 
                    text-align: center; 
                    font-weight: 600; 
                    padding-bottom: 1%;
                    border-bottom: 1px solid ${bordeBase}; 
                    font-size: max(0.65em, 8px);
                }
                
                /* GRIDS DE CALENDARIO - ALTURA CONTROLADA */
                #${this.plc_contenedorPrincipal.id} .plc_grid_calendario { 
                    display: grid; 
                    grid-template-columns: repeat(7, 1fr); 
                    gap: 1px;
                    height: 100%;
                    grid-template-rows: repeat(6, 1fr);
                }
                
                #${this.plc_contenedorPrincipal.id} .plc_grid_semana { 
                    display: grid; 
                    grid-template-columns: repeat(7, 1fr); 
                    gap: 0px;
                    height: 100%;
                    grid-template-rows: 1fr;
                }

                /* CELDAS DE DÍA - CONTENCIÓN ESTRICTA */
                #${this.plc_contenedorPrincipal.id} .plc_dia_celda { 
                    cursor: pointer; 
                    transition: background-color 150ms; 
                    position: relative; 
                    border-radius: 0.2em;
                    padding: 1%;
                    text-align: center; 
                    font-size: max(0.7em, 12px);
                    height: 100%;
                    width: 100%;
                    min-height: 0;
                    min-width: 0;
                    max-height: 100%;
                    max-width: 100%;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: flex-start;
                    box-sizing: border-box;
                }
                
                /* VISTA SEMANAL ESPECÍFICA */
                #${this.plc_contenedorPrincipal.id} .plc_grid_semana .plc_dia_celda { 
                    text-align: left; 
                    padding: 1%;
                    border-radius: 0;
                    border-right: 1px solid ${bordeBase};
                    align-items: flex-start;
                }
                
                #${this.plc_contenedorPrincipal.id} .plc_grid_semana .plc_dia_celda:last-child {
                     border-right: none;
                }
                
                /* NÚMERO DE DÍA */
                #${this.plc_contenedorPrincipal.id} .plc_dia_numero {
                    font-size: max(0.7em, 8px);
                    font-weight: 600;
                    flex-shrink: 0;
                    line-height: 1;
                }
                
                /* INDICADORES DE EVENTOS */
                #${this.plc_contenedorPrincipal.id} .plc_bg_eventos { 
                    background-color: ${colorP50}; 
                    color: ${textoPrincipal}; 
                }
                #${this.plc_contenedorPrincipal.id} .plc_bg_eventos_semana { 
                    background-color: ${colorP40} !important; 
                }

                /* PANEL LATERAL - RESPONSIVE */
                #${this.plc_contenedorPrincipal.id} .plc_vista_completa_grid { 
                    display: grid; 
                    grid-template-columns: 1fr 0.4fr;
                    gap: 1%;
                    transition: grid-template-columns 300ms ease-out; 
                    height: 100%;
                    width: 100%;
                }
                
                #${this.plc_contenedorPrincipal.id} .plc_vista_completa_grid.plc_panel_oculto {
                    grid-template-columns: 1fr 0;
                }
                
                #${this.plc_contenedorPrincipal.id} #plc_panel_eventos { 
                    background-color: ${bgPanel}; 
                    border: 1px solid ${bordeBase}; 
                    padding: 2%;
                    border-radius: 0.5em;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                }
                
                #${this.plc_contenedorPrincipal.id} #plc_panel_lista_eventos {
                    flex: 1;
                    overflow-y: auto; 
                    padding-right: 1%;
                }
                
                /* TARJETAS DE EVENTOS */
                #${this.plc_contenedorPrincipal.id} .plc_tarjeta_evento {
                    padding: 2%;
                    font-size: max(0.7em, 8px);
                    background-color: ${bgPrincipal};
                    border-radius: 0.4em;
                    margin-bottom: 2%;
                    cursor: pointer;
                    border: 1px solid ${bordeBase};
                }
                
                #${this.plc_contenedorPrincipal.id} .plc_tarjeta_evento:hover {
                    background-color: ${colorP50};
                }

                /* SCROLLBAR PERSONALIZADA */
                #${this.plc_contenedorPrincipal.id} ::-webkit-scrollbar {
                    width: 4px;
                }
                #${this.plc_contenedorPrincipal.id} ::-webkit-scrollbar-track {
                    background: ${bgPanel};
                }
                #${this.plc_contenedorPrincipal.id} ::-webkit-scrollbar-thumb {
                    background: ${bordeBase};
                    border-radius: 2px;
                }

                /* RESPONSIVE BREAKPOINTS */
                @container (max-width: 300px) {
                    #${this.plc_contenedorPrincipal.id} .plc_vista_completa_grid {
                        grid-template-columns: 1fr;
                        grid-template-rows: 1fr auto;
                    }
                    #${this.plc_contenedorPrincipal.id} #plc_panel_eventos {
                        max-height: 30%;
                    }
                }
            `;
        }
        
        plc_generarMarkup() {
            const textoMuted = this.plc_opciones.modoOscuro ? 'text-gray-300' : 'text-gray-700';
            const textoPrincipal = this.plc_opciones.modoOscuro ? '#f3f4f6' : '#111827'; // Obtenido para inyección directa

            let html = '<div style="height: 100%; width: 100%; display: flex; flex-direction: column; box-sizing: border-box;">';
            
            // Año - Tamaño relativo
            html += `<div style="text-align: center; padding: 1% 0; flex-shrink: 0;">
                <h2 id="plc_display_anio" style="color: ${textoPrincipal}; font-size: max(0.85em, 10px); font-weight: 800; text-align: center; flex: 1; margin: 0;">${this.plc_fechaActual.getFullYear()}</h2>
            </div>`;
            
            html += `<div style="padding: 1%; flex: 1; display: flex; flex-direction: column; overflow: hidden; box-sizing: border-box; min-height: 0;">`;
            
            html += `<div id="plc_vista_completa_grid" class="plc_vista_completa_grid ${this.plc_panelOculto ? 'plc_panel_oculto' : ''}" style="flex: 1; min-height: 0;">`;

            // Contenedor del Calendario
            html += `<div style="display: flex; flex-direction: column; height: 100%; overflow: hidden; min-height: 0;">`;
            
            // Controles de navegación
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1%; flex-shrink: 0;">
                    <button id="plc_btn_prev" style="font-size: max(1em, 12px); padding: 1%;">&lt;</button>
                    <h1 id="plc_display_periodo" style="color: ${textoPrincipal}; font-size: max(0.85em, 10px); font-weight: 500; text-align: center; flex: 1; margin: 0;"></h1>
                    <button id="plc_btn_next" style="font-size: max(1em, 12px); padding: 1%;">&gt;</button>
                </div>
            `;
            
            html += `
                <div id="plc_cabecera_dias" class="plc_grid_cabecera" style="flex-shrink: 0;">
                    <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
                </div>
            `;
            
            html += `<div id="plc_grilla_principal" style="flex: 1; overflow: hidden; min-height: 0;"></div>`;
            html += `</div>`;

            // Panel de Eventos
            html += `
                <div id="plc_panel_eventos" style="min-height: 0;">
                    <div style="text-align: right; margin-bottom: 2%;">
                        <button id="plc_btn_cerrar_panel" style="background: none; border: none; font-size: max(1.2em, 14px); cursor: pointer; color: ${textoMuted}; padding: 0;">&times;</button>
                    </div>

                    <div style="display: flex; gap: 1%; margin-bottom: 2%; flex-shrink: 0;">
                        <button data-view="month" class="plc_toggle_vista" style="flex: 1; padding: 1%;">Mes</button>
                        <button data-view="week" class="plc_toggle_vista" style="flex: 1; padding: 1%;">Semana</button>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2%; flex-shrink: 0;">
                        <h2 id="plc_panel_titulo_fecha" style="font-size: max(0.85em, 10px); font-weight: bold; margin: 0; flex: 1;">Seleccione un día</h2>
                        <button id="plc_btn_new_event_panel" class="plc_bg_primario" style="padding: 1% 2%; border-radius: 0.3em; font-size: max(0.7em, 8px); font-weight: 500; white-space: nowrap;">+ Nuevo</button>
                    </div>

                    <ul id="plc_panel_lista_eventos" style="list-style: none; padding: 0; margin: 0;"></ul>
                </div>
            `;

            html += `</div>`;
            html += `</div>`;
            html += `</div>`;
            
            return html;
        }

        plc_obtenerReferenciasDOM() {
            this.plc_grillaPrincipal = this.plc_contenedorPrincipal.querySelector('#plc_grilla_principal');
            this.plc_displayPeriodo = this.plc_contenedorPrincipal.querySelector('#plc_display_periodo');
            this.plc_displayAnio = this.plc_contenedorPrincipal.querySelector('#plc_display_anio');
            this.plc_cabeceraDias = this.plc_contenedorPrincipal.querySelector('#plc_cabecera_dias');
            this.plc_vistaCompletaGrid = this.plc_contenedorPrincipal.querySelector('#plc_vista_completa_grid');

            this.plc_panelEventos = this.plc_contenedorPrincipal.querySelector('#plc_panel_eventos');
            this.plc_panelTitulo = this.plc_panelEventos ? this.plc_panelEventos.querySelector('#plc_panel_titulo_fecha') : null;
            this.plc_panelListaEventos = this.plc_panelEventos ? this.plc_panelEventos.querySelector('#plc_panel_lista_eventos') : null;
            this.plc_btnNewEventPanel = this.plc_contenedorPrincipal.querySelector('#plc_btn_new_event_panel');
        }

        plc_adjuntarManejadoresDeEventos() {
            const prevBtn = this.plc_contenedorPrincipal.querySelector('#plc_btn_prev');
            const nextBtn = this.plc_contenedorPrincipal.querySelector('#plc_btn_next');
            if (prevBtn) prevBtn.addEventListener('click', () => this.plc_cambiarPeriodo(-1));
            if (nextBtn) nextBtn.addEventListener('click', () => this.plc_cambiarPeriodo(1));
            
            this.plc_contenedorPrincipal.querySelectorAll('.plc_toggle_vista').forEach(btn => {
                btn.addEventListener('click', (e) => this.plc_establecerVista(e.target.dataset.view));
            });
            
            if (this.plc_btnNewEventPanel && this.plc_opciones.onNewEvent) {
                this.plc_btnNewEventPanel.addEventListener('click', () => this.plc_opciones.onNewEvent(this.plc_fechaSeleccionada));
            }
            
            const cerrarPanelBtn = this.plc_contenedorPrincipal.querySelector('#plc_btn_cerrar_panel');
            if (cerrarPanelBtn) {
                 cerrarPanelBtn.addEventListener('click', () => this.plc_togglePanelLateral(true));
            }
        }

        plc_togglePanelLateral(forzarOcultar) {
            if (forzarOcultar !== undefined) {
                 this.plc_panelOculto = forzarOcultar;
            } else {
                 this.plc_panelOculto = !this.plc_panelOculto;
            }

            if (this.plc_vistaCompletaGrid) {
                if (this.plc_panelOculto) {
                    this.plc_vistaCompletaGrid.classList.add('plc_panel_oculto');
                } else {
                    this.plc_vistaCompletaGrid.classList.remove('plc_panel_oculto');
                    this.plc_actualizarPanelEventos(this.plc_fechaSeleccionada);
                }
            }
        }
        
        plc_cambiarPeriodo(delta) {
            const nuevaFecha = new Date(this.plc_fechaActual.getFullYear(), this.plc_fechaActual.getMonth(), this.plc_fechaActual.getDate()); 

            if (this.plc_vistaActual === 'month') {
                nuevaFecha.setDate(1); 
                nuevaFecha.setMonth(nuevaFecha.getMonth() + delta);
                
                const diaDeseado = Math.min(this.plc_fechaActual.getDate(), new Date(nuevaFecha.getFullYear(), nuevaFecha.getMonth() + 1, 0).getDate());
                nuevaFecha.setDate(diaDeseado); 

            } else if (this.plc_vistaActual === 'week') {
                nuevaFecha.setDate(this.plc_fechaActual.getDate() + (delta * 7));
            }
            
            this.plc_fechaActual = nuevaFecha;
            this.plc_fechaSeleccionada = this.plc_formatoFecha(this.plc_fechaActual); 

            this.plc_renderizarPlanificador();
        }

        plc_establecerVista(vista) {
            this.plc_vistaActual = vista;
            this.plc_fechaSeleccionada = this.plc_formatoFecha(this.plc_fechaActual); 
            this.plc_renderizarPlanificador();
        }
        
        plc_manejarClicEnDiaInterno(fechaString, eventos) {
            this.plc_fechaSeleccionada = fechaString; 
            
            const partesFecha = fechaString.split('-'); 
            if (partesFecha.length === 3) {
                 this.plc_fechaActual = new Date(parseInt(partesFecha[0]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[2]));
            }
            
            if (this.plc_panelOculto) {
                this.plc_togglePanelLateral(false); 
            }
            
            if (typeof this.plc_opciones.alClicEnDia === 'function') {
                this.plc_opciones.alClicEnDia(fechaString, eventos);
            }

            this.plc_actualizarPanelEventos(fechaString);
            
            if (this.plc_vistaActual === 'month') {
                this.plc_renderizarVistaMes();
            } else if (this.plc_vistaActual === 'week') {
                this.plc_renderizarVistaSemana();
            }
        }
        
        plc_renderizarPlanificador() {
            if (!this.plc_grillaPrincipal) return; 
            
            this.plc_grillaPrincipal.innerHTML = ''; 
            
            if (this.plc_displayAnio) {
                this.plc_displayAnio.textContent = this.plc_fechaActual.getFullYear();
            }

            this.plc_contenedorPrincipal.querySelectorAll('.plc_toggle_vista').forEach(btn => {
               btn.classList.remove('plc_borde_seleccion'); 
               if (btn.dataset.view === this.plc_vistaActual) {
                   btn.classList.add('plc_borde_seleccion'); 
               }
           });
            
            try {
                 let textoPeriodo = '';
                 if (this.plc_vistaActual === 'month') {
                    textoPeriodo = this.plc_fechaActual.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
                 } else if (this.plc_vistaActual === 'week') {
                    const inicioSemana = this.plc_obtenerInicioSemana(this.plc_fechaActual);
                    const finSemana = new Date(inicioSemana);
                    finSemana.setDate(finSemana.getDate() + 6);
                    const formatoFecha = { day: 'numeric', month: 'short' };
                    
                    textoPeriodo = `${inicioSemana.toLocaleDateString('es-ES', formatoFecha)} - ${finSemana.toLocaleDateString('es-ES', { ...formatoFecha, year: 'numeric' })}`;
                 }
                 this.plc_displayPeriodo.textContent = textoPeriodo.toUpperCase(); 
            } catch (e) {
                this.plc_displayPeriodo.textContent = 'ERROR EN FECHA';
            }

            this.plc_cabeceraDias.style.display = (this.plc_vistaActual === 'month' || this.plc_vistaActual === 'week') ? 'grid' : 'none';

            if (this.plc_vistaActual === 'month') {
                this.plc_renderizarVistaMes();
            } else if (this.plc_vistaActual === 'week') {
                this.plc_renderizarVistaSemana();
            }
            
            this.plc_actualizarPanelEventos(this.plc_fechaSeleccionada);
            this.plc_togglePanelLateral(this.plc_panelOculto); 
        }

        plc_renderizarVistaMes() {
            this.plc_grillaPrincipal.className = 'plc_grid_calendario'; 
            this.plc_grillaPrincipal.innerHTML = ''; 
            
            const mes = this.plc_fechaActual.getMonth();
            const anio = this.plc_fechaActual.getFullYear();
            const diasEnMes = new Date(anio, mes + 1, 0).getDate();
            const primerDia = new Date(anio, mes, 1).getDay(); 
            const hoyString = this.plc_formatoFecha(new Date());
            
            // Días de relleno
            for (let i = 0; i < primerDia; i++) {
                this.plc_grillaPrincipal.appendChild(document.createElement('div'));
            }
            
            // Días del mes actual
            for (let dia = 1; dia <= diasEnMes; dia++) { 
                const celdaDia = document.createElement('div');
                const fechaActual = new Date(anio, mes, dia);
                const fechaFormateada = this.plc_formatoFecha(fechaActual);
                const eventosDia = this.plc_obtenerEventosPorDia(fechaFormateada);
                
                const esHoy = fechaFormateada === hoyString;
                const esSeleccionado = fechaFormateada === this.plc_fechaSeleccionada;
                
                let clases = 'plc_dia_celda';
                
                if (esSeleccionado) {
                    clases += ' plc_bg_primario';
                } else if (esHoy) {
                    clases += ' plc_borde_primario'; 
                } 
                
                if (eventosDia.length > 0 && !esSeleccionado) {
                    clases += ' plc_bg_eventos'; 
                }

                celdaDia.className = clases;
                
                const numeroDia = document.createElement('span');
                numeroDia.className = 'plc_dia_numero';
                numeroDia.textContent = dia;
                celdaDia.appendChild(numeroDia);
                
                // Indicador de eventos
                if (eventosDia.length > 0) {
                     const indicador = document.createElement('span');
                     indicador.style.cssText = 'font-size: max(0.5em, 6px); color: #fff; background-color: #ef4444; border-radius: 9999px; padding: 0px 2px; margin-top: 1px; white-space: nowrap;';
                     indicador.textContent = `${eventosDia.length}`;
                     celdaDia.appendChild(indicador);
                }

                celdaDia.addEventListener('click', () => this.plc_manejarClicEnDiaInterno(fechaFormateada, eventosDia));
                
                this.plc_grillaPrincipal.appendChild(celdaDia);
            }
        }
        
        plc_renderizarVistaSemana() {
            this.plc_grillaPrincipal.className = 'plc_grid_semana'; 
            this.plc_grillaPrincipal.innerHTML = ''; 

            const inicioSemana = this.plc_obtenerInicioSemana(this.plc_fechaActual);
            const hoyString = this.plc_formatoFecha(new Date());

            for (let i = 0; i < 7; i++) {
                const fechaActual = new Date(inicioSemana);
                fechaActual.setDate(inicioSemana.getDate() + i);
                
                const fechaFormateada = this.plc_formatoFecha(fechaActual);
                const eventosDia = this.plc_obtenerEventosPorDia(fechaFormateada);
                
                const esHoy = fechaFormateada === hoyString;
                const esSeleccionado = fechaFormateada === this.plc_fechaSeleccionada;
                
                const celdaDia = document.createElement('div');
                
                let clases = 'plc_dia_celda';
                
                if (esSeleccionado) {
                    clases += ' plc_bg_primario';
                }
                else if (esHoy) {
                    clases += ' plc_borde_primario'; 
                } 
                
                if (eventosDia.length > 0 && !esSeleccionado) {
                    clases += ' plc_bg_eventos_semana'; 
                }

                celdaDia.className = clases;
                
                const diaNumero = fechaActual.getDate();
                const textoMuted = this.plc_opciones.modoOscuro ? '#9ca3af' : '#6b7280';
                
                // Contenido de eventos - limitado
                let contenidoEventos = '';
                if (eventosDia.length > 0) {
                    contenidoEventos = `<ul style="list-style: none; padding: 0; margin-top: 1%; font-size: max(0.55em, 6px); line-height: 1.2; width: 100%;">`;
                    eventosDia.slice(0, 2).forEach(e => { 
                         const colorPunto = e.category === 'work' ? '#f59e0b' : e.category === 'personal' ? '#3b82f6' : '#ef4444';
                         contenidoEventos += `<li style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: flex-start; margin-bottom: 1%;">
                             <span style="display: inline-block; width: 3px; height: 3px; background-color: ${colorPunto}; border-radius: 50%; margin-right: 2px; margin-top: 2px; flex-shrink: 0;"></span>
                             <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${e.time || '00:00'} ${e.description}</span>
                         </li>`;
                    });
                    if (eventosDia.length > 2) {
                         contenidoEventos += `<li style="color: ${textoMuted}; white-space: nowrap;">+${eventosDia.length - 2}</li>`;
                    }
                    contenidoEventos += `</ul>`;
                }

                celdaDia.innerHTML = `
                    <div class="plc_dia_numero" style="margin-bottom: 1%;">${diaNumero}</div>
                    <div style="font-size: max(0.6em, 6px); color: ${esSeleccionado ? '#fff' : textoMuted}; flex-shrink: 0;">
                        ${eventosDia.length} ev.
                    </div>
                    <div style="flex: 1; overflow: hidden; width: 100%;">
                        ${contenidoEventos}
                    </div>
                `;
                
                celdaDia.addEventListener('click', () => this.plc_manejarClicEnDiaInterno(fechaFormateada, eventosDia));
                this.plc_grillaPrincipal.appendChild(celdaDia);
            }
        }

        plc_actualizarPanelEventos(fechaString) {
            if (!this.plc_panelTitulo || !this.plc_panelListaEventos) return; 

            this.plc_fechaSeleccionada = fechaString;
            const eventos = this.plc_obtenerEventosPorDia(fechaString);
            
            let fechaDisplay = 'Día no seleccionado';
            try {
                const fechaObjeto = new Date(fechaString + 'T00:00:00');
                if (!isNaN(fechaObjeto.getTime())) {
                   fechaDisplay = fechaObjeto.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
                } else {
                    fechaDisplay = 'Día Inválido';
                }
            } catch (e) {
                fechaDisplay = 'Día Inválido';
            }
            
            this.plc_panelTitulo.textContent = fechaDisplay.charAt(0).toUpperCase() + fechaDisplay.slice(1);
            this.plc_panelListaEventos.innerHTML = '';
            
            const textoMuted = this.plc_opciones.modoOscuro ? '#9ca3af' : '#6b7280';
            const bgPrincipal = this.plc_opciones.modoOscuro ? '#1f2937' : '#ffffff';
            
            if (eventos.length === 0) {
                this.plc_panelListaEventos.innerHTML = `
                    <li style="padding: 2%; text-align: center; color: ${textoMuted}; border: 1px dashed ${textoMuted}; border-radius: 0.5em; font-size: max(0.7em, 8px);">
                        <p style="margin: 0;">No hay eventos.</p>
                    </li>
                `;
            } else {
                eventos.forEach(evento => {
                    const listItem = document.createElement('li');
                    
                    const colorPunto = evento.category === 'work' ? '#f59e0b' : evento.category === 'personal' ? '#3b82f6' : '#ef4444';
                                     
                    listItem.className = 'plc_tarjeta_evento';
                    listItem.style.cssText += `border-left: 3px solid ${colorPunto}; background-color: ${bgPrincipal};`;

                    listItem.innerHTML = `
                        <div style="flex-grow: 1;">
                            <p style="font-weight: 600; font-size: max(0.75em, 9px); margin: 0 0 1% 0;">${evento.description}</p>
                            <p style="font-size: max(0.65em, 8px); color: ${textoMuted}; margin: 1% 0;">${evento.time || ''}</p>
                            ${evento.details ? `<p style="font-size: max(0.6em, 7px); color: ${textoMuted}; margin: 1% 0;">${evento.details}</p>` : ''}
                        </div>
                    `;
                    listItem.addEventListener('click', () => this.plc_opciones.alClicEnEvento(evento));
                    this.plc_panelListaEventos.appendChild(listItem);
                });
            }
        }
    }

    // Función expuesta globalmente
    window.inicializarPlanificador = function(config) {
        if (!config || !config.node) {
            return null;
        }
        
        const { node, ...opciones } = config;
        
        return new PlanificadorCalendarioCore(node, opciones);
    };

})();