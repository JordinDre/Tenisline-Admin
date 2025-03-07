import { Link, usePage } from '@inertiajs/react';

const Resumen = ({
    subtotal,
    faltaParaEnvioGratis,
    ruta,
    botonTexto,
    processing,
}) => {
    const envioGratis = usePage().props.auth.envio_gratis;
    const envioValor = usePage().props.auth.envio;
    const porcentajeEnvioGratis =
        faltaParaEnvioGratis > 0
            ? ((envioGratis - faltaParaEnvioGratis) / envioGratis) * 100
            : 100;

    return (
        <div className="rounded-lg bg-gray-50 p-6 shadow-lg">
            <h2 className="mb-4 text-xl font-bold text-gray-800">
                Resumen del pedido
            </h2>

            {faltaParaEnvioGratis > 0 && (
                <div className="mb-4">
                    <span className="text-gray-600">
                        Faltan Q{faltaParaEnvioGratis.toFixed(2)} para el envío
                        gratis
                    </span>
                    <div className="mt-2">
                        <progress
                            className={`progress w-full ${
                                porcentajeEnvioGratis < 100
                                    ? 'progress-warning'
                                    : 'progress-success'
                            }`}
                            value={porcentajeEnvioGratis}
                            max="100"
                        ></progress>
                    </div>
                </div>
            )}

            <div className="flex justify-between border-b border-gray-200 py-2">
                <span className="text-gray-600">Subtotal</span>
                <span className="font-semibold text-gray-800">
                    Q{subtotal.toFixed(2)}
                </span>
            </div>
            <div className="flex justify-between border-b border-gray-200 py-2">
                <span className="text-gray-600">Envío</span>
                <span className="font-semibold text-gray-800">
                    Q{faltaParaEnvioGratis > 0 ? envioValor : '0.00'}
                </span>
            </div>
            <div className="mt-4 flex justify-between border-t border-gray-300 py-2 pt-4">
                <span className="text-lg font-bold text-gray-800">Total</span>
                <span className="text-lg font-bold text-gray-800">
                    Q
                    {(
                        subtotal + (faltaParaEnvioGratis > 0 ? envioValor : 0)
                    ).toFixed(2)}
                </span>
            </div>

            <Link
                href={ruta}
                disabled={processing}
                className="btn btn-neutral mt-2 w-full rounded-lg py-2 font-semibold text-white"
            >
                {botonTexto}
            </Link>

            <Link
                href={route('catalogo')}
                disabled={processing}
                className="btn-neutral-content btn mt-2 w-full rounded-lg py-2 font-semibold"
            >
                Buscar Más Productos
            </Link>
        </div>
    );
};

export default Resumen;
