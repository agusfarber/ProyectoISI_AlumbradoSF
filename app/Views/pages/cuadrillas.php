<div id="app" class="cuadrillas-page">

    <!-- Título -->
    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-people-fill"></i></span>
        <h1 class="app-page-title__text">Cuadrillas</h1>
    </div>

    <!-- Acciones: botón a la izquierda, buscador a la derecha -->
    <div class="cuadrillas-toolbar">
        <button class="btn-nueva" @click="abrirFormulario()">
            <i class="bi bi-plus-lg"></i> Nueva cuadrilla
        </button>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" v-model="filtroBusqueda" placeholder="Buscar">
            <button v-if="filtroBusqueda" class="search-clear" @click="filtroBusqueda = ''" title="Limpiar">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>

    <!-- Cuadrícula de tarjetas -->
    <div class="cuadrillas-grid">
        <article
            v-for="c in cuadrillasMostradas"
            :key="c.id"
            class="cuadrilla-card"
            @click="verDetalle(c.id)"
            tabindex="0"
            @keyup.enter="verDetalle(c.id)">

            <div class="cuadrilla-card__top">
                <div class="cuadrilla-card__icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="cuadrilla-card__heading">
                    <h3 class="cuadrilla-card__name">{{ c.nombre }}</h3>
                    <span class="cuadrilla-card__count">
                        <i class="bi bi-person"></i>
                        {{ (c.operarios ? c.operarios.length : 0) }}
                        {{ (c.operarios && c.operarios.length === 1) ? 'operario' : 'operarios' }}
                    </span>
                </div>
                <button type="button" class="cuadrilla-card__gear" @click.stop="editarCuadrilla(c.id)" title="Editar cuadrilla">
                    <i class="bi bi-gear"></i>
                </button>
            </div>

            <p class="cuadrilla-card__desc" v-if="c.descripcion">{{ c.descripcion }}</p>
            <p class="cuadrilla-card__desc cuadrilla-card__desc--empty" v-else>Sin descripción</p>

            <div class="cuadrilla-card__footer" v-if="c.operarios && c.operarios.length">
                <div class="avatar-stack">
                    <template v-for="op in c.operarios.slice(0, 5)" :key="op.id">
                        <img
                            v-if="op.foto_perfil"
                            class="avatar avatar--img"
                            :class="{ 'avatar--jefe': Number(op.es_jefe) === 1 }"
                            :src="urlFoto(op.foto_perfil)"
                            :alt="op.nombre"
                            :title="op.nombre + (Number(op.es_jefe) === 1 ? ' (Gestión)' : '')">
                        <span
                            v-else
                            class="avatar"
                            :class="{ 'avatar--jefe': Number(op.es_jefe) === 1 }"
                            :style="{ backgroundColor: colorAvatar(op.nombre) }"
                            :title="op.nombre + (Number(op.es_jefe) === 1 ? ' (Gestión)' : '')">
                            {{ iniciales(op.nombre) }}
                        </span>
                    </template>
                    <span v-if="c.operarios.length > 5" class="avatar avatar--more">
                        +{{ c.operarios.length - 5 }}
                    </span>
                </div>
                <span v-if="operariosConGestion(c).length" class="jefe-line" :title="operariosConGestion(c).map(op => op.nombre).join(', ')">
                    <i class="bi bi-person-badge-fill"></i> {{ etiquetaGestionCuadrilla(c) }}
                </span>
            </div>
            <div class="cuadrilla-card__footer cuadrilla-card__footer--empty" v-else>
                <i class="bi bi-info-circle"></i> Sin operarios asignados
            </div>
        </article>

        <!-- Estado vacío -->
        <div v-if="cuadrillasMostradas.length === 0" class="cuadrillas-empty">
            <i class="bi bi-people"></i>
            <p v-if="filtroBusqueda">No se encontraron cuadrillas para "{{ filtroBusqueda }}".</p>
            <p v-else>Todavía no hay cuadrillas. Creá la primera con "Nueva cuadrilla".</p>
        </div>
    </div>

    <!-- Modal Cuadrilla (crear / editar) -->
    <div class="modal fade" id="modalCuadrilla" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 720px;">
            <div class="modal-content cuadrilla-edit">
                <form @submit.prevent="guardarCuadrilla">
                    <div class="cuadrilla-edit__header">
                        <div class="cuadrilla-edit__title">
                            <span class="cuadrilla-edit__title-icon"><i class="bi bi-people-fill"></i></span>
                            <h5>{{ cuadrilla.id ? 'Editar cuadrilla' : 'Nueva cuadrilla' }}</h5>
                        </div>
                        <button type="button" class="cuadrilla-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="cuadrilla-edit__body">
                        <!-- Datos básicos -->
                        <div class="ce-form">
                            <div class="ce-field">
                                <label>Nombre de la cuadrilla</label>
                                <input type="text" v-model="cuadrilla.nombre" :required="!cuadrilla.id" placeholder="Ej: Cuadrilla Centro">
                            </div>
                            <div class="ce-field">
                                <label>Descripción <span class="ce-opt">opcional</span></label>
                                <textarea v-model="cuadrilla.descripcion" rows="2" placeholder="Breve descripción de la cuadrilla..."></textarea>
                            </div>
                        </div>

                        <!-- Gestión de operarios -->
                        <div class="ce-operarios">
                            <!-- Seleccionados (chips compactos) -->
                            <div class="ce-seleccionados">
                                <div class="ce-seleccionados__head">
                                    <span class="ce-panel__title"><i class="bi bi-people-fill"></i> Seleccionados</span>
                                    <span class="ce-seleccionados__hint" v-if="cuadrilla.operarios && cuadrilla.operarios.length">Tocá uno o más operarios para darles permisos de gestión</span>
                                </div>
                                <div class="ce-chips" v-if="cuadrilla.operarios && cuadrilla.operarios.length">
                                    <div
                                        v-for="operario in cuadrilla.operarios"
                                        :key="operario.id"
                                        class="ce-chip"
                                        :class="{ 'ce-chip--jefe': esJefeOperario(operario.id) }"
                                        :title="esJefeOperario(operario.id) ? operario.nombre + ' (Gestión)' : 'Otorgar permisos de gestión a ' + operario.nombre"
                                        @click="toggleJefeOperario(operario.id)">
                                        <img v-if="operario.foto_perfil" class="ce-chip__avatar ce-avatar--img" :src="urlFoto(operario.foto_perfil)" :alt="operario.nombre">
                                        <span v-else class="ce-chip__avatar" :style="{ backgroundColor: colorAvatar(operario.nombre) }">{{ iniciales(operario.nombre) }}</span>
                                        <i v-if="esJefeOperario(operario.id)" class="bi bi-person-badge-fill ce-chip__jefe-icon"></i>
                                        <span class="ce-chip__name">{{ operario.nombre }}</span>
                                        <button type="button" class="ce-chip__quitar" title="Quitar" @click.stop="quitarOperario(operario.id)">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="ce-chips-empty" v-else>
                                    <i class="bi bi-hand-index"></i>
                                    <span>Seleccioná operarios desde la lista de abajo</span>
                                </div>
                            </div>

                            <!-- Lista completa de operarios disponibles -->
                            <div class="ce-panel">
                                <div class="ce-panel__head">
                                    <span class="ce-panel__title"><i class="bi bi-person-plus"></i> Operarios disponibles</span>
                                    <span class="ce-cupo" :class="{ 'ce-cupo--lleno': (cuadrilla.operarios ? cuadrilla.operarios.length : 0) >= 4 }">
                                        {{ (cuadrilla.operarios ? cuadrilla.operarios.length : 0) }} / 4
                                    </span>
                                </div>
                                <div class="ce-search">
                                    <i class="bi bi-search"></i>
                                    <input type="text" v-model="filtroDisponibles" placeholder="Buscar operario...">
                                </div>
                                <ul class="ce-list" :class="{ 'ce-list--bloqueada': (cuadrilla.operarios ? cuadrilla.operarios.length : 0) >= 4 }" v-if="operariosDisponiblesFiltrados.length">
                                    <li v-for="operario in operariosDisponiblesFiltrados" :key="operario.id" class="ce-row ce-row--click" @click="agregarOperarioDirecto(operario)">
                                        <img v-if="operario.foto_perfil" class="ce-avatar ce-avatar--img" :src="urlFoto(operario.foto_perfil)" :alt="operario.nombre">
                                        <span v-else class="ce-avatar" :style="{ backgroundColor: colorAvatar(operario.nombre) }">{{ iniciales(operario.nombre) }}</span>
                                        <div class="ce-row__info">
                                            <span class="ce-row__name">{{ operario.nombre }}</span>
                                            <span class="ce-row__sub">{{ operario.email || operario.legajo }}</span>
                                        </div>
                                        <span class="ce-btn-icon ce-btn-agregar" title="Agregar a la cuadrilla">
                                            <i class="bi bi-plus-lg"></i>
                                        </span>
                                    </li>
                                </ul>
                                <div class="ce-empty" v-else>
                                    <i class="bi bi-search"></i>
                                    <span>{{ filtroDisponibles ? 'Sin coincidencias' : 'No hay operarios disponibles' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cuadrilla-edit__footer">
                        <button type="button" class="ce-btn-eliminar" @click="eliminarCuadrillaCompleta" v-if="cuadrilla.id">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                        <span v-else></span>
                        <div class="ce-footer-acciones">
                            <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="ce-btn-guardar"><i class="bi bi-check-lg"></i> Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalle de Cuadrilla (vista minimalista) -->
    <div class="modal fade" id="modalDetalleCuadrilla" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content detalle-cuadrilla" v-if="detalle">
                <button type="button" class="detalle-cuadrilla__close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>

                <div class="detalle-cuadrilla__header">
                    <div class="detalle-cuadrilla__icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h3 class="detalle-cuadrilla__name">{{ detalle.nombre }}</h3>
                        <span class="detalle-cuadrilla__count">
                            <i class="bi bi-person"></i>
                            {{ (detalle.operarios ? detalle.operarios.length : 0) }}
                            {{ (detalle.operarios && detalle.operarios.length === 1) ? 'operario' : 'operarios' }}
                        </span>
                    </div>
                </div>

                <div class="detalle-cuadrilla__section">
                    <span class="detalle-cuadrilla__label">Descripción</span>
                    <p class="detalle-cuadrilla__desc" v-if="detalle.descripcion">{{ detalle.descripcion }}</p>
                    <p class="detalle-cuadrilla__desc detalle-cuadrilla__desc--empty" v-else>Sin descripción</p>
                </div>

                <div class="detalle-cuadrilla__section">
                    <span class="detalle-cuadrilla__label">Operarios</span>
                    <ul class="detalle-operarios" v-if="detalle.operarios && detalle.operarios.length">
                        <li v-for="op in detalle.operarios" :key="op.id" class="detalle-operario">
                            <img
                                v-if="op.foto_perfil"
                                class="detalle-operario__avatar detalle-operario__avatar--img"
                                :class="{ 'avatar--jefe': Number(op.es_jefe) === 1 }"
                                :src="urlFoto(op.foto_perfil)"
                                :alt="op.nombre">
                            <span
                                v-else
                                class="detalle-operario__avatar"
                                :class="{ 'avatar--jefe': Number(op.es_jefe) === 1 }"
                                :style="{ backgroundColor: colorAvatar(op.nombre) }">
                                {{ iniciales(op.nombre) }}
                            </span>
                            <div class="detalle-operario__info">
                                <span class="detalle-operario__nombre">{{ op.nombre }}</span>
                                <span class="detalle-operario__sub">{{ op.email || op.legajo }}</span>
                            </div>
                            <span v-if="Number(op.es_jefe) === 1" class="detalle-operario__jefe">
                                <i class="bi bi-person-badge-fill"></i> Gestión
                            </span>
                        </li>
                    </ul>
                    <p class="detalle-cuadrilla__desc detalle-cuadrilla__desc--empty" v-else>Sin operarios asignados</p>
                </div>

                <div class="detalle-cuadrilla__footer">
                    <button type="button" class="btn-detalle-editar" @click="editarDesdeDetalle">
                        <i class="bi bi-gear"></i> Editar cuadrilla
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
