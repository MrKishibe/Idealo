export function crearMarkupSkeletonDataTable(numeroColumnas) {
    const filas = Array.from({ length: 4 }, () => {
        const celdas = Array.from(
            { length: numeroColumnas },
            () => '<span class="skeleton-block skeleton-effect-wave datatable-skeleton__cell"></span>'
        ).join('');

        return `<div class="datatable-skeleton__row" style="--datatable-skeleton-columns: ${numeroColumnas}">${celdas}</div>`;
    }).join('');

    return `<div class="datatable-skeleton" role="status" aria-label="Cargando registros">${filas}</div>`;
}
