import ImagenProducto from '@/Components/tienda/ImagenProducto';
import MuestraColor from '@/Components/tienda/MuestraColor';
import { useCarrito } from '@/Contexts/CarritoContext';
import { capitalizar, descuento, quetzales } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Check, ShoppingBag } from 'lucide-react';
import { useState } from 'react';

export default function ProductoCard({ producto, indice = 0 }) {
    const { agregar, items } = useCarrito();
    const [recien, setRecien] = useState(false);
    const pct = descuento(producto);
    const tallas = producto.tallas ?? [];
    // Cada par es único: si el modelo tiene una sola talla se agrega directo; si tiene varias, hay que elegir cuál par
    const unico = tallas.length === 1;
    const enCarrito = items.some((i) => i.id === producto.id);
    const ruta = route('producto', producto.slug);

    const alAgregar = () => {
        agregar({
            id: producto.id, slug: producto.slug, codigo: producto.codigo, talla: tallas[0],
            precio: producto.precio, precio_oferta: producto.precio_oferta,
            descripcion: producto.descripcion, marca: producto.marca, color: producto.color, imagen: producto.imagen,
        });
        setRecien(true);
        setTimeout(() => setRecien(false), 1800);
    };

    const nombre = `${producto.marca ?? ''} ${capitalizar(producto.descripcion)}${producto.color ? ` ${capitalizar(producto.color)}` : ''}`.trim();
    const pildora = 'absolute bottom-3 z-20 flex h-10 items-center justify-center gap-2 rounded-full text-sm font-semibold text-white transition-[opacity,transform,background-color] duration-500 md:left-3 md:right-3 md:translate-y-3 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100 md:focus-visible:translate-y-0 md:focus-visible:opacity-100';

    return (
        <div className="group relative flex h-full animate-fade-up flex-col" style={{ animationDelay: `${Math.min(indice, 11) * 80}ms` }}>
            <div className="relative aspect-[4/5] overflow-hidden rounded-2xl bg-neutral-100">
                <Link href={ruta} aria-label={nombre} className="absolute inset-0">
                    <ImagenProducto src={producto.imagen} alt={nombre} className="h-full w-full transition-transform duration-700 ease-[cubic-bezier(.22,1,.36,1)] group-hover:scale-[1.06]" />
                </Link>

                <div className="pointer-events-none absolute inset-x-3 top-3 z-10 flex items-start justify-between gap-2">
                    {pct > 0 ? <span className="rounded-full bg-brand px-2.5 py-1 text-xs font-bold leading-none text-white">-{pct}%</span> : <span />}
                    {producto.genero && (
                        <span className="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase leading-none tracking-wide text-neutral-700 backdrop-blur">
                            {capitalizar(producto.genero)}
                        </span>
                    )}
                </div>

                {/* Agregar: botón redondo siempre visible en celular; en computadora, barra al pasar el mouse */}
                {unico ? (
                    <button type="button" onClick={alAgregar} disabled={enCarrito && !recien}
                        aria-label={enCarrito && !recien ? 'Ya está en tu carrito' : 'Agregar al carrito'}
                        className={cn(pildora, 'right-3 w-10 md:w-auto', enCarrito || recien ? 'bg-emerald-600' : 'bg-ink hover:bg-brand')}>
                        {enCarrito || recien ? <Check className="h-4 w-4" /> : <ShoppingBag className="h-4 w-4" />}
                        <span className="hidden md:inline">{enCarrito || recien ? 'En tu carrito' : 'Agregar al carrito'}</span>
                    </button>
                ) : (
                    <Link href={ruta} className={cn(pildora, 'hidden bg-ink hover:bg-brand md:flex')}>Elegir talla</Link>
                )}
            </div>

            <Link href={ruta} className="flex flex-1 flex-col pt-3">
                <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500">{producto.marca}</p>
                <h3 className="mt-1 line-clamp-2 min-h-[2.5rem] text-[15px] font-semibold leading-5 text-neutral-900 transition-colors group-hover:text-brand">
                    {capitalizar(producto.descripcion)}
                </h3>
                <p className="mt-1 flex h-5 items-center gap-1.5 truncate text-sm font-medium text-neutral-700">
                    {producto.color ? <><MuestraColor color={producto.color} />{capitalizar(producto.color)}</> : ' '}
                </p>
                <div className="mt-auto flex items-baseline gap-2 pt-2">
                    <span className={`text-base font-bold ${pct > 0 ? 'text-brand' : 'text-neutral-900'}`}>
                        {quetzales(producto.precio_oferta || producto.precio)}
                    </span>
                    {pct > 0 && <span className="text-sm text-neutral-400 line-through">{quetzales(producto.precio)}</span>}
                </div>
                <p className="mt-1 truncate text-xs text-neutral-500">
                    {tallas.length ? (unico ? `Talla ${tallas[0]}` : `Tallas ${tallas.slice(0, 6).join(' · ')}${tallas.length > 6 ? ' …' : ''}`) : ' '}
                </p>
            </Link>
        </div>
    );
}
