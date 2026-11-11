/**
 * Panel /admin — comportamiento (JS vanilla, sin dependencias).
 *
 * - Confirmación antes de eliminar o restaurar.
 * - Listas: mostrar/ocultar, flechas y arrastrar y soltar (ratón y pantallas táctiles), guardado al momento.
 * - Formularios: subida de imágenes/videos con progreso, fotos ordenables, editor de texto
 *   del blog, vista previa en vivo y aviso si sales sin guardar.
 */
(function () {
    'use strict';

    const $ = (sel, raiz = document) => raiz.querySelector(sel);
    const $$ = (sel, raiz = document) => Array.from(raiz.querySelectorAll(sel));
    const csrf = () => ($('input[name="csrf"]') || {}).value || '';

    // --- Aviso flotante ------------------------------------------------------------
    let temporizadorToast;
    function toast(texto, error = false) {
        const t = $('[data-toast]');
        if (!t) return;
        t.textContent = texto;
        t.classList.toggle('toast--error', error);
        t.hidden = false;
        clearTimeout(temporizadorToast);
        temporizadorToast = setTimeout(() => { t.hidden = true; }, error ? 6000 : 2500);
    }

    // --- Peticiones al servidor ------------------------------------------------------
    /** Envía un FormData a accion.php. Devuelve la respuesta JSON; lanza Error con el mensaje. */
    function enviar(datos, alProgresar) {
        return new Promise((resolver, rechazar) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/accion.php');
            xhr.setRequestHeader('X-Requested-With', 'fetch');
            xhr.setRequestHeader('X-CSRF', csrf());
            xhr.responseType = 'json';
            if (alProgresar) {
                xhr.upload.onprogress = (ev) => { if (ev.lengthComputable) alProgresar(ev.loaded / ev.total); };
            }
            xhr.onload = () => {
                const r = xhr.response;
                if (r && r.ok) return resolver(r);
                if (xhr.status === 401) { window.location.reload(); return; }
                rechazar(new Error((r && r.error) || 'No se pudo completar. Revisa tu conexión.'));
            };
            xhr.onerror = () => rechazar(new Error('Sin conexión con el servidor. Intenta de nuevo.'));
            xhr.send(datos);
        });
    }

    function datosDe(form, extra = {}) {
        const fd = new FormData(form);
        Object.entries(extra).forEach(([k, v]) => {
            if (Array.isArray(v)) { fd.delete(k); v.forEach((x) => fd.append(k, x)); } else fd.set(k, v);
        });
        return fd;
    }

    /** Tras cada cambio la versión del archivo cambia: se actualiza en todos los formularios de la lista. */
    function nuevaVersion(version, lista) {
        if (version === undefined || !lista) return;
        $$('[data-version]', lista).forEach((i) => { i.value = version; });
    }

    // --- Confirmaciones (modal propio) ------------------------------------------------------
    // Los formularios con data-confirmar piden confirmación antes de enviarse. Opcionales:
    // data-confirmar-titulo, -nombre, -imagen, -nota, -boton y -tipo ("peligro" por defecto o "aviso").
    const ICONOS = {
        peligro: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2zM6 9h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 9zm4 2v8h1.5v-8H10zm3.5 0v8H15v-8h-1.5z"/></svg>',
        aviso: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a8 8 0 1 1-7.75 10h2.07A6 6 0 1 0 8 7.76L10 10H4V4l2.35 2.35A7.97 7.97 0 0 1 12 4zm-1 4h2v4.6l3 1.8-1 1.7-4-2.4V8z"/></svg>',
    };
    let modal;
    function crearModal() {
        modal = document.createElement('dialog');
        modal.className = 'modal';
        modal.setAttribute('aria-labelledby', 'modal-titulo');
        modal.setAttribute('aria-describedby', 'modal-cuerpo');
        modal.innerHTML = `
            <form method="dialog" class="modal__caja">
                <button class="modal__cerrar" value="no" aria-label="Cancelar">×</button>
                <div class="modal__encabezado">
                    <span class="modal__icono" data-modal-icono></span>
                    <h2 class="modal__titulo" id="modal-titulo" data-modal-titulo></h2>
                </div>
                <div class="modal__cuerpo" id="modal-cuerpo">
                    <img class="modal__imagen" alt="" width="96" height="72" data-modal-imagen hidden>
                    <div>
                        <p class="modal__nombre" data-modal-nombre></p>
                        <p class="modal__nota" data-modal-nota></p>
                    </div>
                </div>
                <div class="modal__acciones">
                    <button class="boton boton--secundario" value="no" data-modal-cancelar>Cancelar</button>
                    <button class="boton" value="si" data-modal-aceptar></button>
                </div>
            </form>`;
        document.body.append(modal);
        // Clic fuera de la caja = cancelar
        modal.addEventListener('click', (ev) => { if (ev.target === modal) modal.close('no'); });
    }
    function preguntar(form) {
        if (!modal) crearModal();
        const d = form.dataset;
        const tipo = d.confirmarTipo === 'aviso' ? 'aviso' : 'peligro';
        modal.dataset.tipo = tipo;
        $('[data-modal-icono]', modal).innerHTML = ICONOS[tipo];
        $('[data-modal-titulo]', modal).textContent = d.confirmarTitulo || d.confirmar;
        $('[data-modal-nombre]', modal).textContent = d.confirmarNombre ? `«${d.confirmarNombre}»` : '';
        $('[data-modal-nombre]', modal).hidden = !d.confirmarNombre;
        $('[data-modal-nota]', modal).textContent = d.confirmarNota || (d.confirmarTitulo ? d.confirmar : '');
        const img = $('[data-modal-imagen]', modal);
        img.hidden = !d.confirmarImagen;
        if (d.confirmarImagen) img.src = d.confirmarImagen; else img.removeAttribute('src');
        const aceptar = $('[data-modal-aceptar]', modal);
        aceptar.textContent = d.confirmarBoton || 'Aceptar';
        aceptar.className = 'boton ' + (tipo === 'peligro' ? 'boton--peligro-lleno' : '');
        return new Promise((resolver) => {
            modal.returnValue = '';
            modal.addEventListener('close', () => resolver(modal.returnValue === 'si'), { once: true });
            modal.showModal();
            $('[data-modal-cancelar]', modal).focus();   // lo seguro primero: Enter no borra por accidente
        });
    }
    document.addEventListener('submit', async (ev) => {
        const form = ev.target;
        if (!form.dataset.confirmar) return;
        if (form.dataset.confirmado) { delete form.dataset.confirmado; return; }
        ev.preventDefault();
        ev.stopImmediatePropagation();
        const quien = ev.submitter;
        if (typeof HTMLDialogElement !== 'function') {   // navegadores muy viejos
            if (window.confirm(form.dataset.confirmar)) { form.dataset.confirmado = '1'; form.requestSubmit(quien); }
            return;
        }
        if (await preguntar(form)) {
            form.dataset.confirmado = '1';
            form.requestSubmit(quien);
        }
    }, true);

    // --- Arrastrar y soltar (genérico) ----------------------------------------------------
    /**
     * Hace ordenable un contenedor. Los hijos con [data-item] se arrastran desde
     * `asa` (selector) o desde todo el elemento si no hay asa. Al soltar llama a alTerminar().
     */
    function ordenable(contenedor, { asa, alTerminar }) {
        let arrastrado = null;
        let ordenInicial = '';

        contenedor.addEventListener('pointerdown', (ev) => {
            const item = ev.target.closest('[data-item]');
            if (!item || !contenedor.contains(item) || ev.button !== 0) return;
            if (asa ? !ev.target.closest(asa) : ev.target.closest('button, a, input, label')) return;
            ev.preventDefault();
            arrastrado = item;
            ordenInicial = ids();
            item.classList.add('arrastrando');
            try { item.setPointerCapture(ev.pointerId); } catch (e) { /* sin captura también funciona */ }
        });

        contenedor.addEventListener('pointermove', (ev) => {
            if (!arrastrado) return;
            const bajo = document.elementFromPoint(ev.clientX, ev.clientY);
            const destino = bajo && bajo.closest('[data-item]');
            if (!destino || destino === arrastrado || destino.parentNode !== contenedor) return;
            const hermanos = $$(':scope > [data-item]', contenedor);
            const antes = hermanos.indexOf(arrastrado) > hermanos.indexOf(destino);
            contenedor.insertBefore(arrastrado, antes ? destino : destino.nextSibling);
        });

        const soltar = () => {
            if (!arrastrado) return;
            arrastrado.classList.remove('arrastrando');
            arrastrado = null;
            if (ids() !== ordenInicial) alTerminar();
        };
        contenedor.addEventListener('pointerup', soltar);
        contenedor.addEventListener('pointercancel', soltar);

        function ids() {
            return $$(':scope > [data-item]', contenedor).map((el) => el.dataset.id || el.querySelector('img')?.src).join('|');
        }
    }

    // =============================================================================
    // Lista de una sección
    // =============================================================================
    $$('ol.lista, ul.lista').forEach((lista) => {
        const seccion = lista.dataset.seccion;

        // Mostrar / ocultar
        $$('form[data-accion="activar"]', lista).forEach((form) => {
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const boton = $('button', form);
                boton.disabled = true;
                try {
                    const r = await enviar(datosDe(form));
                    boton.setAttribute('aria-checked', r.activo ? 'true' : 'false');
                    $('[data-estado-texto]', boton).textContent = r.activo ? 'Visible' : 'Oculto';
                    form.closest('[data-item]').classList.toggle('lista__item--oculto', !r.activo);
                    if (seccion) nuevaVersion(r.version, lista);
                    else nuevaVersion(r.version, form.closest('[data-item]'));
                    toast(r.mensaje);
                } catch (e) {
                    toast(e.message, true);
                } finally {
                    boton.disabled = false;
                }
            });
        });

        if (!seccion) return;   // el blog no se reordena

        async function guardarOrden() {
            const ids = $$(':scope > [data-item]', lista).map((li) => li.dataset.id);
            const fd = new FormData();
            fd.set('accion', 'ordenar');
            fd.set('s', seccion);
            fd.set('version', $('[data-version]', lista).value);
            ids.forEach((id) => fd.append('ids[]', id));
            try {
                const r = await enviar(fd);
                nuevaVersion(r.version, lista);
                toast(r.mensaje);
            } catch (e) {
                toast(e.message + ' Recargando…', true);
                setTimeout(() => window.location.reload(), 2500);
            }
        }

        // Flechas ▲▼
        lista.addEventListener('click', (ev) => {
            const boton = ev.target.closest('[data-mover-item]');
            if (!boton) return;
            ev.preventDefault();
            const item = boton.closest('[data-item]');
            const dir = Number(boton.dataset.moverItem);
            const vecino = dir < 0 ? item.previousElementSibling : item.nextElementSibling;
            if (!vecino) return;
            lista.insertBefore(item, dir < 0 ? vecino : vecino.nextSibling);
            boton.focus();
            guardarOrden();
        });

        ordenable(lista, { asa: '[data-asa]', alTerminar: guardarOrden });
    });

    // =============================================================================
    // Formulario de edición
    // =============================================================================
    // --- Acciones lentas (Tokko): deshabilita el botón y avisa mientras espera -------------
    $$('form[data-espera]').forEach((f) => {
        f.addEventListener('submit', () => {
            $$('form[data-espera] button').forEach((b) => { b.disabled = true; });
            const boton = $('button', f);
            if (boton) boton.textContent = f.dataset.espera;
        });
    });

    // --- Agregar desde Tokko con barra de progreso ----------------------------------------
    // La petición no informa su avance real: la barra avanza por etapas estimadas y llega
    // al 100 % solo cuando el servidor responde.
    $$('form[data-agregar-tokko]').forEach((f) => {
        f.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const fila = f.closest('[data-propiedad]');
            const caja = $('[data-progreso]', fila);
            const barra = $('[role="progressbar"]', caja);
            const relleno = $('[data-relleno]', caja);
            const texto = $('[data-progreso-texto]', caja);
            const botones = $$('form[data-agregar-tokko] button, form[data-espera] button');
            const pintar = (pct, mensaje) => {
                relleno.style.width = pct + '%';
                barra.setAttribute('aria-valuenow', String(Math.round(pct)));
                if (mensaje) texto.textContent = mensaje;
            };
            botones.forEach((b) => { b.disabled = true; });
            $('button', f).textContent = 'Agregando…';
            caja.hidden = false;
            fila.classList.add('lista__item--cargando');
            let pct = 0;
            pintar(0, 'Obteniendo la propiedad de Tokko…');
            const avance = setInterval(() => {
                pct += (92 - pct) * 0.08;   // se acerca a 92 % sin llegar
                pintar(pct, pct > 35 ? 'Copiando la foto de portada…' : null);
            }, 200);
            try {
                await enviar(new FormData(f));
                clearInterval(avance);
                pintar(100, 'Listo. Abriendo destacados…');
                setTimeout(() => { window.location.href = '/admin/seccion.php?s=destacados'; }, 500);
            } catch (e) {
                clearInterval(avance);
                caja.hidden = true;
                fila.classList.remove('lista__item--cargando');
                botones.forEach((b) => { b.disabled = false; });
                $('button', f).textContent = 'Agregar a destacados';
                toast(e.message, true);
            }
        });
    });

    // --- Buscador de propiedades de Tokko -----------------------------------------------
    const filtros = $('[data-filtros]');
    if (filtros) {
        filtros.hidden = false;
        const buscar = $('[data-buscar]', filtros);
        const operacion = $('[data-filtro-operacion]', filtros);
        const filas = $$('[data-propiedad]');
        const sinAcentos = (t) => t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        filas.forEach((f) => { f.dataset.busqueda = sinAcentos(f.dataset.busqueda); });
        const filtrar = () => {
            const palabras = sinAcentos(buscar.value).split(/\s+/).filter(Boolean);
            let visibles = 0;
            filas.forEach((f) => {
                const ok = palabras.every((p) => f.dataset.busqueda.includes(p))
                    && (!operacion.value || f.dataset.operaciones.split(' ').includes(operacion.value));
                f.hidden = !ok;
                if (ok) visibles++;
            });
            $('[data-cuenta]', filtros).textContent = visibles;
            $('[data-sin-resultados]').hidden = visibles > 0;
        };
        buscar.addEventListener('input', filtrar);
        operacion.addEventListener('change', filtrar);
    }

    const form = $('[data-formulario]');
    if (!form) return;
    const seccion = form.dataset.seccion;
    const botonPrevia = $('[data-previa]', form);
    let sinGuardar = false;
    let subiendo = 0;

    // Enfoca el aviso de error al cargar, para lectores de pantalla
    const avisoError = $('[data-enfocar]');
    if (avisoError) avisoError.focus();

    // --- Vista previa en vivo -----------------------------------------------------------
    let temporizadorPrevia;
    function actualizarPrevia() {
        clearTimeout(temporizadorPrevia);
        temporizadorPrevia = setTimeout(() => {
            if (botonPrevia && form.requestSubmit) form.requestSubmit(botonPrevia);
        }, 600);
    }
    if (botonPrevia) {
        botonPrevia.hidden = true;   // con JS se actualiza sola
        actualizarPrevia();
    }
    form.addEventListener('input', () => { sinGuardar = true; actualizarPrevia(); });
    form.addEventListener('change', () => { sinGuardar = true; actualizarPrevia(); });

    // --- Aviso al salir sin guardar ----------------------------------------------------
    form.addEventListener('submit', (ev) => {
        if (ev.submitter === botonPrevia) return;
        if (subiendo > 0) {
            ev.preventDefault();
            toast('Espera a que termine de subir el archivo.', true);
            return;
        }
        sinGuardar = false;
    });
    window.addEventListener('beforeunload', (ev) => {
        if (sinGuardar) { ev.preventDefault(); ev.returnValue = ''; }
    });

    // --- Precio con formato ------------------------------------------------------------
    $$('[data-precio]', form).forEach((input) => {
        const eco = input.parentNode.querySelector('[data-eco-precio]');
        const fmt = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 });
        const pintar = () => {
            const n = Number(input.value.replace(/[\s$,]|mxn|usd/gi, ''));
            eco.textContent = input.value.trim() === '' ? '' : (Number.isFinite(n) && n >= 0 ? '$' + fmt.format(n) : 'Escribe solo números');
        };
        input.addEventListener('input', pintar);
    });

    // --- Dirección del artículo a partir del título ------------------------------------
    const slug = $('[data-slug]', form);
    const tituloInput = $('#campo-titulo', form);
    if (slug && tituloInput) {
        let tocado = slug.value !== '';
        const hacerSlug = (t) => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
        tituloInput.addEventListener('input', () => { if (!tocado) slug.value = hacerSlug(tituloInput.value); });
        slug.addEventListener('input', () => { tocado = slug.value !== ''; });
        slug.addEventListener('change', () => { slug.value = hacerSlug(slug.value); });
    }

    // --- Subidas -----------------------------------------------------------------------
    const prefijo = () => {
        const t = $('#campo-titulo, #campo-nombre', form);
        return (t && t.value.trim()) || seccion;
    };

    async function subir(archivo, campo, estado) {
        const fd = new FormData();
        fd.set('accion', 'subir');
        fd.set('s', seccion);
        fd.set('campo', campo);
        fd.set('prefijo', prefijo());
        fd.set('archivo', archivo);
        subiendo++;
        estado.classList.remove('subida__estado--error');
        try {
            return await enviar(fd, (p) => {
                estado.textContent = p < 1 ? `Subiendo ${archivo.name}… ${Math.round(p * 100)} %` : `Procesando ${archivo.name}…`;
            });
        } finally {
            subiendo--;
        }
    }

    function mostrarError(estado, e) {
        estado.textContent = e.message;
        estado.classList.add('subida__estado--error');
    }

    // Imagen o video único
    $$('[data-imagen], [data-video]', form).forEach((caja) => {
        const input = $('input[type="file"]', caja);
        const ruta = $('[data-ruta]', caja);
        const vista = $('[data-vista]', caja);
        const vacia = $('[data-vacia]', caja);
        const quitar = $('[data-quitar]', caja);
        const estado = $('[data-estado]', caja);
        const textoElegir = $('[data-texto-elegir]', caja);
        const esVideo = caja.hasAttribute('data-video');

        const pintar = (rutaNueva, url) => {
            ruta.value = rutaNueva;
            vista.src = url || '';
            vista.hidden = !rutaNueva;
            vacia.hidden = !!rutaNueva;
            quitar.hidden = !rutaNueva;
            textoElegir.textContent = rutaNueva ? (esVideo ? 'Cambiar video' : 'Cambiar imagen') : (esVideo ? 'Elegir video' : 'Elegir imagen');
            form.dispatchEvent(new Event('change'));
        };

        input.addEventListener('change', async () => {
            const archivo = input.files[0];
            if (!archivo) return;
            try {
                const r = await subir(archivo, input.dataset.subir, estado);
                pintar(r.rutas[''], r.urls['']);
                estado.textContent = 'Listo. Recuerda guardar.';
            } catch (e) {
                mostrarError(estado, e);
            }
            input.value = '';
        });
        quitar.addEventListener('click', () => { pintar('', ''); estado.textContent = ''; });
    });

    // Galería de fotos
    $$('[data-fotos]', form).forEach((caja) => {
        const lista = $('[data-ordenable-local]', caja);
        const plantilla = $('[data-plantilla-foto]', caja);
        const input = $('input[type="file"]', caja);
        const estado = $('[data-estado]', caja);
        const cuenta = $('[data-cuenta]', caja);
        const max = Number(caja.dataset.max);

        const contar = () => {
            const n = $$('[data-item]', lista).length;
            cuenta.textContent = `${n} de ${max}`;
            input.disabled = n >= max;
            input.closest('label').classList.toggle('boton--deshabilitado', n >= max);
        };
        const cambio = () => { contar(); form.dispatchEvent(new Event('change')); };

        input.addEventListener('change', async () => {
            const libres = max - $$('[data-item]', lista).length;
            const archivos = Array.from(input.files).slice(0, libres);
            if (input.files.length > libres) {
                toast(`Solo caben ${libres} fotos más (máximo ${max}).`, true);
            }
            let errores = 0;
            for (const archivo of archivos) {
                try {
                    const r = await subir(archivo, input.dataset.subir, estado);
                    const li = plantilla.content.firstElementChild.cloneNode(true);
                    $('[data-vista]', li).src = r.urls.card;
                    $('[data-ruta-card]', li).value = r.rutas.card;
                    $('[data-ruta-full]', li).value = r.rutas.full;
                    lista.appendChild(li);
                    cambio();
                } catch (e) {
                    errores++;
                    mostrarError(estado, new Error(`${archivo.name}: ${e.message}`));
                }
            }
            if (!errores && archivos.length) estado.textContent = `${archivos.length === 1 ? 'Foto agregada' : archivos.length + ' fotos agregadas'}. Recuerda guardar.`;
            input.value = '';
        });

        lista.addEventListener('click', (ev) => {
            const li = ev.target.closest('[data-item]');
            if (!li) return;
            if (ev.target.closest('[data-quitar-foto]')) {
                li.remove();
                cambio();
                toast('Foto quitada. Se borrará al guardar.');
            } else if (ev.target.closest('[data-mover]')) {
                const dir = Number(ev.target.closest('[data-mover]').dataset.mover);
                const vecino = dir < 0 ? li.previousElementSibling : li.nextElementSibling;
                if (vecino) lista.insertBefore(li, dir < 0 ? vecino : vecino.nextSibling);
                cambio();
            }
        });
        ordenable(lista, { alTerminar: cambio });
        contar();
    });

    // --- Editor del blog -------------------------------------------------------------------
    $$('[data-editor]', form).forEach((editor) => {
        const area = $('textarea', editor);
        const estado = $('[data-estado]', editor);

        /** Reemplaza la selección (o inserta en el cursor) y deja seleccionado el texto de ejemplo. */
        function insertar(antes, despues = '', ejemplo = '', bloque = false) {
            const ini = area.selectionStart;
            const fin = area.selectionEnd;
            const valor = area.value;
            let seleccion = valor.slice(ini, fin) || ejemplo;
            let prefijoLinea = '';
            if (bloque && ini > 0 && valor[ini - 1] !== '\n') prefijoLinea = '\n\n';
            const nuevo = prefijoLinea + antes + seleccion + despues;
            area.setRangeText(nuevo, ini, fin, 'end');
            const inicioSel = ini + prefijoLinea.length + antes.length;
            area.setSelectionRange(inicioSel, inicioSel + seleccion.length);
            area.focus();
            area.dispatchEvent(new Event('input', { bubbles: true }));
        }

        /** Antepone un prefijo a cada línea seleccionada. */
        function prefijar(prefijoFn) {
            const valor = area.value;
            const ini = valor.lastIndexOf('\n', area.selectionStart - 1) + 1;
            let fin = valor.indexOf('\n', area.selectionEnd);
            if (fin === -1) fin = valor.length;
            const lineas = valor.slice(ini, fin).split('\n').map((l, i) => prefijoFn(i) + l);
            area.setRangeText(lineas.join('\n'), ini, fin, 'select');
            area.focus();
            area.dispatchEvent(new Event('input', { bubbles: true }));
        }

        const acciones = {
            titulo:      () => prefijar(() => '## '),
            negritas:    () => insertar('**', '**', 'texto en negritas'),
            cursiva:     () => insertar('*', '*', 'texto en cursiva'),
            lista:       () => prefijar(() => '- '),
            numerada:    () => prefijar((i) => `${i + 1}. `),
            cita:        () => prefijar(() => '> '),
            linea:       () => insertar('\n---\n\n', '', '', true),
            calculadora: () => insertar('[[calculadora-gastos]]\n\n', '', '', true),
            asesoria:    () => insertar('[[cta-asesoria]]\n\n', '', '', true),
            liga: () => {
                const url = window.prompt('Dirección de la liga (https://… o /pagina):', 'https://');
                if (url && url !== 'https://') insertar('[', `](${url.trim()})`, 'texto de la liga');
            },
        };
        $$('[data-formato]', editor).forEach((b) => b.addEventListener('click', () => acciones[b.dataset.formato]()));

        // Imagen dentro del artículo
        const inputImagen = $('input[data-insertar]', editor);
        inputImagen.addEventListener('change', async () => {
            const archivo = inputImagen.files[0];
            if (!archivo) return;
            try {
                const r = await subir(archivo, inputImagen.dataset.subir, estado);
                const alt = window.prompt('Describe brevemente la imagen (para Google y accesibilidad):', '') || '';
                insertar(`![${alt.replace(/[\[\]]/g, '')}](${r.rutas['']})\n\n`, '', '', true);
                estado.textContent = 'Imagen insertada. Recuerda guardar.';
            } catch (e) {
                mostrarError(estado, e);
            }
            inputImagen.value = '';
        });
    });

    // --- Contador de caracteres en textos largos --------------------------------------------
    $$('textarea[maxlength]', form).forEach((area) => {
        const max = Number(area.getAttribute('maxlength'));
        const contador = document.createElement('p');
        contador.className = 'campo__contador';
        contador.setAttribute('aria-live', 'polite');
        area.after(contador);
        const pintar = () => { contador.textContent = `${area.value.length} / ${max}`; };
        area.addEventListener('input', pintar);
        pintar();
    });
})();
