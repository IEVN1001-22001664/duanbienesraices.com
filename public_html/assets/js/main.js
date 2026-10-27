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
        function ir(n) {
            var total = paginas();
            n = (n + total) % total;
            pista.scrollTo({ left: items[n].offsetLeft - pista.offsetLeft, behavior: reducirMovimiento ? 'auto' : 'smooth' });
        }

        var contPuntos = $('[data-puntos]', carrusel);
        var marcar = function () {};
        function construirPuntos() {
            if (!contPuntos) return;
            contPuntos.innerHTML = '';
            marcar = crearPuntos(contPuntos, paginas(), ir, 'Ir al elemento');
            marcar(Math.min(indiceActual(), paginas() - 1));
        }
        construirPuntos();

        var ant = $('[data-anterior]', carrusel), sig = $('[data-siguiente]', carrusel);
        if (ant) ant.addEventListener('click', function () { ir(indiceActual() - 1); });
        if (sig) sig.addEventListener('click', function () { ir(indiceActual() + 1); });

        var espera;
        pista.addEventListener('scroll', function () {
            clearTimeout(espera);
            espera = setTimeout(function () { marcar(Math.min(indiceActual(), paginas() - 1)); }, 80);
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

        function actual() { return Math.round(pista.scrollLeft / pista.clientWidth); }
        function ir(n) {
            n = (n + fotos.length) % fotos.length;
            pista.scrollTo({ left: n * pista.clientWidth, behavior: reducirMovimiento ? 'auto' : 'smooth' });
        }
        if (izq) izq.addEventListener('click', function () { ir(actual() - 1); });
        if (der) der.addEventListener('click', function () { ir(actual() + 1); });
        pista.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowLeft') { ev.preventDefault(); ir(actual() - 1); }
            if (ev.key === 'ArrowRight') { ev.preventDefault(); ir(actual() + 1); }
        });
        var espera;
        pista.addEventListener('scroll', function () {
            clearTimeout(espera);
            espera = setTimeout(function () { if (contador) contador.textContent = String(actual() + 1); }, 60);
        }, { passive: true });
    });

    /* ---------------------------------------------------------------
       YouTube "lite embed": el iframe se crea al hacer clic
       --------------------------------------------------------------- */
    $$('[data-youtube]').forEach(function (marco) {
        var enlace = $('.video__play', marco);
        if (!enlace) return;
        enlace.addEventListener('click', function (ev) {
            ev.preventDefault();
            var id = marco.getAttribute('data-youtube');
            if (!/^[\w-]{11}$/.test(id)) return;
            var iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
            iframe.title = marco.getAttribute('data-titulo') || 'Video de YouTube';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            marco.innerHTML = '';
            marco.appendChild(iframe);
            iframe.focus();
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
       Formularios: validación en el navegador.
       Mientras no exista api/enviar.php (Fase 3) se muestra un aviso con WhatsApp.
       --------------------------------------------------------------- */
    $$('[data-formulario]').forEach(function (form) {
        var estado = $('.formulario__estado', form);
        form.addEventListener('submit', function (ev) {
            var invalidos = $$('input, select, textarea', form).filter(function (c) {
                var ok = c.checkValidity();
                c.setAttribute('aria-invalid', ok ? 'false' : 'true');
                return !ok;
            });
            if (invalidos.length) {
                ev.preventDefault();
                estado.className = 'formulario__estado';
                estado.textContent = 'Revise los campos marcados: ' + invalidos.map(function (c) {
                    var l = form.querySelector('label[for="' + c.id + '"]');
                    return l ? l.textContent.replace('*', '').trim() : c.name;
                }).join(', ') + '.';
                invalidos[0].focus();
                return;
            }
            if (form.hasAttribute('data-pendiente')) {
                ev.preventDefault();
                estado.className = 'formulario__estado';
                estado.innerHTML = 'El envío en línea estará disponible muy pronto. Mientras tanto, escríbanos por ' +
                    '<a href="https://api.whatsapp.com/send?phone=524775514226" target="_blank" rel="noopener">WhatsApp</a> ' +
                    'o al correo <a href="mailto:info@duanbienesraices.com">info@duanbienesraices.com</a>.';
            }
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
})();
