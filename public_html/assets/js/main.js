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

    /* Sombra del encabezado fijo al bajar */
    var encabezado = $('.encabezado');
    if (encabezado) {
        var marcarDesplazado = function () { encabezado.classList.toggle('es-desplazado', window.scrollY > 8); };
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
        var usarVideo = window.matchMedia('(min-width: 768px)').matches && !reducirMovimiento &&
            !(navigator.connection && navigator.connection.saveData);
        var automatico = !reducirMovimiento && slides.length > 1;

        function siguiente() { ir(actual + 1); }

        /* Programa el siguiente cambio. Con video: al terminar (empezando el fundido un poco antes
           del final para que el video que sale se desvanezca en movimiento). Sin video: el intervalo.
           Siempre queda un temporizador de respaldo, así el carrusel nunca se queda atorado. */
        function programar() {
            clearTimeout(temporizador);
            if (!automatico) return;
            var v = $('video', slides[actual]);
            if (!v || !usarVideo) { temporizador = setTimeout(siguiente, intervalo); return; }
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
                if (i === actual && usarVideo) {
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
       Carruseles con scroll-snap (testimonios, galería de Nosotros)
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
        if (ant) ant.addEventListener('click', function () { ir(indice - 1); });
        if (sig) sig.addEventListener('click', function () { ir(indice + 1); });

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
       Listado de propiedades: los chips y listas aplican el filtro al
       cambiar; el texto y el precio esperan al botón «Buscar».
       --------------------------------------------------------------- */
    var filtros = $('[data-filtros-propiedades]');
    if (filtros) {
        filtros.addEventListener('change', function (ev) {
            if (ev.target.matches('input[type="radio"], select')) {
                if (filtros.requestSubmit) filtros.requestSubmit(); else filtros.submit();
            }
        });
        // Celular: tipo, ciudad, recámaras y precio se pliegan tras «Más filtros»
        // (abiertos si alguno ya está aplicado). En pantallas anchas el CSS los muestra siempre.
        var botonMas = $('[data-mas-filtros]', filtros);
        if (botonMas) {
            var textoMas = $('[data-mas-texto]', botonMas);
            var plegar = function (plegado) {
                filtros.classList.toggle('es-plegado', plegado);
                botonMas.setAttribute('aria-expanded', plegado ? 'false' : 'true');
                textoMas.textContent = plegado ? 'Más filtros' : 'Menos filtros';
            };
            botonMas.hidden = false;
            plegar(botonMas.getAttribute('data-mas-filtros') === '0');
            botonMas.addEventListener('click', function () {
                plegar(!filtros.classList.contains('es-plegado'));
            });
        }
        // Los campos vacíos no viajan en la URL
        filtros.addEventListener('submit', function () {
            $$('input, select', filtros).forEach(function (c) { if (c.name && c.value === '') c.disabled = true; });
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
