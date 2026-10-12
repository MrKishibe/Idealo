import Swal from 'sweetalert2';

const confirmButtonColor = '#198754';
const cancelButtonColor = '#6c757d';

export const successAlert = (title, text) => Swal.fire({
    icon: 'success',
    title,
    text,
    confirmButtonColor,
    confirmButtonText: 'Aceptar',
    timer: 1800,
    timerProgressBar: true
});

export const errorAlert = (title, text) => Swal.fire({
    icon: 'error',
    title,
    text,
    confirmButtonColor,
    confirmButtonText: 'Aceptar'
});

export const statusAlert = (title, text, icon) => Swal.fire({
    title,
    text,
    icon,
    timer: 2000,
    showConfirmButton: false,
    confirmButtonColor
});

export const confirmActionAlert = (title, text) => Swal.fire({
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    confirmButtonColor,
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar'
});

export const confirmDeleteAlert = (onConfirm) => Swal.fire({
    icon: 'warning',
    title: '¿Eliminar registro?',
    text: 'Esta acción no se puede deshacer.',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
    cancelButtonColor
}).then((result) => {
    if (result.isConfirmed && typeof onConfirm === 'function') {
        return onConfirm();
    }

    return result;
});

export const editAlert = (title, text) => Swal.fire({
    icon: 'success',
    title,
    text,
    confirmButtonColor,
    confirmButtonText: 'Aceptar'
});