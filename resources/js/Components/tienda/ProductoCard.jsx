import ImagenProducto from '@/Components/tienda/ImagenProducto';
import { capitalizar, descuento, quetzales } from '@/lib/tienda';
import { Link } from '@inertiajs/react';

const GENERO = { CABALLERO: 'Hombre', DAMA: 'Mujer', 'NIÑO': 'Niño', INFANTE: 'Infante' };

/** Tarjeta de modelo, estilo tienda oficial: foto sobre gris, etiqueta, nombre, subtítulo y precio. */
export default function ProductoCard({ producto, indice = 0 }) {
    const pct = descuento(producto);
    const tallas = producto.tallas ?? [];
    const etiqueta = pct > 0 ? `Oferta -${pct}%` : null;

    return (
        <Link
            href={route('producto', producto.slug)}
            className="group block animate-in fade-in slide-in-from-bottom-2 fill-mode-both duration-500"
            style={{ animationDelay: `${Math.min(indice, 11) * 35}ms` }}
        >
            <div className="relative aspect-square overflow-hidden bg-neutral-100">
                <ImagenProducto
                    src={producto.imagen}
                    alt={producto.descripcion}
                    className="h-full w-full transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                />
                {tallas.length > 0 && (
                    <div className="absolute inset-x-3 bottom-3 hidden translate-y-2 flex-wrap gap-1 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 md:flex">
                        {tallas.slice(0, 7).map((t) => (
                            <span key={t} className="rounded bg-white/95 px-2 py-1 text-xs font-medium shadow-sm">{t}</span>
                        ))}
                        {tallas.length > 7 && <span className="rounded bg-white/95 px-2 py-1 text-xs font-medium shadow-sm">+{tallas.length - 7}</span>}
                    </div>
                )}
            </div>

            <div className="pb-2 pt-3">
                {etiqueta && <p className={`text-[15px] font-medium ${pct > 0 ? 'text-red-600' : 'text-orange-700'}`}>{etiqueta}</p>}
                <h3 className="line-clamp-2 text-[15px] font-medium leading-snug text-neutral-900">
                    {producto.marca && <span>{capitalizar(producto.marca)} </span>}
                    {capitalizar(producto.descripcion)}
                </h3>
                <p className="text-[15px] text-neutral-500">
                    {[GENERO[producto.genero] ?? capitalizar(producto.genero ?? ''), producto.color && capitalizar(producto.color)].filter(Boolean).join(' · ')}
                </p>
                {tallas.length > 0 && (
                    <p className="text-[15px] text-neutral-500">{tallas.length} {tallas.length === 1 ? 'talla' : 'tallas'}</p>
                )}
                <div className="mt-2 flex items-baseline gap-2">
                    <span className="text-[15px] font-medium">{quetzales(producto.precio_oferta || producto.precio)}</span>
                    {pct > 0 && <span className="text-[15px] text-neutral-500 line-through">{quetzales(producto.precio)}</span>}
                </div>
            </div>
        </Link>
    );
}
