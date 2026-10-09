import { Link, usePage } from '@inertiajs/react';
import { MapPin, MessageCircle } from 'lucide-react';

export default function Footer() {
    const { tienda } = usePage().props;
    const sucursales = tienda?.sucursales ?? [];
    const anio = new Date().getFullYear();

    return (
        <footer className="mt-24 bg-ink text-neutral-300">
            <div className="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <img src="/images/logo.png" alt="Tenisline" className="h-20 w-auto invert mix-blend-screen" />
                    <p className="mt-4 max-w-xs text-sm leading-relaxed text-neutral-400">
                        Tenis originales de las mejores marcas a precios que no vas a encontrar en otro lado.
                        <span className="mt-1 block font-semibold text-white">No box, sí precio.</span>
                    </p>
                </div>

                <div>
                    <h3 className="mb-4 font-display text-sm uppercase tracking-widest text-white">Comprar</h3>
                    <ul className="space-y-2.5 text-sm">
                        <li><Link href="/catalogo" className="hover:text-white">Catálogo completo</Link></li>
                        <li><Link href="/catalogo?categoria=dama" className="hover:text-white">Dama</Link></li>
                        <li><Link href="/catalogo?categoria=caballero" className="hover:text-white">Caballero</Link></li>
                        <li><Link href="/catalogo?categoria=nino" className="hover:text-white">Niños</Link></li>
                        <li><Link href="/catalogo?categoria=ofertas" className="text-brand hover:text-white">Ofertas</Link></li>
                        <li><Link href="/marcas" className="hover:text-white">Marcas</Link></li>
                    </ul>
                </div>

                <div className="lg:col-span-2">
                    <h3 className="mb-4 font-display text-sm uppercase tracking-widest text-white">Nuestras tiendas</h3>
                    <ul className="grid gap-4 sm:grid-cols-3">
                        {sucursales.map((s) => (
                            <li key={s.nombre} className="rounded-2xl border border-white/10 p-4">
                                <p className="font-semibold text-white">{s.nombre}</p>
                                {s.direccion && (
                                    <p className="mt-1 flex gap-1.5 text-xs text-neutral-400">
                                        <MapPin className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        {s.direccion}
                                    </p>
                                )}
                                <a
                                    href={`https://wa.me/${s.telefono}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-[#25D366] hover:underline"
                                >
                                    <MessageCircle className="h-4 w-4" />
                                    +{s.telefono.slice(0, 3)} {s.telefono.slice(3, 7)}-{s.telefono.slice(7)}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-neutral-500 sm:flex-row sm:px-6">
                    <p>© {anio} Tenisline. Todos los derechos reservados.</p>
                    <a href="/admin" className="hover:text-neutral-300">Acceso colaboradores</a>
                </div>
            </div>
        </footer>
    );
}
