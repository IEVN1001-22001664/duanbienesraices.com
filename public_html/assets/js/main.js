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
        var intervalo = parseInt(hero.getAttribute('data-auto'), 10) || 0;
        var temporizador = null;
        var usarVideo = window.matchMedia('(min-width: 768px)').matches && !reducirMovimiento &&
            !(navigator.connection && navigator.connection.saveData);

        function sincronizarVideo() {
            slides.forEach(function (s, i) {
                var v = $('video', s);
                if (!v) return;
                if (i === actual && usarVideo) {
                    if (!v.src) { v.src = v.getAttribute('data-src'); }
                    var p = v.play();
                    if (p && p.catch) p.catch(function () {});
                } else if (!v.paused) {
                    v.pause();
                }
            });
        }

        var marcar = crearPuntos($('[data-puntos]', hero), slides.length, function (n) { ir(n); reiniciar(); }, 'Ir a la diapositiva');

        function ir(n) {
            slides[actual].classList.remove('es-activa');
            slides[actual].setAttribute('aria-hidden', 'true');
            actual = (n + slides.length) % slides.length;
            slides[actual].classList.add('es-activa');
            slides[actual].removeAttribute('aria-hidden');
            marcar(actual);
            sincronizarVideo();
        }
        function reiniciar() {
            clearInterval(temporizador);
            if (intervalo && !reducirMovimiento && slides.length > 1) {
                temporizador = setInterval(function () { ir(actual + 1); }, intervalo);
            }
        }

        var ant = $('[data-anterior]', hero), sig = $('[data-siguiente]', hero);
        if (ant) ant.addEventListener('click', function () { ir(actual - 1); reiniciar(); });
        if (sig) sig.addEventListener('click', function () { ir(actual + 1); reiniciar(); });
        // Pausa al pasar el mouse o enfocar con teclado (WCAG 2.2.2)
        hero.addEventListener('mouseenter', function () { clearInterval(temporizador); });
        hero.addEventListener('mouseleave', reiniciar);
        hero.addEventListener('focusin', function () { clearInterval(temporizador); });
        hero.addEventListener('focusout', reiniciar);

        marcar(0);
        sincronizarVideo();
        reiniciar();
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
        function mostrar() { if (contador) contador.textContent = String(indice + 1); }
        function ir(n) {
            indice = (n + fotos.length) % fotos.length;
            pista.scrollTo({ left: indice * pista.clientWidth, behavior: reducirMovimiento ? 'auto' : 'smooth' });
            mostrar();
        }
        if (izq) izq.addEventListener('click', function () { ir(indice - 1); });
        if (der) der.addEventListener('click', function () { ir(indice + 1); });
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
       Mapa de Google: se carga al hacer clic
       --------------------------------------------------------------- */
    $$('[data-mapa]').forEach(function (marco) {
        var boton = $('button', marco);
        if (!boton) return;
        boton.addEventListener('click', function () {
            var iframe = document.createElement('iframe');
            iframe.src = marco.getAttribute('data-mapa');
            iframe.title = 'Mapa de ubicación de Du\'an Bienes Raíces';
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
                if (res.ok) form.reset();
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
