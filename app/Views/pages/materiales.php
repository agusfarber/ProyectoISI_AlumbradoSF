<div id="app" class="materiales-page">

    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-box-seam"></i></span>
        <h1 class="app-page-title__text">Materiales</h1>
    </div>

    <!-- Toolbar -->
    <div class="materiales-toolbar">
        <div class="materiales-toolbar__actions">
            <button
                v-if="vista === 'categorias'"
                type="button"
                class="btn-nueva"
                @click="abrirModalCategorias()">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-md-inline">Nueva categoría</span>
                <span class="d-md-none">Categoría</span>
            </button>
            <button
                v-if="vista === 'detalle'"
                type="button"
                class="btn-nueva"
                @click="abrirFormularioEnCategoria()">
                <i class="bi bi-plus-lg"></i> Nuevo material
            </button>
            <button
                v-if="vista === 'categorias'"
                type="button"
                class="mat-btn mat-btn--outline"
                @click="abrirModalImportar()">
                <i class="bi bi-upload"></i> Importar
            </button>
        </div>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" v-model="filtroBusqueda" :placeholder="vista === 'categorias' ? 'Buscar categoría' : 'Buscar material'">
            <button v-if="filtroBusqueda" type="button" class="search-clear" @click="filtroBusqueda = ''" title="Limpiar">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>

    <!-- ========== Vista: categorías ========== -->
    <div v-if="vista === 'categorias'">
        <p class="materiales-hint">Elegí una categoría para ver y administrar sus materiales.</p>

        <div class="materiales-grid">
            <article
                v-for="cat in categoriasMostradas"
                :key="cat.key"
                class="mat-categoria-card"
                @click="abrirCategoria(cat)"
                tabindex="0"
                @keyup.enter="abrirCategoria(cat)">
                <div class="mat-categoria-card__top">
                    <div class="mat-categoria-card__icon" :style="{ background: cat.color }">
                        <i :class="cat.icono"></i>
                    </div>
                    <div class="mat-categoria-card__heading">
                        <h3 class="mat-categoria-card__name">{{ cat.nombre }}</h3>
                        <span class="mat-categoria-card__count">
                            {{ cat.cantidadMateriales }}
                            {{ cat.cantidadMateriales === 1 ? 'material' : 'materiales' }}
                        </span>
                    </div>
                    <button
                        v-if="cat.idTipo != null"
                        type="button"
                        class="mat-categoria-card__gear"
                        title="Editar categoría"
                        @click.stop="editarCategoria(cat)">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
                <div class="mat-categoria-card__footer">
                    <span class="mat-categoria-card__enter">Ver materiales</span>
                    <i class="bi bi-chevron-right mat-categoria-card__arrow"></i>
                </div>
            </article>

            <div v-if="categoriasMostradas.length === 0" class="materiales-empty">
                <i class="bi bi-tags"></i>
                <p v-if="filtroBusqueda">No se encontraron categorías para "{{ filtroBusqueda }}".</p>
                <template v-else>
                    <p>Todavía no hay categorías. Creá la primera para organizar tus materiales.</p>
                    <button type="button" class="btn-nueva" @click="abrirModalCategorias()">
                        <i class="bi bi-plus-lg"></i> Nueva categoría
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ========== Vista: materiales de una categoría ========== -->
    <div v-else class="mat-detalle">
        <div class="mat-detalle__header">
            <button type="button" class="mat-btn mat-btn--ghost" @click="volverACategorias">
                <i class="bi bi-arrow-left"></i> Categorías
            </button>
            <div class="mat-detalle__title">
                <span class="mat-detalle__icon" :style="{ background: categoriaActiva.color }">
                    <i :class="categoriaActiva.icono"></i>
                </span>
                <div>
                    <h2>{{ categoriaActiva.nombre }}</h2>
                    <p>
                        {{ materialesDeCategoria.length }}
                        {{ materialesDeCategoria.length === 1 ? 'material' : 'materiales' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mat-items-grid" v-if="materialesDeCategoriaFiltrados.length">
            <article
                v-for="m in materialesDeCategoriaFiltrados"
                :key="m.id"
                class="mat-item-card">
                <div class="mat-item-card__media">
                    <img v-if="m.foto" :src="urlFotoMaterial(m.foto)" :alt="m.nombre">
                    <span v-else class="mat-item-card__placeholder"><i class="bi bi-image"></i></span>
                </div>
                <div class="mat-item-card__body">
                    <h4 class="mat-item-card__name" :title="m.nombre">{{ m.nombre }}</h4>
                    <div class="mat-item-card__meta">
                        <div class="mat-item-card__actions">
                            <button type="button" class="mat-icon-btn" title="Editar" @click="editarMaterial(m)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="mat-icon-btn mat-icon-btn--danger" title="Eliminar" @click="eliminarMaterial(m)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="materiales-empty materiales-empty--detalle">
            <i class="bi bi-box"></i>
            <p v-if="filtroBusqueda">No hay materiales que coincidan con "{{ filtroBusqueda }}".</p>
            <p v-else>Esta categoría todavía no tiene materiales.</p>
            <button type="button" class="btn-nueva" @click="abrirFormularioEnCategoria()">
                <i class="bi bi-plus-lg"></i> Agregar material
            </button>
        </div>
    </div>

    <!-- Modal Material -->
    <div class="modal fade" id="modalMaterial" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content mat-modal mat-modal--material">
                <form @submit.prevent="guardarMaterial">
                    <div class="mat-modal__header mat-modal__header--slim">
                        <h5>{{ material.id ? 'Editar material' : 'Nuevo material' }}</h5>
                        <button type="button" class="mat-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="mat-modal__body mat-modal__body--material">
                        <!-- Vista previa -->
                        <div class="mat-mat-preview">
                            <label class="mat-mat-preview__photo" for="inputFotoMaterial" title="Agregar o cambiar foto">
                                <img v-if="fotoPreviewUrl" :src="fotoPreviewUrl" alt="Vista previa">
                                <div v-else class="mat-mat-preview__empty">
                                    <i class="bi bi-camera"></i>
                                    <span>Agregar foto</span>
                                </div>
                            </label>
                            <input
                                type="file"
                                id="inputFotoMaterial"
                                class="d-none"
                                accept="image/jpeg,image/png,image/webp"
                                @change="onFotoSeleccionada">

                            <div class="mat-mat-preview__info">
                                <span
                                    v-if="categoriaActiva.nombre"
                                    class="mat-mat-preview__cat"
                                    :style="{ background: categoriaActiva.color || '#6c757d' }">
                                    <i :class="categoriaActiva.icono || 'bi bi-tag'"></i>
                                    {{ categoriaActiva.nombre }}
                                </span>
                                <h3 class="mat-mat-preview__name">
                                    {{ (material.nombre || '').trim() || 'Nombre del material' }}
                                </h3>
                                <button
                                    v-if="fotoPreviewUrl"
                                    type="button"
                                    class="mat-mat-preview__quitar-foto"
                                    @click="quitarFotoSeleccionada">
                                    Quitar foto
                                </button>
                            </div>
                        </div>

                        <div class="mat-mat-fields">
                            <div class="mat-field">
                                <label for="inputNombreMaterial">Nombre</label>
                                <input
                                    id="inputNombreMaterial"
                                    type="text"
                                    v-model="material.nombre"
                                    required
                                    placeholder="Ej: Luminaria LED 100W"
                                    maxlength="100">
                            </div>
                        </div>
                    </div>
                    <div class="mat-modal__footer">
                        <button type="button" class="mat-btn mat-btn--ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-nueva">
                            {{ material.id ? 'Guardar cambios' : 'Crear material' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nueva categoría -->
    <div class="modal fade" id="modalTiposMateriales" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content mat-modal">
                <form @submit.prevent="guardarTipo">
                    <div class="mat-modal__header">
                        <div class="mat-modal__title">
                            <span class="mat-modal__title-icon"><i class="bi bi-tags"></i></span>
                            <h5>Nueva categoría</h5>
                        </div>
                        <button type="button" class="mat-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="mat-modal__body">
                        <div class="mat-cat-preview">
                            <div class="mat-cat-preview__icon" :style="{ background: tipo.color }">
                                <i :class="tipo.icono"></i>
                            </div>
                            <div class="mat-cat-preview__text">
                                <strong>{{ (tipo.nombre || '').trim() || 'Nombre de la categoría' }}</strong>
                                <small>Vista previa</small>
                            </div>
                        </div>

                        <div class="mat-field">
                            <label>Nombre</label>
                            <input type="text" v-model="tipo.nombre" required placeholder="Ej: Luminarias" maxlength="50">
                        </div>

                        <div class="mat-icon-picker">
                            <label>Icono</label>
                            <div class="mat-icon-picker__grid">
                                <button
                                    v-for="ico in iconosDisponibles"
                                    :key="ico"
                                    type="button"
                                    class="mat-icon-picker__opt"
                                    :class="{ 'is-selected': tipo.icono === ico }"
                                    :title="ico"
                                    @click="tipo.icono = ico">
                                    <i :class="ico"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mat-color-picker">
                            <label>Color de fondo</label>
                            <div class="mat-color-picker__row">
                                <button
                                    v-for="col in coloresDisponibles"
                                    :key="'new-' + col"
                                    type="button"
                                    class="mat-color-picker__swatch"
                                    :class="{ 'is-selected': tipo.color.toLowerCase() === col.toLowerCase() }"
                                    :style="{ background: col }"
                                    :title="col"
                                    @click="tipo.color = col">
                                </button>
                                <label class="mat-color-picker__custom" title="Color personalizado">
                                    <input type="color" v-model="tipo.color">
                                    <i class="bi bi-eyedropper"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mat-modal__footer">
                        <button type="button" class="mat-btn mat-btn--ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-nueva">Crear categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content mat-modal">
                <form @submit.prevent="guardarCategoriaEditada">
                    <div class="mat-modal__header">
                        <div class="mat-modal__title">
                            <span class="mat-modal__title-icon"><i class="bi bi-gear"></i></span>
                            <h5>Editar categoría</h5>
                        </div>
                        <button type="button" class="mat-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="mat-modal__body">
                        <div class="mat-cat-preview">
                            <div class="mat-cat-preview__icon" :style="{ background: tipoEdicion.color }">
                                <i :class="tipoEdicion.icono"></i>
                            </div>
                            <div class="mat-cat-preview__text">
                                <strong>{{ (tipoEdicion.nombre || '').trim() || 'Nombre de la categoría' }}</strong>
                                <small>Vista previa</small>
                            </div>
                        </div>

                        <div class="mat-field">
                            <label>Nombre</label>
                            <input type="text" v-model="tipoEdicion.nombre" required placeholder="Nombre de la categoría" maxlength="50">
                        </div>

                        <div class="mat-icon-picker">
                            <label>Icono</label>
                            <div class="mat-icon-picker__grid">
                                <button
                                    v-for="ico in iconosDisponibles"
                                    :key="'edit-' + ico"
                                    type="button"
                                    class="mat-icon-picker__opt"
                                    :class="{ 'is-selected': tipoEdicion.icono === ico }"
                                    :title="ico"
                                    @click="tipoEdicion.icono = ico">
                                    <i :class="ico"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mat-color-picker">
                            <label>Color de fondo</label>
                            <div class="mat-color-picker__row">
                                <button
                                    v-for="col in coloresDisponibles"
                                    :key="'edit-' + col"
                                    type="button"
                                    class="mat-color-picker__swatch"
                                    :class="{ 'is-selected': (tipoEdicion.color || '').toLowerCase() === col.toLowerCase() }"
                                    :style="{ background: col }"
                                    :title="col"
                                    @click="tipoEdicion.color = col">
                                </button>
                                <label class="mat-color-picker__custom" title="Color personalizado">
                                    <input type="color" v-model="tipoEdicion.color">
                                    <i class="bi bi-eyedropper"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mat-modal__footer mat-modal__footer--edit">
                        <button type="button" class="mat-btn mat-btn--danger" @click="eliminarDesdeEdicion">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                        <div class="mat-modal__footer-right">
                            <button type="button" class="mat-btn mat-btn--ghost" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn-nueva">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Importar materiales -->
    <div class="modal fade" id="modalImportarMateriales" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content mat-modal">
                <div class="mat-modal__header">
                    <div class="mat-modal__title">
                        <span class="mat-modal__title-icon"><i class="bi bi-upload"></i></span>
                        <h5>Importar materiales</h5>
                    </div>
                    <button type="button" class="mat-modal__close" data-bs-dismiss="modal" aria-label="Cerrar" @click="limpiarArchivo">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="mat-modal__body">
                    <div class="mat-import-ejemplo">
                        <div class="mat-import-ejemplo__head">
                            <i class="bi bi-file-earmark-excel"></i>
                            <div>
                                <strong>Formato del archivo</strong>
                                <p>El Excel o CSV debe tener estas 2 columnas, en este orden:</p>
                            </div>
                        </div>
                        <div class="mat-import-sheet" aria-hidden="true">
                            <div class="mat-import-sheet__bar">
                                <span></span><span></span><span></span>
                                <em>materiales.xlsx</em>
                            </div>
                            <table class="mat-import-sheet__table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>A</th>
                                        <th>B</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>1</th>
                                        <td class="is-header">nombre</td>
                                        <td class="is-header">tipo</td>
                                    </tr>
                                    <tr>
                                        <th>2</th>
                                        <td>Luminaria LED 100W</td>
                                        <td>Luminarias</td>
                                    </tr>
                                    <tr>
                                        <th>3</th>
                                        <td>Cable 2.5mm</td>
                                        <td>Cables</td>
                                    </tr>
                                    <tr>
                                        <th>4</th>
                                        <td>Poste metálico 9m</td>
                                        <td>Postes</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mat-import-ejemplo__nota">
                            <i class="bi bi-info-circle"></i>
                            La columna <strong>tipo</strong> es el nombre de la categoría.
                            Debe coincidir con una categoría ya existente; si no existe, esa fila se rechaza.
                        </p>
                    </div>

                    <input
                        type="file"
                        id="inputArchivoMateriales"
                        class="d-none"
                        @change="onArchivoSeleccionado"
                        accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">

                    <div
                        class="mat-import-dropzone"
                        :class="{ 'is-dragover': importDragOver, 'has-file': !!archivoSeleccionado }"
                        @dragenter.prevent="onImportDragEnter"
                        @dragover.prevent="onImportDragOver"
                        @dragleave.prevent="onImportDragLeave"
                        @drop.prevent="onImportDrop">
                        <template v-if="!archivoSeleccionado">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p>Arrastrá el archivo acá</p>
                            <span>o</span>
                            <button type="button" class="mat-btn mat-btn--outline" @click="abrirSelectorArchivo">
                                <i class="bi bi-folder2-open"></i> Seleccionar archivo
                            </button>
                            <small>CSV o Excel (.xlsx / .xls)</small>
                        </template>
                        <template v-else>
                            <i class="bi bi-file-earmark-check"></i>
                            <p class="mat-import-dropzone__nombre" :title="archivoSeleccionado.name">{{ archivoSeleccionado.name }}</p>
                            <small>{{ formatoTamanoArchivo(archivoSeleccionado.size) }}</small>
                            <div class="mat-import-dropzone__acciones">
                                <button type="button" class="mat-btn mat-btn--ghost" @click="limpiarArchivo">
                                    <i class="bi bi-x-circle"></i> Quitar
                                </button>
                                <button type="button" class="mat-btn mat-btn--outline" @click="abrirSelectorArchivo">
                                    <i class="bi bi-arrow-repeat"></i> Cambiar
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="mat-modal__footer">
                    <button type="button" class="mat-btn mat-btn--ghost" data-bs-dismiss="modal" @click="limpiarArchivo">Cancelar</button>
                    <button
                        v-if="archivoSeleccionado"
                        type="button"
                        class="btn-nueva"
                        :disabled="importando"
                        @click="importarArchivo">
                        <i class="bi bi-upload"></i>
                        {{ importando ? 'Importando…' : 'Importar archivo' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
