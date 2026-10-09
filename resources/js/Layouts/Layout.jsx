import CarritoDrawer, { IconoWhatsApp } from '@/Components/tienda/CarritoDrawer';
import Footer from '@/Components/tienda/Footer';
import Header from '@/Components/tienda/Header';
import { CarritoProvider } from '@/Contexts/CarritoContext';
import { cn } from '@/lib/utils';
import { Head, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function BotonWhatsApp() {
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const [abierto, setAbierto] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!abierto) return;
        const cerrar = (e) => ref.current && !ref.current.contains(e.target) && setAbierto(false);
        document.addEventListener('pointerdown', cerrar);
        return () => document.removeEventListener('pointerdown', cerrar);
    }, [abierto]);

    return (
        <div ref={ref} className="fixed bottom-5 right-5 z-30 flex flex-col items-end gap-3">
            <div className={cn('w-64 origin-bottom-right rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-black/5 transition-all duration-200',
                abierto ? 'scale-100 opacity-100' : 'pointer-events-none scale-95 opacity-0')}>
                <p className="px-3 pb-1 pt-2 text-sm font-bold">Escríbenos a la tienda</p>
                {sucursales.map((s) => (
                    <a key={s.nombre}
                        href={`https://wa.me/${s.telefono}?text=${encodeURIComponent('¡Hola Tenisline! Quiero información sobre unos tenis.')}`}
                        target="_blank" rel="noopener noreferrer"
                        className="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition-colors hover:bg-neutral-100">
                        {s.nombre} <IconoWhatsApp className="h-4 w-4 text-[#25D366]" />
                    </a>
                ))}
            </div>
            <button onClick={() => setAbierto((v) => !v)} aria-label="Escribir por WhatsApp"
                className="flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-[#25D366]/30 transition-transform duration-300 hover:scale-110 active:scale-95">
                {abierto ? <X className="h-6 w-6" /> : <IconoWhatsApp className="h-7 w-7" />}
            </button>
        </div>
    );
}

// Layout del sitio público de Tenisline (header, carrito, footer y botón de WhatsApp).
export default function Layout({ children }) {
    const seo = usePage().props.seo;

    return (
        <CarritoProvider>
            {seo?.title && <Head title={seo.title} />}
            <div className="flex min-h-screen flex-col bg-white">
                <Header />
                <main className="flex-1">{children}</main>
                <Footer />
            </div>
            <CarritoDrawer />
            <BotonWhatsApp />
        </CarritoProvider>
    );
}
