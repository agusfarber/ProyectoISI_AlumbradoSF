<div id="app" class="col-12">
	<!-- Logo arriba a la izquierda -->
	<div class="position-absolute top-0 start-0 m-3">
		<img src="/static/img/logo_SanFrancisco.png" alt="Logo San Francisco" style="height: 60px;">
	</div>

	<div class="form-signin w-100 m-auto text-center d-flex justify-content-center">
		<div class="row w-100">
			<!-- Selección de Rol -->
			<div v-if="!rolSeleccionado" class="col-md-6 mx-auto">
				<div class="card">
					<div class="card-body">
						<div class="text-center mb-4">
							<div class="login-logo mb-3">
								<i class="fas fa-lightbulb"></i>
							</div>
							<h1 class="h1 mb-2">Gestión de Alumbrado Público</h1>
							<p class="text-muted">Seleccione su tipo de usuario</p>
						</div>
						
						
						<div class="d-grid gap-3">
							<button class="btn btn-primary btn-lg" @click="seleccionarRol('admin')">
								<i class="fas fa-user-shield me-2"></i>
								Administrador
							</button>
							
							<button class="btn btn-success btn-lg" @click="seleccionarRol('supervisor')">
								<i class="fas fa-user-tie me-2"></i>
								Supervisor / Operario
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Formulario con Email (Administrador) -->
			<div v-if="rolSeleccionado === 'admin'" class="col-md-6 mx-auto">
				<div class="card">
					<div class="card-body">
						<div class="text-center mb-3">
							<h1 class="h3 mb-0 fw-normal">Administrador</h1>
						</div>


						<form @submit.prevent="login" class="col-12">
							<div class="form-group mb-3">
								<label for="email" class="form-label">Correo electrónico</label>
								<input type="email" class="form-control" id="email" v-model="usuario.email" required>
							</div>

							<div class="form-group mb-3">
								<label for="password" class="form-label">Contraseña</label>
								<input type="password" class="form-control" id="password" v-model="usuario.contrasena" required>
							</div>

							<!-- Mensaje de error -->
							<p v-if="errorMessage" class="text-danger">{{ errorMessage }}</p>

							<div class="d-grid gap-2">
								<button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
									<span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
									{{ loading ? 'Ingresando...' : 'Iniciar sesión' }}
								</button>
								<button type="button" class="btn btn-outline-secondary" @click="volverSeleccion">
									<i class="fas fa-arrow-left me-1"></i>Volver
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Formulario con Legajo (Supervisor/Operario) -->
			<div v-if="rolSeleccionado === 'supervisor'" class="col-md-6 mx-auto">
				<div class="card">
					<div class="card-body">
						<div class="text-center mb-3">
							<h1 class="h3 mb-0 fw-normal">Supervisor / Operario</h1>
						</div>

						

						<form @submit.prevent="loginLegajo" class="col-12">
							<div class="form-group mb-3">
								<label for="legajo" class="form-label">Legajo</label>
								<input type="text" class="form-control" id="legajo" v-model="usuarioLegajo.legajo" required>
							</div>

							<div class="form-group mb-3">
								<label for="passwordLegajo" class="form-label">Contraseña</label>
								<input type="password" class="form-control" id="passwordLegajo" v-model="usuarioLegajo.contrasena" required>
							</div>

							<!-- Mensaje de error para legajo -->
							<p v-if="errorMessageLegajo" class="text-danger">{{ errorMessageLegajo }}</p>

							<div class="d-grid gap-2">
								<button class="btn btn-success btn-lg" type="submit" :disabled="loadingLegajo">
									<span v-if="loadingLegajo" class="spinner-border spinner-border-sm me-2"></span>
									{{ loadingLegajo ? 'Ingresando...' : 'Iniciar sesión' }}
								</button>
								<button type="button" class="btn btn-outline-secondary" @click="volverSeleccion">
									<i class="fas fa-arrow-left me-1"></i>Volver
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
