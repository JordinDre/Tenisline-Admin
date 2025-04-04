import Resumen from '@/Components/Resumen';
import Layout from '@/Layouts/Layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FaMinus, FaPlus, FaTrashAlt } from 'react-icons/fa';
import { MdOutlineRemoveShoppingCart } from 'react-icons/md';

export default function Carrito() {
    const carrito = usePage().props.auth.carrito;
    const url = usePage().props.auth.url;
    const { post, processing } = useForm();
    const envioGratis = usePage().props.auth.envio_gratis;

    function sumar(id) {
        post(route('sumar.carrito', id));
    }

    function restar(id) {
        post(route('restar.carrito', id));
    }

    function eliminar(id) {
        post(route('eliminar.carrito', id));
    }

    const subtotal = carrito.reduce(
        (total, item) => total + item.precio * item.cantidad,
        0,
    );

    const faltaParaEnvioGratis = envioGratis - subtotal;

    return (
        <Layout>
            <Head>
                <title>Carrito</title>
                <meta name="carrito" content="Carrito - Tienda en línea" />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>

            <div className="mx-auto mt-10 max-w-screen-xl p-4">
                <h1 className="mb-6 text-2xl font-bold text-zinc-800">
                    Tu carrito
                </h1>
                {carrito.length > 0 ? (
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div className="col-span-1 max-h-[60vh] space-y-4 overflow-y-auto rounded-lg bg-white p-4 shadow-md lg:col-span-2">
                            {carrito.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-col items-center justify-between border-b border-zinc-200 pb-4 sm:flex-row"
                                >
                                    <div className="flex items-center gap-4">
                                        <img
                                            src={
                                                url + item.producto.imagenes[0]
                                            }
                                            alt={item.producto.descripcion}
                                            className="h-20 w-20 rounded-lg object-cover"
                                        />
                                        <div>
                                            <p className="text-lg font-semibold text-zinc-800">
                                                {item.producto.descripcion}
                                            </p>
                                            <p className="text-sm text-zinc-500">
                                                Código: {item.producto.id}-
                                                {item.producto.codigo}
                                            </p>
                                            <p className="text-sm font-bold text-zinc-700">
                                                Precio: Q{item.precio}{' '}
                                                &nbsp;&nbsp;&nbsp;SubTotal: Q
                                                {(
                                                    item.precio * item.cantidad
                                                ).toFixed(2)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-4 flex items-center gap-4 sm:mt-0">
                                        <button
                                            onClick={() =>
                                                restar(item.producto_id)
                                            }
                                            className="btn btn-outline btn-sm"
                                            disabled={processing}
                                        >
                                            <FaMinus />
                                        </button>
                                        <span className="text-lg font-semibold text-zinc-800">
                                            {item.cantidad}
                                        </span>
                                        <button
                                            onClick={() =>
                                                sumar(item.producto_id)
                                            }
                                            className="btn btn-outline btn-sm"
                                            disabled={processing}
                                        >
                                            <FaPlus />
                                        </button>
                                        <button
                                            onClick={() =>
                                                eliminar(item.producto_id)
                                            }
                                            className="btn btn-error btn-sm text-white"
                                            disabled={processing}
                                        >
                                            <FaTrashAlt />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Resumen
                            subtotal={subtotal}
                            faltaParaEnvioGratis={faltaParaEnvioGratis}
                            ruta={route('crear.orden')}
                            botonTexto="Continuar con la Compra"
                            processing={processing}
                        />
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center">
                        <MdOutlineRemoveShoppingCart className="text-9xl text-zinc-400" />
                        <p className="mt-4 text-xl font-semibold text-zinc-600">
                            Tu carrito está vacío. ¡Agrega productos para
                            continuar!
                        </p>
                    </div>
                )}
            </div>
        </Layout>
    );
}
