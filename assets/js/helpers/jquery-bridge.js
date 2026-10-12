const jquery = window.jQuery;

if (!jquery) {
    throw new Error('jQuery debe cargarse antes de los módulos de la aplicación.');
}

export default jquery;
