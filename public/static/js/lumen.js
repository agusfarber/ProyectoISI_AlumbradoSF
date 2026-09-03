(function () {
    const ROLES = { '1': 'admin', '2': 'supervisor', '3': 'operario' };
    const STOP = new Set([
        'el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'al', 'en', 'y', 'o',
        'que', 'como', 'para', 'por', 'con', 'mi', 'me', 'se', 'es', 'son', 'hay',
        'the', 'a'
    ]);
    const EMOJI_TEMA = {
        ingreso: '🔑',
        rol: '🧭',
        usuarios: '👥',
        frecuentes: '❓',
        cuadrillas: '👷',
        reclamos: '📋',
        materiales: '📦',
        mapa: '🗺️',
        rutas: '🛣️',
        cierre: '✅',
        analisis: '📊',
        notas: '📝',
        tareas: '☑️',
        gestion: '🛡️',
        ejecucion: '▶️',
        obra: '🔧',
        estados: '🚩'
    };

    let catalogo = null;
    let cierreIdx = 0;
    let vista = 'temas';
    let temaActual = null;
    let preguntaActual = null;
    let yaBindeado = false;
    let hablaGen = 0;

    function rolActual() {
        return String(window.USER_ROLE || '');
    }

    function estaAutenticado() {
        return Object.prototype.hasOwnProperty.call(ROLES, rolActual());
    }

    function nombreUsuario() {
        const n = String(window.USER_NAME || '').trim();
        return n;
    }

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function fold(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9ñ\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokens(texto) {
        return fold(texto).split(' ').filter((t) => t.length >= 3 && !STOP.has(t));
    }

    function preguntasDelRol() {
        const rol = rolActual();
        return ((catalogo && catalogo.preguntas) || []).filter((p) => String(p.rol) === rol);
    }

    function temasDelRol() {
        return (catalogo && catalogo.temas && catalogo.temas[rolActual()]) || [];
    }

    function buscarSugerencias(query) {
        const q = fold(query);
        if (q.length < 2) return [];
        const qTokens = tokens(q);
        const scored = preguntasDelRol().map((p) => {
            const haystackPreg = fold(p.pregunta);
            const haystackKey = fold(p.keywords || '');
            const haystackResp = fold(p.respuesta || '');
            let score = 0;
            if (haystackPreg.includes(q) || haystackKey.includes(q)) score += 12;
            qTokens.forEach((t) => {
                if (haystackKey.split(' ').includes(t)) score += 6;
                if (haystackPreg.includes(t)) score += 3;
                if (haystackResp.includes(t)) score += 1;
            });
            return { p, score };
        }).filter((x) => x.score > 0);
        scored.sort((a, b) => b.score - a.score);
        return scored.slice(0, 6).map((x) => x.p);
    }

    function siguienteCierre() {
        const lista = catalogo.cierres || [];
        if (!lista.length) return '';
        const msg = lista[cierreIdx % lista.length];
        cierreIdx += 1;
        return msg;
    }

    function htmlEscape(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function horaActual() {
        const d = new Date();
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function htmlMetaBot() {
        return `<p class="lumen-msg__meta">Lúmen • Asistente • ${htmlEscape(horaActual())}</p>`;
    }

    function mediaUrl(url) {
        if (!url) return '';
        if (/^https?:\/\//i.test(url) || url.charAt(0) === '/') return url;
        const base = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/');
        return base.replace(/\/?$/, '/') + url.replace(/^\//, '');
    }

    function esVideo(url) {
        return /\.(mp4|webm|mov|m4v|ogg)(\?|$)/i.test(String(url || ''));
    }

    function htmlMediaRespuesta(p) {
        const url = String((p && p.recurso_url) || '').trim();
        if (!url) return '';
        const src = htmlEscape(mediaUrl(url));
        const alt = htmlEscape(p.recurso_alt || p.pregunta || '');
        if (esVideo(url)) {
            return `<div class="lumen-msg__media lumen-msg__media--video" role="button" title="Ampliar"><video src="${src}" autoplay muted loop playsinline aria-label="${alt}"></video></div>`;
        }
        return `<div class="lumen-msg__media" role="button" title="Ampliar"><img src="${src}" alt="${alt}"></div>`;
    }

    function htmlEnlaceRespuesta(p) {
        const texto = String((p && p.enlace_texto) || '').trim();
        if (!texto) return '';
        const url = mediaUrl(String((p && p.enlace_url) || (catalogo && catalogo.manual_url) || '').trim());
        if (!url) return '';
        return `<p class="lumen-msg__link"><a href="${htmlEscape(url)}" target="_blank" rel="noopener noreferrer">${htmlEscape(texto)}</a></p>`;
    }

    function reduceMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function nuevaHabla() {
        hablaGen += 1;
        return hablaGen;
    }

    function sigueHabla(gen) {
        return gen === hablaGen;
    }

    function esperar(ms, gen) {
        return new Promise((resolve) => {
            setTimeout(() => resolve(sigueHabla(gen)), ms);
        });
    }

    function setEstado(escribiendo) {
        const panel = $('#lumenPanel');
        const status = $('#lumenStatus');
        if (panel) panel.classList.toggle('is-typing', !!escribiendo);
        if (status) {
            status.textContent = escribiendo
                ? 'Escribiendo…'
                : 'Asistente de la plataforma';
        }
    }

    function actualizarAtras() {
        const btn = $('#lumenBack');
        if (!btn) return;
        btn.hidden = vista === 'temas';
    }

    function hilo() {
        return $('#lumenBody');
    }

    function scrollCuerpo() {
        const body = hilo();
        if (body) body.scrollTop = body.scrollHeight;
    }

    function appendHtml(html) {
        const body = hilo();
        if (!body) return null;
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const first = wrap.firstElementChild;
        while (wrap.firstChild) body.appendChild(wrap.firstChild);
        scrollCuerpo();
        return first;
    }

    function quitarPills() {
        const body = hilo();
        if (!body) return;
        body.querySelectorAll('.lumen-pills').forEach((el) => el.remove());
    }

    function quitarTyping() {
        const el = $('#lumenTyping');
        if (el) el.remove();
    }

    async function tipear(el, texto, gen) {
        if (!el) return;
        const completo = String(texto || '');
        if (reduceMotion()) {
            el.textContent = completo;
            el.classList.remove('is-typing');
            return;
        }
        el.classList.add('is-typing');
        el.textContent = '';
        let i = 0;
        while (i < completo.length && sigueHabla(gen)) {
            const ch = completo.charAt(i);
            const step = (completo.length > 220 && ch !== ' ' && '.!?'.indexOf(ch) === -1) ? 2 : 1;
            el.textContent += completo.slice(i, i + step);
            i += step;
            let delay = 14 + Math.random() * 16;
            if ('.!?'.indexOf(ch) !== -1) delay = 150 + Math.random() * 80;
            else if (',;:'.indexOf(ch) !== -1) delay = 50 + Math.random() * 40;
            if (!(await esperar(delay, gen))) return;
            if (i % 8 === 0) scrollCuerpo();
        }
        el.classList.remove('is-typing');
        if (sigueHabla(gen)) el.textContent = completo;
        scrollCuerpo();
    }

    function textoSaludo() {
        const nombre = nombreUsuario();
        if (nombre) {
            return 'Hola, ' + nombre + '. Soy Lúmen, tu asistente de la plataforma. ¿En qué te puedo ayudar?';
        }
        return 'Hola, soy Lúmen, tu asistente de la plataforma. ¿En qué te puedo ayudar?';
    }

    function htmlPillsTemas() {
        return '<div class="lumen-pills">' + temasDelRol().map((t, i) => (
            `<button type="button" class="lumen-pill" style="--n:${i}" data-lumen-tema="${htmlEscape(t.id)}"><span class="lumen-pill__emoji">${EMOJI_TEMA[t.id] || '💡'}</span>${htmlEscape(t.nombre)}</button>`
        )).join('') + '</div>';
    }

    function htmlPillsPreguntas(temaId) {
        const lista = preguntasDelRol().filter((p) => p.tema === temaId);
        return htmlPillsPreguntasLista(lista);
    }

    function htmlPillsPreguntasLista(lista) {
        if (!lista.length) {
            return '<div class="lumen-pills"><p class="lumen-vacio">No encontré una pregunta parecida.</p></div>';
        }
        return '<div class="lumen-pills">' + lista.map((p, i) => (
            `<button type="button" class="lumen-pill" style="--n:${i}" data-lumen-q="${htmlEscape(p.id)}"><span class="lumen-pill__emoji">💬</span>${htmlEscape(p.pregunta)}</button>`
        )).join('') + '</div>';
    }

    function htmlPillsSeguir() {
        return `<div class="lumen-pills">
            ${temaActual ? `<button type="button" class="lumen-pill" style="--n:0" data-lumen-back="preguntas"><span class="lumen-pill__emoji">↩️</span>Otras de este tema</button>` : ''}
            <button type="button" class="lumen-pill" style="--n:1" data-lumen-back="temas"><span class="lumen-pill__emoji">🗂️</span>Ver temas</button>
        </div>`;
    }

    function revelarExtras(textoEl) {
        const bubble = textoEl && textoEl.closest('.lumen-msg__bubble');
        if (!bubble) return;

        bubble.querySelectorAll('.lumen-msg__media').forEach((el) => {
            const img = el.querySelector('img');
            const video = el.querySelector('video');
            const mostrar = () => {
                requestAnimationFrame(() => el.classList.add('is-in'));
            };
            if (video) {
                if (video.readyState >= 2) {
                    mostrar();
                    return;
                }
                video.addEventListener('loadeddata', mostrar, { once: true });
                video.addEventListener('error', mostrar, { once: true });
                return;
            }
            if (!img || (img.complete && img.naturalWidth)) {
                mostrar();
                return;
            }
            img.addEventListener('load', mostrar, { once: true });
            img.addEventListener('error', mostrar, { once: true });
        });

        const delay = reduceMotion() ? 0 : (bubble.querySelector('.lumen-msg__media') ? 360 : 180);
        bubble.querySelectorAll('.lumen-msg__cierre, .lumen-msg__link').forEach((el) => {
            if (reduceMotion()) {
                el.classList.add('is-in');
                return;
            }
            setTimeout(() => {
                requestAnimationFrame(() => el.classList.add('is-in'));
            }, delay);
        });
    }

    function appendUser(texto) {
        appendHtml(`<div class="lumen-msg lumen-msg--user"><div class="lumen-msg__bubble">${htmlEscape(texto)}</div></div>`);
    }

    function appendTyping() {
        appendHtml(`<div class="lumen-msg lumen-msg--bot" id="lumenTyping"><div class="lumen-msg__bubble lumen-msg__bubble--typing" aria-label="Lúmen está escribiendo"><span></span><span></span><span></span></div></div>`);
    }

    async function hablarBot(texto, pillsHtml, extraHtml) {
        const gen = nuevaHabla();
        setEstado(true);
        appendTyping();
        const espera = reduceMotion() ? 0 : 520 + Math.random() * 420;
        if (!(await esperar(espera, gen))) return false;
        quitarTyping();
        if (!sigueHabla(gen)) return false;
        const id = 'lumenTxt' + gen;
        appendHtml(`<div class="lumen-msg lumen-msg--bot">
            <div class="lumen-msg__bubble">
                <p class="lumen-msg__text" id="${id}"></p>
            </div>
            ${htmlMetaBot()}
        </div>`);
        await tipear($('#' + id), texto, gen);
        if (!sigueHabla(gen)) return false;
        const textoEl = $('#' + id);
        if (textoEl && extraHtml) {
            textoEl.insertAdjacentHTML('afterend', extraHtml);
            revelarExtras(textoEl);
        }
        if (pillsHtml) appendHtml(pillsHtml);
        setEstado(false);
        scrollCuerpo();
        return true;
    }

    async function mostrarTemas(saludar) {
        const body = hilo();
        if (!body) return;
        vista = 'temas';
        temaActual = null;
        preguntaActual = null;
        actualizarAtras();
        if (!catalogo) {
            body.innerHTML = '<div class="lumen-msg lumen-msg--bot"><div class="lumen-msg__bubble"><p class="lumen-msg__text">Cargando preguntas…</p></div></div>';
            return;
        }
        body.innerHTML = '';
        if (saludar) {
            await hablarBot(textoSaludo(), htmlPillsTemas());
            return;
        }
        appendHtml(`<div class="lumen-msg lumen-msg--bot">
            <div class="lumen-msg__bubble"><p class="lumen-msg__text">${htmlEscape(textoSaludo())}</p></div>
            ${htmlMetaBot()}
        </div>`);
        appendHtml(htmlPillsTemas());
        setEstado(false);
    }

    async function elegirTema(id) {
        const tema = temasDelRol().find((t) => t.id === id);
        if (!tema) return;
        temaActual = tema;
        vista = 'preguntas';
        preguntaActual = null;
        const input = $('#lumenSearch');
        if (input) input.value = '';
        quitarPills();
        appendUser(tema.nombre);
        actualizarAtras();
        await hablarBot(
            'Estas son las consultas de ' + tema.nombre + '. Tocá una para que te la explique.',
            htmlPillsPreguntas(tema.id)
        );
    }

    async function abrirPregunta(id) {
        const p = preguntasDelRol().find((x) => x.id === id);
        if (!p) return;
        preguntaActual = { ...p, _cierre: siguienteCierre() };
        const tema = temasDelRol().find((t) => t.id === p.tema);
        if (tema) temaActual = tema;
        vista = 'respuesta';
        const input = $('#lumenSearch');
        if (input) input.value = '';
        const fab = $('#lumenFab');
        if (fab) fab.classList.add('is-on');
        quitarPills();
        appendUser(p.pregunta);
        actualizarAtras();

        const media = htmlMediaRespuesta(p);
        const enlace = htmlEnlaceRespuesta(p);
        const cierre = preguntaActual._cierre
            ? `<p class="lumen-msg__cierre">${htmlEscape(preguntaActual._cierre)}</p>`
            : '';
        await hablarBot(p.respuesta, htmlPillsSeguir(), media + enlace + cierre);
    }

    function pillsSegunVista() {
        const q = (($('#lumenSearch') && $('#lumenSearch').value) || '').trim();
        if (q.length >= 2) return htmlPillsPreguntasLista(buscarSugerencias(q));
        if (vista === 'preguntas' && temaActual) return htmlPillsPreguntas(temaActual.id);
        if (vista === 'respuesta') return htmlPillsSeguir();
        return htmlPillsTemas();
    }

    function atras() {
        const input = $('#lumenSearch');
        if (input && input.value.trim()) {
            input.value = '';
            quitarPills();
            appendHtml(pillsSegunVista());
            return;
        }
        if (vista === 'respuesta' && temaActual) {
            vista = 'preguntas';
            preguntaActual = null;
            quitarPills();
            actualizarAtras();
            hablarBot(
                '¿Otra consulta de ' + temaActual.nombre + '?',
                htmlPillsPreguntas(temaActual.id)
            );
            return;
        }
        mostrarTemas(false);
    }

    function videosDelChat() {
        const body = hilo();
        return body ? Array.from(body.querySelectorAll('video')) : [];
    }

    function pausarVideosChat() {
        videosDelChat().forEach((v) => v.pause());
    }

    function reanudarVideosChat() {
        videosDelChat().forEach((v) => {
            v.play().catch(() => {});
        });
    }

    function hayConversacion() {
        const body = hilo();
        if (!body || !body.children.length) return false;
        const txt = body.querySelector('.lumen-msg__text');
        if (
            body.children.length === 1 &&
            txt &&
            /Cargando preguntas/.test(txt.textContent || '')
        ) {
            return false;
        }
        return true;
    }

    function abrir() {
        const panel = $('#lumenPanel');
        const fab = $('#lumenFab');
        if (!panel) return;
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        if (fab) {
            fab.classList.add('is-on');
            fab.setAttribute('aria-expanded', 'true');
        }
        if (hayConversacion()) {
            actualizarAtras();
            reanudarVideosChat();
            return;
        }
        mostrarTemas(true);
        const input = $('#lumenSearch');
        if (input) input.value = '';
    }

    function cerrar() {
        const panel = $('#lumenPanel');
        const fab = $('#lumenFab');
        if (panel) {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        }
        pausarVideosChat();
        cerrarVisor();
        if (fab) {
            fab.classList.remove('is-on');
            fab.setAttribute('aria-expanded', 'false');
        }
    }

    function visorAbierto() {
        const visor = $('#lumenVisor');
        return !!(visor && visor.classList.contains('is-open'));
    }

    function abrirVisor(mediaEl) {
        const visor = $('#lumenVisor');
        const cuerpo = $('#lumenVisorCuerpo');
        if (!visor || !cuerpo || !mediaEl) return;
        const img = mediaEl.querySelector('img');
        const video = mediaEl.querySelector('video');
        cuerpo.innerHTML = '';
        if (video) {
            const copia = document.createElement('video');
            copia.src = video.currentSrc || video.src;
            copia.autoplay = true;
            copia.muted = true;
            copia.loop = true;
            copia.playsInline = true;
            copia.setAttribute('aria-label', video.getAttribute('aria-label') || 'Video');
            cuerpo.appendChild(copia);
            copia.play().catch(() => {});
        } else if (img) {
            const copia = document.createElement('img');
            copia.src = img.currentSrc || img.src;
            copia.alt = img.alt || '';
            cuerpo.appendChild(copia);
        } else {
            return;
        }
        visor.classList.add('is-open');
        visor.setAttribute('aria-hidden', 'false');
    }

    function cerrarVisor() {
        const visor = $('#lumenVisor');
        const cuerpo = $('#lumenVisorCuerpo');
        if (!visor) return;
        visor.classList.remove('is-open');
        visor.setAttribute('aria-hidden', 'true');
        if (cuerpo) {
            cuerpo.querySelectorAll('video').forEach((v) => {
                v.pause();
                v.removeAttribute('src');
                v.load();
            });
            cuerpo.innerHTML = '';
        }
    }

    function toggle() {
        const panel = $('#lumenPanel');
        if (panel && panel.classList.contains('is-open')) cerrar();
        else abrir();
    }

    function bind() {
        const root = $('#lumenRoot');
        if (!root || yaBindeado) return;
        yaBindeado = true;
        root.addEventListener('click', (e) => {
            if (e.target.closest('#lumenVisorCerrar') || e.target.id === 'lumenVisor') {
                e.preventDefault();
                cerrarVisor();
                return;
            }
            if (e.target.closest('#lumenVisor')) return;
            const media = e.target.closest('.lumen-msg__media');
            if (media) {
                e.preventDefault();
                abrirVisor(media);
                return;
            }
            const fab = e.target.closest('#lumenFab');
            if (fab) {
                e.preventDefault();
                toggle();
                return;
            }
            const close = e.target.closest('#lumenClose');
            if (close) {
                e.preventDefault();
                cerrar();
                return;
            }
            const back = e.target.closest('#lumenBack');
            if (back) {
                e.preventDefault();
                atras();
                return;
            }
            const temaBtn = e.target.closest('[data-lumen-tema]');
            if (temaBtn) {
                elegirTema(temaBtn.getAttribute('data-lumen-tema'));
                return;
            }
            const qBtn = e.target.closest('[data-lumen-q]');
            if (qBtn) {
                abrirPregunta(qBtn.getAttribute('data-lumen-q'));
                return;
            }
            const backPill = e.target.closest('[data-lumen-back]');
            if (backPill) {
                const dest = backPill.getAttribute('data-lumen-back') || 'temas';
                if (dest === 'preguntas' && temaActual) {
                    vista = 'preguntas';
                    preguntaActual = null;
                    quitarPills();
                    actualizarAtras();
                    hablarBot(
                        '¿Otra consulta de ' + temaActual.nombre + '?',
                        htmlPillsPreguntas(temaActual.id)
                    );
                    return;
                }
                mostrarTemas(false);
            }
        });
        const input = $('#lumenSearch');
        if (input) {
            input.addEventListener('input', () => {
                nuevaHabla();
                setEstado(false);
                quitarTyping();
                quitarPills();
                appendHtml(pillsSegunVista());
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (visorAbierto()) {
                e.preventDefault();
                cerrarVisor();
                return;
            }
            cerrar();
        });
    }

    function colocarEnBody(root) {
        if (root && root.parentNode !== document.body) {
            document.body.appendChild(root);
        }
    }

    function montar() {
        const root = document.getElementById('lumenRoot');
        if (!root) return;
        colocarEnBody(root);
        bind();
    }

    async function iniciar() {
        if (!estaAutenticado()) return;
        montar();
        [0, 150, 600].forEach((ms) => {
            setTimeout(() => colocarEnBody(document.getElementById('lumenRoot')), ms);
        });
        const base = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/').replace(/\/?$/, '/');
        try {
            const res = await fetch(base + 'static/data/lumen-catalogo.json', { cache: 'no-store' });
            catalogo = await res.json();
            const panel = $('#lumenPanel');
            if (panel && panel.classList.contains('is-open') && !hayConversacion()) {
                mostrarTemas(true);
            }
        } catch (err) {
            console.warn('Lúmen: no se pudo cargar el catálogo', err);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
