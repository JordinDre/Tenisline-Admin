import ImagenProducto from '@/Components/tienda/ImagenProducto';
import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useCarrito } from '@/Contexts/CarritoContext';
import { capitalizar, enlaceWhatsApp, precioFinal, quetzales } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { useState } from 'react';

export const IconoWhatsApp = ({ className }) => (
    <svg viewBox="0 0 24 24" className={cn('fill-current', className)} aria-hidden="true">
        <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.49-.23-.36A9.43 9.43 0 0 1 2.6 12C2.6 6.8 6.84 2.56 12.05 2.56c2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.68c0 5.2-4.24 9.44-9.44 9.44zm8.03-17.47A11.3 11.3 0 0 0 12.04.7C5.79.7.7 5.78.7 12.04c0 2 .52 3.95 1.52 5.67L.6 23.3l5.73-1.5a11.3 11.3 0 0 0 5.7 1.45h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.31-8.01z" />
    </svg>
);

export default function CarritoDrawer() {
    const { items, abierto, setAbierto, cambiarCantidad, quitar, vaciar, cantidad } = useCarrito();
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const [sucursal, setSucursal] = useState(0);
    const total = items.reduce((s, i) => s + precioFinal(i) * i.cantidad, 0);

    const enviar = () => {
        const s = sucursales[sucursal];
        if (s && items.length) window.open(enlaceWhatsApp(s.telefono, items, s.nombre), '_blank', 'noopener');
    };

    return (
        <Sheet open={abierto} onOpenChange={setAbierto}>
            <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-md">
                <SheetHeader className="border-b px-6 py-5 text-left">
                    <SheetTitle className="font-display text-xl uppercase">
                        Tu carrito <span className="text-neutral-400">({cantidad})</span>
                    </SheetTitle>
                    <SheetDescription className="sr-only">Tenis que agregaste para pedir por WhatsApp</SheetDescription>
                </SheetHeader>

                {items.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center px-10 text-center">
                        <span className="flex h-20 w-20 items-center justify-center rounded-full bg-brand-light">
                            <ShoppingBag className="h-9 w-9 text-brand" />
                        </span>
                        <p className="mt-5 text-lg font-bold">Tu carrito está vacío</p>
                        <p className="mt-1 text-sm text-neutral-500">Agrega los tenis que te gusten y envíanos tu pedido por WhatsApp.</p>
                        <Button asChild className="mt-6 h-12 rounded-full bg-ink px-8 hover:bg-neutral-800" onClick={() => setAbierto(false)}>
                            <Link href="/catalogo">Ver catálogo</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <ul className="flex-1 divide-y overflow-y-auto px-6">
                            {items.map((i) => (
                                <li key={i.id} className="flex animate-fade-up gap-4 py-5">
                                    <Link href={route('producto', i.slug)} onClick={() => setAbierto(false)} className="shrink-0">
                                        <ImagenProducto src={i.imagen} alt={i.descripcion} sizes="80px" ancho={240} className="h-24 w-20 rounded-xl" />
                                    </Link>
                                    <div className="flex min-w-0 flex-1 flex-col">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500">{i.marca}</p>
                                                <p className="truncate font-semibold leading-5">{capitalizar(i.descripcion)}</p>
                                                <p className="text-sm text-neutral-500">Talla {i.talla}{i.color ? ` · ${capitalizar(i.color)}` : ''}</p>
                                            </div>
                                            <button onClick={() => quitar(i.id)} className="-mr-1 rounded-full p-1.5 text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-500" aria-label="Eliminar">
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div className="mt-auto flex items-center justify-between pt-3">
                                            <div className="flex h-9 items-center rounded-full border">
                                                <button onClick={() => cambiarCantidad(i.id, i.cantidad - 1)} className="flex h-9 w-9 items-center justify-center rounded-full hover:bg-neutral-100" aria-label="Quitar uno"><Minus className="h-3.5 w-3.5" /></button>
                                                <span className="w-6 text-center text-sm font-semibold tabular-nums">{i.cantidad}</span>
                                                <button onClick={() => cambiarCantidad(i.id, i.cantidad + 1)} className="flex h-9 w-9 items-center justify-center rounded-full hover:bg-neutral-100" aria-label="Agregar uno"><Plus className="h-3.5 w-3.5" /></button>
                                            </div>
                                            <span className="font-bold tabular-nums">{quetzales(precioFinal(i) * i.cantidad)}</span>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="space-y-4 border-t bg-neutral-50 px-6 py-5">
                            <div>
                                <p className="mb-2 text-sm font-semibold">¿A qué tienda le escribimos?</p>
                                <div className="grid grid-cols-3 gap-2">
                                    {sucursales.map((s, n) => (
                                        <button key={s.nombre} onClick={() => setSucursal(n)}
                                            className={cn('h-10 rounded-xl border text-sm font-semibold transition-colors', sucursal === n ? 'border-ink bg-ink text-white' : 'border-neutral-200 bg-white hover:border-neutral-400')}>
                                            {s.nombre}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-neutral-600">Total aproximado</span>
                                <span className="text-xl font-bold tabular-nums">{quetzales(total)}</span>
                            </div>
                            <Button onClick={enviar} className="h-14 w-full rounded-full bg-[#25D366] text-base font-bold text-white shadow-lg shadow-[#25D366]/25 hover:bg-[#1fbd59]">
                                <IconoWhatsApp className="h-5 w-5" /> Enviar pedido por WhatsApp
                            </Button>
                            <p className="text-center text-xs text-neutral-500">Sin pagos en línea: confirmamos disponibilidad y forma de pago por WhatsApp.</p>
                            <button onClick={vaciar} className="w-full text-center text-xs font-semibold text-neutral-500 underline underline-offset-4 hover:text-ink">Vaciar carrito</button>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
