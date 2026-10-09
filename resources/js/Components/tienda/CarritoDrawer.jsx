import ImagenProducto from '@/Components/tienda/ImagenProducto';
import { useCarrito } from '@/Contexts/CarritoContext';
import { capitalizar, enlaceWhatsApp, precioFinal, quetzales } from '@/lib/tienda';
import { usePage } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function CarritoDrawer() {
    const { items, abierto, setAbierto, cambiarCantidad, quitar, vaciar, cantidad } = useCarrito();
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const [sucursal, setSucursal] = useState(0);
    const total = items.reduce((s, i) => s + precioFinal(i) * i.cantidad, 0);

    useEffect(() => {
        document.body.style.overflow = abierto ? 'hidden' : '';
        return () => (document.body.style.overflow = '');
    }, [abierto]);

    const enviar = () => {
        const s = sucursales[sucursal];
        if (!s || !items.length) return;
        window.open(enlaceWhatsApp(s.telefono, items, s.nombre), '_blank', 'noopener');
    };

    return (
        <div className={`fixed inset-0 z-50 ${abierto ? '' : 'pointer-events-none'}`} aria-hidden={!abierto}>
            <div
                onClick={() => setAbierto(false)}
                className={`absolute inset-0 bg-black/40 transition-opacity duration-300 ${abierto ? 'opacity-100' : 'opacity-0'}`}
            />
            <aside
                className={`absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl transition-transform duration-300 ${abierto ? 'translate-x-0' : 'translate-x-full'}`}
            >
                <div className="flex items-center justify-between border-b px-5 py-4">
                    <h2 className="font-display text-xl uppercase">
                        Tu carrito <span className="text-neutral-400">({cantidad})</span>
                    </h2>
                    <button onClick={() => setAbierto(false)} className="rounded-full p-2 hover:bg-neutral-100" aria-label="Cerrar carrito">
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {items.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 px-8 text-center">
                        <ShoppingBag className="h-14 w-14 text-neutral-300" />
                        <p className="font-semibold">Tu carrito está vacío</p>
                        <p className="text-sm text-neutral-500">
                            Agrega los tenis que te gusten y envíanos tu pedido por WhatsApp.
                        </p>
                        <button onClick={() => setAbierto(false)} className="mt-2 rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">
                            Seguir viendo
                        </button>
                    </div>
                ) : (
                    <>
                        <ul className="flex-1 divide-y overflow-y-auto px-5">
                            {items.map((i) => (
                                <li key={i.id} className="flex gap-4 py-4">
                                    <ImagenProducto
                                        src={i.imagen}
                                        alt={i.descripcion}
                                        className="h-24 w-20 shrink-0 rounded-xl"
                                    />
                                    <div className="flex min-w-0 flex-1 flex-col">
                                        <p className="text-xs font-bold uppercase tracking-widest text-neutral-500">{i.marca}</p>
                                        <p className="truncate font-semibold">{capitalizar(i.descripcion)}</p>
                                        <p className="text-sm text-neutral-500">
                                            Talla {i.talla}
                                            {i.color ? ` · ${capitalizar(i.color)}` : ''}
                                        </p>
                                        <div className="mt-auto flex items-center justify-between pt-2">
                                            <div className="flex items-center rounded-full border">
                                                <button onClick={() => cambiarCantidad(i.id, i.cantidad - 1)} className="p-2" aria-label="Quitar uno">
                                                    <Minus className="h-3.5 w-3.5" />
                                                </button>
                                                <span className="w-6 text-center text-sm font-semibold">{i.cantidad}</span>
                                                <button onClick={() => cambiarCantidad(i.id, i.cantidad + 1)} className="p-2" aria-label="Agregar uno">
                                                    <Plus className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                            <span className="font-bold">{quetzales(precioFinal(i) * i.cantidad)}</span>
                                        </div>
                                    </div>
                                    <button onClick={() => quitar(i.id)} className="self-start p-1 text-neutral-400 hover:text-red-500" aria-label="Eliminar">
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <div className="space-y-4 border-t bg-neutral-50 px-5 py-5">
                            <div>
                                <p className="mb-2 text-sm font-semibold">¿A qué tienda le escribimos?</p>
                                <div className="grid grid-cols-3 gap-2">
                                    {sucursales.map((s, n) => (
                                        <button
                                            key={s.nombre}
                                            onClick={() => setSucursal(n)}
                                            className={`rounded-xl border px-2 py-2 text-sm font-semibold transition ${sucursal === n ? 'border-ink bg-ink text-white' : 'bg-white hover:border-neutral-400'}`}
                                        >
                                            {s.nombre}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-neutral-600">Total aproximado</span>
                                <span className="text-xl font-bold">{quetzales(total)}</span>
                            </div>
                            <button
                                onClick={enviar}
                                className="flex w-full items-center justify-center gap-2 rounded-full bg-[#25D366] py-4 font-bold text-white transition hover:brightness-95"
                            >
                                <svg viewBox="0 0 24 24" className="h-5 w-5 fill-current" aria-hidden="true">
                                    <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.49-.23-.36A9.43 9.43 0 0 1 2.6 12C2.6 6.8 6.84 2.56 12.05 2.56c2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.68c0 5.2-4.24 9.44-9.44 9.44zm8.03-17.47A11.3 11.3 0 0 0 12.04.7C5.79.7.7 5.78.7 12.04c0 2 .52 3.95 1.52 5.67L.6 23.3l5.73-1.5a11.3 11.3 0 0 0 5.7 1.45h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.31-8.01z" />
                                </svg>
                                Enviar pedido por WhatsApp
                            </button>
                            <p className="text-center text-xs text-neutral-500">
                                Sin pagos en línea: confirmamos disponibilidad y forma de pago por WhatsApp.
                            </p>
                            <button onClick={vaciar} className="w-full text-center text-xs font-semibold text-neutral-500 underline">
                                Vaciar carrito
                            </button>
                        </div>
                    </>
                )}
            </aside>
        </div>
    );
}
