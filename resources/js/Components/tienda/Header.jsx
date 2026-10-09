import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import { useCarrito } from '@/Contexts/CarritoContext';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Menu, MessageCircle, Search, ShoppingBag, Store, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export const NAV = [
    { label: 'Novedades', href: '/catalogo' },
    { label: 'Dama', href: '/catalogo?categoria=dama' },
    { label: 'Caballero', href: '/catalogo?categoria=caballero' },
    { label: 'Niños', href: '/catalogo?categoria=nino' },
    { label: 'Marcas', href: '/marcas' },
    { label: 'Ofertas', href: '/catalogo?categoria=ofertas', destacado: true },
];

// Avisos por defecto; se reemplazan por los que se configuran en Filament > Promociones web
const AVISOS_BASE = [
    { texto: 'Arma tu bolsa y envíanos tu pedido por WhatsApp', enlace: '/catalogo', cta: 'Ver catálogo' },
    { texto: 'Precios especiales en modelos seleccionados', enlace: '/catalogo?categoria=ofertas', cta: 'Ver ofertas' },
];

function BarraAvisos({ avisos }) {
    const AVISOS = avisos?.length ? avisos : AVISOS_BASE;
    const [i, setI] = useState(0);
    useEffect(() => {
        if (AVISOS.length < 2) return;
        const t = setInterval(() => setI((n) => (n + 1) % AVISOS.length), 5000);
        return () => clearInterval(t);
    }, [AVISOS.length]);
    const aviso = AVISOS[i % AVISOS.length];

    return (
        <div className="border-b bg-neutral-100">
            <div className="mx-auto flex h-11 max-w-[1600px] items-center justify-between px-2 sm:px-6">
                <button onClick={() => setI((n) => (n - 1 + AVISOS.length) % AVISOS.length)} className="p-2 text-neutral-600 hover:text-black" aria-label="Aviso anterior">
                    <ChevronLeft className="h-4 w-4" />
                </button>
                <p key={i} className="flex animate-in items-center gap-3 truncate text-center text-[13px] text-neutral-800 duration-500 fade-in">
                    <span className="truncate">{aviso.texto}</span>
                    {aviso.enlace && aviso.cta && (
                        <Link href={aviso.enlace} className="hidden shrink-0 font-medium underline underline-offset-4 sm:inline">{aviso.cta}</Link>
                    )}
                </p>
                <button onClick={() => setI((n) => (n + 1) % AVISOS.length)} className="p-2 text-neutral-600 hover:text-black" aria-label="Siguiente aviso">
                    <ChevronRight className="h-4 w-4" />
                </button>
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
    const [oculto, setOculto] = useState(false);

    // Como en Nike: el header se esconde al bajar y reaparece al subir
    useEffect(() => {
        let ultimo = window.scrollY;
        const onScroll = () => {
            const y = window.scrollY;
            setOculto(y > 140 && y > ultimo);
            ultimo = y;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        setMenu(false);
        setBuscando(false);
    }, [url]);

    const buscar = (e) => {
        e.preventDefault();
        if (texto.trim()) router.get('/catalogo', { search: texto.trim() });
    };

    return (
        <>
            {/* Barra de utilidades */}
            <div className="hidden bg-neutral-100 md:block">
                <div className="mx-auto flex h-9 max-w-[1600px] items-center justify-between px-6 text-xs font-medium">
                    <span className="flex items-center gap-1.5 text-neutral-600"><Store className="h-3.5 w-3.5" /> Zacapa · Chiquimula · Esquipulas</span>
                    <div className="flex items-center gap-4">
                        {sucursales[0] && (
                            <a href={`https://wa.me/${sucursales[0].telefono}`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1.5 hover:text-neutral-500">
                                <MessageCircle className="h-3.5 w-3.5" /> Ayuda por WhatsApp
                            </a>
                        )}
                        <span className="h-3 w-px bg-neutral-300" />
                        <Link href="/marcas" className="hover:text-neutral-500">Marcas</Link>
                    </div>
                </div>
            </div>

            <header className={cn('sticky top-0 z-40 bg-white transition-transform duration-300', oculto && !buscando && '-translate-y-full')}>
                <div className="mx-auto grid h-16 max-w-[1600px] grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-6 lg:h-[72px]">
                    <div className="flex items-center gap-1">
                        <Sheet open={menu} onOpenChange={setMenu}>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="-ml-2 rounded-full lg:hidden" aria-label="Abrir menú">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-[85%] max-w-sm p-0">
                                <SheetHeader className="border-b px-6 py-4 text-left">
                                    <SheetTitle><img src="/images/logo.png" alt="Tenisline" className="h-10 mix-blend-multiply" /></SheetTitle>
                                </SheetHeader>
                                <nav className="flex flex-col px-3 py-4">
                                    {NAV.map((n) => (
                                        <Link key={n.href} href={n.href} className={cn('flex items-center justify-between rounded-lg px-3 py-3.5 text-2xl font-medium tracking-tight hover:bg-neutral-100', n.destacado && 'text-red-600')}>
                                            {n.label} <ChevronRight className="h-5 w-5 text-neutral-400" />
                                        </Link>
                                    ))}
                                </nav>
                                <div className="mx-6 border-t pt-6 text-sm text-neutral-600">
                                    <p className="mb-3 font-medium text-black">Escríbenos</p>
                                    {sucursales.map((s) => (
                                        <a key={s.nombre} href={`https://wa.me/${s.telefono}`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 py-1.5 hover:text-black">
                                            <MessageCircle className="h-4 w-4" /> {s.nombre}
                                        </a>
                                    ))}
                                </div>
                            </SheetContent>
                        </Sheet>
                        <Link href="/" aria-label="Tenisline, inicio" className="shrink-0">
                            <img src="/images/logo.png" alt="Tenisline" className="h-11 w-auto mix-blend-multiply lg:h-14" />
                        </Link>
                    </div>

                    <nav className="hidden items-center lg:flex">
                        {NAV.map((n) => (
                            <Link
                                key={n.href}
                                href={n.href}
                                className={cn(
                                    'relative px-3 py-2 text-[15px] font-medium transition-colors after:absolute after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 xl:px-4',
                                    url === n.href && 'after:scale-x-100',
                                    n.destacado && 'text-red-600',
                                )}
                            >
                                {n.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center justify-end gap-1">
                        <form onSubmit={buscar} className="hidden items-center rounded-full bg-neutral-100 pl-1 transition-colors focus-within:bg-neutral-200 md:flex">
                            <Button type="submit" variant="ghost" size="icon" className="h-9 w-9 rounded-full hover:bg-neutral-300" aria-label="Buscar">
                                <Search className="h-[18px] w-[18px]" />
                            </Button>
                            <input value={texto} onChange={(e) => setTexto(e.target.value)} placeholder="Buscar"
                                className="w-36 border-0 bg-transparent py-2 pl-1 pr-4 text-[15px] placeholder:text-neutral-500 focus:ring-0 xl:w-48" />
                        </form>
                        <Button variant="ghost" size="icon" className="rounded-full md:hidden" onClick={() => setBuscando(true)} aria-label="Buscar">
                            <Search className="h-5 w-5" />
                        </Button>
                        <Button variant="ghost" size="icon" className="relative rounded-full" onClick={() => setAbierto(true)} aria-label="Abrir bolsa">
                            <ShoppingBag className="h-5 w-5" />
                            {cantidad > 0 && (
                                <span className="absolute right-0.5 top-0.5 flex h-[18px] min-w-[18px] animate-in items-center justify-center rounded-full bg-black px-1 text-[10px] font-semibold text-white zoom-in">
                                    {cantidad}
                                </span>
                            )}
                        </Button>
                    </div>
                </div>

                {buscando && (
                    <div className="absolute inset-x-0 top-0 z-50 flex h-16 animate-in items-center gap-2 bg-white px-4 fade-in slide-in-from-top-2 md:hidden">
                        <form onSubmit={buscar} className="flex flex-1 items-center rounded-full bg-neutral-100 px-3">
                            <Search className="h-4 w-4 text-neutral-500" />
                            <input autoFocus value={texto} onChange={(e) => setTexto(e.target.value)} placeholder="Buscar modelo, marca o código"
                                className="w-full border-0 bg-transparent py-2.5 text-[15px] focus:ring-0" />
                        </form>
                        <Button variant="ghost" size="icon" className="rounded-full" onClick={() => setBuscando(false)} aria-label="Cerrar búsqueda">
                            <X className="h-5 w-5" />
                        </Button>
                    </div>
                )}
            </header>

            <BarraAvisos avisos={props.tienda?.avisos} />
        </>
    );
}
