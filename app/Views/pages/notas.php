<div id="app" class="notas-page">

    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-journal-text"></i></span>
        <h1 class="app-page-title__text">Notas</h1>
    </div>

    <!-- Composer siempre visible -->
    <form class="nota-composer" @submit.prevent="crearNota">
        <input
            type="text"
            class="nota-composer__titulo"
            v-model.trim="nueva.titulo"
            maxlength="160"
            placeholder="Título (opcional)"
            @keydown.esc="limpiarNueva">
        <textarea
            ref="composerContenido"
            class="nota-composer__contenido"
            v-model.trim="nueva.contenido"
            rows="2"
            placeholder="Escribí una nota… (Ctrl+Enter para guardar)"
            @keydown.ctrl.enter.prevent="crearNota"
            @keydown.meta.enter.prevent="crearNota"
            @keydown.esc="limpiarNueva"></textarea>
        <div class="nota-composer__barra">
            <label class="nota-composer__pin" title="Fijar al inicio">
                <input type="checkbox" v-model="nueva.fijada">
                <i :class="nueva.fijada ? 'bi bi-pin-angle-fill' : 'bi bi-pin-angle'"></i>
                <span>Fijar</span>
            </label>
            <div class="nota-composer__acciones">
                <button v-if="nueva.titulo || nueva.contenido" type="button" class="nota-composer__cancelar" @click="limpiarNueva">
                    Limpiar
                </button>
                <button type="submit" class="btn-nueva nota-composer__guardar" :disabled="guardandoNueva || !(nueva.contenido || '').trim()">
                    <span v-if="guardandoNueva" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-plus-lg"></i>
                    {{ guardandoNueva ? 'Guardando…' : 'Agregar' }}
                </button>
            </div>
        </div>
    </form>

    <div class="notas-toolbar">
        <div class="notas-chips">
            <button type="button" class="notas-chip" :class="{ active: filtro === 'activas' }" @click="setFiltro('activas')">
                Activas
            </button>
            <button type="button" class="notas-chip" :class="{ active: filtro === 'hechas' }" @click="setFiltro('hechas')">
                Hechas
            </button>
            <button type="button" class="notas-chip" :class="{ active: filtro === 'todas' }" @click="setFiltro('todas')">
                Todas
            </button>
        </div>
        <span v-if="!cargando" class="notas-count">
            <template v-if="total > notas.length">{{ notas.length }} de {{ total }}</template>
            <template v-else>{{ total }} nota{{ total === 1 ? '' : 's' }}</template>
        </span>
    </div>

    <div v-if="cargando" class="notas-loading">
        <div class="spinner-border text-secondary" role="status"></div>
        <span>Cargando notas…</span>
    </div>

    <div v-else class="notas-lista">
        <article
            v-for="n in notas"
            :key="n.id"
            class="nota-card"
            :class="{
                'nota-card--hecha': Number(n.hecha) === 1 && editandoId !== n.id,
                'nota-card--fijada': Number(n.fijada) === 1,
                'nota-card--editando': editandoId === n.id
            }">

            <!-- Modo edición inline -->
            <template v-if="editandoId === n.id">
                <input
                    type="text"
                    class="nota-inline__titulo"
                    v-model.trim="borrador.titulo"
                    maxlength="160"
                    placeholder="Título (opcional)"
                    @keydown.esc="cancelarEdicion"
                    @keydown.ctrl.enter.prevent="guardarEdicion"
                    @keydown.meta.enter.prevent="guardarEdicion">
                <textarea
                    ref="editContenido"
                    class="nota-inline__contenido"
                    v-model.trim="borrador.contenido"
                    rows="4"
                    placeholder="Contenido de la nota"
                    @keydown.esc="cancelarEdicion"
                    @keydown.ctrl.enter.prevent="guardarEdicion"
                    @keydown.meta.enter.prevent="guardarEdicion"></textarea>
                <div class="nota-inline__barra">
                    <span class="nota-inline__hint">Esc cancela · Ctrl+Enter guarda</span>
                    <div class="nota-inline__acciones">
                        <button type="button" class="nota-inline__btn nota-inline__btn--ghost" @click="cancelarEdicion" :disabled="guardandoEdicion">
                            Cancelar
                        </button>
                        <button type="button" class="nota-inline__btn nota-inline__btn--primary" @click="guardarEdicion" :disabled="guardandoEdicion || !(borrador.contenido || '').trim()">
                            <span v-if="guardandoEdicion" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check-lg"></i>
                            Guardar
                        </button>
                    </div>
                </div>
            </template>

            <!-- Modo lectura -->
            <template v-else>
                <div class="nota-card__top">
                    <div class="nota-card__heading" @click="iniciarEdicion(n)">
                        <span v-if="Number(n.fijada) === 1" class="nota-card__pin" title="Fijada"><i class="bi bi-pin-angle-fill"></i></span>
                        <h3>{{ n.titulo || 'Sin título' }}</h3>
                    </div>
                    <div class="nota-card__acciones">
                        <button type="button" class="nota-card__btn" :title="Number(n.fijada) === 1 ? 'Desfijar' : 'Fijar'" @click="toggleFijada(n)">
                            <i :class="Number(n.fijada) === 1 ? 'bi bi-pin-angle-fill' : 'bi bi-pin-angle'"></i>
                        </button>
                        <button type="button" class="nota-card__btn" :title="Number(n.hecha) === 1 ? 'Marcar como activa' : 'Marcar como hecha'" @click="toggleHecha(n)">
                            <i :class="Number(n.hecha) === 1 ? 'bi bi-arrow-counterclockwise' : 'bi bi-check2'"></i>
                        </button>
                        <button type="button" class="nota-card__btn" title="Editar" @click="iniciarEdicion(n)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <div class="nota-eliminar">
                            <button
                                type="button"
                                class="nota-card__btn nota-card__btn--danger"
                                title="Eliminar"
                                :class="{ 'is-open': confirmarEliminarId === n.id }"
                                @click.stop="pedirEliminar(n)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <div v-if="confirmarEliminarId === n.id" class="nota-eliminar__pop" @click.stop>
                                <span>¿Eliminar?</span>
                                <button type="button" class="nota-eliminar__si" :disabled="eliminandoId === n.id" @click="confirmarEliminar(n)">
                                    <span v-if="eliminandoId === n.id" class="spinner-border spinner-border-sm"></span>
                                    <template v-else>Sí</template>
                                </button>
                                <button type="button" class="nota-eliminar__no" :disabled="eliminandoId === n.id" @click="cancelarEliminar">No</button>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="nota-card__body" @click="iniciarEdicion(n)">{{ n.contenido }}</p>
                <div class="nota-card__meta">
                    <span v-if="Number(n.hecha) === 1" class="nota-badge nota-badge--hecha">Hecha</span>
                    <span v-else></span>
                    <span>{{ formatearFecha(n.updated_at || n.created_at) }}</span>
                </div>
            </template>
        </article>

        <div v-if="notas.length === 0" class="notas-empty">
            <i class="bi bi-journal-text"></i>
            <p v-if="filtro === 'hechas'">No hay notas marcadas como hechas.</p>
            <p v-else-if="filtro === 'activas'">No hay notas activas. Escribí arriba y tocá Agregar.</p>
            <p v-else>Todavía no hay notas.</p>
        </div>
    </div>

    <div v-if="!cargando && hayMas" class="notas-mas">
        <button type="button" class="notas-mas__btn" :disabled="cargandoMas" @click="cargarMas">
            <span v-if="cargandoMas" class="spinner-border spinner-border-sm"></span>
            <template v-else>Cargar más</template>
        </button>
    </div>
</div>
