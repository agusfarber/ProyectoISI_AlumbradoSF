<div id="app" class="col-12">
	<!-- Logo arriba a la izquierda
	<div class="position-absolute top-0 start-0 m-3">
		<img src="/static/img/logo_SanFrancisco.png" alt="Logo San Francisco" style="height: 60px;">
	</div-->

	<div class="form-signin w-100 m-auto text-center d-flex justify-content-center">
		<div class="row w-100">
			<div class="col-md-6 mx-auto">
				<div class="card">
					<div class="card-body">
						<div class="text-center login-header mb-4">
							<div class="login-logo">
								<i class="fas fa-lightbulb"></i>
							</div>
							<h1 class="h1 mb-0">Gestión de Alumbrado Público</h1>
						</div>

						<form @submit.prevent="login" class="col-12">
							<div class="form-group mb-3">
								<label for="credencial" class="form-label">Usuario</label>
								<input type="text" class="form-control" id="credencial" v-model="usuario.credencial" required>
							</div>

							<div class="form-group mb-3">
								<label for="password" class="form-label">Contraseña</label>
								<input type="password" class="form-control" id="password" v-model="usuario.contrasena" required>
							</div>

							<p v-if="errorMessage" class="text-danger">{{ errorMessage }}</p>

							<div class="d-grid">
								<button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
									<span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
									{{ loading ? 'Ingresando...' : 'Iniciar sesión' }}
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
