<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;

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

    #REPORTES
    Route::get('/reporte/ventas', [ReporteController::class, 'Ventas'])->name('reporte.ventas');
    Route::get('/reporte/ventas-detallado', [ReporteController::class, 'VentasDetallado'])->name('reporte.ventas-detallado');
    Route::get('/reporte/pagos', [ReporteController::class, 'Pagos'])->name('reporte.pagos');
    Route::get('/reporte/resultados', [ReporteController::class, 'Resultados'])->name('reporte.resultados');

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
        Route::get('/factura-venta/{id}', [PDFController::class, 'facturaVenta'])->name('pdf.factura.venta');
        Route::get('/nota-credito-venta/{id}', [PDFController::class, 'notaCreditoVenta'])->name('pdf.nota-credito.venta');
    });
});

require __DIR__.'/auth.php';
