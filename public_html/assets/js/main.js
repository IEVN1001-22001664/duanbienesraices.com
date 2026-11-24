/**
 * Du'an Bienes Raíces — interacciones del sitio (JS vanilla, sin dependencias).
 * Todo es mejora progresiva: sin JS el contenido sigue siendo visible y navegable.
 */
(function () {
    'use strict';

    var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

    /* ---------------------------------------------------------------
       Menú móvil
       --------------------------------------------------------------- */
    var botonMenu = $('.menu-boton');
    var nav = $('#menu-principal');
    if (botonMenu && nav) {
        botonMenu.addEventListener('click', function () {
            var abierto = botonMenu.getAttribute('aria-expanded') === 'true';
            botonMenu.setAttribute('aria-expanded', String(!abierto));
            nav.classList.toggle('es-abierta', !abierto);
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && nav.classList.contains('es-abierta')) {
                botonMenu.click();
                botonMenu.focus();
            }
        });
        // Cierra el panel al tocar fuera de él
        document.addEventListener('click', function (ev) {
            if (nav.classList.contains('es-abierta') && !nav.contains(ev.target) && !botonMenu.contains(ev.target)) {
                botonMenu.click();
            }
        });
    }

    /* Encabezado fijo: al bajar se vuelve tarjeta flotante (la transición está en el CSS).
       Dos umbrales distintos para que no parpadee justo en el límite. */
    var encabezado = $('.encabezado');
    if (encabezado) {
        var marcarDesplazado = function () {
            var y = window.scrollY;
            if (y > 40) encabezado.classList.add('es-desplazado');
            else if (y < 12) encabezado.classList.remove('es-desplazado');
        };
        window.addEventListener('scroll', marcarDesplazado, { passive: true });
        marcarDesplazado();
    }

    /* ---------------------------------------------------------------
       Puntos de navegación compartidos por los carruseles
       --------------------------------------------------------------- */
    function crearPuntos(contenedor, total, alElegir, etiqueta) {
        if (!contenedor || total < 2) return function () {};
        var botones = [];
        for (var i = 0; i < total; i++) {
            var b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('aria-label', etiqueta + ' ' + (i + 1) + ' de ' + total);
            (function (n) { b.addEventListener('click', function () { alElegir(n); }); })(i);
            contenedor.appendChild(b);
            botones.push(b);
        }
        return function marcar(n) {
            botones.forEach(function (b, i) { b.setAttribute('aria-current', i === n ? 'true' : 'false'); });
        };
    }

    /* ---------------------------------------------------------------
       Hero: fundido entre diapositivas + video de fondo en escritorio
       --------------------------------------------------------------- */
    $$('.hero[data-carrusel]').forEach(function (hero) {
        var slides = $$('[data-slide]', hero);
        if (!slides.length) return;
        var actual = 0;
        var intervalo = parseInt(hero.getAttribute('data-auto'), 10) || 7000;
        var FUNDIDO = 900;                 // igual que la transición de .hero__slide en el CSS
        var ESPERA_VIDEO = 10000;          // si el video no arranca en este tiempo, se avanza con el intervalo normal
        var temporizador = null;
        var ahorroDatos = !!(navigator.connection && navigator.connection.saveData);
        var pantallaGrande = window.matchMedia('(min-width: 768px)').matches;
        // Video en escritorio; en celular solo los ligeros (data-ligero, menos de 1 MB). Nunca con
        // «reducir movimiento» ni con ahorro de datos.
        function usarVideo(v) {
            return !!v && !reducirMovimiento && !ahorroDatos && (pantallaGrande || v.hasAttribute('data-ligero'));
        }
        var automatico = !reducirMovimiento && slides.length > 1;

        function siguiente() { ir(actual + 1); }

        /* Programa el siguiente cambio. Con video: al terminar (empezando el fundido un poco antes
           del final para que el video que sale se desvanezca en movimiento). Sin video: el intervalo.
           Siempre queda un temporizador de respaldo, así el carrusel nunca se queda atorado. */
        function programar() {
            clearTimeout(temporizador);
            if (!automatico) return;
            var v = $('video', slides[actual]);
            if (!usarVideo(v)) { temporizador = setTimeout(siguiente, intervalo); return; }
            if (v.duration && isFinite(v.duration)) {
                var resta = (v.duration - v.currentTime) * 1000 - FUNDIDO;
                temporizador = setTimeout(siguiente, Math.max(resta, 1500) + 2000);   // respaldo por si se traba
            } else {
                temporizador = setTimeout(siguiente, ESPERA_VIDEO);
            }
        }

        function vigilarVideo(v, slide) {
            if (v.dataset.vigilado) return;
            v.dataset.vigilado = '1';
            v.loop = !automatico;          // con una sola diapositiva el video sí se repite
            v.addEventListener('loadedmetadata', function () { if (slides[actual] === slide) programar(); });
            v.addEventListener('timeupdate', function () {
                if (slides[actual] !== slide || !automatico || !v.duration) return;
                if (v.duration - v.currentTime <= FUNDIDO / 1000) siguiente();
            });
            v.addEventListener('ended', function () { if (slides[actual] === slide && automatico) siguiente(); });
            v.addEventListener('error', function () { if (slides[actual] === slide) { clearTimeout(temporizador); temporizador = setTimeout(siguiente, intervalo); } });
        }

        function sincronizarVideo(anterior) {
            slides.forEach(function (s, i) {
                var v = $('video', s);
                if (!v) return;
                if (i === actual && usarVideo(v)) {
                    vigilarVideo(v, s);
                    if (!v.src) { v.src = v.getAttribute('data-src'); }
                    try { v.currentTime = 0; } catch (e) {}
                    var p = v.play();
                    if (p && p.catch) p.catch(function () {});
                } else if (i === anterior && !v.paused) {
                    // El que sale sigue corriendo mientras se desvanece y se pausa al terminar el fundido.
                    setTimeout(function () { if (slides[actual] !== s) v.pause(); }, FUNDIDO);
                } else if (!v.paused) {
                    v.pause();
                }
            });
        }

        var marcar = crearPuntos($('[data-puntos]', hero), slides.length, function (n) { ir(n); }, 'Ir a la diapositiva');

        function ir(n) {
            var anterior = actual;
            n = (n + slides.length) % slides.length;
            if (n === anterior) { programar(); return; }
            slides[anterior].classList.remove('es-activa');
            slides[anterior].setAttribute('aria-hidden', 'true');
            actual = n;
            slides[actual].classList.add('es-activa');
            slides[actual].removeAttribute('aria-hidden');
            marcar(actual);
            sincronizarVideo(anterior);
            programar();
        }

        var ant = $('[data-anterior]', hero), sig = $('[data-siguiente]', hero);
        if (ant) ant.addEventListener('click', function () { ir(actual - 1); });
        if (sig) sig.addEventListener('click', function () { ir(actual + 1); });

        // Si la pestaña queda en segundo plano, al volver se reprograma para no saltar varias diapositivas.
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') programar(); else clearTimeout(temporizador);
        });

        marcar(0);
        sincronizarVideo(-1);
        programar();
    });

    /* ---------------------------------------------------------------
       Carruseles con scroll-snap (testimonios, galería de Nosotros, banners)
       Con data-auto="ms" avanzan solos: se detienen con el mouse o el foco
       dentro, fuera de pantalla y con «reducir movimiento»; tras usar las
       flechas o deslizar, la cuenta vuelve a empezar.
       --------------------------------------------------------------- */
    $$('.carrusel[data-carrusel]').forEach(function (carrusel) {
        var pista = $('.carrusel__pista', carrusel);
        var items = $$('[data-slide]', pista);
        if (!pista || items.length < 2) return;

        function porVista() {
            var ancho = items[0].getBoundingClientRect().width || 1;
            return Math.max(1, Math.round(pista.clientWidth / ancho));
        }
        function paginas() { return Math.max(1, items.length - porVista() + 1); }
        function indiceActual() {
            var ancho = items[0].getBoundingClientRect().width + parseFloat(getComputedStyle(pista).columnGap || 0);
            return Math.round(pista.scrollLeft / ancho);
        }
        var indice = 0;
        var marcar = function () {};
        function ir(n) {
            var total = paginas();
            indice = (n + total) % total;
            // La pista es position:relative, así que offsetLeft ya es relativo a ella.
            pista.scrollTo({ left: items[indice].offsetLeft, behavior: reducirMovimiento ? 'auto' : 'smooth' });
            marcar(indice);
        }

        var contPuntos = $('[data-puntos]', carrusel);
        function construirPuntos() {
            if (!contPuntos) return;
            contPuntos.innerHTML = '';
            marcar = crearPuntos(contPuntos, paginas(), ir, 'Ir al elemento');
            indice = Math.min(indiceActual(), paginas() - 1);
            marcar(indice);
        }
        construirPuntos();

        var ant = $('[data-anterior]', carrusel), sig = $('[data-siguiente]', carrusel);
        if (ant) ant.addEventListener('click', function () { ir(indice - 1); reiniciarAuto(); });
        if (sig) sig.addEventListener('click', function () { ir(indice + 1); reiniciarAuto(); });

        // Avance automático
        var auto = parseInt(carrusel.getAttribute('data-auto'), 10) || 0;
        var relojAuto = null, pausado = false, enPantalla = true;
        function reiniciarAuto() {
            clearInterval(relojAuto);
            if (!auto || reducirMovimiento) return;
            relojAuto = setInterval(function () {
                if (!pausado && enPantalla && !document.hidden) ir(indice + 1);
            }, auto);
        }
        if (auto && !reducirMovimiento) {
            carrusel.addEventListener('mouseenter', function () { pausado = true; });
            carrusel.addEventListener('mouseleave', function () { pausado = false; reiniciarAuto(); });
            carrusel.addEventListener('focusin', function () { pausado = true; });
            carrusel.addEventListener('focusout', function () { pausado = false; });
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (e) { enPantalla = e[0].isIntersecting; }).observe(carrusel);
            }
            pista.addEventListener('touchstart', reiniciarAuto, { passive: true });
            reiniciarAuto();
        }

        // Deslizar con el dedo: sincroniza el índice cuando termina el scroll.
        var espera;
        pista.addEventListener('scroll', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                indice = Math.min(indiceActual(), paginas() - 1);
                marcar(indice);
            }, 150);
        }, { passive: true });
        window.addEventListener('resize', function () { clearTimeout(espera); espera = setTimeout(construirPuntos, 200); });
    });

    /* ---------------------------------------------------------------
       Resultados comprobados: la cinta se mueve sola con CSS. Como las fotos
       entran por la derecha ya transformadas, el lazy nativo llega tarde; cuando
       la sección está por verse se piden todas de una vez.
       --------------------------------------------------------------- */
    $$('.resultados__pista, .testimonios__pista').forEach(function (pista) {
        var cargar = function () {
            $$('img[loading="lazy"]', pista).forEach(function (img) { img.loading = 'eager'; });
        };
        if (!('IntersectionObserver' in window)) { cargar(); return; }
        var obs = new IntersectionObserver(function (entradas) {
            if (entradas[0].isIntersecting) { cargar(); obs.disconnect(); }
        }, { rootMargin: '600px 0px' });
        obs.observe(pista);
    });

    /* ---------------------------------------------------------------
       Resultados comprobados: con JS la cinta se mueve aquí (sin JS, con CSS).
       - Avanza sola a la misma velocidad que la animación CSS (--segundos por tarjeta).
       - Las flechas avanzan o retroceden una tarjeta; se puede arrastrar con el dedo.
       - Se detiene con el mouse encima o con el foco en una tarjeta, y tras usar las
         flechas o arrastrar sigue 4 s después. La lista va dos veces: la posición se
         toma módulo una copia, así nunca se acaba.
       - Con «reducir movimiento» queda quieta y las flechas desplazan la ventana.
       --------------------------------------------------------------- */
    $$('[data-cinta]').forEach(function (seccion) {
        var ventana = $('.resultados__ventana', seccion);
        var pista = $('.resultados__pista', seccion);
        var grupo = $('.resultados__grupo', seccion);
        var flechas = $('.resultados__flechas', seccion);
        if (!ventana || !pista || !grupo || !flechas) return;
        seccion.classList.add('con-js');
        flechas.hidden = false;

        var paso = 0, copia = 0, velocidad = 0;   // px por tarjeta, px de una copia, px/s
        var medir = function () {
            var primera = grupo.children[0];
            if (!primera) return;
            var hueco = parseFloat(getComputedStyle(grupo).columnGap) || 0;
            paso = primera.getBoundingClientRect().width + hueco;
            copia = grupo.getBoundingClientRect().width;
            var total = grupo.children.length || 1;
            var segundos = parseFloat(getComputedStyle(pista).getPropertyValue('--segundos')) || 5;
            velocidad = copia / (total * segundos);
        };

        if (reducirMovimiento) {
            $$('[data-cinta-dir]', flechas).forEach(function (b) {
                b.addEventListener('click', function () {
                    medir();
                    ventana.scrollBy({ left: Number(b.getAttribute('data-cinta-dir')) * paso });
                });
            });
            return;
        }

        var pos = 0, salto = null, arrastre = null, pausaHasta = 0, encima = false, visible = true, antes = 0;
        var normal = function (x) { return copia ? ((x % copia) + copia) % copia : 0; };
        var pintar = function () { pista.style.transform = 'translate3d(' + (-normal(pos)) + 'px, 0, 0)'; };
        var pausar = function (ms) { pausaHasta = Math.max(pausaHasta, performance.now() + ms); };
        var focoEnTarjeta = function () {
            var a = document.activeElement;
            return a && ventana.contains(a);
        };
        var cuadro = function (t) {
            var dt = antes ? Math.min((t - antes) / 1000, .1) : 0;
            antes = t;
            if (salto) {
                var k = Math.min((t - salto.inicio) / salto.dura, 1);
                pos = salto.desde + (salto.hasta - salto.desde) * (1 - Math.pow(1 - k, 3));
                if (k === 1) { salto = null; pos = normal(pos); }
            } else if (!arrastre && !encima && visible && t >= pausaHasta && !focoEnTarjeta()) {
                pos += velocidad * dt;
            }
            pintar();
            requestAnimationFrame(cuadro);
        };

        $$('[data-cinta-dir]', flechas).forEach(function (b) {
            b.addEventListener('click', function () {
                if (!paso) medir();
                if (!paso) return;
                var dir = Number(b.getAttribute('data-cinta-dir'));
                var base = salto ? salto.hasta : pos;
                var hasta = dir > 0
                    ? (Math.floor(base / paso + 1e-3) + 1) * paso
                    : (Math.ceil(base / paso - 1e-3) - 1) * paso;
                salto = { desde: pos, hasta: hasta, inicio: performance.now(), dura: 450 };
                pausar(4000);
            });
        });

        // El mouse encima detiene la cinta (no aplica al tacto: ahí se arrastra)
        ventana.addEventListener('pointerenter', function (ev) { if (ev.pointerType === 'mouse') encima = true; });
        ventana.addEventListener('pointerleave', function (ev) { if (ev.pointerType === 'mouse') { encima = false; pausar(600); } });

        // Arrastrar con el dedo (o lápiz); el desplazamiento vertical de la página sigue igual (touch-action: pan-y)
        ventana.addEventListener('pointerdown', function (ev) {
            if (ev.pointerType === 'mouse') return;
            salto = null;
            arrastre = { x: ev.clientX, pos: pos, id: ev.pointerId };
        });
        ventana.addEventListener('pointermove', function (ev) {
            if (!arrastre || ev.pointerId !== arrastre.id) return;
            pos = arrastre.pos - (ev.clientX - arrastre.x);
        });
        var soltar = function (ev) {
            if (!arrastre || ev.pointerId !== arrastre.id) return;
            arrastre = null;
            pausar(4000);
        };
        ventana.addEventListener('pointerup', soltar);
        ventana.addEventListener('pointercancel', soltar);

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entradas) { visible = entradas[0].isIntersecting; }).observe(ventana);
        }
        var espera;
        window.addEventListener('resize', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                var proporcion = copia ? normal(pos) / copia : 0;
                medir();
                pos = proporcion * copia;
                pintar();
            }, 150);
        });
        medir();
        requestAnimationFrame(cuadro);
    });

    /* ---------------------------------------------------------------
       Galerías de fotos de los destacados
       --------------------------------------------------------------- */
    $$('[data-galeria]').forEach(function (galeria) {
        var pista = $('.galeria__pista', galeria);
        var fotos = $$('.galeria__foto', galeria);
        var izq = $('.galeria__flecha--izq', galeria);
        var der = $('.galeria__flecha--der', galeria);
        var contador = $('[data-actual]', galeria);
        if (!pista || fotos.length < 2) return;
        if (izq) izq.hidden = false;
        if (der) der.hidden = false;

        // El índice se guarda aparte: leer scrollLeft a mitad de la animación daba saltos.
        var indice = 0;
        function mostrar() {
            if (contador) contador.textContent = String(indice + 1);
            galeria.dispatchEvent(new CustomEvent('galeria:cambio', { detail: indice }));
        }
        function ir(n) {
            indice = (n + fotos.length) % fotos.length;
            pista.scrollTo({ left: indice * pista.clientWidth, behavior: reducirMovimiento ? 'auto' : 'smooth' });
            mostrar();
        }
        if (izq) izq.addEventListener('click', function () { ir(indice - 1); });
        if (der) der.addEventListener('click', function () { ir(indice + 1); });
        // Las miniaturas de la ficha piden ir a una foto con este evento
        galeria.addEventListener('galeria:ir', function (ev) { ir(ev.detail); });
        pista.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowLeft') { ev.preventDefault(); ir(indice - 1); }
            if (ev.key === 'ArrowRight') { ev.preventDefault(); ir(indice + 1); }
        });
        // Si el usuario desliza con el dedo, sincroniza el índice cuando el scroll termina.
        var espera;
        pista.addEventListener('scroll', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                indice = Math.round(pista.scrollLeft / pista.clientWidth);
                mostrar();
            }, 150);
        }, { passive: true });
    });

    /* ---------------------------------------------------------------
       YouTube: la tarjeta solo muestra la miniatura; al hacer clic el video
       se abre en un modal grande (a todo lo ancho en celular). El iframe se
       crea al abrir y se elimina al cerrar para detener la reproducción.
       --------------------------------------------------------------- */
    var modalVideo = null;
    var abrirVideo = function (id, titulo) {
        if (!modalVideo) {
            modalVideo = document.createElement('dialog');
            modalVideo.className = 'video-modal';
            modalVideo.innerHTML = '<button class="video-modal__cerrar" type="button" aria-label="Cerrar video">&times;</button>' +
                '<div class="video-modal__marco"></div>';
            document.body.appendChild(modalVideo);
            $('.video-modal__cerrar', modalVideo).addEventListener('click', function () { modalVideo.close(); });
            // Clic fuera del video (en el fondo oscuro) cierra
            modalVideo.addEventListener('click', function (ev) { if (ev.target === modalVideo) modalVideo.close(); });
            modalVideo.addEventListener('close', function () { $('.video-modal__marco', modalVideo).innerHTML = ''; });
        }
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
        iframe.title = titulo || 'Video de YouTube';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
        iframe.allowFullscreen = true;
        $('.video-modal__marco', modalVideo).appendChild(iframe);
        modalVideo.setAttribute('aria-label', iframe.title);
        modalVideo.showModal();
    };
    // Botones y ligas con el id en data-abrir-video (ficha de propiedad)
    $$('[data-abrir-video]').forEach(function (enlace) {
        enlace.addEventListener('click', function (ev) {
            var id = enlace.getAttribute('data-abrir-video');
            if (!/^[\w-]{11}$/.test(id) || typeof HTMLDialogElement !== 'function') return; // sin <dialog>: abre YouTube
            ev.preventDefault();
            abrirVideo(id, enlace.getAttribute('data-titulo'));
        });
    });

    // Aviso flotante del video en la ficha: aparece a los 2.5 s (después de la animación de entrada en
    // celular), una vez por visita a cada propiedad; se quita al cerrarlo, al abrir el video o al llegar
    // a la sección de videos.
    var avisoVideo = $('[data-video-aviso]');
    if (avisoVideo) {
        var claveAviso = 'duan-video-' + avisoVideo.getAttribute('data-video-aviso');
        var yaVisto = false;
        try { yaVisto = sessionStorage.getItem(claveAviso) === '1'; } catch (err) {}
        var quitarAviso = function () {
            try { sessionStorage.setItem(claveAviso, '1'); } catch (err) {}
            avisoVideo.classList.remove('es-visible');
            setTimeout(function () { avisoVideo.hidden = true; }, 300);
        };
        if (!yaVisto) {
            setTimeout(function () {
                avisoVideo.hidden = false;
                requestAnimationFrame(function () { requestAnimationFrame(function () { avisoVideo.classList.add('es-visible'); }); });
            }, 2500);
            $('.video-aviso__cerrar', avisoVideo).addEventListener('click', quitarAviso);
            $('.video-aviso__enlace', avisoVideo).addEventListener('click', quitarAviso);
            var seccionVideos = $('.ficha__video');
            if (seccionVideos && 'IntersectionObserver' in window) {
                new IntersectionObserver(function (e, obs) {
                    if (e[0].isIntersecting) { obs.disconnect(); if (!avisoVideo.hidden) quitarAviso(); }
                }).observe(seccionVideos);
            }
        }
    }

    $$('[data-youtube]').forEach(function (marco) {
        var enlace = $('.video__play', marco);
        if (!enlace) return;
        enlace.addEventListener('click', function (ev) {
            var id = marco.getAttribute('data-youtube');
            if (!/^[\w-]{11}$/.test(id) || typeof HTMLDialogElement !== 'function') return; // sin <dialog>: abre YouTube
            ev.preventDefault();
            abrirVideo(id, marco.getAttribute('data-titulo'));
        });
    });

    /* ---------------------------------------------------------------
       Banners de código propio (inicio): el documento aislado del <iframe>
       avisa su alto con postMessage y aquí se ajusta el marco. Solo se
       acepta el mensaje si viene de uno de esos marcos.
       --------------------------------------------------------------- */
    var marcosBanner = $$('[data-cartel-codigo]');
    if (marcosBanner.length) {
        window.addEventListener('message', function (ev) {
            var d = ev.data;
            if (!d || d.duanCartel !== 1 || typeof d.alto !== 'number') return;
            marcosBanner.forEach(function (marco) {
                if (marco.contentWindow === ev.source) {
                    marco.style.height = Math.max(60, Math.min(1600, Math.round(d.alto))) + 'px';
                }
            });
        });
    }

    /* ---------------------------------------------------------------
       «Agendar cita» (fichas): la agenda de Cal.com se abre en un modal sin
       salir de la página (a pantalla completa en celular). El iframe se crea
       al abrir y se borra al cerrar. Sin <dialog> o sin JS, abre otra pestaña.
       --------------------------------------------------------------- */
    var modalAgenda = null;
    $$('[data-agendar-cita="cal"]').forEach(function (boton) {
        if (typeof HTMLDialogElement !== 'function') return;
        boton.addEventListener('click', function (ev) {
            if (ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.button !== 0) return; // nueva pestaña a propósito
            ev.preventDefault();
            if (!modalAgenda) {
                modalAgenda = document.createElement('dialog');
                modalAgenda.className = 'agenda-modal';
                modalAgenda.setAttribute('aria-label', 'Agendar visita');
                modalAgenda.innerHTML = '<div class="agenda-modal__barra"><span class="agenda-modal__titulo">Agendar visita</span>' +
                    '<button class="agenda-modal__cerrar" type="button" aria-label="Cerrar agenda">&times;</button></div>' +
                    '<div class="agenda-modal__marco"><p class="agenda-modal__cargando">Cargando agenda…</p></div>';
                document.body.appendChild(modalAgenda);
                $('.agenda-modal__cerrar', modalAgenda).addEventListener('click', function () { modalAgenda.close(); });
                modalAgenda.addEventListener('click', function (e) { if (e.target === modalAgenda) modalAgenda.close(); });
                modalAgenda.addEventListener('close', function () {
                    var viejo = $('iframe', modalAgenda);
                    if (viejo) viejo.remove();
                    document.documentElement.classList.remove('sin-scroll');
                });
            }
            var iframe = document.createElement('iframe');
            iframe.src = boton.href;
            iframe.title = 'Agenda de citas de Du\'an Bienes Raíces';
            $('.agenda-modal__marco', modalAgenda).appendChild(iframe);
            document.documentElement.classList.add('sin-scroll');
            modalAgenda.showModal();
        });
    });

    /* ---------------------------------------------------------------
       Ficha: «Regresar a la búsqueda». Si la página anterior fue el listado
       de este mismo sitio, se usa el historial para volver con los mismos
       filtros, página y posición; si no, la liga abre /propiedades.
       --------------------------------------------------------------- */
    var regresar = $('[data-regresar]');
    if (regresar) {
        regresar.addEventListener('click', function (ev) {
            var anterior;
            try { anterior = new URL(document.referrer); } catch (err) { return; }
            if (anterior.origin === location.origin && /^\/propiedades\/?$/.test(anterior.pathname) && history.length > 1) {
                ev.preventDefault();
                history.back();
            }
        });
    }

    /* ---------------------------------------------------------------
       Texto plegable ([data-plegable="N"] + el botón .plegable__boton que
       le sigue): muestra N líneas y «Ver más / Ver menos». Si el texto
       apenas pasa del límite (menos de 2 líneas de más) se deja completo.
       --------------------------------------------------------------- */
    $$('[data-plegable]').forEach(function (bloque) {
        var boton = bloque.nextElementSibling;
        if (!boton || !boton.classList.contains('plegable__boton')) return;
        var lineas = parseInt(bloque.getAttribute('data-plegable'), 10) || 6;
        var estilo = getComputedStyle($('p', bloque) || bloque);
        var alto = parseFloat(estilo.lineHeight) || parseFloat(estilo.fontSize) * 1.6;
        var limite = Math.round(alto * lineas);
        if (bloque.scrollHeight <= limite + alto * 2) return;
        var texto = $('[data-plegable-texto]', boton);
        var plegar = function (plegado) {
            bloque.classList.toggle('plegable--cerrado', plegado);
            bloque.style.maxHeight = plegado ? limite + 'px' : '';
            boton.setAttribute('aria-expanded', plegado ? 'false' : 'true');
            if (texto) texto.textContent = plegado ? 'Ver más' : 'Ver menos';
        };
        plegar(true);
        boton.hidden = false;
        boton.addEventListener('click', function () {
            var plegado = !bloque.classList.contains('plegable--cerrado');
            plegar(plegado);
            // Al cerrar, si el inicio del texto quedó arriba de la pantalla, volver a él
            if (plegado && bloque.getBoundingClientRect().top < 0) {
                bloque.scrollIntoView({ block: 'center', behavior: reducirMovimiento ? 'auto' : 'smooth' });
            }
        });
    });

    /* ---------------------------------------------------------------
       Ficha de propiedad: miniaturas que mueven la galería principal
       --------------------------------------------------------------- */
    var galeriaFicha = $('[data-ficha-galeria]');
    var miniaturas = $('[data-miniaturas]');
    if (galeriaFicha && miniaturas) {
        var botonesMini = $$('[data-ir]', miniaturas);
        botonesMini.forEach(function (b) {
            b.addEventListener('click', function () {
                galeriaFicha.dispatchEvent(new CustomEvent('galeria:ir', { detail: Number(b.getAttribute('data-ir')) }));
            });
        });
        galeriaFicha.addEventListener('galeria:cambio', function (ev) {
            botonesMini.forEach(function (b, i) {
                if (i === ev.detail) {
                    b.setAttribute('aria-current', 'true');
                    // Mantiene visible la miniatura activa sin mover la página
                    miniaturas.scrollTo({ left: b.parentNode.offsetLeft - miniaturas.clientWidth / 2 + b.offsetWidth / 2, behavior: reducirMovimiento ? 'auto' : 'smooth' });
                } else {
                    b.removeAttribute('aria-current');
                }
            });
        });
    }

    /* ---------------------------------------------------------------
       Ficha de propiedad: al hacer clic en una foto se abre un visor a
       pantalla completa con la foto entera. Flechas, teclado y deslizar
       con el dedo cambian de foto; al cerrar, la galería queda en la
       misma foto.
       --------------------------------------------------------------- */
    if (galeriaFicha && typeof HTMLDialogElement === 'function') {
        var pistaFicha = $('.galeria__pista', galeriaFicha);
        var imagenesFicha = $$('.galeria__foto img', galeriaFicha);
        var indiceFicha = 0, indiceVisor = 0, visor = null;
        galeriaFicha.classList.add('ficha__galeria--ampliable');
        galeriaFicha.addEventListener('galeria:cambio', function (ev) { indiceFicha = ev.detail; });

        var pintarVisor = function () {
            var origen = imagenesFicha[indiceVisor];
            var img = $('.visor-fotos__img', visor);
            img.src = origen.currentSrc || origen.src;
            img.alt = origen.alt;
            $('.visor-fotos__contador', visor).textContent = (indiceVisor + 1) + ' / ' + imagenesFicha.length;
        };
        var moverVisor = function (paso) {
            indiceVisor = (indiceVisor + paso + imagenesFicha.length) % imagenesFicha.length;
            pintarVisor();
        };
        var abrirVisor = function (n) {
            if (!visor) {
                var flechaIzq = $('.galeria__flecha--izq', galeriaFicha);
                var flechaDer = $('.galeria__flecha--der', galeriaFicha);
                visor = document.createElement('dialog');
                visor.className = 'visor-fotos';
                visor.setAttribute('aria-label', 'Fotos en pantalla completa');
                visor.innerHTML = '<img class="visor-fotos__img" alt="" referrerpolicy="no-referrer">' +
                    '<button class="visor-fotos__cerrar" type="button" aria-label="Cerrar">&times;</button>' +
                    (imagenesFicha.length > 1
                        ? '<button class="visor-fotos__anterior" type="button" aria-label="Foto anterior">' + (flechaIzq ? flechaIzq.innerHTML : '&lsaquo;') + '</button>' +
                          '<button class="visor-fotos__siguiente" type="button" aria-label="Foto siguiente">' + (flechaDer ? flechaDer.innerHTML : '&rsaquo;') + '</button>'
                        : '') +
                    '<span class="visor-fotos__contador" aria-live="polite"></span>';
                document.body.appendChild(visor);
                $('.visor-fotos__cerrar', visor).addEventListener('click', function () { visor.close(); });
                var ant = $('.visor-fotos__anterior', visor), sig = $('.visor-fotos__siguiente', visor);
                if (ant) ant.addEventListener('click', function () { moverVisor(-1); });
                if (sig) sig.addEventListener('click', function () { moverVisor(1); });
                // Clic en el fondo oscuro (fuera de la foto y de los botones) cierra
                visor.addEventListener('click', function (ev) { if (ev.target === visor) visor.close(); });
                visor.addEventListener('keydown', function (ev) {
                    if (imagenesFicha.length < 2) return;
                    if (ev.key === 'ArrowLeft') { ev.preventDefault(); moverVisor(-1); }
                    if (ev.key === 'ArrowRight') { ev.preventDefault(); moverVisor(1); }
                });
                // Deslizar con el dedo: más de 50 px en horizontal cambia de foto
                var xInicio = null;
                visor.addEventListener('touchstart', function (ev) { xInicio = ev.touches.length === 1 ? ev.touches[0].clientX : null; }, { passive: true });
                visor.addEventListener('touchend', function (ev) {
                    if (xInicio === null || imagenesFicha.length < 2) return;
                    var dx = ev.changedTouches[0].clientX - xInicio;
                    xInicio = null;
                    if (Math.abs(dx) > 50) moverVisor(dx < 0 ? 1 : -1);
                }, { passive: true });
                visor.addEventListener('close', function () {
                    document.documentElement.style.overflow = '';
                    // La galería se queda en la foto que se estaba viendo
                    if (indiceVisor !== indiceFicha) galeriaFicha.dispatchEvent(new CustomEvent('galeria:ir', { detail: indiceVisor }));
                });
            }
            indiceVisor = n;
            pintarVisor();
            document.documentElement.style.overflow = 'hidden';   // la página no se mueve detrás
            visor.showModal();
        };
        imagenesFicha.forEach(function (img, i) {
            img.addEventListener('click', function () { abrirVisor(i); });
        });
        // Con teclado: Enter sobre la galería abre la foto actual
        if (pistaFicha) pistaFicha.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); abrirVisor(indiceFicha); }
        });
    }

    /* ---------------------------------------------------------------
       Listado de propiedades: ningún filtro se aplica solo; la búsqueda
       corre al tocar la lupa («Buscar») o Enter. Mientras haya cambios sin
       aplicar, el botón se resalta (.hay-cambios).
       --------------------------------------------------------------- */
    var filtros = $('[data-filtros-propiedades]');
    if (filtros) {
        var estadoFiltros = function () {
            return $$('input, select', filtros).map(function (c) {
                return c.type === 'radio' || c.type === 'checkbox' ? (c.checked ? c.name + '=' + c.value : '') : (c.name ? c.name + '=' + c.value : '');
            }).join('&');
        };
        var estadoInicial = estadoFiltros();
        var revisarCambios = function () { filtros.classList.toggle('hay-cambios', estadoFiltros() !== estadoInicial); };
        filtros.addEventListener('change', revisarCambios);
        filtros.addEventListener('input', revisarCambios);
        // Excepción: los chips Todas / Venta / Renta buscan en cuanto se tocan (con lo que haya en la barra).
        // requestSubmit pasa por el manejador de «submit» que quita los campos vacíos de la URL.
        $$('input[name="operacion"]', filtros).forEach(function (chip) {
            chip.addEventListener('change', function () {
                if (typeof filtros.requestSubmit === 'function') filtros.requestSubmit(); else filtros.submit();
            });
        });
        // Celular: precio y «Más filtros» se pliegan tras «Filtros» (tipo queda fuera, siempre visible).
        // En pantallas anchas el CSS los muestra siempre.
        var botonMas = $('[data-mas-filtros]', filtros);
        if (botonMas) {
            var plegar = function (plegado) {
                filtros.classList.toggle('es-plegado', plegado);
                botonMas.setAttribute('aria-expanded', plegado ? 'false' : 'true');
            };
            botonMas.hidden = false;
            filtros.classList.add('con-js');
            // Cerrado al cargar aunque haya filtros aplicados: el número del botón dice cuántos hay
            plegar(true);
            // Cerrar el desplegable al tocar fuera de la barra
            document.addEventListener('click', function (ev) {
                if (!filtros.contains(ev.target) && !filtros.classList.contains('es-plegado')) plegar(true);
            });
            botonMas.addEventListener('click', function () {
                plegar(!filtros.classList.contains('es-plegado'));
            });
        }
        // Desde 760 px: botón «Más filtros» que abre el panel flotante; se cierra con Esc o al tocar fuera
        var abrirMas = $('[data-abrir-mas]', filtros);
        if (abrirMas) {
            filtros.classList.add('con-js');
            abrirMas.hidden = false;
            var panelMas = function (abierto) {
                filtros.classList.toggle('panel-abierto', abierto);
                abrirMas.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            };
            abrirMas.addEventListener('click', function () { panelMas(!filtros.classList.contains('panel-abierto')); });
            document.addEventListener('click', function (ev) {
                if (!filtros.contains(ev.target)) panelMas(false);
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && filtros.classList.contains('panel-abierto')) { panelMas(false); abrirMas.focus(); }
            });
        }
        // «Limpiar» del panel: deja sus opciones como al principio (sin buscar todavía)
        var limpiarMas = $('[data-limpiar-mas]', filtros);
        if (limpiarMas) {
            limpiarMas.hidden = false;
            limpiarMas.addEventListener('click', function () {
                $$('input', $('[data-filtros-mas]', filtros)).forEach(function (c) {
                    if (c.type === 'radio') c.checked = c.value === '' || c.value === 'construida';
                    else if (c.type === 'checkbox') c.checked = false;
                    else c.value = '';
                });
                revisarCambios();
            });
        }
        // Celular: cuando la barra queda fija se compacta (sin chips). Un marcador de 0 px en su
        // lugar original dice si ya se pasó; el alto de los chips se guarda para compensar el hueco.
        if ('IntersectionObserver' in window) {
            var marcador = document.createElement('div');
            marcador.setAttribute('aria-hidden', 'true');
            filtros.parentNode.insertBefore(marcador, filtros);
            var chips = $('.filtros-propiedades__operacion', filtros);
            var vigilar = function () {
                var arriba = parseFloat(getComputedStyle(filtros).top) || 0;
                // Al compactarse se esconden los chips y el tipo: se mide cuánto se encoge la barra (con y sin
                // la clase, sin pintar en medio) y se guarda en el contenedor, porque el relleno va en el hermano
                if (chips && chips.offsetHeight !== undefined) {
                    var tenia = filtros.classList.contains('es-compacta');
                    filtros.classList.remove('es-compacta');
                    var completa = filtros.offsetHeight;
                    filtros.classList.add('es-compacta');
                    var compacta = filtros.offsetHeight;
                    filtros.classList.toggle('es-compacta', tenia);
                    filtros.parentNode.style.setProperty('--alto-chips', Math.max(0, completa - compacta) + 'px');
                }
                if (vigilar.obs) vigilar.obs.disconnect();
                vigilar.obs = new IntersectionObserver(function (e) {
                    filtros.classList.toggle('es-compacta', !e[0].isIntersecting && e[0].boundingClientRect.top < arriba);
                }, { rootMargin: (2 - Math.floor(arriba)) + 'px 0px 0px 0px' }); // 2 px de margen: el marcador empieza dentro (la barra está pegada al menú)
                vigilar.obs.observe(marcador);
            };
            vigilar();
            window.addEventListener('resize', vigilar, { passive: true });
        }

        // Los campos vacíos no viajan en la URL
        filtros.addEventListener('submit', function () {
            $$('input, select', filtros).forEach(function (c) { if (c.name && c.value === '') c.disabled = true; });
            // «Construida/Terreno» solo viaja si hay medidas
            if (!filtros.elements.m2min.value && !filtros.elements.m2max.value) {
                $$('input[name="sup"]', filtros).forEach(function (c) { c.disabled = true; });
            }
        });
    }
    var orden = $('[data-orden]');
    if (orden && orden.form) {
        orden.addEventListener('change', function () {
            if (orden.value === 'recientes') orden.disabled = true;
            orden.form.submit();
        });
    }

    /* ---------------------------------------------------------------
       Contadores (Nosotros): suben de 0 a la cifra la primera vez que se
       ven, en ~1.8 s y frenando al final. Sin JS o con «reducir
       movimiento» se queda la cifra final que trae el HTML.
       --------------------------------------------------------------- */
    var contadores = $$('[data-contador]');
    if (contadores.length && !reducirMovimiento && 'IntersectionObserver' in window) {
        var animarContador = function (el) {
            var meta = Number(el.getAttribute('data-contador')) || 0;
            var prefijo = el.getAttribute('data-prefijo') || '';
            var duracion = 1800, inicio = null;
            var paso = function (t) {
                if (inicio === null) inicio = t;
                var avance = Math.min(1, (t - inicio) / duracion);
                var suave = 1 - Math.pow(1 - avance, 3);      // ease-out cúbico
                el.textContent = prefijo + Math.round(meta * suave);
                if (avance < 1) requestAnimationFrame(paso);
            };
            requestAnimationFrame(paso);
        };
        var vigiaContador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (en) {
                if (en.isIntersecting) {
                    vigiaContador.unobserve(en.target);
                    animarContador(en.target);
                }
            });
        }, { threshold: 0.6 });
        contadores.forEach(function (el) {
            // El número animado es aria-hidden; los lectores de pantalla leen el texto oculto con la cifra final
            el.textContent = (el.getAttribute('data-prefijo') || '') + '0';
            vigiaContador.observe(el);
        });
    }

    /* ---------------------------------------------------------------
       Parallax de los fondos (portadas, servicios del inicio y banda
       roja): la foto se mueve más lento que la página. Solo se calcula
       para las secciones que están en pantalla.
       --------------------------------------------------------------- */
    var fondosParalaje = $$('.portada, .servicios, .banda-inventario');
    if (fondosParalaje.length && !reducirMovimiento) {
        var paralajePendiente = false;
        var moverFondos = function () {
            paralajePendiente = false;
            var alto = window.innerHeight;
            fondosParalaje.forEach(function (el) {
                var r = el.getBoundingClientRect();
                if (r.bottom < 0 || r.top > alto) return;
                // 0 cuando la sección asoma por abajo, 1 cuando termina de salir por arriba
                var avance = (alto - r.top) / (alto + r.height);
                // La foto sobra un 16 % arriba y abajo; se usa hasta el 14 %
                el.style.setProperty('--paralaje', ((avance - 0.5) * 2 * r.height * 0.14).toFixed(1) + 'px');
            });
        };
        var pedirParalaje = function () {
            if (!paralajePendiente) { paralajePendiente = true; requestAnimationFrame(moverFondos); }
        };
        window.addEventListener('scroll', pedirParalaje, { passive: true });
        window.addEventListener('resize', pedirParalaje);
        moverFondos();
    }

    /* ---------------------------------------------------------------
       Aparición gradual al desplazar: títulos, tarjetas y bloques
       entran con un fundido la primera vez que se ven. Lo que ya está
       en pantalla al cargar no se toca. Los hermanos entran escalonados.
       --------------------------------------------------------------- */
    if (!reducirMovimiento && 'IntersectionObserver' in window) {
        var candidatos = $$([
            'main .titulo-seccion', 'main .subtitulo-seccion',
            '.bienvenida__grid > *', '.servicio', '.destacados__grid > *', '.videos__grid > li',
            '.fundador .contenedor > *', '.mision__grid > *', '.cifra-destacada', '.cifras__grid > li',
            '.como__grid > *', '.pilar', '.pilares__grafico', '.equipo', '.ejemplo__tarjeta', '.banda-inventario .contenedor',
            '.blog__grid > *', '.propiedades__grid > *', '.contacto .contenedor > *', '.formulario-seccion .contenedor > *'
        ].join(','));
        var altoVentana = window.innerHeight;
        var vigiaAparece = new IntersectionObserver(function (entradas) {
            var orden = 0;
            entradas.forEach(function (en) {
                if (!en.isIntersecting) return;
                var el = en.target;
                vigiaAparece.unobserve(el);
                var retraso = Math.min(orden++, 5) * 90;
                el.style.setProperty('--retraso', retraso + 'ms');
                el.classList.add('es-visible');
                // Al terminar se limpia todo para no estorbar a las transiciones propias (hover, etc.)
                setTimeout(function () {
                    el.classList.remove('aparece', 'aparece--izq', 'aparece--der', 'es-visible');
                    el.style.removeProperty('--retraso');
                }, retraso + 800);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
        candidatos.forEach(function (el) {
            // No anidar: si un ancestro ya aparece, este se queda quieto
            if (el.parentElement && el.parentElement.closest('.aparece')) return;
            if (el.getBoundingClientRect().top < altoVentana * 0.92) return;
            el.classList.add('aparece');
            if (el.classList.contains('pilar')) el.classList.add('aparece--izq');
            if (el.classList.contains('pilares__grafico')) el.classList.add('aparece--der');
            vigiaAparece.observe(el);
        });
    }

    /* ---------------------------------------------------------------
       Mapa de Google: se carga al hacer clic
       --------------------------------------------------------------- */
    $$('[data-mapa]').forEach(function (marco) {
        var boton = $('button', marco);
        if (!boton) return;
        boton.addEventListener('click', function () {
            var iframe = document.createElement('iframe');
            iframe.src = marco.getAttribute('data-mapa');
            iframe.title = marco.getAttribute('data-mapa-titulo') || 'Mapa de ubicación de Du\'an Bienes Raíces';
            iframe.loading = 'lazy';
            iframe.referrerPolicy = 'no-referrer-when-downgrade';
            marco.innerHTML = '';
            marco.appendChild(iframe);
        });
    });

    /* ---------------------------------------------------------------
       Calculadora de gastos iniciales (blog)
       --------------------------------------------------------------- */
    $$('[data-calculadora]').forEach(function (calc) {
        var entrada = $('input', calc);
        var res = function (k) { return $('[data-res="' + k + '"]', calc); };
        var mxn = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
        var num = new Intl.NumberFormat('es-MX');
        var whatsBase = res('whatsapp') ? res('whatsapp').href.split('&text=')[0] : '';

        function calcular() {
            var precio = parseFloat(entrada.value.replace(/[^0-9]/g, '')) || 0;
            var enganche = precio * 0.10, notMin = precio * 0.04, notMax = precio * 0.06;
            var credito = precio * 0.90, millones = credito / 1e6;
            res('enganche').textContent = mxn.format(enganche);
            res('notaria').textContent = mxn.format(notMin) + ' – ' + mxn.format(notMax);
            res('total').textContent = mxn.format(enganche + notMin) + ' – ' + mxn.format(enganche + notMax);
            res('credito').textContent = mxn.format(credito) + ' MXN';
            res('mensualidad').textContent = mxn.format(millones * 11000) + ' – ' + mxn.format(millones * 12000) + ' MXN/mes';
            res('liquidez').textContent = mxn.format(enganche + notMin) + ' – ' + mxn.format(enganche + notMax) + ' MXN';
            if (whatsBase) {
                res('whatsapp').href = whatsBase + '&text=' + encodeURIComponent(
                    'Hola, usé la calculadora de su blog para una casa de ' + mxn.format(precio) +
                    ' y me gustaría recibir asesoría para tramitar mi crédito.');
            }
            $$('.chip', calc).forEach(function (c) {
                c.setAttribute('aria-pressed', String(parseInt(c.getAttribute('data-precio'), 10) === precio));
            });
        }
        entrada.addEventListener('input', function () {
            var limpio = entrada.value.replace(/[^0-9]/g, '');
            entrada.value = limpio ? num.format(parseInt(limpio, 10)) : '';
            calcular();
        });
        $$('.chip', calc).forEach(function (chip) {
            chip.addEventListener('click', function () {
                entrada.value = num.format(parseInt(chip.getAttribute('data-precio'), 10));
                calcular();
            });
        });
        calcular();
    });

    /* ---------------------------------------------------------------
       Formularios: validación en el navegador y envío a api/enviar.php.
       El script de Cloudflare Turnstile se carga solo cuando el formulario
       está por verse, para no frenar la carga de la página.
       --------------------------------------------------------------- */
    var cargarTurnstile = function () {
        if (cargarTurnstile.hecho) return;
        cargarTurnstile.hecho = true;
        window.duanTurnstile = function () {
            $$('[data-turnstile]').forEach(function (caja) {
                caja.dataset.widget = window.turnstile.render(caja, {
                    sitekey: caja.getAttribute('data-turnstile'),
                    language: 'es',
                    appearance: 'interaction-only'
                });
            });
        };
        var s = document.createElement('script');
        s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=duanTurnstile';
        s.async = true;
        document.head.appendChild(s);
    };
    if ($('[data-turnstile]')) {
        if ('IntersectionObserver' in window) {
            var vigia = new IntersectionObserver(function (entradas) {
                if (entradas.some(function (en) { return en.isIntersecting; })) {
                    vigia.disconnect();
                    cargarTurnstile();
                }
            }, { rootMargin: '400px' });
            $$('[data-formulario]').forEach(function (f) { vigia.observe(f); });
        } else {
            cargarTurnstile();
        }
    }

    $$('[data-formulario]').forEach(function (form) {
        var estado = $('.formulario__estado', form);
        var boton = $('button[type="submit"]', form);
        var caja = $('[data-turnstile]', form);
        form.addEventListener('focusin', cargarTurnstile);
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var invalidos = $$('input, select, textarea', form).filter(function (c) {
                var ok = c.checkValidity();
                c.setAttribute('aria-invalid', ok ? 'false' : 'true');
                return !ok;
            });
            if (invalidos.length) {
                estado.className = 'formulario__estado';
                estado.textContent = 'Revise los campos marcados: ' + invalidos.map(function (c) {
                    var l = form.querySelector('label[for="' + c.id + '"]');
                    return l ? l.textContent.replace('*', '').trim() : c.name;
                }).join(', ') + '.';
                invalidos[0].focus();
                return;
            }
            if (!window.fetch) { form.submit(); return; }

            var textoBoton = boton.textContent;
            boton.disabled = true;
            boton.textContent = 'Enviando…';
            estado.className = 'formulario__estado';
            estado.textContent = '';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            }).then(function (r) {
                return r.json();
            }).then(function (res) {
                estado.className = 'formulario__estado' + (res.ok ? ' es-exito' : '');
                estado.textContent = res.mensaje;
                if (res.ok) {
                    form.reset();
                    // Aviso para Google Tag Manager: solo envíos que llegaron bien (activador «Evento personalizado»)
                    var tipo = form.elements.formulario ? form.elements.formulario.value : '';
                    (window.dataLayer = window.dataLayer || []).push({ event: 'formulario_enviado', formulario: tipo });
                }
            }).catch(function () {
                estado.className = 'formulario__estado';
                estado.innerHTML = 'No pudimos enviar su mensaje. Escríbanos por ' +
                    '<a href="https://api.whatsapp.com/send?phone=524775514226" target="_blank" rel="noopener">WhatsApp</a> ' +
                    'o al correo <a href="mailto:info@duanbienesraices.com">info@duanbienesraices.com</a>.';
            }).then(function () {
                boton.disabled = false;
                boton.textContent = textoBoton;
                // Cada token de Turnstile sirve una sola vez
                if (caja && caja.dataset.widget && window.turnstile) window.turnstile.reset(caja.dataset.widget);
            });
        });
    });

    /* ---------------------------------------------------------------
       Botón "volver arriba"
       --------------------------------------------------------------- */
    var subir = $('.subir');
    if (subir) {
        var revisar = function () { subir.classList.toggle('es-visible', window.scrollY > 700); };
        window.addEventListener('scroll', revisar, { passive: true });
        revisar();
        // Sube al inicio de la página. No se usa un ancla al encabezado: como es fijo siempre está
        // a la vista y el navegador no se movía.
        subir.addEventListener('click', function (ev) {
            ev.preventDefault();
            window.scrollTo({ top: 0, behavior: reducirMovimiento ? 'auto' : 'smooth' });
        });
    }
    // --- Mapa de la zona en las fichas (components/mapa-zona.php) ---
    // Leaflet + OpenStreetMap se cargan solo cuando el mapa está por verse. El círculo de «zona
    // aproximada» es una capa del mapa, así que se queda en su lugar al arrastrar y escala con el zoom.
    // Si Leaflet no carga, se pone el mapa de Google de respaldo (sin círculo).
    var mapasZona = $$('[data-mapa-zona]');
    if (mapasZona.length) {
        var leafletListo = null;
        var cargarLeaflet = function (caja) {
            if (!leafletListo) {
                leafletListo = new Promise(function (listo, falla) {
                    var pendientes = 2;
                    var uno = function () { if (--pendientes === 0) listo(); };
                    var css = document.createElement('link');
                    css.rel = 'stylesheet'; css.href = caja.getAttribute('data-leaflet-css');
                    css.onload = uno; css.onerror = falla;
                    var js = document.createElement('script');
                    js.src = caja.getAttribute('data-leaflet-js');
                    js.onload = uno; js.onerror = falla;
                    document.head.appendChild(css);
                    document.head.appendChild(js);
                });
            }
            return leafletListo;
        };
        var pintarMapa = function (caja) {
            cargarLeaflet(caja).then(function () {
                var L = window.L;
                var centro = L.latLng(parseFloat(caja.getAttribute('data-lat')), parseFloat(caja.getAttribute('data-lng')));
                var radio = parseFloat(caja.getAttribute('data-radio')) || 800;
                var animar = !reducirMovimiento;
                var mapa = L.map(caja, {
                    scrollWheelZoom: false,          // la rueda sigue desplazando la página
                    zoomAnimation: animar, fadeAnimation: animar, markerZoomAnimation: animar
                });
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
                }).addTo(mapa);
                // Primero la vista (el círculo necesita una para medirse) y luego el círculo
                mapa.fitBounds(centro.toBounds(radio * 2), { padding: [28, 28], maxZoom: 16 });
                L.circle(centro, {
                    radius: radio, color: '#e21c21', weight: 2, opacity: .75,
                    fillColor: '#e21c21', fillOpacity: .14, interactive: false
                }).addTo(mapa);
            }).catch(function () {
                var marco = document.createElement('iframe');
                marco.src = caja.getAttribute('data-respaldo');
                marco.title = caja.getAttribute('aria-label') || 'Mapa de la zona';
                marco.loading = 'lazy';
                caja.innerHTML = '';
                caja.appendChild(marco);
            });
        };
        if ('IntersectionObserver' in window) {
            var vigiaMapa = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (en) {
                    if (!en.isIntersecting) return;
                    vigiaMapa.unobserve(en.target);
                    pintarMapa(en.target);
                });
            }, { rootMargin: '400px 0px' });
            mapasZona.forEach(function (caja) { vigiaMapa.observe(caja); });
        } else {
            mapasZona.forEach(pintarMapa);
        }
    }

    // --- Publicación de Instagram del fundador (components/fundador.php) ---
    // El embed oficial avisa su alto con mensajes {type: "MEASURE", details: {height}}; con eso se
    // ajusta el iframe para que no tenga barra propia, sin cargar el script de Instagram. Mientras no
    // llega (o si nunca llega) se estima con el ancho: foto 4:5 + 184 px de encabezado y pie. Como
    // Instagram no admite fotos más altas que 4:5, ese es el alto máximo: se usa el menor de los dos,
    // porque con la sesión de Instagram abierta el embed reporta un área en blanco de más.
    // El marco esconde los últimos 45 px, que son la fila «Agrega un comentario…».
    var marcosInstagram = $$('[data-instagram-marco]');
    if (marcosInstagram.length) {
        var PIE_INSTAGRAM = 45;
        var altoInstagram = function (marco, alto) {
            marco.querySelector('iframe').style.height = alto + 'px';
            marco.style.height = (alto - PIE_INSTAGRAM) + 'px';
        };
        var maximoInstagram = function (m) { return Math.round(m.clientWidth * 1.25 + 184); };
        var estimarInstagram = function () {
            marcosInstagram.forEach(function (m) {
                if (m.clientWidth) altoInstagram(m, maximoInstagram(m));
            });
        };
        estimarInstagram();
        var anchoPrevio = window.innerWidth;
        window.addEventListener('resize', function () {
            if (window.innerWidth === anchoPrevio) return;   // en celular la barra del navegador cambia el alto
            anchoPrevio = window.innerWidth;
            estimarInstagram();
        });
        window.addEventListener('message', function (ev) {
            if (ev.origin !== 'https://www.instagram.com') return;
            var datos = ev.data;
            if (typeof datos === 'string') {
                try { datos = JSON.parse(datos); } catch (e) { return; }
            }
            if (!datos || datos.type !== 'MEASURE' || !datos.details) return;
            var alto = parseInt(datos.details.height, 10);
            if (!alto || alto < 200 || alto > 3000) return;
            marcosInstagram.forEach(function (m) {
                if (m.querySelector('iframe').contentWindow === ev.source) {
                    altoInstagram(m, m.clientWidth ? Math.min(alto, maximoInstagram(m)) : alto);
                }
            });
        });
    }

    // --- Animación de entrada en celular: al terminar se quita y no se repite en la visita ---
    var intro = $('[data-intro]');
    if (intro) {
        var quitar = function () {
            if (intro.parentNode) intro.parentNode.removeChild(intro);
            document.documentElement.classList.add('intro-vista');
        };
        if (getComputedStyle(intro).display === 'none') {
            quitar();
        } else {
            try { sessionStorage.setItem('duan-intro', '1'); } catch (e) { /* modo privado */ }
            intro.addEventListener('animationend', function (ev) {
                if (ev.animationName === 'intro-fuera') quitar();
            });
            setTimeout(quitar, 4000);   // por si el navegador no dispara animationend
        }
    }
})();
