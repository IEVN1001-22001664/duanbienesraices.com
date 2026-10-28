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

    // --- Confirmaciones -------------------------------------------------------------------
    document.addEventListener('submit', (ev) => {
        const form = ev.target;
        if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) {
            ev.preventDefault();
            ev.stopImmediatePropagation();
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
