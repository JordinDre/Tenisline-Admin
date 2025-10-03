<?php

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
        Route::get('/comprobante-traslado/{id}', [PDFController::class, 'comprobanteTraslado'])->name('pdf.comprobante.traslado');

        Route::get('/venta/{id}', [PDFController::class, 'venta'])->name('pdf.venta');
        Route::get('/cierre/{id}', [PDFController::class, 'cierre'])->name('pdf.cierre');
        Route::get('/compra/{id}', [PDFController::class, 'compra'])->name('pdf.compra');
        Route::get('/traslado/{id}', [PDFController::class, 'traslado'])->name('pdf.traslado');
        Route::get('/factura-venta/{id}', [PDFController::class, 'facturaVenta'])->name('pdf.factura.venta');
        Route::get('/nota-credito-venta/{id}', [PDFController::class, 'notaCreditoVenta'])->name('pdf.nota-credito.venta');
        Route::get('/catalogo', [PDFController::class, 'catalogo'])->name('pdf.catalogo');

        Route::get('/catalogo/pdf', [TiendaController::class, 'exportarPdf'])->name('catalogo.pdf');
    });
});

require __DIR__.'/auth.php';
