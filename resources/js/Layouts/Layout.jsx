import CarritoDrawer from '@/Components/tienda/CarritoDrawer';
import Footer from '@/Components/tienda/Footer';
import Header from '@/Components/tienda/Header';
import { CarritoProvider } from '@/Contexts/CarritoContext';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

// Layout del sitio público de Tenisline (header, carrito, footer y botón de WhatsApp).
export default function Layout({ children }) {
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const [whatsapp, setWhatsapp] = useState(false);

    return (
        <CarritoProvider>
            <div className="flex min-h-screen flex-col bg-white">
                <Header />
                <main className="flex-1">{children}</main>
                <Footer />
            </div>
            <CarritoDrawer />

            <div className="fixed bottom-5 right-5 z-30 flex flex-col items-end gap-2">
                {whatsapp && (
                    <div className="w-60 animate-in fade-in slide-in-from-bottom-2 rounded-2xl bg-white p-3 shadow-2xl ring-1 ring-black/5">
                        <p className="px-2 pb-2 text-sm font-semibold">Escríbenos a la tienda:</p>
                        {sucursales.map((s) => (
                            <a
                                key={s.nombre}
                                href={`https://wa.me/${s.telefono}?text=${encodeURIComponent('¡Hola Tenisline! Quiero información sobre unos tenis.')}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="block rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-neutral-100"
                            >
                                {s.nombre}
                            </a>
                        ))}
                    </div>
                )}
                <button
                    onClick={() => setWhatsapp((v) => !v)}
                    className="flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition-transform hover:scale-110"
                    aria-label="Escribir por WhatsApp"
                >
                    <svg viewBox="0 0 24 24" className="h-7 w-7 fill-current" aria-hidden="true">
                        <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.49-.23-.36A9.43 9.43 0 0 1 2.6 12C2.6 6.8 6.84 2.56 12.05 2.56c2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.68c0 5.2-4.24 9.44-9.44 9.44zm8.03-17.47A11.3 11.3 0 0 0 12.04.7C5.79.7.7 5.78.7 12.04c0 2 .52 3.95 1.52 5.67L.6 23.3l5.73-1.5a11.3 11.3 0 0 0 5.7 1.45h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.31-8.01z" />
                    </svg>
                </button>
            </div>
        </CarritoProvider>
    );
}
