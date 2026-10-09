import { Link, usePage } from '@inertiajs/react';
import { MapPin } from 'lucide-react';

const telefono = (t) => `+${t.slice(0, 3)} ${t.slice(3, 7)}-${t.slice(7)}`;

export default function Footer() {
    const sucursales = usePage().props.tienda?.sucursales ?? [];

    return (
        <footer className="mt-24 border-t bg-white">
            <div className="mx-auto grid max-w-[1600px] gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr_2fr]">
                <div>
                    <img src="/images/logo.png" alt="Tenisline" className="h-16 w-auto mix-blend-multiply" />
                    <p className="mt-4 max-w-xs text-sm leading-relaxed text-neutral-500">
                        Las mejores marcas de tenis, a precios que no vas a encontrar en otro lado.
                    </p>
                </div>

                <div>
                    <h3 className="mb-4 text-sm font-medium">Comprar</h3>
                    <ul className="space-y-3 text-sm text-neutral-500">
                        <li><Link href="/catalogo" className="hover:text-black">Novedades</Link></li>
                        <li><Link href="/catalogo?categoria=dama" className="hover:text-black">Dama</Link></li>
                        <li><Link href="/catalogo?categoria=caballero" className="hover:text-black">Caballero</Link></li>
                        <li><Link href="/catalogo?categoria=nino" className="hover:text-black">Niños</Link></li>
                        <li><Link href="/catalogo?categoria=ofertas" className="hover:text-black">Ofertas</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 className="mb-4 text-sm font-medium">Ayuda</h3>
                    <ul className="space-y-3 text-sm text-neutral-500">
                        <li><Link href="/marcas" className="hover:text-black">Marcas</Link></li>
                        <li>Pedidos por WhatsApp</li>
                        <li>Sin pagos en línea</li>
                        <li><a href="/admin" className="hover:text-black">Acceso colaboradores</a></li>
                    </ul>
                </div>

                <div>
                    <h3 className="mb-4 text-sm font-medium">Tiendas</h3>
                    <ul className="grid gap-5 sm:grid-cols-3">
                        {sucursales.map((s) => (
                            <li key={s.nombre} className="text-sm">
                                <p className="font-medium">{s.nombre}</p>
                                {s.direccion && (
                                    <p className="mt-1 flex gap-1.5 text-neutral-500"><MapPin className="mt-0.5 h-3.5 w-3.5 shrink-0" />{s.direccion}</p>
                                )}
                                <a href={`https://wa.me/${s.telefono}`} target="_blank" rel="noopener noreferrer" className="mt-2 inline-block text-neutral-500 underline-offset-4 hover:text-black hover:underline">
                                    {telefono(s.telefono)}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
            <div className="mx-auto flex max-w-[1600px] flex-col justify-between gap-2 border-t px-4 py-6 text-xs text-neutral-500 sm:flex-row sm:px-6">
                <p>© {new Date().getFullYear()} Tenisline. Todos los derechos reservados.</p>
                <p>No box, sí precio.</p>
            </div>
        </footer>
    );
}
