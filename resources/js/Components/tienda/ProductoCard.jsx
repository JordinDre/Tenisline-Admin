import ImagenProducto from '@/Components/tienda/ImagenProducto';
import { capitalizar, descuento, quetzales } from '@/lib/tienda';
import { Link } from '@inertiajs/react';

export default function ProductoCard({ producto, indice = 0 }) {
    const pct = descuento(producto);
    const tallas = producto.tallas ?? [];

    return (
        <Link
            href={route('producto', producto.slug)}
            className="group flex h-full animate-fade-up flex-col"
            style={{ animationDelay: `${Math.min(indice, 11) * 80}ms` }}
        >
            <div className="relative aspect-[4/5] overflow-hidden rounded-2xl bg-neutral-100">
                <ImagenProducto
                    src={producto.imagen}
                    alt={producto.descripcion}
                    className="h-full w-full transition-transform duration-700 ease-[cubic-bezier(.22,1,.36,1)] group-hover:scale-[1.06]"
                />

                <div className="absolute inset-x-3 top-3 flex items-start justify-between gap-2">
                    {pct > 0 ? (
                        <span className="rounded-full bg-brand px-2.5 py-1 text-xs font-bold leading-none text-white ">-{pct}%</span>
                    ) : (
                        <span />
                    )}
                    {producto.genero && (
                        <span className="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase leading-none tracking-wide text-neutral-700 backdrop-blur">
                            {capitalizar(producto.genero)}
                        </span>
                    )}
                </div>

                <span className="pointer-events-none absolute inset-x-3 bottom-3 hidden translate-y-3 rounded-full bg-ink py-2.5 text-center text-sm font-semibold text-white opacity-0 transition-all duration-500 group-hover:translate-y-0 group-hover:opacity-100 md:block">
                    Ver tallas
                </span>
            </div>

            <div className="flex flex-1 flex-col pt-3">
                <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-500">{producto.marca}</p>
                <h3 className="mt-1 line-clamp-2 min-h-[2.5rem] text-[15px] font-semibold leading-5 text-neutral-900 transition-colors group-hover:text-brand">
                    {capitalizar(producto.descripcion)}
                </h3>
                <p className="mt-0.5 truncate text-sm text-neutral-500">{producto.color ? capitalizar(producto.color) : ' '}</p>
                <div className="mt-auto flex items-baseline gap-2 pt-2">
                    <span className={`text-base font-bold ${pct > 0 ? 'text-brand' : 'text-neutral-900'}`}>
                        {quetzales(producto.precio_oferta || producto.precio)}
                    </span>
                    {pct > 0 && <span className="text-sm text-neutral-400 line-through">{quetzales(producto.precio)}</span>}
                </div>
                <p className="mt-1 truncate text-xs text-neutral-500">
                    {tallas.length ? `Tallas ${tallas.slice(0, 6).join(' · ')}${tallas.length > 6 ? ' …' : ''}` : ' '}
                </p>
            </div>
        </Link>
    );
}
