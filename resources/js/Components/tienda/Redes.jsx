import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { Facebook, Instagram } from 'lucide-react';

const IconoTikTok = (props) => (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
        <path d="M19.6 6.7a5.4 5.4 0 0 1-3.3-1.1 5.4 5.4 0 0 1-2-3.3h-3.4v13.1a2.5 2.5 0 1 1-2.5-2.5c.3 0 .5 0 .8.1V9.5a6 6 0 0 0-.8-.1 5.9 5.9 0 1 0 5.9 5.9V9a8.8 8.8 0 0 0 5.2 1.7V7.3a5.4 5.4 0 0 1-1.9-.6Z" />
    </svg>
);

const REDES = [
    ['instagram', 'Instagram', Instagram],
    ['tiktok', 'TikTok', IconoTikTok],
    ['facebook', 'Facebook', Facebook],
];

/** Iconos de las redes sociales de Tenisline (Instagram, TikTok y Facebook). */
export default function Redes({ className = '', enlace = '' }) {
    const redes = usePage().props.tienda?.redes ?? {};

    return (
        <div className={cn('flex items-center gap-2', className)}>
            {REDES.filter(([clave]) => redes[clave]).map(([clave, nombre, Icono]) => (
                <a key={clave} href={redes[clave]} target="_blank" rel="noopener noreferrer me" aria-label={`Tenisline en ${nombre}`}
                    className={cn('flex h-10 w-10 items-center justify-center rounded-full transition-colors duration-300', enlace)}>
                    <Icono className="h-5 w-5" strokeWidth={1.9} />
                </a>
            ))}
        </div>
    );
}
