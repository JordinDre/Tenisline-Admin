import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import { useCarrito } from '@/Contexts/CarritoContext';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, Menu, Search, ShoppingBag, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export const NAV = [
    { label: 'Inicio', href: '/' },
    { label: 'Catálogo', href: '/catalogo' },
    { label: 'Dama', href: '/catalogo?categoria=dama' },
    { label: 'Caballero', href: '/catalogo?categoria=caballero' },
    { label: 'Niños', href: '/catalogo?categoria=nino' },
    { label: 'Marcas', href: '/marcas' },
    { label: 'Ofertas', href: '/catalogo?categoria=ofertas', destacado: true },
];

// Textos por defecto de la cinta superior; se reemplazan con los avisos de Filament > Promociones web
const AVISOS_BASE = ['No box, sí precio', 'Zacapa · Chiquimula · Esquipulas', 'Pide por WhatsApp', 'Nuevos modelos cada semana'];

function Cinta({ avisos }) {
    const textos = avisos?.length ? avisos.map((a) => a.texto) : AVISOS_BASE;
    // Se repite para que el desplazamiento sea continuo sin saltos
    const fila = [...textos, ...textos, ...textos, ...textos];

    return (
        <div className="relative overflow-hidden bg-ink text-white">
            <div className="flex h-9 w-max animate-marquee items-center hover:[animation-play-state:paused]">
                {[0, 1].map((copia) => (
                    <div key={copia} className="flex shrink-0 items-center" aria-hidden={copia === 1}>
                        {fila.map((t, n) => (
                            <span key={n} className="flex items-center whitespace-nowrap px-6 text-[11px] font-semibold uppercase tracking-[0.2em]">
                                {t}
                                <span className="ml-12 text-brand">✦</span>
                            </span>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function Header() {
    const { url, props } = usePage();
    const sucursales = props.tienda?.sucursales ?? [];
    const { cantidad, setAbierto } = useCarrito();
    const [menu, setMenu] = useState(false);
    const [buscando, setBuscando] = useState(false);
    const [texto, setTexto] = useState('');
    const [sombra, setSombra] = useState(false);

    useEffect(() => {
        const onScroll = () => setSombra(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        setMenu(false);
        setBuscando(false);
    }, [url]);

    const activo = (href) => url === href || (href === '/marcas' && url.startsWith('/marcas'));

    const buscar = (e) => {
        e.preventDefault();
        if (texto.trim()) router.get('/catalogo', { search: texto.trim() });
    };

    return (
        <>
            <Cinta avisos={props.tienda?.avisos} />

            <header className={cn('sticky top-0 z-40 border-b bg-white/85 backdrop-blur-xl transition-[box-shadow,border-color] duration-300', sombra ? 'border-neutral-200 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.15)]' : 'border-transparent')}>
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:h-20 lg:px-8">
                    <Sheet open={menu} onOpenChange={setMenu}>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="-ml-2 h-10 w-10 rounded-full lg:hidden" aria-label="Abrir menú">
                                <Menu className="h-6 w-6" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="flex w-[86%] max-w-sm flex-col p-0">
                            <SheetHeader className="border-b px-5 py-4 text-left">
                                <SheetTitle><img src="/images/logo.png" alt="Tenisline" className="h-10 mix-blend-multiply" /></SheetTitle>
                                <SheetDescription className="sr-only">Menú de navegación</SheetDescription>
                            </SheetHeader>
                            <nav className="flex flex-col p-3">
                                {NAV.map((n, i) => (
                                    <Link key={n.href} href={n.href}
                                        className={cn('flex animate-fade-up items-center justify-between rounded-xl px-4 py-3.5 text-lg font-semibold transition-colors hover:bg-neutral-100', n.destacado ? 'text-brand' : 'text-neutral-900', activo(n.href) && 'bg-neutral-100')}
                                        style={{ animationDelay: `${i * 35}ms` }}>
                                        {n.label} <ChevronRight className="h-5 w-5 text-neutral-400" />
                                    </Link>
                                ))}
                            </nav>
                            <div className="mt-auto border-t p-5">
                                <p className="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-neutral-500">Escríbenos</p>
                                <div className="grid grid-cols-3 gap-2">
                                    {sucursales.map((s) => (
                                        <a key={s.nombre} href={`https://wa.me/${s.telefono}`} target="_blank" rel="noopener noreferrer"
                                            className="rounded-xl bg-neutral-100 px-2 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-[#25D366] hover:text-white">
                                            {s.nombre}
                                        </a>
                                    ))}
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>

                    <Link href="/" className="flex shrink-0 items-center" aria-label="Tenisline, inicio">
                        <img src="/images/logo.png" alt="Tenisline" className="h-11 w-auto mix-blend-multiply transition-transform duration-300 hover:scale-105 lg:h-14" />
                    </Link>

                    <nav className="ml-4 hidden items-center gap-0.5 lg:flex xl:ml-8">
                        {NAV.map((n) => (
                            <Link key={n.href} href={n.href}
                                className={cn('rounded-full px-3.5 py-2 text-sm font-semibold transition-colors xl:px-4',
                                    n.destacado ? 'text-brand hover:bg-brand-light' : activo(n.href) ? 'bg-ink text-white' : 'text-neutral-700 hover:bg-neutral-100 hover:text-ink')}>
                                {n.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1">
                        <form onSubmit={buscar} className="group hidden h-10 items-center rounded-full bg-neutral-100 pl-4 pr-1 ring-brand/30 transition-all focus-within:bg-white focus-within:ring-2 md:flex">
                            <Search className="h-4 w-4 shrink-0 text-neutral-500" />
                            <input value={texto} onChange={(e) => setTexto(e.target.value)} placeholder="Buscar modelo, marca o código"
                                className="h-full w-48 border-0 bg-transparent px-2.5 text-sm placeholder:text-neutral-500 focus:ring-0 xl:w-64" />
                        </form>
                        <Button variant="ghost" size="icon" className="h-10 w-10 rounded-full md:hidden" onClick={() => setBuscando(true)} aria-label="Buscar">
                            <Search className="h-5 w-5" />
                        </Button>
                        <Button variant="ghost" size="icon" className="relative h-10 w-10 rounded-full" onClick={() => setAbierto(true)} aria-label="Abrir carrito">
                            <ShoppingBag className="h-[22px] w-[22px]" />
                            {cantidad > 0 && (
                                <span key={cantidad} className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 animate-in items-center justify-center rounded-full bg-brand px-1 text-[11px] font-bold text-white ring-2 ring-white zoom-in-50">
                                    {cantidad}
                                </span>
                            )}
                        </Button>
                    </div>
                </div>

                {buscando && (
                    <div className="absolute inset-0 z-10 flex animate-in items-center gap-2 bg-white px-4 duration-200 fade-in md:hidden">
                        <form onSubmit={buscar} className="flex h-11 flex-1 items-center rounded-full bg-neutral-100 px-4">
                            <Search className="h-4 w-4 text-neutral-500" />
                            <input autoFocus value={texto} onChange={(e) => setTexto(e.target.value)} placeholder="Buscar modelo, marca o código"
                                className="h-full w-full border-0 bg-transparent px-2.5 text-[15px] focus:ring-0" />
                        </form>
                        <Button variant="ghost" size="icon" className="h-10 w-10 rounded-full" onClick={() => setBuscando(false)} aria-label="Cerrar búsqueda">
                            <X className="h-5 w-5" />
                        </Button>
                    </div>
                )}
            </header>
        </>
    );
}

