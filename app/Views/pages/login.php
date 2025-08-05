<div id="app" class="col-12">
	<div class="form-signin w-100 m-auto text-center d-flex justify-content-center">
		<div class="row w-100">
			<!-- Selección de Rol -->
			<div v-if="!rolSeleccionado" class="col-md-6 mx-auto">
				<div class="card">
					<div class="card-body">
						<h1 class="h3 mb-4 fw-normal">Seleccione su tipo de usuario</h1>
						
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
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h1 class="h3 mb-0 fw-normal">Iniciar sesión - Administrador</h1>
							<button class="btn btn-outline-secondary btn-sm" @click="volverSeleccion">
								<i class="fas fa-arrow-left me-1"></i>Volver
							</button>
						</div>

						<form @submit.prevent="login" class="col-12">
							<div class="form-floating mb-3">
								<input type="email" class="form-control" id="floatingInput" placeholder="name@example.com" v-model="usuario.email" required>
								<label for="floatingInput">Correo electrónico</label>
							</div>

							<div class="form-floating mb-3">
								<input type="password" class="form-control" id="floatingPassword" placeholder="Password" v-model="usuario.contrasena" required>
								<label for="floatingPassword">Contraseña</label>
							</div>

							<!-- Mensaje de error -->
							<p v-if="errorMessage" class="text-danger">{{ errorMessage }}</p>

							<div class="d-grid">
								<button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
									<span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
									{{ loading ? 'Ingresando...' : 'Ingresar' }}
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
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h1 class="h3 mb-0 fw-normal">Iniciar sesión - Supervisor/Operario</h1>
							<button class="btn btn-outline-secondary btn-sm" @click="volverSeleccion">
								<i class="fas fa-arrow-left me-1"></i>Volver
							</button>
						</div>

						<form @submit.prevent="loginLegajo" class="col-12">
							<div class="form-floating mb-3">
								<input type="text" class="form-control" id="floatingLegajo" placeholder="12345" v-model="usuarioLegajo.legajo" required>
								<label for="floatingLegajo">Legajo</label>
							</div>

							<div class="form-floating mb-3">
								<input type="password" class="form-control" id="floatingPasswordLegajo" placeholder="Password" v-model="usuarioLegajo.contrasena" required>
								<label for="floatingPasswordLegajo">Contraseña</label>
							</div>

							<!-- Mensaje de error para legajo -->
							<p v-if="errorMessageLegajo" class="text-danger">{{ errorMessageLegajo }}</p>

							<div class="d-grid">
								<button class="btn btn-success btn-lg" type="submit" :disabled="loadingLegajo">
									<span v-if="loadingLegajo" class="spinner-border spinner-border-sm me-2"></span>
									{{ loadingLegajo ? 'Ingresando...' : 'Ingresar' }}
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
