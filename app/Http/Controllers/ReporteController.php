<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ReportePagosExport;
use App\Exports\ReporteDiarioExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteVentasGeneralExport;
use App\Exports\ReporteHistorialClienteExport;
use App\Exports\ReporteUltimaCompraClienteExport;

class ReporteController extends Controller
{
    public function VentasGeneral(Request $request)
    {
        $fecha_inicial = $request->fecha_inicial ?? $request->fecha_incial;
        $fecha_final = $request->fecha_final ?? $request->fecha_final;

        $sql = "
        WITH base AS (
            SELECT
                v.id              AS venta_id,
                v.created_at      AS venta_fecha,
                v.bodega_id,
                v.estado,
                v.cliente_id,
                v.asesor_id,
                vd.subtotal       AS importe_linea
            FROM ventas v
            JOIN venta_detalles vd ON vd.venta_id = v.id
            WHERE v.created_at >= ? AND v.created_at < DATE_ADD(?, INTERVAL 1 DAY)
              AND v.estado IN ('creada','liquidada','parcialmente_devuelta')
              AND vd.devuelto = 0
        )
        SELECT
            v.created_at,
            v.id,
            COALESCE(f.fel_uuid,  '') AS fel_uuid,
            COALESCE(f.fel_serie, '') AS fel_serie,
            COALESCE(f.fel_numero,'') AS fel_numero,
            COALESCE(f.fel_fecha, '') AS fel_fecha,
            u.created_at               AS creacion_cliente,
            u.nit,
            u.dpi,
            u.razon_social,
            v.estado,
            b.bodega                   AS bodega,
            CASE WHEN EXISTS(SELECT 1 FROM pagos p WHERE p.pagable_id = v.id) THEN '✓' ELSE 'x' END AS pago,
            COALESCE((
                SELECT GROUP_CONCAT(DISTINCT tp.tipo_pago ORDER BY tp.tipo_pago SEPARATOR ', ')
                FROM pagos p
                JOIN tipo_pagos tp ON tp.id = p.tipo_pago_id
                WHERE p.pagable_id = v.id
            ), '') AS tipo_pago,
            -- total consistente desde detalle no devuelto
            ROUND(SUM(base.importe_linea), 2) AS total,
            (SELECT name FROM users u2 WHERE u2.id = v.asesor_id) AS asesor
        FROM base
        JOIN ventas v   ON v.id = base.venta_id
        JOIN users u    ON u.id = v.cliente_id
        JOIN bodegas b  ON b.id = v.bodega_id
        LEFT JOIN (
            SELECT DISTINCT facturable_id, fel_uuid, fel_serie, fel_numero, fel_fecha
            FROM facturas
            WHERE facturable_type = 'App\\\\Models\\\\Venta' AND deleted_at IS NULL
        ) f ON f.facturable_id = v.id
        GROUP BY
            v.id, v.created_at, fel_uuid, fel_serie, fel_numero, fel_fecha,
            u.created_at, u.nit, u.dpi, u.razon_social, v.estado, b.bodega, asesor
        ORDER BY v.created_at DESC
    ";

        $data = DB::select($sql, [$fecha_inicial, $fecha_final]);

        return Excel::download(
            new ReporteVentasGeneralExport($data),
            'Ventas General fecha: '.$fecha_inicial.' - '.$fecha_final.'.xlsx'
        );
    }

    public function Pagos(Request $request)
    {
        $fecha_incial = $request->fecha_incial;
        $fecha_final = $request->fecha_final;

        $consulta = "
            SELECT
                ventas.id,
                COALESCE(facturas.fel_uuid, '') AS fel_uuid, 
                COALESCE(facturas.fel_serie, '') AS fel_serie, 
                COALESCE(facturas.fel_numero, '') AS fel_numero, 
                COALESCE(facturas.fel_fecha, '') AS fel_fecha, 
                users.nit,
                users.dpi,
                users.razon_social,
                ventas.estado,
                CASE
                    WHEN COUNT(op.id) >= 1 THEN '✓'
                    WHEN COUNT(op.id) = 0 THEN 'x'
                    ELSE 'x'
                END AS pago,
                tipo_pago,
                op.total,
                (
                    SELECT
                        name
                    FROM
                        users u
                    WHERE
                        u.id = ventas.asesor_id
                ) AS asesor
            FROM pagos op
            LEFT JOIN ventas ON op.pagable_id = ventas.id
                left JOIN users ON users.id = ventas.cliente_id
            LEFT JOIN (
                SELECT DISTINCT facturable_id, fel_uuid, fel_serie, fel_numero, fel_fecha
                FROM facturas 
                WHERE facturable_type = 'App\\\\Models\\\\Venta' 
                AND deleted_at IS NULL
            ) facturas on facturas.facturable_id = ventas.id
                left JOIN tipo_pagos ON tipo_pagos.id = op.tipo_pago_id
            WHERE
                DATE(ventas.created_at) BETWEEN ?
                AND ?
                AND ventas.estado NOT IN ('devuelta', 'anulada')
            GROUP BY
                op.id, ventas.id, facturas.fel_uuid, facturas.fel_serie, facturas.fel_numero, facturas.fel_fecha, users.nit,
                users.dpi, users.razon_social, ventas.estado, tipo_pagos.tipo_pago, op.total, ventas.asesor_id
        ";

        $data = DB::select($consulta, [
            $fecha_incial,
            $fecha_final,
        ]);

        return Excel::download(new ReportePagosExport($data), 'Pagos fecha: '.$fecha_incial.' - '.$fecha_final.'.xlsx');
    }

    public function VentasDetalle(Request $request)
    {
        $fecha_inicial = $request->fecha_inicial ?? $request->fecha_incial;
        $fecha_final = $request->fecha_final ?? $request->fecha_final;

        $sql = "
        WITH base AS (
            SELECT
                v.id              AS venta_id,
                v.created_at      AS venta_fecha,
                v.bodega_id,
                v.estado,
                v.cliente_id,
                v.asesor_id,
                vd.producto_id,
                vd.cantidad,
                vd.subtotal       AS importe_linea
            FROM ventas v
            JOIN venta_detalles vd ON vd.venta_id = v.id
            WHERE v.created_at >= ? AND v.created_at < DATE_ADD(?, INTERVAL 1 DAY)
              AND v.estado IN ('creada','liquidada','parcialmente_devuelta')
              AND vd.devuelto = 0
        )
        SELECT
            base.venta_fecha                  AS created_at,
            base.venta_id                     AS id,
            v.estado,
            v.cliente_id,
            uc.razon_social,
            uc.nit,
            v.envio,
            v.subtotal,
            v.total,
            bo.bodega                         AS bodega,
            base.producto_id,
            p.codigo,
            p.descripcion,
            m.marca,
            base.cantidad,
            p.precio_costo,
            COALESCE(p.envio, 0)              AS envio_producto,
            COALESCE(base.importe_linea, 0)   AS precio_venta,
            COALESCE(base.importe_linea, 0) - (p.precio_costo * base.cantidad) AS utilidad_bruta,
            (SELECT name FROM users u2 WHERE u2.id = v.asesor_id) AS asesor
        FROM base
        JOIN ventas v     ON v.id = base.venta_id
        JOIN users uc     ON uc.id = v.cliente_id
        JOIN productos p  ON p.id = base.producto_id
        LEFT JOIN marcas m ON m.id = p.marca_id
        JOIN bodegas bo   ON bo.id = v.bodega_id
        ORDER BY base.venta_fecha DESC, base.venta_id
    ";

        $data = DB::select($sql, [$fecha_inicial, $fecha_final]);

        return Excel::download(
            new ReporteVentasGeneralExport($data),
            'Ventas Detalle fecha: '.$fecha_inicial.' - '.$fecha_final.'.xlsx'
        );
    }

    public function HistorialCliente(Request $request)
    {
        $consulta = "
            SELECT
                users.name as nombre_cliente,
                GROUP_CONCAT(roles.name SEPARATOR ', ') AS roles_cliente,
                users.razon_social as razon_social_cliente,
                ventas.created_at as fecha_venta,
                CASE 
                    WHEN ventas.estado = 'parcialmente_devuelta' THEN 'Parcialmente Liquidado'
                    ELSE ventas.estado
                END AS estado_venta,
                venta_detalles.cantidad as cantidad_producto,
                venta_detalles.subtotal as subtotal_producto,
                bodegas.bodega as nombre_bodega,
                productos.codigo as codigo_producto,
                COALESCE(productos.descripcion, 'Sin descripción') as descripcion_producto,
                COALESCE(marcas.marca, 'Sin marca') as marca_producto,
                COALESCE(productos.talla, 'Sin talla') as talla_producto,
                COALESCE(productos.genero, 'Sin género') as genero_producto,
                (
                    SELECT
                        u.name
                    FROM
                        users u
                    WHERE
                        u.id = ventas.asesor_id
                ) as nombre_asesor
            FROM
                ventas
                INNER JOIN model_has_roles on model_has_roles.model_id = ventas.cliente_id
                INNER JOIN roles on roles.id = model_has_roles.role_id
                INNER JOIN users on users.id = ventas.cliente_id
                INNER JOIN venta_detalles on venta_detalles.venta_id = ventas.id
                INNER JOIN productos on venta_detalles.producto_id = productos.id
                LEFT JOIN marcas on productos.marca_id = marcas.id
                INNER JOIN bodegas on ventas.bodega_id = bodegas.id
            WHERE
                ventas.cliente_id = ?
                AND venta_detalles.devuelto = 0
                AND ventas.estado IN ('creada', 'liquidada', 'parcialmente_devuelta')
            GROUP BY
                ventas.id,
                users.name,
                users.razon_social,
                ventas.created_at,
                ventas.estado,
                venta_detalles.cantidad,
                venta_detalles.subtotal,
                bodegas.bodega,
                productos.codigo,
                productos.descripcion,
                marcas.marca,
                productos.talla,
                productos.genero,
                nombre_asesor,
                venta_detalles.devuelto
        ";

        $data = DB::select($consulta, [
            $request->cliente_id,
        ]);

        return Excel::download(new ReporteHistorialClienteExport($data), 'Historial del cliente con id: '.$request->cliente_id.'.xlsx');
    }

    public function Resultados(Request $request)
    {
        $fecha_inicial = $request->fecha_inicial;
        $fecha_final = $request->fecha_final;

        $sql = "
        WITH base AS (
            SELECT
                v.id              AS venta_id,
                v.created_at      AS venta_fecha,
                v.bodega_id,
                v.estado,
                v.asesor_id,
                vd.producto_id,
                vd.cantidad,
                vd.subtotal       AS importe_linea
            FROM ventas v
            JOIN venta_detalles vd ON vd.venta_id = v.id
            WHERE v.created_at >= ? AND v.created_at < DATE_ADD(?, INTERVAL 1 DAY)
              AND v.estado IN ('creada','liquidada','parcialmente_devuelta')
              AND vd.devuelto = 0
        ),
        diario AS (
            SELECT
                b.bodega_id,
                DATE(b.venta_fecha)                     AS fecha_dia,
                COUNT(DISTINCT b.venta_id)              AS ventas_dia,
                SUM(b.importe_linea)                    AS precio_venta,
                SUM(COALESCE(p.precio_costo,0) * b.cantidad) AS precio_costo
            FROM base b
            JOIN productos p ON p.id = b.producto_id
            GROUP BY b.bodega_id, DATE(b.venta_fecha)
        ),
        turnos AS (
            SELECT
                c.bodega_id,
                DATE(c.created_at) AS fecha_dia,
                GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS turno
            FROM cierres c
            JOIN users u ON u.id = c.user_id
            WHERE c.created_at >= ? AND c.created_at < DATE_ADD(?, INTERVAL 1 DAY)
            GROUP BY c.bodega_id, DATE(c.created_at)
        )
        SELECT
            bo.bodega                                         AS Tienda,
            d.fecha_dia                                       AS Fecha,
            COALESCE(t.turno, '')                             AS Turno,
            d.ventas_dia                                      AS Facturas,
            d.precio_venta                                    AS Precio_Venta,
            d.precio_costo                                    AS Precio_Costo,
            (d.precio_venta - d.precio_costo)                 AS Utilidad_Dia,
            ROUND((d.precio_venta - d.precio_costo) - (d.precio_venta * 0.025), 2) AS Utilidad_Neta
        FROM diario d
        JOIN bodegas bo ON bo.id = d.bodega_id
        LEFT JOIN turnos t ON t.bodega_id = d.bodega_id AND t.fecha_dia = d.fecha_dia
        ORDER BY d.fecha_dia ASC, bo.bodega ASC
    ";

        $data = DB::select($sql, [$fecha_inicial, $fecha_final, $fecha_inicial, $fecha_final]);

        return Excel::download(
            new ReporteDiarioExport($data),
            'Reporte Resultados '.$fecha_inicial.' - '.$fecha_final.'.xlsx'
        );
    }

    public function ReporteInventario()
    {

        $fecha = Carbon::now()->format('d:m:y');

        $sql = "
        SELECT
            p.id AS producto_id,
            p.codigo,
            p.descripcion,
            m.marca AS marca,
            p.precio_venta,
            p.precio_costo,
            COALESCE(MAX(CASE WHEN i.bodega_id = 1 THEN i.existencia END), 0) AS zacapa,
            COALESCE(MAX(CASE WHEN i.bodega_id = 2 THEN i.existencia END), 0) AS central_bodega,
            COALESCE(MAX(CASE WHEN i.bodega_id = 3 THEN i.existencia END), 0) AS mal_estado,
            COALESCE(MAX(CASE WHEN i.bodega_id = 4 THEN i.existencia END), 0) AS traslado,
            COALESCE(MAX(CASE WHEN i.bodega_id = 5 THEN i.existencia END), 0) AS zacapa_bodega,
            COALESCE(MAX(CASE WHEN i.bodega_id = 6 THEN i.existencia END), 0) AS chiquimula,
            COALESCE(MAX(CASE WHEN i.bodega_id = 7 THEN i.existencia END), 0) AS chiquimula_bodega,
            COALESCE(MAX(CASE WHEN i.bodega_id = 8 THEN i.existencia END), 0) AS esquipulas,
            COALESCE(MAX(CASE WHEN i.bodega_id = 9 THEN i.existencia END), 0) AS esquipulas_bodega,
            COALESCE(SUM(i.existencia),0) AS total_existencias
        FROM productos p
        LEFT JOIN inventarios i ON i.producto_id = p.id
        LEFT JOIN marcas m ON m.id = p.marca_id
        GROUP BY
            p.id, p.codigo, p.descripcion, m.marca,
            p.precio_venta, p.precio_costo
        ORDER BY p.id;
        ";

        $data = DB::select($sql);

        return Excel::download(
            new ReporteUltimaCompraClienteExport($data),
            'Reporte Inventario fecha:'.$fecha.'.xlsx'
        );
    }
}
