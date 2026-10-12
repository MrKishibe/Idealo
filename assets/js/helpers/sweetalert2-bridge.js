const sweetalert = window.Swal;

if (!sweetalert) {
    throw new Error('SweetAlert2 debe cargarse antes de los módulos de la aplicación.');
}

export default sweetalert;
