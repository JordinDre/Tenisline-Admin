import { Link, usePage } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { IconoWhatsApp } from './CarritoDrawer';

const telefono = (t) => `+${t.slice(0, 3)} ${t.slice(3, 7)}-${t.slice(7)}`;

const ENLACES = [
    ['Catálogo completo', '/catalogo'],
    ['Dama', '/catalogo?categoria=dama'],
    ['Caballero', '/catalogo?categoria=caballero'],
    ['Niños', '/catalogo?categoria=nino'],
    ['Marcas', '/marcas'],
];

export default function Footer() {
    const sucursales = usePage().props.tienda?.sucursales ?? [];

    return (
        <footer className="relative mt-24 overflow-hidden bg-ink text-neutral-300">
            <div className="pointer-events-none absolute -right-40 -top-40 h-96 w-96 rounded-full bg-brand/20 blur-3xl" />
            <div className="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:px-8">
                <div className="lg:col-span-3">
                    <img src="/images/logo.png" alt="Tenisline" className="h-16 w-auto invert mix-blend-screen" />
                    <p className="mt-5 max-w-xs text-sm leading-relaxed text-neutral-400">
                        Las mejores marcas de tenis, a precios que no vas a encontrar en otro lado.
                    </p>
                    <p className="mt-2 font-display text-sm uppercase tracking-wide text-white">No box, sí precio.</p>
                </div>

                <div className="lg:col-span-2">
                    <h3 className="mb-5 font-display text-xs uppercase tracking-[0.2em] text-white">Comprar</h3>
                    <ul className="space-y-3 text-sm">
                        {ENLACES.map(([t, h]) => (
                            <li key={h}><Link href={h} className="transition-colors hover:text-white">{t}</Link></li>
                        ))}
                        <li><Link href="/catalogo?categoria=ofertas" className="font-semibold text-brand transition-colors hover:text-white">Ofertas</Link></li>
                    </ul>
                </div>

                <div className="md:col-span-2 lg:col-span-7">
                    <h3 className="mb-5 font-display text-xs uppercase tracking-[0.2em] text-white">Nuestras tiendas</h3>
                    <ul className="grid gap-4 sm:grid-cols-3">
                        {sucursales.map((s) => (
                            <li key={s.nombre} className="flex flex-col rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition-colors hover:border-white/25">
                                <p className="font-semibold text-white">{s.nombre}</p>
                                {s.direccion && (
                                    <p className="mt-2 flex gap-1.5 text-xs leading-relaxed text-neutral-400">
                                        <MapPin className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        {s.direccion}
                                    </p>
                                )}
                                <a href={`https://wa.me/${s.telefono}`} target="_blank" rel="noopener noreferrer"
                                    className="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-[#25D366] hover:underline">
                                    <IconoWhatsApp className="h-4 w-4" /> {telefono(s.telefono)}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="relative border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-neutral-500 sm:flex-row sm:px-6 lg:px-8">
                    <p>© {new Date().getFullYear()} Tenisline. Todos los derechos reservados.</p>
                    <a href="/admin" className="transition-colors hover:text-neutral-300">Acceso colaboradores</a>
                </div>
            </div>
        </footer>
    );
}
