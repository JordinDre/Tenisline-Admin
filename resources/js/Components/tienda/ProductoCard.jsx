import ImagenProducto from '@/Components/tienda/ImagenProducto';
import { capitalizar, descuento, quetzales } from '@/lib/tienda';
import { Link } from '@inertiajs/react';

export default function ProductoCard({ producto, indice = 0 }) {
    const pct = descuento(producto);
    const tallas = producto.tallas ?? [];

    return (
        <Link
            href={route('producto', producto.slug)}
            className="group flex animate-fade-up flex-col"
            style={{ animationDelay: `${Math.min(indice, 12) * 40}ms` }}
        >
            <div className="relative aspect-[4/5] overflow-hidden rounded-2xl bg-neutral-100">
                <ImagenProducto
                    src={producto.imagen}
                    alt={producto.descripcion}
                    className="h-full w-full transition-transform duration-500 group-hover:scale-105"
                />
                {pct > 0 && (
                    <span className="absolute left-3 top-3 rounded-full bg-brand px-2.5 py-1 text-xs font-bold text-white">
                        -{pct}%
                    </span>
                )}
                {producto.genero && (
                    <span className="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-700 backdrop-blur">
                        {capitalizar(producto.genero)}
                    </span>
                )}
                <span className="absolute inset-x-3 bottom-3 translate-y-2 rounded-full bg-ink py-2 text-center text-sm font-semibold text-white opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                    Ver tallas
                </span>
            </div>

            <div className="mt-3 flex flex-1 flex-col">
                <p className="text-xs font-bold uppercase tracking-widest text-neutral-500">
                    {producto.marca}
                </p>
                <h3 className="mt-0.5 line-clamp-2 font-semibold leading-snug text-neutral-900">
                    {capitalizar(producto.descripcion)}
                </h3>
                {producto.color && (
                    <p className="text-sm text-neutral-500">{capitalizar(producto.color)}</p>
                )}
                <div className="mt-2 flex items-baseline gap-2">
                    <span className={`font-bold ${pct > 0 ? 'text-brand' : 'text-neutral-900'}`}>
                        {quetzales(producto.precio_oferta || producto.precio)}
                    </span>
                    {pct > 0 && (
                        <span className="text-sm text-neutral-400 line-through">
                            {quetzales(producto.precio)}
                        </span>
                    )}
                </div>
                {tallas.length > 0 && (
                    <p className="mt-1 truncate text-xs text-neutral-500">
                        Tallas: {tallas.slice(0, 8).join(' · ')}
                        {tallas.length > 8 ? ' …' : ''}
                    </p>
                )}
            </div>
        </Link>
    );
}
