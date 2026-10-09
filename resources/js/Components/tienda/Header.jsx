import { useCarrito } from '@/Contexts/CarritoContext';
import { Link, router, usePage } from '@inertiajs/react';
import { Menu, Search, ShoppingBag, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const NAV = [
    { label: 'Inicio', href: '/' },
    { label: 'Catálogo', href: '/catalogo' },
    { label: 'Dama', href: '/catalogo?categoria=dama' },
    { label: 'Caballero', href: '/catalogo?categoria=caballero' },
    { label: 'Niños', href: '/catalogo?categoria=nino' },
    { label: 'Ofertas', href: '/catalogo?categoria=ofertas', destacado: true },
    { label: 'Marcas', href: '/marcas' },
];

export default function Header() {
    const { url } = usePage();
    const { cantidad, setAbierto } = useCarrito();
    const [menu, setMenu] = useState(false);
    const [buscar, setBuscar] = useState(false);
    const [texto, setTexto] = useState('');
    const [scroll, setScroll] = useState(false);

    useEffect(() => {
        const onScroll = () => setScroll(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => setMenu(false), [url]);

    const activo = (href) => url === href || (href === '/marcas' && url.startsWith('/marcas'));

    const enviarBusqueda = (e) => {
        e.preventDefault();
        if (!texto.trim()) return;
        setBuscar(false);
        router.get('/catalogo', { search: texto.trim() });
    };

    return (
        <>
            <div className="overflow-hidden bg-ink py-2 text-xs font-semibold uppercase tracking-widest text-white">
                <div className="flex w-max animate-marquee gap-12 whitespace-nowrap">
                    {Array.from({ length: 2 }).flatMap((_, k) =>
                        [
                            'Tenis 100% originales',
                            'No box, sí precio',
                            'Zacapa · Chiquimula · Esquipulas',
                            'Pide por WhatsApp',
                            'Nuevos modelos cada semana',
                        ].map((t) => (
                            <span key={`${k}-${t}`} className="flex items-center gap-12">
                                {t} <span className="text-brand">✦</span>
                            </span>
                        )),
                    )}
                </div>
            </div>

            <header
                className={`sticky top-0 z-40 border-b bg-white/90 backdrop-blur-md transition-shadow ${scroll ? 'border-neutral-200 shadow-sm' : 'border-transparent'}`}
            >
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:h-20">
                    <button
                        onClick={() => setMenu(true)}
                        className="-ml-2 rounded-full p-2 hover:bg-neutral-100 lg:hidden"
                        aria-label="Abrir menú"
                    >
                        <Menu className="h-6 w-6" />
                    </button>

                    <Link href="/" className="flex shrink-0 items-center" aria-label="Tenisline, inicio">
                        <img
                            src="/images/logo.png"
                            alt="Tenisline"
                            className="h-12 w-auto mix-blend-multiply lg:h-16"
                        />
                    </Link>

                    <nav className="ml-6 hidden items-center gap-1 lg:flex">
                        {NAV.map((n) => (
                            <Link
                                key={n.href}
                                href={n.href}
                                className={`relative rounded-full px-3.5 py-2 text-sm font-semibold transition-colors ${
                                    n.destacado
                                        ? 'text-brand hover:bg-brand-light'
                                        : activo(n.href)
                                          ? 'bg-neutral-900 text-white'
                                          : 'text-neutral-700 hover:bg-neutral-100'
                                }`}
                            >
                                {n.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1">
                        <form
                            onSubmit={enviarBusqueda}
                            className="hidden items-center rounded-full bg-neutral-100 px-4 md:flex"
                        >
                            <Search className="h-4 w-4 text-neutral-500" />
                            <input
                                value={texto}
                                onChange={(e) => setTexto(e.target.value)}
                                placeholder="Buscar modelo, marca o código"
                                className="w-56 border-0 bg-transparent py-2.5 text-sm focus:ring-0 xl:w-72"
                            />
                        </form>
                        <button
                            onClick={() => setBuscar((b) => !b)}
                            className="rounded-full p-2.5 hover:bg-neutral-100 md:hidden"
                            aria-label="Buscar"
                        >
                            <Search className="h-5 w-5" />
                        </button>
                        <button
                            onClick={() => setAbierto(true)}
                            className="relative rounded-full p-2.5 hover:bg-neutral-100"
                            aria-label="Abrir carrito"
                        >
                            <ShoppingBag className="h-6 w-6" />
                            {cantidad > 0 && (
                                <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 animate-in zoom-in items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white">
                                    {cantidad}
                                </span>
                            )}
                        </button>
                    </div>
                </div>

                {buscar && (
                    <form onSubmit={enviarBusqueda} className="border-t px-4 py-3 md:hidden">
                        <div className="flex items-center rounded-full bg-neutral-100 px-4">
                            <Search className="h-4 w-4 text-neutral-500" />
                            <input
                                autoFocus
                                value={texto}
                                onChange={(e) => setTexto(e.target.value)}
                                placeholder="Buscar modelo, marca o código"
                                className="w-full border-0 bg-transparent py-3 text-sm focus:ring-0"
                            />
                        </div>
                    </form>
                )}
            </header>

            {/* Menú móvil */}
            <div
                className={`fixed inset-0 z-50 lg:hidden ${menu ? '' : 'pointer-events-none'}`}
                aria-hidden={!menu}
            >
                <div
                    onClick={() => setMenu(false)}
                    className={`absolute inset-0 bg-black/40 transition-opacity ${menu ? 'opacity-100' : 'opacity-0'}`}
                />
                <aside
                    className={`absolute inset-y-0 left-0 flex w-80 max-w-[85%] flex-col bg-white shadow-xl transition-transform duration-300 ${menu ? 'translate-x-0' : '-translate-x-full'}`}
                >
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <img src="/images/logo.png" alt="Tenisline" className="h-9 mix-blend-multiply" />
                        <button onClick={() => setMenu(false)} className="rounded-full p-2 hover:bg-neutral-100" aria-label="Cerrar menú">
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                    <nav className="flex flex-col p-3">
                        {NAV.map((n) => (
                            <Link
                                key={n.href}
                                href={n.href}
                                className={`rounded-xl px-4 py-3 text-lg font-semibold ${n.destacado ? 'text-brand' : 'text-neutral-800'} hover:bg-neutral-100`}
                            >
                                {n.label}
                            </Link>
                        ))}
                    </nav>
                </aside>
            </div>
        </>
    );
}
