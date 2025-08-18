<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ReportePagosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteResultadosExport;
use App\Exports\ReporteVentasClientesExport;
use App\Exports\ReporteHistorialClienteExport;
use App\Exports\ReporteVentasDetalladasExport;

class ReporteController extends Controller
{
    public function Ventas(Request $request)
    {
        $fecha_incial = $request->fecha_incial;
        $fecha_final = $request->fecha_final;

        $consulta = "
            SELECT
                ventas.created_at,
                ventas.id,
                facturas.fel_uuid, 
                facturas.fel_serie, 
                facturas.fel_numero, 
                facturas.fel_fecha, 
                users.created_at AS creacion_cliente,
                users.nit,
                users.dpi,
                users.razon_social,
                ventas.estado,
                bodegas.bodega AS bodega,
                CASE
                    WHEN COUNT(op.id) >= 1 THEN '✓'
                    WHEN COUNT(op.id) = 0 THEN 'x'
                    ELSE 'x'
                END AS pago,
                tipo_pago,
                ventas.total,
                (
                    SELECT
                        name
                    FROM
                        users u
                    WHERE
                        u.id = ventas.asesor_id
                ) AS asesor
            FROM ventas
                JOIN users ON users.id = ventas.cliente_id
                LEFT JOIN facturas ON facturas.facturable_id = ventas.id
                LEFT JOIN pagos op ON op.pagable_id = ventas.id
                JOIN tipo_pagos ON tipo_pagos.id = op.tipo_pago_id
                JOIN bodegas ON bodegas.id = ventas.bodega_id
            WHERE
                DATE(ventas.created_at) BETWEEN ?
                AND ?
                AND ventas.estado NOT IN ('devuelta', 'anulada')
            GROUP BY
                ventas.id, ventas.created_at, facturas.fel_uuid, facturas.fel_serie, facturas.fel_numero, facturas.fel_fecha, users.created_at,
                users.nit, users.dpi, users.razon_social, ventas.estado, bodegas.bodega, tipo_pagos.tipo_pago, ventas.total, ventas.asesor_id, ventas.tipo_envio
        ";

        $data = DB::select($consulta, [
            $fecha_incial,
            $fecha_final,
        ]);

        return Excel::download(new ReporteVentasClientesExport($data), 'Ventas fecha: '.$fecha_incial.' - '.$fecha_final.'.xlsx');
    }

    public function VentasDetallado(Request $request)
    {

        $consulta = DB::select('
            SELECT
                ventas.created_at,
                ventas.id,
                ventas.estado,
                ventas.cliente_id,
                users.razon_social,
                users.nit,
                ventas.envio,
                ventas.subtotal,
                ventas.total,
                bodegas.bodega AS bodega,
                venta_detalles.producto_id,
                productos.codigo,
                productos.descripcion,
                marcas.marca,
                venta_detalles.cantidad,
                productos.precio_compra,
                COALESCE(productos.envio, 0) as envio_producto,
                COALESCE(venta_detalles.precio, 0) AS precio_venta,
                COALESCE(venta_detalles.precio, 0) - (productos.precio_compra) * venta_detalles.cantidad AS utilidad_bruta,
                COALESCE(venta_detalles.precio, 0) * venta_detalles.cantidad AS subtotal_detalle,
                (SELECT name FROM users u WHERE u.id = ventas.asesor_id) AS asesor
            FROM
                venta_detalles
                INNER JOIN ventas ON venta_detalles.venta_id = ventas.id
                INNER JOIN users ON users.id = ventas.cliente_id
                INNER JOIN productos ON productos.id = venta_detalles.producto_id
                INNER JOIN marcas ON marcas.id = productos.marca_id
                INNER JOIN bodegas ON bodegas.id = ventas.bodega_id
            WHERE
                MONTH(ventas.created_at) = ?
                AND YEAR(ventas.created_at) = ?
            ORDER BY
                asesor ASC
        ', [
            $request->mes,
            $request->año,
        ]);

        return Excel::download(new ReporteVentasDetalladasExport($consulta), 'Ventas Detalladas Mes: '.$request->mes.' Año: '.$request->año.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function Pagos(Request $request)
    {
        $fecha_incial = $request->fecha_incial;
        $fecha_final = $request->fecha_final;

        $consulta = "
            SELECT
                ventas.id,
                facturas.fel_uuid, 
                facturas.fel_serie, 
                facturas.fel_numero, 
                facturas.fel_fecha, 
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
            LEFT JOIN facturas on facturas.facturable_id = ventas.id
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

    public function Resultados(Request $request)
    {
        $consulta = DB::select("
           WITH comodin AS (
                SELECT
                    users.id,
                    users.name,
                    (
                        SELECT
                            COUNT(o.cliente_id)
                        FROM
                            ventas o
                        WHERE
                            o.asesor_id = ventas.asesor_id
                            AND YEAR(o.created_at) = ?
                            AND MONTH(o.created_at) = ?
                            AND o.estado IN('liquidada')
                    ) AS cantidad_pedidos,
                    model_has_roles.role_id,
                    SUM(
                        (
                            (
                                productos.precio_compra
                            ) * venta_detalles.cantidad
                        )
                    ) AS costo_compra,
                    SUM(
                        (
                            venta_detalles.precio * venta_detalles.cantidad
                        )
                    ) AS costo_venta,
                    SUM(
                        venta_detalles.cantidad * venta_detalles.precio
                    ) AS subtotal_ordenes_detalladas,
                    (SELECT SUM(od.devuelto * od.precio) FROM venta_detalles od
		    inner join ventas v on ventas.id = od.venta_id
                    WHERE v.asesor_id = users.id AND YEAR(v.created_at) = ?
                    AND MONTH(v.created_at) = ? and v.estado = 'devueltas') AS subtotal_ordenes_devueltas,
                    (SELECT SUM(od.cantidad * od.precio) FROM venta_detalles od
                    INNER JOIN ventas o ON o.id = od.venta_id
                    WHERE o.estado NOT IN ('anuladas', 'devueltas') AND o.asesor_id = users.id AND YEAR(o.created_at) = ?
                    AND MONTH(o.created_at) = ?) AS subtotal_ordenes_todas,
                    (SELECT COUNT(DISTINCT(o.cliente_id)) FROM ventas o 
                    inner join users u on o.cliente_id = u.id WHERE o.asesor_id = users.id 
                    AND o.estado IN('liquidada') AND YEAR(o.created_at) = ?
                    AND MONTH(o.created_at) = ? and year(u.created_at) != ? and month(u.created_at) != ?  ) AS cobertura_clientes,
                    IFNULL((
                        SELECT
                            SUM(o.total)
                            FROM ventas o
                        WHERE
                            asesor_id = users.id
                            AND YEAR(o.created_at) = ?
                            AND MONTH(o.created_at) = ?
                            AND o.estado NOT IN('anuladas', 'devueltas')), 0) AS total
                FROM
                    venta_detalles
                    INNER JOIN ventas ON ventas.id = venta_detalles.venta_id
                    INNER JOIN productos ON venta_detalles.producto_id = productos.id
                    INNER JOIN users ON ventas.asesor_id = users.id
                    LEFT JOIN model_has_roles ON model_has_roles.model_id = users.id
                WHERE
                    YEAR(ventas.created_at) = ?
                    AND MONTH(ventas.created_at) = ?
                    AND ventas.estado IN('liquidada')
                GROUP BY
                    users.id, role_id, users.name, ventas.asesor_id, ventas.id
                HAVING
                    model_has_roles.role_id IN (5)
            )
            SELECT
                id,
                name,
                ROUND(subtotal_ordenes_detalladas, 2) AS venta_mes,
                round(((total - costo_compra) / total)* 100 / 2,2) as rendimiento,
                cobertura_clientes as clientes_cartera_vendidos,
                ROUND((subtotal_ordenes_detalladas/cobertura_clientes),2) AS ticket_promedio,
                cantidad_pedidos,
                ROUND(costo_compra, 2) AS costo_compra,
                ROUND((costo_venta-costo_compra), 2) AS utilidad_bruta,
                ROUND(((subtotal_ordenes_detalladas-costo_compra)/subtotal_ordenes_detalladas) * 100, 2) AS margen_bruto,
                CASE
                    WHEN role_id = 5 THEN 'vendedor'
                    ELSE ''
                END AS Contrato, 
                ifnull(subtotal_ordenes_devueltas,0) AS devoluciones
            FROM
                comodin;
        ", [
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
            $request->año,
            $request->mes,
        ]);

        return Excel::download(new ReporteResultadosExport($consulta), 'Resultados Mes: '.$request->mes.' Año: '.$request->año.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function VentasGeneral(Request $request)
    {
        $fecha_incial = $request->fecha_incial;
        $fecha_final = $request->fecha_final;

        $consulta = '
            select
                ventas.created_at as fecha_venta,
                facturas.fel_numero as numero_factura,
                ventas.estado,
                users.id,
                users.razon_social,
                users.nit,
                productos.precio_oferta,
                productos.precio_venta,
                productos.precio_costo,
                bodegas.bodega,
                productos.codigo,
                productos.descripcion,
                marcas.marca,
                productos.talla,
                productos.genero,
                (
                    select
                        u.name
                    from
                        users u
                    where
                        u.id = ventas.asesor_id
                ) as asesor
            from
                ventas
                inner join facturas on facturas.facturable_id = ventas.id
                inner join users on users.id = ventas.cliente_id
                inner join venta_detalles on venta_detalles.venta_id = ventas.id
                inner join productos on venta_detalles.producto_id = productos.id
                inner join marcas on productos.marca_id = marcas.id
                inner join bodegas on ventas.bodega_id = bodegas.id
            WHERE
                ventas.created_at BETWEEN ?
                AND ?
        ';

        $data = DB::select($consulta, [
            $fecha_incial,
            $fecha_final,
        ]);

        return Excel::download(new ReporteVentasClientesExport($data), 'Ventas General fecha: '.$fecha_incial.' - '.$fecha_final.'.xlsx');
    }

    public function HistorialCliente(Request $request)
    {
        $consulta = "
            select
            users.name,
            GROUP_CONCAT(roles.name SEPARATOR ', ') AS roles,
            users.razon_social,
            ventas.created_at as fecha_venta,
            ventas.estado,
            venta_detalles.cantidad,
            venta_detalles.subtotal,
            bodegas.bodega,
            productos.codigo,
            productos.descripcion,
            marcas.marca,
            productos.talla,
            productos.genero,
            (
                select
                    u.name
                from
                    users u
                where
                    u.id = ventas.asesor_id
            ) as asesor
        from
            ventas
            inner join model_has_roles on model_has_roles.model_id = ventas.cliente_id
            inner join roles on roles.id = model_has_roles.role_id
            inner join users on users.id = ventas.cliente_id
            inner join venta_detalles on venta_detalles.venta_id = ventas.id
            inner join productos on venta_detalles.producto_id = productos.id
            inner join marcas on productos.marca_id = marcas.id
            inner join bodegas on ventas.bodega_id = bodegas.id
        WHERE
            ventas.cliente_id = ?
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
            asesor
        ";

        $data = DB::select($consulta, [
            $request->cliente_id
        ]);
        
        return Excel::download(new ReporteHistorialClienteExport($data), 'Historial del cliente con id: '.$request->cliente_id.'.xlsx');
    }
}
