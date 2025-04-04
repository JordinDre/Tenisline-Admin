import { Link, usePage } from '@inertiajs/react';
import { Drawer } from 'flowbite-react';
import { useState } from 'react';
import { FaShoppingCart } from 'react-icons/fa';
import { MdOutlineRemoveShoppingCart } from 'react-icons/md';

export default function CarritoDrawer() {
    const [isOpen, setIsOpen] = useState(false);

    const handleClose = () => setIsOpen(false);

    const user = usePage().props.auth.user;
    const carrito = usePage().props.auth.carrito;
    const url = usePage().props.auth.url;
    const envioGratis = usePage().props.auth.envio_gratis;
    const envioValor = usePage().props.auth.envio;

    const subtotal = carrito.reduce(
        (total, item) => total + item.precio * item.cantidad,
        0,
    );

    const faltaParaEnvioGratis = envioGratis - subtotal;
    const porcentajeEnvioGratis =
        faltaParaEnvioGratis > 0
            ? ((envioGratis - faltaParaEnvioGratis) / envioGratis) * 100
            : 100;

    const progresoColor =
        faltaParaEnvioGratis > 0 ? 'bg-yellow-400' : 'bg-green-600';

    // Calcular el total, agregando el costo de envío si es necesario
    const envio = faltaParaEnvioGratis > 0 ? envioValor : 0;
    const total = subtotal + envio;

    return (
        <>
            <button
                onClick={() => setIsOpen(true)}
                className="btn btn-circle btn-ghost"
            >
                <div className="indicator">
                    <FaShoppingCart className="h-5 w-5 text-zinc-700" />
                    <span className="badge indicator-item badge-sm bg-red-500 text-white">
                        {user
                            ? carrito.reduce(
                                (total, item) => total + item.cantidad,
                                0,
                            )
                            : 0}
                    </span>
                </div>
            </button>

            <Drawer
                open={isOpen}
                onClose={handleClose}
                position="right"
                className="max-w-lg"
            >
                <Drawer.Header title="Tu carrito" />
                <Drawer.Items>
                    <div className="flex min-h-[50vh] flex-col overflow-y-auto p-4">
                        {carrito && carrito.length > 0 ? (
                            <>
                                {/* Mostrar el total arriba */}
                                <div className="mb-6">
                                    <p className="text-xl font-semibold text-zinc-800">
                                        Total: Q{total.toFixed(2)}
                                    </p>
                                    <div className="mt-2">
                                        {faltaParaEnvioGratis > 0 ? (
                                            <p className="text-sm text-zinc-800">
                                                Faltan Q
                                                {faltaParaEnvioGratis.toFixed(
                                                    2,
                                                )}{' '}
                                                para el envío gratis
                                            </p>
                                        ) : (
                                            <p className="text-sm text-zinc-800">
                                                ¡Tienes envío gratis!
                                            </p>
                                        )}
                                        <div className="mt-2 h-2 w-full rounded-full bg-zinc-200">
                                            <div
                                                className={`h-2 rounded-full ${progresoColor}`}
                                                style={{
                                                    width: `${porcentajeEnvioGratis}%`,
                                                }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <ul className="space-y-4">
                                    {carrito.map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex items-center justify-between gap-3 border-b pb-3"
                                        >
                                            <img
                                                src={
                                                    url +
                                                    item.producto.imagenes[0]
                                                }
                                                alt={item.producto.descripcion}
                                                className="h-12 w-12 rounded-lg object-cover"
                                            />
                                            <div className="flex-1">
                                                <p className="text-sm font-semibold text-zinc-800">
                                                    {item.producto.descripcion},{' '}
                                                    {item.producto.marca.marca}
                                                </p>
                                                <p className="mt-1 text-xs text-zinc-500">
                                                    Cantidad: {item.cantidad}
                                                </p>
                                            </div>
                                            <p className="text-sm font-semibold text-zinc-800">
                                                Q
                                                {(
                                                    item.precio * item.cantidad
                                                ).toFixed(2)}
                                            </p>
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-6">
                                    <Link
                                        href={route('carrito')}
                                        className="btn w-full bg-green-600 font-semibold text-white hover:bg-green-800"
                                    >
                                        Ver carrito
                                    </Link>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-col items-center text-center text-zinc-500">
                                <MdOutlineRemoveShoppingCart className="h-12 w-12 text-zinc-300" />
                                <p className="mt-2 text-sm font-semibold">
                                    Tu carrito está vacío
                                </p>
                                <p className="mt-1 text-xs text-zinc-400">
                                    Agrega productos para comenzar tu compra.
                                </p>
                            </div>
                        )}
                    </div>
                </Drawer.Items>
            </Drawer>
        </>
    );
}
