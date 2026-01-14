<?php

use App\Http\Controllers\GUATEXController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TiendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TiendaController::class, 'index'])->name('inicio');
Route::get('/catalogo', [TiendaController::class, 'catalogo'])->name('catalogo');
Route::get('/producto/{slug}', [TiendaController::class, 'producto'])->name('producto');

Route::get('/', function () {
    return redirect('/catalogo');
});
/* Route::get('/login', function () {
    return redirect('/admin');
}); */

Route::middleware('auth')->group(function () {
    /* Route::get('/crear-orden', [TiendaController::class, 'orden'])->name('crear.orden');
    Route::post('/guardar-orden', [TiendaController::class, 'storeOrden'])->name('guardar.orden');
    Route::inertia('/carrito', 'Carrito')->name('carrito');
    Route::post('/agregar-carrito', [TiendaController::class, 'agregarCarrito'])->name('agregar.carrito');
    Route::post('/sumar-carrito/{id}', [TiendaController::class, 'sumarCarrito'])->name('sumar.carrito');
    Route::post('/restar-carrito/{id}', [TiendaController::class, 'restarCarrito'])->name('restar.carrito');
    Route::post('/eliminar-carrito/{id}', [TiendaController::class, 'eliminarCarrito'])->name('eliminar.carrito'); */

    // REPORTES
    Route::get('/reporte/ventas-general', [ReporteController::class, 'VentasGeneral'])->name('reporte.ventasgeneral');
    Route::get('/reporte/ventas-detalle', [ReporteController::class, 'VentasDetalle'])->name('reporte.ventasdetalle');
    Route::get('/reporte/pagos', [ReporteController::class, 'Pagos'])->name('reporte.pagos');
    Route::get('/reporte/resultados', [ReporteController::class, 'Resultados'])->name('reporte.resultados');
    Route::get('/reporte/historial-cliente', [ReporteController::class, 'HistorialCliente'])->name('reporte.historialcliente');
    Route::get('/reporte/reporte-iventario', [ReporteController::class, 'ReporteInventario'])->name('reporte.reporteinventario');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('pdf')->group(function () {
        Route::get('/orden/{id}', [PDFController::class, 'orden'])->name('pdf.orden');
        Route::get('/cotizacion/{id}', [PDFController::class, 'cotizacion'])->name('pdf.cotizacion');
        Route::get('/recibo-orden/{id}', [PDFController::class, 'reciboOrden'])->name('pdf.recibo-orden');
        Route::get('/guias/{id}', [PDFController::class, 'guias'])->name('pdf.guias');
        Route::get('/factura-orden/{id}', [PDFController::class, 'facturaOrden'])->name('pdf.factura.orden');
        Route::get('/nota-credito-orden/{id}', [PDFController::class, 'notaCreditoOrden'])->name('pdf.nota-credito.orden');

        Route::get('/venta/{id}', [PDFController::class, 'venta'])->name('pdf.venta');
        Route::get('/cierre/{id}', [PDFController::class, 'cierre'])->name('pdf.cierre');
        Route::get('/compra/{id}', [PDFController::class, 'compra'])->name('pdf.compra');
        Route::get('/traslado/{id}', [PDFController::class, 'traslado'])->name('pdf.traslado');
        Route::get('/factura-venta/{id}', [PDFController::class, 'facturaVenta'])->name('pdf.factura.venta');
        Route::get('/nota-credito-venta/{id}', [PDFController::class, 'notaCreditoVenta'])->name('pdf.nota-credito.venta');
        Route::get('/catalogo', [PDFController::class, 'catalogo'])->name('pdf.catalogo');

        Route::get('/catalogo/pdf', [TiendaController::class, 'exportarPdf'])->name('catalogo.pdf');
        Route::get('/catalogo/pdf-historial', [TiendaController::class, 'HistorialVendidosPdf'])->name('historial.pdf');
    });
});

/* GUATEX */
Route::prefix('guatex')->group(function () {
    Route::get('/destinos/{departamento}/{municipio?}', [GUATEXController::class, 'obtenerDestinos']);
    Route::get('/destino/{codigo}', [GUATEXController::class, 'consultarCodigoDestino']);
    Route::get('/guia/{id}', [GUATEXController::class, 'generarGuia']);
    Route::get('/guia_cubeta_caneca/{id}', [GUATEXController::class, 'generarGuiaCanecasCubetas']);
    Route::get('/consulta_tracking/{tracking}', [GUATEXController::class, 'consultarTracking']);
    Route::get('/eliminar_guia/{tracking}', [GUATEXController::class, 'eliminarGuia']);
    Route::get('/recolectar/{tracking}', [GUATEXController::class, 'recolectar']);
    Route::get('/entregar/{tracking}', [GUATEXController::class, 'entregar']);
    Route::post('/liquidar', [GUATEXController::class, 'liquidar']);
    Route::get('/generar_guias_pdf/{id}', [GUATEXController::class, 'generarGuiasPdf']);
    Route::get('/generar_guias_cubetas_canecas_pdf/{id}', [GUATEXController::class, 'generarGuiasCanecasCubetasPdf']);
    Route::get('/obtenerContent/{id}', [GUATEXController::class, 'obtenerContent']);
    Route::get('/obtenerContentCanecasCubetas/{id}', [GUATEXController::class, 'obtenerContentCanecasCubetas']);
    Route::get('/actualizarOrdenesConTracking', [GUATEXController::class, 'actualizarOrdenesConTracking']);
});

require __DIR__.'/auth.php';
