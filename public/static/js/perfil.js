document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.perfil-page');
    if (!page) return;

    const userId = page.getAttribute('data-user-id');
    const btnCambiar = document.getElementById('btnCambiarFoto');
    const input = document.getElementById('inputPerfilFoto');

    if (!userId || !btnCambiar || !input) return;

    btnCambiar.addEventListener('click', () => input.click());

    input.addEventListener('change', async (event) => {
        const archivo = event.target.files && event.target.files[0];
        if (!archivo) return;

        // Validación básica en cliente
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!tiposPermitidos.includes(archivo.type)) {
            mostrarAviso('Formato no permitido. Use JPG, PNG o WEBP.', 'error');
            input.value = '';
            return;
        }
        if (archivo.size > 2 * 1024 * 1024) {
            mostrarAviso('La imagen no debe superar los 2 MB.', 'error');
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('foto', archivo);

        try {
            btnCambiar.classList.add('cargando');
            const response = await axios.post(BASE_URL + 'api/usuarios/' + userId + '/foto', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            const url = response.data && response.data.url;
            if (url) {
                actualizarAvatar(url);
                actualizarAvatarMenu(url);
            }
            mostrarAviso('Foto actualizada correctamente.', 'success');
        } catch (error) {
            const msg = error.response?.data?.message
                || error.response?.data?.messages?.error
                || 'No se pudo actualizar la foto.';
            mostrarAviso(msg, 'error');
        } finally {
            btnCambiar.classList.remove('cargando');
            input.value = '';
        }
    });

    function actualizarAvatar(url) {
        const cont = document.querySelector('.perfil-avatar');
        if (!cont) return;
        let img = document.getElementById('perfilAvatarImg');
        const iniciales = document.getElementById('perfilAvatarIniciales');
        const cacheBust = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
        if (img) {
            img.src = cacheBust;
        } else {
            img = document.createElement('img');
            img.id = 'perfilAvatarImg';
            img.alt = 'Foto de perfil';
            img.src = cacheBust;
            if (iniciales) {
                cont.replaceChild(img, iniciales);
            } else {
                cont.insertBefore(img, cont.firstChild);
            }
        }
    }

    function actualizarAvatarMenu(url) {
        const menuAvatar = document.querySelector('.sidebar-footer .user-avatar');
        if (!menuAvatar) return;
        const cacheBust = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
        let img = menuAvatar.querySelector('.user-avatar-img');
        if (img) {
            img.src = cacheBust;
        } else {
            const icono = menuAvatar.querySelector('i');
            if (icono) icono.remove();
            img = document.createElement('img');
            img.className = 'user-avatar-img';
            img.alt = 'Foto de perfil';
            img.src = cacheBust;
            menuAvatar.appendChild(img);
        }
    }

    function mostrarAviso(mensaje, tipo) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'success' ? 'success' : 'error',
                title: tipo === 'success' ? 'Listo' : 'Atención',
                text: mensaje,
                confirmButtonColor: '#3A3972'
            });
        } else {
            alert(mensaje);
        }
    }
});
