/**
 * Indicador visual camión + cronómetro en obra.
 * Colores según demora vs tiempo promedio del motivo (tabla tiempo_promedio_motivo).
 *
 * Verde:    < 75% del promedio
 * Amarillo: 75% – 125% del promedio (zona esperada)
 * Rojo:     > 125% del promedio
 */
const ObraCronometroUtil = {
    UMBRAL_VERDE_RATIO: 0.75,
    UMBRAL_ROJO_RATIO: 1.25,
    TIEMPO_DEFAULT_MINUTOS: 15,

    indexarPromediosMotivo(promedios) {
        const map = {};
        (promedios || []).forEach((p) => {
            if (p?.motivo) {
                map[p.motivo] = p;
            }
        });
        return map;
    },

    promedioMinutosMotivo(promediosMap, motivo) {
        const clave = (motivo || '').trim();
        if (clave && promediosMap?.[clave]) {
            const registro = promediosMap[clave];
            const promedio = parseFloat(registro.tiempo_promedio_minutos);
            if (!Number.isNaN(promedio) && promedio > 0) {
                return promedio;
            }
            const defecto = parseInt(registro.tiempo_default_minutos, 10);
            if (!Number.isNaN(defecto) && defecto > 0) {
                return defecto;
            }
        }
        return this.TIEMPO_DEFAULT_MINUTOS;
    },

    nivelDemoraObra(elapsedMs, promedioMinutos) {
        const elapsedMin = Math.max(0, Number(elapsedMs) || 0) / 60000;
        const promedio = Math.max(1, Number(promedioMinutos) || this.TIEMPO_DEFAULT_MINUTOS);
        const ratio = elapsedMin / promedio;
        if (ratio < this.UMBRAL_VERDE_RATIO) {
            return 'verde';
        }
        if (ratio <= this.UMBRAL_ROJO_RATIO) {
            return 'amarillo';
        }
        return 'rojo';
    },

    /** Convierte tiempoEstimado de ruta (HH:MM:SS) a minutos; null si no es usable. */
    tiempoEstimadoRutaMinutos(tiempoEstimado) {
        if (!tiempoEstimado) {
            return null;
        }
        const partes = String(tiempoEstimado).trim().split(':');
        if (partes.length < 2) {
            return null;
        }
        const horas = parseInt(partes[0], 10) || 0;
        const minutos = parseInt(partes[1], 10) || 0;
        const total = (horas * 60) + minutos;
        return total > 0 ? total : null;
    },

    /** Nivel de demora de la hoja en ejecución vs tiempoEstimado guardado en la ruta. */
    nivelDemoraEjecucionRuta(elapsedMs, tiempoEstimado) {
        const estimadoMin = this.tiempoEstimadoRutaMinutos(tiempoEstimado);
        if (estimadoMin == null) {
            return null;
        }
        return this.nivelDemoraObra(elapsedMs, estimadoMin);
    },

    clasesBadgeCronometroEjecucionRuta(nivel) {
        if (nivel !== 'verde' && nivel !== 'amarillo' && nivel !== 'rojo') {
            return 'badge bg-dark font-monospace cronometro-ejecucion cronometro-badge-con-ico';
        }
        return [
            'badge',
            'font-monospace',
            'cronometro-ejecucion',
            'cronometro-badge-con-ico',
            'ruta-secuencia-crono-reparacion',
            this.claseListaCronoObra(nivel, false),
        ].join(' ');
    },

  /** SVG: trazo de ruta en escuadra (similar a línea de recorrido en mapa). */
    SVG_ICONO_RUTA: '<svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',

    htmlIconoCronometroBadge(tipo) {
        if (tipo === 'ruta') {
            return this.SVG_ICONO_RUTA;
        }
        return '<i class="bi bi-truck cronometro-badge-ico" aria-hidden="true"></i>';
    },

    /** HTML interno: icono + texto para badges de cronómetro en lista/popup. */
    htmlContenidoCronometroBadge(tiempoText, tipo) {
        const texto = tiempoText != null && String(tiempoText).trim() !== '' ? String(tiempoText) : '—';
        return `${this.htmlIconoCronometroBadge(tipo)}<span class="cronometro-badge-txt">${texto}</span>`;
    },

    htmlSpanCronometroBadge(clases, tiempoText, tipo, attrsHtml = '') {
        return `<span class="cronometro-badge-con-ico ${clases}" ${attrsHtml}>${this.htmlContenidoCronometroBadge(tiempoText, tipo)}</span>`;
    },

    asegurarIconoCronometroBadge(el, tipo) {
        if (!el) {
            return null;
        }
        el.classList.add('cronometro-badge-con-ico');
        let txt = el.querySelector('.cronometro-badge-txt');
        let ico = el.querySelector('.cronometro-badge-ico');
        const icoEsRuta = ico?.tagName?.toLowerCase() === 'svg';
        const tipoCorrecto = tipo === 'ruta' ? icoEsRuta : ico?.tagName?.toLowerCase() === 'i';

        if (!txt || !tipoCorrecto) {
            const plain = txt
                ? txt.textContent
                : (el.textContent || '').trim() || '—';
            el.innerHTML = this.htmlContenidoCronometroBadge(plain, tipo);
            txt = el.querySelector('.cronometro-badge-txt');
        }

        return txt;
    },

    actualizarTextoCronometroBadge(el, texto, tipo) {
        const txt = this.asegurarIconoCronometroBadge(el, tipo);
        if (txt) {
            txt.textContent = texto != null && String(texto).trim() !== '' ? String(texto) : '—';
        }
    },

    sincronizarClasesCronometroEjecucionRuta(el, nivel, extraClass = '') {
        if (!el) {
            return;
        }
        el.classList.remove(
            'bg-dark',
            'ruta-secuencia-crono-reparacion',
            'ruta-secuencia-crono-reparacion--verde',
            'ruta-secuencia-crono-reparacion--amarillo',
            'ruta-secuencia-crono-reparacion--rojo'
        );
        el.classList.add('badge', 'font-monospace', 'cronometro-ejecucion', 'cronometro-badge-con-ico');
        (extraClass || '').split(/\s+/).filter(Boolean).forEach((c) => el.classList.add(c));
        if (nivel === 'verde' || nivel === 'amarillo' || nivel === 'rojo') {
            el.classList.add('ruta-secuencia-crono-reparacion');
            el.classList.add(`ruta-secuencia-crono-reparacion--${nivel}`);
        } else {
            el.classList.add('bg-dark');
        }
        this.asegurarIconoCronometroBadge(el, 'ruta');
    },

    coloresNivelObra(nivel) {
        if (nivel === 'verde') {
            return { borde: '#28a745', texto: '#7ddea0' };
        }
        if (nivel === 'rojo') {
            return { borde: '#dc3545', texto: '#ff8f9a' };
        }
        return { borde: '#ffc107', texto: '#ffc107' };
    },

    claseListaCronoObra(nivel, pausado = false) {
        const n = nivel === 'verde' || nivel === 'amarillo' || nivel === 'rojo' ? nivel : 'amarillo';
        let cls = `ruta-secuencia-crono-reparacion--${n}`;
        if (pausado) {
            cls += ' ruta-secuencia-crono-reparacion--pausado';
        }
        return cls;
    },

    sincronizarClasesNivelCronoObra(el, claseNivel) {
        if (!el) {
            return;
        }
        el.classList.remove(
            'bg-dark',
            'ruta-secuencia-crono-reparacion--verde',
            'ruta-secuencia-crono-reparacion--amarillo',
            'ruta-secuencia-crono-reparacion--rojo',
            'ruta-secuencia-crono-reparacion--pausado'
        );
        el.classList.add('ruta-secuencia-crono-reparacion', 'cronometro-badge-con-ico');
        (claseNivel || '').split(/\s+/).filter(Boolean).forEach((c) => el.classList.add(c));
        this.asegurarIconoCronometroBadge(el, 'reclamo');
    },

    crearElementoIndicadorObraMapbox(hms, nivel) {
        const { borde, texto } = this.coloresNivelObra(nivel);
        const wrap = document.createElement('div');
        wrap.className = 'indicador-obra-camion';
        wrap.style.cssText = `display:flex;align-items:center;gap:4px;background:#212529;border:2px solid ${borde};border-radius:8px;padding:2px 8px;box-shadow:0 2px 6px rgba(0,0,0,.35);pointer-events:none;`;
        const truck = document.createElement('i');
        truck.className = 'bi bi-truck indicador-obra-camion-ico';
        truck.style.cssText = `font-size:13px;line-height:1;color:${texto};`;
        truck.setAttribute('aria-hidden', 'true');
        const span = document.createElement('span');
        span.className = 'cron-obra-hms';
        span.style.cssText = `font-family:monospace;font-size:11px;color:${texto};font-weight:600;`;
        span.textContent = hms;
        wrap.appendChild(truck);
        wrap.appendChild(span);
        return { wrap, span };
    },

    actualizarIndicadorObraMapbox(wrap, span, hms, nivel) {
        const { borde, texto } = this.coloresNivelObra(nivel);
        if (wrap) {
            wrap.style.borderColor = borde;
            const ico = wrap.querySelector('.indicador-obra-camion-ico');
            if (ico) {
                ico.style.color = texto;
            }
        }
        if (span) {
            span.textContent = hms;
            span.style.color = texto;
        }
    },

    quitarCompanionObraGoogle(ref) {
        if (!ref) {
            return;
        }
        if (ref.overlay) {
            ref.overlay.setMap(null);
        }
    },

    crearCompanionObraGoogleOverlay(latLng, map, hms, nivel) {
        const { wrap, span } = this.crearElementoIndicadorObraMapbox(hms, nivel);
        wrap.style.whiteSpace = 'nowrap';

        const overlay = new google.maps.OverlayView();
        let div = null;
        overlay.onAdd = function () {
            div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.transform = 'translate(-4px, -50%)';
            div.appendChild(wrap);
            const panes = this.getPanes();
            (panes?.floatPane || panes?.overlayLayer).appendChild(div);
        };
        overlay.draw = function () {
            const proj = this.getProjection();
            if (!proj || !div) {
                return;
            }
            const point = proj.fromLatLngToDivPixel(latLng);
            if (point) {
                div.style.left = point.x + 'px';
                div.style.top = point.y + 'px';
            }
        };
        overlay.onRemove = function () {
            if (div?.parentNode) {
                div.parentNode.removeChild(div);
            }
            div = null;
        };
        overlay.setMap(map);

        return { overlay, span, wrap };
    }
};
