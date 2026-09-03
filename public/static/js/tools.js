axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const modals = {};
const tools = {
  install(app, options) {

    app.config.globalProperties.clone = function(data)
    {
      return JSON.parse(JSON.stringify(data));
    }
    app.config.globalProperties.restore = function(item,resItem)
    {
      console.log(item);
      this.reSet(item,this.clone(app._component.data()[resItem]));
      this.$forceUpdate();
    }
		app.config.globalProperties.tooltip = function () {
				const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
				tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
		},
		app.config.globalProperties.modal = function(id, fn) {
		if (!modals[id]) {
			const modalElement = document.getElementById(id);
			if (modalElement) {
				modals[id] = new bootstrap.Modal(modalElement);

				modalElement.addEventListener('shown.bs.modal', event => {
					if (fn === "show") {
						const el = document.getElementById("field_" + id + "_" + 0);
						if (el) el.focus();
					}
				});
			}
		}

			if (modals[id]) {
				if (fn === 'show') modals[id].show();
				else if (fn === 'hide') modals[id].hide();
			}
		},

    app.config.globalProperties.toggle = function(opt)
    {
      var value = this[opt];
      if(value === 1){this[opt] = 0; return;}
      if(value === 0){this[opt] = 1; return;}
      if(value === "1"){this[opt] = "0"; return;}
      if(value === "0"){this[opt] = "1"; return;}
      if(value === true){this[opt] = false; return;}
      if(value === false){this[opt] = true; return;}
      if(value === "true"){this[opt] = "false"; return;}
      if(value === "false"){this[opt] = "true"; return;}
    },
    app.config.globalProperties.confirm = function(title,yes,no,resultFn,item)
    {
      Swal.fire({
        title: title,
        showDenyButton: true,
        confirmButtonText: yes,
        denyButtonText: no,
      }).then((result) => {resultFn(result,item)})
    },
		
	app.config.globalProperties.swal = function (title,text, icon){
			Swal.fire({
				title: title,
				text: text,
				icon: icon
			});
	},
app.config.globalProperties.initMap = async function (latitudOrigen, longitudOrigen, encodeRuta, destinos) {
    try {
        var lat = parseFloat(latitudOrigen);
        var lon = parseFloat(longitudOrigen);

        const mapOptions = {
            center: { lat: lat, lng: lon }, // Coordenadas iniciales
            zoom: 12
        };
      
        const { Map } = await google.maps.importLibrary("maps");

        // Inicializa el mapa
        const mapElement = document.getElementById("map");

        // Verifica que el mapa se haya inicializado correctamente
        if (mapElement) {

            // Cargar AdvancedMarkerElement
        const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
        
        const map = new Map(mapElement, {
            center: { lat: parseFloat(latitudOrigen), lng: parseFloat(longitudOrigen) },
            zoom: 12,
            mapId: "map" // Opcional pero recomendado para estilos personalizados
        });

        // Crea el marcador avanzado (usando la referencia correcta)
        const marker = new AdvancedMarkerElement({
            map: map,
            position: { lat: parseFloat(latitudOrigen), lng: parseFloat(longitudOrigen) },
            title: "Origen",
            content: this.buildContent("O", "#0F9D58", true) // Verde
        });

            // Crear marcadores para los destinos
            if (Array.isArray(destinos) && destinos.length > 0) {
                destinos.forEach(destino => {
                    const markerDestino = new google.maps.marker.AdvancedMarkerElement({
                        position: { lat: parseFloat(destino.latitud), lng: parseFloat(destino.longitud) },
                        map: map,
                        title: destino.nombre || "Destino",
                        content: this.buildContent("D", "#4285F4") // Azul
                    });
                });
            }

            // Si tienes una ruta codificada (encoded polyline), la decodificamos y dibujamos
            if (encodeRuta) {
                // Esperar la importación del módulo de geometría
                const { encoding } = await google.maps.importLibrary("geometry");
                const encodedPath = encoding.decodePath(encodeRuta);

                const polyline = new google.maps.Polyline({
                    path: encodedPath, // Ruta decodificada
                    geodesic: true, // Asegura que la ruta se dibuje sobre la superficie curva de la Tierra
                    strokeColor: "#FF0000", // Color de la línea
                    strokeOpacity: 1.0, // Opacidad de la línea
                    strokeWeight: 3 // Grosor de la línea
                });

                // Agregar la polyline al mapa
                polyline.setMap(map);

                // Crear el objeto LatLngBounds para ajustar la vista del mapa
                const bounds = new google.maps.LatLngBounds();

                // Incluir cada punto de la ruta en los límites del mapa
                encodedPath.forEach((point) => {
                    bounds.extend(point);
                });

                // Ajustar el mapa a los límites de la ruta
                map.fitBounds(bounds);
            }
        } else {
            console.error("Mapa no inicializado correctamente.");
        }
    } catch (error) {
        console.error("Error al inicializar el mapa:", error);
    }
},
  app.config.globalProperties.buildContent = function (text, color, isOrigin = false) {
    const content = document.createElement("div");
    content.className = "custom-marker";
    
    content.innerHTML = `
        <div style="
            background: ${color};
            width: ${isOrigin ? '2rem' : '1.5rem'};
            height: ${isOrigin ? '2rem' : '1.5rem'};
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        ">
            ${text}
        </div>
    `;
    return content;
},
	app.config.globalProperties.formatDate = function (date){

		if (date == null) {
			return "Sin fecha de registro";
		}
		
	 var d = new Date(date);

		// Extraer componentes de la fecha
		const day = String(d.getDate()).padStart(2, '0');
		const month = String(d.getMonth() + 1).padStart(2, '0');
		const year = d.getFullYear();

		// Formatear la fecha
		return `${day}/${month}/${year}`;
			
		},
			
	app.config.globalProperties.toast = function (title,icon)
    {
				const Toast = Swal.mixin({
				toast: true,
				position: "bottom-end",
				showConfirmButton: false,
				timer: 2000,
				didOpen: (toast) => {
					toast.style.display = "flex";
					toast.style.flexDirection = "row";
					toast.style.alignItems = "center";
					toast.style.boxShadow = "0px 4px 10px #071729";
					toast.style.padding = "10px 14px";
					toast.style.fontSize = "12px";
					      // Reducir el tamaño del icono
      const iconElement = toast.querySelector('.swal2-icon');
      if (iconElement) {
        iconElement.style.fontSize = '12px'; // Ajusta el tamaño del icono
      }
					
				}
			});

				Toast.fire({
					icon: icon,
					title: title
				});
    },
    app.config.globalProperties.loadStructure = function(file,to)
    {
      axios.get(BASE_URL+"static/js/"+file+".json").then(({ data }) => {this[to] = data;});
    },
    app.config.globalProperties.getType = function(objetive)
    {
      if(objetive.constructor === String){return "string";}
      if(objetive.constructor === Object){return "object";}
      if(objetive.constructor === Array ){return "array"; }
      return false;
    },
		app.config.globalProperties.logout = function() {
			axios.post(BASE_URL + 'auth/logout')
				.then(response => {
					console.log('Logout exitoso', response.data);
					// Redirigir a la página de login o a otra página según sea necesario
					window.location.replace(BASE_URL + 'login');
				})
				.catch(error => {
					console.error('Error al hacer logout', error);
				});
		},
				app.config.globalProperties.FormatoMoneda = function(price)
    {
      return (price).toLocaleString('en-US', { 
        style: 'currency', 
        currency: 'USD' 
      });
    },
    app.config.globalProperties.numberWithCommas = function (x)
    {
      if (x == null) 
      {
        return "";
      }
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

     
    app.config.globalProperties.idi = 'es',
			
    app.config.globalProperties.diccionario = 
    {

    },
			
    app.config.globalProperties.changeIdioma = function(idioma)
    {
        this.$root.idioma = idioma;
        localStorage.setItem('idioma', idioma);
        if (typeof iniciarMapaMaster !== "undefined") { 
            loadGoogleMapsAPI(idioma,'iniciarMapaMaster')
            //iniciarMapaMaster();
        }
    }
    app.config.globalProperties.idioma = localStorage.getItem('idioma') || 'en';
    
    app.config.globalProperties.lang = function(texto)
    {
// 			console.log("texto",texto);
       if (this.$root.idioma === "es") 
       {
        return texto;
       } else if (this.$root.idioma === "en") {
          return this.diccionario[texto] || texto; // Retorna el texto original si no existe traducción
      }
    }
    
    
  }
}

app.use(tools,{});

app.component('readonly_tool',{
  props: ['id','inputType','title','field','required','item','only'],
  template: `
    <div class="form-floating mb-2" v-if="onlyDisplay(only,item)">
      <input v-bind:type="inputType" class="form-control" v-bind:id="'field_'" v-model="item[field]" v-bind:placeholder="title" readonly>
      <label v-bind:for="'field_'">{{lang(title)}} <span v-if="required" class="text-danger">*</span></label>
    </div>
  `,
  data() {
    return {}
  },
  methods: {
  }
});

app.component('select-tool-lang',{
  props: ['id','title','field','required','optionsInRoot','item','displayField','only','read','fnChange'],
  template: `
    <div class="form-floating mb-2" v-if="onlyDisplay(only,item)">
      <select class="form-select" v-bind:id="'field_'+id" v-bind:aria-label="title" v-model="item[field]" :required="required" :disabled="read" v-on:change="change(fnChange, item[field])">
        <option selected v-bind:value="undefined">{{lang('Selecciona una opción')}}</option>
        <option  v-for="(option,key) in options" :value="option.id">{{lang((displayField)?option[displayField]:option.name)}}</option>
      </select>
      <label v-bind:for="'field_'+id">{{lang(title)}} <span v-if="required" class="text-danger">*</span></label>
    </div>
  `,
  data() {
    return {
      options:[]
    }
  },
  methods: {
    change: function(fn, item)
    {
      if(fn != "" && fn != undefined && fn != null)
      {
        this.$root[fn](item);
      }
    }
  },
  mounted: function(){
    this.options = this.getPathObjectFromRoot(this.optionsInRoot);
  },
});

app.component('uploader-tool',{
  props: ['title','field','onChange','loaded','item','only'],
  template: `
    <div class="form-floating mb-2" v-if="onlyDisplay(only,item)">
      <input class="form-control fileItem" type="file" v-bind:id="'field_'+field" v-on:change="doOnChange()">
      <div class="progress">
        <div class="progress-bar" role="progressbar" v-bind:style="'width: '+this.$root[this.loaded]+'%'">{{this.$root[this.loaded]+'%'}}</div>
      </div>
      <label v-bind:for="'field_'+field">{{lang(title)}}</label>
    </div>
  `,
  data() {
    return {}
  },
  methods: {
    doOnChange: function()
    {
      this.$root[this.onChange](this.field);
    }
  },
  mounted: function(){
  },
});

app.component('form-tool', {
  props: ['fields', 'baseUrl'],
  template: `
    <form class="col-8 d-flex justify-content-center flex-column gap-2 form-style" @submit.prevent="submitForm">
      <div v-for="(row, rowIndex) in fieldRows" :key="rowIndex" class="d-flex justify-content-between col-12 align-self-center p-4 pb-2">
        <div v-for="(field, fieldIndex) in row" :key="fieldIndex" class="col-5 form-floating">
          <input
            :type="field.type"
            class="form-control"
            :id="field.name"
            :placeholder="field.label"
            v-model="formData[field.name]"
            :required="field.required"
          />
          <label :for="field.name">{{ field.label }} <span v-if="field.required" class="text-danger">*</span></label>
        </div>
      </div>

      <div class="d-flex justify-content-center col-12 align-self-center gap-4 pt-4 p-2">
        <button type="button" class="col-3 btn-exit" @click="$emit('cancel')">
          <img class="menu-icon" :src="baseUrl + '/back-icon.svg'" alt="back-icon" width="18" height="18" />
          Volver
        </button>
        <button type="submit" class="col-3 btn-save">
          <img class="menu-icon" :src="baseUrl + '/save-icon.svg'" alt="save-icon" width="18" height="18" />
          Guardar
        </button>
      </div>
    </form>
  `,
  data() {
    return {
      formData: {}
    };
  },
  computed: {
    fieldRows() {
      const rows = [];
      for (let i = 0; i < this.fields.length; i += 2) {
        rows.push(this.fields.slice(i, i + 2));
      }
      return rows;
    }
  },
  methods: {
    submitForm() {
      this.$emit('submit', this.formData);
    }
  },
  mounted() {
    this.fields.forEach(field => {
      this.formData[field.name] = field.defaultValue || '';
    });
  }
});

app.component('progress-bar-tool', {
  props: ['estado', 'label'],
  template: `
    <div class="progress" style="height: 14px; border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;">
      <div
        class="progress-bar"
        role="progressbar"
        :class="progressClass"
        :style="{ width: progressWidth + '%' }"
        style=" border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;"
        aria-valuenow="progressWidth"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        {{ label }}
      </div>
    </div>
  `,
  computed: {
    progressWidth() {
      return {
        1: 25,
        2: 50,
        3: 70,
        4: 100,
        5: 100,
      }[this.estado] || 0;
    },
    progressClass() {
      return {
        1: 'bg-secondary progress-bar-striped progress-bar-animated',
        2: 'bg-warning progress-bar-striped progress-bar-animated',
        3: 'bg-warning progress-bar-striped progress-bar-animated',
        4: 'bg-success',
        5: 'bg-danger',
      }[this.estado] || 'bg-secondary';
    }
  }
});


app.component('modal-tool', {
  props: ['id', 'title'],
  emits: ['update'],
  template: `
    <div class="modal fade" :id="id" ref="modalElement" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog l">
        <div class="modal-content window-modal">
          <div class="modal-header"  data-bs-theme="dark">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <slot></slot>
          </div>
          <div class="modal-footer">
						<div class="d-flex justify-content-center col-12">
            	<button type="button" class="mx-1 btn-save col-12" @click="$emit('update')">Guardar</button>
						</div>
          </div>
        </div>
      </div>
    </div>
  `,
});

app.component('table-tool', {
  props: ['items', 'columns', 'actions', 'baseUrl', 'badgeFields'],
  template: `
    <table class="tabla display" ref="dataTable">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key" scope="col">{{ column.label }}</th>
          <th v-if="actions.length > 0" scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id">
          <td v-for="column in columns" :key="column.key">
            <span v-if="badgeFields"
              :class="item[column.key] == 1 ? 'badge text-bg-success' : 'badge text-bg-danger'">
              {{ item[column.key] == 1 ? 'Activo' : 'Inactivo' }}
            </span>
            <span v-else>
              {{ item[column.key] }}
            </span>
          </td>
          <td v-if="actions.length > 0" class="d-flex justify-content-center gap-1">
            <button v-for="action in actions" :key="action.name" :class="action.class" @click="$emit(action.event, item)">
              <img class="menu-icon" :src="baseUrl + action.icon" :alt="action.name" />
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  `,
  data() {
    return {
      tableInstance: null
    };
  },
  mounted() {
    this.$nextTick(() => {
      this.initTable();
    });
  },
  watch: {
    items: {
      handler() {
        this.$nextTick(() => {
          this.initTable();
        });
      },
      deep: true
    }
  },
  methods: {
    initTable() {
      if (this.tableInstance) {
        this.tableInstance.destroy();
      }
      this.$nextTick(() => {
        this.tableInstance = $(this.$refs.dataTable).DataTable({
          responsive: true,
          lengthMenu: [
            [5, 10, 15],
            [5, 10, 15]
          ],
          language: {
            url: '//cdn.datatables.net/plug-ins/2.2.1/i18n/es-MX.json',
          },
          pagingType: "full_numbers"
        });
      });
    }
  }
});

const MyApp = (typeof app !== 'undefined' && !app._container)
    ? app.mount('#app')
    : (typeof app !== 'undefined' ? (app._instance && app._instance.proxy) : null);

/**
 * Función para mejorar la experiencia táctil en móviles
 */
function setupMobileTableTouch() {
    const tableResponsives = document.querySelectorAll('.table-responsive');
    
    tableResponsives.forEach(container => {
        let startX = 0;
        let startY = 0;
        let isScrolling = false;
        
        container.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isScrolling = false;
        });
        
        container.addEventListener('touchmove', function(e) {
            if (!isScrolling) {
                const deltaX = Math.abs(e.touches[0].clientX - startX);
                const deltaY = Math.abs(e.touches[0].clientY - startY);
                
                if (deltaX > deltaY && deltaX > 10) {
                    isScrolling = true;
                    e.preventDefault();
                }
            }
        }, { passive: false });
        
        container.addEventListener('touchend', function(e) {
            isScrolling = false;
        });
    });
}

/**
 * Función para inicializar todas las mejoras de tablas
 */
function initializeTableEnhancements() {
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setupMobileTableTouch();
        });
    } else {
        setupMobileTableTouch();
    }
}

// Inicializar mejoras cuando se carga la página
initializeTableEnhancements();

// Exportar funciones para uso en otros archivos
window.tableEnhancements = {
    setupMobileTableTouch,
    initializeTableEnhancements
};


