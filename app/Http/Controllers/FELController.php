<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;

class FELController extends Controller
{
    public static function facturaOrden($orden)
    {
        $cliente = User::withTrashed()->find($orden->cliente_id);
        $receptorID = $orden->facturar_cf ? 'CF' : $cliente->nit;
        $receptorNombre = $orden->facturar_cf ? $cliente->name : $cliente->razon_social;
        $tipo = $orden->tipo_pago_id == 2 ? 'FCAM' : 'FACT';

        $totalMontoImpuesto = 0;
        $xmlItems = '';
        $correlativo = 1;
        $granTotal = 0;
        foreach ($orden->detalles as $item) {
            $producto = Producto::withTrashed()->find($item->producto_id);
            $descripcion = $producto->codigo.' - '.$producto->descripcion.' - '.$producto->marca->marca.' - '.$producto->presentacion->presentacion;
            $precioUnitario = $item->precio;
            $precioTotal = round($item->cantidad * $precioUnitario, 2);
            $montoGravable = round($precioTotal / 1.12, 2);
            $montoImpuesto = round($montoGravable * 0.12, 2);
            $totalMontoImpuesto += round($montoImpuesto, 2);
            $granTotal += round($item->cantidad * $item->precio, 2);

            $xmlItems .= '<dte:Item BienOServicio="B" NumeroLinea="'.$correlativo.'">
            <dte:Cantidad>'.$item->cantidad.'</dte:Cantidad>
            <dte:UnidadMedida>UNI</dte:UnidadMedida>
            <dte:Descripcion>'.$descripcion.'</dte:Descripcion>
            <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
            <dte:Precio>'.$precioTotal.'</dte:Precio>
            <dte:Descuento>0.00</dte:Descuento>
            <dte:Impuestos>
            <dte:Impuesto>
            <dte:NombreCorto>IVA</dte:NombreCorto>
            <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
            <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
            <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
            </dte:Impuesto>
            </dte:Impuestos>
            <dte:Total>'.$precioTotal.'</dte:Total>
            </dte:Item>';
            $correlativo++;
        }

        if ($orden->envio > 0) {
            $precioUnitario = $orden->envio;
            $precioTotal = $orden->envio;
            $montoGravable = round($precioTotal / 1.12, 2);
            $montoImpuesto = round($montoGravable * 0.12, 2);
            $totalMontoImpuesto += round($montoImpuesto, 2);
            $granTotal += round($orden->envio, 2);

            $xmlItems .= '<dte:Item BienOServicio="S" NumeroLinea="'.$correlativo.'">
            <dte:Cantidad>1</dte:Cantidad>
            <dte:UnidadMedida>ENV</dte:UnidadMedida>
            <dte:Descripcion>SERVICIO DE ENVÍO</dte:Descripcion>
            <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
            <dte:Precio>'.$precioTotal.'</dte:Precio>
            <dte:Descuento>0.00</dte:Descuento>
            <dte:Impuestos>
            <dte:Impuesto>
            <dte:NombreCorto>IVA</dte:NombreCorto>
            <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
            <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
            <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
            </dte:Impuesto>
            </dte:Impuestos>
            <dte:Total>'.$precioTotal.'</dte:Total>
            </dte:Item>';
            $correlativo++;
        }

        $xmlComplementos = '';
        if ($orden->tipo_pago_id == 2) {
            $xmlComplementos = '<dte:Complementos>
            <dte:Complemento IDComplemento="Cambiaria" NombreComplemento="Cambiaria" URIComplemento="http://www.sat.gob.gt/fel/cambiaria.xsd">
            <cfc:AbonosFacturaCambiaria xmlns:cfc="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0" Version="1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0 C:\Users\Desktop\SAT_FEL_FINAL_V1\Esquemas\GT_Complemento_Cambiaria-0.1.0.xsd">
            <cfc:Abono>
            <cfc:NumeroAbono>1</cfc:NumeroAbono>
            <cfc:FechaVencimiento>'.Carbon::parse($orden->fecha_vencimiento)->format('Y-m-d').'</cfc:FechaVencimiento>
            <cfc:MontoAbono>'.$precioTotal.'</cfc:MontoAbono>
            </cfc:Abono>
            </cfc:AbonosFacturaCambiaria>
            </dte:Complemento>
            </dte:Complementos>';
        }

        $curl = curl_init();
        $tipoEspecial = ($cliente->nit || $orden->facturar_cf) ? '' : ' TipoEspecial="CUI"';

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="UTF-8"?>
            <dte:GTDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.2.0">
                <dte:SAT ClaseDocumento="dte">
                    <dte:DTE ID="DatosCertificados">
                        <dte:DatosEmision ID="DatosEmision">
                        <dte:DatosGenerales CodigoMoneda="GTQ" FechaHoraEmision="'.now()->format('Y-m-d\TH:i:s').'-06:00" Tipo="'.$tipo.'"></dte:DatosGenerales>
                        <dte:Emisor AfiliacionIVA="GEN" CodigoEstablecimiento="2" NITEmisor="'.env('NIT').'" NombreComercial="'.env('NOMBRE_COMERCIAL').'" NombreEmisor="'.env('RAZON_SOCIAL').'">
                            <dte:DireccionEmisor>
                                <dte:Direccion>'.env('DIRECCION').'</dte:Direccion>
                                <dte:CodigoPostal>'.env('CODIGO_POSTAL').'</dte:CodigoPostal>
                                <dte:Municipio>'.env('MUNICIPIO').'</dte:Municipio>
                                <dte:Departamento>'.env('DEPARTAMENTO').'</dte:Departamento>
                                <dte:Pais>'.env('PAIS').'</dte:Pais>
                            </dte:DireccionEmisor>
                        </dte:Emisor>
                        <dte:Receptor IDReceptor="'.$receptorID.'" NombreReceptor="'.$receptorNombre.'" CorreoReceptor="'.env('CORREO').'"'.$tipoEspecial.'>
                            <dte:DireccionReceptor>
                            <dte:Direccion>'.Direccion::withTrashed()->find($orden->direccion_id)->direccion.'</dte:Direccion>
                            <dte:CodigoPostal>0</dte:CodigoPostal>
                            <dte:Municipio>'.Direccion::withTrashed()->find($orden->direccion_id)->municipio->municipio.'</dte:Municipio>
                            <dte:Departamento>'.Direccion::withTrashed()->find($orden->direccion_id)->departamento->departamento.'</dte:Departamento>
                            <dte:Pais>'.env('PAIS').'</dte:Pais>
                            </dte:DireccionReceptor>
                        </dte:Receptor>
                        <dte:Frases>
                            <dte:Frase CodigoEscenario="1" TipoFrase="1"></dte:Frase>
                        </dte:Frases>
                        <dte:Items>'.$xmlItems.'</dte:Items>
                        <dte:Totales>
                            <dte:TotalImpuestos>
                            <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="'.$totalMontoImpuesto.'"></dte:TotalImpuesto>
                            </dte:TotalImpuestos>
                            <dte:GranTotal>'.$granTotal.'</dte:GranTotal>
                        </dte:Totales>
                        '.$xmlComplementos.'
                        </dte:DatosEmision>
                    </dte:DTE>  
                    <dte:Adenda>
                        <CondicionesPago>'.$orden->tipo_pago->tipo_pago.'</CondicionesPago>
                        <SolicitadoPor>'.User::withTrashed()->find($orden->asesor_id)->name.'</SolicitadoPor>
                        <Vendedor>'.User::withTrashed()->find($orden->asesor_id)->id.'</Vendedor>
                        <Envio>'.$orden->tipo_envio->value.'</Envio>
                        <OrdenCompra>'.$orden->id.'</OrdenCompra>
                        <TelReceptor>'.$cliente->telefono.'</TelReceptor>
                        <PBX>'.env('PBX').'</PBX>
                    </dte:Adenda>
                </dte:SAT>
            </dte:GTDocumento>',

            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'UsuarioFirma: '.config('services.fel.usuario_firma'),
                'Identificador:'.config('services.fel.identificador').'FACTURA-ORDEN-'.$orden->id,
                'LlaveFirma: '.config('services.fel.llave_firma'),
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, true);

        return $responseData;
    }

    public static function anularFacturaOrden($orden, $motivo)
    {
        $cliente = User::withTrashed()->find($orden->cliente_id);
        $receptorID = $orden->facturar_cf ? 'CF' : $cliente->nit;
        $xml = '<dte:GTAnulacionDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.1.0" xmlns:n1="http://www.altova.com/samplexml/other-namespace" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.1.0 C:\Users\User\Desktop\FEL\Esquemas\GT_AnulacionDocumento-0.1.0.xsd">
            <dte:SAT>
                <dte:AnulacionDTE ID="DatosCertificados">
                <dte:DatosGenerales FechaEmisionDocumentoAnular="'.Carbon::parse($orden->factura->fel_fecha)->format('Y-m-d').'" FechaHoraAnulacion="'.now()->format('Y-m-d\TH:i:s').'" ID="DatosAnulacion" IDReceptor="'.$receptorID.'" MotivoAnulacion="'.$motivo.'" NITEmisor="'.env('NIT').'" NumeroDocumentoAAnular="'.$orden->factura->fel_uuid.'"></dte:DatosGenerales>
                </dte:AnulacionDTE>
            </dte:SAT>
            </dte:GTAnulacionDocumento>';

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'UsuarioFirma: '.config('services.fel.usuario_firma'),
                'Identificador:'.config('services.fel.identificador').'ANULACION-ORDEN-'.$orden->id,
                'LlaveFirma: '.config('services.fel.llave_firma'),
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        $responseData = json_decode($response, true);

        return $responseData;
    }

    public static function devolverFacturaOrden($orden, $motivo)
    {
        $cliente = User::withTrashed()->find($orden->cliente_id);
        $receptorID = $orden->facturar_cf ? 'CF' : $cliente->nit;
        $receptorNombre = $orden->facturar_cf ? $cliente->name : $cliente->razon_social;

        $totalMontoImpuesto = 0;
        $granTotal = 0;
        $xmlItems = '';
        $correlativo = 1;
        foreach ($orden->detalles as $item) {
            if ($item->devuelto > 0) {
                $producto = Producto::withTrashed()->find($item->producto_id);
                $descripcion = $producto->codigo.' - '.$producto->descripcion.' - '.$producto->marca->marca.' - '.$producto->presentacion->presentacion;
                $precioUnitario = $item->precio;
                $precioTotal = round($item->devuelto * $precioUnitario, 2);
                $montoGravable = round($precioTotal / 1.12, 2);
                $montoImpuesto = round($montoGravable * 0.12, 2);
                $totalMontoImpuesto += round($montoImpuesto, 2);
                $granTotal += round($item->devuelto * $item->precio, 2);
                $xmlItems .= '<dte:Item BienOServicio="B" NumeroLinea="'.$correlativo.'">
                <dte:Cantidad>'.$item->devuelto.'</dte:Cantidad>
                <dte:UnidadMedida>UNI</dte:UnidadMedida>
                <dte:Descripcion>'.$descripcion.'</dte:Descripcion>
                <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
                <dte:Precio>'.$precioTotal.'</dte:Precio>
                <dte:Descuento>0.00</dte:Descuento>
                <dte:Impuestos>
                <dte:Impuesto>
                <dte:NombreCorto>IVA</dte:NombreCorto>
                <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
                <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
                <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
                </dte:Impuesto>
                </dte:Impuestos>
                <dte:Total>'.$precioTotal.'</dte:Total>
                </dte:Item>';
                $correlativo++;
            }
        }

        if ($orden->envio) {
            $precioUnitario = $orden->envio;
            $precioTotal = $orden->envio;
            $montoGravable = round($precioTotal / 1.12, 2);
            $montoImpuesto = round($montoGravable * 0.12, 2);
            $totalMontoImpuesto += round($montoImpuesto, 2);
            $granTotal += round($orden->envio, 2);

            $xmlItems .= '<dte:Item BienOServicio="S" NumeroLinea="'.$correlativo.'">
            <dte:Cantidad>1</dte:Cantidad>
            <dte:UnidadMedida>ENV</dte:UnidadMedida>
            <dte:Descripcion>SERVICIO DE ENVÍO</dte:Descripcion>
            <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
            <dte:Precio>'.$precioTotal.'</dte:Precio>
            <dte:Descuento>0.00</dte:Descuento>
            <dte:Impuestos>
            <dte:Impuesto>
            <dte:NombreCorto>IVA</dte:NombreCorto>
            <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
            <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
            <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
            </dte:Impuesto>
            </dte:Impuestos>
            <dte:Total>'.$precioTotal.'</dte:Total>
            </dte:Item>';
            $correlativo++;
        }

        $curl = curl_init();
        $tipoEspecial = ($cliente->nit || $orden->facturar_cf) ? '' : ' TipoEspecial="CUI"';

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="UTF-8"?>
            <dte:GTDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.2.0">
              <dte:SAT ClaseDocumento="dte">
                <dte:DTE ID="DatosCertificados">
                  <dte:DatosEmision ID="DatosEmision">
                        <dte:DatosGenerales CodigoMoneda="GTQ" FechaHoraEmision="'.now()->format('Y-m-d\TH:i:s').'-06:00" Tipo="NCRE"></dte:DatosGenerales>
                        <dte:Emisor AfiliacionIVA="GEN" CodigoEstablecimiento="2" NITEmisor="'.env('NIT').'" NombreComercial="'.env('NOMBRE_COMERCIAL').'" NombreEmisor="'.env('RAZON_SOCIAL').'">
                        <dte:DireccionEmisor>
                        <dte:Direccion>'.env('DIRECCION').'</dte:Direccion>
                        <dte:CodigoPostal>'.env('CODIGO_POSTAL').'</dte:CodigoPostal>
                        <dte:Municipio>'.env('MUNICIPIO').'</dte:Municipio>
                        <dte:Departamento>'.env('DEPARTAMENTO').'</dte:Departamento>
                        <dte:Pais>'.env('PAIS').'</dte:Pais>
                        </dte:DireccionEmisor>
                        </dte:Emisor>
                        <dte:Receptor IDReceptor="'.$receptorID.'" NombreReceptor="'.$receptorNombre.'" CorreoReceptor="'.env('CORREO').'"'.$tipoEspecial.'>
                        <dte:DireccionReceptor>
                        <dte:Direccion>'.Direccion::withTrashed()->find($orden->direccion_id)->direccion.'</dte:Direccion>
                        <dte:CodigoPostal>0</dte:CodigoPostal>
                        <dte:Municipio>'.Direccion::withTrashed()->find($orden->direccion_id)->municipio->municipio.'</dte:Municipio>
                        <dte:Departamento>'.Direccion::withTrashed()->find($orden->direccion_id)->departamento->departamento.'</dte:Departamento>
                        <dte:Pais>'.env('PAIS').'</dte:Pais>
                        </dte:DireccionReceptor>
                        </dte:Receptor>
                        <dte:Frases>
                        <dte:Frase CodigoEscenario="1" TipoFrase="1"></dte:Frase>
                        </dte:Frases>
                        <dte:Items>'.$xmlItems.'</dte:Items>
                        <dte:Totales>
                            <dte:TotalImpuestos>
                            <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="'.$totalMontoImpuesto.'"></dte:TotalImpuesto>
                            </dte:TotalImpuestos>
                            <dte:GranTotal>'.$granTotal.'</dte:GranTotal>
                        </dte:Totales>
                        <dte:Complementos>
                        <dte:Complemento IDComplemento="TEXT" NombreComplemento="TEXT" URIComplemento="TEXT">
                            <cno:ReferenciasNota xmlns:cno="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0" FechaEmisionDocumentoOrigen="'.Carbon::parse($orden->factura->fel_fecha)->format('Y-m-d').'" MotivoAjuste="'.$motivo.'" NumeroAutorizacionDocumentoOrigen="'.$orden->factura->fel_uuid.'" NumeroDocumentoOrigen="'.$orden->factura->fel_serie.'" SerieDocumentoOrigen="'.$orden->factura->fel_numero.'" Version="0.0" xsi:schemaLocation="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0 C:\Users\User\Desktop\FEL\Esquemas\GT_Complemento_Referencia_Nota-0.1.0.xsd"></cno:ReferenciasNota>
                        </dte:Complemento>
                        </dte:Complementos>
                    </dte:DatosEmision>
                </dte:DTE>
                <dte:Adenda>
                    <CondicionesPago>'.$orden->tipo_pago->tipo_pago.'</CondicionesPago>
                    <SolicitadoPor>'.User::withTrashed()->find($orden->asesor_id)->name.'</SolicitadoPor>
                    <Vendedor>'.User::withTrashed()->find($orden->asesor_id)->id.'</Vendedor>
                    <Envio>'.$orden->tipo_envio->value.'</Envio>
                    <OrdenCompra>'.$orden->id.'</OrdenCompra>
                    <TelReceptor>'.$cliente->telefono.'</TelReceptor>
                    <PBX>'.env('PBX').'</PBX>
                </dte:Adenda>
              </dte:SAT>
            </dte:GTDocumento>',

            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'UsuarioFirma: '.config('services.fel.usuario_firma'),
                'Identificador:'.config('services.fel.identificador').'DEVOLUCION-ORDEN-'.$orden->id,
                'LlaveFirma: '.config('services.fel.llave_firma'),
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, true);

        return $responseData;
    }

    public static function facturaVenta($venta, $bodega)
    {
        $cliente = User::withTrashed()->find($venta->cliente_id);
        $receptorID = $venta->facturar_cf == false ? $cliente->nit : 'CF';
        $receptorNombre = $venta->facturar_cf == false ? $cliente->razon_social : $cliente->name;
        $tipo = $venta->pagos->first()->tipo_pago_id == 2 ? 'FCAM' : 'FACT';

        $emisor = $bodega == 6 ? config('services.fel2') : config('services.fel');

        $codigo = $bodega == 6 ? 4 : 2;

        $totalMontoImpuesto = 0;
        $xmlItems = '';
        $correlativo = 1;
        $granTotal = 0;

        foreach ($venta->detalles as $item) {
            $producto = Producto::withTrashed()->find($item->producto_id);
            $descripcion = $producto->codigo.' - '.$producto->descripcion.' - '.$producto->marca->marca;
            $precioUnitario = $item->precio;
            $precioTotal = round($item->cantidad * $precioUnitario, 2);
            $montoGravable = round($precioTotal / 1.12, 2);
            $montoImpuesto = round($montoGravable * 0.12, 2);
            $totalMontoImpuesto += round($montoImpuesto, 2);
            $granTotal += round($precioTotal, 2);

            $xmlItems .= '<dte:Item BienOServicio="B" NumeroLinea="'.$correlativo.'">
                <dte:Cantidad>'.$item->cantidad.'</dte:Cantidad>
                <dte:UnidadMedida>UNI</dte:UnidadMedida>
                <dte:Descripcion>'.$descripcion.'</dte:Descripcion>
                <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
                <dte:Precio>'.$precioTotal.'</dte:Precio>
                <dte:Descuento>0.00</dte:Descuento>
                <dte:Impuestos>
                    <dte:Impuesto>
                        <dte:NombreCorto>IVA</dte:NombreCorto>
                        <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
                        <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
                        <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
                    </dte:Impuesto>
                </dte:Impuestos>
                <dte:Total>'.$precioTotal.'</dte:Total>
            </dte:Item>';

            $correlativo++;
        }

        $xmlComplementos = '';
        if ($venta->pagos->first()->tipo_pago_id == 2) {
            $xmlComplementos = '<dte:Complementos>
                <dte:Complemento IDComplemento="Cambiaria" NombreComplemento="Cambiaria" URIComplemento="http://www.sat.gob.gt/fel/cambiaria.xsd">
                <cfc:AbonosFacturaCambiaria xmlns:cfc="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0" Version="1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0 C:\Users\Desktop\SAT_FEL_FINAL_V1\Esquemas\GT_Complemento_Cambiaria-0.1.0.xsd">
                    <cfc:Abono>
                        <cfc:NumeroAbono>1</cfc:NumeroAbono>
                        <cfc:FechaVencimiento>'.Carbon::parse($venta->fecha_vencimiento)->format('Y-m-d').'</cfc:FechaVencimiento>
                        <cfc:MontoAbono>'.$precioTotal.'</cfc:MontoAbono>
                    </cfc:Abono>
                </cfc:AbonosFacturaCambiaria>
                </dte:Complemento>
            </dte:Complementos>';
        }

        $tipoEspecial = ($cliente->nit || $venta->facturar_cf) ? '' : ' TipoEspecial="CUI"';

        $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>
        <dte:GTDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.2.0">
            <dte:SAT ClaseDocumento="dte">
                <dte:DTE ID="DatosCertificados">
                    <dte:DatosEmision ID="DatosEmision">
                        <dte:DatosGenerales CodigoMoneda="GTQ" FechaHoraEmision="'.now()->format('Y-m-d\TH:i:s').'-06:00" Tipo="'.$tipo.'"/>
                        <dte:Emisor AfiliacionIVA="GEN" CodigoEstablecimiento="'.$codigo.'" NITEmisor="'.$emisor['nit'].'" NombreComercial="'.$emisor['nombre_comercial'].'" NombreEmisor="'.$emisor['razon_social'].'">
                            <dte:DireccionEmisor>
                                <dte:Direccion>'.$emisor['direccion'].'</dte:Direccion>
                                <dte:CodigoPostal>'.$emisor['codigo_postal'].'</dte:CodigoPostal>
                                <dte:Municipio>'.$emisor['municipio'].'</dte:Municipio>
                                <dte:Departamento>'.$emisor['departamento'].'</dte:Departamento>
                                <dte:Pais>'.$emisor['pais'].'</dte:Pais>
                            </dte:DireccionEmisor>
                        </dte:Emisor>
                        <dte:Receptor IDReceptor="'.$receptorID.'" NombreReceptor="'.$receptorNombre.'" CorreoReceptor="'.$emisor['correo'].'"'.$tipoEspecial.'>
                            <dte:DireccionReceptor>
                                <dte:Direccion>'.$venta->bodega->direccion.'</dte:Direccion>
                                <dte:CodigoPostal>0</dte:CodigoPostal>
                                <dte:Municipio>'.$venta->bodega->municipio->municipio.'</dte:Municipio>
                                <dte:Departamento>'.$venta->bodega->departamento->departamento.'</dte:Departamento>
                                <dte:Pais>'.config('services.fel.pais').'</dte:Pais>
                            </dte:DireccionReceptor>
                        </dte:Receptor>
                        <dte:Frases>
                            <dte:Frase CodigoEscenario="1" TipoFrase="1"/>
                        </dte:Frases>
                        <dte:Items>'.$xmlItems.'</dte:Items>
                        <dte:Totales>
                            <dte:TotalImpuestos>
                                <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="'.$totalMontoImpuesto.'"/>
                            </dte:TotalImpuestos>
                            <dte:GranTotal>'.$granTotal.'</dte:GranTotal>
                        </dte:Totales>
                        '.$xmlComplementos.'
                    </dte:DatosEmision>
                </dte:DTE>
                <dte:Adenda>
                    <CondicionesPago>'.$venta->pagos->first()->tipoPago->tipo_pago.'</CondicionesPago>
                    <SolicitadoPor>'.User::withTrashed()->find($venta->asesor_id)->name.'</SolicitadoPor>
                    <Vendedor>'.User::withTrashed()->find($venta->asesor_id)->id.'</Vendedor>
                    <Envio>0</Envio>
                    <OrdenCompra>'.$venta->id.'</OrdenCompra>
                    <TelReceptor>'.$cliente->telefono.'</TelReceptor>
                    <PBX>'.env('PBX').'</PBX>
                </dte:Adenda>
            </dte:SAT>
        </dte:GTDocumento>';

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xmlPayload,
            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.$emisor['usuario_api'],
                'LlaveApi: '.$emisor['llave_api'],
                'UsuarioFirma: '.$emisor['usuario_firma'],
                'LlaveFirma: '.$emisor['llave_firma'],
                'Identificador:'.config('services.fel.identificador').'FACTURA-VENTA-'.$venta->id,
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    public static function anularFacturaVenta($venta, $motivo)
    {
        $cliente = User::withTrashed()->find($venta->cliente_id);
        $receptorID = $venta->facturar_cf ? 'CF' : $cliente->nit;
        $xml = '<dte:GTAnulacionDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.1.0" xmlns:n1="http://www.altova.com/samplexml/other-namespace" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.1.0 C:\Users\User\Desktop\FEL\Esquemas\GT_AnulacionDocumento-0.1.0.xsd">
            <dte:SAT>
                <dte:AnulacionDTE ID="DatosCertificados">
                <dte:DatosGenerales FechaEmisionDocumentoAnular="'.Carbon::parse($venta->factura->fel_fecha)->format('Y-m-d\TH:i:s').'" FechaHoraAnulacion="'.now()->format('Y-m-d\TH:i:s').'" ID="DatosAnulacion" IDReceptor="'.$receptorID.'" MotivoAnulacion="'.$motivo.'" NITEmisor="'.config('services.fel.nit').'" NumeroDocumentoAAnular="'.$venta->factura->fel_uuid.'"></dte:DatosGenerales>
                </dte:AnulacionDTE>
            </dte:SAT>
            </dte:GTAnulacionDocumento>';

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'UsuarioFirma: '.config('services.fel.usuario_firma'),
                'Identificador:'.config('services.fel.identificador').'ANULACION-VENTA-'.$venta->id,
                'LlaveFirma: '.config('services.fel.llave_firma'),
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        $responseData = json_decode($response, true);

        return $responseData;
    }

    public static function devolverFacturaVenta($venta, $motivo)
    {

        $cliente = User::withTrashed()->find($venta->cliente_id);
        $receptorID = $venta->facturar_cf ? 'CF' : $cliente->nit;
        $receptorNombre = $venta->facturar_cf ? $cliente->name : $cliente->razon_social;

        $asesor = User::withTrashed()->find($venta->asesor_id);

        $totalMontoImpuesto = 0;
        $granTotal = 0;
        $xmlItems = '';
        $correlativo = 1;
        foreach ($venta->detalles as $item) {
            if ($item->devuelto > 0) {
                $producto = Producto::withTrashed()->find($item->producto_id);
                $descripcion = $producto->codigo.' - '.$producto->descripcion.' - '.$producto->marca->marca;
                $precioUnitario = $item->precio;
                $precioTotal = round($item->devuelto * $precioUnitario, 2);
                $montoGravable = round($precioTotal / 1.12, 2);
                $montoImpuesto = round($montoGravable * 0.12, 2);
                $totalMontoImpuesto += round($montoImpuesto, 2);
                $granTotal += round($item->devuelto * $item->precio, 2);
                $xmlItems .= '<dte:Item BienOServicio="B" NumeroLinea="'.$correlativo.'">
                <dte:Cantidad>'.$item->devuelto.'</dte:Cantidad>
                <dte:UnidadMedida>UNI</dte:UnidadMedida>
                <dte:Descripcion>'.$descripcion.'</dte:Descripcion>
                <dte:PrecioUnitario>'.$precioUnitario.'</dte:PrecioUnitario>
                <dte:Precio>'.$precioTotal.'</dte:Precio>
                <dte:Descuento>0.00</dte:Descuento>
                <dte:Impuestos>
                <dte:Impuesto>
                <dte:NombreCorto>IVA</dte:NombreCorto>
                <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
                <dte:MontoGravable>'.$montoGravable.'</dte:MontoGravable>
                <dte:MontoImpuesto>'.$montoImpuesto.'</dte:MontoImpuesto>
                </dte:Impuesto>
                </dte:Impuestos>
                <dte:Total>'.$precioTotal.'</dte:Total>
                </dte:Item>';
                $correlativo++;
            }
        }

        $curl = curl_init();
        $tipoEspecial = ($cliente->nit || $venta->facturar_cf) ? '' : ' TipoEspecial="CUI"';

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="UTF-8"?>
            <dte:GTDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.2.0">
              <dte:SAT ClaseDocumento="dte">
                <dte:DTE ID="DatosCertificados">
                  <dte:DatosEmision ID="DatosEmision">
                        <dte:DatosGenerales CodigoMoneda="GTQ" FechaHoraEmision="'.now()->format('Y-m-d\TH:i:s').'-06:00" Tipo="NCRE"></dte:DatosGenerales>
                        <dte:Emisor AfiliacionIVA="GEN" CodigoEstablecimiento="2" NITEmisor="'.config('services.fel.nit').'" NombreComercial="'.config('services.fel.nombre_comercial').'" NombreEmisor="'.config('services.fel.razon_social').'">
                        <dte:DireccionEmisor>
                        <dte:Direccion>'.config('services.fel.direccion').'</dte:Direccion>
                        <dte:CodigoPostal>'.config('services.fel.codigo_postal').'</dte:CodigoPostal>
                        <dte:Municipio>'.config('services.fel.municipio').'</dte:Municipio>
                        <dte:Departamento>'.config('services.fel.departamento').'</dte:Departamento>
                        <dte:Pais>'.config('services.fel.pais').'</dte:Pais>
                        </dte:DireccionEmisor>
                        </dte:Emisor>
                        <dte:Receptor IDReceptor="'.$receptorID.'" NombreReceptor="'.$receptorNombre.'" CorreoReceptor="'.config('services.fel.correo').'"'.$tipoEspecial.'>
                        <dte:DireccionReceptor>
                            <dte:Direccion>'.$venta->bodega->direccion.'</dte:Direccion>
                            <dte:CodigoPostal>0</dte:CodigoPostal>
                            <dte:Municipio>'.$venta->bodega->municipio->municipio.'</dte:Municipio>
                            <dte:Departamento>'.$venta->bodega->departamento->departamento.'</dte:Departamento>
                            <dte:Pais>'.config('services.fel.pais').'</dte:Pais>
                            </dte:DireccionReceptor>
                        </dte:Receptor>
                        <dte:Frases>
                        <dte:Frase CodigoEscenario="1" TipoFrase="1"></dte:Frase>
                        </dte:Frases>
                        <dte:Items>'.$xmlItems.'</dte:Items>
                        <dte:Totales>
                            <dte:TotalImpuestos>
                            <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="'.$totalMontoImpuesto.'"></dte:TotalImpuesto>
                            </dte:TotalImpuestos>
                            <dte:GranTotal>'.$granTotal.'</dte:GranTotal>
                        </dte:Totales>
                        <dte:Complementos>
                        <dte:Complemento IDComplemento="TEXT" NombreComplemento="TEXT" URIComplemento="TEXT">
                            <cno:ReferenciasNota xmlns:cno="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0" FechaEmisionDocumentoOrigen="'.Carbon::parse($venta->factura->fel_fecha)->format('Y-m-d').'" MotivoAjuste="'.$motivo.'" NumeroAutorizacionDocumentoOrigen="'.$venta->factura->fel_uuid.'" NumeroDocumentoOrigen="'.$venta->factura->fel_serie.'" SerieDocumentoOrigen="'.$venta->factura->fel_numero.'" Version="0.0" xsi:schemaLocation="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0 C:\Users\User\Desktop\FEL\Esquemas\GT_Complemento_Referencia_Nota-0.1.0.xsd"></cno:ReferenciasNota>
                        </dte:Complemento>
                        </dte:Complementos>
                    </dte:DatosEmision>
                </dte:DTE>
                <dte:Adenda>
                    <CondicionesPago>'.$venta->pagos->first()->tipoPago->tipo_pago.'</CondicionesPago>
                    <SolicitadoPor>'.$asesor->name.'</SolicitadoPor>
                    <Vendedor>'.$asesor->id.'</Vendedor>
                    <Envio>0</Envio>
                    <OrdenCompra>'.$venta->id.'</OrdenCompra>
                    <TelReceptor>'.$cliente->telefono.'</TelReceptor>
                    <PBX>'.env('PBX').'</PBX>
                </dte:Adenda>
              </dte:SAT>
            </dte:GTDocumento>',

            CURLOPT_HTTPHEADER => [
                'UsuarioApi: '.config('services.fel.usuario_api'),
                'LlaveApi: '.config('services.fel.llave_api'),
                'UsuarioFirma: '.config('services.fel.usuario_firma'),
                'Identificador:'.config('services.fel.identificador').'DEVOLUCION-VENTA-'.$venta->id,
                'LlaveFirma: '.config('services.fel.llave_firma'),
                'Content-Type: application/xml',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, true);

        return $responseData;
    }
}
